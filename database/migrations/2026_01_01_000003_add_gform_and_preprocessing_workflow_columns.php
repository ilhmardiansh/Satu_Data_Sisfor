<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('formulir', function (Blueprint $table): void {
            $table->boolean('batasi_satu_respon')->default(false)->after('target');
            $table->string('status_preprocessing', 30)->default('belum')->after('batasi_satu_respon');
            $table->json('ringkasan_preprocessing')->nullable()->after('status_preprocessing');
            $table->foreignId('diproses_oleh')->nullable()->after('ringkasan_preprocessing')->constrained('pengguna')->nullOnDelete();
            $table->dateTime('waktu_diproses')->nullable()->after('diproses_oleh');
        });

        Schema::table('kolom_formulir', function (Blueprint $table): void {
            $table->string('deskripsi_bantuan', 255)->nullable()->after('label');
        });

        Schema::table('pengisian_formulir', function (Blueprint $table): void {
            $table->string('status_validasi', 20)->default('mentah')->after('waktu_pengisian');
            $table->text('catatan_validasi')->nullable()->after('status_validasi');
            $table->string('sumber_input', 20)->default('formulir')->after('catatan_validasi');
        });

        Schema::table('data_isian', function (Blueprint $table): void {
            $table->longText('nilai_mentah')->nullable()->after('nilai');
        });

        Schema::table('dataset', function (Blueprint $table): void {
            $table->string('status_publikasi', 20)->default('terpublikasi')->after('tipe_akses');
            $table->json('konfigurasi_sumber')->nullable()->after('baris');
        });
    }

    public function down(): void
    {
        Schema::table('dataset', function (Blueprint $table): void {
            $table->dropColumn(['status_publikasi', 'konfigurasi_sumber']);
        });

        Schema::table('data_isian', function (Blueprint $table): void {
            $table->dropColumn(['nilai_mentah']);
        });

        Schema::table('pengisian_formulir', function (Blueprint $table): void {
            $table->dropColumn(['status_validasi', 'catatan_validasi', 'sumber_input']);
        });

        Schema::table('kolom_formulir', function (Blueprint $table): void {
            $table->dropColumn(['deskripsi_bantuan']);
        });

        Schema::table('formulir', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('diproses_oleh');
            $table->dropColumn([
                'batasi_satu_respon',
                'status_preprocessing',
                'ringkasan_preprocessing',
                'waktu_diproses',
            ]);
        });
    }
};
