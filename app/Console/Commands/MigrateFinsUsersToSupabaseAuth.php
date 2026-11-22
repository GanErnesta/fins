<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\SupabaseAuthService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class MigrateFinsUsersToSupabaseAuth extends Command
{
    protected $signature = 'fins:auth-migrate
        {--email= : Migrasikan satu akun berdasarkan email}
        {--all : Migrasikan semua akun lama yang memenuhi syarat}
        {--dry-run : Periksa akun tanpa mengubah data}';

    protected $description = 'Impor hash password akun FINS lama ke Supabase Auth tanpa mengganti users.id';

    public function handle(SupabaseAuthService $supabase): int
    {
        $email = trim((string) $this->option('email'));
        $all = (bool) $this->option('all');
        $dry = (bool) $this->option('dry-run');
        if (($email === '' && !$all) || ($email !== '' && $all)) {
            $this->error('Pilih salah satu: --email=alamat@student.ub.ac.id ATAU --all.');
            return self::FAILURE;
        }

        $query = User::query()->whereNull('auth_user_id')
            ->whereNotNull('email')->whereNotNull('password');
        if ($email !== '') $query->where('email', strtolower($email));
        $count = (clone $query)->count();
        $this->info("Kandidat akun: {$count}" . ($dry ? ' (DRY RUN)' : ''));
        if ($count === 0) return self::SUCCESS;
        if (!$dry && $all && !$this->confirm('Mulai migrasi semua akun lama ke Supabase Auth?')) {
            return self::FAILURE;
        }

        $done = 0;
        $failed = 0;
        $skipped = 0;
        $query->orderBy('id')->chunkById(25, function ($users) use ($supabase, $dry, &$done, &$failed, &$skipped) {
            foreach ($users as $user) {
                // Karena createVerifiedUserFromHash akan mengkonfirmasi email, hanya
                // akun yang SUDAH melewati verifikasi OTP pada Laravel yang boleh diimpor.
                if (!$user->email_verified_at) {
                    $this->warn("Lewati ID {$user->id}: email belum terverifikasi.");
                    $skipped++;
                    continue;
                }
                $hash = (string) $user->getRawOriginal('password');
                if (!preg_match('/^\$(2[aby]\$|argon2(?:i|id)\$)/', $hash)) {
                    $this->warn("Lewati ID {$user->id}: hash password tidak didukung.");
                    $skipped++;
                    continue;
                }
                if ($dry) {
                    $this->line("Siap migrasi ID {$user->id} ({$user->email})");
                    $done++;
                    continue;
                }
                try {
                    // Jika email sudah ada di auth.users tetapi belum terhubung,
                    // Supabase akan menolak. Jangan otomatis hubungkan berdasarkan email!
                    $authId = $supabase->createVerifiedUserFromHash(
                        strtolower($user->email), $hash,
                        ['nim' => $user->nim, 'name' => $user->name]
                    );
                    try {
                        $user->forceFill(['auth_user_id' => $authId])->save();
                    } catch (Throwable $saveError) {
                        // Hanya hapus user Auth yang BARU dibuat dalam pemanggilan ini.
                        try { $supabase->deleteUser($authId); }
                        catch (Throwable $cleanupError) {
                            Log::error('Migrasi FINS: gagal cleanup user Auth baru', ['auth_id' => $authId]);
                        }
                        throw $saveError;
                    }
                    $done++;
                    $this->info("Berhasil migrasi ID {$user->id}");
                } catch (Throwable $error) {
                    $failed++;
                    $this->error("Gagal migrasi ID {$user->id}: periksa Laravel log dan Authentication > Users.");
                    Log::warning('Migrasi FINS gagal', [
                        'user_id' => $user->id,
                        'error_type' => class_basename($error),
                        'http_status' => $error->getCode(),
                    ]);
                }
            }
        });
        $this->line("Selesai. Siap/berhasil={$done}, dilewati={$skipped}, gagal={$failed}");
        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
