import * as THREE from 'three';

function createDotTexture(lightMode = false) {
    const canvas = document.createElement('canvas');
    canvas.width = 64;
    canvas.height = 64;
    const ctx = canvas.getContext('2d');
    if (ctx) {
        const grad = ctx.createRadialGradient(32, 32, 0, 32, 32, 32);
        if (lightMode) {
            grad.addColorStop(0, 'rgba(68, 114, 208, 0.95)');
            grad.addColorStop(0.45, 'rgba(14, 165, 233, 0.45)');
            grad.addColorStop(1, 'rgba(14, 165, 233, 0)');
        } else {
            grad.addColorStop(0, 'rgba(255, 255, 255, 1)');
            grad.addColorStop(0.35, 'rgba(186, 230, 253, 0.85)');
            grad.addColorStop(1, 'rgba(186, 230, 253, 0)');
        }
        ctx.fillStyle = grad;
        ctx.fillRect(0, 0, 64, 64);
    }
    return new THREE.CanvasTexture(canvas);
}

function buildOrbitScene(scene, camera, disposables) {
    const fog = new THREE.Fog(0x182654, 9, 20);
    scene.fog = fog;
    scene.add(new THREE.AmbientLight(0xbfe6ff, 1.25));

    const keyLight = new THREE.PointLight(0x38bdf8, 40, 24);
    keyLight.position.set(3, 3, 5);
    const rimLight = new THREE.PointLight(0x6366f1, 25, 20);
    rimLight.position.set(-4, -2, 2);
    scene.add(keyLight, rimLight);

    const root = new THREE.Group();
    scene.add(root);

    const coreGeo = new THREE.IcosahedronGeometry(1.25, 1);
    const coreMat = new THREE.MeshStandardMaterial({
        color: 0x0ea5e9,
        emissive: 0x075985,
        emissiveIntensity: 0.7,
        roughness: 0.3,
        metalness: 0.25,
        flatShading: true,
    });
    const core = new THREE.Mesh(coreGeo, coreMat);

    const shellGeo = new THREE.IcosahedronGeometry(1.85, 1);
    const shellMat = new THREE.MeshBasicMaterial({
        color: 0x7dd3fc,
        wireframe: true,
        transparent: true,
        opacity: 0.24,
    });
    const shell = new THREE.Mesh(shellGeo, shellMat);
    root.add(core, shell);

    const orbit = new THREE.Group();
    orbit.rotation.x = 0.34;
    root.add(orbit);

    const radius = 3.1;
    const ringGeo = new THREE.TorusGeometry(radius, 0.012, 8, 180);
    const ringMat = new THREE.MeshBasicMaterial({
        color: 0x93c5fd,
        transparent: true,
        opacity: 0.3,
    });
    const ring = new THREE.Mesh(ringGeo, ringMat);
    ring.rotation.x = Math.PI / 2;
    orbit.add(ring);

    const cardGeo = new THREE.BoxGeometry(0.72, 0.95, 0.04);
    const cardMat = new THREE.MeshStandardMaterial({
        color: 0xffffff,
        roughness: 0.45,
        transparent: true,
        opacity: 0.88,
    });
    const edgeGeo = new THREE.EdgesGeometry(cardGeo);
    const edgeMat = new THREE.LineBasicMaterial({ color: 0x38bdf8 });
    const stripeGeo = new THREE.PlaneGeometry(0.44, 0.055);
    const stripeMat = new THREE.MeshBasicMaterial({ color: 0x0284c7 });
    const headMat = new THREE.MeshBasicMaterial({ color: 0x1e3a8a });

    const cards = Array.from({ length: 6 }, (_, i) => {
        const card = new THREE.Group();
        card.add(new THREE.Mesh(cardGeo, cardMat), new THREE.LineSegments(edgeGeo, edgeMat));
        for (let k = 0; k < 4; k++) {
            const stripe = new THREE.Mesh(stripeGeo, k === 0 ? headMat : stripeMat);
            const short = k === 3;
            stripe.scale.x = short ? 0.55 : 1;
            stripe.position.set(short ? -0.1 : 0, 0.28 - k * 0.15, 0.025);
            card.add(stripe);
        }
        orbit.add(card);
        return {
            card,
            angle: (i / 6) * Math.PI * 2,
            lift: i % 2 ? 0.28 : -0.28,
        };
    });

    const linkPos = new Float32Array(cards.length * 6);
    const linkGeo = new THREE.BufferGeometry();
    linkGeo.setAttribute('position', new THREE.BufferAttribute(linkPos, 3));
    const linkMat = new THREE.LineBasicMaterial({
        color: 0x38bdf8,
        transparent: true,
        opacity: 0.24,
    });
    orbit.add(new THREE.LineSegments(linkGeo, linkMat));

    const count = 160;
    const spanY = 4.5;
    const dots = new Float32Array(count * 3);
    const speeds = new Float32Array(count);
    for (let i = 0; i < count; i++) {
        dots[i * 3] = (Math.random() - 0.5) * 12;
        dots[i * 3 + 1] = (Math.random() - 0.5) * spanY * 2;
        dots[i * 3 + 2] = (Math.random() - 0.5) * 4 - 1;
        speeds[i] = 0.1 + Math.random() * 0.25;
    }
    const dotGeo = new THREE.BufferGeometry();
    dotGeo.setAttribute('position', new THREE.BufferAttribute(dots, 3));
    const dotTex = createDotTexture(false);
    const dotMat = new THREE.PointsMaterial({
        size: 0.08,
        map: dotTex,
        transparent: true,
        depthWrite: false,
        opacity: 0.72,
        blending: THREE.AdditiveBlending,
    });
    scene.add(new THREE.Points(dotGeo, dotMat));

    disposables.push(
        coreGeo, coreMat, shellGeo, shellMat, ringGeo, ringMat,
        cardGeo, cardMat, edgeGeo, edgeMat, stripeGeo, stripeMat,
        headMat, linkGeo, linkMat, dotGeo, dotMat, dotTex
    );

    let elapsed = 0;
    let intro = 0;

    return {
        setInitialReduced() {
            intro = 1;
        },
        resize(aspect) {
            const tan = Math.tan((camera.fov * Math.PI) / 360);
            const dist = Math.max(
                2.6 + 2.1 / (0.72 * tan),
                1.2 + 3.5 / (0.72 * tan * aspect)
            );
            camera.position.z = dist;
            fog.near = dist - 2;
            fog.far = dist + 8;
        },
        update(dt, pointer) {
            elapsed += dt;
            intro = Math.min(1, intro + dt / 1.6);
            root.scale.setScalar(0.82 + 0.18 * (1 - (1 - intro) ** 3));
            core.rotation.x += dt * 0.18;
            core.rotation.y += dt * 0.3;
            shell.rotation.y -= dt * 0.12;
            shell.rotation.z += dt * 0.06;
            const pulse = 1 + Math.sin(elapsed * 1.3) * 0.035;
            core.scale.setScalar(pulse);

            cards.forEach((c, i) => {
                const a = c.angle + elapsed * 0.22;
                const x = Math.cos(a) * radius;
                const z = Math.sin(a) * radius;
                const y = c.lift + Math.sin(elapsed * 0.9 + i) * 0.14;
                c.card.position.set(x, y, z);
                c.card.rotation.set(-orbit.rotation.x, Math.sin(elapsed * 0.7 + i) * 0.2, 0);
                linkPos.set([0, 0, 0, x, y, z], i * 6);
            });
            linkGeo.attributes.position.needsUpdate = true;

            for (let i = 0; i < count; i++) {
                const yi = i * 3 + 1;
                dots[yi] += speeds[i] * dt;
                if (dots[yi] > spanY) dots[yi] = -spanY;
            }
            dotGeo.attributes.position.needsUpdate = true;

            const ease = 1 - Math.exp(-dt * 2.5);
            root.rotation.y += (pointer.x * 0.25 - root.rotation.y) * ease;
            root.rotation.x += (pointer.y * 0.12 - root.rotation.x) * ease;
        },
    };
}

