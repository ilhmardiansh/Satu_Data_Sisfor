<div
    x-data="{
        open: false,
        title: 'Hapus data?',
        message: '',
        action: '',
        method: 'DELETE',
        buttonText: 'Hapus',
        init() {
            window.addEventListener('open-confirm', (e) => {
                const d = e.detail || {};
                this.title = d.title || 'Hapus data?';
                this.message = d.message || '';
                this.action = d.action || '';
                this.method = (d.method || 'DELETE').toUpperCase();
                this.buttonText = d.buttonText || 'Hapus';
                this.open = true;
            });
        }
    }"
    x-show="open"
    x-cloak
    @keydown.escape.window="open = false"
    class="fixed inset-0 z-[65] grid place-items-end bg-slate-900/40 backdrop-blur-sm sm:place-items-center sm:p-6"
    @click.self="open = false"
>
    <div
        role="dialog"
        aria-modal="true"
        class="flex w-full max-w-md flex-col overflow-hidden rounded-t-2xl bg-white shadow-2xl sm:rounded-2xl"
    >
        <div class="flex gap-4 px-6 py-6 sm:px-8">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-red-50 text-[#DC3545]">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            </span>
            <div>
                <h2 class="text-lg font-bold text-slate-900" x-text="title"></h2>
                <p class="mt-1 text-sm text-slate-600" x-text="message"></p>
                <p class="mt-2 text-xs text-slate-500">Tindakan ini tidak dapat dibatalkan.</p>
            </div>
        </div>
        <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:px-8">
            <button type="button" @click="open = false" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                Batal
            </button>
            <form :action="action" method="POST" class="inline">
                @csrf
                <template x-if="method !== 'POST'">
                    <input type="hidden" name="_method" :value="method">
                </template>
                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#DC3545] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#bb2d3b]">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                    <span x-text="buttonText"></span>
                </button>
            </form>
        </div>
    </div>
</div>
