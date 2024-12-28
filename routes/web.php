<?php

use App\Http\Controllers\BuilderController;
use App\Livewire\Builders\{CreateBuilder,ListBuilder};
use Illuminate\Support\Facades\Route;
use App\Livewire\LotProgress;


Route::redirect('/', 'dashboard', 301)
    ->middleware(['auth', 'verified']);

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard')
    ->defaults('label', 'Dashboard')
    ->defaults('icon', 'home');

Route::view('profile', 'profile')
    ->middleware(['auth', 'verified'])
    ->name('profile')
    ->defaults('label', 'Profile')
    ->defaults('icon', 'user-circle');

Route::get('builders', ListBuilder::class)
    ->middleware(['auth', 'verified'])
    ->name('builders')
    ->defaults('label', 'Builders')
    ->defaults('icon', 'building-office-2');

Route::get('builders/create', CreateBuilder::class)
    ->middleware(['auth', 'verified'])
    ->name('builders.create')
    ->defaults('label', 'Add Builder')
    ->defaults('icon', 'building-office');

Route::get('lot-progress', LotProgress::class)
    ->middleware(['auth', 'verified'])
    ->name('lot-progress')
    ->defaults('label', 'Lots Progress')
    ->defaults('icon', 'clipboard-document-check');

require __DIR__.'/auth.php';
