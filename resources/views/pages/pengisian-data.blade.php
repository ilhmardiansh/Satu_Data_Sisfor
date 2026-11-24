@extends('layouts.app')

@section('title', 'Pengisian Data - Satu Data Sistem Informasi UNUSA')

@section('content')
@php
    $user = auth()->user();
    $isLoggedIn = $user !== null;
    $userRole = $user?->role ?? null;
@endphp

<main class="mx-auto max-w-[1200px] px-4 py-10 sm:px-8" x-data="pengisianDataManager()">
    <p class="text-sm text-[#475569]">
        <a href="{{ route('beranda') }}" class="hover:text-[#0069A8]">{{ $t('Beranda', 'Home') }}</a>
        <span class="mx-1">/</span>
        <span class="font-semibold text-[#0069A8]">{{ $t('Pengisian Data', 'Data Submission') }}</span>
    </p>

    <section class="relative isolate mt-4 overflow-hidden rounded-3xl bg-gradient-to-br from-[#182654] to-[#0069A8] text-white">
        <div data-scene="orbit"
             aria-hidden="true"
             class="pointer-events-none absolute inset-y-6 right-4 hidden w-[44%] opacity-75 md:block lg:right-8 lg:w-[42%] lg:opacity-90"
             style="mask-image: radial-gradient(closest-side, #000 66%, transparent 98%); -webkit-mask-image: radial-gradient(closest-side, #000 66%, transparent 98%);"></div>

        <div class="relative z-10 flex flex-col gap-8 p-6 sm:p-9 md:w-[60%] lg:min-h-80 lg:justify-between">
            <div>
                <p class="flex items-center gap-2 text-xs font-bold tracking-wider text-[#74D4FF] uppercase motion-safe:animate-fade-up">
                    <i data-lucide="clipboard-list" class="h-4 w-4" aria-hidden="true"></i>
                    {{ $t('Instrumen Pengumpulan Data Model Google Forms', 'Google Forms-Style Data Collection') }}
                </p>
                <h1 class="mt-3 max-w-2xl text-[clamp(1.75rem,3.4vw,2.5rem)] leading-tight font-extrabold tracking-tight motion-safe:animate-fade-up motion-safe:[animation-delay:80ms]">
                    {{ $t('Pilih dan isi formulir pengumpulan data', 'Choose and complete your data form') }}
                </h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-200 sm:text-base motion-safe:animate-fade-up motion-safe:[animation-delay:140ms]">
                    {{ $t(
                        'Isian Anda masuk sebagai data mentah untuk melalui fase Pre-processing Wali Data sebelum dikemas menjadi dataset dan visualisasi resmi prodi.',
                        'Your response enters Wali Data pre-processing before being compiled into official datasets and dashboards.'
                    ) }}
                </p>
            </div>

            <div class="flex flex-wrap items-end justify-between gap-6">
                <dl class="flex flex-wrap items-center gap-x-8 gap-y-4">
                    <div>
                        <dt class="text-xs font-medium text-slate-200">{{ $t('Form aktif', 'Active forms') }}</dt>
                        <dd class="mt-0.5 text-2xl font-extrabold tabular-nums text-white" data-countup="{{ $activeCount }}">{{ $activeCount }}</dd>
                    </div>
                    <div class="hidden h-8 w-px bg-white/20 sm:block" aria-hidden="true"></div>
                    <div>
                        <dt class="text-xs font-medium text-slate-200">{{ $t('Kategori', 'Categories') }}</dt>
                        <dd class="mt-0.5 text-2xl font-extrabold tabular-nums text-white" data-countup="{{ $categoriesCount }}">{{ $categoriesCount }}</dd>
                    </div>
                    <div class="hidden h-8 w-px bg-white/20 sm:block" aria-hidden="true"></div>
                    <div>
                        <dt class="text-xs font-medium text-slate-200">{{ $t('Mahasiswa', 'Students') }}</dt>
                        <dd class="mt-0.5 text-2xl font-extrabold tabular-nums text-white" data-countup="{{ $mhsCount }}">{{ $mhsCount }}</dd>
                    </div>
                </dl>

                <button type="button"
                        data-motion-toggle
                        onclick="toggleSceneMotion(this)"
                        aria-pressed="false"
                        class="hidden min-h-[38px] items-center gap-1.5 rounded-lg border border-white/20 bg-white/10 px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-white/20 md:inline-flex">
                    <i data-lucide="pause-circle" class="h-3.5 w-3.5" aria-hidden="true"></i>
                    <span data-motion-label>{{ $t('Jeda latar 3D', 'Pause 3D backdrop') }}</span>
                </button>
            </div>
        </div>
    </section>

    <section class="mt-8">
        <div class="flex flex-col gap-3 rounded-2xl border border-slate-200/80 bg-[#F6F9FC] p-4 sm:flex-row">
            <label for="formSearchInput" class="relative flex-1">
                <i data-lucide="search" class="pointer-events-none absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-[#475569]" aria-hidden="true"></i>
                <span class="sr-only">{{ $t('Cari formulir', 'Search forms') }}</span>
                <input
                    id="formSearchInput"
                    type="text"
                    x-model="searchQuery"
                    class="min-h-[44px] w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 pl-10 text-sm text-slate-900 outline-none transition placeholder:text-[#475569] focus:border-[#0069A8] focus:ring-4 focus:ring-sky-100"
                    placeholder="{{ $t('Cari nama atau kategori formulir...', 'Search forms or categories...') }}"
                />
            </label>
            <select
                x-model="targetFilter"
                class="min-h-[44px] w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-medium text-slate-700 outline-none focus:border-[#0069A8] focus:ring-4 focus:ring-sky-100 sm:w-48"
                aria-label="{{ $t('Filter sasaran', 'Target filter') }}"
            >
                <option value="Semua">{{ $t('Semua formulir', 'All forms') }}</option>
                <option value="Mahasiswa">{{ $t('Mahasiswa', 'Students') }}</option>
                <option value="Dosen">{{ $t('Dosen', 'Lecturers') }}</option>
            </select>
        </div>

        <div class="mt-6 flex items-end justify-between gap-4">
            <div>
                <h2 class="text-xl font-extrabold text-slate-900">
                    {{ $t('Formulir tersedia', 'Available forms') }}
                </h2>
                <p class="mt-1 text-sm text-[#475569]">
                    <span x-text="filteredForms.length"></span> {{ $t('formulir aktif', 'active forms') }}
                </p>
            </div>
        </div>

        <div class="reveal-stagger mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3" x-show="filteredForms.length > 0">
            <template x-for="form in filteredForms" :key="form.code">
                <article class="flex min-h-80 flex-col rounded-2xl border border-slate-200/80 bg-white p-5 transition-colors hover:border-[#0069A8]">
                    <div class="flex items-start justify-between gap-3">
                        <span class="grid h-11 w-11 place-items-center rounded-xl bg-[#F0F9FF] text-[#0069A8]">
                            <template x-if="form.target === 'Mahasiswa'">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                            </template>
                            <template x-if="form.target === 'Dosen'">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="7" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                            </template>
                            <template x-if="form.target === 'Semua'">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            </template>
                        </span>
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-700" x-text="form.target"></span>
                    </div>

                    <p class="mt-4 text-xs font-bold tracking-wide text-[#0069A8] uppercase" x-text="form.categoryName"></p>
                    <h3 class="mt-1 text-lg leading-snug font-extrabold text-slate-900" x-text="form.title"></h3>
                    <p class="mt-2 mb-5 line-clamp-3 text-sm leading-6 text-[#475569]" x-text="form.desc"></p>

                    <div class="mt-auto space-y-2 border-t border-slate-100 pt-4 text-xs text-[#475569]">
                        <div class="flex items-center justify-between gap-3">
                            <span x-text="`${form.fields.length} pertanyaan`"></span>
                            <span x-text="`${form.tahun} · ${form.periode}`"></span>
                        </div>
                        <div class="flex items-center gap-1.5 font-semibold" :class="form.urgent ? 'text-[#B91C1C]' : 'text-[#92400E]'">
                            <span x-text="`${form.timeLeft} · ${form.deadlineFormatted}`"></span>
                        </div>
                        <template x-if="form.mine > 0">
                            <div class="font-semibold text-[#047857]" x-text="`Sudah dikirim ${form.mine}x`"></div>
                        </template>
                    </div>

                    <a :href="form.formUrl"
                       class="sd-btn-primary mt-5 inline-flex min-h-[44px] w-full items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold">
                        <span x-text="buttonLabel(form)"></span>
                    </a>
                </article>
            </template>
        </div>

        <div class="mt-5 rounded-2xl border border-dashed border-slate-300 bg-[#F6F9FC] px-6 py-16 text-center" x-show="filteredForms.length === 0" x-cloak>
            <i data-lucide="clipboard-list" class="mx-auto h-10 w-10 text-slate-400" aria-hidden="true"></i>
            <h3 class="mt-3 font-bold text-slate-800">
                {{ $activeCount === 0 ? $t('Belum ada formulir pengumpulan data aktif', 'No active data collection forms yet') : $t('Formulir tidak ditemukan', 'No forms found') }}
            </h3>
            <p class="mt-1 text-sm text-[#475569]">
                {{ $activeCount === 0 ? $t('Wali Data atau Admin perlu membuat dan membuka instrumen formulir terlebih dahulu.', 'Wali Data or Admin must create an open form first.') : $t('Tidak ada formulir yang cocok dengan kata kunci atau sasaran saat ini.', 'No forms match the current keyword or target filter.') }}
            </p>
            <div class="mt-4 flex flex-wrap justify-center gap-2">
                <button type="button" x-show="searchQuery !== '' || targetFilter !== 'Semua'" @click="searchQuery = ''; targetFilter = 'Semua'" class="inline-flex min-h-[40px] items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-[#0069A8] hover:bg-[#F0F9FF]">
                    {{ $t('Reset filter', 'Reset filters') }}
                </button>
                @auth
                    @if(auth()->user()->isStaff())
                        <a href="{{ route('panel.formulir') }}" class="inline-flex min-h-[40px] items-center rounded-xl bg-[#0069A8] px-4 py-2 text-sm font-semibold text-white hover:bg-[#182654]">
                            Buat Instrumen Formulir
                        </a>
                    @endif
                @endauth
            </div>
        </div>
    </section>
</main>
@endsection

@push('scripts')
<script>
function pengisianDataManager() {
    return {
        forms: @json($formsPayload),
        isLoggedIn: @json($isLoggedIn),
        searchQuery: @json($q),
        targetFilter: @json($target),

        get filteredForms() {
            const q = this.searchQuery.trim().toLowerCase();
            return this.forms.filter((f) => {
                const matchesTarget = this.targetFilter === 'Semua' || f.target === this.targetFilter;
                const matchesQuery = !q || `${f.title} ${f.desc} ${f.categoryName}`.toLowerCase().includes(q);
                return matchesTarget && matchesQuery;
            });
        },

        buttonLabel(form) {
            if (!this.isLoggedIn) {
                return @js($t('Buka formulir (Perlu masuk)', 'Open form (Sign in required)'));
            }
            if (form.batasiSatuRespon && form.mine > 0) {
                return @js($t('Sudah diisi · Lihat detail', 'Already submitted · View details'));
            }
            if (form.eligible) {
                return form.mine > 0 ? @js($t('Isi formulir lagi', 'Submit again')) : @js($t('Isi formulir', 'Fill out form'));
            }
            return @js($t('Lihat instrumen formulir', 'View form instrument'));
        },
    };
}
</script>
@endpush
