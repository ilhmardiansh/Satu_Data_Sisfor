import {
  ArrowRight,
  BarChart3,
  CalendarClock,
  ClipboardList,
  Database,
  FilePen,
  FolderTree,
  History,
  Inbox,
  LayoutDashboard,
  LayoutTemplate,
  LogOut,
  RotateCcw,
  ShieldAlert,
  Table2,
  Users,
} from "lucide-react"
import {
  canSee,
  fmtDate,
  isOpen,
  roleLabel,
  useStore,
  type Role,
} from "../store"
import { btn } from "../ui"
import { Konten, Pengguna, Permohonan, ReqStatus } from "./Admin"
import { DataTerkumpul, Kategori, KelolaDataset, Visualisasi } from "./Data"
import { IsiFormulir, KelolaFormulir, left, Riwayat, type Go } from "./Forms"
import { Card, PanelHead, useConfirm } from "./kit"

const all: Role[] = ["produsen", "wali", "admin"]
const staff: Role[] = ["wali", "admin"]
export const sections = [
  {
    id: "ringkasan",
    label: "Ringkasan",
    icon: LayoutDashboard,
    roles: all,
    group: "",
  },
  {
    id: "isi",
    label: "Isi Formulir",
    icon: FilePen,
    roles: ["produsen"] as Role[],
    group: "Pengumpulan data",
  },
  {
    id: "formulir",
    label: "Kelola Formulir",
    icon: ClipboardList,
    roles: staff,
    group: "Pengumpulan data",
  },
  {
    id: "riwayat",
    label: "Riwayat Formulir",
    icon: History,
    roles: all,
    group: "Pengumpulan data",
  },
  {
    id: "data",
    label: "Data Terkumpul",
    icon: Database,
    roles: staff,
    group: "Pengelolaan data",
  },
  {
    id: "kategori",
    label: "Kategori",
    icon: FolderTree,
    roles: staff,
    group: "Pengelolaan data",
  },
  {
    id: "dataset",
    label: "Kelola Dataset",
    icon: Table2,
    roles: staff,
    group: "Pengelolaan data",
  },
  {
    id: "visualisasi",
    label: "Visualisasi",
    icon: BarChart3,
    roles: staff,
    group: "Pengelolaan data",
  },
  {
    id: "permohonan",
    label: "Permohonan Data",
    icon: Inbox,
    roles: staff,
    group: "Pengelolaan data",
  },
  {
    id: "pengguna",
    label: "Pengguna & Peran",
    icon: Users,
    roles: ["admin"] as Role[],
    group: "Administrasi",
  },
  {
    id: "konten",
    label: "Konten Beranda",
    icon: LayoutTemplate,
    roles: ["admin"] as Role[],
    group: "Administrasi",
  },
]

function Stat({
  icon: Icon,
  n,
  label,
  onClick,
}: {
  icon: typeof Inbox
  n: number | string
  label: string
  onClick?: () => void
}) {
  return (
    <button
      onClick={onClick}
      className="flex items-center gap-4 rounded-2xl border border-slate-200 bg-surface p-5 text-left transition hover:border-sky-300 hover:shadow-md"
    >
      <span className="grid h-11 w-11 place-items-center rounded-xl bg-sky-100/80 text-sky-700">
        <Icon className="h-5 w-5" />
      </span>
      <div>
        <p className="text-[28px] leading-none font-extrabold text-slate-900">
          {n}
        </p>
        <p className="mt-1 text-sm text-slate-500">{label}</p>
      </div>
    </button>
  )
}

