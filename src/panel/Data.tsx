import { useState } from "react"
import {
  BarChart3,
  Database,
  Download,
  Eye,
  Pencil,
  Plus,
  Search,
  Table2,
  Trash2,
} from "lucide-react"
import { Chart, kinds, numericCols, palettes, series } from "../charts"
import type { Dataset } from "../datasets"
import { Preview } from "../Dataset"
import {
  fmtDate,
  uid,
  useStore,
  type DataForm,
  type Kind,
  type Viz,
} from "../store"
import {
  AccessBadge,
  Alert,
  Empty,
  Field,
  Modal,
  ModalFoot,
  ModalHead,
  Pager,
  btn,
  downloadCsv,
  inp,
  sel,
  td,
  th,
  usePage,
} from "../ui"
import { Card, EditModal, PanelHead, Toggle, useConfirm, useToast } from "./kit"
import { inspectSubmissions } from "../dataQuality"
import CleanSubmission from "./CleanSubmission"
import type { Submission } from "../store"

// Kolom yang tersedia dari isian formulir: metadata pengirim + setiap pertanyaan.
const sourceCols = (f: DataForm) => [
  { id: "_nama", label: "Nama pengirim" },
  { id: "_nim", label: "NIM/NIP" },
  { id: "_at", label: "Waktu kirim" },
  ...f.fields.map((x) => ({ id: x.id, label: x.label })),
]

