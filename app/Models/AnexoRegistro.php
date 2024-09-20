<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnexoRegistro extends Model
{
    use HasFactory;

    protected $table = 'anexos_registro';

    protected $fillable = [
        'nombre',
        'ruta',
        'id_entidad',
        'proceso',
        'sub_proceso',
        'id_sub_proceso',
        'tipo_anexo',
        'id_tipo_anexo',
        'estado',  
        'usuario_creacion',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_creacion');
    }
}
