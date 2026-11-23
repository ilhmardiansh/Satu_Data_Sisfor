import { useState } from "react"
import { BarChart3 } from "lucide-react"
import {
  Chart,
  fmt,
  kinds,
  numericCols,
  palettes,
  series,
  type Item,
} from "./charts"
import Reveal from "./Reveal"
import { useStore, type Kind, type Viz } from "./store"

type Table = {
  heading: string
  label: string
  cols: string[]
  rows: [string, ...number[]][]
}
type Metric = {
  table: Table
  title: string
  color: string
  total: string
  unit: string
  trend?: {
    v: string
    up: boolean
  }
  data: Item[]
  foot?: [string, string][]
  def?: Kind
}

const akademik1: Metric[] = [
  {
    title: "Mahasiswa Aktif",
    table: {
      heading: "Mahasiswa aktif per angkatan dan jenis kelamin",
      label: "Angkatan",
      cols: ["Laki-laki", "Perempuan"],
      rows: [
        ["2026", 112, 92],
        ["2025", 105, 91],
        ["2024", 99, 84],
        ["2023", 93, 78],
        ["2022 dan sebelumnya", 55, 33],
      ],
    },
    color: "sky",
    total: "842",
    unit: "mahasiswa aktif",
    data: [
      { n: "Angkatan 2026", v: 204 },
      { n: "Angkatan 2025", v: 196 },
      { n: "Angkatan 2024", v: 183 },
      { n: "Angkatan 2023", v: 171 },
      { n: "≤ 2022", v: 88 },
    ],
    foot: [
      ["Maba 2026", "204"],
      ["Lulus tepat waktu", "71,2%"],
    ],
    def: "donut",
  },
  {
    title: "Kategori Mahasiswa",
    table: {
      heading: "Mahasiswa baru per tahun masuk berdasarkan kategori",
      label: "Tahun masuk",
      cols: ["Reguler", "Beasiswa"],
      rows: [
        ["2022", 142, 44],
        ["2023", 148, 49],
        ["2024", 157, 56],
        ["2025", 165, 62],
        ["2026", 171, 66],
      ],
    },
    color: "blue",
    total: "237",
    unit: "mahasiswa 2026",
    trend: { v: "4,2% vs 2025", up: true },
    data: [
      { n: "Reguler", v: 171 },
      { n: "Beasiswa", v: 66 },
    ],
    foot: [["Rasio beasiswa", "27,8%"]],
  },
  {
    title: "Penerimaan Beasiswa",
    table: {
      heading: "Penerima beasiswa per jenis dan jenis kelamin",
      label: "Jenis beasiswa",
      cols: ["Laki-laki", "Perempuan"],
      rows: [
        ["KIP Kuliah", 17, 21],
        ["Prestasi", 5, 7],
        ["Yayasan", 4, 5],
        ["Mitra Industri", 3, 2],
        ["Lainnya", 1, 1],
      ],
    },
    color: "indigo",
    total: "66",
    unit: "penerima",
    trend: { v: "6,5% vs 2025", up: true },
    data: [
      { n: "KIP Kuliah", v: 38 },
      { n: "Prestasi", v: 12 },
      { n: "Yayasan", v: 9 },
      { n: "Mitra Industri", v: 5 },
      { n: "Lainnya", v: 2 },
    ],
  },
  {
    title: "Alumni",
    table: {
      heading: "Status alumni per tahun lulus (tracer study)",
      label: "Tahun lulus",
      cols: [
        "Bekerja",
        "Wirausaha",
        "Studi lanjut",
        "Mencari kerja",
        "Belum memberi",
      ],
      rows: [
        ["2021", 128, 22, 18, 30, 6],
        ["2022", 140, 24, 19, 36, 7],
        ["2023", 150, 26, 20, 42, 8],
        ["2024", 158, 27, 19, 48, 8],
        ["2025", 166, 29, 20, 54, 9],
      ],
    },
    color: "cyan",
    total: "1.214",
    unit: "responden tracer",
    data: [
      { n: "Bekerja", v: 742 },
      { n: "Wirausaha", v: 128 },
      { n: "Studi lanjut", v: 96 },
      { n: "Mencari kerja", v: 210 },
      { n: "Belum memberi", v: 38 },
    ],
    foot: [["Masa tunggu", "3,4 bulan"]],
  },
]
const akademik2: Metric[] = [
  {
    title: "Mahasiswa Berprestasi",
    table: {
      heading: "Prestasi mahasiswa per tingkat dan bidang",
      label: "Tingkat",
      cols: ["Akademik", "Non-akademik"],
      rows: [
        ["Internasional", 4, 2],
        ["Nasional", 15, 8],
        ["Regional", 11, 8],
      ],
    },
    color: "violet",
    total: "48",
    unit: "prestasi 2025",
    trend: { v: "20% vs 2024", up: true },
    data: [
      { n: "Internasional", v: 6 },
      { n: "Nasional", v: 23 },
      { n: "Regional", v: 19 },
    ],
  },
  {
    title: "Penelitian & PKM",
    table: {
      heading: "Judul penelitian dan pengabdian per sumber dana",
      label: "Kegiatan",
      cols: ["Hibah eksternal", "Internal", "Mitra"],
      rows: [
        ["Penelitian", 6, 10, 3],
        ["Pengabdian (PKM)", 4, 10, 1],
      ],
    },
    color: "teal",
    total: "34",
    unit: "judul 2025",
    trend: { v: "13,3% vs 2024", up: true },
    data: [
      { n: "Penelitian", v: 19 },
      { n: "Pengabdian", v: 15 },
    ],
    foot: [["Dana hibah", "Rp 1,2 M"]],
  },
  {
    title: "Publikasi Ilmiah",
    table: {
      heading: "Publikasi per jenis dan keterlibatan penulis",
      label: "Jenis publikasi",
      cols: ["Dosen", "Dosen + mahasiswa"],
      rows: [
        ["Jurnal Sinta", 20, 11],
        ["Jurnal Scopus", 7, 3],
        ["Prosiding", 12, 10],
      ],
    },
    color: "sky",
    total: "63",
    unit: "publikasi 2025",
    trend: { v: "14,5% vs 2024", up: true },
    data: [
      { n: "Jurnal Sinta", v: 31 },
      { n: "Jurnal Scopus", v: 10 },
      { n: "Prosiding", v: 22 },
    ],
    foot: [["Publikasi / dosen", "2,42"]],
  },
  {
    title: "Sebaran IPK",
    table: {
      heading: "Sebaran IPK mahasiswa aktif per jenis kelamin",
      label: "Rentang IPK",
      cols: ["Laki-laki", "Perempuan"],
      rows: [
        ["< 2,50", 26, 12],
        ["2,50 – 3,00", 74, 52],
        ["3,00 – 3,50", 208, 176],
        ["3,50 – 4,00", 156, 138],
      ],
    },
    color: "blue",
    total: "3,41",
    unit: "rata-rata IPK",
    data: [
      { n: "< 2,5", v: 38 },
      { n: "2,5–3,0", v: 126 },
      { n: "3,0–3,5", v: 384 },
      { n: "3,5–4,0", v: 294 },
    ],
  },
]
const nonakademik1: Metric[] = [
  {
    title: "Dosen",
    table: {
      heading: "Dosen per status dan pendidikan tertinggi",
      label: "Status",
      cols: ["S2", "S3"],
      rows: [
        ["Dosen tetap", 12, 11],
        ["Dosen tidak tetap", 3, 0],
      ],
    },
    color: "sky",
    total: "26",
    unit: "dosen aktif",
    data: [
      { n: "Tetap", v: 23 },
      { n: "Tidak tetap", v: 3 },
    ],
    foot: [
      ["Rasio dosen : mhs", "1 : 32,4"],
      ["Kualifikasi S3", "11 (42%)"],
    ],
  },
  {
    title: "Jabatan Fungsional",
    table: {
      heading: "Dosen per jabatan fungsional dan pendidikan",
      label: "Jabatan",
      cols: ["S2", "S3"],
      rows: [
        ["Tenaga Pengajar", 3, 0],
        ["Asisten Ahli", 6, 1],
        ["Lektor", 5, 5],
        ["Lektor Kepala", 1, 4],
        ["Profesor", 0, 1],
      ],
    },
    color: "indigo",
    total: "26",
    unit: "dosen",
    data: [
      { n: "Tenaga Pengajar", v: 3 },
      { n: "Asisten Ahli", v: 7 },
      { n: "Lektor", v: 10 },
      { n: "Lektor Kepala", v: 5 },
      { n: "Profesor", v: 1 },
    ],
  },
  {
    title: "Tenaga Kependidikan",
    table: {
      heading: "Tenaga kependidikan per unit dan status",
      label: "Posisi",
      cols: ["Tetap", "Kontrak"],
      rows: [
        ["Admin Prodi", 2, 1],
        ["Laboran", 2, 1],
        ["Pustakawan", 1, 0],
      ],
    },
    color: "cyan",
    total: "7",
    unit: "tendik",
    data: [
      { n: "Admin Prodi", v: 3 },
      { n: "Laboran", v: 3 },
      { n: "Pustakawan", v: 1 },
    ],
  },
]
const nonakademik2: Metric[] = [
  {
    title: "Kerjasama",
    table: {
      heading: "Kerja sama aktif per mitra dan jenis dokumen",
      label: "Jenis mitra",
      cols: ["MoU", "MoA", "IA"],
      rows: [
        ["Industri", 3, 5, 3],
        ["Pemerintah", 2, 1, 1],
        ["PT dalam negeri", 2, 2, 1],
        ["Luar negeri", 1, 1, 1],
        ["Lainnya", 1, 0, 0],
      ],
    },
    color: "blue",
    total: "24",
    unit: "kerja sama aktif",
    data: [
      { n: "Industri", v: 11 },
      { n: "Pemerintah", v: 4 },
      { n: "PT dalam negeri", v: 5 },
      { n: "Luar negeri", v: 3 },
      { n: "Lainnya", v: 1 },
    ],
    foot: [["Habis tahun ini", "4"]],
  },
]

