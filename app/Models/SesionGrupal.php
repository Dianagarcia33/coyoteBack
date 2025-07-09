<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SesionGrupal extends Model
{
    protected $fillable = ['name', 'professional_id', 'scheduled_at', 'duration_minutes', 'meeting_link'];

    public function profesional()
    {
        return $this->belongsTo(User::class, 'professional_id');
    }

    public function participantes()
    {
        return $this->belongsToMany(User::class, 'sesion_grupal_participantes')->withTimestamps();
    }
}
