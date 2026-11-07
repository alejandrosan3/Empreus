<?php

namespace App\Livewire\Teams;

use Livewire\Component;
use App\Models\Team;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\DB;

class CreateTeam extends Component
{
    public string $name = '';
    public array $workerIds = [];
    public $workers;

    protected $rules = [
        'name' => 'required|string|max:255',
        'workerIds' => 'array',
        'workerIds.*' => 'integer',
    ];

    public function mount()
    {
        $this->workers = User::workersInDivision()
            ->orderBy('name')
            ->get();
    }

    public function save()
    {
        $this->authorize('edit-crews');

        $validated = $this->validate();

        DB::transaction(function () use ($validated) {
            $team = Team::create([
                'name' => $validated['name'],
            ]);

            if (! empty($validated['workerIds'])) {
                // Only people who work in this division can join its teams.
                $team->workers()->attach(
                    User::workersInDivision()->whereIn('users.id', $validated['workerIds'])->pluck('users.id')
                );
            }
        });

        Flux::toast(
            text: 'Team created successfully.',
            heading: 'Success!',
            variant: 'success'
        );

        return redirect()->route('teams');
    }

    public function render()
    {
        return view('livewire.teams.create-team')->layout('layouts.app');
    }
}
