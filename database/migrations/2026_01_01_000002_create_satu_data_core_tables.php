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
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('data_forms', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 40)->unique();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('tahun');
            $table->string('periode', 20);
            $table->string('target', 20);
            $table->timestamp('deadline');
            $table->json('fields');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('form_submissions', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 40)->unique();
            $table->foreignId('data_form_id')->constrained('data_forms')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->json('values');
            $table->json('attachments')->nullable();
            $table->timestamp('submitted_at');
            $table->foreignId('cleaned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cleaned_at')->nullable();
            $table->timestamps();
        });

        Schema::create('datasets', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 40)->unique();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->foreignId('source_form_id')->nullable()->constrained('data_forms')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('tahun');
            $table->string('periode', 20);
            $table->string('access', 20)->default('private');
            $table->string('format', 10)->default('CSV');
            $table->string('updated_label', 40)->nullable();
            $table->json('cols');
            $table->json('rows');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('data_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 40)->unique();
            $table->foreignId('dataset_id')->constrained('datasets')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('session_token', 100)->nullable()->index();
            $table->string('nama');
            $table->string('email');
            $table->string('hp', 30);
            $table->string('nim', 50);
            $table->string('tujuan');
            $table->text('deskripsi');
            $table->string('status', 20)->default('menunggu');
            $table->text('note')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamp('requested_at');
            $table->timestamps();
        });

        Schema::create('visualizations', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('title');
            $table->foreignId('dataset_id')->constrained('datasets')->cascadeOnDelete();
            $table->unsignedInteger('label_col')->default(0);
            $table->unsignedInteger('value_col')->default(1);
            $table->string('kind', 20)->default('bar');
            $table->string('color', 20)->default('sky');
            $table->boolean('show')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('dashboard_metrics', function (Blueprint $table): void {
            $table->id();
            $table->string('section', 30)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('title');
            $table->string('color', 20);
            $table->string('total', 50);
            $table->string('unit', 80);
            $table->string('default_kind', 20)->default('donut');
            $table->string('trend_value', 50)->nullable();
            $table->boolean('trend_up')->nullable();
            $table->json('data');
            $table->json('foot')->nullable();
            $table->json('table_data');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dashboard_metrics');
        Schema::dropIfExists('visualizations');
        Schema::dropIfExists('data_requests');
        Schema::dropIfExists('datasets');
        Schema::dropIfExists('form_submissions');
        Schema::dropIfExists('data_forms');
        Schema::dropIfExists('categories');
    }
};
