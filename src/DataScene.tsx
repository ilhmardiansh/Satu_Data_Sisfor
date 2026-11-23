import { useEffect, useRef } from "react"

type Three = typeof import("three")

type DataSceneProps = {
  className?: string
}

// Tepi adegan memudar lembut sehingga tidak tampak terpotong oleh kotak kanvas.
const edgeMask = "radial-gradient(closest-side, #000 66%, transparent 98%)"

// Animasi Three.js: inti data, kartu formulir yang mengorbit, dan partikel data.
export default function DataScene({ className = "" }: DataSceneProps) {
  const host = useRef<HTMLDivElement>(null)

  useEffect(() => {
    const el = host.current
    if (!el) return
    let disposed = false
    let cleanup = () => {}
    // Three.js dimuat terpisah dan baru saat area adegan benar-benar terlihat,
    // sehingga tidak ada konteks WebGL ketika adegan disembunyikan (mis. layar kecil).
    const gate = new IntersectionObserver((entries) => {
      if (
        !entries.some((e) => e.isIntersecting && e.boundingClientRect.width > 0)
      )
        return
      gate.disconnect()
      import("three")
        .then((THREE) => {
          if (!disposed) cleanup = mountScene(THREE, el)
        })
        .catch(() => {})
    })
    gate.observe(el)
    return () => {
      disposed = true
      gate.disconnect()
      cleanup()
    }
  }, [])

  return (
    <div
      ref={host}
      aria-hidden="true"
      className={className}
      style={{ maskImage: edgeMask, WebkitMaskImage: edgeMask }}
    />
  )
}

function dotTexture(THREE: Three) {
  const canvas = document.createElement("canvas")
  canvas.width = canvas.height = 64
  const ctx = canvas.getContext("2d")
  if (ctx) {
    const g = ctx.createRadialGradient(32, 32, 0, 32, 32, 32)
    g.addColorStop(0, "rgba(255,255,255,1)")
    g.addColorStop(0.35, "rgba(186,230,253,0.85)")
    g.addColorStop(1, "rgba(186,230,253,0)")
    ctx.fillStyle = g
    ctx.fillRect(0, 0, 64, 64)
  }
  return new THREE.CanvasTexture(canvas)
}

