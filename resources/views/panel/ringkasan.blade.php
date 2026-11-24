@extends('layouts.panel')

@section('title', 'Ringkasan Panel - Satu Data UNUSA')

@section('panel_content')
@php
    $user = auth()->user();
    $firstName = explode(' ', trim(preg_replace('/[ ,]+/', ' ', $user->name) ?? $user->name))[0];
@endphp

@if($user->isProdusen())
    <x-panel-head
        title="Halo, {{ $firstName }}"
        desc="{{ $user->roleLabel() }} · {{ $user->jenis }} · {{ $user->nim }}"
    />

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <a href="{{ route('panel.isi') }}" class="sd-card sd-card-hover flex items-center gap-4 p-5 text-left">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-[#F0F9FF] text-[#0069A8]">
                <i data-lucide="file-pen" class="h-5 w-5" aria-hidden="true"></i>
            </span>
            <div>
                <p class="text-[28px] leading-none font-extrabold tracking-tight tabular-nums text-slate-900">{{ $openFormsCount }}</p>
                <p class="mt-1 text-sm text-[#475569]">Formulir yang dibuka</p>
            </div>
        </a>

        <a href="{{ route('panel.riwayat') }}" class="sd-card sd-card-hover flex items-center gap-4 p-5 text-left">
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
    <div class="sd-card divide-y divide-slate-100">
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
    <x-panel-head
        title="Halo, {{ $firstName }}"
        desc="{{ $user->roleLabel() }} · ringkasan alur data dari instrumen hingga visualisasi"
    >
        @if($user->isAdmin())
            <button
                type="button"
                @click="$dispatch('open-confirm', {
                    title: 'Reset ke kondisi bersih?',
                    message: 'Seluruh formulir, respons pengisian, dataset, visualisasi, dan permohonan akan dihapus bersih. Data master pengguna, kategori, dan profil portal tetap dipertahankan.',
                    action: @js(route('panel.reset-demo')),
                    method: 'POST',
                    buttonText: 'Ya, reset bersih'
                })"
                class="inline-flex min-h-[42px] items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
            >
                <i data-lucide="rotate-ccw" class="h-4 w-4" aria-hidden="true"></i>
                Reset ke kondisi bersih
            </button>
        @endif
    </x-panel-head>

    <!-- Pipeline Satu Data 5-Tahap -->
    <section class="mb-6 rounded-2xl border border-slate-200/80 bg-[#F6F9FC] p-5">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 class="text-sm font-bold tracking-wide text-[#182654] uppercase">Alur Pengolahan Satu Data</h2>
                <p class="mt-0.5 text-xs text-[#475569]">Data wajib melewati formulir pengumpulan dan fase Pre-processing sebelum disusun menjadi dataset publik atau visualisasi.</p>
            </div>
        </div>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <a href="{{ route('panel.formulir') }}" class="flex flex-col rounded-xl border border-slate-200 bg-white p-3.5 transition hover:border-[#0069A8]">
                <span class="font-mono text-[11px] font-bold text-[#0069A8]">TAHAP 1</span>
                <p class="mt-1 text-sm font-bold text-slate-900">Instrumen Formulir</p>
                <p class="mt-auto pt-2 text-xs text-[#475569]">
                    <span class="font-bold text-slate-900">{{ $openFormsCount }}</span> aktif / {{ $totalFormsCount }} form
                </p>
            </a>

            @if($user->isAdmin())
                <a href="{{ route('panel.isi') }}" class="flex flex-col rounded-xl border border-slate-200 bg-white p-3.5 transition hover:border-[#0069A8]">
                    <span class="font-mono text-[11px] font-bold text-[#0069A8]">TAHAP 2</span>
                    <p class="mt-1 text-sm font-bold text-slate-900">Pengisian GForm</p>
                    <p class="mt-auto pt-2 text-xs text-[#475569]">
                        <span class="font-bold text-slate-900">{{ $submissionsCount }}</span> respons masuk
                    </p>
                </a>
            @else
                <a href="{{ route('pengisian-data') }}" class="flex flex-col rounded-xl border border-slate-200 bg-white p-3.5 transition hover:border-[#0069A8]">
                    <span class="font-mono text-[11px] font-bold text-[#0069A8]">TAHAP 2</span>
                    <p class="mt-1 text-sm font-bold text-slate-900">Portal GForm</p>
                    <p class="mt-auto pt-2 text-xs text-[#475569]">
                        <span class="font-bold text-slate-900">{{ $submissionsCount }}</span> respons masuk
                    </p>
                </a>
            @endif

            <a href="{{ route('panel.preprocessing') }}" class="flex flex-col rounded-xl border {{ $formsNeedPreprocessingCount > 0 ? 'border-amber-300 bg-[#FFFBEB]/60' : 'border-slate-200 bg-white' }} p-3.5 transition hover:border-[#0069A8]">
                <span class="font-mono text-[11px] font-bold text-[#92400E]">TAHAP 3 · WAJIB</span>
                <p class="mt-1 text-sm font-bold text-slate-900">Pre-processing</p>
                <p class="mt-auto pt-2 text-xs text-[#475569]">
                    <span class="font-bold text-slate-900">{{ $rawSubmissionsCount }}</span> mentah · <span class="font-bold text-[#047857]">{{ $formsReadyCount }}</span> siap
                </p>
            </a>

            <a href="{{ route('panel.dataset') }}" class="flex flex-col rounded-xl border border-slate-200 bg-white p-3.5 transition hover:border-[#0069A8]">
                <span class="font-mono text-[11px] font-bold text-[#0069A8]">TAHAP 4</span>
                <p class="mt-1 text-sm font-bold text-slate-900">Dataset Terolah</p>
                <p class="mt-auto pt-2 text-xs text-[#475569]">
                    <span class="font-bold text-slate-900">{{ $publishedDatasetsCount }}</span> terbit · {{ $draftDatasetsCount }} draft
                </p>
            </a>

            <a href="{{ route('panel.visualisasi') }}" class="flex flex-col rounded-xl border border-slate-200 bg-white p-3.5 transition hover:border-[#0069A8]">
                <span class="font-mono text-[11px] font-bold text-[#0069A8]">TAHAP 5</span>
                <p class="mt-1 text-sm font-bold text-slate-900">Visualisasi</p>
                <p class="mt-auto pt-2 text-xs text-[#475569]">
                    <span class="font-bold text-slate-900">{{ $visualizationsCount }}</span> grafik aktif
                </p>
            </a>
        </div>
    </section>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <a href="{{ route('panel.formulir') }}" class="sd-card sd-card-hover flex items-center gap-4 p-5 text-left">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-[#F0F9FF] text-[#0069A8]">
                <i data-lucide="clipboard-list" class="h-5 w-5" aria-hidden="true"></i>
            </span>
            <div>
                <p class="text-[28px] leading-none font-extrabold tracking-tight tabular-nums text-slate-900">{{ $openFormsCount }}</p>
                <p class="mt-1 text-sm text-[#475569]">Formulir dibuka</p>
            </div>
        </a>

        <a href="{{ route('panel.preprocessing') }}" class="sd-card sd-card-hover flex items-center gap-4 p-5 text-left">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-[#F0F9FF] text-[#0069A8]">
                <i data-lucide="sparkles" class="h-5 w-5" aria-hidden="true"></i>
            </span>
            <div>
                <p class="text-[28px] leading-none font-extrabold tracking-tight tabular-nums text-slate-900">{{ $validSubmissionsCount }}/{{ $submissionsCount }}</p>
                <p class="mt-1 text-sm text-[#475569]">Isian valid / total</p>
            </div>
        </a>

        <a href="{{ route('panel.dataset') }}" class="sd-card sd-card-hover flex items-center gap-4 p-5 text-left">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-[#F0F9FF] text-[#0069A8]">
                <i data-lucide="table-2" class="h-5 w-5" aria-hidden="true"></i>
            </span>
            <div>
                <p class="text-[28px] leading-none font-extrabold tracking-tight tabular-nums text-slate-900">{{ $publicDatasetsCount }}/{{ $totalDatasetsCount }}</p>
                <p class="mt-1 text-sm text-[#475569]">Dataset publik / total</p>
            </div>
        </a>

        <a href="{{ route('panel.permohonan') }}" class="sd-card sd-card-hover flex items-center gap-4 p-5 text-left">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-[#F0F9FF] text-[#0069A8]">
                <i data-lucide="inbox" class="h-5 w-5" aria-hidden="true"></i>
            </span>
            <div>
                <p class="text-[28px] leading-none font-extrabold tracking-tight tabular-nums text-slate-900">{{ $pendingRequestsCount }}</p>
                <p class="mt-1 text-sm text-[#475569]">Permohonan menunggu</p>
            </div>
        </a>
    </div>

    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="min-w-0">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="font-bold text-slate-900">Kiriman terbaru</h2>
                <a href="{{ route('panel.preprocessing') }}" class="flex items-center gap-1 text-sm font-semibold text-[#0069A8] hover:text-[#182654]">
                    Pre-processing
                    <i data-lucide="arrow-right" class="h-4 w-4" aria-hidden="true"></i>
                </a>
            </div>
            <div class="sd-card divide-y divide-slate-100">
                @forelse($recentSubmissions as $sub)
                    <div class="flex items-center justify-between gap-3 px-5 py-3.5">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-900">{{ $sub->dataForm?->title ?? '(formulir dihapus)' }}</p>
                            <p class="text-xs text-[#475569]">{{ $sub->user?->name ?? '-' }} · {{ $sub->submittedFormatted() }}</p>
                        </div>
                        <span class="shrink-0 rounded-md px-2 py-0.5 text-[11px] font-bold {{ $sub->status_validasi === 'valid' ? 'bg-[#ECFDF5] text-[#047857]' : ($sub->status_validasi === 'ditolak' ? 'bg-[#FEF2F2] text-[#B91C1C]' : 'bg-[#FFFBEB] text-[#92400E]') }}">
                            {{ $sub->status_validasi === 'valid' ? 'Valid' : ($sub->status_validasi === 'ditolak' ? 'Ditolak' : 'Mentah') }}
                        </span>
                    </div>
                @empty
                    <div class="px-5 py-8 text-center">
                        <p class="text-sm font-semibold text-slate-700">Belum ada respons formulir.</p>
                        <p class="mt-1 text-xs text-[#475569]">Mulai dari menu Instrumen Formulir untuk membuka pengumpulan data.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="min-w-0">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="font-bold text-slate-900">Permohonan perlu konfirmasi</h2>
                <a href="{{ route('panel.permohonan') }}" class="flex items-center gap-1 text-sm font-semibold text-[#0069A8] hover:text-[#182654]">
                    Semua
                    <i data-lucide="arrow-right" class="h-4 w-4" aria-hidden="true"></i>
                </a>
            </div>
            <div class="sd-card divide-y divide-slate-100">
                @forelse($pendingRequests as $req)
                    <a href="{{ route('panel.permohonan') }}" class="flex w-full items-center gap-3 px-5 py-3.5 text-left transition hover:bg-slate-50/70">
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
        </div>
    </div>
@endif
@endsection