function MetricCard({ m }: { m: Metric }) {
  const [kind, setKind] = useState<Kind>(m.def ?? "donut")
  const [open, setOpen] = useState(false)
  const colors = palettes[m.color]
  const total = m.data.reduce((a, b) => a + b.v, 0)
  const circular = kind === "donut" || kind === "pie"
  return (
    <article
      className="flex flex-col rounded-2xl border border-slate-200 border-t-4 bg-surface p-5 shadow-sm transition hover:shadow-md"
      style={{ borderTopColor: colors[0] }}
    >
      <h3 className="flex items-center gap-2 font-bold" title={m.title}>
        <span
          className="h-2.5 w-2.5 shrink-0 rounded-full"
          style={{ background: colors[0] }}
        />
        <span className="truncate">{m.title}</span>
      </h3>
      <div className="mt-3 flex h-7 items-center justify-between gap-2">
        {m.trend ? (
          <span
            className={`inline-flex shrink-0 rounded-full px-2 py-0.5 text-xs font-bold ${
              m.trend.up
                ? "bg-emerald-50 text-emerald-700"
                : "bg-rose-50 text-rose-600"
            }`}
          >
            {m.trend.up ? "▲" : "▼"} {m.trend.v}
          </span>
        ) : (
          <span />
        )}
        <select
          value={kind}
          onChange={(e) => setKind(e.target.value as Kind)}
          title="Ubah visualisasi"
          className="rounded-md border border-slate-200 bg-slate-50 px-1.5 py-1 text-[11px] font-medium text-slate-600 outline-none hover:border-sky-300"
        >
          {kinds.map(([k, l]) => (
            <option key={k} value={k}>
              {l}
            </option>
          ))}
        </select>
      </div>
      <div className="relative mt-3 h-48">
        <Chart kind={kind} data={m.data} colors={colors} />
        {kind === "donut" && (
          <div className="pointer-events-none absolute inset-0 grid place-items-center text-center">
            <div>
              <p className="text-3xl font-extrabold leading-none">{m.total}</p>
              <p className="mt-1 text-xs text-slate-500">{m.unit}</p>
            </div>
          </div>
        )}
      </div>
      {!circular && (
        <p className="mt-2 text-xs text-slate-500">
          <span className="text-lg font-extrabold text-slate-900">
            {m.total}
          </span>{" "}
          {m.unit}
        </p>
      )}
      <ul className="mt-4 space-y-2 text-[13px]">
        {m.data.map((d, i) => (
          <li key={d.n} className="flex items-center gap-2">
            <span
              className="h-2.5 w-2.5 shrink-0 rounded-sm"
              style={{ background: colors[i % colors.length] }}
            />
            <span
              className="min-w-0 flex-1 truncate text-slate-600"
              title={d.n}
            >
              {d.n}
            </span>
            <span className="shrink-0 font-mono text-xs text-slate-800">
              {fmt(d.v, total)}
            </span>
          </li>
        ))}
      </ul>
      <div className="mt-auto pt-4">
        {m.foot && (
          <div className="mb-4 grid grid-cols-2 gap-3 border-t border-slate-100 pt-3 text-[11px] leading-snug text-slate-500">
            {m.foot.map(([l, v], i) => (
              <div key={l} className={`min-w-0 ${i ? "text-right" : ""}`}>
                {l}
                <p className="text-sm font-bold text-slate-900">{v}</p>
              </div>
            ))}
          </div>
        )}
        <button
          onClick={() => setOpen(true)}
          className="w-full rounded-xl border py-2.5 text-sm font-bold transition hover:bg-slate-50"
          style={{ borderColor: colors[1], color: colors[3] }}
        >
          Lihat rincian agregat ↗
        </button>
      </div>
      {open && <Detail m={m} colors={colors} onClose={() => setOpen(false)} />}
    </article>
  )
}

