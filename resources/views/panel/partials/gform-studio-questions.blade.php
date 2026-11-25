<div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-[276px_minmax(0,1fr)_328px]">

    <!-- =================================================================== -->
    <!-- PANE 1 (LEFT): Katalog Komponen & Struktur Alur UNUSA               -->
    <!-- =================================================================== -->
    <aside class="hidden space-y-4 xl:sticky xl:top-40 xl:block">
        <div class="sd-wb-surface overflow-hidden">
            <div class="border-b border-slate-100 bg-[#F8FAFC] p-2">
                <div class="grid grid-cols-2 gap-1 rounded-lg bg-slate-200/70 p-0.5 text-[11px] font-bold">
                    <button type="button"
                            @click="leftPaneTab = 'palette'"
                            :class="leftPaneTab === 'palette' ? 'bg-white text-[#0069A8] shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                            class="rounded-md py-1.5 transition">
                        Komponen
                    </button>
                    <button type="button"
                            @click="leftPaneTab = 'outline'"
                            :class="leftPaneTab === 'outline' ? 'bg-white text-[#0069A8] shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                            class="rounded-md py-1.5 transition"
                            x-text="`Struktur (${totalSections()})`">
                    </button>
                </div>
            </div>

            <!-- Sub-panel A: Categorized Component Palette -->
            <div x-show="leftPaneTab === 'palette'" class="max-h-[calc(100vh-15rem)] space-y-4 overflow-y-auto p-3.5">
                <template x-for="grp in paletteGroups" :key="grp.title">
                    <div>
                        <p class="mb-2 text-[10px] font-bold tracking-[0.14em] text-[#475569] uppercase" x-text="grp.title"></p>
                        <div class="grid grid-cols-1 gap-1.5">
                            <template x-for="pt in grp.items" :key="pt.type">
                                <button type="button"
                                        @click="addItem(pt.type)"
                                        class="group flex items-center justify-between gap-2 rounded-xl border border-slate-200/80 bg-[#F8FAFC] px-3 py-2 text-left text-xs font-semibold text-slate-800 transition hover:border-[#0069A8] hover:bg-[#F0F9FF] hover:text-[#0069A8]">
                                    <span class="truncate" x-text="pt.label"></span>
                                    <span class="shrink-0 rounded bg-white px-1.5 py-0.5 text-[10px] font-bold text-slate-500 ring-1 ring-slate-200 group-hover:text-[#0069A8]" x-text="pt.code"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>

                <div class="border-t border-slate-100 pt-3">
                    <button type="button"
                            @click="importDrawerOpen = true"
                            class="flex w-full items-center justify-center gap-2 rounded-xl border border-dashed border-[#0069A8]/50 bg-[#F0F9FF] px-3 py-2.5 text-xs font-bold text-[#0069A8] transition hover:bg-[#E0F2FE]">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M12 18v-6"/><path d="m9 15 3 3 3-3"/></svg>
                        <span>Impor dari Formulir Lain</span>
                    </button>
                </div>
            </div>

            <!-- Sub-panel B: Interactive Section Outline -->
            <div x-show="leftPaneTab === 'outline'" x-cloak class="max-h-[calc(100vh-15rem)] space-y-3 overflow-y-auto p-3.5 text-xs">
                <button type="button"
                        @click="selectHeader(); window.scrollTo({ top: 0, behavior: 'smooth' });"
                        :class="selectedTarget === 'header' ? 'border-[#0069A8] bg-[#F0F9FF] text-[#0069A8]' : 'border-slate-200 bg-[#F8FAFC] text-slate-800'"
                        class="flex w-full items-center justify-between rounded-xl border px-3 py-2 font-bold">
                    <span class="truncate">Sampul &amp; Metadata</span>
                    <span class="text-[10px]">TOP</span>
                </button>

                <template x-for="(sec, sIdx) in sectionsList()" :key="sec.id">
                    <div class="space-y-1">
                        <button type="button"
                                @click="if (sec.itemIndex >= 0) { selectItem(sec.id); document.getElementById('gf-card-' + sec.id)?.scrollIntoView({ behavior: 'smooth', block: 'center' }); }"
                                class="flex w-full items-center justify-between rounded-lg bg-slate-100/90 px-2.5 py-1.5 text-left text-[11px] font-extrabold text-[#182654] hover:bg-[#F0F9FF] hover:text-[#0069A8]">
                            <span class="truncate" x-text="`Bagian ${sIdx + 1}: ${sec.title || 'Bagian'}`"></span>
                            <span class="text-[10px] tabular-nums text-slate-500" x-text="sec.items.length"></span>
                        </button>
                        <div class="ml-2 space-y-0.5 border-l border-slate-200 pl-2">
                            <template x-for="entry in sec.items" :key="entry.item.id">
                                <button type="button"
                                        @click="selectItem(entry.item.id); document.getElementById('gf-card-' + entry.item.id)?.scrollIntoView({ behavior: 'smooth', block: 'center' });"
                                        :class="selectedTarget === 'item' && activeId === entry.item.id ? 'bg-[#F0F9FF] font-bold text-[#0069A8]' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'"
                                        class="flex w-full items-center justify-between gap-2 rounded-lg px-2 py-1.5 text-left text-[11px] transition">
                                    <span class="truncate" x-text="entry.item.label || 'Tanpa judul'"></span>
                                    <span x-show="entry.item.required" class="text-[#B91C1C]">*</span>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </aside>

    <!-- =================================================================== -->
    <!-- PANE 2 (CENTER): Kanvas Editorial Instrumen Satu Data UNUSA         -->
    <!-- =================================================================== -->
    <div :data-gf-palette="settings.theme?.palette || 'cerulean'"
         :data-gf-bg="settings.theme?.bg_tone || 'soft'"
         class="mx-auto w-full max-w-[820px] space-y-5">

        <!-- UNUSA Architectural Hero Cover Card (Click to Inspect Metadata & Cover) -->
        <section @click="selectHeader()"
                 :class="selectedTarget === 'header' ? 'ring-2 ring-[#00A6F4]' : ''"
                 class="relative cursor-pointer overflow-hidden rounded-2xl border border-slate-200/80 shadow-sm transition">
            <template x-if="settings.theme?.header_image">
                <div class="relative min-h-[190px] bg-[#09122C]">
                    <img :src="settings.theme.header_image" alt="Sampul formulir" class="absolute inset-0 h-full w-full object-cover opacity-55" />
                    <div class="absolute inset-0 bg-linear-to-r from-[#09122C]/92 via-[#0F1C44]/85 to-[#182654]/75"></div>
                </div>
            </template>
            <template x-if="!settings.theme?.header_image">
                <div :class="`gf-banner-${settings.theme?.header_preset || 'cerulean-grid'}`" class="absolute inset-0"></div>
            </template>

            <div class="relative z-10 p-6 text-white sm:p-7">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-[10px] font-bold tracking-wider text-[#74D4FF] uppercase">
                        <span class="h-1.5 w-1.5 rounded-full bg-[#38BDF8]"></span>
                        SAMPUL INSTRUMEN PENGUMPULAN DATA
                    </span>
                    <button type="button"
                            @click.stop="selectHeader(); mobileInspectorOpen = true;"
                            class="rounded-lg border border-white/25 bg-white/10 px-2.5 py-1 text-[11px] font-bold text-white hover:bg-white/20">
                        Atur Metadata &amp; Tenggat
                    </button>
                </div>

                <div class="mt-4">
                    <input type="text"
                           x-model="meta.title"
                           placeholder="Judul Instrumen Pengumpulan Data..."
                           aria-label="Judul Instrumen"
                           class="w-full rounded-xl border border-white/20 bg-white/10 px-3.5 py-2.5 text-xl font-extrabold tracking-tight text-white outline-none transition placeholder:text-white/50 focus:border-[#38BDF8] focus:bg-[#09122C]/60 sm:text-2xl" />
                </div>

                <div class="mt-3">
                    <div class="mb-1.5 flex items-center gap-1">
                        <button type="button" @click.stop="applyRichFormat(meta, 'description', 'bold')" class="rounded bg-white/10 px-2 py-0.5 text-[11px] font-extrabold text-white hover:bg-white/20" title="Tebal">B</button>
                        <button type="button" @click.stop="applyRichFormat(meta, 'description', 'italic')" class="rounded bg-white/10 px-2 py-0.5 text-[11px] italic text-white hover:bg-white/20" title="Miring">I</button>
                        <button type="button" @click.stop="applyRichFormat(meta, 'description', 'underline')" class="rounded bg-white/10 px-2 py-0.5 text-[11px] underline text-white hover:bg-white/20" title="Garis bawah">U</button>
                        <button type="button" @click.stop="applyRichFormat(meta, 'description', 'link')" class="rounded bg-white/10 px-2 py-0.5 text-[11px] font-semibold text-[#74D4FF] hover:bg-white/20" title="Sisipkan tautan">Tautan</button>
                    </div>
                    <textarea rows="2"
                              x-model="meta.description"
                              placeholder="Tulis tujuan pengumpulan data dan petunjuk umum bagi Produsen Data..."
                              class="w-full rounded-xl border border-white/15 bg-white/10 px-3.5 py-2 text-xs text-slate-100 outline-none transition placeholder:text-white/50 focus:border-[#38BDF8] focus:bg-[#09122C]/60 sm:text-sm"></textarea>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-white/15 pt-3 text-[11px] text-slate-200">
                    <span class="rounded-md bg-white/15 px-2.5 py-1 font-semibold" x-text="`Sasaran: ${meta.target}`"></span>
                    <span class="rounded-md bg-white/15 px-2.5 py-1 font-semibold" x-text="`${meta.tahun} · Semester ${meta.periode}`"></span>
                    <span class="rounded-md bg-white/15 px-2.5 py-1 tabular-nums" x-text="`Tenggat: ${meta.deadline ? meta.deadline.replace('T', ' ') : '-'}`"></span>
                    <span class="rounded-md bg-white/15 px-2.5 py-1 tabular-nums" x-text="`${inputCount()} pertanyaan`"></span>
                </div>
            </div>
        </section>

        <!-- Unified Editorial Document Sheet (NO disconnected floating GForm stack) -->
        <div class="sd-wb-surface divide-y divide-slate-200/80 p-3 sm:p-5">
            <template x-for="(item, idx) in items" :key="item.id">
                <div :id="`gf-card-${item.id}`"
                     @click="selectItem(item.id)"
                     @dragover="onDragOver(idx, $event)"
                     @drop="onDrop(idx, $event)"
                     :class="{
                         'sd-wb-block-active': selectedTarget === 'item' && activeId === item.id,
                         'gf-drag-over': dragOverIndex === idx
                     }"
                     class="sd-wb-block my-1.5 p-4 sm:p-5">

                    <!-- Block Control Strip -->
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <span draggable="true"
                                  @dragstart="onDragStart(idx, $event)"
                                  @dragend="onDragEnd()"
                                  title="Seret untuk atur ulang posisi"
                                  class="cursor-grab rounded-lg p-1 text-slate-400 hover:bg-slate-200/70 hover:text-slate-700 active:cursor-grabbing">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><circle cx="9" cy="6" r="1.5"/><circle cx="15" cy="6" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="9" cy="18" r="1.5"/><circle cx="15" cy="18" r="1.5"/></svg>
                            </span>

                            <template x-if="item.type === 'section'">
                                <span class="inline-flex items-center gap-1.5 rounded-lg bg-[#182654] px-2.5 py-1 text-[11px] font-bold text-white"
                                      x-text="`BAGIAN ${String(sectionNumberForItem(idx)).padStart(2, '0')} / ${String(totalSections()).padStart(2, '0')}`"></span>
                            </template>

                            <template x-if="!isInput(item.type) && item.type !== 'section'">
                                <span class="rounded-lg bg-[#F0F9FF] px-2.5 py-1 text-[11px] font-bold text-[#0069A8]" x-text="typeLabel(item.type)"></span>
                            </template>

                            <template x-if="isInput(item.type)">
                                <span class="inline-flex h-6 min-w-6 items-center justify-center rounded-md bg-[#F0F9FF] px-1.5 text-xs font-extrabold tabular-nums text-[#0069A8]"
                                      x-text="String(questionNumberAt(idx)).padStart(2, '0')"></span>
                            </template>

                            <template x-if="isInput(item.type)">
                                <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600" x-text="typeLabel(item.type)"></span>
                            </template>

                            <span x-show="item.required" class="rounded-md bg-red-50 px-2 py-0.5 text-[10px] font-bold text-[#B91C1C]">Wajib</span>
                            <span x-show="item.config?.validation?.enabled" class="rounded-md bg-sky-50 px-2 py-0.5 text-[10px] font-bold text-[#0069A8]">Validasi</span>
                            <span x-show="item.config?.use_branching" class="rounded-md bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-[#92400E]">Bercabang</span>
                            <span x-show="settings.is_quiz && item.config?.points > 0" class="rounded-md bg-emerald-50 px-2 py-0.5 text-[10px] font-bold tabular-nums text-[#047857]" x-text="`${item.config?.points || 0} poin`"></span>
                        </div>

                        <div class="flex items-center gap-1">
                            <button type="button" :disabled="idx === 0" @click.stop="moveItem(idx, -1)" class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 hover:border-[#0069A8] hover:text-[#0069A8] disabled:opacity-30" title="Naikkan">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="18 15 12 9 6 15"/></svg>
                            </button>
                            <button type="button" :disabled="idx === items.length - 1" @click.stop="moveItem(idx, 1)" class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 hover:border-[#0069A8] hover:text-[#0069A8] disabled:opacity-30" title="Turunkan">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                            </button>
                            <button type="button" @click.stop="duplicateItem(idx)" class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 hover:border-[#0069A8] hover:text-[#0069A8]" title="Duplikat">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                            </button>
                            <button type="button" @click.stop="removeItem(idx)" class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 hover:border-red-200 hover:bg-red-50 hover:text-[#B91C1C]" title="Hapus">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                            </button>
                            <button type="button" @click.stop="selectItem(item.id); mobileInspectorOpen = true;" class="inline-flex h-7 items-center rounded-lg border border-slate-200 bg-white px-2 text-[11px] font-bold text-[#0069A8] xl:hidden">
                                Atur
                            </button>
                        </div>
                    </div>

                    <!-- CANVAS EDITOR: SECTION HEADER -->
                    <template x-if="item.type === 'section'">
                        <div class="space-y-2.5 rounded-xl bg-[#F8FAFC] p-4 ring-1 ring-slate-200/80">
                            <input type="text"
                                   x-model="item.label"
                                   placeholder="Judul Bagian Formulir..."
                                   class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-base font-extrabold text-[#182654] outline-none focus:border-[#0069A8]" />
                            <textarea rows="2"
                                      x-model="item.help"
                                      placeholder="Keterangan singkat untuk bagian ini (opsional)"
                                      class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-[#475569] outline-none focus:border-[#0069A8]"></textarea>
                            <div class="flex flex-wrap items-center justify-between gap-2 pt-1 text-[11px] font-semibold">
                                <span class="text-[#475569]">Navigasi setelah bagian &amp; opsi lainnya tersedia di panel kanan.</span>
                                <div class="flex flex-wrap gap-1.5">
                                    <button type="button" @click.stop="duplicateSection(idx)" class="rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-slate-700 hover:border-[#0069A8] hover:text-[#0069A8]">Duplikat bagian</button>
                                    <button type="button" @click.stop="mergeSectionUp(idx)" class="rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-slate-700 hover:border-[#0069A8] hover:text-[#0069A8]">Gabungkan ke atas</button>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- CANVAS EDITOR: TITLE & INFO BLOCK -->
                    <template x-if="item.type === 'title_block'">
                        <div class="space-y-2 rounded-xl bg-[#F8FAFC] p-4 ring-1 ring-slate-200/80">
                            <input type="text" x-model="item.label" placeholder="Judul blok informasi..." class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-extrabold text-slate-900 outline-none focus:border-[#0069A8]" />
                            <textarea rows="2" x-model="item.help" placeholder="Catatan atau petunjuk pengisian tambahan..." class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-[#475569] outline-none focus:border-[#0069A8]"></textarea>
                        </div>
                    </template>

                    <!-- CANVAS EDITOR: IMAGE BLOCK -->
                    <template x-if="item.type === 'image_block'">
                        <div class="space-y-2.5">
                            <input type="text" x-model="item.label" placeholder="Judul gambar / keterangan..." class="w-full rounded-lg border border-slate-200 bg-[#F8FAFC] px-3 py-2 text-sm font-bold text-slate-900 outline-none focus:border-[#0069A8] focus:bg-white" />
                            <div x-show="uploadingTarget === item.id" x-cloak class="rounded-xl border border-sky-200 bg-[#F0F9FF] px-3 py-2 text-xs font-semibold text-[#0069A8]">
                                Mengunggah gambar...
                            </div>
                            <template x-if="item.config?.image_url">
                                <div :class="{ 'text-left': item.config.image_align === 'left', 'text-center': !item.config.image_align || item.config.image_align === 'center', 'text-right': item.config.image_align === 'right' }">
                                    <img :src="item.config.image_url" :alt="item.label || 'Gambar formulir'" class="inline-block max-h-60 rounded-xl border border-slate-200 object-contain" />
                                    <p x-show="item.config?.image_caption" class="mt-1.5 text-xs text-[#475569]" x-text="item.config.image_caption"></p>
                                </div>
                            </template>
                            <template x-if="!item.config?.image_url">
                                <div class="rounded-xl border border-dashed border-slate-300 bg-[#F8FAFC] px-4 py-6 text-center text-xs text-slate-500">
                                    Unggah atau tempel URL gambar melalui Inspektor Properti di sebelah kanan.
                                </div>
                            </template>
                        </div>
                    </template>

                    <!-- CANVAS EDITOR: VIDEO BLOCK -->
                    <template x-if="item.type === 'video_block'">
                        <div class="space-y-2.5">
                            <input type="text" x-model="item.label" placeholder="Judul video penjelasan..." class="w-full rounded-lg border border-slate-200 bg-[#F8FAFC] px-3 py-2 text-sm font-bold text-slate-900 outline-none focus:border-[#0069A8] focus:bg-white" />
                            <template x-if="parseYoutubeEmbed(item.config?.video_url)">
                                <div class="aspect-video overflow-hidden rounded-xl border border-slate-200 bg-slate-900">
                                    <iframe :src="parseYoutubeEmbed(item.config.video_url)" class="h-full w-full" allowfullscreen title="Pratinjau video"></iframe>
                                </div>
                            </template>
                            <template x-if="!parseYoutubeEmbed(item.config?.video_url)">
                                <div class="rounded-xl border border-dashed border-slate-300 bg-[#F8FAFC] px-4 py-6 text-center text-xs text-slate-500">
                                    Masukkan tautan YouTube di Inspektor Properti sebelah kanan untuk menampilkan pemutar video.
                                </div>
                            </template>
                        </div>
                    </template>

                    <!-- CANVAS EDITOR: ALL 13 QUESTION INPUT TYPES -->
                    <template x-if="isInput(item.type)">
                        <div class="space-y-3">
                            <div class="flex items-center gap-2">
                                <input type="text"
                                       x-model="item.label"
                                       placeholder="Ketik pertanyaan di sini..."
                                       class="w-full flex-1 rounded-xl border border-slate-200 bg-[#F8FAFC] px-3.5 py-2.5 text-sm font-bold text-slate-900 outline-none transition focus:border-[#0069A8] focus:bg-white" />
                                <label @click.stop="selectItem(item.id)"
                                       title="Sematkan / ganti gambar pertanyaan"
                                       class="inline-flex h-10 w-10 shrink-0 cursor-pointer items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition hover:border-[#0069A8] hover:text-[#0069A8]">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                                    <input type="file" accept="image/*" class="sr-only" @change="uploadQuestionImage($event, item)" />
                                </label>
                            </div>

                            <div x-show="item._showHelp || (item.help && item.help.trim() !== '')">
                                <input type="text"
                                       x-model="item.help"
                                       placeholder="Petunjuk atau contoh format jawaban (opsional)"
                                       class="w-full rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs text-[#475569] outline-none focus:border-[#0069A8]" />
                            </div>

                            <div x-show="uploadingTarget === (item.id + '_iq')" x-cloak class="rounded-xl border border-sky-200 bg-[#F0F9FF] px-3 py-2 text-xs font-semibold text-[#0069A8]">
                                Mengunggah gambar pertanyaan...
                            </div>

                            <template x-if="item.config?.image_url">
                                <div :class="{ 'text-left': item.config.image_align === 'left', 'text-center': !item.config.image_align || item.config.image_align === 'center', 'text-right': item.config.image_align === 'right' }"
                                     class="space-y-1.5 rounded-xl border border-slate-200/80 bg-[#F8FAFC] p-3">
                                    <div class="relative inline-block">
                                        <img :src="item.config.image_url" alt="Gambar pertanyaan" class="max-h-48 rounded-xl border border-slate-200 bg-white object-contain" />
                                        <button type="button"
                                                @click.stop="clearItemImage(item)"
                                                class="mt-2 inline-flex items-center gap-1 rounded-lg border border-red-200 bg-white px-2.5 py-1 text-[11px] font-bold text-[#B91C1C] hover:bg-red-50">
                                            Hapus gambar pertanyaan
                                        </button>
                                    </div>
                                    <p x-show="item.config?.image_caption" class="text-xs text-[#475569]" x-text="item.config.image_caption"></p>
                                </div>
                            </template>

                            <!-- Text / Textarea / Number Preview Box -->
                            <template x-if="['text', 'textarea', 'number'].includes(item.type)">
                                <div class="rounded-xl border border-slate-200/80 bg-[#F8FAFC] px-3.5 py-2.5 text-xs text-slate-500"
                                     x-text="item.type === 'textarea' ? 'Kotak isian paragraf multi-baris...' : (item.type === 'number' ? 'Kotak isian angka numerik...' : 'Kotak teks jawaban singkat...')"></div>
                            </template>

                            <!-- Radio / Checkbox / Select Inline Options Editor -->
                            <template x-if="isChoice(item.type)">
                                <div class="space-y-2">
                                    <template x-for="(opt, oIdx) in item.options" :key="`${item.id}_opt_${oIdx}`">
                                        <div class="space-y-1.5">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="shrink-0 text-slate-400">
                                                    <template x-if="item.type === 'radio'">
                                                        <span class="inline-block h-4 w-4 rounded-full border-2 border-slate-300 bg-white"></span>
                                                    </template>
                                                    <template x-if="item.type === 'checkbox'">
                                                        <span class="inline-block h-4 w-4 rounded border-2 border-slate-300 bg-white"></span>
                                                    </template>
                                                    <template x-if="item.type === 'select'">
                                                        <span class="text-xs font-bold tabular-nums text-slate-500" x-text="`${oIdx + 1}.`"></span>
                                                    </template>
                                                </span>

                                                <input type="text"
                                                       x-model="item.options[oIdx]"
                                                       @focus="item._prevOpt = item.options[oIdx]"
                                                       @change="syncOptionImageKey(item, item._prevOpt, item.options[oIdx]); item._prevOpt = item.options[oIdx]"
                                                       placeholder="Tulis opsi..."
                                                       class="h-9 min-w-[150px] flex-1 rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-800 outline-none focus:border-[#0069A8]" />

                                                <template x-if="item.type !== 'select'">
                                                    <label class="inline-flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 hover:border-[#0069A8] hover:text-[#0069A8]" title="Unggah gambar untuk opsi">
                                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                                                        <input type="file" accept="image/*" class="sr-only" @change="uploadOptionImage(item, oIdx, $event)" />
                                                    </label>
                                                </template>

                                                <button type="button"
                                                        :disabled="item.options.length <= 1"
                                                        @click="removeOption(item, oIdx)"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-400 hover:border-red-200 hover:text-[#B91C1C] disabled:opacity-25">
                                                    ×
                                                </button>
                                            </div>
                                            <template x-if="item.config?.option_images?.[opt]">
                                                <div class="ml-6 flex items-center gap-2.5 rounded-lg border border-slate-200 bg-[#F8FAFC] p-2">
                                                    <img :src="item.config.option_images[opt]" :alt="opt" class="h-12 w-12 rounded-md border border-slate-200 bg-white object-cover" />
                                                    <button type="button" @click.stop="clearOptionImage(item, opt)" class="text-[11px] font-semibold text-[#B91C1C] hover:underline">
                                                        Hapus gambar opsi
                                                    </button>
                                                </div>
                                            </template>
                                        </div>
                                    </template>

                                    <div x-show="item.config?.allow_other" class="flex items-center gap-2 rounded-lg border border-dashed border-slate-300 bg-[#F8FAFC] px-3 py-1.5 text-xs text-slate-500">
                                        <span>Opsi tambahan: "Lainnya..." aktif (responden mengetik sendiri)</span>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-3 pt-1 text-xs">
                                        <button type="button" @click="addOption(item)" class="font-bold text-[#0069A8] hover:underline">+ Tambah opsi</button>
                                        <template x-if="item.type !== 'select' && !item.config?.allow_other">
                                            <button type="button" @click="item.config.allow_other = true" class="font-semibold text-slate-600 hover:text-[#0069A8]">+ Opsi "Lainnya"</button>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            <!-- Linear Scale Canvas Preview -->
                            <template x-if="item.type === 'linear_scale'">
                                <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-slate-200/80 bg-[#F8FAFC] px-4 py-3 text-xs">
                                    <span class="font-semibold text-[#475569]" x-text="item.config?.scale_min_label || ''"></span>
                                    <div class="flex flex-wrap gap-1.5">
                                        <template x-for="n in scaleRange(item.config)" :key="n">
                                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-xs font-bold tabular-nums text-slate-700" x-text="n"></span>
                                        </template>
                                    </div>
                                    <span class="font-semibold text-[#475569]" x-text="item.config?.scale_max_label || ''"></span>
                                </div>
                            </template>

                            <!-- Rating Canvas Preview -->
                            <template x-if="item.type === 'rating'">
                                <div class="flex flex-wrap items-center gap-2 rounded-xl border border-slate-200/80 bg-[#F8FAFC] px-4 py-2.5">
                                    <template x-for="n in ratingRange(item.config)" :key="n">
                                        <span class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-sm text-[#0069A8]">
                                            <span x-text="item.config?.rating_icon === 'heart' ? '♥' : (item.config?.rating_icon === 'thumb' ? '👍' : '★')"></span>
                                            <span class="text-[11px] font-bold tabular-nums text-slate-600" x-text="n"></span>
                                        </span>
                                    </template>
                                </div>
                            </template>

                            <!-- MC Grid & Checkbox Grid Editor -->
                            <template x-if="isGrid(item.type)">
                                <div class="grid grid-cols-1 gap-3 rounded-xl border border-slate-200/80 bg-[#F8FAFC] p-3.5 text-xs sm:grid-cols-2">
                                    <div>
                                        <p class="mb-1.5 font-extrabold text-slate-800">Daftar Baris</p>
                                        <div class="space-y-1.5">
                                            <template x-for="(rowLbl, rIdx) in item.config.grid_rows" :key="`${item.id}_gr_${rIdx}`">
                                                <div class="flex items-center gap-1.5">
                                                    <span class="text-[10px] tabular-nums text-slate-400" x-text="`${rIdx + 1}.`"></span>
                                                    <input type="text" x-model="item.config.grid_rows[rIdx]" class="h-8 flex-1 rounded-lg border border-slate-200 bg-white px-2.5 text-xs" />
                                                    <button type="button" @click="removeGridRow(item, rIdx)" :disabled="item.config.grid_rows.length <= 1" class="text-xs font-bold text-[#B91C1C] disabled:opacity-30">×</button>
                                                </div>
                                            </template>
                                        </div>
                                        <button type="button" @click="addGridRow(item)" class="mt-2 font-bold text-[#0069A8] hover:underline">+ Baris</button>
                                    </div>
                                    <div>
                                        <p class="mb-1.5 font-extrabold text-slate-800">Daftar Kolom</p>
                                        <div class="space-y-1.5">
                                            <template x-for="(colLbl, cIdx) in item.config.grid_cols" :key="`${item.id}_gc_${cIdx}`">
                                                <div class="flex items-center gap-1.5">
                                                    <span class="h-3.5 w-3.5 border border-slate-400 bg-white" :class="item.type === 'mc_grid' ? 'rounded-full' : 'rounded'"></span>
                                                    <input type="text" x-model="item.config.grid_cols[cIdx]" class="h-8 flex-1 rounded-lg border border-slate-200 bg-white px-2.5 text-xs" />
                                                    <button type="button" @click="removeGridCol(item, cIdx)" :disabled="item.config.grid_cols.length <= 1" class="text-xs font-bold text-[#B91C1C] disabled:opacity-30">×</button>
                                                </div>
                                            </template>
                                        </div>
                                        <button type="button" @click="addGridCol(item)" class="mt-2 font-bold text-[#0069A8] hover:underline">+ Kolom</button>
                                    </div>
                                </div>
                            </template>

                            <!-- File / Date / Time Concise Preview -->
                            <template x-if="item.type === 'file'">
                                <div class="rounded-xl border border-dashed border-slate-300 bg-[#F8FAFC] px-3.5 py-2.5 text-xs font-medium text-slate-600"
                                     x-text="`Zona unggah berkas · ${item.config?.restrict_file_types ? 'Jenis terpilih' : 'Semua jenis berkas'} · Maks ${item.config?.file_max_count || 1} file (${Number(item.config?.file_max_mb || 0) === 0 ? 'tanpa batas ukuran' : 'maks ' + item.config.file_max_mb + ' MB'})`"></div>
                            </template>
                            <template x-if="item.type === 'date'">
                                <div class="rounded-xl border border-slate-200/80 bg-[#F8FAFC] px-3.5 py-2 text-xs text-slate-600"
                                     x-text="`Pemilih Tanggal${item.config?.include_time ? ' + Jam' : ''}${item.config?.include_year === false ? ' (tanpa tahun)' : ''}`"></div>
                            </template>
                            <template x-if="item.type === 'time'">
                                <div class="rounded-xl border border-slate-200/80 bg-[#F8FAFC] px-3.5 py-2 text-xs text-slate-600"
                                     x-text="item.config?.time_mode === 'duration' ? 'Input Durasi (JJ:MM:DD)' : 'Input Waktu Jam (JJ:MM)'"></div>
                            </template>
                        </div>
                    </template>
                </div>
            </template>

            <div class="pt-3">
                <button type="button"
                        @click="addItem('radio')"
                        class="flex w-full items-center justify-center gap-2 rounded-xl border border-dashed border-slate-300 bg-[#F8FAFC] py-3 text-xs font-bold text-[#0069A8] transition hover:border-[#0069A8] hover:bg-[#F0F9FF]">
                    + Tambah Pertanyaan Baru
                </button>
            </div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- PANE 3 (RIGHT): Inspektor Properti Kontekstual UNUSA                -->
    <!-- =================================================================== -->
    <aside class="hidden xl:sticky xl:top-40 xl:block">
        <div class="sd-wb-surface max-h-[calc(100vh-11rem)] overflow-y-auto p-4 text-xs">
            @include('panel.partials.gform-studio-inspector-body')
        </div>
    </aside>

    <!-- Mobile Drawers for Palette & Inspector (< xl screens) -->
    <template x-if="mobilePaletteOpen">
        <div class="fixed inset-0 z-50 flex justify-start bg-slate-950/45 backdrop-blur-[1px] xl:hidden" @click.self="mobilePaletteOpen = false">
            <aside class="flex h-full w-full max-w-xs flex-col bg-white shadow-2xl">
                <div class="flex items-center justify-between bg-[#182654] px-4 py-3 text-white">
                    <span class="text-xs font-extrabold tracking-wider uppercase">Katalog Komponen</span>
                    <button type="button" @click="mobilePaletteOpen = false" class="rounded-lg bg-white/15 px-2.5 py-1 text-xs font-bold">Tutup</button>
                </div>
                <div class="flex-1 space-y-4 overflow-y-auto p-4">
                    <template x-for="grp in paletteGroups" :key="grp.title">
                        <div>
                            <p class="mb-2 text-[10px] font-bold tracking-wider text-[#475569] uppercase" x-text="grp.title"></p>
                            <div class="grid grid-cols-1 gap-1.5">
                                <template x-for="pt in grp.items" :key="pt.type">
                                    <button type="button" @click="addItem(pt.type)" class="flex items-center justify-between rounded-xl border border-slate-200 bg-[#F8FAFC] px-3 py-2 text-xs font-semibold text-slate-800">
                                        <span x-text="pt.label"></span>
                                        <span class="text-[10px] text-[#0069A8]" x-text="pt.code"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>
                    <button type="button" @click="mobilePaletteOpen = false; importDrawerOpen = true" class="w-full rounded-xl bg-[#F0F9FF] py-2.5 text-xs font-bold text-[#0069A8]">Impor dari Formulir Lain</button>
                </div>
            </aside>
        </div>
    </template>

    <template x-if="mobileInspectorOpen">
        <div class="fixed inset-0 z-50 flex justify-end bg-slate-950/45 backdrop-blur-[1px] xl:hidden" @click.self="mobileInspectorOpen = false">
            <aside class="flex h-full w-full max-w-sm flex-col bg-white shadow-2xl">
                <div class="flex items-center justify-between bg-[#182654] px-4 py-3 text-white">
                    <span class="text-xs font-extrabold tracking-wider uppercase">Inspektor Properti</span>
                    <button type="button" @click="mobileInspectorOpen = false" class="rounded-lg bg-white/15 px-2.5 py-1 text-xs font-bold">Selesai</button>
                </div>
                <div class="flex-1 overflow-y-auto p-4 text-xs">
                    @include('panel.partials.gform-studio-inspector-body')
                </div>
            </aside>
        </div>
    </template>
</div>
