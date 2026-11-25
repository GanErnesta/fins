
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_attachments', function (Blueprint $table) {
            $table->id();

            // Pesan yang memiliki lampiran
            $table->foreignId('message_id')
                ->constrained('messages')
                ->cascadeOnDelete();

            // Nama file asli
            $table->string('file_name');

            // Lokasi file di storage
            $table->string('file_path');

            // Jenis file, misalnya image/jpeg
            $table->string('file_type', 100)->nullable();

            // Ukuran file dalam byte
            $table->unsignedBigInteger('file_size')->default(0);

            // Storage yang digunakan
            $table->string('disk', 50)->default('supabase');
            $table->string('bucket', 100)->default('chat-attachments');

            $table->timestamps();

            // Mempercepat pencarian lampiran
            $table->index('message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_attachments');
    }
};
