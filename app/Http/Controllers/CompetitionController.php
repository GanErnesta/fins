<?php

namespace App\Http\Controllers;

use App\Models\CariLomba;

class CompetitionController extends Controller
{
    public function index()
    {
        // Mengambil semua lomba
        $lomba = CariLomba::orderBy('deadline', 'asc')->get();

        // Mengambil daftar penyelenggara
        $penyelenggara = CariLomba::select('organizer')
            ->distinct()
            ->orderBy('organizer', 'asc')
            ->pluck('organizer');

        // Mengirim data ke Blade
        return view('cari_lomba', compact(
            'lomba',
            'penyelenggara'
        ));
    }

    public function show(CariLomba $cariLomba)
    {
        return view('cari_lomba_detail', [
            'lomba' => $cariLomba
        ]);
    }
    public function store(\Illuminate\Http\Request $request)
{
    $data = $request->validate([
        'lomba_id' => 'required|integer',
        'skills' => 'required|array|min:1',
        'skills.*' => 'required|string|max:100',
    ]);

    $lomba = \App\Models\Lomba::findOrFail($data['lomba_id']);

    $lomba->skills = $data['skills'];
    $lomba->save();

    return response()->json([
        'success' => true,
        'message' => 'Skill lomba berhasil disimpan',
        'data' => $lomba,
    ], 200);
}
}