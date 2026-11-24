@extends('layouts.app')

@section('title', 'Tentang kami - Satu Data Sistem Informasi UNUSA')

@section('content')
@php
    $missing = $t('Belum diisi', 'Not provided');
@endphp

<main>
    <!-- Hero Banner (DPPL p. 54) -->
    <section class="relative isolate overflow-hidden bg-gradient-to-br from-[#182654] to-[#1E3575] text-white">
        <div data-scene="waves"
             aria-hidden="true"
             class="pointer-events-none absolute inset-y-0 right-0 hidden w-[46%] opacity-70 md:block"
             style="mask-image: radial-gradient(closest-side, #000 56%, transparent 98%); -webkit-mask-image: radial-gradient(closest-side, #000 56%, transparent 98%);"></div>

        <div class="relative z-10 mx-auto max-w-[1200px] px-4 py-12 sm:px-8 sm:py-16">
            <p class="text-sm text-white/80">
                <a href="{{ route('beranda') }}" class="hover:text-white">{{ $t('Beranda', 'Home') }}</a>
                <span class="mx-1">/</span>
                <span class="font-semibold text-white">{{ $t('Tentang kami', 'About us') }}</span>
            </p>

            <h1 class="mt-3 max-w-3xl text-[clamp(1.85rem,3.6vw,2.75rem)] leading-tight font-extrabold tracking-tight">
                Program Studi <span class="text-[#74D4FF]">Sistem Informasi</span> UNUSA
            </h1>

            @if($profil?->deskripsi)
                <p class="mt-4 max-w-2xl text-base leading-relaxed text-white/85">
                    {{ $profil->deskripsi }}
                </p>
            @endif

            <div class="mt-8 flex flex-wrap items-end justify-between gap-6">
                <dl class="flex flex-wrap items-center gap-x-8 gap-y-4">
                    <div>
                        <dt class="text-xs font-medium text-white/75">{{ $t('Akreditasi', 'Accreditation') }}</dt>
                        <dd class="mt-0.5 text-2xl font-extrabold tracking-tight text-white">
                            {{ $profil?->akreditasi ?: $missing }}
                        </dd>
                    </div>
                    <div class="hidden h-8 w-px bg-white/20 sm:block" aria-hidden="true"></div>
                    <div>
                        <dt class="text-xs font-medium text-white/75">{{ $t('Tahun berdiri', 'Established') }}</dt>
                        <dd class="mt-0.5 text-2xl font-extrabold tracking-tight tabular-nums text-white">
                            {{ $profil?->berdiri ?: $missing }}
                        </dd>
                    </div>
                    <div class="hidden h-8 w-px bg-white/20 sm:block" aria-hidden="true"></div>
                    <div>
                        <dt class="text-xs font-medium text-white/75">{{ $t('Dosen', 'Lecturers') }}</dt>
                        <dd class="mt-0.5 text-2xl font-extrabold tracking-tight tabular-nums text-white">
                            {{ $dosen->count() }}
                        </dd>
                    </div>
                    <div class="hidden h-8 w-px bg-white/20 sm:block" aria-hidden="true"></div>
                    <div>
                        <dt class="text-xs font-medium text-white/75">{{ $t('Prestasi terbaru', 'Recent achievements') }}</dt>
                        <dd class="mt-0.5 text-2xl font-extrabold tracking-tight tabular-nums text-white">
                            {{ $prestasiCount }}
                        </dd>
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
    </section>

    <div class="mx-auto max-w-[1200px] px-4 sm:px-8">
        <!-- Sejarah Prodi -->
        <section id="sejarah" class="grid scroll-mt-24 grid-cols-1 gap-6 border-b border-slate-200/80 py-12 md:grid-cols-[16rem_minmax(0,1fr)] md:gap-10">
            <h2 class="flex items-center gap-3 self-start text-[22px] font-extrabold tracking-tight text-ink md:sticky md:top-24">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-[#F0F9FF] text-[#0069A8]">
                    <i data-lucide="history" class="h-5 w-5" aria-hidden="true"></i>
                </span>
                {{ $t('Sejarah Prodi', 'History') }}
            </h2>
            <div class="min-w-0">
                <p class="max-w-3xl leading-relaxed whitespace-pre-line text-[#45556C]">{{ $profil?->sejarah ?: $t('Sejarah program studi belum dipublikasikan.', 'Program history has not been published yet.') }}</p>
            </div>
        </section>

        <!-- Tentang Prodi: Visi & Misi side-by-side (DPPL p. 54) -->
        <section id="tentang-prodi" class="grid scroll-mt-24 grid-cols-1 gap-6 border-b border-slate-200/80 py-12 md:grid-cols-[16rem_minmax(0,1fr)] md:gap-10">
            <h2 class="flex items-center gap-3 self-start text-[22px] font-extrabold tracking-tight text-ink md:sticky md:top-24">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-[#F0F9FF] text-[#0069A8]">
                    <i data-lucide="book-open-text" class="h-5 w-5" aria-hidden="true"></i>
                </span>
                {{ $t('Tentang Prodi', 'About the Program') }}
            </h2>
            <div class="min-w-0 space-y-6">
                <p class="max-w-3xl leading-relaxed text-[#45556C]">
                    {{ $profil?->deskripsi ?: $missing }}
                </p>

                @if(! empty($profil?->visi) || ! empty($profil?->misi))
                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                        @if(! empty($profil?->visi))
                            <div class="rounded-2xl bg-[#F6F9FC] p-6">
                                <h3 class="flex items-center gap-2 text-sm font-bold text-[#0069A8] uppercase">
                                    <i data-lucide="compass" class="h-4 w-4" aria-hidden="true"></i>
                                    Visi
                                </h3>
                                <p class="mt-2.5 text-sm leading-relaxed text-ink">{{ $profil->visi }}</p>
                            </div>
                        @endif

                        @if(! empty($profil?->misi))
                            <div class="rounded-2xl border border-slate-200/80 bg-white p-6">
                                <h3 class="text-sm font-bold text-ink uppercase">Misi</h3>
                                <ol class="mt-3 space-y-2.5">
                                    @foreach($profil->misi as $mIdx => $misiItem)
                                        <li class="flex items-start gap-2.5 text-sm leading-relaxed text-[#45556C]">
                                            <span class="grid h-5 w-5 shrink-0 place-items-center rounded-full bg-[#F0F9FF] text-[11px] font-bold text-[#0069A8]">
                                                {{ $mIdx + 1 }}
                                            </span>
                                            <span>{{ $misiItem }}</span>
                                        </li>
                                    @endforeach
                                </ol>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </section>

        <!-- Dosen Tetap (DPPL p. 54) -->
        <section id="dosen-prodi" class="grid scroll-mt-24 grid-cols-1 gap-6 border-b border-slate-200/80 py-12 md:grid-cols-[16rem_minmax(0,1fr)] md:gap-10">
            <h2 class="flex items-center gap-3 self-start text-[22px] font-extrabold tracking-tight text-ink md:sticky md:top-24">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-[#F0F9FF] text-[#0069A8]">
                    <i data-lucide="users" class="h-5 w-5" aria-hidden="true"></i>
                </span>
                {{ $t('Dosen', 'Lecturers') }}
            </h2>
            <div class="min-w-0">
                <p class="mb-6 text-sm text-[#62748E]">
                    {{ $t('Dosen tetap Program Studi Sistem Informasi beserta bidang keahliannya.', 'Permanent lecturers of Information Systems and their areas of expertise.') }}
                </p>
                @if($dosen->isNotEmpty())
                    <div class="grid grid-cols-1 gap-x-6 gap-y-5 sm:grid-cols-2">
                        @foreach($dosen as $d)
                            <div class="flex items-start gap-3.5 border-b border-slate-100 pb-4 last:border-0">
                                <span x-data="{ imgFailed: false }" class="grid h-11 w-11 shrink-0 place-items-center overflow-hidden rounded-full bg-[#F0F9FF] text-xs font-extrabold text-[#0069A8]">
                                    @if($d->foto)
                                        <img x-show="!imgFailed" x-on:error="imgFailed = true" src="{{ $d->foto }}" alt="Foto {{ $d->nama }}" class="h-full w-full object-cover" loading="lazy" />
                                        <span x-show="imgFailed" x-cloak>{{ $d->initials() }}</span>
                                    @else
                                        <span>{{ $d->initials() }}</span>
                                    @endif
                                </span>
                                <div class="min-w-0">
                                    <p class="font-bold text-ink">{{ $d->nama }}</p>
                                    <p class="text-xs text-[#62748E]">{{ $d->jabatan }} · {{ $d->pendidikan }}</p>
                                    <span class="mt-1.5 inline-flex rounded-md bg-[#F0F9FF] px-2 py-0.5 text-[11px] font-semibold text-[#0069A8]">
                                        {{ $d->bidang }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-[#62748E]">{{ $missing }}</p>
                @endif
            </div>
        </section>

        <!-- Detail Prodi -->
        <section id="detail-prodi" class="grid scroll-mt-24 grid-cols-1 gap-6 py-12 md:grid-cols-[16rem_minmax(0,1fr)] md:gap-10">
            <h2 class="flex items-center gap-3 self-start text-[22px] font-extrabold tracking-tight text-ink md:sticky md:top-24">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-[#F0F9FF] text-[#0069A8]">
                    <i data-lucide="building-2" class="h-5 w-5" aria-hidden="true"></i>
                </span>
                {{ $t('Detail Prodi', 'Program Details') }}
            </h2>
            <div class="min-w-0">
                @php
                    $kontak = implode(' · ', array_filter([$profil?->email, $profil?->telp]));
                    $details = [
                        [$t('Nama program', 'Program'), $profil?->program],
                        [$t('Gelar', 'Degree'), $profil?->gelar],
                        [$t('Durasi', 'Duration'), $profil?->durasi],
                        [$t('Syarat masuk', 'Entry requirements'), $profil?->syarat],
                        [$t('Lokasi kampus', 'Campus location'), $profil?->alamat],
                        [$t('Fakultas', 'Faculty'), $profil?->fakultas],
                        [$t('Fasilitas', 'Facilities'), $profil?->fasilitas],
                        [$t('Kontak', 'Contact'), $kontak],
                    ];
                @endphp
                <dl class="divide-y divide-slate-200/80 border-y border-slate-200/80 text-sm">
                    @foreach($details as [$label, $value])
                        <div class="grid grid-cols-1 gap-1 py-4 sm:grid-cols-[11rem_minmax(0,1fr)] sm:gap-4">
                            <dt class="font-semibold text-ink">{{ $label }}</dt>
                            <dd class="min-w-0 break-words whitespace-pre-line {{ $value ? 'text-[#45556C]' : 'text-[#62748E]' }}">
                                {{ $value ?: $missing }}
                            </dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </section>
    </div>
</main>
@endsection
