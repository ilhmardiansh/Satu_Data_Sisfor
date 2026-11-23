import { createContext, useContext, useState } from "react"
import {
  AlertTriangle,
  CheckCircle2,
  Info,
  Trash2,
  XCircle,
} from "lucide-react"
import { Field, Modal, ModalFoot, ModalHead, btn, inp } from "../ui"

// ---- Notifikasi singkat ----
type Tone = "success" | "error" | "info"
const ToastC = createContext<(msg: string, tone?: Tone) => void>(() => {})
export const useToast = () => useContext(ToastC)
export function ToastProvider({ children }: { children: React.ReactNode }) {
  const [items, setItems] = useState<{
    id: number
    msg: string
    tone: Tone
  }[]>([])
  const push = (msg: string, tone: Tone = "success") => {
    const id = Date.now() + Math.random()
    setItems((x) => [...x, { id, msg, tone }])
    setTimeout(() => setItems((x) => x.filter((i) => i.id !== id)), 3500)
  }
  return (
    <ToastC.Provider value={push}>
      {children}
      <div className="pointer-events-none fixed right-4 bottom-4 z-[60] flex flex-col gap-2">
        {items.map((i) => {
          const [Ic, c] = {
            success: [CheckCircle2, "text-emerald-600"],
            error: [XCircle, "text-[#DC3545]"],
            info: [Info, "text-sky-600"],
          }[i.tone] as [typeof Info, string]
          return (
            <div
              key={i.id}
              role="status"
              className="pointer-events-auto flex max-w-sm items-start gap-3 rounded-xl bg-white px-4 py-3 text-sm text-slate-700 shadow-xl ring-1 ring-slate-200"
            >
              <Ic className={`mt-0.5 h-4 w-4 shrink-0 ${c}`} />
              {i.msg}
            </div>
          )
        })}
      </div>
    </ToastC.Provider>
  )
}

// ---- Konfirmasi hapus ----
export function useConfirm() {
  const [st, setSt] = useState<{
    msg: string
    fn: () => void
  } | null>(null)
  const el = st && (
    <Modal onClose={() => setSt(null)}>
      <div className="flex gap-4 px-6 py-6 sm:px-8">
        <span className="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-red-50 text-[#DC3545]">
          <AlertTriangle className="h-5 w-5" />
        </span>
        <div>
          <h2 className="text-lg font-bold">Hapus data?</h2>
          <p className="mt-1 text-sm text-slate-600">{st.msg}</p>
          <p className="mt-2 text-xs text-slate-500">
            Tindakan ini tidak dapat dibatalkan.
          </p>
        </div>
      </div>
      <ModalFoot>
        <button onClick={() => setSt(null)} className={btn.outline}>
          Batal
        </button>
        <button
          onClick={() => {
            st.fn()
            setSt(null)
          }}
          className={btn.danger}
        >
          <Trash2 className="h-4 w-4" />
          Hapus
        </button>
      </ModalFoot>
    </Modal>
  )
  return [el, (msg: string, fn: () => void) => setSt({ msg, fn })] as const
}

export function PanelHead({
  title,
  desc,
  children,
}: {
  title: string
  desc?: string
  children?: React.ReactNode
}) {
  return (
    <div className="mb-6 flex flex-wrap items-end justify-between gap-4">
      <div>
        <h1 className="text-[26px] font-extrabold tracking-tight text-slate-900">
          {title}
        </h1>
        {desc && <p className="mt-1 text-[15px] text-slate-500">{desc}</p>}
      </div>
      {children && <div className="flex flex-wrap gap-2">{children}</div>}
    </div>
  )
}

export function Toggle({
  on,
  onChange,
  label,
}: {
  on: boolean
  onChange: (v: boolean) => void
  label: string
}) {
  return (
    <button
      type="button"
      role="switch"
      aria-checked={on}
      aria-label={label}
      onClick={() => onChange(!on)}
      className={`relative h-6 w-11 shrink-0 rounded-full transition ${
        on ? "bg-sky-600" : "bg-slate-300"
      }`}
    >
      <span
        className={`absolute top-0.5 h-5 w-5 rounded-full bg-white shadow transition ${
          on ? "left-[22px]" : "left-0.5"
        }`}
      />
    </button>
  )
}

export const Card = ({
  children,
  className = "",
}: {
  children: React.ReactNode
  className?: string
}) => (
  <div
    className={`rounded-2xl border border-slate-200 bg-surface ${className}`}
  >
    {children}
  </div>
)

