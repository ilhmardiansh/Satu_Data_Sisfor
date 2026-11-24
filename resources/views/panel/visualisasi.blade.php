@extends('layouts.panel')

@section('title', 'Visualisasi Dashboard - Panel Satu Data UNUSA')

@section('panel_content')
@php
    $user = auth()->user();
    $shownCount = collect($vizPayload)->where('show', true)->count();
@endphp

<div x-data="vizManager()">
    <x-panel-head
        eyebrow="TAHAP 05 · ANALITIKA PUBLIK"
        scene="visualisasi"
        badge="{{ $shownCount }}/{{ count($vizPayload) }} Tampil"
        badgeTone="sky"
        title="Visualisasi Dashboard"
        desc="Bangun grafik interaktif dari dataset bernumerik yang telah tersusun. Pengunjung dapat mengubah kolom dan tipe grafik langsung di Dashboard."
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

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach($vizPayload as $v)
                @php
                    $canDelete = $user->isAdmin() || (int) $v['created_by'] === $user->id;
                    $vizSearchKey = strtolower($v['code'].' '.$v['title'].' '.$v['datasetTitle'].' '.$v['creatorName']);
                @endphp
                <div class="wd-bento-interactive flex flex-col p-5"
                     x-data="{ shown: @js((bool) $v['show']), busy: false }"
                     x-show="matchesViz(@js($vizSearchKey), shown)">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="rounded bg-[#F0F9FF] px-1.5 py-0.5 font-mono text-[10px] font-bold text-[#0069A8]">{{ $v['code'] }}</span>
                                @if(! ($v['datasetPublished'] ?? true))
                                    <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold text-slate-700">Sumber Draft</span>
                                @elseif(($v['datasetAccess'] ?? 'public') === 'private')
                                    <span class="rounded bg-[#FFFBEB] px-1.5 py-0.5 text-[10px] font-bold text-[#92400E]">Sumber Private</span>
                                @endif
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

                    <div class="mt-4 h-40 rounded-xl border border-slate-100 bg-[#F8FAFC] p-2" id="panelVizChart_{{ $v['code'] }}"></div>

                    <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3">
                        <span class="text-xs font-semibold" :class="shown ? 'text-[#047857]' : 'text-slate-500'" x-text="shown ? 'Tampil di Dashboard' : 'Disembunyikan'"></span>
                        <div class="flex gap-1.5">
                            <button
                                type="button"
                                @click="openEdit(@js($v))"
                                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-[#0069A8] hover:text-[#0069A8]"
                                aria-label="Ubah"
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
        <div class="wd-bento border-dashed border-slate-300 px-6 py-14 text-center">
            <span class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-[#F0F9FF] text-[#0069A8]">
                <i data-lucide="bar-chart-3" class="h-6 w-6" aria-hidden="true"></i>
            </span>
            @if(empty($datasetsPayload))
                <p class="mt-4 font-bold text-slate-800">Belum ada dataset bernumerik untuk divisualisasikan</p>
                <p class="mt-1 text-sm text-[#475569]">Susun dataset rekap atau tabel bernumerik dari data yang telah lolos Pre-processing terlebih dahulu.</p>
                <a href="{{ route('panel.dataset') }}" class="mt-4 inline-flex min-h-[42px] items-center rounded-xl bg-[#0069A8] px-4 py-2 text-sm font-semibold text-white hover:bg-[#182654]">
                    Kelola Dataset
                </a>
            @else
                <p class="mt-4 font-bold text-slate-800">Belum ada visualisasi</p>
                <p class="mt-1 text-sm text-[#475569]">Buat visualisasi pertama dari dataset bernumerik yang telah Anda susun.</p>
            @endif
        </div>
    @endif

    <!-- VizBuilder Modal -->
    <template x-if="modalOpen">
        <div class="fixed inset-0 z-50 grid place-items-end bg-slate-950/55 backdrop-blur-xs sm:place-items-center sm:p-6" @click.self="modalOpen = false" @keydown.escape.window="modalOpen = false">
            <div role="dialog" aria-modal="true" aria-labelledby="panel-viz-modal-title" class="flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-t-2xl border border-slate-200 bg-white shadow-2xl sm:rounded-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 bg-[#F8FAFC] px-5 pt-5 pb-4 sm:px-8 sm:pt-6">
                    <div class="min-w-0">
                        <p class="font-mono text-[11px] font-bold tracking-wider text-[#0069A8] uppercase">Visualisasi dashboard</p>
                        <h2 id="panel-viz-modal-title" class="mt-1 text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl" x-text="isEdit ? 'Ubah visualisasi' : 'Buat visualisasi'"></h2>
                        <p class="mt-1 text-sm text-[#475569]">Pilih data yang ingin ditampilkan di Dashboard dalam bentuk angka atau grafik.</p>
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
                                        <option :value="String(ds.id)" x-text="ds.title + (!ds.published ? ' (draft)' : (ds.access === 'private' ? ' (private)' : ''))"></option>
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
                                <span class="mb-1.5 block text-sm font-semibold text-slate-800">Jenis grafik default</span>
                                <div class="grid grid-cols-3 gap-1.5">
                                    @foreach($kinds as $k => $label)
                                        <button type="button" @click="v.kind = @js($k)" :class="v.kind === @js($k) ? 'border-[#0069A8] bg-[#F0F9FF] text-[#0069A8]' : 'border-slate-200 text-slate-600'" class="rounded-lg border py-1.5 text-xs font-semibold transition">
                                            {{ $label }}
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

                        <div class="rounded-2xl border border-slate-200 bg-[#F8FAFC] p-5">
                            <p class="font-bold text-slate-900" x-text="v.title || 'Judul visualisasi'"></p>
                            <p class="text-xs text-[#475569]" x-text="currentDataset()?.title || ''"></p>
                            <div class="mt-4 h-64 rounded-xl border border-slate-200/70 bg-white p-3" x-html="previewChartSvg()"></div>
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
</div>
@endsection

@push('scripts')
<script>
function vizManager() {
    return {
        usableDatasets: @json($datasetsPayload),
        vizList: @json($vizPayload),
        vizFilter: 'all',
        vizQuery: '',
        modalOpen: false,
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
                    const el = document.getElementById('panelVizChart_' + item.code);
                    if (el && window.SatuDataCharts) {
                        const seriesData = window.SatuDataCharts.series(item.rows, item.label_col, item.value_col);
                        el.innerHTML = window.SatuDataCharts.renderSvg(item.kind, seriesData, item.palette, 280, 160);
                    }
                });
            });
            window.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') this.modalOpen = false;
            });
        },

        matchesViz(searchKey, isShown) {
            if (this.vizFilter === 'shown' && !isShown) return false;
            if (this.vizFilter === 'hidden' && isShown) return false;
            const q = this.vizQuery.trim().toLowerCase();
            return !q || String(searchKey || '').includes(q);
        },

        openNew() {
            const first = this.usableDatasets[0];
            const numKeys = first ? Object.keys(first.numericCols || {}).map(Number) : [0];
            this.isEdit = false;
            this.formAction = @js(route('panel.visualisasi.store'));
            this.v = {
                title: '',
                dataset_id: first ? String(first.id) : '',
                label_col: 0,
                value_col: numKeys[numKeys.length - 1] ?? 0,
                kind: 'bar',
                color: 'sky',
                show: true,
            };
            this.tried = false;
            this.modalOpen = true;
        },

        openEdit(item) {
            this.isEdit = true;
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

        currentDataset() {
            return this.usableDatasets.find((d) => String(d.id) === String(this.v.dataset_id)) || null;
        },

        onDatasetChange() {
            const ds = this.currentDataset();
            if (!ds) return;
            const numKeys = Object.keys(ds.numericCols || {}).map(Number);
            this.v.label_col = 0;
            this.v.value_col = numKeys[numKeys.length - 1] ?? 0;
        },

        previewChartSvg() {
            const ds = this.currentDataset();
            if (!ds || !window.SatuDataCharts) return '';
            const seriesData = window.SatuDataCharts.series(ds.rows, this.v.label_col, this.v.value_col);
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
