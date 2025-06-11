<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\Lot;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Company $company1;
    private Company $company2;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user and companies
        $this->user = User::factory()->create();
        $this->company1 = Company::factory()->create();
        $this->company2 = Company::factory()->create();

        // Associate user with companies
        $this->user->companies()->attach([$this->company1->id, $this->company2->id]);

        // Set default company context
        session(['company_id' => $this->company1->id]);

        $this->seed(\Database\Seeders\StatusTypeSeeder::class);
        $this->seed(\Database\Seeders\TaskTypeSeeder::class);
    }

    /**
     * Test that User model can fetch its lots and with company_id in context
     */
    public function test_it_returns_lots_for_current_company(): void
    {
        // Create lots for each company
        $lot1 = Lot::factory()->create(['company_id' => $this->company1->id]);
        $lot2 = Lot::factory()->create(['company_id' => $this->company2->id]);

        // Retrieve lots for the current company
        $lots = $this->user->lotsForCurrentCompany()->get();

        // Assert that only the lots for the current company are returned
        $this->assertTrue($lots->contains($lot1));
        $this->assertFalse($lots->contains($lot2));
    }

    /**
     * Test that User model can fetch tasks for the current company
     */
    public function test_it_returns_tasks_for_current_company(): void
    {
        // Create tasks for each company
        $task1 = Task::factory()->create(['company_id' => $this->company1->id]);
        $task2 = Task::factory()->create(['company_id' => $this->company2->id]);

        // Retrieve tasks for the current company
        $tasks = $this->user->tasksForCurrentCompany()->get();

        // Assert that only the tasks for the current company are returned
        $this->assertTrue($tasks->contains($task1));
        $this->assertFalse($tasks->contains($task2));
    }

    public function test_it_returns_empty_collection_when_no_company_context(): void
    {
        session()->forget('company_id');

        $this->assertEmpty($this->user->lotsForCurrentCompany()->get());
        $this->assertEmpty($this->user->tasksForCurrentCompany()->get());
    }

    public function test_it_returns_correct_number_of_lots_for_current_company(): void
    {
        // Create multiple lots for both companies
        Lot::factory(3)->create(['company_id' => $this->company1->id]);
        Lot::factory(2)->create(['company_id' => $this->company2->id]);

        $lots = $this->user->lotsForCurrentCompany()->get();

        $this->assertCount(3, $lots);
        $lots->each(fn($lot) => $this->assertEquals($this->company1->id, $lot->company_id));
    }
}
