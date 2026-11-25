<?php

namespace App\Http\Controllers;

use App\Models\CariLomba;
use Carbon\Carbon;

class CompetitionController extends Controller
{
    public function index()
    {
        // Menghapus otomatis lomba H+2 setelah deadline
        CariLomba::whereDate(
            'deadline',
            '<=',
            Carbon::today('Asia/Jakarta')->subDays(2)
        )->delete();

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

    public function show($id)
    {
        $lomba = CariLomba::findOrFail($id);

        return view('cari_lomba_detail', [
            'lomba' => $lomba
        ]);
    }

    public function store(\Illuminate\Http\Request $request)
    {
        $data = $request->validate([
            'lomba_id' => 'required|integer',
            'skills' => 'required|array|min:1',
            'skills.*' => 'required|string|max:100',
        ]);

        $lomba = CariLomba::findOrFail($data['lomba_id']);

        $lomba->skills = $data['skills'];
        $lomba->save();

        return response()->json([
            'success' => true,
            'message' => 'Skill lomba berhasil disimpan',
            'data' => $lomba,
        ], 200);
    }
}