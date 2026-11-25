@extends('layouts.panel')

@section('title', 'Akses Ditolak - Panel Satu Data UNUSA')

@section('panel_content')
@php
    $allowedLabels = array_map(
        fn (string $r): string => \App\Models\User::ROLE_LABELS[$r] ?? ucfirst($r),
        $allowedRoles ?? []
    );
@endphp
<div class="rounded-2xl border border-slate-200 bg-surface px-6 py-16 text-center">
    <i data-lucide="shield-alert" class="mx-auto h-10 w-10 text-[#DC3545]"></i>
    <h1 class="mt-4 text-xl font-bold text-slate-900">Akses ditolak</h1>
    <p class="mt-1 text-sm text-slate-500">
        Halaman ini hanya tersedia untuk {{ implode(', ', $allowedLabels) ?: 'peran berwenang' }}.
    </p>
    <a href="{{ route('panel.ringkasan') }}" class="mt-6 inline-flex items-center justify-center gap-2 rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-700">
        Ke ringkasan
    </a>
</div>
@endsection
