@php
    $pie = $chartData['pies'][$id];
    $fmt = fn ($v) => $pie['money'] ? '$' . number_format($v) : number_format($v);
@endphp

<flux:card class="flex flex-col gap-3">
    <div>
        <flux:heading>{{ $title }}</flux:heading>
        <flux:text size="sm">{{ $help }}</flux:text>
    </div>

    @if(empty($pie['slices']))
        <div class="flex-1 grid place-items-center text-sm text-zinc-500 py-10">Nothing to show for these filters.</div>
    @endif

    <div wire:ignore class="relative h-44 {{ empty($pie['slices']) ? 'hidden' : '' }}" data-pie-wrap="{{ $id }}">
        <canvas id="pie-{{ $id }}" role="img" aria-label="{{ $title }}"></canvas>
        <div class="absolute inset-0 grid place-items-center pointer-events-none text-center">
            <div>
                <div class="text-lg font-semibold text-zinc-900 dark:text-white" data-pie-total="{{ $id }}">{{ $fmt($pie['total']) }}</div>
                <div class="text-xs text-zinc-500">total</div>
            </div>
        </div>
    </div>

    <ul class="space-y-1 text-sm">
        @foreach($pie['slices'] as $slice)
            <li class="flex items-center gap-2" wire:key="{{ $id }}-{{ $slice['key'] }}">
                <span class="swatch" style="background: var(--series-{{ $slice['slot'] }})"></span>
                <span class="flex-1 truncate text-zinc-700 dark:text-zinc-200" title="{{ $slice['label'] }}">{{ $slice['label'] }}</span>
                <span class="num text-zinc-900 dark:text-white">{{ $fmt($slice['value']) }}</span>
                <span class="num w-12 text-right text-zinc-500">{{ $slice['percent'] }}%</span>
            </li>
        @endforeach
    </ul>
</flux:card>
