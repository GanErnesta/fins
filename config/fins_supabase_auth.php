<?php

return [
    'url' => rtrim(
        (string) env('SUPABASE_PROJECT_URL', ''),
        '/'
    ),

    'public_key' => env(
        'SUPABASE_PUBLISHABLE_KEY',
        env('SUPABASE_ANON_KEY')
    ),

    'admin_key' => env(
        'SUPABASE_SECRET_KEY',
        env('SUPABASE_SERVICE_ROLE_KEY')
    ),

    'timeout' => 15,
];