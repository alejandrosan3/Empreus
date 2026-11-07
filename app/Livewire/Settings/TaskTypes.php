<?php

namespace App\Livewire\Settings;

use App\Livewire\Settings\Concerns\ManagesBusinessLists;
use App\Models\Builder;
use App\Models\TaskType;
use App\Models\TaskTypePrice;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Settings › Task types & prices: the kinds of tasks this business does,
 * their default price and any builder-specific prices.
 */
#[Layout('layouts.app')]
class TaskTypes extends Component
{
    use ManagesBusinessLists;

    public string $search = '';

    public bool $showArchived = false;

    // Add
    public string $newName = '';

    public ?string $newPrice = null;

    // Edit modal
    #[Locked]
    public ?int $editingId = null;

    public string $name = '';

    public ?string $description = null;

    public ?string $defaultPrice = null;

    public ?int $priceBuilderId = null;

    public ?string $builderPrice = null;

    #[Computed]
    public function taskTypes(): Collection
    {
        return TaskType::query()
            ->when($this->showArchived, fn ($q) => $q->onlyTrashed())
            ->when($this->search !== '', fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
            ->withCount('prices')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function editing(): ?TaskType
    {
        return $this->editingId ? TaskType::withTrashed()->with('prices.builder')->find($this->editingId) : null;
    }

    #[Computed]
    public function builders(): Collection
    {
        return Builder::orderBy('name')->get(['id', 'name']);
    }

    private function seesMoney(): bool
    {
        return auth()->user()->can('see-money');
    }

    public function add(): void
    {
        $this->ensureCanChange();
        $this->validate([
            'newName' => 'required|string|max:255',
            'newPrice' => 'nullable|numeric|min:0',
        ], ['newName.required' => 'Give the task type a name.']);

        if (TaskType::withTrashed()->where('name', trim($this->newName))->exists()) {
            $this->refuse('There is already a task type with that name (it may be archived).', 'newName');

            return;
        }

        TaskType::create([
            'name' => trim($this->newName),
            'description' => trim($this->newName),
            'default_price' => $this->seesMoney() && $this->newPrice !== '' ? $this->newPrice : null,
        ]);

        $this->reset('newName', 'newPrice');
        unset($this->taskTypes);
        $this->changed('Task type added.');
    }

    public function edit(int $id): void
    {
        $type = TaskType::withTrashed()->findOrFail($id);
        $this->editingId = $type->id;
        $this->name = $type->name;
        $this->description = $type->description;
        $this->defaultPrice = $type->default_price;
        $this->reset('priceBuilderId', 'builderPrice');
        $this->resetValidation();
        Flux::modal('task-type')->show();
    }

    public function save(): void
    {
        $this->ensureCanChange();
        $type = TaskType::withTrashed()->findOrFail($this->editingId);
        $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'defaultPrice' => 'nullable|numeric|min:0',
        ]);

        if (TaskType::withTrashed()->where('name', trim($this->name))->whereKeyNot($type->id)->exists()) {
            $this->refuse('Another task type already has that name.', 'name');

            return;
        }

        $type->fill(['name' => trim($this->name), 'description' => $this->description]);
        if ($this->seesMoney()) {
            $type->default_price = $this->defaultPrice === '' ? null : $this->defaultPrice;
        }
        $type->save();

        unset($this->taskTypes);
        Flux::modal('task-type')->close();
        $this->changed('Saved. Existing tasks keep their price; new tasks use the new one.');
    }

    public function setBuilderPrice(): void
    {
        $this->ensureCanChange();
        abort_unless($this->seesMoney(), 403);
        $this->validate([
            'priceBuilderId' => 'required|integer',
            'builderPrice' => 'required|numeric|min:0',
        ], ['priceBuilderId.required' => 'Pick a builder.', 'builderPrice.required' => 'Enter a price.']);

        $type = TaskType::withTrashed()->findOrFail($this->editingId);
        $builder = Builder::findOrFail($this->priceBuilderId); // only this organization's builders

        TaskTypePrice::updateOrCreate(
            ['task_type_id' => $type->id, 'builder_id' => $builder->id],
            ['price' => $this->builderPrice],
        );

        $this->reset('priceBuilderId', 'builderPrice');
        unset($this->editing, $this->taskTypes);
        $this->changed("{$builder->name} now pays ".money($type->priceFor($builder->id))." for {$type->name}.");
    }

    public function removeBuilderPrice(int $priceId): void
    {
        $this->ensureCanChange();
        abort_unless($this->seesMoney(), 403);
        $type = TaskType::withTrashed()->findOrFail($this->editingId); // only this business's task types
        TaskTypePrice::where('task_type_id', $type->id)->whereKey($priceId)->delete();
        unset($this->editing, $this->taskTypes);
        $this->changed('Builder price removed. They pay the default price again.');
    }

    public function archive(int $id): void
    {
        $this->ensureCanChange('full');
        TaskType::findOrFail($id)->delete();
        unset($this->taskTypes);
        $this->changed('Archived. Existing tasks keep it; it no longer shows when adding tasks.');
    }

    public function restore(int $id): void
    {
        $this->ensureCanChange('full');
        TaskType::onlyTrashed()->findOrFail($id)->restore();
        unset($this->taskTypes);
        $this->changed('Restored.');
    }

    public function render()
    {
        return view('livewire.settings.task-types');
    }
}
