@props([
    'title',
    'desc' => null,
    'eyebrow' => null,
    'scene' => null,
    'badge' => null,
    'badgeTone' => 'sky',
])

@if($scene)
    <header class="wd-stage-frame mb-6 px-6 py-6 sm:px-8 sm:py-7">
        <div data-scene="walidata-header"
             data-scene-mode="{{ $scene }}"
             aria-hidden="true"
             class="pointer-events-none absolute inset-y-0 right-0 hidden w-[54%] opacity-90 md:block"
             style="mask-image: linear-gradient(to left, #000 58%, transparent 100%); -webkit-mask-image: linear-gradient(to left, #000 58%, transparent 100%);"></div>

        <div class="relative z-10 flex flex-wrap items-end justify-between gap-5">
            <div class="max-w-2xl">
                @if($eyebrow || $badge)
                    <div class="mb-2 flex flex-wrap items-center gap-2.5">
                        @if($eyebrow)
                            <span class="font-mono text-[11px] font-bold tracking-[0.16em] text-[#74D4FF] uppercase">
                                {{ $eyebrow }}
                            </span>
                        @endif
                        @if($badge)
                            @php
                                $badgeCls = match ($badgeTone) {
                                    'amber' => 'bg-amber-400/20 text-amber-200 ring-amber-300/35',
                                    'emerald' => 'bg-emerald-400/20 text-emerald-200 ring-emerald-300/35',
                                    default => 'bg-sky-400/20 text-[#BAE6FD] ring-sky-300/35',
                                };
                            @endphp
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 font-mono text-[11px] font-bold ring-1 {{ $badgeCls }}">
                                {{ $badge }}
                            </span>
                        @endif
                    </div>
                @endif

                <h1 class="text-2xl font-extrabold tracking-tight text-balance text-white sm:text-[28px]">
                    {{ $title }}
                </h1>

                @if($desc)
                    <p class="mt-2 max-w-2xl text-sm leading-relaxed text-slate-200/95 sm:text-[15px]">
                        {{ $desc }}
                    </p>
                @endif
            </div>

            @if(trim($slot) !== '')
                <div class="relative z-10 flex flex-wrap items-center gap-2.5">
                    {{ $slot }}
                </div>
            @endif
        </div>
    </header>
@else
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4 border-b border-slate-200/80 pb-5">
        <div>
            @if($eyebrow)
                <p class="mb-1 font-mono text-[11px] font-bold tracking-[0.14em] text-[#0069A8] uppercase">{{ $eyebrow }}</p>
            @endif
            <h1 class="text-2xl font-extrabold tracking-tight text-slate-900 sm:text-[26px]">{{ $title }}</h1>
            @if($desc)
                <p class="mt-1 text-[15px] text-[#45556C]">{{ $desc }}</p>
            @endif
        </div>
        @if(trim($slot) !== '')
            <div class="flex flex-wrap gap-2">
                {{ $slot }}
            </div>
        @endif
    </div>
@endif
