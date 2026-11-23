import { useCallback, useEffect, useRef, useState, type ReactNode } from "react"
import {
  ArrowRight,
  BookOpenText,
  Building2,
  History,
  Mail,
  X,
} from "lucide-react"
import { createPortal } from "react-dom"
import type { Dosen } from "./content"
import { useStore } from "./store"
import { Modal } from "./ui"

export const initials = (n: string) =>
  n.replace(/^(Dr|Prof)\.?\s*/i, "")
    .split(/[\s,]+/)
    .filter((w) => /^[A-Z]/.test(w))
    .slice(0, 2)
    .map((w) => w[0])
    .join("")

// Gulir ke sub-bagian dari dropdown header, mis. #tentang-kami/sejarah.
export function useSectionScroll(sec?: string) {
  useEffect(() => {
    if (!sec) return
    const id = window.setTimeout(
      () =>
        document
          .getElementById(sec)
          ?.scrollIntoView({ behavior: "smooth", block: "start" }),
      60,
    )
    return () => window.clearTimeout(id)
  }, [sec])
}

type PhotoProps = {
  d: Dosen
  className: string
}

type InfoProps = {
  label: string
  children: ReactNode
}

type DetailProps = {
  d: Dosen
  onClose: () => void
}

function DosenPhoto({ d, className }: PhotoProps) {
  const [failed, setFailed] = useState<string | null>(null)
  return (
    <span
      className={`grid shrink-0 place-items-center overflow-hidden bg-sky-100/80 font-extrabold text-brand-dark ${className}`}
    >
      {d.foto && failed !== d.foto ? (
        <img
          src={d.foto}
          alt={`Foto ${d.nama}`}
          className="h-full w-full object-cover"
          loading="lazy"
          onError={() => setFailed(d.foto!)}
        />
      ) : (
        initials(d.nama)
      )}
    </span>
  )
}

function Info({ label, children }: InfoProps) {
  return (
    <div className="min-w-0">
      <dt className="text-xs font-semibold tracking-wide text-[#62748E] uppercase">
        {label}
      </dt>
      <dd className="mt-1 text-sm font-medium break-words text-ink">
        {children}
      </dd>
    </div>
  )
}

function DosenDetail({ d, onClose }: DetailProps) {
  const { t } = useStore()
  const mk = (d.mataKuliah ?? "")
    .split(",")
    .map((x) => x.trim())
    .filter(Boolean)
  return (
    <Modal onClose={onClose}>
      <div className="overflow-y-auto">
        <div className="relative bg-gradient-to-br from-benhur via-benhur to-deep px-6 pt-6 pb-16 text-white sm:px-8">
          <div className="flex items-start justify-between gap-4">
            <p className="text-xs font-semibold tracking-wider text-white/80 uppercase">
              {t("Profil Dosen", "Lecturer Profile")}
            </p>
            <button
              onClick={onClose}
              autoFocus
              aria-label={t("Tutup", "Close")}
              className="grid h-9 w-9 place-items-center rounded-lg bg-white/10 transition hover:bg-white/20"
            >
              <X className="h-4 w-4" />
            </button>
          </div>
        </div>
        <div className="relative -mt-12 px-6 pb-7 sm:px-8">
          <DosenPhoto
            d={d}
            className="h-28 w-28 rounded-2xl text-3xl ring-4 ring-white shadow-lg"
          />
          <h2 className="mt-4 text-2xl font-extrabold tracking-tight text-ink">
            {d.nama}
          </h2>
          <p className="mt-1 text-sm text-[#62748E]">
            {d.jabatan} ·{" "}
            {t("Program Studi Sistem Informasi", "Information Systems Program")}
          </p>
          <span className="mt-3 inline-flex rounded-full bg-sky-100/80 px-3 py-1 text-xs font-semibold text-brand-dark">
            {d.bidang}
          </span>
          {d.profil && (
            <p className="mt-5 text-sm leading-relaxed whitespace-pre-line text-[#45556C]">
              {d.profil}
            </p>
          )}
          <dl className="mt-6 grid grid-cols-1 gap-4 rounded-2xl border border-[#E2E8F0] bg-[#F8FAFC] p-5 sm:grid-cols-2">
            <Info label="NIDN">
              <span className="font-mono">{d.nidn}</span>
            </Info>
            <Info label={t("Pendidikan terakhir", "Highest degree")}>
              {d.pendidikan}
            </Info>
            <Info label={t("Jabatan fungsional", "Academic rank")}>
              {d.jabatan}
            </Info>
            <Info label={t("Bidang keahlian", "Expertise")}>{d.bidang}</Info>
            {d.email && (
              <Info label="Email">
                <a
                  href={`mailto:${d.email}`}
                  className="inline-flex items-center gap-1.5 text-brand hover:underline"
                >
                  <Mail className="h-3.5 w-3.5" />
                  {d.email}
                </a>
              </Info>
            )}
          </dl>
          {mk.length > 0 && (
            <div className="mt-6">
              <h3 className="text-sm font-bold text-ink">
                {t("Mata kuliah yang diampu", "Courses taught")}
              </h3>
              <ul className="mt-3 flex flex-wrap gap-2">
                {mk.map((x) => (
                  <li
                    key={x}
                    className="rounded-lg border border-[#E2E8F0] px-3 py-1.5 text-sm text-[#45556C]"
                  >
                    {x}
                  </li>
                ))}
              </ul>
            </div>
          )}
        </div>
      </div>
    </Modal>
  )
}

