<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CariPartnerController extends Controller
{
    public function index(Request $request)
    {
        // Ambil ID pengguna yang sedang login
        $currentUser = $request->user();

        if (!$currentUser) {
            return redirect()->route('login');
        }

        $currentUserId = $currentUser->getAuthIdentifier();

        // Ambil pengguna lain dari database Supabase
        $users = DB::table('users')
            ->where('id', '!=', $currentUserId)
            ->select(
                'id',
                'name',
                'profile_fullname',
                'profile_bio',
                'profile_skills',
                'fakultas',
                'prodi'
            )
            ->orderBy('name')
            ->get();

        // Ubah data pengguna menjadi format kartu partner
        $partners = $users->map(function ($user) {

            // Ambil dan olah keahlian pengguna
            $raw = trim((string) ($user->profile_skills ?? ''));

            $decoded = json_decode($raw, true);

            if (is_array($decoded)) {
                $skills = array_values(array_filter(
                    $decoded,
                    'is_string'
                ));
            } else {
                $skills = array_values(array_filter(
                    array_map(
                        'trim',
                        preg_split('/[,\n;]+/', $raw)
                    )
                ));
            }

            // Ambil nama pengguna
            $name = $user->profile_fullname
                ?: $user->name;

            // Buat inisial nama untuk avatar
            $words = preg_split('/\s+/', trim($name));

            $initials = collect($words)
                ->filter()
                ->take(2)
                ->map(function ($word) {
                    return mb_strtoupper(
                        mb_substr($word, 0, 1)
                    );
                })
                ->implode('');

            return [
                'id' => $user->id,
                'name' => $name,
                'study' => $user->prodi
                    ?: 'Program studi belum diisi',
                'semester' => null,
                'initials' => $initials,
                'bio' => $user->profile_bio
                    ?: 'Belum ada deskripsi profil.',
                'skills' => $skills,
                'interests' => [],
                'match' => 0,
                'tag' => 'Mahasiswa FINS',
                'kind' => 'same',
                'category' => $user->fakultas
                    ?: 'Universitas Brawijaya',
                'online' => false,
            ];
        })->all();

        // Pilihan filter keahlian
        // Diambil dari skill pengguna yang tersedia
        $skillOptions = collect($partners)
            ->flatMap(function ($partner) {
                return $partner['skills'];
            })
            ->filter()
            ->unique()
            ->sort()
            ->mapWithKeys(function ($skill) use ($partners) {
                $total = collect($partners)
                    ->filter(function ($partner) use ($skill) {
                        return in_array(
                            $skill,
                            $partner['skills']
                        );
                    })
                    ->count();

                return [$skill => $total];
            })
            ->all();

        // Pilihan filter program studi
        $studyOptions = collect($partners)
            ->pluck('study')
            ->filter(function ($study) {
                return $study !== 'Program studi belum diisi';
            })
            ->unique()
            ->sort()
            ->values()
            ->all();

        // Belum tersedia kolom minat kompetisi
        // pada tabel users
        $interestOptions = [];

        // Kirim semua data ke Blade
        return view('cari_partner', compact(
            'partners',
            'skillOptions',
            'studyOptions',
            'interestOptions'
        ));
    }
}
