@extends('layouts.panel')

@section('title', 'Permohonan Data - Panel Satu Data UNUSA')

@section('panel_content')
@php
    $tabs = [
        ['k' => 'menunggu', 'label' => 'Menunggu', 'count' => $counts['menunggu'] ?? 0],
        ['k' => 'disetujui', 'label' => 'Disetujui', 'count' => $counts['disetujui'] ?? 0],
        ['k' => 'ditolak', 'label' => 'Ditolak', 'count' => $counts['ditolak'] ?? 0],
        ['k' => 'all', 'label' => 'Semua', 'count' => $counts['all'] ?? 0],
    ];
    $reopenCode = session('decide_request_code');
@endphp

<div x-data="permohonanManager()">
    <x-panel-head
        scene="permohonan"
        badge="{{ $counts['menunggu'] ?? 0 }} Menunggu"
        :badgeTone="($counts['menunggu'] ?? 0) > 0 ? 'amber' : 'sky'"
        title="Permohonan Data"
        desc="Tinjau pengajuan akses dataset berstatus Private."
    />

    <div class="wd-bento mb-6 flex flex-wrap items-center justify-between gap-3 p-3">
        <div class="flex flex-wrap gap-1 rounded-xl border border-slate-200/90 bg-[#F8FAFC] p-1">
            @foreach($tabs as $t)
                @php $active = $tab === $t['k']; @endphp
                <a href="{{ route('panel.permohonan', ['tab' => $t['k']]) }}"
                   class="inline-flex min-h-[38px] shrink-0 items-center gap-2 rounded-lg px-3.5 py-1.5 text-xs font-bold whitespace-nowrap transition {{ $active ? 'bg-[#0069A8] text-white' : 'text-slate-600 hover:text-slate-900' }}">
                    <span>{{ $t['label'] }}</span>
                    <span class="rounded-full px-1.5 text-[11px] font-bold tabular-nums {{ $active ? 'bg-white/20 text-white' : 'bg-slate-200/70 text-slate-700' }}">
                        {{ $t['count'] }}
                    </span>
                </a>
            @endforeach
        </div>

        <label class="flex h-10 min-w-[220px] flex-1 items-center gap-2 rounded-xl border border-slate-200 bg-[#F8FAFC] px-3 focus-within:border-[#0069A8] focus-within:bg-white sm:max-w-xs">
            <i data-lucide="search" class="h-4 w-4 shrink-0 text-[#475569]" aria-hidden="true"></i>
            <input type="text"
                   x-model="quickSearch"
                   placeholder="Cari pemohon, NIM, atau dataset..."
                   class="min-w-0 flex-1 bg-transparent text-sm outline-none placeholder:text-slate-400" />
        </label>
    </div>

    @if($requests->isEmpty())
        <div class="wd-bento border-dashed border-slate-300 px-6 py-12 text-center">
            <span class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-[#F0F9FF] text-[#0069A8]">
                <i data-lucide="inbox" class="h-6 w-6" aria-hidden="true"></i>
            </span>
            <p class="mt-4 text-base font-bold text-slate-900">Belum ada permohonan data</p>
            <p class="mx-auto mt-1 max-w-md text-sm text-[#475569]">
                Permohonan akses dataset berstatus Private dari pengguna publik akan muncul di sini.
            </p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($requests as $r)
                @php
                    $searchKey = strtolower($r->code.' '.$r->nama.' '.$r->nim.' '.($r->dataset?->title ?? ''));
                @endphp
                <article x-show="matchesSearch(@js($searchKey))" class="wd-bento-interactive p-5">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-md bg-[#F0F9FF] px-2 py-0.5 text-[11px] font-bold tabular-nums text-[#0069A8]">
                                    {{ $r->code }}
                                </span>
                                <x-req-status :status="$r->status" />
                                @if($r->dataset)
                                    <x-access-badge :access="$r->dataset->access" />
                                @endif
                                <span class="text-xs tabular-nums text-[#475569]">
                                    {{ $r->requested_at?->format('d M Y, H:i') }}
                                </span>
                            </div>

                            <h2 class="mt-2.5 text-base font-extrabold text-slate-900">
                                {{ $r->dataset?->title ?? 'Dataset tidak tersedia' }}
                            </h2>

                            <div class="mt-2.5 grid grid-cols-1 gap-2 rounded-xl border border-slate-200/70 bg-[#F8FAFC] px-3.5 py-2.5 text-xs text-slate-600 sm:grid-cols-2 lg:grid-cols-4">
                                <span><strong class="font-semibold text-slate-800">Pemohon:</strong> {{ $r->nama }}</span>
                                <span><strong class="font-semibold text-slate-800">NIM/NIP:</strong> <span class="tabular-nums">{{ $r->nim }}</span></span>
                                <span class="truncate"><strong class="font-semibold text-slate-800">Email:</strong> {{ $r->email }}</span>
                                <span><strong class="font-semibold text-slate-800">HP:</strong> <span class="tabular-nums">{{ $r->hp }}</span></span>
                            </div>

                            <div class="mt-3 rounded-xl border border-slate-200/80 border-l-4 border-l-[#0069A8] bg-white px-4 py-3 text-sm">
                                <p class="text-slate-700">
                                    <span class="font-semibold text-slate-900">Tujuan:</span> {{ $r->tujuan }}
                                </p>
                                @if($r->deskripsi)
                                    <p class="mt-1 text-slate-600">{{ $r->deskripsi }}</p>
                                @endif
                            </div>

                            @if($r->status !== 'menunggu')
                                <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-[#475569]">
                                    @if($r->decider)
                                        <span>Diputuskan oleh <strong class="text-slate-800">{{ $r->decider->name }}</strong></span>
                                    @endif
                                    @if($r->decided_at)
                                        <span>· {{ $r->decided_at->format('d M Y, H:i') }}</span>
                                    @endif
                                    @if($r->note)
                                        <span class="w-full text-slate-700">Catatan: “{{ $r->note }}”</span>
                                    @endif
                                </div>
                            @endif
                        </div>

                        @if($r->status === 'menunggu')
                            <div class="flex shrink-0 flex-wrap gap-2">
                                <button
                                    type="button"
                                    @click="openDecision({
                                        code: @js($r->code),
                                        datasetTitle: @js($r->dataset?->title ?? 'Dataset Private'),
                                        requester: @js($r->nama),
                                        mode: 'disetujui',
                                        action: @js(route('panel.permohonan.decide', $r->code)),
                                        note: ''
                                    })"
                                    class="inline-flex min-h-[40px] items-center gap-1.5 rounded-xl bg-[#047857] px-4 py-2 text-xs font-bold text-white transition hover:bg-[#065F46]"
                                >
                                    <i data-lucide="check" class="h-3.5 w-3.5" aria-hidden="true"></i>
                                    Setujui
                                </button>
                                <button
                                    type="button"
                                    @click="openDecision({
                                        code: @js($r->code),
                                        datasetTitle: @js($r->dataset?->title ?? 'Dataset Private'),
                                        requester: @js($r->nama),
                                        mode: 'ditolak',
                                        action: @js(route('panel.permohonan.decide', $r->code)),
                                        note: ''
                                    })"
                                    class="inline-flex min-h-[40px] items-center gap-1.5 rounded-xl border border-red-200 bg-white px-4 py-2 text-xs font-bold text-[#B91C1C] transition hover:bg-red-50"
                                >
                                    <i data-lucide="x" class="h-3.5 w-3.5" aria-hidden="true"></i>
                                    Tolak
                                </button>
                            </div>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-5">
            {{ $requests->links('components.pagination') }}
        </div>
    @endif

    <!-- Decision Modal -->
    <template x-if="modalOpen">
        <div class="fixed inset-0 z-50 grid place-items-end bg-slate-950/55 backdrop-blur-xs sm:place-items-center sm:p-6" @click.self="modalOpen = false" @keydown.escape.window="modalOpen = false">
            <div role="dialog" aria-modal="true" class="flex max-h-[92vh] w-full max-w-lg flex-col overflow-hidden rounded-t-2xl border border-slate-200 bg-white shadow-2xl sm:rounded-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 bg-[#F8FAFC] px-5 pt-5 pb-4 sm:px-8 sm:pt-6">
                    <div class="min-w-0">
                        <p class="text-xs font-bold tabular-nums text-[#0069A8]" x-text="target.code"></p>
                        <h2 class="mt-1 text-xl font-extrabold tracking-tight text-slate-900" x-text="target.mode === 'disetujui' ? 'Setujui permohonan akses?' : 'Tolak permohonan akses?'"></h2>
                        <p class="mt-1 text-xs text-[#475569]" x-text="target.requester + ' · ' + target.datasetTitle"></p>
                    </div>
                    <button type="button" @click="modalOpen = false" aria-label="Tutup" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 hover:border-[#0069A8] hover:text-[#0069A8]">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>

                <form :action="target.action" method="POST" @submit="submit($event)" novalidate class="flex min-h-0 flex-col">
                    @csrf
                    <input type="hidden" name="status" :value="target.mode">
                    <input type="hidden" name="tab" value="{{ $tab }}">

                    <div class="space-y-4 overflow-y-auto px-5 py-6 sm:px-8">
                        <p class="rounded-xl border border-slate-200 bg-[#F8FAFC] px-4 py-3 text-xs leading-relaxed text-slate-600" x-show="target.mode === 'disetujui'">
                            Setelah disetujui, pemohon dapat melihat pratinjau serta mengunduh CSV dan XLSX dataset ini melalui halaman katalog Dataset.
                        </p>

                        <label class="block">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">
                                <span x-text="target.mode === 'disetujui' ? 'Catatan untuk pemohon (opsional)' : 'Alasan penolakan'"></span>
                                <span x-show="target.mode === 'ditolak'" class="text-[#B91C1C]">*</span>
                            </span>
                            <textarea
                                rows="3"
                                name="note"
                                x-model="note"
                                :placeholder="target.mode === 'disetujui' ? 'Contoh: Gunakan hanya untuk penelitian skripsi.' : 'Jelaskan alasan penolakan permohonan ini...'"
                                class="w-full rounded-xl border bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-[#0069A8] focus:ring-2 focus:ring-[#BAE6FD]"
                                :class="noteErr ? 'border-[#B91C1C]' : 'border-slate-200'"
                            ></textarea>
                            <span x-show="noteErr" class="mt-1 block text-xs text-[#B91C1C]" x-text="noteErr"></span>
                        </label>
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:px-8">
                        <button type="button" @click="modalOpen = false" class="inline-flex min-h-[40px] items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Batal
                        </button>
                        <button
                            type="submit"
                            class="inline-flex min-h-[40px] items-center justify-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold text-white transition"
                            :class="target.mode === 'disetujui' ? 'bg-[#047857] hover:bg-[#065F46]' : 'bg-[#B91C1C] hover:bg-[#991B1B]'"
                            x-text="target.mode === 'disetujui' ? 'Setujui akses' : 'Tolak permohonan'"
                        ></button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>
@endsection

@push('scripts')
<script>
function permohonanManager() {
    @php
        $reopenReq = $reopenCode ? $requests->firstWhere('code', $reopenCode) : null;
        $initialReopenData = $reopenReq ? [
            'code' => $reopenReq->code,
            'datasetTitle' => $reopenReq->dataset?->title ?? 'Dataset Private',
            'requester' => $reopenReq->nama,
            'mode' => old('status', 'ditolak'),
            'action' => route('panel.permohonan.decide', $reopenReq->code),
            'note' => old('note', ''),
        ] : null;
        $serverNoteError = $errors->first('note');
    @endphp
    const initialReopen = @json($initialReopenData);
    const serverNoteError = @js($serverNoteError);

    return {
        quickSearch: '',
        modalOpen: Boolean(initialReopen),
        target: initialReopen || { code: '', datasetTitle: '', requester: '', mode: 'disetujui', action: '', note: '' },
        note: initialReopen ? (initialReopen.note || '') : '',
        tried: Boolean(initialReopen && serverNoteError),
        serverErr: serverNoteError || '',

        matchesSearch(haystack) {
            const q = this.quickSearch.trim().toLowerCase();
            return !q || String(haystack || '').includes(q);
        },

        openDecision(data) {
            this.target = data;
            this.note = data.note || '';
            this.tried = false;
            this.serverErr = '';
            this.modalOpen = true;
        },

        get noteErr() {
            if (this.serverErr) return this.serverErr;
            if (this.tried && this.target.mode === 'ditolak' && !String(this.note || '').trim()) {
                return 'Alasan penolakan wajib diisi.';
            }
            return '';
        },

        submit(ev) {
            this.tried = true;
            this.serverErr = '';
            if (this.target.mode === 'ditolak' && !String(this.note || '').trim()) {
                ev.preventDefault();
            }
        },
    };
}
</script>
@endpush
