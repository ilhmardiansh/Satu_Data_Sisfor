import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useRef,
  useState,
  type ReactNode,
} from "react"
import { content as seedContent, type Content } from "./content"
import {
  categories as seedCategories,
  datasets as seedDatasets,
  type Category,
  type Dataset,
} from "./datasets"
import { fieldError } from "./dataQuality"

// PROTOTIPE: seluruh data, akun, dan sesi hanya disimpan di localStorage peramban.
// Tidak ada backend (Laravel/MySQL), otorisasi server, maupun pengiriman email sungguhan.

export type Role = "produsen" | "wali" | "admin"
export const roleLabel: Record<Role, string> = {
  produsen: "Produsen Data",
  wali: "Wali Data",
  admin: "Admin",
}
export type User = {
  id: string
  nama: string
  nim: string
  password: string
  role: Role
  jenis?: "Mahasiswa" | "Dosen"
  aktif: boolean
}
export type FieldType = "text" | "textarea" | "number" | "date" | "select" | "file"
export type FormField = {
  id: string
  label: string
  type: FieldType
  required: boolean
  options?: string[]
}
export type DataForm = {
  id: string
  title: string
  desc: string
  kategori: string
  tahun: number
  periode: "Ganjil" | "Genap"
  target: "Mahasiswa" | "Dosen" | "Semua"
  deadline: string
  fields: FormField[]
  createdBy: string
  createdAt: string
}
export type Submission = {
  id: string
  formId: string
  userId: string
  at: string
  values: Record<string, string>
  cleanedBy?: string
  cleanedAt?: string
}
export type DataRequest = {
  id: string
  datasetId: string
  nama: string
  email: string
  hp: string
  nim: string
  tujuan: string
  deskripsi: string
  at: string
  status: "menunggu" | "disetujui" | "ditolak"
  note?: string
  decidedBy?: string
  decidedAt?: string
}
export type Kind = "number" | "donut" | "pie" | "bar" | "hbar" | "line" | "radar"
export type Viz = {
  id: string
  title: string
  datasetId: string
  labelCol: number
  valueCol: number
  kind: Kind
  color: string
  show: boolean
  createdBy: string
  createdAt: string
}
export type State = {
  users: User[]
  forms: DataForm[]
  submissions: Submission[]
  requests: DataRequest[]
  viz: Viz[]
  datasets: Dataset[]
  categories: Category[]
  content: Content
  mine: string[]
}

const KEY = "satudata-unusa-v2"
const SESSION = "satudata-unusa-session"
const LANG = "satudata-lang"
export const IDLE_MS = 30 * 60 * 1000

export const uid = (p: string) =>
  `${p}-${Date.now().toString(36).slice(-4).toUpperCase()}${Math.random().toString(36).slice(2, 5).toUpperCase()}`
export const fmtDate = (iso: string, time = false) => {
  const d = new Date(iso)
  if (Number.isNaN(d.getTime())) return iso
  return d.toLocaleDateString("id-ID", {
    day: "numeric",
    month: "short",
    year: "numeric",
    ...(time ? { hour: "2-digit", minute: "2-digit" } : {}),
  })
}
export const isOpen = (f: DataForm) =>
  new Date(f.deadline).getTime() > Date.now()
export const canSee = (u: User, f: DataForm) =>
  u.role !== "produsen" || f.target === "Semua" || f.target === u.jenis

const day = 86400000
const iso = (offset: number) => new Date(Date.now() + offset).toISOString()

