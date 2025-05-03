@props(['type'])

@php
    $meta = config("addables.{$type}");
@endphp


<flux:modal.trigger name="{{ $meta['modalName'] }}">
    <flux:tooltip :content="'Add ' . $meta['label']">
        <flux:button icon="{{ $meta['icon'] }}"></flux:button>
    </flux:tooltip>
</flux:modal.trigger>

<flux:modal name="{{ $meta['modalName'] }}" class="md:w-lg">
    @livewire($meta['livewire'], [], key("modal-{$meta['modalName']}")
)
</flux:modal>
