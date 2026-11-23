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
            pickedCols: [],
            title: '',
            description: '',
            categoryId: '',
            tahun: @js((string) date('Y')),
            periode: 'Ganjil',
            access: 'private',
            format: 'XLSX',
        },

        openBuilder(defaultFormId = null) {
            if (!this.builderForms.length) return;
            const target = defaultFormId
                ? this.builderForms.find((f) => Number(f.id) === Number(defaultFormId))
                : this.builderForms[0];
            const f = target || this.builderForms[0];
            this.b.formId = String(f.id);
            this.onBuilderFormChange();
            this.b.access = 'private';
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
            return (f.fields || []).filter((x) => x.type === 'select' || x.type === 'text');
        },

        onBuilderFormChange() {
            const f = this.builderCurrentForm();
            if (!f) return;
            const groupable = this.builderGroupable();
            this.b.mode = groupable.length ? 'rekap' : 'rinci';
            this.b.groupFieldId = groupable[0]?.id || '';
            this.b.pickedCols = (f.sourceCols || []).filter((c) => c.id !== '_at').map((c) => c.id);
            this.b.title = `Rekap ${f.title}`;
            this.b.description = f.desc || '';
            this.b.categoryId = String(f.category_id);
            this.b.tahun = String(f.tahun);
            this.b.periode = f.periode;
        },

        builderQualityError() {
            const f = this.builderCurrentForm();
            if (!f) return 'Belum ada formulir tersedia.';
            if (!f.subs.length) return 'Belum ada isian sumber untuk dibuat menjadi dataset.';
            if (f.dirty) return 'Data sumber belum bersih. Perbaiki melalui menu Data Terkumpul sebelum membuat dataset.';
            if (this.b.mode === 'rekap' && !this.builderGroupable().some((x) => x.id === this.b.groupFieldId)) {
                return 'Tidak ada kolom pengelompokan yang valid. Pilih bentuk Data rinci atau kolom pengelompokan.';
            }
            return '';
        },

        builderPreview() {
            const f = this.builderCurrentForm();
            if (!f) return { cols: [], rows: [] };

            if (this.b.mode === 'rekap') {
                const fl = (f.fields || []).find((x) => x.id === this.b.groupFieldId);
                const counts = new Map();
                f.subs.forEach((s) => {
                    const k = (s.values?.[this.b.groupFieldId] || '').trim() || '(kosong)';
                    counts.set(k, (counts.get(k) || 0) + 1);
                });
                (fl?.options || []).forEach((o) => {
                    if (!counts.has(o)) counts.set(o, 0);
                });
                return {
                    cols: [fl?.label || 'Kategori', 'Jumlah'],
                    rows: [...counts.entries()].map(([k, v]) => [k, v]),
                };
            }

            const sc = (f.sourceCols || []).filter((c) => this.b.pickedCols.includes(c.id));
            return {
                cols: sc.map((c) => c.label),
                rows: f.subs.map((s) =>
                    sc.map((c) => {
                        if (c.id === '_nama') return s.nama;
                        if (c.id === '_nim') return s.nim;
                        if (c.id === '_at') return s.at;
                        return s.values?.[c.id] ?? '';
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