function mountScene(THREE: Three, el: HTMLDivElement) {
  let renderer: import("three").WebGLRenderer
  try {
    renderer = new THREE.WebGLRenderer({
      antialias: true,
      alpha: true,
      powerPreference: "low-power",
    })
  } catch {
    // WebGL tidak tersedia: latar gradien CSS tetap tampil.
    return () => {}
  }
  const reduced = window.matchMedia("(prefers-reduced-motion: reduce)").matches
  renderer.setPixelRatio(Math.min(window.devicePixelRatio, 1.75))
  renderer.setClearColor(0x000000, 0)
  // Kanvas muncul perlahan setelah frame pertama siap.
  renderer.domElement.className =
    "block h-full w-full opacity-0 motion-safe:transition-opacity motion-safe:duration-1000 motion-safe:ease-out"
  el.appendChild(renderer.domElement)

  const scene = new THREE.Scene()
  const fog = new THREE.Fog(0x182654, 9, 20)
  scene.fog = fog
  const camera = new THREE.PerspectiveCamera(42, 1, 0.1, 50)
  camera.position.set(0, 0, 11)

  scene.add(new THREE.AmbientLight(0xbfe6ff, 1.2))
  const keyLight = new THREE.PointLight(0x38bdf8, 40, 24)
  keyLight.position.set(3, 3, 5)
  const rimLight = new THREE.PointLight(0x6366f1, 25, 20)
  rimLight.position.set(-4, -2, 2)
  scene.add(keyLight, rimLight)

  const root = new THREE.Group()
  scene.add(root)

  // Inti data
  const coreGeo = new THREE.IcosahedronGeometry(1.25, 1)
  const coreMat = new THREE.MeshStandardMaterial({
    color: 0x0ea5e9,
    emissive: 0x075985,
    emissiveIntensity: 0.7,
    roughness: 0.3,
    metalness: 0.25,
    flatShading: true,
  })
  const core = new THREE.Mesh(coreGeo, coreMat)
  const shellGeo = new THREE.IcosahedronGeometry(1.85, 1)
  const shellMat = new THREE.MeshBasicMaterial({
    color: 0x7dd3fc,
    wireframe: true,
    transparent: true,
    opacity: 0.24,
  })
  const shell = new THREE.Mesh(shellGeo, shellMat)
  root.add(core, shell)

  // Orbit kartu formulir
  const orbit = new THREE.Group()
  orbit.rotation.x = 0.34
  root.add(orbit)
  const radius = 3.1
  const ringGeo = new THREE.TorusGeometry(radius, 0.012, 8, 180)
  const ringMat = new THREE.MeshBasicMaterial({
    color: 0x93c5fd,
    transparent: true,
    opacity: 0.3,
  })
  const ring = new THREE.Mesh(ringGeo, ringMat)
  ring.rotation.x = Math.PI / 2
  orbit.add(ring)

  const cardGeo = new THREE.BoxGeometry(0.72, 0.95, 0.04)
  const cardMat = new THREE.MeshStandardMaterial({
    color: 0xffffff,
    roughness: 0.45,
    transparent: true,
    opacity: 0.86,
  })
  const edgeGeo = new THREE.EdgesGeometry(cardGeo)
  const edgeMat = new THREE.LineBasicMaterial({ color: 0x38bdf8 })
  const stripeGeo = new THREE.PlaneGeometry(0.44, 0.055)
  const stripeMat = new THREE.MeshBasicMaterial({ color: 0x0284c7 })
  const headMat = new THREE.MeshBasicMaterial({ color: 0x1e3a8a })

  const cards = Array.from({ length: 6 }, (_, i) => {
    const card = new THREE.Group()
    card.add(
      new THREE.Mesh(cardGeo, cardMat),
      new THREE.LineSegments(edgeGeo, edgeMat),
    )
    for (let k = 0; k < 4; k++) {
      const stripe = new THREE.Mesh(stripeGeo, k === 0 ? headMat : stripeMat)
      const short = k === 3
      stripe.scale.x = short ? 0.55 : 1
      stripe.position.set(short ? -0.1 : 0, 0.28 - k * 0.15, 0.025)
      card.add(stripe)
    }
    orbit.add(card)
    return {
      card,
      angle: (i / 6) * Math.PI * 2,
      lift: i % 2 ? 0.28 : -0.28,
    }
  })

  // Garis aliran data dari inti ke setiap kartu
  const linkPos = new Float32Array(cards.length * 6)
  const linkGeo = new THREE.BufferGeometry()
  linkGeo.setAttribute("position", new THREE.BufferAttribute(linkPos, 3))
  const linkMat = new THREE.LineBasicMaterial({
    color: 0x38bdf8,
    transparent: true,
    opacity: 0.24,
  })
  orbit.add(new THREE.LineSegments(linkGeo, linkMat))

  // Partikel data (dibatasi di sekitar adegan agar ringan dan tidak meluber)
  const count = 160
  const spanY = 4.5
  const dots = new Float32Array(count * 3)
  const speed = new Float32Array(count)
  for (let i = 0; i < count; i++) {
    dots[i * 3] = (Math.random() - 0.5) * 12
    dots[i * 3 + 1] = (Math.random() - 0.5) * spanY * 2
    dots[i * 3 + 2] = (Math.random() - 0.5) * 4 - 1
    speed[i] = 0.1 + Math.random() * 0.25
  }
  const dotGeo = new THREE.BufferGeometry()
  dotGeo.setAttribute("position", new THREE.BufferAttribute(dots, 3))
  const dotTex = dotTexture(THREE)
  const dotMat = new THREE.PointsMaterial({
    size: 0.08,
    map: dotTex,
    transparent: true,
    depthWrite: false,
    opacity: 0.7,
    blending: THREE.AdditiveBlending,
  })
  scene.add(new THREE.Points(dotGeo, dotMat))

  const pointer = { x: 0, y: 0 }
  const onPointer = (e: PointerEvent) => {
    const r = el.getBoundingClientRect()
    pointer.x = Math.max(
      -1,
      Math.min(1, ((e.clientX - r.left) / r.width) * 2 - 1),
    )
    pointer.y = Math.max(
      -1,
      Math.min(1, ((e.clientY - r.top) / r.height) * 2 - 1),
    )
  }

  const timer = new THREE.Timer()
  let elapsed = 0
  // Intro: adegan membesar halus dari 82% ke 100% saat pertama tampil.
  let intro = reduced ? 1 : 0
  const update = (dt: number) => {
    elapsed += dt
    intro = Math.min(1, intro + dt / 1.6)
    root.scale.setScalar(0.82 + 0.18 * (1 - (1 - intro) ** 3))
    core.rotation.x += dt * 0.18
    core.rotation.y += dt * 0.3
    shell.rotation.y -= dt * 0.12
    shell.rotation.z += dt * 0.06
    const pulse = 1 + Math.sin(elapsed * 1.3) * 0.035
    core.scale.setScalar(pulse)

    cards.forEach((c, i) => {
      const a = c.angle + elapsed * 0.22
      const x = Math.cos(a) * radius
      const z = Math.sin(a) * radius
      const y = c.lift + Math.sin(elapsed * 0.9 + i) * 0.14
      c.card.position.set(x, y, z)
      // Kartu tetap menghadap kamera dengan sedikit goyangan.
      c.card.rotation.set(
        -orbit.rotation.x,
        Math.sin(elapsed * 0.7 + i) * 0.2,
        0,
      )
      linkPos.set([0, 0, 0, x, y, z], i * 6)
    })
    linkGeo.attributes.position.needsUpdate = true

    for (let i = 0; i < count; i++) {
      const yi = i * 3 + 1
      dots[yi] += speed[i] * dt
      if (dots[yi] > spanY) dots[yi] = -spanY
    }
    dotGeo.attributes.position.needsUpdate = true

    // Pelunakan berbasis waktu sehingga terasa sama di layar 60 Hz maupun 120 Hz.
    const ease = 1 - Math.exp(-dt * 2.5)
    root.rotation.y += (pointer.x * 0.25 - root.rotation.y) * ease
    root.rotation.x += (pointer.y * 0.12 - root.rotation.x) * ease
  }

  let revealed = false
  const render = () => {
    renderer.render(scene, camera)
    if (!revealed) {
      revealed = true
      requestAnimationFrame(() =>
        renderer.domElement.classList.remove("opacity-0"),
      )
    }
  }
  let frame = 0
  let visible = true
  const tick = (time: number) => {
    timer.update(time)
    update(Math.min(timer.getDelta(), 0.05))
    render()
    frame = requestAnimationFrame(tick)
  }
  const start = () => {
    if (reduced || frame || !visible || document.hidden) return
    timer.reset()
    frame = requestAnimationFrame(tick)
  }
  const stop = () => {
    cancelAnimationFrame(frame)
    frame = 0
  }

  const resize = () => {
    const w = Math.max(1, el.clientWidth)
    const h = Math.max(1, el.clientHeight)
    renderer.setSize(w, h, false)
    camera.aspect = w / h
    // Jarak kamera dihitung agar orbit kartu selalu muat (± 72% area) di
    // berbagai rasio, sehingga tidak ada kartu yang terpotong tepi.
    const tan = Math.tan((camera.fov * Math.PI) / 360)
    const dist = Math.max(
      2.6 + 2.1 / (0.72 * tan),
      1.2 + 3.5 / (0.72 * tan * camera.aspect),
    )
    camera.position.z = dist
    fog.near = dist - 2
    fog.far = dist + 8
    camera.updateProjectionMatrix()
    if (!frame) render()
  }
  const sizeObserver = new ResizeObserver(resize)
  sizeObserver.observe(el)
  const viewObserver = new IntersectionObserver(([entry]) => {
    visible = entry.isIntersecting
    if (visible) start()
    else stop()
  })
  viewObserver.observe(el)
  const onVisibility = () => (document.hidden ? stop() : start())
  document.addEventListener("visibilitychange", onVisibility)
  if (!reduced) window.addEventListener("pointermove", onPointer)

  update(reduced ? 0.6 : 0)
  resize()
  start()

  return () => {
    stop()
    sizeObserver.disconnect()
    viewObserver.disconnect()
    document.removeEventListener("visibilitychange", onVisibility)
    window.removeEventListener("pointermove", onPointer)
    timer.dispose()
    ;[
      coreGeo,
      coreMat,
      shellGeo,
      shellMat,
      ringGeo,
      ringMat,
      cardGeo,
      cardMat,
      edgeGeo,
      edgeMat,
      stripeGeo,
      stripeMat,
      headMat,
      linkGeo,
      linkMat,
      dotGeo,
      dotMat,
      dotTex,
    ].forEach((x) => x.dispose())
    renderer.dispose()
    renderer.forceContextLoss()
    renderer.domElement.remove()
  }
}
