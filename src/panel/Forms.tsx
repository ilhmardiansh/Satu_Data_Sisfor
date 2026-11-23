import { useState } from "react"
import {
  ArrowDown,
  ArrowUp,
  CalendarClock,
  CheckCircle2,
  ClipboardList,
  Database,
  Eye,
  FilePlus2,
  History,
  Paperclip,
  Pencil,
  Plus,
  Search,
  Trash2,
  X,
} from "lucide-react"
import {
  canSee,
  fmtDate,
  isOpen,
  uid,
  useStore,
  type DataForm,
  type FieldType,
  type FormField,
  type Submission,
} from "../store"
import {
  Alert,
  Empty,
  Field,
  Modal,
  ModalFoot,
  ModalHead,
  Pager,
  btn,
  inp,
  sel,
  td,
  th,
  usePage,
} from "../ui"
import { Card, PanelHead, useConfirm, useToast } from "./kit"

export type Go = (section: string, arg?: string) => void
export const left = (deadline: string) => {
  const ms = new Date(deadline).getTime() - Date.now()
  if (ms <= 0) return "Ditutup"
  const d = Math.floor(ms / 86400000)
  return d >= 1
    ? `Tutup dalam ${d} hari`
    : `Tutup dalam ${Math.max(1, Math.floor(ms / 3600000))} jam`
}
export const StatusForm = ({ f }: { f: DataForm }) =>
  isOpen(f) ? (
    <span className="inline-flex rounded-md bg-emerald-50 px-2 py-0.5 text-[11px] font-bold text-emerald-700">
      Dibuka
    </span>
  ) : (
    <span className="inline-flex rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-500">
      Ditutup otomatis
    </span>
  )
const toLocal = (iso: string) => {
  const d = new Date(iso)
  return new Date(d.getTime() - d.getTimezoneOffset() * 60000)
    .toISOString()
    .slice(0, 16)
}

// ===== Produsen Data: isi formulir =====
type FillFormProps = {
  f: DataForm
  onClose: () => void
}

