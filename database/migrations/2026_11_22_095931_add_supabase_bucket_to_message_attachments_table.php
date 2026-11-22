
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tambahkan kolom hanya jika belum tersedia
        if (!Schema::hasColumn('message_attachments', 'bucket')) {
            Schema::table('message_attachments', function (Blueprint $table) {
                $table->string('bucket', 100)
                    ->default('chat-attachments');
            });
        }

        // Perbarui nilai disk yang masih menggunakan public
        DB::table('message_attachments')
            ->where('disk', 'public')
            ->update([
                'disk' => 'supabase',
                'bucket' => 'chat-attachments'
            ]);
    }

    public function down(): void
    {
        // Tidak menghapus kolom karena mungkin sudah ada
        // sebelum migration ini dibuat.
    }
};
