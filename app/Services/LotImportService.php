<?php

namespace App\Services;

use App\Models\Builder;
use App\Models\Company;
use App\Models\Lot;
use App\Models\StatusType;
use App\Models\Supervisor;
use App\Models\Task;
use App\Models\TaskNote;
use App\Models\TaskType;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Support\Facades\Log;

class LotImportService
{
    /**
     * Parse string as date.
     *
     * @param string|null $endedAt
     * @return Carbon|null
     */
    private function parseStringAsDate(?string $endedAt): ?Carbon
    {
        if (empty($endedAt)) {
            return null;
        }

        try {
            return Carbon::parse($endedAt);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Validate and import a batch of items.
     *
     * @param array $items
     * @return array
     */
    public function validateAndImport(array $items): array
    {
        $results = [];

        foreach ($items as $item) {
            try {
                // Validate the item
                $this->validateItem($item);

                // Import the item
                $result = $this->importItem($item);
                $results[] = $result;
            } catch (Exception $e) {
                // Log the error and continue with the next item
                Log::error('Error importing item', [
                    'error' => $e->getMessage(),
                    'item' => $item,
                ]);
                $results[] = ['error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Validate a single item.
     *
     * @param array $item
     * @throws Exception
     */
    protected function validateItem(array $item): void
    {
        $validator = Validator::make($item, [
            'company_id' => 'required|exists:companies,id',
            'builder' => 'required|string|max:255',
            'lot_number' => 'required|string|max:255',
            'task_name' => 'required|string|exists:task_types,name',
            'supervisor_name' => 'nullable|string|max:255',
            'subdivision' => 'nullable|string|max:255',
            'status' => 'required|string|exists:status_types,identifier',
            'floor_area' => 'nullable|numeric|min:0',
            'num_of_floors' => 'nullable|integer|min:1',
            'notes' => 'nullable|string',
            'started_at' => 'nullable|string',
            'ended_at' => 'nullable|string',
            'address' => 'nullable|string',
            'phase' => 'nullable|string|max:255',
            'is_extra' => 'required|boolean',
            'price' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            throw new Exception(json_encode($validator->errors()));
        }
    }

    /**
     * Import a single item.
     *
     * @param array $item
     * @return array
     */
    protected function importItem(array $item): array
    {
        /**
         * Find 'pending' status
         */
        if (!$pendingStatus = StatusType::where('identifier', 'pending')->first()) {
            throw new Exception('Pending StatusType not found');
        }

        /**
         * Find Company
         */
        if (!$company = Company::where(['id' => $item['company_id']])->first()) {
            throw new Exception("Company {$item['company_id']} not found");
        }

        /**
         * Find Task Status
         */
        if (!$taskStatus = StatusType::where(['identifier' => $item['status']])->first()) {
            throw new Exception("Task Status '{$item['status']}' not found.");
        }

        /**
         * Find Task Type
         */
        if (!$taskType = TaskType::where('name', $item['task_name'])->first()) {
            throw new Exception("Task Type '{$item['task_name']}' not found.");
        }

        /**
         * Find or create Builder
         */
        $builder = Builder::firstOrCreate(
            ['name' => $item['builder']]
        );

        /**
         * Find or create Supervisor
         */
        $supervisorName = !empty($item['supervisor_name']) ? $item['supervisor_name'] : 'N/A';
        $supervisor = Supervisor::firstOrCreate(
            ['name' => $supervisorName]
        );

        /**
         * Process Lot
         */
        $lotData = [
            'company_id' => $company->id,
            'builder_id' => $builder->id,
            'lot_number' => $item['lot_number'],
        ];

        $lot = Lot::updateOrCreate(
            $lotData,
            [
                'status_type_id' => $pendingStatus->id,
                'supervisor_id' => $supervisor->id,
                'phase' => $item['phase'],
                'address' => $item['address'],
                'subdivision' => $item['subdivision'] ?? null,
                'floor_area' => !empty($item['floor_area']) ? $item['floor_area'] : 0,
                'num_of_floors' => $item['num_of_floors'] ?? 1,
            ]
        );

        /**
         * Process Lot Task
         */
        $lotTaskFindParams = [
            'company_id' => $company->id,
            'lot_id' => $lot->id,
            'task_type_id' => $taskType->id,
        ];

        $endedAt = $this->parseStringAsDate($item['ended_at']);
        $startedAt = $this->parseStringAsDate($item['started_at']);
        $lotTask = Task::updateOrCreate($lotTaskFindParams,
            [
                'status_type_id' => $taskStatus->id,
                'is_extra' => $item['is_extra'],
                'price' => empty($item['price']) ? 0 : $item['price'],
                'scheduled_start_date' => $startedAt ? $startedAt->startOfDay()->toDateTimeString() : null,
                'scheduled_end_date' => $endedAt ? $endedAt->endOfDay()->toDateTimeString() : null,
                'started_at' => $startedAt ? $startedAt->startOfDay()->toDateTimeString() : null,
                'ended_at' => $endedAt ? $endedAt->endOfDay()->toDateTimeString() : null,
            ]);

        /**
         * Process Task Notes
         * Find latest note and if description is different from the parameters then as add new note
         */
        $taskNoteFindParams = ['task_id' => $lotTask->id];
        $taskNote = TaskNote::where($taskNoteFindParams)->orderBy('id', 'desc')->first();
        $newTaskNote = null;

        if (!$taskNote || (strtolower($taskNote->description) !== strtolower(trim($item['notes'])))) {
            $newTaskNote = TaskNote::create([
                ...$taskNoteFindParams,
                'description' => $item['notes'],
            ]);
        }

        return [
            'success' => true,
            'is_new_lot' => $lot->wasRecentlyCreated,
            'is_new_task' => $lotTask->wasRecentlyCreated,
            'task_notes_added' => !empty($newTaskNote),
        ];

    }
}
