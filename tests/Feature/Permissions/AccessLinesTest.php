<?php

namespace Tests\Feature\Permissions;

use App\Models\Business;
use App\Models\Company;
use App\Models\Organization;
use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\User;
use App\Notifications\Invitation;
use App\Services\PeopleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;
use Tests\Concerns\BuildsPlaces;
use Tests\TestCase;

/**
 * A person's access is a list of "role + where" lines that flow downward.
 */
class AccessLinesTest extends TestCase
{
    use RefreshDatabase, BuildsPlaces;

    private Organization $empreus;

    private Business $cleaning;

    private Business $painting;

    private Company $jacksonville;

    private Company $volusia;

    private Company $orlando;

    protected function setUp(): void
    {
        parent::setUp();

        $this->empreus = Organization::factory()->create(['name' => 'Empreus']);
        $this->cleaning = Business::factory()->for($this->empreus)->create(['name' => 'Cleaning']);
        $this->painting = Business::factory()->for($this->empreus)->create(['name' => 'Painting']);
        $this->jacksonville = Company::factory()->for($this->cleaning)->create(['name' => 'Jacksonville']);
        $this->volusia = Company::factory()->for($this->cleaning)->create(['name' => 'Volusia']);
        $this->orlando = Company::factory()->for($this->painting)->create(['name' => 'Orlando']);
    }

    public function test_new_organizations_get_the_starter_roles(): void
    {
        $this->assertEqualsCanonicalizing(
            ['Owner', 'Admin', 'Scheduler', 'Supervisor', 'Worker', 'Viewer'],
            $this->empreus->roles()->pluck('name')->all()
        );
    }

    public function test_organization_wide_access_covers_every_division(): void
    {
        $owner = $this->makePerson($this->empreus, 'owner');

        $this->assertTrue($owner->canAccessDivision($this->jacksonville));
        $this->assertTrue($owner->canAccessDivision($this->orlando));
        $this->assertCount(3, $owner->accessibleDivisions()->get());
    }

    public function test_business_access_covers_its_divisions_only(): void
    {
        $manager = $this->makePerson($this->cleaning, 'admin');

        $this->assertTrue($manager->canAccessDivision($this->jacksonville));
        $this->assertTrue($manager->canAccessDivision($this->volusia));
        $this->assertFalse($manager->canAccessDivision($this->orlando));
    }

    public function test_a_person_can_have_different_roles_in_different_places(): void
    {
        $lead = $this->makePerson($this->jacksonville, 'worker', 'Crew lead');
        app(PeopleService::class)->grant($lead, $this->role('scheduler'), RoleAssignment::DIVISION, $this->orlando->id);

        $this->assertSame('own', $lead->reachIn($this->jacksonville));
        $this->assertFalse($lead->hasLevel('scheduler', 'view', $this->jacksonville));

        $this->assertSame('all', $lead->reachIn($this->orlando));
        $this->assertTrue($lead->hasLevel('scheduler', 'edit', $this->orlando));

        $this->assertFalse($lead->canAccessDivision($this->volusia));
    }

    public function test_the_highest_level_wins_when_two_lines_cover_the_same_division(): void
    {
        $person = $this->makePerson($this->cleaning, 'viewer');
        $this->assertFalse($person->hasLevel('tasks', 'edit', $this->volusia));

        app(PeopleService::class)->grant($person, $this->role('scheduler'), RoleAssignment::DIVISION, $this->volusia->id);

        $this->assertTrue($person->fresh()->hasLevel('tasks', 'edit', $this->volusia));
        $this->assertFalse($person->fresh()->hasLevel('tasks', 'edit', $this->jacksonville));
    }

    public function test_a_role_cannot_be_given_in_another_organization(): void
    {
        $other = Company::factory()->create();
        $person = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        app(PeopleService::class)->grant($person, $this->role('worker'), RoleAssignment::DIVISION, $other->id);
    }

