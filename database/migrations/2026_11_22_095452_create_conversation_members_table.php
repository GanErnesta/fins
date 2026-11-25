
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversation_members', function (Blueprint $table) {
            $table->id();

            // ID percakapan
            $table->foreignId('conversation_id')
                ->constrained('conversations')
                ->cascadeOnDelete();

            // ID pengguna
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Peran anggota: admin atau member
            $table->string('role', 20)->default('member');

            // Waktu bergabung
            $table->timestamp('joined_at')->useCurrent();

            $table->timestamps();

            // Satu pengguna hanya boleh terdaftar sekali
            // dalam percakapan yang sama
            $table->unique(['conversation_id', 'user_id']);

            // Mempercepat pencarian anggota
            $table->index(['conversation_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_members');
    }
};
