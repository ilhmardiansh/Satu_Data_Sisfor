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
            'label' => 'Pengumpulan data',
            'items' => [
                ['route' => 'panel.isi', 'pattern' => 'panel.isi*', 'label' => 'Isi Formulir', 'icon' => 'file-pen', 'roles' => ['produsen']],
                ['route' => 'panel.formulir', 'pattern' => 'panel.formulir*', 'label' => 'Kelola Formulir', 'icon' => 'clipboard-list', 'roles' => ['wali', 'admin']],
                ['route' => 'panel.riwayat', 'pattern' => 'panel.riwayat*', 'label' => 'Riwayat Formulir', 'icon' => 'history', 'roles' => ['produsen', 'wali', 'admin']],
            ],
        ],
        [
            'label' => 'Pengelolaan data',
            'items' => [
                ['route' => 'panel.data', 'pattern' => 'panel.data*', 'label' => 'Data Terkumpul', 'icon' => 'database', 'roles' => ['wali', 'admin']],
                ['route' => 'panel.kategori', 'pattern' => 'panel.kategori*', 'label' => 'Kategori', 'icon' => 'folder-tree', 'roles' => ['wali', 'admin']],
                ['route' => 'panel.dataset', 'pattern' => 'panel.dataset*', 'label' => 'Kelola Dataset', 'icon' => 'table-2', 'roles' => ['wali', 'admin']],
                ['route' => 'panel.visualisasi', 'pattern' => 'panel.visualisasi*', 'label' => 'Visualisasi', 'icon' => 'bar-chart-3', 'roles' => ['wali', 'admin']],
                ['route' => 'panel.permohonan', 'pattern' => 'panel.permohonan*', 'label' => 'Permohonan Data', 'icon' => 'inbox', 'roles' => ['wali', 'admin'], 'badge' => $pendingRequestsCount ?? 0],
            ],
        ],
        [
            'label' => 'Administrasi',
            'items' => [
                ['route' => 'panel.pengguna', 'pattern' => 'panel.pengguna*', 'label' => 'Pengguna & Peran', 'icon' => 'users', 'roles' => ['admin']],
                ['route' => 'panel.konten', 'pattern' => 'panel.konten*', 'label' => 'Konten Beranda', 'icon' => 'layout-template', 'roles' => ['admin']],
            ],
        ],
    ];
@endphp

<div class="relative mx-auto grid max-w-[1200px] grid-cols-1 gap-6 px-4 py-8 sm:px-8 lg:grid-cols-[240px_minmax(0,1fr)]">
    <div data-scene="ambient"
         aria-hidden="true"
         class="pointer-events-none absolute top-2 right-8 hidden h-32 w-72 opacity-65 lg:block"
         style="mask-image: radial-gradient(closest-side, #000 58%, transparent 98%); -webkit-mask-image: radial-gradient(closest-side, #000 58%, transparent 98%);"></div>
    <aside class="relative z-10 min-w-0 lg:sticky lg:top-24 lg:self-start">
        <div class="sd-card p-4">
            <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-gradient-to-b from-brand to-brand-dark text-sm font-bold text-white shadow-[0_2px_6px_rgba(68,114,208,0.35),inset_0_1px_0_rgba(255,255,255,0.25)]">
                    {{ $user->initials() }}
                </span>
                <div class="min-w-0">
                    <p class="truncate text-sm font-bold text-slate-900">{{ $user->name }}</p>
                    <p class="text-xs text-slate-500">
                        {{ $user->roleLabel() }}{{ $user->jenis ? ' · '.$user->jenis : '' }}
                    </p>
                </div>
            </div>

            <nav class="mt-3 flex gap-1 overflow-x-auto lg:block lg:space-y-4 lg:overflow-visible" aria-label="Menu panel">
                @foreach($navGroups as $group)
                    @php
                        $visibleItems = array_filter($group['items'], fn ($item) => in_array($role, $item['roles'], true));
                    @endphp
                    @if(! empty($visibleItems))
                        <div class="flex gap-1 lg:block lg:space-y-0.5">
                            @if($group['label'] !== '')
                                <p class="hidden px-3 pb-1 text-[11px] font-bold tracking-wider text-slate-400 uppercase lg:block">
                                    {{ $group['label'] }}
                                </p>
                            @endif
                            @foreach($visibleItems as $item)
                                @php $active = request()->routeIs($item['pattern']); @endphp
                                <a href="{{ route($item['route']) }}"
                                   class="flex shrink-0 items-center gap-2.5 rounded-xl px-3 py-2 text-left text-sm whitespace-nowrap transition lg:w-full {{ $active ? 'bg-sky-50/90 font-bold text-brand-dark ring-1 ring-sky-200/80 shadow-2xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                                    <i data-lucide="{{ $item['icon'] }}" class="h-4 w-4 shrink-0"></i>
                                    <span class="flex-1">{{ $item['label'] }}</span>
                                    @if(($item['badge'] ?? 0) > 0)
                                        <span class="rounded-full bg-[#DC3545] px-1.5 text-[11px] font-bold text-white">
                                            {{ $item['badge'] }}
                                        </span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    @endif
                @endforeach
            </nav>

            <form action="{{ route('logout') }}" method="POST" class="mt-4 border-t border-slate-100 pt-3">
                @csrf
                <button type="submit" class="flex w-full items-center gap-2.5 rounded-xl px-3 py-1.5 text-sm font-semibold text-slate-600 transition hover:text-[#DC3545]">
                    <i data-lucide="log-out" class="h-4 w-4"></i>
                    Keluar
                </button>
            </form>
        </div>
    </aside>

    <section class="relative z-10 min-w-0 motion-safe:animate-page-in">
        @yield('panel_content')
    </section>
</div>
@endsection
