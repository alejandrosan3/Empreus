@php
use Illuminate\Support\Facades\Route;

$routes = collect(Route::getRoutes()->getRoutesByName())
    ->filter(fn($route) => isset($route->defaults['label'])); // Only include routes with 'label' in defaults
@endphp

<flux:navlist variant="outline">
    <flux:navlist.item icon="{{ Route::getRoutes()->getByName('dashboard')->defaults['icon'] }}" href="{{ route('dashboard') }}" :current="request()->is('dashboard')">{{ Route::getRoutes()->getByName('dashboard')->defaults['label'] }}</flux:navlist.item>
    <flux:navlist.item icon="{{ Route::getRoutes()->getByName('builders')->defaults['icon'] }}" href="{{ route('builders') }}" :current="request()->is('builders')">{{ Route::getRoutes()->getByName('builders')->defaults['label'] }}</flux:navlist.item>
    <flux:navlist.item icon="{{ Route::getRoutes()->getByName('lot-progress')->defaults['icon'] }}" href="{{ route('lot-progress') }}" :current="request()->is('lot-progress')">{{ Route::getRoutes()->getByName('lot-progress')->defaults['label'] }}</flux:navlist.item>
    <flux:navlist.item icon="{{ Route::getRoutes()->getByName('content-details')->defaults['icon'] }}" href="{{ route('content-details') }}" :current="request()->is('content-details')">{{ Route::getRoutes()->getByName('content-details')->defaults['label'] }}</flux:navlist.item>
</flux:navlist>