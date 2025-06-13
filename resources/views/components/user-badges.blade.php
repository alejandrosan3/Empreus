@props(['users' => collect()])

{{-- Compact user badge list using Flux components --}}

@php
    $display = $users instanceof \Illuminate\Support\Collection ? $users->take(4) : array_slice($users, 0, 4);
    $extra = $users instanceof \Illuminate\Support\Collection ? $users->count() - $display->count() : count($users) - count($display);
@endphp

<div x-data="{ open: false }" class="relative">
    <div class="flex items-center">
        @foreach ($display as $user)
            @php
                $initials = collect(explode(' ', $user->name))->map(fn($p) => strtoupper(substr($p, 0, 1)))->join('');
            @endphp
            <flux:tooltip content="{{ $user->name }}" class="first:ml-0 -ml-2">
                @if(!empty($user->avatar))
                    <flux:avatar src="{{ $user->avatar }}" size="sm" class="!rounded-full ring-2 ring-white" />
                @else
                    <div class="size-8 flex items-center justify-center rounded-full bg-zinc-200 text-xs font-medium text-zinc-700 ring-2 ring-white">
                        {{ $initials }}
                    </div>
                @endif
            </flux:tooltip>
        @endforeach

        @if($extra > 0)
            <button type="button" @click="open = !open" class="size-8 -ml-2 first:ml-0 flex items-center justify-center rounded-full bg-zinc-200 text-xs font-medium text-zinc-700 ring-2 ring-white">
                +{{ $extra }}
            </button>
        @endif
    </div>

    <flux:popover x-show="open" x-cloak @click.outside="open = false" class="absolute z-50 mt-2">
        <div class="grid grid-cols-4 sm:grid-cols-6 gap-2">
            @foreach ($users as $user)
                @php
                    $initials = collect(explode(' ', $user->name))->map(fn($p) => strtoupper(substr($p, 0, 1)))->join('');
                @endphp
                <flux:tooltip content="{{ $user->name }}">
                    @if(!empty($user->avatar))
                        <flux:avatar src="{{ $user->avatar }}" size="sm" class="!rounded-full" />
                    @else
                        <div class="size-8 flex items-center justify-center rounded-full bg-zinc-200 text-xs font-medium text-zinc-700">
                            {{ $initials }}
                        </div>
                    @endif
                </flux:tooltip>
            @endforeach
        </div>
    </flux:popover>
</div>
