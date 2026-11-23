<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\CodeGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $role = (string) $request->query('role', 'all');

        $roleCounts = [
            'produsen' => User::query()->where('role', 'produsen')->count(),
            'wali' => User::query()->where('role', 'wali')->count(),
            'admin' => User::query()->where('role', 'admin')->count(),
        ];

        $query = User::query()->orderBy('id');

        if (in_array($role, ['produsen', 'wali', 'admin'], true)) {
            $query->where('role', $role);
        }

        if ($q !== '') {
            $like = '%'.strtolower($q).'%';
            $query->where(function ($sub) use ($like): void {
                $sub->whereRaw('LOWER(name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(nim) LIKE ?', [$like]);
            });
        }

        $users = $query->paginate(10)->withQueryString();

        return view('panel.pengguna', compact('users', 'roleCounts', 'q', 'role'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'nim' => ['required', 'string', 'max:60', 'unique:users,nim'],
            'role' => ['required', 'in:produsen,wali,admin'],
            'jenis' => ['required_if:role,produsen', 'nullable', 'in:Mahasiswa,Dosen'],
            'password' => ['required', 'string', 'min:6'],
            'aktif' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'nim.required' => 'NIM/NIP wajib diisi.',
            'nim.unique' => 'NIM/NIP sudah terdaftar.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Minimal 6 karakter.',
        ]);

        User::create([
            'code' => CodeGenerator::next('U', 'users'),
            'name' => trim($validated['name']),
            'nim' => trim($validated['nim']),
            'role' => $validated['role'],
            'jenis' => $validated['role'] === 'produsen' ? $validated['jenis'] : null,
            'password' => Hash::make($validated['password']),
            'aktif' => $request->boolean('aktif'),
        ]);

        return redirect()->route('panel.pengguna')
            ->with('success', 'Perubahan berhasil disimpan');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'nim' => ['required', 'string', 'max:60', Rule::unique('users', 'nim')->ignore($user->id)],
            'role' => ['required', 'in:produsen,wali,admin'],
            'jenis' => ['required_if:role,produsen', 'nullable', 'in:Mahasiswa,Dosen'],
            'password' => ['nullable', 'string', 'min:6'],
            'aktif' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'nim.required' => 'NIM/NIP wajib diisi.',
            'nim.unique' => 'NIM/NIP sudah terdaftar.',
            'password.min' => 'Minimal 6 karakter.',
        ]);

        $aktif = $request->boolean('aktif');
        if ($user->id === $request->user()->id && ($validated['role'] !== 'admin' || ! $aktif)) {
            return back()->withInput()
                ->with('error', 'Anda tidak dapat menurunkan peran atau menonaktifkan akun sendiri.');
        }

        $payload = [
            'name' => trim($validated['name']),
            'nim' => trim($validated['nim']),
            'role' => $validated['role'],
            'jenis' => $validated['role'] === 'produsen' ? $validated['jenis'] : null,
            'aktif' => $aktif,
        ];

        if (! empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        $user->update($payload);

        return redirect()->route('panel.pengguna')
            ->with('success', 'Perubahan berhasil disimpan');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        $user->delete();

        return redirect()->route('panel.pengguna')
            ->with('success', 'Pengguna dihapus.');
    }
}
