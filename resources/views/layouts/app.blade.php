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
    <script defer src="https://unpkg.com/lucide@latest"></script>

    @stack('styles')
</head>
<body class="flex min-h-screen flex-col bg-white text-ink antialiased selection:bg-sky-100 selection:text-benhur"
      x-data="{ mobileOpen: false, berandaDrop: false, tentangDrop: false }"
      @keydown.escape.window="mobileOpen = false; berandaDrop = false; tentangDrop = false">
    <a href="#main-content" class="skip-link">{{ $t('Lewati ke konten utama', 'Skip to main content') }}</a>

    <!-- Top Header -->
    <header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white">
        <div class="mx-auto flex h-16 max-w-[1200px] items-center justify-between gap-4 px-4 sm:px-8">
            <a href="{{ route('beranda') }}" class="group flex min-w-0 items-center gap-2.5">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-[#0069A8] text-white transition-colors group-hover:bg-[#005187]">
                    <i data-lucide="database" class="h-5 w-5" aria-hidden="true"></i>
                </span>
                <span class="min-w-0 leading-tight">
                    <span class="block truncate text-[15px] font-extrabold tracking-tight text-ink">
                        Satu Data Sistem Informasi
                    </span>
                    <span class="block text-[11px] font-semibold text-[#62748E]">UNUSA</span>
                </span>
            </a>

            <!-- Desktop Navigation -->
            <nav class="hidden flex-1 justify-center gap-1 xl:flex" aria-label="Menu utama">
                <div class="relative flex items-center"
                     @mouseenter="berandaDrop = true"
                     @mouseleave="berandaDrop = false"
                     @click.outside="berandaDrop = false">
                    <a href="{{ route('beranda') }}"
                       class="flex items-center rounded-l-lg px-3 py-2 text-sm font-medium whitespace-nowrap transition-colors {{ request()->routeIs('beranda') ? 'bg-[#F0F9FF] font-semibold text-[#0069A8]' : 'text-[#45556C] hover:bg-slate-100/70 hover:text-ink' }}">
                        {{ $t('Beranda', 'Home') }}
                    </a>
                    <button type="button"
                            @click="berandaDrop = !berandaDrop"
                            :aria-expanded="berandaDrop"
                            aria-label="{{ $t('Submenu Beranda', 'Home submenu') }}"
                            class="-ml-1 grid h-9 w-7 place-items-center rounded-r-lg transition-colors {{ request()->routeIs('beranda') ? 'bg-[#F0F9FF] text-[#0069A8]' : 'text-[#45556C] hover:bg-slate-100/70 hover:text-ink' }}">
                        <i data-lucide="chevron-down" class="h-3.5 w-3.5 transition-transform duration-200" :class="{ 'rotate-180': berandaDrop }" aria-hidden="true"></i>
                    </button>
                    <div x-show="berandaDrop"
                         x-cloak
                         x-transition:enter="transition duration-150 ease-out"
                         x-transition:enter-start="opacity-0 translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition duration-100 ease-in"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 translate-y-1"
                         class="absolute top-full left-1/2 z-50 w-60 -translate-x-1/2 pt-2">
                        <div class="rounded-xl border border-slate-200 bg-white p-1.5 shadow-lg shadow-slate-900/10">
                            <a href="{{ route('beranda') }}#profil-prodi" @click="berandaDrop = false" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-[#45556C] transition-colors hover:bg-[#F0F9FF] hover:text-[#0069A8]">{{ $t('Profil Program Studi', 'Study Program Profile') }}</a>
                            <a href="{{ route('beranda') }}#berita" @click="berandaDrop = false" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-[#45556C] transition-colors hover:bg-[#F0F9FF] hover:text-[#0069A8]">{{ $t('Berita Prodi', 'Program News') }}</a>
                            <a href="{{ route('beranda') }}#prestasi" @click="berandaDrop = false" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-[#45556C] transition-colors hover:bg-[#F0F9FF] hover:text-[#0069A8]">{{ $t('Prestasi Mahasiswa', 'Student Achievements') }}</a>
                            <a href="{{ route('beranda') }}#dosen" @click="berandaDrop = false" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-[#45556C] transition-colors hover:bg-[#F0F9FF] hover:text-[#0069A8]">{{ $t('Data Dosen', 'Lecturers') }}</a>
                            <a href="{{ route('beranda') }}#kurikulum" @click="berandaDrop = false" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-[#45556C] transition-colors hover:bg-[#F0F9FF] hover:text-[#0069A8]">{{ $t('Kurikulum', 'Curriculum') }}</a>
                        </div>
                    </div>
                </div>

                <a href="{{ route('dashboard') }}"
                   class="flex items-center rounded-lg px-3 py-2 text-sm font-medium whitespace-nowrap transition-colors {{ request()->routeIs('dashboard') ? 'bg-[#F0F9FF] font-semibold text-[#0069A8]' : 'text-[#45556C] hover:bg-slate-100/70 hover:text-ink' }}">
                    Dashboard
                </a>

                <a href="{{ route('dataset') }}"
                   class="flex items-center rounded-lg px-3 py-2 text-sm font-medium whitespace-nowrap transition-colors {{ request()->routeIs('dataset*') ? 'bg-[#F0F9FF] font-semibold text-[#0069A8]' : 'text-[#45556C] hover:bg-slate-100/70 hover:text-ink' }}">
                    Dataset
                </a>

                <a href="{{ route('berita') }}"
                   class="flex items-center rounded-lg px-3 py-2 text-sm font-medium whitespace-nowrap transition-colors {{ request()->routeIs('berita*') ? 'bg-[#F0F9FF] font-semibold text-[#0069A8]' : 'text-[#45556C] hover:bg-slate-100/70 hover:text-ink' }}">
                    {{ $t('Berita', 'News') }}
                </a>

                <a href="{{ route('pengisian-data') }}"
                   class="flex items-center rounded-lg px-3 py-2 text-sm font-medium whitespace-nowrap transition-colors {{ request()->routeIs('pengisian-data') ? 'bg-[#F0F9FF] font-semibold text-[#0069A8]' : 'text-[#45556C] hover:bg-slate-100/70 hover:text-ink' }}">
                    {{ $t('Pengisian Data', 'Data Submission') }}
                </a>

                <div class="relative flex items-center"
                     @mouseenter="tentangDrop = true"
                     @mouseleave="tentangDrop = false"
                     @click.outside="tentangDrop = false">
                    <a href="{{ route('tentang-kami') }}"
                       class="flex items-center rounded-l-lg px-3 py-2 text-sm font-medium whitespace-nowrap transition-colors {{ request()->routeIs('tentang-kami') ? 'bg-[#F0F9FF] font-semibold text-[#0069A8]' : 'text-[#45556C] hover:bg-slate-100/70 hover:text-ink' }}">
                        {{ $t('Tentang kami', 'About us') }}
                    </a>
                    <button type="button"
                            @click="tentangDrop = !tentangDrop"
                            :aria-expanded="tentangDrop"
                            aria-label="{{ $t('Submenu Tentang kami', 'About us submenu') }}"
                            class="-ml-1 grid h-9 w-7 place-items-center rounded-r-lg transition-colors {{ request()->routeIs('tentang-kami') ? 'bg-[#F0F9FF] text-[#0069A8]' : 'text-[#45556C] hover:bg-slate-100/70 hover:text-ink' }}">
                        <i data-lucide="chevron-down" class="h-3.5 w-3.5 transition-transform duration-200" :class="{ 'rotate-180': tentangDrop }" aria-hidden="true"></i>
                    </button>
                    <div x-show="tentangDrop"
                         x-cloak
                         x-transition:enter="transition duration-150 ease-out"
                         x-transition:enter-start="opacity-0 translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition duration-100 ease-in"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 translate-y-1"
                         class="absolute top-full left-1/2 z-50 w-60 -translate-x-1/2 pt-2">
                        <div class="rounded-xl border border-slate-200 bg-white p-1.5 shadow-lg shadow-slate-900/10">
                            <a href="{{ route('tentang-kami') }}#sejarah" @click="tentangDrop = false" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-[#45556C] transition-colors hover:bg-[#F0F9FF] hover:text-[#0069A8]">{{ $t('Sejarah Prodi', 'History') }}</a>
                            <a href="{{ route('tentang-kami') }}#tentang-prodi" @click="tentangDrop = false" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-[#45556C] transition-colors hover:bg-[#F0F9FF] hover:text-[#0069A8]">{{ $t('Visi & Misi', 'Vision & Mission') }}</a>
                            <a href="{{ route('tentang-kami') }}#dosen-prodi" @click="tentangDrop = false" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-[#45556C] transition-colors hover:bg-[#F0F9FF] hover:text-[#0069A8]">{{ $t('Dosen Tetap', 'Lecturers') }}</a>
                            <a href="{{ route('tentang-kami') }}#detail-prodi" @click="tentangDrop = false" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-[#45556C] transition-colors hover:bg-[#F0F9FF] hover:text-[#0069A8]">{{ $t('Detail Prodi', 'Program Details') }}</a>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Language & Account -->
            <div class="hidden items-center gap-2.5 xl:flex">
                <div class="flex shrink-0 rounded-xl border border-slate-200 bg-slate-50 p-0.5 text-xs font-semibold" role="group" aria-label="Bahasa">
                    <form action="{{ route('lang.switch', 'id') }}" method="POST">
                        @csrf
                        <button type="submit" aria-pressed="{{ $appLang === 'id' ? 'true' : 'false' }}" class="rounded-lg px-2.5 py-1 uppercase transition-colors {{ $appLang === 'id' ? 'bg-[#0069A8] text-white' : 'text-[#45556C] hover:text-ink' }}">ID</button>
                    </form>
                    <form action="{{ route('lang.switch', 'en') }}" method="POST">
                        @csrf
                        <button type="submit" aria-pressed="{{ $appLang === 'en' ? 'true' : 'false' }}" class="rounded-lg px-2.5 py-1 uppercase transition-colors {{ $appLang === 'en' ? 'bg-[#0069A8] text-white' : 'text-[#45556C] hover:text-ink' }}">EN</button>
                    </form>
                </div>

                @auth
                    <a href="{{ route('panel.ringkasan') }}"
                       class="flex shrink-0 items-center gap-2 rounded-xl border bg-white px-2.5 py-1.5 text-sm font-semibold transition-colors {{ request()->routeIs('panel.*') ? 'border-sky-300 bg-[#F0F9FF] text-[#0069A8]' : 'border-slate-200 text-[#45556C] hover:border-sky-300 hover:text-ink' }}">
                        <span class="grid h-6 w-6 place-items-center rounded-full bg-[#0069A8] text-[10px] font-bold text-white">
                            {{ auth()->user()->initials() }}
                        </span>
                        <span class="max-w-[120px] truncate">{{ auth()->user()->roleLabel() }}</span>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="sd-btn-primary shrink-0 gap-1.5 px-4 py-2 text-sm">
                        <i data-lucide="log-in" class="h-4 w-4" aria-hidden="true"></i>
                        {{ $t('Masuk', 'Sign in') }}
                    </a>
                @endauth
            </div>

            <!-- Mobile Menu Trigger (visible label per antislop-layoutmobile) -->
            <button type="button"
                    @click="mobileOpen = !mobileOpen"
                    class="inline-flex min-h-[44px] shrink-0 items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-[#45556C] transition-colors hover:border-slate-300 hover:text-ink xl:hidden"
                    aria-label="Toggle menu"
                    :aria-expanded="mobileOpen">
                <i data-lucide="menu" class="h-4 w-4" x-show="!mobileOpen" aria-hidden="true"></i>
                <i data-lucide="x" class="h-4 w-4" x-show="mobileOpen" x-cloak aria-hidden="true"></i>
                <span x-text="mobileOpen ? @js($t('Tutup', 'Close')) : 'Menu'">Menu</span>
            </button>
        </div>

        <!-- Mobile Drawer -->
        <div x-show="mobileOpen" x-cloak x-transition class="border-t border-slate-200 bg-white px-4 pt-3 pb-5 sm:px-8 xl:hidden">
            <nav class="grid gap-1" aria-label="Menu utama">
                <div>
                    <a href="{{ route('beranda') }}" class="flex min-h-[44px] items-center rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('beranda') ? 'bg-[#F0F9FF] font-semibold text-[#0069A8]' : 'text-[#45556C]' }}">
                        {{ $t('Beranda', 'Home') }}
                    </a>
                    <div class="mb-1 ml-3 grid gap-0.5 border-l border-slate-200 pl-2">
                        <a href="{{ route('beranda') }}#profil-prodi" @click="mobileOpen = false" class="flex min-h-[40px] items-center rounded-lg px-3 py-1.5 text-[13px] text-[#45556C] hover:bg-slate-50 hover:text-ink">{{ $t('Profil Program Studi', 'Study Program Profile') }}</a>
                        <a href="{{ route('beranda') }}#berita" @click="mobileOpen = false" class="flex min-h-[40px] items-center rounded-lg px-3 py-1.5 text-[13px] text-[#45556C] hover:bg-slate-50 hover:text-ink">{{ $t('Berita Prodi', 'Program News') }}</a>
                        <a href="{{ route('beranda') }}#prestasi" @click="mobileOpen = false" class="flex min-h-[40px] items-center rounded-lg px-3 py-1.5 text-[13px] text-[#45556C] hover:bg-slate-50 hover:text-ink">{{ $t('Prestasi Mahasiswa', 'Student Achievements') }}</a>
                        <a href="{{ route('beranda') }}#dosen" @click="mobileOpen = false" class="flex min-h-[40px] items-center rounded-lg px-3 py-1.5 text-[13px] text-[#45556C] hover:bg-slate-50 hover:text-ink">{{ $t('Data Dosen', 'Lecturers') }}</a>
                        <a href="{{ route('beranda') }}#kurikulum" @click="mobileOpen = false" class="flex min-h-[40px] items-center rounded-lg px-3 py-1.5 text-[13px] text-[#45556C] hover:bg-slate-50 hover:text-ink">{{ $t('Kurikulum', 'Curriculum') }}</a>
                    </div>
                </div>

                <a href="{{ route('dashboard') }}" class="flex min-h-[44px] items-center rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-[#F0F9FF] font-semibold text-[#0069A8]' : 'text-[#45556C]' }}">Dashboard</a>
                <a href="{{ route('dataset') }}" class="flex min-h-[44px] items-center rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('dataset*') ? 'bg-[#F0F9FF] font-semibold text-[#0069A8]' : 'text-[#45556C]' }}">Dataset</a>
                <a href="{{ route('berita') }}" class="flex min-h-[44px] items-center rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('berita*') ? 'bg-[#F0F9FF] font-semibold text-[#0069A8]' : 'text-[#45556C]' }}">{{ $t('Berita', 'News') }}</a>
                <a href="{{ route('pengisian-data') }}" class="flex min-h-[44px] items-center rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('pengisian-data') ? 'bg-[#F0F9FF] font-semibold text-[#0069A8]' : 'text-[#45556C]' }}">{{ $t('Pengisian Data', 'Data Submission') }}</a>

                <div>
                    <a href="{{ route('tentang-kami') }}" class="flex min-h-[44px] items-center rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('tentang-kami') ? 'bg-[#F0F9FF] font-semibold text-[#0069A8]' : 'text-[#45556C]' }}">
                        {{ $t('Tentang kami', 'About us') }}
                    </a>
                    <div class="mb-1 ml-3 grid gap-0.5 border-l border-slate-200 pl-2">
                        <a href="{{ route('tentang-kami') }}#sejarah" @click="mobileOpen = false" class="flex min-h-[40px] items-center rounded-lg px-3 py-1.5 text-[13px] text-[#45556C] hover:bg-slate-50 hover:text-ink">{{ $t('Sejarah Prodi', 'History') }}</a>
                        <a href="{{ route('tentang-kami') }}#tentang-prodi" @click="mobileOpen = false" class="flex min-h-[40px] items-center rounded-lg px-3 py-1.5 text-[13px] text-[#45556C] hover:bg-slate-50 hover:text-ink">{{ $t('Visi & Misi', 'Vision & Mission') }}</a>
                        <a href="{{ route('tentang-kami') }}#dosen-prodi" @click="mobileOpen = false" class="flex min-h-[40px] items-center rounded-lg px-3 py-1.5 text-[13px] text-[#45556C] hover:bg-slate-50 hover:text-ink">{{ $t('Dosen Tetap', 'Lecturers') }}</a>
                        <a href="{{ route('tentang-kami') }}#detail-prodi" @click="mobileOpen = false" class="flex min-h-[40px] items-center rounded-lg px-3 py-1.5 text-[13px] text-[#45556C] hover:bg-slate-50 hover:text-ink">{{ $t('Detail Prodi', 'Program Details') }}</a>
                    </div>
                </div>
            </nav>

            <div class="mt-3 flex items-center justify-between gap-3 border-t border-slate-200 pt-3">
                <div class="flex shrink-0 rounded-xl border border-slate-200 bg-slate-50 p-0.5 text-xs font-semibold">
                    <form action="{{ route('lang.switch', 'id') }}" method="POST">
                        @csrf
                        <button type="submit" class="min-h-[36px] rounded-lg px-3 py-1 uppercase {{ $appLang === 'id' ? 'bg-[#0069A8] text-white' : 'text-[#45556C]' }}">ID</button>
                    </form>
                    <form action="{{ route('lang.switch', 'en') }}" method="POST">
                        @csrf
                        <button type="submit" class="min-h-[36px] rounded-lg px-3 py-1 uppercase {{ $appLang === 'en' ? 'bg-[#0069A8] text-white' : 'text-[#45556C]' }}">EN</button>
                    </form>
                </div>

                @auth
                    <a href="{{ route('panel.ringkasan') }}" class="flex min-h-[44px] shrink-0 items-center gap-2 rounded-xl border border-slate-200 px-3 py-1.5 text-sm font-semibold text-[#45556C]">
                        <span class="grid h-6 w-6 place-items-center rounded-full bg-[#0069A8] text-[10px] font-bold text-white">
                            {{ auth()->user()->initials() }}
                        </span>
                        <span>{{ auth()->user()->roleLabel() }}</span>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="sd-btn-primary min-h-[44px] shrink-0 gap-1.5 px-4 py-2 text-sm">
                        <i data-lucide="log-in" class="h-4 w-4" aria-hidden="true"></i>
                        {{ $t('Masuk', 'Sign in') }}
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <!-- Main Page Content -->
    <div id="main-content" tabindex="-1" class="flex-1 motion-safe:animate-page-in outline-none">
        @yield('content')
    </div>

    @unless(View::hasSection('hideFooter'))
        <footer class="bg-gradient-to-br from-[#182654] to-[#1E3575] text-white/85">
            <div class="mx-auto grid max-w-[1200px] grid-cols-1 gap-8 px-4 py-12 text-sm sm:px-8 md:grid-cols-3">
                <div>
                    <p class="flex items-center gap-2 font-extrabold text-white">
                        <i data-lucide="database" class="h-5 w-5 text-[#74D4FF]" aria-hidden="true"></i>
                        Satu Data Sistem Informasi
                    </p>
                    <p class="mt-2 leading-relaxed text-white/80">
                        {{ $t(
                            'Portal data terintegrasi Program Studi Sistem Informasi, Universitas Nahdlatul Ulama Surabaya.',
                            'Integrated data portal of the Information Systems Study Program, Universitas Nahdlatul Ulama Surabaya.'
                        ) }}
                    </p>
                </div>
                <div class="space-y-2.5">
                    <p class="flex items-start gap-2">
                        <i data-lucide="map-pin" class="mt-0.5 h-4 w-4 shrink-0 text-[#74D4FF]" aria-hidden="true"></i>
                        <span>{{ $siteProfil?->alamat ?? 'Kampus B UNUSA, Jl. Jemursari No. 51-57, Surabaya, Jawa Timur 60237' }}</span>
                    </p>
                    <p class="flex items-center gap-2">
                        <i data-lucide="mail" class="h-4 w-4 shrink-0 text-[#74D4FF]" aria-hidden="true"></i>
                        <span>{{ $siteProfil?->email ?? 'si@unusa.ac.id' }}</span>
                    </p>
                    <p class="flex items-center gap-2">
                        <i data-lucide="phone" class="h-4 w-4 shrink-0 text-[#74D4FF]" aria-hidden="true"></i>
                        <span>{{ $siteProfil?->telp ?? '(031) 8291920' }}</span>
                    </p>
                </div>
                <nav class="grid grid-cols-2 gap-2" aria-label="Tautan cepat footer">
                    <a href="{{ route('beranda') }}" class="hover:text-white">{{ $t('Beranda', 'Home') }}</a>
                    <a href="{{ route('dashboard') }}" class="hover:text-white">Dashboard</a>
                    <a href="{{ route('dataset') }}" class="hover:text-white">Dataset</a>
                    <a href="{{ route('berita') }}" class="hover:text-white">{{ $t('Berita', 'News') }}</a>
                    <a href="{{ route('pengisian-data') }}" class="hover:text-white">{{ $t('Pengisian Data', 'Data Submission') }}</a>
                    <a href="{{ route('tentang-kami') }}" class="hover:text-white">{{ $t('Tentang kami', 'About us') }}</a>
                </nav>
            </div>
            <p class="border-t border-white/15 px-4 py-4 text-center text-xs text-white/80">
                © {{ date('Y') }} Satu Data Sistem Informasi UNUSA · {{ $t('Portal Data Resmi Program Studi Sistem Informasi UNUSA.', 'Official Data Portal of the Information Systems Study Program UNUSA.') }}
            </p>
        </footer>
    @endunless

    <x-toast />
    <x-confirm-modal />

    @stack('scripts')
</body>
</html>
