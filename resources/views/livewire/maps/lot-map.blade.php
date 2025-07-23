<flux:container class="relative">

    <div class="top-2 right-2 flex items-center space-x-2 mb-2">
        <flux:switch id="toggle-legend" wire:model="showLegend"/>
        <span class="text-sm">Show legend</span>
    </div>

    <div 
        id="lot-map" 
        data-lots='@json($lots)' 
        data-google-key='{{ config("services.google.maps_key") }}'
        wire:ignore
        class="w-full h-screen"
        >
    </div>

    <div id="lot-legend" class="absolute top-8 right-2 bg-white dark:bg-zinc-800 rounded shadow p-3 space-y-1 hidden">
        @foreach($subdivisions as $index => $name)
            <div class="flex items-center space-x-2">
                <span class="w-3 h-3 rounded-full" data-subdivision-color="{{ $name }}"></span>
                <span class="text-xs">{{ $name }}</span>
            </div>
        @endforeach
    </div>
</flux:container>


    @push('scripts')
        {{-- this will output the correct localhost:5173 URL for you --}}
        @vite('resources/js/lot-map.js')
    @endpush

