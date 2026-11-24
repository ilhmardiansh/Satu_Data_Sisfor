<template x-teleport="body">
    <div x-show="builderOpen" x-cloak class="fixed inset-0 z-[65] grid place-items-end bg-slate-950/55 backdrop-blur-xs sm:place-items-center sm:p-6" @click.self="builderOpen = false" @keydown.escape.window="builderOpen = false">
        <div role="dialog" aria-modal="true" aria-labelledby="dataset-builder-title" class="flex max-h-[92vh] w-full max-w-5xl flex-col overflow-hidden rounded-t-2xl border border-slate-200 bg-white shadow-2xl sm:rounded-2xl">
            <div class="flex items-start justify-between gap-4 border-b border-slate-200 bg-[#F8FAFC] px-5 pt-5 pb-4 sm:px-8 sm:pt-6">
                <div class="min-w-0">
                    <p class="font-mono text-[11px] font-bold tracking-wider text-[#0069A8] uppercase">Susun Dataset dari Data Tervalidasi</p>
                    <h2 id="dataset-builder-title" class="mt-1 text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">Buat dataset dari formulir siap pakai</h2>
                    <p class="mt-1 text-sm text-[#475569]">Hanya formulir yang telah lulus fase Pre-processing beserta baris berstatus valid yang dapat dikemas menjadi dataset.</p>
                </div>
                <button type="button" @click="builderOpen = false" aria-label="Tutup" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 hover:border-[#0069A8] hover:text-[#0069A8]">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <form action="{{ route('panel.dataset.from-form') }}" method="POST" @submit="submitBuilder($event)" novalidate class="flex min-h-0 flex-col">
                @csrf
                <input type="hidden" name="mode" :value="b.mode">
                <input type="hidden" name="access" :value="b.access">
                <input type="hidden" name="publish_status" :value="b.publishStatus">

                <div class="grid grid-cols-1 gap-6 overflow-y-auto px-5 py-6 sm:px-8 lg:grid-cols-2">
                    <template x-if="builderQualityError()">
                        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-amber-200 bg-[#FFFBEB] px-4 py-3 text-sm font-medium text-[#92400E] lg:col-span-2">
                            <span x-text="builderQualityError()"></span>
                            <template x-if="builderCurrentForm()">
                                <a :href="builderCurrentForm().preprocessUrl" class="inline-flex min-h-[36px] items-center rounded-lg bg-[#92400E] px-3 py-1.5 text-xs font-bold text-white hover:bg-amber-900">
                                    Buka Pre-processing Data
                                </a>
                            </template>
                        </div>
                    </template>

                    <div class="space-y-5">
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">Sumber formulir <span class="text-[#B91C1C]">*</span></span>
                            <select name="form_id" x-model="b.formId" @change="onBuilderFormChange()" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-[#0069A8] focus:ring-4 focus:ring-sky-100">
                                <template x-for="f in builderForms" :key="f.id">
                                    <option :value="String(f.id)" x-text="`${f.code} · ${f.title} (${f.validCount} valid / ${f.totalCount} total${f.preprocessed ? '' : ' - Belum Pre-processing'})`"></option>
                                </template>
                            </select>
                        </label>

                        <div>
                            <p class="text-sm font-semibold text-slate-800">Mode penyusunan dataset</p>
                            <div class="mt-1.5 grid grid-cols-2 gap-2">
                                <button type="button" @click="b.mode = 'rekap'" :class="b.mode === 'rekap' ? 'border-[#0069A8] bg-[#F0F9FF]' : 'border-slate-200 hover:border-[#0069A8]'" class="rounded-xl border p-3 text-left transition">
                                    <p class="text-sm font-bold text-slate-900">Rekap Agregat</p>
                                    <p class="text-xs text-[#475569]">Hitung, total, atau rata-rata per grup</p>
                                </button>
                                <button type="button" @click="b.mode = 'rinci'" :class="b.mode === 'rinci' ? 'border-[#0069A8] bg-[#F0F9FF]' : 'border-slate-200 hover:border-[#0069A8]'" class="rounded-xl border p-3 text-left transition">
                                    <p class="text-sm font-bold text-slate-900">Tabel Rinci</p>
                                    <p class="text-xs text-[#475569]">Baris valid dengan alias &amp; masking</p>
                                </button>
                            </div>
                        </div>

                        <template x-if="b.mode === 'rekap'">
                            <div class="space-y-3 rounded-xl border border-slate-200 bg-[#F6F9FC] p-3.5">
                                <label class="block">
                                    <span class="mb-1 block text-xs font-semibold text-slate-700">Kelompokkan berdasarkan <span class="text-[#B91C1C]">*</span></span>
                                    <select name="group_field_id" x-model="b.groupFieldId" class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 outline-none focus:border-[#0069A8]">
                                        <template x-for="gf in builderGroupable()" :key="gf.id">
                                            <option :value="gf.id" x-text="gf.label"></option>
                                        </template>
                                    </select>
                                </label>
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <label class="block">
                                        <span class="mb-1 block text-xs font-semibold text-slate-700">Fungsi agregasi</span>
                                        <select name="agg_func" x-model="b.aggFunc" class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 outline-none focus:border-[#0069A8]">
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
                                            <span class="mb-1 block text-xs font-semibold text-slate-700">Kolom angka target <span class="text-[#B91C1C]">*</span></span>
                                            <select name="agg_field_id" x-model="b.aggFieldId" class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 outline-none focus:border-[#0069A8]">
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
                            <div class="rounded-xl border border-slate-200 bg-[#F6F9FC] p-3.5">
                                <p class="mb-2 text-xs font-bold text-slate-800">Pilih Kolom, Alias Header, &amp; Anonimisasi <span class="text-[#B91C1C]">*</span></p>
                                <div class="max-h-56 space-y-2 overflow-y-auto pr-1">
                                    <template x-for="col in builderCurrentForm().sourceCols" :key="col.id">
                                        <div class="grid grid-cols-[auto_1fr_auto] items-center gap-2 rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-xs">
                                            <label class="flex items-center gap-2 font-semibold text-slate-800">
                                                <input type="checkbox" name="picked_cols[]" :value="col.id" x-model="b.pickedCols" class="h-4 w-4 accent-[#0069A8]" />
                                            </label>
                                            <input type="text"
                                                   :name="`col_aliases[${col.id}]`"
                                                   x-model="b.colAliases[col.id]"
                                                   :placeholder="col.label"
                                                   aria-label="Alias kolom"
                                                   class="min-w-0 rounded border border-slate-200 px-2 py-1 text-xs text-slate-800 outline-none focus:border-[#0069A8]" />
                                            <label class="flex cursor-pointer items-center gap-1 text-[11px] font-semibold text-[#475569]" title="Samarkan nilai sensitif (masking)">
                                                <input type="checkbox" name="masked_cols[]" :value="col.id" x-model="b.maskedCols" class="h-3.5 w-3.5 accent-[#182654]" />
                                                <span>Mask</span>
                                            </label>
                                        </div>
                                    </template>
                                </div>
                                <span x-show="bTried && !b.pickedCols.length" class="mt-1.5 block text-xs font-semibold text-[#B91C1C]">Pilih minimal satu kolom.</span>
                            </div>
                        </template>

                        <label class="block">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">Judul dataset <span class="text-[#B91C1C]">*</span></span>
                            <input type="text" name="title" x-model="b.title" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-[#0069A8] focus:ring-4 focus:ring-sky-100" />
                            <span x-show="bTried && !b.title.trim()" class="mt-1 block text-xs text-[#B91C1C]">Judul wajib diisi.</span>
                        </label>

                        <label class="block">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">Deskripsi</span>
                            <textarea rows="2" name="description" x-model="b.description" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-[#0069A8] focus:ring-4 focus:ring-sky-100"></textarea>
                        </label>

                        <div class="grid grid-cols-2 gap-3">
                            <label class="block">
                                <span class="mb-1.5 block text-sm font-semibold text-slate-800">Kategori <span class="text-[#B91C1C]">*</span></span>
                                <select name="category_id" x-model="b.categoryId" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-[#0069A8]">
                                    <option value="">Pilih...</option>
                                    @foreach($categories as $c)
                                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="block">
                                <span class="mb-1.5 block text-sm font-semibold text-slate-800">Tahun <span class="text-[#B91C1C]">*</span></span>
                                <input type="text" name="tahun" x-model="b.tahun" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-[#0069A8]" />
                            </label>
                            <label class="block">
                                <span class="mb-1.5 block text-sm font-semibold text-slate-800">Semester</span>
                                <select name="periode" x-model="b.periode" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-[#0069A8]">
                                    <option value="Ganjil">Ganjil</option>
                                    <option value="Genap">Genap</option>
                                </select>
                            </label>
                            <label class="block">
                                <span class="mb-1.5 block text-sm font-semibold text-slate-800">Format unduhan</span>
                                <select name="format" x-model="b.format" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-[#0069A8]">
                                    <option value="XLSX">XLSX</option>
                                    <option value="CSV">CSV</option>
                                </select>
                            </label>
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <span class="mb-1.5 block text-sm font-semibold text-slate-800">Status publikasi</span>
                                <div class="grid grid-cols-2 gap-2">
                                    <button type="button" @click="b.publishStatus = 'terpublikasi'" :class="b.publishStatus === 'terpublikasi' ? 'border-[#0069A8] bg-[#F0F9FF] text-[#0069A8]' : 'border-slate-200 text-slate-700'" class="rounded-xl border py-2 text-xs font-bold transition">Terpublikasi</button>
                                    <button type="button" @click="b.publishStatus = 'draft'" :class="b.publishStatus === 'draft' ? 'border-[#182654] bg-slate-100 text-slate-900' : 'border-slate-200 text-slate-700'" class="rounded-xl border py-2 text-xs font-bold transition">Draft</button>
                                </div>
                            </div>
                            <div>
                                <span class="mb-1.5 block text-sm font-semibold text-slate-800">Tipe akses</span>
                                <div class="grid grid-cols-2 gap-2">
                                    <button type="button" @click="b.access = 'public'" :class="b.access === 'public' ? 'border-emerald-500 bg-[#ECFDF5]' : 'border-slate-200'" class="flex items-center justify-center rounded-xl border py-2 transition">
                                        <span class="text-xs font-bold text-[#047857]">Publik</span>
                                    </button>
                                    <button type="button" @click="b.access = 'private'" :class="b.access === 'private' ? 'border-amber-500 bg-[#FFFBEB]' : 'border-slate-200'" class="flex items-center justify-center rounded-xl border py-2 transition">
                                        <span class="text-xs font-bold text-[#92400E]">Private</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Live Preview -->
                    <div class="min-w-0">
                        <p class="mb-2 text-sm font-semibold text-slate-800">
                            Pratinjau Hasil Dataset (<span x-text="builderPreview().rows.length"></span> baris)
                        </p>
                        <div class="overflow-x-auto rounded-xl border border-slate-200">
                            <table class="w-full">
                                <thead class="bg-[#F6F9FC]">
                                    <tr>
                                        <template x-for="col in builderPreview().cols" :key="col">
                                            <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-[#475569] uppercase" x-text="col"></th>
                                        </template>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(row, rIdx) in builderPreview().rows.slice(0, 8)" :key="rIdx">
                                        <tr class="border-t border-slate-100">
                                            <template x-for="(cell, cIdx) in row" :key="cIdx">
                                                <td class="max-w-[180px] truncate px-4 py-3 text-sm tabular-nums text-slate-700" x-text="cell"></td>
                                            </template>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                            <template x-if="builderPreview().rows.length === 0">
                                <p class="py-8 text-center text-sm text-[#475569]">Belum ada baris valid dari fase Pre-processing.</p>
                            </template>
                        </div>

                        <template x-if="b.mode === 'rekap' && builderPreview().rows.length > 0">
                            <div class="mt-4 h-48 rounded-xl border border-slate-200 p-3" x-html="builderPreviewChart()"></div>
                        </template>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:px-8">
                    <button type="button" @click="builderOpen = false" class="inline-flex min-h-[42px] items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Batal
                    </button>
                    <button type="submit" :disabled="Boolean(builderQualityError())" class="inline-flex min-h-[42px] items-center justify-center gap-2 rounded-xl bg-[#0069A8] px-5 py-2 text-sm font-semibold text-white hover:bg-[#182654] disabled:cursor-not-allowed disabled:opacity-45">
                        Simpan Dataset
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
