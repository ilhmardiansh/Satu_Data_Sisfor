import { useState } from "react"
import {
  Award,
  Check,
  Inbox,
  Pencil,
  Plus,
  Search,
  Trash2,
  UserCog,
  Users,
  X,
} from "lucide-react"
import type { Dosen, Prestasi } from "../content"
import {
  fmtDate,
  roleLabel,
  uid,
  useStore,
  type DataRequest,
  type Role,
  type User,
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
  inp,
  sel,
  td,
  th,
  usePage,
} from "../ui"
import { Card, EditModal, PanelHead, useConfirm, useToast } from "./kit"
import PublicationContent from "./PublicationContent"

// ===== Permohonan data (Wali Data) =====
export const ReqStatus = ({ s }: { s: DataRequest["status"] }) => {
  const c = {
    menunggu: "bg-sky-100/80 text-sky-700",
    disetujui: "bg-emerald-50 text-emerald-700",
    ditolak: "bg-red-50 text-[#b02a37]",
  }[s]
  return (
    <span
      className={`inline-flex rounded-md px-2 py-0.5 text-[11px] font-bold ${c}`}
    >
      {{ menunggu: "Menunggu", disetujui: "Disetujui", ditolak: "Ditolak" }[s]}
    </span>
  )
}

type DecideProps = {
  r: DataRequest
  onClose: () => void
}

function Decide({ r, onClose }: DecideProps) {
  const { s, set, user } = useStore()
  const toast = useToast()
  const [note, setNote] = useState(r.note ?? "")
  const [err, setErr] = useState("")
  const d = s.datasets.find((x) => x.id === r.datasetId)
  const decide = (status: "disetujui" | "ditolak") => {
    if (status === "ditolak" && !note.trim())
      return setErr("Alasan penolakan wajib diisi.")
    set((p) => ({
      ...p,
      requests: p.requests.map((x) =>
        x.id === r.id
          ? {
              ...x,
              status,
              note: note.trim() || undefined,
              decidedBy: user!.id,
              decidedAt: new Date().toISOString(),
            }
          : x,
      ),
    }))
    toast(
      status === "disetujui"
        ? `Permohonan ${r.id} disetujui. Pemohon kini dapat mengunduh dataset.`
        : `Permohonan ${r.id} ditolak.`,
      status === "disetujui" ? "success" : "info",
    )
    onClose()
  }
  return (
    <Modal onClose={onClose}>
      <ModalHead
        kicker={
          <>
            Permohonan {r.id} <ReqStatus s={r.status} />
          </>
        }
        title={d?.title ?? r.datasetId}
        desc={`Diajukan ${fmtDate(r.at, true)}`}
        onClose={onClose}
      />
      <div className="space-y-5 overflow-y-auto px-5 py-6 sm:px-8">
        <dl className="grid grid-cols-1 gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
          {[
            ["Nama", r.nama],
            ["NIP/NIM/NIK", r.nim],
            ["Email", r.email],
            ["Nomor HP", r.hp],
            ["Tujuan", r.tujuan],
          ].map(([l, v]) => (
            <div key={l}>
              <dt className="text-slate-500">{l}</dt>
              <dd className="font-semibold text-slate-800">{v}</dd>
            </div>
          ))}
          <div>
            <dt className="text-slate-500">Dataset</dt>
            <dd className="mt-0.5">
              <AccessBadge access={d?.access ?? "private"} />
            </dd>
          </div>
          <div className="sm:col-span-2">
            <dt className="text-slate-500">Kebutuhan data</dt>
            <dd className="mt-1 rounded-xl bg-slate-50 p-3 text-slate-700">
              {r.deskripsi}
            </dd>
          </div>
        </dl>
        {r.status === "menunggu" ? (
          <Field
            label="Catatan untuk pemohon"
            hint="Wajib diisi jika permohonan ditolak."
            error={err}
          >
            <textarea
              rows={3}
              className={inp}
              value={note}
              onChange={(e) => {
                setNote(e.target.value)
                setErr("")
              }}
            />
          </Field>
        ) : (
          <Alert tone={r.status === "disetujui" ? "success" : "error"}>
            {r.status === "disetujui" ? "Disetujui" : "Ditolak"} oleh{" "}
            {s.users.find((u) => u.id === r.decidedBy)?.nama ?? "-"} pada{" "}
            {r.decidedAt ? fmtDate(r.decidedAt, true) : "-"}
            {r.note ? ` — “${r.note}”` : ""}
          </Alert>
        )}
      </div>
      <ModalFoot>
        {r.status === "menunggu" ? (
          <>
            <button onClick={() => decide("ditolak")} className={btn.danger}>
              <X className="h-4 w-4" />
              Tolak
            </button>
            <button onClick={() => decide("disetujui")} className={btn.primary}>
              <Check className="h-4 w-4" />
              Setujui
            </button>
          </>
        ) : (
          <button onClick={onClose} className={btn.outline}>
            Tutup
          </button>
        )}
      </ModalFoot>
    </Modal>
  )
}

