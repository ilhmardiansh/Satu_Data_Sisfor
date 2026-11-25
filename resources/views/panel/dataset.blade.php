@extends('layouts.panel')

@section('title', 'Kelola Dataset - Panel Satu Data UNUSA')

@section('panel_content')
<div>
    <x-panel-head
        scene="dataset"
        badge="{{ $datasets->total() }} Dataset"
        badgeTone="emerald"
        title="Kelola Dataset"
        desc="Susun, publikasikan, dan atur akses dataset resmi Program Studi."
    >
        <a href="{{ route('panel.dataset.create') }}"
           class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl bg-[#00A6F4] px-4 py-2.5 text-sm font-bold text-[#09122C] transition hover:bg-[#38BDF8]">
            <i data-lucide="plus" class="h-4 w-4" aria-hidden="true"></i>
            Buat dataset
        </a>
    </x-panel-head>

    <div class="mb-5 grid grid-cols-2 gap-3.5 lg:grid-cols-4">
        <div class="wd-bento p-4">
            <div class="flex items-center justify-between gap-2">
                <span class="text-xs font-semibold text-[#475569]">Total Dataset</span>
                <span class="grid h-7 w-7 place-items-center rounded-lg bg-[#F0F9FF] text-[#0069A8]">
                    <i data-lucide="database" class="h-3.5 w-3.5" aria-hidden="true"></i>
                </span>
            </div>
            <p class="mt-2 text-2xl font-extrabold tracking-tight tabular-nums text-slate-900">{{ $datasetStats['total'] ?? $datasets->total() }}</p>
        </div>

        <div class="wd-bento p-4">
            <div class="flex items-center justify-between gap-2">
                <span class="text-xs font-semibold text-[#475569]">Terpublikasi</span>
                <span class="grid h-7 w-7 place-items-center rounded-lg bg-[#ECFDF5] text-[#047857]">
                    <i data-lucide="globe" class="h-3.5 w-3.5" aria-hidden="true"></i>
                </span>
            </div>
            <p class="mt-2 text-2xl font-extrabold tracking-tight tabular-nums text-slate-900">{{ $datasetStats['published'] ?? 0 }}</p>
        </div>

        <div class="wd-bento p-4">
            <div class="flex items-center justify-between gap-2">
                <span class="text-xs font-semibold text-[#475569]">Akses Publik</span>
                <span class="grid h-7 w-7 place-items-center rounded-lg bg-[#EFF6FF] text-[#182654]">
                    <i data-lucide="unlock" class="h-3.5 w-3.5" aria-hidden="true"></i>
                </span>
            </div>
            <p class="mt-2 text-2xl font-extrabold tracking-tight tabular-nums text-slate-900">{{ $datasetStats['public'] ?? 0 }}</p>
        </div>

        @if($readyFormsCount > 0)
            <a href="{{ route('panel.dataset.create') }}" class="wd-bento-interactive p-4">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-semibold text-[#475569]">Formulir Siap Susun</span>
                    <span class="grid h-7 w-7 place-items-center rounded-lg bg-[#ECFDF5] text-[#047857]">
                        <i data-lucide="sparkles" class="h-3.5 w-3.5" aria-hidden="true"></i>
                    </span>
                </div>
                <p class="mt-2 text-2xl font-extrabold tracking-tight tabular-nums text-[#0069A8]">{{ $readyFormsCount }}</p>
            </a>
        @else
            <div class="wd-bento p-4">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-semibold text-[#475569]">Akses Private</span>
                    <span class="grid h-7 w-7 place-items-center rounded-lg bg-[#FFFBEB] text-[#92400E]">
                        <i data-lucide="lock" class="h-3.5 w-3.5" aria-hidden="true"></i>
                    </span>
                </div>
                <p class="mt-2 text-2xl font-extrabold tracking-tight tabular-nums text-slate-900">{{ $datasetStats['private'] ?? 0 }}</p>
            </div>
        @endif
    </div>

    <form method="GET" action="{{ route('panel.dataset') }}" class="wd-bento mb-5 flex flex-wrap items-center gap-2 p-3">
        <label class="flex h-10 min-w-0 flex-1 items-center gap-2 rounded-xl border border-slate-200 bg-[#F8FAFC] px-3 focus-within:border-[#0069A8] focus-within:bg-white">
            <i data-lucide="search" class="h-4 w-4 text-[#475569]" aria-hidden="true"></i>
            <input type="text" name="q" value="{{ $q }}" placeholder="Cari dataset..." class="min-w-0 flex-1 bg-transparent text-sm outline-none" />
        </label>
        <select name="kategori" onchange="this.form.submit()" class="h-10 rounded-xl border border-slate-200 bg-[#F8FAFC] px-3 text-sm text-slate-800 outline-none focus:border-[#0069A8] focus:bg-white">
            <option value="all">Semua kategori</option>
            @foreach($categories as $c)
                <option value="{{ $c->code }}" {{ $kategori === $c->code ? 'selected' : '' }}>{{ $c->name }}</option>
            @endforeach
        </select>
        <select name="tahun" onchange="this.form.submit()" class="h-10 rounded-xl border border-slate-200 bg-[#F8FAFC] px-3 text-sm text-slate-800 outline-none focus:border-[#0069A8] focus:bg-white">
            <option value="all">Semua tahun</option>
            @foreach($years as $y)
                <option value="{{ $y }}" {{ $tahun === (string) $y ? 'selected' : '' }}>{{ $y }}</option>
            @endforeach
        </select>
        <button type="submit" class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:border-[#0069A8] hover:text-[#0069A8]">
            Cari
        </button>
    </form>

    <div class="wd-bento overflow-hidden">
        <div class="overflow-x-auto">
            <table class="wd-hairline-table min-w-[860px]">
                <thead>
                    <tr>
                        <th class="px-4 py-3 text-left text-[11px] font-bold tracking-wider whitespace-nowrap text-[#475569] uppercase">Dataset &amp; Sumber</th>
                        <th class="px-4 py-3 text-left text-[11px] font-bold tracking-wider whitespace-nowrap text-[#475569] uppercase">Periode &amp; Baris</th>
                        <th class="px-4 py-3 text-left text-[11px] font-bold tracking-wider whitespace-nowrap text-[#475569] uppercase">Publikasi</th>
                        <th class="px-4 py-3 text-left text-[11px] font-bold tracking-wider whitespace-nowrap text-[#475569] uppercase">Akses</th>
                        <th class="px-4 py-3 text-right text-[11px] font-bold tracking-wider whitespace-nowrap text-[#475569] uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($datasets as $d)
                        <tr class="transition-colors hover:bg-[#F8FAFC]">
                            <td class="px-4 py-3.5 text-sm text-slate-600">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded bg-[#F0F9FF] px-1.5 py-0.5 text-[11px] font-bold tabular-nums text-[#0069A8]">{{ $d->code }}</span>
                                    @if($d->sourceForm)
                                        <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[11px] font-semibold text-slate-700">Dari {{ $d->sourceForm->code }}</span>
                                    @endif
                                    @if(! empty($d->konfigurasi_sumber['mode']))
                                        <span class="rounded px-1.5 py-0.5 text-[11px] font-bold {{ $d->konfigurasi_sumber['mode'] === 'rekap' ? 'bg-[#EFF6FF] text-[#182654]' : 'bg-[#ECFDF5] text-[#047857]' }}">
                                            {{ $d->konfigurasi_sumber['mode'] === 'rekap' ? 'Versi Ringkas' : 'Versi Detail' }}
                                        </span>
                                    @endif
                                </div>
                                <a href="{{ route('panel.dataset.show', $d->code) }}" class="mt-1 block font-bold text-slate-900 transition hover:text-[#0069A8]">
                                    {{ $d->title }}
                                </a>
                                <p class="text-xs text-[#475569]">
                                    {{ $d->category?->name }} · {{ $d->format }} · {{ $d->updated_label ?? ($d->updated_at?->locale('id')->translatedFormat('j M Y') ?? '-') }}
                                </p>
                            </td>
                            <td class="px-4 py-3.5 text-sm whitespace-nowrap text-slate-700">
                                <p class="font-semibold">{{ $d->tahun }} · {{ $d->periode }}</p>
                                <p class="text-xs tabular-nums text-[#475569]">{{ count($d->rows ?? []) }} baris · {{ count($d->cols ?? []) }} kolom</p>
                            </td>
                            <td class="px-4 py-3.5 text-sm text-slate-600">
                                <div class="flex items-center gap-2" x-data="{ published: @js($d->isPublished()), busy: false }">
                                    <button
                                        type="button"
                                        role="switch"
                                        :aria-checked="published"
                                        :aria-busy="busy"
                                        aria-label="Terpublikasi"
                                        :disabled="busy"
                                        @click="
                                            busy = true;
                                            fetch(@js(route('panel.dataset.publish', $d->code)), {
                                                method: 'PATCH',
                                                headers: {
                                                    'Content-Type': 'application/json',
                                                    Accept: 'application/json',
                                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                                                },
                                                body: JSON.stringify({ status_publikasi: published ? 'draft' : 'terpublikasi' })
                                            }).then(r => r.json()).then(res => {
                                                published = res.status_publikasi === 'terpublikasi';
                                                $dispatch('toast', { message: res.message, tone: 'info' });
                                            }).finally(() => busy = false);
                                        "
                                        :class="published ? 'bg-[#0069A8]' : 'bg-slate-300'"
                                        class="relative h-6 w-11 shrink-0 rounded-full transition disabled:opacity-60"
                                    >
                                        <span :class="published ? 'left-[22px]' : 'left-0.5'" class="absolute top-0.5 h-5 w-5 rounded-full bg-white shadow transition"></span>
                                    </button>
                                    <span x-show="published" class="inline-flex rounded-md bg-[#F0F9FF] px-2 py-0.5 text-[11px] font-bold text-[#0069A8]">Terbit</span>
                                    <span x-show="!published" x-cloak class="inline-flex rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-700">Draft</span>
                                </div>
                            </td>
                            <td class="px-4 py-3.5 text-sm text-slate-600">
                                <div class="flex items-center gap-2" x-data="{ pub: @js($d->access === 'public'), busy: false }">
                                    <button
                                        type="button"
                                        role="switch"
                                        :aria-checked="pub"
                                        :aria-busy="busy"
                                        aria-label="Publik"
                                        :disabled="busy"
                                        @click="
                                            busy = true;
                                            fetch(@js(route('panel.dataset.access', $d->code)), {
                                                method: 'PATCH',
                                                headers: {
                                                    'Content-Type': 'application/json',
                                                    Accept: 'application/json',
                                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                                                },
                                                body: JSON.stringify({ access: pub ? 'private' : 'public' })
                                            }).then(r => r.json()).then(res => {
                                                pub = res.access === 'public';
                                                $dispatch('toast', { message: res.message, tone: 'info' });
                                            }).finally(() => busy = false);
                                        "
                                        :class="pub ? 'bg-[#047857]' : 'bg-slate-300'"
                                        class="relative h-6 w-11 shrink-0 rounded-full transition disabled:opacity-60"
                                    >
                                        <span :class="pub ? 'left-[22px]' : 'left-0.5'" class="absolute top-0.5 h-5 w-5 rounded-full bg-white shadow transition"></span>
                                    </button>
                                    <span x-show="pub" class="inline-flex items-center gap-1 rounded-md bg-[#ecfdf5] px-2 py-0.5 text-[11px] font-bold text-[#047857]">Publik</span>
                                    <span x-show="!pub" x-cloak class="inline-flex items-center gap-1 rounded-md bg-[#fffbeb] px-2 py-0.5 text-[11px] font-bold text-[#92400e]">Private</span>
                                </div>
                            </td>
                            <td class="px-4 py-3.5 text-sm text-slate-600">
                                <div class="flex justify-end gap-1.5">
                                    @if($d->id_formulir_sumber)
                                        <form method="POST" action="{{ route('panel.dataset.sync', $d->code) }}" class="inline">
                                            @csrf
                                            <button type="submit"
                                                    class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 transition hover:border-[#0069A8] hover:text-[#0069A8]"
                                                    title="Sinkronkan ulang"
                                                    aria-label="Sinkronkan ulang">
                                                <i data-lucide="refresh-cw" class="h-4 w-4" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    @endif
                                    <a href="{{ route('panel.dataset.show', $d->code) }}"
                                       class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 transition hover:border-[#0069A8] hover:text-[#0069A8]"
                                       title="Lihat detail &amp; data"
                                       aria-label="Lihat">
                                        <i data-lucide="eye" class="h-4 w-4" aria-hidden="true"></i>
                                    </a>
                                    <a href="{{ route('panel.dataset.edit', $d->code) }}"
                                       class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 transition hover:border-[#0069A8] hover:text-[#0069A8]"
                                       title="Kelola &amp; ubah dataset"
                                       aria-label="Ubah">
                                        <i data-lucide="pencil" class="h-4 w-4" aria-hidden="true"></i>
                                    </a>
                                    <button type="button"
                                            @click="$dispatch('open-confirm', {
                                                title: 'Hapus dataset ini?',
                                                message: @js("Dataset “{$d->title}” ({$d->code}) beserta visualisasi terkait akan dihapus."),
                                                action: @js(route('panel.dataset.destroy', $d->code)),
                                                method: 'DELETE',
                                                buttonText: 'Ya, Hapus Dataset'
                                            })"
                                            class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-red-200 hover:bg-red-50 hover:text-[#B91C1C]"
                                            title="Hapus dataset"
                                            aria-label="Hapus dataset {{ $d->code }}">
                                        <i data-lucide="trash-2" class="h-4 w-4" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <span class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-[#F0F9FF] text-[#0069A8]">
                                    <i data-lucide="table-2" class="h-6 w-6" aria-hidden="true"></i>
                                </span>
                                <p class="mt-3 text-sm font-bold text-slate-800">Belum ada dataset tersedia</p>
                                <p class="mt-1 text-xs text-[#475569]">
                                    {{ $readyFormsCount > 0 ? 'Terdapat '.$readyFormsCount.' formulir siap dikemas menjadi dataset.' : 'Tutup formulir, selesaikan Pre-processing Data, lalu buat dataset baru.' }}
                                </p>
                                @if($readyFormsCount > 0)
                                    <a href="{{ route('panel.dataset.create') }}"
                                       class="mt-4 inline-flex min-h-[40px] items-center rounded-xl bg-[#0069A8] px-4 py-2 text-xs font-semibold text-white hover:bg-[#005187]">
                                        Buat dataset
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $datasets->links('components.pagination') }}
    </div>
</div>
@endsection
