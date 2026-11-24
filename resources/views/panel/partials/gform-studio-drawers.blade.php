<!-- 1. Slide-over Drawer: Sesuaikan Tampilan UNUSA -->
<template x-if="themeDrawerOpen">
    <div class="fixed inset-0 z-50 flex justify-end bg-slate-950/40 backdrop-blur-[1px]" @click.self="themeDrawerOpen = false">
        <aside class="flex h-full w-full max-w-sm flex-col overflow-hidden border-l border-slate-200 bg-white shadow-2xl">
            <div class="flex items-center justify-between bg-linear-to-r from-[#09122C] to-[#182654] px-5 py-4 text-white">
                <div>
                    <p class="font-mono text-[10px] font-bold tracking-wider text-[#74D4FF] uppercase">Identitas Visual</p>
                    <h2 class="text-base font-extrabold">Sesuaikan Tampilan UNUSA</h2>
                </div>
                <button type="button" @click="themeDrawerOpen = false" class="rounded-lg bg-white/15 px-2.5 py-1 text-xs font-bold hover:bg-white/25">Tutup</button>
            </div>

            <div class="flex-1 space-y-6 overflow-y-auto p-5 text-xs">
                <div>
                    <p class="mb-2.5 font-extrabold text-slate-800">Palet Aksen Resmi</p>
                    <div class="grid grid-cols-5 gap-2">
                        <template x-for="pal in [
                            { key: 'cerulean', name: 'Cerulean', hex: '#0069A8' },
                            { key: 'benhur', name: 'Benhur', hex: '#182654' },
                            { key: 'cyan', name: 'Cyan', hex: '#0284C7' },
                            { key: 'emerald', name: 'Mutu', hex: '#047857' },
                            { key: 'slate', name: 'Formal', hex: '#334155' }
                        ]" :key="pal.key">
                            <button type="button"
                                    @click="settings.theme.palette = pal.key"
                                    :class="settings.theme.palette === pal.key ? 'ring-2 ring-slate-900 ring-offset-2' : ''"
                                    class="flex flex-col items-center gap-1 rounded-xl border border-slate-200 p-2 transition hover:border-slate-400">
                                <span class="h-6 w-6 rounded-full" :style="`background-color: ${pal.hex}`"></span>
                                <span class="text-[10px] font-bold text-slate-700" x-text="pal.name"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <div>
                    <p class="mb-2 font-extrabold text-slate-800">Warna Latar Kanvas</p>
                    <div class="grid grid-cols-2 gap-2">
                        <template x-for="bg in [['soft','Studio Lembut'],['tint','Biru Muda UNUSA'],['paper','Putih Bersih'],['slate','Abu Arsitektural']]" :key="bg[0]">
                            <button type="button"
                                    @click="settings.theme.bg_tone = bg[0]"
                                    :class="settings.theme.bg_tone === bg[0] ? 'border-[#0069A8] bg-[#F0F9FF] text-[#0069A8]' : 'border-slate-200 text-slate-700'"
                                    class="rounded-xl border px-3 py-2 text-left font-bold" x-text="bg[1]"></button>
                        </template>
                    </div>
                </div>

                <div class="space-y-2.5">
                    <p class="font-extrabold text-slate-800">Sampul Header &amp; Gambar Kustom</p>
                    <div class="grid grid-cols-2 gap-2">
                        <template x-for="pr in [['cerulean-grid','Kisi Cerulean'],['benhur-arch','Lengkung Benhur'],['cyan-wave','Gelombang Cyan'],['emerald-mutu','Mutu Zamrud'],['slate-formal','Formal Gelap'],['minimal','Minimal Solid']]" :key="pr[0]">
                            <button type="button"
                                    @click="settings.theme.header_preset = pr[0]; settings.theme.header_image = ''"
                                    :class="(!settings.theme.header_image && settings.theme.header_preset === pr[0]) ? 'border-[#0069A8] ring-2 ring-[#0069A8]/30' : 'border-slate-200'"
                                    class="overflow-hidden rounded-xl border text-left">
                                <div :class="`gf-banner-${pr[0]}`" class="h-8 w-full"></div>
                                <span class="block px-2 py-1 text-[11px] font-semibold text-slate-700" x-text="pr[1]"></span>
                            </button>
                        </template>
                    </div>

                    <div class="space-y-2 rounded-xl border border-dashed border-slate-300 bg-[#F8FAFC] p-3">
                        <label class="flex cursor-pointer items-center justify-between gap-2 font-bold text-[#0069A8]">
                            <span x-text="uploadingTarget === 'header_banner' ? 'Mengunggah foto sampul...' : 'Unggah Foto Sampul Kustom'">Unggah Foto Sampul Kustom</span>
                            <input type="file" accept="image/*" class="sr-only" @change="uploadHeaderImage($event)" />
                        </label>
                        <p x-show="uploadError" x-cloak class="text-[11px] font-semibold text-[#B91C1C]" x-text="uploadError"></p>
                        <template x-if="settings.theme.header_image">
                            <div class="space-y-1.5">
                                <img :src="settings.theme.header_image" alt="Pratinjau sampul" class="h-20 w-full rounded-lg border border-slate-200 object-cover" />
                                <button type="button" @click="settings.theme.header_image = ''" class="text-[11px] font-semibold text-[#B91C1C] hover:underline">Hapus gambar sampul</button>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="space-y-3 border-t border-slate-100 pt-4">
                    <p class="font-extrabold text-slate-800">Tipografi Portal</p>
                    <label class="block">
                        <span class="mb-1 block font-semibold text-slate-700">Keluarga Font</span>
                        <select x-model="settings.theme.font_family" class="h-9 w-full rounded-xl border border-slate-200 bg-white px-2.5 font-semibold">
                            <option value="jakarta">Plus Jakarta Sans (Baku UNUSA)</option>
                            <option value="serif">Serif Formal</option>
                            <option value="mono">JetBrains Mono</option>
                        </select>
                    </label>
                    <div class="grid grid-cols-3 gap-2">
                        <label class="block">
                            <span class="mb-1 block text-[11px] font-semibold text-slate-600">Header (px)</span>
                            <input type="number" min="18" max="32" x-model.number="settings.theme.header_size" class="h-8 w-full rounded-lg border border-slate-200 px-2 text-center font-mono" />
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-[11px] font-semibold text-slate-600">Tanya (px)</span>
                            <input type="number" min="13" max="20" x-model.number="settings.theme.question_size" class="h-8 w-full rounded-lg border border-slate-200 px-2 text-center font-mono" />
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-[11px] font-semibold text-slate-600">Teks (px)</span>
                            <input type="number" min="11" max="16" x-model.number="settings.theme.text_size" class="h-8 w-full rounded-lg border border-slate-200 px-2 text-center font-mono" />
                        </label>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</template>

