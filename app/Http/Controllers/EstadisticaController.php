<?php

namespace App\Http\Controllers;

use App\Models\ConteoDias;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\VisitaInspeccion; 


class EstadisticaController extends Controller
{
    /**
     * Muestra el formulario para consultar estadísticas.
     *
     * @return \Illuminate\View\View Devuelve la vista 'estadisticas' que no sean visitas canceladas.
    */

    public function estadisticas(){
        $cantidadDeVisitas = VisitaInspeccion::whereNotIn('estado_informe', ['CANCELADO'])
                ->count();

        return view('estadisticas', [
            'cantidad_visitas_actuales' => $cantidadDeVisitas,
        ]);
    }

    /**
     * Retorna los datos para las estadisticas.
     *
     * @return \Illuminate\View\View Devuelve los datos de las estadisticas de las visitas que no sean canceladas.
    */

    public function estadisticas_datos() {

        try {
            $data = VisitaInspeccion::whereNotIn('estado_informe', ['CANCELADO'])
                ->with('entidad')
                ->with('conteoDias.usuario')
                ->with('historiales.usuario')
                ->with('usuario')
                ->with('usuarioDiagnostico')
                ->with('grupoInspeccion.usuarioAsignado')
                ->get();

            $conteo_dias = ConteoDias::select('etapa', DB::raw('AVG(conteo_dias) as promedio_conteo_dias'))
                ->groupBy('etapa')
                ->get();
            
            return response()->json(['datos' => $data, 'conteo_dias' => $conteo_dias]);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }

    }
    
    
}
