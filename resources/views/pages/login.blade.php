<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk ke Satu Data - Sistem Informasi UNUSA</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS (configured exact to src/index.css) -->
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
                            soft: '#c9eeff',
                        },
                        surface: '#f4f7fb',
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
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #ffffff; color: #0f172a; }
    </style>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="h-full bg-white text-ink antialiased" x-data="{ nim: '{{ old('username') }}', pw: '', showPw: false }">
    <main class="grid min-h-[calc(100dvh-65px)] grid-cols-1 lg:grid-cols-2">
        <!-- Panel gambar kiri (Exact Login.tsx) -->
        <section class="relative hidden overflow-hidden lg:block lg:h-full">
            <img
                src="https://images.unsplash.com/photo-1562774053-701939374585?auto=format&fit=crop&w=1600&q=80"
                alt="Gedung kampus UNUSA"
                class="absolute inset-0 h-full w-full object-cover"
            />
            <div class="absolute inset-0 bg-gradient-to-br from-benhur/95 via-benhur/85 to-deep/75"></div>
            <div class="relative flex h-full flex-col justify-center p-10 text-white xl:p-12">
                <span class="mb-5 inline-flex w-fit items-center gap-2 rounded-lg border border-white/25 bg-white/10 px-3 py-1 text-sm">
                    Area internal
                </span>
                <h1 class="max-w-md text-[40px] leading-tight font-extrabold tracking-tight">
                    Kelola <span class="text-accent-soft">data prodi</span> dalam satu tempat
                </h1>
                <p class="mt-4 max-w-md text-lg text-white/85">
                    Isi formulir, kelola data dan dataset, buat visualisasi, dan konfirmasi permohonan data sesuai peran Anda.
                </p>
            </div>
        </section>

        <!-- Panel Form Kanan (Exact Login.tsx) -->
        <section class="flex items-center justify-center px-4 py-8 sm:px-8 lg:py-10">
            <div class="w-full max-w-md">
                <div class="flex items-center gap-3">
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-sky-600 text-white">
                        <i data-lucide="database" class="h-5 w-5"></i>
                    </span>
                    <h2 class="text-2xl leading-tight font-extrabold tracking-tight sm:text-[28px]">
                        Masuk ke Satu Data
                    </h2>
                </div>
                <p class="mt-3 text-[15px] text-slate-500">
                    Gunakan NIM/NIP dan password yang terdaftar.
                </p>

                @if(session('error'))
                    <div class="mt-4 flex gap-3 rounded-xl px-4 py-3 text-sm bg-red-50 text-[#b02a37]">
                        <i data-lucide="x-circle" class="mt-0.5 h-4 w-4 shrink-0"></i>
                        <div>{{ session('error') }}</div>
                    </div>
                @endif

                <form action="{{ route('login.post') }}" method="POST" class="mt-6 space-y-4">
                    @csrf
                    <label class="block">
                        <span class="mb-1.5 block text-sm font-semibold text-slate-800">
                            NIM / NIP <span class="ml-0.5 text-[#DC3545]">*</span>
                        </span>
                        <input
                            type="text"
                            name="username"
                            x-model="nim"
                            required
                            autocomplete="username"
                            placeholder="Contoh: 1462200031"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                        />
                    </label>

                    <label class="block">
                        <span class="mb-1.5 block text-sm font-semibold text-slate-800">
                            Password <span class="ml-0.5 text-[#DC3545]">*</span>
                        </span>
                        <div class="relative">
                            <input
                                :type="showPw ? 'text' : 'password'"
                                name="password"
                                x-model="pw"
                                required
                                autocomplete="current-password"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 pr-11 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                            />
                            <button
                                type="button"
                                @click="showPw = !showPw"
                                :aria-label="showPw ? 'Sembunyikan password' : 'Tampilkan password'"
                                class="absolute inset-y-0 right-0 grid w-11 place-items-center text-slate-400 hover:text-slate-700"
                            >
                                <i data-lucide="eye" class="h-4 w-4" x-show="!showPw"></i>
                                <i data-lucide="eye-off" class="h-4 w-4" x-show="showPw" style="display: none;"></i>
                            </button>
                        </div>
                    </label>

                    <button
                        type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-xl text-sm font-semibold transition motion-safe:active:scale-[0.97] bg-brand px-4 py-3 text-[15px] text-white hover:bg-sky-700 w-full"
                    >
                        <i data-lucide="log-in" class="h-4 w-4"></i>
                        Masuk
                    </button>

                    <p class="text-center text-xs text-slate-500">
                        Sesi berakhir otomatis setelah 30 menit tidak aktif. Autentikasi ini simulasi lokal prototipe, bukan login server.
                    </p>
                </form>

                <!-- Demo Accounts Box (Exact Login.tsx) -->
                <div class="mt-6 rounded-2xl border border-dashed border-slate-300 bg-surface p-4">
                    <p class="text-xs font-bold tracking-wider text-slate-500 uppercase">
                        Akun demo prototipe
                    </p>
                    <div class="mt-2 grid grid-cols-1 gap-1 sm:grid-cols-2">
                        @php
                            $demos = [
                                ['admin', 'admin123', 'Admin'],
                                ['walidata', 'wali123', 'Wali Data'],
                                ['1462200031', 'mhs123', 'Produsen · Mahasiswa'],
                                ['0712048601', 'dosen123', 'Produsen · Dosen'],
                            ];
                        @endphp
                        @foreach($demos as $d)
                            <button
                                type="button"
                                @click="nim = '{{ $d[0] }}'; pw = '{{ $d[1] }}'"
                                class="flex min-w-0 items-center justify-between gap-3 rounded-lg px-2.5 py-1.5 text-left text-sm transition-colors hover:bg-sky-50 sm:flex-col sm:items-start sm:justify-start sm:gap-0.5 sm:py-2"
                            >
                                <span class="font-semibold text-slate-700">{{ $d[2] }}</span>
                                <span class="max-w-full truncate font-mono text-xs text-slate-500">
                                    {{ $d[0] }} / {{ $d[1] }}
                                </span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <a
                    href="{{ route('beranda') }}"
                    class="mt-5 inline-block text-sm font-semibold text-sky-600 hover:text-sky-700"
                >
                    ← Kembali ke beranda
                </a>
            </div>
        </section>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (window.lucide) {
                lucide.createIcons();
            }
        });
    </script>
</body>
</html>
