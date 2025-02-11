<?php

use App\Http\Middleware\SetCurrentCompany;
use App\Livewire\Builders\{CreateBuilder, ListBuilder};
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Livewire\Lot\LotProgress;
use App\Livewire\Pages\LotDetails;
use App\Livewire\Pages\TaskDetails;



Route::get('/', function () {
    return redirect()->route('login'); // Redirect to login or a public landing page
});

Route::post('/logout', function () {
    Auth::logout();
    session()->invalidate();
    session()->regenerateToken();

    return redirect('/');
})->name('logout');

Route::middleware(['auth', 'verified', SetCurrentCompany::class])
    ->group(function () {
        Route::redirect('/', 'dashboard', 301);

        Route::view('dashboard', 'dashboard')
            ->name('dashboard')
            ->defaults('label', 'Dashboard')
            ->defaults('icon', 'home');

        Route::view('profile', 'profile')
            ->name('profile')
            ->defaults('label', 'Profile')
            ->defaults('icon', 'user-circle');

        Route::get('builders', ListBuilder::class)
            ->name('builders')
            ->defaults('label', 'Builders')
            ->defaults('icon', 'building-office-2');

        Route::get('builders/create', CreateBuilder::class)
            ->name('builders.create')
            ->defaults('label', 'Add Builder')
            ->defaults('icon', 'building-office');

        Route::get('lot-progress', LotProgress::class)
            ->name('lot-progress')
            ->defaults('label', 'Lots Progress')
            ->defaults('icon', 'clipboard-document-check');

        Route::get('/lots/{lot_id}/details', LotDetails::class)
            ->name('lot-details')
            ->defaults('label', 'Lot Details view example')
            ->defaults('icon', 'clipboard-document-check');

        Route::get('/tasks/{task_id}/details', TaskDetails::class)
            ->name('task-details')
            ->defaults('label', 'Task Details view example')
            ->defaults('icon', 'clipboard-document-check');
});

require __DIR__.'/auth.php';
