<?php

namespace Tests\Feature\Permissions;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\BuildsPlaces;
use Tests\TestCase;

/**
 * The lot import API used to accept files from anyone. It now needs a token and
 * Edit access to lots in each division of the file.
 */
class LotImportApiTest extends TestCase
{
    use RefreshDatabase, BuildsPlaces;

    private array $place;

    protected function setUp(): void
    {
        parent::setUp();
        $this->place = $this->makePlace('Acme');
        tenancy()->forget();
    }

    private function file(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('lots.json', json_encode([[
            'company_id' => $this->place['division']->id,
            'builder' => 'API Builder',
            'subdivision' => 'API Subdivision',
            'lot_number' => '7',
            'status' => 'pending',
            'task_name' => 'Acme Task Type',
            'is_extra' => false,
            'phase' => '1',
            'address' => '1 Main St',
        ]]));
    }

    public function test_anonymous_imports_are_refused(): void
    {
        $this->postJson(route('lots.import'), ['file' => $this->file()])->assertUnauthorized();
    }

    public function test_people_who_cannot_edit_lots_there_are_refused(): void
    {
        Sanctum::actingAs($this->makePerson($this->place['division'], 'viewer'));

        $this->post(route('lots.import'), ['file' => $this->file()], ['Accept' => 'application/json'])->assertForbidden();
    }

    public function test_admins_can_import(): void
    {
        Sanctum::actingAs($this->place['admin']);

        $this->post(route('lots.import'), ['file' => $this->file()], ['Accept' => 'application/json'])->assertOk();
        $this->assertTrue(\App\Models\Builder::everywhere()->where('name', 'API Builder')->exists());
    }
}