<!-- 2. Slide-over Drawer: Impor Pertanyaan -->
<template x-if="importDrawerOpen">
    <div class="fixed inset-0 z-50 flex justify-end bg-slate-950/40 backdrop-blur-[1px]" @click.self="importDrawerOpen = false">
        <aside class="flex h-full w-full max-w-md flex-col overflow-hidden border-l border-slate-200 bg-white shadow-2xl">
            <div class="flex items-center justify-between bg-linear-to-r from-[#09122C] to-[#182654] px-5 py-4 text-white">
                <div>
                    <p class="font-mono text-[10px] font-bold tracking-wider text-[#74D4FF] uppercase">Re-use Instrumen</p>
                    <h2 class="text-base font-extrabold">Impor Pertanyaan Formulir Lain</h2>
                </div>
                <button type="button" @click="importDrawerOpen = false" class="rounded-lg bg-white/15 px-2.5 py-1 text-xs font-bold hover:bg-white/25">Tutup</button>
            </div>

            <div class="flex-1 space-y-4 overflow-y-auto p-5 text-xs">
                <input type="text" x-model="importSearch" placeholder="Cari judul atau kode formulir..." class="h-9 w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-3 outline-none focus:border-[#0069A8] focus:bg-white" />

                <template x-if="filteredImportForms().length === 0">
                    <p class="rounded-xl border border-dashed border-slate-200 p-6 text-center text-slate-500">Belum ada formulir lain untuk diimpor.</p>
                </template>

                <template x-if="filteredImportForms().length > 0">
                    <div class="space-y-3">
                        <label class="block">
                            <span class="mb-1 block font-bold text-slate-700">Sumber Instrumen</span>
                            <select x-model="importSelectedFormCode" @change="importSelectedIds = []" class="h-9 w-full rounded-xl border border-slate-200 bg-white px-2.5 font-semibold">
                                <template x-for="f in filteredImportForms()" :key="f.code">
                                    <option :value="f.code" x-text="`${f.code} · ${f.title}`"></option>
                                </template>
                            </select>
                        </label>

                        <template x-if="currentImportForm()">
                            <div class="space-y-2">
                                <p class="font-bold text-slate-800" x-text="`Pilih komponen (${importSelectedIds.length} dipilih):`"></p>
                                <template x-for="it in currentImportForm().items" :key="it.id">
                                    <label class="flex cursor-pointer items-start gap-2.5 rounded-xl border border-slate-200 bg-[#F8FAFC] p-3 hover:border-[#0069A8]">
                                        <input type="checkbox" :checked="importSelectedIds.includes(it.id)" @change="toggleImportField(it.id)" class="mt-0.5 h-4 w-4 accent-[#0069A8]" />
                                        <div class="min-w-0">
                                            <p class="font-bold text-slate-900" x-text="it.label"></p>
                                            <p class="font-mono text-[10px] text-slate-500" x-text="typeLabel(it.type)"></p>
                                        </div>
                                    </label>
                                </template>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            <div class="border-t border-slate-200 bg-[#F8FAFC] px-5 py-3">
                <button type="button"
                        @click="importSelectedQuestions()"
                        :disabled="importSelectedIds.length === 0"
                        class="w-full rounded-xl bg-[#0069A8] py-2.5 text-xs font-bold text-white transition hover:bg-[#182654] disabled:opacity-40">
                    Impor <span x-text="importSelectedIds.length"></span> item terpilih
                </button>
            </div>
        </aside>
    </div>
