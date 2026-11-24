@extends('layouts.panel')

@section('title', 'Konten Beranda & Tentang Kami - Panel Satu Data UNUSA')

@section('panel_content')
@php
    $tabs = [
        ['k' => 'profil', 'label' => 'Profil Prodi'],
        ['k' => 'dosen', 'label' => 'Dosen', 'count' => $lecturers->count()],
        ['k' => 'prestasi', 'label' => 'Prestasi', 'count' => $achievements->count()],
        ['k' => 'berita', 'label' => 'Berita', 'count' => $news->count()],
        ['k' => 'kurikulum', 'label' => 'Kurikulum', 'count' => $courses->count()],
    ];
    $tingkatClass = static fn (string $t): string => match ($t) {
        'Internasional' => 'bg-violet-50 text-violet-700',
        'Nasional' => 'bg-sky-100/80 text-sky-700',
        default => 'bg-emerald-50 text-emerald-700',
    };
@endphp

<div x-data="contentManager()">
    <x-panel-head
        title="Konten Beranda & Tentang Kami"
        desc="Perbarui profil program studi, dosen pengampu, prestasi, berita, dan kurikulum tanpa mengubah kode program."
    />

    <div class="mb-6 flex gap-1 overflow-x-auto border-b border-slate-200">
        @foreach($tabs as $t)
            @php $active = $tab === $t['k']; @endphp
            <a href="{{ route('panel.konten', ['tab' => $t['k']]) }}"
               class="-mb-px inline-flex shrink-0 items-center gap-2 border-b-2 px-4 py-2.5 text-sm font-semibold whitespace-nowrap transition {{ $active ? 'border-sky-600 text-sky-700' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
                <span>{{ $t['label'] }}</span>
                @isset($t['count'])
                    <span class="rounded-full px-1.5 text-[11px] font-bold {{ $active ? 'bg-sky-100 text-sky-700' : 'bg-slate-100 text-slate-600' }}">
                        {{ $t['count'] }}
                    </span>
                @endisset
            </a>
        @endforeach
    </div>

    @if($errors->any())
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-[#b02a37]" role="alert">
            <p class="font-bold">Periksa kembali data yang dikirim:</p>
            <ul class="mt-1 list-disc pl-5 text-xs">
                @foreach($errors->all() as $msg)
                    <li>{{ $msg }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($tab === 'profil')
        <form action="{{ route('panel.konten.profil.update') }}" method="POST" class="rounded-2xl border border-slate-200 bg-surface p-6">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <label class="block">
                    <span class="mb-1.5 block text-sm font-semibold text-slate-800">Nama Program Studi</span>
                    <input type="text" name="program" value="{{ old('program', $profil?->program ?? 'S1 Sistem Informasi') }}" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-sm font-semibold text-slate-800">Gelar Lulusan</span>
                    <input type="text" name="gelar" value="{{ old('gelar', $profil?->gelar ?? 'Sarjana Komputer (S.Kom.)') }}" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-sm font-semibold text-slate-800">Akreditasi <span class="text-[#DC3545]">*</span></span>
                    <input type="text" name="akreditasi" value="{{ old('akreditasi', $profil?->akreditasi) }}" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100" required />
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-sm font-semibold text-slate-800">Tahun Berdiri</span>
                    <input type="text" name="berdiri" value="{{ old('berdiri', $profil?->berdiri) }}" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-sm font-semibold text-slate-800">Durasi Studi</span>
                    <input type="text" name="durasi" value="{{ old('durasi', $profil?->durasi) }}" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-sm font-semibold text-slate-800">Fakultas</span>
                    <input type="text" name="fakultas" value="{{ old('fakultas', $profil?->fakultas) }}" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-sm font-semibold text-slate-800">Email Resmi <span class="text-[#DC3545]">*</span></span>
                    <input type="email" name="email" value="{{ old('email', $profil?->email) }}" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100" required />
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-sm font-semibold text-slate-800">Telepon</span>
                    <input type="text" name="telp" value="{{ old('telp', $profil?->telp) }}" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                </label>
                <label class="block sm:col-span-2">
                    <span class="mb-1.5 block text-sm font-semibold text-slate-800">Alamat Kampus</span>
                    <input type="text" name="alamat" value="{{ old('alamat', $profil?->alamat) }}" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                </label>
                <label class="block sm:col-span-2">
                    <span class="mb-1.5 block text-sm font-semibold text-slate-800">Deskripsi Singkat <span class="text-[#DC3545]">*</span></span>
                    <textarea rows="3" name="deskripsi" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100" required>{{ old('deskripsi', $profil?->deskripsi) }}</textarea>
                </label>
                <label class="block sm:col-span-2">
                    <span class="mb-1.5 block text-sm font-semibold text-slate-800">Visi <span class="text-[#DC3545]">*</span></span>
                    <textarea rows="3" name="visi" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100" required>{{ old('visi', $profil?->visi) }}</textarea>
                </label>
                <label class="block sm:col-span-2">
                    <span class="mb-1.5 block text-sm font-semibold text-slate-800">Misi <span class="text-[#DC3545]">*</span></span>
                    <textarea rows="5" name="misi_text" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100" required>{{ old('misi_text', implode("\n", $profil?->misi ?? [])) }}</textarea>
                    <span class="mt-1 block text-xs text-slate-500">Tulis satu poin misi per baris.</span>
                </label>
                <label class="block sm:col-span-2">
                    <span class="mb-1.5 block text-sm font-semibold text-slate-800">Sejarah</span>
                    <textarea rows="3" name="sejarah" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100">{{ old('sejarah', $profil?->sejarah) }}</textarea>
                </label>
                <label class="block sm:col-span-2">
                    <span class="mb-1.5 block text-sm font-semibold text-slate-800">Syarat Pendaftaran</span>
                    <textarea rows="2" name="syarat" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100">{{ old('syarat', $profil?->syarat) }}</textarea>
                </label>
                <label class="block sm:col-span-2">
                    <span class="mb-1.5 block text-sm font-semibold text-slate-800">Fasilitas</span>
                    <textarea rows="2" name="fasilitas" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100">{{ old('fasilitas', $profil?->fasilitas) }}</textarea>
                </label>
            </div>
            <div class="mt-6 flex justify-end border-t border-slate-200 pt-5">
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-brand px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-700 motion-safe:active:scale-[0.97]">
                    <i data-lucide="save" class="h-4 w-4"></i>
                    Simpan profil
                </button>
            </div>
        </form>
    @elseif($tab === 'dosen')
        <div class="mb-4 flex items-center justify-between gap-3">
            <p class="text-sm text-slate-600">{{ $lecturers->count() }} dosen ditampilkan pada halaman Beranda & Tentang Kami.</p>
            <button type="button" @click="openLecturer()" class="inline-flex items-center gap-2 rounded-xl bg-brand px-4 py-2 text-sm font-semibold text-white transition hover:bg-sky-700">
                <i data-lucide="plus" class="h-4 w-4"></i>
                Tambah dosen
            </button>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            @foreach($lecturers as $d)
                <article class="flex flex-col justify-between rounded-2xl border border-slate-200 bg-surface p-5">
                    <div class="flex items-start gap-3.5">
                        @if($d->foto)
                            <img src="{{ $d->foto }}" alt="{{ $d->nama }}" class="h-14 w-14 shrink-0 rounded-full border border-slate-200 object-cover" />
                        @else
                            <span class="grid h-14 w-14 shrink-0 place-items-center rounded-full bg-sky-100 text-sm font-bold text-sky-700">
                                {{ $d->initials() }}
                            </span>
                        @endif
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="font-mono text-[11px] font-bold text-sky-700">{{ $d->code }}</span>
                                <span class="rounded bg-sky-100/80 px-1.5 py-0.5 text-[10px] font-bold text-sky-700">{{ $d->pendidikan }}</span>
                                <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold text-slate-600">{{ $d->jabatan }}</span>
                            </div>
                            <h3 class="mt-1 text-sm font-bold text-slate-900">{{ $d->nama }}</h3>
                            <p class="text-xs text-slate-500">NIDN {{ $d->nidn }} · {{ $d->bidang }}</p>
                            @if($d->mata_kuliah)
                                <p class="mt-1.5 line-clamp-1 text-xs text-slate-600">{{ $d->mata_kuliah }}</p>
                            @endif
                        </div>
                    </div>
                    <div class="mt-4 flex justify-end gap-1.5 border-t border-slate-200/70 pt-3">
                        <button
                            type="button"
                            @click="openLecturer({
                                isEdit: true,
                                action: @js(route('panel.konten.dosen.update', $d->id)),
                                nama: @js($d->nama),
                                nidn: @js($d->nidn),
                                pendidikan: @js($d->pendidikan),
                                jabatan: @js($d->jabatan),
                                bidang: @js($d->bidang),
                                foto: @js($d->foto ?? ''),
                                email: @js($d->email ?? ''),
                                mata_kuliah: @js($d->mata_kuliah ?? ''),
                                profil: @js($d->profil ?? '')
                            })"
                            class="inline-flex h-8 items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 text-xs font-semibold text-slate-700 hover:border-sky-300 hover:text-sky-700"
                        >
                            Ubah
                        </button>
                        <button
                            type="button"
                            @click="$dispatch('open-confirm', {
                                message: @js("Dosen “{$d->nama}” akan dihapus."),
                                action: @js(route('panel.konten.dosen.destroy', $d->id)),
                                method: 'DELETE'
                            })"
                            class="inline-flex h-8 items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 text-xs font-semibold text-[#DC3545] hover:border-red-200 hover:bg-red-50"
                        >
                            Hapus
                        </button>
                    </div>
                </article>
            @endforeach
        </div>
    @elseif($tab === 'prestasi')
        <div class="mb-4 flex items-center justify-between gap-3">
            <p class="text-sm text-slate-600">{{ $achievements->count() }} prestasi mahasiswa & dosen tercatat.</p>
            <button type="button" @click="openAchievement()" class="inline-flex items-center gap-2 rounded-xl bg-brand px-4 py-2 text-sm font-semibold text-white transition hover:bg-sky-700">
                <i data-lucide="plus" class="h-4 w-4"></i>
                Tambah prestasi
            </button>
        </div>

        <div class="space-y-3">
            @foreach($achievements as $p)
                <article class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-surface p-4 sm:p-5">
                    <div class="flex min-w-0 flex-1 items-center gap-4">
                        @if($p->foto)
                            <img src="{{ $p->foto }}" alt="{{ $p->judul }}" class="h-14 w-14 shrink-0 rounded-xl border border-slate-200 object-cover" />
                        @else
                            <span class="grid h-14 w-14 shrink-0 place-items-center rounded-xl bg-[#F0F9FF] text-[#0069A8]">
                                <i data-lucide="trophy" class="h-6 w-6"></i>
                            </span>
                        @endif
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-mono text-[11px] font-bold text-[#0069A8]">{{ $p->code }}</span>
                                <span class="rounded-md px-2 py-0.5 text-[11px] font-bold {{ $tingkatClass($p->tingkat) }}">{{ $p->tingkat }}</span>
                                <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-700">{{ $p->peringkat }}</span>
                                <span class="text-xs text-slate-500">{{ $p->tahun }}</span>
                            </div>
                            <h3 class="mt-1 truncate text-sm font-bold text-slate-900">{{ $p->judul }}</h3>
                            <p class="text-xs text-slate-600">{{ $p->nama }}</p>
                        </div>
                    </div>
                    <div class="flex shrink-0 gap-1.5">
                        <button
                            type="button"
                            @click="openAchievement({
                                isEdit: true,
                                action: @js(route('panel.konten.prestasi.update', $p->id)),
                                judul: @js($p->judul),
                                nama: @js($p->nama),
                                tingkat: @js($p->tingkat),
                                peringkat: @js($p->peringkat),
                                tahun: @js((string) $p->tahun),
                                foto: @js($p->foto ?? '')
                            })"
                            class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-2.5 text-xs font-semibold text-slate-700 hover:border-sky-300 hover:text-sky-700"
                        >
                            Ubah
                        </button>
                        <button
                            type="button"
                            @click="$dispatch('open-confirm', {
                                message: @js("Prestasi “{$p->judul}” akan dihapus."),
                                action: @js(route('panel.konten.prestasi.destroy', $p->id)),
                                method: 'DELETE'
                            })"
                            class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-2.5 text-xs font-semibold text-[#DC3545] hover:border-red-200 hover:bg-red-50"
                        >
                            Hapus
                        </button>
                    </div>
                </article>
            @endforeach
        </div>
    @elseif($tab === 'berita')
        <div class="mb-4 flex items-center justify-between gap-3">
            <p class="text-sm text-slate-600">{{ $news->count() }} artikel berita tampil di halaman Beranda & Berita.</p>
            <button type="button" @click="openNews()" class="inline-flex items-center gap-2 rounded-xl bg-brand px-4 py-2 text-sm font-semibold text-white transition hover:bg-sky-700">
                <i data-lucide="plus" class="h-4 w-4"></i>
                Tambah berita
            </button>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            @foreach($news as $b)
                <article class="flex flex-col justify-between rounded-2xl border border-slate-200 bg-surface p-5">
                    <div class="flex gap-4">
                        <img src="{{ $b->image_url }}" alt="{{ $b->title }}" class="h-20 w-24 shrink-0 rounded-xl border border-slate-200 object-cover" />
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="font-mono text-[11px] font-bold text-sky-700">{{ $b->code }}</span>
                                <span class="rounded bg-sky-100/80 px-1.5 py-0.5 text-[10px] font-bold text-sky-700">{{ $b->badge }}</span>
                                @if($b->corner)
                                    <span class="rounded bg-amber-50 px-1.5 py-0.5 text-[10px] font-bold text-amber-700">{{ $b->corner }}</span>
                                @endif
                            </div>
                            <h3 class="mt-1 line-clamp-2 text-sm font-bold text-slate-900">{{ $b->title }}</h3>
                            <p class="mt-1 text-xs text-slate-500">{{ $b->date_label }} · {{ $b->author }} · {{ $b->read_minutes }} menit</p>
                        </div>
                    </div>
                    <p class="mt-3 line-clamp-2 text-xs text-slate-600">{{ $b->excerpt }}</p>
                    <div class="mt-4 flex items-center justify-between gap-2 border-t border-slate-200/70 pt-3">
                        <a href="{{ route('berita.show', $b->code) }}" target="_blank" class="text-xs font-semibold text-sky-700 hover:underline">
                            Lihat artikel &rarr;
                        </a>
                        <div class="flex gap-1.5">
                            <button
                                type="button"
                                @click="openNews({
                                    isEdit: true,
                                    action: @js(route('panel.konten.berita.update', $b->id)),
                                    title: @js($b->title),
                                    badge: @js($b->badge),
                                    corner: @js($b->corner ?? ''),
                                    date_label: @js($b->date_label),
                                    author: @js($b->author),
                                    read_minutes: @js((string) $b->read_minutes),
                                    image_url: @js($b->image_url),
                                    excerpt: @js($b->excerpt),
                                    body_text: @js(implode("\n\n", $b->body ?? []))
                                })"
                                class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-2.5 text-xs font-semibold text-slate-700 hover:border-sky-300 hover:text-sky-700"
                            >
                                Ubah
                            </button>
                            <button
                                type="button"
                                @click="$dispatch('open-confirm', {
                                    message: @js("Berita “{$b->title}” akan dihapus."),
                                    action: @js(route('panel.konten.berita.destroy', $b->id)),
                                    method: 'DELETE'
                                })"
                                class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-2.5 text-xs font-semibold text-[#DC3545] hover:border-red-200 hover:bg-red-50"
                            >
                                Hapus
                            </button>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @elseif($tab === 'kurikulum')
        <div class="mb-4 flex items-center justify-between gap-3">
            <p class="text-sm text-slate-600">{{ $courses->count() }} mata kuliah terdaftar pada struktur kurikulum.</p>
            <button type="button" @click="openCourse()" class="inline-flex items-center gap-2 rounded-xl bg-brand px-4 py-2 text-sm font-semibold text-white transition hover:bg-sky-700">
                <i data-lucide="plus" class="h-4 w-4"></i>
                Tambah mata kuliah
            </button>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-surface">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[680px]">
                    <thead class="bg-surface-strong">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">Kode MK</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">Nama Mata Kuliah</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">Tahun</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">Semester</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">SKS</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">Sifat</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($courses as $c)
                            <tr class="border-t border-slate-200/70 hover:bg-white">
                                <td class="px-4 py-3 font-mono text-xs font-bold text-sky-700">{{ $c->kode }}</td>
                                <td class="px-4 py-3 text-sm font-semibold text-slate-900">{{ $c->nama }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $c->tahun }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">Semester {{ $c->semester }}</td>
                                <td class="px-4 py-3 text-sm font-semibold text-slate-700">{{ $c->sks }} SKS</td>
                                <td class="px-4 py-3 text-sm">
                                    @if($c->pilihan)
                                        <span class="rounded-md bg-amber-50 px-2 py-0.5 text-[11px] font-bold text-amber-700">Pilihan</span>
                                    @else
                                        <span class="rounded-md bg-emerald-50 px-2 py-0.5 text-[11px] font-bold text-emerald-700">Wajib</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-1.5">
                                        <button
                                            type="button"
                                            @click="openCourse({
                                                isEdit: true,
                                                action: @js(route('panel.konten.kurikulum.update', $c->id)),
                                                kode: @js($c->kode),
                                                nama: @js($c->nama),
                                                tahun: @js((string) $c->tahun),
                                                semester: @js((string) $c->semester),
                                                sks: @js((string) $c->sks),
                                                pilihan: @json((bool) $c->pilihan)
                                            })"
                                            class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-2.5 text-xs font-semibold text-slate-700 hover:border-sky-300 hover:text-sky-700"
                                        >
                                            Ubah
                                        </button>
                                        <button
                                            type="button"
                                            @click="$dispatch('open-confirm', {
                                                message: @js("Mata kuliah “{$c->kode} - {$c->nama}” akan dihapus."),
                                                action: @js(route('panel.konten.kurikulum.destroy', $c->id)),
                                                method: 'DELETE'
                                            })"
                                            class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-2.5 text-xs font-semibold text-[#DC3545] hover:border-red-200 hover:bg-red-50"
                                        >
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Lecturer Modal -->
    <template x-if="modalType === 'dosen'">
        <div class="fixed inset-0 z-50 grid place-items-end bg-slate-900/50 sm:place-items-center sm:p-6" @click.self="modalType = null" @keydown.escape.window="modalType = null">
            <div role="dialog" aria-modal="true" class="flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-t-2xl bg-white shadow-2xl sm:rounded-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 pt-5 pb-4 sm:px-8 sm:pt-6">
                    <div>
                        <p class="text-sm text-slate-500">Data Dosen</p>
                        <h2 class="mt-1 text-xl font-extrabold text-slate-900" x-text="dosen.isEdit ? 'Ubah data dosen' : 'Tambah dosen'"></h2>
                    </div>
                    <button type="button" @click="modalType = null" aria-label="Tutup" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 hover:border-sky-300 hover:text-sky-700">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <form :action="dosen.action" method="POST" class="flex min-h-0 flex-col">
                    @csrf
                    <template x-if="dosen.isEdit"><input type="hidden" name="_method" value="PUT"></template>
                    <div class="grid grid-cols-1 gap-4 overflow-y-auto px-5 py-6 sm:grid-cols-2 sm:px-8">
                        <label class="block sm:col-span-2">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">Nama lengkap & gelar <span class="text-[#DC3545]">*</span></span>
                            <input type="text" name="nama" x-model="dosen.nama" required class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">NIDN (10 digit) <span class="text-[#DC3545]">*</span></span>
                            <input type="text" name="nidn" x-model="dosen.nidn" required class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">Pendidikan <span class="text-[#DC3545]">*</span></span>
                            <select name="pendidikan" x-model="dosen.pendidikan" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                                @foreach($pendidikanOptions as $opt)
                                    <option value="{{ $opt }}">{{ $opt }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">Jabatan Akademik <span class="text-[#DC3545]">*</span></span>
                            <select name="jabatan" x-model="dosen.jabatan" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                                @foreach($jabatanOptions as $opt)
                                    <option value="{{ $opt }}">{{ $opt }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">Bidang Keahlian <span class="text-[#DC3545]">*</span></span>
                            <input type="text" name="bidang" x-model="dosen.bidang" required class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">Email</span>
                            <input type="email" name="email" x-model="dosen.email" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">URL Foto (HTTPS)</span>
                            <input type="url" name="foto" x-model="dosen.foto" placeholder="https://..." class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                        </label>
                        <label class="block sm:col-span-2">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">Mata Kuliah Diampu</span>
                            <input type="text" name="mata_kuliah" x-model="dosen.mata_kuliah" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                        </label>
                        <label class="block sm:col-span-2">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">Profil Singkat</span>
                            <textarea rows="3" name="profil" x-model="dosen.profil" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100"></textarea>
                        </label>
                    </div>
                    <div class="flex justify-end gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:px-8">
                        <button type="button" @click="modalType = null" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</button>
                        <button type="submit" class="rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white hover:bg-sky-700">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- Achievement Modal -->
    <template x-if="modalType === 'prestasi'">
        <div class="fixed inset-0 z-50 grid place-items-end bg-slate-900/50 sm:place-items-center sm:p-6" @click.self="modalType = null" @keydown.escape.window="modalType = null">
            <div role="dialog" aria-modal="true" class="flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-t-2xl bg-white shadow-2xl sm:rounded-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 pt-5 pb-4 sm:px-8 sm:pt-6">
                    <div>
                        <p class="text-sm text-slate-500">Prestasi</p>
                        <h2 class="mt-1 text-xl font-extrabold text-slate-900" x-text="prestasi.isEdit ? 'Ubah prestasi' : 'Tambah prestasi'"></h2>
                    </div>
                    <button type="button" @click="modalType = null" aria-label="Tutup" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 hover:border-sky-300 hover:text-sky-700">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <form :action="prestasi.action" method="POST" class="flex min-h-0 flex-col">
                    @csrf
                    <template x-if="prestasi.isEdit"><input type="hidden" name="_method" value="PUT"></template>
                    <div class="grid grid-cols-1 gap-4 overflow-y-auto px-5 py-6 sm:grid-cols-2 sm:px-8">
                        <label class="block sm:col-span-2">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">Judul Prestasi <span class="text-[#DC3545]">*</span></span>
                            <input type="text" name="judul" x-model="prestasi.judul" required class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                        </label>
                        <label class="block sm:col-span-2">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">Nama Peraih / Tim <span class="text-[#DC3545]">*</span></span>
                            <input type="text" name="nama" x-model="prestasi.nama" required class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">Tingkat <span class="text-[#DC3545]">*</span></span>
                            <select name="tingkat" x-model="prestasi.tingkat" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                                @foreach($tingkatOptions as $opt)
                                    <option value="{{ $opt }}">{{ $opt }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">Peringkat <span class="text-[#DC3545]">*</span></span>
                            <input type="text" name="peringkat" x-model="prestasi.peringkat" required placeholder="Contoh: Juara 1 / Finalis" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">Tahun (4 digit) <span class="text-[#DC3545]">*</span></span>
                            <input type="number" name="tahun" x-model="prestasi.tahun" required class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">URL Foto (HTTPS)</span>
                            <input type="url" name="foto" x-model="prestasi.foto" placeholder="https://..." class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                        </label>
                    </div>
                    <div class="flex justify-end gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:px-8">
                        <button type="button" @click="modalType = null" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</button>
                        <button type="submit" class="rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white hover:bg-sky-700">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- News Modal -->
    <template x-if="modalType === 'berita'">
        <div class="fixed inset-0 z-50 grid place-items-end bg-slate-900/50 sm:place-items-center sm:p-6" @click.self="modalType = null" @keydown.escape.window="modalType = null">
            <div role="dialog" aria-modal="true" class="flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-t-2xl bg-white shadow-2xl sm:rounded-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 pt-5 pb-4 sm:px-8 sm:pt-6">
                    <div>
                        <p class="text-sm text-slate-500">Artikel Berita</p>
                        <h2 class="mt-1 text-xl font-extrabold text-slate-900" x-text="berita.isEdit ? 'Ubah berita' : 'Tambah berita'"></h2>
                    </div>
                    <button type="button" @click="modalType = null" aria-label="Tutup" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 hover:border-sky-300 hover:text-sky-700">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <form :action="berita.action" method="POST" class="flex min-h-0 flex-col">
                    @csrf
                    <template x-if="berita.isEdit"><input type="hidden" name="_method" value="PUT"></template>
                    <div class="grid grid-cols-1 gap-4 overflow-y-auto px-5 py-6 sm:grid-cols-2 sm:px-8">
                        <label class="block sm:col-span-2">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">Judul Berita <span class="text-[#DC3545]">*</span></span>
                            <input type="text" name="title" x-model="berita.title" required class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">Kategori / Tag <span class="text-[#DC3545]">*</span></span>
                            <select name="badge" x-model="berita.badge" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                                @foreach($newsTags as $tag)
                                    <option value="{{ $tag }}">{{ $tag }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">Label Pojok (opsional)</span>
                            <input type="text" name="corner" x-model="berita.corner" placeholder="Contoh: Rilis Data" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">Tanggal Tampil <span class="text-[#DC3545]">*</span></span>
                            <input type="text" name="date_label" x-model="berita.date_label" required placeholder="Contoh: 18 Mei 2026" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">Waktu Baca (menit) <span class="text-[#DC3545]">*</span></span>
                            <input type="number" min="1" name="read_minutes" x-model="berita.read_minutes" required class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">Penulis <span class="text-[#DC3545]">*</span></span>
                            <input type="text" name="author" x-model="berita.author" required class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">URL Gambar Cover (HTTPS) <span class="text-[#DC3545]">*</span></span>
                            <input type="url" name="image_url" x-model="berita.image_url" required placeholder="https://..." class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                        </label>
                        <label class="block sm:col-span-2">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">Ringkasan <span class="text-[#DC3545]">*</span></span>
                            <textarea rows="2" name="excerpt" x-model="berita.excerpt" required class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100"></textarea>
                        </label>
                        <label class="block sm:col-span-2">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">Isi Lengkap Berita <span class="text-[#DC3545]">*</span></span>
                            <textarea rows="5" name="body_text" x-model="berita.body_text" required class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100"></textarea>
                            <span class="mt-1 block text-xs text-slate-500">Pisahkan antar paragraf dengan satu baris kosong.</span>
                        </label>
                    </div>
                    <div class="flex justify-end gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:px-8">
                        <button type="button" @click="modalType = null" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</button>
                        <button type="submit" class="rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white hover:bg-sky-700">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- Course Modal -->
    <template x-if="modalType === 'kurikulum'">
        <div class="fixed inset-0 z-50 grid place-items-end bg-slate-900/50 sm:place-items-center sm:p-6" @click.self="modalType = null" @keydown.escape.window="modalType = null">
            <div role="dialog" aria-modal="true" class="flex max-h-[92vh] w-full max-w-lg flex-col overflow-hidden rounded-t-2xl bg-white shadow-2xl sm:rounded-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 pt-5 pb-4 sm:px-8 sm:pt-6">
                    <div>
                        <p class="text-sm text-slate-500">Kurikulum</p>
                        <h2 class="mt-1 text-xl font-extrabold text-slate-900" x-text="kurikulum.isEdit ? 'Ubah mata kuliah' : 'Tambah mata kuliah'"></h2>
                    </div>
                    <button type="button" @click="modalType = null" aria-label="Tutup" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 hover:border-sky-300 hover:text-sky-700">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <form :action="kurikulum.action" method="POST" class="flex min-h-0 flex-col">
                    @csrf
                    <template x-if="kurikulum.isEdit"><input type="hidden" name="_method" value="PUT"></template>
                    <div class="grid grid-cols-1 gap-4 overflow-y-auto px-5 py-6 sm:grid-cols-2 sm:px-8">
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">Kode MK <span class="text-[#DC3545]">*</span></span>
                            <input type="text" name="kode" x-model="kurikulum.kode" required placeholder="Contoh: SI-401" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">Tahun Kurikulum <span class="text-[#DC3545]">*</span></span>
                            <input type="number" name="tahun" x-model="kurikulum.tahun" required class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                        </label>
                        <label class="block sm:col-span-2">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">Nama Mata Kuliah <span class="text-[#DC3545]">*</span></span>
                            <input type="text" name="nama" x-model="kurikulum.nama" required class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">Semester (1–8) <span class="text-[#DC3545]">*</span></span>
                            <input type="number" min="1" max="8" name="semester" x-model="kurikulum.semester" required class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-800">Bobot SKS (1–12) <span class="text-[#DC3545]">*</span></span>
                            <input type="number" min="1" max="12" name="sks" x-model="kurikulum.sks" required class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                        </label>
                        <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 sm:col-span-2">
                            <div>
                                <p class="text-sm font-semibold text-slate-800">Mata kuliah pilihan</p>
                                <p class="text-xs text-slate-500">Matikan jika termasuk mata kuliah wajib.</p>
                            </div>
                            <input type="hidden" name="pilihan" :value="kurikulum.pilihan ? '1' : '0'">
                            <button
                                type="button"
                                role="switch"
                                :aria-checked="kurikulum.pilihan"
                                @click="kurikulum.pilihan = !kurikulum.pilihan"
                                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full p-0.5 transition-colors"
                                :class="kurikulum.pilihan ? 'bg-sky-600' : 'bg-slate-300'"
                            >
                                <span class="inline-block h-5 w-5 rounded-full bg-white shadow transition-transform" :class="kurikulum.pilihan ? 'translate-x-5' : 'translate-x-0'"></span>
                            </button>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:px-8">
                        <button type="button" @click="modalType = null" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</button>
                        <button type="submit" class="rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white hover:bg-sky-700">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>
@endsection

@push('scripts')
<script>
function contentManager() {
    return {
        modalType: null,
        dosen: { isEdit: false, action: @js(route('panel.konten.dosen.store')), nama: '', nidn: '', pendidikan: @js($pendidikanOptions[0] ?? 'S2'), jabatan: @js($jabatanOptions[0] ?? 'Lektor'), bidang: '', foto: '', email: '', mata_kuliah: '', profil: '' },
        prestasi: { isEdit: false, action: @js(route('panel.konten.prestasi.store')), judul: '', nama: '', tingkat: @js($tingkatOptions[0] ?? 'Nasional'), peringkat: '', tahun: String(new Date().getFullYear()), foto: '' },
        berita: { isEdit: false, action: @js(route('panel.konten.berita.store')), title: '', badge: @js($newsTags[0] ?? 'Rilis Data'), corner: '', date_label: '', author: 'Tim Satu Data SI', read_minutes: '4', image_url: '', excerpt: '', body_text: '' },
        kurikulum: { isEdit: false, action: @js(route('panel.konten.kurikulum.store')), kode: '', nama: '', tahun: String(new Date().getFullYear()), semester: '1', sks: '3', pilihan: false },

        openLecturer(item = null) {
            this.dosen = item || {
                isEdit: false,
                action: @js(route('panel.konten.dosen.store')),
                nama: '',
                nidn: '',
                pendidikan: @js($pendidikanOptions[0] ?? 'S2'),
                jabatan: @js($jabatanOptions[0] ?? 'Lektor'),
                bidang: '',
                foto: '',
                email: '',
                mata_kuliah: '',
                profil: '',
            };
            this.modalType = 'dosen';
        },

        openAchievement(item = null) {
            this.prestasi = item || {
                isEdit: false,
                action: @js(route('panel.konten.prestasi.store')),
                judul: '',
                nama: '',
                tingkat: @js($tingkatOptions[0] ?? 'Nasional'),
                peringkat: '',
                tahun: String(new Date().getFullYear()),
                foto: '',
            };
            this.modalType = 'prestasi';
        },

        openNews(item = null) {
            this.berita = item || {
                isEdit: false,
                action: @js(route('panel.konten.berita.store')),
                title: '',
                badge: @js($newsTags[0] ?? 'Rilis Data'),
                corner: '',
                date_label: '',
                author: 'Tim Satu Data SI',
                read_minutes: '4',
                image_url: '',
                excerpt: '',
                body_text: '',
            };
            this.modalType = 'berita';
        },

        openCourse(item = null) {
            this.kurikulum = item || {
                isEdit: false,
                action: @js(route('panel.konten.kurikulum.store')),
                kode: '',
                nama: '',
                tahun: String(new Date().getFullYear()),
                semester: '1',
                sks: '3',
                pilihan: false,
            };
            this.modalType = 'kurikulum';
        },
    };
}
</script>
@endpush
