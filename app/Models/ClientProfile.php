<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientProfile extends Model
{
    protected $fillable = [
        'user_id',
        'birth_date',
        'gender',
        'height',
        'weight',
        'activity_level',
        'dietary_preferences',
        'allergies',
        'medical_conditions',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relationship',
        'photo',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'dietary_preferences' => 'array',
        'height' => 'decimal:2',
        'weight' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function goals()
    {
        return $this->hasMany(ClientGoal::class, 'client_id', 'user_id');
    }

    public function measurements()
    {
        return $this->hasMany(ClientMeasurement::class, 'client_id', 'user_id');
    }
}
