@props(['heading', 'subheading' => null])

{{-- Shared frame for every Settings page: tabs on the left, the page on the right. --}}
<div class="p-4 lg:p-6">
    <div class="flex flex-col gap-6 md:flex-row">
        <nav class="md:w-56 shrink-0" aria-label="Settings">
            <flux:heading size="lg" class="mb-3">Settings</flux:heading>
            <flux:navlist>
                @foreach (config('settings.tabs') as $tab)
                    @if (Route::has($tab['route']) && (! $tab['ability'] || Gate::allows($tab['ability'])))
                        <flux:navlist.item
                            :icon="$tab['icon']"
                            :href="route($tab['route'])"
                            :current="request()->routeIs($tab['route'].'*')"
                            wire:navigate
                        >{{ $tab['label'] }}</flux:navlist.item>
                    @endif
                @endforeach
            </flux:navlist>
        </nav>

        <section class="flex-1 min-w-0 space-y-6">
            <header>
                <flux:heading size="xl" level="1">{{ $heading }}</flux:heading>
                @if ($subheading)
                    <flux:subheading>{{ $subheading }}</flux:subheading>
                @endif
            </header>

            {{ $slot }}
        </section>
    </div>
</div>
