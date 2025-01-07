<?php

namespace App\Livewire\Layout;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Session;

class MultiaccountsDropdown extends Component
{
    public $accounts;
    
    #[Session(key: 'selected_account_id')] 
    public $selectedAccountId;

    public function mount()
    {
        $this->accounts = Auth::user()->accounts;
        
        // If no account is selected in the session, default to the first account
        if (!$this->selectedAccountId) {
            $this->selectedAccountId = $this->accounts->first()->id ?? null;
        }
        
    }

    public function render()
    {  
        return view('livewire.layout.multiaccounts-dropdown');
    }
}