function Ringkasan({ go }: { go: Go }) {
  const { s, user, reset } = useStore()
  const [confirmEl, ask] = useConfirm()
  const u = user!
  if (u.role === "produsen") {
    const open = s.forms
      .filter((f) => canSee(u, f) && isOpen(f))
      .sort((a, b) => a.deadline.localeCompare(b.deadline))
    const mine = s.submissions.filter((x) => x.userId === u.id)
    return (
      <>
        <PanelHead
          title={`Halo, ${u.nama.split(" ")[0]}`}
          desc={`${roleLabel[u.role]} · ${u.jenis} · ${u.nim}`}
        />
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <Stat
            icon={FilePen}
            n={open.length}
            label="Formulir yang dibuka"
            onClick={() => go("isi")}
          />
          <Stat
            icon={History}
            n={mine.length}
            label="Formulir telah dikirim"
            onClick={() => go("riwayat")}
          />
        </div>
        <h2 className="mt-8 mb-3 font-bold">Tenggat terdekat</h2>
        <Card className="divide-y divide-slate-100">
          {open.slice(0, 4).map((f) => (
            <button
              key={f.id}
              onClick={() => go("isi")}
              className="flex w-full items-center gap-4 px-5 py-4 text-left hover:bg-white/70"
            >
              <CalendarClock className="h-5 w-5 text-amber-600" />
              <div className="min-w-0 flex-1">
                <p className="truncate font-semibold">{f.title}</p>
                <p className="text-xs text-slate-500">
                  Tenggat {fmtDate(f.deadline, true)}
                </p>
              </div>
              <span className="text-xs font-bold text-amber-700">
                {left(f.deadline)}
              </span>
            </button>
          ))}
          {!open.length && (
            <p className="px-5 py-6 text-sm text-slate-500">
              Tidak ada formulir yang sedang dibuka.
            </p>
          )}
        </Card>
      </>
    )
  }
  const pending = s.requests.filter((r) => r.status === "menunggu")
  const recent = [...s.submissions]
    .sort((a, b) => b.at.localeCompare(a.at))
    .slice(0, 5)
  return (
    <>
      <PanelHead
        title={`Halo, ${u.nama.split(/[ ,]/)[0]}`}
        desc={`${roleLabel[u.role]} · ringkasan pengelolaan Satu Data`}
      >
        {u.role === "admin" && (
          <button
            onClick={() =>
              ask(
                "Seluruh perubahan pada prototipe ini akan dikembalikan ke data contoh awal.",
                reset,
              )
            }
            className={btn.outline}
          >
            <RotateCcw className="h-4 w-4" />
            Reset data contoh
          </button>
        )}
      </PanelHead>
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <Stat
          icon={ClipboardList}
          n={s.forms.filter(isOpen).length}
          label="Formulir dibuka"
          onClick={() => go("formulir")}
        />
        <Stat
          icon={Database}
          n={s.submissions.length}
          label="Isian terkumpul"
          onClick={() => go("data")}
        />
        <Stat
          icon={Table2}
          n={`${s.datasets.filter((d) => d.access === "public").length}/${s.datasets.length}`}
          label="Dataset publik / total"
          onClick={() => go("dataset")}
        />
        <Stat
          icon={Inbox}
          n={pending.length}
          label="Permohonan menunggu"
          onClick={() => go("permohonan")}
        />
      </div>
      <div className="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div className="min-w-0">
          <div className="mb-3 flex items-center justify-between">
            <h2 className="font-bold">Kiriman terbaru</h2>
            <button
              onClick={() => go("riwayat")}
              className="flex items-center gap-1 text-sm font-semibold text-sky-600"
            >
              Riwayat
              <ArrowRight className="h-4 w-4" />
            </button>
          </div>
          <Card className="divide-y divide-slate-100">
            {recent.map((x) => {
              const f = s.forms.find((y) => y.id === x.formId)
              const usr = s.users.find((y) => y.id === x.userId)
              return (
                <div key={x.id} className="px-5 py-3.5">
                  <p className="truncate text-sm font-semibold">{f?.title}</p>
                  <p className="text-xs text-slate-500">
                    {usr?.nama} · {fmtDate(x.at, true)}
                  </p>
                </div>
              )
            })}
          </Card>
        </div>
        <div className="min-w-0">
          <div className="mb-3 flex items-center justify-between">
            <h2 className="font-bold">Permohonan perlu konfirmasi</h2>
            <button
              onClick={() => go("permohonan")}
              className="flex items-center gap-1 text-sm font-semibold text-sky-600"
            >
              Semua
              <ArrowRight className="h-4 w-4" />
            </button>
          </div>
          <Card className="divide-y divide-slate-100">
            {pending.slice(0, 5).map((r) => (
              <button
                key={r.id}
                onClick={() => go("permohonan")}
                className="flex w-full items-center gap-3 px-5 py-3.5 text-left hover:bg-white/70"
              >
                <div className="min-w-0 flex-1">
                  <p className="truncate text-sm font-semibold">
                    {s.datasets.find((d) => d.id === r.datasetId)?.title}
                  </p>
                  <p className="text-xs text-slate-500">
                    {r.nama} · {fmtDate(r.at)}
                  </p>
                </div>
                <ReqStatus s={r.status} />
              </button>
            ))}
            {!pending.length && (
              <p className="px-5 py-6 text-sm text-slate-500">
                Semua permohonan sudah ditangani.
              </p>
            )}
          </Card>
        </div>
      </div>
      {confirmEl}
    </>
  )
}

