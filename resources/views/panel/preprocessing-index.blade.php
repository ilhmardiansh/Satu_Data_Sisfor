@extends('layouts.panel')

@section('title', 'Pilih Formulir Pre-processing - Panel Satu Data UNUSA')

@section('panel_content')
<div>
    <x-panel-head
        eyebrow="TAHAP 03 · KURASI & PEMBERSIHAN WAJIB"
        scene="preprocessing"
        :badge="$summary['needs_attention'] > 0 ? $summary['needs_attention'].' Perlu Antrean' : 'Semua Terkendali'"
        :badgeTone="$summary['needs_attention'] > 0 ? 'amber' : 'emerald'"
        title="Pilih Formulir Pre-processing"
        desc="Cari dan pilih instrumen formulir yang akan dibersihkan. Halaman direktori ini memudahkan pencarian ketika instrumen berjumlah puluhan atau ratusan."
    >
        <a href="{{ route('panel.formulir') }}" class="inline-flex min-h-[42px] items-center justify-center gap-2 rounded-xl border border-white/25 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/20">
            <i data-lucide="clipboard-list" class="h-4 w-4" aria-hidden="true"></i>
            Kelola Instrumen Formulir
        </a>
    </x-panel-head>

    <!-- 4 Kartu KPI Filter Tahapan -->
    @php
        $baseFilters = array_filter([
            'q' => $q !== '' ? $q : null,
            'kategori' => $kategori !== 'all' ? $kategori : null,
            'tahun' => $tahun !== 'all' ? $tahun : null,
            'periode' => $periode !== 'all' ? $periode : null,
            'sort' => $sort !== 'priority' ? $sort : null,
        ]);
    @endphp
    <div class="mb-5 grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach([
            ['all', 'Semua Formulir', $summary['total'], 'layers', 'text-[#0069A8]', 'bg-[#F0F9FF]'],
            ['needs_attention', 'Perlu Pre-processing', $summary['needs_attention'], 'sparkles', 'text-[#92400E]', 'bg-[#FFFBEB]'],
            ['ready', 'Siap Dataset', $summary['ready'], 'check-circle-2', 'text-[#047857]', 'bg-[#ECFDF5]'],
            ['empty', 'Belum Ada Respons', $summary['empty'], 'inbox', 'text-slate-600', 'bg-slate-100'],
        ] as [$stKey, $stLabel, $stCount, $stIcon, $stText, $stBg])
            @php
                $isActive = $stage === $stKey;
                $href = route('panel.preprocessing', $stKey === 'all' ? $baseFilters : array_merge($baseFilters, ['stage' => $stKey]));
            @endphp
            <a href="{{ $href }}"
               class="wd-bento-interactive flex items-center justify-between gap-3 p-4 {{ $isActive ? 'border-[#0069A8] ring-2 ring-[#00A6F4]/35' : '' }}">
                <div class="min-w-0">
                    <p class="truncate text-xs font-semibold text-[#475569]">{{ $stLabel }}</p>
                    <p class="mt-1 font-mono text-2xl font-extrabold tabular-nums text-slate-900">{{ $stCount }}</p>
                </div>
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl {{ $stBg }} {{ $stText }}">
                    <i data-lucide="{{ $stIcon }}" class="h-5 w-5" aria-hidden="true"></i>
                </span>
            </a>
        @endforeach
    </div>

    <!-- Bilah Pencarian & Filter Direktori -->
    <form method="GET" action="{{ route('panel.preprocessing') }}" class="wd-bento mb-6 p-4">
        @if($stage !== 'all')
            <input type="hidden" name="stage" value="{{ $stage }}">
        @endif

        <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
            <label class="flex h-11 flex-1 items-center gap-2.5 rounded-xl border border-slate-200 bg-[#F8FAFC] px-3.5 focus-within:border-[#0069A8] focus-within:bg-white focus-within:ring-4 focus-within:ring-sky-100">
                <i data-lucide="search" class="h-4 w-4 shrink-0 text-[#0069A8]" aria-hidden="true"></i>
                <input type="text"
                       name="q"
                       value="{{ $q }}"
                       placeholder="Cari kode formulir (mis. F-...) atau judul instrumen..."
                       class="min-w-0 flex-1 bg-transparent text-sm text-slate-900 outline-none placeholder:text-slate-400" />
            </label>

            <div class="flex flex-wrap items-center gap-2">
                <select name="kategori" onchange="this.form.submit()" aria-label="Filter Kategori" class="h-11 rounded-xl border border-slate-200 bg-[#F8FAFC] px-3 text-sm text-slate-800 outline-none focus:border-[#0069A8] focus:bg-white">
                    <option value="all">Semua kategori</option>
                    @foreach($filterCategories as $c)
                        <option value="{{ $c->code }}" {{ $kategori === $c->code ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>

                <select name="tahun" onchange="this.form.submit()" aria-label="Filter Tahun" class="h-11 rounded-xl border border-slate-200 bg-[#F8FAFC] px-3 text-sm text-slate-800 outline-none focus:border-[#0069A8] focus:bg-white">
                    <option value="all">Semua tahun</option>
                    @foreach($years as $y)
                        <option value="{{ $y }}" {{ $tahun === (string) $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>

                <select name="periode" onchange="this.form.submit()" aria-label="Filter Periode" class="h-11 rounded-xl border border-slate-200 bg-[#F8FAFC] px-3 text-sm text-slate-800 outline-none focus:border-[#0069A8] focus:bg-white">
                    <option value="all">Semua semester</option>
                    <option value="Ganjil" {{ $periode === 'Ganjil' ? 'selected' : '' }}>Ganjil</option>
                    <option value="Genap" {{ $periode === 'Genap' ? 'selected' : '' }}>Genap</option>
                </select>

                <select name="sort" onchange="this.form.submit()" aria-label="Urutkan formulir" class="h-11 rounded-xl border border-slate-200 bg-[#F8FAFC] px-3 text-sm font-semibold text-slate-800 outline-none focus:border-[#0069A8] focus:bg-white">
                    <option value="priority" {{ $sort === 'priority' ? 'selected' : '' }}>Urut: Prioritas Antrean</option>
                    <option value="latest" {{ $sort === 'latest' ? 'selected' : '' }}>Urut: Terbaru</option>
                    <option value="responses" {{ $sort === 'responses' ? 'selected' : '' }}>Urut: Respons Terbanyak</option>
                    <option value="title" {{ $sort === 'title' ? 'selected' : '' }}>Urut: Judul A-Z</option>
                </select>

                <button type="submit" class="inline-flex h-11 items-center justify-center gap-1.5 rounded-xl bg-[#0069A8] px-4 text-sm font-bold text-white transition hover:bg-[#182654]">
                    Cari
                </button>

                @if($q !== '' || $kategori !== 'all' || $tahun !== 'all' || $periode !== 'all' || $stage !== 'all' || $sort !== 'priority')
                    <a href="{{ route('panel.preprocessing') }}" class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-3.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Reset
                    </a>
                @endif
            </div>
        </div>

        <p class="mt-3 text-xs text-[#475569]">
            Menampilkan <strong class="font-mono text-slate-900">{{ $formsPaginator->total() }}</strong> instrumen formulir. Klik kartu untuk membuka ruang kerja pembersihan data &amp; normalisasi.
        </p>
    </form>

    <!-- Direktori Kartu Formulir -->
    @if($formsPaginator->total() > 0)
        <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
            @foreach($formsPaginator as $f)
                @php
                    $isReady = $f->isPreprocessed();
                    $needsAttention = ! $isReady && ($f->status_preprocessing === 'proses' || (int) $f->submissions_count > 0);
                    $activeRows = max(0, (int) $f->submissions_count - (int) $f->rejected_submissions_count);
                    $validPct = $activeRows > 0 ? (int) round(((int) $f->valid_submissions_count / $activeRows) * 100) : 0;
                @endphp
                <article class="wd-bento-interactive flex flex-col justify-between overflow-hidden p-5">
                    <div>
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="rounded-md bg-[#F0F9FF] px-2.5 py-1 font-mono text-xs font-extrabold text-[#0069A8]">{{ $f->code }}</span>
                                <span class="rounded-md bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-700">{{ $f->category?->name ?? 'Umum' }}</span>
                                <span class="rounded-md bg-slate-50 px-2 py-1 font-mono text-[11px] font-medium text-[#475569]">{{ $f->tahun }} · {{ $f->periode }}</span>
                            </div>

                            @if($isReady)
                                <span class="inline-flex items-center gap-1 rounded-full bg-[#ECFDF5] px-2.5 py-1 text-[11px] font-bold text-[#047857]">
                                    <span class="h-1.5 w-1.5 rounded-full bg-[#047857]"></span>
                                    Siap Dataset
                                </span>
                            @elseif($needsAttention)
                                <span class="inline-flex items-center gap-1 rounded-full bg-[#FFFBEB] px-2.5 py-1 text-[11px] font-bold text-[#92400E]">
                                    <span class="h-1.5 w-1.5 rounded-full bg-[#D97706]"></span>
                                    Perlu Pre-processing
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600">
                                    Belum Ada Respons
                                </span>
                            @endif
                        </div>

                        <h2 class="mt-3 text-lg font-extrabold tracking-tight text-slate-900">
                            <a href="{{ route('panel.preprocessing.show', $f->code) }}" class="transition hover:text-[#0069A8]">
                                {{ $f->title }}
                            </a>
                        </h2>

                        @if($f->description)
                            <p class="mt-1 line-clamp-2 text-xs leading-relaxed text-[#475569]">{{ $f->description }}</p>
                        @endif

                        <div class="mt-3 flex flex-wrap items-center gap-3 text-[11px] text-[#475569]">
                            <span>Sasaran: <strong class="text-slate-800">{{ $f->target }}</strong></span>
                            <span>&middot;</span>
                            <span>{{ count($f->fields ?? []) }} kolom pertanyaan</span>
                            <span>&middot;</span>
                            <span class="{{ $f->isOpen() ? 'font-semibold text-[#0069A8]' : 'text-slate-500' }}">
                                {{ $f->isOpen() ? 'Formulir Terbuka' : 'Formulir Ditutup' }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-4 space-y-3 border-t border-slate-100 pt-4">
                        <div class="grid grid-cols-4 gap-2 text-center">
                            <div class="rounded-xl bg-[#F8FAFC] px-2 py-2">
                                <span class="block text-[10px] font-semibold text-[#475569]">Total</span>
                                <span class="font-mono text-sm font-extrabold tabular-nums text-slate-900">{{ $f->submissions_count }}</span>
                            </div>
                            <div class="rounded-xl bg-[#FFFBEB]/70 px-2 py-2">
                                <span class="block text-[10px] font-semibold text-[#92400E]">Mentah</span>
                                <span class="font-mono text-sm font-extrabold tabular-nums text-[#92400E]">{{ $f->raw_submissions_count }}</span>
                            </div>
                            <div class="rounded-xl bg-[#ECFDF5]/70 px-2 py-2">
                                <span class="block text-[10px] font-semibold text-[#047857]">Valid</span>
                                <span class="font-mono text-sm font-extrabold tabular-nums text-[#047857]">{{ $f->valid_submissions_count }}</span>
                            </div>
                            <div class="rounded-xl bg-slate-100/70 px-2 py-2">
                                <span class="block text-[10px] font-semibold text-slate-600">Ditolak</span>
                                <span class="font-mono text-sm font-extrabold tabular-nums text-slate-700">{{ $f->rejected_submissions_count }}</span>
                            </div>
                        </div>

                        @if($f->submissions_count > 0)
                            <div>
                                <div class="mb-1 flex items-center justify-between text-[11px] text-[#475569]">
                                    <span>Kesiapan baris aktif</span>
                                    <span class="font-mono font-bold text-slate-800">{{ $validPct }}% valid</span>
                                </div>
                                <div class="h-1.5 overflow-hidden rounded-full bg-slate-200">
                                    <div class="h-full rounded-full {{ $isReady ? 'bg-[#047857]' : 'bg-[#0069A8]' }}" style="width: {{ $validPct }}%"></div>
                                </div>
                            </div>
                        @endif

                        <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
                            <span class="text-[11px] text-[#475569]">
                                @if($f->waktu_diproses)
                                    Terakhir diproses {{ $f->waktu_diproses->copy()->locale('id')->translatedFormat('j M Y, H.i') }}
                                @else
                                    Tenggat {{ $f->deadlineFormatted() }}
                                @endif
                            </span>
                            <a href="{{ route('panel.preprocessing.show', $f->code) }}"
                               class="inline-flex min-h-[38px] items-center gap-1.5 rounded-xl bg-[#0069A8] px-4 py-2 text-xs font-bold text-white transition hover:bg-[#182654]">
                                <span>Buka Pre-processing</span>
                                <i data-lucide="arrow-right" class="h-3.5 w-3.5" aria-hidden="true"></i>
                            </a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $formsPaginator->links('components.pagination') }}
        </div>
    @else
        <div class="wd-bento border-dashed border-slate-300 px-6 py-14 text-center">
            <span class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-[#F0F9FF] text-[#0069A8]">
                <i data-lucide="folder-search" class="h-6 w-6" aria-hidden="true"></i>
            </span>
            @if($summary['total'] > 0)
                <p class="mt-4 font-bold text-slate-800">Tidak ada formulir yang cocok dengan pencarian atau filter</p>
                <p class="mt-1 text-xs text-[#475569]">Coba ubah kata kunci atau reset filter direktori formulir.</p>
                <a href="{{ route('panel.preprocessing') }}" class="mt-4 inline-flex min-h-[40px] items-center rounded-xl bg-[#0069A8] px-4 py-2 text-xs font-bold text-white hover:bg-[#182654]">
                    Tampilkan semua formulir
                </a>
            @else
                <p class="mt-4 font-bold text-slate-800">Belum ada instrumen formulir</p>
                <p class="mt-1 text-xs text-[#475569]">Buat instrumen formulir terlebih dahulu agar respons pengumpulan data dapat melalui fase Pre-processing.</p>
                <a href="{{ route('panel.formulir.create') }}" class="mt-4 inline-flex min-h-[40px] items-center rounded-xl bg-[#0069A8] px-4 py-2 text-xs font-bold text-white hover:bg-[#182654]">
                    Buat formulir baru
                </a>
            @endif
        </div>
    @endif
</div>
@endsection
