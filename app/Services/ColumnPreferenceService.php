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

        $configKey = str_replace('_', '-', $context) . '.columns';
        $default = config($configKey, []);

        // If we have saved columns, merge them with the current config,
        // so new columns (like Tags) appear automatically.
        if ($preference && !empty($preference->columns)) {
            return $this->mergeColumns($preference->columns, $default);
        }

        return $default;
    }

    /**
     * Merge saved columns with current defaults:
     * - Keep user order and settings for existing columns
     * - Append any new columns from the default config to the end
     */
    private function mergeColumns(array $saved, array $default): array
    {
        // Map saved values to quick lookup
        $savedKeys = array_map(fn($c) => $c['value'] ?? null, $saved);
        $savedKeys = array_filter($savedKeys);

        $merged = $saved;

        // Append any default columns not present in saved prefs
        foreach ($default as $defCol) {
            $key = $defCol['value'] ?? null;
            if ($key && !in_array($key, $savedKeys, true)) {
                $merged[] = $defCol;
            }
        }

        return $merged;
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
