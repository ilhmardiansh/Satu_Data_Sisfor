import { useState } from "react"
import {
  CheckCircle2,
  Clock,
  Download,
  Eye,
  FileSpreadsheet,
  FolderOpen,
  Search,
  XCircle,
} from "lucide-react"
import type { Dataset } from "./datasets"
import { fmtDate, uid, useStore, type DataRequest } from "./store"
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
  downloadXlsx,
  inp,
  sel,
  td,
  th,
  usePage,
} from "./ui"

export const dl = (d: Dataset, fmt: "csv" | "xlsx") =>
  (fmt === "csv" ? downloadCsv : downloadXlsx)(d.title, d.cols, d.rows)

type DownloadButtonsProps = {
  d: Dataset
  compact?: boolean
}

function DownloadButtons({ d, compact }: DownloadButtonsProps) {
  return (
    <div className="flex gap-1.5">
      <button
        onClick={() => dl(d, "csv")}
        className={
          compact ? btn.icon + " w-auto px-2.5 text-xs font-bold" : btn.outline
        }
      >
        <Download className="h-4 w-4" />
        CSV
      </button>
      <button
        onClick={() => dl(d, "xlsx")}
        className={
          compact ? btn.icon + " w-auto px-2.5 text-xs font-bold" : btn.primary
        }
      >
        <FileSpreadsheet className="h-4 w-4" />
        XLSX
      </button>
    </div>
  )
}

type PreviewProps = {
  d: Dataset
  onClose: () => void
}