</template>

<!-- 3. Slide-over Drawer: Distribusi / Bagikan Formulir -->
<template x-if="shareDrawerOpen">
    <div class="fixed inset-0 z-50 flex justify-end bg-slate-950/40 backdrop-blur-[1px]" @click.self="shareDrawerOpen = false">
        <aside class="flex h-full w-full max-w-md flex-col overflow-hidden border-l border-slate-200 bg-white shadow-2xl">
            <div class="flex items-center justify-between bg-linear-to-r from-[#09122C] to-[#182654] px-5 py-4 text-white">
                <div>
                    <p class="font-mono text-[10px] font-bold tracking-wider text-[#74D4FF] uppercase">Distribusi Instrumen</p>
                    <h2 class="text-base font-extrabold">Bagikan Formulir</h2>
                </div>
                <button type="button" @click="shareDrawerOpen = false" class="rounded-lg bg-white/15 px-2.5 py-1 text-xs font-bold hover:bg-white/25">Tutup</button>
            </div>

            <div class="flex-1 space-y-5 overflow-y-auto p-5 text-xs">
                <div class="rounded-xl border border-slate-200 bg-[#F8FAFC] p-4">
                    <p class="font-bold text-slate-800">Tautan Portal Pengisian</p>
                    <p class="mt-1 break-all font-mono text-[11px] text-[#0069A8]" x-text="publicUrl || ''"></p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <button type="button" @click="copyPublicLink()" class="rounded-lg bg-[#0069A8] px-3.5 py-2 font-bold text-white">Salin Tautan</button>
                        <a :href="publicUrl" target="_blank" class="rounded-lg border border-slate-200 bg-white px-3.5 py-2 font-bold text-slate-700 hover:text-[#0069A8]">Buka Halaman</a>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-[#F8FAFC] p-4">
                    <p class="font-bold text-slate-800">Sematkan HTML (&lt;iframe&gt;)</p>
                    <div class="mt-2 grid grid-cols-2 gap-2">
                        <label>
                            <span class="block text-[10px] text-slate-500">Lebar (px)</span>
                            <input type="number" x-model.number="embedWidth" class="h-8 w-full rounded-lg border border-slate-200 bg-white px-2 font-mono" />
                        </label>
                        <label>
                            <span class="block text-[10px] text-slate-500">Tinggi (px)</span>
                            <input type="number" x-model.number="embedHeight" class="h-8 w-full rounded-lg border border-slate-200 bg-white px-2 font-mono" />
                        </label>
                    </div>
                    <textarea rows="3" readonly :value="embedHtmlCode()" class="mt-2 w-full rounded-lg border border-slate-200 bg-white p-2 font-mono text-[11px] text-slate-700"></textarea>
                    <button type="button" @click="copyEmbedCode()" class="mt-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 font-bold text-slate-700 hover:text-[#0069A8]">Salin Kode HTML</button>
                </div>

                <template x-if="duplicateUrl">
                    <form method="POST" :action="duplicateUrl" class="rounded-xl border border-slate-200 bg-white p-4">
                        @csrf
                        <p class="font-bold text-slate-800">Duplikat sebagai Instrumen Baru</p>
                        <p class="mt-1 text-[#475569]">Salin seluruh pertanyaan, percabangan, kunci jawaban, dan tampilan ke kode formulir baru.</p>
                        <button type="submit" class="mt-3 rounded-lg border border-slate-200 bg-[#F8FAFC] px-3.5 py-2 font-bold text-slate-800 hover:border-[#0069A8] hover:text-[#0069A8]">Buat Salinan</button>
                    </form>
                </template>
            </div>
        </aside>
    </div>
</template>
