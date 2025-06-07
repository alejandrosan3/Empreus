<?php

namespace App\Livewire\Search;

use Livewire\Component;
use Livewire\Attributes\Computed;
use App\Models\Task;

class Results extends Component
{
    public string $query = '';

    public function mount(string $query = ''): void
    {
        $this->query = $query;
    }

    #[Computed]
    public function results()
    {
        if ($this->query === '') {
            return collect();
        }

        return Task::search($this->query)->get();
    }

    public function render()
    {
        return view('livewire.search.results')
            ->layout('layouts.app', [
                'showSearch' => true,
            ]);
    }
}
