<flux:table>
    <flux:columns>
        <flux:column>Builder</flux:column>
        <flux:column>Subdivision</flux:column>
        <flux:column>LOT</flux:column>
        @foreach ($tasks as $task)
            <flux:column>{{ $task }}</flux:column>
        @endforeach
        <flux:column>Floor</flux:column>
        <flux:column>EPOs</flux:column>
        <flux:column>Progress</flux:column>
        <flux:column>Actions</flux:column>
    </flux:columns>

    <flux:rows>
        @foreach ($dummyData as $row)
            <flux:row :key="$row['id']">
                <flux:cell>{{ $row['builder'] }}</flux:cell>
                <flux:cell>{{ $row['subdivision'] }}</flux:cell>
                <flux:cell>{{ $row['lot'] }}</flux:cell>
                @foreach ($tasks as $task) <!-- Use $task as the key to access the value -->
                    <flux:cell>
                        @if($row['tasks'][$task] ?? false)
                            <flux:checkbox checked disabled />
                        @else
                            <flux:checkbox disabled />
                        @endif
                    </flux:cell>
                @endforeach
                <flux:cell>{{ $row['floor'] }}</flux:cell>
                <flux:cell>{{ $row['EPO'] }}</flux:cell>
                <flux:cell>
                    <flux:badge :color="$row['progress_color']" variant="pill" size="lg">
                        {{ $row['progress'] }}
                    </flux:badge>
                </flux:cell>
                <flux:cell>
                    <flux:button icon="ellipsis-horizontal" variant="ghost" size="sm" />
                </flux:cell>
            </flux:row>
        @endforeach
    </flux:rows>
</flux:table>