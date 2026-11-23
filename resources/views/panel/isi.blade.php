@extends('layouts.panel')

@section('title', 'Isi Formulir - Panel Satu Data UNUSA')

@section('panel_content')
@php
    $formsJson = $forms->map(fn ($f) => [
        'code' => $f->code,
        'title' => $f->title,
        'desc' => $f->description ?? '',
        'categoryName' => $f->category?->name ?? '',
        'tahun' => $f->tahun,
        'periode' => $f->periode,
        'deadlineFormatted' => $f->deadlineFormatted(),
        'timeLeft' => $f->timeLeftLabel(),
        'fields' => $f->fields ?? [],
        'submitUrl' => route('panel.isi.submit', $f->code),
    ])->values()->all();
@endphp

<div x-data="panelIsiManager()">
    <x-panel-head
        title="Isi Formulir"
        desc="Formulir pengumpulan data yang sedang dibuka untuk Anda. Formulir yang melewati tenggat waktu tidak ditampilkan."
    />

    @if($forms->isNotEmpty())
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            @foreach($forms as $f)
                @php
                    $mine = (int) ($myCounts[$f->id] ?? 0);
                    $urgent = $f->isUrgent();
                @endphp
                <div class="flex flex-col rounded-2xl border border-slate-200 bg-surface p-5">
                    <div class="flex items-center gap-2 text-xs text-slate-500">
                        <span class="rounded-md bg-sky-100/80 px-2 py-0.5 font-bold text-sky-700">
                            {{ $f->category?->name }}
                        </span>
                        {{ $f->tahun }} · Semester {{ $f->periode }}
                    </div>
                    <h3 class="mt-3 text-lg font-bold text-slate-900">{{ $f->title }}</h3>
                    <p class="mt-1 line-clamp-2 text-sm text-slate-500">{{ $f->description }}</p>

                    <div class="mt-4 flex flex-wrap gap-x-5 gap-y-1 text-[13px] text-slate-500">
                        <span class="flex items-center gap-1.5 font-semibold {{ $urgent ? 'text-[#DC3545]' : 'text-amber-700' }}">
                            <i data-lucide="calendar-clock" class="h-4 w-4"></i>
                            {{ $f->timeLeftLabel() }}
                        </span>
                        <span>{{ count($f->fields ?? []) }} pertanyaan</span>
                        @if($mine > 0)
                            <span class="flex items-center gap-1 font-semibold text-emerald-600">
                                <i data-lucide="check-circle-2" class="h-4 w-4"></i>
                                Sudah dikirim {{ $mine }}×
                            </span>
                        @endif
                    </div>

                    <div class="mt-5 flex justify-end border-t border-slate-100 pt-4">
                        <button
                            type="button"
                            @click="openForm(@js($f->code))"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-700 motion-safe:active:scale-[0.97]"
                        >
                            <i data-lucide="file-plus-2" class="h-4 w-4"></i>
                            {{ $mine > 0 ? 'Kirim lagi' : 'Isi formulir' }}
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="rounded-2xl border border-dashed border-slate-300 bg-surface px-6 py-14 text-center">
            <span class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-slate-100 text-slate-400">
                <i data-lucide="clipboard-list" class="h-6 w-6"></i>
            </span>
            <p class="mt-4 font-bold text-slate-800">Tidak ada formulir yang dibuka</p>
            <p class="mt-1 text-sm text-slate-500">Formulir baru akan muncul di sini saat Wali Data membukanya.</p>
        </div>
    @endif

    <!-- FillForm Modal -->
    <template x-if="modalOpen && selectedForm">
        <div class="fixed inset-0 z-50 grid place-items-end bg-slate-900/40 backdrop-blur-sm sm:place-items-center sm:p-6" @click.self="closeModal()">
            <div role="dialog" aria-modal="true" class="flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-t-2xl bg-white shadow-2xl sm:rounded-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 pt-5 pb-4 sm:px-8 sm:pt-6">
                    <div class="min-w-0">
                        <p class="flex flex-wrap items-center gap-2 text-sm text-slate-500">
                            <span x-text="`${selectedForm.categoryName} · ${selectedForm.tahun} · Semester ${selectedForm.periode}`"></span>
                        </p>
                        <h2 class="mt-1 text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl" x-text="selectedForm.title"></h2>
                        <p class="mt-1 text-sm text-slate-500" x-text="selectedForm.desc"></p>
                        <p class="mt-2 inline-flex items-center gap-1.5 rounded-md bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-700">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 7.5V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h3.5"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h5"/><path d="M17.5 17.5 16 16.3V14"/><circle cx="16" cy="16" r="6"/></svg>
                            <span x-text="`Tenggat ${selectedForm.deadlineFormatted} · ${selectedForm.timeLeft}`"></span>
                        </p>
                    </div>
                    <button type="button" @click="closeModal()" aria-label="Tutup" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 hover:border-sky-300 hover:text-sky-700">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>

                <form :action="selectedForm.submitUrl" method="POST" enctype="multipart/form-data" @submit="validateBeforeSubmit($event)" novalidate class="flex min-h-0 flex-col">
                    @csrf
                    <div class="space-y-5 overflow-y-auto px-5 py-6 sm:px-8">
                        <p class="text-xs text-slate-500">
                            Kolom bertanda <span class="text-[#DC3545]">*</span> wajib diisi.
                        </p>

                        <template x-for="fld in selectedForm.fields" :key="fld.id">
                            <label class="block">
                                <span class="mb-1.5 block text-sm font-semibold text-slate-800">
                                    <span x-text="fld.label"></span>
                                    <template x-if="fld.required">
                                        <span class="ml-0.5 text-[#DC3545]">*</span>
                                    </template>
                                </span>

                                <template x-if="fld.type === 'textarea'">
                                    <textarea rows="3" :name="`values[${fld.id}]`" x-model="values[fld.id]" class="w-full rounded-xl border bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-sky-400 focus:ring-4 focus:ring-sky-100" :class="fieldErr(fld.id) ? 'border-[#DC3545]' : 'border-slate-200'"></textarea>
                                </template>

                                <template x-if="fld.type === 'select'">
                                    <select :name="`values[${fld.id}]`" x-model="values[fld.id]" class="w-full rounded-xl border bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" :class="fieldErr(fld.id) ? 'border-[#DC3545]' : 'border-slate-200'">
                                        <option value="">Pilih…</option>
                                        <template x-for="opt in fld.options || []" :key="opt">
                                            <option :value="opt" x-text="opt"></option>
                                        </template>
                                    </select>
                                </template>

                                <template x-if="fld.type === 'file'">
                                    <div>
                                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-dashed px-4 py-3 text-sm transition hover:border-sky-400 hover:bg-sky-50/50" :class="fieldErr(fld.id) ? 'border-[#DC3545]' : 'border-slate-300'">
                                            <svg class="h-4 w-4 text-sky-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                                            <span :class="fileNames[fld.id] ? 'font-semibold text-slate-800' : 'text-slate-500'" x-text="fileNames[fld.id] || 'Pilih berkas bukti…'"></span>
                                            <input type="file" :name="`files[${fld.id}]`" accept=".pdf,.jpg,.jpeg,.png" class="sr-only" @change="onFileChange(fld.id, $event.target.files[0], $event.target)" />
                                        </label>
                                        <span class="mt-1 block text-xs text-slate-500">PDF, JPG, atau PNG, maksimal 5 MB.</span>
                                    </div>
                                </template>

                                <template x-if="fld.type !== 'textarea' && fld.type !== 'select' && fld.type !== 'file'">
                                    <input :type="fld.type" :name="`values[${fld.id}]`" x-model="values[fld.id]" class="w-full rounded-xl border bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-sky-400 focus:ring-4 focus:ring-sky-100" :class="fieldErr(fld.id) ? 'border-[#DC3545]' : 'border-slate-200'" />
                                </template>

                                <span x-show="fieldErr(fld.id)" class="mt-1 block text-xs text-[#DC3545]" x-text="fieldErr(fld.id)"></span>
                            </label>
                        </template>
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:px-8">
                        <button type="button" @click="closeModal()" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                            Batal
                        </button>
                        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-700">
                            Kirim formulir
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
function panelIsiManager() {
    return {
        forms: @json($formsJson),
        modalOpen: false,
        selectedForm: null,
        values: {},
        fileNames: {},
        fileErrors: {},
        tried: false,

        init() {
            const reopenCode = @json(session('open_form_code'));
            if (reopenCode) {
                this.openForm(reopenCode);
                this.values = @json(old('values', []));
                const errs = @json($errors->toArray());
                Object.keys(errs).forEach((k) => {
                    this.fileErrors[k] = errs[k][0];
                });
                this.tried = true;
            }
            window.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') this.closeModal();
            });
        },

        openForm(code) {
            const f = this.forms.find((x) => x.code === code);
            if (!f) return;
            this.selectedForm = f;
            this.values = {};
            this.fileNames = {};
            this.fileErrors = {};
            this.tried = false;
            this.modalOpen = true;
            document.body.style.overflow = 'hidden';
        },

        closeModal() {
            this.modalOpen = false;
            this.selectedForm = null;
            document.body.style.overflow = '';
        },

        onFileChange(fieldId, file, inputEl) {
            if (!file) {
                this.fileNames[fieldId] = '';
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                this.fileErrors[fieldId] = 'Ukuran berkas maksimal 5 MB.';
                this.fileNames[fieldId] = '';
                inputEl.value = '';
                return;
            }
            if (!/\.(pdf|jpe?g|png)$/i.test(file.name)) {
                this.fileErrors[fieldId] = 'Format berkas harus PDF, JPG, atau PNG.';
                this.fileNames[fieldId] = '';
                inputEl.value = '';
                return;
            }
            delete this.fileErrors[fieldId];
            this.fileNames[fieldId] = file.name;
        },

        fieldErr(fieldId) {
            if (this.fileErrors[fieldId]) return this.fileErrors[fieldId];
            if (!this.tried || !this.selectedForm) return '';
            const fld = this.selectedForm.fields.find((x) => x.id === fieldId);
            if (!fld || !fld.required) return '';
            if (fld.type === 'file') {
                return this.fileNames[fieldId] ? '' : 'Lampiran bukti wajib diunggah.';
            }
            const val = String(this.values[fieldId] ?? '').trim();
            return val ? '' : 'Kolom ini wajib diisi.';
        },

        validateBeforeSubmit(event) {
            this.tried = true;
            if (!this.selectedForm) return;
            const hasError = this.selectedForm.fields.some((f) => Boolean(this.fieldErr(f.id)));
            if (hasError) event.preventDefault();
        },
    };
}
</script>
@endpush
