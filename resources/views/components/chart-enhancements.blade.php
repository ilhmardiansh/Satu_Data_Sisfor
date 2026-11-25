<script>
(function () {
    function patchCharts() {
        const charts = window.SatuDataCharts;
        if (!charts || charts.__areaPatched) return;
        charts.__areaPatched = true;

        if (Array.isArray(charts.kinds) && !charts.kinds.some((k) => k[0] === 'area')) {
            const radarIdx = charts.kinds.findIndex((k) => k[0] === 'radar');
            if (radarIdx >= 0) {
                charts.kinds.splice(radarIdx, 0, ['area', 'Area']);
            } else {
                charts.kinds.push(['area', 'Area']);
            }
        }

        const origRenderSvg = charts.renderSvg;
        charts.renderSvg = function (kind, data, colors, width = 300, height = 180) {
            if (kind === 'area') {
                const pal = colors && colors.length ? colors : charts.palettes.sky;
                if (!Array.isArray(data) || !data.length) {
                    return '<div class="grid h-full place-items-center text-xs font-medium text-slate-600">Belum ada data</div>';
                }
                const esc = charts.escapeHtml || ((s) => String(s ?? ''));
                const fmtCompact = (val) => {
                    const num = Number(val) || 0;
                    if (Math.abs(num) >= 1000000) return `${(num / 1000000).toFixed(1).replace('.', ',')} jt`;
                    if (Math.abs(num) >= 10000) return `${(num / 1000).toFixed(1).replace('.', ',')} rb`;
                    return num.toLocaleString('id-ID');
                };
                const shortLabel = (text, max = 9) => {
                    const clean = String(text ?? '').trim();
                    return clean.length <= max ? clean : `${clean.slice(0, max - 1)}…`;
                };

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
                            <text x="${padL - 5}" y="${gy + 3}" text-anchor="end" font-size="9.5" font-weight="600" fill="#475569">${esc(t === 1 ? '0' : fmtCompact(Math.round(val)))}</text>
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
                    ? pts.map((p) => `<text x="${p.x}" y="${height - 6}" text-anchor="middle" font-size="9" font-weight="600" fill="#475569">${esc(shortLabel(p.n, 8))}</text>`).join('')
                    : `
                        <text x="${pts[0].x}" y="${height - 6}" text-anchor="start" font-size="9.5" font-weight="600" fill="#475569">${esc(shortLabel(pts[0].n, 10))}</text>
                        <text x="${pts[pts.length - 1].x}" y="${height - 6}" text-anchor="end" font-size="9.5" font-weight="600" fill="#475569">${esc(shortLabel(pts[pts.length - 1].n, 10))}</text>
                    `;

                const circles = pts
                    .map((p) => {
                        const tip = esc(`${p.n}: ${p.v.toLocaleString('id-ID')}`);
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

            return origRenderSvg.call(this, kind, data, colors, width, height);
        };
    }

    window.__sdPatchCharts = patchCharts;
    if (window.SatuDataCharts) {
        patchCharts();
    } else {
        document.addEventListener('alpine:init', patchCharts);
        document.addEventListener('DOMContentLoaded', patchCharts);
    }
})();
</script>
