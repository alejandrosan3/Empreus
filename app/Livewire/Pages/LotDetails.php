<?php
namespace App\Livewire\Pages;


use Livewire\Component;
use App\Models\Lot;


class LotDetails extends Component
{
    public $tasks = [];
    public $lot;
    public $viaLiveWire;


    public function mount($lot_id = null, $lot = null)
    {
        if ($lot) {
            // Use the passed lot object directly
            $this->lot = $lot;
        } elseif ($lot_id) {
            // Fetch the lot using the ID from the route
            $this->lot = Lot::with(['builder', 'supervisor', 'tasks.taskType', 'tasks.statusType','tasks.taskNotes'])->findOrFail($lot_id);
        } else {
            // Throw an exception if neither parameter is provided
            throw new \Exception('No lot provided');
        }

    
        // Group tasks by type dynamically
        $this->tasks = [
            'standard' => $this->lot->tasks->where('is_extra', false),
            'extra' => $this->lot->tasks->where('is_extra', true),
        ];
    }

    public function render()
    {
        //dd($this->tasks);
        return view('livewire.pages.lot-details', [
            'lot' => $this->lot,
            'tasks' => $this->tasks,
            
        ])->layout('layouts.app');
    }
}