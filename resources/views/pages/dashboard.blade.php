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

<main>
    <!-- Header Arsitektural Dashboard (UNUSA Benhur Navy) -->
    <section class="sd-hero-band relative isolate overflow-hidden">
        <div data-scene="waves"
             aria-hidden="true"
             class="pointer-events-none absolute inset-0 opacity-45"
             style="mask-image: radial-gradient(ellipse at top right, #000 45%, transparent 92%); -webkit-mask-image: radial-gradient(ellipse at top right, #000 45%, transparent 92%);"></div>

        <div class="relative z-10 mx-auto max-w-[1200px] px-4 py-10 sm:px-8 sm:py-12">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="max-w-3xl">
                    <nav aria-label="Breadcrumb" class="text-sm text-slate-200">
                        <a href="{{ route('beranda') }}" class="sd-focus-dark hover:text-white">{{ $t('Beranda', 'Home') }}</a>
                        <span class="mx-1.5 text-white/60">/</span>
                        <span class="font-semibold text-[#74D4FF]">Dashboard</span>
                    </nav>
                    <h1 class="mt-2 text-[28px] leading-tight font-extrabold tracking-tight text-white sm:text-[36px]">
                        Dashboard Sistem Informasi
                    </h1>
                    <p class="mt-2 max-w-2xl text-[15px] leading-relaxed text-slate-200">
                        {{ $t(
                            'Indikator kinerja tridharma, sumber daya, dan mutu Program Studi Sistem Informasi UNUSA. Ubah parameter maupun tipe grafik langsung di setiap kartu.',
                            'Tridharma performance indicators, resources, and quality assurance for UNUSA Information Systems. Switch parameters or chart types directly on each card.'
                        ) }}
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <a href="{{ route('dataset') }}" class="sd-focus-dark inline-flex min-h-[44px] items-center gap-2 rounded-xl border border-white/25 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-white/20">
                        <i data-lucide="database" class="h-4 w-4 text-[#74D4FF]" aria-hidden="true"></i>
                        {{ $t('Buka Katalog Dataset', 'Open Dataset Catalog') }}
                    </a>

                    <button type="button"
                            data-motion-toggle
                            onclick="toggleSceneMotion(this)"
                            aria-pressed="false"
                            class="sd-focus-dark hidden min-h-[44px] items-center gap-1.5 rounded-xl border border-white/20 bg-white/10 px-3.5 py-2 text-xs font-medium text-white transition-colors hover:bg-white/20 md:inline-flex">
                        <i data-lucide="pause-circle" class="h-4 w-4" aria-hidden="true"></i>
                        <span data-motion-label>{{ $t('Jeda latar 3D', 'Pause 3D backdrop') }}</span>
                    </button>
                </div>
            </div>

            <!-- Baris Ringkasan Nyata dari Basis Data + Navigasi Seksi -->
            <div class="mt-8 flex flex-wrap items-center justify-between gap-6 border-t border-white/15 pt-6">
                <dl class="grid grid-cols-2 gap-x-8 gap-y-4 sm:flex sm:flex-wrap sm:items-center sm:gap-x-10">
                    <div>
                        <dt class="text-xs font-medium text-slate-200">{{ $t('Indikator Inti', 'Core Indicators') }}</dt>
                        <dd class="mt-0.5 text-2xl font-extrabold tracking-tight tabular-nums text-white">{{ $summaryStats['metricCards'] }}</dd>
                    </div>
                    <div class="hidden h-8 w-px bg-white/15 sm:block" aria-hidden="true"></div>
                    <div>
                        <dt class="text-xs font-medium text-slate-200">{{ $t('Visualisasi Pilihan', 'Featured Charts') }}</dt>
                        <dd class="mt-0.5 text-2xl font-extrabold tracking-tight tabular-nums text-white">{{ $summaryStats['featuredCharts'] }}</dd>
                    </div>
                    <div class="hidden h-8 w-px bg-white/15 sm:block" aria-hidden="true"></div>
                    <div>
                        <dt class="text-xs font-medium text-slate-200">{{ $t('Dataset Publik', 'Public Datasets') }}</dt>
                        <dd class="mt-0.5 text-2xl font-extrabold tracking-tight tabular-nums text-white">{{ $summaryStats['publicDatasets'] }}</dd>
                    </div>
                    <div class="hidden h-8 w-px bg-white/15 sm:block" aria-hidden="true"></div>
                    <div>
                        <dt class="text-xs font-medium text-slate-200">{{ $t('Kategori Data', 'Data Categories') }}</dt>
                        <dd class="mt-0.5 text-2xl font-extrabold tracking-tight tabular-nums text-white">{{ $summaryStats['categories'] }}</dd>
                    </div>
                </dl>

                <nav aria-label="{{ $t('Loncat ke seksi', 'Jump to section') }}" class="flex flex-wrap gap-2">
                    @if(! empty($vizList))
                        <a href="#visualisasi-pilihan" class="sd-focus-dark inline-flex min-h-[40px] items-center rounded-lg border border-white/20 bg-white/10 px-3 py-1.5 text-xs font-semibold text-white transition-colors hover:bg-white/20">
                            {{ $t('Visualisasi Prodi', 'Featured Charts') }}
                        </a>
                    @endif
                    @if($summaryStats['metricCards'] > 0)
                        <a href="#seksi-akademik" class="sd-focus-dark inline-flex min-h-[40px] items-center rounded-lg border border-white/20 bg-white/10 px-3 py-1.5 text-xs font-semibold text-white transition-colors hover:bg-white/20">
                            {{ $t('A. Akademik', 'A. Academic') }}
                        </a>
                        <a href="#seksi-non-akademik" class="sd-focus-dark inline-flex min-h-[40px] items-center rounded-lg border border-white/20 bg-white/10 px-3 py-1.5 text-xs font-semibold text-white transition-colors hover:bg-white/20">
                            {{ $t('N. Non-Akademik', 'N. Non-Academic') }}
                        </a>
                    @endif
                </nav>
            </div>
        </div>
    </section>

    <!-- Visualisasi Pilihan Prodi (Galeri Asimetris Terbuka) -->
    @if(! empty($vizList))
        <section id="visualisasi-pilihan" class="scroll-mt-20 border-b border-slate-200/80 bg-[#f6f9fc] py-10 sm:py-12">
            <div class="mx-auto max-w-[1200px] px-4 sm:px-8">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div class="flex items-start gap-3">
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-[#182654] text-[#74D4FF]">
                            <i data-lucide="bar-chart-3" class="h-5 w-5" aria-hidden="true"></i>
                        </span>
                        <div>
                            <h2 class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">
                                {{ $t('Visualisasi pilihan prodi', 'Featured visualizations') }}
                            </h2>
                            <p class="mt-0.5 text-sm text-[#475569]">
                                {{ $t('Disusun Wali Data langsung dari dataset publik dan hasil pengumpulan instrumen resmi prodi', 'Curated by the Data Steward directly from published study program datasets') }}
                            </p>
                        </div>
                    </div>

                    <div class="flex w-full flex-wrap items-center gap-2 sm:w-auto">
                        <select id="vizFilterCat"
                                onchange="filterVizCards()"
                                aria-label="{{ $t('Kategori', 'Category') }}"
                                class="min-h-[42px] min-w-0 flex-1 rounded-xl border border-slate-200 bg-white px-3 text-sm font-medium text-slate-800 outline-none transition-colors hover:border-[#0069a8] focus:border-[#0069a8] sm:flex-none">
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
                                class="min-h-[42px] min-w-0 flex-1 rounded-xl border border-slate-200 bg-white px-3 text-sm font-medium text-slate-800 outline-none transition-colors hover:border-[#0069a8] focus:border-[#0069a8] sm:flex-none">
                            <option value="all">{{ $t('Semua tahun', 'All years') }}</option>
                            @foreach($years as $y)
                                <option value="{{ $y }}" {{ $selectedYear === (string) $y ? 'selected' : '' }}>
                                    {{ $y }}
                                </option>
                            @endforeach
                        </select>

                        <button type="button"
                                id="vizResetBtn"
                                onclick="resetVizFilters()"
                                class="hidden min-h-[42px] items-center justify-center rounded-xl border border-slate-200 bg-white px-3.5 text-sm font-semibold text-[#0069a8] transition-colors hover:border-[#0069a8] hover:bg-[#f0f9ff]">
                            Reset
                        </button>
                    </div>
                </div>

                <div id="vizCardsGrid" class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
                    @foreach($vizList as $vIdx => $v)
                        @php $isLead = ($vIdx === 0 && count($vizList) > 1); @endphp
                        <article id="vizCardArt_{{ $vIdx }}"
                                 data-cat="{{ $v['kategori'] }}"
                                 data-year="{{ $v['tahun'] }}"
                                 data-donut-card
                                 class="viz-card-item sd-card sd-card-hover flex flex-col p-5 sm:p-6 {{ $isLead ? 'lg:col-span-2' : '' }}">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2 text-xs">
                                        <span class="rounded-md bg-[#f0f9ff] px-2 py-0.5 font-semibold tabular-nums text-[#0069a8]">{{ $v['id'] }}</span>
                                        <span class="font-semibold text-[#475569]">{{ $v['kategoriName'] }}</span>
                                        <span class="text-slate-400">·</span>
                                        <span class="font-medium tabular-nums text-[#475569]">{{ $v['tahun'] }}</span>
                                    </div>
                                    <h3 class="mt-2 text-lg font-extrabold tracking-tight text-slate-900">{{ $v['title'] }}</h3>
                                    <p class="mt-1 truncate text-xs font-medium text-[#475569]" title="{{ $v['datasetTitle'] }}">
                                        Sumber: {{ $v['datasetTitle'] }}
                                    </p>
                                </div>

                                <div class="flex flex-wrap items-center gap-2">
                                    <a href="{{ $v['datasetUrl'] }}"
                                       class="inline-flex min-h-[40px] items-center gap-1.5 rounded-xl border border-slate-200 bg-[#f6f9fc] px-3 py-1.5 text-xs font-bold text-[#0069a8] transition-colors hover:border-[#0069a8] hover:bg-[#f0f9ff]">
                                        <i data-lucide="external-link" class="h-3.5 w-3.5" aria-hidden="true"></i>
                                        {{ $t('Lihat dataset', 'View dataset') }}
                                    </a>
                                </div>
                            </div>

                            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <label class="block">
                                    <span class="mb-1 block text-[11px] font-semibold tracking-wide text-[#475569] uppercase">Parameter Nilai</span>
                                    <select id="vizCol_{{ $vIdx }}"
                                            onchange="updateVizCard({{ $vIdx }})"
                                            aria-label="Parameter nilai {{ $v['title'] }}"
                                            class="min-h-[40px] w-full rounded-xl border border-slate-200 bg-[#f6f9fc] px-3 py-1.5 text-xs font-semibold text-slate-800 outline-none transition-colors hover:border-[#0069a8] focus:border-[#0069a8]">
                                        @foreach($v['numericCols'] as $cIdx => $cName)
                                            <option value="{{ $cIdx }}" {{ (int) $cIdx === (int) $v['valueCol'] ? 'selected' : '' }}>
                                                {{ $cName }}
                                            </option>
                                        @endforeach
                                    </select>
                                </label>

                                <label class="block">
                                    <span class="mb-1 block text-[11px] font-semibold tracking-wide text-[#475569] uppercase">Jenis Grafik</span>
                                    <select id="vizKind_{{ $vIdx }}"
                                            onchange="updateVizCard({{ $vIdx }})"
                                            aria-label="Jenis grafik {{ $v['title'] }}"
                                            class="min-h-[40px] w-full rounded-xl border border-slate-200 bg-[#f6f9fc] px-3 py-1.5 text-xs font-semibold text-slate-800 outline-none transition-colors hover:border-[#0069a8] focus:border-[#0069a8]">
                                        <option value="number" {{ $v['kind'] === 'number' ? 'selected' : '' }}>Angka</option>
                                        <option value="donut" {{ $v['kind'] === 'donut' ? 'selected' : '' }}>Donat</option>
                                        <option value="pie" {{ $v['kind'] === 'pie' ? 'selected' : '' }}>Pai</option>
                                        <option value="bar" {{ $v['kind'] === 'bar' ? 'selected' : '' }}>Batang</option>
                                        <option value="hbar" {{ $v['kind'] === 'hbar' ? 'selected' : '' }}>Horizontal</option>
                                        <option value="line" {{ $v['kind'] === 'line' ? 'selected' : '' }}>Garis</option>
                                        <option value="radar" {{ $v['kind'] === 'radar' ? 'selected' : '' }}>Radar</option>
                                    </select>
                                </label>
                            </div>

                            <div class="mt-5 {{ $isLead ? 'grid grid-cols-1 items-center gap-6 lg:grid-cols-[1.25fr_0.9fr]' : '' }}">
                                <div class="relative {{ $isLead ? 'h-56' : 'h-48' }} w-full">
                                    <div id="vizRenderSlot_{{ $vIdx }}" class="h-full w-full"></div>
                                    <div id="vizDonutCenter_{{ $vIdx }}" class="pointer-events-none absolute inset-0 grid place-items-center text-center {{ $v['kind'] === 'donut' ? '' : 'hidden' }}">
                                        <div class="max-w-[125px] px-1">
                                            <p id="vizDonutTotal_{{ $vIdx }}" data-donut-total class="text-2xl font-extrabold leading-none tracking-tight tabular-nums text-slate-900"></p>
                                            <p id="vizDonutColName_{{ $vIdx }}" data-donut-sub class="mt-1 truncate text-[11px] font-semibold text-[#475569]"></p>
                                        </div>
                                    </div>
                                </div>

                                <ul id="vizLegend_{{ $vIdx }}" class="{{ $isLead ? 'space-y-2.5' : 'mt-4 space-y-2' }} text-[13px] tabular-nums"></ul>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div id="vizEmptyNotice" class="hidden rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center">
                    <p class="font-bold text-slate-900">{{ $t('Tidak ada visualisasi untuk kategori dan tahun yang dipilih.', 'No visualizations match the selected category and year.') }}</p>
                    <p class="mt-1 text-sm text-[#475569]">{{ $t('Ubah pilihan filter atau tampilkan kembali seluruh visualisasi prodi.', 'Adjust filter choices or reset to view all featured charts.') }}</p>
                    <button type="button" onclick="resetVizFilters()" class="sd-btn-primary mt-4 min-h-[42px] px-5 py-2 text-sm">
                        {{ $t('Reset filter', 'Reset filter') }}
                    </button>
                </div>
            </div>
        </section>
    @endif

    @if(empty($vizList) && $summaryStats['metricCards'] === 0)
        <section id="dashboard-empty-state" class="mx-auto max-w-[1200px] px-4 py-12 sm:px-8">
            <div class="rounded-3xl border border-slate-200 bg-[#F6F9FC] p-6 sm:p-10">
                <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-200/80 pb-6">
                    <div class="max-w-2xl">
                        <span class="inline-flex items-center rounded-md bg-[#F0F9FF] px-2.5 py-1 text-xs font-bold text-[#0069A8]">
                            {{ $t('Satu Data Prodi', 'Satu Data Flow') }}
                        </span>
                        <h2 class="mt-3 text-2xl font-extrabold tracking-tight text-slate-900">
                            {{ $t('Belum ada visualisasi dashboard yang diterbitkan', 'No published dashboard visualizations yet') }}
                        </h2>
                        <p class="mt-2 text-sm leading-relaxed text-[#475569]">
                            {{ $t(
                                'Seluruh indikator dan grafik di portal ini bersumber langsung dari instrumen pengumpulan data resmi yang telah melewati fase Pre-processing dan pengemasan Dataset.',
                                'All indicators and charts on this portal are derived from official collection forms verified through Pre-processing and published Datasets.'
                            ) }}
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2.5">
                        <a href="{{ route('pengisian-data') }}" class="inline-flex min-h-[44px] items-center gap-2 rounded-xl bg-[#0069A8] px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-[#182654]">
                            {{ $t('Portal Pengisian Data', 'Data Collection Portal') }}
                        </a>
                        @auth
                            @if(auth()->user()->isStaff())
                                <a href="{{ route('panel.formulir') }}" class="inline-flex min-h-[44px] items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-800 transition-colors hover:border-[#0069A8] hover:text-[#0069A8]">
                                    {{ $t('Kelola di Panel', 'Manage in Panel') }}
                                </a>
                            @endif
                        @endauth
                    </div>
                </div>

                <ol class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <li class="rounded-2xl border border-slate-200/80 bg-white p-4">
                        <p class="text-xs font-bold text-[#0069A8]">Formulir GForm</p>
                        <p class="mt-1.5 text-sm font-bold text-slate-900">Rancang Instrumen</p>
                        <p class="mt-1 text-xs leading-relaxed text-[#475569]">Wali Data atau Admin menyusun formulir pertanyaan dan sasaran responden.</p>
                    </li>
                    <li class="rounded-2xl border border-slate-200/80 bg-white p-4">
                        <p class="text-xs font-bold text-[#0069A8]">Pengumpulan</p>
                        <p class="mt-1.5 text-sm font-bold text-slate-900">Isi Formulir / Impor</p>
                        <p class="mt-1 text-xs leading-relaxed text-[#475569]">Produsen Data mengisi tautan GForm atau Wali Data mengimpor respons Excel/CSV.</p>
                    </li>
                    <li class="rounded-2xl border border-slate-200/80 bg-white p-4">
                        <p class="text-xs font-bold text-[#92400E]">Pre-processing</p>
                        <p class="mt-1.5 text-sm font-bold text-slate-900">Pembersihan &amp; Validasi</p>
                        <p class="mt-1 text-xs leading-relaxed text-[#475569]">Normalisasi format, deduplikasi, penanganan missing value, dan finalisasi status valid.</p>
                    </li>
                    <li class="rounded-2xl border border-slate-200/80 bg-white p-4">
                        <p class="text-xs font-bold text-[#047857]">Dataset &amp; Grafik</p>
                        <p class="mt-1.5 text-sm font-bold text-slate-900">Publikasi Visualisasi</p>
                        <p class="mt-1 text-xs leading-relaxed text-[#475569]">Dataset bernumerik yang terbit dapat divisualisasikan langsung ke Dashboard ini.</p>
                    </li>
                </ol>
            </div>
        </section>
    @endif

    @if($summaryStats['metricCards'] > 0)
    <div class="mx-auto max-w-[1200px] px-4 py-12 sm:px-8">
        <!-- Seksi A -->
        <section id="seksi-akademik" class="scroll-mt-20">
            <div class="flex flex-wrap items-end justify-between gap-3 border-b border-slate-200 pb-4">
                <h2 class="flex items-center gap-3 text-2xl font-extrabold tracking-tight text-slate-900">
                    <span class="grid h-9 w-9 place-items-center rounded-xl bg-[#0069a8] text-sm font-extrabold text-white">A</span>
                    Akademik
                </h2>
                <p class="text-sm font-medium text-[#475569]">
                    Tridharma: pendidikan, penelitian, dan pengabdian beserta luarannya
                </p>
            </div>

            <!-- Group A.1 (Kartu terbelah horizontal 2 kolom agar lega dibaca) -->
            <div class="mt-8">
                <div class="mb-5 flex items-center gap-3 text-sm">
                    <span class="inline-flex items-center rounded-md bg-[#f0f9ff] px-2.5 py-0.5 text-xs font-bold tabular-nums text-[#0069a8]">A.1</span>
                    <h3 class="font-bold text-slate-900">Pendidikan dan mahasiswa</h3>
                    <span class="h-px min-w-4 flex-1 bg-slate-200"></span>
                    <span class="shrink-0 text-xs font-semibold tabular-nums text-[#475569]">{{ count($metrics['akademik1']) }} indikator</span>
                </div>
                <div class="reveal-stagger grid grid-cols-1 gap-6 lg:grid-cols-2">
                    @foreach($metrics['akademik1'] as $idx => $m)
                        @include('pages.partials.metric-card', ['m' => $m, 'cardId' => 'ak1_'.$idx, 'layout' => 'split'])
                    @endforeach
                </div>
            </div>

            <!-- Group A.2 -->
            <div class="mt-10">
                <div class="mb-5 flex items-center gap-3 text-sm">
                    <span class="inline-flex items-center rounded-md bg-[#f0f9ff] px-2.5 py-0.5 text-xs font-bold tabular-nums text-[#0069a8]">A.2</span>
                    <h3 class="font-bold text-slate-900">Penelitian, prestasi, dan luaran</h3>
                    <span class="h-px min-w-4 flex-1 bg-slate-200"></span>
                    <span class="shrink-0 text-xs font-semibold tabular-nums text-[#475569]">{{ count($metrics['akademik2']) }} indikator</span>
                </div>
                <div class="reveal-stagger grid grid-cols-1 gap-6 lg:grid-cols-2">
                    @foreach($metrics['akademik2'] as $idx => $m)
                        @include('pages.partials.metric-card', ['m' => $m, 'cardId' => 'ak2_'.$idx, 'layout' => 'split'])
                    @endforeach
                </div>
            </div>
        </section>

        <!-- Seksi N -->
        <section id="seksi-non-akademik" class="mt-16 scroll-mt-20">
            <div class="flex flex-wrap items-end justify-between gap-3 border-b border-slate-200 pb-4">
                <h2 class="flex items-center gap-3 text-2xl font-extrabold tracking-tight text-slate-900">
                    <span class="grid h-9 w-9 place-items-center rounded-xl bg-[#182654] text-sm font-extrabold text-white">N</span>
                    Non-Akademik
                </h2>
                <p class="text-sm font-medium text-[#475569]">
                    Sumber daya dan tata kelola yang menopang tridharma
                </p>
            </div>

            <!-- Group N.1 (3 kolom vertikal) -->
            <div class="mt-8">
                <div class="mb-5 flex items-center gap-3 text-sm">
                    <span class="inline-flex items-center rounded-md bg-[#f0f9ff] px-2.5 py-0.5 text-xs font-bold tabular-nums text-[#0069a8]">N.1</span>
                    <h3 class="font-bold text-slate-900">Sumber daya manusia</h3>
                    <span class="h-px min-w-4 flex-1 bg-slate-200"></span>
                    <span class="shrink-0 text-xs font-semibold tabular-nums text-[#475569]">{{ count($metrics['nonakademik1']) }} indikator</span>
                </div>
                <div class="reveal-stagger grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach($metrics['nonakademik1'] as $idx => $m)
                        @include('pages.partials.metric-card', ['m' => $m, 'cardId' => 'nak1_'.$idx, 'layout' => 'vertical'])
                    @endforeach
                </div>
            </div>

            <!-- Group N.2 (Spotlight penuh horizontal) -->
            <div class="mt-10">
                <div class="mb-5 flex items-center gap-3 text-sm">
                    <span class="inline-flex items-center rounded-md bg-[#f0f9ff] px-2.5 py-0.5 text-xs font-bold tabular-nums text-[#0069a8]">N.2</span>
                    <h3 class="font-bold text-slate-900">Tata kelola dan kemitraan</h3>
                    <span class="h-px min-w-4 flex-1 bg-slate-200"></span>
                    <span class="shrink-0 text-xs font-semibold tabular-nums text-[#475569]">{{ count($metrics['nonakademik2']) }} indikator</span>
                </div>
                <div class="reveal-stagger grid grid-cols-1 gap-6">
                    @foreach($metrics['nonakademik2'] as $idx => $m)
                        @include('pages.partials.metric-card', ['m' => $m, 'cardId' => 'nak2_'.$idx, 'layout' => 'spotlight'])
                    @endforeach
                </div>
            </div>
        </section>
    </div>
    @endif
</main>

<!-- Modal Detail Agregat -->
<div id="detailModal"
     class="fixed inset-0 z-50 hidden place-items-center bg-slate-900/50 p-3 sm:p-6"
     onclick="closeDetailModal()">
    <div id="detailDialog"
         role="dialog"
         aria-modal="true"
         aria-labelledby="detailTitle"
         class="flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl border border-slate-200 border-t-4 bg-white shadow-2xl"
         onclick="event.stopPropagation()">
        <div class="flex items-start justify-between gap-6 border-b border-slate-200 px-5 pt-6 pb-5 sm:px-8">
            <div>
                <p class="flex items-center gap-2 text-sm font-medium text-[#475569]">
                    <span id="detailDot" class="h-2.5 w-2.5 rounded-full"></span>
                    <span id="detailKicker">Dashboard › Judul</span>
                </p>
                <h2 id="detailTitle" class="mt-2 text-2xl font-extrabold tracking-tight text-slate-900">Rincian</h2>
                <p class="mt-1 flex items-baseline gap-2">
                    <span id="detailTotal" class="text-4xl font-extrabold tabular-nums text-slate-900"></span>
                    <span id="detailUnit" class="text-sm font-medium text-[#475569]"></span>
                </p>
            </div>
            <button type="button"
                    onclick="closeDetailModal()"
                    aria-label="Tutup dialog"
                    class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 transition-colors hover:border-[#0069a8] hover:text-[#0069a8]">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <div class="overflow-y-auto px-5 py-6 sm:px-8">
            <h3 id="detailHeading" class="mb-3 font-bold text-slate-900"></h3>
            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="w-full text-sm">
                    <thead class="sticky top-0 bg-[#f6f9fc] text-xs tracking-wide text-[#475569] uppercase">
                        <tr id="detailTheadRow"></tr>
                    </thead>
                    <tbody id="detailTbody"></tbody>
                    <tfoot id="detailTfoot"></tfoot>
                </table>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:px-8">
            <p class="text-sm font-medium text-[#475569]">Sumber: Program Studi Sistem Informasi UNUSA</p>
            <div class="ml-auto flex shrink-0 gap-3">
                <button type="button" id="detailDownloadBtn"
                        class="min-h-[44px] rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-800 transition-colors hover:border-[#0069a8] hover:text-[#0069a8]">
                    Unduh CSV
                </button>
                <button type="button" onclick="closeDetailModal()"
                        class="sd-btn-primary min-h-[44px] px-5 py-2 text-sm font-bold">
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
        const cardEl = document.getElementById('vizCardArt_' + idx);
        const colIdx = parseInt(colSelect.value, 10);
        const kind = kindSelect.value;
        const colors = v.palette || window.SatuDataCharts.palettes.sky;

        const seriesData = window.SatuDataCharts.series(v.rows, v.labelCol, colIdx);
        const total = seriesData.reduce((acc, cur) => acc + cur.v, 0);
        const maxVal = Math.max(...seriesData.map((x) => Math.max(0, Number(x.v) || 0)), 1);

        const renderSlot = document.getElementById('vizRenderSlot_' + idx);
        const donutCenter = document.getElementById('vizDonutCenter_' + idx);
        const legend = document.getElementById('vizLegend_' + idx);
        const totalFormatted = total.toLocaleString('id-ID');
        const colLabel = v.cols[colIdx] || '';

        if (cardEl) {
            cardEl.dataset.defaultTotal = totalFormatted;
            cardEl.dataset.defaultSub = colLabel;
        }

        if (kind === 'donut') {
            donutCenter?.classList.remove('hidden');
            document.getElementById('vizDonutTotal_' + idx).textContent = totalFormatted;
            document.getElementById('vizDonutColName_' + idx).textContent = colLabel;
        } else {
            donutCenter?.classList.add('hidden');
        }

        const isLead = cardEl?.classList.contains('lg:col-span-2');
        const svgW = isLead ? 420 : 320;
        const svgH = isLead ? 210 : 175;

        if (renderSlot) {
            renderSlot.innerHTML = window.SatuDataCharts.renderSvg(kind, seriesData, colors, svgW, svgH);
        }

        if (legend) {
            const limit = isLead ? 8 : 5;
            legend.innerHTML = seriesData.slice(0, limit).map((x, i) => {
                const color = colors[i % colors.length];
                const barW = maxVal > 0 ? Math.max(4, Math.round((Math.max(0, x.v) / maxVal) * 1000) / 10) : 0;
                return `
                    <li>
                        <div class="flex items-center justify-between gap-2">
                            <span class="flex min-w-0 items-center gap-2">
                                <span class="h-2.5 w-2.5 shrink-0 rounded-[3px]" style="background: ${color}"></span>
                                <span class="truncate font-medium text-slate-700">${window.SatuDataCharts.escapeHtml(x.n)}</span>
                            </span>
                            <span class="shrink-0 text-xs font-semibold tabular-nums text-slate-900">${x.v.toLocaleString('id-ID')}</span>
                        </div>
                        <div class="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-slate-100" aria-hidden="true">
                            <div class="h-full rounded-full" style="width: ${barW}%; background: ${color}"></div>
                        </div>
                    </li>
                `;
            }).join('');
        }
    }

    function filterVizCards() {
        const catVal = document.getElementById('vizFilterCat')?.value || 'all';
        const yearVal = document.getElementById('vizFilterYear')?.value || 'all';
        const items = document.querySelectorAll('.viz-card-item');
        const resetBtn = document.getElementById('vizResetBtn');
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

        const isFiltered = catVal !== 'all' || yearVal !== 'all';
        if (resetBtn) {
            resetBtn.classList.toggle('hidden', !isFiltered);
            resetBtn.classList.toggle('inline-flex', isFiltered);
        }

        const notice = document.getElementById('vizEmptyNotice');
        if (notice) {
            notice.classList.toggle('hidden', visibleCount > 0);
        }
    }

    function resetVizFilters() {
        const catSel = document.getElementById('vizFilterCat');
        const yearSel = document.getElementById('vizFilterYear');
        if (catSel) catSel.value = 'all';
        if (yearSel) yearSel.value = 'all';
        filterVizCards();
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
        thHtml += `<th class="px-4 py-3 text-right font-semibold">Porsi</th>`;
        document.getElementById('detailTheadRow').innerHTML = thHtml;

        document.getElementById('detailTbody').innerHTML = rows.map((r, i) => {
            const color = colors[i % colors.length];
            const pctNum = grand > 0 ? Math.min(100, Math.round((r.total / grand) * 1000) / 10) : 0;
            const valCells = r.vals.map((v) => `<td class="px-4 text-right tabular-nums text-slate-700">${v.toLocaleString('id-ID')}</td>`).join('');
            return `
                <tr class="border-t border-slate-100 hover:bg-[#f0f9ff]/60">
                    <td class="px-4 py-3 font-medium text-slate-900">
                        <span class="flex items-center gap-2.5">
                            <span class="h-2.5 w-2.5 shrink-0 rounded-sm" style="background: ${color}"></span>
                            ${escHtml(r.n)}
                        </span>
                    </td>
                    ${valCells}
                    <td class="px-4 text-right font-bold tabular-nums text-slate-900">${r.total.toLocaleString('id-ID')}</td>
                    <td class="px-4 py-3 text-right tabular-nums text-[#475569]">
                        <div class="ml-auto flex max-w-[100px] items-center justify-end gap-2">
                            <span class="h-1.5 w-10 overflow-hidden rounded-full bg-slate-100" aria-hidden="true">
                                <span class="block h-full rounded-full" style="width: ${pctNum}%; background: ${color}"></span>
                            </span>
                            <span>${fmtPct(r.total, grand)}</span>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        const sumCells = colSum.map((v) => `<td class="px-4 text-right tabular-nums text-slate-900">${v.toLocaleString('id-ID')}</td>`).join('');
        document.getElementById('detailTfoot').innerHTML = `
            <tr class="border-t-2 border-slate-200 bg-slate-50 font-bold text-slate-900">
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
