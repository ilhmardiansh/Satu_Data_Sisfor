@props([
    'eyebrow' => null,
    'title',
    'desc' => null,
    'badge' => null,
    'badgeTone' => 'info',
    'scene' => null,
])

@if($scene)
    @php
        $darkBadgeClass = match ($badgeTone) {
            'emerald', 'success' => 'bg-emerald-400/20 text-emerald-200 ring-1 ring-emerald-300/30',
            'amber', 'warning' => 'bg-amber-400/20 text-amber-200 ring-1 ring-amber-300/30',
            'red', 'danger' => 'bg-red-400/20 text-red-200 ring-1 ring-red-300/30',
            default => 'bg-sky-400/20 text-[#74D4FF] ring-1 ring-sky-300/30',
        };
    @endphp

    <header class="wd-stage-frame mb-6 px-5 py-5 sm:px-7 sm:py-6">
        <div class="pointer-events-none absolute inset-0" aria-hidden="true">
            <div data-scene="walidata-header" data-scene-mode="{{ $scene }}" class="h-full w-full"></div>
        </div>
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-r from-[#09122C]/92 via-[#101F4A]/72 to-transparent" aria-hidden="true"></div>

        <div class="relative z-10 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2.5">
                    <h1 class="text-2xl font-extrabold tracking-tight text-white">{{ $title }}</h1>
                    @if($badge)
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold tabular-nums {{ $darkBadgeClass }}">
                            {{ $badge }}
                        </span>
                    @endif
                </div>
                @if($desc)
                    <p class="mt-1.5 max-w-2xl text-sm text-slate-200">{{ $desc }}</p>
                @endif
            </div>

            @if(isset($slot) && trim((string) $slot) !== '')
                <div class="flex shrink-0 flex-wrap items-center gap-2">
                    {{ $slot }}
                </div>
            @endif
        </div>
    </header>
@else
    @php
        $badgeClass = match ($badgeTone) {
            'emerald', 'success' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            'amber', 'warning' => 'border-amber-200 bg-amber-50 text-amber-800',
            'red', 'danger' => 'border-red-200 bg-red-50 text-red-700',
            'slate', 'neutral' => 'border-slate-200 bg-slate-100 text-slate-700',
            default => 'border-[#bae6fd] bg-[#F0F9FF] text-[#0069A8]',
        };
    @endphp

    <header class="mb-6 border-b border-slate-200/80 pb-5">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2.5">
                    <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ $title }}</h1>
                    @if($badge)
                        <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-bold tabular-nums {{ $badgeClass }}">
                            {{ $badge }}
                        </span>
                    @endif
                </div>
                @if($desc)
                    <p class="mt-1.5 max-w-2xl text-sm text-[#475569]">{{ $desc }}</p>
                @endif
            </div>

            @if(isset($slot) && trim((string) $slot) !== '')
                <div class="flex shrink-0 flex-wrap items-center gap-2">
                    {{ $slot }}
                </div>
            @endif
        </div>
    </header>
@endif