const nf = (x: number) => x.toLocaleString("id-ID")

function Detail({
  m,
  colors,
  onClose,
}: {
  m: Metric
  colors: string[]
  onClose: () => void
}) {
  const t = m.table
  const rows = t.rows.map(([n, ...vals]) => ({
    n,
    vals,
    total: vals.reduce((a, b) => a + b, 0),
  }))
  const grand = rows.reduce((a, r) => a + r.total, 0)
  const colSum = t.cols.map((_, i) => rows.reduce((a, r) => a + r.vals[i], 0))
  const download = () => {
    const csv = [
      [t.label, ...t.cols, "Total", "Persentase"].join(","),
      ...rows.map((r) =>
        [r.n, ...r.vals, r.total, fmt(r.total, grand)].join(","),
      ),
      ["Total", ...colSum, grand, "100%"].join(","),
    ].join("\n")
    const a = document.createElement("a")
    a.href = URL.createObjectURL(new Blob([csv], { type: "text/csv" }))
    a.download = `${m.title}.csv`
    a.click()
  }
  const th = "px-4 py-3 text-right font-semibold"
  return (
    <div
      className="fixed inset-0 z-50 grid place-items-center bg-slate-900/40 p-3 backdrop-blur-sm sm:p-6"
      onClick={onClose}
    >
      <div
        className="flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl border-t-4 bg-white shadow-2xl"
        style={{ borderTopColor: colors[0] }}
        onClick={(e) => e.stopPropagation()}
      >
        <div className="flex items-start justify-between gap-6 border-b border-slate-200 px-5 pt-6 pb-5 sm:px-8">
          <div>
            <p className="flex items-center gap-2 text-sm text-slate-500">
              <span
                className="h-2.5 w-2.5 rounded-full"
                style={{ background: colors[0] }}
              />
              Dashboard › {m.title}
            </p>
            <h2 className="mt-2 text-2xl font-extrabold tracking-tight">
              Rincian {m.title}
            </h2>
            <p className="mt-1 flex items-baseline gap-2">
              <span className="text-4xl font-extrabold">{m.total}</span>
              <span className="text-sm text-slate-500">{m.unit}</span>
            </p>
          </div>
          <button
            onClick={onClose}
            aria-label="Tutup"
            className="grid h-10 w-10 shrink-0 place-items-center rounded-xl border border-slate-300 text-xl text-slate-600 hover:bg-slate-50"
          >
            ×
          </button>
        </div>
        <div className="overflow-y-auto px-5 py-6 sm:px-8">
          <h3 className="mb-3 font-bold">{t.heading}</h3>
          <div className="overflow-x-auto rounded-xl border border-slate-200">
            <table className="w-full text-sm">
              <thead className="bg-surface-strong text-xs tracking-wide text-slate-500 uppercase">
                <tr>
                  <th className="px-4 py-3 text-left font-semibold">
                    {t.label}
                  </th>
                  {t.cols.map((c) => (
                    <th key={c} className={th}>
                      {c}
                    </th>
                  ))}
                  <th className={th}>Total</th>
                  <th className={th}>%</th>
                </tr>
              </thead>
              <tbody>
                {rows.map((r, i) => (
                  <tr
                    key={r.n}
                    className="border-t border-slate-100 hover:bg-sky-50/50"
                  >
                    <td className="px-4 py-3">
                      <span className="flex items-center gap-2.5">
                        <span
                          className="h-2.5 w-2.5 rounded-sm"
                          style={{ background: colors[i % colors.length] }}
                        />
                        {r.n}
                      </span>
                    </td>
                    {r.vals.map((v, j) => (
                      <td
                        key={j}
                        className="px-4 text-right tabular-nums text-slate-600"
                      >
                        {nf(v)}
                      </td>
                    ))}
                    <td className="px-4 text-right font-bold tabular-nums">
                      {nf(r.total)}
                    </td>
                    <td className="px-4 text-right tabular-nums text-slate-500">
                      {fmt(r.total, grand)}
                    </td>
                  </tr>
                ))}
              </tbody>
              <tfoot>
                <tr className="border-t-2 border-slate-200 bg-slate-50 font-bold">
                  <td className="px-4 py-3">Total</td>
                  {colSum.map((v, j) => (
                    <td key={j} className="px-4 text-right tabular-nums">
                      {nf(v)}
                    </td>
                  ))}
                  <td
                    className="px-4 text-right tabular-nums"
                    style={{ color: colors[0] }}
                  >
                    {nf(grand)}
                  </td>
                  <td className="px-4 text-right">100%</td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
        <div className="flex flex-wrap items-center justify-between gap-x-4 gap-y-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:px-8">
          <p className="text-sm text-slate-500">
            Sumber: Prodi Sistem Informasi UNUSA
          </p>
          <div className="ml-auto flex shrink-0 gap-3">
            <button
              onClick={download}
              className="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-100"
            >
              Unduh CSV
            </button>
            <button
              onClick={onClose}
              className="rounded-xl bg-blue-700 px-5 py-2.5 text-sm font-bold text-white hover:bg-blue-800"
            >
              Tutup
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}

function Group({
  code,
  title,
  items,
  cols = 4,
  className = "",
}: {
  code: string
  title: string
  items: Metric[]
  cols?: 1 | 3 | 4
  className?: string
}) {
  return (
    <div className={`mt-8 flex min-w-0 flex-col ${className}`}>
      {/* Tinggi minimum menjaga kartu tetap sejajar saat dua grup berdampingan. */}
      <div className="mb-4 flex items-center gap-3 text-sm lg:min-h-10">
        <span className="font-mono font-bold text-sky-600">{code}</span>
        <span className="font-semibold">{title}</span>
        <span className="h-px min-w-4 flex-1 bg-slate-200" />
        <span className="shrink-0 text-slate-400">{items.length} kategori</span>
      </div>
      <Reveal
        stagger
        className={`grid flex-1 grid-cols-1 gap-5 sm:grid-cols-2 ${
          { 1: "lg:grid-cols-1", 3: "lg:grid-cols-3", 4: "lg:grid-cols-4" }[
            cols
          ]
        }`}
      >
        {items.map((m) => (
          <MetricCard key={m.title} m={m} />
        ))}
      </Reveal>
    </div>
  )
}

// Kartu visualisasi yang dibuat Wali Data; pengunjung dapat memilih parameter kolom nilai dan jenis grafik.
function VizCard({ v }: { v: Viz }) {
  const { s } = useStore()
  const d = s.datasets.find((x) => x.id === v.datasetId)
  const [kind, setKind] = useState<Kind>(v.kind)
  const [col, setCol] = useState(v.valueCol)
  if (!d) return null
  const colors = palettes[v.color] ?? palettes.sky
  const data = series(d.rows, v.labelCol, col)
  const total = data.reduce((a, b) => a + b.v, 0)
  const nums = numericCols(d.rows, d.cols).filter((i) => i !== v.labelCol)
  return (
    <article
      className="flex flex-col rounded-2xl border border-t-4 border-slate-200 bg-surface p-5 shadow-sm"
      style={{ borderTopColor: colors[0] }}
    >
      <h3 className="font-bold text-slate-900">{v.title}</h3>
      <p className="mt-0.5 truncate text-xs text-slate-500" title={d.title}>
        Sumber: {d.title} · {d.tahun}
      </p>
      <div className="mt-3 grid grid-cols-2 gap-2">
        <select
          value={col}
          onChange={(e) => setCol(Number(e.target.value))}
          aria-label="Parameter nilai"
          className="rounded-md border border-slate-200 bg-slate-50 px-1.5 py-1 text-[11px] font-medium text-slate-600 outline-none"
        >
          {nums.map((i) => (
            <option key={i} value={i}>
              {d.cols[i]}
            </option>
          ))}
        </select>
        <select
          value={kind}
          onChange={(e) => setKind(e.target.value as Kind)}
          aria-label="Jenis grafik"
          className="rounded-md border border-slate-200 bg-slate-50 px-1.5 py-1 text-[11px] font-medium text-slate-600 outline-none"
        >
          {kinds.map(([k, l]) => (
            <option key={k} value={k}>
              {l}
            </option>
          ))}
        </select>
      </div>
      <div className="relative mt-4 h-44">
        <Chart kind={kind} data={data} colors={colors} />
        {kind === "donut" && (
          <div className="pointer-events-none absolute inset-0 grid place-items-center text-center">
            <div>
              <p className="text-2xl font-extrabold leading-none">
                {total.toLocaleString("id-ID")}
              </p>
              <p className="mt-1 text-[11px] text-slate-500">{d.cols[col]}</p>
            </div>
          </div>
        )}
      </div>
      <ul className="mt-4 space-y-1.5 text-[13px]">
        {data.slice(0, 6).map((x, i) => (
          <li key={x.n} className="flex items-center gap-2">
            <span
              className="h-2.5 w-2.5 shrink-0 rounded-sm"
              style={{ background: colors[i % colors.length] }}
            />
            <span className="min-w-0 flex-1 truncate text-slate-600">
              {x.n}
            </span>
            <span className="font-mono text-xs text-slate-800">
              {x.v.toLocaleString("id-ID")}
            </span>
          </li>
        ))}
      </ul>
    </article>
  )
}

function Banner({
  letter,
  title,
  desc,
}: {
  letter: string
  title: string
  desc: string
}) {
  return (
    <div className="mt-12 flex flex-wrap items-center justify-between gap-2 rounded-2xl border-l-4 border-sky-500 bg-gradient-to-r from-sky-100 via-sky-50 to-white px-6 py-5">
      <h2 className="flex items-center gap-3 text-2xl font-extrabold tracking-tight">
        <span className="grid h-9 w-9 place-items-center rounded-lg bg-sky-600 text-sm text-white">
          {letter}
        </span>
        {title}
      </h2>
      <p className="text-sm text-slate-500">{desc}</p>
    </div>
  )
}

export default function Dashboard() {
  const { s, t, user } = useStore()
  const [tahun, setTahun] = useState("all")
  const [kategori, setKategori] = useState("all")
  const privileged = user?.role === "wali" || user?.role === "admin"
  // Visualisasi dari dataset private tidak ditampilkan kepada publik.
  const shown = s.viz.filter(
    (v) =>
      v.show &&
      (privileged ||
        s.datasets.find((d) => d.id === v.datasetId)?.access === "public"),
  )
  const years = [
    ...new Set(
      shown
        .map((v) => s.datasets.find((d) => d.id === v.datasetId)?.tahun)
        .filter(Boolean),
    ),
  ].sort((a, b) => b! - a!)
  const cats = s.categories.filter((c) =>
    shown.some(
      (v) => s.datasets.find((d) => d.id === v.datasetId)?.kategori === c.code,
    ),
  )
  const list = shown.filter((v) => {
    const d = s.datasets.find((x) => x.id === v.datasetId)
    return (
      (tahun === "all" || String(d?.tahun) === tahun) &&
      (kategori === "all" || d?.kategori === kategori)
    )
  })
  const selectCls =
    "h-10 min-w-0 rounded-xl border border-white/30 bg-white/15 px-3 text-sm font-medium text-white outline-none [&>option]:text-slate-900"
  return (
    <main className="mx-auto max-w-[1200px] px-4 py-10 sm:px-8">
      <div className="flex flex-wrap items-end justify-between gap-4">
        <div>
          <p className="text-sm text-slate-500">
            {t("Beranda", "Home")} <span className="mx-1">/</span>{" "}
            <span className="text-sky-600">Dashboard</span>
          </p>
          <h1 className="mt-1 text-[28px] font-extrabold tracking-tight">
            Dashboard Sistem Informasi
          </h1>
          <p className="mt-1 text-sm text-slate-500">
            {t(
              "Universitas Nahdlatul Ulama Surabaya (UNUSA) · Pilih parameter dan jenis visualisasi di setiap kartu",
              "Universitas Nahdlatul Ulama Surabaya (UNUSA) · Choose parameters and chart type on each card",
            )}
          </p>
        </div>
      </div>

      {shown.length > 0 && (
        <section className="mt-8 rounded-2xl bg-gradient-to-br from-benhur via-benhur to-deep p-5 text-white sm:p-6">
          <div className="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div className="flex items-center gap-3">
              <span className="grid h-9 w-9 place-items-center rounded-lg bg-white/10">
                <BarChart3 className="h-5 w-5 text-white" />
              </span>
              <div>
                <h2 className="text-lg font-bold">
                  {t("Visualisasi pilihan prodi", "Featured visualizations")}
                </h2>
                <p className="text-xs text-white/80">
                  {t(
                    "Disusun Wali Data dari data yang telah terkumpul",
                    "Curated by the Data Steward from collected data",
                  )}
                </p>
              </div>
            </div>
            <div className="flex w-full flex-wrap gap-2 sm:w-auto">
              <select
                value={kategori}
                onChange={(e) => setKategori(e.target.value)}
                aria-label={t("Kategori", "Category")}
                className={`${selectCls} flex-1 sm:flex-none`}
              >
                <option value="all">
                  {t("Semua kategori", "All categories")}
                </option>
                {cats.map((c) => (
                  <option key={c.code} value={c.code}>
                    {c.name}
                  </option>
                ))}
              </select>
              <select
                value={tahun}
                onChange={(e) => setTahun(e.target.value)}
                aria-label={t("Tahun", "Year")}
                className={`${selectCls} flex-1 sm:flex-none`}
              >
                <option value="all">{t("Semua tahun", "All years")}</option>
                {years.map((y) => (
                  <option key={y}>{y}</option>
                ))}
              </select>
            </div>
          </div>
          <div className="grid grid-cols-1 gap-5 text-slate-900 sm:grid-cols-2 lg:grid-cols-3">
            {list.map((v) => (
              <VizCard key={v.id} v={v} />
            ))}
          </div>
          {!list.length && (
            <p className="rounded-xl bg-white/10 py-8 text-center text-sm text-white/85">
              {t(
                "Tidak ada visualisasi untuk kategori dan tahun ini.",
                "No visualizations for this category and year.",
              )}
            </p>
          )}
        </section>
      )}

      <Banner
        letter="A"
        title="Akademik"
        desc="Tridharma: pendidikan, penelitian, dan pengabdian beserta luarannya"
      />
      <Group code="A.1" title="Pendidikan dan mahasiswa" items={akademik1} />
      <Group
        code="A.2"
        title="Penelitian, prestasi, dan luaran"
        items={akademik2}
      />
      <Banner
        letter="N"
        title="Non-Akademik"
        desc="Sumber daya dan tata kelola yang menopang tridharma"
      />
      {/* N.1 (3 kartu) dan N.2 (1 kartu) berbagi satu baris 4 kolom di desktop agar tidak ada ruang kosong. */}
      <div className="lg:grid lg:grid-cols-4 lg:gap-5">
        <Group
          className="lg:col-span-3"
          cols={3}
          code="N.1"
          title="Sumber daya"
          items={nonakademik1}
        />
        <Group
          cols={1}
          code="N.2"
          title="Tata kelola dan kemitraan"
          items={nonakademik2}
        />
      </div>
    </main>
  )
}