function buildNetworkScene(scene, camera, disposables) {
    const root = new THREE.Group();
    scene.add(root);

    const nodeCount = 42;
    const nodes = Array.from({ length: nodeCount }, () => ({
        x: (Math.random() - 0.5) * 9.5,
        y: (Math.random() - 0.5) * 5.2,
        z: (Math.random() - 0.5) * 3.8,
        vx: (Math.random() - 0.5) * 0.28,
        vy: (Math.random() - 0.5) * 0.22,
        vz: (Math.random() - 0.5) * 0.18,
    }));

    const ptPos = new Float32Array(nodeCount * 3);
    const ptGeo = new THREE.BufferGeometry();
    ptGeo.setAttribute('position', new THREE.BufferAttribute(ptPos, 3));
    const dotTex = createDotTexture(false);
    const ptMat = new THREE.PointsMaterial({
        size: 0.18,
        map: dotTex,
        color: 0xbae6fd,
        transparent: true,
        depthWrite: false,
        opacity: 0.85,
        blending: THREE.AdditiveBlending,
    });
    root.add(new THREE.Points(ptGeo, ptMat));

    const maxSegs = (nodeCount * (nodeCount - 1)) / 2;
    const linePos = new Float32Array(maxSegs * 6);
    const lineGeo = new THREE.BufferGeometry();
    lineGeo.setAttribute('position', new THREE.BufferAttribute(linePos, 3));
    const lineMat = new THREE.LineBasicMaterial({
        color: 0x7dd3fc,
        transparent: true,
        opacity: 0.24,
    });
    const lineSegments = new THREE.LineSegments(lineGeo, lineMat);
    root.add(lineSegments);

    const octGeo = new THREE.OctahedronGeometry(0.32, 0);
    const octMat = new THREE.MeshBasicMaterial({
        color: 0x7dd3fc,
        wireframe: true,
        transparent: true,
        opacity: 0.32,
    });
    const hubs = Array.from({ length: 6 }, (_, i) => {
        const mesh = new THREE.Mesh(octGeo, octMat);
        mesh.position.set(
            (Math.random() - 0.5) * 6.5,
            (Math.random() - 0.5) * 3.6,
            (Math.random() - 0.5) * 2
        );
        mesh.scale.setScalar(0.75 + (i % 3) * 0.25);
        root.add(mesh);
        return mesh;
    });

    disposables.push(ptGeo, ptMat, dotTex, lineGeo, lineMat, octGeo, octMat);

    const syncGeometry = () => {
        let segIdx = 0;
        const thresholdSq = 2.6 * 2.6;

        for (let i = 0; i < nodeCount; i++) {
            const a = nodes[i];
            ptPos[i * 3] = a.x;
            ptPos[i * 3 + 1] = a.y;
            ptPos[i * 3 + 2] = a.z;

            for (let j = i + 1; j < nodeCount; j++) {
                const b = nodes[j];
                const dx = a.x - b.x;
                const dy = a.y - b.y;
                const dz = a.z - b.z;
                if (dx * dx + dy * dy + dz * dz <= thresholdSq) {
                    const offset = segIdx * 6;
                    linePos[offset] = a.x;
                    linePos[offset + 1] = a.y;
                    linePos[offset + 2] = a.z;
                    linePos[offset + 3] = b.x;
                    linePos[offset + 4] = b.y;
                    linePos[offset + 5] = b.z;
                    segIdx++;
                }
            }
        }

        ptGeo.attributes.position.needsUpdate = true;
        lineGeo.setDrawRange(0, segIdx * 2);
        lineGeo.attributes.position.needsUpdate = true;
    };

    syncGeometry();

    return {
        setInitialReduced() {},
        resize(aspect) {
            camera.position.z = Math.max(7.2, 6.2 / Math.max(aspect, 0.7));
        },
        update(dt, pointer) {
            for (const n of nodes) {
                n.x += n.vx * dt;
                n.y += n.vy * dt;
                n.z += n.vz * dt;
                if (n.x > 4.75 || n.x < -4.75) n.vx *= -1;
                if (n.y > 2.6 || n.y < -2.6) n.vy *= -1;
                if (n.z > 1.9 || n.z < -1.9) n.vz *= -1;
            }
            syncGeometry();

            hubs.forEach((h, i) => {
                h.rotation.x += dt * (0.25 + i * 0.04);
                h.rotation.y += dt * (0.35 - i * 0.03);
            });

            const ease = 1 - Math.exp(-dt * 2.5);
            root.rotation.y += (pointer.x * 0.2 - root.rotation.y) * ease;
            root.rotation.x += (pointer.y * 0.1 - root.rotation.x) * ease;
        },
    };
}

