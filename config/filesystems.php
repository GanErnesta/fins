
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    */

    'disks' => [

        /*
        |--------------------------------------------------------------------------
        | Supabase Storage - FINS
        |--------------------------------------------------------------------------
        |
        | Digunakan untuk menyimpan foto profil mahasiswa
        | langsung ke bucket Supabase Storage.
        |
        */

        'supabase' => [
            'driver' => 's3',

            // Kredensial S3 dari Supabase
            'key' => env('SUPABASE_S3_ACCESS_KEY'),
            'secret' => env('SUPABASE_S3_SECRET_KEY'),

            // Region Supabase
            'region' => env(
                'SUPABASE_S3_REGION',
                'ap-southeast-2'
            ),

            // Nama bucket foto profil
            'bucket' => env(
                'SUPABASE_S3_BUCKET',
                'profile-photos'
            ),

            // Endpoint S3 Supabase
            'endpoint' => env('SUPABASE_S3_ENDPOINT'),

            'use_path_style_endpoint' => true,

            // URL publik project Supabase
            'public_base_url' => env(
                'SUPABASE_URL',
                'https://cckkvqsiqpocsbhesgjk.supabase.co'
            ),

            // Berikan error jika upload gagal
            'throw' => true,

            'report' => false,
        ],

        /*
        |--------------------------------------------------------------------------
        | Local Storage
        |--------------------------------------------------------------------------
        */

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        /*
        |--------------------------------------------------------------------------
        | Public Storage
        |--------------------------------------------------------------------------
        */

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL') . '/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        /*
        |--------------------------------------------------------------------------
        | AWS S3 Storage
        |--------------------------------------------------------------------------
        */

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env(
                'AWS_USE_PATH_STYLE_ENDPOINT',
                false
            ),
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
