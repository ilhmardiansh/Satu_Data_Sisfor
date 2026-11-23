import { useMemo, useState } from "react"
import {
  ArrowRight,
  BriefcaseBusiness,
  CalendarClock,
  CheckCircle2,
  ClipboardList,
  FileText,
  GraduationCap,
  Search,
  Users,
} from "lucide-react"
import { canSee, fmtDate, isOpen, useStore, type DataForm } from "./store"
import CountUp from "./CountUp"
import DataScene from "./DataScene"
import { FillForm, left } from "./panel/Forms"
import Reveal from "./Reveal"
import { btn, inp, sel } from "./ui"

const targetIcon = {
  Mahasiswa: GraduationCap,
  Dosen: BriefcaseBusiness,
  Semua: Users,
}

export default function PengisianData() {
  const { s, user, t } = useStore()
  const [query, setQuery] = useState("")
  const [target, setTarget] = useState("Semua")
  const [open, setOpen] = useState<DataForm | null>(null)

  const active = s.forms.filter(isOpen)
  const categories = new Set(active.map((f) => f.kategori)).size
  const forms = useMemo(() => {
    const keyword = query.trim().toLowerCase()
    return active
      .filter((f) => target === "Semua" || f.target === target)
      .filter((f) => {
        const category =
          s.categories.find((c) => c.code === f.kategori)?.name ?? ""
        return (
          !keyword ||
          `${f.title} ${f.desc} ${category}`.toLowerCase().includes(keyword)
        )
      })
      .sort((a, b) => a.deadline.localeCompare(b.deadline))
  }, [active, query, s.categories, target])

  const action = (form: DataForm) => {
    if (!user) {
      location.hash = "#masuk"
      return
    }
    if (user.role === "produsen" && canSee(user, form)) {
      setOpen(form)
      return
    }
    location.hash = user.role === "produsen" ? "#panel/isi" : "#panel/formulir"
  }

  return (
    <main className="mx-auto max-w-[1200px] px-4 py-10 sm:px-8">
      <p className="text-sm text-slate-500">
        {t("Beranda", "Home")} <span className="mx-1">/</span>{" "}
        <span className="text-sky-600">
          {t("Pengisian Data", "Data Submission")}
        </span>
      </p>

      <section className="relative isolate mt-4 overflow-hidden rounded-3xl bg-gradient-to-br from-benhur via-benhur to-deep text-white shadow-lg shadow-slate-300/60">
        <div
          aria-hidden="true"
          className="pointer-events-none absolute -top-24 -left-20 h-72 w-72 rounded-full bg-accent/30 blur-3xl motion-safe:animate-float"
        />
        <div
          aria-hidden="true"
          className="pointer-events-none absolute -right-16 -bottom-32 h-80 w-80 rounded-full bg-white/15 blur-3xl motion-safe:animate-float motion-safe:[animation-delay:-8s]"
        />
        {/* Adegan dekoratif dibatasi di sisi kanan agar tidak menutupi teks; disembunyikan di layar kecil. */}
        <DataScene className="pointer-events-none absolute inset-y-6 right-4 hidden w-[44%] opacity-75 md:block lg:right-8 lg:w-[42%] lg:opacity-90" />
        <div className="relative z-10 flex flex-col gap-8 p-6 sm:p-9 md:w-[58%] lg:min-h-96 lg:justify-between">
          <div>
            <span className="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1.5 text-xs font-bold tracking-wide text-white uppercase motion-safe:animate-fade-up">
              <ClipboardList className="h-4 w-4" />
              {t("Portal pengumpulan data", "Data collection portal")}
            </span>
            <h1 className="mt-4 max-w-2xl text-3xl font-extrabold tracking-tight sm:text-4xl motion-safe:animate-fade-up motion-safe:[animation-delay:90ms]">
              {t(
                "Pilih dan isi formulir data Anda",
                "Choose and complete your data form",
              )}
            </h1>
            <p className="mt-3 max-w-2xl text-sm leading-6 text-white/85 sm:text-base motion-safe:animate-fade-up motion-safe:[animation-delay:180ms]">
              {t(
                "Temukan formulir yang sesuai, periksa tenggat waktu, lalu kirim data dan dokumen pendukung secara terarah.",
                "Find the relevant form, check its deadline, then submit your data and supporting documents.",
              )}
            </p>
          </div>
          <div className="grid max-w-md grid-cols-3 gap-2 motion-safe:animate-fade-up motion-safe:[animation-delay:270ms] sm:gap-3">
            {[
              [active.length, t("Form aktif", "Active forms")],
              [categories, t("Kategori", "Categories")],
              [
                active.filter((f) => f.target === "Mahasiswa").length,
                t("Mahasiswa", "Students"),
              ],
            ].map(([value, label]) => (
              <div
                key={label}
                className="min-w-0 rounded-2xl bg-white/10 px-1.5 py-3 text-center ring-1 ring-white/10 backdrop-blur-sm sm:p-3"
              >
                <strong className="block text-2xl font-extrabold">
                  <CountUp value={value} />
                </strong>
                <span className="mt-1 block text-[11px] text-white/80">
                  {label}
                </span>
              </div>
            ))}
          </div>
        </div>
      </section>

      <section className="mt-8">
        <div className="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-surface p-4 shadow-sm sm:flex-row">
          <label className="relative flex-1">
            <Search className="pointer-events-none absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-slate-400" />
            <input
              value={query}
              onChange={(e) => setQuery(e.target.value)}
              className={`${inp} pl-10`}
              placeholder={t(
                "Cari nama atau kategori formulir...",
                "Search forms or categories...",
              )}
            />
          </label>
          <select
            value={target}
            onChange={(e) => setTarget(e.target.value)}
            className={`${sel} w-full sm:w-48`}
            aria-label={t("Filter sasaran", "Target filter")}
          >
            <option value="Semua">{t("Semua formulir", "All forms")}</option>
            <option value="Mahasiswa">{t("Mahasiswa", "Students")}</option>
            <option value="Dosen">{t("Dosen", "Lecturers")}</option>
          </select>
        </div>

        <div className="mt-5 flex items-end justify-between gap-4">
          <div>
            <h2 className="text-xl font-extrabold text-slate-900">
              {t("Formulir tersedia", "Available forms")}
            </h2>
            <p className="mt-1 text-sm text-slate-500">
              {forms.length} {t("formulir sesuai pilihan", "matching forms")}
            </p>
          </div>
        </div>

        {forms.length ? (
          <Reveal
            stagger
            className="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3"
          >
            {forms.map((form) => {
              const Icon = targetIcon[form.target]
              const category = s.categories.find(
                (c) => c.code === form.kategori,
              )?.name
              const mine = user
                ? s.submissions.filter(
                    (x) => x.formId === form.id && x.userId === user.id,
                  ).length
                : 0
              const eligible = user?.role === "produsen" && canSee(user, form)
              const urgent =
                new Date(form.deadline).getTime() - Date.now() < 3 * 86400000

              return (
                <article
                  key={form.id}
                  className="group flex min-h-80 flex-col overflow-hidden rounded-2xl border border-slate-200 bg-surface shadow-sm transition duration-300 ease-out hover:border-sky-300 hover:shadow-lg hover:shadow-sky-900/5 motion-safe:hover:-translate-y-1"
                >
                  <div className="h-1.5 bg-gradient-to-r from-sky-500 to-blue-700" />
                  <div className="flex flex-1 flex-col p-5">
                    <div className="flex items-start justify-between gap-3">
                      <span className="grid h-11 w-11 place-items-center rounded-xl bg-sky-100/80 text-sky-700">
                        <Icon className="h-5 w-5" />
                      </span>
                      <span className="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600">
                        {form.target}
                      </span>
                    </div>

                    <p className="mt-4 text-xs font-bold tracking-wide text-sky-700 uppercase">
                      {category}
                    </p>
                    <h3 className="mt-1 text-lg leading-snug font-extrabold text-slate-900">
                      {form.title}
                    </h3>
                    <p className="mt-2 mb-5 line-clamp-3 text-sm leading-6 text-slate-500">
                      {form.desc}
                    </p>

                    {/* mt-auto menjaga info dan tombol sejajar di dasar kartu. */}
                    <div className="mt-auto space-y-2 border-t border-slate-100 pt-4 text-xs text-slate-500">
                      <div className="flex items-center justify-between gap-3">
                        <span className="flex items-center gap-1.5">
                          <FileText className="h-4 w-4 text-slate-400" />
                          {form.fields.length} {t("pertanyaan", "questions")}
                        </span>
                        <span>
                          {form.tahun} · {form.periode}
                        </span>
                      </div>
                      <div
                        className={`flex items-center gap-1.5 font-semibold ${
                          urgent ? "text-red-600" : "text-amber-700"
                        }`}
                      >
                        <CalendarClock className="h-4 w-4" />
                        {left(form.deadline)} · {fmtDate(form.deadline)}
                      </div>
                      {mine > 0 && (
                        <div className="flex items-center gap-1.5 font-semibold text-emerald-600">
                          <CheckCircle2 className="h-4 w-4" />
                          {t("Sudah dikirim", "Submitted")} {mine}×
                        </div>
                      )}
                    </div>

                    <button
                      onClick={() => action(form)}
                      className={`${btn.primary} mt-5 w-full`}
                    >
                      {eligible
                        ? mine
                          ? t("Kirim lagi", "Submit again")
                          : t("Isi formulir", "Complete form")
                        : user
                          ? t("Buka panel formulir", "Open forms panel")
                          : t("Masuk untuk mengisi", "Sign in to complete")}
                      <ArrowRight className="h-4 w-4" />
                    </button>
                  </div>
                </article>
              )
            })}
          </Reveal>
        ) : (
          <div className="mt-5 rounded-2xl border border-dashed border-slate-300 bg-surface px-6 py-16 text-center">
            <ClipboardList className="mx-auto h-10 w-10 text-slate-300" />
            <h3 className="mt-3 font-bold text-slate-800">
              {t("Formulir tidak ditemukan", "No forms found")}
            </h3>
            <p className="mt-1 text-sm text-slate-500">
              {t(
                "Coba ubah kata kunci atau filter sasaran.",
                "Try another keyword or target filter.",
              )}
            </p>
          </div>
        )}
      </section>

      {open && <FillForm f={open} onClose={() => setOpen(null)} />}
    </main>
  )
}
