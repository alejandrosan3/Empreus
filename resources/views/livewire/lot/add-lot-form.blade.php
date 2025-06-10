<div class="p-4">
    <flux:button icon="arrow-left" variant="ghost" wire:click="goBack">
        Back
    </flux:button>

    {{-- Validation errors (will show once we add rules) --}}
    @if ($errors->any())
        <div class="mb-4 text-red-600">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <flux:heading size="xl" class="mb-4"> Add Lot <flux:badge color="yellow" inset="top bottom">{{ $companyName }}</flux:badge> </flux:heading>

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
        {{-- Builder / Subdivision / Lot --}}
        <div>
            <flux:card>
                {{-- Builder select --}}
                <flux:select
                    wire:key="builder_id-none"
                    label="Builder"
                    variant="listbox"
                    wire:model.live="builder_id"
                    :error="$errors->first('builder_id')"
                    searchable
                    clearable
                >
                    <option disabled value="">— Select —</option>
                    @foreach($card1[0]['options'] as $opt)
                        <flux:select.option value="{{ $opt->id }}">{{ $opt->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                {{-- Subdivision select or input --}}
                @if(!$showSubdivisionInput)
                    <div class="flex items-end gap-2">
                        <div class="flex-1">
                            @php $disabled = empty($card1[1]['options']); @endphp
                            <flux:select
                                wire:key="subdivision-{{ $builder_id ?? 'none' }}"
                                label="Subdivision"
                                variant="listbox"
                                wire:model.live="subdivision"
                                :error="$errors->first('subdivision')"
                                searchable
                                clearable
                                :disabled="$disabled"
                            >
                                <option disabled value="">— Select —</option>
                                @foreach($card1[1]['options'] as $opt)
                                    <flux:select.option value="{{ $opt->id }}">{{ $opt->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>
                        <flux:button wire:click="toggleSubdivisionInput" size="sm" variant="ghost" icon="plus" :disabled="$disabled" />
                    </div>
                @else
                    <flux:input
                        label="Subdivision"
                        wire:model.defer="newSubdivision"
                        :error="$errors->first('subdivision')"
                    />
                @endif

                {{-- Lot Number --}}
                <flux:input
                    wire:key="lot_number-{{ $subdivision ?? 'none' }}"
                    label="Lot Number"
                    wire:model.defer="lot_number"
                    :error="$errors->first('lot_number')"
                />
            </flux:card>
        </div>

        {{-- Address / Supervisor / Phase --}}
        <div>
            <flux:card>
                <flux:input
                    label="Phase"
                    wire:model.defer="phase"
                    :error="$errors->first('phase')"
                />

                <flux:input
                    label="Address"
                    wire:model.defer="address"
                    :error="$errors->first('address')"
                />

                @php $disabled = empty($card2[2]['options']); @endphp
                @if(!$showSupervisorInput)
                    <div class="flex items-end gap-2">
                        <div class="flex-1">
                            <flux:select
                                wire:key="supervisor-{{ $builder_id ?? 'none' }}"
                                label="Supervisor"
                                variant="listbox"
                                wire:model.live="supervisor_id"
                                :error="$errors->first('supervisor_id')"
                                searchable
                                clearable
                                :disabled="$disabled"
                            >
                                <option disabled value="">— Select —</option>
                                @foreach($card2[2]['options'] as $opt)
                                    <flux:select.option value="{{ $opt->id }}">{{ $opt->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>
                        <flux:button wire:click="toggleSupervisorInput" size="sm" variant="ghost" icon="plus" :disabled="$disabled" />
                    </div>
                @else
                    <div class="flex items-end gap-2">
                        <div class="flex-1">
                            <flux:input
                                label="Supervisor"
                                wire:model.defer="newSupervisor"
                                :error="$errors->first('newSupervisor')"
                            />
                        </div>
                        <flux:button wire:click="toggleSupervisorInput" size="sm" variant="ghost" icon="x-mark" />
                    </div>
                @endif
            </flux:card>
        </div>

        {{-- Floor Number and SQFT --}}
        <livewire:partials.cards :items="$card3" mode="add" />
    </div>

    <flux:button variant="primary" class="w-full mt-4" wire:click.prevent="save">
        Add Lot
    </flux:button>

    @persist('toast')
        <flux:toast />
    @endpersist
</div>
