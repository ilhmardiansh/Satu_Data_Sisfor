@extends('layouts.app')

@section('title', 'Dashboard Sistem Informasi - Satu Data UNUSA')

@section('content')
@php
    $allMetricCards = [];
    foreach ($metrics['akademik1'] as $idx => $m) {
        $allMetricCards['ak1_'.$idx] = $m;
    }
    foreach ($metrics['akademik2'] as $idx => $m) {
        $allMetricCards['ak2_'.$idx] = $m;
    }
    foreach ($metrics['nonakademik1'] as $idx => $m) {
        $allMetricCards['nak1_'.$idx] = $m;
    }
    foreach ($metrics['nonakademik2'] as $idx => $m) {
        $allMetricCards['nak2_'.$idx] = $m;
    }
@endphp

<main class="mx-auto max-w-[1200px] px-4 py-10 sm:px-8">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm text-slate-500">
                <a href="{{ route('beranda') }}" class="hover:text-sky-600">{{ $t('Beranda', 'Home') }}</a>
                <span class="mx-1">/</span>
                <span class="text-sky-600">Dashboard</span>
            </p>
            <h1 class="mt-1 text-[28px] font-extrabold tracking-tight text-slate-900">
                Dashboard Sistem Informasi
            </h1>
            <p class="mt-1 text-sm text-slate-500">
                {{ $t(
                    'Universitas Nahdlatul Ulama Surabaya (UNUSA) · Pilih parameter dan jenis visualisasi di setiap kartu',
                    'Universitas Nahdlatul Ulama Surabaya (UNUSA) · Choose parameters and chart type on each card'
                ) }}
            </p>
        </div>
    </div>

    @if(! empty($vizList))
        <section class="relative isolate mt-8 overflow-hidden rounded-2xl border border-white/15 bg-gradient-to-br from-benhur via-[#062f78] to-deep p-5 text-white shadow-[0_16px_40px_-12px_rgba(0,36,98,0.45)] sm:p-6">
            <div data-scene="waves"
                 aria-hidden="true"
                 class="pointer-events-none absolute inset-0 opacity-55"
                 style="mask-image: radial-gradient(ellipse at top right, #000 45%, transparent 92%); -webkit-mask-image: radial-gradient(ellipse at top right, #000 45%, transparent 92%);"></div>
            <div class="relative z-10 mb-5 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="sd-glass-panel grid h-10 w-10 place-items-center rounded-xl">
                        <i data-lucide="bar-chart-3" class="h-5 w-5 text-white"></i>
                    </span>
                    <div>
                        <h2 class="text-lg font-bold tracking-tight">{{ $t('Visualisasi pilihan prodi', 'Featured visualizations') }}</h2>
                        <p class="text-xs text-white/80">{{ $t('Disusun Wali Data dari data yang telah terkumpul', 'Curated by the Data Steward from collected data') }}</p>
                    </div>
                </div>

                <div class="flex w-full flex-wrap gap-2 sm:w-auto">
                    <select id="vizFilterCat"
                            onchange="filterVizCards()"
                            aria-label="{{ $t('Kategori', 'Category') }}"
                            class="h-10 min-w-0 flex-1 rounded-xl border border-white/25 bg-white/12 px-3 text-sm font-medium text-white backdrop-blur-md outline-none transition hover:bg-white/20 sm:flex-none [&>option]:text-slate-900">
                        <option value="all">{{ $t('Semua kategori', 'All categories') }}</option>
                        @foreach($cats as $c)
                            <option value="{{ $c['code'] }}" {{ $selectedCat === $c['code'] ? 'selected' : '' }}>
                                {{ $c['name'] }}
                            </option>
                        @endforeach
                    </select>

                    <select id="vizFilterYear"
                            onchange="filterVizCards()"
                            aria-label="{{ $t('Tahun', 'Year') }}"
                            class="h-10 min-w-0 flex-1 rounded-xl border border-white/25 bg-white/12 px-3 text-sm font-medium text-white backdrop-blur-md outline-none transition hover:bg-white/20 sm:flex-none [&>option]:text-slate-900">
                        <option value="all">{{ $t('Semua tahun', 'All years') }}</option>
                        @foreach($years as $y)
                            <option value="{{ $y }}" {{ $selectedYear === (string) $y ? 'selected' : '' }}>
                                {{ $y }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div id="vizCardsGrid" class="relative z-10 grid grid-cols-1 gap-5 text-slate-900 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($vizList as $vIdx => $v)
                    @php $vColors = $v['palette']; @endphp
                    <article id="vizCardArt_{{ $vIdx }}"
                             data-cat="{{ $v['kategori'] }}"
                             data-year="{{ $v['tahun'] }}"
                             class="viz-card-item sd-card sd-card-hover relative flex flex-col overflow-hidden p-5">
                        <span class="pointer-events-none absolute inset-x-0 top-0 h-[3px]" style="background: {{ $vColors[0] }}"></span>
                        <h3 class="font-bold tracking-tight text-slate-900">{{ $v['title'] }}</h3>
                        <p class="mt-0.5 truncate text-xs text-slate-500" title="{{ $v['datasetTitle'] }}">
                            Sumber: {{ $v['datasetTitle'] }} · {{ $v['tahun'] }}
                        </p>

                        <div class="mt-3 grid grid-cols-2 gap-2">
                            <select id="vizCol_{{ $vIdx }}"
                                    onchange="updateVizCard({{ $vIdx }})"
                                    aria-label="Parameter nilai"
                                    class="cursor-pointer rounded-lg border border-slate-200/90 bg-slate-50/80 px-2 py-1 text-[11px] font-semibold text-slate-600 shadow-2xs outline-none transition hover:border-sky-300 focus:border-sky-400">
                                @foreach($v['numericCols'] as $cIdx => $cName)
                                    <option value="{{ $cIdx }}" {{ (int) $cIdx === (int) $v['valueCol'] ? 'selected' : '' }}>
                                        {{ $cName }}
                                    </option>
                                @endforeach
                            </select>

                            <select id="vizKind_{{ $vIdx }}"
                                    onchange="updateVizCard({{ $vIdx }})"
                                    aria-label="Jenis grafik"
                                    class="cursor-pointer rounded-lg border border-slate-200/90 bg-slate-50/80 px-2 py-1 text-[11px] font-semibold text-slate-600 shadow-2xs outline-none transition hover:border-sky-300 focus:border-sky-400">
                                <option value="number" {{ $v['kind'] === 'number' ? 'selected' : '' }}>Angka</option>
                                <option value="donut" {{ $v['kind'] === 'donut' ? 'selected' : '' }}>Donat</option>
                                <option value="pie" {{ $v['kind'] === 'pie' ? 'selected' : '' }}>Pai</option>
                                <option value="bar" {{ $v['kind'] === 'bar' ? 'selected' : '' }}>Batang</option>
                                <option value="hbar" {{ $v['kind'] === 'hbar' ? 'selected' : '' }}>Horizontal</option>
                                <option value="line" {{ $v['kind'] === 'line' ? 'selected' : '' }}>Garis</option>
                                <option value="radar" {{ $v['kind'] === 'radar' ? 'selected' : '' }}>Radar</option>
                            </select>
                        </div>

                        <div class="relative mt-4 h-44 w-full">
                            <div id="vizRenderSlot_{{ $vIdx }}" class="h-full w-full"></div>
                            <div id="vizDonutCenter_{{ $vIdx }}" class="pointer-events-none absolute inset-0 grid place-items-center text-center {{ $v['kind'] === 'donut' ? '' : 'hidden' }}">
                                <div>
                                    <p id="vizDonutTotal_{{ $vIdx }}" class="text-2xl font-extrabold leading-none tracking-tight tabular-nums text-slate-900"></p>
                                    <p id="vizDonutColName_{{ $vIdx }}" class="mt-1 text-[11px] font-medium text-slate-500"></p>
                                </div>
                            </div>
                        </div>

                        <ul id="vizLegend_{{ $vIdx }}" class="mt-4 space-y-1.5 text-[13px] tabular-nums"></ul>
                    </article>
                @endforeach
            </div>

            <p id="vizEmptyNotice" class="relative z-10 hidden rounded-xl bg-white/10 py-8 text-center text-sm text-white/85">
                {{ $t('Tidak ada visualisasi untuk kategori dan tahun ini.', 'No visualizations for this category and year.') }}
            </p>
        </section>
    @endif

    <!-- Banner A -->
    <div class="mt-12 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200/80 border-l-4 border-l-brand bg-white px-6 py-5 shadow-[0_1px_2px_rgba(15,23,42,0.04),0_8px_24px_-6px_rgba(15,23,42,0.05)]">
        <h2 class="flex items-center gap-3 text-2xl font-extrabold tracking-tight text-slate-900">
            <span class="grid h-10 w-10 place-items-center rounded-xl bg-gradient-to-b from-brand to-brand-dark text-sm font-extrabold text-white shadow-[0_2px_6px_rgba(68,114,208,0.35),inset_0_1px_0_rgba(255,255,255,0.25)]">A</span>
            Akademik
        </h2>
        <p class="text-sm text-slate-500">
            Tridharma: pendidikan, penelitian, dan pengabdian beserta luarannya
        </p>
    </div>

    <!-- Group A.1 -->
    <div class="mt-8 flex min-w-0 flex-col">
        <div class="mb-4 flex items-center gap-3 text-sm lg:min-h-10">
            <span class="inline-flex items-center rounded-md bg-sky-50 px-2 py-0.5 font-mono text-xs font-bold text-brand-dark ring-1 ring-sky-200/70">A.1</span>
            <span class="font-semibold text-slate-900">Pendidikan dan mahasiswa</span>
            <span class="h-px min-w-4 flex-1 bg-slate-200/80"></span>
            <span class="shrink-0 tabular-nums text-slate-400">{{ count($metrics['akademik1']) }} kategori</span>
        </div>
        <div class="reveal-stagger grid flex-1 grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($metrics['akademik1'] as $idx => $m)
                @include('pages.partials.metric-card', ['m' => $m, 'cardId' => 'ak1_'.$idx])
            @endforeach
        </div>
    </div>

    <!-- Group A.2 -->
    <div class="mt-8 flex min-w-0 flex-col">
        <div class="mb-4 flex items-center gap-3 text-sm lg:min-h-10">
            <span class="inline-flex items-center rounded-md bg-sky-50 px-2 py-0.5 font-mono text-xs font-bold text-brand-dark ring-1 ring-sky-200/70">A.2</span>
            <span class="font-semibold text-slate-900">Penelitian, prestasi, dan luaran</span>
            <span class="h-px min-w-4 flex-1 bg-slate-200/80"></span>
            <span class="shrink-0 tabular-nums text-slate-400">{{ count($metrics['akademik2']) }} kategori</span>
        </div>
        <div class="reveal-stagger grid flex-1 grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($metrics['akademik2'] as $idx => $m)
                @include('pages.partials.metric-card', ['m' => $m, 'cardId' => 'ak2_'.$idx])
            @endforeach
        </div>
    </div>

    <!-- Banner N -->
    <div class="mt-12 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200/80 border-l-4 border-l-brand bg-white px-6 py-5 shadow-[0_1px_2px_rgba(15,23,42,0.04),0_8px_24px_-6px_rgba(15,23,42,0.05)]">
        <h2 class="flex items-center gap-3 text-2xl font-extrabold tracking-tight text-slate-900">
            <span class="grid h-10 w-10 place-items-center rounded-xl bg-gradient-to-b from-brand to-brand-dark text-sm font-extrabold text-white shadow-[0_2px_6px_rgba(68,114,208,0.35),inset_0_1px_0_rgba(255,255,255,0.25)]">N</span>
            Non-Akademik
        </h2>
        <p class="text-sm text-slate-500">
            Sumber daya dan tata kelola yang menopang tridharma
        </p>
    </div>

    <div class="lg:grid lg:grid-cols-4 lg:gap-5">
        <div class="mt-8 flex min-w-0 flex-col lg:col-span-3">
            <div class="mb-4 flex items-center gap-3 text-sm lg:min-h-10">
                <span class="inline-flex items-center rounded-md bg-sky-50 px-2 py-0.5 font-mono text-xs font-bold text-brand-dark ring-1 ring-sky-200/70">N.1</span>
                <span class="font-semibold text-slate-900">Sumber daya</span>
                <span class="h-px min-w-4 flex-1 bg-slate-200/80"></span>
                <span class="shrink-0 tabular-nums text-slate-400">{{ count($metrics['nonakademik1']) }} kategori</span>
            </div>
            <div class="reveal-stagger grid flex-1 grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($metrics['nonakademik1'] as $idx => $m)
                    @include('pages.partials.metric-card', ['m' => $m, 'cardId' => 'nak1_'.$idx])
                @endforeach
            </div>
        </div>

        <div class="mt-8 flex min-w-0 flex-col lg:col-span-1">
            <div class="mb-4 flex items-center gap-3 text-sm lg:min-h-10">
                <span class="inline-flex items-center rounded-md bg-sky-50 px-2 py-0.5 font-mono text-xs font-bold text-brand-dark ring-1 ring-sky-200/70">N.2</span>
                <span class="font-semibold text-slate-900">Tata kelola dan kemitraan</span>
                <span class="h-px min-w-4 flex-1 bg-slate-200/80"></span>
                <span class="shrink-0 tabular-nums text-slate-400">{{ count($metrics['nonakademik2']) }} kategori</span>
            </div>
            <div class="reveal-stagger grid flex-1 grid-cols-1 gap-5">
                @foreach($metrics['nonakademik2'] as $idx => $m)
                    @include('pages.partials.metric-card', ['m' => $m, 'cardId' => 'nak2_'.$idx])
                @endforeach
            </div>
        </div>
    </div>
</main>

<!-- Modal Detail Agregat -->
<div id="detailModal"
     class="fixed inset-0 z-50 hidden place-items-center bg-slate-900/40 p-3 backdrop-blur-sm sm:p-6"
     onclick="closeDetailModal()">
    <div id="detailDialog"
         role="dialog"
         aria-modal="true"
         class="flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl border border-slate-200/80 border-t-4 bg-white shadow-[0_24px_64px_-12px_rgba(15,23,42,0.28)]"
         onclick="event.stopPropagation()">
        <div class="flex items-start justify-between gap-6 border-b border-slate-200 px-5 pt-6 pb-5 sm:px-8">
            <div>
                <p class="flex items-center gap-2 text-sm text-slate-500">
                    <span id="detailDot" class="h-2.5 w-2.5 rounded-full"></span>
                    <span id="detailKicker">Dashboard › Judul</span>
                </p>
                <h2 id="detailTitle" class="mt-2 text-2xl font-extrabold tracking-tight text-slate-900">Rincian</h2>
                <p class="mt-1 flex items-baseline gap-2">
                    <span id="detailTotal" class="text-4xl font-extrabold text-slate-900"></span>
                    <span id="detailUnit" class="text-sm text-slate-500"></span>
                </p>
            </div>
            <button type="button"
                    onclick="closeDetailModal()"
                    aria-label="Tutup"
                    class="grid h-10 w-10 shrink-0 place-items-center rounded-xl border border-slate-300 text-xl text-slate-600 transition hover:bg-slate-50">
                ×
            </button>
        </div>

        <div class="overflow-y-auto px-5 py-6 sm:px-8">
            <h3 id="detailHeading" class="mb-3 font-bold text-slate-900"></h3>
            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="w-full text-sm">
                    <thead class="bg-surface-strong text-xs tracking-wide text-slate-500 uppercase">
                        <tr id="detailTheadRow"></tr>
                    </thead>
                    <tbody id="detailTbody"></tbody>
                    <tfoot id="detailTfoot"></tfoot>
                </table>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:px-8">
            <p class="text-sm text-slate-500">Sumber: Prodi Sistem Informasi UNUSA</p>
            <div class="ml-auto flex shrink-0 gap-3">
                <button type="button" id="detailDownloadBtn"
                        class="rounded-xl border border-slate-200/90 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-2xs transition hover:border-slate-300 hover:bg-slate-50">
                    Unduh CSV
                </button>
                <button type="button" onclick="closeDetailModal()"
                        class="sd-btn-primary px-5 py-2.5 text-sm font-bold">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const metricDataStore = @json($allMetricCards);
    const vizDataStore = @json($vizList);

    function updateMetricCard(cardId, kind) {
        const m = metricDataStore[cardId];
        if (!m || !window.SatuDataCharts) return;

        const colors = m.palette || window.SatuDataCharts.palettes.sky;
        const renderSlot = document.getElementById('metricRenderSlot_' + cardId);
        const donutCenter = document.getElementById('metricDonutCenter_' + cardId);
        const nonCircTotal = document.getElementById('metricNonCircTotal_' + cardId);

        if (kind === 'donut') {
            donutCenter?.classList.remove('hidden');
            nonCircTotal?.classList.add('hidden');
        } else if (kind === 'pie' || kind === 'number') {
            donutCenter?.classList.add('hidden');
            nonCircTotal?.classList.add('hidden');
        } else {
            donutCenter?.classList.add('hidden');
            nonCircTotal?.classList.remove('hidden');
        }

        if (renderSlot) {
            renderSlot.innerHTML = window.SatuDataCharts.renderSvg(kind, m.data, colors, 300, 180);
        }
    }

    function updateVizCard(idx) {
        const v = vizDataStore[idx];
        if (!v || !window.SatuDataCharts) return;

        const colSelect = document.getElementById('vizCol_' + idx);
        const kindSelect = document.getElementById('vizKind_' + idx);
        const colIdx = parseInt(colSelect.value, 10);
        const kind = kindSelect.value;
        const colors = v.palette || window.SatuDataCharts.palettes.sky;

        const seriesData = window.SatuDataCharts.series(v.rows, v.labelCol, colIdx);
        const total = seriesData.reduce((acc, cur) => acc + cur.v, 0);

        const renderSlot = document.getElementById('vizRenderSlot_' + idx);
        const donutCenter = document.getElementById('vizDonutCenter_' + idx);
        const legend = document.getElementById('vizLegend_' + idx);

        if (kind === 'donut') {
            donutCenter?.classList.remove('hidden');
            document.getElementById('vizDonutTotal_' + idx).textContent = total.toLocaleString('id-ID');
            document.getElementById('vizDonutColName_' + idx).textContent = v.cols[colIdx] || '';
        } else {
            donutCenter?.classList.add('hidden');
        }

        if (renderSlot) {
            renderSlot.innerHTML = window.SatuDataCharts.renderSvg(kind, seriesData, colors, 300, 160);
        }

        if (legend) {
            legend.innerHTML = seriesData.slice(0, 6).map((x, i) => {
                const color = colors[i % colors.length];
                return `
                    <li class="flex items-center gap-2">
                        <span class="h-2.5 w-2.5 shrink-0 rounded-sm" style="background: ${color}"></span>
                        <span class="min-w-0 flex-1 truncate text-slate-600">${window.SatuDataCharts.escapeHtml(x.n)}</span>
                        <span class="font-mono text-xs text-slate-800">${x.v.toLocaleString('id-ID')}</span>
                    </li>
                `;
            }).join('');
        }
    }

    function filterVizCards() {
        const catVal = document.getElementById('vizFilterCat')?.value || 'all';
        const yearVal = document.getElementById('vizFilterYear')?.value || 'all';
        const items = document.querySelectorAll('.viz-card-item');
        let visibleCount = 0;

        items.forEach((el) => {
            const matchCat = catVal === 'all' || el.getAttribute('data-cat') === catVal;
            const matchYear = yearVal === 'all' || el.getAttribute('data-year') === yearVal;
            if (matchCat && matchYear) {
                el.classList.remove('hidden');
                visibleCount++;
            } else {
                el.classList.add('hidden');
            }
        });

        const notice = document.getElementById('vizEmptyNotice');
        if (notice) {
            notice.classList.toggle('hidden', visibleCount > 0);
        }
    }

    function openMetricDetail(m) {
        const colors = m.palette || window.SatuDataCharts.palettes.sky;
        document.getElementById('detailDialog').style.borderTopColor = colors[0];
        document.getElementById('detailDot').style.backgroundColor = colors[0];
        document.getElementById('detailKicker').textContent = `Dashboard › ${m.title}`;
        document.getElementById('detailTitle').textContent = `Rincian ${m.title}`;
        document.getElementById('detailTotal').textContent = m.total;
        document.getElementById('detailUnit').textContent = m.unit;
        document.getElementById('detailHeading').textContent = m.table.heading;

        const t = m.table;
        const rows = t.rows.map((r) => {
            const n = r[0];
            const vals = r.slice(1).map((v) => (typeof v === 'number' ? v : window.SatuDataCharts.parseNum(v) || 0));
            const total = vals.reduce((a, b) => a + b, 0);
            return { n, vals, total };
        });

        const grand = rows.reduce((a, r) => a + r.total, 0);
        const colSum = t.cols.map((_, i) => rows.reduce((a, r) => a + r.vals[i], 0));
        const fmtPct = (x, tot) => window.SatuDataCharts.fmtPct(x, tot);

        const escHtml = window.SatuDataCharts.escapeHtml;
        let thHtml = `<th class="px-4 py-3 text-left font-semibold">${escHtml(t.label)}</th>`;
        t.cols.forEach((c) => {
            thHtml += `<th class="px-4 py-3 text-right font-semibold">${escHtml(c)}</th>`;
        });
        thHtml += `<th class="px-4 py-3 text-right font-semibold">Total</th>`;
        thHtml += `<th class="px-4 py-3 text-right font-semibold">%</th>`;
        document.getElementById('detailTheadRow').innerHTML = thHtml;

        document.getElementById('detailTbody').innerHTML = rows.map((r, i) => {
            const color = colors[i % colors.length];
            const valCells = r.vals.map((v) => `<td class="px-4 text-right tabular-nums text-slate-600">${v.toLocaleString('id-ID')}</td>`).join('');
            return `
                <tr class="border-t border-slate-100 hover:bg-sky-50/50">
                    <td class="px-4 py-3">
                        <span class="flex items-center gap-2.5">
                            <span class="h-2.5 w-2.5 shrink-0 rounded-sm" style="background: ${color}"></span>
                            ${escHtml(r.n)}
                        </span>
                    </td>
                    ${valCells}
                    <td class="px-4 text-right font-bold tabular-nums">${r.total.toLocaleString('id-ID')}</td>
                    <td class="px-4 text-right tabular-nums text-slate-500">${fmtPct(r.total, grand)}</td>
                </tr>
            `;
        }).join('');

        const sumCells = colSum.map((v) => `<td class="px-4 text-right tabular-nums">${v.toLocaleString('id-ID')}</td>`).join('');
        document.getElementById('detailTfoot').innerHTML = `
            <tr class="border-t-2 border-slate-200 bg-slate-50 font-bold">
                <td class="px-4 py-3">Total</td>
                ${sumCells}
                <td class="px-4 text-right tabular-nums" style="color: ${colors[0]}">${grand.toLocaleString('id-ID')}</td>
                <td class="px-4 text-right">100%</td>
            </tr>
        `;

        document.getElementById('detailDownloadBtn').onclick = () => {
            const esc = (v) => {
                const s = String(v ?? '');
                return /[",\n;]/.test(s) ? `"${s.replace(/"/g, '""')}"` : s;
            };
            const csvRows = [
                [t.label, ...t.cols, 'Total', 'Persentase'].map(esc).join(','),
                ...rows.map((r) => [r.n, ...r.vals, r.total, fmtPct(r.total, grand)].map(esc).join(',')),
                ['Total', ...colSum, grand, '100%'].map(esc).join(','),
            ];
            const blob = new Blob(['\uFEFF' + csvRows.join('\r\n')], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = `${m.title}.csv`;
            link.click();
            setTimeout(() => URL.revokeObjectURL(link.href), 1000);
        };

        const modal = document.getElementById('detailModal');
        modal.classList.remove('hidden');
        modal.classList.add('grid');
    }

    function closeDetailModal() {
        const modal = document.getElementById('detailModal');
        modal.classList.add('hidden');
        modal.classList.remove('grid');
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeDetailModal();
    });

    document.addEventListener('DOMContentLoaded', () => {
        Object.keys(metricDataStore).forEach((cardId) => {
            updateMetricCard(cardId, metricDataStore[cardId].def || 'donut');
        });
        vizDataStore.forEach((_, idx) => updateVizCard(idx));
        filterVizCards();
    });
</script>
@endpush
