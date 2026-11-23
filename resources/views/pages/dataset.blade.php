@extends('layouts.app')

@section('title', 'Dataset Sistem Informasi - Satu Data UNUSA')

@section('content')
<main class="mx-auto max-w-[1200px] px-4 py-10 sm:px-8" x-data="datasetManager()" x-cloak>
    <div class="relative overflow-hidden">
        <div data-scene="ambient"
             aria-hidden="true"
             class="pointer-events-none absolute -top-4 right-0 hidden h-36 w-80 opacity-85 lg:block"
             style="mask-image: radial-gradient(closest-side, #000 58%, transparent 98%); -webkit-mask-image: radial-gradient(closest-side, #000 58%, transparent 98%);"></div>
        <div class="relative z-10">
            <p class="text-sm text-slate-500">
                <a href="{{ route('beranda') }}" class="transition hover:text-sky-600">{{ $t('Beranda', 'Home') }}</a>
                <span class="mx-1">/</span>
                <span class="text-sky-600">Dataset</span>
            </p>

            <h1 class="mt-1 text-[28px] font-extrabold tracking-tight text-slate-900">
                Dataset Sistem Informasi
            </h1>
            <p class="mt-1 text-[15px] text-slate-500">
                {{ $t(
                    'Dataset publik dapat langsung dilihat dan diunduh. Dataset private memerlukan permohonan yang dikonfirmasi Wali Data.',
                    'Public datasets can be viewed and downloaded directly. Private datasets require a request approved by the Data Steward.'
                ) }}
            </p>
        </div>
    </div>

    <!-- Permohonan Saya -->
    <template x-if="myRequests.length > 0">
        <div class="sd-card mt-6 p-4">
            <p class="mb-3 text-xs font-bold tracking-wider text-slate-500 uppercase">
                {{ $t('Permohonan saya', 'My requests') }}
            </p>
            <div class="flex flex-wrap gap-2">
                <template x-for="r in myRequests" :key="r.id">
                    <button
                        type="button"
                        @click="handleRequestPillClick(r)"
                        :class="{
                            'bg-sky-100/80 text-sky-700': r.status === 'menunggu',
                            'bg-emerald-50 text-emerald-700': r.status === 'disetujui',
                            'bg-red-50 text-[#b02a37]': r.status === 'ditolak'
                        }"
                        class="inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                    >
                        <template x-if="r.status === 'menunggu'">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        </template>
                        <template x-if="r.status === 'disetujui'">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        </template>
                        <template x-if="r.status === 'ditolak'">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                        </template>
                        <span x-text="`${r.id} · ${(getDataset(r.datasetId)?.title || r.datasetId)} · ${r.status === 'menunggu' ? 'Menunggu konfirmasi' : (r.status === 'disetujui' ? 'Disetujui' : 'Ditolak')}`"></span>
                    </button>
                </template>
            </div>
        </div>
    </template>

    <div class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-[240px_1fr]">
        <aside class="hidden space-y-1 lg:block">
            <p class="mb-2 text-xs font-bold tracking-wider text-slate-500 uppercase">
                {{ $t('Kategori', 'Category') }}
            </p>
            <button
                type="button"
                @click="setKategori('all')"
                :class="kategori === 'all' ? 'bg-sky-50/90 font-bold text-brand-dark ring-1 ring-sky-200/80 shadow-2xs' : 'text-slate-600 hover:bg-white hover:text-slate-900'"
                class="flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-left text-sm transition"
            >
                <span>{{ $t('Semua kategori', 'All categories') }}</span>
                <span class="text-xs tabular-nums text-slate-400" x-text="allDatasets.length"></span>
            </button>
            <template x-for="cat in categories" :key="cat.code">
                <button
                    type="button"
                    @click="setKategori(cat.code)"
                    :class="kategori === cat.code ? 'bg-sky-50/90 font-bold text-brand-dark ring-1 ring-sky-200/80 shadow-2xs' : 'text-slate-600 hover:bg-white hover:text-slate-900'"
                    class="flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-left text-sm transition"
                >
                    <span x-text="cat.name"></span>
                    <span class="text-xs tabular-nums text-slate-400" x-text="cat.count"></span>
                </button>
            </template>
        </aside>

        <section class="min-w-0">
            <div class="flex flex-col gap-3 sm:flex-row">
                <label class="flex flex-1 items-center gap-3 rounded-xl border border-slate-200/90 bg-white px-4 shadow-2xs focus-within:border-sky-400 focus-within:ring-4 focus-within:ring-sky-100">
                    <svg class="h-4 w-4 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input
                        type="text"
                        x-model="q"
                        @input="page = 1"
                        placeholder="{{ $t('Cari kata kunci: judul, deskripsi, atau kolom…', 'Search keyword: title, description, or column…') }}"
                        class="h-11 flex-1 bg-transparent text-sm outline-none"
                    />
                </label>

                <div class="flex shrink-0 rounded-xl border border-slate-200/90 bg-white p-1 text-sm shadow-2xs">
                    <button type="button" @click="setAccess('all')" :class="access === 'all' ? 'bg-brand text-white shadow-xs' : 'text-slate-500 hover:text-slate-800'" class="flex-1 rounded-lg px-3.5 py-1.5 font-medium transition">{{ $t('Semua', 'All') }}</button>
                    <button type="button" @click="setAccess('public')" :class="access === 'public' ? 'bg-brand text-white shadow-xs' : 'text-slate-500 hover:text-slate-800'" class="flex-1 rounded-lg px-3.5 py-1.5 font-medium transition">{{ $t('Publik', 'Public') }}</button>
                    <button type="button" @click="setAccess('private')" :class="access === 'private' ? 'bg-brand text-white shadow-xs' : 'text-slate-500 hover:text-slate-800'" class="flex-1 rounded-lg px-3.5 py-1.5 font-medium transition">Private</button>
                </div>
            </div>

            <div class="sd-card mt-3 flex flex-wrap items-center gap-2 p-3">
                <select x-model="kategori" @change="page = 1" aria-label="Kategori" class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100 lg:hidden">
                    <option value="all">{{ $t('Semua kategori', 'All categories') }}</option>
                    <template x-for="cat in categories" :key="cat.code">
                        <option :value="cat.code" x-text="cat.name"></option>
                    </template>
                </select>

                <select x-model="tahun" @change="page = 1" aria-label="Tahun" class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                    <option value="all">{{ $t('Semua tahun', 'All years') }}</option>
                    <template x-for="y in years" :key="y">
                        <option :value="String(y)" x-text="y"></option>
                    </template>
                </select>

                <select x-model="periode" @change="page = 1" aria-label="Semester" class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                    <option value="all">{{ $t('Semua semester', 'All semesters') }}</option>
                    <option value="Ganjil">Ganjil</option>
                    <option value="Genap">Genap</option>
                </select>

                <select x-model="format" @change="page = 1" aria-label="Format" class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                    <option value="all">{{ $t('Semua format', 'All formats') }}</option>
                    <option value="CSV">CSV</option>
                    <option value="XLSX">XLSX</option>
                </select>

                <span class="mx-1 hidden h-6 w-px bg-slate-200 sm:block"></span>
                <span class="text-xs font-bold tracking-wider text-slate-500 uppercase">{{ $t('Kelompokkan', 'Group by') }}</span>

                <select x-model="group" @change="page = 1" class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                    <option value="kategori">Kategori</option>
                    <option value="tahun">Tahun</option>
                    <option value="kategori-tahun">Kategori & tahun</option>
                    <option value="none">Tanpa pengelompokan</option>
                </select>

                <button type="button" @click="resetFilters()" class="ml-auto text-sm font-semibold text-sky-600 hover:text-sky-700">
                    Reset
                </button>
            </div>

            <p class="mt-4 text-sm text-slate-500">
                <span x-text="filteredDatasets.length"></span> {{ $t('dataset ditemukan', 'datasets found') }}
            </p>

            <div x-show="filteredDatasets.length > 0" class="sd-card mt-3 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px]">
                        <thead class="border-b border-slate-200/80 bg-slate-50/90">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-slate-500 uppercase">Dataset</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-slate-500 uppercase">{{ $t('Periode', 'Period') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-slate-500 uppercase">Format</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-slate-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold tracking-wide whitespace-nowrap text-slate-500 uppercase">{{ $t('Aksi', 'Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="r in tableRows" :key="r._rowKey">
                                <tr :class="r._isHeader ? 'border-t border-slate-100 bg-sky-50/60' : 'border-t border-slate-200/70 align-top hover:bg-white'">
                                    <template x-if="r._isHeader">
                                        <td colspan="5" class="px-4 py-2 text-xs font-bold text-sky-800">
                                            <span x-text="r.groupTitle"></span>
                                            <span class="ml-1 font-medium text-sky-600/80" x-text="'· ' + r.groupCount + ' dataset'"></span>
                                        </td>
                                    </template>

                                    <template x-if="!r._isHeader">
                                        <td class="max-w-[360px] px-4 py-3 text-sm text-slate-600">
                                            <p class="font-bold text-slate-900" x-text="r.dataset.title"></p>
                                            <p class="mt-0.5 line-clamp-2 text-[13px] text-slate-500" x-text="r.dataset.desc"></p>
                                            <p class="mt-1 text-xs text-slate-400">
                                                <span x-text="getCategoryName(r.dataset.kategori)"></span> ·
                                                <span x-text="`${r.dataset.rowCount} baris`"></span> ·
                                                <span>{{ $t('diperbarui', 'updated') }} </span><span x-text="r.dataset.updated"></span>
                                            </p>
                                        </td>
                                    </template>

                                    <template x-if="!r._isHeader">
                                        <td class="px-4 py-3 text-sm whitespace-nowrap text-slate-600">
                                            <span x-text="r.dataset.tahun"></span>
                                            <p class="text-xs text-slate-400" x-text="`Semester ${r.dataset.periode}`"></p>
                                        </td>
                                    </template>

                                    <template x-if="!r._isHeader">
                                        <td class="px-4 py-3 text-sm text-slate-600">
                                            <span class="rounded-md bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600" x-text="r.dataset.format"></span>
                                        </td>
                                    </template>

                                    <template x-if="!r._isHeader">
                                        <td class="px-4 py-3 text-sm text-slate-600">
                                            <template x-if="r.dataset.access === 'public'">
                                                <span class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 text-[11px] font-bold text-emerald-700">
                                                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                                                    Publik
                                                </span>
                                            </template>
                                            <template x-if="r.dataset.access === 'private'">
                                                <span class="inline-flex items-center gap-1 rounded-md bg-amber-50 px-2 py-0.5 text-[11px] font-bold text-amber-700">
                                                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
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

                                    <template x-if="!r._isHeader">
                                        <td class="px-4 py-3 text-right text-sm text-slate-600">
                                            <template x-if="canUse(r.dataset)">
                                                <div class="flex justify-end gap-1.5">
                                                    <button type="button"
                                                            @click="openPreview(r.dataset)"
                                                            class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-sky-300 hover:text-sky-700"
                                                            title="Lihat data"
                                                            aria-label="Lihat data">
                                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                                    </button>
                                                    <a :href="r.dataset.csvUrl"
                                                       class="inline-flex h-9 shrink-0 items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-2.5 text-xs font-bold text-slate-600 transition hover:border-sky-300 hover:text-sky-700">
                                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                                        CSV
                                                    </a>
                                                    <a :href="r.dataset.xlsxUrl"
                                                       class="inline-flex h-9 shrink-0 items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-2.5 text-xs font-bold text-slate-600 transition hover:border-sky-300 hover:text-sky-700">
                                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><path d="M8 13h2"/><path d="M8 17h2"/><path d="M14 13h2"/><path d="M14 17h2"/></svg>
                                                        XLSX
                                                    </a>
                                                </div>
                                            </template>
                                            <template x-if="!canUse(r.dataset)">
                                                <button type="button"
                                                        @click="handlePrivateAction(r.dataset)"
                                                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">
                                                    <span x-text="getMyRequest(r.dataset)?.status === 'menunggu' ? 'Lihat status' : @js($t('Ajukan permohonan', 'Request access'))"></span>
                                                </button>
                                            </template>
                                        </td>
                                    </template>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div x-show="filteredDatasets.length > pageSize" class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-4 py-3 text-sm text-slate-500">
                    <span>
                        <span x-text="(page - 1) * pageSize + 1"></span>–<span x-text="Math.min(page * pageSize, filteredDatasets.length)"></span> dari <span x-text="filteredDatasets.length"></span>
                    </span>
                    <div class="flex items-center gap-1.5">
                        <button type="button" :disabled="page <= 1" @click="page--" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-sky-300 hover:text-sky-700 disabled:cursor-not-allowed disabled:opacity-50" aria-label="Sebelumnya">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                        </button>
                        <span class="px-2 font-semibold text-slate-700" x-text="`${page} / ${totalPages}`"></span>
                        <button type="button" :disabled="page >= totalPages" @click="page++" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-sky-300 hover:text-sky-700 disabled:cursor-not-allowed disabled:opacity-50" aria-label="Berikutnya">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            <div x-show="filteredDatasets.length === 0" class="mt-3 rounded-2xl border border-dashed border-slate-300 bg-surface px-6 py-14 text-center">
                <span class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-slate-100 text-slate-400">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"/><path d="M2 10h20"/></svg>
                </span>
                <p class="mt-4 font-bold text-slate-800">{{ $t('Data tidak ditemukan', 'No matching datasets.') }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ $t('Ubah kata kunci atau filter pencarian.', 'Try a different keyword or filter.') }}</p>
            </div>
        </section>
    </div>

    <!-- Modal Preview -->
    <template x-if="activeModal === 'preview' && selectedDataset">
        <div class="fixed inset-0 z-50 grid place-items-end bg-slate-900/40 backdrop-blur-sm sm:place-items-center sm:p-6" @click.self="closeModal()">
            <div role="dialog" aria-modal="true" class="flex max-h-[92vh] w-full max-w-6xl flex-col overflow-hidden rounded-t-2xl bg-white shadow-2xl sm:rounded-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 pt-5 pb-4 sm:px-8 sm:pt-6">
                    <div class="min-w-0">
                        <p class="flex flex-wrap items-center gap-2 text-sm text-slate-500">
                            <span>Dataset › <span x-text="getCategoryName(selectedDataset.kategori)"></span></span>
                            <span x-show="selectedDataset.access === 'public'" class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 text-[11px] font-bold text-emerald-700">Publik</span>
                            <span x-show="selectedDataset.access === 'private'" class="inline-flex items-center gap-1 rounded-md bg-amber-50 px-2 py-0.5 text-[11px] font-bold text-amber-700">Private</span>
                        </p>
                        <h2 class="mt-1 text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl" x-text="selectedDataset.title"></h2>
                        <p class="mt-1 text-sm text-slate-500" x-text="selectedDataset.desc"></p>
                    </div>
                    <button type="button" @click="closeModal()" aria-label="Tutup" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-sky-300 hover:text-sky-700">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>

                <div class="overflow-auto px-5 py-5 sm:px-8">
                    <template x-if="previewLoading">
                        <p class="py-12 text-center text-sm text-slate-500">Memuat data…</p>
                    </template>
                    <template x-if="previewError">
                        <div class="rounded-xl bg-red-50 px-4 py-3 text-sm text-[#b02a37]" x-text="previewError"></div>
                    </template>
                    <template x-if="!previewLoading && !previewError && previewPayload">
                        <div class="overflow-hidden rounded-xl border border-slate-200">
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead class="bg-surface-strong">
                                        <tr>
                                            <template x-for="c in previewPayload.cols" :key="c">
                                                <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-slate-500 uppercase" x-text="c"></th>
                                            </template>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="(row, idx) in previewSlice" :key="idx">
                                            <tr class="border-t border-slate-100 hover:bg-sky-50/50">
                                                <template x-for="(val, cIdx) in row" :key="cIdx">
                                                    <td class="max-w-[340px] px-4 py-3 align-top text-sm whitespace-pre-line break-words tabular-nums text-slate-600" x-text="val"></td>
                                                </template>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            <div x-show="previewPayload.rows.length > 10" class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-4 py-3 text-sm text-slate-500">
                                <span>
                                    <span x-text="(previewPage - 1) * 10 + 1"></span>–<span x-text="Math.min(previewPage * 10, previewPayload.rows.length)"></span> dari <span x-text="previewPayload.rows.length"></span>
                                </span>
                                <div class="flex items-center gap-1.5">
                                    <button type="button" :disabled="previewPage <= 1" @click="previewPage--" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 disabled:opacity-50">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                                    </button>
                                    <span class="px-2 font-semibold text-slate-700" x-text="`${previewPage} / ${Math.ceil(previewPayload.rows.length / 10)}`"></span>
                                    <button type="button" :disabled="previewPage >= Math.ceil(previewPayload.rows.length / 10)" @click="previewPage++" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 disabled:opacity-50">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:px-8">
                    <p class="mr-auto text-sm text-slate-500">
                        <span x-text="selectedDataset.id"></span> ·
                        <span x-text="`${selectedDataset.rowCount} baris`"></span> ·
                        <span>diperbarui </span><span x-text="selectedDataset.updated"></span>
                    </p>
                    <div class="flex gap-1.5">
                        <a :href="selectedDataset.csvUrl" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                            CSV
                        </a>
                        <a :href="selectedDataset.xlsxUrl" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-700">
                            XLSX
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <!-- Modal Request Form -->
    <template x-if="activeModal === 'form' && selectedDataset">
        <div class="fixed inset-0 z-50 grid place-items-end bg-slate-900/40 backdrop-blur-sm sm:place-items-center sm:p-6" @click.self="closeModal()">
            <div role="dialog" aria-modal="true" class="flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-t-2xl bg-white shadow-2xl sm:rounded-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 pt-5 pb-4 sm:px-8 sm:pt-6">
                    <div class="min-w-0">
                        <p class="flex flex-wrap items-center gap-2 text-sm text-slate-500">
                            <span>Permohonan data</span>
                            <span class="inline-flex items-center gap-1 rounded-md bg-amber-50 px-2 py-0.5 text-[11px] font-bold text-amber-700">Private</span>
                        </p>
                        <h2 class="mt-1 text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl" x-text="selectedDataset.title"></h2>
                        <p class="mt-1 text-sm text-slate-500" x-text="selectedDataset.desc"></p>
                    </div>
                    <button type="button" @click="closeModal()" aria-label="Tutup" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-sky-300 hover:text-sky-700">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>

                <template x-if="requestSentId">
                    <div class="px-8 py-12 text-center">
                        <div class="mx-auto grid h-14 w-14 place-items-center rounded-full bg-sky-100/80 text-sky-600">
                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        </div>
                        <h3 class="mt-4 text-xl font-bold text-slate-900">Permohonan berhasil dikirim</h3>
                        <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">
                            Nomor permohonan <b class="text-slate-800" x-text="requestSentId"></b>. Permohonan akan diperiksa oleh Wali Data. Setelah dikonfirmasi, dataset dapat dilihat dan diunduh langsung dari halaman ini.
                        </p>
                        <button type="button" @click="closeModal()" class="mt-6 inline-flex items-center justify-center gap-2 rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-700">
                            Kembali ke dataset
                        </button>
                    </div>
                </template>

                <template x-if="!requestSentId">
                    <form @submit.prevent="submitRequest()" class="flex min-h-0 flex-col">
                        <div class="space-y-5 overflow-y-auto px-5 py-6 sm:px-8">
                            <div class="flex gap-3 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800">
                                <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                <div>
                                    Dataset ini berstatus <b>private</b>. Akses diberikan setelah permohonan dikonfirmasi oleh Wali Data. Kolom bertanda <span class="text-[#DC3545]">*</span> wajib diisi.
                                </div>
                            </div>

                            <label class="block">
                                <span class="mb-1.5 block text-sm font-semibold text-slate-800">Nama lengkap <span class="text-[#DC3545]">*</span></span>
                                <input type="text" x-model="form.nama" placeholder="Nama sesuai identitas" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                                <span x-show="formErrors.nama" class="mt-1 block text-xs text-[#DC3545]" x-text="formErrors.nama"></span>
                            </label>

                            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                <label class="block">
                                    <span class="mb-1.5 block text-sm font-semibold text-slate-800">Email <span class="text-[#DC3545]">*</span></span>
                                    <input type="email" x-model="form.email" placeholder="nama@email.com" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                                    <span x-show="formErrors.email" class="mt-1 block text-xs text-[#DC3545]" x-text="formErrors.email"></span>
                                    <span x-show="!formErrors.email" class="mt-1 block text-xs text-slate-500">Pemberitahuan dikirim ke email ini.</span>
                                </label>
                                <label class="block">
                                    <span class="mb-1.5 block text-sm font-semibold text-slate-800">Nomor HP <span class="text-[#DC3545]">*</span></span>
                                    <input type="tel" x-model="form.hp" placeholder="08xxxxxxxxxx" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                                    <span x-show="formErrors.hp" class="mt-1 block text-xs text-[#DC3545]" x-text="formErrors.hp"></span>
                                </label>
                            </div>

                            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                <label class="block">
                                    <span class="mb-1.5 block text-sm font-semibold text-slate-800">NIP/NIM/NIK <span class="text-[#DC3545]">*</span></span>
                                    <input type="text" x-model="form.nim" placeholder="Nomor induk" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                                    <span x-show="formErrors.nim" class="mt-1 block text-xs text-[#DC3545]" x-text="formErrors.nim"></span>
                                </label>
                                <label class="block">
                                    <span class="mb-1.5 block text-sm font-semibold text-slate-800">Tujuan penggunaan data <span class="text-[#DC3545]">*</span></span>
                                    <select x-model="form.tujuan" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                                        <option value="">Pilih tujuan…</option>
                                        @foreach($purposes as $p)
                                            <option value="{{ $p }}">{{ $p }}</option>
                                        @endforeach
                                    </select>
                                    <span x-show="formErrors.tujuan" class="mt-1 block text-xs text-[#DC3545]" x-text="formErrors.tujuan"></span>
                                </label>
                            </div>

                            <label class="block">
                                <span class="mb-1.5 block text-sm font-semibold text-slate-800">Deskripsikan kebutuhan data Anda <span class="text-[#DC3545]">*</span></span>
                                <textarea rows="4" x-model="form.deskripsi" placeholder="Contoh: data penerima KIP angkatan 2022–2024 untuk analisis retensi…" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-sky-400 focus:ring-4 focus:ring-sky-100"></textarea>
                                <span x-show="formErrors.deskripsi" class="mt-1 block text-xs text-[#DC3545]" x-text="formErrors.deskripsi"></span>
                                <span x-show="!formErrors.deskripsi" class="mt-1 block text-xs text-slate-500">Jelaskan bentuk dan cakupan data yang diinginkan.</span>
                            </label>
                        </div>

                        <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:px-8">
                            <button type="button" @click="resetForm()" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                Reset
                            </button>
                            <button type="submit" :disabled="submittingRequest" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-700 disabled:opacity-50">
                                <span x-text="submittingRequest ? 'Mengirim…' : 'Kirim permohonan'"></span>
                            </button>
                        </div>
                    </form>
                </template>
            </div>
        </div>
    </template>

    <!-- Modal Status Permohonan -->
    <template x-if="activeModal === 'status' && selectedRequest && selectedDataset">
        <div class="fixed inset-0 z-50 grid place-items-end bg-slate-900/40 backdrop-blur-sm sm:place-items-center sm:p-6" @click.self="closeModal()">
            <div role="dialog" aria-modal="true" class="flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-t-2xl bg-white shadow-2xl sm:rounded-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 pt-5 pb-4 sm:px-8 sm:pt-6">
                    <div class="min-w-0">
                        <p class="text-sm text-slate-500">Status permohonan · <span x-text="selectedRequest.id"></span></p>
                        <h2 class="mt-1 text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl" x-text="selectedDataset.title"></h2>
                    </div>
                    <button type="button" @click="closeModal()" aria-label="Tutup" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-sky-300 hover:text-sky-700">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>

                <div class="space-y-4 px-5 py-6 sm:px-8">
                    <template x-if="selectedRequest.status === 'menunggu'">
                        <div class="flex gap-3 rounded-xl bg-sky-100/80 px-4 py-3 text-sm text-sky-800">
                            <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                            <div>
                                Permohonan Anda tertanggal <b x-text="formatDate(selectedRequest.at)"></b> sedang menunggu konfirmasi Wali Data.
                            </div>
                        </div>
                    </template>
                    <template x-if="selectedRequest.status === 'ditolak'">
                        <div class="flex gap-3 rounded-xl bg-red-50 px-4 py-3 text-sm text-[#b02a37]">
                            <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                            <div>
                                Permohonan ditolak oleh Wali Data<span x-text="selectedRequest.note ? `: “${selectedRequest.note}”` : '.'"></span> Anda dapat mengajukan permohonan baru.
                            </div>
                        </div>
                    </template>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:px-8">
                    <button type="button" @click="closeModal()" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        Tutup
                    </button>
                    <template x-if="selectedRequest.status === 'ditolak'">
                        <button type="button" @click="openRequestForm(selectedDataset)" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-700">
                            Ajukan baru
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </template>
</main>
@endsection

@push('scripts')
<script>
function datasetManager() {
    return {
        allDatasets: @json($allDatasets),
        categories: @json($categories),
        years: @json($years),
        myRequests: @json($myRequests),

        kategori: @json($kategori),
        access: @json($access),
        tahun: @json($tahun),
        periode: @json($periode),
        format: @json($format),
        group: @json($group),
        q: @json($q),

        page: 1,
        pageSize: 8,

        activeModal: null,
        selectedDataset: null,
        selectedRequest: null,
        previewPayload: null,
        previewLoading: false,
        previewError: '',
        previewPage: 1,

        form: {
            nama: @json(auth()->user()?->name ?? ''),
            email: @json(auth()->user()?->email ?? ''),
            hp: '',
            nim: @json(auth()->user()?->nim ?? ''),
            tujuan: '',
            deskripsi: '',
        },
        formErrors: {},
        requestSentId: null,
        submittingRequest: false,

        init() {
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
            const found = this.categories.find((c) => c.code === code);
            return found ? found.name : code;
        },

        getDataset(id) {
            return this.allDatasets.find((d) => d.id === id);
        },

        getMyRequest(dataset) {
            const reqs = this.myRequests.filter((r) => r.datasetId === dataset.id);
            if (!reqs.length) return null;
            return [...reqs].sort((a, b) => b.at.localeCompare(a.at))[0];
        },

        canUse(dataset) {
            if (dataset.accessible) return true;
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
            return this.sortedDatasets.filter((d) => this.getKeyOf(d) === key).length;
        },

        get filteredDatasets() {
            const kw = this.q.trim().toLowerCase();
            return this.allDatasets.filter((d) => {
                const matchCat = this.kategori === 'all' || d.kategori === this.kategori;
                const matchAcc = this.access === 'all' || d.access === this.access;
                const matchYr = this.tahun === 'all' || String(d.tahun) === this.tahun;
                const matchPer = this.periode === 'all' || d.periode === this.periode;
                const matchFmt = this.format === 'all' || d.format === this.format;
                const matchKw = !kw || `${d.title} ${d.desc} ${this.getCategoryName(d.kategori)} ${d.cols.join(' ')}`.toLowerCase().includes(kw);

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
                const showHead = this.group !== 'none' && (i === 0 || this.getKeyOf(slice[i - 1]) !== k);
                if (showHead) {
                    rows.push({
                        _isHeader: true,
                        _rowKey: 'header_' + k + '_' + i,
                        groupTitle: k,
                        groupCount: this.getGroupCount(k),
                    });
                }
                rows.push({
                    _isHeader: false,
                    _rowKey: 'data_' + d.id,
                    dataset: d,
                });
            }
            return rows;
        },

        get previewSlice() {
            if (!this.previewPayload) return [];
            const start = (this.previewPage - 1) * 10;
            return this.previewPayload.rows.slice(start, start + 10);
        },

        async openPreview(dataset) {
            this.selectedDataset = dataset;
            this.previewPayload = null;
            this.previewError = '';
            this.previewLoading = true;
            this.previewPage = 1;
            this.activeModal = 'preview';
            document.body.style.overflow = 'hidden';

            try {
                const resp = await fetch(dataset.previewUrl, {
                    headers: { Accept: 'application/json' },
                });
                const json = await resp.json();
                if (!resp.ok) {
                    this.previewError = json.message || 'Gagal memuat dataset.';
                } else {
                    this.previewPayload = json;
                }
            } catch {
                this.previewError = 'Terjadi kesalahan saat mengambil data pratinjau.';
            } finally {
                this.previewLoading = false;
            }
        },

        openRequestForm(dataset) {
            this.selectedDataset = dataset;
            this.formErrors = {};
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
            this.previewPayload = null;
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
                this.openRequestForm(dataset);
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
            this.form = { nama: '', email: '', hp: '', nim: '', tujuan: '', deskripsi: '' };
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

        async submitRequest() {
            if (!this.validateForm() || this.submittingRequest) return;
            this.submittingRequest = true;

            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const resp = await fetch(this.selectedDataset.requestUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': token,
                    },
                    body: JSON.stringify(this.form),
                });
                const json = await resp.json();
                if (!resp.ok) {
                    if (json.errors) {
                        Object.keys(json.errors).forEach((k) => {
                            this.formErrors[k] = json.errors[k][0];
                        });
                    }
                    return;
                }
                this.myRequests.unshift(json.request);
                this.requestSentId = json.request.id;
            } catch {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Gagal mengirim permohonan.', tone: 'error' } }));
            } finally {
                this.submittingRequest = false;
            }
        },

        formatDate(isoStr) {
            if (!isoStr) return '';
            const date = new Date(isoStr);
            return Number.isNaN(date.getTime())
                ? isoStr
                : date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
        },
    };
}
</script>
@endpush
