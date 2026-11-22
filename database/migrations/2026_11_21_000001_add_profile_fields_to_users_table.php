<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Tidak membuat tabel profiles. Semua field tambahan ada pada users.
        $fields = [
            'profile_fullname' => 'string',
            'profile_username' => 'string',
            'profile_bio' => 'text',
            'profile_skills' => 'text',
            'profile_instagram' => 'string',
            'profile_github' => 'string',
            'profile_linkedin' => 'string',
            'profile_photo_path' => 'string',
        ];

        foreach ($fields as $column => $type) {
            if (!Schema::hasColumn('users', $column)) {
                Schema::table('users', function (Blueprint $table) use ($column, $type) {
                    $table->{$type}($column)->nullable();
                });
            }
        }
    }

    public function down(): void
    {
        // Sengaja tidak menghapus kolom. Karena tabel users sudah ada sebelumnya,
        // kita tidak bisa memastikan apakah kolom tertentu berasal dari migration ini.
        // Ini menghindari kehilangan data profil pengguna saat rollback.
    }
};
