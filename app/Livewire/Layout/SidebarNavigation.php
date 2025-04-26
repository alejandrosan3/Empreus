<?php

namespace App\Livewire\Layout;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class SidebarNavigation extends Component
{
    public function render()
    {
        return view('livewire.layout.sidebar-navigation');
    }
}