export function FillForm({ f, onClose }: FillFormProps) {
  const { submit, s } = useStore()
  const toast = useToast()
  const [v, setV] = useState<Record<string, string>>({})
  const [tried, setTried] = useState(false)
  const [fail, setFail] = useState("")
  const [fileErr, setFileErr] = useState<Record<string, string>>({})
  const errs: Record<string, string> = { ...fileErr }
  f.fields.forEach((x) => {
    if (x.required && !v[x.id]?.trim())
      errs[x.id] =
        x.type === "file"
          ? "Lampiran bukti wajib diunggah."
          : "Kolom ini wajib diisi."
  })
  const cat = s.categories.find((c) => c.code === f.kategori)?.name

  const onFile = (id: string, file?: File) => {
    if (!file) return setV({ ...v, [id]: "" })
    if (file.size > 5 * 1024 * 1024) {
      setFileErr({ ...fileErr, [id]: "Ukuran berkas maksimal 5 MB." })
      return setV({ ...v, [id]: "" })
    }
    if (!/\.(pdf|jpe?g|png)$/i.test(file.name)) {
      setFileErr({
        ...fileErr,
        [id]: "Format berkas harus PDF, JPG, atau PNG.",
      })
      return setV({ ...v, [id]: "" })
    }
    const { [id]: _, ...rest } = fileErr
    setFileErr(rest)
    setV({ ...v, [id]: file.name })
  }
  const send = (e: React.FormEvent) => {
    e.preventDefault()
    setTried(true)
    if (Object.keys(errs).length) return
    const r = submit(f.id, v)
    if (r) return setFail(r)
    toast("Formulir Berhasil Disimpan")
    onClose()
  }
  return (
    <Modal onClose={onClose}>
      <ModalHead
        kicker={
          <>
            <span>
              {cat} · {f.tahun} · Semester {f.periode}
            </span>
          </>
        }
        title={f.title}
        desc={f.desc}
        onClose={onClose}
      >
        <p className="mt-2 inline-flex items-center gap-1.5 rounded-md bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-700">
          <CalendarClock className="h-3.5 w-3.5" />
          Tenggat {fmtDate(f.deadline, true)} · {left(f.deadline)}
        </p>
      </ModalHead>
      <form noValidate onSubmit={send} className="flex min-h-0 flex-col">
        <div className="space-y-5 overflow-y-auto px-5 py-6 sm:px-8">
          {fail && <Alert tone="error">{fail}</Alert>}
          <p className="text-xs text-slate-500">
            Kolom bertanda <span className="text-[#DC3545]">*</span> wajib
            diisi.
          </p>
          {f.fields.map((x) => {
            const err = tried ? errs[x.id] : fileErr[x.id]
            const cls = `${inp} ${err ? "border-[#DC3545]" : ""}`
            return (
              <Field
                key={x.id}
                label={x.label}
                required={x.required}
                error={err}
                hint={
                  x.type === "file"
                    ? "PDF, JPG, atau PNG, maksimal 5 MB. Prototipe hanya mencatat nama berkas, bukan menyimpan isi lampiran."
                    : undefined
                }
              >
                {x.type === "textarea" ? (
                  <textarea
                    rows={3}
                    className={cls}
                    value={v[x.id] ?? ""}
                    onChange={(e) => setV({ ...v, [x.id]: e.target.value })}
                  />
                ) : x.type === "select" ? (
                  <select
                    className={cls}
                    value={v[x.id] ?? ""}
                    onChange={(e) => setV({ ...v, [x.id]: e.target.value })}
                  >
                    <option value="">Pilih…</option>
                    {x.options?.map((o) => <option key={o}>{o}</option>)}
                  </select>
                ) : x.type === "file" ? (
                  <label
                    className={`flex cursor-pointer items-center gap-3 rounded-xl border border-dashed px-4 py-3 text-sm transition hover:border-sky-400 hover:bg-sky-50/50 ${
                      err ? "border-[#DC3545]" : "border-slate-300"
                    }`}
                  >
                    <Paperclip className="h-4 w-4 text-sky-600" />
                    <span
                      className={
                        v[x.id]
                          ? "font-semibold text-slate-800"
                          : "text-slate-500"
                      }
                    >
                      {v[x.id] || "Pilih berkas bukti…"}
                    </span>
                    <input
                      type="file"
                      accept=".pdf,.jpg,.jpeg,.png"
                      className="sr-only"
                      onChange={(e) => onFile(x.id, e.target.files?.[0])}
                    />
                  </label>
                ) : (
                  <input
                    type={x.type}
                    className={cls}
                    value={v[x.id] ?? ""}
                    onChange={(e) => setV({ ...v, [x.id]: e.target.value })}
                  />
                )}
              </Field>
            )
          })}
        </div>
        <ModalFoot>
          <button type="button" onClick={onClose} className={btn.outline}>
            Batal
          </button>
          <button className={btn.primary}>Kirim formulir</button>
        </ModalFoot>
      </form>
    </Modal>
  )
}

export function IsiFormulir() {
  const { s, user } = useStore()
  const [open, setOpen] = useState<DataForm | null>(null)
  // Formulir yang lewat tenggat disembunyikan otomatis.
  const forms = s.forms
    .filter((f) => canSee(user!, f) && isOpen(f))
    .sort((a, b) => a.deadline.localeCompare(b.deadline))
  return (
    <>
      <PanelHead
        title="Isi Formulir"
        desc="Formulir pengumpulan data yang sedang dibuka untuk Anda. Formulir yang melewati tenggat waktu tidak ditampilkan."
      />
      {forms.length ? (
        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
          {forms.map((f) => {
            const mine = s.submissions.filter(
              (x) => x.formId === f.id && x.userId === user!.id,
            ).length
            const urgent =
              new Date(f.deadline).getTime() - Date.now() < 3 * 86400000
            return (
              <Card key={f.id} className="flex flex-col p-5">
                <div className="flex items-center gap-2 text-xs text-slate-500">
                  <span className="rounded-md bg-sky-100/80 px-2 py-0.5 font-bold text-sky-700">
                    {s.categories.find((c) => c.code === f.kategori)?.name}
                  </span>
                  {f.tahun} · Semester {f.periode}
                </div>
                <h3 className="mt-3 text-lg font-bold text-slate-900">
                  {f.title}
                </h3>
                <p className="mt-1 line-clamp-2 text-sm text-slate-500">
                  {f.desc}
                </p>
                <div className="mt-4 flex flex-wrap gap-x-5 gap-y-1 text-[13px] text-slate-500">
                  <span
                    className={`flex items-center gap-1.5 font-semibold ${
                      urgent ? "text-[#DC3545]" : "text-amber-700"
                    }`}
                  >
                    <CalendarClock className="h-4 w-4" />
                    {left(f.deadline)}
                  </span>
                  <span>{f.fields.length} pertanyaan</span>
                  {mine > 0 && (
                    <span className="flex items-center gap-1 font-semibold text-emerald-600">
                      <CheckCircle2 className="h-4 w-4" />
                      Sudah dikirim {mine}×
                    </span>
                  )}
                </div>
                <div className="mt-5 flex justify-end border-t border-slate-100 pt-4">
                  <button onClick={() => setOpen(f)} className={btn.primary}>
                    <FilePlus2 className="h-4 w-4" />
                    {mine ? "Kirim lagi" : "Isi formulir"}
                  </button>
                </div>
              </Card>
            )
          })}
        </div>
      ) : (
        <Empty
          icon={ClipboardList}
          title="Tidak ada formulir yang dibuka"
          desc="Formulir baru akan muncul di sini saat Wali Data membukanya."
        />
      )}
      {open && <FillForm f={open} onClose={() => setOpen(null)} />}
    </>
  )
}

