<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        $demoUsers = [
            ['username' => 'admin', 'password' => 'admin123', 'role' => 'Administrator Prodi', 'color' => 'red'],
            ['username' => 'walidata', 'password' => 'wali123', 'role' => 'Wali Data Prodi', 'color' => 'indigo'],
            ['username' => '1462200031', 'password' => 'mhs123', 'role' => 'Produsen (Mahasiswa)', 'color' => 'sky'],
            ['username' => '0712048601', 'password' => 'dosen123', 'role' => 'Produsen (Dosen)', 'color' => 'emerald'],
        ];

        return view('pages.login', compact('demoUsers'));
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $users = [
            'admin' => ['password' => 'admin123', 'name' => 'Administrator Prodi', 'role' => 'admin'],
            'walidata' => ['password' => 'wali123', 'name' => 'Rina Wulandari, M.Kom', 'role' => 'wali'],
            '1462200031' => ['password' => 'mhs123', 'name' => 'Nabila Putri', 'role' => 'produsen', 'jenis' => 'Mahasiswa'],
            '0712048601' => ['password' => 'dosen123', 'name' => 'Dr. Andi Setiawan, M.Kom', 'role' => 'produsen', 'jenis' => 'Dosen'],
        ];

        $username = trim($request->input('username'));
        $password = $request->input('password');

        if (isset($users[$username]) && $users[$username]['password'] === $password) {
            session([
                'user' => [
                    'username' => $username,
                    'name' => $users[$username]['name'],
                    'role' => $users[$username]['role'],
                    'jenis' => $users[$username]['jenis'] ?? null,
                ]
            ]);

            return redirect()->route('dashboard')->with('success', 'Selamat datang kembali, ' . $users[$username]['name'] . '!');
        }

        return back()->withInput()->with('error', 'NIM/NIP atau kata sandi yang Anda masukkan salah.');
    }

    public function logout(Request $request)
    {
        $request->session()->forget('user');
        return redirect()->route('beranda')->with('success', 'Anda telah berhasil keluar.');
    }
}