function seed(): State {
  const forms: DataForm[] = [
    {
      id: "F-01",
      title: "Pelaporan Prestasi Mahasiswa 2026",
      desc: "Laporkan prestasi lomba atau rekognisi yang diraih selama semester berjalan beserta bukti sertifikat.",
      kategori: "KT-MHS-04",
      tahun: 2026,
      periode: "Ganjil",
      target: "Mahasiswa",
      deadline: iso(10 * day),
      createdBy: "U-02",
      createdAt: iso(-12 * day),
      fields: [
        { id: "q1", label: "Nama kompetisi", type: "text", required: true },
        {
          id: "q2",
          label: "Tingkat",
          type: "select",
          required: true,
          options: ["Regional", "Nasional", "Internasional"],
        },
        { id: "q3", label: "Peringkat", type: "text", required: true },
        {
          id: "q4",
          label: "Tanggal pelaksanaan",
          type: "date",
          required: true,
        },
        { id: "q5", label: "Sertifikat / bukti", type: "file", required: true },
      ],
    },
    {
      id: "F-02",
      title: "Pendataan Penerima Beasiswa",
      desc: "Pendataan mahasiswa penerima beasiswa aktif tahun akademik 2026/2027.",
      kategori: "KT-MHS-05",
      tahun: 2026,
      periode: "Ganjil",
      target: "Mahasiswa",
      deadline: iso(2 * day),
      createdBy: "U-02",
      createdAt: iso(-7 * day),
      fields: [
        {
          id: "q1",
          label: "Jenis beasiswa",
          type: "select",
          required: true,
          options: ["KIP Kuliah", "Prestasi", "Yayasan", "Mitra Industri"],
        },
        {
          id: "q2",
          label: "Tahun mulai menerima",
          type: "number",
          required: true,
        },
        {
          id: "q3",
          label: "SK penerima beasiswa",
          type: "file",
          required: true,
        },
      ],
    },
    {
      id: "F-03",
      title: "Laporan Penelitian Dosen 2026",
      desc: "Pelaporan judul, skema, pendanaan, dan luaran penelitian dosen.",
      kategori: "KT-TRD-02",
      tahun: 2026,
      periode: "Ganjil",
      target: "Dosen",
      deadline: iso(20 * day),
      createdBy: "U-02",
      createdAt: iso(-5 * day),
      fields: [
        { id: "q1", label: "Judul penelitian", type: "text", required: true },
        {
          id: "q2",
          label: "Skema pendanaan",
          type: "select",
          required: true,
          options: ["Hibah eksternal", "Internal", "Mitra"],
        },
        { id: "q3", label: "Jumlah dana (Rp)", type: "number", required: true },
        { id: "q4", label: "Luaran", type: "textarea", required: false },
        {
          id: "q5",
          label: "Kontrak penelitian",
          type: "file",
          required: false,
        },
      ],
    },
    {
      id: "F-04",
      title: "Kegiatan Pengabdian Masyarakat 2025",
      desc: "Pendataan kegiatan PKM semester genap 2025.",
      kategori: "KT-TRD-03",
      tahun: 2025,
      periode: "Genap",
      target: "Dosen",
      deadline: iso(-5 * day),
      createdBy: "U-02",
      createdAt: iso(-40 * day),
      fields: [
        { id: "q1", label: "Judul kegiatan", type: "text", required: true },
        { id: "q2", label: "Mitra", type: "text", required: true },
        { id: "q3", label: "Jumlah peserta", type: "number", required: true },
      ],
    },
  ]
  return {
    users: [
      {
        id: "U-01",
        nama: "Administrator Prodi",
        nim: "admin",
        password: "admin123",
        role: "admin",
        aktif: true,
      },
      {
        id: "U-02",
        nama: "Rina Wulandari, M.Kom",
        nim: "walidata",
        password: "wali123",
        role: "wali",
        aktif: true,
      },
      {
        id: "U-03",
        nama: "Nabila Putri",
        nim: "1462200031",
        password: "mhs123",
        role: "produsen",
        jenis: "Mahasiswa",
        aktif: true,
      },
      {
        id: "U-04",
        nama: "Fajar Hidayat",
        nim: "1462300012",
        password: "mhs123",
        role: "produsen",
        jenis: "Mahasiswa",
        aktif: true,
      },
      {
        id: "U-05",
        nama: "Dr. Andi Setiawan, M.Kom",
        nim: "0712048601",
        password: "dosen123",
        role: "produsen",
        jenis: "Dosen",
        aktif: true,
      },
    ],
    forms,
    submissions: [
      {
        id: "S-01",
        formId: "F-01",
        userId: "U-03",
        at: iso(-6 * day),
        values: {
          q1: "Gemastik UI/UX",
          q2: "Nasional",
          q3: "Juara 1",
          q4: "2026-09-14",
          q5: "sertifikat-gemastik.pdf",
        },
      },
      {
        id: "S-02",
        formId: "F-01",
        userId: "U-04",
        at: iso(-4 * day),
        values: {
          q1: "Hackathon Jatim",
          q2: "Regional",
          q3: "Juara 2",
          q4: "2026-09-28",
          q5: "piagam-hackathon.jpg",
        },
      },
      {
        id: "S-03",
        formId: "F-02",
        userId: "U-03",
        at: iso(-3 * day),
        values: { q1: "KIP Kuliah", q2: "2022", q3: "sk-kip-2022.pdf" },
      },
      {
        id: "S-04",
        formId: "F-03",
        userId: "U-05",
        at: iso(-2 * day),
        values: {
          q1: "Prediksi retensi mahasiswa berbasis ML",
          q2: "Hibah eksternal",
          q3: "75000000",
          q4: "Artikel jurnal Q1",
          q5: "kontrak-penelitian.pdf",
        },
      },
      {
        id: "S-05",
        formId: "F-04",
        userId: "U-05",
        at: iso(-20 * day),
        values: {
          q1: "Digitalisasi pencatatan UMKM",
          q2: "Desa Wonocolo",
          q3: "35",
        },
      },
    ],
    requests: [
      {
        id: "R-01",
        datasetId: "DS-02",
        nama: "Dimas Arya",
        email: "dimas@example.com",
        hp: "081234567890",
        nim: "1462100045",
        tujuan: "Penelitian / tugas akhir",
        deskripsi:
          "Data penerima KIP angkatan 2022–2023 untuk analisis retensi mahasiswa.",
        at: iso(-1 * day),
        status: "menunggu",
      },
      {
        id: "R-02",
        datasetId: "DS-04",
        nama: "Tim LPM UNUSA",
        email: "lpm@example.com",
        hp: "082233445566",
        nim: "0099887766",
        tujuan: "Akreditasi & penjaminan mutu",
        deskripsi: "Rincian rekognisi mahasiswa untuk borang akreditasi.",
        at: iso(-8 * day),
        status: "disetujui",
        decidedBy: "U-02",
        decidedAt: iso(-7 * day),
      },
    ],
    viz: [
      {
        id: "V-01",
        title: "Total penerima beasiswa per tahun",
        datasetId: "DS-01",
        labelCol: 0,
        valueCol: 5,
        kind: "bar",
        color: "sky",
        show: true,
        createdBy: "U-02",
        createdAt: iso(-10 * day),
      },
      {
        id: "V-02",
        title: "Prestasi mahasiswa per tingkat",
        datasetId: "DS-03",
        labelCol: 0,
        valueCol: 3,
        kind: "donut",
        color: "violet",
        show: true,
        createdBy: "U-02",
        createdAt: iso(-9 * day),
      },
      {
        id: "V-03",
        title: "Tren penelitian dosen",
        datasetId: "DS-07",
        labelCol: 0,
        valueCol: 4,
        kind: "line",
        color: "teal",
        show: true,
        createdBy: "U-02",
        createdAt: iso(-8 * day),
      },
    ],
    datasets: seedDatasets,
    categories: seedCategories,
    content: seedContent,
    mine: [],
  }
}

