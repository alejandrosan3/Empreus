<?php

namespace App\Jobs;

use App\Services\LotImportService;
use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessLotImport implements ShouldQueue
{
    use Queueable, Batchable;

    public array $data;

    /**
     * Create a new job instance.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     * @throws Exception
     */
    public function handle(LotImportService $lotImportService): void
    {
        if ($this->batch()->cancelled()) {
            Log::error('Batch cancelled, job will not proceed.');
            return;
        }

        try {
            $lotImportService->validateAndImport($this->data);
            Log::error('Lot import Job successful.', [
                'count' => count($this->data),
                'batch_id' => $this->batch()->id
            ]);

        } catch (Exception $e) {
            Log::error('Error during lot import.', [
                'error' => $e->getMessage(),
                'batch_id' => $this->batch()->id
            ]);

            throw $e;
        }
    }
}
