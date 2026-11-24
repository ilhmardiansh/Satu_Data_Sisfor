import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';

function createBokehTexture() {
    const canvas = document.createElement('canvas');
    canvas.width = 64;
    canvas.height = 64;
    const ctx = canvas.getContext('2d');
    if (ctx) {
        const grad = ctx.createRadialGradient(32, 32, 0, 32, 32, 32);
        grad.addColorStop(0, 'rgba(255, 255, 255, 1)');
        grad.addColorStop(0.35, 'rgba(116, 212, 255, 0.72)');
        grad.addColorStop(0.75, 'rgba(0, 166, 244, 0.16)');
        grad.addColorStop(1, 'rgba(0, 166, 244, 0)');
        ctx.fillStyle = grad;
        ctx.fillRect(0, 0, 64, 64);
    }
    const tex = new THREE.CanvasTexture(canvas);
    tex.colorSpace = THREE.SRGBColorSpace;
    return tex;
}

function parseMetrics(el) {
    try {
        return JSON.parse(el.dataset.metrics || '{}') || {};
    } catch {
        return {};
    }
}

function createCanvasTexture(width, height, drawFn, disposables) {
    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;
    const ctx = canvas.getContext('2d');
    if (ctx) {
        drawFn(ctx, width, height);
    }
    const tex = new THREE.CanvasTexture(canvas);
    tex.colorSpace = THREE.SRGBColorSpace;
    tex.anisotropy = 4;
    tex.needsUpdate = true;
    disposables.push(tex);
    return tex;
}

function createRoundedRectGeometry(w, h, r) {
    const hw = w / 2;
    const hh = h / 2;
    const rad = Math.min(r, hw * 0.48, hh * 0.48);

    const shape = new THREE.Shape();
    shape.moveTo(-hw + rad, -hh);
    shape.lineTo(hw - rad, -hh);
    shape.quadraticCurveTo(hw, -hh, hw, -hh + rad);
    shape.lineTo(hw, hh - rad);
    shape.quadraticCurveTo(hw, hh, hw - rad, hh);
    shape.lineTo(-hw + rad, hh);
    shape.quadraticCurveTo(-hw, hh, -hw, hh - rad);
    shape.lineTo(-hw, -hh + rad);
    shape.quadraticCurveTo(-hw, -hh, -hw + rad, -hh);

    const geo = new THREE.ShapeGeometry(shape, 12);
    const pos = geo.attributes.position;
    const uvs = new Float32Array(pos.count * 2);
    for (let i = 0; i < pos.count; i++) {
        uvs[i * 2] = (pos.getX(i) + hw) / w;
        uvs[i * 2 + 1] = (pos.getY(i) + hh) / h;
    }
    geo.setAttribute('uv', new THREE.BufferAttribute(uvs, 2));
    return geo;
}

function drawRoundedRectPath(ctx, x, y, w, h, r) {
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.lineTo(x + w - r, y);
    ctx.quadraticCurveTo(x + w, y, x + w, y + r);
    ctx.lineTo(x + w, y + h - r);
    ctx.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
    ctx.lineTo(x + r, y + h);
    ctx.quadraticCurveTo(x, y + h, x, y + h - r);
    ctx.lineTo(x, y + r);
    ctx.quadraticCurveTo(x, y, x + r, y);
    ctx.closePath();
}

function createBadgeSprite(code, label, accentCss, disposables) {
    const tex = createCanvasTexture(300, 68, (ctx, w, h) => {
        ctx.clearRect(0, 0, w, h);
        drawRoundedRectPath(ctx, 4, 4, w - 8, h - 8, 28);
        ctx.fillStyle = 'rgba(9, 18, 44, 0.88)';
        ctx.fill();
        ctx.lineWidth = 2.5;
        ctx.strokeStyle = accentCss;
        ctx.stroke();

        drawRoundedRectPath(ctx, 14, 15, 52, 38, 16);
        ctx.fillStyle = accentCss;
        ctx.fill();

        ctx.fillStyle = '#09122C';
        ctx.font = '800 20px monospace';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(code, 40, 35);

        ctx.fillStyle = '#F8FAFC';
        ctx.font = '700 22px system-ui, -apple-system, sans-serif';
        ctx.textAlign = 'left';
        ctx.fillText(label, 80, 35);
    }, disposables);

    const mat = new THREE.SpriteMaterial({
        map: tex,
        transparent: true,
        depthWrite: false,
    });
    disposables.push(mat);
    const sprite = new THREE.Sprite(mat);
    sprite.scale.set(1.48, 0.34, 1);
    return sprite;
}

function createGFormTexture(disposables) {
    return createCanvasTexture(256, 320, (ctx, w, h) => {
        ctx.fillStyle = '#F8FAFC';
        ctx.fillRect(0, 0, w, h);

        ctx.fillStyle = '#0069A8';
        ctx.fillRect(0, 0, w, 24);

        ctx.fillStyle = '#0F172A';
        ctx.font = 'bold 18px system-ui, sans-serif';
        ctx.fillText('INSTRUMEN DATA', 20, 52);

        ctx.fillStyle = '#64748B';
        ctx.fillRect(20, 66, 150, 8);

        const rows = [102, 164, 226];
        rows.forEach((ry, i) => {
            drawRoundedRectPath(ctx, 18, ry, w - 36, 46, 10);
            ctx.fillStyle = '#FFFFFF';
            ctx.fill();
            ctx.strokeStyle = i === 0 ? '#00A6F4' : '#CBD5E1';
            ctx.lineWidth = 2;
            ctx.stroke();

            ctx.beginPath();
            ctx.arc(36, ry + 23, 8, 0, Math.PI * 2);
            ctx.fillStyle = i < 2 ? '#00A6F4' : '#E2E8F0';
            ctx.fill();

            ctx.fillStyle = '#334155';
            ctx.fillRect(54, ry + 14, 128 - i * 16, 8);
            ctx.fillStyle = '#94A3B8';
            ctx.fillRect(54, ry + 28, 92, 6);
        });

        drawRoundedRectPath(ctx, w - 96, h - 38, 76, 24, 12);
        ctx.fillStyle = '#047857';
        ctx.fill();
        ctx.fillStyle = '#FFFFFF';
        ctx.font = 'bold 12px system-ui, sans-serif';
        ctx.fillText('KIRIM', w - 74, h - 22);
    }, disposables);
}

function createPreprocConsoleTexture(statusCss, statusText, disposables) {
    return createCanvasTexture(280, 190, (ctx, w, h) => {
        ctx.fillStyle = '#0B1536';
        ctx.fillRect(0, 0, w, h);

        ctx.strokeStyle = 'rgba(116, 212, 255, 0.35)';
        ctx.lineWidth = 3;
        drawRoundedRectPath(ctx, 6, 6, w - 12, h - 12, 14);
        ctx.stroke();

        ctx.fillStyle = '#74D4FF';
        ctx.font = 'bold 14px monospace';
        ctx.fillText('QA TELEMETRY', 20, 32);

        drawRoundedRectPath(ctx, w - 108, 16, 90, 24, 12);
        ctx.fillStyle = statusCss;
        ctx.fill();
        ctx.fillStyle = '#09122C';
        ctx.font = 'bold 11px system-ui, sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText(statusText, w - 63, 32);
        ctx.textAlign = 'left';

        const metrics = [
            ['NORMALISASI', 0.92, '#38BDF8'],
            ['DEDUPLIKASI', 0.84, '#00A6F4'],
            ['IMPUTASI', 0.96, '#10B981'],
        ];
        metrics.forEach(([lbl, pct, col], i) => {
            const y = 66 + i * 38;
            ctx.fillStyle = '#CBD5E1';
            ctx.font = 'bold 11px monospace';
            ctx.fillText(lbl, 20, y);

            drawRoundedRectPath(ctx, 20, y + 8, w - 40, 10, 5);
            ctx.fillStyle = 'rgba(255, 255, 255, 0.12)';
            ctx.fill();

            drawRoundedRectPath(ctx, 20, y + 8, (w - 40) * pct, 10, 5);
            ctx.fillStyle = col;
            ctx.fill();
        });
    }, disposables);
}

function createDatasetTableTexture(disposables) {
    return createCanvasTexture(260, 210, (ctx, w, h) => {
        ctx.fillStyle = '#FFFFFF';
        ctx.fillRect(0, 0, w, h);

        ctx.fillStyle = '#182654';
        ctx.fillRect(0, 0, w, 38);
        ctx.fillStyle = '#74D4FF';
        ctx.font = 'bold 13px monospace';
        ctx.fillText('DATASET · CSV / XLSX', 16, 24);

        for (let r = 0; r < 4; r++) {
            const y = 50 + r * 36;
            ctx.fillStyle = r % 2 === 0 ? '#F0F9FF' : '#F8FAFC';
            drawRoundedRectPath(ctx, 14, y, w - 28, 28, 8);
            ctx.fill();

            ctx.fillStyle = '#0069A8';
            ctx.fillRect(26, y + 10, 56, 8);
            ctx.fillStyle = '#475569';
            ctx.fillRect(96, y + 10, 74, 8);
            ctx.fillStyle = '#047857';
            ctx.fillRect(186, y + 10, 44, 8);
        }
    }, disposables);
}

