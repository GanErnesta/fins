<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PostingLombaController extends Controller
{
    // Menampilkan halaman posting lomba
    public function create()
    {
        return view('posting_lomba');
    }

    // Menyimpan postingan lomba
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_lomba' => 'required|string|max:150',
            'kategori' => 'required|string|max:100',
            'penyelenggara' => 'required|string|max:150',
            'deadline' => 'required|date|after_or_equal:today',
            'deskripsi' => 'required|string|max:3000',
            'target' => 'required|string|max:300',
            'kanal_diskusi' => 'required|string|max:255',

            'positions' => 'required|array|min:1|max:20',
            'positions.*.name' => 'required|string|max:100',
            'positions.*.count' => 'required|integer|min:1|max:30',
            'positions.*.skills' => 'required|string|max:500',
        ]);

        DB::table('posting_lombas')->insert([
            'user_id' => Auth::id(),
            'nama_lomba' => $validated['nama_lomba'],
            'kategori' => $validated['kategori'],
            'penyelenggara' => $validated['penyelenggara'],
            'deadline' => $validated['deadline'],
            'deskripsi' => $validated['deskripsi'],
            'target' => $validated['target'],
            'kanal_diskusi' => $validated['kanal_diskusi'],
            'positions' => json_encode(
                $validated['positions'],
                JSON_UNESCAPED_UNICODE
            ),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('posting_lomba.create')
            ->with(
                'success',
                'Postingan lomba berhasil dipublikasikan!'
            );
    }
}