<?php

use Carbon\Carbon;
use App\Models\ConteoDias;
use App\Http\Controllers\DiagnosticoController;
use App\Models\Parametro;
use App\Models\VisitaInspeccion; 


        $fecha_actual = now();
        $totalDiasVisita = Parametro::sum('dias');
        $visita_inspeccion = VisitaInspeccion::whereNotIn('etapa', ['CANCELADO', 'FINALIZADO'])
                                                ->with('etapaProceso')
                                                ->with('entidad')
                                                ->get();
    
        if ($visita_inspeccion->count() > 0) {
            foreach ($visita_inspeccion as $visita) {

                $diasFestivosColombia = [
                    '2024-01-01', 
                    '2024-01-06', 
                    '2024-03-25', 
                    '2024-03-28', 
                    '2024-03-29', 
                    '2024-05-01', 
                    '2024-05-13', 
                    '2024-06-03', 
                    '2024-06-10', 
                    '2024-07-01', 
                    '2024-07-20', 
                    '2024-08-07', 
                    '2024-08-19', 
                    '2024-10-14', 
                    '2024-11-04', 
                    '2024-11-11', 
                    '2024-12-08', 
                    '2024-12-25',
                ];

                $fecha_asignacion_grupo_inspeccion = ConteoDias::where('id_informe', $visita->id)
                                                                ->where('etapa', 'ASIGNACIÓN GRUPO DE INSPECCIÓN')
                                                                ->select('fecha_inicio')
                                                                ->first();

                $diagnosticoController = new DiagnosticoController();
        
                $fecha_limite_etapa = $diagnosticoController->sumarDiasHabiles($fecha_asignacion_grupo_inspeccion['fecha_inicio'], $visita->etapaProceso->dias, $diasFestivosColombia, $visita->id);

                if ($fecha_actual > $fecha_limite_etapa) {
                    echo 'Número de visita: '. $visita->numero_informe . ' alerta vencimiento de etapa <br>';

                    $visita_inspeccion = VisitaInspeccion::findOrFail($visita->id);
                    $visita_inspeccion->estado_etapa = 'EN DESTIEMPO';
                    $visita_inspeccion->save();

                    $asunto_email = 'Alerta vencimiento de etapa de visita '.$visita->numero_informe;
                    $datos_adicionales = ['numero_informe' => 'Alerta vencimiento de etapa de visita '.$visita->numero_informe,
                                            'mensaje' => 'La visita número '. $visita->numero_informe. ' a la entidad ' . $visita->entidad->razon_social . ' se encuentra en destiempo en la etapa '. $visita->etapa . ', por favor ingresar a la plataforma y realizar la respectiva gestión.'];
                    $diagnosticoController->enviar_correos( json_decode($visita->usuario_actual) , $asunto_email, $datos_adicionales);
                }

                $diasHabilesTranscurridos = $diagnosticoController->contarDiasHabiles(Carbon::parse($fecha_asignacion_grupo_inspeccion['fecha_inicio']), $fecha_actual, $diasFestivosColombia);

                if ($diasHabilesTranscurridos > $totalDiasVisita) {
                    echo 'Número de visita: '. $visita->numero_informe . ' informe vencido <br>';

                    $visita_inspeccion = VisitaInspeccion::findOrFail($visita->id);
                    $visita_inspeccion->estado_informe = 'EN DESTIEMPO';
                    $visita_inspeccion->save();

                    $asunto_email = 'Alerta vencimiento de visita de inspección '.$visita->numero_informe;
                    $datos_adicionales = ['numero_informe' => 'Alerta vencimiento de visita de inspección '.$visita->numero_informe,
                                            'mensaje' => 'La visita número '. $visita->numero_informe. ' a la entidad ' . $visita->entidad->razon_social . ' se encuentra vencida en la etapa '. $visita->etapa . ', por favor ingresar a la plataforma y realizar la respectiva gestión.'];
                    $diagnosticoController->enviar_correos( $visita->usuario_creacion, $asunto_email, $datos_adicionales);
                }

                $diasHabilesFaltantes = $diagnosticoController->diasHabilesRestantes($fecha_limite_etapa, $diasFestivosColombia);

                if ($diasHabilesFaltantes == 1 || $diasHabilesFaltantes == 2) {

                    $asunto_email = 'Alerta gestión de visita '.$visita->numero_informe;
                    $datos_adicionales = ['numero_informe' => 'Alerta gestión de visita '.$visita->numero_informe,
                                            'mensaje' => 'La visita número '. $visita->numero_informe. ' a la entidad ' . $visita->entidad->razon_social . ' se encuentra a '. $diasHabilesFaltantes . ' días habiles para su vencimiento, por favor ingresar a la plataforma y realizar la respectiva gestión.'];
                    
                    $usuarios = json_decode($visita->usuario_actual);
    
                    foreach ($usuarios as $usuario) {
                        $diagnosticoController->enviar_correos($usuario, $asunto_email, ['numero_informe' => $asunto_email, 'mensaje' => $mensaje]);
                    }
                }
            }

            echo 'cron ejecutado';
        }else {
            echo 'No se escontraron registros';
        }