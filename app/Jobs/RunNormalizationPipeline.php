<?php

namespace App\Jobs;

use App\Models\SupplierImport;
use App\Services\Normalization\NormalizationPipelineService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunNormalizationPipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $importId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $importId)
    {
        $this->importId = $importId;
    }

    /**
     * Execute the job.
     */
    public function handle(NormalizationPipelineService $pipeline): void
    {
        $import = SupplierImport::find($this->importId);

        if (! $import || $import->status !== 'processed') {
            return;
        }

        $pipeline->runForImport($import);
    }
}

