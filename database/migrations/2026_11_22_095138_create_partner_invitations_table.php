
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_invitations', function (Blueprint $table) {
            $table->id();

            // Mahasiswa yang mengirim undangan
            $table->foreignId('sender_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Mahasiswa yang menerima undangan
            $table->foreignId('receiver_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Informasi ajakan kolaborasi
            $table->string('competition_name', 150);
            $table->string('role', 100)->nullable();
            $table->text('message')->nullable();

            // pending, accepted, rejected, cancelled
            $table->string('status', 20)->default('pending');

            $table->timestamps();

            // Mempercepat pencarian undangan
            $table->index(['receiver_id', 'status']);
            $table->index(['sender_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_invitations');
    }
};
