@extends('layouts.panel')

@section('title', 'Kategori Data - Panel Satu Data UNUSA')

@section('panel_content')
@php
    $isAdmin = auth()->user()->isAdmin();
@endphp

<div x-data="categoryManager()">
    <x-panel-head
        title="Kategori Data"
        desc="Kategori untuk mengelompokkan formulir, data, dan dataset. Kategori baru dapat ditambahkan tanpa mengubah kode program."
    >
        <button type="button" @click="openNew()" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-700 motion-safe:active:scale-[0.97]">
            <i data-lucide="plus" class="h-4 w-4"></i>
            Tambah kategori
        </button>
    </x-panel-head>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-surface">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px]">
                <thead class="bg-surface-strong">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-slate-500 uppercase">Kode</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-slate-500 uppercase">Nama</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-slate-500 uppercase">Deskripsi</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-slate-500 uppercase">Formulir</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-slate-500 uppercase">Dataset</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($categories as $c)
                        @php $used = $c->data_forms_count + $c->datasets_count; @endphp
                        <tr class="border-t border-slate-200/70 hover:bg-white">
                            <td class="px-4 py-3 font-mono text-xs font-bold text-sky-700">{{ $c->code }}</td>
                            <td class="px-4 py-3 text-sm font-semibold text-slate-900">{{ $c->name }}</td>
                            <td class="px-4 py-3 text-sm text-slate-500">{{ $c->description }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $c->data_forms_count }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $c->datasets_count }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">
                                <div class="flex justify-end gap-1.5">
                                    <button
                                        type="button"
                                        @click="openEdit({ id: {{ $c->id }}, code: @js($c->code), name: @js($c->name), description: @js($c->description ?? ''), action: @js(route('panel.kategori.update', $c->id)) })"
                                        class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-sky-300 hover:text-sky-700"
                                        aria-label="Ubah"
                                    >
                                        <i data-lucide="pencil" class="h-4 w-4"></i>
                                    </button>
                                    @if($isAdmin)
                                        @if($used > 0)
                                            <button
                                                type="button"
                                                @click="$dispatch('toast', { message: @js("Kategori masih dipakai {$used} formulir/dataset. Pindahkan dulu sebelum menghapus."), tone: 'error' })"
                                                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition hover:border-red-200 hover:bg-red-50 hover:text-[#DC3545]"
                                                aria-label="Hapus"
                                            >
                                                <i data-lucide="trash-2" class="h-4 w-4"></i>
                                            </button>
                                        @else
                                            <button
                                                type="button"
                                                @click="$dispatch('open-confirm', {
                                                    message: @js("Kategori “{$c->name}” akan dihapus."),
                                                    action: @js(route('panel.kategori.destroy', $c->id)),
                                                    method: 'DELETE'
                                                })"
                                                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition hover:border-red-200 hover:bg-red-50 hover:text-[#DC3545]"
                                                aria-label="Hapus"
                                            >
                                                <i data-lucide="trash-2" class="h-4 w-4"></i>
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

    <!-- Add / Edit Modal -->
    <template x-if="modalOpen">
        <div class="fixed inset-0 z-50 grid place-items-end bg-slate-900/50 sm:place-items-center sm:p-6" @click.self="modalOpen = false" @keydown.escape.window="modalOpen = false">
            <div role="dialog" aria-modal="true" class="flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-t-2xl border border-slate-200 bg-white shadow-2xl sm:rounded-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 pt-5 pb-4 sm:px-8 sm:pt-6">
                    <div class="min-w-0">
                        <p class="text-sm text-slate-500">Kategori data</p>
                        <h2 class="mt-1 text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl" x-text="isEdit ? 'Ubah kategori' : 'Tambah kategori'"></h2>
                    </div>
                    <button type="button" @click="modalOpen = false" aria-label="Tutup" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 hover:border-sky-300 hover:text-sky-700">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>

                <form :action="formAction" method="POST" @submit="submit($event)" novalidate class="flex min-h-0 flex-col">
                    @csrf
                    <template x-if="isEdit">
                        <input type="hidden" name="_method" value="PUT">
                    </template>
                    <div class="grid grid-cols-1 gap-5 overflow-y-auto px-5 py-6 sm:grid-cols-2 sm:px-8">
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">Kode <span class="text-[#DC3545]">*</span></span>
                            <input type="text" name="code" x-model="form.code" class="w-full rounded-xl border bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100" :class="err('code') ? 'border-[#DC3545]' : 'border-slate-200'" />
                            <span x-show="err('code')" class="mt-1 block text-xs text-[#DC3545]" x-text="err('code')"></span>
                            <span x-show="!err('code')" class="mt-1 block text-xs text-slate-500">Contoh: KT-MHS-07</span>
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">Nama kategori <span class="text-[#DC3545]">*</span></span>
                            <input type="text" name="name" x-model="form.name" class="w-full rounded-xl border bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100" :class="err('name') ? 'border-[#DC3545]' : 'border-slate-200'" />
                            <span x-show="err('name')" class="mt-1 block text-xs text-[#DC3545]" x-text="err('name')"></span>
                        </label>
                        <label class="block sm:col-span-2">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">Deskripsi</span>
                            <textarea rows="4" name="description" x-model="form.description" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100"></textarea>
                        </label>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:px-8">
                        <button type="button" @click="modalOpen = false" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
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
</div>
@endsection

@push('scripts')
<script>
function categoryManager() {
    const existingCodes = @json($categories->map(fn ($c) => ['id' => $c->id, 'code' => $c->code]));
    return {
        modalOpen: false,
        isEdit: false,
        editId: null,
        formAction: @js(route('panel.kategori.store')),
        tried: false,
        form: { code: '', name: '', description: '' },

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
