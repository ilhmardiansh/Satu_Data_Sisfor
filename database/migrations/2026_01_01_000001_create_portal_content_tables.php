<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profil_prodi', function (Blueprint $table): void {
            $table->id();
            $table->text('sejarah')->nullable();
            $table->json('visi_misi');
            $table->text('deskripsi_prodi');
            $table->string('gelar', 120)->nullable();
            $table->string('durasi_studi', 120)->nullable();
            $table->text('persyaratan')->nullable();
            $table->string('lokasi_kampus', 255)->nullable();
            $table->string('fakultas', 180)->nullable();
            $table->text('fasilitas')->nullable();
            $table->json('kontak');
            $table->string('nama_prodi', 120)->nullable();
            $table->string('akreditasi', 80)->nullable();
            $table->string('tahun_berdiri', 16)->nullable();
            $table->foreignId('dikelola_oleh')->nullable()->constrained('pengguna')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('berita', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('judul', 255);
            $table->longText('isi_berita');
            $table->date('tanggal_publikasi')->nullable();
            $table->string('penulis', 120);
            $table->string('kategori_badge', 60)->default('Kegiatan');
            $table->string('label_pojok', 60)->nullable();
            $table->string('label_tanggal', 60)->nullable();
            $table->unsignedSmallInteger('menit_baca')->default(3);
            $table->text('ringkasan')->nullable();
            $table->string('inisial_penulis', 8)->default('SD');
            $table->string('jalur_gambar', 500)->nullable();
            $table->foreignId('dikelola_oleh')->nullable()->constrained('pengguna')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('kurikulum', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('kode_mk', 30);
            $table->string('nama_mk', 180);
            $table->unsignedTinyInteger('sks');
            $table->unsignedTinyInteger('semester');
            $table->string('tahun', 10);
            $table->boolean('pilihan')->default(false);
            $table->foreignId('dikelola_oleh')->nullable()->constrained('pengguna')->nullOnDelete();
            $table->timestamps();

            $table->unique(['tahun', 'kode_mk']);
        });

        Schema::create('dosen', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('nama', 160);
            $table->string('gelar', 120)->nullable();
            $table->string('jalur_foto', 500)->nullable();
            $table->string('nidn', 40)->nullable();
            $table->string('jabatan', 120)->nullable();
            $table->string('pendidikan', 20)->default('S2');
            $table->string('bidang', 160)->nullable();
            $table->string('email', 160)->nullable();
            $table->string('mata_kuliah', 500)->nullable();
            $table->text('profil')->nullable();
            $table->unsignedInteger('urutan')->default(0);
            $table->foreignId('dikelola_oleh')->nullable()->constrained('pengguna')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('prestasi', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('judul', 255);
            $table->text('deskripsi')->nullable();
            $table->date('tanggal')->nullable();
            $table->string('jalur_gambar', 500)->nullable();
            $table->string('nama_peraih', 180);
            $table->string('tingkat', 60)->default('Nasional');
            $table->string('peringkat', 80)->nullable();
            $table->unsignedSmallInteger('tahun');
            $table->foreignId('dikelola_oleh')->nullable()->constrained('pengguna')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prestasi');
        Schema::dropIfExists('dosen');
        Schema::dropIfExists('kurikulum');
        Schema::dropIfExists('berita');
        Schema::dropIfExists('profil_prodi');
    }
};
