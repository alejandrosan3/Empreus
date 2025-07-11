<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TeamWorkerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all teams and workers
        $teams = Team::all();
        $workers = User::where('role', 'worker')->get();

        if ($teams->isEmpty() || $workers->isEmpty()) {
            $this->command->info('No teams or workers found. Make sure to run TeamSeeder and WorkerSeeder first.');
            return;
        }

        // Assign workers to teams
        $teamAssignments = [
            'Plumbing Team' => 3,     // 3 workers
            'Electrical Team' => 4,   // 4 workers
            'Framing Team' => 5,      // 5 workers
            'Roofing Team' => 3,      // 3 workers
            'Flooring Team' => 3,     // 3 workers
            'Finishing Team' => 2,    // 2 workers
        ];

        $workerIndex = 0;

        foreach ($teams as $team) {
            $teamName = $team->name;
            $workerCount = $teamAssignments[$teamName] ?? 3; // Default to 3 if not specified

            for ($i = 0; $i < $workerCount && $workerIndex < $workers->count(); $i++) {
                DB::table('team_worker')->insert([
                    'team_id' => $team->id,
                    'worker_id' => $workers[$workerIndex]->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $workerIndex++;
            }
        }

        $this->command->info("Assigned {$workerIndex} workers to teams.");
    }
}
