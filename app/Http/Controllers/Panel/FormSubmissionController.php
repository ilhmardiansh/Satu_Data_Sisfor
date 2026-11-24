<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\DataForm;
use App\Models\FormSubmission;
use App\Services\DataPreprocessingService;
use App\Services\DataQualityService;
use App\Support\CodeGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FormSubmissionController extends Controller
{
    public function showPublicForm(DataForm $dataForm): View
    {
        $dataForm->loadMissing(['category', 'kolomFormulir']);
        $user = Auth::user();
        $alreadySubmittedCount = $user
            ? FormSubmission::query()
                ->where('id_formulir', $dataForm->id)
                ->where('diisi_oleh', $user->id)
                ->count()
            : 0;

        $closedReason = null;
        $canSubmit = false;

        if (! $user) {
            $closedReason = 'Masuk terlebih dahulu dengan akun Produsen Data atau Admin untuk mengisi formulir ini.';
        } elseif (! ($user->isProdusen() || $user->isAdmin())) {
            $closedReason = 'Wali Data tidak mengisi instrumen secara langsung. Gunakan menu Pre-processing Data untuk meninjau respons.';
        } elseif (! $dataForm->canBeSeenBy($user)) {
            $closedReason = "Formulir ini khusus ditujukan untuk responden kategori {$dataForm->target}.";
        } elseif (! $dataForm->isOpen()) {
            $closedReason = 'Formulir telah ditutup atau melewati tenggat waktu pengumpulan.';
        } elseif ($dataForm->batasi_satu_respon && $alreadySubmittedCount > 0) {
            $closedReason = 'Anda sudah mengirimkan respons pada formulir ini (dibatasi 1 respons per akun).';
        } else {
            $canSubmit = true;
        }

        return view('pages.formulir-isi', compact(
            'dataForm',
            'alreadySubmittedCount',
            'canSubmit',
            'closedReason'
        ));
    }

    public function index(): View
    {
        $user = Auth::user();

        $forms = DataForm::open()
            ->with(['category', 'kolomFormulir'])
            ->orderBy('tanggal_waktu')
            ->get()
            ->filter(fn (DataForm $form): bool => $form->canBeSeenBy($user))
            ->values();

        $myCounts = FormSubmission::query()
            ->where('diisi_oleh', $user->id)
            ->selectRaw('id_formulir, COUNT(*) as total')
            ->groupBy('id_formulir')
            ->pluck('total', 'id_formulir')
            ->all();

        return view('panel.isi', compact('forms', 'myCounts'));
    }

    public function store(
        Request $request,
        DataForm $dataForm,
        DataQualityService $quality,
        DataPreprocessingService $preprocessing
    ): JsonResponse|RedirectResponse {
        $user = $request->user();

        if (! $user || (! $user->isProdusen() && ! $user->isAdmin())) {
            $msg = 'Hanya Produsen Data atau Admin yang dapat mengisi formulir.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $msg], 403);
            }

            return back()->with('error', $msg);
        }

        if (! $dataForm->canBeSeenBy($user)) {
            $msg = 'Formulir ini tidak ditujukan untuk Anda.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $msg], 403);
            }

            return back()->with('error', $msg);
        }

        if (! $dataForm->isOpen()) {
            $msg = 'Tenggat waktu formulir telah berakhir. Pengiriman ditolak.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $msg], 422);
            }

            return back()->with('error', $msg);
        }

        if ($dataForm->batasi_satu_respon && $dataForm->submissions()->where('diisi_oleh', $user->id)->exists()) {
            $msg = 'Formulir ini dibatasi 1 respons per akun dan Anda sudah mengisinya.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $msg], 422);
            }

            return back()->with('error', $msg);
        }

        $dataForm->loadMissing('kolomFormulir');
        $fields = $dataForm->fields ?? [];
        $values = [];
        $cleanStatusMap = [];
        $attachments = [];
        $errors = [];

        foreach ($fields as $field) {
            $fid = (string) ($field['id'] ?? '');
            $type = (string) ($field['type'] ?? 'text');
            $required = (bool) ($field['required'] ?? false);

            if ($type === 'file') {
                $uploadedFile = $request->file("files.{$fid}") ?? $request->file("values.{$fid}");
                if (! $uploadedFile) {
                    if ($required) {
                        $errors[$fid] = 'Lampiran bukti wajib diunggah.';
                    } else {
                        $values[$fid] = '';
                        $cleanStatusMap[$fid] = true;
                    }
                    continue;
                }

                $fileValidator = Validator::make(
                    ['file' => $uploadedFile],
                    ['file' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:5120']],
                    [
                        'file.mimes' => 'Format berkas harus PDF, JPG, atau PNG.',
                        'file.max' => 'Ukuran berkas maksimal 5 MB.',
                    ]
                );

                if ($fileValidator->fails()) {
                    $errors[$fid] = $fileValidator->errors()->first('file');
                    continue;
                }

                $storedPath = $uploadedFile->store("submissions/{$dataForm->code}", 'local');
                $values[$fid] = $uploadedFile->getClientOriginalName();
                $cleanStatusMap[$fid] = true;
                if ($storedPath !== false) {
                    $attachments[$fid] = $storedPath;
                }
            } else {
                $rawInput = $request->input("values.{$fid}", '');
                if ($type === 'checkbox' && is_array($rawInput)) {
                    $rawValue = implode(', ', array_values(array_filter(array_map(
                        static fn ($item): string => trim((string) $item),
                        $rawInput
                    ), static fn (string $s): bool => $s !== '')));
                } else {
                    $rawValue = is_array($rawInput) ? implode(', ', $rawInput) : (string) $rawInput;
                }

                $fieldErr = $quality->fieldError($field, $rawValue);
                if ($fieldErr !== null) {
                    $errors[$fid] = $fieldErr;
                } else {
                    $values[$fid] = $rawValue;
                    $cleanStatusMap[$fid] = $rawValue === '' || $quality->normalizeValue($field, $rawValue) === $rawValue;
                }
            }
        }

        if (! empty($errors)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Periksa kembali isian formulir Anda.',
                    'errors' => $errors,
                ], 422);
            }

            return back()
                ->withInput()
                ->withErrors($errors)
                ->with('open_form_code', $dataForm->code);
        }

        $submission = DB::transaction(function () use ($dataForm, $user, $values, $attachments, $cleanStatusMap, $preprocessing): FormSubmission {
            $firstAttachment = ! empty($attachments) ? reset($attachments) : null;

            $sub = FormSubmission::create([
                'code' => CodeGenerator::next('S', 'pengisian_formulir'),
                'id_formulir' => $dataForm->id,
                'diisi_oleh' => $user->id,
                'waktu_pengisian' => now(),
                'status_validasi' => 'mentah',
                'sumber_input' => 'formulir',
                'jalur_berkas_bukti' => is_string($firstAttachment) ? $firstAttachment : null,
            ]);

            $sub->syncValues($dataForm, $values, ! empty($attachments) ? $attachments : null, $cleanStatusMap, false);
            $preprocessing->markFormNeedsPreprocessing($dataForm);

            return $sub;
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Formulir Berhasil Disimpan',
                'submission_code' => $submission->code,
            ], 201);
        }

        return back()->with('success', "Formulir Berhasil Disimpan ({$submission->code}). Data masuk ke antrean Pre-processing Wali Data.");
    }

    public function history(Request $request): View
    {
        $user = Auth::user();
        $own = $user->isProdusen();

        $q = trim((string) $request->query('q', ''));
        $formFilter = (string) $request->query('form', 'all');

        $query = FormSubmission::query()
            ->with(['dataForm.kolomFormulir', 'user', 'dataIsian.kolomFormulir'])
            ->orderByDesc('waktu_pengisian');

        if ($own) {
            $query->where('diisi_oleh', $user->id);
        }

        if ($formFilter !== '' && $formFilter !== 'all') {
            $query->whereHas('dataForm', function ($sub) use ($formFilter): void {
                $sub->where('code', $formFilter);
            });
        }

        if ($q !== '') {
            $like = '%'.strtolower($q).'%';
            $query->where(function ($sub) use ($like): void {
                $sub->whereHas('user', function ($uq) use ($like): void {
                    $uq->whereRaw('LOWER(nama_pengguna) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(nim) LIKE ?', [$like]);
                })->orWhereHas('dataForm', function ($fq) use ($like): void {
                    $fq->whereRaw('LOWER(judul) LIKE ?', [$like]);
                })->orWhereHas('dataIsian', function ($dq) use ($like): void {
                    $dq->whereRaw('LOWER(nilai) LIKE ?', [$like]);
                });
            });
        }

        $submissions = $query->paginate(10)->withQueryString();

        $formsUsed = DataForm::query()
            ->whereHas('submissions', function ($sub) use ($own, $user): void {
                if ($own) {
                    $sub->where('diisi_oleh', $user->id);
                }
            })
            ->orderBy('judul')
            ->get(['id', 'code', 'judul']);

        return view('panel.riwayat', compact(
            'submissions',
            'formsUsed',
            'own',
            'q',
            'formFilter'
        ));
    }

    public function downloadFile(Request $request, FormSubmission $submission, string $fieldId): StreamedResponse
    {
        $user = $request->user();

        if (! $user->isStaff() && (int) $submission->diisi_oleh !== (int) $user->id) {
            abort(403, 'Anda tidak berhak mengunduh lampiran ini.');
        }

        $submission->loadMissing('dataIsian.kolomFormulir');
        $attachments = $submission->attachments ?? [];
        $path = $attachments[$fieldId] ?? null;

        if (! is_string($path) || $path === '' || ! Storage::disk('local')->exists($path)) {
            abort(404, 'Berkas lampiran tidak ditemukan di server.');
        }

        $originalName = (string) ($submission->values[$fieldId] ?? basename($path));

        return Storage::disk('local')->download($path, $originalName);
    }
}
