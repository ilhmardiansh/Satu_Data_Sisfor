<div
    x-data="{
        items: [],
        init() {
            @if(session('success'))
                this.push(@js(session('success')), 'success');
            @endif
            @if(session('error'))
                this.push(@js(session('error')), 'error');
            @endif
            @if(session('info'))
                this.push(@js(session('info')), 'info');
            @endif
            window.addEventListener('toast', (e) => {
                if (e.detail && e.detail.message) {
                    this.push(e.detail.message, e.detail.tone || 'success');
                }
            });
        },
        push(msg, tone = 'success') {
            const id = Date.now() + Math.random();
            this.items.push({ id, msg, tone });
            setTimeout(() => {
                this.items = this.items.filter((i) => i.id !== id);
            }, 3500);
        }
    }"
    class="pointer-events-none fixed right-4 bottom-4 z-[70] flex flex-col gap-2"
>
    <template x-for="item in items" :key="item.id">
        <div
            role="status"
            x-transition
            class="pointer-events-auto flex max-w-sm items-start gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 shadow-lg"
        >
            <template x-if="item.tone === 'success'">
                <svg class="mt-0.5 h-4 w-4 shrink-0 text-[#047857]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </template>
            <template x-if="item.tone === 'error'">
                <svg class="mt-0.5 h-4 w-4 shrink-0 text-[#B91C1C]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            </template>
            <template x-if="item.tone === 'info'">
                <svg class="mt-0.5 h-4 w-4 shrink-0 text-[#0069A8]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
            </template>
            <span x-text="item.msg"></span>
        </div>
    </template>
</div>
