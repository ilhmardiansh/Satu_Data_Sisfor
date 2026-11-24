@extends('layouts.panel')

@section('title', 'Instrumen Formulir - Panel Satu Data UNUSA')

@section('panel_content')
@php
    $isAdmin = auth()->user()->isAdmin();
    $defaultDeadline = now()->addDays(14)->format('Y-m-d\TH:i');
@endphp

<div x-data="formBuilderManager()">
    <x-panel-head
        title="Instrumen Formulir"
        desc="Rancang formulir pengumpulan data seperti Google Forms, tentukan tenggat waktu, lalu salin tautan formulir untuk dibagikan kepada Produsen Data."
    >
        <button type="button" @click="openNew()" class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl bg-[#0069A8] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#182654] motion-safe:active:scale-[0.97]">
            <i data-lucide="plus" class="h-4 w-4" aria-hidden="true"></i>
            Buat formulir baru
        </button>
    </x-panel-head>

    <div class="mb-4 flex w-fit flex-wrap gap-1 rounded-xl border border-slate-200 bg-white p-1 text-sm">
        @foreach(['all' => 'Semua', 'open' => 'Dibuka', 'closed' => 'Ditutup'] as $k => $label)
            <a href="{{ route('panel.formulir', ['status' => $k]) }}"
               class="inline-flex min-h-[38px] items-center rounded-lg px-3.5 py-1.5 font-semibold transition {{ $status === $k ? 'bg-[#0069A8] text-white' : 'text-slate-600 hover:text-slate-900' }}">
                {{ $label }} <span class="ml-1 opacity-80">({{ $counts[$k] ?? 0 }})</span>
            </a>
        @endforeach
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-surface">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px]">
                <thead class="bg-surface-strong">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-[#475569] uppercase">Formulir</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-[#475569] uppercase">Sasaran &amp; Tenggat</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-[#475569] uppercase">Status Form</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-[#475569] uppercase">Pipeline Pre-processing</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-[#475569] uppercase">Respons</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold tracking-wide whitespace-nowrap text-[#475569] uppercase">Aksi</th>
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
                                'batasi_satu_respon' => (bool) $f->batasi_satu_respon,
                                'deadline' => $f->deadline?->format('Y-m-d\TH:i') ?? $defaultDeadline,
                                'fields' => array_map(fn ($fl) => [
                                    'id' => $fl['id'],
                                    'label' => $fl['label'],
                                    'help' => $fl['help'] ?? '',
                                    'type' => $fl['type'],
                                    'required' => (bool) ($fl['required'] ?? false),
                                    'opt' => isset($fl['options']) ? implode(', ', $fl['options']) : '',
                                ], $f->fields ?? []),
                            ];
                            $gformUrl = route('formulir.show', $f->code);
                        @endphp
                        <tr class="border-t border-slate-200/70 hover:bg-white">
                            <td class="px-4 py-3 text-sm text-slate-600">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded bg-[#F0F9FF] px-1.5 py-0.5 font-mono text-[11px] font-bold text-[#0069A8]">{{ $f->code }}</span>
                                    @if($f->batasi_satu_respon)
                                        <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[11px] font-semibold text-slate-700">1x/akun</span>
                                    @endif
                                </div>
                                <p class="mt-1 font-bold text-slate-900">{{ $f->title }}</p>
                                <p class="text-xs text-[#475569]">
                                    {{ $f->category?->name }} · {{ $f->tahun }} · {{ $f->periode }} · {{ count($f->fields ?? []) }} pertanyaan
                                </p>
                            </td>
                            <td class="px-4 py-3 text-sm whitespace-nowrap text-slate-700">
                                <p class="font-semibold text-slate-900">{{ $f->target }}</p>
                                <p class="text-xs text-[#475569]">{{ $f->deadlineFormatted() }}</p>
                                <p class="text-xs text-[#475569]">{{ $f->timeLeftLabel() }}</p>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                @if($f->isOpen())
                                    <span class="inline-flex rounded-md bg-[#ECFDF5] px-2 py-0.5 text-[11px] font-bold text-[#047857]">Dibuka</span>
                                @else
                                    <span class="inline-flex rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-700">Ditutup</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm">
                                @if($f->status_preprocessing === 'siap')
                                    <span class="inline-flex items-center gap-1 rounded-md bg-[#ECFDF5] px-2 py-0.5 text-[11px] font-bold text-[#047857]">Siap Dataset</span>
                                @elseif($f->status_preprocessing === 'proses' || $f->submissions_count > 0)
                                    <span class="inline-flex items-center gap-1 rounded-md bg-[#FFFBEB] px-2 py-0.5 text-[11px] font-bold text-[#92400E]">Perlu Pre-processing</span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-600">Belum ada respons</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm tabular-nums text-slate-700">
                                <p class="font-bold text-slate-900">{{ $f->submissions_count }}</p>
                                @if($f->submissions_count > 0)
                                    <p class="text-[11px] text-[#475569]">
                                        <span class="text-[#047857]">{{ $f->valid_submissions_count }} valid</span> · <span class="text-[#92400E]">{{ $f->raw_submissions_count }} mentah</span>
                                    </p>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">
                                <div class="flex justify-end gap-1.5">
                                    <button type="button"
                                            @click="copyLink(@js($gformUrl))"
                                            class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-[#0069A8] hover:text-[#0069A8]"
                                            title="Salin tautan GForm"
                                            aria-label="Salin tautan formulir">
                                        <i data-lucide="link-2" class="h-4 w-4"></i>
                                    </button>
                                    <a href="{{ $gformUrl }}"
                                       class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-[#0069A8] hover:text-[#0069A8]"
                                       title="Buka halaman formulir"
                                       aria-label="Buka halaman formulir">
                                        <i data-lucide="external-link" class="h-4 w-4"></i>
                                    </a>
                                    <form method="POST" action="{{ route('panel.formulir.toggle', $f->code) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-[#0069A8] hover:text-[#0069A8]"
                                                title="{{ $f->status_aktif ? 'Tutup formulir' : 'Buka formulir' }}"
                                                aria-label="{{ $f->status_aktif ? 'Tutup formulir' : 'Buka formulir' }}">
                                            <i data-lucide="{{ $f->status_aktif ? 'lock' : 'unlock' }}" class="h-4 w-4"></i>
                                        </button>
                                    </form>
                                    <a href="{{ route('panel.preprocessing', ['form' => $f->code]) }}"
                                       class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-[#0069A8] hover:text-[#0069A8]"
                                       title="Buka Pre-processing Data"
                                       aria-label="Buka Pre-processing Data">
                                        <i data-lucide="sparkles" class="h-4 w-4"></i>
                                    </a>
                                    <button type="button"
                                            @click="openEdit(@js($editPayload))"
                                            class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-[#0069A8] hover:text-[#0069A8]"
                                            title="Ubah formulir"
                                            aria-label="Ubah formulir">
                                        <i data-lucide="pencil" class="h-4 w-4"></i>
                                    </button>
                                    @if($isAdmin)
                                        <button type="button"
                                                @click="$dispatch('open-confirm', {
                                                    message: @js("Formulir “{$f->title}” beserta {$f->submissions_count} isiannya akan dihapus."),
                                                    action: @js(route('panel.formulir.destroy', $f->code)),
                                                    method: 'DELETE'
                                                })"
                                                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-red-200 hover:bg-red-50 hover:text-[#B91C1C]"
                                                title="Hapus formulir"
                                                aria-label="Hapus formulir">
                                            <i data-lucide="trash-2" class="h-4 w-4"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <p class="text-sm font-bold text-slate-800">Belum ada instrumen formulir</p>
                                <p class="mt-1 text-xs text-[#475569]">Klik "Buat formulir baru" untuk merancang instrumen pengumpulan data pertama Anda.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $forms->links('components.pagination') }}
    </div>

    <!-- Form Builder Modal (GForm Model) -->
    <template x-if="modalOpen">
        <div class="fixed inset-0 z-50 grid place-items-end bg-slate-900/50 sm:place-items-center sm:p-6" @click.self="modalOpen = false" @keydown.escape.window="modalOpen = false">
            <div role="dialog" aria-modal="true" aria-labelledby="form-builder-title" class="flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-t-2xl border border-slate-200 bg-white shadow-2xl sm:rounded-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 pt-5 pb-4 sm:px-8 sm:pt-6">
                    <div class="min-w-0">
                        <p class="text-xs font-bold tracking-wider text-[#0069A8] uppercase">Instrumen Pengumpulan Data</p>
                        <h2 id="form-builder-title" class="mt-1 text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl" x-text="isEdit ? 'Ubah instrumen formulir' : 'Buat instrumen formulir baru'"></h2>
                        <p class="mt-1 text-sm text-[#475569]">Semua respons dari formulir ini masuk sebagai data mentah ke fase Pre-processing sebelum menjadi Dataset.</p>
                    </div>
                    <button type="button" @click="modalOpen = false" aria-label="Tutup" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 hover:border-[#0069A8] hover:text-[#0069A8]">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
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
                                    <span class="mb-1.5 block text-sm font-semibold text-slate-800">Judul formulir <span class="text-[#B91C1C]">*</span></span>
                                    <input type="text" name="title" x-model="v.title" placeholder="Contoh: Pendataan Prestasi Mahasiswa 2026" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#0069A8] focus:ring-4 focus:ring-sky-100" />
                                    <span x-show="err('title')" class="mt-1 block text-xs text-[#B91C1C]" x-text="err('title')"></span>
                                </label>
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block">
                                    <span class="mb-1.5 block text-sm font-semibold text-slate-800">Deskripsi / petunjuk pengisian</span>
                                    <textarea rows="2" name="description" x-model="v.description" placeholder="Jelaskan tujuan formulir dan dokumen yang perlu disiapkan pengisi..." class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#0069A8] focus:ring-4 focus:ring-sky-100"></textarea>
                                </label>
                            </div>

                            <label class="block">
                                <span class="mb-1.5 block text-sm font-semibold text-slate-800">Kategori data <span class="text-[#B91C1C]">*</span></span>
                                <select name="category_id" x-model="v.category_id" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-[#0069A8] focus:ring-4 focus:ring-sky-100">
                                    <option value="">Pilih kategori...</option>
                                    @foreach($categories as $c)
                                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                                    @endforeach
                                </select>
                                <span x-show="err('category_id')" class="mt-1 block text-xs text-[#B91C1C]" x-text="err('category_id')"></span>
                            </label>

                            <label class="block">
                                <span class="mb-1.5 block text-sm font-semibold text-slate-800">Sasaran responden <span class="text-[#B91C1C]">*</span></span>
                                <select name="target" x-model="v.target" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-[#0069A8] focus:ring-4 focus:ring-sky-100">
                                    <option value="Mahasiswa">Mahasiswa</option>
                                    <option value="Dosen">Dosen</option>
                                    <option value="Semua">Semua (Mahasiswa &amp; Dosen)</option>
                                </select>
                            </label>

                            <div class="grid grid-cols-2 gap-3">
                                <label class="block">
                                    <span class="mb-1.5 block text-sm font-semibold text-slate-800">Tahun <span class="text-[#B91C1C]">*</span></span>
                                    <input type="text" name="tahun" inputmode="numeric" x-model="v.tahun" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-[#0069A8] focus:ring-4 focus:ring-sky-100" />
                                    <span x-show="err('tahun')" class="mt-1 block text-xs text-[#B91C1C]" x-text="err('tahun')"></span>
                                </label>
                                <label class="block">
                                    <span class="mb-1.5 block text-sm font-semibold text-slate-800">Semester <span class="text-[#B91C1C]">*</span></span>
                                    <select name="periode" x-model="v.periode" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-[#0069A8] focus:ring-4 focus:ring-sky-100">
                                        <option value="Ganjil">Ganjil</option>
                                        <option value="Genap">Genap</option>
                                    </select>
                                </label>
                            </div>

                            <label class="block">
                                <span class="mb-1.5 block text-sm font-semibold text-slate-800">Tenggat waktu <span class="text-[#B91C1C]">*</span></span>
                                <input type="datetime-local" name="deadline" x-model="v.deadline" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-[#0069A8] focus:ring-4 focus:ring-sky-100" />
                                <span x-show="err('deadline')" class="mt-1 block text-xs text-[#B91C1C]" x-text="err('deadline')"></span>
                            </label>

                            <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-[#F6F9FC] px-4 py-3 sm:col-span-2">
                                <input type="checkbox" name="batasi_satu_respon" value="1" x-model="v.batasi_satu_respon" class="h-4 w-4 accent-[#0069A8]" />
                                <span class="text-sm font-semibold text-slate-800">Batasi 1 respons per akun (gaya Google Forms)</span>
                            </label>
                        </div>

                        <div>
                            <div class="mb-3 flex items-center justify-between">
                                <h3 class="font-bold text-slate-900">Daftar Pertanyaan</h3>
                                <span x-show="err('fields')" class="text-xs text-[#B91C1C]" x-text="err('fields')"></span>
                            </div>

                            <div class="space-y-3">
                                <template x-for="(fl, i) in fields" :key="fl.id">
                                    <div class="rounded-xl border border-slate-200 border-l-4 border-l-[#0069A8] bg-[#F6F9FC] p-4">
                                        <div class="flex flex-wrap items-start gap-3">
                                            <span class="mt-2.5 grid h-6 w-6 place-items-center rounded-full bg-white text-xs font-bold text-slate-700 ring-1 ring-slate-200" x-text="i + 1"></span>

                                            <div class="min-w-[200px] flex-1 space-y-2">
                                                <input type="text" x-model="fl.label" placeholder="Pertanyaan" aria-label="Label pertanyaan" class="w-full rounded-xl border bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-[#0069A8] focus:ring-4 focus:ring-sky-100" :class="err(fl.id) ? 'border-[#B91C1C]' : 'border-slate-200'" />
                                                <input type="text" x-model="fl.help" placeholder="Teks bantuan / petunjuk pengisian (opsional)" aria-label="Teks bantuan pertanyaan" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs text-slate-700 outline-none focus:border-[#0069A8]" />
                                                <span x-show="err(fl.id)" class="block text-xs text-[#B91C1C]" x-text="err(fl.id)"></span>
                                            </div>

                                            <select x-model="fl.type" aria-label="Jenis isian" class="h-[42px] rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-800 outline-none focus:border-[#0069A8] focus:ring-4 focus:ring-sky-100">
                                                <option value="text">Teks singkat</option>
                                                <option value="textarea">Paragraf</option>
                                                <option value="number">Angka</option>
                                                <option value="date">Tanggal</option>
                                                <option value="radio">Pilihan ganda (Radio)</option>
                                                <option value="checkbox">Kotak centang (Checkbox)</option>
                                                <option value="select">Dropdown</option>
                                                <option value="file">Lampiran bukti</option>
                                            </select>

                                            <label class="mt-2.5 flex items-center gap-2 text-sm font-medium text-slate-700">
                                                <input type="checkbox" x-model="fl.required" class="h-4 w-4 accent-[#0069A8]" />
                                                Wajib
                                            </label>

                                            <div class="flex gap-1">
                                                <button type="button" :disabled="i === 0" @click="moveField(i, -1)" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 disabled:opacity-40" aria-label="Naikkan" title="Naikkan">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="18 15 12 9 6 15"/></svg>
                                                </button>
                                                <button type="button" :disabled="i === fields.length - 1" @click="moveField(i, 1)" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 disabled:opacity-40" aria-label="Turunkan" title="Turunkan">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                                                </button>
                                                <button type="button" @click="duplicateField(i)" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 hover:border-[#0069A8] hover:text-[#0069A8]" aria-label="Duplikat pertanyaan" title="Duplikat">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                                                </button>
                                                <button type="button" @click="fields.splice(i, 1)" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 hover:border-red-200 hover:bg-red-50 hover:text-[#B91C1C]" aria-label="Hapus pertanyaan" title="Hapus">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                                </button>
                                            </div>
                                        </div>

                                        <template x-if="['select', 'radio', 'checkbox'].includes(fl.type)">
                                            <input type="text" x-model="fl.opt" placeholder="Opsi pilihan dipisahkan koma. Contoh: KIP Kuliah, Prestasi, Yayasan" class="mt-3 w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-[#0069A8] focus:ring-4 focus:ring-sky-100" />
                                        </template>
                                    </div>
                                </template>
                            </div>

                            <button type="button" @click="addField()" class="mt-3 inline-flex min-h-[42px] w-full items-center justify-center gap-2 rounded-xl border border-dashed border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                Tambah pertanyaan
                            </button>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:px-8">
                        <button type="button" @click="modalOpen = false" class="inline-flex min-h-[42px] items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                            Batal
                        </button>
                        <button type="submit" class="inline-flex min-h-[42px] items-center justify-center gap-2 rounded-xl bg-[#0069A8] px-5 py-2 text-sm font-semibold text-white transition hover:bg-[#182654]">
                            <span x-text="isEdit ? 'Simpan perubahan' : 'Publikasikan formulir'"></span>
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
            batasi_satu_respon: false,
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

        copyLink(url) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(() => {
                    this.$dispatch('toast', { message: 'Tautan formulir disalin ke papan klip.', tone: 'success' });
                });
            } else {
                this.$dispatch('toast', { message: 'Buka tautan: ' + url, tone: 'info' });
            }
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
                batasi_satu_respon: false,
                deadline: @js($defaultDeadline),
            };
            this.fields = [{ id: this.uid(), label: '', help: '', type: 'text', required: true, opt: '' }];
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
                batasi_satu_respon: !!data.batasi_satu_respon,
                deadline: data.deadline,
            };
            this.fields = (data.fields || []).map((f) => ({ help: '', ...f }));
            this.tried = false;
            this.modalOpen = true;
        },

        addField() {
            this.fields.push({ id: this.uid(), label: '', help: '', type: 'text', required: false, opt: '' });
        },

        duplicateField(i) {
            const src = this.fields[i];
            if (!src) return;
            this.fields.splice(i + 1, 0, {
                ...src,
                id: this.uid(),
                label: src.label ? `${src.label} (salinan)` : '',
            });
        },

        moveField(i, delta) {
            const target = i + delta;
            if (target < 0 || target >= this.fields.length) return;
            const copy = [...this.fields];
            [copy[i], copy[target]] = [copy[target], copy[i]];
            this.fields = copy;
        },

        serializedFields() {
            const hasOpts = (t) => ['select', 'radio', 'checkbox'].includes(t);
            return JSON.stringify(this.fields.map((f) => ({
                id: f.id,
                label: String(f.label || '').trim(),
                help: String(f.help || '').trim(),
                type: f.type,
                required: !!f.required,
                options: hasOpts(f.type)
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
                } else if (['select', 'radio', 'checkbox'].includes(f.type) && !String(f.opt || '').split(',').some((o) => o.trim())) {
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
