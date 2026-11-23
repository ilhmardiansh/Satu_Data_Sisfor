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
        $activeForms = DataForm::query()
            ->with('category')
            ->where('deadline', '>', now())
            ->orderBy('deadline')
            ->get();

        $myCounts = [];
        if ($user) {
            $myCounts = FormSubmission::query()
                ->where('user_id', $user->id)
                ->selectRaw('data_form_id, COUNT(*) as total')
                ->groupBy('data_form_id')
                ->pluck('total', 'data_form_id')
                ->all();
        }

        $formsPayload = $activeForms->map(function (DataForm $form) use ($user, $myCounts): array {
            $eligible = $user && $user->isProdusen() && $form->canBeSeenBy($user);

            return [
                'id' => $form->id,
                'code' => $form->code,
                'title' => $form->title,
                'desc' => $form->description ?? '',
                'kategori' => $form->category?->code ?? '',
                'categoryName' => $form->category?->name ?? '',
                'tahun' => $form->tahun,
                'periode' => $form->periode,
                'target' => $form->target,
                'deadline' => $form->deadline?->toIso8601String() ?? '',
                'deadlineFormatted' => $form->deadlineFormatted(),
                'timeLeft' => $form->timeLeftLabel(),
                'urgent' => $form->isUrgent(),
                'fields' => $form->fields ?? [],
                'mine' => (int) ($myCounts[$form->id] ?? 0),
                'eligible' => $eligible,
                'submitUrl' => route('panel.isi.submit', $form->code),
            ];
        })->values()->all();

        $activeCount = $activeForms->count();
        $categoriesCount = $activeForms->pluck('category_id')->unique()->count();
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
