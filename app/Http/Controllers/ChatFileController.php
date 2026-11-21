<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChatFileController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'max:10240',
                'mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg,txt,zip'
            ],
        ]);

        $file = $request->file('file');

        $originalName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());

        $filename = Str::random(40) . '.' . $extension;

        $path = $file->storeAs(
            'chat-attachments',
            $filename,
            'local'
        );

        return response()->json([
            'success' => true,
            'name' => $originalName,
            'size' => $file->getSize(),
            'url' => route('chat.file.download', [
                'filename' => $filename
            ]),
        ]);
    }

    public function download(string $filename)
    {
        $path = 'chat-attachments/' . $filename;

        if (!Storage::disk('local')->exists($path)) {
            abort(404, 'File tidak ditemukan');
        }

        return Storage::disk('local')->download($path);
    }
}
