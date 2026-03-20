<?php

namespace App\Http\Controllers;

use App\Models\SupplierImport;
use App\Models\NormalizationRun;
use App\Support\BackgroundArtisan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NormalizationController extends Controller
{
    public function runForImport(Request $request, SupplierImport $import): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $import);

        if ($import->status !== 'processed') {
            return $this->pipelineResponse(
                $request,
                $import,
                'Solo se puede ejecutar la normalización avanzada sobre una importación ya procesada.',
                'error',
                422
            );
        }

        if ($import->pipeline_is_running) {
            return $this->pipelineResponse(
                $request,
                $import,
                'La importación ya tiene un proceso en marcha.',
                'error',
                409
            );
        }

        $import->update([
            'pipeline_status' => 'queued',
            'pipeline_stage' => 'normalizing',
            'pipeline_total' => $import->normalizedProducts()->count(),
            'pipeline_processed' => 0,
            'pipeline_percent' => 0,
            'pipeline_message' => 'La normalización avanzada ha entrado en cola.',
            'pipeline_started_at' => null,
            'pipeline_finished_at' => null,
            'error_message' => null,
        ]);

        app(BackgroundArtisan::class)->run(
            ['imports:run-normalization', (string) $import->id],
            storage_path("logs/import-normalize-{$import->id}.log")
        );

        return $this->pipelineResponse(
            $request,
            $import->fresh(),
            'Reprocesado completo y cierre automático encolados. La barra de progreso se actualizará automáticamente.'
        );
    }

    public function showRunStatus(NormalizationRun $run)
    {
        $import = $run->import;
        $this->authorize('view', $import);

        return response()->json([
            'id' => $run->id,
            'status' => $run->status,
            'total_products' => $run->total_products,
            'processed_products' => $run->processed_products,
            'percent_complete' => $run->percent_complete,
            'errors' => $run->errors,
            'started_at' => $run->started_at,
            'finished_at' => $run->finished_at,
            'error_message' => $run->error_message,
        ]);
    }

    private function pipelineResponse(
        Request $request,
        SupplierImport $import,
        string $message,
        string $type = 'success',
        int $statusCode = 200
    ): RedirectResponse|JsonResponse {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => $type === 'success',
                'type' => $type,
                'message' => $message,
                'status_url' => route('imports.status', $import),
                'show_url' => route('imports.show', $import),
                'pipeline_status' => $import->pipeline_status,
                'pipeline_stage' => $import->pipeline_stage,
            ], $statusCode);
        }

        if ($type === 'success') {
            return redirect()->route('imports.show', $import)->with('status', $message);
        }

        return redirect()->route('imports.show', $import)->withErrors(['normalization' => $message]);
    }
}
