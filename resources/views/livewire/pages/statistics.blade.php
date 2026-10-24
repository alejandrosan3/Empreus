@php
    $t    = $statistics['totals'];
    $prev = $statistics['previous'];
    $all  = $statistics['all_time'];
    $money = fn ($v, $decimals = 0) => ($v < 0 ? '-$' : '$') . number_format(abs($v), $decimals);
    $date  = fn ($d) => \Carbon\Carbon::parse($d)->format('M j, Y');

    // "12% more than before" / "same as before" in plain words.
    $change = function ($now, $before) {
        if ($before == 0) {
            return $now == 0 ? null : ['dir' => 'up', 'text' => 'new this period'];
        }
        $pct = round(($now - $before) / abs($before) * 100);
        if ($pct == 0) {
            return ['dir' => 'flat', 'text' => 'same as before'];
        }
        return ['dir' => $pct > 0 ? 'up' : 'down', 'text' => abs($pct) . '% ' . ($pct > 0 ? 'more' : 'less') . ' than before'];
    };

    $missingPay = $t['tasks_done'] - $t['tasks_with_pay'];
@endphp

<div class="stats-root space-y-6">
    @vite(['resources/js/statistics.js'])

    <style>
        .stats-root {
            --series-1: #2a78d6; --series-2: #eb6834; --series-3: #1baf7a; --series-4: #eda100;
            --series-5: #e87ba4; --series-6: #008300; --series-7: #6250d6; --series-8: #e34948;
            --series-other: #b5b3ab;
            --ink-primary: #0b0b0b; --ink-secondary: #52514e; --ink-muted: #898781;
            --grid: #e1e0d9; --chart-surface: #ffffff; --good: #006300;
        }
        .dark .stats-root {
            --series-1: #3987e5; --series-2: #d95926; --series-3: #199e70; --series-4: #c98500;
            --series-5: #d55181; --series-6: #008300; --series-7: #9085e9; --series-8: #e66767;
            --series-other: #5f5e59;
            --ink-primary: #f0efec; --ink-secondary: #c3c2b7; --ink-muted: #898781;
            --grid: #2c2c2a; --chart-surface: #27272a; --good: #0ca30c;
        }
        .stats-root .swatch { width: .75rem; height: .75rem; border-radius: 3px; flex: none; }
        .stats-root .num { font-variant-numeric: tabular-nums; }
    </style>

    {{-- Filters --}}
    <flux:card class="space-y-4">
        <div class="flex flex-wrap items-start justify-between gap-2">
            <div>
                <flux:heading size="xl">Statistics</flux:heading>
                <flux:text>
                    Showing <strong>{{ $date($statistics['period']['start']) }} – {{ $date($statistics['period']['end']) }}</strong>.
                    Arrows compare with the same number of days right before
                    ({{ $date($statistics['period']['prev_start']) }} – {{ $date($statistics['period']['prev_end']) }}).
                </flux:text>
            </div>
            <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="clearFilters">Clear filters</flux:button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4">
            <flux:date-picker
                label="Dates"
                mode="range"
                with-presets
                presets="thisWeek lastWeek thisMonth lastMonth last3Months last6Months yearToDate thisYear lastYear"
                wire:model.live="range"
            />

            <flux:select label="Fiscal year" wire:model.live="selectedPreset" placeholder="Pick a fiscal year">
                <flux:select.option value="">Custom dates</flux:select.option>
                @foreach($presets as $key => $preset)
                    <flux:select.option value="{{ $key }}">{{ $preset['label'] }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select label="Builders" variant="listbox" multiple searchable clearable
                         placeholder="All builders" wire:model.live="builderIds">
                @foreach($options['builders'] as $id => $name)
                    <flux:select.option value="{{ $id }}">{{ $name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select label="Subdivisions" variant="listbox" multiple searchable clearable
                         placeholder="All subdivisions" wire:model.live="subdivisionNames">
                @foreach($options['subdivisions'] as $name)
                    <flux:select.option value="{{ $name }}">{{ $name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select label="Teams" variant="listbox" multiple searchable clearable
                         placeholder="All teams" wire:model.live="teamIds">
                @foreach($options['teams'] as $id => $name)
                    <flux:select.option value="{{ $id }}">{{ $name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </flux:card>

    <div wire:loading.delay class="text-sm text-zinc-500">Updating numbers…</div>

    {{-- Money --}}
    <section class="space-y-4">
        <flux:heading size="lg">Money</flux:heading>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4">
            @foreach([
                ['Money earned', $t['earned'], $prev['earned'], 'Price of the jobs we finished.'],
                ['Paid to crews', $t['paid_to_crews'], $prev['paid_to_crews'], 'Crew pay of the jobs we finished.'],
                ['What we keep', $t['kept'], $prev['kept'], $t['kept_percent'] !== null ? "That's {$t['kept_percent']}% of what we earned." : 'Money earned minus paid to crews.'],
                ['Still to earn', $t['still_to_earn'], $prev['still_to_earn'], 'Jobs planned in these dates that are not finished yet.'],
                ['Waiting to be paid', $t['waiting_payment'], $prev['waiting_payment'], 'Finished jobs with no check number yet.'],
            ] as [$label, $value, $before, $help])
                @php($c = $change($value, $before))
                <flux:card class="space-y-1">
                    <flux:text>{{ $label }}</flux:text>
                    <div class="text-3xl font-semibold text-zinc-900 dark:text-white">{{ $money($value) }}</div>
                    @if($c)
                        <div class="text-xs text-zinc-600 dark:text-zinc-300">
                            {{ ['up' => '▲', 'down' => '▼', 'flat' => '■'][$c['dir']] }} {{ $c['text'] }} ({{ $money($before) }})
                        </div>
                    @endif
                    <flux:text size="sm" class="text-zinc-500">{{ $help }}</flux:text>
                </flux:card>
            @endforeach
        </div>

        @if($t['tasks_done'] > 0 && $missingPay > 0)
            <flux:callout icon="information-circle" variant="warning">
                <flux:callout.heading>Crew pay is missing on {{ $missingPay }} of {{ $t['tasks_done'] }} finished jobs</flux:callout.heading>
                <flux:callout.text>
                    Until it's filled in, "Paid to crews" is too low and "What we keep" looks higher than it really is.
                    Add it in the <strong>Crew Pay</strong> column on Tasks Progress.
                </flux:callout.text>
            </flux:callout>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 2xl:grid-cols-4 gap-4">
            @include('livewire.pages.statistics.pie', ['id' => 'earnedByBuilder', 'title' => 'Where the money comes from', 'help' => 'Money earned, by builder.'])
            @include('livewire.pages.statistics.pie', ['id' => 'moneySplit', 'title' => 'Where the money goes', 'help' => 'Of what we earned: paid to crews vs. what we keep.'])
            @include('livewire.pages.statistics.pie', ['id' => 'paidByTeam', 'title' => 'Who we pay', 'help' => 'Crew pay, by team.'])
            @include('livewire.pages.statistics.pie', ['id' => 'paymentStatus', 'title' => 'Did we get paid?', 'help' => 'Finished jobs with a check number vs. without.'])
        </div>

        <flux:card>
            <flux:heading>Money earned and paid to crews, by {{ $statistics['timeline']['unit'] }}</flux:heading>
            <flux:text size="sm" class="mb-3">Hover a bar to see the exact amounts.</flux:text>
            <div class="flex gap-4 text-sm mb-2">
                <span class="flex items-center gap-2"><span class="swatch" style="background: var(--series-1)"></span>Money earned</span>
                <span class="flex items-center gap-2"><span class="swatch" style="background: var(--series-2)"></span>Paid to crews</span>
            </div>
            <div wire:ignore class="h-72"><canvas id="chart-timeline" aria-label="Money earned and paid to crews over time" role="img"></canvas></div>
        </flux:card>
    </section>

    {{-- Work --}}
    <section class="space-y-4">
        <flux:heading size="lg">Work</flux:heading>

        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4">
            @foreach([
                ['Builders', $t['builders'], "worked with in these dates · {$all['builders']} in total"],
                ['Subdivisions', $t['subdivisions'], "worked in · {$all['subdivisions']} in total"],
                ['Zones', $t['zones'], 'cities / ZIP codes we went to'],
                ['Lots', $t['lots'], "worked on · {$all['lots']} in total"],
                ['Jobs finished', $t['tasks_done'], $t['tasks_open'] . ' more planned, not finished'],
                ['Average job', $money($t['avg_per_task']), 'earned per finished job'],
            ] as [$label, $value, $help])
                <flux:card class="space-y-1">
                    <flux:text>{{ $label }}</flux:text>
                    <div class="text-3xl font-semibold text-zinc-900 dark:text-white">{{ is_numeric($value) ? number_format($value) : $value }}</div>
                    <flux:text size="sm" class="text-zinc-500">{{ $help }}</flux:text>
                </flux:card>
            @endforeach
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 2xl:grid-cols-4 gap-4">
            @include('livewire.pages.statistics.pie', ['id' => 'lotsBySubdivision', 'title' => 'Lots by subdivision', 'help' => 'How many lots we worked on in each subdivision.'])
            @include('livewire.pages.statistics.pie', ['id' => 'lotsByZone', 'title' => 'Lots by zone', 'help' => 'Read from the lot address (city and ZIP).'])
            @include('livewire.pages.statistics.pie', ['id' => 'earnedByTaskType', 'title' => 'Money by type of job', 'help' => 'Money earned, by task type.'])
            @include('livewire.pages.statistics.pie', ['id' => 'tasksByStatus', 'title' => 'Jobs by status', 'help' => 'All jobs in these dates, finished or not.'])
        </div>
    </section>

    {{-- Map --}}
    <section class="space-y-4">
        <flux:card class="space-y-3">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <div>
                    <flux:heading size="lg">Where we work</flux:heading>
                    <flux:text size="sm">
                        Each circle is a subdivision, colored by builder; bigger circles mean more lots. Click one for details.
                        {{ $chartData['map']['lots_located'] }} of {{ $chartData['map']['lots_total'] }} lots in these dates are on the map.
                        @if($chartData['map']['lots_located'] < $chartData['map']['lots_total'])
                            The rest are located automatically each night, for free.
                        @endif
                    </flux:text>
                </div>
                <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-200" wire:ignore>
                    <input type="checkbox" id="stats-map-lots" class="rounded border-zinc-300">
                    Show each lot
                </label>
            </div>
            <div wire:ignore>
                <div id="stats-map" class="w-full h-[520px] rounded-lg z-0" aria-label="Map of subdivisions"></div>
            </div>
        </flux:card>
    </section>

    {{-- Detail tables --}}
    <section class="space-y-4">
        <flux:heading size="lg">Details</flux:heading>

        <flux:card>
            <flux:heading>Builders</flux:heading>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Builder</flux:table.column>
                    <flux:table.column align="end">Subdivisions</flux:table.column>
                    <flux:table.column align="end">Lots</flux:table.column>
                    <flux:table.column align="end">Jobs finished</flux:table.column>
                    <flux:table.column align="end">Earned</flux:table.column>
                    <flux:table.column align="end">Paid to crews</flux:table.column>
                    <flux:table.column align="end">We keep</flux:table.column>
                    <flux:table.column align="end">Still to earn</flux:table.column>
                    <flux:table.column align="end">Waiting to be paid</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse($statistics['builders'] as $b)
                        <flux:table.row :key="'b'.$b['id']">
                            <flux:table.cell>
                                <span class="flex items-center gap-2">
                                    <span class="swatch" style="background: var(--series-{{ $chartData['builderColors'][$b['id']] ?? 'other' }})"></span>
                                    {{ $b['name'] }}
                                </span>
                            </flux:table.cell>
                            <flux:table.cell align="end" class="num">{{ $b['subdivisions'] }}</flux:table.cell>
                            <flux:table.cell align="end" class="num">{{ $b['lots'] }}</flux:table.cell>
                            <flux:table.cell align="end" class="num">{{ $b['tasks_done'] }}</flux:table.cell>
                            <flux:table.cell align="end" class="num">{{ $money($b['earned']) }}</flux:table.cell>
                            <flux:table.cell align="end" class="num">{{ $money($b['paid_to_crews']) }}</flux:table.cell>
                            <flux:table.cell align="end" class="num">{{ $money($b['kept']) }}</flux:table.cell>
                            <flux:table.cell align="end" class="num">{{ $money($b['still_to_earn']) }}</flux:table.cell>
                            <flux:table.cell align="end" class="num">{{ $money($b['waiting_payment']) }}</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row><flux:table.cell colspan="9">No work in these dates.</flux:table.cell></flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>

        <flux:card x-data="{ q: '', all: false }">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <flux:heading>Subdivisions</flux:heading>
                <div class="w-64"><flux:input size="sm" icon="magnifying-glass" placeholder="Find a subdivision" x-model="q" /></div>
            </div>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Subdivision</flux:table.column>
                    <flux:table.column>Builder</flux:table.column>
                    <flux:table.column>Zone</flux:table.column>
                    <flux:table.column align="end">Lots</flux:table.column>
                    <flux:table.column align="end">Jobs finished</flux:table.column>
                    <flux:table.column align="end">Jobs to do</flux:table.column>
                    <flux:table.column align="end">Earned</flux:table.column>
                    <flux:table.column align="end">Paid to crews</flux:table.column>
                    <flux:table.column align="end">We keep</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse($statistics['subdivisions'] as $i => $s)
                        <flux:table.row :key="'s'.$i"
                            x-show="(all || {{ $i }} < 15 || q) && (!q || {{ \Illuminate\Support\Js::from(strtolower($s['name'].' '.$s['builder'].' '.$s['zone'])) }}.includes(q.toLowerCase()))">
                            <flux:table.cell>{{ $s['name'] }}</flux:table.cell>
                            <flux:table.cell>{{ $s['builder'] }}</flux:table.cell>
                            <flux:table.cell>{{ $s['zone'] }}</flux:table.cell>
                            <flux:table.cell align="end" class="num">{{ $s['lots'] }}</flux:table.cell>
                            <flux:table.cell align="end" class="num">{{ $s['tasks_done'] }}</flux:table.cell>
                            <flux:table.cell align="end" class="num">{{ $s['tasks_open'] }}</flux:table.cell>
                            <flux:table.cell align="end" class="num">{{ $money($s['earned']) }}</flux:table.cell>
                            <flux:table.cell align="end" class="num">{{ $money($s['paid_to_crews']) }}</flux:table.cell>
                            <flux:table.cell align="end" class="num">{{ $money($s['kept']) }}</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row><flux:table.cell colspan="9">No work in these dates.</flux:table.cell></flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
            @if(count($statistics['subdivisions']) > 15)
                <flux:button size="sm" variant="ghost" class="mt-2" x-on:click="all = !all"
                             x-text="all ? 'Show top 15' : 'Show all {{ count($statistics['subdivisions']) }}'"></flux:button>
            @endif
        </flux:card>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <flux:card>
                <flux:heading>Teams we paid</flux:heading>
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Team</flux:table.column>
                        <flux:table.column align="end">Jobs</flux:table.column>
                        <flux:table.column align="end">Paid</flux:table.column>
                        <flux:table.column align="end">Per job</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse($statistics['teams'] as $i => $team)
                            <flux:table.row :key="'t'.$i">
                                <flux:table.cell>{{ $team['name'] }}</flux:table.cell>
                                <flux:table.cell align="end" class="num">{{ $team['tasks'] }}</flux:table.cell>
                                <flux:table.cell align="end" class="num">{{ $money($team['paid']) }}</flux:table.cell>
                                <flux:table.cell align="end" class="num">{{ $money($team['avg_per_task']) }}</flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row><flux:table.cell colspan="4">No crew pay recorded in these dates.</flux:table.cell></flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </flux:card>

            <flux:card>
                <flux:heading>Workers we paid</flux:heading>
                <flux:text size="sm">A job's crew pay is split evenly between the workers on it.</flux:text>
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Worker</flux:table.column>
                        <flux:table.column align="end">Jobs</flux:table.column>
                        <flux:table.column align="end">Paid</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse($statistics['workers'] as $i => $w)
                            <flux:table.row :key="'w'.$i">
                                <flux:table.cell>{{ $w['name'] }}</flux:table.cell>
                                <flux:table.cell align="end" class="num">{{ $w['tasks'] }}</flux:table.cell>
                                <flux:table.cell align="end" class="num">{{ $money($w['paid']) }}</flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row><flux:table.cell colspan="3">No workers on paid jobs in these dates.</flux:table.cell></flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </flux:card>

            <flux:card class="lg:col-span-2">
                <flux:heading>Zones</flux:heading>
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Zone</flux:table.column>
                        <flux:table.column align="end">Subdivisions</flux:table.column>
                        <flux:table.column align="end">Lots</flux:table.column>
                        <flux:table.column align="end">Earned</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse($statistics['zones'] as $i => $z)
                            <flux:table.row :key="'z'.$i">
                                <flux:table.cell>{{ $z['name'] }}</flux:table.cell>
                                <flux:table.cell align="end" class="num">{{ $z['subdivisions'] }}</flux:table.cell>
                                <flux:table.cell align="end" class="num">{{ $z['lots'] }}</flux:table.cell>
                                <flux:table.cell align="end" class="num">{{ $money($z['earned']) }}</flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row><flux:table.cell colspan="4">No work in these dates.</flux:table.cell></flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </flux:card>
        </div>
    </section>

    <script type="application/json" id="statistics-data">@json($chartData)</script>
</div>
