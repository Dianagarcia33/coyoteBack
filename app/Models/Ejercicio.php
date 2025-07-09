<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ejercicio extends Model
{
    protected $fillable = ['rutina_id', 'name', 'reps', 'sets'];

    public function rutina()
    {
        return $this->belongsTo(Rutina::class);
    }
}
