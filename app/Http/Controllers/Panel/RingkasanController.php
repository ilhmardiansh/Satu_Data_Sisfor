<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\DataForm;
use App\Models\DataRequest;
use App\Models\Dataset;
use App\Models\FormSubmission;
use App\Models\Visualization;
use Database\Seeders\SatuDataSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RingkasanController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        if ($user->isProdusen()) {
            $openForms = DataForm::open()
                ->with('kolomFormulir')
                ->orderBy('tanggal_waktu')
                ->get()
                ->filter(fn (DataForm $form): bool => $form->canBeSeenBy($user))
                ->values();

            $mySubmissionsCount = FormSubmission::query()
                ->where('diisi_oleh', $user->id)
                ->count();
            $validMineCount = FormSubmission::query()
                ->where('diisi_oleh', $user->id)
                ->where('status_validasi', 'valid')
                ->count();
            $pendingMineCount = FormSubmission::query()
                ->where('diisi_oleh', $user->id)
                ->where('status_validasi', 'mentah')
                ->count();
            $rejectedMineCount = FormSubmission::query()
                ->where('diisi_oleh', $user->id)
                ->where('status_validasi', 'ditolak')
                ->count();

            return view('panel.ringkasan', [
                'openFormsCount' => $openForms->count(),
                'mySubmissionsCount' => $mySubmissionsCount,
                'validMineCount' => $validMineCount,
                'pendingMineCount' => $pendingMineCount,
                'rejectedMineCount' => $rejectedMineCount,
                'nearestForms' => $openForms->take(4),
            ]);
        }

        $openFormsCount = DataForm::open()->count();
        $totalFormsCount = DataForm::query()->count();
        $submissionsCount = FormSubmission::query()->count();
        $rawSubmissionsCount = FormSubmission::query()->where('status_validasi', 'mentah')->count();
        $validSubmissionsCount = FormSubmission::query()->where('status_validasi', 'valid')->count();
        $rejectedSubmissionsCount = FormSubmission::query()->where('status_validasi', 'ditolak')->count();
        $formsNeedPreprocessingCount = DataForm::query()->where('status_preprocessing', 'proses')->count();
        $formsReadyCount = DataForm::query()->where('status_preprocessing', 'siap')->count();
        $publicDatasetsCount = Dataset::published()->where('tipe_akses', 'public')->count();
        $publishedDatasetsCount = Dataset::published()->count();
        $draftDatasetsCount = Dataset::query()->where('status_publikasi', 'draft')->count();
        $totalDatasetsCount = Dataset::query()->count();
        $visualizationsCount = Visualization::datasetCharts()->count();
        $activeVisualizationsCount = Visualization::datasetCharts()
            ->get()
            ->filter(fn (Visualization $viz): bool => $viz->show)
            ->count();
        $categoriesCount = Category::query()->count();
        $pendingRequestsCount = DataRequest::query()->where('status', 'menunggu')->count();

        $activeSubmissionsTotal = max(0, $submissionsCount - $rejectedSubmissionsCount);
        $qualityHealthScore = $activeSubmissionsTotal > 0
            ? (int) round(($validSubmissionsCount / $activeSubmissionsTotal) * 100)
            : null;

        $activeFormsList = DataForm::query()
            ->with('category')
            ->withCount([
                'submissions',
                'submissions as valid_submissions_count' => fn ($q) => $q->where('status_validasi', 'valid'),
                'submissions as raw_submissions_count' => fn ($q) => $q->where('status_validasi', 'mentah'),
            ])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $categoryDistribution = Category::query()
            ->withCount(['dataForms', 'datasets'])
            ->get()
            ->sort(function (Category $a, Category $b): int {
                $totalDiff = ($b->data_forms_count + $b->datasets_count) <=> ($a->data_forms_count + $a->datasets_count);

                return $totalDiff !== 0 ? $totalDiff : $a->id <=> $b->id;
            })
            ->take(6)
            ->values();

        $recentSubmissions = FormSubmission::query()
            ->with(['dataForm', 'user'])
            ->orderByDesc('waktu_pengisian')
            ->limit(8)
            ->get();

        $pendingRequests = DataRequest::query()
            ->with('dataset')
            ->where('status', 'menunggu')
            ->orderByDesc('waktu_pengajuan')
            ->limit(5)
            ->get();

        return view('panel.ringkasan', compact(
            'openFormsCount',
            'totalFormsCount',
            'submissionsCount',
            'rawSubmissionsCount',
            'validSubmissionsCount',
            'rejectedSubmissionsCount',
            'formsNeedPreprocessingCount',
            'formsReadyCount',
            'publicDatasetsCount',
            'publishedDatasetsCount',
            'draftDatasetsCount',
            'totalDatasetsCount',
            'visualizationsCount',
            'activeVisualizationsCount',
            'categoriesCount',
            'pendingRequestsCount',
            'qualityHealthScore',
            'activeFormsList',
            'categoryDistribution',
            'recentSubmissions',
            'pendingRequests'
        ));
    }

    public function resetDemo(Request $request, SatuDataSeeder $seeder): RedirectResponse
    {
        $seeder->run($request->user()->id);

        return redirect()->route('panel.ringkasan')
            ->with('success', 'Basis data berhasil direset ke kondisi awal bersih tanpa inputan data.');
    }
}
