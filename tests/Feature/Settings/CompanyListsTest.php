<?php

namespace Tests\Feature\Settings;

use App\Livewire\Settings\Statuses;
use App\Livewire\Settings\Tags;
use App\Livewire\Settings\TaskTypes;
use App\Livewire\Task\AddTaskForm;
use App\Models\Business;
use App\Models\Company;
use App\Models\StatusType;
use App\Models\Tag;
use App\Models\TaskType;
use App\Models\TaskTypePrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsPlaces;
use Tests\TestCase;

class CompanyListsTest extends TestCase
{
    use RefreshDatabase, BuildsPlaces;

    public function test_add_a_task_type_with_a_default_and_a_builder_price(): void
    {
        $place = $this->makePlace('A');
        $this->workIn($place['admin'], $place['division']);

        Livewire::test(TaskTypes::class)
            ->set('newName', 'Final clean')->set('newPrice', '150')
            ->call('add')->assertHasNoErrors();

        $type = TaskType::where('name', 'Final clean')->firstOrFail();
        $this->assertSame($place['business']->id, $type->business_id);
        $this->assertSame('150.00', $type->default_price);

        Livewire::test(TaskTypes::class)
            ->call('edit', $type->id)
            ->set('priceBuilderId', $place['builder']->id)->set('builderPrice', '175')
            ->call('setBuilderPrice')->assertHasNoErrors();

        $this->assertSame('175.00', $type->priceFor($place['builder']->id));
        $this->assertSame('150.00', $type->priceFor(null));
    }

    public function test_new_tasks_suggest_the_builder_price(): void
    {
        $place = $this->makePlace('A');
        $this->workIn($place['admin'], $place['division']);
        $type = $place['taskType'];
        $type->update(['default_price' => 90]);
        TaskTypePrice::create(['task_type_id' => $type->id, 'builder_id' => $place['builder']->id, 'price' => 120]);

        Livewire::test(AddTaskForm::class)
            ->set('task_type_id', $type->id)
            ->assertSet('price', '90.00')
            ->set('price', null)
            ->set('builder_id', $place['builder']->id)
            ->assertSet('price', '120.00');
    }

    public function test_archived_task_types_leave_pickers_but_stay_on_old_tasks(): void
    {
        $place = $this->makePlace('A');
        $this->workIn($place['admin'], $place['division']);

        Livewire::test(TaskTypes::class)->call('archive', $place['taskType']->id);

        $this->assertFalse(TaskType::whereKey($place['taskType']->id)->exists());
        $this->assertSame('A Task Type', $place['task']->fresh()->taskType->name);

        Livewire::test(TaskTypes::class)->set('showArchived', true)->assertSee('A Task Type')
            ->call('restore', $place['taskType']->id);
        $this->assertTrue(TaskType::whereKey($place['taskType']->id)->exists());
    }

    public function test_task_types_belong_to_one_business(): void
    {
        $place = $this->makePlace('A');
        $painting = Business::factory()->for($place['organization'])->create(['name' => 'Painting']);
        $orlando = Company::factory()->for($painting)->create(['name' => 'Orlando']);
        $this->workIn($place['admin'], $orlando);

        $this->get(route('settings.task-types'))->assertOk()->assertDontSee('A Task Type');

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        Livewire::test(TaskTypes::class)->call('archive', $place['taskType']->id);
    }

    public function test_prices_are_hidden_from_roles_without_money(): void
    {
        $place = $this->makePlace('A');
        $place['taskType']->update(['default_price' => 4321]);
        $role = \App\Models\Role::create([
            'organization_id' => $place['organization']->id,
            'name' => 'List keeper',
            'levels' => \App\Models\Role::expandLevels(['settings' => 'edit']),
            'details' => ['money' => false],
        ]);
        $keeper = $this->makePerson($place['business'], $role, 'Keeper');
        $this->workIn($keeper, $place['division']);

        $this->get(route('settings.task-types'))->assertOk()->assertDontSee('4,321');

        Livewire::test(TaskTypes::class)
            ->call('edit', $place['taskType']->id)
            ->set('name', 'Renamed')->set('defaultPrice', '1')
            ->call('save')->assertHasNoErrors();
        $this->assertSame('4321.00', $place['taskType']->fresh()->default_price);
        $this->assertSame('Renamed', $place['taskType']->fresh()->name);
    }

    public function test_division_admins_can_look_at_lists_but_not_change_them(): void
    {
        $place = $this->makePlace('A');
        $local = $this->makePerson($place['division'], 'admin', 'Local admin');
        $this->workIn($local, $place['division']);

        $this->get(route('settings.tags'))->assertOk()->assertSee('A Tag');
        Livewire::test(Tags::class)->set('newName', 'Rush')->call('add')->assertForbidden();
    }

    public function test_rename_and_recolor_statuses_per_business(): void
    {
        $place = $this->makePlace('A');
        $painting = Business::factory()->for($place['organization'])->create(['name' => 'Painting']);
        $orlando = Company::factory()->for($painting)->create(['name' => 'Orlando']);
        $pending = $this->pendingStatus();
        $this->workIn($place['admin'], $place['division']);

        Livewire::test(Statuses::class)
            ->set('statuses.pending.name', 'Waiting')
            ->set('statuses.pending.color', 'orange')
            ->call('save')->assertHasNoErrors();

        tenancy()->setDivision($place['division']->fresh());
        $this->assertSame('Waiting', StatusType::find($pending->id)->name);
        $this->assertSame('orange', StatusType::find($pending->id)->color);
        $this->assertSame([$pending->id => 'Waiting'], StatusType::all()->pluck('name', 'id')->all());

        // Painting still sees the standard name.
        tenancy()->setDivision($orlando->fresh());
        $this->assertSame('Pending', StatusType::find($pending->id)->name);

        $this->workIn($place['admin'], $place['division']->fresh());
        Livewire::test(Statuses::class)->call('resetToDefaults');
        tenancy()->setDivision($place['division']->fresh());
        $this->assertSame('Pending', StatusType::find($pending->id)->name);
    }

    public function test_bad_status_colors_are_refused(): void
    {
        $place = $this->makePlace('A');
        $this->pendingStatus();
        $this->workIn($place['admin'], $place['division']);

        Livewire::test(Statuses::class)->set('statuses.pending.color', 'url(javascript:x)')->call('save')
            ->assertHasErrors('statuses.pending.color');
    }

    public function test_tags_add_edit_archive_restore(): void
    {
        $place = $this->makePlace('A');
        $this->workIn($place['admin'], $place['division']);
        $place['task']->tags()->attach($place['tag']->id);

        Livewire::test(Tags::class)->set('newName', 'Rush')->set('newColor', '#ff0000')->call('add')->assertHasNoErrors();
        $rush = Tag::where('name', 'Rush')->firstOrFail();
        $this->assertSame($place['business']->id, $rush->business_id);

        Livewire::test(Tags::class)->call('edit', $rush->id)->set('color', 'red')->call('save')->assertHasErrors('color');

        Livewire::test(Tags::class)->call('archive', $place['tag']->id);
        $this->assertCount(0, $place['task']->fresh()->tags);

        Livewire::test(Tags::class)->call('restore', $place['tag']->id);
        $this->assertCount(1, $place['task']->fresh()->tags);
    }
}
