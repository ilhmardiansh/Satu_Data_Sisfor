<div class="mx-auto max-w-[1120px] px-4 py-6">
    <!-- Top Banner Returning to Studio -->
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-[#182654] px-5 py-3.5 text-white shadow-lg">
        <div>
            <p class="text-[10px] font-bold tracking-wider text-[#74D4FF] uppercase">Pratinjau Portal Responden Satu Data UNUSA</p>
            <p class="text-sm font-bold">Uji alur multi-bagian, percabangan, rating, dan kisi tanpa menyimpan data.</p>
        </div>
        <button type="button" @click="previewMode = false" class="rounded-xl bg-[#00A6F4] px-4 py-2 text-xs font-extrabold text-[#09122C] hover:bg-[#38BDF8]">
            Kembali ke Studio
        </button>
    </div>

    <!-- Preview UNUSA Hero Header -->
    <section class="relative overflow-hidden rounded-3xl border border-slate-200/80 text-white shadow-sm">
        <template x-if="settings.theme?.header_image">
            <img :src="settings.theme.header_image" alt="Sampul formulir" class="absolute inset-0 h-full w-full object-cover" />
        </template>
        <div :class="`gf-banner-${settings.theme?.header_preset || 'cerulean-grid'}`" class="p-6 sm:p-8" :style="settings.theme?.header_image ? 'background: rgba(9, 18, 44, 0.78);' : ''">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-[10px] font-bold text-[#74D4FF] uppercase">
                Pratinjau Instrumen · <span x-text="meta.target"></span>
            </span>
            <h2 class="mt-3 text-2xl font-extrabold tracking-tight" x-text="meta.title || 'Judul Instrumen Belum Diisi'"></h2>
            <p x-show="meta.description" class="mt-2 whitespace-pre-line text-sm text-slate-200" x-text="meta.description"></p>
        </div>
    </section>

    <template x-if="pvSubmitted">
        <div class="wd-bento mt-5 border-l-4 border-l-[#047857] bg-[#ECFDF5] p-8 text-center">
            <p class="text-xs font-bold text-[#047857] uppercase">Simulasi Pengiriman Berhasil</p>
            <h3 class="mt-2 text-xl font-extrabold text-slate-900" x-text="settings.confirmation_message || 'Respons Anda telah direkam.'"></h3>
            <button type="button" @click="pvStep = 0; pvSubmitted = false; pvAnswers = {};" class="mt-5 rounded-xl bg-[#0069A8] px-4 py-2 text-xs font-bold text-white">
                Ulangi Simulasi
            </button>
        </div>
    </template>

    <template x-if="!pvSubmitted && sectionsList()[pvStep]">
        <div class="mt-5 grid grid-cols-1 items-start gap-5 lg:grid-cols-[260px_minmax(0,1fr)]">
            <aside class="wd-bento p-4 text-xs">
                <p class="text-[10px] font-bold tracking-wider text-[#0069A8] uppercase">Navigasi Bagian</p>
                <ol class="mt-2.5 space-y-1.5">
                    <template x-for="(sec, sIdx) in sectionsList()" :key="sec.id">
                        <li :class="pvStep === sIdx ? 'bg-[#F0F9FF] font-bold text-[#0069A8]' : 'text-slate-600'" class="flex items-center gap-2 rounded-lg px-2.5 py-1.5">
                            <span class="text-[10px] tabular-nums" x-text="`${sIdx + 1}.`"></span>
                            <span class="truncate" x-text="sec.title"></span>
                        </li>
                    </template>
                </ol>
            </aside>

            <div class="wd-bento divide-y divide-slate-100 overflow-hidden">
                <template x-for="(entry, pIdx) in sectionsList()[pvStep].items" :key="entry.item.id">
                    <div class="p-6">
                        <template x-if="entry.item.type === 'title_block'">
                            <div>
                                <h4 class="text-base font-extrabold text-slate-900" x-text="entry.item.label"></h4>
                                <p class="mt-1 text-sm text-[#475569]" x-text="entry.item.help"></p>
                            </div>
                        </template>

                        <template x-if="entry.item.type === 'image_block'">
                            <div class="space-y-2">
                                <h4 class="text-sm font-bold text-slate-900" x-text="entry.item.label"></h4>
                                <template x-if="entry.item.config?.image_url">
                                    <img :src="entry.item.config.image_url" class="max-h-64 rounded-xl border border-slate-200 object-contain" />
                                </template>
                                <p x-show="entry.item.config?.image_caption" class="text-xs text-[#475569]" x-text="entry.item.config.image_caption"></p>
                            </div>
                        </template>

                        <template x-if="entry.item.type === 'video_block'">
                            <div class="space-y-2">
                                <h4 class="text-sm font-bold text-slate-900" x-text="entry.item.label"></h4>
                                <template x-if="parseYoutubeEmbed(entry.item.config?.video_url)">
                                    <div class="aspect-video overflow-hidden rounded-xl border border-slate-200">
                                        <iframe :src="parseYoutubeEmbed(entry.item.config.video_url)" class="h-full w-full" allowfullscreen></iframe>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <template x-if="isInput(entry.item.type)">
                            <div>
                                <p class="text-sm font-bold text-slate-900">
                                    <span x-text="entry.item.label"></span>
                                    <span x-show="entry.item.required" class="ml-1 text-[#B91C1C]">*</span>
                                </p>
                                <p x-show="entry.item.help" class="mt-1 text-xs text-[#475569]" x-text="entry.item.help"></p>
                                <template x-if="entry.item.config?.image_url">
                                    <div :class="{ 'text-left': entry.item.config.image_align === 'left', 'text-center': !entry.item.config.image_align || entry.item.config.image_align === 'center', 'text-right': entry.item.config.image_align === 'right' }"
                                         class="mt-3">
                                        <img :src="entry.item.config.image_url" :alt="entry.item.label || 'Gambar pertanyaan'" class="inline-block max-h-60 rounded-xl border border-slate-200 object-contain" />
                                        <p x-show="entry.item.config?.image_caption" class="mt-1 text-xs text-[#475569]" x-text="entry.item.config.image_caption"></p>
                                    </div>
                                </template>
                                <div class="mt-3">
                                    <template x-if="entry.item.type === 'radio'">
                                        <div class="space-y-2">
                                            <template x-for="opt in entry.item.options" :key="opt">
                                                <label class="sd-choice-tile flex cursor-pointer items-center gap-2.5 px-3.5 py-2 text-sm">
                                                    <input type="radio" :name="`pv_${entry.item.id}`" :value="opt" x-model="pvAnswers[entry.item.id]" class="accent-[#0069A8]" />
                                                    <span class="flex-1" x-text="opt"></span>
                                                    <template x-if="entry.item.config?.option_images?.[opt]">
                                                        <img :src="entry.item.config.option_images[opt]" :alt="opt" class="h-12 w-12 rounded-lg border border-slate-200 object-cover" />
                                                    </template>
                                                </label>
                                            </template>
                                        </div>
                                    </template>

                                    <template x-if="entry.item.type === 'checkbox'">
                                        <div class="space-y-2">
                                            <template x-for="opt in entry.item.options" :key="opt">
                                                <label class="sd-choice-tile flex cursor-pointer items-center gap-2.5 px-3.5 py-2 text-sm">
                                                    <input type="checkbox" :name="`pv_${entry.item.id}[]`" :value="opt" class="h-4 w-4 accent-[#0069A8]" />
                                                    <span class="flex-1" x-text="opt"></span>
                                                    <template x-if="entry.item.config?.option_images?.[opt]">
                                                        <img :src="entry.item.config.option_images[opt]" :alt="opt" class="h-12 w-12 rounded-lg border border-slate-200 object-cover" />
                                                    </template>
                                                </label>
                                            </template>
                                        </div>
                                    </template>

                                    <template x-if="entry.item.type === 'select'">
                                        <select x-model="pvAnswers[entry.item.id]" class="h-10 w-full rounded-xl border border-slate-200 px-3 text-sm">
                                            <option value="">Pilih jawaban...</option>
                                            <template x-for="opt in entry.item.options" :key="opt">
                                                <option :value="opt" x-text="opt"></option>
                                            </template>
                                        </select>
                                    </template>

                                    <template x-if="entry.item.type === 'textarea'">
                                        <textarea rows="3" x-model="pvAnswers[entry.item.id]" placeholder="Jawaban paragraf responden..." class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"></textarea>
                                    </template>

                                    <template x-if="entry.item.type === 'file'">
                                        <div class="flex items-center gap-3 rounded-xl border border-dashed border-slate-300 bg-[#F8FAFC] px-4 py-3 text-xs text-slate-600">
                                            <span class="font-bold text-[#0069A8]">Zona Unggah Berkas</span>
                                            <span x-text="`· Maks ${entry.item.config?.file_max_count || 1} file (${Number(entry.item.config?.file_max_mb || 0) === 0 ? 'tanpa batas ukuran' : 'maks ' + entry.item.config.file_max_mb + ' MB'})`"></span>
                                        </div>
                                    </template>

                                    <template x-if="entry.item.type === 'linear_scale'">
                                        <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl bg-[#F8FAFC] px-4 py-3 text-xs">
                                            <span x-text="entry.item.config?.scale_min_label || ''"></span>
                                            <div class="flex gap-2">
                                                <template x-for="n in scaleRange(entry.item.config)" :key="n">
                                                    <button type="button" @click="pvAnswers[entry.item.id] = n" :class="pvAnswers[entry.item.id] == n ? 'bg-[#0069A8] text-white border-[#0069A8]' : 'bg-white text-slate-700 border-slate-300'" class="h-9 w-9 rounded-lg border font-bold tabular-nums transition" x-text="n"></button>
                                                </template>
                                            </div>
                                            <span x-text="entry.item.config?.scale_max_label || ''"></span>
                                        </div>
                                    </template>

                                    <template x-if="entry.item.type === 'rating'">
                                        <div class="flex flex-wrap gap-2">
                                            <template x-for="n in ratingRange(entry.item.config)" :key="n">
                                                <button type="button" @click="pvAnswers[entry.item.id] = n" :class="Number(pvAnswers[entry.item.id] || 0) >= n ? 'text-[#0069A8] scale-110' : 'text-slate-300'" class="text-2xl transition" x-text="entry.item.config?.rating_icon === 'heart' ? '♥' : (entry.item.config?.rating_icon === 'thumb' ? '👍' : '★')"></button>
                                            </template>
                                        </div>
                                    </template>

                                    <template x-if="isGrid(entry.item.type)">
                                        <div class="overflow-x-auto rounded-xl border border-slate-200">
                                            <table class="wd-hairline-table text-xs">
                                                <thead>
                                                    <tr>
                                                        <th class="p-2 text-left"></th>
                                                        <template x-for="colLbl in entry.item.config?.grid_cols || []" :key="colLbl">
                                                            <th class="p-2 text-center font-semibold text-slate-700" x-text="colLbl"></th>
                                                        </template>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <template x-for="(rowLbl, rIdx) in entry.item.config?.grid_rows || []" :key="rIdx">
                                                        <tr>
                                                            <td class="p-2 font-semibold text-slate-800" x-text="rowLbl"></td>
                                                            <template x-for="colLbl in entry.item.config?.grid_cols || []" :key="colLbl">
                                                                <td class="p-2 text-center">
                                                                    <input :type="entry.item.type === 'mc_grid' ? 'radio' : 'checkbox'" :name="`pvg_${entry.item.id}_${rIdx}`" class="h-4 w-4 accent-[#0069A8]" />
                                                                </td>
                                                            </template>
                                                        </tr>
                                                    </template>
                                                </tbody>
                                            </table>
                                        </div>
                                    </template>

                                    <template x-if="!['radio', 'checkbox', 'select', 'textarea', 'file', 'linear_scale', 'rating', 'mc_grid', 'checkbox_grid'].includes(entry.item.type)">
                                        <input :type="entry.item.type === 'date' ? 'date' : (entry.item.type === 'time' ? 'time' : (entry.item.type === 'number' ? 'number' : 'text'))" x-model="pvAnswers[entry.item.id]" placeholder="Jawaban responden..." class="h-10 w-full rounded-xl border border-slate-200 px-3 text-sm" />
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                <div class="flex items-center justify-between gap-3 bg-[#F8FAFC] px-6 py-4">
                    <button type="button" x-show="pvStep > 0" @click="pvStep--" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700">Kembali</button>
                    <button type="button" @click="pvStepNext()" class="ml-auto rounded-xl bg-[#0069A8] px-5 py-2 text-xs font-bold text-white" x-text="pvStep >= sectionsList().length - 1 ? 'Kirim (Simulasi)' : 'Berikutnya'"></button>
                </div>
            </div>
        </div>
    </template>
</div>
