<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessLotImportFile;
use App\Services\LotImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Throwable;

class LotImportController extends Controller
{
    protected LotImportService $lotImportService;

    public function __construct(LotImportService $lotImportService)
    {
        $this->lotImportService = $lotImportService;
    }

    public function import(Request $request)
    {
        // Validate the uploaded file
        $request->validate([
            'file' => 'required|file|mimetypes:application/json,text/plain',
        ]);

        // Read the file content
        $filePath = $request->file('file')->store('imports');
        $fileContent = Storage::get($filePath);
        $items = json_decode($fileContent, true);

        if (!is_array($items)) {
            return response()->json(['error' => 'Invalid JSON format'], 400);
        }

        if ($request->boolean('use_queue')) {
            // Queue the batch processing job
            ProcessLotImportFile::dispatch($filePath);

            $count = count($items);
            return response()->json(['message' => "File containing $count items has been queued for processing."]);
        }

        // Direct processing
        try {
            if (count($items) > 100) {
                throw new \Exception("Direct processing limited to 100 items. Try use_queue=true parameter.");
            }

            // Process each lot in the file
            $result = $this->lotImportService->validateAndImport($items);

            $response = [
                'message' => "Import process completed",
                'processed' => count($items),
                'result' => $result,
            ];

            return response()->json($response); // 207 = Multi-Status if errors exist

        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
