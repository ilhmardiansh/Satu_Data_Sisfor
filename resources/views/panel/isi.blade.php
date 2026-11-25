@extends('layouts.panel')

@section('title', 'Isi Formulir - Panel Satu Data UNUSA')

@section('panel_content')
<div x-data="{ }">
    <x-panel-head
        scene="formulir"
        title="Isi Formulir Pengumpulan Data"
        desc="Pilih formulir aktif untuk mulai mengisi data."
    />

    @if($forms->isNotEmpty())
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            @foreach($forms as $f)
                @php
                    $mine = (int) ($myCounts[$f->id] ?? 0);
                    $urgent = $f->isUrgent();
                    $blockedOnce = $f->batasi_satu_respon && $mine > 0;
                @endphp
                <div class="flex flex-col rounded-2xl border border-slate-200 bg-surface p-5">
                    <div class="flex flex-wrap items-center gap-2 text-xs text-[#475569]">
                        <span class="rounded-md bg-[#F0F9FF] px-2 py-0.5 font-bold text-[#0069A8]">
                            {{ $f->category?->name }}
                        </span>
                        <span>{{ $f->tahun }} · Semester {{ $f->periode }}</span>
                        @if($f->batasi_satu_respon)
                            <span class="rounded-md bg-[#FFFBEB] px-2 py-0.5 font-semibold text-[#92400E]">1x/akun</span>
                        @endif
                    </div>
                    <h3 class="mt-3 text-lg font-bold text-slate-900">{{ $f->title }}</h3>
                    <p class="mt-1 line-clamp-2 text-sm text-[#475569]">{{ $f->description }}</p>

                    <div class="mt-4 flex flex-wrap gap-x-5 gap-y-1 text-[13px] text-[#475569]">
                        <span class="flex items-center gap-1.5 font-semibold {{ $urgent ? 'text-[#B91C1C]' : 'text-[#92400E]' }}">
                            <i data-lucide="calendar-clock" class="h-4 w-4" aria-hidden="true"></i>
                            {{ $f->timeLeftLabel() }}
                        </span>
                        <span>{{ count($f->fields ?? []) }} pertanyaan</span>
                        @if($mine > 0)
                            <span class="flex items-center gap-1 font-semibold text-[#047857]">
                                <i data-lucide="check-circle-2" class="h-4 w-4" aria-hidden="true"></i>
                                Sudah dikirim {{ $mine }}x
                            </span>
                        @endif
                    </div>

                    <div class="mt-5 flex justify-end border-t border-slate-100 pt-4">
                        <a href="{{ route('formulir.show', $f->code) }}"
                           class="inline-flex min-h-[42px] items-center justify-center gap-2 rounded-xl {{ $blockedOnce ? 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50' : 'bg-[#0069A8] text-white hover:bg-[#182654]' }} px-4 py-2 text-sm font-semibold transition">
                            <i data-lucide="file-pen" class="h-4 w-4" aria-hidden="true"></i>
                            {{ $blockedOnce ? 'Lihat detail isian' : ($mine > 0 ? 'Isi formulir lagi' : 'Buka formulir') }}
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="rounded-2xl border border-dashed border-slate-300 bg-surface px-6 py-14 text-center">
            <span class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-slate-100 text-slate-500">
                <i data-lucide="clipboard-list" class="h-6 w-6" aria-hidden="true"></i>
            </span>
            <p class="mt-4 font-bold text-slate-800">Tidak ada formulir yang dibuka</p>
            <p class="mt-1 text-sm text-[#475569]">Formulir baru akan muncul di sini setelah Wali Data membuatnya di menu Instrumen Formulir.</p>
        </div>
    @endif
</div>
@endsection
