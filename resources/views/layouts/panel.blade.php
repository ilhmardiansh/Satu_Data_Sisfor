@extends('layouts.app')

@section('hideFooter', true)

@section('content')
@php
    $user = auth()->user();
    $role = $user->role;

    $navGroups = [
        [
            'code' => '00',
            'label' => 'Ikhtisar',
            'items' => [
                ['route' => 'panel.ringkasan', 'pattern' => 'panel.ringkasan', 'label' => 'Ringkasan', 'desc' => 'Observatorium 3D & radar tugas harian', 'icon' => 'layout-dashboard', 'roles' => ['produsen', 'wali', 'admin']],
            ],
        ],
        [
            'code' => '01',
            'label' => 'Alur Satu Data',
            'items' => [
                ['route' => 'panel.formulir', 'pattern' => 'panel.formulir*', 'label' => 'Instrumen Formulir', 'desc' => 'Rancang & kelola formulir GForm', 'icon' => 'clipboard-list', 'roles' => ['wali', 'admin']],
                ['route' => 'panel.isi', 'pattern' => 'panel.isi*', 'label' => 'Isi Formulir', 'desc' => 'Pengisian instrumen data aktif', 'icon' => 'file-pen', 'roles' => ['produsen', 'admin']],
                ['route' => 'panel.preprocessing', 'pattern' => ['panel.preprocessing*', 'panel.data*'], 'label' => 'Pre-processing Data', 'desc' => 'Normalisasi, deduplikasi & missing value', 'icon' => 'sparkles', 'roles' => ['wali', 'admin'], 'badge' => $formsNeedingPreprocessingCount ?? 0, 'badgeTone' => 'amber'],
                ['route' => 'panel.dataset', 'pattern' => 'panel.dataset*', 'label' => 'Kelola Dataset', 'desc' => 'Susun, publikasi & atur akses dataset', 'icon' => 'table-2', 'roles' => ['wali', 'admin']],
                ['route' => 'panel.visualisasi', 'pattern' => 'panel.visualisasi*', 'label' => 'Visualisasi Dashboard', 'desc' => 'Bangun grafik publik interaktif', 'icon' => 'bar-chart-3', 'roles' => ['wali', 'admin']],
            ],
        ],
        [
            'code' => '02',
            'label' => 'Layanan & Referensi',
            'items' => [
                ['route' => 'panel.riwayat', 'pattern' => 'panel.riwayat*', 'label' => 'Riwayat Pengisian', 'desc' => 'Log respons & status validasi', 'icon' => 'history', 'roles' => ['produsen', 'wali', 'admin']],
                ['route' => 'panel.permohonan', 'pattern' => 'panel.permohonan*', 'label' => 'Permohonan Data', 'desc' => 'Tinjau permintaan akses private', 'icon' => 'inbox', 'roles' => ['wali', 'admin'], 'badge' => $pendingRequestsCount ?? 0, 'badgeTone' => 'red'],
                ['route' => 'panel.kategori', 'pattern' => 'panel.kategori*', 'label' => 'Kategori Data', 'desc' => 'Taksonomi kategori instrumen & dataset', 'icon' => 'folder-tree', 'roles' => ['wali', 'admin']],
            ],
        ],
        [
            'code' => '03',
            'label' => 'Administrasi',
            'items' => [
                ['route' => 'panel.pengguna', 'pattern' => 'panel.pengguna*', 'label' => 'Pengguna & Peran', 'desc' => 'Kelola akun & hak akses RBAC', 'icon' => 'users', 'roles' => ['admin']],
                ['route' => 'panel.konten', 'pattern' => 'panel.konten*', 'label' => 'Konten Portal', 'desc' => 'Profil prodi, dosen, prestasi & berita', 'icon' => 'layout-template', 'roles' => ['admin']],
            ],
        ],
    ];

    $paletteItems = [];
    foreach ($navGroups as $grp) {
        foreach ($grp['items'] as $it) {
            if (in_array($role, $it['roles'], true)) {
                $paletteItems[] = [
                    'group' => $grp['label'],
                    'label' => $it['label'],
                    'desc' => $it['desc'],
                    'url' => route($it['route']),
                    'badge' => (int) ($it['badge'] ?? 0),
                ];
            }
        }
    }
@endphp

