<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Platform admins run the whole platform (all organizations, Telescope, Horizon,
 * the role library). There is no screen to make one, on purpose.
 */
class PlatformAdmin extends Command
{
    protected $signature = 'empreus:platform-admin {email : The person\'s login email} {--remove : Take platform admin away}';

    protected $description = 'Make someone a platform admin, or remove it';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();
        if (! $user) {
            $this->error('Nobody has that email.');

            return self::FAILURE;
        }

        $user->forceFill(['is_platform_admin' => ! $this->option('remove')])->save();
        $this->info($this->option('remove')
            ? "{$user->name} is no longer a platform admin."
            : "{$user->name} is now a platform admin.");

        return self::SUCCESS;
    }
}
