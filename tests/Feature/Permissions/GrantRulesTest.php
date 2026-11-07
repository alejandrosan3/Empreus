<?php

namespace Tests\Feature\Permissions;

use App\Models\Company;
use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\User;
use App\Services\PeopleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\Concerns\BuildsPlaces;
use Tests\TestCase;

/**
 * Who can give access, and where.
 */
class GrantRulesTest extends TestCase
{
    use RefreshDatabase, BuildsPlaces;

    private PeopleService $people;

    protected function setUp(): void
    {
        parent::setUp();
        $this->people = app(PeopleService::class);
    }

    public function test_a_division_admin_can_only_give_access_in_their_division(): void
    {
        $place = $this->makePlace('A');
        $other = Company::factory()->for($place['business'])->create(['name' => 'Other']);
        $admin = $this->makePerson($place['division'], 'admin', 'Local admin');
        $this->workIn($admin, $place['division']);
        $scheduler = $place['organization']->roles()->where('key', 'scheduler')->first();
        $newcomer = User::factory()->create();

        $this->people->grantAs($admin, $newcomer, $scheduler, RoleAssignment::DIVISION, $place['division']->id);
        $this->assertTrue($newcomer->fresh()->canAccessDivision($place['division']));

        foreach ([
            [RoleAssignment::DIVISION, $other->id],
            [RoleAssignment::BUSINESS, $place['business']->id],
            [RoleAssignment::ORGANIZATION, $place['organization']->id],
        ] as [$type, $id]) {
            try {
                $this->people->grantAs($admin, $newcomer, $scheduler, $type, $id);
                $this->fail("Should not grant at $type");
            } catch (InvalidArgumentException) {
            }
        }

        $labels = array_column($this->people->placesManagedBy($admin, $place['organization']), 'label');
        $this->assertSame([$place['division']->fullName()], $labels);
    }

    public function test_a_business_admin_can_give_access_in_any_of_its_divisions(): void
    {
        $place = $this->makePlace('A');
        $other = Company::factory()->for($place['business'])->create(['name' => 'Other']);
        $admin = $this->makePerson($place['business'], 'admin', 'Business admin');
        $this->workIn($admin, $place['division']);

        $this->assertTrue($this->people->canManagePlace($admin, RoleAssignment::DIVISION, $other->id));
        $this->assertTrue($this->people->canManagePlace($admin, RoleAssignment::BUSINESS, $place['business']->id));
        $this->assertFalse($this->people->canManagePlace($admin, RoleAssignment::ORGANIZATION, $place['organization']->id));
    }

    public function test_nobody_can_give_access_in_another_organization(): void
    {
        $a = $this->makePlace('A');
        $b = $this->makePlace('B');
        $this->workIn($a['admin'], $a['division']);

        $this->assertFalse($this->people->canManagePlace($a['admin'], RoleAssignment::DIVISION, $b['division']->id));
    }

    public function test_people_without_people_access_cannot_give_access(): void
    {
        $place = $this->makePlace('A');
        $scheduler = $this->makePerson($place['division'], 'scheduler', 'Sam');
        $this->workIn($scheduler, $place['division']);

        $this->assertFalse($this->people->canManagePlace($scheduler, RoleAssignment::DIVISION, $place['division']->id));
    }

    public function test_an_admin_cannot_make_someone_owner(): void
    {
        $place = $this->makePlace('A');
        $admin = $this->makePerson($place['organization'], 'admin', 'Org admin');
        $this->workIn($admin, $place['division']);
        $owner = $place['organization']->roles()->where('is_owner', true)->first();

        $this->expectException(InvalidArgumentException::class);
        $this->people->grantAs($admin, User::factory()->create(), $owner, RoleAssignment::ORGANIZATION, $place['organization']->id);
    }

    public function test_removing_access_follows_the_same_rules(): void
    {
        $place = $this->makePlace('A');
        $other = Company::factory()->for($place['business'])->create(['name' => 'Other']);
        $local = $this->makePerson($place['division'], 'admin', 'Local admin');
        $farAway = $this->makePerson($other, 'worker', 'Far worker');
        $this->workIn($local, $place['division']);

        $this->expectException(InvalidArgumentException::class);
        $this->people->revokeAs($local, $farAway->roleAssignments()->first());
    }
}
