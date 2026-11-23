<template x-if="builderOpen">
    <div class="fixed inset-0 z-50 grid place-items-end bg-slate-900/40 backdrop-blur-sm sm:place-items-center sm:p-6" @click.self="builderOpen = false">
        <div role="dialog" aria-modal="true" class="flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-t-2xl bg-white shadow-2xl sm:rounded-2xl">
            <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 pt-5 pb-4 sm:px-8 sm:pt-6">
                <div class="min-w-0">
                    <p class="text-sm text-slate-500">Kelola dataset</p>
                    <h2 class="mt-1 text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">Buat dataset dari data terkumpul</h2>
                    <p class="mt-1 text-sm text-slate-500">Kemas isian formulir menjadi dataset yang siap dilihat dan diunduh.</p>
                </div>
                <button type="button" @click="builderOpen = false" aria-label="Tutup" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 hover:border-sky-300 hover:text-sky-700">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <form action="{{ route('panel.dataset.from-form') }}" method="POST" @submit="submitBuilder($event)" novalidate class="flex min-h-0 flex-col">
                @csrf
                <input type="hidden" name="mode" :value="b.mode">
                <input type="hidden" name="access" :value="b.access">

                <div class="grid grid-cols-1 gap-6 overflow-y-auto px-5 py-6 sm:px-8 lg:grid-cols-2">
                    <template x-if="builderQualityError()">
                        <div class="flex gap-3 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800 lg:col-span-2">
                            <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                            <div x-text="builderQualityError()"></div>
                        </div>
                    </template>

                    <div class="space-y-5">
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">Sumber formulir <span class="text-[#DC3545]">*</span></span>
                            <select name="form_id" x-model="b.formId" @change="onBuilderFormChange()" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                                <template x-for="f in builderForms" :key="f.id">
                                    <option :value="String(f.id)" x-text="`${f.title} (${f.subs.length} isian)`"></option>
                                </template>
                            </select>
                        </label>

                        <div>
                            <p class="text-sm font-semibold text-slate-800">Bentuk dataset</p>
                            <div class="mt-1.5 grid grid-cols-2 gap-2">
                                <button type="button" @click="b.mode = 'rekap'" :class="b.mode === 'rekap' ? 'border-sky-500 bg-sky-100/80' : 'border-slate-200 hover:border-sky-300'" class="rounded-xl border p-3 text-left transition">
                                    <p class="text-sm font-bold text-slate-900">Rekap jumlah</p>
                                    <p class="text-xs text-slate-500">Agregat per nilai, cocok untuk visualisasi</p>
                                </button>
                                <button type="button" @click="b.mode = 'rinci'" :class="b.mode === 'rinci' ? 'border-sky-500 bg-sky-100/80' : 'border-slate-200 hover:border-sky-300'" class="rounded-xl border p-3 text-left transition">
                                    <p class="text-sm font-bold text-slate-900">Data rinci</p>
                                    <p class="text-xs text-slate-500">Satu baris per isian</p>
                                </button>
                            </div>
                        </div>

                        <template x-if="b.mode === 'rekap'">
                            <label class="block">
                                <span class="mb-1.5 block text-sm font-semibold text-slate-800">Kelompokkan berdasarkan <span class="text-[#DC3545]">*</span></span>
                                <select name="group_field_id" x-model="b.groupFieldId" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                                    <template x-for="gf in builderGroupable()" :key="gf.id">
                                        <option :value="gf.id" x-text="gf.label"></option>
                                    </template>
                                </select>
                            </label>
                        </template>

                        <template x-if="b.mode === 'rinci' && builderCurrentForm()">
                            <div>
                                <p class="text-sm font-semibold text-slate-800">Kolom<span class="text-[#DC3545]">*</span></p>
                                <div class="mt-1.5 grid grid-cols-2 gap-2">
                                    <template x-for="col in builderCurrentForm().sourceCols" :key="col.id">
                                        <label class="flex items-center gap-2 text-sm text-slate-600">
                                            <input type="checkbox" name="picked_cols[]" :value="col.id" x-model="b.pickedCols" class="h-4 w-4 accent-sky-600" />
                                            <span x-text="col.label"></span>
                                        </label>
                                    </template>
                                </div>
                                <span x-show="bTried && !b.pickedCols.length" class="mt-1 block text-xs text-[#DC3545]">Pilih minimal satu kolom.</span>
                            </div>
                        </template>

                        <label class="block">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">Judul dataset <span class="text-[#DC3545]">*</span></span>
                            <input type="text" name="title" x-model="b.title" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                            <span x-show="bTried && !b.title.trim()" class="mt-1 block text-xs text-[#DC3545]">Judul wajib diisi.</span>
                        </label>

                        <label class="block">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">Deskripsi</span>
                            <textarea rows="2" name="description" x-model="b.description" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100"></textarea>
                        </label>

                        <div class="grid grid-cols-2 gap-3">
                            <label class="block">
                                <span class="mb-1.5 block text-sm font-semibold text-slate-800">Kategori <span class="text-[#DC3545]">*</span></span>
                                <select name="category_id" x-model="b.categoryId" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                                    <option value="">Pilih…</option>
                                    @foreach($categories as $c)
                                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="block">
                                <span class="mb-1.5 block text-sm font-semibold text-slate-800">Tahun <span class="text-[#DC3545]">*</span></span>
                                <input type="text" name="tahun" x-model="b.tahun" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                            </label>
                            <label class="block">
                                <span class="mb-1.5 block text-sm font-semibold text-slate-800">Semester</span>
                                <select name="periode" x-model="b.periode" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                                    <option value="Ganjil">Ganjil</option>
                                    <option value="Genap">Genap</option>
                                </select>
                            </label>
                            <label class="block">
                                <span class="mb-1.5 block text-sm font-semibold text-slate-800">Format unduhan utama</span>
                                <select name="format" x-model="b.format" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                                    <option value="XLSX">XLSX</option>
                                    <option value="CSV">CSV</option>
                                </select>
                            </label>
                        </div>

                        <div>
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">Akses <span class="text-[#DC3545]">*</span></span>
                            <div class="grid grid-cols-2 gap-2">
                                <button type="button" @click="b.access = 'public'" :class="b.access === 'public' ? 'border-sky-500 bg-sky-100/80' : 'border-slate-200'" class="flex items-center justify-center rounded-xl border py-2.5 transition">
                                    <span class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 text-[11px] font-bold text-emerald-700">Publik</span>
                                </button>
                                <button type="button" @click="b.access = 'private'" :class="b.access === 'private' ? 'border-sky-500 bg-sky-100/80' : 'border-slate-200'" class="flex items-center justify-center rounded-xl border py-2.5 transition">
                                    <span class="inline-flex items-center gap-1 rounded-md bg-amber-50 px-2 py-0.5 text-[11px] font-bold text-amber-700">Private</span>
                                </button>
                            </div>
                            <span class="mt-1 block text-xs text-slate-500">Dataset private hanya dapat diakses Wali Data, Admin, dan pemohon yang dikonfirmasi.</span>
                        </div>
                    </div>

                    <!-- Live Preview -->
                    <div class="min-w-0">
                        <p class="mb-2 text-sm font-semibold text-slate-800">
                            Pratinjau (<span x-text="builderPreview().rows.length"></span> baris)
                        </p>
                        <div class="overflow-x-auto rounded-xl border border-slate-200">
                            <table class="w-full">
                                <thead class="bg-surface-strong">
                                    <tr>
                                        <template x-for="col in builderPreview().cols" :key="col">
                                            <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-slate-500 uppercase" x-text="col"></th>
                                        </template>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(row, rIdx) in builderPreview().rows.slice(0, 8)" :key="rIdx">
                                        <tr class="border-t border-slate-100">
                                            <template x-for="(cell, cIdx) in row" :key="cIdx">
                                                <td class="max-w-[180px] truncate px-4 py-3 text-sm text-slate-600" x-text="cell"></td>
                                            </template>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                            <template x-if="builderPreview().rows.length === 0">
                                <p class="py-6 text-center text-sm text-slate-500">Belum ada isian.</p>
                            </template>
                        </div>

                        <template x-if="b.mode === 'rekap' && builderPreview().rows.length > 0">
                            <div class="mt-4 h-48 rounded-xl border border-slate-200 p-3" x-html="builderPreviewChart()"></div>
                        </template>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:px-8">
                    <button type="button" @click="builderOpen = false" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Batal
                    </button>
                    <button type="submit" :disabled="Boolean(builderQualityError())" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white hover:bg-sky-700 disabled:cursor-not-allowed disabled:opacity-50">
                        Buat dataset
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
