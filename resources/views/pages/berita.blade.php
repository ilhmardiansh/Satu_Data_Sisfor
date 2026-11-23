@extends('layouts.app')

@section('title', 'Berita Sistem Informasi - Satu Data UNUSA')

@section('content')
@php
    $lead = $berita->first();
    $rest = $berita->slice(1);
@endphp

<main class="mx-auto max-w-[1200px] px-4 py-10 sm:px-8">
    <div class="relative overflow-hidden">
        <div data-scene="ambient"
             aria-hidden="true"
             class="pointer-events-none absolute -top-4 right-0 hidden h-36 w-80 opacity-75 lg:block"
             style="mask-image: radial-gradient(closest-side, #000 58%, transparent 98%); -webkit-mask-image: radial-gradient(closest-side, #000 58%, transparent 98%);"></div>
        <div class="relative z-10">
            <p class="text-sm text-slate-400">
                <a href="{{ route('beranda') }}" class="transition hover:text-sky-600">{{ $t('Beranda', 'Home') }}</a>
                <span class="mx-1">/</span>
                <span class="text-sky-600">{{ $t('Berita', 'News') }}</span>
            </p>

            <div class="mt-1 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between sm:gap-6">
                <div class="min-w-0">
                    <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">
                        Berita Sistem Informasi
                    </h1>
                    <p class="mt-1 text-sm text-slate-500">
                        Kabar prestasi, riset, pengabdian, dan kegiatan Program Studi Sistem Informasi UNUSA.
                    </p>
                </div>
                <form action="{{ route('berita') }}" method="GET" class="w-full shrink-0 sm:w-72">
                    @if($selectedTag && $selectedTag !== 'Semua')
                        <input type="hidden" name="tag" value="{{ $selectedTag }}">
                    @endif
                    <input
                        type="text"
                        name="q"
                        value="{{ $q }}"
                        placeholder="Cari berita…"
                        aria-label="Cari berita"
                        class="h-10 w-full rounded-xl border border-slate-200/90 bg-white px-4 text-sm shadow-2xs outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                    />
                </form>
            </div>
        </div>
    </div>

    <div class="mt-6 flex gap-2 overflow-x-auto border-b border-slate-200/80 pb-4">
        @foreach($tags as $tag)
            <a
                href="{{ route('berita', array_filter(['tag' => $tag === 'Semua' ? null : $tag, 'q' => $q ?: null])) }}"
                aria-pressed="{{ $selectedTag === $tag ? 'true' : 'false' }}"
                class="shrink-0 rounded-full px-4 py-1.5 text-sm font-semibold whitespace-nowrap transition motion-safe:active:scale-[0.97] {{ $selectedTag === $tag ? 'bg-gradient-to-b from-brand to-brand-dark text-white shadow-xs' : 'bg-white text-slate-600 ring-1 ring-slate-200/90 hover:ring-slate-300 hover:text-slate-900' }}"
            >
                {{ $tag }}
            </a>
        @endforeach
    </div>

    @if(! $lead)
        <p class="mt-8 rounded-2xl border border-dashed border-slate-300 bg-white py-12 text-center text-sm text-slate-500">
            Tidak ada berita yang cocok.
        </p>
    @else
        <a
            href="{{ route('berita.show', $lead->code) }}"
            class="sd-card sd-card-hover group mt-8 grid w-full grid-cols-1 overflow-hidden text-left md:grid-cols-[1.3fr_1fr]"
        >
            <div class="relative aspect-[16/10] overflow-hidden bg-slate-100">
                <img
                    src="{{ $lead->image_url }}"
                    alt="{{ $lead->title }}"
                    class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                />
            </div>
            <div class="flex min-w-0 flex-col justify-center p-6 sm:p-8">
                <span class="w-fit rounded-lg bg-brand px-2.5 py-1 text-[11px] font-bold text-white shadow-2xs">
                    {{ $lead->badge }}
                </span>
                <h2 class="mt-4 text-2xl leading-snug font-extrabold tracking-tight text-slate-900 transition-colors group-hover:text-brand">
                    {{ $lead->title }}
                </h2>
                <p class="mt-3 leading-relaxed text-slate-500">{{ $lead->excerpt }}</p>
                <div class="mt-6 flex items-center justify-between">
                    <div class="flex items-center gap-3 text-xs font-medium text-slate-500">
                        <span>{{ $lead->date_label }}</span>
                        <span class="h-1 w-1 rounded-full bg-slate-300"></span>
                        <span>{{ $lead->read_minutes }} menit baca</span>
                    </div>
                    <span class="text-sm font-bold text-brand">Baca →</span>
                </div>
            </div>
        </a>

        @if($rest->isNotEmpty())
            <div class="reveal-stagger mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($rest as $n)
                    <a
                        href="{{ route('berita.show', $n->code) }}"
                        class="sd-card sd-card-hover group flex flex-col overflow-hidden text-left motion-safe:hover:-translate-y-1"
                    >
                        <div class="relative aspect-[16/10] w-full overflow-hidden bg-slate-100">
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
                            <div class="flex items-center gap-3 text-xs font-medium text-slate-500">
                                <span>{{ $n->date_label }}</span>
                                <span class="h-1 w-1 rounded-full bg-slate-300"></span>
                                <span>{{ $n->read_minutes }} menit baca</span>
                            </div>
                            <h3 class="mt-3 line-clamp-3 min-h-[4.1rem] text-[17px] leading-snug font-bold tracking-tight text-slate-900 transition-colors group-hover:text-brand">
                                {{ $n->title }}
                            </h3>
                            <p class="mt-2 line-clamp-2 text-sm leading-relaxed text-slate-500">{{ $n->excerpt }}</p>
                            <div class="mt-auto flex items-center justify-between pt-5">
                                <span class="flex items-center gap-2 text-xs font-medium text-slate-600">
                                    <span class="grid h-7 w-7 place-items-center rounded-full bg-sky-50 text-[10px] font-bold text-brand-dark ring-1 ring-sky-200/70">
                                        {{ $n->author_initials }}
                                    </span>
                                    {{ $n->author }}
                                </span>
                                <span class="text-sm font-bold text-brand">Baca →</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    @endif
</main>
@endsection