function createDashboardScreenTexture(disposables) {
    return createCanvasTexture(360, 200, (ctx, w, h) => {
        const grad = ctx.createLinearGradient(0, 0, w, h);
        grad.addColorStop(0, '#09122C');
        grad.addColorStop(1, '#182654');
        ctx.fillStyle = grad;
        ctx.fillRect(0, 0, w, h);

        ctx.strokeStyle = 'rgba(116, 212, 255, 0.4)';
        ctx.lineWidth = 2;
        ctx.strokeRect(4, 4, w - 8, h - 8);

        ctx.fillStyle = '#74D4FF';
        ctx.font = 'bold 14px monospace';
        ctx.fillText('DASHBOARD SATU DATA', 20, 30);

        // Smooth trend line
        ctx.beginPath();
        const pts = [
            [24, 152],
            [86, 128],
            [144, 136],
            [208, 86],
            [268, 96],
            [332, 54],
        ];
        ctx.moveTo(pts[0][0], pts[0][1]);
        for (let i = 1; i < pts.length; i++) {
            const xc = (pts[i - 1][0] + pts[i][0]) / 2;
            const yc = (pts[i - 1][1] + pts[i][1]) / 2;
            ctx.quadraticCurveTo(pts[i - 1][0], pts[i - 1][1], xc, yc);
        }
        ctx.lineTo(pts[pts.length - 1][0], pts[pts.length - 1][1]);
        ctx.strokeStyle = '#38BDF8';
        ctx.lineWidth = 4;
        ctx.stroke();

        pts.forEach(([px, py], i) => {
            ctx.beginPath();
            ctx.arc(px, py, 5.5, 0, Math.PI * 2);
            ctx.fillStyle = i === pts.length - 1 ? '#10B981' : '#FFFFFF';
            ctx.fill();
        });
    }, disposables);
}

function createSharedActorGeos(disposables) {
    const geos = {
        torso: new THREE.CapsuleGeometry(0.105, 0.21, 8, 16),
        head: new THREE.SphereGeometry(0.102, 20, 20),
        collar: new THREE.TorusGeometry(0.108, 0.014, 8, 28),
        visor: new THREE.TorusGeometry(0.086, 0.012, 6, 18, Math.PI * 0.65),
        arm: new THREE.CapsuleGeometry(0.032, 0.14, 6, 12),
        handOrb: new THREE.SphereGeometry(0.036, 10, 10),
        footRing: new THREE.TorusGeometry(0.14, 0.01, 8, 28),
    };
    Object.values(geos).forEach((g) => disposables.push(g));
    return geos;
}

function createActor(role, sharedGeos, disposables) {
    const palettes = {
        wali: { coat: 0x0069a8, emissive: 0x075985, accent: 0x74d4ff, head: 0xf1f5f9 },
        produsen: { coat: 0x38bdf8, emissive: 0x0284c7, accent: 0xfbbf24, head: 0xf8fafc },
        publik: { coat: 0x10b981, emissive: 0x065f46, accent: 0x74d4ff, head: 0xf1f5f9 },
    };
    const pal = palettes[role] || palettes.wali;

    const coatMat = new THREE.MeshPhysicalMaterial({
        color: pal.coat,
        emissive: pal.emissive,
        emissiveIntensity: 0.45,
        roughness: 0.3,
        metalness: 0.15,
        clearcoat: 0.6,
    });
    const headMat = new THREE.MeshStandardMaterial({
        color: pal.head,
        roughness: 0.28,
        metalness: 0.08,
    });
    const accentMat = new THREE.MeshBasicMaterial({
        color: pal.accent,
        transparent: true,
        opacity: 0.92,
    });
    disposables.push(coatMat, headMat, accentMat);

    const group = new THREE.Group();

    const footRing = new THREE.Mesh(sharedGeos.footRing, accentMat);
    footRing.rotation.x = Math.PI / 2;
    footRing.position.y = 0.012;
    group.add(footRing);

    const body = new THREE.Group();
    group.add(body);

    const torso = new THREE.Mesh(sharedGeos.torso, coatMat);
    torso.position.y = 0.23;
    body.add(torso);

    const collar = new THREE.Mesh(sharedGeos.collar, accentMat);
    collar.rotation.x = Math.PI / 2;
    collar.position.y = 0.37;
    body.add(collar);

    const headGroup = new THREE.Group();
    headGroup.position.y = 0.51;
    const headMesh = new THREE.Mesh(sharedGeos.head, headMat);
    const visor = new THREE.Mesh(sharedGeos.visor, accentMat);
    visor.rotation.x = Math.PI / 2;
    visor.rotation.z = Math.PI * 0.18;
    visor.position.set(0, 0.01, 0.032);
    headGroup.add(headMesh, visor);
    body.add(headGroup);

    const leftArmPivot = new THREE.Group();
    leftArmPivot.position.set(-0.145, 0.34, 0);
    const leftArm = new THREE.Mesh(sharedGeos.arm, coatMat);
    leftArm.position.y = -0.09;
    const leftHand = new THREE.Mesh(sharedGeos.handOrb, headMat);
    leftHand.position.y = -0.19;
    leftArmPivot.add(leftArm, leftHand);
    body.add(leftArmPivot);

    const rightArmPivot = new THREE.Group();
    rightArmPivot.position.set(0.145, 0.34, 0);
    const rightArm = new THREE.Mesh(sharedGeos.arm, coatMat);
    rightArm.position.y = -0.09;
    const rightHand = new THREE.Mesh(sharedGeos.handOrb, headMat);
    rightHand.position.y = -0.19;
    rightArmPivot.add(rightArm, rightHand);
    body.add(rightArmPivot);

    return {
        group,
        body,
        head: headGroup,
        leftArmPivot,
        rightArmPivot,
        rightHand,
    };
}

