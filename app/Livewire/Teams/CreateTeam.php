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
        'workerIds.*' => 'exists:users,id',
    ];

    public function mount()
    {
        $companyId = session('company_id');

        $this->workers = User::where('role', 'worker')
            ->whereHas('companies', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->orderBy('name')
            ->get();
    }

    public function save()
    {
        $validated = $this->validate();

        DB::transaction(function () use ($validated) {
            $team = Team::create([
                'name' => $validated['name'],
            ]);

            if (! empty($validated['workerIds'])) {
                $team->workers()->attach($validated['workerIds']);
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
