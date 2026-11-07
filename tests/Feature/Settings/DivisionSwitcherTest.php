<?php

namespace Tests\Feature\Settings;

use App\Livewire\Layout\MultiaccountsDropdown;
use App\Models\Company;
use App\Models\RoleAssignment;
use App\Services\PeopleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsPlaces;
use Tests\TestCase;

class DivisionSwitcherTest extends TestCase
{
    use RefreshDatabase, BuildsPlaces;

    public function test_lists_divisions_from_access_lines_with_business_names(): void
    {
        $place = $this->makePlace('A');
        Company::factory()->for($place['business'])->create(['name' => 'Volusia']);
        $this->workIn($place['admin'], $place['division']);

        $accounts = Livewire::test(MultiaccountsDropdown::class)->get('accounts');

        $this->assertSame(['A Business › A Division', 'A Business › Volusia'], array_column($accounts, 'label'));
    }

    public function test_shows_the_organization_when_someone_works_for_several(): void
    {
        $a = $this->makePlace('A');
        $b = $this->makePlace('B');
        $viewer = $b['organization']->roles()->where('key', 'viewer')->first();
        app(PeopleService::class)->grant($a['worker'], $viewer, RoleAssignment::DIVISION, $b['division']->id);
        $this->workIn($a['worker'], $a['division']);

        $labels = array_column(Livewire::test(MultiaccountsDropdown::class)->get('accounts'), 'label');

        $this->assertSame(['A Org · A Business › A Division', 'B Org · B Business › B Division'], $labels);
    }

    public function test_cannot_switch_to_a_division_without_access(): void
    {
        $a = $this->makePlace('A');
        $b = $this->makePlace('B');
        $this->workIn($a['admin'], $a['division']);

        Livewire::test(MultiaccountsDropdown::class)
            ->set('selectedAccountId', $b['division']->id)
            ->assertSet('selectedAccountId', $a['division']->id)
            ->assertNoRedirect();
    }
}
