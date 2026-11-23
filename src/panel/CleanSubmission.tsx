import { useState } from "react"
import { fieldError, normalizeValue } from "../dataQuality"
import { useStore, type DataForm, type Submission } from "../store"
import { Alert, Field, Modal, ModalFoot, ModalHead, btn, inp } from "../ui"
import { useToast } from "./kit"

export default function CleanSubmission({
  form,
  submission,
  onClose,
}: {
  form: DataForm
  submission: Submission
  onClose: () => void
}) {
  const { set, user } = useStore()
  const toast = useToast()
  const [values, setValues] = useState({ ...submission.values })
  const [tried, setTried] = useState(false)
  const errors = Object.fromEntries(
    form.fields.map((f) => [f.id, fieldError(f, values[f.id] ?? "")]),
  )
  return (
    <Modal onClose={onClose}>
      <ModalHead
        title="Perbaiki data isian"
        desc={form.title}
        onClose={onClose}
      />
      <form
        noValidate
        className="flex min-h-0 flex-col"
        onSubmit={(e) => {
          e.preventDefault()
          setTried(true)
          if (
            Object.values(errors).some(Boolean) ||
            !user ||
            user.role === "produsen"
          )
            return
          set((s) => ({
            ...s,
            submissions: s.submissions.map((x) =>
              x.id === submission.id
                ? {
                    ...x,
                    values,
                    cleanedBy: user.id,
                    cleanedAt: new Date().toISOString(),
                  }
                : x,
            ),
          }))
          toast("Perbaikan disimpan. Kualitas data diperiksa ulang.")
          onClose()
        }}
      >
        <div className="space-y-4 overflow-y-auto px-5 py-6 sm:px-8">
          <Alert tone="info">
            Perubahan memperbarui data sumber, bukan dataset yang sudah dibuat.
            Data ganda tetap perlu ditinjau dan dihapus secara manual.
          </Alert>
          <button
            type="button"
            className={btn.outline}
            onClick={() =>
              setValues(
                Object.fromEntries(
                  form.fields.map((f) => [
                    f.id,
                    normalizeValue(f, values[f.id] ?? ""),
                  ]),
                ),
              )
            }
          >
            Seragamkan spasi & pilihan
          </button>
          {form.fields.map((f) => (
            <Field
              key={f.id}
              label={f.label}
              required={f.required}
              error={tried ? errors[f.id] : undefined}
              hint={
                f.type === "file"
                  ? "Nama bukti dari prototipe; bukan unggahan berkas ke server."
                  : undefined
              }
            >
              {f.type === "select" ? (
                <select
                  className={inp}
                  value={values[f.id] ?? ""}
                  onChange={(e) =>
                    setValues({ ...values, [f.id]: e.target.value })
                  }
                >
                  <option value="">Pilih…</option>
                  {!!values[f.id] && !f.options?.includes(values[f.id]) && (
                    <option value={values[f.id]}>
                      {values[f.id]} (tidak valid)
                    </option>
                  )}
                  {f.options?.map((o) => <option key={o}>{o}</option>)}
                </select>
              ) : f.type === "textarea" ? (
                <textarea
                  className={inp}
                  rows={3}
                  value={values[f.id] ?? ""}
                  onChange={(e) =>
                    setValues({ ...values, [f.id]: e.target.value })
                  }
                />
              ) : (
                <input
                  className={inp}
                  type={f.type === "file" ? "text" : f.type}
                  value={values[f.id] ?? ""}
                  onChange={(e) =>
                    setValues({ ...values, [f.id]: e.target.value })
                  }
                />
              )}
            </Field>
          ))}
        </div>
        <ModalFoot>
          <button type="button" className={btn.outline} onClick={onClose}>
            Batal
          </button>
          <button className={btn.primary}>Simpan perbaikan</button>
        </ModalFoot>
      </form>
    </Modal>
  )
}
