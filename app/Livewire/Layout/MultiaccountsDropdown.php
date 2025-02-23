<?php

namespace App\Livewire\Layout;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Session;

class MultiaccountsDropdown extends Component
{
    public $accounts;

    #[Session(key: 'company_id')]
    public $selectedAccountId;

    public function mount()
    {
        $this->accounts = Auth::user()->companies;
    }

    public function render()
    {
        return view('livewire.layout.multiaccounts-dropdown');
    }

    public function updatedSelectedAccountId($value)
    {
        $this->dispatch('company-updated', $value);
    }
}
