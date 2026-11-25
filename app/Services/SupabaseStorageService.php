<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class SupabaseStorageService
{
    private function baseUrl(): string
    {
        $url = rtrim(
            (string) config('services.supabase.url'),
            '/'
        );

        if ($url === '') {
            throw new RuntimeException('SUPABASE_URL belum diatur.');
        }

        return $url . '/storage/v1';
    }

    private function secretKey(): string
    {
        $key = (string) config('services.supabase.secret_key');

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

    private function request()
    {
        return Http::withToken($this->secretKey())
            ->timeout(60);
    }

    private function checkResponse(Response $response): void
    {
        if ($response->failed()) {
            throw new RuntimeException(
                'Supabase Storage gagal: ' .
                $response->status() . ' - ' .
                $response->body()
            );
        }
    }

    public function upload(
        UploadedFile $file,
        int $conversationId
    ): array {
        $extension = strtolower(
            $file->getClientOriginalExtension()
        );

        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];

        if (!in_array($extension, $allowed, true)) {
            throw new RuntimeException(
                'Jenis file tidak didukung.'
            );
        }

        $filename = Str::uuid() . '.' . $extension;

        $path = 'conversations/' .
            $conversationId . '/' .
            $filename;

        $stream = fopen($file->getRealPath(), 'rb');

        if ($stream === false) {
            throw new RuntimeException('File tidak dapat dibaca.');
        }

        try {
            $response = $this->request()
                ->withHeaders([
                    'Content-Type' => $file->getMimeType()
                        ?? 'application/octet-stream',
                    'x-upsert' => 'false',
                ])
                ->withBody(
                    $stream,
                    $file->getMimeType()
                        ?? 'application/octet-stream'
                )
                ->post(
                    $this->baseUrl() .
                    '/object/' .
                    $this->bucket() .
                    '/' .
                    $path
                );

            $this->checkResponse($response);
        } finally {
            fclose($stream);
        }

        return [
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'disk' => 'supabase',
            'bucket' => $this->bucket(),
        ];
    }

    public function signedUrl(
        string $path,
        int $expiresIn = 300
    ): string {
        $response = $this->request()
            ->post(
                $this->baseUrl() .
                '/object/sign/' .
                $this->bucket() .
                '/' .
                $path,
                ['expiresIn' => $expiresIn]
            );

        $this->checkResponse($response);

        $signedUrl = $response->json('signedURL')
            ?? $response->json('signedUrl');

        if (!$signedUrl) {
            throw new RuntimeException(
                'Signed URL tidak ditemukan.'
            );
        }

        if (str_starts_with($signedUrl, 'http')) {
            return $signedUrl;
        }

        return rtrim(
            (string) config('services.supabase.url'),
            '/'
        ) . '/storage/v1' .
            (str_starts_with($signedUrl, '/')
                ? $signedUrl
                : '/' . $signedUrl);
    }

    public function delete(string $path): void
    {
        $response = $this->request()
            ->delete(
                $this->baseUrl() .
                '/object/' .
                $this->bucket() .
                '/' .
                $path
            );

        $this->checkResponse($response);
    }
}
