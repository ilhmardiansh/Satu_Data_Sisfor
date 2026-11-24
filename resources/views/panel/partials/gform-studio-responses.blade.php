<div class="mx-auto max-w-6xl space-y-5">
    <!-- Top Bento Telemetry & Actions -->
    <section class="grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1fr)_auto]">
        <div class="wd-bento grid grid-cols-2 divide-x divide-y divide-slate-100 overflow-hidden sm:grid-cols-4 sm:divide-y-0">
            <div class="p-4">
                <p class="font-mono text-[10px] font-bold tracking-wider text-[#475569] uppercase">Total Respons</p>
                <p class="mt-1 font-mono text-2xl font-extrabold text-[#182654]" x-text="responses.total || 0"></p>
            </div>
            <div class="p-4">
                <p class="font-mono text-[10px] font-bold tracking-wider text-[#047857] uppercase">Valid Dataset</p>
                <p class="mt-1 font-mono text-2xl font-extrabold text-[#047857]" x-text="responses.valid || 0"></p>
            </div>
            <div class="p-4">
                <p class="font-mono text-[10px] font-bold tracking-wider text-[#92400E] uppercase">Antrean Mentah</p>
                <p class="mt-1 font-mono text-2xl font-extrabold text-[#92400E]" x-text="responses.mentah || 0"></p>
            </div>
            <div class="p-4">
                <p class="font-mono text-[10px] font-bold tracking-wider text-slate-500 uppercase">Ditolak</p>
                <p class="mt-1 font-mono text-2xl font-extrabold text-slate-700" x-text="responses.ditolak || 0"></p>
            </div>
        </div>

        <div class="wd-bento flex flex-wrap items-center gap-2.5 p-4">
            <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 bg-[#F8FAFC] px-3 py-2 text-xs font-bold text-slate-800">
                <input type="checkbox" x-model="meta.status_aktif" class="h-4 w-4 accent-[#0069A8]" />
                <span>Menerima respons</span>
            </label>
            <template x-if="csvUrl">
                <a :href="csvUrl" class="inline-flex h-9 items-center rounded-xl border border-slate-200 bg-white px-3.5 text-xs font-bold text-slate-700 hover:border-[#0069A8] hover:text-[#0069A8]">Unduh CSV</a>
            </template>
            <template x-if="preprocessUrl">
                <a :href="preprocessUrl" class="inline-flex h-9 items-center rounded-xl bg-[#0069A8] px-4 text-xs font-bold text-white hover:bg-[#182654]">Buka Pre-processing</a>
            </template>
        </div>
    </section>

    <template x-if="(responses.total || 0) === 0">
        <div class="wd-bento border-dashed border-slate-300 px-6 py-14 text-center">
            <p class="text-sm font-bold text-slate-800" x-text="mode === 'create' ? 'Publikasikan formulir terlebih dahulu untuk mulai memantau analitik respons.' : 'Belum ada respons yang dikirim untuk formulir ini.'"></p>
            <p class="mt-1 text-xs text-[#475569]">Distribusi grafik jawaban, rincian per pertanyaan, dan tabel per responden akan muncul di sini.</p>
        </div>
    </template>

    <template x-if="(responses.total || 0) > 0">
        <div class="space-y-4">
            <div class="wd-bento flex flex-wrap items-center justify-between gap-3 p-3">
                <div class="inline-flex rounded-xl border border-slate-200 bg-[#F8FAFC] p-1 text-xs font-bold">
                    <button type="button" @click="responsesSubTab = 'summary'" :class="responsesSubTab === 'summary' ? 'bg-[#0069A8] text-white' : 'text-slate-600 hover:text-slate-900'" class="rounded-lg px-4 py-1.5 transition">Ringkasan Visual</button>
                    <button type="button" @click="responsesSubTab = 'question'" :class="responsesSubTab === 'question' ? 'bg-[#0069A8] text-white' : 'text-slate-600 hover:text-slate-900'" class="rounded-lg px-4 py-1.5 transition">Per Pertanyaan</button>
                    <button type="button" @click="responsesSubTab = 'individual'" :class="responsesSubTab === 'individual' ? 'bg-[#0069A8] text-white' : 'text-slate-600 hover:text-slate-900'" class="rounded-lg px-4 py-1.5 transition">Individual</button>
                </div>
                <span class="font-mono text-xs text-[#475569]" x-text="`${responses.questions.length} kolom terukur`"></span>
            </div>

            <!-- Sub-tab 1: 2-Column Bento Charts Grid -->
            <template x-if="responsesSubTab === 'summary'">
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <template x-for="qStat in responses.questions" :key="qStat.id">
                        <section class="wd-bento flex flex-col p-5">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <h3 class="text-sm font-extrabold text-slate-900" x-text="qStat.label"></h3>
                                    <p class="mt-0.5 text-xs text-[#475569]">
                                        <span x-text="`${qStat.answered_count} jawaban`"></span>
                                        <template x-if="qStat.avg !== null">
                                            <span class="ml-1.5 font-mono font-bold text-[#0069A8]" x-text="`· Rata-rata: ${qStat.avg}`"></span>
                                        </template>
                                    </p>
                                </div>
                                <span class="rounded bg-[#F0F9FF] px-2 py-0.5 font-mono text-[10px] font-bold text-[#0069A8]" x-text="typeLabel(qStat.type)"></span>
                            </div>

                            <template x-if="qStat.chart_kind && qStat.series && qStat.series.length > 0">
                                <div class="mt-4 grid flex-1 grid-cols-1 items-center gap-4 sm:grid-cols-2">
                                    <div class="h-44" x-html="renderSummaryChart(qStat)"></div>
                                    <div class="space-y-1.5 text-xs">
                                        <template x-for="pt in qStat.series" :key="pt.n">
                                            <div class="flex items-center justify-between gap-2 rounded-lg bg-[#F8FAFC] px-2.5 py-1.5">
                                                <span class="truncate font-semibold text-slate-800" x-text="pt.n"></span>
                                                <span class="font-mono font-bold text-[#0069A8]" x-text="pt.v"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            <template x-if="!qStat.chart_kind">
                                <div class="mt-3 max-h-48 space-y-1.5 overflow-y-auto text-xs">
                                    <template x-for="ans in qStat.answers" :key="ans.sub_code">
                                        <div class="rounded-lg bg-[#F8FAFC] px-3 py-2 text-slate-800" x-text="ans.value"></div>
                                    </template>
                                </div>
                            </template>
                        </section>
                    </template>
                </div>
            </template>

            <!-- Sub-tab 2: Per-Question Viewer -->
            <template x-if="responsesSubTab === 'question'">
                <section class="wd-bento space-y-4 p-5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <select x-model.number="responseQuestionIdx" class="h-10 min-w-[260px] flex-1 rounded-xl border border-slate-200 bg-[#F8FAFC] px-3 text-sm font-bold text-slate-800">
                            <template x-for="(qStat, qIdx) in responses.questions" :key="qStat.id">
                                <option :value="qIdx" x-text="`${qIdx + 1}. ${qStat.label} (${qStat.answered_count})`"></option>
                            </template>
                        </select>
                        <div class="flex items-center gap-2 text-xs font-bold">
                            <button type="button" @click="responseQuestionIdx = Math.max(0, responseQuestionIdx - 1)" :disabled="responseQuestionIdx <= 0" class="rounded-lg border border-slate-200 px-3 py-1.5 disabled:opacity-35">Sebelumnya</button>
                            <button type="button" @click="responseQuestionIdx = Math.min(responses.questions.length - 1, responseQuestionIdx + 1)" :disabled="responseQuestionIdx >= responses.questions.length - 1" class="rounded-lg border border-slate-200 px-3 py-1.5 disabled:opacity-35">Berikutnya</button>
                        </div>
                    </div>

                    <template x-if="responses.questions[responseQuestionIdx]">
                        <div class="space-y-2">
                            <template x-for="ans in responses.questions[responseQuestionIdx].answers" :key="ans.sub_code">
                                <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200/80 bg-[#F8FAFC] px-4 py-3 text-xs">
                                    <div>
                                        <p class="font-bold text-slate-900" x-text="ans.value"></p>
                                        <p class="mt-0.5 text-[#475569]" x-text="`${ans.user_name} (${ans.user_nim}) · ${ans.at}`"></p>
                                    </div>
                                    <span class="font-mono text-[11px] font-bold text-[#0069A8]" x-text="ans.sub_code"></span>
                                </div>
                            </template>
                        </div>
                    </template>
                </section>
            </template>

            <!-- Sub-tab 3: Individual Submission Viewer -->
            <template x-if="responsesSubTab === 'individual'">
                <section class="wd-bento space-y-4 p-5">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-3">
                        <div class="text-xs">
                            <p class="font-bold text-slate-900" x-text="`${responses.submissions[responseIndividualIdx]?.name || ''} (${responses.submissions[responseIndividualIdx]?.nim || '-'})`"></p>
                            <p class="text-[#475569]" x-text="`${responses.submissions[responseIndividualIdx]?.code || ''} · ${responses.submissions[responseIndividualIdx]?.at || ''}`"></p>
                        </div>
                        <div class="flex items-center gap-2 text-xs font-bold">
                            <button type="button" @click="responseIndividualIdx = Math.max(0, responseIndividualIdx - 1)" :disabled="responseIndividualIdx <= 0" class="rounded-lg border border-slate-200 px-3 py-1.5 disabled:opacity-35">‹</button>
                            <span class="font-mono text-slate-700" x-text="`${responseIndividualIdx + 1} / ${responses.submissions.length}`"></span>
                            <button type="button" @click="responseIndividualIdx = Math.min(responses.submissions.length - 1, responseIndividualIdx + 1)" :disabled="responseIndividualIdx >= responses.submissions.length - 1" class="rounded-lg border border-slate-200 px-3 py-1.5 disabled:opacity-35">›</button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <template x-for="fld in items.filter(i => isInput(i.type))" :key="fld.id">
                            <div class="rounded-xl border border-slate-200/80 bg-[#F8FAFC] p-3.5 text-xs">
                                <p class="font-bold text-slate-800" x-text="fld.label"></p>
                                <p class="mt-1 text-slate-900" x-text="responses.submissions[responseIndividualIdx]?.values?.[fld.id] || '(kosong)'"></p>
                            </div>
                        </template>
                    </div>
                </section>
            </template>
        </div>
    </template>
</div>
