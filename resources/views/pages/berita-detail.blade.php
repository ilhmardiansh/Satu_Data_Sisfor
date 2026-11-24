@extends('layouts.app')

@section('title', $n->title . ' - Satu Data UNUSA')

@section('content')
<main class="relative mx-auto max-w-[1200px] overflow-x-clip px-4 py-10 sm:px-8">
    <div data-scene="ambient"
         aria-hidden="true"
         class="pointer-events-none absolute top-4 right-4 hidden h-32 w-72 opacity-75 lg:block"
         style="mask-image: radial-gradient(closest-side, #000 58%, transparent 98%); -webkit-mask-image: radial-gradient(closest-side, #000 58%, transparent 98%);"></div>
    <div class="relative z-10">
        <a href="{{ route('berita') }}" class="text-sm font-semibold text-[#0069A8] hover:text-[#1E3575]">
            ← Kembali ke Berita
        </a>

        <div class="mt-6 grid grid-cols-1 gap-12 lg:grid-cols-[1fr_320px]">
        <article>
            <span class="rounded-lg bg-[#0069A8] px-2.5 py-1 text-[11px] font-bold text-white">
                {{ $n->badge }}
            </span>
            <h1 class="mt-4 text-3xl leading-tight font-extrabold tracking-tight text-slate-900 sm:text-4xl">
                {{ $n->title }}
            </h1>
            <div class="mt-4 flex items-center gap-3">
                <span class="grid h-9 w-9 place-items-center rounded-full bg-[#F0F9FF] text-xs font-bold text-[#0069A8]">
                    {{ $n->author_initials }}
                </span>
                <div>
                    <p class="text-sm font-semibold text-slate-900">{{ $n->author }}</p>
                    <div class="flex items-center gap-3 text-xs text-[#62748E]">
                        <span>{{ $n->date_label }}</span>
                        <span class="h-1 w-1 rounded-full bg-slate-300"></span>
                        <span>{{ $n->read_minutes }} menit baca</span>
                    </div>
                </div>
            </div>
            <img
                src="{{ $n->image_url }}"
                alt="{{ $n->title }}"
                class="mt-8 aspect-[16/9] w-full rounded-2xl object-cover"
            />
            <p class="mt-8 text-lg leading-relaxed font-medium text-slate-800">
                {{ $n->excerpt }}
            </p>
            @foreach($n->body ?? [] as $p)
                <p class="mt-5 leading-relaxed text-[#45556C]">
                    {{ $p }}
                </p>
            @endforeach
        </article>

        <aside>
            <div class="sticky top-24 rounded-2xl bg-[#F6F9FC] p-5">
                <h2 class="text-xs font-bold tracking-wider text-[#62748E] uppercase">
                    Berita lainnya
                </h2>
                <div class="mt-4 space-y-5">
                    @foreach($related as $r)
                        <a href="{{ route('berita.show', $r->code) }}" class="group flex w-full gap-3 text-left">
                            <img
                                src="{{ $r->image_url }}"
                                alt="{{ $r->title }}"
                                loading="lazy"
                                class="h-16 w-24 shrink-0 rounded-xl object-cover"
                            />
                            <div class="min-w-0">
                                <p class="text-[11px] font-bold text-[#0069A8]">{{ $r->badge }}</p>
                                <p class="line-clamp-2 text-sm leading-snug font-semibold text-slate-900 transition-colors group-hover:text-[#0069A8]">
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
