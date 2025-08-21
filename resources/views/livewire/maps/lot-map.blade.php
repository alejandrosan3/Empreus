<flux:container class="relative">

    <div class="top-2 right-2 flex items-center space-x-2 mb-2">
        <flux:switch id="toggle-legend" wire:model="showLegend"/>
        <span class="text-sm">Show legend</span>
    </div>

    @php
        // Build a clean array the JS can trust
        $lotMapPayload = collect($lots)->map(function ($l) {
            $lat = data_get($l, 'latitude');
            $lng = data_get($l, 'longitude');

            // Cast to numbers or null
            $lat = is_numeric($lat) ? (float) $lat : null;
            $lng = is_numeric($lng) ? (float) $lng : null;

            return [
                'id'          => data_get($l, 'id'),
                'address'     => data_get($l, 'address'),
                'subdivision' => data_get($l, 'subdivision'),
                'lat'         => $lat,   // <-- always these keys for JS
                'lng'         => $lng,
            ];
        })
        ->filter(fn ($row) => !is_null($row['id']))
        ->values()
        ->all();
    @endphp

    <div
        id="lot-map"
        data-lots='@json($lotMapPayload)'
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
