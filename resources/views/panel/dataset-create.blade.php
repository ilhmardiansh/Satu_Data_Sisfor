@extends('layouts.panel')

@section('title', 'Buat Dataset - Panel Satu Data UNUSA')

@section('panel_content')
<div x-data="datasetCreateManager(
    @js($readyFormsPayload),
    @js($preselectedFormCode),
    @js(old('form_id', '')),
    @js([
        'mode' => old('mode', ''),
        'title' => old('title', ''),
        'description' => old('description', ''),
        'categoryId' => old('category_id', ''),
        'tahun' => old('tahun', ''),
        'periode' => old('periode', ''),
        'format' => old('format', ''),
        'access' => old('access', ''),
        'publishStatus' => old('publish_status', ''),
        'groupFieldId' => old('group_field_id', ''),
        'aggFunc' => old('agg_func', ''),
        'aggFieldId' => old('agg_field_id', ''),
        'pickedCols' => old('picked_cols', []),
        'colAliases' => old('col_aliases', []),
        'maskedCols' => old('masked_cols', []),
    ])
)">
    <x-panel-head
        scene="dataset"
        title="Buat Dataset"
        desc="Susun dataset dari formulir yang telah ditutup dan selesai diproses di Pre-processing."
    >
        <a href="{{ route('panel.dataset') }}"
           class="inline-flex min-h-[42px] items-center justify-center gap-2 rounded-xl border border-white/25 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/20">
            <i data-lucide="arrow-left" class="h-4 w-4" aria-hidden="true"></i>
            Kembali ke Kelola Dataset
        </a>
    </x-panel-head>

    @if($errors->any())
        <div class="mb-5 rounded-2xl border border-red-200 bg-[#FEF2F2] px-5 py-4 text-sm text-[#B91C1C]" role="alert">
            <p class="font-bold">Periksa kembali isian dataset:</p>
            <ul class="mt-1 list-disc pl-5 text-xs">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(empty($readyFormsPayload))
        <div class="wd-bento border-dashed border-slate-300 px-6 py-14 text-center">
            <span class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-[#F0F9FF] text-[#0069A8]">
                <i data-lucide="table-2" class="h-6 w-6" aria-hidden="true"></i>
            </span>
            <p class="mt-4 text-base font-bold text-slate-900">Belum ada formulir yang siap dijadikan dataset</p>
            <p class="mx-auto mt-1 max-w-md text-sm text-[#475569]">
                Tutup formulir terlebih dahulu, lalu jalankan finalisasi di Pre-processing Data.
            </p>
            <div class="mt-5 flex flex-wrap justify-center gap-2.5">
                <a href="{{ route('panel.preprocessing') }}"
                   class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl bg-[#0069A8] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#005187]">
                    Buka Pre-processing Data
                </a>
                <a href="{{ route('panel.formulir') }}"
                   class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Kelola Formulir
                </a>
            </div>
        </div>
    @else
        <form action="{{ route('panel.dataset.from-form') }}"
              method="POST"
              @submit="submitBuilder($event)"
              novalidate
              class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[minmax(0,1fr)_390px]">
            @csrf
            <input type="hidden" name="mode" :value="b.mode">
            <input type="hidden" name="access" :value="b.access">
            <input type="hidden" name="publish_status" :value="b.publishStatus">

            <!-- Kolom Kiri: Langkah Formulir Pembuatan Dataset -->
            <div class="space-y-5 min-w-0">
                <!-- 1. Pilih Sumber Formulir (Tanpa Dropdown) -->
                <section class="wd-bento p-5 sm:p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">1. Pilih Formulir</h2>
                            <p class="mt-0.5 text-xs text-[#475569]">Hanya menampilkan formulir yang sudah ditutup dan lolos Pre-processing.</p>
                        </div>
                        <span class="rounded-lg bg-[#ECFDF5] px-2.5 py-1 text-xs font-bold tabular-nums text-[#047857]"
                              x-text="`${readyForms.length} siap`"></span>
                    </div>

                    <template x-if="readyForms.length > 4">
                        <label class="mt-4 flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-[#F8FAFC] px-3 focus-within:border-[#0069A8] focus-within:bg-white">
                            <i data-lucide="search" class="h-4 w-4 text-[#475569]" aria-hidden="true"></i>
                            <input type="text"
                                   x-model="formSearch"
                                   placeholder="Cari kode atau judul formulir..."
                                   class="min-w-0 flex-1 bg-transparent text-sm text-slate-900 outline-none placeholder:text-slate-400" />
                        </label>
                    </template>

                    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2" role="radiogroup" aria-label="Pilih formulir sumber">
                        <template x-for="f in filteredReadyForms()" :key="f.id">
                            <label class="flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition"
                                   :class="String(b.formId) === String(f.id)
                                        ? 'border-[#0069A8] bg-[#F0F9FF] ring-2 ring-[#0069A8]/15'
                                        : 'border-slate-200 bg-white hover:border-[#0069A8]/50'">
                                <input type="radio"
                                       name="form_id"
                                       :value="String(f.id)"
                                       x-model="b.formId"
                                       @change="onBuilderFormChange()"
                                       class="mt-1 h-4 w-4 shrink-0 accent-[#0069A8]" />
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <span class="rounded bg-white px-2 py-0.5 text-xs font-bold text-[#0069A8] ring-1 ring-slate-200/80" x-text="f.code"></span>
                                        <span class="rounded bg-[#ECFDF5] px-2 py-0.5 text-xs font-bold tabular-nums text-[#047857]" x-text="`${f.validCount} baris valid`"></span>
                                    </div>
                                    <p class="mt-2 text-sm font-bold leading-snug text-slate-900" x-text="f.title"></p>
                                    <p class="mt-1 text-xs text-[#475569]" x-text="`${f.tahun} · ${f.periode}`"></p>
                                </div>
                            </label>
                        </template>
                    </div>

                    <template x-if="filteredReadyForms().length === 0">
                        <p class="mt-4 rounded-xl border border-dashed border-slate-200 py-6 text-center text-sm text-[#475569]">
                            Formulir tidak ditemukan.
                        </p>
                    </template>
                </section>

                <!-- 2. Bentuk & Kolom Dataset -->
                <section class="wd-bento space-y-5 p-5 sm:p-6">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">2. Susunan Data</h2>
                        <p class="mt-0.5 text-xs text-[#475569]">Pilih ringkasan hitungan atau tabel baris lengkap.</p>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <button type="button"
                                @click="b.mode = 'rekap'"
                                :disabled="builderGroupable().length === 0"
                                :class="b.mode === 'rekap' ? 'border-[#0069A8] bg-[#F0F9FF] ring-2 ring-[#0069A8]/15' : 'border-slate-200 bg-white hover:border-[#0069A8]/50'"
                                class="min-h-[68px] rounded-xl border p-4 text-left transition disabled:cursor-not-allowed disabled:opacity-50">
                            <p class="text-sm font-bold text-slate-900">Rekap Agregat</p>
                            <p class="mt-0.5 text-xs text-[#475569]">Jumlah, total, atau rata-rata per grup</p>
                        </button>
                        <button type="button"
                                @click="b.mode = 'rinci'"
                                :class="b.mode === 'rinci' ? 'border-[#0069A8] bg-[#F0F9FF] ring-2 ring-[#0069A8]/15' : 'border-slate-200 bg-white hover:border-[#0069A8]/50'"
                                class="min-h-[68px] rounded-xl border p-4 text-left transition">
                            <p class="text-sm font-bold text-slate-900">Tabel Rinci</p>
                            <p class="mt-0.5 text-xs text-[#475569]">Semua baris valid dengan pilihan kolom</p>
                        </button>
                    </div>

                    <template x-if="b.mode === 'rekap'">
                        <div class="space-y-4 rounded-xl border border-slate-200 bg-[#F8FAFC] p-4">
                            <div>
                                <p class="mb-2 text-xs font-bold text-slate-700">Kelompokkan berdasarkan <span class="text-[#B91C1C]">*</span></p>
                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                    <template x-for="gf in builderGroupable()" :key="gf.id">
                                        <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border bg-white px-3 py-2.5 text-sm transition"
                                               :class="b.groupFieldId === gf.id ? 'border-[#0069A8] font-semibold text-[#0069A8]' : 'border-slate-200 text-slate-800'">
                                            <input type="radio"
                                                   name="group_field_id"
                                                   :value="gf.id"
                                                   x-model="b.groupFieldId"
                                                   class="h-4 w-4 accent-[#0069A8]" />
                                            <span class="truncate" x-text="gf.label"></span>
                                        </label>
                                    </template>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <label class="block">
                                    <span class="mb-1.5 block text-xs font-bold text-slate-700">Perhitungan</span>
                                    <select name="agg_func" x-model="b.aggFunc" class="min-h-[42px] w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 outline-none focus:border-[#0069A8]">
                                        <option value="count">Jumlah baris (COUNT)</option>
                                        <template x-if="builderNumericFields().length > 0">
                                            <option value="sum">Total nilai (SUM)</option>
                                        </template>
                                        <template x-if="builderNumericFields().length > 0">
                                            <option value="avg">Rata-rata (AVG)</option>
                                        </template>
                                        <template x-if="builderNumericFields().length > 0">
                                            <option value="min">Nilai minimum (MIN)</option>
                                        </template>
                                        <template x-if="builderNumericFields().length > 0">
                                            <option value="max">Nilai maksimum (MAX)</option>
                                        </template>
                                    </select>
                                </label>
                                <template x-if="b.aggFunc !== 'count'">
                                    <label class="block">
                                        <span class="mb-1.5 block text-xs font-bold text-slate-700">Kolom angka <span class="text-[#B91C1C]">*</span></span>
                                        <select name="agg_field_id" x-model="b.aggFieldId" class="min-h-[42px] w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 outline-none focus:border-[#0069A8]">
                                            <template x-for="nf in builderNumericFields()" :key="nf.id">
                                                <option :value="nf.id" x-text="nf.label"></option>
                                            </template>
                                        </select>
                                    </label>
                                </template>
                            </div>
                        </div>
                    </template>

                    <template x-if="b.mode === 'rinci' && builderCurrentForm()">
                        <div class="rounded-xl border border-slate-200 bg-[#F8FAFC] p-4">
                            <p class="mb-2.5 text-xs font-bold text-slate-800">Pilih kolom, nama tampilan, dan samarkan jika sensitif <span class="text-[#B91C1C]">*</span></p>
                            <div class="space-y-2">
                                <template x-for="col in builderCurrentForm().sourceCols" :key="col.id">
                                    <div class="grid grid-cols-[auto_1fr_auto] items-center gap-2.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs">
                                        <label class="flex items-center">
                                            <input type="checkbox" name="picked_cols[]" :value="col.id" x-model="b.pickedCols" class="h-4 w-4 accent-[#0069A8]" />
                                        </label>
                                        <input type="text"
                                               :name="`col_aliases[${col.id}]`"
                                               x-model="b.colAliases[col.id]"
                                               :placeholder="col.label"
                                               aria-label="Nama tampilan kolom"
                                               class="min-w-0 rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs text-slate-800 outline-none focus:border-[#0069A8]" />
                                        <label class="flex cursor-pointer items-center gap-1.5 font-semibold text-[#475569]">
                                            <input type="checkbox" name="masked_cols[]" :value="col.id" x-model="b.maskedCols" class="h-4 w-4 accent-[#182654]" />
                                            <span>Samarkan</span>
                                        </label>
                                    </div>
                                </template>
                            </div>
                            <span x-show="bTried && !b.pickedCols.length" class="mt-2 block text-xs font-semibold text-[#B91C1C]">Pilih minimal satu kolom.</span>
                        </div>
                    </template>
                </section>

                <!-- 3. Detail & Akses Dataset -->
                <section class="wd-bento space-y-4 p-5 sm:p-6">
                    <h2 class="text-base font-bold text-slate-900">3. Informasi Dataset</h2>

                    <label class="block">
                        <span class="mb-1.5 block text-sm font-semibold text-slate-800">Judul dataset <span class="text-[#B91C1C]">*</span></span>
                        <input type="text" name="title" x-model="b.title" class="min-h-[44px] w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-[#0069A8] focus:ring-4 focus:ring-sky-100" />
                        <span x-show="bTried && !b.title.trim()" class="mt-1 block text-xs text-[#B91C1C]">Judul wajib diisi.</span>
                    </label>

                    <label class="block">
                        <span class="mb-1.5 block text-sm font-semibold text-slate-800">Deskripsi</span>
                        <textarea rows="2" name="description" x-model="b.description" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-[#0069A8] focus:ring-4 focus:ring-sky-100"></textarea>
                    </label>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">Kategori <span class="text-[#B91C1C]">*</span></span>
                            <select name="category_id" x-model="b.categoryId" class="min-h-[44px] w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-[#0069A8]">
                                <option value="">Pilih...</option>
                                @foreach($categories as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                            <span x-show="bTried && !b.categoryId" class="mt-1 block text-xs text-[#B91C1C]">Kategori wajib dipilih.</span>
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">Tahun <span class="text-[#B91C1C]">*</span></span>
                            <input type="text" name="tahun" x-model="b.tahun" class="min-h-[44px] w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm tabular-nums text-slate-900 outline-none focus:border-[#0069A8]" />
                            <span x-show="bTried && !/^\d{4}$/.test(b.tahun)" class="mt-1 block text-xs text-[#B91C1C]">Tahun harus 4 digit.</span>
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">Semester</span>
                            <select name="periode" x-model="b.periode" class="min-h-[44px] w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-[#0069A8]">
                                <option value="Ganjil">Ganjil</option>
                                <option value="Genap">Genap</option>
                            </select>
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">Format unduhan</span>
                            <select name="format" x-model="b.format" class="min-h-[44px] w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-[#0069A8]">
                                <option value="XLSX">XLSX</option>
                                <option value="CSV">CSV</option>
                            </select>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 gap-4 pt-1 sm:grid-cols-2">
                        <div>
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">Status publikasi</span>
                            <div class="grid grid-cols-2 gap-2">
                                <button type="button" @click="b.publishStatus = 'terpublikasi'" :class="b.publishStatus === 'terpublikasi' ? 'border-[#0069A8] bg-[#F0F9FF] text-[#0069A8]' : 'border-slate-200 text-slate-700'" class="min-h-[42px] rounded-xl border py-2 text-xs font-bold transition">Terpublikasi</button>
                                <button type="button" @click="b.publishStatus = 'draft'" :class="b.publishStatus === 'draft' ? 'border-[#182654] bg-slate-100 text-slate-900' : 'border-slate-200 text-slate-700'" class="min-h-[42px] rounded-xl border py-2 text-xs font-bold transition">Draft</button>
                            </div>
                        </div>
                        <div>
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">Tipe akses</span>
                            <div class="grid grid-cols-2 gap-2">
                                <button type="button" @click="b.access = 'public'" :class="b.access === 'public' ? 'border-emerald-500 bg-[#ECFDF5] text-[#047857]' : 'border-slate-200 text-slate-700'" class="min-h-[42px] rounded-xl border py-2 text-xs font-bold transition">Publik</button>
                                <button type="button" @click="b.access = 'private'" :class="b.access === 'private' ? 'border-amber-500 bg-[#FFFBEB] text-[#92400E]' : 'border-slate-200 text-slate-700'" class="min-h-[42px] rounded-xl border py-2 text-xs font-bold transition">Private</button>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Kolom Kanan: Pratinjau Hasil & Simpan -->
            <aside class="wd-bento space-y-4 p-5 lg:sticky lg:top-24">
                <div class="flex items-center justify-between gap-2">
                    <h2 class="text-sm font-bold text-slate-900">Pratinjau Hasil</h2>
                    <span class="rounded-md bg-[#F0F9FF] px-2.5 py-0.5 text-xs font-bold tabular-nums text-[#0069A8]"
                          x-text="`${builderPreview().rows.length} baris`"></span>
                </div>

                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="w-full">
                        <thead class="bg-[#F8FAFC]">
                            <tr>
                                <template x-for="col in builderPreview().cols" :key="col">
                                    <th class="px-3.5 py-2.5 text-left text-xs font-bold whitespace-nowrap text-[#475569]" x-text="col"></th>
                                </template>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(row, rIdx) in builderPreview().rows.slice(0, 8)" :key="rIdx">
                                <tr class="border-t border-slate-100">
                                    <template x-for="(cell, cIdx) in row" :key="cIdx">
                                        <td class="max-w-[160px] truncate px-3.5 py-2.5 text-xs tabular-nums text-slate-700" x-text="cell"></td>
                                    </template>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    <template x-if="builderPreview().rows.length === 0">
                        <p class="py-8 text-center text-xs text-[#475569]">Belum ada baris untuk ditampilkan.</p>
                    </template>
                </div>

                <template x-if="b.mode === 'rekap' && builderPreview().rows.length > 0">
                    <div class="h-44 rounded-xl border border-slate-200 bg-[#F8FAFC] p-3" x-html="builderPreviewChart()"></div>
                </template>

                <template x-if="builderQualityError()">
                    <p class="rounded-xl border border-amber-200 bg-[#FFFBEB] px-3.5 py-2.5 text-xs font-medium text-[#92400E]" x-text="builderQualityError()"></p>
                </template>

                <div class="space-y-2 border-t border-slate-100 pt-4">
                    <button type="submit"
                            :disabled="Boolean(builderQualityError())"
                            class="inline-flex min-h-[44px] w-full items-center justify-center rounded-xl bg-[#0069A8] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#005187] disabled:cursor-not-allowed disabled:opacity-45">
                        Simpan Dataset
                    </button>
                    <a href="{{ route('panel.dataset') }}"
                       class="inline-flex min-h-[42px] w-full items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Batal
                    </a>
                </div>
            </aside>
        </form>
    @endif
</div>
@endsection

@push('scripts')
<script>
function datasetCreateManager(readyForms, preselectedCode, oldFormId, oldState) {
    return {
        readyForms,
        formSearch: '',
        bTried: false,
        b: {
            formId: '',
            mode: 'rekap',
            groupFieldId: '',
            aggFunc: 'count',
            aggFieldId: '',
            pickedCols: [],
            colAliases: {},
            maskedCols: [],
            title: '',
            description: '',
            categoryId: '',
            tahun: @js((string) date('Y')),
            periode: 'Ganjil',
            access: 'private',
            publishStatus: 'terpublikasi',
            format: 'XLSX',
        },

        init() {
            if (!this.readyForms.length) return;
            let initial = null;
            if (oldFormId) {
                initial = this.readyForms.find((f) => String(f.id) === String(oldFormId));
            }
            if (!initial && preselectedCode) {
                initial = this.readyForms.find((f) => f.code === preselectedCode);
            }
            initial = initial || this.readyForms[0];
            this.b.formId = String(initial.id);
            this.onBuilderFormChange();

            if (oldState && oldState.title) {
                if (oldState.mode) this.b.mode = oldState.mode;
                this.b.title = oldState.title;
                if (oldState.description) this.b.description = oldState.description;
                if (oldState.categoryId) this.b.categoryId = String(oldState.categoryId);
                if (oldState.tahun) this.b.tahun = String(oldState.tahun);
                if (oldState.periode) this.b.periode = oldState.periode;
                if (oldState.format) this.b.format = oldState.format;
                if (oldState.access) this.b.access = oldState.access;
                if (oldState.publishStatus) this.b.publishStatus = oldState.publishStatus;
                if (oldState.groupFieldId) this.b.groupFieldId = oldState.groupFieldId;
                if (oldState.aggFunc) this.b.aggFunc = oldState.aggFunc;
                if (oldState.aggFieldId) this.b.aggFieldId = oldState.aggFieldId;
                if (Array.isArray(oldState.pickedCols) && oldState.pickedCols.length) {
                    this.b.pickedCols = oldState.pickedCols;
                }
                if (oldState.colAliases && typeof oldState.colAliases === 'object') {
                    this.b.colAliases = oldState.colAliases;
                }
                if (Array.isArray(oldState.maskedCols) && oldState.maskedCols.length) {
                    this.b.maskedCols = oldState.maskedCols;
                }
            }
        },

        filteredReadyForms() {
            const q = this.formSearch.trim().toLowerCase();
            if (!q) return this.readyForms;
            return this.readyForms.filter((f) =>
                `${f.code} ${f.title} ${f.tahun} ${f.periode}`.toLowerCase().includes(q)
            );
        },

        builderCurrentForm() {
            return this.readyForms.find((f) => String(f.id) === String(this.b.formId)) || null;
        },

        builderGroupable() {
            const f = this.builderCurrentForm();
            if (!f) return [];
            return (f.fields || []).filter((x) =>
                ['select', 'radio', 'checkbox', 'text', 'linear_scale', 'rating'].includes(x.type)
            );
        },

        builderNumericFields() {
            const f = this.builderCurrentForm();
            if (!f) return [];
            return (f.fields || []).filter((x) =>
                ['number', 'linear_scale', 'rating'].includes(x.type)
            );
        },

        onBuilderFormChange() {
            const f = this.builderCurrentForm();
            if (!f) return;
            const groupable = this.builderGroupable();
            const numeric = this.builderNumericFields();
            this.b.mode = groupable.length ? 'rekap' : 'rinci';
            this.b.groupFieldId = groupable[0]?.id || '';
            this.b.aggFunc = 'count';
            this.b.aggFieldId = numeric[0]?.id || '';
            this.b.pickedCols = (f.sourceCols || []).filter((c) => c.id !== '_at').map((c) => c.id);
            this.b.colAliases = {};
            this.b.maskedCols = [];
            this.b.title = groupable.length ? `Rekap ${f.title}` : `Dataset ${f.title}`;
            this.b.description = f.desc || '';
            this.b.categoryId = String(f.category_id);
            this.b.tahun = String(f.tahun);
            this.b.periode = f.periode;
        },

        builderQualityError() {
            const f = this.builderCurrentForm();
            if (!f) return 'Pilih formulir sumber terlebih dahulu.';
            if (!f.preprocessed || !f.validCount) return 'Formulir belum memiliki baris valid hasil Pre-processing.';
            if (this.b.mode === 'rekap' && !this.builderGroupable().some((x) => x.id === this.b.groupFieldId)) {
                return 'Pilih kolom pengelompokan yang valid atau gunakan mode Tabel Rinci.';
            }
            if (this.b.mode === 'rekap' && this.b.aggFunc !== 'count' && !this.b.aggFieldId) {
                return 'Pilih kolom angka untuk perhitungan agregasi.';
            }
            return '';
        },

        maskValue(cid, val, idx) {
            if (cid === '_nama') return 'Responden #' + String(idx + 1).padStart(3, '0');
            const s = String(val ?? '').trim();
            if (!s || s === '-') return s;
            if (s.length <= 5) return s.slice(0, 1) + '*'.repeat(Math.max(3, s.length - 1));
            return s.slice(0, 3) + '***' + s.slice(-2);
        },

        builderPreview() {
            const f = this.builderCurrentForm();
            if (!f) return { cols: [], rows: [] };

            if (this.b.mode === 'rekap') {
                const fl = (f.fields || []).find((x) => x.id === this.b.groupFieldId);
                const aggField = (f.fields || []).find((x) => x.id === this.b.aggFieldId);
                const buckets = new Map();

                f.subs.forEach((s) => {
                    const raw = String(s.values?.[this.b.groupFieldId] || '').trim();
                    let keys = [];
                    if (fl?.type === 'checkbox' && raw) {
                        keys = raw.split(',').map((x) => x.trim()).filter(Boolean);
                    }
                    if (!keys.length) keys = [raw || '(kosong)'];

                    const rawMetric = this.b.aggFieldId ? window.SatuDataCharts?.parseNum(s.values?.[this.b.aggFieldId]) : null;
                    keys.forEach((k) => {
                        if (!buckets.has(k)) buckets.set(k, { count: 0, nums: [] });
                        const bkt = buckets.get(k);
                        bkt.count++;
                        if (rawMetric !== null && Number.isFinite(rawMetric)) bkt.nums.push(rawMetric);
                    });
                });

                (fl?.options || []).forEach((o) => {
                    if (!buckets.has(o)) buckets.set(o, { count: 0, nums: [] });
                });

                const rows = [...buckets.entries()].map(([k, bkt]) => {
                    if (this.b.aggFunc === 'count') return [k, bkt.count];
                    if (!bkt.nums.length) return [k, 0];
                    let v = 0;
                    if (this.b.aggFunc === 'sum') v = bkt.nums.reduce((a, c) => a + c, 0);
                    if (this.b.aggFunc === 'avg') v = bkt.nums.reduce((a, c) => a + c, 0) / bkt.nums.length;
                    if (this.b.aggFunc === 'min') v = Math.min(...bkt.nums);
                    if (this.b.aggFunc === 'max') v = Math.max(...bkt.nums);
                    return [k, Math.round(v * 100) / 100];
                });

                const metricLabel = {
                    count: 'Jumlah',
                    sum: `Total ${aggField?.label || 'Nilai'}`,
                    avg: `Rata-rata ${aggField?.label || 'Nilai'}`,
                    min: `Minimum ${aggField?.label || 'Nilai'}`,
                    max: `Maksimum ${aggField?.label || 'Nilai'}`,
                }[this.b.aggFunc] || 'Jumlah';

                return {
                    cols: [fl?.label || 'Kategori', metricLabel],
                    rows,
                };
            }

            const sc = (f.sourceCols || []).filter((c) => this.b.pickedCols.includes(c.id));
            return {
                cols: sc.map((c) => String(this.b.colAliases[c.id] || '').trim() || c.label),
                rows: f.subs.map((s, idx) =>
                    sc.map((c) => {
                        let raw = '';
                        if (c.id === '_nama') raw = s.nama;
                        else if (c.id === '_nim') raw = s.nim;
                        else if (c.id === '_at') raw = s.at;
                        else raw = s.values?.[c.id] ?? '';
                        return this.b.maskedCols.includes(c.id) ? this.maskValue(c.id, raw, idx) : raw;
                    })
                ),
            };
        },

        builderPreviewChart() {
            if (!window.SatuDataCharts) return '';
            const out = this.builderPreview();
            const data = out.rows.map((r) => ({ n: String(r[0]), v: Number(r[1]) || 0 }));
            return window.SatuDataCharts.renderSvg('bar', data, window.SatuDataCharts.palettes.sky, 320, 160);
        },

        submitBuilder(ev) {
            this.bTried = true;
            const out = this.builderPreview();
            if (
                this.builderQualityError() ||
                !this.b.title.trim() ||
                !this.b.categoryId ||
                !/^\d{4}$/.test(this.b.tahun) ||
                !out.cols.length
            ) {
                ev.preventDefault();
            }
        },
    };
}
</script>
@endpush
