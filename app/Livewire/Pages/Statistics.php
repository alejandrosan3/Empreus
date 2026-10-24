<?php

namespace App\Livewire\Pages;

use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Carbon\Carbon;
use Flux\DateRange;
use App\Models\{Builder, Team};
use App\Services\StatisticsService;
use Illuminate\Support\Facades\DB;

class Statistics extends Component
{
    public DateRange $range;
    public array $presets = [];
    public string $selectedPreset = '';

    #[Url(as: 'builders')]
    public array $builderIds = [];

    #[Url(as: 'subdivisions')]
    public array $subdivisionNames = [];

    #[Url(as: 'teams')]
    public array $teamIds = [];

    public function mount()
    {
        $this->applyDefaultRange();
        $this->initPresets();
    }

    protected function applyDefaultRange(): void
    {
        // Year to date: long enough to be meaningful, short enough to be current.
        $this->range = new DateRange(Carbon::now()->startOfYear(), Carbon::now()->endOfDay());
    }

    protected function initPresets(): void
    {
        $company = config('fiscal_years.company');

        $this->presets = [
            'company_fy' => [
                'label' => 'Company Fiscal Year',
                'start' => $company['start'],
                'end'   => $company['end'],
            ],
        ];

        $builderConfigs = config('fiscal_years.builders', []);
        foreach ($builderConfigs as $slug => $bcfg) {
            $builder = Builder::whereRaw('LOWER(REPLACE(name, " ", "-")) = ?', [$slug])->first();
            if (! $builder) {
                continue;
            }

            $this->presets['builder_'.$builder->id] = [
                'label' => $builder->name.' Fiscal Year',
                'start' => $bcfg['start'],
                'end'   => $bcfg['end'],
            ];
        }
    }

    public function updatedSelectedPreset(): void
    {
        $this->applyPreset();
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

    public function clearFilters(): void
    {
        $this->reset(['builderIds', 'subdivisionNames', 'teamIds', 'selectedPreset']);
        $this->applyDefaultRange();
    }

    /**
     * Options for the filter dropdowns (only what this company works with).
     */
    #[Computed]
    public function filterOptions(): array
    {
        $companyId = session('company_id');

        return [
            'builders' => Builder::whereHas('lots', fn ($q) => $q->where('company_id', $companyId))
                ->orderBy('name')->pluck('name', 'id')->all(),
            'subdivisions' => DB::table('lots')
                ->where('company_id', $companyId)
                ->when($this->builderIds, fn ($q) => $q->whereIn('builder_id', $this->builderIds))
                ->whereNotNull('subdivision')
                ->distinct()->orderBy('subdivision')->pluck('subdivision')->all(),
            'teams' => Team::orderBy('name')->pluck('name', 'id')->all(),
        ];
    }

    /**
     * Each builder keeps the same color everywhere (pies, map, tables), no
     * matter which filters are on: the builders with the most lots get the
     * eight palette slots, the rest share the "other" gray.
     */
    #[Computed]
    public function builderColors(): array
    {
        return DB::table('lots')
            ->where('company_id', session('company_id'))
            ->select('builder_id', DB::raw('count(*) as c'))
            ->groupBy('builder_id')
            ->orderByDesc('c')->orderBy('builder_id')
            ->limit(8)
            ->pluck('builder_id')
            ->values()
            ->mapWithKeys(fn ($id, $i) => [$id => $i + 1])
            ->all();
    }

    #[Computed]
    public function statistics(): array
    {
        $service = new StatisticsService(
            companyId: (int) session('company_id'),
            start: $this->range->start() ?? Carbon::now()->startOfYear(),
            end: $this->range->end() ?? Carbon::now(),
            builderIds: array_map('intval', $this->builderIds),
            subdivisions: $this->subdivisionNames,
            teamIds: array_map('intval', $this->teamIds),
        );

        return $service->build();
    }

    /**
     * Everything the charts and the map need, in one payload for the browser.
     */
    #[Computed]
    public function chartData(): array
    {
        $s = $this->statistics;
        $colors = $this->builderColors;

        // Builders keep their own color; other pies color slices by size order.
        $builderSlices = array_map(function ($slice) use ($colors) {
            $slice['slot'] = $colors[$slice['key']] ?? 'other';
            return $slice;
        }, StatisticsService::pie($s['builders'], 'earned', 'name', 'id'));

        $pies = [
            'earnedByBuilder'  => ['slices' => $builderSlices, 'money' => true],
            'paidByTeam'       => ['slices' => StatisticsService::pie($s['teams'], 'paid'), 'money' => true],
            'lotsBySubdivision'=> ['slices' => StatisticsService::pie($s['subdivisions'], 'lots', 'label', 'label'), 'money' => false],
            'lotsByZone'       => ['slices' => StatisticsService::pie($s['zones'], 'lots'), 'money' => false],
            'earnedByTaskType' => ['slices' => StatisticsService::pie($s['task_types'], 'earned'), 'money' => true],
            'tasksByStatus'    => ['slices' => StatisticsService::pie($s['statuses'], 'tasks'), 'money' => false],
            'moneySplit'       => ['slices' => array_values(array_filter([
                ['label' => 'Paid to crews', 'value' => $s['totals']['paid_to_crews'], 'key' => 'paid', 'slot' => 2],
                ['label' => 'What we keep', 'value' => max($s['totals']['kept'], 0), 'key' => 'kept', 'slot' => 1],
            ], fn ($x) => $x['value'] > 0)), 'money' => true],
            'paymentStatus'    => ['slices' => array_values(array_filter([
                ['label' => 'Got paid', 'value' => $s['totals']['collected'], 'key' => 'collected', 'slot' => 3],
                ['label' => 'Waiting to be paid', 'value' => $s['totals']['waiting_payment'], 'key' => 'waiting', 'slot' => 4],
            ], fn ($x) => $x['value'] > 0)), 'money' => true],
        ];

        foreach ($pies as &$pie) {
            $total = array_sum(array_column($pie['slices'], 'value'));
            foreach ($pie['slices'] as $i => &$slice) {
                $slice['slot'] ??= $slice['key'] === '__other' ? 'other' : $i + 1;
                $slice['percent'] = $total > 0 ? round($slice['value'] / $total * 100, 1) : 0;
            }
            unset($slice);
            $pie['total'] = round($total, 2);
        }
        unset($pie);

        return [
            'pies'          => $pies,
            'timeline'      => $s['timeline'],
            'map'           => $s['map'],
            'builderColors' => $colors,
        ];
    }

    /**
     * After any filter change, hand the new numbers to the charts and the map.
     */
    public function updated($property): void
    {
        unset($this->statistics, $this->chartData, $this->filterOptions);

        if ($property === 'builderIds') {
            // Drop subdivisions that don't belong to the chosen builders.
            $this->subdivisionNames = array_values(array_intersect(
                $this->subdivisionNames,
                $this->filterOptions['subdivisions']
            ));
            unset($this->statistics, $this->chartData);
        }

        $this->dispatch('statistics-updated', data: $this->chartData);
    }

    public function render()
    {
        return view('livewire.pages.statistics', [
                'statistics' => $this->statistics,
                'chartData'  => $this->chartData,
                'options'    => $this->filterOptions,
            ])
            ->layout('layouts.app');
    }
}
