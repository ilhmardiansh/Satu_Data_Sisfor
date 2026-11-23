<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ami_indicators', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('kelompok_standar', 80);
            $table->unsignedSmallInteger('standar_nomor')->index();
            $table->string('standar_nama');
            $table->string('no_butir', 20)->nullable();
            $table->text('indikator');
            $table->string('baseline_2023', 120)->nullable();
            $table->string('baseline_2024', 120)->nullable();
            $table->text('keterangan_umum')->nullable();
            $table->text('keterangan_renstra')->nullable();
            $table->text('pengukuran')->nullable();
            $table->string('target_universitas', 120)->nullable();
            $table->string('target_renop_prodi', 120)->nullable();
            $table->string('target_akhir', 120)->nullable();
            $table->text('catatan_perhitungan')->nullable();
            $table->string('realisasi', 120)->nullable();
            $table->text('link_bukti')->nullable();
            $table->string('konfirmasi_auditor', 20)->nullable();
            $table->text('catatan_auditor')->nullable();
            $table->string('status', 60)->nullable()->index();
            $table->decimal('capaian', 10, 4)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('ami_standard_summaries', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('urutan');
            $table->unsignedSmallInteger('standar_nomor')->index();
            $table->string('standar_nama');
            $table->string('kelompok_standar', 80);
            $table->unsignedInteger('jumlah_indikator');
            $table->unsignedInteger('jumlah_tercapai');
            $table->unsignedInteger('jumlah_tidak_tercapai');
            $table->decimal('persentase_tercapai', 8, 4);
            $table->decimal('rata_rata_capaian', 8, 4);
            $table->timestamps();
        });

        Schema::create('ami_benchmarks', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('standar_nomor')->index();
            $table->string('standar_nama');
            $table->string('sub_kelompok', 40)->nullable();
            $table->string('no_butir', 20)->nullable();
            $table->text('indikator');
            $table->json('prodi_values');
            $table->string('nilai_si', 120)->nullable();
            $table->string('rekap_unusa', 120)->nullable();
            $table->string('baseline_2023', 120)->nullable();
            $table->string('target_2024', 120)->nullable();
            $table->text('asal_data_2023')->nullable();
            $table->text('asal_data_2024')->nullable();
            $table->text('catatan_review_prodi')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('ami_ptk_findings', function (Blueprint $table): void {
            $table->id();
            $table->string('jenis_ptk', 40)->index();
            $table->string('tahun_akademik', 40)->index();
            $table->string('no_temuan', 20)->nullable();
            $table->string('kriteria_atau_standar', 100)->nullable();
            $table->string('tanggal_temuan', 40)->nullable();
            $table->text('uraian');
            $table->string('kategori', 40)->nullable()->index();
            $table->text('analisis_penyebab')->nullable();
            $table->text('tindakan_koreksi')->nullable();
            $table->string('rencana_selesai', 60)->nullable();
            $table->string('realisasi_selesai', 60)->nullable();
            $table->string('status', 40)->nullable()->index();
            $table->text('link_bukti')->nullable();
            $table->string('konfirmasi_auditor', 60)->nullable();
            $table->text('catatan')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('ami_recommendations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('no_urut');
            $table->string('aspek_bidang', 150);
            $table->text('kelebihan');
            $table->text('peluang_peningkatan');
            $table->string('tanggal_audit', 60)->nullable();
            $table->string('ketua_spmi', 150)->nullable();
            $table->string('wakil_auditee', 150)->nullable();
            $table->string('auditor_ketua', 150)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ami_recommendations');
        Schema::dropIfExists('ami_ptk_findings');
        Schema::dropIfExists('ami_benchmarks');
        Schema::dropIfExists('ami_standard_summaries');
        Schema::dropIfExists('ami_indicators');
    }
};