export function Preview({ d, onClose }: PreviewProps) {
  const { s } = useStore()
  const pg = usePage(d.rows, 10)
  const cat =
    s.categories.find((c) => c.code === d.kategori)?.name ?? d.kategori
  return (
    <Modal wide onClose={onClose}>
      <ModalHead
        kicker={
          <>
            Dataset › {cat} <AccessBadge access={d.access} />
          </>
        }
        title={d.title}
        desc={d.desc}
        onClose={onClose}
      />
      <div className="overflow-auto px-5 py-5 sm:px-8">
        <div className="overflow-hidden rounded-xl border border-slate-200">
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="bg-surface-strong">
                <tr>
                  {d.cols.map((c) => (
                    <th key={c} className={th}>
                      {c}
                    </th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {pg.slice.map((r, i) => (
                  <tr
                    key={i}
                    className="border-t border-slate-100 hover:bg-sky-50/50"
                  >
                    {r.map((v, j) => (
                      <td key={j} className={`${td} tabular-nums`}>
                        {v}
                      </td>
                    ))}
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          <Pager {...pg} />
        </div>
      </div>
      <ModalFoot>
        <p className="mr-auto text-sm text-slate-500">
          {d.id} · {d.rows.length} baris · diperbarui {d.updated}
        </p>
        <DownloadButtons d={d} />
      </ModalFoot>
    </Modal>
  )
}

const purposes = [
  "Penelitian / tugas akhir",
  "Akreditasi & penjaminan mutu",
  "Pelaporan instansi",
  "Publikasi / jurnalistik",
  "Perencanaan program",
  "Lainnya",
]

type RequestFormProps = {
  d: Dataset
  onClose: () => void
}

function RequestForm({ d, onClose }: RequestFormProps) {
  const { set } = useStore()
  const empty = {
    nama: "",
    email: "",
    hp: "",
    nim: "",
    tujuan: "",
    deskripsi: "",
  }
  const [f, setF] = useState(empty)
  const [tried, setTried] = useState(false)
  const [sent, setSent] = useState<string | null>(null)
  const on =
    (k: keyof typeof empty) =>
    (
      e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>,
    ) =>
      setF({ ...f, [k]: e.target.value })
  type ReqKey = keyof typeof empty
  const err: Partial<Record<keyof typeof empty, string>> = {}
  ;(Object.keys(empty) as ReqKey[]).forEach((k) => {
    if (!f[k].trim()) err[k] = "Kolom ini wajib diisi."
  })
  if (f.email && !/^\S+@\S+\.\S+$/.test(f.email))
    err.email = "Format email tidak valid."
  if (f.hp && !/^0\d{9,13}$/.test(f.hp))
    err.hp = "Nomor HP diawali 0, 10–14 digit."
  const e = (k: keyof typeof empty) => (tried ? err[k] : undefined)

  const send = (ev: React.FormEvent) => {
    ev.preventDefault()
    setTried(true)
    if (Object.keys(err).length) return
    const id = uid("R")
    const r: DataRequest = {
      id,
      datasetId: d.id,
      ...f,
      at: new Date().toISOString(),
      status: "menunggu",
    }
    set((p) => ({ ...p, requests: [r, ...p.requests], mine: [...p.mine, id] }))
    setSent(id)
  }

  return (
    <Modal onClose={onClose}>
      <ModalHead
        kicker={
          <>
            Permohonan data <AccessBadge access="private" />
          </>
        }
        title={d.title}
        desc={d.desc}
        onClose={onClose}
      />
      {sent ? (
        <div className="px-8 py-12 text-center">
          <div className="mx-auto grid h-14 w-14 place-items-center rounded-full bg-sky-100/80 text-sky-600">
            <Clock className="h-7 w-7" />
          </div>
          <h3 className="mt-4 text-xl font-bold">
            Permohonan berhasil dikirim
          </h3>
          <p className="mx-auto mt-2 max-w-md text-sm text-slate-500">
            Nomor permohonan <b className="text-slate-800">{sent}</b>.
            Permohonan akan diperiksa oleh Wali Data. Setelah dikonfirmasi,
            dataset dapat dilihat dan diunduh dari halaman ini pada browser yang
            sama.
          </p>
          <button onClick={onClose} className={`${btn.primary} mt-6`}>
            Kembali ke dataset
          </button>
        </div>
      ) : (
        <form noValidate className="flex min-h-0 flex-col" onSubmit={send}>
          <div className="space-y-5 overflow-y-auto px-5 py-6 sm:px-8">
            <Alert tone="warn">
              Dataset ini berstatus <b>private</b>. Akses diberikan setelah
              permohonan dikonfirmasi oleh Wali Data. Kolom bertanda{" "}
              <span className="text-[#DC3545]">*</span> wajib diisi.
            </Alert>
            <Field label="Nama lengkap" required error={e("nama")}>
              <input
                className={inp}
                value={f.nama}
                onChange={on("nama")}
                placeholder="Nama sesuai identitas"
              />
            </Field>
            <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
              <Field
                label="Email"
                required
                hint="Pemberitahuan dikirim ke email ini."
                error={e("email")}
              >
                <input
                  type="email"
                  className={inp}
                  value={f.email}
                  onChange={on("email")}
                  placeholder="nama@email.com"
                />
              </Field>
              <Field label="Nomor HP" required error={e("hp")}>
                <input
                  type="tel"
                  className={inp}
                  value={f.hp}
                  onChange={on("hp")}
                  placeholder="08xxxxxxxxxx"
                />
              </Field>
            </div>
            <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
              <Field label="NIP/NIM/NIK" required error={e("nim")}>
                <input
                  className={inp}
                  value={f.nim}
                  onChange={on("nim")}
                  placeholder="Nomor induk"
                />
              </Field>
              <Field
                label="Tujuan penggunaan data"
                required
                error={e("tujuan")}
              >
                <select
                  className={inp}
                  value={f.tujuan}
                  onChange={on("tujuan")}
                >
                  <option value="">Pilih tujuan…</option>
                  {purposes.map((p) => (
                    <option key={p}>{p}</option>
                  ))}
                </select>
              </Field>
            </div>
            <Field
              label="Deskripsikan kebutuhan data Anda"
              required
              hint="Jelaskan bentuk dan cakupan data yang diinginkan."
              error={e("deskripsi")}
            >
              <textarea
                rows={4}
                className={inp}
                value={f.deskripsi}
                onChange={on("deskripsi")}
                placeholder="Contoh: data penerima KIP angkatan 2022–2024 untuk analisis retensi…"
              />
            </Field>
          </div>
          <ModalFoot>
            <button
              type="button"
              onClick={() => {
                setF(empty)
                setTried(false)
              }}
              className={btn.outline}
            >
              Reset
            </button>
            <button className={btn.primary}>Kirim permohonan</button>
          </ModalFoot>
        </form>
      )}
    </Modal>
  )
}

function StatusInfo({
  r,
  d,
  onClose,
}: {
  r: DataRequest
  d: Dataset
  onClose: () => void
}) {
  return (
    <Modal onClose={onClose}>
      <ModalHead
        kicker={<>Status permohonan · {r.id}</>}
        title={d.title}
        onClose={onClose}
      />
      <div className="space-y-4 px-5 py-6 sm:px-8">
        {r.status === "menunggu" ? (
          <Alert tone="info">
            Permohonan Anda tertanggal {fmtDate(r.at, true)} sedang menunggu
            konfirmasi Wali Data.
          </Alert>
        ) : (
          <Alert tone="error">
            Permohonan ditolak oleh Wali Data{r.note ? `: “${r.note}”` : "."}{" "}
            Anda dapat mengajukan permohonan baru.
          </Alert>
        )}
      </div>
      <ModalFoot>
        <button onClick={onClose} className={btn.outline}>
          Tutup
        </button>
      </ModalFoot>
    </Modal>
  )
}

type Group = "none" | "kategori" | "tahun" | "kategori-tahun"
const groupOpts: [Group, string][] = [
  ["kategori", "Kategori"],
  ["tahun", "Tahun"],
  ["kategori-tahun", "Kategori & tahun"],
  ["none", "Tanpa pengelompokan"],
]
export type DatasetInit = {
  kategori?: string
  q?: string
  access?: "all" | Dataset["access"]
}

export default function DatasetPage({ init }: { init: DatasetInit }) {
  const { s, user, t } = useStore()
  const [kategori, setKategori] = useState(init.kategori ?? "all")
  const [q, setQ] = useState(init.q ?? "")
  const [access, setAccess] = useState<"all" | Dataset["access"]>(
    init.access ?? "all",
  )
  const [tahun, setTahun] = useState("all")
  const [periode, setPeriode] = useState("all")
  const [format, setFormat] = useState("all")
  const [group, setGroup] = useState<Group>("kategori")
  const [open, setOpen] = useState<{
    d: Dataset
    mode: "view" | "form" | "status"
  } | null>(null)

  const privileged = user?.role === "wali" || user?.role === "admin"
  const myReq = (d: Dataset) =>
    s.requests
      .filter((r) => r.datasetId === d.id && s.mine.includes(r.id))
      .sort((a, b) => b.at.localeCompare(a.at))[0]
  const canUse = (d: Dataset) =>
    d.access === "public" || privileged || myReq(d)?.status === "disetujui"
  const name = (code: string) =>
    s.categories.find((c) => c.code === code)?.name ?? code
  const years = [...new Set(s.datasets.map((d) => d.tahun))].sort(
    (a, b) => b - a,
  )
  const kw = q.trim().toLowerCase()
  const list = s.datasets.filter(
    (d) =>
      (kategori === "all" || d.kategori === kategori) &&
      (access === "all" || d.access === access) &&
      (tahun === "all" || String(d.tahun) === tahun) &&
      (periode === "all" || d.periode === periode) &&
      (format === "all" || d.format === format) &&
      (!kw ||
        `${d.title} ${d.desc} ${name(d.kategori)} ${d.cols.join(" ")}`
          .toLowerCase()
          .includes(kw)),
  )
  const keyOf = (d: Dataset) =>
    group === "kategori"
      ? name(d.kategori)
      : group === "tahun"
        ? `Tahun ${d.tahun}`
        : group === "kategori-tahun"
          ? `${name(d.kategori)} · ${d.tahun}`
          : ""
  const sorted = [...list].sort(
    (a, b) =>
      (group === "kategori" || group === "kategori-tahun"
        ? keyOf(a).localeCompare(keyOf(b))
        : 0) ||
      b.tahun - a.tahun ||
      (a.periode === b.periode ? 0 : a.periode === "Genap" ? -1 : 1),
  )
  const pg = usePage(sorted, 8)
  const act = (d: Dataset, mode: "view" | "csv" | "xlsx") => {
    if (!canUse(d)) {
      const r = myReq(d)
      return setOpen({
        d,
        mode:
          r && r.status === "menunggu"
            ? "status"
            : r?.status === "ditolak"
              ? "status"
              : "form",
      })
    }
    mode === "view" ? setOpen({ d, mode: "view" }) : dl(d, mode)
  }
  const mine = s.requests.filter((r) => s.mine.includes(r.id))
  const resetFilters = () => {
    setTahun("all")
    setPeriode("all")
    setFormat("all")
    setAccess("all")
    setQ("")
    setKategori("all")
  }

  return (
    <main className="mx-auto max-w-[1200px] px-4 py-10 sm:px-8">
      <p className="text-sm text-slate-500">
        {t("Beranda", "Home")} <span className="mx-1">/</span>{" "}
        <span className="text-sky-600">Dataset</span>
      </p>
      <h1 className="mt-1 text-[28px] font-extrabold tracking-tight">
        Dataset Sistem Informasi
      </h1>
      <p className="mt-1 text-[15px] text-slate-500">
        {t(
          "Dataset publik dapat langsung dilihat dan diunduh. Dataset private memerlukan permohonan yang dikonfirmasi Wali Data.",
          "Public datasets can be viewed and downloaded directly. Private datasets require a request approved by the Data Steward.",
        )}
      </p>

      {mine.length > 0 && (
        <div className="mt-6 rounded-2xl border border-slate-200 bg-surface p-4">
          <p className="mb-3 text-xs font-bold tracking-wider text-slate-500 uppercase">
            {t("Permohonan saya", "My requests")}
          </p>
          <div className="flex flex-wrap gap-2">
            {mine.map((r) => {
              const d = s.datasets.find((x) => x.id === r.datasetId)
              const c = {
                menunggu: [
                  "bg-sky-100/80 text-sky-700",
                  Clock,
                  "Menunggu konfirmasi",
                ],
                disetujui: [
                  "bg-emerald-50 text-emerald-700",
                  CheckCircle2,
                  "Disetujui",
                ],
                ditolak: ["bg-red-50 text-[#b02a37]", XCircle, "Ditolak"],
              }[r.status] as [string, typeof Clock, string]
              const Ic = c[1]
              return (
                <button
                  key={r.id}
                  onClick={() => d && act(d, "view")}
                  className={`inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-xs font-semibold ${c[0]}`}
                >
                  <Ic className="h-3.5 w-3.5" />
                  {r.id} · {d?.title ?? r.datasetId} · {c[2]}
                </button>
              )
            })}
          </div>
        </div>
      )}

      <div className="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-[240px_1fr]">
        <aside className="hidden space-y-1 lg:block">
          <p className="mb-2 text-xs font-bold tracking-wider text-slate-500 uppercase">
            {t("Kategori", "Category")}
          </p>
          {[
            { code: "all", name: t("Semua kategori", "All categories") },
            ...s.categories,
          ].map((c) => {
            const n =
              c.code === "all"
                ? s.datasets.length
                : s.datasets.filter((d) => d.kategori === c.code).length
            return (
              <button
                key={c.code}
                onClick={() => {
                  setKategori(c.code)
                  pg.setPage(1)
                }}
                className={`flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-left text-sm transition ${
                  kategori === c.code
                    ? "bg-sky-100/80 font-bold text-sky-700"
                    : "text-slate-600 hover:bg-slate-100"
                }`}
              >
                {c.name}
                <span className="text-xs text-slate-400">{n}</span>
              </button>
            )
          })}
        </aside>

        <section className="min-w-0">
          <div className="flex flex-col gap-3 sm:flex-row">
            <label className="flex flex-1 items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 focus-within:border-sky-400 focus-within:ring-4 focus-within:ring-sky-100">
              <Search className="h-4 w-4 text-slate-400" />
              <input
                value={q}
                onChange={(e) => {
                  setQ(e.target.value)
                  pg.setPage(1)
                }}
                placeholder={t(
                  "Cari kata kunci: judul, deskripsi, atau kolom…",
                  "Search keyword: title, description, or column…",
                )}
                className="h-11 flex-1 bg-transparent text-sm outline-none"
              />
            </label>
            <div className="flex rounded-xl border border-slate-200 bg-white p-1 text-sm">
              {([
                ["all", t("Semua", "All")],
                ["public", t("Publik", "Public")],
                ["private", "Private"],
              ] as const).map(([k, l]) => (
                <button
                  key={k}
                  onClick={() => {
                    setAccess(k)
                    pg.setPage(1)
                  }}
                  className={`flex-1 rounded-lg px-3.5 py-1.5 font-medium ${
                    access === k
                      ? "bg-sky-600 text-white"
                      : "text-slate-500 hover:text-slate-800"
                  }`}
                >
                  {l}
                </button>
              ))}
            </div>
          </div>
          <div className="mt-3 flex flex-wrap items-center gap-2 rounded-2xl border border-slate-200 bg-surface p-3">
            <select
              value={kategori}
              onChange={(e) => setKategori(e.target.value)}
              className={`${sel} lg:hidden`}
              aria-label="Kategori"
            >
              <option value="all">
                {t("Semua kategori", "All categories")}
              </option>
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
              aria-label="Tahun"
            >
              <option value="all">{t("Semua tahun", "All years")}</option>
              {years.map((y) => (
                <option key={y}>{y}</option>
              ))}
            </select>
            <select
              value={periode}
              onChange={(e) => setPeriode(e.target.value)}
              className={sel}
              aria-label="Semester"
            >
              <option value="all">
                {t("Semua semester", "All semesters")}
              </option>
              <option>Ganjil</option>
              <option>Genap</option>
            </select>
            <select
              value={format}
              onChange={(e) => setFormat(e.target.value)}
              className={sel}
              aria-label="Format"
            >
              <option value="all">{t("Semua format", "All formats")}</option>
              <option>CSV</option>
              <option>XLSX</option>
            </select>
            <span className="mx-1 hidden h-6 w-px bg-slate-200 sm:block" />
            <span className="text-xs font-bold tracking-wider text-slate-500 uppercase">
              {t("Kelompokkan", "Group by")}
            </span>
            <select
              value={group}
              onChange={(e) => setGroup(e.target.value as Group)}
              className={sel}
            >
              {groupOpts.map(([k, l]) => (
                <option key={k} value={k}>
                  {l}
                </option>
              ))}
            </select>
            <button
              onClick={resetFilters}
              className="ml-auto text-sm font-semibold text-sky-600 hover:text-sky-700"
            >
              Reset
            </button>
          </div>

          <p className="mt-4 text-sm text-slate-500">
            {list.length} {t("dataset ditemukan", "datasets found")}
          </p>
          {list.length ? (
            <div className="mt-3 overflow-hidden rounded-2xl border border-slate-200 bg-surface">
              <div className="overflow-x-auto">
                <table className="w-full min-w-[760px]">
                  <thead className="bg-surface-strong">
                    <tr>
                      <th className={th}>Dataset</th>
                      <th className={th}>{t("Periode", "Period")}</th>
                      <th className={th}>Format</th>
                      <th className={th}>Status</th>
                      <th className={`${th} text-right`}>
                        {t("Aksi", "Actions")}
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    {pg.slice.map((d, i) => {
                      const k = keyOf(d)
                      const showHead =
                        group !== "none" &&
                        (i === 0 || keyOf(pg.slice[i - 1]) !== k)
                      const r = myReq(d)
                      const ok = canUse(d)
                      return [
                        showHead && (
                          <tr
                            key={`g-${k}`}
                            className="border-t border-slate-100 bg-sky-50/60"
                          >
                            <td
                              colSpan={5}
                              className="px-4 py-2 text-xs font-bold text-sky-800"
                            >
                              {k}{" "}
                              <span className="ml-1 font-medium text-sky-600/80">
                                · {sorted.filter((x) => keyOf(x) === k).length}{" "}
                                dataset
                              </span>
                            </td>
                          </tr>
                        ),
                        <tr
                          key={d.id}
                          className="border-t border-slate-200/70 align-top hover:bg-white"
                        >
                          <td className={`${td} max-w-[360px]`}>
                            <p className="font-bold text-slate-900">
                              {d.title}
                            </p>
                            <p className="mt-0.5 line-clamp-2 text-[13px] text-slate-500">
                              {d.desc}
                            </p>
                            <p className="mt-1 text-xs text-slate-400">
                              {name(d.kategori)} · {d.rows.length} baris ·{" "}
                              {t("diperbarui", "updated")} {d.updated}
                            </p>
                          </td>
                          <td className={`${td} whitespace-nowrap`}>
                            {d.tahun}
                            <p className="text-xs text-slate-400">
                              Semester {d.periode}
                            </p>
                          </td>
                          <td className={td}>
                            <span className="rounded-md bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">
                              {d.format}
                            </span>
                          </td>
                          <td className={td}>
                            <AccessBadge access={d.access} />
                            {d.access === "private" && r && (
                              <p
                                className={`mt-1 text-[11px] font-semibold ${
                                  r.status === "disetujui"
                                    ? "text-emerald-600"
                                    : r.status === "ditolak"
                                      ? "text-[#DC3545]"
                                      : "text-sky-600"
                                }`}
                              >
                                {r.status === "disetujui"
                                  ? "Akses diberikan"
                                  : r.status === "ditolak"
                                    ? "Permohonan ditolak"
                                    : "Menunggu konfirmasi"}
                              </p>
                            )}
                          </td>
                          <td className={`${td} text-right`}>
                            {ok ? (
                              <div className="flex justify-end gap-1.5">
                                <button
                                  onClick={() => act(d, "view")}
                                  className={btn.icon}
                                  title="Lihat data"
                                  aria-label="Lihat data"
                                >
                                  <Eye className="h-4 w-4" />
                                </button>
                                <DownloadButtons d={d} compact />
                              </div>
                            ) : (
                              <button
                                onClick={() => act(d, "view")}
                                className={`${btn.outline} py-2 text-xs`}
                              >
                                {r?.status === "menunggu"
                                  ? "Lihat status"
                                  : t("Ajukan permohonan", "Request access")}
                              </button>
                            )}
                          </td>
                        </tr>,
                      ]
                    })}
                  </tbody>
                </table>
              </div>
              <Pager {...pg} />
            </div>
          ) : (
            <div className="mt-3">
              <Empty
                icon={FolderOpen}
                title={t("Data tidak ditemukan", "No matching datasets.")}
                desc={t(
                  "Ubah kata kunci atau filter pencarian.",
                  "Try a different keyword or filter.",
                )}
              />
            </div>
          )}
        </section>
      </div>

      {open?.mode === "view" && (
        <Preview d={open.d} onClose={() => setOpen(null)} />
      )}
      {open?.mode === "form" && (
        <RequestForm d={open.d} onClose={() => setOpen(null)} />
      )}
      {open?.mode === "status" &&
        myReq(open.d) &&
        (myReq(open.d).status === "ditolak" ? (
          <RequestForm d={open.d} onClose={() => setOpen(null)} />
        ) : (
          <StatusInfo
            r={myReq(open.d)}
            d={open.d}
            onClose={() => setOpen(null)}
          />
        ))}
    </main>
  )
}