// ---- Formulir ubah generik ----
type V = Record<string, string | number | boolean>
export type Spec = {
  k: string
  label: string
  type?: "text" | "textarea" | "select" | "number" | "password" | "email" | "checkbox" | "datetime"
  options?: (string | [string, string])[]
  required?: boolean
  hint?: string
  full?: boolean
  check?: (v: string, all: V) => string | undefined
  when?: (all: V) => boolean
}

export function EditModal<T extends V>({
  title,
  kicker,
  specs,
  value,
  onSave,
  onClose,
  saveLabel = "Simpan",
}: {
  title: string
  kicker?: string
  specs: Spec[]
  value: T
  onSave: (v: T) => string | void
  onClose: () => void
  saveLabel?: string
}) {
  const [v, setV] = useState<T>(value)
  const [tried, setTried] = useState(false)
  const [fail, setFail] = useState("")
  const vis = specs.filter((s) => !s.when || s.when(v))
  const errs: Record<string, string> = {}
  vis.forEach((s) => {
    const val = String(v[s.k] ?? "").trim()
    if (s.required && s.type !== "checkbox" && !val)
      errs[s.k] = "Kolom ini wajib diisi."
    else if (s.type === "email" && val && !/^\S+@\S+\.\S+$/.test(val))
      errs[s.k] = "Format email tidak valid."
    else if (s.check && val) {
      const e = s.check(val, v)
      if (e) errs[s.k] = e
    }
  })
  const save = (e: React.FormEvent) => {
    e.preventDefault()
    setTried(true)
    if (Object.keys(errs).length) return
    const r = onSave(v)
    if (typeof r === "string") setFail(r)
    else onClose()
  }
  const on = (k: string, x: string | boolean) => setV({ ...v, [k]: x })
  return (
    <Modal onClose={onClose}>
      <ModalHead kicker={kicker} title={title} onClose={onClose} />
      <form noValidate onSubmit={save} className="flex min-h-0 flex-col">
        <div className="grid grid-cols-1 gap-5 overflow-y-auto px-5 py-6 sm:grid-cols-2 sm:px-8">
          {fail && (
            <p className="rounded-xl bg-red-50 px-4 py-3 text-sm text-[#b02a37] sm:col-span-2">
              {fail}
            </p>
          )}
          {vis.map((s) => {
            const val = v[s.k]
            const err = tried ? errs[s.k] : undefined
            const cls = `${inp} ${err ? "border-[#DC3545]" : ""}`
            if (s.type === "checkbox")
              return (
                <label
                  key={s.k}
                  className="flex items-center gap-3 text-sm font-semibold text-slate-800 sm:col-span-2"
                >
                  <Toggle
                    on={!!val}
                    onChange={(x) => on(s.k, x)}
                    label={s.label}
                  />
                  {s.label}
                </label>
              )
            return (
              <div
                key={s.k}
                className={
                  s.full || s.type === "textarea" ? "sm:col-span-2" : ""
                }
              >
                <Field
                  label={s.label}
                  required={s.required}
                  hint={s.hint}
                  error={err}
                >
                  {s.type === "textarea" ? (
                    <textarea
                      rows={4}
                      className={cls}
                      value={String(val ?? "")}
                      onChange={(e) => on(s.k, e.target.value)}
                    />
                  ) : s.type === "select" ? (
                    <select
                      className={cls}
                      value={String(val ?? "")}
                      onChange={(e) => on(s.k, e.target.value)}
                    >
                      <option value="">Pilih…</option>
                      {s.options!.map((o) => {
                        const [a, b] = Array.isArray(o) ? o : [o, o]
                        return (
                          <option key={a} value={a}>
                            {b}
                          </option>
                        )
                      })}
                    </select>
                  ) : (
                    <input
                      type={
                        s.type === "datetime"
                          ? "datetime-local"
                          : (s.type ?? "text")
                      }
                      className={cls}
                      value={String(val ?? "")}
                      onChange={(e) => on(s.k, e.target.value)}
                    />
                  )}
                </Field>
              </div>
            )
          })}
        </div>
        <ModalFoot>
          <button type="button" onClick={onClose} className={btn.outline}>
            Batal
          </button>
          <button className={btn.primary}>{saveLabel}</button>
        </ModalFoot>
      </form>
    </Modal>
  )
}
