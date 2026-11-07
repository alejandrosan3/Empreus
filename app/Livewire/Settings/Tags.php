<?php

namespace App\Livewire\Settings;

use App\Livewire\Settings\Concerns\ManagesBusinessLists;
use App\Models\Tag;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Settings › Tags: the labels this business puts on tasks (Walk, Closing…).
 */
#[Layout('layouts.app')]
class Tags extends Component
{
    use ManagesBusinessLists;

    public bool $showArchived = false;

    public string $newName = '';

    public string $newColor = '#0284c7';

    #[Locked]
    public ?int $editingId = null;

    public string $name = '';

    public string $color = '#0284c7';

    #[Computed]
    public function tags(): Collection
    {
        return Tag::query()
            ->when($this->showArchived, fn ($q) => $q->onlyTrashed())
            ->withCount('tasks')
            ->orderBy('name')
            ->get();
    }

    private function tagRules(): array
    {
        return [
            'name' => 'required|string|max:50',
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ];
    }

    public function add(): void
    {
        $this->ensureCanChange();
        $this->validate([
            'newName' => 'required|string|max:50',
            'newColor' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ], ['newName.required' => 'Give the tag a name.', 'newColor.regex' => 'Pick a color.']);

        if (Tag::withTrashed()->where('name', trim($this->newName))->exists()) {
            $this->refuse('There is already a tag with that name (it may be archived).', 'newName');

            return;
        }

        Tag::create(['name' => trim($this->newName), 'color' => $this->newColor]);
        $this->reset('newName');
        unset($this->tags);
        $this->changed('Tag added.');
    }

    public function edit(int $id): void
    {
        $tag = Tag::findOrFail($id);
        [$this->editingId, $this->name, $this->color] = [$tag->id, $tag->name, $tag->color ?: '#0284c7'];
        $this->resetValidation();
        Flux::modal('tag')->show();
    }

    public function save(): void
    {
        $this->ensureCanChange();
        $tag = Tag::findOrFail($this->editingId);
        $this->validate($this->tagRules(), ['color.regex' => 'Pick a color.']);

        if (Tag::withTrashed()->where('name', trim($this->name))->whereKeyNot($tag->id)->exists()) {
            $this->refuse('Another tag already has that name.', 'name');

            return;
        }

        $tag->update(['name' => trim($this->name), 'color' => $this->color]);
        unset($this->tags);
        Flux::modal('tag')->close();
        $this->changed('Saved.');
    }

    public function archive(int $id): void
    {
        $this->ensureCanChange('full');
        Tag::findOrFail($id)->delete();
        unset($this->tags);
        $this->changed('Archived. It disappears from tasks until restored.');
    }

    public function restore(int $id): void
    {
        $this->ensureCanChange('full');
        Tag::onlyTrashed()->findOrFail($id)->restore();
        unset($this->tags);
        $this->changed('Restored, including on the tasks that had it.');
    }

    public function render()
    {
        return view('livewire.settings.tags');
    }
}
