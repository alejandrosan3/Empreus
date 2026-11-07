<?php

namespace Tests\Concerns;

use App\Models\Builder;
use App\Models\Business;
use App\Models\Company;
use App\Models\Lot;
use App\Models\Organization;
use App\Models\Role;
use App\Models\RoleAssignment;
use App\Services\PeopleService;
use App\Models\StatusType;
use App\Models\Supervisor;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TaskType;
use App\Models\Team;
use App\Models\User;

/**
 * Builds complete, separate "places" (organization › business › division) with
 * their own records, so tests can check that one never sees the other.
 */
trait BuildsPlaces
{
    protected function pendingStatus(): StatusType
    {
        return StatusType::where('identifier', 'pending')->first()
            ?? tap((new StatusType)->forceFill([
                'identifier' => 'pending', 'name' => 'Pending', 'color' => '#999999', 'color_dark' => '#666666',
            ]))->save();
    }

    /**
     * @return array{organization: Organization, business: Business, division: Company, admin: User, worker: User, builder: Builder, supervisor: Supervisor, lot: Lot, task: Task, team: Team, tag: Tag, taskType: TaskType}
     */
    protected function makePlace(string $label, ?Business $business = null): array
    {
        $business ??= Business::factory()->for(Organization::factory()->state(['name' => "$label Org"]))->create(['name' => "$label Business"]);
        $division = Company::factory()->for($business)->create(['name' => "$label Division"]);

        return tenancy()->runIn($division, function () use ($label, $business, $division) {
            $admin = $this->makeAdmin($division, "$label Admin");
            $worker = $this->makePerson($division, 'worker', "$label Worker");

            $builder = Builder::factory()->create(['name' => "$label Builder"]);
            $supervisor = Supervisor::factory()->create(['name' => "$label Supervisor"]);
            $taskType = TaskType::factory()->create(['name' => "$label Task Type"]);
            $lot = Lot::factory()->create([
                'company_id' => $division->id,
                'builder_id' => $builder->id,
                'supervisor_id' => $supervisor->id,
                'status_type_id' => $this->pendingStatus()->id,
                'subdivision' => "$label Subdivision",
            ]);
            $task = Task::factory()->create([
                'company_id' => $division->id,
                'lot_id' => $lot->id,
                'task_type_id' => $taskType->id,
                'status_type_id' => $this->pendingStatus()->id,
            ]);
            $team = Team::factory()->create(['name' => "$label Team"]);
            $tag = Tag::factory()->create(['name' => "$label Tag"]);

            return [
                'organization' => $business->organization,
                'business' => $business,
                'division' => $division,
                'admin' => $admin,
                'worker' => $worker,
                'builder' => $builder,
                'supervisor' => $supervisor,
                'lot' => $lot,
                'task' => $task,
                'team' => $team,
                'tag' => $tag,
                'taskType' => $taskType,
            ];
        });
    }

    /** An Owner of the division's whole organization. */
    protected function makeAdmin(Company $division, string $name = 'Admin'): User
    {
        $organization = $division->business->organization;

        return $this->makePerson($organization, 'owner', $name);
    }

    /**
     * A person with one starter role (owner, admin, scheduler, supervisor, worker,
     * viewer) or a given Role, in an organization, business or division.
     */
    protected function makePerson(Organization|Business|Company $place, string|Role $role, string $name = 'Person'): User
    {
        $organization = match (true) {
            $place instanceof Organization => $place,
            $place instanceof Business => $place->organization,
            $place instanceof Company => $place->business->organization,
        };
        $role = $role instanceof Role ? $role : $organization->roles()->where('key', $role)->firstOrFail();
        $scopeType = match (true) {
            $place instanceof Organization => RoleAssignment::ORGANIZATION,
            $place instanceof Business => RoleAssignment::BUSINESS,
            $place instanceof Company => RoleAssignment::DIVISION,
        };

        $user = User::factory()->create(['name' => $name]);
        app(PeopleService::class)->grant($user, $role, $scopeType, $place->id);

        return $user;
    }

    /** Log in as someone working in the given division. */
    protected function workIn(User $user, Company $division): static
    {
        $this->actingAs($user)->withSession(['company_id' => $division->id]);
        session(['company_id' => $division->id]);
        tenancy()->setDivision($division);

        return $this;
    }
}