// ===== Riwayat formulir =====
function SubmissionDetail({
  x,
  onClose,
}: {
  x: Submission
  onClose: () => void
}) {
  const { s } = useStore()
  const f = s.forms.find((y) => y.id === x.formId)
  const u = s.users.find((y) => y.id === x.userId)
  return (
    <Modal onClose={onClose}>
      <ModalHead
        kicker={<>Riwayat · {x.id}</>}
        title={f?.title ?? x.formId}
        desc={`Dikirim oleh ${u?.nama ?? "-"} (${u?.nim ?? "-"}) pada ${fmtDate(x.at, true)}`}
        onClose={onClose}
      />
      <dl className="divide-y divide-slate-100 overflow-y-auto px-5 py-2 sm:px-8">
        {(
          f?.fields ??
          Object.keys(x.values).map((k) => ({
            id: k,
            label: k,
            type: "text" as FieldType,
          }))
        ).map((fl) => (
          <div
            key={fl.id}
            className="grid grid-cols-1 gap-1 py-3 sm:grid-cols-[200px_1fr]"
          >
            <dt className="text-sm text-slate-500">{fl.label}</dt>
            <dd className="text-sm font-medium text-slate-800">
              {fl.type === "file" && x.values[fl.id] ? (
                <span className="inline-flex items-center gap-1.5 text-sky-700">
                  <Paperclip className="h-4 w-4" />
                  {x.values[fl.id]}
                </span>
              ) : (
                x.values[fl.id] || "—"
              )}
            </dd>
          </div>
        ))}
      </dl>
      <ModalFoot>
        <button onClick={onClose} className={btn.outline}>
          Tutup
        </button>
      </ModalFoot>
    </Modal>
  )
}

