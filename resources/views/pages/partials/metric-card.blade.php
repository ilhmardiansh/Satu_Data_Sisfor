@php
    $defKind = $m['def'] ?? 'donut';
    $palette = $m['palette'] ?? ['#4472d0', '#0ea5e9', '#a0bbea', '#0f172a', '#7dd3fc', '#94a3b8'];
    $totalSum = array_sum(array_column($m['data'], 'v'));
@endphp

<article class="sd-card sd-card-hover relative flex flex-col overflow-hidden p-5">
    <span class="pointer-events-none absolute inset-x-0 top-0 h-[3px]" style="background: {{ $palette[0] }}"></span>
    <h3 class="flex items-center gap-2 font-bold tracking-tight text-slate-900" title="{{ $m['title'] }}">
        <span class="h-2.5 w-2.5 shrink-0 rounded-full ring-2 ring-slate-100" style="background: {{ $palette[0] }}"></span>
        <span class="truncate">{{ $m['title'] }}</span>
    </h3>

    <div class="mt-3 flex h-7 items-center justify-between gap-2">
        @if(! empty($m['trend']))
            <span class="inline-flex shrink-0 items-center rounded-full px-2.5 py-0.5 text-[11px] font-bold tabular-nums ring-1 {{ $m['trend']['up'] ? 'bg-emerald-50 text-emerald-700 ring-emerald-200/70' : 'bg-rose-50 text-rose-600 ring-rose-200/70' }}">
                {{ $m['trend']['up'] ? '▲' : '▼' }} {{ $m['trend']['v'] }}
            </span>
        @else
            <span></span>
        @endif

        <select id="metricKind_{{ $cardId }}"
                onchange="updateMetricCard('{{ $cardId }}', this.value)"
                title="Ubah visualisasi"
                class="cursor-pointer rounded-lg border border-slate-200/90 bg-slate-50/80 px-2 py-1 text-[11px] font-semibold text-slate-600 shadow-2xs outline-none transition hover:border-sky-300 focus:border-sky-400 focus:ring-2 focus:ring-sky-100">
            <option value="number" {{ $defKind === 'number' ? 'selected' : '' }}>Angka</option>
            <option value="donut" {{ $defKind === 'donut' ? 'selected' : '' }}>Donat</option>
            <option value="pie" {{ $defKind === 'pie' ? 'selected' : '' }}>Pai</option>
            <option value="bar" {{ $defKind === 'bar' ? 'selected' : '' }}>Batang</option>
            <option value="hbar" {{ $defKind === 'hbar' ? 'selected' : '' }}>Horizontal</option>
            <option value="line" {{ $defKind === 'line' ? 'selected' : '' }}>Garis</option>
            <option value="radar" {{ $defKind === 'radar' ? 'selected' : '' }}>Radar</option>
        </select>
    </div>

    <div class="relative mt-3 h-48 w-full">
        <div id="metricRenderSlot_{{ $cardId }}" class="h-full w-full"></div>
        <div id="metricDonutCenter_{{ $cardId }}" class="pointer-events-none absolute inset-0 grid place-items-center text-center {{ $defKind === 'donut' ? '' : 'hidden' }}">
            <div>
                <p class="text-3xl font-extrabold leading-none tracking-tight tabular-nums text-slate-900">{{ $m['total'] }}</p>
                <p class="mt-1 text-xs font-medium text-slate-500">{{ $m['unit'] }}</p>
            </div>
        </div>
    </div>

    <p id="metricNonCircTotal_{{ $cardId }}" class="mt-2 text-xs text-slate-500 {{ in_array($defKind, ['donut', 'pie'], true) ? 'hidden' : '' }}">
        <span class="text-lg font-extrabold tracking-tight tabular-nums text-slate-900">{{ $m['total'] }}</span> {{ $m['unit'] }}
    </p>

    <ul class="mt-4 space-y-2 text-[13px]">
        @foreach($m['data'] as $i => $d)
            @php
                $pct = $totalSum > 0 ? number_format(($d['v'] / $totalSum) * 100, 1, ',', '') . '%' : '0%';
                $color = $palette[$i % count($palette)];
            @endphp
            <li class="flex items-center gap-2">
                <span class="h-2.5 w-2.5 shrink-0 rounded-[3px]" style="background: {{ $color }}"></span>
                <span class="min-w-0 flex-1 truncate text-slate-600" title="{{ $d['n'] }}">{{ $d['n'] }}</span>
                <span class="shrink-0 font-mono text-xs font-semibold tabular-nums text-slate-800">{{ $pct }}</span>
            </li>
        @endforeach
    </ul>

    <div class="mt-auto pt-4">
        @if(! empty($m['foot']))
            <div class="mb-4 grid grid-cols-2 gap-3 border-t border-slate-100 pt-3 text-[11px] leading-snug text-slate-500">
                @foreach($m['foot'] as $fIdx => $foot)
                    <div class="min-w-0 {{ $fIdx ? 'text-right' : '' }}">
                        {{ $foot[0] }}
                        <p class="text-sm font-bold tabular-nums text-slate-900">{{ $foot[1] }}</p>
                    </div>
                @endforeach
            </div>
        @endif

        <button type="button"
                onclick='openMetricDetail(@json($m))'
                class="w-full rounded-xl border bg-slate-50/70 py-2.5 text-sm font-bold shadow-2xs transition hover:bg-sky-50/70 hover:shadow-xs"
                style="border-color: {{ $palette[1] ?? '#a0bbea' }}; color: {{ $palette[3] ?? '#0f172a' }}">
            Lihat rincian agregat ↗
        </button>
    </div>
</article>
