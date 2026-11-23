@extends('layouts.app')

@section('title', 'Beranda - Program Studi Sistem Informasi UNUSA')

@section('content')
<main x-data="dosenModalData()">
    <!-- Hero Section (Exact App.tsx) -->
    <section class="relative isolate overflow-hidden">
        <img
            src="https://images.unsplash.com/photo-1562774053-701939374585?auto=format&fit=crop&w=1600&q=80"
            alt="Gedung kampus Universitas Nahdlatul Ulama Surabaya"
            class="absolute inset-0 h-full w-full origin-center object-cover motion-safe:animate-ken-burns"
        />
        <div class="absolute inset-0 bg-gradient-to-r from-benhur/95 via-benhur/80 to-deep/45"></div>
        <div class="relative mx-auto max-w-[1200px] px-4 py-16 text-white sm:px-8 sm:py-20">
            <h1 class="max-w-3xl text-[32px] leading-[1.15] font-extrabold tracking-tight sm:text-[44px] motion-safe:animate-fade-up">
                Program Studi <span class="text-accent-soft">Sistem Informasi</span> UNUSA
            </h1>
            <p class="mt-5 max-w-2xl text-base leading-relaxed text-white/90 sm:text-lg motion-safe:animate-fade-up motion-safe:[animation-delay:120ms]">
                Tempat ide, teknologi, dan bisnis bertemu. Ikuti kabar terbaru prodi, rayakan capaian mahasiswa, kenali para dosen, dan jelajahi kurikulum yang menyiapkan Anda menjadi penggerak transformasi digital.
            </p>
        </div>
    </section>

    <!-- Berita Prodi Section (Exact App.tsx) -->
    <section id="berita" class="scroll-mt-16">
        <div class="mx-auto max-w-[1200px] px-4 py-14 sm:px-8 sm:py-16">
            <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 class="text-[24px] font-extrabold tracking-tight text-ink sm:text-[28px]">
                        Berita Prodi
                    </h2>
                </div>
                <a href="{{ route('berita') }}" class="group/link flex shrink-0 items-center gap-1.5 text-sm font-semibold text-brand hover:text-sky-700">
                    Semua berita
                    <i data-lucide="arrow-right" class="h-4 w-4 transition-transform duration-200 motion-safe:group-hover/link:translate-x-0.5"></i>
                </a>
            </div>

            @if(count($berita))
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach(array_slice($berita, 0, 3) as $n)
                        <article class="group flex flex-col overflow-hidden rounded-2xl border border-[#E2E8F0] bg-surface transition duration-300 ease-out hover:shadow-xl hover:shadow-slate-200 motion-safe:hover:-translate-y-1">
                            <div class="relative aspect-[16/10] overflow-hidden">
                                <img
                                    src="{{ $n['img'] }}"
                                    alt=""
                                    class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                                />
                                <span class="absolute top-3 left-3 rounded-md bg-brand px-2.5 py-1 text-[11px] font-bold text-white">
                                    {{ $n['badge'] }}
                                </span>
                            </div>
                            <div class="flex flex-1 flex-col p-5">
                                <div class="flex items-center gap-4 text-xs text-[#62748E]">
                                    <span class="flex items-center gap-1.5">
                                        <i data-lucide="calendar-days" class="h-3.5 w-3.5"></i>
                                        {{ $n['date'] }}
                                    </span>
                                    <span class="flex items-center gap-1.5">
                                        <i data-lucide="clock" class="h-3.5 w-3.5"></i>
                                        {{ $n['read'] }} menit baca
                                    </span>
                                </div>
                                <h3 class="mt-3 line-clamp-3 text-[17px] leading-snug font-bold group-hover:text-brand">
                                    {{ $n['title'] }}
                                </h3>
                                <p class="mt-2 line-clamp-2 text-sm text-[#62748E]">
                                    {{ $n['ex'] }}
                                </p>
                                <a href="{{ route('berita.show', $n['id']) }}" class="mt-auto flex items-center gap-1 self-end pt-5 text-sm font-bold text-brand transition motion-safe:active:scale-[0.97]">
                                    Baca
                                    <i data-lucide="arrow-right" class="h-4 w-4 transition-transform duration-200 motion-safe:group-hover:translate-x-0.5"></i>
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <p class="rounded-2xl border border-dashed border-[#E2E8F0] p-6 text-sm text-[#62748E]">
                    Belum ada berita prodi.
                </p>
            @endif
        </div>
    </section>

    <!-- Prestasi Mahasiswa Section (Exact App.tsx, white bg) -->
    <section id="prestasi" class="scroll-mt-16 bg-white">
        <div class="mx-auto max-w-[1200px] px-4 py-14 sm:px-8 sm:py-16">
            <div class="mb-8">
                <h2 class="text-[24px] font-extrabold tracking-tight text-ink sm:text-[28px]">
                    Prestasi Mahasiswa
                </h2>
            </div>

            @if(count($prestasi))
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($prestasi as $x)
                        @php
                            $tingkatCls = match($x['tingkat']) {
                                'Internasional' => 'bg-violet-50 text-violet-700',
                                'Nasional' => 'bg-sky-100/80 text-brand-dark',
                                default => 'bg-emerald-50 text-emerald-700',
                            };
                        @endphp
                        <article class="group flex min-w-0 flex-col overflow-hidden rounded-2xl border border-[#E2E8F0] bg-surface transition duration-300 hover:border-amber-200 hover:shadow-lg hover:shadow-slate-200/70">
                            <div class="relative aspect-[16/10] overflow-hidden bg-gradient-to-br from-amber-50 to-amber-100">
                                @if(!empty($x['foto']))
                                    <img
                                        src="{{ $x['foto'] }}"
                                        alt="Dokumentasi {{ $x['judul'] }}"
                                        loading="lazy"
                                        class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                                    />
                                    <div class="absolute inset-x-0 bottom-0 h-1/2 bg-gradient-to-t from-black/45 to-transparent"></div>
                                @else
                                    <span class="grid h-full w-full place-items-center text-amber-500">
                                        <i data-lucide="award" class="h-10 w-10"></i>
                                    </span>
                                @endif

                                <span class="absolute top-3 right-3 rounded-full px-2.5 py-1 text-xs font-semibold shadow-sm {{ $tingkatCls }}">
                                    {{ $x['tingkat'] }}
                                </span>
                                <span class="absolute bottom-3 left-3 inline-flex items-center gap-1.5 rounded-full bg-amber-400 px-3 py-1 text-xs font-extrabold text-amber-950 shadow-sm">
                                    <i data-lucide="award" class="h-3.5 w-3.5"></i>
                                    {{ $x['peringkat'] }}
                                </span>
                            </div>
                            <div class="flex flex-1 flex-col p-5">
                                <h3 class="leading-snug font-bold text-ink">{{ $x['judul'] }}</h3>
                                <p class="mt-auto pt-3 text-sm text-[#62748E]">
                                    {{ $x['nama'] }} · {{ $x['tahun'] }}
                                </p>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <p class="rounded-2xl border border-dashed border-[#E2E8F0] p-6 text-sm text-[#62748E]">
                    Belum ada prestasi mahasiswa.
                </p>
            @endif
        </div>
    </section>

    <!-- Data Dosen Section (Exact App.tsx & Tentang.tsx) -->
    <section id="dosen" class="scroll-mt-16">
        <div class="mx-auto max-w-[1200px] px-4 py-14 sm:px-8 sm:py-16">
            <div class="mb-8">
                <h2 class="text-[24px] font-extrabold tracking-tight text-ink sm:text-[28px]">
                    Data Dosen
                </h2>
            </div>

            @if(count($dosen))
                <div class="grid grid-cols-1 gap-4 min-[420px]:grid-cols-2 lg:grid-cols-4">
                    @foreach($dosen as $d)
                        <article class="group flex h-full min-w-0 flex-col items-center rounded-2xl border border-[#E2E8F0] bg-surface px-5 pt-7 pb-5 text-center transition duration-300 ease-out hover:border-sky-200 hover:shadow-lg hover:shadow-sky-900/5 motion-safe:hover:-translate-y-1">
                            <span class="grid h-24 w-24 shrink-0 place-items-center overflow-hidden rounded-full bg-sky-100/80 text-2xl font-extrabold text-brand-dark ring-4 ring-white outline outline-1 outline-sky-100">
                                @if(!empty($d['foto']))
                                    <img src="{{ $d['foto'] }}" alt="Foto {{ $d['nama'] }}" class="h-full w-full object-cover" loading="lazy" />
                                @else
                                    {{ strtoupper(substr($d['nama'], 0, 2)) }}
                                @endif
                            </span>
                            <h3 class="mt-4 leading-snug font-bold text-ink">{{ $d['nama'] }}</h3>
                            <p class="mt-1 text-[13px] text-[#62748E]">
                                {{ $d['jabatan'] }} · {{ $d['pendidikan'] }}
                            </p>
                            <span class="mt-3 inline-flex rounded-full bg-sky-100/80 px-3 py-1 text-xs font-semibold text-brand-dark">
                                {{ $d['bidang'] }}
                            </span>
                            <div class="mt-auto w-full pt-5">
                                <button
                                    type="button"
                                    @click="openModal({{ json_encode($d) }})"
                                    aria-haspopup="dialog"
                                    class="inline-flex w-full items-center justify-center gap-1.5 rounded-xl border border-[#E2E8F0] px-4 py-2.5 text-sm font-semibold text-brand transition hover:border-brand hover:bg-sky-50 motion-safe:active:scale-[0.98]">
                                    Lebih lanjut
                                    <i data-lucide="arrow-right" class="h-4 w-4 transition-transform motion-safe:group-hover:translate-x-0.5"></i>
                                </button>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <p class="rounded-2xl border border-dashed border-[#E2E8F0] p-6 text-sm text-[#62748E]">
                    Data dosen belum tersedia.
                </p>
            @endif
        </div>
    </section>

    <!-- Kurikulum Section (Exact Curriculum.tsx) -->
    @php
        $years = array_values(array_unique(array_map(fn($c) => $c['tahun'], $kurikulum)));
        rsort($years);
        $activeYear = $years[0] ?? 2024;
    @endphp
    <section id="kurikulum" class="scroll-mt-20 bg-white" x-data="curriculumData({{ json_encode($kurikulum) }}, {{ $activeYear }})">
        <div class="mx-auto max-w-[1200px] px-4 py-14 sm:px-8 sm:py-16">
            <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 class="text-[24px] font-extrabold tracking-tight text-ink sm:text-[28px]">
                        Kurikulum
                    </h2>
                    <p class="mt-1.5 text-[15px] text-[#62748E]">
                        Pilih semester untuk melihat mata kuliah wajib dan pilihan.
                    </p>
                </div>
                @if(count($years) > 1)
                    <select
                        aria-label="Tahun kurikulum"
                        class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                        x-model="selectedYear"
                        @change="openSemesters = []">
                        @foreach($years as $y)
                            <option value="{{ $y }}">Kurikulum {{ $y }}</option>
                        @endforeach
                    </select>
                @endif
            </div>

            <template x-if="semesters.length === 0">
                <p class="rounded-2xl border border-dashed border-[#E2E8F0] p-6 text-sm text-[#62748E]">
                    Kurikulum belum dipublikasikan oleh program studi.
                </p>
            </template>

            <div class="space-y-3" x-show="semesters.length > 0">
                <template x-for="sem in semesters" :key="sem">
                    <div class="overflow-hidden rounded-2xl border bg-surface transition-colors"
                         :class="isOpen(sem) ? 'border-sky-200' : 'border-[#E2E8F0]'">
                        <button
                            type="button"
                            @click="toggle(sem)"
                            :aria-expanded="isOpen(sem)"
                            class="flex w-full items-center gap-4 px-5 py-4 text-left hover:bg-white/70">
                            <span
                                class="grid h-10 w-10 shrink-0 place-items-center rounded-xl text-sm font-extrabold"
                                :class="isOpen(sem) ? 'bg-brand text-white' : 'bg-sky-100/80 text-brand-dark'"
                                x-text="sem">
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block font-bold text-ink" x-text="'Semester ' + sem"></span>
                                <span class="block text-sm text-[#62748E]" x-text="getSemesterSummary(sem)"></span>
                            </span>
                            <i data-lucide="chevron-down" class="h-5 w-5 shrink-0 text-[#62748E] transition-transform duration-200"
                               :class="{ 'rotate-180': isOpen(sem) }"></i>
                        </button>

                        <div x-show="isOpen(sem)" x-collapse>
                            <ul class="divide-y divide-slate-100 border-t border-slate-100">
                                <template x-for="c in getCourses(sem)" :key="c.id">
                                    <li class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-x-4 gap-y-1 px-5 py-3 sm:grid-cols-[7rem_minmax(0,1fr)_5rem_4rem]">
                                        <span class="flex items-center gap-2 text-xs font-semibold text-[#62748E] sm:text-sm">
                                            <i data-lucide="book-open" class="h-4 w-4 shrink-0 text-sky-600"></i>
                                            <span x-text="c.kode"></span>
                                        </span>
                                        <span class="row-start-2 min-w-0 font-semibold text-ink sm:row-start-auto" x-text="c.nama"></span>
                                        <span
                                            class="justify-self-end rounded-md px-2 py-0.5 text-xs font-semibold sm:justify-self-start"
                                            :class="c.pilihan ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-600'"
                                            x-text="c.pilihan ? 'Pilihan' : 'Wajib'">
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

    <!-- Modal Dosen Detail (Exact Tentang.tsx DosenDetail) -->
    <div
        x-show="modalOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 grid place-items-end bg-slate-900/40 backdrop-blur-sm sm:place-items-center sm:p-6"
        @keydown.escape.window="closeModal()"
        style="display: none;">
        
        <div
            @click.outside="closeModal()"
            role="dialog"
            aria-modal="true"
            class="flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-t-2xl bg-white shadow-2xl sm:rounded-2xl">
            
            <template x-if="selectedDosen">
                <div class="overflow-y-auto">
                    <!-- Modal Header -->
                    <div class="relative bg-gradient-to-br from-benhur via-benhur to-deep px-6 pt-6 pb-16 text-white sm:px-8">
                        <div class="flex items-start justify-between gap-4">
                            <p class="text-xs font-semibold tracking-wider text-white/80 uppercase">
                                Profil Dosen
                            </p>
                            <button
                                @click="closeModal()"
                                aria-label="Tutup"
                                class="grid h-9 w-9 place-items-center rounded-lg bg-white/10 transition hover:bg-white/20">
                                <i data-lucide="x" class="h-4 w-4"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Modal Body -->
                    <div class="relative -mt-12 px-6 pb-7 sm:px-8">
                        <span class="grid h-28 w-28 shrink-0 place-items-center overflow-hidden rounded-2xl bg-sky-100/80 text-3xl font-extrabold text-brand-dark ring-4 ring-white shadow-lg">
                            <template x-if="selectedDosen.foto">
                                <img :src="selectedDosen.foto" :alt="'Foto ' + selectedDosen.nama" class="h-full w-full object-cover" />
                            </template>
                            <template x-if="!selectedDosen.foto">
                                <span x-text="getInitials(selectedDosen.nama)"></span>
                            </template>
                        </span>

                        <h2 class="mt-4 text-2xl font-extrabold tracking-tight text-ink" x-text="selectedDosen.nama"></h2>
                        <p class="mt-1 text-sm text-[#62748E]">
                            <span x-text="selectedDosen.jabatan"></span> · Program Studi Sistem Informasi
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
                                <dt class="text-xs font-semibold tracking-wide text-[#62748E] uppercase">Pendidikan terakhir</dt>
                                <dd class="mt-1 text-sm font-medium break-words text-ink" x-text="selectedDosen.pendidikan"></dd>
                            </div>
                            <div class="min-w-0">
                                <dt class="text-xs font-semibold tracking-wide text-[#62748E] uppercase">Jabatan fungsional</dt>
                                <dd class="mt-1 text-sm font-medium break-words text-ink" x-text="selectedDosen.jabatan"></dd>
                            </div>
                            <div class="min-w-0">
                                <dt class="text-xs font-semibold tracking-wide text-[#62748E] uppercase">Bidang keahlian</dt>
                                <dd class="mt-1 text-sm font-medium break-words text-ink" x-text="selectedDosen.bidang"></dd>
                            </div>
                            <template x-if="selectedDosen.email">
                                <div class="min-w-0">
                                    <dt class="text-xs font-semibold tracking-wide text-[#62748E] uppercase">Email</dt>
                                    <dd class="mt-1 text-sm font-medium break-words text-ink">
                                        <a :href="'mailto:' + selectedDosen.email" class="inline-flex items-center gap-1.5 text-brand hover:underline">
                                            <i data-lucide="mail" class="h-3.5 w-3.5"></i>
                                            <span x-text="selectedDosen.email"></span>
                                        </a>
                                    </dd>
                                </div>
                            </template>
                        </dl>

                        <template x-if="selectedDosen.mataKuliah">
                            <div class="mt-6">
                                <h3 class="text-sm font-bold text-ink">Mata kuliah yang diampu</h3>
                                <ul class="mt-3 flex flex-wrap gap-2">
                                    <template x-for="mk in selectedDosen.mataKuliah.split(',')" :key="mk">
                                        <li class="rounded-lg border border-[#E2E8F0] px-3 py-1.5 text-sm text-[#45556C]" x-text="mk.trim()"></li>
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
            selectedDosen: null,
            openModal(dosen) {
                this.selectedDosen = dosen;
                this.modalOpen = true;
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            },
            closeModal() {
                this.modalOpen = false;
                this.selectedDosen = null;
            },
            getInitials(name) {
                if (!name) return '';
                return name.replace(/^(Dr|Prof)\.?\s*/i, '')
                    .split(/[\s,]+/)
                    .filter(w => /^[A-Z]/.test(w))
                    .slice(0, 2)
                    .map(w => w[0])
                    .join('');
            }
        };
    }

    function curriculumData(rawCourses, initialYear) {
        return {
            courses: rawCourses,
            selectedYear: initialYear,
            openSemesters: [],
            get list() {
                return this.courses
                    .filter(c => Number(c.tahun) === Number(this.selectedYear))
                    .sort((a, b) => a.semester - b.semester || a.kode.localeCompare(b.kode));
            },
            get semesters() {
                return [...new Set(this.list.map(c => c.semester))];
            },
            isOpen(sem) {
                return this.openSemesters.includes(sem);
            },
            toggle(sem) {
                if (this.isOpen(sem)) {
                    this.openSemesters = this.openSemesters.filter(x => x !== sem);
                } else {
                    this.openSemesters.push(sem);
                }
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            },
            getCourses(sem) {
                return this.list.filter(c => c.semester === sem);
            },
            getSemesterSummary(sem) {
                const items = this.getCourses(sem);
                const totalSks = items.reduce((sum, c) => sum + Number(c.sks), 0);
                return `${items.length} mata kuliah · ${totalSks} SKS`;
            }
        };
    }
</script>
@endpush