export function Riwayat() {
  const { s, user } = useStore()
  const own = user!.role === "produsen"
  const [q, setQ] = useState("")
  const [form, setForm] = useState("all")
  const [open, setOpen] = useState<Submission | null>(null)
  const kw = q.toLowerCase()
  const rows = s.submissions
    .filter(
      (x) =>
        (!own || x.userId === user!.id) &&
        (form === "all" || x.formId === form),
    )
    .filter((x) => {
      const u = s.users.find((y) => y.id === x.userId)
      const f = s.forms.find((y) => y.id === x.formId)
      return (
        !kw || `${u?.nama} ${u?.nim} ${f?.title}`.toLowerCase().includes(kw)
      )
    })
    .sort((a, b) => b.at.localeCompare(a.at))
  const pg = usePage(rows, 10)
  const formsUsed = s.forms.filter((f) =>
    s.submissions.some(
      (x) => x.formId === f.id && (!own || x.userId === user!.id),
    ),
  )
  return (
    <>
      <PanelHead
        title="Riwayat Formulir"
        desc={
          own
            ? "Formulir yang telah Anda kirim beserta waktu pengirimannya."
            : "Catatan seluruh formulir yang telah diunggah Produsen Data: siapa pengirimnya dan kapan dikirim."
        }
      />
      <div className="mb-4 flex flex-col gap-3 sm:flex-row">
        <label className="flex flex-1 items-center gap-3 rounded-xl border border-slate-200 bg-white px-4">
          <Search className="h-4 w-4 text-slate-400" />
          <input
            value={q}
            onChange={(e) => setQ(e.target.value)}
            placeholder={
              own ? "Cari formulir…" : "Cari nama, NIM, atau formulir…"
            }
            className="h-10 flex-1 bg-transparent text-sm outline-none"
          />
        </label>
        <select
          value={form}
          onChange={(e) => setForm(e.target.value)}
          className={sel}
        >
          <option value="all">Semua formulir</option>
          {formsUsed.map((f) => (
            <option key={f.id} value={f.id}>
              {f.title}
            </option>
          ))}
        </select>
      </div>
      {rows.length ? (
        <Card className="overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full min-w-[680px]">
              <thead className="bg-surface-strong">
                <tr>
                  <th className={th}>Waktu kirim</th>
                  <th className={th}>Formulir</th>
                  {!own && <th className={th}>Pengirim</th>}
                  <th className={th}>Lampiran</th>
                  <th className={th}>Status</th>
                  <th className={th} />
                </tr>
              </thead>
              <tbody>
                {pg.slice.map((x) => {
                  const f = s.forms.find((y) => y.id === x.formId)
                  const u = s.users.find((y) => y.id === x.userId)
                  const files =
                    f?.fields.filter(
                      (fl) => fl.type === "file" && x.values[fl.id],
                    ).length ?? 0
                  return (
                    <tr
                      key={x.id}
                      className="border-t border-slate-200/70 hover:bg-white"
                    >
                      <td className={`${td} whitespace-nowrap`}>
                        {fmtDate(x.at, true)}
                      </td>
                      <td className={td}>
                        <p className="font-semibold text-slate-900">
                          {f?.title ?? "(formulir dihapus)"}
                        </p>
                        <p className="text-xs text-slate-400">{x.id}</p>
                      </td>
                      {!own && (
                        <td className={td}>
                          {u?.nama}
                          <p className="text-xs text-slate-400">
                            {u?.nim} · {u?.jenis}
                          </p>
                        </td>
                      )}
                      <td className={td}>
                        {files ? (
                          <span className="inline-flex items-center gap-1 text-sky-700">
                            <Paperclip className="h-3.5 w-3.5" />
                            {files} berkas
                          </span>
                        ) : (
                          "—"
                        )}
                      </td>
                      <td className={td}>
                        <span className="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 text-[11px] font-bold text-emerald-700">
                          <CheckCircle2 className="h-3 w-3" />
                          Terkirim
                        </span>
                      </td>
                      <td className={`${td} text-right`}>
                        <button
                          onClick={() => setOpen(x)}
                          className={btn.icon}
                          aria-label="Lihat detail"
                        >
                          <Eye className="h-4 w-4" />
                        </button>
                      </td>
                    </tr>
                  )
                })}
              </tbody>
            </table>
          </div>
          <Pager {...pg} />
        </Card>
      ) : (
        <Empty icon={History} title="Belum ada riwayat formulir" />
      )}
      {open && <SubmissionDetail x={open} onClose={() => setOpen(null)} />}
    </>
  )
}

// ===== Wali Data: kelola formulir =====
const types: [FieldType, string][] = [
  ["text", "Teks singkat"],
  ["textarea", "Paragraf"],
  ["number", "Angka"],
  ["date", "Tanggal"],
  ["select", "Pilihan"],
  ["file", "Lampiran bukti"],
]

type DraftField = FormField & { opt?: string }

type FormBuilderProps = {
  f?: DataForm
  onClose: () => void
}

