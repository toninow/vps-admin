<?php

namespace App\Console\Commands;

use App\Services\Import\XlsxFileReader;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class SuppliersInspectFileCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'suppliers:inspect-file {path : Ruta absoluta al archivo XLSX a inspeccionar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inspección detallada de un archivo Excel de proveedor (hojas, cabeceras, filas de ejemplo).';

    public function handle(XlsxFileReader $xlsxReader): int
    {
        $path = $this->argument('path');

        if (! is_file($path)) {
            $this->error("El archivo no existe: {$path}");
            return self::FAILURE;
        }

        $this->info("Inspeccionando archivo: {$path}");

        try {
            $analysis = $xlsxReader->analyzeStructure($path, null);
        } catch (\Throwable $e) {
            $this->error('No se pudo analizar la estructura con XlsxFileReader: ' . $e->getMessage());
            return self::FAILURE;
        }

        $sheetsInfo = $analysis['sheets'] ?? [];
        $selectedSheetIndex = $analysis['selected_sheet_index'] ?? null;
        $selectedSheetName = $analysis['selected_sheet_name'] ?? null;
        $selectedHeaderRow = $analysis['selected_header_row'] ?? null;

        $this->line('');
        $this->line('=== Resumen de hojas ===');
        $this->line('Número de hojas: ' . count($sheetsInfo));

        foreach ($sheetsInfo as $sheet) {
            $this->line('');
            $this->line(sprintf(
                '- Hoja #%d: "%s"',
                $sheet['index'],
                $sheet['name']
            ));
            $this->line('  Filas totales: ' . $sheet['total_rows']);
            $this->line('  Columnas totales: ' . $sheet['total_columns']);
            $this->line('  Filas no vacías (escaneadas): ' . $sheet['non_empty_rows_scanned']);
            $this->line('  Puntuación de hoja (sheet_score): ' . $sheet['sheet_score']);

            $bestHeader = $sheet['best_header_row'] ?? null;
            if ($bestHeader) {
                $this->line(sprintf(
                    '  Mejor fila cabecera candidata: fila %d (score=%s, non_empty=%d, text_cells=%d, keyword_hits=%d, data_rows_below=%d)',
                    $bestHeader['row'],
                    $bestHeader['score'],
                    $bestHeader['non_empty'],
                    $bestHeader['text_cells'],
                    $bestHeader['keyword_hits'],
                    $bestHeader['data_rows_below'],
                ));
                if (! empty($bestHeader['sample_values'])) {
                    $this->line('    Valores de ejemplo: ' . implode(' | ', $bestHeader['sample_values']));
                }
            } else {
                $this->line('  No se encontraron filas candidatas a cabecera en esta hoja.');
            }
        }

        $this->line('');
        $this->line('=== Selección del catálogo principal ===');
        if ($selectedSheetIndex === null) {
            $this->warn('No se pudo determinar una hoja principal.');
        } else {
            $this->info(sprintf(
                'Hoja seleccionada: #%d "%s"',
                $selectedSheetIndex,
                $selectedSheetName
            ));
            $this->info('Fila detectada como cabecera: ' . ($selectedHeaderRow ?? 'desconocida'));
        }

        // Mostrar primeras filas de cada hoja (en bruto)
        $this->line('');
        $this->line('=== Primeras filas de cada hoja (máx. 20) ===');

        try {
            $spreadsheet = IOFactory::load($path);
        } catch (\Throwable $e) {
            $this->error('No se pudo abrir el archivo Excel para mostrar filas: ' . $e->getMessage());
            return self::FAILURE;
        }

        foreach ($spreadsheet->getWorksheetIterator() as $index => $sheet) {
            $this->line('');
            $this->line(str_repeat('-', 80));
            $this->line(sprintf('Hoja #%d: "%s"', $index, $sheet->getTitle()));

            $highestRow = $sheet->getHighestRow();
            $highestColumn = $sheet->getHighestColumn();
            $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

            $rowsToShow = min($highestRow, 20);
            if ($rowsToShow === 0) {
                $this->warn('  Hoja sin filas.');
                continue;
            }

            for ($r = 1; $r <= $rowsToShow; $r++) {
                $cells = [];
                for ($c = 1; $c <= $highestColumnIndex; $c++) {
                    $coord = Coordinate::stringFromColumnIndex($c) . $r;
                    $val = $sheet->getCell($coord)->getValue();
                    $valStr = $val !== null ? trim((string) $val) : '';
                    $cells[] = $valStr;
                }
                $prefix = $r === ($selectedHeaderRow ?? -1) && $index === $selectedSheetIndex ? 'H* ' : sprintf('%3d ', $r);
                $this->line($prefix . implode(' | ', $cells));
            }
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        $this->line('');
        $this->line('Diagnóstico completado.');

        return self::SUCCESS;
    }
}

