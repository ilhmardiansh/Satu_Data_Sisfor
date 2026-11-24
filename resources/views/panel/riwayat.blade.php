@extends('layouts.panel')

@section('title', 'Riwayat Formulir - Panel Satu Data UNUSA')

@section('panel_content')
<div x-data="{ detail: null }" @keydown.escape.window="detail = null">
    <x-panel-head
        title="Riwayat Pengisian Formulir"
        :desc="$own ? 'Formulir yang telah Anda kirim beserta status verifikasi pre-processing oleh Wali Data.' : 'Catatan seluruh respons formulir dari Produsen Data beserta status validasi pre-processing.'"
    />

    <form method="GET" action="{{ route('panel.riwayat') }}" class="mb-4 flex flex-col gap-3 sm:flex-row">
        <label class="flex flex-1 items-center gap-3 rounded-xl border border-slate-200 bg-white px-4">
            <i data-lucide="search" class="h-4 w-4 text-slate-400" aria-hidden="true"></i>
            <input
                type="text"
                name="q"
                value="{{ $q }}"
                placeholder="{{ $own ? 'Cari formulir...' : 'Cari nama, NIM, atau formulir...' }}"
                class="h-10 flex-1 bg-transparent text-sm outline-none"
            />
        </label>
        <select
            name="form"
            onchange="this.form.submit()"
            class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none focus:border-[#0069A8] focus:ring-4 focus:ring-sky-100"
        >
            <option value="all">Semua formulir</option>
            @foreach($formsUsed as $fu)
                <option value="{{ $fu->code }}" {{ $formFilter === $fu->code ? 'selected' : '' }}>
                    {{ $fu->title }}
                </option>
            @endforeach
        </select>
        <button type="submit" class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">
            Cari
        </button>
    </form>

    @if($submissions->total() > 0)
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-surface">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[680px]">
                    <thead class="bg-surface-strong">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-[#475569] uppercase">Waktu kirim</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-[#475569] uppercase">Formulir</th>
                            @if(! $own)
                                <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-[#475569] uppercase">Pengirim</th>
                            @endif
                            <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-[#475569] uppercase">Lampiran</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-[#475569] uppercase">Status Pre-processing</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($submissions as $x)
                            @php
                                $f = $x->dataForm;
                                $u = $x->user;
                                $filesCount = 0;
                                $detailFields = [];
                                $fieldsList = $f?->fields ?? [];

                                foreach ($fieldsList as $fl) {
                                    $fid = $fl['id'];
                                    $val = (string) ($x->values[$fid] ?? '');
                                    $isFile = ($fl['type'] ?? 'text') === 'file';
                                    if ($isFile && $val !== '') {
                                        $filesCount++;
                                    }
                                    $hasDownload = $isFile && ! empty(($x->attachments ?? [])[$fid]);
                                    $detailFields[] = [
                                        'label' => $fl['label'],
                                        'type' => $fl['type'] ?? 'text',
                                        'value' => $val,
                                        'downloadUrl' => $hasDownload ? route('panel.submissions.file', [$x->code, $fid]) : null,
                                    ];
                                }

                                $detailPayload = [
                                    'code' => $x->code,
                                    'formTitle' => $f?->title ?? $x->code,
                                    'sender' => ($u?->name ?? '-') . ' (' . ($u?->nim ?? '-') . ')',
                                    'submittedAt' => $x->submittedFormatted(),
                                    'statusLabel' => $x->validationLabel(),
                                    'note' => $x->catatan_validasi ?? '',
                                    'fields' => $detailFields,
                                ];
                            @endphp
                            <tr class="border-t border-slate-200/70 hover:bg-white">
                                <td class="px-4 py-3 text-sm whitespace-nowrap text-slate-600">{{ $x->submittedFormatted() }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">
                                    <p class="font-semibold text-slate-900">{{ $f?->title ?? '(formulir dihapus)' }}</p>
                                    <p class="text-xs text-[#475569]">{{ $x->code }} · {{ $x->sumber_input === 'impor' ? 'Impor GForm' : 'Isian GForm' }}</p>
                                </td>
                                @if(! $own)
                                    <td class="px-4 py-3 text-sm text-slate-600">
                                        {{ $u?->name ?? '-' }}
                                        <p class="text-xs text-[#475569]">{{ $u?->nim ?? '-' }} · {{ $u?->jenis ?? '-' }}</p>
                                    </td>
                                @endif
                                <td class="px-4 py-3 text-sm text-slate-600">
                                    @if($filesCount > 0)
                                        <span class="inline-flex items-center gap-1 text-[#0069A8]">
                                            <i data-lucide="paperclip" class="h-3.5 w-3.5" aria-hidden="true"></i>
                                            {{ $filesCount }} berkas
                                        </span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">
                                    @if($x->status_validasi === 'valid')
                                        <span class="inline-flex items-center gap-1 rounded-md bg-[#ECFDF5] px-2 py-0.5 text-[11px] font-bold text-[#047857]">
                                            <i data-lucide="check-circle-2" class="h-3 w-3" aria-hidden="true"></i>
                                            Valid · Siap Dataset
                                        </span>
                                    @elseif($x->status_validasi === 'ditolak')
                                        <span class="inline-flex items-center gap-1 rounded-md bg-[#FEF2F2] px-2 py-0.5 text-[11px] font-bold text-[#B91C1C]">
                                            <i data-lucide="x-circle" class="h-3 w-3" aria-hidden="true"></i>
                                            Ditolak
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-md bg-[#FFFBEB] px-2 py-0.5 text-[11px] font-bold text-[#92400E]">
                                            <i data-lucide="clock" class="h-3 w-3" aria-hidden="true"></i>
                                            Menunggu Pre-processing
                                        </span>
                                    @endif
                                    @if($x->catatan_validasi)
                                        <p class="mt-1 max-w-xs truncate text-xs text-[#475569]" title="{{ $x->catatan_validasi }}">{{ $x->catatan_validasi }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right text-sm text-slate-600">
                                    <button
                                        type="button"
                                        @click="detail = @js($detailPayload)"
                                        class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-[#0069A8] hover:text-[#0069A8]"
                                        aria-label="Lihat detail"
                                    >
                                        <i data-lucide="eye" class="h-4 w-4"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $submissions->links('components.pagination') }}
        </div>
    @else
        <div class="rounded-2xl border border-dashed border-slate-300 bg-surface px-6 py-14 text-center">
            <span class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-slate-100 text-slate-400">
                <i data-lucide="history" class="h-6 w-6" aria-hidden="true"></i>
            </span>
            <p class="mt-4 font-bold text-slate-800">Belum ada riwayat formulir</p>
        </div>
    @endif

    <!-- Submission Detail Modal -->
    <template x-if="detail">
        <div class="fixed inset-0 z-50 grid place-items-end bg-slate-900/50 sm:place-items-center sm:p-6" @click.self="detail = null" @keydown.escape.window="detail = null">
            <div role="dialog" aria-modal="true" aria-labelledby="riwayat-detail-title" class="flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-t-2xl border border-slate-200 bg-white shadow-2xl sm:rounded-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 pt-5 pb-4 sm:px-8 sm:pt-6">
                    <div class="min-w-0">
                        <p class="text-sm text-[#475569]">Riwayat · <span x-text="detail.code"></span> · <span class="font-semibold" x-text="detail.statusLabel"></span></p>
                        <h2 id="riwayat-detail-title" class="mt-1 text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl" x-text="detail.formTitle"></h2>
                        <p class="mt-1 text-sm text-[#475569]" x-text="`Dikirim oleh ${detail.sender} pada ${detail.submittedAt}`"></p>
                    </div>
                    <button type="button" @click="detail = null" aria-label="Tutup" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 hover:border-[#0069A8] hover:text-[#0069A8]">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>

                <div class="overflow-y-auto px-5 py-4 sm:px-8">
                    <template x-if="detail.note">
                        <div class="mb-3 rounded-xl border border-amber-200 bg-[#FFFBEB] px-4 py-3 text-xs font-medium text-[#92400E]" x-text="'Catatan Pre-processing: ' + detail.note"></div>
                    </template>

                    <dl class="divide-y divide-slate-100">
                        <template x-for="(fl, idx) in detail.fields" :key="idx">
                            <div class="grid grid-cols-1 gap-1 py-3 sm:grid-cols-[200px_1fr]">
                                <dt class="text-sm text-[#475569]" x-text="fl.label"></dt>
                                <dd class="text-sm font-medium text-slate-800">
                                    <template x-if="fl.type === 'file' && fl.value">
                                        <div class="flex items-center gap-3">
                                            <span class="inline-flex items-center gap-1.5 text-[#0069A8]">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                                                <span x-text="fl.value"></span>
                                            </span>
                                            <template x-if="fl.downloadUrl">
                                                <a :href="fl.downloadUrl" class="rounded-lg border border-[#BAE6FD] bg-[#F0F9FF] px-2.5 py-1 text-xs font-bold text-[#0069A8] hover:bg-sky-100">
                                                    Unduh berkas
                                                </a>
                                            </template>
                                        </div>
                                    </template>
                                    <template x-if="fl.type !== 'file' || !fl.value">
                                        <span x-text="fl.value || '-'"></span>
                                    </template>
                                </dd>
                            </div>
                        </template>
                    </dl>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:px-8">
                    <button type="button" @click="detail = null" class="inline-flex min-h-[40px] items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection
