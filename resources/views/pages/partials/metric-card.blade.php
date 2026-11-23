@php
    $defKind = $m['def'] ?? 'donut';
    $palette = $m['palette'] ?? ['#4472d0', '#0ea5e9', '#a0bbea', '#0f172a', '#7dd3fc', '#94a3b8'];
    $totalSum = array_sum(array_column($m['data'], 'v'));
@endphp

<article class="flex flex-col rounded-2xl border border-slate-200 border-t-4 bg-white p-5 shadow-sm transition hover:shadow-md"
         style="border-top-color: {{ $palette[0] }}">
    <!-- Title -->
    <h3 class="flex items-center gap-2 font-bold text-slate-900 text-sm" title="{{ $m['title'] }}">
        <span class="h-2 w-2 shrink-0 rounded-full" style="background: {{ $palette[0] }}"></span>
        <span class="truncate">{{ $m['title'] }}</span>
    </h3>

    <!-- Trend & Kind select -->
    <div class="mt-3 flex h-7 items-center justify-between gap-2">
        @if (isset($m['trend']))
            <span class="inline-flex shrink-0 items-center gap-1 text-xs font-bold {{ $m['trend']['up'] ? 'text-emerald-600' : 'text-rose-600' }}">
                {{ $m['trend']['up'] ? '▲' : '▼' }} {{ $m['trend']['v'] }}
            </span>
        @else
            <span></span>
        @endif

        <select id="metricKind_{{ $cardId }}"
                onchange="updateMetricCard('{{ $cardId }}', this.value)"
                title="Ubah visualisasi"
                class="rounded-md border border-slate-200 bg-slate-50 px-2 py-1 text-[11px] font-medium text-slate-600 outline-none hover:border-sky-300 cursor-pointer">
            <option value="donut" {{ $defKind === 'donut' ? 'selected' : '' }}>Donat</option>
            <option value="pie" {{ $defKind === 'pie' ? 'selected' : '' }}>Pai</option>
            <option value="bar" {{ $defKind === 'bar' ? 'selected' : '' }}>Batang</option>
            <option value="hbar">Horizontal</option>
            <option value="line" {{ $defKind === 'line' ? 'selected' : '' }}>Garis</option>
            <option value="radar">Radar</option>
            <option value="number">Angka</option>
        </select>
    </div>

    <!-- Chart container -->
    <div class="relative mt-3 h-48 w-full" id="metricChartBox_{{ $cardId }}">
        <!-- Number mode container -->
        <div id="metricNumberWrap_{{ $cardId }}" class="hidden h-full flex-col items-center justify-center text-center">
            <p class="text-4xl font-extrabold tabular-nums" style="color: {{ $palette[0] }}">
                {{ number_format($totalSum, 0, ',', '.') }}
            </p>
            <p class="mt-2 text-xs text-slate-500">
                Total nilai · {{ count($m['data']) }} kelompok
            </p>
        </div>

        <!-- Visual Render Slot (SVG / Canvas) -->
        <div id="metricRenderSlot_{{ $cardId }}" class="h-full w-full">
            <canvas id="metricCanvas_{{ $cardId }}" class="h-full w-full"></canvas>
        </div>

        <!-- Donut center total -->
        <div id="metricDonutCenter_{{ $cardId }}" class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center text-center {{ $defKind === 'donut' ? '' : 'hidden' }}">
            <p class="text-3xl font-black leading-none text-slate-900 tracking-tight">{{ $m['total'] }}</p>
            <p class="mt-1 text-xs font-medium text-slate-500">{{ $m['unit'] }}</p>
        </div>
    </div>

    <!-- Non-circular total caption -->
    <p id="metricNonCircTotal_{{ $cardId }}" class="mt-2 text-xs text-slate-500 {{ $defKind === 'donut' || $defKind === 'pie' ? 'hidden' : '' }}">
        <span class="text-lg font-extrabold text-slate-900">{{ $m['total'] }}</span> {{ $m['unit'] }}
    </p>

    <!-- Legend breakdown -->
    <ul id="metricLegend_{{ $cardId }}" class="mt-4 space-y-2 text-[13px]">
        @foreach ($m['data'] as $i => $d)
            @php
                $pct = $totalSum > 0 ? number_format(($d['v'] / $totalSum) * 100, 1, ',', '') . '%' : '0%';
                $color = $palette[$i % count($palette)];
            @endphp
            <li class="flex items-center gap-2">
                <span class="h-2 w-2 shrink-0 rounded-full" style="background: {{ $color }}"></span>
                <span class="min-w-0 flex-1 truncate text-slate-600" title="{{ $d['n'] }}">{{ $d['n'] }}</span>
                <span class="shrink-0 text-xs font-medium text-slate-800">{{ $pct }}</span>
            </li>
        @endforeach
    </ul>

    <!-- Card Footer -->
    <div class="mt-auto pt-4">
        @if (!empty($m['foot']))
            <div class="mb-4 grid grid-cols-2 gap-3 border-t border-slate-100 pt-3 text-[11px] leading-snug text-slate-500">
                @foreach ($m['foot'] as $fIdx => $foot)
                    <div class="{{ $fIdx ? 'text-right' : '' }} min-w-0">
                        <p class="truncate text-slate-400">{{ $foot[0] }}</p>
                        <p class="text-sm font-bold text-slate-900 mt-0.5">{{ $foot[1] }}</p>
                    </div>
                @endforeach
            </div>
        @endif

        <button type="button"
                onclick='openMetricDetail(@json($m))'
                class="w-full rounded-xl border border-sky-300 py-2.5 text-sm font-semibold text-sky-700 transition hover:bg-sky-50 shadow-sm"
                style="border-color: {{ $palette[1] ?? '#a0bbea' }}; color: {{ $palette[0] ?? '#4472d0' }}">
            Lihat rincian agregat ↗
        </button>
    </div>
</article>
