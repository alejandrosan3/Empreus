<?php

namespace Tests\Feature\Permissions;

use App\Livewire\Builders\ListBuilder;
use App\Livewire\Task\TaskProgress;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsPlaces;
use Tests\TestCase;

/**
 * Prices, contact details and notes only show to roles with those switches on.
 */
class SensitiveDetailsTest extends TestCase
{
    use RefreshDatabase, BuildsPlaces;

    private array $place;

    protected function setUp(): void
    {
        parent::setUp();
        $this->place = $this->makePlace('Acme');
        tenancy()->forget();
    }

    public function test_task_page_hides_the_price_from_roles_without_money(): void
    {
        $this->place['task']->update(['price' => 4321]);

        $this->workIn($this->makePerson($this->place['division'], 'scheduler'), $this->place['division']);
        $this->get(route('task-details', $this->place['task']->id))->assertOk()->assertDontSee('4,321')->assertDontSee('4321');

        $this->workIn($this->place['admin'], $this->place['division']);
        $this->get(route('task-details', $this->place['task']->id))->assertOk()->assertSee('4,321');
    }

    public function test_contact_columns_are_hidden_without_contacts(): void
    {
        $this->workIn($this->overseerWithDirectory(), $this->place['division']);

        $columns = Livewire::test(ListBuilder::class)->get('columns');
        $this->assertNotContains('email', $columns);
        $this->assertNotContains('phone', $columns);
        $this->assertContains('name', $columns);
    }

    public function test_price_column_is_hidden_from_roles_without_money(): void
    {
        $this->workIn($this->makePerson($this->place['division'], 'scheduler'), $this->place['division']);
        $columns = collect(Livewire::test(TaskProgress::class)->get('columns'))->pluck('value');
        $this->assertNotContains('price', $columns);

        $this->workIn($this->place['admin'], $this->place['division']);
        $columns = collect(Livewire::test(TaskProgress::class)->get('columns'))->pluck('value');
        $this->assertContains('price', $columns);
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

    public function test_statistics_hide_revenue_from_roles_without_money(): void
    {
        $viewer = $this->makePerson($this->place['division'], 'viewer', 'Vic'); // reports: view, money: off
        $this->workIn($viewer, $this->place['division']);
        $this->get(route('statistics'))->assertOk()->assertDontSee('Completed Revenue');

        $this->workIn($this->place['admin'], $this->place['division']);
        $this->get(route('statistics'))->assertOk()->assertSee('Completed Revenue');
    }
}
