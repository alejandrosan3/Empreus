<div>
    <div class="py-4 sm:py-6 lg:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Welcome Section -->
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <flux:heading size="xl" class="text-gray-900 dark:text-white">
                        Welcome back, {{ auth()->user()->name }}!
                    </flux:heading>
                </div>
                <div class="mt-4 sm:mt-0">
                    <flux:button variant="outline" wire:click="refreshStats" class="w-full sm:w-auto">
                        <flux:icon.arrow-path class="h-4 w-4 mr-2" />
                        Refresh Stats
                    </flux:button>
                </div>
            </div>

            <!-- Quick Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8">
                <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-gray-200 dark:border-zinc-700 p-4 sm:p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <flux:icon.home class="h-8 w-8 text-blue-600" />
                        </div>
                        <div class="ml-4">
                            <div class="flex items-center">
                                <flux:heading size="sm" class="text-gray-500 dark:text-gray-400">Active Lots</flux:heading>
                                <flux:tooltip content="Lots that have confirmed or in-progress tasks">
                                    <flux:icon.information-circle class="h-4 w-4 ml-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" />
                                </flux:tooltip>
                            </div>
                            <flux:heading size="lg" class="text-gray-900 dark:text-white">{{ $activeLots }}</flux:heading>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-gray-200 dark:border-zinc-700 p-4 sm:p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <flux:icon.queue-list class="h-8 w-8 text-green-600" />
                        </div>
                        <div class="ml-4">
                            <div class="flex items-center">
                                <flux:heading size="sm" class="text-gray-500 dark:text-gray-400">Pending Tasks</flux:heading>
                                <flux:tooltip content="Tasks that are pending approval or confirmed but not yet started">
                                    <flux:icon.information-circle class="h-4 w-4 ml-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" />
                                </flux:tooltip>
                            </div>
                            <flux:heading size="lg" class="text-gray-900 dark:text-white">{{ $pendingTasks }}</flux:heading>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-gray-200 dark:border-zinc-700 p-4 sm:p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <flux:icon.user-group class="h-8 w-8 text-purple-600" />
                        </div>
                        <div class="ml-4">
                            <div class="flex items-center">
                                <flux:heading size="sm" class="text-gray-500 dark:text-gray-400">Active Teams</flux:heading>
                                <flux:tooltip content="Teams that have workers assigned and are available for task assignment">
                                    <flux:icon.information-circle class="h-4 w-4 ml-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" />
                                </flux:tooltip>
                            </div>
                            <flux:heading size="lg" class="text-gray-900 dark:text-white">{{ $activeTeams }}</flux:heading>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-gray-200 dark:border-zinc-700 p-4 sm:p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <flux:icon.chart-bar class="h-8 w-8 text-orange-600" />
                        </div>
                        <div class="ml-4">
                            <div class="flex items-center">
                                <flux:heading size="sm" class="text-gray-500 dark:text-gray-400">Completed Today</flux:heading>
                                <flux:tooltip content="Tasks that were completed today (marked as completed with end date today)">
                                    <flux:icon.information-circle class="h-4 w-4 ml-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" />
                                </flux:tooltip>
                            </div>
                            <flux:heading size="lg" class="text-gray-900 dark:text-white">{{ $completedToday }}</flux:heading>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-gray-200 dark:border-zinc-700 p-4 sm:p-6">
                <flux:heading size="lg" class="text-gray-900 dark:text-white mb-4">Quick Actions</flux:heading>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <flux:button variant="outline" :href="route('task-scheduler')" class="w-full justify-start">
                        <flux:icon.calendar class="h-5 w-5 mr-2" />
                        View Task Scheduler
                    </flux:button>
                    
                    <flux:button variant="outline" :href="route('lot-progress')" class="w-full justify-start">
                        <flux:icon.clipboard-document-check class="h-5 w-5 mr-2" />
                        Track Lot Progress
                    </flux:button>
                    
                    <flux:button variant="outline" :href="route('task-create')" class="w-full justify-start">
                        <flux:icon.plus class="h-5 w-5 mr-2" />
                        Create New Task
                    </flux:button>
                </div>
            </div>
        </div>
    </div>
</div>
