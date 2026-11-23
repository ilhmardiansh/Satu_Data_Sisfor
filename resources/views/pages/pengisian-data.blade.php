@extends('layouts.app')

@section('title', 'Pengisian Data - Satu Data Sistem Informasi UNUSA')

@section('content')
@php
    $user = auth()->user();
    $isLoggedIn = $user !== null;
    $userRole = $user?->role ?? null;
@endphp

<main class="mx-auto max-w-[1200px] px-4 py-10 sm:px-8" x-data="pengisianDataManager()">
    <p class="text-sm text-slate-500">
        <a href="{{ route('beranda') }}" class="hover:text-sky-600">{{ $t('Beranda', 'Home') }}</a>
        <span class="mx-1">/</span>
        <span class="text-sky-600">{{ $t('Pengisian Data', 'Data Submission') }}</span>
    </p>

    <!-- Hero Banner -->
    <section class="relative isolate mt-4 overflow-hidden rounded-3xl border border-white/15 bg-gradient-to-br from-benhur via-[#062f78] to-deep text-white shadow-[0_20px_50px_-14px_rgba(0,36,98,0.45)]">
        <div aria-hidden="true" class="pointer-events-none absolute -top-24 -left-20 h-72 w-72 rounded-full bg-accent/20 blur-3xl motion-safe:animate-float"></div>
        <div aria-hidden="true" class="pointer-events-none absolute -right-16 -bottom-32 h-80 w-80 rounded-full bg-white/10 blur-3xl motion-safe:animate-float motion-safe:[animation-delay:-8s]"></div>
        <div data-scene="orbit"
             aria-hidden="true"
             class="pointer-events-none absolute inset-y-6 right-4 hidden w-[44%] opacity-75 md:block lg:right-8 lg:w-[42%] lg:opacity-90"
             style="mask-image: radial-gradient(closest-side, #000 66%, transparent 98%); -webkit-mask-image: radial-gradient(closest-side, #000 66%, transparent 98%);"></div>

        <div class="relative z-10 flex flex-col gap-8 p-6 sm:p-9 md:w-[58%] lg:min-h-96 lg:justify-between">
            <div>
                <span class="sd-glass-panel inline-flex items-center gap-2 rounded-full px-3.5 py-1.5 text-xs font-bold tracking-wide text-white uppercase motion-safe:animate-fade-up">
                    <i data-lucide="clipboard-list" class="h-4 w-4"></i>
                    {{ $t('Portal pengumpulan data', 'Data collection portal') }}
                </span>
                <h1 class="mt-4 max-w-2xl text-3xl font-extrabold tracking-tight drop-shadow-xs sm:text-4xl motion-safe:animate-fade-up motion-safe:[animation-delay:90ms]">
                    {{ $t('Pilih dan isi formulir data Anda', 'Choose and complete your data form') }}
                </h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-white/85 sm:text-base motion-safe:animate-fade-up motion-safe:[animation-delay:180ms]">
                    {{ $t(
                        'Temukan formulir yang sesuai, periksa tenggat waktu, lalu kirim data dan dokumen pendukung secara terarah.',
                        'Find the relevant form, check its deadline, then submit your data and supporting documents.'
                    ) }}
                </p>
            </div>

            <div class="grid max-w-md grid-cols-3 gap-2 motion-safe:animate-fade-up motion-safe:[animation-delay:270ms] sm:gap-3">
                <div class="sd-glass-panel min-w-0 rounded-2xl px-1.5 py-3 text-center sm:p-3">
                    <strong class="block text-2xl font-extrabold tabular-nums" data-countup="{{ $activeCount }}">{{ $activeCount }}</strong>
                    <span class="mt-1 block text-[11px] text-white/80">{{ $t('Form aktif', 'Active forms') }}</span>
                </div>
                <div class="sd-glass-panel min-w-0 rounded-2xl px-1.5 py-3 text-center sm:p-3">
                    <strong class="block text-2xl font-extrabold tabular-nums" data-countup="{{ $categoriesCount }}">{{ $categoriesCount }}</strong>
                    <span class="mt-1 block text-[11px] text-white/80">{{ $t('Kategori', 'Categories') }}</span>
                </div>
                <div class="sd-glass-panel min-w-0 rounded-2xl px-1.5 py-3 text-center sm:p-3">
                    <strong class="block text-2xl font-extrabold tabular-nums" data-countup="{{ $mhsCount }}">{{ $mhsCount }}</strong>
                    <span class="mt-1 block text-[11px] text-white/80">{{ $t('Mahasiswa', 'Students') }}</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Filter & Cards -->
    <section class="mt-8">
        <div class="sd-card flex flex-col gap-3 p-4 sm:flex-row">
            <label class="relative flex-1">
                <i data-lucide="search" class="pointer-events-none absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                <input
                    type="text"
                    x-model="searchQuery"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 pl-10 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                    placeholder="{{ $t('Cari nama atau kategori formulir...', 'Search forms or categories...') }}"
                />
            </label>
            <select
                x-model="targetFilter"
                class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100 sm:w-48"
                aria-label="{{ $t('Filter sasaran', 'Target filter') }}"
            >
                <option value="Semua">{{ $t('Semua formulir', 'All forms') }}</option>
                <option value="Mahasiswa">{{ $t('Mahasiswa', 'Students') }}</option>
                <option value="Dosen">{{ $t('Dosen', 'Lecturers') }}</option>
            </select>
        </div>

        <div class="mt-5 flex items-end justify-between gap-4">
            <div>
                <h2 class="text-xl font-extrabold text-slate-900">
                    {{ $t('Formulir tersedia', 'Available forms') }}
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    <span x-text="filteredForms.length"></span> {{ $t('formulir sesuai pilihan', 'matching forms') }}
                </p>
            </div>
        </div>

        <div class="reveal-stagger mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3" x-show="filteredForms.length > 0">
            <template x-for="form in filteredForms" :key="form.code">
                <article class="sd-card sd-card-hover group relative flex min-h-80 flex-col overflow-hidden">
                    <div class="h-[3px] bg-gradient-to-r from-brand to-accent"></div>
                    <div class="flex flex-1 flex-col p-5">
                        <div class="flex items-start justify-between gap-3">
                            <span class="grid h-11 w-11 place-items-center rounded-xl bg-sky-50 text-brand-dark shadow-2xs ring-1 ring-sky-200/70">
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
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600" x-text="form.target"></span>
                        </div>

                        <p class="mt-4 text-xs font-bold tracking-wide text-sky-700 uppercase" x-text="form.categoryName"></p>
                        <h3 class="mt-1 text-lg leading-snug font-extrabold text-slate-900" x-text="form.title"></h3>
                        <p class="mt-2 mb-5 line-clamp-3 text-sm leading-6 text-slate-500" x-text="form.desc"></p>

                        <div class="mt-auto space-y-2 border-t border-slate-100 pt-4 text-xs text-slate-500">
                            <div class="flex items-center justify-between gap-3">
                                <span class="flex items-center gap-1.5">
                                    <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                                    <span x-text="`${form.fields.length} pertanyaan`"></span>
                                </span>
                                <span x-text="`${form.tahun} · ${form.periode}`"></span>
                            </div>
                            <div class="flex items-center gap-1.5 font-semibold" :class="form.urgent ? 'text-red-600' : 'text-amber-700'">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 7.5V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h3.5"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h5"/><path d="M17.5 17.5 16 16.3V14"/><circle cx="16" cy="16" r="6"/></svg>
                                <span x-text="`${form.timeLeft} · ${form.deadlineFormatted}`"></span>
                            </div>
                            <template x-if="form.mine > 0">
                                <div class="flex items-center gap-1.5 font-semibold text-emerald-600">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                    <span x-text="`Sudah dikirim ${form.mine}×`"></span>
                                </div>
                            </template>
                        </div>

                        <button
                            type="button"
                            @click="handleAction(form)"
                            class="mt-5 sd-btn-primary w-full gap-2 px-4 py-2.5 text-sm font-semibold"
                        >
                            <span x-text="buttonLabel(form)"></span>
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                        </button>
                    </div>
                </article>
            </template>
        </div>

        <div class="sd-card mt-5 border-dashed px-6 py-16 text-center" x-show="filteredForms.length === 0" x-cloak>
            <i data-lucide="clipboard-list" class="mx-auto h-10 w-10 text-slate-300"></i>
            <h3 class="mt-3 font-bold text-slate-800">{{ $t('Formulir tidak ditemukan', 'No forms found') }}</h3>
            <p class="mt-1 text-sm text-slate-500">{{ $t('Coba ubah kata kunci atau filter sasaran.', 'Try another keyword or target filter.') }}</p>
        </div>
    </section>

    <!-- FillForm Modal (Real DB Submission with Multipart Upload) -->
    <template x-if="modalOpen && selectedForm">
        <div class="fixed inset-0 z-50 grid place-items-end bg-slate-900/40 backdrop-blur-sm sm:place-items-center sm:p-6" @click.self="closeModal()">
            <div role="dialog" aria-modal="true" class="flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-t-2xl border border-slate-200/80 bg-white shadow-[0_24px_64px_-12px_rgba(15,23,42,0.28)] sm:rounded-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 pt-5 pb-4 sm:px-8 sm:pt-6">
                    <div class="min-w-0">
                        <p class="flex flex-wrap items-center gap-2 text-sm text-slate-500">
                            <span x-text="`${selectedForm.categoryName} · ${selectedForm.tahun} · Semester ${selectedForm.periode}`"></span>
                        </p>
                        <h2 class="mt-1 text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl" x-text="selectedForm.title"></h2>
                        <p class="mt-1 text-sm text-slate-500" x-text="selectedForm.desc"></p>
                        <p class="mt-2 inline-flex items-center gap-1.5 rounded-md bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-700">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 7.5V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h3.5"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h5"/><path d="M17.5 17.5 16 16.3V14"/><circle cx="16" cy="16" r="6"/></svg>
                            <span x-text="`Tenggat ${selectedForm.deadlineFormatted} · ${selectedForm.timeLeft}`"></span>
                        </p>
                    </div>
                    <button type="button" @click="closeModal()" aria-label="Tutup" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 hover:border-sky-300 hover:text-sky-700">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>

                <form :action="selectedForm.submitUrl" method="POST" enctype="multipart/form-data" @submit="validateBeforeSubmit($event)" novalidate class="flex min-h-0 flex-col">
                    @csrf
                    <div class="space-y-5 overflow-y-auto px-5 py-6 sm:px-8">
                        <p class="text-xs text-slate-500">
                            Kolom bertanda <span class="text-[#DC3545]">*</span> wajib diisi.
                        </p>

                        <template x-if="serverFail">
                            <div class="rounded-xl bg-red-50 px-4 py-3 text-sm text-[#b02a37]" x-text="serverFail"></div>
                        </template>

                        <template x-for="fld in selectedForm.fields" :key="fld.id">
                            <label class="block">
                                <span class="mb-1.5 block text-sm font-semibold text-slate-800">
                                    <span x-text="fld.label"></span>
                                    <template x-if="fld.required">
                                        <span class="ml-0.5 text-[#DC3545]">*</span>
                                    </template>
                                </span>

                                <template x-if="fld.type === 'textarea'">
                                    <textarea
                                        rows="3"
                                        :name="`values[${fld.id}]`"
                                        x-model="values[fld.id]"
                                        class="w-full rounded-xl border bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                                        :class="fieldErr(fld.id) ? 'border-[#DC3545]' : 'border-slate-200'"></textarea>
                                </template>

                                <template x-if="fld.type === 'select'">
                                    <select
                                        :name="`values[${fld.id}]`"
                                        x-model="values[fld.id]"
                                        class="w-full rounded-xl border bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                                        :class="fieldErr(fld.id) ? 'border-[#DC3545]' : 'border-slate-200'">
                                        <option value="">Pilih…</option>
                                        <template x-for="opt in fld.options || []" :key="opt">
                                            <option :value="opt" x-text="opt"></option>
                                        </template>
                                    </select>
                                </template>

                                <template x-if="fld.type === 'file'">
                                    <div>
                                        <label
                                            class="flex cursor-pointer items-center gap-3 rounded-xl border border-dashed px-4 py-3 text-sm transition hover:border-sky-400 hover:bg-sky-50/50"
                                            :class="fieldErr(fld.id) ? 'border-[#DC3545]' : 'border-slate-300'">
                                            <svg class="h-4 w-4 text-sky-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                                            <span :class="fileNames[fld.id] ? 'font-semibold text-slate-800' : 'text-slate-500'" x-text="fileNames[fld.id] || 'Pilih berkas bukti…'"></span>
                                            <input
                                                type="file"
                                                :name="`files[${fld.id}]`"
                                                accept=".pdf,.jpg,.jpeg,.png"
                                                class="sr-only"
                                                @change="onFileChange(fld.id, $event.target.files[0], $event.target)"
                                            />
                                        </label>
                                        <span class="mt-1 block text-xs text-slate-500">PDF, JPG, atau PNG, maksimal 5 MB.</span>
                                    </div>
                                </template>

                                <template x-if="fld.type !== 'textarea' && fld.type !== 'select' && fld.type !== 'file'">
                                    <input
                                        :type="fld.type"
                                        :name="`values[${fld.id}]`"
                                        x-model="values[fld.id]"
                                        class="w-full rounded-xl border bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                                        :class="fieldErr(fld.id) ? 'border-[#DC3545]' : 'border-slate-200'"
                                    />
                                </template>

                                <span x-show="fieldErr(fld.id)" class="mt-1 block text-xs text-[#DC3545]" x-text="fieldErr(fld.id)"></span>
                            </label>
                        </template>
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:px-8">
                        <button type="button" @click="closeModal()" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                            Batal
                        </button>
                        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-700">
                            Kirim formulir
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</main>
@endsection

