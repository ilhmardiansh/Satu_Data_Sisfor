@extends('layouts.app')

@section('title', 'Satu Data Sistem Informasi UNUSA')

@section('content')
<main x-data="dosenModalData()">
    <!-- Hero Section -->
    <section class="relative isolate overflow-hidden">
        <img
            src="{{ asset('images/kampus-unusa.jpg') }}"
            alt="Gedung kampus Universitas Nahdlatul Ulama Surabaya"
            class="absolute inset-0 h-full w-full origin-center object-cover motion-safe:animate-ken-burns"
        />
        <div class="absolute inset-0 bg-gradient-to-r from-benhur/95 via-benhur/82 to-deep/45"></div>
        <div data-scene="network"
             aria-hidden="true"
             class="pointer-events-none absolute inset-y-2 right-0 hidden w-[52%] opacity-85 md:block"
             style="mask-image: radial-gradient(closest-side, #000 58%, transparent 98%); -webkit-mask-image: radial-gradient(closest-side, #000 58%, transparent 98%);"></div>
        <div class="relative z-10 mx-auto max-w-[1200px] px-4 py-16 text-white sm:px-8 sm:py-22">
            <h1 class="max-w-3xl text-[32px] leading-[1.12] font-extrabold tracking-tight drop-shadow-xs sm:text-[46px] motion-safe:animate-fade-up">
                Program Studi <span class="text-accent-soft">Sistem Informasi</span> UNUSA
            </h1>
            <p class="mt-5 max-w-2xl text-base leading-relaxed text-white/90 sm:text-lg motion-safe:animate-fade-up motion-safe:[animation-delay:120ms]">
                {{ $t(
                    'Tempat ide, teknologi, dan bisnis bertemu. Ikuti kabar terbaru prodi, rayakan capaian mahasiswa, kenali para dosen, dan jelajahi kurikulum yang menyiapkan Anda menjadi penggerak transformasi digital.',
                    'Where ideas, technology, and business meet. Follow the latest program news, celebrate student achievements, meet our lecturers, and explore a curriculum that prepares you to drive digital transformation.'
                ) }}
            </p>
        </div>
    </section>

    <!-- Berita Prodi -->
    <section id="berita" class="scroll-mt-16">
        <div class="mx-auto max-w-[1200px] px-4 py-14 sm:px-8 sm:py-16">
            <div class="reveal mb-8 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 class="text-[24px] font-extrabold tracking-tight text-ink sm:text-[28px]">
                        {{ $t('Berita Prodi', 'Program News') }}
                    </h2>
                </div>
                <a href="{{ route('berita') }}" class="group/link inline-flex shrink-0 items-center gap-1.5 rounded-full border border-slate-200/80 bg-white px-3.5 py-1.5 text-sm font-semibold text-brand shadow-2xs transition hover:border-sky-200 hover:bg-sky-50/70 hover:text-brand-dark">
                    {{ $t('Semua berita', 'All news') }}
                    <i data-lucide="arrow-right" class="h-4 w-4 transition-transform duration-200 motion-safe:group-hover/link:translate-x-0.5"></i>
                </a>
            </div>

            @if($berita->isNotEmpty())
                <div class="reveal-stagger grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach($berita as $n)
                        <article class="sd-card sd-card-hover group flex flex-col overflow-hidden">
                            <div class="relative aspect-[16/10] overflow-hidden bg-slate-100">
                                <img
                                    src="{{ $n->image_url }}"
                                    alt="{{ $n->title }}"
                                    class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                                />
                                <span class="absolute top-3 left-3 rounded-lg bg-brand/95 px-2.5 py-1 text-[11px] font-bold text-white shadow-sm backdrop-blur-sm">
                                    {{ $n->badge }}
                                </span>
                            </div>
                            <div class="flex flex-1 flex-col p-5">
                                <div class="flex items-center gap-4 text-xs font-medium text-[#62748E]">
                                    <span class="flex items-center gap-1.5">
                                        <i data-lucide="calendar-days" class="h-3.5 w-3.5 text-slate-400"></i>
                                        {{ $n->date_label }}
                                    </span>
                                    <span class="flex items-center gap-1.5">
                                        <i data-lucide="clock" class="h-3.5 w-3.5 text-slate-400"></i>
                                        {{ $n->read_minutes }} {{ $t('menit baca', 'min read') }}
                                    </span>
                                </div>
                                <h3 class="mt-3 line-clamp-3 text-[17px] leading-snug font-bold tracking-tight text-ink transition-colors group-hover:text-brand">
                                    {{ $n->title }}
                                </h3>
                                <p class="mt-2 line-clamp-2 text-sm leading-relaxed text-[#62748E]">
                                    {{ $n->excerpt }}
                                </p>
                                <a href="{{ route('berita.show', $n->code) }}" class="mt-auto flex items-center gap-1 self-end pt-5 text-sm font-bold text-brand transition motion-safe:active:scale-[0.97]">
                                    {{ $t('Baca', 'Read') }}
                                    <i data-lucide="arrow-right" class="h-4 w-4 transition-transform duration-200 motion-safe:group-hover:translate-x-0.5"></i>
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <p class="rounded-2xl border border-dashed border-slate-200 bg-white p-6 text-sm text-[#62748E]">
                    {{ $t('Belum ada berita prodi.', 'No program news yet.') }}
                </p>
            @endif
        </div>
    </section>

    <!-- Prestasi Mahasiswa -->
    <section id="prestasi" class="scroll-mt-16 border-y border-slate-200/70 bg-white">
        <div class="mx-auto max-w-[1200px] px-4 py-14 sm:px-8 sm:py-16">
            <div class="reveal mb-8 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 class="text-[24px] font-extrabold tracking-tight text-ink sm:text-[28px]">
                        {{ $t('Prestasi Mahasiswa', 'Student Achievements') }}
                    </h2>
                </div>
            </div>

            @if($prestasi->isNotEmpty())
                <div class="reveal-stagger grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($prestasi as $x)
                        @php
                            $tingkatCls = match($x->tingkat) {
                                'Internasional' => 'bg-violet-50/95 text-violet-700 ring-1 ring-violet-200/70',
                                'Nasional' => 'bg-sky-50/95 text-brand-dark ring-1 ring-sky-200/70',
                                default => 'bg-emerald-50/95 text-emerald-700 ring-1 ring-emerald-200/70',
                            };
                        @endphp
                        <article class="sd-card sd-card-hover group flex min-w-0 flex-col overflow-hidden hover:border-amber-200">
                            <div class="relative aspect-[16/10] overflow-hidden bg-gradient-to-br from-amber-50 to-amber-100" x-data="{ imgFailed: false }">
                                @if($x->foto)
                                    <img
                                        x-show="!imgFailed"
                                        x-on:error="imgFailed = true"
                                        src="{{ $x->foto }}"
                                        alt="Dokumentasi {{ $x->judul }}"
                                        loading="lazy"
                                        class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                                    />
                                    <div x-show="!imgFailed" class="absolute inset-x-0 bottom-0 h-1/2 bg-gradient-to-t from-black/50 to-transparent"></div>
                                    <span x-show="imgFailed" x-cloak class="grid h-full w-full place-items-center text-amber-500">
                                        <i data-lucide="award" class="h-10 w-10"></i>
                                    </span>
                                @else
                                    <span class="grid h-full w-full place-items-center text-amber-500">
                                        <i data-lucide="award" class="h-10 w-10"></i>
                                    </span>
                                @endif

                                <span class="absolute top-3 right-3 rounded-full px-2.5 py-1 text-xs font-semibold shadow-sm backdrop-blur-sm {{ $tingkatCls }}">
                                    {{ $x->tingkat }}
                                </span>
                                <span class="absolute bottom-3 left-3 inline-flex items-center gap-1.5 rounded-full bg-amber-400 px-3 py-1 text-xs font-extrabold text-amber-950 shadow-sm">
                                    <i data-lucide="award" class="h-3.5 w-3.5"></i>
                                    {{ $x->peringkat }}
                                </span>
                            </div>
                            <div class="flex flex-1 flex-col p-5">
                                <h3 class="leading-snug font-bold tracking-tight text-ink">{{ $x->judul }}</h3>
                                <p class="mt-auto pt-3 text-sm font-medium text-[#62748E]">
                                    {{ $x->nama }} · {{ $x->tahun }}
                                </p>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <p class="rounded-2xl border border-dashed border-slate-200 p-6 text-sm text-[#62748E]">
                    {{ $t('Belum ada prestasi mahasiswa.', 'No achievements yet.') }}
                </p>
            @endif
        </div>
    </section>

    <!-- Data Dosen -->
    <section id="dosen" class="scroll-mt-16">
        <div class="mx-auto max-w-[1200px] px-4 py-14 sm:px-8 sm:py-16">
            <div class="reveal mb-8 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 class="text-[24px] font-extrabold tracking-tight text-ink sm:text-[28px]">
                        {{ $t('Data Dosen', 'Lecturers') }}
                    </h2>
                </div>
            </div>

            @if($dosen->isNotEmpty())
                <div class="reveal-stagger grid grid-cols-1 gap-4 min-[420px]:grid-cols-2 lg:grid-cols-4">
                    @foreach($dosen as $d)
                        @php
                            $dosenJson = [
                                'nama' => $d->nama,
                                'nidn' => $d->nidn,
                                'jabatan' => $d->jabatan,
                                'pendidikan' => $d->pendidikan,
                                'bidang' => $d->bidang,
                                'foto' => $d->foto,
                                'email' => $d->email,
                                'mataKuliah' => $d->mata_kuliah,
                                'profil' => $d->profil,
                                'initials' => $d->initials(),
                            ];
                        @endphp
                        <article class="sd-card sd-card-hover group flex h-full min-w-0 flex-col items-center px-5 pt-7 pb-5 text-center">
                            <span x-data="{ imgFailed: false }" class="grid h-24 w-24 shrink-0 place-items-center overflow-hidden rounded-full bg-sky-100/80 text-2xl font-extrabold text-brand-dark shadow-sm ring-4 ring-white outline outline-1 outline-sky-200/80">
                                @if($d->foto)
                                    <img x-show="!imgFailed" x-on:error="imgFailed = true" src="{{ $d->foto }}" alt="Foto {{ $d->nama }}" class="h-full w-full object-cover" loading="lazy" />
                                    <span x-show="imgFailed" x-cloak>{{ $d->initials() }}</span>
                                @else
                                    <span>{{ $d->initials() }}</span>
                                @endif
                            </span>
                            <h3 class="mt-4 leading-snug font-bold tracking-tight text-ink">{{ $d->nama }}</h3>
                            <p class="mt-1 text-[13px] text-[#62748E]">
                                {{ $d->jabatan }} · {{ $d->pendidikan }}
                            </p>
                            <span class="mt-3 inline-flex rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-brand-dark ring-1 ring-sky-200/70">
                                {{ $d->bidang }}
                            </span>
                            <div class="mt-auto w-full pt-5">
                                <button
                                    type="button"
                                    @click="openModal(@js($dosenJson))"
                                    aria-haspopup="dialog"
                                    class="inline-flex w-full items-center justify-center gap-1.5 rounded-xl border border-slate-200/90 bg-slate-50/70 px-4 py-2.5 text-sm font-semibold text-brand shadow-2xs transition hover:border-brand hover:bg-sky-50 motion-safe:active:scale-[0.98]">
                                    {{ $t('Lebih lanjut', 'Learn more') }}
                                    <i data-lucide="arrow-right" class="h-4 w-4 transition-transform motion-safe:group-hover:translate-x-0.5"></i>
                                </button>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <p class="rounded-2xl border border-dashed border-slate-200 bg-white p-6 text-sm text-[#62748E]">
                    {{ $t('Data dosen belum tersedia.', 'No lecturers yet.') }}
                </p>
            @endif
        </div>
    </section>

    <!-- Kurikulum -->
    @php
        $kurikulumList = $kurikulum->map(fn ($c) => [
            'id' => $c->id,
            'tahun' => $c->tahun,
            'semester' => $c->semester,
            'kode' => $c->kode,
            'nama' => $c->nama,
            'sks' => $c->sks,
            'pilihan' => $c->pilihan,
        ])->values()->all();
        $years = $kurikulum->pluck('tahun')->unique()->sortDesc()->values()->all();
        $activeYear = $years[0] ?? date('Y');
    @endphp
    <section id="kurikulum" class="scroll-mt-20 border-t border-slate-200/70 bg-white" x-data="curriculumData(@js($kurikulumList), {{ $activeYear }})">
        <div class="mx-auto max-w-[1200px] px-4 py-14 sm:px-8 sm:py-16">
            <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 class="text-[24px] font-extrabold tracking-tight text-ink sm:text-[28px]">
                        {{ $t('Kurikulum', 'Curriculum') }}
                    </h2>
                    <p class="mt-1.5 text-[15px] text-[#62748E]">
                        {{ $t(
                            'Pilih semester untuk melihat mata kuliah wajib dan pilihan.',
                            'Choose a semester to see required and elective courses.'
                        ) }}
                    </p>
                </div>
                @if(count($years) > 1)
                    <select
                        aria-label="{{ $t('Tahun kurikulum', 'Curriculum year') }}"
                        class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                        x-model="selectedYear"
                        @change="openSemesters = []">
                        @foreach($years as $y)
                            <option value="{{ $y }}">{{ $t('Kurikulum', 'Curriculum') }} {{ $y }}</option>
                        @endforeach
                    </select>
                @endif
            </div>

            <template x-if="semesters.length === 0">
                <p class="rounded-2xl border border-dashed border-[#E2E8F0] p-6 text-sm text-[#62748E]">
                    {{ $t(
                        'Kurikulum belum dipublikasikan oleh program studi.',
                        'The program has not published its curriculum yet.'
                    ) }}
                </p>
            </template>

            <div class="space-y-3" x-show="semesters.length > 0">
                <template x-for="sem in semesters" :key="sem">
                    <div class="overflow-hidden rounded-2xl border bg-[#f8fafd]/75 transition-all"
                         :class="isOpen(sem) ? 'border-sky-300 bg-white shadow-[0_6px_20px_-6px_rgba(68,114,208,0.12)]' : 'border-slate-200/80 hover:border-slate-300'">
                        <button
                            type="button"
                            @click="toggle(sem)"
                            :aria-expanded="isOpen(sem)"
                            class="flex w-full items-center gap-4 px-5 py-4 text-left transition hover:bg-white">
                            <span
                                class="grid h-10 w-10 shrink-0 place-items-center rounded-xl text-sm font-extrabold transition"
                                :class="isOpen(sem) ? 'bg-gradient-to-b from-brand to-brand-dark text-white shadow-sm' : 'bg-sky-50 text-brand-dark ring-1 ring-sky-200/70'"
                                x-text="sem">
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block font-bold text-ink" x-text="'Semester ' + sem"></span>
                                <span class="block text-sm text-[#62748E]" x-text="getSemesterSummary(sem)"></span>
                            </span>
                            <svg class="h-5 w-5 shrink-0 text-[#62748E] transition-transform duration-200"
                                 :class="{ 'rotate-180': isOpen(sem) }"
                                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                        </button>

                        <div x-show="isOpen(sem)" x-cloak>
                            <ul class="divide-y divide-slate-100 border-t border-slate-100">
                                <template x-for="c in getCourses(sem)" :key="c.id">
                                    <li class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-x-4 gap-y-1 px-5 py-3 sm:grid-cols-[7rem_minmax(0,1fr)_5rem_4rem]">
                                        <span class="flex items-center gap-2 text-xs font-semibold text-[#62748E] sm:text-sm">
                                            <svg class="h-4 w-4 shrink-0 text-sky-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                                            <span x-text="c.kode"></span>
                                        </span>
                                        <span class="row-start-2 min-w-0 font-semibold text-ink sm:row-start-auto" x-text="c.nama"></span>
                                        <span
                                            class="justify-self-end rounded-md px-2 py-0.5 text-xs font-semibold sm:justify-self-start"
                                            :class="c.pilihan ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-600'"
                                            x-text="c.pilihan ? @js($t('Pilihan', 'Elective')) : @js($t('Wajib', 'Required'))">
                                        </span>
                                        <span class="row-start-2 text-right text-sm font-bold text-ink sm:row-start-auto" x-text="c.sks + ' SKS'"></span>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </section>

    <!-- Modal Dosen Detail -->
    <div
        x-show="modalOpen"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-50 grid place-items-end bg-slate-900/40 backdrop-blur-sm sm:place-items-center sm:p-6"
        @keydown.escape.window="closeModal()"
        @click.self="closeModal()">
        <div
            role="dialog"
            aria-modal="true"
            class="flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-t-2xl border border-slate-200/80 bg-white shadow-[0_24px_64px_-12px_rgba(15,23,42,0.28)] sm:rounded-2xl">
            <template x-if="selectedDosen">
                <div class="overflow-y-auto">
                    <div class="relative bg-gradient-to-br from-benhur via-[#062f78] to-deep px-6 pt-6 pb-16 text-white sm:px-8">
                        <div class="flex items-start justify-between gap-4">
                            <p class="text-xs font-semibold tracking-wider text-white/80 uppercase">
                                {{ $t('Profil Dosen', 'Lecturer Profile') }}
                            </p>
                            <button
                                type="button"
                                @click="closeModal()"
                                aria-label="{{ $t('Tutup', 'Close') }}"
                                class="grid h-9 w-9 place-items-center rounded-xl border border-white/15 bg-white/10 backdrop-blur-md transition hover:bg-white/20">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="relative -mt-12 px-6 pb-7 sm:px-8">
                        <span class="grid h-28 w-28 shrink-0 place-items-center overflow-hidden rounded-2xl bg-sky-100/80 text-3xl font-extrabold text-brand-dark ring-4 ring-white shadow-lg">
                            <template x-if="selectedDosen.foto && !modalImgFailed">
                                <img :src="selectedDosen.foto" x-on:error="modalImgFailed = true" :alt="'Foto ' + selectedDosen.nama" class="h-full w-full object-cover" />
                            </template>
                            <template x-if="!selectedDosen.foto || modalImgFailed">
                                <span x-text="selectedDosen.initials"></span>
                            </template>
                        </span>

                        <h2 class="mt-4 text-2xl font-extrabold tracking-tight text-ink" x-text="selectedDosen.nama"></h2>
                        <p class="mt-1 text-sm text-[#62748E]">
                            <span x-text="selectedDosen.jabatan"></span> · {{ $t('Program Studi Sistem Informasi', 'Information Systems Program') }}
                        </p>
                        <span class="mt-3 inline-flex rounded-full bg-sky-100/80 px-3 py-1 text-xs font-semibold text-brand-dark" x-text="selectedDosen.bidang"></span>

                        <template x-if="selectedDosen.profil">
                            <p class="mt-5 text-sm leading-relaxed whitespace-pre-line text-[#45556C]" x-text="selectedDosen.profil"></p>
                        </template>

                        <dl class="mt-6 grid grid-cols-1 gap-4 rounded-2xl border border-[#E2E8F0] bg-[#F8FAFC] p-5 sm:grid-cols-2">
                            <div class="min-w-0">
                                <dt class="text-xs font-semibold tracking-wide text-[#62748E] uppercase">NIDN</dt>
                                <dd class="mt-1 font-mono text-sm font-medium break-words text-ink" x-text="selectedDosen.nidn"></dd>
                            </div>
                            <div class="min-w-0">
                                <dt class="text-xs font-semibold tracking-wide text-[#62748E] uppercase">{{ $t('Pendidikan terakhir', 'Highest degree') }}</dt>
                                <dd class="mt-1 text-sm font-medium break-words text-ink" x-text="selectedDosen.pendidikan"></dd>
                            </div>
                            <div class="min-w-0">
                                <dt class="text-xs font-semibold tracking-wide text-[#62748E] uppercase">{{ $t('Jabatan fungsional', 'Academic rank') }}</dt>
                                <dd class="mt-1 text-sm font-medium break-words text-ink" x-text="selectedDosen.jabatan"></dd>
                            </div>
                            <div class="min-w-0">
                                <dt class="text-xs font-semibold tracking-wide text-[#62748E] uppercase">{{ $t('Bidang keahlian', 'Expertise') }}</dt>
                                <dd class="mt-1 text-sm font-medium break-words text-ink" x-text="selectedDosen.bidang"></dd>
                            </div>
                            <template x-if="selectedDosen.email">
                                <div class="min-w-0">
                                    <dt class="text-xs font-semibold tracking-wide text-[#62748E] uppercase">Email</dt>
                                    <dd class="mt-1 text-sm font-medium break-words text-ink">
                                        <a :href="'mailto:' + selectedDosen.email" class="inline-flex items-center gap-1.5 text-brand hover:underline">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                                            <span x-text="selectedDosen.email"></span>
                                        </a>
                                    </dd>
                                </div>
                            </template>
                        </dl>

                        <template x-if="selectedDosen.mataKuliah">
                            <div class="mt-6">
                                <h3 class="text-sm font-bold text-ink">{{ $t('Mata kuliah yang diampu', 'Courses taught') }}</h3>
                                <ul class="mt-3 flex flex-wrap gap-2">
                                    <template x-for="mk in selectedDosen.mataKuliah.split(',').map(x => x.trim()).filter(Boolean)" :key="mk">
                                        <li class="rounded-lg border border-[#E2E8F0] px-3 py-1.5 text-sm text-[#45556C]" x-text="mk"></li>
                                    </template>
                                </ul>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>
