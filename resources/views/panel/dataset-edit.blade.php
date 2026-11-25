@extends('layouts.panel')

@section('title', 'Ubah Dataset ('.$dataset->code.') - Panel Satu Data UNUSA')

@section('panel_content')
@php
    $categoriesMap = $categories->mapWithKeys(fn ($c) => [(string) $c->id => $c->name])->all();
    $initialState = [
        'title' => (string) old('title', $dataset->title),
        'description' => (string) old('description', $dataset->description ?? ''),
        'categoryId' => (string) old('category_id', (string) $dataset->category_id),
        'tahun' => (string) old('tahun', (string) $dataset->tahun),
        'periode' => (string) old('periode', $dataset->periode),
        'access' => (string) old('access', $dataset->access),
        'publishStatus' => (string) old('publish_status', $dataset->status_publikasi ?? 'terpublikasi'),
        'format' => (string) old('format', $dataset->format),
        'rebuildFromSource' => $canRebuildSource && (bool) old('rebuild_from_source', ! empty($sourceConfig['mode']) || ! empty($sourceFormPayload)),
        'mode' => (string) old('mode', $sourceConfig['mode'] ?? ''),
        'groupFieldId' => (string) old('group_field_id', $sourceConfig['group_field_id'] ?? ''),
        'aggFunc' => (string) old('agg_func', $sourceConfig['agg_func'] ?? 'count'),
        'aggFieldId' => (string) old('agg_field_id', $sourceConfig['agg_field_id'] ?? ''),
        'pickedCols' => old('picked_cols', $sourceConfig['picked_cols'] ?? []),
        'colAliases' => old('col_aliases', $sourceConfig['col_aliases'] ?? []),
        'maskedCols' => old('masked_cols', $sourceConfig['masked_cols'] ?? []),
    ];
    $existingDatasetPayload = [
        'code' => $dataset->code,
        'cols' => array_values($dataset->cols ?? []),
        'rows' => array_values($dataset->rows ?? []),
        'updatedLabel' => $dataset->updated_label ?? ($dataset->updated_at?->locale('id')->translatedFormat('j M Y') ?? '-'),
    ];
    $editPayload = [
        'initialState' => $initialState,
        'categoriesMap' => $categoriesMap,
        'existingDataset' => $existingDatasetPayload,
        'sourceForm' => $sourceFormPayload,
    ];
@endphp

<script type="application/json" id="dataset-edit-payload">{!! json_encode($editPayload, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) !!}</script>