export function DosenCard({ d }: { d: Dosen }) {
  const { t } = useStore()
  const [open, setOpen] = useState(false)
  const btnRef = useRef<HTMLButtonElement>(null)
  const close = useCallback(() => {
    setOpen(false)
    btnRef.current?.focus()
  }, [])
  return (
    <article className="group flex h-full min-w-0 flex-col items-center rounded-2xl border border-[#E2E8F0] bg-surface px-5 pt-7 pb-5 text-center transition duration-300 ease-out hover:border-sky-200 hover:shadow-lg hover:shadow-sky-900/5 motion-safe:hover:-translate-y-1">
      <DosenPhoto
        d={d}
        className="h-24 w-24 rounded-full text-2xl ring-4 ring-white outline outline-1 outline-sky-100"
      />
      <h3 className="mt-4 leading-snug font-bold text-ink">{d.nama}</h3>
      <p className="mt-1 text-[13px] text-[#62748E]">
        {d.jabatan} · {d.pendidikan}
      </p>
      <span className="mt-3 inline-flex rounded-full bg-sky-100/80 px-3 py-1 text-xs font-semibold text-brand-dark">
        {d.bidang}
      </span>
      <div className="mt-auto w-full pt-5">
        <button
          ref={btnRef}
          onClick={() => setOpen(true)}
          aria-haspopup="dialog"
          className="inline-flex w-full items-center justify-center gap-1.5 rounded-xl border border-[#E2E8F0] px-4 py-2.5 text-sm font-semibold text-brand transition hover:border-brand hover:bg-sky-50 motion-safe:active:scale-[0.98]"
        >
          {t("Lebih lanjut", "Learn more")}
          <ArrowRight className="h-4 w-4 transition-transform motion-safe:group-hover:translate-x-0.5" />
        </button>
      </div>
      {open &&
        createPortal(<DosenDetail d={d} onClose={close} />, document.body)}
    </article>
  )
}

function Block({
  id,
  icon: Icon,
  title,
  children,
}: {
  id: string
  icon: typeof History
  title: string
  children: ReactNode
}) {
  return (
    <section
      id={id}
      className="grid scroll-mt-24 grid-cols-1 gap-5 border-b border-[#E2E8F0] py-12 last:border-0 md:grid-cols-[16rem_minmax(0,1fr)] md:gap-10"
    >
      <h2 className="flex items-center gap-3 self-start text-[22px] font-extrabold tracking-tight text-ink md:sticky md:top-24">
        <span className="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-sky-100/80 text-brand">
          <Icon className="h-5 w-5" />
        </span>
        {title}
      </h2>
      <div className="min-w-0">{children}</div>
    </section>
  )
}

