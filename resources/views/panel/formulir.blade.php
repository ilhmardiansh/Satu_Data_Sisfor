@extends('layouts.panel')

@section('title', 'Kelola Formulir - Panel Satu Data UNUSA')

@section('panel_content')
@php
    $isAdmin = auth()->user()->isAdmin();
    $defaultDeadline = now()->addDays(14)->format('Y-m-d\TH:i');
@endphp

<div x-data="formBuilderManager()">
    <x-panel-head
        title="Kelola Formulir"
        desc="Buat formulir pengumpulan data dan tetapkan tenggat waktu pengisiannya."
    >
        <button type="button" @click="openNew()" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-700 motion-safe:active:scale-[0.97]">
            <i data-lucide="plus" class="h-4 w-4"></i>
            Buat formulir
        </button>
    </x-panel-head>

    <div class="mb-4 flex w-fit rounded-xl border border-slate-200 bg-white p-1 text-sm">
        @foreach(['all' => 'Semua', 'open' => 'Dibuka', 'closed' => 'Ditutup'] as $k => $label)
            <a href="{{ route('panel.formulir', ['status' => $k]) }}"
               class="rounded-lg px-3.5 py-1.5 font-medium transition {{ $status === $k ? 'bg-sky-600 text-white' : 'text-slate-500 hover:text-slate-800' }}">
                {{ $label }} <span class="opacity-70">({{ $counts[$k] ?? 0 }})</span>
            </a>
        @endforeach
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-surface">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[820px]">
                <thead class="bg-surface-strong">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-slate-500 uppercase">Formulir</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-slate-500 uppercase">Diisi oleh</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-slate-500 uppercase">Tenggat</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-slate-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-slate-500 uppercase">Isian</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold tracking-wide whitespace-nowrap text-slate-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($forms as $f)
                        @php
                            $editPayload = [
                                'code' => $f->code,
                                'action' => route('panel.formulir.update', $f->code),
                                'title' => $f->title,
                                'description' => $f->description ?? '',
                                'category_id' => $f->category_id,
                                'tahun' => (string) $f->tahun,
                                'periode' => $f->periode,
                                'target' => $f->target,
                                'deadline' => $f->deadline?->format('Y-m-d\TH:i') ?? $defaultDeadline,
                                'fields' => array_map(fn ($fl) => [
                                    'id' => $fl['id'],
                                    'label' => $fl['label'],
                                    'type' => $fl['type'],
                                    'required' => (bool) ($fl['required'] ?? false),
                                    'opt' => isset($fl['options']) ? implode(', ', $fl['options']) : '',
                                ], $f->fields ?? []),
                            ];
                        @endphp
                        <tr class="border-t border-slate-200/70 hover:bg-white">
                            <td class="px-4 py-3 text-sm text-slate-600">
                                <p class="font-semibold text-slate-900">{{ $f->title }}</p>
                                <p class="text-xs text-slate-400">
                                    {{ $f->category?->name }} · {{ $f->tahun }} · {{ $f->periode }} · {{ count($f->fields ?? []) }} pertanyaan
                                </p>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $f->target }}</td>
                            <td class="px-4 py-3 text-sm whitespace-nowrap text-slate-600">
                                {{ $f->deadlineFormatted() }}
                                <p class="text-xs text-slate-400">{{ $f->timeLeftLabel() }}</p>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">
                                @if($f->isOpen())
                                    <span class="inline-flex rounded-md bg-emerald-50 px-2 py-0.5 text-[11px] font-bold text-emerald-700">Dibuka</span>
                                @else
                                    <span class="inline-flex rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-500">Ditutup otomatis</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm font-semibold text-slate-600">{{ $f->submissions_count }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">
                                <div class="flex justify-end gap-1.5">
                                    <a href="{{ route('panel.data', ['form' => $f->code]) }}"
                                       class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-sky-300 hover:text-sky-700"
                                       title="Lihat data terkumpul"
                                       aria-label="Lihat data terkumpul">
                                        <i data-lucide="database" class="h-4 w-4"></i>
                                    </a>
                                    <button type="button"
                                            @click="openEdit(@js($editPayload))"
                                            class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-sky-300 hover:text-sky-700"
                                            title="Ubah"
                                            aria-label="Ubah">
                                        <i data-lucide="pencil" class="h-4 w-4"></i>
                                    </button>
                                    @if($isAdmin)
                                        <button type="button"
                                                @click="$dispatch('open-confirm', {
                                                    message: @js("Formulir “{$f->title}” beserta {$f->submissions_count} isiannya akan dihapus."),
                                                    action: @js(route('panel.formulir.destroy', $f->code)),
                                                    method: 'DELETE'
                                                })"
                                                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition hover:border-red-200 hover:bg-red-50 hover:text-[#DC3545]"
                                                title="Hapus"
                                                aria-label="Hapus">
                                            <i data-lucide="trash-2" class="h-4 w-4"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">
                                Belum ada formulir pada filter ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $forms->links('components.pagination') }}
    </div>

    <!-- Form Builder Modal -->
    <template x-if="modalOpen">
        <div class="fixed inset-0 z-50 grid place-items-end bg-slate-900/40 backdrop-blur-sm sm:place-items-center sm:p-6" @click.self="modalOpen = false">
            <div role="dialog" aria-modal="true" class="flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-t-2xl bg-white shadow-2xl sm:rounded-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 pt-5 pb-4 sm:px-8 sm:pt-6">
                    <div class="min-w-0">
                        <p class="text-sm text-slate-500">Kelola formulir</p>
                        <h2 class="mt-1 text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl" x-text="isEdit ? 'Ubah formulir' : 'Buat formulir baru'"></h2>
                        <p class="mt-1 text-sm text-slate-500">Formulir otomatis disembunyikan dan menolak pengiriman setelah tenggat waktu.</p>
                    </div>
                    <button type="button" @click="modalOpen = false" aria-label="Tutup" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 hover:border-sky-300 hover:text-sky-700">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>

                <form :action="formAction" method="POST" @submit="submitBuilder($event)" novalidate class="flex min-h-0 flex-col">
                    @csrf
                    <template x-if="isEdit">
                        <input type="hidden" name="_method" value="PUT">
                    </template>
                    <input type="hidden" name="fields_json" :value="serializedFields()">

                    <div class="space-y-6 overflow-y-auto px-5 py-6 sm:px-8">
                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label class="block">
                                    <span class="mb-1.5 block text-sm font-semibold text-slate-800">Judul formulir <span class="text-[#DC3545]">*</span></span>
                                    <input type="text" name="title" x-model="v.title" placeholder="Contoh: Pelaporan Prestasi Mahasiswa 2026" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                                    <span x-show="err('title')" class="mt-1 block text-xs text-[#DC3545]" x-text="err('title')"></span>
                                </label>
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block">
                                    <span class="mb-1.5 block text-sm font-semibold text-slate-800">Deskripsi</span>
                                    <textarea rows="2" name="description" x-model="v.description" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-sky-400 focus:ring-4 focus:ring-sky-100"></textarea>
                                </label>
                            </div>

                            <label class="block">
                                <span class="mb-1.5 block text-sm font-semibold text-slate-800">Kategori data <span class="text-[#DC3545]">*</span></span>
                                <select name="category_id" x-model="v.category_id" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                                    <option value="">Pilih kategori…</option>
                                    @foreach($categories as $c)
                                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                                    @endforeach
                                </select>
                                <span x-show="err('category_id')" class="mt-1 block text-xs text-[#DC3545]" x-text="err('category_id')"></span>
                            </label>

                            <label class="block">
                                <span class="mb-1.5 block text-sm font-semibold text-slate-800">Diisi oleh <span class="text-[#DC3545]">*</span></span>
                                <select name="target" x-model="v.target" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                                    <option value="Mahasiswa">Mahasiswa</option>
                                    <option value="Dosen">Dosen</option>
                                    <option value="Semua">Semua</option>
                                </select>
                            </label>

                            <div class="grid grid-cols-2 gap-3">
                                <label class="block">
                                    <span class="mb-1.5 block text-sm font-semibold text-slate-800">Tahun <span class="text-[#DC3545]">*</span></span>
                                    <input type="text" name="tahun" inputmode="numeric" x-model="v.tahun" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                                    <span x-show="err('tahun')" class="mt-1 block text-xs text-[#DC3545]" x-text="err('tahun')"></span>
                                </label>
                                <label class="block">
                                    <span class="mb-1.5 block text-sm font-semibold text-slate-800">Semester <span class="text-[#DC3545]">*</span></span>
                                    <select name="periode" x-model="v.periode" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                                        <option value="Ganjil">Ganjil</option>
                                        <option value="Genap">Genap</option>
                                    </select>
                                </label>
                            </div>

                            <label class="block">
                                <span class="mb-1.5 block text-sm font-semibold text-slate-800">Tenggat waktu <span class="text-[#DC3545]">*</span></span>
                                <input type="datetime-local" name="deadline" x-model="v.deadline" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                                <span x-show="err('deadline')" class="mt-1 block text-xs text-[#DC3545]" x-text="err('deadline')"></span>
                            </label>
                        </div>

                        <div>
                            <div class="mb-3 flex items-center justify-between">
                                <h3 class="font-bold text-slate-900">Pertanyaan</h3>
                                <span x-show="err('fields')" class="text-xs text-[#DC3545]" x-text="err('fields')"></span>
                            </div>

                            <div class="space-y-3">
                                <template x-for="(fl, i) in fields" :key="fl.id">
                                    <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                                        <div class="flex flex-wrap items-start gap-3">
                                            <span class="mt-2.5 grid h-6 w-6 place-items-center rounded-full bg-white text-xs font-bold text-slate-500 ring-1 ring-slate-200" x-text="i + 1"></span>

                                            <div class="min-w-[200px] flex-1">
                                                <input type="text" x-model="fl.label" placeholder="Label pertanyaan" aria-label="Label pertanyaan" class="w-full rounded-xl border bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100" :class="err(fl.id) ? 'border-[#DC3545]' : 'border-slate-200'" />
                                                <span x-show="err(fl.id)" class="mt-1 block text-xs text-[#DC3545]" x-text="err(fl.id)"></span>
                                            </div>

                                            <select x-model="fl.type" aria-label="Jenis isian" class="h-[42px] rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                                                <option value="text">Teks singkat</option>
                                                <option value="textarea">Paragraf</option>
                                                <option value="number">Angka</option>
                                                <option value="date">Tanggal</option>
                                                <option value="select">Pilihan</option>
                                                <option value="file">Lampiran bukti</option>
                                            </select>

                                            <label class="mt-2.5 flex items-center gap-2 text-sm text-slate-600">
                                                <input type="checkbox" x-model="fl.required" class="h-4 w-4 accent-sky-600" />
                                                Wajib
                                            </label>

                                            <div class="flex gap-1">
                                                <button type="button" :disabled="i === 0" @click="moveField(i, -1)" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 disabled:opacity-40" aria-label="Naikkan">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="18 15 12 9 6 15"/></svg>
                                                </button>
                                                <button type="button" :disabled="i === fields.length - 1" @click="moveField(i, 1)" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 disabled:opacity-40" aria-label="Turunkan">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                                                </button>
                                                <button type="button" @click="fields.splice(i, 1)" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 hover:border-red-200 hover:bg-red-50 hover:text-[#DC3545]" aria-label="Hapus pertanyaan">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                                </button>
                                            </div>
                                        </div>

                                        <template x-if="fl.type === 'select'">
                                            <input type="text" x-model="fl.opt" placeholder="Pilihan, pisahkan dengan koma. Contoh: Regional, Nasional, Internasional" class="mt-3 w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                                        </template>
                                    </div>
                                </template>
                            </div>

                            <button type="button" @click="addField()" class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-xl border border-dashed border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                Tambah pertanyaan
                            </button>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:px-8">
                        <button type="button" @click="modalOpen = false" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                            Batal
                        </button>
                        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-700">
                            <span x-text="isEdit ? 'Simpan perubahan' : 'Buat formulir'"></span>
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
function formBuilderManager() {
    return {
        modalOpen: false,
        isEdit: false,
        formAction: @js(route('panel.formulir.store')),
        tried: false,
        v: {
            title: '',
            description: '',
            category_id: '',
            tahun: @js((string) date('Y')),
            periode: 'Ganjil',
            target: 'Mahasiswa',
            deadline: @js($defaultDeadline),
        },
        fields: [],

        init() {
            window.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') this.modalOpen = false;
            });
        },

        uid() {
            return 'q_' + Math.random().toString(36).slice(2, 7);
        },

        openNew() {
            this.isEdit = false;
            this.formAction = @js(route('panel.formulir.store'));
            this.v = {
                title: '',
                description: '',
                category_id: '',
                tahun: @js((string) date('Y')),
                periode: 'Ganjil',
                target: 'Mahasiswa',
                deadline: @js($defaultDeadline),
            };
            this.fields = [{ id: this.uid(), label: '', type: 'text', required: true, opt: '' }];
            this.tried = false;
            this.modalOpen = true;
        },

        openEdit(data) {
            this.isEdit = true;
            this.formAction = data.action;
            this.v = {
                title: data.title,
                description: data.description,
                category_id: String(data.category_id),
                tahun: String(data.tahun),
                periode: data.periode,
                target: data.target,
                deadline: data.deadline,
            };
            this.fields = (data.fields || []).map((f) => ({ ...f }));
            this.tried = false;
            this.modalOpen = true;
        },

        addField() {
            this.fields.push({ id: this.uid(), label: '', type: 'text', required: false, opt: '' });
        },

        moveField(i, delta) {
            const target = i + delta;
            if (target < 0 || target >= this.fields.length) return;
            const copy = [...this.fields];
            [copy[i], copy[target]] = [copy[target], copy[i]];
            this.fields = copy;
        },

        serializedFields() {
            return JSON.stringify(this.fields.map((f) => ({
                id: f.id,
                label: String(f.label || '').trim(),
                type: f.type,
                required: !!f.required,
                options: f.type === 'select'
                    ? String(f.opt || '').split(',').map((o) => o.trim()).filter(Boolean)
                    : undefined,
            })));
        },

        errors() {
            const e = {};
            if (!String(this.v.title).trim()) e.title = 'Kolom ini wajib diisi.';
            if (!String(this.v.category_id).trim()) e.category_id = 'Kolom ini wajib diisi.';
            if (!/^\d{4}$/.test(String(this.v.tahun).trim())) e.tahun = 'Tahun 4 digit.';
            if (!String(this.v.deadline).trim()) {
                e.deadline = 'Kolom ini wajib diisi.';
            } else if (!this.isEdit && new Date(this.v.deadline).getTime() <= Date.now()) {
                e.deadline = 'Tenggat harus setelah waktu sekarang.';
            }
            if (!this.fields.length) e.fields = 'Tambahkan minimal satu pertanyaan.';
            this.fields.forEach((f) => {
                if (!String(f.label).trim()) {
                    e[f.id] = 'Label pertanyaan wajib diisi.';
                } else if (f.type === 'select' && !String(f.opt || '').split(',').some((o) => o.trim())) {
                    e[f.id] = 'Isi minimal satu pilihan.';
                }
            });
            return e;
        },

        err(k) {
            if (!this.tried) return '';
            return this.errors()[k] || '';
        },

        submitBuilder(ev) {
            this.tried = true;
            if (Object.keys(this.errors()).length > 0) {
                ev.preventDefault();
            }
        },
    };
}
</script>
@endpush
