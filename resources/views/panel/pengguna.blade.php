@extends('layouts.panel')

@section('title', 'Pengguna & Peran - Panel Satu Data UNUSA')

@section('panel_content')
@php
    $authId = auth()->id();
    $roleFilterPills = [
        ['k' => 'all', 'label' => 'Semua', 'count' => ($roleCounts['produsen'] ?? 0) + ($roleCounts['wali'] ?? 0) + ($roleCounts['admin'] ?? 0)],
        ['k' => 'produsen', 'label' => 'Produsen Data', 'count' => $roleCounts['produsen'] ?? 0],
        ['k' => 'wali', 'label' => 'Wali Data', 'count' => $roleCounts['wali'] ?? 0],
        ['k' => 'admin', 'label' => 'Admin', 'count' => $roleCounts['admin'] ?? 0],
    ];
    $roleBadgeClass = static fn (string $r): string => match ($r) {
        'admin' => 'bg-violet-50 text-violet-700',
        'wali' => 'bg-sky-100/80 text-sky-700',
        default => 'bg-emerald-50 text-emerald-700',
    };
@endphp

<div x-data="userManager()">
    <x-panel-head
        title="Pengguna & Peran"
        desc="Kelola akun produsen data, wali data, dan administrator portal."
    >
        <button type="button" @click="openNew()" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-700 motion-safe:active:scale-[0.97]">
            <i data-lucide="user-plus" class="h-4 w-4"></i>
            Tambah pengguna
        </button>
    </x-panel-head>

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap gap-1.5">
            @foreach($roleFilterPills as $p)
                @php $active = $role === $p['k']; @endphp
                <a href="{{ route('panel.pengguna', array_filter(['role' => $p['k'] !== 'all' ? $p['k'] : null, 'q' => $q !== '' ? $q : null])) }}"
                   class="inline-flex items-center gap-1.5 rounded-full border px-3.5 py-1.5 text-xs font-semibold transition {{ $active ? 'border-sky-600 bg-sky-600 text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-sky-300 hover:text-sky-700' }}">
                    <span>{{ $p['label'] }}</span>
                    <span class="rounded-full px-1.5 text-[11px] {{ $active ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">{{ $p['count'] }}</span>
                </a>
            @endforeach
        </div>

        <form action="{{ route('panel.pengguna') }}" method="GET" class="flex items-center gap-2">
            @if($role !== 'all')
                <input type="hidden" name="role" value="{{ $role }}">
            @endif
            <div class="relative">
                <i data-lucide="search" class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                <input
                    type="search"
                    name="q"
                    value="{{ $q }}"
                    placeholder="Cari nama atau NIM/NIP..."
                    class="w-60 rounded-xl border border-slate-200 bg-white py-2 pr-3 pl-9 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                />
            </div>
            @if($q !== '')
                <a href="{{ route('panel.pengguna', $role !== 'all' ? ['role' => $role] : []) }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-surface">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[700px]">
                <thead class="bg-surface-strong">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-slate-500 uppercase">Pengguna</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-slate-500 uppercase">NIM / NIP</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-slate-500 uppercase">Peran</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-slate-500 uppercase">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $u)
                        <tr class="border-t border-slate-200/70 hover:bg-white">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-sky-100 text-xs font-bold text-sky-700">
                                        {{ $u->initials() }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-slate-900">
                                            {{ $u->name }}
                                            @if($u->id === $authId)
                                                <span class="ml-1 rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold text-slate-500">Anda</span>
                                            @endif
                                        </p>
                                        <p class="font-mono text-[11px] text-slate-400">{{ $u->code }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-slate-700">{{ $u->nim }}</td>
                            <td class="px-4 py-3 text-sm">
                                <span class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[11px] font-bold {{ $roleBadgeClass($u->role) }}">
                                    {{ $u->roleLabel() }}
                                </span>
                                @if($u->role === 'produsen' && $u->jenis)
                                    <span class="ml-1 text-xs text-slate-500">· {{ $u->jenis }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm">
                                @if($u->aktif)
                                    <span class="inline-flex rounded-md bg-emerald-50 px-2 py-0.5 text-[11px] font-bold text-emerald-700">Aktif</span>
                                @else
                                    <span class="inline-flex rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-500">Nonaktif</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-1.5">
                                    <button
                                        type="button"
                                        @click="openEdit({
                                            id: {{ $u->id }},
                                            name: @js($u->name),
                                            nim: @js($u->nim),
                                            role: @js($u->role),
                                            jenis: @js($u->jenis ?? 'Mahasiswa'),
                                            aktif: @json((bool) $u->aktif),
                                            action: @js(route('panel.pengguna.update', $u->id))
                                        })"
                                        class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-sky-300 hover:text-sky-700"
                                        aria-label="Ubah"
                                    >
                                        <i data-lucide="pencil" class="h-4 w-4"></i>
                                    </button>
                                    @if($u->id !== $authId)
                                        <button
                                            type="button"
                                            @click="$dispatch('open-confirm', {
                                                message: @js("Akun “{$u->name}” ({$u->nim}) akan dihapus permanen."),
                                                action: @js(route('panel.pengguna.destroy', $u->id)),
                                                method: 'DELETE'
                                            })"
                                            class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition hover:border-red-200 hover:bg-red-50 hover:text-[#DC3545]"
                                            aria-label="Hapus"
                                        >
                                            <i data-lucide="trash-2" class="h-4 w-4"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-sm text-slate-500">
                                Tidak ada pengguna yang cocok dengan filter pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">
        {{ $users->links('components.pagination') }}
    </div>

    <!-- Add / Edit User Modal -->
    <template x-if="modalOpen">
        <div class="fixed inset-0 z-50 grid place-items-end bg-slate-900/40 backdrop-blur-sm sm:place-items-center sm:p-6" @click.self="modalOpen = false" @keydown.escape.window="modalOpen = false">
            <div role="dialog" aria-modal="true" class="flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-t-2xl bg-white shadow-2xl sm:rounded-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 pt-5 pb-4 sm:px-8 sm:pt-6">
                    <div class="min-w-0">
                        <p class="text-sm text-slate-500">Akun pengguna</p>
                        <h2 class="mt-1 text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl" x-text="isEdit ? 'Ubah pengguna' : 'Tambah pengguna'"></h2>
                    </div>
                    <button type="button" @click="modalOpen = false" aria-label="Tutup" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 hover:border-sky-300 hover:text-sky-700">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>

                <form :action="formAction" method="POST" @submit="submit($event)" novalidate class="flex min-h-0 flex-col">
                    @csrf
                    <template x-if="isEdit">
                        <input type="hidden" name="_method" value="PUT">
                    </template>
                    <input type="hidden" name="_action_url" :value="formAction">
                    <input type="hidden" name="_edit_id" :value="editId ?? ''">
                    <input type="hidden" name="_is_edit" :value="isEdit ? '1' : '0'">

                    <div class="grid grid-cols-1 gap-5 overflow-y-auto px-5 py-6 sm:grid-cols-2 sm:px-8">
                        <label class="block sm:col-span-2">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">Nama lengkap <span class="text-[#DC3545]">*</span></span>
                            <input type="text" name="name" x-model="form.name" class="w-full rounded-xl border bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100" :class="err('name') ? 'border-[#DC3545]' : 'border-slate-200'" />
                            <span x-show="err('name')" class="mt-1 block text-xs text-[#DC3545]" x-text="err('name')"></span>
                        </label>

                        <label class="block">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">NIM / NIP <span class="text-[#DC3545]">*</span></span>
                            <input type="text" name="nim" x-model="form.nim" class="w-full rounded-xl border bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100" :class="err('nim') ? 'border-[#DC3545]' : 'border-slate-200'" />
                            <span x-show="err('nim')" class="mt-1 block text-xs text-[#DC3545]" x-text="err('nim')"></span>
                        </label>

                        <label class="block">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">Peran <span class="text-[#DC3545]">*</span></span>
                            <select name="role" x-model="form.role" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                                @foreach(\App\Models\User::ROLE_LABELS as $rk => $rl)
                                    <option value="{{ $rk }}">{{ $rl }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="block" x-show="form.role === 'produsen'">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">Jenis Produsen <span class="text-[#DC3545]">*</span></span>
                            <select name="jenis" x-model="form.jenis" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                                <option value="Mahasiswa">Mahasiswa</option>
                                <option value="Dosen">Dosen</option>
                            </select>
                            <span x-show="err('jenis')" class="mt-1 block text-xs text-[#DC3545]" x-text="err('jenis')"></span>
                        </label>

                        <label class="block" :class="form.role !== 'produsen' ? 'sm:col-span-2' : ''">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">
                                Password <span x-show="!isEdit" class="text-[#DC3545]">*</span>
                            </span>
                            <input type="password" name="password" x-model="form.password" autocomplete="new-password" class="w-full rounded-xl border bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100" :class="err('password') ? 'border-[#DC3545]' : 'border-slate-200'" />
                            <span x-show="err('password')" class="mt-1 block text-xs text-[#DC3545]" x-text="err('password')"></span>
                            <span x-show="!err('password') && isEdit" class="mt-1 block text-xs text-slate-500">Kosongkan bila tidak ingin mengubah password.</span>
                        </label>

                        <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 sm:col-span-2">
                            <div>
                                <p class="text-sm font-semibold text-slate-800">Akun aktif</p>
                                <p class="text-xs text-slate-500">Pengguna nonaktif tidak dapat masuk ke sistem.</p>
                            </div>
                            <input type="hidden" name="aktif" :value="form.aktif ? '1' : '0'">
                            <button
                                type="button"
                                role="switch"
                                :aria-checked="form.aktif"
                                @click="form.aktif = !form.aktif"
                                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full p-0.5 transition-colors"
                                :class="form.aktif ? 'bg-sky-600' : 'bg-slate-300'"
                            >
                                <span class="inline-block h-5 w-5 rounded-full bg-white shadow transition-transform" :class="form.aktif ? 'translate-x-5' : 'translate-x-0'"></span>
                            </button>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:px-8">
                        <button type="button" @click="modalOpen = false" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Batal
                        </button>
                        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white hover:bg-sky-700">
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>
@endsection

@push('scripts')
<script>
function userManager() {
    const serverErrors = @json($errors->getMessages());
    const hasServerErrors = Object.keys(serverErrors).length > 0;
    const oldIsEdit = @json(old('_is_edit') === '1');
    const oldEditId = @json(old('_edit_id') ? (int) old('_edit_id') : null);
    const oldAction = @json(old('_action_url'));

    return {
        modalOpen: hasServerErrors,
        isEdit: hasServerErrors ? oldIsEdit : false,
        editId: hasServerErrors ? oldEditId : null,
        formAction: hasServerErrors && oldAction ? oldAction : @js(route('panel.pengguna.store')),
        tried: hasServerErrors,
        serverErrors: hasServerErrors ? serverErrors : {},
        form: hasServerErrors
            ? {
                name: @js(old('name', '')),
                nim: @js(old('nim', '')),
                role: @js(old('role', 'produsen')),
                jenis: @js(old('jenis', 'Mahasiswa')),
                password: '',
                aktif: @json(old('aktif', '1') === '1'),
            }
            : { name: '', nim: '', role: 'produsen', jenis: 'Mahasiswa', password: '', aktif: true },

        openNew() {
            this.isEdit = false;
            this.editId = null;
            this.formAction = @js(route('panel.pengguna.store'));
            this.form = { name: '', nim: '', role: 'produsen', jenis: 'Mahasiswa', password: '', aktif: true };
            this.tried = false;
            this.serverErrors = {};
            this.modalOpen = true;
        },

        openEdit(user) {
            this.isEdit = true;
            this.editId = user.id;
            this.formAction = user.action;
            this.form = {
                name: user.name,
                nim: user.nim,
                role: user.role,
                jenis: user.jenis || 'Mahasiswa',
                password: '',
                aktif: Boolean(user.aktif),
            };
            this.tried = false;
            this.serverErrors = {};
            this.modalOpen = true;
        },

        errors() {
            const e = {};
            if (!String(this.form.name || '').trim()) e.name = 'Nama lengkap wajib diisi.';
            if (!String(this.form.nim || '').trim()) e.nim = 'NIM/NIP wajib diisi.';
            const pwd = String(this.form.password || '');
            if (!this.isEdit && !pwd) {
                e.password = 'Password wajib diisi.';
            } else if (pwd && pwd.length < 6) {
                e.password = 'Minimal 6 karakter.';
            }
            return e;
        },

        err(k) {
            if (this.serverErrors[k]?.length) return this.serverErrors[k][0];
            return this.tried ? (this.errors()[k] || '') : '';
        },

        submit(ev) {
            this.tried = true;
            this.serverErrors = {};
            if (Object.keys(this.errors()).length > 0) {
                ev.preventDefault();
            }
        },
    };
}
</script>
@endpush
