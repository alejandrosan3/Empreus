<?php

namespace App\Livewire\Partials;

use Livewire\Component;

class Cards extends Component
{
    public $items = []; // Array of title-value pairs
    
    public function render()
    {
        return view('livewire.partials.cards');
    }
}
