@extends('layouts.app')

@section('title', $dataForm->title . ' - Formulir Satu Data UNUSA')

@section('content')
@php
    $user = auth()->user();
    $fields = $dataForm->fields ?? [];
    $oldValues = old('values', []);
@endphp

<main class="min-h-screen bg-[#F6F9FC] py-8 sm:py-12">
    <div class="mx-auto max-w-3xl px-4 sm:px-6" x-data="publicGFormRunner()">
        <nav aria-label="Breadcrumb" class="mb-4 flex items-center justify-between gap-4 text-sm text-[#475569]">
            <div>
                <a href="{{ route('pengisian-data') }}" class="font-semibold hover:text-[#0069A8]">Portal Pengisian Data</a>
                <span class="mx-1.5">/</span>
                <span class="font-mono font-semibold text-[#0069A8]">{{ $dataForm->code }}</span>
            </div>
            @auth
                <a href="{{ route('panel.riwayat') }}" class="text-xs font-bold text-[#0069A8] hover:underline">Riwayat saya</a>
            @endauth
        </nav>

        <!-- GForm Header Card -->
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="h-3 bg-gradient-to-r from-[#182654] via-[#0069A8] to-[#00A6F4]" aria-hidden="true"></div>
            <div class="p-6 sm:p-8">
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <span class="rounded-md bg-[#F0F9FF] px-2.5 py-1 font-bold text-[#0069A8]">{{ $dataForm->category?->nama_kategori ?? 'Umum' }}</span>
                    <span class="rounded-md bg-slate-100 px-2.5 py-1 font-mono font-semibold text-slate-700">{{ $dataForm->code }}</span>
                    <span class="font-medium text-[#475569]">{{ $dataForm->tahun }} · Semester {{ $dataForm->periode }} · Sasaran: {{ $dataForm->target }}</span>
                    @if($dataForm->batasi_satu_respon)
                        <span class="rounded-md bg-[#FFFBEB] px-2 py-0.5 font-semibold text-[#92400E]">Maks 1x per akun</span>
                    @endif
                </div>

                <h1 class="mt-4 text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">
                    {{ $dataForm->title }}
                </h1>

                @if($dataForm->description)
                    <p class="mt-2.5 text-sm leading-relaxed text-[#475569]">{{ $dataForm->description }}</p>
                @endif

                <div class="mt-5 flex flex-wrap items-center gap-4 border-t border-slate-100 pt-4 text-xs text-[#475569]">
                    <span class="inline-flex items-center gap-1.5 font-semibold {{ $dataForm->isUrgent() ? 'text-[#B91C1C]' : 'text-[#92400E]' }}">
                        <i data-lucide="calendar-clock" class="h-4 w-4" aria-hidden="true"></i>
                        Tenggat {{ $dataForm->deadlineFormatted() }} ({{ $dataForm->timeLeftLabel() }})
                    </span>
                    <span>{{ count($fields) }} pertanyaan</span>
                    @if($alreadySubmittedCount > 0)
                        <span class="inline-flex items-center gap-1 font-semibold text-[#047857]">
                            <i data-lucide="check-circle-2" class="h-4 w-4" aria-hidden="true"></i>
                            Anda sudah mengirim {{ $alreadySubmittedCount }}x
                        </span>
                    @endif
                </div>
            </div>
        </section>

        @if(session('success'))
            <div class="mt-4 rounded-2xl border border-emerald-200 bg-[#ECFDF5] p-5 text-sm text-[#047857]">
                <p class="font-bold">{{ session('success') }}</p>
                <p class="mt-1 text-xs text-slate-700">Setelah masuk fase Pre-processing dan dinyatakan valid oleh Wali Data, data siap dikemas menjadi dataset resmi prodi.</p>
            </div>
        @endif

        @if(session('error'))
            <div class="mt-4 rounded-2xl border border-red-200 bg-[#FEF2F2] p-4 text-sm font-semibold text-[#B91C1C]">
                {{ session('error') }}
            </div>
        @endif

        <!-- Identitas Responden -->
        <section class="mt-4 rounded-2xl border border-slate-200 bg-white px-6 py-4">
            @if($user)
                <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
                    <div>
                        <p class="text-xs font-semibold text-[#475569]">Identitas Responden Tercatat</p>
                        <p class="font-bold text-slate-900">{{ $user->name }} <span class="font-mono text-xs font-normal text-[#475569]">({{ $user->nim }})</span></p>
                    </div>
                    <span class="rounded-full bg-[#F0F9FF] px-3 py-1 text-xs font-bold text-[#0069A8]">
                        {{ $user->roleLabel() }}{{ $user->jenis ? ' · '.$user->jenis : '' }}
                    </span>
                </div>
            @else
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm text-[#475569]">Silakan masuk menggunakan akun kampus Anda agar pengirim tercatat otomatis.</p>
                    <a href="{{ route('login') }}" class="inline-flex min-h-[42px] items-center rounded-xl bg-[#0069A8] px-4 py-2 text-sm font-semibold text-white hover:bg-[#182654]">
                        Masuk untuk mengisi
                    </a>
                </div>
            @endif
        </section>

        @if(! $canSubmit)
            <section class="mt-4 rounded-2xl border border-amber-200 bg-[#FFFBEB] p-6 text-slate-800">
                <h2 class="text-base font-bold text-[#92400E]">Formulir tidak dapat dikirim saat ini</h2>
                <p class="mt-1 text-sm text-slate-700">{{ $closedReason }}</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    @guest
                        <a href="{{ route('login') }}" class="inline-flex min-h-[42px] items-center rounded-xl bg-[#0069A8] px-4 py-2 text-sm font-semibold text-white hover:bg-[#182654]">Masuk sekarang</a>
                    @else
                        @if($user->isWali())
                            <a href="{{ route('panel.preprocessing', ['form' => $dataForm->code]) }}" class="inline-flex min-h-[42px] items-center rounded-xl bg-[#0069A8] px-4 py-2 text-sm font-semibold text-white hover:bg-[#182654]">Buka Pre-processing Data</a>
                        @endif
                    @endguest
                    <a href="{{ route('pengisian-data') }}" class="inline-flex min-h-[42px] items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Daftar formulir lainnya</a>
                </div>
            </section>
        @else
            <form action="{{ route('panel.isi.submit', $dataForm->code) }}" method="POST" enctype="multipart/form-data" class="mt-4 space-y-4">
                @csrf

                @foreach($fields as $idx => $fld)
                    @php
                        $fid = (string) ($fld['id'] ?? 'q'.$idx);
                        $type = (string) ($fld['type'] ?? 'text');
                        $req = (bool) ($fld['required'] ?? false);
                        $help = (string) ($fld['help'] ?? '');
                        $options = $fld['options'] ?? [];
                        $oldVal = $oldValues[$fid] ?? '';
                        $fieldErr = $errors->first($fid);
                    @endphp
                    <div class="rounded-2xl border {{ $fieldErr ? 'border-[#B91C1C]' : 'border-slate-200 focus-within:border-[#0069A8]' }} bg-white p-5 sm:p-6">
                        <div class="mb-3">
                            <label for="fld_{{ $fid }}" class="block text-sm font-bold text-slate-900">
                                <span class="mr-1.5 font-mono text-xs text-[#0069A8]">{{ $idx + 1 }}.</span>
                                {{ $fld['label'] }}
                                @if($req)
                                    <span class="ml-0.5 text-[#B91C1C]" aria-hidden="true">*</span>
                                @endif
                            </label>
                            @if($help !== '')
                                <p class="mt-1 text-xs text-[#475569]">{{ $help }}</p>
                            @endif
                        </div>

                        @if($type === 'textarea')
                            <textarea
                                id="fld_{{ $fid }}"
                                name="values[{{ $fid }}]"
                                rows="4"
                                {{ $req ? 'required' : '' }}
                                class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-[#0069A8] focus:ring-4 focus:ring-sky-100"
                            >{{ is_string($oldVal) ? $oldVal : '' }}</textarea>
                        @elseif($type === 'select')
                            <select
                                id="fld_{{ $fid }}"
                                name="values[{{ $fid }}]"
                                {{ $req ? 'required' : '' }}
                                class="min-h-[44px] w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-[#0069A8] focus:ring-4 focus:ring-sky-100"
                            >
                                <option value="">Pilih jawaban...</option>
                                @foreach($options as $opt)
                                    <option value="{{ $opt }}" {{ $oldVal === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                        @elseif($type === 'radio')
                            <div class="space-y-2" role="radiogroup" aria-label="{{ $fld['label'] }}">
                                @foreach($options as $oIdx => $opt)
                                    <label class="flex min-h-[44px] cursor-pointer items-center gap-3 rounded-xl border border-slate-200 px-3.5 py-2 text-sm text-slate-800 transition hover:bg-[#F6F9FC]">
                                        <input type="radio"
                                               name="values[{{ $fid }}]"
                                               value="{{ $opt }}"
                                               {{ $oldVal === $opt ? 'checked' : '' }}
                                               {{ ($req && $oIdx === 0) ? 'required' : '' }}
                                               class="h-4 w-4 accent-[#0069A8]" />
                                        <span>{{ $opt }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @elseif($type === 'checkbox')
                            @php
                                $checkedArr = is_array($oldVal) ? $oldVal : array_map('trim', explode(',', (string) $oldVal));
                            @endphp
                            <div class="space-y-2" role="group" aria-label="{{ $fld['label'] }}">
                                @foreach($options as $opt)
                                    <label class="flex min-h-[44px] cursor-pointer items-center gap-3 rounded-xl border border-slate-200 px-3.5 py-2 text-sm text-slate-800 transition hover:bg-[#F6F9FC]">
                                        <input type="checkbox"
                                               name="values[{{ $fid }}][]"
                                               value="{{ $opt }}"
                                               {{ in_array($opt, $checkedArr, true) ? 'checked' : '' }}
                                               class="h-4 w-4 accent-[#0069A8]" />
                                        <span>{{ $opt }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @elseif($type === 'file')
                            <label class="flex min-h-[52px] cursor-pointer items-center gap-3 rounded-xl border border-dashed border-slate-300 bg-[#F6F9FC] px-4 py-3 text-sm transition hover:border-[#0069A8] hover:bg-[#F0F9FF]">
                                <i data-lucide="paperclip" class="h-4 w-4 shrink-0 text-[#0069A8]" aria-hidden="true"></i>
                                <span x-text="fileLabels['{{ $fid }}'] || 'Pilih berkas bukti (PDF, JPG, PNG maks 5 MB)...'" class="truncate text-slate-700"></span>
                                <input type="file"
                                       id="fld_{{ $fid }}"
                                       name="files[{{ $fid }}]"
                                       accept=".pdf,.jpg,.jpeg,.png"
                                       {{ $req ? 'required' : '' }}
                                       @change="fileLabels['{{ $fid }}'] = $event.target.files[0]?.name || ''"
                                       class="sr-only" />
                            </label>
                        @else
                            <input
                                id="fld_{{ $fid }}"
                                type="{{ in_array($type, ['number', 'date'], true) ? $type : 'text' }}"
                                name="values[{{ $fid }}]"
                                value="{{ is_string($oldVal) ? $oldVal : '' }}"
                                {{ $type === 'number' ? 'step="any"' : '' }}
                                {{ $req ? 'required' : '' }}
                                class="min-h-[44px] w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-[#0069A8] focus:ring-4 focus:ring-sky-100"
                            />
                        @endif

                        @if($fieldErr)
                            <p class="mt-2 text-xs font-semibold text-[#B91C1C]">{{ $fieldErr }}</p>
                        @endif
                    </div>
                @endforeach

                <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white px-6 py-4">
                    <p class="text-xs text-[#475569]">Kolom bertanda <span class="font-bold text-[#B91C1C]">*</span> wajib diisi. Data masuk ke antrean Pre-processing sebelum menjadi dataset.</p>
                    <button type="submit" class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl bg-[#0069A8] px-6 py-2.5 text-sm font-bold text-white transition hover:bg-[#182654]">
                        Kirim respons
                    </button>
                </div>
            </form>
        @endif
    </div>
</main>
@endsection

@push('scripts')
<script>
function publicGFormRunner() {
    return {
        fileLabels: {},
    };
}
</script>
@endpush
