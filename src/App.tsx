import { useEffect, useState, type ReactNode } from "react"
import {
  ArrowRight,
  Award,
  CalendarDays,
  ChevronDown,
  Clock,
  Database,
  LogIn,
  Mail,
  MapPin,
  Menu,
  Phone,
  X,
} from "lucide-react"
import kampus from "./assets/kampus-unusa.jpg"
import Berita from "./Berita"
import Dashboard from "./Dashboard"
import DatasetPage, { type DatasetInit } from "./Dataset"
import Login from "./Login"
import Curriculum from "./Curriculum"
import PengisianData from "./PengisianData"
import Panel from "./panel/Panel"
import Reveal from "./Reveal"
import Tentang, { DosenCard, useSectionScroll } from "./Tentang"
import { roleLabel, useStore } from "./store"
import type { Prestasi } from "./content"

type Page = "beranda" | "dashboard" | "dataset" | "berita" | "pengisian-data" | "tentang-kami" | "masuk" | "panel"
const menus: [Page, string, string][] = [
  ["beranda", "Beranda", "Home"],
  ["dashboard", "Dashboard", "Dashboard"],
  ["dataset", "Dataset", "Dataset"],
  ["berita", "Berita", "News"],
  ["pengisian-data", "Pengisian Data", "Data Submission"],
  ["tentang-kami", "Tentang kami", "About us"],
]
// Sub-menu dropdown header; id = id bagian pada halaman tujuan.
const subMenus: Partial<Record<Page, [string, string, string][]>> = {
  beranda: [
    ["berita", "Berita Prodi", "Program News"],
    ["prestasi", "Prestasi Mahasiswa", "Student Achievements"],
    ["dosen", "Data Dosen", "Lecturers"],
    ["kurikulum", "Kurikulum", "Curriculum"],
  ],
  "tentang-kami": [
    ["sejarah", "Sejarah Prodi", "History"],
    ["tentang-prodi", "Tentang Prodi", "About the Program"],
    ["detail-prodi", "Detail Prodi", "Program Details"],
  ],
}
const subMenusOf = (page: Page) => subMenus[page] ?? []

function SubLink({
  page,
  id,
  className,
  children,
}: {
  page: Page
  id: string
  className: string
  children: ReactNode
}) {
  const href = `#${page}/${id}`
  return (
    <a
      href={href}
      className={className}
      onClick={(e) => {
        // Hash sama tidak memicu hashchange; gulir langsung ke bagiannya.
        if (location.hash === href) {
          e.preventDefault()
          document
            .getElementById(id)
            ?.scrollIntoView({ behavior: "smooth", block: "start" })
        }
      }}
    >
      {children}
    </a>
  )
}

