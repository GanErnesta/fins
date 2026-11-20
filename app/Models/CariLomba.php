<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CariLomba extends Model
{
    protected $table = 'cari_lomba';

    protected $fillable = [
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
        'team' => 'integer',
    ];

    public function getRouteKeyName()
    {
        return 'slug';
    }
    
}