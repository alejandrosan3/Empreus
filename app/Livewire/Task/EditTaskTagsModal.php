<?php

namespace App\Livewire\Task;

use Livewire\Attributes\On;
use Livewire\Component;
use Flux\Flux;

class EditTaskTagsModal extends Component
{
    public bool $showModal = false;
    public ?int $taskId;

    // Receives { taskId: 123 } from JS
    #[On('open-edit-tags')]
    public function handleOpen(int $taskId=null): void
    {
        $this->taskId = $taskId;
        $this->showModal = true;
    }

    public function close(): void
    {
        $this->showModal = false;
        $this->taskId = null;
    }

    public function updatedShowModal($value)
    {
        if (!$value) {
            // Modal was closed, clean up
            $this->taskId = null;
        }
    }

    public function render()
    {
        return view('livewire.task.edit-task-tags-modal');
    }
}
