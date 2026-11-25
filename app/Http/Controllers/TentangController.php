<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\Lecturer;
use App\Models\ProgramProfile;
use Illuminate\View\View;

class TentangController extends Controller
{
    public function index(): View
    {
        $profil = ProgramProfile::query()->first();
        $dosen = Lecturer::query()
            ->orderBy('urutan')
            ->orderBy('id')
            ->get();
        $prestasiCount = Achievement::query()->count();

        return view('pages.tentang-kami', compact('profil', 'dosen', 'prestasiCount'));
    }
}
