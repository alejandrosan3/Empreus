<?php

namespace App\Livewire\Supervisors;

use Livewire\Component;
use App\Models\Supervisor;

use Flux\Flux;

class CreateSupervisor extends Component
{
    public $name;
    public $email;
    public $phone;

    protected $rules = [
        'name' => 'required|string|max:255',
        'email' => 'nullable|email|unique:builders,email|max:255',
        'phone' => 'nullable|numeric',
    ];

    public function save()
    {
        $validated = $this->validate();

        try {
            Supervisor::create($validated);
            Flux::toast(
                text:'Supervisor created successfully.',
                heading:'Success!',
                variant:'success'
            );
        } catch (\Illuminate\Database\QueryException $e) {
            Flux::toast(
                text:'A supervisor with that name already exists.',
                heading:'Duplicate!',
                variant:'warning'
            );
            return;
        }

        return redirect()->route('supervisors');
    }
    
    public function render()
    {
        return view('livewire.supervisors.create-supervisor')->layout('layouts.app');
    }
}
