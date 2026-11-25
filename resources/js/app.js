import { gformStudio } from './gform-studio.js';
import { publicGFormRunner, submissionFixEditor } from './gform-runner.js';

window.gformStudio = gformStudio;
window.publicGFormRunner = publicGFormRunner;
window.submissionFixEditor = submissionFixEditor;

document.addEventListener('alpine:init', () => {
    if (window.Alpine?.data) {
        window.Alpine.data('gformStudio', gformStudio);
        window.Alpine.data('publicGFormRunner', publicGFormRunner);
        window.Alpine.data('submissionFixEditor', submissionFixEditor);
    }
});

const palettes = {
    sky: ['#0069a8', '#00a6f4', '#74d4ff', '#182654', '#0284c7', '#64748b'],
    blue: ['#182654', '#1e3575', '#0069a8', '#00a6f4', '#74d4ff', '#64748b'],
    indigo: ['#4338ca', '#6366f1', '#818cf8', '#312e81', '#a5b4fc', '#64748b'],
    cyan: ['#0e7490', '#06b6d4', '#22d3ee', '#164e63', '#67e8f9', '#64748b'],
    teal: ['#0f766e', '#14b8a6', '#2dd4bf', '#134e4a', '#5eead4', '#64748b'],
    violet: ['#6d28d9', '#8b5cf6', '#a78bfa', '#4c1d95', '#c4b5fd', '#64748b'],
};

const kinds = [
    ['number', 'Angka'],
    ['donut', 'Donat'],
    ['pie', 'Pai'],
    ['bar', 'Batang'],
    ['hbar', 'Horizontal'],
    ['line', 'Garis'],
    ['area', 'Area'],
    ['radar', 'Radar'],
];

