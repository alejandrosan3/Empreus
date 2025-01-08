<div x-data="{ open: false }" class="relative inline-block text-left">

    <flux:tooltip content="Add/Edit Columns">
        <!-- Dropdown Button -->
        <button @click="open = !open" class="relative items-center font-medium justify-center gap-2 whitespace-nowrap disabled:opacity-75 dark:disabled:opacity-75 disabled:cursor-default disabled:pointer-events-none h-10 text-sm rounded-lg w-10 inline-flex bg-white hover:bg-zinc-50 dark:bg-zinc-700 dark:hover:bg-zinc-600/75 text-zinc-800 dark:text-white border border-zinc-200 hover:border-zinc-200 border-b-zinc-300/80 dark:border-zinc-600 dark:hover:border-zinc-600 shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 4.5v15m6-15v15m-10.875 0h15.75c.621 0 1.125-.504 1.125-1.125V5.625c0-.621-.504-1.125-1.125-1.125H4.125C3.504 4.5 3 5.004 3 5.625v12.75c0 .621.504 1.125 1.125 1.125Z" />
            </svg>    
        </button>
    </flux:tooltip>

    <!-- Dropdown Content -->
    <div x-show="open" @click.outside="open = false" class="absolute right-0 mt-2 w-64 bg-white border rounded shadow-lg z-50">
        <div class="p-4">
            <!-- Fixed Columns -->
            <h4 class="font-bold text-sm mb-2">Fixed columns</h4>
            <ul id="fixed-columns" class="mb-4 space-y-2" x-init="initSortable($el, 'fixed')" >
                @foreach ($fixedColumns as $column)
                    <li class="flex justify-between items-center space-x-2" data-id="{{ $column['id'] }}">
                        <!-- checkbox - left -->
                        <div class="flex items-center space-x-2">
                            <input type="checkbox" wire:model.defer="fixedColumns.{{ $loop->index }}.selected" class="form-checkbox w-5 h-5 rounded border-accent-content text-accent-foreground checked:bg-black checked:border-accent checked:text-accent-foreground focus:checked:bg-black hover:checked:bg-black active:checked:bg-black focus:outline-none focus:ring-0 hover:outline-none hover:ring-0 active:outline-none active:ring-0">

                            <span>{{ $column['label'] }}</span>
                        </div>

                        <!-- Icon - left -->
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15 12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                        </svg>
                    </li>
                @endforeach
            </ul>

            <!-- Active Columns -->
            <h4 class="font-bold text-sm mb-2">Active columns</h4>
            <ul id="active-columns" class="space-y-2" x-init="initSortable($el, 'active')" >
                @foreach ($activeColumns as $column)
                    <li class="flex justify-between items-center space-x-2" data-id="{{ $column['id'] }}">
                        <!-- checkbox - left -->
                        <div class="flex items-center space-x-2">
                            <input type="checkbox" wire:model.defer="activeColumns.{{ $loop->index }}.selected" class="form-checkbox w-5 h-5 rounded border-accent-content text-accent-foreground checked:bg-black checked:border-accent checked:text-accent-foreground focus:checked:bg-black hover:checked:bg-black active:checked:bg-black focus:outline-none focus:ring-0 hover:outline-none hover:ring-0 active:outline-none active:ring-0">
                            <span>{{ $column['label'] }}</span>
                        </div>

                        <!-- Icon - left -->
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15 12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                        </svg>
                          
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>

<script>
    // Alpine.js initialization for SortableJS
    function initSortable(el, groupName) {
        new Sortable(el, {
            group: {
                name: groupName,
                pull: true,
                put: true,
            },
            animation: 150,
            onEnd: (event) => {
                const reorderedIds = [...event.from.children].map(item => item.getAttribute('data-id'));
                const targetGroup = event.to.id.includes('active') ? 'active' : 'fixed';
                const sourceGroup = event.from.id.includes('active') ? 'active' : 'fixed';

                // Add Livewire dispatch content here.
               
            },
        });
    }

</script>