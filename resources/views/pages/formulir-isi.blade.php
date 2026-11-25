@extends('layouts.app')

@section('title', $dataForm->title . ' - Formulir Satu Data UNUSA')

@section('content')
@php
    $user = auth()->user();
    $settings = $settings ?? $dataForm->settings;
    $theme = is_array($settings['theme'] ?? null) ? $settings['theme'] : [];
    $palette = (string) ($theme['palette'] ?? 'cerulean');
    $bgTone = (string) ($theme['bg_tone'] ?? 'soft');
    $headerPreset = (string) ($theme['header_preset'] ?? 'cerulean-grid');
    $headerImage = (string) ($theme['header_image'] ?? '');
    $fontFamily = (string) ($theme['font_family'] ?? 'jakarta');
    $fontStyle = match ($fontFamily) {
        'mono' => "font-family: 'Plus Jakarta Sans', var(--font-sans);",
        'serif' => 'font-family: Georgia, Cambria, "Times New Roman", serif;',
        default => '',
    };

    $items = $items ?? ($dataForm->items ?? []);
    $fields = $dataForm->fields ?? [];
    $oldValues = old('values', $editingSubmission?->values ?? []);

    $sections = [];
    $currentSection = [
        'id' => '__start__',
        'title' => $dataForm->title,
        'description' => '',
        'after_action' => 'next',
        'items' => [],
        'fieldIds' => [],
        'branchingFields' => [],
    ];
    $startedExplicit = false;

    foreach ($items as $item) {
        $type = (string) ($item['type'] ?? 'text');
        if ($type === 'section') {
            if (! $startedExplicit && empty($currentSection['items'])) {
                $currentSection['id'] = (string) ($item['id'] ?? 'sec_0');
                $currentSection['title'] = (string) ($item['label'] ?? $dataForm->title);
                $currentSection['description'] = (string) ($item['help'] ?? '');
                $currentSection['after_action'] = (string) ($item['config']['after_action'] ?? 'next');
                $startedExplicit = true;
            } else {
                $sections[] = $currentSection;
                $currentSection = [
                    'id' => (string) ($item['id'] ?? 'sec_'.count($sections)),
                    'title' => (string) ($item['label'] ?? 'Bagian '.(count($sections) + 1)),
                    'description' => (string) ($item['help'] ?? ''),
                    'after_action' => (string) ($item['config']['after_action'] ?? 'next'),
                    'items' => [],
                    'fieldIds' => [],
                    'branchingFields' => [],
                ];
                $startedExplicit = true;
            }
            continue;
        }

        $currentSection['items'][] = $item;
        if (! in_array($type, \App\Models\FormField::NON_INPUT_TYPES, true)) {
            $fid = (string) ($item['id'] ?? '');
            $currentSection['fieldIds'][] = $fid;
            if (! empty($item['config']['use_branching']) && ! empty($item['config']['branching'])) {
                $currentSection['branchingFields'][] = [
                    'id' => $fid,
                    'branching' => $item['config']['branching'],
                ];
            }
        }
    }
    $sections[] = $currentSection;

    if (! empty($settings['shuffle_questions']) && empty(old())) {
        foreach ($sections as &$sec) {
            $inputBucket = array_values(array_filter($sec['items'], fn ($it) => ! in_array($it['type'], \App\Models\FormField::NON_INPUT_TYPES, true)));
            shuffle($inputBucket);
            $iPtr = 0;
            foreach ($sec['items'] as &$it) {
                if (! in_array($it['type'], \App\Models\FormField::NON_INPUT_TYPES, true)) {
                    $it = $inputBucket[$iPtr++];
                }
            }
            unset($it);
        }
        unset($sec);
    }

    $oldUploadedTokens = [];
    $oldUploadedLabels = [];
    $rawOldTokens = old('uploaded_tokens', []);
    if (is_array($rawOldTokens)) {
        foreach ($rawOldTokens as $oFid => $oTokRaw) {
            $oTokStr = trim((string) $oTokRaw);
            if ($oTokStr === '' || $oTokStr === '[]') {
                continue;
            }
            $decodedList = json_decode($oTokStr, true);
            $tokArr = is_array($decodedList) ? $decodedList : [$oTokStr];
            $labels = [];
            $validTok = true;
            foreach ($tokArr as $tokItem) {
                try {
                    $meta = json_decode(\Illuminate\Support\Facades\Crypt::decryptString((string) $tokItem), true);
                } catch (\Throwable) {
                    $meta = null;
                }
                if (! is_array($meta) || empty($meta['name']) || (int) ($meta['size'] ?? 0) <= 0) {
                    $validTok = false;
                    break;
                }
                $labels[] = (string) $meta['name'];
            }
            if ($validTok && ! empty($labels)) {
                $oldUploadedTokens[(string) $oFid] = $oTokStr;
                $oldUploadedLabels[(string) $oFid] = implode(', ', $labels);
            }
        }
    }

    $runnerPayload = [
        'sections' => array_map(fn ($s) => [
            'id' => $s['id'],
            'title' => $s['title'],
            'after_action' => $s['after_action'],
            'fieldIds' => $s['fieldIds'],
            'branchingFields' => $s['branchingFields'],
        ], $sections),
        'fields' => $fields,
        'settings' => $settings,
        'uploadChunkUrl' => route('panel.isi.upload-chunk', $dataForm->code),
        'canSubmit' => $canSubmit,
        'oldValues' => $oldValues,
        'oldUploadedTokens' => $oldUploadedTokens,
        'oldUploadedLabels' => $oldUploadedLabels,
        'errors' => $errors->getMessages(),
    ];

    $questionCounter = 0;
    $youtubeEmbed = static function (?string $url): string {
        $str = trim((string) $url);
        if ($str === '') {
            return '';
        }
        if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([A-Za-z0-9_-]{6,})/', $str, $m) === 1) {
            return 'https://www.youtube-nocookie.com/embed/'.$m[1];
        }

        return '';
    };
