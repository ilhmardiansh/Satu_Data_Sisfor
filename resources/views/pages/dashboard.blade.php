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
    <!-- Breadcrumb & Title -->
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm text-slate-500">
                <a href="{{ route('beranda') }}" class="hover:text-sky-600">Beranda</a>
                <span class="mx-1">/</span>
                <span class="text-sky-600 font-medium">Dashboard</span>
            </p>
            <h1 class="mt-1 text-[28px] font-extrabold tracking-tight text-slate-900">
                Dashboard Sistem Informasi
            </h1>
            <p class="mt-1 text-sm text-slate-500">
                Universitas Nahdlatul Ulama Surabaya (UNUSA) · Pilih parameter dan jenis visualisasi di setiap kartu
            </p>
        </div>
    </div>

    <!-- SECTION: VISUALISASI PILIHAN PRODI (Wali Data) -->
    @if (!empty($vizList))
        <section class="mt-8 rounded-3xl bg-[#002462] p-6 text-white shadow-xl">
            <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="grid h-9 w-9 place-items-center rounded-lg bg-white/10">
                        <i data-lucide="bar-chart-3" class="h-5 w-5 text-white"></i>
                    </span>
                    <div>
                        <h2 class="text-lg font-bold">Visualisasi pilihan prodi</h2>
                        <p class="text-xs text-white/80">Disusun Wali Data dari data yang telah terkumpul</p>
                    </div>
                </div>

                <!-- Category and Year Filter Dropdowns -->
                <div class="flex w-full flex-wrap gap-2 sm:w-auto">
                    <!-- Category Filter -->
                    <select id="vizFilterCat"
                            onchange="filterVizCards()"
                            aria-label="Kategori"
                            class="h-10 min-w-0 rounded-xl border border-white/20 bg-white/10 px-3.5 text-sm font-medium text-white outline-none [&>option]:text-slate-900 cursor-pointer">
                        <option value="all">Semua kategori</option>
                        @foreach ($cats as $c)
                            <option value="{{ $c['code'] }}" {{ (isset($selectedCat) && $selectedCat == $c['code']) ? 'selected' : '' }}>
                                {{ $c['name'] }}
                            </option>
                        @endforeach
                    </select>

                    <!-- Year Filter -->
                    <select id="vizFilterYear"
                            onchange="filterVizCards()"
                            aria-label="Tahun"
                            class="h-10 min-w-0 rounded-xl border border-white/20 bg-white/10 px-3.5 text-sm font-medium text-white outline-none [&>option]:text-slate-900 cursor-pointer">
                        <option value="all">Semua tahun</option>
                        @foreach ($years as $y)
                            <option value="{{ $y }}" {{ (isset($selectedYear) && $selectedYear == $y) ? 'selected' : '' }}>
                                {{ $y }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Viz Cards Grid -->
            <div id="vizCardsGrid" class="grid grid-cols-1 gap-5 text-slate-900 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($filteredViz as $vIdx => $v)
                    @php
                        $d = $v['dataset'];
                        $vColors = $v['palette'];
                    @endphp
                    <article id="vizCardArt_{{ $vIdx }}"
                             data-cat="{{ $d['kategori'] }}"
                             data-year="{{ $d['tahun'] }}"
                             class="viz-card-item flex flex-col rounded-2xl border border-t-4 border-slate-200 bg-white p-5 shadow-sm"
                             style="border-top-color: {{ $vColors[0] }}">
                        <h3 class="font-bold text-slate-900 text-sm">{{ $v['title'] }}</h3>
                        <p class="mt-0.5 truncate text-xs text-slate-500" title="{{ $d['title'] }}">
                            Sumber: {{ $d['title'] }} · {{ $d['tahun'] }}
                        </p>

                        <!-- Selects for column & kind -->
                        <div class="mt-3 grid grid-cols-2 gap-2">
                            <select id="vizCol_{{ $vIdx }}"
                                    onchange="updateVizCard({{ $vIdx }})"
                                    aria-label="Parameter nilai"
                                    class="rounded-md border border-slate-200 bg-slate-50 px-2 py-1 text-[11px] font-medium text-slate-600 outline-none cursor-pointer hover:border-sky-300">
                                @foreach ($v['numericCols'] as $cIdx => $cName)
                                    <option value="{{ $cIdx }}" {{ $cIdx == $v['valueCol'] ? 'selected' : '' }}>
                                        {{ $cName }}
                                    </option>
                                @endforeach
                            </select>

                            <select id="vizKind_{{ $vIdx }}"
                                    onchange="updateVizCard({{ $vIdx }})"
                                    aria-label="Jenis grafik"
                                    class="rounded-md border border-slate-200 bg-slate-50 px-2 py-1 text-[11px] font-medium text-slate-600 outline-none cursor-pointer hover:border-sky-300">
                                <option value="bar" {{ $v['kind'] === 'bar' ? 'selected' : '' }}>Batang</option>
                                <option value="donut" {{ $v['kind'] === 'donut' ? 'selected' : '' }}>Donat</option>
                                <option value="pie" {{ $v['kind'] === 'pie' ? 'selected' : '' }}>Pai</option>
                                <option value="hbar">Horizontal</option>
                                <option value="line" {{ $v['kind'] === 'line' ? 'selected' : '' }}>Garis</option>
                                <option value="radar">Radar</option>
                                <option value="number">Angka</option>
                            </select>
                        </div>

                        <!-- Chart Container -->
                        <div class="relative mt-4 h-44 w-full" id="vizChartBox_{{ $vIdx }}">
                            <div id="vizNumberWrap_{{ $vIdx }}" class="hidden h-full flex-col items-center justify-center text-center">
                                <p id="vizNumberVal_{{ $vIdx }}" class="text-4xl font-extrabold tabular-nums" style="color: {{ $vColors[0] }}"></p>
                                <p id="vizNumberSub_{{ $vIdx }}" class="mt-2 text-xs text-slate-500"></p>
                            </div>
                            <div id="vizRenderSlot_{{ $vIdx }}" class="h-full w-full">
                                <canvas id="vizChartCanvas_{{ $vIdx }}" class="h-full w-full"></canvas>
                            </div>
                            <div id="vizDonutCenter_{{ $vIdx }}" class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center text-center {{ $v['kind'] === 'donut' ? '' : 'hidden' }}">
                                <p id="vizDonutTotal_{{ $vIdx }}" class="text-2xl font-black leading-none text-slate-900 tracking-tight"></p>
                                <p id="vizDonutColName_{{ $vIdx }}" class="mt-1 text-[11px] text-slate-500 font-medium"></p>
                            </div>
                        </div>

                        <!-- Legend Items -->
                        <ul id="vizLegend_{{ $vIdx }}" class="mt-4 space-y-1.5 text-[13px] border-t border-slate-100 pt-3">
                            <!-- Populated dynamically by JS -->
                        </ul>
                    </article>
                @empty
                    <div class="col-span-3 rounded-xl bg-white/5 py-8 text-center text-sm text-sky-100/80">
                        Tidak ada visualisasi untuk filter ini.
                    </div>
                @endforelse
            </div>
            <div id="vizEmptyNotice" class="hidden rounded-xl bg-white/5 py-8 text-center text-sm text-sky-100/80">
                Tidak ada visualisasi untuk filter ini.
            </div>
        </section>
    @endif

    <!-- BANNER A: AKADEMIK -->
    <div class="mt-12 flex flex-wrap items-center justify-between gap-2 rounded-2xl border-l-4 border-sky-500 bg-gradient-to-r from-sky-100 via-sky-50 to-white px-6 py-5 shadow-sm">
        <h2 class="flex items-center gap-3 text-2xl font-extrabold tracking-tight text-slate-900">
            <span class="grid h-9 w-9 place-items-center rounded-lg bg-[#4472d0] text-sm font-bold text-white shadow-sm">
                A
            </span>
            Akademik
        </h2>
        <p class="text-sm text-slate-500">
            Tridharma: pendidikan, penelitian, dan pengabdian beserta luarannya
        </p>
    </div>

    <!-- GROUP A.1: Pendidikan dan mahasiswa -->
    <div class="mt-8 flex min-w-0 flex-col">
        <div class="mb-4 flex items-center gap-3 text-sm lg:min-h-10">
            <span class="font-mono font-bold text-sky-600">A.1</span>
            <span class="font-semibold text-slate-900">Pendidikan dan mahasiswa</span>
            <span class="h-px min-w-4 flex-1 bg-slate-200"></span>
            <span class="shrink-0 text-slate-400">{{ count($metrics['akademik1']) }} kategori</span>
        </div>
        <div class="grid flex-1 grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($metrics['akademik1'] as $idx => $m)
                @include('pages.partials.metric-card', ['m' => $m, 'cardId' => 'ak1_'.$idx])
            @endforeach
        </div>
    </div>

    <!-- GROUP A.2: Penelitian, prestasi, dan luaran -->
    <div class="mt-8 flex min-w-0 flex-col">
        <div class="mb-4 flex items-center gap-3 text-sm lg:min-h-10">
            <span class="font-mono font-bold text-sky-600">A.2</span>
            <span class="font-semibold text-slate-900">Penelitian, prestasi, dan luaran</span>
            <span class="h-px min-w-4 flex-1 bg-slate-200"></span>
            <span class="shrink-0 text-slate-400">{{ count($metrics['akademik2']) }} kategori</span>
        </div>
        <div class="grid flex-1 grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($metrics['akademik2'] as $idx => $m)
                @include('pages.partials.metric-card', ['m' => $m, 'cardId' => 'ak2_'.$idx])
            @endforeach
        </div>
    </div>

    <!-- BANNER N: NON-AKADEMIK -->
    <div class="mt-12 flex flex-wrap items-center justify-between gap-2 rounded-2xl border-l-4 border-sky-500 bg-gradient-to-r from-sky-100 via-sky-50 to-white px-6 py-5 shadow-sm">
        <h2 class="flex items-center gap-3 text-2xl font-extrabold tracking-tight text-slate-900">
            <span class="grid h-9 w-9 place-items-center rounded-lg bg-[#4472d0] text-sm font-bold text-white shadow-sm">
                N
            </span>
            Non-Akademik
        </h2>
        <p class="text-sm text-slate-500">
            Sumber daya dan tata kelola yang menopang tridharma
        </p>
    </div>

    <!-- N.1 (3 kartu) dan N.2 (1 kartu) berbagi satu baris 4 kolom di desktop agar tidak ada ruang kosong -->
    <div class="lg:grid lg:grid-cols-4 lg:gap-5">
        <div class="mt-8 flex min-w-0 flex-col lg:col-span-3">
            <div class="mb-4 flex items-center gap-3 text-sm lg:min-h-10">
                <span class="font-mono font-bold text-sky-600">N.1</span>
                <span class="font-semibold text-slate-900">Sumber daya</span>
                <span class="h-px min-w-4 flex-1 bg-slate-200"></span>
                <span class="shrink-0 text-slate-400">{{ count($metrics['nonakademik1']) }} kategori</span>
            </div>
            <div class="grid flex-1 grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($metrics['nonakademik1'] as $idx => $m)
                    @include('pages.partials.metric-card', ['m' => $m, 'cardId' => 'nak1_'.$idx])
                @endforeach
            </div>
        </div>

        <div class="mt-8 flex min-w-0 flex-col lg:col-span-1">
            <div class="mb-4 flex items-center gap-3 text-sm lg:min-h-10">
                <span class="font-mono font-bold text-sky-600">N.2</span>
                <span class="font-semibold text-slate-900">Tata kelola dan kemitraan</span>
                <span class="h-px min-w-4 flex-1 bg-slate-200"></span>
                <span class="shrink-0 text-slate-400">{{ count($metrics['nonakademik2']) }} kategori</span>
            </div>
            <div class="grid flex-1 grid-cols-1 gap-5">
                @foreach ($metrics['nonakademik2'] as $idx => $m)
                    @include('pages.partials.metric-card', ['m' => $m, 'cardId' => 'nak2_'.$idx])
                @endforeach
            </div>
        </div>
    </div>
</main>

<!-- MODAL DETAIL RINCIAN AGREGAT -->
<div id="detailModal"
     class="fixed inset-0 z-50 hidden place-items-center bg-slate-900/40 p-3 backdrop-blur-sm sm:p-6"
     onclick="closeDetailModal()">
    <div id="detailDialog"
         class="flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl border-t-4 bg-white shadow-2xl"
         onclick="event.stopPropagation()">
        <!-- Modal Head -->
        <div class="flex items-start justify-between gap-6 border-b border-slate-200 px-5 pt-6 pb-5 sm:px-8">
            <div>
                <p class="flex items-center gap-2 text-sm text-slate-500">
                    <span id="detailDot" class="h-2.5 w-2.5 rounded-full"></span>
                    <span id="detailKicker">Dashboard › Judul</span>
                </p>
                <h2 id="detailTitle" class="mt-2 text-2xl font-extrabold tracking-tight text-slate-900">
                    Rincian Judul
                </h2>
                <p class="mt-1 flex items-baseline gap-2">
                    <span id="detailTotal" class="text-4xl font-extrabold text-slate-900"></span>
                    <span id="detailUnit" class="text-sm text-slate-500"></span>
                </p>
            </div>
            <button onclick="closeDetailModal()"
                    aria-label="Tutup"
                    class="grid h-10 w-10 shrink-0 place-items-center rounded-xl border border-slate-300 text-xl text-slate-600 hover:bg-slate-50 transition">
                ×
            </button>
        </div>

        <!-- Modal Body (Table) -->
        <div class="overflow-y-auto px-5 py-6 sm:px-8">
            <h3 id="detailHeading" class="mb-3 font-bold text-slate-900">Heading</h3>
            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="w-full text-sm">
                    <thead class="bg-surface-strong text-xs tracking-wide text-slate-500 uppercase">
                        <tr id="detailTheadRow">
                            <!-- Injected by JS -->
                        </tr>
                    </thead>
                    <tbody id="detailTbody" class="divide-y divide-slate-100">
                        <!-- Injected by JS -->
                    </tbody>
                    <tfoot id="detailTfoot">
                        <!-- Injected by JS -->
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:px-8">
            <p class="text-sm text-slate-500">
                Sumber: Prodi Sistem Informasi UNUSA
            </p>
            <div class="ml-auto flex shrink-0 gap-3">
                <button id="detailDownloadBtn"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-100 transition">
                    Unduh CSV
                </button>
                <button onclick="closeDetailModal()"
                        class="rounded-xl bg-[#4472d0] px-5 py-2.5 text-sm font-bold text-white hover:bg-sky-700 transition">
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
    const metricChartInstances = {};

    const vizDataStore = @json($filteredViz);
    const vizChartInstances = {};

    function parseNum(v) {
        if (typeof v === 'number') return v;
        if (!v) return 0;
        let t = String(v).replace(/rp|\s|%/gi, '');
        if (/,\d{1,2}$/.test(t)) {
            t = t.replace(/\./g, '').replace(',', '.');
        } else {
            t = t.replace(/[.,]/g, '');
        }
        const n = parseFloat(t);
        return isNaN(n) ? 0 : n;
    }

    // PURE SVG CHART RENDERER (100% IDENTICAL TO RECHARTS PROTOTYPE)
    function generateSvgChart(kind, data, colors, width = 300, height = 180) {
        const total = data.reduce((a, b) => a + b.v, 0);

        if (kind === 'donut' || kind === 'pie') {
            const isDonut = kind === 'donut';
            const cx = width / 2;
            const cy = height / 2;
            const R = Math.min(width, height) / 2 - 8;
            const r = isDonut ? R * 0.72 : 0;

            let currentAngle = -Math.PI / 2; // start from 12 o'clock
            let paths = '';

            data.forEach((d, i) => {
                const sliceAngle = total > 0 ? (d.v / total) * 2 * Math.PI : 0;
                const endAngle = currentAngle + sliceAngle;

                const x1 = cx + R * Math.cos(currentAngle);
                const y1 = cy + R * Math.sin(currentAngle);
                const x2 = cx + R * Math.cos(endAngle);
                const y2 = cy + R * Math.sin(endAngle);

                const largeArc = sliceAngle > Math.PI ? 1 : 0;

                let pathData = '';
                if (isDonut) {
                    const x3 = cx + r * Math.cos(endAngle);
                    const y3 = cy + r * Math.sin(endAngle);
                    const x4 = cx + r * Math.cos(currentAngle);
                    const y4 = cy + r * Math.sin(currentAngle);
                    pathData = `M ${x1} ${y1} A ${R} ${R} 0 ${largeArc} 1 ${x2} ${y2} L ${x3} ${y3} A ${r} ${r} 0 ${largeArc} 0 ${x4} ${y4} Z`;
                } else {
                    pathData = `M ${cx} ${cy} L ${x1} ${y1} A ${R} ${R} 0 ${largeArc} 1 ${x2} ${y2} Z`;
                }

                const color = colors[i % colors.length];
                paths += `<path d="${pathData}" fill="${color}" stroke="#ffffff" stroke-width="2"><title>${d.n}: ${d.v.toLocaleString('id-ID')}</title></path>`;
                currentAngle = endAngle;
            });

            return `<svg viewBox="0 0 ${width} ${height}" class="w-full h-full">${paths}</svg>`;
        }

        if (kind === 'bar') {
            const padX = 20;
            const padB = 10;
            const padT = 15;
            const maxVal = Math.max(...data.map(d => d.v), 1);
            const n = data.length;
            const gap = 12;
            const barW = Math.max((width - 2 * padX - (n - 1) * gap) / n, 8);

            let rects = '';
            data.forEach((d, i) => {
                const barH = (d.v / maxVal) * (height - padT - padB);
                const x = padX + i * (barW + gap);
                const y = height - padB - barH;
                const color = colors[i % colors.length];
                rects += `<rect x="${x}" y="${y}" width="${barW}" height="${barH}" rx="4" ry="4" fill="${color}"><title>${d.n}: ${d.v.toLocaleString('id-ID')}</title></rect>`;
            });

            return `<svg viewBox="0 0 ${width} ${height}" class="w-full h-full">${rects}</svg>`;
        }

        if (kind === 'hbar') {
            const padY = 15;
            const padL = 20;
            const padR = 20;
            const maxVal = Math.max(...data.map(d => d.v), 1);
            const n = data.length;
            const gap = 8;
            const barH = Math.max((height - 2 * padY - (n - 1) * gap) / n, 6);

            let rects = '';
            data.forEach((d, i) => {
                const barW = (d.v / maxVal) * (width - padL - padR);
                const y = padY + i * (barH + gap);
                const color = colors[i % colors.length];
                rects += `<rect x="${padL}" y="${y}" width="${barW}" height="${barH}" rx="4" ry="4" fill="${color}"><title>${d.n}: ${d.v.toLocaleString('id-ID')}</title></rect>`;
            });

            return `<svg viewBox="0 0 ${width} ${height}" class="w-full h-full">${rects}</svg>`;
        }

        if (kind === 'line') {
            const padX = 24;
            const padY = 24;
            const vals = data.map(d => d.v);
            const maxVal = Math.max(...vals);
            const minVal = Math.min(...vals);
            const n = data.length;
            const range = maxVal - minVal || 1;

            const pts = data.map((d, i) => {
                const x = padX + i * ((width - 2 * padX) / Math.max(n - 1, 1));
                const y = height - padY - ((d.v - minVal) / range) * (height - 2 * padY);
                return { x, y, v: d.v, n: d.n };
            });

            const polyPoints = pts.map(p => `${p.x},${p.y}`).join(' ');
            let circles = '';
            pts.forEach(p => {
                circles += `<circle cx="${p.x}" cy="${p.y}" r="4.5" fill="${colors[0]}" stroke="#ffffff" stroke-width="2"><title>${p.n}: ${p.v.toLocaleString('id-ID')}</title></circle>`;
            });

            return `
                <svg viewBox="0 0 ${width} ${height}" class="w-full h-full">
                    <polyline points="${polyPoints}" fill="none" stroke="${colors[0]}" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                    ${circles}
                </svg>
            `;
        }

        if (kind === 'radar') {
            const cx = width / 2;
            const cy = height / 2;
            const R = Math.min(width, height) / 2 - 20;
            const vals = data.map(d => d.v);
            const maxVal = Math.max(...vals, 1);
            const n = data.length;

            let grid = '';
            [0.33, 0.66, 1].forEach(frac => {
                const r = R * frac;
                const gPts = [];
                for (let i = 0; i < n; i++) {
                    const angle = -Math.PI / 2 + (i / n) * 2 * Math.PI;
                    gPts.push(`${cx + r * Math.cos(angle)},${cy + r * Math.sin(angle)}`);
                }
                grid += `<polygon points="${gPts.join(' ')}" fill="none" stroke="#e2e8f0" stroke-width="1" />`;
            });

            const pts = data.map((d, i) => {
                const r = (d.v / maxVal) * R;
                const angle = -Math.PI / 2 + (i / n) * 2 * Math.PI;
                return `${cx + r * Math.cos(angle)},${cy + r * Math.sin(angle)}`;
            });

            return `
                <svg viewBox="0 0 ${width} ${height}" class="w-full h-full">
                    ${grid}
                    <polygon points="${pts.join(' ')}" fill="${colors[1] || colors[0]}" fill-opacity="0.4" stroke="${colors[0]}" stroke-width="2" />
                </svg>
            `;
        }

        return '';
    }

    // UPDATE AND RENDER METRIC CARDS (A.1, A.2, N.1, N.2)
    function updateMetricCard(cardId, kind) {
        const m = metricDataStore[cardId];
        if (!m) return;

        const colors = m.palette || ['#4472d0', '#0ea5e9', '#a0bbea', '#0f172a', '#7dd3fc', '#94a3b8'];
        const renderSlot = document.getElementById('metricRenderSlot_' + cardId);
        const numberWrap = document.getElementById('metricNumberWrap_' + cardId);
        const donutCenter = document.getElementById('metricDonutCenter_' + cardId);
        const nonCircTotal = document.getElementById('metricNonCircTotal_' + cardId);

        if (kind === 'number') {
            if (renderSlot) renderSlot.classList.add('hidden');
            if (donutCenter) donutCenter.classList.add('hidden');
            if (numberWrap) {
                numberWrap.classList.remove('hidden');
                numberWrap.classList.add('flex');
            }
            if (nonCircTotal) nonCircTotal.classList.add('hidden');
            return;
        }

        if (numberWrap) {
            numberWrap.classList.add('hidden');
            numberWrap.classList.remove('flex');
        }
        if (renderSlot) renderSlot.classList.remove('hidden');

        if (kind === 'donut') {
            if (donutCenter) donutCenter.classList.remove('hidden');
            if (nonCircTotal) nonCircTotal.classList.add('hidden');
        } else if (kind === 'pie') {
            if (donutCenter) donutCenter.classList.add('hidden');
            if (nonCircTotal) nonCircTotal.classList.add('hidden');
        } else {
            if (donutCenter) donutCenter.classList.add('hidden');
            if (nonCircTotal) nonCircTotal.classList.remove('hidden');
        }

        // Render Pure SVG into Slot
        if (renderSlot) {
            renderSlot.innerHTML = generateSvgChart(kind, m.data, colors, 300, 180);
        }
    }

    // UPDATE VIZ CARDS (Visualisasi pilihan prodi)
    function updateVizCard(idx) {
        const v = vizDataStore[idx];
        if (!v) return;

        const colSelect = document.getElementById('vizCol_' + idx);
        const kindSelect = document.getElementById('vizKind_' + idx);
        const colIdx = parseInt(colSelect.value);
        const kind = kindSelect.value;
        const colors = v.palette || ['#4472d0', '#0ea5e9', '#a0bbea', '#0f172a', '#7dd3fc'];

        const labelCol = v.labelCol;
        const seriesData = v.dataset.rows.map(r => ({
            n: String(r[labelCol]),
            v: parseNum(r[colIdx])
        }));
        const total = seriesData.reduce((acc, cur) => acc + cur.v, 0);

        const renderSlot = document.getElementById('vizRenderSlot_' + idx);
        const numberWrap = document.getElementById('vizNumberWrap_' + idx);
        const donutCenter = document.getElementById('vizDonutCenter_' + idx);
        const legend = document.getElementById('vizLegend_' + idx);

        if (kind === 'number') {
            if (renderSlot) renderSlot.classList.add('hidden');
            if (donutCenter) donutCenter.classList.add('hidden');
            if (numberWrap) {
                numberWrap.classList.remove('hidden');
                numberWrap.classList.add('flex');
            }
            document.getElementById('vizNumberVal_' + idx).textContent = total.toLocaleString('id-ID');
            document.getElementById('vizNumberSub_' + idx).textContent = `Total nilai · ${seriesData.length} kelompok`;
        } else {
            if (numberWrap) {
                numberWrap.classList.add('hidden');
                numberWrap.classList.remove('flex');
            }
            if (renderSlot) renderSlot.classList.remove('hidden');

            if (kind === 'donut') {
                if (donutCenter) donutCenter.classList.remove('hidden');
                document.getElementById('vizDonutTotal_' + idx).textContent = total.toLocaleString('id-ID');
                const colName = v.dataset.cols[colIdx];
                document.getElementById('vizDonutColName_' + idx).textContent = colName === 'Total' ? 'Total' : colName;
            } else {
                if (donutCenter) donutCenter.classList.add('hidden');
            }

            if (renderSlot) {
                renderSlot.innerHTML = generateSvgChart(kind, seriesData, colors, 300, 160);
            }
        }

        // Update Legend with round dots
        let legHtml = '';
        seriesData.slice(0, 6).forEach((x, i) => {
            const color = colors[i % colors.length];
            legHtml += `
                <li class="flex items-center gap-2">
                    <span class="h-2 w-2 shrink-0 rounded-full" style="background: ${color}"></span>
                    <span class="min-w-0 flex-1 truncate text-xs text-slate-600">${x.n}</span>
                    <span class="text-xs text-slate-700">${x.v.toLocaleString('id-ID')}</span>
                </li>
            `;
        });
        legend.innerHTML = legHtml;
    }

    // FILTER VIZ CARDS (Category & Year)
    function filterVizCards() {
        const catVal = document.getElementById('vizFilterCat').value;
        const yearVal = document.getElementById('vizFilterYear').value;
        const items = document.querySelectorAll('.viz-card-item');
        let visibleCount = 0;

        items.forEach(el => {
            const itemCat = el.getAttribute('data-cat');
            const itemYear = el.getAttribute('data-year');

            const matchCat = (catVal === 'all' || itemCat === catVal);
            const matchYear = (yearVal === 'all' || itemYear === yearVal);

            if (matchCat && matchYear) {
                el.classList.remove('hidden');
                visibleCount++;
            } else {
                el.classList.add('hidden');
            }
        });

        const notice = document.getElementById('vizEmptyNotice');
        if (notice) {
            if (visibleCount === 0) {
                notice.classList.remove('hidden');
            } else {
                notice.classList.add('hidden');
            }
        }
    }

    // DETAIL MODAL LOGIC
    function openMetricDetail(m) {
        const colors = m.palette || ['#4472d0', '#0ea5e9', '#a0bbea', '#0f172a', '#7dd3fc'];
        document.getElementById('detailDialog').style.borderTopColor = colors[0];
        document.getElementById('detailDot').style.backgroundColor = colors[0];
        document.getElementById('detailKicker').textContent = `Dashboard › ${m.title}`;
        document.getElementById('detailTitle').textContent = `Rincian ${m.title}`;
        document.getElementById('detailTotal').textContent = m.total;
        document.getElementById('detailUnit').textContent = m.unit;
        document.getElementById('detailHeading').textContent = m.table.heading;

        const t = m.table;
        const rows = t.rows.map(r => {
            const n = r[0];
            const vals = r.slice(1).map(v => typeof v === 'number' ? v : parseNum(v));
            const total = vals.reduce((a, b) => a + b, 0);
            return { n, vals, total };
        });

        const grand = rows.reduce((a, r) => a + r.total, 0);
        const colSum = t.cols.map((_, i) => rows.reduce((a, r) => a + r.vals[i], 0));

        const fmtPct = (x, tot) => tot > 0 ? ((x / tot) * 100).toFixed(1).replace('.', ',') + '%' : '0%';

        // Header
        let thHtml = `<th class="px-4 py-3 text-left font-semibold">${t.label}</th>`;
        t.cols.forEach(c => {
            thHtml += `<th class="px-4 py-3 text-right font-semibold">${c}</th>`;
        });
        thHtml += `<th class="px-4 py-3 text-right font-semibold">Total</th>`;
        thHtml += `<th class="px-4 py-3 text-right font-semibold">%</th>`;
        document.getElementById('detailTheadRow').innerHTML = thHtml;

        // Body
        let tbHtml = '';
        rows.forEach((r, i) => {
            const color = colors[i % colors.length];
            tbHtml += `<tr class="border-t border-slate-100 hover:bg-sky-50/50">`;
            tbHtml += `
                <td class="px-4 py-3">
                    <span class="flex items-center gap-2.5">
                        <span class="h-2 w-2 rounded-full shrink-0" style="background: ${color}"></span>
                        <span class="font-medium text-slate-900">${r.n}</span>
                    </span>
                </td>
            `;
            r.vals.forEach(v => {
                tbHtml += `<td class="px-4 text-right tabular-nums text-slate-600">${v.toLocaleString('id-ID')}</td>`;
            });
            tbHtml += `<td class="px-4 text-right font-bold tabular-nums text-slate-900">${r.total.toLocaleString('id-ID')}</td>`;
            tbHtml += `<td class="px-4 text-right tabular-nums text-slate-500">${fmtPct(r.total, grand)}</td>`;
            tbHtml += `</tr>`;
        });
        document.getElementById('detailTbody').innerHTML = tbHtml;

        // Footer
        let tfHtml = `<tr class="border-t-2 border-slate-200 bg-slate-50 font-bold">`;
        tfHtml += `<td class="px-4 py-3">Total</td>`;
        colSum.forEach(v => {
            tfHtml += `<td class="px-4 text-right tabular-nums">${v.toLocaleString('id-ID')}</td>`;
        });
        tfHtml += `<td class="px-4 text-right tabular-nums" style="color: ${colors[0]}">${grand.toLocaleString('id-ID')}</td>`;
        tfHtml += `<td class="px-4 text-right">100%</td>`;
        tfHtml += `</tr>`;
        document.getElementById('detailTfoot').innerHTML = tfHtml;

        // CSV Download handler
        document.getElementById('detailDownloadBtn').onclick = function() {
            const csvRows = [
                [t.label, ...t.cols, "Total", "Persentase"].join(","),
                ...rows.map(r => [r.n, ...r.vals, r.total, fmtPct(r.total, grand)].join(",")),
                ["Total", ...colSum, grand, "100%"].join(",")
            ];
            const blob = new Blob([csvRows.join("\n")], { type: "text/csv;charset=utf-8;" });
            const link = document.createElement("a");
            link.href = URL.createObjectURL(blob);
            link.download = `${m.title}.csv`;
            link.click();
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

    // INITIAL RENDER
    document.addEventListener('DOMContentLoaded', function() {
        // Render all metric cards (A.1, A.2, N.1, N.2)
        if (typeof metricDataStore !== 'undefined') {
            Object.keys(metricDataStore).forEach(cardId => {
                const m = metricDataStore[cardId];
                const def = m.def || 'donut';
                updateMetricCard(cardId, def);
            });
        }

        // Render viz cards
        if (typeof vizDataStore !== 'undefined' && vizDataStore.length) {
            vizDataStore.forEach((_, idx) => updateVizCard(idx));
        }
    });
</script>
@endpush
