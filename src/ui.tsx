import { useEffect, useState, type ComponentType, type ReactNode } from "react"
import {
  AlertTriangle,
  CheckCircle2,
  ChevronLeft,
  ChevronRight,
  Globe2,
  Info,
  Lock,
  X,
  XCircle,
} from "lucide-react"
import { strToU8, zipSync } from "fflate"

export const inp =
  "w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
export const sel =
  "h-10 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
const base =
  "inline-flex items-center justify-center gap-2 rounded-xl text-sm font-semibold transition motion-safe:active:scale-[0.97] disabled:cursor-not-allowed disabled:opacity-50 disabled:active:scale-100"
export const btn = {
  primary: `${base} bg-brand px-4 py-2.5 text-white hover:bg-sky-700`,
  outline: `${base} border border-slate-200 bg-white px-4 py-2.5 text-slate-700 hover:bg-slate-50`,
  danger: `${base} bg-[#DC3545] px-4 py-2.5 text-white hover:bg-[#bb2d3b]`,
  icon: `${base} h-9 w-9 shrink-0 border border-slate-200 bg-white text-slate-600 hover:border-sky-300 hover:text-sky-700`,
  iconDanger: `${base} h-9 w-9 shrink-0 border border-slate-200 bg-white text-slate-500 hover:border-red-200 hover:bg-red-50 hover:text-[#DC3545]`,
}
export const th =
  "px-4 py-3 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-slate-500 uppercase"
export const td = "px-4 py-3 text-sm text-slate-600"

export function Modal({
  children,
  onClose,
  wide,
}: {
  children: ReactNode
  onClose: () => void
  wide?: boolean
}) {
  useEffect(() => {
    const k = (e: KeyboardEvent) => {
      if (e.key === "Escape") onClose()
    }
    const prev = document.body.style.overflow
    document.body.style.overflow = "hidden"
    window.addEventListener("keydown", k)
    return () => {
      window.removeEventListener("keydown", k)
      document.body.style.overflow = prev
    }
  }, [onClose])
  return (
    <div
      className="fixed inset-0 z-50 grid place-items-end bg-slate-900/40 backdrop-blur-sm sm:place-items-center sm:p-6"
      onMouseDown={onClose}
    >
      <div
        role="dialog"
        aria-modal="true"
        onMouseDown={(e) => e.stopPropagation()}
        className={`flex max-h-[92vh] w-full flex-col overflow-hidden rounded-t-2xl bg-white shadow-2xl sm:rounded-2xl ${
          wide ? "max-w-4xl" : "max-w-2xl"
        }`}
      >
        {children}
      </div>
    </div>
  )
}
export function ModalHead({
  kicker,
  title,
  desc,
  onClose,
  children,
}: {
  kicker?: ReactNode
  title: string
  desc?: string
  onClose: () => void
  children?: ReactNode
}) {
  return (
    <div className="flex items-start justify-between gap-4 border-b border-slate-200 px-5 pt-5 pb-4 sm:px-8 sm:pt-6">
      <div className="min-w-0">
        {kicker && (
          <p className="flex flex-wrap items-center gap-2 text-sm text-slate-500">
            {kicker}
          </p>
        )}
        <h2 className="mt-1 text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">
          {title}
        </h2>
        {desc && <p className="mt-1 text-sm text-slate-500">{desc}</p>}
        {children}
      </div>
      <button onClick={onClose} aria-label="Tutup" className={btn.icon}>
        <X className="h-4 w-4" />
      </button>
    </div>
  )
}
export const ModalFoot = ({ children }: { children: ReactNode }) => (
  <div className="flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:px-8">
    {children}
  </div>
)

export function Field({
  label,
  required,
  hint,
  error,
  children,
}: {
  label: string
  required?: boolean
  hint?: string
  error?: string
  children: ReactNode
}) {
  return (
    <label className="block">
      <span className="mb-1.5 block text-sm font-semibold text-slate-800">
        {label}
        {required && <span className="ml-0.5 text-[#DC3545]">*</span>}
      </span>
      {children}
      {error ? (
        <span className="mt-1 block text-xs text-[#DC3545]">{error}</span>
      ) : (
        hint && (
          <span className="mt-1 block text-xs text-slate-500">{hint}</span>
        )
      )}
    </label>
  )
}

export const AccessBadge = ({ access }: { access: "public" | "private" }) =>
  access === "public" ? (
    <span className="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 text-[11px] font-bold text-emerald-700">
      <Globe2 className="h-3 w-3" />
      Publik
    </span>
  ) : (
    <span className="inline-flex items-center gap-1 rounded-md bg-amber-50 px-2 py-0.5 text-[11px] font-bold text-amber-700">
      <Lock className="h-3 w-3" />
      Private
    </span>
  )

export function Alert({
  tone,
  children,
}: {
  tone: "info" | "success" | "error" | "warn"
  children: ReactNode
}) {
  const [Ic, c] = {
    info: [Info, "bg-sky-100/80 text-sky-800"],
    success: [CheckCircle2, "bg-emerald-50 text-emerald-800"],
    error: [XCircle, "bg-red-50 text-[#b02a37]"],
    warn: [AlertTriangle, "bg-amber-50 text-amber-800"],
  }[tone] as [typeof Info, string]
  return (
    <div
      role={tone === "error" ? "alert" : "status"}
      className={`flex gap-3 rounded-xl px-4 py-3 text-sm ${c}`}
    >
      <Ic className="mt-0.5 h-4 w-4 shrink-0" />
      <div>{children}</div>
    </div>
  )
}

