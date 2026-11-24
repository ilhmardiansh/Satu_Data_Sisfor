@extends('layouts.app')

@section('title', 'Satu Data Sistem Informasi UNUSA')

@section('content')
<main x-data="dosenModalData()">
    <!-- Hero Section (SKPL p. 13 & DPPL p. 48) -->
    <section class="relative isolate bg-[#182654]">
        <div class="relative overflow-hidden">
            <img
                src="{{ asset('images/kampus-unusa.jpg') }}"
                alt="Gedung kampus Universitas Nahdlatul Ulama Surabaya"
                width="1600"
                height="900"
                fetchpriority="high"
                class="absolute inset-0 h-full w-full object-cover"
            />
            <div class="absolute inset-0 bg-gradient-to-r from-[#182654]/95 via-[#182654]/88 to-[#1E3575]/75"></div>

            <div data-scene="network"
                 aria-hidden="true"
                 class="pointer-events-none absolute inset-y-2 right-0 hidden w-[50%] opacity-80 md:block"
                 style="mask-image: radial-gradient(closest-side, #000 58%, transparent 98%); -webkit-mask-image: radial-gradient(closest-side, #000 58%, transparent 98%);"></div>

            <div class="relative z-10 mx-auto max-w-[1200px] px-4 pt-12 pb-20 text-white sm:px-8 sm:pt-16 sm:pb-24">
                <p class="flex items-center gap-2.5 text-xs font-bold tracking-wider text-[#74D4FF] uppercase motion-safe:animate-fade-up">
                    <span class="h-px w-6 bg-[#74D4FF]" aria-hidden="true"></span>
                    {{ $t('Portal Data Terintegrasi Kampus', 'Campus Integrated Data Portal') }}
                </p>

                <h1 class="mt-4 max-w-3xl text-[clamp(2rem,4.3vw,3.35rem)] leading-[1.12] font-extrabold tracking-tight motion-safe:animate-fade-up">
                    {{ $t('Satu portal untuk semua', 'One portal for all') }}
                    <span class="text-[#74D4FF]">{{ $t('data Sistem Informasi UNUSA', 'UNUSA Information Systems data') }}</span>
                </h1>

                <p class="mt-5 max-w-2xl text-base leading-relaxed text-white/90 sm:text-[18px] motion-safe:animate-fade-up motion-safe:[animation-delay:100ms]">
                    {{ $t(
                        'Statistik dan data terintegrasi yang dikelola secara sistematis, akurat, dan berkelanjutan oleh Program Studi Sistem Informasi UNUSA.',
                        'Integrated statistics and data managed systematically, accurately, and sustainably by the Information Systems Study Program at UNUSA.'
                    ) }}
                </p>

                <div class="mt-7 flex flex-wrap items-center gap-3 motion-safe:animate-fade-up motion-safe:[animation-delay:160ms]">
                    <a href="{{ route('dashboard') }}" class="sd-btn-hero min-h-[46px] gap-2 px-5 py-2.5 text-[15px]">
                        <i data-lucide="layout-grid" class="h-4 w-4" aria-hidden="true"></i>
                        {{ $t('Jelajahi Dashboard', 'Explore Dashboard') }}
                    </a>
                    <a href="{{ route('dataset') }}" class="sd-glass-hero min-h-[46px] px-5 py-2.5 text-[15px]">
                        {{ $t('Lihat Kategori', 'View Categories') }}
                    </a>
                </div>

                <!-- Ringkasan angka langsung dari basis data, terbuka tanpa kotak bertumpuk -->
                <div class="mt-10 flex flex-wrap items-end justify-between gap-6">
                    <dl class="flex flex-wrap items-center gap-x-8 gap-y-4 sm:gap-x-10">
                        <div>
                            <dt class="text-xs font-medium text-white/75">Dataset</dt>
                            <dd class="mt-0.5 text-3xl font-extrabold tracking-tight tabular-nums text-white">{{ $heroStats['datasets'] }}</dd>
                        </div>
                        <div class="hidden h-9 w-px bg-white/20 sm:block" aria-hidden="true"></div>
                        <div>
                            <dt class="text-xs font-medium text-white/75">Dashboard</dt>
                            <dd class="mt-0.5 text-3xl font-extrabold tracking-tight tabular-nums text-white">{{ $heroStats['dashboards'] }}</dd>
                        </div>
                        <div class="hidden h-9 w-px bg-white/20 sm:block" aria-hidden="true"></div>
                        <div>
                            <dt class="text-xs font-medium text-white/75">{{ $t('Kategori Data', 'Data Categories') }}</dt>
                            <dd class="mt-0.5 text-3xl font-extrabold tracking-tight tabular-nums text-white">{{ $heroStats['categories'] }}</dd>
                        </div>
                    </dl>

                    <button type="button"
                            data-motion-toggle
                            onclick="toggleSceneMotion(this)"
                            aria-pressed="false"
                            class="hidden min-h-[38px] items-center gap-1.5 rounded-lg border border-white/20 bg-white/10 px-3 py-1.5 text-xs font-medium text-white/90 transition-colors hover:bg-white/20 md:inline-flex">
                        <i data-lucide="pause-circle" class="h-3.5 w-3.5" aria-hidden="true"></i>
                        <span data-motion-label>{{ $t('Jeda latar 3D', 'Pause 3D backdrop') }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Bilah pencarian cepat SKPL (p. 13) -->
        <div class="relative z-20 mx-auto max-w-[1200px] px-4 sm:px-8">
            <form action="{{ route('dataset') }}"
                  method="GET"
                  class="-mt-8 translate-y-4 grid grid-cols-1 gap-2 rounded-2xl border border-slate-200/80 bg-white p-2.5 shadow-xl shadow-slate-900/10 md:grid-cols-[1fr_200px_170px_auto]">
                <label for="heroSearchQ" class="flex min-h-[44px] items-center gap-2.5 rounded-xl px-3 focus-within:bg-[#F6F9FC]">
                    <i data-lucide="search" class="h-4 w-4 shrink-0 text-[#62748E]" aria-hidden="true"></i>
                    <span class="sr-only">{{ $t('Cari dashboard atau dataset', 'Search dashboards or datasets') }}</span>
                    <input
                        id="heroSearchQ"
                        type="text"
                        name="q"
                        placeholder="{{ $t('Cari dashboard atau dataset...', 'Search dashboards or datasets...') }}"
                        class="w-full bg-transparent text-sm text-ink outline-none placeholder:text-[#62748E]"
                    />
                </label>

                <label for="heroSearchCat" class="flex min-h-[44px] items-center border-t border-slate-100 px-2 md:border-t-0 md:border-l md:border-slate-200/80">
                    <span class="sr-only">{{ $t('Pilih kategori', 'Choose category') }}</span>
                    <select id="heroSearchCat" name="kategori" class="w-full bg-transparent px-2 py-2 text-sm font-medium text-[#45556C] outline-none">
                        <option value="all">{{ $t('Semua kategori', 'All categories') }}</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->code }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label for="heroSearchAccess" class="flex min-h-[44px] items-center border-t border-slate-100 px-2 md:border-t-0 md:border-l md:border-slate-200/80">
                    <span class="sr-only">{{ $t('Pilih akses', 'Choose access') }}</span>
                    <select id="heroSearchAccess" name="access" class="w-full bg-transparent px-2 py-2 text-sm font-medium text-[#45556C] outline-none">
                        <option value="all">{{ $t('Semua akses', 'All access') }}</option>
                        <option value="public">{{ $t('Publik', 'Public') }}</option>
                        <option value="private">Private</option>
                    </select>
                </label>

                <button type="submit" class="inline-flex min-h-[44px] items-center justify-center rounded-xl bg-[#155DFC] px-6 py-2.5 text-sm font-bold text-white transition-colors hover:bg-[#1d4ed8]">
                    {{ $t('Cari', 'Search') }}
                </button>
            </form>
        </div>
    </section>

    <!-- Profil Program Studi (DPPL p. 49 - susunan editorial asimetris terbuka) -->
    <section id="profil-prodi" class="scroll-mt-20 pt-16 pb-14 sm:pt-20 sm:pb-16">
        <div class="mx-auto max-w-[1200px] px-4 sm:px-8">
            <div class="reveal flex flex-wrap items-end justify-between gap-4 border-b border-slate-200/80 pb-6">
                <div class="max-w-3xl">
                    <h2 class="text-[24px] font-extrabold tracking-tight text-ink sm:text-[28px]">
                        {{ $t('Profil Program Studi', 'Study Program Profile') }}
                    </h2>
                    <p class="mt-2 text-[15px] leading-relaxed text-[#45556C]">
                        {{ $profil?->deskripsi ?: $t('Program Studi Sistem Informasi Universitas Nahdlatul Ulama Surabaya.', 'Information Systems Study Program at Universitas Nahdlatul Ulama Surabaya.') }}
                    </p>
                </div>
                <a href="{{ route('tentang-kami') }}" class="inline-flex min-h-[40px] shrink-0 items-center text-sm font-bold text-[#0069A8] transition-colors hover:text-[#005187]">
                    {{ $t('Selengkapnya di Tentang Kami', 'More in About Us') }}
                </a>
            </div>

            <div class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-12 lg:gap-10">
                <div class="space-y-6 lg:col-span-8">
                    @if($profil?->visi)
                        <div class="rounded-2xl bg-[#F6F9FC] p-6 sm:p-7">
                            <p class="flex items-center gap-2 text-xs font-bold tracking-wider text-[#0069A8] uppercase">
                                <i data-lucide="compass" class="h-4 w-4" aria-hidden="true"></i>
                                Visi
                            </p>
                            <p class="mt-2.5 text-base leading-relaxed font-medium text-ink">
                                {{ $profil->visi }}
                            </p>
                        </div>
                    @endif

                    @if(! empty($profil?->misi))
                        <div>
                            <h3 class="text-sm font-bold tracking-wide text-ink uppercase">Misi</h3>
                            <ol class="mt-4 grid grid-cols-1 gap-x-8 gap-y-4 sm:grid-cols-2">
                                @foreach($profil->misi as $mIdx => $misiItem)
                                    <li class="flex items-start gap-3">
                                        <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-[#F0F9FF] text-xs font-bold text-[#0069A8]">
                                            {{ $mIdx + 1 }}
                                        </span>
                                        <span class="text-sm leading-relaxed text-[#45556C]">{{ $misiItem }}</span>
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                    @endif
                </div>

                <dl class="divide-y divide-slate-200/80 border-t border-slate-200/80 pt-2 lg:col-span-4 lg:border-t-0 lg:border-l lg:pt-0 lg:pl-8">
                    <div class="py-4 first:pt-0">
                        <dt class="text-xs font-semibold text-[#62748E]">{{ $t('Akreditasi', 'Accreditation') }}</dt>
                        <dd class="mt-1 text-2xl font-extrabold tracking-tight text-ink">
                            {{ $profil?->akreditasi ?: '-' }}
                        </dd>
                    </div>
                    <div class="py-4">
                        <dt class="text-xs font-semibold text-[#62748E]">{{ $t('Dosen tetap', 'Permanent Lecturers') }}</dt>
                        <dd class="mt-1 text-2xl font-extrabold tracking-tight tabular-nums text-ink">
                            {{ $dosen->count() }}
                        </dd>
                    </div>
                    <div class="py-4 last:pb-0">
                        <dt class="text-xs font-semibold text-[#62748E]">{{ $t('Prestasi terbaru', 'Recent Achievements') }}</dt>
                        <dd class="mt-1 text-2xl font-extrabold tracking-tight tabular-nums text-ink">
                            {{ $prestasi->count() }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </section>

    <!-- Berita Prodi (Komposisi 1 utama + 2 pendukung, tidak seragam kotak) -->
    <section id="berita" class="scroll-mt-16 border-t border-slate-200/70 bg-white">
        <div class="mx-auto max-w-[1200px] px-4 py-14 sm:px-8 sm:py-16">
            <div class="reveal mb-8 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 class="text-[24px] font-extrabold tracking-tight text-ink sm:text-[28px]">
                        {{ $t('Kabar & Prestasi Terkini', 'Latest News & Achievements') }}
                    </h2>
                    <p class="mt-1.5 text-[15px] text-[#45556C]">
                        {{ $t(
                            'Sorotan capaian tridharma: prestasi mahasiswa, riset dosen, dan kegiatan pengabdian masyarakat.',
                            'Highlights of academic achievements, lecturer research, and community outreach.'
                        ) }}
                    </p>
                </div>
                <a href="{{ route('berita') }}" class="inline-flex min-h-[40px] shrink-0 items-center text-sm font-bold text-[#0069A8] transition-colors hover:text-[#005187]">
                    {{ $t('Arsip berita', 'News archive') }}
                </a>
            </div>

            @if($berita->isNotEmpty())
                @php
                    $leadNews = $berita->first();
                    $sideNews = $berita->slice(1);
                @endphp
                <div class="reveal space-y-8">
                    <article class="group grid grid-cols-1 items-center gap-6 rounded-2xl bg-[#F6F9FC] p-4 sm:p-6 lg:grid-cols-[1.15fr_1fr] lg:gap-8">
                        <a href="{{ route('berita.show', $leadNews->code) }}" class="relative block aspect-[16/10] overflow-hidden rounded-xl bg-slate-200">
                            <img
                                src="{{ $leadNews->image_url }}"
                                alt="{{ $leadNews->title }}"
                                loading="lazy"
                                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.03]"
                            />
                        </a>
                        <div class="flex flex-col">
                            <span class="w-fit rounded-lg bg-[#0069A8] px-2.5 py-1 text-xs font-bold text-white">
                                {{ $leadNews->badge }}
                            </span>
                            <div class="mt-3 flex flex-wrap items-center gap-4 text-xs font-medium text-[#62748E]">
                                <span class="flex items-center gap-1.5">
                                    <i data-lucide="calendar-days" class="h-3.5 w-3.5" aria-hidden="true"></i>
                                    {{ $leadNews->date_label }}
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <i data-lucide="clock" class="h-3.5 w-3.5" aria-hidden="true"></i>
                                    {{ $leadNews->read_minutes }} {{ $t('menit baca', 'min read') }}
                                </span>
                            </div>
                            <h3 class="mt-3 text-xl leading-snug font-extrabold tracking-tight text-ink sm:text-2xl">
                                <a href="{{ route('berita.show', $leadNews->code) }}" class="transition-colors hover:text-[#0069A8]">
                                    {{ $leadNews->title }}
                                </a>
                            </h3>
                            <p class="mt-2.5 line-clamp-3 text-sm leading-relaxed text-[#45556C]">
                                {{ $leadNews->excerpt }}
                            </p>
                            <a href="{{ route('berita.show', $leadNews->code) }}" class="mt-5 inline-flex min-h-[40px] w-fit items-center text-sm font-bold text-[#0069A8] transition-colors hover:text-[#005187]">
                                {{ $t('Baca selengkapnya', 'Read article') }}
                            </a>
                        </div>
                    </article>

                    @if($sideNews->isNotEmpty())
                        <div class="grid grid-cols-1 gap-8 border-t border-slate-200/80 pt-8 md:grid-cols-2">
                            @foreach($sideNews as $n)
                                <article class="group flex flex-col gap-4 sm:flex-row sm:items-start">
                                    <a href="{{ route('berita.show', $n->code) }}" class="aspect-[16/10] w-full shrink-0 overflow-hidden rounded-xl bg-slate-100 sm:w-44">
                                        <img
                                            src="{{ $n->image_url }}"
                                            alt="{{ $n->title }}"
                                            loading="lazy"
                                            class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.03]"
                                        />
                                    </a>
                                    <div class="flex min-w-0 flex-1 flex-col">
                                        <span class="w-fit rounded-md bg-[#F0F9FF] px-2 py-0.5 text-[11px] font-bold text-[#0069A8]">
                                            {{ $n->badge }}
                                        </span>
                                        <h3 class="mt-2 line-clamp-2 text-base leading-snug font-bold tracking-tight text-ink">
                                            <a href="{{ route('berita.show', $n->code) }}" class="transition-colors hover:text-[#0069A8]">
                                                {{ $n->title }}
                                            </a>
                                        </h3>
                                        <p class="mt-1.5 line-clamp-2 text-sm text-[#45556C]">{{ $n->excerpt }}</p>
                                        <div class="mt-3 flex items-center justify-between text-xs text-[#62748E]">
                                            <span>{{ $n->date_label }} · {{ $n->read_minutes }} {{ $t('menit baca', 'min read') }}</span>
                                            <a href="{{ route('berita.show', $n->code) }}" class="font-bold text-[#0069A8] hover:text-[#005187]">
                                                {{ $t('Baca', 'Read') }}
                                            </a>
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </div>
            @else
                <p class="rounded-2xl border border-dashed border-slate-300 bg-[#F6F9FC] p-6 text-sm text-[#45556C]">
                    {{ $t('Belum ada berita prodi.', 'No program news yet.') }}
                </p>
            @endif
        </div>
    </section>

    <!-- Prestasi Mahasiswa (Alur foto terbuka, tanpa kotak bertumpuk) -->
    <section id="prestasi" class="scroll-mt-16 border-y border-slate-200/70 bg-[#F6F9FC]">
        <div class="mx-auto max-w-[1200px] px-4 py-14 sm:px-8 sm:py-16">
            <div class="reveal mb-8 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 class="text-[24px] font-extrabold tracking-tight text-ink sm:text-[28px]">
                        {{ $t('Prestasi Mahasiswa', 'Student Achievements') }}
                    </h2>
                    <p class="mt-1 text-sm text-[#45556C]">
                        {{ $t('Capaian lomba dan rekognisi akademik mahasiswa Sistem Informasi UNUSA.', 'Student competition awards and academic recognitions.') }}
                    </p>
                </div>
            </div>

            @if($prestasi->isNotEmpty())
                <div class="reveal-stagger grid grid-cols-1 gap-7 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($prestasi as $x)
                        @php
                            $tingkatCls = match($x->tingkat) {
                                'Internasional' => 'bg-violet-50 text-violet-800',
                                'Nasional' => 'bg-[#F0F9FF] text-[#0069A8]',
                                default => 'bg-emerald-50 text-[#047857]',
                            };
                        @endphp
                        <article class="group flex min-w-0 flex-col">
                            <div class="relative aspect-[16/10] overflow-hidden rounded-xl bg-amber-50" x-data="{ imgFailed: false }">
                                @if($x->foto)
                                    <img
                                        x-show="!imgFailed"
                                        x-on:error="imgFailed = true"
                                        src="{{ $x->foto }}"
                                        alt="Dokumentasi {{ $x->judul }}"
                                        loading="lazy"
                                        class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.03]"
                                    />
                                    <div x-show="!imgFailed" class="absolute inset-x-0 bottom-0 h-1/2 bg-gradient-to-t from-black/55 to-transparent"></div>
                                    <span x-show="imgFailed" x-cloak class="grid h-full w-full place-items-center text-amber-700">
                                        <i data-lucide="award" class="h-10 w-10" aria-hidden="true"></i>
                                    </span>
                                @else
                                    <span class="grid h-full w-full place-items-center text-amber-700">
                                        <i data-lucide="award" class="h-10 w-10" aria-hidden="true"></i>
                                    </span>
                                @endif

                                <span class="absolute top-3 right-3 rounded-full px-2.5 py-1 text-xs font-semibold {{ $tingkatCls }}">
                                    {{ $x->tingkat }}
                                </span>
                                <span class="absolute bottom-3 left-3 inline-flex items-center gap-1.5 rounded-lg bg-[#FFFBEB] px-2.5 py-1 text-xs font-extrabold text-[#92400E]">
                                    <i data-lucide="award" class="h-3.5 w-3.5" aria-hidden="true"></i>
                                    {{ $x->peringkat }}
                                </span>
                            </div>
                            <div class="mt-3.5 flex flex-1 flex-col">
                                <h3 class="leading-snug font-bold tracking-tight text-ink">{{ $x->judul }}</h3>
                                <p class="mt-1.5 text-sm font-medium text-[#62748E]">
                                    {{ $x->nama }} · {{ $x->tahun }}
                                </p>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <p class="rounded-2xl border border-dashed border-slate-300 bg-white p-6 text-sm text-[#45556C]">
                    {{ $t('Belum ada prestasi mahasiswa.', 'No achievements yet.') }}
                </p>
            @endif
        </div>
    </section>

    <!-- Data Dosen (Direktori potret bersih tanpa kartu kotak) -->
    <section id="dosen" class="scroll-mt-16 bg-white">
        <div class="mx-auto max-w-[1200px] px-4 py-14 sm:px-8 sm:py-16">
            <div class="reveal mb-10 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 class="text-[24px] font-extrabold tracking-tight text-ink sm:text-[28px]">
                        {{ $t('Data Dosen', 'Lecturers') }}
                    </h2>
                    <p class="mt-1 text-sm text-[#45556C]">
                        {{ $t('Dosen tetap Program Studi Sistem Informasi UNUSA beserta bidang keahliannya.', 'Permanent lecturers of UNUSA Information Systems and their areas of expertise.') }}
                    </p>
                </div>
            </div>

            @if($dosen->isNotEmpty())
                <div class="reveal-stagger grid grid-cols-1 gap-x-6 gap-y-10 sm:grid-cols-2 lg:grid-cols-4">
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
                        <article class="flex h-full min-w-0 flex-col items-center text-center">
                            <span x-data="{ imgFailed: false }" class="grid h-24 w-24 shrink-0 place-items-center overflow-hidden rounded-full bg-[#F0F9FF] text-2xl font-extrabold text-[#0069A8] ring-4 ring-slate-100">
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
                            <span class="mt-2.5 inline-flex rounded-full bg-[#F0F9FF] px-3 py-1 text-xs font-semibold text-[#0069A8]">
                                {{ $d->bidang }}
                            </span>
                            <div class="mt-auto pt-4">
                                <button
                                    type="button"
                                    @click="openModal(@js($dosenJson))"
                                    aria-haspopup="dialog"
                                    class="inline-flex min-h-[40px] items-center justify-center rounded-xl border border-slate-200 px-4 py-2 text-xs font-bold text-[#0069A8] transition-colors hover:border-[#0069A8] hover:bg-[#F0F9FF]">
                                    {{ $t('Lihat profil', 'View profile') }}
                                </button>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <p class="rounded-2xl border border-dashed border-slate-300 bg-[#F6F9FC] p-6 text-sm text-[#45556C]">
                    {{ $t('Data dosen belum tersedia.', 'No lecturers yet.') }}
                </p>
            @endif
        </div>
    </section>

    <!-- Kurikulum (Daftar akordeon menyatu dengan pemisah garis tipis) -->
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
    <section id="kurikulum" class="scroll-mt-20 border-t border-slate-200/70 bg-[#F6F9FC]" x-data="curriculumData(@js($kurikulumList), {{ $activeYear }})">
        <div class="mx-auto max-w-[1200px] px-4 py-14 sm:px-8 sm:py-16">
            <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 class="text-[24px] font-extrabold tracking-tight text-ink sm:text-[28px]">
                        {{ $t('Kurikulum', 'Curriculum') }}
                    </h2>
                    <p class="mt-1.5 text-[15px] text-[#45556C]">
                        {{ $t(
                            'Pilih semester untuk melihat mata kuliah wajib dan pilihan.',
                            'Choose a semester to see required and elective courses.'
                        ) }}
                    </p>
                </div>
                @if(count($years) > 1)
                    <select
                        aria-label="{{ $t('Tahun kurikulum', 'Curriculum year') }}"
                        class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm font-medium text-slate-700 outline-none focus:border-[#0069A8] focus:ring-4 focus:ring-sky-100"
                        x-model="selectedYear"
                        @change="openSemesters = []">
                        @foreach($years as $y)
                            <option value="{{ $y }}">{{ $t('Kurikulum', 'Curriculum') }} {{ $y }}</option>
                        @endforeach
                    </select>
                @endif
            </div>

            <template x-if="semesters.length === 0">
                <p class="rounded-2xl border border-dashed border-slate-300 bg-white p-6 text-sm text-[#45556C]">
                    {{ $t(
                        'Kurikulum belum dipublikasikan oleh program studi.',
                        'The program has not published its curriculum yet.'
                    ) }}
                </p>
            </template>

            <div class="divide-y divide-slate-200/80 overflow-hidden rounded-2xl border border-slate-200/80 bg-white" x-show="semesters.length > 0">
                <template x-for="sem in semesters" :key="sem">
                    <div>
                        <button
                            type="button"
                            @click="toggle(sem)"
                            :aria-expanded="isOpen(sem)"
                            class="flex min-h-[64px] w-full items-center gap-4 px-5 py-4 text-left transition-colors hover:bg-[#F6F9FC]">
                            <span
                                class="grid h-10 w-10 shrink-0 place-items-center rounded-xl text-sm font-extrabold transition-colors"
                                :class="isOpen(sem) ? 'bg-[#0069A8] text-white' : 'bg-[#F0F9FF] text-[#0069A8]'"
                                x-text="sem">
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block font-bold text-ink" x-text="'Semester ' + sem"></span>
                                <span class="block text-sm text-[#62748E]" x-text="getSemesterSummary(sem)"></span>
                            </span>
                            <svg class="h-5 w-5 shrink-0 text-[#62748E] transition-transform duration-200"
                                 :class="{ 'rotate-180': isOpen(sem) }"
                                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                        </button>

                        <div x-show="isOpen(sem)" x-cloak>
                            <ul class="divide-y divide-slate-100 border-t border-slate-200/70 bg-[#FAFBFD]">
                                <template x-for="c in getCourses(sem)" :key="c.id">
                                    <li class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-x-4 gap-y-1 px-5 py-3 sm:grid-cols-[7.5rem_minmax(0,1fr)_5.5rem_4.5rem]">
                                        <span class="flex items-center gap-2 font-mono text-xs font-semibold text-[#62748E] sm:text-sm">
                                            <svg class="h-4 w-4 shrink-0 text-[#0069A8]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                                            <span x-text="c.kode"></span>
                                        </span>
                                        <span class="row-start-2 min-w-0 font-semibold text-ink sm:row-start-auto" x-text="c.nama"></span>
                                        <span
                                            class="justify-self-end rounded-md px-2 py-0.5 text-xs font-semibold sm:justify-self-start"
                                            :class="c.pilihan ? 'bg-[#FFFBEB] text-[#92400E]' : 'bg-slate-200/70 text-slate-700'"
                                            x-text="c.pilihan ? @js($t('Pilihan', 'Elective')) : @js($t('Wajib', 'Required'))">
                                        </span>
                                        <span class="row-start-2 text-right text-sm font-bold tabular-nums text-ink sm:row-start-auto" x-text="c.sks + ' SKS'"></span>
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
        class="fixed inset-0 z-50 grid place-items-end bg-slate-900/50 sm:place-items-center sm:p-6"
        @keydown.escape.window="closeModal()"
        @click.self="closeModal()">
        <div
            role="dialog"
            aria-modal="true"
            aria-labelledby="dosen-modal-title"
            class="flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-t-2xl border border-slate-200 bg-white shadow-2xl sm:rounded-2xl">
            <template x-if="selectedDosen">
                <div class="overflow-y-auto">
                    <div class="relative bg-gradient-to-br from-[#182654] to-[#1E3575] px-6 pt-6 pb-16 text-white sm:px-8">
                        <div class="flex items-start justify-between gap-4">
                            <p class="text-xs font-semibold tracking-wider text-white/80 uppercase">
                                {{ $t('Profil Dosen', 'Lecturer Profile') }}
                            </p>
                            <button
                                type="button"
                                @click="closeModal()"
                                aria-label="{{ $t('Tutup', 'Close') }}"
                                class="grid min-h-[44px] min-w-[44px] place-items-center rounded-xl border border-white/20 bg-white/10 transition-colors hover:bg-white/20">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="relative -mt-12 px-6 pb-7 sm:px-8">
                        <span class="grid h-28 w-28 shrink-0 place-items-center overflow-hidden rounded-2xl bg-[#F0F9FF] text-3xl font-extrabold text-[#0069A8] shadow-md ring-4 ring-white">
                            <template x-if="selectedDosen.foto && !modalImgFailed">
                                <img :src="selectedDosen.foto" x-on:error="modalImgFailed = true" :alt="'Foto ' + selectedDosen.nama" class="h-full w-full object-cover" />
                            </template>
                            <template x-if="!selectedDosen.foto || modalImgFailed">
                                <span x-text="selectedDosen.initials"></span>
                            </template>
                        </span>

                        <h2 id="dosen-modal-title" class="mt-4 text-2xl font-extrabold tracking-tight text-ink" x-text="selectedDosen.nama"></h2>
                        <p class="mt-1 text-sm text-[#62748E]">
                            <span x-text="selectedDosen.jabatan"></span> · {{ $t('Program Studi Sistem Informasi', 'Information Systems Program') }}
                        </p>
                        <span class="mt-3 inline-flex rounded-full bg-[#F0F9FF] px-3 py-1 text-xs font-semibold text-[#0069A8]" x-text="selectedDosen.bidang"></span>

                        <template x-if="selectedDosen.profil">
                            <p class="mt-5 text-sm leading-relaxed whitespace-pre-line text-[#45556C]" x-text="selectedDosen.profil"></p>
                        </template>

                        <dl class="mt-6 grid grid-cols-1 gap-4 rounded-2xl border border-slate-200 bg-[#F6F9FC] p-5 sm:grid-cols-2">
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
                                        <a :href="'mailto:' + selectedDosen.email" class="inline-flex items-center gap-1.5 text-[#0069A8] hover:underline">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
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
                                        <li class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm text-[#45556C]" x-text="mk"></li>
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
