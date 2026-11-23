<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\DataForm;
use App\Support\CodeGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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

        $now = now();
        $counts = [
            'all' => DataForm::query()->count(),
            'open' => DataForm::query()->where('deadline', '>', $now)->count(),
            'closed' => DataForm::query()->where('deadline', '<=', $now)->count(),
        ];

        $query = DataForm::query()
            ->with('category')
            ->withCount('submissions')
            ->orderByDesc('created_at');

        if ($status === 'open') {
            $query->where('deadline', '>', $now);
        } elseif ($status === 'closed') {
            $query->where('deadline', '<=', $now);
        }

        $forms = $query->paginate(10)->withQueryString();
        $categories = Category::query()->orderBy('id')->get();

        return view('panel.formulir', compact('forms', 'categories', 'status', 'counts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePayload($request, true);

        DataForm::create([
            'code' => CodeGenerator::next('F', 'data_forms'),
            'category_id' => $data['category_id'],
            'title' => $data['title'],
            'description' => $data['description'],
            'tahun' => $data['tahun'],
            'periode' => $data['periode'],
            'target' => $data['target'],
            'deadline' => $data['deadline'],
            'fields' => $data['fields'],
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('panel.formulir')
            ->with('success', 'Formulir Berhasil Dibuat');
    }

    public function update(Request $request, DataForm $dataForm): RedirectResponse
    {
        $data = $this->validatePayload($request, false);

        $dataForm->update([
            'category_id' => $data['category_id'],
            'title' => $data['title'],
            'description' => $data['description'],
            'tahun' => $data['tahun'],
            'periode' => $data['periode'],
            'target' => $data['target'],
            'deadline' => $data['deadline'],
            'fields' => $data['fields'],
        ]);

        return redirect()->route('panel.formulir')
            ->with('success', 'Formulir diperbarui.');
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
     *     deadline: Carbon,
     *     fields: array<int, array<string, mixed>>
     * }
     */
    protected function validatePayload(Request $request, bool $isCreate): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:1000'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'tahun' => ['required', 'digits:4'],
            'periode' => ['required', 'in:Ganjil,Genap'],
            'target' => ['required', 'in:Mahasiswa,Dosen,Semua'],
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

        $allowedTypes = ['text', 'textarea', 'number', 'date', 'select', 'file'];
        $cleanFields = [];

        foreach ($rawFields as $index => $item) {
            if (! is_array($item)) {
                continue;
            }
            $label = trim((string) ($item['label'] ?? ''));
            $type = (string) ($item['type'] ?? 'text');
            $fid = trim((string) ($item['id'] ?? '')) ?: 'q'.($index + 1);
            $required = (bool) ($item['required'] ?? false);

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
                'type' => $type,
                'required' => $required,
            ];

            if ($type === 'select') {
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
            'deadline' => Carbon::parse($validated['deadline']),
            'fields' => $cleanFields,
        ];
    }
}
