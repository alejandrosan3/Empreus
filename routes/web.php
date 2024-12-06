<?php

use App\Http\Controllers\BuilderController;
use App\Livewire\Builders\{CreateBuilder,ListBuilder};
use Illuminate\Support\Facades\Route;

Route::redirect('/', 'dashboard', 301)
    ->middleware(['auth', 'verified']);

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth', 'verified'])
    ->name('profile');

Route::get('builders', ListBuilder::class)
    ->middleware(['auth', 'verified'])
    ->name('builders');

Route::get('builders/create', CreateBuilder::class)
    ->middleware(['auth', 'verified'])
    ->name('builders.create');

require __DIR__.'/auth.php';
