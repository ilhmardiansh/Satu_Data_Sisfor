@extends('layouts.panel')

@section('title', 'Kategori Data - Panel Satu Data UNUSA')

@section('panel_content')
@php
    $isAdmin = auth()->user()->isAdmin();
    $maxCatUsage = max(1, $categories->max(fn ($c) => $c->data_forms_count + $c->datasets_count) ?? 1);
@endphp

<div x-data="categoryManager()">
    <x-panel-head
        scene="kategori"
        badge="{{ $categories->count() }} Kategori"
        badgeTone="sky"
        title="Kategori Data"
        desc="Kelompokkan formulir dan dataset ke dalam kategori data prodi."
    >
        <button type="button"
                @click="openNew()"
                class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl bg-[#00A6F4] px-4 py-2.5 text-sm font-bold text-[#09122C] transition hover:bg-[#38BDF8] motion-safe:active:scale-[0.98]">
            <i data-lucide="plus" class="h-4 w-4" aria-hidden="true"></i>
            Tambah kategori
        </button>
    </x-panel-head>

    <!-- Dynamic Search & View Switcher -->
    <div class="wd-bento mb-5 flex flex-wrap items-center justify-between gap-3 p-3">
        <label class="flex h-10 min-w-[220px] flex-1 items-center gap-2 rounded-xl border border-slate-200 bg-[#F8FAFC] px-3 focus-within:border-[#0069A8] focus-within:bg-white sm:max-w-sm">
            <i data-lucide="search" class="h-4 w-4 shrink-0 text-[#475569]" aria-hidden="true"></i>
            <input type="text"
                   x-model="search"
                   placeholder="Cari kode atau nama kategori..."
                   class="min-w-0 flex-1 bg-transparent text-sm outline-none placeholder:text-slate-400" />
        </label>

        <div class="inline-flex rounded-xl border border-slate-200/90 bg-[#F8FAFC] p-1 text-xs font-bold">
            <button type="button"
                    @click="viewMode = 'table'"
                    :class="viewMode === 'table' ? 'bg-[#0069A8] text-white' : 'text-slate-600 hover:text-slate-900'"
                    class="min-h-[36px] rounded-lg px-3.5 py-1.5 transition">
                Tabel
            </button>
            <button type="button"
                    @click="viewMode = 'cards'"
                    :class="viewMode === 'cards' ? 'bg-[#0069A8] text-white' : 'text-slate-600 hover:text-slate-900'"
                    class="min-h-[36px] rounded-lg px-3.5 py-1.5 transition">
                Kartu Bento
            </button>
        </div>
    </div>

    <!-- View Mode 1: Table -->
    <div x-show="viewMode === 'table'" class="wd-bento overflow-hidden">
        <div class="overflow-x-auto">
            <table class="wd-hairline-table min-w-[640px]">
                <thead>
                    <tr>
                        <th class="px-4 py-3 text-left text-[11px] font-bold tracking-wider whitespace-nowrap text-[#475569] uppercase">Kode</th>
                        <th class="px-4 py-3 text-left text-[11px] font-bold tracking-wider whitespace-nowrap text-[#475569] uppercase">Nama</th>
                        <th class="px-4 py-3 text-left text-[11px] font-bold tracking-wider whitespace-nowrap text-[#475569] uppercase">Deskripsi</th>
                        <th class="px-4 py-3 text-left text-[11px] font-bold tracking-wider whitespace-nowrap text-[#475569] uppercase">Formulir</th>
                        <th class="px-4 py-3 text-left text-[11px] font-bold tracking-wider whitespace-nowrap text-[#475569] uppercase">Dataset</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($categories as $c)
                        @php
                            $used = $c->data_forms_count + $c->datasets_count;
                            $catSearchKey = strtolower($c->code.' '.$c->name.' '.($c->description ?? ''));
                        @endphp
                        <tr x-show="matchesSearch(@js($catSearchKey))" class="transition-colors hover:bg-[#F8FAFC]">
                            <td class="px-4 py-3 text-xs font-bold tabular-nums text-[#0069A8]">{{ $c->code }}</td>
                            <td class="px-4 py-3 text-sm font-bold text-slate-900">{{ $c->name }}</td>
                            <td class="px-4 py-3 text-sm text-[#475569]">{{ $c->description }}</td>
                            <td class="px-4 py-3 text-sm tabular-nums text-slate-700">{{ $c->data_forms_count }}</td>
                            <td class="px-4 py-3 text-sm tabular-nums text-slate-700">{{ $c->datasets_count }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">
                                <div class="flex justify-end gap-1.5">
                                    <button
                                        type="button"
                                        @click="openEdit({ id: {{ $c->id }}, code: @js($c->code), name: @js($c->name), description: @js($c->description ?? ''), action: @js(route('panel.kategori.update', $c->id)) })"
                                        class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-[#0069A8] hover:text-[#0069A8]"
                                        aria-label="Ubah"
                                    >
                                        <i data-lucide="pencil" class="h-4 w-4" aria-hidden="true"></i>
                                    </button>
                                    @if($isAdmin)
                                        @if($used > 0)
                                            <button
                                                type="button"
                                                @click="$dispatch('toast', { message: @js("Kategori masih dipakai {$used} formulir/dataset. Pindahkan dulu sebelum menghapus."), tone: 'error' })"
                                                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition hover:border-red-200 hover:bg-red-50 hover:text-[#B91C1C]"
                                                aria-label="Hapus"
                                            >
                                                <i data-lucide="trash-2" class="h-4 w-4" aria-hidden="true"></i>
                                            </button>
                                        @else
                                            <button
                                                type="button"
                                                @click="$dispatch('open-confirm', {
                                                    message: @js("Kategori “{$c->name}” akan dihapus."),
                                                    action: @js(route('panel.kategori.destroy', $c->id)),
                                                    method: 'DELETE'
                                                })"
                                                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition hover:border-red-200 hover:bg-red-50 hover:text-[#B91C1C]"
                                                aria-label="Hapus"
                                            >
                                                <i data-lucide="trash-2" class="h-4 w-4" aria-hidden="true"></i>
                                            </button>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- View Mode 2: Bento Cards -->
    <div x-show="viewMode === 'cards'" x-cloak class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($categories as $c)
            @php
                $used = $c->data_forms_count + $c->datasets_count;
                $pct = $used > 0 ? max(8, (int) round(($used / $maxCatUsage) * 100)) : 4;
                $catSearchKey = strtolower($c->code.' '.$c->name.' '.($c->description ?? ''));
            @endphp
            <article x-show="matchesSearch(@js($catSearchKey))" class="wd-bento-interactive flex flex-col justify-between p-5">
                <div>
                    <div class="flex items-start justify-between gap-2">
                        <span class="rounded-md bg-[#F0F9FF] px-2 py-0.5 text-[11px] font-bold tabular-nums text-[#0069A8]">{{ $c->code }}</span>
                        <div class="flex gap-1">
                            <button type="button"
                                    @click="openEdit({ id: {{ $c->id }}, code: @js($c->code), name: @js($c->name), description: @js($c->description ?? ''), action: @js(route('panel.kategori.update', $c->id)) })"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 hover:border-[#0069A8] hover:text-[#0069A8]"
                                    aria-label="Ubah">
                                <i data-lucide="pencil" class="h-3.5 w-3.5" aria-hidden="true"></i>
                            </button>
                            @if($isAdmin)
                                <button type="button"
                                        @click="{{ $used > 0
                                            ? "\$dispatch('toast', { message: ".json_encode("Kategori masih dipakai {$used} formulir/dataset. Pindahkan dulu sebelum menghapus.").", tone: 'error' })"
                                            : "\$dispatch('open-confirm', { message: ".json_encode("Kategori “{$c->name}” akan dihapus.").", action: ".json_encode(route('panel.kategori.destroy', $c->id)).", method: 'DELETE' })" }}"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 hover:border-red-200 hover:bg-red-50 hover:text-[#B91C1C]"
                                        aria-label="Hapus">
                                    <i data-lucide="trash-2" class="h-3.5 w-3.5" aria-hidden="true"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                    <h2 class="mt-2.5 text-base font-extrabold text-slate-900">{{ $c->name }}</h2>
                    <p class="mt-1 line-clamp-2 text-xs leading-relaxed text-[#475569]">{{ $c->description ?: 'Tidak ada deskripsi tambahan.' }}</p>
                </div>

                <div class="mt-4 border-t border-slate-100 pt-3">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-semibold text-slate-700">{{ $c->data_forms_count }} Formulir · {{ $c->datasets_count }} Dataset</span>
                        <span class="font-bold tabular-nums text-[#0069A8]">{{ $used }}</span>
                    </div>
                    <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full {{ $used > 0 ? 'bg-[#0069A8]' : 'bg-slate-200' }}" style="width: {{ $pct }}%"></div>
                    </div>
                </div>
            </article>
        @endforeach
    </div>

    <!-- Add / Edit Modal -->
    <template x-if="modalOpen">
        <div class="fixed inset-0 z-50 grid place-items-end bg-slate-950/55 backdrop-blur-xs sm:place-items-center sm:p-6" @click.self="modalOpen = false" @keydown.escape.window="modalOpen = false">
            <div role="dialog" aria-modal="true" class="flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-t-2xl border border-slate-200 bg-white shadow-2xl sm:rounded-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 bg-[#F8FAFC] px-5 pt-5 pb-4 sm:px-8 sm:pt-6">
                    <div class="min-w-0">
                        <p class="text-[11px] font-bold tracking-wider text-[#0069A8] uppercase">Kategori data</p>
                        <h2 class="mt-1 text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl" x-text="isEdit ? 'Ubah kategori' : 'Tambah kategori'"></h2>
                    </div>
                    <button type="button" @click="modalOpen = false" aria-label="Tutup" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 hover:border-[#0069A8] hover:text-[#0069A8]">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>

                <form :action="formAction" method="POST" @submit="submit($event)" novalidate class="flex min-h-0 flex-col">
                    @csrf
                    <template x-if="isEdit">
                        <input type="hidden" name="_method" value="PUT">
                    </template>
                    <div class="grid grid-cols-1 gap-5 overflow-y-auto px-5 py-6 sm:grid-cols-2 sm:px-8">
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">Kode <span class="text-[#B91C1C]">*</span></span>
                            <input type="text" name="code" x-model="form.code" class="w-full rounded-xl border bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-[#0069A8] focus:ring-4 focus:ring-sky-100" :class="err('code') ? 'border-[#B91C1C]' : 'border-slate-200'" />
                            <span x-show="err('code')" class="mt-1 block text-xs text-[#B91C1C]" x-text="err('code')"></span>
                            <span x-show="!err('code')" class="mt-1 block text-xs text-[#475569]">Contoh: KT-MHS-07</span>
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">Nama kategori <span class="text-[#B91C1C]">*</span></span>
                            <input type="text" name="name" x-model="form.name" class="w-full rounded-xl border bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-[#0069A8] focus:ring-4 focus:ring-sky-100" :class="err('name') ? 'border-[#B91C1C]' : 'border-slate-200'" />
                            <span x-show="err('name')" class="mt-1 block text-xs text-[#B91C1C]" x-text="err('name')"></span>
                        </label>
                        <label class="block sm:col-span-2">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">Deskripsi</span>
                            <textarea rows="4" name="description" x-model="form.description" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-[#0069A8] focus:ring-4 focus:ring-sky-100"></textarea>
                        </label>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:px-8">
                        <button type="button" @click="modalOpen = false" class="inline-flex min-h-[42px] items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Batal
                        </button>
                        <button type="submit" class="inline-flex min-h-[42px] items-center justify-center gap-2 rounded-xl bg-[#0069A8] px-5 py-2 text-sm font-semibold text-white hover:bg-[#182654]">
                            Simpan
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
function categoryManager() {
    const existingCodes = @json($categories->map(fn ($c) => ['id' => $c->id, 'code' => $c->code]));
    return {
        search: '',
        viewMode: 'table',
        modalOpen: false,
        isEdit: false,
        editId: null,
        formAction: @js(route('panel.kategori.store')),
        tried: false,
        form: { code: '', name: '', description: '' },

        matchesSearch(haystack) {
            const q = this.search.trim().toLowerCase();
            return !q || String(haystack || '').includes(q);
        },

        openNew() {
            this.isEdit = false;
            this.editId = null;
            this.formAction = @js(route('panel.kategori.store'));
            this.form = { code: '', name: '', description: '' };
            this.tried = false;
            this.modalOpen = true;
        },

        openEdit(item) {
            this.isEdit = true;
            this.editId = item.id;
            this.formAction = item.action;
            this.form = { code: item.code, name: item.name, description: item.description };
            this.tried = false;
            this.modalOpen = true;
        },

        errors() {
            const e = {};
            const code = String(this.form.code || '').trim();
            if (!code) {
                e.code = 'Kolom ini wajib diisi.';
            } else if (!/^[A-Z0-9-]+$/.test(code)) {
                e.code = 'Gunakan huruf kapital, angka, dan tanda hubung.';
            } else if (existingCodes.some((c) => c.code === code && c.id !== this.editId)) {
                e.code = 'Kode sudah dipakai.';
            }
            if (!String(this.form.name || '').trim()) {
                e.name = 'Kolom ini wajib diisi.';
            }
            return e;
        },

        err(k) {
            return this.tried ? (this.errors()[k] || '') : '';
        },

        submit(ev) {
            this.tried = true;
            if (Object.keys(this.errors()).length > 0) ev.preventDefault();
        },
    };
}
</script>
@endpush
