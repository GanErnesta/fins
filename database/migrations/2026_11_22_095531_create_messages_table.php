
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();

            // Percakapan tempat pesan dikirim
            $table->foreignId('conversation_id')
                ->constrained('conversations')
                ->cascadeOnDelete();

            // Pengguna yang mengirim pesan
            $table->foreignId('sender_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Isi pesan
            $table->text('message')->nullable();

            // text, file, image, system
            $table->string('type', 20)->default('text');

            // Pesan yang dibalas (opsional)
            $table->foreignId('reply_to_id')
                ->nullable()
                ->constrained('messages')
                ->nullOnDelete();

            // Waktu pesan dibaca (opsional)
            $table->timestamp('read_at')->nullable();

            // Waktu pesan dihapus
            $table->softDeletes();

            $table->timestamps();

            // Mempercepat pengambilan riwayat chat
            $table->index(['conversation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