@endphp

<script type="application/json" id="gform-runner-data">@json($runnerPayload)</script>

<main data-gf-palette="{{ $palette }}"
      data-gf-bg="{{ $bgTone }}"
      style="{{ $fontStyle }}"
      class="gf-studio-canvas min-h-screen py-7 sm:py-10">
    <div class="mx-auto max-w-[1140px] px-4 sm:px-6" x-data="publicGFormRunner()">

        <!-- Breadcrumb -->
        <nav aria-label="Breadcrumb" class="mb-4 flex items-center justify-between gap-4 text-sm text-[#475569]">
            <div>
                <a href="{{ route('pengisian-data') }}" class="font-semibold hover:text-[#0069A8]">Portal Pengisian Data</a>
                <span class="mx-1.5">/</span>
                <span class="font-semibold tabular-nums text-[#0069A8]">{{ $dataForm->code }}</span>
            </div>
            @if($user && ! $user->isWali())
                <a href="{{ route('panel.riwayat') }}" class="text-xs font-bold text-[#0069A8] hover:underline">Riwayat pengisian saya</a>
            @endif
        </nav>

        @if($user && $user->isWali())
            <div role="alert" class="rounded-2xl border border-amber-200 bg-amber-50/90 p-6 sm:p-8">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex items-start gap-3.5">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-amber-100 text-amber-800">
                            <i data-lucide="alert-triangle" class="h-5 w-5" aria-hidden="true"></i>
                        </span>
                        <div>
                            <h1 class="text-lg font-extrabold tracking-tight text-slate-900">{{ $dataForm->judul }}</h1>
                            <p class="mt-1.5 text-sm font-semibold text-amber-900">Wali Data tidak diperkenankan mengisi data.</p>
                        </div>
                    </div>
                    <a href="{{ route('pengisian-data') }}" class="inline-flex min-h-[44px] shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-xs font-bold text-slate-800 hover:border-[#0069A8] hover:text-[#0069A8]">
                        Kembali
                    </a>
                </div>
            </div>
        @else
            <!-- UNUSA Architectural Hero Header Band -->
        <section class="relative isolate overflow-hidden rounded-3xl border border-slate-200/80 text-white shadow-sm">
            @if($headerImage !== '')
                <img src="{{ $headerImage }}" alt="{{ $dataForm->title }}" class="absolute inset-0 -z-20 h-full w-full object-cover" />
                <div class="absolute inset-0 -z-10 bg-linear-to-r from-[#09122C]/92 via-[#0F1C44]/85 to-[#182654]/75"></div>
            @else
                <div class="gf-banner-{{ $headerPreset }} absolute inset-0 -z-10" aria-hidden="true"></div>
            @endif

            <div class="p-6 sm:p-9">
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-[11px] font-bold text-[#74D4FF]">
                        <span class="h-1.5 w-1.5 rounded-full bg-[#38BDF8]"></span>
                        {{ $dataForm->category?->nama_kategori ?? 'Umum' }}
                    </span>
                    <span class="rounded-full bg-white/15 px-3 py-1 text-[11px] font-semibold tabular-nums text-white">{{ $dataForm->code }}</span>
                    <span class="rounded-full bg-white/10 px-3 py-1 text-[11px] font-medium text-slate-200">{{ $dataForm->tahun }} · Semester {{ $dataForm->periode }} · Sasaran: {{ $dataForm->target }}</span>
                    @if($dataForm->batasi_satu_respon)
                        <span class="rounded-full bg-amber-400/20 px-3 py-1 text-[11px] font-bold text-amber-200">Maks 1x per akun</span>
                    @endif
                    @if(! empty($settings['is_quiz']))
                        <span class="rounded-full bg-emerald-400/20 px-3 py-1 text-[11px] font-bold text-emerald-200">Mode Kuis</span>
                    @endif
                </div>

                <h1 class="mt-4 max-w-3xl font-extrabold tracking-tight text-white" style="font-size: {{ (int) ($theme['header_size'] ?? 26) }}px; line-height: 1.2;">
                    {{ $dataForm->title }}
                </h1>

                @if($dataForm->description)
                    <p class="mt-2.5 max-w-3xl whitespace-pre-line leading-relaxed text-slate-200" style="font-size: {{ (int) ($theme['text_size'] ?? 14) }}px;">{{ $dataForm->description }}</p>
                @endif

                <div class="mt-6 flex flex-wrap items-center gap-5 border-t border-white/15 pt-4 text-xs text-slate-200">
                    <span class="inline-flex items-center gap-1.5 font-semibold {{ $dataForm->isUrgent() ? 'text-amber-300' : 'text-[#74D4FF]' }}">
                        <i data-lucide="calendar-clock" class="h-4 w-4" aria-hidden="true"></i>
                        Tenggat {{ $dataForm->deadlineFormatted() }} ({{ $dataForm->timeLeftLabel() }})
                    </span>
                    <span class="tabular-nums">{{ count($fields) }} pertanyaan</span>
                    @if(count($sections) > 1)
                        <span class="tabular-nums">{{ count($sections) }} bagian</span>
                    @endif
                    @if($alreadySubmittedCount > 0)
                        <span class="inline-flex items-center gap-1 font-semibold text-emerald-300">
                            <i data-lucide="check-circle-2" class="h-4 w-4" aria-hidden="true"></i>
                            Anda sudah mengirim {{ $alreadySubmittedCount }}x
                        </span>
                    @endif
                </div>
            </div>
        </section>

        @if(session('success'))
            <div class="wd-bento mt-5 border-l-4 border-l-[#047857] bg-[#ECFDF5] p-6 text-sm text-[#047857]">
                <p class="text-base font-extrabold">{{ session('success') }}</p>
                @if(session('custom_confirmation'))
                    <p class="mt-1.5 text-sm text-slate-800">{{ session('custom_confirmation') }}</p>
                @endif

                @if(! $dataForm->batasi_satu_respon && ! empty($settings['show_another_response_link']))
                    <a href="{{ route('formulir.show', $dataForm->code) }}" class="mt-3 inline-flex text-xs font-bold text-[#0069A8] underline">
                        Kirim jawaban lain
                    </a>
                @endif
            </div>
        @endif

        @if(session('quiz_result'))
            @php $qr = session('quiz_result'); @endphp
            <section class="wd-bento mt-5 border-t-4 border-t-[#047857] p-6">
                <p class="text-[11px] font-bold tracking-wider text-[#047857] uppercase">Hasil Penilaian Kuis Otomatis</p>
                <h2 class="mt-1 text-xl font-extrabold text-slate-900">
                    Skor Anda: <span class="tabular-nums text-[#047857]">{{ $qr['earned'] ?? 0 }} / {{ $qr['possible'] ?? 0 }}</span> ({{ $qr['pct'] ?? 0 }}%)
                </h2>

                @if(! empty($qr['breakdown']))
                    <div class="mt-4 grid grid-cols-1 gap-2.5 text-xs md:grid-cols-2">
                        @foreach($qr['breakdown'] as $b)
                            <div class="rounded-xl border p-3.5 {{ $b['is_correct'] ? 'border-emerald-200 bg-[#ECFDF5]/60' : 'border-red-200 bg-[#FEF2F2]/70' }}">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="font-bold text-slate-900">{{ $b['label'] }}</p>
                                    @if(! empty($qr['show_points']))
                                        <span class="shrink-0 font-bold tabular-nums {{ $b['is_correct'] ? 'text-[#047857]' : 'text-[#B91C1C]' }}">
                                            {{ $b['points_earned'] }}/{{ $b['points_possible'] }} poin
                                        </span>
                                    @endif
                                </div>
                                <p class="mt-1 text-slate-700">Jawaban Anda: <strong>{{ $b['user_answer'] }}</strong></p>
                                @if(! $b['is_correct'] && ! empty($qr['show_correct']) && $b['correct_answer'] !== '')
                                    <p class="mt-0.5 font-semibold text-[#047857]">Kunci jawaban: {{ $b['correct_answer'] }}</p>
                                @endif
                                @if($b['feedback'] !== '')
                                    <p class="mt-1 text-slate-600 italic">{{ $b['feedback'] }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif

        @if(session('error'))
            <div class="mt-5 rounded-2xl border border-red-200 bg-[#FEF2F2] p-4 text-sm font-semibold text-[#B91C1C]">
                {{ session('error') }}
            </div>
        @endif

        <!-- 2-Column UNUSA Portal Workspace -->
        <div class="mt-6 grid grid-cols-1 items-start gap-6 lg:grid-cols-[280px_minmax(0,1fr)]">

            <!-- Left Sticky Telemetry & Stepper Sidebar -->
            <aside class="space-y-4 lg:sticky lg:top-24">
                @if($settings['collect_identity'] ?? true)
                    <section class="wd-bento overflow-hidden">
                        <div class="bg-[#182654] px-4 py-3 text-white">
                            <p class="text-[10px] font-bold tracking-wider text-[#74D4FF] uppercase">Identitas Responden</p>
                        </div>
                        <div class="p-4 text-xs">
                            @if($user)
                                <div class="flex items-center gap-3">
                                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-[#F0F9FF] text-xs font-extrabold tabular-nums text-[#0069A8]">
                                        {{ $user->initials() }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate font-extrabold text-slate-900">{{ $user->name }}</p>
                                        <p class="text-[11px] tabular-nums text-[#475569]">{{ $user->nim }}</p>
                                    </div>
                                </div>
                                <div class="mt-3 flex flex-wrap gap-1.5">
                                    <span class="rounded-md bg-[#F0F9FF] px-2 py-0.5 font-bold text-[#0069A8]">
                                        {{ $user->roleLabel() }}{{ $user->jenis ? ' · '.$user->jenis : '' }}
                                    </span>
                                    @if($editingSubmission)
                                        <span class="rounded-md bg-[#FFFBEB] px-2 py-0.5 font-bold text-[#92400E]">Edit {{ $editingSubmission->code }}</span>
                                    @endif
                                </div>
                            @else
                                <p class="text-[#475569]">Masuk menggunakan akun UNUSA agar identitas pengirim tercatat otomatis.</p>
                                <a href="{{ route('login') }}" class="mt-3 inline-flex w-full items-center justify-center rounded-xl bg-[#0069A8] py-2 font-bold text-white hover:bg-[#182654]">
                                    Masuk untuk mengisi
                                </a>
                            @endif
                        </div>
                    </section>
                @endif

                @if(count($sections) > 1)
                    <section class="wd-bento p-4 text-xs">
                        <div class="flex items-center justify-between">
                            <p class="text-[10px] font-bold tracking-wider text-[#0069A8] uppercase">Progres Bagian</p>
                            <span class="font-bold tabular-nums text-slate-700" x-text="`${currentSectionIdx + 1}/${totalSections()}`"></span>
                        </div>
                        @if(! empty($settings['show_progress_bar']))
                            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-200">
                                <div class="h-full rounded-full transition-all duration-300" style="background-color: var(--gf-primary);" :style="`width: ${progressPct()}%`"></div>
                            </div>
                        @endif
                        <ol class="mt-3 space-y-1.5">
                            @foreach($sections as $sIdx => $sec)
                                <li class="flex items-center gap-2 rounded-lg px-2.5 py-1.5 transition"
                                    :class="currentSectionIdx === {{ $sIdx }} ? 'bg-[#F0F9FF] font-bold text-[#0069A8]' : 'text-slate-600'">
                                    <span class="grid h-5 w-5 shrink-0 place-items-center rounded-md text-[10px] font-extrabold tabular-nums"
                                          :class="currentSectionIdx === {{ $sIdx }} ? 'bg-[#0069A8] text-white' : 'bg-slate-100 text-slate-600'">
                                        {{ $sIdx + 1 }}
                                    </span>
                                    <span class="truncate">{{ $sec['title'] }}</span>
                                </li>
                            @endforeach
                        </ol>
                    </section>
                @endif

            </aside>

            <!-- Right Editorial Document Area -->
            <div class="min-w-0">
                @if(! $canSubmit)
                    <section class="wd-bento border-amber-200 bg-[#FFFBEB] p-6 text-slate-800">
                        <h2 class="text-base font-bold text-[#92400E]">Formulir tidak dapat dikirim saat ini</h2>
                        <p class="mt-1 text-sm text-slate-700">{{ $closedReason }}</p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            @guest
                                <a href="{{ route('login') }}" class="inline-flex min-h-[42px] items-center rounded-xl bg-[#0069A8] px-4 py-2 text-sm font-semibold text-white hover:bg-[#182654]">Masuk sekarang</a>
                            @else
                                @if($user->isWali())
                                    <a href="{{ route('panel.preprocessing.show', $dataForm->code) }}" class="inline-flex min-h-[42px] items-center rounded-xl bg-[#0069A8] px-4 py-2 text-sm font-semibold text-white hover:bg-[#182654]">Buka Pre-processing Data</a>
                                @endif
                            @endguest
                            <a href="{{ route('pengisian-data') }}" class="inline-flex min-h-[42px] items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Daftar formulir lainnya</a>
                        </div>
                    </section>
                @else
                    <form x-ref="runnerForm"
                          action="{{ route('panel.isi.submit', $dataForm->code) }}"
                          method="POST"
                          enctype="multipart/form-data"
                          novalidate
                          @submit="handleSubmit($event)"
                          class="space-y-4">
                        @csrf
                        @if($editingSubmission)
                            <input type="hidden" name="editing_submission_code" value="{{ $editingSubmission->code }}">
                        @endif

                        <!-- Client-Side Section Error Alert -->
                        <div x-show="sectionError !== ''" x-cloak class="rounded-xl border border-red-200 bg-[#FEF2F2] px-4 py-3 text-xs font-bold text-[#B91C1C]" x-text="sectionError"></div>

                        @foreach($sections as $sIdx => $section)
                            <div id="gf-runner-sec-{{ $sIdx }}"
                                 x-show="currentSectionIdx === {{ $sIdx }}"
                                 @if($sIdx > 0) x-cloak @endif
                                 class="wd-bento overflow-hidden">

                                @if(count($sections) > 1 && ($sIdx > 0 || $section['id'] !== '__start__'))
                                    <div class="border-b border-slate-200 bg-[#F8FAFC] px-6 py-4">
                                        <p class="text-[10px] font-bold tracking-wider text-[#0069A8] uppercase">Bagian {{ $sIdx + 1 }} dari {{ count($sections) }}</p>
                                        <h2 class="mt-0.5 text-lg font-extrabold text-[#182654]">{{ $section['title'] }}</h2>
                                        @if($section['description'] !== '')
                                            <p class="mt-1 whitespace-pre-line text-xs text-[#475569]">{{ $section['description'] }}</p>
                                        @endif
                                    </div>
                                @endif

                                <div class="divide-y divide-slate-100">
                                    @foreach($section['items'] as $fld)
                                        @php
                                            $fid = (string) ($fld['id'] ?? '');
                                            $type = (string) ($fld['type'] ?? 'text');
                                            $cfg = is_array($fld['config'] ?? null) ? $fld['config'] : [];
                                        @endphp

                                        @if($type === 'title_block')
                                            <div class="bg-[#F8FAFC]/60 p-6">
                                                <h3 class="text-base font-extrabold text-slate-900">{{ $fld['label'] }}</h3>
                                                @if(($fld['help'] ?? '') !== '')
                                                    <p class="mt-1.5 whitespace-pre-line text-sm text-[#475569]">{{ $fld['help'] }}</p>
                                                @endif
                                            </div>
                                        @elseif($type === 'image_block')
                                            <div class="space-y-2.5 p-6">
                                                @if(($fld['label'] ?? '') !== '')
                                                    <h3 class="text-sm font-bold text-slate-900">{{ $fld['label'] }}</h3>
                                                @endif
                                                @if(! empty($cfg['image_url']))
                                                    <div class="{{ ($cfg['image_align'] ?? 'center') === 'left' ? 'text-left' : (($cfg['image_align'] ?? 'center') === 'right' ? 'text-right' : 'text-center') }}">
                                                        <img src="{{ $cfg['image_url'] }}" alt="{{ $fld['label'] ?? 'Gambar formulir' }}" class="inline-block max-h-80 rounded-xl border border-slate-200 object-contain" />
                                                    </div>
                                                @endif
                                                @if(! empty($cfg['image_caption']))
                                                    <p class="text-xs text-[#475569]">{{ $cfg['image_caption'] }}</p>
                                                @endif
                                            </div>
                                        @elseif($type === 'video_block')
                                            @php $embed = $youtubeEmbed($cfg['video_url'] ?? ''); @endphp
                                            <div class="space-y-2.5 p-6">
                                                @if(($fld['label'] ?? '') !== '')
                                                    <h3 class="text-sm font-bold text-slate-900">{{ $fld['label'] }}</h3>
                                                @endif
                                                @if($embed !== '')
                                                    <div class="aspect-video overflow-hidden rounded-xl border border-slate-200 bg-slate-900">
                                                        <iframe src="{{ $embed }}" class="h-full w-full" allowfullscreen title="{{ $fld['label'] ?? 'Video formulir' }}"></iframe>
                                                    </div>
                                                @elseif(! empty($cfg['video_url']))
                                                    <a href="{{ $cfg['video_url'] }}" target="_blank" rel="noopener" class="text-xs font-bold text-[#0069A8] underline">{{ $cfg['video_url'] }}</a>
                                                @endif
                                                @if(! empty($cfg['video_caption']))
                                                    <p class="text-xs text-[#475569]">{{ $cfg['video_caption'] }}</p>
                                                @endif
                                            </div>
                                        @else
                                            @php
                                                $questionCounter++;
                                                $req = (bool) ($fld['required'] ?? false);
                                                $help = (string) ($fld['help'] ?? '');
                                                $options = array_values(array_map('strval', $fld['options'] ?? []));
                                                if (! empty($cfg['shuffle_options']) && empty(old())) {
                                                    shuffle($options);
                                                }
                                                $oldVal = $oldValues[$fid] ?? '';
                                                $fieldErr = $errors->first($fid);
                                                $allowOther = (bool) ($cfg['allow_other'] ?? false);
                                                $optImages = is_array($cfg['option_images'] ?? null) ? $cfg['option_images'] : [];
                                                $isOtherSelected = $type === 'radio' && is_string($oldVal) && $oldVal !== '' && ! in_array($oldVal, $options, true);
                                            @endphp
                                            <div id="gf-q-{{ $fid }}" class="p-6 transition-colors {{ $fieldErr ? 'bg-[#FEF2F2]/60' : 'hover:bg-[#F8FAFC]/45' }}">
                                                <div class="mb-3 flex flex-wrap items-start justify-between gap-2">
                                                    <div class="min-w-0 flex-1">
                                                        <label for="fld_{{ $fid }}" class="flex items-start gap-2.5 font-bold text-slate-900" style="font-size: {{ (int) ($theme['question_size'] ?? 15) }}px;">
                                                            <span class="mt-0.5 inline-flex h-6 min-w-6 shrink-0 items-center justify-center rounded-md bg-[#F0F9FF] px-1.5 text-[11px] font-extrabold tabular-nums text-[#0069A8]">{{ str_pad((string) $questionCounter, 2, '0', STR_PAD_LEFT) }}</span>
                                                            <span>
                                                                {{ $fld['label'] }}
                                                                @if($req)
                                                                    <span class="ml-0.5 text-[#B91C1C]" aria-hidden="true">*</span>
                                                                @endif
                                                            </span>
                                                        </label>
                                                        @if($help !== '')
                                                            <p class="mt-1.5 ml-8.5 whitespace-pre-line text-xs text-[#475569]">{{ $help }}</p>
                                                        @endif
                                                    </div>
                                                    @if(! empty($settings['is_quiz']) && ! empty($cfg['points']) && ! empty($settings['quiz_show_points']))
                                                        <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-bold tabular-nums text-slate-600">{{ (int) $cfg['points'] }} poin</span>
                                                    @endif
                                                </div>

                                                <div class="ml-0 sm:ml-8.5">
                                                    @if(! empty($cfg['image_url']))
                                                        <div class="mb-3 {{ ($cfg['image_align'] ?? 'center') === 'left' ? 'text-left' : (($cfg['image_align'] ?? 'center') === 'right' ? 'text-right' : 'text-center') }}">
                                                            <img src="{{ $cfg['image_url'] }}" alt="{{ $fld['label'] }}" class="inline-block max-h-64 rounded-xl border border-slate-200 object-contain" />
                                                            @if(! empty($cfg['image_caption']))
                                                                <p class="mt-1.5 text-xs text-[#475569]">{{ $cfg['image_caption'] }}</p>
                                                            @endif
                                                        </div>
                                                    @endif

                                                    @if($type === 'textarea')
                                                        <textarea
                                                            id="fld_{{ $fid }}"
                                                            name="values[{{ $fid }}]"
                                                            x-model="answers['{{ $fid }}']"
                                                            rows="4"
                                                            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-[#0069A8] focus:ring-4 focus:ring-sky-100"
                                                        >{{ is_string($oldVal) ? $oldVal : '' }}</textarea>
                                                    @elseif($type === 'select')
                                                        <select
                                                            id="fld_{{ $fid }}"
                                                            name="values[{{ $fid }}]"
                                                            x-model="answers['{{ $fid }}']"
                                                            class="min-h-[44px] w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-[#0069A8] focus:ring-4 focus:ring-sky-100"
                                                        >
                                                            <option value="">Pilih jawaban...</option>
                                                            @foreach($options as $opt)
                                                                <option value="{{ $opt }}" {{ $oldVal === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                                            @endforeach
                                                        </select>
                                                    @elseif($type === 'radio')
                                                        <div class="space-y-2" role="radiogroup" aria-label="{{ $fld['label'] }}">
                                                            @foreach($options as $opt)
                                                                <label class="sd-choice-tile flex min-h-[44px] cursor-pointer items-center gap-3 px-3.5 py-2.5 text-sm text-slate-800">
                                                                    <input type="radio"
                                                                           name="values[{{ $fid }}]"
                                                                           value="{{ $opt }}"
                                                                           x-model="answers['{{ $fid }}']"
                                                                           {{ $oldVal === $opt ? 'checked' : '' }}
                                                                           class="h-4 w-4 accent-[#0069A8]" />
                                                                    <span class="flex-1 font-medium">{{ $opt }}</span>
                                                                    @if(! empty($optImages[$opt]))
                                                                        <img src="{{ $optImages[$opt] }}" alt="{{ $opt }}" class="h-12 w-12 rounded-lg border border-slate-200 object-cover" />
                                                                    @endif
                                                                </label>
                                                            @endforeach
                                                            @if($allowOther)
                                                                <label class="sd-choice-tile flex min-h-[44px] cursor-pointer flex-wrap items-center gap-3 px-3.5 py-2.5 text-sm text-slate-800">
                                                                    <input type="radio"
                                                                           name="values[{{ $fid }}]"
                                                                           value="__other__"
                                                                           x-model="answers['{{ $fid }}']"
                                                                           {{ $isOtherSelected ? 'checked' : '' }}
                                                                           class="h-4 w-4 accent-[#0069A8]" />
                                                                    <span class="font-medium">Lainnya:</span>
                                                                    <input type="text"
                                                                           name="values_other[{{ $fid }}]"
                                                                           value="{{ $isOtherSelected ? $oldVal : old('values_other.'.$fid, '') }}"
                                                                           x-model="otherText['{{ $fid }}']"
                                                                           @focus="answers['{{ $fid }}'] = '__other__'"
                                                                           placeholder="Tulis jawaban Anda..."
                                                                           class="min-w-[160px] flex-1 border-b border-slate-300 bg-transparent py-0.5 text-sm outline-none focus:border-[#0069A8]" />
                                                                </label>
                                                            @endif
                                                        </div>
                                                    @elseif($type === 'checkbox')
                                                        @php
                                                            $checkedArr = is_array($oldVal) ? $oldVal : array_values(array_filter(array_map('trim', explode(',', (string) $oldVal))));
                                                            $unknownOther = collect($checkedArr)->first(fn ($v) => ! in_array($v, $options, true));
                                                        @endphp
                                                        <div class="space-y-2" role="group" aria-label="{{ $fld['label'] }}">
                                                            @foreach($options as $opt)
                                                                <label class="sd-choice-tile flex min-h-[44px] cursor-pointer items-center gap-3 px-3.5 py-2.5 text-sm text-slate-800">
                                                                    <input type="checkbox"
                                                                           name="values[{{ $fid }}][]"
                                                                           value="{{ $opt }}"
                                                                           {{ in_array($opt, $checkedArr, true) ? 'checked' : '' }}
                                                                           class="h-4 w-4 accent-[#0069A8]" />
                                                                    <span class="flex-1 font-medium">{{ $opt }}</span>
                                                                    @if(! empty($optImages[$opt]))
                                                                        <img src="{{ $optImages[$opt] }}" alt="{{ $opt }}" class="h-12 w-12 rounded-lg border border-slate-200 object-cover" />
                                                                    @endif
                                                                </label>
                                                            @endforeach
                                                            @if($allowOther)
                                                                <label class="sd-choice-tile flex min-h-[44px] cursor-pointer flex-wrap items-center gap-3 px-3.5 py-2.5 text-sm text-slate-800">
                                                                    <input type="checkbox"
                                                                           name="values[{{ $fid }}][]"
                                                                           value="__other__"
                                                                           {{ $unknownOther ? 'checked' : '' }}
                                                                           class="h-4 w-4 accent-[#0069A8]" />
                                                                    <span class="font-medium">Lainnya:</span>
                                                                    <input type="text"
                                                                           name="values_other[{{ $fid }}]"
                                                                           value="{{ $unknownOther ?: old('values_other.'.$fid, '') }}"
                                                                           placeholder="Tulis opsi tambahan..."
                                                                           class="min-w-[160px] flex-1 border-b border-slate-300 bg-transparent py-0.5 text-sm outline-none focus:border-[#0069A8]" />
                                                                </label>
                                                            @endif
                                                        </div>
                                                    @elseif($type === 'linear_scale')
                                                        @php
                                                            $sMin = (int) ($cfg['scale_min'] ?? 1);
                                                            $sMax = max($sMin + 1, (int) ($cfg['scale_max'] ?? 5));
                                                        @endphp
                                                        <div class="rounded-xl border border-slate-200/80 bg-[#F8FAFC] p-4">
                                                            <div class="flex flex-wrap items-center justify-between gap-3">
                                                                @if(! empty($cfg['scale_min_label']))
                                                                    <span class="text-xs font-semibold text-[#475569]">{{ $cfg['scale_min_label'] }}</span>
                                                                @endif
                                                                <div class="flex flex-1 flex-wrap items-center justify-center gap-2">
                                                                    @for($n = $sMin; $n <= $sMax; $n++)
                                                                        <label class="flex cursor-pointer flex-col items-center gap-1 rounded-xl border border-slate-200 bg-white px-3 py-2 transition hover:border-[#0069A8]"
                                                                               :class="String(answers['{{ $fid }}'] ?? '') === '{{ $n }}' ? 'border-[#0069A8] bg-[#F0F9FF] text-[#0069A8]' : 'text-slate-700'">
                                                                            <span class="text-xs font-bold tabular-nums">{{ $n }}</span>
                                                                            <input type="radio"
                                                                                   name="values[{{ $fid }}]"
                                                                                   value="{{ $n }}"
                                                                                   x-model="answers['{{ $fid }}']"
                                                                                   {{ (string) $oldVal === (string) $n ? 'checked' : '' }}
                                                                                   class="h-3.5 w-3.5 accent-[#0069A8]" />
                                                                        </label>
                                                                    @endfor
                                                                </div>
                                                                @if(! empty($cfg['scale_max_label']))
                                                                    <span class="text-xs font-semibold text-[#475569]">{{ $cfg['scale_max_label'] }}</span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @elseif($type === 'rating')
                                                        @php
                                                            $rMax = max(3, min(10, (int) ($cfg['rating_max'] ?? 5)));
                                                            $rIcon = (string) ($cfg['rating_icon'] ?? 'star');
                                                            $glyph = $rIcon === 'heart' ? '♥' : ($rIcon === 'thumb' ? '👍' : '★');
                                                        @endphp
                                                        <div class="flex flex-wrap items-center gap-2" role="radiogroup" aria-label="{{ $fld['label'] }}">
                                                            @for($n = 1; $n <= $rMax; $n++)
                                                                <label class="flex cursor-pointer flex-col items-center gap-1 rounded-xl border border-slate-200 px-3 py-2 transition hover:border-[#0069A8]"
                                                                       :class="Number(answers['{{ $fid }}'] || 0) >= {{ $n }} ? 'border-[#0069A8] bg-[#F0F9FF]' : 'bg-white'">
                                                                    <input type="radio"
                                                                           name="values[{{ $fid }}]"
                                                                           value="{{ $n }}"
                                                                           x-model="answers['{{ $fid }}']"
                                                                           {{ (string) $oldVal === (string) $n ? 'checked' : '' }}
                                                                           class="sr-only" />
                                                                    <span class="text-xl transition"
                                                                          :class="Number(answers['{{ $fid }}'] || 0) >= {{ $n }} ? 'text-[#0069A8]' : 'text-slate-300'">{{ $glyph }}</span>
                                                                    <span class="text-[10px] font-bold tabular-nums text-slate-600">{{ $n }}</span>
                                                                </label>
                                                            @endfor
                                                        </div>
                                                    @elseif($type === 'mc_grid' || $type === 'checkbox_grid')
                                                        @php
                                                            $gridRows = array_values(array_filter(array_map('strval', $cfg['grid_rows'] ?? [])));
                                                            $gridCols = array_values(array_filter(array_map('strval', $cfg['grid_cols'] ?? [])));
                                                        @endphp
                                                        <div class="overflow-x-auto rounded-xl border border-slate-200">
                                                            <table class="wd-hairline-table min-w-[420px] text-xs">
                                                                <thead>
                                                                    <tr>
                                                                        <th class="p-3 text-left font-bold text-slate-700">Aspek / Kriteria</th>
                                                                        @foreach($gridCols as $colLbl)
                                                                            <th class="p-3 text-center font-bold text-slate-700">{{ $colLbl }}</th>
                                                                        @endforeach
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @foreach($gridRows as $rIdx => $rowLbl)
                                                                        <tr class="hover:bg-[#F8FAFC]">
                                                                            <td class="p-3 font-semibold text-slate-800">{{ $rowLbl }}</td>
                                                                            @foreach($gridCols as $colLbl)
                                                                                <td class="p-3 text-center">
                                                                                    @if($type === 'mc_grid')
                                                                                        <input type="radio"
                                                                                               name="values[{{ $fid }}][{{ $rIdx }}]"
                                                                                               value="{{ $colLbl }}"
                                                                                               x-model="gridValues['{{ $fid }}'][{{ $rIdx }}]"
                                                                                               @change="enforceGridColLimit('{{ $fid }}', {{ $rIdx }}, @js($colLbl), false)"
                                                                                               class="h-4 w-4 accent-[#0069A8]" />
                                                                                    @else
                                                                                        <input type="checkbox"
                                                                                               name="values[{{ $fid }}][{{ $rIdx }}][]"
                                                                                               value="{{ $colLbl }}"
                                                                                               x-model="gridValues['{{ $fid }}'][{{ $rIdx }}]"
                                                                                               @change="enforceGridColLimit('{{ $fid }}', {{ $rIdx }}, @js($colLbl), true)"
                                                                                               class="h-4 w-4 accent-[#0069A8]" />
                                                                                    @endif
                                                                                </td>
                                                                            @endforeach
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                        @if(! empty($cfg['limit_one_per_col']))
                                                            <p class="mt-1.5 text-[11px] font-medium text-[#475569]">Catatan: maksimal 1 pilihan yang sama per kolom.</p>
                                                        @endif
                                                    @elseif($type === 'file')
                                                        @php
                                                            $qualitySvc = app(\App\Services\DataQualityService::class);
                                                            $allowedExts = $qualitySvc->allowedFileExtensions($fld);
                                                            $acceptAttr = $allowedExts !== null ? implode(',', array_map(fn ($e) => '.'.$e, $allowedExts)) : '';
                                                            $rawMaxMb = array_key_exists('file_max_mb', $cfg) ? (int) $cfg['file_max_mb'] : 0;
                                                            $maxMb = $rawMaxMb <= 0 ? 0 : min(1024, $rawMaxMb);
                                                            $maxCount = max(1, min(20, (int) ($cfg['file_max_count'] ?? 1)));
                                                            $existingFile = $editingSubmission?->values[$fid] ?? '';
                                                            $typeLabelHint = $allowedExts !== null ? strtoupper(implode(', ', $allowedExts)) : 'Semua jenis berkas';
                                                            $sizeLabelHint = $maxMb === 0 ? 'tanpa batas ukuran' : "maks {$maxMb} MB";
                                                            $defaultPrompt = $existingFile !== ''
                                                                ? "Terunggah: {$existingFile} (klik untuk ganti)"
                                                                : "Pilih berkas ({$typeLabelHint} · {$sizeLabelHint})...";
                                                        @endphp
                                                        <input type="hidden" name="uploaded_tokens[{{ $fid }}]" :value="uploadedTokens['{{ $fid }}'] || ''">
                                                        <div class="space-y-2.5">
                                                            <div class="flex flex-wrap items-center gap-2 rounded-xl border border-dashed px-4 py-3 text-sm transition"
                                                                 :class="uploadedTokens['{{ $fid }}'] ? 'border-[#047857] bg-[#ECFDF5]/70' : 'border-slate-300 bg-[#F8FAFC] hover:border-[#0069A8] hover:bg-[#F0F9FF]'">
                                                                <label for="fld_{{ $fid }}" class="flex min-w-0 flex-1 cursor-pointer items-center gap-3">
                                                                    <i data-lucide="paperclip" class="h-4 w-4 shrink-0" :class="uploadedTokens['{{ $fid }}'] ? 'text-[#047857]' : 'text-[#0069A8]'" aria-hidden="true"></i>
                                                                    <span x-text="fileLabels['{{ $fid }}'] || @js($defaultPrompt)" class="min-w-0 flex-1 truncate font-medium" :class="uploadedTokens['{{ $fid }}'] ? 'text-[#047857]' : 'text-slate-700'"></span>
                                                                </label>
                                                                <template x-if="uploadedTokens['{{ $fid }}']">
                                                                    <div class="flex shrink-0 items-center gap-2">
                                                                        <span class="rounded-md bg-[#047857] px-2 py-0.5 text-[10px] font-bold text-white uppercase">Siap dikirim</span>
                                                                        <button type="button" @click="removeUploadedFile('{{ $fid }}')" class="rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-bold text-[#B91C1C] hover:bg-red-50">
                                                                            Ganti / Hapus
                                                                        </button>
                                                                    </div>
                                                                </template>
                                                                <input type="file"
                                                                       id="fld_{{ $fid }}"
                                                                       name="{{ $maxCount > 1 ? 'files['.$fid.'][]' : 'files['.$fid.']' }}"
                                                                       @if($acceptAttr !== '') accept="{{ $acceptAttr }}" @endif
                                                                       @if($maxCount > 1) multiple @endif
                                                                       data-has-existing="{{ $existingFile !== '' ? '1' : '0' }}"
                                                                       @change="handleFileSelect('{{ $fid }}', $event, {{ $maxCount }}, {{ $maxMb }})"
                                                                       class="sr-only" />
                                                            </div>

                                                            <div x-show="uploadingFields['{{ $fid }}']" x-cloak class="space-y-1">
                                                                <div class="flex items-center justify-between text-[11px] font-bold text-[#0069A8]">
                                                                    <span>Mengunggah berkas secara bertahap...</span>
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
                                                        @if(! empty($cfg['include_time']))
                                                            <input id="fld_{{ $fid }}"
                                                                   type="datetime-local"
                                                                   name="values[{{ $fid }}]"
                                                                   value="{{ is_string($oldVal) ? $oldVal : '' }}"
                                                                   class="min-h-[44px] w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-[#0069A8] focus:ring-4 focus:ring-sky-100" />
                                                        @else
                                                            <input id="fld_{{ $fid }}"
                                                                   type="date"
                                                                   name="values[{{ $fid }}]"
                                                                   value="{{ is_string($oldVal) ? $oldVal : '' }}"
                                                                   class="min-h-[44px] w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-[#0069A8] focus:ring-4 focus:ring-sky-100" />
                                                        @endif
                                                    @elseif($type === 'time')
                                                        @if(($cfg['time_mode'] ?? 'time') === 'duration')
                                                            <input id="fld_{{ $fid }}"
                                                                   type="text"
                                                                   name="values[{{ $fid }}]"
                                                                   value="{{ is_string($oldVal) ? $oldVal : '' }}"
                                                                   placeholder="JJ:MM:DD (contoh: 01:30:00)"
                                                                   class="min-h-[44px] w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm tabular-nums text-slate-900 outline-none focus:border-[#0069A8] focus:ring-4 focus:ring-sky-100" />
                                                        @else
                                                            <input id="fld_{{ $fid }}"
                                                                   type="time"
                                                                   name="values[{{ $fid }}]"
                                                                   value="{{ is_string($oldVal) ? $oldVal : '' }}"
                                                                   class="min-h-[44px] w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-[#0069A8] focus:ring-4 focus:ring-sky-100" />
                                                        @endif
                                                    @else
                                                        <input
                                                            id="fld_{{ $fid }}"
                                                            type="{{ $type === 'number' ? 'number' : 'text' }}"
                                                            name="values[{{ $fid }}]"
                                                            value="{{ is_string($oldVal) ? $oldVal : '' }}"
                                                            {{ $type === 'number' ? 'step="any"' : '' }}
                                                            class="min-h-[44px] w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-[#0069A8] focus:ring-4 focus:ring-sky-100"
                                                        />
                                                    @endif

                                                    @if($fieldErr)
                                                        <p class="mt-2 text-xs font-semibold text-[#B91C1C]">{{ $fieldErr }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endforeach

                        <!-- Submit & Section Navigation Bar -->
                        <div class="wd-bento flex flex-wrap items-center justify-between gap-3 px-6 py-4">
                            <div class="flex flex-wrap items-center gap-3">
                                <p class="text-xs text-[#475569]">Kolom bertanda <span class="font-bold text-[#B91C1C]">*</span> wajib diisi.</p>
                                @if($canSubmit)
                                    <button type="button" @click="clearForm($refs.runnerForm)" class="text-xs font-bold text-[#0069A8] hover:underline">
                                        Kosongkan isian
                                    </button>
                                @endif
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <button type="button"
                                        x-show="currentSectionIdx > 0"
                                        x-cloak
                                        @click="prevSection()"
                                        class="inline-flex min-h-[42px] items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">
                                    Kembali
                                </button>

                                <button type="button"
                                        x-show="!isLastStep()"
                                        x-cloak
                                        @click="nextSection($refs.runnerForm)"
                                        class="inline-flex min-h-[42px] items-center justify-center rounded-xl bg-[#0069A8] px-5 py-2 text-sm font-bold text-white transition hover:bg-[#182654]">
                                    Berikutnya
                                </button>

                                <button type="submit"
                                        x-show="isLastStep()"
                                        :disabled="Object.values(uploadingFields).some(Boolean)"
                                        class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl bg-[#0069A8] px-6 py-2.5 text-sm font-bold text-white transition hover:bg-[#182654] disabled:cursor-not-allowed disabled:opacity-55">
                                    <span x-text="Object.values(uploadingFields).some(Boolean) ? 'Menunggu unggahan selesai...' : @js($editingSubmission ? 'Simpan perubahan respons' : 'Kirim respons')">
                                        {{ $editingSubmission ? 'Simpan perubahan respons' : 'Kirim respons' }}
                                    </span>
                                </button>
                            </div>
                        </div>
                    </form>
                @endif
            </div>
        </div>
        @endif
    </div>
</main>
@endsection
