<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\DataRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DataRequestController extends Controller
{
    public function index(Request $request): View
    {
        $tab = (string) $request->query('tab', 'menunggu');
        if (! in_array($tab, ['menunggu', 'disetujui', 'ditolak', 'all'], true)) {
            $tab = 'menunggu';
        }

        $counts = [
            'menunggu' => DataRequest::query()->where('status', 'menunggu')->count(),
            'disetujui' => DataRequest::query()->where('status', 'disetujui')->count(),
            'ditolak' => DataRequest::query()->where('status', 'ditolak')->count(),
            'all' => DataRequest::query()->count(),
        ];

        $query = DataRequest::query()
            ->with(['dataset', 'decider'])
            ->orderByDesc('waktu_pengajuan');

        if ($tab !== 'all') {
            $query->where('status', $tab);
        }

        $requests = $query->paginate(10)->withQueryString();

        return view('panel.permohonan', compact('requests', 'tab', 'counts'));
    }

    public function decide(Request $request, DataRequest $dataRequest): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:disetujui,ditolak'],
            'note' => ['required_if:status,ditolak', 'nullable', 'string', 'max:1000'],
        ], [
            'note.required_if' => 'Alasan penolakan wajib diisi.',
        ]);

        if ($validated['status'] === 'ditolak' && trim((string) ($validated['note'] ?? '')) === '') {
            return back()->withErrors(['note' => 'Alasan penolakan wajib diisi.'])
                ->with('decide_request_code', $dataRequest->code);
        }

        $note = trim((string) ($validated['note'] ?? ''));

        $dataRequest->konfirmasiPermohonan(
            $validated['status'],
            $note !== '' ? $note : null,
            $request->user()->id
        );

        $message = $validated['status'] === 'disetujui'
            ? "Permohonan {$dataRequest->code} disetujui. Pemohon kini dapat mengunduh dataset."
            : "Permohonan {$dataRequest->code} ditolak.";

        return redirect()->route('panel.permohonan', ['tab' => $request->input('tab', 'menunggu')])
            ->with($validated['status'] === 'disetujui' ? 'success' : 'info', $message);
    }
}
