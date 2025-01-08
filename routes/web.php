<?php

use App\Http\Middleware\SetCurrentCompany;
use App\Livewire\Builders\{CreateBuilder, ListBuilder};
use Illuminate\Support\Facades\Route;
use App\Livewire\Lot\LotProgress;

Route::get('/', function () {
    return redirect()->route('login'); // Redirect to login or a public landing page
});

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
});

require __DIR__.'/auth.php';
