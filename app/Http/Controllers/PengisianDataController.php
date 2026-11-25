<?php

namespace App\Http\Controllers;

use App\Models\DataForm;
use App\Models\FormSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PengisianDataController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        $activeForms = DataForm::open()
            ->with(['category', 'kolomFormulir'])
            ->orderBy('tanggal_waktu')
            ->get();

        $myCounts = [];
        if ($user) {
            $myCounts = FormSubmission::query()
                ->where('diisi_oleh', $user->id)
                ->selectRaw('id_formulir, COUNT(*) as total')
                ->groupBy('id_formulir')
                ->pluck('total', 'id_formulir')
                ->all();
        }

        $formsPayload = $activeForms->map(function (DataForm $form) use ($user, $myCounts): array {
            $eligible = $user && ($user->isProdusen() || $user->isAdmin()) && $form->canBeSeenBy($user);

            return [
                'id' => $form->id,
                'code' => $form->code,
                'title' => $form->judul,
                'desc' => $form->deskripsi ?? '',
                'kategori' => $form->category?->code ?? '',
                'categoryName' => $form->category?->nama_kategori ?? '',
                'tahun' => $form->tahun,
                'periode' => $form->periode,
                'target' => $form->target,
                'batasiSatuRespon' => (bool) $form->batasi_satu_respon,
                'deadline' => $form->tanggal_waktu?->toIso8601String() ?? '',
                'deadlineFormatted' => $form->deadlineFormatted(),
                'timeLeft' => $form->timeLeftLabel(),
                'urgent' => $form->isUrgent(),
                'fields' => $form->fields ?? [],
                'mine' => (int) ($myCounts[$form->id] ?? 0),
                'eligible' => $eligible,
                'formUrl' => route('formulir.show', $form->code),
                'submitUrl' => route('panel.isi.submit', $form->code),
            ];
        })->values()->all();

        $activeCount = $activeForms->count();
        $categoriesCount = $activeForms->pluck('id_kategori')->unique()->count();
        $mhsCount = $activeForms->where('target', 'Mahasiswa')->count();

        $target = (string) $request->query('target', 'Semua');
        $q = trim((string) $request->query('q', ''));

        return view('pages.pengisian-data', compact(
            'formsPayload',
            'activeCount',
            'categoriesCount',
            'mhsCount',
            'target',
            'q'
        ));
    }
}
