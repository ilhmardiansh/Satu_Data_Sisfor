<script>
function datasetBuilderMixin(builderForms) {
    return {
        builderForms,
        builderOpen: false,
        bTried: false,
        b: {
            formId: '',
            mode: 'rekap',
            groupFieldId: '',
            aggFunc: 'count',
            aggFieldId: '',
            pickedCols: [],
            colAliases: {},
            maskedCols: [],
            title: '',
            description: '',
            categoryId: '',
            tahun: @js((string) date('Y')),
            periode: 'Ganjil',
            access: 'private',
            publishStatus: 'terpublikasi',
            format: 'XLSX',
        },

        openBuilder(defaultFormId = null) {
            if (!this.builderForms.length) return;
            const preferred = defaultFormId
                ? this.builderForms.find((f) => Number(f.id) === Number(defaultFormId))
                : (this.builderForms.find((f) => f.preprocessed && f.validCount > 0) || this.builderForms[0]);
            const f = preferred || this.builderForms[0];
            this.b.formId = String(f.id);
            this.onBuilderFormChange();
            this.b.access = 'private';
            this.b.publishStatus = 'terpublikasi';
            this.b.format = 'XLSX';
            this.bTried = false;
            this.builderOpen = true;
        },

        builderCurrentForm() {
            return this.builderForms.find((f) => String(f.id) === String(this.b.formId)) || null;
        },

        builderGroupable() {
            const f = this.builderCurrentForm();
            if (!f) return [];
            return (f.fields || []).filter((x) => ['select', 'radio', 'checkbox', 'text'].includes(x.type));
        },

        builderNumericFields() {
            const f = this.builderCurrentForm();
            if (!f) return [];
            return (f.fields || []).filter((x) => x.type === 'number');
        },

        onBuilderFormChange() {
            const f = this.builderCurrentForm();
            if (!f) return;
            const groupable = this.builderGroupable();
            const numeric = this.builderNumericFields();
            this.b.mode = groupable.length ? 'rekap' : 'rinci';
            this.b.groupFieldId = groupable[0]?.id || '';
            this.b.aggFunc = 'count';
            this.b.aggFieldId = numeric[0]?.id || '';
            this.b.pickedCols = (f.sourceCols || []).filter((c) => c.id !== '_at').map((c) => c.id);
            this.b.colAliases = {};
            this.b.maskedCols = [];
            this.b.title = groupable.length ? `Rekap ${f.title}` : `Dataset ${f.title}`;
            this.b.description = f.desc || '';
            this.b.categoryId = String(f.category_id);
            this.b.tahun = String(f.tahun);
            this.b.periode = f.periode;
        },

        builderQualityError() {
            const f = this.builderCurrentForm();
            if (!f) return 'Belum ada formulir tersedia. Buat instrumen formulir terlebih dahulu.';
            if (!f.totalCount) return 'Belum ada respons sumber pada formulir ini.';
            if (!f.preprocessed) return 'Formulir ini belum menyelesaikan Finalisasi Pre-processing.';
            if (!f.validCount) return 'Belum ada baris berstatus valid hasil pre-processing.';
            if (f.dirty) return 'Data sumber belum bersih. Perbaiki melalui menu Pre-processing Data.';
            if (this.b.mode === 'rekap' && !this.builderGroupable().some((x) => x.id === this.b.groupFieldId)) {
                return 'Tidak ada kolom pengelompokan yang valid. Gunakan mode Tabel Rinci.';
            }
            if (this.b.mode === 'rekap' && this.b.aggFunc !== 'count' && !this.b.aggFieldId) {
                return 'Pilih kolom angka target untuk fungsi agregasi.';
            }
            return '';
        },

        maskValue(cid, val, idx) {
            if (cid === '_nama') return 'Responden #' + String(idx + 1).padStart(3, '0');
            const s = String(val ?? '').trim();
            if (!s || s === '-') return s;
            if (s.length <= 5) return s.slice(0, 1) + '*'.repeat(Math.max(3, s.length - 1));
            return s.slice(0, 3) + '***' + s.slice(-2);
        },

        builderPreview() {
            const f = this.builderCurrentForm();
            if (!f) return { cols: [], rows: [] };

            if (this.b.mode === 'rekap') {
                const fl = (f.fields || []).find((x) => x.id === this.b.groupFieldId);
                const aggField = (f.fields || []).find((x) => x.id === this.b.aggFieldId);
                const buckets = new Map();

                f.subs.forEach((s) => {
                    const raw = String(s.values?.[this.b.groupFieldId] || '').trim();
                    let keys = [];
                    if (fl?.type === 'checkbox' && raw) {
                        keys = raw.split(',').map((x) => x.trim()).filter(Boolean);
                    }
                    if (!keys.length) keys = [raw || '(kosong)'];

                    const rawMetric = this.b.aggFieldId ? window.SatuDataCharts?.parseNum(s.values?.[this.b.aggFieldId]) : null;
                    keys.forEach((k) => {
                        if (!buckets.has(k)) buckets.set(k, { count: 0, nums: [] });
                        const bkt = buckets.get(k);
                        bkt.count++;
                        if (rawMetric !== null && Number.isFinite(rawMetric)) bkt.nums.push(rawMetric);
                    });
                });

                (fl?.options || []).forEach((o) => {
                    if (!buckets.has(o)) buckets.set(o, { count: 0, nums: [] });
                });

                const rows = [...buckets.entries()].map(([k, bkt]) => {
                    if (this.b.aggFunc === 'count') return [k, bkt.count];
                    if (!bkt.nums.length) return [k, 0];
                    let v = 0;
                    if (this.b.aggFunc === 'sum') v = bkt.nums.reduce((a, c) => a + c, 0);
                    if (this.b.aggFunc === 'avg') v = bkt.nums.reduce((a, c) => a + c, 0) / bkt.nums.length;
                    if (this.b.aggFunc === 'min') v = Math.min(...bkt.nums);
                    if (this.b.aggFunc === 'max') v = Math.max(...bkt.nums);
                    return [k, Math.round(v * 100) / 100];
                });

                const metricLabel = {
                    count: 'Jumlah',
                    sum: `Total ${aggField?.label || 'Nilai'}`,
                    avg: `Rata-rata ${aggField?.label || 'Nilai'}`,
                    min: `Minimum ${aggField?.label || 'Nilai'}`,
                    max: `Maksimum ${aggField?.label || 'Nilai'}`,
                }[this.b.aggFunc] || 'Jumlah';

                return {
                    cols: [fl?.label || 'Kategori', metricLabel],
                    rows,
                };
            }

            const sc = (f.sourceCols || []).filter((c) => this.b.pickedCols.includes(c.id));
            return {
                cols: sc.map((c) => String(this.b.colAliases[c.id] || '').trim() || c.label),
                rows: f.subs.map((s, idx) =>
                    sc.map((c) => {
                        let raw = '';
                        if (c.id === '_nama') raw = s.nama;
                        else if (c.id === '_nim') raw = s.nim;
                        else if (c.id === '_at') raw = s.at;
                        else raw = s.values?.[c.id] ?? '';
                        return this.b.maskedCols.includes(c.id) ? this.maskValue(c.id, raw, idx) : raw;
                    })
                ),
            };
        },

        builderPreviewChart() {
            if (!window.SatuDataCharts) return '';
            const out = this.builderPreview();
            const data = out.rows.map((r) => ({ n: String(r[0]), v: Number(r[1]) || 0 }));
            return window.SatuDataCharts.renderSvg('bar', data, window.SatuDataCharts.palettes.sky, 320, 170);
        },

        submitBuilder(ev) {
            this.bTried = true;
            const out = this.builderPreview();
            if (
                this.builderQualityError() ||
                !this.b.title.trim() ||
                !this.b.categoryId ||
                !/^\d{4}$/.test(this.b.tahun) ||
                !out.cols.length
            ) {
                ev.preventDefault();
            }
        },
    };
}
</script>
