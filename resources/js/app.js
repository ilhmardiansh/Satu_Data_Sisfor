const palettes = {
    sky: ['#4472d0', '#0ea5e9', '#a0bbea', '#0f172a', '#7dd3fc', '#94a3b8'],
    blue: ['#3760bb', '#7398dd', '#a0bbea', '#1c2a4a', '#c7d6f3', '#94a3b8'],
    indigo: ['#4f46e5', '#818cf8', '#a5b4fc', '#312e81', '#c7d2fe', '#94a3b8'],
    cyan: ['#0891b2', '#22d3ee', '#67e8f9', '#164e63', '#a5f3fc', '#94a3b8'],
    teal: ['#0d9488', '#2dd4bf', '#5eead4', '#134e4a', '#99f6e4', '#94a3b8'],
    violet: ['#7c3aed', '#a78bfa', '#c4b5fd', '#4c1d95', '#ddd6fe', '#94a3b8'],
};

const kinds = [
    ['number', 'Angka'],
    ['donut', 'Donat'],
    ['pie', 'Pai'],
    ['bar', 'Batang'],
    ['hbar', 'Horizontal'],
    ['line', 'Garis'],
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

function renderSvg(kind, data, colors, width = 300, height = 180) {
    const pal = colors && colors.length ? colors : palettes.sky;
    if (!Array.isArray(data) || !data.length) {
        return '<div class="grid h-full place-items-center text-xs text-slate-400">Belum ada data</div>';
    }

    if (kind === 'number') {
        const sum = data.reduce((acc, item) => acc + (Number(item.v) || 0), 0);
        return `
            <div class="flex h-full flex-col items-center justify-center text-center">
                <p class="text-4xl font-extrabold tabular-nums" style="color: ${pal[0]}">${sum.toLocaleString('id-ID')}</p>
                <p class="mt-2 text-xs text-slate-500">Total nilai · ${data.length} kelompok</p>
            </div>
        `;
    }

    const total = data.reduce((acc, item) => acc + Math.max(0, Number(item.v) || 0), 0);

    if (kind === 'donut' || kind === 'pie') {
        const isDonut = kind === 'donut';
        const cx = width / 2;
        const cy = height / 2;
        const R = Math.min(width, height) / 2 - 6;
        const r = isDonut ? R * 0.72 : 0;

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
            const shape = isDonut
                ? `<circle cx="${cx}" cy="${cy}" r="${(R + r) / 2}" fill="none" stroke="${color}" stroke-width="${R - r}" data-chart-tip="${tip}" />`
                : `<circle cx="${cx}" cy="${cy}" r="${R}" fill="${color}" stroke="#ffffff" stroke-width="2" data-chart-tip="${tip}" />`;
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
            paths += `<path d="${pathData}" fill="${color}" stroke="#ffffff" stroke-width="2" class="transition-opacity hover:opacity-85 cursor-pointer" data-chart-tip="${tip}"><title>${tip}</title></path>`;
            currentAngle = endAngle;
        });

        return `<svg viewBox="0 0 ${width} ${height}" class="h-full w-full">${paths}</svg>`;
    }

    if (kind === 'bar') {
        const padX = 16;
        const padB = 10;
        const padT = 14;
        const maxVal = Math.max(...data.map((d) => Number(d.v) || 0), 1);
        const n = data.length;
        const gap = Math.min(12, Math.max(2, Math.floor((width - 2 * padX) / (n * 3))));
        const barW = Math.max((width - 2 * padX - (n - 1) * gap) / n, 3);
        const viewW = Math.max(width, 2 * padX + n * barW + Math.max(0, n - 1) * gap);
        const plotH = height - padT - padB;

        const gridLines = [0, 0.5, 1]
            .map((t) => {
                const gy = padT + t * plotH;
                return `<line x1="${padX}" y1="${gy}" x2="${viewW - padX}" y2="${gy}" stroke="#e2e8f0" stroke-width="1" stroke-opacity="${t === 1 ? '0.9' : '0.55'}" ${t < 1 ? 'stroke-dasharray="3 3"' : ''} />`;
            })
            .join('');

        let rects = '';
        data.forEach((d, i) => {
            const val = Math.max(0, Number(d.v) || 0);
            const barH = Math.max((val / maxVal) * plotH, val > 0 ? 4 : 0);
            const x = padX + i * (barW + gap);
            const y = height - padB - barH;
            const color = pal[i % pal.length];
            const tip = escapeAttr(`${d.n}: ${val.toLocaleString('id-ID')}`);
            rects += `<rect x="${x}" y="${y}" width="${barW}" height="${barH}" rx="4" ry="4" fill="${color}" class="transition-opacity hover:opacity-85 cursor-pointer" data-chart-tip="${tip}"><title>${tip}</title></rect>`;
        });

        return `<svg viewBox="0 0 ${viewW} ${height}" class="h-full w-full">${gridLines}${rects}</svg>`;
    }

    if (kind === 'hbar') {
        const padY = 10;
        const padL = 16;
        const padR = 16;
        const maxVal = Math.max(...data.map((d) => Number(d.v) || 0), 1);
        const n = data.length;
        const gap = n > 12 ? 2 : 8;
        const barH = Math.max((height - 2 * padY - (n - 1) * gap) / n, 2.5);
        const viewH = Math.max(height, 2 * padY + n * barH + Math.max(0, n - 1) * gap);
        const plotW = width - padL - padR;

        const gridLines = [0, 0.5, 1]
            .map((t) => {
                const gx = padL + t * plotW;
                return `<line x1="${gx}" y1="${padY}" x2="${gx}" y2="${viewH - padY}" stroke="#e2e8f0" stroke-width="1" stroke-opacity="${t === 0 ? '0.9' : '0.55'}" ${t > 0 ? 'stroke-dasharray="3 3"' : ''} />`;
            })
            .join('');

        let rects = '';
        data.forEach((d, i) => {
            const val = Math.max(0, Number(d.v) || 0);
            const barW = Math.max((val / maxVal) * plotW, val > 0 ? 4 : 0);
            const y = padY + i * (barH + gap);
            const color = pal[i % pal.length];
            const tip = escapeAttr(`${d.n}: ${val.toLocaleString('id-ID')}`);
            rects += `<rect x="${padL}" y="${y}" width="${barW}" height="${barH}" rx="${barH < 5 ? 1.5 : 4}" ry="${barH < 5 ? 1.5 : 4}" fill="${color}" class="transition-opacity hover:opacity-85 cursor-pointer" data-chart-tip="${tip}"><title>${tip}</title></rect>`;
        });

        return `<svg viewBox="0 0 ${width} ${viewH}" class="h-full w-full">${gridLines}${rects}</svg>`;
    }

    if (kind === 'line') {
        const padX = 24;
        const padY = 22;
        const vals = data.map((d) => Number(d.v) || 0);
        const maxVal = Math.max(...vals);
        const minVal = Math.min(...vals);
        const n = data.length;
        const range = maxVal - minVal || 1;
        const plotH = height - 2 * padY;

        const gridLines = [0, 0.5, 1]
            .map((t) => {
                const gy = padY + t * plotH;
                return `<line x1="${padX}" y1="${gy}" x2="${width - padX}" y2="${gy}" stroke="#e2e8f0" stroke-width="1" stroke-opacity="${t === 1 ? '0.85' : '0.55'}" ${t < 1 ? 'stroke-dasharray="3 3"' : ''} />`;
            })
            .join('');

        const pts = data.map((d, i) => {
            const val = Number(d.v) || 0;
            const x = n === 1 ? width / 2 : padX + i * ((width - 2 * padX) / (n - 1));
            const y = maxVal === minVal
                ? height / 2
                : height - padY - ((val - minVal) / range) * plotH;
            return { x, y, v: val, n: d.n };
        });

        const polyPoints = pts.map((p) => `${p.x},${p.y}`).join(' ');
        const circles = pts
            .map((p) => {
                const tip = escapeAttr(`${p.n}: ${p.v.toLocaleString('id-ID')}`);
                return `<circle cx="${p.x}" cy="${p.y}" r="4.5" fill="${pal[0]}" stroke="#ffffff" stroke-width="2" class="cursor-pointer" data-chart-tip="${tip}"><title>${tip}</title></circle>`;
            })
            .join('');

        return `
            <svg viewBox="0 0 ${width} ${height}" class="h-full w-full">
                ${gridLines}
                ${n > 1 ? `<polyline points="${polyPoints}" fill="none" stroke="${pal[0]}" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />` : ''}
                ${circles}
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
            grid += `<polygon points="${gPts.join(' ')}" fill="none" stroke="#e2e8f0" stroke-width="1" />`;
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
            dots += `<circle cx="${px}" cy="${py}" r="3.5" fill="${pal[0]}" stroke="#ffffff" stroke-width="1.5" class="cursor-pointer" data-chart-tip="${tip}"><title>${tip}</title></circle>`;
            return `${px},${py}`;
        });

        return `
            <svg viewBox="0 0 ${width} ${height}" class="h-full w-full">
                ${grid}
                ${axes}
                <polygon points="${pts.join(' ')}" fill="${pal[1] || pal[0]}" fill-opacity="0.38" stroke="${pal[0]}" stroke-width="2" />
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
    normalizeValue(field, value) {
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
        if (field.type === 'select' && Array.isArray(field.options)) {
            const matched = field.options.find(
                (o) => String(o).toLowerCase() === clean.toLowerCase()
            );
            return matched !== undefined ? String(matched) : clean;
        }
        return clean;
    },
};

