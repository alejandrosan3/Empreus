<?php

namespace App\Livewire\Settings;

use App\Livewire\Settings\Concerns\ManagesBusinessLists;
use App\Models\StatusType;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Settings › Statuses: each business can rename the statuses and pick their colors.
 * The statuses themselves (and what they mean to the app) are the same everywhere.
 */
#[Layout('layouts.app')]
class Statuses extends Component
{
    use ManagesBusinessLists;

    /** @var array<string, array{name: string, color: string}> identifier => choice */
    public array $statuses = [];

    public function mount(): void
    {
        foreach ($this->statusTypes as $status) {
            $this->statuses[$status->identifier] = ['name' => $status->name, 'color' => $status->color];
        }
    }

    #[Computed]
    public function statusTypes(): Collection
    {
        return StatusType::orderBy('id')->get();
    }

    public function save(): void
    {
        $this->ensureCanChange();
        $identifiers = $this->statusTypes->pluck('identifier')->all();

        $this->validate([
            'statuses' => 'array',
            'statuses.*.name' => 'required|string|max:50',
            'statuses.*.color' => ['required', Rule::in(StatusType::COLORS)],
        ], ['statuses.*.name.required' => 'Every status needs a name.']);

        $overrides = [];
        foreach ($this->statusTypes as $status) {
            $choice = $this->statuses[$status->identifier] ?? null;
            if (! $choice || ! in_array($status->identifier, $identifiers, true)) {
                continue;
            }
            $entry = [];
            if (trim($choice['name']) !== $status->originalName()) {
                $entry['name'] = trim($choice['name']);
            }
            if ($choice['color'] !== $status->originalColor()) {
                $entry['color'] = $choice['color'];
            }
            if ($entry) {
                $overrides[$status->identifier] = $entry;
            }
        }

        $business = tenancy()->business();
        $business->update(['settings' => array_merge($business->settings ?? [], ['statuses' => $overrides])]);

        $this->changed('Saved for '.$business->name.'.');
    }

    public function resetToDefaults(): void
    {
        $this->ensureCanChange();
        $business = tenancy()->business();
        $settings = $business->settings ?? [];
        unset($settings['statuses']);
        $business->update(['settings' => $settings]);

        foreach ($this->statusTypes as $status) {
            $this->statuses[$status->identifier] = ['name' => $status->originalName(), 'color' => $status->originalColor()];
        }
        $this->changed('Statuses are back to their standard names and colors.');
    }

    public function render()
    {
        return view('livewire.settings.statuses');
    }
}
