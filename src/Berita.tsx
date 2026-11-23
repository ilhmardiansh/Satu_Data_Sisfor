import { useState } from "react"
import { newsTags, type News } from "./news"
import { useStore } from "./store"
import Reveal from "./Reveal"

const Meta = ({ n }: { n: News }) => (
  <div className="flex items-center gap-3 text-xs text-slate-500">
    <span>{n.date}</span>
    <span className="h-1 w-1 rounded-full bg-slate-300" />
    <span>{n.read} menit baca</span>
  </div>
)

function Article({
  n,
  onBack,
  onOpen,
}: {
  n: News
  onBack: () => void
  onOpen: (id: string) => void
}) {
  const { s } = useStore()
  const news = s.content.berita
  const related = news.filter((x) => x.id !== n.id).slice(0, 3)
  return (
    <main className="mx-auto max-w-[1200px] px-4 py-10 sm:px-8">
      <button
        onClick={onBack}
        className="text-sm font-semibold text-sky-600 hover:text-sky-700"
      >
        ← Kembali ke Berita
      </button>
      <div className="mt-6 grid grid-cols-1 gap-12 lg:grid-cols-[1fr_320px]">
        <article>
          <span className="rounded-md bg-blue-600 px-2.5 py-1 text-[11px] font-bold text-white">
            {n.badge}
          </span>
          <h1 className="mt-4 text-4xl leading-tight font-extrabold tracking-tight">
            {n.title}
          </h1>
          <div className="mt-4 flex items-center gap-3">
            <span className="grid h-9 w-9 place-items-center rounded-full bg-sky-100 text-xs font-bold text-sky-700">
              {n.ini}
            </span>
            <div>
              <p className="text-sm font-semibold">{n.au}</p>
              <Meta n={n} />
            </div>
          </div>
          <img
            src={n.img}
            alt=""
            className="mt-8 aspect-[16/9] w-full rounded-2xl object-cover"
          />
          <p className="mt-8 text-lg leading-relaxed font-medium text-slate-700">
            {n.ex}
          </p>
          {n.body.map((p, i) => (
            <p key={i} className="mt-5 leading-relaxed text-slate-600">
              {p}
            </p>
          ))}
        </article>
        <aside>
          <div className="sticky top-24">
            <h2 className="text-sm font-bold tracking-wider text-slate-400 uppercase">
              Berita lainnya
            </h2>
            <div className="mt-4 space-y-5">
              {related.map((r) => (
                <button
                  key={r.id}
                  onClick={() => onOpen(r.id)}
                  className="group flex w-full gap-3 text-left"
                >
                  <img
                    src={r.img}
                    alt=""
                    className="h-16 w-24 shrink-0 rounded-lg object-cover"
                  />
                  <div className="min-w-0">
                    <p className="text-[11px] font-bold text-sky-600">
                      {r.badge}
                    </p>
                    <p className="line-clamp-2 text-sm leading-snug font-semibold group-hover:text-blue-700">
                      {r.title}
                    </p>
                  </div>
                </button>
              ))}
            </div>
          </div>
        </aside>
      </div>
    </main>
  )
}

