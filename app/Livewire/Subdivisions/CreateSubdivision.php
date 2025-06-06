<?php

namespace App\Livewire\Subdivisions;

use Livewire\Component;
use App\Models\Subdivision;
use App\Models\Lot;

use Flux\Flux;

class CreateSubdivision extends Component
{
    public $name;

    protected $rules = [
        'name' => 'required|string|max:255',
    ];

    public function save()
    {
        $validated = $this->validate();

        try {
            Lot::create([
                'company_id'   => session('company_id'),
                'subdivision'  => $this->name,
            ]);
            Flux::toast(
                text:'Subdivision created successfully.',
                heading:'Success!',
                variant:'success'
            );
        } catch (\Illuminate\Database\QueryException $e) {
            Flux::toast(
                text:' A Subdivision with that name already exists.',
                heading:'Duplicate!',
                variant:'warning'
            );
            return;
        }

        return redirect()->route('subdivisions');
    }
    
    public function render()
    {
        return view('livewire.subdivisions.create-subdivision')->layout('layouts.app');
    }
}
