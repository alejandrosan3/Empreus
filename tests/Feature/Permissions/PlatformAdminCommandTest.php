<?php

namespace Tests\Feature\Permissions;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_grant_and_remove_platform_admin(): void
    {
        $user = User::factory()->create(['email' => 'boss@example.com']);

        $this->artisan('empreus:platform-admin', ['email' => 'boss@example.com'])->assertSuccessful();
        $this->assertTrue($user->fresh()->isPlatformAdmin());

        $this->artisan('empreus:platform-admin', ['email' => 'boss@example.com', '--remove' => true])->assertSuccessful();
        $this->assertFalse($user->fresh()->isPlatformAdmin());

        $this->artisan('empreus:platform-admin', ['email' => 'nobody@example.com'])->assertFailed();
    }
}
