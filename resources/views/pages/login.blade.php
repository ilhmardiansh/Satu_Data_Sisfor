@extends('layouts.app')

@section('title', 'Masuk ke Satu Data - Sistem Informasi UNUSA')
@section('hideFooter', true)

@section('content')
<main class="grid min-h-[calc(100dvh-65px)] grid-cols-1 lg:grid-cols-2"
      x-data="{
          nim: @js(old('nim', '')),
          pw: '',
          showPw: false,
          tried: false
      }">
    <section class="relative hidden overflow-hidden lg:sticky lg:top-[65px] lg:block lg:h-[calc(100dvh-65px)] lg:self-start">
        <img
            src="{{ asset('images/kampus-unusa.jpg') }}"
            alt="Gedung kampus UNUSA"
            class="absolute inset-0 h-full w-full object-cover"
        />
        <div class="absolute inset-0 bg-gradient-to-br from-benhur/95 via-benhur/85 to-deep/75"></div>
        <div data-scene="network"
             aria-hidden="true"
             class="pointer-events-none absolute inset-0 opacity-65"
             style="mask-image: radial-gradient(closest-side, #000 55%, transparent 98%); -webkit-mask-image: radial-gradient(closest-side, #000 55%, transparent 98%);"></div>
        <div class="relative z-10 flex h-full flex-col justify-center p-10 text-white xl:p-12">
            <span class="sd-glass-panel mb-5 inline-flex w-fit items-center gap-2 rounded-lg px-3 py-1 text-sm font-medium">
                Area internal
            </span>
            <h1 class="max-w-md text-[40px] leading-tight font-extrabold tracking-tight drop-shadow-xs">
                Kelola <span class="text-accent-soft">data prodi</span> dalam satu tempat
            </h1>
            <p class="mt-4 max-w-md text-lg text-white/85">
                Isi formulir, kelola data dan dataset, buat visualisasi, dan konfirmasi permohonan data sesuai peran Anda.
            </p>
        </div>
    </section>

    <section class="flex items-center justify-center px-4 py-8 sm:px-8 lg:py-10">
        <div class="sd-card w-full max-w-md p-6 sm:p-8">
            <div class="flex items-center gap-3">
                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-gradient-to-b from-brand to-brand-dark text-white shadow-[0_2px_6px_rgba(68,114,208,0.35),inset_0_1px_0_rgba(255,255,255,0.25)]">
                    <i data-lucide="database" class="h-5 w-5"></i>
                </span>
                <h2 class="text-2xl leading-tight font-extrabold tracking-tight sm:text-[28px]">
                    {{ $t('Masuk ke Satu Data', 'Sign in to Satu Data') }}
                </h2>
            </div>
            <p class="mt-3 text-[15px] text-slate-500">
                {{ $t('Gunakan NIM/NIP dan password yang terdaftar.', 'Use your registered student/staff ID and password.') }}
            </p>

            <form action="{{ route('login.post') }}" method="POST" novalidate @submit="tried = true; if (!nim.trim() || !pw) $event.preventDefault();" class="mt-6 space-y-4">
                @csrf

                @if(session('notice') && ! session('error'))
                    <div role="status" class="flex gap-3 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        <i data-lucide="alert-triangle" class="mt-0.5 h-4 w-4 shrink-0"></i>
                        <div>{{ session('notice') }}</div>
                    </div>
                @endif

                @if(session('error'))
                    <div role="alert" class="flex gap-3 rounded-xl bg-red-50 px-4 py-3 text-sm text-[#b02a37]">
                        <i data-lucide="x-circle" class="mt-0.5 h-4 w-4 shrink-0"></i>
                        <div>{{ session('error') }}</div>
                    </div>
                @endif

                <label class="block">
                    <span class="mb-1.5 block text-sm font-semibold text-slate-800">
                        NIM / NIP <span class="ml-0.5 text-[#DC3545]">*</span>
                    </span>
                    <input
                        type="text"
                        name="nim"
                        x-model="nim"
                        autocomplete="username"
                        placeholder="Contoh: 1462200031"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                    />
                    <span x-show="tried && !nim.trim()" x-cloak class="mt-1 block text-xs text-[#DC3545]">NIM/NIP wajib diisi.</span>
                    @error('nim')
                        <span class="mt-1 block text-xs text-[#DC3545]">{{ $message }}</span>
                    @enderror
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
                            <i data-lucide="eye-off" class="h-4 w-4" x-show="showPw" x-cloak></i>
                        </button>
                    </div>
                    <span x-show="tried && !pw" x-cloak class="mt-1 block text-xs text-[#DC3545]">Password wajib diisi.</span>
                    @error('password')
                        <span class="mt-1 block text-xs text-[#DC3545]">{{ $message }}</span>
                    @enderror
                </label>

                <button
                    type="submit"
                    class="sd-btn-primary w-full gap-2 px-4 py-3 text-[15px] font-semibold"
                >
                    <i data-lucide="log-in" class="h-4 w-4"></i>
                    {{ $t('Masuk', 'Sign in') }}
                </button>

                <p class="text-center text-xs text-slate-500">
                    Sesi berakhir otomatis setelah 30 menit tidak aktif.
                </p>
            </form>

            <div class="mt-6 rounded-2xl border border-dashed border-slate-300/90 bg-slate-50/80 p-4">
                <p class="text-xs font-bold tracking-wider text-slate-500 uppercase">
                    Akun bawaan sistem
                </p>
                <div class="mt-2 grid grid-cols-1 gap-1 sm:grid-cols-2">
                    @foreach($demoUsers as $d)
                        <button
                            type="button"
                            @click="nim = '{{ $d['nim'] }}'; pw = '{{ $d['password'] }}';"
                            class="flex min-w-0 items-center justify-between gap-3 rounded-lg px-2.5 py-1.5 text-left text-sm transition-colors hover:bg-sky-50 sm:flex-col sm:items-start sm:justify-start sm:gap-0.5 sm:py-2"
                        >
                            <span class="font-semibold text-slate-700">{{ $d['label'] }}</span>
                            <span class="max-w-full truncate font-mono text-xs text-slate-500">
                                {{ $d['nim'] }} / {{ $d['password'] }}
                            </span>
                        </button>
                    @endforeach
                </div>
            </div>

            <a href="{{ route('beranda') }}" class="mt-5 inline-block text-sm font-semibold text-sky-600 hover:text-sky-700">
                ← {{ $t('Kembali ke beranda', 'Back to home') }}
            </a>
        </div>
    </section>
</main>
@endsection
