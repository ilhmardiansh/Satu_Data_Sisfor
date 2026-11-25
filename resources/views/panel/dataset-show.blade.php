@extends('layouts.panel')

@section('title', $dataset->title.' - Kelola Dataset - Satu Data UNUSA')

@section('panel_content')
@php
    $isPublished = $dataset->isPublished();
    $isPublic = $dataset->access === 'public';
    $numericIndices = array_keys($numericColumns);
    $firstNumericIdx = $numericIndices[0] ?? null;
    $labelOptions = [];
    foreach ($cols as $idx => $colName) {
        if (! array_key_exists($idx, $numericColumns)) {
            $labelOptions[] = ['idx' => $idx, 'name' => (string) $colName];
        }
    }
    if (empty($labelOptions) && ! empty($cols)) {
        $labelOptions[] = ['idx' => 0, 'name' => (string) $cols[0]];
    }
    $sourceMode = (string) ($sourceConfig['mode'] ?? '');
    $showPayload = [
        'cols' => $cols,
        'rows' => $rows,
        'numericColumns' => $numericColumns,
        'labelOptions' => $labelOptions,
        'firstNumericIdx' => $firstNumericIdx,
        'initialPublished' => $isPublished,
        'initialPublic' => $isPublic,
    ];
@endphp

<script type="application/json" id="dataset-show-payload">{!! json_encode($showPayload, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) !!}</script>

