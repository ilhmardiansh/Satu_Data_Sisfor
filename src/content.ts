import { news as starterNews, type News } from "./news"

// Konten landing page (contoh). Dapat diubah Admin melalui panel "Konten Beranda".
export type Profil = {
  deskripsi: string
  visi: string
  misi: string[]
  akreditasi: string
  berdiri: string
  alamat: string
  email: string
  telp: string
  sejarah?: string
  program?: string
  gelar?: string
  durasi?: string
  syarat?: string
  fakultas?: string
  fasilitas?: string
}
export type Dosen = {
  id: string
  nama: string
  nidn: string
  jabatan: string
  pendidikan: string
  bidang: string
  foto?: string
  email?: string
  mataKuliah?: string
  profil?: string
}
export type Course = {
  id: string
  tahun: number
  semester: number
  kode: string
  nama: string
  sks: number
  pilihan: boolean
}
export type Prestasi = {
  id: string
  judul: string
  nama: string
  tingkat: "Regional" | "Nasional" | "Internasional"
  peringkat: string
  tahun: number
  foto?: string
}
export type Content = {
  profil: Profil
  dosen: Dosen[]
  prestasi: Prestasi[]
  berita: News[]
  kurikulum: Course[]
  // Penanda agar contoh kurikulum hanya diisikan sekali ke data browser lama.
  kurikulumSeeded?: boolean
  fotoSeeded?: boolean
}

