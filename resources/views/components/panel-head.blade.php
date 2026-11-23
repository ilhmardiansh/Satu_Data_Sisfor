@props(['title', 'desc' => null])

<div class="mb-6 flex flex-wrap items-end justify-between gap-4 border-b border-slate-200/70 pb-5">
    <div>
        <h1 class="text-[26px] font-extrabold tracking-tight text-slate-900">{{ $title }}</h1>
        @if($desc)
            <p class="mt-1 text-[15px] text-slate-500">{{ $desc }}</p>
        @endif
    </div>
    @if(trim($slot) !== '')
        <div class="flex flex-wrap gap-2">
            {{ $slot }}
        </div>
    @endif
</div>
