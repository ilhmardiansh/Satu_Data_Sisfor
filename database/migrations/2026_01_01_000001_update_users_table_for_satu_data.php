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
        Schema::table('users', function (Blueprint $table): void {
            $table->string('email')->nullable()->change();
            $table->string('code', 40)->nullable()->unique()->after('id');
            $table->string('nim', 60)->unique()->after('name');
            $table->string('role', 20)->default('produsen')->after('password');
            $table->string('jenis', 30)->nullable()->after('role');
            $table->boolean('aktif')->default(true)->after('jenis');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['code']);
            $table->dropUnique(['nim']);
            $table->dropColumn(['code', 'nim', 'role', 'jenis', 'aktif']);
            $table->string('email')->nullable(false)->change();
        });
    }
};
