<?php

namespace Tests\Feature\Settings;

use App\Livewire\Settings\RoleEditor;
use App\Livewire\Settings\Roles;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsPlaces;
use Tests\TestCase;

class RolesScreenTest extends TestCase
{
    use RefreshDatabase, BuildsPlaces;

    public function test_owner_sees_roles_with_plain_summaries(): void
    {
        $place = $this->makePlace('A');
        $this->workIn($place['admin'], $place['division']);

        $this->get(route('settings.roles'))
            ->assertOk()
            ->assertSee('Scheduler')
            ->assertSee('Can see dashboard');
    }

    public function test_settings_opens_the_first_allowed_tab(): void
    {
        $place = $this->makePlace('A');
        $this->workIn($place['admin'], $place['division']);
        $this->get(route('settings'))->assertRedirect();
    }

    public function test_people_without_people_access_cannot_open_roles(): void
    {
        $place = $this->makePlace('A');
        $scheduler = $this->makePerson($place['division'], 'scheduler', 'Sam');
        $this->workIn($scheduler, $place['division']);

        $this->get(route('settings.roles'))->assertForbidden();
    }

    public function test_roles_of_another_organization_do_not_open(): void
    {
        $a = $this->makePlace('A');
        $b = $this->makePlace('B');
        $this->workIn($a['admin'], $a['division']);

        $this->get(route('settings.roles.edit', $b['organization']->roles()->first()))->assertNotFound();
    }

    public function test_create_an_overseer_and_set_its_levels(): void
    {
        $place = $this->makePlace('A');
        $this->workIn($place['admin'], $place['division']);

        Livewire::test(Roles::class)
            ->set('newName', 'Overseer')
            ->call('createRole')
            ->assertRedirect();

        $overseer = Role::where('name', 'Overseer')->firstOrFail();

        Livewire::test(RoleEditor::class, ['role' => $overseer])
            ->set('levels.scheduler', 'view')
            ->set('levels.tasks', 'view')
            ->assertSee('Can see tasks and scheduler.')
            ->assertSee("Can't change anything.")
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('view', $overseer->fresh()->level('scheduler'));
        $this->assertSame('hidden', $overseer->fresh()->level('lots'));
    }

    public function test_the_editor_explains_when_a_change_is_too_much(): void
    {
        $place = $this->makePlace('A');
        $role = Role::create([
            'organization_id' => $place['organization']->id,
            'name' => 'People only',
            'levels' => Role::expandLevels(['people' => 'edit', 'tasks' => 'view']),
            'details' => [],
        ]);
        $helper = $this->makePerson($place['division'], $role, 'Helper');
        $this->workIn($helper, $place['division']);

        $target = Role::create([
            'organization_id' => $place['organization']->id,
            'name' => 'Target',
            'levels' => Role::expandLevels([]),
            'details' => [],
        ]);

        Livewire::test(RoleEditor::class, ['role' => $target])
            ->set('levels.settings', 'full')
            ->assertSee("You can't give more access than you have", false)
            ->call('save')
            ->assertHasErrors('levels');

        $this->assertSame('hidden', $target->fresh()->level('settings'));
    }

    public function test_viewers_of_people_can_look_but_not_save(): void
    {
        $place = $this->makePlace('A');
        $role = Role::create([
            'organization_id' => $place['organization']->id,
            'name' => 'Auditor',
            'levels' => Role::expandLevels(['people' => 'view']),
            'details' => [],
        ]);
        $auditor = $this->makePerson($place['division'], $role, 'Auditor');
        $this->workIn($auditor, $place['division']);

        $scheduler = $place['organization']->roles()->where('key', 'scheduler')->first();
        Livewire::test(RoleEditor::class, ['role' => $scheduler])
            ->assertDontSee('Save role')
            ->call('save')
            ->assertForbidden();
    }

    public function test_view_as_lowers_access_until_stopped(): void
    {
        $place = $this->makePlace('A');
        $this->workIn($place['admin'], $place['division']);
        $viewer = $place['organization']->roles()->where('key', 'viewer')->first();

        Livewire::test(RoleEditor::class, ['role' => $viewer])->call('viewAs')->assertRedirect(route('dashboard'));
        $this->assertSame($viewer->id, session('view_as_role_id'));

        $this->get(route('dashboard'))->assertOk()->assertSee('seeing the app as');
        $this->get(route('settings.roles'))->assertForbidden();
        $this->get(route('lot-create'))->assertForbidden();

        $this->post(route('settings.view-as.stop'))->assertRedirect(route('settings.roles'));
        $this->assertNull(session('view_as_role_id'));
        $this->get(route('settings.roles'))->assertOk();
    }

    public function test_only_platform_admins_see_save_to_library(): void
    {
        $place = $this->makePlace('A');
        $this->workIn($place['admin'], $place['division']);
        $this->get(route('settings.roles'))->assertDontSee('Save to library');

        $platform = User::factory()->create(['is_platform_admin' => true]);
        $this->workIn($platform, $place['division']);
        $this->get(route('settings.roles'))->assertSee('Save to library');

        $scheduler = $place['organization']->roles()->where('key', 'scheduler')->first();
        Livewire::test(Roles::class)->call('saveToLibrary', $scheduler->id);
        $this->assertTrue(Role::library()->where('name', 'Scheduler')->exists());
    }
}
