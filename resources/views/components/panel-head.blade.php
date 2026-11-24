@props(['title', 'desc' => null])

<div class="mb-6 flex flex-wrap items-end justify-between gap-4 border-b border-slate-200/80 pb-5">
    <div>
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
