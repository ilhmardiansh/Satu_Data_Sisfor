<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Achievement;
use App\Models\Course;
use App\Models\Lecturer;
use App\Models\NewsArticle;
use App\Models\ProgramProfile;
use App\Support\CodeGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContentController extends Controller
{
    public function index(Request $request): View
    {
        $tab = (string) $request->query('tab', 'profil');
        if (! in_array($tab, ['profil', 'dosen', 'prestasi', 'berita', 'kurikulum'], true)) {
            $tab = 'profil';
        }

        $profil = ProgramProfile::query()->first();
        $lecturers = Lecturer::query()->orderBy('sort_order')->orderBy('id')->get();
        $achievements = Achievement::query()->orderByDesc('tahun')->orderBy('id')->get();
        $news = NewsArticle::query()->orderByDesc('created_at')->orderBy('id')->get();
        $courses = Course::query()->orderByDesc('tahun')->orderBy('semester')->orderBy('kode')->get();

        $newsTags = NewsArticle::TAGS;
        $jabatanOptions = Lecturer::JABATAN_OPTIONS;
        $pendidikanOptions = Lecturer::PENDIDIKAN_OPTIONS;
        $tingkatOptions = Achievement::TINGKAT_OPTIONS;

        return view('panel.konten', compact(
            'tab',
            'profil',
            'lecturers',
            'achievements',
            'news',
            'courses',
            'newsTags',
            'jabatanOptions',
            'pendidikanOptions',
            'tingkatOptions'
        ));
    }

    public function updateProfil(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'deskripsi' => ['required', 'string'],
            'visi' => ['required', 'string'],
            'misi_text' => ['required', 'string'],
            'akreditasi' => ['required', 'string', 'max:100'],
            'berdiri' => ['nullable', 'string', 'max:20'],
            'alamat' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:150'],
            'telp' => ['nullable', 'string', 'max:50'],
            'sejarah' => ['nullable', 'string'],
            'program' => ['nullable', 'string', 'max:150'],
            'gelar' => ['nullable', 'string', 'max:150'],
            'durasi' => ['nullable', 'string', 'max:150'],
            'syarat' => ['nullable', 'string'],
            'fakultas' => ['nullable', 'string', 'max:200'],
            'fasilitas' => ['nullable', 'string'],
        ], [
            'deskripsi.required' => 'Lengkapi kolom wajib pada profil.',
            'visi.required' => 'Lengkapi kolom wajib pada profil.',
            'misi_text.required' => 'Lengkapi kolom wajib pada profil.',
            'akreditasi.required' => 'Lengkapi kolom wajib pada profil.',
            'email.required' => 'Lengkapi kolom wajib pada profil.',
            'email.email' => 'Format email profil tidak valid.',
        ]);

        $misi = array_values(array_filter(
            array_map('trim', preg_split('/\r?\n/', $validated['misi_text']) ?: [])
        ));

        $payload = [
            'deskripsi' => trim($validated['deskripsi']),
            'visi' => trim($validated['visi']),
            'misi' => $misi,
            'akreditasi' => trim($validated['akreditasi']),
            'berdiri' => trim((string) ($validated['berdiri'] ?? '')) ?: null,
            'alamat' => trim((string) ($validated['alamat'] ?? '')) ?: null,
            'email' => trim($validated['email']),
            'telp' => trim((string) ($validated['telp'] ?? '')) ?: null,
            'sejarah' => trim((string) ($validated['sejarah'] ?? '')) ?: null,
            'program' => trim((string) ($validated['program'] ?? '')) ?: null,
            'gelar' => trim((string) ($validated['gelar'] ?? '')) ?: null,
            'durasi' => trim((string) ($validated['durasi'] ?? '')) ?: null,
            'syarat' => trim((string) ($validated['syarat'] ?? '')) ?: null,
            'fakultas' => trim((string) ($validated['fakultas'] ?? '')) ?: null,
            'fasilitas' => trim((string) ($validated['fasilitas'] ?? '')) ?: null,
        ];

        $profil = ProgramProfile::query()->first();
        if ($profil) {
            $profil->update($payload);
        } else {
            ProgramProfile::create($payload);
        }

        return redirect()->route('panel.konten', ['tab' => 'profil'])
            ->with('success', 'Profil prodi diperbarui di landing page.');
    }

    public function storeLecturer(Request $request): RedirectResponse
    {
        $data = $this->validateLecturer($request);
        $maxOrder = (int) Lecturer::query()->max('sort_order');

        Lecturer::create([
            'code' => CodeGenerator::next('D', 'lecturers'),
            ...$data,
            'sort_order' => $maxOrder + 1,
        ]);

        return redirect()->route('panel.konten', ['tab' => 'dosen'])
            ->with('success', 'Data dosen disimpan.');
    }

    public function updateLecturer(Request $request, Lecturer $lecturer): RedirectResponse
    {
        $lecturer->update($this->validateLecturer($request));

        return redirect()->route('panel.konten', ['tab' => 'dosen'])
            ->with('success', 'Data dosen disimpan.');
    }

    public function destroyLecturer(Lecturer $lecturer): RedirectResponse
    {
        $lecturer->delete();

        return redirect()->route('panel.konten', ['tab' => 'dosen'])
            ->with('success', 'Dosen dihapus.');
    }

    public function storeAchievement(Request $request): RedirectResponse
    {
        $data = $this->validateAchievement($request);

        Achievement::create([
            'code' => CodeGenerator::next('P', 'achievements'),
            ...$data,
        ]);

        return redirect()->route('panel.konten', ['tab' => 'prestasi'])
            ->with('success', 'Prestasi disimpan.');
    }

    public function updateAchievement(Request $request, Achievement $achievement): RedirectResponse
    {
        $achievement->update($this->validateAchievement($request));

        return redirect()->route('panel.konten', ['tab' => 'prestasi'])
            ->with('success', 'Prestasi disimpan.');
    }

    public function destroyAchievement(Achievement $achievement): RedirectResponse
    {
        $achievement->delete();

        return redirect()->route('panel.konten', ['tab' => 'prestasi'])
            ->with('success', 'Prestasi dihapus.');
    }

    public function storeNews(Request $request): RedirectResponse
    {
        $data = $this->validateNews($request);

        NewsArticle::create([
            'code' => CodeGenerator::next('B', 'news_articles'),
            ...$data,
        ]);

        return redirect()->route('panel.konten', ['tab' => 'berita'])
            ->with('success', 'Berita disimpan dan tampil di Beranda & Berita.');
    }

    public function updateNews(Request $request, NewsArticle $newsArticle): RedirectResponse
    {
        $newsArticle->update($this->validateNews($request));

        return redirect()->route('panel.konten', ['tab' => 'berita'])
            ->with('success', 'Berita disimpan dan tampil di Beranda & Berita.');
    }

    public function destroyNews(NewsArticle $newsArticle): RedirectResponse
    {
        $newsArticle->delete();

        return redirect()->route('panel.konten', ['tab' => 'berita'])
            ->with('success', 'Konten dihapus.');
    }

    public function storeCourse(Request $request): RedirectResponse
    {
        $data = $this->validateCourse($request);

        Course::create([
            'code' => CodeGenerator::next('MK', 'courses'),
            ...$data,
        ]);

        return redirect()->route('panel.konten', ['tab' => 'kurikulum'])
            ->with('success', 'Mata kuliah disimpan.');
    }

    public function updateCourse(Request $request, Course $course): RedirectResponse
    {
        $course->update($this->validateCourse($request, $course));

        return redirect()->route('panel.konten', ['tab' => 'kurikulum'])
            ->with('success', 'Mata kuliah disimpan.');
    }

    public function destroyCourse(Course $course): RedirectResponse
    {
        $course->delete();

        return redirect()->route('panel.konten', ['tab' => 'kurikulum'])
            ->with('success', 'Konten dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateLecturer(Request $request): array
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:200'],
            'nidn' => ['required', 'digits:10'],
            'pendidikan' => ['required', Rule::in(Lecturer::PENDIDIKAN_OPTIONS)],
            'jabatan' => ['required', Rule::in(Lecturer::JABATAN_OPTIONS)],
            'bidang' => ['required', 'string', 'max:150'],
            'foto' => ['nullable', 'regex:/^https:\/\/\S+$/i'],
            'email' => ['nullable', 'email', 'max:150'],
            'mata_kuliah' => ['nullable', 'string', 'max:500'],
            'profil' => ['nullable', 'string', 'max:2000'],
        ], [
            'nidn.digits' => 'NIDN terdiri dari 10 digit.',
            'foto.regex' => 'Gunakan URL gambar HTTPS.',
            'email.email' => 'Format email tidak valid.',
        ]);

        return [
            'nama' => trim($validated['nama']),
            'nidn' => trim($validated['nidn']),
            'pendidikan' => $validated['pendidikan'],
            'jabatan' => $validated['jabatan'],
            'bidang' => trim($validated['bidang']),
            'foto' => trim((string) ($validated['foto'] ?? '')) ?: null,
            'email' => trim((string) ($validated['email'] ?? '')) ?: null,
            'mata_kuliah' => trim((string) ($validated['mata_kuliah'] ?? '')) ?: null,
            'profil' => trim((string) ($validated['profil'] ?? '')) ?: null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateAchievement(Request $request): array
    {
        $validated = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'nama' => ['required', 'string', 'max:200'],
            'tingkat' => ['required', Rule::in(Achievement::TINGKAT_OPTIONS)],
            'peringkat' => ['required', 'string', 'max:100'],
            'tahun' => ['required', 'digits:4'],
            'foto' => ['nullable', 'regex:/^https:\/\/\S+$/i'],
        ], [
            'tahun.digits' => 'Tahun 4 digit.',
            'foto.regex' => 'Gunakan URL gambar HTTPS.',
        ]);

        return [
            'judul' => trim($validated['judul']),
            'nama' => trim($validated['nama']),
            'tingkat' => $validated['tingkat'],
            'peringkat' => trim($validated['peringkat']),
            'tahun' => (int) $validated['tahun'],
            'foto' => trim((string) ($validated['foto'] ?? '')) ?: null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateNews(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'badge' => ['required', Rule::in(NewsArticle::TAGS)],
            'corner' => ['nullable', 'string', 'max:60'],
            'date_label' => ['required', 'string', 'max:60'],
            'author' => ['required', 'string', 'max:150'],
            'read_minutes' => ['required', 'integer', 'min:1'],
            'image_url' => ['required', 'regex:/^https:\/\/\S+$/i'],
            'excerpt' => ['required', 'string', 'max:600'],
            'body_text' => ['required', 'string'],
        ], [
            'read_minutes.min' => 'Masukkan menit bulat positif.',
            'image_url.regex' => 'Gunakan URL gambar HTTPS.',
        ]);

        $author = trim($validated['author']);
        $paragraphs = array_values(array_filter(
            array_map('trim', preg_split('/\r?\n\s*\r?\n/', $validated['body_text']) ?: [])
        ));

        return [
            'title' => trim($validated['title']),
            'badge' => $validated['badge'],
            'corner' => trim((string) ($validated['corner'] ?? '')) ?: null,
            'date_label' => trim($validated['date_label']),
            'author' => $author,
            'author_initials' => NewsArticle::makeInitials($author),
            'read_minutes' => (int) $validated['read_minutes'],
            'image_url' => trim($validated['image_url']),
            'excerpt' => trim($validated['excerpt']),
            'body' => $paragraphs,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateCourse(Request $request, ?Course $ignore = null): array
    {
        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:30'],
            'nama' => ['required', 'string', 'max:200'],
            'tahun' => ['required', 'digits:4'],
            'semester' => ['required', 'integer', 'between:1,8'],
            'sks' => ['required', 'integer', 'between:1,12'],
            'pilihan' => ['nullable', 'boolean'],
        ], [
            'tahun.digits' => 'Tahun harus 4 digit.',
            'semester.between' => 'Semester harus 1–8.',
            'sks.between' => 'SKS harus bilangan bulat 1–12.',
        ]);

        $kode = trim($validated['kode']);
        $tahun = (int) $validated['tahun'];

        $duplicate = Course::query()
            ->where('tahun', $tahun)
            ->whereRaw('LOWER(kode) = ?', [strtolower($kode)])
            ->when($ignore, fn ($q) => $q->where('id', '!=', $ignore->id))
            ->exists();

        if ($duplicate) {
            return back()->withInput()->withErrors([
                'kode' => 'Kode mata kuliah sudah ada pada tahun kurikulum ini.',
            ])->throwResponse();
        }

        return [
            'kode' => $kode,
            'nama' => trim($validated['nama']),
            'tahun' => $tahun,
            'semester' => (int) $validated['semester'],
            'sks' => (int) $validated['sks'],
            'pilihan' => $request->boolean('pilihan'),
        ];
    }
}
