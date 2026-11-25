@props(['access' => 'public'])

@if($access === 'public')
    <span class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 text-[11px] font-bold text-emerald-700">
        <i data-lucide="globe-2" class="h-3 w-3"></i>
        Publik
    </span>
@else
    <span class="inline-flex items-center gap-1 rounded-md bg-amber-50 px-2 py-0.5 text-[11px] font-bold text-amber-700">
        <i data-lucide="lock" class="h-3 w-3"></i>
        Private
    </span>
@endif
