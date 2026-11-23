@extends('layouts.app')

@section('title', 'Tentang kami - Satu Data Sistem Informasi UNUSA')

@section('content')
@php
    $missing = $t('Belum diisi', 'Not provided');
@endphp

<main>
    <section class="relative isolate overflow-hidden bg-gradient-to-br from-benhur via-[#062f78] to-deep text-white shadow-[inset_0_-1px_0_rgba(255,255,255,0.12)]">
        <div data-scene="waves"
             aria-hidden="true"
             class="pointer-events-none absolute inset-y-0 right-0 hidden w-[48%] opacity-75 md:block"
             style="mask-image: radial-gradient(closest-side, #000 56%, transparent 98%); -webkit-mask-image: radial-gradient(closest-side, #000 56%, transparent 98%);"></div>
        <div class="relative z-10 mx-auto max-w-[1200px] px-4 py-12 sm:px-8 sm:py-14">
            <p class="text-sm text-white/75">
                <a href="{{ route('beranda') }}" class="hover:text-white">{{ $t('Beranda', 'Home') }}</a>
                <span class="mx-1">/</span>
                <span class="font-semibold text-white">{{ $t('Tentang kami', 'About us') }}</span>
            </p>
            <h1 class="mt-2 max-w-3xl text-[32px] leading-tight font-extrabold tracking-tight sm:text-[40px]">
                Program Studi <span class="text-accent-soft">Sistem Informasi</span> UNUSA
            </h1>
        </div>
    </section>

    <div class="mx-auto max-w-[1200px] px-4 sm:px-8">
        <!-- Sejarah Prodi -->
        <section id="sejarah" class="grid scroll-mt-24 grid-cols-1 gap-5 border-b border-slate-200/80 py-12 last:border-0 md:grid-cols-[16rem_minmax(0,1fr)] md:gap-10">
            <h2 class="flex items-center gap-3 self-start text-[22px] font-extrabold tracking-tight text-ink md:sticky md:top-24">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-sky-50 text-brand shadow-2xs ring-1 ring-sky-200/70">
                    <i data-lucide="history" class="h-5 w-5"></i>
                </span>
                {{ $t('Sejarah Prodi', 'History') }}
            </h2>
            <div class="min-w-0">
                <p class="max-w-3xl leading-relaxed whitespace-pre-line text-[#45556C]">{{ $profil?->sejarah ?: $t('Sejarah program studi belum dipublikasikan.', 'Program history has not been published yet.') }}</p>
            </div>
        </section>

        <!-- Tentang Prodi -->
        <section id="tentang-prodi" class="grid scroll-mt-24 grid-cols-1 gap-5 border-b border-slate-200/80 py-12 last:border-0 md:grid-cols-[16rem_minmax(0,1fr)] md:gap-10">
            <h2 class="flex items-center gap-3 self-start text-[22px] font-extrabold tracking-tight text-ink md:sticky md:top-24">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-sky-50 text-brand shadow-2xs ring-1 ring-sky-200/70">
                    <i data-lucide="book-open-text" class="h-5 w-5"></i>
                </span>
                {{ $t('Tentang Prodi', 'About the Program') }}
            </h2>
            <div class="min-w-0">
                <p class="max-w-3xl leading-relaxed text-[#45556C]">
                    {{ $profil?->deskripsi ?: $missing }}
                </p>
                <dl class="mt-6 grid max-w-xl grid-cols-2 gap-4">
                    <div class="sd-card p-5">
                        <dt class="text-sm text-[#62748E]">{{ $t('Akreditasi', 'Accreditation') }}</dt>
                        <dd class="mt-1 text-2xl font-extrabold tracking-tight text-ink">
                            {{ $profil?->akreditasi ?: $missing }}
                        </dd>
                    </div>
                    <div class="sd-card p-5">
                        <dt class="text-sm text-[#62748E]">{{ $t('Tahun berdiri', 'Established') }}</dt>
                        <dd class="mt-1 text-2xl font-extrabold tracking-tight tabular-nums text-ink">
                            {{ $profil?->berdiri ?: $missing }}
                        </dd>
                    </div>
                </dl>

                @if(! empty($profil?->visi))
                    <div class="sd-card mt-6 p-6">
                        <h3 class="text-sm font-bold text-ink">Visi</h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-[#45556C]">{{ $profil->visi }}</p>
                        @if(! empty($profil->misi))
                            <h3 class="mt-4 text-sm font-bold text-ink">Misi</h3>
                            <ul class="mt-1.5 list-disc space-y-1 pl-5 text-sm leading-relaxed text-[#45556C]">
                                @foreach($profil->misi as $misiItem)
                                    <li>{{ $misiItem }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endif
            </div>
        </section>

        <!-- Detail Prodi -->
        <section id="detail-prodi" class="grid scroll-mt-24 grid-cols-1 gap-5 border-b border-slate-200/80 py-12 last:border-0 md:grid-cols-[16rem_minmax(0,1fr)] md:gap-10">
            <h2 class="flex items-center gap-3 self-start text-[22px] font-extrabold tracking-tight text-ink md:sticky md:top-24">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-sky-50 text-brand shadow-2xs ring-1 ring-sky-200/70">
                    <i data-lucide="building-2" class="h-5 w-5"></i>
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
                <dl class="sd-card overflow-hidden text-sm">
                    @foreach($details as [$label, $value])
                        <div class="grid grid-cols-1 gap-1 border-b border-slate-100 px-5 py-4 last:border-0 sm:grid-cols-[10rem_minmax(0,1fr)] sm:gap-4">
                            <dt class="font-semibold text-ink">{{ $label }}</dt>
                            <dd class="min-w-0 break-words whitespace-pre-line {{ $value ? 'text-[#45556C]' : 'text-slate-400' }}">
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
