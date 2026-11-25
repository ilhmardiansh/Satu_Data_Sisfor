<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('panel.ringkasan');
        }

        $demoUsers = [
            ['nim' => 'admin', 'password' => 'admin123', 'label' => 'Admin'],
            ['nim' => 'walidata', 'password' => 'wali123', 'label' => 'Wali Data'],
            ['nim' => '1462200031', 'password' => 'mhs123', 'label' => 'Produsen · Mahasiswa'],
            ['nim' => '0712048601', 'password' => 'dosen123', 'label' => 'Produsen · Dosen'],
        ];

        return view('pages.login', compact('demoUsers'));
    }

    public function login(Request $request): RedirectResponse
    {
        $nimInput = trim((string) ($request->input('nim') ?? $request->input('username') ?? ''));
        $request->merge(['nim' => $nimInput]);

        $validated = $request->validate([
            'nim' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'nim.required' => 'NIM/NIP wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $user = User::query()->where('nim', $validated['nim'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->kata_sandi)) {
            return back()
                ->withInput(['nim' => $validated['nim']])
                ->with('error', 'NIM/NIP atau password salah.');
        }

        if (! $user->aktif) {
            return back()
                ->withInput(['nim' => $validated['nim']])
                ->with('error', 'Akun Anda dinonaktifkan. Hubungi Admin prodi.');
        }

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('last_activity_at', now()->timestamp);

        return redirect()->intended(route('panel.ringkasan'))
            ->with('success', 'Selamat datang kembali, '.$user->name.'!');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'Anda telah berhasil keluar.');
    }
}