export function usePage<T>(items: T[], size = 10) {
  const [page, setPage] = useState(1)
  const pages = Math.max(1, Math.ceil(items.length / size))
  const cur = Math.min(page, pages)
  return {
    page: cur,
    pages,
    setPage,
    total: items.length,
    size,
    slice: items.slice((cur - 1) * size, cur * size),
  }
}
export function Pager({
  page,
  pages,
  setPage,
  total,
  size,
}: {
  page: number
  pages: number
  setPage: (n: number) => void
  total: number
  size: number
  slice?: unknown[]
}) {
  if (total <= size) return null
  return (
    <div className="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-4 py-3 text-sm text-slate-500">
      <span>
        {(page - 1) * size + 1}–{Math.min(page * size, total)} dari {total}
      </span>
      <div className="flex items-center gap-1.5">
        <button
          disabled={page <= 1}
          onClick={() => setPage(page - 1)}
          className={btn.icon}
          aria-label="Sebelumnya"
        >
          <ChevronLeft className="h-4 w-4" />
        </button>
        <span className="px-2 font-semibold text-slate-700">
          {page} / {pages}
        </span>
        <button
          disabled={page >= pages}
          onClick={() => setPage(page + 1)}
          className={btn.icon}
          aria-label="Berikutnya"
        >
          <ChevronRight className="h-4 w-4" />
        </button>
      </div>
    </div>
  )
}

export function Empty({
  icon: Icon,
  title,
  desc,
}: {
  icon: ComponentType<{ className?: string }>
  title: string
  desc?: string
}) {
  return (
    <div className="rounded-2xl border border-dashed border-slate-300 bg-surface px-6 py-14 text-center">
      <span className="mx-auto grid h-12 w-12 place-items-center rounded-full bg-slate-100 text-slate-400">
        <Icon className="h-6 w-6" />
      </span>
      <p className="mt-4 font-bold text-slate-800">{title}</p>
      {desc && <p className="mt-1 text-sm text-slate-500">{desc}</p>}
    </div>
  )
}

// ---- Unduhan (dibuat di peramban; bukan unduhan aman dari server) ----
type Cell = string | number
const save = (blob: Blob, name: string) => {
  const a = document.createElement("a")
  a.href = URL.createObjectURL(blob)
  a.download = name.replace(/[\\/:*?"<>|]+/g, "-")
  document.body.appendChild(a)
  a.click()
  a.remove()
  setTimeout(() => URL.revokeObjectURL(a.href), 1000)
}
export function downloadCsv(name: string, cols: string[], rows: Cell[][]) {
  const esc = (v: Cell) => {
    const x = String(v ?? "")
    return /[",\n;]/.test(x) ? `"${x.replace(/"/g, '""')}"` : x
  }
  const csv = [cols, ...rows].map((r) => r.map(esc).join(",")).join("\r\n")
  save(
    new Blob(["\uFEFF" + csv], { type: "text/csv;charset=utf-8" }),
    `${name}.csv`,
  )
}
const xml = (v: string) =>
  v.replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
const colName = (i: number) => {
  let n = ""
  i++
  while (i > 0) {
    const m = (i - 1) % 26
    n = String.fromCharCode(65 + m) + n
    i = Math.floor((i - 1) / 26)
  }
  return n
}
export function downloadXlsx(name: string, cols: string[], rows: Cell[][]) {
  const cell = (v: Cell, r: number, c: number) => {
    const ref = `${colName(c)}${r + 1}`
    return typeof v === "number" && Number.isFinite(v)
      ? `<c r="${ref}"><v>${v}</v></c>`
      : `<c r="${ref}" t="inlineStr"${
          r === 0 ? ' s="1"' : ""
        }><is><t xml:space="preserve">${xml(String(v ?? ""))}</t></is></c>`
  }
  const sheet = `<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>${[cols, ...rows].map((r, i) => `<row r="${i + 1}">${r.map((v, j) => cell(v, i, j)).join("")}</row>`).join("")}</sheetData></worksheet>`
  const files = {
    "[Content_Types].xml": strToU8(
      '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>',
    ),
    "_rels/.rels": strToU8(
      '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
    ),
    "xl/workbook.xml": strToU8(
      '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Data" sheetId="1" r:id="rId1"/></sheets></workbook>',
    ),
    "xl/_rels/workbook.xml.rels": strToU8(
      '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>',
    ),
    "xl/styles.xml": strToU8(
      '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts><fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills><borders count="1"><border/></borders><cellStyleXfs count="1"><xf/></cellStyleXfs><cellXfs count="2"><xf/><xf fontId="1" applyFont="1"/></cellXfs></styleSheet>',
    ),
    "xl/worksheets/sheet1.xml": strToU8(sheet),
  }
  save(
    new Blob([zipSync(files)], {
      type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
    }),
    `${name}.xlsx`,
  )
}