</main>
@endsection

@push('scripts')
<script>
    function dosenModalData() {
        return {
            modalOpen: false,
            modalImgFailed: false,
            selectedDosen: null,
            openModal(dosen) {
                this.selectedDosen = dosen;
                this.modalImgFailed = false;
                this.modalOpen = true;
            },
            closeModal() {
                this.modalOpen = false;
                this.selectedDosen = null;
            },
        };
    }

    function curriculumData(rawCourses, initialYear) {
        return {
            courses: rawCourses,
            selectedYear: initialYear,
            openSemesters: [],
            get list() {
                return this.courses
                    .filter((c) => Number(c.tahun) === Number(this.selectedYear))
                    .sort((a, b) => a.semester - b.semester || a.kode.localeCompare(b.kode));
            },
            get semesters() {
                return [...new Set(this.list.map((c) => c.semester))];
            },
            isOpen(sem) {
                return this.openSemesters.includes(sem);
            },
            toggle(sem) {
                if (this.isOpen(sem)) {
                    this.openSemesters = this.openSemesters.filter((x) => x !== sem);
                } else {
                    this.openSemesters.push(sem);
                }
            },
            getCourses(sem) {
                return this.list.filter((c) => c.semester === sem);
            },
            getSemesterSummary(sem) {
                const items = this.getCourses(sem);
                const totalSks = items.reduce((sum, c) => sum + Number(c.sks), 0);
                return `${items.length} {{ $t('mata kuliah', 'courses') }} · ${totalSks} SKS`;
            },
        };
    }
</script>
@endpush
