<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Entidad extends Model
{
    protected $table = 'entidades';

    use HasFactory;

    protected $fillable = [
        'codigo_entidad',
        'nit',
        'razon_social',
        'sigla',
        'nivel_supervision',
        'naturaleza_organizacion',
        'tipo_organizacion',
        'categoria',
        'grupo_niif',
        'incluye_sarlaft',
        'ciudad_municipio',
        'departamento',
        'direccion',
        'numero_asociados',
        'numero_empleados',
        'total_activos',
        'total_pasivos',
        'total_patrimonio',
        'total_ingresos',
        'fecha_ultimo_reporte',
        'fecha_corte_visita',
        'representate_legal',
        'correo_representate_legal',
        'telefono_representate_legal',
        'tipo_revisor_fiscal',
        'razon_social_revision_fiscal',
        'nombre_revisor_fiscal',
        'direccion_revisor_fiscal',
        'telefono_revisor_fiscal',
        'correo_revisor_fiscal',
        'usuario_creacion',
        'estado',
        'motivo',
    ];
}
