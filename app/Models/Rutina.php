<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rutina extends Model
{
    protected $fillable = ['title', 'description', 'professional_id'];

    public function profesional()
    {
        return $this->belongsTo(User::class, 'professional_id');
    }

    public function ejercicios()
    {
        return $this->hasMany(Ejercicio::class);
    }

    public function clientes()
    {
        return $this->belongsToMany(User::class, 'rutina_cliente', 'rutina_id', 'cliente_id')->withTimestamps();
    }
}
