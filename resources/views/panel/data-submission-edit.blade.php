@extends('layouts.panel')

@section('title', 'Perbaiki Data Isian (' . $submission->code . ') - Panel Satu Data UNUSA')

@section('panel_content')
@php
    $qualitySvc = app(\App\Services\DataQualityService::class);
    $editorPayload = [
        'uploadChunkUrl' => route('panel.preprocessing.submissions.upload-chunk', $submission->code),
        'fields' => $fields,
        'oldUploadedTokens' => $oldUploadedTokens,
        'oldUploadedLabels' => $oldUploadedLabels,
    ];
    $formatSize = static function (int $bytes): string {
        if ($bytes < 1024) {
            return $bytes.' B';
        }
        if ($bytes < 1024 * 1024) {
            return number_format($bytes / 1024, 1, ',', '.').' KB';
        }

        return number_format($bytes / (1024 * 1024), 2, ',', '.').' MB';
    };
@endphp

<script type="application/json" id="submission-fix-data">@json($editorPayload)</script>

<div x-data="submissionFixEditor()">
    <!-- Full-Page Editorial Top Header Bar -->
    <section class="wd-bento mb-6 overflow-hidden border-[#BAE6FD]">
        <div class="bg-linear-to-r from-[#09122C] via-[#101F4A] to-[#182654] px-6 py-6 text-white">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <a href="{{ route('panel.preprocessing.show', $form->code) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-white/20 bg-white/10 px-3 py-1.5 font-semibold text-white transition hover:bg-white/20">
                            <i data-lucide="arrow-left" class="h-3.5 w-3.5" aria-hidden="true"></i>
                            Kembali ke Pre-processing
                        </a>
                        <span class="rounded-md bg-white/15 px-2.5 py-1 font-bold tabular-nums text-[#74D4FF]">{{ $submission->code }}</span>
                        <span class="rounded-md bg-white/10 px-2.5 py-1 tabular-nums text-slate-200">{{ $form->code }}</span>
                        <span class="rounded-md px-2.5 py-1 font-bold {{ $submission->status_validasi === 'valid' ? 'bg-emerald-400/20 text-emerald-200' : ($submission->status_validasi === 'ditolak' ? 'bg-slate-400/20 text-slate-200' : 'bg-amber-400/20 text-amber-200') }}">
                            {{ $submission->validationLabel() }}
                        </span>
                    </div>
                    <h1 class="mt-3 text-xl font-extrabold tracking-tight text-white sm:text-2xl">
                        Perbaiki Data Isian &middot; {{ $submission->code }}
                    </h1>
                    <p class="mt-1 text-sm text-slate-200">
                        {{ $form->title }} &middot; Pengirim: <strong class="text-white">{{ $submission->user?->name ?? '-' }}</strong> ({{ $submission->user?->nim ?? '-' }}) &middot; {{ $submission->submittedFormatted() }}
                    </p>
                </div>
            </div>
        </div>
    </section>

    <div class="grid grid-cols-1 items-start gap-6 xl:grid-cols-[320px_minmax(0,1fr)]">
        <!-- Left Context & Attachment Rail -->
        <aside class="space-y-4">
            <!-- Quality Findings -->
            <section class="wd-bento p-5">
                <p class="text-[10px] font-bold tracking-wider text-[#0069A8] uppercase">Inspeksi Kualitas</p>
                <h2 class="mt-1 text-sm font-extrabold text-slate-900">Status Baris Saat Ini</h2>
                @if(! empty($issues))
                    <ul class="mt-3 space-y-2 text-xs font-medium text-[#B91C1C]">
                        @foreach($issues as $issue)
                            <li class="flex items-start gap-2 rounded-xl border border-red-200 bg-[#FEF2F2] px-3 py-2">
                                <i data-lucide="alert-circle" class="mt-0.5 h-3.5 w-3.5 shrink-0" aria-hidden="true"></i>
                                <span>{{ $issue }}</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="mt-3 flex items-center gap-2 rounded-xl border border-emerald-200 bg-[#ECFDF5] px-3 py-2.5 text-xs font-semibold text-[#047857]">
                        <i data-lucide="check-circle-2" class="h-4 w-4 shrink-0" aria-hidden="true"></i>
                        <span>Seluruh kolom memenuhi standar kualitas.</span>
                    </div>
                @endif
            </section>

            <!-- Current Proof Attachments Inspector -->
            @if(! empty($attachmentMeta))
                <section class="wd-bento p-5">
                    <p class="text-[10px] font-bold tracking-wider text-[#0069A8] uppercase">Berkas Lampiran</p>
                    <h2 class="mt-1 text-sm font-extrabold text-slate-900">Bukti Pendukung Tersimpan</h2>
                    <div class="mt-3 space-y-3">
                        @foreach($fields as $fld)
                            @if(($fld['type'] ?? '') === 'file')
                                @php $meta = $attachmentMeta[$fld['id']] ?? null; @endphp
                                <div class="rounded-xl border border-slate-200 bg-[#F8FAFC] p-3 text-xs">
                                    <p class="font-bold text-slate-900">{{ $fld['label'] }}</p>
                                    @if($meta && $meta['valid_file'])
                                        <p class="mt-1 truncate font-medium text-[#0069A8]" title="{{ $meta['name'] }}">{{ $meta['name'] }}</p>
                                        <p class="mt-0.5 text-[11px] tabular-nums text-[#475569]">{{ $formatSize($meta['size']) }}</p>
                                        <div class="mt-2.5 flex flex-wrap gap-1.5">
                                            @if($meta['preview_url'])
                                                <a href="{{ $meta['preview_url'] }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-bold text-slate-700 hover:border-[#0069A8] hover:text-[#0069A8]">
                                                    <i data-lucide="eye" class="h-3 w-3" aria-hidden="true"></i>
                                                    Lihat
                                                </a>
                                            @endif
                                            @if($meta['download_url'])
                                                <a href="{{ $meta['download_url'] }}" class="inline-flex items-center gap-1 rounded-lg border border-[#BAE6FD] bg-[#F0F9FF] px-2.5 py-1 text-[11px] font-bold text-[#0069A8] hover:bg-sky-100">
                                                    <i data-lucide="download" class="h-3 w-3" aria-hidden="true"></i>
                                                    Unduh
                                                </a>
                                            @endif
                                        </div>
                                    @elseif($meta && $meta['exists'] && $meta['size'] === 0)
                                        <p class="mt-1.5 font-semibold text-[#B91C1C]">Berkas tercatat 0 byte (korup). Unggah ulang bukti melalui formulir di samping.</p>
                                    @else
                                        <p class="mt-1.5 text-[#475569]">Belum ada berkas bukti yang tersimpan. Anda dapat mengunggahnya di kolom sebelah kanan.</p>
                                    @endif
                                </div>
                            @endif
                        @endforeach
                    </div>
                </section>
            @endif

            <!-- Audit Note -->
            <section class="wd-bento p-5 text-xs leading-relaxed text-[#475569]">
                <p class="text-[10px] font-bold tracking-wider text-[#182654] uppercase">Riwayat Perubahan</p>
                <p class="mt-1.5">Perbaikan ini memperbarui nilai tervalidasi dan lampiran bukti, sementara data asli pengisian tetap tersimpan.</p>
            </section>
        </aside>

        <!-- Right Full-Page Editor -->
        <section class="wd-bento overflow-hidden">
            <form x-ref="fixForm"
                  action="{{ route('panel.data.submissions.update', $submission->code) }}"
                  method="POST"
                  enctype="multipart/form-data"
                  @submit="handleSubmit($event)"
                  novalidate>
                @csrf
                @method('PUT')

                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-[#F8FAFC] px-6 py-4">
                    <div>
                        <h2 class="text-sm font-extrabold text-slate-900">Seluruh Kolom Isian Formulir</h2>
                        <p class="text-xs text-[#475569]">Nilai pilihan dan lampiran dimuat langsung dari basis data.</p>
                    </div>
                    <button type="button"
                            @click="normalizeAll($refs.fixForm)"
                            class="inline-flex min-h-[38px] items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-1.5 text-xs font-bold text-slate-700 transition hover:border-[#0069A8] hover:text-[#0069A8]">
                        <i data-lucide="sparkles" class="h-3.5 w-3.5 text-[#0069A8]" aria-hidden="true"></i>
                        Seragamkan spasi, angka &amp; pilihan
                    </button>
                </div>

                <div class="space-y-5 p-6">
                    <div x-show="normalizeNotice !== ''" x-cloak class="rounded-xl border border-[#BAE6FD] bg-[#F0F9FF] px-4 py-2.5 text-xs font-semibold text-[#0069A8]" x-text="normalizeNotice"></div>
                    <div x-show="submitError !== ''" x-cloak class="rounded-xl border border-red-200 bg-[#FEF2F2] px-4 py-2.5 text-xs font-bold text-[#B91C1C]" x-text="submitError"></div>

                    @if($errors->any())
                        <div class="rounded-xl border border-red-200 bg-[#FEF2F2] px-4 py-3 text-xs text-[#B91C1C]">
                            <p class="font-bold">Periksa kembali kolom berikut:</p>
                            <ul class="mt-1 list-inside list-disc space-y-0.5">
                                @foreach($errors->all() as $errMsg)
                                    <li>{{ $errMsg }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @foreach($fields as $fld)
                        @php
                            $fid = (string) ($fld['id'] ?? '');
                            $type = (string) ($fld['type'] ?? 'text');
                            $req = (bool) ($fld['required'] ?? false);
                            $cfg = is_array($fld['config'] ?? null) ? $fld['config'] : [];
                            $options = array_values(array_map('strval', $fld['options'] ?? []));
                            $currentVal = old('values.'.$fid, $values[$fid] ?? '');
                            $currentStr = is_array($currentVal) ? implode(', ', $currentVal) : (string) $currentVal;
                            $rawVal = (string) ($rawValues[$fid] ?? '');
                            $fieldErr = $errors->first($fid);
                        @endphp

                        <div class="rounded-2xl border p-4 transition {{ $fieldErr ? 'border-red-200 bg-[#FEF2F2]/45' : 'border-slate-200/80 bg-white' }}">
                            <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                                <label for="fix_fld_{{ $fid }}" class="text-sm font-bold text-slate-900">
                                    {{ $fld['label'] }}
                                    @if($req)
                                        <span class="ml-0.5 text-[#B91C1C]">*</span>
                                    @endif
                                </label>
                                <span class="rounded bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-[#475569]">{{ $type }}</span>
                            </div>

                            @if($type === 'select' || $type === 'radio')
                                @php $allowOther = $type === 'radio' && (bool) ($cfg['allow_other'] ?? false); @endphp
                                <select id="fix_fld_{{ $fid }}"
                                        name="values[{{ $fid }}]"
                                        class="min-h-[44px] w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-[#0069A8] focus:ring-4 focus:ring-sky-100">
                                    <option value="">Pilih...</option>
                                    @if($currentStr !== '' && ! in_array($currentStr, $options, true))
                                        <option value="{{ $currentStr }}" selected>{{ $currentStr }}{{ $allowOther ? ' (lainnya)' : ' (tidak valid)' }}</option>
                                    @endif
                                    @foreach($options as $opt)
                                        <option value="{{ $opt }}" {{ $currentStr === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                    @endforeach
                                </select>
                                @if($allowOther)
                                    <div class="mt-2">
                                        <input type="text"
                                               name="values_other[{{ $fid }}]"
                                               value="{{ old('values_other.'.$fid, (! in_array($currentStr, $options, true) ? $currentStr : '')) }}"
                                               placeholder="Isi jika memilih opsi Lainnya..."
                                               class="w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-3 py-2 text-xs text-slate-800 outline-none focus:border-[#0069A8] focus:bg-white" />
                                    </div>
                                @endif
                            @elseif($type === 'checkbox')
                                @php
                                    $checkedArr = is_array($currentVal) ? $currentVal : array_values(array_filter(array_map('trim', explode(',', $currentStr))));
                                    $unknownOther = collect($checkedArr)->first(fn ($v) => ! in_array($v, $options, true));
                                    $allowOther = (bool) ($cfg['allow_other'] ?? false);
                                @endphp
                                <div class="space-y-2">
                                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                        @foreach($options as $opt)
                                            <label class="flex min-h-[42px] cursor-pointer items-center gap-2.5 rounded-xl border border-slate-200 bg-[#F8FAFC] px-3 py-2 text-sm text-slate-800 hover:border-[#0069A8]">
                                                <input type="checkbox"
                                                       name="values[{{ $fid }}][]"
                                                       value="{{ $opt }}"
                                                       {{ in_array($opt, $checkedArr, true) ? 'checked' : '' }}
                                                       class="h-4 w-4 accent-[#0069A8]" />
                                                <span class="font-medium">{{ $opt }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    @if($allowOther)
                                        <label class="flex items-center gap-2 rounded-xl border border-slate-200 bg-[#F8FAFC] px-3 py-2 text-xs text-slate-700">
                                            <input type="checkbox"
                                                   name="values[{{ $fid }}][]"
                                                   value="__other__"
                                                   {{ $unknownOther ? 'checked' : '' }}
                                                   class="h-4 w-4 accent-[#0069A8]" />
                                            <span class="font-semibold">Lainnya:</span>
                                            <input type="text"
                                                   name="values_other[{{ $fid }}]"
                                                   value="{{ old('values_other.'.$fid, $unknownOther ?? '') }}"
                                                   placeholder="Opsi lainnya..."
                                                   class="flex-1 bg-transparent text-xs outline-none" />
                                        </label>
                                    @endif
                                </div>
                            @elseif($type === 'textarea')
                                <textarea id="fix_fld_{{ $fid }}"
                                          name="values[{{ $fid }}]"
                                          rows="4"
                                          class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-[#0069A8] focus:ring-4 focus:ring-sky-100">{{ $currentStr }}</textarea>
                            @elseif($type === 'linear_scale')
                                @php
                                    $sMin = (int) ($cfg['scale_min'] ?? 1);
                                    $sMax = max($sMin + 1, (int) ($cfg['scale_max'] ?? 5));
                                @endphp
                                <div class="flex flex-wrap items-center gap-2 rounded-xl border border-slate-200 bg-[#F8FAFC] p-3">
                                    @for($n = $sMin; $n <= $sMax; $n++)
                                        <label class="flex min-w-[42px] cursor-pointer items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-800 hover:border-[#0069A8]">
                                            <input type="radio"
                                                   name="values[{{ $fid }}]"
                                                   value="{{ $n }}"
                                                   {{ (string) $currentStr === (string) $n ? 'checked' : '' }}
                                                   class="h-3.5 w-3.5 accent-[#0069A8]" />
                                            <span>{{ $n }}</span>
                                        </label>
                                    @endfor
                                </div>
                            @elseif($type === 'rating')
                                @php
                                    $rMax = max(3, min(10, (int) ($cfg['rating_max'] ?? 5)));
                                @endphp
                                <div class="flex flex-wrap items-center gap-2">
                                    @for($n = 1; $n <= $rMax; $n++)
                                        <label class="flex cursor-pointer items-center gap-1.5 rounded-xl border border-slate-200 bg-[#F8FAFC] px-3 py-1.5 text-xs font-bold text-slate-800 hover:border-[#0069A8]">
                                            <input type="radio"
                                                   name="values[{{ $fid }}]"
                                                   value="{{ $n }}"
                                                   {{ (string) $currentStr === (string) $n ? 'checked' : '' }}
                                                   class="h-3.5 w-3.5 accent-[#0069A8]" />
                                            <span>★ {{ $n }}</span>
                                        </label>
                                    @endfor
                                </div>
                            @elseif($type === 'mc_grid' || $type === 'checkbox_grid')
                                @php
                                    $gridRows = array_values(array_filter(array_map('strval', $cfg['grid_rows'] ?? [])));
                                    $gridCols = array_values(array_filter(array_map('strval', $cfg['grid_cols'] ?? [])));
                                    $parsedGrid = is_array($currentVal) ? $currentVal : $qualitySvc->parseGridValue($currentStr);
                                @endphp
                                <div class="overflow-x-auto rounded-xl border border-slate-200">
                                    <table class="wd-hairline-table min-w-[420px] text-xs">
                                        <thead>
                                            <tr>
                                                <th class="p-2.5 text-left font-bold text-slate-700">Aspek / Baris</th>
                                                @foreach($gridCols as $colLbl)
                                                    <th class="p-2.5 text-center font-bold text-slate-700">{{ $colLbl }}</th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($gridRows as $rIdx => $rowLbl)
                                                @php $rowPicked = $parsedGrid[$rowLbl] ?? ($parsedGrid[$rIdx] ?? []); @endphp
                                                <tr>
                                                    <td class="p-2.5 font-semibold text-slate-800">{{ $rowLbl }}</td>
                                                    @foreach($gridCols as $colLbl)
                                                        <td class="p-2.5 text-center">
                                                            @if($type === 'mc_grid')
                                                                <input type="radio"
                                                                       name="values[{{ $fid }}][{{ $rIdx }}]"
                                                                       value="{{ $colLbl }}"
                                                                       {{ (is_array($rowPicked) ? in_array($colLbl, $rowPicked, true) : $rowPicked === $colLbl) ? 'checked' : '' }}
                                                                       class="h-4 w-4 accent-[#0069A8]" />
                                                            @else
                                                                <input type="checkbox"
                                                                       name="values[{{ $fid }}][{{ $rIdx }}][]"
                                                                       value="{{ $colLbl }}"
                                                                       {{ (is_array($rowPicked) && in_array($colLbl, $rowPicked, true)) ? 'checked' : '' }}
                                                                       class="h-4 w-4 accent-[#0069A8]" />
                                                            @endif
                                                        </td>
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @elseif($type === 'file')
                                @php
                                    $meta = $attachmentMeta[$fid] ?? null;
                                    $allowedExts = $qualitySvc->allowedFileExtensions($fld);
                                    $acceptAttr = $allowedExts !== null ? implode(',', array_map(fn ($e) => '.'.$e, $allowedExts)) : '';
                                    $rawMaxMb = array_key_exists('file_max_mb', $cfg) ? (int) $cfg['file_max_mb'] : 0;
                                    $maxMb = $rawMaxMb <= 0 ? 0 : min(1024, $rawMaxMb);
                                    $maxCount = max(1, min(20, (int) ($cfg['file_max_count'] ?? 1)));
                                    $typeHint = $allowedExts !== null ? strtoupper(implode(', ', $allowedExts)) : 'Semua jenis berkas';
                                    $sizeHint = $maxMb === 0 ? 'tanpa batas ukuran' : "maks {$maxMb} MB";
                                @endphp
                                <input type="hidden" name="uploaded_tokens[{{ $fid }}]" :value="uploadedTokens['{{ $fid }}'] || ''">

                                <div class="space-y-3">
                                    @if($meta && $meta['valid_file'])
                                        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-[#BAE6FD] bg-[#F0F9FF] px-4 py-3 text-xs">
                                            <div class="min-w-0">
                                                <p class="font-bold text-[#182654]">Berkas terlampir saat ini</p>
                                                <p class="mt-0.5 truncate font-semibold text-[#0069A8]">{{ $meta['name'] }} ({{ $formatSize($meta['size']) }})</p>
                                            </div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                @if($meta['preview_url'])
                                                    <a href="{{ $meta['preview_url'] }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 font-bold text-slate-700 hover:border-[#0069A8] hover:text-[#0069A8]">Lihat</a>
                                                @endif
                                                @if($meta['download_url'])
                                                    <a href="{{ $meta['download_url'] }}" class="inline-flex items-center gap-1 rounded-lg bg-[#0069A8] px-2.5 py-1.5 font-bold text-white hover:bg-[#182654]">Unduh</a>
                                                @endif
                                            </div>
                                        </div>
                                        @if(! $req)
                                            <label class="inline-flex items-center gap-2 text-xs font-medium text-slate-600">
                                                <input type="checkbox" name="remove_files[{{ $fid }}]" value="1" x-model="removeExisting['{{ $fid }}']" class="h-3.5 w-3.5 accent-[#B91C1C]" />
                                                <span>Hapus lampiran ini</span>
                                            </label>
                                        @endif
                                    @elseif($meta && $meta['exists'] && $meta['size'] === 0)
                                        <div class="rounded-xl border border-amber-200 bg-[#FFFBEB] px-4 py-2.5 text-xs font-semibold text-[#92400E]">
                                            Berkas di server berukuran 0 byte. Unggah ulang berkas bukti baru di bawah ini.
                                        </div>
                                    @endif

                                    <div class="flex flex-wrap items-center gap-2 rounded-xl border border-dashed px-4 py-3 text-sm transition"
                                         :class="uploadedTokens['{{ $fid }}'] ? 'border-[#047857] bg-[#ECFDF5]/70' : 'border-slate-300 bg-[#F8FAFC] hover:border-[#0069A8] hover:bg-[#F0F9FF]'">
                                        <label for="fix_file_{{ $fid }}" class="flex min-w-0 flex-1 cursor-pointer items-center gap-3">
                                            <i data-lucide="upload-cloud" class="h-4 w-4 shrink-0" :class="uploadedTokens['{{ $fid }}'] ? 'text-[#047857]' : 'text-[#0069A8]'" aria-hidden="true"></i>
                                            <span class="min-w-0 flex-1 truncate font-medium"
                                                  :class="uploadedTokens['{{ $fid }}'] ? 'text-[#047857]' : 'text-slate-700'"
                                                  x-text="fileLabels['{{ $fid }}'] || @js(($meta && $meta['valid_file'] ? 'Pilih berkas pengganti' : 'Pilih atau seret berkas bukti') . ' (' . $typeHint . ' · ' . $sizeHint . ')...')"></span>
                                        </label>
                                        <template x-if="uploadedTokens['{{ $fid }}']">
                                            <div class="flex shrink-0 items-center gap-2">
                                                <span class="rounded-md bg-[#047857] px-2 py-0.5 text-[10px] font-bold text-white uppercase">Siap disimpan</span>
                                                <button type="button" @click="clearNewUpload('{{ $fid }}')" class="rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-bold text-[#B91C1C] hover:bg-red-50">
                                                    Batalkan
                                                </button>
                                            </div>
                                        </template>
                                        <input type="file"
                                               id="fix_file_{{ $fid }}"
                                               name="{{ $maxCount > 1 ? 'files['.$fid.'][]' : 'files['.$fid.']' }}"
                                               @if($acceptAttr !== '') accept="{{ $acceptAttr }}" @endif
                                               @if($maxCount > 1) multiple @endif
                                               @change="handleFileSelect('{{ $fid }}', $event, {{ $maxCount }}, {{ $maxMb }})"
                                               class="sr-only" />
                                    </div>

                                    <div x-show="uploadingFields['{{ $fid }}']" x-cloak class="space-y-1">
                                        <div class="flex items-center justify-between text-[11px] font-bold text-[#0069A8]">
                                            <span>Mengunggah berkas bukti secara bertahap...</span>
                                            <span class="tabular-nums" x-text="`${uploadProgress['{{ $fid }}'] || 0}%`"></span>
                                        </div>
                                        <div class="h-1.5 overflow-hidden rounded-full bg-slate-200">
                                            <div class="h-full rounded-full bg-[#0069A8] transition-all" :style="`width: ${uploadProgress['{{ $fid }}'] || 0}%`"></div>
                                        </div>
                                    </div>

                                    <div x-show="uploadErrors['{{ $fid }}']" x-cloak class="flex flex-wrap items-center gap-2 text-xs font-semibold text-[#B91C1C]">
                                        <span x-text="uploadErrors['{{ $fid }}']"></span>
                                        <button x-show="uploadReloadNeeded['{{ $fid }}']"
                                                type="button"
                                                @click="window.location.reload()"
                                                class="rounded-lg border border-red-200 bg-white px-2.5 py-1 text-[11px] font-bold text-[#0069A8] hover:bg-[#F0F9FF]">
                                            Muat ulang halaman
                                        </button>
                                    </div>
                                </div>
                            @elseif($type === 'date')
                                <input id="fix_fld_{{ $fid }}"
                                       type="{{ ! empty($cfg['include_time']) ? 'datetime-local' : 'date' }}"
                                       name="values[{{ $fid }}]"
                                       value="{{ $currentStr }}"
                                       class="min-h-[44px] w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-[#0069A8] focus:ring-4 focus:ring-sky-100" />
                            @elseif($type === 'time')
                                <input id="fix_fld_{{ $fid }}"
                                       type="{{ ($cfg['time_mode'] ?? 'time') === 'duration' ? 'text' : 'time' }}"
                                       name="values[{{ $fid }}]"
                                       value="{{ $currentStr }}"
                                       class="min-h-[44px] w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-[#0069A8] focus:ring-4 focus:ring-sky-100" />
                            @else
                                <input id="fix_fld_{{ $fid }}"
                                       type="{{ $type === 'number' ? 'number' : 'text' }}"
                                       name="values[{{ $fid }}]"
                                       value="{{ $currentStr }}"
                                       {{ $type === 'number' ? 'step="any"' : '' }}
                                       class="min-h-[44px] w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-[#0069A8] focus:ring-4 focus:ring-sky-100" />
                            @endif

                            @if($rawVal !== '' && $rawVal !== $currentStr)
                                <p class="mt-1.5 text-[11px] text-[#475569]">Nilai mentah asli: <code class="rounded bg-slate-100 px-1.5 py-0.5 text-slate-700">{{ $rawVal }}</code></p>
                            @endif

                            @if($fieldErr)
                                <p class="mt-1.5 text-xs font-semibold text-[#B91C1C]">{{ $fieldErr }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 bg-[#F8FAFC] px-6 py-4">
                    <a href="{{ route('panel.preprocessing.show', $form->code) }}" class="inline-flex min-h-[42px] items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Batal
                    </a>
                    <button type="submit"
                            :disabled="isAnyUploading()"
                            class="inline-flex min-h-[42px] items-center justify-center gap-2 rounded-xl bg-[#0069A8] px-5 py-2 text-sm font-bold text-white transition hover:bg-[#182654] disabled:cursor-not-allowed disabled:opacity-55">
                        <i data-lucide="save" class="h-4 w-4" aria-hidden="true"></i>
                        <span x-text="isAnyUploading() ? 'Menunggu unggahan selesai...' : 'Simpan perbaikan'">Simpan perbaikan</span>
                    </button>
                </div>
            </form>
        </section>
    </div>
</div>
@endsection
