<?php

namespace App\Livewire\Workers;

use App\Models\User;
use App\Models\CompanyUser;
use Flux\Flux;
use Illuminate\Support\Facades\Hash;
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
        $validated = $this->validate();

        try {
            \DB::transaction(function () use ($validated) {
                $user = User::create([
                    'name'              => $validated['name'],
                    'email'             => $validated['email'],
                    'role'              => 'worker',
                    'password'          => Hash::make('demo'),
                    'email_verified_at' => now(),
                ]);

                if ($companyId = session('company_id')) {
                    CompanyUser::create([
                        'user_id'    => $user->id,
                        'company_id' => $companyId,
                    ]);
                }
            });

            Flux::toast(
                text:    'Worker created successfully.',
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
