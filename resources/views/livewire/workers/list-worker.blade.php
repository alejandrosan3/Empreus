<div>
    <livewire:partials.editable-modal />
    <livewire:workers.manage-worker-teams />

    <flux:checkbox.group x-data="bulkAssignCheckboxes()">
        <div class="h-8 flex items-center">
            <div x-show="selectedIds.length > 0" x-transition
                class="transition-opacity duration-300 opacity-0"
                :class="{ 'opacity-100': selectedIds.length > 0 }"
            >
                <flux:button @click="$wire.set('checkedWorkers', selectedIds); $wire.startBulkAssign()" size="sm">
                    Assign Teams
                </flux:button>
            </div>
        </div>

        <flux:table :paginate="$this->workers">
            <flux:table.columns>
                <flux:table.column>
                    <flux:checkbox.all />
                </flux:table.column>
                @foreach ($this->columns as $column)
                    <flux:table.column
                        sortable
                        :sorted="$sortBy === '{{ $column }}'"
                        :direction="$sortDirection"
                        wire:click="sort('{{ $column }}')">
                        {{ ucfirst(str_replace('_', ' ', $column)) }}
                    </flux:table.column>
                @endforeach
                <flux:table.column>Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->workers as $worker)
                    <flux:table.row :key="$worker->id">
                        <flux:table.cell class="sticky left-0 z-20 bg-white dark:bg-zinc-800">
                            <flux:checkbox
                                x-data="{ id: {{ $worker->id }} }"
                                value="{{ $worker->id }}"
                                x-bind:checked="selectedIds.includes(id)"
                                @change="e => {
                                    if (e.target.checked) selectedIds.push(id);
                                    else selectedIds = selectedIds.filter(i => i !== id);
                                }"
                            />
                        </flux:table.cell>
                        @foreach ($this->columns as $column)
                            <flux:table.cell
                                x-on:dblclick="
                                    $dispatch('editField', {
                                        fieldId: {{ $worker->id }},
                                        currentValue: '{{ addslashes($worker->{$column} ?? '') }}',
                                        fieldType: 'input',
                                        optionKey: '',
                                        modelClass: '{{ addslashes(\App\Models\User::class) }}',
                                        modelAttribute: '{{ $column }}',
                                        editable: true
                                    })"
                            >
                                {{ $worker->{$column} }}
                            </flux:table.cell>
                        @endforeach
                        <flux:table.cell>
                            <flux:dropdown>
                                <flux:button icon="ellipsis-horizontal" variant="ghost" size="sm" />
                                <flux:menu>
                                    <flux:menu.item icon="user-group" @click="$dispatch('open-worker-team-modal', { ids: [{{ $worker->id }}] })">
                                        Assign Teams
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:checkbox.group>


    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('bulkAssignCheckboxes', () => ({
                selectedIds: [],
                allIds: @js($this->workers->pluck('id')),
                toggleAll(e) {
                    this.selectedIds = e.detail.all ? this.allIds : [];
                }
            }));
        });
    </script>
</div>
