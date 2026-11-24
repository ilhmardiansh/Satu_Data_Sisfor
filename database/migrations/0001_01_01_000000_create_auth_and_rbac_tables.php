<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('peran', function (Blueprint $table): void {
            $table->id();
            $table->string('nama_peran', 40)->unique();
            $table->string('deskripsi', 255);
            $table->timestamps();
        });

        Schema::create('hak_akses', function (Blueprint $table): void {
            $table->id();
            $table->string('nama_hak_akses', 80)->unique();
            $table->timestamps();
        });

        Schema::create('peran_hak_akses', function (Blueprint $table): void {
            $table->foreignId('id_peran')->constrained('peran')->cascadeOnDelete();
            $table->foreignId('id_hak_akses')->constrained('hak_akses')->cascadeOnDelete();
            $table->primary(['id_peran', 'id_hak_akses']);
        });

        Schema::create('pengguna', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('nama_pengguna', 255);
            $table->string('nim', 60)->unique();
            $table->string('email', 255)->nullable()->unique();
            $table->string('kata_sandi', 255);
            $table->foreignId('id_peran')->constrained('peran')->restrictOnDelete();
            $table->string('jenis', 40)->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table): void {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('pengguna');
        Schema::dropIfExists('peran_hak_akses');
        Schema::dropIfExists('hak_akses');
        Schema::dropIfExists('peran');
    }
};
