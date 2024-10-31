<?php

namespace App\Http\Controllers;

use App\Models\ConteoDias;
use App\Models\Parametro;
use App\Models\Lugares;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User; 
use App\Models\VisitaInspeccion; 


class EstadisticaController extends Controller
{
    /**
     * Muestra el formulario para consultar estadísticas.
     *
     * @return \Illuminate\View\View Devuelve la vista 'estadisticas' que no sean visitas canceladas.
    */

    public function estadisticas(){

        

        return view('estadisticas', []);
    }

    /**
     * Retorna los datos para las estadisticas.
     *
     * @return \Illuminate\View\View Devuelve los datos de las estadisticas de las visitas que no sean canceladas.
    */

    public function estadisticas_datos( Request $request ) {

        try {

            //Query visitas de inspección

            $query = VisitaInspeccion::whereNotIn('estado_informe', ['CANCELADO'])
                ->with('entidad.lugar')
                ->with('conteoDias.usuario')
                ->with('historiales.usuario')
                ->with('usuario')
                ->with('usuarioDiagnostico')
                ->with('grupoInspeccion.usuarioAsignado')
                ->with('etapaProceso');

            if ($request->filled('estado_etapa')) {
                $query->where('estado_etapa', $request->estado_etapa);
            }

            if ($request->filled('etapa_actual')) {
                $query->where('etapa', $request->etapa_actual);
            }

            if ($request->filled('usuario_actual')) {
                $query->whereRaw("JSON_CONTAINS(usuario_actual, '{\"id\": " . $request->usuario_actual . "}')");
            }        

            if ($request->filled('estado_informe')) {
                $query->where('estado_informe', 'like', '%' . $request->estado_informe . '%');
            }

            if ($request->filled('fecha_inicial') && $request->filled('fecha_final')) {
                $fechaInicioDesde = $request->fecha_inicial;
                $fechaInicioHasta = $request->fecha_final;
                $query->whereBetween('created_at', [$fechaInicioDesde, $fechaInicioHasta]);
                $query->whereBetween('updated_at', [$fechaInicioDesde, $fechaInicioHasta]);
            }

            if ($request->filled('departamentos')) {
                $query->whereHas('entidad', function ($query) use ($request) {
                    $query->where('departamento', $request->departamentos);
                });
            }

            if ($request->filled('region')) {
                $query->whereHas('entidad.lugar', function ($query) use ($request) {
                    $query->where('region', $request->region);
                });
            }
            
            if ($request->filled('naturaleza_organizacion')) {
                $query->whereHas('entidad', function ($query) use ($request) {
                    $query->where('naturaleza_organizacion', $request->naturaleza_organizacion);
                });
            }
            
            if ($request->filled('tipo_organizacion')) {
                $query->whereHas('entidad', function ($query) use ($request) {
                    $query->where('tipo_organizacion', $request->tipo_organizacion);
                });
            }
            
            if ($request->filled('nivel_supervision')) {
                $query->whereHas('entidad', function ($query) use ($request) {  
                    $query->where('nivel_supervision', $request->nivel_supervision);
                });
            }
            
            $data = $query->get();

            //Query cantidad de visitas en la consulta actual

            $cantidadDeVisitas = $query->count();

            $usuarios = User::whereIn('profile', ['Coordinador', 'Contratista'])->get();

            $parametros = Parametro::where('proceso', 'VISITAS_INSPECCION')->get();
            $lugares = Lugares::get();
            
            return response()->json(['datos' => $data, 
                                    'usuarios' => $usuarios, 
                                    'parametros' => $parametros,
                                    'cantidad_visitas_actuales' => $cantidadDeVisitas,
                                    'lugares' => $lugares,
                                
                                ]);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }

    }
    
    
}