function escapeAttr(val) {
    return String(val ?? '')
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

function parseNum(v) {
    if (typeof v === 'number') return Number.isFinite(v) ? v : null;
    if (v === null || v === undefined) return null;
    const t = String(v).replace(/rp|\s|%/gi, '');
    if (!/^-?[\d.,]+$/.test(t)) return null;
    let normalized;
    if (/,\d{1,2}$/.test(t)) {
        normalized = t.replace(/\./g, '').replace(',', '.');
    } else if (/^-?[\d,]*\.\d{1,2}$/.test(t)) {
        normalized = t.replace(/,/g, '');
    } else {
        normalized = t.replace(/[.,]/g, '');
    }
    const n = Number(normalized);
    return Number.isFinite(n) ? n : null;
}

function numericCols(rows, cols) {
    if (!Array.isArray(rows) || !rows.length || !Array.isArray(cols)) return [];
    return cols
        .map((_, i) => i)
        .filter((i) => rows.every((r) => parseNum(r[i]) !== null));
}

function series(rows, labelCol, valueCol) {
    if (!Array.isArray(rows)) return [];
    return rows.map((r) => ({
        n: String(r[labelCol] ?? ''),
        v: parseNum(r[valueCol]) ?? 0,
    }));
}

function fmtPct(x, total) {
    if (!total || total <= 0) return '0,0%';
    return `${((x / total) * 100).toFixed(1).replace('.', ',')}%`;
}

function fmtCompact(val) {
    const num = Number(val) || 0;
    if (Math.abs(num) >= 1000000) {
        return `${(num / 1000000).toFixed(1).replace('.', ',')} jt`;
    }
    if (Math.abs(num) >= 10000) {
        return `${(num / 1000).toFixed(1).replace('.', ',')} rb`;
    }
    return num.toLocaleString('id-ID');
}

function shortLabel(text, max = 9) {
    const clean = String(text ?? '').trim();
    if (clean.length <= max) return clean;
    return `${clean.slice(0, max - 1)}…`;
}

function renderSvg(kind, data, colors, width = 300, height = 180) {
    const pal = colors && colors.length ? colors : palettes.sky;
    if (!Array.isArray(data) || !data.length) {
        return '<div class="grid h-full place-items-center text-xs font-medium text-slate-600">Belum ada data</div>';
    }

    if (kind === 'number') {
        const sum = data.reduce((acc, item) => acc + (Number(item.v) || 0), 0);
        const top = [...data].sort((a, b) => (Number(b.v) || 0) - (Number(a.v) || 0))[0];
        return `
            <div class="flex h-full flex-col items-center justify-center rounded-xl bg-[#f6f9fc] px-4 text-center">
                <p class="text-4xl font-extrabold tracking-tight tabular-nums" style="color: ${pal[0]}">${sum.toLocaleString('id-ID')}</p>
                <p class="mt-2 text-xs font-semibold text-slate-700">${data.length} segmen terukur</p>
                ${top ? `<p class="mt-1 max-w-full truncate text-[11px] font-medium text-slate-600">Tertinggi: ${escapeAttr(top.n)} (${Number(top.v).toLocaleString('id-ID')})</p>` : ''}
            </div>
        `;
    }

    const total = data.reduce((acc, item) => acc + Math.max(0, Number(item.v) || 0), 0);

    if (kind === 'donut' || kind === 'pie') {
        const isDonut = kind === 'donut';
        const cx = width / 2;
        const cy = height / 2;
        const R = Math.min(width, height) / 2 - 8;
        const r = isDonut ? R * 0.68 : 0;

        if (total <= 0) {
            const ring = isDonut
                ? `<circle cx="${cx}" cy="${cy}" r="${(R + r) / 2}" fill="none" stroke="#e2e8f0" stroke-width="${R - r}" />`
                : `<circle cx="${cx}" cy="${cy}" r="${R}" fill="#e2e8f0" />`;
            return `<svg viewBox="0 0 ${width} ${height}" class="h-full w-full">${ring}</svg>`;
        }

        const nonZero = data.filter((d) => (Number(d.v) || 0) > 0);
        if (nonZero.length === 1) {
            const d = nonZero[0];
            const idx = data.indexOf(d);
            const color = pal[idx % pal.length];
            const tip = escapeAttr(`${d.n}: ${d.v.toLocaleString('id-ID')}`);
            const valLabel = escapeAttr(Number(d.v).toLocaleString('id-ID'));
            const nameLabel = escapeAttr(d.n);
            const shape = isDonut
                ? `<circle cx="${cx}" cy="${cy}" r="${(R + r) / 2}" fill="none" stroke="${color}" stroke-width="${R - r}" data-chart-tip="${tip}" data-donut-val="${valLabel}" data-donut-label="${nameLabel}" />`
                : `<circle cx="${cx}" cy="${cy}" r="${R}" fill="${color}" stroke="#ffffff" stroke-width="2.5" data-chart-tip="${tip}" />`;
            return `<svg viewBox="0 0 ${width} ${height}" class="h-full w-full">${shape}</svg>`;
        }

        let currentAngle = -Math.PI / 2;
        let paths = '';

        data.forEach((d, i) => {
            const val = Math.max(0, Number(d.v) || 0);
            if (val <= 0) return;
            const sliceAngle = (val / total) * 2 * Math.PI;
            const endAngle = currentAngle + sliceAngle;

            const x1 = cx + R * Math.cos(currentAngle);
            const y1 = cy + R * Math.sin(currentAngle);
            const x2 = cx + R * Math.cos(endAngle);
            const y2 = cy + R * Math.sin(endAngle);
            const largeArc = sliceAngle > Math.PI ? 1 : 0;

            let pathData = '';
            if (isDonut) {
                const x3 = cx + r * Math.cos(endAngle);
                const y3 = cy + r * Math.sin(endAngle);
                const x4 = cx + r * Math.cos(currentAngle);
                const y4 = cy + r * Math.sin(currentAngle);
                pathData = `M ${x1} ${y1} A ${R} ${R} 0 ${largeArc} 1 ${x2} ${y2} L ${x3} ${y3} A ${r} ${r} 0 ${largeArc} 0 ${x4} ${y4} Z`;
            } else {
                pathData = `M ${cx} ${cy} L ${x1} ${y1} A ${R} ${R} 0 ${largeArc} 1 ${x2} ${y2} Z`;
            }

            const color = pal[i % pal.length];
            const tip = escapeAttr(`${d.n}: ${val.toLocaleString('id-ID')} (${fmtPct(val, total)})`);
            const donutAttrs = isDonut
                ? ` data-donut-val="${escapeAttr(val.toLocaleString('id-ID'))}" data-donut-label="${escapeAttr(shortLabel(d.n, 16))}"`
                : '';
            paths += `<path d="${pathData}" fill="${color}" stroke="#ffffff" stroke-width="2.5" class="transition-opacity duration-150 hover:opacity-85 cursor-pointer" data-chart-tip="${tip}"${donutAttrs}><title>${tip}</title></path>`;
            currentAngle = endAngle;
        });

        return `<svg viewBox="0 0 ${width} ${height}" class="h-full w-full">${paths}</svg>`;
    }

    if (kind === 'bar') {
        const padL = 34;
        const padR = 14;
        const padT = 14;
        const n = data.length;
        const showX = n <= 7;
        const padB = showX ? 24 : 10;
        const maxVal = Math.max(...data.map((d) => Number(d.v) || 0), 1);
        const usableW = width - padL - padR;
        const gap = Math.min(12, Math.max(3, Math.floor(usableW / (n * 3.5))));
        const barW = Math.max((usableW - (n - 1) * gap) / n, 4);
        const viewW = Math.max(width, padL + padR + n * barW + Math.max(0, n - 1) * gap);
        const plotH = height - padT - padB;

        const gridLines = [0, 0.5, 1]
            .map((t) => {
                const gy = padT + t * plotH;
                const val = maxVal * (1 - t);
                const label = t === 1 ? '0' : fmtCompact(Math.round(val));
                return `
                    <line x1="${padL}" y1="${gy}" x2="${viewW - padR}" y2="${gy}" stroke="#e2e8f0" stroke-width="1" stroke-opacity="${t === 1 ? '1' : '0.65'}" ${t < 1 ? 'stroke-dasharray="3 3"' : ''} />
                    <text x="${padL - 5}" y="${gy + 3}" text-anchor="end" font-size="9.5" font-weight="600" fill="#475569">${escapeAttr(label)}</text>
                `;
            })
            .join('');

        let tracks = '';
        let rects = '';
        let xLabels = '';
        data.forEach((d, i) => {
            const val = Math.max(0, Number(d.v) || 0);
            const barH = Math.max((val / maxVal) * plotH, val > 0 ? 4 : 0);
            const x = padL + i * (barW + gap);
            const y = height - padB - barH;
            const color = pal[i % pal.length];
            const tip = escapeAttr(`${d.n}: ${val.toLocaleString('id-ID')}`);
            tracks += `<rect x="${x}" y="${padT}" width="${barW}" height="${plotH}" rx="4" fill="#f1f5f9" />`;
            rects += `<rect x="${x}" y="${y}" width="${barW}" height="${barH}" rx="4" ry="4" fill="${color}" class="transition-opacity hover:opacity-85 cursor-pointer" data-chart-tip="${tip}"><title>${tip}</title></rect>`;
            if (showX) {
                xLabels += `<text x="${x + barW / 2}" y="${height - 7}" text-anchor="middle" font-size="9.5" font-weight="600" fill="#475569">${escapeAttr(shortLabel(d.n, 8))}</text>`;
            }
        });

        return `<svg viewBox="0 0 ${viewW} ${height}" class="h-full w-full">${tracks}${gridLines}${rects}${xLabels}</svg>`;
    }

    if (kind === 'hbar') {
        const padY = 10;
        const padL = 12;
        const padR = 44;
        const maxVal = Math.max(...data.map((d) => Number(d.v) || 0), 1);
        const n = data.length;
        const gap = n > 14 ? 2.5 : n > 8 ? 5 : 8;
        const barH = Math.max((height - 2 * padY - (n - 1) * gap) / n, 3);
        const viewH = Math.max(height, 2 * padY + n * barH + Math.max(0, n - 1) * gap);
        const plotW = width - padL - padR;

        const gridLines = [0, 0.5, 1]
            .map((t) => {
                const gx = padL + t * plotW;
                return `<line x1="${gx}" y1="${padY}" x2="${gx}" y2="${viewH - padY}" stroke="#e2e8f0" stroke-width="1" stroke-opacity="${t === 0 ? '1' : '0.65'}" ${t > 0 ? 'stroke-dasharray="3 3"' : ''} />`;
            })
            .join('');

        let tracks = '';
        let rects = '';
        let labels = '';
        data.forEach((d, i) => {
            const val = Math.max(0, Number(d.v) || 0);
            const barW = Math.max((val / maxVal) * plotW, val > 0 ? 4 : 0);
            const y = padY + i * (barH + gap);
            const r = barH < 6 ? 2 : 4;
            const color = pal[i % pal.length];
            const tip = escapeAttr(`${d.n}: ${val.toLocaleString('id-ID')}`);
            tracks += `<rect x="${padL}" y="${y}" width="${plotW}" height="${barH}" rx="${r}" fill="#f1f5f9" />`;
            rects += `<rect x="${padL}" y="${y}" width="${barW}" height="${barH}" rx="${r}" ry="${r}" fill="${color}" class="transition-opacity hover:opacity-85 cursor-pointer" data-chart-tip="${tip}"><title>${tip}</title></rect>`;
            if (n <= 10) {
                labels += `<text x="${padL + plotW + 6}" y="${y + barH / 2 + 3}" font-size="10" font-weight="600" fill="#334155">${escapeAttr(fmtCompact(val))}</text>`;
            }
        });

        return `<svg viewBox="0 0 ${width} ${viewH}" class="h-full w-full">${tracks}${gridLines}${rects}${labels}</svg>`;
    }

    if (kind === 'line') {
        const padL = 36;
        const padR = 16;
        const padT = 16;
        const padB = 24;
        const vals = data.map((d) => Number(d.v) || 0);
        const maxVal = Math.max(...vals);
        const minVal = Math.min(...vals);
        const n = data.length;
        const range = maxVal - minVal || 1;
        const plotW = width - padL - padR;
        const plotH = height - padT - padB;
        const gradId = `sd-lg-${Math.random().toString(36).slice(2, 9)}`;

        const gridLines = [0, 0.5, 1]
            .map((t) => {
                const gy = padT + t * plotH;
                const val = maxVal === minVal ? maxVal : maxVal - t * range;
                return `
                    <line x1="${padL}" y1="${gy}" x2="${width - padR}" y2="${gy}" stroke="#e2e8f0" stroke-width="1" stroke-opacity="${t === 1 ? '1' : '0.65'}" ${t < 1 ? 'stroke-dasharray="3 3"' : ''} />
                    <text x="${padL - 5}" y="${gy + 3}" text-anchor="end" font-size="9.5" font-weight="600" fill="#475569">${escapeAttr(fmtCompact(Math.round(val)))}</text>
                `;
            })
            .join('');

        const pts = data.map((d, i) => {
            const val = Number(d.v) || 0;
            const x = n === 1 ? padL + plotW / 2 : padL + i * (plotW / (n - 1));
            const y = maxVal === minVal
                ? padT + plotH / 2
                : height - padB - ((val - minVal) / range) * plotH;
            return { x, y, v: val, n: d.n };
        });

        const polyPoints = pts.map((p) => `${p.x},${p.y}`).join(' ');
        const baselineY = height - padB;
        const areaPoints = n > 1
            ? `${pts[0].x},${baselineY} ${polyPoints} ${pts[pts.length - 1].x},${baselineY}`
            : '';

        const xLabels = n > 1
            ? `
                <text x="${pts[0].x}" y="${height - 6}" text-anchor="start" font-size="9.5" font-weight="600" fill="#475569">${escapeAttr(shortLabel(pts[0].n, 10))}</text>
                <text x="${pts[pts.length - 1].x}" y="${height - 6}" text-anchor="end" font-size="9.5" font-weight="600" fill="#475569">${escapeAttr(shortLabel(pts[pts.length - 1].n, 10))}</text>
            `
            : `<text x="${pts[0].x}" y="${height - 6}" text-anchor="middle" font-size="9.5" font-weight="600" fill="#475569">${escapeAttr(shortLabel(pts[0].n, 12))}</text>`;

        const circles = pts
            .map((p) => {
                const tip = escapeAttr(`${p.n}: ${p.v.toLocaleString('id-ID')}`);
                return `<circle cx="${p.x}" cy="${p.y}" r="4.5" fill="${pal[0]}" stroke="#ffffff" stroke-width="2" class="cursor-pointer" data-chart-tip="${tip}"><title>${tip}</title></circle>`;
            })
            .join('');

        return `
            <svg viewBox="0 0 ${width} ${height}" class="h-full w-full">
                <defs>
                    <linearGradient id="${gradId}" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="${pal[1] || pal[0]}" stop-opacity="0.22" />
                        <stop offset="100%" stop-color="${pal[1] || pal[0]}" stop-opacity="0" />
                    </linearGradient>
                </defs>
                ${gridLines}
                ${areaPoints ? `<polygon points="${areaPoints}" fill="url(#${gradId})" />` : ''}
                ${n > 1 ? `<polyline points="${polyPoints}" fill="none" stroke="${pal[0]}" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />` : ''}
                ${circles}
                ${xLabels}
            </svg>
        `;
    }

    if (kind === 'area') {
        const padL = 36;
        const padR = 16;
        const padT = 16;
        const padB = 24;
        const vals = data.map((d) => Math.max(0, Number(d.v) || 0));
        const maxVal = Math.max(...vals, 1);
        const n = data.length;
        const plotW = width - padL - padR;
        const plotH = height - padT - padB;
        const gradId = `sd-ag-${Math.random().toString(36).slice(2, 9)}`;

        const gridLines = [0, 0.5, 1]
            .map((t) => {
                const gy = padT + t * plotH;
                const val = maxVal * (1 - t);
                return `
                    <line x1="${padL}" y1="${gy}" x2="${width - padR}" y2="${gy}" stroke="#e2e8f0" stroke-width="1" stroke-opacity="${t === 1 ? '1' : '0.65'}" ${t < 1 ? 'stroke-dasharray="3 3"' : ''} />
                    <text x="${padL - 5}" y="${gy + 3}" text-anchor="end" font-size="9.5" font-weight="600" fill="#475569">${escapeAttr(t === 1 ? '0' : fmtCompact(Math.round(val)))}</text>
                `;
            })
            .join('');

        const pts = data.map((d, i) => {
            const val = Math.max(0, Number(d.v) || 0);
            const x = n === 1 ? padL + plotW / 2 : padL + i * (plotW / (n - 1));
            const y = height - padB - (val / maxVal) * plotH;
            return { x, y, v: val, n: d.n };
        });

        const polyPoints = pts.map((p) => `${p.x},${p.y}`).join(' ');
        const baselineY = height - padB;
        const areaPoints = n > 1
            ? `${pts[0].x},${baselineY} ${polyPoints} ${pts[pts.length - 1].x},${baselineY}`
            : '';

        const xLabels = n <= 6
            ? pts.map((p) => `<text x="${p.x}" y="${height - 6}" text-anchor="middle" font-size="9" font-weight="600" fill="#475569">${escapeAttr(shortLabel(p.n, 8))}</text>`).join('')
            : `
                <text x="${pts[0].x}" y="${height - 6}" text-anchor="start" font-size="9.5" font-weight="600" fill="#475569">${escapeAttr(shortLabel(pts[0].n, 10))}</text>
                <text x="${pts[pts.length - 1].x}" y="${height - 6}" text-anchor="end" font-size="9.5" font-weight="600" fill="#475569">${escapeAttr(shortLabel(pts[pts.length - 1].n, 10))}</text>
            `;

        const circles = pts
            .map((p) => {
                const tip = escapeAttr(`${p.n}: ${p.v.toLocaleString('id-ID')}`);
                return `<circle cx="${p.x}" cy="${p.y}" r="4.5" fill="${pal[0]}" stroke="#ffffff" stroke-width="2" class="cursor-pointer" data-chart-tip="${tip}"><title>${tip}</title></circle>`;
            })
            .join('');

        return `
            <svg viewBox="0 0 ${width} ${height}" class="h-full w-full">
                <defs>
                    <linearGradient id="${gradId}" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="${pal[0]}" stop-opacity="0.38" />
                        <stop offset="100%" stop-color="${pal[1] || pal[0]}" stop-opacity="0.04" />
                    </linearGradient>
                </defs>
                ${gridLines}
                ${areaPoints ? `<polygon points="${areaPoints}" fill="url(#${gradId})" />` : ''}
                ${n > 1 ? `<polyline points="${polyPoints}" fill="none" stroke="${pal[0]}" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />` : ''}
                ${circles}
                ${xLabels}
            </svg>
        `;
    }

    if (kind === 'radar') {
        const cx = width / 2;
        const cy = height / 2;
        const R = Math.min(width, height) / 2 - 22;
        const vals = data.map((d) => Math.max(0, Number(d.v) || 0));
        const maxVal = Math.max(...vals, 1);
        const n = data.length;

        if (n < 3) {
            return renderSvg('bar', data, pal, width, height);
        }

        let grid = '';
        [0.33, 0.66, 1].forEach((frac) => {
            const r = R * frac;
            const gPts = [];
            for (let i = 0; i < n; i++) {
                const angle = -Math.PI / 2 + (i / n) * 2 * Math.PI;
                gPts.push(`${cx + r * Math.cos(angle)},${cy + r * Math.sin(angle)}`);
            }
            grid += `<polygon points="${gPts.join(' ')}" fill="none" stroke="#cbd5e1" stroke-width="1" />`;
        });

        let axes = '';
        let dots = '';
        const pts = data.map((d, i) => {
            const val = Math.max(0, Number(d.v) || 0);
            const r = (val / maxVal) * R;
            const angle = -Math.PI / 2 + (i / n) * 2 * Math.PI;
            const px = cx + r * Math.cos(angle);
            const py = cy + r * Math.sin(angle);
            const ax = cx + R * Math.cos(angle);
            const ay = cy + R * Math.sin(angle);
            axes += `<line x1="${cx}" y1="${cy}" x2="${ax}" y2="${ay}" stroke="#e2e8f0" stroke-width="1" />`;
            const tip = escapeAttr(`${d.n}: ${val.toLocaleString('id-ID')}`);
            dots += `<circle cx="${px}" cy="${py}" r="4" fill="${pal[0]}" stroke="#ffffff" stroke-width="1.5" class="cursor-pointer" data-chart-tip="${tip}"><title>${tip}</title></circle>`;
            return `${px},${py}`;
        });

        return `
            <svg viewBox="0 0 ${width} ${height}" class="h-full w-full">
                ${grid}
                ${axes}
                <polygon points="${pts.join(' ')}" fill="${pal[1] || pal[0]}" fill-opacity="0.24" stroke="${pal[0]}" stroke-width="2" />
                ${dots}
            </svg>
        `;
    }

    return '';
}

window.SatuDataCharts = {
    palettes,
    kinds,
    parseNum,
    numericCols,
    series,
    fmtPct,
    escapeHtml: escapeAttr,
    renderSvg,
};

window.SatuDataQuality = {
    normalizeValue(field, value, opts = {}) {
        const str = String(value ?? '');
        if (field.type === 'file') return str;
        const clean =
            field.type === 'textarea'
                ? str
                    .trim()
                    .split(/\r?\n/)
                    .map((line) => line.trim().replace(/[^\S\n]+/g, ' '))
                    .join('\n')
                : str.trim().replace(/\s+/g, ' ');
        if (field.type === 'number' && opts.normalizeNumbers && clean) {
            let numStr = clean.replace(/rp|\s|%/gi, '');
            if (/^-?\d{1,3}(\.\d{3})+(,\d+)?$/.test(numStr)) {
                numStr = numStr.replace(/\./g, '').replace(',', '.');
            } else if (/^-?\d+,\d+$/.test(numStr)) {
                numStr = numStr.replace(',', '.');
            }
            if (/^-?\d+(\.\d+)?$/.test(numStr)) return numStr;
        }
        if ((field.type === 'select' || field.type === 'radio') && Array.isArray(field.options)) {
            const matched = field.options.find(
                (o) => String(o).toLowerCase() === clean.toLowerCase()
            );
            return matched !== undefined ? String(matched) : clean;
        }
        if (field.type === 'checkbox') {
            if (!clean) return '';
            const optsList = Array.isArray(field.options) ? field.options : [];
            const seen = new Set();
            const out = [];
            clean.split(',').forEach((part) => {
                const tok = part.trim().replace(/\s+/g, ' ');
                if (!tok) return;
                const matched = optsList.find((o) => String(o).toLowerCase() === tok.toLowerCase());
                const val = matched !== undefined ? String(matched) : tok;
                const key = val.toLowerCase();
                if (!seen.has(key)) {
                    seen.add(key);
                    out.push(val);
                }
            });
            return out.join(', ');
        }
        return clean;
    },
};

window.initDataScene = function initDataScene(el) {
    if (!el || el.dataset.sceneMounted) return;
    el.dataset.sceneMounted = '1';

    const variant = el.getAttribute('data-scene') || 'orbit';
    const isWaliVariant = variant === 'walidata-pipeline' || variant === 'walidata-header';
    const load = () => {
        if (isWaliVariant) {
            import('./walidata-stage.js')
                .then(({ mountWaliDataScene }) => mountWaliDataScene(el, variant))
                .catch(() => {});
        } else {
            import('./three-scenes.js')
                .then(({ mountThreeScene }) => mountThreeScene(el, variant))
                .catch(() => {});
        }
    };

    if ('IntersectionObserver' in window) {
        const gate = new IntersectionObserver((entries) => {
            if (!entries.some((e) => e.isIntersecting && e.boundingClientRect.width > 0)) return;
            gate.disconnect();
            load();
        });
        gate.observe(el);
    } else {
        load();
    }
};

window.initLucide = function initLucide() {
    if (window.lucide) {
        window.lucide.createIcons();
    }
};

window.toggleSceneMotion = function toggleSceneMotion(btn) {
    const paused = !(window.__sdMotionPaused === true);
    window.__sdMotionPaused = paused;
    window.dispatchEvent(new CustomEvent('sd:motion-toggle', { detail: { paused } }));

    document.querySelectorAll('[data-motion-toggle]').forEach((el) => {
        el.setAttribute('aria-pressed', paused ? 'true' : 'false');
        const label = el.querySelector('[data-motion-label]');
        if (label) {
            label.textContent = paused ? 'Putar latar 3D' : 'Jeda latar 3D';
        }
    });

    if (btn) {
        btn.setAttribute('aria-pressed', paused ? 'true' : 'false');
    }
};

function initTooltips() {
    if (document.getElementById('sd-chart-tooltip')) return;
    const tip = document.createElement('div');
    tip.id = 'sd-chart-tooltip';
    tip.className = 'pointer-events-none fixed z-[80] hidden rounded-lg border border-slate-700 bg-slate-900 px-2.5 py-1.5 text-xs font-semibold text-white shadow-lg';
    document.body.appendChild(tip);

    document.addEventListener('mouseover', (e) => {
        const target = e.target?.closest?.('[data-chart-tip]');
        if (!target) {
            tip.classList.add('hidden');
        } else {
            tip.textContent = target.getAttribute('data-chart-tip') || '';
            tip.classList.remove('hidden');
        }

        const donutSlice = e.target?.closest?.('[data-donut-val]');
        const donutCard = donutSlice?.closest?.('[data-donut-card]');
        if (donutSlice && donutCard) {
            const totalEl = donutCard.querySelector('[data-donut-total]');
            const subEl = donutCard.querySelector('[data-donut-sub]');
            if (totalEl) totalEl.textContent = donutSlice.getAttribute('data-donut-val') || '';
            if (subEl) subEl.textContent = donutSlice.getAttribute('data-donut-label') || '';
        }
    });

    document.addEventListener('mousemove', (e) => {
        if (tip.classList.contains('hidden')) return;
        const rect = tip.getBoundingClientRect();
        const maxLeft = Math.max(8, window.innerWidth - rect.width - 12);
        const left = Math.min(e.clientX + 12, maxLeft);
        const top = Math.max(8, e.clientY - rect.height - 12);
        tip.style.left = `${left}px`;
        tip.style.top = `${top}px`;
    });

    document.addEventListener('mouseout', (e) => {
        if (e.target?.closest?.('[data-chart-tip]')) {
            tip.classList.add('hidden');
        }

        const donutSlice = e.target?.closest?.('[data-donut-val]');
        const donutCard = donutSlice?.closest?.('[data-donut-card]');
        if (donutSlice && donutCard) {
            const nextSlice = e.relatedTarget?.closest?.('[data-donut-val]');
            if (!nextSlice || !donutCard.contains(nextSlice)) {
                const totalEl = donutCard.querySelector('[data-donut-total]');
                const subEl = donutCard.querySelector('[data-donut-sub]');
                if (totalEl && donutCard.dataset.defaultTotal !== undefined) {
                    totalEl.textContent = donutCard.dataset.defaultTotal;
                }
                if (subEl && donutCard.dataset.defaultSub !== undefined) {
                    subEl.textContent = donutCard.dataset.defaultSub;
                }
            }
        }
    });
}

function initRevealAndCountUp() {
    const reduced = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
    const reveals = document.querySelectorAll('.reveal, .reveal-stagger');

    const animateCounter = (el) => {
        if (el.dataset.counted) return;
        el.dataset.counted = '1';
        const target = Number(el.dataset.countup);
        if (!Number.isFinite(target)) return;
        if (reduced) {
            el.textContent = target.toLocaleString('id-ID');
            return;
        }
        const start = performance.now();
        const duration = 1100;
        const step = (now) => {
            const p = Math.min(1, (now - start) / duration);
            const current = Math.round(target * (1 - (1 - p) ** 3));
            el.textContent = current.toLocaleString('id-ID');
            if (p < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
    };

    const counters = document.querySelectorAll('[data-countup]');

    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-in');
                        observer.unobserve(entry.target);
                    }
                });
            },
            { threshold: 0.08 }
        );
        reveals.forEach((el) => observer.observe(el));

        const counterObserver = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        animateCounter(entry.target);
                        counterObserver.unobserve(entry.target);
                    }
                });
            },
            { threshold: 0.15 }
        );
        counters.forEach((el) => counterObserver.observe(el));
    } else {
        reveals.forEach((el) => el.classList.add('is-in'));
        counters.forEach(animateCounter);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.initLucide();
    initRevealAndCountUp();
    initTooltips();
    document.querySelectorAll('[data-scene]').forEach((el) => window.initDataScene(el));
});
