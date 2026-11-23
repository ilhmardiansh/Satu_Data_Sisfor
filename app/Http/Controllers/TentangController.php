<?php

namespace App\Http\Controllers;

use App\Services\SatuDataService;
use Illuminate\Http\Request;

class TentangController extends Controller
{
    public function index()
    {
        $profil = SatuDataService::getProfil();
        $dosen = SatuDataService::getDosen();
        $prestasi = SatuDataService::getPrestasi();
        $kurikulum = SatuDataService::getKurikulum();

        return view('pages.tentang-kami', compact('profil', 'dosen', 'prestasi', 'kurikulum'));
    }
}
