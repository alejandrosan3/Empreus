<?php

namespace App\Livewire\Lot;

use Livewire\Component;
use App\Models\Lot;

class AddLotForm extends Component
{
    public $lot_number, $address, $builder_id;

    protected $rules = [
        'lot_number' => 'required|numeric|unique:lots,lot_number',
        'address'    => 'nullable|string',
        'builder_id' => 'required|exists:builders,id',
    ];

    public function save()
    {
        $this->validate();

        Lot::create([
            'lot_number' => $this->lot_number,
            'address'    => $this->address,
            'builder_id' => $this->builder_id,
        ]);

        $this->dispatchBrowserEvent('close-modal');
        $this->emitUp('lotAdded');
    }

    public function render()
    {
        return view('livewire.lot.add-lot-form');
    }
}
