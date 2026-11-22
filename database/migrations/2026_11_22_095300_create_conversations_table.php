
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();

            // private = chat pribadi, group = chat grup
            $table->string('type', 20)->default('private');

            // Nama grup (kosong untuk chat pribadi)
            $table->string('name', 100)->nullable();

            // Pengguna yang membuat percakapan
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Foto grup (opsional)
            $table->string('photo_path')->nullable();

            $table->timestamps();

            // Mempercepat pencarian percakapan
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
