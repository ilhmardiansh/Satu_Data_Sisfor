@extends('layouts.panel')

@section('title', 'Pre-processing Data - Panel Satu Data UNUSA')

@section('panel_content')
<div x-data="collectedDataManager()">
    <x-panel-head
        title="Pre-processing Data"
        desc="Pemeriksaan kualitas, normalisasi format, deduplikasi, dan penanganan nilai kosong wajib diselesaikan sebelum data dikemas menjadi dataset."
    >
        @if($activeForm)
            <button
                type="button"
                @click="importOpen = true"
                class="inline-flex min-h-[42px] items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
            >
                <i data-lucide="upload" class="h-4 w-4" aria-hidden="true"></i>
                Impor CSV / XLSX GForm
            </button>
            <a href="{{ route('panel.preprocessing.csv', $activeForm->code) }}" class="inline-flex min-h-[42px] items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                <i data-lucide="download" class="h-4 w-4" aria-hidden="true"></i>
                Unduh CSV
            </a>
            <form method="POST" action="{{ route('panel.preprocessing.finalize', $activeForm->code) }}" class="inline">
                @csrf
                <button
                    type="submit"
                    {{ ($problemCount > 0 || $profile['active_count'] === 0) ? 'disabled' : '' }}
                    class="inline-flex min-h-[42px] items-center justify-center gap-2 rounded-xl bg-[#182654] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#0069A8] disabled:cursor-not-allowed disabled:opacity-45"
                >
                    <i data-lucide="check-check" class="h-4 w-4" aria-hidden="true"></i>
                    Finalisasi Pre-processing
                </button>
            </form>
            <button
                type="button"
                @click="openBuilder({{ $activeForm->id }})"
                {{ (! $activeForm->isPreprocessed() || $profile['valid'] === 0) ? 'disabled' : '' }}
                class="inline-flex min-h-[42px] items-center justify-center gap-2 rounded-xl bg-[#0069A8] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#182654] disabled:cursor-not-allowed disabled:opacity-45"
            >
                <i data-lucide="table-2" class="h-4 w-4" aria-hidden="true"></i>
                Susun Dataset
            </button>
        @endif
    </x-panel-head>

    <!-- Filter Formulir & Pencarian -->
    <form method="GET" action="{{ route('panel.preprocessing') }}" class="mb-5 flex flex-wrap items-center gap-2 rounded-2xl border border-slate-200 bg-[#F6F9FC] p-3">
        <select name="kategori" onchange="this.form.submit()" aria-label="Kategori" class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-800 outline-none focus:border-[#0069A8]">
            <option value="all">Semua kategori</option>
            @foreach($filterCategories as $c)
                <option value="{{ $c->code }}" {{ $kategori === $c->code ? 'selected' : '' }}>{{ $c->name }}</option>
            @endforeach
        </select>

        <select name="tahun" onchange="this.form.submit()" aria-label="Tahun" class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-800 outline-none focus:border-[#0069A8]">
            <option value="all">Semua tahun</option>
            @foreach($years as $y)
                <option value="{{ $y }}" {{ $tahun === (string) $y ? 'selected' : '' }}>{{ $y }}</option>
            @endforeach
        </select>

        <select name="form" onchange="this.form.submit()" aria-label="Formulir" class="h-10 min-w-[200px] flex-1 rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-800 outline-none focus:border-[#0069A8]">
            @forelse($filteredForms as $fx)
                <option value="{{ $fx->code }}" {{ $activeForm?->id === $fx->id ? 'selected' : '' }}>
                    {{ $fx->code }} · {{ $fx->title }} ({{ $fx->submissions_count }} respons)
                </option>
            @empty
                <option value="">Belum ada formulir</option>
            @endforelse
        </select>

        <label class="flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-3">
            <i data-lucide="search" class="h-4 w-4 text-[#475569]" aria-hidden="true"></i>
            <input type="text" name="q" value="{{ $q }}" placeholder="Cari pengisi atau nilai..." class="w-40 bg-transparent text-sm outline-none" />
        </label>

        @if($status !== 'all')
            <input type="hidden" name="status" value="{{ $status }}">
        @endif
    </form>

    @if($activeForm)
        <!-- Banner Status Pipeline & Ringkasan Kuantitatif -->
        <section class="mb-5 rounded-2xl border p-5 {{ $activeForm->isPreprocessed() ? 'border-emerald-200 bg-[#ECFDF5]' : ($problemCount > 0 ? 'border-red-200 bg-[#FEF2F2]' : 'border-amber-200 bg-[#FFFBEB]') }}">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-md bg-white px-2.5 py-0.5 font-mono text-xs font-bold text-[#182654] ring-1 ring-slate-200">{{ $activeForm->code }}</span>
                        <span class="rounded-md px-2.5 py-0.5 text-xs font-bold {{ $activeForm->isPreprocessed() ? 'bg-white text-[#047857]' : 'bg-white text-[#92400E]' }}">
                            {{ $activeForm->preprocessingStatusLabel() }}
                        </span>
                    </div>
                    <h2 class="mt-2 text-lg font-extrabold text-slate-900">
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
                    <p class="mt-1 text-sm text-[#475569]">
                        Alur: <strong class="text-slate-900">Respons Mentah</strong> -> <strong class="text-slate-900">Normalisasi &amp; Deduplikasi &amp; Missing Value</strong> -> <strong class="text-slate-900">Finalisasi Pre-processing</strong> -> <strong class="text-slate-900">Susun Dataset</strong>.
                    </p>
                </div>

                <dl class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div class="rounded-xl bg-white px-3.5 py-2.5 text-center ring-1 ring-slate-200/80">
                        <dt class="text-[11px] font-semibold text-[#475569]">Mentah</dt>
                        <dd class="mt-0.5 text-lg font-extrabold tabular-nums text-[#92400E]">{{ $profile['mentah'] }}</dd>
                    </div>
                    <div class="rounded-xl bg-white px-3.5 py-2.5 text-center ring-1 ring-slate-200/80">
                        <dt class="text-[11px] font-semibold text-[#475569]">Bermasalah</dt>
                        <dd class="mt-0.5 text-lg font-extrabold tabular-nums text-[#B91C1C]">{{ $profile['issue_rows'] }}</dd>
                    </div>
                    <div class="rounded-xl bg-white px-3.5 py-2.5 text-center ring-1 ring-slate-200/80">
                        <dt class="text-[11px] font-semibold text-[#475569]">Valid</dt>
                        <dd class="mt-0.5 text-lg font-extrabold tabular-nums text-[#047857]">{{ $profile['valid'] }}</dd>
                    </div>
                    <div class="rounded-xl bg-white px-3.5 py-2.5 text-center ring-1 ring-slate-200/80">
                        <dt class="text-[11px] font-semibold text-[#475569]">Ditolak</dt>
                        <dd class="mt-0.5 text-lg font-extrabold tabular-nums text-slate-700">{{ $profile['ditolak'] }}</dd>
                    </div>
                </dl>
            </div>
        </section>

        @if($totalSubmissionsCount > 0)
            <!-- Profiling Kualitas per Kolom -->
            <section class="mb-5 rounded-2xl border border-slate-200 bg-white p-5">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <h3 class="text-sm font-bold text-slate-900">Profiling Kualitas per Kolom</h3>
                    <span class="text-xs text-[#475569]">Menghitung {{ $profile['active_count'] }} baris non-ditolak</span>
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($profile['columns'] as $colStat)
                        <div class="rounded-xl border border-slate-200/80 bg-[#F6F9FC] p-3.5">
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
                        </div>
                    @endforeach
                </div>
            </section>

            <!-- Toolbar Aksi Pre-processing (Normalisasi, Deduplikasi, Missing Values) -->
            <section class="mb-5 grid grid-cols-1 gap-4 lg:grid-cols-3">
                <!-- 1. Normalisasi Batch -->
                <form method="POST" action="{{ route('panel.preprocessing.normalize', $activeForm->code) }}" class="flex flex-col rounded-2xl border border-slate-200 bg-white p-4">
                    @csrf
                    <p class="text-xs font-bold tracking-wide text-[#0069A8] uppercase">Langkah A · Normalisasi</p>
                    <p class="mt-1 text-xs text-[#475569]">Merapikan spasi ganda, menyeragamkan label pilihan/checkbox, dan menstandarkan angka Indonesia.</p>
                    <div class="mt-3 space-y-2.5 text-xs">
                        <label class="flex cursor-pointer items-center gap-2 text-slate-800">
                            <input type="checkbox" name="normalize_numbers" value="1" checked class="h-4 w-4 accent-[#0069A8]" />
                            <span>Bersihkan format angka (Rp / titik ribuan / koma)</span>
                        </label>
                        <label class="block">
                            <span class="mb-1 block font-semibold text-slate-700">Kapitalisasi kolom teks</span>
                            <select name="text_case" class="h-9 w-full rounded-lg border border-slate-200 bg-[#F6F9FC] px-2.5 text-xs text-slate-800">
                                <option value="none">Pertahankan huruf asli (rapikan spasi saja)</option>
                                <option value="title">Title Case (Contoh: Nama Kompetisi)</option>
                                <option value="upper">HURUF KAPITAL SEMUA</option>
                                <option value="lower">huruf kecil semua</option>
                            </select>
                        </label>
                    </div>
                    <button type="submit" class="mt-4 inline-flex min-h-[40px] w-full items-center justify-center rounded-xl border border-[#0069A8] bg-[#F0F9FF] px-3 py-2 text-xs font-bold text-[#0069A8] transition hover:bg-[#0069A8] hover:text-white">
                        Jalankan Normalisasi Batch
                    </button>
                </form>

                <!-- 2. Deduplikasi -->
                <form method="POST" action="{{ route('panel.preprocessing.deduplicate', $activeForm->code) }}" class="flex flex-col rounded-2xl border border-slate-200 bg-white p-4">
                    @csrf
                    <p class="text-xs font-bold tracking-wide text-[#0069A8] uppercase">Langkah B · Deduplikasi</p>
                    <p class="mt-1 text-xs text-[#475569]">Mendeteksi entri ganda (terdeteksi saat ini: <strong class="text-slate-900">{{ $profile['duplicate_rows'] }}</strong> baris). Baris terbaru dipertahankan.</p>
                    <div class="mt-3 space-y-2.5 text-xs">
                        <label class="block">
                            <span class="mb-1 block font-semibold text-slate-700">Kriteria kesamaan</span>
                            <select name="scope" class="h-9 w-full rounded-lg border border-slate-200 bg-[#F6F9FC] px-2.5 text-xs text-slate-800">
                                <option value="same_values">Isi seluruh kolom identik</option>
                                <option value="same_user_and_values">Pengirim &amp; isi sama persis</option>
                            </select>
                        </label>
                        <label class="block">
                            <span class="mb-1 block font-semibold text-slate-700">Tindakan untuk duplikat</span>
                            <select name="action" class="h-9 w-full rounded-lg border border-slate-200 bg-[#F6F9FC] px-2.5 text-xs text-slate-800">
                                <option value="reject">Tandai ditolak (pertahankan log audit)</option>
                                <option value="delete">Hapus permanen dari respons</option>
                            </select>
                        </label>
                    </div>
                    <button type="submit" class="mt-4 inline-flex min-h-[40px] w-full items-center justify-center rounded-xl border border-[#0069A8] bg-[#F0F9FF] px-3 py-2 text-xs font-bold text-[#0069A8] transition hover:bg-[#0069A8] hover:text-white">
                        Tangani Baris Duplikat
                    </button>
                </form>

                <!-- 3. Penanganan Missing Values -->
                <form method="POST" action="{{ route('panel.preprocessing.impute', $activeForm->code) }}" x-data="{ strat: 'mode' }" class="flex flex-col rounded-2xl border border-slate-200 bg-white p-4">
                    @csrf
                    <p class="text-xs font-bold tracking-wide text-[#0069A8] uppercase">Langkah C · Missing Value</p>
                    <p class="mt-1 text-xs text-[#475569]">Imputasi nilai kosong per kolom (mean, median, modus, nilai tetap) atau tandai ditolak.</p>
                    <div class="mt-3 space-y-2 text-xs">
                        <label class="block">
                            <span class="mb-1 block font-semibold text-slate-700">Kolom target</span>
                            <select name="field_code" class="h-9 w-full rounded-lg border border-slate-200 bg-[#F6F9FC] px-2.5 text-xs text-slate-800">
                                @foreach($activeForm->fields ?? [] as $fl)
                                    <option value="{{ $fl['id'] }}">{{ $fl['label'] }} ({{ $fl['type'] }})</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block">
                            <span class="mb-1 block font-semibold text-slate-700">Strategi</span>
                            <select name="strategy" x-model="strat" class="h-9 w-full rounded-lg border border-slate-200 bg-[#F6F9FC] px-2.5 text-xs text-slate-800">
                                <option value="mode">Isi dengan Modus (nilai terbanyak)</option>
                                <option value="mean">Isi dengan Rata-rata (Mean angka)</option>
                                <option value="median">Isi dengan Median (angka)</option>
                                <option value="constant">Isi dengan Nilai Tetap</option>
                                <option value="reject_missing">Tandai baris kosong sebagai Ditolak</option>
                            </select>
                        </label>
                        <input x-show="strat === 'constant'" type="text" name="custom_value" placeholder="Masukkan nilai tetap..." class="h-9 w-full rounded-lg border border-slate-200 bg-white px-2.5 text-xs text-slate-900" />
                    </div>
                    <button type="submit" class="mt-4 inline-flex min-h-[40px] w-full items-center justify-center rounded-xl border border-[#0069A8] bg-[#F0F9FF] px-3 py-2 text-xs font-bold text-[#0069A8] transition hover:bg-[#0069A8] hover:text-white">
                        Terapkan Strategi
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
                <a href="{{ route('panel.preprocessing', array_filter(['kategori' => $kategori, 'tahun' => $tahun, 'form' => $activeForm->code, 'q' => $q, 'status' => $sKey])) }}"
                   class="inline-flex min-h-[38px] items-center gap-1.5 rounded-xl border px-3.5 py-1.5 text-xs font-bold transition {{ $status === $sKey ? 'border-[#0069A8] bg-[#0069A8] text-white' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}">
                    <span>{{ $sLabel }}</span>
                    <span class="font-mono opacity-85">{{ $sCount }}</span>
                </a>
            @endforeach
        </div>

        @if($submissionsPaginator->total() > 0)
            @php $cols = $activeForm->sourceCols(); @endphp
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-surface">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-surface-strong">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-[#475569] uppercase">Baris &amp; Status</th>
                                @foreach($cols as $col)
                                    <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-[#475569] uppercase">{{ $col['label'] }}</th>
                                @endforeach
                                <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-[#475569] uppercase">Kualitas &amp; Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($submissionsPaginator as $sub)
                                @php
                                    $issues = $qualityIssues[$sub->id] ?? [];
                                    $rawMap = $sub->rawValues ?? [];
                                    $fieldValues = $sub->values ?? [];
                                    $cleanPayload = [
                                        'code' => $sub->code,
                                        'action' => route('panel.data.submissions.update', $sub->code),
                                        'formTitle' => $activeForm->title,
                                        'fields' => $activeForm->fields ?? [],
                                        'values' => $fieldValues,
                                        'rawValues' => $rawMap,
                                    ];
                                @endphp
                                <tr class="border-t border-slate-100 {{ $sub->status_validasi === 'ditolak' ? 'bg-slate-50/80 opacity-80' : (! empty($issues) ? 'bg-[#FEF2F2]' : 'hover:bg-white') }}">
                                    <td class="px-4 py-3 align-top text-xs whitespace-nowrap">
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
                                        @endphp
                                        <td class="max-w-[220px] px-4 py-3 align-top text-sm text-slate-800" title="{{ $val }}">
                                            <p class="truncate">{{ $val !== '' ? $val : '-' }}</p>
                                            @if($rawVal !== $val)
                                                <p class="truncate text-[11px] text-[#475569]">Mentah: {{ $rawVal !== '' ? $rawVal : '(kosong)' }}</p>
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
                                            <button
                                                type="button"
                                                @click="openClean(@js($cleanPayload))"
                                                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 transition hover:border-[#0069A8] hover:text-[#0069A8]"
                                                title="Perbaiki sel"
                                                aria-label="Perbaiki isian"
                                            >
                                                <i data-lucide="pencil" class="h-4 w-4"></i>
                                            </button>
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
                                                <i data-lucide="trash-2" class="h-4 w-4"></i>
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
            <div class="rounded-2xl border border-dashed border-slate-300 bg-surface px-6 py-14 text-center">
                <span class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-slate-100 text-slate-500">
                    <i data-lucide="sparkles" class="h-6 w-6" aria-hidden="true"></i>
                </span>
                <p class="mt-4 font-bold text-slate-800">
                    {{ $status !== 'all' ? 'Tidak ada baris pada filter status ini' : ($q !== '' ? 'Isian tidak ditemukan' : 'Belum ada respons untuk formulir ini') }}
                </p>
                <p class="mt-1 text-xs text-[#475569]">Kumpulkan respons melalui halaman GForm atau klik "Impor CSV / XLSX GForm" di pojok kanan atas.</p>
            </div>
        @endif

        @if(! empty($preprocessingLogs))
            <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-5">
                <h3 class="text-sm font-bold text-slate-900">Jejak Audit Pre-processing</h3>
                <ul class="mt-3 divide-y divide-slate-100 text-xs">
                    @foreach(array_slice($preprocessingLogs, 0, 8) as $log)
                        <li class="flex flex-wrap items-center justify-between gap-2 py-2">
                            <span>
                                <strong class="font-bold text-[#0069A8]">{{ $log['action'] ?? 'Aksi' }}:</strong>
                                <span class="text-slate-700">{{ $log['detail'] ?? '' }}</span>
                            </span>
                            <span class="font-mono text-[#475569]">{{ $log['at_label'] ?? '' }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    @else
        <div class="rounded-2xl border border-dashed border-slate-300 bg-surface px-6 py-14 text-center">
            <span class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-slate-100 text-slate-500">
                <i data-lucide="database" class="h-6 w-6" aria-hidden="true"></i>
            </span>
            <p class="mt-4 font-bold text-slate-800">Belum ada formulir pada filter ini</p>
            <p class="mt-1 text-sm text-[#475569]">Buat instrumen formulir terlebih dahulu agar respons pengumpulan data dapat diproses.</p>
            <a href="{{ route('panel.formulir') }}" class="mt-4 inline-flex min-h-[42px] items-center rounded-xl bg-[#0069A8] px-4 py-2 text-sm font-semibold text-white hover:bg-[#182654]">
                Buka Instrumen Formulir
            </a>
        </div>
    @endif

    <!-- Clean Submission Modal -->
    <template x-if="cleanOpen && cleanTarget">
        <div class="fixed inset-0 z-50 grid place-items-end bg-slate-900/50 sm:place-items-center sm:p-6" @click.self="cleanOpen = false" @keydown.escape.window="cleanOpen = false">
            <div role="dialog" aria-modal="true" aria-labelledby="clean-modal-title" class="flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-t-2xl border border-slate-200 bg-white shadow-2xl sm:rounded-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 pt-5 pb-4 sm:px-8 sm:pt-6">
                    <div class="min-w-0">
                        <h2 id="clean-modal-title" class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">Perbaiki data isian (<span x-text="cleanTarget.code"></span>)</h2>
                        <p class="mt-1 text-sm text-[#475569]" x-text="cleanTarget.formTitle"></p>
                    </div>
                    <button type="button" @click="cleanOpen = false" aria-label="Tutup" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 hover:border-[#0069A8] hover:text-[#0069A8]">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>

                <form :action="cleanTarget.action" method="POST" @submit="submitClean($event)" novalidate class="flex min-h-0 flex-col">
                    @csrf
                    <input type="hidden" name="_method" value="PUT">

                    <div class="space-y-4 overflow-y-auto px-5 py-6 sm:px-8">
                        <div class="flex gap-3 rounded-xl bg-[#F0F9FF] px-4 py-3 text-xs font-medium text-[#0069A8]">
                            Perbaikan hanya mengubah nilai bersih (`nilai`), sedangkan jejak nilai asli sebelum dibersihkan tetap tersimpan di `nilai_mentah`.
                        </div>

                        <button type="button" @click="normalizeCleanValues()" class="inline-flex min-h-[40px] items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                            Seragamkan spasi, angka &amp; pilihan
                        </button>

                        <template x-for="f in cleanTarget.fields" :key="f.id">
                            <div class="block">
                                <span class="mb-1.5 block text-sm font-semibold text-slate-800">
                                    <span x-text="f.label"></span>
                                    <template x-if="f.required">
                                        <span class="ml-0.5 text-[#B91C1C]">*</span>
                                    </template>
                                </span>

                                <template x-if="f.type === 'select' || f.type === 'radio'">
                                    <select :name="`values[${f.id}]`" x-model="cleanValues[f.id]" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-[#0069A8] focus:ring-4 focus:ring-sky-100">
                                        <option value="">Pilih...</option>
                                        <template x-if="cleanValues[f.id] && !(f.options || []).includes(cleanValues[f.id])">
                                            <option :value="cleanValues[f.id]" x-text="`${cleanValues[f.id]} (tidak valid)`"></option>
                                        </template>
                                        <template x-for="o in f.options || []" :key="o">
                                            <option :value="o" x-text="o"></option>
                                        </template>
                                    </select>
                                </template>

                                <template x-if="f.type === 'checkbox'">
                                    <div class="space-y-1">
                                        <input type="text" :name="`values[${f.id}]`" x-model="cleanValues[f.id]" placeholder="Pisahkan opsi dengan koma" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-[#0069A8]" />
                                        <p class="text-[11px] text-[#475569]" x-text="'Opsi valid: ' + (f.options || []).join(', ')"></p>
                                    </div>
                                </template>

                                <template x-if="f.type === 'textarea'">
                                    <textarea rows="3" :name="`values[${f.id}]`" x-model="cleanValues[f.id]" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-[#0069A8] focus:ring-4 focus:ring-sky-100"></textarea>
                                </template>

                                <template x-if="!['select', 'radio', 'checkbox', 'textarea'].includes(f.type)">
                                    <input :type="f.type === 'file' ? 'text' : f.type" :name="`values[${f.id}]`" x-model="cleanValues[f.id]" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-[#0069A8] focus:ring-4 focus:ring-sky-100" />
                                </template>

                                <span x-show="cleanErrors[f.id]" class="mt-1 block text-xs font-semibold text-[#B91C1C]" x-text="cleanErrors[f.id]"></span>
                            </div>
                        </template>
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:px-8">
                        <button type="button" @click="cleanOpen = false" class="inline-flex min-h-[42px] items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Batal
                        </button>
                        <button type="submit" class="inline-flex min-h-[42px] items-center justify-center gap-2 rounded-xl bg-[#0069A8] px-5 py-2 text-sm font-semibold text-white hover:bg-[#182654]">
                            Simpan perbaikan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- Import Spreadsheet Responses Modal -->
    @if($activeForm)
        <template x-if="importOpen">
            <div class="fixed inset-0 z-50 grid place-items-end bg-slate-900/50 sm:place-items-center sm:p-6" @click.self="importOpen = false" @keydown.escape.window="importOpen = false">
                <div role="dialog" aria-modal="true" aria-labelledby="import-modal-title" class="flex max-h-[92vh] w-full max-w-lg flex-col overflow-hidden rounded-t-2xl border border-slate-200 bg-white shadow-2xl sm:rounded-2xl">
                    <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 pt-5 pb-4 sm:px-7 sm:pt-6">
                        <div class="min-w-0">
                            <h2 id="import-modal-title" class="text-xl font-extrabold tracking-tight text-slate-900">Impor Respons GForm (Excel / CSV)</h2>
                            <p class="mt-1 text-sm text-[#475569]">{{ $activeForm->title }}</p>
                        </div>
                        <button type="button" @click="importOpen = false" aria-label="Tutup" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 hover:border-[#0069A8] hover:text-[#0069A8]">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
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
function collectedDataManager() {
    return {
        ...datasetBuilderMixin(@json($builderFormsPayload)),
        importOpen: false,
        cleanOpen: false,
        cleanTarget: null,
        cleanValues: {},
        cleanErrors: {},

        init() {
            const reopenCode = @json(session('clean_submission_code'));
            if (reopenCode) {
                const errs = @json($errors->toArray());
                Object.keys(errs).forEach((k) => {
                    this.cleanErrors[k] = errs[k][0];
                });
            }
            window.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    this.importOpen = false;
                    this.cleanOpen = false;
                    this.builderOpen = false;
                }
            });
        },

        openClean(payload) {
            this.cleanTarget = payload;
            this.cleanValues = { ...(payload.values || {}) };
            this.cleanErrors = {};
            this.cleanOpen = true;
        },

        normalizeCleanValues() {
            if (!this.cleanTarget || !window.SatuDataQuality) return;
            const updated = {};
            this.cleanTarget.fields.forEach((f) => {
                updated[f.id] = window.SatuDataQuality.normalizeValue(f, this.cleanValues[f.id] ?? '', { normalizeNumbers: true });
            });
            this.cleanValues = updated;
        },

        submitClean(ev) {
            if (!this.cleanTarget) return;
            this.cleanErrors = {};
            this.cleanTarget.fields.forEach((f) => {
                const val = String(this.cleanValues[f.id] ?? '').trim();
                if (!val && f.required) {
                    this.cleanErrors[f.id] = 'Data wajib masih kosong.';
                } else if (val && f.type === 'number' && !Number.isFinite(Number(val))) {
                    this.cleanErrors[f.id] = 'Nilai harus berupa angka yang valid.';
                } else if (val && (f.type === 'select' || f.type === 'radio') && !(f.options || []).includes(val)) {
                    this.cleanErrors[f.id] = 'Nilai tidak sesuai pilihan formulir.';
                } else if (val && f.type === 'checkbox') {
                    const opts = f.options || [];
                    const parts = val.split(',').map((x) => x.trim()).filter(Boolean);
                    if (parts.some((p) => !opts.includes(p))) {
                        this.cleanErrors[f.id] = 'Terdapat pilihan kotak centang yang tidak sesuai.';
                    }
                } else if (val && f.type === 'file' && !/\.(pdf|jpe?g|png)$/i.test(val)) {
                    this.cleanErrors[f.id] = 'Format bukti harus PDF, JPG, atau PNG.';
                }
            });
            if (Object.keys(this.cleanErrors).length > 0) {
                ev.preventDefault();
            }
        },
    };
}
</script>
@endpush
