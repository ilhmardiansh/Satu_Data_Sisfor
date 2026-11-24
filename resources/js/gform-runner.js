function parseJsonResponse(rawText) {
    try {
        return JSON.parse(rawText);
    } catch {
        // Fallback: extract embedded JSON error message if PHP prepended a warning banner
        const idx = String(rawText || '').indexOf('{');
        if (idx >= 0) {
            try {
                return JSON.parse(rawText.slice(idx));
            } catch {
                return null;
            }
        }
        return null;
    }
}

function arrayBufferToBase64(buffer) {
    const bytes = new Uint8Array(buffer);
    const step = 0x8000;
    let binary = '';
    for (let i = 0; i < bytes.length; i += step) {
        binary += String.fromCharCode.apply(null, bytes.subarray(i, i + step));
    }
    return btoa(binary);
}

export async function uploadFileInChunks({ url, file, fieldId = '', csrf = '', onProgress = null }) {
    if (!file || file.size <= 0) {
        throw new Error(`Berkas "${file?.name || 'pilihan'}" kosong (0 byte) dan tidak dapat diunggah.`);
    }

    const sendWithChunkSize = async (chunkSize) => {
        const totalChunks = Math.max(1, Math.ceil(file.size / chunkSize));
        const uploadId = `u_${Date.now()}_${Math.random().toString(36).slice(2, 8)}`;
        let finalData = null;

        for (let cIdx = 0; cIdx < totalChunks; cIdx++) {
            const start = cIdx * chunkSize;
            const end = Math.min(file.size, start + chunkSize);
            const slice = file.slice(start, end);
            const buffer = await slice.arrayBuffer();
            const chunkB64 = arrayBufferToBase64(buffer);

            const res = await fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-Field-Id': String(fieldId || ''),
                    'X-Upload-Id': uploadId,
                    'X-Chunk-Index': String(cIdx),
                    'X-Total-Chunks': String(totalChunks),
                    'X-File-Name': encodeURIComponent(file.name),
                },
                body: JSON.stringify({
                    field_id: fieldId,
                    upload_id: uploadId,
                    chunk_index: cIdx,
                    total_chunks: totalChunks,
                    file_name: file.name,
                    chunk_b64: chunkB64,
                }),
            });

            const rawText = await res.text();
            const data = parseJsonResponse(rawText);

            const hitTempSpill = cIdx === 0 && chunkSize > 8192 && (
                rawText.includes('Unable to create temporary file')
                || rawText.includes("POST data can't be buffered")
                || (res.status === 422 && String(data?.message || '').includes('Potongan berkas kosong'))
            );

            if (hitTempSpill) {
                return null;
            }

            if (!res.ok || !data || data.ok !== true) {
                const err = new Error(data?.message || `Gagal mengunggah "${file.name}" (HTTP ${res.status}).`);
                err.reloadRequired = Boolean(data?.reload_required);
                throw err;
            }

            if (typeof onProgress === 'function') {
                onProgress(end - start, file.size);
            }

            finalData = data;
        }

        return finalData;
    };

    // Prefer 512 KB chunks; automatically fall back to 8 KB (~11 KB JSON) if server PHP CLI hits temp-dir spill
    let finalData = await sendWithChunkSize(512 * 1024);
    if (finalData === null) {
        finalData = await sendWithChunkSize(8 * 1024);
    }

    if (
        !finalData
        || finalData.completed !== true
        || typeof finalData.token !== 'string'
        || !finalData.token
        || Number(finalData.size || 0) <= 0
    ) {
        throw new Error('Server tidak mengembalikan token berkas yang valid. Coba unggah ulang.');
    }

    return finalData;
}

