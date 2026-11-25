@extends('layouts.panel')

@section('title', 'Visualisasi Dashboard - Panel Satu Data UNUSA')

@section('panel_content')
@php
    $user = auth()->user();
    $shownCount = collect($vizPayload)->where('show', true)->count();
@endphp

<div x-data="vizManager()">
    <x-panel-head
        scene="visualisasi"
        badge="{{ $shownCount }}/{{ count($vizPayload) }} Tampil"
        badgeTone="sky"
        title="Visualisasi Dashboard"
        desc="Pilih bentuk diagram sendiri, lihat data sumber, dan tayangkan grafik ke Dashboard."
    >
        <button type="button"
                @click="openNew()"
                {{ empty($datasetsPayload) ? 'disabled' : '' }}
                class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl bg-[#00A6F4] px-4 py-2.5 text-sm font-bold text-[#09122C] transition hover:bg-[#38BDF8] disabled:cursor-not-allowed disabled:opacity-45">
            <i data-lucide="plus" class="h-4 w-4" aria-hidden="true"></i>
            Buat visualisasi
        </button>
    </x-panel-head>

    @if(! empty($vizPayload))
        <!-- Dynamic Search & Visibility Filter Bar -->
        <div class="wd-bento mb-5 flex flex-wrap items-center justify-between gap-3 p-3">
            <div class="flex flex-wrap gap-1 rounded-xl border border-slate-200/90 bg-[#F8FAFC] p-1 text-xs font-bold">
                <button type="button"
                        @click="vizFilter = 'all'"
                        :class="vizFilter === 'all' ? 'bg-[#0069A8] text-white' : 'text-slate-600 hover:text-slate-900'"
                        class="min-h-[36px] rounded-lg px-3.5 py-1.5 transition">
                    Semua ({{ count($vizPayload) }})
                </button>
                <button type="button"
                        @click="vizFilter = 'shown'"
                        :class="vizFilter === 'shown' ? 'bg-[#0069A8] text-white' : 'text-slate-600 hover:text-slate-900'"
                        class="min-h-[36px] rounded-lg px-3.5 py-1.5 transition">
                    Tampil di Dashboard
                </button>
                <button type="button"
                        @click="vizFilter = 'hidden'"
                        :class="vizFilter === 'hidden' ? 'bg-[#0069A8] text-white' : 'text-slate-600 hover:text-slate-900'"
                        class="min-h-[36px] rounded-lg px-3.5 py-1.5 transition">
                    Disembunyikan
                </button>
            </div>

            <label class="flex h-10 min-w-[220px] flex-1 items-center gap-2 rounded-xl border border-slate-200 bg-[#F8FAFC] px-3 focus-within:border-[#0069A8] focus-within:bg-white sm:max-w-xs">
                <i data-lucide="search" class="h-4 w-4 shrink-0 text-[#475569]" aria-hidden="true"></i>
                <input type="text"
                       x-model="vizQuery"
                       placeholder="Cari judul grafik atau dataset..."
                       class="min-w-0 flex-1 bg-transparent text-sm outline-none placeholder:text-slate-400" />
            </label>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            @foreach($vizPayload as $v)
                @php
                    $canDelete = $user->isAdmin() || (int) $v['created_by'] === $user->id;
                    $vizSearchKey = strtolower($v['code'].' '.$v['title'].' '.$v['datasetTitle'].' '.$v['creatorName'].' '.$v['tahun']);
                @endphp
                <div class="wd-bento-interactive flex flex-col p-5"
                     x-data="{
                        shown: @js((bool) $v['show']),
                        busy: false,
                        busyKind: false,
                        cardKind: @js($v['kind']),
                        cardView: 'chart',
                        itemData: @js($v)
                     }"
                     x-show="matchesViz(@js($vizSearchKey), shown)">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="rounded bg-[#F0F9FF] px-1.5 py-0.5 text-[10px] font-bold tabular-nums text-[#0069A8]">{{ $v['code'] }}</span>
                                @if(! empty($v['tahun']))
                                    <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold tabular-nums text-slate-700">{{ $v['tahun'] }}</span>
                                @endif
                                @if(! ($v['datasetPublished'] ?? true))
                                    <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold text-slate-700">Sumber Draft</span>
                                @elseif(($v['datasetAccess'] ?? 'public') === 'private')
                                    <span class="rounded bg-[#FFFBEB] px-1.5 py-0.5 text-[10px] font-bold text-[#92400E]">Sumber Private</span>
                                @endif
                                <span class="rounded bg-[#ECFDF5] px-1.5 py-0.5 text-[10px] font-bold tabular-nums text-[#047857]">{{ count($v['rows']) }} baris data</span>
                            </div>
                            <p class="mt-1.5 font-bold text-slate-900">{{ $v['title'] }}</p>
                            <p class="truncate text-xs text-[#475569]">
                                {{ $v['datasetTitle'] }} · oleh {{ $v['creatorName'] }}
                            </p>
                        </div>
                        <button
                            type="button"
                            role="switch"
                            :aria-checked="shown"
                            :aria-busy="busy"
                            aria-label="Tampilkan di Dashboard"
                            :disabled="busy"
                            @click="
                                busy = true;
                                fetch(@js(route('panel.visualisasi.toggle', $v['code'])), {
                                    method: 'PATCH',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        Accept: 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                                    },
                                    body: JSON.stringify({ show: !shown })
                                }).then(r => r.json()).then(res => {
                                    shown = !!res.show;
                                    $dispatch('toast', { message: res.show ? 'Visualisasi ditampilkan di Dashboard.' : 'Visualisasi disembunyikan.', tone: 'info' });
                                }).finally(() => busy = false);
                            "
                            :class="shown ? 'bg-[#0069A8]' : 'bg-slate-300'"
                            class="relative h-6 w-11 shrink-0 rounded-full transition disabled:opacity-60"
                        >
                            <span :class="shown ? 'left-[22px]' : 'left-0.5'" class="absolute top-0.5 h-5 w-5 rounded-full bg-white shadow transition"></span>
                        </button>
                    </div>

                    <!-- Kontrol Langsung: Pilih Bentuk Diagram & Toggle Grafik / Data -->
                    <div class="mt-3 flex flex-wrap items-center justify-between gap-2 rounded-xl border border-slate-200/80 bg-[#F8FAFC] p-2">
                        <div class="flex flex-wrap items-center gap-1" role="group" aria-label="Pilih bentuk diagram">
                            @foreach($kinds as $k => $label)
                                <button type="button"
                                        :disabled="busyKind"
                                        @click="
                                            if (cardKind === @js($k)) return;
                                            const prevKind = cardKind;
                                            cardKind = @js($k);
                                            cardView = 'chart';
                                            renderCardChart(itemData, cardKind);
                                            busyKind = true;
                                            fetch(@js(route('panel.visualisasi.kind', $v['code'])), {
                                                method: 'PATCH',
                                                headers: {
                                                    'Content-Type': 'application/json',
                                                    Accept: 'application/json',
                                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                                                },
                                                body: JSON.stringify({ kind: @js($k) })
                                            }).then(r => {
                                                if (!r.ok) throw new Error('Gagal');
                                                return r.json();
                                            }).then(res => {
                                                itemData.kind = res.kind;
                                                $dispatch('toast', { message: res.message, tone: 'info' });
                                            }).catch(() => {
                                                cardKind = prevKind;
                                                renderCardChart(itemData, prevKind);
                                            }).finally(() => busyKind = false);
                                        "
                                        :class="cardKind === @js($k) ? 'bg-[#0069A8] text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                                        class="inline-flex h-7 items-center gap-1 rounded-lg px-2 text-[11px] font-semibold transition">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>

                        <div class="inline-flex rounded-lg border border-slate-200 bg-white p-0.5 text-[11px] font-bold">
                            <button type="button"
                                    @click="cardView = 'chart'; $nextTick(() => renderCardChart(itemData, cardKind))"
                                    :class="cardView === 'chart' ? 'bg-[#182654] text-white' : 'text-slate-600 hover:text-slate-900'"
                                    class="rounded-md px-2.5 py-1 transition">
                                Grafik
                            </button>
                            <button type="button"
                                    @click="cardView = 'data'"
                                    :class="cardView === 'data' ? 'bg-[#182654] text-white' : 'text-slate-600 hover:text-slate-900'"
                                    class="rounded-md px-2.5 py-1 transition">
                                Data ({{ count($v['rows']) }})
                            </button>
                        </div>
                    </div>

                    <!-- Slot Grafik -->
                    <div x-show="cardView === 'chart'" class="mt-3 space-y-3">
                        <div class="relative h-48 rounded-xl border border-slate-100 bg-[#F8FAFC] p-2">
                            <div class="h-full w-full" id="panelVizChart_{{ $v['code'] }}"></div>
                            <div x-show="cardKind === 'donut'"
                                 class="pointer-events-none absolute inset-0 grid place-items-center text-center">
                                <div class="max-w-[110px] px-1">
                                    <p class="text-lg font-extrabold leading-none tabular-nums text-slate-900"
                                       x-text="seriesTotal(itemData).toLocaleString('id-ID')"></p>
                                    <p class="mt-0.5 truncate text-[10px] font-semibold text-[#475569]"
                                       x-text="itemData.cols?.[itemData.value_col] || ''"></p>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-x-3 gap-y-1.5 text-xs">
                            <template x-for="(pt, pIdx) in seriesPoints(itemData).slice(0, 4)" :key="pIdx">
                                <div class="flex items-center justify-between gap-2 rounded-lg bg-[#F8FAFC] px-2.5 py-1.5">
                                    <span class="flex min-w-0 items-center gap-1.5">
                                        <span class="h-2 w-2 shrink-0 rounded-sm" :style="`background:${itemData.palette[pIdx % itemData.palette.length]}`"></span>
                                        <span class="truncate font-medium text-slate-700" x-text="pt.n"></span>
                                    </span>
                                    <span class="shrink-0 font-bold tabular-nums text-slate-900" x-text="pt.v.toLocaleString('id-ID')"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Slot Tabel Data Langsung -->
                    <div x-show="cardView === 'data'" x-cloak class="mt-3 max-h-64 overflow-auto rounded-xl border border-slate-200 bg-white">
                        <table class="w-full text-xs">
                            <thead class="sticky top-0 bg-[#F8FAFC] text-[11px] font-bold text-[#475569]">
                                <tr>
                                    <th class="px-3 py-2 text-left" x-text="itemData.cols?.[itemData.label_col] || 'Label'"></th>
                                    <th class="px-3 py-2 text-right" x-text="itemData.cols?.[itemData.value_col] || 'Nilai'"></th>
                                    <th class="px-3 py-2 text-right">Porsi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template x-for="(pt, pIdx) in seriesPoints(itemData)" :key="pIdx">
                                    <tr class="hover:bg-[#F0F9FF]/50">
                                        <td class="px-3 py-2 font-medium text-slate-800">
                                            <span class="flex items-center gap-2">
                                                <span class="h-2 w-2 shrink-0 rounded-sm" :style="`background:${itemData.palette[pIdx % itemData.palette.length]}`"></span>
                                                <span class="truncate" x-text="pt.n"></span>
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 text-right font-bold tabular-nums text-slate-900" x-text="pt.v.toLocaleString('id-ID')"></td>
                                        <td class="px-3 py-2 text-right tabular-nums text-[#475569]" x-text="window.SatuDataCharts ? window.SatuDataCharts.fmtPct(pt.v, seriesTotal(itemData)) : '-'"></td>
                                    </tr>
                                </template>
                            </tbody>
                            <tfoot class="border-t border-slate-200 bg-[#F8FAFC] font-bold text-slate-900">
                                <tr>
                                    <td class="px-3 py-2">Total ({{ count($v['rows']) }} baris)</td>
                                    <td class="px-3 py-2 text-right tabular-nums" x-text="seriesTotal(itemData).toLocaleString('id-ID')"></td>
                                    <td class="px-3 py-2 text-right">100%</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="mt-4 flex items-center justify-between gap-2 border-t border-slate-100 pt-3">
                        <span class="text-xs font-semibold" :class="shown ? 'text-[#047857]' : 'text-slate-500'" x-text="shown ? 'Tampil di Dashboard' : 'Disembunyikan'"></span>
                        <div class="flex gap-1.5">
                            <button
                                type="button"
                                @click="openDataModal(itemData)"
                                class="inline-flex h-9 items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-2.5 text-xs font-semibold text-slate-700 transition hover:border-[#0069A8] hover:text-[#0069A8]"
                                title="Lihat seluruh data tabel"
                            >
                                <i data-lucide="table-2" class="h-3.5 w-3.5" aria-hidden="true"></i>
                                Lihat Data
                            </button>
                            <button
                                type="button"
                                @click="openEdit(itemData)"
                                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-[#0069A8] hover:text-[#0069A8]"
                                aria-label="Ubah"
                                title="Ubah konfigurasi lengkap"
                            >
                                <i data-lucide="pencil" class="h-4 w-4" aria-hidden="true"></i>
                            </button>
                            @if($canDelete)
                                <button
                                    type="button"
                                    @click="$dispatch('open-confirm', {
                                        message: @js("Visualisasi “{$v['title']}” akan dihapus dari Dashboard."),
                                        action: @js(route('panel.visualisasi.destroy', $v['code'])),
                                        method: 'DELETE'
                                    })"
                                    class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-red-200 hover:bg-red-50 hover:text-[#B91C1C]"
                                    aria-label="Hapus"
                                >
                                    <i data-lucide="trash-2" class="h-4 w-4" aria-hidden="true"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="wd-bento border-dashed border-slate-300 px-6 py-12 text-center">
            <span class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-[#F0F9FF] text-[#0069A8]">
                <i data-lucide="bar-chart-3" class="h-6 w-6" aria-hidden="true"></i>
            </span>
            @if(empty($datasetsPayload))
                <p class="mt-4 font-bold text-slate-800">Belum ada dataset bernumerik untuk divisualisasikan</p>
                <p class="mt-1 text-sm text-[#475569]">Susun dataset rekap atau tabel bernumerik dari data yang telah lolos Pre-processing, atau muat data contoh untuk langsung melihat visualisasi dan dataset multi-tahun.</p>
                <div class="mt-4 flex flex-wrap items-center justify-center gap-2.5">
                    <form method="POST" action="{{ route('panel.visualisasi.seed-sample') }}">
                        @csrf
                        <button type="submit" class="inline-flex min-h-[42px] items-center gap-2 rounded-xl bg-[#00A6F4] px-4 py-2 text-sm font-bold text-[#09122C] transition hover:bg-[#38BDF8]">
                            <i data-lucide="sparkles" class="h-4 w-4" aria-hidden="true"></i>
                            Muat Data Contoh
                        </button>
                    </form>
                    <a href="{{ route('panel.dataset') }}" class="inline-flex min-h-[42px] items-center rounded-xl bg-[#0069A8] px-4 py-2 text-sm font-semibold text-white hover:bg-[#182654]">
                        Kelola Dataset
                    </a>
                </div>
            @else
                <p class="mt-4 font-bold text-slate-800">Belum ada visualisasi</p>
                <p class="mt-1 text-sm text-[#475569]">Pilih salah satu dataset bernumerik di bawah atau klik Buat visualisasi.</p>
            @endif
        </div>
    @endif

    @if(! empty($datasetsPayload))
        <!-- Daftar Dataset yang Sudah Ada Datanya (Siap Visualisasi) -->
        <section class="mt-8">
            <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="text-base font-extrabold tracking-tight text-slate-900">Dataset yang Sudah Ada Datanya ({{ count($datasetsPayload) }})</h2>
                    <p class="mt-0.5 text-xs text-[#475569]">Lihat tabel data setiap dataset yang memiliki kolom angka atau langsung buat visualisasi baru darinya.</p>
                </div>
                <a href="{{ route('panel.dataset') }}" class="text-xs font-bold text-[#0069A8] hover:underline">Buka Kelola Dataset</a>
            </div>

            <div class="grid grid-cols-1 gap-3.5 md:grid-cols-2 xl:grid-cols-3">
                @foreach($datasetsPayload as $ds)
                    <div class="wd-bento flex flex-col justify-between p-4">
                        <div>
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="rounded bg-[#F0F9FF] px-1.5 py-0.5 text-[10px] font-bold tabular-nums text-[#0069A8]">{{ $ds['code'] }}</span>
                                    <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold tabular-nums text-slate-700">{{ $ds['tahun'] }} · {{ $ds['periode'] }}</span>
                                    @if(! $ds['published'])
                                        <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold text-slate-600">Draft</span>
                                    @elseif($ds['access'] === 'private')
                                        <span class="rounded bg-[#FFFBEB] px-1.5 py-0.5 text-[10px] font-bold text-[#92400E]">Private</span>
                                    @else
                                        <span class="rounded bg-[#ECFDF5] px-1.5 py-0.5 text-[10px] font-bold text-[#047857]">Publik</span>
                                    @endif
                                </div>
                                <span class="text-[11px] font-bold tabular-nums {{ $ds['vizCount'] > 0 ? 'text-[#0069A8]' : 'text-slate-500' }}">
                                    {{ $ds['vizCount'] > 0 ? $ds['vizCount'].' grafik' : 'Belum divisualisasikan' }}
                                </span>
                            </div>

                            <p class="mt-2 font-bold leading-snug text-slate-900">{{ $ds['title'] }}</p>
                            <p class="mt-1 text-xs text-[#475569]">
                                {{ $ds['categoryName'] }} · <span class="font-semibold tabular-nums text-slate-700">{{ $ds['rowCount'] }} baris</span> · {{ count($ds['cols']) }} kolom
                            </p>

                            <div class="mt-2 flex flex-wrap gap-1">
                                @foreach($ds['numericCols'] as $numColName)
                                    <span class="rounded-md border border-slate-200 bg-[#F8FAFC] px-2 py-0.5 text-[10px] font-semibold text-slate-700">{{ $numColName }}</span>
                                @endforeach
                            </div>
                        </div>

                        <div class="mt-4 flex items-center gap-2 border-t border-slate-100 pt-3">
                            <button type="button"
                                    @click="openDatasetDataModal(@js($ds))"
                                    class="inline-flex min-h-[36px] flex-1 items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 transition hover:border-[#0069A8] hover:text-[#0069A8]">
                                <i data-lucide="table-2" class="h-3.5 w-3.5" aria-hidden="true"></i>
                                Lihat Data
                            </button>
                            <button type="button"
                                    @click="openNewForDataset(@js($ds['id']))"
                                    class="inline-flex min-h-[36px] flex-1 items-center justify-center gap-1.5 rounded-xl bg-[#0069A8] px-3 text-xs font-bold text-white transition hover:bg-[#182654]">
                                <i data-lucide="bar-chart-3" class="h-3.5 w-3.5" aria-hidden="true"></i>
                                Visualisasikan
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <!-- VizBuilder Modal -->
    <template x-if="modalOpen">
        <div class="fixed inset-0 z-50 grid place-items-end bg-slate-950/55 backdrop-blur-xs sm:place-items-center sm:p-6" @click.self="modalOpen = false" @keydown.escape.window="modalOpen = false">
            <div role="dialog" aria-modal="true" aria-labelledby="panel-viz-modal-title" class="flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-t-2xl border border-slate-200 bg-white shadow-2xl sm:rounded-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 bg-[#F8FAFC] px-5 pt-5 pb-4 sm:px-8 sm:pt-6">
                    <div class="min-w-0">
                        <p class="text-[11px] font-bold tracking-wider text-[#0069A8] uppercase">Visualisasi dashboard</p>
                        <h2 id="panel-viz-modal-title" class="mt-1 text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl" x-text="isEdit ? 'Ubah visualisasi' : 'Buat visualisasi'"></h2>
                        <p class="mt-1 text-sm text-[#475569]">Pilih sumber data, kolom, dan bentuk diagram yang ingin ditampilkan di Dashboard.</p>
                    </div>
                    <button type="button" @click="modalOpen = false" aria-label="Tutup" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 hover:border-[#0069a8] hover:text-[#0069a8]">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>

                <form :action="formAction" method="POST" @submit="submitViz($event)" novalidate class="flex min-h-0 flex-col">
                    @csrf
                    <template x-if="isEdit">
                        <input type="hidden" name="_method" value="PUT">
                    </template>
                    <input type="hidden" name="kind" :value="v.kind">
                    <input type="hidden" name="color" :value="v.color">
                    <input type="hidden" name="show" :value="v.show ? '1' : '0'">

                    <div class="grid grid-cols-1 gap-6 overflow-y-auto px-5 py-6 sm:px-8 lg:grid-cols-[1fr_1.1fr]">
                        <div class="space-y-4">
                            <label class="block">
                                <span class="mb-1.5 block text-sm font-semibold text-slate-800">Judul visualisasi <span class="text-[#B91C1C]">*</span></span>
                                <input type="text" name="title" x-model="v.title" placeholder="Contoh: Penerima beasiswa per tahun" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-[#0069A8] focus:ring-4 focus:ring-sky-100" />
                                <span x-show="tried && !v.title.trim()" class="mt-1 block text-xs text-[#B91C1C]">Judul wajib diisi.</span>
                            </label>

                            <label class="block">
                                <span class="mb-1.5 block text-sm font-semibold text-slate-800">Sumber dataset <span class="text-[#B91C1C]">*</span></span>
                                <select name="dataset_id" x-model="v.dataset_id" @change="onDatasetChange()" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-[#0069A8] focus:ring-4 focus:ring-sky-100">
                                    <template x-for="ds in usableDatasets" :key="ds.id">
                                        <option :value="String(ds.id)" x-text="`${ds.title} (${ds.tahun} · ${ds.rowCount} baris)` + (!ds.published ? ' [draft]' : (ds.access === 'private' ? ' [private]' : ''))"></option>
                                    </template>
                                </select>
                            </label>

                            <div class="grid grid-cols-2 gap-3">
                                <label class="block">
                                    <span class="mb-1.5 block text-sm font-semibold text-slate-800">Label <span class="text-[#B91C1C]">*</span></span>
                                    <select name="label_col" x-model.number="v.label_col" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-[#0069A8] focus:ring-4 focus:ring-sky-100">
                                        <template x-for="(col, idx) in currentDataset()?.cols || []" :key="idx">
                                            <option :value="idx" x-text="col"></option>
                                        </template>
                                    </select>
                                </label>
                                <label class="block">
                                    <span class="mb-1.5 block text-sm font-semibold text-slate-800">Nilai <span class="text-[#B91C1C]">*</span></span>
                                    <select name="value_col" x-model.number="v.value_col" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-[#0069A8] focus:ring-4 focus:ring-sky-100">
                                        <template x-for="entry in Object.entries(currentDataset()?.numericCols || {})" :key="entry[0]">
                                            <option :value="Number(entry[0])" x-text="entry[1]"></option>
                                        </template>
                                    </select>
                                </label>
                            </div>

                            <div>
                                <span class="mb-1.5 block text-sm font-semibold text-slate-800">Pilih bentuk diagram</span>
                                <div class="grid grid-cols-4 gap-1.5">
                                    @foreach($kinds as $k => $label)
                                        <button type="button"
                                                @click="v.kind = @js($k)"
                                                :class="v.kind === @js($k) ? 'border-[#0069A8] bg-[#F0F9FF] text-[#0069A8] ring-1 ring-[#0069A8]/30' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'"
                                                class="flex flex-col items-center justify-center gap-1 rounded-xl border px-2 py-2 text-[11px] font-semibold transition">
                                            <span>{{ $label }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            <div>
                                <span class="mb-1.5 block text-sm font-semibold text-slate-800">Warna</span>
                                <div class="flex gap-2">
                                    @foreach($palettes as $k => $pal)
                                        <button type="button" @click="v.color = @js($k)" aria-label="{{ $k }}" :class="v.color === @js($k) ? 'ring-2 ring-slate-400' : ''" class="h-7 w-7 rounded-full ring-offset-2" style="background: {{ $pal[0] }}"></button>
                                    @endforeach
                                </div>
                            </div>

                            <label class="flex cursor-pointer items-center gap-3 text-sm font-semibold text-slate-800">
                                <button type="button" role="switch" :aria-checked="v.show" @click="v.show = !v.show" :class="v.show ? 'bg-[#0069A8]' : 'bg-slate-300'" class="relative h-6 w-11 shrink-0 rounded-full transition">
                                    <span :class="v.show ? 'left-[22px]' : 'left-0.5'" class="absolute top-0.5 h-5 w-5 rounded-full bg-white shadow transition"></span>
                                </button>
                                Tampilkan di Dashboard publik
                            </label>

                            <template x-if="currentDataset() && !currentDataset().published && v.show">
                                <p class="text-xs font-medium text-[#92400E]">
                                    Catatan: dataset sumber masih berstatus Draft. Terbitkan dataset di menu Kelola Dataset agar visualisasi muncul di Dashboard publik.
                                </p>
                            </template>
                            <template x-if="currentDataset() && currentDataset().published && currentDataset().access === 'private' && v.show">
                                <p class="text-xs font-medium text-[#92400E]">
                                    Catatan: dataset sumber berstatus private, sehingga visualisasi ini hanya terlihat oleh Wali Data dan Admin. Jadikan dataset publik agar tampil di Dashboard publik.
                                </p>
                            </template>
                        </div>

                        <div class="flex flex-col rounded-2xl border border-slate-200 bg-[#F8FAFC] p-5">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="font-bold text-slate-900" x-text="v.title || 'Judul visualisasi'"></p>
                                    <p class="truncate text-xs text-[#475569]" x-text="currentDataset()?.title || ''"></p>
                                </div>
                                <div class="inline-flex shrink-0 rounded-lg border border-slate-200 bg-white p-0.5 text-[11px] font-bold">
                                    <button type="button" @click="modalPreviewTab = 'chart'" :class="modalPreviewTab === 'chart' ? 'bg-[#0069A8] text-white' : 'text-slate-600'" class="rounded-md px-2.5 py-1 transition">Grafik</button>
                                    <button type="button" @click="modalPreviewTab = 'data'" :class="modalPreviewTab === 'data' ? 'bg-[#0069A8] text-white' : 'text-slate-600'" class="rounded-md px-2.5 py-1 transition">Data (<span x-text="currentDataset()?.rows?.length || 0"></span>)</button>
                                </div>
                            </div>

                            <div x-show="modalPreviewTab === 'chart'" class="relative mt-4 h-64 rounded-xl border border-slate-200/70 bg-white p-3">
                                <div class="h-full w-full" x-html="previewChartSvg()"></div>
                                <div x-show="v.kind === 'donut'" class="pointer-events-none absolute inset-0 grid place-items-center text-center">
                                    <div class="max-w-[115px] px-1">
                                        <p class="text-xl font-extrabold leading-none tabular-nums text-slate-900" x-text="previewTotal().toLocaleString('id-ID')"></p>
                                        <p class="mt-0.5 truncate text-[10px] font-semibold text-[#475569]" x-text="currentDataset()?.cols?.[v.value_col] || ''"></p>
                                    </div>
                                </div>
                            </div>

                            <div x-show="modalPreviewTab === 'data'" x-cloak class="mt-4 max-h-64 overflow-auto rounded-xl border border-slate-200 bg-white">
                                <table class="w-full text-xs">
                                    <thead class="sticky top-0 bg-[#F8FAFC] text-[11px] font-bold text-[#475569]">
                                        <tr>
                                            <th class="px-3 py-2 text-left" x-text="currentDataset()?.cols?.[v.label_col] || 'Label'"></th>
                                            <th class="px-3 py-2 text-right" x-text="currentDataset()?.cols?.[v.value_col] || 'Nilai'"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <template x-for="(pt, pIdx) in previewSeries()" :key="pIdx">
                                            <tr>
                                                <td class="px-3 py-2 font-medium text-slate-800" x-text="pt.n"></td>
                                                <td class="px-3 py-2 text-right font-bold tabular-nums text-slate-900" x-text="pt.v.toLocaleString('id-ID')"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                    <tfoot class="border-t border-slate-200 bg-[#F8FAFC] font-bold text-slate-900">
                                        <tr>
                                            <td class="px-3 py-2">Total</td>
                                            <td class="px-3 py-2 text-right tabular-nums" x-text="previewTotal().toLocaleString('id-ID')"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:px-8">
                        <button type="button" @click="modalOpen = false" class="inline-flex min-h-[42px] items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Batal
                        </button>
                        <button type="submit" class="inline-flex min-h-[42px] items-center justify-center gap-2 rounded-xl bg-[#0069A8] px-5 py-2 text-sm font-semibold text-white hover:bg-[#182654]">
                            Simpan visualisasi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- Data Detail Modal -->
    <template x-if="dataModalOpen && activeDataItem">
        <div class="fixed inset-0 z-50 grid place-items-end bg-slate-950/55 backdrop-blur-xs sm:place-items-center sm:p-6" @click.self="dataModalOpen = false" @keydown.escape.window="dataModalOpen = false">
            <div role="dialog" aria-modal="true" aria-labelledby="panel-data-modal-title" class="flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-t-2xl border border-slate-200 bg-white shadow-2xl sm:rounded-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 bg-[#F8FAFC] px-5 pt-5 pb-4 sm:px-8">
                    <div class="min-w-0">
                        <p class="text-[11px] font-bold tracking-wider text-[#0069A8] uppercase" x-text="activeDataItem.code ? `Data Sumber · ${activeDataItem.code}` : 'Data Sumber'"></p>
                        <h2 id="panel-data-modal-title" class="mt-1 text-xl font-extrabold tracking-tight text-slate-900" x-text="activeDataItem.title"></h2>
                        <p class="mt-0.5 text-xs text-[#475569]" x-text="`${(activeDataItem.rows || []).length} baris · ${(activeDataItem.cols || []).length} kolom`"></p>
                    </div>
                    <button type="button" @click="dataModalOpen = false" aria-label="Tutup" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 hover:border-[#0069A8] hover:text-[#0069A8]">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>

                <div class="overflow-y-auto px-5 py-5 sm:px-8">
                    <div class="overflow-x-auto rounded-xl border border-slate-200">
                        <table class="w-full text-sm">
                            <thead class="bg-[#F8FAFC] text-xs font-bold text-[#475569]">
                                <tr>
                                    <th class="px-3.5 py-2.5 text-left">#</th>
                                    <template x-for="(col, cIdx) in activeDataItem.cols || []" :key="cIdx">
                                        <th class="px-3.5 py-2.5 text-left whitespace-nowrap" x-text="col"></th>
                                    </template>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template x-for="(row, rIdx) in activeDataItem.rows || []" :key="rIdx">
                                    <tr class="hover:bg-[#F0F9FF]/50">
                                        <td class="px-3.5 py-2.5 text-xs font-semibold tabular-nums text-slate-400" x-text="rIdx + 1"></td>
                                        <template x-for="(cell, cIdx) in row" :key="cIdx">
                                            <td class="px-3.5 py-2.5 tabular-nums text-slate-800" x-text="formatCell(cell)"></td>
                                        </template>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:px-8">
                    <button type="button" @click="downloadCsv(activeDataItem)" class="inline-flex min-h-[40px] items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 hover:border-[#0069A8] hover:text-[#0069A8]">
                        <i data-lucide="download" class="h-3.5 w-3.5" aria-hidden="true"></i>
                        Unduh CSV
                    </button>
                    <button type="button" @click="dataModalOpen = false" class="inline-flex min-h-[40px] items-center justify-center rounded-xl bg-[#0069A8] px-5 py-2 text-sm font-semibold text-white hover:bg-[#182654]">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection

@push('scripts')
@include('components.chart-enhancements')
<script>
function vizManager() {
    return {
        usableDatasets: @json($datasetsPayload),
        vizList: @json($vizPayload),
        vizFilter: 'all',
        vizQuery: '',
        modalOpen: false,
        modalPreviewTab: 'chart',
        dataModalOpen: false,
        activeDataItem: null,
        isEdit: false,
        formAction: @js(route('panel.visualisasi.store')),
        tried: false,
        v: {
            title: '',
            dataset_id: '',
            label_col: 0,
            value_col: 0,
            kind: 'bar',
            color: 'sky',
            show: true,
        },

        init() {
            this.$nextTick(() => {
                this.vizList.forEach((item) => {
                    this.renderCardChart(item, item.kind);
                });
            });
            window.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    this.modalOpen = false;
                    this.dataModalOpen = false;
                }
            });
        },

        renderCardChart(item, kind) {
            const el = document.getElementById('panelVizChart_' + item.code);
            if (!el || !window.SatuDataCharts) return;
            window.__sdPatchCharts?.();
            const seriesData = window.SatuDataCharts.series(item.rows, item.label_col, item.value_col);
            el.innerHTML = window.SatuDataCharts.renderSvg(kind, seriesData, item.palette, 320, 175);
        },

        seriesPoints(item) {
            if (!item || !window.SatuDataCharts) return [];
            return window.SatuDataCharts.series(item.rows || [], item.label_col, item.value_col);
        },

        seriesTotal(item) {
            return this.seriesPoints(item).reduce((acc, cur) => acc + (Number(cur.v) || 0), 0);
        },

        matchesViz(searchKey, isShown) {
            if (this.vizFilter === 'shown' && !isShown) return false;
            if (this.vizFilter === 'hidden' && isShown) return false;
            const q = this.vizQuery.trim().toLowerCase();
            return !q || String(searchKey || '').includes(q);
        },

        openNew() {
            const first = this.usableDatasets[0];
            this.openNewForDataset(first ? first.id : '');
        },

        openNewForDataset(datasetId) {
            const ds = this.usableDatasets.find((d) => String(d.id) === String(datasetId)) || this.usableDatasets[0];
            const numKeys = ds ? Object.keys(ds.numericCols || {}).map(Number) : [0];
            this.isEdit = false;
            this.modalPreviewTab = 'chart';
            this.formAction = @js(route('panel.visualisasi.store'));
            this.v = {
                title: ds ? ds.title : '',
                dataset_id: ds ? String(ds.id) : '',
                label_col: 0,
                value_col: numKeys[0] ?? 0,
                kind: 'bar',
                color: 'sky',
                show: true,
            };
            this.tried = false;
            this.modalOpen = true;
        },

        openEdit(item) {
            this.isEdit = true;
            this.modalPreviewTab = 'chart';
            this.formAction = @js(url('/panel/visualisasi')) + '/' + item.code;
            this.v = {
                title: item.title,
                dataset_id: String(item.dataset_id),
                label_col: Number(item.label_col),
                value_col: Number(item.value_col),
                kind: item.kind,
                color: item.color,
                show: !!item.show,
            };
            this.tried = false;
            this.modalOpen = true;
        },

        openDataModal(item) {
            this.activeDataItem = {
                code: item.datasetCode || item.code,
                title: item.title + (item.datasetTitle ? ` (${item.datasetTitle})` : ''),
                cols: item.cols || [],
                rows: item.rows || [],
            };
            this.dataModalOpen = true;
        },

        openDatasetDataModal(ds) {
            this.activeDataItem = {
                code: ds.code,
                title: ds.title,
                cols: ds.cols || [],
                rows: ds.rows || [],
            };
            this.dataModalOpen = true;
        },

        formatCell(cell) {
            if (typeof cell === 'number') return cell.toLocaleString('id-ID');
            return cell ?? '-';
        },

        downloadCsv(item) {
            const esc = (v) => {
                const s = String(v ?? '');
                return /[",\n;]/.test(s) ? `"${s.replace(/"/g, '""')}"` : s;
            };
            const lines = [
                (item.cols || []).map(esc).join(','),
                ...(item.rows || []).map((r) => r.map(esc).join(',')),
            ];
            const blob = new Blob(['\uFEFF' + lines.join('\r\n')], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = `${item.code || 'data'}.csv`;
            link.click();
            setTimeout(() => URL.revokeObjectURL(link.href), 1000);
        },

        currentDataset() {
            return this.usableDatasets.find((d) => String(d.id) === String(this.v.dataset_id)) || null;
        },

        onDatasetChange() {
            const ds = this.currentDataset();
            if (!ds) return;
            const numKeys = Object.keys(ds.numericCols || {}).map(Number);
            this.v.label_col = 0;
            this.v.value_col = numKeys[0] ?? 0;
        },

        previewSeries() {
            const ds = this.currentDataset();
            if (!ds || !window.SatuDataCharts) return [];
            return window.SatuDataCharts.series(ds.rows, this.v.label_col, this.v.value_col);
        },

        previewTotal() {
            return this.previewSeries().reduce((acc, cur) => acc + (Number(cur.v) || 0), 0);
        },

        previewChartSvg() {
            const ds = this.currentDataset();
            if (!ds || !window.SatuDataCharts) return '';
            window.__sdPatchCharts?.();
            const seriesData = this.previewSeries();
            const pal = window.SatuDataCharts.palettes[this.v.color] || window.SatuDataCharts.palettes.sky;
            return window.SatuDataCharts.renderSvg(this.v.kind, seriesData, pal, 320, 220);
        },

        submitViz(ev) {
            this.tried = true;
            if (!this.v.title.trim() || !this.currentDataset()) {
                ev.preventDefault();
            }
        },
    };
}
</script>
@endpush