function buildWavesScene(scene, camera, disposables) {
    const root = new THREE.Group();
    root.rotation.x = -0.38;
    root.position.y = -0.35;
    scene.add(root);

    const cols = 26;
    const rows = 12;
    const totalPts = cols * rows;
    const pos = new Float32Array(totalPts * 3);
    const geo = new THREE.BufferGeometry();
    geo.setAttribute('position', new THREE.BufferAttribute(pos, 3));
    const dotTex = createDotTexture(false);
    const mat = new THREE.PointsMaterial({
        size: 0.14,
        map: dotTex,
        color: 0x93c5fd,
        transparent: true,
        depthWrite: false,
        opacity: 0.78,
        blending: THREE.AdditiveBlending,
    });
    root.add(new THREE.Points(geo, mat));

    const rowLineMat = new THREE.LineBasicMaterial({
        color: 0x38bdf8,
        transparent: true,
        opacity: 0.18,
    });
    const rowLines = [];
    for (let r = 0; r < rows; r++) {
        const lGeo = new THREE.BufferGeometry();
        const lArr = new Float32Array(cols * 3);
        lGeo.setAttribute('position', new THREE.BufferAttribute(lArr, 3));
        const line = new THREE.Line(lGeo, rowLineMat);
        root.add(line);
        rowLines.push({ lGeo, lArr });
        disposables.push(lGeo);
    }

    disposables.push(geo, mat, dotTex, rowLineMat);

    let elapsed = 0;
    const updateWave = (t) => {
        const spanX = 11.5;
        const spanZ = 5.4;
        for (let r = 0; r < rows; r++) {
            const z = (r / (rows - 1) - 0.5) * spanZ;
            const lArr = rowLines[r].lArr;
            for (let c = 0; c < cols; c++) {
                const x = (c / (cols - 1) - 0.5) * spanX;
                const y = Math.sin(x * 0.72 + t * 1.15) * 0.34 + Math.cos(z * 1.05 - t * 0.9) * 0.24;
                const ptIdx = (r * cols + c) * 3;
                pos[ptIdx] = x;
                pos[ptIdx + 1] = y;
                pos[ptIdx + 2] = z;

                const lIdx = c * 3;
                lArr[lIdx] = x;
                lArr[lIdx + 1] = y;
                lArr[lIdx + 2] = z;
            }
            rowLines[r].lGeo.attributes.position.needsUpdate = true;
        }
        geo.attributes.position.needsUpdate = true;
    };

    updateWave(0.8);

    return {
        setInitialReduced() {},
        resize(aspect) {
            camera.position.z = Math.max(7.2, 6.4 / Math.max(aspect, 0.75));
        },
        update(dt, pointer) {
            elapsed += dt;
            updateWave(elapsed);
            const ease = 1 - Math.exp(-dt * 2.5);
            root.rotation.y += (pointer.x * 0.16 - root.rotation.y) * ease;
            root.rotation.x += (-0.38 + pointer.y * 0.08 - root.rotation.x) * ease;
        },
    };
}

