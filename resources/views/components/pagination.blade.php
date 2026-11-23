@if ($paginator->hasPages())
    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-4 py-3 text-sm text-slate-500">
        <span>
            {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} dari {{ $paginator->total() }}
        </span>
        <div class="flex items-center gap-1.5">
            @if ($paginator->onFirstPage())
                <span class="inline-flex h-9 w-9 cursor-not-allowed items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-400 opacity-50" aria-disabled="true">
                    <i data-lucide="chevron-left" class="h-4 w-4"></i>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-sky-300 hover:text-sky-700" aria-label="Sebelumnya">
                    <i data-lucide="chevron-left" class="h-4 w-4"></i>
                </a>
            @endif

            <span class="px-2 font-semibold text-slate-700">
                {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
            </span>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-sky-300 hover:text-sky-700" aria-label="Berikutnya">
                    <i data-lucide="chevron-right" class="h-4 w-4"></i>
                </a>
            @else
                <span class="inline-flex h-9 w-9 cursor-not-allowed items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-400 opacity-50" aria-disabled="true">
                    <i data-lucide="chevron-right" class="h-4 w-4"></i>
                </span>
            @endif
        </div>
    </div>
@endif
