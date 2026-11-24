@extends('layouts.panel')

@section('title', 'Ringkasan Panel - Satu Data UNUSA')

@section('panel_content')
@php
    $user = auth()->user();
    $firstName = explode(' ', trim(preg_replace('/[ ,]+/', ' ', $user->name) ?? $user->name))[0];
@endphp

@if($user->isProdusen())
    <x-panel-head
        eyebrow="PORTAL PRODUSEN DATA"
        title="Halo, {{ $firstName }}"
        desc="{{ $user->roleLabel() }} · {{ $user->jenis }} · {{ $user->nim }}"
    />

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <a href="{{ route('panel.isi') }}" class="wd-bento-interactive flex items-center gap-4 p-5 text-left">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-[#F0F9FF] text-[#0069A8]">
                <i data-lucide="file-pen" class="h-5 w-5" aria-hidden="true"></i>
            </span>
            <div>
                <p class="text-[28px] leading-none font-extrabold tracking-tight tabular-nums text-slate-900">{{ $openFormsCount }}</p>
                <p class="mt-1 text-sm text-[#475569]">Formulir yang dibuka</p>
            </div>
        </a>

        <a href="{{ route('panel.riwayat') }}" class="wd-bento-interactive flex items-center gap-4 p-5 text-left">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-[#F0F9FF] text-[#0069A8]">
                <i data-lucide="history" class="h-5 w-5" aria-hidden="true"></i>
            </span>
            <div>
                <p class="text-[28px] leading-none font-extrabold tracking-tight tabular-nums text-slate-900">{{ $mySubmissionsCount }}</p>
                <p class="mt-1 text-sm text-[#475569]">Total kiriman Anda</p>
            </div>
        </a>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div class="rounded-xl border border-[#bae6fd] bg-[#F0F9FF] px-4 py-3">
            <p class="text-xs font-semibold text-[#0069A8]">Menunggu Pre-processing</p>
            <p class="mt-1 text-xl font-extrabold tabular-nums text-slate-900">{{ $pendingMineCount }}</p>
        </div>
        <div class="rounded-xl border border-emerald-200 bg-[#ECFDF5] px-4 py-3">
            <p class="text-xs font-semibold text-[#047857]">Valid (Siap Dataset)</p>
            <p class="mt-1 text-xl font-extrabold tabular-nums text-slate-900">{{ $validMineCount }}</p>
        </div>
        <div class="rounded-xl border border-red-200 bg-[#FEF2F2] px-4 py-3">
            <p class="text-xs font-semibold text-[#B91C1C]">Ditolak</p>
            <p class="mt-1 text-xl font-extrabold tabular-nums text-slate-900">{{ $rejectedMineCount }}</p>
        </div>
    </div>

    <h2 class="mt-8 mb-3 font-bold text-slate-900">Tenggat terdekat</h2>
    <div class="wd-bento divide-y divide-slate-100">
        @forelse($nearestForms as $f)
            <a href="{{ route('formulir.show', $f->code) }}" class="flex min-h-[56px] w-full items-center gap-4 px-5 py-4 text-left transition hover:bg-[#F6F9FC]">
                <i data-lucide="calendar-clock" class="h-5 w-5 shrink-0 text-[#92400E]" aria-hidden="true"></i>
                <div class="min-w-0 flex-1">
                    <p class="truncate font-semibold text-slate-900">{{ $f->title }}</p>
                    <p class="text-xs text-[#475569]">Tenggat {{ $f->deadlineFormatted() }}</p>
                </div>
                <span class="rounded-md bg-[#FFFBEB] px-2.5 py-1 text-xs font-bold text-[#92400E]">{{ $f->timeLeftLabel() }}</span>
            </a>
        @empty
            <p class="px-5 py-6 text-sm text-[#475569]">Tidak ada formulir yang sedang dibuka saat ini.</p>
        @endforelse
    </div>
@else
    @php
        $pipelineMetrics = [
            'openForms' => $openFormsCount,
            'totalForms' => $totalFormsCount,
            'submissions' => $submissionsCount,
            'rawSubmissions' => $rawSubmissionsCount,
            'validSubmissions' => $validSubmissionsCount,
            'formsNeedPreproc' => $formsNeedPreprocessingCount,
            'formsReady' => $formsReadyCount,
            'publishedDatasets' => $publishedDatasetsCount,
            'draftDatasets' => $draftDatasetsCount,
            'visualizations' => $visualizationsCount,
            'pendingRequests' => $pendingRequestsCount,
        ];

        $stagesPayload = [
            'formulir' => [
                'code' => '01',
                'title' => 'Instrumen Formulir',
                'actor' => 'Wali Data · Perancang Instrumen',
                'metric' => $openFormsCount.' aktif',
                'sub' => 'dari '.$totalFormsCount.' instrumen terdaftar',
                'tone' => $openFormsCount > 0 ? 'sky' : 'slate',
                'statusLabel' => $openFormsCount > 0 ? 'Pengumpulan Berjalan' : 'Menunggu Instrumen',
                'desc' => 'Wali Data menyusun formulir digital terstruktur, menentukan sasaran responden, serta mengatur tenggat pengumpulan.',
                'url' => route('panel.formulir'),
                'cta' => 'Kelola Formulir',
            ],
            'respons' => [
                'code' => '02',
                'title' => 'Arus Respons Masuk',
                'actor' => 'Produsen Data · Mahasiswa & Dosen',
                'metric' => $submissionsCount.' respons',
                'sub' => $rawSubmissionsCount.' menunggu kurasi · '.$validSubmissionsCount.' valid',
                'tone' => $submissionsCount > 0 ? 'sky' : 'slate',
                'statusLabel' => $submissionsCount > 0 ? 'Data Masuk Tercatat' : 'Belum Ada Respons',
                'desc' => 'Mahasiswa dan dosen mengirimkan data isian maupun lampiran bukti ke wadah penampungan sebelum tahap kurasi.',
                'url' => $user->isAdmin() ? route('panel.isi') : route('panel.riwayat'),
                'cta' => $user->isAdmin() ? 'Buka Pengisian' : 'Lihat Riwayat',
            ],
            'preprocessing' => [
                'code' => '03',
                'title' => 'Pre-processing Wajib',
                'actor' => 'Wali Data · Kurator Mutu',
                'metric' => $formsNeedPreprocessingCount > 0 ? $formsNeedPreprocessingCount.' form antre' : ($formsReadyCount.' siap'),
                'sub' => $rawSubmissionsCount.' baris mentah · '.$validSubmissionsCount.' baris valid',
                'tone' => ($formsNeedPreprocessingCount > 0 || $rawSubmissionsCount > 0) ? 'amber' : ($formsReadyCount > 0 ? 'emerald' : 'sky'),
                'statusLabel' => ($formsNeedPreprocessingCount > 0 || $rawSubmissionsCount > 0) ? 'Perlu Pembersihan' : ($formsReadyCount > 0 ? 'Bersih & Tervalidasi' : 'Siap Menerima Data'),
                'desc' => 'Wali Data menjalankan pemindaian normalisasi, deduplikasi otomatis, dan imputasi nilai kosong sebelum penyusunan dataset.',
                'url' => route('panel.preprocessing'),
                'cta' => 'Buka Workbench',
            ],
            'dataset' => [
                'code' => '04',
                'title' => 'Dataset Terolah',
                'actor' => 'Wali Data · Pengemas Katalog',
                'metric' => $publishedDatasetsCount.' terbit',
                'sub' => $publicDatasetsCount.' publik · '.$draftDatasetsCount.' draft',
                'tone' => $publishedDatasetsCount > 0 ? 'emerald' : 'sky',
                'statusLabel' => $publishedDatasetsCount > 0 ? 'Katalog Aktif' : 'Menunggu Penyusunan',
                'desc' => 'Data bersih dikemas ke basis data terstruktur (rekap agregat atau rinci dengan masking) dalam format CSV/XLSX.',
                'url' => route('panel.dataset'),
                'cta' => 'Kelola Dataset',
            ],
            'visualisasi' => [
                'code' => '05',
                'title' => 'Visualisasi Dashboard',
                'actor' => 'Pimpinan Prodi & Publik',
                'metric' => $activeVisualizationsCount.' tampil',
                'sub' => 'dari '.$visualizationsCount.' grafik tersimpan',
                'tone' => $activeVisualizationsCount > 0 ? 'sky' : 'slate',
                'statusLabel' => $activeVisualizationsCount > 0 ? 'Tayang di Portal' : 'Belum Diterbitkan',
                'desc' => 'Pimpinan prodi dan pengguna publik meninjau grafik analitik interaktif langsung pada portal Satu Data UNUSA.',
                'url' => route('panel.visualisasi'),
                'cta' => 'Kelola Visualisasi',
            ],
        ];

        $recentSubmissionsPayload = $recentSubmissions->map(fn ($sub): array => [
            'id' => $sub->id,
            'code' => $sub->code,
            'formTitle' => $sub->dataForm?->title ?? '(formulir dihapus)',
            'formCode' => $sub->dataForm?->code ?? '',
            'sender' => $sub->user?->name ?? '-',
            'submittedAt' => $sub->submittedFormatted(),
            'status' => $sub->status_validasi,
            'source' => $sub->sumber_input === 'impor' ? 'Impor' : 'GForm',
            'url' => $sub->dataForm ? route('panel.preprocessing', ['form' => $sub->dataForm->code]) : route('panel.preprocessing'),
        ])->values()->all();

        $ringRadius = 42;
        $ringCircumference = round(2 * M_PI * $ringRadius, 2);
        $scoreVal = $qualityHealthScore ?? 0;
        $ringOffset = round($ringCircumference * (1 - ($scoreVal / 100)), 2);
    @endphp

    <div x-data="waliObservatory(@js($stagesPayload), @js($recentSubmissionsPayload))" class="space-y-6">
        <!-- 1. Interactive 3D Satu Data Pipeline Observatory -->
        <section class="wd-stage-frame p-5 sm:p-7">
            <!-- Top Control Strip -->
            <div class="relative z-10 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-2.5 py-1 font-mono text-[10px] font-bold tracking-[0.14em] text-[#74D4FF] uppercase ring-1 ring-white/15">
                            <span class="h-1.5 w-1.5 rounded-full bg-[#38BDF8]"></span>
                            Observatorium Satu Data · UNUSA
                        </span>
                        <span class="font-mono text-[11px] font-semibold text-slate-300">
                            {{ $user->roleLabel() }}
                        </span>
                    </div>
                    <h1 class="mt-2.5 text-2xl font-extrabold tracking-tight text-white sm:text-[30px]">
                        Halo, {{ $firstName }}
                    </h1>
                    <p class="mt-1 max-w-xl text-sm leading-relaxed text-slate-300">
                        Pantau arus data lima tahap secara real-time—klik simpul 3D atau dok di bawah untuk meninjau status setiap fase.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <div class="inline-flex flex-wrap rounded-xl border border-white/15 bg-white/[0.07] p-1 text-xs font-semibold">
                        <button type="button"
                                @click="focusStage('overview')"
                                :class="cameraPreset === 'overview' && !tourActive ? 'bg-[#00A6F4] text-[#09122C] font-bold' : 'text-slate-200 hover:text-white'"
                                class="min-h-[34px] rounded-lg px-3 py-1 transition">
                            Ikhtisar
                        </button>
                        <button type="button"
                                @click="selectAndFocus('preprocessing')"
                                :class="cameraPreset === 'preprocessing' && !tourActive ? 'bg-[#00A6F4] text-[#09122C] font-bold' : 'text-slate-200 hover:text-white'"
                                class="min-h-[34px] rounded-lg px-3 py-1 transition">
                            Fokus Pre-proc
                        </button>
                        <button type="button"
                                @click="toggleTour()"
                                :aria-pressed="tourActive"
                                :class="tourActive ? 'bg-emerald-400 text-[#09122C] font-bold' : 'text-slate-200 hover:text-white'"
                                class="inline-flex min-h-[34px] items-center gap-1.5 rounded-lg px-3 py-1 transition">
                            <i data-lucide="play" class="h-3 w-3" aria-hidden="true"></i>
                            <span x-text="tourActive ? 'Hentikan Tur' : 'Tur Simulasi'"></span>
                        </button>
                    </div>

                    @if($user->isAdmin())
                        <button type="button"
                                @click="$dispatch('open-confirm', {
                                    title: 'Reset ke kondisi bersih?',
                                    message: 'Seluruh formulir, respons pengisian, dataset, visualisasi, dan permohonan akan dihapus bersih. Data master pengguna, kategori, dan profil portal tetap dipertahankan.',
                                    action: @js(route('panel.reset-demo')),
                                    method: 'POST',
                                    buttonText: 'Ya, reset bersih'
                                })"
                                class="inline-flex min-h-[40px] items-center gap-1.5 rounded-xl border border-white/20 bg-white/10 px-3.5 py-2 text-xs font-semibold text-white transition hover:bg-white/20">
                            <i data-lucide="rotate-ccw" class="h-3.5 w-3.5" aria-hidden="true"></i>
                            <span>Reset Bersih</span>
                        </button>
                    @endif
                </div>
            </div>

            <!-- Interactive 3D Viewport + Floating Telemetry HUD -->
            <div class="relative mt-4 grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1fr)_300px] lg:items-center">
                <div class="relative h-[310px] w-full overflow-hidden rounded-2xl border border-white/10 bg-[#070F26]/55 sm:h-[360px] lg:h-[390px]">
                    <div data-scene="walidata-pipeline"
                         data-metrics='@json($pipelineMetrics)'
                         class="h-full w-full"></div>

                    <!-- Subtle Orbit Hint -->
                    <div x-show="!hasInteracted"
                         x-transition.opacity
                         class="pointer-events-none absolute bottom-3 left-3 inline-flex items-center gap-2 rounded-full border border-white/15 bg-[#09122C]/75 px-3 py-1 text-[11px] font-medium text-slate-200 backdrop-blur-sm">
                        <i data-lucide="move-3d" class="h-3.5 w-3.5 text-[#74D4FF]" aria-hidden="true"></i>
                        <span>Seret untuk memutar · Klik fase 3D</span>
                    </div>

                    <!-- Live Hover Chip -->
                    <div x-show="hoveredStage && hoveredStage !== selectedStage"
                         x-cloak
                         class="pointer-events-none absolute top-3 left-3 inline-flex items-center gap-2 rounded-lg border border-[#38BDF8]/40 bg-[#09122C]/85 px-2.5 py-1 text-xs font-semibold text-[#BAE6FD] backdrop-blur-sm">
                        <span class="font-mono text-[#74D4FF]" x-text="stages[hoveredStage]?.code"></span>
                        <span x-text="stages[hoveredStage]?.title"></span>
                    </div>
                </div>

                <!-- Floating High-Contrast Telemetry Inspector -->
                <aside class="wd-glass-dark flex flex-col justify-between rounded-2xl p-5">
                    <div>
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-mono text-[11px] font-bold tracking-[0.14em] text-[#74D4FF] uppercase"
                                  x-text="'FASE ' + currentStage().code"></span>
                            <span class="rounded-full px-2.5 py-0.5 text-[11px] font-bold"
                                  :class="tonePillClass(currentStage().tone)"
                                  x-text="currentStage().statusLabel"></span>
                        </div>

                        <h2 class="mt-2.5 text-lg font-extrabold tracking-tight text-white"
                            x-text="currentStage().title"></h2>

                        <div class="mt-2 inline-flex items-center gap-1.5 rounded-lg border border-white/12 bg-white/[0.06] px-2.5 py-1 text-[11px] font-semibold text-[#BAE6FD]">
                            <i data-lucide="user-check" class="h-3.5 w-3.5 text-[#74D4FF]" aria-hidden="true"></i>
                            <span x-text="currentStage().actor"></span>
                        </div>

                        <div class="mt-3 rounded-xl border border-white/10 bg-white/[0.05] px-3.5 py-3">
                            <p class="font-mono text-2xl font-extrabold tracking-tight tabular-nums text-white"
                               x-text="currentStage().metric"></p>
                            <p class="mt-0.5 text-xs text-slate-300"
                               x-text="currentStage().sub"></p>
                        </div>

                        <p class="mt-3 text-xs leading-relaxed text-slate-300"
                           x-text="currentStage().desc"></p>
                    </div>

                    <div class="mt-5 flex items-center justify-between gap-2 border-t border-white/10 pt-3.5">
                        <button type="button"
                                @click="focusStage('overview')"
                                class="text-xs font-semibold text-slate-300 transition hover:text-white">
                            Reset kamera
                        </button>
                        <a :href="currentStage().url"
                           class="inline-flex min-h-[38px] items-center gap-1.5 rounded-xl bg-[#00A6F4] px-3.5 py-1.5 text-xs font-bold text-[#09122C] transition hover:bg-[#38BDF8]">
                            <span x-text="currentStage().cta"></span>
                            <i data-lucide="arrow-right" class="h-3.5 w-3.5" aria-hidden="true"></i>
                        </a>
                    </div>
                </aside>
            </div>

            <!-- Bottom 5-Stage Interactive Dock -->
            <div class="relative z-10 mt-4 grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-5" role="tablist" aria-label="Pilih tahap alur Satu Data">
                @foreach([
                    ['key' => 'formulir', 'num' => '01', 'label' => 'Instrumen', 'val' => $openFormsCount.'/'.$totalFormsCount, 'alert' => false],
                    ['key' => 'respons', 'num' => '02', 'label' => 'Respons', 'val' => $submissionsCount.' masuk', 'alert' => false],
                    ['key' => 'preprocessing', 'num' => '03', 'label' => 'Pre-proc', 'val' => $rawSubmissionsCount.' mentah', 'alert' => ($formsNeedPreprocessingCount > 0 || $rawSubmissionsCount > 0)],
                    ['key' => 'dataset', 'num' => '04', 'label' => 'Dataset', 'val' => $publishedDatasetsCount.' terbit', 'alert' => false],
                    ['key' => 'visualisasi', 'num' => '05', 'label' => 'Visualisasi', 'val' => $activeVisualizationsCount.' aktif', 'alert' => false],
                ] as $dock)
                    <button type="button"
                            role="tab"
                            :aria-selected="selectedStage === @js($dock['key'])"
                            @click="selectAndFocus(@js($dock['key']))"
                            :class="selectedStage === @js($dock['key'])
                                ? 'border-[#38BDF8] bg-white/14 text-white shadow-[0_0_0_1px_rgba(56,189,248,0.35)]'
                                : 'border-white/10 bg-white/[0.05] text-slate-200 hover:border-white/25 hover:bg-white/10'"
                            class="flex min-h-[54px] items-center justify-between gap-2 rounded-xl border px-3.5 py-2.5 text-left transition">
                        <div class="min-w-0">
                            <p class="font-mono text-[10px] font-bold tracking-wider text-[#74D4FF]">TAHAP {{ $dock['num'] }}</p>
                            <p class="truncate text-xs font-bold">{{ $dock['label'] }}</p>
                        </div>
                        <div class="flex shrink-0 items-center gap-1.5">
                            @if($dock['alert'])
                                <span class="h-2 w-2 rounded-full bg-[#FBBF24]" aria-hidden="true"></span>
                            @endif
                            <span class="font-mono text-[11px] font-bold tabular-nums text-slate-200">{{ $dock['val'] }}</span>
                        </div>
                    </button>
                @endforeach
            </div>
        </section>

        <!-- 2. Asymmetric 12-Column Telemetry Bento Grid -->
        <section class="grid grid-cols-1 gap-5 lg:grid-cols-12">
            <!-- Card A (5 cols): Indeks Kualitas & Kesehatan Data -->
            <article class="wd-bento flex flex-col justify-between p-5 sm:p-6 lg:col-span-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-mono text-[11px] font-bold tracking-[0.12em] text-[#0069A8] uppercase">Telemetri Kualitas</p>
                        <h2 class="mt-1 text-lg font-extrabold tracking-tight text-slate-900">Indeks Kesiapan Data</h2>
                    </div>
                    <a href="{{ route('panel.preprocessing') }}" class="text-xs font-bold text-[#0069A8] hover:text-[#182654]">
                        Pre-processing →
                    </a>
                </div>

                <div class="mt-5 flex flex-wrap items-center gap-6">
                    <!-- SVG Radial Gauge -->
                    <div class="relative grid h-28 w-28 shrink-0 place-items-center">
                        <svg class="h-28 w-28 -rotate-90" viewBox="0 0 100 100" aria-hidden="true">
                            <circle cx="50" cy="50" r="{{ $ringRadius }}" fill="none" stroke="#E2E8F0" stroke-width="9" />
                            <circle cx="50"
                                    cy="50"
                                    r="{{ $ringRadius }}"
                                    fill="none"
                                    stroke="{{ $qualityHealthScore === null ? '#94A3B8' : ($qualityHealthScore >= 85 ? '#047857' : ($qualityHealthScore >= 50 ? '#0069A8' : '#D97706')) }}"
                                    stroke-width="9"
                                    stroke-linecap="round"
                                    stroke-dasharray="{{ $ringCircumference }}"
                                    stroke-dashoffset="{{ $qualityHealthScore === null ? $ringCircumference : $ringOffset }}" />
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                            <span class="font-mono text-2xl font-extrabold tracking-tight tabular-nums text-slate-900">
                                {{ $qualityHealthScore !== null ? $qualityHealthScore.'%' : '—' }}
                            </span>
                            <span class="text-[10px] font-bold tracking-wider text-slate-500 uppercase">
                                {{ $qualityHealthScore !== null ? 'Valid' : 'Standby' }}
                            </span>
                        </div>
                    </div>

                    <!-- Segmented Breakdown -->
                    <div class="min-w-[180px] flex-1 space-y-3">
                        @php
                            $subDenom = max(1, $submissionsCount);
                        @endphp
                        <div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-semibold text-[#047857]">Valid (Siap Dataset)</span>
                                <span class="font-mono font-bold tabular-nums text-slate-900">{{ $validSubmissionsCount }}</span>
                            </div>
                            <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-[#047857]" style="width: {{ round(($validSubmissionsCount / $subDenom) * 100) }}%"></div>
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-semibold text-[#92400E]">Mentah (Antrean)</span>
                                <span class="font-mono font-bold tabular-nums text-slate-900">{{ $rawSubmissionsCount }}</span>
                            </div>
                            <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-amber-500" style="width: {{ round(($rawSubmissionsCount / $subDenom) * 100) }}%"></div>
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-semibold text-slate-600">Ditolak / Dikecualikan</span>
                                <span class="font-mono font-bold tabular-nums text-slate-900">{{ $rejectedSubmissionsCount }}</span>
                            </div>
                            <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-slate-400" style="width: {{ round(($rejectedSubmissionsCount / $subDenom) * 100) }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </article>

            <!-- Card B (4 cols): Publikasi Dataset & Visualisasi -->
            <article class="wd-bento flex flex-col justify-between p-5 sm:p-6 lg:col-span-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-mono text-[11px] font-bold tracking-[0.12em] text-[#0069A8] uppercase">Katalog &amp; Analitika</p>
                        <h2 class="mt-1 text-lg font-extrabold tracking-tight text-slate-900">Publikasi &amp; Grafik</h2>
                    </div>
                    <a href="{{ route('panel.dataset') }}" class="text-xs font-bold text-[#0069A8] hover:text-[#182654]">
                        Dataset →
                    </a>
                </div>

                <div class="mt-5 grid grid-cols-2 gap-3">
                    <div class="rounded-xl border border-slate-200/80 bg-[#F8FAFC] p-3.5">
                        <p class="text-xs font-semibold text-slate-600">Dataset Terbit</p>
                        <p class="mt-1 font-mono text-2xl font-extrabold tabular-nums text-slate-900">
                            {{ $publishedDatasetsCount }}<span class="text-sm font-semibold text-slate-400">/{{ $totalDatasetsCount }}</span>
                        </p>
                        <p class="mt-1 text-[11px] text-[#475569]">{{ $publicDatasetsCount }} publik · {{ $draftDatasetsCount }} draft</p>
                    </div>

                    <div class="rounded-xl border border-slate-200/80 bg-[#F8FAFC] p-3.5">
                        <p class="text-xs font-semibold text-slate-600">Grafik Dashboard</p>
                        <p class="mt-1 font-mono text-2xl font-extrabold tabular-nums text-slate-900">
                            {{ $activeVisualizationsCount }}<span class="text-sm font-semibold text-slate-400">/{{ $visualizationsCount }}</span>
                        </p>
                        <p class="mt-1 text-[11px] text-[#475569]">Tayang di Dashboard</p>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-3.5 text-xs">
                    <span class="text-[#475569]">Formulir siap dataset: <strong class="font-mono text-slate-900">{{ $formsReadyCount }}</strong></span>
                    <a href="{{ route('panel.visualisasi') }}" class="font-bold text-[#0069A8] hover:text-[#182654]">
                        Atur grafik →
                    </a>
                </div>
            </article>

            <!-- Card C (3 cols): Radar Prioritas Wali Data -->
            <article class="wd-bento flex flex-col justify-between p-5 sm:p-6 lg:col-span-3">
                <div>
                    <p class="font-mono text-[11px] font-bold tracking-[0.12em] text-[#0069A8] uppercase">Radar Tugas</p>
                    <h2 class="mt-1 text-lg font-extrabold tracking-tight text-slate-900">Prioritas</h2>
                </div>

                <ul class="mt-4 space-y-2.5 text-xs">
                    <li>
                        <a href="{{ route('panel.preprocessing') }}"
                           class="flex items-center justify-between gap-2 rounded-xl border px-3 py-2.5 transition {{ $formsNeedPreprocessingCount > 0 ? 'border-amber-200 bg-[#FFFBEB] text-[#92400E] hover:border-amber-300' : 'border-slate-200/80 bg-[#F8FAFC] text-slate-700 hover:border-[#0069A8]/40' }}">
                            <span class="font-semibold">Antrean Pre-proc</span>
                            <span class="font-mono font-bold tabular-nums">{{ $formsNeedPreprocessingCount }}</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('panel.permohonan') }}"
                           class="flex items-center justify-between gap-2 rounded-xl border px-3 py-2.5 transition {{ $pendingRequestsCount > 0 ? 'border-red-200 bg-[#FEF2F2] text-[#B91C1C] hover:border-red-300' : 'border-slate-200/80 bg-[#F8FAFC] text-slate-700 hover:border-[#0069A8]/40' }}">
                            <span class="font-semibold">Permohonan Private</span>
                            <span class="font-mono font-bold tabular-nums">{{ $pendingRequestsCount }}</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('panel.formulir') }}"
                           class="flex items-center justify-between gap-2 rounded-xl border border-slate-200/80 bg-[#F8FAFC] px-3 py-2.5 text-slate-700 transition hover:border-[#0069A8]/40">
                            <span class="font-semibold">Formulir Dibuka</span>
                            <span class="font-mono font-bold tabular-nums text-[#0069A8]">{{ $openFormsCount }}</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('panel.kategori') }}"
                           class="flex items-center justify-between gap-2 rounded-xl border border-slate-200/80 bg-[#F8FAFC] px-3 py-2.5 text-slate-700 transition hover:border-[#0069A8]/40">
                            <span class="font-semibold">Kategori Aktif</span>
                            <span class="font-mono font-bold tabular-nums text-slate-900">{{ $categoriesCount }}</span>
                        </a>
                    </li>
                </ul>
            </article>
        </section>

        <!-- 3. Asymmetric 12-Column Operational Workspace -->
        <section class="grid grid-cols-1 gap-6 lg:grid-cols-12">
            <!-- Left (7 cols): Dynamic Operational Monitor -->
            <div class="wd-bento overflow-hidden lg:col-span-7">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200/80 px-5 py-4 sm:px-6">
                    <div class="inline-flex rounded-xl border border-slate-200 bg-[#F8FAFC] p-1 text-xs font-semibold">
                        <button type="button"
                                @click="opTab = 'submissions'"
                                :class="opTab === 'submissions' ? 'bg-white text-[#0069A8] shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900'"
                                class="min-h-[34px] rounded-lg px-3.5 py-1.5 transition">
                            Kiriman terbaru
                        </button>
                        <button type="button"
                                @click="opTab = 'forms'"
                                :class="opTab === 'forms' ? 'bg-white text-[#0069A8] shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900'"
                                class="min-h-[34px] rounded-lg px-3.5 py-1.5 transition">
                            Instrumen aktif
                        </button>
                    </div>

                    <div x-show="opTab === 'submissions'" class="flex flex-wrap gap-1">
                        @foreach(['all' => 'Semua', 'mentah' => 'Mentah', 'valid' => 'Valid', 'ditolak' => 'Ditolak'] as $sk => $sl)
                            <button type="button"
                                    @click="subFilter = @js($sk)"
                                    :class="subFilter === @js($sk) ? 'bg-[#0069A8] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                    class="rounded-lg px-2.5 py-1 text-[11px] font-bold transition">
                                {{ $sl }}
                            </button>
                        @endforeach
                    </div>

                    <a x-show="opTab === 'forms'"
                       x-cloak
                       href="{{ route('panel.formulir') }}"
                       class="text-xs font-bold text-[#0069A8] hover:text-[#182654]">
                        Kelola semua →
                    </a>
                </div>

                <!-- Tab 1: Kiriman Terbaru -->
                <div x-show="opTab === 'submissions'">
                    @if($recentSubmissions->isEmpty())
                        <div class="px-6 py-12 text-center">
                            <span class="mx-auto grid h-11 w-11 place-items-center rounded-xl bg-[#F0F9FF] text-[#0069A8]">
                                <i data-lucide="clipboard-list" class="h-5 w-5" aria-hidden="true"></i>
                            </span>
                            <p class="mt-3 text-sm font-bold text-slate-800">Belum ada respons formulir.</p>
                            <p class="mt-1 text-xs text-[#475569]">Mulai dari menu Instrumen Formulir untuk membuka pengumpulan data.</p>
                            <a href="{{ route('panel.formulir') }}" class="mt-4 inline-flex min-h-[38px] items-center rounded-xl bg-[#0069A8] px-4 py-2 text-xs font-bold text-white hover:bg-[#182654]">
                                Buka Instrumen Formulir
                            </a>
                        </div>
                    @else
                        <div class="divide-y divide-slate-100">
                            <template x-for="sub in filteredSubmissions()" :key="sub.id">
                                <a :href="sub.url" class="flex items-center justify-between gap-3 px-5 py-3.5 transition hover:bg-[#F8FAFC] sm:px-6">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="rounded bg-[#F0F9FF] px-1.5 py-0.5 font-mono text-[10px] font-bold text-[#0069A8]" x-text="sub.code"></span>
                                            <p class="truncate text-sm font-semibold text-slate-900" x-text="sub.formTitle"></p>
                                        </div>
                                        <p class="mt-0.5 text-xs text-[#475569]" x-text="sub.sender + ' · ' + sub.source + ' · ' + sub.submittedAt"></p>
                                    </div>
                                    <span class="shrink-0 rounded-md px-2 py-0.5 text-[11px] font-bold"
                                          :class="sub.status === 'valid' ? 'bg-[#ECFDF5] text-[#047857]' : (sub.status === 'ditolak' ? 'bg-[#FEF2F2] text-[#B91C1C]' : 'bg-[#FFFBEB] text-[#92400E]')"
                                          x-text="sub.status === 'valid' ? 'Valid' : (sub.status === 'ditolak' ? 'Ditolak' : 'Mentah')"></span>
                                </a>
                            </template>
                            <template x-if="filteredSubmissions().length === 0">
                                <p class="px-6 py-10 text-center text-xs font-medium text-[#475569]">Tidak ada kiriman pada filter status ini.</p>
                            </template>
                        </div>
                    @endif
                </div>

                <!-- Tab 2: Instrumen Formulir Aktif -->
                <div x-show="opTab === 'forms'" x-cloak>
                    @forelse($activeFormsList as $af)
                        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-5 py-3.5 first:border-t-0 sm:px-6">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="rounded bg-[#F0F9FF] px-1.5 py-0.5 font-mono text-[10px] font-bold text-[#0069A8]">{{ $af->code }}</span>
                                    <p class="truncate text-sm font-bold text-slate-900">{{ $af->title }}</p>
                                </div>
                                <p class="mt-0.5 text-xs text-[#475569]">
                                    {{ $af->category?->name ?? 'Tanpa kategori' }} · {{ $af->submissions_count }} respons ({{ $af->valid_submissions_count }} valid / {{ $af->raw_submissions_count }} mentah)
                                </p>
                            </div>
                            <div class="flex shrink-0 items-center gap-1.5">
                                <button type="button"
                                        @click="copyFormUrl(@js(route('formulir.show', $af->code)))"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-[#0069A8] hover:text-[#0069A8]"
                                        title="Salin tautan GForm"
                                        aria-label="Salin tautan formulir">
                                    <i data-lucide="link-2" class="h-4 w-4" aria-hidden="true"></i>
                                </button>
                                <a href="{{ route('panel.preprocessing.show', $af->code) }}"
                                   class="inline-flex min-h-[36px] items-center rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 transition hover:border-[#0069A8] hover:text-[#0069A8]">
                                    Pre-proc →
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-12 text-center">
                            <p class="text-sm font-bold text-slate-800">Belum ada instrumen formulir.</p>
                            <a href="{{ route('panel.formulir') }}" class="mt-3 inline-flex min-h-[38px] items-center rounded-xl bg-[#0069A8] px-4 py-2 text-xs font-bold text-white hover:bg-[#182654]">
                                Buat Formulir Baru
                            </a>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Right (5 cols): Permohonan & Sebaran Kategori -->
            <div class="space-y-6 lg:col-span-5">
                <article class="wd-bento overflow-hidden">
                    <div class="flex items-center justify-between border-b border-slate-200/80 px-5 py-4">
                        <h2 class="text-sm font-extrabold tracking-tight text-slate-900">Permohonan perlu konfirmasi</h2>
                        <a href="{{ route('panel.permohonan') }}" class="inline-flex items-center gap-1 text-xs font-bold text-[#0069A8] hover:text-[#182654]">
                            Semua
                            <i data-lucide="arrow-right" class="h-3.5 w-3.5" aria-hidden="true"></i>
                        </a>
                    </div>
                    <div class="divide-y divide-slate-100">
                        @forelse($pendingRequests as $req)
                            <a href="{{ route('panel.permohonan') }}" class="flex w-full items-center gap-3 px-5 py-3.5 text-left transition hover:bg-[#F8FAFC]">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $req->dataset?->title ?? '-' }}</p>
                                    <p class="text-xs text-[#475569]">{{ $req->nama }} · {{ $req->requested_at?->locale('id')->translatedFormat('j M Y') }}</p>
                                </div>
                                <x-req-status :status="$req->status" />
                            </a>
                        @empty
                            <p class="px-5 py-6 text-sm text-[#475569]">Semua permohonan sudah ditangani.</p>
                        @endforelse
                    </div>
                </article>

                <article class="wd-bento p-5">
                    <div class="mb-3.5 flex items-center justify-between">
                        <div>
                            <p class="font-mono text-[10px] font-bold tracking-[0.14em] text-[#0069A8] uppercase">Taksonomi</p>
                            <h2 class="text-sm font-extrabold tracking-tight text-slate-900">Sebaran Kategori Data</h2>
                        </div>
                        <a href="{{ route('panel.kategori') }}" class="text-xs font-bold text-[#0069A8] hover:text-[#182654]">
                            Kelola →
                        </a>
                    </div>
                    @php
                        $maxCatUsage = max(1, $categoryDistribution->max(fn ($c) => $c->data_forms_count + $c->datasets_count) ?? 1);
                    @endphp
                    <div class="space-y-3">
                        @foreach($categoryDistribution as $catItem)
                            @php
                                $usage = $catItem->data_forms_count + $catItem->datasets_count;
                                $pct = $usage > 0 ? max(8, (int) round(($usage / $maxCatUsage) * 100)) : 4;
                            @endphp
                            <div>
                                <div class="flex items-center justify-between gap-2 text-xs">
                                    <span class="truncate font-semibold text-slate-800">{{ $catItem->name }}</span>
                                    <span class="shrink-0 font-mono text-[11px] font-bold tabular-nums text-slate-600">
                                        {{ $catItem->data_forms_count }}F · {{ $catItem->datasets_count }}D
                                    </span>
                                </div>
                                <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full {{ $usage > 0 ? 'bg-[#0069A8]' : 'bg-slate-200' }}" style="width: {{ $pct }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </article>
            </div>
        </section>
    </div>
@endif
@endsection

@if(! auth()->user()->isProdusen())
@push('scripts')
<script>
function waliObservatory(stages, recentSubmissions) {
    const stageOrder = ['formulir', 'respons', 'preprocessing', 'dataset', 'visualisasi'];
    return {
        stages,
        recentSubmissions,
        selectedStage: 'preprocessing',
        hoveredStage: null,
        cameraPreset: 'overview',
        hasInteracted: false,
        tourActive: false,
        tourTimer: null,
        tourIdx: 0,
        opTab: 'submissions',
        subFilter: 'all',

        init() {
            window.addEventListener('wd:stage-select', (e) => {
                const key = e?.detail?.stageKey;
                if (key && this.stages[key]) {
                    this.stopTour();
                    this.selectedStage = key;
                    this.cameraPreset = key === 'preprocessing' ? 'preprocessing' : 'custom';
                    this.hasInteracted = true;
                }
            });
            window.addEventListener('wd:stage-hover', (e) => {
                this.hoveredStage = e?.detail?.stageKey || null;
            });
            window.addEventListener('wd:stage-interacted', () => {
                this.stopTour();
                this.hasInteracted = true;
            });
            window.addEventListener('sd:motion-toggle', (e) => {
                if (e?.detail?.paused) {
                    this.stopTour();
                }
            });
            window.addEventListener('pagehide', () => this.stopTour(), { once: true });
        },

        currentStage() {
            const key = this.hoveredStage || this.selectedStage || 'preprocessing';
            return this.stages[key] || this.stages.preprocessing;
        },

        selectAndFocus(stageKey) {
            if (!this.stages[stageKey]) return;
            this.stopTour();
            this.selectedStage = stageKey;
            this.hasInteracted = true;
            this.cameraPreset = stageKey === 'preprocessing' ? 'preprocessing' : 'custom';
            window.dispatchEvent(new CustomEvent('wd:camera-focus', { detail: { stageKey } }));
        },

        focusStage(preset) {
            this.stopTour();
            this.cameraPreset = preset;
            window.dispatchEvent(new CustomEvent('wd:camera-focus', { detail: { stageKey: preset } }));
        },

        toggleTour() {
            if (this.tourActive) {
                this.stopTour();
                this.focusStage('overview');
                return;
            }
            if (window.matchMedia?.('(prefers-reduced-motion: reduce)').matches) {
                this.$dispatch('toast', { message: 'Mode gerak berkurang aktif; gunakan dok tahap di bawah untuk meninjau fase.', tone: 'info' });
                return;
            }
            this.tourActive = true;
            this.hasInteracted = true;
            this.tourIdx = 0;
            const step = () => {
                const key = stageOrder[this.tourIdx % stageOrder.length];
                this.selectedStage = key;
                this.cameraPreset = key;
                window.dispatchEvent(new CustomEvent('wd:camera-focus', { detail: { stageKey: key } }));
                this.tourIdx = (this.tourIdx + 1) % stageOrder.length;
            };
            step();
            this.tourTimer = setInterval(step, 4200);
        },

        stopTour() {
            this.tourActive = false;
            if (this.tourTimer) {
                clearInterval(this.tourTimer);
                this.tourTimer = null;
            }
        },

        tonePillClass(tone) {
            if (tone === 'amber') return 'bg-amber-400/20 text-amber-200 ring-1 ring-amber-300/35';
            if (tone === 'emerald') return 'bg-emerald-400/20 text-emerald-200 ring-1 ring-emerald-300/35';
            if (tone === 'sky') return 'bg-sky-400/20 text-[#BAE6FD] ring-1 ring-sky-300/35';
            return 'bg-white/10 text-slate-200 ring-1 ring-white/20';
        },

        filteredSubmissions() {
            if (this.subFilter === 'all') return this.recentSubmissions;
            return this.recentSubmissions.filter((s) => s.status === this.subFilter);
        },

        copyFormUrl(url) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(() => {
                    this.$dispatch('toast', { message: 'Tautan formulir disalin ke papan klip.', tone: 'success' });
                });
            } else {
                this.$dispatch('toast', { message: 'Buka tautan: ' + url, tone: 'info' });
            }
        },
    };
}
</script>
@endpush
@endif
