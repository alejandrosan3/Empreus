<?php

namespace Tests\Feature\Permissions;

use App\Livewire\Partials\BulkEditModal;
use App\Livewire\Partials\EditableModal;
use App\Livewire\Task\TaskScheduler;
use App\Models\Role;
use App\Models\StatusType;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsPlaces;
use Tests\TestCase;

/**
 * Save buttons, inline edits and bulk actions follow the role, not just the page.
 */
class ActionAccessTest extends TestCase
{
    use RefreshDatabase, BuildsPlaces;

    private array $place;

    private StatusType $confirmed;

    private StatusType $cancelled;

    protected function setUp(): void
    {
        parent::setUp();
        $this->place = $this->makePlace('Acme');
        foreach (['confirmed' => 'Confirmed', 'cancelled' => 'Cancelled'] as $identifier => $name) {
            $this->{$identifier} = tap((new StatusType)->forceFill([
                'identifier' => $identifier, 'name' => $name, 'color' => '#999', 'color_dark' => '#666',
            ]))->save();
        }
        tenancy()->forget();
    }

    private function editField(string $class, int $id, string $attribute, mixed $value)
    {
        return Livewire::test(EditableModal::class)
            ->set('modelClass', $class)
            ->set('modelAttribute', $attribute)
            ->set('fieldId', $id)
            ->set('fieldType', 'input')
            ->set('fieldValue', $value)
            ->call('save');
    }

    private function overseer(): User
    {
        $role = Role::create([
            'organization_id' => $this->place['organization']->id,
            'name' => 'Overseer',
            'levels' => Role::expandLevels(['dashboard' => 'view', 'scheduler' => 'view', 'tasks' => 'view']),
            'details' => ['money' => false, 'contacts' => false, 'notes' => true],
            'reach' => 'all',
        ]);

        return $this->makePerson($this->place['division'], $role, 'Olivia Overseer');
    }

    public function test_view_only_roles_cannot_change_a_task(): void
    {
        $this->workIn($this->overseer(), $this->place['division']);

        $this->editField(Task::class, $this->place['task']->id, 'status_type_id', $this->confirmed->id)->assertForbidden();
        $this->assertNotEquals($this->confirmed->id, $this->place['task']->fresh()->status_type_id);
    }

    public function test_schedulers_can_change_status_but_not_cancel_or_change_prices(): void
    {
        $this->workIn($this->makePerson($this->place['division'], 'scheduler'), $this->place['division']);
        $task = $this->place['task'];

        $this->editField(Task::class, $task->id, 'status_type_id', $this->confirmed->id)->assertHasNoErrors();
        $this->assertEquals($this->confirmed->id, $task->fresh()->status_type_id);

        $this->editField(Task::class, $task->id, 'status_type_id', $this->cancelled->id)->assertForbidden();
        $this->editField(Task::class, $task->id, 'price', 999)->assertForbidden();
        $this->assertNotEquals(999, (float) $task->fresh()->price);
    }

    public function test_admins_can_cancel_and_change_prices(): void
    {
        $this->workIn($this->place['admin'], $this->place['division']);
        $task = $this->place['task'];

        $this->editField(Task::class, $task->id, 'price', 321)->assertHasNoErrors();
        $this->editField(Task::class, $task->id, 'status_type_id', $this->cancelled->id)->assertHasNoErrors();

        $this->assertEquals(321, (float) $task->fresh()->price);
        $this->assertEquals($this->cancelled->id, $task->fresh()->status_type_id);
    }

    public function test_fields_outside_the_allowed_list_cannot_be_changed_even_by_owners(): void
    {
        $this->workIn($this->place['admin'], $this->place['division']);
        $worker = $this->place['worker'];

        $this->editField(User::class, $worker->id, 'email', 'stolen@example.com')->assertForbidden();
        $this->editField(User::class, $worker->id, 'role', 'admin')->assertForbidden();
        $this->assertNotEquals('stolen@example.com', $worker->fresh()->email);
    }

    public function test_another_divisions_task_cannot_be_edited(): void
    {
        $rival = $this->makePlace('Rival');
        tenancy()->forget();
        $this->workIn($this->place['admin'], $this->place['division']);

        try {
            $this->editField(Task::class, $rival['task']->id, 'order_number', 'X-1');
            $this->fail('Editing another division\'s task should not find it.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            // expected: the task is invisible from this division
        }
        $this->assertNotEquals('X-1', Task::everywhere()->find($rival['task']->id)->order_number);
    }

    public function test_bulk_edit_needs_edit_access(): void
    {
        $this->workIn($this->makePerson($this->place['division'], 'viewer'), $this->place['division']);

        Livewire::test(BulkEditModal::class)
            ->set('taskIds', [$this->place['task']->id])
            ->set('status_type_id', $this->confirmed->id)
            ->call('save')
            ->assertForbidden();
    }

    public function test_dragging_a_task_to_cancelled_on_the_scheduler_needs_full_access(): void
    {
        $this->workIn($this->makePerson($this->place['division'], 'scheduler'), $this->place['division']);

        Livewire::test(TaskScheduler::class)
            ->call('taskStatusUpdated', $this->place['task']->id, 'cancelled')
            ->assertForbidden();
    }

    public function test_add_buttons_only_show_for_roles_that_can_add(): void
    {
        $this->workIn($this->overseerWithDirectory(), $this->place['division']);
        $this->get(route('builders'))->assertOk()->assertDontSee(route('builder-create'));

        $this->workIn($this->place['admin'], $this->place['division']);
        $this->get(route('builders'))->assertOk()->assertSee(route('builder-create'));
    }

    private function overseerWithDirectory(): User
    {
        $role = Role::create([
            'organization_id' => $this->place['organization']->id,
            'name' => 'Directory viewer',
            'levels' => Role::expandLevels(['dashboard' => 'view', 'directory' => 'view']),
            'details' => ['money' => false, 'contacts' => false, 'notes' => false],
            'reach' => 'all',
        ]);

        return $this->makePerson($this->place['division'], $role, 'Dee Viewer');
    }
}
