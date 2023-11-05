<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Actividad extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre', 'descripcion','duracion',
        'precio_adulto', 'precio_nino','aforo','imagen','activa'
    ];

    public function horarios()
    {
        return $this->hasMany(Horario::class);
    }

    public function valoraciones()
    {
        return $this->hasMany(Valoracion::class);
    }
}
