import { useState } from "react"
import { Database, Eye, EyeOff, LogIn } from "lucide-react"
import kampus from "./assets/kampus-unusa.jpg"
import { useStore } from "./store"
import { Alert, Field, btn, inp } from "./ui"

const demo = [
  ["admin", "admin123", "Admin"],
  ["walidata", "wali123", "Wali Data"],
  ["1462200031", "mhs123", "Produsen · Mahasiswa"],
  ["0712048601", "dosen123", "Produsen · Dosen"],
]

export default function Login({
  onDone,
  onBack,
}: {
  onDone: () => void
  onBack: () => void
}) {
  const { login, t, notice } = useStore()
  const [nim, setNim] = useState("")
  const [pw, setPw] = useState("")
  const [show, setShow] = useState(false)
  const [tried, setTried] = useState(false)
  const [err, setErr] = useState("")
  const submit = (e: React.FormEvent) => {
    e.preventDefault()
    setTried(true)
    setErr("")
    if (!nim.trim() || !pw) return
    const r = login(nim, pw)
    if (r) setErr(r)
    else onDone()
  }
  return (
    <main className="grid min-h-[calc(100dvh-65px)] grid-cols-1 lg:grid-cols-2">
      {/* Panel gambar menempel setinggi layar (di bawah header 65px) supaya teksnya selalu terlihat. */}
      <section className="relative hidden overflow-hidden lg:sticky lg:top-[65px] lg:block lg:h-[calc(100dvh-65px)] lg:self-start">
        <img
          src={kampus}
          alt="Gedung kampus UNUSA"
          className="absolute inset-0 h-full w-full object-cover"
        />
        <div className="absolute inset-0 bg-gradient-to-br from-benhur/95 via-benhur/85 to-deep/75" />
        <div className="relative flex h-full flex-col justify-center p-10 text-white xl:p-12">
          <span className="mb-5 inline-flex w-fit items-center gap-2 rounded-lg border border-white/25 bg-white/10 px-3 py-1 text-sm">
            Area internal
          </span>
          <h1 className="max-w-md text-[40px] leading-tight font-extrabold tracking-tight">
            Kelola <span className="text-accent-soft">data prodi</span> dalam
            satu tempat
          </h1>
          <p className="mt-4 max-w-md text-lg text-white/85">
            Isi formulir, kelola data dan dataset, buat visualisasi, dan
            konfirmasi permohonan data sesuai peran Anda.
          </p>
        </div>
      </section>
      <section className="flex items-center justify-center px-4 py-8 sm:px-8 lg:py-10">
        <div className="w-full max-w-md">
          <div className="flex items-center gap-3">
            <span className="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-sky-600 text-white">
              <Database className="h-5 w-5" />
            </span>
            <h2 className="text-2xl leading-tight font-extrabold tracking-tight sm:text-[28px]">
              {t("Masuk ke Satu Data", "Sign in to Satu Data")}
            </h2>
          </div>
          <p className="mt-3 text-[15px] text-slate-500">
            {t(
              "Gunakan NIM/NIP dan password yang terdaftar.",
              "Use your registered student/staff ID and password.",
            )}
          </p>
          <form noValidate onSubmit={submit} className="mt-6 space-y-4">
            {notice && !err && <Alert tone="warn">{notice}</Alert>}
            {err && <Alert tone="error">{err}</Alert>}
            <Field
              label="NIM / NIP"
              required
              error={tried && !nim.trim() ? "NIM/NIP wajib diisi." : undefined}
            >
              <input
                autoComplete="username"
                className={inp}
                value={nim}
                onChange={(e) => setNim(e.target.value)}
                placeholder="Contoh: 1462200031"
              />
            </Field>
            <Field
              label="Password"
              required
              error={tried && !pw ? "Password wajib diisi." : undefined}
            >
              <div className="relative">
                <input
                  type={show ? "text" : "password"}
                  autoComplete="current-password"
                  className={`${inp} pr-11`}
                  value={pw}
                  onChange={(e) => setPw(e.target.value)}
                />
                <button
                  type="button"
                  onClick={() => setShow(!show)}
                  aria-label={
                    show ? "Sembunyikan password" : "Tampilkan password"
                  }
                  className="absolute inset-y-0 right-0 grid w-11 place-items-center text-slate-400 hover:text-slate-700"
                >
                  {show ? (
                    <EyeOff className="h-4 w-4" />
                  ) : (
                    <Eye className="h-4 w-4" />
                  )}
                </button>
              </div>
            </Field>
            <button className={`${btn.primary} w-full py-3 text-[15px]`}>
              <LogIn className="h-4 w-4" />
              {t("Masuk", "Sign in")}
            </button>
            <p className="text-center text-xs text-slate-500">
              Sesi berakhir otomatis setelah 30 menit tidak aktif. Autentikasi
              ini simulasi lokal prototipe, bukan login server.
            </p>
          </form>
          <div className="mt-6 rounded-2xl border border-dashed border-slate-300 bg-surface p-4">
            <p className="text-xs font-bold tracking-wider text-slate-500 uppercase">
              Akun demo prototipe
            </p>
            <div className="mt-2 grid grid-cols-1 gap-1 sm:grid-cols-2">
              {demo.map(([n, p, l]) => (
                <button
                  key={n}
                  type="button"
                  onClick={() => {
                    setNim(n)
                    setPw(p)
                    setErr("")
                  }}
                  className="flex min-w-0 items-center justify-between gap-3 rounded-lg px-2.5 py-1.5 text-left text-sm transition-colors hover:bg-sky-50 sm:flex-col sm:items-start sm:justify-start sm:gap-0.5 sm:py-2"
                >
                  <span className="font-semibold text-slate-700">{l}</span>
                  <span className="max-w-full truncate font-mono text-xs text-slate-500">
                    {n} / {p}
                  </span>
                </button>
              ))}
            </div>
          </div>
          <button
            onClick={onBack}
            className="mt-5 text-sm font-semibold text-sky-600 hover:text-sky-700"
          >
            ← {t("Kembali ke beranda", "Back to home")}
          </button>
        </div>
      </section>
    </main>
  )
}
