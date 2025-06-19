<?php

namespace App\Livewire\Layout;

use Livewire\Component;
use Illuminate\Support\Collection;
use Flux\Flux;

class AvatarStack extends Component
{
    public Collection $users;

    // config knobs you can override when including the component
    public $maxVisible;
    public $total;
    public $avSize;     

    /**
     * Accept the worker list (and optional display settings)
     * when the component is mounted.
     */
    public function mount(Collection $users, int $maxVisible = 3, ?string $avSize=null ):void
    {
        $this->users      = $users;
        $this->maxVisible = $maxVisible;
        $this->avSize     = $avSize;
    }

    public function render()
    {
        return view('livewire.layout.avatar-stack');
    }
}
