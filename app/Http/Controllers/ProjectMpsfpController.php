<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\DuplicateProductGroup;
use App\Models\MasterProduct;
use App\Models\NormalizedProduct;
use App\Models\ProductEanIssue;
use App\Models\Project;
use App\Models\Supplier;
use App\Models\SupplierFieldMapping;
use App\Models\SupplierImport;
use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProjectMpsfpController extends Controller
{
    public function dashboardBySlug(): RedirectResponse
    {
        $project = Project::where('slug', 'mpsfp')->firstOrFail();

        return redirect()->route('projects.mpsfp.dashboard', $project);
    }

    public function dashboard(Project $project): View
    {
        $this->ensureMpsfpProject($project);

        $context = $this->buildContext();
        $access = Auth::user()->mpsfpCapabilities();
        $sections = $this->availableSections($project, $access);

        return view('projects.mpsfp.dashboard', compact('project', 'context', 'sections', 'access'));
    }

    public function section(Project $project, string $section): View
    {
        $this->ensureMpsfpProject($project);

        $access = Auth::user()->mpsfpCapabilities();
        $sections = $this->availableSections($project, $access);
        abort_unless(isset($sections[$section]), 404);

        $sectionMeta = $sections[$section];
        if (! ($sectionMeta['enabled'] ?? false)) {
            abort(403);
        }

        $context = $this->buildContext();
        $sectionData = $this->buildSectionData($project, $section, $sectionMeta);

        return view('projects.mpsfp.section', compact('project', 'context', 'sections', 'section', 'sectionMeta', 'sectionData', 'access'));
    }

    protected function ensureMpsfpProject(Project $project): void
    {
        $this->authorize('view', $project);

        if ($project->slug !== 'mpsfp') {
            abort(404);
        }
    }

    protected function buildContext(): array
    {
        return [
            'activeSuppliers' => Supplier::where('is_active', true)->count(),
            'recentSuppliers' => Supplier::query()
                ->latest()
                ->take(6)
                ->get(),
            'processedImports' => SupplierImport::where('status', 'processed')->count(),
            'importsToday' => SupplierImport::whereDate('created_at', today())->count(),
            'totalImports' => SupplierImport::count(),
            'pendingImports' => SupplierImport::whereIn('status', ['uploaded', 'previewed', 'mapped'])->count(),
            'draftImports' => SupplierImport::whereIn('status', ['uploaded', 'previewed'])->count(),
            'activeMappings' => SupplierFieldMapping::where('is_active', true)->count(),
            'normalizedProducts' => NormalizedProduct::count(),
            'unlinkedNormalizedProducts' => NormalizedProduct::whereNull('master_product_id')->count(),
            'suggestedCategoriesCount' => NormalizedProduct::where('category_status', 'suggested')->count(),
            'masterProducts' => MasterProduct::count(),
            'approvedMasterProducts' => MasterProduct::where('is_approved', true)->count(),
            'pendingMasterProducts' => MasterProduct::where('is_approved', false)->count(),
            'categories' => Category::count(),
            'productsWithoutImage' => NormalizedProduct::query()
                ->where(function ($query) {
                    $query->whereNull('image_urls')
                        ->orWhereJsonLength('image_urls', 0);
                })
                ->count(),
            'openEanIssues' => ProductEanIssue::whereNull('resolved_at')->count(),
            'resolvedEanIssues' => ProductEanIssue::whereNotNull('resolved_at')->count(),
            'duplicateGroups' => DuplicateProductGroup::count(),
            'pendingDuplicateGroups' => DuplicateProductGroup::where('status', 'pending')->count(),
            'crossSupplierGroups' => DB::table('normalized_products')
                ->selectRaw('ean13')
                ->whereNotNull('ean13')
                ->where('ean13', '<>', '')
                ->groupBy('ean13')
                ->havingRaw('COUNT(DISTINCT supplier_id) > 1')
                ->get()
                ->count(),
            'settingsCount' => Setting::count(),
            'recentOpenEanIssues' => ProductEanIssue::with(['normalizedProduct', 'masterProduct'])
                ->whereNull('resolved_at')
                ->latest()
                ->take(5)
                ->get(),
            'recentPendingDuplicateGroups' => DuplicateProductGroup::query()
                ->where('status', 'pending')
                ->latest()
                ->take(5)
                ->get(),
            'recentImports' => SupplierImport::with('supplier')
                ->latest()
                ->take(6)
                ->get(),
            'recentProcessedImports' => SupplierImport::with('supplier')
                ->where('status', 'processed')
                ->latest()
                ->take(8)
                ->get(),
            'recentUserLogs' => ActivityLog::with('user')
                ->where('user_id', Auth::id())
                ->latest()
                ->take(6)
                ->get(),
        ];
    }

    protected function availableSections(Project $project, ?array $access = null): array
    {
        $capabilities = $access['sections'] ?? Auth::user()->mpsfpCapabilities()['sections'];
        $sections = [
            'proveedores' => [
                'label' => 'Proveedores',
                'description' => 'Alta, revisión y mapeo de proveedores activos del sistema.',
                'permission' => 'suppliers.view',
                'url' => route('projects.mpsfp.suppliers.index', $project),
                'primary_url' => route('projects.mpsfp.suppliers.index', $project),
                'primary_label' => 'Abrir proveedores',
                'secondary_actions' => [
                    ['label' => 'Ir al dashboard MPSFP', 'url' => route('projects.mpsfp.dashboard', $project)],
                ],
            ],
            'importaciones' => [
                'label' => 'Importaciones',
                'description' => 'Subida de archivos, preview, mapeo y procesamiento de catálogos.',
                'permission' => 'imports.view',
                'url' => route('projects.mpsfp.imports.index', $project),
                'primary_url' => route('projects.mpsfp.imports.index', $project),
                'primary_label' => 'Abrir importaciones',
                'secondary_actions' => [
                    ['label' => 'Nueva importación', 'url' => route('projects.mpsfp.imports.create', $project)],
                ],
            ],
            'normalizados' => [
                'label' => 'Normalizados',
                'description' => 'Productos procesados desde proveedores antes de consolidarlos en catálogo maestro.',
                'permission' => 'products.view',
                'url' => route('projects.mpsfp.normalized.index', $project),
                'primary_url' => route('projects.mpsfp.normalized.index', $project),
                'primary_label' => 'Abrir normalizados',
                'secondary_actions' => [],
            ],
            'maestros' => [
                'label' => 'Maestros',
                'description' => 'Catálogo maestro operativo, aprobación de productos y base de exportación.',
                'permission' => 'master_products.view',
                'url' => route('projects.mpsfp.master.index', $project),
                'primary_url' => route('projects.mpsfp.master.index', $project),
                'primary_label' => 'Abrir maestros',
                'secondary_actions' => [],
            ],
            'ean' => [
                'label' => 'Incidencias EAN',
                'description' => 'Seguimiento de EAN vacíos, inválidos y pendientes de corrección.',
                'permission' => 'ean.view',
                'primary_url' => route('ean-issues.index'),
                'primary_label' => 'Abrir incidencias EAN',
                'secondary_actions' => [],
            ],
            'duplicados' => [
                'label' => 'Duplicados',
                'description' => 'Agrupación y revisión de productos con el mismo EAN o conflicto de identidad.',
                'permission' => 'duplicates.view',
                'primary_url' => route('duplicates.index'),
                'primary_label' => 'Abrir duplicados',
                'secondary_actions' => [],
            ],
            'cruce_proveedores' => [
                'label' => 'Cruce proveedores',
                'description' => 'Mismo producto detectado en varios proveedores por EAN compartido.',
                'permission' => 'duplicates.view',
                'url' => route('projects.mpsfp.cross-suppliers.index', $project),
                'primary_url' => route('projects.mpsfp.cross-suppliers.index', $project),
                'primary_label' => 'Abrir cruce proveedores',
                'secondary_actions' => [],
            ],
            'categorias' => [
                'label' => 'Categorías',
                'description' => 'Árbol maestro y estructura final de clasificación para exportación.',
                'permission' => 'categories.view',
                'url' => route('projects.mpsfp.categories.review', $project),
                'primary_url' => route('projects.mpsfp.categories.review', $project),
                'primary_label' => 'Revisar sugerencias',
                'secondary_actions' => [
                    ['label' => 'Árbol maestro', 'url' => route('categories.index')],
                ],
            ],
            'exportacion' => [
                'label' => 'Exportación',
                'description' => 'Preparación del catálogo maestro para exportación final a PrestaShop.',
                'permission' => 'export.view',
                'url' => route('projects.mpsfp.export.index', $project),
                'primary_url' => route('projects.mpsfp.export.index', $project),
                'primary_label' => 'Abrir exportación',
                'secondary_actions' => [
                    ['label' => 'Ver maestros aprobados', 'url' => route('projects.mpsfp.master.index', [$project, 'is_approved' => 1])],
                ],
            ],
        ];

        foreach ($sections as $key => &$meta) {
            $capability = $capabilities[$key] ?? null;
            $meta['enabled'] = $capability['view'] ?? false;
            $meta['mode'] = $capability['mode'] ?? 'blocked';
            $meta['mode_label'] = $capability['mode_label'] ?? 'Sin acceso';
            $meta['reason'] = $capability['reason'] ?? 'No disponible para tu rol actual.';
        }

        return $sections;
    }

    protected function buildSectionData(Project $project, string $section, array $sectionMeta): array
    {
        $data = [
            'summary' => [],
            'table' => null,
            'primary_url' => $sectionMeta['primary_url'],
            'primary_label' => $sectionMeta['primary_label'],
            'secondary_actions' => $sectionMeta['secondary_actions'] ?? [],
        ];

        switch ($section) {
            case 'proveedores':
                $suppliers = Supplier::orderByDesc('is_active')->orderBy('name')->take(10)->get();
                $data['summary'] = [
                    ['label' => 'Activos', 'value' => Supplier::where('is_active', true)->count()],
                    ['label' => 'Total', 'value' => Supplier::count()],
                    ['label' => 'Mappings activos', 'value' => SupplierFieldMapping::where('is_active', true)->count()],
                ];
                $data['table'] = [
                    'columns' => ['Nombre', 'Slug', 'Estado', 'Acción'],
                    'rows' => $suppliers->map(fn (Supplier $supplier) => [
                        $supplier->name,
                        $supplier->slug,
                        $supplier->is_active ? 'Activo' : 'Inactivo',
                        ['label' => 'Ver', 'url' => route('projects.mpsfp.suppliers.show', [$project, $supplier])],
                    ])->all(),
                    'empty' => 'No hay proveedores registrados.',
                ];
                break;

            case 'importaciones':
                $imports = SupplierImport::with('supplier')->latest()->take(10)->get();
                $data['summary'] = [
                    ['label' => 'Procesadas', 'value' => SupplierImport::where('status', 'processed')->count()],
                    ['label' => 'Pendientes', 'value' => SupplierImport::where('status', '!=', 'processed')->count()],
                    ['label' => 'Total', 'value' => SupplierImport::count()],
                ];
                $data['table'] = [
                    'columns' => ['Archivo', 'Proveedor', 'Estado', 'Acción'],
                    'rows' => $imports->map(fn (SupplierImport $import) => [
                        $import->filename_original,
                        $import->supplier->name ?? '—',
                        $import->status,
                        ['label' => 'Ver', 'url' => route('projects.mpsfp.imports.show', [$project, $import])],
                    ])->all(),
                    'empty' => 'No hay importaciones registradas.',
                ];
                break;

            case 'normalizados':
                $products = NormalizedProduct::with(['supplier', 'masterProduct'])->latest()->take(10)->get();
                $data['summary'] = [
                    ['label' => 'Total', 'value' => NormalizedProduct::count()],
                    ['label' => 'Con maestro', 'value' => NormalizedProduct::whereNotNull('master_product_id')->count()],
                    ['label' => 'Pendientes', 'value' => NormalizedProduct::whereNull('master_product_id')->count()],
                ];
                $data['table'] = [
                    'columns' => ['Nombre', 'Proveedor', 'EAN', 'Acción'],
                    'rows' => $products->map(fn (NormalizedProduct $product) => [
                        $product->name,
                        $product->supplier->name ?? '—',
                        $product->ean13 ?? '—',
                        ['label' => 'Ver', 'url' => route('projects.mpsfp.normalized.show', [$project, $product])],
                    ])->all(),
                    'empty' => 'No hay productos normalizados.',
                ];
                break;

            case 'maestros':
                $products = MasterProduct::with('category')->latest()->take(10)->get();
                $data['summary'] = [
                    ['label' => 'Total', 'value' => MasterProduct::count()],
                    ['label' => 'Aprobados', 'value' => MasterProduct::where('is_approved', true)->count()],
                    ['label' => 'Pendientes', 'value' => MasterProduct::where('is_approved', false)->count()],
                ];
                $data['table'] = [
                    'columns' => ['Nombre', 'Categoría', 'Aprobado', 'Acción'],
                    'rows' => $products->map(fn (MasterProduct $product) => [
                        $product->name,
                        $product->category->name ?? '—',
                        $product->is_approved ? 'Sí' : 'No',
                        ['label' => 'Ver', 'url' => route('projects.mpsfp.master.show', [$project, $product])],
                    ])->all(),
                    'empty' => 'No hay productos maestros.',
                ];
                break;

            case 'ean':
                $issues = ProductEanIssue::with(['normalizedProduct', 'masterProduct'])->latest()->take(10)->get();
                $data['summary'] = [
                    ['label' => 'Abiertas', 'value' => ProductEanIssue::whereNull('resolved_at')->count()],
                    ['label' => 'Resueltas', 'value' => ProductEanIssue::whereNotNull('resolved_at')->count()],
                    ['label' => 'Total', 'value' => ProductEanIssue::count()],
                ];
                $data['table'] = [
                    'columns' => ['Tipo', 'Valor recibido', 'Estado', 'Acción'],
                    'rows' => $issues->map(fn (ProductEanIssue $issue) => [
                        $issue->issue_type,
                        $issue->value_received ?? '—',
                        $issue->resolved_at ? 'Resuelta' : 'Pendiente',
                        ['label' => 'Ver', 'url' => route('ean-issues.show', $issue)],
                    ])->all(),
                    'empty' => 'No hay incidencias EAN registradas.',
                ];
                break;

            case 'duplicados':
                $groups = DuplicateProductGroup::with('duplicateProductGroupItems')->latest()->take(10)->get();
                $data['summary'] = [
                    ['label' => 'Grupos', 'value' => DuplicateProductGroup::count()],
                    ['label' => 'Pendientes', 'value' => DuplicateProductGroup::where('status', 'pending')->count()],
                    ['label' => 'Con maestro', 'value' => DuplicateProductGroup::whereNotNull('master_product_id')->count()],
                ];
                $data['table'] = [
                    'columns' => ['EAN', 'Estado', 'Ítems', 'Acción'],
                    'rows' => $groups->map(fn (DuplicateProductGroup $group) => [
                        $group->ean13,
                        $group->status ?? 'pending',
                        (string) $group->duplicateProductGroupItems->count(),
                        ['label' => 'Ver', 'url' => route('duplicates.show', $group)],
                    ])->all(),
                    'empty' => 'No hay grupos de duplicados.',
                ];
                break;

            case 'cruce_proveedores':
                $groupRows = DB::table('normalized_products as np')
                    ->join('suppliers as s', 's.id', '=', 'np.supplier_id')
                    ->selectRaw('np.ean13, COUNT(*) as rows_count, COUNT(DISTINCT np.supplier_id) as suppliers_count, GROUP_CONCAT(DISTINCT s.name ORDER BY s.name SEPARATOR ", ") as supplier_names')
                    ->whereNotNull('np.ean13')
                    ->where('np.ean13', '<>', '')
                    ->groupBy('np.ean13')
                    ->havingRaw('COUNT(DISTINCT np.supplier_id) > 1')
                    ->orderByDesc('suppliers_count')
                    ->orderByDesc('rows_count')
                    ->limit(10)
                    ->get();

                $data['summary'] = [
                    ['label' => 'Grupos EAN', 'value' => DB::table('normalized_products')->selectRaw('ean13')->whereNotNull('ean13')->where('ean13', '<>', '')->groupBy('ean13')->havingRaw('COUNT(DISTINCT supplier_id) > 1')->get()->count()],
                    ['label' => 'Máx. proveedores', 'value' => (int) ($groupRows->max('suppliers_count') ?? 0)],
                    ['label' => 'Muestras', 'value' => $groupRows->count()],
                ];
                $data['table'] = [
                    'columns' => ['EAN', 'Proveedores', 'Filas', 'Proveedor(es)'],
                    'rows' => $groupRows->map(fn ($row) => [
                        $row->ean13,
                        (string) $row->suppliers_count,
                        (string) $row->rows_count,
                        $row->supplier_names,
                    ])->all(),
                    'empty' => 'No hay grupos de producto compartido entre proveedores.',
                ];
                break;

            case 'categorias':
                $categories = Category::with('parent')->orderByDesc('updated_at')->take(10)->get();
                $data['summary'] = [
                    ['label' => 'Total', 'value' => Category::count()],
                    ['label' => 'Activas', 'value' => Category::where('is_active', true)->count()],
                    ['label' => 'Raíz', 'value' => Category::whereNull('parent_id')->count()],
                ];
                $data['table'] = [
                    'columns' => ['Nombre', 'Padre', 'Estado', 'Acción'],
                    'rows' => $categories->map(fn (Category $category) => [
                        $category->name,
                        $category->parent->name ?? '—',
                        $category->is_active ? 'Activa' : 'Inactiva',
                        ['label' => 'Ver', 'url' => route('categories.show', $category)],
                    ])->all(),
                    'empty' => 'No hay categorías registradas.',
                ];
                break;

            case 'exportacion':
                $products = MasterProduct::where('is_approved', true)->latest()->take(10)->get();
                $data['summary'] = [
                    ['label' => 'Aprobados', 'value' => MasterProduct::where('is_approved', true)->count()],
                    ['label' => 'Pendientes', 'value' => MasterProduct::where('is_approved', false)->count()],
                    ['label' => 'Configuraciones', 'value' => Setting::count()],
                ];
                $data['table'] = [
                    'columns' => ['Producto', 'EAN', 'Stock', 'Acción'],
                    'rows' => $products->map(fn (MasterProduct $product) => [
                        $product->name,
                        $product->ean13 ?? '—',
                        (string) $product->quantity,
                        ['label' => 'Ver', 'url' => route('projects.mpsfp.master.show', [$project, $product])],
                    ])->all(),
                    'empty' => 'No hay productos maestros aprobados todavía.',
                ];
                break;
        }

        return $data;
    }
}
