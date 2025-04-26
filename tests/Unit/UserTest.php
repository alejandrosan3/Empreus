<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\Lot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that User model can fetch its lots and with company_id in context
     */
    public function test_it_returns_lots_for_current_company(): void
    {
        // Create a user
        $user = User::factory()->create();

        // Create two companies and associate the user with them
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();

        $user->companies()->attach([$company1->id, $company2->id]);

        // Create lots for each company
        $lot1 = Lot::factory()->create(['company_id' => $company1->id]);
        $lot2 = Lot::factory()->create(['company_id' => $company2->id]);

        // Set the session to the first company
        session(['company_id' => $company1->id]);

        // Retrieve lots for the current company
        $lots = $user->lotsForCurrentCompany()->get();

        // Assert that only the lots for the current company are returned
        $this->assertTrue($lots->contains($lot1));
        $this->assertFalse($lots->contains($lot2));
    }
}
