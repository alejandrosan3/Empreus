<?php

namespace App\Livewire\Builders;

use Livewire\Component;
use App\Models\Builder;

use Flux\Flux;

class CreateBuilder extends Component
{
    public $name;
    public $email;
    public $address;
    public $phone;

    protected $rules = [
        'name' => 'required|string|max:255',
        'email' => 'nullable|email|unique:builders,email|max:255',
        'address' => 'nullable|string|max:255',
        'phone' => 'nullable|numeric|digits:10',
    ];

    public function save()
    {
        $validated = $this->validate();

        try {
            Builder::create($validated);
            Flux::toast(
                text:'Builder created successfully.',
                heading:'Success!',
                variant:'success'
            );
        } catch (\Illuminate\Database\QueryException $e) {
            Flux::toast(
                text:'A builder with that name already exists.',
                heading:'Duplicate!',
                variant:'warning'
            );
            return;
        }

        return redirect()->route('builders');
    }

    public function render()    
    {
        return view('livewire.builders.create')->layout('layouts.app');
    }
}