// ===== Data terkumpul =====
export function DataTerkumpul({ arg }: { arg?: string }) {
  const { s, set, user } = useStore()
  const toast = useToast()
  const [confirmEl, ask] = useConfirm()
  const [kategori, setKategori] = useState("all")
  const [tahun, setTahun] = useState("all")
  const forms = s.forms.filter(
    (f) =>
      (kategori === "all" || f.kategori === kategori) &&
      (tahun === "all" || String(f.tahun) === tahun),
  )
  const [formId, setFormId] = useState(arg ?? s.forms[0]?.id ?? "")
  const f = forms.find((x) => x.id === formId) ?? forms[0]
  const [q, setQ] = useState("")
  const [build, setBuild] = useState(false)
  const [editing, setEditing] = useState<Submission | null>(null)
  const [onlyIssues, setOnlyIssues] = useState(false)
  const years = [...new Set(s.forms.map((x) => x.tahun))].sort((a, b) => b - a)
  const subs = f ? s.submissions.filter((x) => x.formId === f.id) : []
  const quality = f ? inspectSubmissions(f, subs) : new Map<string, string[]>()
  const problemCount = [...quality.values()].filter(
    (issues) => issues.length,
  ).length
  const rowOf = (x: typeof subs[number]) => {
    const u = s.users.find((y) => y.id === x.userId)
    return [
      u?.nama ?? "-",
      u?.nim ?? "-",
      fmtDate(x.at, true),
      ...f!.fields.map((fl) => x.values[fl.id] ?? ""),
    ]
  }
  const kw = q.toLowerCase()
  const rows = subs
    .filter((x) => !onlyIssues || !!quality.get(x.id)?.length)
    .filter((x) => !kw || rowOf(x).join(" ").toLowerCase().includes(kw))
    .sort((a, b) => b.at.localeCompare(a.at))
  const pg = usePage(rows, 10)
  const cols = f ? sourceCols(f).map((c) => c.label) : []

  return (
    <>
      <PanelHead
        title="Data Terkumpul"
        desc="Data yang dikirim Produsen Data, tersimpan terpusat dan dikelompokkan berdasarkan kategori dan tahun."
      >
        {f && (
          <button
            onClick={() => downloadCsv(f.title, cols, rows.map(rowOf))}
            className={btn.outline}
          >
            <Download className="h-4 w-4" />
            Unduh CSV
          </button>
        )}
        {f && (
          <button
            onClick={() => setBuild(true)}
            disabled={problemCount > 0 || !subs.length}
            className={btn.primary}
          >
            <Table2 className="h-4 w-4" />
            Jadikan dataset
          </button>
        )}
      </PanelHead>
      <Card className="mb-4 flex flex-wrap items-center gap-2 p-3">
        <select
          value={kategori}
          onChange={(e) => setKategori(e.target.value)}
          className={sel}
          aria-label="Kategori"
        >
          <option value="all">Semua kategori</option>
          {s.categories
            .filter((c) => s.forms.some((x) => x.kategori === c.code))
            .map((c) => (
              <option key={c.code} value={c.code}>
                {c.name}
              </option>
            ))}
        </select>
        <select
          value={tahun}
          onChange={(e) => setTahun(e.target.value)}
          className={sel}
          aria-label="Tahun"
        >
          <option value="all">Semua tahun</option>
          {years.map((y) => (
            <option key={y}>{y}</option>
          ))}
        </select>
        <select
          value={f?.id ?? ""}
          onChange={(e) => setFormId(e.target.value)}
          className={`${sel} min-w-0 flex-1`}
          aria-label="Formulir"
        >
          {forms.map((x) => (
            <option key={x.id} value={x.id}>
              {x.title} ({s.submissions.filter((y) => y.formId === x.id).length}
              )
            </option>
          ))}
        </select>
        <label className="flex h-10 items-center gap-2 rounded-xl border border-slate-200 px-3">
          <Search className="h-4 w-4 text-slate-400" />
          <input
            value={q}
            onChange={(e) => setQ(e.target.value)}
            placeholder="Cari isian…"
            className="w-40 bg-transparent text-sm outline-none"
          />
        </label>
      </Card>
      {f && (
        <Card
          className={`mb-4 flex flex-wrap items-center justify-between gap-3 p-4 ${
            problemCount
              ? "border-red-200 bg-red-50"
              : "border-emerald-200 bg-emerald-50"
          }`}
        >
          <div className="min-w-0">
            <h2 className="font-bold">
              {problemCount
                ? `${problemCount} dari ${subs.length} isian perlu dibersihkan`
                : `${subs.length} isian · tidak ada masalah terdeteksi`}
            </h2>
            <p className="mt-1 text-sm text-slate-600">
              Pemeriksaan data ganda, kolom wajib kosong, nilai tidak valid, dan
              format. Bersihkan sebelum membuat dataset.
            </p>
          </div>
          <label className="flex items-center gap-2 text-sm font-semibold">
            <input
              type="checkbox"
              checked={onlyIssues}
              onChange={(e) => setOnlyIssues(e.target.checked)}
            />
            Hanya data bermasalah
          </label>
        </Card>
      )}
      {!f ? (
        <Empty
          icon={Database}
          title="Tidak ada formulir pada kategori/tahun ini"
        />
      ) : rows.length ? (
        <Card className="overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="bg-surface-strong">
                <tr>
                  {cols.map((c) => (
                    <th key={c} className={`${th} whitespace-nowrap`}>
                      {c}
                    </th>
                  ))}
                  <th className={th}>Kualitas & tindakan</th>
                </tr>
              </thead>
              <tbody>
                {pg.slice.map((x) => (
                  <tr
                    key={x.id}
                    className={`border-t border-slate-100 ${
                      quality.get(x.id)?.length ? "bg-red-50" : "hover:bg-white"
                    }`}
                  >
                    {rowOf(x).map((v, i) => (
                      <td
                        key={i}
                        className={`${td} max-w-[240px] truncate ${
                          i === 0 ? "font-semibold text-slate-900" : ""
                        }`}
                        title={String(v)}
                      >
                        {v || "—"}
                      </td>
                    ))}
                    {user!.role !== "produsen" && (
                      <td className={`${td} text-right`}>
                        <div className="min-w-52 text-left">
                          {quality.get(x.id)?.length ? (
                            <ul className="mb-2 list-inside list-disc text-xs text-red-700">
                              {quality.get(x.id)!.map((issue) => (
                                <li key={issue}>{issue}</li>
                              ))}
                            </ul>
                          ) : (
                            <p className="mb-2 text-xs text-emerald-700">
                              Tidak ada masalah terdeteksi
                            </p>
                          )}
                          {x.cleanedAt && (
                            <p className="mb-2 text-xs text-slate-500">
                              Diperbaiki{" "}
                              {s.users.find((u) => u.id === x.cleanedBy)
                                ?.nama ?? "pengelola"}{" "}
                              · {fmtDate(x.cleanedAt, true)}
                            </p>
                          )}
                        </div>
                        <button
                          onClick={() => setEditing(x)}
                          className={`${btn.icon} mr-2`}
                          aria-label="Perbaiki isian"
                        >
                          <Pencil className="h-4 w-4" />
                        </button>
                        <button
                          onClick={() =>
                            ask(
                              "Isian ini akan dihapus dari basis data.",
                              () => {
                                set((p) => ({
                                  ...p,
                                  submissions: p.submissions.filter(
                                    (y) => y.id !== x.id,
                                  ),
                                }))
                                toast("Isian dihapus.")
                              },
                            )
                          }
                          className={btn.iconDanger}
                          aria-label="Hapus isian"
                        >
                          <Trash2 className="h-4 w-4" />
                        </button>
                      </td>
                    )}
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          <Pager {...pg} />
        </Card>
      ) : (
        <Empty
          icon={Database}
          title={
            onlyIssues
              ? "Tidak ada isian bermasalah yang cocok"
              : q
                ? "Isian tidak ditemukan"
                : "Belum ada isian untuk formulir ini"
          }
        />
      )}
      {build && f && (
        <DatasetBuilder formId={f.id} onClose={() => setBuild(false)} />
      )}
      {editing && f && (
        <CleanSubmission
          form={f}
          submission={editing}
          onClose={() => setEditing(null)}
        />
      )}
      {confirmEl}
    </>
  )
}

// ===== Pembuat dataset dari data terkumpul =====
function DatasetBuilder({
  formId,
  onClose,
}: {
  formId?: string
  onClose: () => void
}) {
  const { s, set } = useStore()
  const toast = useToast()
  const [fid, setFid] = useState(formId ?? s.forms[0]?.id ?? "")
  const f = s.forms.find((x) => x.id === fid)
  const [mode, setMode] = useState<"rinci" | "rekap">(
    f?.fields.some((field) => field.type === "text" || field.type === "select")
      ? "rekap"
      : "rinci",
  )
  const [picked, setPicked] = useState<string[]>(() =>
    f
      ? sourceCols(f)
          .filter((c) => c.id !== "_at")
          .map((c) => c.id)
      : [],
  )
  const groupable =
    f?.fields.filter((x) => x.type === "select" || x.type === "text") ?? []
  const [by, setBy] = useState(groupable[0]?.id ?? "")
  const [m, setM] = useState({
    title: f ? `Rekap ${f.title}` : "",
    desc: f?.desc ?? "",
    kategori: f?.kategori ?? "",
    tahun: String(f?.tahun ?? 2026),
    periode: f?.periode ?? "Ganjil",
    access: "private",
    format: "XLSX",
  })
  const [tried, setTried] = useState(false)

  const changeForm = (id: string) => {
    const nf = s.forms.find((x) => x.id === id)!
    setFid(id)
    setPicked(
      sourceCols(nf)
        .filter((c) => c.id !== "_at")
        .map((c) => c.id),
    )
    setBy(
      nf.fields.find((x) => x.type === "select" || x.type === "text")?.id ?? "",
    )
    setM({
      ...m,
      title: `Rekap ${nf.title}`,
      desc: nf.desc,
      kategori: nf.kategori,
      tahun: String(nf.tahun),
      periode: nf.periode,
    })
  }
  const subs = s.submissions.filter((x) => x.formId === fid)
  const dirty = f
    ? [...inspectSubmissions(f, subs).values()].some((issues) => issues.length)
    : false
  const build = (): {
    cols: string[]
    rows: (string | number)[][]
  } => {
    if (!f) return { cols: [], rows: [] }
    if (mode === "rekap") {
      const fl = f.fields.find((x) => x.id === by)
      const counts = new Map<string, number>()
      subs.forEach((x) => {
        const k = x.values[by] || "(kosong)"
        counts.set(k, (counts.get(k) ?? 0) + 1)
      })
      ;(fl?.options ?? []).forEach((o) => counts.has(o) || counts.set(o, 0))
      return {
        cols: [fl?.label ?? "Kategori", "Jumlah"],
        rows: [...counts].map(([k, v]) => [k, v]),
      }
    }
    const sc = sourceCols(f).filter((c) => picked.includes(c.id))
    return {
      cols: sc.map((c) => c.label),
      rows: subs.map((x) => {
        const u = s.users.find((y) => y.id === x.userId)
        return sc.map((c) =>
          c.id === "_nama"
            ? (u?.nama ?? "-")
            : c.id === "_nim"
              ? (u?.nim ?? "-")
              : c.id === "_at"
                ? fmtDate(x.at, true)
                : (x.values[c.id] ?? ""),
        )
      }),
    }
  }
  const out = build()
  const errs: Record<string, string> = {}
  if (!m.title.trim()) errs.title = "Judul wajib diisi."
  if (!m.kategori) errs.kategori = "Kategori wajib dipilih."
  if (!/^\d{4}$/.test(m.tahun)) errs.tahun = "Tahun 4 digit."
  if (!out.cols.length) errs.cols = "Pilih minimal satu kolom."
  if (mode === "rekap" && !groupable.some((field) => field.id === by))
    errs.quality =
      "Tidak ada kolom pengelompokan yang valid. Pilih bentuk Data rinci atau kolom pengelompokan."
  if (dirty)
    errs.quality =
      "Data sumber belum bersih. Perbaiki melalui menu Data Terkumpul sebelum membuat dataset."
  if (!subs.length)
    errs.quality = "Belum ada isian sumber untuk dibuat menjadi dataset."
  const save = (e: React.FormEvent) => {
    e.preventDefault()
    setTried(true)
    if (Object.keys(errs).length) return
    const d: Dataset = {
      id: uid("DS"),
      title: m.title.trim(),
      desc: m.desc.trim() || `Dibuat dari formulir ${f?.title}.`,
      kategori: m.kategori,
      tahun: Number(m.tahun),
      periode: m.periode as Dataset["periode"],
      access: m.access as Dataset["access"],
      format: m.format,
      updated: fmtDate(new Date().toISOString()),
      cols: out.cols,
      rows: out.rows,
    }
    set((p) => ({ ...p, datasets: [d, ...p.datasets] }))
    toast(
      `Dataset “${d.title}” dibuat (${
        d.access === "public" ? "publik" : "private"
      }).`,
    )
    onClose()
  }
  const e = (k: string) => (tried ? errs[k] : undefined)
  return (
    <Modal wide onClose={onClose}>
      <ModalHead
        kicker="Kelola dataset"
        title="Buat dataset dari data terkumpul"
        desc="Kemas isian formulir menjadi dataset yang siap dilihat dan diunduh."
        onClose={onClose}
      />
      <form noValidate onSubmit={save} className="flex min-h-0 flex-col">
        <div className="grid grid-cols-1 gap-6 overflow-y-auto px-5 py-6 sm:px-8 lg:grid-cols-2">
          {errs.quality && (
            <div className="lg:col-span-2">
              <Alert tone="warn">{errs.quality}</Alert>
            </div>
          )}
          <div className="space-y-5">
            <Field label="Sumber formulir" required>
              <select
                className={inp}
                value={fid}
                onChange={(x) => changeForm(x.target.value)}
              >
                {s.forms.map((x) => (
                  <option key={x.id} value={x.id}>
                    {x.title} (
                    {s.submissions.filter((y) => y.formId === x.id).length}{" "}
                    isian)
                  </option>
                ))}
              </select>
            </Field>
            <div>
              <p className="text-sm font-semibold text-slate-800">
                Bentuk dataset
              </p>
              <div className="mt-1.5 grid grid-cols-2 gap-2">
                {([
                  [
                    "rekap",
                    "Rekap jumlah",
                    "Agregat per nilai, cocok untuk visualisasi",
                  ],
                  ["rinci", "Data rinci", "Satu baris per isian"],
                ] as const).map(([k, l, d]) => (
                  <button
                    type="button"
                    key={k}
                    onClick={() => setMode(k)}
                    className={`rounded-xl border p-3 text-left ${
                      mode === k
                        ? "border-sky-500 bg-sky-100/80"
                        : "border-slate-200 hover:border-sky-300"
                    }`}
                  >
                    <p className="text-sm font-bold">{l}</p>
                    <p className="text-xs text-slate-500">{d}</p>
                  </button>
                ))}
              </div>
            </div>
            {mode === "rekap" ? (
              <Field label="Kelompokkan berdasarkan" required>
                <select
                  className={inp}
                  value={by}
                  onChange={(x) => setBy(x.target.value)}
                >
                  {groupable.map((x) => (
                    <option key={x.id} value={x.id}>
                      {x.label}
                    </option>
                  ))}
                </select>
              </Field>
            ) : (
              f && (
                <div>
                  <p className="text-sm font-semibold text-slate-800">
                    Kolom<span className="text-[#DC3545]">*</span>
                  </p>
                  <div className="mt-1.5 grid grid-cols-2 gap-2">
                    {sourceCols(f).map((c) => (
                      <label
                        key={c.id}
                        className="flex items-center gap-2 text-sm text-slate-600"
                      >
                        <input
                          type="checkbox"
                          className="h-4 w-4 accent-sky-600"
                          checked={picked.includes(c.id)}
                          onChange={(x) =>
                            setPicked(
                              x.target.checked
                                ? [...picked, c.id]
                                : picked.filter((p) => p !== c.id),
                            )
                          }
                        />
                        {c.label}
                      </label>
                    ))}
                  </div>
                  {e("cols") && (
                    <p className="mt-1 text-xs text-[#DC3545]">{e("cols")}</p>
                  )}
                </div>
              )
            )}
            <Field label="Judul dataset" required error={e("title")}>
              <input
                className={inp}
                value={m.title}
                onChange={(x) => setM({ ...m, title: x.target.value })}
              />
            </Field>
            <Field label="Deskripsi">
              <textarea
                rows={2}
                className={inp}
                value={m.desc}
                onChange={(x) => setM({ ...m, desc: x.target.value })}
              />
            </Field>
            <div className="grid grid-cols-2 gap-3">
              <Field label="Kategori" required error={e("kategori")}>
                <select
                  className={inp}
                  value={m.kategori}
                  onChange={(x) => setM({ ...m, kategori: x.target.value })}
                >
                  <option value="">Pilih…</option>
                  {s.categories.map((c) => (
                    <option key={c.code} value={c.code}>
                      {c.name}
                    </option>
                  ))}
                </select>
              </Field>
              <Field label="Tahun" required error={e("tahun")}>
                <input
                  className={inp}
                  value={m.tahun}
                  onChange={(x) => setM({ ...m, tahun: x.target.value })}
                />
              </Field>
              <Field label="Semester">
                <select
                  className={inp}
                  value={m.periode}
                  onChange={(x) =>
                    setM({ ...m, periode: x.target.value as "Ganjil" })
                  }
                >
                  <option>Ganjil</option>
                  <option>Genap</option>
                </select>
              </Field>
              <Field label="Format unduhan utama">
                <select
                  className={inp}
                  value={m.format}
                  onChange={(x) => setM({ ...m, format: x.target.value })}
                >
                  <option>XLSX</option>
                  <option>CSV</option>
                </select>
              </Field>
            </div>
            <Field
              label="Akses"
              required
              hint="Dataset private hanya dapat diakses Wali Data, Admin, dan pemohon yang dikonfirmasi."
            >
              <div className="grid grid-cols-2 gap-2">
                {(["public", "private"] as const).map((a) => (
                  <button
                    type="button"
                    key={a}
                    onClick={() => setM({ ...m, access: a })}
                    className={`flex items-center justify-center rounded-xl border py-2.5 ${
                      m.access === a
                        ? "border-sky-500 bg-sky-100/80"
                        : "border-slate-200"
                    }`}
                  >
                    <AccessBadge access={a} />
                  </button>
                ))}
              </div>
            </Field>
          </div>
          <div className="min-w-0">
            <p className="mb-2 text-sm font-semibold text-slate-800">
              Pratinjau ({out.rows.length} baris)
            </p>
            <div className="overflow-x-auto rounded-xl border border-slate-200">
              <table className="w-full">
                <thead className="bg-surface-strong">
                  <tr>
                    {out.cols.map((c) => (
                      <th key={c} className={`${th} whitespace-nowrap`}>
                        {c}
                      </th>
                    ))}
                  </tr>
                </thead>
                <tbody>
                  {out.rows.slice(0, 8).map((r, i) => (
                    <tr key={i} className="border-t border-slate-100">
                      {r.map((v, j) => (
                        <td key={j} className={`${td} max-w-[180px] truncate`}>
                          {v}
                        </td>
                      ))}
                    </tr>
                  ))}
                </tbody>
              </table>
              {!out.rows.length && (
                <p className="py-6 text-center text-sm text-slate-500">
                  Belum ada isian.
                </p>
              )}
            </div>
            {mode === "rekap" && out.rows.length > 0 && (
              <div className="mt-4 h-48 rounded-xl border border-slate-200 p-3">
                <Chart
                  kind="bar"
                  data={series(out.rows, 0, 1)}
                  colors={palettes.sky}
                />
              </div>
            )}
          </div>
        </div>
        <ModalFoot>
          <button type="button" onClick={onClose} className={btn.outline}>
            Batal
          </button>
          <button disabled={!!errs.quality} className={btn.primary}>
            Buat dataset
          </button>
        </ModalFoot>
      </form>
    </Modal>
  )
}

// ===== Kategori =====
export function Kategori() {
  const { s, set, user } = useStore()
  const toast = useToast()
  const [confirmEl, ask] = useConfirm()
  const [edit, setEdit] = useState<{
    code: string
    name: string
    desc: string
    _old?: string
  } | null>(null)
  const used = (code: string) =>
    s.datasets.filter((d) => d.kategori === code).length +
    s.forms.filter((f) => f.kategori === code).length
  return (
    <>
      <PanelHead
        title="Kategori Data"
        desc="Kategori untuk mengelompokkan formulir, data, dan dataset. Kategori baru dapat ditambahkan tanpa mengubah kode program."
      >
        <button
          onClick={() => setEdit({ code: "", name: "", desc: "" })}
          className={btn.primary}
        >
          <Plus className="h-4 w-4" />
          Tambah kategori
        </button>
      </PanelHead>
      <Card className="overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full min-w-[640px]">
            <thead className="bg-surface-strong">
              <tr>
                <th className={th}>Kode</th>
                <th className={th}>Nama</th>
                <th className={th}>Deskripsi</th>
                <th className={th}>Formulir</th>
                <th className={th}>Dataset</th>
                <th className={th} />
              </tr>
            </thead>
            <tbody>
              {s.categories.map((c) => (
                <tr
                  key={c.code}
                  className="border-t border-slate-200/70 hover:bg-white"
                >
                  <td
                    className={`${td} font-mono text-xs font-bold text-sky-700`}
                  >
                    {c.code}
                  </td>
                  <td className={`${td} font-semibold text-slate-900`}>
                    {c.name}
                  </td>
                  <td className={`${td} text-slate-500`}>{c.desc}</td>
                  <td className={td}>
                    {s.forms.filter((f) => f.kategori === c.code).length}
                  </td>
                  <td className={td}>
                    {s.datasets.filter((d) => d.kategori === c.code).length}
                  </td>
                  <td className={td}>
                    <div className="flex justify-end gap-1.5">
                      <button
                        onClick={() => setEdit({ ...c, _old: c.code })}
                        className={btn.icon}
                        aria-label="Ubah"
                      >
                        <Pencil className="h-4 w-4" />
                      </button>
                      {user!.role === "admin" && (
                        <button
                          onClick={() =>
                            used(c.code)
                              ? toast(
                                  `Kategori masih dipakai ${used(c.code)} formulir/dataset. Pindahkan dulu sebelum menghapus.`,
                                  "error",
                                )
                              : ask(
                                  `Kategori “${c.name}” akan dihapus.`,
                                  () => {
                                    set((p) => ({
                                      ...p,
                                      categories: p.categories.filter(
                                        (x) => x.code !== c.code,
                                      ),
                                    }))
                                    toast("Kategori dihapus.")
                                  },
                                )
                          }
                          className={btn.iconDanger}
                          aria-label="Hapus"
                        >
                          <Trash2 className="h-4 w-4" />
                        </button>
                      )}
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </Card>
      {edit && (
        <EditModal
          title={edit._old ? "Ubah kategori" : "Tambah kategori"}
          kicker="Kategori data"
          value={edit}
          onClose={() => setEdit(null)}
          specs={[
            {
              k: "code",
              label: "Kode",
              required: true,
              hint: "Contoh: KT-MHS-07",
              check: (v) =>
                !/^[A-Z0-9-]+$/.test(v)
                  ? "Gunakan huruf kapital, angka, dan tanda hubung."
                  : s.categories.some(
                        (c) => c.code === v && c.code !== edit._old,
                      )
                    ? "Kode sudah dipakai."
                    : undefined,
            },
            { k: "name", label: "Nama kategori", required: true },
            { k: "desc", label: "Deskripsi", type: "textarea" },
          ]}
          onSave={(v) => {
            const c = {
              code: String(v.code).trim(),
              name: String(v.name).trim(),
              desc: String(v.desc).trim(),
            }
            set((p) => ({
              ...p,
              categories: edit._old
                ? p.categories.map((x) => (x.code === edit._old ? c : x))
                : [...p.categories, c],
              datasets: p.datasets.map((d) =>
                d.kategori === edit._old ? { ...d, kategori: c.code } : d,
              ),
              forms: p.forms.map((f) =>
                f.kategori === edit._old ? { ...f, kategori: c.code } : f,
              ),
            }))
            toast("Kategori disimpan.")
          }}
        />
      )}
      {confirmEl}
    </>
  )
}

// ===== Kelola dataset =====
export function KelolaDataset() {
  const { s, set, user } = useStore()
  const toast = useToast()
  const [confirmEl, ask] = useConfirm()
  const [q, setQ] = useState("")
  const [kategori, setKategori] = useState("all")
  const [tahun, setTahun] = useState("all")
  const [view, setView] = useState<Dataset | null>(null)
  const [edit, setEdit] = useState<Dataset | null>(null)
  const [build, setBuild] = useState(false)
  const years = [...new Set(s.datasets.map((d) => d.tahun))].sort(
    (a, b) => b - a,
  )
  const rows = s.datasets.filter(
    (d) =>
      (kategori === "all" || d.kategori === kategori) &&
      (tahun === "all" || String(d.tahun) === tahun) &&
      d.title.toLowerCase().includes(q.toLowerCase()),
  )
  const pg = usePage(rows, 10)
  const setAccess = (d: Dataset, pub: boolean) => {
    set((p) => ({
      ...p,
      datasets: p.datasets.map((x) =>
        x.id === d.id ? { ...x, access: pub ? "public" : "private" } : x,
      ),
    }))
    toast(`“${d.title}” kini ${pub ? "publik" : "private"}.`, "info")
  }
  return (
    <>
      <PanelHead
        title="Kelola Dataset"
        desc="Sebarluaskan data dengan menetapkan dataset sebagai publik atau private."
      >
        <button onClick={() => setBuild(true)} className={btn.primary}>
          <Plus className="h-4 w-4" />
          Buat dataset
        </button>
      </PanelHead>
      <Card className="mb-4 flex flex-wrap items-center gap-2 p-3">
        <label className="flex h-10 min-w-0 flex-1 items-center gap-2 rounded-xl border border-slate-200 px-3">
          <Search className="h-4 w-4 text-slate-400" />
          <input
            value={q}
            onChange={(e) => setQ(e.target.value)}
            placeholder="Cari dataset…"
            className="min-w-0 flex-1 bg-transparent text-sm outline-none"
          />
        </label>
        <select
          value={kategori}
          onChange={(e) => setKategori(e.target.value)}
          className={sel}
        >
          <option value="all">Semua kategori</option>
          {s.categories.map((c) => (
            <option key={c.code} value={c.code}>
              {c.name}
            </option>
          ))}
        </select>
        <select
          value={tahun}
          onChange={(e) => setTahun(e.target.value)}
          className={sel}
        >
          <option value="all">Semua tahun</option>
          {years.map((y) => (
            <option key={y}>{y}</option>
          ))}
        </select>
      </Card>
      <Card className="overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full min-w-[760px]">
            <thead className="bg-surface-strong">
              <tr>
                <th className={th}>Dataset</th>
                <th className={th}>Periode</th>
                <th className={th}>Baris</th>
                <th className={th}>Publik</th>
                <th className={`${th} text-right`}>Aksi</th>
              </tr>
            </thead>
            <tbody>
              {pg.slice.map((d) => (
                <tr
                  key={d.id}
                  className="border-t border-slate-200/70 hover:bg-white"
                >
                  <td className={td}>
                    <p className="font-semibold text-slate-900">{d.title}</p>
                    <p className="text-xs text-slate-400">
                      {s.categories.find((c) => c.code === d.kategori)?.name} ·{" "}
                      {d.format} · diperbarui {d.updated}
                    </p>
                  </td>
                  <td className={`${td} whitespace-nowrap`}>
                    {d.tahun} · {d.periode}
                  </td>
                  <td className={td}>{d.rows.length}</td>
                  <td className={td}>
                    <div className="flex items-center gap-2">
                      <Toggle
                        on={d.access === "public"}
                        onChange={(v) => setAccess(d, v)}
                        label="Publik"
                      />
                      <AccessBadge access={d.access} />
                    </div>
                  </td>
                  <td className={td}>
                    <div className="flex justify-end gap-1.5">
                      <button
                        onClick={() => setView(d)}
                        className={btn.icon}
                        aria-label="Lihat"
                      >
                        <Eye className="h-4 w-4" />
                      </button>
                      <button
                        onClick={() => setEdit(d)}
                        className={btn.icon}
                        aria-label="Ubah"
                      >
                        <Pencil className="h-4 w-4" />
                      </button>
                      {user!.role === "admin" && (
                        <button
                          onClick={() =>
                            ask(
                              `Dataset “${d.title}” beserta visualisasi terkait akan dihapus.`,
                              () => {
                                set((p) => ({
                                  ...p,
                                  datasets: p.datasets.filter(
                                    (x) => x.id !== d.id,
                                  ),
                                  viz: p.viz.filter(
                                    (v) => v.datasetId !== d.id,
                                  ),
                                }))
                                toast("Dataset dihapus.")
                              },
                            )
                          }
                          className={btn.iconDanger}
                          aria-label="Hapus"
                        >
                          <Trash2 className="h-4 w-4" />
                        </button>
                      )}
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <Pager {...pg} />
      </Card>
      {view && <Preview d={view} onClose={() => setView(null)} />}
      {build && <DatasetBuilder onClose={() => setBuild(false)} />}
      {edit && (
        <EditModal
          title="Ubah dataset"
          kicker={edit.id}
          value={{
            title: edit.title,
            desc: edit.desc,
            kategori: edit.kategori,
            tahun: String(edit.tahun),
            periode: edit.periode,
            access: edit.access,
            format: edit.format,
          }}
          onClose={() => setEdit(null)}
          specs={[
            { k: "title", label: "Judul", required: true, full: true },
            { k: "desc", label: "Deskripsi", type: "textarea" },
            {
              k: "kategori",
              label: "Kategori",
              type: "select",
              required: true,
              options: s.categories.map((c) => [c.code, c.name]),
            },
            {
              k: "tahun",
              label: "Tahun",
              required: true,
              check: (v) => (/^\d{4}$/.test(v) ? undefined : "Tahun 4 digit."),
            },
            {
              k: "periode",
              label: "Semester",
              type: "select",
              required: true,
              options: ["Ganjil", "Genap"],
            },
            {
              k: "access",
              label: "Akses",
              type: "select",
              required: true,
              options: [
                ["public", "Publik"],
                ["private", "Private"],
              ],
            },
            {
              k: "format",
              label: "Format",
              type: "select",
              required: true,
              options: ["CSV", "XLSX"],
            },
          ]}
          onSave={(v) => {
            set((p) => ({
              ...p,
              datasets: p.datasets.map((x) =>
                x.id === edit.id
                  ? {
                      ...x,
                      title: String(v.title),
                      desc: String(v.desc),
                      kategori: String(v.kategori),
                      tahun: Number(v.tahun),
                      periode: v.periode as Dataset["periode"],
                      access: v.access as Dataset["access"],
                      format: String(v.format),
                      updated: fmtDate(new Date().toISOString()),
                    }
                  : x,
              ),
            }))
            toast("Dataset diperbarui.")
          }}
        />
      )}
      {confirmEl}
    </>
  )
}

// ===== Visualisasi dashboard =====
type VizBuilderProps = {
  v?: Viz
  onClose: () => void
}

function VizBuilder({ v, onClose }: VizBuilderProps) {
  const { s, set, user } = useStore()
  const toast = useToast()
  const usable = s.datasets.filter((d) => numericCols(d.rows, d.cols).length)
  const [x, setX] = useState<Omit<Viz, "id" | "createdBy" | "createdAt">>(
    v ?? {
      title: "",
      datasetId: usable[0]?.id ?? "",
      labelCol: 0,
      valueCol:
        numericCols(usable[0]?.rows ?? [], usable[0]?.cols ?? []).slice(
          -1,
        )[0] ?? 0,
      kind: "bar",
      color: "sky",
      show: true,
    },
  )
  const [tried, setTried] = useState(false)
  const d = s.datasets.find((y) => y.id === x.datasetId)
  const nums = d ? numericCols(d.rows, d.cols) : []
  const pickDs = (id: string) => {
    const nd = s.datasets.find((y) => y.id === id)!
    setX({
      ...x,
      datasetId: id,
      labelCol: 0,
      valueCol: numericCols(nd.rows, nd.cols).slice(-1)[0] ?? 0,
    })
  }
  const save = (e: React.FormEvent) => {
    e.preventDefault()
    setTried(true)
    if (!x.title.trim() || !d || !nums.includes(x.valueCol)) return
    const nv: Viz = {
      ...x,
      title: x.title.trim(),
      id: v?.id ?? uid("V"),
      createdBy: v?.createdBy ?? user!.id,
      createdAt: v?.createdAt ?? new Date().toISOString(),
    }
    set((p) => ({
      ...p,
      viz: v ? p.viz.map((y) => (y.id === v.id ? nv : y)) : [...p.viz, nv],
    }))
    toast(
      x.show
        ? "Visualisasi disimpan dan tampil di Dashboard."
        : "Visualisasi disimpan (tidak ditampilkan).",
    )
    onClose()
  }
  return (
    <Modal wide onClose={onClose}>
      <ModalHead
        kicker="Visualisasi dashboard"
        title={v ? "Ubah visualisasi" : "Buat visualisasi"}
        desc="Pilih data yang ingin ditampilkan di Dashboard dalam bentuk angka atau grafik."
        onClose={onClose}
      />
      <form noValidate onSubmit={save} className="flex min-h-0 flex-col">
        <div className="grid grid-cols-1 gap-6 overflow-y-auto px-5 py-6 sm:px-8 lg:grid-cols-[1fr_1.1fr]">
          <div className="space-y-4">
            <Field
              label="Judul visualisasi"
              required
              error={
                tried && !x.title.trim() ? "Judul wajib diisi." : undefined
              }
            >
              <input
                className={inp}
                value={x.title}
                onChange={(e) => setX({ ...x, title: e.target.value })}
                placeholder="Contoh: Penerima beasiswa per tahun"
              />
            </Field>
            <Field label="Sumber dataset" required>
              <select
                className={inp}
                value={x.datasetId}
                onChange={(e) => pickDs(e.target.value)}
              >
                {usable.map((y) => (
                  <option key={y.id} value={y.id}>
                    {y.title}
                    {y.access === "private" ? " (private)" : ""}
                  </option>
                ))}
              </select>
            </Field>
            {(!d || !nums.includes(x.valueCol)) && (
              <Alert tone="warn">
                Pilih dataset dengan kolom angka yang valid untuk membuat
                visualisasi.
              </Alert>
            )}
            <div className="grid grid-cols-2 gap-3">
              <Field label="Label" required>
                <select
                  className={inp}
                  value={x.labelCol}
                  onChange={(e) =>
                    setX({ ...x, labelCol: Number(e.target.value) })
                  }
                >
                  {d?.cols.map((c, i) => (
                    <option key={c} value={i}>
                      {c}
                    </option>
                  ))}
                </select>
              </Field>
              <Field label="Nilai" required>
                <select
                  className={inp}
                  value={x.valueCol}
                  onChange={(e) =>
                    setX({ ...x, valueCol: Number(e.target.value) })
                  }
                >
                  {nums.map((i) => (
                    <option key={i} value={i}>
                      {d!.cols[i]}
                    </option>
                  ))}
                </select>
              </Field>
            </div>
            <Field label="Jenis grafik default">
              <div className="grid grid-cols-3 gap-1.5">
                {kinds.map(([k, l]) => (
                  <button
                    type="button"
                    key={k}
                    onClick={() => setX({ ...x, kind: k as Kind })}
                    className={`rounded-lg border py-1.5 text-xs font-semibold ${
                      x.kind === k
                        ? "border-sky-500 bg-sky-100/80 text-sky-700"
                        : "border-slate-200 text-slate-600"
                    }`}
                  >
                    {l}
                  </button>
                ))}
              </div>
            </Field>
            <Field label="Warna">
              <div className="flex gap-2">
                {Object.entries(palettes).map(([k, p]) => (
                  <button
                    type="button"
                    key={k}
                    onClick={() => setX({ ...x, color: k })}
                    aria-label={k}
                    className={`h-7 w-7 rounded-full ring-offset-2 ${
                      x.color === k ? "ring-2 ring-slate-400" : ""
                    }`}
                    style={{ background: p[0] }}
                  />
                ))}
              </div>
            </Field>
            <label className="flex items-center gap-3 text-sm font-semibold">
              <Toggle
                on={x.show}
                onChange={(b) => setX({ ...x, show: b })}
                label="Tampilkan di Dashboard"
              />
              Tampilkan di Dashboard publik
            </label>
            {d?.access === "private" && x.show && (
              <p className="text-xs text-amber-700">
                Catatan: dataset sumber berstatus private, sehingga visualisasi
                ini hanya terlihat oleh Wali Data dan Admin. Jadikan dataset
                publik agar tampil di Dashboard publik.
              </p>
            )}
          </div>
          <div className="rounded-2xl border border-slate-200 p-5">
            <p className="font-bold">{x.title || "Judul visualisasi"}</p>
            <p className="text-xs text-slate-500">{d?.title}</p>
            <div className="mt-4 h-64">
              {d && (
                <Chart
                  kind={x.kind}
                  data={series(d.rows, x.labelCol, x.valueCol)}
                  colors={palettes[x.color]}
                />
              )}
            </div>
          </div>
        </div>
        <ModalFoot>
          <button type="button" onClick={onClose} className={btn.outline}>
            Batal
          </button>
          <button className={btn.primary}>Simpan visualisasi</button>
        </ModalFoot>
      </form>
    </Modal>
  )
}

export function Visualisasi() {
  const { s, set, user } = useStore()
  const toast = useToast()
  const [confirmEl, ask] = useConfirm()
  const [edit, setEdit] = useState<Viz | "new" | null>(null)
  return (
    <>
      <PanelHead
        title="Visualisasi Dashboard"
        desc="Tentukan data yang tampil di Dashboard. Pengunjung tetap dapat mengubah parameter kolom dan jenis grafik."
      >
        <button onClick={() => setEdit("new")} className={btn.primary}>
          <Plus className="h-4 w-4" />
          Buat visualisasi
        </button>
      </PanelHead>
      {s.viz.length ? (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
          {s.viz.map((v) => {
            const d = s.datasets.find((y) => y.id === v.datasetId)
            return (
              <Card key={v.id} className="flex flex-col p-5">
                <div className="flex items-start justify-between gap-3">
                  <div className="min-w-0">
                    <p className="font-bold text-slate-900">{v.title}</p>
                    <p className="truncate text-xs text-slate-500">
                      {d?.title ?? "Dataset dihapus"} · oleh{" "}
                      {s.users.find((u) => u.id === v.createdBy)?.nama}
                    </p>
                  </div>
                  <Toggle
                    on={v.show}
                    onChange={(b) =>
                      set((p) => ({
                        ...p,
                        viz: p.viz.map((y) =>
                          y.id === v.id ? { ...y, show: b } : y,
                        ),
                      }))
                    }
                    label="Tampilkan di Dashboard"
                  />
                </div>
                <div className="mt-4 h-40">
                  {d && (
                    <Chart
                      kind={v.kind}
                      data={series(d.rows, v.labelCol, v.valueCol)}
                      colors={palettes[v.color]}
                    />
                  )}
                </div>
                <div className="mt-4 flex items-center justify-between border-t border-slate-100 pt-3">
                  <span
                    className={`text-xs font-semibold ${
                      v.show ? "text-emerald-600" : "text-slate-400"
                    }`}
                  >
                    {v.show ? "Tampil di Dashboard" : "Disembunyikan"}
                  </span>
                  <div className="flex gap-1.5">
                    <button
                      onClick={() => setEdit(v)}
                      className={btn.icon}
                      aria-label="Ubah"
                    >
                      <Pencil className="h-4 w-4" />
                    </button>
                    {(user!.role === "admin" || v.createdBy === user!.id) && (
                      <button
                        onClick={() =>
                          ask(
                            `Visualisasi “${v.title}” akan dihapus dari Dashboard.`,
                            () => {
                              set((p) => ({
                                ...p,
                                viz: p.viz.filter((y) => y.id !== v.id),
                              }))
                              toast("Visualisasi dihapus.")
                            },
                          )
                        }
                        className={btn.iconDanger}
                        aria-label="Hapus"
                      >
                        <Trash2 className="h-4 w-4" />
                      </button>
                    )}
                  </div>
                </div>
              </Card>
            )
          })}
        </div>
      ) : (
        <Empty
          icon={BarChart3}
          title="Belum ada visualisasi"
          desc="Buat visualisasi pertama dari dataset yang tersedia."
        />
      )}
      {edit && (
        <VizBuilder
          v={edit === "new" ? undefined : edit}
          onClose={() => setEdit(null)}
        />
      )}
      {confirmEl}
    </>
  )
}
