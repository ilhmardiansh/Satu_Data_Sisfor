@extends('layouts.panel')

@section('title', 'Pre-processing Data - Panel Satu Data UNUSA')

@section('panel_content')
<div x-data="collectedDataManager(@js($activeForm?->fields[0]['id'] ?? ''))">
    <x-panel-head
        eyebrow="TAHAP 03 · KURASI & PEMBERSIHAN WAJIB"
        scene="preprocessing"
        :badge="$activeForm ? $activeForm->preprocessingStatusLabel() : null"
        :badgeTone="$activeForm?->isPreprocessed() ? 'emerald' : ($problemCount > 0 ? 'amber' : 'sky')"
        title="Pre-processing Data"
        desc="Pemeriksaan kualitas, normalisasi format, deduplikasi, dan penanganan nilai kosong wajib diselesaikan sebelum data dikemas menjadi dataset."
    >
        @if($activeForm)
            <button
                type="button"
                @click="importOpen = true"
                class="inline-flex min-h-[42px] items-center justify-center gap-2 rounded-xl border border-white/25 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/20"
            >
                <i data-lucide="upload" class="h-4 w-4" aria-hidden="true"></i>
                Impor CSV / XLSX GForm
            </button>
            <a href="{{ route('panel.preprocessing.csv', $activeForm->code) }}" class="inline-flex min-h-[42px] items-center justify-center gap-2 rounded-xl border border-white/25 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/20">
                <i data-lucide="download" class="h-4 w-4" aria-hidden="true"></i>
                Unduh CSV
            </a>
            <form method="POST" action="{{ route('panel.preprocessing.finalize', $activeForm->code) }}" class="inline">
                @csrf
                <button
                    type="submit"
                    {{ ($problemCount > 0 || $profile['active_count'] === 0) ? 'disabled' : '' }}
                    class="inline-flex min-h-[42px] items-center justify-center gap-2 rounded-xl bg-emerald-500 px-4 py-2 text-sm font-bold text-white transition hover:bg-emerald-400 disabled:cursor-not-allowed disabled:opacity-45"
                >
                    <i data-lucide="check-check" class="h-4 w-4" aria-hidden="true"></i>
                    Finalisasi Pre-processing
                </button>
            </form>
            <button
                type="button"
                @click="openBuilder({{ $activeForm->id }})"
                {{ (! $activeForm->isPreprocessed() || $profile['valid'] === 0) ? 'disabled' : '' }}
                class="inline-flex min-h-[42px] items-center justify-center gap-2 rounded-xl bg-[#00A6F4] px-4 py-2 text-sm font-bold text-[#09122C] transition hover:bg-[#38BDF8] disabled:cursor-not-allowed disabled:opacity-45"
            >
                <i data-lucide="table-2" class="h-4 w-4" aria-hidden="true"></i>
                Susun Dataset
            </button>
        @endif
    </x-panel-head>

    <!-- Bar Navigasi Formulir Aktif & Pencarian Baris -->
    <div class="wd-bento mb-5 flex flex-wrap items-center justify-between gap-3 p-4">
        <div class="flex min-w-0 flex-1 flex-wrap items-center gap-3">
            <a href="{{ route('panel.preprocessing') }}"
               class="inline-flex min-h-[40px] shrink-0 items-center gap-2 rounded-xl border border-slate-200 bg-[#F8FAFC] px-3.5 py-2 text-xs font-bold text-slate-700 transition hover:border-[#0069A8] hover:bg-[#F0F9FF] hover:text-[#0069A8]">
                <i data-lucide="arrow-left" class="h-3.5 w-3.5" aria-hidden="true"></i>
                Pilih Formulir Lain
            </a>
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="rounded-md bg-[#F0F9FF] px-2 py-0.5 font-mono text-[11px] font-bold text-[#0069A8]">{{ $activeForm->code }}</span>
                    <span class="text-xs font-medium text-[#475569]">{{ $activeForm->category?->name ?? 'Umum' }} · {{ $activeForm->tahun }} · {{ $activeForm->periode }}</span>
                </div>
                <p class="mt-0.5 truncate text-sm font-extrabold text-slate-900">{{ $activeForm->title }}</p>
            </div>
        </div>

        <form method="GET" action="{{ route('panel.preprocessing.show', $activeForm->code) }}" class="flex w-full flex-wrap items-center gap-2 sm:w-auto">
            @if($status !== 'all')
                <input type="hidden" name="status" value="{{ $status }}">
            @endif
            <label class="flex h-10 min-w-[200px] flex-1 items-center gap-2 rounded-xl border border-slate-200 bg-[#F8FAFC] px-3 focus-within:border-[#0069A8] focus-within:bg-white sm:w-64">
                <i data-lucide="search" class="h-4 w-4 text-[#475569]" aria-hidden="true"></i>
                <input type="text" name="q" value="{{ $q }}" placeholder="Cari pengisi, NIM, atau isi..." class="min-w-0 flex-1 bg-transparent text-sm outline-none" />
            </label>
            <button type="submit" class="inline-flex h-10 items-center rounded-xl bg-[#0069A8] px-3.5 text-xs font-bold text-white transition hover:bg-[#182654]">
                Cari
            </button>
            @if($q !== '')
                <a href="{{ route('panel.preprocessing.show', $status !== 'all' ? ['dataForm' => $activeForm->code, 'status' => $status] : $activeForm->code) }}"
                   class="inline-flex h-10 items-center rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                    Reset
                </a>
            @endif
        </form>
    </div>

    @if($activeForm)
        @php
            $cleanRate = $profile['active_count'] > 0
                ? (int) round((max(0, $profile['active_count'] - $problemCount) / $profile['active_count']) * 100)
                : 0;
            $stepperActive = $activeForm->isPreprocessed() ? 4 : ($totalSubmissionsCount > 0 ? ($problemCount === 0 ? 3 : 2) : 1);
            $r = 26;
            $circ = round(2 * M_PI * $r, 2);
            $off = round($circ * (1 - ($cleanRate / 100)), 2);
        @endphp

        <!-- Banner Status Pipeline & 4-Step Visual Stepper -->
        <section class="wd-bento mb-5 overflow-hidden">
            <div class="border-b px-5 py-5 {{ $activeForm->isPreprocessed() ? 'border-emerald-200 bg-[#ECFDF5]/80' : ($problemCount > 0 ? 'border-red-200 bg-[#FEF2F2]/80' : 'border-amber-200 bg-[#FFFBEB]/80') }}">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex min-w-0 flex-1 items-center gap-4">
                        <!-- Mini Clean-Rate Radial -->
                        <div class="relative hidden h-16 w-16 shrink-0 place-items-center sm:grid">
                            <svg class="h-16 w-16 -rotate-90" viewBox="0 0 64 64" aria-hidden="true">
                                <circle cx="32" cy="32" r="{{ $r }}" fill="none" stroke="rgba(15,23,42,0.1)" stroke-width="5.5" />
                                <circle cx="32" cy="32" r="{{ $r }}" fill="none" stroke="{{ $activeForm->isPreprocessed() ? '#047857' : ($problemCount > 0 ? '#B91C1C' : '#0069A8') }}" stroke-width="5.5" stroke-linecap="round" stroke-dasharray="{{ $circ }}" stroke-dashoffset="{{ $totalSubmissionsCount > 0 ? $off : $circ }}" />
                            </svg>
                            <span class="absolute font-mono text-xs font-extrabold tabular-nums text-slate-900">
                                {{ $totalSubmissionsCount > 0 ? $cleanRate.'%' : '0%' }}
                            </span>
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-md bg-white px-2.5 py-0.5 font-mono text-xs font-bold text-[#182654] ring-1 ring-slate-200">{{ $activeForm->code }}</span>
                                <span class="rounded-md bg-white px-2.5 py-0.5 text-xs font-bold ring-1 ring-slate-200/70 {{ $activeForm->isPreprocessed() ? 'text-[#047857]' : 'text-[#92400E]' }}">
                                    {{ $activeForm->preprocessingStatusLabel() }}
                                </span>
                            </div>
                            <h2 class="mt-1.5 text-lg font-extrabold tracking-tight text-slate-900">
                                @if($activeForm->isPreprocessed())
                                    Data siap dikemas menjadi Dataset ({{ $profile['valid'] }} baris valid)
                                @elseif($totalSubmissionsCount === 0)
                                    Belum ada respons masuk untuk diproses
                                @elseif($problemCount > 0)
                                    {{ $problemCount }} dari {{ $profile['active_count'] }} baris aktif memerlukan pembersihan
                                @else
                                    Seluruh {{ $profile['active_count'] }} baris aktif bersih · klik Finalisasi Pre-processing untuk membuka tahap Dataset
                                @endif
                            </h2>
                        </div>
                    </div>

                    <dl class="grid grid-cols-2 gap-2.5 sm:grid-cols-4">
                        <div class="rounded-xl bg-white px-3.5 py-2 text-center ring-1 ring-slate-200/80">
                            <dt class="text-[11px] font-semibold text-[#475569]">Mentah</dt>
                            <dd class="mt-0.5 font-mono text-lg font-extrabold tabular-nums text-[#92400E]">{{ $profile['mentah'] }}</dd>
                        </div>
                        <div class="rounded-xl bg-white px-3.5 py-2 text-center ring-1 ring-slate-200/80">
                            <dt class="text-[11px] font-semibold text-[#475569]">Bermasalah</dt>
                            <dd class="mt-0.5 font-mono text-lg font-extrabold tabular-nums text-[#B91C1C]">{{ $profile['issue_rows'] }}</dd>
                        </div>
                        <div class="rounded-xl bg-white px-3.5 py-2 text-center ring-1 ring-slate-200/80">
                            <dt class="text-[11px] font-semibold text-[#475569]">Valid</dt>
                            <dd class="mt-0.5 font-mono text-lg font-extrabold tabular-nums text-[#047857]">{{ $profile['valid'] }}</dd>
                        </div>
                        <div class="rounded-xl bg-white px-3.5 py-2 text-center ring-1 ring-slate-200/80">
                            <dt class="text-[11px] font-semibold text-[#475569]">Ditolak</dt>
                            <dd class="mt-0.5 font-mono text-lg font-extrabold tabular-nums text-slate-700">{{ $profile['ditolak'] }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- 4-Step Visual Pipeline Stepper -->
            <div class="grid grid-cols-2 divide-y divide-slate-100 bg-white text-xs sm:grid-cols-4 sm:divide-x sm:divide-y-0">
                @foreach([
                    1 => ['01', 'Respons Mentah', 'Masuk dari GForm / Impor'],
                    2 => ['02', 'Normalisasi & Dedup', 'Seragamkan & buang ganda'],
                    3 => ['03', 'Missing Value', 'Imputasi atau tandai ditolak'],
                    4 => ['04', 'Finalisasi Dataset', 'Buka gerbang susun dataset'],
                ] as $stepNum => [$code, $label, $sub])
                    <div class="flex items-center gap-3 px-4 py-3 {{ $stepperActive >= $stepNum ? 'bg-white' : 'bg-[#F8FAFC]/70 opacity-65' }}">
                        <span class="grid h-7 w-7 shrink-0 place-items-center rounded-lg font-mono text-[11px] font-extrabold {{ $stepperActive > $stepNum ? 'bg-[#ECFDF5] text-[#047857]' : ($stepperActive === $stepNum ? 'bg-[#0069A8] text-white' : 'bg-slate-200 text-slate-600') }}">
                            {{ $code }}
                        </span>
                        <div class="min-w-0">
                            <p class="truncate font-bold text-slate-900">{{ $label }}</p>
                            <p class="truncate text-[11px] text-[#475569]">{{ $sub }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        @if($totalSubmissionsCount > 0)
            <!-- Profiling Kualitas per Kolom (Collapsible + Smart Target to Langkah C) -->
            <section class="wd-bento mb-5 p-5">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900">Profiling Kualitas per Kolom</h3>
                        <p class="text-xs text-[#475569]">Menghitung {{ $profile['active_count'] }} baris non-ditolak</p>
                    </div>
                    <button type="button"
                            @click="profilingExpanded = !profilingExpanded"
                            class="inline-flex min-h-[34px] items-center gap-1.5 rounded-lg border border-slate-200 bg-[#F8FAFC] px-3 text-xs font-semibold text-slate-700 hover:border-[#0069A8] hover:text-[#0069A8]">
                        <span x-text="profilingExpanded ? 'Ringkas' : 'Tampilkan detail'"></span>
                    </button>
                </div>

                <div x-show="profilingExpanded" x-transition class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($profile['columns'] as $colStat)
                        <div class="rounded-xl border border-slate-200/80 bg-[#F8FAFC] p-3.5">
                            <div class="flex items-start justify-between gap-2">
                                <p class="truncate text-xs font-bold text-slate-900" title="{{ $colStat['label'] }}">{{ $colStat['label'] }}</p>
                                <span class="shrink-0 rounded bg-white px-1.5 py-0.5 font-mono text-[10px] font-semibold text-[#0069A8] ring-1 ring-slate-200">{{ $colStat['type'] }}</span>
                            </div>
                            <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-slate-200">
                                <div class="h-full rounded-full {{ $colStat['completeness_pct'] >= 99.9 ? 'bg-[#047857]' : 'bg-[#0069A8]' }}" style="width: {{ $colStat['completeness_pct'] }}%"></div>
                            </div>
                            <div class="mt-2 flex flex-wrap items-center justify-between gap-x-3 gap-y-1 text-[11px] tabular-nums text-[#475569]">
                                <span>Lengkap: <strong class="text-slate-900">{{ $colStat['completeness_pct'] }}%</strong></span>
                                <span>Kosong: <strong class="{{ $colStat['missing'] > 0 ? 'text-[#B91C1C]' : 'text-slate-700' }}">{{ $colStat['missing'] }}</strong></span>
                                <span>Invalid: <strong class="{{ $colStat['invalid'] > 0 ? 'text-[#B91C1C]' : 'text-slate-700' }}">{{ $colStat['invalid'] }}</strong></span>
                                <span>Unik: <strong class="text-slate-800">{{ $colStat['unique_count'] }}</strong></span>
                            </div>
                            @if($colStat['missing'] > 0 && ! empty($colStat['id']))
                                <button type="button"
                                        @click="targetMissingField(@js($colStat['id']))"
                                        class="mt-2.5 inline-flex items-center gap-1 rounded-lg border border-red-200 bg-white px-2.5 py-1 text-[11px] font-bold text-[#B91C1C] transition hover:bg-red-50">
                                    Tangani {{ $colStat['missing'] }} kosong →
                                </button>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>

            <!-- Toolbar Aksi Pre-processing (Normalisasi, Deduplikasi, Missing Values) -->
            <section class="mb-5 grid grid-cols-1 gap-4 lg:grid-cols-3">
                <!-- 1. Normalisasi Batch -->
                <form method="POST" action="{{ route('panel.preprocessing.normalize', $activeForm->code) }}" class="wd-bento flex flex-col p-5">
                    @csrf
                    <div class="flex items-center gap-2.5">
                        <span class="grid h-7 w-7 place-items-center rounded-lg bg-[#F0F9FF] font-mono text-xs font-extrabold text-[#0069A8]">A</span>
                        <p class="text-xs font-extrabold tracking-wide text-[#0069A8] uppercase">Normalisasi Batch</p>
                    </div>
                    <p class="mt-2 text-xs leading-relaxed text-[#475569]">Merapikan spasi ganda, menyeragamkan label pilihan/checkbox, dan menstandarkan angka Indonesia.</p>
                    <div class="mt-3.5 space-y-2.5 text-xs">
                        <label class="flex cursor-pointer items-center gap-2 text-slate-800">
                            <input type="checkbox" name="normalize_numbers" value="1" checked class="h-4 w-4 accent-[#0069A8]" />
                            <span>Bersihkan format angka (Rp / titik ribuan / koma)</span>
                        </label>
                        <label class="block">
                            <span class="mb-1 block font-semibold text-slate-700">Kapitalisasi kolom teks</span>
                            <select name="text_case" class="h-9 w-full rounded-lg border border-slate-200 bg-[#F8FAFC] px-2.5 text-xs text-slate-800">
                                <option value="none">Pertahankan huruf asli (rapikan spasi saja)</option>
                                <option value="title">Title Case (Contoh: Nama Kompetisi)</option>
                                <option value="upper">HURUF KAPITAL SEMUA</option>
                                <option value="lower">huruf kecil semua</option>
                            </select>
                        </label>
                    </div>
                    <button type="submit" class="mt-auto pt-4">
                        <span class="inline-flex min-h-[40px] w-full items-center justify-center rounded-xl border border-[#0069A8] bg-[#F0F9FF] px-3 py-2 text-xs font-bold text-[#0069A8] transition hover:bg-[#0069A8] hover:text-white">
                            Jalankan Normalisasi Batch
                        </span>
                    </button>
                </form>

                <!-- 2. Deduplikasi -->
                <form method="POST" action="{{ route('panel.preprocessing.deduplicate', $activeForm->code) }}" class="wd-bento flex flex-col p-5">
                    @csrf
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2.5">
                            <span class="grid h-7 w-7 place-items-center rounded-lg bg-[#F0F9FF] font-mono text-xs font-extrabold text-[#0069A8]">B</span>
                            <p class="text-xs font-extrabold tracking-wide text-[#0069A8] uppercase">Deduplikasi</p>
                        </div>
                        @if($profile['duplicate_rows'] > 0)
                            <span class="rounded-full bg-[#FFFBEB] px-2 py-0.5 font-mono text-[11px] font-bold text-[#92400E] ring-1 ring-amber-200">{{ $profile['duplicate_rows'] }} ganda</span>
                        @endif
                    </div>
                    <p class="mt-2 text-xs leading-relaxed text-[#475569]">Mendeteksi entri ganda (terdeteksi saat ini: <strong class="text-slate-900">{{ $profile['duplicate_rows'] }}</strong> baris). Baris terbaru dipertahankan.</p>
                    <div class="mt-3.5 space-y-2.5 text-xs">
                        <label class="block">
                            <span class="mb-1 block font-semibold text-slate-700">Kriteria kesamaan</span>
                            <select name="scope" class="h-9 w-full rounded-lg border border-slate-200 bg-[#F8FAFC] px-2.5 text-xs text-slate-800">
                                <option value="same_values">Isi seluruh kolom identik</option>
                                <option value="same_user_and_values">Pengirim &amp; isi sama persis</option>
                            </select>
                        </label>
                        <label class="block">
                            <span class="mb-1 block font-semibold text-slate-700">Tindakan untuk duplikat</span>
                            <select name="action" class="h-9 w-full rounded-lg border border-slate-200 bg-[#F8FAFC] px-2.5 text-xs text-slate-800">
                                <option value="reject">Tandai ditolak (pertahankan log audit)</option>
                                <option value="delete">Hapus permanen dari respons</option>
                            </select>
                        </label>
                    </div>
                    <button type="submit" class="mt-auto pt-4">
                        <span class="inline-flex min-h-[40px] w-full items-center justify-center rounded-xl border border-[#0069A8] bg-[#F0F9FF] px-3 py-2 text-xs font-bold text-[#0069A8] transition hover:bg-[#0069A8] hover:text-white">
                            Tangani Baris Duplikat
                        </span>
                    </button>
                </form>

                <!-- 3. Penanganan Missing Values -->
                <form id="langkah-c"
                      method="POST"
                      action="{{ route('panel.preprocessing.impute', $activeForm->code) }}"
                      x-data="{ strat: 'mode' }"
                      :class="imputePulse ? 'ring-2 ring-[#00A6F4] border-[#0069A8]' : ''"
                      class="wd-bento flex flex-col p-5 transition-all">
                    @csrf
                    <div class="flex items-center gap-2.5">
                        <span class="grid h-7 w-7 place-items-center rounded-lg bg-[#F0F9FF] font-mono text-xs font-extrabold text-[#0069A8]">C</span>
                        <p class="text-xs font-extrabold tracking-wide text-[#0069A8] uppercase">Missing Value</p>
                    </div>
                    <p class="mt-2 text-xs leading-relaxed text-[#475569]">Imputasi nilai kosong per kolom (mean, median, modus, nilai tetap) atau tandai ditolak.</p>
                    <div class="mt-3.5 space-y-2 text-xs">
                        <label class="block">
                            <span class="mb-1 block font-semibold text-slate-700">Kolom target</span>
                            <select name="field_code" x-model="imputeFieldCode" class="h-9 w-full rounded-lg border border-slate-200 bg-[#F8FAFC] px-2.5 text-xs text-slate-800">
                                @foreach($activeForm->fields ?? [] as $fl)
                                    <option value="{{ $fl['id'] }}">{{ $fl['label'] }} ({{ $fl['type'] }})</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block">
                            <span class="mb-1 block font-semibold text-slate-700">Strategi</span>
                            <select name="strategy" x-model="strat" class="h-9 w-full rounded-lg border border-slate-200 bg-[#F8FAFC] px-2.5 text-xs text-slate-800">
                                <option value="mode">Isi dengan Modus (nilai terbanyak)</option>
                                <option value="mean">Isi dengan Rata-rata (Mean angka)</option>
                                <option value="median">Isi dengan Median (angka)</option>
                                <option value="constant">Isi dengan Nilai Tetap</option>
                                <option value="reject_missing">Tandai baris kosong sebagai Ditolak</option>
                            </select>
                        </label>
                        <input x-show="strat === 'constant'" type="text" name="custom_value" placeholder="Masukkan nilai tetap..." class="h-9 w-full rounded-lg border border-slate-200 bg-white px-2.5 text-xs text-slate-900" />
                    </div>
                    <button type="submit" class="mt-auto pt-4">
                        <span class="inline-flex min-h-[40px] w-full items-center justify-center rounded-xl border border-[#0069A8] bg-[#F0F9FF] px-3 py-2 text-xs font-bold text-[#0069A8] transition hover:bg-[#0069A8] hover:text-white">
                            Terapkan Strategi
                        </span>
                    </button>
                </form>
            </section>
        @endif

        <!-- Tab Filter Status Baris -->
        <div class="mb-4 flex flex-wrap gap-1.5">
            @foreach([
                'all' => ['Semua', $profile['total']],
                'mentah' => ['Mentah', $profile['mentah']],
                'issues' => ['Bermasalah', $profile['issue_rows']],
                'valid' => ['Valid', $profile['valid']],
                'ditolak' => ['Ditolak', $profile['ditolak']],
            ] as $sKey => [$sLabel, $sCount])
                <a href="{{ route('panel.preprocessing.show', array_filter(['dataForm' => $activeForm->code, 'q' => $q !== '' ? $q : null, 'status' => $sKey !== 'all' ? $sKey : null])) }}"
                   class="inline-flex min-h-[38px] items-center gap-1.5 rounded-xl border px-3.5 py-1.5 text-xs font-bold transition {{ $status === $sKey ? 'border-[#0069A8] bg-[#0069A8] text-white' : 'border-slate-200 bg-white text-slate-700 hover:border-[#0069A8]/50 hover:bg-slate-50' }}">
                    <span>{{ $sLabel }}</span>
                    <span class="font-mono opacity-85">{{ $sCount }}</span>
                </a>
            @endforeach
        </div>

        @if($submissionsPaginator->total() > 0)
            @php $cols = $activeForm->sourceCols(); @endphp
            <div class="wd-bento overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="wd-hairline-table">
                        <thead>
                            <tr>
                                <th class="sticky left-0 z-10 bg-[#F8FAFC] px-4 py-3 text-left text-[11px] font-bold tracking-wider whitespace-nowrap text-[#475569] uppercase shadow-[1px_0_0_rgba(15,23,42,0.06)]">Baris &amp; Status</th>
                                @foreach($cols as $col)
                                    <th class="px-4 py-3 text-left text-[11px] font-bold tracking-wider whitespace-nowrap text-[#475569] uppercase">{{ $col['label'] }}</th>
                                @endforeach
                                <th class="px-4 py-3 text-left text-[11px] font-bold tracking-wider whitespace-nowrap text-[#475569] uppercase">Kualitas &amp; Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($submissionsPaginator as $sub)
                                @php
                                    $issues = $qualityIssues[$sub->id] ?? [];
                                    $rawMap = $sub->rawValues ?? [];
                                    $fieldValues = $sub->values ?? [];
                                    $subAttachments = $sub->attachments ?? [];
                                    $rowBg = $sub->status_validasi === 'ditolak'
                                        ? 'bg-slate-50/90 opacity-80'
                                        : (! empty($issues) ? 'bg-[#FEF2F2]' : 'bg-white hover:bg-[#F8FAFC]');
                                    $stickyBg = $sub->status_validasi === 'ditolak'
                                        ? 'bg-slate-50'
                                        : (! empty($issues) ? 'bg-[#FEF2F2]' : 'bg-white');
                                @endphp
                                <tr class="{{ $rowBg }}">
                                    <td class="sticky left-0 z-10 px-4 py-3 align-top text-xs whitespace-nowrap shadow-[1px_0_0_rgba(15,23,42,0.06)] {{ $stickyBg }}">
                                        <p class="font-mono font-bold text-slate-900">{{ $sub->code }}</p>
                                        <span class="mt-1 inline-flex rounded px-1.5 py-0.5 text-[10px] font-bold {{ $sub->sumber_input === 'impor' ? 'bg-slate-100 text-slate-700' : 'bg-[#F0F9FF] text-[#0069A8]' }}">
                                            {{ $sub->sumber_input === 'impor' ? 'Impor' : 'GForm' }}
                                        </span>
                                        <span class="mt-1 ml-1 inline-flex rounded px-1.5 py-0.5 text-[10px] font-bold {{ $sub->status_validasi === 'valid' ? 'bg-[#ECFDF5] text-[#047857]' : ($sub->status_validasi === 'ditolak' ? 'bg-slate-200 text-slate-700' : 'bg-[#FFFBEB] text-[#92400E]') }}">
                                            {{ $sub->status_validasi === 'valid' ? 'Valid' : ($sub->status_validasi === 'ditolak' ? 'Ditolak' : 'Mentah') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 align-top text-sm font-semibold text-slate-900">{{ $sub->user?->name ?? '-' }}</td>
                                    <td class="px-4 py-3 align-top text-sm text-slate-600">{{ $sub->user?->nim ?? '-' }}</td>
                                    <td class="px-4 py-3 align-top text-xs whitespace-nowrap text-slate-600">{{ $sub->submittedFormatted() }}</td>
                                    @foreach($activeForm->fields ?? [] as $fl)
                                        @php
                                            $fid = $fl['id'];
                                            $val = (string) ($fieldValues[$fid] ?? '');
                                            $rawVal = (string) ($rawMap[$fid] ?? $val);
                                            $isFileCol = ($fl['type'] ?? '') === 'file';
                                            $attPath = $isFileCol ? ($subAttachments[$fid] ?? null) : null;
                                            $attExists = is_string($attPath) && $attPath !== '' && \Illuminate\Support\Facades\Storage::disk('local')->exists($attPath);
                                            $attSize = $attExists ? (int) \Illuminate\Support\Facades\Storage::disk('local')->size($attPath) : 0;
                                        @endphp
                                        <td class="max-w-[240px] px-4 py-3 align-top text-sm text-slate-800" title="{{ $val }}">
                                            @if($isFileCol)
                                                @if($attExists && $attSize > 0)
                                                    <div class="space-y-1.5">
                                                        <p class="truncate font-medium text-[#0069A8]">{{ $val !== '' ? $val : basename($attPath) }}</p>
                                                        <div class="flex flex-wrap items-center gap-1.5">
                                                            <a href="{{ route('panel.submissions.file', [$sub->code, $fid, 'inline' => 1]) }}" target="_blank" rel="noopener" class="rounded-md border border-slate-200 bg-white px-2 py-0.5 text-[11px] font-bold text-slate-700 hover:border-[#0069A8] hover:text-[#0069A8]">Lihat</a>
                                                            <a href="{{ route('panel.submissions.file', [$sub->code, $fid]) }}" class="rounded-md border border-[#BAE6FD] bg-[#F0F9FF] px-2 py-0.5 text-[11px] font-bold text-[#0069A8] hover:bg-sky-100">Unduh</a>
                                                        </div>
                                                    </div>
                                                @else
                                                    <div class="space-y-1">
                                                        <span class="inline-flex rounded bg-red-50 px-2 py-0.5 text-[11px] font-semibold text-[#B91C1C]">
                                                            {{ ($attExists && $attSize === 0) ? 'Berkas kosong (0 B)' : 'Belum ada bukti' }}
                                                        </span>
                                                        <div>
                                                            <a href="{{ route('panel.preprocessing.submissions.edit', $sub->code) }}" class="text-[11px] font-bold text-[#0069A8] hover:underline">Unggah bukti →</a>
                                                        </div>
                                                    </div>
                                                @endif
                                            @else
                                                <p class="truncate">{{ $val !== '' ? $val : '-' }}</p>
                                                @if($rawVal !== $val)
                                                    <p class="truncate text-[11px] text-[#475569]">Mentah: {{ $rawVal !== '' ? $rawVal : '(kosong)' }}</p>
                                                @endif
                                            @endif
                                        </td>
                                    @endforeach
                                    <td class="px-4 py-3 align-top text-sm text-slate-700">
                                        <div class="min-w-52 text-left">
                                            @if($sub->status_validasi === 'ditolak')
                                                <p class="mb-2 text-xs font-medium text-slate-600">
                                                    Dikecualikan dari dataset{{ $sub->catatan_validasi ? ': '.$sub->catatan_validasi : '.' }}
                                                </p>
                                            @elseif(! empty($issues))
                                                <ul class="mb-2 list-inside list-disc text-xs font-medium text-[#B91C1C]">
                                                    @foreach($issues as $issue)
                                                        <li>{{ $issue }}</li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                <p class="mb-2 text-xs font-semibold text-[#047857]">Tidak ada masalah kualitas</p>
                                            @endif
                                        </div>
                                        <div class="flex flex-wrap gap-1.5">
                                            <a
                                                href="{{ route('panel.preprocessing.submissions.edit', $sub->code) }}"
                                                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 transition hover:border-[#0069A8] hover:text-[#0069A8]"
                                                title="Perbaiki data isian di halaman khusus"
                                                aria-label="Perbaiki isian"
                                            >
                                                <i data-lucide="pencil" class="h-4 w-4" aria-hidden="true"></i>
                                            </a>
                                            <form method="POST" action="{{ route('panel.preprocessing.submissions.status', $sub->code) }}" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status_validasi" value="{{ $sub->status_validasi === 'ditolak' ? 'mentah' : 'ditolak' }}">
                                                <button
                                                    type="submit"
                                                    class="inline-flex h-9 items-center justify-center rounded-xl border border-slate-200 bg-white px-2.5 text-xs font-semibold text-slate-700 transition hover:border-[#0069A8] hover:text-[#0069A8]"
                                                    title="{{ $sub->status_validasi === 'ditolak' ? 'Pulihkan ke Mentah' : 'Tolak baris ini' }}"
                                                >
                                                    {{ $sub->status_validasi === 'ditolak' ? 'Pulihkan' : 'Tolak' }}
                                                </button>
                                            </form>
                                            <button
                                                type="button"
                                                @click="$dispatch('open-confirm', {
                                                    message: 'Isian ini akan dihapus permanen dari basis data.',
                                                    action: @js(route('panel.data.submissions.destroy', $sub->code)),
                                                    method: 'DELETE'
                                                })"
                                                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-red-200 hover:bg-red-50 hover:text-[#B91C1C]"
                                                aria-label="Hapus isian"
                                            >
                                                <i data-lucide="trash-2" class="h-4 w-4" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $submissionsPaginator->links('components.pagination') }}
            </div>
        @else
            <div class="wd-bento border-dashed border-slate-300 px-6 py-14 text-center">
                <span class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-[#F0F9FF] text-[#0069A8]">
                    <i data-lucide="sparkles" class="h-6 w-6" aria-hidden="true"></i>
                </span>
                <p class="mt-4 font-bold text-slate-800">
                    {{ $status !== 'all' ? 'Tidak ada baris pada filter status ini' : ($q !== '' ? 'Isian tidak ditemukan' : 'Belum ada respons untuk formulir ini') }}
                </p>
                <p class="mt-1 text-xs text-[#475569]">Kumpulkan respons melalui halaman GForm atau klik "Impor CSV / XLSX GForm" di pojok kanan atas.</p>
            </div>
        @endif

        @if(! empty($preprocessingLogs))
            <section class="wd-bento mt-6 p-5">
                <p class="font-mono text-[10px] font-bold tracking-[0.14em] text-[#0069A8] uppercase">Audit Log</p>
                <h3 class="mt-0.5 text-sm font-extrabold text-slate-900">Jejak Audit Pre-processing</h3>
                <ol class="mt-4 space-y-3 border-l-2 border-slate-200 pl-4 text-xs">
                    @foreach(array_slice($preprocessingLogs, 0, 8) as $log)
                        <li class="relative flex flex-wrap items-center justify-between gap-2">
                            <span class="absolute -left-[21px] h-2.5 w-2.5 rounded-full bg-[#0069A8] ring-4 ring-white" aria-hidden="true"></span>
                            <span>
                                <strong class="font-bold text-[#0069A8]">{{ $log['action'] ?? 'Aksi' }}:</strong>
                                <span class="text-slate-700">{{ $log['detail'] ?? '' }}</span>
                            </span>
                            <span class="font-mono text-[#475569]">{{ $log['at_label'] ?? '' }}</span>
                        </li>
                    @endforeach
                </ol>
            </section>
        @endif
    @else
        <div class="wd-bento border-dashed border-slate-300 px-6 py-14 text-center">
            <span class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-[#F0F9FF] text-[#0069A8]">
                <i data-lucide="database" class="h-6 w-6" aria-hidden="true"></i>
            </span>
            <p class="mt-4 font-bold text-slate-800">Belum ada formulir pada filter ini</p>
            <p class="mt-1 text-sm text-[#475569]">Buat instrumen formulir terlebih dahulu agar respons pengumpulan data dapat diproses.</p>
            <a href="{{ route('panel.formulir') }}" class="mt-4 inline-flex min-h-[42px] items-center rounded-xl bg-[#0069A8] px-4 py-2 text-sm font-semibold text-white hover:bg-[#182654]">
                Buka Instrumen Formulir
            </a>
        </div>
    @endif

    <!-- Import Spreadsheet Responses Modal -->
    @if($activeForm)
        <template x-teleport="body">
            <div x-show="importOpen" x-cloak class="fixed inset-0 z-[65] grid place-items-end bg-slate-950/55 backdrop-blur-xs sm:place-items-center sm:p-6" @click.self="importOpen = false" @keydown.escape.window="importOpen = false">
                <div role="dialog" aria-modal="true" aria-labelledby="import-modal-title" class="flex max-h-[92vh] w-full max-w-lg flex-col overflow-hidden rounded-t-2xl border border-slate-200 bg-white shadow-2xl sm:rounded-2xl">
                    <div class="flex items-start justify-between gap-4 border-b border-slate-200 bg-[#F8FAFC] px-5 pt-5 pb-4 sm:px-7 sm:pt-6">
                        <div class="min-w-0">
                            <h2 id="import-modal-title" class="text-xl font-extrabold tracking-tight text-slate-900">Impor Respons GForm (Excel / CSV)</h2>
                            <p class="mt-1 text-sm text-[#475569]">{{ $activeForm->title }}</p>
                        </div>
                        <button type="button" @click="importOpen = false" aria-label="Tutup" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 hover:border-[#0069A8] hover:text-[#0069A8]">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>

                    <form action="{{ route('panel.preprocessing.import', $activeForm->code) }}" method="POST" enctype="multipart/form-data" class="flex min-h-0 flex-col">
                        @csrf
                        <div class="space-y-4 overflow-y-auto px-5 py-6 sm:px-7">
                            <div class="rounded-xl border border-[#BAE6FD] bg-[#F0F9FF] px-4 py-3 text-sm text-[#182654]">
                                Unggah berkas respons <strong>.xlsx</strong> atau <strong>.csv</strong> hasil ekspor Google Forms atau Excel. Seluruh baris masuk sebagai data mentah (<code>status_validasi = mentah</code>) untuk dibersihkan pada fase Pre-processing.
                            </div>

                            <label class="block">
                                <span class="mb-1.5 block text-sm font-semibold text-slate-800">Pilih Berkas (.xlsx / .csv) <span class="text-[#B91C1C]">*</span></span>
                                <input type="file" name="file" accept=".csv,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required class="block w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-slate-700 hover:file:bg-slate-200" />
                            </label>

                            <div>
                                <p class="mb-2 text-xs font-semibold tracking-wide text-[#475569] uppercase">Kolom formulir yang dikenali</p>
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach($activeForm->fields ?? [] as $fl)
                                        <span class="rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-700">{{ $fl['label'] }}</span>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:px-7">
                            <button type="button" @click="importOpen = false" class="inline-flex min-h-[42px] items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                Batal
                            </button>
                            <button type="submit" class="inline-flex min-h-[42px] items-center justify-center gap-2 rounded-xl bg-[#0069A8] px-5 py-2 text-sm font-semibold text-white hover:bg-[#182654]">
                                Impor Sekarang
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>
    @endif

    @include('panel.partials.dataset-builder-modal')
</div>
@endsection

@push('scripts')
@include('panel.partials.dataset-builder-script')
<script>
function collectedDataManager(defaultFieldCode) {
    return {
        ...datasetBuilderMixin(@json($builderFormsPayload)),
        importOpen: false,
        profilingExpanded: true,
        imputeFieldCode: defaultFieldCode || '',
        imputePulse: false,

        init() {
            window.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    this.importOpen = false;
                    this.builderOpen = false;
                }
            });
        },

        targetMissingField(fieldCode) {
            this.imputeFieldCode = fieldCode;
            this.imputePulse = true;
            document.getElementById('langkah-c')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            setTimeout(() => {
                this.imputePulse = false;
            }, 1800);
        },
    };
}
</script>
@endpush