@push('scripts')
<script>
function pengisianDataManager() {
    return {
        forms: @json($formsPayload),
        isLoggedIn: @json($isLoggedIn),
        userRole: @json($userRole),
        loginUrl: @json(route('login')),
        panelIsiUrl: @json(route('panel.isi')),
        panelFormulirUrl: @json(route('panel.formulir')),
        searchQuery: @json($q),
        targetFilter: @json($target),
        modalOpen: false,
        selectedForm: null,
        values: {},
        fileNames: {},
        fileErrors: {},
        tried: false,
        serverFail: '',

        init() {
            const reopenCode = @json(session('open_form_code'));
            if (reopenCode) {
                const f = this.forms.find((x) => x.code === reopenCode);
                if (f) {
                    this.openFormModal(f);
                    this.values = @json(old('values', []));
                    const errs = @json($errors->toArray());
                    Object.keys(errs).forEach((k) => {
                        this.fileErrors[k] = errs[k][0];
                    });
                    this.tried = true;
                }
            }
            window.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') this.closeModal();
            });
        },

        get filteredForms() {
            const q = this.searchQuery.trim().toLowerCase();
            return this.forms.filter((f) => {
                const matchesTarget = this.targetFilter === 'Semua' || f.target === this.targetFilter;
                const matchesQuery = !q || `${f.title} ${f.desc} ${f.categoryName}`.toLowerCase().includes(q);
                return matchesTarget && matchesQuery;
            });
        },

        buttonLabel(form) {
            if (form.eligible) {
                return form.mine > 0 ? @js($t('Kirim lagi', 'Submit again')) : @js($t('Isi formulir', 'Complete form'));
            }
            if (this.isLoggedIn) {
                return @js($t('Buka panel formulir', 'Open forms panel'));
            }
            return @js($t('Masuk untuk mengisi', 'Sign in to complete'));
        },

        handleAction(form) {
            if (!this.isLoggedIn) {
                window.location.href = this.loginUrl;
                return;
            }
            if (form.eligible) {
                this.openFormModal(form);
                return;
            }
            window.location.href = this.userRole === 'produsen' ? this.panelIsiUrl : this.panelFormulirUrl;
        },

        openFormModal(form) {
            this.selectedForm = form;
            this.values = {};
            this.fileNames = {};
            this.fileErrors = {};
            this.tried = false;
            this.serverFail = '';
            this.modalOpen = true;
            document.body.style.overflow = 'hidden';
        },

        closeModal() {
            this.modalOpen = false;
            this.selectedForm = null;
            document.body.style.overflow = '';
        },

        onFileChange(fieldId, file, inputEl) {
            if (!file) {
                this.fileNames[fieldId] = '';
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                this.fileErrors[fieldId] = 'Ukuran berkas maksimal 5 MB.';
                this.fileNames[fieldId] = '';
                inputEl.value = '';
                return;
            }
            if (!/\.(pdf|jpe?g|png)$/i.test(file.name)) {
                this.fileErrors[fieldId] = 'Format berkas harus PDF, JPG, atau PNG.';
                this.fileNames[fieldId] = '';
                inputEl.value = '';
                return;
            }
            delete this.fileErrors[fieldId];
            this.fileNames[fieldId] = file.name;
        },

        fieldErr(fieldId) {
            if (this.fileErrors[fieldId]) return this.fileErrors[fieldId];
            if (!this.tried || !this.selectedForm) return '';
            const fld = this.selectedForm.fields.find((x) => x.id === fieldId);
            if (!fld || !fld.required) return '';
            if (fld.type === 'file') {
                return this.fileNames[fieldId] ? '' : 'Lampiran bukti wajib diunggah.';
            }
            const val = String(this.values[fieldId] ?? '').trim();
            return val ? '' : 'Kolom ini wajib diisi.';
        },

        validateBeforeSubmit(event) {
            this.tried = true;
            if (!this.selectedForm) return;
            const hasError = this.selectedForm.fields.some((f) => Boolean(this.fieldErr(f.id)));
            if (hasError) {
                event.preventDefault();
            }
        },
    };
}
</script>
@endpush
