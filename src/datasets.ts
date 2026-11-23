// Kategori data prodi; kategori tanpa dataset ditandai "Segera".
export type Category = {
  code: string
  name: string
  desc: string
}

export const categories: Category[] = [
  {
    code: "KT-MHS-05",
    name: "Beasiswa",
    desc: "Penerima dan sebaran dukungan finansial mahasiswa.",
  },
  {
    code: "KT-MHS-04",
    name: "Prestasi & Rekognisi",
    desc: "Capaian prestasi dan rekognisi mahasiswa.",
  },
  {
    code: "KT-TRD-03",
    name: "Pengabdian Masyarakat",
    desc: "Kegiatan pengabdian yang diinisiasi oleh prodi.",
  },
  {
    code: "KT-TRD-02",
    name: "Penelitian",
    desc: "Penelitian dosen dan mahasiswa prodi.",
  },
  {
    code: "KT-MHS-01",
    name: "Kemahasiswaan",
    desc: "Profil, status, dan keaktifan mahasiswa.",
  },
  {
    code: "KT-AKD-01",
    name: "Akademik",
    desc: "Perkuliahan, IPK, dan kelulusan.",
  },
  {
    code: "KT-MHS-06",
    name: "Alumni",
    desc: "Tracer study dan keterserapan lulusan.",
  },
  { code: "KT-SDM-01", name: "SDM", desc: "Dosen dan tenaga kependidikan." },
  {
    code: "KT-TRD-04",
    name: "Publikasi Ilmiah",
    desc: "Jurnal, prosiding, dan HKI.",
  },
  {
    code: "KT-KS-01",
    name: "Kerjasama",
    desc: "Mitra dan dokumen kerja sama.",
  },
  {
    code: "KT-MTU-01",
    name: "Akreditasi",
    desc: "Dokumen dan capaian akreditasi.",
  },
  {
    code: "KT-KEU-01",
    name: "Keuangan",
    desc: "Anggaran dan realisasi prodi.",
  },
]

export type Dataset = {
  id: string
  tahun: number
  periode: "Ganjil" | "Genap"
  title: string
  kategori: string
  desc: string
  access: "public" | "private"
  updated: string
  format: string
  cols: string[]
  rows: (string | number)[][]
}