<div x-data="datasetShowManager()">
    <x-panel-head
        scene="dataset"
        badge="{{ $dataset->code }}"
        badgeTone="emerald"
        title="{{ $dataset->title }}"
        desc="{{ $dataset->description ?: 'Dataset resmi Program Studi Sistem Informasi UNUSA.' }}"
    >
        <a href="{{ route('panel.dataset.edit', $dataset->code) }}"
           class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl bg-[#00A6F4] px-4 py-2.5 text-sm font-bold text-[#09122C] transition hover:bg-[#38BDF8]">
            <i data-lucide="pencil" class="h-4 w-4" aria-hidden="true"></i>
            Ubah Dataset
        </a>
        <a href="{{ route('panel.dataset') }}"
           class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl border border-white/25 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-white/20">
            <i data-lucide="arrow-left" class="h-4 w-4" aria-hidden="true"></i>
            Kembali
        </a>
    </x-panel-head>

    <!-- Ringkasan Metrik Dataset -->
    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="wd-bento p-4 sm:p-5">
            <div class="flex items-center justify-between gap-2">
                <span class="text-xs font-semibold text-[#475569]">Dimensi Tabel</span>
                <span class="grid h-8 w-8 place-items-center rounded-lg bg-[#F0F9FF] text-[#0069A8]">
                    <i data-lucide="table-2" class="h-4 w-4" aria-hidden="true"></i>
                </span>
            </div>
            <p class="mt-2.5 text-2xl font-extrabold tracking-tight tabular-nums text-slate-900">{{ count($rows) }}</p>
            <p class="mt-0.5 text-xs tabular-nums text-[#475569]">baris · {{ count($cols) }} kolom</p>
        </div>

        <div class="wd-bento p-4 sm:p-5">
            <div class="flex items-center justify-between gap-2">
                <span class="text-xs font-semibold text-[#475569]">Periode &amp; Format</span>
                <span class="grid h-8 w-8 place-items-center rounded-lg bg-[#EFF6FF] text-[#182654]">
                    <i data-lucide="calendar" class="h-4 w-4" aria-hidden="true"></i>
                </span>
            </div>
            <p class="mt-2.5 text-2xl font-extrabold tracking-tight tabular-nums text-slate-900">{{ $dataset->tahun }}</p>
            <p class="mt-0.5 text-xs text-[#475569]">Semester {{ $dataset->periode }} · {{ $dataset->format }}</p>
        </div>

        <div class="wd-bento p-4 sm:p-5">
            <div class="flex items-center justify-between gap-2">
                <span class="text-xs font-semibold text-[#475569]">Visibilitas</span>
                <span class="grid h-8 w-8 place-items-center rounded-lg bg-[#ECFDF5] text-[#047857]">
                    <i data-lucide="shield-check" class="h-4 w-4" aria-hidden="true"></i>
                </span>
            </div>
            <div class="mt-3 flex flex-wrap items-center gap-1.5">
                <span x-show="published" class="inline-flex rounded-md bg-[#F0F9FF] px-2.5 py-1 text-xs font-bold text-[#0069A8]">Terpublikasi</span>
                <span x-show="!published" x-cloak class="inline-flex rounded-md bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700">Draft</span>
                <span x-show="isPublic" class="inline-flex rounded-md bg-[#ECFDF5] px-2.5 py-1 text-xs font-bold text-[#047857]">Publik</span>
                <span x-show="!isPublic" x-cloak class="inline-flex rounded-md bg-[#FFFBEB] px-2.5 py-1 text-xs font-bold text-[#92400E]">Private</span>
            </div>
            <p class="mt-1.5 text-xs text-[#475569]">{{ $dataset->category?->name ?? 'Tanpa kategori' }}</p>
        </div>

        <div class="wd-bento p-4 sm:p-5">
            <div class="flex items-center justify-between gap-2">
                <span class="text-xs font-semibold text-[#475569]">Diperbarui</span>
                <span class="grid h-8 w-8 place-items-center rounded-lg bg-slate-100 text-slate-700">
                    <i data-lucide="clock" class="h-4 w-4" aria-hidden="true"></i>
                </span>
            </div>
            <p class="mt-2.5 truncate text-lg font-extrabold tracking-tight text-slate-900">
                {{ $dataset->updated_label ?? ($dataset->updated_at?->locale('id')->translatedFormat('j M Y') ?? '-') }}
            </p>
            <p class="mt-1 truncate text-xs text-[#475569]">
                {{ $dataset->sourceForm ? 'Dari '.$dataset->sourceForm->code : 'Dataset mandiri' }}
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
        <!-- Kolom Kiri: Grafik Otomatis & Tabel Data Lengkap -->
        <div class="min-w-0 space-y-5">
            @if(! empty($rows) && ! empty($numericColumns))
                <section class="wd-bento p-5 sm:p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-4">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Pratinjau Grafik Otomatis</h2>
                            <p class="mt-0.5 text-xs text-[#475569]">Visualisasi cepat dari kolom angka pada dataset ini.</p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            @if(count($labelOptions) > 1)
                                <select x-model.number="labelIdx" aria-label="Kolom label" class="h-9 rounded-xl border border-slate-200 bg-[#F8FAFC] px-2.5 text-xs font-semibold text-slate-700 outline-none focus:border-[#0069A8]">
                                    <template x-for="opt in labelOptions" :key="opt.idx">
                                        <option :value="opt.idx" x-text="opt.name"></option>
                                    </template>
                                </select>
                            @endif
                            @if(count($numericColumns) > 1)
                                <select x-model.number="valueIdx" aria-label="Kolom nilai" class="h-9 rounded-xl border border-slate-200 bg-[#F8FAFC] px-2.5 text-xs font-semibold text-slate-700 outline-none focus:border-[#0069A8]">
                                    @foreach($numericColumns as $nIdx => $nName)
                                        <option value="{{ $nIdx }}">{{ $nName }}</option>
                                    @endforeach
                                </select>
                            @endif

                            <div class="inline-flex rounded-xl border border-slate-200 bg-[#F8FAFC] p-0.5 text-xs font-bold">
                                <button type="button" @click="chartKind = 'bar'" :class="chartKind === 'bar' ? 'bg-[#0069A8] text-white' : 'text-slate-600 hover:text-slate-900'" class="rounded-lg px-2.5 py-1.5 transition">Batang</button>
                                <button type="button" @click="chartKind = 'hbar'" :class="chartKind === 'hbar' ? 'bg-[#0069A8] text-white' : 'text-slate-600 hover:text-slate-900'" class="rounded-lg px-2.5 py-1.5 transition">Horizontal</button>
                                <button type="button" @click="chartKind = 'donut'" :class="chartKind === 'donut' ? 'bg-[#0069A8] text-white' : 'text-slate-600 hover:text-slate-900'" class="rounded-lg px-2.5 py-1.5 transition">Donat</button>
                                <button type="button" @click="chartKind = 'line'" :class="chartKind === 'line' ? 'bg-[#0069A8] text-white' : 'text-slate-600 hover:text-slate-900'" class="rounded-lg px-2.5 py-1.5 transition">Garis</button>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 h-64 rounded-2xl border border-slate-100 bg-[#F8FAFC] p-3" x-html="chartSvg()"></div>
                </section>
            @endif

            <section class="wd-bento overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200/80 bg-[#F8FAFC] px-5 py-4">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Isi Tabel Dataset</h2>
                        <p class="mt-0.5 text-xs tabular-nums text-[#475569]"
                           x-text="`${filteredRows().length} baris cocok dari total ${rows.length} baris`"></p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <label class="flex h-9 items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 focus-within:border-[#0069A8]">
                            <i data-lucide="search" class="h-3.5 w-3.5 text-[#475569]" aria-hidden="true"></i>
                            <input type="text"
                                   x-model="tableQuery"
                                   @input="page = 1"
                                   placeholder="Cari isi tabel..."
                                   class="w-40 bg-transparent text-xs text-slate-900 outline-none placeholder:text-slate-400 sm:w-52" />
                        </label>
                        <select x-model="perPage" @change="page = 1" aria-label="Jumlah baris per halaman" class="h-9 rounded-xl border border-slate-200 bg-white px-2.5 text-xs font-semibold text-slate-700 outline-none focus:border-[#0069A8]">
                            <option value="15">15 / hal</option>
                            <option value="30">30 / hal</option>
                            <option value="50">50 / hal</option>
                            <option value="all">Semua</option>
                        </select>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="wd-hairline-table">
                        <thead>
                            <tr>
                                <th class="w-12 px-4 py-3 text-left text-[11px] font-bold tracking-wider whitespace-nowrap text-[#475569] uppercase">#</th>
                                @foreach($cols as $col)
                                    <th class="px-4 py-3 text-left text-[11px] font-bold tracking-wider whitespace-nowrap text-[#475569] uppercase">{{ $col }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(item, idx) in pagedRows()" :key="item.origIdx">
                                <tr class="transition-colors hover:bg-[#F0F9FF]/50">
                                    <td class="px-4 py-3 text-xs font-semibold tabular-nums text-slate-400" x-text="item.origIdx + 1"></td>
                                    <template x-for="(cell, cIdx) in item.cells" :key="cIdx">
                                        <td class="px-4 py-3 text-sm tabular-nums text-slate-700" x-text="cell"></td>
                                    </template>
                                </tr>
                            </template>
                            <template x-if="pagedRows().length === 0">
                                <tr>
                                    <td colspan="{{ max(1, count($cols) + 1) }}" class="px-6 py-12 text-center text-sm text-[#475569]">
                                        Tidak ada baris data yang cocok dengan pencarian.
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <template x-if="totalPages() > 1">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200/80 bg-[#F8FAFC] px-5 py-3 text-xs">
                        <p class="font-medium tabular-nums text-[#475569]"
                           x-text="`Halaman ${page} dari ${totalPages()}`"></p>
                        <div class="flex items-center gap-1.5">
                            <button type="button"
                                    @click="if (page > 1) page--"
                                    :disabled="page <= 1"
                                    class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 font-semibold text-slate-700 hover:border-[#0069A8] hover:text-[#0069A8] disabled:cursor-not-allowed disabled:opacity-45">
                                Sebelumnya
                            </button>
                            <button type="button"
                                    @click="if (page < totalPages()) page++"
                                    :disabled="page >= totalPages()"
                                    class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 font-semibold text-slate-700 hover:border-[#0069A8] hover:text-[#0069A8] disabled:cursor-not-allowed disabled:opacity-45">
                                Berikutnya
                            </button>
                        </div>
                    </div>
                </template>
            </section>
        </div>

        <!-- Kolom Kanan: Kontrol Cepat, Ekspor & Informasi Sumber -->
        <aside class="space-y-4 lg:sticky lg:top-24">
            <section class="wd-bento space-y-3 p-5">
                <h2 class="text-sm font-extrabold text-slate-900">Pengaturan Cepat &amp; Ekspor</h2>

                <div class="space-y-2.5 rounded-xl border border-slate-200/80 bg-[#F8FAFC] p-3.5 text-xs">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="font-bold text-slate-900">Publikasi Portal</p>
                            <p class="text-[11px] text-[#475569]" x-text="published ? 'Tayang di katalog' : 'Disimpan sebagai draft'"></p>
                        </div>
                        <button type="button"
                                role="switch"
                                :aria-checked="published"
                                :disabled="busyPublish"
                                @click="togglePublish(@js(route('panel.dataset.publish', $dataset->code)))"
                                :class="published ? 'bg-[#0069A8]' : 'bg-slate-300'"
                                class="relative h-6 w-11 shrink-0 rounded-full transition disabled:opacity-60">
                            <span :class="published ? 'left-[22px]' : 'left-0.5'" class="absolute top-0.5 h-5 w-5 rounded-full bg-white shadow transition"></span>
                        </button>
                    </div>

                    <div class="border-t border-slate-200/80 pt-2.5 flex items-center justify-between gap-3">
                        <div>
                            <p class="font-bold text-slate-900">Akses Publik</p>
                            <p class="text-[11px] text-[#475569]" x-text="isPublic ? 'Terbuka langsung' : 'Perlu persetujuan'"></p>
                        </div>
                        <button type="button"
                                role="switch"
                                :aria-checked="isPublic"
                                :disabled="busyAccess"
                                @click="toggleAccess(@js(route('panel.dataset.access', $dataset->code)))"
                                :class="isPublic ? 'bg-[#047857]' : 'bg-slate-300'"
                                class="relative h-6 w-11 shrink-0 rounded-full transition disabled:opacity-60">
                            <span :class="isPublic ? 'left-[22px]' : 'left-0.5'" class="absolute top-0.5 h-5 w-5 rounded-full bg-white shadow transition"></span>
                        </button>
                    </div>
                </div>

                <a href="{{ route('panel.dataset.edit', $dataset->code) }}"
                   class="inline-flex min-h-[42px] w-full items-center justify-center gap-2 rounded-xl bg-[#0069A8] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#005187]">
                    <i data-lucide="pencil" class="h-4 w-4" aria-hidden="true"></i>
                    Ubah Metadata &amp; Susunan
                </a>

                <div class="grid grid-cols-2 gap-2">
                    <a href="{{ route('dataset.download', [$dataset->code, 'xlsx']) }}"
                       class="inline-flex min-h-[40px] items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 transition hover:border-[#0069A8] hover:text-[#0069A8]">
                        <i data-lucide="file-spreadsheet" class="h-3.5 w-3.5" aria-hidden="true"></i>
                        Unduh XLSX
                    </a>
                    <a href="{{ route('dataset.download', [$dataset->code, 'csv']) }}"
                       class="inline-flex min-h-[40px] items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 transition hover:border-[#0069A8] hover:text-[#0069A8]">
                        <i data-lucide="download" class="h-3.5 w-3.5" aria-hidden="true"></i>
                        Unduh CSV
                    </a>
                </div>

                @if($dataset->sourceForm)
                    <form method="POST" action="{{ route('panel.dataset.sync', $dataset->code) }}">
                        @csrf
                        <input type="hidden" name="redirect_to" value="show">
                        <button type="submit"
                                class="inline-flex min-h-[40px] w-full items-center justify-center gap-2 rounded-xl border border-[#BAE6FD] bg-[#F0F9FF] px-4 py-2 text-xs font-bold text-[#0069A8] transition hover:bg-sky-100">
                            <i data-lucide="refresh-cw" class="h-3.5 w-3.5" aria-hidden="true"></i>
                            Sinkronkan Ulang dari Formulir
                        </button>
                    </form>
                @endif
            </section>

            <section class="wd-bento space-y-3 p-5">
                <h2 class="text-sm font-extrabold text-slate-900">Informasi &amp; Sumber</h2>
                <dl class="space-y-2.5 text-xs">
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-[#475569]">Kategori</dt>
                        <dd class="text-right font-semibold text-slate-900">{{ $dataset->category?->name ?? '-' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-[#475569]">Penyusun</dt>
                        <dd class="text-right font-semibold text-slate-900">{{ $dataset->creator?->name ?? '-' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-[#475569]">Sumber Formulir</dt>
                        <dd class="text-right font-semibold text-slate-900">
                            @if($dataset->sourceForm)
                                <span class="text-[#0069A8]">{{ $dataset->sourceForm->code }}</span>
                                <span class="block text-[11px] font-normal text-[#475569]">{{ $dataset->sourceForm->title }}</span>
                            @else
                                Mandiri / Impor
                            @endif
                        </dd>
                    </div>
                    @if($sourceMode !== '')
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-[#475569]">Versi Dataset</dt>
                            <dd class="text-right font-semibold text-slate-900">
                                {{ $sourceMode === 'rekap' ? 'Versi Ringkas (Rekap Agregat)' : 'Versi Detail (Tabel Rinci)' }}
                            </dd>
                        </div>
                    @endif
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-[#475569]">Permohonan Akses</dt>
                        <dd class="text-right font-semibold tabular-nums text-slate-900">
                            {{ $pendingRequestsCount }} menunggu · {{ $approvedRequestsCount }} disetujui
                        </dd>
                    </div>
                </dl>

                @if($dataset->sourceForm)
                    <div class="border-t border-slate-100 pt-3 flex flex-wrap gap-2">
                        @if($dataset->sourceForm->isClosed())
                            <a href="{{ route('panel.preprocessing.show', $dataset->sourceForm->code) }}"
                               class="inline-flex items-center gap-1 text-xs font-bold text-[#0069A8] hover:underline">
                                <i data-lucide="sparkles" class="h-3.5 w-3.5" aria-hidden="true"></i>
                                Buka Pre-processing Sumber
                            </a>
                        @else
                            <a href="{{ route('panel.formulir.edit', $dataset->sourceForm->code) }}"
                               class="inline-flex items-center gap-1 text-xs font-bold text-[#0069A8] hover:underline">
                                <i data-lucide="clipboard-list" class="h-3.5 w-3.5" aria-hidden="true"></i>
                                Buka Instrumen Sumber
                            </a>
                        @endif
                    </div>
                @endif
            </section>

            <section class="wd-bento space-y-3 p-5">
                <div class="flex items-center justify-between gap-2">
                    <h2 class="text-sm font-extrabold text-slate-900">Visualisasi Terkait</h2>
                    <a href="{{ route('panel.visualisasi') }}" class="text-xs font-bold text-[#0069A8] hover:underline">Kelola</a>
                </div>

                @if($dataset->visualizations->isEmpty())
                    <p class="text-xs text-[#475569]">Belum ada grafik yang dibuat dari dataset ini.</p>
                @else
                    <div class="space-y-2">
                        @foreach($dataset->visualizations as $viz)
                            <div class="flex items-center justify-between gap-2 rounded-xl border border-slate-200/80 bg-[#F8FAFC] px-3 py-2 text-xs">
                                <div class="min-w-0">
                                    <p class="truncate font-bold text-slate-900">{{ $viz->title }}</p>
                                    <p class="text-[11px] text-[#475569]">{{ $viz->code }} · {{ strtoupper($viz->kind) }}</p>
                                </div>
                                <span class="shrink-0 rounded-md px-2 py-0.5 text-[10px] font-bold {{ $viz->show ? 'bg-[#ECFDF5] text-[#047857]' : 'bg-slate-200 text-slate-700' }}">
                                    {{ $viz->show ? 'Tampil' : 'Tersembunyi' }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="wd-bento space-y-2.5 border-red-200/70 p-5">
                <p class="text-[11px] font-bold tracking-wider text-[#B91C1C] uppercase">Hapus Dataset</p>
                <p class="text-xs leading-relaxed text-[#475569]">
                    Hapus dataset beserta visualisasi terkait{{ $dataset->sourceForm ? ' agar formulir '.$dataset->sourceForm->code.' dapat disusun ulang jika diperlukan' : '' }}.
                </p>
                <button type="button"
                        @click="$dispatch('open-confirm', {
                            title: 'Hapus dataset ini?',
                            message: @js("Dataset “{$dataset->title}” ({$dataset->code}) beserta visualisasi terkait akan dihapus."),
                            action: @js(route('panel.dataset.destroy', $dataset->code)),
                            method: 'DELETE',
                            buttonText: 'Ya, Hapus Dataset'
                        })"
                        class="inline-flex min-h-[42px] w-full items-center justify-center gap-2 rounded-xl border border-red-200 bg-red-50/80 px-4 py-2 text-xs font-bold text-[#B91C1C] transition hover:bg-red-100">
                    <i data-lucide="trash-2" class="h-4 w-4" aria-hidden="true"></i>
                    Hapus Dataset Ini
                </button>
            </section>
        </aside>
    </div>
</div>
@endsection

@push('scripts')
<script>
function datasetShowManager() {
    const payloadEl = document.getElementById('dataset-show-payload');
    const payload = payloadEl ? JSON.parse(payloadEl.textContent || '{}') : {};
    const cols = payload.cols || [];
    const rows = payload.rows || [];
    const numericColumns = payload.numericColumns || {};
    const labelOptions = payload.labelOptions || [];
    const firstNumericIdx = payload.firstNumericIdx ?? null;
    const initialPublished = payload.initialPublished ?? false;
    const initialPublic = payload.initialPublic ?? false;

    return {
        cols,
        rows,
        numericColumns,
        labelOptions,
        labelIdx: labelOptions[0]?.idx ?? 0,
        valueIdx: firstNumericIdx !== null ? Number(firstNumericIdx) : 0,
        chartKind: 'bar',
        tableQuery: '',
        perPage: '15',
        page: 1,
        published: Boolean(initialPublished),
        isPublic: Boolean(initialPublic),
        busyPublish: false,
        busyAccess: false,

        filteredRows() {
            const q = this.tableQuery.trim().toLowerCase();
            const mapped = this.rows.map((cells, origIdx) => ({ origIdx, cells }));
            if (!q) return mapped;
            return mapped.filter((item) =>
                item.cells.some((c) => String(c ?? '').toLowerCase().includes(q))
            );
        },

        totalPages() {
            if (this.perPage === 'all') return 1;
            const size = Math.max(1, Number(this.perPage) || 15);
            return Math.max(1, Math.ceil(this.filteredRows().length / size));
        },

        pagedRows() {
            const list = this.filteredRows();
            if (this.perPage === 'all') return list;
            const size = Math.max(1, Number(this.perPage) || 15);
            const safePage = Math.min(this.page, this.totalPages());
            const start = (safePage - 1) * size;
            return list.slice(start, start + size);
        },

        chartSvg() {
            if (!window.SatuDataCharts || !this.rows.length) return '';
            const series = window.SatuDataCharts.series(this.rows, Number(this.labelIdx), Number(this.valueIdx));
            return window.SatuDataCharts.renderSvg(
                this.chartKind,
                series,
                window.SatuDataCharts.palettes.sky,
                620,
                230
            );
        },

        async togglePublish(url) {
            this.busyPublish = true;
            try {
                const resp = await fetch(url, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: JSON.stringify({ status_publikasi: this.published ? 'draft' : 'terpublikasi' }),
                });
                if (resp.ok) {
                    const res = await resp.json();
                    this.published = res.status_publikasi === 'terpublikasi';
                    this.$dispatch('toast', { message: res.message, tone: 'info' });
                }
            } finally {
                this.busyPublish = false;
            }
        },

        async toggleAccess(url) {
            this.busyAccess = true;
            try {
                const resp = await fetch(url, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: JSON.stringify({ access: this.isPublic ? 'private' : 'public' }),
                });
                if (resp.ok) {
                    const res = await resp.json();
                    this.isPublic = res.access === 'public';
                    this.$dispatch('toast', { message: res.message, tone: 'info' });
                }
            } finally {
                this.busyAccess = false;
            }
        },
    };
}
</script>
@endpush