    public function test_owner_can_only_be_given_for_the_whole_organization(): void
    {
        $this->expectException(InvalidArgumentException::class);
        app(PeopleService::class)->grant(User::factory()->create(), $this->role('owner'), RoleAssignment::DIVISION, $this->jacksonville->id);
    }

    public function test_the_last_owner_cannot_be_removed(): void
    {
        $owner = $this->makePerson($this->empreus, 'owner');
        $line = $owner->roleAssignments()->first();

        $this->expectException(InvalidArgumentException::class);
        app(PeopleService::class)->revoke($line);
    }

    public function test_an_owner_can_be_removed_when_another_owner_exists(): void
    {
        $first = $this->makePerson($this->empreus, 'owner');
        $this->makePerson($this->empreus, 'owner');

        app(PeopleService::class)->revoke($first->roleAssignments()->first());

        $this->assertFalse($first->fresh()->canAccessDivision($this->jacksonville));
    }

    public function test_inviting_sends_a_choose_your_password_email_instead_of_a_shared_password(): void
    {
        Notification::fake();

        $person = app(PeopleService::class)->invite('Nina New', 'nina@example.com', $this->role('scheduler'), [
            ['scope_type' => RoleAssignment::DIVISION, 'scope_id' => $this->volusia->id],
        ]);

        Notification::assertSentTo($person, Invitation::class);
        $this->assertFalse(Hash::check('demo', $person->password));
        $this->assertTrue($person->canAccessDivision($this->volusia));
    }

    public function test_copy_access_gives_the_same_lines(): void
    {
        $existing = $this->makePerson($this->jacksonville, 'scheduler');
        app(PeopleService::class)->grant($existing, $this->role('viewer'), RoleAssignment::BUSINESS, $this->painting->id);
        $newcomer = User::factory()->create();

        app(PeopleService::class)->copyAccess($existing, $newcomer, $this->empreus);

        $this->assertTrue($newcomer->canAccessDivision($this->jacksonville));
        $this->assertTrue($newcomer->canAccessDivision($this->orlando));
        $this->assertFalse($newcomer->canAccessDivision($this->volusia));
    }

    public function test_deactivated_people_lose_access_and_cannot_log_in(): void
    {
        $person = $this->makePerson($this->jacksonville, 'worker');
        $person->forceFill(['password' => Hash::make('secret-pass')])->save();
        app(PeopleService::class)->deactivate($person);

        $this->assertFalse($person->fresh()->canAccessDivision($this->jacksonville));

        \Livewire\Volt\Volt::test('pages.auth.login')
            ->set('form.email', $person->email)
            ->set('form.password', 'secret-pass')
            ->call('login')
            ->assertHasErrors();
        $this->assertGuest();
    }

    public function test_workers_list_only_includes_people_whose_role_can_take_tasks(): void
    {
        $worker = $this->makePerson($this->jacksonville, 'worker', 'Willa Worker');
        $this->makePerson($this->jacksonville, 'scheduler', 'Sam Scheduler');

        $names = User::workersInDivision($this->jacksonville->id)->pluck('name')->all();

        $this->assertSame(['Willa Worker'], $names);
        app(PeopleService::class)->deactivate($worker);
        $this->assertSame([], User::workersInDivision($this->jacksonville->id)->pluck('name')->all());
    }

    public function test_role_summary_reads_in_plain_language(): void
    {
        $overseer = Role::create([
            'organization_id' => $this->empreus->id,
            'name' => 'Overseer',
            'levels' => Role::expandLevels(['scheduler' => 'view', 'tasks' => 'view']),
            'details' => ['money' => false, 'contacts' => true, 'notes' => true],
            'reach' => 'all',
        ]);

        $this->assertSame(
            "Can see tasks and scheduler. Can't change anything. Doesn't see prices and money.",
            $overseer->summary()
        );
    }

    private function role(string $key): Role
    {
        return $this->empreus->roles()->where('key', $key)->firstOrFail();
    }
}
