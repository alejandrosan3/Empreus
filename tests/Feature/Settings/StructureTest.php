<?php

namespace Tests\Feature\Settings;

use App\Livewire\Settings\OrganizationSettings;
use App\Livewire\Settings\Preferences;
use App\Livewire\Settings\Structure;
use App\Models\Business;
use App\Models\Company;
use App\Models\Lot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsPlaces;
use Tests\TestCase;

class StructureTest extends TestCase
{
    use RefreshDatabase, BuildsPlaces;

    public function test_owner_adds_a_business_and_a_division(): void
    {
        $place = $this->makePlace('A');
        $this->workIn($place['admin'], $place['division']);

        Livewire::test(Structure::class)
            ->set('newBusiness', 'Painting')->call('addBusiness')->assertHasNoErrors();
        $painting = Business::where('name', 'Painting')->firstOrFail();
        $this->assertSame($place['organization']->id, $painting->organization_id);

        Livewire::test(Structure::class)
            ->set("newDivision.{$painting->id}", 'Orlando')->call('addDivision', $painting->id)->assertHasNoErrors();
        $orlando = Company::where('name', 'Orlando')->firstOrFail();

        // The owner has the whole organization, so they can switch to it right away.
        $this->assertTrue($place['admin']->fresh()->canAccessDivision($orlando));
    }

    public function test_duplicate_names_are_refused(): void
    {
        $place = $this->makePlace('A');
        $this->workIn($place['admin'], $place['division']);

        Livewire::test(Structure::class)
            ->set("newDivision.{$place['business']->id}", 'A Division')
            ->call('addDivision', $place['business']->id)
            ->assertHasErrors("newDivision.{$place['business']->id}");
    }

    public function test_rename_and_archive_a_division_keeps_its_records(): void
    {
        $place = $this->makePlace('A');
        $volusia = Company::factory()->for($place['business'])->create(['name' => 'Volusia']);
        $this->workIn($place['admin'], $place['division']);

        Livewire::test(Structure::class)
            ->call('startRename', 'division', $place['division']->id)
            ->set('renameTo', 'Jacksonville')
            ->call('rename')
            ->assertHasNoErrors();
        $this->assertSame('Jacksonville', $place['division']->fresh()->name);

        Livewire::test(Structure::class)->call('archive', 'division', $place['division']->id)->assertHasNoErrors();
        $this->assertSoftDeleted($place['division']);
        $this->assertSame(1, Lot::withoutGlobalScopes()->where('company_id', $place['division']->id)->count());
        $this->assertFalse($place['admin']->fresh()->accessibleDivisions()->whereKey($place['division']->id)->exists());

        $this->workIn($place['admin'], $volusia);
        Livewire::test(Structure::class)->call('restore', 'division', $place['division']->id)->assertHasNoErrors();
        $this->assertNotSoftDeleted($place['division']);
    }

    public function test_the_last_division_cannot_be_archived(): void
    {
        $place = $this->makePlace('A');
        $this->workIn($place['admin'], $place['division']);

        Livewire::test(Structure::class)->call('archive', 'division', $place['division']->id)->assertHasErrors('structure');
        $this->assertNotSoftDeleted($place['division']);
    }

    public function test_a_division_admin_can_rename_only_their_division(): void
    {
        $place = $this->makePlace('A');
        $volusia = Company::factory()->for($place['business'])->create(['name' => 'Volusia']);
        $local = $this->makePerson($place['division'], 'admin', 'Local admin');
        $this->workIn($local, $place['division']);

        Livewire::test(Structure::class)
            ->call('startRename', 'division', $volusia->id)->set('renameTo', 'Hacked')->call('rename')
            ->assertHasErrors('renameTo');
        Livewire::test(Structure::class)
            ->set('newBusiness', 'Drywall')->call('addBusiness')->assertHasErrors('newBusiness');

        $this->assertSame('Volusia', $volusia->fresh()->name);
    }

    public function test_other_organizations_are_out_of_reach(): void
    {
        $a = $this->makePlace('A');
        $b = $this->makePlace('B');
        $this->workIn($a['admin'], $a['division']);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        Livewire::test(Structure::class)->call('archive', 'division', $b['division']->id);
    }

    public function test_organization_settings_change_the_currency(): void
    {
        $place = $this->makePlace('A');
        $this->workIn($place['admin'], $place['division']);

        Livewire::test(OrganizationSettings::class)->set('currencySymbol', '€')->call('save')->assertHasNoErrors();

        tenancy()->setDivision($place['division']->fresh()); // next request
        $this->assertSame('€1,250.00', money(1250));
    }

    public function test_a_division_admin_cannot_change_organization_settings(): void
    {
        $place = $this->makePlace('A');
        $local = $this->makePerson($place['division'], 'admin', 'Local admin');
        $this->workIn($local, $place['division']);

        Livewire::test(OrganizationSettings::class)->set('name', 'Mine now')->call('save')->assertForbidden();
    }

    public function test_rows_per_page_preference(): void
    {
        $place = $this->makePlace('A');
        $this->workIn($place['worker'], $place['division']);

        $this->get(route('settings.preferences'))->assertOk();
        Livewire::test(Preferences::class)->set('rowsPerPage', 50)->call('save')->assertHasNoErrors();
        $this->assertSame(50, $place['worker']->fresh()->rowsPerPage());

        Livewire::test(Preferences::class)->set('rowsPerPage', 7)->call('save')->assertHasErrors('rowsPerPage');
    }

    public function test_workers_cannot_open_organization_settings(): void
    {
        $place = $this->makePlace('A');
        $this->workIn($place['worker'], $place['division']);

        $this->get(route('settings.organization'))->assertForbidden();
        $this->get(route('settings'))->assertRedirect(route('settings.preferences'));
    }
}
