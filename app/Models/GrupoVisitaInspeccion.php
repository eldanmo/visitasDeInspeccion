<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrupoVisitaInspeccion extends Model
{
    use HasFactory;

    protected $table = 'grupo_vista_inspeccion';

    protected $fillable = [
        'id_informe',
        'id_usuario',
        'rol',
        'estado',
        'enlace_hallazgos',
        'informe_firmado',
        'usuario_creacion',
        'permiso_carpeta_drive',
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
