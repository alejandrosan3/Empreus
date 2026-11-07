<?php

namespace Tests\Feature\Settings;

use App\Livewire\Settings\People;
use App\Livewire\Settings\Person;
use App\Models\Company;
use App\Models\RoleAssignment;
use App\Models\User;
use App\Notifications\Invitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\Concerns\BuildsPlaces;
use Tests\TestCase;

class PeopleScreenTest extends TestCase
{
    use RefreshDatabase, BuildsPlaces;

    public function test_people_list_shows_only_this_organization(): void
    {
        $a = $this->makePlace('A');
        $b = $this->makePlace('B');
        $this->workIn($a['admin'], $a['division']);

        $this->get(route('settings.people'))
            ->assertOk()
            ->assertSee('A Worker')
            ->assertSeeText('Worker in A Business › A Division')
            ->assertDontSee('B Worker');

        $this->get(route('settings.people.show', $b['worker']))->assertNotFound();
    }

    public function test_invite_a_new_person_sends_an_email(): void
    {
        Notification::fake();
        $place = $this->makePlace('A');
        $this->workIn($place['admin'], $place['division']);
        $scheduler = $place['organization']->roles()->where('key', 'scheduler')->first();

        Livewire::test(People::class)
            ->call('openInvite')
            ->assertSet('place', 'division:'.$place['division']->id)
            ->set('name', 'Dana')
            ->set('email', 'dana@example.com')
            ->set('roleId', $scheduler->id)
            ->call('invite')
            ->assertHasNoErrors()
            ->assertRedirect();

        $dana = User::where('email', 'dana@example.com')->firstOrFail();
        $this->assertTrue($dana->canAccessDivision($place['division']));
        Notification::assertSentTo($dana, Invitation::class);
    }

    public function test_inviting_someone_from_another_organization_adds_access_without_email(): void
    {
        Notification::fake();
        $a = $this->makePlace('A');
        $b = $this->makePlace('B');
        $this->workIn($a['admin'], $a['division']);
        $viewer = $a['organization']->roles()->where('key', 'viewer')->first();

        Livewire::test(People::class)
            ->call('openInvite')
            ->set('name', 'whatever')
            ->set('email', $b['worker']->email)
            ->set('roleId', $viewer->id)
            ->call('invite')
            ->assertHasNoErrors();

        $this->assertTrue($b['worker']->fresh()->canAccessDivision($a['division']));
        $this->assertTrue($b['worker']->fresh()->canAccessDivision($b['division']));
        Notification::assertNothingSent();
    }

    public function test_an_admin_cannot_invite_an_owner(): void
    {
        $place = $this->makePlace('A');
        $admin = $this->makePerson($place['organization'], 'admin', 'Org admin');
        $this->workIn($admin, $place['division']);
        $owner = $place['organization']->roles()->where('is_owner', true)->first();

        Livewire::test(People::class)
            ->assertDontSee('>Owner<', false)
            ->set('name', 'X')->set('email', 'x@example.com')
            ->set('roleId', $owner->id)
            ->set('place', 'organization:'.$place['organization']->id)
            ->call('invite')
            ->assertHasErrors('roleId');

        $this->assertDatabaseMissing('users', ['email' => 'x@example.com']);
    }

    public function test_add_and_remove_access_lines(): void
    {
        $place = $this->makePlace('A');
        $other = Company::factory()->for($place['business'])->create(['name' => 'Volusia']);
        $this->workIn($place['admin'], $place['division']);
        $scheduler = $place['organization']->roles()->where('key', 'scheduler')->first();

        $component = Livewire::test(Person::class, ['user' => $place['worker']])
            ->set('roleId', $scheduler->id)
            ->set('place', 'division:'.$other->id)
            ->call('addLine')
            ->assertHasNoErrors()
            ->assertSee('Scheduler');

        $this->assertTrue($place['worker']->fresh()->canAccessDivision($other));

        $line = RoleAssignment::where('user_id', $place['worker']->id)->where('scope_id', $other->id)->firstOrFail();
        $component->call('removeLine', $line->id);
        $this->assertModelMissing($line);
    }

    public function test_copy_access_from_someone_else(): void
    {
        $place = $this->makePlace('A');
        $this->workIn($place['admin'], $place['division']);
        $newbie = $this->makePerson($place['division'], 'viewer', 'Newbie');

        Livewire::test(Person::class, ['user' => $newbie])
            ->set('copyFromId', $place['worker']->id)
            ->call('copyAccess')
            ->assertHasNoErrors();

        $this->assertTrue($newbie->roleAssignments()->whereHas('role', fn ($q) => $q->where('key', 'worker'))->exists());
    }

    public function test_close_and_reopen_an_account(): void
    {
        $place = $this->makePlace('A');
        $this->workIn($place['admin'], $place['division']);

        Livewire::test(Person::class, ['user' => $place['worker']])->call('deactivate')->assertHasNoErrors();
        $this->assertTrue($place['worker']->fresh()->isDeactivated());

        Livewire::test(Person::class, ['user' => $place['worker']->fresh()])->call('reactivate');
        $this->assertFalse($place['worker']->fresh()->isDeactivated());
    }

    public function test_owners_and_yourself_cannot_be_closed_here(): void
    {
        $place = $this->makePlace('A');
        $this->workIn($place['admin'], $place['division']);

        Livewire::test(Person::class, ['user' => $place['admin']])->call('deactivate')->assertHasErrors('lines');
        $this->assertFalse($place['admin']->fresh()->isDeactivated());
    }

    public function test_people_working_for_another_organization_are_not_closed_by_one_of_them(): void
    {
        $a = $this->makePlace('A');
        $b = $this->makePlace('B');
        $viewer = $a['organization']->roles()->where('key', 'viewer')->first();
        app(\App\Services\PeopleService::class)->grant($b['worker'], $viewer, RoleAssignment::DIVISION, $a['division']->id);
        $this->workIn($a['admin'], $a['division']);

        Livewire::test(Person::class, ['user' => $b['worker']])->call('deactivate')->assertHasErrors('lines');
        $this->assertFalse($b['worker']->fresh()->isDeactivated());
    }

    public function test_link_a_supervisor_record(): void
    {
        $place = $this->makePlace('A');
        $this->workIn($place['admin'], $place['division']);

        Livewire::test(Person::class, ['user' => $place['worker']])
            ->set('supervisorId', $place['supervisor']->id)
            ->call('linkSupervisor');

        $this->assertSame($place['worker']->id, $place['supervisor']->fresh()->user_id);
    }

    public function test_viewers_of_people_cannot_change_anything(): void
    {
        $place = $this->makePlace('A');
        $role = \App\Models\Role::create([
            'organization_id' => $place['organization']->id,
            'name' => 'Auditor',
            'levels' => \App\Models\Role::expandLevels(['people' => 'view']),
            'details' => [],
        ]);
        $auditor = $this->makePerson($place['division'], $role, 'Auditor');
        $this->workIn($auditor, $place['division']);

        $this->get(route('settings.people'))->assertOk()->assertDontSee('wire:click="openInvite"', false);
        Livewire::test(Person::class, ['user' => $place['worker']])
            ->assertDontSee('Close account')
            ->call('deactivate')->assertForbidden();
    }
}
