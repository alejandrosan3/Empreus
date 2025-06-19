<?php

namespace App\Jobs;

use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Throwable;

class ProcessLotImportFile implements ShouldQueue
{
    use Queueable, Dispatchable;

    protected string $filePath;

    /**
     * Create a new job instance.
     */
    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    /**
     * Execute the job.
     */
    /**
     * @return void
     * @throws \Throwable
     */
    public function handle(): void
    {
        // Add validation to ensure the file exists before attempting to read it
        if (!Storage::exists($this->filePath)) {
            Log::error("File does not exist: {$this->filePath}");
            return;
        }
        $fileContent = Storage::get($this->filePath);
        $items = json_decode($fileContent, true);

        if (!is_array($items)) {
            Log::error("Invalid JSON format in import file: {$this->filePath}");
            return;
        }

        Log::info("Total lots found in file: " . count($items));

        // Chunking the data to dispatch jobs in manageable sizes
        $chunks = array_chunk($items, config('IMPORTER_BATCH_SIZE', 100));

        Bus::batch(
            collect($chunks)->map(fn ($chunk) => new ProcessLotImport($chunk))
        )->before(function (Batch $batch) {
        // The batch has been created but no jobs have been added...
            Log::info("Lot import batch {$batch->id} has started.");
        })->progress(function (Batch $batch) {
            // A single job has completed successfully...
        })->then(function (Batch $batch) {
            Log::info("Lot import batch {$batch->id} completed successfully.");
        })->catch(function (Batch $batch, Throwable $e) {
            // First batch job failure detected...
            Log::error("Lot import batch {$batch->id} encountered errors: " . $e->getMessage());
        })->finally(function (Batch $batch) {
            // The batch has finished executing...
            Log::info("Lot import batch {$batch->id} has finished processing.");
        })->name('Lot Import Batch ' . now()->timestamp)->dispatch();
    }
}