export default function Panel({
  sec,
  arg,
  go,
}: {
  sec: string
  arg?: string
  go: Go
}) {
  const { s, user, logout } = useStore()
  const u = user!
  const mine = sections.filter((x) => x.roles.includes(u.role))
  const cur = sections.find((x) => x.id === sec) ?? sections[0]
  // Pemeriksaan otorisasi per halaman, bukan hanya menyembunyikan menu.
  const allowed = cur.roles.includes(u.role)
  const pending = s.requests.filter((r) => r.status === "menunggu").length
  const groups = [...new Set(mine.map((x) => x.group))]
  const view = {
    ringkasan: <Ringkasan go={go} />,
    isi: <IsiFormulir />,
    riwayat: <Riwayat />,
    formulir: <KelolaFormulir go={go} />,
    data: <DataTerkumpul key={arg ?? "all"} arg={arg} />,
    kategori: <Kategori />,
    dataset: <KelolaDataset />,
    visualisasi: <Visualisasi />,
    permohonan: <Permohonan />,
    pengguna: <Pengguna />,
    konten: <Konten />,
  }[cur.id]

  return (
    // grid-cols-1 eksplisit mencegah menu geser horizontal melebarkan kolom di layar kecil.
    <div className="mx-auto grid max-w-[1200px] grid-cols-1 gap-6 px-4 py-8 sm:px-8 lg:grid-cols-[240px_minmax(0,1fr)]">
      <aside className="min-w-0 lg:sticky lg:top-24 lg:self-start">
        <div className="rounded-2xl border border-slate-200 bg-surface p-4">
          <div className="flex items-center gap-3 border-b border-slate-100 pb-4">
            <span className="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-sky-600 text-sm font-bold text-white">
              {u.nama
                .replace(/^(Dr|Prof)\.?\s*/i, "")
                .split(" ")
                .slice(0, 2)
                .map((w) => w[0])
                .join("")}
            </span>
            <div className="min-w-0">
              <p className="truncate text-sm font-bold">{u.nama}</p>
              <p className="text-xs text-slate-500">
                {roleLabel[u.role]}
                {u.jenis ? ` · ${u.jenis}` : ""}
              </p>
            </div>
          </div>
          <nav
            className="mt-3 flex gap-1 overflow-x-auto lg:block lg:space-y-4 lg:overflow-visible"
            aria-label="Menu panel"
          >
            {groups.map((g) => (
              <div
                key={g || "top"}
                className="flex gap-1 lg:block lg:space-y-0.5"
              >
                {g && (
                  <p className="hidden px-3 pb-1 text-[11px] font-bold tracking-wider text-slate-400 uppercase lg:block">
                    {g}
                  </p>
                )}
                {mine
                  .filter((x) => x.group === g)
                  .map((x) => (
                    <button
                      key={x.id}
                      onClick={() => go(x.id)}
                      className={`flex shrink-0 items-center gap-2.5 rounded-xl px-3 py-2 text-left text-sm whitespace-nowrap transition lg:w-full ${
                        cur.id === x.id
                          ? "bg-sky-100/80 font-bold text-sky-700"
                          : "text-slate-600 hover:bg-slate-100"
                      }`}
                    >
                      <x.icon className="h-4 w-4 shrink-0" />
                      <span className="flex-1">{x.label}</span>
                      {x.id === "permohonan" && pending > 0 && (
                        <span className="rounded-full bg-[#DC3545] px-1.5 text-[11px] font-bold text-white">
                          {pending}
                        </span>
                      )}
                    </button>
                  ))}
              </div>
            ))}
          </nav>
          <button
            onClick={logout}
            className="mt-4 flex w-full items-center gap-2.5 rounded-xl border-t border-slate-100 px-3 pt-4 pb-1 text-sm font-semibold text-slate-600 hover:text-[#DC3545]"
          >
            <LogOut className="h-4 w-4" />
            Keluar
          </button>
        </div>
      </aside>
      <section key={cur.id} className="min-w-0 motion-safe:animate-page-in">
        {allowed ? (
          view
        ) : (
          <Card className="px-6 py-16 text-center">
            <ShieldAlert className="mx-auto h-10 w-10 text-[#DC3545]" />
            <h1 className="mt-4 text-xl font-bold">Akses ditolak</h1>
            <p className="mt-1 text-sm text-slate-500">
              Halaman “{cur.label}” hanya untuk{" "}
              {cur.roles.map((r) => roleLabel[r]).join(", ")}.
            </p>
            <button
              onClick={() => go("ringkasan")}
              className={`${btn.primary} mt-6`}
            >
              Ke ringkasan
            </button>
          </Card>
        )}
      </section>
    </div>
  )
}
