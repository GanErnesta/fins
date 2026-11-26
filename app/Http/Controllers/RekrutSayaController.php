<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RekrutSayaController extends Controller
{
    public function index(Request $request)
    {
        $rows = DB::table('posting_lombas')
            ->where('user_id', $request->user()->getAuthIdentifier())
            ->orderByDesc('created_at')->orderByDesc('id')
            ->get(['id', 'cari_lomba_id', 'nama_lomba', 'kategori', 'positions', 'created_at']);

        $cards = [];
        foreach ($rows as $row) {
            array_push($cards, ...$this->cardsForPosting($row));
        }

        return response()->json([
            'posting' => $cards,
            'lomba' => DB::table('cari_lomba')->orderBy('title')->get(['id', 'title', 'category']),
        ])->header('Cache-Control', 'private, no-store');
    }

    private function cardsForPosting(object $row): array
    {
        $cards = [];
        foreach (json_decode($row->positions ?? '[]', true) ?? [] as $i => $position) {
            $skills = $position['skills'] ?? '';
            $cards[] = [
                'id' => (string) $row->id . '/' . $i,
                'version' => hash('sha256', $row->positions),
                'competition' => $row->cari_lomba_id,
                'judul' => $row->nama_lomba,
                'kategori' => $row->kategori,
                'posisi' => $position['name'],
                'slot' => (int) $position['count'],
                'skill' => is_array($skills) ? $skills : array_values(array_filter(array_map('trim', explode(',', $skills)), 'strlen')),
                'waktu' => $row->created_at ? Carbon::parse($row->created_at)->format('d/m/Y H:i') : '',
                'status' => $position['status'] ?? 'Aktif',
                'pelamar' => [],
            ];
        }

        return $cards;
    }

    private function position(Request $request): array
    {
        $data = $request->validate([
            'posisi' => ['required', 'string', 'max:100'],
            'slot' => ['required', 'integer', 'between:1,100'],
            'skill' => ['required', 'array', 'min:1'],
            'skill.*' => ['required', 'string', 'max:500'],
            'status' => ['required', Rule::in(['Aktif', 'Ditutup'])],
        ]);

        return [
            'name' => $data['posisi'], 'count' => (int) $data['slot'],
            'skills' => implode(', ', $data['skill']), 'status' => $data['status'],
        ];
    }

    public function store(Request $request)
    {
        $position = $this->position($request);
        $data = $request->validate([
            'competition' => ['required', 'integer', 'exists:cari_lomba,id'],
            'deskripsi' => ['required', 'string', 'max:3000'],
            'target' => ['required', 'string', 'max:300'],
            'kanal_diskusi' => ['required', 'string', 'max:255'],
        ]);
        $lomba = DB::table('cari_lomba')->where('id', $data['competition'])->firstOrFail();

        $row = DB::transaction(function () use ($request, $data, $lomba, $position) {
            $id = DB::table('posting_lombas')->insertGetId([
                'user_id' => $request->user()->getAuthIdentifier(),
                'cari_lomba_id' => $lomba->id, 'nama_lomba' => $lomba->title,
                'kategori' => $lomba->category, 'penyelenggara' => $lomba->organizer,
                'deadline' => $lomba->deadline, 'image' => $lomba->image,
                'deskripsi' => $data['deskripsi'], 'target' => $data['target'],
                'kanal_diskusi' => $data['kanal_diskusi'],
                'positions' => json_encode([$position], JSON_THROW_ON_ERROR),
                'created_at' => now(), 'updated_at' => now(),
            ]);

            return DB::table('posting_lombas')->where('id', $id)->firstOrFail();
        });

        return response()->json(['id' => $row->id, 'posting' => $this->cardsForPosting($row)], 201);
    }

    public function update(Request $request, int $posting, int $posisi)
    {
        return $this->change($request, $posting, $posisi, $this->position($request));
    }

    public function destroy(Request $request, int $posting, int $posisi)
    {
        return $this->change($request, $posting, $posisi, null);
    }

    private function change(Request $request, int $posting, int $posisi, ?array $replacement)
    {
        $version = $request->validate(['version' => ['required', 'string', 'size:64']])['version'];

        DB::transaction(function () use ($request, $posting, $posisi, $replacement, $version) {
            $query = DB::table('posting_lombas')->where('id', $posting)
                ->where('user_id', $request->user()->getAuthIdentifier());
            $row = (clone $query)->lockForUpdate()->firstOrFail();
            abort_unless(hash_equals(hash('sha256', $row->positions), $version), 409,
                'Data posisi telah berubah. Muat ulang halaman sebelum mencoba kembali.');
            $positions = json_decode($row->positions, true, 512, JSON_THROW_ON_ERROR);
            abort_unless(isset($positions[$posisi]), 404);

            if ($replacement === null) {
                array_splice($positions, $posisi, 1);
            } else {
                $positions[$posisi] = array_replace($positions[$posisi], $replacement);
            }

            if ($positions === []) {
                $query->delete();
            } else {
                $query->update(['positions' => json_encode($positions, JSON_THROW_ON_ERROR), 'updated_at' => now()]);
            }
        });

        return response()->noContent();
    }
}