<div class="wd-studio-bg min-h-[calc(100dvh-65px)]">
    <div class="mx-auto grid max-w-[1400px] grid-cols-1 gap-6 px-4 py-6 sm:px-6 lg:grid-cols-[264px_minmax(0,1fr)] lg:px-8 lg:py-8"
         x-data="panelShell(@js($paletteItems))"
         @keydown.escape.window="panelNavOpen = false; cmdOpen = false"
         @keydown.window="handleShortcut($event)">

        <!-- Command Rail Sidebar -->
        <aside class="relative z-20 min-w-0 lg:sticky lg:top-24 lg:self-start">
            <div class="wd-bento overflow-hidden">
                <!-- Architectural Identity Header -->
                <div class="bg-linear-to-br from-[#09122C] via-[#101F4A] to-[#182654] p-4 text-white">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-[#00A6F4]/20 font-mono text-sm font-bold text-[#74D4FF] ring-1 ring-[#74D4FF]/35">
                                {{ $user->initials() }}
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold text-white">{{ $user->name }}</p>
                                <p class="mt-0.5 flex items-center gap-1.5 text-[11px] font-medium text-slate-300">
                                    <span class="inline-block h-1.5 w-1.5 rounded-full bg-[#38BDF8]"></span>
                                    <span class="truncate">{{ $user->roleLabel() }}{{ $user->jenis ? ' · '.$user->jenis : '' }}</span>
                                </p>
                            </div>
                        </div>
                        <button type="button"
                                @click="panelNavOpen = !panelNavOpen"
                                :aria-expanded="panelNavOpen"
                                aria-label="Menu panel internal"
                                class="inline-flex min-h-[40px] shrink-0 items-center gap-1.5 rounded-xl border border-white/20 bg-white/10 px-3 text-xs font-semibold text-white transition hover:bg-white/20 lg:hidden">
                            <i data-lucide="menu" class="h-4 w-4" aria-hidden="true"></i>
                            <span>Menu</span>
                        </button>
                    </div>

                    @if($user->isStaff())
                        <div class="mt-3.5 grid grid-cols-2 gap-2 border-t border-white/12 pt-3 text-[11px]">
                            <a href="{{ route('panel.preprocessing') }}" class="rounded-lg bg-white/[0.07] px-2.5 py-1.5 transition hover:bg-white/15">
                                <span class="block text-slate-300">Pre-proc</span>
                                <span class="font-mono text-sm font-bold tabular-nums {{ ($formsNeedingPreprocessingCount ?? 0) > 0 ? 'text-[#FBBF24]' : 'text-white' }}">
                                    {{ $formsNeedingPreprocessingCount ?? 0 }} antrean
                                </span>
                            </a>
                            <a href="{{ route('panel.permohonan') }}" class="rounded-lg bg-white/[0.07] px-2.5 py-1.5 transition hover:bg-white/15">
                                <span class="block text-slate-300">Permohonan</span>
                                <span class="font-mono text-sm font-bold tabular-nums {{ ($pendingRequestsCount ?? 0) > 0 ? 'text-[#74D4FF]' : 'text-white' }}">
                                    {{ $pendingRequestsCount ?? 0 }} tunggu
                                </span>
                            </a>
                        </div>
                    @endif
                </div>

                <!-- Navigation Body -->
                <div class="p-3 lg:block" :class="panelNavOpen ? 'block' : 'hidden lg:block'">
                    <!-- Quick Command Palette Trigger -->
                    <button type="button"
                            @click="openCmd()"
                            class="mb-3 flex min-h-[40px] w-full items-center justify-between gap-2 rounded-xl border border-slate-200/90 bg-[#F8FAFC] px-3 text-left text-xs font-medium text-[#475569] transition hover:border-[#0069A8]/50 hover:bg-white hover:text-slate-900">
                        <span class="flex items-center gap-2 truncate">
                            <i data-lucide="search" class="h-3.5 w-3.5 shrink-0 text-[#0069A8]" aria-hidden="true"></i>
                            <span class="truncate">Lompat cepat modul...</span>
                        </span>
                        <kbd class="hidden rounded border border-slate-200 bg-white px-1.5 py-0.5 font-mono text-[10px] font-semibold text-slate-500 sm:inline-block">Ctrl K</kbd>
                    </button>

                    <nav class="space-y-4" aria-label="Menu panel">
                        @foreach($navGroups as $group)
                            @php
                                $visibleItems = array_filter($group['items'], fn ($item) => in_array($role, $item['roles'], true));
                            @endphp
                            @if(! empty($visibleItems))
                                <div class="space-y-0.5">
                                    @if($group['code'] !== '00')
                                        <p class="flex items-center gap-1.5 px-2.5 pb-1 font-mono text-[10px] font-bold tracking-[0.14em] text-slate-500 uppercase">
                                            <span class="text-[#0069A8]">{{ $group['code'] }}</span>
                                            <span>/</span>
                                            <span>{{ $group['label'] }}</span>
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
                                           aria-current="{{ $active ? 'page' : 'false' }}"
                                           class="group relative flex min-h-[42px] w-full items-center gap-2.5 rounded-xl px-3 py-2 text-left text-[13.5px] transition-colors {{ $active ? 'bg-[#F0F9FF] font-bold text-[#0069A8] ring-1 ring-[#BAE6FD]' : 'font-medium text-slate-700 hover:bg-slate-50 hover:text-slate-900' }}">
                                            @if($active)
                                                <span class="absolute top-2 bottom-2 left-0 w-[3px] rounded-r-full bg-[#0069A8]" aria-hidden="true"></span>
                                            @endif
                                            <i data-lucide="{{ $item['icon'] }}" class="h-4 w-4 shrink-0 {{ $active ? 'text-[#0069A8]' : 'text-slate-500 group-hover:text-[#0069A8]' }}" aria-hidden="true"></i>
                                            <span class="flex-1 truncate">{{ $item['label'] }}</span>
                                            @if($badgeVal > 0)
                                                <span class="rounded-full px-2 py-0.5 font-mono text-[11px] font-bold tabular-nums {{ $badgeTone === 'amber' ? 'bg-[#FFFBEB] text-[#92400E] ring-1 ring-amber-300/70' : 'bg-[#B91C1C] text-white' }}">
                                                    {{ $badgeVal }}
                                                </span>
                                            @endif
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        @endforeach
                    </nav>

                    <div class="mt-4 space-y-1.5 border-t border-slate-200/80 pt-3">
                        @if($user->isStaff())
                            <button type="button"
                                    data-motion-toggle
                                    onclick="toggleSceneMotion(this)"
                                    aria-pressed="false"
                                    class="flex min-h-[40px] w-full items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-50 hover:text-[#0069A8]">
                                <i data-lucide="pause-circle" class="h-4 w-4 shrink-0" aria-hidden="true"></i>
                                <span data-motion-label>Jeda latar 3D</span>
                            </button>
                        @endif

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

        <!-- Command Palette Modal (Ctrl+K) -->
        <template x-if="cmdOpen">
            <div class="fixed inset-0 z-[75] grid place-items-start justify-items-center bg-slate-950/55 px-4 pt-16 backdrop-blur-xs sm:pt-24"
                 @click.self="cmdOpen = false">
                <div role="dialog"
                     aria-modal="true"
                     aria-label="Navigasi cepat modul panel"
                     class="w-full max-w-lg overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
                    <div class="flex items-center gap-3 border-b border-slate-200 px-4 py-3">
                        <svg class="h-4 w-4 shrink-0 text-[#0069A8]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                        <input x-ref="cmdInput"
                               x-model="cmdQuery"
                               @keydown.arrow-down.prevent="moveActive(1)"
                               @keydown.arrow-up.prevent="moveActive(-1)"
                               @keydown.enter.prevent="goActive()"
                               type="text"
                               placeholder="Ketik nama modul panel (mis. Pre-processing, Dataset)..."
                               class="w-full bg-transparent text-sm text-slate-900 outline-none placeholder:text-slate-400" />
                        <button type="button"
                                @click="cmdOpen = false"
                                class="rounded-lg border border-slate-200 px-2 py-1 font-mono text-[11px] font-semibold text-slate-500 hover:text-slate-900">
                            ESC
                        </button>
                    </div>

                    <div class="max-h-80 overflow-y-auto p-2">
                        <template x-for="(item, idx) in filteredItems()" :key="item.url">
                            <a :href="item.url"
                               @mouseenter="activeIdx = idx"
                               :class="idx === activeIdx ? 'bg-[#F0F9FF] text-[#0069A8]' : 'text-slate-700 hover:bg-slate-50'"
                               class="flex items-center justify-between gap-3 rounded-xl px-3 py-2.5 transition-colors">
                                <div class="min-w-0">
                                    <p class="text-[10px] font-mono font-bold tracking-wider text-slate-400 uppercase" x-text="item.group"></p>
                                    <p class="truncate text-sm font-bold" x-text="item.label"></p>
                                    <p class="truncate text-xs text-slate-500" x-text="item.desc"></p>
                                </div>
                                <div class="flex shrink-0 items-center gap-2">
                                    <template x-if="item.badge > 0">
                                        <span class="rounded-full bg-[#FFFBEB] px-2 py-0.5 font-mono text-[11px] font-bold text-[#92400E] ring-1 ring-amber-200" x-text="item.badge"></span>
                                    </template>
                                    <svg class="h-4 w-4 opacity-60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                                </div>
                            </a>
                        </template>
                        <template x-if="filteredItems().length === 0">
                            <p class="px-4 py-8 text-center text-sm text-slate-500">Modul tidak ditemukan.</p>
                        </template>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>

@push('scripts')
<script>
function panelShell(items) {
    return {
        panelNavOpen: false,
        cmdOpen: false,
        cmdQuery: '',
        activeIdx: 0,
        items,

        openCmd() {
            this.cmdQuery = '';
            this.activeIdx = 0;
            this.cmdOpen = true;
            this.$nextTick(() => this.$refs.cmdInput?.focus());
        },

        handleShortcut(e) {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                this.cmdOpen ? (this.cmdOpen = false) : this.openCmd();
            }
        },

        filteredItems() {
            const q = this.cmdQuery.trim().toLowerCase();
            if (!q) return this.items;
            return this.items.filter((i) =>
                (i.label + ' ' + i.desc + ' ' + i.group).toLowerCase().includes(q)
            );
        },

        moveActive(delta) {
            const list = this.filteredItems();
            if (!list.length) return;
            this.activeIdx = (this.activeIdx + delta + list.length) % list.length;
        },

        goActive() {
            const list = this.filteredItems();
            const target = list[this.activeIdx];
            if (target?.url) {
                window.location.href = target.url;
            }
        },
    };
}
</script>
@endpush
@endsection
