<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PostingLombaController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | MENAMPILKAN HALAMAN POSTING LOMBA
    |--------------------------------------------------------------------------
    */

    public function create()
    {
        // Mengambil daftar lomba dari Supabase
        $lombas = DB::table('cari_lomba')
            ->select(
                'id',
                'title',
                'category',
                'organizer',
                'deadline',
                'image'
            )
            ->orderBy('title', 'asc')
            ->get();

        return view('posting_lomba', compact('lombas'));
    }

    /*
    |--------------------------------------------------------------------------
    | MENYIMPAN POSTINGAN LOMBA
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        // 1. Validasi input
        $validated = $request->validate([
            'mode_lomba' => [
                'required',
                Rule::in(['existing', 'new']),
            ],

            // Lomba yang sudah tersedia
            'cari_lomba_id' => [
                'required_if:mode_lomba,existing',
                'nullable',
                'integer',
                Rule::exists('cari_lomba', 'id'),
            ],

            // Data lomba baru
            'nama_lomba' => [
                'required_if:mode_lomba,new',
                'nullable',
                'string',
                'max:150',
            ],

            'kategori' => [
                'required_if:mode_lomba,new',
                'nullable',
                'string',
                'max:100',
            ],

            'penyelenggara' => [
                'required_if:mode_lomba,new',
                'nullable',
                'string',
                'max:150',
            ],

            'deadline' => [
                'required_if:mode_lomba,new',
                'nullable',
                'date',
                'after_or_equal:today',
            ],

            // Data pencarian anggota
            'deskripsi' => [
                'required',
                'string',
                'max:3000',
            ],

            'target' => [
                'required',
                'string',
                'max:300',
            ],

            'kanal_diskusi' => [
                'required',
                'string',
                'max:255',
            ],

            // Posisi anggota
            'positions' => [
                'required',
                'array',
                'min:1',
                'max:20',
            ],

            'positions.*.name' => [
                'required',
                'string',
                'max:100',
            ],

            'positions.*.count' => [
                'required',
                'integer',
                'min:1',
                'max:30',
            ],

            'positions.*.skills' => [
                'required',
                'string',
                'max:500',
            ],

            // Upload poster lomba maksimal 2 MB
            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | 2. CEK STRUKTUR TABEL POSTING LOMBA
        |--------------------------------------------------------------------------
        */

        $requiredColumns = [
            'cari_lomba_id',
            'nama_lomba',
            'kategori',
            'penyelenggara',
            'deadline',
            'deskripsi',
            'target',
            'kanal_diskusi',
            'positions',
            'image',
        ];

        foreach ($requiredColumns as $column) {
            if (!Schema::hasColumn('posting_lombas', $column)) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'database' =>
                            "Kolom {$column} belum tersedia di tabel posting_lombas.",
                    ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 3. UPLOAD GAMBAR
        |--------------------------------------------------------------------------
        */

        $imagePath = null;
        $imageUrl = null;

        if ($request->hasFile('image')) {

            // Simpan poster ke storage/app/public/posters
            $imagePath = $request->file('image')
                ->store('posters', 'public');

            // Path yang dapat digunakan oleh asset() di Blade
            $imageUrl = 'storage/' . $imagePath;
        }

        /*
        |--------------------------------------------------------------------------
        | 4. TRANSAKSI DATABASE SUPABASE
        |--------------------------------------------------------------------------
        */

        try {

            DB::transaction(function () use ($validated, $imageUrl) {

                $positions = $validated['positions'];

                // Menghitung total anggota yang dicari
                $totalAnggota = collect($positions)
                    ->sum(
                        fn ($position) => (int) $position['count']
                    );

                // Menggabungkan skill semua posisi
                $skills = collect($positions)
                    ->flatMap(function ($position) {
                        return explode(',', $position['skills']);
                    })
                    ->map(fn ($skill) => trim($skill))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                /*
                |--------------------------------------------------------------------------
                | PILIHAN 1: MENGGUNAKAN LOMBA YANG SUDAH ADA
                |--------------------------------------------------------------------------
                */

                if ($validated['mode_lomba'] === 'existing') {

                    $lomba = DB::table('cari_lomba')
                        ->where('id', $validated['cari_lomba_id'])
                        ->first();

                    if (!$lomba) {
                        throw ValidationException::withMessages([
                            'cari_lomba_id' => 'Lomba tidak ditemukan.',
                        ]);
                    }

                    $cariLombaId = $lomba->id;

                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | PILIHAN 2: MEMBUAT LOMBA BARU
                    |--------------------------------------------------------------------------
                    */

                    $namaLomba = trim($validated['nama_lomba']);

                    // Membuat slug unik
                    $slug = Str::slug($namaLomba)
                        . '-'
                        . Str::lower(Str::random(10));

                    // Simpan lomba baru ke tabel cari_lomba
                    $cariLombaId = DB::table('cari_lomba')
                        ->insertGetId([
                            'user_id' => Auth::id(),
                            
                            'title' => $namaLomba,

                            'slug' => $slug,

                            'subtitle' => 'Open Recruitment Tim Lomba',

                            'category' => $validated['kategori'],

                            'organizer' => $validated['penyelenggara'],

                            'deadline' => $validated['deadline'],

                            'team' => $totalAnggota,

                            'description' => $validated['deskripsi'],

                            'skills' => json_encode(
                                $skills,
                                JSON_UNESCAPED_UNICODE
                            ),

                            // Gambar untuk kartu Cari Lomba
                            'image' => $imageUrl,

                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                    // Mengambil data lomba baru
                    $lomba = DB::table('cari_lomba')
                        ->where('id', $cariLombaId)
                        ->first();
                }

                /*
                |--------------------------------------------------------------------------
                | 5. MENYIAPKAN DATA POSTING LOMBA
                |--------------------------------------------------------------------------
                */

                // Jika tidak upload poster baru,
                // gunakan gambar lomba yang sudah ada
                $posterPosting = $imageUrl
                    ?? $lomba->image
                    ?? null;

                $postingData = [
                    'cari_lomba_id' => $cariLombaId,

                    'nama_lomba' => $lomba->title,

                    'kategori' => $lomba->category,

                    'penyelenggara' => $lomba->organizer,

                    'deadline' => $lomba->deadline,

                    'deskripsi' => $validated['deskripsi'],

                    'target' => $validated['target'],

                    'kanal_diskusi' => $validated['kanal_diskusi'],

                    // Poster untuk postingan rekrutmen
                    'image' => $posterPosting,

                    'positions' => json_encode(
                        $positions,
                        JSON_UNESCAPED_UNICODE
                    ),

                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                // Menyimpan ID pengguna jika kolom tersedia
                if (Schema::hasColumn('posting_lombas', 'user_id')) {
                    $postingData['user_id'] = Auth::id();
                }

                /*
                |--------------------------------------------------------------------------
                | 6. SIMPAN POSTINGAN KE SUPABASE
                |--------------------------------------------------------------------------
                */

                DB::table('posting_lombas')
                    ->insert($postingData);

            });

        } catch (\Throwable $error) {

            /*
            |--------------------------------------------------------------------------
            | 7. HAPUS POSTER JIKA DATABASE GAGAL
            |--------------------------------------------------------------------------
            */

            if ($imagePath !== null) {
                Storage::disk('public')->delete($imagePath);
            }

            throw $error;
        }

        /*
        |--------------------------------------------------------------------------
        | 8. REDIRECT KE CARI LOMBA
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route('cari-lomba')
            ->with(
                'success',
                'Postingan lomba berhasil dipublikasikan!'
            );
    }
}
