<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class SupabaseStorageService
{
    // ==========================================
    // 1. KONFIGURASI SUPABASE
    // ==========================================

    private function baseUrl(): string
    {
        $url = rtrim(
            (string) config('services.supabase.url'),
            '/'
        );

        if ($url === '') {
            throw new RuntimeException(
                'SUPABASE_URL belum diatur.'
            );
        }

        return $url . '/storage/v1';
    }

    private function secretKey(): string
    {
        $key = (string) config(
            'services.supabase.secret_key'
        );

        if ($key === '') {
            throw new RuntimeException(
                'SUPABASE_SECRET_KEY belum diatur.'
            );
        }

        return $key;
    }

    private function bucket(): string
    {
        return (string) config(
            'services.supabase.storage_bucket',
            'chat-attachments'
        );
    }

    // ==========================================
    // 2. HTTP CLIENT
    // ==========================================

    private function request(
        int $timeout = 30
    ): PendingRequest {
        return Http::withToken(
            $this->secretKey()
        )
            ->acceptJson()
            ->connectTimeout(10)
            ->timeout($timeout)
            ->withOptions([
                'http_errors' => false,
            ]);
    }

    // ==========================================
    // 3. VALIDASI RESPONSE
    // ==========================================

    private function checkResponse(
        Response $response,
        string $operation = 'request'
    ): void {
        if (!$response->successful()) {

            Log::error('[FINS Storage] Request gagal', [
                'operation' => $operation,
                'status' => $response->status(),
            ]);

            throw new RuntimeException(
                'Supabase Storage gagal (' .
                $response->status() .
                ').'
            );
        }
    }

    // ==========================================
    // 4. ENCODE OBJECT PATH
    // ==========================================

    private function encodePath(
        string $path
    ): string {
        return implode(
            '/',
            array_map(
                'rawurlencode',
                explode('/', $path)
            )
        );
    }

    private function objectUrl(
        string $path
    ): string {
        return $this->baseUrl() .
            '/object/' .
            rawurlencode($this->bucket()) .
            '/' .
            $this->encodePath($path);
    }

    // ==========================================
    // 5. UPLOAD FILE
    // ==========================================

    public function upload(
        UploadedFile $file,
        int $conversationId
    ): array {
        $startTime = microtime(true);

        // ======================================
        // VALIDASI FILE
        // ======================================

        if (!$file->isValid()) {
            throw new RuntimeException(
                'File upload tidak valid.'
            );
        }

        if ($conversationId <= 0) {
            throw new RuntimeException(
                'ID percakapan tidak valid.'
            );
        }

        $extension = strtolower(
            $file->getClientOriginalExtension()
        );

        $allowedExtensions = [
            'jpg',
            'jpeg',
            'png',
            'webp',
            'pdf',
        ];

        if (!in_array(
            $extension,
            $allowedExtensions,
            true
        )) {
            throw new RuntimeException(
                'Jenis file tidak didukung.'
            );
        }

        // Ambil metadata satu kali saja.

        $mimeType = $file->getMimeType();

        $fileSize = $file->getSize();

        $originalName =
            $file->getClientOriginalName();

        $allowedMimeTypes = [
            'image/jpeg',
            'image/png',
            'image/webp',
            'application/pdf',
        ];

        if (!in_array(
            $mimeType,
            $allowedMimeTypes,
            true
        )) {
            throw new RuntimeException(
                'MIME type file tidak didukung.'
            );
        }

        if (
            $fileSize === false ||
            $fileSize <= 0 ||
            $fileSize > 10 * 1024 * 1024
        ) {
            throw new RuntimeException(
                'Ukuran file tidak valid atau melebihi 10 MB.'
            );
        }

        // ======================================
        // GENERATE NAMA FILE UNIK
        // ======================================

        $filename =
            Str::uuid()->toString() .
            '.' .
            $extension;

        $path =
            'conversations/' .
            $conversationId .
            '/' .
            $filename;

        $realPath = $file->getRealPath();

        if (
            $realPath === false ||
            !is_readable($realPath)
        ) {
            throw new RuntimeException(
                'File tidak dapat dibaca.'
            );
        }

        // ======================================
        // BUKA FILE STREAM
        // ======================================

        $stream = fopen(
            $realPath,
            'rb'
        );

        if ($stream === false) {
            throw new RuntimeException(
                'Gagal membuka file.'
            );
        }

        Log::info('[FINS Storage] Upload dimulai', [
            'conversation_id' => $conversationId,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
        ]);

        // ======================================
        // UPLOAD KE SUPABASE STORAGE
        // ======================================

        try {

            $response = $this->request(45)

                ->withHeaders([
                    'x-upsert' => 'false',
                    'Content-Length' => (string) $fileSize,
                ])

                ->withBody(
                    $stream,
                    $mimeType
                )

                ->post(
                    $this->objectUrl($path)
                );

            $this->checkResponse(
                $response,
                'upload'
            );

            Log::info(
                '[FINS Storage] Upload berhasil',
                [
                    'conversation_id' => $conversationId,

                    'status' => $response->status(),

                    'file_size' => $fileSize,

                    'duration_ms' => round(
                        (microtime(true) - $startTime)
                        * 1000
                    ),
                ]
            );

        } catch (ConnectionException $exception) {

            Log::error(
                '[FINS Storage] Upload timeout atau koneksi gagal',
                [
                    'conversation_id' => $conversationId,

                    'duration_ms' => round(
                        (microtime(true) - $startTime)
                        * 1000
                    ),
                ]
            );

            throw new RuntimeException(
                'Koneksi ke Supabase Storage terlalu lama atau terputus.',
                0,
                $exception
            );

        } finally {

            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        // ======================================
        // RETURN METADATA
        // ======================================

        return [
            'file_name' => $originalName,

            'file_path' => $path,

            'file_type' => $mimeType,

            'file_size' => $fileSize,

            'disk' => 'supabase',

            'bucket' => $this->bucket(),
        ];
    }

    // ==========================================
    // 6. GENERATE SIGNED URL
    // ==========================================

    public function signedUrl(
        string $path,
        int $expiresIn = 300
    ): string {
        $startTime = microtime(true);

        $expiresIn = max(
            1,
            min($expiresIn, 3600)
        );

        try {

            $response = $this->request(15)

                ->post(
                    $this->baseUrl() .
                    '/object/sign/' .
                    rawurlencode($this->bucket()) .
                    '/' .
                    $this->encodePath($path),

                    [
                        'expiresIn' => $expiresIn,
                    ]
                );

            $this->checkResponse(
                $response,
                'signed-url'
            );

        } catch (ConnectionException $exception) {

            Log::error(
                '[FINS Storage] Signed URL timeout'
            );

            throw new RuntimeException(
                'Gagal menghubungi penyimpanan lampiran.',
                0,
                $exception
            );
        }

        $signedUrl =
            $response->json('signedURL') ??
            $response->json('signedUrl');

        if (
            !is_string($signedUrl) ||
            $signedUrl === ''
        ) {
            throw new RuntimeException(
                'Signed URL tidak ditemukan.'
            );
        }

        Log::info(
            '[FINS Storage] Signed URL berhasil',
            [
                'duration_ms' => round(
                    (microtime(true) - $startTime)
                    * 1000
                ),
            ]
        );

        // Jika Supabase mengembalikan URL lengkap.

        if (
            str_starts_with($signedUrl, 'https://') ||
            str_starts_with($signedUrl, 'http://')
        ) {
            return $signedUrl;
        }

        $supabaseUrl = rtrim(
            (string) config('services.supabase.url'),
            '/'
        );

        // Hindari duplikasi /storage/v1.

        if (
            str_starts_with(
                $signedUrl,
                '/storage/v1/'
            )
        ) {
            return $supabaseUrl . $signedUrl;
        }

        return $supabaseUrl .
            '/storage/v1/' .
            ltrim($signedUrl, '/');
    }

    // ==========================================
    // 7. HAPUS FILE DARI STORAGE
    // ==========================================

    public function delete(
        string $path
    ): void {
        $startTime = microtime(true);

        try {

            $response = $this->request(15)

                ->delete(
                    $this->objectUrl($path)
                );

            // File sudah tidak tersedia.

            if ($response->status() === 404) {
                return;
            }

            $this->checkResponse(
                $response,
                'delete'
            );

            Log::info(
                '[FINS Storage] File berhasil dihapus',
                [
                    'duration_ms' => round(
                        (microtime(true) - $startTime)
                        * 1000
                    ),
                ]
            );

        } catch (ConnectionException $exception) {

            Log::error(
                '[FINS Storage] Delete timeout'
            );

            throw new RuntimeException(
                'Gagal menghapus file dari penyimpanan.',
                0,
                $exception
            );
        }
    }
}