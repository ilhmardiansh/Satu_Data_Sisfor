<!-- Shared Contextual Property Inspector (Desktop Right Pane + Mobile Drawer) -->
<div class="space-y-4">
    <!-- Case 1: Header / Metadata Selected -->
    <template x-if="selectedTarget === 'header' || !activeItem()">
        <div class="space-y-4">
            <div class="border-b border-slate-100 pb-3">
                <p class="font-mono text-[10px] font-bold tracking-[0.14em] text-[#0069A8] uppercase">Inspektor Instrumen</p>
                <h3 class="mt-0.5 text-sm font-extrabold text-slate-900">Metadata &amp; Tata Kelola</h3>
            </div>

            <label class="block">
                <span class="mb-1 block font-bold text-slate-700">Kategori Data <span class="text-[#B91C1C]">*</span></span>
                <select x-model="meta.category_id" class="h-9 w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-2.5 font-semibold text-slate-800 outline-none focus:border-[#0069A8] focus:bg-white">
                    <option value="">Pilih kategori...</option>
                    @foreach($categories as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </label>

            <label class="block">
                <span class="mb-1 block font-bold text-slate-700">Sasaran Responden</span>
                <select x-model="meta.target" class="h-9 w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-2.5 font-semibold text-slate-800 outline-none focus:border-[#0069A8] focus:bg-white">
                    <option value="Mahasiswa">Mahasiswa</option>
                    <option value="Dosen">Dosen</option>
                    <option value="Semua">Semua (Mahasiswa &amp; Dosen)</option>
                </select>
            </label>

            <div class="grid grid-cols-2 gap-2.5">
                <label class="block">
                    <span class="mb-1 block font-bold text-slate-700">Tahun</span>
                    <input type="text" inputmode="numeric" x-model="meta.tahun" class="h-9 w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-2.5 font-mono font-bold text-slate-800" />
                </label>
                <label class="block">
                    <span class="mb-1 block font-bold text-slate-700">Semester</span>
                    <select x-model="meta.periode" class="h-9 w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-2.5 font-semibold text-slate-800">
                        <option value="Ganjil">Ganjil</option>
                        <option value="Genap">Genap</option>
                    </select>
                </label>
            </div>

            <label class="block">
                <span class="mb-1 block font-bold text-slate-700">Tenggat Pengumpulan <span class="text-[#B91C1C]">*</span></span>
                <input type="datetime-local" x-model="meta.deadline" class="h-9 w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-2.5 font-mono font-semibold text-slate-800" />
            </label>

            <div class="space-y-2 border-t border-slate-100 pt-3">
                <label class="flex cursor-pointer items-center justify-between gap-2 font-bold text-slate-700">
                    <span>Aktif menerima respons</span>
                    <input type="checkbox" x-model="meta.status_aktif" class="h-4 w-4 accent-[#0069A8]" />
                </label>
                <label class="flex cursor-pointer items-center justify-between gap-2 font-bold text-slate-700">
                    <span>Batasi 1 respons per akun</span>
                    <input type="checkbox" x-model="meta.batasi_satu_respon" class="h-4 w-4 accent-[#0069A8]" />
                </label>
            </div>

            <button type="button" @click="themeDrawerOpen = true" class="w-full rounded-xl border border-slate-200 bg-[#F8FAFC] py-2 font-bold text-[#0069A8] hover:border-[#0069A8] hover:bg-[#F0F9FF]">
                Sesuaikan Warna &amp; Sampul UNUSA
            </button>
        </div>
    </template>

    <!-- Case 2: Active Item Selected -->
    <template x-if="selectedTarget === 'item' && activeItem()">
        <div class="space-y-4">
            <div class="flex items-start justify-between gap-2 border-b border-slate-100 pb-3">
                <div>
                    <p class="font-mono text-[10px] font-bold tracking-[0.14em] text-[#0069A8] uppercase">Inspektor Properti</p>
                    <h3 class="mt-0.5 text-sm font-extrabold text-slate-900" x-text="typeLabel(activeItem().type)"></h3>
                </div>
                <span class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[10px] font-bold text-slate-600" x-text="activeItem().id"></span>
            </div>

            <!-- 2A: Section Inspector -->
            <template x-if="activeItem().type === 'section'">
                <div class="space-y-3">
                    <label class="block">
                        <span class="mb-1.5 block font-bold text-slate-700">Navigasi setelah bagian ini</span>
                        <select x-model="activeItem().config.after_action" class="h-9 w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-2.5 font-semibold text-slate-800">
                            <option value="next">Lanjut ke bagian berikutnya</option>
                            <option value="submit">Kirim formulir langsung</option>
                            <template x-for="(secOpt, sOptIdx) in sectionsList()" :key="secOpt.id">
                                <option :value="secOpt.id" x-text="`Buka bag. ${sOptIdx + 1}: ${secOpt.title}`"></option>
                            </template>
                        </select>
                    </label>
                    <div class="space-y-1.5 border-t border-slate-100 pt-3">
                        <button type="button" @click="duplicateSection(activeItemIndex())" class="w-full rounded-xl border border-slate-200 bg-[#F8FAFC] py-2 font-bold text-slate-700 hover:border-[#0069A8]">Duplikat Bagian</button>
                        <button type="button" @click="mergeSectionUp(activeItemIndex())" class="w-full rounded-xl border border-slate-200 bg-[#F8FAFC] py-2 font-bold text-slate-700 hover:border-[#0069A8]">Gabungkan ke Atas</button>
                        <button type="button" @click="deleteSection(activeItemIndex())" class="w-full rounded-xl border border-red-200 bg-white py-2 font-bold text-[#B91C1C] hover:bg-red-50">Hapus Seluruh Bagian</button>
                    </div>
                </div>
            </template>

            <!-- 2B: Image Block Inspector -->
            <template x-if="activeItem().type === 'image_block'">
                <div class="space-y-3">
                    <label class="inline-flex w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-[#0069A8] py-2 font-bold text-white hover:opacity-95">
                        <span x-text="uploadingTarget === activeItem().id ? 'Mengunggah Gambar...' : 'Unggah Gambar'">Unggah Gambar</span>
                        <input type="file" accept="image/*" class="sr-only" @change="uploadBlockImage($event)" />
                    </label>
                    <p x-show="uploadError" x-cloak class="text-[11px] font-semibold text-[#B91C1C]" x-text="uploadError"></p>
                    <template x-if="activeItem().config?.image_url">
                        <div class="space-y-1.5 rounded-xl border border-slate-200 bg-[#F8FAFC] p-2">
                            <img :src="activeItem().config.image_url" alt="Pratinjau gambar" class="max-h-32 w-full rounded-lg object-contain bg-white" />
                            <button type="button" @click="clearItemImage(activeItem())" class="text-[11px] font-semibold text-[#B91C1C] hover:underline">Hapus gambar</button>
                        </div>
                    </template>
                    <label class="block">
                        <span class="mb-1 block font-bold text-slate-700">Atau URL Gambar</span>
                        <input type="url" x-model="activeItem().config.image_url" placeholder="https://... atau /formulir-media/..." class="h-9 w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-2.5" />
                    </label>
                    <label class="block">
                        <span class="mb-1 block font-bold text-slate-700">Perataan</span>
                        <select x-model="activeItem().config.image_align" class="h-9 w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-2.5 font-semibold">
                            <option value="left">Rata Kiri</option>
                            <option value="center">Tengah</option>
                            <option value="right">Rata Kanan</option>
                        </select>
                    </label>
                    <label class="block">
                        <span class="mb-1 block font-bold text-slate-700">Keterangan Bawah</span>
                        <input type="text" x-model="activeItem().config.image_caption" class="h-9 w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-2.5" />
                    </label>
                </div>
            </template>

            <!-- 2C: Video Block Inspector -->
            <template x-if="activeItem().type === 'video_block'">
                <div class="space-y-3">
                    <label class="block">
                        <span class="mb-1 block font-bold text-slate-700">Tautan YouTube</span>
                        <input type="url" x-model="activeItem().config.video_url" placeholder="https://www.youtube.com/watch?v=..." class="h-9 w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-2.5" />
                    </label>
                    <label class="block">
                        <span class="mb-1 block font-bold text-slate-700">Catatan Video</span>
                        <input type="text" x-model="activeItem().config.video_caption" class="h-9 w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-2.5" />
                    </label>
                </div>
            </template>

            <!-- 2D: Input Question Inspector -->
            <template x-if="isInput(activeItem().type)">
                <div class="space-y-4">
                    <!-- Change Type + Required + Help + Question Image -->
                    <div class="space-y-3">
                        <label class="block">
                            <span class="mb-1 block font-bold text-slate-700">Jenis Input</span>
                            <select x-model="activeItem().type" @change="onTypeChange(activeItem())" class="h-9 w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-2.5 font-bold text-slate-800">
                                <template x-for="qt in questionTypes" :key="qt.value">
                                    <option :value="qt.value" x-text="qt.label"></option>
                                </template>
                            </select>
                        </label>

                        <label class="flex cursor-pointer items-center justify-between gap-2 rounded-xl border border-slate-200 bg-[#F8FAFC] px-3 py-2 font-bold text-slate-800">
                            <span>Wajib diisi responden</span>
                            <input type="checkbox" x-model="activeItem().required" class="h-4 w-4 accent-[#0069A8]" />
                        </label>

                        <label class="flex cursor-pointer items-center justify-between gap-2 font-semibold text-slate-700">
                            <span>Tampilkan teks bantuan</span>
                            <input type="checkbox" x-model="activeItem()._showHelp" class="h-4 w-4 accent-[#0069A8]" />
                        </label>

                        <div class="space-y-2.5 rounded-xl border border-dashed border-slate-300 bg-[#F8FAFC] p-3">
                            <label class="flex cursor-pointer items-center justify-between gap-2 font-bold text-[#0069A8]">
                                <span x-text="uploadingTarget === (activeItem().id + '_iq') ? 'Mengunggah gambar pertanyaan...' : 'Sematkan gambar pertanyaan'">Sematkan gambar pertanyaan</span>
                                <span class="rounded-lg bg-white px-2 py-0.5 text-[10px] font-bold text-[#0069A8] ring-1 ring-sky-200">Pilih File</span>
                                <input type="file" accept="image/*" class="sr-only" @change="uploadQuestionImage($event)" />
                            </label>

                            <p x-show="uploadError" x-cloak class="text-[11px] font-semibold text-[#B91C1C]" x-text="uploadError"></p>

                            <template x-if="activeItem().config?.image_url">
                                <div class="space-y-2 border-t border-slate-200/80 pt-2">
                                    <img :src="activeItem().config.image_url" alt="Pratinjau gambar pertanyaan" class="max-h-32 w-full rounded-lg border border-slate-200 bg-white object-contain" />
                                    <div class="grid grid-cols-2 gap-1.5">
                                        <select x-model="activeItem().config.image_align" class="h-8 rounded-lg border border-slate-200 bg-white px-2 text-[11px] font-semibold">
                                            <option value="left">Rata Kiri</option>
                                            <option value="center">Tengah</option>
                                            <option value="right">Rata Kanan</option>
                                        </select>
                                        <button type="button" @click="clearItemImage(activeItem())" class="h-8 rounded-lg border border-red-200 bg-white px-2 text-[11px] font-bold text-[#B91C1C] hover:bg-red-50">
                                            Hapus gambar
                                        </button>
                                    </div>
                                    <input type="text" x-model="activeItem().config.image_caption" placeholder="Keterangan gambar (opsional)" class="h-8 w-full rounded-lg border border-slate-200 bg-white px-2.5 text-[11px]" />
                                </div>
                            </template>

                            <label class="block border-t border-slate-200/70 pt-2">
                                <span class="mb-1 block text-[10px] font-semibold text-slate-600">Atau tempel URL gambar</span>
                                <input type="text" x-model="activeItem().config.image_url" placeholder="https://... atau /formulir-media/..." class="h-8 w-full rounded-lg border border-slate-200 bg-white px-2.5 text-[11px]" />
                            </label>
                        </div>
                    </div>

                    <!-- Type-Specific Options: Choice / Branching -->
                    <template x-if="isChoice(activeItem().type)">
                        <div class="space-y-2.5 border-t border-slate-100 pt-3">
                            <p class="font-mono text-[10px] font-bold tracking-wider text-[#475569] uppercase">Pengaturan Pilihan</p>

                            <template x-if="activeItem().type !== 'select'">
                                <label class="flex cursor-pointer items-center justify-between gap-2 font-semibold text-slate-700">
                                    <span>Tambahkan opsi "Lainnya..."</span>
                                    <input type="checkbox" x-model="activeItem().config.allow_other" class="h-4 w-4 accent-[#0069A8]" />
                                </label>
                            </template>

                            <label class="flex cursor-pointer items-center justify-between gap-2 font-semibold text-slate-700">
                                <span>Acak urutan opsi</span>
                                <input type="checkbox" x-model="activeItem().config.shuffle_options" class="h-4 w-4 accent-[#0069A8]" />
                            </label>

                            <template x-if="activeItem().type === 'radio' || activeItem().type === 'select'">
                                <div class="space-y-2 pt-1">
                                    <label class="flex cursor-pointer items-center justify-between gap-2 font-bold text-[#0069A8]">
                                        <span>Percabangan antar bagian</span>
                                        <input type="checkbox" x-model="activeItem().config.use_branching" @change="if (activeItem().config.use_branching && !activeItem().config.branching) activeItem().config.branching = {}" class="h-4 w-4 accent-[#0069A8]" />
                                    </label>
                                    <template x-if="activeItem().config?.use_branching && activeItem().config?.branching">
                                        <div class="space-y-2 rounded-xl bg-[#F8FAFC] p-2.5 ring-1 ring-slate-200/80">
                                            <template x-for="opt in activeItem().options" :key="`br_${activeItem().id}_${opt}`">
                                                <label class="block">
                                                    <span class="mb-0.5 block truncate font-bold text-slate-700" x-text="`Jika memilih “${opt}”:`"></span>
                                                    <select x-model="activeItem().config.branching[opt]" class="h-8 w-full rounded-lg border border-slate-200 bg-white px-2 text-[11px] font-semibold">
                                                        <option value="">Lanjut ke bagian berikutnya</option>
                                                        <option value="submit">Kirim formulir langsung</option>
                                                        <template x-for="(secOpt, sIdx3) in sectionsList()" :key="secOpt.id">
                                                            <option :value="secOpt.id" x-text="`Buka bag. ${sIdx3 + 1}: ${secOpt.title}`"></option>
                                                        </template>
                                                    </select>
                                                </label>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </template>

                    <!-- Type-Specific Options: Linear Scale -->
                    <template x-if="activeItem().type === 'linear_scale'">
                        <div class="space-y-2.5 border-t border-slate-100 pt-3">
                            <p class="font-mono text-[10px] font-bold tracking-wider text-[#475569] uppercase">Rentang Skala</p>
                            <div class="grid grid-cols-2 gap-2">
                                <label class="block">
                                    <span class="mb-1 block font-semibold text-slate-700">Dari</span>
                                    <select x-model.number="activeItem().config.scale_min" class="h-8 w-full rounded-lg border border-slate-200 bg-[#F8FAFC] px-2 font-bold">
                                        <option value="0">0</option>
                                        <option value="1">1</option>
                                    </select>
                                </label>
                                <label class="block">
                                    <span class="mb-1 block font-semibold text-slate-700">Sampai</span>
                                    <select x-model.number="activeItem().config.scale_max" class="h-8 w-full rounded-lg border border-slate-200 bg-[#F8FAFC] px-2 font-bold">
                                        <template x-for="n in [2,3,4,5,6,7,8,9,10]" :key="n">
                                            <option :value="n" x-text="n"></option>
                                        </template>
                                    </select>
                                </label>
                            </div>
                            <input type="text" x-model="activeItem().config.scale_min_label" placeholder="Label nilai terendah" class="h-8 w-full rounded-lg border border-slate-200 bg-[#F8FAFC] px-2.5" />
                            <input type="text" x-model="activeItem().config.scale_max_label" placeholder="Label nilai tertinggi" class="h-8 w-full rounded-lg border border-slate-200 bg-[#F8FAFC] px-2.5" />
                        </div>
                    </template>

                    <!-- Type-Specific Options: Rating -->
                    <template x-if="activeItem().type === 'rating'">
                        <div class="space-y-2.5 border-t border-slate-100 pt-3">
                            <p class="font-mono text-[10px] font-bold tracking-wider text-[#475569] uppercase">Pengaturan Peringkat</p>
                            <div class="grid grid-cols-2 gap-2">
                                <label class="block">
                                    <span class="mb-1 block font-semibold text-slate-700">Level Maks</span>
                                    <select x-model.number="activeItem().config.rating_max" class="h-8 w-full rounded-lg border border-slate-200 bg-[#F8FAFC] px-2 font-bold">
                                        <template x-for="n in [3,4,5,6,7,8,9,10]" :key="n">
                                            <option :value="n" x-text="n"></option>
                                        </template>
                                    </select>
                                </label>
                                <label class="block">
                                    <span class="mb-1 block font-semibold text-slate-700">Ikon</span>
                                    <select x-model="activeItem().config.rating_icon" class="h-8 w-full rounded-lg border border-slate-200 bg-[#F8FAFC] px-2 font-bold">
                                        <option value="star">Bintang ★</option>
                                        <option value="heart">Hati ♥</option>
                                        <option value="thumb">Jempol 👍</option>
                                    </select>
                                </label>
                            </div>
                        </div>
                    </template>

                    <!-- Type-Specific Options: Grids -->
                    <template x-if="isGrid(activeItem().type)">
                        <div class="space-y-2 border-t border-slate-100 pt-3">
                            <p class="font-mono text-[10px] font-bold tracking-wider text-[#475569] uppercase">Aturan Kisi</p>
                            <label class="flex cursor-pointer items-center justify-between gap-2 font-semibold text-slate-700">
                                <span>Wajibkan 1 respons tiap baris</span>
                                <input type="checkbox" x-model="activeItem().config.require_row_response" class="h-4 w-4 accent-[#0069A8]" />
                            </label>
                            <label class="flex cursor-pointer items-center justify-between gap-2 font-semibold text-slate-700">
                                <span>Batasi 1 respons per kolom</span>
                                <input type="checkbox" x-model="activeItem().config.limit_one_per_col" class="h-4 w-4 accent-[#0069A8]" />
                            </label>
                            <label class="flex cursor-pointer items-center justify-between gap-2 font-semibold text-slate-700">
                                <span>Acak urutan baris</span>
                                <input type="checkbox" x-model="activeItem().config.shuffle_rows" class="h-4 w-4 accent-[#0069A8]" />
                            </label>
                        </div>
                    </template>

                    <!-- Type-Specific Options: Date / Time -->
                    <template x-if="activeItem().type === 'date'">
                        <div class="space-y-2 border-t border-slate-100 pt-3">
                            <p class="font-mono text-[10px] font-bold tracking-wider text-[#475569] uppercase">Format Tanggal</p>
                            <label class="flex cursor-pointer items-center justify-between font-semibold text-slate-700">
                                <span>Sertakan waktu</span>
                                <input type="checkbox" x-model="activeItem().config.include_time" class="h-4 w-4 accent-[#0069A8]" />
                            </label>
                            <label class="flex cursor-pointer items-center justify-between font-semibold text-slate-700">
                                <span>Sertakan tahun</span>
                                <input type="checkbox" x-model="activeItem().config.include_year" class="h-4 w-4 accent-[#0069A8]" />
                            </label>
                        </div>
                    </template>

                    <template x-if="activeItem().type === 'time'">
                        <div class="space-y-2 border-t border-slate-100 pt-3">
                            <p class="font-mono text-[10px] font-bold tracking-wider text-[#475569] uppercase">Format Waktu</p>
                            <select x-model="activeItem().config.time_mode" class="h-9 w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-2.5 font-semibold">
                                <option value="time">Waktu Jam (JJ:MM)</option>
                                <option value="duration">Durasi (JJ:MM:DD)</option>
                            </select>
                        </div>
                    </template>

                    <!-- Type-Specific Options: File Upload -->
                    <template x-if="activeItem().type === 'file'">
                        <div class="space-y-2.5 border-t border-slate-100 pt-3">
                            <p class="font-mono text-[10px] font-bold tracking-wider text-[#475569] uppercase">Aturan Unggah Berkas</p>
                            <label class="flex cursor-pointer items-center justify-between font-bold text-slate-800">
                                <span>Batasi jenis berkas tertentu</span>
                                <input type="checkbox" x-model="activeItem().config.restrict_file_types" class="h-4 w-4 accent-[#0069A8]" />
                            </label>
                            <p x-show="!activeItem().config.restrict_file_types" class="text-[11px] text-[#047857]">
                                Semua jenis berkas aman (PDF, Office, Gambar, ZIP/RAR, Video, Audio, dll.) diizinkan.
                            </p>
                            <div x-show="activeItem().config.restrict_file_types" class="flex flex-wrap gap-1.5">
                                <template x-for="ft in [['pdf','PDF'],['image','Gambar'],['document','Dokumen'],['spreadsheet','Spreadsheet'],['presentation','Presentasi'],['archive','Arsip ZIP/RAR'],['video','Video'],['audio','Audio']]" :key="ft[0]">
                                    <button type="button"
                                            @click="toggleFileTypeGroup(activeItem(), ft[0])"
                                            :class="(activeItem().config.file_types || []).includes(ft[0]) ? 'border-[#0069A8] bg-[#F0F9FF] text-[#0069A8]' : 'border-slate-200 bg-white text-slate-600'"
                                            class="rounded-lg border px-2 py-1 text-[11px] font-bold"
                                            x-text="ft[1]"></button>
                                </template>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <label class="block">
                                    <span class="mb-1 block font-semibold text-slate-700">Maks File</span>
                                    <select x-model.number="activeItem().config.file_max_count" class="h-8 w-full rounded-lg border border-slate-200 bg-[#F8FAFC] px-2 font-bold">
                                        <option value="1">1 file</option>
                                        <option value="3">3 file</option>
                                        <option value="5">5 file</option>
                                        <option value="10">10 file</option>
                                        <option value="20">20 file</option>
                                    </select>
                                </label>
                                <label class="block">
                                    <span class="mb-1 block font-semibold text-slate-700">Batas Ukuran</span>
                                    <select x-model.number="activeItem().config.file_max_mb" class="h-8 w-full rounded-lg border border-slate-200 bg-[#F8FAFC] px-2 font-bold">
                                        <option value="0">Tanpa batas</option>
                                        <option value="10">10 MB</option>
                                        <option value="25">25 MB</option>
                                        <option value="50">50 MB</option>
                                        <option value="100">100 MB</option>
                                        <option value="250">250 MB</option>
                                        <option value="500">500 MB</option>
                                        <option value="1024">1 GB</option>
                                    </select>
                                </label>
                            </div>
                        </div>
                    </template>

                    <!-- Response Validation Rules -->
                    <template x-if="canValidate(activeItem().type)">
                        <div class="space-y-2.5 border-t border-slate-100 pt-3">
                            <div class="flex items-center justify-between">
                                <span class="font-mono text-[10px] font-bold tracking-wider text-[#475569] uppercase">Validasi Respons</span>
                                <button type="button"
                                        @click="toggleValidation(activeItem())"
                                        class="font-bold text-[#0069A8] hover:underline"
                                        x-text="activeItem().config?.validation?.enabled ? 'Nonaktifkan' : '+ Aktifkan'"></button>
                            </div>

                            <template x-if="activeItem().config?.validation?.enabled">
                                <div class="space-y-2 rounded-xl bg-[#F0F9FF]/60 p-3 ring-1 ring-sky-200">
                                    <template x-if="activeItem().type === 'checkbox'">
                                        <select x-model="activeItem().config.validation.operator" class="h-8 w-full rounded-lg border border-slate-200 bg-white px-2 font-semibold">
                                            <option value="min_select">Pilih paling sedikit</option>
                                            <option value="max_select">Pilih paling banyak</option>
                                            <option value="exact_select">Pilih tepat sejumlah</option>
                                        </select>
                                    </template>
                                    <template x-if="activeItem().type !== 'checkbox'">
                                        <div class="grid grid-cols-2 gap-1.5">
                                            <select x-model="activeItem().config.validation.category" class="h-8 rounded-lg border border-slate-200 bg-white px-2 font-semibold">
                                                <option value="number">Angka</option>
                                                <option value="text">Teks</option>
                                                <option value="length">Panjang</option>
                                                <option value="regex">Regex</option>
                                            </select>
                                            <select x-model="activeItem().config.validation.operator" class="h-8 rounded-lg border border-slate-200 bg-white px-2 font-semibold">
                                                <option value="gt">&gt;</option>
                                                <option value="gte">&gt;=</option>
                                                <option value="lt">&lt;</option>
                                                <option value="lte">&lt;=</option>
                                                <option value="between">Di antara</option>
                                                <option value="contains">Mengandung</option>
                                                <option value="not_contains">Tidak mengandung</option>
                                                <option value="email">Email</option>
                                                <option value="url">URL</option>
                                                <option value="min_chars">Min huruf</option>
                                                <option value="max_chars">Maks huruf</option>
                                                <option value="matches">Cocok Regex</option>
                                            </select>
                                        </div>
                                    </template>
                                    <input type="text" x-model="activeItem().config.validation.value" placeholder="Nilai pembanding / pola" class="h-8 w-full rounded-lg border border-slate-200 bg-white px-2.5" />
                                    <input type="text" x-model="activeItem().config.validation.message" placeholder="Pesan kesalahan kustom" class="h-8 w-full rounded-lg border border-slate-200 bg-white px-2.5" />
                                </div>
                            </template>
                        </div>
                    </template>

                    <!-- Quiz Answer Key & Scoring (Visible when Quiz Mode Active) -->
                    <template x-if="settings.is_quiz">
                        <div class="space-y-2.5 border-t border-slate-100 pt-3">
                            <p class="font-mono text-[10px] font-bold tracking-wider text-[#047857] uppercase">Kunci Jawaban Kuis</p>
                            <label class="flex items-center justify-between gap-2 font-bold text-slate-700">
                                <span>Bobot Poin</span>
                                <input type="number" min="0" max="1000" x-model.number="activeItem().config.points" class="h-8 w-20 rounded-lg border border-emerald-200 bg-[#ECFDF5] px-2 text-center font-mono font-bold text-[#047857]" />
                            </label>
                            <input type="text" x-model="activeItem().config.answer_key" placeholder="Kunci jawaban..." class="h-8 w-full rounded-lg border border-emerald-200 bg-white px-2.5" />
                            <input type="text" x-model="activeItem().config.feedback_correct" placeholder="Umpan balik benar" class="h-8 w-full rounded-lg border border-emerald-200 bg-white px-2.5" />
                            <input type="text" x-model="activeItem().config.feedback_wrong" placeholder="Umpan balik salah" class="h-8 w-full rounded-lg border border-emerald-200 bg-white px-2.5" />
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </template>
</div>
