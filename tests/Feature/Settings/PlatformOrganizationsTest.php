<?php

namespace Tests\Feature\Settings;

use App\Livewire\Settings\Organizations;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\Invitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\Concerns\BuildsPlaces;
use Tests\TestCase;

class PlatformOrganizationsTest extends TestCase
{
    use RefreshDatabase, BuildsPlaces;

    public function test_only_platform_admins_open_the_page(): void
    {
        $place = $this->makePlace('A');
        $this->workIn($place['admin'], $place['division']);
        $this->get(route('platform.organizations'))->assertForbidden();

        $platform = User::factory()->create(['is_platform_admin' => true]);
        $this->workIn($platform, $place['division']);
        $this->get(route('platform.organizations'))->assertOk()->assertSee('A Org');
    }

    public function test_create_an_organization_with_its_owner(): void
    {
        Notification::fake();
        $place = $this->makePlace('A');
        $platform = User::factory()->create(['is_platform_admin' => true]);
        $this->workIn($platform, $place['division']);

        Livewire::test(Organizations::class)
            ->set('organizationName', 'Acme Painting')
            ->set('businessName', 'Painting')
            ->set('divisionName', 'Orlando')
            ->set('ownerName', 'Pat')
            ->set('ownerEmail', 'pat@acme.test')
            ->call('create')
            ->assertHasNoErrors();

        $acme = Organization::where('name', 'Acme Painting')->firstOrFail();
        $pat = User::where('email', 'pat@acme.test')->firstOrFail();
        $this->assertTrue($pat->isOwnerOf($acme));
        $this->assertSame(['Orlando'], $acme->divisions()->pluck('companies.name')->all());
        $this->assertTrue($acme->roles()->where('key', 'scheduler')->exists());
        $this->assertFalse($pat->canAccessDivision($place['division']));
        Notification::assertSentTo($pat, Invitation::class);
    }
}
