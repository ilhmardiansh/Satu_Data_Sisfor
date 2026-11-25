@extends('layouts.app')

@section('title', ($dataForm ? 'Ubah: '.$dataForm->title : 'Buat Formulir Baru') . ' - Studio Instrumen Satu Data UNUSA')
@section('hideFooter', true)

@section('content')
<script type="application/json" id="gform-studio-data">@json($studioPayload)</script>

<div x-data="gformStudio()"
     :data-gf-palette="settings.theme?.palette || 'cerulean'"
     :data-gf-bg="settings.theme?.bg_tone || 'soft'"
     class="wd-studio-bg min-h-[calc(100dvh-65px)] pb-16">

    <!-- Hidden authoritative payload submitted on Simpan / Publikasikan -->
    <form x-ref="studioForm" :action="action" method="POST" novalidate class="hidden">
        @csrf
        <template x-if="mode === 'edit'">
            <input type="hidden" name="_method" value="PUT">
        </template>
        <input type="hidden" name="title" :value="meta.title">
        <input type="hidden" name="description" :value="meta.description">
        <input type="hidden" name="category_id" :value="meta.category_id">
        <input type="hidden" name="tahun" :value="meta.tahun">
        <input type="hidden" name="periode" :value="meta.periode">
        <input type="hidden" name="target" :value="meta.target">
        <input type="hidden" name="deadline" :value="meta.deadline">
        <input type="hidden" name="batasi_satu_respon" :value="meta.batasi_satu_respon ? 1 : 0">
        <input type="hidden" name="status_aktif" :value="meta.status_aktif ? 1 : 0">
        <input type="hidden" name="after_save" :value="afterSave">
        <input type="hidden" name="fields_json" :value="serializedFields()">
        <input type="hidden" name="settings_json" :value="serializedSettings()">
    </form>

    <!-- UNUSA Architectural Dark-Navy Studio Command Header -->
    <header x-show="!previewMode" class="sticky top-16 z-30 shadow-sm">
        <div class="border-b border-white/10 bg-linear-to-r from-[#09122C] via-[#101F4A] to-[#182654] text-white">
            <div class="mx-auto flex max-w-[1440px] flex-wrap items-center justify-between gap-3 px-4 py-3 sm:px-6">
                <!-- Left: Back + Eyebrow + Inline Editable Title -->
                <div class="flex min-w-0 flex-1 items-center gap-3">
                    <a href="{{ route('panel.formulir') }}"
                       class="inline-flex h-10 shrink-0 items-center gap-1.5 rounded-xl border border-white/15 bg-white/10 px-3 text-xs font-bold text-white transition hover:bg-white/20"
                       title="Kembali ke Daftar Instrumen">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
                        <span class="hidden sm:inline">Daftar</span>
                    </a>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-[10px] font-bold tracking-[0.14em] text-[#74D4FF] uppercase">
                                STUDIO INSTRUMEN SATU DATA
                            </span>
                            <template x-if="formCode">
                                <span class="rounded bg-white/15 px-1.5 py-0.5 text-[10px] font-bold tabular-nums text-white" x-text="formCode"></span>
                            </template>
                            <template x-if="settings.is_quiz">
                                <span class="rounded bg-emerald-400/20 px-1.5 py-0.5 text-[10px] font-bold tabular-nums text-emerald-200" x-text="`KUIS · ${totalPoints()} POIN`"></span>
                            </template>
                            <span x-show="hasChanges" class="rounded bg-amber-400/20 px-1.5 py-0.5 text-[10px] font-semibold text-amber-200">
                                Perubahan belum disimpan
                            </span>
                        </div>
                        <input type="text"
                               x-model="meta.title"
                               placeholder="Ketik judul instrumen pengumpulan data..."
                               aria-label="Judul instrumen"
                               class="mt-0.5 w-full max-w-lg truncate rounded-lg border border-transparent bg-transparent px-1.5 py-0.5 text-sm font-extrabold text-white outline-none transition placeholder:text-white/45 hover:border-white/20 focus:border-[#00A6F4] focus:bg-[#09122C]/70 sm:text-base" />
                    </div>
                </div>

                <!-- Right: Studio Actions -->
                <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                    <div class="hidden items-center rounded-xl border border-white/15 bg-white/10 p-0.5 sm:inline-flex">
                        <button type="button"
                                @click="undo()"
                                :disabled="historyIndex <= 0"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-white/90 transition hover:bg-white/15 disabled:opacity-35"
                                title="Urungkan (Ctrl+Z)"
                                aria-label="Urungkan">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7v6h6"/><path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13"/></svg>
                        </button>
                        <button type="button"
                                @click="redo()"
                                :disabled="historyIndex >= history.length - 1"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-white/90 transition hover:bg-white/15 disabled:opacity-35"
                                title="Ulangi (Ctrl+Y)"
                                aria-label="Ulangi">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 7v6h-6"/><path d="M3 17a9 9 0 0 1 9-9 9 9 0 0 1 6 2.3l3 2.7"/></svg>
                        </button>
                    </div>

                    <button type="button"
                            @click="themeDrawerOpen = true"
                            class="inline-flex h-9 items-center gap-1.5 rounded-xl border border-white/15 bg-white/10 px-3 text-xs font-semibold text-white transition hover:bg-white/20"
                            title="Sesuaikan Identitas Visual UNUSA">
                        <svg class="h-3.5 w-3.5 text-[#74D4FF]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="13.5" cy="6.5" r=".5" fill="currentColor"/><circle cx="17.5" cy="10.5" r=".5" fill="currentColor"/><circle cx="8.5" cy="7.5" r=".5" fill="currentColor"/><circle cx="6.5" cy="12.5" r=".5" fill="currentColor"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"/></svg>
                        <span class="hidden md:inline">Tampilan</span>
                    </button>

                    <button type="button"
                            @click="openPreview()"
                            class="inline-flex h-9 items-center gap-1.5 rounded-xl border border-white/15 bg-white/10 px-3 text-xs font-semibold text-white transition hover:bg-white/20"
                            title="Pratinjau Portal Responden">
                        <svg class="h-3.5 w-3.5 text-[#74D4FF]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        <span class="hidden md:inline">Pratinjau</span>
                    </button>

                    <template x-if="mode === 'edit'">
                        <button type="button"
                                @click="shareDrawerOpen = true"
                                class="inline-flex h-9 items-center gap-1.5 rounded-xl border border-white/15 bg-white/10 px-3 text-xs font-semibold text-white transition hover:bg-white/20">
                            <svg class="h-3.5 w-3.5 text-[#74D4FF]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg>
                            <span>Bagikan</span>
                        </button>
                    </template>

                    <button type="button"
                            @click="saveStudio('edit', $refs.studioForm)"
                            class="hidden h-9 items-center gap-1.5 rounded-xl border border-white/25 bg-white/10 px-3 text-xs font-bold text-white transition hover:bg-white/20 sm:inline-flex">
                        Simpan
                    </button>

                    <button type="button"
                            @click="saveStudio('list', $refs.studioForm)"
                            class="inline-flex h-9 items-center gap-1.5 rounded-xl bg-[#00A6F4] px-4 text-xs font-extrabold text-[#09122C] transition hover:bg-[#38BDF8]">
                        <span x-text="mode === 'edit' ? 'Simpan & Selesai' : 'Publikasikan'"></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- UNUSA Studio Module Strip (Segmented Pill Tabs — NO Google underline tabs) -->
        <div class="border-b border-slate-200/90 bg-white">
            <div class="mx-auto flex max-w-[1440px] flex-wrap items-center justify-between gap-3 px-4 py-2 sm:px-6">
                <div class="inline-flex rounded-xl border border-slate-200 bg-[#F8FAFC] p-1 text-xs font-bold">
                    <button type="button"
                            @click="activeTab = 'questions'"
                            :class="activeTab === 'questions' ? 'bg-[#0069A8] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                            class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-1.5 transition">
                        <span class="text-[10px] opacity-80">01</span>
                        <span>Rancang Instrumen</span>
                    </button>
                    <button type="button"
                            @click="activeTab = 'responses'"
                            :class="activeTab === 'responses' ? 'bg-[#0069A8] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                            class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-1.5 transition">
                        <span class="text-[10px] opacity-80">02</span>
                        <span>Analitik Respons</span>
                        <span class="rounded-md px-1.5 py-0.5 text-[10px] tabular-nums"
                              :class="activeTab === 'responses' ? 'bg-white/20 text-white' : 'bg-slate-200/80 text-slate-700'"
                              x-text="responses.total || 0"></span>
                    </button>
                    <button type="button"
                            @click="activeTab = 'settings'"
                            :class="activeTab === 'settings' ? 'bg-[#0069A8] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                            class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-1.5 transition">
                        <span class="text-[10px] opacity-80">03</span>
                        <span>Konfigurasi &amp; Kuis</span>
                    </button>
                </div>

                <!-- Telemetry & Mobile Drawer Triggers -->
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <span class="hidden rounded-lg bg-[#F0F9FF] px-2.5 py-1 font-semibold text-[#0069A8] md:inline-block"
                          x-text="`${meta.target} · ${meta.tahun} (${meta.periode})`"></span>
                    <span class="hidden rounded-lg px-2.5 py-1 text-[11px] font-bold sm:inline-block"
                          :class="meta.status_aktif ? 'bg-[#ECFDF5] text-[#047857]' : 'bg-slate-100 text-slate-600'"
                          x-text="meta.status_aktif ? 'Menerima Respons' : 'Ditutup'"></span>

                    <div x-show="activeTab === 'questions'" class="flex items-center gap-1.5 xl:hidden">
                        <button type="button" @click="mobilePaletteOpen = true" class="inline-flex h-8 items-center gap-1 rounded-lg bg-[#0069A8] px-2.5 text-xs font-bold text-white">
                            + Komponen
                        </button>
                        <button type="button" @click="mobileInspectorOpen = true" class="inline-flex h-8 items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 text-xs font-bold text-slate-700">
                            Properti
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Validation Error & Status Banners -->
    <div x-show="validationError !== ''" x-cloak class="mx-auto mt-4 max-w-5xl px-4">
        <div class="flex items-center justify-between gap-3 rounded-xl border border-red-200 bg-[#FEF2F2] px-4 py-3 text-sm font-semibold text-[#B91C1C]">
            <span x-text="validationError"></span>
            <button type="button" @click="validationError = ''" class="text-xs underline">Tutup</button>
        </div>
    </div>

    @if($errors->any())
        <div class="mx-auto mt-4 max-w-5xl px-4">
            <div class="rounded-xl border border-red-200 bg-[#FEF2F2] px-4 py-3 text-sm text-[#B91C1C]">
                <p class="font-bold">Gagal menyimpan formulir:</p>
                <ul class="mt-1 list-inside list-disc text-xs">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    @if(session('success'))
        <div class="mx-auto mt-4 max-w-5xl px-4">
            <div class="rounded-xl border border-emerald-200 bg-[#ECFDF5] px-4 py-3 text-sm font-semibold text-[#047857]">
                {{ session('success') }}
            </div>
        </div>
    @endif

    <!-- Main Workbench Workspace -->
    <div x-show="!previewMode" class="mx-auto max-w-[1440px] px-4 pt-5 sm:px-6">
        <div x-show="activeTab === 'questions'">
            @include('panel.partials.gform-studio-questions')
        </div>
        <div x-show="activeTab === 'responses'" x-cloak>
            @include('panel.partials.gform-studio-responses')
        </div>
        <div x-show="activeTab === 'settings'" x-cloak>
            @include('panel.partials.gform-studio-settings')
        </div>
    </div>

    <!-- Live Interactive Preview Mode -->
    <div x-show="previewMode" x-cloak>
        @include('panel.partials.gform-studio-preview')
    </div>

    <!-- Drawers (Theme Customizer, Import Questions, Share / Send) -->
    @include('panel.partials.gform-studio-drawers')
</div>
@endsection
