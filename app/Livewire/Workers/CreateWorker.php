<?php

namespace App\Livewire\Workers;

use App\Models\Role;
use App\Models\RoleAssignment;
use App\Services\PeopleService;
use Flux\Flux;
use Livewire\Component;

class CreateWorker extends Component
{
    public string $name = '';
    public string $email = '';

    protected $rules = [
        'name'  => 'required|string|max:255',
        'email' => 'required|email|unique:users,email|max:255',
    ];
    
     public function save()
    {
        $this->authorize('edit-crews');
        $validated = $this->validate();

        try {
            // New workers get the organization's Worker role in this division and an
            // email to choose their own password.
            $role = Role::where('organization_id', tenancy()->organizationId())
                ->where('assignable', true)
                ->orderByRaw("`key` = 'worker' desc")
                ->firstOrFail();

            app(PeopleService::class)->invite(
                $validated['name'],
                $validated['email'],
                $role,
                [['scope_type' => RoleAssignment::DIVISION, 'scope_id' => tenancy()->divisionId()]],
                auth()->user(),
            );

            Flux::toast(
                text:    'Worker added. They will get an email to choose their password.',
                heading: 'Success!',
                variant: 'success'
            );

            return redirect()->route('workers');
        } catch (\Throwable $e) {
            Flux::toast(
                text:    'Could not create worker.',
                heading: 'Oops!',
                variant: 'warning'
            );

            return redirect()->back()->withInput();
        }
    }

    public function render()
    {
        return view('livewire.workers.create-worker')->layout('layouts.app')    ;
    }
}
