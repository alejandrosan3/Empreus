<?php

namespace Tests\Feature\Permissions;

use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsPlaces;
use Tests\TestCase;

/**
 * Pages and menu items follow the role's level for each area.
 */
class PageAccessTest extends TestCase
{
    use RefreshDatabase, BuildsPlaces;

    private array $place;

    protected function setUp(): void
    {
        parent::setUp();
        $this->place = $this->makePlace('Acme');
        tenancy()->forget();
    }

    public function test_workers_cannot_open_office_pages(): void
    {
        $this->workIn($this->place['worker'], $this->place['division']);

        $this->get(route('dashboard'))->assertOk()->assertDontSee('Builders')->assertDontSee('Lots Progress');
        $this->get(route('builders'))->assertForbidden();
        $this->get(route('lot-progress'))->assertForbidden();
        $this->get(route('task-scheduler'))->assertForbidden();
        $this->get(route('settings.structure'))->assertForbidden();
    }

    public function test_owners_see_every_menu_item(): void
    {
        $this->workIn($this->place['admin'], $this->place['division']);

        $this->get(route('dashboard'))->assertOk()
            ->assertSee('Builders')->assertSee('Workers')->assertSee('Lots Progress')
            ->assertSee('Tasks Progress')->assertSee('Tasks Scheduler');
    }

    public function test_a_custom_overseer_role_sees_only_what_it_was_given(): void
    {
        $overseer = Role::create([
            'organization_id' => $this->place['organization']->id,
            'name' => 'Overseer',
            'levels' => Role::expandLevels(['dashboard' => 'view', 'scheduler' => 'view', 'tasks' => 'view']),
            'details' => ['money' => false, 'contacts' => false, 'notes' => true],
            'reach' => 'all',
        ]);
        $person = $this->makePerson($this->place['division'], $overseer, 'Olivia Overseer');

        $this->workIn($person, $this->place['division']);

        $this->get(route('task-scheduler'))->assertOk();
        $this->get(route('task-progress'))->assertOk();
        $this->get(route('lot-progress'))->assertForbidden();
        $this->get(route('lot-details', $this->place['lot']->id))->assertForbidden();
        $this->get(route('task-create'))->assertForbidden(); // view only, can't add tasks
        $this->get(route('dashboard'))->assertSee('Tasks Scheduler')->assertDontSee('Lots Progress');
    }

    public function test_schedulers_can_add_tasks_but_not_open_settings(): void
    {
        $scheduler = $this->makePerson($this->place['division'], 'scheduler');
        $this->workIn($scheduler, $this->place['division']);

        $this->get(route('task-create'))->assertOk();
        $this->get(route('settings.structure'))->assertForbidden();
    }

    public function test_people_with_no_access_anywhere_are_stopped(): void
    {
        $stranger = \App\Models\User::factory()->create();

        $this->actingAs($stranger)->get(route('dashboard'))->assertForbidden();
    }
}
