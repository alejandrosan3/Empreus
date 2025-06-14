@php
use Illuminate\Support\Facades\Route;

$routes = collect(Route::getRoutes()->getRoutesByName())
    ->filter(fn($route) => isset($route->defaults['label'])); // Only include routes with 'label' in defaults
@endphp

<flux:navlist variant="outline">
    <flux:navlist.item icon="{{ Route::getRoutes()->getByName('dashboard')->defaults['icon'] }}" href="{{ route('dashboard') }}" :current="request()->is('dashboard')" wire:navigate>{{ Route::getRoutes()->getByName('dashboard')->defaults['label'] }}</flux:navlist.item>

    @if(auth()->user()->role !== 'worker')
        <flux:navlist.group expandable heading="Directory" class="hidden lg:grid">
            <flux:navlist.item icon="{{ Route::getRoutes()->getByName('builders')->defaults['icon'] }}" href="{{ route('builders') }}" :current="request()->is('builders')" wire:navigate>{{ Route::getRoutes()->getByName('builders')->defaults['label'] }}</flux:navlist.item>
            <flux:navlist.item icon="{{ Route::getRoutes()->getByName('subdivisions')->defaults['icon'] }}" href="{{ route('subdivisions') }}" :current="request()->is('subdivisions')" wire:navigate>{{ Route::getRoutes()->getByName('subdivisions')->defaults['label'] }}</flux:navlist.item>
            <flux:navlist.item icon="{{ Route::getRoutes()->getByName('supervisors')->defaults['icon'] }}" href="{{ route('supervisors') }}" :current="request()->is('supervisors')" wire:navigate>{{ Route::getRoutes()->getByName('supervisors')->defaults['label'] }}</flux:navlist.item>
            <flux:navlist.item icon="{{ Route::getRoutes()->getByName('workers')->defaults['icon'] }}" href="{{ route('workers') }}" :current="request()->is('workers')" wire:navigate>{{ Route::getRoutes()->getByName('workers')->defaults['label'] }}</flux:navlist.item>
        </flux:navlist.group>

        <flux:navlist.item icon="{{ Route::getRoutes()->getByName('lot-progress')->defaults['icon'] }}" href="{{ route('lot-progress') }}" :current="request()->is('lot-progress')" wire:navigate>{{ Route::getRoutes()->getByName('lot-progress')->defaults['label'] }}</flux:navlist.item>
        <flux:navlist.item icon="{{ Route::getRoutes()->getByName('task-progress')->defaults['icon'] }}" href="{{ route('task-progress') }}" :current="request()->is('task-progress')" wire:navigate>{{ Route::getRoutes()->getByName('task-progress')->defaults['label'] }}</flux:navlist.item>
        <flux:navlist.item icon="{{ Route::getRoutes()->getByName('task-board')->defaults['icon'] }}" href="{{ route('task-board') }}" :current="request()->is('task-board')" wire:navigate>{{ Route::getRoutes()->getByName('task-board')->defaults['label'] }}</flux:navlist.item>
    @endif
</flux:navlist>
