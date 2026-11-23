import { useState } from "react"
import { BookOpen, ChevronDown } from "lucide-react"
import { useStore } from "./store"
import { sel } from "./ui"

export default function Curriculum() {
  const { s, t } = useStore()
  const courses = s.content.kurikulum
  const years = [...new Set(courses.map((c) => c.tahun))].sort((a, b) => b - a)
  const [year, setYear] = useState("")
  const [open, setOpen] = useState<number[]>([])
  const active = years.includes(Number(year)) ? Number(year) : years[0]
  const list = courses
    .filter((c) => c.tahun === active)
    .sort((a, b) => a.semester - b.semester || a.kode.localeCompare(b.kode))
  const semesters = [...new Set(list.map((c) => c.semester))]
  const toggle = (n: number) =>
    setOpen((o) => (o.includes(n) ? o.filter((x) => x !== n) : [...o, n]))
  return (
    <section id="kurikulum" className="scroll-mt-20 bg-white">
      <div className="mx-auto max-w-[1200px] px-4 py-14 sm:px-8 sm:py-16">
        <div className="mb-8 flex flex-wrap items-end justify-between gap-4">
          <div>
            <h2 className="text-[24px] font-extrabold tracking-tight text-ink sm:text-[28px]">
              {t("Kurikulum", "Curriculum")}
            </h2>
            <p className="mt-1.5 text-[15px] text-[#62748E]">
              {t(
                "Pilih semester untuk melihat mata kuliah wajib dan pilihan.",
                "Choose a semester to see required and elective courses.",
              )}
            </p>
          </div>
          {years.length > 1 && (
            <select
              aria-label={t("Tahun kurikulum", "Curriculum year")}
              className={sel}
              value={active}
              onChange={(e) => {
                setYear(e.target.value)
                setOpen([])
              }}
            >
              {years.map((y) => (
                <option key={y} value={y}>
                  {t("Kurikulum", "Curriculum")} {y}
                </option>
              ))}
            </select>
          )}
        </div>
        {!semesters.length ? (
          <p className="rounded-2xl border border-dashed border-[#E2E8F0] p-6 text-sm text-[#62748E]">
            {t(
              "Kurikulum belum dipublikasikan oleh program studi.",
              "The program has not published its curriculum yet.",
            )}
          </p>
        ) : (
          <div className="space-y-3">
            {semesters.map((n) => {
              const items = list.filter((c) => c.semester === n)
              const isOpen = open.includes(n)
              const panelId = `kurikulum-semester-${n}`
              return (
                <div
                  key={n}
                  className={`overflow-hidden rounded-2xl border bg-surface transition-colors ${
                    isOpen ? "border-sky-200" : "border-[#E2E8F0]"
                  }`}
                >
                  <button
                    type="button"
                    onClick={() => toggle(n)}
                    aria-expanded={isOpen}
                    aria-controls={panelId}
                    className="flex w-full items-center gap-4 px-5 py-4 text-left hover:bg-white/70"
                  >
                    <span
                      className={`grid h-10 w-10 shrink-0 place-items-center rounded-xl text-sm font-extrabold ${
                        isOpen
                          ? "bg-brand text-white"
                          : "bg-sky-100/80 text-brand-dark"
                      }`}
                    >
                      {n}
                    </span>
                    <span className="min-w-0 flex-1">
                      <span className="block font-bold text-ink">
                        Semester {n}
                      </span>
                      <span className="block text-sm text-[#62748E]">
                        {items.length} {t("mata kuliah", "courses")} ·{" "}
                        {items.reduce((sum, c) => sum + c.sks, 0)} SKS
                      </span>
                    </span>
                    <ChevronDown
                      className={`h-5 w-5 shrink-0 text-[#62748E] transition-transform duration-200 ${
                        isOpen ? "rotate-180" : ""
                      }`}
                    />
                  </button>
                  {isOpen && (
                    <ul
                      id={panelId}
                      className="divide-y divide-slate-100 border-t border-slate-100"
                    >
                      {items.map((c) => (
                        <li
                          key={c.id}
                          className="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-x-4 gap-y-1 px-5 py-3 sm:grid-cols-[7rem_minmax(0,1fr)_5rem_4rem]"
                        >
                          <span className="flex items-center gap-2 text-xs font-semibold text-[#62748E] sm:text-sm">
                            <BookOpen className="h-4 w-4 shrink-0 text-sky-600" />
                            {c.kode}
                          </span>
                          <span className="row-start-2 min-w-0 font-semibold text-ink sm:row-start-auto">
                            {c.nama}
                          </span>
                          <span
                            className={`justify-self-end rounded-md px-2 py-0.5 text-xs font-semibold sm:justify-self-start ${
                              c.pilihan
                                ? "bg-amber-50 text-amber-700"
                                : "bg-slate-100 text-slate-600"
                            }`}
                          >
                            {c.pilihan
                              ? t("Pilihan", "Elective")
                              : t("Wajib", "Required")}
                          </span>
                          <span className="row-start-2 text-right text-sm font-bold text-ink sm:row-start-auto">
                            {c.sks} SKS
                          </span>
                        </li>
                      ))}
                    </ul>
                  )}
                </div>
              )
            })}
          </div>
        )}
      </div>
    </section>
  )
}
