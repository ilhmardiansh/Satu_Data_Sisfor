<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\DataForm;
use App\Models\FormField;
use App\Models\FormSubmission;
use App\Services\DataPreprocessingService;
use App\Services\DataQualityService;
use App\Support\CodeGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class FormSubmissionController extends Controller
{
    public function showPublicForm(DataForm $dataForm): View
    {
        $dataForm->loadMissing(['category', 'kolomFormulir']);
        $user = Auth::user();
        $settings = $dataForm->settings;

        $alreadySubmittedCount = $user
            ? FormSubmission::query()
                ->where('id_formulir', $dataForm->id)
                ->where('diisi_oleh', $user->id)
                ->count()
            : 0;

        $editingSubmission = null;
        if ($user && $alreadySubmittedCount > 0 && ($settings['allow_edit_after_submit'] ?? false)) {
            $editingSubmission = FormSubmission::query()
                ->with('dataIsian.kolomFormulir')
                ->where('id_formulir', $dataForm->id)
                ->where('diisi_oleh', $user->id)
                ->where('status_validasi', 'mentah')
                ->latest('waktu_pengisian')
                ->first();
        }

        $closedReason = null;
        $canSubmit = false;

        if (! $user) {
            $closedReason = 'Masuk terlebih dahulu dengan akun Produsen Data atau Admin untuk mengisi formulir ini.';
        } elseif (! ($user->isProdusen() || $user->isAdmin())) {
            $closedReason = 'Wali Data tidak mengisi instrumen secara langsung. Gunakan menu Pre-processing Data untuk meninjau respons.';
        } elseif (! $dataForm->canBeSeenBy($user)) {
            $closedReason = "Formulir ini khusus ditujukan untuk responden kategori {$dataForm->target}.";
        } elseif (! $dataForm->isOpen()) {
            $customClosed = trim((string) ($settings['closed_message'] ?? ''));
            $closedReason = $customClosed !== ''
                ? $customClosed
                : 'Formulir telah ditutup atau melewati tenggat waktu pengumpulan.';
        } elseif ($dataForm->batasi_satu_respon && $alreadySubmittedCount > 0 && ! $editingSubmission) {
            $closedReason = 'Anda sudah mengirimkan respons pada formulir ini (dibatasi 1 respons per akun).';
        } else {
            $canSubmit = true;
        }

        $items = $dataForm->items ?? [];

        return view('pages.formulir-isi', compact(
            'dataForm',
            'items',
            'settings',
            'alreadySubmittedCount',
            'editingSubmission',
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
        $settings = $dataForm->settings;

        if (! $user || (! $user->isProdusen() && ! $user->isAdmin())) {
            $msg = 'Hanya Produsen Data atau Admin yang dapat mengisi formulir.';

            return $request->expectsJson()
                ? response()->json(['message' => $msg], 403)
                : back()->with('error', $msg);
        }

        if (! $dataForm->canBeSeenBy($user)) {
            $msg = 'Formulir ini tidak ditujukan untuk Anda.';

            return $request->expectsJson()
                ? response()->json(['message' => $msg], 403)
                : back()->with('error', $msg);
        }

        if (! $dataForm->isOpen()) {
            $customClosed = trim((string) ($settings['closed_message'] ?? ''));
            $msg = $customClosed !== '' ? $customClosed : 'Tenggat waktu formulir telah berakhir. Pengiriman ditolak.';

            return $request->expectsJson()
                ? response()->json(['message' => $msg], 422)
                : back()->with('error', $msg);
        }

        $editingCode = trim((string) $request->input('editing_submission_code', ''));
        $editingSubmission = null;
        if ($editingCode !== '' && ($settings['allow_edit_after_submit'] ?? false)) {
            $editingSubmission = FormSubmission::query()
                ->where('code', $editingCode)
                ->where('id_formulir', $dataForm->id)
                ->where('diisi_oleh', $user->id)
                ->where('status_validasi', 'mentah')
                ->first();
        }

        if (
            ! $editingSubmission
            && $dataForm->batasi_satu_respon
            && $dataForm->submissions()->where('diisi_oleh', $user->id)->exists()
        ) {
            $msg = 'Formulir ini dibatasi 1 respons per akun dan Anda sudah mengisinya.';

            return $request->expectsJson()
                ? response()->json(['message' => $msg], 422)
                : back()->with('error', $msg);
        }

        $dataForm->loadMissing('kolomFormulir');
        $items = $dataForm->items ?? [];
        $fields = $dataForm->fields ?? [];

        // Extract normalized raw inputs for every input field first (also needed for section branching trace)
        $extracted = [];
        foreach ($fields as $field) {
            $extracted[(string) $field['id']] = $this->extractFieldInput($request, $field);
        }

        $activeFieldIds = $quality->resolveActiveFieldIds($items, $extracted);

        $values = [];
        $cleanStatusMap = [];
        $attachments = [];
        $errors = [];

        foreach ($fields as $field) {
            $fid = (string) ($field['id'] ?? '');
            $type = (string) ($field['type'] ?? 'text');
            $required = (bool) ($field['required'] ?? false);
            $config = is_array($field['config'] ?? null) ? $field['config'] : [];

            if (! isset($activeFieldIds[$fid])) {
                $values[$fid] = '';
                $cleanStatusMap[$fid] = true;
                continue;
            }

            if ($type === 'file') {
                $tokenRaw = trim((string) $request->input("uploaded_tokens.{$fid}", ''));
                $uploaded = $request->file("files.{$fid}") ?? $request->file("values.{$fid}");
                $fileList = is_array($uploaded) ? array_values(array_filter($uploaded)) : ($uploaded ? [$uploaded] : []);
                $existingAttachment = $editingSubmission?->attachments[$fid] ?? null;
                $maxFiles = max(1, min(20, (int) ($config['file_max_count'] ?? 1)));
                $rawMaxMb = array_key_exists('file_max_mb', $config) ? (int) $config['file_max_mb'] : 0;
                $maxMb = $rawMaxMb <= 0 ? 0 : min(1024, $rawMaxMb);
                $allowedExts = $quality->allowedFileExtensions($field);

                // Path A: Chunked upload encrypted token(s)
                if ($tokenRaw !== '' && empty($fileList)) {
                    $decodedTokens = json_decode($tokenRaw, true);
                    $tokenList = is_array($decodedTokens) ? $decodedTokens : [$tokenRaw];
                    $tokenList = array_values(array_filter(array_map('strval', $tokenList)));

                    if (empty($tokenList)) {
                        if ($tokenRaw !== '[]') {
                            $errors[$fid] = 'Sesi berkas unggahan tidak valid atau kedaluwarsa. Unggah ulang berkas Anda.';
                            continue;
                        }
                        // Treat "[]" as no upload so Path B enforces $required validation cleanly
                    } else {
                        if (count($tokenList) > $maxFiles) {
                            $errors[$fid] = "Jumlah berkas maksimal {$maxFiles} file.";
                            continue;
                        }

                        $storedPaths = [];
                        $origNames = [];
                        $tokenFailed = false;

                        foreach ($tokenList as $tok) {
                            try {
                                $meta = json_decode(Crypt::decryptString($tok), true);
                            } catch (Throwable) {
                                $meta = null;
                            }

                            $tokPath = is_array($meta) ? (string) ($meta['path'] ?? '') : '';
                            $tokName = is_array($meta) ? (string) ($meta['name'] ?? '') : '';
                            $tokSize = is_array($meta) ? (int) ($meta['size'] ?? 0) : 0;

                            if (
                                ! is_array($meta)
                                || (int) ($meta['user_id'] ?? 0) !== (int) $user->id
                                || (int) ($meta['form_id'] ?? 0) !== (int) $dataForm->id
                                || (string) ($meta['field_id'] ?? '') !== $fid
                                || $tokPath === ''
                                || ! Storage::disk('local')->exists($tokPath)
                            ) {
                                $errors[$fid] = 'Sesi berkas unggahan tidak valid atau kedaluwarsa. Unggah ulang berkas Anda.';
                                $tokenFailed = true;
                                break;
                            }

                            $actualSize = (int) Storage::disk('local')->size($tokPath);
                            if ($tokSize <= 0 || $actualSize <= 0) {
                                $errors[$fid] = 'Berkas unggahan kosong (0 byte) atau rusak. Unggah ulang berkas Anda.';
                                $tokenFailed = true;
                                break;
                            }

                            $nameErr = $quality->fileNameError($field, $tokName);
                            if ($nameErr !== null) {
                                $errors[$fid] = $nameErr;
                                $tokenFailed = true;
                                break;
                            }

                            if ($maxMb > 0 && $actualSize > $maxMb * 1024 * 1024) {
                                $errors[$fid] = "Ukuran berkas maksimal {$maxMb} MB.";
                                $tokenFailed = true;
                                break;
                            }

                            $storedPaths[] = $tokPath;
                            $origNames[] = $tokName;
                        }

                        if ($tokenFailed) {
                            continue;
                        }

                        $values[$fid] = implode(', ', $origNames);
                        $cleanStatusMap[$fid] = true;
                        if (! empty($storedPaths)) {
                            $attachments[$fid] = $storedPaths[0];
                        }
                        continue;
                    }
                }

                // Path B: Direct multipart form upload fallback
                if (empty($fileList)) {
                    if ($existingAttachment) {
                        $values[$fid] = (string) ($editingSubmission->values[$fid] ?? basename($existingAttachment));
                        $attachments[$fid] = $existingAttachment;
                        $cleanStatusMap[$fid] = true;
                    } elseif ($required) {
                        $errors[$fid] = 'Lampiran bukti wajib diunggah.';
                    } else {
                        $values[$fid] = '';
                        $cleanStatusMap[$fid] = true;
                    }
                    continue;
                }

                if (count($fileList) > $maxFiles) {
                    $errors[$fid] = "Jumlah berkas maksimal {$maxFiles} file.";
                    continue;
                }

                $storedPaths = [];
                $origNames = [];
                $fileInvalid = false;

                foreach ($fileList as $uploadedFile) {
                    if (! $uploadedFile->isValid()) {
                        $errCode = $uploadedFile->getError();
                        $errors[$fid] = in_array($errCode, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
                            ? 'Ukuran berkas melebihi batas unggahan langsung PHP ('.ini_get('upload_max_filesize').'). Gunakan halaman formulir terbaru agar sistem mengunggah bertahap otomatis.'
                            : 'Berkas gagal diunggah (kode PHP '.$errCode.'). Periksa ukuran atau coba unggah ulang.';
                        $fileInvalid = true;
                        break;
                    }

                    if ((int) $uploadedFile->getSize() <= 0) {
                        $errors[$fid] = 'Berkas kosong (0 byte) tidak dapat diunggah.';
                        $fileInvalid = true;
                        break;
                    }

                    $origName = $uploadedFile->getClientOriginalName();
                    $nameErr = $quality->fileNameError($field, $origName);
                    if ($nameErr !== null) {
                        $errors[$fid] = $nameErr;
                        $fileInvalid = true;
                        break;
                    }

                    $fileRules = ['file'];
                    $customMessages = [];
                    if ($allowedExts !== null) {
                        $allowedUpper = strtoupper(implode(', ', $allowedExts));
                        $fileRules[] = 'mimes:'.implode(',', $allowedExts);
                        $customMessages['file.mimes'] = "Format berkas harus berupa: {$allowedUpper}.";
                    }
                    if ($maxMb > 0) {
                        $fileRules[] = 'max:'.($maxMb * 1024);
                        $customMessages['file.max'] = "Ukuran berkas maksimal {$maxMb} MB.";
                    }

                    $fileValidator = Validator::make(['file' => $uploadedFile], ['file' => $fileRules], $customMessages);
                    if ($fileValidator->fails()) {
                        $errors[$fid] = $fileValidator->errors()->first('file');
                        $fileInvalid = true;
                        break;
                    }

                    $storedPath = $uploadedFile->store("submissions/{$dataForm->code}", 'local');
                    if ($storedPath !== false) {
                        $storedPaths[] = $storedPath;
                        $origNames[] = $origName;
                    }
                }

                if ($fileInvalid) {
                    continue;
                }

                $values[$fid] = implode(', ', $origNames);
                $cleanStatusMap[$fid] = true;
                if (! empty($storedPaths)) {
                    $attachments[$fid] = $storedPaths[0];
                }
            } else {
                $rawValue = $extracted[$fid] ?? '';
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

        $quizResult = $this->evaluateQuizScore($settings, $fields, $values, $quality);

        $submission = DB::transaction(function () use ($editingSubmission, $dataForm, $user, $values, $attachments, $cleanStatusMap, $preprocessing): FormSubmission {
            $firstAttachment = ! empty($attachments) ? reset($attachments) : null;

            if ($editingSubmission) {
                $editingSubmission->update([
                    'waktu_pengisian' => now(),
                    'status_validasi' => 'mentah',
                    'jalur_berkas_bukti' => is_string($firstAttachment) ? $firstAttachment : $editingSubmission->jalur_berkas_bukti,
                ]);
                $editingSubmission->syncValues($dataForm, $values, ! empty($attachments) ? $attachments : null, $cleanStatusMap, false);
                $preprocessing->markFormNeedsPreprocessing($dataForm);

                return $editingSubmission;
            }

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
                'quiz_result' => $quizResult,
            ], 201);
        }

        $redirect = back()->with(
            'success',
            "Formulir Berhasil Disimpan ({$submission->code}). Data masuk ke antrean Pre-processing Wali Data."
        );

        $customConfirm = trim((string) ($settings['confirmation_message'] ?? ''));
        if ($customConfirm !== '') {
            $redirect->with('custom_confirmation', $customConfirm);
        }

        if ($quizResult !== null && ($settings['release_grade'] ?? 'immediately') === 'immediately') {
            $redirect->with('quiz_result', $quizResult);
        }

        return $redirect;
    }

    /**
     * Extract and flatten incoming request input for any Google Forms field type into a canonical string.
     *
     * @param  array<string, mixed>  $field
     */
    protected function extractFieldInput(Request $request, array $field): string
    {
        $fid = (string) ($field['id'] ?? '');
        $type = (string) ($field['type'] ?? 'text');
        $config = is_array($field['config'] ?? null) ? $field['config'] : [];
        $rawInput = $request->input("values.{$fid}", '');
        $otherInput = trim((string) $request->input("values_other.{$fid}", ''));

        if ($type === 'radio') {
            $val = is_array($rawInput) ? (string) ($rawInput[0] ?? '') : (string) $rawInput;
            if ($val === '__other__') {
                return $otherInput;
            }

            return trim($val);
        }

        if ($type === 'checkbox') {
            if (! is_array($rawInput)) {
                $str = trim((string) $rawInput);

                return $str === '__other__' ? $otherInput : $str;
            }
            $picked = [];
            foreach ($rawInput as $item) {
                $itemStr = trim((string) $item);
                if ($itemStr === '__other__') {
                    if ($otherInput !== '') {
                        $picked[] = $otherInput;
                    }
                } elseif ($itemStr !== '') {
                    $picked[] = $itemStr;
                }
            }

            return implode(', ', $picked);
        }

        if ($type === 'mc_grid' || $type === 'checkbox_grid') {
            if (! is_array($rawInput)) {
                return trim((string) $rawInput);
            }
            $gridRows = array_values(array_filter(array_map('strval', $config['grid_rows'] ?? [])));
            $segments = [];
            foreach ($gridRows as $rIdx => $rowLabel) {
                $cellVal = $rawInput[$rIdx] ?? ($rawInput[$rowLabel] ?? null);
                if (is_array($cellVal)) {
                    $cols = array_values(array_filter(array_map(
                        static fn ($c): string => trim((string) $c),
                        $cellVal
                    ), static fn (string $c): bool => $c !== ''));
                    if (! empty($cols)) {
                        $segments[] = $rowLabel.': '.implode(', ', $cols);
                    }
                } elseif (is_string($cellVal) && trim($cellVal) !== '') {
                    $segments[] = $rowLabel.': '.trim($cellVal);
                }
            }

            return implode('; ', $segments);
        }

        if ($type === 'date' && ($config['include_time'] ?? false)) {
            $dateStr = is_array($rawInput) ? '' : trim((string) $rawInput);
            $timeStr = trim((string) $request->input("values_time.{$fid}", ''));
            if ($dateStr !== '' && $timeStr !== '' && ! str_contains($dateStr, 'T') && ! str_contains($dateStr, ' ')) {
                return $dateStr.'T'.$timeStr;
            }

            return $dateStr;
        }

        return is_array($rawInput) ? implode(', ', $rawInput) : trim((string) $rawInput);
    }

    /**
     * Evaluate Quiz score when Quiz mode is active on the form.
     *
     * @param  array<string, mixed>  $settings
     * @param  array<int, array<string, mixed>>  $fields
     * @param  array<string, string>  $values
     * @return array<string, mixed>|null
     */
    protected function evaluateQuizScore(
        array $settings,
        array $fields,
        array $values,
        DataQualityService $quality
    ): ?array {
        if (! ($settings['is_quiz'] ?? false)) {
            return null;
        }

        $earned = 0;
        $possible = 0;
        $breakdown = [];

        foreach ($fields as $fld) {
            $config = is_array($fld['config'] ?? null) ? $fld['config'] : [];
            $points = max(0, (int) ($config['points'] ?? 0));
            $answerKey = trim((string) ($config['answer_key'] ?? ''));

            if ($points === 0 && $answerKey === '') {
                continue;
            }

            $fid = (string) ($fld['id'] ?? '');
            $type = (string) ($fld['type'] ?? 'text');
            $userAns = trim((string) ($values[$fid] ?? ''));
            $isCorrect = false;

            if ($answerKey !== '' && $userAns !== '') {
                if ($type === 'checkbox') {
                    $keySet = array_values(array_filter(array_map(
                        static fn (string $s): string => mb_strtolower(trim($s)),
                        explode(',', $answerKey)
                    )));
                    $userSet = array_values(array_filter(array_map(
                        static fn (string $s): string => mb_strtolower(trim($s)),
                        explode(',', $userAns)
                    )));
                    sort($keySet);
                    sort($userSet);
                    $isCorrect = $keySet === $userSet && ! empty($keySet);
                } elseif ($type === 'mc_grid' || $type === 'checkbox_grid') {
                    $normKey = mb_strtolower($quality->normalizeValue($fld, $answerKey));
                    $normUser = mb_strtolower($quality->normalizeValue($fld, $userAns));
                    $isCorrect = $normKey !== '' && $normKey === $normUser;
                } else {
                    $isCorrect = mb_strtolower($quality->normalizeValue($fld, $userAns))
                        === mb_strtolower($quality->normalizeValue($fld, $answerKey));
                }
            }

            $possible += $points;
            $ptsEarned = $isCorrect ? $points : 0;
            $earned += $ptsEarned;

            $breakdown[] = [
                'id' => $fid,
                'label' => (string) ($fld['label'] ?? $fid),
                'user_answer' => $userAns !== '' ? $userAns : '(tidak dijawab)',
                'correct_answer' => $answerKey,
                'is_correct' => $isCorrect,
                'points_earned' => $ptsEarned,
                'points_possible' => $points,
                'feedback' => $isCorrect
                    ? trim((string) ($config['feedback_correct'] ?? ''))
                    : trim((string) ($config['feedback_wrong'] ?? '')),
            ];
        }

        if ($possible === 0 && empty($breakdown)) {
            return null;
        }

        return [
            'earned' => $earned,
            'possible' => $possible,
            'pct' => $possible > 0 ? (int) round(($earned / $possible) * 100) : 0,
            'show_missed' => (bool) ($settings['quiz_show_missed'] ?? true),
            'show_correct' => (bool) ($settings['quiz_show_correct'] ?? true),
            'show_points' => (bool) ($settings['quiz_show_points'] ?? true),
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Extract non-empty parameter across form input, raw JSON payload, query string, or HTTP header.
     *
     * @param  array<string, mixed>  $json
     */
    private function extractUploadParam(Request $request, array $json, string $key, ?string $header = null): string
    {
        $candidates = [
            $request->input($key),
            $json[$key] ?? null,
            $request->query($key),
            $header !== null ? $request->header($header) : null,
        ];

        foreach ($candidates as $val) {
            if ($val === null || is_array($val) || is_object($val)) {
                continue;
            }
            $str = trim((string) $val);
            if ($str !== '') {
                return $str;
            }
        }

        return '';
    }

    /**
     * Receive FormData, JSON Base64, or raw binary file chunks so large files or any allowed format upload reliably
     * even when PHP server's upload_max_filesize or upload_tmp_dir is restrictive.
     */
    public function uploadChunk(Request $request, DataForm $dataForm, DataQualityService $quality): JsonResponse
    {
        $user = $request->user();
        if (! $user || (! $user->isProdusen() && ! $user->isAdmin())) {
            return response()->json(['message' => 'Hanya Produsen Data atau Admin yang dapat mengunggah berkas.'], 403);
        }
        if (! $dataForm->canBeSeenBy($user)) {
            return response()->json(['message' => 'Formulir ini tidak ditujukan untuk Anda.'], 403);
        }
        if (! $dataForm->isOpen()) {
            return response()->json(['message' => 'Formulir sudah ditutup.'], 422);
        }

        $rawContent = (string) $request->getContent();
        $jsonDecoded = json_decode($rawContent, true);
        $jsonPayload = is_array($jsonDecoded) ? $jsonDecoded : [];

        $fid = $this->extractUploadParam($request, $jsonPayload, 'field_id', 'X-Field-Id');
        $rawUploadId = $this->extractUploadParam($request, $jsonPayload, 'upload_id', 'X-Upload-Id');
        $uploadId = preg_replace('/[^a-zA-Z0-9_-]/', '', $rawUploadId) ?: Str::random(16);

        $chunkIdxRaw = $this->extractUploadParam($request, $jsonPayload, 'chunk_index', 'X-Chunk-Index');
        $totalChunksRaw = $this->extractUploadParam($request, $jsonPayload, 'total_chunks', 'X-Total-Chunks');
        $chunkIndex = $chunkIdxRaw !== '' ? max(0, (int) $chunkIdxRaw) : 0;
        $totalChunks = $totalChunksRaw !== '' ? max(1, min(131072, (int) $totalChunksRaw)) : 1;

        $rawFileName = $this->extractUploadParam($request, $jsonPayload, 'file_name', null);
        $origName = basename($rawFileName !== ''
            ? $rawFileName
            : rawurldecode((string) $request->header('X-File-Name', 'berkas.bin')));

        $dataForm->loadMissing('kolomFormulir');
        $field = $fid !== '' ? collect($dataForm->fields ?? [])->firstWhere('id', $fid) : null;
        if (! is_array($field) || ($field['type'] ?? '') !== 'file') {
            return response()->json([
                'message' => 'Kolom unggahan tidak cocok dengan versi formulir terbaru (formulir kemungkinan baru saja diubah). Muat ulang halaman ini (F5) lalu unggah ulang berkas Anda.',
                'reload_required' => true,
            ], 422);
        }

        $nameErr = $quality->fileNameError($field, $origName);
        if ($nameErr !== null) {
            return response()->json(['message' => $nameErr], 422);
        }

        $chunkB64 = $this->extractUploadParam($request, $jsonPayload, 'chunk_b64', null);
        if ($chunkB64 !== '') {
            $decoded = base64_decode($chunkB64, true);
            if ($decoded === false) {
                return response()->json(['message' => 'Format potongan berkas tidak valid.'], 422);
            }
            $bytes = $decoded;
        } elseif (($chunkFile = $request->file('chunk')) !== null) {
            if (! $chunkFile->isValid()) {
                return response()->json(['message' => 'Potongan berkas gagal diterima server (kode PHP '.$chunkFile->getError().').'], 422);
            }
            $bytes = file_get_contents($chunkFile->getRealPath());
            $bytes = $bytes === false ? '' : $bytes;
        } else {
            $bytes = $rawContent;
        }

        $config = is_array($field['config'] ?? null) ? $field['config'] : [];
        $rawMaxMb = array_key_exists('file_max_mb', $config) ? (int) $config['file_max_mb'] : 0;
        $maxMb = $rawMaxMb <= 0 ? 0 : min(1024, $rawMaxMb);

        $chunkRel = "chunks/{$dataForm->code}_{$user->id}_{$uploadId}.part";
        $chunkAbs = Storage::disk('local')->path($chunkRel);
        $chunkDir = dirname($chunkAbs);
        if (! is_dir($chunkDir)) {
            mkdir($chunkDir, 0775, true);
        }

        if ($bytes === '') {
            if ($chunkIndex === 0 && is_file($chunkAbs)) {
                @unlink($chunkAbs);
            }

            return response()->json(['message' => 'Potongan berkas kosong (0 byte) atau gagal diterima server. Coba unggah ulang.'], 422);
        }

        $mode = $chunkIndex === 0 ? 'wb' : 'ab';
        $fp = fopen($chunkAbs, $mode);
        if ($fp === false) {
            return response()->json(['message' => 'Gagal menyimpan potongan berkas sementara.'], 500);
        }
        fwrite($fp, $bytes);
        fclose($fp);

        clearstatcache(true, $chunkAbs);
        $currentSize = (int) filesize($chunkAbs);

        if ($maxMb > 0 && $currentSize > $maxMb * 1024 * 1024) {
            @unlink($chunkAbs);

            return response()->json(['message' => "Ukuran berkas melebihi batas maksimal ({$maxMb} MB)."], 422);
        }

        if ($chunkIndex < $totalChunks - 1) {
            return response()->json([
                'ok' => true,
                'completed' => false,
                'chunk' => $chunkIndex,
            ]);
        }

        if ($currentSize <= 0) {
            @unlink($chunkAbs);

            return response()->json(['message' => 'Berkas kosong (0 byte) tidak dapat disimpan.'], 422);
        }

        $ext = strtolower((string) pathinfo($origName, PATHINFO_EXTENSION));
        $safeBase = Str::slug(pathinfo($origName, PATHINFO_FILENAME)) ?: 'berkas';
        $finalRel = "submissions/{$dataForm->code}/".Str::random(24).'_'.$safeBase.($ext !== '' ? '.'.$ext : '');
        $finalAbs = Storage::disk('local')->path($finalRel);
        $finalDir = dirname($finalAbs);
        if (! is_dir($finalDir)) {
            mkdir($finalDir, 0775, true);
        }
        rename($chunkAbs, $finalAbs);

        if (! Storage::disk('local')->exists($finalRel) || (int) Storage::disk('local')->size($finalRel) <= 0) {
            return response()->json(['message' => 'Berkas gagal disimpan ke penyimpanan server.'], 500);
        }

        $token = Crypt::encryptString(json_encode([
            'user_id' => (int) $user->id,
            'form_id' => (int) $dataForm->id,
            'field_id' => $fid,
            'path' => $finalRel,
            'name' => $origName,
            'size' => $currentSize,
        ], JSON_THROW_ON_ERROR));

        return response()->json([
            'ok' => true,
            'completed' => true,
            'token' => $token,
            'filename' => $origName,
            'size' => $currentSize,
        ], 201);
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

        $rawLabel = (string) ($submission->values[$fieldId] ?? basename($path));
        $originalName = trim((string) explode(',', $rawLabel)[0]) ?: basename($path);

        if ($request->boolean('inline')) {
            $ext = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
            if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
                return Storage::disk('local')->response($path, $originalName);
            }
        }

        return Storage::disk('local')->download($path, $originalName);
    }
}
