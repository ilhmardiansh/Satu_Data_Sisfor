@extends('layouts.app')

@section('hideFooter', true)

@section('content')
@php
    $user = auth()->user();
    $role = $user->role;

    $navGroups = [
        [
            'label' => '',
            'items' => [
                ['route' => 'panel.ringkasan', 'pattern' => 'panel.ringkasan', 'label' => 'Ringkasan', 'icon' => 'layout-dashboard', 'roles' => ['produsen', 'wali', 'admin']],
            ],
        ],
        [
            'label' => 'Alur Satu Data',
            'items' => [
                ['route' => 'panel.formulir', 'pattern' => 'panel.formulir*', 'label' => 'Instrumen Formulir', 'icon' => 'clipboard-list', 'roles' => ['wali', 'admin']],
                ['route' => 'panel.isi', 'pattern' => 'panel.isi*', 'label' => 'Isi Formulir', 'icon' => 'file-pen', 'roles' => ['produsen', 'admin']],
                ['route' => 'panel.preprocessing', 'pattern' => ['panel.preprocessing*', 'panel.data*'], 'label' => 'Pre-processing Data', 'icon' => 'sparkles', 'roles' => ['wali', 'admin'], 'badge' => $formsNeedingPreprocessingCount ?? 0, 'badgeTone' => 'amber'],
                ['route' => 'panel.dataset', 'pattern' => 'panel.dataset*', 'label' => 'Kelola Dataset', 'icon' => 'table-2', 'roles' => ['wali', 'admin']],
                ['route' => 'panel.visualisasi', 'pattern' => 'panel.visualisasi*', 'label' => 'Visualisasi Dashboard', 'icon' => 'bar-chart-3', 'roles' => ['wali', 'admin']],
            ],
        ],
        [
            'label' => 'Layanan & Referensi',
            'items' => [
                ['route' => 'panel.riwayat', 'pattern' => 'panel.riwayat*', 'label' => 'Riwayat Pengisian', 'icon' => 'history', 'roles' => ['produsen', 'wali', 'admin']],
                ['route' => 'panel.permohonan', 'pattern' => 'panel.permohonan*', 'label' => 'Permohonan Data', 'icon' => 'inbox', 'roles' => ['wali', 'admin'], 'badge' => $pendingRequestsCount ?? 0, 'badgeTone' => 'red'],
                ['route' => 'panel.kategori', 'pattern' => 'panel.kategori*', 'label' => 'Kategori Data', 'icon' => 'folder-tree', 'roles' => ['wali', 'admin']],
            ],
        ],
        [
            'label' => 'Administrasi',
            'items' => [
                ['route' => 'panel.pengguna', 'pattern' => 'panel.pengguna*', 'label' => 'Pengguna & Peran', 'icon' => 'users', 'roles' => ['admin']],
                ['route' => 'panel.konten', 'pattern' => 'panel.konten*', 'label' => 'Konten Portal', 'icon' => 'layout-template', 'roles' => ['admin']],
            ],
        ],
    ];
@endphp

<div class="relative mx-auto grid max-w-[1200px] grid-cols-1 gap-6 px-4 py-8 sm:px-8 lg:grid-cols-[248px_minmax(0,1fr)]"
     x-data="{ panelNavOpen: false }"
     @keydown.escape.window="panelNavOpen = false">
    <div data-scene="ambient"
         aria-hidden="true"
         class="pointer-events-none absolute top-2 right-8 hidden h-28 w-64 opacity-50 lg:block"
         style="mask-image: radial-gradient(closest-side, #000 58%, transparent 98%); -webkit-mask-image: radial-gradient(closest-side, #000 58%, transparent 98%);"></div>

    <aside class="relative z-10 min-w-0 lg:sticky lg:top-24 lg:self-start">
        <div class="rounded-2xl border border-slate-200/80 bg-[#F6F9FC] p-4">
            <div class="flex items-center justify-between gap-3 border-b border-slate-200/80 pb-4">
                <div class="flex min-w-0 items-center gap-3">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-[#0069A8] text-sm font-bold text-white">
                        {{ $user->initials() }}
                    </span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold text-slate-900">{{ $user->name }}</p>
                        <p class="truncate text-xs text-[#475569]">
                            {{ $user->roleLabel() }}{{ $user->jenis ? ' · '.$user->jenis : '' }}
                        </p>
                    </div>
                </div>
                <button type="button"
                        @click="panelNavOpen = !panelNavOpen"
                        :aria-expanded="panelNavOpen"
                        aria-label="Menu panel internal"
                        class="inline-flex min-h-[44px] shrink-0 items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 lg:hidden">
                    <i data-lucide="menu" class="h-4 w-4" aria-hidden="true"></i>
                    <span>Menu</span>
                </button>
            </div>

            <div class="mt-3 lg:block" :class="panelNavOpen ? 'block' : 'hidden lg:block'">
                <nav class="space-y-4" aria-label="Menu panel">
                    @foreach($navGroups as $group)
                        @php
                            $visibleItems = array_filter($group['items'], fn ($item) => in_array($role, $item['roles'], true));
                        @endphp
                        @if(! empty($visibleItems))
                            <div class="space-y-1">
                                @if($group['label'] !== '')
                                    <p class="px-3 pb-1 text-xs font-bold text-[#475569]">
                                        {{ $group['label'] }}
                                    </p>
                                @endif
                                @foreach($visibleItems as $item)
                                    @php
                                        $active = is_array($item['pattern'])
                                            ? request()->routeIs(...$item['pattern'])
                                            : request()->routeIs($item['pattern']);
                                        $badgeVal = (int) ($item['badge'] ?? 0);
                                        $badgeTone = $item['badgeTone'] ?? 'red';
                                    @endphp
                                    <a href="{{ route($item['route']) }}"
                                       @click="panelNavOpen = false"
                                       class="flex min-h-[44px] w-full items-center gap-2.5 rounded-xl px-3 py-2 text-left text-sm transition-colors {{ $active ? 'bg-white font-bold text-[#0069A8] ring-1 ring-sky-200' : 'text-slate-700 hover:bg-white hover:text-slate-900' }}">
                                        <i data-lucide="{{ $item['icon'] }}" class="h-4 w-4 shrink-0" aria-hidden="true"></i>
                                        <span class="flex-1 truncate">{{ $item['label'] }}</span>
                                        @if($badgeVal > 0)
                                            <span class="rounded-full px-2 py-0.5 text-[11px] font-bold {{ $badgeTone === 'amber' ? 'bg-[#FFFBEB] text-[#92400E] ring-1 ring-amber-200' : 'bg-[#B91C1C] text-white' }}">
                                                {{ $badgeVal }}
                                            </span>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    @endforeach
                </nav>

                <div class="mt-4 border-t border-slate-200/80 pt-3">
                    <button type="button"
                            @click="$dispatch('open-confirm', {
                                title: 'Keluar dari sesi?',
                                message: 'Sesi akses Anda akan diakhiri dan Anda akan diarahkan kembali ke halaman masuk.',
                                action: @js(route('logout')),
                                method: 'POST',
                                buttonText: 'Ya, keluar',
                                tone: 'primary',
                                warningText: ''
                            })"
                            class="flex min-h-[44px] w-full items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 transition-colors hover:bg-white hover:text-[#B91C1C]">
                        <i data-lucide="log-out" class="h-4 w-4" aria-hidden="true"></i>
                        Keluar
                    </button>
                </div>
            </div>
        </div>
    </aside>

    <main class="relative z-10 min-w-0 motion-safe:animate-page-in">
        @yield('panel_content')
    </main>
</div>
@endsection