export const content: Content = {
  berita: starterNews,
  // Contoh kurikulum; ganti dengan kurikulum resmi melalui panel Admin.
  kurikulum: [
    {
      id: "MK-SI101",
      tahun: 2024,
      semester: 1,
      kode: "SI101",
      nama: "Pengantar Sistem Informasi",
      sks: 3,
      pilihan: false,
    },
    {
      id: "MK-SI102",
      tahun: 2024,
      semester: 1,
      kode: "SI102",
      nama: "Algoritma dan Pemrograman",
      sks: 4,
      pilihan: false,
    },
    {
      id: "MK-SI103",
      tahun: 2024,
      semester: 1,
      kode: "SI103",
      nama: "Matematika Diskrit",
      sks: 3,
      pilihan: false,
    },
    {
      id: "MK-SI104",
      tahun: 2024,
      semester: 1,
      kode: "SI104",
      nama: "Pendidikan Aswaja",
      sks: 2,
      pilihan: false,
    },
    {
      id: "MK-SI201",
      tahun: 2024,
      semester: 2,
      kode: "SI201",
      nama: "Struktur Data",
      sks: 3,
      pilihan: false,
    },
    {
      id: "MK-SI202",
      tahun: 2024,
      semester: 2,
      kode: "SI202",
      nama: "Basis Data",
      sks: 3,
      pilihan: false,
    },
    {
      id: "MK-SI203",
      tahun: 2024,
      semester: 2,
      kode: "SI203",
      nama: "Statistika",
      sks: 3,
      pilihan: false,
    },
    {
      id: "MK-SI204",
      tahun: 2024,
      semester: 2,
      kode: "SI204",
      nama: "Proses Bisnis",
      sks: 3,
      pilihan: false,
    },
    {
      id: "MK-SI301",
      tahun: 2024,
      semester: 3,
      kode: "SI301",
      nama: "Pemrograman Web",
      sks: 3,
      pilihan: false,
    },
    {
      id: "MK-SI302",
      tahun: 2024,
      semester: 3,
      kode: "SI302",
      nama: "Analisis dan Perancangan Sistem",
      sks: 3,
      pilihan: false,
    },
    {
      id: "MK-SI303",
      tahun: 2024,
      semester: 3,
      kode: "SI303",
      nama: "Jaringan Komputer",
      sks: 3,
      pilihan: false,
    },
    {
      id: "MK-SI304",
      tahun: 2024,
      semester: 3,
      kode: "SI304",
      nama: "Sistem Informasi Manajemen",
      sks: 3,
      pilihan: false,
    },
    {
      id: "MK-SI401",
      tahun: 2024,
      semester: 4,
      kode: "SI401",
      nama: "Rekayasa Perangkat Lunak",
      sks: 3,
      pilihan: false,
    },
    {
      id: "MK-SI402",
      tahun: 2024,
      semester: 4,
      kode: "SI402",
      nama: "Interaksi Manusia dan Komputer",
      sks: 3,
      pilihan: false,
    },
    {
      id: "MK-SI403",
      tahun: 2024,
      semester: 4,
      kode: "SI403",
      nama: "Manajemen Proyek SI",
      sks: 3,
      pilihan: false,
    },
    {
      id: "MK-SI404",
      tahun: 2024,
      semester: 4,
      kode: "SI404",
      nama: "Data Warehouse",
      sks: 3,
      pilihan: false,
    },
    {
      id: "MK-SI501",
      tahun: 2024,
      semester: 5,
      kode: "SI501",
      nama: "Tata Kelola TI",
      sks: 3,
      pilihan: false,
    },
    {
      id: "MK-SI502",
      tahun: 2024,
      semester: 5,
      kode: "SI502",
      nama: "Analitika Data",
      sks: 3,
      pilihan: false,
    },
    {
      id: "MK-SI503",
      tahun: 2024,
      semester: 5,
      kode: "SI503",
      nama: "Enterprise Resource Planning",
      sks: 3,
      pilihan: false,
    },
    {
      id: "MK-SI551",
      tahun: 2024,
      semester: 5,
      kode: "SI551",
      nama: "Machine Learning",
      sks: 3,
      pilihan: true,
    },
    {
      id: "MK-SI601",
      tahun: 2024,
      semester: 6,
      kode: "SI601",
      nama: "Audit Sistem Informasi",
      sks: 3,
      pilihan: false,
    },
    {
      id: "MK-SI602",
      tahun: 2024,
      semester: 6,
      kode: "SI602",
      nama: "Keamanan Sistem Informasi",
      sks: 3,
      pilihan: false,
    },
    {
      id: "MK-SI603",
      tahun: 2024,
      semester: 6,
      kode: "SI603",
      nama: "Metodologi Penelitian",
      sks: 2,
      pilihan: false,
    },
    {
      id: "MK-SI651",
      tahun: 2024,
      semester: 6,
      kode: "SI651",
      nama: "UI/UX Design",
      sks: 3,
      pilihan: true,
    },
    {
      id: "MK-SI701",
      tahun: 2024,
      semester: 7,
      kode: "SI701",
      nama: "Kerja Praktik",
      sks: 3,
      pilihan: false,
    },
    {
      id: "MK-SI702",
      tahun: 2024,
      semester: 7,
      kode: "SI702",
      nama: "Kuliah Kerja Nyata",
      sks: 3,
      pilihan: false,
    },
    {
      id: "MK-SI751",
      tahun: 2024,
      semester: 7,
      kode: "SI751",
      nama: "E-Government",
      sks: 3,
      pilihan: true,
    },
    {
      id: "MK-SI752",
      tahun: 2024,
      semester: 7,
      kode: "SI752",
      nama: "Business Intelligence",
      sks: 3,
      pilihan: true,
    },
    {
      id: "MK-SI801",
      tahun: 2024,
      semester: 8,
      kode: "SI801",
      nama: "Skripsi",
      sks: 6,
      pilihan: false,
    },
  ],
  kurikulumSeeded: true,
  fotoSeeded: true,
  profil: {
    deskripsi:
      "Program Studi Sistem Informasi Universitas Nahdlatul Ulama Surabaya menyiapkan lulusan yang mampu merancang, membangun, dan mengelola sistem informasi untuk mendukung proses bisnis organisasi dengan berlandaskan nilai-nilai Ahlussunnah wal Jamaah.",
    visi: "Menjadi program studi Sistem Informasi yang unggul dalam pengembangan sistem informasi bisnis dan tata kelola data berbasis nilai Islam Ahlussunnah wal Jamaah di tingkat nasional pada tahun 2030.",
    misi: [
      "Menyelenggarakan pendidikan sistem informasi yang berkualitas dan relevan dengan kebutuhan industri.",
      "Melaksanakan penelitian di bidang sistem informasi, analitika data, dan transformasi digital.",
      "Melaksanakan pengabdian kepada masyarakat melalui penerapan teknologi informasi tepat guna.",
      "Menjalin kerja sama dengan pemerintah, industri, dan perguruan tinggi di dalam maupun luar negeri.",
    ],
    akreditasi: "Baik Sekali",
    berdiri: "2013",
    alamat:
      "Kampus B UNUSA, Jl. Jemursari No. 51–57, Surabaya, Jawa Timur 60237",
    email: "si@unusa.ac.id",
    telp: "(031) 8291920",
  },
  // Foto dosen & prestasi adalah foto ilustrasi Unsplash; ganti dengan foto asli via Admin.
  dosen: [
    {
      id: "D-01",
      nama: "Dr. Andi Setiawan, M.Kom",
      nidn: "0712048601",
      jabatan: "Lektor Kepala",
      pendidikan: "S3",
      bidang: "Machine Learning",
      foto: "https://images.unsplash.com/photo-1600896997793-b8ed3459a17f?auto=format&fit=crop&crop=faces&w=400&h=400&q=80",
    },
    {
      id: "D-02",
      nama: "Rina Wulandari, M.Kom",
      nidn: "0703108802",
      jabatan: "Lektor",
      pendidikan: "S2",
      bidang: "E-Government",
      foto: "https://images.unsplash.com/photo-1585917170055-e40d30c73414?auto=format&fit=crop&crop=faces&w=400&h=400&q=80",
    },
    {
      id: "D-03",
      nama: "Dr. Moch. Yusuf Hidayat, M.T",
      nidn: "0721078503",
      jabatan: "Lektor",
      pendidikan: "S3",
      bidang: "Tata Kelola TI",
      foto: "https://images.unsplash.com/photo-1720501828093-c792c10e3f0b?auto=format&fit=crop&crop=faces&w=400&h=400&q=80",
    },
    {
      id: "D-04",
      nama: "Siti Aminah, S.Kom., M.Kom",
      nidn: "0715029004",
      jabatan: "Asisten Ahli",
      pendidikan: "S2",
      bidang: "Rekayasa Perangkat Lunak",
      foto: "https://images.unsplash.com/photo-1750231413225-6513a6622a2a?auto=format&fit=crop&crop=faces&w=400&h=400&q=80",
    },
    {
      id: "D-05",
      nama: "Fitri Handayani, M.Sc",
      nidn: "0709119105",
      jabatan: "Asisten Ahli",
      pendidikan: "S2",
      bidang: "Analitika Data",
      foto: "https://images.unsplash.com/photo-1611682060567-d7b07878d380?auto=format&fit=crop&crop=faces&w=400&h=400&q=80",
    },
    {
      id: "D-06",
      nama: "Ahmad Zainuri, M.Kom",
      nidn: "0728058706",
      jabatan: "Lektor",
      pendidikan: "S2",
      bidang: "Basis Data",
      foto: "https://images.unsplash.com/photo-1681097561932-36d0df02b379?auto=format&fit=crop&crop=faces&w=400&h=400&q=80",
    },
    {
      id: "D-07",
      nama: "Dr. Laila Nurhayati, M.M",
      nidn: "0711068407",
      jabatan: "Lektor Kepala",
      pendidikan: "S3",
      bidang: "Sistem Informasi Manajemen",
      foto: "https://images.unsplash.com/photo-1690166444476-8cc4c0f24032?auto=format&fit=crop&crop=faces&w=400&h=400&q=80",
    },
    {
      id: "D-08",
      nama: "Bagus Prasetyo, M.Kom",
      nidn: "0730049208",
      jabatan: "Tenaga Pengajar",
      pendidikan: "S2",
      bidang: "UI/UX Design",
      foto: "https://images.unsplash.com/photo-1543132220-3ec99c6094dc?auto=format&fit=crop&crop=faces&w=400&h=400&q=80",
    },
  ],
  prestasi: [
    {
      id: "P-01",
      judul: "Gemastik — Desain Pengalaman Pengguna",
      nama: "Nabila Putri dkk.",
      tingkat: "Nasional",
      peringkat: "Juara 1",
      tahun: 2025,
      foto: "https://images.unsplash.com/photo-1576267423048-15c0040fec78?auto=format&fit=crop&w=800&h=500&q=80",
    },
    {
      id: "P-02",
      judul: "Hackathon Smart City Jawa Timur",
      nama: "Fajar Hidayat dkk.",
      tingkat: "Regional",
      peringkat: "Juara 2",
      tahun: 2025,
      foto: "https://images.unsplash.com/photo-1695067058684-da5a90013c57?auto=format&fit=crop&w=800&h=500&q=80",
    },
    {
      id: "P-03",
      judul: "ASEAN Data Science Explorers",
      nama: "Tim Data UNUSA",
      tingkat: "Internasional",
      peringkat: "Finalis",
      tahun: 2025,
      foto: "https://images.unsplash.com/photo-1725272333872-4c2d274ad493?auto=format&fit=crop&w=800&h=500&q=80",
    },
    {
      id: "P-04",
      judul: "Business Plan Competition Nasional",
      nama: "Laila Rahma dkk.",
      tingkat: "Nasional",
      peringkat: "Juara 3",
      tahun: 2024,
      foto: "https://images.unsplash.com/photo-1749723477883-4e3dde4e3a28?auto=format&fit=crop&w=800&h=500&q=80",
    },
  ],
}
