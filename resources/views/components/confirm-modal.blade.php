<div
    x-data="{
        open: false,
        title: 'Hapus data?',
        message: '',
        action: '',
        method: 'DELETE',
        buttonText: 'Hapus',
        tone: 'danger',
        warningText: 'Tindakan ini tidak dapat dibatalkan.',
        init() {
            window.addEventListener('open-confirm', (e) => {
                const d = e.detail || {};
                this.title = d.title || 'Hapus data?';
                this.message = d.message || '';
                this.action = d.action || '';
                this.method = (d.method || 'DELETE').toUpperCase();
                this.buttonText = d.buttonText || 'Hapus';
                this.tone = d.tone || 'danger';
                this.warningText = d.warningText !== undefined ? d.warningText : 'Tindakan ini tidak dapat dibatalkan.';
                this.open = true;
            });
        }
    }"
    x-show="open"
    x-cloak
    @keydown.escape.window="open = false"
    class="fixed inset-0 z-[65] grid place-items-end bg-slate-900/50 sm:place-items-center sm:p-6"
    @click.self="open = false"
>
    <div
        role="dialog"
        aria-modal="true"
        aria-labelledby="confirm-modal-title"
        class="flex w-full max-w-md flex-col overflow-hidden rounded-t-2xl border border-slate-200 bg-white shadow-2xl sm:rounded-2xl"
    >
        <div class="flex gap-4 px-6 py-6 sm:px-8">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full"
                  :class="tone === 'primary' ? 'bg-[#F0F9FF] text-[#0069A8]' : 'bg-red-50 text-[#B91C1C]'">
                <template x-if="tone === 'primary'">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                </template>
                <template x-if="tone !== 'primary'">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </template>
            </span>
            <div>
                <h2 id="confirm-modal-title" class="text-lg font-bold text-slate-900" x-text="title"></h2>
                <p class="mt-1 text-sm text-[#45556C]" x-text="message"></p>
                <p x-show="warningText !== ''" class="mt-2 text-xs text-[#62748E]" x-text="warningText"></p>
            </div>
        </div>
        <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:px-8">
            <button type="button" @click="open = false" class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-100">
                Batal
            </button>
            <form :action="action" method="POST" class="inline">
                @csrf
                <template x-if="method !== 'POST'">
                    <input type="hidden" name="_method" :value="method">
                </template>
                <button type="submit"
                        :class="tone === 'primary' ? 'bg-[#0069A8] hover:bg-[#005187]' : 'bg-[#DC3545] hover:bg-[#b02a37]'"
                        class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold text-white transition-colors">
                    <template x-if="tone !== 'primary'">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                    </template>
                    <span x-text="buttonText"></span>
                </button>
            </form>
        </div>
    </div>
</div>
