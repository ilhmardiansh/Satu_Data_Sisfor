@extends('layouts.app')

@section('title', 'Pengisian Data - Satu Data Sistem Informasi UNUSA')

@section('content')
@php
    $forms = $forms ?? [];
    $allForms = \App\Services\SatuDataService::getForms();
    $activeForms = array_filter($allForms, fn($f) => $f['is_active']);
    $categoriesCount = count(array_unique(array_column($activeForms, 'kategori')));
    $mhsCount = count(array_filter($activeForms, fn($f) => $f['target'] === 'Mahasiswa'));
@endphp

<main class="mx-auto max-w-[1200px] px-4 py-10 sm:px-8" x-data="pengisianDataManager()">
    <!-- Breadcrumb -->
    <p class="text-sm text-slate-500">
        <a href="{{ route('beranda') }}" class="hover:text-sky-600">Beranda</a>
        <span class="mx-1">/</span>
        <span class="text-sky-600">Pengisian Data</span>
    </p>

    <!-- Hero Banner (Exact PengisianData.tsx) -->
    <section class="relative isolate mt-4 overflow-hidden rounded-3xl bg-gradient-to-br from-benhur via-benhur to-deep text-white shadow-lg shadow-slate-300/60">
        <div aria-hidden="true" class="pointer-events-none absolute -top-24 -left-20 h-72 w-72 rounded-full bg-accent/30 blur-3xl motion-safe:animate-float"></div>
        <div aria-hidden="true" class="pointer-events-none absolute -right-16 -bottom-32 h-80 w-80 rounded-full bg-white/15 blur-3xl motion-safe:animate-float motion-safe:[animation-delay:-8s]"></div>

        <div class="relative z-10 flex flex-col gap-8 p-6 sm:p-9 md:w-[68%] lg:min-h-96 lg:justify-between">
            <div>
                <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1.5 text-xs font-bold tracking-wide text-white uppercase motion-safe:animate-fade-up">
                    <i data-lucide="clipboard-list" class="h-4 w-4"></i>
                    Portal pengumpulan data
                </span>
                <h1 class="mt-4 max-w-2xl text-3xl font-extrabold tracking-tight sm:text-4xl motion-safe:animate-fade-up motion-safe:[animation-delay:90ms]">
                    Pilih dan isi formulir data Anda
                </h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-white/85 sm:text-base motion-safe:animate-fade-up motion-safe:[animation-delay:180ms]">
                    Temukan formulir yang sesuai, periksa tenggat waktu, lalu kirim data dan dokumen pendukung secara terarah.
                </p>
            </div>

            <!-- Stats Bar -->
            <div class="grid max-w-md grid-cols-3 gap-2 motion-safe:animate-fade-up motion-safe:[animation-delay:270ms] sm:gap-3">
                <div class="min-w-0 rounded-2xl bg-white/10 px-1.5 py-3 text-center ring-1 ring-white/10 backdrop-blur-sm sm:p-3">
                    <strong class="block text-2xl font-extrabold">{{ count($activeForms) }}</strong>
                    <span class="mt-1 block text-[11px] text-white/80">Form aktif</span>
                </div>
                <div class="min-w-0 rounded-2xl bg-white/10 px-1.5 py-3 text-center ring-1 ring-white/10 backdrop-blur-sm sm:p-3">
                    <strong class="block text-2xl font-extrabold">{{ $categoriesCount }}</strong>
                    <span class="mt-1 block text-[11px] text-white/80">Kategori</span>
                </div>
                <div class="min-w-0 rounded-2xl bg-white/10 px-1.5 py-3 text-center ring-1 ring-white/10 backdrop-blur-sm sm:p-3">
                    <strong class="block text-2xl font-extrabold">{{ $mhsCount }}</strong>
                    <span class="mt-1 block text-[11px] text-white/80">Mahasiswa</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Filter Section (Exact PengisianData.tsx) -->
    <section class="mt-8">
        <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-surface p-4 shadow-sm sm:flex-row">
            <label class="relative flex-1">
                <i data-lucide="search" class="pointer-events-none absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                <input
                    type="text"
                    x-model="searchQuery"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 pl-10 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                    placeholder="Cari nama atau kategori formulir..."
                />
            </label>
            <select
                x-model="targetFilter"
                class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100 w-full sm:w-48"
                aria-label="Filter sasaran"
            >
                <option value="Semua">Semua formulir</option>
                <option value="Mahasiswa">Mahasiswa</option>
                <option value="Dosen">Dosen</option>
            </select>
        </div>

        <div class="mt-5 flex items-end justify-between gap-4">
            <div>
                <h2 class="text-xl font-extrabold text-slate-900">
                    Formulir tersedia
                </h2>
                <p class="mt-1 text-sm text-slate-500" x-text="`${filteredForms.length} formulir sesuai pilihan`"></p>
            </div>
        </div>

        <!-- Forms Grid (Exact PengisianData.tsx) -->
        <div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3" x-show="filteredForms.length > 0">
            <template x-for="form in filteredForms" :key="form.id">
                <article class="group flex min-h-80 flex-col overflow-hidden rounded-2xl border border-slate-200 bg-surface shadow-sm transition duration-300 ease-out hover:border-sky-300 hover:shadow-lg hover:shadow-sky-900/5 motion-safe:hover:-translate-y-1">
                    <div class="h-1.5 bg-gradient-to-r from-sky-500 to-blue-700"></div>
                    <div class="flex flex-1 flex-col p-5">
                        <div class="flex items-start justify-between gap-3">
                            <span class="grid h-11 w-11 place-items-center rounded-xl bg-sky-100/80 text-sky-700">
                                <template x-if="form.target === 'Mahasiswa'">
                                    <i data-lucide="graduation-cap" class="h-5 w-5"></i>
                                </template>
                                <template x-if="form.target === 'Dosen'">
                                    <i data-lucide="briefcase" class="h-5 w-5"></i>
                                </template>
                                <template x-if="form.target === 'Semua'">
                                    <i data-lucide="users" class="h-5 w-5"></i>
                                </template>
                            </span>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600" x-text="form.target"></span>
                        </div>

                        <p class="mt-4 text-xs font-bold tracking-wide text-sky-700 uppercase" x-text="getCategoryName(form.kategori)"></p>
                        <h3 class="mt-1 text-lg leading-snug font-extrabold text-slate-900" x-text="form.title"></h3>
                        <p class="mt-2 mb-5 line-clamp-3 text-sm leading-6 text-slate-500" x-text="form.desc"></p>

                        <div class="mt-auto space-y-2 border-t border-slate-100 pt-4 text-xs text-slate-500">
                            <div class="flex items-center justify-between gap-3">
                                <span class="flex items-center gap-1.5">
                                    <i data-lucide="file-text" class="h-4 w-4 text-slate-400"></i>
                                    <span x-text="`${form.fields ? form.fields.length : 0} pertanyaan`"></span>
                                </span>
                                <span x-text="`${form.tahun} · ${form.periode}`"></span>
                            </div>
                            <div class="flex items-center gap-1.5 font-semibold" :class="form.days_left <= 7 ? 'text-red-600' : 'text-amber-700'">
                                <i data-lucide="calendar" class="h-4 w-4"></i>
                                <span x-text="`${form.deadline_label || form.deadline} (${form.days_left} hari lagi)`"></span>
                            </div>
                        </div>

                        @if(session()->has('user'))
                            <button
                                type="button"
                                @click="openFormModal(form)"
                                class="inline-flex items-center justify-center gap-2 rounded-xl text-sm font-semibold transition motion-safe:active:scale-[0.97] bg-brand px-4 py-2.5 text-white hover:bg-sky-700 mt-5 w-full"
                            >
                                Isi formulir
                                <i data-lucide="arrow-right" class="h-4 w-4"></i>
                            </button>
                        @else
                            <a
                                href="{{ route('login') }}"
                                class="inline-flex items-center justify-center gap-2 rounded-xl text-sm font-semibold transition motion-safe:active:scale-[0.97] bg-brand px-4 py-2.5 text-white hover:bg-sky-700 mt-5 w-full"
                            >
                                Masuk untuk mengisi
                                <i data-lucide="arrow-right" class="h-4 w-4"></i>
                            </a>
                        @endif
                    </div>
                </article>
            </template>
        </div>

        <!-- Empty State -->
        <div class="mt-5 rounded-2xl border border-dashed border-slate-300 bg-surface px-6 py-16 text-center" x-show="filteredForms.length === 0">
            <i data-lucide="clipboard-list" class="mx-auto h-10 w-10 text-slate-300"></i>
            <h3 class="mt-3 font-bold text-slate-800">Formulir tidak ditemukan</h3>
            <p class="mt-1 text-sm text-slate-500">Coba ubah kata kunci atau filter sasaran.</p>
        </div>
    </section>

    <!-- Form Modal (FillForm) -->
    <div
        x-show="modalOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 grid place-items-end bg-slate-900/40 backdrop-blur-sm sm:place-items-center sm:p-6"
        style="display: none;"
    >
        <div
            @click.outside="modalOpen = false"
            class="flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-t-2xl bg-white shadow-2xl sm:rounded-2xl"
        >
            <template x-if="selectedForm">
                <div>
                    <!-- Modal Header -->
                    <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 pt-5 pb-4 sm:px-8 sm:pt-6">
                        <div class="min-w-0">
                            <p class="flex flex-wrap items-center gap-2 text-sm text-slate-500">
                                <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold" x-text="selectedForm.target"></span>
                                <span x-text="getCategoryName(selectedForm.kategori)"></span>
                            </p>
                            <h2 class="mt-1 text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl" x-text="selectedForm.title"></h2>
                            <p class="mt-1 text-sm text-slate-500" x-text="selectedForm.desc"></p>
                        </div>
                        <button type="button" @click="modalOpen = false" class="grid h-9 w-9 shrink-0 place-items-center rounded-xl border border-slate-200 bg-white text-slate-600 hover:border-sky-300 hover:text-sky-700">
                            <i data-lucide="x" class="h-4 w-4"></i>
                        </button>
                    </div>

                    <!-- Modal Body: Dynamic Fields -->
                    <form @submit.prevent="submitForm()" class="overflow-y-auto px-5 py-6 sm:px-8 space-y-4 max-h-[60vh]">
                        <template x-for="fld in selectedForm.fields" :key="fld.id">
                            <label class="block">
                                <span class="mb-1.5 block text-sm font-semibold text-slate-800">
                                    <span x-text="fld.label"></span>
                                    <template x-if="fld.required">
                                        <span class="ml-0.5 text-[#DC3545]">*</span>
                                    </template>
                                </span>

                                <template x-if="fld.type === 'text'">
                                    <input type="text" :placeholder="fld.placeholder || ''" :required="fld.required" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                                </template>

                                <template x-if="fld.type === 'number'">
                                    <input type="number" :placeholder="fld.placeholder || ''" :required="fld.required" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                                </template>

                                <template x-if="fld.type === 'select'">
                                    <select :required="fld.required" class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                                        <option value="">Pilih...</option>
                                        <template x-for="opt in fld.options" :key="opt">
                                            <option :value="opt" x-text="opt"></option>
                                        </template>
                                    </select>
                                </template>

                                <template x-if="fld.type === 'date'">
                                    <input type="date" :required="fld.required" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                                </template>

                                <template x-if="fld.type === 'textarea'">
                                    <textarea rows="3" :placeholder="fld.placeholder || ''" :required="fld.required" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-sky-400 focus:ring-4 focus:ring-sky-100"></textarea>
                                </template>

                                <template x-if="fld.type === 'file'">
                                    <input type="file" :required="fld.required" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm text-slate-500 file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-sky-50 file:text-sky-700 hover:file:bg-sky-100" />
                                </template>
                            </label>
                        </template>

                        <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 -mx-5 sm:-mx-8 -mb-6 px-5 py-4 sm:px-8 mt-6">
                            <button type="button" @click="modalOpen = false" class="inline-flex items-center justify-center gap-2 rounded-xl text-sm font-semibold transition border border-slate-200 bg-white px-4 py-2.5 text-slate-700 hover:bg-slate-50">
                                Batal
                            </button>
                            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl text-sm font-semibold transition bg-brand px-4 py-2.5 text-white hover:bg-sky-700">
                                Kirim data
                            </button>
                        </div>
                    </form>
                </div>
            </template>
        </div>
    </div>
</main>
@endsection

@push('scripts')
<script>
    function pengisianDataManager() {
        return {
            forms: @json($allForms),
            categories: @json($categories ?? []),
            searchQuery: '{{ $q ?? "" }}',
            targetFilter: '{{ $target ?? "Semua" }}',
            modalOpen: false,
            selectedForm: null,

            get filteredForms() {
                const q = this.searchQuery.trim().toLowerCase();
                const target = this.targetFilter;

                return this.forms.filter(f => {
                    const matchesTarget = (target === 'Semua' || f.target === target);
                    const catName = this.getCategoryName(f.kategori).toLowerCase();
                    const matchesQuery = !q || (
                        (f.title && f.title.toLowerCase().includes(q)) ||
                        (f.desc && f.desc.toLowerCase().includes(q)) ||
                        catName.includes(q)
                    );
                    return matchesTarget && matchesQuery && f.is_active;
                });
            },

            getCategoryName(code) {
                const c = this.categories.find(x => x.code === code);
                return c ? c.name : code;
            },

            openFormModal(form) {
                this.selectedForm = form;
                this.modalOpen = true;
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            },

            submitForm() {
                alert('Data formulir berhasil dikirim!');
                this.modalOpen = false;
            }
        };
    }
</script>
@endpush
