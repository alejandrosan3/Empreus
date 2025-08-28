<div>
    {{-- Modal for hybrid inline editing behavior --}}
    <livewire:partials.editable-modal />

    {{-- Modals for bulk behavior --}}
    <livewire:partials.bulk-edit-modal />
    <livewire:teams.manage-teams :key="'manage-teams'" />

    <flux:checkbox.group x-data="bulkEditCheckboxes()">
        <div class="h-8 flex items-center gap-2">
            <div x-show="selectedIds.length > 0" x-transition
                class="transition-opacity duration-300 opacity-0"
                :class="{ 'opacity-100': selectedIds.length > 0 }"
            >
                <flux:button @click="$wire.set('checkedTasks', selectedIds); $wire.startBulkEdit()" size="sm">
                    Bulk Edit
                </flux:button>
            </div>
            <div x-show="selectedIds.length > 0" x-transition
                class="transition-opacity duration-300 opacity-0"
                :class="{ 'opacity-100': selectedIds.length > 0 }"
            >
                <flux:button @click="$wire.set('checkedTasks', selectedIds); $wire.startBulkAssign()" size="sm">
                    Assign Workers
                </flux:button>
            </div>
        </div>

        <div
            class="overflow-x-auto"         {{-- ensures horizontal scroll --}}
            x-data="stickyTable()"           {{-- mount our Alpine component --}}
            x-init="init()"                  {{-- call init() on load --}}
            x-ref="tableContainer"           {{-- reference for scroll & query --}}
        >
            <flux:table :paginate="$tasks->onEachSide(1)">
                <!-- Table Header -->
                <flux:table.columns>
                    <!-- checkbox "Select-All" Column -->
                    <flux:table.column class="sticky frozen-cell left-0 z-20 bg-white dark:bg-zinc-800" data-fixed-index="0">
                        <flux:checkbox.all />
                    </flux:table.column>
                    <!-- Fixed Columns -->
                    @foreach($columns as $column)
                        @if($column['selected'])
                            <flux:table.column
                                :sortable="$column['sortable']"
                                :sorted="$column['sortable'] ? $sortBy === $column['value'] : false"
                                wire:click="{{ $column['sortable'] ? 'sort(\'' . $column['value'] . '\')' : '' }}"
                                class="bg-white dark:bg-zinc-800 {{ $column['group'] == 'fixed' ? 'sticky frozen-cell' : '' }}"
                                data-fixed-index="{{ $column['group'] == 'fixed' ? $loop->index + 1 : '' }}"
                            >
                                {{ $column['label'] }}
                            </flux:table.column>
                        @endif
                    @endforeach
                    <!-- Actions Column -->
                    <flux:table.column>Actions</flux:table.column>
                </flux:table.columns>

                <!-- Table Rows -->
                <flux:table.rows>
                    @foreach($flatTasks as $row)
                        <flux:table.row :key="$row['id']">
                        <flux:table.cell class="sticky frozen-cell left-0 z-20 bg-white dark:bg-zinc-800" data-fixed-index="0" >
                            <flux:checkbox
                                x-data="{ id: {{ $row['id'] }} }"
                                value="{{ $row['id'] }}"
                                x-bind:checked="selectedIds.includes(id)"
                                @change="e => {
                                    if (e.target.checked) selectedIds.push(id);
                                    else selectedIds = selectedIds.filter(i => i !== id);
                                }"
                            />
                        </flux:table.cell>
                            @foreach($columns as $column)
                                @if($column['selected'])
                                    @php($cell = $row[$column['value']])
                                    @php($event = $column['filterType'] == 'tag' ? 'open-edit-tags' : 'editField' )
                                    <flux:table.cell
                                        wire:key="cell-{{ $row['id'] }}-{{ $column['value'] }}"
                                        x-data="{ev: '{{ $event }}'}"
                                        x-on:dblclick=" ev == 'open-edit-tags' ?
                                        Livewire.dispatchTo('task.edit-task-tags-modal','open-edit-tags', {taskId: {{ $cell['recordId'] }}} )
                                        :
                                            Livewire.dispatchTo('partials.editable-modal',ev, {
                                            fieldId: {{ $cell['recordId'] }},
                                            currentValue: '{{ $cell['rawValue'] }}',
                                            fieldType: '{{ $cell['fieldType'] }}',
                                            optionKey: '{{ $cell['optionKey'] }}',
                                            modelClass: '{{ addslashes($cell['modelClass']) }}',
                                            modelAttribute: '{{ $cell['modelAttribute'] }}',
                                            editable: {{ $cell['editable'] ? 'true' : 'false' }}
                                            })"
                                        class="bg-white dark:bg-zinc-800 {{ $column['group']=='fixed'?'sticky frozen-cell':'' }}"
                                        data-fixed-index="{{ $column['group']=='fixed'? $loop->index + 1 : '' }}"
                                    >

                                        @if ($column['value'] === 'status')
                                            <flux:badge color="{{ $cell['color'] }}"> {{ $cell['display'] }} </flux:badge>
                                        @else
                                            {{ $cell['display'] }}
                                        @endif
                                    </flux:table.cell>
                                @endif
                            @endforeach

                            <!-- Actions Cell -->
                            <flux:table.cell>
                                <flux:dropdown>
                                    <flux:button icon="ellipsis-horizontal" variant="ghost" size="sm" />
                                    <flux:menu>
                                        {{-- TASK DETAILS --}}
                                        <flux:menu.item icon="clipboard-document-check" href="{{ route('task-details', ['task_id' => $row['id']]) }}" wire:navigate >
                                            Details
                                        </flux:menu.item>

                                        {{-- DUPLICATE --}}
                                        <flux:menu.item icon="document-duplicate" wire:click="duplicateTask({{ $row['id'] }})" >
                                            Duplicate
                                        </flux:menu.item>

                                        {{-- WORKERS --}}
                                        <flux:menu.item icon="user-group" @click="$dispatch('openModal', { taskId: {{ $row['id'] }} })">
                                            Workers
                                        </flux:menu.item>

                                    </flux:menu>
                                </flux:dropdown>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>

        // Modal for tags editing
        <livewire:task.edit-task-tags-modal />

        {{-- Include ManageTeams component for each task --}}
        @foreach($flatTasks as $row)
            <livewire:teams.manage-teams :taskId="$row['id']" :key="'manage-teams-'.$row['id']" />
        @endforeach


        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('stickyTable', () => ({
                ticking: false,
                offsets: [],

                init() {
                    // 1) Compute offsets right away
                    this.updateStickyOffsets();

                    // 2) On horizontal scroll, recalc
                    this.$refs.tableContainer.addEventListener('scroll', () => this.onScroll());


                    // 3) On window resize, recalc
                    window.addEventListener('resize', () => this.updateStickyOffsets());

                     // 4) Watch for DOM changes (column reordering)
                    const observer = new MutationObserver(() => {
                        requestAnimationFrame(() => this.updateStickyOffsets());
                    });
                    observer.observe(this.$refs.tableContainer, { childList: true, subtree: true });
                },

                onScroll() {
                    if (!this.ticking) {
                    window.requestAnimationFrame(() => {
                        this.updateStickyOffsets();
                        this.ticking = false;
                    });
                    this.ticking = true;
                    }
                },

                updateStickyOffsets() {
                    let offset = 0;
                    this.offsets = [];

                    // A) HEADER: find every header cell that’s sticky
                    this.$refs.tableContainer
                    .querySelectorAll('[data-fixed-index].sticky')
                    .forEach((col, idx) => {
                        col.style.left = offset + 'px';
                        this.offsets[idx] = offset;
                        offset += col.offsetWidth;
                    });

                    // B) BODY: for each row’s sticky cells, apply the same offsets
                    this.$refs.tableContainer
                    .querySelectorAll('[data-fixed-index].sticky')
                    .forEach((cell, idx) => {
                        // data-fixed-index gives us the column index
                        const left = this.offsets[cell.getAttribute('data-fixed-index')];
                        if (left !== undefined) {
                        cell.style.left     = left + 'px';
                        cell.style.position = 'sticky';
                        cell.style.top      = '0';
                        }
                    });
                },
                }));
            });
        </script>

    </flux:checkbox.group>


    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('bulkEditCheckboxes', () => ({
                selectedIds: [],
                allIds: @js(array_column($flatTasks, 'id')),

                toggleAll(e) {
                    this.selectedIds = e.detail.all ? this.allIds : [];
                }
            }));
        });
    </script>
</div>
