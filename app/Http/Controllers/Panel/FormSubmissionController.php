<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\DataForm;
use App\Models\FormSubmission;
use App\Services\DataQualityService;
use App\Support\CodeGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FormSubmissionController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        $forms = DataForm::query()
            ->with('category')
            ->where('deadline', '>', now())
            ->orderBy('deadline')
            ->get()
            ->filter(fn (DataForm $form): bool => $form->canBeSeenBy($user))
            ->values();

        $myCounts = FormSubmission::query()
            ->where('user_id', $user->id)
            ->selectRaw('data_form_id, COUNT(*) as total')
            ->groupBy('data_form_id')
            ->pluck('total', 'data_form_id')
            ->all();

        return view('panel.isi', compact('forms', 'myCounts'));
    }

    public function store(
        Request $request,
        DataForm $dataForm,
        DataQualityService $quality
    ): JsonResponse|RedirectResponse {
        $user = $request->user();

        if (! $user || ! $user->isProdusen()) {
            $msg = 'Hanya Produsen Data yang dapat mengisi formulir.';
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

        $fields = $dataForm->fields ?? [];
        $values = [];
        $attachments = [];
        $errors = [];

        foreach ($fields as $field) {
            $fid = (string) ($field['id'] ?? '');
            $label = (string) ($field['label'] ?? $fid);
            $type = (string) ($field['type'] ?? 'text');
            $required = (bool) ($field['required'] ?? false);

            if ($type === 'file') {
                $uploadedFile = $request->file("files.{$fid}") ?? $request->file("values.{$fid}");
                if (! $uploadedFile) {
                    if ($required) {
                        $errors[$fid] = 'Lampiran bukti wajib diunggah.';
                    } else {
                        $values[$fid] = '';
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
                if ($storedPath !== false) {
                    $attachments[$fid] = $storedPath;
                }
            } else {
                $rawValue = (string) $request->input("values.{$fid}", '');
                $fieldErr = $quality->fieldError($field, $rawValue);
                if ($fieldErr !== null) {
                    $errors[$fid] = $fieldErr;
                } else {
                    $values[$fid] = $rawValue;
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

        $submission = FormSubmission::create([
            'code' => CodeGenerator::next('S', 'form_submissions'),
            'data_form_id' => $dataForm->id,
            'user_id' => $user->id,
            'values' => $values,
            'attachments' => ! empty($attachments) ? $attachments : null,
            'submitted_at' => now(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Formulir Berhasil Disimpan',
                'submission_code' => $submission->code,
            ], 201);
        }

        return back()->with('success', 'Formulir Berhasil Disimpan');
    }

    public function history(Request $request): View
    {
        $user = Auth::user();
        $own = $user->isProdusen();

        $q = trim((string) $request->query('q', ''));
        $formFilter = (string) $request->query('form', 'all');

        $query = FormSubmission::query()
            ->with(['dataForm', 'user'])
            ->orderByDesc('submitted_at');

        if ($own) {
            $query->where('user_id', $user->id);
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
                    $uq->whereRaw('LOWER(name) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(nim) LIKE ?', [$like]);
                })->orWhereHas('dataForm', function ($fq) use ($like): void {
                    $fq->whereRaw('LOWER(title) LIKE ?', [$like]);
                });
            });
        }

        $submissions = $query->paginate(10)->withQueryString();

        $formsUsedQuery = DataForm::query()
            ->whereHas('submissions', function ($sub) use ($own, $user): void {
                if ($own) {
                    $sub->where('user_id', $user->id);
                }
            })
            ->orderBy('title');

        $formsUsed = $formsUsedQuery->get(['id', 'code', 'title']);

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

        if (! $user->isStaff() && $submission->user_id !== $user->id) {
            abort(403, 'Anda tidak berhak mengunduh lampiran ini.');
        }

        $attachments = $submission->attachments ?? [];
        $path = $attachments[$fieldId] ?? null;

        if (! is_string($path) || $path === '' || ! Storage::disk('local')->exists($path)) {
            abort(404, 'Berkas lampiran tidak ditemukan di server.');
        }

        $originalName = (string) ($submission->values[$fieldId] ?? basename($path));

        return Storage::disk('local')->download($path, $originalName);
    }
}
