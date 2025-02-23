<div x-data="{ open: false }" class="relative inline-block text-left">

    <flux:tooltip content="Add/Edit Columns">
        <!-- Dropdown Button -->
        <button @click="open = !open" class="relative items-center font-medium justify-center gap-2 whitespace-nowrap disabled:opacity-75 dark:disabled:opacity-75 disabled:cursor-default disabled:pointer-events-none h-10 text-sm rounded-lg w-10 inline-flex bg-white hover:bg-zinc-50 dark:bg-zinc-700 dark:hover:bg-zinc-600/75 text-zinc-800 dark:text-white border border-zinc-200 hover:border-zinc-200 border-b-zinc-300/80 dark:border-zinc-600 dark:hover:border-zinc-600 shadow-xs">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 4.5v15m6-15v15m-10.875 0h15.75c.621 0 1.125-.504 1.125-1.125V5.625c0-.621-.504-1.125-1.125-1.125H4.125C3.504 4.5 3 5.004 3 5.625v12.75c0 .621.504 1.125 1.125 1.125Z" />
            </svg>
        </button>
    </flux:tooltip>

    <!-- Dropdown Content -->
    <div x-show="open" @click.outside="open = false" class="absolute right-0 mt-2 w-64 bg-white border rounded-sm shadow-lg z-50">
        <div class="p-4">
            <!-- Fixed Columns -->
            <h4 class="font-bold text-sm mb-2">Fixed columns</h4>
            <ul id="fixed-columns" class="mb-4 space-y-2" x-init="initSortable($el, 'fixed')">
                @foreach($columns as $index => $column)
                    @if($column['group'] === 'fixed')
                        <li class="flex justify-between items-center space-x-2" data-id="{{ $column['id'] }}">
                            <div class="flex items-center space-x-2">
                                <input type="checkbox" checked disabled class="form-checkbox w-5 h-5 rounded-sm border-gray-300 text-gray-600 focus:ring-gray-500 opacity-25">
                                <span>{{ $column['label'] }}</span>
                            </div>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                            </svg>
                        </li>
                    @endif
                @endforeach
            </ul>


            <!-- Active Columns -->
            <h4 class="font-bold text-sm mb-2">Active columns</h4>
            <ul id="active-columns" class="space-y-2" x-init="initSortable($el, 'active')">
                @foreach($columns as $index => $column)
                    @if($column['group'] === 'active')
                        <li class="flex justify-between items-center space-x-2" data-id="{{ $column['id'] }}" wire:key="column-{{ $column['id'] }}">
                            <div class="flex items-center space-x-2">
                                <input type="checkbox"
                                    wire:model.defer="columns.{{ $index }}.selected"
                                    class="form-checkbox w-5 h-5 rounded-sm border-gray-300 text-blue-600 focus:ring-blue-500"
                                    @change="dispatchUpdatedColumns()">
                                <span>{{ $column['label'] }}</span>
                            </div>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                            </svg>
                        </li>
                    @endif
                @endforeach
            </ul>
        </div>
    </div>
</div>

<script>

    function dispatchUpdatedColumns() {
        const fixedIds = [...document.getElementById('fixed-columns').children].map(item => item.getAttribute('data-id'));
        const activeIds = [...document.getElementById('active-columns').children].map(item => item.getAttribute('data-id'));
        Livewire.dispatch('update-column-lists', { fixedIds, activeIds });
    }
    // Alpine.js initialization for SortableJS
    function initSortable(el, groupName) {
        new Sortable(el, {
            group: {
                name: groupName,
                pull: true,
                put: true,
            },
            animation: 200,
            onEnd: (event) => {
                dispatchUpdatedColumns();


            },
        });
    }

</script>
