<?php

namespace Tests\Feature\Permissions;

use App\Models\Role;
use App\Models\User;
use App\Services\RoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\Concerns\BuildsPlaces;
use Tests\TestCase;

/**
 * Creating and changing roles, and the rule that nobody can give more than they have.
 */
class RoleRulesTest extends TestCase
{
    use RefreshDatabase, BuildsPlaces;

    private RoleService $roles;

    protected function setUp(): void
    {
        parent::setUp();
        $this->roles = app(RoleService::class);
    }

    /** A role that can manage people but can't see money or open reports. */
    private function peopleManagerIn(array $place): User
    {
        $role = Role::create([
            'organization_id' => $place['organization']->id,
            'name' => 'Office helper',
            'levels' => Role::expandLevels(['people' => 'full', 'tasks' => 'edit', 'lots' => 'view']),
            'details' => ['money' => false, 'contacts' => true, 'notes' => true],
            'reach' => 'all',
        ]);

        return $this->makePerson($place['division'], $role, 'Helper');
    }

    public function test_owner_can_create_a_role_from_a_copy(): void
    {
        $place = $this->makePlace('A');
        $this->workIn($place['admin'], $place['division']);

        $scheduler = $place['organization']->roles()->where('key', 'scheduler')->first();
        $overseer = $this->roles->create($place['admin'], $place['organization'], 'Overseer', $scheduler);

        $this->assertSame('Overseer', $overseer->name);
        $this->assertSame($scheduler->levels, $overseer->levels);
        $this->assertFalse($overseer->is_owner);
        $this->assertNull($overseer->key);
    }

    public function test_names_stay_unique_inside_an_organization(): void
    {
        $place = $this->makePlace('A');
        $this->workIn($place['admin'], $place['division']);

        $first = $this->roles->create($place['admin'], $place['organization'], 'Scheduler');

        $this->assertSame('Scheduler (2)', $first->name);
    }

    public function test_a_copy_of_owner_is_a_normal_role_with_everything(): void
    {
        $place = $this->makePlace('A');
        $this->workIn($place['admin'], $place['division']);

        $owner = $place['organization']->roles()->where('is_owner', true)->first();
        $copy = $this->roles->create($place['admin'], $place['organization'], 'Partner', $owner);

        $this->assertFalse($copy->is_owner);
        $this->assertSame('full', $copy->level('settings'));
        $this->assertTrue($copy->sees('money'));
    }

    public function test_nobody_can_build_a_role_stronger_than_their_own(): void
    {
        $place = $this->makePlace('A');
        $helper = $this->peopleManagerIn($place);
        $this->workIn($helper, $place['division']);

        $role = $this->roles->create($helper, $place['organization'], 'Crew lead');

        // Within their own access: fine.
        $this->roles->update($helper, $role, ['levels' => ['tasks' => 'edit', 'lots' => 'view']]);

        // Settings is hidden for them, so they can't hand it out.
        try {
            $this->roles->update($helper, $role->fresh(), ['levels' => ['settings' => 'edit']]);
            $this->fail('Expected the change to be refused.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('Settings', $e->getMessage());
        }

        // Nor money they can't see.
        $this->expectException(InvalidArgumentException::class);
        $this->roles->update($helper, $role->fresh(), ['details' => ['money' => true]]);
    }

    public function test_only_owners_can_give_the_owner_role(): void
    {
        $place = $this->makePlace('A');
        $helper = $this->peopleManagerIn($place);
        $this->workIn($helper, $place['division']);

        $owner = $place['organization']->roles()->where('is_owner', true)->first();

        $this->assertFalse($this->roles->canGrant($helper, $owner));
        $this->assertTrue($this->roles->canGrant($place['admin'], $owner));
    }

    public function test_owner_role_cannot_be_changed_or_archived(): void
    {
        $place = $this->makePlace('A');
        $this->workIn($place['admin'], $place['division']);
        $owner = $place['organization']->roles()->where('is_owner', true)->first();

        try {
            $this->roles->update($place['admin'], $owner, ['levels' => ['settings' => 'hidden']]);
            $this->fail('Owner role should be locked.');
        } catch (InvalidArgumentException) {
        }

        $this->expectException(InvalidArgumentException::class);
        $this->roles->archive($place['admin'], $owner);
    }

    public function test_a_role_in_use_cannot_be_archived_but_an_unused_one_can(): void
    {
        $place = $this->makePlace('A');
        $this->workIn($place['admin'], $place['division']);

        $worker = $place['organization']->roles()->where('key', 'worker')->first();
        try {
            $this->roles->archive($place['admin'], $worker);
            $this->fail('Worker role is in use.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('another role first', $e->getMessage());
        }

        $spare = $this->roles->create($place['admin'], $place['organization'], 'Spare');
        $this->roles->archive($place['admin'], $spare);
        $this->assertSoftDeleted($spare);

        $this->roles->restore($place['admin'], $spare);
        $this->assertNotSoftDeleted($spare);
    }

    public function test_platform_admin_can_share_a_role_through_the_library(): void
    {
        $a = $this->makePlace('A');
        $b = $this->makePlace('B');
        $platform = User::factory()->create(['is_platform_admin' => true]);
        $this->workIn($platform, $a['division']);

        $overseer = $this->roles->create($platform, $a['organization'], 'Overseer');
        $this->roles->update($platform, $overseer, ['levels' => ['scheduler' => 'view', 'tasks' => 'view']]);

        $template = $this->roles->saveToLibrary($platform, $overseer->fresh());
        $this->assertTrue($template->isLibraryTemplate());

        // Organization B's owner copies it.
        $this->workIn($b['admin'], $b['division']);
        $copy = $this->roles->create($b['admin'], $b['organization'], 'Overseer', $template);
        $this->assertSame($b['organization']->id, $copy->organization_id);
        $this->assertSame('view', $copy->level('scheduler'));
        $this->assertSame('hidden', $copy->level('lots'));

        // Owners can't add to the library.
        $this->expectException(InvalidArgumentException::class);
        $this->roles->saveToLibrary($b['admin'], $copy);
    }

    public function test_roles_from_another_organization_cannot_be_copied(): void
    {
        $a = $this->makePlace('A');
        $b = $this->makePlace('B');
        $this->workIn($a['admin'], $a['division']);

        $this->expectException(InvalidArgumentException::class);
        $this->roles->create($a['admin'], $a['organization'], 'Stolen', $b['organization']->roles()->first());
    }
}
