<div class="mx-auto grid max-w-6xl grid-cols-1 gap-5 lg:grid-cols-2">
    <!-- Card 1: Tata Kelola & Pengumpulan Respons -->
    <section class="wd-bento space-y-4 p-6 text-xs">
        <div class="border-b border-slate-100 pb-3">
            <p class="text-[10px] font-bold tracking-[0.14em] text-[#0069A8] uppercase">Tata Kelola</p>
            <h2 class="mt-0.5 text-base font-extrabold text-slate-900">Pengumpulan Data &amp; Batas Akses</h2>
        </div>

        <label class="flex cursor-pointer items-center justify-between gap-4">
            <div>
                <p class="font-bold text-slate-800">Kumpulkan identitas akun UNUSA (Nama &amp; NIM/NIP)</p>
                <p class="text-[#475569]">Otomatis tercatat untuk verifikasi Produsen Data di fase Pre-processing.</p>
            </div>
            <input type="checkbox" x-model="settings.collect_identity" class="h-4 w-4 accent-[#0069A8]" />
        </label>

        <label class="flex cursor-pointer items-center justify-between gap-4 border-t border-slate-100 pt-3">
            <div>
                <p class="font-bold text-slate-800">Batasi 1 respons per akun</p>
                <p class="text-[#475569]">Mencegah pengisian ganda oleh pengguna yang sama.</p>
            </div>
            <input type="checkbox" x-model="meta.batasi_satu_respon" class="h-4 w-4 accent-[#0069A8]" />
        </label>

        <label class="flex cursor-pointer items-center justify-between gap-4 border-t border-slate-100 pt-3">
            <div>
                <p class="font-bold text-slate-800">Izinkan ubah jawaban sesudah kirim</p>
                <p class="text-[#475569]">Aktif selama respons berstatus Mentah sebelum difinalisasi Wali Data.</p>
            </div>
            <input type="checkbox" x-model="settings.allow_edit_after_submit" class="h-4 w-4 accent-[#0069A8]" />
        </label>

        <label class="flex cursor-pointer items-center justify-between gap-4 border-t border-slate-100 pt-3">
            <div>
                <p class="font-bold text-slate-800">Formulir aktif menerima respons</p>
                <p class="text-[#475569]">Nonaktifkan untuk menutup formulir sementara.</p>
            </div>
            <input type="checkbox" x-model="meta.status_aktif" class="h-4 w-4 accent-[#0069A8]" />
        </label>

        <label class="block border-t border-slate-100 pt-3">
            <span class="mb-1.5 block font-bold text-slate-800">Pesan saat formulir ditutup</span>
            <input type="text" x-model="settings.closed_message" class="h-9 w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-3 text-xs text-slate-900 outline-none focus:border-[#0069A8] focus:bg-white" />
        </label>
    </section>

    <!-- Card 2: Mode Kuis & Penilaian Otomatis -->
    <section class="wd-bento space-y-4 p-6 text-xs">
        <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
            <div>
                <p class="text-[10px] font-bold tracking-[0.14em] text-[#047857] uppercase">Evaluasi &amp; Skor</p>
                <h2 class="mt-0.5 text-base font-extrabold text-slate-900">Mode Kuis Otomatis</h2>
            </div>
            <input type="checkbox" x-model="settings.is_quiz" class="h-5 w-5 accent-[#047857]" />
        </div>

        <div x-show="!settings.is_quiz" class="rounded-xl bg-[#F8FAFC] p-4 text-[#475569]">
            Aktifkan <strong>Mode Kuis Otomatis</strong> untuk memberi bobot poin, kunci jawaban, serta umpan balik nilai instan setelah responden mengirim formulir.
        </div>

        <div x-show="settings.is_quiz" x-cloak class="space-y-3.5">
            <div class="space-y-1.5">
                <p class="font-bold text-slate-800">Jadwal Rilis Nilai</p>
                <label class="flex cursor-pointer items-center gap-2 text-slate-700">
                    <input type="radio" value="immediately" x-model="settings.release_grade" class="accent-[#047857]" />
                    <span>Segera setelah pengiriman selesai</span>
                </label>
                <label class="flex cursor-pointer items-center gap-2 text-slate-700">
                    <input type="radio" value="later" x-model="settings.release_grade" class="accent-[#047857]" />
                    <span>Nanti setelah peninjauan manual</span>
                </label>
            </div>

            <div class="space-y-2 border-t border-slate-100 pt-3">
                <label class="flex cursor-pointer items-center justify-between gap-3">
                    <span class="text-slate-700">Tampilkan pertanyaan yang salah dijawab</span>
                    <input type="checkbox" x-model="settings.quiz_show_missed" class="h-4 w-4 accent-[#047857]" />
                </label>
                <label class="flex cursor-pointer items-center justify-between gap-3">
                    <span class="text-slate-700">Tampilkan kunci jawaban yang benar</span>
                    <input type="checkbox" x-model="settings.quiz_show_correct" class="h-4 w-4 accent-[#047857]" />
                </label>
                <label class="flex cursor-pointer items-center justify-between gap-3">
                    <span class="text-slate-700">Tampilkan rincian nilai poin</span>
                    <input type="checkbox" x-model="settings.quiz_show_points" class="h-4 w-4 accent-[#047857]" />
                </label>
            </div>

            <label class="flex items-center justify-between gap-3 border-t border-slate-100 pt-3">
                <span class="font-bold text-slate-800">Nilai poin default pertanyaan baru</span>
                <input type="number" min="0" max="100" x-model.number="settings.default_points" class="h-9 w-24 rounded-xl border border-slate-200 px-3 text-center font-bold tabular-nums" />
            </label>
        </div>
    </section>

    <!-- Card 3: Presentasi & Default Pertanyaan -->
    <section class="wd-bento space-y-4 p-6 text-xs">
        <div class="border-b border-slate-100 pb-3">
            <p class="text-[10px] font-bold tracking-[0.14em] text-[#0069A8] uppercase">Pengalaman Responden</p>
            <h2 class="mt-0.5 text-base font-extrabold text-slate-900">Presentasi &amp; Default</h2>
        </div>

        <label class="flex cursor-pointer items-center justify-between gap-4">
            <span class="font-bold text-slate-800">Tampilkan bilah progres antar bagian</span>
            <input type="checkbox" x-model="settings.show_progress_bar" class="h-4 w-4 accent-[#0069A8]" />
        </label>

        <label class="flex cursor-pointer items-center justify-between gap-4 border-t border-slate-100 pt-3">
            <span class="font-bold text-slate-800">Acak urutan pertanyaan tiap responden</span>
            <input type="checkbox" x-model="settings.shuffle_questions" class="h-4 w-4 accent-[#0069A8]" />
        </label>

        <label class="flex cursor-pointer items-center justify-between gap-4 border-t border-slate-100 pt-3">
            <span class="font-bold text-slate-800">Tampilkan tautan kirim jawaban lain</span>
            <input type="checkbox" x-model="settings.show_another_response_link" class="h-4 w-4 accent-[#0069A8]" />
        </label>

        <label class="flex cursor-pointer items-center justify-between gap-4 border-t border-slate-100 pt-3">
            <span class="font-bold text-slate-800">Jadikan pertanyaan baru Wajib secara default</span>
            <input type="checkbox" x-model="settings.default_required" class="h-4 w-4 accent-[#0069A8]" />
        </label>

        <label class="block border-t border-slate-100 pt-3">
            <span class="mb-1.5 block font-bold text-slate-800">Pesan konfirmasi setelah kirim</span>
            <textarea rows="2" x-model="settings.confirmation_message" class="w-full rounded-xl border border-slate-200 bg-[#F8FAFC] p-3 text-xs text-slate-900 outline-none focus:border-[#0069A8] focus:bg-white"></textarea>
        </label>
    </section>

    <!-- Card 4: Ringkasan Tema & Identitas Visual -->
    <section class="wd-bento space-y-4 p-6 text-xs">
        <div class="flex items-start justify-between gap-3 border-b border-slate-100 pb-3">
            <div>
                <p class="text-[10px] font-bold tracking-[0.14em] text-[#0069A8] uppercase">Identitas Visual</p>
                <h2 class="mt-0.5 text-base font-extrabold text-slate-900">Tema &amp; Tipografi UNUSA</h2>
            </div>
            <button type="button" @click="themeDrawerOpen = true" class="rounded-lg bg-[#F0F9FF] px-3 py-1.5 font-bold text-[#0069A8] hover:bg-[#E0F2FE]">Panel Tema Lengkap</button>
        </div>

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
                        class="flex flex-col items-center gap-1 rounded-xl border border-slate-200 p-2">
                    <span class="h-6 w-6 rounded-full" :style="`background-color: ${pal.hex}`"></span>
                    <span class="text-[10px] font-bold text-slate-700" x-text="pal.name"></span>
                </button>
            </template>
        </div>

        <div class="grid grid-cols-2 gap-3 pt-1">
            <label class="block">
                <span class="mb-1 block font-bold text-slate-700">Latar Kanvas</span>
                <select x-model="settings.theme.bg_tone" class="h-9 w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-2.5 font-semibold">
                    <option value="soft">Studio Lembut</option>
                    <option value="tint">Biru Muda UNUSA</option>
                    <option value="paper">Putih Bersih</option>
                    <option value="slate">Abu Arsitektural</option>
                </select>
            </label>
            <label class="block">
                <span class="mb-1 block font-bold text-slate-700">Font Portal</span>
                <select x-model="settings.theme.font_family" class="h-9 w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-2.5 font-semibold">
                    <option value="jakarta">Plus Jakarta Sans</option>
                    <option value="serif">Serif Formal</option>
                    <option value="mono">JetBrains Mono</option>
                </select>
            </label>
        </div>
    </section>
</div>
