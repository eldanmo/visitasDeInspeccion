<?php

namespace App\Http\Controllers;
use App\Models\VisitaInspeccion; 
use App\Models\GrupoVisitaInspeccion; 
use App\Models\User;  
use App\Models\HistorialVisitas;
use App\Models\ConteoDias;
use App\Models\DiaNoLaboral;
use App\Models\Entidad;
use Illuminate\Support\Facades\Auth;

use Illuminate\Http\Request;
use App\Models\Parametro;
use Illuminate\Support\Facades\Mail;
use App\Mail\CorreosVistasInspeccion;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Google\Service\Drive\Resource\Folder;
use Google\Service\Drive\Permission;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use PhpOffice\PhpSpreadsheet\IOFactory;

class DiagnosticoController extends Controller
{

    public function crear()
    {
        $diasDiagnostico = Parametro::select('dias')->where('estado', 'DIAGNÓSTICO INTENDENCIA')->first();
        $nombreUsuario = Auth::user()->name;

        return view('crear_diagnostico', [
            'dias_diagnostico' => $diasDiagnostico,
            'nombreUsuario' => $nombreUsuario
        ]);
    }

    public function crear_diagnostico(Request $request) {

        try {

            $validatedData = $request->validate([
                'fecha_inicio_diagnostico' => 'required',
                'fecha_fin_diagnostico' => 'required',
                'id_entidad' => 'required',
                'razon_social' => 'required',
                'nit' => 'required',
            ]);

            $informe_entidad = VisitaInspeccion::where('id_entidad', $validatedData['id_entidad'])
                                                ->whereNotIn('estado_informe', ['FINALIZADO', 'CANCELADO'])
                                                ->get();

            if ($informe_entidad->count() > 0) {
                return response()->json(['error' => 'La entidad ya se encuentra con un informe activo'], 422);
            }
    
            $usuarioCreacionId = Auth::id();
            $userName = Auth::user()->name;
            $anio_actual = date('Y');

            $visita_inspeccion = new VisitaInspeccion();
            $visita_inspeccion->fecha_inicio_diagnostico = $validatedData['fecha_inicio_diagnostico'];
            $visita_inspeccion->id_entidad = $validatedData['id_entidad'];
            $visita_inspeccion->usuario_creacion = $usuarioCreacionId;
            $visita_inspeccion->etapa = 'DIAGNÓSTICO INTENDENCIA';
            $visita_inspeccion->estado_informe = 'VIGENTE';
            $visita_inspeccion->estado_etapa = 'VIGENTE';
            $visita_inspeccion->usuario_diagnostico = $usuarioCreacionId;
            $visita_inspeccion->usuario_actual = json_encode([['id' => $usuarioCreacionId, 'nombre' => $userName]]);
            $visita_inspeccion->save();

            $visita_inspeccion->numero_informe = $visita_inspeccion->id . $anio_actual;
            $visita_inspeccion->save();

            $this->historialInformes($visita_inspeccion->id, 'CREACIÓN', 'DIAGNÓSTICO INTENDENCIA', 'VIGENTE', date('Y-m-d'), '', 'VIGENTE', '', $validatedData['fecha_inicio_diagnostico'], NULL, '', $validatedData['fecha_fin_diagnostico']);
            $this->conteoDias($visita_inspeccion->id, 'DIAGNÓSTICO INTENDENCIA', $validatedData['fecha_inicio_diagnostico'], NULL);

            $asunto_email = 'Creación diagnóstico '.$visita_inspeccion->numero_informe;
            $datos_adicionales = ['numero_informe' => 'Se ha creado el diagnóstico número '.$visita_inspeccion->numero_informe,
                                    'mensaje' => 'Se realizó la creación del diagnóstico de la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                                     $validatedData['nit'] . ' recuerde registrar la finalización de este DIAGNÓSTICO INTENDENCIA antes del '. $validatedData['fecha_fin_diagnostico']];
            $this->enviar_correos($usuarioCreacionId, $asunto_email, $datos_adicionales);

            $successMessage = 'Diágnostico creado correctamente';

            return response()->json(['message' => $successMessage]);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }

    }

    public function enviar_correos($usuario, $asunto = '', $datos_adicionales = []){

        if (is_int($usuario) || is_string($usuario) ) {
            $email_usuario = User::select('email')
                                    ->where('id',$usuario)
                                    ->first();
            Mail::to($email_usuario)
                ->send(new CorreosVistasInspeccion($asunto, $datos_adicionales));
        }if ( is_array($usuario) ) {
            foreach($usuario as $user){
                $email_usuario = User::select('email')
                                    ->where('id',$user->id)
                                    ->first();
                Mail::to($email_usuario)
                    ->send(new CorreosVistasInspeccion($asunto, $datos_adicionales));
            }
        }

    }

    public function historialInformes($id_informe, $accion, $etapa, $estado, $fecha_creacion, $observaciones, $estado_etapa, $usuario_asignado, $fecha_inicio, $fecha_fin, $conteo_dias, $fecha_limite_etapa) {
        $usuarioCreacionId = Auth::id();
        
        $historial_informe = new HistorialVisitas();
        $historial_informe->id_informe = $id_informe;
        $historial_informe->accion=$accion;
        $historial_informe->etapa=$etapa;
        $historial_informe->estado=$estado;
        $historial_informe->fecha_creacion=$fecha_creacion;
        $historial_informe->observaciones=$observaciones;
        $historial_informe->estado_etapa=$estado_etapa;
        $historial_informe->usuario_asignado=$usuario_asignado;
        $historial_informe->fecha_inicio=$fecha_inicio;
        $historial_informe->fecha_fin=$fecha_fin;
        $historial_informe->conteo_dias=$conteo_dias;
        $historial_informe->fecha_limite_etapa=$fecha_limite_etapa;
        $historial_informe->usuario_creacion= $usuarioCreacionId;
        $historial_informe->save();

        Log::info('Entrando a historialInformes', [
            'id_informe' => $id_informe,
            'accion' => $accion,
            'etapa' => $etapa,
            'estado' => $estado,
            'fecha_creacion' => $fecha_creacion,
            'observaciones' => $observaciones,
            'estado_etapa' => $estado_etapa,
            'usuario_asignado' => $usuario_asignado,
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin,
            'conteo_dias' => $conteo_dias,
            'fecha_limite_etapa' => $fecha_limite_etapa,
        ]);
    }

    public function conteoDias($id_informe, $etapa, $fecha_inicial, $fecha_final) {
        $usuarioCreacionId = Auth::id();

        $conteo_dia = 0;
        $fecha_limite_etapa = '';

        $dias_etapa = Parametro::select('dias')->where('estado', $etapa)->first();

        $diasFestivosColombia = DiaNoLaboral::pluck('dia')->toArray();

        if ($dias_etapa->dias == 0 && $etapa != 'ASIGNACIÓN GRUPO DE INSPECCIÓN' && $etapa != 'EN REVISIÓN DEL INFORME DIAGNÓSTICO') {
            $fecha_limite_etapa_anterior = ConteoDias::select('fecha_limite_etapa')
                                            ->where('id_informe', $id_informe)
                                            ->where('dias_habiles', '!=', 0)
                                            ->orderBy('created_at', 'desc')
                                            ->first();

            $fecha_limite_etapa = $fecha_limite_etapa_anterior->fecha_limite_etapa;
        }else{
            $fecha_limite_etapa = $this->sumarDiasHabiles($fecha_inicial, $dias_etapa->dias, $diasFestivosColombia, $id_informe, $etapa);
        }

        $conteo_dias = new ConteoDias();
        $conteo_dias->id_informe = $id_informe;
        $conteo_dias->etapa=$etapa;
        $conteo_dias->fecha_inicio=$fecha_inicial;
        $conteo_dias->fecha_fin=$fecha_final;
        $conteo_dias->dias_habiles=$dias_etapa->dias;
        $conteo_dias->conteo_dias = $conteo_dia;
        $conteo_dias->fecha_limite_etapa = $fecha_limite_etapa;
        $conteo_dias->usuario_creacion = $usuarioCreacionId;
        $conteo_dias->save();
    }

    public function actualizarConteoDias($id_informe, $etapa, $fecha_final, $tipo = '') {

        $conteo_dia = 0;

        $diasFestivosColombia = DiaNoLaboral::pluck('dia')->toArray();

        $conteo_dias = ConteoDias::where('id_informe', $id_informe )
                                    ->where('etapa', $etapa)
                                    ->orderBy('created_at', 'desc')
                                    ->first();

        $conteo_dia = $this->contarDiasHabiles(Carbon::parse($conteo_dias->fecha_inicio), $fecha_final, $diasFestivosColombia);

        if($tipo == 'cron'){
            $conteo_dias->conteo_dias = $conteo_dia;
        }else{
            $conteo_dias->fecha_fin = $fecha_final;
            $conteo_dias->conteo_dias = $conteo_dia;
        }
        $conteo_dias->save();

    }

    public function esDiaHabilColombia($fecha, $diasFestivosColombia) {
        $diaSemana = date('N', strtotime($fecha));
    
        return $diaSemana >= 1 && $diaSemana <= 5 && !in_array($fecha, $diasFestivosColombia);
    }

    public function sumarDiasHabiles($fechaInicial, $dias, $diasFestivosColombia, $id_informe = '', $etapa='') {

        $contador = 0;
        $fecha = strtotime($fechaInicial);

        if ($dias == 0 && ($etapa !='ASIGNACIÓN GRUPO DE INSPECCIÓN' && $etapa !='EN REVISIÓN DEL INFORME DIAGNÓSTICO')) {
            $fecha_limite_etapa_anterior = ConteoDias::select('fecha_limite_etapa')
                                            ->where('id_informe', $id_informe)
                                            ->where('dias_habiles', '!=', 0)
                                            ->orderBy('created_at', 'desc')
                                            ->first();
            
            if ($fecha_limite_etapa_anterior) {
                $fecha = strtotime($fecha_limite_etapa_anterior->fecha_limite_etapa);
            } else {
                dd($id_informe);
            }
        }else{
        
            while ($contador < intval($dias)) {
                $fecha = strtotime('+1 day', $fecha);
                $fechaStr = date('Y-m-d', $fecha);
        
                if ($this->esDiaHabilColombia($fechaStr, $diasFestivosColombia)) {
                    $contador++;
                }
            }
        }
        
        return date('Y-m-d', $fecha);
    }

    public function consultar_informes(Request $request) {

        $informes = VisitaInspeccion::query();

        if ($request->filled('numero_informe')) {
            $informes->where('numero_informe', 'like', '%' . $request->numero_informe . '%');
        }

        if ($request->filled('estado_etapa')) {
            $informes->where('estado_etapa', 'like', '%' . $request->estado_etapa . '%');
        }

        if ($request->filled('usuario_actual')) {
            $informes->whereRaw("JSON_CONTAINS(usuario_actual, '{\"id\": " . $request->usuario_actual . "}')");
        }        

        if ($request->filled('nombre_entidad')) {
            $informes->whereHas('entidad', function ($query) use ($request) {
                $query->where('razon_social', 'like', '%' . $request->nombre_entidad . '%');
            });
        }

        if ($request->filled('nit_entidad')) {
            $informes->whereHas('entidad', function ($query) use ($request) {
                $query->where('nit', 'like', '%' . $request->nit_entidad . '%');
            });
        }

        if ($request->filled('estado_informe')) {
            $informes->where('estado_informe', 'like', '%' . $request->estado_informe . '%');
        }

        if ($request->filled('etapa_actual')) {
            $informes->where('etapa', 'like', '%' . $request->etapa_actual . '%');
        }

        if ($request->filled('fecha_inicial') && $request->filled('fecha_final')) {
            $fechaInicioDesde = $request->fecha_inicial;
            $fechaInicioHasta = $request->fecha_final;
            $informes->whereBetween('fecha_inicio_gestion', [$fechaInicioDesde, $fechaInicioHasta]);
        }

        if ($request->filled('fecha_modificacion_desde') && $request->filled('fecha_modificacion_hasta')) {
            $fechaModificacionDesde = $request->fecha_inicial;
            $fechaModificacionHasta = $request->fecha_final;
            $informes->whereBetween('fecha_fin_gestion', [$fechaModificacionDesde, $fechaModificacionHasta]);
        }

        if (!$request->filled(['numero_informe', 'estado_etapa', 'estado_informe', 'usuario_actual', 'nombre_entidad', 'etapa_actual', 'nit_entidad'])) {
            $informes->get();
        }

        $usuarios = User::get();
        $informes = $informes->with('entidad')->orderby('id', 'desc')->paginate(10);
        $parametros = Parametro::get();

        return view('consultar_informe', [
            'usuarios' => $usuarios,
            'informes' => $informes,
            'parametros' => $parametros
        ]);
    }

    public function vista_informe($id)
    {
        $informe = VisitaInspeccion::where('id', $id)
                                    ->with('entidad')
                                    ->with('conteoDias.usuario')
                                    ->with('historiales.usuario')
                                    ->with('usuario')
                                    ->with('usuarioDiagnostico')
                                    ->with('grupoInspeccion.usuarioAsignado')
                                    ->first();
        $usuarios = User::orderby('name', 'ASC')->get();
        return view('detalle_informe', [
            'usuariosTotales' => $usuarios,
            'informe' => $informe,
        ]);
    }

    public function guardar_observacion(Request $request) {
        try {

            $usuarioCreacionId = Auth::id();

            $validatedData = $request->validate([
                'observaciones' => 'required',
                'id' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'estado_etapa' => 'required',
                'numero_informe' => 'required',
                'razon_social' => 'required',
                'nit' => 'required',
                'accion' => 'required',
            ]);

            $visita_inspeccion = VisitaInspeccion::findOrFail($validatedData['id']);

            if($validatedData['accion'] === 'observacion'){
                $this->historialInformes($validatedData['id'], 'OBSERVACIÓN', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $validatedData['observaciones'], $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                $asunto_email = 'Observación a visita de inspección '.$validatedData['numero_informe'];
                $datos_adicionales = ['numero_informe' => 'Se ha registrado una observación para la visita de inspección '. $validatedData['numero_informe'],
                                        'mensaje' => 'Se registro una observación para la visita de inspección a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                $validatedData['nit'] . ' con la siguiente observación '. $validatedData['observaciones']];

                $successMessage = 'Observación registrada correctamente';
            }else{
                $visita_inspeccion->estado_informe = 'CANCELADO';
                $visita_inspeccion->etapa = 'CANCELADO';
                $visita_inspeccion->estado_etapa = 'CANCELADO';
                $visita_inspeccion->save();

                $this->historialInformes($validatedData['id'], 'CANCELACIÓN', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $validatedData['observaciones'], $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                $asunto_email = 'Visita de inspección cancelada '.$validatedData['numero_informe'];
                $datos_adicionales = ['numero_informe' => 'Se ha cancelado la visita de inspección '. $validatedData['numero_informe'],
                                        'mensaje' => 'Se canceló la visita de inspección a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                $validatedData['nit'] . 'Con la siguiente observación: '. $validatedData['observaciones']];

                $successMessage = 'Visita cancelada correctamente';
            }

            foreach ( json_decode($visita_inspeccion->usuario_actual) as $usuario) {
                $this->enviar_correos($usuario->id, $asunto_email, $datos_adicionales);
            }
            
            return response()->json(['message' => $successMessage]);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }

    public function actualizar_dias_visitas() {
        $fecha_actual = now();
        $totalDiasVisita = Parametro::sum('dias');
        $visita_inspeccion = VisitaInspeccion::whereNotIn('etapa', ['CANCELADO', 'FINALIZADO'])
                                                ->with('etapaProceso')
                                                ->with('entidad')
                                                ->get();
    
        if ($visita_inspeccion->count() > 0) {
            foreach ($visita_inspeccion as $visita) {

                $diasFestivosColombia = DiaNoLaboral::pluck('dia')->toArray();

                $fecha_asignacion_grupo_inspeccion = ConteoDias::where('id_informe', $visita->id)
                                                                ->where('etapa', 'ASIGNACIÓN GRUPO DE INSPECCIÓN')
                                                                ->select('fecha_inicio')
                                                                ->first();
        
                if ($fecha_asignacion_grupo_inspeccion) {
                
                    $fecha_limite_etapa = $this->sumarDiasHabiles($fecha_asignacion_grupo_inspeccion['fecha_inicio'], $visita->etapaProceso->dias, $diasFestivosColombia, $visita->id);

                    if ($fecha_actual > $fecha_limite_etapa) {
                        echo 'Número de visita: '. $visita->numero_informe . ' alerta vencimiento de etapa <br>';

                        $visita_inspeccion = VisitaInspeccion::findOrFail($visita->id);
                        $visita_inspeccion->estado_etapa = 'EN DESTIEMPO';
                        $visita_inspeccion->save();

                        $asunto_email = 'Alerta vencimiento de etapa de visita '.$visita->numero_informe;
                        $datos_adicionales = ['numero_informe' => 'Alerta vencimiento de etapa de visita '.$visita->numero_informe,
                                                'mensaje' => 'La visita número '. $visita->numero_informe. ' a la entidad ' . $visita->entidad->razon_social . ' se encuentra en destiempo en la etapa '. $visita->etapa . ', por favor ingresar a la plataforma y realizar la respectiva gestión.'];
                        $this->enviar_correos( json_decode($visita->usuario_actual) , $asunto_email, $datos_adicionales);
                    }

                    $diasHabilesTranscurridos = $this->contarDiasHabiles(Carbon::parse($fecha_asignacion_grupo_inspeccion['fecha_inicio']), $fecha_actual, $diasFestivosColombia);

                    if ($diasHabilesTranscurridos > $totalDiasVisita) {
                        echo 'Número de visita: '. $visita->numero_informe . ' informe vencido <br>';

                        $visita_inspeccion = VisitaInspeccion::findOrFail($visita->id);
                        $visita_inspeccion->estado_informe = 'EN DESTIEMPO';
                        $visita_inspeccion->save();

                        $asunto_email = 'Alerta vencimiento de visita de inspección '.$visita->numero_informe;
                        $datos_adicionales = ['numero_informe' => 'Alerta vencimiento de visita de inspección '.$visita->numero_informe,
                                                'mensaje' => 'La visita número '. $visita->numero_informe. ' a la entidad ' . $visita->entidad->razon_social . ' se encuentra vencida en la etapa '. $visita->etapa . ', por favor ingresar a la plataforma y realizar la respectiva gestión.'];
                        $this->enviar_correos( $visita->usuario_creacion, $asunto_email, $datos_adicionales);
                    }

                    $diasHabilesFaltantes = $this->diasHabilesRestantes($fecha_limite_etapa, $diasFestivosColombia);

                    if ($diasHabilesFaltantes == 1 || $diasHabilesFaltantes == 2) {

                        $asunto_email = 'Alerta gestión de visita '.$visita->numero_informe;
                        $datos_adicionales = ['numero_informe' => 'Alerta gestión de visita '.$visita->numero_informe,
                                                'mensaje' => 'La visita número '. $visita->numero_informe. ' a la entidad ' . $visita->entidad->razon_social . ' se encuentra a '. $diasHabilesFaltantes . ' días habiles para su vencimiento, por favor ingresar a la plataforma y realizar la respectiva gestión.'];

                        $usuarios = json_decode($visita->usuario_actual);
        
                        foreach ($usuarios as $usuario) {
                            $this->enviar_correos($usuario, $asunto_email, $asunto_email, $datos_adicionales);
                        }

                    }
                }else {
                    $fecha_intendencia = ConteoDias::where('id_informe', $visita->id)
                        ->where('etapa', 'DIAGNÓSTICO INTENDENCIA')
                        ->select('fecha_inicio')
                        ->first();

                    if ($fecha_intendencia) {
                        $fecha_limite_etapa = $this->sumarDiasHabiles($fecha_intendencia['fecha_inicio'], $visita->etapaProceso->dias, $diasFestivosColombia, $visita->id);

                        if ($fecha_actual > $fecha_limite_etapa) {
                            echo 'Número de visita: '. $visita->numero_informe . ' alerta vencimiento de etapa <br>';
    
                            $visita_inspeccion = VisitaInspeccion::findOrFail($visita->id);
                            $visita_inspeccion->estado_etapa = 'EN DESTIEMPO';
                            $visita_inspeccion->save();
    
                            $asunto_email = 'Alerta vencimiento de etapa de visita '.$visita->numero_informe;
                            $datos_adicionales = ['numero_informe' => 'Alerta vencimiento de etapa de visita '.$visita->numero_informe,
                                                    'mensaje' => 'La visita número '. $visita->numero_informe. ' a la entidad ' . $visita->entidad->razon_social . ' se encuentra en destiempo en la etapa '. $visita->etapa . ', por favor ingresar a la plataforma y realizar la respectiva gestión.'];
                            $this->enviar_correos( json_decode($visita->usuario_actual) , $asunto_email, $datos_adicionales);
                        }

                        $diasHabilesFaltantes = $this->diasHabilesRestantes($fecha_limite_etapa, $diasFestivosColombia);

                        if ($diasHabilesFaltantes == 1 || $diasHabilesFaltantes == 2) {
                            echo 'Número de visita: '. $visita->numero_informe . ' notificación previa vencimiento de etapa <br>';

                            $asunto_email = 'Alerta gestión de visita '.$visita->numero_informe;
                            $datos_adicionales = ['numero_informe' => 'Alerta gestión de visita '.$visita->numero_informe,
                                                    'mensaje' => 'La visita número '. $visita->numero_informe. ' a la entidad ' . $visita->entidad->razon_social . ' se encuentra a '. $diasHabilesFaltantes . ' días habiles para su vencimiento, por favor ingresar a la plataforma y realizar la respectiva gestión.'];
    
                            $usuarios = json_decode($visita->usuario_actual);
            
                            foreach ($usuarios as $usuario) {
                                $this->enviar_correos($usuario, $asunto_email, $asunto_email, $datos_adicionales);
                            }
    
                        }
                    }
                }
            }

            echo 'cron ejecutado';
        }else {
            echo 'No se escontraron registros';
        }
    }

    public function actualizar_historico_visitas() {

        $visita_inspeccion = VisitaInspeccion::whereNotIn('etapa', ['CANCELADO', 'FINALIZADO'])
                                                ->with('etapaProceso')
                                                ->with('entidad')
                                                ->get();
    
        if ($visita_inspeccion->count() > 0) {
            foreach ($visita_inspeccion as $visita) {

                $conteo_dias = ConteoDias::where('id_informe', $visita->id)
                                            ->whereNull('fecha_fin')
                                            ->orderBy('created_at', 'desc')
                                            ->first();

                if ($conteo_dias) {
                    $this->actualizarConteoDias($visita->id, $conteo_dias->etapa, date('Y-m-d'), 'cron');
                    echo 'Se actualizo la etapa '.$conteo_dias->etapa.' del informe '.$visita->numero_informe.', ';
                }
            }

            echo 'cron ejecutado';
        }else {
            echo 'No se encontraron registros';
        }
    }

    public function contarDiasHabiles($fechaInicial, $fechaFinal, $diasFestivosColombia) {
        $diasHabiles = 0;
        $fecha = $fechaInicial->copy();
        while ($fecha <= $fechaFinal) {
            if ($fecha->isWeekday() && $diasFestivosColombia) {
                $diasHabiles++;
            }
            $fecha->addDay();
        }
        return $diasHabiles;
    }

    public function diasHabilesRestantes($fechaLimite, $diasFestivos) {
        $contador = 0;
        $fechaActual = Carbon::now();
        $fechaLimite = Carbon::parse($fechaLimite);
    
        while ($fechaActual->lessThanOrEqualTo($fechaLimite)) {

            if ($fechaActual->isWeekday() && !$this->esFestivo($fechaActual, $diasFestivos)) {
                $contador++;
            }

            $fechaActual->addDay();
        }
        return $contador;
    }
    
    private function esFestivo($fecha, $diasFestivos) {
        return in_array($fecha->format('Y-m-d'), $diasFestivos);
    }

    public function finalizar_diagnostico(Request $request) {
        try {

            $validatedData = $request->validate([
                'ciclo_vida_diagnostico' => 'required|file|max:6000|mimes:pdf,doc,docx,xls,xlsx',
                'anexo_diagnostico.*' => 'file|max:6000|mimes:pdf,doc,docx,xls,xlsx',
                'nombre_anexo_diagnostico.*' => 'string',
                'id' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'estado_etapa' => 'required',
                'numero_informe' => 'required',
                'razon_social' => 'required',
                'nit' => 'required',
                'codigo' => 'required',
                'sigla' => 'required',
                'observacion' => '',
            ]);

            $banderaAnexos = false;

            if ($request->file('anexo_diagnostico')) {
                $uploadedFiles = $request->file('anexo_diagnostico');
                $fileNames = $request->input('nombre_anexo_diagnostico');
                $banderaAnexos = true;
            }

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {  

                $accessToken = auth()->user()->google_token;
                $folderId = "";
                $anexos_adicionales_diagnostico = [];

                $folderData = [
                    'name' => $validatedData['codigo'].'_'.$validatedData['nit'].'_'.$validatedData['sigla'].'_'.$validatedData['numero_informe'],
                    'parents' => ['1TXNr751iU7oHoSTFDUx1mwtxDyw_rVzP'],
                    'mimeType' => 'application/vnd.google-apps.folder',
                ];

                $response = Http::withToken($accessToken)->post('https://www.googleapis.com/drive/v3/files', $folderData);

                if ($response->successful()) {
                    $folder = $response->json();
                    $folderId = $folder['id'];
            
                    $pdfPath = $request->file('ciclo_vida_diagnostico')->getRealPath();
                    $pdfName = $request->file('ciclo_vida_diagnostico')->getClientOriginalName();

                    $uniqueCode = Str::random(8);
                    $fecha = date('Ymd');
                    $nameFormat = str_replace(' ', '_', $pdfName);

                    $archivoName = "{$fecha}_{$uniqueCode}_{$nameFormat}";
            
                    $metadata = [
                        'name' =>  $archivoName,
                        'parents' => [$folderId],
                    ];

                    $response = Http::withToken($accessToken)
                        ->attach(
                            'data',
                            json_encode($metadata),
                            'metadata.json'
                        )
                        ->attach(
                            'file',
                            file_get_contents($pdfPath),
                            $archivoName
                        )
                        ->post('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart');

                        if ($response->successful()) {
                            $file = $response->json();
                            $fileId = $file['id'];
                            $fileUrl = 'https://drive.google.com/file/d/' . $fileId . '/view';
                
                        } else {
                            return response()->json(['error' => $response->json()['error']['message']], 500);
                        }

                    if ($banderaAnexos) {
                            foreach ($uploadedFiles as $index =>$newFile) {

                                $uniqueCode = Str::random(8);
                                $fecha = date('Ymd');

                                if($fileNames[$index]){
                                    $nameFormat = str_replace(' ', '_', $fileNames[$index]);
                                }else{
                                    $nameFormat = str_replace(' ', '_', $newFile->getClientOriginalName());
                                }
    
                                $newFileName = "{$fecha}_{$uniqueCode}_{$nameFormat}";
                                $metadata['name'] = $newFileName;
                                
                                $responseAnexos = Http::withToken($accessToken)
                                ->attach(
                                    'metadata',
                                    json_encode($metadata),
                                    'metadata.json'
                                )
                                ->attach(
                                    'file',
                                    file_get_contents($newFile),
                                    $newFileName
                                )
                                ->post('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart');
        
                                if ($responseAnexos->successful()) {
                                    $file = $response->json();
                                    $fileId = $file['id'];
                                    $fileUrlAnexo = 'https://drive.google.com/file/d/' . $fileId . '/view';

                                    $anexos_adicionales_diagnostico[] = ["fileName" => $fileNames[$index], "fileUrl" =>  $fileUrlAnexo];
                        
                                } else {
                                    return response()->json(['error' => $responseAnexos->json()['error']['message']], 500);
                                }
                            }
                    }
            
                } else {
                    return response()->json(['error' => $response->json()['error']['message']], 500);
                }
                
                $diasFestivosColombia = DiaNoLaboral::pluck('dia')->toArray();

                $fecha_inicio_gestion = ConteoDias::where('id_informe', $validatedData['id'])
                                                    ->where('etapa', $validatedData['etapa'])
                                                    ->first();

                $this->sumarDiasHabiles($fecha_inicio_gestion->fecha_inicio, $visita_inspeccion->etapaProceso->dias, $diasFestivosColombia, $validatedData['id'], $validatedData['etapa']);

                $estado_etapa = 'VIGENTE';                 

                $usuarios_coordinadores = User::where('profile', 'Coordinador')
                                            ->get();
                
                $usuarios = [];

                $asunto_email = 'Informe diágnostico creado en eSigna '.$validatedData['numero_informe'];
                $datos_adicionales = ['numero_informe' => 'Se ha creado el informe diagnóstico en eSigna de la visita de inspección '. $validatedData['numero_informe'],
                                            'mensaje' => 'Se creo el informe diagnóstico de la visita a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                $validatedData['nit'] . ' con el ciclo de vida en eSigna número : '. $validatedData['ciclo_vida_diagnostico']];

                foreach ($usuarios_coordinadores as $usuario) {
                    $usuarios[] = ['id' => $usuario->id, 'nombre' => $usuario->name];

                    $this->enviar_correos($usuario->id, $asunto_email, $datos_adicionales);
                }

                $usuariosSinDuplicados = collect($usuarios)->unique('id');

                $visita_inspeccion->etapa = 'ASIGNACIÓN GRUPO DE INSPECCIÓN';
                $visita_inspeccion->estado_etapa = $estado_etapa;
                $visita_inspeccion->ciclo_vida_diagnostico = $fileUrl;
                $visita_inspeccion->fecha_fin_diagnostico = date('Y-m-d');
                $visita_inspeccion->usuario_actual = json_encode($usuariosSinDuplicados);
                $visita_inspeccion->carpeta_drive = $folderId;
                $visita_inspeccion->documentos_adicionales_diagnostico = $anexos_adicionales_diagnostico;
                $visita_inspeccion->save();

                $this->historialInformes($validatedData['id'], 'FINALIZACIÓN DIAGNÓSTICO', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $validatedData['observacion'], $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                $this->actualizarConteoDias($visita_inspeccion->id, $validatedData['etapa'], date('Y-m-d'));

                $successMessage = 'Diagnóstico enviado correctamente';

                return response()->json(['message' => $successMessage]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }

    public function asignar_grupo_inspeccion(Request $request) {
        try {

            $validatedData = $request->validate([
                'grupo_inspeccion' => 'required',
                'id' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'estado_etapa' => 'required',
                'numero_informe' => 'required',
                'razon_social' => 'required',
                'nit' => 'required',
                'observacion' => '',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                ->with('etapaProceso')
                                                ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {
                $this->conteoDias($validatedData['id'], 'ASIGNACIÓN GRUPO DE INSPECCIÓN', date('Y-m-d'), NULL);

                $grupo_visita_inspeccion = json_decode($validatedData['grupo_inspeccion'], true);

                $usuarios = [];

                

                foreach ($grupo_visita_inspeccion as $index => $persona) {
                    $grupo_visita_inspeccion = new GrupoVisitaInspeccion();
                    $grupo_visita_inspeccion->id_informe = $validatedData['id'];
                    $grupo_visita_inspeccion->id_usuario = $persona['usuario'];
                    $grupo_visita_inspeccion->rol = $persona['rol'];
                    $grupo_visita_inspeccion->estado = 'ACTIVO';
                    $grupo_visita_inspeccion->usuario_creacion = Auth::id();
                    $grupo_visita_inspeccion->save();

                    $asunto_email = 'Asignación '. $persona['rol'] . ' visita de inspección ' .$validatedData['numero_informe'];
                    $datos_adicionales = ['numero_informe' => 'Ha sido asignado como la persona con el rol de '. $persona['rol'] . ' para la visita de inspección '. $validatedData['numero_informe'],
                                                'mensaje' => 'Usted ha sido la persona seleccionada con el rol de '. $persona['rol'] . ' para la visita de inspección identificada con el número ' . $validatedData['numero_informe']];

                    $this->enviar_correos($persona['usuario'], $asunto_email, $datos_adicionales);

                    if ($persona['rol'] === 'Lider de visita') {
                        $usuarios_lider_visita = User::where('id', $persona['usuario'])
                                            ->first();

                        $usuarios[] = ['id' => $usuarios_lider_visita->id, 'nombre' => $usuarios_lider_visita->name];
                    }
                } 

                $usuarios_coordinadores = User::where('profile', 'Coordinador')
                                            ->get();

                foreach ($usuarios_coordinadores as $key => $coordinador) {
                    $usuarios[] = ['id' => $coordinador->id, 'nombre' => $coordinador->name];
                }

                $usuariosSinDuplicados = collect($usuarios)->unique('id');

                $estado_etapa = 'VIGENTE';

                $visita_inspeccion->etapa = 'EN REVISIÓN DEL INFORME DIAGNÓSTICO';
                $visita_inspeccion->estado_etapa = $estado_etapa;
                $visita_inspeccion->usuario_actual = json_encode($usuariosSinDuplicados);
                $visita_inspeccion->fecha_inicio_gestion = date('Y-m-d');
                $visita_inspeccion->save();

                $this->historialInformes($validatedData['id'], 'ASIGNACIÓN GRUPO DE VISITA DE INSPECCIÓN', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $validatedData['observacion'], $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                $this->conteoDias($visita_inspeccion->id, 'EN REVISIÓN DEL INFORME DIAGNÓSTICO', date('Y-m-d'), NULL);
                $this->actualizarConteoDias($visita_inspeccion->id, $validatedData['etapa'], date('Y-m-d'));

                $successMessage = 'Grupo asignado correctamente';

                return response()->json(['message' => $successMessage]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }

    public function guardar_revision_diagnostico(Request $request){

        try {
        $proxima_etapa = '';
        $usuarios = [];

            $validatedData = $request->validate([
                'resultado_revision' => 'required',
                'id' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'estado_etapa' => 'required',
                'numero_informe' => 'required',
                'razon_social' => 'required',
                'nit' => 'required',
                'ciclo_devolucion_documento_diagnostico' => 'string',
                'observaciones_documento_diagnostico' => 'string|required_if:resultado_revision,NO',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                 return response()->json(['error' => $successMessage], 404);
            }else {

                $estado_etapa = '';

                if ($validatedData['resultado_revision'] === 'No') {
                    $proxima_etapa = 'EN REVISIÓN Y SUBSANACIÓN DEL DOCUMENTO DIAGNÓSTICO';

                    $asunto_email = 'Revisar informe diágnostico de la visita '.$validatedData['numero_informe'];
                    $datos_adicionales = ['numero_informe' => 'El informe diagnóstico de la visita '. $validatedData['numero_informe'] . ' requiere de su atención',
                                                'mensaje' => 'El informe diagnóstico de la visita '. $validatedData['numero_informe'] . ' a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                    $validatedData['nit'] . ' requiere ser socializada el día '. $validatedData['ciclo_devolucion_documento_diagnostico'] . ' con las siguientes observaciones: ' . $validatedData['observaciones_documento_diagnostico'] ];

                    $this->enviar_correos($visita_inspeccion->usuario_creacion, $asunto_email, $datos_adicionales);

                    $usuarios_intendente = User::where('id', $visita_inspeccion->usuario_creacion)
                                            ->first();

                    $usuarios = [['id' => $usuarios_intendente->id, 'nombre' => $usuarios_intendente->name]];

                    $this->historialInformes($validatedData['id'], 'SOLICITUD DE SOCIALIZACIÓN DE DOCUMENTO DIAGNÓSTICO', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), "Se agendo socialización del documento diagnóstico para el día: {$validatedData['ciclo_devolucion_documento_diagnostico']} con la siguiente observación: {$validatedData['observaciones_documento_diagnostico']}", $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                    $successMessage = 'Se envío la notificación para la verificación del informe diagnóstico';

                }elseif($validatedData['resultado_revision'] === 'Si'){
                    $proxima_etapa = 'ELABORACIÓN DE PLAN DE VISITA';

                    $asunto_email = 'Elaborar plan de visita '.$validatedData['numero_informe'];
                    $datos_adicionales = ['numero_informe' => 'Se requiere que realice el plan de visita para la vista de inspección número '. $validatedData['numero_informe'],
                                                'mensaje' => 'Se requiere que realice el plan de visita para la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                    $validatedData['nit']];

                    $usuario_lider_visita = GrupoVisitaInspeccion::where('id_informe', $validatedData['id'])
                                            ->where('rol', 'Lider de visita')
                                            ->where('estado', 'ACTIVO')
                                            ->first();

                    $usuario_lider_visita = User::where('id', $usuario_lider_visita->id_usuario)
                                            ->first();

                    $this->enviar_correos($usuario_lider_visita->id, $asunto_email, $datos_adicionales);   
                    
                    $usuarios = [['id' => $usuario_lider_visita->id, 'nombre' => $usuario_lider_visita->name]];

                    $this->historialInformes($validatedData['id'], 'APROBACIÓN INFORME DIAGNÓSTICO', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $validatedData['observaciones_documento_diagnostico'], $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                }

                $catidad_dias_etapa = Parametro::select('dias')
                        ->where('estado', $proxima_etapa)
                        ->first();

                if ($catidad_dias_etapa->dias > 0) {
                    $estado_etapa = 'VIGENTE';
                }else{
                    $estado_etapa = $validatedData['estado_etapa'];
                }

                $usuariosSinDuplicados = collect($usuarios)->unique('id');

                $visita_inspeccion->etapa = $proxima_etapa;
                $visita_inspeccion->estado_etapa = $estado_etapa;
                $visita_inspeccion->usuario_actual = json_encode($usuariosSinDuplicados);
                $visita_inspeccion->save();

                $this->conteoDias($visita_inspeccion->id, $proxima_etapa, date('Y-m-d'), NULL);
                $this->actualizarConteoDias($visita_inspeccion->id, $validatedData['etapa'], date('Y-m-d'));

                $successMessage = 'Diagnóstico enviado correctamente';

                return response()->json(['message' => $successMessage]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }

    public function finalizar_subasanar_diagnostico(Request $request){

        try {
        $proxima_etapa = '';
        $usuarios = [];

            $validatedData = $request->validate([
                'id' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'estado_etapa' => 'required',
                'numero_informe' => 'required',
                'razon_social' => 'required',
                'nit' => 'required',
                'ciclo_vida_diagnostico' => 'string|required_if:producto,GRABACIÓN|required_if:producto,AMBOS',
                'producto' => 'string|required',
                'anexo_subsanacion_diagnostico.*' => 'file|max:6000|mimes:pdf,doc,docx,xls,xlsx|required_if:producto,DOCUMENTO(S)|required_if:producto,AMBOS',
                'nombre_anexo_subsanacion_diagnostico.*' => 'string|required_if:producto,DOCUMENTO(S)|required_if:producto,AMBOS',
                'observaciones' => '',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                $banderaAnexos = false;

                if ($request->file('anexo_subsanacion_diagnostico')) {
                    $uploadedFiles = $request->file('anexo_subsanacion_diagnostico');
                    $fileNames = $request->input('nombre_anexo_subsanacion_diagnostico');
                    $banderaAnexos = true;
                }

                $accessToken = auth()->user()->google_token;
                $folderId = $visita_inspeccion->carpeta_drive;
                $anexos_adicionales_subasanacion_diagnostico = [];

                if ($banderaAnexos) {
                    foreach ($uploadedFiles as $index =>$newFile) {

                        $uniqueCode = Str::random(8);
                        $fecha = date('Ymd');

                        if($fileNames[$index]){
                            $nameFormat = str_replace(' ', '_', $fileNames[$index]);
                        }else{
                            $nameFormat = str_replace(' ', '_', $newFile->getClientOriginalName());
                        }
    
                        $newFileName = "{$fecha}_{$uniqueCode}_{$nameFormat}";

                        $metadata = [
                            'name' =>  $newFileName,
                            'parents' => [$folderId],
                        ];
                                
                        $responseAnexos = Http::withToken($accessToken)
                            ->attach(
                                'metadata',
                                json_encode($metadata),
                                'metadata.json'
                            )
                            ->attach(
                                'file',
                                file_get_contents($newFile),
                                $newFileName
                            )
                            ->post('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart');
        
                            if ($responseAnexos->successful()) {
                                $file = $responseAnexos->json();
                                $fileId = $file['id'];
                                $fileUrlAnexo = 'https://drive.google.com/file/d/' . $fileId . '/view';

                                $anexos_adicionales_subasanacion_diagnostico[] = ["fileName" => $fileNames[$index], "fileUrl" =>  $fileUrlAnexo];
                        
                            } else {
                                return response()->json(['error' => $responseAnexos->json()['error']['message']], 500);
                            }
                    }
                }

                $proxima_etapa = 'ELABORACIÓN DE PLAN DE VISITA';

                $catidad_dias_etapa = Parametro::select('dias')
                        ->where('estado', $proxima_etapa)
                        ->first();

                if ($catidad_dias_etapa->dias > 0) {
                    $estado_etapa = 'VIGENTE';
                }else{
                    $estado_etapa = $validatedData['estado_etapa'];
                }

                $asunto_email = 'Elaborar plan de visita '.$validatedData['numero_informe'];
                $datos_adicionales = ['numero_informe' => 'Se requiere que realice el plan de visita para la vista de inspección número '. $validatedData['numero_informe'],
                                                'mensaje' => 'Se requiere que realice el plan de visita para la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                $validatedData['nit']];

                $usuario_lider_visita = GrupoVisitaInspeccion::where('id_informe', $validatedData['id'])
                                            ->where('rol', 'Lider de visita')
                                            ->where('estado', 'ACTIVO')
                                            ->first();

                $usuario_lider_visita = User::where('id', $usuario_lider_visita->id_usuario)
                                            ->first();

                $this->enviar_correos($usuario_lider_visita->id, $asunto_email, $datos_adicionales);   
                    
                $usuarios = [['id' => $usuario_lider_visita->id, 'nombre' => $usuario_lider_visita->name]];

                $this->historialInformes($validatedData['id'], 'SUBSANACIÓN DE INFORME DIAGNÓSTICO', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $validatedData['observaciones'], $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                $usuariosSinDuplicados = collect($usuarios)->unique('id');

                $visita_inspeccion->etapa = $proxima_etapa;
                $visita_inspeccion->estado_etapa = $estado_etapa;
                $visita_inspeccion->enlace_subsanacion_diagnostico = $validatedData['ciclo_vida_diagnostico'];
                $visita_inspeccion->anexos_subsanacion_diagnostico = json_encode($anexos_adicionales_subasanacion_diagnostico) ;
                $visita_inspeccion->usuario_actual = json_encode($usuariosSinDuplicados);
                $visita_inspeccion->save();


                $this->conteoDias($visita_inspeccion->id, $proxima_etapa, date('Y-m-d'), NULL);
                $this->actualizarConteoDias($visita_inspeccion->id, $validatedData['etapa'], date('Y-m-d'));

                $successMessage = 'Diagnóstico subasando correctamente correctamente';

                return response()->json(['message' => $successMessage]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }

    public function guardar_plan_visita(Request $request) {
        try {

            $validatedData = $request->validate([
                'enlace_plan_visita' => 'required|file|max:6000|mimes:pdf,doc,docx,xls,xlsx',
                'anexo_plan_visita.*' => 'required|file|max:6000|mimes:pdf,doc,docx,xls,xlsx',
                'nombre_anexo_plan_visita.*' => 'string',
                'tipo_visita' => 'required',
                'id' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'estado_etapa' => 'required',
                'numero_informe' => 'required',
                'razon_social' => 'required',
                'nit' => 'required',
                'observacion' => '',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                $banderaAnexos = false;

                if ($request->file('anexo_plan_visita')) {
                    $uploadedFiles = $request->file('anexo_plan_visita');
                    $fileNames = $request->input('nombre_anexo_plan_visita');
                    $banderaAnexos = true;
                }

                $accessToken = auth()->user()->google_token;
                $folderId = $visita_inspeccion->carpeta_drive;
                $anexos_adicionales_plan_visita = [];

                $uniqueCode = Str::random(8);
                $fecha = date('Ymd');
                $nameFormat = str_replace(' ', '_', $validatedData['enlace_plan_visita']->getClientOriginalName());

                $newFileName = "{$fecha}_{$uniqueCode}_{$nameFormat}";

                $filePath = $request->file('enlace_plan_visita')->getRealPath();
                $fileName = $newFileName;

                $metadata = [
                    'name' =>  $fileName,
                    'parents' => [$folderId],
                ];

                $response = Http::withToken($accessToken)
                        ->attach(
                            'data',
                            json_encode($metadata),
                            'metadata.json'
                        )
                        ->attach(
                            'file',
                            file_get_contents($filePath),
                            $fileName
                        )
                        ->post('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart');

                if ($response->successful()) {
                    $file = $response->json();
                    $fileId = $file['id'];
                    $fileUrl = 'https://drive.google.com/file/d/' . $fileId . '/view';
                } else {
                    return response()->json(['error' => $response->json()['error']['message']], 500);
                }

                
                if ($banderaAnexos) {
                    foreach ($uploadedFiles as $index =>$newFile) {

                        $uniqueCode = Str::random(8);
                        $fecha = date('Ymd');

                        if($fileNames[$index]){
                            $nameFormat = str_replace(' ', '_', $fileNames[$index]);
                        }else{
                            $nameFormat = str_replace(' ', '_', $newFile->getClientOriginalName());
                        }
    
                        $newFileName = "{$fecha}_{$uniqueCode}_{$nameFormat}";

                        $metadata = [
                            'name' =>  $newFileName,
                            'parents' => [$folderId],
                        ];
                                
                        $responseAnexos = Http::withToken($accessToken)
                            ->attach(
                                'metadata',
                                json_encode($metadata),
                                'metadata.json'
                            )
                            ->attach(
                                'file',
                                file_get_contents($newFile),
                                $newFileName
                            )
                            ->post('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart');
        
                            if ($responseAnexos->successful()) {
                                $file = $responseAnexos->json();
                                $fileId = $file['id'];
                                $fileUrlAnexo = 'https://drive.google.com/file/d/' . $fileId . '/view';

                                $anexos_adicionales_plan_visita[] = ["fileName" => $fileNames[$index], "fileUrl" =>  $fileUrlAnexo];
                        
                            } else {
                                return response()->json(['error' => $responseAnexos->json()['error']['message']], 500);
                            }
                    }
                }

                $proxima_etapa = 'CONFIRMAR PLAN DE VISITA COORDINACIÓN';

                $catidad_dias_etapa = Parametro::select('dias')
                        ->where('estado', $proxima_etapa)
                        ->first();

                if ($catidad_dias_etapa->dias > 0) {
                    $estado_etapa = 'VIGENTE';
                }else{
                    $estado_etapa = $validatedData['estado_etapa'];
                }

                $usuarios_coordinadores = User::where('profile', 'Coordinador')
                                            ->get();
                
                $usuarios = [];

                $asunto_email = 'Plan de visita '.$validatedData['numero_informe'];
                $datos_adicionales = ['numero_informe' => 'Se ha creado el plan de visita '. $validatedData['numero_informe'],
                                            'mensaje' => 'Se creo el plan de la visita para la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                $validatedData['nit'] . ' que se ejecutara de manera '. $validatedData['tipo_visita'] . ', el plan se encuentra en el enlace '. $validatedData['enlace_plan_visita']];

                foreach ($usuarios_coordinadores as $usuario) {
                    $usuarios[] = ['id' => $usuario->id, 'nombre' => $usuario->name];

                    $this->enviar_correos($usuario->id, $asunto_email, $datos_adicionales);
                }

                $usuariosSinDuplicados = collect($usuarios)->unique('id');

                $visita_inspeccion->etapa = $proxima_etapa;
                $visita_inspeccion->estado_etapa = $estado_etapa;
                $visita_inspeccion->plan_visita = $fileUrl;
                $visita_inspeccion->tipo_visita = $validatedData['tipo_visita'];
                $visita_inspeccion->usuario_actual = json_encode($usuariosSinDuplicados);
                $visita_inspeccion->anexos_adicionales_plan_visita = json_encode($anexos_adicionales_plan_visita);
                $visita_inspeccion->save();

                $this->historialInformes($validatedData['id'], 'CREACIÓN PLAN DE VISITA', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $validatedData['observacion'], $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                $this->conteoDias($visita_inspeccion->id, 'CONFIRMAR PLAN DE VISITA COORDINACIÓN', date('Y-m-d'), NULL);
                $this->actualizarConteoDias($visita_inspeccion->id, $validatedData['etapa'], date('Y-m-d'));

                $successMessage = 'Plan de visita enviado correctamente para la revisión de la coordinación';

                return response()->json(['message' => $successMessage]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }

    public function revisar_plan_visita(Request $request){

        try {
        $fecha_actual = now();
        $proxima_etapa = '';
        $usuarios = [];

            $validatedData = $request->validate([
                'revision_plan_visita' => 'required',
                'id' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'estado_etapa' => 'required',
                'numero_informe' => 'required',
                'razon_social' => 'required',
                'nit' => 'required',
                'observaciones_plan_visita' => 'string',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                if ($validatedData['revision_plan_visita'] === 'Si') {
                    $proxima_etapa = 'MODIFICACIÓN DE PLAN DE VISITA';

                    $asunto_email = 'Revisar plan de visita '.$validatedData['numero_informe'];
                    $datos_adicionales = ['numero_informe' => 'El plan de visita '. $validatedData['numero_informe'] . ' requiere de su atención',
                                                'mensaje' => 'El plan de visita '. $validatedData['numero_informe'] . ' a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                    $validatedData['nit'] . ' requiere de su atención con las siguientes observaciones '. $validatedData['observaciones_plan_visita'] ];

                    $usuario_lider_visita = GrupoVisitaInspeccion::where('id_informe', $validatedData['id'])
                                            ->where('rol', 'Lider de visita')
                                            ->where('estado', 'ACTIVO')
                                            ->first();

                    $usuario_lider_visita = User::where('id', $usuario_lider_visita->id_usuario)
                                            ->first();

                    $this->enviar_correos($usuario_lider_visita->id, $asunto_email, $datos_adicionales);   
                    
                    
                    $usuarios = [['id' => $usuario_lider_visita->id, 'nombre' => $usuario_lider_visita->name]];

                    $this->historialInformes($validatedData['id'], 'SOLICITUD MODIFICACIÓN PLAN DE VISITA', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $validatedData['observaciones_plan_visita'], $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                    $successMessage = 'Se envío la notificación para la modificación del plan de visita';

                }elseif($validatedData['revision_plan_visita'] === 'No'){
                    $proxima_etapa = 'CONFIRMACIÓN REQUERIMIENTO DE INFORMACIÓN PREVIA A LA VISITA';

                    $asunto_email = 'Confirmar requerimiento de información previa a visita '.$validatedData['numero_informe'];
                    $datos_adicionales = ['numero_informe' => 'Se requiere que confirme requerimiento de información previa a visita '. $validatedData['numero_informe'],
                                                'mensaje' => 'Se requiere confirme requerimiento de información previa a visita para la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                    $validatedData['nit']];

                    $usuario_lider_visita = GrupoVisitaInspeccion::where('id_informe', $validatedData['id'])
                                            ->where('rol', 'Lider de visita')
                                            ->where('estado', 'ACTIVO')
                                            ->first();

                    $usuario_lider_visita = User::where('id', $usuario_lider_visita->id_usuario)
                                            ->first();

                    $this->enviar_correos($usuario_lider_visita->id, $asunto_email, $datos_adicionales);   

                    $usuario_coordinador = User::where('profile', 'Coordinador')
                                            ->get();

                    foreach ($usuario_coordinador as $coordinador) {
                        $usuarios[] = ['id' => $coordinador->id, 'nombre' => $coordinador->name];
                        $this->enviar_correos($coordinador->id, $asunto_email, $datos_adicionales);
                    }

                    $usuarios[] = ['id' => $usuario_lider_visita->id, 'nombre' => $usuario_lider_visita->name];

                    $this->historialInformes($validatedData['id'], 'SOLICITUD DE CONFIRMACIÓN DE INFORMACIÓN ADICIONAL', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), '', $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                    $successMessage = 'Se envío la notificación para la confirmación del requerimiento de información previa a la visita';
                }

                $catidad_dias_etapa = Parametro::select('dias')
                        ->where('estado', $proxima_etapa)
                        ->first();

                if ($catidad_dias_etapa->dias > 0) {
                    $estado_etapa = 'VIGENTE';
                }else{
                    $estado_etapa = $validatedData['estado_etapa'];
                }

                $usuariosSinDuplicados = collect($usuarios)->unique('id');

                $visita_inspeccion->etapa = $proxima_etapa;
                $visita_inspeccion->estado_etapa = $estado_etapa;
                $visita_inspeccion->usuario_actual = json_encode($usuariosSinDuplicados);
                $visita_inspeccion->save();


                $this->conteoDias($visita_inspeccion->id, $proxima_etapa, date('Y-m-d'), NULL);
                $this->actualizarConteoDias($visita_inspeccion->id, $validatedData['etapa'], date('Y-m-d'));

                return response()->json(['message' => $successMessage]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }

    public function confirmacion_informacion_previa_visita(Request $request){

        try {
        $proxima_etapa = '';
        $usuarios = [];

            $validatedData = $request->validate([
                'informacion_previa_visita' => 'required',
                'id' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'estado_etapa' => 'required',
                'numero_informe' => 'required',
                'razon_social' => 'required',
                'nit' => 'required',
                'observacion' => 'sometimes|string',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
             }else {
                if ($validatedData['informacion_previa_visita'] === 'Si') {
                    $proxima_etapa = 'EN REQUERIMIENTO DE INFORMACIÓN Y ELABORACIÓN DE CARTAS DE PRESENTACIÓN';

                    $asunto_email = 'Realizar requerimiento de información visita '.$validatedData['numero_informe'];
                    $datos_adicionales = ['numero_informe' => 'Realizar requerimiento de información visita '. $validatedData['numero_informe'],
                                                'mensaje' => 'Debe realizar el requerimiento de información previa a la visita '. $validatedData['numero_informe'] . ' a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                    $validatedData['nit'] . ' y la elaboración de las cartas de presentación.' ];

                    $usuario_lider_visita = GrupoVisitaInspeccion::where('id_informe', $validatedData['id'])
                                            ->where('rol', 'Lider de visita')
                                            ->where('estado', 'ACTIVO')
                                            ->first();

                    $usuario_lider_visita = User::where('id', $usuario_lider_visita->id_usuario)
                                            ->first();

                    $this->enviar_correos($usuario_lider_visita->id, $asunto_email, $datos_adicionales);   
                    
                    $usuarios = [['id' => $usuario_lider_visita->id, 'nombre' => $usuario_lider_visita->name]];

                    $this->historialInformes($validatedData['id'], 'CONFIRMACIÓN DE REQUERIMIENTO DE INFORMACIÓN PREVIA A LA VISITA', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $validatedData['observacion'], $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                    $usuarios_coordinadores = User::where('profile', 'Coordinador')
                                                ->get();

                    foreach ($usuarios_coordinadores as $usuario) {
                        $usuarios[] = ['id' => $usuario->id, 'nombre' => $usuario->name];

                        $this->enviar_correos($usuario->id, $asunto_email, $datos_adicionales);
                    }

                    $successMessage = 'Se envío la notificación al lider de la visita y coordinación para requerir información';

                }elseif($validatedData['informacion_previa_visita'] === 'No'){
                    $proxima_etapa = 'ELABORACIÓN DE CARTAS DE PRESENTACIÓN';

                    $asunto_email = 'Elaborar cartas de presentación para la visita '.$validatedData['numero_informe'];
                    $datos_adicionales = ['numero_informe' => 'Se requiere que realice las cartas de presentación para la visita '. $validatedData['numero_informe'],
                                                'mensaje' => 'Se requiere que realice las cartas de presentación para la visita '. $validatedData['numero_informe'].' para la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                    $validatedData['nit']];

                    $usuario_lider_visita = GrupoVisitaInspeccion::where('id_informe', $validatedData['id'])
                                            ->where('rol', 'Lider de visita')
                                            ->where('estado', 'ACTIVO')
                                            ->first();

                    $usuario_lider_visita = User::where('id', $usuario_lider_visita->id_usuario)
                                            ->first();

                    $this->enviar_correos($usuario_lider_visita->id, $asunto_email, $datos_adicionales);   
                    $this->enviar_correos($visita_inspeccion->usuario_creacion, $asunto_email, $datos_adicionales);

                    $usuarios_intendente = User::where('id', $visita_inspeccion->usuario_creacion)
                                            ->first();

                    $usuarios = [['id' => $usuario_lider_visita->id, 'nombre' => $usuario_lider_visita->name], ['id' => $usuarios_intendente->id, 'nombre' => $usuarios_intendente->name]];

                    $this->historialInformes($validatedData['id'], 'NEGACIÓN DE REQUERIMIENTO DE INFORMACIÓN PREVIA A LA VISITA', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $validatedData['observacion'], $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                    $successMessage = 'Se envío la notificación para la creación de cartas de presentación previa a la visita';

                    /*$proxima_etapa = 'EN APERTURA DE VISITA DE INSPECCIÓN';

                    $asunto_email = 'Realizar apertura de la visita de inspección '.$validatedData['numero_informe'];
                    $datos_adicionales = ['numero_informe' => 'Realizar apertura de la visita de inspección '. $validatedData['numero_informe'],
                                                'mensaje' => 'Se requiere que realice la apertura de la visita de inspección '. $validatedData['numero_informe'] . ' a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                    $validatedData['nit'] ];

                    $usuario_lider_visita = GrupoVisitaInspeccion::where('id_informe', $validatedData['id'])
                                            ->where('rol', 'Lider de visita')
                                            ->where('estado', 'ACTIVO')
                                            ->first();

                    $usuario_lider_visita = User::where('id', $usuario_lider_visita->id_usuario)
                                            ->first();

                    $this->enviar_correos($usuario_lider_visita->id, $asunto_email, $datos_adicionales);   
                    
                    $usuarios = [['id' => $usuario_lider_visita->id, 'nombre' => $usuario_lider_visita->name]];

                    $this->historialInformes($validatedData['id'], 'NEGACIÓN DE REQUERIMIENTO DE INFORMACIÓN PREVIA A LA VISITA', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), '', $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                    $successMessage = 'Se envió la soliditud de apertura de la visita correctamente';*/
                }

                $catidad_dias_etapa = Parametro::select('dias')
                        ->where('estado', $proxima_etapa)
                        ->first();

                if ($catidad_dias_etapa->dias > 0) {
                    $estado_etapa = 'VIGENTE';
                }else{
                    $estado_etapa = $validatedData['estado_etapa'];
                }

                $usuariosSinDuplicados = collect($usuarios)->unique('id');

                $visita_inspeccion->etapa = $proxima_etapa;
                $visita_inspeccion->estado_etapa = $estado_etapa;
                $visita_inspeccion->usuario_actual = json_encode($usuariosSinDuplicados);
                $visita_inspeccion->save();


                $this->conteoDias($visita_inspeccion->id, $proxima_etapa, date('Y-m-d'), NULL);
                $this->actualizarConteoDias($visita_inspeccion->id, $validatedData['etapa'], date('Y-m-d'));

                return response()->json(['message' => $successMessage]);
             }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }

    public function finalizar_requerimiento_informacion(Request $request){

        try {
        $proxima_etapa = '';

            $validatedData = $request->validate([
                'ciclo_vida_requerimiento_informacion_adicional' => 'required',
                'id' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'estado_etapa' => 'required',
                'numero_informe' => 'required',
                'razon_social' => 'required',
                'nit' => 'required',
                'observaciones' => 'sometimes|string',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                $proxima_etapa = 'EN ESPERA DE INFORMACIÓN ADICIONAL POR PARTE DE LA ENTIDAD SOLIDARIA';

                $catidad_dias_etapa = Parametro::select('dias')
                        ->where('estado', $proxima_etapa)
                        ->first();

                if ($catidad_dias_etapa->dias > 0) {
                    $estado_etapa = 'VIGENTE';
                }else{
                    $estado_etapa = $validatedData['estado_etapa'];
                }

                $asunto_email = 'Espera de información adicional por parte de la entidad solidaria '.$validatedData['numero_informe'];
                $datos_adicionales = ['numero_informe' => 'Espera de información adicional por parte de la entidad solidaria - '. $validatedData['numero_informe'],
                                                'mensaje' => 'Se registró el requeimiento de información adicional '. $validatedData['numero_informe'] . ' a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                                        $validatedData['nit'] . ' con el ciclo de vida '. $validatedData['ciclo_vida_requerimiento_informacion_adicional'] ];

                $usuario_lider_visita = GrupoVisitaInspeccion::where('id_informe', $validatedData['id'])
                                            ->where('rol', 'Lider de visita')
                                            ->where('estado', 'ACTIVO')
                                            ->first();

                $usuario_lider_visita = User::where('id', $usuario_lider_visita->id_usuario)
                                            ->first();

                $this->enviar_correos($usuario_lider_visita->id, $asunto_email, $datos_adicionales);   

                $this->historialInformes($validatedData['id'], 'ENVÍO DE REQUERIMIENTO DE INFORMACIÓN ADICIONAL', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $validatedData['observaciones'], $validatedData['estado_etapa'], $validatedData['ciclo_vida_requerimiento_informacion_adicional'], NULL, NULL, '', NULL);

                $successMessage = 'Se registro el requerimiento de información adicional de la visita de inspección';


                $visita_inspeccion->etapa = $proxima_etapa;
                $visita_inspeccion->estado_etapa = $estado_etapa;
                $visita_inspeccion->save();


                $this->conteoDias($visita_inspeccion->id, $proxima_etapa, date('Y-m-d'), NULL);
                $this->actualizarConteoDias($visita_inspeccion->id, $validatedData['etapa'], date('Y-m-d'));

                return response()->json(['message' => $successMessage]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }

    public function registro_respuesta_informacion_adicional(Request $request){

        try {
        $proxima_etapa = '';
        $usuarios = [];

            $validatedData = $request->validate([
                'id' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'estado_etapa' => 'required',
                'numero_informe' => 'required',
                'razon_social' => 'required',
                'nit' => 'required',
                'confirmacion_informacion_entidad' => 'required',
                'radicado_respuesta_entidad' => 'required_if:confirmacion_informacion_entidad,Si',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                $proxima_etapa = 'VALORACIÓN DE LA INFORMACIÓN RECIBIDA';

                $catidad_dias_etapa = Parametro::select('dias')
                        ->where('estado', $proxima_etapa)
                        ->first();

                if ($catidad_dias_etapa->dias > 0) {
                    $estado_etapa = 'VIGENTE';
                }else{
                    $estado_etapa = $validatedData['estado_etapa'];
                }

                if ($validatedData['confirmacion_informacion_entidad'] === 'Si') {
                    $asunto_email = 'Valoración de información de la visita '.$validatedData['numero_informe'];
                    $datos_adicionales = ['numero_informe' => 'Realizar la valoración de la información de la visita '. $validatedData['numero_informe'],
                                                'mensaje' => 'Debe realizar la valoración de información de la visita '. $validatedData['numero_informe'] . ' a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                                        $validatedData['nit'] ];

                    $this->historialInformes($validatedData['id'], 'REGISTRO DE INFORMACIÓN ADICIONAL POR PARTE DE LA ENTIDAD SOLIDARIA', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), '', $validatedData['estado_etapa'], $validatedData['radicado_respuesta_entidad'], NULL, NULL, '', NULL);

                    $successMessage = 'Se registro la respuesta emitida por parte de la entidad solidaria';
                    
                }elseif ($validatedData['confirmacion_informacion_entidad'] === 'No') {
                    $asunto_email = 'La entidad solidaria no respondio al requerimiento de información adicional '.$validatedData['numero_informe'];
                    $datos_adicionales = ['numero_informe' => 'La entidad solidaria de la visita '. $validatedData['numero_informe']. ' no respondio al requerimiento de información adicional',
                                                'mensaje' => 'La entidad solidaria '. $validatedData['razon_social'] . ' identificada con el nit '.$validatedData['nit']. ' de la visita '. $validatedData['numero_informe'] . ' no dio respuesta al requerimiento de información adicional,
                                                por favor ingresar a la plataforma y confirmar si se realizará la visita de inspección.'];
                    
                    $this->historialInformes($validatedData['id'], 'REGISTRO DE NO RESPUESTA POR PARTE DE LA ENTIDAD SOLIDARIA', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), '', $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                    $successMessage = 'Se registro que la entidad solidaria no emitio resuesta';
                }

                

                $usuario_lider_visita = GrupoVisitaInspeccion::where('id_informe', $validatedData['id'])
                                            ->where('rol', 'Lider de visita')
                                            ->where('estado', 'ACTIVO')
                                            ->first();

                $usuario_lider_visita = User::where('id', $usuario_lider_visita->id_usuario)
                                            ->first();

                $this->enviar_correos($usuario_lider_visita->id, $asunto_email, $datos_adicionales);   
                    
                $usuarios = [['id' => $usuario_lider_visita->id, 'nombre' => $usuario_lider_visita->name]];

                $usuariosSinDuplicados = collect($usuarios)->unique('id');

                $visita_inspeccion->etapa = $proxima_etapa;
                $visita_inspeccion->estado_etapa = $estado_etapa;
                $visita_inspeccion->usuario_actual = json_encode($usuariosSinDuplicados);
                $visita_inspeccion->save();


                $this->conteoDias($visita_inspeccion->id, $proxima_etapa, date('Y-m-d'), NULL);
                $this->actualizarConteoDias($visita_inspeccion->id, $validatedData['etapa'], date('Y-m-d'));

                return response()->json(['message' => $successMessage]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }
    
    public function valoracion_informacion_recibida(Request $request){

        try {
        $proxima_etapa = '';
        $usuarios = [];

            $validatedData = $request->validate([
                'necesidad_visita' => 'required',
                'id' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'estado_etapa' => 'required',
                'numero_informe' => 'required',
                'razon_social' => 'required',
                'nit' => 'required',
                'ciclo_vida_plan_visita_ajustado' => '',
                'observaciones_valoracion' => '',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                $proxima_etapa = 'CONFIRMACIÓN DE VISITA DE INSPECCIÓN';

                $catidad_dias_etapa = Parametro::select('dias')
                        ->where('estado', $proxima_etapa)
                        ->first();

                if ($catidad_dias_etapa->dias > 0) {
                    $estado_etapa = 'VIGENTE';
                }else{
                    $estado_etapa = $validatedData['estado_etapa'];
                }

                if ($validatedData['necesidad_visita'] === 'Si') {

                    $asunto_email = 'Confirmar visita de inspección '.$validatedData['numero_informe'];
                    $datos_adicionales = ['numero_informe' => 'Confirmar visita de inspección '. $validatedData['numero_informe'],
                                                'mensaje' => 'Se ha realizado la valoración de la información por parte del lider de la visita, para la visita de inspección '. 
                                                $validatedData['numero_informe'] . ' a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                                                $validatedData['nit'] . ' para la cual se actualizó el plan de visita que se puede consultar en eSigna con el ciclo de vida '.$validatedData['ciclo_vida_plan_visita_ajustado']. 
                                                '. Se requiere que ingrese a la plataforma y confirme si se realizará la visita de inspección'];

                    $this->historialInformes($validatedData['id'], 'CONFIRMACIÓN DE INFORMACIÓN PREVIA A LA VISITA', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), '', $validatedData['estado_etapa'], $validatedData['ciclo_vida_plan_visita_ajustado'], NULL, NULL, '', NULL);

                }elseif($validatedData['necesidad_visita'] === 'No'){
                    $asunto_email = 'Confirmar visita de inspección '.$validatedData['numero_informe'];
                    $datos_adicionales = ['numero_informe' => 'Confirmar visita de inspección '. $validatedData['numero_informe'],
                                                'mensaje' => 'Se ha realizado la valoración de la información por parte del lider de la visita, para la visita de inspección '. 
                                                $validatedData['numero_informe'] . ' a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                                                $validatedData['nit'] . ' para la cual se identifico que no se requiere la visita de inspección con la siguiente obervación '.$validatedData['observaciones_valoracion']. 
                                                ' Se requiere que ingrese a la plataforma y confirme si se realizará la visita de inspección'];

                    $this->historialInformes($validatedData['id'], 'CONFIRMACIÓN DE INFORMACIÓN PREVIA A LA VISITA', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $validatedData['observaciones_valoracion'], $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);
                }

                $usuarios_coordinadores = User::where('profile', 'Coordinador')
                                                ->get();

                foreach ($usuarios_coordinadores as $usuario) {
                    $usuarios[] = ['id' => $usuario->id, 'nombre' => $usuario->name];

                    $this->enviar_correos($usuario->id, $asunto_email, $datos_adicionales);
                }

                $successMessage = 'Se envío la notificación para la confirmación de la visita';

                $usuariosSinDuplicados = collect($usuarios)->unique('id');

                $visita_inspeccion->etapa = $proxima_etapa;
                $visita_inspeccion->estado_etapa = $estado_etapa;
                $visita_inspeccion->usuario_actual = json_encode($usuariosSinDuplicados);
                $visita_inspeccion->save();


                $this->conteoDias($visita_inspeccion->id, $proxima_etapa, date('Y-m-d'), NULL);
                $this->actualizarConteoDias($visita_inspeccion->id, $validatedData['etapa'], date('Y-m-d'));

                return response()->json(['message' => $successMessage]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }

    public function confirmacion_visita(Request $request){

        try {
        $proxima_etapa = '';
        $usuarios = [];

            $validatedData = $request->validate([
                'confirmacion_necesidad_visita' => 'required',
                'id' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'estado_etapa' => 'required',
                'numero_informe' => 'required',
                'razon_social' => 'required',
                'nit' => 'required',
                'ciclo_vida_confirmacion_visita' => 'required',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                if ($validatedData['confirmacion_necesidad_visita'] === 'No') {
                    $proxima_etapa = 'EN VERIFICACIÓN DE LOS CONTENIDOS FINALES DEL EXPEDIENTE';

                    $asunto_email = 'Verificar los contenidos finales del expediente de la visita '.$validatedData['numero_informe'];
                    $datos_adicionales = ['numero_informe' => 'Verificar los contenidos finales del expediente de la visita '. $validatedData['numero_informe'],
                                                'mensaje' => 'Debe los contenidos finales de la visita '. $validatedData['numero_informe'] . ' a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                    $validatedData['nit'] ];

                    $usuario_lider_visita = GrupoVisitaInspeccion::where('id_informe', $validatedData['id'])
                                            ->where('rol', 'Lider de visita')
                                            ->where('estado', 'ACTIVO')
                                            ->first();

                    $usuario_lider_visita = User::where('id', $usuario_lider_visita->id_usuario)
                                            ->first();

                    $this->enviar_correos($usuario_lider_visita->id, $asunto_email, $datos_adicionales);   
                    
                    $usuarios = [['id' => $usuario_lider_visita->id, 'nombre' => $usuario_lider_visita->name]];

                    $this->historialInformes($validatedData['id'], 'CONFIRMACIÓN DE QUE NO ES NECESARIA LA VISITA', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), '', $validatedData['estado_etapa'], $validatedData['ciclo_vida_confirmacion_visita'], NULL, NULL, '', NULL);

                    $successMessage = 'Se actualizó la visita correctamente';

                }elseif($validatedData['confirmacion_necesidad_visita'] === 'Si'){

                    $proxima_etapa = 'EN APERTURA DE VISITA DE INSPECCIÓN';

                    $asunto_email = 'Realizar apertura de la visita de inspección '.$validatedData['numero_informe'];
                    $datos_adicionales = ['numero_informe' => 'Realizar apertura de la visita de inspección '. $validatedData['numero_informe'],
                                                'mensaje' => 'Se requiere que realice la apertura de la visita de inspección '. $validatedData['numero_informe'] . ' a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                    $validatedData['nit'] ];

                    $usuario_lider_visita = GrupoVisitaInspeccion::where('id_informe', $validatedData['id'])
                                            ->where('rol', 'Lider de visita')
                                            ->where('estado', 'ACTIVO')
                                            ->first();

                    $usuario_lider_visita = User::where('id', $usuario_lider_visita->id_usuario)
                                            ->first();

                    $this->enviar_correos($usuario_lider_visita->id, $asunto_email, $datos_adicionales);   
                    
                    $usuarios = [['id' => $usuario_lider_visita->id, 'nombre' => $usuario_lider_visita->name]];

                    $this->historialInformes($validatedData['id'], 'CONFIRMACIÓN DE LA VISITA', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), '', $validatedData['estado_etapa'], $validatedData['ciclo_vida_confirmacion_visita'], NULL, NULL, '', NULL);

                    $successMessage = 'Se envió la soliditud de apertura de la visita correctamente';
                }

                $catidad_dias_etapa = Parametro::select('dias')
                        ->where('estado', $proxima_etapa)
                        ->first();

                if ($catidad_dias_etapa->dias > 0) {
                    $estado_etapa = 'VIGENTE';
                }else{
                    $estado_etapa = $validatedData['estado_etapa'];
                }

                $usuariosSinDuplicados = collect($usuarios)->unique('id');

                $visita_inspeccion->etapa = $proxima_etapa;
                $visita_inspeccion->estado_etapa = $estado_etapa;
                $visita_inspeccion->usuario_actual = json_encode($usuariosSinDuplicados);
                $visita_inspeccion->save();


                $this->conteoDias($visita_inspeccion->id, $proxima_etapa, date('Y-m-d'), NULL);
                $this->actualizarConteoDias($visita_inspeccion->id, $validatedData['etapa'], date('Y-m-d'));

                return response()->json(['message' => $successMessage]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }

    public function cartas_presentacion(Request $request){

        try {
        $proxima_etapa = '';
        $usuarios = [];

            $validatedData = $request->validate([
                'id' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'estado_etapa' => 'required',
                'numero_informe' => 'required',
                'razon_social' => 'required',
                'nit' => 'required',
                'ciclo_vida_cartas_presentacion' => 'required',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                $proxima_etapa = 'EN APERTURA DE VISITA DE INSPECCIÓN';

                $catidad_dias_etapa = Parametro::select('dias')
                        ->where('estado', $proxima_etapa)
                        ->first();

                if ($catidad_dias_etapa->dias > 0) {
                    $estado_etapa = 'VIGENTE';
                }else{
                    $estado_etapa = $validatedData['estado_etapa'];
                }

                $asunto_email = 'Realizar apertura de la visita de inspección '.$validatedData['numero_informe'];
                $datos_adicionales = ['numero_informe' => 'Realizar apertura de la visita de inspección '. $validatedData['numero_informe'],
                                            'mensaje' => 'Se requiere que realice la apertura de la visita de inspección '. $validatedData['numero_informe'] . ' a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                $validatedData['nit'] ];

                $usuario_lider_visita = GrupoVisitaInspeccion::where('id_informe', $validatedData['id'])
                                        ->where('rol', 'Lider de visita')
                                        ->where('estado', 'ACTIVO')
                                        ->first();

                $usuario_lider_visita = User::where('id', $usuario_lider_visita->id_usuario)
                                        ->first();

                $this->enviar_correos($usuario_lider_visita->id, $asunto_email, $datos_adicionales);   
                
                $usuarios = [['id' => $usuario_lider_visita->id, 'nombre' => $usuario_lider_visita->name]];

                $this->historialInformes($validatedData['id'], 'CONFIRMACIÓN DE QUE NO ES NECESARIA LA VISITA', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), '', $validatedData['estado_etapa'], $validatedData['ciclo_vida_cartas_presentacion'], NULL, NULL, '', NULL);

                $successMessage = 'Se envió la soliditud de apertura de la visita correctamente';

                $usuariosSinDuplicados = collect($usuarios)->unique('id');

                $visita_inspeccion->etapa = $proxima_etapa;
                $visita_inspeccion->estado_etapa = $estado_etapa;
                $visita_inspeccion->usuario_actual = json_encode($usuariosSinDuplicados);
                $visita_inspeccion->save();


                $this->conteoDias($visita_inspeccion->id, $proxima_etapa, date('Y-m-d'), NULL);
                $this->actualizarConteoDias($visita_inspeccion->id, $validatedData['etapa'], date('Y-m-d'));

                return response()->json(['message' => $successMessage]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }

    public function abrir_visita_inspeccion(Request $request) {
        try {
            $validatedData = $request->validate([
                'grupo_inspeccion' => 'required',
                'id' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'estado_etapa' => 'required',
                'numero_informe' => 'required',
                'razon_social' => 'required',
                'nit' => 'required',
                'apertura_visita' => 'required',
                'carta_salvaguarda' => 'required',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                        ->with('etapaProceso')
                                                        ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                $grupo_visita_inspeccion = json_decode($validatedData['grupo_inspeccion'], true);

                $usuarios = [];

                foreach ($grupo_visita_inspeccion as $index => $persona) {

                    if ($persona['rol'] === 'Redactor') {
                        $redactor_actual = GrupoVisitaInspeccion::where('id_informe', $validatedData['id'])
                                                                ->where('rol', $persona['rol'])
                                                                ->where('estado', 'ACTIVO')
                                                                ->first();

                        $redactor_actual->id_usuario = $persona['usuario'];                   
                        $redactor_actual->save();                   
                    }else{
                        $existingRecord = GrupoVisitaInspeccion::where('id_informe', $validatedData['id'])
                                                            ->where('id_usuario', $persona['usuario'])
                                                            ->where('rol', $persona['rol'])
                                                            ->where('estado', 'ACTIVO')
                                                            ->first();

                        if (!$existingRecord) {
                            $grupo_visita_inspeccion = new GrupoVisitaInspeccion();
                            $grupo_visita_inspeccion->id_informe = $validatedData['id'];
                            $grupo_visita_inspeccion->id_usuario = $persona['usuario'];
                            $grupo_visita_inspeccion->rol = $persona['rol'];
                            $grupo_visita_inspeccion->estado = 'ACTIVO';
                            $grupo_visita_inspeccion->usuario_creacion = Auth::id();
                            $grupo_visita_inspeccion->save();
                        }

                        $asunto_email = 'Asignación '. $persona['rol'] . ' visita de inspección ' .$validatedData['numero_informe'];
                        $datos_adicionales = ['numero_informe' => 'Ha sido asignado como la persona con el rol de '. $persona['rol'] . ' para la visita de inspección '. $validatedData['numero_informe'],
                                                    'mensaje' => 'Usted ha sido la persona seleccionada con el rol de '. $persona['rol'] . ' para la visita de inspección identificada con el número ' . $validatedData['numero_informe']];

                        $this->enviar_correos($persona['usuario'], $asunto_email, $datos_adicionales);
                    }

                }

                GrupoVisitaInspeccion::where('id_informe', $validatedData['id'])
                                        ->whereNotIn('id_usuario', collect($grupo_visita_inspeccion)->pluck('usuario'))
                                        ->where('estado', 'ACTIVO')
                                        ->where('rol', '!=', 'Lider de visita')
                                        ->update(['estado' => 'INACTIVO']);
                
                $lider_visita = GrupoVisitaInspeccion::where('id_informe', $validatedData['id'])
                                        ->where('rol', 'Lider de visita')
                                        ->where('estado', 'ACTIVO')
                                        ->first();

                $usuarios_lider_visita = User::where('id', $lider_visita->id_usuario)
                                    ->first();

                $usuarios[] = ['id' => $usuarios_lider_visita->id, 'nombre' => $usuarios_lider_visita->name];

                $proxima_etapa = 'PENDIENTE INICIO DE VISITA DE INSPECCIÓN';

                $catidad_dias_etapa = Parametro::select('dias')
                        ->where('estado', $proxima_etapa)
                        ->first();

                if ($catidad_dias_etapa->dias > 0) {
                    $estado_etapa = 'VIGENTE';
                }else{
                    $estado_etapa = $validatedData['estado_etapa'];
                }

                $usuariosSinDuplicados = collect($usuarios)->unique('id');

                $visita_inspeccion->etapa = $proxima_etapa;
                $visita_inspeccion->estado_etapa = $estado_etapa;
                $visita_inspeccion->enlace_apertura = $validatedData['apertura_visita'];
                $visita_inspeccion->carta_salvaguarda = $validatedData['carta_salvaguarda'];
                $visita_inspeccion->usuario_actual = json_encode($usuariosSinDuplicados);
                $visita_inspeccion->save();

                $this->historialInformes($validatedData['id'], 'APERTURA DE LA VISITA DE INSPECCIÓN', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), '', $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                $this->conteoDias($visita_inspeccion->id, $proxima_etapa, date('Y-m-d'), NULL);
                $this->actualizarConteoDias($visita_inspeccion->id, $validatedData['etapa'], date('Y-m-d'));

                $successMessage = 'Apertura realizada correctamente';

                return response()->json(['message' => $successMessage]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }

    public function iniciar_visita_inspeccion(Request $request){

        try {
        $proxima_etapa = '';
        $usuarios = [];

            $validatedData = $request->validate([
                'id' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'estado_etapa' => 'required',
                'numero_informe' => 'required',
                'razon_social' => 'required',
                'nit' => 'required',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();
            
            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
                }else {

                $proxima_etapa = 'EN DESARROLLO DE VISITA DE INSPECCIÓN';

                $catidad_dias_etapa = Parametro::select('dias')
                        ->where('estado', $proxima_etapa)
                        ->first();

                if ($catidad_dias_etapa->dias > 0) {
                    $estado_etapa = 'VIGENTE';
                }else{
                    $estado_etapa = $validatedData['estado_etapa'];
                }

                $asunto_email = 'Se acaba de dar inicio a la visita de inspección '.$validatedData['numero_informe'];
                $datos_adicionales = ['numero_informe' => 'Se acaba de dar inicio a la visita de inspección '. $validatedData['numero_informe'],
                                            'mensaje' => 'Se acaba de dar inicio a la visita de inspección '. $validatedData['numero_informe'] . ' a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                $validatedData['nit'] ];

                $this->enviar_correos($visita_inspeccion->usuario_creacion, $asunto_email, $datos_adicionales);
                
                $usuarios_coordinadores = User::where('profile', 'Coordinador')
                                                ->get();

                foreach ($usuarios_coordinadores as $usuario_coordinador) {
                    $this->enviar_correos($usuario_coordinador->id, $asunto_email, $datos_adicionales);
                }

                $usuarios_delegados = User::where('profile', 'Delegado')
                                                ->get();

                foreach ($usuarios_delegados as $usuario_delegado) {
                    $this->enviar_correos($usuario_delegado->id, $asunto_email, $datos_adicionales);
                }

                $grupo_inspeccion = GrupoVisitaInspeccion::where('id_informe', $validatedData['id'])
                                ->whereIn('rol', ['Lider de visita', 'Inspector'])
                                ->where('estado', 'ACTIVO')
                                ->get();

                foreach ($grupo_inspeccion as $persona) {
                    $usuario_grupo= User::where('id', $persona->id_usuario)
                                                ->first();
                    $usuarios[] = ['id' => $usuario_grupo->id, 'nombre' => $usuario_grupo->name];
                }
                
                $usuariosSinDuplicados = collect($usuarios)->unique('id');

                $this->historialInformes($validatedData['id'], 'INICIO DE LA VISITA DE INSPECCIÓN', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), '', $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                $successMessage = 'Se dio inicio a la visita de inspección, se notifico a la intendencia, delegatura y coordinación';

                $visita_inspeccion->etapa = $proxima_etapa;
                $visita_inspeccion->fecha_inicio_visita = date('Y-m-d');
                $visita_inspeccion->estado_etapa = $estado_etapa;
                $visita_inspeccion->usuario_actual = json_encode($usuariosSinDuplicados);
                $visita_inspeccion->save();


                $this->conteoDias($visita_inspeccion->id, $proxima_etapa, date('Y-m-d'), NULL);
                $this->actualizarConteoDias($visita_inspeccion->id, $validatedData['etapa'], date('Y-m-d'));

                return response()->json(['message' => $successMessage]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }

    public function cerrar_visita_inspeccion(Request $request){

        try {
        $proxima_etapa = '';
        $usuarios = [];

            $validatedData = $request->validate([
                'id' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'estado_etapa' => 'required',
                'numero_informe' => 'required',
                'razon_social' => 'required',
                'nit' => 'required',
                'cierre_visita' => 'required',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                $proxima_etapa = 'EN REGISTRO DE HALLAZGOS DE LA VISITA DE INSPECCIÓN';

                $catidad_dias_etapa = Parametro::select('dias')
                        ->where('estado', $proxima_etapa)
                        ->first();

                if ($catidad_dias_etapa->dias > 0) {
                    $estado_etapa = 'VIGENTE';
                }else{
                    $estado_etapa = $validatedData['estado_etapa'];
                }

                $asunto_email = 'Se ha dado cierre a la visita de inspección '.$validatedData['numero_informe'];
                $datos_adicionales = ['numero_informe' => 'Se ha dado cierre a la visita de inspección '. $validatedData['numero_informe'],
                                            'mensaje' => 'Se ha dado cierre a la visita de inspección '. $validatedData['numero_informe'] . ' a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                $validatedData['nit'] ];

                $usuarios_delegados = User::where('profile', 'Delegado')
                                                ->get();

                foreach ($usuarios_delegados as $usuario_delegado) {
                    $this->enviar_correos($usuario_delegado->id, $asunto_email, $datos_adicionales);
                }

                $this->enviar_correos($visita_inspeccion->usuario_creacion, $asunto_email, $datos_adicionales);
                
                $usuarios_coordinadores = User::where('profile', 'Coordinador')
                                                ->get();

                foreach ($usuarios_coordinadores as $usuario_coordinador) {
                    $this->enviar_correos($usuario_coordinador->id, $asunto_email, $datos_adicionales);
                }

                $grupo_inspeccion = GrupoVisitaInspeccion::where('id_informe', $validatedData['id'])
                                ->where('rol', 'Inspector')
                                ->where('estado', 'ACTIVO')
                                ->get();

                foreach ($grupo_inspeccion as $persona) {
                    $usuario_grupo= User::where('id', $persona->id_usuario)
                                                ->first();
                    $usuarios[] = ['id' => $usuario_grupo->id, 'nombre' => $usuario_grupo->name];
                }
                
                $usuariosSinDuplicados = collect($usuarios)->unique('id');
                
                $this->historialInformes($validatedData['id'], 'FINALIZACIÓN DE LA VISITA DE INSPECCIÓN', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), '', $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                $successMessage = 'Se finalizó la visita de inspección, se notifico a la delegatura, intendencia y coordinación';

                $visita_inspeccion->etapa = $proxima_etapa;
                $visita_inspeccion->fecha_fin_visita = date('Y-m-d');
                $visita_inspeccion->estado_etapa = $estado_etapa;
                $visita_inspeccion->documentos_cierre_visita = $validatedData['cierre_visita'];
                $visita_inspeccion->usuario_actual = json_encode($usuariosSinDuplicados);
                $visita_inspeccion->save();

                $this->conteoDias($visita_inspeccion->id, $proxima_etapa, date('Y-m-d'), NULL);
                $this->actualizarConteoDias($visita_inspeccion->id, $validatedData['etapa'], date('Y-m-d'));

                return response()->json(['message' => $successMessage]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }

    public function registrar_hallazgos(Request $request){

        try {
            $proxima_etapa = '';
            $usuarios = [];

            $validatedData = $request->validate([
                'id' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'estado_etapa' => 'required',
                'numero_informe' => 'required',
                'razon_social' => 'required',
                'nit' => 'required',
                'registro_hallazgos' => 'required',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                $usuario_grupo_inspeccion = GrupoVisitaInspeccion::where('id_informe', $validatedData['id'])
                                        ->where('estado', 'ACTIVO')
                                        ->where('id_usuario', Auth::id())
                                        ->where('rol', '!=', 'Lider de visita')
                                        ->where('rol', '!=', 'Redactor')
                                        ->get();

                $usuario_lider_visita = GrupoVisitaInspeccion::where('id_informe', $validatedData['id'])
                                        ->where('estado', 'ACTIVO')
                                        ->where('rol', 'Lider de visita')
                                        ->first();

                foreach($usuario_grupo_inspeccion as $persona ){
                    
                    $persona->enlace_hallazgos = $validatedData['registro_hallazgos'];
                    $persona->save();
                    
                    $usuario = User::find($persona->id_usuario);

                    $asunto_email = 'Se han cargado hallazgos de la visita de inspección '.$validatedData['numero_informe'];
                    $datos_adicionales = ['numero_informe' => 'Se han cargado hallazgos de la visita de inspección '. $validatedData['numero_informe'],
                                                'mensaje' => 'Se han cargado hallazgos de la visita de inspección '. $validatedData['numero_informe'] . ' a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                    $validatedData['nit'] . ' por el usuario ' . $usuario->name . ' en el enlace ' . $validatedData['registro_hallazgos'] ];

                    $this->enviar_correos($usuario_lider_visita->id_usuario, $asunto_email, $datos_adicionales); 
                }

                $numero_registros = GrupoVisitaInspeccion::where('id_informe', $validatedData['id'])
                                        ->where('estado', 'ACTIVO')
                                        ->whereNotIn('rol', ['Lider de visita', 'Redactor'])
                                        ->whereNull('enlace_hallazgos')
                                        ->count();

                if ($numero_registros >= 1) {
                    $proxima_etapa = 'EN REGISTRO DE HALLAZGOS DE LA VISITA DE INSPECCIÓN';
                    $successMessage = 'Se registraron los hallazgos de la visita de inspección, aun faltan '. $numero_registros . ' usuarios por cargar hallazgos.';
                }else{
                    $proxima_etapa = 'EN CONSOLIDACIÓN DE HALLAZGOS';
                    $successMessage = 'Se registraron los hallazgos de la visita de inspección, se envia notificación al lider de la visita de inspección para la consolidación de hallazgos.';

                    $asunto_email = 'Consolidar hallazgos de la visita de inspección '.$validatedData['numero_informe'];
                    $datos_adicionales = ['numero_informe' => 'Consolidar hallazgos de la visita de inspección '. $validatedData['numero_informe'],
                                                'mensaje' => 'Todos los inspectores han registrado los hallazgos de la visita de inspección '. $validatedData['numero_informe'] . ' a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                    $validatedData['nit'].', por favor proceda a realizar la consolidación de estos hallazgos.'];

                    $this->enviar_correos($usuario_lider_visita->id_usuario, $asunto_email, $datos_adicionales); 

                    $lider_visita_id = GrupoVisitaInspeccion::where('id_informe', $validatedData['id'])
                                        ->where('estado', 'ACTIVO')
                                        ->whereNotIn('rol', ['Lider de visita'])
                                        ->first();

                    $lider_visita = User::where('id', $lider_visita_id->id_usuario)->first();

                    $usuarios[] = ['id' => $lider_visita->id, 'nombre' => $lider_visita->name];

                    $visita_inspeccion->usuario_actual = json_encode($usuarios);

                }

                $catidad_dias_etapa = Parametro::select('dias')
                        ->where('estado', $proxima_etapa)
                        ->first();

                if ($catidad_dias_etapa->dias > 0) {
                    $estado_etapa = 'VIGENTE';
                }else{
                    $estado_etapa = $validatedData['estado_etapa'];
                }

                $this->historialInformes($validatedData['id'], 'CARGUE DE HALLAZGOS DE LA VISITA DE INSPECCIÓN', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), '', $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                $visita_inspeccion->etapa = $proxima_etapa;
                $visita_inspeccion->estado_etapa = $estado_etapa;
                $visita_inspeccion->save();

                $this->conteoDias($visita_inspeccion->id, $proxima_etapa, date('Y-m-d'), NULL);
                $this->actualizarConteoDias($visita_inspeccion->id, $validatedData['etapa'], date('Y-m-d'));

                return response()->json(['message' => $successMessage]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }

    public function consolidar_hallazgos(Request $request){

        try {
        $proxima_etapa = '';
        $usuarios = [];

            $validatedData = $request->validate([
                'id' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'estado_etapa' => 'required',
                'numero_informe' => 'required',
                'razon_social' => 'required',
                'nit' => 'required',
                'registro_hallazgos_consolidados' => 'required',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                $redactor_visita = GrupoVisitaInspeccion::where('id_informe', $validatedData['id'])
                                        ->where('estado', 'ACTIVO')
                                        ->where('rol', 'Redactor')
                                        ->first();

                $asunto_email = 'Se cargó el archivo consolidado con los hallazgos de la visita de inspección '.$validatedData['numero_informe'];
                $datos_adicionales = [
                                        'numero_informe' => 'Se cargó el archivo consolidado con los hallazgos de la visita de inspección '. $validatedData['numero_informe'],
                                        'mensaje' => 'Se cargó el archivo consolidado con los hallazgos de la visita de inspección '. $validatedData['numero_informe'] . ' a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                                        $validatedData['nit'] . ' por parde del lider de la visita en el enlace ' . $validatedData['registro_hallazgos_consolidados'] 
                                    ];

                $this->enviar_correos($redactor_visita->id_usuario, $asunto_email, $datos_adicionales); 
                
                $usuario_redactor_visita = User::where('id', $redactor_visita->id_usuario)
                                                ->first();       
                
                $proxima_etapa = 'EN ELABORACIÓN DE PROYECTO DE INFORME FINAL';

                $usuarios[] = ['id' => $usuario_redactor_visita->id, 'nombre' => $usuario_redactor_visita->name];

                $successMessage = 'Hallazgos consolidados enviados correctamente al redactor';
                
                $this->historialInformes($validatedData['id'], 'CARGUE CONSOLIDADO DE HALLAZGOS DE LA VISITA DE INSPECCIÓN', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), '', $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                $catidad_dias_etapa = Parametro::select('dias')
                    ->where('estado', $proxima_etapa)
                    ->first();

                if ($catidad_dias_etapa->dias > 0) {
                    $estado_etapa = 'VIGENTE';
                }else{
                    $estado_etapa = $validatedData['estado_etapa'];
                }

                $usuariosSinDuplicados = collect($usuarios)->unique('id');

                $visita_inspeccion->etapa = $proxima_etapa;
                $visita_inspeccion->estado_etapa = $estado_etapa;
                $visita_inspeccion->usuario_actual = json_encode($usuariosSinDuplicados);
                $visita_inspeccion->hallazgos_consolidados = $validatedData['registro_hallazgos_consolidados'];
                $visita_inspeccion->save();

                $this->conteoDias($visita_inspeccion->id, $proxima_etapa, date('Y-m-d'), NULL);
                $this->actualizarConteoDias($visita_inspeccion->id, $validatedData['etapa'], date('Y-m-d'));

                return response()->json(['message' => $successMessage]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }

    public function proyecto_informe_final(Request $request){

        try {
        $proxima_etapa = '';
        $usuarios = [];

            $validatedData = $request->validate([
                'id' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'estado_etapa' => 'required',
                'numero_informe' => 'required',
                'razon_social' => 'required',
                'nit' => 'required',
                'proyecto_informe_final' => 'required',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                $redactor_visita = GrupoVisitaInspeccion::where('id_informe', $validatedData['id'])
                                        ->where('estado', 'ACTIVO')
                                        ->where('rol', 'Lider de visita')
                                        ->first();

                $asunto_email = 'Se cargó el proyecto de informe final de la visita '.$validatedData['numero_informe'];
                $datos_adicionales = [
                                        'numero_informe' => 'Se cargó el proyecto de informe final de la visita '. $validatedData['numero_informe'],
                                        'mensaje' => 'Se cargó el proyecto de informe final de la visita '. $validatedData['numero_informe'] . ' a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                                        $validatedData['nit'] . ' por parte del redactor de la visita en el enlace ' . $validatedData['proyecto_informe_final'] 
                                    ];

                $this->enviar_correos($redactor_visita->id_usuario, $asunto_email, $datos_adicionales); 
                
                $usuario_redactor_visita = User::where('id', $redactor_visita->id_usuario)
                                                ->first();       
                
                $proxima_etapa = 'EN REVISIÓN DEL PROYECTO DEL INFORME FINAL';

                $usuarios[] = ['id' => $usuario_redactor_visita->id, 'nombre' => $usuario_redactor_visita->name];

                $successMessage = 'Proyecto de informe final enviado correctamente al lider de la visita de inspección';
                
                $this->historialInformes($validatedData['id'], 'ENVÍO DE PROYECTO DE INFORME FINAL DE LA VISITA DE INSPECCIÓN', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), '', $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                $catidad_dias_etapa = Parametro::select('dias')
                        ->where('estado', $proxima_etapa)
                        ->first();

                if ($catidad_dias_etapa->dias > 0) {
                    $estado_etapa = 'VIGENTE';
                }else{
                    $estado_etapa = $validatedData['estado_etapa'];
                }

                $usuariosSinDuplicados = collect($usuarios)->unique('id');

                $visita_inspeccion->etapa = $proxima_etapa;
                $visita_inspeccion->estado_etapa = $estado_etapa;
                $visita_inspeccion->usuario_actual = json_encode($usuariosSinDuplicados);
                $visita_inspeccion->proyecto_informe_final = $validatedData['proyecto_informe_final'];
                $visita_inspeccion->save();

                $this->conteoDias($visita_inspeccion->id, $proxima_etapa, date('Y-m-d'), NULL);
                $this->actualizarConteoDias($visita_inspeccion->id, $validatedData['etapa'], date('Y-m-d'));

                return response()->json(['message' => $successMessage]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }

    public function revision_proyecto_informe_final(Request $request){

        try {
        $proxima_etapa = '';
        $usuarios = [];

            $validatedData = $request->validate([
                'id' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'estado_etapa' => 'required',
                'numero_informe' => 'required',
                'razon_social' => 'required',
                'nit' => 'required',
                'confirmacion_revision_proyecto_informe_final' => 'required',
                'revision_proyecto_informe_final' => 'required',
                'observaciones_revision_proyecto_informe_final' => 'required',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                if ($validatedData['confirmacion_revision_proyecto_informe_final'] === 'Si') {
                    $proxima_etapa = 'EN VERIFICACIÓN DE CORRECCIONES DEL INFORME FINAL';

                    $asunto_email = 'El proyecto de informe final de la visita '.$validatedData['numero_informe']. ' requiere solicitar revisiones';
                    $datos_adicionales = [
                                        'numero_informe' => 'El proyecto de informe final de la visita '. $validatedData['numero_informe'] . ' requiere solicitar revisiones',
                                        'mensaje' => 'El proyecto de informe final de la visita  '. $validatedData['numero_informe'] . ' a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                                        $validatedData['nit'] . ' requiere solicitar revisiones con las siguientes observaciones ' . $validatedData['observaciones_revision_proyecto_informe_final'] . ' en el enlace ' . $validatedData['revision_proyecto_informe_final']
                                    ];

                    $successMessage = 'Solicitud de requerimiento de correcciones enviada al lider de la visita de inspección';

                    $lider_visita = GrupoVisitaInspeccion::where('id_informe', $validatedData['id'])
                                        ->where('estado', 'ACTIVO')
                                        ->where('rol', 'Lider de visita')
                                        ->first();

                    $this->enviar_correos($lider_visita->id_usuario, $asunto_email, $datos_adicionales); 

                    $usuario_lider_visita = User::where('id', $lider_visita->id_usuario)
                                                    ->first();       
                    
                    $usuarios[] = ['id' => $usuario_lider_visita->id, 'nombre' => $usuario_lider_visita->name];
                                    
                }else {
                    $proxima_etapa = 'EN CORRECCIÓN DEL INFORME FINAL';

                    $asunto_email = 'El proyecto de informe final de la visita '.$validatedData['numero_informe']. ' requiere su atención';
                    $datos_adicionales = [
                                        'numero_informe' => 'El proyecto de informe final de la visita '. $validatedData['numero_informe'] . ' requiere requiere su atención',
                                        'mensaje' => 'El proyecto de informe final de la visita  '. $validatedData['numero_informe'] . ' a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                                        $validatedData['nit'] . ' requiere realizar las correcciones solicitadas por persona líder de visita de inspección con las siguientes observaciones ' . $validatedData['observaciones_revision_proyecto_informe_final'] . ' en el enlace ' . $validatedData['revision_proyecto_informe_final']
                                    ];

                    $successMessage = 'Solicitud de realizar las correcciones enviada al redactor de la visita de inspección';

                    $redactor = GrupoVisitaInspeccion::where('id_informe', $validatedData['id'])
                                        ->where('estado', 'ACTIVO')
                                        ->where('rol', 'Redactor')
                                        ->first();

                    $this->enviar_correos($redactor->id_usuario, $asunto_email, $datos_adicionales); 

                    $usuario_redactor = User::where('id', $redactor->id_usuario)
                                                    ->first();       
                    
                    $usuarios[] = ['id' => $usuario_redactor->id, 'nombre' => $usuario_redactor->name];
                }

                $catidad_dias_etapa = Parametro::select('dias')
                        ->where('estado', $proxima_etapa)
                        ->first();

                if ($catidad_dias_etapa->dias > 0) {
                    $estado_etapa = 'VIGENTE';
                }else{
                    $estado_etapa = $validatedData['estado_etapa'];
                }
                
                $this->historialInformes($validatedData['id'], 'ENVÍO DE REVISIÓN PROYECTO DE INFORME FINAL DE LA VISITA DE INSPECCIÓN', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $validatedData['observaciones_revision_proyecto_informe_final'], $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                $usuariosSinDuplicados = collect($usuarios)->unique('id');

                $visita_inspeccion->etapa = $proxima_etapa;
                $visita_inspeccion->estado_etapa = $estado_etapa;
                $visita_inspeccion->usuario_actual = json_encode($usuariosSinDuplicados);
                $visita_inspeccion->informe_final = $validatedData['revision_proyecto_informe_final'];
                $visita_inspeccion->save();

                $this->conteoDias($visita_inspeccion->id, $proxima_etapa, date('Y-m-d'), NULL);
                $this->actualizarConteoDias($visita_inspeccion->id, $validatedData['etapa'], date('Y-m-d'));

                return response()->json(['message' => $successMessage]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }

    public function verificaciones_correcciones_informe_final(Request $request){

        try {
        $proxima_etapa = '';
        $usuarios = [];

            $validatedData = $request->validate([
                'id' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'estado_etapa' => 'required',
                'numero_informe' => 'required',
                'razon_social' => 'required',
                'nit' => 'required',
                'observaciones_verificacion_correcciones_informe_final' => 'required',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                $redactor_visita = GrupoVisitaInspeccion::where('id_informe', $validatedData['id'])
                                        ->where('estado', 'ACTIVO')
                                        ->where('rol', 'Redactor')
                                        ->first();

                $asunto_email = 'Se requiere que realice correcciones al informe final de la visita '.$validatedData['numero_informe'];
                $datos_adicionales = [
                                        'numero_informe' => 'Se requiere que realice correcciones al informe final de la visita '. $validatedData['numero_informe'],
                                        'mensaje' => 'Se requiere que realice correcciones al informe final de la visita '. $validatedData['numero_informe'] . ' a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                                        $validatedData['nit'] . ' según las siguientes observaciones ' . $validatedData['observaciones_verificacion_correcciones_informe_final'] 
                                    ];

                $this->enviar_correos($redactor_visita->id_usuario, $asunto_email, $datos_adicionales); 
                
                $usuario_redactor_visita = User::where('id', $redactor_visita->id_usuario)
                                                ->first();       
                
                $proxima_etapa = 'EN CORRECCIÓN DEL INFORME FINAL';

                $usuarios[] = ['id' => $usuario_redactor_visita->id, 'nombre' => $usuario_redactor_visita->name];

                $successMessage = 'Correcciones enviadas al redactor de la visita de inspección';
                
                $this->historialInformes($validatedData['id'], 'ENVÍO DE SOLICITUD DE CORRECCIONES DE INFORME FINAL DE LA VISITA DE INSPECCIÓN', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $validatedData['observaciones_verificacion_correcciones_informe_final'], $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                $catidad_dias_etapa = Parametro::select('dias')
                        ->where('estado', $proxima_etapa)
                        ->first();

                if ($catidad_dias_etapa->dias > 0) {
                    $estado_etapa = 'VIGENTE';
                }else{
                    $estado_etapa = $validatedData['estado_etapa'];
                }

                $usuariosSinDuplicados = collect($usuarios)->unique('id');

                $visita_inspeccion->etapa = $proxima_etapa;
                $visita_inspeccion->estado_etapa = $estado_etapa;
                $visita_inspeccion->usuario_actual = json_encode($usuariosSinDuplicados);
                $visita_inspeccion->save();

                $this->conteoDias($visita_inspeccion->id, $proxima_etapa, date('Y-m-d'), NULL);
                $this->actualizarConteoDias($visita_inspeccion->id, $validatedData['etapa'], date('Y-m-d'));

                return response()->json(['message' => $successMessage]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }
    
    public function correcciones_informe_final(Request $request){

        try {
        $proxima_etapa = '';
        $usuarios = [];

            $validatedData = $request->validate([
                'id' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'estado_etapa' => 'required',
                'numero_informe' => 'required',
                'razon_social' => 'required',
                'nit' => 'required',
                'revision_proyecto_informe_final_corregido' => 'required',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {


                $proxima_etapa = 'EN REVISIÓN DE CORRECCIONES INFORME FINAL';

                $catidad_dias_etapa = Parametro::select('dias')
                        ->where('estado', $proxima_etapa)
                        ->first();

                if ($catidad_dias_etapa->dias > 0) {
                    $estado_etapa = 'VIGENTE';
                }else{
                    $estado_etapa = $validatedData['estado_etapa'];
                }

                $asunto_email = 'El proyecto de informe final de la visita '.$validatedData['numero_informe']. ' requiere su atención';
                $datos_adicionales = [
                                        'numero_informe' => 'El proyecto de informe final de la visita '. $validatedData['numero_informe'] . ' requiere requiere su atención',
                                        'mensaje' => 'Se cargaron las correcciones al proyecto de informe final de la visita  '. $validatedData['numero_informe'] . ' a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                                        $validatedData['nit'] . ' en el enlace ' . $validatedData['revision_proyecto_informe_final_corregido']
                                    ];

                $successMessage = 'Se envía notificación al lider de la visita de inspección para la verificación del informe final';

                $lider = GrupoVisitaInspeccion::where('id_informe', $validatedData['id'])
                                        ->where('estado', 'ACTIVO')
                                        ->where('rol', 'Lider de visita')
                                        ->first();

                $this->enviar_correos($lider->id_usuario, $asunto_email, $datos_adicionales); 

                $usuario_lider = User::where('id', $lider->id_usuario)
                                                    ->first();       
                    
                $usuarios[] = ['id' => $usuario_lider->id, 'nombre' => $usuario_lider->name];
                
                $this->historialInformes($validatedData['id'], 'ENVÍO DE CORRECCIONES PROYECTO DE INFORME FINAL DE LA VISITA DE INSPECCIÓN', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), '', $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                $usuariosSinDuplicados = collect($usuarios)->unique('id');

                $visita_inspeccion->etapa = $proxima_etapa;
                $visita_inspeccion->estado_etapa = $estado_etapa;
                $visita_inspeccion->usuario_actual = json_encode($usuariosSinDuplicados);
                $visita_inspeccion->proyecto_informe_final = $validatedData['revision_proyecto_informe_final_corregido'];
                $visita_inspeccion->save();

                $this->conteoDias($visita_inspeccion->id, $proxima_etapa, date('Y-m-d'), NULL);
                $this->actualizarConteoDias($visita_inspeccion->id, $validatedData['etapa'], date('Y-m-d'));

                return response()->json(['message' => $successMessage]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }
    
    public function remitir_proyecto_informe_final_coordinaciones(Request $request){

        try {
        $proxima_etapa = '';
        $usuarios = [];

            $validatedData = $request->validate([
                'id' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'estado_etapa' => 'required',
                'numero_informe' => 'required',
                'razon_social' => 'required',
                'nit' => 'required',
                'revision_proyecto_informe_final_coordinacinoes' => 'required',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                $proxima_etapa = 'EN REVISIÓN DEL INFORME FINAL';

                $catidad_dias_etapa = Parametro::select('dias')
                        ->where('estado', $proxima_etapa)
                        ->first();

                if ($catidad_dias_etapa->dias > 0) {
                    $estado_etapa = 'VIGENTE';
                }else{
                    $estado_etapa = $validatedData['estado_etapa'];
                }

                $asunto_email = 'El informe final de la visita '.$validatedData['numero_informe']. ' requiere su atención';
                $datos_adicionales = [
                                        'numero_informe' => 'El informe final de la visita '. $validatedData['numero_informe'] . ' requiere requiere su atención',
                                        'mensaje' => 'Se cargo el informe final de la visita  '. $validatedData['numero_informe'] . ' a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                                        $validatedData['nit'] . ' en el enlace ' . $validatedData['revision_proyecto_informe_final_coordinacinoes']
                                    ];

                $successMessage = 'Se envía notificación a la coordinación para la verificación del informe final';

                $usuarios_coordinadores = User::where('profile', 'Coordinador')
                                            ->get();       
                    
                foreach ($usuarios_coordinadores as $usuario) {
                    $usuarios[] = ['id' => $usuario->id, 'nombre' => $usuario->name];
                            
                    $this->enviar_correos($usuario->id, $asunto_email, $datos_adicionales);
                }
                
                $this->historialInformes($validatedData['id'], 'ENVÍO DE PROYECTO DE INFORME FINAL DE LA VISITA DE INSPECCIÓN', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), '', $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                $usuariosSinDuplicados = collect($usuarios)->unique('id');

                $visita_inspeccion->etapa = $proxima_etapa;
                $visita_inspeccion->estado_etapa = $estado_etapa;
                $visita_inspeccion->usuario_actual = json_encode($usuariosSinDuplicados);
                $visita_inspeccion->informe_final = $validatedData['revision_proyecto_informe_final_coordinacinoes'];
                $visita_inspeccion->save();

                $this->conteoDias($visita_inspeccion->id, $proxima_etapa, date('Y-m-d'), NULL);
                $this->actualizarConteoDias($visita_inspeccion->id, $validatedData['etapa'], date('Y-m-d'));

                return response()->json(['message' => $successMessage]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }

    public function revision_informe_final_coordinaciones(Request $request){

        try {
        $proxima_etapa = '';
        $usuarios = [];

            $validatedData = $request->validate([
                'id' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'estado_etapa' => 'required',
                'numero_informe' => 'required',
                'razon_social' => 'required',
                'nit' => 'required',
                'revision_informe_final_coordinaciones' => 'required',
                'observaciones_revision_informe_final_coordinaciones' => '',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                $proxima_etapa = 'EN REVISIÓN DEL INFORME FINAL INTENDENTE';

                $catidad_dias_etapa = Parametro::select('dias')
                        ->where('estado', $proxima_etapa)
                        ->first();

                if ($catidad_dias_etapa->dias > 0) {
                    $estado_etapa = 'VIGENTE';
                }else{
                    $estado_etapa = $validatedData['estado_etapa'];
                }

                $asunto_email = 'El informe final de la visita '.$validatedData['numero_informe']. ' requiere su atención';
                $datos_adicionales = [
                                        'numero_informe' => 'El informe final de la visita '. $validatedData['numero_informe'] . ' requiere requiere su atención',
                                        'mensaje' => 'Se cargo el informe final de la visita  '. $validatedData['numero_informe'] . ' a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                                        $validatedData['nit'] . ' en el enlace ' . $validatedData['revision_informe_final_coordinaciones'] . ' con las siguientes observaciones: ' . $validatedData['observaciones_revision_informe_final_coordinaciones']
                                    ];

                $successMessage = 'Se envía notificación a al intendente para la verificación del informe final';

                $this->enviar_correos($visita_inspeccion->usuario_creacion, $asunto_email, $datos_adicionales);

                $usuarios_intendente = User::where('id', $visita_inspeccion->usuario_creacion)
                                            ->first();

                $usuarios[] = ['id' => $usuarios_intendente->id, 'nombre' => $usuarios_intendente->name]; 
                    
                $this->historialInformes($validatedData['id'], 'ENVÍO DE PROYECTO DE INFORME FINAL DE LA VISITA DE INSPECCIÓN A INTENDECIA', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $validatedData['observaciones_revision_informe_final_coordinaciones'], $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                $usuariosSinDuplicados = collect($usuarios)->unique('id');

                $visita_inspeccion->etapa = $proxima_etapa;
                $visita_inspeccion->estado_etapa = $estado_etapa;
                $visita_inspeccion->usuario_actual = json_encode($usuariosSinDuplicados);
                $visita_inspeccion->informe_final = $validatedData['revision_informe_final_coordinaciones'];
                $visita_inspeccion->save();

                $this->conteoDias($visita_inspeccion->id, $proxima_etapa, date('Y-m-d'), NULL);
                $this->actualizarConteoDias($visita_inspeccion->id, $validatedData['etapa'], date('Y-m-d'));

                return response()->json(['message' => $successMessage]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }

    public function revision_informe_final_intendente(Request $request){

        try {
        $proxima_etapa = '';
        $usuarios = [];

            $validatedData = $request->validate([
                'id' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'estado_etapa' => 'required',
                'numero_informe' => 'required',
                'razon_social' => 'required',
                'nit' => 'required',
                'revision_informe_final_intendente' => 'required',
                'observaciones_revision_informe_final_intendente' => '',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                $proxima_etapa = 'EN FIRMA DEL INFORME FINAL POR COMISIÓN DE VISITA DE INSPECCIÓN';

                $catidad_dias_etapa = Parametro::select('dias')
                        ->where('estado', $proxima_etapa)
                        ->first();

                if ($catidad_dias_etapa->dias > 0) {
                    $estado_etapa = 'VIGENTE';
                }else{
                    $estado_etapa = $validatedData['estado_etapa'];
                }

                $asunto_email = 'El informe final de la visita '.$validatedData['numero_informe']. ' requiere su atención';
                $datos_adicionales = [
                                        'numero_informe' => 'El informe final de la visita '. $validatedData['numero_informe'] . ' requiere requiere su atención',
                                        'mensaje' => 'Se reviso el informe final de la visita  '. $validatedData['numero_informe'] . ' a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                                        $validatedData['nit'] . ' en el enlace ' . $validatedData['revision_informe_final_intendente'] . ' con las siguientes observaciones: ' . $validatedData['observaciones_revision_informe_final_intendente'] . '. Por favor ingrese a realizar la firma.'
                                    ];

                $successMessage = 'Se envía notificación al grupo de inspección para la firma';

                $grupo_inspeccion = GrupoVisitaInspeccion::where('id_informe', $validatedData['id'])
                                        ->where('estado', 'ACTIVO')
                                        ->get();

                foreach($grupo_inspeccion as $persona ){

                    $usuario = User::where('id', $persona->id_usuario)
                                    ->first();

                    $this->enviar_correos($usuario->id, $asunto_email, $datos_adicionales); 

                    $usuarios[] = ['id' => $usuario->id, 'nombre' => $usuario->name];
                }
              
                $this->historialInformes($validatedData['id'], 'ENVÍO DE DE INFORME FINAL PARA LA FIRMA DEL GRUPO DE INSPECCIÓN', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $validatedData['observaciones_revision_informe_final_intendente'], $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                $usuariosSinDuplicados = collect($usuarios)->unique('id');

                $visita_inspeccion->etapa = $proxima_etapa;
                $visita_inspeccion->estado_etapa = $estado_etapa;
                $visita_inspeccion->usuario_actual = json_encode($usuariosSinDuplicados);
                $visita_inspeccion->informe_final = $validatedData['revision_informe_final_intendente'];
                $visita_inspeccion->save();

                $this->conteoDias($visita_inspeccion->id, $proxima_etapa, date('Y-m-d'), NULL);
                $this->actualizarConteoDias($visita_inspeccion->id, $validatedData['etapa'], date('Y-m-d'));

                return response()->json(['message' => $successMessage]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }
    
    public function firmar_informe_final(Request $request){

        try {
        $proxima_etapa = '';

            $validatedData = $request->validate([
                'id' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'estado_etapa' => 'required',
                'numero_informe' => 'required',
                'razon_social' => 'required',
                'nit' => 'required',
                'informe_final_firmado' => 'required',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                $usuario_grupo_inspeccion = GrupoVisitaInspeccion::where('id_informe', $validatedData['id'])
                                        ->where('estado', 'ACTIVO')
                                        ->where('id_usuario', Auth::id())
                                        ->get();

                foreach($usuario_grupo_inspeccion as $persona ){ 
                    $persona->informe_firmado = 'SI';
                    $persona->save();
                }

                $numero_registros = GrupoVisitaInspeccion::where('id_informe', $validatedData['id'])
                                        ->where('estado', 'ACTIVO')
                                        ->whereNull('informe_firmado')
                                        ->count();

                if ($numero_registros >= 1) {
                    $proxima_etapa = 'EN FIRMA DEL INFORME FINAL POR COMISIÓN DE VISITA DE INSPECCIÓN';
                    $successMessage = 'Se registro la firma del informe final, aún faltan '. $numero_registros . ' usuarios por firmar el informe.';
                }else{
                    $proxima_etapa = 'EN CONFIRMACIÓN DE MEDIDA DE INTERVENCIÓN INMEDIATA';
                    $successMessage = 'Se registro la firma del informe final, se envía notificación a la intendencia para cofirmar si se necesita intervención inmediata.';

                    $asunto_email = 'Confirmar intervención inmediata de la visita de inspección '.$validatedData['numero_informe'];
                    $datos_adicionales = ['numero_informe' => 'Confirmar intervención inmediata de la visita de inspección '. $validatedData['numero_informe'],
                                                'mensaje' => 'Se requiere de su confirmación si la visita de inspección '. $validatedData['numero_informe'] . ' a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                    $validatedData['nit'].', requiere medida de intervención inmediata.'];

                    $this->enviar_correos($visita_inspeccion->usuario_creacion, $asunto_email, $datos_adicionales); 

                    $usuario = User::where('id', $visita_inspeccion->usuario_creacion)
                                    ->first();
                    $usuarios[] = $usuarios[] = ['id' => $usuario->id, 'nombre' => $usuario->name];

                    $usuariosSinDuplicados = collect($usuarios)->unique('id');

                    $visita_inspeccion->usuario_actual = json_encode($usuariosSinDuplicados);
                }

                $catidad_dias_etapa = Parametro::select('dias')
                        ->where('estado', $proxima_etapa)
                        ->first();

                if ($catidad_dias_etapa->dias > 0) {
                    $estado_etapa = 'VIGENTE';
                }else{
                    $estado_etapa = $validatedData['estado_etapa'];
                }

                $this->historialInformes($validatedData['id'], 'REGISTRO DE INFORME FINAL FIRMADO', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), '', $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                $visita_inspeccion->etapa = $proxima_etapa;
                $visita_inspeccion->estado_etapa = $estado_etapa;
                $visita_inspeccion->informe_final = $validatedData['informe_final_firmado'];
                $visita_inspeccion->save();

                $this->conteoDias($visita_inspeccion->id, $proxima_etapa, date('Y-m-d'), NULL);
                $this->actualizarConteoDias($visita_inspeccion->id, $validatedData['etapa'], date('Y-m-d'));

                return response()->json(['message' => $successMessage]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }

    public function confirmacion_intervencion_inmediata(Request $request){ //TODO: proceso no claro

        try {
            $proxima_etapa = '';
            $usuarios = [];

            $validatedData = $request->validate([
                'id' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'estado_etapa' => 'required',
                'numero_informe' => 'required',
                'razon_social' => 'required',
                'nit' => 'required',
                'confirmacion_intervencion_inmediata' => 'required',
                'observaciones_intervencion_inmediata' => '',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                if ($validatedData['confirmacion_intervencion_inmediata'] === 'Si') {
                    /*$proxima_etapa = 'EN VERIFICACIÓN DE LOS CONTENIDOS FINALES DEL EXPEDIENTE';

                    $asunto_email = 'Verificar los contenidos finales del expediente de la visita '.$validatedData['numero_informe'];
                    $datos_adicionales = ['numero_informe' => 'Verificar los contenidos finales del expediente de la visita '. $validatedData['numero_informe'],
                                                'mensaje' => 'Debe los contenidos finales de la visita '. $validatedData['numero_informe'] . ' a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                    $validatedData['nit'] ];

                    $usuario_lider_visita = GrupoVisitaInspeccion::where('id_informe', $validatedData['id'])
                                            ->where('rol', 'Lider de visita')
                                            ->where('estado', 'ACTIVO')
                                            ->first();

                    $usuario_lider_visita = User::where('id', $usuario_lider_visita->id_usuario)
                                            ->first();

                    $this->enviar_correos($usuario_lider_visita->id, $asunto_email, $datos_adicionales);   
                    
                    $usuarios = [['id' => $usuario_lider_visita->id, 'nombre' => $usuario_lider_visita->name]];*/

                    $proxima_etapa = 'EN ENVÍO DE INFORME DE VISITA DE INSPECCIÓN PARA TRASLADO';

                    $asunto_email = 'Enviar informe de traslado de la visita de inspección '.$validatedData['numero_informe'];
                    $datos_adicionales = ['numero_informe' => 'Enviar informe de traslado de la visita de inspección '. $validatedData['numero_informe'],
                                                'mensaje' => 'Se requiere que realice el memorando de traslado del informe de la organización '. $validatedData['numero_informe'].' para la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                    $validatedData['nit']];

                    $usuario_lider_visita = GrupoVisitaInspeccion::where('id_informe', $validatedData['id'])
                                            ->where('rol', 'Lider de visita')
                                            ->where('estado', 'ACTIVO')
                                            ->first();

                    $usuario_lider_visita = User::where('id', $usuario_lider_visita->id_usuario)
                                            ->first();

                    $this->enviar_correos($usuario_lider_visita->id, $asunto_email, $datos_adicionales); 

                    $usuarios[] = ['id' => $usuario_lider_visita->id, 'nombre' => $usuario_lider_visita->name];

                    $usuarios_coordinadores = User::where('profile', 'Coordinador')
                                            ->get();       
                    
                    foreach ($usuarios_coordinadores as $usuario) {
                        $usuarios[] = ['id' => $usuario->id, 'nombre' => $usuario->name];
                                
                        $this->enviar_correos($usuario->id, $asunto_email, $datos_adicionales);
                    }

                    $this->historialInformes($validatedData['id'], 'CONFIRMACIÓN DE QUE SI ES NECESARIA LA INTERVENCIÓN INMEDIATA', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $validatedData['observaciones_intervencion_inmediata'], $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                    $successMessage = 'Se actualizó la visita correctamente';

                }elseif($validatedData['confirmacion_intervencion_inmediata'] === 'No'){
                    $proxima_etapa = 'EN ENVÍO DE INFORME DE VISITA DE INSPECCIÓN PARA TRASLADO';

                    $asunto_email = 'Enviar informe de traslado de la visita de inspección '.$validatedData['numero_informe'];
                    $datos_adicionales = ['numero_informe' => 'Enviar informe de traslado de la visita de inspección '. $validatedData['numero_informe'],
                                                'mensaje' => 'Se requiere que realice el memorando de traslado del informe de la organización '. $validatedData['numero_informe'].' para la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                    $validatedData['nit']];

                    $usuario_lider_visita = GrupoVisitaInspeccion::where('id_informe', $validatedData['id'])
                                            ->where('rol', 'Lider de visita')
                                            ->where('estado', 'ACTIVO')
                                            ->first();

                    $usuario_lider_visita = User::where('id', $usuario_lider_visita->id_usuario)
                                            ->first();

                    $this->enviar_correos($usuario_lider_visita->id, $asunto_email, $datos_adicionales); 

                    $usuarios[] = ['id' => $usuario_lider_visita->id, 'nombre' => $usuario_lider_visita->name];

                    $usuarios_coordinadores = User::where('profile', 'Coordinador')
                                            ->get();       
                    
                    foreach ($usuarios_coordinadores as $usuario) {
                        $usuarios[] = ['id' => $usuario->id, 'nombre' => $usuario->name];
                                
                        $this->enviar_correos($usuario->id, $asunto_email, $datos_adicionales);
                    }

                    $this->historialInformes($validatedData['id'], 'CONFIRMACIÓN DE QUE NO ES NECESARIA LA INTERVENCIÓN INMEDIATA', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $validatedData['observaciones_intervencion_inmediata'], $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                    $successMessage = 'Se envío la notificación a la coordinación del grupo de inspección y lider de visita para realizar el memorando con el informe de traslado';
                }

                $catidad_dias_etapa = Parametro::select('dias')
                        ->where('estado', $proxima_etapa)
                        ->first();

                if ($catidad_dias_etapa->dias > 0) {
                    $estado_etapa = 'VIGENTE';
                }else{
                    $estado_etapa = $validatedData['estado_etapa'];
                }

                $usuariosSinDuplicados = collect($usuarios)->unique('id');

                $visita_inspeccion->etapa = $proxima_etapa;
                $visita_inspeccion->estado_etapa = $estado_etapa;
                $visita_inspeccion->usuario_actual = json_encode($usuariosSinDuplicados);
                $visita_inspeccion->save();


                $this->conteoDias($visita_inspeccion->id, $proxima_etapa, date('Y-m-d'), NULL);
                $this->actualizarConteoDias($visita_inspeccion->id, $validatedData['etapa'], date('Y-m-d'));

                return response()->json(['message' => $successMessage]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }
    
    public function enviar_traslado(Request $request) {
        try {

            $validatedData = $request->validate([
                //'grupo_inspeccion' => 'required',
                'id' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'estado_etapa' => 'required',
                'numero_informe' => 'required',
                'razon_social' => 'required',
                'nit' => 'required',
                'ciclo_informe_traslado' => 'required',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                        ->with('etapaProceso')
                                                        ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                //$grupo_visita_inspeccion = json_decode($validatedData['grupo_inspeccion'], true);

                $usuarios = [];

                /*foreach ($grupo_visita_inspeccion as $persona) {

                        $grupo_visita_inspeccion = new GrupoVisitaInspeccion();
                        $grupo_visita_inspeccion->id_informe = $validatedData['id'];
                        $grupo_visita_inspeccion->id_usuario = $persona['usuario'];
                        $grupo_visita_inspeccion->rol = $persona['rol'];
                        $grupo_visita_inspeccion->estado = 'ACTIVO';
                        $grupo_visita_inspeccion->usuario_creacion = Auth::id();
                        $grupo_visita_inspeccion->save();

                        $asunto_email = 'Designación para proyectar y remitir oficio de traslado de la visita de inspección ' .$validatedData['numero_informe'];
                        $datos_adicionales = ['numero_informe' => 'Designación para proyectar y remitir oficio de traslado de la visita de inspección '. $validatedData['numero_informe'],
                                                    'mensaje' => 'Usted ha sido la persona seleccionada como designado para proyectar y remitir el oficio de traslado, para la visita de inspección identificada con el número ' . $validatedData['numero_informe']. ' a la entidad '
                                                    . $validatedData['razon_social'] . ' identificada con el nit ' . $validatedData['nit'] . ', el memorando de traslado se encuentra en el ciclo de vida '. $validatedData['ciclo_informe_traslado'] . ' y el informe final en el siguiente enlace: ' 
                                                    . $visita_inspeccion->informe_final ];

                        $this->enviar_correos($persona['usuario'], $asunto_email, $datos_adicionales);

                        $usuarios_lider_visita = User::where('id', $persona['usuario'])
                                        ->first();
                        $usuarios[] = ['id' => $usuarios_lider_visita->id, 'nombre' => $usuarios_lider_visita->name];

                }*/

                $grupo_inspeccion = GrupoVisitaInspeccion::where('estado', 'ACTIVO')
                    ->where('id_informe', $validatedData['id'])
                    ->get();

                foreach ($grupo_inspeccion as  $persona) {
                    $asunto_email = 'Proyectar y remitir oficio de traslado de la visita de inspección ' .$validatedData['numero_informe'];
                    $datos_adicionales = ['numero_informe' => 'Proyectar y remitir oficio de traslado de la visita de inspección '. $validatedData['numero_informe'],
                                                        'mensaje' => 'Proyectar y remitir el oficio de traslado, para la visita de inspección identificada con el número ' . $validatedData['numero_informe']. ' a la entidad '
                                                        . $validatedData['razon_social'] . ' identificada con el nit ' . $validatedData['nit'] . ', el memorando de traslado se encuentra en el ciclo de vida '. $validatedData['ciclo_informe_traslado'] . ' y el informe final en el siguiente enlace: ' 
                                                        . $visita_inspeccion->informe_final ];

                    $this->enviar_correos($persona['usuario'], $asunto_email, $datos_adicionales);

                    $usuarios_lider_visita = User::where('id', $persona['id_usuario'])
                                            ->first();

                    $usuarios_lider[] = ['id' => $usuarios_lider_visita->id, 'nombre' => $usuarios_lider_visita->name];
                    $usuarios[] = ['id' => $usuarios_lider_visita->id, 'nombre' => $usuarios_lider_visita->name];
                }

                $usuariosNombres = '';
                foreach ($usuarios_lider as $usuario) {
                    $usuariosNombres .= $usuario['nombre'] . ', ';
                }

                $usuariosNombres = rtrim($usuariosNombres, ', ');

                $asunto_email = 'Envío de oficio de traslado de la visita ' .$validatedData['numero_informe'];
                $datos_adicionales = ['numero_informe' => 'Envío de oficio de traslado de la visita '. $validatedData['numero_informe'],
                                                    'mensaje' => 'Se realizó el envío del oficio de traslado a los usuarios '. $usuariosNombres .', para la visita de inspección identificada con el número ' . $validatedData['numero_informe']. ' a la entidad '
                                                    . $validatedData['razon_social'] . ' identificada con el nit ' . $validatedData['nit'] . ', el memorando de traslado se encuentra en el ciclo de vida '. $validatedData['ciclo_informe_traslado'] . ' y el informe final en el siguiente enlace: ' 
                                                    . $visita_inspeccion->informe_final ];

                $this->enviar_correos($visita_inspeccion->usuario_creacion, $asunto_email, $datos_adicionales);


                $proxima_etapa = 'EN PROYECCIÓN DEL OFICIO DE TRASLADO DEL INFORME';

                $catidad_dias_etapa = Parametro::select('dias')
                        ->where('estado', $proxima_etapa)
                        ->first();

                if ($catidad_dias_etapa->dias > 0) {
                    $estado_etapa = 'VIGENTE';
                }else{
                    $estado_etapa = $validatedData['estado_etapa'];
                }

                $usuariosSinDuplicados = collect($usuarios)->unique('id');

                $visita_inspeccion->etapa = $proxima_etapa;
                $visita_inspeccion->estado_etapa = $estado_etapa;
                $visita_inspeccion->usuario_actual = json_encode($usuariosSinDuplicados);
                $visita_inspeccion->save();

                $this->historialInformes($validatedData['id'], 'ENVÍO DE MEMORANDO DE OFICIO DE TRASLADO', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), '', $validatedData['estado_etapa'], $validatedData['ciclo_informe_traslado'], NULL, NULL, '', NULL);

                $this->conteoDias($visita_inspeccion->id, 'EN PROYECCIÓN DEL OFICIO DE TRASLADO DEL INFORME', date('Y-m-d'), NULL);
                $this->actualizarConteoDias($visita_inspeccion->id, $validatedData['etapa'], date('Y-m-d'));

                $successMessage = 'Se envío notificación de memorando de oficio de traslado a las coordinaciones, intendencia, superintendencias delegadas y usuarios del grupo de inspección';

                return response()->json(['message' => $successMessage]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }

    public function informe_traslado_entidad(Request $request){

        try {
        $proxima_etapa = '';
        $usuarios = [];

            $validatedData = $request->validate([
                'id' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'estado_etapa' => 'required',
                'numero_informe' => 'required',
                'razon_social' => 'required',
                'nit' => 'required',
                'ciclo_informe_traslado_entidad' => 'required',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                $proxima_etapa = 'EN ESPERA DE PRONUNCIAMIENTO DE LA ORGANIZACIÓN SOLIDARIA';

                $catidad_dias_etapa = Parametro::select('dias')
                        ->where('estado', $proxima_etapa)
                        ->first();

                if ($catidad_dias_etapa->dias > 0) {
                    $estado_etapa = 'VIGENTE';
                }else{
                    $estado_etapa = $validatedData['estado_etapa'];
                }

                $asunto_email = 'Envío del oficio traslado a la entidad solidaria de la visita '.$validatedData['numero_informe'];
                $datos_adicionales = ['numero_informe' => 'Envío del oficio traslado a la entidad solidaria de la visita '. $validatedData['numero_informe'],
                                                'mensaje' => 'Se realizó el envío del oficio traslado de la visita '. $validatedData['numero_informe'] . ' a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                $validatedData['nit']. ' recuerde que esta entidad tiene 5 días habiles para emitir una respuesta y está se debe registrar en el aplicativo.' ];

                $usuarios_designados = GrupoVisitaInspeccion::where('id_informe', $validatedData['id'])
                                            ->where('estado', 'ACTIVO')
                                            ->get();

                foreach ($usuarios_designados as $usuario_designado) {

                        $usuario = User::where('id', $usuario_designado->id_usuario)
                                            ->first();

                        $usuarios[] = ['id' => $usuario->id, 'nombre' => $usuario->name];

                        $this->enviar_correos($usuario_designado->id_usuario, $asunto_email, $datos_adicionales); 
                }                    

                $this->historialInformes($validatedData['id'], 'ENVÍO DE INFORME DE TRASLADO A LA ENTIDAD SOLIDARIA', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), '', $validatedData['estado_etapa'], $validatedData['ciclo_informe_traslado_entidad'], NULL, NULL, '', NULL);

                $successMessage = 'Se registro el envío del informe a la entidad solidaria correctamente';

                $usuariosSinDuplicados = collect($usuarios)->unique('id');

                $visita_inspeccion->etapa = $proxima_etapa;
                $visita_inspeccion->estado_etapa = $estado_etapa;
                $visita_inspeccion->usuario_actual = json_encode($usuariosSinDuplicados);
                $visita_inspeccion->save();


                $this->conteoDias($visita_inspeccion->id, $proxima_etapa, date('Y-m-d'), NULL);
                $this->actualizarConteoDias($visita_inspeccion->id, $validatedData['etapa'], date('Y-m-d'));

                return response()->json(['message' => $successMessage]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }

    public function registrar_pronunciamiento_entidad(Request $request){

        try {
        $proxima_etapa = '';
        $usuarios = [];

            $validatedData = $request->validate([
                'id' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'estado_etapa' => 'required',
                'numero_informe' => 'required',
                'razon_social' => 'required',
                'nit' => 'required',
                'confirmacion_pronunciacion_entidad' => 'required',
                'radicado_entrada_pronunciacion' => 'required_if:confirmacion_pronunciacion_entidad,Si',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                if ($validatedData['confirmacion_pronunciacion_entidad'] === 'Si') {
                    $proxima_etapa = 'EN VALORACIÓN DE LA INFORMACIÓN REMITIDA POR LA ORGANIZACIÓN SOLIDARIA';

                    $asunto_email = 'Valorar la información recibida de parte de la organización de la economía solidaria supervisada de la visita '.$validatedData['numero_informe'];
                    $datos_adicionales = ['numero_informe' => 'Valorar la información recibida de parte de la organización de la economía solidaria supervisada de la visita '. $validatedData['numero_informe'],
                                                    'mensaje' => 'Valorar la información recibida de parte de la organización de la economía solidaria supervisada de la visita '. $validatedData['numero_informe'] . ' a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                    $validatedData['nit']. ' que fue registrada con el radicado de entrada ' . $validatedData['radicado_entrada_pronunciacion']];

                    $usuarios_designados = GrupoVisitaInspeccion::where('id_informe', $validatedData['id'])
                                                ->where('estado', 'ACTIVO')
                                                ->get();

                    foreach ($usuarios_designados as $key => $usuario_designado) {

                            $usuario = User::where('id', $usuario_designado->id_usuario)
                                                ->first();

                            $usuarios[] = ['id' => $usuario->id, 'nombre' => $usuario->name];

                            $this->enviar_correos($usuario_designado->id_usuario, $asunto_email, $datos_adicionales); 
                    }  

                    $this->historialInformes($validatedData['id'], 'REGISTRO DE PRONUNCIAMIENTO DE ENTIDAD', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), '', $validatedData['estado_etapa'], $validatedData['radicado_entrada_pronunciacion'], NULL, NULL, '', NULL);

                    $successMessage = 'Se registro el pronunciamiento de la entidad solidaria correctamente';

                }else {
                    $proxima_etapa = 'EN DEFINICIÓN DE HALLAZGOS';

                    $asunto_email = 'Definir hallazgos finales de la visita '.$validatedData['numero_informe'];
                    $datos_adicionales = ['numero_informe' => 'Definir hallazgos finales de la visita '. $validatedData['numero_informe'],
                                                    'mensaje' => 'Definir hallazgos finales de la visita '. $validatedData['numero_informe'] . ' a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                    $validatedData['nit']];

                    $usuarios_designados = GrupoVisitaInspeccion::where('id_informe', $validatedData['id'])
                                                ->where('estado', 'ACTIVO')
                                                ->get();


                    foreach ($usuarios_designados as $key => $usuario_designado) {

                            $usuario = User::where('id', $usuario_designado->id_usuario)
                                                ->first();

                            $usuarios[] = ['id' => $usuario->id, 'nombre' => $usuario->name];

                            $this->enviar_correos($usuario_designado->id_usuario, $asunto_email, $datos_adicionales); 
                    }  

                    $this->historialInformes($validatedData['id'], 'REGISTRO DE NO PRONUNCIAMIENTO DE ENTIDAD', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), '', $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                    $successMessage = 'Se registro el no pronunciamiento de la entidad solidaria correctamente';
                }

                $catidad_dias_etapa = Parametro::select('dias')
                        ->where('estado', $proxima_etapa)
                        ->first();

                if ($catidad_dias_etapa->dias > 0) {
                    $estado_etapa = 'VIGENTE';
                }else{
                    $estado_etapa = $validatedData['estado_etapa'];
                }

                $usuariosSinDuplicados = collect($usuarios)->unique('id');

                $visita_inspeccion->etapa = $proxima_etapa;
                $visita_inspeccion->estado_etapa = $estado_etapa;
                $visita_inspeccion->usuario_actual = json_encode($usuariosSinDuplicados);
                $visita_inspeccion->save();

                $this->conteoDias($visita_inspeccion->id, $proxima_etapa, date('Y-m-d'), NULL);
                $this->actualizarConteoDias($visita_inspeccion->id, $validatedData['etapa'], date('Y-m-d'));

                return response()->json(['message' => $successMessage]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }

    public function registrar_valoracion_respuesta(Request $request){

        try {
        $proxima_etapa = '';
        $usuarios = [];

            $validatedData = $request->validate([
                'id' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'estado_etapa' => 'required',
                'numero_informe' => 'required',
                'razon_social' => 'required',
                'nit' => 'required',
                'evaluacion_respuesta' => 'required',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                $proxima_etapa = 'EN DEFINICIÓN DE HALLAZGOS';

                $catidad_dias_etapa = Parametro::select('dias')
                        ->where('estado', $proxima_etapa)
                        ->first();

                if ($catidad_dias_etapa->dias > 0) {
                    $estado_etapa = 'VIGENTE';
                }else{
                    $estado_etapa = $validatedData['estado_etapa'];
                }

                $asunto_email = 'Definir hallazgos finales de la visita '.$validatedData['numero_informe'];
                $datos_adicionales = ['numero_informe' => 'Definir hallazgos finales de la visita '. $validatedData['numero_informe'],
                                                    'mensaje' => 'Definir hallazgos finales de la visita '. $validatedData['numero_informe'] . ' a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                                        $validatedData['nit']];

                $usuarios_designados = GrupoVisitaInspeccion::where('id_informe', $validatedData['id'])
                                                ->where('estado', 'ACTIVO')
                                                ->get();

                foreach ($usuarios_designados as $key => $usuario_designado) {

                    $usuario = User::where('id', $usuario_designado->id_usuario)
                                                ->first();

                    $usuarios[] = ['id' => $usuario->id, 'nombre' => $usuario->name];

                    $this->enviar_correos($usuario_designado->id_usuario, $asunto_email, $datos_adicionales); 
                }  

                $this->historialInformes($validatedData['id'], 'REGISTRO DE EVALUACIÓN DE RESPETA DE LA ENTIDAD', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), '', $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                $successMessage = 'Se registro la evaluación de la respuesta de la entidad solidaria correctamente';

                $usuariosSinDuplicados = collect($usuarios)->unique('id');

                $visita_inspeccion->etapa = $proxima_etapa;
                $visita_inspeccion->estado_etapa = $estado_etapa;
                $visita_inspeccion->evaluacion_respuesta_entidad = $validatedData['evaluacion_respuesta'] ;
                $visita_inspeccion->usuario_actual = json_encode($usuariosSinDuplicados);
                $visita_inspeccion->save();

                $this->conteoDias($visita_inspeccion->id, $proxima_etapa, date('Y-m-d'), NULL);
                $this->actualizarConteoDias($visita_inspeccion->id, $validatedData['etapa'], date('Y-m-d'));

                return response()->json(['message' => $successMessage]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }

    public function registrar_informe_hallazgos_finales(Request $request){

        try {

        $proxima_etapa = '';
        $usuarios = [];

            $validatedData = $request->validate([
                'id' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'estado_etapa' => 'required',
                'numero_informe' => 'required',
                'razon_social' => 'required',
                'nit' => 'required',
                'ciclo_informe_final_hallazgos' => 'required',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                $proxima_etapa = 'EN PROPOSICIÓN DE ACTUACIÓN ADMINISTRATIVA';

                $catidad_dias_etapa = Parametro::select('dias')
                        ->where('estado', $proxima_etapa)
                        ->first();

                if ($catidad_dias_etapa->dias > 0) {
                    $estado_etapa = 'VIGENTE';
                }else{
                    $estado_etapa = $validatedData['estado_etapa'];
                }

                $asunto_email = 'Proponer actuazión administrativa de la visita '.$validatedData['numero_informe'];
                $datos_adicionales = ['numero_informe' => 'Proponer actuazión administrativa de la visita '. $validatedData['numero_informe'],
                                                    'mensaje' => 'Proponer actuazión administrativa de la visita '. $validatedData['numero_informe'] . ' a la entidad '. $validatedData['ciclo_informe_final_hallazgos'] . ' identificada con el nit '.
                                        $validatedData['nit'] . ', se cargo el infome con hallazgos en el ciclo de vida '. $validatedData['ciclo_informe_final_hallazgos']];

                $this->enviar_correos($visita_inspeccion->usuario_creacion, $asunto_email, $datos_adicionales); 

                $usuario = User::where('id', $visita_inspeccion->usuario_creacion)
                ->first();

                $usuarios[] = ['id' => $usuario->id, 'nombre' => $usuario->name];


                $this->historialInformes($validatedData['id'], 'REGISTRO DE INFORME FINAL CON HALLAZGOS', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), '', $validatedData['estado_etapa'], $validatedData['ciclo_informe_final_hallazgos'], NULL, NULL, '', NULL);

                $successMessage = 'Se registro el envío del informe con los hallazgos finales correctamente';

                $usuariosSinDuplicados = collect($usuarios)->unique('id');

                $visita_inspeccion->etapa = $proxima_etapa;
                $visita_inspeccion->estado_etapa = $estado_etapa;
                $visita_inspeccion->usuario_actual = json_encode($usuariosSinDuplicados);
                $visita_inspeccion->save();

                $this->conteoDias($visita_inspeccion->id, $proxima_etapa, date('Y-m-d'), NULL);
                $this->actualizarConteoDias($visita_inspeccion->id, $validatedData['etapa'], date('Y-m-d'));

                return response()->json(['message' => $successMessage]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }

    public function proponer_actuacion_administrativa(Request $request){

        try {
            $proxima_etapa = '';
            $usuarios = [];

            $validatedData = $request->validate([
                'id' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'estado_etapa' => 'required',
                'numero_informe' => 'required',
                'razon_social' => 'required',
                'nit' => 'required',
                'tipo_recomendacion' => 'required',
                'ciclo_informe_final_hallazgos_intendencia' => 'required',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                $proxima_etapa = 'EN VERIFICACIÓN DE LOS CONTENIDOS FINALES DEL EXPEDIENTE';

                $catidad_dias_etapa = Parametro::select('dias')
                        ->where('estado', $proxima_etapa)
                        ->first();

                if ($catidad_dias_etapa->dias > 0) {
                    $estado_etapa = 'VIGENTE';
                }else{
                    $estado_etapa = $validatedData['estado_etapa'];
                }

                $asunto_email = 'Verificar los contenidos finales del expediente de la visita '.$validatedData['numero_informe'];
                $datos_adicionales = ['numero_informe' => 'Verificar los contenidos finales del expediente de la visita '. $validatedData['numero_informe'],
                                                'mensaje' => 'Debe los contenidos finales de la visita '. $validatedData['numero_informe'] . ' a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                $validatedData['nit'] ];

                $usuario_lider_visita = GrupoVisitaInspeccion::where('id_informe', $validatedData['id'])
                                            ->where('rol', 'Lider de visita')
                                            ->where('estado', 'ACTIVO')
                                            ->first();

                $usuario_lider_visita = User::where('id', $usuario_lider_visita->id_usuario)
                                            ->first();

                $this->enviar_correos($usuario_lider_visita->id, $asunto_email, $datos_adicionales);   
                    
                $usuarios = [['id' => $usuario_lider_visita->id, 'nombre' => $usuario_lider_visita->name]];

                $this->historialInformes($validatedData['id'], 'ENVÍO DE PROPOSICIÓN DE ACTUACIÓN ADMINISTRATIVA ', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), 'TIPO DE RECOMENDACIÓN: '.$validatedData['tipo_recomendacion'], $validatedData['estado_etapa'], $validatedData['ciclo_informe_final_hallazgos_intendencia'], NULL, NULL, '', NULL);

                $successMessage = 'Se actualizó la visita correctamente';

                $usuariosSinDuplicados = collect($usuarios)->unique('id');

                $visita_inspeccion->etapa = $proxima_etapa;
                $visita_inspeccion->estado_etapa = $estado_etapa;
                $visita_inspeccion->usuario_actual = json_encode($usuariosSinDuplicados);
                $visita_inspeccion->save();


                $this->conteoDias($visita_inspeccion->id, $proxima_etapa, date('Y-m-d'), NULL);
                $this->actualizarConteoDias($visita_inspeccion->id, $validatedData['etapa'], date('Y-m-d'));

                return response()->json(['message' => $successMessage]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }

    public function modificar_grupo_inspeccion(Request $request) {
        try {
            $validatedData = $request->validate([
                'grupo_inspeccion' => 'required',
                'id' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'estado_etapa' => 'required',
                'numero_informe' => 'required',
                'razon_social' => 'required',
                'nit' => 'required',
                'observaciones' => '',
            ]);
    
            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                 ->with('etapaProceso')
                                                 ->first();
    
            if ($visita_inspeccion->etapa !== $validatedData['etapa']) {
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            } else {
                $grupo_visita_inspeccion = json_decode($validatedData['grupo_inspeccion'], true);
                $usuarios_actualizados = [];
                $usuarios_eliminados = [];
    
                foreach ($grupo_visita_inspeccion as $persona) {
                    if ($persona['rol'] === 'Redactor' || $persona['rol'] === 'Lider de visita') {
                        $redactor_actual = GrupoVisitaInspeccion::where('id_informe', $validatedData['id'])
                                                                ->where('rol', $persona['rol'])
                                                                ->where('estado', 'ACTIVO')
                                                                ->first();

                        if ($persona['usuario'] !== $redactor_actual->id_usuario) {

                            $usuario_actual = User::where('id', $redactor_actual->id_usuario)
                                                    ->first();

                            $usuario_nuevo = User::where('id', $persona['usuario'])
                                                    ->first();

                            $usuarios_actualizados[] = "Se cambio al usuario ".$usuario_actual->name. " con el rol ". $persona['rol']. " por el usuario ".$usuario_nuevo->name;
                            $redactor_actual->id_usuario = $persona['usuario'];
                            $redactor_actual->save();
        
                            $asunto_email = 'Asignación ' . $persona['rol'] . ' visita de inspección ' . $validatedData['numero_informe'];
                            $datos_adicionales = [
                                'numero_informe' => 'Ha sido asignado como la persona con el rol de ' . $persona['rol'] . ' para la visita de inspección ' . $validatedData['numero_informe'],
                                'mensaje' => 'Usted ha sido la persona seleccionada con el rol de ' . $persona['rol'] . ' para la visita de inspección identificada con el número ' . $validatedData['numero_informe'] . ' con la siguiente observación: ' . $validatedData['observaciones']
                            ];

                            $this->enviar_correos($persona['usuario'], $asunto_email, $datos_adicionales);

                            $asunto_email = 'Desasignación ' . $persona['rol'] . ' visita de inspección ' . $validatedData['numero_informe'];
                            $datos_adicionales = [
                                'numero_informe' => 'Se ha modificado el grupo de inspección ' . ' para la visita de inspección ' . $validatedData['numero_informe'],
                                'mensaje' => 'Usted ya no es la persona designada con el rol de ' . $persona['rol'] . ' para la visita de inspección identificada con el número ' . $validatedData['numero_informe']. ' con la siguiente observación: ' . $validatedData['observaciones']
                            ];

                            $this->enviar_correos($redactor_actual->id_usuario, $asunto_email, $datos_adicionales);
                        }
    
                        
                    } elseif ($persona['rol'] === 'Inspector') {
                        $existingRecord = GrupoVisitaInspeccion::where('id_informe', $validatedData['id'])
                                                               ->where('id_usuario', $persona['usuario'])
                                                               ->where('rol', $persona['rol'])
                                                               ->where('estado', 'ACTIVO')
                                                               ->first();
    
                        if (!$existingRecord) {

                            $usuario_nuevo = User::where('id', $persona['usuario'])
                                                    ->first();

                            $usuarios_actualizados[] = "Se ingreso a el usuario ".$usuario_nuevo->name. " con el rol ". $persona['rol'];

                            $grupo_visita_inspeccion = new GrupoVisitaInspeccion();
                            $grupo_visita_inspeccion->id_informe = $validatedData['id'];
                            $grupo_visita_inspeccion->id_usuario = $persona['usuario'];
                            $grupo_visita_inspeccion->rol = $persona['rol'];
                            $grupo_visita_inspeccion->estado = 'ACTIVO';
                            $grupo_visita_inspeccion->usuario_creacion = Auth::id();
                            $grupo_visita_inspeccion->save();
                        }
    
                        $asunto_email = 'Asignación ' . $persona['rol'] . ' visita de inspección ' . $validatedData['numero_informe'];
                        $datos_adicionales = [
                            'numero_informe' => 'Ha sido asignado como la persona con el rol de ' . $persona['rol'] . ' para la visita de inspección ' . $validatedData['numero_informe'],
                            'mensaje' => 'Usted ha sido la persona seleccionada con el rol de ' . $persona['rol'] . ' para la visita de inspección identificada con el número ' . $validatedData['numero_informe']. ' con la siguiente observación: ' . $validatedData['observaciones']
                        ];
    
                        $this->enviar_correos($persona['usuario'], $asunto_email, $datos_adicionales);
                    }
                }
    
                $usuarios_a_mantener = collect($grupo_visita_inspeccion)->pluck('usuario')->toArray();
    
                $usuarios_a_eliminar = GrupoVisitaInspeccion::where('id_informe', $validatedData['id'])
                                                            ->whereNotIn('id_usuario', $usuarios_a_mantener)
                                                            ->where('estado', 'ACTIVO')
                                                            ->where('rol', '!=', 'Designado para traslado')
                                                            ->get();
    
                foreach ($usuarios_a_eliminar as $usuario) {

                    $usuario_eliminado = User::where('id', $usuario->id_usuario)
                                                    ->first();

                    $usuarios_eliminados[] = "Se elimino el usuario ".$usuario_eliminado->name." con el rol ".$usuario->rol;

                    $usuario->estado = 'INACTIVO';
                    $usuario->save();

                    $asunto_email = 'Eliminación del grupo de inspección con el rol de ' . $persona['rol'] . ' visita de inspección ' . $validatedData['numero_informe'];
                    $datos_adicionales = [
                        'numero_informe' => 'Ha sido eliminado como la persona con el rol de ' . $persona['rol'] . ' para la visita de inspección ' . $validatedData['numero_informe'],
                        'mensaje' => 'Usted ha sido eliminado como la persona con el rol de ' . $persona['rol'] . ' para la visita de inspección identificada con el número ' . $validatedData['numero_informe']. ' con la siguiente observación: ' . $validatedData['observaciones']
                    ];
    
                    $this->enviar_correos($persona['usuario'], $asunto_email, $datos_adicionales);
                }

                $resultado_final = "";

                if (!empty($usuarios_actualizados)) {
                    foreach ($usuarios_actualizados as $usuario) {
                        $resultado_final .= $usuario . ",";
                    }
                }
                
                if (!empty($usuarios_eliminados)) {
                    foreach ($usuarios_eliminados as $usuario) {
                        $resultado_final .= $usuario . ",";
                    }
                }

                if (!empty($validatedData['numero_informe'])) {
                    $resultado_final .= ", Observaciones: " . $validatedData['observaciones'];
                }
                
                $this->historialInformes($validatedData['id'], 'MODIFICACIÓN DEL GRUPO DE INSPECCIÓN', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $resultado_final, $validatedData['estado_etapa'], '', null, null, '', null);
    
                $successMessage = 'Modificación del grupo de inspección realizada correctamente';
    
                return response()->json([
                    'message' => $successMessage,
                ]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }    

    public function contenidos_finales_expedientes(Request $request){

        try {
            $proxima_etapa = '';
            $usuarios = [];

            $validatedData = $request->validate([
                'id' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'estado_etapa' => 'required',
                'numero_informe' => 'required',
                'razon_social' => 'required',
                'nit' => 'required',
                'ciclos_vida' => 'required',
                'documentos_finales' => 'required',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                $proxima_etapa = 'EN DILIGENCIAMIENTO DEL TABLERO DE CONTROL';

                $catidad_dias_etapa = Parametro::select('dias')
                        ->where('estado', $proxima_etapa)
                        ->first();

                if ($catidad_dias_etapa->dias > 0) {
                    $estado_etapa = 'VIGENTE';
                }else{
                    $estado_etapa = $validatedData['estado_etapa'];
                }

                $asunto_email = 'Diligenciamiento de tablero de control de la visita '.$validatedData['numero_informe'];
                $datos_adicionales = ['numero_informe' => 'Diligenciar el tablero de control de la visita '. $validatedData['numero_informe'],
                                                'mensaje' => 'El tablero de control de la visita '. $validatedData['numero_informe'] . ' a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                $validatedData['nit'] . ' ya se encuentra disponible para ser descargado y diligenciado.' ];

                $usuario_lider_visita = GrupoVisitaInspeccion::where('id_informe', $validatedData['id'])
                                            ->where('rol', 'Lider de visita')
                                            ->where('estado', 'ACTIVO')
                                            ->first();

                $usuario_lider_visita = User::where('id', $usuario_lider_visita->id_usuario)
                                            ->first();

                $this->enviar_correos($usuario_lider_visita->id, $asunto_email, $datos_adicionales);   
                    
                $usuarios = [['id' => $usuario_lider_visita->id, 'nombre' => $usuario_lider_visita->name]];

                $this->historialInformes($validatedData['id'], 'CARGUE DE CONTENIDOS FINALES DEL EXPEDIENTE', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), '', $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                $successMessage = 'Se registraron los contenidos finales correctamente';

                $usuariosSinDuplicados = collect($usuarios)->unique('id');

                $visita_inspeccion->etapa = $proxima_etapa;
                $visita_inspeccion->estado_etapa = $estado_etapa;
                $visita_inspeccion->usuario_actual = json_encode($usuariosSinDuplicados);
                $visita_inspeccion->ciclo_vida_contenidos_finales = $validatedData['ciclos_vida'];
                $visita_inspeccion->documentos_contenidos_finales = $validatedData['documentos_finales'];
                $visita_inspeccion->save();


                $this->conteoDias($visita_inspeccion->id, $proxima_etapa, date('Y-m-d'), NULL);
                $this->actualizarConteoDias($visita_inspeccion->id, $validatedData['etapa'], date('Y-m-d'));

                return response()->json(['message' => $successMessage]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }

    public function generar_tablero(Request $request)
    {

        $validatedData = $request->validate([
            'id' => 'required',
            'etapa' => 'required',
            'estado' => 'required',
            'estado_etapa' => 'required',
            'numero_informe' => 'required',
            'razon_social' => 'required',
            'nit' => 'required',
        ]);

        $templatePath = public_path('templates/FTSUPE058TablerodecontrolvisistasdeinspeccinV1.xlsx');

        $spreadsheet = IOFactory::load($templatePath);


        $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso', 'entidad', 'conteoDias', 'conteoDias', 'historiales', 
                                                            'grupoInspeccion')
                                                    ->first();

            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setCellValue('B7', '1');
            $sheet->setCellValue('C7', '');
            $sheet->setCellValue('D7', $visita_inspeccion->entidad->codigo_entidad);
            $sheet->setCellValue('E7', $visita_inspeccion->entidad->nivel_supervision);
            $sheet->setCellValue('F7', $visita_inspeccion->entidad->tipo_organizacion);
            $sheet->setCellValue('G7', $visita_inspeccion->entidad->categoria);
            $sheet->setCellValue('H7', $visita_inspeccion->entidad->grupo_niif);
            $sheet->setCellValue('I7', $visita_inspeccion->entidad->nit);
            $sheet->setCellValue('J7', '');
            $sheet->setCellValue('K7', strtoupper($visita_inspeccion->entidad->incluye_sarlaft));
            $sheet->setCellValue('L7', $visita_inspeccion->numero_informe);
            $sheet->setCellValue('M7', $visita_inspeccion->entidad->razon_social);
            $sheet->setCellValue('N7', $visita_inspeccion->entidad->sigla);
            $sheet->setCellValue('O7', $visita_inspeccion->entidad->naturaleza_organizacion);
            $sheet->setCellValue('P7', $visita_inspeccion->entidad->ciudad_municipio);
            $sheet->setCellValue('Q7', $visita_inspeccion->entidad->departamento);
            $sheet->setCellValue('R7', $visita_inspeccion->entidad->total_activos);
            $sheet->setCellValue('S7', '');
            $sheet->setCellValue('T7', '');
            $sheet->setCellValue('U7', '');
            $sheet->setCellValue('V7', '');
            $sheet->setCellValue('W7', '');
            $sheet->setCellValue('X7', $visita_inspeccion->fecha_inicio_visita);
            $sheet->setCellValue('Y7', $visita_inspeccion->fecha_fin_visita);
            $sheet->setCellValue('Z7', '');
            $sheet->setCellValue('AA7', '=IF(X7=0,"Pendiente de Programación",IF(Y7=0,"Desarrollo de la Visita",IF(Z7=0,"Informe Pendiente","Informe Entregado")))');
            $sheet->setCellValue('AB7', '');
            $sheet->setCellValue('AC7', '');
            $sheet->setCellValue('AS7', 'Respuesta Informe no Recibida');

        foreach ($visita_inspeccion->conteoDias as $conteoDia) {
            if ($conteoDia->etapa === 'EN DESARROLLO DE VISITA DE INSPECCIÓN') {
                $sheet->setCellValue('AB7', $conteoDia->conteo_dias);
                $sheet->setCellValue('AC7', max(0, $conteoDia->conteo_dias - $conteoDia->dias_habiles) );
            }
            if ($conteoDia->etapa === 'EN REVISIÓN DEL INFORME FINAL INTENDENTE') {
                $sheet->setCellValue('AD7', $conteoDia->fecha_inicio);
                $sheet->setCellValue('AF7', $conteoDia->conteo_dias);
                $sheet->setCellValue('AG7', max(0, $conteoDia->conteo_dias - $conteoDia->dias_habiles) );
            }

            $sheet->setCellValue('AE7', '=IF(X7'.'=0,"Pendiente de Programación",IF(AD7'.'=0,"Informe Pendiente a Intendencia",IF(AA7'.'="Informe Entregado","Informe Entregado a Intendencia","Informe Pendiente a Intendencia")))');

            if ($conteoDia->etapa === 'EN PROYECCIÓN DEL OFICIO DE TRASLADO DEL INFORME') {
                $sheet->setCellValue('AH7', $conteoDia->fecha_inicio);
                $sheet->setCellValue('AN7', $conteoDia->conteo_dias);
                $sheet->setCellValue('AO7', max(0, $conteoDia->conteo_dias - $conteoDia->dias_habiles) );
            }

            $sheet->setCellValue('AM7', '=IF(X7=0,"Pendiente de Programación",IF(AE7="Informe Pendiente a Intendencia","Informe No Finalizado",IF(AH7=0,"Informe No Finalizado",IF(AND(AH7=0,AK7=0),"Informe No Finalizado",IF(AND(AH7<>"0",AK7=0),"Informe Finalizado Pendiente de Remisión a Entidad","Informe Remitido a Entidad")))))');

            if ($conteoDia->etapa === 'EN VALORACIÓN DE LA INFORMACIÓN REMITIDA POR LA ORGANIZACIÓN SOLIDARIA') {
                $sheet->setCellValue('AP7', $conteoDia->fecha_limite_etapa);
                $sheet->setCellValue('AQ7', $conteoDia->fecha_fin);
                $sheet->setCellValue('AH7', $conteoDia->fecha_inicio);
                $sheet->setCellValue('AT7', $conteoDia->conteo_dias);
                $sheet->setCellValue('AU7', max(0, $conteoDia->conteo_dias - $conteoDia->dias_habiles) );
            }

            if ($conteoDia->etapa === 'EN VALORACIÓN DE LA INFORMACIÓN REMITIDA POR LA ORGANIZACIÓN SOLIDARIA'){
                $sheet->setCellValue('AP7', $conteoDia->fecha_limite_etapa);
                $sheet->setCellValue('AQ7', $conteoDia->fecha_fin);
                $sheet->setCellValue('AT7', $conteoDia->conteo_dias);
                $sheet->setCellValue('AU7', max(0, $conteoDia->conteo_dias - $conteoDia->dias_habiles));
                if ($conteoDia->fecha_fin < $conteoDia->fecha_limite_etapa) {
                    $sheet->setCellValue('AV7', 'SI');
                }else{
                    $sheet->setCellValue('AV7', 'NO');
                }
            }

            $sheet->setCellValue('AS7', '=IF(X7'.'=0,"Pendiente de Programación",IF(AQ7'.'=0,"Respuesta Informe No Recibida",IF(AM7'.'="Informe Remitido a Entidad","Respuesta Informe Recibida","Respuesta Informe No Recibida")))');
            
        }

        foreach ($visita_inspeccion->historiales as $historial) {
            if ($historial->etapa === 'EN CONSOLIDACIÓN DE HALLAZGOS') {
                $sheet->setCellValue('Z7', $historial->fecha_creacion);
            }

            if ($historial->etapa === 'EN PROYECCIÓN DEL OFICIO DE TRASLADO DEL INFORME') {
                $sheet->setCellValue('AI7', $historial->usuario_asignado);
                $sheet->setCellValue('AJ7', $historial->fecha_creacion);
            }

            if ($historial->etapa === 'EN ESPERA DE PRONUNCIAMIENTO DE LA ORGANIZACIÓN SOLIDARIA') {
                $sheet->setCellValue('AR7', $historial->usuario_asignado);
                if ($historial->usuario_asignado !== '') {
                    $sheet->setCellValue('AW7', 'SI');
                }else {
                    $sheet->setCellValue('AW7', 'NO');
                }
            }
            
        }
        

        $outputPath = storage_path('app/public/nuevo_archivo.xlsx');
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($outputPath);

        if($validatedData['etapa'] === 'EN DILIGENCIAMIENTO DEL TABLERO DE CONTROL'){
            $visita_inspeccion->estado_etapa = 'FINALIZADO';
            $visita_inspeccion->estado_informe = 'FINALIZADO';
            $visita_inspeccion->etapa = 'FINALIZADO';
            $visita_inspeccion->fecha_fin_gestion = date('Y-m-d');
            $visita_inspeccion->save();

            $this->actualizarConteoDias($visita_inspeccion->id, $validatedData['etapa'], date('Y-m-d'));
        }

        return response()->download($outputPath, 'tablero.xlsx', [], 'inline');
    }

    public function generar_tablero_masivo(Request $request)
    {
        $templatePath = public_path('templates/FTSUPE058TablerodecontrolvisistasdeinspeccinV1.xlsx');
        $spreadsheet = IOFactory::load($templatePath);


        $informes = VisitaInspeccion::with('etapaProceso', 'entidad', 'conteoDias', 'historiales', 'grupoInspeccion');

        if ($request->filled('numero_informe')) {
            $informes->where('numero_informe', 'like', '%' . $request->numero_informe . '%');
        }

        if ($request->filled('estado_etapa')) {
            $informes->where('estado_etapa', 'like', '%' . $request->estado_etapa . '%');
        }

        if ($request->filled('usuario_actual')) {
            $informes->whereRaw("JSON_CONTAINS(usuario_actual, '{\"id\": " . $request->usuario_actual . "}')");
        }        

        if ($request->filled('nombre_entidad')) {
            $informes->whereHas('entidad', function ($query) use ($request) {
                $query->where('razon_social', 'like', '%' . $request->nombre_entidad . '%');
            });
        }

        if ($request->filled('nit_entidad')) {
            $informes->whereHas('entidad', function ($query) use ($request) {
                $query->where('nit', 'like', '%' . $request->nit_entidad . '%');
            });
        }

        if ($request->filled('estado_informe')) {
            $informes->where('estado_informe', 'like', '%' . $request->estado_informe . '%');
        }

        if ($request->filled('etapa_actual')) {
            $informes->where('etapa', 'like', '%' . $request->etapa_actual . '%');
        }

        if ($request->filled('fecha_inicial') && $request->filled('fecha_final')) {
            $fechaInicioDesde = $request->fecha_inicial;
            $fechaInicioHasta = $request->fecha_final;
            $informes->whereBetween('fecha_inicio_gestion', [$fechaInicioDesde, $fechaInicioHasta]);
        }

        if ($request->filled('fecha_modificacion_desde') && $request->filled('fecha_modificacion_hasta')) {
            $fechaModificacionDesde = $request->fecha_inicial;
            $fechaModificacionHasta = $request->fecha_final;
            $informes->whereBetween('fecha_fin_gestion', [$fechaModificacionDesde, $fechaModificacionHasta]);
        }

        if (!$request->filled(['numero_informe', 'estado_etapa', 'estado_informe', 'usuario_actual', 'nombre_entidad', 'etapa_actual', 'nit_entidad'])) {
            $informes->get();
        }

        $visita_inspeccion = $informes->get();

        $key = 7;
        $llave = 1;

        foreach ($visita_inspeccion as $visita_inspeccion) {

            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setCellValue('B'.$key, $llave);
            $sheet->setCellValue('C'.$key, '');
            $sheet->setCellValue('D'.$key, $visita_inspeccion->entidad->codigo_entidad);
            $sheet->setCellValue('E'.$key, $visita_inspeccion->entidad->nivel_supervision);
            $sheet->setCellValue('F'.$key, $visita_inspeccion->entidad->tipo_organizacion);
            $sheet->setCellValue('G'.$key, $visita_inspeccion->entidad->categoria);
            $sheet->setCellValue('H'.$key, $visita_inspeccion->entidad->grupo_niif);
            $sheet->setCellValue('I'.$key, $visita_inspeccion->entidad->nit);
            $sheet->setCellValue('J'.$key, '');
            $sheet->setCellValue('K'.$key, strtoupper($visita_inspeccion->entidad->incluye_sarlaft));
            $sheet->setCellValue('L'.$key, $visita_inspeccion->numero_informe);
            $sheet->setCellValue('M'.$key, $visita_inspeccion->entidad->razon_social);
            $sheet->setCellValue('N'.$key, $visita_inspeccion->entidad->sigla);
            $sheet->setCellValue('O'.$key, $visita_inspeccion->entidad->naturaleza_organizacion);
            $sheet->setCellValue('P'.$key, $visita_inspeccion->entidad->ciudad_municipio);
            $sheet->setCellValue('Q'.$key, $visita_inspeccion->entidad->departamento);
            $sheet->setCellValue('R'.$key, $visita_inspeccion->entidad->total_activos);
            $sheet->setCellValue('S'.$key, '');
            $sheet->setCellValue('T'.$key, '');
            $sheet->setCellValue('U'.$key, '');
            $sheet->setCellValue('V'.$key, '');
            $sheet->setCellValue('W'.$key, '');
            $sheet->setCellValue('X'.$key, $visita_inspeccion->fecha_inicio_visita);
            $sheet->setCellValue('Y'.$key, $visita_inspeccion->fecha_fin_visita);
            $sheet->setCellValue('Z'.$key, '');
            $sheet->setCellValue('AA'.$key, '=IF(X'.$key.'=0,"Pendiente de Programación",IF(Y'.$key.'=0,"Desarrollo de la Visita",IF(Z'.$key.'=0,"Informe Pendiente","Informe Entregado")))');
            $sheet->setCellValue('AB'.$key, '');
            $sheet->setCellValue('AC'.$key, '');

            $innerKey = $key;

            foreach ($visita_inspeccion->conteoDias as $conteoDia) {

                if ($conteoDia->etapa === 'EN DESARROLLO DE VISITA DE INSPECCIÓN') {
                    $sheet->setCellValue('AB'.$innerKey, $conteoDia->conteo_dias);
                    $sheet->setCellValue('AC'.$innerKey, max(0, $conteoDia->conteo_dias - $conteoDia->dias_habiles) );
                }
                if ($conteoDia->etapa === 'EN REVISIÓN DEL INFORME FINAL INTENDENTE') {
                    $sheet->setCellValue('AD'.$innerKey, $conteoDia->fecha_inicio);
                    $sheet->setCellValue('AF'.$innerKey, $conteoDia->conteo_dias);
                    $sheet->setCellValue('AG'.$innerKey, max(0, $conteoDia->conteo_dias - $conteoDia->dias_habiles) );
                }

                $sheet->setCellValue('AE'.$innerKey, '=IF(X'.$innerKey.'=0,"Pendiente de Programación",IF(AD'.$innerKey.'=0,"Informe Pendiente a Intendencia",IF(AA'.$innerKey.'="Informe Entregado","Informe Entregado a Intendencia","Informe Pendiente a Intendencia")))');


                if ($conteoDia->etapa === 'EN PROYECCIÓN DEL OFICIO DE TRASLADO DEL INFORME') {
                    $sheet->setCellValue('AH'.$innerKey, $conteoDia->fecha_inicio);
                    $sheet->setCellValue('AN'.$innerKey, $conteoDia->conteo_dias);
                    $sheet->setCellValue('AO'.$innerKey, max(0, $conteoDia->conteo_dias - $conteoDia->dias_habiles) );
                }

                $sheet->setCellValue('AM'.$innerKey, '=IF(X'.$innerKey.'=0,"Pendiente de Programación",IF(AE'.$innerKey.'="Informe Pendiente a Intendencia","Informe No Finalizado",IF(AH'.$innerKey.'=0,"Informe No Finalizado",IF(AND(AH'.$innerKey.'=0,AK'.$innerKey.'=0),"Informe No Finalizado",IF(AND(AH'.$innerKey.'<>"0",AK'.$innerKey.'=0),"Informe Finalizado Pendiente de Remisión a Entidad","Informe Remitido a Entidad")))))');

                if ($conteoDia->etapa === 'EN VALORACIÓN DE LA INFORMACIÓN REMITIDA POR LA ORGANIZACIÓN SOLIDARIA') {
                    $sheet->setCellValue('AP'.$innerKey, $conteoDia->fecha_limite_etapa);
                    $sheet->setCellValue('AQ'.$innerKey, $conteoDia->fecha_fin);
                    $sheet->setCellValue('AH'.$innerKey, $conteoDia->fecha_inicio);
                    $sheet->setCellValue('AT'.$innerKey, $conteoDia->conteo_dias);
                    $sheet->setCellValue('AU'.$innerKey, max(0, $conteoDia->conteo_dias - $conteoDia->dias_habiles) );
                }

                if ($conteoDia->etapa === 'EN VALORACIÓN DE LA INFORMACIÓN REMITIDA POR LA ORGANIZACIÓN SOLIDARIA'){
                    $sheet->setCellValue('AP'.$innerKey, $conteoDia->fecha_limite_etapa);
                    $sheet->setCellValue('AQ'.$innerKey, $conteoDia->fecha_fin);
                    $sheet->setCellValue('AT'.$innerKey, $conteoDia->conteo_dias);
                    $sheet->setCellValue('AU'.$innerKey, max(0, $conteoDia->conteo_dias - $conteoDia->dias_habiles));
                    if ($conteoDia->fecha_fin < $conteoDia->fecha_limite_etapa) {
                        $sheet->setCellValue('AV'.$innerKey, 'SI');
                    }else{
                        $sheet->setCellValue('AV'.$innerKey, 'NO');
                    }
                }

                $sheet->setCellValue('AS'.$innerKey, '=IF(X'.$innerKey.'=0,"Pendiente de Programación",IF(AQ'.$innerKey.'=0,"Respuesta Informe No Recibida",IF(AM'.$innerKey.'="Informe Remitido a Entidad","Respuesta Informe Recibida","Respuesta Informe No Recibida")))');
                
            }

            foreach ($visita_inspeccion->historiales as $historial) {
                if ($historial->etapa === 'EN CONSOLIDACIÓN DE HALLAZGOS') {
                    $sheet->setCellValue('Z'.$innerKey, $historial->fecha_creacion);
                }
    
                if ($historial->etapa === 'EN PROYECCIÓN DEL OFICIO DE TRASLADO DEL INFORME') {
                    $sheet->setCellValue('AI'.$innerKey, $historial->usuario_asignado);
                    $sheet->setCellValue('AJ'.$innerKey, $historial->fecha_creacion);
                }
    
                if ($historial->etapa === 'EN ESPERA DE PRONUNCIAMIENTO DE LA ORGANIZACIÓN SOLIDARIA') {
                    $sheet->setCellValue('AR'.$innerKey, $historial->usuario_asignado);
                    if ($historial->usuario_asignado !== '') {
                        $sheet->setCellValue('AW'.$innerKey, 'SI');
                    }else {
                        $sheet->setCellValue('AW'.$innerKey, 'NO');
                    }
                }
                
            }
            
            $key++;
            $llave++;
        }

        $outputPath = storage_path('app/public/nuevo_archivo.xlsx');
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($outputPath);


        return response()->download($outputPath, 'tablero.xlsx', [], 'inline');
    }

    public function redirectToGoogle()
    {
        $client = new Client();
        $client->setAuthConfig(storage_path('app/credentials/credenciales.json'));
        $client->setRedirectUri(route('google.auth.callback'));
        $client->addScope(Drive::DRIVE);
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        $authUrl = $client->createAuthUrl();

        return redirect()->away($authUrl);
    }

    public function handleGoogleCallback(Request $request)
    {
        $client = new Client();
        $client->setAuthConfig(storage_path('app/credentials/credenciales.json'));
        $client->setRedirectUri(route('google.auth.callback'));
        $client->addScope(Drive::DRIVE);
        $client->setAccessType('offline');

        $token = $client->fetchAccessTokenWithAuthCode($request->input('code'));
        $request->session()->put('google_token', $token);

        return redirect('/'); 
    }

    public function suspender_visita(Request $request){

        try {
            $proxima_etapa = '';

            $validatedData = $request->validate([
                'id' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'estado_etapa' => 'required',
                'numero_informe' => 'required',
                'razon_social' => 'required',
                'nit' => 'required',
                'observaciones' => 'required',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                $proxima_etapa = 'SUSPENDIDO';
                $estado_etapa = 'SUSPENDIDO';

                $asunto_email = 'Suspención de la visita control de la visita '.$validatedData['numero_informe'];
                $datos_adicionales = ['numero_informe' => 'Suspención de la visita control de la visita '.$validatedData['numero_informe'],
                                                'mensaje' => 'La visita '. $validatedData['numero_informe'] . ' a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                $validatedData['nit'] . ' fue suspendida por '. Auth::user()->name . ' Por el siguiente motivo: '. $validatedData['observaciones'] ];

                $grupo_inspeccion = GrupoVisitaInspeccion::where('id_informe', $validatedData['id'])
                                            ->where('estado', 'ACTIVO')
                                            ->get();

                foreach ($grupo_inspeccion as $usuario) {
                    $persona = User::where('id', $usuario->id_usuario)
                                            ->first();

                    $this->enviar_correos($persona->id, $asunto_email, $datos_adicionales); 
                }

                $usuarios_notificacion = User::wherein('profile', ['Coordinador', 'Delegado']);

                foreach ($usuarios_notificacion as $usuario) {
                    $persona = User::where('id', $usuario->id_usuario)
                                            ->first();

                    $this->enviar_correos($persona->id, $asunto_email, $datos_adicionales); 
                }

                $this->enviar_correos($visita_inspeccion->usuario_creacion, $asunto_email, $datos_adicionales);

                $this->historialInformes($validatedData['id'], 'SUSPENCIÓN', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $validatedData['observaciones'], $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);


                $successMessage = 'Se suspendio la visita de inspección correctamente';

                $visita_inspeccion->etapa = $proxima_etapa;
                $visita_inspeccion->estado_etapa = $estado_etapa;
                $visita_inspeccion->estado_informe = 'SUSPENDIDO';
                $visita_inspeccion->save();

                $this->conteoDias($visita_inspeccion->id, $proxima_etapa, date('Y-m-d'), NULL);
                $this->actualizarConteoDias($visita_inspeccion->id, $validatedData['etapa'], date('Y-m-d'));

                return response()->json(['message' => $successMessage]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }

    public function cambiar_entidad(Request $request) {
        try {
            $validatedData = $request->validate([
                'motivo' => 'required',
                'entidad' => 'required',
                'informe' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'estado_etapa' => 'required',
                'razon_social' => 'required',
                'nit' => 'required',
                'numero_informe' => 'required',
            ]);
    
            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['informe'])
                                                 ->with('etapaProceso')
                                                 ->with('entidad')
                                                 ->first();
    
                $entidad_antigua = Entidad::where('id', $visita_inspeccion->id_entidad)
                                            ->first();

                $entidad_nueva = Entidad::where('id', $validatedData['entidad'])
                                            ->first();

                $observacion = "";

                

                $observacion .="Se actauliza la entidad ".$entidad_antigua->razon_social." por la entidad ".$entidad_nueva->razon_social." con el siguiente motivo: ".$validatedData['motivo'];

                $visita_inspeccion->id_entidad = $validatedData['entidad'];
                $visita_inspeccion->save();

                $grupo_inspeccion = GrupoVisitaInspeccion::where('id_informe', $validatedData['informe'])
                                                            ->where('estado', 'ACTIVO')
                                                            ->get();

                $asunto_email = 'Cambio de entidad para la  visita de inspección ' . $validatedData['numero_informe'];
                $datos_adicionales = [
                    'numero_informe' => 'Se cambio la entidad para la visita de inspección ' . $validatedData['numero_informe'],
                    'mensaje' => 'Se cambio la entidad para la visita de inspección identificada con el número ' . $validatedData['numero_informe'] . 
                    ' anteriormente la se encontraba la entidad '.$entidad_antigua->razon_social. ' y se cambio por la entidad '.$entidad_nueva->razon_social.
                    ' con el siguiente motivo: '.$validatedData['motivo']
                ];

                $this->enviar_correos($visita_inspeccion->usuario_diagnostico, $asunto_email, $datos_adicionales);

                foreach ($grupo_inspeccion as $usuario) {
                    $this->enviar_correos($usuario->id_usuario, $asunto_email, $datos_adicionales);
                }

                $usuarios_adicionales = User::whereIn('profile', ['Coordinador', 'Delegado'])
                                                ->get();

                foreach ($usuarios_adicionales as $usuario) {
                    $this->enviar_correos($usuario->id_usuario, $asunto_email, $datos_adicionales);
                }

                $this->historialInformes($validatedData['informe'], 'CAMBIO DE ENTIDAD A VISITAR', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $observacion, $validatedData['estado_etapa'], '', null, null, '', null);
    
                $successMessage = 'Modificación del grupo de inspección realizada correctamente';
    
                return response()->json([
                    'message' => $successMessage,
                ]);
                
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    } 

    public function eliminar_archivo(Request $request) {
        try {
            $validatedData = $request->validate([
                'id' => 'required',
                'numero_informe' => 'required',
                'razon_social' => 'required',
                'nombre_archivo' => 'required',
                'id_archivo' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'estado_etapa' => 'required',
            ]);
    
            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                 ->with('etapaProceso')
                                                 ->with('entidad')
                                                 ->first();

                $observacion = "";
                $observacion .="Se elimina el archivo ".$validatedData['nombre_archivo'];

                $this->historialInformes(
                    $validatedData['id'], 
                    'ELIMINACIÓN DE ANEXO PLAN DE VISITA', 
                    $validatedData['etapa'], 
                    $validatedData['estado'], 
                    date('Y-m-d'), 
                    $observacion, 
                    $validatedData['estado_etapa'], 
                    '', 
                    null, 
                    null, 
                    '', 
                    null
                );

                $array_anexos = json_decode($visita_inspeccion->anexos_adicionales_plan_visita); 

                foreach($array_anexos as $key => $anexo){

                    $ruta = $anexo->fileUrl;
                    $buscar = $validatedData['id_archivo'];

                    if( strpos($ruta, $buscar) ){
                        $accessToken = auth()->user()->google_token;

                        $response = Http::withToken($accessToken)
                                ->delete("https://www.googleapis.com/drive/v3/files/{$validatedData['id_archivo']}");

                        if (!$response->successful()) {
                            return response()->json(['error' => $response->json()['error']['message']], 500);
                        }else{
                            unset($array_anexos[$key]);
                            $array_anexos = array_values($array_anexos);
                            break;
                        }
                    }
                }

                $visita_inspeccion->anexos_adicionales_plan_visita = json_encode($array_anexos) ;
                $visita_inspeccion->save();

                Log::info('Antes de llamar a historialInformes', [
                    'numero_informe' => $validatedData['numero_informe'],
                    'etapa' => $validatedData['etapa'],
                    'estado' => $validatedData['estado'],
                    'estado_etapa' => $validatedData['estado_etapa'],
                    'observacion' => $observacion,
                ]);
    
                $successMessage = "Se eliminó el anexo {$validatedData['nombre_archivo']} correctamente";
    
                return response()->json([
                    'message' => $successMessage,
                ]);
                
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    } 

    public function guardar_plan_visita_modificado(Request $request) {
        try {

            $validatedData = $request->validate([
                'id' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'estado_etapa' => 'required',
                'numero_informe' => 'required',
                'razon_social' => 'required',
                'nit' => 'required',
                'observacion' => 'sometimes|string',
                'tipo_visita_modificada' => 'required',
                'enlace_plan_visita' => 'sometimes|file|max:6000|mimes:pdf,doc,docx,xls,xlsx',
                'anexo_plan_visita_modificado.*' => 'sometimes|file|max:6000|mimes:pdf,doc,docx,xls,xlsx',
                'nombre_anexo_plan_visita_modificado.*' => 'sometimes|string',  
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                $banderaAnexos = false;
                $banderaPlanDeVisita = false;
                $anexos_adicionales_plan_visita = [];
                $folderId = $visita_inspeccion->carpeta_drive;
                $accessToken = auth()->user()->google_token;

                $observacion_enlace_plan_visita = '';

                if ($request->hasFile('anexo_plan_visita_modificado')) {
                    $uploadedFiles = $request->file('anexo_plan_visita_modificado');
                    $fileNames = $request->input('nombre_anexo_plan_visita_modificado');
                    $banderaAnexos = true;
                }

                if ($request->hasFile('enlace_plan_visita')) {
                    $banderaPlanDeVisita = true;
                    $observacion_enlace_plan_visita = ', el plan se encuentra en el enlace '. $validatedData['enlace_plan_visita'];
                }

                if($banderaPlanDeVisita){
    
                    $uniqueCode = Str::random(8);
                    $fecha = date('Ymd');
                    $nameFormat = str_replace(' ', '_', $validatedData['enlace_plan_visita']->getClientOriginalName());
    
                    $newFileName = "{$fecha}_{$uniqueCode}_{$nameFormat}";
    
                    $filePath = $request->file('enlace_plan_visita')->getRealPath();
                    $fileName = $newFileName;
    
                    $metadata = [
                        'name' =>  $fileName,
                        'parents' => [$folderId],
                    ];
    
                    $response = Http::withToken($accessToken)
                            ->attach(
                                'data',
                                json_encode($metadata),
                                'metadata.json'
                            )
                            ->attach(
                                'file',
                                file_get_contents($filePath),
                                $fileName
                            )
                            ->post('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart');
    
                    if ($response->successful()) {
                        $file = $response->json();
                        $fileId = $file['id'];
                        $fileUrl = 'https://drive.google.com/file/d/' . $fileId . '/view';
                    } else {
                        return response()->json(['error' => $response->json()['error']['message']], 500);
                    }

                    $visita_inspeccion->plan_visita = $fileUrl;
                }

                if ($banderaAnexos) {
                    $anexos_adicionales_plan_visita = json_decode($visita_inspeccion->anexos_adicionales_plan_visita);
                    foreach ($uploadedFiles as $index =>$newFile) {

                        $uniqueCode = Str::random(8);
                        $fecha = date('Ymd');

                        if($fileNames[$index]){
                            $nameFormat = str_replace(' ', '_', $fileNames[$index]);
                        }else{
                            $nameFormat = str_replace(' ', '_', $newFile->getClientOriginalName());
                        }
    
                        $newFileName = "{$fecha}_{$uniqueCode}_{$nameFormat}";

                        $metadata = [
                            'name' =>  $newFileName,
                            'parents' => [$folderId],
                        ];
                                
                        $responseAnexos = Http::withToken($accessToken)
                            ->attach(
                                'metadata',
                                json_encode($metadata),
                                'metadata.json'
                            )
                            ->attach(
                                'file',
                                file_get_contents($newFile),
                                $newFileName
                            )
                            ->post('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart');
        
                            if ($responseAnexos->successful()) {
                                $file = $responseAnexos->json();
                                $fileId = $file['id'];
                                $fileUrlAnexo = 'https://drive.google.com/file/d/' . $fileId . '/view';

                                $anexos_adicionales_plan_visita[] = ["fileName" => $fileNames[$index], "fileUrl" =>  $fileUrlAnexo];
                        
                            } else {
                                return response()->json(['error' => $responseAnexos->json()['error']['message']], 500);
                            }
                    }
                    $visita_inspeccion->anexos_adicionales_plan_visita = json_encode($anexos_adicionales_plan_visita);
                }

                $proxima_etapa = 'CONFIRMAR PLAN DE VISITA COORDINACIÓN';

                $catidad_dias_etapa = Parametro::select('dias')
                        ->where('estado', $proxima_etapa)
                        ->first();

                if ($catidad_dias_etapa->dias > 0) {
                    $estado_etapa = 'VIGENTE';
                }else{
                    $estado_etapa = $validatedData['estado_etapa'];
                }

                $usuarios_coordinadores = User::where('profile', 'Coordinador')
                                            ->get();
                
                $usuarios = [];

                $asunto_email = 'Plan de visita '.$validatedData['numero_informe'];
                $datos_adicionales = ['numero_informe' => 'Se ha modificado el plan de visita '. $validatedData['numero_informe'],
                                            'mensaje' => 'Se modifico el plan de la visita para la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                $validatedData['nit'] . ' que se ejecutara de manera '. $validatedData['tipo_visita_modificada'] . $observacion_enlace_plan_visita];

                foreach ($usuarios_coordinadores as $usuario) {
                    $usuarios[] = ['id' => $usuario->id, 'nombre' => $usuario->name];

                    $this->enviar_correos($usuario->id, $asunto_email, $datos_adicionales);
                }

                $usuariosSinDuplicados = collect($usuarios)->unique('id');

                $visita_inspeccion->etapa = $proxima_etapa;
                $visita_inspeccion->estado_etapa = $estado_etapa;
                
                $visita_inspeccion->tipo_visita = $validatedData['tipo_visita_modificada'];
                $visita_inspeccion->usuario_actual = json_encode($usuariosSinDuplicados);
                
                $visita_inspeccion->save();

                $this->historialInformes($validatedData['id'], 'ACTUALIZACIÓN PLAN DE VISITA', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $validatedData['observacion'], $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                $this->conteoDias($visita_inspeccion->id, 'CONFIRMAR PLAN DE VISITA COORDINACIÓN', date('Y-m-d'), NULL);
                $this->actualizarConteoDias($visita_inspeccion->id, $validatedData['etapa'], date('Y-m-d'));

                $successMessage = 'Plan de visita enviado correctamente para la revisión de la coordinación';

                return response()->json(['message' => $successMessage]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }

}

