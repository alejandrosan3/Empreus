<?php

namespace App\Livewire\Pages;

use Livewire\Component;
use Livewire\Attributes\Computed;
use Carbon\Carbon;
use Flux\DateRange;
use App\Models\{Lot, Task, Builder, StatusType};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
        $cfg = config('fiscal_years.company');
        $startMonth = $cfg['start_month'] ?? 1;
        $startDay = $cfg['start_day'] ?? 1;
        $fyStart = now()->month < $startMonth || (now()->month == $startMonth && now()->day < $startDay)
            ? Carbon::create(now()->year - 1, $startMonth, $startDay)
            : Carbon::create(now()->year, $startMonth, $startDay);
        $fyEnd = $fyStart->copy()->addYear()->subDay();

        $this->presets = [
            'company_fy' => [
                'label' => 'Company Fiscal Year',
                'start' => $fyStart->toDateString(),
                'end'   => $fyEnd->toDateString(),
            ],
        ];

        $companyId = session('company_id');
        $builderIds = Lot::where('company_id', $companyId)
            ->distinct()
            ->pluck('builder_id');

        $builders = Builder::whereIn('id', $builderIds)->get();
        foreach ($builders as $builder) {
            $key = Str::slug($builder->name);
            $cfg = config("fiscal_years.builders.$key");
            $bm = $cfg['start_month'] ?? $startMonth;
            $bd = $cfg['start_day'] ?? $startDay;
            $bStart = now()->month < $bm || (now()->month == $bm && now()->day < $bd)
                ? Carbon::create(now()->year - 1, $bm, $bd)
                : Carbon::create(now()->year, $bm, $bd);
            $bEnd = $bStart->copy()->addYear()->subDay();
            $this->presets['builder_'.$builder->id] = [
                'label' => $builder->name.' Fiscal Year',
                'start' => $bStart->toDateString(),
                'end'   => $bEnd->toDateString(),
            ];
        }
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

        $completedRevenue = Task::where('company_id', $companyId)
            ->whereNotNull('ended_at')
            ->whereBetween('ended_at', [$start, $end])
            ->sum('price');

        $projectedRevenue = Task::where('company_id', $companyId)
            ->whereBetween('scheduled_start_date', [$start, $end])
            ->sum('price');

        $closedLotsBySubdivision = [];
        $rows = Lot::where('company_id', $companyId)
            ->where('status_type_id', $completedId)
            ->whereBetween('created_at', [$start, $end])
            ->with('builder')
            ->select('builder_id', 'subdivision', DB::raw('count(*) as c'))
            ->groupBy('builder_id', 'subdivision')
            ->get();

        foreach ($rows as $row) {
            $builderName = $row->builder->name ?? 'Unknown';
            $closedLotsBySubdivision[$builderName][$row->subdivision] = $row->c;
        }

        return [
            'completed' => $completedLots,
            'open' => $openLots,
            'total' => $totalRangeLots,
            'tasks_finished' => $tasksFinished,
            'completed_revenue' => $completedRevenue,
            'projected_revenue' => $projectedRevenue,
            'subdivisions' => $closedLotsBySubdivision,
        ];
    }

    public function render()
    {
        return view('livewire.pages.statistics', [
                'statistics' => $this->statistics,
            ])
            ->layout('layouts.app');
    }
}
