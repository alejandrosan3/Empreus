<?php

namespace Tests\Feature\Tenancy;

use App\Livewire\Builders\ListBuilder;
use App\Livewire\Supervisors\ListSupervisor;
use App\Livewire\Teams\ListTeam;
use App\Livewire\Workers\ListWorker;
use App\Models\Builder;
use App\Models\Lot;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TaskType;
use App\Models\Team;
use App\Services\LotImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsPlaces;
use Tests\TestCase;

/**
 * Two separate customers ("Acme" and "Rival") must never see each other's records.
 */
class DivisionIsolationTest extends TestCase
{
    use RefreshDatabase, BuildsPlaces;

    private array $acme;

    private array $rival;

    protected function setUp(): void
    {
        parent::setUp();

        $this->acme = $this->makePlace('Acme');
        $this->rival = $this->makePlace('Rival');
        tenancy()->forget();
    }

    public function test_another_divisions_lot_page_cannot_be_opened(): void
    {
        $this->workIn($this->acme['admin'], $this->acme['division']);

        $this->get(route('lot-details', $this->acme['lot']->id))->assertOk();
        $this->get(route('lot-details', $this->rival['lot']->id))->assertNotFound();
    }

    public function test_another_divisions_task_page_cannot_be_opened(): void
    {
        $this->workIn($this->acme['admin'], $this->acme['division']);

        $this->get(route('task-details', $this->acme['task']->id))->assertOk();
        $this->get(route('task-details', $this->rival['task']->id))->assertNotFound();
    }

    public function test_lists_only_show_the_current_organizations_records(): void
    {
        $this->workIn($this->acme['admin'], $this->acme['division']);

        Livewire::test(ListBuilder::class)->assertSee('Acme Builder')->assertDontSee('Rival Builder');
        Livewire::test(ListSupervisor::class)->assertSee('Acme Supervisor')->assertDontSee('Rival Supervisor');
        Livewire::test(ListTeam::class)->assertSee('Acme Team')->assertDontSee('Rival Team');
        Livewire::test(ListWorker::class)->assertSee('Acme Worker')->assertDontSee('Rival Worker');
    }

    public function test_queries_only_return_the_current_places_records(): void
    {
        $this->workIn($this->acme['admin'], $this->acme['division']);

        $this->assertEquals([$this->acme['lot']->id], Lot::pluck('id')->all());
        $this->assertEquals([$this->acme['task']->id], Task::pluck('id')->all());
        $this->assertEquals([$this->acme['team']->id], Team::pluck('id')->all());
        $this->assertEquals(['Acme Tag'], Tag::pluck('name')->all());
        $this->assertEquals(['Acme Task Type'], TaskType::pluck('name')->all());
        $this->assertEquals(['Acme Builder'], Builder::pluck('name')->all());
    }

    public function test_new_records_are_stamped_with_the_current_place(): void
    {
        $this->workIn($this->acme['admin'], $this->acme['division']);

        $builder = Builder::create(['name' => 'New Builder']);
        $team = Team::create(['name' => 'New Team']);
        $tag = Tag::create(['name' => 'New Tag']);

        $this->assertSame($this->acme['organization']->id, $builder->organization_id);
        $this->assertSame($this->acme['division']->id, $team->company_id);
        $this->assertSame($this->acme['business']->id, $tag->business_id);
    }

    public function test_switching_to_a_division_you_dont_belong_to_falls_back_to_your_own(): void
    {
        $this->actingAs($this->acme['admin'])
            ->withSession(['company_id' => $this->rival['division']->id])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSessionHas('company_id', $this->acme['division']->id);
    }

    public function test_the_same_builder_name_can_exist_in_two_organizations(): void
    {
        tenancy()->setDivision($this->rival['division']);

        Builder::create(['name' => 'Acme Builder', 'email' => $this->acme['builder']->email]);

        $this->assertSame(2, Builder::everywhere()->where('name', 'Acme Builder')->count());
    }

    public function test_lot_import_creates_builders_inside_the_divisions_organization(): void
    {
        app(LotImportService::class)->validateAndImport([[
            'company_id' => $this->rival['division']->id,
            'builder' => 'Imported Builder',
            'subdivision' => 'Imported Subdivision',
            'lot_number' => '42',
            'status' => 'pending',
            'task_name' => 'Rival Task Type',
            'is_extra' => false,
            'phase' => '1',
            'address' => '1 Main St',
        ]]);

        $builder = Builder::everywhere()->where('name', 'Imported Builder')->first();
        $this->assertNotNull($builder);
        $this->assertSame($this->rival['organization']->id, $builder->organization_id);
    }
}
