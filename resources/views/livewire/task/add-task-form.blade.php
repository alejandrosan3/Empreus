<section class="space-y-6 p-4">
    <header>
        <flux:heading size="lg" level="2">Add New Task</flux:heading>
    </header>

    <div class="grid grid-cols-2 gap-4">
        {{-- Builder (select) --}}
        <div class="col-span-2">
            <flux:select label="Builder" variant="combobox" :filter="false" wire:model.defer="builder_id" :error="$errors->first('builder_id')">
                @foreach(\App\Models\Builder::all() as $b)
                    <flux:select.option
                        value="{{ $b->id }}"
                        wire:key="builder-{{ $b->id }}"
                    >
                        {{ $b->name }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>

        {{-- Subdivision (select) --}}
        <div class="col-span-2">
            <flux:select label="Subdivision" variant="combobox" :filter="false" wire:model.defer="subdivision_id" :error="$errors->first('subdivision_id')" >
                @foreach(\App\Models\Subdivision::all() as $s)
                    <flux:select.option
                        value="{{ $s->id }}"
                        wire:key="subdivision-{{ $s->id }}"
                    >
                        {{ $s->name }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>

        {{-- Task Name (select or add new) --}}
        <div class="col-span-2">
            <flux:select label="Task Name" variant="combobox" :filter="false" wire:model.defer="task_type_id" :error="$errors->first('task_type_id')" >
                <x-slot name="input">
                    <flux:select.input wire:model.live="task_search" />
                </x-slot>

                @foreach(\App\Models\TaskName::all() as $t)
                    <flux:select.option
                        value="{{ $t->id }}"
                        wire:key="task-{{ $t->id }}"
                    >
                        {{ $t->name }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>

        {{-- Purchase Order --}}
        <flux:input label="Purchase Order" type="text" wire:model.defer="order_number" :error="$errors->first('order_number')" />

        {{-- Price --}}
        <flux:input label="Price" type="number" wire:model.defer="price" :error="$errors->first('price')" />

        {{-- Order Type --}}
        <flux:select label="Order Type" wire:model.defer="is_extra" :error="$errors->first('is_extra')" >
            <flux:select.option value="0">Standard</flux:select.option>
            <flux:select.option value="1">EPO</flux:select.option>
        </flux:select>

        {{-- Planned Start --}}
        <flux:input label="Planned Start" type="date" wire:model.defer="planned_start" :error="$errors->first('planned_start')" />

        {{-- Note (full width) --}}
        <div class="col-span-2">
            <flux:input label="Notes" type="textarea" wire:model.defer="note" :error="$errors->first('note')" />
        </div>
    </div>

    <div class="flex justify-end">
        <flux:button wire:click="saveTask" variant="primary"> Add task </flux:button>
    </div>
</section>