window.initDataScene = function initDataScene(el) {
    if (!el || el.dataset.sceneMounted) return;
    el.dataset.sceneMounted = '1';

    const variant = el.getAttribute('data-scene') || 'orbit';
    const load = () => {
        import('./three-scenes.js')
            .then(({ mountThreeScene }) => mountThreeScene(el, variant))
            .catch(() => {});
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

function initTooltips() {
    if (document.getElementById('sd-chart-tooltip')) return;
    const tip = document.createElement('div');
    tip.id = 'sd-chart-tooltip';
    tip.className = 'pointer-events-none fixed z-[80] hidden rounded-lg border border-slate-700/70 bg-slate-900/95 backdrop-blur-md px-2.5 py-1.5 text-xs font-semibold text-white shadow-[0_8px_24px_rgba(15,23,42,0.28)]';
    document.body.appendChild(tip);

    document.addEventListener('mouseover', (e) => {
        const target = e.target?.closest?.('[data-chart-tip]');
        if (!target) {
            tip.classList.add('hidden');
            return;
        }
        tip.textContent = target.getAttribute('data-chart-tip') || '';
        tip.classList.remove('hidden');
    });

    document.addEventListener('mousemove', (e) => {
        if (tip.classList.contains('hidden')) return;
        tip.style.left = `${e.clientX + 12}px`;
        tip.style.top = `${e.clientY - 28}px`;
    });

    document.addEventListener('mouseout', (e) => {
        if (e.target?.closest?.('[data-chart-tip]')) {
            tip.classList.add('hidden');
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