function buildPipelineObservatory(el, scene, camera, renderer, disposables) {
    const m = parseMetrics(el);
    const openForms = Math.max(0, Number(m.openForms) || 0);
    const totalForms = Math.max(0, Number(m.totalForms) || 0);
    const submissions = Math.max(0, Number(m.submissions) || 0);
    const rawSubmissions = Math.max(0, Number(m.rawSubmissions) || 0);
    const validSubmissions = Math.max(0, Number(m.validSubmissions) || 0);
    const formsNeedPreproc = Math.max(0, Number(m.formsNeedPreproc) || 0);
    const formsReady = Math.max(0, Number(m.formsReady) || 0);
    const publishedDatasets = Math.max(0, Number(m.publishedDatasets) || 0);
    const visualizations = Math.max(0, Number(m.visualizations) || 0);

    const preprocNeedsWork = formsNeedPreproc > 0 || rawSubmissions > 0;
    const preprocHealthy = !preprocNeedsWork && (formsReady > 0 || validSubmissions > 0);
    const preprocAccentHex = preprocNeedsWork ? 0xf59e0b : (preprocHealthy ? 0x10b981 : 0x00a6f4);
    const preprocAccentCss = preprocNeedsWork ? '#FBBF24' : (preprocHealthy ? '#34D399' : '#38BDF8');
    const preprocEmissiveHex = preprocNeedsWork ? 0x78350f : (preprocHealthy ? 0x065f46 : 0x075985);
    const preprocStatusText = preprocNeedsWork ? 'ANTREAN' : (preprocHealthy ? 'TERVALIDASI' : 'SIAP');

    scene.fog = new THREE.FogExp2(0x0b1536, 0.028);

    // Lighting rig
    const ambientLight = new THREE.AmbientLight(0x93c5fd, 0.72);
    const hemiLight = new THREE.HemisphereLight(0x74d4ff, 0x09122c, 1.0);
    const keyLight = new THREE.DirectionalLight(0xe0f2fe, 2.2);
    keyLight.position.set(6, 11, 8);
    const rimLight = new THREE.DirectionalLight(0x0069a8, 1.15);
    rimLight.position.set(-7, 5, -6);
    const focusLight = new THREE.PointLight(0x00a6f4, 24, 15, 1.6);
    focusLight.position.set(0, 2.5, -0.72);
    scene.add(ambientLight, hemiLight, keyLight, rimLight, focusLight);

    // OrbitControls (zoom disabled so page scrolling never gets hijacked)
    camera.position.set(0, 3.6, 11.2);
    const defaultTarget = new THREE.Vector3(0, 0.36, 0);
    camera.lookAt(defaultTarget);

    const controls = new OrbitControls(camera, renderer.domElement);
    controls.target.copy(defaultTarget);
    controls.enableDamping = true;
    controls.dampingFactor = 0.06;
    controls.enablePan = false;
    controls.enableZoom = false;
    controls.minPolarAngle = Math.PI * 0.2;
    controls.maxPolarAngle = Math.PI * 0.47;
    controls.autoRotate = true;
    controls.autoRotateSpeed = 0.32;

    let userInteracted = false;
    let lockedFocusKey = 'overview';

    const onControlStart = () => {
        userInteracted = true;
        controls.autoRotate = false;
        window.dispatchEvent(new CustomEvent('wd:stage-interacted'));
    };
    controls.addEventListener('start', onControlStart);

    const root = new THREE.Group();
    scene.add(root);

    // Smooth Circular Floor Telemetry Rings
    const floorGroup = new THREE.Group();
    floorGroup.position.y = -0.34;
    root.add(floorGroup);

    [
        { r: 2.1, opacity: 0.18, color: 0x38bdf8 },
        { r: 3.7, opacity: 0.14, color: 0x7dd3fc },
        { r: 5.1, opacity: 0.1, color: 0x1e40af },
    ].forEach((spec) => {
        const ringGeo = new THREE.RingGeometry(spec.r - 0.015, spec.r, 96);
        const ringMat = new THREE.MeshBasicMaterial({
            color: spec.color,
            transparent: true,
            opacity: spec.opacity,
            side: THREE.DoubleSide,
        });
        const ringMesh = new THREE.Mesh(ringGeo, ringMat);
        ringMesh.rotation.x = -Math.PI / 2;
        floorGroup.add(ringMesh);
        disposables.push(ringGeo, ringMat);
    });

    const scanRingGeo = new THREE.RingGeometry(1.0, 1.05, 72);
    const scanRingMat = new THREE.MeshBasicMaterial({
        color: 0x38bdf8,
        transparent: true,
        opacity: 0.3,
        side: THREE.DoubleSide,
    });
    const scanRing = new THREE.Mesh(scanRingGeo, scanRingMat);
    scanRing.rotation.x = -Math.PI / 2;
    floorGroup.add(scanRing);
    disposables.push(scanRingGeo, scanRingMat);

    // Shared Pedestal & Actor Geometries (Zero Boxes!)
    const sharedActorGeos = createSharedActorGeos(disposables);
    const pedestalGeo = new THREE.CylinderGeometry(0.76, 0.84, 0.12, 48);
    const pedestalMat = new THREE.MeshStandardMaterial({
        color: 0x11204d,
        metalness: 0.52,
        roughness: 0.32,
    });
    const rimRingGeo = new THREE.TorusGeometry(0.8, 0.02, 12, 64);
    const hitGeo = new THREE.CylinderGeometry(0.98, 0.98, 2.0, 16);
    const hitMat = new THREE.MeshBasicMaterial({ visible: false });
    disposables.push(pedestalGeo, pedestalMat, rimRingGeo, hitGeo, hitMat);

    const stageDefs = [
        { key: 'formulir', code: '01', label: 'FORMULIR', pos: new THREE.Vector3(-3.8, 0, 0.72), accent: openForms > 0 ? 0x38bdf8 : 0x0ea5e9, css: '#38BDF8' },
        { key: 'respons', code: '02', label: 'RESPONS', pos: new THREE.Vector3(-1.95, 0, -0.32), accent: submissions > 0 ? 0x38bdf8 : 0x60a5fa, css: '#74D4FF' },
        { key: 'preprocessing', code: '03', label: 'PRE-PROC', pos: new THREE.Vector3(0, 0.14, -0.72), accent: preprocAccentHex, css: preprocAccentCss },
        { key: 'dataset', code: '04', label: 'DATASET', pos: new THREE.Vector3(1.95, 0, -0.32), accent: publishedDatasets > 0 ? 0x10b981 : 0x38bdf8, css: publishedDatasets > 0 ? '#34D399' : '#38BDF8' },
        { key: 'visualisasi', code: '05', label: 'DASHBOARD', pos: new THREE.Vector3(3.8, 0, 0.72), accent: visualizations > 0 ? 0x74d4ff : 0x38bdf8, css: '#74D4FF' },
    ];

    const stageNodes = [];
    const hitTargets = [];

    // Shared glass material
    const glassMat = new THREE.MeshPhysicalMaterial({
        color: 0xbae6fd,
        transparent: true,
        opacity: 0.28,
        roughness: 0.12,
        metalness: 0.1,
        clearcoat: 1.0,
        side: THREE.DoubleSide,
    });
    disposables.push(glassMat);

    stageDefs.forEach((def, idx) => {
        const group = new THREE.Group();
        group.position.copy(def.pos);
        root.add(group);

        const pedestal = new THREE.Mesh(pedestalGeo, pedestalMat);
        pedestal.position.y = -0.27;
        group.add(pedestal);

        const haloMat = new THREE.MeshBasicMaterial({
            color: def.accent,
            transparent: true,
            opacity: 0.65,
        });
        const halo = new THREE.Mesh(rimRingGeo, haloMat);
        halo.rotation.x = Math.PI / 2;
        halo.position.y = -0.2;
        group.add(halo);
        disposables.push(haloMat);

        const badge = createBadgeSprite(def.code, def.label, def.css, disposables);
        badge.position.set(0, -0.46, 0.88);
        group.add(badge);

        const hitMesh = new THREE.Mesh(hitGeo, hitMat);
        hitMesh.position.y = 0.55;
        hitMesh.userData.stageKey = def.key;
        group.add(hitMesh);
        hitTargets.push(hitMesh);

        const vignette = new THREE.Group();
        vignette.position.y = -0.2;
        group.add(vignette);

        const nodeState = {
            key: def.key,
            index: idx,
            group,
            vignette,
            haloMat,
            accent: def.accent,
            hoverMix: 0,
            parts: {},
        };

        // === PHASE 01: INSTRUMEN FORMULIR (Rounded GForm Tablet + Wali Data Actor w/ Stylus) ===
        if (def.key === 'formulir') {
            const tabletGroup = new THREE.Group();
            tabletGroup.position.set(0.12, 0.62, -0.05);
            tabletGroup.rotation.set(-0.22, -0.25, 0);

            const frameGeo = createRoundedRectGeometry(0.84, 1.02, 0.1);
            const frameMat = new THREE.MeshStandardMaterial({
                color: 0x0f1c44,
                metalness: 0.45,
                roughness: 0.28,
                side: THREE.DoubleSide,
            });
            const screenGeo = createRoundedRectGeometry(0.76, 0.94, 0.08);
            const screenTex = createGFormTexture(disposables);
            const screenMat = new THREE.MeshBasicMaterial({ map: screenTex });
            const scanBarGeo = new THREE.CapsuleGeometry(0.015, 0.56, 4, 8);
            const scanBarMat = new THREE.MeshBasicMaterial({ color: 0x00a6f4, transparent: true, opacity: 0.78 });

            const frameMesh = new THREE.Mesh(frameGeo, frameMat);
            const screenMesh = new THREE.Mesh(screenGeo, screenMat);
            screenMesh.position.z = 0.012;
            const scanBar = new THREE.Mesh(scanBarGeo, scanBarMat);
            scanBar.rotation.z = Math.PI / 2;
            scanBar.position.set(0, 0.12, 0.024);

            tabletGroup.add(frameMesh, screenMesh, scanBar);
            vignette.add(tabletGroup);
            disposables.push(frameGeo, frameMat, screenGeo, screenMat, scanBarGeo, scanBarMat);

            const actor = createActor('wali', sharedActorGeos, disposables);
            actor.group.position.set(-0.36, 0, 0.22);
            actor.group.rotation.y = 0.68;

            const stylusGeo = new THREE.CylinderGeometry(0.012, 0.004, 0.24, 10);
            const stylusMat = new THREE.MeshBasicMaterial({ color: 0x74d4ff });
            const stylus = new THREE.Mesh(stylusGeo, stylusMat);
            stylus.rotation.x = Math.PI * 0.45;
            stylus.position.set(0, -0.22, 0.08);
            actor.rightArmPivot.add(stylus);
            disposables.push(stylusGeo, stylusMat);

            vignette.add(actor.group);
            nodeState.parts = { tabletGroup, scanBar, actor };
        }

        // === PHASE 02: RESPONS MASUK (Lathe Collector Bowl + 2 Produsen Actors Sending Orbs) ===
        else if (def.key === 'respons') {
            const lathePts = [
                new THREE.Vector2(0.02, 0.0),
                new THREE.Vector2(0.14, 0.08),
                new THREE.Vector2(0.24, 0.26),
                new THREE.Vector2(0.42, 0.44),
                new THREE.Vector2(0.45, 0.46),
                new THREE.Vector2(0.39, 0.44),
                new THREE.Vector2(0.18, 0.24),
                new THREE.Vector2(0.01, 0.06),
            ];
            const bowlGeo = new THREE.LatheGeometry(lathePts, 36);
            const bowlMesh = new THREE.Mesh(bowlGeo, glassMat);
            bowlMesh.position.y = 0.18;

            const bowlHaloGeo = new THREE.TorusGeometry(0.44, 0.016, 10, 48);
            const bowlHaloMat = new THREE.MeshBasicMaterial({ color: 0x38bdf8, transparent: true, opacity: 0.78 });
            const bowlHalo = new THREE.Mesh(bowlHaloGeo, bowlHaloMat);
            bowlHalo.rotation.x = Math.PI / 2;
            bowlHalo.position.y = 0.63;
            vignette.add(bowlMesh, bowlHalo);
            disposables.push(bowlGeo, bowlHaloGeo, bowlHaloMat);

            const mhsActor = createActor('produsen', sharedActorGeos, disposables);
            mhsActor.group.position.set(-0.48, 0, 0.24);
            mhsActor.group.rotation.y = 1.1;

            const dosenActor = createActor('produsen', sharedActorGeos, disposables);
            dosenActor.group.position.set(0.48, 0, 0.24);
            dosenActor.group.rotation.y = -1.1;

            const miniPadGeo = createRoundedRectGeometry(0.22, 0.16, 0.03);
            const miniPadMat = new THREE.MeshBasicMaterial({ color: 0x74d4ff, transparent: true, opacity: 0.72, side: THREE.DoubleSide });
            const padL = new THREE.Mesh(miniPadGeo, miniPadMat);
            padL.position.set(-0.32, 0.36, 0.16);
            padL.rotation.set(-0.5, 0.9, 0);
            const padR = new THREE.Mesh(miniPadGeo, miniPadMat);
            padR.position.set(0.32, 0.36, 0.16);
            padR.rotation.set(-0.5, -0.9, 0);
            vignette.add(mhsActor.group, dosenActor.group, padL, padR);
            disposables.push(miniPadGeo, miniPadMat);

            // 4 glowing submission orbs arcing from producers into the collector bowl
            const orbGeo = new THREE.SphereGeometry(0.055, 14, 14);
            const orbMat = new THREE.MeshBasicMaterial({ color: 0x74d4ff });
            disposables.push(orbGeo, orbMat);
            const flightOrbs = Array.from({ length: 4 }, (_, oi) => {
                const orb = new THREE.Mesh(orbGeo, orbMat);
                vignette.add(orb);
                return { orb, side: oi % 2 === 0 ? -1 : 1, offset: oi * 0.25 };
            });

            nodeState.parts = { bowlMesh, bowlHalo, mhsActor, dosenActor, flightOrbs };
        }

        // === PHASE 03: PRE-PROCESSING WAJIB (Optical Filtration Chamber + QA Console + Lead Wali Actor) ===
        else if (def.key === 'preprocessing') {
            const chamberPts = [
                new THREE.Vector2(0.01, 0.0),
                new THREE.Vector2(0.36, 0.04),
                new THREE.Vector2(0.22, 0.34),
                new THREE.Vector2(0.36, 0.66),
                new THREE.Vector2(0.01, 0.7),
            ];
            const chamberGeo = new THREE.LatheGeometry(chamberPts, 40);
            const chamberMesh = new THREE.Mesh(chamberGeo, glassMat);
            chamberMesh.position.y = 0.28;
            vignette.add(chamberMesh);

            const outerGyroGeo = new THREE.TorusGeometry(0.58, 0.022, 14, 64);
            const innerGyroGeo = new THREE.TorusGeometry(0.42, 0.018, 12, 48);
            const gyroMat = new THREE.MeshStandardMaterial({
                color: preprocAccentHex,
                emissive: preprocEmissiveHex,
                emissiveIntensity: 0.6,
                metalness: 0.55,
                roughness: 0.22,
            });
            const outerGyro = new THREE.Mesh(outerGyroGeo, gyroMat);
            outerGyro.position.y = 0.63;
            const innerGyro = new THREE.Mesh(innerGyroGeo, gyroMat);
            innerGyro.position.y = 0.63;

            const coreGeo = new THREE.SphereGeometry(0.18, 24, 24);
            const coreMat = new THREE.MeshPhysicalMaterial({
                color: preprocAccentHex,
                emissive: preprocEmissiveHex,
                emissiveIntensity: 0.9,
                metalness: 0.25,
                roughness: 0.14,
                clearcoat: 1.0,
            });
            const coreSphere = new THREE.Mesh(coreGeo, coreMat);
            coreSphere.position.y = 0.63;

            const laserGeo = new THREE.CircleGeometry(0.33, 36);
            const laserMat = new THREE.MeshBasicMaterial({
                color: 0x38bdf8,
                transparent: true,
                opacity: 0.42,
                side: THREE.DoubleSide,
            });
            const laserDisc = new THREE.Mesh(laserGeo, laserMat);
            laserDisc.rotation.x = Math.PI / 2;
            laserDisc.position.y = 0.63;

            // QA Console Screen
            const qaFrameGeo = createRoundedRectGeometry(0.54, 0.36, 0.05);
            const qaTex = createPreprocConsoleTexture(preprocAccentCss, preprocStatusText, disposables);
            const qaMat = new THREE.MeshBasicMaterial({ map: qaTex, side: THREE.DoubleSide });
            const qaScreen = new THREE.Mesh(qaFrameGeo, qaMat);
            qaScreen.position.set(0.42, 0.44, 0.36);
            qaScreen.rotation.set(-0.35, -0.45, 0);

            // Lead Wali Data Actor operating the filtration chamber
            const actor = createActor('wali', sharedActorGeos, disposables);
            actor.group.position.set(-0.42, 0, 0.34);
            actor.group.rotation.y = 0.62;

            // Raw-to-clean orbs spiraling through filtration chamber
            const filterOrbGeo = new THREE.SphereGeometry(0.042, 12, 12);
            const rawOrbMat = new THREE.MeshBasicMaterial({ color: 0xfbbf24 });
            const cleanOrbMat = new THREE.MeshBasicMaterial({ color: 0x10b981 });
            const filterOrbs = Array.from({ length: 6 }, (_, fi) => {
                const mat = fi % 2 === 0 ? rawOrbMat : cleanOrbMat;
                const mesh = new THREE.Mesh(filterOrbGeo, mat);
                vignette.add(mesh);
                return { mesh, isClean: fi % 2 === 1, phase: fi * 1.05 };
            });

            vignette.add(outerGyro, innerGyro, coreSphere, laserDisc, qaScreen, actor.group);
            disposables.push(
                chamberGeo, outerGyroGeo, innerGyroGeo, gyroMat,
                coreGeo, coreMat, laserGeo, laserMat,
                qaFrameGeo, qaMat, filterOrbGeo, rawOrbMat, cleanOrbMat
            );

            nodeState.parts = { outerGyro, innerGyro, coreSphere, laserDisc, actor, filterOrbs };
        }

        // === PHASE 04: DATASET TEROLAH (3-Tier Cylindrical Database Vault + Table Panel + Wali Curator) ===
        else if (def.key === 'dataset') {
            const platterGeo = new THREE.CylinderGeometry(0.36, 0.36, 0.1, 40);
            const platterMat = new THREE.MeshPhysicalMaterial({
                color: publishedDatasets > 0 ? 0x047857 : 0x0069a8,
                emissive: publishedDatasets > 0 ? 0x065f46 : 0x075985,
                emissiveIntensity: 0.55,
                metalness: 0.4,
                roughness: 0.22,
                clearcoat: 0.8,
            });
            const platterRingGeo = new THREE.TorusGeometry(0.37, 0.014, 10, 48);
            const platterRingMat = new THREE.MeshBasicMaterial({
                color: publishedDatasets > 0 ? 0x34d399 : 0x74d4ff,
            });
            disposables.push(platterGeo, platterMat, platterRingGeo, platterRingMat);

            const platters = [];
            for (let p = 0; p < 3; p++) {
                const holder = new THREE.Group();
                holder.add(new THREE.Mesh(platterGeo, platterMat));
                const ring = new THREE.Mesh(platterRingGeo, platterRingMat);
                ring.rotation.x = Math.PI / 2;
                ring.position.y = 0.045;
                holder.add(ring);
                holder.position.set(-0.14, 0.28 + p * 0.19, -0.06);
                vignette.add(holder);
                platters.push({ holder, baseY: 0.28 + p * 0.19, idx: p });
            }

            const sheetGeo = createRoundedRectGeometry(0.56, 0.44, 0.06);
            const sheetTex = createDatasetTableTexture(disposables);
            const sheetMat = new THREE.MeshBasicMaterial({ map: sheetTex, side: THREE.DoubleSide });
            const sheetMesh = new THREE.Mesh(sheetGeo, sheetMat);
            sheetMesh.position.set(0.38, 0.58, 0.08);
            sheetMesh.rotation.set(-0.12, -0.38, 0);
            vignette.add(sheetMesh);
            disposables.push(sheetGeo, sheetMat);

            const actor = createActor('wali', sharedActorGeos, disposables);
            actor.group.position.set(0.36, 0, 0.34);
            actor.group.rotation.y = -0.78;
            vignette.add(actor.group);

            nodeState.parts = { platters, sheetMesh, actor };
        }

        // === PHASE 05: VISUALISASI DASHBOARD (Curved Screen + 3D Donut + Capsule Bars + Publik Actor) ===
        else if (def.key === 'visualisasi') {
            const screenArcGeo = new THREE.CylinderGeometry(0.68, 0.68, 0.46, 32, 1, true, -0.82, 1.64);
            const dashTex = createDashboardScreenTexture(disposables);
            const screenArcMat = new THREE.MeshBasicMaterial({
                map: dashTex,
                side: THREE.DoubleSide,
                transparent: true,
                opacity: 0.95,
            });
            const screenArc = new THREE.Mesh(screenArcGeo, screenArcMat);
            screenArc.position.set(0, 0.68, 0.08);
            screenArc.rotation.y = Math.PI;
            vignette.add(screenArc);
            disposables.push(screenArcGeo, screenArcMat);

            // Smooth 3D Multi-Segment Donut Chart
            const donutGroup = new THREE.Group();
            donutGroup.position.set(-0.24, 0.36, 0.08);
            donutGroup.rotation.x = 0.25;
            const arcSpecs = [
                { start: 0, len: Math.PI * 0.85, color: 0x00a6f4 },
                { start: Math.PI * 0.95, len: Math.PI * 0.55, color: 0x10b981 },
                { start: Math.PI * 1.6, len: Math.PI * 0.32, color: 0x74d4ff },
            ];
            arcSpecs.forEach((a) => {
                const segGeo = new THREE.TorusGeometry(0.18, 0.044, 12, 28, a.len);
                const segMat = new THREE.MeshStandardMaterial({
                    color: a.color,
                    emissive: a.color,
                    emissiveIntensity: 0.45,
                    roughness: 0.25,
                });
                const segMesh = new THREE.Mesh(segGeo, segMat);
                segMesh.rotation.z = a.start;
                donutGroup.add(segMesh);
                disposables.push(segGeo, segMat);
            });
            vignette.add(donutGroup);

            // 4 Smooth Rounded Capsule Bars
            const pillBars = [];
            const pillGeo = new THREE.CapsuleGeometry(0.036, 0.18, 6, 12);
            const pillMat = new THREE.MeshStandardMaterial({
                color: 0x38bdf8,
                emissive: 0x0069a8,
                emissiveIntensity: 0.55,
                roughness: 0.2,
            });
            disposables.push(pillGeo, pillMat);
            for (let b = 0; b < 4; b++) {
                const bar = new THREE.Mesh(pillGeo, pillMat);
                const baseScale = 0.65 + b * 0.26;
                bar.scale.y = baseScale;
                bar.position.set(0.1 + b * 0.11, 0.24 + baseScale * 0.08, 0.12);
                vignette.add(bar);
                pillBars.push({ bar, baseScale, phase: b * 0.9 });
            }

            const actor = createActor('publik', sharedActorGeos, disposables);
            actor.group.position.set(-0.34, 0, 0.42);
            actor.group.rotation.y = 2.65;
            vignette.add(actor.group);

            nodeState.parts = { donutGroup, pillBars, actor };
        }

        stageNodes.push(nodeState);
    });

    // Smooth Data Conduit Spine + Overhead Arc
    const curvePoints = stageDefs.map((d, i) => new THREE.Vector3(d.pos.x, i === 2 ? 0.56 : 0.4, d.pos.z));
    const pipelineCurve = new THREE.CatmullRomCurve3(curvePoints, false, 'catmullrom', 0.45);
    const tubeGeo = new THREE.TubeGeometry(pipelineCurve, 96, 0.02, 8, false);
    const tubeMat = new THREE.MeshBasicMaterial({
        color: 0x38bdf8,
        transparent: true,
        opacity: 0.34,
    });
    root.add(new THREE.Mesh(tubeGeo, tubeMat));
    disposables.push(tubeGeo, tubeMat);

    const overheadCurve = new THREE.CatmullRomCurve3([
        new THREE.Vector3(-3.8, 0.45, 0.72),
        new THREE.Vector3(0, 1.95, -0.95),
        new THREE.Vector3(3.8, 0.45, 0.72),
    ]);
    const overheadGeo = new THREE.BufferGeometry().setFromPoints(overheadCurve.getPoints(64));
    const overheadMat = new THREE.LineBasicMaterial({
        color: 0x7dd3fc,
        transparent: true,
        opacity: 0.16,
    });
    root.add(new THREE.Line(overheadGeo, overheadMat));
    disposables.push(overheadGeo, overheadMat);

    // 120 Smooth Spherical Data Packets (1 draw call!)
    const PACKET_COUNT = 120;
    const packetGeo = new THREE.SphereGeometry(0.044, 10, 10);
    const packetMat = new THREE.MeshBasicMaterial({ color: 0xffffff });
    const packetMesh = new THREE.InstancedMesh(packetGeo, packetMat, PACKET_COUNT);
    packetMesh.instanceMatrix.setUsage(THREE.DynamicDrawUsage);
    root.add(packetMesh);
    disposables.push(packetGeo, packetMat);

    const packetOffsets = new Float32Array(PACKET_COUNT);
    const packetSpeeds = new Float32Array(PACKET_COUNT);
    const packetRadial = new Float32Array(PACKET_COUNT);
    const packetPhase = new Float32Array(PACKET_COUNT);
    for (let i = 0; i < PACKET_COUNT; i++) {
        packetOffsets[i] = i / PACKET_COUNT;
        packetSpeeds[i] = 0.058 + (i % 7) * 0.007;
        packetRadial[i] = 0.04 + ((i * 11) % 9) * 0.011;
        packetPhase[i] = i * 0.62;
    }

    // Ambient Bokeh Dust
    const DUST_COUNT = 260;
    const dustPos = new Float32Array(DUST_COUNT * 3);
    const dustSpeeds = new Float32Array(DUST_COUNT);
    for (let i = 0; i < DUST_COUNT; i++) {
        dustPos[i * 3] = (Math.random() - 0.5) * 13;
        dustPos[i * 3 + 1] = Math.random() * 4.4 - 0.5;
        dustPos[i * 3 + 2] = (Math.random() - 0.5) * 7.5;
        dustSpeeds[i] = 0.05 + Math.random() * 0.12;
    }
    const dustGeo = new THREE.BufferGeometry();
    dustGeo.setAttribute('position', new THREE.BufferAttribute(dustPos, 3));
    const bokehTex = createBokehTexture();
    const dustMat = new THREE.PointsMaterial({
        size: 0.1,
        map: bokehTex,
        transparent: true,
        opacity: 0.55,
        depthWrite: false,
        blending: THREE.AdditiveBlending,
    });
    root.add(new THREE.Points(dustGeo, dustMat));
    disposables.push(dustGeo, dustMat, bokehTex);

    // Pre-allocated math helpers
    const dummy = new THREE.Object3D();
    const ptVec = new THREE.Vector3();
    const colorRaw = new THREE.Color(preprocNeedsWork ? 0xfbbf24 : 0x38bdf8);
    const colorClean = new THREE.Color(0x10b981);
    const colorCyan = new THREE.Color(0x74d4ff);
    const tempColor = new THREE.Color();
    const desiredCamPos = new THREE.Vector3(0, 3.6, 11.2);
    const desiredTarget = new THREE.Vector3(0, 0.36, 0);
    const focusLightTarget = new THREE.Vector3(0, 2.4, -0.72);
    let cameraTweenActive = false;

    const computeOverviewCam = (aspect) => {
        const tan = Math.tan((camera.fov * Math.PI) / 360);
        const halfW = 4.95;
        const halfH = 1.95;
        const zByWidth = halfW / (0.86 * tan * Math.max(aspect, 0.65));
        const zByHeight = halfH / (0.78 * tan);
        const zDist = Math.max(9.8, zByWidth, zByHeight);
        return { x: 0, y: Math.max(3.35, zDist * 0.31), z: zDist };
    };

    const focusOnStage = (stageKey) => {
        lockedFocusKey = stageKey || 'overview';
        if (lockedFocusKey === 'overview') {
            const ov = computeOverviewCam(camera.aspect || 1.6);
            desiredTarget.set(0, 0.36, 0);
            desiredCamPos.set(ov.x, ov.y, ov.z);
            controls.autoRotate = !userInteracted;
        } else {
            const node = stageNodes.find((n) => n.key === lockedFocusKey);
            if (node) {
                desiredTarget.set(node.group.position.x * 0.88, 0.42, node.group.position.z * 0.85);
                desiredCamPos.set(node.group.position.x * 0.86, 1.98, node.group.position.z + 4.15);
                controls.autoRotate = false;
            }
        }
        cameraTweenActive = true;
    };

    const onCameraFocus = (e) => {
        focusOnStage(e?.detail?.stageKey || 'overview');
    };
    window.addEventListener('wd:camera-focus', onCameraFocus);

    // Single Shared Raycaster + NDC tracking
    const raycaster = new THREE.Raycaster();
    const mouse = new THREE.Vector2(-99, -99);
    let pointerInside = false;
    let hoveredKey = null;
    let downPos = { x: 0, y: 0 };

    const updateNdc = (clientX, clientY) => {
        const rect = renderer.domElement.getBoundingClientRect();
        if (!rect.width || !rect.height) return;
        mouse.x = ((clientX - rect.left) / rect.width) * 2 - 1;
        mouse.y = -((clientY - rect.top) / rect.height) * 2 + 1;
        pointerInside = true;
    };

    const onPointerMove = (e) => updateNdc(e.clientX, e.clientY);
    const onPointerLeave = () => {
        pointerInside = false;
        mouse.set(-99, -99);
        renderer.domElement.style.cursor = 'grab';
        if (hoveredKey !== null) {
            hoveredKey = null;
            window.dispatchEvent(new CustomEvent('wd:stage-hover', { detail: { stageKey: null } }));
        }
    };
    const onPointerDown = (e) => {
        downPos = { x: e.clientX, y: e.clientY };
        updateNdc(e.clientX, e.clientY);
    };
    const onPointerUp = (e) => {
        const dist = Math.hypot(e.clientX - downPos.x, e.clientY - downPos.y);
        if (dist > 6) return;
        updateNdc(e.clientX, e.clientY);
        raycaster.setFromCamera(mouse, camera);
        const hits = raycaster.intersectObjects(hitTargets, true);
        if (hits.length > 0) {
            const key = hits[0].object.userData.stageKey;
            if (key) {
                focusOnStage(key);
                window.dispatchEvent(new CustomEvent('wd:stage-select', { detail: { stageKey: key } }));
            }
        }
    };
    const onTouchStart = (e) => {
        if (e.touches[0]) {
            downPos = { x: e.touches[0].clientX, y: e.touches[0].clientY };
            updateNdc(e.touches[0].clientX, e.touches[0].clientY);
        }
    };
    const onTouchMove = (e) => {
        if (e.touches[0]) {
            updateNdc(e.touches[0].clientX, e.touches[0].clientY);
        }
    };

    renderer.domElement.style.cursor = 'grab';
    renderer.domElement.addEventListener('pointermove', onPointerMove);
    renderer.domElement.addEventListener('pointerleave', onPointerLeave);
    renderer.domElement.addEventListener('pointerdown', onPointerDown);
    renderer.domElement.addEventListener('pointerup', onPointerUp);
    renderer.domElement.addEventListener('touchstart', onTouchStart, { passive: true });
    renderer.domElement.addEventListener('touchmove', onTouchMove, { passive: true });

    let elapsed = 0;

    return {
        resize(aspect) {
            if (lockedFocusKey === 'overview') {
                const ov = computeOverviewCam(aspect);
                desiredCamPos.set(ov.x, ov.y, ov.z);
                if (!userInteracted) {
                    camera.position.set(ov.x, ov.y, ov.z);
                    controls.target.copy(defaultTarget);
                }
            }
        },
        update(dt, reducedMotion) {
            elapsed += dt;

            if (pointerInside) {
                raycaster.setFromCamera(mouse, camera);
                const hits = raycaster.intersectObjects(hitTargets, true);
                const nextHover = hits.length > 0 ? hits[0].object.userData.stageKey : null;
                renderer.domElement.style.cursor = nextHover ? 'pointer' : 'grab';
                if (nextHover !== hoveredKey) {
                    hoveredKey = nextHover;
                    window.dispatchEvent(new CustomEvent('wd:stage-hover', { detail: { stageKey: hoveredKey } }));
                }
            }

            if (cameraTweenActive) {
                const lerpRate = reducedMotion ? 1 : Math.min(1, dt * 4.0);
                controls.target.lerp(desiredTarget, lerpRate);
                camera.position.lerp(desiredCamPos, lerpRate);
                if (camera.position.distanceTo(desiredCamPos) < 0.04 && controls.target.distanceTo(desiredTarget) < 0.04) {
                    cameraTweenActive = false;
                }
            }

            // Telemetry scan ring pulse
            const scanT = (elapsed * 0.34) % 1;
            scanRing.scale.setScalar(1 + scanT * 4.2);
            scanRingMat.opacity = (1 - scanT) * 0.26;

            // Animate 5 phase vignettes + actors
            let activeNodeForLight = stageNodes[2];
            const activeKey = hoveredKey || (lockedFocusKey !== 'overview' ? lockedFocusKey : null);

            stageNodes.forEach((node) => {
                const isHot = node.key === activeKey;
                if (isHot) activeNodeForLight = node;
                node.hoverMix += ((isHot ? 1 : 0) - node.hoverMix) * Math.min(1, dt * 8 || 1);
                node.haloMat.opacity = 0.5 + node.hoverMix * 0.45;
                node.vignette.scale.setScalar(1 + node.hoverMix * 0.08);

                const p = node.parts;

                if (node.key === 'formulir' && p.actor) {
                    p.tabletGroup.position.y = 0.62 + Math.sin(elapsed * 1.4) * 0.025;
                    p.scanBar.position.y = 0.2 - ((elapsed * 0.45) % 1) * 0.55;

                    // Wali Data actor breathing & writing motion
                    p.actor.body.position.y = Math.sin(elapsed * 2.2) * 0.014;
                    p.actor.head.rotation.y = Math.sin(elapsed * 1.1) * 0.14;
                    p.actor.head.rotation.x = 0.12 + Math.sin(elapsed * 2.2) * 0.05;
                    p.actor.rightArmPivot.rotation.x = -0.92 + Math.sin(elapsed * 3.4) * 0.16;
                    p.actor.rightArmPivot.rotation.z = -0.18 + Math.cos(elapsed * 2.6) * 0.12;
                    p.actor.leftArmPivot.rotation.x = -0.35;
                } else if (node.key === 'respons' && p.mhsActor) {
                    const b1 = Math.sin(elapsed * 2.3);
                    const b2 = Math.cos(elapsed * 2.1);
                    p.mhsActor.body.position.y = b1 * 0.012;
                    p.mhsActor.rightArmPivot.rotation.x = -0.75 + Math.max(0, Math.sin(elapsed * 2.4)) * -0.35;
                    p.dosenActor.body.position.y = b2 * 0.012;
                    p.dosenActor.leftArmPivot.rotation.x = -0.75 + Math.max(0, Math.cos(elapsed * 2.4)) * -0.35;

                    p.bowlHalo.scale.setScalar(1 + Math.sin(elapsed * 3.2) * 0.045);

                    // Parabolic submission orb arcs from left/right actors into center collector bowl
                    p.flightOrbs.forEach((item) => {
                        const t = (elapsed * 0.58 + item.offset) % 1;
                        const sx = item.side * 0.34;
                        const sy = 0.4;
                        const sz = 0.18;
                        const ex = 0;
                        const ey = 0.58;
                        const ez = 0;
                        const arc = Math.sin(t * Math.PI) * 0.48;
                        item.orb.position.set(
                            sx + (ex - sx) * t,
                            sy + (ey - sy) * t + arc,
                            sz + (ez - sz) * t
                        );
                        item.orb.scale.setScalar(0.55 + Math.sin(t * Math.PI) * 0.65);
                    });
                } else if (node.key === 'preprocessing' && p.actor) {
                    p.outerGyro.rotation.x = elapsed * 0.5;
                    p.outerGyro.rotation.y = elapsed * 0.34;
                    p.innerGyro.rotation.y = -elapsed * 0.68;
                    p.innerGyro.rotation.z = elapsed * 0.38;
                    p.coreSphere.scale.setScalar(1 + Math.sin(elapsed * 2.5) * 0.06);
                    p.laserDisc.position.y = 0.63 + Math.sin(elapsed * 2.1) * 0.24;

                    // Lead Wali Data orchestrating arms
                    p.actor.body.position.y = Math.sin(elapsed * 2.1) * 0.014;
                    p.actor.head.rotation.y = Math.sin(elapsed * 0.9) * 0.16;
                    p.actor.leftArmPivot.rotation.x = -0.78 + Math.sin(elapsed * 2.2) * 0.18;
                    p.actor.rightArmPivot.rotation.x = -0.78 + Math.cos(elapsed * 2.2) * 0.18;

                    p.filterOrbs.forEach((fo) => {
                        const a = elapsed * 1.45 + fo.phase;
                        const rad = fo.isClean ? 0.26 : 0.32;
                        fo.mesh.position.set(
                            Math.cos(a) * rad,
                            0.63 + Math.sin(a * 1.6) * 0.18,
                            Math.sin(a) * rad
                        );
                    });
                } else if (node.key === 'dataset' && p.actor) {
                    p.platters.forEach((pl) => {
                        pl.holder.position.y = pl.baseY + Math.sin(elapsed * 1.8 + pl.idx * 0.8) * 0.025;
                        pl.holder.rotation.y = elapsed * (0.28 + pl.idx * 0.08);
                    });
                    p.sheetMesh.position.y = 0.58 + Math.sin(elapsed * 1.5) * 0.025;
                    p.actor.body.position.y = Math.sin(elapsed * 2.0) * 0.013;
                    p.actor.head.rotation.y = -0.1 + Math.sin(elapsed * 1.1) * 0.12;
                    p.actor.leftArmPivot.rotation.x = -0.72 + Math.sin(elapsed * 2.0) * 0.15;
                } else if (node.key === 'visualisasi' && p.actor) {
                    p.donutGroup.rotation.z = elapsed * 0.4;
                    p.pillBars.forEach((pb) => {
                        const s = pb.baseScale * (1 + Math.sin(elapsed * 2.2 + pb.phase) * 0.14);
                        pb.bar.scale.y = s;
                        pb.bar.position.y = 0.24 + s * 0.08;
                    });
                    p.actor.body.position.y = Math.sin(elapsed * 2.2) * 0.013;
                    p.actor.head.rotation.y = Math.sin(elapsed * 1.2) * 0.18;
                    p.actor.rightArmPivot.rotation.x = -0.82 + Math.sin(elapsed * 1.9) * 0.16;
                }
            });

            focusLightTarget.set(activeNodeForLight.group.position.x, 2.35, activeNodeForLight.group.position.z);
            focusLight.position.lerp(focusLightTarget, Math.min(1, dt * 5 || 1));
            focusLight.color.setHex(activeNodeForLight.accent);

            // Smooth spherical packets flowing through pipeline
            for (let i = 0; i < PACKET_COUNT; i++) {
                let u = packetOffsets[i] + elapsed * packetSpeeds[i];
                u = u - Math.floor(u);
                pipelineCurve.getPointAt(u, ptVec);

                const rawIntensity = u < 0.5 ? (1 - u * 2) * 0.85 + 0.12 : 0.06;
                const angle = elapsed * 2.6 + packetPhase[i];
                const offsetR = packetRadial[i] * rawIntensity;
                ptVec.y += Math.sin(angle) * offsetR;
                ptVec.z += Math.cos(angle) * offsetR;

                dummy.position.copy(ptVec);
                dummy.rotation.set(0, 0, 0);
                dummy.scale.setScalar(0.76 + Math.sin(elapsed * 3 + i) * 0.22);
                dummy.updateMatrix();
                packetMesh.setMatrixAt(i, dummy.matrix);

                if (u < 0.48) {
                    tempColor.copy(colorRaw).lerp(colorCyan, u * 1.6);
                } else {
                    tempColor.copy(colorCyan).lerp(colorClean, (u - 0.48) * 1.9);
                }
                packetMesh.setColorAt(i, tempColor);
            }
            packetMesh.instanceMatrix.needsUpdate = true;
            if (packetMesh.instanceColor) packetMesh.instanceColor.needsUpdate = true;

            for (let i = 0; i < DUST_COUNT; i++) {
                const yi = i * 3 + 1;
                dustPos[yi] += dustSpeeds[i] * dt;
                if (dustPos[yi] > 3.9) dustPos[yi] = -0.5;
            }
            dustGeo.attributes.position.needsUpdate = true;

            controls.update();
        },
        dispose() {
            controls.removeEventListener('start', onControlStart);
            controls.dispose();
            window.removeEventListener('wd:camera-focus', onCameraFocus);
            renderer.domElement.removeEventListener('pointermove', onPointerMove);
            renderer.domElement.removeEventListener('pointerleave', onPointerLeave);
            renderer.domElement.removeEventListener('pointerdown', onPointerDown);
            renderer.domElement.removeEventListener('pointerup', onPointerUp);
            renderer.domElement.removeEventListener('touchstart', onTouchStart);
            renderer.domElement.removeEventListener('touchmove', onTouchMove);
        },
    };
}

