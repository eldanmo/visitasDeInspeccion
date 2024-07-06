<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SolicitudDiaAdicional extends Model
{
    use HasFactory;

    protected $table = 'solicitud_dias_adicionales';

    protected $fillable = [
        'dias',
        'observacion',
        'estado',
        'id_informe',
        'usuario_creacion',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_creacion');
    }
}