export const datasets: Dataset[] = [
  {
    id: "DS-01",
    tahun: 2026,
    periode: "Ganjil",
    kategori: "KT-MHS-05",
    access: "public",
    title: "Rekap penerima beasiswa per tahun",
    desc: "Jumlah penerima beasiswa berdasarkan jenis dan tahun akademik.",
    updated: "10 Okt 2026",
    format: "CSV",
    cols: [
      "Tahun",
      "KIP Kuliah",
      "Prestasi",
      "Yayasan",
      "Mitra Industri",
      "Total",
    ],
    rows: [
      ["2022", 26, 8, 7, 3, 44],
      ["2023", 29, 9, 7, 4, 49],
      ["2024", 33, 10, 8, 5, 56],
      ["2025", 36, 11, 9, 6, 62],
      ["2026", 38, 12, 9, 7, 66],
    ],
  },
  {
    id: "DS-02",
    tahun: 2026,
    periode: "Ganjil",
    kategori: "KT-MHS-05",
    access: "private",
    title: "Daftar nama penerima KIP Kuliah",
    desc: "Data individu penerima KIP Kuliah beserta status keaktifan.",
    updated: "8 Okt 2026",
    format: "XLSX",
    cols: ["NIM", "Nama", "Angkatan", "Status"],
    rows: [
      ["1462200001", "Ahmad Fauzi", 2022, "Aktif"],
      ["1462200014", "Siti Nurhaliza", 2022, "Aktif"],
      ["1462300007", "Rizky Pratama", 2023, "Aktif"],
      ["1462300021", "Dewi Lestari", 2023, "Cuti"],
      ["1462400003", "M. Iqbal", 2024, "Aktif"],
    ],
  },
  {
    id: "DS-03",
    tahun: 2025,
    periode: "Genap",
    kategori: "KT-MHS-04",
    access: "public",
    title: "Prestasi mahasiswa per tingkat",
    desc: "Jumlah prestasi berdasarkan tingkat kompetisi dan bidang.",
    updated: "2 Okt 2026",
    format: "CSV",
    cols: ["Tingkat", "Akademik", "Non-akademik", "Total"],
    rows: [
      ["Internasional", 4, 2, 6],
      ["Nasional", 15, 8, 23],
      ["Regional", 11, 8, 19],
    ],
  },
  {
    id: "DS-04",
    tahun: 2025,
    periode: "Ganjil",
    kategori: "KT-MHS-04",
    access: "private",
    title: "Rincian sertifikat & rekognisi mahasiswa",
    desc: "Detail lomba, peringkat, dan bukti sertifikat per mahasiswa.",
    updated: "1 Okt 2026",
    format: "XLSX",
    cols: ["NIM", "Nama", "Kompetisi", "Peringkat"],
    rows: [
      ["1462200031", "Nabila Putri", "Gemastik UI/UX", "Juara 1"],
      ["1462300012", "Fajar Hidayat", "Hackathon Jatim", "Juara 2"],
      ["1462300044", "Laila Rahma", "Business Plan Nasional", "Finalis"],
    ],
  },
  {
    id: "DS-05",
    tahun: 2026,
    periode: "Ganjil",
    kategori: "KT-TRD-03",
    access: "public",
    title: "Kegiatan pengabdian masyarakat",
    desc: "Judul, mitra, dan lokasi kegiatan PKM dosen prodi.",
    updated: "25 Sep 2026",
    format: "CSV",
    cols: ["Tahun", "Judul", "Mitra", "Lokasi"],
    rows: [
      ["2025", "Digitalisasi pencatatan UMKM", "Desa Wonocolo", "Surabaya"],
      ["2025", "Literasi data untuk guru SMA", "MGMP TIK", "Sidoarjo"],
      ["2026", "Sistem inventaris koperasi", "Koperasi Al-Falah", "Gresik"],
    ],
  },
  {
    id: "DS-06",
    tahun: 2025,
    periode: "Genap",
    kategori: "KT-TRD-03",
    access: "private",
    title: "Laporan keuangan dana PKM",
    desc: "Realisasi anggaran kegiatan pengabdian per judul.",
    updated: "20 Sep 2026",
    format: "XLSX",
    cols: ["Judul", "Sumber dana", "Anggaran", "Realisasi"],
    rows: [
      [
        "Digitalisasi pencatatan UMKM",
        "Internal",
        "Rp 15.000.000",
        "Rp 14.200.000",
      ],
      [
        "Literasi data untuk guru SMA",
        "Hibah Dikti",
        "Rp 40.000.000",
        "Rp 38.750.000",
      ],
    ],
  },
  {
    id: "DS-07",
    tahun: 2025,
    periode: "Ganjil",
    kategori: "KT-TRD-02",
    access: "public",
    title: "Rekap penelitian per skema",
    desc: "Jumlah judul penelitian dosen per sumber pendanaan.",
    updated: "18 Sep 2026",
    format: "CSV",
    cols: ["Tahun", "Hibah eksternal", "Internal", "Mitra", "Total"],
    rows: [
      ["2023", 4, 8, 2, 14],
      ["2024", 5, 9, 3, 17],
      ["2025", 6, 10, 3, 19],
    ],
  },
  {
    id: "DS-08",
    tahun: 2024,
    periode: "Genap",
    kategori: "KT-TRD-02",
    access: "private",
    title: "Proposal & luaran penelitian dosen",
    desc: "Detail judul, ketua peneliti, dana, dan luaran penelitian.",
    updated: "15 Sep 2026",
    format: "XLSX",
    cols: ["Judul", "Ketua", "Dana", "Luaran"],
    rows: [
      [
        "Prediksi retensi mahasiswa berbasis ML",
        "Dr. Andi S.",
        "Rp 75.000.000",
        "Jurnal Q1",
      ],
      [
        "Model adopsi e-government desa",
        "Rina W., M.Kom",
        "Rp 20.000.000",
        "Sinta 2",
      ],
    ],
  },
  {
    id: "DS-09",
    tahun: 2025,
    periode: "Ganjil",
    kategori: "KT-MHS-05",
    access: "public",
    title: "Rekap penerima beasiswa 2025",
    desc: "Penerima beasiswa tahun akademik 2025 per jenis.",
    updated: "12 Feb 2025",
    format: "CSV",
    cols: ["Jenis", "Laki-laki", "Perempuan", "Total"],
    rows: [
      ["KIP Kuliah", 16, 20, 36],
      ["Prestasi", 5, 6, 11],
      ["Yayasan", 4, 5, 9],
      ["Mitra Industri", 3, 3, 6],
    ],
  },
  {
    id: "DS-10",
    tahun: 2024,
    periode: "Genap",
    kategori: "KT-MHS-04",
    access: "public",
    title: "Prestasi mahasiswa 2024",
    desc: "Rekap prestasi mahasiswa tahun 2024 per tingkat.",
    updated: "20 Jan 2025",
    format: "CSV",
    cols: ["Tingkat", "Akademik", "Non-akademik", "Total"],
    rows: [
      ["Internasional", 3, 2, 5],
      ["Nasional", 12, 7, 19],
      ["Regional", 9, 7, 16],
    ],
  },
  {
    id: "DS-11",
    tahun: 2024,
    periode: "Ganjil",
    kategori: "KT-TRD-03",
    access: "public",
    title: "Kegiatan pengabdian masyarakat 2024",
    desc: "Daftar kegiatan PKM dosen prodi tahun 2024.",
    updated: "15 Des 2024",
    format: "CSV",
    cols: ["Tahun", "Judul", "Mitra", "Lokasi"],
    rows: [
      ["2024", "Pelatihan website UMKM", "Kel. Jemur Wonosari", "Surabaya"],
      ["2024", "Aplikasi kas masjid", "Takmir Al-Ikhlas", "Sidoarjo"],
    ],
  },
  {
    id: "DS-12",
    tahun: 2026,
    periode: "Genap",
    kategori: "KT-TRD-02",
    access: "private",
    title: "Usulan penelitian 2026",
    desc: "Usulan penelitian dosen yang sedang diproses.",
    updated: "3 Okt 2026",
    format: "XLSX",
    cols: ["Judul", "Ketua", "Skema", "Status"],
    rows: [
      [
        "Deteksi fraud transaksi digital",
        "Dr. Andi S.",
        "Hibah Dikti",
        "Review",
      ],
      ["Chatbot layanan akademik", "Rina W., M.Kom", "Internal", "Diterima"],
    ],
  },
]
