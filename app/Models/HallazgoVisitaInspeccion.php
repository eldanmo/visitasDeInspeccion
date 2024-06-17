<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HallazgoVisitaInspeccion extends Model
{
    use HasFactory;

    protected $table = 'hallazgos_visita_inspeccion';

    protected $fillable = [
        'id_informe',
        'id_usuario',
        'enlace_hallazgos',
        'estado',
        'usuario_creacion',
        'enlace_hallazgos',
    ];

    public function usuarioCreacion()
    {
        return $this->belongsTo(User::class, 'usuario_creacion');
    }

    public function usuarioAsignado()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }
}
