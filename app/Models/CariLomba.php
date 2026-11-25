<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CariLomba extends Model
{
    protected $table = 'cari_lomba';

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'subtitle',
        'category',
        'organizer',
        'deadline',
        'team',
        'description',
        'skills',
        'image',
    ];

    protected $casts = [
        'deadline' => 'date',
        'skills' => 'array',
    ];

    // Relasi dengan pengguna yang memposting lomba
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}