<?php

namespace Tests\Unit;

use App\Models\Builder;
use App\Models\Company;
use App\Models\Lot;
use App\Models\Task;
use App\Models\User;
use App\Services\FilterOptionsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilterOptionsServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->company = Company::factory()->create();
        $this->user->companies()->attach($this->company->id);
        session(['company_id' => $this->company->id]);
        $this->actingAs($this->user);
    }

    public function test_lot_and_task_scopes_filter_correctly(): void
    {
        $builderA = Builder::factory()->create(['name' => 'Builder A']);
        $builderB = Builder::factory()->create(['name' => 'Builder B']);

        $lot1 = Lot::factory()->for($builderA)->for($this->company)->create([
            'subdivision' => 'Sub1',
            'phase' => '1',
            'lot_number' => '100',
        ]);
        $lot2 = Lot::factory()->for($builderB)->for($this->company)->create([
            'subdivision' => 'Sub2',
            'phase' => '1',
            'lot_number' => '200',
        ]);

        $task1 = Task::factory()->for($lot1)->for($this->company)->create();
        $task2 = Task::factory()->for($lot2)->for($this->company)->create();

        $lots = Lot::query()->filterByBuilders(['Builder A'])->get();
        $this->assertTrue($lots->contains($lot1));
        $this->assertFalse($lots->contains($lot2));

        $tasks = Task::query()->filterByLots(['100'])->get();
        $this->assertTrue($tasks->contains($task1));
        $this->assertFalse($tasks->contains($task2));
    }

    public function test_filter_options_service_returns_expected_lists(): void
    {
        $builderA = Builder::factory()->create(['name' => 'Builder A']);
        $lot = Lot::factory()->for($builderA)->for($this->company)->create([
            'subdivision' => 'Sub1',
            'phase' => '1',
            'lot_number' => '100',
        ]);
        Task::factory()->for($lot)->for($this->company)->create();

        $service = new FilterOptionsService();
        $options = $service->taskOptions(['Builder A'], [], []);

        $this->assertEquals(['Builder A'], $options['builders']);
        $this->assertEquals('Sub1|1', $options['subdivisions'][0]['value']);
        $this->assertEquals(['100'], $options['lots']);
    }
}
