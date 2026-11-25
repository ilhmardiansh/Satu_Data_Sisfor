@extends('layouts.app')

@section('title', 'Dataset Sistem Informasi - Satu Data UNUSA')

@section('content')
@php
    $datasetCount = count($allDatasets);
    $publicCount = count(array_filter($allDatasets, fn (array $d): bool => ($d['access'] ?? '') === 'public'));
    $privateCount = $datasetCount - $publicCount;
    $categoryCount = count($categories);
@endphp

<main x-data="datasetManager()" x-cloak @keydown.escape.window="closeModal()">
    <!-- Header Arsitektural & Bilah Pencarian Mengambang (UNUSA Identity Motif) -->
    <section class="relative isolate">
        <div class="sd-hero-band relative overflow-hidden">
            <div data-scene="ambient"
                 aria-hidden="true"
                 class="pointer-events-none absolute -top-6 right-0 hidden h-56 w-[440px] opacity-75 lg:block"
                 style="mask-image: radial-gradient(closest-side, #000 58%, transparent 98%); -webkit-mask-image: radial-gradient(closest-side, #000 58%, transparent 98%);"></div>

            <div class="relative z-10 mx-auto max-w-[1200px] px-4 pt-10 pb-16 sm:px-8 sm:pt-12 sm:pb-20">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="max-w-3xl">
                        <nav aria-label="Breadcrumb" class="text-sm text-slate-200">
                            <a href="{{ route('beranda') }}" class="sd-focus-dark hover:text-white">{{ $t('Beranda', 'Home') }}</a>
                            <span class="mx-1.5 text-white/60">/</span>
                            <span class="font-semibold text-[#74D4FF]">Dataset</span>
                        </nav>

                        <h1 class="mt-2 text-[28px] leading-tight font-extrabold tracking-tight text-white sm:text-[36px]">
                            Dataset Sistem Informasi
                        </h1>
                        <p class="mt-2 max-w-2xl text-[15px] leading-relaxed text-slate-200">
                            {{ $t(
                                'Jelajahi kumpulan data resmi Prodi Sistem Informasi UNUSA dalam format CSV dan XLSX. Dataset publik terbuka langsung, sedangkan dataset private memerlukan persetujuan Wali Data.',
                                'Browse official UNUSA Information Systems datasets in CSV and XLSX formats. Public datasets are immediately accessible; private datasets require Data Steward approval.'
                            ) }}
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2.5">
                        <a href="{{ route('dashboard') }}" class="sd-focus-dark inline-flex min-h-[44px] items-center gap-2 rounded-xl border border-white/25 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-white/20">
                            <i data-lucide="layout-grid" class="h-4 w-4 text-[#74D4FF]" aria-hidden="true"></i>
                            {{ $t('Lihat Dashboard', 'View Dashboard') }}
                        </a>
                    </div>
                </div>

                <dl class="mt-7 grid grid-cols-2 gap-x-8 gap-y-4 border-t border-white/15 pt-5 sm:flex sm:flex-wrap sm:items-center sm:gap-x-10">
                    <div>
                        <dt class="text-xs font-medium text-slate-200">{{ $t('Total Dataset', 'Total Datasets') }}</dt>
                        <dd class="mt-0.5 text-2xl font-extrabold tracking-tight tabular-nums text-white">{{ $datasetCount }}</dd>
                    </div>
                    <div class="hidden h-8 w-px bg-white/15 sm:block" aria-hidden="true"></div>
                    <div>
                        <dt class="text-xs font-medium text-slate-200">{{ $t('Akses Publik', 'Public Access') }}</dt>
                        <dd class="mt-0.5 text-2xl font-extrabold tracking-tight tabular-nums text-[#74D4FF]">{{ $publicCount }}</dd>
                    </div>
                    <div class="hidden h-8 w-px bg-white/15 sm:block" aria-hidden="true"></div>
                    <div>
                        <dt class="text-xs font-medium text-slate-200">{{ $t('Akses Private', 'Private Access') }}</dt>
                        <dd class="mt-0.5 text-2xl font-extrabold tracking-tight tabular-nums text-white">{{ $privateCount }}</dd>
                    </div>
                    <div class="hidden h-8 w-px bg-white/15 sm:block" aria-hidden="true"></div>
                    <div>
                        <dt class="text-xs font-medium text-slate-200">{{ $t('Kategori Aktif', 'Active Categories') }}</dt>
                        <dd class="mt-0.5 text-2xl font-extrabold tracking-tight tabular-nums text-white">{{ $categoryCount }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Bilah Pencarian & Kontrol Utama Mengambang -->
        <div class="relative z-20 mx-auto max-w-[1200px] px-4 sm:px-8">
            <div class="-mt-8 translate-y-2 grid grid-cols-1 gap-2.5 rounded-2xl border border-slate-200/85 bg-white p-2.5 shadow-xl shadow-slate-900/10 lg:grid-cols-[1fr_auto_auto]">
                <label class="flex min-h-[44px] flex-1 items-center gap-3 rounded-xl border border-slate-200 bg-[#f6f9fc] px-3.5 focus-within:border-[#0069a8] focus-within:bg-white focus-within:ring-2 focus-within:ring-[#bae6fd]">
                    <svg class="h-4 w-4 shrink-0 text-[#475569]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <span class="sr-only">{{ $t('Cari dataset', 'Search datasets') }}</span>
                    <input
                        type="text"
                        x-model="q"
                        @input="page = 1"
                        placeholder="{{ $t('Cari kata kunci: judul, deskripsi, atau nama kolom…', 'Search keyword: title, description, or column…') }}"
                        class="w-full min-w-0 bg-transparent text-sm text-slate-900 outline-none placeholder:text-[#475569]"
                    />
                    <button
                        type="button"
                        x-show="q !== ''"
                        @click="q = ''; page = 1"
                        aria-label="Hapus pencarian"
                        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-600 hover:bg-slate-200/70 hover:text-slate-900"
                    >
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </label>

                <div class="sd-segmented" role="group" aria-label="{{ $t('Filter status akses', 'Access filter') }}">
                    <button type="button" @click="setAccess('all')" :aria-pressed="access === 'all'" :class="access === 'all' ? 'bg-[#0069a8] text-white' : 'text-slate-700 hover:text-slate-900'" class="min-h-[38px] flex-1 rounded-lg px-3.5 py-1.5 text-xs font-bold transition-colors sm:text-sm">{{ $t('Semua', 'All') }}</button>
                    <button type="button" @click="setAccess('public')" :aria-pressed="access === 'public'" :class="access === 'public' ? 'bg-[#0069a8] text-white' : 'text-slate-700 hover:text-slate-900'" class="min-h-[38px] flex-1 rounded-lg px-3.5 py-1.5 text-xs font-bold transition-colors sm:text-sm">{{ $t('Publik', 'Public') }}</button>
                    <button type="button" @click="setAccess('private')" :aria-pressed="access === 'private'" :class="access === 'private' ? 'bg-[#0069a8] text-white' : 'text-slate-700 hover:text-slate-900'" class="min-h-[38px] flex-1 rounded-lg px-3.5 py-1.5 text-xs font-bold transition-colors sm:text-sm">Private</button>
                </div>

                <div class="sd-segmented" role="group" aria-label="{{ $t('Mode tampilan', 'View mode') }}">
                    <button type="button" @click="viewMode = 'cards'" :aria-pressed="viewMode === 'cards'" :class="viewMode === 'cards' ? 'bg-[#182654] text-white' : 'text-slate-700 hover:text-slate-900'" class="inline-flex min-h-[38px] flex-1 items-center justify-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold transition-colors sm:text-sm">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>
                        {{ $t('Kartu', 'Cards') }}
                    </button>
                    <button type="button" @click="viewMode = 'table'" :aria-pressed="viewMode === 'table'" :class="viewMode === 'table' ? 'bg-[#182654] text-white' : 'text-slate-700 hover:text-slate-900'" class="inline-flex min-h-[38px] flex-1 items-center justify-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold transition-colors sm:text-sm">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                        {{ $t('Tabel', 'Table') }}
                    </button>
                </div>
            </div>
        </div>
    </section>

    <div class="mx-auto max-w-[1200px] px-4 pt-10 pb-14 sm:px-8">
        <!-- Permohonan Saya -->
        <template x-if="myRequests.length > 0">
            <div class="sd-panel-flat mb-7 p-4 sm:p-5">
                <div class="mb-3 flex items-center justify-between gap-2">
                    <p class="text-xs font-bold tracking-wider text-[#475569] uppercase">
                        {{ $t('Riwayat Permohonan Saya', 'My Access Requests') }}
                    </p>
                    <span class="text-xs font-medium text-[#475569]" x-text="`${myRequests.length} permohonan`"></span>
                </div>
                <div class="flex flex-wrap gap-2">
                    <template x-for="r in myRequests" :key="r.id">
                        <button
                            type="button"
                            @click="handleRequestPillClick(r)"
                            :class="{
                                'border-[#bae6fd] bg-[#f0f9ff] text-[#0069a8]': r.status === 'menunggu',
                                'border-[#a7f3d0] bg-[#ecfdf5] text-[#047857]': r.status === 'disetujui',
                                'border-[#fecaca] bg-[#fef2f2] text-[#b91c1c]': r.status === 'ditolak'
                            }"
                            class="inline-flex min-h-[40px] items-center gap-2 rounded-xl border px-3.5 py-1.5 text-xs font-bold transition-opacity hover:opacity-90"
                        >
                            <template x-if="r.status === 'menunggu'">
                                <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            </template>
                            <template x-if="r.status === 'disetujui'">
                                <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                            </template>
                            <template x-if="r.status === 'ditolak'">
                                <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                            </template>
                            <span x-text="`${r.id} · ${(getDataset(r.datasetId)?.title || r.datasetId)} · ${r.status === 'menunggu' ? 'Menunggu konfirmasi' : (r.status === 'disetujui' ? 'Disetujui (Buka)' : 'Ditolak')}`"></span>
                        </button>
                    </template>
                </div>
            </div>
        </template>

        <!-- Strip Kategori Mobile & Tablet -->
        <div class="mb-5 lg:hidden">
            <p class="mb-2 text-xs font-bold tracking-wider text-[#475569] uppercase">{{ $t('Kategori Data', 'Data Categories') }}</p>
            <div class="sd-chip-scroll -mx-1 flex gap-2 overflow-x-auto px-1 pb-1">
                <button
                    type="button"
                    @click="setKategori('all')"
                    :class="kategori === 'all' ? 'border-[#0069a8] bg-[#0069a8] text-white' : 'border-slate-200 bg-white text-slate-700'"
                    class="inline-flex min-h-[44px] shrink-0 items-center gap-1.5 rounded-xl border px-3.5 text-xs font-bold transition-colors"
                >
                    <span>{{ $t('Semua', 'All') }}</span>
                    <span class="font-mono text-[11px] opacity-85" x-text="allDatasets.length"></span>
                </button>
                <template x-for="cat in categories" :key="cat.code">
                    <button
                        type="button"
                        @click="setKategori(cat.code)"
                        :class="kategori === cat.code ? 'border-[#0069a8] bg-[#0069a8] text-white' : 'border-slate-200 bg-white text-slate-700'"
                        class="inline-flex min-h-[44px] shrink-0 items-center gap-1.5 rounded-xl border px-3.5 text-xs font-bold transition-colors"
                    >
                        <span x-text="cat.name"></span>
                        <span class="font-mono text-[11px] opacity-85" x-text="cat.count"></span>
                    </button>
                </template>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-[260px_1fr]">
            <!-- Sidebar Kategori Lengket (Desktop) -->
            <aside class="hidden lg:sticky lg:top-24 lg:block lg:self-start">
                <div class="sd-card p-3">
                    <p class="mb-2 px-2.5 pt-1 text-xs font-bold tracking-wider text-[#475569] uppercase">
                        {{ $t('Kategori Data', 'Data Categories') }}
                    </p>
                    <div class="space-y-1">
                        <button
                            type="button"
                            @click="setKategori('all')"
                            :class="kategori === 'all' ? 'border-[#bae6fd] bg-[#f0f9ff] font-bold text-[#0069a8]' : 'border-transparent text-slate-700 hover:bg-slate-50 hover:text-slate-900'"
                            class="flex min-h-[42px] w-full items-center justify-between gap-2 rounded-xl border px-3 py-2 text-left text-sm transition-colors"
                        >
                            <span>{{ $t('Semua kategori', 'All categories') }}</span>
                            <span class="rounded-md bg-white px-2 py-0.5 font-mono text-xs font-semibold tabular-nums text-[#475569]" x-text="allDatasets.length"></span>
                        </button>
                        <template x-for="cat in categories" :key="cat.code">
                            <button
                                type="button"
                                @click="setKategori(cat.code)"
                                :class="kategori === cat.code ? 'border-[#bae6fd] bg-[#f0f9ff] font-bold text-[#0069a8]' : 'border-transparent text-slate-700 hover:bg-slate-50 hover:text-slate-900'"
                                class="flex min-h-[42px] w-full items-center justify-between gap-2 rounded-xl border px-3 py-2 text-left text-sm transition-colors"
                            >
                                <span class="min-w-0 truncate" x-text="cat.name"></span>
                                <span class="shrink-0 rounded-md bg-white px-2 py-0.5 font-mono text-xs font-semibold tabular-nums text-[#475569]" x-text="cat.count"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </aside>

            <section class="min-w-0">
                <!-- Bilah Filter Lanjutan -->
                <div class="sd-panel-flat flex flex-wrap items-center gap-2.5 p-3">
                    <select x-model="tahun" @change="page = 1" aria-label="Tahun" class="min-h-[44px] flex-1 rounded-xl border border-slate-200 bg-white px-3 text-sm font-medium text-slate-800 outline-none focus:border-[#0069a8] sm:min-h-[40px] sm:flex-none">
                        <option value="all">{{ $t('Semua tahun', 'All years') }}</option>
                        <template x-for="y in years" :key="y">
                            <option :value="String(y)" x-text="y"></option>
                        </template>
                    </select>

                    <select x-model="periode" @change="page = 1" aria-label="Semester" class="min-h-[44px] flex-1 rounded-xl border border-slate-200 bg-white px-3 text-sm font-medium text-slate-800 outline-none focus:border-[#0069a8] sm:min-h-[40px] sm:flex-none">
                        <option value="all">{{ $t('Semua semester', 'All semesters') }}</option>
                        <option value="Ganjil">Ganjil</option>
                        <option value="Genap">Genap</option>
                    </select>

                    <select x-model="format" @change="page = 1" aria-label="Format" class="min-h-[44px] flex-1 rounded-xl border border-slate-200 bg-white px-3 text-sm font-medium text-slate-800 outline-none focus:border-[#0069a8] sm:min-h-[40px] sm:flex-none">
                        <option value="all">{{ $t('Semua format', 'All formats') }}</option>
                        <option value="CSV">CSV</option>
                        <option value="XLSX">XLSX</option>
                    </select>

                    <span class="mx-1 hidden h-6 w-px bg-slate-200 sm:block" aria-hidden="true"></span>

                    <div class="flex flex-1 items-center gap-2 sm:flex-none">
                        <span class="shrink-0 text-xs font-bold tracking-wider text-[#475569] uppercase">{{ $t('Kelompok', 'Group') }}</span>
                        <select x-model="group" @change="page = 1" aria-label="{{ $t('Kelompokkan dataset', 'Group datasets by') }}" class="min-h-[44px] w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-medium text-slate-800 outline-none focus:border-[#0069a8] sm:min-h-[40px] sm:w-auto">
                            <option value="kategori">Kategori</option>
                            <option value="tahun">Tahun</option>
                            <option value="kategori-tahun">Kategori & tahun</option>
                            <option value="none">Tanpa kelompok</option>
                        </select>
                    </div>

                    <button type="button" x-show="hasActiveFilters" @click="resetFilters()" class="ml-auto inline-flex min-h-[40px] items-center rounded-xl px-3 text-sm font-bold text-[#0069a8] transition-colors hover:bg-white hover:text-[#182654]">
                        Reset filter
                    </button>
                </div>

                <!-- Ringkasan Hasil & Chip Filter Aktif -->
                <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm font-medium text-[#475569]">
                        <span class="font-extrabold tabular-nums text-slate-900" x-text="filteredDatasets.length"></span>
                        {{ $t('dataset ditemukan', 'datasets found') }}
                    </p>

                    <div x-show="hasActiveFilters" class="flex flex-wrap items-center gap-1.5">
                        <template x-if="kategori !== 'all'">
                            <button type="button" @click="setKategori('all')" class="inline-flex min-h-[32px] items-center gap-1.5 rounded-lg border border-[#bae6fd] bg-[#f0f9ff] px-2.5 py-1 text-xs font-semibold text-[#0069a8]">
                                <span x-text="getCategoryName(kategori)"></span>
                                <span aria-hidden="true">×</span>
                            </button>
                        </template>
                        <template x-if="access !== 'all'">
                            <button type="button" @click="setAccess('all')" class="inline-flex min-h-[32px] items-center gap-1.5 rounded-lg border border-[#bae6fd] bg-[#f0f9ff] px-2.5 py-1 text-xs font-semibold text-[#0069a8]">
                                <span x-text="access === 'public' ? 'Publik' : 'Private'"></span>
                                <span aria-hidden="true">×</span>
                            </button>
                        </template>
                        <template x-if="tahun !== 'all'">
                            <button type="button" @click="tahun = 'all'; page = 1" class="inline-flex min-h-[32px] items-center gap-1.5 rounded-lg border border-[#bae6fd] bg-[#f0f9ff] px-2.5 py-1 text-xs font-semibold text-[#0069a8]">
                                <span x-text="`Tahun ${tahun}`"></span>
                                <span aria-hidden="true">×</span>
                            </button>
                        </template>
                        <template x-if="periode !== 'all'">
                            <button type="button" @click="periode = 'all'; page = 1" class="inline-flex min-h-[32px] items-center gap-1.5 rounded-lg border border-[#bae6fd] bg-[#f0f9ff] px-2.5 py-1 text-xs font-semibold text-[#0069a8]">
                                <span x-text="`Semester ${periode}`"></span>
                                <span aria-hidden="true">×</span>
                            </button>
                        </template>
                        <template x-if="format !== 'all'">
                            <button type="button" @click="format = 'all'; page = 1" class="inline-flex min-h-[32px] items-center gap-1.5 rounded-lg border border-[#bae6fd] bg-[#f0f9ff] px-2.5 py-1 text-xs font-semibold text-[#0069a8]">
                                <span x-text="format"></span>
                                <span aria-hidden="true">×</span>
                            </button>
                        </template>
                        <template x-if="q.trim() !== ''">
                            <button type="button" @click="q = ''; page = 1" class="inline-flex min-h-[32px] items-center gap-1.5 rounded-lg border border-[#bae6fd] bg-[#f0f9ff] px-2.5 py-1 text-xs font-semibold text-[#0069a8]">
                                <span x-text="`“${q.trim()}”`"></span>
                                <span aria-hidden="true">×</span>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Tampilan Kartu Katalog Modern (Default) -->
                <div x-show="filteredDatasets.length > 0 && viewMode === 'cards'" class="mt-4">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <template x-for="r in tableRows" :key="r._rowKey">
                            <div :class="r._isHeader ? 'md:col-span-2 mt-2 first:mt-0' : 'flex'">
                                <template x-if="r._isHeader">
                                    <div class="flex items-center gap-3 pb-1 text-sm">
                                        <span class="font-extrabold tracking-tight text-[#182654]" x-text="r.groupTitle"></span>
                                        <span class="h-px flex-1 bg-slate-200"></span>
                                        <span class="rounded-md bg-[#f0f9ff] px-2 py-0.5 font-mono text-xs font-bold text-[#0069a8]" x-text="`${r.groupCount} dataset`"></span>
                                    </div>
                                </template>

                                <template x-if="!r._isHeader">
                                    <article class="sd-card sd-card-hover flex w-full flex-col p-5">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <div class="flex flex-wrap items-center gap-1.5 text-xs">
                                                <span class="rounded-md bg-[#f0f9ff] px-2 py-0.5 font-mono font-bold text-[#0069a8]" x-text="r.dataset.id"></span>
                                                <span class="rounded-md bg-slate-100 px-2 py-0.5 font-semibold text-slate-700" x-text="r.dataset.format"></span>
                                                <template x-if="r.dataset.sourceFormCode">
                                                    <span class="rounded-md bg-[#F0F9FF] px-2 py-0.5 font-medium text-[#0069A8]" x-text="`Dari ${r.dataset.sourceFormCode}`" :title="r.dataset.sourceFormTitle || ''"></span>
                                                </template>
                                                <span class="truncate font-medium text-[#475569]" x-text="getCategoryName(r.dataset.kategori)"></span>
                                            </div>

                                            <template x-if="r.dataset.access === 'public'">
                                                <span class="inline-flex items-center gap-1 rounded-md bg-[#ecfdf5] px-2 py-0.5 text-[11px] font-bold text-[#047857]">
                                                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/></svg>
                                                    Publik
                                                </span>
                                            </template>
                                            <template x-if="r.dataset.access === 'private'">
                                                <span class="inline-flex items-center gap-1 rounded-md bg-[#fffbeb] px-2 py-0.5 text-[11px] font-bold text-[#92400e]">
                                                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                                    Private
                                                </span>
                                            </template>
                                        </div>

                                        <h2 class="mt-3 text-[17px] leading-snug font-extrabold tracking-tight text-slate-900" x-text="r.dataset.title"></h2>
                                        <p class="mt-1.5 line-clamp-2 text-sm leading-relaxed text-[#475569]" x-text="r.dataset.desc"></p>

                                        <!-- Pratinjau Skema Kolom Dataset -->
                                        <div class="mt-3 flex flex-wrap gap-1.5">
                                            <template x-for="(col, cIdx) in r.dataset.cols.slice(0, 4)" :key="cIdx">
                                                <span class="max-w-[140px] truncate rounded-md border border-slate-200/80 bg-[#f6f9fc] px-2 py-0.5 text-[11px] font-medium text-slate-700" x-text="col"></span>
                                            </template>
                                            <template x-if="r.dataset.cols.length > 4">
                                                <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-[#475569]" x-text="`+${r.dataset.cols.length - 4} kolom`"></span>
                                            </template>
                                        </div>

                                        <template x-if="r.dataset.access === 'private' && getMyRequest(r.dataset)">
                                            <p class="mt-2.5 text-xs font-bold"
                                               :class="{
                                                   'text-[#047857]': getMyRequest(r.dataset).status === 'disetujui',
                                                   'text-[#b91c1c]': getMyRequest(r.dataset).status === 'ditolak',
                                                   'text-[#0069a8]': getMyRequest(r.dataset).status === 'menunggu'
                                               }"
                                               x-text="getMyRequest(r.dataset).status === 'disetujui' ? 'Status permohonan: Disetujui' : (getMyRequest(r.dataset).status === 'ditolak' ? 'Status permohonan: Ditolak' : 'Status permohonan: Menunggu konfirmasi')">
                                            </p>
                                        </template>

                                        <div class="mt-auto pt-4">
                                            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-3.5">
                                                <p class="text-xs font-medium tabular-nums text-[#475569]">
                                                    <span class="font-bold text-slate-800" x-text="`${r.dataset.tahun} · ${r.dataset.periode}`"></span>
                                                    ·
                                                    <span x-text="`${r.dataset.rowCount.toLocaleString('id-ID')} baris`"></span>
                                                </p>

                                                <template x-if="canUse(r.dataset)">
                                                    <div class="flex flex-wrap items-center gap-1.5">
                                                        <button type="button"
                                                                @click="openPreview(r.dataset)"
                                                                class="inline-flex min-h-[40px] items-center justify-center gap-1.5 rounded-xl bg-[#f0f9ff] px-3 py-1.5 text-xs font-bold text-[#0069a8] transition-colors hover:bg-[#0069a8] hover:text-white">
                                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                                            {{ $t('Pratinjau', 'Preview') }}
                                                        </button>
                                                        <a :href="r.dataset.csvUrl"
                                                           class="inline-flex min-h-[40px] items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 transition-colors hover:border-[#0069a8] hover:text-[#0069a8]">
                                                            CSV
                                                        </a>
                                                        <a :href="r.dataset.xlsxUrl"
                                                           class="inline-flex min-h-[40px] items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 transition-colors hover:border-[#0069a8] hover:text-[#0069a8]">
                                                            XLSX
                                                        </a>
                                                    </div>
                                                </template>

                                                <template x-if="!canUse(r.dataset)">
                                                    <button type="button"
                                                            @click="handlePrivateAction(r.dataset)"
                                                            class="sd-btn-primary min-h-[40px] px-4 py-1.5 text-xs font-bold">
                                                        <span x-text="getMyRequest(r.dataset)?.status === 'menunggu' ? 'Lihat status' : @js($t('Ajukan permohonan', 'Request access'))"></span>
                                                    </button>
                                                </template>
                                            </div>
                                        </div>
                                    </article>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Tampilan Tabel Katalog (Alternatif Ringkas) -->
                <div x-show="filteredDatasets.length > 0 && viewMode === 'table'" class="sd-card mt-4 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[760px]">
                            <thead class="border-b border-slate-200/80 bg-[#f6f9fc]">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-[#475569] uppercase">Dataset</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-[#475569] uppercase">{{ $t('Periode', 'Period') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-[#475569] uppercase">Format</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-[#475569] uppercase">Status</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold tracking-wide whitespace-nowrap text-[#475569] uppercase">{{ $t('Aksi', 'Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="r in tableRows" :key="r._rowKey">
                                    <tr :class="r._isHeader ? 'border-t border-slate-100 bg-[#f0f9ff]' : 'border-t border-slate-200/70 align-top hover:bg-slate-50/70'">
                                        <template x-if="r._isHeader">
                                            <td colspan="5" class="px-4 py-2 text-xs font-bold text-[#0069a8]">
                                                <span x-text="r.groupTitle"></span>
                                                <span class="ml-1 font-medium text-slate-700" x-text="'· ' + r.groupCount + ' dataset'"></span>
                                            </td>
                                        </template>

                                        <template x-if="!r._isHeader">
                                            <td class="max-w-[360px] px-4 py-3.5 text-sm text-slate-700">
                                                <p class="font-bold text-slate-900" x-text="r.dataset.title"></p>
                                                <p class="mt-0.5 line-clamp-2 text-[13px] text-[#475569]" x-text="r.dataset.desc"></p>
                                                <p class="mt-1 text-xs font-medium text-[#475569]">
                                                    <span class="font-mono text-[#0069a8]" x-text="r.dataset.id"></span> ·
                                                    <span x-text="getCategoryName(r.dataset.kategori)"></span> ·
                                                    <span x-text="`${r.dataset.rowCount} baris`"></span> ·
                                                    <span>{{ $t('diperbarui', 'updated') }} </span><span x-text="r.dataset.updated"></span>
                                                </p>
                                            </td>
                                        </template>

                                        <template x-if="!r._isHeader">
                                            <td class="px-4 py-3.5 text-sm whitespace-nowrap text-slate-700">
                                                <span class="font-semibold text-slate-900" x-text="r.dataset.tahun"></span>
                                                <p class="text-xs text-[#475569]" x-text="`Semester ${r.dataset.periode}`"></p>
                                            </td>
                                        </template>

                                        <template x-if="!r._isHeader">
                                            <td class="px-4 py-3.5 text-sm text-slate-700">
                                                <span class="rounded-md bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-800" x-text="r.dataset.format"></span>
                                            </td>
                                        </template>

                                        <template x-if="!r._isHeader">
                                            <td class="px-4 py-3.5 text-sm text-slate-700">
                                                <template x-if="r.dataset.access === 'public'">
                                                    <span class="inline-flex items-center gap-1 rounded-md bg-[#ecfdf5] px-2 py-0.5 text-[11px] font-bold text-[#047857]">
                                                        Publik
                                                    </span>
                                                </template>
                                                <template x-if="r.dataset.access === 'private'">
                                                    <span class="inline-flex items-center gap-1 rounded-md bg-[#fffbeb] px-2 py-0.5 text-[11px] font-bold text-[#92400e]">
                                                        Private
                                                    </span>
                                                </template>
                                                <template x-if="r.dataset.access === 'private' && getMyRequest(r.dataset)">
                                                    <p class="mt-1 text-[11px] font-semibold"
                                                       :class="{
                                                           'text-[#047857]': getMyRequest(r.dataset).status === 'disetujui',
                                                           'text-[#b91c1c]': getMyRequest(r.dataset).status === 'ditolak',
                                                           'text-[#0069a8]': getMyRequest(r.dataset).status === 'menunggu'
                                                       }"
                                                       x-text="getMyRequest(r.dataset).status === 'disetujui' ? 'Akses diberikan' : (getMyRequest(r.dataset).status === 'ditolak' ? 'Permohonan ditolak' : 'Menunggu konfirmasi')">
                                                    </p>
                                                </template>
                                            </td>
                                        </template>

                                        <template x-if="!r._isHeader">
                                            <td class="px-4 py-3.5 text-right text-sm text-slate-700">
                                                <template x-if="canUse(r.dataset)">
                                                    <div class="flex justify-end gap-1.5">
                                                        <button type="button"
                                                                @click="openPreview(r.dataset)"
                                                                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 transition-colors hover:border-[#0069a8] hover:text-[#0069a8]"
                                                                title="Lihat data"
                                                                aria-label="Lihat pratinjau data">
                                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                                        </button>
                                                        <a :href="r.dataset.csvUrl"
                                                           class="inline-flex h-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white px-3 text-xs font-bold text-slate-700 transition-colors hover:border-[#0069a8] hover:text-[#0069a8]">
                                                            CSV
                                                        </a>
                                                        <a :href="r.dataset.xlsxUrl"
                                                           class="inline-flex h-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white px-3 text-xs font-bold text-slate-700 transition-colors hover:border-[#0069a8] hover:text-[#0069a8]">
                                                            XLSX
                                                        </a>
                                                    </div>
                                                </template>
                                                <template x-if="!canUse(r.dataset)">
                                                    <button type="button"
                                                            @click="handlePrivateAction(r.dataset)"
                                                            class="inline-flex min-h-[40px] items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-800 transition-colors hover:border-[#0069a8] hover:text-[#0069a8]">
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
                </div>

                <!-- Paginasi Katalog -->
                <div x-show="filteredDatasets.length > pageSize" class="sd-card mt-4 flex flex-wrap items-center justify-between gap-3 px-4 py-3 text-sm text-[#475569]">
                    <span>
                        Menampilkan <strong class="tabular-nums text-slate-900" x-text="(page - 1) * pageSize + 1"></strong> - <strong class="tabular-nums text-slate-900" x-text="Math.min(page * pageSize, filteredDatasets.length)"></strong> dari <strong class="tabular-nums text-slate-900" x-text="filteredDatasets.length"></strong> dataset
                    </span>
                    <div class="flex items-center gap-1.5">
                        <button type="button" :disabled="page <= 1" @click="page--" class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 transition-colors hover:border-[#0069a8] hover:text-[#0069a8] disabled:opacity-50" aria-label="Halaman sebelumnya">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>
                        </button>
                        <span class="px-2.5 font-mono text-sm font-bold text-slate-800 tabular-nums" x-text="`${page} / ${totalPages}`"></span>
                        <button type="button" :disabled="page >= totalPages" @click="page++" class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 transition-colors hover:border-[#0069a8] hover:text-[#0069a8] disabled:opacity-50" aria-label="Halaman berikutnya">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Status Kosong dengan Aksi Jelas (R-27) -->
                <div x-show="filteredDatasets.length === 0" class="mt-4 rounded-2xl border border-dashed border-slate-300 bg-[#f6f9fc] px-6 py-14 text-center">
                    <span class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-white text-[#0069a8]">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"/><path d="M2 10h20"/></svg>
                    </span>
                    <p class="mt-4 text-base font-bold text-slate-900" x-text="allDatasets.length === 0 ? @js($t('Belum ada dataset yang diterbitkan', 'No published datasets yet')) : @js($t('Tidak ada dataset yang cocok dengan filter ini', 'No datasets match the current filters'))"></p>
                    <p class="mt-1 text-sm text-[#475569]" x-text="allDatasets.length === 0 ? @js($t('Dataset akan muncul di sini setelah instrumen formulir diisi dan lolos fase Pre-processing oleh Wali Data.', 'Datasets appear here once form submissions pass Wali Data pre-processing and are published.')) : @js($t('Ubah kata kunci pencarian atau reset seluruh filter untuk melihat semua koleksi dataset prodi.', 'Change your search query or reset filters to browse all study program datasets.'))"></p>
                    <div class="mt-5 flex flex-wrap justify-center gap-2.5">
                        <button type="button" x-show="allDatasets.length > 0" @click="resetFilters()" class="sd-btn-primary min-h-[44px] px-5 py-2.5 text-sm">
                            {{ $t('Reset semua filter', 'Reset all filters') }}
                        </button>
                        <a href="{{ route('pengisian-data') }}" x-show="allDatasets.length === 0" class="sd-btn-primary inline-flex min-h-[44px] items-center px-5 py-2.5 text-sm">
                            {{ $t('Buka Portal Pengisian Data', 'Open Data Collection Portal') }}
                        </a>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <!-- Modal Preview Interaktif -->
    <template x-if="activeModal === 'preview' && selectedDataset">
        <div class="fixed inset-0 z-50 grid place-items-end bg-slate-900/50 sm:place-items-center sm:p-6" @click.self="closeModal()">
            <div role="dialog" aria-modal="true" aria-labelledby="modal-preview-title" class="flex max-h-[92vh] w-full max-w-6xl flex-col overflow-hidden rounded-t-2xl border border-slate-200 bg-white shadow-2xl sm:rounded-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 pt-5 pb-4 sm:px-8 sm:pt-6">
                    <div class="min-w-0">
                        <p class="flex flex-wrap items-center gap-2 text-xs font-semibold text-[#475569]">
                            <span class="rounded-md bg-[#f0f9ff] px-2 py-0.5 font-mono font-bold text-[#0069a8]" x-text="selectedDataset.id"></span>
                            <span x-text="getCategoryName(selectedDataset.kategori)"></span>
                            <span x-show="selectedDataset.access === 'public'" class="inline-flex rounded-md bg-[#ecfdf5] px-2 py-0.5 font-bold text-[#047857]">Publik</span>
                            <span x-show="selectedDataset.access === 'private'" class="inline-flex rounded-md bg-[#fffbeb] px-2 py-0.5 font-bold text-[#92400e]">Private</span>
                        </p>
                        <h2 id="modal-preview-title" class="mt-1.5 text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl" x-text="selectedDataset.title"></h2>
                        <p class="mt-1 text-sm text-[#475569]" x-text="selectedDataset.desc"></p>
                        <template x-if="selectedDataset.sourceFormTitle">
                            <p class="mt-1.5 inline-flex items-center gap-1.5 rounded-lg bg-[#F0F9FF] px-2.5 py-1 text-xs font-semibold text-[#0069A8]">
                                <span x-text="`Sumber instrumen: ${selectedDataset.sourceFormTitle}${selectedDataset.preprocessingAt ? ' · Pre-processing ' + selectedDataset.preprocessingAt : ''}`"></span>
                            </p>
                        </template>
                    </div>
                    <button type="button" @click="closeModal()" aria-label="Tutup dialog" class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 transition-colors hover:border-[#0069a8] hover:text-[#0069a8]">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>

                <div class="overflow-auto px-5 py-5 sm:px-8">
                    <!-- Loading State -->
                    <template x-if="previewLoading">
                        <div class="space-y-3 py-8">
                            <p class="text-center text-sm font-semibold text-[#475569]">Memuat isi tabel dataset…</p>
                            <div class="mx-auto max-w-2xl space-y-2.5">
                                <div class="h-9 rounded-xl bg-slate-100"></div>
                                <div class="h-8 rounded-xl bg-slate-100/75"></div>
                                <div class="h-8 rounded-xl bg-slate-100/60"></div>
                            </div>
                        </div>
                    </template>

                    <!-- Error State dengan Aksi Ulang -->
                    <template x-if="previewError">
                        <div class="rounded-2xl border border-red-200 bg-[#fef2f2] px-5 py-6 text-center">
                            <p class="text-sm font-bold text-[#b91c1c]" x-text="previewError"></p>
                            <button type="button" @click="openPreview(selectedDataset)" class="mt-3 inline-flex min-h-[40px] items-center justify-center rounded-xl border border-red-300 bg-white px-4 py-1.5 text-xs font-bold text-[#b91c1c] hover:bg-red-50">
                                Coba muat ulang
                            </button>
                        </div>
                    </template>

                    <!-- Loaded Data Table + In-Modal Search -->
                    <template x-if="!previewLoading && !previewError && previewPayload">
                        <div>
                            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                                <label class="flex min-h-[42px] w-full max-w-sm items-center gap-2.5 rounded-xl border border-slate-200 bg-[#f6f9fc] px-3 focus-within:border-[#0069a8] focus-within:bg-white">
                                    <svg class="h-4 w-4 shrink-0 text-[#475569]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                    <span class="sr-only">Cari baris pada tabel pratinjau</span>
                                    <input
                                        type="text"
                                        x-model="previewQuery"
                                        @input="previewPage = 1"
                                        placeholder="Cari di dalam tabel ini…"
                                        class="w-full min-w-0 bg-transparent text-sm text-slate-900 outline-none placeholder:text-[#475569]"
                                    />
                                    <button type="button" x-show="previewQuery !== ''" @click="previewQuery = ''; previewPage = 1" aria-label="Hapus kata kunci tabel" class="text-xs font-bold text-slate-600 hover:text-slate-900">×</button>
                                </label>

                                <p class="text-xs font-semibold tabular-nums text-[#475569]">
                                    <span x-text="previewFilteredRows.length.toLocaleString('id-ID')"></span> baris cocok ·
                                    <span x-text="`${previewPayload.cols.length} kolom`"></span>
                                </p>
                            </div>

                            <div class="overflow-hidden rounded-xl border border-slate-200">
                                <div class="max-h-[52vh] overflow-auto">
                                    <table class="w-full">
                                        <thead class="sticky top-0 z-10 border-b border-slate-200 bg-[#f6f9fc]">
                                            <tr>
                                                <th class="w-12 px-3 py-3 text-left font-mono text-xs font-semibold text-[#475569]">#</th>
                                                <template x-for="c in previewPayload.cols" :key="c">
                                                    <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-[#475569] uppercase" x-text="c"></th>
                                                </template>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="(row, idx) in previewSlice" :key="idx">
                                                <tr class="border-t border-slate-100 hover:bg-[#f0f9ff]/70">
                                                    <td class="px-3 py-3 align-top font-mono text-xs text-[#475569]" x-text="(previewPage - 1) * 10 + idx + 1"></td>
                                                    <template x-for="(val, cIdx) in row" :key="cIdx">
                                                        <td class="max-w-[340px] px-4 py-3 align-top text-sm whitespace-pre-line break-words tabular-nums text-slate-800" x-text="val"></td>
                                                    </template>
                                                </tr>
                                            </template>
                                            <template x-if="previewFilteredRows.length === 0">
                                                <tr>
                                                    <td :colspan="previewPayload.cols.length + 1" class="px-4 py-10 text-center text-sm text-[#475569]">
                                                        Tidak ada baris yang cocok dengan pencarian Anda.
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>

                                <div x-show="previewFilteredRows.length > 10" class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 bg-white px-4 py-3 text-sm text-[#475569]">
                                    <span>
                                        <span class="font-bold text-slate-900" x-text="(previewPage - 1) * 10 + 1"></span>-<span class="font-bold text-slate-900" x-text="Math.min(previewPage * 10, previewFilteredRows.length)"></span> dari <span class="font-bold text-slate-900" x-text="previewFilteredRows.length"></span>
                                    </span>
                                    <div class="flex items-center gap-1.5">
                                        <button type="button" :disabled="previewPage <= 1" @click="previewPage--" aria-label="Halaman sebelumnya" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 hover:border-[#0069a8] hover:text-[#0069a8] disabled:opacity-50">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>
                                        </button>
                                        <span class="px-2 font-mono font-bold text-slate-800 tabular-nums" x-text="`${previewPage} / ${Math.ceil(previewFilteredRows.length / 10)}`"></span>
                                        <button type="button" :disabled="previewPage >= Math.ceil(previewFilteredRows.length / 10)" @click="previewPage++" aria-label="Halaman berikutnya" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 hover:border-[#0069a8] hover:text-[#0069a8] disabled:opacity-50">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:px-8">
                    <p class="mr-auto text-xs font-medium text-[#475569] sm:text-sm">
                        <span class="font-mono font-bold text-slate-900" x-text="selectedDataset.id"></span> ·
                        <span x-text="`${selectedDataset.rowCount} baris`"></span> ·
                        <span>diperbarui </span><span x-text="selectedDataset.updated"></span>
                    </p>
                    <div class="flex gap-2">
                        <a :href="selectedDataset.csvUrl" class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-800 transition-colors hover:border-[#0069a8] hover:text-[#0069a8]">
                            Unduh CSV
                        </a>
                        <a :href="selectedDataset.xlsxUrl" class="sd-btn-primary inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl px-4 py-2 text-sm font-bold text-white">
                            Unduh XLSX
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <!-- Modal Request Form -->
    <template x-if="activeModal === 'form' && selectedDataset">
        <div class="fixed inset-0 z-50 grid place-items-end bg-slate-900/50 sm:place-items-center sm:p-6" @click.self="closeModal()">
            <div role="dialog" aria-modal="true" aria-labelledby="modal-form-title" class="flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-t-2xl border border-slate-200 bg-white shadow-2xl sm:rounded-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 pt-5 pb-4 sm:px-8 sm:pt-6">
                    <div class="min-w-0">
                        <p class="flex flex-wrap items-center gap-2 text-xs font-semibold text-[#475569]">
                            <span>Permohonan akses data</span>
                            <span class="inline-flex items-center gap-1 rounded-md bg-[#fffbeb] px-2 py-0.5 text-[11px] font-bold text-[#92400e]">Private</span>
                        </p>
                        <h2 id="modal-form-title" class="mt-1 text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl" x-text="selectedDataset.title"></h2>
                        <p class="mt-1 text-sm text-[#475569]" x-text="selectedDataset.desc"></p>
                    </div>
                    <button type="button" @click="closeModal()" aria-label="Tutup dialog" class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 transition-colors hover:border-[#0069a8] hover:text-[#0069a8]">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>

                <template x-if="requestSentId">
                    <div class="px-8 py-12 text-center">
                        <div class="mx-auto grid h-14 w-14 place-items-center rounded-full bg-[#f0f9ff] text-[#0069a8]">
                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        </div>
                        <h3 class="mt-4 text-xl font-bold text-slate-900">Permohonan berhasil dikirim</h3>
                        <p class="mx-auto mt-2 max-w-md text-sm text-[#475569]">
                            Nomor permohonan <b class="text-slate-900" x-text="requestSentId"></b>. Permohonan akan diperiksa oleh Wali Data. Setelah dikonfirmasi, dataset dapat dilihat dan diunduh langsung dari halaman ini.
                        </p>
                        <button type="button" @click="closeModal()" class="sd-btn-primary mt-6 inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl px-5 py-2.5 text-sm font-bold text-white">
                            Kembali ke dataset
                        </button>
                    </div>
                </template>

                <template x-if="!requestSentId">
                    <form @submit.prevent="submitRequest()" class="flex min-h-0 flex-col">
                        <div class="space-y-5 overflow-y-auto px-5 py-6 sm:px-8">
                            <div class="flex gap-3 rounded-xl bg-[#fffbeb] px-4 py-3 text-sm text-[#92400e]">
                                <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                <div>
                                    Dataset ini berstatus <b>private</b>. Akses diberikan setelah permohonan dikonfirmasi oleh Wali Data. Kolom bertanda <span class="text-[#b91c1c]">*</span> wajib diisi.
                                </div>
                            </div>

                            <label class="block">
                                <span class="mb-1.5 block text-sm font-semibold text-slate-800">Nama lengkap <span class="text-[#b91c1c]">*</span></span>
                                <input type="text" x-model="form.nama" placeholder="Nama sesuai identitas" class="min-h-[44px] w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-500 focus:border-[#0069a8] focus:ring-2 focus:ring-[#bae6fd]" />
                                <span x-show="formErrors.nama" class="mt-1 block text-xs font-medium text-[#b91c1c]" x-text="formErrors.nama"></span>
                            </label>

                            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                <label class="block">
                                    <span class="mb-1.5 block text-sm font-semibold text-slate-800">Email <span class="text-[#b91c1c]">*</span></span>
                                    <input type="email" x-model="form.email" placeholder="nama@email.com" class="min-h-[44px] w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-500 focus:border-[#0069a8] focus:ring-2 focus:ring-[#bae6fd]" />
                                    <span x-show="formErrors.email" class="mt-1 block text-xs font-medium text-[#b91c1c]" x-text="formErrors.email"></span>
                                    <span x-show="!formErrors.email" class="mt-1 block text-xs text-[#475569]">Pemberitahuan dikirim ke email ini.</span>
                                </label>
                                <label class="block">
                                    <span class="mb-1.5 block text-sm font-semibold text-slate-800">Nomor HP <span class="text-[#b91c1c]">*</span></span>
                                    <input type="tel" x-model="form.hp" placeholder="08xxxxxxxxxx" class="min-h-[44px] w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-500 focus:border-[#0069a8] focus:ring-2 focus:ring-[#bae6fd]" />
                                    <span x-show="formErrors.hp" class="mt-1 block text-xs font-medium text-[#b91c1c]" x-text="formErrors.hp"></span>
                                </label>
                            </div>

                            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                <label class="block">
                                    <span class="mb-1.5 block text-sm font-semibold text-slate-800">NIP/NIM/NIK <span class="text-[#b91c1c]">*</span></span>
                                    <input type="text" x-model="form.nim" placeholder="Nomor induk" class="min-h-[44px] w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-500 focus:border-[#0069a8] focus:ring-2 focus:ring-[#bae6fd]" />
                                    <span x-show="formErrors.nim" class="mt-1 block text-xs font-medium text-[#b91c1c]" x-text="formErrors.nim"></span>
                                </label>
                                <label class="block">
                                    <span class="mb-1.5 block text-sm font-semibold text-slate-800">Tujuan penggunaan data <span class="text-[#b91c1c]">*</span></span>
                                    <select x-model="form.tujuan" class="min-h-[44px] w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition focus:border-[#0069a8] focus:ring-2 focus:ring-[#bae6fd]">
                                        <option value="">Pilih tujuan…</option>
                                        @foreach($purposes as $p)
                                            <option value="{{ $p }}">{{ $p }}</option>
                                        @endforeach
                                    </select>
                                    <span x-show="formErrors.tujuan" class="mt-1 block text-xs font-medium text-[#b91c1c]" x-text="formErrors.tujuan"></span>
                                </label>
                            </div>

                            <label class="block">
                                <span class="mb-1.5 block text-sm font-semibold text-slate-800">Deskripsikan kebutuhan data Anda <span class="text-[#b91c1c]">*</span></span>
                                <textarea rows="4" x-model="form.deskripsi" placeholder="Contoh: data penerima KIP angkatan 2022-2024 untuk analisis retensi…" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-500 focus:border-[#0069a8] focus:ring-2 focus:ring-[#bae6fd]"></textarea>
                                <span x-show="formErrors.deskripsi" class="mt-1 block text-xs font-medium text-[#b91c1c]" x-text="formErrors.deskripsi"></span>
                                <span x-show="!formErrors.deskripsi" class="mt-1 block text-xs text-[#475569]">Jelaskan bentuk dan cakupan data yang diinginkan.</span>
                            </label>
                        </div>

                        <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:px-8">
                            <button type="button" @click="resetForm()" class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                Reset
                            </button>
                            <button type="submit" :disabled="submittingRequest" class="sd-btn-primary inline-flex min-h-[44px] items-center justify-center rounded-xl px-5 py-2 text-sm font-bold text-white disabled:opacity-50">
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
        <div class="fixed inset-0 z-50 grid place-items-end bg-slate-900/50 sm:place-items-center sm:p-6" @click.self="closeModal()">
            <div role="dialog" aria-modal="true" aria-labelledby="modal-status-title" class="flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-t-2xl border border-slate-200 bg-white shadow-2xl sm:rounded-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 pt-5 pb-4 sm:px-8 sm:pt-6">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-[#475569]">Status permohonan · <span class="font-mono font-bold text-[#0069a8]" x-text="selectedRequest.id"></span></p>
                        <h2 id="modal-status-title" class="mt-1 text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl" x-text="selectedDataset.title"></h2>
                    </div>
                    <button type="button" @click="closeModal()" aria-label="Tutup dialog" class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 transition-colors hover:border-[#0069a8] hover:text-[#0069a8]">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>

                <div class="space-y-4 px-5 py-6 sm:px-8">
                    <template x-if="selectedRequest.status === 'menunggu'">
                        <div class="flex gap-3 rounded-xl bg-[#f0f9ff] px-4 py-3 text-sm text-[#0069a8]">
                            <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                            <div>
                                Permohonan Anda tertanggal <b x-text="formatDate(selectedRequest.at)"></b> sedang menunggu konfirmasi Wali Data.
                            </div>
                        </div>
                    </template>
                    <template x-if="selectedRequest.status === 'ditolak'">
                        <div class="flex gap-3 rounded-xl bg-[#fef2f2] px-4 py-3 text-sm text-[#b91c1c]">
                            <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                            <div>
                                Permohonan ditolak oleh Wali Data<span x-text="selectedRequest.note ? `: “${selectedRequest.note}”` : '.'"></span> Anda dapat mengajukan permohonan baru.
                            </div>
                        </div>
                    </template>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:px-8">
                    <button type="button" @click="closeModal()" class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        Tutup
                    </button>
                    <template x-if="selectedRequest.status === 'ditolak'">
                        <button type="button" @click="openRequestForm(selectedDataset)" class="sd-btn-primary inline-flex min-h-[44px] items-center justify-center rounded-xl px-5 py-2 text-sm font-bold text-white">
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
        viewMode: 'cards',

        page: 1,
        pageSize: 8,

        activeModal: null,
        selectedDataset: null,
        selectedRequest: null,
        previewPayload: null,
        previewLoading: false,
        previewError: '',
        previewPage: 1,
        previewQuery: '',

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

        get hasActiveFilters() {
            return (
                this.kategori !== 'all' ||
                this.access !== 'all' ||
                this.tahun !== 'all' ||
                this.periode !== 'all' ||
                this.format !== 'all' ||
                this.q.trim() !== ''
            );
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
                const matchKw = !kw || `${d.id} ${d.title} ${d.desc} ${this.getCategoryName(d.kategori)} ${d.cols.join(' ')}`.toLowerCase().includes(kw);

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

        get previewFilteredRows() {
            if (!this.previewPayload) return [];
            const kw = this.previewQuery.trim().toLowerCase();
            if (!kw) return this.previewPayload.rows;
            return this.previewPayload.rows.filter((row) =>
                row.some((cell) => String(cell ?? '').toLowerCase().includes(kw))
            );
        },

        get previewSlice() {
            const start = (this.previewPage - 1) * 10;
            return this.previewFilteredRows.slice(start, start + 10);
        },

        async openPreview(dataset) {
            this.selectedDataset = dataset;
            this.previewPayload = null;
            this.previewError = '';
            this.previewLoading = true;
            this.previewPage = 1;
            this.previewQuery = '';
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
            this.previewQuery = '';
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
                this.formErrors.hp = 'Nomor HP diawali 0, 10-14 digit.';
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
