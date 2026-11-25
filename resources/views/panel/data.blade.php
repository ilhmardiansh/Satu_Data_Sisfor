@extends('layouts.panel')

@section('title', 'Pre-processing Data - Panel Satu Data UNUSA')

@section('panel_content')
<div x-data="collectedDataManager(@js($activeForm?->fields[0]['id'] ?? ''), {{ $problemCount > 0 ? 'true' : 'false' }})">
    <x-panel-head
        scene="preprocessing"
        :badge="$activeForm ? $activeForm->preprocessingStatusLabel() : null"
        :badgeTone="$activeForm?->isPreprocessed() ? 'emerald' : ($problemCount > 0 ? 'amber' : 'sky')"
        title="Pre-processing Data"
        desc="Bersihkan, seragamkan, dan validasi respons sebelum dibuat menjadi dataset."
    >
        @if($activeForm)
            <button
                type="button"
                @click="importOpen = true"
                class="inline-flex min-h-[42px] items-center justify-center gap-2 rounded-xl border border-white/25 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/20"
            >
                <i data-lucide="upload" class="h-4 w-4" aria-hidden="true"></i>
                Impor CSV / XLSX
            </button>
            <a href="{{ route('panel.preprocessing.csv', $activeForm->code) }}"
               class="inline-flex min-h-[42px] items-center justify-center gap-2 rounded-xl border border-white/25 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/20">
                <i data-lucide="download" class="h-4 w-4" aria-hidden="true"></i>
                Unduh CSV
            </a>
            <form method="POST" action="{{ route('panel.preprocessing.finalize', $activeForm->code) }}" class="inline">
                @csrf
                <button
                    type="submit"
                    {{ ($problemCount > 0 || $profile['active_count'] === 0) ? 'disabled' : '' }}
                    class="inline-flex min-h-[42px] items-center justify-center gap-2 rounded-xl bg-[#047857] px-4 py-2 text-sm font-semibold text-white ring-1 ring-emerald-300/30 transition hover:bg-[#065F46] disabled:cursor-not-allowed disabled:opacity-45"
                >
                    <i data-lucide="check-check" class="h-4 w-4" aria-hidden="true"></i>
                    Finalisasi Pre-processing
                </button>
            </form>
            @if(! empty($existingFormDataset))
                <a href="{{ route('panel.dataset.edit', $existingFormDataset->code) }}"
                   class="inline-flex min-h-[42px] items-center justify-center gap-2 rounded-xl bg-[#00A6F4] px-4 py-2 text-sm font-bold text-[#09122C] transition hover:bg-[#38BDF8]">
                    <i data-lucide="table-2" class="h-4 w-4" aria-hidden="true"></i>
                    Kelola Dataset ({{ $existingFormDataset->code }})
                </a>
            @elseif($activeForm->isPreprocessed() && $profile['valid'] > 0)
                <a href="{{ route('panel.dataset.create', ['form' => $activeForm->code]) }}"
                   class="inline-flex min-h-[42px] items-center justify-center gap-2 rounded-xl bg-[#00A6F4] px-4 py-2 text-sm font-bold text-[#09122C] transition hover:bg-[#38BDF8]">
                    <i data-lucide="table-2" class="h-4 w-4" aria-hidden="true"></i>
                    Susun Dataset
                </a>
            @else
                <button type="button"
                        disabled
                        class="inline-flex min-h-[42px] items-center justify-center gap-2 rounded-xl bg-[#00A6F4] px-4 py-2 text-sm font-bold text-[#09122C] opacity-45">
                    <i data-lucide="table-2" class="h-4 w-4" aria-hidden="true"></i>
                    Susun Dataset
                </button>
            @endif
        @endif
    </x-panel-head>

    <!-- Bar Navigasi Formulir & Pencarian Baris -->
    <div class="wd-bento mb-5 flex flex-wrap items-center justify-between gap-3 p-4">
        <div class="flex min-w-0 flex-1 flex-wrap items-center gap-3">
            <a href="{{ route('panel.preprocessing') }}"
               class="inline-flex min-h-[40px] shrink-0 items-center gap-2 rounded-xl border border-slate-200 bg-[#F8FAFC] px-3.5 py-2 text-xs font-bold text-slate-700 transition hover:border-[#0069A8] hover:bg-[#F0F9FF] hover:text-[#0069A8]">
                <i data-lucide="arrow-left" class="h-3.5 w-3.5" aria-hidden="true"></i>
                Pilih Formulir Lain
            </a>
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="rounded-md bg-[#F0F9FF] px-2 py-0.5 text-[11px] font-bold text-[#0069A8]">{{ $activeForm->code }}</span>
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
        <!-- Status Kesiapan Ringkas -->
        <section class="wd-bento mb-5 overflow-hidden">
            <div class="px-5 py-4 {{ $activeForm->isPreprocessed() ? 'bg-[#ECFDF5]/70' : ($problemCount > 0 ? 'bg-[#FEF2F2]/70' : 'bg-[#FFFBEB]/70') }}">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="min-w-0 flex-1">
                        <span class="inline-flex rounded-md bg-white px-2.5 py-0.5 text-xs font-bold ring-1 ring-slate-200/70 {{ $activeForm->isPreprocessed() ? 'text-[#047857]' : 'text-[#92400E]' }}">
                            {{ $activeForm->preprocessingStatusLabel() }}
                        </span>
                        <h2 class="mt-1.5 text-base font-extrabold tracking-tight text-slate-900">
                            @if($activeForm->isPreprocessed())
                                Siap disusun menjadi dataset ({{ $profile['valid'] }} baris valid)
                            @elseif($totalSubmissionsCount === 0)
                                Belum ada respons masuk
                            @elseif($problemCount > 0)
                                {{ $problemCount }} dari {{ $profile['active_count'] }} baris perlu dibersihkan
                            @else
                                {{ $profile['active_count'] }} baris aktif bersih · klik Finalisasi Pre-processing
                            @endif
                        </h2>
                    </div>

                    <dl class="grid grid-cols-2 gap-2.5 sm:grid-cols-4">
                        <div class="rounded-xl bg-white px-3.5 py-2 text-center ring-1 ring-slate-200/80">
                            <dt class="text-[11px] font-semibold text-[#475569]">Mentah</dt>
                            <dd class="mt-0.5 text-lg font-extrabold tabular-nums text-[#92400E]">{{ $profile['mentah'] }}</dd>
                        </div>
                        <div class="rounded-xl bg-white px-3.5 py-2 text-center ring-1 ring-slate-200/80">
                            <dt class="text-[11px] font-semibold text-[#475569]">Bermasalah</dt>
                            <dd class="mt-0.5 text-lg font-extrabold tabular-nums text-[#B91C1C]">{{ $profile['issue_rows'] }}</dd>
                        </div>
                        <div class="rounded-xl bg-white px-3.5 py-2 text-center ring-1 ring-slate-200/80">
                            <dt class="text-[11px] font-semibold text-[#475569]">Valid</dt>
                            <dd class="mt-0.5 text-lg font-extrabold tabular-nums text-[#047857]">{{ $profile['valid'] }}</dd>
                        </div>
                        <div class="rounded-xl bg-white px-3.5 py-2 text-center ring-1 ring-slate-200/80">
                            <dt class="text-[11px] font-semibold text-[#475569]">Ditolak</dt>
                            <dd class="mt-0.5 text-lg font-extrabold tabular-nums text-slate-700">{{ $profile['ditolak'] }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </section>

        @if($totalSubmissionsCount > 0)
            <!-- Standar Pengolahan Data (5 Tahap Baku Satu Data) -->
            <section class="wd-bento mb-5 p-5">
                <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 pb-4">
                    <div>
                        <p class="text-[11px] font-bold tracking-wider text-[#0069A8] uppercase">Standar Pengolahan Data</p>
                        <h3 class="mt-0.5 text-sm font-extrabold text-slate-900">Alur Baku Pre-processing Sebelum Dataset &amp; Visualisasi</h3>
                        <p class="mt-0.5 text-xs text-[#475569]">
                            Ikuti urutan Tahap 1 → Tahap 5 agar grafik tidak terpecah oleh typo/kapitalisasi, bebas hitung ganda, dan nilai statistik akurat.
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button"
                                @click="standarOpen = !standarOpen"
                                class="inline-flex min-h-[36px] items-center gap-1.5 rounded-xl border border-slate-200 bg-[#F8FAFC] px-3 py-1.5 text-xs font-bold text-slate-700 transition hover:border-[#0069A8] hover:text-[#0069A8]">
                            <i data-lucide="book-open-check" class="h-3.5 w-3.5 text-[#0069A8]" aria-hidden="true"></i>
                            <span x-text="standarOpen ? 'Tutup Panduan Standar' : 'Panduan Standar &amp; Visualisasi'"></span>
                        </button>
                        <form method="POST" action="{{ route('panel.preprocessing.standard', $activeForm->code) }}" class="inline">
                            @csrf
                            <button type="submit"
                                    {{ $profile['active_count'] === 0 ? 'disabled' : '' }}
                                    class="inline-flex min-h-[36px] items-center gap-1.5 rounded-xl bg-[#0069A8] px-3.5 py-1.5 text-xs font-bold text-white transition hover:bg-[#182654] disabled:cursor-not-allowed disabled:opacity-45">
                                <i data-lucide="wand-sparkles" class="h-3.5 w-3.5" aria-hidden="true"></i>
                                Jalankan Standar Tahap 2→3
                            </button>
                        </form>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-2.5 sm:grid-cols-2 lg:grid-cols-5">
                    @foreach($profile['standard_stages'] ?? [] as $stg)
                        <div class="rounded-xl border p-3 text-xs {{ $stg['done'] ? 'border-emerald-200 bg-[#ECFDF5]/60' : 'border-slate-200 bg-[#F8FAFC]' }}">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-extrabold text-[#0069A8]">Tahap {{ $stg['step'] }}</span>
                                <span class="rounded-md px-1.5 py-0.5 text-[10px] font-bold {{ $stg['done'] ? 'bg-[#047857] text-white' : 'bg-[#FFFBEB] text-[#92400E] ring-1 ring-amber-200' }}">
                                    {{ $stg['status_label'] }}
                                </span>
                            </div>
                            <p class="mt-1.5 font-bold text-slate-900">{{ $stg['title'] }}</p>
                            <p class="mt-0.5 text-[11px] leading-relaxed text-[#475569]">{{ $stg['desc'] }}</p>
                        </div>
                    @endforeach
                </div>

                @if(($profile['outlier_cells'] ?? 0) > 0)
                    <div class="mt-3 flex flex-wrap items-center gap-2 rounded-xl border border-amber-200 bg-[#FFFBEB] px-3.5 py-2.5 text-xs text-[#92400E]">
                        <i data-lucide="alert-triangle" class="h-4 w-4 shrink-0" aria-hidden="true"></i>
                        <span>
                            Terdeteksi <strong>{{ $profile['outlier_cells'] }} nilai ekstrem (outlier)</strong> pada kolom numerik. Periksa baris terkait sebelum menghitung rata-rata atau membuat grafik agar skala tidak timpang.
                        </span>
                    </div>
                @endif

                <div x-show="standarOpen" x-cloak x-transition class="mt-4 grid grid-cols-1 gap-3 border-t border-slate-100 pt-4 text-xs md:grid-cols-2">
                    <div class="rounded-xl border border-slate-200/80 bg-[#F8FAFC] p-4 space-y-2">
                        <p class="font-extrabold text-slate-900">Aturan Baku Kualitas Data per Tipe Kolom</p>
                        <ul class="space-y-1.5 text-[#475569]">
                            <li><strong class="text-slate-800">1. Teks &amp; Pilihan:</strong> Spasi ganda dipangkas, label radio/select/checkbox disamakan dengan opsi resmi agar kategori grafik tidak terpecah (mis. <em>"Nasional"</em> vs <em>"nasional "</em>).</li>
                            <li><strong class="text-slate-800">2. Angka &amp; Mata Uang:</strong> Simbol <code>Rp</code>, <code>%</code>, dan pemisah ribuan dibersihkan menjadi numerik murni agar SUM/AVG/grafik batang dapat dihitung.</li>
                            <li><strong class="text-slate-800">3. Tanggal:</strong> Diseragamkan ke format ISO <code>YYYY-MM-DD</code> agar pengurutan grafik garis/tren kronologis akurat.</li>
                            <li><strong class="text-slate-800">4. Jejak Audit:</strong> Nilai asli tetap disimpan sebagai <code>nilai_mentah</code> untuk audit tanpa kehilangan histori respons.</li>
                        </ul>
                    </div>
                    <div class="rounded-xl border border-slate-200/80 bg-[#F8FAFC] p-4 space-y-2">
                        <p class="font-extrabold text-slate-900">Standar Kelayakan Menuju Visualisasi</p>
                        <ul class="space-y-1.5 text-[#475569]">
                            <li><strong class="text-slate-800">1. Bebas Baris Bermasalah:</strong> Wajib <code>0</code> sel wajib kosong, <code>0</code> format invalid, dan <code>0</code> duplikat sebelum Finalisasi.</li>
                            <li><strong class="text-slate-800">2. Gunakan Versi Ringkas untuk Grafik:</strong> Pilih kolom pengelompokan dengan <code>2–12</code> nilai unik (mis. Tingkat, Skala, Status) agar grafik Batang/Donat terbaca jelas.</li>
                            <li><strong class="text-slate-800">3. Gunakan Versi Detail untuk Tabel:</strong> Kolom teks bebas/unik tinggi (mis. Nama Kegiatan) lebih cocok disajikan dalam <em>Versi Detail (Tabel Rinci)</em>.</li>
                            <li><strong class="text-slate-800">4. Tinjau Outlier Numerik:</strong> Jika terdapat nilai ekstrem (metode IQR), pilih agregasi <em>Median/Count</em> atau koreksi salah ketik satuan angka.</li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- Ringkasan Kelengkapan per Kolom -->
            <section class="wd-bento mb-5 p-5">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Kelengkapan &amp; Profil Kolom</h3>
                        <p class="text-xs text-[#475569]">Dari {{ $profile['active_count'] }} baris aktif · rata-rata lengkap {{ $profile['completeness_avg'] ?? 100 }}%</p>
                    </div>
                    <button type="button"
                            @click="profilingExpanded = !profilingExpanded"
                            class="inline-flex min-h-[34px] items-center gap-1.5 rounded-lg border border-slate-200 bg-[#F8FAFC] px-3 text-xs font-semibold text-slate-700 hover:border-[#0069A8] hover:text-[#0069A8]">
                        <span x-text="profilingExpanded ? 'Sembunyikan' : 'Tampilkan'"></span>
                    </button>
                </div>

                <div x-show="profilingExpanded" x-transition class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($profile['columns'] as $colStat)
                        @php
                            $vizTag = $colStat['viz_suitability'] ?? 'detail_only';
                            $vizMeta = match ($vizTag) {
                                'group_ideal' => ['Ideal Grup Grafik', 'bg-[#ECFDF5] text-[#047857]'],
                                'metric_ideal' => ['Siap Metrik Angka', 'bg-[#F0F9FF] text-[#0069A8]'],
                                'needs_fix' => ['Perlu Perbaikan', 'bg-red-50 text-[#B91C1C]'],
                                'low_variance' => ['Variansi Tunggal', 'bg-slate-200 text-slate-700'],
                                'empty' => ['Kosong', 'bg-slate-200 text-slate-700'],
                                default => ['Cocok Versi Detail', 'bg-slate-100 text-slate-700'],
                            };
                            $numStats = $colStat['numeric_stats'] ?? ['count' => 0, 'outlier_count' => 0];
                        @endphp
                        <div class="rounded-xl border border-slate-200/80 bg-[#F8FAFC] p-3.5">
                            <div class="flex items-start justify-between gap-2">
                                <p class="truncate text-xs font-bold text-slate-900" title="{{ $colStat['label'] }}">{{ $colStat['label'] }}</p>
                                <span class="shrink-0 rounded bg-white px-1.5 py-0.5 text-[10px] font-semibold text-[#0069A8] ring-1 ring-slate-200">{{ $colStat['type'] }}</span>
                            </div>
                            <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-slate-200">
                                <div class="h-full rounded-full {{ $colStat['completeness_pct'] >= 99.9 ? 'bg-[#047857]' : 'bg-[#0069A8]' }}" style="width: {{ $colStat['completeness_pct'] }}%"></div>
                            </div>
                            <div class="mt-2 flex flex-wrap items-center justify-between gap-x-3 gap-y-1 text-[11px] tabular-nums text-[#475569]">
                                <span>Lengkap: <strong class="text-slate-900">{{ $colStat['completeness_pct'] }}%</strong></span>
                                <span>Kosong: <strong class="{{ $colStat['missing'] > 0 ? 'text-[#B91C1C]' : 'text-slate-700' }}">{{ $colStat['missing'] }}</strong></span>
                                <span>Invalid: <strong class="{{ $colStat['invalid'] > 0 ? 'text-[#B91C1C]' : 'text-slate-700' }}">{{ $colStat['invalid'] }}</strong></span>
                                <span>Unik: <strong class="text-slate-800">{{ $colStat['unique_count'] ?? 0 }}</strong></span>
                            </div>
                            <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                <span class="rounded px-1.5 py-0.5 text-[10px] font-bold {{ $vizMeta[1] }}">{{ $vizMeta[0] }}</span>
                                @if(($colStat['non_uniform'] ?? 0) > 0)
                                    <span class="rounded bg-[#FFFBEB] px-1.5 py-0.5 text-[10px] font-bold text-[#92400E]">{{ $colStat['non_uniform'] }} belum seragam</span>
                                @endif
                                @if(($numStats['outlier_count'] ?? 0) > 0)
                                    <span class="rounded bg-[#FFFBEB] px-1.5 py-0.5 text-[10px] font-bold text-[#92400E]">{{ $numStats['outlier_count'] }} outlier</span>
                                @endif
                            </div>
                            @if(($numStats['count'] ?? 0) > 0)
                                <p class="mt-1.5 text-[11px] tabular-nums text-[#475569]">
                                    Rentang: <strong class="text-slate-800">{{ $numStats['min'] }}</strong> – <strong class="text-slate-800">{{ $numStats['max'] }}</strong> (median {{ $numStats['median'] }})
                                </p>
                            @endif
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

            <!-- Alat Pembersihan -->
            <section class="mb-5 grid grid-cols-1 gap-4 lg:grid-cols-3">
                <form method="POST" action="{{ route('panel.preprocessing.normalize', $activeForm->code) }}" class="wd-bento flex flex-col p-5">
                    @csrf
                    <p class="text-[11px] font-bold tracking-wider text-[#0069A8] uppercase">Tahap 2</p>
                    <p class="mt-0.5 text-sm font-bold text-slate-900">Normalisasi Format</p>
                    <p class="mt-1 text-xs text-[#475569]">Rapikan spasi, pilihan, tanggal ISO, dan format angka.</p>
                    <div class="mt-3.5 space-y-2.5 text-xs">
                        <label class="flex cursor-pointer items-center gap-2 text-slate-800">
                            <input type="checkbox" name="normalize_numbers" value="1" checked class="h-4 w-4 accent-[#0069A8]" />
                            <span>Seragamkan angka</span>
                        </label>
                        <label class="block">
                            <span class="mb-1 block font-semibold text-slate-700">Huruf kolom teks</span>
                            <select name="text_case" class="h-9 w-full rounded-lg border border-slate-200 bg-[#F8FAFC] px-2.5 text-xs text-slate-800">
                                <option value="none">Pertahankan asli</option>
                                <option value="title">Title Case</option>
                                <option value="upper">HURUF BESAR</option>
                                <option value="lower">huruf kecil</option>
                            </select>
                        </label>
                    </div>
                    <button type="submit" class="mt-auto pt-4">
                        <span class="inline-flex min-h-[40px] w-full items-center justify-center rounded-xl border border-[#0069A8] bg-[#F0F9FF] px-3 py-2 text-xs font-bold text-[#0069A8] transition hover:bg-[#0069A8] hover:text-white">
                            Jalankan Normalisasi
                        </span>
                    </button>
                </form>

                <form method="POST" action="{{ route('panel.preprocessing.deduplicate', $activeForm->code) }}" class="wd-bento flex flex-col p-5">
                    @csrf
                    <div class="flex items-center justify-between gap-2">
                        <div>
                            <p class="text-[11px] font-bold tracking-wider text-[#0069A8] uppercase">Tahap 3</p>
                            <p class="mt-0.5 text-sm font-bold text-slate-900">Deduplikasi</p>
                        </div>
                        @if($profile['duplicate_rows'] > 0)
                            <span class="rounded-full bg-[#FFFBEB] px-2 py-0.5 text-[11px] font-bold tabular-nums text-[#92400E] ring-1 ring-amber-200">{{ $profile['duplicate_rows'] }} ganda</span>
                        @endif
                    </div>
                    <p class="mt-1 text-xs text-[#475569]">Deteksi dan tangani entri ganda.</p>
                    <div class="mt-3.5 space-y-2.5 text-xs">
                        <label class="block">
                            <span class="mb-1 block font-semibold text-slate-700">Kriteria</span>
                            <select name="scope" class="h-9 w-full rounded-lg border border-slate-200 bg-[#F8FAFC] px-2.5 text-xs text-slate-800">
                                <option value="same_values">Isi seluruh kolom sama</option>
                                <option value="same_user_and_values">Pengirim &amp; isi sama</option>
                            </select>
                        </label>
                        <label class="block">
                            <span class="mb-1 block font-semibold text-slate-700">Tindakan</span>
                            <select name="action" class="h-9 w-full rounded-lg border border-slate-200 bg-[#F8FAFC] px-2.5 text-xs text-slate-800">
                                <option value="reject">Tandai ditolak</option>
                                <option value="delete">Hapus permanen</option>
                            </select>
                        </label>
                    </div>
                    <button type="submit" class="mt-auto pt-4">
                        <span class="inline-flex min-h-[40px] w-full items-center justify-center rounded-xl border border-[#0069A8] bg-[#F0F9FF] px-3 py-2 text-xs font-bold text-[#0069A8] transition hover:bg-[#0069A8] hover:text-white">
                            Tangani Duplikat
                        </span>
                    </button>
                </form>

                <form id="langkah-c"
                      method="POST"
                      action="{{ route('panel.preprocessing.impute', $activeForm->code) }}"
                      x-data="{ strat: 'mode' }"
                      :class="imputePulse ? 'ring-2 ring-[#00A6F4] border-[#0069A8]' : ''"
                      class="wd-bento flex flex-col p-5 transition-all">
                    @csrf
                    <p class="text-[11px] font-bold tracking-wider text-[#0069A8] uppercase">Tahap 4</p>
                    <p class="mt-0.5 text-sm font-bold text-slate-900">Missing Value</p>
                    <p class="mt-1 text-xs text-[#475569]">Isi nilai kosong atau tandai ditolak.</p>
                    <div class="mt-3.5 space-y-2 text-xs">
                        <label class="block">
                            <span class="mb-1 block font-semibold text-slate-700">Kolom</span>
                            <select name="field_code" x-model="imputeFieldCode" class="h-9 w-full rounded-lg border border-slate-200 bg-[#F8FAFC] px-2.5 text-xs text-slate-800">
                                @foreach($activeForm->fields ?? [] as $fl)
                                    <option value="{{ $fl['id'] }}">{{ $fl['label'] }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block">
                            <span class="mb-1 block font-semibold text-slate-700">Strategi</span>
                            <select name="strategy" x-model="strat" class="h-9 w-full rounded-lg border border-slate-200 bg-[#F8FAFC] px-2.5 text-xs text-slate-800">
                                <option value="mode">Modus (terbanyak)</option>
                                <option value="mean">Rata-rata (Mean)</option>
                                <option value="median">Median</option>
                                <option value="constant">Nilai tetap</option>
                                <option value="reject_missing">Tandai ditolak</option>
                            </select>
                        </label>
                        <input x-show="strat === 'constant'" type="text" name="custom_value" placeholder="Masukkan nilai tetap..." class="h-9 w-full rounded-lg border border-slate-200 bg-white px-2.5 text-xs text-slate-900" />
                    </div>
                    <button type="submit" class="mt-auto pt-4">
                        <span class="inline-flex min-h-[40px] w-full items-center justify-center rounded-xl border border-[#0069A8] bg-[#F0F9FF] px-3 py-2 text-xs font-bold text-[#0069A8] transition hover:bg-[#0069A8] hover:text-white">
                            Terapkan
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
                    <span class="tabular-nums opacity-85">{{ $sCount }}</span>
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
                                        <p class="font-bold tabular-nums text-slate-900">{{ $sub->code }}</p>
                                        <span class="mt-1 inline-flex rounded px-1.5 py-0.5 text-[10px] font-bold {{ $sub->sumber_input === 'impor' ? 'bg-slate-100 text-slate-700' : 'bg-[#F0F9FF] text-[#0069A8]' }}">
                                            {{ $sub->sumber_input === 'impor' ? 'Impor' : 'Formulir' }}
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
                                                    Ditolak{{ $sub->catatan_validasi ? ': '.$sub->catatan_validasi : '.' }}
                                                </p>
                                            @elseif(! empty($issues))
                                                <ul class="mb-2 list-inside list-disc text-xs font-medium text-[#B91C1C]">
                                                    @foreach($issues as $issue)
                                                        <li>{{ $issue }}</li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                <p class="mb-2 text-xs font-semibold text-[#047857]">Valid</p>
                                            @endif
                                        </div>
                                        <div class="flex flex-wrap gap-1.5">
                                            <a
                                                href="{{ route('panel.preprocessing.submissions.edit', $sub->code) }}"
                                                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 transition hover:border-[#0069A8] hover:text-[#0069A8]"
                                                title="Perbaiki data isian"
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
                <p class="mt-1 text-xs text-[#475569]">Anda dapat mengimpor data respons menggunakan tombol Impor CSV / XLSX.</p>
            </div>
        @endif

        @if(! empty($preprocessingLogs))
            <section class="wd-bento mt-6 p-5">
                <h3 class="text-sm font-bold text-slate-900">Riwayat Pembersihan</h3>
                <ol class="mt-4 space-y-3 border-l-2 border-slate-200 pl-4 text-xs">
                    @foreach(array_slice($preprocessingLogs, 0, 8) as $log)
                        <li class="relative flex flex-wrap items-center justify-between gap-2">
                            <span class="absolute -left-[21px] h-2.5 w-2.5 rounded-full bg-[#0069A8] ring-4 ring-white" aria-hidden="true"></span>
                            <span>
                                <strong class="font-bold text-[#0069A8]">{{ $log['action'] ?? 'Aksi' }}:</strong>
                                <span class="text-slate-700">{{ $log['detail'] ?? '' }}</span>
                            </span>
                            <span class="tabular-nums text-[#475569]">{{ $log['at_label'] ?? '' }}</span>
                        </li>
                    @endforeach
                </ol>
            </section>
        @endif
    @endif

    <!-- Import Spreadsheet Responses Modal -->
    @if($activeForm)
        <template x-teleport="body">
            <div x-show="importOpen" x-cloak class="fixed inset-0 z-[65] grid place-items-end bg-slate-950/55 backdrop-blur-xs sm:place-items-center sm:p-6" @click.self="importOpen = false" @keydown.escape.window="importOpen = false">
                <div role="dialog" aria-modal="true" aria-labelledby="import-modal-title" class="flex max-h-[92vh] w-full max-w-lg flex-col overflow-hidden rounded-t-2xl border border-slate-200 bg-white shadow-2xl sm:rounded-2xl">
                    <div class="flex items-start justify-between gap-4 border-b border-slate-200 bg-[#F8FAFC] px-5 pt-5 pb-4 sm:px-7 sm:pt-6">
                        <div class="min-w-0">
                            <h2 id="import-modal-title" class="text-xl font-extrabold tracking-tight text-slate-900">Impor Respons (Excel / CSV)</h2>
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
                                Unggah berkas <strong>.xlsx</strong> atau <strong>.csv</strong>. Seluruh baris akan masuk ke antrean pre-processing untuk diperiksa terlebih dahulu.
                            </div>

                            <label class="block">
                                <span class="mb-1.5 block text-sm font-semibold text-slate-800">Pilih Berkas (.xlsx / .csv) <span class="text-[#B91C1C]">*</span></span>
                                <input type="file" name="file" accept=".csv,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required class="block w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-slate-700 hover:file:bg-slate-200" />
                            </label>

                            <div>
                                <p class="mb-2 text-xs font-semibold tracking-wide text-[#475569] uppercase">Kolom yang dikenali</p>
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
</div>
@endsection

@push('scripts')
<script>
function collectedDataManager(defaultFieldCode, initialProfilingExpanded) {
    return {
        importOpen: false,
        standarOpen: false,
        profilingExpanded: Boolean(initialProfilingExpanded),
        imputeFieldCode: defaultFieldCode || '',
        imputePulse: false,

        init() {
            window.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    this.importOpen = false;
                }
            });
        },

        targetMissingField(fieldCode) {
            this.imputeFieldCode = fieldCode;
            this.profilingExpanded = true;
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
