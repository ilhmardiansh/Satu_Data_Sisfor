<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Satu Data Sistem Informasi UNUSA') - Portal Data Terpadu</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS (CDN Configured for 100% Fidelity with src/index.css) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            DEFAULT: '#4472d0',
                            dark: '#3760bb',
                        },
                        accent: {
                            DEFAULT: '#0ea5e9',
                            light: '#7dd3fc',
                            soft: '#c9eeff',
                        },
                        surface: {
                            DEFAULT: '#f4f7fb',
                            strong: '#e8eef7',
                        },
                        ink: '#0f172a',
                        benhur: '#002462',
                        deep: '#0a3a8a',
                        sky: {
                            50: '#f1f5fd',
                            100: '#e1eaf9',
                            200: '#c7d6f3',
                            300: '#a0bbea',
                            400: '#7398dd',
                            500: '#5a83d6',
                            600: '#4472d0',
                            700: '#3760bb',
                            800: '#304f96',
                            900: '#2b4377',
                            950: '#1c2a4a',
                        },
                        blue: {
                            50: '#f1f5fd',
                            100: '#e1eaf9',
                            200: '#c7d6f3',
                            300: '#a0bbea',
                            400: '#7398dd',
                            500: '#5a83d6',
                            600: '#4472d0',
                            700: '#3760bb',
                            800: '#304f96',
                            900: '#2b4377',
                            950: '#1c2a4a',
                        }
                    },
                    keyframes: {
                        'fade-up': { from: { opacity: '0', transform: 'translate3d(0, 18px, 0)' }, to: { opacity: '1', transform: 'translate3d(0, 0, 0)' } },
                        'page-in': { from: { opacity: '0', transform: 'translate3d(0, 8px, 0)' }, to: { opacity: '1', transform: 'translate3d(0, 0, 0)' } },
                        'ken-burns': { from: { transform: 'scale(1.04)' }, to: { transform: 'scale(1.12) translate3d(-1.5%, -1%, 0)' } },
                        'float': { '0%, 100%': { transform: 'translate3d(0, 0, 0) scale(1)' }, '50%': { transform: 'translate3d(24px, -18px, 0) scale(1.06)' } },
                        'reveal-up': { from: { opacity: '0', transform: 'translate3d(0, 18px, 0)' }, to: { opacity: '1', transform: 'translate3d(0, 0, 0)' } }
                    },
                    animation: {
                        'fade-up': 'fade-up 0.8s cubic-bezier(0.22, 1, 0.36, 1) backwards',
                        'page-in': 'page-in 0.45s cubic-bezier(0.22, 1, 0.36, 1) backwards',
                        'ken-burns': 'ken-burns 28s ease-in-out infinite alternate',
                        'float': 'float 16s ease-in-out infinite',
                        'reveal-up': 'reveal-up 0.8s cubic-bezier(0.22, 1, 0.36, 1) backwards'
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #ffffff;
            color: #0f172a;
        }
        @media (prefers-reduced-motion: no-preference) {
            .reveal:not(.is-in),
            .reveal-stagger:not(.is-in) > * { opacity: 0; }
            .reveal.is-in {
                animation: reveal-up 0.8s cubic-bezier(0.22, 1, 0.36, 1) backwards;
                animation-delay: var(--reveal-delay, 0ms);
            }
            .reveal-stagger.is-in > * {
                animation: reveal-up 0.7s cubic-bezier(0.22, 1, 0.36, 1) backwards;
                animation-delay: calc(var(--reveal-delay, 0ms) + var(--i, 0) * 70ms);
            }
            .reveal-stagger > :nth-child(2) { --i: 1; }
            .reveal-stagger > :nth-child(3) { --i: 2; }
            .reveal-stagger > :nth-child(4) { --i: 3; }
            .reveal-stagger > :nth-child(5) { --i: 4; }
            .reveal-stagger > :nth-child(6) { --i: 5; }
            .reveal-stagger > :nth-child(7) { --i: 6; }
            .reveal-stagger > :nth-child(n + 8) { --i: 7; }
        }
    </style>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    @stack('styles')
