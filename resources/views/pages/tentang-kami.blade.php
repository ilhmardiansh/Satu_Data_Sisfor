@extends('layouts.app')

@section('title', 'Tentang kami - Satu Data Sistem Informasi UNUSA')

@section('content')
<main>
    <!-- Hero Section (Exact Tentang.tsx) -->
    <section class="bg-gradient-to-br from-benhur via-benhur to-deep text-white">
        <div class="mx-auto max-w-[1200px] px-4 py-12 sm:px-8 sm:py-14">
            <p class="text-sm text-white/75">
                <a href="{{ route('beranda') }}" class="hover:text-white">Beranda</a>
                <span class="mx-1">/</span>
                <span class="font-semibold text-white">Tentang kami</span>
            </p>
            <h1 class="mt-2 max-w-3xl text-[32px] leading-tight font-extrabold tracking-tight sm:text-[40px]">
                Program Studi <span class="text-accent-soft">Sistem Informasi</span> UNUSA
            </h1>
        </div>
    </section>

    <div class="mx-auto max-w-[1200px] px-4 sm:px-8">
        <!-- Block 1: Sejarah Prodi (Exact Tentang.tsx) -->
        <section id="sejarah" class="grid scroll-mt-24 grid-cols-1 gap-5 border-b border-[#E2E8F0] py-12 last:border-0 md:grid-cols-[16rem_minmax(0,1fr)] md:gap-10">
            <h2 class="flex items-center gap-3 self-start text-[22px] font-extrabold tracking-tight text-ink md:sticky md:top-24">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-sky-100/80 text-brand">
                    <i data-lucide="history" class="h-5 w-5"></i>
                </span>
                Sejarah Prodi
            </h2>
            <div class="min-w-0">
                <p class="max-w-3xl leading-relaxed whitespace-pre-line text-[#45556C]">
                    {{ $profil['sejarah'] ?? 'Program Studi Sistem Informasi Universitas Nahdlatul Ulama Surabaya didirikan pada tahun 2013 untuk menjawab kebutuhan tenaga profesional di bidang teknologi informasi dan manajemen data di Jawa Timur dan Indonesia. Sejak awal pendiriannya, program studi ini berkomitmen mengintegrasikan keilmuan sistem informasi dengan nilai-nilai Islam Ahlussunnah wal Jamaah.' }}
                </p>
            </div>
        </section>

        <!-- Block 2: Tentang Prodi (Exact Tentang.tsx) -->
        <section id="tentang-prodi" class="grid scroll-mt-24 grid-cols-1 gap-5 border-b border-[#E2E8F0] py-12 last:border-0 md:grid-cols-[16rem_minmax(0,1fr)] md:gap-10">
            <h2 class="flex items-center gap-3 self-start text-[22px] font-extrabold tracking-tight text-ink md:sticky md:top-24">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-sky-100/80 text-brand">
                    <i data-lucide="book-open-text" class="h-5 w-5"></i>
                </span>
                Tentang Prodi
            </h2>
            <div class="min-w-0">
                <p class="max-w-3xl leading-relaxed text-[#45556C]">
                    {{ $profil['deskripsi'] }}
                </p>
                <dl class="mt-6 grid max-w-xl grid-cols-2 gap-4">
                    <div class="rounded-2xl border border-[#E2E8F0] bg-surface p-5">
                        <dt class="text-sm text-[#62748E]">Akreditasi</dt>
                        <dd class="mt-1 text-2xl font-extrabold text-ink">
                            {{ $profil['akreditasi'] ?? 'Baik Sekali' }}
                        </dd>
                    </div>
                    <div class="rounded-2xl border border-[#E2E8F0] bg-surface p-5">
                        <dt class="text-sm text-[#62748E]">Tahun berdiri</dt>
                        <dd class="mt-1 text-2xl font-extrabold text-ink">
                            {{ $profil['berdiri'] ?? '2013' }}
                        </dd>
                    </div>
                </dl>
            </div>
        </section>

        <!-- Block 3: Detail Prodi (Exact Tentang.tsx) -->
        <section id="detail-prodi" class="grid scroll-mt-24 grid-cols-1 gap-5 border-b border-[#E2E8F0] py-12 last:border-0 md:grid-cols-[16rem_minmax(0,1fr)] md:gap-10">
            <h2 class="flex items-center gap-3 self-start text-[22px] font-extrabold tracking-tight text-ink md:sticky md:top-24">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-sky-100/80 text-brand">
                    <i data-lucide="building-2" class="h-5 w-5"></i>
                </span>
                Detail Prodi
            </h2>
            <div class="min-w-0">
                <dl class="overflow-hidden rounded-2xl border border-[#E2E8F0] bg-surface text-sm">
                    @php
                        $details = [
                            ['label' => 'Nama program', 'val' => $profil['program'] ?? 'Sistem Informasi (S1)'],
                            ['label' => 'Gelar', 'val' => $profil['gelar'] ?? 'Sarjana Komputer (S.Kom)'],
                            ['label' => 'Durasi', 'val' => $profil['durasi'] ?? '8 Semester (4 Tahun)'],
                            ['label' => 'Syarat masuk', 'val' => $profil['syarat'] ?? 'Lulusan SMA/SMK/MA sederajat'],
                            ['label' => 'Lokasi kampus', 'val' => $profil['alamat'] ?? 'Kampus B UNUSA, Jl. Jemursari No. 51–57, Surabaya, Jawa Timur 60237'],
                            ['label' => 'Fakultas', 'val' => $profil['fakultas'] ?? 'Fakultas Kesehatan, Keteknikan, dan Ilmu Terapan (FKKIT)'],
                            ['label' => 'Fasilitas', 'val' => $profil['fasilitas'] ?? 'Laboratorium Komputer, Lab Data & AI, Smart Classroom, Perpustakaan Terpadu'],
                            ['label' => 'Kontak', 'val' => ($profil['email'] ?? 'si@unusa.ac.id') . ' · ' . ($profil['telp'] ?? '(031) 8291920')],
                        ];
                    @endphp
                    @foreach($details as $d)
                        <div class="grid grid-cols-1 gap-1 border-b border-slate-100 px-5 py-4 last:border-0 sm:grid-cols-[10rem_minmax(0,1fr)] sm:gap-4">
                            <dt class="font-semibold text-ink">{{ $d['label'] }}</dt>
                            <dd class="min-w-0 break-words whitespace-pre-line text-[#45556C]">
                                {{ $d['val'] }}
                            </dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </section>
    </div>
</main>
@endsection
