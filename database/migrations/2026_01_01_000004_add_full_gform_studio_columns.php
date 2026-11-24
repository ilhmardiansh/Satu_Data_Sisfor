<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('formulir', function (Blueprint $table): void {
            $table->json('pengaturan')->nullable()->after('batasi_satu_respon');
        });

        Schema::table('kolom_formulir', function (Blueprint $table): void {
            $table->json('konfigurasi')->nullable()->after('opsi');
        });
    }

    public function down(): void
    {
        Schema::table('kolom_formulir', function (Blueprint $table): void {
            $table->dropColumn(['konfigurasi']);
        });

        Schema::table('formulir', function (Blueprint $table): void {
            $table->dropColumn(['pengaturan']);
        });
    }
};
