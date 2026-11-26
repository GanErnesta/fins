<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LombaSayaController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        $lombaDiikuti = [
            [
                'kategori' => 'UI / UX & DESIGN',
                'status' => 'aktif',
                'status_label' => 'Aktif',
                'judul' => 'Gemastik XVIII — Divisi UX Design',
                'tim' => 'Brawijaya Synapse',
                'peran' => 'Lead UI/UX Designer',
                'milestone' => 'Finalisasi Laporan Evaluasi Heuristik & Design System Figma',
                'deadline' => '15 Oktober 2026',
                'kesiapan' => 85,
                'anggota' => [
                    ['nama' => 'Nadhif Al-Farisi', 'peran' => 'Lead UI/UX'],
                    ['nama' => 'Rifqi Fauzan', 'peran' => 'Data & Technical Advisor'],
                ],
            ],
            [
                'kategori' => 'PKM KEMENDIKBUD',
                'status' => 'menunggu',
                'status_label' => 'Menunggu',
                'judul' => 'PKM Karsa Cipta 2026 (Smart Water Monitoring)',
                'tim' => 'AquaGuard UB',
                'peran' => 'Software & UI Lead',
                'milestone' => 'Review Dosen Pembimbing FILKOM & Pengesahan WR 3',
                'deadline' => '18 Oktober 2026',
                'kesiapan' => 85,
                'anggota' => [
                    ['nama' => 'Dimas Arya', 'peran' => 'Ketua Tim (FT)'],
                    ['nama' => 'Nadhif Al-Farisi', 'peran' => 'Software & UI'],
                    ['nama' => 'Sarah Nabila', 'peran' => 'Analisis Finansial'],
                ],
            ],
            [
                'kategori' => 'BUSINESS PLAN & CASE',
                'status' => 'diterima',
                'status_label' => 'Diterima',
                'judul' => 'Brawijaya Business Case Competition 2026',
                'tim' => 'Apex Strategy Hub',
                'peran' => 'Tech Feasibility Member',
                'milestone' => 'Penyusunan Executive Summary Slide 1–10',
                'deadline' => '08 Oktober 2026',
                'kesiapan' => 85,
                'anggota' => [
                    ['nama' => 'Sarah Nabila', 'peran' => 'Ketua Tim (FEB)'],
                    ['nama' => 'Nadhif Al-Farisi', 'peran' => 'Tech Feasibility'],
                ],
            ],
        ];

        $timSaya = [
            [
                'nama' => 'Brawijaya Synapse',
                'lomba' => 'Gemastik XVIII — Divisi UX Design',
                'status' => 'Aktif',
                'anggota' => [
                    ['nama' => 'Nadhif Al-Farisi', 'peran' => 'Lead UI/UX'],
                    ['nama' => 'Rifky Fauzan', 'peran' => 'Data & Technical Advisor'],
                ],
            ],
            [
                'nama' => 'AquaGuard UB',
                'lomba' => 'PKM Karsa Cipta 2026 (Smart Water Monitoring)',
                'status' => 'Menunggu',
                'anggota' => [
                    ['nama' => 'Dimas Arya', 'peran' => 'Ketua Tim (FT)'],
                    ['nama' => 'Nadhif Al-Farisi', 'peran' => 'Software & UI'],
                    ['nama' => 'Sarah Nabila', 'peran' => 'Analisis Finansial'],
                ],
            ],
            [
                'nama' => 'Apex Strategy Hub',
                'lomba' => 'Brawijaya Business Case Competition 2026',
                'status' => 'Diterima',
                'anggota' => [
                    ['nama' => 'Sarah Nabila', 'peran' => 'Ketua Tim (FEB)'],
                    ['nama' => 'Nadhif Al-Farisi', 'peran' => 'Tech Feasibility'],
                ],
            ],
        ];

        // Satu kartu rekrutmen untuk setiap posisi pada posting milik pengguna.
        $postings = DB::table('posting_lombas')
            ->where('user_id', $user->getAuthIdentifier())
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get(['id', 'nama_lomba', 'kategori', 'positions', 'created_at']);

        $postingRekrut = [];

        foreach ($postings as $posting) {
            $positions = json_decode($posting->positions ?? '[]', true);

            if (!is_array($positions)) {
                continue;
            }

            $waktu = $posting->created_at
                ? Carbon::parse($posting->created_at)->format('d/m/Y H:i')
                : '';

            foreach ($positions as $position) {
                if (!is_array($position) || empty($position['name'])) {
                    continue;
                }

                // Form Posting Lomba menyimpan skill sebagai teks dipisahkan koma.
                $skills = collect(explode(',', $position['skills'] ?? ''))
                    ->map(fn ($skill) => trim($skill))
                    ->filter(fn ($skill) => $skill !== '')
                    ->values()
                    ->all();

                $postingRekrut[] = [
                    'id' => $posting->id,
                    'kategori' => $posting->kategori,
                    'waktu' => $waktu,
                    'status' => 'Dipublikasikan',
                    'judul' => $posting->nama_lomba,
                    'posisi' => $position['name'],
                    'slot' => (int) ($position['count'] ?? 0),
                    'skill' => $skills,
                    // Pelamar belum memiliki relasi langsung ke posisi posting.
                    'pelamar' => [],
                ];
            }
        }

        $pengajuan = [
            [
                'judul' => 'PKM-Karsa Cipta (PKM-KC) Inovasi Sensor AI',
                'status' => 'Menunggu',
                'tim' => 'AquaGuard UB (Dimas Arya – FT)',
                'posisi' => 'Frontend Dashboard Developer',
                'catatan' => 'Menunggu review ketua tim FT terkait portofolio dashboard React.',
                'waktu' => 'Kemarin, 14:15',
            ],
            [
                'judul' => 'Brawijaya Business Case Competition 2026',
                'status' => 'Diterima',
                'tim' => 'Apex Strategy Hub (Sarah Nabila – FEB)',
                'posisi' => 'Technology Feasibility Analyst',
                'catatan' => 'Selamat! Anda resmi bergabung dengan tim. Silakan koordinasi di ruang chat.',
                'waktu' => '3 hari lalu',
            ],
        ];

        return view('lomba_saya', compact(
            'lombaDiikuti',
            'timSaya',
            'postingRekrut',
            'pengajuan'
        ));
    }
}