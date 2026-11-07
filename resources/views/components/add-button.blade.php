@props(['type'])

@php
    $meta = config("addables.{$type}");
@endphp

{{-- Only shown when the person's role can add this kind of record. --}}
@if($meta && Gate::allows($meta['ability'] ?? 'edit-'.$type))
    <div>
        <a href="{{ route($meta['route']) }}">
            <flux:tooltip :content="'Add ' . $meta['label']">
                <flux:button icon="{{ $meta['icon'] }}"></flux:button>
            </flux:tooltip>
        </a>
    </div>
@endif
