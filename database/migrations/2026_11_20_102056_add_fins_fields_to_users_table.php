
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tambahkan NIM jika belum tersedia
        if (!Schema::hasColumn('users', 'nim')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('nim', 20)->unique();
            });
        }

        // Tambahkan fakultas jika belum tersedia
        if (!Schema::hasColumn('users', 'fakultas')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('fakultas')->nullable();
            });
        }

        // Tambahkan prodi jika belum tersedia
        if (!Schema::hasColumn('users', 'prodi')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('prodi')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Sengaja tidak menghapus kolom.
        // Kolom NIM mungkin sudah ada sebelum migration ini
        // dan dapat digunakan oleh data pengguna lama.
    }
};