export function publicGFormRunner() {
    const el = document.getElementById('gform-runner-data');
    let payload = {};
    try {
        payload = el ? JSON.parse(el.textContent || '{}') : {};
    } catch {
        payload = {};
    }

    const sections = Array.isArray(payload.sections) && payload.sections.length
        ? payload.sections
        : [{ id: '__default__', title: '', after_action: 'next', items: [] }];

    const initialAnswers = {};
    const initialOther = {};
    const oldValues = payload.oldValues || {};
    const fields = Array.isArray(payload.fields) ? payload.fields : [];

    fields.forEach((fld) => {
        const fid = fld.id;
        const val = oldValues[fid];
        if (val !== undefined && val !== null) {
            initialAnswers[fid] = val;
        }
    });

    return {
        sections,
        fields,
        settings: payload.settings || {},
        uploadChunkUrl: payload.uploadChunkUrl || '',
        canSubmit: !!payload.canSubmit,
        currentSectionIdx: 0,
        visitedHistory: [0],
        sectionError: '',
        clientErrors: payload.errors || {},
        fileLabels: { ...(payload.oldUploadedLabels || {}) },
        uploadedTokens: { ...(payload.oldUploadedTokens || {}) },
        uploadProgress: {},
        uploadErrors: {},
        uploadReloadNeeded: {},
        uploadingFields: {},
        answers: initialAnswers,
        otherText: initialOther,
        gridValues: {},
        hoverRatings: {},

        init() {
            // Hydrate existing grid values when editing or after validation redirect
            this.fields.forEach((fld) => {
                if (fld.type === 'mc_grid' || fld.type === 'checkbox_grid') {
                    const existing = oldValues[fld.id];
                    this.gridValues[fld.id] = {};
                    if (existing && typeof existing === 'object') {
                        this.gridValues[fld.id] = existing;
                    } else if (typeof existing === 'string' && existing.trim() !== '') {
                        const rows = fld.config?.grid_rows || [];
                        existing.split(';').forEach((seg) => {
                            if (!seg.includes(':')) return;
                            const [rLabel, colStr] = seg.split(':', 2);
                            const rIdx = rows.findIndex((r) => r.trim() === rLabel.trim());
                            if (rIdx >= 0) {
                                const cols = colStr.split(',').map((s) => s.trim()).filter(Boolean);
                                this.gridValues[fld.id][rIdx] = fld.type === 'mc_grid' ? (cols[0] || '') : cols;
                            }
                        });
                    }
                }
            });

            // Jump to first section containing a server-side validation error if any
            const errKeys = Object.keys(this.clientErrors || {});
            if (errKeys.length) {
                const targetIdx = this.sections.findIndex((sec) =>
                    (sec.fieldIds || []).some((fid) => errKeys.includes(fid))
                );
                if (targetIdx >= 0) {
                    this.currentSectionIdx = targetIdx;
                }
            }

            this.$nextTick(() => window.initLucide?.());
        },

        totalSections() {
            return this.sections.length;
        },

        progressPct() {
            if (this.sections.length <= 1) return 100;
            return Math.round(((this.currentSectionIdx + 1) / this.sections.length) * 100);
        },

        activeSection() {
            return this.sections[this.currentSectionIdx] || this.sections[0];
        },

        resolvedTargetAction() {
            const sec = this.activeSection();
            let target = sec.after_action || 'next';
            (sec.branchingFields || []).forEach((bf) => {
                const chosen = this.answers[bf.id];
                if (chosen && bf.branching && bf.branching[chosen]) {
                    target = bf.branching[chosen];
                }
            });
            return target;
        },

        isLastStep() {
            if (this.sections.length <= 1) return true;
            const action = this.resolvedTargetAction();
            if (action === 'submit') return true;
            if (action === 'next') return this.currentSectionIdx >= this.sections.length - 1;
            const targetIdx = this.sections.findIndex((s) => s.id === action);
            return targetIdx < 0 && this.currentSectionIdx >= this.sections.length - 1;
        },

        enforceGridColLimit(fldId, rowIdx, colVal, isCheckboxGrid) {
            const fld = this.fields.find((f) => f.id === fldId);
            if (!fld || !fld.config?.limit_one_per_col) return;
            const map = this.gridValues[fldId] || {};
            Object.keys(map).forEach((otherRowKey) => {
                if (Number(otherRowKey) === Number(rowIdx)) return;
                if (isCheckboxGrid) {
                    if (Array.isArray(map[otherRowKey])) {
                        map[otherRowKey] = map[otherRowKey].filter((v) => v !== colVal);
                    }
                } else if (map[otherRowKey] === colVal) {
                    map[otherRowKey] = '';
                }
            });
        },

        validateCurrentSection(formEl) {
            this.sectionError = '';
            const sec = this.activeSection();
            const secEl = document.getElementById(`gf-runner-sec-${this.currentSectionIdx}`);
            if (!secEl) return true;

            const sectionFieldIds = sec.fieldIds || [];
            for (const fid of sectionFieldIds) {
                const fld = this.fields.find((f) => f.id === fid);
                if (!fld) continue;

                if (fld.type === 'mc_grid' || fld.type === 'checkbox_grid') {
                    if (fld.required || fld.config?.require_row_response) {
                        const rows = fld.config?.grid_rows || [];
                        for (let rIdx = 0; rIdx < rows.length; rIdx++) {
                            const val = this.gridValues[fid]?.[rIdx];
                            const hasVal = Array.isArray(val) ? val.length > 0 : !!String(val || '').trim();
                            if (!hasVal) {
                                this.sectionError = `Pertanyaan "${fld.label}": baris "${rows[rIdx]}" wajib diisi.`;
                                document.getElementById(`gf-q-${fid}`)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                return false;
                            }
                        }
                    }
                    continue;
                }

                if (!fld.required) continue;

                if (fld.type === 'file') {
                    const input = formEl.querySelector(`#fld_${fid}`);
                    const hasExisting = input?.dataset?.hasExisting === '1';
                    const tok = String(this.uploadedTokens[fid] || '').trim();
                    const hasToken = tok !== '' && tok !== '[]';
                    if (!hasToken && !hasExisting && (!input?.files || input.files.length === 0)) {
                        this.sectionError = `Lampiran untuk "${fld.label}" wajib diunggah.`;
                        document.getElementById(`gf-q-${fid}`)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        return false;
                    }
                    continue;
                }

                if (fld.type === 'checkbox') {
                    const checked = secEl.querySelectorAll(`input[name="values[${fid}][]"]:checked`);
                    if (checked.length === 0) {
                        this.sectionError = `Pertanyaan "${fld.label}" wajib memilih minimal satu opsi.`;
                        document.getElementById(`gf-q-${fid}`)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        return false;
                    }
                    continue;
                }

                if (fld.type === 'radio') {
                    const checked = secEl.querySelector(`input[name="values[${fid}]"]:checked`);
                    if (!checked) {
                        this.sectionError = `Pertanyaan "${fld.label}" wajib diisi.`;
                        document.getElementById(`gf-q-${fid}`)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        return false;
                    }
                    if (checked.value === '__other__' && !String(this.otherText[fid] || '').trim()) {
                        this.sectionError = `Isi keterangan pilihan "Lainnya" pada "${fld.label}".`;
                        document.getElementById(`gf-q-${fid}`)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        return false;
                    }
                    continue;
                }

                const el = secEl.querySelector(`[name="values[${fid}]"]`);
                if (el && !String(el.value || '').trim()) {
                    this.sectionError = `Pertanyaan "${fld.label}" wajib diisi.`;
                    el.focus?.();
                    document.getElementById(`gf-q-${fid}`)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return false;
                }
            }

            return true;
        },

        nextSection(formEl) {
            if (!this.validateCurrentSection(formEl)) return;
            const target = this.resolvedTargetAction();
            let nextIdx = this.currentSectionIdx + 1;

            if (target !== 'next' && target !== 'submit') {
                const found = this.sections.findIndex((s) => s.id === target);
                if (found >= 0) nextIdx = found;
            }

            if (nextIdx < this.sections.length) {
                this.visitedHistory.push(nextIdx);
                this.currentSectionIdx = nextIdx;
                this.sectionError = '';
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        },

        prevSection() {
            if (this.visitedHistory.length > 1) {
                this.visitedHistory.pop();
                this.currentSectionIdx = this.visitedHistory[this.visitedHistory.length - 1];
            } else if (this.currentSectionIdx > 0) {
                this.currentSectionIdx--;
            }
            this.sectionError = '';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        formatBytes(bytes) {
            const b = Number(bytes || 0);
            if (b < 1024) return `${b} B`;
            if (b < 1024 * 1024) return `${(b / 1024).toFixed(1).replace('.', ',')} KB`;
            if (b < 1024 * 1024 * 1024) return `${(b / (1024 * 1024)).toFixed(1).replace('.', ',')} MB`;
            return `${(b / (1024 * 1024 * 1024)).toFixed(2).replace('.', ',')} GB`;
        },

        async handleFileSelect(fid, event, maxCount = 1, maxMb = 0) {
            const input = event?.target;
            const files = Array.from(input?.files || []);
            this.uploadErrors = { ...this.uploadErrors, [fid]: '' };
            this.uploadReloadNeeded = { ...this.uploadReloadNeeded, [fid]: false };

            if (!files.length) return;
            if (!this.uploadChunkUrl) {
                this.fileLabels = { ...this.fileLabels, [fid]: files.map((f) => f.name).join(', ') };
                return;
            }

            const emptyFile = files.find((f) => !f.size || f.size <= 0);
            if (emptyFile) {
                this.uploadErrors = { ...this.uploadErrors, [fid]: `Berkas "${emptyFile.name}" kosong (0 byte) dan tidak dapat diunggah.` };
                input.value = '';
                return;
            }

            const limitCount = Math.max(1, Number(maxCount || 1));
            if (files.length > limitCount) {
                this.uploadErrors = { ...this.uploadErrors, [fid]: `Jumlah berkas maksimal ${limitCount} file.` };
                input.value = '';
                return;
            }

            const limitMb = Math.max(0, Number(maxMb || 0));
            if (limitMb > 0) {
                const tooBig = files.find((f) => f.size > limitMb * 1024 * 1024);
                if (tooBig) {
                    this.uploadErrors = { ...this.uploadErrors, [fid]: `Ukuran "${tooBig.name}" melebihi batas maksimal ${limitMb} MB.` };
                    input.value = '';
                    return;
                }
            }

            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const totalBytes = files.reduce((acc, f) => acc + Math.max(1, f.size), 0);
            let sentBytes = 0;

            this.uploadingFields = { ...this.uploadingFields, [fid]: true };
            this.uploadProgress = { ...this.uploadProgress, [fid]: 1 };

            try {
                const tokens = [];
                const names = [];

                for (const file of files) {
                    const finalData = await uploadFileInChunks({
                        url: this.uploadChunkUrl,
                        file,
                        fieldId: fid,
                        csrf,
                        onProgress: (deltaBytes) => {
                            sentBytes += Math.max(1, deltaBytes);
                            this.uploadProgress = {
                                ...this.uploadProgress,
                                [fid]: Math.min(99, Math.round((sentBytes / totalBytes) * 100)),
                            };
                        },
                    });

                    tokens.push(finalData.token);
                    names.push(`${file.name} (${this.formatBytes(file.size)})`);
                }

                if (tokens.length !== files.length || tokens.length === 0) {
                    throw new Error('Sebagian berkas gagal menghasilkan token unggahan.');
                }

                const tokenVal = tokens.length === 1 ? tokens[0] : JSON.stringify(tokens);
                this.uploadedTokens = { ...this.uploadedTokens, [fid]: tokenVal };
                this.fileLabels = { ...this.fileLabels, [fid]: names.join(', ') };
                this.uploadProgress = { ...this.uploadProgress, [fid]: 100 };
                // Clear native file input so form submit sends encrypted token instead of raw multipart limit
                input.value = '';
            } catch (err) {
                this.uploadErrors = { ...this.uploadErrors, [fid]: err.message || 'Berkas gagal diunggah.' };
                this.uploadReloadNeeded = { ...this.uploadReloadNeeded, [fid]: Boolean(err?.reloadRequired) };
                const nextTokens = { ...this.uploadedTokens };
                delete nextTokens[fid];
                this.uploadedTokens = nextTokens;
                const nextLabels = { ...this.fileLabels };
                delete nextLabels[fid];
                this.fileLabels = nextLabels;
                input.value = '';
            } finally {
                this.uploadingFields = { ...this.uploadingFields, [fid]: false };
            }
        },

        removeUploadedFile(fid) {
            const nextTokens = { ...this.uploadedTokens };
            delete nextTokens[fid];
            this.uploadedTokens = nextTokens;

            const nextLabels = { ...this.fileLabels };
            delete nextLabels[fid];
            this.fileLabels = nextLabels;

            const nextProg = { ...this.uploadProgress };
            delete nextProg[fid];
            this.uploadProgress = nextProg;

            this.uploadErrors = { ...this.uploadErrors, [fid]: '' };
            this.uploadReloadNeeded = { ...this.uploadReloadNeeded, [fid]: false };
            const input = document.getElementById(`fld_${fid}`);
            if (input) input.value = '';
        },

        handleSubmit(event) {
            const formEl = event.target;
            if (Object.values(this.uploadingFields).some(Boolean)) {
                event.preventDefault();
                this.sectionError = 'Mohon tunggu hingga proses unggah berkas selesai sebelum mengirim.';
                return false;
            }
            if (!this.validateCurrentSection(formEl)) {
                event.preventDefault();
                return false;
            }
            Object.entries(this.uploadedTokens).forEach(([fid, tok]) => {
                if (tok && tok !== '[]') {
                    const fileInput = formEl.querySelector(`#fld_${fid}`);
                    if (fileInput) {
                        fileInput.value = '';
                        fileInput.disabled = true;
                    }
                }
            });
            return true;
        },

        clearForm(formEl) {
            if (!window.confirm('Kosongkan seluruh isian formulir?')) return;
            formEl.reset();
            this.answers = {};
            this.otherText = {};
            this.gridValues = {};
            this.fileLabels = {};
            this.uploadedTokens = {};
            this.uploadProgress = {};
            this.uploadErrors = {};
            this.uploadReloadNeeded = {};
            this.uploadingFields = {};
            this.sectionError = '';
            this.currentSectionIdx = 0;
            this.visitedHistory = [0];
        },
    };
}

export function submissionFixEditor() {
    const el = document.getElementById('submission-fix-data');
    let payload = {};
    try {
        payload = el ? JSON.parse(el.textContent || '{}') : {};
    } catch {
        payload = {};
    }

    return {
        uploadChunkUrl: payload.uploadChunkUrl || '',
        fields: Array.isArray(payload.fields) ? payload.fields : [],
        uploadedTokens: { ...(payload.oldUploadedTokens || {}) },
        fileLabels: { ...(payload.oldUploadedLabels || {}) },
        uploadProgress: {},
        uploadErrors: {},
        uploadReloadNeeded: {},
        uploadingFields: {},
        removeExisting: {},
        submitError: '',
        normalizeNotice: '',

        isAnyUploading() {
            return Object.values(this.uploadingFields).some(Boolean);
        },

        formatBytes(bytes) {
            const b = Number(bytes || 0);
            if (b < 1024) return `${b} B`;
            if (b < 1024 * 1024) return `${(b / 1024).toFixed(1).replace('.', ',')} KB`;
            if (b < 1024 * 1024 * 1024) return `${(b / (1024 * 1024)).toFixed(1).replace('.', ',')} MB`;
            return `${(b / (1024 * 1024 * 1024)).toFixed(2).replace('.', ',')} GB`;
        },

        async handleFileSelect(fid, event, maxCount = 1, maxMb = 0) {
            const input = event?.target;
            const files = Array.from(input?.files || []);
            this.uploadErrors = { ...this.uploadErrors, [fid]: '' };
            this.uploadReloadNeeded = { ...this.uploadReloadNeeded, [fid]: false };

            if (!files.length) return;

            const emptyFile = files.find((f) => !f.size || f.size <= 0);
            if (emptyFile) {
                this.uploadErrors = { ...this.uploadErrors, [fid]: `Berkas "${emptyFile.name}" kosong (0 byte) dan tidak dapat diunggah.` };
                input.value = '';
                return;
            }

            const limitCount = Math.max(1, Number(maxCount || 1));
            if (files.length > limitCount) {
                this.uploadErrors = { ...this.uploadErrors, [fid]: `Jumlah berkas maksimal ${limitCount} file.` };
                input.value = '';
                return;
            }

            const limitMb = Math.max(0, Number(maxMb || 0));
            if (limitMb > 0) {
                const tooBig = files.find((f) => f.size > limitMb * 1024 * 1024);
                if (tooBig) {
                    this.uploadErrors = { ...this.uploadErrors, [fid]: `Ukuran "${tooBig.name}" melebihi batas maksimal ${limitMb} MB.` };
                    input.value = '';
                    return;
                }
            }

            if (!this.uploadChunkUrl) {
                this.fileLabels = { ...this.fileLabels, [fid]: files.map((f) => f.name).join(', ') };
                return;
            }

            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const totalBytes = files.reduce((acc, f) => acc + Math.max(1, f.size), 0);
            let sentBytes = 0;

            this.uploadingFields = { ...this.uploadingFields, [fid]: true };
            this.uploadProgress = { ...this.uploadProgress, [fid]: 1 };

            try {
                const tokens = [];
                const names = [];

                for (const file of files) {
                    const finalData = await uploadFileInChunks({
                        url: this.uploadChunkUrl,
                        file,
                        fieldId: fid,
                        csrf,
                        onProgress: (deltaBytes) => {
                            sentBytes += Math.max(1, deltaBytes);
                            this.uploadProgress = {
                                ...this.uploadProgress,
                                [fid]: Math.min(99, Math.round((sentBytes / totalBytes) * 100)),
                            };
                        },
                    });

                    tokens.push(finalData.token);
                    names.push(`${file.name} (${this.formatBytes(file.size)})`);
                }

                if (tokens.length !== files.length || tokens.length === 0) {
                    throw new Error('Sebagian berkas gagal menghasilkan token unggahan.');
                }

                const tokenVal = tokens.length === 1 ? tokens[0] : JSON.stringify(tokens);
                this.uploadedTokens = { ...this.uploadedTokens, [fid]: tokenVal };
                this.fileLabels = { ...this.fileLabels, [fid]: names.join(', ') };
                this.uploadProgress = { ...this.uploadProgress, [fid]: 100 };
                this.removeExisting = { ...this.removeExisting, [fid]: false };
                input.value = '';
            } catch (err) {
                this.uploadErrors = { ...this.uploadErrors, [fid]: err.message || 'Berkas gagal diunggah.' };
                this.uploadReloadNeeded = { ...this.uploadReloadNeeded, [fid]: Boolean(err?.reloadRequired) };
                const nextTokens = { ...this.uploadedTokens };
                delete nextTokens[fid];
                this.uploadedTokens = nextTokens;
                const nextLabels = { ...this.fileLabels };
                delete nextLabels[fid];
                this.fileLabels = nextLabels;
                input.value = '';
            } finally {
                this.uploadingFields = { ...this.uploadingFields, [fid]: false };
            }
        },

        clearNewUpload(fid) {
            const nextTokens = { ...this.uploadedTokens };
            delete nextTokens[fid];
            this.uploadedTokens = nextTokens;

            const nextLabels = { ...this.fileLabels };
            delete nextLabels[fid];
            this.fileLabels = nextLabels;

            const nextProg = { ...this.uploadProgress };
            delete nextProg[fid];
            this.uploadProgress = nextProg;

            this.uploadErrors = { ...this.uploadErrors, [fid]: '' };
            this.uploadReloadNeeded = { ...this.uploadReloadNeeded, [fid]: false };
            const input = document.getElementById(`fix_file_${fid}`);
            if (input) input.value = '';
        },

        normalizeAll(formEl) {
            if (!window.SatuDataQuality || !formEl) return;
            let changed = 0;

            this.fields.forEach((f) => {
                if (['file', 'mc_grid', 'checkbox_grid'].includes(f.type)) return;

                if (f.type === 'radio') {
                    const checked = formEl.querySelector(`input[name="values[${f.id}]"]:checked`);
                    if (checked) {
                        const norm = window.SatuDataQuality.normalizeValue(f, checked.value, { normalizeNumbers: true });
                        const match = Array.from(formEl.querySelectorAll(`input[name="values[${f.id}]"]`))
                            .find((r) => r.value === norm);
                        if (match && !match.checked) {
                            match.checked = true;
                            changed++;
                        }
                    }
                    return;
                }

                if (f.type === 'checkbox') {
                    const boxes = Array.from(formEl.querySelectorAll(`input[name="values[${f.id}][]"]`));
                    const picked = boxes.filter((b) => b.checked && b.value !== '__other__').map((b) => b.value);
                    const norm = window.SatuDataQuality.normalizeValue(f, picked.join(', '), { normalizeNumbers: true });
                    const normSet = norm ? norm.split(',').map((s) => s.trim()) : [];
                    boxes.forEach((b) => {
                        if (b.value === '__other__') return;
                        const shouldCheck = normSet.includes(b.value);
                        if (b.checked !== shouldCheck) {
                            b.checked = shouldCheck;
                            changed++;
                        }
                    });
                    return;
                }

                const elInput = formEl.querySelector(`[name="values[${f.id}]"]`);
                if (!elInput) return;
                const before = String(elInput.value ?? '');
                const after = window.SatuDataQuality.normalizeValue(f, before, { normalizeNumbers: true });
                if (after !== before) {
                    elInput.value = after;
                    changed++;
                }
            });

            this.normalizeNotice = changed > 0
                ? `${changed} kolom diseragamkan format spasi, angka, dan pilihannya.`
                : 'Seluruh isian sudah seragam.';
        },

        handleSubmit(event) {
            const formEl = event.target;
            this.submitError = '';
            if (this.isAnyUploading()) {
                event.preventDefault();
                this.submitError = 'Mohon tunggu hingga proses unggah berkas bukti selesai sebelum menyimpan perbaikan.';
                return false;
            }
            Object.entries(this.uploadedTokens).forEach(([fid, tok]) => {
                if (tok && tok !== '[]') {
                    const fileInput = formEl.querySelector(`#fix_file_${fid}`);
                    if (fileInput) {
                        fileInput.value = '';
                        fileInput.disabled = true;
                    }
                }
            });
            return true;
        },
    };
}
