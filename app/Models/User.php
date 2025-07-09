<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;


class User extends Authenticatable implements JWTSubject
{


    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    use Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'profile_pic', 'document_verified'
    ];

    protected $hidden = ['password', 'remember_token'];

    public function rutinas()
    {
        return $this->hasMany(Rutina::class, 'professional_id');
    }

    public function rutinasAsignadas()
    {
        return $this->belongsToMany(Rutina::class, 'rutina_cliente', 'cliente_id', 'rutina_id')->withTimestamps();
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class);
    }

    public function sesionesGrupales()
    {
        return $this->hasMany(SesionGrupal::class, 'professional_id');
    }

    public function sesionesParticipadas()
    {
        return $this->belongsToMany(SesionGrupal::class, 'sesion_grupal_participantes')->withTimestamps();
    }
}