function buildAmbientScene(scene, camera, disposables) {
    const root = new THREE.Group();
    scene.add(root);

    const icoGeo = new THREE.IcosahedronGeometry(0.95, 0);
    const octGeo = new THREE.OctahedronGeometry(0.68, 0);
    const dodGeo = new THREE.IcosahedronGeometry(0.54, 1);

    const matPrimary = new THREE.MeshBasicMaterial({
        color: 0x4472d0,
        wireframe: true,
        transparent: true,
        opacity: 0.24,
    });
    const matAccent = new THREE.MeshBasicMaterial({
        color: 0x0ea5e9,
        wireframe: true,
        transparent: true,
        opacity: 0.2,
    });
    const matMuted = new THREE.MeshBasicMaterial({
        color: 0x7398dd,
        wireframe: true,
        transparent: true,
        opacity: 0.18,
    });

    const m1 = new THREE.Mesh(icoGeo, matPrimary);
    m1.position.set(1.3, 0.1, 0);

    const m2 = new THREE.Mesh(octGeo, matAccent);
    m2.position.set(-1.35, -0.35, -0.8);

    const m3 = new THREE.Mesh(dodGeo, matMuted);
    m3.position.set(0.15, 0.9, -1.2);

    root.add(m1, m2, m3);

    const count = 40;
    const pts = new Float32Array(count * 3);
    const speeds = new Float32Array(count);
    for (let i = 0; i < count; i++) {
        pts[i * 3] = (Math.random() - 0.5) * 6.5;
        pts[i * 3 + 1] = (Math.random() - 0.5) * 3.4;
        pts[i * 3 + 2] = (Math.random() - 0.5) * 2.5 - 0.4;
        speeds[i] = 0.08 + Math.random() * 0.16;
    }
    const dotGeo = new THREE.BufferGeometry();
    dotGeo.setAttribute('position', new THREE.BufferAttribute(pts, 3));
    const dotTex = createDotTexture(true);
    const dotMat = new THREE.PointsMaterial({
        size: 0.14,
        map: dotTex,
        transparent: true,
        depthWrite: false,
        opacity: 0.45,
    });
    root.add(new THREE.Points(dotGeo, dotMat));

    disposables.push(
        icoGeo, octGeo, dodGeo, matPrimary, matAccent, matMuted,
        dotGeo, dotMat, dotTex
    );

    let elapsed = 0;

    return {
        setInitialReduced() {},
        resize(aspect) {
            camera.position.z = Math.max(5.5, 4.8 / Math.max(aspect, 0.8));
        },
        update(dt, pointer) {
            elapsed += dt;
            m1.rotation.x += dt * 0.18;
            m1.rotation.y += dt * 0.24;
            m2.rotation.y -= dt * 0.22;
            m2.rotation.z += dt * 0.14;
            m3.rotation.x -= dt * 0.15;
            m3.rotation.z -= dt * 0.2;

            m1.position.y = 0.1 + Math.sin(elapsed * 0.8) * 0.12;
            m2.position.y = -0.35 + Math.cos(elapsed * 0.7) * 0.1;

            for (let i = 0; i < count; i++) {
                const yi = i * 3 + 1;
                pts[yi] += speeds[i] * dt;
                if (pts[yi] > 1.7) pts[yi] = -1.7;
            }
            dotGeo.attributes.position.needsUpdate = true;

            const ease = 1 - Math.exp(-dt * 2.5);
            root.rotation.y += (pointer.x * 0.18 - root.rotation.y) * ease;
            root.rotation.x += (pointer.y * 0.09 - root.rotation.x) * ease;
        },
    };
}

