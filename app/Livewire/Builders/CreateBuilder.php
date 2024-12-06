<?php

namespace App\Livewire\Builders;

use Livewire\Component;
use App\Models\Builder;

class CreateBuilder extends Component
{
    public $name;
    public $email;
    public $address;
    public $phone;

    protected $rules = [
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:builders,email|max:255',
        'address' => 'nullable|string|max:255',
        'phone' => 'nullable|numeric|digits:10',
    ];

    public function store()
    {
        $this->validate();

        Builder::create([
            'name'  => $this->name,
            'email' => $this->email,
            'address' => $this->address,
            'phone' => $this->phone,
        ]);

        session()->flash('message', 'Builder created successfully.');

        return redirect()->route('builders');
    }

    public function render()
    {
        return view('livewire.builders.create')->extends('builders');
    }
}
