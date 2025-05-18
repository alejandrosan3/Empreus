<div>
  <flux:card>
    @foreach($items as $item)
      @if($mode === 'view')
        {{-- just show the label/value --}}
        <flux:heading class="my-3" size="lg" level="{{ $item['level'] ?? 3 }}">
          <u>{{ $item['label'] }}:</u> {{ !empty($item['value']) ? $item['value'] : "-" }}
        </flux:heading>
      @else
        @php
          $type  = $item['type'] ?? 'text';
          $field = $item['field'];
          $label = $item['label'];
        @endphp
        @switch($type)
          @case('select')
            @php
              $options = match($field) {
                          'builder_id'     => $this->builderOptions,
                          'subdivision'    => $this->subdivisionOptions,
                          'lot_number'         => $this->lotOptions,
                          'supervisor_id'  => $this->supervisorOptions,
                          'address'        => $this->addressOptions,
                          'task_type_id'   => $this->taskTypeOptions,
                          'status_type_id'   => $this->statusTypeOptions,
                          'is_extra'       => $this->orderTypeOptions,
                          
                          default => []
                        };        
            @endphp
            <flux:select
              label="{{ $label }}"
              variant="listbox"
              wire:model.defer="$parent.{{ $field }}"
              :error="$errors->first($field)"
              searchable
              clearable
            >
              @foreach($options as $opt)
                <flux:select.option value="{{ $opt->id }}"> {{ $opt->name }} </flux:select.option>
              @endforeach
            </flux:select>
            @break

          @case('date')
            <flux:date-picker label="{{ $label }}" wire:model.defer="$parent.{{ $field }}" selectable-header clearable />
            @break
          
          @default
            <flux:input label="{{ $label }}" wire:model.defer="$parent.{{ $field }}" />
            @break
        @endswitch
        
      @endif
    @endforeach
  </flux:card>
</div>