type StateKey = keyof State
type Seedable = {
  id: string
  foto?: string
}

function load(): State {
  const base = seed()
  try {
    const raw = localStorage.getItem(KEY)
    if (!raw) return base
    const p = JSON.parse(raw) as Partial<State>
    if (!p || typeof p !== "object") return base
    const merged = {
      ...base,
      ...p,
      content: {
        ...base.content,
        ...p.content,
        profil: { ...base.content.profil, ...p.content?.profil },
      },
    }
    for (const key of ["berita", "kurikulum", "dosen", "prestasi"] as const) {
      if (!Array.isArray(merged.content[key])) {
        Object.assign(merged.content, { [key]: base.content[key] })
      }
    }
    if (!p.content?.kurikulumSeeded && !merged.content.kurikulum.length) {
      merged.content.kurikulum = base.content.kurikulum
    }
    merged.content.kurikulumSeeded = true
    // Isi foto contoh sekali untuk data bawaan yang belum pernah punya foto.
    if (!p.content?.fotoSeeded) {
      const fill = <T extends Seedable>(list: T[], src: T[]) =>
        list.map((x) =>
          x.foto === undefined
            ? { ...x, foto: src.find((y) => y.id === x.id)?.foto }
            : x,
        )
      merged.content.dosen = fill(merged.content.dosen, base.content.dosen)
      merged.content.prestasi = fill(
        merged.content.prestasi,
        base.content.prestasi,
      )
    }
    merged.content.fotoSeeded = true
    // Abaikan nilai tersimpan yang bentuknya tidak cocok (mis. array rusak).
    for (const k of Object.keys(base) as StateKey[]) {
      if (Array.isArray(base[k]) !== Array.isArray(merged[k])) {
        ;(merged as Record<string, unknown>)[k] = base[k]
      }
    }
    return merged
  } catch {
    return base
  }
}
type Session = {
  userId: string
  last: number
}
const readSession = (): Session | null => {
  try {
    return JSON.parse(localStorage.getItem(SESSION) ?? "null") as Session | null
  } catch {
    return null
  }
}

