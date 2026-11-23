<!DOCTYPE html>
<html lang="{{ $appLang }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Satu Data Sistem Informasi UNUSA')</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>

    @stack('styles')
</head>
<body class="flex min-h-screen flex-col bg-[#f8fafd] text-ink antialiased selection:bg-sky-100 selection:text-benhur" x-data="{ mobileOpen: false, berandaDrop: false, tentangDrop: false }">
    <!-- Top Header -->
    <header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/85 backdrop-blur-xl shadow-[0_1px_2px_rgba(15,23,42,0.03)]">
        <div class="mx-auto flex h-16 max-w-[1200px] items-center justify-between gap-4 px-4 sm:px-8">
            <a href="{{ route('beranda') }}" class="group flex min-w-0 items-center gap-2.5">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-gradient-to-b from-brand to-brand-dark text-white shadow-[0_2px_6px_rgba(68,114,208,0.35),inset_0_1px_0_rgba(255,255,255,0.25)] transition group-hover:brightness-105">
                    <i data-lucide="database" class="h-5 w-5"></i>
                </span>
                <span class="min-w-0 leading-tight">
                    <span class="block truncate text-[15px] font-extrabold tracking-tight text-ink">
                        Satu Data Sistem Informasi
                    </span>
                    <span class="block text-[11px] font-medium text-[#62748E]">UNUSA</span>
                </span>
            </a>

            <!-- Desktop Navigation -->
            <nav class="hidden flex-1 justify-center gap-1 xl:flex" aria-label="Menu utama">
                <div class="relative" @mouseenter="berandaDrop = true" @mouseleave="berandaDrop = false" @keydown.escape="berandaDrop = false">
                    <a href="{{ route('beranda') }}"
                       class="flex items-center gap-1 rounded-lg px-3 py-2 text-sm font-medium whitespace-nowrap transition {{ request()->routeIs('beranda') ? 'bg-sky-50/90 font-semibold text-brand-dark ring-1 ring-sky-200/80 shadow-[0_1px_2px_rgba(68,114,208,0.08)]' : 'text-[#45556C] hover:bg-slate-100/70 hover:text-ink' }}">
                        <span>{{ $t('Beranda', 'Home') }}</span>
                        <i data-lucide="chevron-down" class="h-3.5 w-3.5 transition-transform duration-200" :class="{ 'rotate-180': berandaDrop }"></i>
                    </a>
                    <div x-show="berandaDrop"
                         x-cloak
                         x-transition:enter="transition duration-150 ease-out"
                         x-transition:enter-start="opacity-0 translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition duration-100 ease-in"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 translate-y-1"
                         class="absolute top-full left-1/2 z-50 w-60 -translate-x-1/2 pt-2">
                        <div class="rounded-xl border border-slate-200/80 bg-white/95 backdrop-blur-xl p-1.5 shadow-[0_12px_36px_-8px_rgba(15,23,42,0.14)] ring-1 ring-slate-900/5">
                            <a href="{{ route('beranda') }}#berita" @click="berandaDrop = false" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-[#45556C] transition hover:bg-sky-50 hover:text-brand-dark">{{ $t('Berita Prodi', 'Program News') }}</a>
                            <a href="{{ route('beranda') }}#prestasi" @click="berandaDrop = false" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-[#45556C] transition hover:bg-sky-50 hover:text-brand-dark">{{ $t('Prestasi Mahasiswa', 'Student Achievements') }}</a>
                            <a href="{{ route('beranda') }}#dosen" @click="berandaDrop = false" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-[#45556C] transition hover:bg-sky-50 hover:text-brand-dark">{{ $t('Data Dosen', 'Lecturers') }}</a>
                            <a href="{{ route('beranda') }}#kurikulum" @click="berandaDrop = false" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-[#45556C] transition hover:bg-sky-50 hover:text-brand-dark">{{ $t('Kurikulum', 'Curriculum') }}</a>
                        </div>
                    </div>
                </div>

                <a href="{{ route('dashboard') }}"
                   class="flex items-center gap-1 rounded-lg px-3 py-2 text-sm font-medium whitespace-nowrap transition {{ request()->routeIs('dashboard') ? 'bg-sky-50/90 font-semibold text-brand-dark ring-1 ring-sky-200/80 shadow-[0_1px_2px_rgba(68,114,208,0.08)]' : 'text-[#45556C] hover:bg-slate-100/70 hover:text-ink' }}">
                    Dashboard
                </a>

                <a href="{{ route('dataset') }}"
                   class="flex items-center gap-1 rounded-lg px-3 py-2 text-sm font-medium whitespace-nowrap transition {{ request()->routeIs('dataset*') ? 'bg-sky-50/90 font-semibold text-brand-dark ring-1 ring-sky-200/80 shadow-[0_1px_2px_rgba(68,114,208,0.08)]' : 'text-[#45556C] hover:bg-slate-100/70 hover:text-ink' }}">
                    Dataset
                </a>

                <a href="{{ route('berita') }}"
                   class="flex items-center gap-1 rounded-lg px-3 py-2 text-sm font-medium whitespace-nowrap transition {{ request()->routeIs('berita*') ? 'bg-sky-50/90 font-semibold text-brand-dark ring-1 ring-sky-200/80 shadow-[0_1px_2px_rgba(68,114,208,0.08)]' : 'text-[#45556C] hover:bg-slate-100/70 hover:text-ink' }}">
                    {{ $t('Berita', 'News') }}
                </a>

                <a href="{{ route('pengisian-data') }}"
                   class="flex items-center gap-1 rounded-lg px-3 py-2 text-sm font-medium whitespace-nowrap transition {{ request()->routeIs('pengisian-data') ? 'bg-sky-50/90 font-semibold text-brand-dark ring-1 ring-sky-200/80 shadow-[0_1px_2px_rgba(68,114,208,0.08)]' : 'text-[#45556C] hover:bg-slate-100/70 hover:text-ink' }}">
                    {{ $t('Pengisian Data', 'Data Submission') }}
                </a>

                <div class="relative" @mouseenter="tentangDrop = true" @mouseleave="tentangDrop = false" @keydown.escape="tentangDrop = false">
                    <a href="{{ route('tentang-kami') }}"
                       class="flex items-center gap-1 rounded-lg px-3 py-2 text-sm font-medium whitespace-nowrap transition {{ request()->routeIs('tentang-kami') ? 'bg-sky-50/90 font-semibold text-brand-dark ring-1 ring-sky-200/80 shadow-[0_1px_2px_rgba(68,114,208,0.08)]' : 'text-[#45556C] hover:bg-slate-100/70 hover:text-ink' }}">
                        <span>{{ $t('Tentang kami', 'About us') }}</span>
                        <i data-lucide="chevron-down" class="h-3.5 w-3.5 transition-transform duration-200" :class="{ 'rotate-180': tentangDrop }"></i>
                    </a>
                    <div x-show="tentangDrop"
                         x-cloak
                         x-transition:enter="transition duration-150 ease-out"
                         x-transition:enter-start="opacity-0 translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition duration-100 ease-in"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 translate-y-1"
                         class="absolute top-full left-1/2 z-50 w-60 -translate-x-1/2 pt-2">
                        <div class="rounded-xl border border-slate-200/80 bg-white/95 backdrop-blur-xl p-1.5 shadow-[0_12px_36px_-8px_rgba(15,23,42,0.14)] ring-1 ring-slate-900/5">
                            <a href="{{ route('tentang-kami') }}#sejarah" @click="tentangDrop = false" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-[#45556C] transition hover:bg-sky-50 hover:text-brand-dark">{{ $t('Sejarah Prodi', 'History') }}</a>
                            <a href="{{ route('tentang-kami') }}#tentang-prodi" @click="tentangDrop = false" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-[#45556C] transition hover:bg-sky-50 hover:text-brand-dark">{{ $t('Tentang Prodi', 'About the Program') }}</a>
                            <a href="{{ route('tentang-kami') }}#detail-prodi" @click="tentangDrop = false" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-[#45556C] transition hover:bg-sky-50 hover:text-brand-dark">{{ $t('Detail Prodi', 'Program Details') }}</a>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Language & Account -->
            <div class="hidden items-center gap-2.5 xl:flex">
                <div class="flex shrink-0 rounded-xl border border-slate-200/80 bg-slate-100/70 p-0.5 text-xs font-semibold" role="group" aria-label="Bahasa">
                    <form action="{{ route('lang.switch', 'id') }}" method="POST">
                        @csrf
                        <button type="submit" aria-pressed="{{ $appLang === 'id' ? 'true' : 'false' }}" class="rounded-lg px-2.5 py-1 uppercase transition {{ $appLang === 'id' ? 'bg-brand text-white shadow-xs' : 'text-[#45556C] hover:text-ink' }}">ID</button>
                    </form>
                    <form action="{{ route('lang.switch', 'en') }}" method="POST">
                        @csrf
                        <button type="submit" aria-pressed="{{ $appLang === 'en' ? 'true' : 'false' }}" class="rounded-lg px-2.5 py-1 uppercase transition {{ $appLang === 'en' ? 'bg-brand text-white shadow-xs' : 'text-[#45556C] hover:text-ink' }}">EN</button>
                    </form>
                </div>

                @auth
                    <a href="{{ route('panel.ringkasan') }}"
                       class="flex shrink-0 items-center gap-2 rounded-xl border bg-white px-2.5 py-1.5 text-sm font-semibold shadow-2xs transition {{ request()->routeIs('panel.*') ? 'border-sky-300 bg-sky-50/90 text-sky-700' : 'border-slate-200/80 text-[#45556C] hover:border-sky-300 hover:shadow-xs' }}">
                        <span class="grid h-6 w-6 place-items-center rounded-full bg-gradient-to-b from-brand to-brand-dark text-[10px] font-bold text-white shadow-xs">
                            {{ auth()->user()->initials() }}
                        </span>
                        <span class="max-w-[120px] truncate">{{ auth()->user()->roleLabel() }}</span>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="sd-btn-primary shrink-0 gap-1.5 px-3.5 py-2 text-sm font-bold">
                        <i data-lucide="log-in" class="h-4 w-4"></i>
                        {{ $t('Masuk', 'Sign in') }}
                    </a>
                @endauth
            </div>

            <!-- Mobile Hamburger -->
            <button type="button" @click="mobileOpen = !mobileOpen" class="grid h-10 w-10 shrink-0 place-items-center rounded-xl border border-slate-200/80 bg-white text-[#45556C] shadow-2xs transition hover:border-slate-300 xl:hidden" aria-label="Toggle menu" :aria-expanded="mobileOpen">
                <i data-lucide="menu" class="h-5 w-5" x-show="!mobileOpen"></i>
                <i data-lucide="x" class="h-5 w-5" x-show="mobileOpen" x-cloak></i>
            </button>
        </div>

        <!-- Mobile Drawer -->
        <div x-show="mobileOpen" x-cloak x-transition class="border-t border-slate-200/80 bg-white/95 backdrop-blur-xl px-4 pt-2 pb-4 sm:px-8 xl:hidden">
            <nav class="grid gap-0.5" aria-label="Menu utama">
                <div>
                    <a href="{{ route('beranda') }}" class="block rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs('beranda') ? 'bg-sky-100/80 text-brand-dark' : 'text-[#45556C]' }}">
                        {{ $t('Beranda', 'Home') }}
                    </a>
                    <div class="mb-1 ml-3 grid gap-0.5 border-l border-[#E2E8F0] pl-2">
                        <a href="{{ route('beranda') }}#berita" @click="mobileOpen = false" class="rounded-lg px-3 py-2 text-[13px] text-[#62748E] hover:bg-slate-50 hover:text-ink">{{ $t('Berita Prodi', 'Program News') }}</a>
                        <a href="{{ route('beranda') }}#prestasi" @click="mobileOpen = false" class="rounded-lg px-3 py-2 text-[13px] text-[#62748E] hover:bg-slate-50 hover:text-ink">{{ $t('Prestasi Mahasiswa', 'Student Achievements') }}</a>
                        <a href="{{ route('beranda') }}#dosen" @click="mobileOpen = false" class="rounded-lg px-3 py-2 text-[13px] text-[#62748E] hover:bg-slate-50 hover:text-ink">{{ $t('Data Dosen', 'Lecturers') }}</a>
                        <a href="{{ route('beranda') }}#kurikulum" @click="mobileOpen = false" class="rounded-lg px-3 py-2 text-[13px] text-[#62748E] hover:bg-slate-50 hover:text-ink">{{ $t('Kurikulum', 'Curriculum') }}</a>
                    </div>
                </div>

                <a href="{{ route('dashboard') }}" class="block rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-sky-100/80 text-brand-dark' : 'text-[#45556C]' }}">Dashboard</a>
                <a href="{{ route('dataset') }}" class="block rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs('dataset*') ? 'bg-sky-100/80 text-brand-dark' : 'text-[#45556C]' }}">Dataset</a>
                <a href="{{ route('berita') }}" class="block rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs('berita*') ? 'bg-sky-100/80 text-brand-dark' : 'text-[#45556C]' }}">{{ $t('Berita', 'News') }}</a>
                <a href="{{ route('pengisian-data') }}" class="block rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs('pengisian-data') ? 'bg-sky-100/80 text-brand-dark' : 'text-[#45556C]' }}">{{ $t('Pengisian Data', 'Data Submission') }}</a>

                <div>
                    <a href="{{ route('tentang-kami') }}" class="block rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs('tentang-kami') ? 'bg-sky-100/80 text-brand-dark' : 'text-[#45556C]' }}">
                        {{ $t('Tentang kami', 'About us') }}
                    </a>
                    <div class="mb-1 ml-3 grid gap-0.5 border-l border-[#E2E8F0] pl-2">
                        <a href="{{ route('tentang-kami') }}#sejarah" @click="mobileOpen = false" class="rounded-lg px-3 py-2 text-[13px] text-[#62748E] hover:bg-slate-50 hover:text-ink">{{ $t('Sejarah Prodi', 'History') }}</a>
                        <a href="{{ route('tentang-kami') }}#tentang-prodi" @click="mobileOpen = false" class="rounded-lg px-3 py-2 text-[13px] text-[#62748E] hover:bg-slate-50 hover:text-ink">{{ $t('Tentang Prodi', 'About the Program') }}</a>
                        <a href="{{ route('tentang-kami') }}#detail-prodi" @click="mobileOpen = false" class="rounded-lg px-3 py-2 text-[13px] text-[#62748E] hover:bg-slate-50 hover:text-ink">{{ $t('Detail Prodi', 'Program Details') }}</a>
                    </div>
                </div>
            </nav>

            <div class="mt-3 flex items-center justify-between gap-3 border-t border-[#E2E8F0] pt-3">
                <div class="flex shrink-0 rounded-lg border border-[#E2E8F0] p-0.5 text-xs font-semibold">
                    <form action="{{ route('lang.switch', 'id') }}" method="POST">
                        @csrf
                        <button type="submit" class="rounded-md px-2.5 py-1 uppercase {{ $appLang === 'id' ? 'bg-brand text-white' : 'text-[#45556C]' }}">ID</button>
                    </form>
                    <form action="{{ route('lang.switch', 'en') }}" method="POST">
                        @csrf
                        <button type="submit" class="rounded-md px-2.5 py-1 uppercase {{ $appLang === 'en' ? 'bg-brand text-white' : 'text-[#45556C]' }}">EN</button>
                    </form>
                </div>

                @auth
                    <a href="{{ route('panel.ringkasan') }}" class="flex shrink-0 items-center gap-2 rounded-xl border border-[#E2E8F0] px-2.5 py-1.5 text-sm font-semibold text-[#45556C]">
                        <span class="grid h-6 w-6 place-items-center rounded-full bg-brand text-[10px] font-bold text-white">
                            {{ auth()->user()->initials() }}
                        </span>
                        <span>{{ auth()->user()->roleLabel() }}</span>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="flex shrink-0 items-center gap-1.5 rounded-xl bg-brand px-3.5 py-2 text-sm font-bold text-white hover:bg-sky-700">
                        <i data-lucide="log-in" class="h-4 w-4"></i>
                        {{ $t('Masuk', 'Sign in') }}
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <!-- Main Page Content -->
    <div class="flex-1 motion-safe:animate-page-in">
        @yield('content')
    </div>

    @unless(View::hasSection('hideFooter'))
        <footer class="relative overflow-hidden bg-gradient-to-br from-benhur via-[#062f78] to-deep text-white/80">
    <div class="pointer-events-none absolute -top-32 right-0 h-80 w-80 rounded-full bg-brand/20 blur-3xl"></div>
    <div class="pointer-events-none absolute bottom-0 left-1/4 h-64 w-64 rounded-full bg-sky-400/10 blur-3xl"></div>
            <div class="mx-auto grid max-w-[1200px] grid-cols-1 gap-8 px-4 py-12 text-sm sm:px-8 md:grid-cols-3">
                <div>
                    <p class="flex items-center gap-2 font-extrabold text-white">
                        <i data-lucide="database" class="h-5 w-5 text-accent-soft"></i>
                        Satu Data Sistem Informasi
                    </p>
                    <p class="mt-2 leading-relaxed">
                        {{ $t(
                            'Portal data terintegrasi Program Studi Sistem Informasi, Universitas Nahdlatul Ulama Surabaya.',
                            'Integrated data portal of the Information Systems Study Program, Universitas Nahdlatul Ulama Surabaya.'
                        ) }}
                    </p>
                </div>
                <div class="space-y-2">
                    <p class="flex items-start gap-2">
                        <i data-lucide="map-pin" class="mt-0.5 h-4 w-4 shrink-0"></i>
                        <span>{{ $siteProfil?->alamat ?? 'Kampus B UNUSA, Jl. Jemursari No. 51–57, Surabaya, Jawa Timur 60237' }}</span>
                    </p>
                    <p class="flex items-center gap-2">
                        <i data-lucide="mail" class="h-4 w-4 shrink-0"></i>
                        <span>{{ $siteProfil?->email ?? 'si@unusa.ac.id' }}</span>
                    </p>
                    <p class="flex items-center gap-2">
                        <i data-lucide="phone" class="h-4 w-4 shrink-0"></i>
                        <span>{{ $siteProfil?->telp ?? '(031) 8291920' }}</span>
                    </p>
                </div>
                <nav class="grid grid-cols-2 gap-2">
                    <a href="{{ route('beranda') }}" class="hover:text-white">{{ $t('Beranda', 'Home') }}</a>
                    <a href="{{ route('dashboard') }}" class="hover:text-white">Dashboard</a>
                    <a href="{{ route('dataset') }}" class="hover:text-white">Dataset</a>
                    <a href="{{ route('berita') }}" class="hover:text-white">{{ $t('Berita', 'News') }}</a>
                    <a href="{{ route('pengisian-data') }}" class="hover:text-white">{{ $t('Pengisian Data', 'Data Submission') }}</a>
                    <a href="{{ route('tentang-kami') }}" class="hover:text-white">{{ $t('Tentang kami', 'About us') }}</a>
                </nav>
            </div>
            <p class="border-t border-white/15 px-4 py-4 text-center text-xs text-white/75">
                © {{ date('Y') }} Satu Data Sistem Informasi UNUSA · {{ $t('Portal Data Resmi Program Studi Sistem Informasi UNUSA.', 'Official Data Portal of the Information Systems Study Program UNUSA.') }}
            </p>
        </footer>
    @endunless

    <x-toast />
    <x-confirm-modal />

    @stack('scripts')
</body>
</html>
