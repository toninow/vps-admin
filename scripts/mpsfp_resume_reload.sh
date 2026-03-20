#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html/mp-admin-vps

LOG="/tmp/mpsfp_resume_reload_$(date +%s).log"

run_normalization() {
  local import_id="$1"
  echo "NORMALIZE import_id=${import_id}" | tee -a "$LOG"
  php artisan imports:run-normalization "$import_id" >>"$LOG" 2>&1
}

create_and_normalize() {
  local supplier="$1"
  local file="$2"

  echo "IMPORT ${supplier} :: $(basename "$file")" | tee -a "$LOG"
  php artisan imports:create-test --supplier="$supplier" --file="$file" >>"$LOG" 2>&1
  local import_id
  import_id="$(php artisan tinker --execute='echo \App\Models\SupplierImport::max("id");' | tail -n 1 | tr -dc '0-9')"
  if [[ -z "$import_id" ]]; then
    echo "ERROR: no se pudo resolver el import_id tras cargar ${supplier}" | tee -a "$LOG"
    exit 1
  fi

  run_normalization "$import_id"
}

echo "[1/5] Limpiando estados colgados..." | tee -a "$LOG"
php artisan tinker >>"$LOG" 2>&1 <<'PHP'
\App\Models\NormalizationRun::query()
    ->where('status', 'running')
    ->update([
        'status' => \App\Models\NormalizationRun::STATUS_FAILED,
        'error_message' => 'Interrumpido para relanzar con el motor de duplicados optimizado.',
        'finished_at' => now(),
    ]);

\App\Models\SupplierImport::query()
    ->whereIn('pipeline_status', ['processing', 'queued'])
    ->update([
        'pipeline_status' => 'idle',
        'pipeline_stage' => null,
        'pipeline_percent' => 0,
        'pipeline_message' => 'Pendiente de relanzar tras optimización del pipeline.',
        'pipeline_started_at' => null,
        'pipeline_finished_at' => null,
    ]);
PHP

echo "[2/5] Reanudando ADAGIO..." | tee -a "$LOG"
if php artisan tinker --execute='exit(\App\Models\SupplierImport::where("id", 1)->exists() ? 0 : 1);' >/dev/null 2>&1; then
  run_normalization 1
fi

echo "[3/5] Cargando proveedores restantes..." | tee -a "$LOG"
while IFS='|' read -r supplier file; do
  create_and_normalize "$supplier" "$file"
done <<'EOF'
Algam|storage/app/real-imports/ALGAM.csv
Alhambra|storage/app/real-imports/ALHAMBRA.xlsx
Daddario|storage/app/real-imports/DADDARIO.xlsx
Earpro|storage/app/real-imports/EARPRO.csv
Enrique Keller|storage/app/real-imports/ENRIQUE KELLER.csv
Euromusica|storage/app/real-imports/EUROMUSICA.csv
Fender|storage/app/real-imports/FENDER.xlsx
Gewa|storage/app/real-imports/GEWA.xlsx
Honsuy|storage/app/real-imports/HONSUY.xlsx
Knobloch|storage/app/real-imports/knobloch1.xlsx
Knobloch|storage/app/real-imports/knobloch2.xlsx
Knobloch|storage/app/real-imports/knobloch3.xlsx
Ludwig NL|storage/app/real-imports/Ludwig NL.xlsx
Madrid Musical|storage/app/real-imports/MADRIDMUSICAL.csv
Ortola|storage/app/real-imports/ORTOLA.xlsx
Ritmo|storage/app/real-imports/RITMO.xlsx
Samba|storage/app/real-imports/SAMBA.csv
Tico|storage/app/real-imports/TICO.csv
Vallestrade|storage/app/real-imports/VALLESTRADE.csv
Yamaha|storage/app/real-imports/YAMAHA-GUITARS (1).xlsx
Yamaha|storage/app/real-imports/YAMAHA-PM OPEN PRODUCTS.xlsx
Yamaha|storage/app/real-imports/YAMAHA-SYNTHESIZER.xlsx
Zentralmedia|storage/app/real-imports/ZENTRALMEDIA.csv
EOF

echo "[4/5] Cierre global..." | tee -a "$LOG"
php artisan products:generate-tags --chunk=500 >>"$LOG" 2>&1
php artisan categories:suggest-normalized --chunk=500 >>"$LOG" 2>&1
php artisan categories:apply-default-suggestions --min-score=1 >>"$LOG" 2>&1
php artisan catalog:consolidate-masters --only-unlinked --chunk=200 >>"$LOG" 2>&1
php artisan catalog:approve-ready-masters --allow-suggested-category --limit=100000 >>"$LOG" 2>&1
php artisan catalog:revoke-unexportable-masters --limit=100000 >>"$LOG" 2>&1

echo "[5/5] Resumen..." | tee -a "$LOG"
php artisan tinker <<'PHP'
dump([
    'suppliers' => \App\Models\Supplier::count(),
    'imports' => \App\Models\SupplierImport::count(),
    'rows' => \App\Models\SupplierImportRow::count(),
    'normalized' => \App\Models\NormalizedProduct::count(),
    'masters' => \App\Models\MasterProduct::count(),
    'approved_masters' => \App\Models\MasterProduct::where('is_approved', true)->count(),
    'normalization_runs' => \App\Models\NormalizationRun::count(),
    'open_ean_issues' => \App\Models\ProductEanIssue::whereNull('resolved_at')->count(),
    'suggested_categories' => \App\Models\NormalizedProduct::where('category_status', 'suggested')->count(),
]);
PHP

echo "LOG=$LOG"
