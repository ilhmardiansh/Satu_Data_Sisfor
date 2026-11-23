@extends('layouts.app')

@section('title', 'Berita Sistem Informasi - Satu Data UNUSA')

@section('content')
@php
    $selectedTag = request('tag', 'Semua');
    $q = request('q', '');
    
    $tags = ['Semua', 'Akademik', 'Prestasi Mahasiswa', 'Publikasi Dosen', 'Pengabdian', 'Kegiatan'];

    $filteredNews = array_filter($berita, function($n) use ($selectedTag, $q) {
        $matchesTag = ($selectedTag === 'Semua' || $n['badge'] === $selectedTag);
        $matchesSearch = empty($q) || (stripos($n['title'], $q) !== false || stripos($n['ex'], $q) !== false);
        return $matchesTag && $matchesSearch;
    });

    $filteredNews = array_values($filteredNews);
    $lead = $filteredNews[0] ?? null;
    $rest = array_slice($filteredNews, 1);
@endphp

<main class="mx-auto max-w-[1200px] px-4 py-10 sm:px-8">
    <!-- Breadcrumb -->
    <p class="text-sm text-slate-400">
        <a href="{{ route('beranda') }}" class="hover:text-sky-600 transition">Beranda</a>
        <span class="mx-1">/</span>
        <span class="text-sky-600">Berita</span>
    </p>

    <!-- Header & Search -->
    <div class="mt-1 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between sm:gap-6">
        <div class="min-w-0">
            <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">
                Berita Sistem Informasi
            </h1>
            <p class="mt-1 text-sm text-slate-500">
                Kabar prestasi, riset, pengabdian, dan kegiatan Program Studi Sistem Informasi UNUSA.
            </p>
        </div>
        <form action="{{ route('berita') }}" method="GET" class="w-full sm:w-72 shrink-0">
            @if(request('tag') && request('tag') !== 'Semua')
                <input type="hidden" name="tag" value="{{ request('tag') }}">
            @endif
            <input
                type="text"
                name="q"
                value="{{ $q }}"
                placeholder="Cari berita…"
                aria-label="Cari berita"
                class="h-10 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
            />
        </form>
    </div>

    <!-- Tags Pills -->
    <div class="mt-6 flex gap-2 overflow-x-auto border-b border-slate-200 pb-4">
        @foreach($tags as $t)
            <a
                href="{{ route('berita', array_filter(['tag' => $t === 'Semua' ? null : $t, 'q' => $q ?: null])) }}"
                class="shrink-0 rounded-full px-4 py-1.5 text-sm font-medium whitespace-nowrap transition motion-safe:active:scale-[0.97] {{ $selectedTag === $t ? 'bg-sky-600 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:text-slate-900' }}"
            >
                {{ $t }}
            </a>
        @endforeach
    </div>

    <!-- Empty State -->
    @if(!$lead)
        <p class="mt-8 rounded-2xl border border-dashed border-slate-300 py-12 text-center text-sm text-slate-500">
            Tidak ada berita yang cocok.
        </p>
    @else
        <!-- Lead Article (Matching Berita.tsx) -->
        <a
            href="{{ route('berita.show', $lead['id']) }}"
            class="group mt-8 grid w-full grid-cols-1 overflow-hidden rounded-2xl border border-slate-200 bg-surface text-left transition duration-300 ease-out hover:shadow-xl hover:shadow-slate-200 md:grid-cols-[1.3fr_1fr]"
        >
            <div class="relative aspect-[16/10] overflow-hidden">
                <img
                    src="{{ $lead['img'] }}"
                    alt=""
                    class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                />
            </div>
            <div class="flex min-w-0 flex-col justify-center p-6 sm:p-8">
                <span class="w-fit rounded-md bg-blue-600 px-2.5 py-1 text-[11px] font-bold text-white">
                    {{ $lead['badge'] }}
                </span>
                <h2 class="mt-4 text-2xl leading-snug font-extrabold group-hover:text-blue-700">
                    {{ $lead['title'] }}
                </h2>
                <p class="mt-3 text-slate-500">{{ $lead['ex'] }}</p>
                <div class="mt-6 flex items-center justify-between">
                    <div class="flex items-center gap-3 text-xs text-slate-500">
                        <span>{{ $lead['date'] }}</span>
                        <span class="h-1 w-1 rounded-full bg-slate-300"></span>
                        <span>{{ $lead['read'] }} menit baca</span>
                    </div>
                    <span class="text-sm font-bold text-blue-600">Baca →</span>
                </div>
            </div>
        </a>

        <!-- Rest Articles Grid -->
        @if(count($rest))
            <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($rest as $n)
                    <a
                        href="{{ route('berita.show', $n['id']) }}"
                        class="group flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-surface text-left transition duration-300 ease-out hover:shadow-xl hover:shadow-slate-200 motion-safe:hover:-translate-y-1"
                    >
                        <div class="relative aspect-[16/10] w-full overflow-hidden">
                            <img
                                src="{{ $n['img'] }}"
                                alt=""
                                class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                            />
                            <span class="absolute top-3 left-3 rounded-md bg-blue-600 px-2.5 py-1 text-[11px] font-bold text-white">
                                {{ $n['badge'] }}
                            </span>
                        </div>
                        <div class="flex flex-1 flex-col p-5">
                            <div class="flex items-center gap-3 text-xs text-slate-500">
                                <span>{{ $n['date'] }}</span>
                                <span class="h-1 w-1 rounded-full bg-slate-300"></span>
                                <span>{{ $n['read'] }} menit baca</span>
                            </div>
                            <h3 class="mt-3 line-clamp-3 min-h-[4.1rem] text-[17px] leading-snug font-bold group-hover:text-blue-700">
                                {{ $n['title'] }}
                            </h3>
                            <p class="mt-2 line-clamp-2 text-sm text-slate-500">{{ $n['ex'] }}</p>
                            <div class="mt-auto flex items-center justify-between pt-5">
                                <span class="flex items-center gap-2 text-xs font-medium text-slate-600">
                                    <span class="grid h-7 w-7 place-items-center rounded-full bg-sky-100 text-[10px] font-bold text-sky-700">
                                        {{ $n['ini'] }}
                                    </span>
                                    {{ $n['au'] }}
                                </span>
                                <span class="text-sm font-bold text-blue-600">Baca →</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    @endif
</main>
@endsection