export default function Tentang({ sec }: { sec?: string }) {
  const { s, t } = useStore()
  const p = s.content.profil
  useSectionScroll(sec)
  const missing = t("Belum diisi", "Not provided")
  return (
    <main>
      <section className="bg-gradient-to-br from-benhur via-benhur to-deep text-white">
        <div className="mx-auto max-w-[1200px] px-4 py-12 sm:px-8 sm:py-14">
          <p className="text-sm text-white/75">
            {t("Beranda", "Home")} <span className="mx-1">/</span>{" "}
            <span className="font-semibold text-white">
              {t("Tentang kami", "About us")}
            </span>
          </p>
          <h1 className="mt-2 max-w-3xl text-[32px] leading-tight font-extrabold tracking-tight sm:text-[40px]">
            Program Studi{" "}
            <span className="text-accent-soft">Sistem Informasi</span> UNUSA
          </h1>
        </div>
      </section>

      <div className="mx-auto max-w-[1200px] px-4 sm:px-8">
        <Block
          id="sejarah"
          icon={History}
          title={t("Sejarah Prodi", "History")}
        >
          <p className="max-w-3xl leading-relaxed whitespace-pre-line text-[#45556C]">
            {p.sejarah ||
              t(
                "Sejarah program studi belum dipublikasikan.",
                "Program history has not been published yet.",
              )}
          </p>
        </Block>

        <Block
          id="tentang-prodi"
          icon={BookOpenText}
          title={t("Tentang Prodi", "About the Program")}
        >
          <p className="max-w-3xl leading-relaxed text-[#45556C]">
            {p.deskripsi}
          </p>
          <dl className="mt-6 grid max-w-xl grid-cols-2 gap-4">
            {[
              [t("Akreditasi", "Accreditation"), p.akreditasi],
              [t("Tahun berdiri", "Established"), p.berdiri],
            ].map(([l, v]) => (
              <div
                key={l}
                className="rounded-2xl border border-[#E2E8F0] bg-surface p-5"
              >
                <dt className="text-sm text-[#62748E]">{l}</dt>
                <dd className="mt-1 text-2xl font-extrabold text-ink">
                  {v || missing}
                </dd>
              </div>
            ))}
          </dl>
        </Block>

        <Block
          id="detail-prodi"
          icon={Building2}
          title={t("Detail Prodi", "Program Details")}
        >
          <dl className="overflow-hidden rounded-2xl border border-[#E2E8F0] bg-surface text-sm">
            {[
              [t("Nama program", "Program"), p.program],
              [t("Gelar", "Degree"), p.gelar],
              [t("Durasi", "Duration"), p.durasi],
              [t("Syarat masuk", "Entry requirements"), p.syarat],
              [t("Lokasi kampus", "Campus location"), p.alamat],
              [t("Fakultas", "Faculty"), p.fakultas],
              [t("Fasilitas", "Facilities"), p.fasilitas],
              [
                t("Kontak", "Contact"),
                [p.email, p.telp].filter(Boolean).join(" · "),
              ],
            ].map(([label, value]) => (
              <div
                key={label}
                className="grid grid-cols-1 gap-1 border-b border-slate-100 px-5 py-4 last:border-0 sm:grid-cols-[10rem_minmax(0,1fr)] sm:gap-4"
              >
                <dt className="font-semibold text-ink">{label}</dt>
                <dd
                  className={`min-w-0 break-words whitespace-pre-line ${
                    value ? "text-[#45556C]" : "text-slate-400"
                  }`}
                >
                  {value || missing}
                </dd>
              </div>
            ))}
          </dl>
        </Block>
      </div>
    </main>
  )
}
