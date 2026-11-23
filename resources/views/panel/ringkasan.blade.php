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
            <span class="grid h-11 w-11 place-items-center rounded-xl bg-sky-50 text-brand-dark shadow-2xs ring-1 ring-sky-200/70">
                <i data-lucide="file-pen" class="h-5 w-5"></i>
            </span>
            <div>
                <p class="text-[28px] leading-none font-extrabold tracking-tight tabular-nums text-slate-900">{{ $openFormsCount }}</p>
                <p class="mt-1 text-sm text-slate-500">Formulir yang dibuka</p>
            </div>
        </a>

        <a href="{{ route('panel.riwayat') }}" class="sd-card sd-card-hover flex items-center gap-4 p-5 text-left">
            <span class="grid h-11 w-11 place-items-center rounded-xl bg-sky-50 text-brand-dark shadow-2xs ring-1 ring-sky-200/70">
                <i data-lucide="history" class="h-5 w-5"></i>
            </span>
            <div>
                <p class="text-[28px] leading-none font-extrabold tracking-tight tabular-nums text-slate-900">{{ $mySubmissionsCount }}</p>
                <p class="mt-1 text-sm text-slate-500">Formulir telah dikirim</p>
            </div>
        </a>
    </div>

    <h2 class="mt-8 mb-3 font-bold text-slate-900">Tenggat terdekat</h2>
    <div class="sd-card divide-y divide-slate-100">
        @forelse($nearestForms as $f)
            <a href="{{ route('panel.isi') }}" class="flex w-full items-center gap-4 px-5 py-4 text-left transition hover:bg-slate-50/70">
                <i data-lucide="calendar-clock" class="h-5 w-5 text-amber-600"></i>
                <div class="min-w-0 flex-1">
                    <p class="truncate font-semibold text-slate-900">{{ $f->title }}</p>
                    <p class="text-xs text-slate-500">Tenggat {{ $f->deadlineFormatted() }}</p>
                </div>
                <span class="text-xs font-bold text-amber-700">{{ $f->timeLeftLabel() }}</span>
            </a>
        @empty
            <p class="px-5 py-6 text-sm text-slate-500">Tidak ada formulir yang sedang dibuka.</p>
        @endforelse
    </div>
@else
    <x-panel-head
        title="Halo, {{ $firstName }}"
        desc="{{ $user->roleLabel() }} · ringkasan pengelolaan Satu Data"
    >
        @if($user->isAdmin())
            <button
                type="button"
                @click="$dispatch('open-confirm', {
                    title: 'Reset data contoh?',
                    message: 'Seluruh perubahan data di basis data akan dikembalikan ke data contoh awal.',
                    action: @js(route('panel.reset-demo')),
                    method: 'POST',
                    buttonText: 'Reset'
                })"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
            >
                <i data-lucide="rotate-ccw" class="h-4 w-4"></i>
                Reset data contoh
            </button>
        @endif
    </x-panel-head>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <a href="{{ route('panel.formulir') }}" class="sd-card sd-card-hover flex items-center gap-4 p-5 text-left">
            <span class="grid h-11 w-11 place-items-center rounded-xl bg-sky-50 text-brand-dark shadow-2xs ring-1 ring-sky-200/70">
                <i data-lucide="clipboard-list" class="h-5 w-5"></i>
            </span>
            <div>
                <p class="text-[28px] leading-none font-extrabold tracking-tight tabular-nums text-slate-900">{{ $openFormsCount }}</p>
                <p class="mt-1 text-sm text-slate-500">Formulir dibuka</p>
            </div>
        </a>

        <a href="{{ route('panel.data') }}" class="sd-card sd-card-hover flex items-center gap-4 p-5 text-left">
            <span class="grid h-11 w-11 place-items-center rounded-xl bg-sky-50 text-brand-dark shadow-2xs ring-1 ring-sky-200/70">
                <i data-lucide="database" class="h-5 w-5"></i>
            </span>
            <div>
                <p class="text-[28px] leading-none font-extrabold tracking-tight tabular-nums text-slate-900">{{ $submissionsCount }}</p>
                <p class="mt-1 text-sm text-slate-500">Isian terkumpul</p>
            </div>
        </a>

        <a href="{{ route('panel.dataset') }}" class="sd-card sd-card-hover flex items-center gap-4 p-5 text-left">
            <span class="grid h-11 w-11 place-items-center rounded-xl bg-sky-50 text-brand-dark shadow-2xs ring-1 ring-sky-200/70">
                <i data-lucide="table-2" class="h-5 w-5"></i>
            </span>
            <div>
                <p class="text-[28px] leading-none font-extrabold tracking-tight tabular-nums text-slate-900">{{ $publicDatasetsCount }}/{{ $totalDatasetsCount }}</p>
                <p class="mt-1 text-sm text-slate-500">Dataset publik / total</p>
            </div>
        </a>

        <a href="{{ route('panel.permohonan') }}" class="sd-card sd-card-hover flex items-center gap-4 p-5 text-left">
            <span class="grid h-11 w-11 place-items-center rounded-xl bg-sky-50 text-brand-dark shadow-2xs ring-1 ring-sky-200/70">
                <i data-lucide="inbox" class="h-5 w-5"></i>
            </span>
            <div>
                <p class="text-[28px] leading-none font-extrabold tracking-tight tabular-nums text-slate-900">{{ $pendingRequestsCount }}</p>
                <p class="mt-1 text-sm text-slate-500">Permohonan menunggu</p>
            </div>
        </a>
    </div>

    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="min-w-0">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="font-bold text-slate-900">Kiriman terbaru</h2>
                <a href="{{ route('panel.riwayat') }}" class="flex items-center gap-1 text-sm font-semibold text-sky-600 hover:text-sky-700">
                    Riwayat
                    <i data-lucide="arrow-right" class="h-4 w-4"></i>
                </a>
            </div>
            <div class="sd-card divide-y divide-slate-100">
                @forelse($recentSubmissions as $sub)
                    <div class="px-5 py-3.5">
                        <p class="truncate text-sm font-semibold text-slate-900">{{ $sub->dataForm?->title ?? '(formulir dihapus)' }}</p>
                        <p class="text-xs text-slate-500">{{ $sub->user?->name ?? '-' }} · {{ $sub->submittedFormatted() }}</p>
                    </div>
                @empty
                    <p class="px-5 py-6 text-sm text-slate-500">Belum ada kiriman masuk.</p>
                @endforelse
            </div>
        </div>

        <div class="min-w-0">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="font-bold text-slate-900">Permohonan perlu konfirmasi</h2>
                <a href="{{ route('panel.permohonan') }}" class="flex items-center gap-1 text-sm font-semibold text-sky-600 hover:text-sky-700">
                    Semua
                    <i data-lucide="arrow-right" class="h-4 w-4"></i>
                </a>
            </div>
            <div class="sd-card divide-y divide-slate-100">
                @forelse($pendingRequests as $req)
                    <a href="{{ route('panel.permohonan') }}" class="flex w-full items-center gap-3 px-5 py-3.5 text-left transition hover:bg-slate-50/70">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-slate-900">{{ $req->dataset?->title ?? '-' }}</p>
                            <p class="text-xs text-slate-500">{{ $req->nama }} · {{ $req->requested_at?->locale('id')->translatedFormat('j M Y') }}</p>
                        </div>
                        <x-req-status :status="$req->status" />
                    </a>
                @empty
                    <p class="px-5 py-6 text-sm text-slate-500">Semua permohonan sudah ditangani.</p>
                @endforelse
            </div>
        </div>
    </div>
@endif
@endsection
