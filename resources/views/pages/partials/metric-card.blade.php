@php
    $layout = $layout ?? 'vertical';
    $defKind = $m['def'] ?? 'donut';
    $palette = $m['palette'] ?? ['#0069a8', '#00a6f4', '#74d4ff', '#182654', '#0284c7', '#64748b'];
    $vals = array_column($m['data'], 'v');
    $totalSum = array_sum($vals);
    $maxVal = max($vals ?: [1]);
@endphp

@if($layout === 'split')
    <article class="sd-card sd-card-hover flex flex-col p-5 sm:p-6"
             data-donut-card
             data-default-total="{{ $m['total'] }}"
             data-default-sub="{{ $m['unit'] }}">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2.5">
                    <span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background: {{ $palette[0] }}"></span>
                    <h3 class="truncate text-base font-bold tracking-tight text-slate-900" title="{{ $m['title'] }}">
                        {{ $m['title'] }}
                    </h3>
                </div>
                @if(! empty($m['trend']))
                    <span class="mt-2 inline-flex items-center rounded-md px-2.5 py-0.5 text-[11px] font-bold tabular-nums {{ $m['trend']['up'] ? 'bg-[#ECFDF5] text-[#047857]' : 'bg-[#FEF2F2] text-[#B91C1C]' }}">
                        {{ $m['trend']['up'] ? '▲' : '▼' }} {{ $m['trend']['v'] }}
                    </span>
                @endif
            </div>

            <select id="metricKind_{{ $cardId }}"
                    onchange="updateMetricCard('{{ $cardId }}', this.value)"
                    title="Ubah visualisasi"
                    aria-label="Jenis grafik {{ $m['title'] }}"
                    class="min-h-[40px] shrink-0 rounded-xl border border-slate-200 bg-[#f6f9fc] px-3 py-1.5 text-xs font-semibold text-[#334155] outline-none transition-colors hover:border-[#0069a8] focus:border-[#0069a8]">
                <option value="number" {{ $defKind === 'number' ? 'selected' : '' }}>Angka</option>
                <option value="donut" {{ $defKind === 'donut' ? 'selected' : '' }}>Donat</option>
                <option value="pie" {{ $defKind === 'pie' ? 'selected' : '' }}>Pai</option>
                <option value="bar" {{ $defKind === 'bar' ? 'selected' : '' }}>Batang</option>
                <option value="hbar" {{ $defKind === 'hbar' ? 'selected' : '' }}>Horizontal</option>
                <option value="line" {{ $defKind === 'line' ? 'selected' : '' }}>Garis</option>
                <option value="area" {{ $defKind === 'area' ? 'selected' : '' }}>Area</option>
                <option value="radar" {{ $defKind === 'radar' ? 'selected' : '' }}>Radar</option>
            </select>
        </div>

        <div class="mt-5 grid grid-cols-1 items-center gap-6 sm:grid-cols-[190px_1fr]">
            <div>
                <div class="relative mx-auto h-44 w-full max-w-[200px]">
                    <div id="metricRenderSlot_{{ $cardId }}" class="h-full w-full"></div>
                    <div id="metricDonutCenter_{{ $cardId }}" class="pointer-events-none absolute inset-0 grid place-items-center text-center {{ $defKind === 'donut' ? '' : 'hidden' }}">
                        <div class="max-w-[116px] px-1">
                            <p data-donut-total class="text-2xl font-extrabold leading-none tracking-tight tabular-nums text-slate-900">{{ $m['total'] }}</p>
                            <p data-donut-sub class="mt-1 truncate text-[11px] font-semibold text-[#475569]">{{ $m['unit'] }}</p>
                        </div>
                    </div>
                </div>

                <p id="metricNonCircTotal_{{ $cardId }}" class="mt-2 text-center text-xs font-medium text-[#475569] {{ in_array($defKind, ['donut', 'pie'], true) ? 'hidden' : '' }}">
                    <span class="text-base font-extrabold tracking-tight tabular-nums text-slate-900">{{ $m['total'] }}</span> {{ $m['unit'] }}
                </p>
            </div>

            <ul class="space-y-3 text-[13px]">
                @foreach($m['data'] as $i => $d)
                    @php
                        $pct = $totalSum > 0 ? number_format(($d['v'] / $totalSum) * 100, 1, ',', '') . '%' : '0%';
                        $barW = $maxVal > 0 ? max(4, round(($d['v'] / $maxVal) * 100, 1)) : 0;
                        $color = $palette[$i % count($palette)];
                    @endphp
                    <li>
                        <div class="flex items-center justify-between gap-2">
                            <span class="flex min-w-0 items-center gap-2">
                                <span class="h-2.5 w-2.5 shrink-0 rounded-[3px]" style="background: {{ $color }}"></span>
                                <span class="truncate font-medium text-slate-700" title="{{ $d['n'] }}">{{ $d['n'] }}</span>
                            </span>
                            <span class="shrink-0 font-mono text-xs font-semibold tabular-nums text-slate-800">
                                {{ number_format((float) $d['v'], 0, ',', '.') }}
                                <span class="text-[#475569]">({{ $pct }})</span>
                            </span>
                        </div>
                        <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-slate-100" aria-hidden="true">
                            <div class="h-full rounded-full" style="width: {{ $barW }}%; background: {{ $color }}"></div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="mt-auto pt-5">
            <div class="flex flex-wrap items-center justify-between gap-4 border-t border-slate-100 pt-4">
                @if(! empty($m['foot']))
                    <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-xs text-[#475569]">
                        @foreach($m['foot'] as $foot)
                            <div>
                                <span>{{ $foot[0] }}</span>
                                <p class="text-sm font-bold tabular-nums text-slate-900">{{ $foot[1] }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-xs font-medium text-[#475569]">
                        Total <span class="font-bold tabular-nums text-slate-900">{{ $m['total'] }}</span> {{ $m['unit'] }}
                    </p>
                @endif

                <button type="button"
                        onclick='openMetricDetail(@json($m))'
                        class="ml-auto inline-flex min-h-[42px] items-center justify-center rounded-xl border border-slate-200 bg-[#f6f9fc] px-4 py-2 text-sm font-bold text-[#0069a8] transition-colors hover:border-[#0069a8] hover:bg-[#f0f9ff]">
                    Lihat rincian agregat
                </button>
            </div>
        </div>
    </article>
@elseif($layout === 'spotlight')
    <article class="sd-card sd-card-hover p-5 sm:p-7"
             data-donut-card
             data-default-total="{{ $m['total'] }}"
             data-default-sub="{{ $m['unit'] }}">
        <div class="grid grid-cols-1 items-center gap-6 lg:grid-cols-[240px_1fr_250px] lg:gap-8">
            <div>
                <div class="relative mx-auto h-44 w-full max-w-[220px]">
                    <div id="metricRenderSlot_{{ $cardId }}" class="h-full w-full"></div>
                    <div id="metricDonutCenter_{{ $cardId }}" class="pointer-events-none absolute inset-0 grid place-items-center text-center {{ $defKind === 'donut' ? '' : 'hidden' }}">
                        <div class="max-w-[120px] px-1">
                            <p data-donut-total class="text-3xl font-extrabold leading-none tracking-tight tabular-nums text-slate-900">{{ $m['total'] }}</p>
                            <p data-donut-sub class="mt-1 truncate text-xs font-semibold text-[#475569]">{{ $m['unit'] }}</p>
                        </div>
                    </div>
                </div>
                <p id="metricNonCircTotal_{{ $cardId }}" class="mt-2 text-center text-xs font-medium text-[#475569] {{ in_array($defKind, ['donut', 'pie'], true) ? 'hidden' : '' }}">
                    <span class="text-lg font-extrabold tracking-tight tabular-nums text-slate-900">{{ $m['total'] }}</span> {{ $m['unit'] }}
                </p>
            </div>

            <div class="min-w-0">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2.5">
                        <span class="h-3 w-3 shrink-0 rounded-full" style="background: {{ $palette[0] }}"></span>
                        <h3 class="text-lg font-extrabold tracking-tight text-slate-900">{{ $m['title'] }}</h3>
                    </div>

                    <select id="metricKind_{{ $cardId }}"
                            onchange="updateMetricCard('{{ $cardId }}', this.value)"
                            title="Ubah visualisasi"
                            aria-label="Jenis grafik {{ $m['title'] }}"
                            class="min-h-[40px] rounded-xl border border-slate-200 bg-[#f6f9fc] px-3 py-1.5 text-xs font-semibold text-[#334155] outline-none transition-colors hover:border-[#0069a8] focus:border-[#0069a8]">
                        <option value="number" {{ $defKind === 'number' ? 'selected' : '' }}>Angka</option>
                        <option value="donut" {{ $defKind === 'donut' ? 'selected' : '' }}>Donat</option>
                        <option value="pie" {{ $defKind === 'pie' ? 'selected' : '' }}>Pai</option>
                        <option value="bar" {{ $defKind === 'bar' ? 'selected' : '' }}>Batang</option>
                        <option value="hbar" {{ $defKind === 'hbar' ? 'selected' : '' }}>Horizontal</option>
                        <option value="line" {{ $defKind === 'line' ? 'selected' : '' }}>Garis</option>
                        <option value="area" {{ $defKind === 'area' ? 'selected' : '' }}>Area</option>
                        <option value="radar" {{ $defKind === 'radar' ? 'selected' : '' }}>Radar</option>
                    </select>
                </div>

                <ul class="mt-4 grid grid-cols-1 gap-x-6 gap-y-3 text-[13px] sm:grid-cols-2">
                    @foreach($m['data'] as $i => $d)
                        @php
                            $pct = $totalSum > 0 ? number_format(($d['v'] / $totalSum) * 100, 1, ',', '') . '%' : '0%';
                            $barW = $maxVal > 0 ? max(4, round(($d['v'] / $maxVal) * 100, 1)) : 0;
                            $color = $palette[$i % count($palette)];
                        @endphp
                        <li>
                            <div class="flex items-center justify-between gap-2">
                                <span class="flex min-w-0 items-center gap-2">
                                    <span class="h-2.5 w-2.5 shrink-0 rounded-[3px]" style="background: {{ $color }}"></span>
                                    <span class="truncate font-medium text-slate-700">{{ $d['n'] }}</span>
                                </span>
                                <span class="shrink-0 font-mono text-xs font-semibold tabular-nums text-slate-800">{{ number_format((float) $d['v'], 0, ',', '.') }} ({{ $pct }})</span>
                            </div>
                            <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-slate-100" aria-hidden="true">
                                <div class="h-full rounded-full" style="width: {{ $barW }}%; background: {{ $color }}"></div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="flex flex-col justify-between gap-4 border-t border-slate-100 pt-4 lg:border-t-0 lg:border-l lg:pt-0 lg:pl-7">
                <div>
                    <p class="text-xs font-bold tracking-wider text-[#475569] uppercase">Ringkasan Mitra</p>
                    <p class="mt-1 text-3xl font-extrabold tracking-tight tabular-nums text-slate-900">{{ $m['total'] }}</p>
                    <p class="text-xs font-medium text-[#475569]">{{ $m['unit'] }}</p>
                    @if(! empty($m['foot']))
                        <div class="mt-3 space-y-1.5 border-t border-slate-100 pt-3 text-xs text-[#475569]">
                            @foreach($m['foot'] as $foot)
                                <div class="flex items-center justify-between">
                                    <span>{{ $foot[0] }}</span>
                                    <span class="font-bold tabular-nums text-slate-900">{{ $foot[1] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <button type="button"
                        onclick='openMetricDetail(@json($m))'
                        class="inline-flex min-h-[44px] w-full items-center justify-center rounded-xl border border-slate-200 bg-[#f6f9fc] px-4 py-2.5 text-sm font-bold text-[#0069a8] transition-colors hover:border-[#0069a8] hover:bg-[#f0f9ff]">
                    Lihat rincian agregat
                </button>
            </div>
        </div>
    </article>
@else
    <article class="sd-card sd-card-hover flex flex-col p-5 sm:p-6"
             data-donut-card
             data-default-total="{{ $m['total'] }}"
             data-default-sub="{{ $m['unit'] }}">
        <div class="flex items-start justify-between gap-2">
            <h3 class="flex min-w-0 items-center gap-2 font-bold tracking-tight text-slate-900" title="{{ $m['title'] }}">
                <span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background: {{ $palette[0] }}"></span>
                <span class="truncate">{{ $m['title'] }}</span>
            </h3>

            <select id="metricKind_{{ $cardId }}"
                    onchange="updateMetricCard('{{ $cardId }}', this.value)"
                    title="Ubah visualisasi"
                    aria-label="Jenis grafik {{ $m['title'] }}"
                    class="min-h-[38px] shrink-0 rounded-xl border border-slate-200 bg-[#f6f9fc] px-2.5 py-1 text-xs font-semibold text-[#334155] outline-none transition-colors hover:border-[#0069a8] focus:border-[#0069a8]">
                <option value="number" {{ $defKind === 'number' ? 'selected' : '' }}>Angka</option>
                <option value="donut" {{ $defKind === 'donut' ? 'selected' : '' }}>Donat</option>
                <option value="pie" {{ $defKind === 'pie' ? 'selected' : '' }}>Pai</option>
                <option value="bar" {{ $defKind === 'bar' ? 'selected' : '' }}>Batang</option>
                <option value="hbar" {{ $defKind === 'hbar' ? 'selected' : '' }}>Horizontal</option>
                <option value="line" {{ $defKind === 'line' ? 'selected' : '' }}>Garis</option>
                <option value="area" {{ $defKind === 'area' ? 'selected' : '' }}>Area</option>
                <option value="radar" {{ $defKind === 'radar' ? 'selected' : '' }}>Radar</option>
            </select>
        </div>

        @if(! empty($m['trend']))
            <div class="mt-2">
                <span class="inline-flex items-center rounded-md px-2.5 py-0.5 text-[11px] font-bold tabular-nums {{ $m['trend']['up'] ? 'bg-[#ECFDF5] text-[#047857]' : 'bg-[#FEF2F2] text-[#B91C1C]' }}">
                    {{ $m['trend']['up'] ? '▲' : '▼' }} {{ $m['trend']['v'] }}
                </span>
            </div>
        @endif

        <div class="relative mt-4 h-44 w-full">
            <div id="metricRenderSlot_{{ $cardId }}" class="h-full w-full"></div>
            <div id="metricDonutCenter_{{ $cardId }}" class="pointer-events-none absolute inset-0 grid place-items-center text-center {{ $defKind === 'donut' ? '' : 'hidden' }}">
                <div class="max-w-[116px] px-1">
                    <p data-donut-total class="text-3xl font-extrabold leading-none tracking-tight tabular-nums text-slate-900">{{ $m['total'] }}</p>
                    <p data-donut-sub class="mt-1 truncate text-xs font-semibold text-[#475569]">{{ $m['unit'] }}</p>
                </div>
            </div>
        </div>

        <p id="metricNonCircTotal_{{ $cardId }}" class="mt-2 text-xs font-medium text-[#475569] {{ in_array($defKind, ['donut', 'pie'], true) ? 'hidden' : '' }}">
            <span class="text-lg font-extrabold tracking-tight tabular-nums text-slate-900">{{ $m['total'] }}</span> {{ $m['unit'] }}
        </p>

        <ul class="mt-4 space-y-2.5 text-[13px]">
            @foreach($m['data'] as $i => $d)
                @php
                    $pct = $totalSum > 0 ? number_format(($d['v'] / $totalSum) * 100, 1, ',', '') . '%' : '0%';
                    $barW = $maxVal > 0 ? max(4, round(($d['v'] / $maxVal) * 100, 1)) : 0;
                    $color = $palette[$i % count($palette)];
                @endphp
                <li>
                    <div class="flex items-center justify-between gap-2">
                        <span class="flex min-w-0 items-center gap-2">
                            <span class="h-2.5 w-2.5 shrink-0 rounded-[3px]" style="background: {{ $color }}"></span>
                            <span class="truncate font-medium text-slate-700" title="{{ $d['n'] }}">{{ $d['n'] }}</span>
                        </span>
                        <span class="shrink-0 font-mono text-xs font-semibold tabular-nums text-slate-800">{{ $pct }}</span>
                    </div>
                    <div class="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-slate-100" aria-hidden="true">
                        <div class="h-full rounded-full" style="width: {{ $barW }}%; background: {{ $color }}"></div>
                    </div>
                </li>
            @endforeach
        </ul>

        <div class="mt-auto pt-4">
            @if(! empty($m['foot']))
                <div class="mb-4 grid grid-cols-2 gap-3 border-t border-slate-100 pt-3 text-xs leading-snug text-[#475569]">
                    @foreach($m['foot'] as $fIdx => $foot)
                        <div class="min-w-0 {{ $fIdx ? 'text-right' : '' }}">
                            <span>{{ $foot[0] }}</span>
                            <p class="text-sm font-bold tabular-nums text-slate-900">{{ $foot[1] }}</p>
                        </div>
                    @endforeach
                </div>
            @endif

            <button type="button"
                    onclick='openMetricDetail(@json($m))'
                    class="min-h-[44px] w-full rounded-xl border border-slate-200 bg-[#f6f9fc] py-2 text-sm font-bold text-[#0069a8] transition-colors hover:border-[#0069a8] hover:bg-[#f0f9ff]">
                Lihat rincian agregat
            </button>
        </div>
    </article>
@endif
