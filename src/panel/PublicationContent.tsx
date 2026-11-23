import { useState } from "react"
import { BookOpen, Pencil, Plus, Trash2 } from "lucide-react"
import { useStore, uid } from "../store"
import { newsTags, type News } from "../news"
import type { Course } from "../content"
import { btn, Empty, th, td } from "../ui"
import { Card, EditModal, useConfirm, useToast } from "./kit"

export default function PublicationContent({
  tab,
}: {
  tab: "berita" | "kurikulum"
}) {
  const { s, set } = useStore()
  const toast = useToast()
  const [confirmEl, ask] = useConfirm()
  const [article, setArticle] = useState<Partial<News> | null>(null)
  const [course, setCourse] = useState<Partial<Course> | null>(null)
  const items = tab === "berita" ? s.content.berita : s.content.kurikulum
  return (
    <>
      <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
        <p className="text-sm text-slate-500">
          Perubahan langsung tampil di halaman publik pada browser ini.
        </p>
        <button
          className={btn.primary}
          onClick={() =>
            tab === "berita"
              ? setArticle({ badge: newsTags[0], read: 3 })
              : setCourse({
                  tahun: new Date().getFullYear(),
                  semester: 1,
                  sks: 3,
                  pilihan: false,
                })
          }
        >
          <Plus className="h-4 w-4" />
          Tambah {tab === "berita" ? "berita" : "mata kuliah"}
        </button>
      </div>
      {!items.length ? (
        <Empty
          icon={BookOpen}
          title={
            tab === "berita" ? "Belum ada berita" : "Kurikulum belum diisi"
          }
        />
      ) : (
        <Card className="overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full min-w-[600px]">
              <thead className="bg-surface-strong">
                <tr>
                  <th className={th}>Judul / mata kuliah</th>
                  <th className={th}>Keterangan</th>
                  <th className={th}>Tindakan</th>
                </tr>
              </thead>
              <tbody>
                {items.map((item) => (
                  <tr key={item.id} className="border-t border-slate-100">
                    <td className={`${td} font-semibold`}>
                      {"title" in item
                        ? item.title
                        : `${item.kode} · ${item.nama}`}
                    </td>
                    <td className={td}>
                      {"badge" in item
                        ? `${item.badge} · ${item.date}`
                        : `${item.tahun} · Semester ${item.semester} · ${item.sks} SKS · ${
                            item.pilihan ? "Pilihan" : "Wajib"
                          }`}
                    </td>
                    <td className={td}>
                      <div className="flex gap-2">
                        <button
                          className={btn.icon}
                          aria-label="Ubah konten"
                          onClick={() =>
                            tab === "berita"
                              ? setArticle(item as News)
                              : setCourse(item as Course)
                          }
                        >
                          <Pencil className="h-4 w-4" />
                        </button>
                        <button
                          className={btn.iconDanger}
                          aria-label="Hapus konten"
                          onClick={() =>
                            ask("Hapus konten ini dari halaman publik?", () => {
                              set((p) => ({
                                ...p,
                                content: {
                                  ...p.content,
                                  [tab]: p.content[tab].filter(
                                    (x) => x.id !== item.id,
                                  ),
                                },
                              }))
                              toast("Konten dihapus.")
                            })
                          }
                        >
                          <Trash2 className="h-4 w-4" />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </Card>
      )}
      {article && (
        <EditModal
          title={article.id ? "Ubah berita" : "Tambah berita"}
          onClose={() => setArticle(null)}
          value={{
            title: article.title ?? "",
            badge: article.badge ?? "",
            corner: article.corner ?? "",
            date:
              article.date ??
              new Date().toLocaleDateString("id-ID", {
                day: "numeric",
                month: "long",
                year: "numeric",
              }),
            read: article.read ?? 3,
            ex: article.ex ?? "",
            au: article.au ?? "",
            img: article.img ?? "",
            body: article.body?.join("\n\n") ?? "",
          }}
          specs={[
            { k: "title", label: "Judul berita", required: true, full: true },
            {
              k: "badge",
              label: "Kategori",
              type: "select",
              options: newsTags,
              required: true,
            },
            { k: "corner", label: "Label tambahan" },
            { k: "date", label: "Tanggal publikasi", required: true },
            { k: "au", label: "Penulis", required: true },
            {
              k: "read",
              label: "Waktu baca (menit)",
              type: "number",
              required: true,
              check: (v) =>
                Number.isInteger(Number(v)) && Number(v) > 0
                  ? undefined
                  : "Masukkan menit bulat positif.",
            },
            {
              k: "img",
              label: "URL gambar (HTTPS)",
              required: true,
              full: true,
              check: (v) =>
                /^https:\/\/\S+$/i.test(v)
                  ? undefined
                  : "Gunakan URL gambar HTTPS.",
            },
            { k: "ex", label: "Ringkasan", type: "textarea", required: true },
            {
              k: "body",
              label: "Isi berita",
              type: "textarea",
              required: true,
              hint: "Pisahkan paragraf dengan baris kosong.",
            },
          ]}
          onSave={(v) => {
            const n: News = {
              ...v,
              id: article.id ?? uid("B"),
              read: Number(v.read),
              ini: v.au
                .trim()
                .split(/\s+/)
                .slice(0, 2)
                .map((w) => w[0])
                .join("")
                .toUpperCase(),
              body: v.body
                .split(/\n\s*\n/)
                .map((p) => p.trim())
                .filter(Boolean),
            }
            set((p) => ({
              ...p,
              content: {
                ...p.content,
                berita: article.id
                  ? p.content.berita.map((x) => (x.id === n.id ? n : x))
                  : [n, ...p.content.berita],
              },
            }))
            toast("Berita disimpan dan tampil di Beranda & Berita.")
          }}
        />
      )}
      {course && (
        <EditModal
          title={course.id ? "Ubah mata kuliah" : "Tambah mata kuliah"}
          onClose={() => setCourse(null)}
          value={{
            kode: course.kode ?? "",
            nama: course.nama ?? "",
            tahun: course.tahun ?? new Date().getFullYear(),
            semester: course.semester ?? 1,
            sks: course.sks ?? 3,
            pilihan: course.pilihan ?? false,
          }}
          specs={[
            { k: "kode", label: "Kode mata kuliah", required: true },
            { k: "nama", label: "Nama mata kuliah", required: true },
            {
              k: "tahun",
              label: "Tahun kurikulum",
              type: "number",
              required: true,
              check: (v) =>
                /^\d{4}$/.test(v) ? undefined : "Tahun harus 4 digit.",
            },
            {
              k: "semester",
              label: "Semester (1–8)",
              type: "number",
              required: true,
              check: (v) =>
                Number.isInteger(Number(v)) && Number(v) >= 1 && Number(v) <= 8
                  ? undefined
                  : "Semester harus 1–8.",
            },
            {
              k: "sks",
              label: "SKS",
              type: "number",
              required: true,
              check: (v) =>
                Number.isInteger(Number(v)) && Number(v) >= 1 && Number(v) <= 12
                  ? undefined
                  : "SKS harus bilangan bulat 1–12.",
            },
            { k: "pilihan", label: "Mata kuliah pilihan", type: "checkbox" },
          ]}
          onSave={(v) => {
            const duplicate = s.content.kurikulum.some(
              (x) =>
                x.id !== course.id &&
                x.kode.toLowerCase() === v.kode.trim().toLowerCase() &&
                x.tahun === Number(v.tahun),
            )
            if (duplicate)
              return "Kode mata kuliah sudah ada pada tahun kurikulum ini."
            const c: Course = {
              ...v,
              kode: v.kode.trim(),
              nama: v.nama.trim(),
              id: course.id ?? uid("MK"),
              tahun: Number(v.tahun),
              semester: Number(v.semester),
              sks: Number(v.sks),
            }
            set((p) => ({
              ...p,
              content: {
                ...p.content,
                kurikulum: course.id
                  ? p.content.kurikulum.map((x) => (x.id === c.id ? c : x))
                  : [...p.content.kurikulum, c],
              },
            }))
            toast("Mata kuliah disimpan.")
          }}
        />
      )}
      {confirmEl}
    </>
  )
}
