<?php

namespace App\Services;

use App\Models\ColumnPreference;
use App\Models\User;

class ColumnPreferenceService
{
    public function getColumns(User $user, string $context): array
    {
        $preference = $user->columnPreferences()
            ->where('context', $context)
            ->first();

        if ($preference) {
            return $preference->columns ?? [];
        }

        $configKey = str_replace('_', '-', $context) . '.columns';
        return config($configKey, []);
    }


    /**
     * Persist the user's column preferences in the database (no cache).
     */
    public function setColumns(User $user, string $context, array $columns): void
    {
        ColumnPreference::updateOrCreate(
            ['user_id' => $user->id, 'context' => $context],
            ['columns' => $columns]
        );
    }
}