const parse = () => {
  const [p = "beranda", sec, arg] = location.hash
    .replace(/^#\/?/, "")
    .split("/")
  const page = ([
    "masuk",
    "panel",
    ...menus.map((m) => m[0]),
  ] as string[]).includes(p)
    ? p as Page
    : "beranda"
  return { page, sec: sec || "ringkasan", sub: sec, arg }
}

const noInit: DatasetInit = {}

function Logo() {
  return (
    <a href="#beranda" className="flex min-w-0 items-center gap-2.5">
      <span className="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-brand text-white">
        <Database className="h-5 w-5" />
      </span>
      <span className="min-w-0 leading-tight">
        <span className="block truncate text-[15px] font-extrabold text-ink">
          Satu Data Sistem Informasi
        </span>
        <span className="block text-[11px] text-[#62748E]">UNUSA</span>
      </span>
    </a>
  )
}

function Header({ page }: { page: Page }) {
  const { user, lang, setLang, t } = useStore()
  const [open, setOpen] = useState(false)
  const [drop, setDrop] = useState<Page | null>(null)
  useEffect(() => {
    setOpen(false)
    setDrop(null)
  }, [page])
  const Lang = (
    <div
      className="flex shrink-0 rounded-lg border border-[#E2E8F0] p-0.5 text-xs font-semibold"
      role="group"
      aria-label="Bahasa"
    >
      {(["id", "en"] as const).map((l) => (
        <button
          key={l}
          onClick={() => setLang(l)}
          aria-pressed={lang === l}
          className={`rounded-md px-2.5 py-1 uppercase ${
            lang === l ? "bg-brand text-white" : "text-[#45556C]"
          }`}
        >
          {l}
        </button>
      ))}
    </div>
  )
  const Account = user ? (
    <a
      href="#panel/ringkasan"
      className={`flex shrink-0 items-center gap-2 rounded-xl border px-2.5 py-1.5 text-sm font-semibold ${
        page === "panel"
          ? "border-sky-300 bg-sky-100/80 text-sky-700"
          : "border-[#E2E8F0] text-[#45556C] hover:border-sky-300"
      }`}
    >
      <span className="grid h-6 w-6 place-items-center rounded-full bg-brand text-[10px] font-bold text-white">
        {user.nama.replace(/^(Dr|Prof)\.?\s*/i, "").charAt(0)}
      </span>
      <span className="max-w-[120px] truncate">{roleLabel[user.role]}</span>
    </a>
  ) : (
    <a
      href="#masuk"
      className="flex shrink-0 items-center gap-1.5 rounded-xl bg-brand px-3.5 py-2 text-sm font-bold text-white hover:bg-sky-700"
    >
      <LogIn className="h-4 w-4" />
      {t("Masuk", "Sign in")}
    </a>
  )
  return (
    <header className="sticky top-0 z-40 border-b border-[#E2E8F0] bg-white">
      <div className="mx-auto flex h-16 max-w-[1200px] items-center justify-between gap-4 px-4 sm:px-8">
        <Logo />
        <nav
          className="hidden flex-1 justify-center gap-0.5 xl:flex"
          aria-label="Menu utama"
        >
          {menus.map(([k, id, en]) => {
            const subs = subMenusOf(k)
            const cls = `flex items-center gap-1 rounded-lg px-3 py-2 text-sm font-medium whitespace-nowrap transition ${
              page === k
                ? "bg-sky-100/80 text-brand-dark"
                : "text-[#45556C] hover:text-ink"
            }`
            if (!subs.length)
              return (
                <a key={k} href={`#${k}`} className={cls}>
                  {t(id, en)}
                </a>
              )
            return (
              <div
                key={k}
                className="group/menu relative"
                onMouseEnter={() => setDrop(k)}
                onMouseLeave={() => setDrop(null)}
                onKeyDown={(e) => e.key === "Escape" && setDrop(null)}
                onClick={() => {
                  setDrop(null)
                  // Lepas fokus agar focus-within tidak menahan dropdown tetap terbuka.
                  ;(document.activeElement as HTMLElement | null)?.blur()
                }}
              >
                <a
                  href={`#${k}`}
                  className={cls}
                  aria-haspopup="true"
                  aria-expanded={drop === k}
                >
                  {t(id, en)}
                  <ChevronDown
                    className={`h-3.5 w-3.5 transition-transform duration-200 group-focus-within/menu:rotate-180 ${
                      drop === k ? "rotate-180" : ""
                    }`}
                  />
                </a>
                {/* pt-2 menjembatani celah agar dropdown tidak tertutup saat kursor turun. */}
                <div
                  className={`absolute top-full left-1/2 z-50 w-60 -translate-x-1/2 pt-2 transition duration-150 group-focus-within/menu:visible group-focus-within/menu:translate-y-0 group-focus-within/menu:opacity-100 ${
                    drop === k
                      ? "visible translate-y-0 opacity-100"
                      : "invisible translate-y-1 opacity-0"
                  }`}
                >
                  <div className="rounded-xl border border-[#E2E8F0] bg-white p-1.5 shadow-xl shadow-slate-900/10">
                    {subs.map(([sid, label, enLabel]) => (
                      <SubLink
                        key={sid}
                        page={k}
                        id={sid}
                        className="block rounded-lg px-3 py-2.5 text-sm font-medium text-[#45556C] transition hover:bg-sky-50 hover:text-brand-dark focus:bg-sky-50 focus:text-brand-dark focus:outline-none"
                      >
                        {t(label, enLabel)}
                      </SubLink>
                    ))}
                  </div>
                </div>
              </div>
            )
          })}
        </nav>
        <div className="hidden items-center gap-2 xl:flex">
          {Lang}
          {Account}
        </div>
        <button
          onClick={() => setOpen(!open)}
          className="grid h-10 w-10 shrink-0 place-items-center rounded-xl border border-[#E2E8F0] text-[#45556C] xl:hidden"
          aria-label={open ? "Tutup menu" : "Buka menu"}
          aria-expanded={open}
        >
          {open ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
        </button>
      </div>
      {open && (
        <div className="border-t border-[#E2E8F0] bg-white px-4 pt-2 pb-4 sm:px-8 xl:hidden">
          <nav className="grid gap-0.5" aria-label="Menu utama">
            {menus.map(([k, id, en]) => (
              <div key={k}>
                <a
                  href={`#${k}`}
                  className={`block rounded-lg px-3 py-2.5 text-sm font-medium ${
                    page === k
                      ? "bg-sky-100/80 text-brand-dark"
                      : "text-[#45556C]"
                  }`}
                >
                  {t(id, en)}
                </a>
                {subMenusOf(k).length > 0 && (
                  <div className="mb-1 ml-3 grid gap-0.5 border-l border-[#E2E8F0] pl-2">
                    {subMenusOf(k).map(([sid, label, enLabel]) => (
                      <SubLink
                        key={sid}
                        page={k}
                        id={sid}
                        className="rounded-lg px-3 py-2 text-[13px] text-[#62748E] hover:bg-slate-50 hover:text-ink"
                      >
                        {t(label, enLabel)}
                      </SubLink>
                    ))}
                  </div>
                )}
              </div>
            ))}
          </nav>
          <div className="mt-3 flex items-center justify-between gap-3 border-t border-[#E2E8F0] pt-3">
            {Lang}
            {Account}
          </div>
        </div>
      )}
    </header>
  )
}

function Section({
  id,
  title,
  sub,
  link,
  href,
  children,
  white,
}: {
  id?: string
  title: string
  sub?: string
  link?: string
  href?: string
  children: ReactNode
  white?: boolean
}) {
  return (
    <section id={id} className={`scroll-mt-16 ${white ? "bg-white" : ""}`}>
      <div className="mx-auto max-w-[1200px] px-4 py-14 sm:px-8 sm:py-16">
        <Reveal className="mb-8 flex flex-wrap items-end justify-between gap-4">
          <div>
            <h2 className="text-[24px] font-extrabold tracking-tight text-ink sm:text-[28px]">
              {title}
            </h2>
            {sub && (
              <p className="mt-1.5 max-w-3xl text-[15px] text-[#62748E]">
                {sub}
              </p>
            )}
          </div>
          {link && (
            <a
              href={href}
              className="group/link flex shrink-0 items-center gap-1.5 text-sm font-semibold text-brand hover:text-sky-700"
            >
              {link}
              <ArrowRight className="h-4 w-4 transition-transform duration-200 motion-safe:group-hover/link:translate-x-0.5" />
            </a>
          )}
        </Reveal>
        {children}
      </div>
    </section>
  )
}

function Home({
  sec,
  openNews,
}: {
  sec?: string
  openNews: (id: string | null) => void
}) {
  const { s, t } = useStore()
  useSectionScroll(sec)
  const tingkatCls = {
    Internasional: "bg-violet-50 text-violet-700",
    Nasional: "bg-sky-100/80 text-brand-dark",
    Regional: "bg-emerald-50 text-emerald-700",
  } as const
  return (
    <main>
      <section className="relative isolate overflow-hidden">
        <img
          src={kampus}
          alt="Gedung kampus Universitas Nahdlatul Ulama Surabaya"
          className="absolute inset-0 h-full w-full origin-center object-cover motion-safe:animate-ken-burns"
        />
        <div className="absolute inset-0 bg-gradient-to-r from-benhur/95 via-benhur/80 to-deep/45" />
        <div className="relative mx-auto max-w-[1200px] px-4 py-16 text-white sm:px-8 sm:py-20">
          <h1 className="max-w-3xl text-[32px] leading-[1.15] font-extrabold tracking-tight sm:text-[44px] motion-safe:animate-fade-up">
            Program Studi{" "}
            <span className="text-accent-soft">Sistem Informasi</span> UNUSA
          </h1>
          <p className="mt-5 max-w-2xl text-base leading-relaxed text-white/90 sm:text-lg motion-safe:animate-fade-up motion-safe:[animation-delay:120ms]">
            {t(
              "Tempat ide, teknologi, dan bisnis bertemu. Ikuti kabar terbaru prodi, rayakan capaian mahasiswa, kenali para dosen, dan jelajahi kurikulum yang menyiapkan Anda menjadi penggerak transformasi digital.",
              "Where ideas, technology, and business meet. Follow the latest program news, celebrate student achievements, meet our lecturers, and explore a curriculum that prepares you to drive digital transformation.",
            )}
          </p>
        </div>
      </section>

      <Section
        id="berita"
        title={t("Berita Prodi", "Program News")}
        link={t("Semua berita", "All news")}
        href="#berita"
      >
        {s.content.berita.length ? (
          <Reveal
            stagger
            className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3"
          >
            {s.content.berita.slice(0, 3).map((n) => (
              <article
                key={n.id}
                className="group flex flex-col overflow-hidden rounded-2xl border border-[#E2E8F0] bg-surface transition duration-300 ease-out hover:shadow-xl hover:shadow-slate-200 motion-safe:hover:-translate-y-1"
              >
                <div className="relative aspect-[16/10] overflow-hidden">
                  <img
                    src={n.img}
                    alt=""
                    className="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                  />
                  <span className="absolute top-3 left-3 rounded-md bg-brand px-2.5 py-1 text-[11px] font-bold text-white">
                    {n.badge}
                  </span>
                </div>
                <div className="flex flex-1 flex-col p-5">
                  <div className="flex items-center gap-4 text-xs text-[#62748E]">
                    <span className="flex items-center gap-1.5">
                      <CalendarDays className="h-3.5 w-3.5" />
                      {n.date}
                    </span>
                    <span className="flex items-center gap-1.5">
                      <Clock className="h-3.5 w-3.5" />
                      {n.read} {t("menit baca", "min read")}
                    </span>
                  </div>
                  <h3 className="mt-3 line-clamp-3 text-[17px] leading-snug font-bold group-hover:text-brand">
                    {n.title}
                  </h3>
                  <p className="mt-2 line-clamp-2 text-sm text-[#62748E]">
                    {n.ex}
                  </p>
                  <button
                    onClick={() => openNews(n.id)}
                    className="mt-auto flex items-center gap-1 self-end pt-5 text-sm font-bold text-brand transition motion-safe:active:scale-[0.97]"
                  >
                    {t("Baca", "Read")}
                    <ArrowRight className="h-4 w-4 transition-transform duration-200 motion-safe:group-hover:translate-x-0.5" />
                  </button>
                </div>
              </article>
            ))}
          </Reveal>
        ) : (
          <EmptyNote>
            {t("Belum ada berita prodi.", "No program news yet.")}
          </EmptyNote>
        )}
      </Section>

      <Section
        id="prestasi"
        white
        title={t("Prestasi Mahasiswa", "Student Achievements")}
      >
        {s.content.prestasi.length ? (
          <Reveal
            stagger
            className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
          >
            {s.content.prestasi.map((x) => (
              <PrestasiCard
                key={x.id}
                x={x}
                tingkatCls={tingkatCls[x.tingkat]}
              />
            ))}
          </Reveal>
        ) : (
          <EmptyNote>
            {t("Belum ada prestasi mahasiswa.", "No achievements yet.")}
          </EmptyNote>
        )}
      </Section>

      <Section id="dosen" title={t("Data Dosen", "Lecturers")}>
        {s.content.dosen.length ? (
          <Reveal
            stagger
            className="grid grid-cols-1 gap-4 min-[420px]:grid-cols-2 lg:grid-cols-4"
          >
            {s.content.dosen.map((d) => (
              <DosenCard key={d.id} d={d} />
            ))}
          </Reveal>
        ) : (
          <EmptyNote>
            {t("Data dosen belum tersedia.", "No lecturers yet.")}
          </EmptyNote>
        )}
      </Section>

      <Curriculum />
    </main>
  )
}

type PrestasiCardProps = {
  x: Prestasi
  tingkatCls: string
}

function PrestasiCard({ x, tingkatCls }: PrestasiCardProps) {
  const [failed, setFailed] = useState<string | null>(null)
  const photo = x.foto && failed !== x.foto
  return (
    <article className="group flex min-w-0 flex-col overflow-hidden rounded-2xl border border-[#E2E8F0] bg-surface transition duration-300 hover:border-amber-200 hover:shadow-lg hover:shadow-slate-200/70">
      <div className="relative aspect-[16/10] overflow-hidden bg-gradient-to-br from-amber-50 to-amber-100">
        {photo ? (
          <img
            src={x.foto}
            alt={`Dokumentasi ${x.judul}`}
            loading="lazy"
            onError={() => setFailed(x.foto!)}
            className="h-full w-full object-cover transition duration-500 group-hover:scale-105"
          />
        ) : (
          <span className="grid h-full w-full place-items-center text-amber-500">
            <Award className="h-10 w-10" />
          </span>
        )}
        {photo && (
          <div className="absolute inset-x-0 bottom-0 h-1/2 bg-gradient-to-t from-black/45 to-transparent" />
        )}
        <span
          className={`absolute top-3 right-3 rounded-full px-2.5 py-1 text-xs font-semibold shadow-sm ${tingkatCls}`}
        >
          {x.tingkat}
        </span>
        <span className="absolute bottom-3 left-3 inline-flex items-center gap-1.5 rounded-full bg-amber-400 px-3 py-1 text-xs font-extrabold text-amber-950 shadow-sm">
          <Award className="h-3.5 w-3.5" />
          {x.peringkat}
        </span>
      </div>
      <div className="flex flex-1 flex-col p-5">
        <h3 className="leading-snug font-bold text-ink">{x.judul}</h3>
        <p className="mt-auto pt-3 text-sm text-[#62748E]">
          {x.nama} · {x.tahun}
        </p>
      </div>
    </article>
  )
}

function EmptyNote({ children }: { children: ReactNode }) {
  return (
    <p className="rounded-2xl border border-dashed border-[#E2E8F0] p-6 text-sm text-[#62748E]">
      {children}
    </p>
  )
}

function Footer() {
  const { s, t } = useStore()
  const p = s.content.profil
  return (
    <footer className="bg-gradient-to-br from-benhur to-deep text-white/80">
      <div className="mx-auto grid grid-cols-1 max-w-[1200px] gap-8 px-4 py-12 text-sm sm:px-8 md:grid-cols-3">
        <div>
          <p className="flex items-center gap-2 font-extrabold text-white">
            <Database className="h-5 w-5 text-accent-soft" />
            Satu Data Sistem Informasi
          </p>
          <p className="mt-2">
            {t(
              "Portal data terintegrasi Program Studi Sistem Informasi, Universitas Nahdlatul Ulama Surabaya.",
              "Integrated data portal of the Information Systems Study Program, Universitas Nahdlatul Ulama Surabaya.",
            )}
          </p>
        </div>
        <div className="space-y-2">
          <p className="flex gap-2">
            <MapPin className="h-4 w-4 shrink-0" />
            {p.alamat}
          </p>
          <p className="flex gap-2">
            <Mail className="h-4 w-4 shrink-0" />
            {p.email}
          </p>
          <p className="flex gap-2">
            <Phone className="h-4 w-4 shrink-0" />
            {p.telp}
          </p>
        </div>
        <nav className="grid grid-cols-2 gap-2">
          {menus.map(([k, id, en]) => (
            <a key={k} href={`#${k}`} className="hover:text-white">
              {t(id, en)}
            </a>
          ))}
        </nav>
      </div>
      <p className="border-t border-white/15 px-4 py-4 text-center text-xs">
        © {new Date().getFullYear()} Satu Data Sistem Informasi UNUSA ·{" "}
        {t(
          "Prototipe antarmuka: data tersimpan di peramban.",
          "Interface prototype: data is stored in the browser.",
        )}
      </p>
    </footer>
  )
}

export default function App() {
  const { user } = useStore()
  const [route, setRoute] = useState(parse)
  const [newsId, setNewsId] = useState<string | null>(null)
  useEffect(() => {
    const on = () => {
      setRoute(parse())
      window.scrollTo(0, 0)
    }
    window.addEventListener("hashchange", on)
    return () => window.removeEventListener("hashchange", on)
  }, [])
  const nav = (h: string) => {
    if (location.hash === `#${h}`) setRoute(parse())
    else location.hash = h
  }
  const openNews = (id: string | null) => {
    setNewsId(id)
    nav("berita")
  }
  const { page, sec, arg } = route

  return (
    <div className="flex min-h-screen flex-col bg-white text-ink">
      <Header page={page} />
      {/* key per halaman memicu transisi masuk ringan setiap pindah rute. */}
      <div key={page} className="flex-1 motion-safe:animate-page-in">
        {page === "beranda" && <Home sec={route.sub} openNews={openNews} />}
        {page === "dashboard" && <Dashboard />}
        {page === "dataset" && <DatasetPage init={noInit} />}
        {page === "berita" && <Berita openId={newsId} setOpenId={setNewsId} />}
        {page === "pengisian-data" && <PengisianData />}
        {page === "tentang-kami" && <Tentang sec={route.sub} />}
        {page === "masuk" &&
          (user ? (
            <Redirect to="panel/ringkasan" />
          ) : (
            <Login
              onDone={() => nav("panel/ringkasan")}
              onBack={() => nav("beranda")}
            />
          ))}
        {page === "panel" &&
          (user ? (
            <Panel
              sec={sec}
              arg={arg}
              go={(s, a) => nav(`panel/${s}${a ? `/${a}` : ""}`)}
            />
          ) : (
            <Login
              onDone={() => setRoute(parse())}
              onBack={() => nav("beranda")}
            />
          ))}
      </div>
      {page !== "masuk" && page !== "panel" && <Footer />}
    </div>
  )
}

function Redirect({ to }: { to: string }) {
  useEffect(() => {
    location.replace(`#${to}`)
  }, [to])
  return null
}
