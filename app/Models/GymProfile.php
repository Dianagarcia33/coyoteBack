<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GymProfile extends Model
{
    protected $fillable = [
        'user_id',
        'business_name',
        'gym_type',
        'specialties',
        'address',
        'phone',
        'opening_hours',
        'machines',
        'classes',
        'description',
        'photos',
        'social_media',
    ];

    protected $casts = [
        'specialties' => 'array',
        'machines' => 'array',
        'classes' => 'array',
        'photos' => 'array',
        'social_media' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
