<?php

namespace App\Http\Controllers;

use App\Mail\CorreosVistasInspeccion;
use App\Models\AnexoRegistro;
use App\Models\AsuntoEspecial;
use App\Models\ConteoDias;
use App\Models\DiaNoLaboral;
use App\Models\HistorialVisitas;
use App\Models\Parametro;
use App\Models\User;  
use App\Models\VisitaInspeccion; 

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

use Carbon\Carbon;

class AsuntoEspecialController extends Controller
{
    public function consultar_entidad_asunto_especial(Request $request) {

        $informes = AsuntoEspecial::query();

        if ($request->filled('estado_etapa')) {
            $informes->where('estado_etapa', 'like', '%' . $request->estado_etapa . '%');
        }

        if ($request->filled('usuario_actual')) {
            $informes->whereRaw("JSON_CONTAINS(usuarios_actuales, '{\"id\": " . $request->usuario_actual . "}')");
        }        

        if ($request->filled('nombre_entidad')) {
            $informes->whereHas('entidad_data', function ($query) use ($request) {
                $query->where('razon_social', 'like', '%' . $request->nombre_entidad . '%');
            });
        }

        if ($request->filled('nit_entidad')) {
            $informes->whereHas('entidad_data', function ($query) use ($request) {
                $query->where('nit', 'like', '%' . $request->nit_entidad . '%');
            });
        }

        if ($request->filled('etapa_actual')) {
            $informes->where('etapa', 'like', '%' . $request->etapa_actual . '%');
        }

        if (!$request->filled(['estado_etapa', 'usuario_actual', 'nombre_entidad', 'etapa_actual', 'nit_entidad'])) {
            $informes->get();
        }

        $usuarios = User::get();
        $informes = $informes->with('entidad_data')->orderby('id', 'desc')->paginate(10);
        $parametros = Parametro::get();

        return view('consultar_entidad_asunto_especial', [
            'usuarios' => $usuarios,
            'ausntoEspeciales' => $informes,
            'parametros' => $parametros
        ]);
    }

    public function asunto_especial($id)
    {
        $informe = AsuntoEspecial::where('id', $id)
                                    ->with('historiales.usuario')
                                    ->with('usuario')
                                    ->with('anexos')
                                    ->first();

        $parametros = Parametro::get();

        $usuarios = User::orderby('name', 'ASC')->get();
        return view('detalle_asunto_especial', [
            'usuariosTotales' => $usuarios,
            'informe' => $informe,
            'parametros' => $parametros
        ]);
    }