function FormBuilder({ f, onClose }: FormBuilderProps) {
  const { s, set, user } = useStore()
  const toast = useToast()
  const [v, setV] = useState({
    title: f?.title ?? "",
    desc: f?.desc ?? "",
    kategori: f?.kategori ?? "",
    tahun: String(f?.tahun ?? new Date().getFullYear()),
    periode: f?.periode ?? "Ganjil",
    target: f?.target ?? "Mahasiswa",
    deadline: f
      ? toLocal(f.deadline)
      : toLocal(new Date(Date.now() + 14 * 86400000).toISOString()),
  })
  const [fields, setFields] = useState<DraftField[]>(
    f?.fields.map((x) => ({ ...x, opt: x.options?.join(", ") })) ?? [
      { id: uid("q"), label: "", type: "text", required: true },
    ],
  )
  const [tried, setTried] = useState(false)
  const errs: Record<string, string> = {}
  ;(["title", "kategori", "tahun", "deadline"] as const).forEach((k) => {
    if (!String(v[k]).trim()) errs[k] = "Kolom ini wajib diisi."
  })
  if (v.deadline && !f && new Date(v.deadline).getTime() <= Date.now())
    errs.deadline = "Tenggat harus setelah waktu sekarang."
  if (!/^\d{4}$/.test(v.tahun)) errs.tahun = "Tahun 4 digit."
  fields.forEach((x) => {
    if (!x.label.trim()) errs[x.id] = "Label pertanyaan wajib diisi."
    else if (
      x.type === "select" &&
      !(x.opt ?? "").split(",").some((o) => o.trim())
    )
      errs[x.id] = "Isi minimal satu pilihan."
  })
  if (!fields.length) errs.fields = "Tambahkan minimal satu pertanyaan."
  const e = (k: string) => (tried ? errs[k] : undefined)
  const upd = (i: number, p: Partial<DraftField>) =>
    setFields(fields.map((x, j) => (j === i ? { ...x, ...p } : x)))
  const move = (i: number, d: number) => {
    const n = [...fields]
    ;[n[i], n[i + d]] = [n[i + d], n[i]]
    setFields(n)
  }

  const save = (ev: React.FormEvent) => {
    ev.preventDefault()
    setTried(true)
    if (Object.keys(errs).length) return
    const clean: FormField[] = fields.map(({ opt, ...x }) => ({
      ...x,
      label: x.label.trim(),
      ...(x.type === "select"
        ? {
            options: (opt ?? "")
              .split(",")
              .map((o) => o.trim())
              .filter(Boolean),
          }
        : { options: undefined }),
    }))
    const data: DataForm = {
      id: f?.id ?? uid("F"),
      title: v.title.trim(),
      desc: v.desc.trim(),
      kategori: v.kategori,
      tahun: Number(v.tahun),
      periode: v.periode as DataForm["periode"],
      target: v.target as DataForm["target"],
      deadline: new Date(v.deadline).toISOString(),
      fields: clean,
      createdBy: f?.createdBy ?? user!.id,
      createdAt: f?.createdAt ?? new Date().toISOString(),
    }
    set((p) => ({
      ...p,
      forms: f
        ? p.forms.map((x) => (x.id === f.id ? data : x))
        : [data, ...p.forms],
    }))
    toast(f ? "Formulir diperbarui." : "Formulir Berhasil Dibuat")
    onClose()
  }
  return (
    <Modal wide onClose={onClose}>
      <ModalHead
        kicker="Kelola formulir"
        title={f ? "Ubah formulir" : "Buat formulir baru"}
        desc="Formulir otomatis disembunyikan dan menolak pengiriman setelah tenggat waktu."
        onClose={onClose}
      />
      <form noValidate onSubmit={save} className="flex min-h-0 flex-col">
        <div className="space-y-6 overflow-y-auto px-5 py-6 sm:px-8">
          <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div className="sm:col-span-2">
              <Field label="Judul formulir" required error={e("title")}>
                <input
                  className={inp}
                  value={v.title}
                  onChange={(x) => setV({ ...v, title: x.target.value })}
                  placeholder="Contoh: Pelaporan Prestasi Mahasiswa 2026"
                />
              </Field>
            </div>
            <div className="sm:col-span-2">
              <Field label="Deskripsi">
                <textarea
                  rows={2}
                  className={inp}
                  value={v.desc}
                  onChange={(x) => setV({ ...v, desc: x.target.value })}
                />
              </Field>
            </div>
            <Field label="Kategori data" required error={e("kategori")}>
              <select
                className={inp}
                value={v.kategori}
                onChange={(x) => setV({ ...v, kategori: x.target.value })}
              >
                <option value="">Pilih kategori…</option>
                {s.categories.map((c) => (
                  <option key={c.code} value={c.code}>
                    {c.name}
                  </option>
                ))}
              </select>
            </Field>
            <Field label="Diisi oleh" required>
              <select
                className={inp}
                value={v.target}
                onChange={(x) =>
                  setV({ ...v, target: x.target.value as DataForm["target"] })
                }
              >
                <option>Mahasiswa</option>
                <option>Dosen</option>
                <option>Semua</option>
              </select>
            </Field>
            <div className="grid grid-cols-2 gap-3">
              <Field label="Tahun" required error={e("tahun")}>
                <input
                  className={inp}
                  inputMode="numeric"
                  value={v.tahun}
                  onChange={(x) => setV({ ...v, tahun: x.target.value })}
                />
              </Field>
              <Field label="Semester" required>
                <select
                  className={inp}
                  value={v.periode}
                  onChange={(x) =>
                    setV({
                      ...v,
                      periode: x.target.value as DataForm["periode"],
                    })
                  }
                >
                  <option>Ganjil</option>
                  <option>Genap</option>
                </select>
              </Field>
            </div>
            <Field label="Tenggat waktu" required error={e("deadline")}>
              <input
                type="datetime-local"
                className={inp}
                value={v.deadline}
                onChange={(x) => setV({ ...v, deadline: x.target.value })}
              />
            </Field>
          </div>

          <div>
            <div className="mb-3 flex items-center justify-between">
              <h3 className="font-bold">Pertanyaan</h3>
              {e("fields") && (
                <span className="text-xs text-[#DC3545]">{e("fields")}</span>
              )}
            </div>
            <div className="space-y-3">
              {fields.map((x, i) => (
                <div
                  key={x.id}
                  className="rounded-xl border border-slate-200 bg-slate-50/60 p-4"
                >
                  <div className="flex flex-wrap items-start gap-3">
                    <span className="mt-2.5 grid h-6 w-6 place-items-center rounded-full bg-white text-xs font-bold text-slate-500 ring-1 ring-slate-200">
                      {i + 1}
                    </span>
                    <div className="min-w-[200px] flex-1">
                      <input
                        className={`${inp} ${
                          e(x.id) ? "border-[#DC3545]" : ""
                        }`}
                        value={x.label}
                        onChange={(ev) => upd(i, { label: ev.target.value })}
                        placeholder="Label pertanyaan"
                        aria-label="Label pertanyaan"
                      />
                      {e(x.id) && (
                        <span className="mt-1 block text-xs text-[#DC3545]">
                          {e(x.id)}
                        </span>
                      )}
                    </div>
                    <select
                      className={`${sel} h-[42px]`}
                      value={x.type}
                      onChange={(ev) =>
                        upd(i, { type: ev.target.value as FieldType })
                      }
                      aria-label="Jenis isian"
                    >
                      {types.map(([k, l]) => (
                        <option key={k} value={k}>
                          {l}
                        </option>
                      ))}
                    </select>
                    <label className="mt-2.5 flex items-center gap-2 text-sm text-slate-600">
                      <input
                        type="checkbox"
                        checked={x.required}
                        onChange={(ev) =>
                          upd(i, { required: ev.target.checked })
                        }
                        className="h-4 w-4 accent-sky-600"
                      />
                      Wajib
                    </label>
                    <div className="flex gap-1">
                      <button
                        type="button"
                        disabled={i === 0}
                        onClick={() => move(i, -1)}
                        className={`${btn.icon} disabled:opacity-40`}
                        aria-label="Naikkan"
                      >
                        <ArrowUp className="h-4 w-4" />
                      </button>
                      <button
                        type="button"
                        disabled={i === fields.length - 1}
                        onClick={() => move(i, 1)}
                        className={`${btn.icon} disabled:opacity-40`}
                        aria-label="Turunkan"
                      >
                        <ArrowDown className="h-4 w-4" />
                      </button>
                      <button
                        type="button"
                        onClick={() =>
                          setFields(fields.filter((_, j) => j !== i))
                        }
                        className={btn.iconDanger}
                        aria-label="Hapus pertanyaan"
                      >
                        <X className="h-4 w-4" />
                      </button>
                    </div>
                  </div>
                  {x.type === "select" && (
                    <input
                      className={`${inp} mt-3`}
                      value={x.opt ?? ""}
                      onChange={(ev) => upd(i, { opt: ev.target.value })}
                      placeholder="Pilihan, pisahkan dengan koma. Contoh: Regional, Nasional, Internasional"
                    />
                  )}
                </div>
              ))}
            </div>
            <button
              type="button"
              onClick={() =>
                setFields([
                  ...fields,
                  { id: uid("q"), label: "", type: "text", required: false },
                ])
              }
              className={`${btn.outline} mt-3 w-full border-dashed`}
            >
              <Plus className="h-4 w-4" />
              Tambah pertanyaan
            </button>
          </div>
        </div>
        <ModalFoot>
          <button type="button" onClick={onClose} className={btn.outline}>
            Batal
          </button>
          <button className={btn.primary}>
            {f ? "Simpan perubahan" : "Buat formulir"}
          </button>
        </ModalFoot>
      </form>
    </Modal>
  )
}

