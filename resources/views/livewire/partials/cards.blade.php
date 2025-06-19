<div>
  <flux:card>
    @foreach($items as $item)
      @if($mode === 'view')
        <flux:heading class="my-3" size="lg" level="{{ $item['level'] ?? 3 }}">
          <u>{{ $item['label'] }}:</u>
          {!! $item['value'] ?? '-' !!}
        </flux:heading>
      @else
        @php
          $type     = $item['type']    ?? 'text';
          $field    = $item['field'];
          $label    = $item['label'];
          $options  = $item['options'] ?? [];

          // Define dependency to force re-rendering
          $dependency = match($field) {
            'subdivision' => $parent->builder_id ?? 'none',
            'lot_number'  => $parent->subdivision ?? 'none',
            default       => 'none',
          };

          $wireKey = "{$field}-{$dependency}";

          // Disable select when empty
          $disabled = $type === 'select' && empty($options);
        @endphp

        @switch($type)
          @case('select')
            <flux:select
              wire:key="{{ $wireKey }}"
              label="{{ $label }}"
              variant="listbox"
              wire:model.live="$parent.{{ $field }}"
              :error="$errors->first($field)"
              searchable
              clearable
              :disabled="$disabled"
            >
              <option disabled value="">— Select —</option>
              @foreach($options as $opt)
                <flux:select.option value="{{ $opt->id }}">
                  {{ $opt->name }}
                </flux:select.option>
              @endforeach
            </flux:select>
            @break

          @case('date')
            <flux:date-picker
              label="{{ $label }}"
              wire:model.defer="$parent.{{ $field }}"
              :error="$errors->first($field)"
              selectable-header
              clearable
            />
            @break

          @default
            <flux:input
              label="{{ $label }}"
              wire:model.defer="$parent.{{ $field }}"
              :error="$errors->first($field)"
            />
        @endswitch
      @endif
    @endforeach
  </flux:card>
</div>