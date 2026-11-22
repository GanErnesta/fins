<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\ProfilePhotoUrl;
use App\Support\FacultyCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class ProfileController extends Controller
{
    // Data pengguna selalu diambil ulang dari tabel users.
    private function currentUser(Request $request): User
    {
        return User::query()->findOrFail($request->user()->getAuthIdentifier());
    }

    // Mendukung variasi nama kolom yang sudah digunakan di fitur signup.
    private function userColumn(array $candidates): ?string
    {
        foreach ($candidates as $column) {
            if (Schema::hasColumn('users', $column)) {
                return $column;
            }
        }
        return null;
    }

    private function attribute(User $user, array $columns, string $fallback = ''): string
    {
        foreach ($columns as $column) {
            $value = $user->getAttribute($column);
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }
        return $fallback;
    }

    private function parseSkills($raw): array
    {
        if ($raw instanceof Collection) {
            $raw = $raw->all();
        }
        if (is_string($raw)) {
            $raw = json_decode($raw, true) ?: [];
        }
        if (!is_array($raw)) {
            return [];
        }
        return array_values(array_unique(array_map(
            'trim',
            array_filter($raw, fn ($skill) => is_string($skill) && trim($skill) !== '')
        )));
    }

    // URL foto di profil dan navbar dibuat oleh helper yang sama.
    private function publicPhotoUrl(?string $path): ?string
    {
        return ProfilePhotoUrl::publicUrl($path);
    }

    // Hanya hapus objek milik pengguna pada folder Supabase miliknya.
    private function belongsToUser(string $path, User $user): bool
    {
        return str_starts_with(
            ProfilePhotoUrl::objectKey($path) ?? '',
            'profile_photos/' . $user->getKey() . '/'
        );
    }

    private function profileData(User $user): array
    {
        $fullname = $this->attribute(
            $user,
            ['name', 'nama', 'nama_lengkap', 'full_name', 'fullname', 'profile_fullname'],
            'Pengguna FINS'
        );

        $parts = preg_split('/\s+/u', trim($fullname), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $initials = collect($parts)
            ->take(2)
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');

        return [
            'fullname' => $fullname,
            'username' => $this->attribute($user, ['profile_username', 'username']),
            'nim' => $this->attribute($user, ['nim', 'NIM']),
            'faculty' => $this->attribute($user, ['fakultas', 'faculty']),
            'study_program' => $this->attribute($user, ['prodi', 'program_studi', 'study_program']),
            'bio' => (string) ($user->profile_bio ?? ''),
            'skills' => $this->parseSkills($user->getAttribute('profile_skills')),
            'instagram' => (string) ($user->profile_instagram ?? ''),
            'github' => (string) ($user->profile_github ?? ''),
            'linkedin' => (string) ($user->profile_linkedin ?? ''),
            'initials' => $initials ?: 'F',
            'photo_url' => $this->publicPhotoUrl($user->profile_photo_path),
            'photo_path' => ProfilePhotoUrl::objectKey($user->profile_photo_path),
        ];
    }

    public function index(Request $request)
    {
        $profile = $this->profileData($this->currentUser($request));

        // Daftar fakultas/prodi berasal dari satu katalog bersama Sign Up.
        $faculties = array_keys(FacultyCatalog::all());
        $programs = FacultyCatalog::programsFor($profile['faculty']);

        // Biarkan nilai lama pengguna tetap terlihat bila nama fakultas berubah.
        $faculties = array_values(array_unique(array_filter([
            $profile['faculty'], ...$faculties
        ])));
        $programs = array_values(array_unique(array_filter([
            $profile['study_program'], ...$programs
        ])));

        return view('profile', compact('profile', 'faculties', 'programs'));
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fullname' => ['required', 'string', 'max:100'],
            'username' => ['nullable', 'string', 'max:30'],
            'faculty' => ['nullable', 'string', 'max:120'],
            'study_program' => ['nullable', 'string', 'max:120'],
            'bio' => ['nullable', 'string', 'max:180'],
            'skills' => ['sometimes', 'array', 'max:6'],
            'skills.*' => ['required', 'string', 'max:80', 'distinct'],
            'instagram' => ['nullable', 'string', 'max:150'],
            'github' => ['nullable', 'string', 'max:150'],
            'linkedin' => ['nullable', 'string', 'max:200'],
        ]);

        $user = $this->currentUser($request);

        // Cegah pasangan fakultas dan prodi yang tidak sesuai.
        // Data lama yang belum cocok dibiarkan jika kedua nilainya tidak diubah,
        // supaya pengguna masih bisa mengedit Bio/Skills tanpa terblokir.
        $requestedFaculty = (string) ($validated['faculty'] ?? '');
        $requestedProgram = (string) ($validated['study_program'] ?? '');
        $previousFaculty = $this->attribute($user, ['fakultas', 'faculty']);
        $previousProgram = $this->attribute($user, ['prodi', 'program_studi', 'study_program']);
        $isUnchangedLegacy = $requestedFaculty === $previousFaculty
            && $requestedProgram === $previousProgram;

        if ($requestedFaculty !== '' && $requestedProgram !== ''
            && !$isUnchangedLegacy
            && !FacultyCatalog::hasProgram($requestedFaculty, $requestedProgram)) {
            throw ValidationException::withMessages([
                'study_program' => 'Program studi tidak sesuai dengan fakultas yang dipilih.',
            ]);
        }

        $nameColumn = $this->userColumn([
            'name', 'nama', 'nama_lengkap', 'full_name', 'fullname', 'profile_fullname'
        ]);
        if (!$nameColumn) {
            throw ValidationException::withMessages([
                'fullname' => 'Kolom nama pengguna belum tersedia di tabel users.'
            ]);
        }
        $user->setAttribute($nameColumn, $validated['fullname']);

        $facultyColumn = $this->userColumn(['fakultas', 'faculty']);
        if ($facultyColumn && array_key_exists('faculty', $validated)) {
            $user->setAttribute($facultyColumn, $validated['faculty']);
        }
        $studyColumn = $this->userColumn(['prodi', 'program_studi', 'study_program']);
        if ($studyColumn && array_key_exists('study_program', $validated)) {
            $user->setAttribute($studyColumn, $validated['study_program']);
        }

        foreach ([
            'username' => 'profile_username',
            'bio' => 'profile_bio',
            'instagram' => 'profile_instagram',
            'github' => 'profile_github',
            'linkedin' => 'profile_linkedin',
        ] as $field => $column) {
            if (array_key_exists($field, $validated)) {
                $user->setAttribute($column, $validated[$field]);
            }
        }

        if (array_key_exists('skills', $validated)) {
            $skills = $this->parseSkills($validated['skills']);
            $cast = $user->getCasts()['profile_skills'] ?? null;
            $user->profile_skills = in_array($cast, ['array', 'json', 'collection', 'object'], true)
                ? $skills
                : json_encode($skills, JSON_UNESCAPED_UNICODE);
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui.',
            'profile' => $this->profileData($this->currentUser($request)),
        ]);
    }

    public function uploadPhoto(Request $request): JsonResponse
    {
        $request->validate([
            'profile_photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $user = $this->currentUser($request);
        $oldPath = (string) ($user->profile_photo_path ?? '');
        $newPath = null;

        try {
            // Upload langsung ke bucket Supabase melalui disk S3.
            $newPath = $request->file('profile_photo')->store(
                'profile_photos/' . $user->getKey(),
                'supabase'
            );
            if (!$newPath) {
                throw new RuntimeException('Supabase Storage gagal menyimpan foto.');
            }

            // Siapkan URL publik sebelum mengubah data users.
            $photoUrl = $this->publicPhotoUrl($newPath);
            if (!$photoUrl) {
                throw new RuntimeException('URL publik Supabase tidak dapat dibuat. Periksa SUPABASE_URL.');
            }
            $user->profile_photo_path = $newPath;
            $user->save();
        } catch (Throwable $exception) {
            // Bersihkan objek baru bila upload sukses tetapi database gagal.
            if ($newPath) {
                try {
                    Storage::disk('supabase')->delete($newPath);
                } catch (Throwable $cleanupError) {
                    Log::warning('Gagal membersihkan upload profil.', ['error' => $cleanupError->getMessage()]);
                }
            }
            Log::error('Gagal upload foto profil Supabase.', ['error' => $exception->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Foto gagal diunggah. Periksa konfigurasi Supabase Storage pada server.',
            ], 500);
        }

        // Hapus objek Supabase lama, hanya jika berada di folder akun ini.
        if ($oldPath !== '' && $oldPath !== $newPath && $this->belongsToUser($oldPath, $user)) {
            try {
                Storage::disk('supabase')->delete(
                    ProfilePhotoUrl::objectKey($oldPath)
                );
            } catch (Throwable $exception) {
                // Foto baru sudah aman disimpan; jangan batalkan keberhasilannya.
                Log::warning('Foto lama Supabase gagal dihapus.', ['error' => $exception->getMessage()]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Foto profil berhasil diunggah ke Supabase.',
            'photoUrl' => $photoUrl,
            'photoPath' => $newPath,
        ]);
    }

    public function removePhoto(Request $request): JsonResponse
    {
        $user = $this->currentUser($request);
        $oldPath = (string) ($user->profile_photo_path ?? '');

        // Hapus referensi dari user terlebih dahulu.
        $user->profile_photo_path = null;
        $user->save();

        $warning = false;
        if ($oldPath !== '' && $this->belongsToUser($oldPath, $user)) {
            try {
                $deleted = Storage::disk('supabase')->delete(
                    ProfilePhotoUrl::objectKey($oldPath)
                );
                $warning = !$deleted;
            } catch (Throwable $exception) {
                Log::warning('Foto Supabase gagal dihapus.', ['error' => $exception->getMessage()]);
                $warning = true;
            }
        }

        return response()->json([
            'success' => true,
            'message' => $warning
                ? 'Foto dilepas dari profil, tetapi file di bucket belum berhasil dihapus.'
                : 'Foto profil berhasil dihapus.',
            'photoUrl' => null,
            'photoPath' => null,
        ]);
    }
}
