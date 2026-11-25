@extends('layouts.app')

@section('hideFooter', true)

@section('content')
@php
    $user = auth()->user();
    $role = $user->role;

    $navGroups = $user->isProdusen()
        ? [
            [
                'label' => 'Menu',
                'items' => [
                    ['route' => 'panel.ringkasan', 'pattern' => 'panel.ringkasan', 'label' => 'Ringkasan', 'icon' => 'layout-dashboard', 'roles' => ['produsen']],
                    ['route' => 'panel.isi', 'pattern' => 'panel.isi*', 'label' => 'Isi Formulir', 'icon' => 'file-pen', 'roles' => ['produsen']],
                    ['route' => 'panel.riwayat', 'pattern' => 'panel.riwayat*', 'label' => 'Riwayat Pengisian', 'icon' => 'history', 'roles' => ['produsen']],
                ],
            ],
        ]
        : [
            [
                'label' => 'Menu',
                'items' => [
                    ['route' => 'panel.ringkasan', 'pattern' => 'panel.ringkasan', 'label' => 'Ringkasan', 'icon' => 'layout-dashboard', 'roles' => ['wali', 'admin']],
                ],
            ],
            [
                'label' => 'Kelola Data',
                'items' => [
                    ['route' => 'panel.formulir', 'pattern' => 'panel.formulir*', 'label' => 'Instrumen Formulir', 'icon' => 'clipboard-list', 'roles' => ['wali', 'admin']],
                    ['route' => 'panel.isi', 'pattern' => 'panel.isi*', 'label' => 'Isi Formulir', 'icon' => 'file-pen', 'roles' => ['admin']],
                    ['route' => 'panel.preprocessing', 'pattern' => ['panel.preprocessing*', 'panel.data*'], 'label' => 'Pre-processing Data', 'icon' => 'sparkles', 'roles' => ['wali', 'admin'], 'badge' => $formsNeedingPreprocessingCount ?? 0, 'badgeTone' => 'amber'],
                    ['route' => 'panel.dataset', 'pattern' => 'panel.dataset*', 'label' => 'Kelola Dataset', 'icon' => 'table-2', 'roles' => ['wali', 'admin']],
                    ['route' => 'panel.visualisasi', 'pattern' => 'panel.visualisasi*', 'label' => 'Visualisasi Dashboard', 'icon' => 'bar-chart-3', 'roles' => ['wali', 'admin']],
                ],
            ],
            [
                'label' => 'Layanan',
                'items' => [
                    ['route' => 'panel.riwayat', 'pattern' => 'panel.riwayat*', 'label' => 'Riwayat Pengisian', 'icon' => 'history', 'roles' => ['wali', 'admin']],
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

<div class="wd-studio-bg relative min-h-[calc(100dvh-65px)] overflow-x-clip">
    <div class="pointer-events-none fixed inset-0 z-0 overflow-hidden opacity-75" aria-hidden="true">
        <div data-scene="ambient" class="h-full w-full"></div>
    </div>

    <div class="relative z-10 mx-auto grid max-w-[1400px] grid-cols-1 gap-6 px-4 py-6 sm:px-6 lg:grid-cols-[256px_minmax(0,1fr)] lg:px-8 lg:py-8"
         x-data="{ panelNavOpen: false }"
         @keydown.escape.window="panelNavOpen = false">

        <!-- Sidebar -->
        <aside class="relative z-20 min-w-0 lg:sticky lg:top-24 lg:self-start">
            <div class="wd-bento overflow-hidden">
                <div class="bg-[#182654] p-4 text-white">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-white/15 text-sm font-bold text-white">
                                {{ $user->initials() }}
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold text-white">{{ $user->name }}</p>
                                <p class="mt-0.5 truncate text-xs text-slate-200">
                                    {{ $user->roleLabel() }}{{ $user->jenis ? ' · '.$user->jenis : '' }}
                                </p>
                            </div>
                        </div>
                        <button type="button"
                                @click="panelNavOpen = !panelNavOpen"
                                :aria-expanded="panelNavOpen"
                                aria-label="Menu panel"
                                class="inline-flex min-h-[40px] shrink-0 items-center gap-1.5 rounded-xl border border-white/20 bg-white/10 px-3 text-xs font-semibold text-white transition hover:bg-white/20 lg:hidden">
                            <i data-lucide="menu" class="h-4 w-4" aria-hidden="true"></i>
                            <span>Menu</span>
                        </button>
                    </div>
                </div>

                <div class="p-3 lg:block" :class="panelNavOpen ? 'block' : 'hidden lg:block'">
                    <nav class="space-y-4" aria-label="Menu panel">
                        @foreach($navGroups as $group)
                            @php
                                $visibleItems = array_filter($group['items'], fn ($item) => in_array($role, $item['roles'], true));
                            @endphp
                            @if(! empty($visibleItems))
                                <div class="space-y-0.5">
                                    <p class="px-2.5 pb-1 text-[11px] font-bold tracking-wide text-slate-500 uppercase">
                                        {{ $group['label'] }}
                                    </p>
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
                                           aria-current="{{ $active ? 'page' : 'false' }}"
                                           class="group relative flex min-h-[42px] w-full items-center gap-2.5 rounded-xl px-3 py-2 text-left text-[13.5px] transition-colors {{ $active ? 'bg-[#F0F9FF] font-bold text-[#0069A8] ring-1 ring-[#BAE6FD]' : 'font-medium text-slate-700 hover:bg-slate-50 hover:text-slate-900' }}">
                                            @if($active)
                                                <span class="absolute top-2 bottom-2 left-0 w-[3px] rounded-r-full bg-[#0069A8]" aria-hidden="true"></span>
                                            @endif
                                            <i data-lucide="{{ $item['icon'] }}" class="h-4 w-4 shrink-0 {{ $active ? 'text-[#0069A8]' : 'text-slate-500 group-hover:text-[#0069A8]' }}" aria-hidden="true"></i>
                                            <span class="flex-1 truncate">{{ $item['label'] }}</span>
                                            @if($badgeVal > 0)
                                                <span class="rounded-full px-2 py-0.5 text-[11px] font-bold tabular-nums {{ $badgeTone === 'amber' ? 'bg-[#FFFBEB] text-[#92400E] ring-1 ring-amber-300/70' : 'bg-[#B91C1C] text-white' }}">
                                                    {{ $badgeVal }}
                                                </span>
                                            @endif
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        @endforeach
                    </nav>

                    <div class="mt-4 space-y-1 border-t border-slate-200/80 pt-3">
                        <button type="button"
                                data-motion-toggle
                                aria-pressed="false"
                                onclick="window.toggleSceneMotion(this)"
                                class="flex min-h-[40px] w-full items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-50 hover:text-[#0069A8]">
                            <i data-lucide="sparkles" class="h-4 w-4 shrink-0 text-[#0069A8]" aria-hidden="true"></i>
                            <span data-motion-label>Jeda latar 3D</span>
                        </button>

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
                                class="flex min-h-[42px] w-full items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 transition-colors hover:bg-red-50/70 hover:text-[#B91C1C]">
                            <i data-lucide="log-out" class="h-4 w-4 shrink-0" aria-hidden="true"></i>
                            <span>Keluar</span>
                        </button>
                    </div>
                </div>
            </div>
        </aside>

        <main class="relative z-10 min-w-0 motion-safe:animate-page-in">
            @yield('panel_content')
        </main>
    </div>
</div>
@endsection
