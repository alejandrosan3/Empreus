<div>
    <flux:card>
        @foreach ($items as $item)
            <flux:heading class="my-3" size="lg" level="{{ $item['level'] ?? 3 }}">
                <u>{{ $item['label'] }}:</u> {{ $item['value'] }}
            </flux:heading>
        @endforeach
    </flux:card>
</div>
