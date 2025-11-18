<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NutritionistProfile extends Model
{
    protected $fillable = [
        'user_id',
        'specialization',
        'hourly_rate',
        'bio',
        'certifications',
        'years_experience',
        'availability',
        'photo',
    ];

    protected $casts = [
        'certifications' => 'array',
        'availability' => 'array',
        'hourly_rate' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'user_id', 'user_id');
    }
}
