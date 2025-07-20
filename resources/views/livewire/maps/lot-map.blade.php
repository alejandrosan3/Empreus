<flux:container class="relative">
    <div id="lot-map" class="w-full h-96"></div>

    <div class="absolute top-2 right-2 flex items-center space-x-2">
        <flux:switch id="toggle-legend" wire:model="showLegend"/>
        <span class="text-sm">Show legend</span>
    </div>

    <div id="lot-legend" class="absolute top-12 right-2 bg-white dark:bg-zinc-800 rounded shadow p-3 space-y-1 hidden">
        @foreach($subdivisions as $index => $name)
            <div class="flex items-center space-x-2">
                <span class="w-3 h-3 rounded-full" data-subdivision-color="{{ $name }}"></span>
                <span class="text-xs">{{ $name }}</span>
            </div>
        @endforeach
    </div>
</flux:container>

@push('scripts')
@vite('resources/js/lot-map.js')
<script>
    window.lotMapData = @json($lots);
    window.googleMapsKey = '{{ config('services.google.maps_key') }}';
</script>
@endpush
