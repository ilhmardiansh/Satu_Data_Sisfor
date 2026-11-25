@extends('layouts.panel')

@section('title', 'Instrumen Formulir - Panel Satu Data UNUSA')

@section('panel_content')
@php
    $isAdmin = auth()->user()->isAdmin();
@endphp

<div x-data="formGalleryManager()">
    <x-panel-head
        scene="formulir"
        badge="{{ $counts['open'] ?? 0 }} Aktif"
        badgeTone="sky"
        title="Instrumen Formulir"
        desc="Buat formulir pendataan dan bagikan tautannya ke Produsen Data."
    >
        <a href="{{ route('panel.formulir.create') }}"
           class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl bg-[#00A6F4] px-4 py-2.5 text-sm font-bold text-[#09122C] transition hover:bg-[#38BDF8] motion-safe:active:scale-[0.98]">
            <i data-lucide="plus" class="h-4 w-4" aria-hidden="true"></i>
            Buat formulir baru
        </a>
    </x-panel-head>

    <section class="mb-5">
        <p class="mb-2.5 text-xs font-bold tracking-wide text-[#0069A8] uppercase">Mulai dari Template</p>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-5">
            @foreach($templates as $tpl)
                <a href="{{ route('panel.formulir.create', ['template' => $tpl['slug']]) }}"
                   class="wd-bento-interactive group flex items-center gap-3 p-3.5">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-[#F0F9FF] text-[#0069A8] transition group-hover:bg-[#0069A8] group-hover:text-white">
                        <i data-lucide="{{ $tpl['icon'] }}" class="h-4 w-4" aria-hidden="true"></i>
                    </span>
                    <div class="min-w-0">
                        <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold text-slate-600">{{ $tpl['badge'] }}</span>
                        <p class="mt-1 truncate text-sm font-bold text-slate-900 group-hover:text-[#0069A8]">{{ $tpl['name'] }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    </section>

    <!-- Toolbar Filter & Pencarian Cepat -->
    <div class="wd-bento mb-5 flex flex-wrap items-center justify-between gap-3 p-3">
        <div class="flex flex-wrap gap-1 rounded-xl border border-slate-200/90 bg-[#F8FAFC] p-1 text-sm">
            @foreach(['all' => 'Semua', 'open' => 'Dibuka', 'closed' => 'Ditutup'] as $k => $label)
                <a href="{{ route('panel.formulir', ['status' => $k]) }}"
                   class="inline-flex min-h-[38px] items-center rounded-lg px-3.5 py-1.5 text-xs font-bold transition {{ $status === $k ? 'bg-[#0069A8] text-white' : 'text-slate-600 hover:text-slate-900' }}">
                    {{ $label }} <span class="ml-1.5 tabular-nums opacity-85">({{ $counts[$k] ?? 0 }})</span>
                </a>
            @endforeach
        </div>

        <label class="flex h-10 min-w-[220px] flex-1 items-center gap-2 rounded-xl border border-slate-200 bg-[#F8FAFC] px-3 focus-within:border-[#0069A8] focus-within:bg-white sm:max-w-xs">
            <i data-lucide="search" class="h-4 w-4 shrink-0 text-[#475569]" aria-hidden="true"></i>
            <input type="text"
                   x-model="quickSearch"
                   placeholder="Saring cepat kode atau judul..."
                   class="min-w-0 flex-1 bg-transparent text-sm text-slate-900 outline-none placeholder:text-slate-400" />
            <button type="button"
                    x-show="quickSearch !== ''"
                    x-cloak
                    @click="quickSearch = ''"
                    aria-label="Hapus pencarian"
                    class="text-xs font-semibold text-slate-500 hover:text-slate-900">
                Reset
            </button>
        </label>
    </div>

    <div class="wd-bento overflow-hidden">
        <div class="overflow-x-auto">
            <table class="wd-hairline-table min-w-[920px]">
                <thead>
                    <tr>
                        <th class="px-4 py-3 text-left text-[11px] font-bold tracking-wider whitespace-nowrap text-[#475569] uppercase">Formulir</th>
                        <th class="px-4 py-3 text-left text-[11px] font-bold tracking-wider whitespace-nowrap text-[#475569] uppercase">Sasaran &amp; Tenggat</th>
                        <th class="px-4 py-3 text-left text-[11px] font-bold tracking-wider whitespace-nowrap text-[#475569] uppercase">Status Form</th>
                        <th class="px-4 py-3 text-left text-[11px] font-bold tracking-wider whitespace-nowrap text-[#475569] uppercase">Pipeline Pre-processing</th>
                        <th class="px-4 py-3 text-left text-[11px] font-bold tracking-wider whitespace-nowrap text-[#475569] uppercase">Respons</th>
                        <th class="px-4 py-3 text-right text-[11px] font-bold tracking-wider whitespace-nowrap text-[#475569] uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($forms as $f)
                        @php
                            $gformUrl = route('formulir.show', $f->code);
                            $searchKey = strtolower($f->code.' '.$f->title.' '.($f->category?->name ?? '').' '.$f->target);
                            $validPct = $f->submissions_count > 0 ? (int) round(($f->valid_submissions_count / $f->submissions_count) * 100) : 0;
                            $sectionCount = collect($f->items ?? [])->where('type', 'section')->count();
                            $isQuiz = (bool) ($f->settings['is_quiz'] ?? false);
                        @endphp
                        <tr x-show="matchesSearch(@js($searchKey))" class="transition-colors hover:bg-[#F8FAFC]">
                            <td class="px-4 py-3.5 text-sm text-slate-600">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="rounded bg-[#F0F9FF] px-1.5 py-0.5 text-[11px] font-bold tabular-nums text-[#0069A8]">{{ $f->code }}</span>
                                    @if($f->batasi_satu_respon)
                                        <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[11px] font-semibold text-slate-700">1x/akun</span>
                                    @endif
                                    @if($isQuiz)
                                        <span class="rounded bg-[#ECFDF5] px-1.5 py-0.5 text-[11px] font-bold text-[#047857]">Kuis</span>
                                    @endif
                                    @if($sectionCount > 1)
                                        <span class="rounded bg-[#EFF6FF] px-1.5 py-0.5 text-[11px] font-semibold text-[#182654]">{{ $sectionCount }} bagian</span>
                                    @endif
                                </div>
                                <a href="{{ route('panel.formulir.edit', $f->code) }}" class="mt-1 block font-bold text-slate-900 hover:text-[#0069A8]">
                                    {{ $f->title }}
                                </a>
                                <p class="text-xs text-[#475569]">
                                    {{ $f->category?->name }} · {{ $f->tahun }} · {{ $f->periode }} · {{ count($f->fields ?? []) }} pertanyaan
                                </p>
                            </td>
                            <td class="px-4 py-3.5 text-sm whitespace-nowrap text-slate-700">
                                <p class="font-semibold text-slate-900">{{ $f->target }}</p>
                                <p class="text-xs text-[#475569]">{{ $f->deadlineFormatted() }}</p>
                                <p class="text-xs font-medium {{ $f->isOpen() ? 'text-[#0069A8]' : 'text-slate-500' }}">{{ $f->timeLeftLabel() }}</p>
                            </td>
                            <td class="px-4 py-3.5 text-sm">
                                @if($f->isOpen())
                                    <span class="inline-flex items-center gap-1.5 rounded-md bg-[#ECFDF5] px-2.5 py-1 text-[11px] font-bold text-[#047857]">
                                        <span class="h-1.5 w-1.5 rounded-full bg-[#047857]" aria-hidden="true"></span>
                                        Dibuka
                                    </span>
                                @else
                                    <span class="inline-flex rounded-md bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-700">Ditutup</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-sm">
                                @if($f->status_preprocessing === 'siap')
                                    <span class="inline-flex items-center gap-1 rounded-md bg-[#ECFDF5] px-2.5 py-1 text-[11px] font-bold text-[#047857]">Siap Dataset</span>
                                @elseif($f->isOpen())
                                    <span class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600">Menunggu ditutup</span>
                                @elseif($f->status_preprocessing === 'proses' || $f->submissions_count > 0)
                                    <span class="inline-flex items-center gap-1 rounded-md bg-[#FFFBEB] px-2.5 py-1 text-[11px] font-bold text-[#92400E]">Perlu Pre-processing</span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600">Belum ada respons</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-sm tabular-nums text-slate-700">
                                <p class="font-bold tabular-nums text-slate-900">{{ $f->submissions_count }}</p>
                                @if($f->submissions_count > 0)
                                    <div class="mt-1.5 h-1 w-24 overflow-hidden rounded-full bg-slate-200">
                                        <div class="h-full rounded-full bg-[#047857]" style="width: {{ $validPct }}%"></div>
                                    </div>
                                    <p class="mt-1 text-[11px] text-[#475569]">
                                        <span class="text-[#047857]">{{ $f->valid_submissions_count }} valid</span> · <span class="text-[#92400E]">{{ $f->raw_submissions_count }} mentah</span>
                                    </p>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-sm text-slate-600">
                                <div class="flex justify-end gap-1.5">
                                    <button type="button"
                                            @click="copyLink(@js($gformUrl))"
                                            class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-[#0069A8] hover:text-[#0069A8]"
                                            title="Salin tautan GForm"
                                            aria-label="Salin tautan formulir">
                                        <i data-lucide="link-2" class="h-4 w-4" aria-hidden="true"></i>
                                    </button>
                                    <a href="{{ $gformUrl }}"
                                       class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-[#0069A8] hover:text-[#0069A8]"
                                       title="Buka halaman formulir"
                                       aria-label="Buka halaman formulir">
                                        <i data-lucide="external-link" class="h-4 w-4" aria-hidden="true"></i>
                                    </a>
                                    <form method="POST" action="{{ route('panel.formulir.toggle', $f->code) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        @if($f->isOpen())
                                            <button type="submit"
                                                    class="inline-flex h-9 shrink-0 items-center gap-1.5 rounded-xl border border-amber-200 bg-amber-50 px-3 text-xs font-bold text-amber-800 transition hover:bg-amber-100"
                                                    title="Tutup formulir agar dapat masuk Pre-processing"
                                                    aria-label="Tutup formulir">
                                                <i data-lucide="lock" class="h-3.5 w-3.5" aria-hidden="true"></i>
                                                <span>Tutup</span>
                                            </button>
                                        @else
                                            <button type="submit"
                                                    class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-[#0069A8] hover:text-[#0069A8]"
                                                    title="{{ $f->status_aktif ? 'Tutup formulir' : 'Buka kembali formulir' }}"
                                                    aria-label="{{ $f->status_aktif ? 'Tutup formulir' : 'Buka formulir' }}">
                                                <i data-lucide="{{ $f->status_aktif ? 'lock' : 'unlock' }}" class="h-4 w-4" aria-hidden="true"></i>
                                            </button>
                                        @endif
                                    </form>
                                    <form method="POST" action="{{ route('panel.formulir.duplicate', $f->code) }}" class="inline">
                                        @csrf
                                        <button type="submit"
                                                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-[#0069A8] hover:text-[#0069A8]"
                                                title="Duplikat formulir"
                                                aria-label="Duplikat formulir">
                                            <i data-lucide="copy" class="h-4 w-4" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                    @if($f->isClosed())
                                        <a href="{{ route('panel.preprocessing.show', $f->code) }}"
                                           class="inline-flex h-9 shrink-0 items-center gap-1.5 rounded-xl bg-[#0069A8] px-3 text-xs font-bold text-white transition hover:bg-[#005187]"
                                           title="Buka Pre-processing Data"
                                           aria-label="Buka Pre-processing Data">
                                            <span>Pre-processing</span>
                                        </a>
                                    @endif
                                    <a href="{{ route('panel.formulir.edit', $f->code) }}"
                                       class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-[#0069A8] hover:text-[#0069A8]"
                                       title="Ubah di Studio Formulir"
                                       aria-label="Ubah formulir">
                                        <i data-lucide="pencil" class="h-4 w-4" aria-hidden="true"></i>
                                    </a>
                                    @if($isAdmin)
                                        <button type="button"
                                                @click="$dispatch('open-confirm', {
                                                    message: @js("Formulir “{$f->title}” beserta {$f->submissions_count} isiannya akan dihapus."),
                                                    action: @js(route('panel.formulir.destroy', $f->code)),
                                                    method: 'DELETE'
                                                })"
                                                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-red-200 hover:bg-red-50 hover:text-[#B91C1C]"
                                                title="Hapus formulir"
                                                aria-label="Hapus formulir">
                                            <i data-lucide="trash-2" class="h-4 w-4" aria-hidden="true"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <span class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-[#F0F9FF] text-[#0069A8]">
                                    <i data-lucide="clipboard-list" class="h-6 w-6" aria-hidden="true"></i>
                                </span>
                                <p class="mt-3 text-sm font-bold text-slate-800">Belum ada instrumen formulir</p>
                                <p class="mt-1 text-xs text-[#475569]">Klik "Buat formulir baru" atau pilih template di atas untuk merancang instrumen pengumpulan data pertama Anda.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $forms->links('components.pagination') }}
    </div>
</div>
@endsection

@push('scripts')
<script>
function formGalleryManager() {
    return {
        quickSearch: '',

        matchesSearch(haystack) {
            const q = this.quickSearch.trim().toLowerCase();
            return !q || String(haystack || '').includes(q);
        },

        copyLink(url) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(() => {
                    this.$dispatch('toast', { message: 'Tautan formulir disalin ke papan klip.', tone: 'success' });
                });
            } else {
                this.$dispatch('toast', { message: 'Buka tautan: ' + url, tone: 'info' });
            }
        },
    };
}
</script>
@endpush
