export type News = {
  id: string
  badge: string
  corner: string
  date: string
  read: number
  title: string
  ex: string
  au: string
  ini: string
  img: string
  body: string[]
}

export const newsTags = [
  "Prestasi Mahasiswa",
  "Publikasi Dosen",
  "Pengabdian",
  "Akademik",
  "Kegiatan",
]

export const news: News[] = [
  {
    id: "b1",
    badge: "Prestasi Mahasiswa",
    corner: "Tingkat Nasional",
    date: "28 Februari 2025",
    read: 4,
    title:
      "Tim mahasiswa raih juara pertama kompetisi UI/UX tingkat nasional 2025",
    ex: "Inovasi prototipe aplikasi aksesibilitas data bagi penyandang disabilitas mendapat apresiasi juri.",
    au: "Himpunan Mahasiswa",
    ini: "HM",
    img: "https://images.unsplash.com/photo-1523580494863-6f3031224c94?w=1200&q=80",
    body: [
      "Tim mahasiswa Program Studi Sistem Informasi UNUSA berhasil meraih juara pertama pada kompetisi desain UI/UX tingkat nasional 2025. Tim yang terdiri dari tiga mahasiswa angkatan 2022 ini mengusung prototipe aplikasi yang membantu penyandang disabilitas netra mengakses data publik.",
      "Dewan juri menilai karya tersebut unggul dari sisi riset pengguna, konsistensi desain, dan dampak sosial. Prototipe dikembangkan selama tiga bulan dengan melibatkan komunitas disabilitas di Surabaya sebagai responden pengujian.",
      "Ketua Program Studi menyampaikan bahwa capaian ini menjadi bukti kurikulum berbasis proyek mampu mendorong mahasiswa menghasilkan karya yang relevan dengan kebutuhan masyarakat.",
    ],
  },
  {
    id: "b2",
    badge: "Publikasi Dosen",
    corner: "Scopus Q1",
    date: "20 Februari 2025",
    read: 6,
    title:
      "Dosen terbitkan riset 'Big Data Analytics Retensi Mahasiswa' di jurnal bereputasi",
    ex: "Karya ilmiah ini membedah algoritma prediktif berbasis machine learning untuk memetakan risiko studi.",
    au: "Lab Riset Data",
    ini: "LR",
    img: "https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=1200&q=80",
    body: [
      "Dosen Program Studi Sistem Informasi menerbitkan artikel ilmiah pada jurnal internasional bereputasi terindeks Scopus Q1. Penelitian ini memanfaatkan data akademik anonim untuk memprediksi risiko mahasiswa tidak melanjutkan studi.",
      "Model yang dikembangkan mencapai akurasi di atas 88% dan kini diuji coba sebagai sistem peringatan dini bagi dosen pembimbing akademik.",
      "Publikasi ini menambah daftar luaran riset prodi dan menjadi bagian dari roadmap penelitian kelompok keahlian Sains Data.",
    ],
  },
  {
    id: "b3",
    badge: "Pengabdian",
    corner: "Dampak Wilayah",
    date: "15 Februari 2025",
    read: 5,
    title: "Pelatihan tata kelola data UMKM berbasis cloud bersama desa binaan",
    ex: "Puluhan pelaku usaha lokal dibantu menstandarkan pencatatan inventaris dan penjualan digital.",
    au: "Tim Pengabdian",
    ini: "TP",
    img: "https://images.unsplash.com/photo-1552664730-d307ca884978?w=1200&q=80",
    body: [
      "Tim pengabdian masyarakat dosen dan mahasiswa Sistem Informasi menggelar pelatihan tata kelola data bagi pelaku UMKM di desa binaan. Peserta diajak memindahkan pencatatan manual ke aplikasi spreadsheet berbasis cloud.",
      "Selain pelatihan, tim juga menyediakan pendampingan selama satu bulan agar peserta dapat menerapkan pencatatan secara konsisten.",
      "Kegiatan ini merupakan bagian dari program pengabdian tahunan prodi yang berfokus pada literasi digital masyarakat.",
    ],
  },
  {
    id: "b4",
    badge: "Akademik",
    corner: "Akreditasi",
    date: "3 Februari 2025",
    read: 3,
    title: "Prodi Sistem Informasi raih akreditasi Unggul dari LAM INFOKOM",
    ex: "Capaian ini menegaskan mutu tata kelola, kurikulum, dan luaran tridharma prodi.",
    au: "Gugus Penjaminan Mutu",
    ini: "GM",
    img: "https://images.unsplash.com/photo-1562774053-701939374585?w=1200&q=80",
    body: [
      "Program Studi Sistem Informasi UNUSA resmi memperoleh status akreditasi Unggul dari LAM INFOKOM. Penilaian mencakup tata kelola, kurikulum, sumber daya, hingga luaran penelitian dan pengabdian.",
      "Status ini berlaku selama lima tahun dan menjadi landasan prodi untuk memperluas kerja sama nasional maupun internasional.",
    ],
  },
  {
    id: "b5",
    badge: "Kegiatan",
    corner: "Workshop",
    date: "27 Januari 2025",
    read: 4,
    title: "Workshop analitik data dengan Python untuk mahasiswa baru",
    ex: "Lebih dari 150 mahasiswa mengikuti workshop pengenalan analisis data menggunakan Python.",
    au: "Himpunan Mahasiswa",
    ini: "HM",
    img: "https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?w=1200&q=80",
    body: [
      "Himpunan Mahasiswa Sistem Informasi menyelenggarakan workshop analitik data dengan Python bagi mahasiswa baru. Materi meliputi dasar pemrograman, pengolahan data dengan pandas, hingga visualisasi sederhana.",
      "Kegiatan ini diharapkan membekali mahasiswa sejak awal perkuliahan dengan keterampilan yang dibutuhkan industri.",
    ],
  },
  {
    id: "b6",
    badge: "Prestasi Mahasiswa",
    corner: "Internasional",
    date: "12 Januari 2025",
    read: 4,
    title: "Mahasiswa SI lolos program pertukaran pelajar ke Malaysia",
    ex: "Empat mahasiswa akan mengikuti perkuliahan satu semester di universitas mitra.",
    au: "Kantor Urusan Internasional",
    ini: "KI",
    img: "https://images.unsplash.com/photo-1541339907198-e08756dedf3f?w=1200&q=80",
    body: [
      "Empat mahasiswa Sistem Informasi dinyatakan lolos seleksi program pertukaran pelajar ke universitas mitra di Malaysia. Selama satu semester, mereka akan mengikuti perkuliahan dan proyek kolaboratif.",
      "Mata kuliah yang diambil akan dikonversi ke dalam kurikulum prodi melalui skema rekognisi kredit.",
    ],
  },
]
