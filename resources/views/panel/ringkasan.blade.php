@extends('layouts.panel')

@section('title', 'Ringkasan Panel - Satu Data UNUSA')

@section('panel_content')
@php
    $user = auth()->user();
    $firstName = explode(' ', trim(preg_replace('/[ ,]+/', ' ', $user->name) ?? $user->name))[0];
@endphp

@if($user->isProdusen())
    <x-panel-head
        scene="formulir"
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
            'visualizations' => $activeVisualizationsCount,
        ];

        $preprocNeedsAttention = $formsNeedPreprocessingCount > 0 || $rawSubmissionsCount > 0;
        $stagesPayload = [
            'formulir' => [
                'key' => 'formulir',
                'code' => '01',
                'title' => 'Instrumen Formulir',
                'metric' => $openFormsCount.' Aktif',
                'statusLabel' => $openFormsCount > 0 ? 'Terbuka' : 'Belum Aktif',
                'statusTone' => 'sky',
                'desc' => 'Susun instrumen formulir untuk Produsen Data.',
                'url' => route('panel.formulir'),
                'ctaLabel' => 'Kelola Formulir',
                'alert' => false,
            ],
            'respons' => [
                'key' => 'respons',
                'code' => '02',
                'title' => 'Respons Masuk',
                'metric' => $submissionsCount.' Entri',
                'statusLabel' => $submissionsCount > 0 ? 'Tercatat' : 'Belum Ada',
                'statusTone' => 'sky',
                'desc' => 'Kiriman data dari Produsen Data.',
                'url' => route('panel.riwayat'),
                'ctaLabel' => 'Lihat Riwayat',
                'alert' => false,
            ],
            'preprocessing' => [
                'key' => 'preprocessing',
                'code' => '03',
                'title' => 'Pre-processing Data',
                'metric' => $formsNeedPreprocessingCount.' Antrean',
                'statusLabel' => $preprocNeedsAttention ? 'Perlu Validasi' : ($formsReadyCount > 0 ? 'Siap Dataset' : 'Menunggu'),
                'statusTone' => $preprocNeedsAttention ? 'amber' : 'emerald',
                'desc' => 'Validasi data formulir yang telah ditutup.',
                'url' => route('panel.preprocessing'),
                'ctaLabel' => 'Buka Pre-processing',
                'alert' => $preprocNeedsAttention,
            ],
            'dataset' => [
                'key' => 'dataset',
                'code' => '04',
                'title' => 'Kelola Dataset',
                'metric' => $publishedDatasetsCount.' Terbit',
                'statusLabel' => $publishedDatasetsCount > 0 ? 'Terpublikasi' : ($formsReadyCount > 0 ? 'Siap Disusun' : 'Menunggu'),
                'statusTone' => 'emerald',
                'desc' => 'Susun dataset resmi dari formulir siap pakai.',
                'url' => route('panel.dataset'),
                'ctaLabel' => 'Kelola Dataset',
                'alert' => false,
            ],
            'visualisasi' => [
                'key' => 'visualisasi',
                'code' => '05',
                'title' => 'Visualisasi Dashboard',
                'metric' => $activeVisualizationsCount.' Grafik',
                'statusLabel' => $activeVisualizationsCount > 0 ? 'Tayang' : 'Belum Tayang',
                'statusTone' => 'sky',
                'desc' => 'Publikasikan grafik interaktif ke Dashboard.',
                'url' => route('panel.visualisasi'),
                'ctaLabel' => 'Kelola Visualisasi',
                'alert' => false,
            ],
        ];
    @endphp

    <!-- Observatory 3D Interaktif 5 Tahap -->
    <section x-data="waliObservatory(@js($stagesPayload))" class="wd-stage-frame mb-6 p-5 sm:p-6">
        <div class="relative z-10 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <h1 class="text-2xl font-extrabold tracking-tight text-white">Ringkasan Panel</h1>
                <p class="mt-1 text-sm text-slate-200">Pantau formulir, pre-processing, dataset, dan visualisasi prodi.</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <div class="inline-flex rounded-xl border border-white/15 bg-white/10 p-1 text-xs font-semibold">
                    <button type="button"
                            @click="focusStage('overview')"
                            :class="cameraPreset === 'overview' ? 'bg-[#00A6F4] text-[#09122C] font-bold' : 'text-slate-200 hover:text-white'"
                            class="min-h-[34px] rounded-lg px-3 py-1 transition">
                        Semua
                    </button>
                    <button type="button"
                            @click="focusStage('preprocessing')"
                            :class="cameraPreset === 'preprocessing' ? 'bg-[#00A6F4] text-[#09122C] font-bold' : 'text-slate-200 hover:text-white'"
                            class="min-h-[34px] rounded-lg px-3 py-1 transition">
                        Pre-processing
                    </button>
                    <button type="button"
                            @click="focusStage('overview')"
                            class="min-h-[34px] rounded-lg px-3 py-1 text-slate-200 transition hover:text-white">
                        Reset kamera
                    </button>
                </div>

                <a href="{{ route('panel.formulir.create') }}"
                   class="inline-flex min-h-[40px] items-center justify-center gap-1.5 rounded-xl bg-[#00A6F4] px-4 py-2 text-xs font-bold text-[#09122C] transition hover:bg-[#38BDF8]">
                    <i data-lucide="plus" class="h-4 w-4" aria-hidden="true"></i>
                    <span>Buat formulir</span>
                </a>

                @if($user->isAdmin())
                    <button type="button"
                            @click="$dispatch('open-confirm', {
                                title: 'Reset ke kondisi bersih?',
                                message: 'Semua formulir, respons, dataset, dan visualisasi akan dikosongkan.',
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

        <div class="relative z-10 mt-4 grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1fr)_280px] lg:items-center">
            <div class="relative h-[300px] w-full overflow-hidden rounded-2xl border border-white/10 bg-[#070F26]/55 sm:h-[340px] lg:h-[370px]">
                <div data-scene="walidata-pipeline" data-metrics='@json($pipelineMetrics)' class="h-full w-full"></div>
                <div x-show="hoveredStage && stages[hoveredStage]"
                     x-cloak
                     class="pointer-events-none absolute top-3 left-3 inline-flex items-center gap-2 rounded-full border border-white/15 bg-[#09122C]/85 px-3 py-1 text-xs font-semibold text-white backdrop-blur">
                    <span class="font-bold text-[#74D4FF] tabular-nums" x-text="stages[hoveredStage]?.code"></span>
                    <span x-text="stages[hoveredStage]?.title"></span>
                </div>
            </div>

            <div class="wd-glass-dark rounded-2xl p-4 sm:p-5">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-bold tracking-wide text-[#74D4FF] tabular-nums" x-text="currentStage().code"></span>
                    <span class="rounded-full px-2.5 py-0.5 text-[11px] font-bold"
                          :class="currentStage().statusTone === 'amber' ? 'bg-amber-400/20 text-amber-200 ring-1 ring-amber-300/30' : (currentStage().statusTone === 'emerald' ? 'bg-emerald-400/20 text-emerald-200 ring-1 ring-emerald-300/30' : 'bg-sky-400/20 text-[#74D4FF] ring-1 ring-sky-300/30')"
                          x-text="currentStage().statusLabel"></span>
                </div>

                <h2 class="mt-2.5 text-lg font-extrabold text-white" x-text="currentStage().title"></h2>
                <p class="mt-2 text-2xl font-extrabold tracking-tight tabular-nums text-white" x-text="currentStage().metric"></p>
                <p class="mt-1.5 text-xs leading-relaxed text-slate-200" x-text="currentStage().desc"></p>

                <a :href="currentStage().url"
                   class="mt-4 inline-flex min-h-[40px] w-full items-center justify-center gap-1.5 rounded-xl bg-[#00A6F4] px-4 py-2 text-xs font-bold text-[#09122C] transition hover:bg-[#38BDF8]">
                    <span x-text="currentStage().ctaLabel"></span>
                    <i data-lucide="arrow-right" class="h-3.5 w-3.5" aria-hidden="true"></i>
                </a>
            </div>
        </div>

        <div class="relative z-10 mt-4 grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-5" role="tablist" aria-label="Pilih tahap">
            @foreach($stagesPayload as $dock)
                <button type="button"
                        role="tab"
                        :aria-selected="selectedStage === '{{ $dock['key'] }}'"
                        @click="selectStage('{{ $dock['key'] }}')"
                        :class="selectedStage === '{{ $dock['key'] }}' ? 'border-[#00A6F4] bg-white/15 ring-1 ring-[#00A6F4]/50' : 'border-white/10 bg-white/5 hover:bg-white/10'"
                        class="flex items-center justify-between gap-2 rounded-xl border px-3 py-2.5 text-left transition">
                    <div class="min-w-0">
                        <p class="truncate text-xs font-bold text-white">{{ $dock['title'] }}</p>
                        <p class="text-[11px] font-semibold tabular-nums text-slate-300">{{ $dock['metric'] }}</p>
                    </div>
                    @if($dock['alert'])
                        <span class="h-2 w-2 shrink-0 rounded-full bg-[#FBBF24]" aria-hidden="true"></span>
                    @endif
                </button>
            @endforeach
        </div>
    </section>

    <!-- Ringkasan Angka Utama -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <a href="{{ route('panel.formulir') }}" class="wd-bento-interactive p-5">
            <div class="flex items-center justify-between gap-2">
                <span class="text-xs font-semibold text-[#475569]">Formulir Aktif</span>
                <span class="grid h-8 w-8 place-items-center rounded-lg bg-[#F0F9FF] text-[#0069A8]">
                    <i data-lucide="clipboard-list" class="h-4 w-4" aria-hidden="true"></i>
                </span>
            </div>
            <p class="mt-3 text-2xl font-extrabold tracking-tight tabular-nums text-slate-900">{{ $openFormsCount }}</p>
            <p class="mt-1 text-xs tabular-nums text-[#475569]">dari {{ $totalFormsCount }} total formulir</p>
        </a>

        <a href="{{ route('panel.preprocessing') }}" class="wd-bento-interactive p-5">
            <div class="flex items-center justify-between gap-2">
                <span class="text-xs font-semibold text-[#475569]">Antrean Pre-processing</span>
                <span class="grid h-8 w-8 place-items-center rounded-lg {{ $formsNeedPreprocessingCount > 0 ? 'bg-[#FFFBEB] text-[#92400E]' : 'bg-[#ECFDF5] text-[#047857]' }}">
                    <i data-lucide="sparkles" class="h-4 w-4" aria-hidden="true"></i>
                </span>
            </div>
            <p class="mt-3 text-2xl font-extrabold tracking-tight tabular-nums text-slate-900">{{ $formsNeedPreprocessingCount }}</p>
            <p class="mt-1 text-xs tabular-nums text-[#475569]">{{ $rawSubmissionsCount }} respons menunggu validasi</p>
        </a>

        <a href="{{ route('panel.dataset') }}" class="wd-bento-interactive p-5">
            <div class="flex items-center justify-between gap-2">
                <span class="text-xs font-semibold text-[#475569]">Dataset Terbit</span>
                <span class="grid h-8 w-8 place-items-center rounded-lg bg-[#ECFDF5] text-[#047857]">
                    <i data-lucide="database" class="h-4 w-4" aria-hidden="true"></i>
                </span>
            </div>
            <p class="mt-3 text-2xl font-extrabold tracking-tight tabular-nums text-slate-900">{{ $publishedDatasetsCount }}</p>
            <p class="mt-1 text-xs tabular-nums text-[#475569]">{{ $formsReadyCount }} formulir siap disusun</p>
        </a>

        <a href="{{ route('panel.visualisasi') }}" class="wd-bento-interactive p-5">
            <div class="flex items-center justify-between gap-2">
                <span class="text-xs font-semibold text-[#475569]">Visualisasi Tampil</span>
                <span class="grid h-8 w-8 place-items-center rounded-lg bg-[#F0F9FF] text-[#0069A8]">
                    <i data-lucide="bar-chart-3" class="h-4 w-4" aria-hidden="true"></i>
                </span>
            </div>
            <p class="mt-3 text-2xl font-extrabold tracking-tight tabular-nums text-slate-900">{{ $activeVisualizationsCount }}</p>
            <p class="mt-1 text-xs tabular-nums text-[#475569]">{{ $pendingRequestsCount }} permohonan data menunggu</p>
        </a>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Daftar Formulir Terbaru -->
        <section class="wd-bento overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h2 class="text-sm font-extrabold text-slate-900">Formulir Terbaru</h2>
                <a href="{{ route('panel.formulir') }}" class="text-xs font-bold text-[#0069A8] hover:underline">Kelola semua</a>
            </div>
            @if($activeFormsList->isEmpty())
                <div class="px-5 py-10 text-center">
                    <p class="text-sm font-semibold text-slate-800">Belum ada formulir</p>
                    <p class="mt-1 text-xs text-[#475569]">Buat formulir baru untuk mulai mengumpulkan data.</p>
                    <a href="{{ route('panel.formulir.create') }}" class="mt-4 inline-flex min-h-[38px] items-center rounded-xl bg-[#0069A8] px-4 text-xs font-bold text-white hover:bg-[#005187]">
                        Buat formulir baru
                    </a>
                </div>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach($activeFormsList as $af)
                        <div class="flex items-center justify-between gap-3 px-5 py-3.5">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="rounded bg-[#F0F9FF] px-1.5 py-0.5 text-[11px] font-bold tabular-nums text-[#0069A8]">{{ $af->code }}</span>
                                    @if($af->isClosed())
                                        <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[11px] font-semibold text-slate-600">Ditutup</span>
                                    @else
                                        <span class="rounded bg-[#ECFDF5] px-1.5 py-0.5 text-[11px] font-semibold text-[#047857]">Dibuka</span>
                                    @endif
                                </div>
                                <p class="mt-1 truncate text-sm font-bold text-slate-900">{{ $af->title }}</p>
                                <p class="text-xs tabular-nums text-[#475569]">{{ $af->submissions_count }} respons</p>
                            </div>
                            @if($af->isClosed())
                                <a href="{{ route('panel.preprocessing.show', $af->code) }}" class="inline-flex h-9 shrink-0 items-center rounded-xl bg-[#0069A8] px-3 text-xs font-bold text-white hover:bg-[#005187]">
                                    Pre-processing
                                </a>
                            @else
                                <a href="{{ route('panel.formulir.edit', $af->code) }}" class="inline-flex h-9 shrink-0 items-center rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 hover:border-[#0069A8] hover:text-[#0069A8]">
                                    Kelola
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        <!-- Respons & Permohonan Terbaru -->
        <div class="space-y-6">
            <section class="wd-bento overflow-hidden">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <h2 class="text-sm font-extrabold text-slate-900">Respons Terbaru</h2>
                    <a href="{{ route('panel.riwayat') }}" class="text-xs font-bold text-[#0069A8] hover:underline">Lihat riwayat</a>
                </div>
                @if($recentSubmissions->isEmpty())
                    <p class="px-5 py-8 text-center text-xs text-[#475569]">Belum ada pengisian formulir yang masuk.</p>
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach($recentSubmissions as $sub)
                            @php
                                $subFormClosed = $sub->dataForm?->isClosed() ?? false;
                                $subUrl = ($subFormClosed && $sub->dataForm)
                                    ? route('panel.preprocessing.show', $sub->dataForm->code)
                                    : route('panel.riwayat');
                                $statusCls = $sub->status_validasi === 'valid'
                                    ? 'bg-[#ECFDF5] text-[#047857]'
                                    : ($sub->status_validasi === 'ditolak' ? 'bg-red-50 text-[#B91C1C]' : 'bg-[#FFFBEB] text-[#92400E]');
                            @endphp
                            <a href="{{ $subUrl }}" class="flex items-center justify-between gap-3 px-5 py-3.5 transition hover:bg-[#F8FAFC]">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $sub->dataForm?->title ?? 'Formulir' }}</p>
                                    <p class="truncate text-xs text-[#475569]">
                                        {{ $sub->user?->name ?? 'Responden' }} · {{ $sub->waktu_pengisian?->copy()->locale('id')->diffForHumans() ?? 'Baru saja' }}
                                    </p>
                                </div>
                                <span class="shrink-0 rounded-md px-2 py-0.5 text-[11px] font-bold {{ $statusCls }}">
                                    {{ $sub->validationLabel() }}
                                </span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>

            @if($pendingRequests->isNotEmpty())
                <section class="wd-bento overflow-hidden">
                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                        <h2 class="text-sm font-extrabold text-slate-900">Permohonan Data Menunggu</h2>
                        <a href="{{ route('panel.permohonan') }}" class="text-xs font-bold text-[#0069A8] hover:underline">Tinjau</a>
                    </div>
                    <div class="divide-y divide-slate-100">
                        @foreach($pendingRequests as $req)
                            <a href="{{ route('panel.permohonan') }}" class="flex items-center justify-between gap-3 px-5 py-3 text-xs transition hover:bg-[#F8FAFC]">
                                <div class="min-w-0">
                                    <p class="truncate font-bold text-slate-900">{{ $req->nama }}</p>
                                    <p class="truncate text-[#475569]">{{ $req->dataset?->judul ?? 'Dataset' }}</p>
                                </div>
                                <span class="shrink-0 rounded-md bg-[#FFFBEB] px-2 py-0.5 text-[11px] font-bold text-[#92400E]">Menunggu</span>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </div>
@endif
@endsection

@if(! auth()->user()->isProdusen())
@push('scripts')
<script>
function waliObservatory(stages) {
    return {
        stages,
        selectedStage: 'preprocessing',
        hoveredStage: null,
        cameraPreset: 'overview',
        interacted: false,
        init() {
            window.addEventListener('wd:stage-select', (e) => {
                const key = e?.detail?.stageKey;
                if (key && this.stages[key]) {
                    this.selectedStage = key;
                    this.cameraPreset = key;
                }
            });
            window.addEventListener('wd:stage-hover', (e) => {
                this.hoveredStage = e?.detail?.stageKey || null;
            });
            window.addEventListener('wd:stage-interacted', () => {
                this.interacted = true;
            });
        },
        currentStage() {
            return this.stages[this.selectedStage] || this.stages.preprocessing;
        },
        selectStage(key) {
            if (!this.stages[key]) return;
            this.selectedStage = key;
            this.focusStage(key);
        },
        focusStage(preset) {
            this.cameraPreset = preset || 'overview';
            window.dispatchEvent(new CustomEvent('wd:camera-focus', {
                detail: { stageKey: this.cameraPreset },
            }));
        },
    };
}
</script>
@endpush
@endif
