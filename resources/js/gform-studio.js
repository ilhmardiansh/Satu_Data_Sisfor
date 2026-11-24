const QUESTION_TYPES = [
    { value: 'text', label: 'Jawaban singkat', group: 'Teks', desc: 'Teks satu baris + validasi angka/email/panjang/regex' },
    { value: 'textarea', label: 'Paragraf', group: 'Teks', desc: 'Jawaban panjang multi-baris' },
    { value: 'radio', label: 'Pilihan ganda', group: 'Pilihan', desc: 'Pilih satu opsi, Lainnya..., gambar opsi & percabangan' },
    { value: 'checkbox', label: 'Kotak centang', group: 'Pilihan', desc: 'Pilih banyak opsi + batas min/maks/tepat' },
    { value: 'select', label: 'Drop-down', group: 'Pilihan', desc: 'Daftar tarik turun ringkas + percabangan bagian' },
    { value: 'file', label: 'Upload file', group: 'Berkas', desc: 'Unggah PDF, gambar, dokumen dengan batas jenis & ukuran' },
    { value: 'linear_scale', label: 'Skala linier', group: 'Skala', desc: 'Skala angka 0/1 hingga 10 beserta label ujung' },
    { value: 'rating', label: 'Peringkat (Rating)', group: 'Skala', desc: 'Ikon bintang, hati, atau jempol (3-10 level)' },
    { value: 'mc_grid', label: 'Kisi pilihan ganda', group: 'Kisi', desc: 'Matriks baris × kolom (1 pilihan per baris)' },
    { value: 'checkbox_grid', label: 'Petak kotak centang', group: 'Kisi', desc: 'Matriks baris × kolom (multi-pilihan per baris)' },
    { value: 'date', label: 'Tanggal', group: 'Waktu', desc: 'Pemilih tanggal (opsional waktu / tahun)' },
    { value: 'time', label: 'Waktu', group: 'Waktu', desc: 'Pemilih jam (JJ:MM) atau durasi (JJ:MM:DD)' },
    { value: 'number', label: 'Angka (Numerik)', group: 'Teks', desc: 'Nilai numerik siap hitung di Pre-processing & Dataset' },
];

const CHOICE_TYPES = ['radio', 'checkbox', 'select'];
const GRID_TYPES = ['mc_grid', 'checkbox_grid'];
const NON_INPUT_TYPES = ['section', 'title_block', 'image_block', 'video_block'];
const VALIDATABLE_TYPES = ['text', 'textarea', 'number', 'checkbox'];

const PALETTE_GROUPS = [
    {
        title: 'Teks & Numerik',
        items: [
            { type: 'text', label: 'Jawaban Singkat', code: 'TXT' },
            { type: 'textarea', label: 'Paragraf', code: 'LNG' },
            { type: 'number', label: 'Angka Numerik', code: 'NUM' },
        ],
    },
    {
        title: 'Pilihan & Daftar',
        items: [
            { type: 'radio', label: 'Pilihan Ganda', code: 'RAD' },
            { type: 'checkbox', label: 'Kotak Centang', code: 'CHK' },
            { type: 'select', label: 'Drop-down', code: 'DRP' },
        ],
    },
    {
        title: 'Skala & Matriks',
        items: [
            { type: 'linear_scale', label: 'Skala Linier', code: 'SCL' },
            { type: 'rating', label: 'Peringkat Ikon', code: 'RTG' },
            { type: 'mc_grid', label: 'Kisi Pilihan', code: 'MGD' },
            { type: 'checkbox_grid', label: 'Petak Centang', code: 'CGD' },
        ],
    },
    {
        title: 'Berkas & Waktu',
        items: [
            { type: 'file', label: 'Upload File', code: 'DOC' },
            { type: 'date', label: 'Tanggal', code: 'TGL' },
            { type: 'time', label: 'Waktu / Durasi', code: 'JAM' },
        ],
    },
    {
        title: 'Struktur & Media',
        items: [
            { type: 'section', label: 'Bagian Halaman', code: 'BAG' },
            { type: 'title_block', label: 'Judul & Info', code: 'HDR' },
            { type: 'image_block', label: 'Sisipkan Gambar', code: 'IMG' },
            { type: 'video_block', label: 'Video YouTube', code: 'VID' },
        ],
    },
];

function uid(prefix = 'q') {
    return `${prefix}_${Math.random().toString(36).slice(2, 7)}`;
}

function defaultConfigFor(type) {
    if (type === 'linear_scale') {
        return { scale_min: 1, scale_max: 5, scale_min_label: 'Sangat Kurang', scale_max_label: 'Sangat Baik' };
    }
    if (type === 'rating') {
        return { rating_max: 5, rating_icon: 'star' };
    }
    if (type === 'mc_grid' || type === 'checkbox_grid') {
        return {
            grid_rows: ['Baris 1', 'Baris 2'],
            grid_cols: ['Kolom 1', 'Kolom 2', 'Kolom 3'],
            require_row_response: false,
            limit_one_per_col: false,
        };
    }
    if (type === 'date') {
        return { include_time: false, include_year: true };
    }
    if (type === 'time') {
        return { time_mode: 'time' };
    }
    if (type === 'file') {
        return { restrict_file_types: false, file_types: [], file_max_count: 1, file_max_mb: 0 };
    }
    if (type === 'section') {
        return { after_action: 'next' };
    }
    if (type === 'image_block') {
        return { image_url: '', image_caption: '', image_align: 'center' };
    }
    if (type === 'video_block') {
        return { video_url: '', video_caption: '' };
    }
    return {};
}