export default function Berita({
  openId,
  setOpenId,
}: {
  openId: string | null
  setOpenId: (id: string | null) => void
}) {
  const [tag, setTag] = useState("Semua")
  const { s } = useStore()
  const news = s.content.berita
  const [q, setQ] = useState("")
  const current = news.find((n) => n.id === openId)
  if (current)
    return (
      <Article
        n={current}
        onBack={() => setOpenId(null)}
        onOpen={(id) => {
          setOpenId(id)
          window.scrollTo(0, 0)
        }}
      />
    )

  const list = news.filter(
    (n) =>
      (tag === "Semua" || n.badge === tag) &&
      (n.title + n.ex).toLowerCase().includes(q.toLowerCase()),
  )
  const [lead, ...rest] = list
  const open = (id: string) => {
    setOpenId(id)
    window.scrollTo(0, 0)
  }

  return (
    <main className="mx-auto max-w-[1200px] px-4 py-10 sm:px-8">
      <p className="text-sm text-slate-400">
        Beranda <span className="mx-1">/</span>{" "}
        <span className="text-sky-600">Berita</span>
      </p>
      <div className="mt-1 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between sm:gap-6">
        <div className="min-w-0">
          <h1 className="text-3xl font-extrabold tracking-tight">
            Berita Sistem Informasi
          </h1>
          <p className="mt-1 text-sm text-slate-500">
            Kabar prestasi, riset, pengabdian, dan kegiatan Program Studi Sistem
            Informasi UNUSA.
          </p>
        </div>
        <input
          value={q}
          onChange={(e) => setQ(e.target.value)}
          placeholder="Cari berita…"
          aria-label="Cari berita"
          className="h-10 w-full shrink-0 rounded-xl border border-slate-200 bg-white px-4 text-sm outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100 sm:w-72"
        />
      </div>
      <div className="mt-6 flex gap-2 overflow-x-auto border-b border-slate-200 pb-4">
        {["Semua", ...newsTags].map((t) => (
          <button
            key={t}
            onClick={() => setTag(t)}
            aria-pressed={tag === t}
            className={`shrink-0 rounded-full px-4 py-1.5 text-sm font-medium whitespace-nowrap transition motion-safe:active:scale-[0.97] ${
              tag === t
                ? "bg-sky-600 text-white"
                : "bg-white text-slate-600 ring-1 ring-slate-200 hover:text-slate-900"
            }`}
          >
            {t}
          </button>
        ))}
      </div>

      {!lead && (
        <p className="mt-8 rounded-2xl border border-dashed border-slate-300 py-12 text-center text-sm text-slate-500">
          Tidak ada berita yang cocok.
        </p>
      )}
      {lead && (
        <button
          onClick={() => open(lead.id)}
          className="group mt-8 grid w-full grid-cols-1 overflow-hidden rounded-2xl border border-slate-200 bg-surface text-left transition duration-300 ease-out hover:shadow-xl hover:shadow-slate-200 md:grid-cols-[1.3fr_1fr]"
        >
          <div className="relative aspect-[16/10] overflow-hidden">
            <img
              src={lead.img}
              alt=""
              className="h-full w-full object-cover transition duration-500 group-hover:scale-105"
            />
          </div>
          <div className="flex min-w-0 flex-col justify-center p-6 sm:p-8">
            <span className="w-fit rounded-md bg-blue-600 px-2.5 py-1 text-[11px] font-bold text-white">
              {lead.badge}
            </span>
            <h2 className="mt-4 text-2xl leading-snug font-extrabold group-hover:text-blue-700">
              {lead.title}
            </h2>
            <p className="mt-3 text-slate-500">{lead.ex}</p>
            <div className="mt-6 flex items-center justify-between">
              <Meta n={lead} />
              <span className="text-sm font-bold text-blue-600">Baca →</span>
            </div>
          </div>
        </button>
      )}
      <Reveal
        stagger
        className="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3"
      >
        {rest.map((n) => (
          <button
            key={n.id}
            onClick={() => open(n.id)}
            className="group flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-surface text-left transition duration-300 ease-out hover:shadow-xl hover:shadow-slate-200 motion-safe:hover:-translate-y-1"
          >
            <div className="relative aspect-[16/10] w-full overflow-hidden">
              <img
                src={n.img}
                alt=""
                className="h-full w-full object-cover transition duration-500 group-hover:scale-105"
              />
              <span className="absolute top-3 left-3 rounded-md bg-blue-600 px-2.5 py-1 text-[11px] font-bold text-white">
                {n.badge}
              </span>
            </div>
            <div className="flex flex-1 flex-col p-5">
              <Meta n={n} />
              <h3 className="mt-3 line-clamp-3 min-h-[4.1rem] text-[17px] leading-snug font-bold group-hover:text-blue-700">
                {n.title}
              </h3>
              <p className="mt-2 line-clamp-2 text-sm text-slate-500">{n.ex}</p>
              <div className="mt-auto flex items-center justify-between pt-5">
                <span className="flex items-center gap-2 text-xs font-medium text-slate-600">
                  <span className="grid h-7 w-7 place-items-center rounded-full bg-sky-100 text-[10px] font-bold text-sky-700">
                    {n.ini}
                  </span>
                  {n.au}
                </span>
                <span className="text-sm font-bold text-blue-600">Baca →</span>
              </div>
            </div>
          </button>
        ))}
      </Reveal>
    </main>
  )
}
