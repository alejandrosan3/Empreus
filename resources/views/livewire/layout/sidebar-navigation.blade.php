@php
use Illuminate\Support\Facades\Route;

$routes = collect(Route::getRoutes()->getRoutesByName())
    ->filter(fn($route) => isset($route->defaults['label'])); // Only include routes with 'label' in defaults
@endphp

<flux:navlist variant="outline">
    <flux:navlist.item icon="{{ Route::getRoutes()->getByName('dashboard')->defaults['icon'] }}" href="{{ route('dashboard') }}" :current="request()->is('dashboard')">{{ Route::getRoutes()->getByName('dashboard')->defaults['label'] }}</flux:navlist.item>

    @if(auth()->user()->role !== 'worker')
        <flux:navlist.group expandable heading="Directory" class="hidden lg:grid">
            <flux:navlist.item icon="{{ Route::getRoutes()->getByName('builders')->defaults['icon'] }}" href="{{ route('builders') }}" :current="request()->is('builders')">{{ Route::getRoutes()->getByName('builders')->defaults['label'] }}</flux:navlist.item>
            <flux:navlist.item icon="{{ Route::getRoutes()->getByName('subdivisions')->defaults['icon'] }}" href="{{ route('subdivisions') }}" :current="request()->is('subdivisions')">{{ Route::getRoutes()->getByName('subdivisions')->defaults['label'] }}</flux:navlist.item>
            <flux:navlist.item icon="{{ Route::getRoutes()->getByName('supervisors')->defaults['icon'] }}" href="{{ route('supervisors') }}" :current="request()->is('supervisors')">{{ Route::getRoutes()->getByName('supervisors')->defaults['label'] }}</flux:navlist.item>
            <flux:navlist.item icon="{{ Route::getRoutes()->getByName('workers')->defaults['icon'] }}" href="{{ route('workers') }}" :current="request()->is('workers')">{{ Route::getRoutes()->getByName('workers')->defaults['label'] }}</flux:navlist.item>
        </flux:navlist.group>

        <flux:navlist.item icon="{{ Route::getRoutes()->getByName('lot-progress')->defaults['icon'] }}" href="{{ route('lot-progress') }}" :current="request()->is('lot-progress')">{{ Route::getRoutes()->getByName('lot-progress')->defaults['label'] }}</flux:navlist.item>
        <flux:navlist.item icon="{{ Route::getRoutes()->getByName('task-progress')->defaults['icon'] }}" href="{{ route('task-progress') }}" :current="request()->is('task-progress')">{{ Route::getRoutes()->getByName('task-progress')->defaults['label'] }}</flux:navlist.item>
        <flux:navlist.item icon="{{ Route::getRoutes()->getByName('task-board')->defaults['icon'] }}" href="{{ route('task-board') }}" :current="request()->is('task-board')">{{ Route::getRoutes()->getByName('task-board')->defaults['label'] }}</flux:navlist.item>
        <flux:navlist.item icon="{{ Route::getRoutes()->getByName('statistics')->defaults['icon'] }}" href="{{ route('statistics') }}" :current="request()->is('statistics')">{{ Route::getRoutes()->getByName('statistics')->defaults['label'] }}</flux:navlist.item>
        <flux:navlist.item icon="{{ Route::getRoutes()->getByName('projections')->defaults['icon'] }}" href="{{ route('projections') }}" :current="request()->is('projections')">{{ Route::getRoutes()->getByName('projections')->defaults['label'] }}</flux:navlist.item>
        <flux:navlist.item icon="{{ Route::getRoutes()->getByName('task-calendar')->defaults['icon'] }}" href="{{ route('task-calendar') }}" :current="request()->is('task-calendar')">{{ Route::getRoutes()->getByName('task-calendar')->defaults['label'] }}</flux:navlist.item>
    @endif
</flux:navlist>
