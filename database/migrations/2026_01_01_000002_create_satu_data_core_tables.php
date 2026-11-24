<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kategori', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('nama_kategori', 120);
            $table->text('deskripsi')->nullable();
            $table->timestamps();
        });

        Schema::create('formulir', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 40)->unique();
            $table->foreignId('id_kategori')->constrained('kategori')->restrictOnDelete();
            $table->string('judul', 255);
            $table->text('deskripsi')->nullable();
            $table->dateTime('tanggal_waktu');
            $table->boolean('status_aktif')->default(true);
            $table->unsignedSmallInteger('tahun');
            $table->string('periode', 30)->default('Ganjil');
            $table->string('target', 30)->default('Semua');
            $table->foreignId('dibuat_oleh')->nullable()->constrained('pengguna')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('kolom_formulir', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('id_formulir')->constrained('formulir')->cascadeOnDelete();
            $table->string('kode_kolom', 60);
            $table->string('label', 255);
            $table->string('tipe_kolom', 30)->default('text');
            $table->boolean('wajib_diisi')->default(false);
            $table->json('opsi')->nullable();
            $table->unsignedInteger('urutan')->default(0);
            $table->timestamps();

            $table->unique(['id_formulir', 'kode_kolom']);
        });

        Schema::create('pengisian_formulir', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 40)->unique();
            $table->foreignId('id_formulir')->constrained('formulir')->cascadeOnDelete();
            $table->foreignId('diisi_oleh')->nullable()->constrained('pengguna')->nullOnDelete();
            $table->dateTime('waktu_pengisian');
            $table->string('jalur_berkas_bukti', 500)->nullable();
            $table->foreignId('dibersihkan_oleh')->nullable()->constrained('pengguna')->nullOnDelete();
            $table->dateTime('waktu_dibersihkan')->nullable();
            $table->timestamps();
        });

        Schema::create('data_isian', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('id_pengisian')->constrained('pengisian_formulir')->cascadeOnDelete();
            $table->foreignId('id_kolom')->constrained('kolom_formulir')->cascadeOnDelete();
            $table->longText('nilai')->nullable();
            $table->boolean('status_bersih')->default(true);
            $table->string('jalur_berkas', 500)->nullable();
            $table->timestamps();

            $table->unique(['id_pengisian', 'id_kolom']);
        });

        Schema::create('dataset', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 40)->unique();
            $table->foreignId('id_kategori')->constrained('kategori')->restrictOnDelete();
            $table->foreignId('id_formulir_sumber')->nullable()->constrained('formulir')->nullOnDelete();
            $table->string('judul', 255);
            $table->text('deskripsi')->nullable();
            $table->string('tahun', 10);
            $table->string('tipe_akses', 20)->default('public');
            $table->string('periode', 30)->default('Ganjil');
            $table->string('format', 20)->default('CSV');
            $table->string('label_diperbarui', 60)->nullable();
            $table->json('kolom');
            $table->longText('baris');
            $table->foreignId('dibuat_oleh')->nullable()->constrained('pengguna')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('permohonan_data', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 40)->unique();
            $table->foreignId('id_dataset')->constrained('dataset')->cascadeOnDelete();
            $table->foreignId('diajukan_oleh')->nullable()->constrained('pengguna')->nullOnDelete();
            $table->string('session_token', 80)->nullable()->index();
            $table->string('nama_pemohon', 160);
            $table->string('email_pemohon', 160);
            $table->string('hp_pemohon', 40);
            $table->string('nim_pemohon', 60);
            $table->string('tujuan', 160);
            $table->text('alasan');
            $table->string('status', 20)->default('menunggu');
            $table->text('catatan_konfirmasi')->nullable();
            $table->foreignId('dikonfirmasi_oleh')->nullable()->constrained('pengguna')->nullOnDelete();
            $table->dateTime('waktu_pengajuan');
            $table->dateTime('waktu_konfirmasi')->nullable();
            $table->timestamps();
        });

        Schema::create('visualisasi_dashboard', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 40)->unique();
            $table->foreignId('id_dataset')->nullable()->constrained('dataset')->cascadeOnDelete();
            $table->string('judul', 255);
            $table->string('tipe_grafik', 30)->default('bar');
            $table->longText('konfigurasi_query');
            $table->foreignId('dibuat_oleh')->nullable()->constrained('pengguna')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visualisasi_dashboard');
        Schema::dropIfExists('permohonan_data');
        Schema::dropIfExists('dataset');
        Schema::dropIfExists('data_isian');
        Schema::dropIfExists('pengisian_formulir');
        Schema::dropIfExists('kolom_formulir');
        Schema::dropIfExists('formulir');
        Schema::dropIfExists('kategori');
    }
};
