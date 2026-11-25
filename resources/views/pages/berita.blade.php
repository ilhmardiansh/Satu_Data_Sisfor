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
            <p class="text-sm text-slate-600">
                <a href="{{ route('beranda') }}" class="transition hover:text-[#0069A8]">{{ $t('Beranda', 'Home') }}</a>
                <span class="mx-1">/</span>
                <span class="font-medium text-[#0069A8]">{{ $t('Berita', 'News') }}</span>
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

    <div class="mt-6 flex gap-2 overflow-x-auto border-b border-slate-200 pb-4">
        @foreach($tags as $tag)
            <a
                href="{{ route('berita', array_filter(['tag' => $tag === 'Semua' ? null : $tag, 'q' => $q ?: null])) }}"
                aria-pressed="{{ $selectedTag === $tag ? 'true' : 'false' }}"
                class="shrink-0 rounded-full px-4 py-2 text-sm font-semibold whitespace-nowrap transition-colors {{ $selectedTag === $tag ? 'bg-[#0069A8] text-white' : 'bg-[#F6F9FC] text-[#45556C] hover:bg-slate-200/70 hover:text-slate-900' }}"
            >
                {{ $tag }}
            </a>
        @endforeach
    </div>

    @if(! $lead)
        <p class="mt-8 rounded-2xl border border-dashed border-slate-300 bg-[#F6F9FC] py-12 text-center text-sm text-[#45556C]">
            Tidak ada berita yang cocok dengan kata kunci atau kategori yang dipilih.
        </p>
    @else
        <a
            href="{{ route('berita.show', $lead->code) }}"
            class="group mt-8 grid w-full grid-cols-1 items-center gap-6 overflow-hidden rounded-2xl bg-[#F6F9FC] p-4 text-left transition-colors hover:bg-[#F0F9FF]/70 sm:p-6 md:grid-cols-[1.25fr_1fr]"
        >
            <div class="relative aspect-[16/10] overflow-hidden rounded-xl bg-slate-200">
                <img
                    src="{{ $lead->image_url }}"
                    alt="{{ $lead->title }}"
                    class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.03]"
                />
            </div>
            <div class="flex min-w-0 flex-col justify-center sm:px-2">
                <span class="w-fit rounded-lg bg-[#0069A8] px-2.5 py-1 text-[11px] font-bold text-white">
                    {{ $lead->badge }}
                </span>
                <h2 class="mt-4 text-2xl leading-snug font-extrabold tracking-tight text-slate-900 transition-colors group-hover:text-[#0069A8]">
                    {{ $lead->title }}
                </h2>
                <p class="mt-3 leading-relaxed text-[#45556C]">{{ $lead->excerpt }}</p>
                <div class="mt-6 flex items-center justify-between">
                    <div class="flex items-center gap-3 text-xs font-medium text-[#62748E]">
                        <span>{{ $lead->date_label }}</span>
                        <span class="h-1 w-1 rounded-full bg-slate-300"></span>
                        <span>{{ $lead->read_minutes }} menit baca</span>
                    </div>
                    <span class="text-sm font-bold text-[#0069A8]">Baca berita</span>
                </div>
            </div>
        </a>

        @if($rest->isNotEmpty())
            <div class="reveal-stagger mt-10 grid grid-cols-1 gap-8 border-t border-slate-200/80 pt-10 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($rest as $n)
                    <a
                        href="{{ route('berita.show', $n->code) }}"
                        class="group flex flex-col text-left"
                    >
                        <div class="relative aspect-[16/10] w-full overflow-hidden rounded-xl bg-slate-100">
                            <img
                                src="{{ $n->image_url }}"
                                alt="{{ $n->title }}"
                                loading="lazy"
                                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.03]"
                            />
                            <span class="absolute top-3 left-3 rounded-lg bg-[#0069A8] px-2.5 py-1 text-[11px] font-bold text-white">
                                {{ $n->badge }}
                            </span>
                        </div>
                        <div class="mt-4 flex flex-1 flex-col">
                            <div class="flex items-center gap-3 text-xs font-medium text-[#62748E]">
                                <span>{{ $n->date_label }}</span>
                                <span class="h-1 w-1 rounded-full bg-slate-300"></span>
                                <span>{{ $n->read_minutes }} menit baca</span>
                            </div>
                            <h3 class="mt-2.5 line-clamp-3 text-[17px] leading-snug font-bold tracking-tight text-slate-900 transition-colors group-hover:text-[#0069A8]">
                                {{ $n->title }}
                            </h3>
                            <p class="mt-2 line-clamp-2 text-sm leading-relaxed text-[#45556C]">{{ $n->excerpt }}</p>
                            <div class="mt-auto flex items-center justify-between pt-4">
                                <span class="flex items-center gap-2 text-xs font-medium text-[#62748E]">
                                    <span class="grid h-7 w-7 place-items-center rounded-full bg-[#F0F9FF] text-[10px] font-bold text-[#0069A8]">
                                        {{ $n->author_initials }}
                                    </span>
                                    {{ $n->author }}
                                </span>
                                <span class="text-sm font-bold text-[#0069A8]">Baca</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    @endif
</main>
@endsection
