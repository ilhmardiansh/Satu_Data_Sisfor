@extends('layouts.panel')

@section('title', 'Data Terkumpul - Panel Satu Data UNUSA')

@section('panel_content')
<div x-data="collectedDataManager()">
    <x-panel-head
        title="Data Terkumpul"
        desc="Data yang dikirim Produsen Data, tersimpan terpusat dan dikelompokkan berdasarkan kategori dan tahun."
    >
        @if($activeForm)
            <a href="{{ route('panel.data.csv', $activeForm->code) }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                <i data-lucide="download" class="h-4 w-4"></i>
                Unduh CSV
            </a>
            <button
                type="button"
                @click="openBuilder({{ $activeForm->id }})"
                {{ ($problemCount > 0 || $totalSubmissionsCount === 0) ? 'disabled' : '' }}
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-700 disabled:cursor-not-allowed disabled:opacity-50"
            >
                <i data-lucide="table-2" class="h-4 w-4"></i>
                Jadikan dataset
            </button>
        @endif
    </x-panel-head>

    <form method="GET" action="{{ route('panel.data') }}" class="mb-4 flex flex-wrap items-center gap-2 rounded-2xl border border-slate-200 bg-surface p-3">
        <select name="kategori" onchange="this.form.submit()" aria-label="Kategori" class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
            <option value="all">Semua kategori</option>
            @foreach($filterCategories as $c)
                <option value="{{ $c->code }}" {{ $kategori === $c->code ? 'selected' : '' }}>{{ $c->name }}</option>
            @endforeach
        </select>

        <select name="tahun" onchange="this.form.submit()" aria-label="Tahun" class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
            <option value="all">Semua tahun</option>
            @foreach($years as $y)
                <option value="{{ $y }}" {{ $tahun === (string) $y ? 'selected' : '' }}>{{ $y }}</option>
            @endforeach
        </select>

        <select name="form" onchange="this.form.submit()" aria-label="Formulir" class="h-10 min-w-0 flex-1 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
            @foreach($filteredForms as $fx)
                <option value="{{ $fx->code }}" {{ $activeForm?->id === $fx->id ? 'selected' : '' }}>
                    {{ $fx->title }} ({{ $fx->submissions_count }})
                </option>
            @endforeach
        </select>

        <label class="flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-3">
            <i data-lucide="search" class="h-4 w-4 text-slate-400"></i>
            <input type="text" name="q" value="{{ $q }}" placeholder="Cari isian…" class="w-40 bg-transparent text-sm outline-none" />
        </label>

        @if($onlyIssues)
            <input type="hidden" name="only_issues" value="1">
        @endif
    </form>

    @if($activeForm)
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl border p-4 {{ $problemCount > 0 ? 'border-red-200 bg-red-50' : 'border-emerald-200 bg-emerald-50' }}">
            <div class="min-w-0">
                <h2 class="font-bold text-slate-900">
                    @if($problemCount > 0)
                        {{ $problemCount }} dari {{ $totalSubmissionsCount }} isian perlu dibersihkan
                    @else
                        {{ $totalSubmissionsCount }} isian · tidak ada masalah terdeteksi
                    @endif
                </h2>
                <p class="mt-1 text-sm text-slate-600">
                    Pemeriksaan data ganda, kolom wajib kosong, nilai tidak valid, dan format. Bersihkan sebelum membuat dataset.
                </p>
            </div>
            <form method="GET" action="{{ route('panel.data') }}">
                <input type="hidden" name="kategori" value="{{ $kategori }}">
                <input type="hidden" name="tahun" value="{{ $tahun }}">
                <input type="hidden" name="form" value="{{ $activeForm->code }}">
                <input type="hidden" name="q" value="{{ $q }}">
                <label class="flex cursor-pointer items-center gap-2 text-sm font-semibold text-slate-800">
                    <input type="checkbox" name="only_issues" value="1" {{ $onlyIssues ? 'checked' : '' }} onchange="this.form.submit()" class="h-4 w-4 accent-sky-600" />
                    Hanya data bermasalah
                </label>
            </form>
        </div>

        @if($submissionsPaginator->total() > 0)
            @php $cols = $activeForm->sourceCols(); @endphp
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-surface">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-surface-strong">
                            <tr>
                                @foreach($cols as $col)
                                    <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-slate-500 uppercase">{{ $col['label'] }}</th>
                                @endforeach
                                <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-slate-500 uppercase">Kualitas & tindakan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($submissionsPaginator as $sub)
                                @php
                                    $issues = $qualityIssues[$sub->id] ?? [];
                                    $cells = [
                                        $sub->user?->name ?? '-',
                                        $sub->user?->nim ?? '-',
                                        $sub->submittedFormatted(),
                                    ];
                                    foreach ($activeForm->fields ?? [] as $fl) {
                                        $cells[] = (string) ($sub->values[$fl['id']] ?? '');
                                    }
                                    $cleanPayload = [
                                        'code' => $sub->code,
                                        'action' => route('panel.data.submissions.update', $sub->code),
                                        'formTitle' => $activeForm->title,
                                        'fields' => $activeForm->fields ?? [],
                                        'values' => $sub->values ?? [],
                                    ];
                                @endphp
                                <tr class="border-t border-slate-100 {{ ! empty($issues) ? 'bg-red-50' : 'hover:bg-white' }}">
                                    @foreach($cells as $cIdx => $val)
                                        <td class="max-w-[240px] truncate px-4 py-3 text-sm {{ $cIdx === 0 ? 'font-semibold text-slate-900' : 'text-slate-600' }}" title="{{ $val }}">
                                            {{ $val !== '' ? $val : '—' }}
                                        </td>
                                    @endforeach
                                    <td class="px-4 py-3 text-right text-sm text-slate-600">
                                        <div class="min-w-52 text-left">
                                            @if(! empty($issues))
                                                <ul class="mb-2 list-inside list-disc text-xs text-red-700">
                                                    @foreach($issues as $issue)
                                                        <li>{{ $issue }}</li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                <p class="mb-2 text-xs text-emerald-700">Tidak ada masalah terdeteksi</p>
                                            @endif
                                            @if($sub->cleaned_at)
                                                <p class="mb-2 text-xs text-slate-500">
                                                    Diperbaiki {{ $sub->cleaner?->name ?? 'pengelola' }} · {{ $sub->cleaned_at->locale('id')->translatedFormat('j M Y, H.i') }}
                                                </p>
                                            @endif
                                        </div>
                                        <button
                                            type="button"
                                            @click="openClean(@js($cleanPayload))"
                                            class="mr-2 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-sky-300 hover:text-sky-700"
                                            aria-label="Perbaiki isian"
                                        >
                                            <i data-lucide="pencil" class="h-4 w-4"></i>
                                        </button>
                                        <button
                                            type="button"
                                            @click="$dispatch('open-confirm', {
                                                message: 'Isian ini akan dihapus dari basis data.',
                                                action: @js(route('panel.data.submissions.destroy', $sub->code)),
                                                method: 'DELETE'
                                            })"
                                            class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition hover:border-red-200 hover:bg-red-50 hover:text-[#DC3545]"
                                            aria-label="Hapus isian"
                                        >
                                            <i data-lucide="trash-2" class="h-4 w-4"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $submissionsPaginator->links('components.pagination') }}
            </div>
        @else
            <div class="rounded-2xl border border-dashed border-slate-300 bg-surface px-6 py-14 text-center">
                <span class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-slate-100 text-slate-400">
                    <i data-lucide="database" class="h-6 w-6"></i>
                </span>
                <p class="mt-4 font-bold text-slate-800">
                    {{ $onlyIssues ? 'Tidak ada isian bermasalah yang cocok' : ($q !== '' ? 'Isian tidak ditemukan' : 'Belum ada isian untuk formulir ini') }}
                </p>
            </div>
        @endif
    @else
        <div class="rounded-2xl border border-dashed border-slate-300 bg-surface px-6 py-14 text-center">
            <span class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-slate-100 text-slate-400">
                <i data-lucide="database" class="h-6 w-6"></i>
            </span>
            <p class="mt-4 font-bold text-slate-800">Tidak ada formulir pada kategori/tahun ini</p>
        </div>
    @endif

    <!-- Clean Submission Modal -->
    <template x-if="cleanOpen && cleanTarget">
        <div class="fixed inset-0 z-50 grid place-items-end bg-slate-900/40 backdrop-blur-sm sm:place-items-center sm:p-6" @click.self="cleanOpen = false">
            <div role="dialog" aria-modal="true" class="flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-t-2xl bg-white shadow-2xl sm:rounded-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 pt-5 pb-4 sm:px-8 sm:pt-6">
                    <div class="min-w-0">
                        <h2 class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">Perbaiki data isian</h2>
                        <p class="mt-1 text-sm text-slate-500" x-text="cleanTarget.formTitle"></p>
                    </div>
                    <button type="button" @click="cleanOpen = false" aria-label="Tutup" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 hover:border-sky-300 hover:text-sky-700">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>

                <form :action="cleanTarget.action" method="POST" @submit="submitClean($event)" novalidate class="flex min-h-0 flex-col">
                    @csrf
                    <input type="hidden" name="_method" value="PUT">

                    <div class="space-y-4 overflow-y-auto px-5 py-6 sm:px-8">
                        <div class="flex gap-3 rounded-xl bg-sky-100/80 px-4 py-3 text-sm text-sky-800">
                            <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                            <div>
                                Perubahan memperbarui data sumber, bukan dataset yang sudah dibuat. Data ganda tetap perlu ditinjau dan dihapus secara manual.
                            </div>
                        </div>

                        <button type="button" @click="normalizeCleanValues()" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                            Seragamkan spasi &amp; pilihan
                        </button>

                        <template x-for="f in cleanTarget.fields" :key="f.id">
                            <label class="block">
                                <span class="mb-1.5 block text-sm font-semibold text-slate-800">
                                    <span x-text="f.label"></span>
                                    <template x-if="f.required">
                                        <span class="ml-0.5 text-[#DC3545]">*</span>
                                    </template>
                                </span>

                                <template x-if="f.type === 'select'">
                                    <select :name="`values[${f.id}]`" x-model="cleanValues[f.id]" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                                        <option value="">Pilih…</option>
                                        <template x-if="cleanValues[f.id] && !(f.options || []).includes(cleanValues[f.id])">
                                            <option :value="cleanValues[f.id]" x-text="`${cleanValues[f.id]} (tidak valid)`"></option>
                                        </template>
                                        <template x-for="o in f.options || []" :key="o">
                                            <option :value="o" x-text="o"></option>
                                        </template>
                                    </select>
                                </template>

                                <template x-if="f.type === 'textarea'">
                                    <textarea rows="3" :name="`values[${f.id}]`" x-model="cleanValues[f.id]" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100"></textarea>
                                </template>

                                <template x-if="f.type !== 'select' && f.type !== 'textarea'">
                                    <input :type="f.type === 'file' ? 'text' : f.type" :name="`values[${f.id}]`" x-model="cleanValues[f.id]" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                                </template>

                                <span x-show="cleanErrors[f.id]" class="mt-1 block text-xs text-[#DC3545]" x-text="cleanErrors[f.id]"></span>
                            </label>
                        </template>
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:px-8">
                        <button type="button" @click="cleanOpen = false" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Batal
                        </button>
                        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white hover:bg-sky-700">
                            Simpan perbaikan
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
function collectedDataManager() {
    return {
        ...datasetBuilderMixin(@json($builderFormsPayload)),
        cleanOpen: false,
        cleanTarget: null,
        cleanValues: {},
        cleanErrors: {},

        init() {
            const reopenCode = @json(session('clean_submission_code'));
            if (reopenCode) {
                const errs = @json($errors->toArray());
                Object.keys(errs).forEach((k) => {
                    this.cleanErrors[k] = errs[k][0];
                });
            }
            window.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    this.cleanOpen = false;
                    this.builderOpen = false;
                }
            });
        },

        openClean(payload) {
            this.cleanTarget = payload;
            this.cleanValues = { ...(payload.values || {}) };
            this.cleanErrors = {};
            this.cleanOpen = true;
        },

        normalizeCleanValues() {
            if (!this.cleanTarget || !window.SatuDataQuality) return;
            const updated = {};
            this.cleanTarget.fields.forEach((f) => {
                updated[f.id] = window.SatuDataQuality.normalizeValue(f, this.cleanValues[f.id] ?? '');
            });
            this.cleanValues = updated;
        },

        submitClean(ev) {
            if (!this.cleanTarget) return;
            this.cleanErrors = {};
            this.cleanTarget.fields.forEach((f) => {
                const val = String(this.cleanValues[f.id] ?? '').trim();
                if (!val && f.required) {
                    this.cleanErrors[f.id] = 'Data wajib masih kosong.';
                } else if (val && f.type === 'number' && !Number.isFinite(Number(val))) {
                    this.cleanErrors[f.id] = 'Nilai harus berupa angka yang valid.';
                } else if (val && f.type === 'select' && !(f.options || []).includes(val)) {
                    this.cleanErrors[f.id] = 'Nilai tidak sesuai pilihan formulir.';
                } else if (val && f.type === 'file' && !/\.(pdf|jpe?g|png)$/i.test(val)) {
                    this.cleanErrors[f.id] = 'Format bukti harus PDF, JPG, atau PNG.';
                }
            });
            if (Object.keys(this.cleanErrors).length > 0) {
                ev.preventDefault();
            }
        },
    };
}
</script>
@endpush
