<div>
    <flux:modal wire:model.self="showModal" class="p-4 min-w-[28rem]">
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold">Edit Tags</h3>
                <flux:modal.close wire:click="close" />
            </div>

            @if(!is_null($taskId))
                <livewire:task.edit-task-tags :taskId="$taskId" :key="'global-edit-tags-'.$taskId" />
            @else
                <flux:text class="text-zinc-500">No task selected.</flux:text>
            @endif
        </div>
    </flux:modal>
</div>