function buildHeaderStage(el, scene, camera, _renderer, disposables) {
    const mode = el.getAttribute('data-scene-mode') || 'formulir';
    const modePalettes = {
        formulir: { base: 0x0ea5e9, hi: 0x74d4ff, line: 0x38bdf8, speed: 1.0 },
        preprocessing: { base: 0x0ea5e9, hi: 0xfbbf24, line: 0x74d4ff, speed: 1.25 },
        dataset: { base: 0x0069a8, hi: 0x34d399, line: 0x74d4ff, speed: 0.95 },
        visualisasi: { base: 0x0284c7, hi: 0x7dd3fc, line: 0x38bdf8, speed: 1.15 },
        permohonan: { base: 0x0069a8, hi: 0x74d4ff, line: 0x93c5fd, speed: 0.9 },
        kategori: { base: 0x0ea5e9, hi: 0xbae6fd, line: 0x38bdf8, speed: 0.85 },
        riwayat: { base: 0x0284c7, hi: 0x74d4ff, line: 0x60a5fa, speed: 0.95 },
    };
    const pal = modePalettes[mode] || modePalettes.formulir;

    scene.fog = new THREE.FogExp2(0x0f1c44, 0.065);
    scene.add(new THREE.AmbientLight(0xbfe6ff, 0.95));
    const dirLight = new THREE.DirectionalLight(0xe0f2fe, 2.0);
    dirLight.position.set(4, 7, 5);
    scene.add(dirLight);

    camera.position.set(0.3, 1.35, 5.2);
    camera.lookAt(0.45, 0.15, 0);

    const root = new THREE.Group();
    scene.add(root);

    // 3 Smooth Curved Fiber Wave Ribbons (Zero Boxes!)
    const ribbonCount = 44;
    const strands = [-0.45, 0, 0.45].map((zOff, si) => {
        const arr = new Float32Array(ribbonCount * 3);
        const geo = new THREE.BufferGeometry();
        geo.setAttribute('position', new THREE.BufferAttribute(arr, 3));
        const mat = new THREE.LineBasicMaterial({
            color: si === 1 ? pal.hi : pal.line,
            transparent: true,
            opacity: si === 1 ? 0.52 : 0.25,
        });
        root.add(new THREE.Line(geo, mat));
        disposables.push(geo, mat);
        return { geo, arr, zOff, phase: si * 1.1 };
    });

    // 64 Smooth Spherical Bokeh Nodes in 1 InstancedMesh
    const NODE_COUNT = 64;
    const nodeGeo = new THREE.SphereGeometry(0.042, 10, 10);
    const nodeMat = new THREE.MeshBasicMaterial({ color: pal.hi, transparent: true, opacity: 0.65 });
    const nodeMesh = new THREE.InstancedMesh(nodeGeo, nodeMat, NODE_COUNT);
    nodeMesh.instanceMatrix.setUsage(THREE.DynamicDrawUsage);
    root.add(nodeMesh);
    disposables.push(nodeGeo, nodeMat);

    const nodeSeeds = Array.from({ length: NODE_COUNT }, (_, i) => ({
        x: (i / NODE_COUNT - 0.5) * 7.6 + 0.5,
        z: ((i * 19) % 10 - 5) * 0.25,
        phase: i * 0.55,
    }));

    // Contextual Phase Vignette + Mini Animated Actor on Right Side
    const sharedActorGeos = createSharedActorGeos(disposables);
    const diorama = new THREE.Group();
    diorama.position.set(1.25, -0.35, 0.25);
    root.add(diorama);

    const baseGeo = new THREE.CylinderGeometry(0.64, 0.72, 0.1, 40);
    const baseMat = new THREE.MeshStandardMaterial({ color: 0x11204d, metalness: 0.5, roughness: 0.3 });
    const ringGeo = new THREE.TorusGeometry(0.68, 0.016, 10, 48);
    const ringMat = new THREE.MeshBasicMaterial({ color: pal.hi, transparent: true, opacity: 0.75 });
    const baseMesh = new THREE.Mesh(baseGeo, baseMat);
    const ringMesh = new THREE.Mesh(ringGeo, ringMat);
    ringMesh.rotation.x = Math.PI / 2;
    ringMesh.position.y = 0.05;
    diorama.add(baseMesh, ringMesh);
    disposables.push(baseGeo, baseMat, ringGeo, ringMat);

    let miniActor = null;
    let modePart = {};

    if (mode === 'formulir') {
        const tabGeo = createRoundedRectGeometry(0.58, 0.72, 0.08);
        const tabMat = new THREE.MeshBasicMaterial({ map: createGFormTexture(disposables), side: THREE.DoubleSide });
        const tab = new THREE.Mesh(tabGeo, tabMat);
        tab.position.set(0.18, 0.56, -0.05);
        tab.rotation.set(-0.18, -0.35, 0);
        diorama.add(tab);
        disposables.push(tabGeo, tabMat);
        miniActor = createActor('wali', sharedActorGeos, disposables);
        miniActor.group.position.set(-0.26, 0.05, 0.16);
        miniActor.group.rotation.y = 0.65;
        diorama.add(miniActor.group);
        modePart = { tab };
    } else if (mode === 'preprocessing') {
        const g1 = new THREE.TorusGeometry(0.42, 0.02, 12, 48);
        const g2 = new THREE.TorusGeometry(0.28, 0.016, 10, 40);
        const gMat = new THREE.MeshStandardMaterial({ color: pal.hi, emissive: 0x78350f, emissiveIntensity: 0.5, metalness: 0.5, roughness: 0.2 });
        const r1 = new THREE.Mesh(g1, gMat);
        const r2 = new THREE.Mesh(g2, gMat);
        r1.position.set(0.15, 0.58, 0);
        r2.position.set(0.15, 0.58, 0);
        const core = new THREE.Mesh(new THREE.SphereGeometry(0.14, 18, 18), gMat);
        core.position.set(0.15, 0.58, 0);
        diorama.add(r1, r2, core);
        disposables.push(g1, g2, gMat, core.geometry);
        miniActor = createActor('wali', sharedActorGeos, disposables);
        miniActor.group.position.set(-0.3, 0.05, 0.22);
        miniActor.group.rotation.y = 0.68;
        diorama.add(miniActor.group);
        modePart = { r1, r2, core };
    } else if (mode === 'dataset') {
        const cylGeo = new THREE.CylinderGeometry(0.28, 0.28, 0.09, 36);
        const cylMat = new THREE.MeshStandardMaterial({ color: 0x047857, emissive: 0x065f46, emissiveIntensity: 0.55, metalness: 0.4, roughness: 0.24 });
        const platters = [0, 1, 2].map((i) => {
            const m = new THREE.Mesh(cylGeo, cylMat);
            m.position.set(-0.1, 0.2 + i * 0.16, -0.05);
            diorama.add(m);
            return m;
        });
        disposables.push(cylGeo, cylMat);
        miniActor = createActor('wali', sharedActorGeos, disposables);
        miniActor.group.position.set(0.28, 0.05, 0.2);
        miniActor.group.rotation.y = -0.8;
        diorama.add(miniActor.group);
        modePart = { platters };
    } else if (mode === 'visualisasi') {
        const donut = new THREE.Group();
        donut.position.set(0.15, 0.56, 0);
        [
            [0, Math.PI * 0.8, 0x00a6f4],
            [Math.PI * 0.9, Math.PI * 0.6, 0x10b981],
            [Math.PI * 1.6, Math.PI * 0.3, 0x74d4ff],
        ].forEach(([st, ln, c]) => {
            const sg = new THREE.TorusGeometry(0.22, 0.05, 12, 28, ln);
            const sm = new THREE.MeshStandardMaterial({ color: c, emissive: c, emissiveIntensity: 0.45 });
            const mesh = new THREE.Mesh(sg, sm);
            mesh.rotation.z = st;
            donut.add(mesh);
            disposables.push(sg, sm);
        });
        diorama.add(donut);
        miniActor = createActor('publik', sharedActorGeos, disposables);
        miniActor.group.position.set(-0.28, 0.05, 0.22);
        miniActor.group.rotation.y = 0.72;
        diorama.add(miniActor.group);
        modePart = { donut };
    } else {
        // permohonan / kategori / riwayat: orbital taxonomy/timeline halo + actor
        const portalRingGeo = new THREE.TorusGeometry(0.32, 0.022, 12, 48);
        const portalMat = new THREE.MeshStandardMaterial({ color: pal.hi, emissive: pal.base, emissiveIntensity: 0.6 });
        const portal = new THREE.Mesh(portalRingGeo, portalMat);
        portal.position.set(0.16, 0.56, 0);
        const orb = new THREE.Mesh(new THREE.SphereGeometry(0.12, 18, 18), portalMat);
        orb.position.set(0.16, 0.56, 0);
        diorama.add(portal, orb);
        disposables.push(portalRingGeo, portalMat, orb.geometry);
        miniActor = createActor('wali', sharedActorGeos, disposables);
        miniActor.group.position.set(-0.28, 0.05, 0.2);
        miniActor.group.rotation.y = 0.68;
        diorama.add(miniActor.group);
        modePart = { portal, orb };
    }

    const dummy = new THREE.Object3D();
    const pointer = { x: 0, y: 0 };

    const onPointer = (e) => {
        const rect = el.getBoundingClientRect();
        if (!rect.width || !rect.height) return;
        pointer.x = Math.max(-1, Math.min(1, ((e.clientX - rect.left) / rect.width) * 2 - 1));
        pointer.y = Math.max(-1, Math.min(1, ((e.clientY - rect.top) / rect.height) * 2 - 1));
    };
    window.addEventListener('pointermove', onPointer);

    let elapsed = 0;

    return {
        resize(aspect) {
            camera.position.z = Math.max(4.6, 5.4 / Math.max(aspect, 1.0));
        },
        update(dt) {
            elapsed += dt * pal.speed;

            strands.forEach((s) => {
                for (let i = 0; i < ribbonCount; i++) {
                    const rx = (i / (ribbonCount - 1) - 0.5) * 7.8 + 0.4;
                    const ry = Math.sin(rx * 0.8 + elapsed * 1.3 + s.phase) * 0.28;
                    const rz = s.zOff + Math.cos(rx * 0.6 - elapsed * 0.9) * 0.35;
                    s.arr[i * 3] = rx;
                    s.arr[i * 3 + 1] = ry;
                    s.arr[i * 3 + 2] = rz;
                }
                s.geo.attributes.position.needsUpdate = true;
            });

            for (let i = 0; i < NODE_COUNT; i++) {
                const ns = nodeSeeds[i];
                const ny = Math.sin(ns.x * 0.8 + elapsed * 1.3 + ns.phase) * 0.34;
                dummy.position.set(ns.x, ny, ns.z);
                dummy.scale.setScalar(0.75 + Math.sin(elapsed * 2 + ns.phase) * 0.25);
                dummy.updateMatrix();
                nodeMesh.setMatrixAt(i, dummy.matrix);
            }
            nodeMesh.instanceMatrix.needsUpdate = true;

            if (miniActor) {
                miniActor.body.position.y = Math.sin(elapsed * 2.1) * 0.012;
                miniActor.head.rotation.y = Math.sin(elapsed * 1.1) * 0.14;
                miniActor.rightArmPivot.rotation.x = -0.78 + Math.sin(elapsed * 2.6) * 0.16;
            }
            if (modePart.r1) {
                modePart.r1.rotation.x = elapsed * 0.6;
                modePart.r2.rotation.y = -elapsed * 0.8;
            }
            if (modePart.donut) {
                modePart.donut.rotation.z = elapsed * 0.45;
            }
            if (modePart.portal) {
                modePart.portal.rotation.y = elapsed * 0.55;
            }
            if (modePart.platters) {
                modePart.platters.forEach((pl, idx) => {
                    pl.position.y = 0.2 + idx * 0.16 + Math.sin(elapsed * 1.8 + idx) * 0.02;
                });
            }

            const ease = 1 - Math.exp(-dt * 3);
            root.rotation.y += (pointer.x * 0.1 - root.rotation.y) * ease;
            root.rotation.x += (pointer.y * 0.05 - root.rotation.x) * ease;
        },
        dispose() {
            window.removeEventListener('pointermove', onPointer);
        },
    };
}

