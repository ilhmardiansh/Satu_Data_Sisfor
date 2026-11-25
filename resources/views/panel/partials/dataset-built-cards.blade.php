<div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
    @foreach($builtDatasets as $built)
        <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-[#F8FAFC] p-3.5 text-xs">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="rounded bg-white px-1.5 py-0.5 font-bold text-[#0069A8] ring-1 ring-slate-200/80">{{ $built['dataset_code'] }}</span>
                    <span class="rounded bg-slate-200/70 px-1.5 py-0.5 font-semibold text-slate-700">Dari {{ $built['form_code'] }}</span>
                    <span class="rounded px-1.5 py-0.5 font-bold {{ $built['is_published'] ? 'bg-[#F0F9FF] text-[#0069A8]' : 'bg-slate-200/80 text-slate-700' }}">
                        {{ $built['is_published'] ? 'Terbit' : 'Draft' }}
                    </span>
                    <span class="rounded px-1.5 py-0.5 font-bold {{ $built['access'] === 'public' ? 'bg-[#ECFDF5] text-[#047857]' : 'bg-[#FFFBEB] text-[#92400E]' }}">
                        {{ $built['access'] === 'public' ? 'Publik' : 'Private' }}
                    </span>
                </div>
                <p class="mt-1.5 truncate text-sm font-bold text-slate-900">{{ $built['dataset_title'] }}</p>
            </div>
            <div class="flex shrink-0 items-center gap-1.5">
                <a href="{{ route('panel.dataset.show', $built['dataset_code']) }}"
                   class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-2.5 font-bold text-slate-700 hover:border-[#0069A8] hover:text-[#0069A8]">
                    Lihat
                </a>
                <a href="{{ route('panel.dataset.edit', $built['dataset_code']) }}"
                   class="inline-flex h-8 items-center rounded-lg bg-[#0069A8] px-2.5 font-bold text-white hover:bg-[#005187]">
                    Ubah
                </a>
                <button type="button"
                        @click="$dispatch('open-confirm', {
                            title: 'Hapus dataset ini?',
                            message: @js("Dataset “{$built['dataset_title']}” ({$built['dataset_code']}) beserta visualisasi terkait akan dihapus agar formulir {$built['form_code']} dapat disusun ulang."),
                            action: @js(route('panel.dataset.destroy', [$built['dataset_code'], 'redirect_to' => 'create'])),
                            method: 'DELETE',
                            buttonText: 'Ya, Hapus Dataset'
                        })"
                        title="Hapus dataset"
                        aria-label="Hapus dataset {{ $built['dataset_code'] }}"
                        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-red-200 bg-white text-[#B91C1C] transition hover:bg-red-50">
                    <i data-lucide="trash-2" class="h-3.5 w-3.5" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    @endforeach
</div>