export function Permohonan() {
  const { s } = useStore()
  const [tab, setTab] = useState<"menunggu" | "disetujui" | "ditolak" | "all">(
    "menunggu",
  )
  const [open, setOpen] = useState<DataRequest | null>(null)
  const rows = s.requests
    .filter((r) => tab === "all" || r.status === tab)
    .sort((a, b) => b.at.localeCompare(a.at))
  const pg = usePage(rows, 10)
  return (
    <>
      <PanelHead
        title="Permohonan Data"
        desc="Konfirmasi permohonan dataset private. Pemohon hanya dapat mengunduh setelah disetujui."
      />
      <div className="mb-4 flex w-fit flex-wrap rounded-xl border border-slate-200 bg-white p-1 text-sm">
        {([
          ["menunggu", "Menunggu"],
          ["disetujui", "Disetujui"],
          ["ditolak", "Ditolak"],
          ["all", "Semua"],
        ] as const).map(([k, l]) => (
          <button
            key={k}
            onClick={() => setTab(k)}
            className={`rounded-lg px-3.5 py-1.5 font-medium ${
              tab === k ? "bg-sky-600 text-white" : "text-slate-500"
            }`}
          >
            {l}{" "}
            <span className="opacity-70">
              ({s.requests.filter((r) => k === "all" || r.status === k).length})
            </span>
          </button>
        ))}
      </div>
      {rows.length ? (
        <Card className="overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full min-w-[760px]">
              <thead className="bg-surface-strong">
                <tr>
                  <th className={th}>Tanggal</th>
                  <th className={th}>Pemohon</th>
                  <th className={th}>Dataset</th>
                  <th className={th}>Tujuan</th>
                  <th className={th}>Status</th>
                  <th className={th} />
                </tr>
              </thead>
              <tbody>
                {pg.slice.map((r) => (
                  <tr
                    key={r.id}
                    className="border-t border-slate-200/70 hover:bg-white"
                  >
                    <td className={`${td} whitespace-nowrap`}>
                      {fmtDate(r.at)}
                      <p className="text-xs text-slate-400">{r.id}</p>
                    </td>
                    <td className={td}>
                      <p className="font-semibold text-slate-900">{r.nama}</p>
                      <p className="text-xs text-slate-400">{r.email}</p>
                    </td>
                    <td className={`${td} max-w-[240px]`}>
                      {s.datasets.find((d) => d.id === r.datasetId)?.title ??
                        r.datasetId}
                    </td>
                    <td className={td}>{r.tujuan}</td>
                    <td className={td}>
                      <ReqStatus s={r.status} />
                    </td>
                    <td className={`${td} text-right`}>
                      <button
                        onClick={() => setOpen(r)}
                        className={
                          r.status === "menunggu"
                            ? `${btn.primary} py-2 text-xs`
                            : `${btn.outline} py-2 text-xs`
                        }
                      >
                        {r.status === "menunggu" ? "Tinjau" : "Detail"}
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          <Pager {...pg} />
        </Card>
      ) : (
        <Empty icon={Inbox} title="Tidak ada permohonan" />
      )}
      {open && <Decide r={open} onClose={() => setOpen(null)} />}
    </>
  )
}

// ===== Pengguna & peran (Admin) =====
export function Pengguna() {
  const { s, set, user } = useStore()
  const toast = useToast()
  const [confirmEl, ask] = useConfirm()
  const [q, setQ] = useState("")
  const [role, setRole] = useState<"all" | Role>("all")
  const [edit, setEdit] = useState<Partial<User> & { _new?: boolean } | null>(
    null,
  )
  const rows = s.users.filter(
    (u) =>
      (role === "all" || u.role === role) &&
      `${u.nama} ${u.nim}`.toLowerCase().includes(q.toLowerCase()),
  )
  const pg = usePage(rows, 10)
  return (
    <>
      <PanelHead
        title="Pengguna & Peran"
        desc="Kelola akun dan tetapkan peran (RBAC). Pengguna baru dapat ditambahkan tanpa mengubah kode program."
      >
        <button
          onClick={() =>
            setEdit({
              _new: true,
              role: "produsen",
              jenis: "Mahasiswa",
              aktif: true,
            })
          }
          className={btn.primary}
        >
          <Plus className="h-4 w-4" />
          Tambah pengguna
        </button>
      </PanelHead>
      <div className="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
        {(["produsen", "wali", "admin"] as Role[]).map((r) => (
          <Card key={r} className="flex items-center gap-3 p-4">
            <span className="grid h-10 w-10 place-items-center rounded-xl bg-sky-100/80 text-sky-700">
              <UserCog className="h-5 w-5" />
            </span>
            <div>
              <p className="text-2xl font-extrabold leading-none">
                {s.users.filter((u) => u.role === r).length}
              </p>
              <p className="text-sm text-slate-500">{roleLabel[r]}</p>
            </div>
          </Card>
        ))}
      </div>
      <Card className="mb-4 flex flex-wrap items-center gap-2 p-3">
        <label className="flex h-10 min-w-0 flex-1 items-center gap-2 rounded-xl border border-slate-200 px-3">
          <Search className="h-4 w-4 text-slate-400" />
          <input
            value={q}
            onChange={(e) => setQ(e.target.value)}
            placeholder="Cari nama atau NIM/NIP…"
            className="min-w-0 flex-1 bg-transparent text-sm outline-none"
          />
        </label>
        <select
          value={role}
          onChange={(e) => setRole(e.target.value as Role)}
          className={sel}
        >
          <option value="all">Semua peran</option>
          {(Object.keys(roleLabel) as Role[]).map((r) => (
            <option key={r} value={r}>
              {roleLabel[r]}
            </option>
          ))}
        </select>
      </Card>
      <Card className="overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full min-w-[680px]">
            <thead className="bg-surface-strong">
              <tr>
                <th className={th}>Nama</th>
                <th className={th}>NIM/NIP</th>
                <th className={th}>Peran</th>
                <th className={th}>Status</th>
                <th className={th} />
              </tr>
            </thead>
            <tbody>
              {pg.slice.map((u) => (
                <tr
                  key={u.id}
                  className="border-t border-slate-200/70 hover:bg-white"
                >
                  <td className={`${td} font-semibold text-slate-900`}>
                    {u.nama}
                    {u.id === user!.id && (
                      <span className="ml-2 rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold text-slate-500">
                        ANDA
                      </span>
                    )}
                  </td>
                  <td className={`${td} font-mono text-xs`}>{u.nim}</td>
                  <td className={td}>
                    <span className="rounded-md bg-sky-100/80 px-2 py-0.5 text-xs font-bold text-sky-700">
                      {roleLabel[u.role]}
                    </span>
                    {u.jenis && (
                      <span className="ml-1.5 text-xs text-slate-400">
                        {u.jenis}
                      </span>
                    )}
                  </td>
                  <td className={td}>
                    {u.aktif ? (
                      <span className="text-xs font-bold text-emerald-600">
                        Aktif
                      </span>
                    ) : (
                      <span className="text-xs font-bold text-slate-400">
                        Nonaktif
                      </span>
                    )}
                  </td>
                  <td className={td}>
                    <div className="flex justify-end gap-1.5">
                      <button
                        onClick={() => setEdit({ ...u, password: "" })}
                        className={btn.icon}
                        aria-label="Ubah"
                      >
                        <Pencil className="h-4 w-4" />
                      </button>
                      {u.id !== user!.id && (
                        <button
                          onClick={() =>
                            ask(`Akun “${u.nama}” akan dihapus.`, () => {
                              set((p) => ({
                                ...p,
                                users: p.users.filter((x) => x.id !== u.id),
                              }))
                              toast("Pengguna dihapus.")
                            })
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
      {edit && (
        <EditModal
          title={edit._new ? "Tambah pengguna" : "Ubah pengguna"}
          kicker="Pengguna & peran"
          onClose={() => setEdit(null)}
          value={{
            nama: edit.nama ?? "",
            nim: edit.nim ?? "",
            role: edit.role ?? "produsen",
            jenis: edit.jenis ?? "Mahasiswa",
            password: "",
            aktif: edit.aktif ?? true,
          }}
          specs={[
            { k: "nama", label: "Nama lengkap", required: true, full: true },
            {
              k: "nim",
              label: "NIM/NIP (username)",
              required: true,
              check: (v) =>
                s.users.some((u) => u.nim === v && u.id !== edit.id)
                  ? "NIM/NIP sudah terdaftar."
                  : undefined,
            },
            {
              k: "role",
              label: "Peran",
              type: "select",
              required: true,
              options: (Object.keys(roleLabel) as Role[]).map((r) => [
                r,
                roleLabel[r],
              ]),
            },
            {
              k: "jenis",
              label: "Jenis Produsen Data",
              type: "select",
              required: true,
              options: ["Mahasiswa", "Dosen"],
              when: (v) => v.role === "produsen",
            },
            {
              k: "password",
              label: edit._new ? "Password" : "Password baru",
              type: "password",
              required: !!edit._new,
              hint: edit._new
                ? "Minimal 6 karakter."
                : "Kosongkan jika tidak diubah.",
              check: (v) => (v.length < 6 ? "Minimal 6 karakter." : undefined),
            },
            { k: "aktif", label: "Akun aktif", type: "checkbox" },
          ]}
          onSave={(v) => {
            if (edit.id === user!.id && (v.role !== "admin" || !v.aktif))
              return "Anda tidak dapat menurunkan peran atau menonaktifkan akun sendiri."
            const base = {
              nama: String(v.nama).trim(),
              nim: String(v.nim).trim(),
              role: v.role as Role,
              jenis:
                v.role === "produsen" ? v.jenis as User["jenis"] : undefined,
              aktif: !!v.aktif,
            }
            set((p) => ({
              ...p,
              users: edit._new
                ? [
                    ...p.users,
                    { id: uid("U"), ...base, password: String(v.password) },
                  ]
                : p.users.map((u) =>
                    u.id === edit.id
                      ? {
                          ...u,
                          ...base,
                          ...(v.password
                            ? { password: String(v.password) }
                            : {}),
                        }
                      : u,
                  ),
            }))
            toast("Perubahan berhasil disimpan")
          }}
        />
      )}
      {confirmEl}
    </>
  )
}

// ===== Konten landing page (Admin) =====
export function Konten() {
  const { s, set } = useStore()
  const toast = useToast()
  const [confirmEl, ask] = useConfirm()
  const [tab, setTab] =
    useState<"profil" | "dosen" | "prestasi" | "berita" | "kurikulum">("profil")
  const p = s.content.profil
  const [prof, setProf] = useState({ ...p, misi: p.misi.join("\n") })
  const [dosen, setDosen] = useState<Partial<Dosen> | null>(null)
  const [pres, setPres] = useState<Partial<Prestasi> | null>(null)
  const req = ["deskripsi", "visi", "misi", "akreditasi", "email"] as const
  const missing = req.filter((k) => !String(prof[k]).trim())
  const saveProfil = (e: React.FormEvent) => {
    e.preventDefault()
    if (missing.length)
      return toast("Lengkapi kolom wajib pada profil.", "error")
    if (!/^\S+@\S+\.\S+$/.test(prof.email))
      return toast("Format email profil tidak valid.", "error")
    set((x) => ({
      ...x,
      content: {
        ...x.content,
        profil: {
          ...prof,
          misi: prof.misi
            .split("\n")
            .map((m) => m.trim())
            .filter(Boolean),
        },
      },
    }))
    toast("Profil prodi diperbarui di landing page.")
  }
  return (
    <>
      <PanelHead
        title="Konten Beranda"
        desc="Kelola profil, sejarah, detail prodi, dosen, prestasi, berita, dan kurikulum pada halaman publik."
      />
      <div className="mb-5 flex max-w-full gap-1 overflow-x-auto rounded-xl border border-slate-200 bg-white p-1 text-sm">
        {([
          ["profil", "Profil prodi"],
          ["dosen", `Dosen (${s.content.dosen.length})`],
          ["prestasi", `Prestasi (${s.content.prestasi.length})`],
          ["berita", `Berita (${s.content.berita.length})`],
          ["kurikulum", `Kurikulum (${s.content.kurikulum.length})`],
        ] as const).map(([k, l]) => (
          <button
            key={k}
            onClick={() => setTab(k)}
            className={`shrink-0 rounded-lg px-3.5 py-1.5 font-medium ${
              tab === k ? "bg-sky-600 text-white" : "text-slate-500"
            }`}
          >
            {l}
          </button>
        ))}
      </div>
      {tab === "profil" && (
        <Card className="p-5 sm:p-6">
          <form
            noValidate
            onSubmit={saveProfil}
            className="grid grid-cols-1 gap-5 sm:grid-cols-2"
          >
            <div className="sm:col-span-2">
              <Field label="Deskripsi prodi" required>
                <textarea
                  rows={3}
                  className={inp}
                  value={prof.deskripsi}
                  onChange={(e) =>
                    setProf({ ...prof, deskripsi: e.target.value })
                  }
                />
              </Field>
            </div>
            <div className="sm:col-span-2">
              <Field label="Visi" required>
                <textarea
                  rows={2}
                  className={inp}
                  value={prof.visi}
                  onChange={(e) => setProf({ ...prof, visi: e.target.value })}
                />
              </Field>
            </div>
            <div className="sm:col-span-2">
              <Field label="Misi" required hint="Satu misi per baris.">
                <textarea
                  rows={4}
                  className={inp}
                  value={prof.misi}
                  onChange={(e) => setProf({ ...prof, misi: e.target.value })}
                />
              </Field>
            </div>
            <Field label="Akreditasi" required>
              <input
                className={inp}
                value={prof.akreditasi}
                onChange={(e) =>
                  setProf({ ...prof, akreditasi: e.target.value })
                }
              />
            </Field>
            <Field label="Tahun berdiri">
              <input
                className={inp}
                value={prof.berdiri}
                onChange={(e) => setProf({ ...prof, berdiri: e.target.value })}
              />
            </Field>
            <div className="sm:col-span-2">
              <Field label="Alamat">
                <input
                  className={inp}
                  value={prof.alamat}
                  onChange={(e) => setProf({ ...prof, alamat: e.target.value })}
                />
              </Field>
            </div>
            <Field label="Email" required>
              <input
                type="email"
                className={inp}
                value={prof.email}
                onChange={(e) => setProf({ ...prof, email: e.target.value })}
              />
            </Field>
            <Field label="Telepon">
              <input
                className={inp}
                value={prof.telp}
                onChange={(e) => setProf({ ...prof, telp: e.target.value })}
              />
            </Field>
            {([
              ["sejarah", "Sejarah prodi", true],
              ["program", "Nama program", false],
              ["gelar", "Gelar lulusan", false],
              ["durasi", "Durasi studi", false],
              ["syarat", "Syarat masuk", true],
              ["fakultas", "Fakultas", false],
              ["fasilitas", "Fasilitas", true],
            ] as const).map(([key, label, multiline]) => (
              <div key={key} className={multiline ? "sm:col-span-2" : ""}>
                <Field label={label}>
                  {multiline ? (
                    <textarea
                      rows={3}
                      className={inp}
                      value={prof[key] ?? ""}
                      onChange={(e) =>
                        setProf({ ...prof, [key]: e.target.value })
                      }
                    />
                  ) : (
                    <input
                      className={inp}
                      value={prof[key] ?? ""}
                      onChange={(e) =>
                        setProf({ ...prof, [key]: e.target.value })
                      }
                    />
                  )}
                </Field>
              </div>
            ))}
            <div className="flex justify-end sm:col-span-2">
              <button className={btn.primary}>Simpan profil</button>
            </div>
          </form>
        </Card>
      )}
      {(tab === "berita" || tab === "kurikulum") && (
        <PublicationContent key={tab} tab={tab} />
      )}
      {(tab === "dosen" || tab === "prestasi") && (
        <>
          <div className="mb-3 flex justify-end">
            <button
              onClick={() =>
                tab === "dosen"
                  ? setDosen({})
                  : setPres({
                      tingkat: "Nasional",
                      tahun: new Date().getFullYear(),
                    })
              }
              className={btn.primary}
            >
              <Plus className="h-4 w-4" />
              Tambah {tab}
            </button>
          </div>
          <Card className="overflow-hidden">
            <div className="overflow-x-auto">
              {tab === "dosen" ? (
                s.content.dosen.length ? (
                  <table className="w-full min-w-[640px]">
                    <thead className="bg-surface-strong">
                      <tr>
                        <th className={th}>Nama</th>
                        <th className={th}>NIDN</th>
                        <th className={th}>Jabatan</th>
                        <th className={th}>Bidang</th>
                        <th className={th} />
                      </tr>
                    </thead>
                    <tbody>
                      {s.content.dosen.map((d) => (
                        <tr key={d.id} className="border-t border-slate-100">
                          <td className={`${td} font-semibold text-slate-900`}>
                            {d.nama}
                            <p className="text-xs font-normal text-slate-400">
                              {d.pendidikan}
                            </p>
                          </td>
                          <td className={`${td} font-mono text-xs`}>
                            {d.nidn}
                          </td>
                          <td className={td}>{d.jabatan}</td>
                          <td className={td}>{d.bidang}</td>
                          <td className={td}>
                            <div className="flex justify-end gap-1.5">
                              <button
                                onClick={() => setDosen(d)}
                                className={btn.icon}
                                aria-label="Ubah"
                              >
                                <Pencil className="h-4 w-4" />
                              </button>
                              <button
                                onClick={() =>
                                  ask(
                                    `Data dosen “${d.nama}” akan dihapus dari landing page.`,
                                    () => {
                                      set((x) => ({
                                        ...x,
                                        content: {
                                          ...x.content,
                                          dosen: x.content.dosen.filter(
                                            (y) => y.id !== d.id,
                                          ),
                                        },
                                      }))
                                      toast("Dosen dihapus.")
                                    },
                                  )
                                }
                                className={btn.iconDanger}
                                aria-label="Hapus"
                              >
                                <Trash2 className="h-4 w-4" />
                              </button>
                            </div>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                ) : (
                  <Empty icon={Users} title="Belum ada data dosen" />
                )
              ) : s.content.prestasi.length ? (
                <table className="w-full min-w-[640px]">
                  <thead className="bg-surface-strong">
                    <tr>
                      <th className={th}>Prestasi</th>
                      <th className={th}>Tingkat</th>
                      <th className={th}>Peringkat</th>
                      <th className={th}>Tahun</th>
                      <th className={th} />
                    </tr>
                  </thead>
                  <tbody>
                    {s.content.prestasi.map((d) => (
                      <tr key={d.id} className="border-t border-slate-100">
                        <td className={`${td} font-semibold text-slate-900`}>
                          {d.judul}
                          <p className="text-xs font-normal text-slate-400">
                            {d.nama}
                          </p>
                        </td>
                        <td className={td}>{d.tingkat}</td>
                        <td className={td}>{d.peringkat}</td>
                        <td className={td}>{d.tahun}</td>
                        <td className={td}>
                          <div className="flex justify-end gap-1.5">
                            <button
                              onClick={() => setPres(d)}
                              className={btn.icon}
                              aria-label="Ubah"
                            >
                              <Pencil className="h-4 w-4" />
                            </button>
                            <button
                              onClick={() =>
                                ask(
                                  `Prestasi “${d.judul}” akan dihapus dari landing page.`,
                                  () => {
                                    set((x) => ({
                                      ...x,
                                      content: {
                                        ...x.content,
                                        prestasi: x.content.prestasi.filter(
                                          (y) => y.id !== d.id,
                                        ),
                                      },
                                    }))
                                    toast("Prestasi dihapus.")
                                  },
                                )
                              }
                              className={btn.iconDanger}
                              aria-label="Hapus"
                            >
                              <Trash2 className="h-4 w-4" />
                            </button>
                          </div>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              ) : (
                <Empty icon={Award} title="Belum ada data prestasi" />
              )}
            </div>
          </Card>
        </>
      )}
      {dosen && (
        <EditModal
          title={dosen.id ? "Ubah dosen" : "Tambah dosen"}
          kicker="Konten beranda"
          onClose={() => setDosen(null)}
          value={{
            nama: dosen.nama ?? "",
            nidn: dosen.nidn ?? "",
            jabatan: dosen.jabatan ?? "",
            pendidikan: dosen.pendidikan ?? "S2",
            bidang: dosen.bidang ?? "",
            foto: dosen.foto ?? "",
            email: dosen.email ?? "",
            mataKuliah: dosen.mataKuliah ?? "",
            profil: dosen.profil ?? "",
          }}
          specs={[
            { k: "nama", label: "Nama & gelar", required: true, full: true },
            {
              k: "nidn",
              label: "NIDN",
              required: true,
              check: (v) =>
                /^\d{10}$/.test(v) ? undefined : "NIDN terdiri dari 10 digit.",
            },
            {
              k: "pendidikan",
              label: "Pendidikan",
              type: "select",
              required: true,
              options: ["S2", "S3"],
            },
            {
              k: "jabatan",
              label: "Jabatan fungsional",
              type: "select",
              required: true,
              options: [
                "Tenaga Pengajar",
                "Asisten Ahli",
                "Lektor",
                "Lektor Kepala",
                "Profesor",
              ],
            },
            { k: "bidang", label: "Bidang keahlian", required: true },
            {
              k: "foto",
              label: "URL foto dosen (HTTPS)",
              full: true,
              hint: "Opsional. Inisial digunakan jika foto kosong atau gagal dimuat.",
              check: (v) =>
                /^https:\/\/\S+$/i.test(v)
                  ? undefined
                  : "Gunakan URL gambar HTTPS.",
            },
            {
              k: "email",
              label: "Email",
              hint: "Opsional. Ditampilkan pada detail dosen.",
              check: (v) =>
                /^\S+@\S+\.\S+$/.test(v)
                  ? undefined
                  : "Format email tidak valid.",
            },
            {
              k: "mataKuliah",
              label: "Mata kuliah diampu",
              hint: "Opsional. Pisahkan dengan koma.",
            },
            {
              k: "profil",
              label: "Profil singkat",
              type: "textarea",
              full: true,
              hint: "Opsional. Ditampilkan saat pengunjung menekan Lebih lanjut.",
            },
          ]}
          onSave={(v) => {
            const d = { id: dosen.id ?? uid("D"), ...v as Omit<Dosen, "id"> }
            set((x) => ({
              ...x,
              content: {
                ...x.content,
                dosen: dosen.id
                  ? x.content.dosen.map((y) => (y.id === d.id ? d : y))
                  : [...x.content.dosen, d],
              },
            }))
            toast("Data dosen disimpan.")
          }}
        />
      )}
      {pres && (
        <EditModal
          title={pres.id ? "Ubah prestasi" : "Tambah prestasi"}
          kicker="Konten beranda"
          onClose={() => setPres(null)}
          value={{
            judul: pres.judul ?? "",
            nama: pres.nama ?? "",
            tingkat: pres.tingkat ?? "Nasional",
            peringkat: pres.peringkat ?? "",
            tahun: String(pres.tahun ?? ""),
            foto: pres.foto ?? "",
          }}
          specs={[
            {
              k: "judul",
              label: "Nama kompetisi / prestasi",
              required: true,
              full: true,
            },
            {
              k: "nama",
              label: "Nama mahasiswa / tim",
              required: true,
              full: true,
            },
            {
              k: "tingkat",
              label: "Tingkat",
              type: "select",
              required: true,
              options: ["Regional", "Nasional", "Internasional"],
            },
            { k: "peringkat", label: "Peringkat", required: true },
            {
              k: "tahun",
              label: "Tahun",
              required: true,
              check: (v) => (/^\d{4}$/.test(v) ? undefined : "Tahun 4 digit."),
            },
            {
              k: "foto",
              label: "URL foto prestasi (HTTPS)",
              full: true,
              hint: "Opsional. Ikon piala digunakan jika foto kosong atau gagal dimuat.",
              check: (v) =>
                /^https:\/\/\S+$/i.test(v)
                  ? undefined
                  : "Gunakan URL gambar HTTPS.",
            },
          ]}
          onSave={(v) => {
            const d: Prestasi = {
              id: pres.id ?? uid("P"),
              judul: String(v.judul),
              nama: String(v.nama),
              tingkat: v.tingkat as Prestasi["tingkat"],
              peringkat: String(v.peringkat),
              tahun: Number(v.tahun),
              foto: String(v.foto ?? ""),
            }
            set((x) => ({
              ...x,
              content: {
                ...x.content,
                prestasi: pres.id
                  ? x.content.prestasi.map((y) => (y.id === d.id ? d : y))
                  : [d, ...x.content.prestasi],
              },
            }))
            toast("Prestasi disimpan.")
          }}
        />
      )}
      {confirmEl}
    </>
  )
}
