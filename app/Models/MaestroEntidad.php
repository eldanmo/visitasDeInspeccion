<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaestroEntidad extends Model
{
    protected $table = 'maestro_entidades';

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

        'objeto_social',
        'entidad_que_vigila_rues',
        'estado_matricula_rues',
        'vigilada_supersolidaria_segun_depuracion',
        'entidad_debe_vigilar_segun_depuracion',
        'correo_notificaciones_judiciales',
        'permiten_notificacion_correo_electronico',
        'en_liquidacion_rues',
        'tipo_liquidacion_rues',
        'otro_tipo_liquidacion_rues',
        'ecomun',
        'cafetera',
        'ano_renovacion_matricula',
        'fecha_renovacion_matricula',
        'certificado_rues',
        'codigos_actividades_financieras',
        'otro_ente_vigilancia_rues',
        'otro_ente_vigilancia',

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
        'observaciones',
        'carpeta_drive',
    ];


    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_creacion');
    }

    public function historiales()
    {
        return $this->hasMany(HistorialVisitas::class, 'id_informe', 'id')
                    ->where('proceso', 'ENTIDAD_INDIVIDUAL');
    }

    public function anexos()
    {
        return $this->hasMany(AnexoRegistro::class, 'id_entidad', 'id')
                            ->where('proceso', 'ENTIDAD_INDIVIDUAL')
                            ->where('estado', 'ACTIVO');
    }
}
