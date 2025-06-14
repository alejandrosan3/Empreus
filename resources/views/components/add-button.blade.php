@props(['type'])

@php
    $meta = config("addables.{$type}");
@endphp


@props(['type'])

@php
    $meta = config("addables.{$type}");
@endphp

<div>
    <a href="{{ route($meta['route']) }}" wire:navigate>
        <flux:tooltip :content="'Add ' . $meta['label']">
            <flux:button icon="{{ $meta['icon'] }}"></flux:button>
        </flux:tooltip>
    </a>
</div>
