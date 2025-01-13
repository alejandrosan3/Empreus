<div class="p-4">
    <flux:heading size="xl" class="mb-4">Name of the content detail page view <flux:badge color="yellow" inset="top bottom">Tag?</flux:badge> </flux:heading>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
        <!-- Builder Details -->
        <div>
            <flux:card class="space-y-6 bg-gray-500/10">
                <flux:heading size="lg" level="2">Builder: DRH</flux:heading>
                <flux:heading size="lg" level="3">Subdivision: Baypointe</flux:heading>
                <flux:heading size="lg" level="3"> Lot: 108 </flux:heading>
            </flux:card>
        </div>

        <!-- Invoice and pricing -->
        <div>
            <flux:card class="space-y-6 bg-gray-500/10">
                <flux:heading size="lg" level="3">PO: 112a5545-e</flux:heading>
                <flux:heading level="5">Price: $125</flux:heading>
                <flux:heading level="5"> Status: <flux:badge variant="solid" color="orange" inset="top bottom">Confirmed</flux:badge> </flux:heading>
            </flux:card>
        </div>

        <!-- Supervisor Section -->
        <div>
            <flux:card class="space-y-6 bg-gray-500/10">
                    <flux:heading size="lg" level="3">Supervisor</flux:heading>
                    <flux:heading level="5">Name: Mr. Thomas James</flux:heading>
                    <flux:heading level="5">Group: Post-Construction Cleaners Group</flux:heading>
            </flux:card>
        </div>

    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
        <!-- Notes Section -->
        <flux:card class="space-y-6">
            <div>
                <flux:heading size="lg" Level="2">Notes</flux:heading>
                <p>
                    Once the data is cleaned and prepared, you can proceed with further analysis and visualization to extract meaningful insights and inform decision-making.
                </p>
            </div>
        </flux:card>

        <!-- Description Info Section -->
        <flux:card class="space-y-6">
            <div>
                <flux:heading size="lg" Level="2">Description</flux:heading>
                <p>
                    Once the data is cleaned and prepared, you can proceed with further analysis and visualization to extract meaningful insights and inform decision-making.
                </p>
            </div>
        </flux:card>
    </div>

    <div class="grid grid-cols-1 gap-4 mt-4">
        <!-- Standard Task Tables -->
        <div>
            <flux:card class="space-y-6">
                <flux:heading size="lg">Standard Tasks <flux:badge color="lime" inset="top bottom">Current</flux:badge></flux:heading>
                <flux:table>
                    <flux:columns>
                        <flux:column>Builder</flux:column>
                        <flux:column>Subdivision</flux:column>
                        <flux:column>Lot</flux:column>
                        <flux:column>Status</flux:column>
                        <flux:column>Start Date</flux:column>
                        <flux:column>Completed Date</flux:column>
                    </flux:columns>
                    <flux:rows>
                        @foreach ($standardTasks as $task)
                        <flux:row class="{{ $task['status'] === 'completed' ? 'opacity-50' : '' }}">
                            <flux:cell>{{ $task['builder'] }}</flux:cell>
                            <flux:cell>{{ $task['subdivision'] }}</flux:cell>
                            <flux:cell>{{ $task['lot'] }}</flux:cell>
                            <flux:cell>
                                <flux:badge color="{{ $task['status'] === 'completed' ? 'zinc' : 'green' }}">{{ $task['status'] }}</flux:badge>
                            </flux:cell>
                            <flux:cell>{{ $task['start_date'] }}</flux:cell>
                            <flux:cell>{{ $task['completed_date'] }}</flux:cell>
                        </flux:row>
                        @endforeach
                    </flux:rows>
                </flux:table>
            </flux:card>
        </div>

        <flux:card class="space-y-6">
                <flux:heading size="lg">Extra Tasks <flux:badge color="lime" inset="top bottom">Optional</flux:badge></flux:heading>
                <flux:table>
                    <flux:columns>
                        <flux:column>Builder</flux:column>
                        <flux:column>Subdivision</flux:column>
                        <flux:column>Lot</flux:column>
                        <flux:column>Status</flux:column>
                        <flux:column>Start Date</flux:column>
                        <flux:column>Completed Date</flux:column>
                    </flux:columns>
                    <flux:rows>
                        @foreach ($extraTasks as $task)
                        <flux:row class="{{ $task['status'] === 'completed' ? 'opacity-50' : '' }}">
                            <flux:cell>{{ $task['builder'] }}</flux:cell>
                            <flux:cell>{{ $task['subdivision'] }}</flux:cell>
                            <flux:cell>{{ $task['lot'] }}</flux:cell>
                            <flux:cell>
                                <flux:badge color="{{ $task['status'] === 'completed' ? 'zinc' : 'green' }}">{{ $task['status'] }}</flux:badge>
                            </flux:cell>
                            <flux:cell>{{ $task['start_date'] }}</flux:cell>
                            <flux:cell>{{ $task['completed_date'] }}</flux:cell>
                        </flux:row>
                        @endforeach
                    </flux:rows>
                </flux:table>
        </flux:card>
    </div>
</div>