function hydrateItem(raw) {
    const type = raw.type || 'text';
    const baseCfg = defaultConfigFor(type);
    const rawCfg = raw.config && typeof raw.config === 'object' && !Array.isArray(raw.config) ? raw.config : {};
    const cfg = { ...baseCfg, ...rawCfg };

    // Always initialize media keys on every item so Alpine 3 tracks reactivity from mount
    cfg.image_url = typeof cfg.image_url === 'string' ? cfg.image_url : '';
    cfg.image_caption = typeof cfg.image_caption === 'string' ? cfg.image_caption : '';
    cfg.image_align = ['left', 'center', 'right'].includes(cfg.image_align) ? cfg.image_align : 'center';

    if (CHOICE_TYPES.includes(type)) {
        cfg.option_images = cfg.option_images && typeof cfg.option_images === 'object' && !Array.isArray(cfg.option_images)
            ? { ...cfg.option_images }
            : {};
        cfg.branching = cfg.branching && typeof cfg.branching === 'object' && !Array.isArray(cfg.branching)
            ? { ...cfg.branching }
            : {};
    }

    if (type === 'file' && cfg.restrict_file_types && (!Array.isArray(cfg.file_types) || cfg.file_types.length === 0)) {
        cfg.restrict_file_types = false;
    }

    if (cfg.validation && typeof cfg.validation === 'object') {
        cfg.validation = {
            enabled: !!cfg.validation.enabled,
            category: cfg.validation.category || (type === 'checkbox' ? 'checkbox' : (type === 'number' ? 'number' : 'text')),
            operator: cfg.validation.operator || (type === 'checkbox' ? 'min_select' : (type === 'number' ? 'gte' : 'contains')),
            value: cfg.validation.value ?? '',
            value2: cfg.validation.value2 ?? '',
            message: cfg.validation.message ?? '',
        };
    }

    if (GRID_TYPES.includes(type)) {
        cfg.grid_rows = Array.isArray(cfg.grid_rows) && cfg.grid_rows.length ? [...cfg.grid_rows] : ['Baris 1'];
        cfg.grid_cols = Array.isArray(cfg.grid_cols) && cfg.grid_cols.length ? [...cfg.grid_cols] : ['Kolom 1', 'Kolom 2'];
    }

    return {
        id: raw.id || uid(),
        label: raw.label ?? '',
        help: raw.help ?? '',
        type,
        required: NON_INPUT_TYPES.includes(type) ? false : !!raw.required,
        options: CHOICE_TYPES.includes(type)
            ? (Array.isArray(raw.options) && raw.options.length ? [...raw.options] : ['Opsi 1', 'Opsi 2'])
            : (Array.isArray(raw.options) ? [...raw.options] : []),
        config: cfg,
        _menuOpen: false,
        _prevOpt: '',
        _showHelp: !!(raw.help && String(raw.help).trim() !== ''),
        _showAnswerKey: !!(cfg.points || cfg.answer_key),
    };
}

