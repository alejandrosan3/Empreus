<?php

namespace App\Livewire\Pages;

use Livewire\Component;
use Livewire\Attributes\Computed;
use Carbon\Carbon;
use Flux\DateRange;
use App\Models\{Lot, Task, Builder, StatusType};
use Illuminate\Support\Facades\DB;

class Statistics extends Component
{
    public DateRange $range;
    public array $presets = [];
    public string $selectedPreset = '';

    public function mount()
    {
        $this->applyDefaultRange();
        $this->initPresets();
    }

    protected function applyDefaultRange(): void
    {
        $start = Carbon::now()->startOfWeek()->addWeek();
        $this->range = new DateRange($start, $start->copy()->endOfWeek());
    }

    protected function initPresets(): void
    {
        $fyStart = Carbon::now()->month < 10
            ? Carbon::create(Carbon::now()->year - 1, 10, 1)
            : Carbon::create(Carbon::now()->year, 10, 1);
        $fyEnd = $fyStart->copy()->addYear()->subDay();

        $this->presets = [
            'company_fy' => [
                'label' => 'Company Fiscal Year',
                'start' => $fyStart->toDateString(),
                'end'   => $fyEnd->toDateString(),
            ],
            'dry_fy' => [
                'label' => 'Dry Fiscal Year',
                'start' => $fyStart->toDateString(),
                'end'   => $fyEnd->toDateString(),
            ],
        ];
    }

    public function applyPreset(): void
    {
        if (isset($this->presets[$this->selectedPreset])) {
            $preset = $this->presets[$this->selectedPreset];
            $this->range = new DateRange(
                Carbon::parse($preset['start']),
                Carbon::parse($preset['end'])
            );
        }
    }

    #[Computed]
    public function getStatisticsProperty(): array
    {
        $start = $this->range->start()->startOfDay();
        $end   = $this->range->end()->endOfDay();
        $companyId = session('company_id');

        $completedId = StatusType::where('identifier', 'completed')->value('id');

        $totalRangeLots = Lot::where('company_id', $companyId)
            ->whereBetween('created_at', [$start, $end])
            ->count();
        $completedLots = Lot::where('company_id', $companyId)
            ->where('status_type_id', $completedId)
            ->whereBetween('created_at', [$start, $end])
            ->count();
        $openLots = Lot::where('company_id', $companyId)
            ->where('status_type_id', '!=', $completedId)
            ->count();

        $tasksFinished = Task::where('company_id', $companyId)
            ->whereNotNull('ended_at')
            ->whereBetween('ended_at', [$start, $end])
            ->count();

        $categories = [];
        $series = [];

        $builders = Builder::whereHas('lots', fn($q) => $q->where('company_id', $companyId))->get();

        foreach ($builders as $builder) {
            $counts = Lot::where('company_id', $companyId)
                ->where('builder_id', $builder->id)
                ->whereBetween('created_at', [$start, $end])
                ->select('subdivision', DB::raw('count(*) as c'))
                ->groupBy('subdivision')
                ->pluck('c', 'subdivision')
                ->toArray();

            $categories = array_unique(array_merge($categories, array_keys($counts)));
            $series[$builder->name] = $counts;
        }

        $categories = array_values($categories);
        $chartSeries = [];
        foreach ($series as $name => $data) {
            $chartSeries[] = [
                'name' => $name,
                'data' => array_map(fn($cat) => $data[$cat] ?? 0, $categories),
            ];
        }

        return [
            'completed' => $completedLots,
            'open' => $openLots,
            'total' => $totalRangeLots,
            'tasks_finished' => $tasksFinished,
            'chart' => [
                'categories' => $categories,
                'series' => $chartSeries,
            ],
        ];
    }

    public function render()
    {
        return view('livewire.pages.statistics')
            ->layout('layouts.app');
    }
}
