<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\DataForm;
use App\Support\CodeGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class DataFormController extends Controller
{
    public function index(Request $request): View
    {
        $status = (string) $request->query('status', 'all');
        if (! in_array($status, ['all', 'open', 'closed'], true)) {
            $status = 'all';
        }

        $counts = [
            'all' => DataForm::query()->count(),
            'open' => DataForm::open()->count(),
            'closed' => DataForm::closed()->count(),
        ];

        $query = DataForm::query()
            ->with(['category', 'kolomFormulir'])
            ->withCount([
                'submissions',
                'submissions as valid_submissions_count' => fn ($q) => $q->where('status_validasi', 'valid'),
                'submissions as raw_submissions_count' => fn ($q) => $q->where('status_validasi', 'mentah'),
            ])
            ->orderByDesc('created_at');

        if ($status === 'open') {
            $query->open();
        } elseif ($status === 'closed') {
            $query->closed();
        }

        $forms = $query->paginate(10)->withQueryString();
        $categories = Category::query()->orderBy('id')->get();

        return view('panel.formulir', compact('forms', 'categories', 'status', 'counts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePayload($request, true);

        DB::transaction(function () use ($request, $data): void {
            $form = DataForm::create([
                'code' => CodeGenerator::next('F', 'formulir'),
                'id_kategori' => $data['category_id'],
                'judul' => $data['title'],
                'deskripsi' => $data['description'],
                'tahun' => $data['tahun'],
                'periode' => $data['periode'],
                'target' => $data['target'],
                'batasi_satu_respon' => $data['batasi_satu_respon'],
                'status_preprocessing' => 'belum',
                'tanggal_waktu' => $data['deadline'],
                'status_aktif' => true,
                'dibuat_oleh' => $request->user()->id,
            ]);

            $form->syncFields($data['fields']);
        });

        return redirect()->route('panel.formulir')
            ->with('success', 'Formulir Berhasil Dibuat');
    }

    public function update(Request $request, DataForm $dataForm): RedirectResponse
    {
        $data = $this->validatePayload($request, false);

        DB::transaction(function () use ($dataForm, $data): void {
            $dataForm->update([
                'id_kategori' => $data['category_id'],
                'judul' => $data['title'],
                'deskripsi' => $data['description'],
                'tahun' => $data['tahun'],
                'periode' => $data['periode'],
                'target' => $data['target'],
                'batasi_satu_respon' => $data['batasi_satu_respon'],
                'tanggal_waktu' => $data['deadline'],
                'status_aktif' => $data['deadline']->isFuture(),
            ]);

            $dataForm->syncFields($data['fields']);
        });

        return redirect()->route('panel.formulir')
            ->with('success', 'Formulir diperbarui.');
    }

    public function toggleActive(DataForm $dataForm): RedirectResponse
    {
        if ($dataForm->status_aktif) {
            $dataForm->tutupFormulir();

            return back()->with('info', "Formulir “{$dataForm->judul}” ditutup.");
        }

        if ($dataForm->tanggal_waktu === null || $dataForm->tanggal_waktu->isPast()) {
            return back()->with('error', 'Tenggat waktu formulir sudah lewat. Perpanjang tenggat melalui tombol Ubah sebelum membuka kembali.');
        }

        $dataForm->update(['status_aktif' => true]);

        return back()->with('success', "Formulir “{$dataForm->judul}” dibuka kembali.");
    }

    public function destroy(DataForm $dataForm): RedirectResponse
    {
        $dataForm->delete();

        return redirect()->route('panel.formulir')
            ->with('success', 'Formulir dihapus.');
    }

    /**
     * @return array{
     *     category_id: int,
     *     title: string,
     *     description: string,
     *     tahun: int,
     *     periode: string,
     *     target: string,
     *     batasi_satu_respon: bool,
     *     deadline: Carbon,
     *     fields: array<int, array<string, mixed>>
     * }
     */
    protected function validatePayload(Request $request, bool $isCreate): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:1000'],
            'category_id' => ['required', 'integer', 'exists:kategori,id'],
            'tahun' => ['required', 'digits:4'],
            'periode' => ['required', 'in:Ganjil,Genap'],
            'target' => ['required', 'in:Mahasiswa,Dosen,Semua'],
            'batasi_satu_respon' => ['nullable', 'boolean'],
            'deadline' => $isCreate ? ['required', 'date', 'after:now'] : ['required', 'date'],
            'fields_json' => ['required', 'string'],
        ];

        $messages = [
            'title.required' => 'Judul formulir wajib diisi.',
            'category_id.required' => 'Kategori data wajib dipilih.',
            'tahun.digits' => 'Tahun harus 4 digit.',
            'deadline.required' => 'Tenggat waktu wajib diisi.',
            'deadline.after' => 'Tenggat harus setelah waktu sekarang.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        $validated = $validator->validate();

        $rawFields = json_decode($validated['fields_json'], true);
        if (! is_array($rawFields) || empty($rawFields)) {
            $validator->errors()->add('fields_json', 'Tambahkan minimal satu pertanyaan.');
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        $allowedTypes = ['text', 'textarea', 'number', 'date', 'radio', 'checkbox', 'select', 'file'];
        $cleanFields = [];

        foreach ($rawFields as $index => $item) {
            if (! is_array($item)) {
                continue;
            }
            $label = trim((string) ($item['label'] ?? ''));
            $type = (string) ($item['type'] ?? 'text');
            $fid = trim((string) ($item['id'] ?? '')) ?: 'q'.($index + 1);
            $required = (bool) ($item['required'] ?? false);
            $help = trim((string) ($item['help'] ?? ''));

            if ($label === '') {
                $validator->errors()->add('fields_json', 'Label pertanyaan wajib diisi.');
                throw new \Illuminate\Validation\ValidationException($validator);
            }

            if (! in_array($type, $allowedTypes, true)) {
                $type = 'text';
            }

            $fieldData = [
                'id' => $fid,
                'label' => $label,
                'help' => $help,
                'type' => $type,
                'required' => $required,
            ];

            if (in_array($type, ['select', 'radio', 'checkbox'], true)) {
                $optsRaw = $item['options'] ?? ($item['opt'] ?? []);
                $options = is_array($optsRaw)
                    ? array_values(array_filter(array_map('trim', array_map('strval', $optsRaw))))
                    : array_values(array_filter(array_map('trim', explode(',', (string) $optsRaw))));

                if (empty($options)) {
                    $validator->errors()->add('fields_json', "Pertanyaan pilihan \"{$label}\" wajib memiliki minimal satu opsi.");
                    throw new \Illuminate\Validation\ValidationException($validator);
                }
                $fieldData['options'] = $options;
            }

            $cleanFields[] = $fieldData;
        }

        return [
            'category_id' => (int) $validated['category_id'],
            'title' => trim($validated['title']),
            'description' => trim((string) ($validated['description'] ?? '')),
            'tahun' => (int) $validated['tahun'],
            'periode' => $validated['periode'],
            'target' => $validated['target'],
            'batasi_satu_respon' => $request->boolean('batasi_satu_respon'),
            'deadline' => Carbon::parse($validated['deadline']),
            'fields' => $cleanFields,
        ];
    }
}