export function gformStudio() {
    const dataEl = document.getElementById('gform-studio-data');
    let payload = {};
    try {
        payload = dataEl ? JSON.parse(dataEl.textContent || '{}') : {};
    } catch {
        payload = {};
    }

    return {
        mode: payload.mode || 'create',
        action: payload.action || '',
        listUrl: payload.listUrl || '/panel/formulir',
        uploadUrl: payload.uploadUrl || '/panel/formulir/media',
        formCode: payload.formCode || null,
        publicUrl: payload.publicUrl || null,
        preprocessUrl: payload.preprocessUrl || null,
        csvUrl: payload.csvUrl || null,
        duplicateUrl: payload.duplicateUrl || null,

        meta: {
            title: payload.meta?.title ?? '',
            description: payload.meta?.description ?? '',
            category_id: String(payload.meta?.category_id ?? ''),
            tahun: String(payload.meta?.tahun ?? new Date().getFullYear()),
            periode: payload.meta?.periode || 'Ganjil',
            target: payload.meta?.target || 'Mahasiswa',
            deadline: payload.meta?.deadline || '',
            batasi_satu_respon: !!payload.meta?.batasi_satu_respon,
            status_aktif: payload.meta?.status_aktif !== false,
        },
        settings: payload.settings || {},
        items: Array.isArray(payload.items) && payload.items.length
            ? payload.items.map(hydrateItem)
            : [hydrateItem({ id: 'q1', label: 'Pertanyaan Tanpa Judul', type: 'radio', options: ['Opsi 1', 'Opsi 2'] })],
        importableForms: Array.isArray(payload.importableForms) ? payload.importableForms : [],
        responses: payload.responses || { total: 0, valid: 0, mentah: 0, ditolak: 0, accepting: true, questions: [], submissions: [] },

        questionTypes: QUESTION_TYPES,
        paletteGroups: PALETTE_GROUPS,
        activeTab: 'questions', // questions | responses | settings
        leftPaneTab: 'palette', // palette | outline
        selectedTarget: 'item', // header | item
        mobilePaletteOpen: false,
        mobileInspectorOpen: false,
        outlineCollapsed: false,
        previewMode: false,
        themeDrawerOpen: false,
        importDrawerOpen: false,
        shareDrawerOpen: false,
        responsesSubTab: 'summary', // summary | question | individual
        responseQuestionIdx: 0,
        responseIndividualIdx: 0,

        activeId: null,
        dragIndex: null,
        dragOverIndex: null,
        uploadingTarget: null,
        uploadError: '',
        importSearch: '',
        importSelectedFormCode: payload.importableForms?.[0]?.code || '',
        importSelectedIds: [],
        embedWidth: 640,
        embedHeight: 720,

        validationError: '',
        afterSave: 'list',
        hasChanges: false,
        history: [],
        historyIndex: -1,
        _skipHistory: false,

        // Preview interactive runner state
        pvStep: 0,
        pvAnswers: {},
        pvOtherAnswers: {},
        pvGridAnswers: {},
        pvSubmitted: false,

        init() {
            if (this.items.length) {
                this.activeId = this.items[0].id;
            }
            this.pushHistory();

            window.addEventListener('keydown', (e) => {
                const mod = e.ctrlKey || e.metaKey;
                if (!mod) return;
                const tag = (e.target?.tagName || '').toLowerCase();
                if (tag === 'input' || tag === 'textarea' || tag === 'select') return;
                if (e.key.toLowerCase() === 'z' && !e.shiftKey) {
                    e.preventDefault();
                    this.undo();
                } else if (e.key.toLowerCase() === 'y' || (e.key.toLowerCase() === 'z' && e.shiftKey)) {
                    e.preventDefault();
                    this.redo();
                }
            });

            this.$watch('items', () => {
                if (!this._skipHistory) {
                    this.hasChanges = true;
                }
            });
            this.$watch('meta', () => {
                this.hasChanges = true;
            });
            this.$watch('settings', () => {
                this.hasChanges = true;
            });

            this.$nextTick(() => window.initLucide?.());
        },

        isInput(type) {
            return !NON_INPUT_TYPES.includes(type);
        },

        isChoice(type) {
            return CHOICE_TYPES.includes(type);
        },

        isGrid(type) {
            return GRID_TYPES.includes(type);
        },

        canValidate(type) {
            return VALIDATABLE_TYPES.includes(type);
        },

        typeLabel(type) {
            if (type === 'section') return 'Bagian Formulir';
            if (type === 'title_block') return 'Judul & Deskripsi';
            if (type === 'image_block') return 'Blok Gambar';
            if (type === 'video_block') return 'Blok Video';
            return QUESTION_TYPES.find((t) => t.value === type)?.label || type;
        },

        totalPoints() {
            return this.items.reduce((sum, it) => {
                if (!this.isInput(it.type)) return sum;
                return sum + Math.max(0, Number(it.config?.points || 0));
            }, 0);
        },

        inputCount() {
            return this.items.filter((it) => this.isInput(it.type)).length;
        },

        questionNumberAt(idx) {
            let count = 0;
            for (let i = 0; i <= idx && i < this.items.length; i++) {
                if (this.isInput(this.items[i].type)) count++;
            }
            return count;
        },

        sectionsList() {
            const list = [];
            let current = {
                id: '__top__',
                title: this.meta.title || 'Bagian Utama',
                description: this.meta.description || '',
                itemIndex: -1,
                afterAction: 'next',
                items: [],
            };

            let startedExplicit = false;
            this.items.forEach((it, idx) => {
                if (it.type === 'section') {
                    if (!startedExplicit && current.items.length === 0) {
                        current = {
                            id: it.id,
                            title: it.label || 'Bagian 1',
                            description: it.help || '',
                            itemIndex: idx,
                            afterAction: it.config?.after_action || 'next',
                            items: [],
                        };
                        startedExplicit = true;
                    } else {
                        list.push(current);
                        current = {
                            id: it.id,
                            title: it.label || `Bagian ${list.length + 1}`,
                            description: it.help || '',
                            itemIndex: idx,
                            afterAction: it.config?.after_action || 'next',
                            items: [],
                        };
                        startedExplicit = true;
                    }
                } else {
                    current.items.push({ item: it, idx });
                }
            });
            list.push(current);
            return list;
        },

        sectionNumberForItem(idx) {
            let sec = 1;
            let sawNonSectionBeforeFirst = false;
            for (let i = 0; i <= idx && i < this.items.length; i++) {
                if (this.items[i].type === 'section') {
                    if (sawNonSectionBeforeFirst || i > 0) {
                        sec++;
                    }
                } else {
                    sawNonSectionBeforeFirst = true;
                }
            }
            return sec;
        },

        totalSections() {
            return this.sectionsList().length;
        },

        snapshotState() {
            return JSON.stringify({
                meta: this.meta,
                settings: this.settings,
                items: this.items.map((it) => ({
                    id: it.id,
                    label: it.label,
                    help: it.help,
                    type: it.type,
                    required: it.required,
                    options: it.options,
                    config: it.config,
                })),
            });
        },

        pushHistory() {
            const snap = this.snapshotState();
            if (this.history[this.historyIndex] === snap) return;
            this.history = this.history.slice(0, this.historyIndex + 1);
            this.history.push(snap);
            if (this.history.length > 35) this.history.shift();
            this.historyIndex = this.history.length - 1;
        },

        applySnapshot(snapStr) {
            if (!snapStr) return;
            try {
                this._skipHistory = true;
                const parsed = JSON.parse(snapStr);
                if (parsed.meta) this.meta = parsed.meta;
                if (parsed.settings) this.settings = parsed.settings;
                if (Array.isArray(parsed.items)) {
                    this.items = parsed.items.map(hydrateItem);
                    if (!this.items.some((i) => i.id === this.activeId)) {
                        this.activeId = this.items[0]?.id || null;
                    }
                }
            } finally {
                this.$nextTick(() => {
                    this._skipHistory = false;
                    window.initLucide?.();
                });
            }
        },

        undo() {
            if (this.historyIndex <= 0) return;
            this.historyIndex--;
            this.applySnapshot(this.history[this.historyIndex]);
        },

        redo() {
            if (this.historyIndex >= this.history.length - 1) return;
            this.historyIndex++;
            this.applySnapshot(this.history[this.historyIndex]);
        },

        selectHeader() {
            this.selectedTarget = 'header';
            this.activeId = null;
            this.items.forEach((it) => {
                it._menuOpen = false;
            });
        },

        selectItem(id) {
            this.selectedTarget = 'item';
            this.activeId = id;
            this.items.forEach((it) => {
                if (it.id !== id) it._menuOpen = false;
            });
        },

        activeItem() {
            return this.items.find((it) => it.id === this.activeId) || null;
        },

        activeItemIndex() {
            return this.items.findIndex((it) => it.id === this.activeId);
        },

        activeIndex() {
            const idx = this.items.findIndex((it) => it.id === this.activeId);
            return idx >= 0 ? idx : this.items.length - 1;
        },

        addItem(type = 'radio') {
            this.pushHistory();
            const isInput = this.isInput(type);
            const newId = uid(isInput ? 'q' : 'blk');
            const labelMap = {
                section: `Bagian ${this.totalSections() + 1}: Tanpa Judul`,
                title_block: 'Judul Informasi',
                image_block: 'Judul Gambar',
                video_block: 'Judul Video',
            };
            const item = hydrateItem({
                id: newId,
                label: labelMap[type] || 'Pertanyaan Tanpa Judul',
                help: '',
                type,
                required: isInput ? !!this.settings.default_required : false,
                options: CHOICE_TYPES.includes(type) ? ['Opsi 1', 'Opsi 2'] : [],
                config: {
                    ...defaultConfigFor(type),
                    ...(isInput && this.settings.is_quiz ? { points: Number(this.settings.default_points || 10) } : {}),
                },
            });

            const insertAt = this.activeIndex() + 1;
            this.items.splice(insertAt, 0, item);
            this.selectedTarget = 'item';
            this.activeId = newId;
            this.mobilePaletteOpen = false;
            this.pushHistory();
            this.$nextTick(() => {
                window.initLucide?.();
                document.getElementById(`gf-card-${newId}`)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            });
        },

        duplicateItem(idx) {
            const src = this.items[idx];
            if (!src) return;
            this.pushHistory();
            const clone = JSON.parse(JSON.stringify(src));
            clone.id = uid(this.isInput(src.type) ? 'q' : 'blk');
            clone.label = src.label ? `${src.label} (salinan)` : '';
            clone._menuOpen = false;
            this.items.splice(idx + 1, 0, clone);
            this.activeId = clone.id;
            this.pushHistory();
            this.$nextTick(() => window.initLucide?.());
        },

        removeItem(idx) {
            const target = this.items[idx];
            if (!target) return;
            if (this.isInput(target.type) && this.inputCount() <= 1) {
                this.$dispatch('toast', { message: 'Formulir wajib memiliki minimal satu pertanyaan.', tone: 'error' });
                return;
            }
            this.pushHistory();
            this.items.splice(idx, 1);
            const next = this.items[Math.min(idx, this.items.length - 1)];
            this.activeId = next?.id || null;
            this.pushHistory();
        },

        moveItem(idx, delta) {
            const target = idx + delta;
            if (target < 0 || target >= this.items.length) return;
            this.pushHistory();
            const copy = [...this.items];
            [copy[idx], copy[target]] = [copy[target], copy[idx]];
            this.items = copy;
            this.pushHistory();
        },

        mergeSectionUp(itemIdx) {
            if (this.items[itemIdx]?.type !== 'section') return;
            this.pushHistory();
            this.items.splice(itemIdx, 1);
            this.pushHistory();
        },

        duplicateSection(secStartItemIdx) {
            this.pushHistory();
            const chunk = [];
            for (let i = Math.max(0, secStartItemIdx); i < this.items.length; i++) {
                if (i > secStartItemIdx && this.items[i].type === 'section') break;
                const clone = JSON.parse(JSON.stringify(this.items[i]));
                clone.id = uid(this.isInput(clone.type) ? 'q' : 'blk');
                if (i === secStartItemIdx) clone.label = `${clone.label || 'Bagian'} (Salinan)`;
                chunk.push(clone);
            }
            const insertAt = Math.max(0, secStartItemIdx) + chunk.length;
            this.items.splice(insertAt, 0, ...chunk);
            this.pushHistory();
        },

        deleteSection(secStartItemIdx) {
            const chunkLen = (() => {
                let len = 0;
                for (let i = secStartItemIdx; i < this.items.length; i++) {
                    if (i > secStartItemIdx && this.items[i].type === 'section') break;
                    len++;
                }
                return len;
            })();
            const remainingInputs = this.items.filter((it, idx) =>
                (idx < secStartItemIdx || idx >= secStartItemIdx + chunkLen) && this.isInput(it.type)
            ).length;
            if (remainingInputs < 1) {
                this.$dispatch('toast', { message: 'Formulir wajib menyisakan minimal satu pertanyaan.', tone: 'error' });
                return;
            }
            this.pushHistory();
            this.items.splice(secStartItemIdx, chunkLen);
            this.pushHistory();
        },

        onTypeChange(item) {
            this.pushHistory();
            if (CHOICE_TYPES.includes(item.type) && (!Array.isArray(item.options) || !item.options.length)) {
                item.options = ['Opsi 1', 'Opsi 2'];
            }
            const defaults = defaultConfigFor(item.type);
            const existingCfg = item.config && typeof item.config === 'object' && !Array.isArray(item.config) ? item.config : {};
            const merged = {
                image_url: '',
                image_caption: '',
                image_align: 'center',
                ...defaults,
                ...existingCfg,
            };
            if (CHOICE_TYPES.includes(item.type)) {
                merged.option_images = merged.option_images && typeof merged.option_images === 'object' && !Array.isArray(merged.option_images)
                    ? { ...merged.option_images }
                    : {};
                merged.branching = merged.branching && typeof merged.branching === 'object' && !Array.isArray(merged.branching)
                    ? { ...merged.branching }
                    : {};
            }
            item.config = merged;
            if (GRID_TYPES.includes(item.type)) {
                if (!Array.isArray(item.config.grid_rows) || !item.config.grid_rows.length) {
                    item.config.grid_rows = ['Baris 1', 'Baris 2'];
                }
                if (!Array.isArray(item.config.grid_cols) || !item.config.grid_cols.length) {
                    item.config.grid_cols = ['Kurang', 'Cukup', 'Baik', 'Sangat Baik'];
                }
            }
            this.$nextTick(() => window.initLucide?.());
        },

        onDragStart(idx, event) {
            this.dragIndex = idx;
            if (event?.dataTransfer) {
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', String(idx));
            }
        },

        onDragOver(idx, event) {
            if (this.dragIndex === null || this.dragIndex === idx) return;
            event.preventDefault();
            this.dragOverIndex = idx;
        },

        onDrop(idx, event) {
            event.preventDefault();
            const from = this.dragIndex;
            this.dragOverIndex = null;
            this.dragIndex = null;
            if (from === null || from === idx) return;
            this.pushHistory();
            const copy = [...this.items];
            const [moved] = copy.splice(from, 1);
            copy.splice(idx, 0, moved);
            this.items = copy;
            this.pushHistory();
        },

        onDragEnd() {
            this.dragIndex = null;
            this.dragOverIndex = null;
        },

        addOption(item) {
            if (!Array.isArray(item.options)) item.options = [];
            item.options.push(`Opsi ${item.options.length + 1}`);
        },

        removeOption(item, oIdx) {
            if (!Array.isArray(item.options) || item.options.length <= 1) return;
            item.options.splice(oIdx, 1);
        },

        addGridRow(item) {
            if (!Array.isArray(item.config.grid_rows)) item.config.grid_rows = [];
            item.config.grid_rows.push(`Baris ${item.config.grid_rows.length + 1}`);
        },

        removeGridRow(item, rIdx) {
            if (!Array.isArray(item.config.grid_rows) || item.config.grid_rows.length <= 1) return;
            item.config.grid_rows.splice(rIdx, 1);
        },

        addGridCol(item) {
            if (!Array.isArray(item.config.grid_cols)) item.config.grid_cols = [];
            item.config.grid_cols.push(`Kolom ${item.config.grid_cols.length + 1}`);
        },

        removeGridCol(item, cIdx) {
            if (!Array.isArray(item.config.grid_cols) || item.config.grid_cols.length <= 1) return;
            item.config.grid_cols.splice(cIdx, 1);
        },

        toggleValidation(item) {
            if (!item.config.validation || typeof item.config.validation !== 'object') {
                item.config.validation = {
                    enabled: true,
                    category: item.type === 'checkbox' ? 'checkbox' : (item.type === 'number' ? 'number' : 'text'),
                    operator: item.type === 'checkbox' ? 'min_select' : (item.type === 'number' ? 'gte' : 'contains'),
                    value: '',
                    value2: '',
                    message: '',
                };
            } else {
                item.config.validation.enabled = !item.config.validation.enabled;
            }
            item._menuOpen = false;
        },

        toggleFileTypeGroup(item, groupKey) {
            if (!Array.isArray(item.config.file_types)) item.config.file_types = [];
            const idx = item.config.file_types.indexOf(groupKey);
            if (idx >= 0) {
                item.config.file_types.splice(idx, 1);
            } else {
                item.config.file_types.push(groupKey);
            }
        },

        scaleRange(cfg) {
            const min = Number(cfg?.scale_min ?? 1);
            const max = Math.max(min + 1, Number(cfg?.scale_max ?? 5));
            const arr = [];
            for (let n = min; n <= max; n++) arr.push(n);
            return arr;
        },

        ratingRange(cfg) {
            const max = Math.max(3, Math.min(10, Number(cfg?.rating_max ?? 5)));
            const arr = [];
            for (let n = 1; n <= max; n++) arr.push(n);
            return arr;
        },

        parseYoutubeEmbed(url) {
            const str = String(url || '').trim();
            if (!str) return '';
            const m = str.match(/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([A-Za-z0-9_-]{6,})/);
            if (m && m[1]) {
                return `https://www.youtube-nocookie.com/embed/${m[1]}`;
            }
            return '';
        },

        setItemImage(item, url) {
            if (!item) return;
            item.config = { ...(item.config || {}), image_url: String(url || '') };
            this.hasChanges = true;
            this.pushHistory();
        },

        clearItemImage(item) {
            if (!item) return;
            item.config = { ...(item.config || {}), image_url: '', image_caption: '' };
            this.hasChanges = true;
            this.pushHistory();
        },

        setOptionImage(item, optKey, url) {
            if (!item || !optKey) return;
            const nextMap = { ...(item.config?.option_images || {}), [optKey]: String(url || '') };
            item.config = { ...(item.config || {}), option_images: nextMap };
            this.hasChanges = true;
            this.pushHistory();
        },

        clearOptionImage(item, optKey) {
            if (!item || !optKey) return;
            const nextMap = { ...(item.config?.option_images || {}) };
            delete nextMap[optKey];
            item.config = { ...(item.config || {}), option_images: nextMap };
            this.hasChanges = true;
            this.pushHistory();
        },

        syncOptionImageKey(item, oldKey, newKey) {
            const prev = String(oldKey || '').trim();
            const next = String(newKey || '').trim();
            if (!item || !prev || !next || prev === next) return;
            const map = item.config?.option_images;
            if (!map || !map[prev]) return;
            const nextMap = { ...map, [next]: map[prev] };
            delete nextMap[prev];
            item.config = { ...(item.config || {}), option_images: nextMap };
            this.hasChanges = true;
        },

        uploadQuestionImage(event, targetItem = null) {
            const item = targetItem || this.activeItem();
            const inputEl = event?.target || null;
            const file = inputEl?.files?.[0] || null;
            if (!item || !file) return;
            this.uploadImageFile(file, `${item.id}_iq`, (url) => this.setItemImage(item, url), inputEl);
        },

        uploadBlockImage(event) {
            const item = this.activeItem();
            const inputEl = event?.target || null;
            const file = inputEl?.files?.[0] || null;
            if (!item || !file) return;
            this.uploadImageFile(file, item.id, (url) => this.setItemImage(item, url), inputEl);
        },

        uploadOptionImage(item, oIdx, event) {
            const inputEl = event?.target || null;
            const file = inputEl?.files?.[0] || null;
            if (!item || !file) return;
            const optKey = String(item.options?.[oIdx] ?? '').trim() || `Opsi ${oIdx + 1}`;
            this.uploadImageFile(file, `${item.id}_oimg_${oIdx}`, (url) => this.setOptionImage(item, optKey, url), inputEl);
        },

        uploadHeaderImage(event) {
            const inputEl = event?.target || null;
            const file = inputEl?.files?.[0] || null;
            if (!file) return;
            this.uploadImageFile(file, 'header_banner', (url) => {
                this.settings = {
                    ...this.settings,
                    theme: { ...(this.settings.theme || {}), header_image: url },
                };
                this.hasChanges = true;
                this.pushHistory();
            }, inputEl);
        },

        async uploadImageFile(file, targetKey, onSuccess, inputEl = null) {
            if (!file) return;
            this.uploadError = '';
            if (file.size <= 0) {
                this.uploadError = 'Berkas gambar kosong (0 byte).';
                this.$dispatch('toast', { message: this.uploadError, tone: 'error' });
                if (inputEl) inputEl.value = '';
                return;
            }
            this.uploadingTarget = targetKey;
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

                const sendImageWithChunkSize = async (chunkSize) => {
                    const totalChunks = Math.max(1, Math.ceil(file.size / chunkSize));
                    const uploadId = `img_${Date.now()}_${Math.random().toString(36).slice(2, 7)}`;
                    let data = null;

                    for (let cIdx = 0; cIdx < totalChunks; cIdx++) {
                        const start = cIdx * chunkSize;
                        const end = Math.min(file.size, start + chunkSize);
                        const buf = await file.slice(start, end).arrayBuffer();
                        const bytes = new Uint8Array(buf);
                        let binary = '';
                        for (let i = 0; i < bytes.length; i += 0x8000) {
                            binary += String.fromCharCode.apply(null, bytes.subarray(i, i + 0x8000));
                        }

                        const res = await fetch(this.uploadUrl, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'Content-Type': 'application/json',
                                Accept: 'application/json',
                                'X-CSRF-TOKEN': token,
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-Upload-Id': uploadId,
                                'X-Chunk-Index': String(cIdx),
                                'X-Total-Chunks': String(totalChunks),
                                'X-File-Name': encodeURIComponent(file.name),
                            },
                            body: JSON.stringify({
                                upload_id: uploadId,
                                chunk_index: cIdx,
                                total_chunks: totalChunks,
                                file_name: file.name,
                                chunk_b64: btoa(binary),
                            }),
                        });

                        const rawText = await res.text();
                        try {
                            data = JSON.parse(rawText);
                        } catch {
                            const idx = rawText.indexOf('{');
                            try {
                                data = idx >= 0 ? JSON.parse(rawText.slice(idx)) : null;
                            } catch {
                                data = null;
                            }
                        }

                        const hitTempSpill = cIdx === 0 && chunkSize > 8192 && (
                            rawText.includes('Unable to create temporary file')
                            || rawText.includes("POST data can't be buffered")
                            || (res.status === 422 && String(data?.message || '').includes('Potongan gambar kosong'))
                        );

                        if (hitTempSpill) {
                            return null;
                        }

                        if (!res.ok || !data || data.ok === false) {
                            throw new Error(data?.message || `Gagal mengunggah gambar (HTTP ${res.status}).`);
                        }
                    }

                    return data;
                };

                let data = await sendImageWithChunkSize(512 * 1024);
                if (data === null) {
                    data = await sendImageWithChunkSize(8 * 1024);
                }

                if (!data?.url) {
                    throw new Error('URL gambar tidak diterima dari server.');
                }

                onSuccess(data.url);
                this.$dispatch('toast', { message: 'Gambar berhasil diunggah.', tone: 'success' });
            } catch (err) {
                this.uploadError = err.message || 'Gagal mengunggah gambar.';
                this.$dispatch('toast', { message: this.uploadError, tone: 'error' });
            } finally {
                this.uploadingTarget = null;
                if (inputEl) inputEl.value = '';
            }
        },

        applyRichFormat(obj, key, style) {
            const val = String(obj[key] || '');
            const map = {
                bold: ['**', '**'],
                italic: ['*', '*'],
                underline: ['__', '__'],
                link: ['[', '](https://unusa.ac.id)'],
            };
            const wrap = map[style];
            if (!wrap) return;
            obj[key] = val ? `${val} ${wrap[0]}teks${wrap[1]}`.trim() : `${wrap[0]}teks${wrap[1]}`;
        },

        // Import Questions Drawer
        currentImportForm() {
            return this.importableForms.find((f) => f.code === this.importSelectedFormCode) || null;
        },

        filteredImportForms() {
            const q = this.importSearch.trim().toLowerCase();
            if (!q) return this.importableForms;
            return this.importableForms.filter((f) =>
                `${f.code} ${f.title} ${f.category}`.toLowerCase().includes(q)
            );
        },

        toggleImportField(fid) {
            const idx = this.importSelectedIds.indexOf(fid);
            if (idx >= 0) this.importSelectedIds.splice(idx, 1);
            else this.importSelectedIds.push(fid);
        },

        importSelectedQuestions() {
            const srcForm = this.currentImportForm();
            if (!srcForm || !this.importSelectedIds.length) return;
            this.pushHistory();
            const picked = (srcForm.items || []).filter((it) => this.importSelectedIds.includes(it.id));
            let insertAt = this.activeIndex() + 1;
            picked.forEach((raw) => {
                const cloned = hydrateItem({
                    ...JSON.parse(JSON.stringify(raw)),
                    id: uid(this.isInput(raw.type) ? 'q' : 'blk'),
                });
                this.items.splice(insertAt, 0, cloned);
                insertAt++;
            });
            this.pushHistory();
            this.importSelectedIds = [];
            this.importDrawerOpen = false;
            this.$dispatch('toast', { message: `${picked.length} item berhasil diimpor dari ${srcForm.code}.`, tone: 'success' });
            this.$nextTick(() => window.initLucide?.());
        },

        // Responses charts
        renderSummaryChart(qStat) {
            if (!window.SatuDataCharts || !qStat?.series?.length) return '';
            const pal = window.SatuDataCharts.palettes.sky;
            const kind = qStat.chart_kind || 'bar';
            return window.SatuDataCharts.renderSvg(kind, qStat.series, pal, 420, 210);
        },

        // Live Interactive Preview
        openPreview() {
            this.previewMode = true;
            this.pvStep = 0;
            this.pvSubmitted = false;
            this.$nextTick(() => window.initLucide?.());
        },

        pvStepNext() {
            const sections = this.sectionsList();
            const curSec = sections[this.pvStep];
            if (!curSec) return;
            let nextTarget = curSec.afterAction || 'next';
            for (const entry of curSec.items) {
                const it = entry.item;
                if (it.config?.use_branching && it.config?.branching) {
                    const ans = this.pvAnswers[it.id];
                    if (ans && it.config.branching[ans]) {
                        nextTarget = it.config.branching[ans];
                    }
                }
            }
            if (nextTarget === 'submit') {
                this.pvSubmitted = true;
                return;
            }
            const targetIdx = sections.findIndex((s) => s.id === nextTarget);
            if (targetIdx >= 0) {
                this.pvStep = targetIdx;
            } else if (this.pvStep < sections.length - 1) {
                this.pvStep++;
            } else {
                this.pvSubmitted = true;
            }
        },

        // Share & QR helpers
        copyPublicLink() {
            if (!this.publicUrl) return;
            if (navigator.clipboard?.writeText) {
                navigator.clipboard.writeText(this.publicUrl).then(() => {
                    this.$dispatch('toast', { message: 'Tautan formulir disalin ke papan klip.', tone: 'success' });
                });
            }
        },

        embedHtmlCode() {
            if (!this.publicUrl) return '';
            return `<iframe src="${this.publicUrl}" width="${this.embedWidth}" height="${this.embedHeight}" frameborder="0" marginheight="0" marginwidth="0">Memuat…</iframe>`;
        },

        copyEmbedCode() {
            const code = this.embedHtmlCode();
            if (code && navigator.clipboard?.writeText) {
                navigator.clipboard.writeText(code).then(() => {
                    this.$dispatch('toast', { message: 'Kode sematan HTML disalin.', tone: 'success' });
                });
            }
        },

        // Form Serialization & Submit
        serializedFields() {
            return JSON.stringify(
                this.items.map((it) => {
                    const cleanCfg = JSON.parse(JSON.stringify(it.config || {}));
                    if (CHOICE_TYPES.includes(it.type) && Array.isArray(it.options)) {
                        return {
                            id: it.id,
                            label: String(it.label || '').trim(),
                            help: String(it.help || '').trim(),
                            type: it.type,
                            required: !!it.required,
                            options: it.options.map((o) => String(o || '').trim()).filter(Boolean),
                            config: cleanCfg,
                        };
                    }
                    return {
                        id: it.id,
                        label: String(it.label || '').trim(),
                        help: String(it.help || '').trim(),
                        type: it.type,
                        required: this.isInput(it.type) ? !!it.required : false,
                        config: cleanCfg,
                    };
                })
            );
        },

        serializedSettings() {
            return JSON.stringify(this.settings || {});
        },

        saveStudio(afterSaveMode, formEl) {
            this.afterSave = afterSaveMode;
            this.validationError = '';

            if (this.uploadingTarget !== null) {
                this.validationError = 'Mohon tunggu hingga proses unggah gambar selesai sebelum menyimpan formulir.';
                return;
            }
            if (!String(this.meta.title || '').trim()) {
                this.validationError = 'Judul formulir wajib diisi.';
                this.activeTab = 'questions';
                return;
            }
            if (!String(this.meta.category_id || '').trim()) {
                this.validationError = 'Pilih Kategori Data di bagian Setelan / Metadata sebelum menyimpan.';
                return;
            }
            if (!String(this.meta.deadline || '').trim()) {
                this.validationError = 'Tenggat waktu wajib ditentukan.';
                return;
            }
            if (this.inputCount() === 0) {
                this.validationError = 'Tambahkan minimal satu pertanyaan input.';
                this.activeTab = 'questions';
                return;
            }
            for (let i = 0; i < this.items.length; i++) {
                const it = this.items[i];
                if (this.isInput(it.type) && !String(it.label || '').trim()) {
                    this.validationError = `Pertanyaan #${i + 1} belum memiliki judul pertanyaan.`;
                    this.activeTab = 'questions';
                    this.activeId = it.id;
                    return;
                }
                if (this.isChoice(it.type)) {
                    const validOpts = (it.options || []).map((o) => String(o || '').trim()).filter(Boolean);
                    if (!validOpts.length) {
                        this.validationError = `Pertanyaan pilihan "${it.label}" wajib memiliki minimal satu opsi.`;
                        this.activeTab = 'questions';
                        this.activeId = it.id;
                        return;
                    }
                }
                if (this.isGrid(it.type)) {
                    const rows = (it.config?.grid_rows || []).filter((r) => String(r || '').trim() !== '');
                    const cols = (it.config?.grid_cols || []).filter((c) => String(c || '').trim() !== '');
                    if (!rows.length || !cols.length) {
                        this.validationError = `Pertanyaan kisi "${it.label}" wajib memiliki minimal 1 baris dan 1 kolom.`;
                        this.activeTab = 'questions';
                        this.activeId = it.id;
                        return;
                    }
                }
            }

            const setField = (name, val) => {
                const input = formEl.querySelector(`input[name="${name}"]`);
                if (input) input.value = val;
            };
            setField('title', String(this.meta.title ?? ''));
            setField('description', String(this.meta.description ?? ''));
            setField('category_id', String(this.meta.category_id ?? ''));
            setField('tahun', String(this.meta.tahun ?? ''));
            setField('periode', String(this.meta.periode ?? 'Ganjil'));
            setField('target', String(this.meta.target ?? 'Mahasiswa'));
            setField('deadline', String(this.meta.deadline ?? ''));
            setField('batasi_satu_respon', this.meta.batasi_satu_respon ? '1' : '0');
            setField('status_aktif', this.meta.status_aktif ? '1' : '0');
            setField('after_save', String(this.afterSave ?? 'list'));
            setField('fields_json', this.serializedFields());
            setField('settings_json', this.serializedSettings());

            formEl.submit();
        },
    };
}
