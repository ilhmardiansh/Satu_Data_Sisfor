@extends('layouts.app')

@section('title', $n['title'] . ' - Satu Data UNUSA')

@section('content')
<main class="mx-auto max-w-[1200px] px-4 py-10 sm:px-8">
    <a
        href="{{ route('berita') }}"
        class="text-sm font-semibold text-sky-600 hover:text-sky-700 inline-flex items-center gap-1"
    >
        ← Kembali ke Berita
    </a>

    <div class="mt-6 grid grid-cols-1 gap-12 lg:grid-cols-[1fr_320px]">
        <article>
            <span class="rounded-md bg-blue-600 px-2.5 py-1 text-[11px] font-bold text-white">
                {{ $n['badge'] }}
            </span>
            <h1 class="mt-4 text-4xl leading-tight font-extrabold tracking-tight text-slate-900">
                {{ $n['title'] }}
            </h1>
            <div class="mt-4 flex items-center gap-3">
                <span class="grid h-9 w-9 place-items-center rounded-full bg-sky-100 text-xs font-bold text-sky-700">
                    {{ $n['ini'] }}
                </span>
                <div>
                    <p class="text-sm font-semibold text-slate-900">{{ $n['au'] }}</p>
                    <div class="flex items-center gap-3 text-xs text-slate-500">
                        <span>{{ $n['date'] }}</span>
                        <span class="h-1 w-1 rounded-full bg-slate-300"></span>
                        <span>{{ $n['read'] }} menit baca</span>
                    </div>
                </div>
            </div>
            <img
                src="{{ $n['img'] }}"
                alt=""
                class="mt-8 aspect-[16/9] w-full rounded-2xl object-cover"
            />
            <p class="mt-8 text-lg leading-relaxed font-medium text-slate-700">
                {{ $n['ex'] }}
            </p>
            @foreach($n['body'] as $p)
                <p class="mt-5 leading-relaxed text-slate-600">
                    {{ $p }}
                </p>
            @endforeach
        </article>

        <aside>
            <div class="sticky top-24">
                <h2 class="text-sm font-bold tracking-wider text-slate-400 uppercase">
                    Berita lainnya
                </h2>
                <div class="mt-4 space-y-5">
                    @foreach($related as $r)
                        <a
                            href="{{ route('berita.show', $r['id']) }}"
                            class="group flex w-full gap-3 text-left"
                        >
                            <img
                                src="{{ $r['img'] }}"
                                alt=""
                                class="h-16 w-24 shrink-0 rounded-lg object-cover"
                            />
                            <div class="min-w-0">
                                <p class="text-[11px] font-bold text-sky-600">
                                    {{ $r['badge'] }}
                                </p>
                                <p class="line-clamp-2 text-sm leading-snug font-semibold text-slate-900 group-hover:text-blue-700">
                                    {{ $r['title'] }}
                                </p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </aside>
    </div>
</main>
@endsection
