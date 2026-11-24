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
        title="Permohonan Data"
        desc="Tinjau pengajuan akses dataset berstatus Private. Permohonan yang disetujui membuka unduhan untuk pemohon terkait."
    />

    <div class="mb-6 flex gap-1 overflow-x-auto border-b border-slate-200">
        @foreach($tabs as $t)
            @php $active = $tab === $t['k']; @endphp
            <a href="{{ route('panel.permohonan', ['tab' => $t['k']]) }}"
               class="-mb-px inline-flex shrink-0 items-center gap-2 border-b-2 px-4 py-2.5 text-sm font-semibold whitespace-nowrap transition {{ $active ? 'border-sky-600 text-sky-700' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
                <span>{{ $t['label'] }}</span>
                <span class="rounded-full px-1.5 text-[11px] font-bold {{ $active ? 'bg-sky-100 text-sky-700' : 'bg-slate-100 text-slate-600' }}">
                    {{ $t['count'] }}
                </span>
            </a>
        @endforeach
    </div>

    @if($requests->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-300 bg-surface px-6 py-12 text-center">
            <span class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-[#F0F9FF] text-[#0069A8]">
                <i data-lucide="inbox" class="h-6 w-6"></i>
            </span>
            <p class="mt-4 text-base font-bold text-slate-900">Belum ada permohonan data</p>
            <p class="mx-auto mt-1 max-w-md text-sm text-slate-500">
                Permohonan akses dataset berstatus Private dari pengguna publik akan muncul di sini.
            </p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($requests as $r)
                <article class="rounded-2xl border border-slate-200 bg-surface p-5">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-md bg-[#F0F9FF] px-2 py-0.5 font-mono text-[11px] font-bold text-[#0069A8]">
                                    {{ $r->code }}
                                </span>
                                <x-req-status :status="$r->status" />
                                @if($r->dataset)
                                    <x-access-badge :access="$r->dataset->access" />
                                @endif
                                <span class="text-xs text-slate-500">
                                    {{ $r->requested_at?->format('d M Y, H:i') }}
                                </span>
                            </div>

                            <h2 class="mt-2.5 text-base font-bold text-slate-900">
                                {{ $r->dataset?->title ?? 'Dataset tidak tersedia' }}
                            </h2>

                            <div class="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-xs text-slate-600">
                                <span><strong class="font-semibold text-slate-800">Pemohon:</strong> {{ $r->nama }}</span>
                                <span><strong class="font-semibold text-slate-800">NIM/NIP:</strong> {{ $r->nim }}</span>
                                <span><strong class="font-semibold text-slate-800">Email:</strong> {{ $r->email }}</span>
                                <span><strong class="font-semibold text-slate-800">HP:</strong> {{ $r->hp }}</span>
                            </div>

                            <div class="mt-3 rounded-xl border border-slate-200/80 bg-white px-4 py-3 text-sm">
                                <p class="text-slate-700">
                                    <span class="font-semibold text-slate-900">Tujuan:</span> {{ $r->tujuan }}
                                </p>
                                @if($r->deskripsi)
                                    <p class="mt-1 text-slate-600">{{ $r->deskripsi }}</p>
                                @endif
                            </div>

                            @if($r->status !== 'menunggu')
                                <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
                                    @if($r->decider)
                                        <span>Diputuskan oleh <strong class="text-slate-700">{{ $r->decider->name }}</strong></span>
                                    @endif
                                    @if($r->decided_at)
                                        <span>· {{ $r->decided_at->format('d M Y, H:i') }}</span>
                                    @endif
                                    @if($r->note)
                                        <span class="w-full text-slate-600">Catatan: “{{ $r->note }}”</span>
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
                                    class="inline-flex min-h-[38px] items-center gap-1.5 rounded-xl bg-[#047857] px-3.5 py-2 text-xs font-semibold text-white transition hover:bg-[#065F46]"
                                >
                                    <i data-lucide="check" class="h-3.5 w-3.5"></i>
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
                                    class="inline-flex min-h-[38px] items-center gap-1.5 rounded-xl border border-red-200 bg-white px-3.5 py-2 text-xs font-semibold text-[#B91C1C] transition hover:bg-red-50"
                                >
                                    <i data-lucide="x" class="h-3.5 w-3.5"></i>
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
        <div class="fixed inset-0 z-50 grid place-items-end bg-slate-900/50 sm:place-items-center sm:p-6" @click.self="modalOpen = false" @keydown.escape.window="modalOpen = false">
            <div role="dialog" aria-modal="true" class="flex max-h-[92vh] w-full max-w-lg flex-col overflow-hidden rounded-t-2xl border border-slate-200 bg-white shadow-2xl sm:rounded-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 pt-5 pb-4 sm:px-8 sm:pt-6">
                    <div class="min-w-0">
                        <p class="font-mono text-xs font-bold text-[#0069A8]" x-text="target.code"></p>
                        <h2 class="mt-1 text-xl font-extrabold tracking-tight text-slate-900" x-text="target.mode === 'disetujui' ? 'Setujui permohonan akses?' : 'Tolak permohonan akses?'"></h2>
                        <p class="mt-1 text-xs text-slate-500" x-text="target.requester + ' · ' + target.datasetTitle"></p>
                    </div>
                    <button type="button" @click="modalOpen = false" aria-label="Tutup" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 hover:border-[#0069A8] hover:text-[#0069A8]">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>

                <form :action="target.action" method="POST" @submit="submit($event)" novalidate class="flex min-h-0 flex-col">
                    @csrf
                    <input type="hidden" name="status" :value="target.mode">
                    <input type="hidden" name="tab" value="{{ $tab }}">

                    <div class="space-y-4 overflow-y-auto px-5 py-6 sm:px-8">
                        <p class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs leading-relaxed text-slate-600" x-show="target.mode === 'disetujui'">
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
        modalOpen: Boolean(initialReopen),
        target: initialReopen || { code: '', datasetTitle: '', requester: '', mode: 'disetujui', action: '', note: '' },
        note: initialReopen ? (initialReopen.note || '') : '',
        tried: Boolean(initialReopen && serverNoteError),
        serverErr: serverNoteError || '',

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
