<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\DataForm;
use App\Models\DataRequest;
use App\Models\Dataset;
use App\Models\FormSubmission;
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
            $openForms = DataForm::query()
                ->where('deadline', '>', now())
                ->orderBy('deadline')
                ->get()
                ->filter(fn (DataForm $form): bool => $form->canBeSeenBy($user))
                ->values();

            $mySubmissionsCount = FormSubmission::query()
                ->where('user_id', $user->id)
                ->count();

            return view('panel.ringkasan', [
                'openFormsCount' => $openForms->count(),
                'mySubmissionsCount' => $mySubmissionsCount,
                'nearestForms' => $openForms->take(4),
            ]);
        }

        $openFormsCount = DataForm::query()->where('deadline', '>', now())->count();
        $submissionsCount = FormSubmission::query()->count();
        $publicDatasetsCount = Dataset::query()->where('access', 'public')->count();
        $totalDatasetsCount = Dataset::query()->count();
        $pendingRequestsCount = DataRequest::query()->where('status', 'menunggu')->count();

        $recentSubmissions = FormSubmission::query()
            ->with(['dataForm', 'user'])
            ->orderByDesc('submitted_at')
            ->limit(5)
            ->get();

        $pendingRequests = DataRequest::query()
            ->with('dataset')
            ->where('status', 'menunggu')
            ->orderByDesc('requested_at')
            ->limit(5)
            ->get();

        return view('panel.ringkasan', compact(
            'openFormsCount',
            'submissionsCount',
            'publicDatasetsCount',
            'totalDatasetsCount',
            'pendingRequestsCount',
            'recentSubmissions',
            'pendingRequests'
        ));
    }

    public function resetDemo(Request $request, SatuDataSeeder $seeder): RedirectResponse
    {
        $seeder->run($request->user()->id);

        return redirect()->route('panel.ringkasan')
            ->with('success', 'Data contoh berhasil dikembalikan ke kondisi awal.');
    }
}
