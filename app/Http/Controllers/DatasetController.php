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
            ->withCount(['datasets' => fn ($q) => $q->where('status_publikasi', 'terpublikasi')])
            ->orderBy('id')
            ->get()
            ->map(fn (Category $cat): array => [
                'code' => $cat->code,
                'name' => $cat->nama_kategori,
                'desc' => $cat->deskripsi,
                'count' => $cat->datasets_count,
            ])
            ->values()
            ->all();

        $datasetsCollection = Dataset::published()
            ->with(['category', 'sourceForm'])
            ->orderByDesc('tahun')
            ->orderBy('id')
            ->get();

        $requestsQuery = DataRequest::query()->with('dataset');
        $requestsQuery->where(function ($query) use ($user, $requesterToken, $sessionRequestCodes): void {
            if ($user) {
                $query->orWhere('diajukan_oleh', $user->id);
            }
            if ($requesterToken !== '') {
                $query->orWhere('session_token', $requesterToken);
            }
            if (! empty($sessionRequestCodes)) {
                $query->orWhereIn('code', $sessionRequestCodes);
            }
        });

        $requests = ($user || $requesterToken !== '' || ! empty($sessionRequestCodes))
            ? $requestsQuery->orderByDesc('waktu_pengajuan')->get()
            : collect();

        $myRequests = $requests->map(fn (DataRequest $req): array => [
            'id' => $req->code,
            'datasetId' => $req->dataset?->code ?? '',
            'nama' => $req->nama_pemohon,
            'email' => $req->email_pemohon,
            'status' => $req->status,
            'note' => $req->catatan_konfirmasi,
            'at' => $req->waktu_pengajuan?->toIso8601String() ?? '',
        ])->values()->all();

        $allDatasets = $datasetsCollection->map(function (Dataset $d) use ($user, $requesterToken, $sessionRequestCodes): array {
            $accessible = $d->isAccessibleBy($user, $requesterToken, $sessionRequestCodes);

            return [
                'id' => $d->code,
                'tahun' => $d->tahun,
                'periode' => $d->periode,
                'kategori' => $d->category?->code ?? '',
                'access' => $d->tipe_akses,
                'accessible' => $accessible,
                'title' => $d->judul,
                'desc' => $d->deskripsi ?? '',
                'updated' => $d->label_diperbarui ?? ($d->updated_at?->locale('id')->translatedFormat('j M Y') ?? ''),
                'format' => $d->format,
                'cols' => $d->kolom ?? [],
                'rowCount' => count($d->baris ?? []),
                'sourceFormCode' => $d->sourceForm?->code,
                'sourceFormTitle' => $d->sourceForm?->judul,
                'buildMode' => $d->konfigurasi_sumber['mode'] ?? null,
                'preprocessingAt' => $d->sourceForm?->waktu_diproses?->locale('id')->translatedFormat('j M Y'),
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
            'title' => $dataset->judul,
            'desc' => $dataset->deskripsi ?? '',
            'category' => $dataset->category?->nama_kategori ?? '',
            'access' => $dataset->tipe_akses,
            'updated' => $dataset->label_diperbarui ?? ($dataset->updated_at?->locale('id')->translatedFormat('j M Y') ?? ''),
            'format' => $dataset->format,
            'cols' => $dataset->kolom ?? [],
            'rows' => $dataset->baris ?? [],
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
            return $exporter->downloadCsv($dataset->judul, $dataset->kolom ?? [], $dataset->baris ?? []);
        }

        if ($fmt === 'xlsx') {
            return $exporter->downloadXlsx($dataset->judul, $dataset->kolom ?? [], $dataset->baris ?? []);
        }

        abort(404);
    }

    public function storeRequest(Request $request, Dataset $dataset): JsonResponse|RedirectResponse
    {
        if (! $dataset->isPublished()) {
            abort(403, 'Dataset ini masih berstatus draft.');
        }

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
        $code = CodeGenerator::next('R', 'permohonan_data');

        $dataRequest = DataRequest::create([
            'code' => $code,
            'id_dataset' => $dataset->id,
            'diajukan_oleh' => Auth::id(),
            'session_token' => $requesterToken !== '' ? $requesterToken : null,
            'nama_pemohon' => trim($validated['nama']),
            'email_pemohon' => trim($validated['email']),
            'hp_pemohon' => trim($validated['hp']),
            'nim_pemohon' => trim($validated['nim']),
            'tujuan' => trim($validated['tujuan']),
            'alasan' => trim($validated['deskripsi']),
            'status' => 'menunggu',
            'waktu_pengajuan' => now(),
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
                    'nama' => $dataRequest->nama_pemohon,
                    'email' => $dataRequest->email_pemohon,
                    'status' => $dataRequest->status,
                    'note' => $dataRequest->catatan_konfirmasi,
                    'at' => $dataRequest->waktu_pengajuan?->toIso8601String() ?? '',
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
