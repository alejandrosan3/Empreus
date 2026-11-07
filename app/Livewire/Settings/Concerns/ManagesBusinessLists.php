<?php

namespace App\Livewire\Settings\Concerns;

use App\Services\StructureService;
use App\Support\ListCache;
use Flux\Flux;
use InvalidArgumentException;

/**
 * Shared rules for the business-wide lists (task types, statuses, tags):
 * anyone with Settings can look; changing needs Edit in Settings for the whole
 * business (or organization); archiving needs Full.
 */
trait ManagesBusinessLists
{
    public function canChange(string $level = 'edit'): bool
    {
        $business = tenancy()->business();

        return $business && app(StructureService::class)->can(auth()->user(), $business, $level);
    }

    protected function ensureCanChange(string $level = 'edit'): void
    {
        $this->authorize($level.'-settings');
        abort_unless($this->canChange($level), 403, 'Changing this list needs Settings access for the whole business.');
    }

    protected function changed(string $message): void
    {
        ListCache::forgetBusiness(tenancy()->business());
        Flux::toast(text: $message, variant: 'success');
    }

    protected function refuse(InvalidArgumentException|string $error, string $field): void
    {
        $this->addError($field, $error instanceof InvalidArgumentException ? $error->getMessage() : $error);
    }
}