export type Lang = "id" | "en"
type Ctx = {
  s: State
  set: (fn: (p: State) => State) => void
  user: User | null
  login: (nim: string, password: string) => string | null
  logout: () => void
  submit: (formId: string, values: Record<string, string>) => string | null
  reset: () => void
  notice: string | null
  clearNotice: () => void
  lang: Lang
  setLang: (l: Lang) => void
  t: (id: string, en: string) => string
}
const StoreC = createContext<Ctx | null>(null)
export const useStore = () => {
  const c = useContext(StoreC)
  if (!c) throw new Error("useStore harus dipakai di dalam StoreProvider")
  return c
}

export function StoreProvider({ children }: { children: ReactNode }) {
  const [s, setS] = useState<State>(load)
  const [session, setSession] = useState<Session | null>(() => {
    const x = readSession()
    return x && Date.now() - x.last < IDLE_MS ? x : null
  })
  const [notice, setNotice] = useState<string | null>(null)
  const [lang, setLangRaw] = useState<Lang>(() =>
    localStorage.getItem(LANG) === "en" ? "en" : "id",
  )
  const sRef = useRef(s)
  sRef.current = s

  useEffect(() => {
    localStorage.setItem(KEY, JSON.stringify(s))
  }, [s])
  useEffect(() => {
    if (session) localStorage.setItem(SESSION, JSON.stringify(session))
    else localStorage.removeItem(SESSION)
  }, [session])

  const user = session
    ? (s.users.find((u) => u.id === session.userId && u.aktif) ?? null)
    : null
  const set = useCallback((fn: (p: State) => State) => setS((p) => fn(p)), [])
  const logout = useCallback(() => {
    setSession(null)
    location.hash = "masuk"
  }, [])

  // Sesi berakhir otomatis setelah 30 menit tanpa aktivitas (simulasi di sisi peramban).
  useEffect(() => {
    if (!session) return
    let last = session.last
    const touch = () => {
      const now = Date.now()
      if (now - last > 15000) {
        last = now
        setSession((x) => (x ? { ...x, last: now } : x))
      }
    }
    const check = () => {
      if (Date.now() - last >= IDLE_MS) {
        setSession(null)
        setNotice(
          "Sesi Anda berakhir karena tidak ada aktivitas selama 30 menit. Silakan masuk kembali.",
        )
      }
    }
    const evs = [
      "mousemove",
      "keydown",
      "click",
      "scroll",
      "touchstart",
    ] as const
    evs.forEach((e) => window.addEventListener(e, touch, { passive: true }))
    const id = window.setInterval(check, 20000)
    return () => {
      evs.forEach((e) => window.removeEventListener(e, touch))
      window.clearInterval(id)
    }
  }, [session?.userId]) // eslint-disable-line react-hooks/exhaustive-deps

  const value = useMemo<Ctx>(
    () => ({
      s,
      set,
      user,
      logout,
      notice,
      clearNotice: () => setNotice(null),
      login: (nim, password) => {
        const u = sRef.current.users.find((x) => x.nim === nim.trim())
        if (!u || u.password !== password) return "NIM/NIP atau password salah."
        if (!u.aktif) return "Akun Anda dinonaktifkan. Hubungi Admin prodi."
        setNotice(null)
        setSession({ userId: u.id, last: Date.now() })
        return null
      },
      submit: (formId, values) => {
        const f = sRef.current.forms.find((x) => x.id === formId)
        if (!user) return "Sesi berakhir. Silakan masuk kembali."
        if (user.role !== "produsen")
          return "Hanya Produsen Data yang dapat mengisi formulir."
        if (!f) return "Formulir tidak ditemukan."
        if (!canSee(user, f)) return "Formulir ini tidak ditujukan untuk Anda."
        // Penolakan otomatis setelah tenggat (hanya di peramban, bukan validasi server).
        if (!isOpen(f))
          return "Tenggat waktu formulir telah berakhir. Pengiriman ditolak."
        for (const field of f.fields) {
          const error = fieldError(field, values[field.id] ?? "")
          if (error) return `${field.label}: ${error}`
        }
        const sub: Submission = {
          id: uid("S"),
          formId,
          userId: user.id,
          at: new Date().toISOString(),
          values,
        }
        setS((p) => ({ ...p, submissions: [sub, ...p.submissions] }))
        return null
      },
      reset: () => {
        const keep = sRef.current.users.find((u) => u.id === session?.userId)
        const n = seed()
        setS(n)
        if (!keep || !n.users.some((u) => u.id === keep.id)) setSession(null)
      },
      lang,
      setLang: (l) => {
        setLangRaw(l)
        localStorage.setItem(LANG, l)
      },
      t: (id, en) => (lang === "en" ? en : id),
    }),
    [s, set, user, logout, notice, lang, session?.userId],
  )

  return <StoreC.Provider value={value}>{children}</StoreC.Provider>
}
