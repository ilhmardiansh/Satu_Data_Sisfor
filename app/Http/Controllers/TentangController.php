<?php

namespace App\Http\Controllers;

use App\Models\ProgramProfile;
use Illuminate\View\View;

class TentangController extends Controller
{
    public function index(): View
    {
        $profil = ProgramProfile::query()->first();

        return view('pages.tentang-kami', compact('profil'));
    }
}