export function mountThreeScene(el, variant = 'orbit') {
    let renderer;
    try {
        renderer = new THREE.WebGLRenderer({
            antialias: true,
            alpha: true,
            powerPreference: 'low-power',
        });
    } catch {
        return () => {};
    }

    const reduced = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 1.75));
    renderer.setClearColor(0x000000, 0);
    renderer.domElement.className =
        'block h-full w-full opacity-0 motion-safe:transition-opacity motion-safe:duration-1000 motion-safe:ease-out';
    el.appendChild(renderer.domElement);

    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(42, 1, 0.1, 50);
    camera.position.set(0, 0, 10);

    const disposables = [];
    const preset =
        variant === 'network'
            ? buildNetworkScene(scene, camera, disposables)
            : variant === 'waves'
              ? buildWavesScene(scene, camera, disposables)
              : variant === 'ambient'
                ? buildAmbientScene(scene, camera, disposables)
                : buildOrbitScene(scene, camera, disposables);

    if (reduced) {
        preset.setInitialReduced();
    }

    const pointer = { x: 0, y: 0 };
    const onPointer = (e) => {
        const r = el.getBoundingClientRect();
        if (!r.width || !r.height) return;
        pointer.x = Math.max(-1, Math.min(1, ((e.clientX - r.left) / r.width) * 2 - 1));
        pointer.y = Math.max(-1, Math.min(1, ((e.clientY - r.top) / r.height) * 2 - 1));
    };

    let revealed = false;
    const render = () => {
        renderer.render(scene, camera);
        if (!revealed) {
            revealed = true;
            requestAnimationFrame(() => renderer.domElement.classList.remove('opacity-0'));
        }
    };

    let frame = 0;
    let visible = true;
    let userPaused = window.__sdMotionPaused === true;
    let lastTime = 0;

    const tick = (now) => {
        const dt = lastTime > 0 ? Math.min((now - lastTime) / 1000, 0.05) : 0.016;
        lastTime = now;
        preset.update(dt, pointer);
        render();
        frame = requestAnimationFrame(tick);
    };

    const start = () => {
        if (reduced || userPaused || frame || !visible || document.hidden) return;
        lastTime = performance.now();
        frame = requestAnimationFrame(tick);
    };

    const stop = () => {
        cancelAnimationFrame(frame);
        frame = 0;
    };

    const onMotionToggle = (e) => {
        userPaused = Boolean(e?.detail?.paused ?? window.__sdMotionPaused);
        if (userPaused) {
            stop();
            render();
        } else {
            start();
        }
    };
    window.addEventListener('sd:motion-toggle', onMotionToggle);

    const resize = () => {
        const w = Math.max(1, el.clientWidth);
        const h = Math.max(1, el.clientHeight);
        renderer.setSize(w, h, false);
        camera.aspect = w / h;
        preset.resize(camera.aspect);
        camera.updateProjectionMatrix();
        if (!frame) render();
    };

    const sizeObserver = new ResizeObserver(resize);
    sizeObserver.observe(el);

    const viewObserver = new IntersectionObserver(([entry]) => {
        visible = entry.isIntersecting;
        if (visible) start();
        else stop();
    });
    viewObserver.observe(el);

    const onVisibility = () => (document.hidden ? stop() : start());
    document.addEventListener('visibilitychange', onVisibility);
    if (!reduced) {
        window.addEventListener('pointermove', onPointer);
    }

    preset.update(reduced ? 0.6 : 0, pointer);
    resize();
    start();

    const cleanup = () => {
        stop();
        sizeObserver.disconnect();
        viewObserver.disconnect();
        document.removeEventListener('visibilitychange', onVisibility);
        window.removeEventListener('sd:motion-toggle', onMotionToggle);
        window.removeEventListener('pointermove', onPointer);
        disposables.forEach((d) => d.dispose?.());
        renderer.dispose();
        renderer.forceContextLoss?.();
        renderer.domElement.remove();
    };

    window.addEventListener('pagehide', cleanup, { once: true });

    return cleanup;
}