<div x-data="datasetEditManager()">
    <x-panel-head
        scene="dataset"
        badge="{{ $dataset->code }}"
        badgeTone="sky"
        title="Kelola & Ubah Dataset"
        desc="Perbarui informasi, status publikasi, kontrol akses, dan susunan data pada halaman penuh."
    >
        <a href="{{ route('panel.dataset.show', $dataset->code) }}"
           class="inline-flex min-h-[42px] items-center justify-center gap-2 rounded-xl border border-white/25 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/20">
            <i data-lucide="eye" class="h-4 w-4" aria-hidden="true"></i>
            Lihat Data Lengkap
        </a>
        <a href="{{ route('panel.dataset') }}"
           class="inline-flex min-h-[42px] items-center justify-center gap-2 rounded-xl border border-white/25 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/20">
            <i data-lucide="arrow-left" class="h-4 w-4" aria-hidden="true"></i>
            Kembali ke Kelola Dataset
        </a>
    </x-panel-head>

    @if($errors->any())
        <div class="mb-5 rounded-2xl border border-red-200 bg-[#FEF2F2] px-5 py-4 text-sm text-[#B91C1C]" role="alert">
            <p class="font-bold">Periksa kembali isian dataset:</p>
            <ul class="mt-1 list-disc pl-5 text-xs">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[minmax(0,1fr)_390px]">
        <!-- Kolom Kiri: Form Kelola & Ubah Dataset -->
        <form id="dataset-edit-form"
              x-ref="editForm"
              action="{{ route('panel.dataset.update', $dataset->code) }}"
              method="POST"
              @submit="submitEdit($event)"
              novalidate
              class="min-w-0 space-y-5">
            @csrf
            @method('PUT')

            <input type="hidden" name="periode" :value="b.periode">
            <input type="hidden" name="format" :value="b.format">
            <input type="hidden" name="publish_status" :value="b.publishStatus">
            <input type="hidden" name="access" :value="b.access">
            <input type="hidden" name="rebuild_from_source" :value="b.rebuildFromSource ? '1' : '0'">
            <template x-if="b.rebuildFromSource">
                <input type="hidden" name="mode" :value="b.mode">
            </template>

            <!-- 1. Identitas & Klasifikasi Dataset -->
            <section class="wd-bento space-y-5 p-5 sm:p-6">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-4">
                    <div>
                        <p class="text-[11px] font-bold tracking-wider text-[#0069A8] uppercase">Bagian 01</p>
                        <h2 class="mt-0.5 text-base font-bold text-slate-900">Identitas &amp; Klasifikasi Dataset</h2>
                        <p class="mt-0.5 text-xs text-[#475569]">Lengkapi judul, ringkasan deskripsi, kategori, dan periode akademik.</p>
                    </div>
                    <span class="rounded-lg bg-[#F0F9FF] px-2.5 py-1 text-xs font-bold tabular-nums text-[#0069A8]">{{ $dataset->code }}</span>
                </div>

                <div>
                    <div class="mb-1.5 flex items-center justify-between gap-2">
                        <label for="ds_edit_title" class="block text-sm font-semibold text-slate-800">
                            Judul dataset <span class="text-[#B91C1C]">*</span>
                        </label>
                        <span class="text-[11px] font-medium tabular-nums text-slate-400" x-text="`${b.title.length}/200`"></span>
                    </div>
                    <input id="ds_edit_title"
                           type="text"
                           name="title"
                           maxlength="200"
                           x-model="b.title"
                           class="min-h-[44px] w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-[#0069A8] focus:ring-4 focus:ring-sky-100" />
                    <span x-show="tried && !b.title.trim()" x-cloak class="mt-1 block text-xs font-medium text-[#B91C1C]">Judul dataset wajib diisi.</span>
                </div>

                <div>
                    <div class="mb-1.5 flex items-center justify-between gap-2">
                        <label for="ds_edit_desc" class="block text-sm font-semibold text-slate-800">Deskripsi</label>
                        <span class="text-[11px] font-medium tabular-nums text-slate-400" x-text="`${b.description.length}/1000`"></span>
                    </div>
                    <textarea id="ds_edit_desc"
                              rows="4"
                              name="description"
                              maxlength="1000"
                              x-model="b.description"
                              placeholder="Jelaskan cakupan data, sumber responden, dan kegunaan dataset..."
                              class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm leading-relaxed text-slate-900 outline-none focus:border-[#0069A8] focus:ring-4 focus:ring-sky-100"></textarea>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <label class="block">
                        <span class="mb-1.5 block text-sm font-semibold text-slate-800">Kategori <span class="text-[#B91C1C]">*</span></span>
                        <select name="category_id"
                                x-model="b.categoryId"
                                class="min-h-[44px] w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-[#0069A8] focus:ring-4 focus:ring-sky-100">
                            <option value="">Pilih kategori...</option>
                            @foreach($categories as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                        <span x-show="tried && !b.categoryId" x-cloak class="mt-1 block text-xs font-medium text-[#B91C1C]">Kategori wajib dipilih.</span>
                    </label>

                    <label class="block">
                        <span class="mb-1.5 block text-sm font-semibold text-slate-800">Tahun <span class="text-[#B91C1C]">*</span></span>
                        <input type="text"
                               name="tahun"
                               maxlength="4"
                               x-model="b.tahun"
                               class="min-h-[44px] w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm tabular-nums text-slate-900 outline-none focus:border-[#0069A8] focus:ring-4 focus:ring-sky-100" />
                        <span x-show="tried && !/^\d{4}$/.test(b.tahun)" x-cloak class="mt-1 block text-xs font-medium text-[#B91C1C]">Tahun harus 4 digit angka.</span>
                    </label>
                </div>

                <div class="grid grid-cols-1 gap-4 pt-1 sm:grid-cols-2">
                    <div>
                        <span class="mb-1.5 block text-sm font-semibold text-slate-800">Semester</span>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button"
                                    @click="b.periode = 'Ganjil'"
                                    :class="b.periode === 'Ganjil' ? 'border-[#0069A8] bg-[#F0F9FF] text-[#0069A8] ring-2 ring-[#0069A8]/15' : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300'"
                                    class="min-h-[44px] rounded-xl border px-3 py-2 text-xs font-bold transition">
                                Ganjil
                            </button>
                            <button type="button"
                                    @click="b.periode = 'Genap'"
                                    :class="b.periode === 'Genap' ? 'border-[#0069A8] bg-[#F0F9FF] text-[#0069A8] ring-2 ring-[#0069A8]/15' : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300'"
                                    class="min-h-[44px] rounded-xl border px-3 py-2 text-xs font-bold transition">
                                Genap
                            </button>
                        </div>
                    </div>

                    <div>
                        <span class="mb-1.5 block text-sm font-semibold text-slate-800">Format unduhan utama</span>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button"
                                    @click="b.format = 'XLSX'"
                                    :class="b.format === 'XLSX' ? 'border-[#0069A8] bg-[#F0F9FF] text-[#0069A8] ring-2 ring-[#0069A8]/15' : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300'"
                                    class="min-h-[44px] rounded-xl border px-3 py-2 text-left transition">
                                <span class="block text-xs font-bold">XLSX</span>
                                <span class="block text-[10px] opacity-75">Excel terformat</span>
                            </button>
                            <button type="button"
                                    @click="b.format = 'CSV'"
                                    :class="b.format === 'CSV' ? 'border-[#0069A8] bg-[#F0F9FF] text-[#0069A8] ring-2 ring-[#0069A8]/15' : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300'"
                                    class="min-h-[44px] rounded-xl border px-3 py-2 text-left transition">
                                <span class="block text-xs font-bold">CSV</span>
                                <span class="block text-[10px] opacity-75">Teks ringan</span>
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 2. Publikasi & Kontrol Akses -->
            <section class="wd-bento space-y-5 p-5 sm:p-6">
                <div class="border-b border-slate-100 pb-4">
                    <p class="text-[11px] font-bold tracking-wider text-[#0069A8] uppercase">Bagian 02</p>
                    <h2 class="mt-0.5 text-base font-bold text-slate-900">Publikasi &amp; Kontrol Akses</h2>
                    <p class="mt-0.5 text-xs text-[#475569]">Tentukan penayangan di portal dan mekanisme perizinan unduhan.</p>
                </div>

                <div>
                    <span class="mb-2 block text-sm font-semibold text-slate-800">Status publikasi</span>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <button type="button"
                                @click="b.publishStatus = 'terpublikasi'"
                                :class="b.publishStatus === 'terpublikasi' ? 'border-[#0069A8] bg-[#F0F9FF] ring-2 ring-[#0069A8]/15' : 'border-slate-200 bg-white hover:border-[#0069A8]/45'"
                                class="flex items-start gap-3 rounded-xl border p-4 text-left transition">
                            <span class="mt-0.5 grid h-9 w-9 shrink-0 place-items-center rounded-xl"
                                  :class="b.publishStatus === 'terpublikasi' ? 'bg-[#0069A8] text-white' : 'bg-slate-100 text-slate-600'">
                                <i data-lucide="globe" class="h-4 w-4" aria-hidden="true"></i>
                            </span>
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-slate-900">Terpublikasi</p>
                                <p class="mt-0.5 text-xs leading-relaxed text-[#475569]">Dataset tampil di katalog portal sesuai aturan akses.</p>
                            </div>
                        </button>

                        <button type="button"
                                @click="b.publishStatus = 'draft'"
                                :class="b.publishStatus === 'draft' ? 'border-[#182654] bg-slate-50 ring-2 ring-[#182654]/15' : 'border-slate-200 bg-white hover:border-slate-300'"
                                class="flex items-start gap-3 rounded-xl border p-4 text-left transition">
                            <span class="mt-0.5 grid h-9 w-9 shrink-0 place-items-center rounded-xl"
                                  :class="b.publishStatus === 'draft' ? 'bg-[#182654] text-white' : 'bg-slate-100 text-slate-600'">
                                <i data-lucide="file-clock" class="h-4 w-4" aria-hidden="true"></i>
                            </span>
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-slate-900">Draft</p>
                                <p class="mt-0.5 text-xs leading-relaxed text-[#475569]">Disembunyikan dari publik; hanya terlihat di panel staf.</p>
                            </div>
                        </button>
                    </div>
                </div>

                <div>
                    <span class="mb-2 block text-sm font-semibold text-slate-800">Tipe akses data</span>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <button type="button"
                                @click="b.access = 'public'"
                                :class="b.access === 'public' ? 'border-emerald-500 bg-[#ECFDF5] ring-2 ring-emerald-500/15' : 'border-slate-200 bg-white hover:border-emerald-300'"
                                class="flex items-start gap-3 rounded-xl border p-4 text-left transition">
                            <span class="mt-0.5 grid h-9 w-9 shrink-0 place-items-center rounded-xl"
                                  :class="b.access === 'public' ? 'bg-[#047857] text-white' : 'bg-slate-100 text-slate-600'">
                                <i data-lucide="unlock" class="h-4 w-4" aria-hidden="true"></i>
                            </span>
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-slate-900">Publik</p>
                                <p class="mt-0.5 text-xs leading-relaxed text-[#475569]">Pengunjung langsung bisa pratinjau dan mengunduh CSV/XLSX.</p>
                            </div>
                        </button>

                        <button type="button"
                                @click="b.access = 'private'"
                                :class="b.access === 'private' ? 'border-amber-500 bg-[#FFFBEB] ring-2 ring-amber-500/15' : 'border-slate-200 bg-white hover:border-amber-300'"
                                class="flex items-start gap-3 rounded-xl border p-4 text-left transition">
                            <span class="mt-0.5 grid h-9 w-9 shrink-0 place-items-center rounded-xl"
                                  :class="b.access === 'private' ? 'bg-[#92400E] text-white' : 'bg-slate-100 text-slate-600'">
                                <i data-lucide="lock" class="h-4 w-4" aria-hidden="true"></i>
                            </span>
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-slate-900">Private</p>
                                <p class="mt-0.5 text-xs leading-relaxed text-[#475569]">Pengunjung wajib mengajukan permohonan dan menunggu persetujuan.</p>
                            </div>
                        </button>
                    </div>
                </div>
            </section>

            <!-- 3. Sumber Formulir & Susunan Data (Versi Ringkas / Versi Detail) -->
            @if($sourceForm)
                <section id="bagian-susunan" class="wd-bento space-y-4 p-5 sm:p-6">
                    <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 pb-4">
                        <div>
                            <p class="text-[11px] font-bold tracking-wider text-[#0069A8] uppercase">Bagian 03</p>
                            <h2 class="mt-0.5 text-base font-bold text-slate-900">Versi Dataset &amp; Susunan Data</h2>
                            <p class="mt-0.5 text-xs text-[#475569]">
                                Tersambung dengan <strong class="text-slate-800">{{ $sourceForm->code }}</strong> · {{ $sourceForm->title }}
                            </p>
                        </div>
                        <span class="rounded-lg px-2.5 py-1 text-xs font-bold {{ $canRebuildSource ? 'bg-[#ECFDF5] text-[#047857]' : 'bg-slate-100 text-slate-700' }}">
                            {{ $canRebuildSource ? ($sourceFormPayload['validCount'].' baris siap') : ($sourceForm->isOpen() ? 'Form masih dibuka' : 'Perlu Pre-processing') }}
                        </span>
                    </div>

                    <div class="rounded-xl border border-slate-200/80 bg-[#F8FAFC] p-3.5">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="text-xs font-bold text-slate-700">Kolom dataset tersimpan saat ini</p>
                            @if(! empty($sourceConfig['mode']))
                                <span class="rounded-md bg-white px-2 py-0.5 text-[11px] font-bold text-[#0069A8] ring-1 ring-slate-200/80">
                                    {{ $sourceConfig['mode'] === 'rekap' ? 'Saat ini: Versi Ringkas' : 'Saat ini: Versi Detail' }}
                                </span>
                            @endif
                        </div>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @foreach($dataset->cols as $colName)
                                <span class="rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $colName }}</span>
                            @endforeach
                        </div>
                    </div>

                    @if($canRebuildSource)
                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-bold text-slate-900">Ubah susunan kolom atau agregasi dari formulir</p>
                                    <p class="mt-0.5 text-xs text-[#475569]">Pilih langsung Versi Ringkas atau Versi Detail di bawah untuk memperbarui isi tabel dataset.</p>
                                </div>
                                <button type="button"
                                        role="switch"
                                        aria-label="Aktifkan ubah susunan data"
                                        :aria-checked="b.rebuildFromSource"
                                        @click="b.rebuildFromSource = !b.rebuildFromSource"
                                        :class="b.rebuildFromSource ? 'bg-[#0069A8]' : 'bg-slate-300'"
                                        class="relative h-6 w-11 shrink-0 rounded-full transition">
                                    <span :class="b.rebuildFromSource ? 'left-[22px]' : 'left-0.5'" class="absolute top-0.5 h-5 w-5 rounded-full bg-white shadow transition"></span>
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2" role="group" aria-label="Pilih versi dataset">
                            <button type="button"
                                    @click="selectMode('rekap')"
                                    :disabled="groupableFields().length === 0"
                                    :class="b.rebuildFromSource && b.mode === 'rekap'
                                        ? 'border-[#0069A8] bg-[#F0F9FF] ring-2 ring-[#0069A8]/15'
                                        : 'border-slate-200 bg-white hover:border-[#0069A8]/50'"
                                    class="min-h-[76px] rounded-xl border p-4 text-left transition disabled:cursor-not-allowed disabled:opacity-50">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-sm font-bold text-slate-900">Versi Ringkas (Rekap Agregat)</p>
                                    <span class="rounded bg-white px-2 py-0.5 text-[10px] font-bold text-[#0069A8] ring-1 ring-sky-200">Ringkas</span>
                                </div>
                                <p class="mt-1 text-xs leading-relaxed text-[#475569]">Jumlah, total, minimum, maksimum, atau rata-rata per grup.</p>
                            </button>

                            <button type="button"
                                    @click="selectMode('rinci')"
                                    :class="b.rebuildFromSource && b.mode === 'rinci'
                                        ? 'border-[#0069A8] bg-[#F0F9FF] ring-2 ring-[#0069A8]/15'
                                        : 'border-slate-200 bg-white hover:border-[#0069A8]/50'"
                                    class="min-h-[76px] rounded-xl border p-4 text-left transition">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-sm font-bold text-slate-900">Versi Detail (Tabel Rinci)</p>
                                    <span class="rounded bg-white px-2 py-0.5 text-[10px] font-bold text-[#047857] ring-1 ring-emerald-200">Detail</span>
                                </div>
                                <p class="mt-1 text-xs leading-relaxed text-[#475569]">Semua baris data valid lengkap dengan pilihan kolom, alias, dan penyamaran.</p>
                            </button>
                        </div>

                        <template x-if="b.rebuildFromSource && sourceForm">
                            <div class="space-y-4 rounded-xl border border-[#BAE6FD] bg-[#F0F9FF]/45 p-4">
                                <template x-if="b.mode === 'rekap'">
                                    <div class="space-y-4 rounded-xl border border-slate-200 bg-white p-4">
                                        <input type="hidden" name="group_field_id" :value="b.groupFieldId">
                                        <input type="hidden" name="agg_func" :value="b.aggFunc">
                                        <template x-if="b.aggFunc !== 'count'">
                                            <input type="hidden" name="agg_field_id" :value="b.aggFieldId">
                                        </template>

                                        <div>
                                            <p class="mb-2 text-xs font-bold text-slate-700">Kelompokkan berdasarkan <span class="text-[#B91C1C]">*</span></p>
                                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                                <template x-for="gf in groupableFields()" :key="gf.id">
                                                    <label class="flex cursor-pointer items-center justify-between gap-2 rounded-xl border bg-white px-3 py-2.5 text-sm transition"
                                                           :class="b.groupFieldId === gf.id ? 'border-[#0069A8] bg-[#F0F9FF] font-semibold text-[#0069A8]' : 'border-slate-200 text-slate-800'">
                                                        <span class="flex min-w-0 items-center gap-2.5">
                                                            <input type="radio"
                                                                   :value="gf.id"
                                                                   :checked="String(b.groupFieldId) === String(gf.id)"
                                                                   @change="b.groupFieldId = gf.id"
                                                                   class="h-4 w-4 shrink-0 accent-[#0069A8]" />
                                                            <span class="truncate" x-text="gf.label"></span>
                                                        </span>
                                                        <template x-if="gf.uniqueCount !== undefined">
                                                            <span class="shrink-0 rounded px-1.5 py-0.5 text-[10px] font-bold tabular-nums"
                                                                  :class="gf.uniqueCount >= 2 && gf.uniqueCount <= 12 ? 'bg-[#ECFDF5] text-[#047857]' : (gf.uniqueCount > 12 ? 'bg-[#FFFBEB] text-[#92400E]' : 'bg-slate-100 text-slate-600')"
                                                                  x-text="gf.uniqueCount >= 2 && gf.uniqueCount <= 12 ? `${gf.uniqueCount} grup · ideal` : (gf.uniqueCount > 12 ? `${gf.uniqueCount} grup · padat` : `${gf.uniqueCount} grup`)"></span>
                                                        </template>
                                                    </label>
                                                </template>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                            <label class="block">
                                                <span class="mb-1.5 block text-xs font-bold text-slate-700">Perhitungan</span>
                                                <select x-model="b.aggFunc" class="min-h-[42px] w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 outline-none focus:border-[#0069A8]">
                                                    <option value="count">Jumlah baris (COUNT)</option>
                                                    <template x-if="numericFields().length > 0">
                                                        <option value="sum">Total nilai (SUM)</option>
                                                    </template>
                                                    <template x-if="numericFields().length > 0">
                                                        <option value="avg">Rata-rata (AVG)</option>
                                                    </template>
                                                    <template x-if="numericFields().length > 0">
                                                        <option value="min">Nilai minimum (MIN)</option>
                                                    </template>
                                                    <template x-if="numericFields().length > 0">
                                                        <option value="max">Nilai maksimum (MAX)</option>
                                                    </template>
                                                </select>
                                            </label>
                                            <template x-if="b.aggFunc !== 'count'">
                                                <label class="block">
                                                    <span class="mb-1.5 block text-xs font-bold text-slate-700">Kolom angka <span class="text-[#B91C1C]">*</span></span>
                                                    <select x-model="b.aggFieldId" class="min-h-[42px] w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 outline-none focus:border-[#0069A8]">
                                                        <template x-for="nf in numericFields()" :key="nf.id">
                                                            <option :value="nf.id" :selected="String(b.aggFieldId) === String(nf.id)" x-text="nf.label"></option>
                                                        </template>
                                                    </select>
                                                </label>
                                            </template>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="b.mode === 'rinci'">
                                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                                        <template x-for="pid in b.pickedCols" :key="'h-pick-' + pid">
                                            <input type="hidden" name="picked_cols[]" :value="pid">
                                        </template>
                                        <template x-for="mid in b.maskedCols" :key="'h-mask-' + mid">
                                            <input type="hidden" name="masked_cols[]" :value="mid">
                                        </template>

                                        <div class="mb-2.5 flex flex-wrap items-center justify-between gap-2">
                                            <p class="text-xs font-bold text-slate-800">Pilih kolom, nama tampilan, dan samarkan jika sensitif <span class="text-[#B91C1C]">*</span></p>
                                            <div class="flex flex-wrap gap-1.5">
                                                <button type="button"
                                                        @click="pickAllCols()"
                                                        class="rounded-lg border border-slate-200 bg-[#F8FAFC] px-2.5 py-1 text-[11px] font-bold text-slate-700 hover:border-[#0069A8] hover:text-[#0069A8]">
                                                    Pilih semua
                                                </button>
                                                <button type="button"
                                                        @click="pickQuestionColsOnly()"
                                                        class="rounded-lg border border-slate-200 bg-[#F8FAFC] px-2.5 py-1 text-[11px] font-bold text-slate-700 hover:border-[#0069A8] hover:text-[#0069A8]">
                                                    Hanya isian form
                                                </button>
                                            </div>
                                        </div>

                                        <div class="space-y-2">
                                            <template x-for="col in sourceForm.sourceCols" :key="col.id">
                                                <div class="grid grid-cols-[auto_1fr_auto] items-center gap-2.5 rounded-xl border border-slate-200 bg-[#F8FAFC] px-3 py-2 text-xs">
                                                    <label class="flex items-center">
                                                        <input type="checkbox"
                                                               :value="col.id"
                                                               :checked="b.pickedCols.includes(col.id)"
                                                               @change="togglePickedCol(col.id)"
                                                               class="h-4 w-4 accent-[#0069A8]" />
                                                    </label>
                                                    <input type="text"
                                                           :name="`col_aliases[${col.id}]`"
                                                           x-model="b.colAliases[col.id]"
                                                           :placeholder="col.label"
                                                           aria-label="Nama tampilan kolom"
                                                           class="min-w-0 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs text-slate-800 outline-none focus:border-[#0069A8]" />
                                                    <label class="flex cursor-pointer items-center gap-1.5 font-semibold text-[#475569]">
                                                        <input type="checkbox"
                                                               :value="col.id"
                                                               :checked="b.maskedCols.includes(col.id)"
                                                               @change="toggleMaskedCol(col.id)"
                                                               class="h-4 w-4 accent-[#182654]" />
                                                        <span>Samarkan</span>
                                                    </label>
                                                </div>
                                            </template>
                                        </div>
                                        <span x-show="tried && !b.pickedCols.length" class="mt-2 block text-xs font-semibold text-[#B91C1C]">Pilih minimal satu kolom.</span>
                                    </div>
                                </template>
                            </div>
                        </template>
                    @else
                        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-amber-200 bg-[#FFFBEB] px-4 py-3 text-xs leading-relaxed text-[#92400E]">
                            <p class="min-w-0 flex-1">
                                Formulir sumber saat ini belum siap untuk diubah ke Versi Ringkas atau Versi Detail ({{ $sourceForm->isOpen() ? 'formulir masih berstatus dibuka' : 'menunggu finalisasi di Pre-processing Data' }}). Anda tetap dapat mengubah seluruh metadata, publikasi, dan akses dataset di atas.
                            </p>
                            <a href="{{ $sourceForm->isOpen() ? route('panel.formulir.edit', $sourceForm->code) : route('panel.preprocessing.show', $sourceForm->code) }}"
                               class="inline-flex min-h-[34px] shrink-0 items-center rounded-lg bg-[#0069A8] px-3 py-1.5 text-xs font-bold text-white hover:bg-[#005187]">
                                {{ $sourceForm->isOpen() ? 'Buka Formulir' : 'Selesaikan Pre-processing' }}
                            </a>
                        </div>
                    @endif
                </section>
            @endif
        </form>

        <!-- Kolom Kanan: Pratinjau Langsung & Aksi -->
        <aside class="space-y-4 lg:sticky lg:top-24">
            <!-- Pratinjau Kartu Katalog -->
            <section class="wd-bento space-y-3 p-5">
                <div class="flex items-center justify-between gap-2">
                    <h2 class="text-sm font-extrabold text-slate-900">Pratinjau Kartu Dataset</h2>
                    <span class="rounded bg-[#F0F9FF] px-2 py-0.5 text-[11px] font-bold tabular-nums text-[#0069A8]">{{ $dataset->code }}</span>
                </div>

                <div class="rounded-xl border border-slate-200/80 bg-[#F8FAFC] p-4">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span x-show="b.publishStatus === 'terpublikasi'" class="rounded-md bg-[#F0F9FF] px-2 py-0.5 text-[11px] font-bold text-[#0069A8]">Terbit</span>
                        <span x-show="b.publishStatus !== 'terpublikasi'" x-cloak class="rounded-md bg-slate-200 px-2 py-0.5 text-[11px] font-bold text-slate-700">Draft</span>
                        <span x-show="b.access === 'public'" class="rounded-md bg-[#ECFDF5] px-2 py-0.5 text-[11px] font-bold text-[#047857]">Publik</span>
                        <span x-show="b.access !== 'public'" x-cloak class="rounded-md bg-[#FFFBEB] px-2 py-0.5 text-[11px] font-bold text-[#92400E]">Private</span>
                        <span class="rounded-md bg-white px-2 py-0.5 text-[11px] font-bold text-slate-600 ring-1 ring-slate-200/80" x-text="b.format"></span>
                        <template x-if="b.rebuildFromSource && sourceForm">
                            <span class="rounded-md px-2 py-0.5 text-[11px] font-bold"
                                  :class="b.mode === 'rekap' ? 'bg-[#EFF6FF] text-[#182654]' : 'bg-[#ECFDF5] text-[#047857]'"
                                  x-text="b.mode === 'rekap' ? 'Versi Ringkas' : 'Versi Detail'"></span>
                        </template>
                    </div>

                    <p class="mt-2.5 text-sm font-extrabold leading-snug text-slate-900" x-text="b.title.trim() || 'Judul dataset belum diisi'"></p>
                    <p class="mt-1 line-clamp-2 text-xs leading-relaxed text-[#475569]" x-text="b.description.trim() || 'Tidak ada deskripsi tambahan.'"></p>

                    <div class="mt-3 flex flex-wrap items-center gap-2 border-t border-slate-200/80 pt-2.5 text-[11px] font-semibold text-[#475569]">
                        <span x-text="categoryName()"></span>
                        <span>·</span>
                        <span class="tabular-nums" x-text="`${b.tahun || '----'} · ${b.periode}`"></span>
                    </div>
                </div>
            </section>

            <!-- Pratinjau Isi Data & Grafik -->
            <section class="wd-bento space-y-3 p-5">
                <div class="flex items-center justify-between gap-2">
                    <h2 class="text-sm font-extrabold text-slate-900">Pratinjau Isi Data</h2>
                    <span class="rounded-md bg-[#F0F9FF] px-2.5 py-0.5 text-xs font-bold tabular-nums text-[#0069A8]"
                          x-text="`${previewData().rows.length} baris`"></span>
                </div>

                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="w-full">
                        <thead class="bg-[#F8FAFC]">
                            <tr>
                                <template x-for="col in previewData().cols" :key="col">
                                    <th class="px-3 py-2 text-left text-[11px] font-bold whitespace-nowrap text-[#475569]" x-text="col"></th>
                                </template>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(row, rIdx) in previewData().rows.slice(0, 6)" :key="rIdx">
                                <tr class="border-t border-slate-100">
                                    <template x-for="(cell, cIdx) in row" :key="cIdx">
                                        <td class="max-w-[150px] truncate px-3 py-2 text-xs tabular-nums text-slate-700" x-text="cell"></td>
                                    </template>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    <template x-if="previewData().rows.length === 0">
                        <p class="py-6 text-center text-xs text-[#475569]">Belum ada baris data.</p>
                    </template>
                </div>

                <template x-if="previewChartSvg()">
                    <div class="h-40 rounded-xl border border-slate-200 bg-[#F8FAFC] p-2.5" x-html="previewChartSvg()"></div>
                </template>

                <template x-if="b.rebuildFromSource && rebuildError()">
                    <p class="rounded-xl border border-amber-200 bg-[#FFFBEB] px-3 py-2 text-xs font-medium text-[#92400E]" x-text="rebuildError()"></p>
                </template>

                <a href="{{ route('panel.dataset.show', $dataset->code) }}"
                   class="inline-flex w-full items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-[#0069A8] transition hover:border-[#0069A8] hover:bg-[#F0F9FF]">
                    <i data-lucide="eye" class="h-3.5 w-3.5" aria-hidden="true"></i>
                    Buka Seluruh Data ({{ count($dataset->rows ?? []) }} baris)
                </a>
            </section>

            <!-- Tombol Simpan Utama (menunjuk ke form="dataset-edit-form") -->
            <section class="wd-bento space-y-2 p-5">
                <button type="submit"
                        form="dataset-edit-form"
                        name="after_save"
                        value="index"
                        :disabled="b.rebuildFromSource && Boolean(rebuildError())"
                        class="inline-flex min-h-[44px] w-full items-center justify-center gap-2 rounded-xl bg-[#0069A8] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#005187] disabled:cursor-not-allowed disabled:opacity-45">
                    <i data-lucide="save" class="h-4 w-4" aria-hidden="true"></i>
                    Simpan Perubahan
                </button>
                <button type="submit"
                        form="dataset-edit-form"
                        name="after_save"
                        value="stay"
                        :disabled="b.rebuildFromSource && Boolean(rebuildError())"
                        class="inline-flex min-h-[40px] w-full items-center justify-center rounded-xl border border-[#BAE6FD] bg-[#F0F9FF] px-4 py-2 text-xs font-bold text-[#0069A8] transition hover:bg-sky-100 disabled:cursor-not-allowed disabled:opacity-45">
                    Simpan &amp; Tetap di Halaman Ini
                </button>
                <a href="{{ route('panel.dataset') }}"
                   class="inline-flex min-h-[40px] w-full items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                    Batal
                </a>
            </section>

            <!-- Tindakan Tambahan (Form terpisah, tidak bersarang) -->
            <section class="wd-bento space-y-2.5 p-5">
                <p class="text-[11px] font-bold tracking-wider text-[#475569] uppercase">Tindakan Tambahan</p>

                @if($sourceForm)
                    <form method="POST" action="{{ route('panel.dataset.sync', $dataset->code) }}">
                        @csrf
                        <input type="hidden" name="redirect_to" value="edit">
                        <button type="submit"
                                class="inline-flex min-h-[40px] w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 transition hover:border-[#0069A8] hover:text-[#0069A8]">
                            <i data-lucide="refresh-cw" class="h-3.5 w-3.5" aria-hidden="true"></i>
                            Sinkronkan Ulang dari {{ $sourceForm->code }}
                        </button>
                    </form>
                @endif

                <div class="grid grid-cols-2 gap-2">
                    <a href="{{ route('dataset.download', [$dataset->code, 'xlsx']) }}"
                       class="inline-flex min-h-[38px] items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:border-[#0069A8] hover:text-[#0069A8]">
                        Unduh XLSX
                    </a>
                    <a href="{{ route('dataset.download', [$dataset->code, 'csv']) }}"
                       class="inline-flex min-h-[38px] items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:border-[#0069A8] hover:text-[#0069A8]">
                        Unduh CSV
                    </a>
                </div>

                <div class="border-t border-slate-100 pt-2.5">
                    <button type="button"
                            @click="$dispatch('open-confirm', {
                                title: 'Hapus dataset ini?',
                                message: @js("Dataset “{$dataset->title}” ({$dataset->code}) beserta visualisasi terkait akan dihapus."),
                                action: @js(route('panel.dataset.destroy', $dataset->code)),
                                method: 'DELETE',
                                buttonText: 'Ya, Hapus Dataset'
                            })"
                            class="inline-flex min-h-[40px] w-full items-center justify-center gap-2 rounded-xl border border-red-200 bg-red-50/80 px-4 py-2 text-xs font-bold text-[#B91C1C] transition hover:bg-red-100">
                        <i data-lucide="trash-2" class="h-3.5 w-3.5" aria-hidden="true"></i>
                        Hapus Dataset
                    </button>
                </div>
            </section>
        </aside>
    </div>
</div>
@endsection

@push('scripts')
<script>
function datasetEditManager() {
    const payloadEl = document.getElementById('dataset-edit-payload');
    const payload = payloadEl ? JSON.parse(payloadEl.textContent || '{}') : {};
    const initialState = payload.initialState || {};
    const categoriesMap = payload.categoriesMap || {};
    const existingDataset = payload.existingDataset || { cols: [], rows: [] };
    const sourceForm = payload.sourceForm || null;

    return {
        b: { ...initialState },
        categoriesMap,
        existingDataset,
        sourceForm,
        tried: false,

        init() {
            if (this.sourceForm) {
                const groupable = this.groupableFields();
                const numeric = this.numericFields();
                const validColIds = new Set((this.sourceForm.sourceCols || []).map((c) => c.id));
                if (!this.b.mode || !['rekap', 'rinci'].includes(this.b.mode)) {
                    this.b.mode = groupable.length ? 'rekap' : 'rinci';
                }
                if (this.b.mode === 'rekap' && groupable.length === 0) {
                    this.b.mode = 'rinci';
                }
                if (!this.b.groupFieldId || !groupable.some((g) => g.id === this.b.groupFieldId)) {
                    this.b.groupFieldId = groupable[0]?.id || '';
                }
                if (!numeric.length) {
                    this.b.aggFunc = 'count';
                    this.b.aggFieldId = '';
                } else if (!this.b.aggFieldId || !numeric.some((n) => n.id === this.b.aggFieldId)) {
                    this.b.aggFieldId = numeric[0].id;
                }
                if (!Array.isArray(this.b.pickedCols)) {
                    this.b.pickedCols = [];
                }
                this.b.pickedCols = this.b.pickedCols.filter((id) => validColIds.has(id));
                if (!this.b.pickedCols.length) {
                    this.b.pickedCols = (this.sourceForm.sourceCols || [])
                        .filter((c) => c.id !== '_at')
                        .map((c) => c.id);
                }
                if (!this.b.colAliases || typeof this.b.colAliases !== 'object' || Array.isArray(this.b.colAliases)) {
                    this.b.colAliases = {};
                }
                if (!Array.isArray(this.b.maskedCols)) {
                    this.b.maskedCols = [];
                }
                this.b.maskedCols = this.b.maskedCols.filter((id) => validColIds.has(id));
            }
        },

        selectMode(nextMode) {
            if (nextMode === 'rekap' && this.groupableFields().length === 0) return;
            const prevMode = this.b.mode;
            this.b.mode = nextMode;
            this.b.rebuildFromSource = true;

            if (this.sourceForm && prevMode !== nextMode) {
                const rekapDefault = `Rekap ${this.sourceForm.title}`;
                const rinciDefault = `Dataset ${this.sourceForm.title}`;
                const curTitle = String(this.b.title || '').trim();
                if (nextMode === 'rinci' && curTitle === rekapDefault) {
                    this.b.title = rinciDefault;
                } else if (nextMode === 'rekap' && curTitle === rinciDefault) {
                    this.b.title = rekapDefault;
                }
            }

            if (nextMode === 'rekap' && !this.groupableFields().some((g) => g.id === this.b.groupFieldId)) {
                this.b.groupFieldId = this.groupableFields()[0]?.id || '';
            }
            if (nextMode === 'rinci' && (!Array.isArray(this.b.pickedCols) || !this.b.pickedCols.length)) {
                this.b.pickedCols = (this.sourceForm?.sourceCols || [])
                    .filter((c) => c.id !== '_at')
                    .map((c) => c.id);
            }
        },

        togglePickedCol(colId) {
            const exists = this.b.pickedCols.includes(colId);
            if (exists) {
                this.b.pickedCols = this.b.pickedCols.filter((id) => id !== colId);
            } else {
                const order = (this.sourceForm?.sourceCols || []).map((c) => c.id);
                const next = [...this.b.pickedCols, colId];
                this.b.pickedCols = next.sort((a, b) => order.indexOf(a) - order.indexOf(b));
            }
        },

        toggleMaskedCol(colId) {
            if (this.b.maskedCols.includes(colId)) {
                this.b.maskedCols = this.b.maskedCols.filter((id) => id !== colId);
            } else {
                this.b.maskedCols = [...this.b.maskedCols, colId];
            }
        },

        pickAllCols() {
            if (!this.sourceForm) return;
            this.b.pickedCols = (this.sourceForm.sourceCols || []).map((c) => c.id);
        },

        pickQuestionColsOnly() {
            if (!this.sourceForm) return;
            const metaIds = new Set(['_nama', '_nim', '_at']);
            const qCols = (this.sourceForm.sourceCols || [])
                .filter((c) => !metaIds.has(c.id))
                .map((c) => c.id);
            this.b.pickedCols = qCols.length ? qCols : (this.sourceForm.sourceCols || []).map((c) => c.id);
        },

        categoryName() {
            return this.categoriesMap[String(this.b.categoryId)] || 'Kategori belum dipilih';
        },

        groupableFields() {
            if (!this.sourceForm) return [];
            if (Array.isArray(this.sourceForm.groupableFields) && this.sourceForm.groupableFields.length) {
                return this.sourceForm.groupableFields;
            }
            return (this.sourceForm.fields || []).filter((x) =>
                ['select', 'radio', 'checkbox', 'text', 'linear_scale', 'rating'].includes(x.type)
            );
        },

        numericFields() {
            if (!this.sourceForm) return [];
            return (this.sourceForm.fields || []).filter((x) =>
                ['number', 'linear_scale', 'rating'].includes(x.type)
            );
        },

        maskValue(cid, val, idx) {
            if (cid === '_nama') return 'Responden #' + String(idx + 1).padStart(3, '0');
            const s = String(val ?? '').trim();
            if (!s || s === '-') return s;
            if (s.length <= 5) return s.slice(0, 1) + '*'.repeat(Math.max(3, s.length - 1));
            return s.slice(0, 3) + '***' + s.slice(-2);
        },

        rebuildError() {
            if (!this.b.rebuildFromSource || !this.sourceForm) return '';
            if (this.b.mode === 'rekap' && !this.groupableFields().some((x) => x.id === this.b.groupFieldId)) {
                return 'Pilih kolom pengelompokan yang valid atau gunakan Tabel Rinci.';
            }
            if (this.b.mode === 'rekap' && this.b.aggFunc !== 'count' && !this.b.aggFieldId) {
                return 'Pilih kolom angka untuk agregasi.';
            }
            if (this.b.mode === 'rinci' && !this.b.pickedCols.length) {
                return 'Pilih minimal satu kolom untuk Tabel Rinci.';
            }
            return '';
        },

        previewData() {
            if (!this.b.rebuildFromSource || !this.sourceForm) {
                return {
                    cols: this.existingDataset.cols || [],
                    rows: this.existingDataset.rows || [],
                };
            }

            const f = this.sourceForm;
            if (this.b.mode === 'rekap') {
                const fl = (f.fields || []).find((x) => x.id === this.b.groupFieldId);
                const aggField = (f.fields || []).find((x) => x.id === this.b.aggFieldId);
                const buckets = new Map();

                (f.subs || []).forEach((s) => {
                    const raw = String(s.values?.[this.b.groupFieldId] || '').trim();
                    let keys = [];
                    if (fl?.type === 'checkbox' && raw) {
                        keys = raw.split(',').map((x) => x.trim()).filter(Boolean);
                    }
                    if (!keys.length) keys = [raw || '(kosong)'];

                    const rawMetric = this.b.aggFieldId ? window.SatuDataCharts?.parseNum(s.values?.[this.b.aggFieldId]) : null;
                    keys.forEach((k) => {
                        if (!buckets.has(k)) buckets.set(k, { count: 0, nums: [] });
                        const bkt = buckets.get(k);
                        bkt.count++;
                        if (rawMetric !== null && Number.isFinite(rawMetric)) bkt.nums.push(rawMetric);
                    });
                });

                (fl?.options || []).forEach((o) => {
                    if (!buckets.has(o)) buckets.set(o, { count: 0, nums: [] });
                });

                const rows = [...buckets.entries()].map(([k, bkt]) => {
                    if (this.b.aggFunc === 'count') return [k, bkt.count];
                    if (!bkt.nums.length) return [k, 0];
                    let v = 0;
                    if (this.b.aggFunc === 'sum') v = bkt.nums.reduce((a, c) => a + c, 0);
                    if (this.b.aggFunc === 'avg') v = bkt.nums.reduce((a, c) => a + c, 0) / bkt.nums.length;
                    if (this.b.aggFunc === 'min') v = Math.min(...bkt.nums);
                    if (this.b.aggFunc === 'max') v = Math.max(...bkt.nums);
                    return [k, Math.round(v * 100) / 100];
                });

                const metricLabel = {
                    count: 'Jumlah',
                    sum: `Total ${aggField?.label || 'Nilai'}`,
                    avg: `Rata-rata ${aggField?.label || 'Nilai'}`,
                    min: `Minimum ${aggField?.label || 'Nilai'}`,
                    max: `Maksimum ${aggField?.label || 'Nilai'}`,
                }[this.b.aggFunc] || 'Jumlah';

                return {
                    cols: [fl?.label || 'Kategori', metricLabel],
                    rows,
                };
            }

            const sc = (f.sourceCols || []).filter((c) => this.b.pickedCols.includes(c.id));
            return {
                cols: sc.map((c) => String(this.b.colAliases[c.id] || '').trim() || c.label),
                rows: (f.subs || []).map((s, idx) =>
                    sc.map((c) => {
                        let raw = '';
                        if (c.id === '_nama') raw = s.nama;
                        else if (c.id === '_nim') raw = s.nim;
                        else if (c.id === '_at') raw = s.at;
                        else raw = s.values?.[c.id] ?? '';
                        return this.b.maskedCols.includes(c.id) ? this.maskValue(c.id, raw, idx) : raw;
                    })
                ),
            };
        },

        previewChartSvg() {
            if (!window.SatuDataCharts) return '';
            const out = this.previewData();
            if (!out.rows.length || !out.cols.length) return '';
            const numIdxs = window.SatuDataCharts.numericCols(out.rows, out.cols);
            if (!numIdxs.length) return '';
            const valIdx = numIdxs[0];
            const labelIdx = out.cols.findIndex((_, idx) => idx !== valIdx);
            const data = window.SatuDataCharts.series(out.rows, labelIdx >= 0 ? labelIdx : 0, valIdx);
            if (!data.length) return '';
            return window.SatuDataCharts.renderSvg('bar', data, window.SatuDataCharts.palettes.sky, 320, 150);
        },

        submitEdit(ev) {
            this.tried = true;
            if (
                !this.b.title.trim() ||
                !this.b.categoryId ||
                !/^\d{4}$/.test(this.b.tahun) ||
                (this.b.rebuildFromSource && this.rebuildError())
            ) {
                ev.preventDefault();
            }
        },
    };
}
</script>
@endpush
