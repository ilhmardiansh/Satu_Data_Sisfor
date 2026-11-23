@extends('layouts.app')

@section('title', 'Dataset Sistem Informasi - Satu Data UNUSA')

@push('styles')
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
<main class="mx-auto max-w-[1200px] px-4 py-10 sm:px-8" x-data="datasetManager()" x-cloak>
    <!-- Breadcrumb -->
    <p class="text-sm text-slate-500">
        <a href="{{ route('beranda') }}" class="hover:text-sky-600 transition">Beranda</a>
        <span class="mx-1">/</span>
        <span class="text-sky-600 font-medium">Dataset</span>
    </p>

    <!-- Header -->
    <h1 class="mt-1 text-[28px] font-extrabold tracking-tight text-slate-900">
        Dataset Sistem Informasi
    </h1>
    <p class="mt-1 text-[15px] text-slate-500">
        Dataset publik dapat langsung dilihat dan diunduh. Dataset private memerlukan permohonan yang dikonfirmasi Wali Data.
    </p>

    <!-- Permohonan Saya (jika ada permohonan) -->
    <template x-if="myRequests.length > 0">
        <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="mb-3 text-xs font-bold tracking-wider text-slate-500 uppercase">
                Permohonan saya
            </p>
            <div class="flex flex-wrap gap-2">
                <template x-for="r in myRequests" :key="r.id">
                    <button
                        type="button"
                        @click="handleRequestPillClick(r)"
                        :class="{
                            'bg-sky-50 text-sky-700 hover:bg-sky-100': r.status === 'menunggu',
                            'bg-emerald-50 text-emerald-700 hover:bg-emerald-100': r.status === 'disetujui',
                            'bg-red-50 text-[#b02a37] hover:bg-red-100': r.status === 'ditolak'
                        }"
                        class="inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                    >
                        <!-- Status Icon -->
                        <template x-if="r.status === 'menunggu'">
                            <svg class="h-3.5 w-3.5 text-sky-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                            </svg>
                        </template>
                        <template x-if="r.status === 'disetujui'">
                            <svg class="h-3.5 w-3.5 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                            </svg>
                        </template>
                        <template x-if="r.status === 'ditolak'">
                            <svg class="h-3.5 w-3.5 text-[#b02a37]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                            </svg>
                        </template>

                        <span x-text="`${r.id} · ${(getDataset(r.datasetId)?.title || r.datasetId)} · ${r.status === 'menunggu' ? 'Menunggu konfirmasi' : (r.status === 'disetujui' ? 'Disetujui' : 'Ditolak')}`"></span>
                    </button>
                </template>
            </div>
        </div>
    </template>

    <!-- Main Layout: 2 Kolom (Desktop: 240px + flex-1) -->
    <div class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-[240px_1fr]">
        <!-- Aside Kategori (Desktop) -->
        <aside class="hidden space-y-1 lg:block">
            <p class="mb-2 text-xs font-bold tracking-wider text-slate-500 uppercase">
                Kategori
            </p>
            <button
                type="button"
                @click="setKategori('all')"
                :class="kategori === 'all' ? 'bg-[#e1eaf9]/80 font-bold text-sky-800' : 'text-slate-600 hover:bg-slate-100'"
                class="flex w-full items-center justify-between rounded-xl px-3.5 py-2.5 text-left text-sm transition"
            >
                <span>Semua kategori</span>
                <span class="text-xs font-medium text-slate-400" x-text="allDatasets.length"></span>
            </button>
            <template x-for="cat in categories" :key="cat.code">
                <button
                    type="button"
                    @click="setKategori(cat.code)"
                    :class="kategori === cat.code ? 'bg-[#e1eaf9]/80 font-bold text-sky-800' : 'text-slate-600 hover:bg-slate-100'"
                    class="flex w-full items-center justify-between rounded-xl px-3.5 py-2.5 text-left text-sm transition"
                >
                    <span x-text="cat.name"></span>
                    <span class="text-xs font-medium text-slate-400" x-text="cat.count"></span>
                </button>
            </template>
        </aside>

        <!-- Section Konten Tabel & Filter -->
        <section class="min-w-0">
            <!-- Search Bar & Access Toggle -->
            <div class="flex flex-col gap-3 sm:flex-row">
                <!-- Search Keyword -->
                <label class="flex flex-1 items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 focus-within:border-sky-400 focus-within:ring-4 focus-within:ring-sky-100 transition shadow-sm">
                    <svg class="h-4 w-4 text-slate-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    <input
                        type="text"
                        x-model="q"
                        @input="page = 1"
                        placeholder="Cari kata kunci: judul, deskripsi, atau kolom…"
                        class="h-11 flex-1 bg-transparent text-sm text-slate-900 outline-none placeholder:text-slate-400"
                    />
                    <button type="button" x-show="q" @click="q = ''; page = 1" class="text-slate-400 hover:text-slate-600">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </label>

                <!-- Segmented Access Toggle -->
                <div class="flex rounded-xl border border-slate-200 bg-white p-1 text-sm shadow-sm shrink-0">
                    <button
                        type="button"
                        @click="setAccess('all')"
                        :class="access === 'all' ? 'bg-sky-600 text-white font-medium shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                        class="flex-1 sm:flex-none rounded-lg px-3.5 py-1.5 transition font-medium text-center"
                    >
                        Semua
                    </button>
                    <button
                        type="button"
                        @click="setAccess('public')"
                        :class="access === 'public' ? 'bg-sky-600 text-white font-medium shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                        class="flex-1 sm:flex-none rounded-lg px-3.5 py-1.5 transition font-medium text-center"
                    >
                        Publik
                    </button>
                    <button
                        type="button"
                        @click="setAccess('private')"
                        :class="access === 'private' ? 'bg-sky-600 text-white font-medium shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                        class="flex-1 sm:flex-none rounded-lg px-3.5 py-1.5 transition font-medium text-center"
                    >
                        Private
                    </button>
                </div>
            </div>

            <!-- Toolbar Dropdowns -->
            <div class="mt-3 flex flex-wrap items-center gap-2 rounded-2xl border border-slate-200 bg-surface p-3">
                <!-- Dropdown Kategori (Tampil hanya di layar kecil / mobile) -->
                <select
                    x-model="kategori"
                    @change="page = 1"
                    aria-label="Kategori"
                    class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100 lg:hidden"
                >
                    <option value="all">Semua kategori</option>
                    <template x-for="cat in categories" :key="cat.code">
                        <option :value="cat.code" x-text="cat.name"></option>
                    </template>
                </select>

                <!-- Dropdown Tahun -->
                <select
                    x-model="tahun"
                    @change="page = 1"
                    aria-label="Tahun"
                    class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                >
                    <option value="all">Semua tahun</option>
                    <template x-for="y in years" :key="y">
                        <option :value="String(y)" x-text="y"></option>
                    </template>
                </select>

                <!-- Dropdown Semester / Periode -->
                <select
                    x-model="periode"
                    @change="page = 1"
                    aria-label="Semester"
                    class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                >
                    <option value="all">Semua semester</option>
                    <option value="Ganjil">Ganjil</option>
                    <option value="Genap">Genap</option>
                </select>

                <!-- Dropdown Format -->
                <select
                    x-model="format"
                    @change="page = 1"
                    aria-label="Format"
                    class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                >
                    <option value="all">Semua format</option>
                    <option value="CSV">CSV</option>
                    <option value="XLSX">XLSX</option>
                </select>

                <span class="mx-1 hidden h-6 w-px bg-slate-200 sm:block"></span>

                <span class="text-xs font-bold tracking-wider text-slate-500 uppercase">
                    Kelompokkan
                </span>

                <!-- Dropdown Grouping -->
                <select
                    x-model="group"
                    @change="page = 1"
                    class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                >
                    <option value="kategori">Kategori</option>
                    <option value="tahun">Tahun</option>
                    <option value="kategori-tahun">Kategori & tahun</option>
                    <option value="none">Tanpa pengelompokan</option>
                </select>

                <!-- Reset Button -->
                <button
                    type="button"
                    @click="resetFilters()"
                    class="ml-auto text-sm font-semibold text-sky-600 hover:text-sky-700 transition"
                >
                    Reset
                </button>
            </div>

            <!-- Counter Hasil -->
            <p class="mt-4 text-sm text-slate-500">
                <span class="font-semibold text-slate-800" x-text="filteredDatasets.length"></span> dataset ditemukan
            </p>

            <!-- Tabel Dataset Utama -->
            <div x-show="filteredDatasets.length > 0" class="mt-3 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px] border-collapse text-left">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider whitespace-nowrap text-slate-500 uppercase">Dataset</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider whitespace-nowrap text-slate-500 uppercase">Periode</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider whitespace-nowrap text-slate-500 uppercase">Format</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider whitespace-nowrap text-slate-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold tracking-wider whitespace-nowrap text-slate-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="r in tableRows" :key="r._rowKey">
                                <tr :class="r._isHeader ? 'border-t border-slate-100 bg-sky-50/60' : 'border-t border-slate-100 align-top hover:bg-slate-50/60 transition'">
                                    <!-- Header Group Cell -->
                                    <template x-if="r._isHeader">
                                        <td colspan="5" class="px-4 py-2.5 text-xs font-bold text-sky-900 bg-sky-50/60">
                                            <span x-text="r.groupTitle"></span>
                                            <span class="ml-1 font-medium text-sky-600/80" x-text="'· ' + r.groupCount + ' dataset'"></span>
                                        </td>
                                    </template>

                                    <!-- Data Row Cells: Dataset info -->
                                    <template x-if="!r._isHeader">
                                        <td class="px-4 py-3 text-sm text-slate-600 max-w-[360px]">
                                            <p class="font-bold text-slate-900" x-text="r.dataset.title"></p>
                                            <p class="mt-0.5 line-clamp-2 text-[13px] text-slate-500" x-text="r.dataset.desc"></p>
                                            <p class="mt-1 text-xs text-slate-400">
                                                <span x-text="getCategoryName(r.dataset.kategori)"></span> · 
                                                <span x-text="`${r.dataset.rows.length} baris`"></span> · 
                                                <span>diperbarui </span><span x-text="r.dataset.updated"></span>
                                            </p>
                                        </td>
                                    </template>

                                    <!-- Periode -->
                                    <template x-if="!r._isHeader">
                                        <td class="px-4 py-3 text-sm text-slate-600 whitespace-nowrap">
                                            <span class="font-semibold text-slate-800" x-text="r.dataset.tahun"></span>
                                            <p class="text-xs text-slate-400" x-text="`Semester ${r.dataset.periode}`"></p>
                                        </td>
                                    </template>

                                    <!-- Format -->
                                    <template x-if="!r._isHeader">
                                        <td class="px-4 py-3 text-sm text-slate-600 whitespace-nowrap">
                                            <span class="rounded-md bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600" x-text="r.dataset.format"></span>
                                        </td>
                                    </template>

                                    <!-- Status -->
                                    <template x-if="!r._isHeader">
                                        <td class="px-4 py-3 text-sm text-slate-600 whitespace-nowrap">
                                            <template x-if="r.dataset.access === 'public'">
                                                <span class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 text-[11px] font-bold text-emerald-700">
                                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                                                    Publik
                                                </span>
                                            </template>
                                            <template x-if="r.dataset.access === 'private'">
                                                <span class="inline-flex items-center gap-1 rounded-md bg-amber-50 px-2 py-0.5 text-[11px] font-bold text-amber-700">
                                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                                    Private
                                                </span>
                                            </template>
                                            <template x-if="r.dataset.access === 'private' && getMyRequest(r.dataset)">
                                                <p class="mt-1 text-[11px] font-semibold"
                                                   :class="{
                                                       'text-emerald-600': getMyRequest(r.dataset).status === 'disetujui',
                                                       'text-[#DC3545]': getMyRequest(r.dataset).status === 'ditolak',
                                                       'text-sky-600': getMyRequest(r.dataset).status === 'menunggu'
                                                   }"
                                                   x-text="getMyRequest(r.dataset).status === 'disetujui' ? 'Akses diberikan' : (getMyRequest(r.dataset).status === 'ditolak' ? 'Permohonan ditolak' : 'Menunggu konfirmasi')">
                                                </p>
                                            </template>
                                        </td>
                                    </template>

                                    <!-- Aksi -->
                                    <template x-if="!r._isHeader">
                                        <td class="px-4 py-3 text-sm text-slate-600 text-right whitespace-nowrap">
                                            <template x-if="canUse(r.dataset)">
                                                <div class="flex items-center justify-end gap-1.5">
                                                    <button type="button"
                                                            @click="openPreview(r.dataset)"
                                                            class="inline-flex items-center justify-center h-9 w-9 shrink-0 rounded-xl border border-slate-200 bg-white text-slate-600 hover:border-sky-300 hover:text-sky-700 transition"
                                                            title="Lihat data">
                                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                                    </button>
                                                    <button type="button"
                                                            @click="downloadCsv(r.dataset.title, r.dataset.cols, r.dataset.rows)"
                                                            class="inline-flex items-center justify-center gap-1 h-9 px-2.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-600 hover:border-sky-300 hover:text-sky-700 transition"
                                                            title="Unduh CSV">
                                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                                        CSV
                                                    </button>
                                                    <button type="button"
                                                            @click="downloadXlsx(r.dataset.title, r.dataset.cols, r.dataset.rows)"
                                                            class="inline-flex items-center justify-center gap-1 h-9 px-2.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-600 hover:border-sky-300 hover:text-sky-700 transition"
                                                            title="Unduh XLSX">
                                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><path d="M8 13h2"/><path d="M8 17h2"/><path d="M14 13h2"/><path d="M14 17h2"/></svg>
                                                        XLSX
                                                    </button>
                                                </div>
                                            </template>
                                            <template x-if="!canUse(r.dataset)">
                                                <button type="button"
                                                        @click="handlePrivateAction(r.dataset)"
                                                        class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-300 bg-white px-4 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 shadow-sm transition">
                                                    <span x-text="getMyRequest(r.dataset)?.status === 'menunggu' ? 'Lihat status' : 'Ajukan permohonan'"></span>
                                                </button>
                                            </template>
                                        </td>
                                    </template>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Pager Pagination -->
                <div x-show="filteredDatasets.length > pageSize" class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-4 py-3 text-sm text-slate-500">
                    <span>
                        <span x-text="(page - 1) * pageSize + 1"></span>–<span x-text="Math.min(page * pageSize, filteredDatasets.length)"></span> dari <span x-text="filteredDatasets.length"></span>
                    </span>
                    <div class="flex items-center gap-1.5">
                        <button
                            type="button"
                            :disabled="page <= 1"
                            @click="page--"
                            class="inline-flex items-center justify-center h-9 w-9 shrink-0 rounded-xl border border-slate-200 bg-white text-slate-600 hover:border-sky-300 hover:text-sky-700 disabled:opacity-40 disabled:cursor-not-allowed transition"
                            aria-label="Sebelumnya"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                        </button>
                        <span class="px-2 font-semibold text-slate-700" x-text="`${page} / ${totalPages}`"></span>
                        <button
                            type="button"
                            :disabled="page >= totalPages"
                            @click="page++"
                            class="inline-flex items-center justify-center h-9 w-9 shrink-0 rounded-xl border border-slate-200 bg-white text-slate-600 hover:border-sky-300 hover:text-sky-700 disabled:opacity-40 disabled:cursor-not-allowed transition"
                            aria-label="Berikutnya"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Empty State jika tidak ada dataset yang cocok -->
            <div x-show="filteredDatasets.length === 0" class="mt-3 rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center">
                <span class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-slate-100 text-slate-400">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"/><path d="M2 10h20"/></svg>
                </span>
                <p class="mt-4 font-bold text-slate-800">Tidak ada dataset yang cocok.</p>
                <p class="mt-1 text-sm text-slate-500">Ubah kata kunci atau filter pencarian.</p>
            </div>
        </section>
    </div>

    <!-- MODAL 1: PREVIEW DATASET -->
    <template x-if="activeModal === 'preview' && selectedDataset">
        <div class="fixed inset-0 z-50 grid place-items-end bg-slate-900/40 backdrop-blur-sm sm:place-items-center sm:p-6" @click.self="closeModal()">
            <div class="flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-t-2xl bg-white shadow-2xl sm:rounded-2xl animate-fade-up">
                <!-- Modal Head -->
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 pt-5 pb-4 sm:px-8 sm:pt-6">
                    <div class="min-w-0">
                        <p class="flex flex-wrap items-center gap-2 text-sm text-slate-500">
                            <span>Dataset › <span x-text="getCategoryName(selectedDataset.kategori)"></span></span>
                            <template x-if="selectedDataset.access === 'public'">
                                <span class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 text-[11px] font-bold text-emerald-700">
                                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                                    Publik
                                </span>
                            </template>
                            <template x-if="selectedDataset.access === 'private'">
                                <span class="inline-flex items-center gap-1 rounded-md bg-amber-50 px-2 py-0.5 text-[11px] font-bold text-amber-700">
                                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                    Private
                                </span>
                            </template>
                        </p>
                        <h2 class="mt-1 text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl" x-text="selectedDataset.title"></h2>
                        <p class="mt-1 text-sm text-slate-500" x-text="selectedDataset.desc"></p>
                    </div>
                    <button type="button" @click="closeModal()" aria-label="Tutup" class="inline-flex items-center justify-center h-9 w-9 shrink-0 rounded-xl border border-slate-200 bg-white text-slate-600 hover:border-sky-300 hover:text-sky-700 transition">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>

                <!-- Modal Body: Table Preview -->
                <div class="overflow-auto px-5 py-5 sm:px-8">
                    <div class="overflow-hidden rounded-xl border border-slate-200">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <template x-for="c in selectedDataset.cols" :key="c">
                                            <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-slate-500 uppercase" x-text="c"></th>
                                        </template>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(row, idx) in previewSlice" :key="idx">
                                        <tr class="border-t border-slate-100 hover:bg-sky-50/50 transition">
                                            <template x-for="(val, cIdx) in row" :key="cIdx">
                                                <td class="px-4 py-3 text-sm text-slate-600 tabular-nums whitespace-nowrap" x-text="val"></td>
                                            </template>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <!-- Preview Table Pager -->
                        <div x-show="selectedDataset.rows.length > 10" class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-4 py-3 text-sm text-slate-500">
                            <span>
                                <span x-text="(previewPage - 1) * 10 + 1"></span>–<span x-text="Math.min(previewPage * 10, selectedDataset.rows.length)"></span> dari <span x-text="selectedDataset.rows.length"></span>
                            </span>
                            <div class="flex items-center gap-1.5">
                                <button
                                    type="button"
                                    :disabled="previewPage <= 1"
                                    @click="previewPage--"
                                    class="inline-flex items-center justify-center h-9 w-9 shrink-0 rounded-xl border border-slate-200 bg-white text-slate-600 hover:border-sky-300 hover:text-sky-700 disabled:opacity-40 disabled:cursor-not-allowed transition"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                                </button>
                                <span class="px-2 font-semibold text-slate-700" x-text="`${previewPage} / ${Math.ceil(selectedDataset.rows.length / 10)}`"></span>
                                <button
                                    type="button"
                                    :disabled="previewPage >= Math.ceil(selectedDataset.rows.length / 10)"
                                    @click="previewPage++"
                                    class="inline-flex items-center justify-center h-9 w-9 shrink-0 rounded-xl border border-slate-200 bg-white text-slate-600 hover:border-sky-300 hover:text-sky-700 disabled:opacity-40 disabled:cursor-not-allowed transition"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Foot -->
                <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:px-8">
                    <p class="mr-auto text-sm text-slate-500">
                        <span x-text="selectedDataset.id"></span> · 
                        <span x-text="`${selectedDataset.rows.length} baris`"></span> · 
                        <span>diperbarui </span><span x-text="selectedDataset.updated"></span>
                    </p>
                    <div class="flex gap-1.5">
                        <button
                            type="button"
                            @click="downloadCsv(selectedDataset.title, selectedDataset.cols, selectedDataset.rows)"
                            class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                            CSV
                        </button>
                        <button
                            type="button"
                            @click="downloadXlsx(selectedDataset.title, selectedDataset.cols, selectedDataset.rows)"
                            class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-[#0084D1] px-4 py-2.5 text-sm font-semibold text-white hover:bg-sky-700 transition"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><path d="M8 13h2"/><path d="M8 17h2"/><path d="M14 13h2"/><path d="M14 17h2"/></svg>
                            XLSX
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <!-- MODAL 2: REQUEST FORM DATASET PRIVATE -->
    <template x-if="activeModal === 'form' && selectedDataset">
        <div class="fixed inset-0 z-50 grid place-items-end bg-slate-900/40 backdrop-blur-sm sm:place-items-center sm:p-6" @click.self="closeModal()">
            <div class="flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-t-2xl bg-white shadow-2xl sm:rounded-2xl animate-fade-up">
                <!-- Modal Head -->
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 pt-5 pb-4 sm:px-8 sm:pt-6">
                    <div class="min-w-0">
                        <p class="flex flex-wrap items-center gap-2 text-sm text-slate-500">
                            <span>Permohonan data</span>
                            <span class="inline-flex items-center gap-1 rounded-md bg-amber-50 px-2 py-0.5 text-[11px] font-bold text-amber-700">
                                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                Private
                            </span>
                        </p>
                        <h2 class="mt-1 text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl" x-text="selectedDataset.title"></h2>
                        <p class="mt-1 text-sm text-slate-500" x-text="selectedDataset.desc"></p>
                    </div>
                    <button type="button" @click="closeModal()" aria-label="Tutup" class="inline-flex items-center justify-center h-9 w-9 shrink-0 rounded-xl border border-slate-200 bg-white text-slate-600 hover:border-sky-300 hover:text-sky-700 transition">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>

                <!-- Jika Permohonan Terkirim Sukses -->
                <template x-if="requestSentId">
                    <div class="px-8 py-12 text-center">
                        <div class="mx-auto grid h-14 w-14 place-items-center rounded-full bg-sky-50 text-sky-600">
                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        </div>
                        <h3 class="mt-4 text-xl font-bold text-slate-900">Permohonan terkirim</h3>
                        <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">
                            Nomor permohonan <b class="text-slate-800" x-text="requestSentId"></b>. Permohonan akan diperiksa oleh Wali Data. Setelah dikonfirmasi, dataset dapat dilihat dan diunduh dari halaman ini, dan pemberitahuan dikirim ke <b class="text-slate-800" x-text="form.email"></b>.
                        </p>
                        <button type="button" @click="closeModal()" class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#0084D1] px-5 py-2.5 text-sm font-semibold text-white hover:bg-sky-700 mt-6 transition">
                            Kembali ke dataset
                        </button>
                    </div>
                </template>

                <!-- Form Permohonan Data -->
                <template x-if="!requestSentId">
                    <form @submit.prevent="submitRequest()" class="flex min-h-0 flex-col">
                        <div class="space-y-5 overflow-y-auto px-5 py-6 sm:px-8">
                            <!-- Alert Box -->
                            <div class="flex gap-3 rounded-xl px-4 py-3 text-sm bg-amber-50 text-amber-800">
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-amber-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                <div>
                                    Dataset ini berstatus <b>private</b>. Akses diberikan setelah permohonan dikonfirmasi oleh Wali Data. Kolom bertanda <span class="text-[#DC3545] font-bold">*</span> wajib diisi.
                                </div>
                            </div>

                            <!-- Field Nama Lengkap -->
                            <label class="block">
                                <span class="mb-1.5 block text-sm font-semibold text-slate-800">
                                    Nama lengkap <span class="text-[#DC3545]">*</span>
                                </span>
                                <input
                                    type="text"
                                    x-model="form.nama"
                                    placeholder="Nama sesuai identitas"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                                />
                                <span x-show="formErrors.nama" class="mt-1 block text-xs text-[#DC3545]" x-text="formErrors.nama"></span>
                            </label>

                            <!-- Field Email & HP -->
                            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                <label class="block">
                                    <span class="mb-1.5 block text-sm font-semibold text-slate-800">
                                        Email <span class="text-[#DC3545]">*</span>
                                    </span>
                                    <input
                                        type="email"
                                        x-model="form.email"
                                        placeholder="nama@email.com"
                                        class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                                    />
                                    <span x-show="formErrors.email" class="mt-1 block text-xs text-[#DC3545]" x-text="formErrors.email"></span>
                                    <span x-show="!formErrors.email" class="mt-1 block text-xs text-slate-500">Pemberitahuan dikirim ke email ini.</span>
                                </label>
                                <label class="block">
                                    <span class="mb-1.5 block text-sm font-semibold text-slate-800">
                                        Nomor HP <span class="text-[#DC3545]">*</span>
                                    </span>
                                    <input
                                        type="tel"
                                        x-model="form.hp"
                                        placeholder="08xxxxxxxxxx"
                                        class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                                    />
                                    <span x-show="formErrors.hp" class="mt-1 block text-xs text-[#DC3545]" x-text="formErrors.hp"></span>
                                </label>
                            </div>

                            <!-- Field NIP/NIM/NIK & Tujuan -->
                            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                <label class="block">
                                    <span class="mb-1.5 block text-sm font-semibold text-slate-800">
                                        NIP/NIM/NIK <span class="text-[#DC3545]">*</span>
                                    </span>
                                    <input
                                        type="text"
                                        x-model="form.nim"
                                        placeholder="Nomor induk"
                                        class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                                    />
                                    <span x-show="formErrors.nim" class="mt-1 block text-xs text-[#DC3545]" x-text="formErrors.nim"></span>
                                </label>
                                <label class="block">
                                    <span class="mb-1.5 block text-sm font-semibold text-slate-800">
                                        Tujuan penggunaan data <span class="text-[#DC3545]">*</span>
                                    </span>
                                    <select
                                        x-model="form.tujuan"
                                        class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                                    >
                                        <option value="">Pilih tujuan…</option>
                                        <option value="Penelitian / tugas akhir">Penelitian / tugas akhir</option>
                                        <option value="Akreditasi & penjaminan mutu">Akreditasi & penjaminan mutu</option>
                                        <option value="Pelaporan instansi">Pelaporan instansi</option>
                                        <option value="Publikasi / jurnalistik">Publikasi / jurnalistik</option>
                                        <option value="Perencanaan program">Perencanaan program</option>
                                        <option value="Lainnya">Lainnya</option>
                                    </select>
                                    <span x-show="formErrors.tujuan" class="mt-1 block text-xs text-[#DC3545]" x-text="formErrors.tujuan"></span>
                                </label>
                            </div>

                            <!-- Field Deskripsi Kebutuhan Data -->
                            <label class="block">
                                <span class="mb-1.5 block text-sm font-semibold text-slate-800">
                                    Deskripsikan kebutuhan data Anda <span class="text-[#DC3545]">*</span>
                                </span>
                                <textarea
                                    rows="4"
                                    x-model="form.deskripsi"
                                    placeholder="Contoh: data penerima KIP angkatan 2022–2024 untuk analisis retensi…"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                                ></textarea>
                                <span x-show="formErrors.deskripsi" class="mt-1 block text-xs text-[#DC3545]" x-text="formErrors.deskripsi"></span>
                                <span x-show="!formErrors.deskripsi" class="mt-1 block text-xs text-slate-500">Jelaskan bentuk dan cakupan data yang diinginkan.</span>
                            </label>
                        </div>

                        <!-- Modal Foot -->
                        <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:px-8">
                            <button
                                type="button"
                                @click="resetForm()"
                                class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition"
                            >
                                Reset
                            </button>
                            <button
                                type="submit"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#0084D1] px-5 py-2.5 text-sm font-semibold text-white hover:bg-sky-700 transition"
                            >
                                Kirim permohonan
                            </button>
                        </div>
                    </form>
                </template>
            </div>
        </div>
    </template>

    <!-- MODAL 3: STATUS INFO PERMOHONAN -->
    <template x-if="activeModal === 'status' && selectedRequest && selectedDataset">
        <div class="fixed inset-0 z-50 grid place-items-end bg-slate-900/40 backdrop-blur-sm sm:place-items-center sm:p-6" @click.self="closeModal()">
            <div class="flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-t-2xl bg-white shadow-2xl sm:rounded-2xl animate-fade-up">
                <!-- Modal Head -->
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 pt-5 pb-4 sm:px-8 sm:pt-6">
                    <div class="min-w-0">
                        <p class="text-sm text-slate-500">
                            Status permohonan · <span class="font-bold text-slate-700" x-text="selectedRequest.id"></span>
                        </p>
                        <h2 class="mt-1 text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl" x-text="selectedDataset.title"></h2>
                    </div>
                    <button type="button" @click="closeModal()" aria-label="Tutup" class="inline-flex items-center justify-center h-9 w-9 shrink-0 rounded-xl border border-slate-200 bg-white text-slate-600 hover:border-sky-300 hover:text-sky-700 transition">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="space-y-4 px-5 py-6 sm:px-8">
                    <!-- Menunggu -->
                    <template x-if="selectedRequest.status === 'menunggu'">
                        <div class="flex gap-3 rounded-xl px-4 py-3 text-sm bg-sky-50 text-sky-800">
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-sky-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                            <div>
                                Permohonan Anda tertanggal <b x-text="formatDate(selectedRequest.at)"></b> sedang menunggu konfirmasi Wali Data.
                            </div>
                        </div>
                    </template>

                    <!-- Ditolak -->
                    <template x-if="selectedRequest.status === 'ditolak'">
                        <div class="flex gap-3 rounded-xl px-4 py-3 text-sm bg-red-50 text-[#b02a37]">
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-[#b02a37]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                            <div>
                                Permohonan ditolak oleh Wali Data<span x-text="selectedRequest.note ? `: “${selectedRequest.note}”` : '.'"></span> Anda dapat mengajukan permohonan baru.
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Modal Foot -->
                <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:px-8">
                    <button
                        type="button"
                        @click="closeModal()"
                        class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition"
                    >
                        Tutup
                    </button>
                    <template x-if="selectedRequest.status === 'ditolak'">
                        <button
                            type="button"
                            @click="openRequestForm(selectedDataset)"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#0084D1] px-5 py-2.5 text-sm font-semibold text-white hover:bg-sky-700 transition"
                        >
                            Ajukan baru
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </template>
</main>

@push('scripts')
<script>
function datasetManager() {
    return {
        allDatasets: @json($allDatasets),
        categories: @json($categories),
        years: @json($years),
        myRequests: @json($requests),
        
        // Filter states
        kategori: '{{ $kategori }}',
        access: '{{ $access }}',
        tahun: '{{ $tahun }}',
        periode: '{{ $periode }}',
        format: '{{ $format }}',
        group: '{{ $group }}',
        q: '{{ $q }}',

        // Pagination
        page: 1,
        pageSize: 8,

        // Modal states: 'preview' | 'form' | 'status' | null
        activeModal: null,
        selectedDataset: null,
        selectedRequest: null,
        previewPage: 1,

        // Form state
        form: {
            nama: '',
            email: '',
            hp: '',
            nim: '',
            tujuan: '',
            deskripsi: ''
        },
        formErrors: {},
        requestSentId: null,

        init() {
            // Watch escape key
            window.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') this.closeModal();
            });
        },

        setKategori(catCode) {
            this.kategori = catCode;
            this.page = 1;
        },

        setAccess(acc) {
            this.access = acc;
            this.page = 1;
        },

        resetFilters() {
            this.kategori = 'all';
            this.access = 'all';
            this.tahun = 'all';
            this.periode = 'all';
            this.format = 'all';
            this.group = 'kategori';
            this.q = '';
            this.page = 1;
        },

        getCategoryName(code) {
            const found = this.categories.find(c => c.code === code);
            return found ? found.name : code;
        },

        getDataset(id) {
            return this.allDatasets.find(d => d.id === id);
        },

        getMyRequest(dataset) {
            const reqs = this.myRequests.filter(r => r.datasetId === dataset.id);
            if (!reqs.length) return null;
            return [...reqs].sort((a, b) => b.at.localeCompare(a.at))[0];
        },

        canUse(dataset) {
            if (dataset.access === 'public') return true;
            const req = this.getMyRequest(dataset);
            return req && req.status === 'disetujui';
        },

        getKeyOf(dataset) {
            if (this.group === 'kategori') return this.getCategoryName(dataset.kategori);
            if (this.group === 'tahun') return `Tahun ${dataset.tahun}`;
            if (this.group === 'kategori-tahun') return `${this.getCategoryName(dataset.kategori)} · ${dataset.tahun}`;
            return '';
        },

        getGroupCount(key) {
            return this.sortedDatasets.filter(d => this.getKeyOf(d) === key).length;
        },

        shouldShowGroupHeader(index) {
            if (this.group === 'none') return false;
            if (index === 0) return true;
            const currentKey = this.getKeyOf(this.paginatedDatasets[index]);
            const prevKey = this.getKeyOf(this.paginatedDatasets[index - 1]);
            return currentKey !== prevKey;
        },

        get filteredDatasets() {
            const kw = this.q.trim().toLowerCase();
            return this.allDatasets.filter(d => {
                const matchCat = (this.kategori === 'all' || d.kategori === this.kategori);
                const matchAcc = (this.access === 'all' || d.access === this.access);
                const matchYr = (this.tahun === 'all' || String(d.tahun) === this.tahun);
                const matchPer = (this.periode === 'all' || d.periode === this.periode);
                const matchFmt = (this.format === 'all' || d.format === this.format);
                const matchKw = (!kw || `${d.title} ${d.desc} ${this.getCategoryName(d.kategori)} ${d.cols.join(' ')}`.toLowerCase().includes(kw));

                return matchCat && matchAcc && matchYr && matchPer && matchFmt && matchKw;
            });
        },

        get sortedDatasets() {
            const list = [...this.filteredDatasets];
            return list.sort((a, b) => {
                if (this.group === 'kategori' || this.group === 'kategori-tahun') {
                    const comp = this.getKeyOf(a).localeCompare(this.getKeyOf(b));
                    if (comp !== 0) return comp;
                }
                const yrDiff = b.tahun - a.tahun;
                if (yrDiff !== 0) return yrDiff;
                if (a.periode === b.periode) return 0;
                return a.periode === 'Genap' ? -1 : 1;
            });
        },

        get totalPages() {
            return Math.max(1, Math.ceil(this.sortedDatasets.length / this.pageSize));
        },

        get paginatedDatasets() {
            const cur = Math.min(this.page, this.totalPages);
            const start = (cur - 1) * this.pageSize;
            return this.sortedDatasets.slice(start, start + this.pageSize);
        },

        get tableRows() {
            const rows = [];
            const slice = this.paginatedDatasets;
            for (let i = 0; i < slice.length; i++) {
                const d = slice[i];
                const k = this.getKeyOf(d);
                const showHead = (this.group !== 'none') && (i === 0 || this.getKeyOf(slice[i - 1]) !== k);
                if (showHead) {
                    rows.push({
                        _isHeader: true,
                        _rowKey: 'header_' + k + '_' + i,
                        groupTitle: k,
                        groupCount: this.getGroupCount(k)
                    });
                }
                rows.push({
                    _isHeader: false,
                    _rowKey: 'data_' + d.id,
                    dataset: d
                });
            }
            return rows;
        },

        // Preview table slice
        get previewSlice() {
            if (!this.selectedDataset) return [];
            const start = (this.previewPage - 1) * 10;
            return this.selectedDataset.rows.slice(start, start + 10);
        },

        // Modal triggers
        openPreview(dataset) {
            this.selectedDataset = dataset;
            this.previewPage = 1;
            this.activeModal = 'preview';
            document.body.style.overflow = 'hidden';
        },

        openRequestForm(dataset) {
            this.selectedDataset = dataset;
            this.resetForm();
            this.requestSentId = null;
            this.activeModal = 'form';
            document.body.style.overflow = 'hidden';
        },

        openStatusInfo(request, dataset) {
            this.selectedRequest = request;
            this.selectedDataset = dataset;
            this.activeModal = 'status';
            document.body.style.overflow = 'hidden';
        },

        closeModal() {
            this.activeModal = null;
            this.selectedDataset = null;
            this.selectedRequest = null;
            this.requestSentId = null;
            document.body.style.overflow = '';
        },

        handlePrivateAction(dataset) {
            const req = this.getMyRequest(dataset);
            if (!req) {
                this.openRequestForm(dataset);
            } else if (req.status === 'menunggu') {
                this.openStatusInfo(req, dataset);
            } else if (req.status === 'ditolak') {
                this.openStatusInfo(req, dataset);
            } else {
                this.openPreview(dataset);
            }
        },

        handleRequestPillClick(request) {
            const d = this.getDataset(request.datasetId);
            if (!d) return;
            if (request.status === 'disetujui') {
                this.openPreview(d);
            } else {
                this.openStatusInfo(request, d);
            }
        },

        resetForm() {
            this.form = {
                nama: '',
                email: '',
                hp: '',
                nim: '',
                tujuan: '',
                deskripsi: ''
            };
            this.formErrors = {};
        },

        validateForm() {
            this.formErrors = {};
            if (!this.form.nama.trim()) this.formErrors.nama = 'Kolom ini wajib diisi.';
            if (!this.form.email.trim()) {
                this.formErrors.email = 'Kolom ini wajib diisi.';
            } else if (!/^\S+@\S+\.\S+$/.test(this.form.email)) {
                this.formErrors.email = 'Format email tidak valid.';
            }
            if (!this.form.hp.trim()) {
                this.formErrors.hp = 'Kolom ini wajib diisi.';
            } else if (!/^0\d{9,13}$/.test(this.form.hp)) {
                this.formErrors.hp = 'Nomor HP diawali 0, 10–14 digit.';
            }
            if (!this.form.nim.trim()) this.formErrors.nim = 'Kolom ini wajib diisi.';
            if (!this.form.tujuan) this.formErrors.tujuan = 'Kolom ini wajib diisi.';
            if (!this.form.deskripsi.trim()) this.formErrors.deskripsi = 'Kolom ini wajib diisi.';

            return Object.keys(this.formErrors).length === 0;
        },

        submitRequest() {
            if (!this.validateForm()) return;
            const newId = 'R-' + String(this.myRequests.length + 1).padStart(2, '0');
            const newReq = {
                id: newId,
                datasetId: this.selectedDataset.id,
                ...this.form,
                at: new Date().toISOString(),
                status: 'menunggu'
            };
            this.myRequests.unshift(newReq);
            this.requestSentId = newId;
        },

        formatDate(isoStr) {
            if (!isoStr) return '';
            try {
                const date = new Date(isoStr);
                return date.toLocaleDateString('id-ID', {
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric'
                });
            } catch (e) {
                return isoStr;
            }
        },

        // File download handlers
        downloadCsv(title, cols, rows) {
            const esc = v => {
                const x = String(v ?? '');
                return /[",\n;]/.test(x) ? `"${x.replace(/"/g, '""')}"` : x;
            };
            const csv = [cols, ...rows].map(r => r.map(esc).join(',')).join('\r\n');
            const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8' });
            this.triggerSave(blob, `${title}.csv`);
        },

        downloadXlsx(title, cols, rows) {
            // HTML Excel format standard for browser export
            let tableHtml = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
            tableHtml += '<head><meta charset="utf-8"></head><body><table>';
            tableHtml += '<thead><tr>' + cols.map(c => `<th style="background-color:#f1f5f9;font-weight:bold;border:1px solid #cbd5e1;padding:6px 10px;">${c}</th>`).join('') + '</tr></thead>';
            tableHtml += '<tbody>';
            rows.forEach(r => {
                tableHtml += '<tr>' + r.map(v => `<td style="border:1px solid #e2e8f0;padding:6px 10px;">${v}</td>`).join('') + '</tr>';
            });
            tableHtml += '</tbody></table></body></html>';
            const blob = new Blob([tableHtml], { type: 'application/vnd.ms-excel;charset=utf-8' });
            this.triggerSave(blob, `${title}.xls`);
        },

        triggerSave(blob, filename) {
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = filename.replace(/[\\/:*?"<>|]+/g, '-');
            document.body.appendChild(a);
            a.click();
            a.remove();
            setTimeout(() => URL.revokeObjectURL(a.href), 1000);
        }
    };
}
</script>
@endpush
@endsection