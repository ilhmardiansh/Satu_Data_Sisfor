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
        Schema::create('program_profiles', function (Blueprint $table): void {
            $table->id();
            $table->text('deskripsi');
            $table->text('visi');
            $table->json('misi');
            $table->string('akreditasi');
            $table->string('berdiri', 20)->nullable();
            $table->string('alamat')->nullable();
            $table->string('email');
            $table->string('telp', 50)->nullable();
            $table->text('sejarah')->nullable();
            $table->string('program')->nullable();
            $table->string('gelar')->nullable();
            $table->string('durasi')->nullable();
            $table->text('syarat')->nullable();
            $table->string('fakultas')->nullable();
            $table->text('fasilitas')->nullable();
            $table->timestamps();
        });

        Schema::create('lecturers', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('nama');
            $table->string('nidn', 20);
            $table->string('jabatan');
            $table->string('pendidikan', 20);
            $table->string('bidang');
            $table->string('foto')->nullable();
            $table->string('email')->nullable();
            $table->string('mata_kuliah')->nullable();
            $table->text('profil')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('achievements', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('judul');
            $table->string('nama');
            $table->string('tingkat', 30);
            $table->string('peringkat', 50);
            $table->unsignedSmallInteger('tahun');
            $table->string('foto')->nullable();
            $table->timestamps();
        });

        Schema::create('news_articles', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('badge', 60);
            $table->string('corner', 60)->nullable();
            $table->string('date_label', 60);
            $table->unsignedSmallInteger('read_minutes')->default(3);
            $table->string('title');
            $table->text('excerpt');
            $table->string('author');
            $table->string('author_initials', 10);
            $table->string('image_url');
            $table->json('body');
            $table->timestamps();
        });

        Schema::create('courses', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 40)->unique();
            $table->unsignedSmallInteger('tahun');
            $table->unsignedTinyInteger('semester');
            $table->string('kode', 30);
            $table->string('nama');
            $table->unsignedTinyInteger('sks');
            $table->boolean('pilihan')->default(false);
            $table->timestamps();

            $table->unique(['tahun', 'kode']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
        Schema::dropIfExists('news_articles');
        Schema::dropIfExists('achievements');
        Schema::dropIfExists('lecturers');
        Schema::dropIfExists('program_profiles');
    }
};
