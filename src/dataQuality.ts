import type { DataForm, FormField, Submission } from "./store"

export function normalizeValue(field: FormField, value: string) {
  if (field.type === "file") return value
  // Keep paragraph breaks in long answers when standardizing whitespace.
  const clean =
    field.type === "textarea"
      ? value
          .trim()
          .split(/\r?\n/)
          .map((line) => line.trim().replace(/[^\S\n]+/g, " "))
          .join("\n")
      : value.trim().replace(/\s+/g, " ")
  if (field.type === "select")
    return (
      field.options?.find((o) => o.toLowerCase() === clean.toLowerCase()) ??
      clean
    )
  return clean
}

export function fieldError(field: FormField, value: string) {
  const clean = value.trim()
  if (!clean) return field.required ? "Data wajib masih kosong." : undefined
  if (field.type === "number" && !Number.isFinite(Number(clean)))
    return "Nilai harus berupa angka yang valid."
  if (field.type === "date") {
    const date = new Date(clean)
    if (
      !/^\d{4}-\d{2}-\d{2}$/.test(clean) ||
      !Number.isFinite(date.getTime()) ||
      date.toISOString().slice(0, 10) !== clean
    )
      return "Tanggal tidak valid."
  }
  if (field.type === "select" && !field.options?.includes(clean))
    return "Nilai tidak sesuai pilihan formulir."
  if (field.type === "file" && !/\.(pdf|jpe?g|png)$/i.test(clean))
    return "Format bukti harus PDF, JPG, atau PNG."
  return undefined
}

export function inspectSubmissions(form: DataForm, submissions: Submission[]) {
  const groups = new Map<string, string[]>()
  for (const sub of submissions) {
    const key = JSON.stringify([
      sub.userId,
      ...form.fields.map((f) =>
        normalizeValue(f, sub.values[f.id] ?? "").toLowerCase(),
      ),
    ])
    groups.set(key, [...(groups.get(key) ?? []), sub.id])
  }
  const duplicateIds = new Set(
    [...groups.values()].filter((ids) => ids.length > 1).flat(),
  )
  return new Map(
    submissions.map((sub) => {
      const issues: string[] = []
      if (duplicateIds.has(sub.id))
        issues.push(
          "Data ganda: pengirim dan isi sama. Tinjau sebelum menghapus.",
        )
      for (const field of form.fields) {
        const value = sub.values[field.id] ?? ""
        const error = fieldError(field, value)
        if (error) issues.push(`${field.label}: ${error}`)
        if (value && normalizeValue(field, value) !== value)
          issues.push(`${field.label}: format belum seragam.`)
      }
      return [sub.id, issues] as const
    }),
  )
}
