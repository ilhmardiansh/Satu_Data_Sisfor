<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureRequesterToken;
use App\Models\Category;
use App\Models\DataRequest;
use App\Models\Dataset;
use App\Services\SpreadsheetExportService;
use App\Support\CodeGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class DatasetController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        $requesterToken = $this->requesterToken($request);
        $sessionRequestCodes = (array) $request->session()->get('my_request_codes', []);

        $categories = Category::query()
            ->withCount('datasets')
            ->orderBy('id')
            ->get()
            ->map(fn (Category $cat): array => [
                'code' => $cat->code,
                'name' => $cat->name,
                'desc' => $cat->description,
                'count' => $cat->datasets_count,
            ])
            ->values()
            ->all();

        $datasetsCollection = Dataset::query()
            ->with('category')
            ->orderByDesc('tahun')
            ->orderBy('id')
            ->get();

        $requestsQuery = DataRequest::query()->with('dataset');
        $requestsQuery->where(function ($query) use ($user, $requesterToken, $sessionRequestCodes): void {
            if ($user) {
                $query->orWhere('user_id', $user->id);
            }
            if ($requesterToken !== '') {
                $query->orWhere('session_token', $requesterToken);
            }
            if (! empty($sessionRequestCodes)) {
                $query->orWhereIn('code', $sessionRequestCodes);
            }
        });

        $requests = ($user || $requesterToken !== '' || ! empty($sessionRequestCodes))
            ? $requestsQuery->orderByDesc('requested_at')->get()
            : collect();

        $myRequests = $requests->map(fn (DataRequest $req): array => [
            'id' => $req->code,
            'datasetId' => $req->dataset?->code ?? '',
            'nama' => $req->nama,
            'email' => $req->email,
            'status' => $req->status,
            'note' => $req->note,
            'at' => $req->requested_at?->toIso8601String() ?? '',
        ])->values()->all();

        $allDatasets = $datasetsCollection->map(function (Dataset $d) use ($user, $requesterToken, $sessionRequestCodes): array {
            $accessible = $d->isAccessibleBy($user, $requesterToken, $sessionRequestCodes);

            return [
                'id' => $d->code,
                'tahun' => $d->tahun,
                'periode' => $d->periode,
                'kategori' => $d->category?->code ?? '',
                'access' => $d->access,
                'accessible' => $accessible,
                'title' => $d->title,
                'desc' => $d->description ?? '',
                'updated' => $d->updated_label ?? ($d->updated_at?->locale('id')->translatedFormat('j M Y') ?? ''),
                'format' => $d->format,
                'cols' => $d->cols ?? [],
                'rowCount' => count($d->rows ?? []),
                'previewUrl' => route('dataset.preview', $d->code),
                'csvUrl' => route('dataset.download', [$d->code, 'csv']),
                'xlsxUrl' => route('dataset.download', [$d->code, 'xlsx']),
                'requestUrl' => route('dataset.request', $d->code),
            ];
        })->values()->all();

        $years = $datasetsCollection->pluck('tahun')->unique()->sortDesc()->values()->all();

        $kategori = (string) $request->query('kategori', 'all');
        $access = (string) $request->query('access', 'all');
        $tahun = (string) $request->query('tahun', 'all');
        $periode = (string) $request->query('periode', 'all');
        $format = (string) $request->query('format', 'all');
        $group = (string) $request->query('group', 'kategori');
        $q = trim((string) $request->query('q', ''));
        $purposes = DataRequest::PURPOSES;

        return view('pages.dataset', compact(
            'allDatasets',
            'categories',
            'years',
            'myRequests',
            'kategori',
            'access',
            'tahun',
            'periode',
            'format',
            'group',
            'q',
            'purposes'
        ));
    }

    public function preview(Request $request, Dataset $dataset): JsonResponse
    {
        $user = $request->user();
        $requesterToken = $this->requesterToken($request);
        $sessionRequestCodes = (array) $request->session()->get('my_request_codes', []);

        if (! $dataset->isAccessibleBy($user, $requesterToken, $sessionRequestCodes)) {
            return response()->json([
                'message' => 'Dataset private hanya dapat diakses setelah permohonan disetujui Wali Data.',
            ], 403);
        }

        $dataset->loadMissing('category');

        return response()->json([
            'id' => $dataset->code,
            'title' => $dataset->title,
            'desc' => $dataset->description ?? '',
            'category' => $dataset->category?->name ?? '',
            'access' => $dataset->access,
            'updated' => $dataset->updated_label ?? ($dataset->updated_at?->locale('id')->translatedFormat('j M Y') ?? ''),
            'format' => $dataset->format,
            'cols' => $dataset->cols ?? [],
            'rows' => $dataset->rows ?? [],
            'csvUrl' => route('dataset.download', [$dataset->code, 'csv']),
            'xlsxUrl' => route('dataset.download', [$dataset->code, 'xlsx']),
        ]);
    }

    public function download(
        Request $request,
        Dataset $dataset,
        string $format,
        SpreadsheetExportService $exporter
    ): Response {
        $user = $request->user();
        $requesterToken = $this->requesterToken($request);
        $sessionRequestCodes = (array) $request->session()->get('my_request_codes', []);

        if (! $dataset->isAccessibleBy($user, $requesterToken, $sessionRequestCodes)) {
            abort(403, 'Anda tidak memiliki akses untuk mengunduh dataset ini.');
        }

        $fmt = strtolower($format);
        if ($fmt === 'csv') {
            return $exporter->downloadCsv($dataset->title, $dataset->cols ?? [], $dataset->rows ?? []);
        }

        if ($fmt === 'xlsx') {
            return $exporter->downloadXlsx($dataset->title, $dataset->cols ?? [], $dataset->rows ?? []);
        }

        abort(404);
    }

    public function storeRequest(Request $request, Dataset $dataset): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150'],
            'hp' => ['required', 'regex:/^0\d{9,13}$/'],
            'nim' => ['required', 'string', 'max:50'],
            'tujuan' => ['required', 'string', 'max:150'],
            'deskripsi' => ['required', 'string', 'max:2000'],
        ], [
            'nama.required' => 'Kolom ini wajib diisi.',
            'email.required' => 'Kolom ini wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'hp.required' => 'Kolom ini wajib diisi.',
            'hp.regex' => 'Nomor HP diawali 0, 10–14 digit.',
            'nim.required' => 'Kolom ini wajib diisi.',
            'tujuan.required' => 'Kolom ini wajib diisi.',
            'deskripsi.required' => 'Kolom ini wajib diisi.',
        ]);

        $requesterToken = $this->requesterToken($request);
        $code = CodeGenerator::next('R', 'data_requests');

        $dataRequest = DataRequest::create([
            'code' => $code,
            'dataset_id' => $dataset->id,
            'user_id' => Auth::id(),
            'session_token' => $requesterToken !== '' ? $requesterToken : null,
            'nama' => trim($validated['nama']),
            'email' => trim($validated['email']),
            'hp' => trim($validated['hp']),
            'nim' => trim($validated['nim']),
            'tujuan' => trim($validated['tujuan']),
            'deskripsi' => trim($validated['deskripsi']),
            'status' => 'menunggu',
            'requested_at' => now(),
        ]);

        $codes = (array) $request->session()->get('my_request_codes', []);
        $codes[] = $code;
        $request->session()->put('my_request_codes', array_values(array_unique($codes)));

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Permohonan berhasil dikirim.',
                'request' => [
                    'id' => $dataRequest->code,
                    'datasetId' => $dataset->code,
                    'nama' => $dataRequest->nama,
                    'email' => $dataRequest->email,
                    'status' => $dataRequest->status,
                    'note' => $dataRequest->note,
                    'at' => $dataRequest->requested_at?->toIso8601String() ?? '',
                ],
            ], 201);
        }

        return back()->with('success', "Permohonan {$code} berhasil dikirim.");
    }

    protected function requesterToken(Request $request): string
    {
        return (string) (
            $request->attributes->get('requester_token')
            ?? $request->cookie(EnsureRequesterToken::COOKIE_NAME, '')
        );
    }
}
