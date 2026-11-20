<?php

namespace App\Http\Controllers;

class LombaSayaController extends Controller
{
    public function index()
    {
        // Data dummy: ganti dengan query Eloquent saat database sudah siap
        $lombaDiikuti = [
            [
                'kategori' => 'UI / UX & DESIGN', 'status' => 'aktif', 'status_label' => 'Aktif',
                'judul' => 'Gemastik XVIII — Divisi UX Design',
                'tim' => 'Brawijaya Synapse', 'peran' => 'Lead UI/UX Designer',
                'milestone' => 'Finalisasi Laporan Evaluasi Heuristik & Design System Figma',
                'deadline' => '15 Oktober 2026', 'kesiapan' => 85,
                'anggota' => [
                    ['nama' => 'Nadhif Al-Farisi', 'peran' => 'Lead UI/UX'],
                    ['nama' => 'Rifqi Fauzan', 'peran' => 'Data & Technical Advisor'],
                ],
            ],
            [
                'kategori' => 'PKM KEMENDIKBUD', 'status' => 'menunggu', 'status_label' => 'Menunggu',
                'judul' => 'PKM Karsa Cipta 2026 (Smart Water Monitoring)',
                'tim' => 'AquaGuard UB', 'peran' => 'Software & UI Lead',
                'milestone' => 'Review Dosen Pembimbing FILKOM & Pengesahan WR 3',
                'deadline' => '18 Oktober 2026', 'kesiapan' => 85,
                'anggota' => [
                    ['nama' => 'Dimas Arya', 'peran' => 'Ketua Tim (FT)'],
                    ['nama' => 'Nadhif Al-Farisi', 'peran' => 'Software & UI'],
                    ['nama' => 'Sarah Nabila', 'peran' => 'Analisis Finansial'],
                ],
            ],
            [
                'kategori' => 'BUSINESS PLAN & CASE', 'status' => 'diterima', 'status_label' => 'Diterima',
                'judul' => 'Brawijaya Business Case Competition 2026',
                'tim' => 'Apex Strategy Hub', 'peran' => 'Tech Feasibility Member',
                'milestone' => 'Penyusunan Executive Summary Slide 1–10',
                'deadline' => '08 Oktober 2026', 'kesiapan' => 85,
                'anggota' => [
                    ['nama' => 'Sarah Nabila', 'peran' => 'Ketua Tim (FEB)'],
                    ['nama' => 'Nadhif Al-Farisi', 'peran' => 'Tech Feasibility'],
                ],
            ],
        ];

        $timSaya = [
            [
                'nama' => 'Brawijaya Synapse', 'lomba' => 'Gemastik XVIII — Divisi UX Design', 'status' => 'Aktif',
                'anggota' => [
                    ['nama' => 'Nadhif Al-Farisi', 'peran' => 'Lead UI/UX'],
                    ['nama' => 'Rifky Fauzan', 'peran' => 'Data & Technical Advisor'],
                ],
            ],
            [
                'nama' => 'AquaGuard UB', 'lomba' => 'PKM Karsa Cipta 2026 (Smart Water Monitoring)', 'status' => 'Menunggu',
                'anggota' => [
                    ['nama' => 'Dimas Arya', 'peran' => 'Ketua Tim (FT)'],
                    ['nama' => 'Nadhif Al-Farisi', 'peran' => 'Software & UI'],
                    ['nama' => 'Sarah Nabila', 'peran' => 'Analisis Finansial'],
                ],
            ],
            [
                'nama' => 'Apex Strategy Hub', 'lomba' => 'Brawijaya Business Case Competition 2026', 'status' => 'Diterima',
                'anggota' => [
                    ['nama' => 'Sarah Nabila', 'peran' => 'Ketua Tim (FEB)'],
                    ['nama' => 'Nadhif Al-Farisi', 'peran' => 'Tech Feasibility'],
                ],
            ],
        ];

        $postingRekrut = [
            [
                'kategori' => 'UI/UX & Desain', 'waktu' => 'Kemarin, 14:20', 'status' => 'Aktif',
                'judul' => 'Gemastik XVIII — Divisi UX Design',
                'posisi' => 'UX Researcher / Writer', 'slot' => 1,
                'skill' => ['User Interview', 'Usability Metric', 'Microcopy', 'Figma'],
                'pelamar' => [
                    [
                        'nama' => 'Kezia Aurellia', 'asal' => 'Administrasi Publik (FIA UB)',
                        'posisi' => 'UX Researcher / Writer', 'status' => 'Diterima',
                        'pesan' => 'Halo Nadhif! Saya terbiasa menyusun riset kualitatif kebijakan inklusi disabilitas dan menyusun laporan riset akademik. Sangat tertarik berkontribusi di divisi UX!',
                    ],
                ],
            ],
        ];

        $pengajuan = [
            [
                'judul' => 'PKM-Karsa Cipta (PKM-KC) Inovasi Sensor AI', 'status' => 'Menunggu',
                'tim' => 'AquaGuard UB (Dimas Arya – FT)', 'posisi' => 'Frontend Dashboard Developer',
                'catatan' => 'Menunggu review ketua tim FT terkait portofolio dashboard React.',
                'waktu' => 'Kemarin, 14:15',
            ],
            [
                'judul' => 'Brawijaya Business Case Competition 2026', 'status' => 'Diterima',
                'tim' => 'Apex Strategy Hub (Sarah Nabila – FEB)', 'posisi' => 'Technology Feasibility Analyst',
                'catatan' => 'Selamat! Anda resmi bergabung dengan tim. Silakan koordinasi di ruang chat.',
                'waktu' => '3 hari lalu',
            ],
        ];

        return view('lomba_saya', compact('lombaDiikuti', 'timSaya', 'postingRekrut', 'pengajuan'));
    }
}