</head>
<body class="flex min-h-screen flex-col bg-white text-ink antialiased" x-data="{ mobileOpen: false, activeLang: 'id', berandaDrop: false, tentangDrop: false }">

    <!-- Top Navigation Header (Matching App.tsx exactly) -->
    <header class="sticky top-0 z-40 border-b border-[#E2E8F0] bg-white">
        <div class="mx-auto flex h-16 max-w-[1200px] items-center justify-between gap-4 px-4 sm:px-8">
            <!-- Brand Logo -->
            <a href="{{ route('beranda') }}" class="flex min-w-0 items-center gap-2.5">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-brand text-white">
                    <i data-lucide="database" class="h-5 w-5"></i>
                </span>
                <span class="min-w-0 leading-tight">
                    <span class="block truncate text-[15px] font-extrabold text-ink">
                        Satu Data Sistem Informasi
                    </span>
                    <span class="block text-[11px] text-[#62748E]">
                        UNUSA
                    </span>
                </span>
            </a>

            <!-- Desktop Menu with Submenu Dropdowns -->
            <nav class="hidden flex-1 justify-center gap-0.5 xl:flex" aria-label="Menu utama">
                <!-- Beranda Dropdown -->
                <div class="relative group" @mouseenter="berandaDrop = true" @mouseleave="berandaDrop = false">
                    <a href="{{ route('beranda') }}"
                       class="flex items-center gap-1 rounded-lg px-3 py-2 text-sm font-medium whitespace-nowrap transition {{ request()->routeIs('beranda') ? 'bg-sky-100/80 text-brand-dark' : 'text-[#45556C] hover:text-ink' }}">
                        <span>Beranda</span>
                        <i data-lucide="chevron-down" class="h-3.5 w-3.5 transition-transform duration-200" :class="{ 'rotate-180': berandaDrop }"></i>
                    </a>
                    <div x-show="berandaDrop"
                         x-transition:enter="transition duration-150 ease-out"
                         x-transition:enter-start="opacity-0 translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition duration-100 ease-in"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 translate-y-1"
                         class="absolute top-full left-1/2 z-50 w-60 -translate-x-1/2 pt-2"
                         style="display: none;">
                        <div class="rounded-xl border border-[#E2E8F0] bg-white p-1.5 shadow-xl shadow-slate-900/10">
                            <a href="{{ route('beranda') }}#berita" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-[#45556C] transition hover:bg-sky-50 hover:text-brand-dark">Berita Prodi</a>
                            <a href="{{ route('beranda') }}#prestasi" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-[#45556C] transition hover:bg-sky-50 hover:text-brand-dark">Prestasi Mahasiswa</a>
                            <a href="{{ route('beranda') }}#dosen" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-[#45556C] transition hover:bg-sky-50 hover:text-brand-dark">Data Dosen</a>
                            <a href="{{ route('beranda') }}#kurikulum" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-[#45556C] transition hover:bg-sky-50 hover:text-brand-dark">Kurikulum</a>
                        </div>
                    </div>
                </div>

                <!-- Dashboard -->
                <a href="{{ route('dashboard') }}"
                   class="flex items-center gap-1 rounded-lg px-3 py-2 text-sm font-medium whitespace-nowrap transition {{ request()->routeIs('dashboard') ? 'bg-sky-100/80 text-brand-dark' : 'text-[#45556C] hover:text-ink' }}">
                    Dashboard
                </a>

                <!-- Dataset -->
                <a href="{{ route('dataset') }}"
                   class="flex items-center gap-1 rounded-lg px-3 py-2 text-sm font-medium whitespace-nowrap transition {{ request()->routeIs('dataset') ? 'bg-sky-100/80 text-brand-dark' : 'text-[#45556C] hover:text-ink' }}">
                    Dataset
                </a>

                <!-- Berita -->
                <a href="{{ route('berita') }}"
                   class="flex items-center gap-1 rounded-lg px-3 py-2 text-sm font-medium whitespace-nowrap transition {{ request()->routeIs('berita') || request()->routeIs('berita.*') ? 'bg-sky-100/80 text-brand-dark' : 'text-[#45556C] hover:text-ink' }}">
                    Berita
                </a>

                <!-- Pengisian Data -->
                <a href="{{ route('pengisian-data') }}"
                   class="flex items-center gap-1 rounded-lg px-3 py-2 text-sm font-medium whitespace-nowrap transition {{ request()->routeIs('pengisian-data') ? 'bg-sky-100/80 text-brand-dark' : 'text-[#45556C] hover:text-ink' }}">
                    Pengisian Data
                </a>

                <!-- Tentang Kami Dropdown -->
                <div class="relative group" @mouseenter="tentangDrop = true" @mouseleave="tentangDrop = false">
                    <a href="{{ route('tentang-kami') }}"
                       class="flex items-center gap-1 rounded-lg px-3 py-2 text-sm font-medium whitespace-nowrap transition {{ request()->routeIs('tentang-kami') ? 'bg-sky-100/80 text-brand-dark' : 'text-[#45556C] hover:text-ink' }}">
                        <span>Tentang kami</span>
                        <i data-lucide="chevron-down" class="h-3.5 w-3.5 transition-transform duration-200" :class="{ 'rotate-180': tentangDrop }"></i>
                    </a>
                    <div x-show="tentangDrop"
                         x-transition:enter="transition duration-150 ease-out"
                         x-transition:enter-start="opacity-0 translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition duration-100 ease-in"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 translate-y-1"
                         class="absolute top-full left-1/2 z-50 w-60 -translate-x-1/2 pt-2"
                         style="display: none;">
                        <div class="rounded-xl border border-[#E2E8F0] bg-white p-1.5 shadow-xl shadow-slate-900/10">
                            <a href="{{ route('tentang-kami') }}#sejarah" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-[#45556C] transition hover:bg-sky-50 hover:text-brand-dark">Sejarah Prodi</a>
                            <a href="{{ route('tentang-kami') }}#tentang-prodi" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-[#45556C] transition hover:bg-sky-50 hover:text-brand-dark">Tentang Prodi</a>
                            <a href="{{ route('tentang-kami') }}#detail-prodi" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-[#45556C] transition hover:bg-sky-50 hover:text-brand-dark">Detail Prodi</a>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Language and Account -->
            <div class="hidden items-center gap-2 xl:flex">
                <div class="flex shrink-0 rounded-lg border border-[#E2E8F0] p-0.5 text-xs font-semibold" role="group" aria-label="Bahasa">
                    <button type="button" @click="activeLang = 'id'" :class="activeLang === 'id' ? 'bg-brand text-white' : 'text-[#45556C]'" class="rounded-md px-2.5 py-1 uppercase">ID</button>
                    <button type="button" @click="activeLang = 'en'" :class="activeLang === 'en' ? 'bg-brand text-white' : 'text-[#45556C]'" class="rounded-md px-2.5 py-1 uppercase">EN</button>
                </div>

                @if (session()->has('user'))
                    <div class="flex items-center gap-2">
                        <div class="flex shrink-0 items-center gap-2 rounded-xl border border-[#E2E8F0] px-2.5 py-1.5 text-sm font-semibold text-[#45556C]">
                            <span class="grid h-6 w-6 place-items-center rounded-full bg-brand text-[10px] font-bold text-white">
                                {{ strtoupper(substr(session('user.nama') ?? session('user.name'), 0, 1)) }}
                            </span>
                            <span class="max-w-[120px] truncate">{{ session('user.nama') ?? session('user.name') }}</span>
                        </div>
                        <form action="{{ route('logout') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" title="Keluar" class="grid h-8 w-8 place-items-center rounded-lg border border-slate-200 text-slate-500 hover:bg-rose-50 hover:text-rose-600 transition">
                                <i data-lucide="log-out" class="h-4 w-4"></i>
                            </button>
                        </form>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="flex shrink-0 items-center gap-1.5 rounded-xl bg-brand px-3.5 py-2 text-sm font-bold text-white hover:bg-sky-700 transition">
                        <i data-lucide="log-in" class="h-4 w-4"></i>
                        Masuk
                    </a>
                @endif
            </div>

            <!-- Mobile Hamburger Button -->
            <button @click="mobileOpen = !mobileOpen" class="grid h-10 w-10 shrink-0 place-items-center rounded-xl border border-[#E2E8F0] text-[#45556C] xl:hidden" aria-label="Toggle menu">
                <i data-lucide="menu" class="h-5 w-5" x-show="!mobileOpen"></i>
                <i data-lucide="x" class="h-5 w-5" x-show="mobileOpen" style="display: none;"></i>
            </button>
        </div>

        <!-- Mobile Menu Drawer (App.tsx structure) -->
        <div x-show="mobileOpen" x-transition class="border-t border-[#E2E8F0] bg-white px-4 pt-2 pb-4 sm:px-8 xl:hidden" style="display: none;">
            <nav class="grid gap-0.5" aria-label="Menu utama">
                <!-- Beranda & Sub-menus -->
                <div>
                    <a href="{{ route('beranda') }}" class="block rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs('beranda') ? 'bg-sky-100/80 text-brand-dark' : 'text-[#45556C]' }}">
                        Beranda
                    </a>
                    <div class="mb-1 ml-3 grid gap-0.5 border-l border-[#E2E8F0] pl-2">
                        <a href="{{ route('beranda') }}#berita" class="rounded-lg px-3 py-2 text-[13px] text-[#62748E] hover:bg-slate-50 hover:text-ink">Berita Prodi</a>
                        <a href="{{ route('beranda') }}#prestasi" class="rounded-lg px-3 py-2 text-[13px] text-[#62748E] hover:bg-slate-50 hover:text-ink">Prestasi Mahasiswa</a>
                        <a href="{{ route('beranda') }}#dosen" class="rounded-lg px-3 py-2 text-[13px] text-[#62748E] hover:bg-slate-50 hover:text-ink">Data Dosen</a>
                        <a href="{{ route('beranda') }}#kurikulum" class="rounded-lg px-3 py-2 text-[13px] text-[#62748E] hover:bg-slate-50 hover:text-ink">Kurikulum</a>
                    </div>
                </div>

                <a href="{{ route('dashboard') }}" class="block rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-sky-100/80 text-brand-dark' : 'text-[#45556C]' }}">Dashboard</a>
                <a href="{{ route('dataset') }}" class="block rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs('dataset') ? 'bg-sky-100/80 text-brand-dark' : 'text-[#45556C]' }}">Dataset</a>
                <a href="{{ route('berita') }}" class="block rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs('berita') || request()->routeIs('berita.*') ? 'bg-sky-100/80 text-brand-dark' : 'text-[#45556C]' }}">Berita</a>
                <a href="{{ route('pengisian-data') }}" class="block rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs('pengisian-data') ? 'bg-sky-100/80 text-brand-dark' : 'text-[#45556C]' }}">Pengisian Data</a>

                <!-- Tentang Kami & Sub-menus -->
                <div>
                    <a href="{{ route('tentang-kami') }}" class="block rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs('tentang-kami') ? 'bg-sky-100/80 text-brand-dark' : 'text-[#45556C]' }}">
                        Tentang kami
                    </a>
                    <div class="mb-1 ml-3 grid gap-0.5 border-l border-[#E2E8F0] pl-2">
                        <a href="{{ route('tentang-kami') }}#sejarah" class="rounded-lg px-3 py-2 text-[13px] text-[#62748E] hover:bg-slate-50 hover:text-ink">Sejarah Prodi</a>
                        <a href="{{ route('tentang-kami') }}#tentang-prodi" class="rounded-lg px-3 py-2 text-[13px] text-[#62748E] hover:bg-slate-50 hover:text-ink">Tentang Prodi</a>
                        <a href="{{ route('tentang-kami') }}#detail-prodi" class="rounded-lg px-3 py-2 text-[13px] text-[#62748E] hover:bg-slate-50 hover:text-ink">Detail Prodi</a>
                    </div>
                </div>
            </nav>

            <div class="mt-3 flex items-center justify-between gap-3 border-t border-[#E2E8F0] pt-3">
                <div class="flex shrink-0 rounded-lg border border-[#E2E8F0] p-0.5 text-xs font-semibold">
                    <button type="button" @click="activeLang = 'id'" :class="activeLang === 'id' ? 'bg-brand text-white' : 'text-[#45556C]'" class="rounded-md px-2.5 py-1 uppercase">ID</button>
                    <button type="button" @click="activeLang = 'en'" :class="activeLang === 'en' ? 'bg-brand text-white' : 'text-[#45556C]'" class="rounded-md px-2.5 py-1 uppercase">EN</button>
                </div>
                @if (session()->has('user'))
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-sky-800">{{ session('user.nama') ?? session('user.name') }}</span>
                        <form action="{{ route('logout') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="rounded-lg bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-600">Keluar</button>
                        </form>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="flex shrink-0 items-center gap-1.5 rounded-xl bg-brand px-3.5 py-2 text-sm font-bold text-white hover:bg-sky-700">
                        <i data-lucide="log-in" class="h-4 w-4"></i>
                        Masuk
                    </a>
                @endif
            </div>
        </div>
    </header>

    <!-- Main Page Content -->
    <div class="flex-1 motion-safe:animate-page-in">
        @yield('content')
    </div>

    <!-- Global Footer (Exact App.tsx Footer) -->
    <footer class="bg-gradient-to-br from-benhur to-deep text-white/80">
        <div class="mx-auto grid grid-cols-1 max-w-[1200px] gap-8 px-4 py-12 text-sm sm:px-8 md:grid-cols-3">
            <div>
                <p class="flex items-center gap-2 font-extrabold text-white text-base">
                    <i data-lucide="database" class="h-5 w-5 text-accent-soft"></i>
                    Satu Data Sistem Informasi
                </p>
                <p class="mt-2 text-white/80 leading-relaxed">
                    Portal data terintegrasi Program Studi Sistem Informasi, Universitas Nahdlatul Ulama Surabaya.
                </p>
            </div>
            <div class="space-y-2">
                <p class="flex items-start gap-2 text-white/80">
                    <i data-lucide="map-pin" class="h-4 w-4 shrink-0 mt-0.5"></i>
                    <span>Kampus B UNUSA, Jl. Jemursari No. 51–57, Surabaya, Jawa Timur 60237</span>
                </p>
                <p class="flex items-center gap-2 text-white/80">
                    <i data-lucide="mail" class="h-4 w-4 shrink-0"></i>
                    <span>si@unusa.ac.id</span>
                </p>
                <p class="flex items-center gap-2 text-white/80">
                    <i data-lucide="phone" class="h-4 w-4 shrink-0"></i>
                    <span>(031) 8291920</span>
                </p>
            </div>
            <nav class="grid grid-cols-2 gap-2 text-white/80">
                <a href="{{ route('beranda') }}" class="hover:text-white transition">Beranda</a>
                <a href="{{ route('dashboard') }}" class="hover:text-white transition">Dashboard</a>
                <a href="{{ route('dataset') }}" class="hover:text-white transition">Dataset</a>
                <a href="{{ route('berita') }}" class="hover:text-white transition">Berita</a>
                <a href="{{ route('pengisian-data') }}" class="hover:text-white transition">Pengisian Data</a>
                <a href="{{ route('tentang-kami') }}" class="hover:text-white transition">Tentang kami</a>
            </nav>
        </div>
        <p class="border-t border-white/15 px-4 py-4 text-center text-xs text-white/60">
            © {{ date('Y') }} Satu Data Sistem Informasi UNUSA · Prototipe antarmuka: data tersimpan di peramban.
        </p>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (window.lucide) {
                lucide.createIcons();
            }

            // Scroll reveal observer
            const reveals = document.querySelectorAll('.reveal, .reveal-stagger');
            if ('IntersectionObserver' in window) {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('is-in');
                            observer.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.1 });
                reveals.forEach(el => observer.observe(el));
            } else {
                reveals.forEach(el => el.classList.add('is-in'));
            }
        });
    </script>

    @stack('scripts')
</body>
</html>