export function KelolaFormulir({ go }: { go: Go }) {
  const { s, set, user } = useStore()
  const toast = useToast()
  const [confirmEl, ask] = useConfirm()
  const [edit, setEdit] = useState<DataForm | "new" | null>(null)
  const [status, setStatus] = useState<"all" | "open" | "closed">("all")
  const rows = s.forms
    .filter((f) => status === "all" || (status === "open") === isOpen(f))
    .sort((a, b) => b.createdAt.localeCompare(a.createdAt))
  const pg = usePage(rows, 10)
  return (
    <>
      <PanelHead
        title="Kelola Formulir"
        desc="Buat formulir pengumpulan data dan tetapkan tenggat waktu pengisiannya."
      >
        <button onClick={() => setEdit("new")} className={btn.primary}>
          <Plus className="h-4 w-4" />
          Buat formulir
        </button>
      </PanelHead>
      <div className="mb-4 flex w-fit rounded-xl border border-slate-200 bg-white p-1 text-sm">
        {([
          ["all", "Semua"],
          ["open", "Dibuka"],
          ["closed", "Ditutup"],
        ] as const).map(([k, l]) => (
          <button
            key={k}
            onClick={() => setStatus(k)}
            className={`rounded-lg px-3.5 py-1.5 font-medium ${
              status === k ? "bg-sky-600 text-white" : "text-slate-500"
            }`}
          >
            {l}{" "}
            <span className="opacity-70">
              (
              {
                s.forms.filter(
                  (f) => k === "all" || (k === "open") === isOpen(f),
                ).length
              }
              )
            </span>
          </button>
        ))}
      </div>
      <Card className="overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full min-w-[820px]">
            <thead className="bg-surface-strong">
              <tr>
                <th className={th}>Formulir</th>
                <th className={th}>Diisi oleh</th>
                <th className={th}>Tenggat</th>
                <th className={th}>Status</th>
                <th className={th}>Isian</th>
                <th className={`${th} text-right`}>Aksi</th>
              </tr>
            </thead>
            <tbody>
              {pg.slice.map((f) => (
                <tr
                  key={f.id}
                  className="border-t border-slate-200/70 hover:bg-white"
                >
                  <td className={td}>
                    <p className="font-semibold text-slate-900">{f.title}</p>
                    <p className="text-xs text-slate-400">
                      {s.categories.find((c) => c.code === f.kategori)?.name} ·{" "}
                      {f.tahun} · {f.periode} · {f.fields.length} pertanyaan
                    </p>
                  </td>
                  <td className={td}>{f.target}</td>
                  <td className={`${td} whitespace-nowrap`}>
                    {fmtDate(f.deadline, true)}
                    <p className="text-xs text-slate-400">{left(f.deadline)}</p>
                  </td>
                  <td className={td}>
                    <StatusForm f={f} />
                  </td>
                  <td className={`${td} font-semibold`}>
                    {s.submissions.filter((x) => x.formId === f.id).length}
                  </td>
                  <td className={td}>
                    <div className="flex justify-end gap-1.5">
                      <button
                        onClick={() => go("data", f.id)}
                        className={btn.icon}
                        title="Lihat data terkumpul"
                        aria-label="Lihat data terkumpul"
                      >
                        <Database className="h-4 w-4" />
                      </button>
                      <button
                        onClick={() => setEdit(f)}
                        className={btn.icon}
                        title="Ubah"
                        aria-label="Ubah"
                      >
                        <Pencil className="h-4 w-4" />
                      </button>
                      {user!.role === "admin" && (
                        <button
                          onClick={() =>
                            ask(
                              `Formulir “${f.title}” beserta ${s.submissions.filter((x) => x.formId === f.id).length} isiannya akan dihapus.`,
                              () => {
                                set((p) => ({
                                  ...p,
                                  forms: p.forms.filter((x) => x.id !== f.id),
                                  submissions: p.submissions.filter(
                                    (x) => x.formId !== f.id,
                                  ),
                                }))
                                toast("Formulir dihapus.")
                              },
                            )
                          }
                          className={btn.iconDanger}
                          title="Hapus"
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
      {edit && (
        <FormBuilder
          f={edit === "new" ? undefined : edit}
          onClose={() => setEdit(null)}
        />
      )}
      {confirmEl}
    </>
  )
}