export function mountWaliDataScene(el, variant = 'walidata-header') {
    let renderer;
    try {
        renderer = new THREE.WebGLRenderer({
            antialias: true,
            alpha: true,
            powerPreference: 'high-performance',
        });
    } catch {
        return () => {};
    }

    THREE.ColorManagement.enabled = true;
    renderer.outputColorSpace = THREE.SRGBColorSpace;
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.15;
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
    renderer.setClearColor(0x000000, 0);

    const ariaLabel =
        variant === 'walidata-pipeline'
            ? 'Simulasi 3D interaktif lima fase Satu Data beserta aktor: Instrumen Formulir, Pengisian Respons, Pre-processing, Dataset, dan Visualisasi Dashboard.'
            : 'Diorama 3D kontekstual modul Wali Data Satu Data Sistem Informasi UNUSA.';
    renderer.domElement.setAttribute('role', 'img');
    renderer.domElement.setAttribute('aria-label', ariaLabel);
    renderer.domElement.className =
        'block h-full w-full opacity-0 motion-safe:transition-opacity motion-safe:duration-700 motion-safe:ease-out';
    el.appendChild(renderer.domElement);

    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(44, 1, 0.1, 80);
    const disposables = [];

    const controller =
        variant === 'walidata-pipeline'
            ? buildPipelineObservatory(el, scene, camera, renderer, disposables)
            : buildHeaderStage(el, scene, camera, renderer, disposables);

    const mq = window.matchMedia('(prefers-reduced-motion: reduce)');
    let reducedMotion = mq.matches;
    let userPaused = window.__sdMotionPaused === true;
    let visible = true;
    let running = false;

    const clock = new THREE.Clock();
    let revealed = false;

    const renderFrame = () => {
        renderer.render(scene, camera);
        if (!revealed) {
            revealed = true;
            requestAnimationFrame(() => renderer.domElement.classList.remove('opacity-0'));
        }
    };

    const animate = () => {
        const dt = Math.min(clock.getDelta(), 0.05);
        controller.update(dt, reducedMotion);
        renderFrame();
    };

    const syncLoopState = () => {
        const shouldRun = !reducedMotion && !userPaused && visible && !document.hidden;
        if (shouldRun && !running) {
            clock.getDelta();
            running = true;
            renderer.setAnimationLoop(animate);
        } else if (!shouldRun && running) {
            running = false;
            renderer.setAnimationLoop(null);
            renderFrame();
        }
    };

    const onReducedChange = (e) => {
        reducedMotion = e.matches;
        syncLoopState();
    };
    mq.addEventListener('change', onReducedChange);

    const onMotionToggle = (e) => {
        userPaused = Boolean(e?.detail?.paused ?? window.__sdMotionPaused);
        syncLoopState();
    };
    window.addEventListener('sd:motion-toggle', onMotionToggle);

    const onVisibility = () => syncLoopState();
    document.addEventListener('visibilitychange', onVisibility);

    const onInteractiveCommand = () => {
        if (!running) {
            controller.update(0.25, true);
            renderFrame();
        }
    };
    window.addEventListener('wd:camera-focus', onInteractiveCommand);

    const resize = () => {
        const w = Math.max(1, el.clientWidth);
        const h = Math.max(1, el.clientHeight);
        renderer.setSize(w, h, false);
        camera.aspect = w / h;
        controller.resize(camera.aspect);
        camera.updateProjectionMatrix();
        if (!running) renderFrame();
    };

    const sizeObserver = new ResizeObserver(resize);
    sizeObserver.observe(el);

    const viewObserver = new IntersectionObserver(([entry]) => {
        visible = entry.isIntersecting;
        syncLoopState();
    });
    viewObserver.observe(el);

    controller.update(0.6, reducedMotion);
    resize();
    syncLoopState();

    const cleanup = () => {
        running = false;
        renderer.setAnimationLoop(null);
        sizeObserver.disconnect();
        viewObserver.disconnect();
        mq.removeEventListener('change', onReducedChange);
        window.removeEventListener('sd:motion-toggle', onMotionToggle);
        window.removeEventListener('wd:camera-focus', onInteractiveCommand);
        document.removeEventListener('visibilitychange', onVisibility);
        controller.dispose?.();
        disposables.forEach((d) => d.dispose?.());
        renderer.dispose();
        renderer.forceContextLoss?.();
        renderer.domElement.remove();
    };

    window.addEventListener('pagehide', cleanup, { once: true });

    return cleanup;
}
