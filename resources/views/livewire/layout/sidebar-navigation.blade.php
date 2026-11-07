@php
use Illuminate\Support\Facades\Route;

// Menu items only show when the person's role can at least view that area.
$item = function (string $name, ?string $pattern = null) {
    $route = Route::getRoutes()->getByName($name);

    return [
        'href' => route($name),
        'icon' => $route->defaults['icon'] ?? null,
        'label' => $route->defaults['label'] ?? $name,
        'current' => request()->is($pattern ?? ltrim(parse_url(route($name), PHP_URL_PATH), '/')),
    ];
};
$directory = collect([
    ['builders', 'view-directory'],
    ['subdivisions', 'view-directory'],
    ['supervisors', 'view-directory'],
    ['workers', 'view-crews'],
    ['teams', 'view-crews'],
])->filter(fn ($entry) => Gate::allows($entry[1]))->map(fn ($entry) => $item($entry[0]));
@endphp

<flux:navlist variant="outline">
    @can('view-dashboard')
        @php($link = $item('dashboard'))
        <flux:navlist.item icon="{{ $link['icon'] }}" href="{{ $link['href'] }}" :current="$link['current']">{{ $link['label'] }}</flux:navlist.item>
    @endcan

    @if($directory->isNotEmpty())
        <flux:navlist.group expandable heading="Directory">
            @foreach($directory as $link)
                <flux:navlist.item icon="{{ $link['icon'] }}" href="{{ $link['href'] }}" :current="$link['current']">{{ $link['label'] }}</flux:navlist.item>
            @endforeach
        </flux:navlist.group>
    @endif

    @can('view-lots')
        @php($link = $item('lot-progress'))
        <flux:navlist.item icon="{{ $link['icon'] }}" href="{{ $link['href'] }}" :current="$link['current']">{{ $link['label'] }}</flux:navlist.item>
    @endcan
    @can('view-tasks')
        @php($link = $item('task-progress'))
        <flux:navlist.item icon="{{ $link['icon'] }}" href="{{ $link['href'] }}" :current="$link['current']">{{ $link['label'] }}</flux:navlist.item>
    @endcan
    @can('view-scheduler')
        @php($link = $item('task-scheduler'))
        <flux:navlist.item icon="{{ $link['icon'] }}" href="{{ $link['href'] }}" :current="$link['current']">{{ $link['label'] }}</flux:navlist.item>
    @endcan

    {{-- Statistics and Projections - WIP
    @can('view-reports') … @endcan
    --}}
</flux:navlist>
