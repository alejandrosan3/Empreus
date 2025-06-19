<div>
    @php
        $total   = $users->count();
        $max     = $maxVisible;          // shorthand inside Blade loop
    @endphp

    @if ($total)
        <!-- ONE group + Alpine toggling -->
        <div x-data="{ open: false }">
            <flux:avatar.group class="flex-wrap gap-2 mt-1">
                @foreach ($users as $index => $user)

                    <!-- normal avatars: show if open OR index < max -->
                    <template x-if="open || {{ $index }} < {{ $max }}">
                        <flux:dropdown hover position="bottom center">
                            <flux:avatar  as="button" :size="$avSize" circle
                                          :name="$user->name" />
                            <flux:popover class="relative max-w-[15rem]">
                                <flux:avatar size="xl" circle :name="$user->name" />
                                <flux:heading class="mt-2">{{ $user->name }}</flux:heading>
                                <flux:text>{{ $user->email }}</flux:text>
                            </flux:popover>
                        </flux:dropdown>
                    </template>

                @endforeach

                {{-- “+X” pill — only when list is closed and we have extras --}}
                @if ($total > $maxVisible)
                    <template x-if="!open">
                        <flux:avatar as="button" color="lime" :size="$avSize" circle @click="open = true">
                            +{{ $total - $maxVisible }}
                        </flux:avatar>
                    </template>

                    <template x-if="open">
                        <flux:avatar as="button" color="lime" :size="$avSize" circle @click="open = false">
                            <flux:icon name="arrow-left" class="w-4 h-4" />
                        </flux:avatar>
                    </template>

                @endif
            </flux:avatar.group>
        </div>
    @endif
</div>
