<?php

namespace App\Http\Controllers;

use App\Models\CariLomba;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CompetitionController extends Controller
{
    // Menampilkan daftar lomba
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

        return view('cari_lomba', compact(
            'lomba',
            'penyelenggara'
        ));
    }

    // Menampilkan detail lomba
    public function show($id)
    {
        $lomba = CariLomba::findOrFail($id);

        return view('cari_lomba_detail', [
            'lomba' => $lomba
        ]);
    }

    // Menampilkan form edit lomba
    public function edit($id)
    {
        $lomba = CariLomba::findOrFail($id);

        // Memastikan pengguna adalah pemilik lomba
        $this->checkOwner($lomba);

        return view('cari_lomba_edit', compact('lomba'));
    }

    // Menyimpan perubahan lomba
    public function update(Request $request, $id)
    {
        $lomba = CariLomba::findOrFail($id);

        // Memastikan pengguna adalah pemilik lomba
        $this->checkOwner($lomba);

        // Validasi data
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'category' => 'required|string|max:100',
            'organizer' => 'required|string|max:255',
            'deadline' => 'required|date',
            'team' => 'required|integer|min:1',
            'description' => 'required|string',
            'skills' => 'nullable|array',
            'skills.*' => 'required|string|max:100',
        ]);

        // Memperbarui data lomba
        $lomba->update($data);

        return redirect()
            ->route('cari_lomba.show', $lomba->id)
            ->with('success', 'Lomba berhasil diperbarui.');
    }

    // Menghapus lomba
    public function destroy($id)
    {
        $lomba = CariLomba::findOrFail($id);

        // Memastikan pengguna adalah pemilik lomba
        $this->checkOwner($lomba);

        // Menghapus data dari database
        $lomba->delete();

        return redirect()
            ->route('cari-lomba')
            ->with('success', 'Lomba berhasil dihapus.');
    }

    // Menyimpan skill lomba
    public function store(Request $request)
    {
        $data = $request->validate([
            'lomba_id' => 'required|integer',
            'skills' => 'required|array|min:1',
            'skills.*' => 'required|string|max:100',
        ]);

        $lomba = CariLomba::findOrFail($data['lomba_id']);

        // Mencegah pengguna lain mengubah skill lomba
        $this->checkOwner($lomba);

        $lomba->skills = $data['skills'];
        $lomba->save();

        return response()->json([
            'success' => true,
            'message' => 'Skill lomba berhasil disimpan',
            'data' => $lomba,
        ], 200);
    }

    // Validasi kepemilikan lomba
    private function checkOwner(CariLomba $lomba): void
    {
        abort_unless(
            auth()->check() &&
            $lomba->user_id !== null &&
            (string) auth()->id() === (string) $lomba->user_id,
            403,
            'Anda tidak memiliki akses untuk mengubah atau menghapus lomba ini.'
        );
    }
}