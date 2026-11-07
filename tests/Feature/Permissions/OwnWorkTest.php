<?php

namespace Tests\Feature\Permissions;

use App\Models\Lot;
use App\Models\Role;
use App\Models\Task;
use App\Models\Team;
use App\Services\RoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsPlaces;
use Tests\TestCase;

/**
 * Roles set to "Only their own work" see only their tasks and lots.
 */
class OwnWorkTest extends TestCase
{
    use RefreshDatabase, BuildsPlaces;

    private array $place;

    private Task $otherTask;

    private Lot $otherLot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->place = $this->makePlace('A');

        // A second lot and task nobody in particular is assigned to.
        tenancy()->runIn($this->place['division'], function () {
            $this->otherLot = Lot::factory()->create([
                'company_id' => $this->place['division']->id,
                'builder_id' => $this->place['builder']->id,
                'supervisor_id' => null,
                'status_type_id' => $this->pendingStatus()->id,
                'subdivision' => 'Other Subdivision',
            ]);
            $this->otherTask = Task::factory()->create([
                'company_id' => $this->place['division']->id,
                'lot_id' => $this->otherLot->id,
                'task_type_id' => $this->place['taskType']->id,
                'status_type_id' => $this->pendingStatus()->id,
            ]);
        });
        tenancy()->forget();
    }

    private function visibleTaskIds(): array
    {
        return Task::pluck('id')->sort()->values()->all();
    }

    public function test_workers_see_tasks_assigned_to_them(): void
    {
        $worker = $this->place['worker']; // Worker role: own work only
        $this->place['task']->workers()->attach($worker->id);
        $this->workIn($worker, $this->place['division']);

        $this->assertSame([$this->place['task']->id], $this->visibleTaskIds());
        $this->assertSame([$this->place['lot']->id], Lot::pluck('id')->all());

        $this->get(route('task-details', $this->otherTask->id))->assertNotFound();
        $this->get(route('task-details', $this->place['task']->id))->assertOk();
    }

    public function test_workers_see_their_teams_tasks(): void
    {
        $worker = $this->place['worker'];
        $team = $this->place['team'];
        $team->workers()->attach($worker->id);
        $this->otherTask->forceFill(['team_id' => $team->id])->save();
        $this->workIn($worker, $this->place['division']);

        $this->assertSame([$this->otherTask->id], $this->visibleTaskIds());
    }

    public function test_importer_assignments_count_too(): void
    {
        $worker = $this->place['worker'];
        DB::table('task_worker')->insert(['task_id' => $this->otherTask->id, 'worker_id' => $worker->id, 'created_at' => now(), 'updated_at' => now()]);
        $this->workIn($worker, $this->place['division']);

        $this->assertSame([$this->otherTask->id], $this->visibleTaskIds());
    }

    public function test_supervisors_see_the_lots_they_supervise(): void
    {
        $person = $this->makePerson($this->place['division'], 'supervisor', 'Sue');
        $this->place['supervisor']->update(['user_id' => $person->id]);
        $this->workIn($person, $this->place['division']);

        $this->assertSame([$this->place['lot']->id], Lot::pluck('id')->all());
        $this->assertSame([$this->place['task']->id], $this->visibleTaskIds());
        $this->get(route('lot-details', $this->otherLot->id))->assertNotFound();
    }

    public function test_nothing_assigned_means_nothing_shown(): void
    {
        $this->workIn($this->place['worker'], $this->place['division']);

        $this->assertSame([], $this->visibleTaskIds());
        $this->assertSame([], Lot::pluck('id')->all());
    }

    public function test_everyone_else_sees_everything(): void
    {
        $this->workIn($this->place['admin'], $this->place['division']);
        $this->assertCount(2, $this->visibleTaskIds());

        $scheduler = $this->makePerson($this->place['division'], 'scheduler', 'Sam');
        $this->workIn($scheduler, $this->place['division']);
        $this->assertCount(2, $this->visibleTaskIds());
    }

    public function test_a_wider_role_in_the_same_place_wins(): void
    {
        $worker = $this->place['worker'];
        $viewer = $this->place['organization']->roles()->where('key', 'viewer')->first();
        app(\App\Services\PeopleService::class)->grant($worker, $viewer, 'division', $this->place['division']->id);
        $this->workIn($worker, $this->place['division']);

        $this->assertCount(2, $this->visibleTaskIds());
    }

    public function test_view_as_an_own_work_role_limits_an_owner(): void
    {
        $owner = $this->place['admin'];
        $this->place['task']->workers()->attach($owner->id);
        $workerRole = $this->place['organization']->roles()->where('key', 'worker')->first();
        $this->workIn($owner, $this->place['division']);

        $this->withSession(['view_as_role_id' => $workerRole->id]);
        $this->get(route('task-details', $this->place['task']->id))->assertOk();
        $this->get(route('task-details', $this->otherTask->id))->assertNotFound();
    }

    public function test_list_pages_still_load_with_the_filter_on(): void
    {
        $person = $this->makePerson($this->place['division'], 'supervisor', 'Sue');
        $this->place['supervisor']->update(['user_id' => $person->id]);
        $this->workIn($person, $this->place['division']);

        foreach (['task-progress', 'task-board', 'task-calendar', 'task-scheduler', 'lot-progress', 'dashboard'] as $page) {
            $response = $this->get(route($page));
            $this->assertContains($response->status(), [200, 302], "$page failed with {$response->status()}");
        }
    }
}
