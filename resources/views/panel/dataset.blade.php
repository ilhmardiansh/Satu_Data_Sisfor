@extends('layouts.panel')

@section('title', 'Kelola Dataset - Panel Satu Data UNUSA')

@section('panel_content')
@php
    $isAdmin = auth()->user()->isAdmin();
@endphp

<div x-data="manageDatasetManager()">
    <x-panel-head
        title="Kelola Dataset"
        desc="Sebarluaskan data dengan menetapkan dataset sebagai publik atau private."
    >
        <button type="button" @click="openBuilder()" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-700 motion-safe:active:scale-[0.97]">
            <i data-lucide="plus" class="h-4 w-4"></i>
            Buat dataset
        </button>
    </x-panel-head>

    <form method="GET" action="{{ route('panel.dataset') }}" class="mb-4 flex flex-wrap items-center gap-2 rounded-2xl border border-slate-200 bg-surface p-3">
        <label class="flex h-10 min-w-0 flex-1 items-center gap-2 rounded-xl border border-slate-200 bg-white px-3">
            <i data-lucide="search" class="h-4 w-4 text-slate-400"></i>
            <input type="text" name="q" value="{{ $q }}" placeholder="Cari dataset…" class="min-w-0 flex-1 bg-transparent text-sm outline-none" />
        </label>
        <select name="kategori" onchange="this.form.submit()" class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
            <option value="all">Semua kategori</option>
            @foreach($categories as $c)
                <option value="{{ $c->code }}" {{ $kategori === $c->code ? 'selected' : '' }}>{{ $c->name }}</option>
            @endforeach
        </select>
        <select name="tahun" onchange="this.form.submit()" class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
            <option value="all">Semua tahun</option>
            @foreach($years as $y)
                <option value="{{ $y }}" {{ $tahun === (string) $y ? 'selected' : '' }}>{{ $y }}</option>
            @endforeach
        </select>
        <button type="submit" class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">
            Cari
        </button>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-surface">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px]">
                <thead class="bg-surface-strong">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-slate-500 uppercase">Dataset</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-slate-500 uppercase">Periode</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-slate-500 uppercase">Baris</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-slate-500 uppercase">Publik</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold tracking-wide whitespace-nowrap text-slate-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($datasets as $d)
                        @php
                            $editPayload = [
                                'code' => $d->code,
                                'action' => route('panel.dataset.update', $d->code),
                                'title' => $d->title,
                                'description' => $d->description ?? '',
                                'category_id' => (string) $d->category_id,
                                'tahun' => (string) $d->tahun,
                                'periode' => $d->periode,
                                'access' => $d->access,
                                'format' => $d->format,
                            ];
                        @endphp
                        <tr class="border-t border-slate-200/70 hover:bg-white">
                            <td class="px-4 py-3 text-sm text-slate-600">
                                <p class="font-semibold text-slate-900">{{ $d->title }}</p>
                                <p class="text-xs text-slate-400">
                                    {{ $d->category?->name }} · {{ $d->format }} · diperbarui {{ $d->updated_label ?? ($d->updated_at?->locale('id')->translatedFormat('j M Y') ?? '-') }}
                                </p>
                            </td>
                            <td class="px-4 py-3 text-sm whitespace-nowrap text-slate-600">
                                {{ $d->tahun }} · {{ $d->periode }}
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ count($d->rows ?? []) }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">
                                <div class="flex items-center gap-2" x-data="{ pub: @js($d->access === 'public'), busy: false }">
                                    <button
                                        type="button"
                                        role="switch"
                                        :aria-checked="pub"
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
                                        :class="pub ? 'bg-sky-600' : 'bg-slate-300'"
                                        class="relative h-6 w-11 shrink-0 rounded-full transition"
                                    >
                                        <span :class="pub ? 'left-[22px]' : 'left-0.5'" class="absolute top-0.5 h-5 w-5 rounded-full bg-white shadow transition"></span>
                                    </button>
                                    <span x-show="pub" class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 text-[11px] font-bold text-emerald-700">Publik</span>
                                    <span x-show="!pub" x-cloak class="inline-flex items-center gap-1 rounded-md bg-amber-50 px-2 py-0.5 text-[11px] font-bold text-amber-700">Private</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">
                                <div class="flex justify-end gap-1.5">
                                    <button type="button"
                                            @click="openPreview(@js(route('dataset.preview', $d->code)))"
                                            class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-sky-300 hover:text-sky-700"
                                            aria-label="Lihat">
                                        <i data-lucide="eye" class="h-4 w-4"></i>
                                    </button>
                                    <button type="button"
                                            @click="openEdit(@js($editPayload))"
                                            class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-sky-300 hover:text-sky-700"
                                            aria-label="Ubah">
                                        <i data-lucide="pencil" class="h-4 w-4"></i>
                                    </button>
                                    @if($isAdmin)
                                        <button type="button"
                                                @click="$dispatch('open-confirm', {
                                                    message: @js("Dataset “{$d->title}” beserta visualisasi terkait akan dihapus."),
                                                    action: @js(route('panel.dataset.destroy', $d->code)),
                                                    method: 'DELETE'
                                                })"
                                                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition hover:border-red-200 hover:bg-red-50 hover:text-[#DC3545]"
                                                aria-label="Hapus">
                                            <i data-lucide="trash-2" class="h-4 w-4"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">
                                Tidak ada dataset yang sesuai filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $datasets->links('components.pagination') }}
    </div>

    <!-- Preview Modal -->
    <template x-if="previewOpen && previewData">
        <div class="fixed inset-0 z-50 grid place-items-end bg-slate-900/40 backdrop-blur-sm sm:place-items-center sm:p-6" @click.self="previewOpen = false">
            <div role="dialog" aria-modal="true" class="flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-t-2xl bg-white shadow-2xl sm:rounded-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 pt-5 pb-4 sm:px-8 sm:pt-6">
                    <div class="min-w-0">
                        <p class="flex flex-wrap items-center gap-2 text-sm text-slate-500">
                            <span>Dataset › <span x-text="previewData.category"></span></span>
                            <span x-show="previewData.access === 'public'" class="inline-flex rounded-md bg-emerald-50 px-2 py-0.5 text-[11px] font-bold text-emerald-700">Publik</span>
                            <span x-show="previewData.access === 'private'" class="inline-flex rounded-md bg-amber-50 px-2 py-0.5 text-[11px] font-bold text-amber-700">Private</span>
                        </p>
                        <h2 class="mt-1 text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl" x-text="previewData.title"></h2>
                        <p class="mt-1 text-sm text-slate-500" x-text="previewData.desc"></p>
                    </div>
                    <button type="button" @click="previewOpen = false" aria-label="Tutup" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 hover:border-sky-300 hover:text-sky-700">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>

                <div class="overflow-auto px-5 py-5 sm:px-8">
                    <div class="overflow-hidden rounded-xl border border-slate-200">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-surface-strong">
                                    <tr>
                                        <template x-for="c in previewData.cols" :key="c">
                                            <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-slate-500 uppercase" x-text="c"></th>
                                        </template>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(r, idx) in previewData.rows" :key="idx">
                                        <tr class="border-t border-slate-100 hover:bg-sky-50/50">
                                            <template x-for="(val, cIdx) in r" :key="cIdx">
                                                <td class="px-4 py-3 text-sm tabular-nums text-slate-600" x-text="val"></td>
                                            </template>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:px-8">
                    <p class="mr-auto text-sm text-slate-500" x-text="`${previewData.id} · ${previewData.rows.length} baris · diperbarui ${previewData.updated}`"></p>
                    <a :href="previewData.csvUrl" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">CSV</a>
                    <a :href="previewData.xlsxUrl" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white hover:bg-sky-700">XLSX</a>
                </div>
            </div>
        </div>
    </template>

    <!-- Edit Metadata Modal -->
    <template x-if="editOpen && editTarget">
        <div class="fixed inset-0 z-50 grid place-items-end bg-slate-900/40 backdrop-blur-sm sm:place-items-center sm:p-6" @click.self="editOpen = false">
            <div role="dialog" aria-modal="true" class="flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-t-2xl bg-white shadow-2xl sm:rounded-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 pt-5 pb-4 sm:px-8 sm:pt-6">
                    <div class="min-w-0">
                        <p class="text-sm text-slate-500" x-text="editTarget.code"></p>
                        <h2 class="mt-1 text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">Ubah dataset</h2>
                    </div>
                    <button type="button" @click="editOpen = false" aria-label="Tutup" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 hover:border-sky-300 hover:text-sky-700">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>

                <form :action="editTarget.action" method="POST" @submit="submitEdit($event)" novalidate class="flex min-h-0 flex-col">
                    @csrf
                    <input type="hidden" name="_method" value="PUT">
                    <div class="grid grid-cols-1 gap-5 overflow-y-auto px-5 py-6 sm:grid-cols-2 sm:px-8">
                        <label class="block sm:col-span-2">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">Judul <span class="text-[#DC3545]">*</span></span>
                            <input type="text" name="title" x-model="editForm.title" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                            <span x-show="editTried && !editForm.title.trim()" class="mt-1 block text-xs text-[#DC3545]">Kolom ini wajib diisi.</span>
                        </label>
                        <label class="block sm:col-span-2">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">Deskripsi</span>
                            <textarea rows="4" name="description" x-model="editForm.description" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100"></textarea>
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">Kategori <span class="text-[#DC3545]">*</span></span>
                            <select name="category_id" x-model="editForm.category_id" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                                @foreach($categories as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">Tahun <span class="text-[#DC3545]">*</span></span>
                            <input type="text" name="tahun" x-model="editForm.tahun" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                            <span x-show="editTried && !/^\d{4}$/.test(editForm.tahun)" class="mt-1 block text-xs text-[#DC3545]">Tahun 4 digit.</span>
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">Semester <span class="text-[#DC3545]">*</span></span>
                            <select name="periode" x-model="editForm.periode" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                                <option value="Ganjil">Ganjil</option>
                                <option value="Genap">Genap</option>
                            </select>
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">Akses <span class="text-[#DC3545]">*</span></span>
                            <select name="access" x-model="editForm.access" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                                <option value="public">Publik</option>
                                <option value="private">Private</option>
                            </select>
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">Format <span class="text-[#DC3545]">*</span></span>
                            <select name="format" x-model="editForm.format" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                                <option value="CSV">CSV</option>
                                <option value="XLSX">XLSX</option>
                            </select>
                        </label>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:px-8">
                        <button type="button" @click="editOpen = false" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Batal
                        </button>
                        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white hover:bg-sky-700">
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    @include('panel.partials.dataset-builder-modal')
</div>
@endsection

@push('scripts')
@include('panel.partials.dataset-builder-script')
<script>
function manageDatasetManager() {
    return {
        ...datasetBuilderMixin(@json($builderFormsPayload)),
        previewOpen: false,
        previewData: null,
        editOpen: false,
        editTarget: null,
        editTried: false,
        editForm: {
            title: '',
            description: '',
            category_id: '',
            tahun: '',
            periode: 'Ganjil',
            access: 'public',
            format: 'CSV',
        },

        async openPreview(url) {
            const resp = await fetch(url, { headers: { Accept: 'application/json' } });
            if (resp.ok) {
                this.previewData = await resp.json();
                this.previewOpen = true;
            }
        },

        openEdit(item) {
            this.editTarget = item;
            this.editForm = { ...item };
            this.editTried = false;
            this.editOpen = true;
        },

        submitEdit(ev) {
            this.editTried = true;
            if (!this.editForm.title.trim() || !/^\d{4}$/.test(this.editForm.tahun)) {
                ev.preventDefault();
            }
        },
    };
}
</script>
@endpush
