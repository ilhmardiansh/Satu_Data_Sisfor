@props(['status' => 'menunggu'])

@php
    $cls = match ($status) {
        'disetujui' => 'bg-emerald-50 text-emerald-700',
        'ditolak' => 'bg-red-50 text-[#b02a37]',
        default => 'bg-sky-100/80 text-sky-700',
    };
    $label = match ($status) {
        'disetujui' => 'Disetujui',
        'ditolak' => 'Ditolak',
        default => 'Menunggu',
    };
@endphp

<span class="inline-flex rounded-md px-2 py-0.5 text-[11px] font-bold {{ $cls }}">
    {{ $label }}
</span>