    public function guardar_observacion_asunto_especial(Request $request) {
        try {

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

            $asunto_especial = AsuntoEspecial::findOrFail($validatedData['id']);

            if($validatedData['accion'] === 'observacion'){
                $this->historialInformes($validatedData['id'], 'OBSERVACIÓN', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d H:i:s'), $validatedData['observaciones'], $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                $asunto_email = 'Observación a '.$validatedData['numero_informe'];
                $datos_adicionales = ['numero_informe' => 'Se ha registrado una observación para la '. $validatedData['numero_informe'] . ' de la entidad '. $validatedData['razon_social'],
                                        'mensaje' => 'Se registro una observación para la'. $validatedData['numero_informe'] . ' a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                $validatedData['nit'] . ' con la siguiente observación '. $validatedData['observaciones']];

                $successMessage = 'Observación registrada correctamente';
            }else{
                $asunto_especial->etapa = 'CANCELADO';
                $asunto_especial->estado_etapa = 'CANCELADO';
                $asunto_especial->save();

                $this->historialInformes($validatedData['id'], 'CANCELACIÓN', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $validatedData['observaciones'], $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                $asunto_email = $validatedData['numero_informe'].' cancelada ';
                $datos_adicionales = ['numero_informe' => 'Se ha cancelado la '. $validatedData['numero_informe'],
                                        'mensaje' => 'Se canceló la '.$validatedData['numero_informe'].' a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                $validatedData['nit'] . 'Con la siguiente observación: '. $validatedData['observaciones']];

                $successMessage = $validatedData['numero_informe']. ' cancelada correctamente';
            }

            foreach ( json_decode($asunto_especial->usuarios_actuales) as $usuario) {
                $this->enviar_correos($usuario->id, $asunto_email, $datos_adicionales);
            }
            
            return response()->json(['message' => $successMessage]);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }

    public function guardar_documento_adicional_asunto_especial(Request $request){
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

                'anexo_asuntos_especiales.*' => 'nullable|file|max:6000|mimes:pdf,doc,docx,xls,xlsx',
                'nombre_anexo_asuntos_especiales.*' => 'nullable|string',
            ]);

            $visita_inspeccion = AsuntoEspecial::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                if ($request->file('anexo_asuntos_especiales')) {

                    $response_files = $this->cargarArchivosGoogle(
                        $request->file('anexo_asuntos_especiales'), 
                        $request->input('nombre_anexo_asuntos_especiales'), 
                        $visita_inspeccion->carpeta_drive,
                        $visita_inspeccion->entidad,
                        'ASUNTOS_ESPECIALES',
                        'ANEXO_ASUNTOS_ESPECIALES',
                        '',
                        'ANEXO_ASUNTOS_ESPECIALES',
                        $validatedData['id'],
                    );

                    if ($response_files['status'] == 'error') {
                        return response()->json(['error' => $response_files['message']], 500);
                    }
                }

                $asunto_email = 'Registro de anexos al procedimiento '.$validatedData['numero_informe'];
                $datos_adicionales = ['numero_informe' => 'Registro de anexos al procedimiento'. $validatedData['numero_informe']. ' de la entidad '.$validatedData['razon_social'],
                                                    'mensaje' => 'Se registraron anexos al proceso de '. $validatedData['numero_informe'] . ' a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                                        $validatedData['nit']];

                foreach ( json_decode($visita_inspeccion->usuarios_actuales) as $usuario) {
                    $this->enviar_correos($usuario->id, $asunto_email, $datos_adicionales);
                }

                $this->historialInformes($validatedData['id'], 'REGISTRO DE ANEXOS ADICIONALES', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), '', $validatedData['estado_etapa'], NULL, NULL, NULL, '', NULL);

                $successMessage = 'Se registraron los anexos correctamente y se notifico a los usuarios actuales';

                return response()->json(['message' => $successMessage]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }

    public function guardar_memorando_traslado_grupo_asuntos_especiales(Request $request){
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
                'ciclo_vida_memorando_traslado_grupo_asuntos_especiales' => 'required',
                'observaciones' => 'nullable|string',

                'anexo_trasladar_memorando_grupo_asuntos_especiales.*' => 'nullable|file|max:6000|mimes:pdf,doc,docx,xls,xlsx',
                'nombre_anexo_trasladar_memorando_grupo_asuntos_especiales.*' => 'nullable|string',
            ]);

            $asunto_especial = AsuntoEspecial::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($asunto_especial->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                if ($request->file('anexo_trasladar_memorando_grupo_asuntos_especiales')) {

                    $response_files = $this->cargarArchivosGoogle(
                        $request->file('anexo_trasladar_memorando_grupo_asuntos_especiales'), 
                        $request->input('nombre_anexo_trasladar_memorando_grupo_asuntos_especiales'), 
                        $asunto_especial->carpeta_drive,
                        $asunto_especial->entidad,
                        'ASUNTOS_ESPECIALES',
                        'ANEXOS_TRASLADAR_MEMORANDO_GRUPO_ASUNTOS_ESPECIALES',
                        '',
                        'ANEXOS_TRASLADAR_MEMORANDO_GRUPO_ASUNTOS_ESPECIALES',
                        $validatedData['id'],
                    );

                    if ($response_files['status'] == 'error') {
                        return response()->json(['error' => $response_files['message']], 500);
                    }
                }

                $observaciones_email = '';
                if($validatedData['observaciones'] !== NULL || $validatedData['observaciones'] !== ''){
                    $observaciones_email = ' con las siguientes observaciones: '. $validatedData['observaciones'];
                }

                $proxima_etapa = 'EN ANÁLISIS DE LA INFORMACIÓN APORTADA - COORDINACIÓN ASUNTOS ESPECIALES';

                $catidad_dias_etapa = Parametro::select('dias')
                        ->where('estado', $proxima_etapa)
                        ->first();

                if ($catidad_dias_etapa->dias > 0) {
                    $estado_etapa = 'VIGENTE';
                }else{
                    $estado_etapa = $validatedData['estado_etapa'];
                }

                $asunto_email = 'Asignación de memorando de traslado '.$validatedData['numero_informe'];
                $datos_adicionales = ['numero_informe' => 'Asignación de memorando de traslado '. $validatedData['numero_informe'],
                                                    'mensaje' => 'Se realiza el traslado del memorando '. $validatedData['ciclo_vida_memorando_traslado_grupo_asuntos_especiales'] . ' para el proceso de '. $validatedData['numero_informe'] . ' a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                                        $validatedData['nit'].$observaciones_email];
                
                $usuarios_coordinacion = User::where('profile', 'Coordinacion asuntos especiales')
                                ->get();

                if ($usuarios_coordinacion->count() > 0 ) {
                    foreach ($usuarios_coordinacion as $usuario) {
                        $this->enviar_correos($usuario->id, $asunto_email, $datos_adicionales); 
                        $usuarios[] = ['id' => $usuario->id, 'nombre' => $usuario->name];
                    }
                }

                $this->historialInformes($validatedData['id'], 'ENVÍO DE MEMORANDO DE TRASLADO', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $validatedData['observaciones'], $validatedData['estado_etapa'], $validatedData['ciclo_vida_memorando_traslado_grupo_asuntos_especiales'], NULL, NULL, '', NULL);

                $successMessage = 'Se registro el ciclo de vida del memorando de traslado y se notifico a la coordinación de asuntos especiales';

                $usuariosSinDuplicados = collect($usuarios)->unique('id');

                $asunto_especial->etapa = $proxima_etapa;
                $asunto_especial->estado_etapa = $estado_etapa;
                $asunto_especial->usuarios_actuales = json_encode($usuariosSinDuplicados);
                $asunto_especial->memorando_traslado = $validatedData['ciclo_vida_memorando_traslado_grupo_asuntos_especiales'];
                $asunto_especial->save();

                $this->conteoDias($asunto_especial->id, $proxima_etapa, date('Y-m-d'), NULL);
                //$this->actualizarConteoDias($asunto_especial->id, $validatedData['etapa'], date('Y-m-d'));

                return response()->json(['message' => $successMessage]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }



    //Helpers

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
        $historial_informe->proceso= 'ASUNTOS_ESPECIALES';
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

    public function cargarArchivosGoogle($uploadedFiles, $fileNames, $folderId, $id_entidad, $proceso, $sub_proceso, $id_sub_proceso, $tipo_anexo, $id_tipo_anexo ) {
        $accessToken = auth()->user()->google_token;
        $anexos_adicionales = [];
    
        foreach ($uploadedFiles as $index => $newFile) {
            $uniqueCode = Str::random(8);
            $fecha = date('Ymd');
    
            if ($fileNames[$index]) {
                $nameFormat = str_replace(' ', '_', $fileNames[$index]);
            } else {
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

                $archivos = new AnexoRegistro();
                $archivos->nombre = $fileNames[$index];
                $archivos->ruta = $fileUrlAnexo;
                $archivos->id_entidad = $id_entidad;
                $archivos->proceso = $proceso;
                $archivos->sub_proceso = $sub_proceso;
                $archivos->id_sub_proceso = $id_sub_proceso;
                $archivos->tipo_anexo = $tipo_anexo;
                $archivos->id_tipo_anexo = $id_tipo_anexo;
                $archivos->estado = 'ACTIVO';
                $archivos->usuario_creacion = Auth::id();
                $archivos->save();
    
                $anexos_adicionales[] = ["fileName" => $fileNames[$index], "fileUrl" => $fileUrlAnexo];
            } else {
                if (strpos($responseAnexos, 'Expected OAuth 2 access token') !== false) {
                    auth()->logout();
                    return [
                        'status' => 'error',
                        'message' => 'Sesión cerrada finalizada. Por favor, vuelva a iniciar sesión.'
                    ];
                }
                return [
                    'status' => 'error',
                    'message' => $responseAnexos->json()['error']['message']
                ];
            }
        }
    
        return [
            'status' => 'success',
            'data' => $anexos_adicionales
        ];
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
}


