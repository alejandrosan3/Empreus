<?php

namespace App\Livewire\Partials;

use Livewire\Component;

class Cards extends Component
{
    public array  $items = []; 
    public string $mode  = 'view';

    public function render()
    {
        return view('livewire.partials.cards');
    }
}
