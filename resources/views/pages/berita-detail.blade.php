@extends('layouts.app')

@section('title', $n->title . ' - Satu Data UNUSA')

@section('content')
<main class="relative mx-auto max-w-[1200px] overflow-x-clip px-4 py-10 sm:px-8">
    <div data-scene="ambient"
         aria-hidden="true"
         class="pointer-events-none absolute top-4 right-4 hidden h-32 w-72 opacity-75 lg:block"
         style="mask-image: radial-gradient(closest-side, #000 58%, transparent 98%); -webkit-mask-image: radial-gradient(closest-side, #000 58%, transparent 98%);"></div>
    <div class="relative z-10">
        <a href="{{ route('berita') }}" class="text-sm font-semibold text-sky-600 hover:text-sky-700">
            ← Kembali ke Berita
        </a>

        <div class="mt-6 grid grid-cols-1 gap-12 lg:grid-cols-[1fr_320px]">
        <article class="sd-card p-6 sm:p-8">
            <span class="rounded-lg bg-brand px-2.5 py-1 text-[11px] font-bold text-white shadow-2xs">
                {{ $n->badge }}
            </span>
            <h1 class="mt-4 text-3xl leading-tight font-extrabold tracking-tight text-slate-900 sm:text-4xl">
                {{ $n->title }}
            </h1>
            <div class="mt-4 flex items-center gap-3">
                <span class="grid h-9 w-9 place-items-center rounded-full bg-sky-50 text-xs font-bold text-brand-dark ring-1 ring-sky-200/70">
                    {{ $n->author_initials }}
                </span>
                <div>
                    <p class="text-sm font-semibold text-slate-900">{{ $n->author }}</p>
                    <div class="flex items-center gap-3 text-xs text-slate-500">
                        <span>{{ $n->date_label }}</span>
                        <span class="h-1 w-1 rounded-full bg-slate-300"></span>
                        <span>{{ $n->read_minutes }} menit baca</span>
                    </div>
                </div>
            </div>
            <img
                src="{{ $n->image_url }}"
                alt="{{ $n->title }}"
                class="mt-8 aspect-[16/9] w-full rounded-2xl border border-slate-200/70 object-cover shadow-sm"
            />
            <p class="mt-8 text-lg leading-relaxed font-medium text-slate-700">
                {{ $n->excerpt }}
            </p>
            @foreach($n->body ?? [] as $p)
                <p class="mt-5 leading-relaxed text-slate-600">
                    {{ $p }}
                </p>
            @endforeach
        </article>

        <aside>
            <div class="sd-card sticky top-24 p-5">
                <h2 class="text-xs font-bold tracking-wider text-slate-400 uppercase">
                    Berita lainnya
                </h2>
                <div class="mt-4 space-y-5">
                    @foreach($related as $r)
                        <a href="{{ route('berita.show', $r->code) }}" class="group flex w-full gap-3 text-left">
                            <img
                                src="{{ $r->image_url }}"
                                alt="{{ $r->title }}"
                                class="h-16 w-24 shrink-0 rounded-xl border border-slate-200/70 object-cover"
                            />
                            <div class="min-w-0">
                                <p class="text-[11px] font-bold text-brand">{{ $r->badge }}</p>
                                <p class="line-clamp-2 text-sm leading-snug font-semibold text-slate-900 transition-colors group-hover:text-brand">
                                    {{ $r->title }}
                                </p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </aside>
        </div>
    </div>
</main>
@endsection
