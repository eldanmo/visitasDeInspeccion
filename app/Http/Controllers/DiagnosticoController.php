<?php

namespace App\Http\Controllers;
use App\Models\AsuntoEspecial;
use App\Models\VisitaInspeccion; 
use App\Models\GrupoVisitaInspeccion; 
use App\Models\User;  
use App\Models\HistorialVisitas;
use App\Models\ConteoDias;
use App\Models\DiaNoLaboral;
use App\Models\Entidad;
use App\Models\SolicitudDiaAdicional;
use App\Models\AnexoRegistro;
use Illuminate\Support\Facades\Auth;

use Illuminate\Http\Request;
use App\Models\Parametro;
use Illuminate\Support\Facades\Mail;
use App\Mail\CorreosVistasInspeccion;
use App\Models\HistoricoSolicitudDiaAdicional;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

use PhpOffice\PhpSpreadsheet\IOFactory;

class DiagnosticoController extends Controller
{

    /**
     * Muestra el formulario para crear un diagnóstico.
     *
     * Recupera el número de días configurados para el diagnóstico de intendencia
     * y el nombre del usuario autenticado.
     *
     * @return \Illuminate\View\View Devuelve la vista 'crear_diagnostico' con los días del diagnóstico y el nombre del usuario autenticado.
    */

    public function crear()
    {
        $diasDiagnostico = Parametro::select('dias')->where('estado', 'DIAGNÓSTICO INTENDENCIA')->first();
        $nombreUsuario = Auth::user()->name;

        return view('crear_diagnostico', [
            'dias_diagnostico' => $diasDiagnostico,
            'nombreUsuario' => $nombreUsuario
        ]);
    }

    /**
     * Creación del diagnóstico para una entidad.
     *
     * Valida los datos de entrada y crea un nuevo diagnóstico asociado a una entidad. 
     * Realiza las siguientes acciones:
     *  - Crea el diagnóstico si no existe un informe activo para la entidad.
     *  - Asigna el diagnóstico al usuario creador y lo marca como vigente.
     *  - Actualiza una hoja de cálculo en Google Sheets con los datos del diagnóstico.
     *  - Actualiza el historial de la visita de la entidad.
     *  - Crea el registro para el conteo de días del diagnóstico.
     *  - Envía una notificación por correo electrónico al creador del diagnóstico.
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos del diagnóstico.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el diagnóstico se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

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
            $usuario_intendente = [];

            //Consulta de entidad

            $entidad = Entidad::where('id', $validatedData['id_entidad'])
                                ->first();

            //Busqueda de usuario intendente 

            if($entidad->naturaleza_organizacion === 'FONDO'){
                $usuarios_intendentes = User::where('profile', 'Intendencia de fondos de empleados')
                                            ->get();
            }else {
            $usuarios_intendentes = User::where('profile', 'Intendencia de cooperativas y otras organizaciones solidarias')
                                        ->get();
            }

            //Current usser

            foreach($usuarios_intendentes as $usuario){
                $usuario_intendente[] = ['id' => $usuario->id, 'nombre' => $usuario->name, 'rol' => 'Intendente' ];
            }

            $visita_inspeccion = new VisitaInspeccion();
            $visita_inspeccion->fecha_inicio_diagnostico = $validatedData['fecha_inicio_diagnostico'];
            $visita_inspeccion->id_entidad = $validatedData['id_entidad'];
            $visita_inspeccion->usuario_creacion = $usuarioCreacionId;
            $visita_inspeccion->etapa = 'DIAGNÓSTICO INTENDENCIA';
            $visita_inspeccion->estado_informe = 'VIGENTE';
            $visita_inspeccion->estado_etapa = 'VIGENTE';
            $visita_inspeccion->usuario_diagnostico = $usuarioCreacionId;
            $visita_inspeccion->usuario_actual = json_encode($usuario_intendente);
            $visita_inspeccion->save();

            $visita_inspeccion->numero_informe = $visita_inspeccion->id . $anio_actual;
            $visita_inspeccion->save();

            $entidad = Entidad::where('id', $validatedData['id_entidad'])
                                ->first();

            $create_sheets = $this->create_sheets($visita_inspeccion->id, 
                                $visita_inspeccion->id . $anio_actual,
                                NULL,
                                $validatedData['nit'],
                                NULL,
                                NULL,
                                NULL,
                                NULL,
                                NULL,
                                NULL,
                                NULL,
                                $entidad->numero_asociados,
                                $entidad->total_activos,
                                $entidad->fecha_corte_visita,
                                $entidad->nivel_supervision
                            );

            if($create_sheets['status'] === 'error'){
                return response()->json(['error' => $create_sheets['message']], 500);
            }

            $this->historialInformes($visita_inspeccion->id, 'CREACIÓN', 'DIAGNÓSTICO INTENDENCIA', 'VIGENTE', date('Y-m-d'), '', 'VIGENTE', '', $validatedData['fecha_inicio_diagnostico'], NULL, '', $validatedData['fecha_fin_diagnostico']);
            $this->conteoDias($visita_inspeccion->id, 'DIAGNÓSTICO INTENDENCIA', $validatedData['fecha_inicio_diagnostico'], NULL);

            $asunto_email = 'Creación diagnóstico '.$visita_inspeccion->numero_informe;
            $datos_adicionales = ['numero_informe' => 'Se ha creado el diagnóstico número '.$visita_inspeccion->numero_informe,
                                    'mensaje' => 'Se realizó la creación del diagnóstico de la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                                     $validatedData['nit']];

            //Send email

            foreach($usuarios_intendentes as $usuario){
                $this->enviar_correos($usuario->id, $asunto_email, $datos_adicionales);
            }

            $successMessage = 'Diágnostico creado correctamente';

            return response()->json(['message' => $successMessage]);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }

    }

    /**
     * Envía correos electrónicos a uno o varios usuarios.
     *
     * Este método envía un correo electrónico a un usuario individual o a un grupo de usuarios. 
     * Dependiendo del tipo de parámetro `$usuario`, realiza lo siguiente:
     *  - Si `$usuario` es un entero o una cadena (ID del usuario), envía un correo al usuario correspondiente.
     *  - Si `$usuario` es un arreglo de usuarios, envía un correo a cada uno de los usuarios en el arreglo.
     *
     * @param int|string|array $usuario Puede ser el ID del usuario (int o string) o un arreglo de objetos de usuario.
     * @param string $asunto El asunto del correo electrónico (opcional, por defecto vacío).
     * @param array $datos_adicionales Datos adicionales para ser incluidos en el correo (opcional, por defecto vacío).
     *
     * @return void
    */

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

    /**
     * Registra un historial de informes de visitas de inspección.
     *
     * Este método crea un registro en la tabla de historial de informes asociada a un informe de visita de inspección, 
     * detallando la acción, etapa, estado y otros datos relacionados con el informe.
     * También registra la información en los logs para seguimiento.
     *
     * @param int $id_informe ID del informe al que se asocia el historial.
     * @param string $accion Acción realizada en el informe (ejemplo: 'CREACIÓN', 'ACTUALIZACIÓN').
     * @param string $etapa Etapa del proceso en la que se encuentra el informe.
     * @param string $estado Estado actual del informe (ejemplo: 'VIGENTE', 'FINALIZADO').
     * @param string $fecha_creacion Fecha de creación del historial.
     * @param string|null $observaciones Comentarios u observaciones adicionales (opcional).
     * @param string $estado_etapa Estado de la etapa actual del proceso (ejemplo: 'VIGENTE', 'PENDIENTE').
     * @param string|null $usuario_asignado Usuario asignado para la siguiente etapa o acción (opcional).
     * @param string $fecha_inicio Fecha de inicio de la etapa o proceso.
     * @param string|null $fecha_fin Fecha de finalización de la etapa o proceso (opcional).
     * @param int|null $conteo_dias Cantidad de días asociados a la etapa o proceso (opcional).
     * @param string|null $fecha_limite_etapa Fecha límite para finalizar la etapa (opcional).
     * @param string $proceso Tipo de proceso (por defecto 'VISITA_INSPECCION').
     *
     * @return void
    */

    public function historialInformes($id_informe, $accion, $etapa, $estado, $fecha_creacion, $observaciones, $estado_etapa, $usuario_asignado, $fecha_inicio, $fecha_fin, $conteo_dias, $fecha_limite_etapa, $proceso = 'VISITA_INSPECCION') {
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
        $historial_informe->proceso= $proceso;
        $historial_informe->save();

        $this->create_history_sheets($historial_informe->id, $id_informe.date('Y'), Auth::user()->name, $accion, $etapa, $etapa.$historial_informe->id, date('d/m/Y'), $observaciones);

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

    /**
     * Registra el conteo de días hábiles para una etapa de un informe de visita de inspección.
     *
     * Este método calcula y registra el número de días hábiles permitidos para completar una etapa de inspección,
     * tomando en cuenta los días no laborales en Colombia y la cantidad de días asignados para la etapa.
     * Si la etapa no requiere días hábiles, se obtiene la fecha límite de la etapa anterior.
     *
     * @param int $id_informe ID del informe al que se asocia el conteo de días.
     * @param string $etapa Nombre de la etapa para la que se realiza el conteo de días.
     * @param string $fecha_inicial Fecha de inicio de la etapa.
     * @param string|null $fecha_final Fecha de finalización de la etapa (opcional).
     * @param string $proceso Tipo de proceso, por defecto 'VISITAS_INSPECCION'.
     *
     * @return void
    */

    public function conteoDias($id_informe, $etapa, $fecha_inicial, $fecha_final, $proceso = 'VISITAS_INSPECCION') {
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
        $conteo_dias->proceso = $proceso;
        $conteo_dias->save();
    }

    /**
     * Actualiza el número de días habiles transcurridos.
     *
     * Este método calcula y actualiza el número de días hábiles transcurridos en una etapa de inspección,
     * tomando en cuenta los días no laborales en Colombia y la cantidad de días asignados para la etapa.
     *
     * @param int $id_informe ID del informe al que se asocia el conteo de días.
     * @param string $etapa Nombre de la etapa para la que se realiza el conteo de días.
     * @param string $fecha_inicial Fecha de inicio de la etapa.
     * @param string|null $fecha_final Fecha de finalización de la etapa (opcional).
     * @param string $tipo Tipo de proceso ejecutado, por defecto ''.
     *
     * @return void
    */

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

    /**
     * Verifica los días habiles.
     *
     * Este método verifica los días habiles en Colombia
     *
     * @param string $fecha Fecha que recibe la función.
     * @param array $diasFestivosColombia arreglo con los días festivos.
     *
     * @return void
    */

    public function esDiaHabilColombia($fecha, $diasFestivosColombia) {
        $diaSemana = date('N', strtotime($fecha));
    
        return $diaSemana >= 1 && $diaSemana <= 5 && !in_array($fecha, $diasFestivosColombia);
    }

    /**
     * Sumatoría de días habiles.
     *
     * Este método realiza la sumatoria de días habiles transcurridos
     *
     * @param string $fechaInicial Fecha que recibe la función.
     * @param string $dias cantidad de días a sumar.
     * @param array $diasFestivosColombia arreglo con los días festivos.
     * @param string $id_informe identificador de la visita de inspección.
     * @param string $etapa etapa en la que se encuentra la visita de inspección.
     *
     * @return date
    */

    public function sumarDiasHabiles($fechaInicial, $dias, $diasFestivosColombia, $id_informe = '', $etapa='') { //TODO: verificar conteo de dias 0

        $contador = 0;
        $fecha = strtotime($fechaInicial);

        if ($dias == 0 && ($etapa !='ASIGNACIÓN GRUPO DE INSPECCIÓN' && $etapa !='EN REVISIÓN DEL INFORME DIAGNÓSTICO')) {
            $fecha_limite_etapa_anterior = ConteoDias::select('fecha_limite_etapa')
                                            ->where('id_informe', $id_informe)
                                            //->where('dias_habiles', '!=', 0)
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

    /**
     * Muestra el formulario con todas las visitas de inspección.
     * 
     * Metodo para la consulta de las visitas de inspección
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos para los filtros de la consulta.
     * 
     * @return \Illuminate\View\View Devuelve la vista 'consultar_informe' con los días de la visita de inspección, usuarios y parametros.
    */

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
        $informes = $informes->with('entidad')
                            ->with('conteoDias')
                            ->with('diasActuales')
                            ->orderby('id', 'desc')
                            ->paginate(10);
        $parametros = Parametro::get();

        return view('consultar_informe', [
            'usuarios' => $usuarios,
            'informes' => $informes,
            'parametros' => $parametros
        ]);
    }

    /**
     * Muestra el formulario con todas las visitas de inspección.
     * 
     * Metodo para la consulta de las visitas de inspección
     * 
     * @param string $id Id que recibe de la visita de inspección.
     *
     * 
     * @return \Illuminate\View\View Devuelve la vista 'detalle_informe' con los usuarios totales y los datos asociados a la visita de inspección.
    */

    public function vista_informe($id)
    {
        $informe = VisitaInspeccion::where('id', $id)
                                    ->with('entidad')
                                    ->with('conteoDias.usuario')
                                    ->with('historiales.usuario')
                                    ->with('usuario')
                                    ->with('usuarioDiagnostico')
                                    ->with('grupoInspeccion.usuarioAsignado')
                                    ->with('solicitudDiasAdicionales.usuario')
                                    ->with('solicitudDiasAdicionales.historial.usuario')
                                    ->with('solicitudDiasAdicionales.anexosDiasAdicionales')
                                    ->with('anexos')
                                    ->with('etapaProceso')
                                    ->first();
        $usuarios = User::orderby('name', 'ASC')->get();
        $parametros = Parametro::where('proceso', 'VISITAS_INSPECCION')->where('orden_etapa', '>=', 1)->orderby('orden_etapa', 'ASC')->get();
        return view('detalle_informe', [
            'usuariosTotales' => $usuarios,
            'informe' => $informe,
            'parametros' => $parametros,
        ]);
    }

    /**
     * Agregar observación a una visita de inspección o la cancela
     *
     * Agrega una observación a la visita de inspección. 
     * Realiza las siguientes acciones:
     *  - Actualiza el historial de la visita de la entidad.
     *  - Envía una notificación por correo electrónico a los usuarios actuales.
     * 
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos de la observación o cancelación.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el diagnóstico se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

    public function guardar_observacion(Request $request) {
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

    /**
     * Obtiene todas las visitas de inspección en curso (no canceladas ni finalizadas)
     *
     * Calcula la fecha límite para cada etapa en función de los días hábiles y las fechas festivas.
     * 
     * Si la etapa está vencida o a punto de vencer, se envía una alerta por correo electrónico a los usuarios correspondientes.
     * 
    */

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
                        
                        if ($visita->estado_informe !== 'SUSPENDIDO') {
                            echo 'Número de visita: '. $visita->numero_informe . ' alerta vencimiento de etapa <br>';

                            $visita_inspeccion = VisitaInspeccion::findOrFail($visita->id);
                            $visita_inspeccion->estado_etapa = 'EN DESTIEMPO';
                            $visita_inspeccion->save();

                            $asunto_email = 'Alerta vencimiento de etapa de visita '.$visita->numero_informe;
                            $datos_adicionales = ['numero_informe' => 'Alerta vencimiento de etapa de visita '.$visita->numero_informe,
                                                    'mensaje' => 'La visita número '. $visita->numero_informe. ' a la entidad ' . $visita->entidad->razon_social . ' se encuentra en destiempo en la etapa '. $visita->etapa . ', por favor ingresar a la plataforma y realizar la respectiva gestión.'];
                            $this->enviar_correos( json_decode($visita->usuario_actual) , $asunto_email, $datos_adicionales);
                        }

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

    /**
     * Obtiene todas las visitas de inspección en curso (no canceladas ni finalizadas)
     *
     * Calcula la fecha límite para cada etapa en función de los días hábiles y las fechas festivas.
     * 
     * Si la etapa está vencida o a punto de vencer, se envía una alerta por correo electrónico a los usuarios correspondientes.
     * 
    */

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

    /**
     * Obtiene la cantidad de días hábiles entre dos fechas.
     *
     * Recorre las fechas desde la fecha inicial hasta la fecha final,
     * verificando si cada día es un día hábil (lunes a viernes) y no es un día festivo.
     * Actualiza el conteo de días hábiles y emite un mensaje si se ha actualizado.
     * 
     * @param $fechaInicial Fecha inicial del conteo de días hábiles.
     * @param $fechaFinal Fecha final del conteo de días hábiles.
     * @param array $diasFestivosColombia Array de fechas que representan días no laborales en Colombia.
     *
     * @return int Cantidad de días hábiles entre las fechas especificadas.
    */

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

    /**
     * Obtiene los días restantes de una visita
     *
     * Verifica cuantos días falta para el vencimiento de una etapa de una visita
     * 
     * @param string $fechaLimite fecha inicial del conteo de días habiles.
     * @param string $diasFestivosColombia días no laborales.
     *
     * @return int días habiles para el vencimiento de la etapa
    */

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

    /**
     * obtiene los días festivos
     *
     * 
     * @param object $fecha fecha inicial del conteo de días habiles.
     * @param array $diasFestivos días no laborales.
     *
     * @return array días festivos en formato Y-m-d
    */
    
    private function esFestivo($fecha, $diasFestivos) {
        return in_array($fecha->format('Y-m-d'), $diasFestivos);
    }

    
    /**
     * Solicitud de Creación del diagnóstico para una entidad.
     *
     * Valida los datos de entrada y crea una solicitud de diagnóstico a una entidad creando la visita de inspección 
     * Realiza las siguientes acciones:
     *  - Validadción de datos que ingresan
     *  - Validación que la entidad no tenga visitas sin finalizar
     *  - Declaración de variables
     *  - Consulta de entidad
     *  - Busqueda de usuario intendente 
     *  - Creación de la visita de inspección
     *  - Creación de la carpeta en drive que contendra los folders
     *  - Creación del registro en la hoja de cálculo de sheets
     *  - Creación del historico
     *  - Envío de notifiación a la intendencia
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con el id de la entidad.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el diagnóstico se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

    public function solicitar_diagnostico(Request $request) {

        try {

            //Validadción de datos que ingresan

            $validatedData = $request->validate([
                'id' => 'required',
                'observacion' => 'required',
                'anexo_solicitar_diagnostico.*' => 'file|max:6000|mimes:pdf,doc,docx,xls,xlsx',
                'nombre_anexo_solicitar_diagnostico.*' => 'string',
            ]);

            //Validación que la entidad no tenga visitas sin finalizar

            $informe_entidad = VisitaInspeccion::where('id_entidad', $validatedData['id'])
                                                ->whereNotIn('estado_informe', ['FINALIZADO', 'CANCELADO'])
                                                ->get();

            if ($informe_entidad->count() > 0) {
                return response()->json(['error' => 'La entidad ya se encuentra con una visita de inspección activa'], 422);
            }

            //Declaración de variables 
    
            $usuarioCreacionId = Auth::id();
            $anio_actual = date('Y');
            $usuario_intendente = [];

            //Consulta de entidad

            $entidad = Entidad::where('id', $validatedData['id'])
                                ->first();

            //Busqueda de usuario intendente 

            if($entidad->naturaleza_organizacion === 'FONDO'){
                $usuarios_intendentes = User::where('profile', 'Intendencia de fondos de empleados')
                                            ->get();
            }else {
                $usuarios_intendentes = User::where('profile', 'Intendencia de cooperativas y otras organizaciones solidarias')
                                            ->get();
            }

            foreach($usuarios_intendentes as $usuario){
                $usuario_intendente[] = ['id' => $usuario->id, 'nombre' => $usuario->name, 'rol' => 'Intendente' ];
            }

            //Creación de la visita de inspección

            $visita_inspeccion = new VisitaInspeccion();
            $visita_inspeccion->fecha_inicio_diagnostico = date('Y-m-d');
            $visita_inspeccion->id_entidad = $validatedData['id'];
            $visita_inspeccion->usuario_creacion = $usuarioCreacionId;
            $visita_inspeccion->etapa = 'DIAGNÓSTICO INTENDENCIA';
            $visita_inspeccion->estado_informe = 'VIGENTE';
            $visita_inspeccion->estado_etapa = 'VIGENTE';
            $visita_inspeccion->usuario_diagnostico = $usuarioCreacionId;
            $visita_inspeccion->usuario_actual = json_encode($usuario_intendente);
            $visita_inspeccion->save();

            $visita_inspeccion->numero_informe = $visita_inspeccion->id . $anio_actual;
            $visita_inspeccion->save();

            //Creación de la carpeta en drive

            $accessToken = decrypt(auth()->user()->google_token);
            $folderId = "";

            $folderData = [
                'name' => $entidad->codigo.'_'.$entidad->nit.'_'.$entidad->sigla.'_'.$visita_inspeccion->id . $anio_actual,
                'parents' => [env('FOLDER_GOOGLE')],
                'mimeType' => 'application/vnd.google-apps.folder',
            ];

            $response = Http::withToken($accessToken)->post('https://www.googleapis.com/drive/v3/files', $folderData);

            if ($response->successful()) {
                $folder = $response->json();
                $folderId = $folder['id'];

                if ($request->file('anexo_solicitar_diagnostico')) {

                    $response_files = $this->cargarArchivosGoogle(
                        $request->file('anexo_solicitar_diagnostico'), 
                        $request->input('nombre_anexo_solicitar_diagnostico'), 
                        $folderId,
                        $validatedData['id'],
                        'VISITA DE INSPECCIÓN',
                        'ANEXO_SOLICITUD_DOCUMENTO_DIAGNOSTICO',
                        '',
                        'ANEXO_SOLICITUD_DOCUMENTO_DIAGNOSTICO',
                        $visita_inspeccion->id,
                    );

                    if ($response_files['status'] == 'error') {
                        return response()->json(['error' => $response_files['message']], 500);
                    }
                }
        
            } else {

                if (strpos($response->json()['error']['message'], 'Expected OAuth 2 access token') !== false) {
                    auth()->logout();
                    return response()->json(['error' => 'Sesión cerrada por problemas de autenticación. Por favor, vuelva a iniciar sesión.'], 401);
                }
                return response()->json(['error' => $response->json()['error']['message']], 500);

            }

            $visita_inspeccion->carpeta_drive = $folderId;
            $visita_inspeccion->save();

            //Creación del registro en la hoja de cálculo de sheets

            $entidad = Entidad::where('id', $validatedData['id'])
                                ->first();

            $fechaFormateada = Carbon::parse($entidad->fecha_ultimo_reporte)->format('d/m/Y');

            $create_sheets = $this->create_sheets($visita_inspeccion->id, 
                                $visita_inspeccion->id . $anio_actual,
                                NULL,
                                $entidad->nit,
                                NULL,
                                NULL,
                                NULL,
                                NULL,
                                NULL,
                                NULL,
                                NULL,
                                $entidad->numero_asociados,
                                $entidad->total_activos,
                                $fechaFormateada,
                                $entidad->nivel_supervision
                            );

            if($create_sheets['status'] === 'error'){
                return response()->json(['error' => $create_sheets['message']], 500);
            }

            //Creación del historico

            $this->historialInformes($visita_inspeccion->id, 'CREACIÓN', 'SOLICITUD DE DIAGNÓSTICO A INTENDENCIA', 'VIGENTE', date('Y-m-d'), '', 'VIGENTE', '', date('Y-m-d'), NULL, '', NULL);
            
            //Creación del registro para conteo de días
            
            $this->conteoDias($visita_inspeccion->id, 'DIAGNÓSTICO INTENDENCIA', date('Y-m-d') , NULL);

            //Envío de notifiación a la intendencia

            $asunto_email = 'solicitud de creación de dumento diagnóstico '.$visita_inspeccion->numero_informe;
            $datos_adicionales = ['numero_informe' => 'Se ha la solicitud de documento diagnóstico '.$visita_inspeccion->numero_informe,
                                    'mensaje' => 'Se realizó la solicitud de creación del documento diagnóstico de la entidad '. $entidad->razon_social . ' identificada con el nit '.
                                     $entidad->nit . ' con las siguientes observaciones: '. $validatedData['observacion']];

            foreach($usuarios_intendentes as $usuario){
                $this->enviar_correos($usuario->id, $asunto_email, $datos_adicionales);
            }

            //Mensaje de respuesta

            $successMessage = 'Solicitud de diagnóstico creada correctamente';

            return response()->json(['message' => $successMessage]);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }

    }

    /**
     * Finaliza el diagnóstico a una entidad
     *
     * Realiza las siguientes acciones:
     *  - Valida que la etapa del proceso sea la misma actual
     *  - Carga el diagnóstico a drive
     *  - Suma los días habiles
     *  - Actualiza el estado de la etapa
     *  - Envía notificación por correo electrónico
     *  - Actualiza la hoja de google sheets
     *  - Actualiza el historial de informes
     *  - Actualiza el estado de la entidad a eliminada
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el diagnóstico se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

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
                'sigla' => '',
                'observacion' => '',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {  

                $accessToken = decrypt(auth()->user()->google_token);
                
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
                $validatedData['nit']];

                foreach ($usuarios_coordinadores as $usuario) {
                    $usuarios[] = ['id' => $usuario->id, 'nombre' => $usuario->name];

                    $this->enviar_correos($usuario->id, $asunto_email, $datos_adicionales);

                    $permissionData = [
                        'type' => 'user',
                        'role' => 'writer',
                        'emailAddress' => $usuario->email,
                    ];

                    $responseFolder = Http::withToken($accessToken)
                                    ->post("https://www.googleapis.com/drive/v3/files/{$visita_inspeccion->carpeta_drive}/permissions", $permissionData);

                    if (!$responseFolder->successful()) {
                        return response()->json(['error' => $responseFolder->json()['error']['message']], 500);
                    }

                    
                }

                $usuarios_delegados = User::where('profile', 'Delegado')
                                            ->get();

                if ($usuarios_delegados->count() > 0) {
                    foreach ($usuarios_delegados as $usuarioD) {

                        $permissionData = [
                            'type' => 'user',
                            'role' => 'writer',
                            'emailAddress' => $usuarioD->email,
                        ];
    
                        $responseFolder = Http::withToken($accessToken)
                            ->post("https://www.googleapis.com/drive/v3/files/{$visita_inspeccion->carpeta_drive}/permissions", $permissionData);
    
                            
                                
                        if (!$responseFolder->successful()) {
                           return response()->json(['error' => $responseFolder->json()['error']['message']], 500);
                        }
    
                    }
                }
                
                $usuariosSinDuplicados = collect($usuarios)->unique('id')->values()->all();

                $visita_inspeccion->etapa = 'ASIGNACIÓN GRUPO DE INSPECCIÓN';
                $visita_inspeccion->estado_etapa = $estado_etapa;
                $visita_inspeccion->fecha_fin_diagnostico = date('Y-m-d');
                $visita_inspeccion->usuario_actual = json_encode($usuariosSinDuplicados);
                
                $visita_inspeccion->save();

                $this->historialInformes($validatedData['id'], 'FINALIZACIÓN DIAGNÓSTICO', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $validatedData['observacion'], $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                $this->actualizarConteoDias($visita_inspeccion->id, $validatedData['etapa'], date('Y-m-d'));

                $entidad = Entidad::where('id', $visita_inspeccion->id_entidad)
                                    ->first();

                $entidad->estado = 'ELIMINADA';        
                $entidad->motivo = 'diagnóstico cargado';       
                $entidad->save();       

                //Actualiza sheets

                $this->update_sheets($visita_inspeccion->id, NULL, NULL, NULL, NULL, NULL, $nombres_usuarios_grupo[0], $nombres_usuarios_grupo[1], $nombres_usuarios_grupo[2], $nombres_usuarios_grupo[3] ?? NULL, $nombres_usuarios_grupo[4] ?? NULL, $nombres_usuarios_grupo[5] ?? NULL, date('d/m/Y'));


                $successMessage = 'Diagnóstico enviado correctamente';

                return response()->json(['message' => $successMessage]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }

    /**
     * Asignar grupo de inspección
     * 
     * Se asigna el grupo de inpección que realizará la visita de inspección
     *
     * Realiza las siguientes acciones:
     *  - Valida que la etapa del proceso sea la misma actual
     *  - Envía notificación al grupo de inspección
     *  - Comparte la carpeta de google drive
     *  - Actualiza el historial de informes
     *  - Actualiza los datos de la visita de inspección
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el diagnóstico se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

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
                $nombres_usuarios_grupo = [];

                foreach ($grupo_visita_inspeccion as $index => $persona) {
                    $grupo_visita_inspeccion = new GrupoVisitaInspeccion();
                    $grupo_visita_inspeccion->id_informe = $validatedData['id'];
                    $grupo_visita_inspeccion->id_usuario = $persona['usuario'];
                    $grupo_visita_inspeccion->rol = $persona['rol'];
                    $grupo_visita_inspeccion->estado = 'ACTIVO';
                    $grupo_visita_inspeccion->usuario_creacion = Auth::id();

                    $asunto_email = 'Asignación '. $persona['rol'] . ' visita de inspección ' .$validatedData['numero_informe'];
                    $datos_adicionales = ['numero_informe' => 'Ha sido asignado como la persona con el rol de '. $persona['rol'] . ' para la visita de inspección '. $validatedData['numero_informe'],
                                                'mensaje' => 'Usted ha sido la persona seleccionada con el rol de '. $persona['rol'] . ' para la visita de inspección identificada con el número ' . $validatedData['numero_informe']];

                    $this->enviar_correos($persona['usuario'], $asunto_email, $datos_adicionales);

                    if ($persona['rol'] === 'Lider de visita') {
                        $usuarios_lider_visita = User::where('id', $persona['usuario'])
                                            ->first();

                        $usuarios[] = ['id' => $usuarios_lider_visita->id, 'nombre' => $usuarios_lider_visita->name];
                    }

                    $usuario = User::where('id', $persona['usuario'])
                                ->first();

                    $nombres_usuarios_grupo[] = $usuario->name;

                    $accessToken = decrypt(auth()->user()->google_token);                       

                    $permissionData = [
                        'type' => 'user',
                        'role' => 'writer',
                        'emailAddress' => $usuario->email,
                    ];

                    $responseFolder = Http::withToken($accessToken)
                                    ->post("https://www.googleapis.com/drive/v3/files/{$visita_inspeccion->carpeta_drive}/permissions", $permissionData);

                    if (!$responseFolder->successful()) {
                        if (strpos($responseFolder->json()['error']['message'], 'Expected OAuth 2 access token') !== false) {
                            auth()->logout();
                            return response()->json(['error' => 'Sesión cerrada por problemas de autenticación. Por favor, vuelva a iniciar sesión.'], 401);
                        }
                        return response()->json(['error' => $responseFolder->json()['error']['message']], 500);
                    }

                    $permission = $responseFolder->json();
                    $permissionId = $permission['id']; 

                    

                    $grupo_visita_inspeccion->permiso_carpeta_drive = $permissionId ;
                    $grupo_visita_inspeccion->save();
                } 

                $this->update_sheets($visita_inspeccion->id, NULL, NULL, NULL, NULL, NULL, $nombres_usuarios_grupo[0], $nombres_usuarios_grupo[1], $nombres_usuarios_grupo[2], $nombres_usuarios_grupo[3] ?? NULL, $nombres_usuarios_grupo[4] ?? NULL, $nombres_usuarios_grupo[5] ?? NULL, date('d/m/Y'));

                $usuarios_coordinadores = User::where('profile', 'Coordinador')
                                            ->get();

                foreach ($usuarios_coordinadores as $key => $coordinador) {
                    $usuarios[] = ['id' => $coordinador->id, 'nombre' => $coordinador->name];
                }

                $usuariosSinDuplicados = collect($usuarios)->unique('id')->values()->all();

                $estado_etapa = 'VIGENTE';

                $visita_inspeccion->etapa = 'EN REVISIÓN DEL INFORME DIAGNÓSTICO';
                $visita_inspeccion->estado_etapa = $estado_etapa;
                $visita_inspeccion->usuario_actual = json_encode($usuariosSinDuplicados);
                $visita_inspeccion->fecha_inicio_gestion = date('Y-m-d');
                $visita_inspeccion->save();

                $this->historialInformes($validatedData['id'], 'ASIGNACIÓN GRUPO DE VISITA DE INSPECCIÓN', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $validatedData['observacion'], $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                $this->conteoDias($visita_inspeccion->id, 'EN REVISIÓN DEL INFORME DIAGNÓSTICO', date('Y-m-d'), NULL);
                $this->actualizarConteoDias($visita_inspeccion->id, $validatedData['etapa'], date('Y-m-d'));

                $successMessage = 'Grupo asignado correctamente y documentos compartidos correctamente';

                return response()->json(['message' => $successMessage]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }

    /**
     * Asignar grupo de inspección
     * 
     * Se asigna el grupo de inpección que realizará la visita de inspección
     *
     * Realiza las siguientes acciones:
     *  - Valida que la etapa del proceso sea la misma actual
     *  - Verifica el resultado de la revisión
     *  - Envía notificación por correo electrónico
     *  - Actualiza el historial de informes
     *  - Actualiza los datos de la visita de inspección
     *  - Actualiza el conteo de días
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

    public function guardar_revision_diagnostico(Request $request){

        try {
        $proxima_etapa = '';
        $usuarios = [];

            $validatedData = $request->validate([
                'resultado_revision' => 'string|required',
                'id' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'estado_etapa' => 'required',
                'numero_informe' => 'required',
                'razon_social' => 'required',
                'nit' => 'required',
                'ciclo_devolucion_documento_diagnostico' => 'required_if:resultado_revision,No',
                'observaciones_documento_diagnostico' => 'required_if:resultado_revision,No',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                 return response()->json(['error' => $successMessage], 404);
            }else {

                $estado_etapa = '';
                $usuario_intendente = [];

                $observaciones = NULL;

                if(!empty($validatedData['observaciones_documento_diagnostico'])){
                    $observaciones = $validatedData['observaciones_documento_diagnostico'];
                }

                //Consulta de entidad
                $entidad = Entidad::where('id', $validatedData['id'])
                                    ->first();

                //Busqueda de usuario intendente 
                if($entidad->naturaleza_organizacion === 'FONDO'){
                    $usuarios_intendentes = User::where('profile', 'Intendencia de fondos de empleados')
                                                ->get();
                }else {
                    $usuarios_intendentes = User::where('profile', 'Intendencia de cooperativas y otras organizaciones solidarias')
                                                ->get();
                }

                if ($validatedData['resultado_revision'] === 'No') {
                    $proxima_etapa = 'EN REVISIÓN Y SUBSANACIÓN DEL DOCUMENTO DIAGNÓSTICO';

                    $update_shits = $this->update_sheets($visita_inspeccion->id, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, Carbon::parse($validatedData['ciclo_devolucion_documento_diagnostico'])->format('d/m/Y'), );

                    if($update_shits['status'] === 'error'){
                        return response()->json(['error' => $update_shits['message']], 500);
                    }    

                    $asunto_email = 'Revisar informe diágnostico de la visita '.$validatedData['numero_informe'];
                    $datos_adicionales = ['numero_informe' => 'El informe diagnóstico de la visita '. $validatedData['numero_informe'] . ' requiere de su atención',
                                                'mensaje' => 'El informe diagnóstico de la visita '. $validatedData['numero_informe'] . ' a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                    $validatedData['nit'] . ' requiere ser socializada el día '. $validatedData['ciclo_devolucion_documento_diagnostico'] . ' con las siguientes observaciones: ' . $observaciones ];

                    //Envío de notificación a la intendencia
                    foreach($usuarios_intendentes as $usuario){
                        $this->enviar_correos($usuario->id, $asunto_email, $datos_adicionales);
                    }

                    $usuarios_intendente = User::where('id', $visita_inspeccion->usuario_creacion)
                                            ->first();

                    $usuarios = [['id' => $usuarios_intendente->id, 'nombre' => $usuarios_intendente->name]];

                    $this->historialInformes($validatedData['id'], 'SOLICITUD DE SOCIALIZACIÓN DE DOCUMENTO DIAGNÓSTICO', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), "Se agendo socialización del documento diagnóstico para el día: {$observaciones}", $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                    $successMessage = 'Se envío la notificación para la verificación del informe diagnóstico';

                }elseif($validatedData['resultado_revision'] === 'Si'){
                    $proxima_etapa = 'ELABORACIÓN DE PLAN DE VISITA';

                    $update_shits = $this->update_sheets($visita_inspeccion->id, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL,NULL,
                        NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL,NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL,NULL,
                        NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL,NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL,NULL, NULL, NULL, NULL, date('d/m/Y') );

                    if($update_shits['status'] === 'error'){
                        return response()->json(['error' => $update_shits->json()['error']['message']], 500);
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

                    $this->historialInformes($validatedData['id'], 'APROBACIÓN INFORME DIAGNÓSTICO', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $observaciones, $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);
                }

                $catidad_dias_etapa = Parametro::select('dias')
                        ->where('estado', $proxima_etapa)
                        ->first();

                if ($catidad_dias_etapa->dias > 0) {
                    $estado_etapa = 'VIGENTE';
                }else{
                    $estado_etapa = $validatedData['estado_etapa'];
                }

                $usuariosSinDuplicados = collect($usuarios)->unique('id')->values()->all();

                $visita_inspeccion->etapa = $proxima_etapa;
                $visita_inspeccion->estado_etapa = $estado_etapa;
                $visita_inspeccion->usuario_actual = json_encode($usuariosSinDuplicados);
                $visita_inspeccion->save();

                $this->conteoDias($visita_inspeccion->id, $proxima_etapa, date('Y-m-d'), NULL);
                $this->actualizarConteoDias($visita_inspeccion->id, $validatedData['etapa'], date('Y-m-d'));

                $successMessage = 'Diagnóstico revisado correctamente';

                return response()->json(['message' => $successMessage]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }

    /**
     * Registra la socializacion de la visita
     * 
     * Se registra el resultado de la socializacion de la visita
     *
     * Realiza las siguientes acciones:
     *  - Valida que la etapa del proceso sea la misma actual
     *  - guarda acta de visita
     *  - guarda los anexos adicionales en drive
     *  - Envía notificación por correo electrónico
     *  - Actualiza el historial de informes
     *  - Actualiza los datos de la visita de inspección
     *  - Actualiza el conteo de días
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

    public function finalizar_socializar_visita(Request $request){

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
                'producto_generado_socializacion' => 'string|required',
                'enlace_grabacion_socializacion' => 'string|required_if:producto_generado_socializacion,GRABACIÓN|required_if:producto_generado_socializacion,AMBOS',
                'acta_asistencia_socializacion.*' => 'file|mimes:pdf,doc,docx,xls,xlsx|required_if:producto_generado_socializacion,DOCUMENTO(S)|required_if:producto_generado_socializacion,AMBOS',
                
                'anexo_socializacion_visita.*' => 'file|mimes:pdf,doc,docx,xls,xlsx|required_if:producto_generado_socializacion,DOCUMENTO(S)|required_if:producto_generado_socializacion,AMBOS',
                'nombre_anexo_socializacion_visita.*' => '',
                'observaciones' => '',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                if ($request->file('acta_asistencia_socializacion')) {
                    $response_files = $this->cargar_documento_individual(
                        $request->file('acta_asistencia_socializacion'), 
                        $visita_inspeccion->carpeta_drive,
                        $visita_inspeccion->id_entidad,
                        'VISITA DE INSPECCIÓN',
                        'ACTA_ASISTENCIA_SOCIALIZACION',
                        '',
                        'ACTA_ASISTENCIA_SOCIALIZACION',
                        $validatedData['id'],
                    );

                    if ($response_files['status'] == 'error') {
                        if (strpos($response_files->json()['error']['message'], 'Expected OAuth 2 access token') !== false) {
                            auth()->logout();
                            return response()->json(['error' => 'Sesión cerrada por problemas de autenticación. Por favor, vuelva a iniciar sesión.'], 401);
                        }
                        return response()->json(['error' => $response_files->json()['error']['message']], 500);
                    }
                }

                if ($request->file('anexo_socializacion_visita')) {

                    $response_files = $this->cargarArchivosGoogle(
                        $request->file('anexo_socializacion_visita'), 
                        $request->input('nombre_anexo_socializacion_visita'), 
                        $visita_inspeccion->carpeta_drive,
                        $visita_inspeccion->id_entidad,
                        'VISITA DE INSPECCIÓN',
                        'ANEXO_SOCIALIZACION_VISITA',
                        '',
                        'ANEXO_SOCIALIZACION_VISITA',
                        $validatedData['id'],
                    );

                    if ($response_files['status'] == 'error') {
                        if (strpos($response_files->json()['error']['message'], 'Expected OAuth 2 access token') !== false) {
                            auth()->logout();
                            return response()->json(['error' => 'Sesión cerrada por problemas de autenticación. Por favor, vuelva a iniciar sesión.'], 401);
                        }
                        return response()->json(['error' => $response_files->json()['error']['message']], 500);
                    }
                }

                /*$update_shits = $this->update_sheets($visita_inspeccion->id, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL,NULL,
                        NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL,NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL,NULL,
                        NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL,NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL,NULL, NULL, NULL, NULL, date('d/m/Y') );

                if($update_shits['status'] === 'error'){
                    return response()->json(['error' => $update_shits->json()['error']['message']], 500);
                }*/

                $proxima_etapa = 'CONFIRMACIÓN REQUERIMIENTO DE INFORMACIÓN PREVIA A LA VISITA';

                $catidad_dias_etapa = Parametro::select('dias')
                        ->where('estado', $proxima_etapa)
                        ->first();

                if ($catidad_dias_etapa->dias > 0) {
                    $estado_etapa = 'VIGENTE';
                }else{
                    $estado_etapa = $validatedData['estado_etapa'];
                }

                $asunto_email = 'Confirmar requerimiento de información previo a la visita '.$validatedData['numero_informe'];
                $datos_adicionales = ['numero_informe' => 'Confirmar requerimiento de información para la vista de inspección número '. $validatedData['numero_informe'],
                                                'mensaje' => 'Se requiere que realice la confirmación del requerimiento de información adicional para la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                $validatedData['nit']];

                $usuario_lider_visita = GrupoVisitaInspeccion::where('id_informe', $validatedData['id'])
                                            ->where('rol', 'Lider de visita')
                                            ->where('estado', 'ACTIVO')
                                            ->first();

                $usuario_lider_visita = User::where('id', $usuario_lider_visita->id_usuario)
                                            ->first();

                $this->enviar_correos($usuario_lider_visita->id, $asunto_email, $datos_adicionales);   
                    
                $usuarios = [['id' => $usuario_lider_visita->id, 'nombre' => $usuario_lider_visita->name]];

                $this->historialInformes($validatedData['id'], 'REGISTRO DE SOCIALIZACIÓN DE VISITA DE INSPECCIÓN', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $validatedData['observaciones'], $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                $usuariosSinDuplicados = collect($usuarios)->unique('id')->values()->all();

                $visita_inspeccion->etapa = $proxima_etapa;
                $visita_inspeccion->estado_etapa = $estado_etapa;
                $visita_inspeccion->usuario_actual = json_encode($usuariosSinDuplicados);
                $visita_inspeccion->acta_socializacion_visita  = $validatedData['enlace_grabacion_socializacion'];
                $visita_inspeccion->save();

                $this->conteoDias($visita_inspeccion->id, $proxima_etapa, date('Y-m-d'), NULL);
                $this->actualizarConteoDias($visita_inspeccion->id, $validatedData['etapa'], date('Y-m-d'));

                $successMessage = 'Se registro la socialización de la visita correctamente';

                return response()->json(['message' => $successMessage]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }

    /**
     * Registra la subsanación del documento diagnóstico
     * 
     * Se registra el resultado de la subsanación del documento diagnóstico
     *
     * Realiza las siguientes acciones:
     *  - Valida que la etapa del proceso sea la misma actual
     *  - guarda los anexos adicionales en drive
     *  - Envía notificación por correo electrónico
     *  - Actualiza el historial de informes
     *  - Actualiza los datos de la visita de inspección
     *  - Actualiza el conteo de días
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

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

                if ($request->file('anexo_subsanacion_diagnostico')) {

                    $response_files = $this->cargarArchivosGoogle(
                        $request->file('anexo_subsanacion_diagnostico'), 
                        $request->input('nombre_anexo_subsanacion_diagnostico'), 
                        $visita_inspeccion->carpeta_drive,
                        $visita_inspeccion->id_entidad,
                        'VISITA DE INSPECCIÓN',
                        'ANEXO_SUBSANACION_DIAGNOSTICO',
                        '',
                        'ANEXO_SUBSANACION_DIAGNOSTICO',
                        $validatedData['id'],
                    );

                    if ($response_files['status'] == 'error') {
                        if (strpos($response_files->json()['error']['message'], 'Expected OAuth 2 access token') !== false) {
                            auth()->logout();
                            return response()->json(['error' => 'Sesión cerrada por problemas de autenticación. Por favor, vuelva a iniciar sesión.'], 401);
                        }
                        return response()->json(['error' => $response_files->json()['error']['message']], 500);
                    }
                }

                $update_shits = $this->update_sheets($visita_inspeccion->id, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL,NULL,
                        NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL,NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL,NULL,
                        NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL,NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL,NULL, NULL, NULL, NULL, date('d/m/Y') );

                if($update_shits['status'] === 'error'){
                    return response()->json(['error' => $update_shits->json()['error']['message']], 500);
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

                $usuariosSinDuplicados = collect($usuarios)->unique('id')->values()->all();

                $visita_inspeccion->etapa = $proxima_etapa;
                $visita_inspeccion->estado_etapa = $estado_etapa;
                $visita_inspeccion->enlace_subsanacion_diagnostico = $validatedData['ciclo_vida_diagnostico'];
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

    /**
     * Guardar plan de visita de inspección 
     * 
     * Se guarda el plan de visita de inspección
     *
     * Realiza las siguientes acciones:
     *  - Valida que la etapa del proceso sea la misma actual
     *  - guarda el plan de visita en drive
     *  - guarda los anexos adicionales en drive
     *  - Envía notificación por correo electrónico
     *  - Actualiza el historial de informes
     *  - Actualiza los datos de la visita de inspección
     *  - Actualiza el conteo de días
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

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
                'como_efectua_visita' => '',
                'caracter_visita' => '',
                'ciclo_vida' => '',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                if ($request->file('enlace_plan_visita')) {
                    $response_files = $this->cargar_documento_individual(
                        $request->file('enlace_plan_visita'), 
                        $visita_inspeccion->carpeta_drive,
                        $visita_inspeccion->id_entidad,
                        'VISITA DE INSPECCIÓN',
                        'PLAN_VISITA',
                        '',
                        'PLAN_VISITA',
                        $validatedData['id'],
                    );

                    if ($response_files['status'] == 'error') {
                        if (strpos($response_files->json()['error']['message'], 'Expected OAuth 2 access token') !== false) {
                            auth()->logout();
                            return response()->json(['error' => 'Sesión cerrada por problemas de autenticación. Por favor, vuelva a iniciar sesión.'], 401);
                        }
                        return response()->json(['error' => $response_files->json()['error']['message']], 500);
                    }
                }

                if ($request->file('anexo_plan_visita')) {

                    $response_files = $this->cargarArchivosGoogle(
                        $request->file('anexo_plan_visita'), 
                        $request->input('nombre_anexo_plan_visita'), 
                        $visita_inspeccion->carpeta_drive,
                        $visita_inspeccion->id_entidad,
                        'VISITA DE INSPECCIÓN',
                        'ANEXOS_PLAN_VISITA',
                        '',
                        'ANEXOS_PLAN_VISITA',
                        $validatedData['id'],
                    );

                    if ($response_files['status'] == 'error') {
                        return response()->json(['error' => $response_files['message']], 500);
                    }
                }

                $update_shits = $this->update_sheets($visita_inspeccion->id, NULL, NULL, NULL, NULL, $validatedData['ciclo_vida'], NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, date('d/m/Y') , $validatedData['como_efectua_visita'], $validatedData['caracter_visita'], $validatedData['tipo_visita']);

                if($update_shits['status'] === 'error'){
                    return response()->json(['error' => $update_shits->json()['error']['message']], 500);
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
                $validatedData['nit'] . ' que se ejecutara de manera '. $validatedData['tipo_visita'] ];

                foreach ($usuarios_coordinadores as $usuario) {
                    $usuarios[] = ['id' => $usuario->id, 'nombre' => $usuario->name];

                    $this->enviar_correos($usuario->id, $asunto_email, $datos_adicionales);
                }

                $usuariosSinDuplicados = collect($usuarios)->unique('id')->values()->all();

                $visita_inspeccion->etapa = $proxima_etapa;
                $visita_inspeccion->estado_etapa = $estado_etapa;
                $visita_inspeccion->tipo_visita = $validatedData['tipo_visita'];
                $visita_inspeccion->como_efectua_visita = $validatedData['como_efectua_visita'];
                $visita_inspeccion->caracter_visita = $validatedData['caracter_visita'];
                $visita_inspeccion->ciclo_vida = $validatedData['ciclo_vida'];
                $visita_inspeccion->usuario_actual = json_encode($usuariosSinDuplicados);
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

    /**
     * Verificar plan de visita de inspección 
     * 
     * Se confirma si el plan de visita de inspección se debe modificar o no
     *
     * Realiza las siguientes acciones:
     *  - Valida que la etapa del proceso sea la misma actual
     *  - Envía notificación por correo electrónico
     *  - Actualiza el historial de informes
     *  - Actualiza los datos de la visita de inspección
     *  - Actualiza el conteo de días
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

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
                    $proxima_etapa = 'EN REUNIÓN DE SOCIALIZACIÓN DE LA VISITA DE INSPECCIÓN';

                    $asunto_email = 'Citar y realizar reunión de socialización de la visita de inspección '.$validatedData['numero_informe'];
                    $datos_adicionales = ['numero_informe' => 'Citar y realizar reunión de socialización de la visita de inspección '. $validatedData['numero_informe'],
                                                'mensaje' => 'Se requiere que realice la reunión de socialzación con el grupo de inspección de la visita de inspección a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                    $validatedData['nit']];

                    $usuario_lider_visita = GrupoVisitaInspeccion::where('id_informe', $validatedData['id'])
                                            ->where('rol', 'Lider de visita')
                                            ->where('estado', 'ACTIVO')
                                            ->first();

                    $usuario_lider_visita = User::where('id', $usuario_lider_visita->id_usuario)
                                            ->first();

                    $this->enviar_correos($usuario_lider_visita->id, $asunto_email, $datos_adicionales);   

                    $usuarios[] = ['id' => $usuario_lider_visita->id, 'nombre' => $usuario_lider_visita->name];

                    $this->historialInformes($validatedData['id'], 'SOLICITUD DE CITACIÓN A REUNIÓN CON EL GRUPO DE INSPECCIÓN PARA LA SOCIALIZACIÓN DE LA VISITA DE INSPECCIÓN', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), '', $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                    $successMessage = 'Se envío la notificación para la confirmación del requerimiento de información previa a la visita';
                }

                /*
                    elseif($validatedData['revision_plan_visita'] === 'No'){
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
                */

                $catidad_dias_etapa = Parametro::select('dias')
                        ->where('estado', $proxima_etapa)
                        ->first();

                if ($catidad_dias_etapa->dias > 0) {
                    $estado_etapa = 'VIGENTE';
                }else{
                    $estado_etapa = $validatedData['estado_etapa'];
                }

                $usuariosSinDuplicados = collect($usuarios)->unique('id')->values()->all();

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

    /**
     * Confirmar de requerimiento previo a la visita de inspección 
     * 
     * Se confirma si es necesario realizar requerimiento previo de la visita de inspección a la organización solidaria
     *
     * Realiza las siguientes acciones:
     *  - Valida que la etapa del proceso sea la misma actual
     *  - Envía notificación por correo electrónico
     *  - Actualiza el historial de la visita de inspección
     *  - Actualiza los datos de la visita de inspección
     *  - Actualiza el conteo de días
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

    public function confirmacion_informacion_previa_visita(Request $request){

        try {
        $proxima_etapa = '';
        $usuarios = [];

            $validatedData = $request->validate([
                'informacion_previa_visita' => 'required',
                'id' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'id_entidad' => 'required',
                'estado_etapa' => 'required',
                'numero_informe' => 'required',
                'razon_social' => 'required',
                'nit' => 'required',
                'observacion' => 'nullable|string',
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
                    $usuario_intendente = [];

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

                    //Consulta de entidad
                    $entidad = Entidad::where('id', $validatedData['id_entidad'])
                                        ->first();

                    //Busqueda de usuario intendente 
                    if($entidad->naturaleza_organizacion === 'FONDO'){
                        $usuarios_intendentes = User::where('profile', 'Intendencia de fondos de empleados')
                                                    ->get();
                    }else {
                    $usuarios_intendentes = User::where('profile', 'Intendencia de cooperativas y otras organizaciones solidarias')
                                                ->get();
                    }

                    //Current usser
                    foreach($usuarios_intendentes as $usuario){
                        $usuario_intendente[] = ['id' => $usuario->id, 'nombre' => $usuario->name, 'rol' => 'Intendente' ];
                    }
                    
                    //Send email
                    foreach($usuarios_intendentes as $usuario){
                        $this->enviar_correos($usuario->id, $asunto_email, $datos_adicionales);
                    }

                    $usuarios = [['id' => $usuario_lider_visita->id, 'nombre' => $usuario_lider_visita->name]];

                    $this->historialInformes($validatedData['id'], 'NEGACIÓN DE REQUERIMIENTO DE INFORMACIÓN PREVIA A LA VISITA', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $validatedData['observacion'], $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                    $successMessage = 'Se envío la notificación para la creación de cartas de presentación previa a la visita';

                }

                $catidad_dias_etapa = Parametro::select('dias')
                        ->where('estado', $proxima_etapa)
                        ->first();

                if ($catidad_dias_etapa->dias > 0) {
                    $estado_etapa = 'VIGENTE';
                }else{
                    $estado_etapa = $validatedData['estado_etapa'];
                }

                $usuariosSinDuplicados = collect($usuarios)->unique('id')->values()->all();

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

    /**
     * Finalizar requerimiento previo a la visita de inspección 
     * 
     * Se registra el requerimiento previo de la visita de inspección a la organización solidaria
     *
     * Realiza las siguientes acciones:
     *  - Valida que la etapa del proceso sea la misma actual
     *  - Envía notificación por correo electrónico
     *  - Actualiza el historial de la visita de inspección
     *  - Actualiza los datos de la visita de inspección
     *  - Actualiza el conteo de días
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

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
                'observaciones' => 'nullable|string',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                $update_shits = $this->update_sheets($visita_inspeccion->id, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, date('d/m/Y'));

                if($update_shits['status'] === 'error'){
                    return response()->json(['error' => $update_shits->json()['error']['message']], 500);
                }

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
                $visita_inspeccion->ciclo_informacion_adicional = $validatedData['ciclo_vida_requerimiento_informacion_adicional'];
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

    /**
     * Ragistrar la respuesta al requerimiento previo a la visita de inspección 
     * 
     * Se registra la respuesta al requerimiento previo de la visita de inspección a la organización solidaria
     *
     * Realiza las siguientes acciones:
     *  - Valida que la etapa del proceso sea la misma actual
     *  - Se cargan los adjuntos al drive
     *  - Envía notificación por correo electrónico
     *  - Actualiza el historial de la visita de inspección
     *  - Actualiza los datos de la visita de inspección
     *  - Actualiza el conteo de días
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

    public function registro_respuesta_informacion_adicional(Request $request)
    {
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
                'observaciones' => 'nullable|string',
                'anexo_respuesta_informacion_adicional.*' => 'file|max:6000|mimes:pdf,doc,docx,xls,xlsx',
                'nombre_anexo_respuesta_informacion_adicional.*' => 'string',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                ->with('etapaProceso')
                ->first();

            if ($visita_inspeccion->etapa !== $validatedData['etapa']) {
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            } else {

                if ($request->file('anexo_respuesta_informacion_adicional')) {

                    $response_files = $this->cargarArchivosGoogle(
                        $request->file('anexo_respuesta_informacion_adicional'), 
                        $request->input('nombre_anexo_respuesta_informacion_adicional'), 
                        $visita_inspeccion->carpeta_drive,
                        $visita_inspeccion->id_entidad,
                        'VISITA DE INSPECCIÓN',
                        'ANEXOS_INFORMACION_ADICIONAL_RECIBIDA',
                        '',
                        'ANEXOS_INFORMACION_ADICIONAL_RECIBIDA',
                        $validatedData['id'],
                    );

                    if ($response_files['status'] == 'error') {
                        if (strpos($response_files->json()['error']['message'], 'Expected OAuth 2 access token') !== false) {
                            auth()->logout();
                            return response()->json(['error' => 'Sesión cerrada por problemas de autenticación. Por favor, vuelva a iniciar sesión.'], 401);
                        }
                        return response()->json(['error' => $response_files->json()['error']['message']], 500);
                    }
                }

                $proxima_etapa = 'VALORACIÓN DE LA INFORMACIÓN RECIBIDA';

                $catidad_dias_etapa = Parametro::select('dias')
                    ->where('estado', $proxima_etapa)
                    ->first();

                if ($catidad_dias_etapa->dias > 0) {
                    $estado_etapa = 'VIGENTE';
                } else {
                    $estado_etapa = $validatedData['estado_etapa'];
                }

                if ($validatedData['confirmacion_informacion_entidad'] === 'Si') {
                    $asunto_email = 'Valoración de información de la visita ' . $validatedData['numero_informe'];
                    $datos_adicionales = [
                        'numero_informe' => 'Realizar la valoración de la información de la visita ' . $validatedData['numero_informe'],
                        'mensaje' => 'Debe realizar la valoración de información de la visita ' . $validatedData['numero_informe'] . ' a la entidad ' . $validatedData['razon_social'] . ' identificada con el nit ' .
                            $validatedData['nit']
                    ];

                    $this->historialInformes($validatedData['id'], 'REGISTRO DE INFORMACIÓN ADICIONAL POR PARTE DE LA ENTIDAD SOLIDARIA', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $validatedData['observaciones'], $validatedData['estado_etapa'], $validatedData['radicado_respuesta_entidad'], NULL, NULL, '', NULL);

                    $successMessage = 'Se registro la respuesta emitida por parte de la entidad solidaria';
                } elseif ($validatedData['confirmacion_informacion_entidad'] === 'No') {
                    $asunto_email = 'La entidad solidaria no respondio al requerimiento de información adicional ' . $validatedData['numero_informe'];
                    $datos_adicionales = [
                        'numero_informe' => 'La entidad solidaria de la visita ' . $validatedData['numero_informe'] . ' no respondio al requerimiento de información adicional',
                        'mensaje' => 'La entidad solidaria ' . $validatedData['razon_social'] . ' identificada con el nit ' . $validatedData['nit'] . ' de la visita ' . $validatedData['numero_informe'] . ' no dio respuesta al requerimiento de información adicional, por favor ingresar a la plataforma y confirmar si se realizará la visita de inspección.'
                    ];

                    $this->historialInformes($validatedData['id'], 'REGISTRO DE NO RESPUESTA POR PARTE DE LA ENTIDAD SOLIDARIA', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $validatedData['observaciones'], $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

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

                $usuariosSinDuplicados = collect($usuarios)->unique('id')->values()->all();

                $visita_inspeccion->etapa = $proxima_etapa;
                $visita_inspeccion->estado_etapa = $estado_etapa;
                $visita_inspeccion->usuario_actual = json_encode($usuariosSinDuplicados);
                $visita_inspeccion->radicado_entrada_informacion_adicional = $validatedData['radicado_respuesta_entidad'];
                $visita_inspeccion->save();

                $this->conteoDias($visita_inspeccion->id, $proxima_etapa, date('Y-m-d'), NULL);
                $this->actualizarConteoDias($visita_inspeccion->id, $validatedData['etapa'], date('Y-m-d'));

                return response()->json(['message' => $successMessage]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            if (strpos($errorMessage, 'Expected OAuth 2 access token') !== false) {
                auth()->logout();
                return response()->json(['error' => 'Sesión cerrada por problemas de autenticación. Por favor, vuelva a iniciar sesión.'], 401);
            }
            return response()->json(['error' => $errorMessage], 500);
        }
    }

    /**
     * Valorar la información recibida por parte de la entidad 
     * 
     * Se registra la valoración de la respuesta que emite la entidad solidaria
     *
     * Realiza las siguientes acciones:
     *  - Valida que la etapa del proceso sea la misma actual
     *  - Se cargan los adjuntos al drive
     *  - Envía notificación por correo electrónico
     *  - Actualiza el historial de la visita de inspección
     *  - Actualiza los datos de la visita de inspección
     *  - Actualiza el conteo de días
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

    public function valoracion_informacion_recibida(Request $request){
        try {
        $proxima_etapa = '';
        $usuarios = [];

            $validatedData = $request->validate([
                //'necesidad_visita' => 'required',
                'id' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'estado_etapa' => 'required',
                'numero_informe' => 'required',
                'razon_social' => 'required',
                'nit' => 'required',
                'ciclo_vida_plan_visita_ajustado' => 'file|max:6000|mimes:pdf,doc,docx,xls,xlsx',
                'observaciones_valoracion' => 'nullable',
                'anexo_validacion_informacion_recibida.*' => 'file|max:6000|mimes:pdf,doc,docx,xls,xlsx',
                'nombre_anexo_validacion_informacion_recibida.*' => 'nullable|string',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                if ($request->file('ciclo_vida_plan_visita_ajustado')) {
                    $response_files = $this->cargar_documento_individual(
                        $request->file('ciclo_vida_plan_visita_ajustado'), 
                        $visita_inspeccion->carpeta_drive,
                        $visita_inspeccion->id_entidad,
                        'VISITA DE INSPECCIÓN',
                        'PLAN_VISITA_AJUSTADO',
                        '',
                        'PLAN_VISITA_AJUSTADO',
                        $validatedData['id'],
                    );

                    if ($response_files['status'] == 'error') {
                        if (strpos($response_files->json()['error']['message'], 'Expected OAuth 2 access token') !== false) {
                            auth()->logout();
                            return response()->json(['error' => 'Sesión cerrada por problemas de autenticación. Por favor, vuelva a iniciar sesión.'], 401);
                        }
                        return response()->json(['error' => $response_files->json()['error']['message']], 500);
                    }
                }

                if ($request->file('anexo_validacion_informacion_recibida')) {

                    $response_files = $this->cargarArchivosGoogle(
                        $request->file('anexo_validacion_informacion_recibida'), 
                        $request->input('nombre_anexo_validacion_informacion_recibida'), 
                        $visita_inspeccion->carpeta_drive,
                        $visita_inspeccion->id_entidad,
                        'VISITA DE INSPECCIÓN',
                        'ANEXOS_PLAN_VISITA_AJUSTADO',
                        '',
                        'ANEXOS_PLAN_VISITA_AJUSTADO',
                        $validatedData['id'],
                    );

                    if ($response_files['status'] == 'error') {
                        return response()->json(['error' => $response_files['message']], 500);
                    }
                }

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

                $this->historialInformes($validatedData['id'], 'CARGUE DE PLAN DE VISITA AJUSTADO', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $validatedData['observaciones_valoracion'], $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                $successMessage = 'Se envió la soliditud de apertura de la visita correctamente';

                $usuariosSinDuplicados = collect($usuarios)->unique('id')->values()->all();

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

    /**
     * Confirmación de realización de la visita de inspección
     * 
     * Se confirma si es necesario realizar la visita de inspección
     *
     * Realiza las siguientes acciones:
     *  - Valida que la etapa del proceso sea la misma actual
     *  - Se cargan los adjuntos al drive
     *  - Envía notificación por correo electrónico
     *  - Actualiza el historial de la visita de inspección
     *  - Actualiza los datos de la visita de inspección
     *  - Actualiza el conteo de días
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

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
                'observaciones' => 'nullable|string',
                'anexo_confirmar_visita.*' => 'file|max:6000|mimes:pdf,doc,docx,xls,xlsx',
                'nombre_anexo_confirmar_visita.*' => 'nullable|string',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                if ($request->file('anexo_confirmar_visita')) {

                    $response_files = $this->cargarArchivosGoogle(
                        $request->file('anexo_confirmar_visita'), 
                        $request->input('nombre_anexo_confirmar_visita'), 
                        $visita_inspeccion->carpeta_drive,
                        $visita_inspeccion->id_entidad,
                        'VISITA DE INSPECCIÓN',
                        'ANEXO_CONFIRMACION_VISITA',
                        '',
                        'ANEXO_CONFIRMACION_VISITA',
                        $validatedData['id'],
                    );

                    if ($response_files['status'] == 'error') {
                        if (strpos($response_files->json()['error']['message'], 'Expected OAuth 2 access token') !== false) {
                            auth()->logout();
                            return response()->json(['error' => 'Sesión cerrada por problemas de autenticación. Por favor, vuelva a iniciar sesión.'], 401);
                        }
                        return response()->json(['error' => $response_files->json()['error']['message']], 500);
                    }
                }

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

                    $this->historialInformes($validatedData['id'], 'CONFIRMACIÓN DE QUE NO ES NECESARIA LA VISITA', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $validatedData['observaciones'], $validatedData['estado_etapa'], $validatedData['ciclo_vida_confirmacion_visita'], NULL, NULL, '', NULL);

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

                    $this->historialInformes($validatedData['id'], 'CONFIRMACIÓN DE LA VISITA', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $validatedData['observaciones'], $validatedData['estado_etapa'], $validatedData['ciclo_vida_confirmacion_visita'], NULL, NULL, '', NULL);

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

                $usuariosSinDuplicados = collect($usuarios)->unique('id')->values()->all();

                $visita_inspeccion->etapa = $proxima_etapa;
                $visita_inspeccion->estado_etapa = $estado_etapa;
                $visita_inspeccion->ciclo_vida_confirmacion_visita = $validatedData['ciclo_vida_confirmacion_visita'];
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

    /**
     * Registro de realización de cartas de presentación previo a  la visita de inspección
     * 
     * Se registra la carta de presentación que se envía a la entidad previo a la visita de inspección
     *
     * Realiza las siguientes acciones:
     *  - Valida que la etapa del proceso sea la misma actual
     *  - Envía notificación por correo electrónico
     *  - Actualiza el historial de la visita de inspección
     *  - Actualiza los datos de la visita de inspección
     *  - Actualiza el conteo de días
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

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

                $update_shits = $this->update_sheets($visita_inspeccion->id, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, date('d/m/Y'));

                if($update_shits['status'] === 'error'){
                    return response()->json(['error' => $update_shits['message']], 500);
                }

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

                $usuariosSinDuplicados = collect($usuarios)->unique('id')->values()->all();

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

    /**
     * Abrir la visita de inspección
     * 
     * Se realiza la apertura de la visita de inspección
     *
     * Realiza las siguientes acciones:
     *  - Valida que la etapa del proceso sea la misma actual
     *  - Se cargan los adjuntos al drive
     *  - Envía notificación por correo electrónico
     *  - Actualiza el historial de la visita de inspección
     *  - Actualiza los datos de la visita de inspección
     *  - Actualiza el conteo de días
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

    public function abrir_visita_inspeccion(Request $request) {
        try {
            $validatedData = $request->validate([
                'id' => 'required',
                'grupo_inspeccion' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'estado_etapa' => 'required',
                'numero_informe' => 'required',
                'razon_social' => 'required',
                'nit' => 'required',
                'observaciones' => 'nullable|string',
                
                'nombre_anexo_abrir_visita.*' => 'nullable|string',
                'anexo_abrir_visita.*' => 'file|max:6000|mimes:pdf,doc,docx,xls,xlsx',
                'carta_salvaguarda' => 'file|max:6000|mimes:pdf,doc,docx,xls,xlsx',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                        ->with('etapaProceso')
                                                        ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                

                if ($request->file('carta_salvaguarda')) {
                    $response_files = $this->cargar_documento_individual(
                        $request->file('carta_salvaguarda'), 
                        $visita_inspeccion->carpeta_drive,
                        $visita_inspeccion->id_entidad,
                        'VISITA DE INSPECCIÓN',
                        'CARTA_SALVAGUARDA',
                        '',
                        'CARTA_SALVAGUARDA',
                        $validatedData['id'],
                    );

                    if ($response_files['status'] == 'error') {
                        return response()->json(['error' => $response_files['message']], 500);
                    }
                }

                if ($request->file('anexo_abrir_visita')) {

                    $response_files = $this->cargarArchivosGoogle(
                        $request->file('anexo_abrir_visita'), 
                        $request->input('nombre_anexo_abrir_visita'), 
                        $visita_inspeccion->carpeta_drive,
                        $visita_inspeccion->id_entidad,
                        'VISITA DE INSPECCIÓN',
                        'ANEXO_APERTURA_VISITA',
                        '',
                        'ANEXO_APERTURA_VISITA',
                        $validatedData['id'],
                    );

                    if ($response_files['status'] == 'error') {
                        return response()->json(['error' => $response_files['message']], 500);
                    }
                }

                $update_shits = $this->update_sheets($visita_inspeccion->id, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL,  NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'FUE CARGADA', date('d/m/Y'));

                if($update_shits['status'] === 'error'){
                    return response()->json(['error' => $update_shits->json()['error']['message']], 500);
                }

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

                        $usuario = User::where('id', $persona['usuario'])
                                ->first();

                        $accessToken = decrypt(auth()->user()->google_token);                       

                        $permissionData = [
                            'type' => 'user',
                            'role' => 'writer',
                            'emailAddress' => $usuario->email,
                        ];

                        $responseFolder = Http::withToken($accessToken)
                                        ->post("https://www.googleapis.com/drive/v3/files/{$visita_inspeccion->carpeta_drive}/permissions", $permissionData);

                        if (!$responseFolder->successful()) {
                            return response()->json(['error' => $responseFolder->json()['error']['message']], 500);
                        }
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


                //Consulta de entidad
                $entidad = Entidad::where('id', $validatedData['id'])
                                    ->first();

                //Busqueda de usuario intendente 
                if($entidad->naturaleza_organizacion === 'FONDO'){
                                $usuarios_intendentes = User::where('profile', 'Intendencia de fondos de empleados')
                                                            ->get();
                }else {
                                $usuarios_intendentes = User::where('profile', 'Intendencia de cooperativas y otras organizaciones solidarias')
                                                            ->get();
                }

                //Envío de notificación a la intendencia
                foreach($usuarios_intendentes as $usuario){
                    $this->enviar_correos($usuario->id, $asunto_email, $datos_adicionales);
                }

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


                $usuariosSinDuplicados = collect($usuarios)->unique('id')->values()->all();

                $visita_inspeccion->etapa = $proxima_etapa;
                $visita_inspeccion->estado_etapa = $estado_etapa;
                $visita_inspeccion->usuario_actual = json_encode($usuariosSinDuplicados);
                $visita_inspeccion->save();

                $this->historialInformes($validatedData['id'], 'APERTURA DE LA VISITA DE INSPECCIÓN', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $validatedData['observaciones'], $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

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

    /**
     * Iniciar la visita de inspección
     * 
     * Se da el inicio de la visita de inspección
     *
     * Realiza las siguientes acciones:
     *  - Valida que la etapa del proceso sea la misma actual
     *  - Envía notificación por correo electrónico
     *  - Actualiza el historial de la visita de inspección
     *  - Actualiza los datos de la visita de inspección
     *  - Actualiza el conteo de días
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

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

                $update_shits = $this->update_sheets($visita_inspeccion->id, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL,  NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, date('d/m/Y'), $catidad_dias_etapa->dias);

                if($update_shits['status'] === 'error'){
                    return response()->json(['error' => $update_shits->json()['error']['message']], 500);
                }

                if ($catidad_dias_etapa->dias > 0) {
                    $estado_etapa = 'VIGENTE';
                }else{
                    $estado_etapa = $validatedData['estado_etapa'];
                }

                

                $grupo_inspeccion = GrupoVisitaInspeccion::where('id_informe', $validatedData['id'])
                                ->whereIn('rol', ['Lider de visita', 'Inspector'])
                                ->where('estado', 'ACTIVO')
                                ->get();

               foreach ($grupo_inspeccion as $persona) {
                    $usuario_grupo = User::where('id', $persona->id_usuario)->first();
                        if ($usuario_grupo) {
                            $usuarios[] = [
                                'id' => $usuario_grupo->id,
                                'nombre' => $usuario_grupo->name
                            ];
                    }
                }
                
                $usuariosSinDuplicados = collect($usuarios)->unique('id')->values()->all();

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

    /**
     * Finalizar la visita de inspección
     * 
     * Se da fin a la visita de inspección
     *
     * Realiza las siguientes acciones:
     *  - Valida que la etapa del proceso sea la misma actual
     *  - Carga los documentos al drive
     *  - Envía notificación por correo electrónico
     *  - Actualiza el historial de la visita de inspección
     *  - Actualiza los datos de la visita de inspección
     *  - Actualiza el conteo de días
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

    public function cerrar_visita_inspeccion(Request $request){
        try {
        $proxima_etapa = '';
        $usuarios = [];

            $validatedData = $request->validate([
                'id' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'id_entidad' => 'required',
                'estado_etapa' => 'required',
                'numero_informe' => 'required',
                'razon_social' => 'required',
                'nit' => 'required',
                'observaciones' => 'nullable|string',

                'nombre_documento_cierre_visita.*' => 'nullable|string',
                'enlace_documento_cierre_visita.*' => 'nullable|file|max:6000|mimes:pdf,doc,docx,xls,xlsx',

                'nombre_documento_cierre_visita_FT-SUPE-016_noobligat.*' => 'nullable|string',
                'enlace_documento_cierre_visita_FT-SUPE-016_noobligat.*' => 'nullable|file|max:6000|mimes:pdf,doc,docx,xls,xlsx',

                'nombre_documento_cierre_visita_FT-SUPE-020_noobligat.*' => 'nullable|string',
                'enlace_documento_cierre_visita_FT-SUPE-020_noobligat.*' => 'nullable|file|max:6000|mimes:pdf,doc,docx,xls,xlsx',

                'nombre_documento_cierre_visita_FT-SUPE-023_noobligat.*' => 'nullable|string',
                'enlace_documento_cierre_visita_FT-SUPE-023_noobligat.*' => 'nullable|file|max:6000|mimes:pdf,doc,docx,xls,xlsx',

                'nombre_documento_cierre_visita_FT-SUPE-024_noobligat.*' => 'nullable|string',
                'enlace_documento_cierre_visita_FT-SUPE-024_noobligat.*' => 'nullable|file|max:6000|mimes:pdf,doc,docx,xls,xlsx',

                'nombre_documento_cierre_visita_FT-SUPE-025_noobligat.*' => 'nullable|string',
                'enlace_documento_cierre_visita_FT-SUPE-025_noobligat.*' => 'nullable|file|max:6000|mimes:pdf,doc,docx,xls,xlsx',

                'nombre_documento_cierre_visita_FT-SUPE-026_noobligat.*' => 'nullable|string',
                'enlace_documento_cierre_visita_FT-SUPE-026_noobligat.*' => 'nullable|file|max:6000|mimes:pdf,doc,docx,xls,xlsx',

                'nombre_documento_cierre_visita_FT-SUPE-027_noobligat.*' => 'nullable|string',
                'enlace_documento_cierre_visita_FT-SUPE-027_noobligat.*' => 'nullable|file|max:6000|mimes:pdf,doc,docx,xls,xlsx',

                'nombre_documento_cierre_visita_FT-SUPE-028_noobligat.*' => 'nullable|string',
                'enlace_documento_cierre_visita_FT-SUPE-028_noobligat.*' => 'nullable|file|max:6000|mimes:pdf,doc,docx,xls,xlsx',

                'nombre_documento_cierre_visita_FT-SUPE-029_noobligat.*' => 'nullable|string',
                'enlace_documento_cierre_visita_FT-SUPE-029_noobligat.*' => 'nullable|file|max:6000|mimes:pdf,doc,docx,xls,xlsx',
                
                'documento_apertura_visita' => 'required|string',
                'acta_apertura_visita' => 'file|max:6000|mimes:pdf,doc,docx,xls,xlsx|required_if:documento_apertura_visita,Acta de apertura',
                'grabacion_apertura_visita' => 'required_if:documento_apertura_visita,Grabación de apertura',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                if ($request->file('acta_apertura_visita')) {
                    $response_files = $this->cargar_documento_individual(
                        $request->file('acta_apertura_visita'), 
                        $visita_inspeccion->carpeta_drive,
                        $visita_inspeccion->id_entidad,
                        'VISITA DE INSPECCIÓN',
                        'ACTA_APERTURA_VISITA',
                        '',
                        'ACTA_APERTURA_VISITA',
                        $validatedData['id'],
                    );

                    if ($response_files['status'] == 'error') {
                        if (strpos($response_files->json()['error']['message'], 'Expected OAuth 2 access token') !== false) {
                            auth()->logout();
                            return response()->json(['error' => 'Sesión cerrada por problemas de autenticación. Por favor, vuelva a iniciar sesión.'], 401);
                        }
                        return response()->json(['error' => $response_files->json()['error']['message']], 500);
                    }
                }

                $FT_SUPE_016 = 'NO';
                $FT_SUPE_020 = 'NO';
                $FT_SUPE_023 = 'NO';
                $FT_SUPE_024 = 'NO';
                $FT_SUPE_025 = 'NO';
                $FT_SUPE_026 = 'NO';
                $FT_SUPE_027 = 'NO';
                $FT_SUPE_028 = 'NO';
                $FT_SUPE_029 = 'NO';

                if ($request->file('enlace_documento_cierre_visita')) {

                    $response_files = $this->cargarArchivosGoogle(
                        $request->file('enlace_documento_cierre_visita'), 
                        $request->input('nombre_documento_cierre_visita'), 
                        $visita_inspeccion->carpeta_drive,
                        $visita_inspeccion->id_entidad,
                        'VISITA DE INSPECCIÓN',
                        'CIERRE_VISITA_INSPECCION',
                        '',
                        'CIERRE_VISITA_INSPECCION',
                        $validatedData['id'],
                    );

                    if ($response_files['status'] == 'error') {
                        if (strpos($response_files->json()['error']['message'], 'Expected OAuth 2 access token') !== false) {
                            auth()->logout();
                            return response()->json(['error' => 'Sesión cerrada por problemas de autenticación. Por favor, vuelva a iniciar sesión.'], 401);
                        }
                        return response()->json(['error' => $response_files->json()['error']['message']], 500);
                    }
                }

                if ($request->file('enlace_documento_cierre_visita_FT-SUPE-016_noobligat')) {

                    $response_files = $this->cargarArchivosGoogle(
                        $request->file('enlace_documento_cierre_visita_FT-SUPE-016_noobligat'), 
                        $request->input('nombre_documento_cierre_visita_FT-SUPE-016_noobligat'), 
                        $visita_inspeccion->carpeta_drive,
                        $visita_inspeccion->id_entidad,
                        'VISITA DE INSPECCIÓN',
                        'CIERRE_VISITA_INSPECCION',
                        '',
                        'CIERRE_VISITA_INSPECCION',
                        $validatedData['id'],
                    );

                    if ($response_files['status'] == 'error') {
                        if (strpos($response_files->json()['error']['message'], 'Expected OAuth 2 access token') !== false) {
                            auth()->logout();
                            return response()->json(['error' => 'Sesión cerrada por problemas de autenticación. Por favor, vuelva a iniciar sesión.'], 401);
                        }
                        return response()->json(['error' => $response_files->json()['error']['message']], 500);
                    }

                    $FT_SUPE_016 = 'SI';
                }

                if ($request->file('enlace_documento_cierre_visita_FT-SUPE-020_noobligat')) {

                    $response_files = $this->cargarArchivosGoogle(
                        $request->file('enlace_documento_cierre_visita_FT-SUPE-020_noobligat'), 
                        $request->input('nombre_documento_cierre_visita_FT-SUPE-020_noobligat'), 
                        $visita_inspeccion->carpeta_drive,
                        $visita_inspeccion->id_entidad,
                        'VISITA DE INSPECCIÓN',
                        'CIERRE_VISITA_INSPECCION',
                        '',
                        'CIERRE_VISITA_INSPECCION',
                        $validatedData['id'],
                    );

                    if ($response_files['status'] == 'error') {
                        if (strpos($response_files->json()['error']['message'], 'Expected OAuth 2 access token') !== false) {
                            auth()->logout();
                            return response()->json(['error' => 'Sesión cerrada por problemas de autenticación. Por favor, vuelva a iniciar sesión.'], 401);
                        }
                        return response()->json(['error' => $response_files->json()['error']['message']], 500);
                    }

                    $FT_SUPE_020 = 'SI';
                }

                if ($request->file('enlace_documento_cierre_visita_FT-SUPE-023_noobligat')) {

                    $response_files = $this->cargarArchivosGoogle(
                        $request->file('enlace_documento_cierre_visita_FT-SUPE-023_noobligat'), 
                        $request->input('nombre_documento_cierre_visita_FT-SUPE-023_noobligat'), 
                        $visita_inspeccion->carpeta_drive,
                        $visita_inspeccion->id_entidad,
                        'VISITA DE INSPECCIÓN',
                        'CIERRE_VISITA_INSPECCION',
                        '',
                        'CIERRE_VISITA_INSPECCION',
                        $validatedData['id'],
                    );

                    if ($response_files['status'] == 'error') {
                        if (strpos($response_files->json()['error']['message'], 'Expected OAuth 2 access token') !== false) {
                            auth()->logout();
                            return response()->json(['error' => 'Sesión cerrada por problemas de autenticación. Por favor, vuelva a iniciar sesión.'], 401);
                        }
                        return response()->json(['error' => $response_files->json()['error']['message']], 500);
                    }

                    $FT_SUPE_023 = 'SI';
                }

                if ($request->file('enlace_documento_cierre_visita_FT-SUPE-024_noobligat')) {

                    $response_files = $this->cargarArchivosGoogle(
                        $request->file('enlace_documento_cierre_visita_FT-SUPE-024_noobligat'), 
                        $request->input('nombre_documento_cierre_visita_FT-SUPE-024_noobligat'), 
                        $visita_inspeccion->carpeta_drive,
                        $visita_inspeccion->id_entidad,
                        'VISITA DE INSPECCIÓN',
                        'CIERRE_VISITA_INSPECCION',
                        '',
                        'CIERRE_VISITA_INSPECCION',
                        $validatedData['id'],
                    );

                    if ($response_files['status'] == 'error') {
                        if (strpos($response_files->json()['error']['message'], 'Expected OAuth 2 access token') !== false) {
                            auth()->logout();
                            return response()->json(['error' => 'Sesión cerrada por problemas de autenticación. Por favor, vuelva a iniciar sesión.'], 401);
                        }
                        return response()->json(['error' => $response_files->json()['error']['message']], 500);
                    }

                    $FT_SUPE_024 = 'SI';

                }

                if ($request->file('enlace_documento_cierre_visita_FT-SUPE-025_noobligat')) {

                    $response_files = $this->cargarArchivosGoogle(
                        $request->file('enlace_documento_cierre_visita_FT-SUPE-025_noobligat'), 
                        $request->input('nombre_documento_cierre_visita_FT-SUPE-025_noobligat'), 
                        $visita_inspeccion->carpeta_drive,
                        $visita_inspeccion->id_entidad,
                        'VISITA DE INSPECCIÓN',
                        'CIERRE_VISITA_INSPECCION',
                        '',
                        'CIERRE_VISITA_INSPECCION',
                        $validatedData['id'],
                    );

                    if ($response_files['status'] == 'error') {
                        if (strpos($response_files->json()['error']['message'], 'Expected OAuth 2 access token') !== false) {
                            auth()->logout();
                            return response()->json(['error' => 'Sesión cerrada por problemas de autenticación. Por favor, vuelva a iniciar sesión.'], 401);
                        }
                        return response()->json(['error' => $response_files->json()['error']['message']], 500);
                    }

                    $FT_SUPE_025 = 'SI';
                }

                if ($request->file('enlace_documento_cierre_visita_FT-SUPE-026_noobligat')) {

                    $response_files = $this->cargarArchivosGoogle(
                        $request->file('enlace_documento_cierre_visita_FT-SUPE-026_noobligat'), 
                        $request->input('nombre_documento_cierre_visita_FT-SUPE-026_noobligat'), 
                        $visita_inspeccion->carpeta_drive,
                        $visita_inspeccion->id_entidad,
                        'VISITA DE INSPECCIÓN',
                        'CIERRE_VISITA_INSPECCION',
                        '',
                        'CIERRE_VISITA_INSPECCION',
                        $validatedData['id'],
                    );

                    if ($response_files['status'] == 'error') {
                        if (strpos($response_files->json()['error']['message'], 'Expected OAuth 2 access token') !== false) {
                            auth()->logout();
                            return response()->json(['error' => 'Sesión cerrada por problemas de autenticación. Por favor, vuelva a iniciar sesión.'], 401);
                        }
                        return response()->json(['error' => $response_files->json()['error']['message']], 500);
                    }

                    $FT_SUPE_026 = 'SI';
                }

                if ($request->file('enlace_documento_cierre_visita_FT-SUPE-027_noobligat')) {

                    $response_files = $this->cargarArchivosGoogle(
                        $request->file('enlace_documento_cierre_visita_FT-SUPE-027_noobligat'), 
                        $request->input('nombre_documento_cierre_visita_FT-SUPE-027_noobligat'), 
                        $visita_inspeccion->carpeta_drive,
                        $visita_inspeccion->id_entidad,
                        'VISITA DE INSPECCIÓN',
                        'CIERRE_VISITA_INSPECCION',
                        '',
                        'CIERRE_VISITA_INSPECCION',
                        $validatedData['id'],
                    );

                    if ($response_files['status'] == 'error') {
                        if (strpos($response_files->json()['error']['message'], 'Expected OAuth 2 access token') !== false) {
                            auth()->logout();
                            return response()->json(['error' => 'Sesión cerrada por problemas de autenticación. Por favor, vuelva a iniciar sesión.'], 401);
                        }
                        return response()->json(['error' => $response_files->json()['error']['message']], 500);
                    }

                    $FT_SUPE_027 = 'SI';

                }

                if ($request->file('enlace_documento_cierre_visita_FT-SUPE-028_noobligat')) {

                    $response_files = $this->cargarArchivosGoogle(
                        $request->file('enlace_documento_cierre_visita_FT-SUPE-028_noobligat'), 
                        $request->input('nombre_documento_cierre_visita_FT-SUPE-028_noobligat'), 
                        $visita_inspeccion->carpeta_drive,
                        $visita_inspeccion->id_entidad,
                        'VISITA DE INSPECCIÓN',
                        'CIERRE_VISITA_INSPECCION',
                        '',
                        'CIERRE_VISITA_INSPECCION',
                        $validatedData['id'],
                    );

                    if ($response_files['status'] == 'error') {
                        if (strpos($response_files->json()['error']['message'], 'Expected OAuth 2 access token') !== false) {
                            auth()->logout();
                            return response()->json(['error' => 'Sesión cerrada por problemas de autenticación. Por favor, vuelva a iniciar sesión.'], 401);
                        }
                        return response()->json(['error' => $response_files->json()['error']['message']], 500);
                    }

                    $FT_SUPE_028 = 'SI';

                }

                if ($request->file('enlace_documento_cierre_visita_FT-SUPE-029_noobligat')) {

                    $response_files = $this->cargarArchivosGoogle(
                        $request->file('enlace_documento_cierre_visita_FT-SUPE-029_noobligat'), 
                        $request->input('nombre_documento_cierre_visita_FT-SUPE-029_noobligat'), 
                        $visita_inspeccion->carpeta_drive,
                        $visita_inspeccion->id_entidad,
                        'VISITA DE INSPECCIÓN',
                        'CIERRE_VISITA_INSPECCION',
                        '',
                        'CIERRE_VISITA_INSPECCION',
                        $validatedData['id'],
                    );

                    if ($response_files['status'] == 'error') {
                        if (strpos($response_files->json()['error']['message'], 'Expected OAuth 2 access token') !== false) {
                            auth()->logout();
                            return response()->json(['error' => 'Sesión cerrada por problemas de autenticación. Por favor, vuelva a iniciar sesión.'], 401);
                        }
                        return response()->json(['error' => $response_files->json()['error']['message']], 500);
                    }

                    $FT_SUPE_029 = 'SI';

                }

                $update_shits = $this->update_sheets($visita_inspeccion->id, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 
                    NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, date('d/m/Y'), NULL, NULL, NULL, $FT_SUPE_020, $FT_SUPE_016,
                    $FT_SUPE_024, $FT_SUPE_026, $FT_SUPE_028, $FT_SUPE_029, $FT_SUPE_027, $FT_SUPE_025, $FT_SUPE_023);

                if($update_shits['status'] === 'error'){
                    return response()->json(['error' => $update_shits['message']], 500);
                }

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

                //Consulta de entidad
                $entidad = Entidad::where('id', $validatedData['id_entidad'])
                                ->first();

                //Busqueda de usuario intendente 
                if($entidad->naturaleza_organizacion === 'FONDO'){
                    $usuarios_intendentes = User::where('profile', 'Intendencia de fondos de empleados')
                                                ->get();
                }else {
                $usuarios_intendentes = User::where('profile', 'Intendencia de cooperativas y otras organizaciones solidarias')
                                            ->get();
                }

                //Send email
                foreach($usuarios_intendentes as $usuario){
                    $this->enviar_correos($usuario->id, $asunto_email, $datos_adicionales);
                }

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
                
                $usuariosSinDuplicados = collect($usuarios)->unique('id')->values()->all();
                
                $this->historialInformes($validatedData['id'], 'FINALIZACIÓN DE LA VISITA DE INSPECCIÓN', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $validatedData['observaciones'], $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                $successMessage = 'Se finalizó la visita de inspección, se notifico a la delegatura, intendencia y coordinación';

                $visita_inspeccion->etapa = $proxima_etapa;
                $visita_inspeccion->fecha_fin_visita = date('Y-m-d');
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

    /**
     * Se cargan los archivos al google drive
     * 
     * Función para cargar archivos a google drive
     *
     * @param file $uploadedFiles documeto a cargar.
     * @param string $fileNames nombre del documento a cargar.
     * @param string $folderId id de la carpeta donde se cargara el documento.
     * @param string $id_entidad id de la entidad.
     * @param string $proceso proceso al que pertenece el documento.
     * @param string $sub_proceso sub proceso del proceso.
     * @param string $id_sub_proceso id del subproceso.
     * @param string $tipo_anexo tipo de anexo.
     * @param string $id_tipo_anexo id del tipo de anexo.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

    public function cargarArchivosGoogle($uploadedFiles, $fileNames, $folderId, $id_entidad, $proceso, $sub_proceso, $id_sub_proceso, $tipo_anexo, $id_tipo_anexo ) {
        $accessToken = decrypt(auth()->user()->google_token);
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

    /**
     * Solicitar días adicionales para finalizar la visita de inspección
     * 
     * Se solicitan días adicionales para finalizar la visita de inspección
     *
     * Realiza las siguientes acciones:
     *  - Valida que la etapa del proceso sea la misma actual
     *  - Carga los documentos al drive
     *  - Envía notificación por correo electrónico
     *  - Actualiza el historial de la visita de inspección
     *  - Actualiza los datos de la visita de inspección
     *  - Actualiza el conteo de días
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

    public function solicitar_dias_adicionales(Request $request){
        try {

            $validatedData = $request->validate([
                'id' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'id_entidad' => 'required',
                'estado_etapa' => 'required',
                'numero_informe' => 'required',
                'razon_social' => 'required',
                'nit' => 'required',
                'observaciones' => 'required|string',
                'dias' => 'required|numeric',
                'anexo_dias_adicionales_lider.*' => 'file|max:6000|mimes:pdf,doc,docx,xls,xlsx',
                'nombre_anexo_dias_adicionales_lider.*' => 'nullable|string',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                $usuarioCreacionId = Auth::id();
                $userName = Auth::user()->name;

                $asunto_email = 'Solicitud de días adicionales para la visita '.$validatedData['numero_informe'];
                $datos_adicionales = ['numero_informe' => 'Solicitud de días adicionales para la visita '. $validatedData['numero_informe'],
                                            'mensaje' => $userName.' lider de la visita de inspección '. $validatedData['numero_informe'] . ' a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                $validatedData['nit']. ', solicita adicionar '. $validatedData['dias']. ' días adicionales para dar cierre a la visita de inspección con las siguientes observaciones: '. $validatedData['observaciones'] ];

                //Consulta de entidad
                $entidad = Entidad::where('id', $validatedData['id_entidad'])
                                    ->first();

                //Busqueda de usuario intendente 
                if($entidad->naturaleza_organizacion === 'FONDO'){
                    $usuarios_intendentes = User::where('profile', 'Intendencia de fondos de empleados')
                                                ->get();
                }else {
                $usuarios_intendentes = User::where('profile', 'Intendencia de cooperativas y otras organizaciones solidarias')
                                            ->get();
                }

                //Send email
                foreach($usuarios_intendentes as $usuario){
                    $this->enviar_correos($usuario->id, $asunto_email, $datos_adicionales);
                }

                $usuarios_coordinadores = User::where('profile', 'Coordinador')
                                                ->get();

                foreach ($usuarios_coordinadores as $usuario_coordinador) {
                    $this->enviar_correos($usuario_coordinador->id, $asunto_email, $datos_adicionales);
                }

                $dias_adicionales = new SolicitudDiaAdicional();
                $dias_adicionales->dias = $validatedData['dias'];
                $dias_adicionales->observacion = $validatedData['observaciones'];
                $dias_adicionales->estado = 'APROBACIÓN COORDINACIÓN';
                $dias_adicionales->id_informe = $validatedData['id'];
                $dias_adicionales->usuario_creacion = $usuarioCreacionId;
                $dias_adicionales->save();

                if ($request->file('anexo_dias_adicionales_lider')) {

                    $response_files = $this->cargarArchivosGoogle(
                        $request->file('anexo_dias_adicionales_lider'), 
                        $request->input('nombre_anexo_dias_adicionales_lider'), 
                        $visita_inspeccion->carpeta_drive,
                        $visita_inspeccion->id_entidad,
                        'VISITA DE INSPECCIÓN',
                        'ANEXO_DIAS_ADICIONALES',
                        $dias_adicionales->id,
                        'ANEXO_DIAS_ADICIONALES',
                        $dias_adicionales->id,
                    );

                    if ($response_files['status'] == 'error') {
                        if (strpos($response_files->json()['error']['message'], 'Expected OAuth 2 access token') !== false) {
                            auth()->logout();
                            return response()->json(['error' => 'Sesión cerrada por problemas de autenticación. Por favor, vuelva a iniciar sesión.'], 401);
                        }
                        return response()->json(['error' => $response_files->json()['error']['message']], 500);
                    }
                }

                $this->historialInformes($validatedData['id'], 'SOLICITUD DE DÍAS ADICIONALES A LA VISITA', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), 'Solicitud de '.$validatedData['dias']. ' días adicionales con las siguientes observaciones: '.$validatedData['observaciones'], $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                $successMessage = 'Se envía la solicitud de '.$validatedData['dias'].' día(s) adicional(es) a la coordinación del grupo de inspección';

                return response()->json(['message' => $successMessage]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }

    /**
     * confirmar días adicionales para finalizar la visita de inspección coordinación
     * 
     * Se confirma o se rechaza la solicitud de días adicionales por parte de la coordinación del grupo de inspección
     *
     * Realiza las siguientes acciones:
     *  - Valida que la etapa del proceso sea la misma actual
     *  - Carga los documentos al drive
     *  - Envía notificación por correo electrónico
     *  - Actualiza el historial de la visita de inspección
     *  - Actualiza los datos de la visita de inspección
     *  - Actualiza el conteo de días
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

    public function confirmar_dias_adicionales_coordinacion(Request $request){
        try {

            $validatedData = $request->validate([
                'id' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'estado_etapa' => 'required',
                'numero_informe' => 'required',
                'razon_social' => 'required',
                'nit' => 'required',
                'confirmar_rechazar_solicitud' => 'required|string',
                'dias' => 'required|numeric',
                'observaciones' => 'required_if:confirmar_rechazar_solicitud,Rechazar',
                'id_solicitud' => 'required|numeric',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                $usuarioCreacionId = Auth::id();
                $userName = Auth::user()->name;

                $dias_adicionales = SolicitudDiaAdicional::where('id', $validatedData['id_solicitud'])
                                                        ->first();
                $historico_dias_adicionales = new HistoricoSolicitudDiaAdicional();

                $observaciones = '';

                if ($validatedData['observaciones']) {
                    $observaciones = 'con las siguientes observaciones: '. $validatedData['observaciones'];
                }

                if ($validatedData['confirmar_rechazar_solicitud'] === 'Confirmar') {
                    $dias_adicionales->estado = 'APROBACIÓN DELEGATURA';
                    $historico_dias_adicionales->estado = 'APROBADO';

                    $asunto_email = 'Solicitud de días adicionales para la visita '.$validatedData['numero_informe'];
                    $datos_adicionales = ['numero_informe' => 'Solicitud de días adicionales para la visita '. $validatedData['numero_informe'],
                                                'mensaje' => $userName.' en la coordinación de la visita de inspección '. $validatedData['numero_informe'] . ' a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                    $validatedData['nit']. ', aprobó adicionar '. $validatedData['dias']. ' días adicionales para dar cierre a la visita de inspección '.$observaciones];

                    $usuarios = User::where('profile', 'Delegado')
                            ->get();
                    
                    foreach ($usuarios as $usuario) {
                        $this->enviar_correos($usuario->id, $asunto_email, $datos_adicionales);
                    }

                    $this->historialInformes($validatedData['id'], 'APROBACIÓN DE SOLICITUD DE DÍAS ADICIONALES A LA VISITA', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), 'Aprobación de solicitud de '.$validatedData['dias']. ' días adicionales '.$observaciones, $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                    $successMessage = 'Se envía la solicitud de '.$validatedData['dias'].' día(s) adicional(es) a la delegatura para su aprobación.';

                }else{
                    $dias_adicionales->estado = 'RECHAZADO';
                    $historico_dias_adicionales->estado = 'RECHAZADO';

                    $asunto_email = 'Solicitud de días adicionales para la visita '.$validatedData['numero_informe']. ', rechazada';
                    $datos_adicionales = ['numero_informe' => 'Solicitud de días adicionales para la visita '. $validatedData['numero_informe']. ', rechazada',
                                                'mensaje' => $userName.' en la coordinación de la visita de inspección '. $validatedData['numero_informe'] . ' a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                    $validatedData['nit']. ', rechazó adicionar '. $validatedData['dias']. ' días adicionales para dar cierre a la visita de inspección '.$observaciones];

                    $this->enviar_correos($dias_adicionales->usuario_creacion, $asunto_email, $datos_adicionales);

                    $this->historialInformes($validatedData['id'], 'RECHAZO DE SOLICITUD DE DÍAS ADICIONALES A LA VISITA', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), 'Rechazo de solicitud de '.$validatedData['dias']. ' días adicionales '.$observaciones, $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                    $successMessage = 'Se envía la notificación al lider de la visita de inspección.';

                }

                $historico_dias_adicionales->dias = $validatedData['dias'];
                $historico_dias_adicionales->observacion = $validatedData['observaciones'];
                $historico_dias_adicionales->id_solicitud = $validatedData['id_solicitud'];
                $historico_dias_adicionales->usuario_creacion = $usuarioCreacionId;
                $historico_dias_adicionales->save();

                $dias_adicionales->save();

                return response()->json(['message' => $successMessage]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }

    /**
     * confirmar días adicionales para finalizar la visita de inspección delegatura
     * 
     * Se confirma o se rechaza la solicitud de días adicionales por parte de la delegatura
     *
     * Realiza las siguientes acciones:
     *  - Valida que la etapa del proceso sea la misma actual
     *  - Envía notificación por correo electrónico
     *  - Actualiza el historial de la visita de inspección
     *  - Actualiza los datos de la visita de inspección
     *  - Actualiza el conteo de días
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

    public function confirmar_dias_adicionales_delegatura(Request $request){
        try {

            $validatedData = $request->validate([
                'id' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'estado_etapa' => 'required',
                'numero_informe' => 'required',
                'razon_social' => 'required',
                'nit' => 'required',
                'confirmar_rechazar_solicitud' => 'required|string',
                'dias' => 'required|numeric',
                'observaciones' => 'required_if:confirmar_rechazar_solicitud,Rechazar',
                'id_solicitud' => 'required|numeric',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                $update_shits = $this->update_sheets($visita_inspeccion->id, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL,  NULL, NULL, NULL, NULL, NULL,
                                    NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'SI', $validatedData['dias'] );

                if($update_shits['status'] === 'error'){
                    return response()->json(['error' => $update_shits->json()['error']['message']], 500);
                }

                $usuarioCreacionId = Auth::id();

                $dias_adicionales = SolicitudDiaAdicional::where('id', $validatedData['id_solicitud'])
                                                        ->first();
                $historico_dias_adicionales = new HistoricoSolicitudDiaAdicional();

                $observaciones = '';

                if ($validatedData['observaciones']) {
                    $observaciones = 'con las siguientes observaciones: '. $validatedData['observaciones'];
                }

                if ($validatedData['confirmar_rechazar_solicitud'] === 'Confirmar') {
                    $dias_adicionales->estado = 'APROBADO';
                    $historico_dias_adicionales->estado = 'APROBADO';

                    $asunto_email = 'Solicitud de días adicionales para la visita '.$validatedData['numero_informe'].', aprobada';
                    $datos_adicionales = ['numero_informe' => 'Solicitud de días adicionales para la visita '. $validatedData['numero_informe'].', aprobada',
                                                'mensaje' => 'Fue aprobada la soilicitud de la visita de inspección '. $validatedData['numero_informe'] . ' a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                    $validatedData['nit']. ', se aprobo adicionar '. $validatedData['dias']. ' días adicionales para dar cierre a la visita de inspección '.$observaciones];

                    $this->historialInformes($validatedData['id'], 'APROBACIÓN DE SOLICITUD DE DÍAS ADICIONALES A LA VISITA', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), 'Aprobación de solicitud de '.$validatedData['dias']. ' días adicionales '.$observaciones, $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                    $successMessage = 'Se aprueba la solicitud de '.$validatedData['dias'].' día(s) adicional(es), se notifica al lider de la visita y la coordinación de inspección.';

                    $dias_actuales = ConteoDias::where('id_informe', $validatedData['id'])
                                                ->where('etapa','EN DESARROLLO DE VISITA DE INSPECCIÓN')
                                                ->first();

                    $diasFestivosColombia = DiaNoLaboral::pluck('dia')->toArray();

                    $fecha_limite_etapa = $this->sumarDiasHabiles($dias_actuales->fecha_inicio, $dias_actuales->dias_habiles + $validatedData['dias'], $diasFestivosColombia, $validatedData['id'], $validatedData['etapa']);

                    $dias_actuales->dias_habiles = $dias_actuales->dias_habiles + $validatedData['dias'];
                    $dias_actuales->fecha_limite_etapa = $fecha_limite_etapa;
                    
                    $dias_actuales->save();

                }else{
                    $dias_adicionales->estado = 'RECHAZADO';
                    $historico_dias_adicionales->estado = 'RECHAZADO';

                    $asunto_email = 'Solicitud de días adicionales para la visita '.$validatedData['numero_informe']. ', rechazada';
                    $datos_adicionales = ['numero_informe' => 'Solicitud de días adicionales para la visita '. $validatedData['numero_informe']. ', rechazada',
                                                'mensaje' => 'La solicitud de adicionar días a la visita de inspección '. $validatedData['numero_informe'] . ' a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                    $validatedData['nit']. ', se rechazó adicionar '. $validatedData['dias']. ' días adicionales para dar cierre a la visita de inspección '.$observaciones];

                    $this->historialInformes($validatedData['id'], 'RECHAZO DE SOLICITUD DE DÍAS ADICIONALES A LA VISITA', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), 'Rechazo de solicitud de '.$validatedData['dias']. ' días adicionales '.$observaciones, $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                    $successMessage = 'Se envía la notificación al lider de la visita de inspección y la coordinación de visitas de inspección.';

                }

                $usuarios = User::where('profile', 'Coordinador')
                            ->get();
                    
                foreach ($usuarios as $usuario) {
                    $this->enviar_correos($usuario->id, $asunto_email, $datos_adicionales);
                }

                $usuario_lider_visita = GrupoVisitaInspeccion::where('id_informe', $validatedData['id'])
                                        ->where('estado', 'ACTIVO')
                                        ->where('rol', 'Lider de visita')
                                        ->first();

                $this->enviar_correos($usuario_lider_visita->id_usuario, $asunto_email, $datos_adicionales);

                $historico_dias_adicionales->dias = $validatedData['dias'];
                $historico_dias_adicionales->observacion = $validatedData['observaciones'];
                $historico_dias_adicionales->id_solicitud = $validatedData['id_solicitud'];
                $historico_dias_adicionales->usuario_creacion = $usuarioCreacionId;
                $historico_dias_adicionales->save();

                $dias_adicionales->save();

                return response()->json(['message' => $successMessage]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }

    /**
     * Registrar hallazgos de la visita de inspección
     * 
     * Se registran los hallazgos encontrados por los inspectores
     *
     * Realiza las siguientes acciones:
     *  - Valida que la etapa del proceso sea la misma actual
     *  - Carga los documentos al drive
     *  - Envía notificación por correo electrónico
     *  - Actualiza el historial de la visita de inspección
     *  - Actualiza los datos de la visita de inspección
     *  - Actualiza el conteo de días
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

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
                'enlace_documento_hallazgo.*' => 'required|file|max:6000|mimes:pdf,doc,docx,xls,xlsx',
                'nombre_documento_hallazgo.*' => 'required|string',
                'observaciones' => 'nullable|string',
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

                    if ($request->file('enlace_documento_hallazgo')) {

                        $response_files = $this->cargarArchivosGoogle(
                            $request->file('enlace_documento_hallazgo'), 
                            $request->input('nombre_documento_hallazgo'), 
                            $visita_inspeccion->carpeta_drive,
                            $visita_inspeccion->id_entidad,
                            'VISITA DE INSPECCIÓN',
                            'HALLAZGO_VISITA_INSPECCION',
                            $persona->id_usuario,
                            'HALLAZGO_VISITA_INSPECCION',
                            $validatedData['id'],
                        );
    
                        if ($response_files['status'] == 'error') {
                            if (strpos($response_files->json()['error']['message'], 'Expected OAuth 2 access token') !== false) {
                                auth()->logout();
                                return response()->json(['error' => 'Sesión cerrada por problemas de autenticación. Por favor, vuelva a iniciar sesión.'], 401);
                            }
                            return response()->json(['error' => $response_files->json()['error']['message']], 500);
                        }
                    }
                    
                    $persona->enlace_hallazgos = 'cargado';
                    $persona->save();

                    $response_files = null;
                    
                    $usuario = User::find($persona->id_usuario);

                    $asunto_email = 'Se han cargado hallazgos de la visita de inspección '.$validatedData['numero_informe'];
                    $datos_adicionales = ['numero_informe' => 'Se han cargado hallazgos de la visita de inspección '. $validatedData['numero_informe'],
                                                'mensaje' => 'Se han cargado hallazgos de la visita de inspección '. $validatedData['numero_informe'] . ' a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                    $validatedData['nit'] . ' por el usuario ' . $usuario->name /*. ' en el enlace ' . $validatedData['registro_hallazgos'] */];

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

                    $loggedInUserId = auth()->user()->id;

                    $usuarios_actuales = json_decode($visita_inspeccion->usuario_actual,true);

                    $usuariosFiltrados = array_filter($usuarios_actuales, function($usuario) use ($loggedInUserId) {
                        return $usuario['id'] !== $loggedInUserId;
                    });

                    $usuarios = array_values($usuariosFiltrados);

                    $usuariosSinDuplicados = collect($usuarios)->unique('id')->values()->all();

                    $visita_inspeccion->usuario_actual = json_encode($usuariosSinDuplicados);
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

                $this->historialInformes($validatedData['id'], 'CARGUE DE HALLAZGOS DE LA VISITA DE INSPECCIÓN', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $validatedData['observaciones'], $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

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

    /**
     * Se carga un solo documento al google drive
     * 
     * Función para cargar archivos a google drive
     *
     * @param file $enlace_plan_visita documeto a cargar.
     * @param string $folderId id de la carpeta donde se cargara el documento.
     * @param string $id_entidad id de la entidad.
     * @param string $proceso proceso al que pertenece el documento.
     * @param string $sub_proceso sub proceso del proceso.
     * @param string $id_sub_proceso id del subproceso.
     * @param string $tipo_anexo tipo de anexo.
     * @param string $id_tipo_anexo id del tipo de anexo.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

    public function cargar_documento_individual($enlace_plan_visita, $folderId, $id_entidad, $proceso, $sub_proceso, $id_sub_proceso, $tipo_anexo, $id_tipo_anexo) {
        $accessToken = decrypt(auth()->user()->google_token);
        $uniqueCode = Str::random(8);
        $fecha = date('Ymd');
        $nameFormat = str_replace(' ', '_', $enlace_plan_visita->getClientOriginalName());
    
        $newFileName = "{$fecha}_{$uniqueCode}_{$nameFormat}";
    
        $filePath = $enlace_plan_visita->getRealPath();
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

            $archivos = new AnexoRegistro();
            $archivos->nombre = $fileName;
            $archivos->ruta = $fileUrl;
            $archivos->id_entidad = $id_entidad;
            $archivos->proceso = $proceso;
            $archivos->sub_proceso = $sub_proceso;
            $archivos->id_sub_proceso = $id_sub_proceso;
            $archivos->tipo_anexo = $tipo_anexo;
            $archivos->id_tipo_anexo = $id_tipo_anexo;
            $archivos->estado = 'ACTIVO';
            $archivos->usuario_creacion = Auth::id();
            $archivos->save();
    
            $anexos_adicionales[] = ["fileName" => $fileName, "fileUrl" => $fileUrl];
        } else {
            if (strpos($response, 'Expected OAuth 2 access token') !== false) {
                auth()->logout();
                return [
                    'status' => 'error',
                    'message' => 'Sesión cerrada finalizada. Por favor, vuelva a iniciar sesión.'
                ];
            }
            return [
                'status' => 'error',
                'message' => $response->json()['error']['message']
            ];
        }

        return [
            'status' => 'success',
            'data' => $anexos_adicionales
        ];
                
    }

    /**
     * Consolidar hallazgos
     * 
     * Se consolidan los hallazgos encontrados por los inspectores en un solo documento
     *
     * Realiza las siguientes acciones:
     *  - Valida que la etapa del proceso sea la misma actual
     *  - Carga los documentos al drive
     *  - Envía notificación por correo electrónico
     *  - Actualiza el historial de la visita de inspección
     *  - Actualiza los datos de la visita de inspección
     *  - Actualiza el conteo de días
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

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
                'registro_hallazgos_consolidados' => 'file|max:6000|mimes:pdf,doc,docx,xls,xlsx',
                'observaciones' => 'nullable|string',
                'archivo_consolidar_hallazgo.*' => 'file|max:6000|mimes:pdf,doc,docx,xls,xlsx',
                'nombre_archivo_consolidar_hallazgo.*' => 'nullable|string',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                if ($request->file('registro_hallazgos_consolidados')) {
                    $response_files = $this->cargar_documento_individual(
                        $request->file('registro_hallazgos_consolidados'), 
                        $visita_inspeccion->carpeta_drive,
                        $visita_inspeccion->id_entidad,
                        'VISITA DE INSPECCIÓN',
                        'HALLAZGOS_CONSOLIDADOS',
                        '',
                        'HALLAZGOS_CONSOLIDADOS',
                        $validatedData['id'],
                    );

                    if ($response_files['status'] == 'error') {
                        if (strpos($response_files->json()['error']['message'], 'Expected OAuth 2 access token') !== false) {
                            auth()->logout();
                            return response()->json(['error' => 'Sesión cerrada por problemas de autenticación. Por favor, vuelva a iniciar sesión.'], 401);
                        }
                        return response()->json(['error' => $response_files->json()['error']['message']], 500);
                    }
                }

                if ($request->file('archivo_consolidar_hallazgo')) {

                    $response_files = $this->cargarArchivosGoogle(
                        $request->file('archivo_consolidar_hallazgo'), 
                        $request->input('nombre_archivo_consolidar_hallazgo'), 
                        $visita_inspeccion->carpeta_drive,
                        $visita_inspeccion->id_entidad,
                        'VISITA DE INSPECCIÓN',
                        'ANEXOS_HALLAZGOS_CONSOLIDADOS',
                        '',
                        'ANEXOS_HALLAZGOS_CONSOLIDADOS',
                        $validatedData['id'],
                    );

                    if ($response_files['status'] == 'error') {
                        return response()->json(['error' => $response_files['message']], 500);
                    }
                }

                $redactor_visita = GrupoVisitaInspeccion::where('id_informe', $validatedData['id'])
                                        ->where('estado', 'ACTIVO')
                                        ->where('rol', 'Redactor')
                                        ->first();

                $asunto_email = 'Se cargó el archivo consolidado con los hallazgos de la visita de inspección '.$validatedData['numero_informe'];
                $datos_adicionales = [
                                        'numero_informe' => 'Se cargó el archivo consolidado con los hallazgos de la visita de inspección '. $validatedData['numero_informe'],
                                        'mensaje' => 'Se cargó el archivo consolidado con los hallazgos de la visita de inspección '. $validatedData['numero_informe'] . ' a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                                        $validatedData['nit'] . ' por parde del lider de la visita.' 
                                    ];

                $this->enviar_correos($redactor_visita->id_usuario, $asunto_email, $datos_adicionales); 
                
                $usuario_redactor_visita = User::where('id', $redactor_visita->id_usuario)
                                                ->first();       
                
                $proxima_etapa = 'EN ELABORACIÓN DE PROYECTO DE INFORME FINAL';

                $usuarios[] = ['id' => $usuario_redactor_visita->id, 'nombre' => $usuario_redactor_visita->name];

                $successMessage = 'Hallazgos consolidados enviados correctamente al redactor';
                
                $this->historialInformes($validatedData['id'], 'CARGUE CONSOLIDADO DE HALLAZGOS DE LA VISITA DE INSPECCIÓN', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $validatedData['observaciones'], $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                $catidad_dias_etapa = Parametro::select('dias')
                    ->where('estado', $proxima_etapa)
                    ->first();

                if ($catidad_dias_etapa->dias > 0) {
                    $estado_etapa = 'VIGENTE';
                }else{
                    $estado_etapa = $validatedData['estado_etapa'];
                }

                $usuariosSinDuplicados = collect($usuarios)->unique('id')->values()->all();

                $visita_inspeccion->etapa = $proxima_etapa;
                $visita_inspeccion->estado_etapa = $estado_etapa;
                $visita_inspeccion->usuario_actual = json_encode($usuariosSinDuplicados);
                //$visita_inspeccion->hallazgos_consolidados = $validatedData['registro_hallazgos_consolidados'];
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

    /**
     * Proyecto de informe final
     * 
     * Se carga el documento de proyecto de informe final
     *
     * Realiza las siguientes acciones:
     *  - Valida que la etapa del proceso sea la misma actual
     *  - Carga los documentos al drive
     *  - Envía notificación por correo electrónico
     *  - Actualiza el historial de la visita de inspección
     *  - Actualiza los datos de la visita de inspección
     *  - Actualiza el conteo de días
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

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
                'proyecto_informe_final' => 'file|max:6000|mimes:pdf,doc,docx,xls,xlsx',
                'anexo_informe_final.*' => 'file|max:6000|mimes:pdf,doc,docx,xls,xlsx',
                'nombre_anexo_informe_final.*' => 'nullable|string',
                'observaciones' => 'nullable|string',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                if ($request->file('proyecto_informe_final')) {
                    $response_files = $this->cargar_documento_individual(
                        $request->file('proyecto_informe_final'), 
                        $visita_inspeccion->carpeta_drive,
                        $visita_inspeccion->id_entidad,
                        'VISITA DE INSPECCIÓN',
                        'PROYECTO_INFORME_FINAL',
                        '',
                        'PROYECTO_INFORME_FINAL',
                        $validatedData['id'],
                    );

                    if ($response_files['status'] == 'error') {
                        if (strpos($response_files->json()['error']['message'], 'Expected OAuth 2 access token') !== false) {
                            auth()->logout();
                            return response()->json(['error' => 'Sesión cerrada por problemas de autenticación. Por favor, vuelva a iniciar sesión.'], 401);
                        }
                        return response()->json(['error' => $response_files->json()['error']['message']], 500);
                    }
                }

                if ($request->file('anexo_informe_final')) {

                    $response_files = $this->cargarArchivosGoogle(
                        $request->file('anexo_informe_final'), 
                        $request->input('nombre_anexo_informe_final'), 
                        $visita_inspeccion->carpeta_drive,
                        $visita_inspeccion->id_entidad,
                        'VISITA DE INSPECCIÓN',
                        'ANEXOS_PROYECTO_INFORME_FINAL',
                        '',
                        'ANEXOS_PROYECTO_INFORME_FINAL',
                        $validatedData['id'],
                    );

                    if ($response_files['status'] == 'error') {
                        return response()->json(['error' => $response_files['message']], 500);
                    }
                }

                $redactor_visita = GrupoVisitaInspeccion::where('id_informe', $validatedData['id'])
                                        ->where('estado', 'ACTIVO')
                                        ->where('rol', 'Lider de visita')
                                        ->first();

                $asunto_email = 'Se cargó el proyecto de informe final de la visita '.$validatedData['numero_informe'];
                $datos_adicionales = [
                                        'numero_informe' => 'Se cargó el proyecto de informe final de la visita '. $validatedData['numero_informe'],
                                        'mensaje' => 'Se cargó el proyecto de informe final de la visita '. $validatedData['numero_informe'] . ' a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                                        $validatedData['nit'] . ' por parte del redactor de la visita.'
                                    ];

                $this->enviar_correos($redactor_visita->id_usuario, $asunto_email, $datos_adicionales); 
                
                $usuario_redactor_visita = User::where('id', $redactor_visita->id_usuario)
                                                ->first();       
                
                $proxima_etapa = 'EN REVISIÓN DEL PROYECTO DEL INFORME FINAL';

                $usuarios[] = ['id' => $usuario_redactor_visita->id, 'nombre' => $usuario_redactor_visita->name];

                $successMessage = 'Proyecto de informe final enviado correctamente al lider de la visita de inspección';
                
                $this->historialInformes($validatedData['id'], 'ENVÍO DE PROYECTO DE INFORME FINAL DE LA VISITA DE INSPECCIÓN', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $validatedData['observaciones'], $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                $catidad_dias_etapa = Parametro::select('dias')
                        ->where('estado', $proxima_etapa)
                        ->first();

                if ($catidad_dias_etapa->dias > 0) {
                    $estado_etapa = 'VIGENTE';
                }else{
                    $estado_etapa = $validatedData['estado_etapa'];
                }

                $usuariosSinDuplicados = collect($usuarios)->unique('id')->values()->all();

                $visita_inspeccion->etapa = $proxima_etapa;
                $visita_inspeccion->estado_etapa = $estado_etapa;
                $visita_inspeccion->usuario_actual = json_encode($usuariosSinDuplicados);
                //$visita_inspeccion->proyecto_informe_final = $validatedData['proyecto_informe_final'];
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

    /**
     * Revisión del proyecto de informe final
     * 
     * Se confirma si se debe modificar el proyecto de informe final
     *
     * Realiza las siguientes acciones:
     *  - Valida que la etapa del proceso sea la misma actual
     *  - Carga los documentos al drive
     *  - Envía notificación por correo electrónico
     *  - Actualiza el historial de la visita de inspección
     *  - Actualiza los datos de la visita de inspección
     *  - Actualiza el conteo de días
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

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
                'revision_proyecto_informe_final' => 'file|max:6000|mimes:pdf,doc,docx,xls,xlsx|required_if:confirmacion_revision_proyecto_informe_final,Si',
                'observaciones' => 'required_if:confirmacion_revision_proyecto_informe_final,Si',

                'anexo_revision_proyecto_informe_final.*' => 'file|max:6000|mimes:pdf,doc,docx,xls,xlsx',
                'nombre_anexo_revision_proyecto_informe_final.*' => 'nullable|string',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                if ($request->file('revision_proyecto_informe_final')) {
                    $response_files = $this->cargar_documento_individual(
                        $request->file('revision_proyecto_informe_final'), 
                        $visita_inspeccion->carpeta_drive,
                        $visita_inspeccion->id_entidad,
                        'VISITA DE INSPECCIÓN',
                        'PROYECTO_INFORME_FINAL_MODIFICAR_1',
                        '',
                        'PROYECTO_INFORME_FINAL_MODIFICAR_1',
                        $validatedData['id'],
                    );

                    if ($response_files['status'] == 'error') {
                        if (strpos($response_files->json()['error']['message'], 'Expected OAuth 2 access token') !== false) {
                            auth()->logout();
                            return response()->json(['error' => 'Sesión cerrada por problemas de autenticación. Por favor, vuelva a iniciar sesión.'], 401);
                        }
                        return response()->json(['error' => $response_files->json()['error']['message']], 500);
                    }
                }

                if ($request->file('anexo_revision_proyecto_informe_final')) {

                    $response_files = $this->cargarArchivosGoogle(
                        $request->file('anexo_revision_proyecto_informe_final'), 
                        $request->input('nombre_anexo_revision_proyecto_informe_final'), 
                        $visita_inspeccion->carpeta_drive,
                        $visita_inspeccion->id_entidad,
                        'VISITA DE INSPECCIÓN',
                        'ANEXO_REVISION_PROYECTO_INFORME_FINAL',
                        '',
                        'ANEXO_REVISION_PROYECTO_INFORME_FINAL',
                        $validatedData['id'],
                    );

                    if ($response_files['status'] == 'error') {
                        return response()->json(['error' => $response_files['message']], 500);
                    }

                }

                if ($validatedData['confirmacion_revision_proyecto_informe_final'] === 'Si') {
                    $proxima_etapa = 'EN CORRECCIÓN DEL INFORME FINAL';

                    $asunto_email = 'Se requiere que realice correcciones al informe final de la visita '.$validatedData['numero_informe'];
                    $datos_adicionales = [
                                        'numero_informe' => 'Se requiere que realice correcciones al informe final de la visita '. $validatedData['numero_informe'],
                                        'mensaje' => 'Se requiere que realice correcciones al informe final de la visita '. $validatedData['numero_informe'] . ' a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                                        $validatedData['nit'] . ' según las siguientes observaciones ' . $validatedData['observaciones'] 
                                    ];

                    $successMessage = 'Correcciones enviadas al redactor de la visita de inspección';

                    $redactor_visita = GrupoVisitaInspeccion::where('id_informe', $validatedData['id'])
                                        ->where('estado', 'ACTIVO')
                                        ->where('rol', 'Redactor')
                                        ->first();

                    $this->enviar_correos($redactor_visita->id_usuario, $asunto_email, $datos_adicionales);

                    $usuario_redactor_visita = User::where('id', $redactor_visita->id_usuario)
                                                ->first();     
                    
                    $usuarios[] = ['id' => $usuario_redactor_visita->id, 'nombre' => $usuario_redactor_visita->name];
                                    
                }else {
                    $proxima_etapa = 'EN REVISIÓN DEL INFORME FINAL';

                    $asunto_email = 'El informe final de la visita '.$validatedData['numero_informe']. ' requiere su atención';
                    $datos_adicionales = [
                                    'numero_informe' => 'El informe final de la visita '. $validatedData['numero_informe'] . ' requiere requiere su atención',
                                    'mensaje' => 'Se cargo el informe final de la visita  '. $validatedData['numero_informe'] . ' a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                                    $validatedData['nit']
                                ];

                    $successMessage = 'Se envía notificación a la coordinación del grupo de inspección y al lider de la visita para la verificación del informe final';

                    $usuarios_coordinadores = User::where('profile', 'Coordinador')
                                            ->get();       
                    
                    foreach ($usuarios_coordinadores as $usuario) {
                        $usuarios[] = ['id' => $usuario->id, 'nombre' => $usuario->name];
                                
                        $this->enviar_correos($usuario->id, $asunto_email, $datos_adicionales);
                    }   

                    $lider = GrupoVisitaInspeccion::where('id_informe', $validatedData['id'])
                                        ->where('estado', 'ACTIVO')
                                        ->where('rol', 'Lider de visita')
                                        ->first();

                    $this->enviar_correos($lider->id_usuario, $asunto_email, $datos_adicionales); 

                    $usuario_lider = User::where('id', $lider->id_usuario)
                                                        ->first();       
                        
                    $usuarios[] = ['id' => $usuario_lider->id, 'nombre' => $usuario_lider->name];
                }

                $catidad_dias_etapa = Parametro::select('dias')
                        ->where('estado', $proxima_etapa)
                        ->first();

                if ($catidad_dias_etapa->dias > 0) {
                    $estado_etapa = 'VIGENTE';
                }else{
                    $estado_etapa = $validatedData['estado_etapa'];
                }
                
                $this->historialInformes($validatedData['id'], 'ENVÍO DE REVISIÓN PROYECTO DE INFORME FINAL DE LA VISITA DE INSPECCIÓN', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $validatedData['observaciones'], $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                $usuariosSinDuplicados = collect($usuarios)->unique('id')->values()->all();

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

    /**
     * Verificación de correcciones del informe final
     * 
     * Se envía el informe final para que tenga correcciones
     *
     * Realiza las siguientes acciones:
     *  - Valida que la etapa del proceso sea la misma actual
     *  - Envía notificación por correo electrónico
     *  - Actualiza el historial de la visita de inspección
     *  - Actualiza los datos de la visita de inspección
     *  - Actualiza el conteo de días
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

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

                $usuariosSinDuplicados = collect($usuarios)->unique('id')->values()->all();

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

    /**
     * Correcciones del informe final
     * 
     * Se carga el informe final con correcciones
     *
     * Realiza las siguientes acciones:
     *  - Valida que la etapa del proceso sea la misma actual
     *  - Carga los documentos al drive
     *  - Envía notificación por correo electrónico
     *  - Actualiza el historial de la visita de inspección
     *  - Actualiza los datos de la visita de inspección
     *  - Actualiza el conteo de días
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */
    
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
                'revision_proyecto_informe_final_corregido' => 'required|file|max:6000|mimes:pdf,doc,docx,xls,xlsx',
                'anexo_correcion_proyecto_informe_final.*' => 'file|max:6000|mimes:pdf,doc,docx,xls,xlsx',
                'nombre_anexo_correcion_proyecto_informe_final.*' => 'nullable|string',
                'observaciones' => 'nullable|string'
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                if ($request->file('revision_proyecto_informe_final_corregido')) {
                    $response_files = $this->cargar_documento_individual(
                        $request->file('revision_proyecto_informe_final_corregido'), 
                        $visita_inspeccion->carpeta_drive,
                        $visita_inspeccion->id_entidad,
                        'VISITA DE INSPECCIÓN',
                        'CORRECCION_PROYECTO_INFORME_FINAL',
                        '',
                        'CORRECCION_PROYECTO_INFORME_FINAL',
                        $validatedData['id'],
                    );

                    if ($response_files['status'] == 'error') {
                        if (strpos($response_files->json()['error']['message'], 'Expected OAuth 2 access token') !== false) {
                            auth()->logout();
                            return response()->json(['error' => 'Sesión cerrada por problemas de autenticación. Por favor, vuelva a iniciar sesión.'], 401);
                        }
                        return response()->json(['error' => $response_files->json()['error']['message']], 500);
                    }
                }

                if ($request->file('anexo_correcion_proyecto_informe_final')) {

                    $response_files = $this->cargarArchivosGoogle(
                        $request->file('anexo_correcion_proyecto_informe_final'), 
                        $request->input('nombre_anexo_correcion_proyecto_informe_final'), 
                        $visita_inspeccion->carpeta_drive,
                        $visita_inspeccion->id_entidad,
                        'VISITA DE INSPECCIÓN',
                        'ANEXOS_CORRECCION_PROYECTO_INFORME_FINAL',
                        '',
                        'ANEXOS_CORRECCION_PROYECTO_INFORME_FINAL',
                        $validatedData['id'],
                    );

                    if ($response_files['status'] == 'error') {
                        return response()->json(['error' => $response_files['message']], 500);
                    }
                }

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
                                        $validatedData['nit']
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
                
                $this->historialInformes($validatedData['id'], 'ENVÍO DE CORRECCIONES PROYECTO DE INFORME FINAL DE LA VISITA DE INSPECCIÓN', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $validatedData['observaciones'], $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                $usuariosSinDuplicados = collect($usuarios)->unique('id')->values()->all();

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

    /**
     * Remitir el informe final a la coordinación
     * 
     * Se remite el informe final a la coordinación para su revisión
     *
     * Realiza las siguientes acciones:
     *  - Valida que la etapa del proceso sea la misma actual
     *  - Carga los documentos al drive
     *  - Envía notificación por correo electrónico
     *  - Actualiza el historial de la visita de inspección
     *  - Actualiza los datos de la visita de inspección
     *  - Actualiza el conteo de días
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */
    
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
                'revision_proyecto_informe_final_coordinacinoes' => 'file|max:6000|mimes:pdf,doc,docx,xls,xlsx',

                'anexo_informe_final_coordinaciones.*' => 'file|max:6000|mimes:pdf,doc,docx,xls,xlsx',
                'nombre_anexo_informe_final_coordinaciones.*' => 'nullable|string',

                'observaciones' => 'nullable|string',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                if ($request->file('revision_proyecto_informe_final_coordinacinoes')) {
                    $response_files = $this->cargar_documento_individual(
                        $request->file('revision_proyecto_informe_final_coordinacinoes'), 
                        $visita_inspeccion->carpeta_drive,
                        $visita_inspeccion->id_entidad,
                        'VISITA DE INSPECCIÓN',
                        'PROYECTO_INFORME_FINAL_COORDINACIONES',
                        '',
                        'PROYECTO_INFORME_FINAL_COORDINACIONES',
                        $validatedData['id'],
                    );

                    if ($response_files['status'] == 'error') {
                        if (strpos($response_files->json()['error']['message'], 'Expected OAuth 2 access token') !== false) {
                            auth()->logout();
                            return response()->json(['error' => 'Sesión cerrada por problemas de autenticación. Por favor, vuelva a iniciar sesión.'], 401);
                        }
                        return response()->json(['error' => $response_files->json()['error']['message']], 500);
                    }
                }

                if ($request->file('anexo_informe_final_coordinaciones')) {

                    $response_files = $this->cargarArchivosGoogle(
                        $request->file('anexo_informe_final_coordinaciones'), 
                        $request->input('nombre_anexo_informe_final_coordinaciones'), 
                        $visita_inspeccion->carpeta_drive,
                        $visita_inspeccion->id_entidad,
                        'VISITA DE INSPECCIÓN',
                        'ANEXO_PROYECTO_INFORME_FINAL_COORDINACIONES',
                        '',
                        'ANEXO_PROYECTO_INFORME_FINAL_COORDINACIONES',
                        $validatedData['id'],
                    );

                    if ($response_files['status'] == 'error') {
                        return response()->json(['error' => $response_files['message']], 500);
                    }
                }

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
                                        $validatedData['nit']
                                    ];

                $successMessage = 'Se envía notificación a la coordinación para la verificación del informe final';

                $usuarios_coordinadores = User::where('profile', 'Coordinador')
                                            ->get();       
                    
                foreach ($usuarios_coordinadores as $usuario) {
                    $usuarios[] = ['id' => $usuario->id, 'nombre' => $usuario->name];
                            
                    $this->enviar_correos($usuario->id, $asunto_email, $datos_adicionales);
                }
                
                $this->historialInformes($validatedData['id'], 'ENVÍO DE PROYECTO DE INFORME FINAL DE LA VISITA DE INSPECCIÓN', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $validatedData['observaciones'], $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                $usuariosSinDuplicados = collect($usuarios)->unique('id')->values()->all();

                $visita_inspeccion->etapa = $proxima_etapa;
                $visita_inspeccion->estado_etapa = $estado_etapa;
                $visita_inspeccion->usuario_actual = json_encode($usuariosSinDuplicados);
                //$visita_inspeccion->informe_final = $validatedData['revision_proyecto_informe_final_coordinacinoes'];
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

    /**
     * Revisión del informe final por la coordinación
     * 
     * La coordinación revisa el informe final y lo envía a la intendencia
     *
     * Realiza las siguientes acciones:
     *  - Valida que la etapa del proceso sea la misma actual
     *  - Carga los documentos al drive
     *  - Envía notificación por correo electrónico
     *  - Actualiza el historial de la visita de inspección
     *  - Actualiza los datos de la visita de inspección
     *  - Actualiza el conteo de días
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

    public function revision_informe_final_coordinaciones(Request $request){
        try {
        $proxima_etapa = '';

            $validatedData = $request->validate([
                'id' => 'required',
                'id_entidad' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'estado_etapa' => 'required',
                'numero_informe' => 'required',
                'razon_social' => 'required',
                'nit' => 'required',
                'revision_informe_final_coordinaciones' => 'required|file|max:6000|mimes:pdf,doc,docx,xls,xlsx',

                'anexo_revision_coordinacion.*' => 'file|max:6000|mimes:pdf,doc,docx,xls,xlsx',
                'nombre_anexo_revision_coordinacion.*' => 'nullable|string',

                'observaciones' => 'nullable',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                if ($request->file('revision_informe_final_coordinaciones')) {
                    $response_files = $this->cargar_documento_individual(
                        $request->file('revision_informe_final_coordinaciones'), 
                        $visita_inspeccion->carpeta_drive,
                        $visita_inspeccion->id_entidad,
                        'VISITA DE INSPECCIÓN',
                        'INFORME_FINAL_REVISADO_COORDINACION',
                        '',
                        'INFORME_FINAL_REVISADO_COORDINACION',
                        $validatedData['id'],
                    );

                    if ($response_files['status'] == 'error') {
                        if (strpos($response_files->json()['error']['message'], 'Expected OAuth 2 access token') !== false) {
                            auth()->logout();
                            return response()->json(['error' => 'Sesión cerrada por problemas de autenticación. Por favor, vuelva a iniciar sesión.'], 401);
                        }
                        return response()->json(['error' => $response_files->json()['error']['message']], 500);
                    }
                }

                if ($request->file('anexo_revision_coordinacion')) {

                    $response_files = $this->cargarArchivosGoogle(
                        $request->file('anexo_revision_coordinacion'), 
                        $request->input('nombre_anexo_revision_coordinacion'), 
                        $visita_inspeccion->carpeta_drive,
                        $visita_inspeccion->id_entidad,
                        'VISITA DE INSPECCIÓN',
                        'ANEXO_INFORME_FINAL_REVISADO_COORDINACION',
                        '',
                        'ANEXO_INFORME_FINAL_REVISADO_COORDINACION',
                        $validatedData['id'],
                    );

                    if ($response_files['status'] == 'error') {
                        return response()->json(['error' => $response_files['message']], 500);
                    }
                }

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
                                        $validatedData['nit'] . ' con las siguientes observaciones: ' . $validatedData['observaciones']
                                    ];

                $successMessage = 'Se envía notificación a la intendencia para la verificación del informe final';

                $usuario_intendente = [];

                //Consulta de entidad
                $entidad = Entidad::where('id', $validatedData['id_entidad'])
                                ->first();


                //Busqueda de usuario intendente 
                if($entidad->naturaleza_organizacion === 'FONDO'){
                    $usuarios_intendentes = User::where('profile', 'Intendencia de fondos de empleados')
                                            ->get();
                }else {
                    $usuarios_intendentes = User::where('profile', 'Intendencia de cooperativas y otras organizaciones solidarias')
                                            ->get();
                }

                //Current usser
                foreach($usuarios_intendentes as $usuario){
                    $usuario_intendente[] = ['id' => $usuario->id, 'nombre' => $usuario->name, 'rol' => 'Intendente' ];
                }

                $visita_inspeccion->usuario_actual = json_encode($usuario_intendente);

                //Send email
                foreach($usuarios_intendentes as $usuario){
                    $this->enviar_correos($usuario->id, $asunto_email, $datos_adicionales);
                }

                $this->historialInformes($validatedData['id'], 'ENVÍO DE PROYECTO DE INFORME FINAL DE LA VISITA DE INSPECCIÓN A INTENDECIA', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $validatedData['observaciones'], $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                $usuariosSinDuplicados = collect($usuario_intendente)->unique('id')->values()->all();

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

    /**
     * Revisión del informe final por la intendencia
     * 
     * La intendencia revisa el informe final
     *
     * Realiza las siguientes acciones:
     *  - Valida que la etapa del proceso sea la misma actual
     *  - Carga los documentos al drive
     *  - Envía notificación por correo electrónico
     *  - Actualiza el historial de la visita de inspección
     *  - Actualiza los datos de la visita de inspección
     *  - Actualiza el conteo de días
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

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
                'revision_informe_final_intendente' => 'file|max:6000|mimes:pdf',

                'anexo_revision_informe_final_intendente.*' => 'file|max:6000|mimes:pdf,doc,docx,xls,xlsx',
                'nombre_anexo_revision_informe_final_intendente.*' => 'nullable|string',
                
                'observaciones' => '',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                if ($request->file('revision_informe_final_intendente')) {
                    $response_files = $this->cargar_documento_individual(
                        $request->file('revision_informe_final_intendente'), 
                        $visita_inspeccion->carpeta_drive,
                        $visita_inspeccion->id_entidad,
                        'VISITA DE INSPECCIÓN',
                        'INFORME_FINAL_REVISADO_INTENDENCIA',
                        '',
                        'INFORME_FINAL_REVISADO_INTENDENCIA',
                        $validatedData['id'],
                    );

                    if ($response_files['status'] == 'error') {
                        if (strpos($response_files->json()['error']['message'], 'Expected OAuth 2 access token') !== false) {
                            auth()->logout();
                            return response()->json(['error' => 'Sesión cerrada por problemas de autenticación. Por favor, vuelva a iniciar sesión.'], 401);
                        }
                        return response()->json(['error' => $response_files->json()['error']['message']], 500);
                    }
                }

                if ($request->file('anexo_revision_informe_final_intendente')) {

                    $response_files = $this->cargarArchivosGoogle(
                        $request->file('anexo_revision_informe_final_intendente'), 
                        $request->input('nombre_anexo_revision_informe_final_intendente'), 
                        $visita_inspeccion->carpeta_drive,
                        $visita_inspeccion->id_entidad,
                        'VISITA DE INSPECCIÓN',
                        'ANEXO_INFORME_FINAL_REVISADO_INTENDENCIA',
                        '',
                        'ANEXO_INFORME_FINAL_REVISADO_INTENDENCIA',
                        $validatedData['id'],
                    );

                    if ($response_files['status'] == 'error') {
                        return response()->json(['error' => $response_files['message']], 500);
                    }
                }

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
                                        $validatedData['nit'] . ' con las siguientes observaciones: ' . $validatedData['observaciones'] . '. Por favor ingrese a realizar la firma.'
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
              
                $this->historialInformes($validatedData['id'], 'ENVÍO DE DE INFORME FINAL PARA LA FIRMA DEL GRUPO DE INSPECCIÓN', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $validatedData['observaciones'], $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                $usuariosSinDuplicados = collect($usuarios)->unique('id')->values()->all();

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

    /**
     * Firma del informe final
     * 
     * El grupo de inspección firma el informe final, hasta que todos los usuarios firmen, no pasa a la siguiente etapa
     *
     * Realiza las siguientes acciones:
     *  - Valida que la etapa del proceso sea la misma actual
     *  - Carga los documentos al drive
     *  - Envía notificación por correo electrónico
     *  - Actualiza el historial de la visita de inspección
     *  - Actualiza los datos de la visita de inspección
     *  - Actualiza el conteo de días
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

    public function firmar_informe_final(Request $request){
        try {
        $proxima_etapa = '';

            $validatedData = $request->validate([
                'id' => 'required',
                'id_entidad' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'estado_etapa' => 'required',
                'numero_informe' => 'required',
                'razon_social' => 'required',
                'nit' => 'required',
                'informe_final_firmado' => 'required|file|max:6000|mimes:pdf',
                'observaciones' => 'nullable|string',
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

                $accessToken = decrypt(auth()->user()->google_token);
                $uniqueCode = Str::random(8);
                $fecha = date('Ymd');
                $nameFormat = str_replace(' ', '_', $request->file('informe_final_firmado.pdf'));
            
                $newFileName = "{$fecha}_{$uniqueCode}_informe_final_firmado.pdf";
            
                $filePath = $request->file('informe_final_firmado')->getRealPath();
                $fileName = $newFileName;
            
                $metadata = [
                    'name' =>  $fileName,
                    'parents' => [$visita_inspeccion->carpeta_drive],
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


                    $archivos = AnexoRegistro::where('id_tipo_anexo', $validatedData['id'])
                                                ->where('tipo_anexo', 'INFORME_FINAL_REVISADO_INTENDENCIA')
                                                ->where('estado', 'ACTIVO')
                                                ->first();
                    
                    $archivos->nombre = $fileName;
                    $archivos->ruta = $fileUrl;
                    $archivos->save();
            
                    $anexos_adicionales[] = ["fileName" => $fileName, "fileUrl" => $fileUrl];
                } else {
                    if (strpos($response, 'Expected OAuth 2 access token') !== false) {
                        auth()->logout();
                        return [
                            'status' => 'error',
                            'message' => 'Sesión cerrada finalizada. Por favor, vuelva a iniciar sesión.'
                        ];
                    }
                    return [
                        'status' => 'error',
                        'message' => $response->json()['error']['message']
                    ];
                }

                $numero_registros = GrupoVisitaInspeccion::where('id_informe', $validatedData['id'])
                                        ->where('estado', 'ACTIVO')
                                        ->whereNull('informe_firmado')
                                        ->count();

                if ($numero_registros >= 1) {
                    $proxima_etapa = 'EN FIRMA DEL INFORME FINAL POR COMISIÓN DE VISITA DE INSPECCIÓN';
                    $successMessage = 'Se registro la firma del informe final, aún faltan '. $numero_registros . ' usuarios por firmar el informe.';

                    $loggedInUserId = auth()->user()->id;

                    $usuarios_actuales = json_decode($visita_inspeccion->usuario_actual,true);

                    $usuariosFiltrados = array_filter($usuarios_actuales, function($usuario) use ($loggedInUserId) {
                        return $usuario['id'] !== $loggedInUserId;
                    });

                    $usuarios = array_values($usuariosFiltrados);

                    $usuariosSinDuplicados = collect($usuarios)->unique('id')->values()->all();

                    $visita_inspeccion->usuario_actual = json_encode($usuariosSinDuplicados);

                }else{
                    $proxima_etapa = 'EN CONFIRMACIÓN DE MEDIDA DE INTERVENCIÓN INMEDIATA';
                    $successMessage = 'Se registro la firma del informe final, se envía notificación a la intendencia para cofirmar si se necesita intervención inmediata.';

                    $asunto_email = 'Confirmar intervención inmediata de la visita de inspección '.$validatedData['numero_informe'];
                    $datos_adicionales = ['numero_informe' => 'Confirmar intervención inmediata de la visita de inspección '. $validatedData['numero_informe'],
                                                'mensaje' => 'Se requiere de su confirmación si la visita de inspección '. $validatedData['numero_informe'] . ' a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                    $validatedData['nit'].', requiere medida de intervención inmediata.'];

                    $usuario_intendente = [];

                    //Consulta de entidad
                    $entidad = Entidad::where('id', $validatedData['id_entidad'])
                                    ->first();


                    //Busqueda de usuario intendente 
                    if($entidad->naturaleza_organizacion === 'FONDO'){
                        $usuarios_intendentes = User::where('profile', 'Intendencia de fondos de empleados')
                                                ->get();
                    }else {
                        $usuarios_intendentes = User::where('profile', 'Intendencia de cooperativas y otras organizaciones solidarias')
                                                ->get();
                    }

                    //Current usser
                    foreach($usuarios_intendentes as $usuario){
                        $usuario_intendente[] = ['id' => $usuario->id, 'nombre' => $usuario->name, 'rol' => 'Intendente' ];
                    }

                    //Send email
                    foreach($usuarios_intendentes as $usuario){
                        $this->enviar_correos($usuario->id, $asunto_email, $datos_adicionales);
                    }

                    $usuariosSinDuplicados = collect($usuario_intendente)->unique('id')->values()->all();

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

                $this->historialInformes($validatedData['id'], 'REGISTRO DE INFORME FINAL FIRMADO', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $validatedData['observaciones'], $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

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

    /**
     * Confirmación de intervención inmediata
     * 
     * Si se confirma la medida de intervención inmediata, se crea el proceso de toma de posesión
     *
     * Realiza las siguientes acciones:
     *  - Valida que la etapa del proceso sea la misma actual
     *  - Carga los documentos al drive
     *  - Envía notificación por correo electrónico
     *  - Actualiza el historial de la visita de inspección
     *  - Actualiza los datos de la visita de inspección
     *  - Actualiza el conteo de días
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

    public function confirmacion_intervencion_inmediata(Request $request){
        try {
            $proxima_etapa = '';
            $usuarios = [];
            $usuarios_toma = [];

            $validatedData = $request->validate([
                'id' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'estado_etapa' => 'required',
                'numero_informe' => 'required',
                'razon_social' => 'required',
                'nit' => 'required',
                'id_entidad' => 'required',
                'codigo' => 'required',
                'sigla' => 'nullable',
                'confirmacion_intervencion_inmediata' => 'required',
                'observaciones_intervencion_inmediata' => 'required_if:confirmacion_intervencion_inmediata,Si',
                'memorando_causales_intervencion' => 'required_if:confirmacion_intervencion_inmediata,Si',
                'anexo_confirmacion_intervencion_inmediata.*' => 'nullable|file|max:6000|mimes:pdf,doc,docx,xls,xlsx',
                'nombre_anexo_confirmacion_intervencion_inmediata.*' => 'nullable|string',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                if ($request->file('anexo_confirmacion_intervencion_inmediata')) {

                    $response_files = $this->cargarArchivosGoogle(
                        $request->file('anexo_confirmacion_intervencion_inmediata'), 
                        $request->input('nombre_anexo_confirmacion_intervencion_inmediata'), 
                        $visita_inspeccion->carpeta_drive,
                        $visita_inspeccion->id_entidad,
                        'VISITA DE INSPECCIÓN',
                        'ANEXO_CONFIRMACION_INERVENCION_INMEDIATA',
                        '',
                        'ANEXO_CONFIRMACION_INERVENCION_INMEDIATA',
                        $validatedData['id'],
                    );

                    if ($response_files['status'] == 'error') {
                        if (strpos($response_files->json()['error']['message'], 'Expected OAuth 2 access token') !== false) {
                            auth()->logout();
                            return response()->json(['error' => 'Sesión cerrada por problemas de autenticación. Por favor, vuelva a iniciar sesión.'], 401);
                        }
                        return response()->json(['error' => $response_files->json()['error']['message']], 500);
                    }
                }

                if ($validatedData['confirmacion_intervencion_inmediata'] === 'Si') {
                 
                    $proxima_etapa = 'EN ENVÍO DE INFORME DE VISITA DE INSPECCIÓN PARA TRASLADO';

                    $asunto_email = 'Medida de intervención imediata';
                    $datos_adicionales = ['numero_informe' => 'Se ha determinado medida de intervención inmediata en la visita de inspección '. $validatedData['numero_informe'],
                                                'mensaje' => 'Se ha determinado medida de intervención inmediata de la para la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                    $validatedData['nit']. ', en el memorando '. $validatedData['memorando_causales_intervencion']. ', con las siguientes observaciones: '.$validatedData['observaciones_intervencion_inmediata']];
                    
                    $usuarios_coordinadores = User::where('profile', 'Delegado')
                                            ->get();
                    
                    foreach ($usuarios_coordinadores as $usuario) {
                        $usuarios_toma[] = ['id' => $usuario->id, 'nombre' => $usuario->name];
                                
                        $this->enviar_correos($usuario->id, $asunto_email, $datos_adicionales);
                    }

                    

                    $usuariosSinDuplicadosToma = collect($usuarios_toma)->unique('id');

                    $usuario_lider_visita = GrupoVisitaInspeccion::where('id_informe', $validatedData['id'])
                                                                ->where('rol', 'Lider de visita')
                                                                ->where('estado', 'ACTIVO')
                                                                ->first();

                    $usuario_lider_visita = User::where('id', $usuario_lider_visita->id_usuario)
                                        ->first();

                    $usuarios[] = ['id' => $usuario_lider_visita->id, 'nombre' => $usuario_lider_visita->name];

                    $this->historialInformes($validatedData['id'], 'CONFIRMACIÓN DE QUE SI ES NECESARIA LA INTERVENCIÓN INMEDIATA', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $validatedData['observaciones_intervencion_inmediata'], $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);
                    

                    $accessToken = decrypt(auth()->user()->google_token);
                    $folderId = "";

                    $folderData = [
                        'name' => $validatedData['codigo'].'_'.$validatedData['nit'].'_'.$validatedData['sigla'],
                        'parents' => [env('FOLDER_GOOGLE_TOMA_POSESION')],
                        'mimeType' => 'application/vnd.google-apps.folder',
                    ];

                    $response = Http::withToken($accessToken)->post('https://www.googleapis.com/drive/v3/files', $folderData);

                    if ($response->successful()) {

                        $folder = $response->json();
                        $folderId = $folder['id'];

                        $toma_posesion_general = new AsuntoEspecial();

                        $toma_posesion_general->etapa = 'EN RECEPCIÓN DE MEMORANDO CON CAUSALES DE LA TOMA DE POSESIÓN - SUPERINTENDENCIA DELEGADA';
                        $toma_posesion_general->estado_etapa = 'VIGENTE';
                        $toma_posesion_general->tipo_toma = 'TOMA DE POSESIÓN GENERAL';
                        $toma_posesion_general->usuarios_actuales = json_encode($usuariosSinDuplicadosToma);
                        $toma_posesion_general->entidad = $validatedData['id_entidad'];
                        $toma_posesion_general->carpeta_drive = $folderId;
                        $toma_posesion_general->ciclo_memorando = $validatedData['memorando_causales_intervencion'];
                        $toma_posesion_general->usuario_creacion = auth()->user()->id;
                        $toma_posesion_general->save();

                        if ($request->file('anexo_confirmacion_intervencion_inmediata')) {

                            $response_files = $this->cargarArchivosGoogle(
                                $request->file('anexo_confirmacion_intervencion_inmediata'), 
                                $request->input('nombre_anexo_confirmacion_intervencion_inmediata'), 
                                $folderId,
                                $visita_inspeccion->id_entidad,
                                'ASUNTOS_ESPECIALES',
                                'ANEXO_CONFIRMACION_INERVENCION_INMEDIATA',
                                '',
                                'ANEXO_CONFIRMACION_INERVENCION_INMEDIATA',
                                $toma_posesion_general->id,
                            );
        
                            if ($response_files['status'] == 'error') {
                                return response()->json(['error' => $response_files['message']], 500);
                            }
                        }

                        $this->historialInformes($toma_posesion_general->id, 'CREACIÓN DE PROCESO DE TOMA DE POSESIÓN GENERAL', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $validatedData['observaciones_intervencion_inmediata'], $validatedData['estado_etapa'], '', NULL, NULL, '', NULL, 'ASUNTOS_ESPECIALES');
                        $this->conteoDias($toma_posesion_general->id, 'EN RECEPCIÓN DE MEMORANDO CON CAUSALES DE LA TOMA DE POSESIÓN - SUPERINTENDENCIA DELEGADA', date('Y-m-d'), NULL, 'ASUNTOS_ESPECIALES');
                        
                    
                    } else {
                        return response()->json(['error' => $response->json()['error']['message']], 500);
                    }

                    $successMessage = 'Se envío notificación a la delegatura y se creo el proceso de toma de posesión general correctamente';

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

                $usuariosSinDuplicados = collect($usuarios)->unique('id')->values()->all();

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

    /**
     * Enviar para traslado
     * 
     * Se envía el oficio para traslado
     *
     * Realiza las siguientes acciones:
     *  - Valida que la etapa del proceso sea la misma actual
     *  - Carga los documentos al drive
     *  - Envía notificación por correo electrónico
     *  - Actualiza el historial de la visita de inspección
     *  - Actualiza los datos de la visita de inspección
     *  - Actualiza el conteo de días
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */
    
    public function enviar_traslado(Request $request) {
        try {
            $validatedData = $request->validate([
                'id' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'estado_etapa' => 'required',
                'numero_informe' => 'required',
                'razon_social' => 'required',
                'nit' => 'required',
                'ciclo_informe_traslado' => 'required',
                'observaciones' => 'nullable|string',
                'anexo_informe_visita_para_traslado.*' => 'nullable|file|max:6000|mimes:pdf,doc,docx,xls,xlsx',
                'nombre_anexo_informe_visita_para_traslado.*' => 'nullable|string',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                        ->with('etapaProceso')
                                                        ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                if ($request->file('anexo_informe_visita_para_traslado')) {

                    $response_files = $this->cargarArchivosGoogle(
                        $request->file('anexo_informe_visita_para_traslado'), 
                        $request->input('nombre_anexo_informe_visita_para_traslado'), 
                        $visita_inspeccion->carpeta_drive,
                        $visita_inspeccion->id_entidad,
                        'VISITA DE INSPECCIÓN',
                        'ANEXO_INFORME_VISITA_PARA_TRASLADO',
                        '',
                        'ANEXO_INFORME_VISITA_PARA_TRASLADO',
                        $validatedData['id'],
                    );

                    if ($response_files['status'] == 'error') {
                        if (strpos($response_files->json()['error']['message'], 'Expected OAuth 2 access token') !== false) {
                            auth()->logout();
                            return response()->json(['error' => 'Sesión cerrada por problemas de autenticación. Por favor, vuelva a iniciar sesión.'], 401);
                        }
                        return response()->json(['error' => $response_files->json()['error']['message']], 500);
                    }
                }

                $update_shits = $this->update_sheets($visita_inspeccion->id, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 
                    NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL,
                    NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, date('d/m/Y'), $validatedData['ciclo_informe_traslado']);

                if($update_shits['status'] === 'error'){
                    return response()->json(['error' => $update_shits['message']], 500);
                }

                $usuarios = [];

                /*$grupo_inspeccion = GrupoVisitaInspeccion::where('estado', 'ACTIVO')
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

                $usuariosNombres = rtrim($usuariosNombres, ', ');*/

                $asunto_email = 'Envío de oficio de traslado de la visita ' .$validatedData['numero_informe'];
                $datos_adicionales = ['numero_informe' => 'Envío de oficio de traslado de la visita '. $validatedData['numero_informe'],
                                                    'mensaje' => 'Se realizó el envío del oficio de traslado a la empresa solidaria '
                                                    . $validatedData['razon_social'] . ' identificada con el nit ' . $validatedData['nit'] ];

                $usuarios_coordinador = User::where('profile', 'Coordinador')
                                        ->get();

                foreach ($usuarios_coordinador as $key => $usuario_coordinador) {
                    $this->enviar_correos($usuario_coordinador->id, $asunto_email, $datos_adicionales);

                    $usuarios[] = ['id' => $usuario_coordinador->id, 'nombre' => $usuario_coordinador->name];
                }

                $proxima_etapa = 'EN ESPERA DE PRONUNCIAMIENTO DE LA ORGANIZACIÓN SOLIDARIA';

                $catidad_dias_etapa = Parametro::select('dias')
                        ->where('estado', $proxima_etapa)
                        ->first();

                if ($catidad_dias_etapa->dias > 0) {
                    $estado_etapa = 'VIGENTE';
                }else{
                    $estado_etapa = $validatedData['estado_etapa'];
                }

                $usuariosSinDuplicados = collect($usuarios)->unique('id')->values()->all();

                $visita_inspeccion->etapa = $proxima_etapa;
                $visita_inspeccion->estado_etapa = $estado_etapa;
                $visita_inspeccion->usuario_actual = json_encode($usuariosSinDuplicados);
                $visita_inspeccion->save();

                $this->historialInformes($validatedData['id'], 'ENVÍO DE MEMORANDO DE OFICIO DE TRASLADO', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), '', $validatedData['estado_etapa'], $validatedData['ciclo_informe_traslado'], NULL, NULL, '', NULL);
                
                $this->conteoDias($visita_inspeccion->id, $proxima_etapa, date('Y-m-d'), NULL);
                $this->actualizarConteoDias($visita_inspeccion->id, $validatedData['etapa'], date('Y-m-d'));

                $successMessage = 'Se envío notificación de memorando de oficio de traslado a la coordinación.';

                return response()->json(['message' => $successMessage]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }

    /**
     * Enviar informe para traslado a entidad
     * 
     * Se envía el informe para traslado a la entidad
     *
     * Realiza las siguientes acciones:
     *  - Valida que la etapa del proceso sea la misma actual
     *  - Carga los documentos al drive
     *  - Envía notificación por correo electrónico
     *  - Actualiza el historial de la visita de inspección
     *  - Actualiza los datos de la visita de inspección
     *  - Actualiza el conteo de días
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

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
                'observaciones' => 'nullable|string',
                'anexo_proyeccion_informe_traslado.*' => 'nullable|file|max:6000|mimes:pdf,doc,docx,xls,xlsx',
                'nombre_anexo_proyeccion_informe_traslado.*' => 'nullable|string',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                if ($request->file('anexo_proyeccion_informe_traslado')) {

                    $response_files = $this->cargarArchivosGoogle(
                        $request->file('anexo_proyeccion_informe_traslado'), 
                        $request->input('nombre_anexo_proyeccion_informe_traslado'), 
                        $visita_inspeccion->carpeta_drive,
                        $visita_inspeccion->id_entidad,
                        'VISITA DE INSPECCIÓN',
                        'ANEXO_PROYECCION_INFORME_TRASLADO',
                        '',
                        'ANEXO_PROYECCION_INFORME_TRASLADO',
                        $validatedData['id'],
                    );

                    if ($response_files['status'] == 'error') {
                        if (strpos($response_files->json()['error']['message'], 'Expected OAuth 2 access token') !== false) {
                            auth()->logout();
                            return response()->json(['error' => 'Sesión cerrada por problemas de autenticación. Por favor, vuelva a iniciar sesión.'], 401);
                        }
                        return response()->json(['error' => $response_files->json()['error']['message']], 500);
                    }
                }

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

                $this->historialInformes($validatedData['id'], 'ENVÍO DE INFORME DE TRASLADO A LA ENTIDAD SOLIDARIA', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $validatedData['observaciones'], $validatedData['estado_etapa'], $validatedData['ciclo_informe_traslado_entidad'], NULL, NULL, '', NULL);

                $successMessage = 'Se registro el envío del informe a la entidad solidaria correctamente';

                $usuariosSinDuplicados = collect($usuarios)->unique('id')->values()->all();

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

    /**
     * Registrar el pronunciamiento de la entidad 
     * 
     * se registra si la entidad se pronuncia
     *
     * Realiza las siguientes acciones:
     *  - Valida que la etapa del proceso sea la misma actual
     *  - Carga los documentos al drive
     *  - Envía notificación por correo electrónico
     *  - Actualiza el historial de la visita de inspección
     *  - Actualiza los datos de la visita de inspección
     *  - Actualiza el conteo de días
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

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
                'observaciones' => 'nullable|string',
                'anexo_registrar_pronunciamiento.*' => 'nullable|file|max:6000|mimes:pdf,doc,docx,xls,xlsx',
                'nombre_anexo_registrar_pronunciamiento.*' => 'nullable|string',

                'radicado_entrada_pronunciacion_empresa_solidaria' => 'nullable|string',
                'fecha_radicado_entrada_pronunciacion_empresa_solidaria' => 'nullable|string',
                'radicado_entrada_pronunciacion_revisoria_fiscal' => 'nullable|string',
                'fecha_radicado_entrada_pronunciacion_revisoria_fiscal' => 'nullable|string',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                $fecha_radicado_entrada_pronunciacion_empresa_solidaria = NULL;
                $fecha_radicado_entrada_pronunciacion_revisoria_fiscal = NULL;

                if($validatedData['fecha_radicado_entrada_pronunciacion_empresa_solidaria'] !== NULL){
                    $fecha_radicado_entrada_pronunciacion_empresa_solidaria = date('d/m/Y', strtotime($validatedData['fecha_radicado_entrada_pronunciacion_empresa_solidaria']));
                }

                if($validatedData['fecha_radicado_entrada_pronunciacion_revisoria_fiscal'] !== NULL){
                    $fecha_radicado_entrada_pronunciacion_revisoria_fiscal = date('d/m/Y', strtotime($validatedData['fecha_radicado_entrada_pronunciacion_revisoria_fiscal']));
                }

                $update_shits = $this->update_sheets($visita_inspeccion->id, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 
                    NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL,
                    NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, $fecha_radicado_entrada_pronunciacion_empresa_solidaria,
                    $validatedData['radicado_entrada_pronunciacion_empresa_solidaria'], $fecha_radicado_entrada_pronunciacion_revisoria_fiscal, $validatedData['radicado_entrada_pronunciacion_revisoria_fiscal']);

                if($update_shits['status'] === 'error'){
                    return response()->json(['error' => $update_shits['message']], 500);
                }

                if ($request->file('anexo_registrar_pronunciamiento')) {

                    $response_files = $this->cargarArchivosGoogle(
                        $request->file('anexo_registrar_pronunciamiento'), 
                        $request->input('nombre_anexo_registrar_pronunciamiento'), 
                        $visita_inspeccion->carpeta_drive,
                        $visita_inspeccion->id_entidad,
                        'VISITA DE INSPECCIÓN',
                        'ANEXO_REGISTRAR_PRONUNCIAMIENTO',
                        '',
                        'ANEXO_REGISTRAR_PRONUNCIAMIENTO',
                        $validatedData['id'],
                    );

                    if ($response_files['status'] == 'error') {
                        if (strpos($response_files->json()['error']['message'], 'Expected OAuth 2 access token') !== false) {
                            auth()->logout();
                            return response()->json(['error' => 'Sesión cerrada por problemas de autenticación. Por favor, vuelva a iniciar sesión.'], 401);
                        }
                        return response()->json(['error' => $response_files->json()['error']['message']], 500);
                    }
                }

                if ($validatedData['confirmacion_pronunciacion_entidad'] === 'Si') {
                    $proxima_etapa = 'EN VALORACIÓN DE LA INFORMACIÓN REMITIDA POR LA ORGANIZACIÓN SOLIDARIA';

                    $asunto_email = 'Valorar la información recibida de parte de la organización de la economía solidaria supervisada de la visita '.$validatedData['numero_informe'];
                    $datos_adicionales = ['numero_informe' => 'Valorar la información recibida de parte de la organización de la economía solidaria supervisada de la visita '. $validatedData['numero_informe'],
                                                    'mensaje' => 'Valorar la información recibida de parte de la organización de la economía solidaria supervisada de la visita '. $validatedData['numero_informe'] . ' a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
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

                    $this->historialInformes($validatedData['id'], 'REGISTRO DE PRONUNCIAMIENTO DE ENTIDAD', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $validatedData['observaciones'], $validatedData['estado_etapa'], NULL, NULL, NULL, '', NULL);

                    $successMessage = 'Se registro el pronunciamiento de la entidad solidaria correctamente';

                    $visita_inspeccion->radicado_entrada_pronunciacion_empresa_solidaria = $validatedData['radicado_entrada_pronunciacion_empresa_solidaria'];
                    $visita_inspeccion->fecha_radicado_entrada_pronunciacion_empresa_solidaria = $validatedData['fecha_radicado_entrada_pronunciacion_empresa_solidaria'];
                    $visita_inspeccion->radicado_entrada_pronunciacion_revisoria_fiscal = $validatedData['radicado_entrada_pronunciacion_revisoria_fiscal'];
                    $visita_inspeccion->fecha_radicado_entrada_pronunciacion_revisoria_fiscal = $validatedData['fecha_radicado_entrada_pronunciacion_revisoria_fiscal'];

                }else {
                    $proxima_etapa = 'EN TRASLADO DEL RESULTADO DE EVALUACIÓN DE LA RESPUESTA';

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

                    $this->historialInformes($validatedData['id'], 'REGISTRO DE NO PRONUNCIAMIENTO DE ENTIDAD', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $validatedData['observaciones'], $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

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

                $usuariosSinDuplicados = collect($usuarios)->unique('id')->values()->all();

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

    /**
     * Registrar valoración de la respuesta
     * 
     * Si la entidad se pronuncia, se registra la valoración de la respuesta de la entidad
     *
     * Realiza las siguientes acciones:
     *  - Valida que la etapa del proceso sea la misma actual
     *  - Carga los documentos al drive
     *  - Envía notificación por correo electrónico
     *  - Actualiza el historial de la visita de inspección
     *  - Actualiza los datos de la visita de inspección
     *  - Actualiza el conteo de días
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

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
                'evaluacion_respuesta' => 'required|file|max:6000|mimes:pdf,doc,docx,xls,xlsx',
                'observaciones' => 'nullable|string',
                'anexo_valoracion_informacion_remitida.*' => 'nullable|file|max:6000|mimes:pdf,doc,docx,xls,xlsx',
                'nombre_anexo_valoracion_informacion_remitida.*' => 'nullable|string',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                if ($request->file('evaluacion_respuesta')) {
                    $response_files = $this->cargar_documento_individual(
                        $request->file('evaluacion_respuesta'), 
                        $visita_inspeccion->carpeta_drive,
                        $visita_inspeccion->id_entidad,
                        'VISITA DE INSPECCIÓN',
                        'VALORACION_INFORMACION_REMITIDA',
                        '',
                        'VALORACION_INFORMACION_REMITIDA',
                        $validatedData['id'],
                    );

                    if ($response_files['status'] == 'error') {
                        if (strpos($response_files->json()['error']['message'], 'Expected OAuth 2 access token') !== false) {
                            auth()->logout();
                            return response()->json(['error' => 'Sesión cerrada por problemas de autenticación. Por favor, vuelva a iniciar sesión.'], 401);
                        }
                        return response()->json(['error' => $response_files->json()['error']['message']], 500);
                    }
                }

                if ($request->file('anexo_valoracion_informacion_remitida')) {

                    $response_files = $this->cargarArchivosGoogle(
                        $request->file('anexo_valoracion_informacion_remitida'), 
                        $request->input('nombre_anexo_valoracion_informacion_remitida'), 
                        $visita_inspeccion->carpeta_drive,
                        $visita_inspeccion->id_entidad,
                        'VISITA DE INSPECCIÓN',
                        'ANEXO_VALORACION_INFORMACION_REMITIDA',
                        '',
                        'ANEXO_VALORACION_INFORMACION_REMITIDA',
                        $validatedData['id'],
                    );

                    if ($response_files['status'] == 'error') {
                        return response()->json(['error' => $response_files['message']], 500);
                    }
                }

                $proxima_etapa = 'EN TRASLADO DEL RESULTADO DE EVALUACIÓN DE LA RESPUESTA';

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

                $this->historialInformes($validatedData['id'], 'REGISTRO DE EVALUACIÓN DE RESPUESTA DE LA ENTIDAD', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $validatedData['observaciones'], $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                $successMessage = 'Se registro la evaluación de la respuesta de la entidad solidaria correctamente';

                $usuariosSinDuplicados = collect($usuarios)->unique('id')->values()->all();

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

    /**
     * Registrar hallazgos finales
     * 
     * Se registran los hallazgos finales del informe
     *
     * Realiza las siguientes acciones:
     *  - Valida que la etapa del proceso sea la misma actual
     *  - Carga los documentos al drive
     *  - Envía notificación por correo electrónico
     *  - Actualiza el historial de la visita de inspección
     *  - Actualiza los datos de la visita de inspección
     *  - Actualiza el conteo de días
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

    public function registrar_informe_hallazgos_finales(Request $request){
        try {

        $proxima_etapa = '';

            $validatedData = $request->validate([
                'id' => 'required',
                'id_entidad' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'estado_etapa' => 'required',
                'numero_informe' => 'required',
                'razon_social' => 'required',
                'id_entidad' => 'required',
                'nit' => 'required',
                'ciclo_informe_final_hallazgos' => 'required',
                'radicado_memorando_traslado' => 'required',
                'fecha_radicado_memorando_traslado' => 'required',
                'observaciones' => 'nullable|string',
                'anexo_traslado_resultado_respuesta.*' => 'nullable|file|max:6000|mimes:pdf,doc,docx,xls,xlsx',
                'nombre_anexo_traslado_resultado_respuesta.*' => 'nullable|string',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                $update_shits = $this->update_sheets($visita_inspeccion->id, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 
                    NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL,
                    NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, $validatedData['radicado_memorando_traslado'],
                    date('d/m/Y', strtotime($validatedData['fecha_radicado_memorando_traslado'])) );

                if($update_shits['status'] === 'error'){
                    return response()->json(['error' => $update_shits['message']], 500);
                }

                if ($request->file('anexo_traslado_resultado_respuesta')) {

                    $response_files = $this->cargarArchivosGoogle(
                        $request->file('anexo_traslado_resultado_respuesta'), 
                        $request->input('nombre_anexo_traslado_resultado_respuesta'), 
                        $visita_inspeccion->carpeta_drive,
                        $visita_inspeccion->id_entidad,
                        'VISITA DE INSPECCIÓN',
                        'ANEXOS_TRASLADO_RESULTADO_RESPUESTA',
                        '',
                        'ANEXOS_TRASLADO_RESULTADO_RESPUESTA',
                        $validatedData['id'],
                    );

                    if ($response_files['status'] == 'error') {
                        if (strpos($response_files->json()['error']['message'], 'Expected OAuth 2 access token') !== false) {
                            auth()->logout();
                            return response()->json(['error' => 'Sesión cerrada por problemas de autenticación. Por favor, vuelva a iniciar sesión.'], 401);
                        }
                        return response()->json(['error' => $response_files->json()['error']['message']], 500);
                    }
                }

                $proxima_etapa = 'EN CITACIÓN A COMITE INTERNO EVALUADOR DE INSPECCIÓN';

                $catidad_dias_etapa = Parametro::select('dias')
                        ->where('estado', $proxima_etapa)
                        ->first();

                if ($catidad_dias_etapa->dias > 0) {
                    $estado_etapa = 'VIGENTE';
                }else{
                    $estado_etapa = $validatedData['estado_etapa'];
                }

                $asunto_email = 'Citar a comité interno evaluador de la visita '.$validatedData['numero_informe'];
                $datos_adicionales = ['numero_informe' => 'Citar a comité interno evaluador de la visita '. $validatedData['numero_informe'],
                                                    'mensaje' => 'Citar a comité interno evaluador de la visita '. $validatedData['numero_informe'] . ' a la entidad '. $validatedData['ciclo_informe_final_hallazgos'] . ' identificada con el nit '.
                                        $validatedData['nit']];

                $usuario_intendente = [];

                //Consulta de entidad
                $entidad = Entidad::where('id', $validatedData['id_entidad'])
                                ->first();

                //Busqueda de usuario intendente 
                if($entidad->naturaleza_organizacion === 'FONDO'){
                    $usuarios_intendentes = User::where('profile', 'Intendencia de fondos de empleados')
                                            ->get();
                }else {
                    $usuarios_intendentes = User::where('profile', 'Intendencia de cooperativas y otras organizaciones solidarias')
                                            ->get();
                }

                //Current usser
                foreach($usuarios_intendentes as $usuario){
                    $usuario_intendente[] = ['id' => $usuario->id, 'nombre' => $usuario->name, 'rol' => 'Intendente' ];
                }

                //Send email
                foreach($usuarios_intendentes as $usuario){
                    $this->enviar_correos($usuario->id, $asunto_email, $datos_adicionales);
                }

                $this->historialInformes($validatedData['id'], 'REGISTRO DE TRASLADO DEL RESULTADO DE LA EVALUCIÓN DE LA RESPUESTA DEL INFORME FINAL', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), '', $validatedData['estado_etapa'], $validatedData['ciclo_informe_final_hallazgos'], NULL, NULL, '', NULL);

                $successMessage = 'Se registro el envío del informe con los hallazgos finales correctamente';

                $usuariosSinDuplicados = collect($usuario_intendente)->unique('id')->values()->all();

                $visita_inspeccion->etapa = $proxima_etapa;
                $visita_inspeccion->estado_etapa = $estado_etapa;
                $visita_inspeccion->ciclo_informe_final_hallazgos = $validatedData['ciclo_informe_final_hallazgos'];
                $visita_inspeccion->radicado_memorando_traslado = $validatedData['radicado_memorando_traslado'];
                $visita_inspeccion->fecha_radicado_memorando_traslado = $validatedData['fecha_radicado_memorando_traslado'];
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

    /**
     * Proponer actuación administrativa
     * 
     * Se registran la proposición de la actuación administrativa
     *
     * Realiza las siguientes acciones:
     *  - Valida que la etapa del proceso sea la misma actual
     *  - Carga los documentos al drive
     *  - Envía notificación por correo electrónico
     *  - Actualiza el historial de la visita de inspección
     *  - Actualiza los datos de la visita de inspección
     *  - Actualiza el conteo de días
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

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
                'acta_actuacion_administrativa' => 'file|max:6000|mimes:pdf,doc,docx,xls,xlsx',
                'nombre_anexo_actuacion_administrativa.*' => 'nullable|string',
                'anexo_actuacion_administrativa.*' => 'nullable|file|max:6000|mimes:pdf,doc,docx,xls,xlsx',
                'observaciones' => 'nullable|string',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                if ($request->file('acta_actuacion_administrativa')) {
                    $response_files = $this->cargar_documento_individual(
                        $request->file('acta_actuacion_administrativa'), 
                        $visita_inspeccion->carpeta_drive,
                        $visita_inspeccion->id_entidad,
                        'VISITA DE INSPECCIÓN',
                        'ACTA_ACTUACION_ADMINISTRATIVA',
                        '',
                        'ACTA_ACTUACION_ADMINISTRATIVA',
                        $validatedData['id'],
                    );

                    if ($response_files['status'] == 'error') {
                        if (strpos($response_files->json()['error']['message'], 'Expected OAuth 2 access token') !== false) {
                            auth()->logout();
                            return response()->json(['error' => 'Sesión cerrada por problemas de autenticación. Por favor, vuelva a iniciar sesión.'], 401);
                        }
                        return response()->json(['error' => $response_files->json()['error']['message']], 500);
                    }
                }

                if ($request->file('anexo_actuacion_administrativa')) {

                    $response_files = $this->cargarArchivosGoogle(
                        $request->file('anexo_actuacion_administrativa'), 
                        $request->input('nombre_anexo_actuacion_administrativa'), 
                        $visita_inspeccion->carpeta_drive,
                        $visita_inspeccion->id_entidad,
                        'VISITA DE INSPECCIÓN',
                        'ANEXOS_ACTUACION_ADMINISTRATIVA',
                        '',
                        'ANEXOS_ACTUACION_ADMINISTRATIVA',
                        $validatedData['id'],
                    );

                    if ($response_files['status'] == 'error') {
                        return response()->json(['error' => $response_files['message']], 500);
                    }
                }

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

                $this->historialInformes($validatedData['id'], 'ENVÍO DE PROPOSICIÓN DE ACTUACIÓN ADMINISTRATIVA ', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), 'TIPO DE RECOMENDACIÓN: '.$validatedData['tipo_recomendacion']. ' - '. $validatedData['observaciones'], $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                $successMessage = 'Se actualizó la visita correctamente';

                $usuariosSinDuplicados = collect($usuarios)->unique('id')->values()->all();

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

    /**
     * Modificar grupo de inspección
     * 
     * Se elimina, ingresa inspectores para la visita de inspección
     *
     * Realiza las siguientes acciones:
     *  - Valida que la etapa del proceso sea la misma actual
     *  - Envía notificación por correo electrónico
     *  - Actualiza el historial de la visita de inspección
     *  - Actualiza los datos de la visita de inspección
     *  - Actualiza el conteo de días
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

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
                'observaciones' => 'nullable',
            ]);
    
            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                 ->with('etapaProceso')
                                                 ->first();

            $accessToken = decrypt(auth()->user()->google_token);
            $folderId = $visita_inspeccion->carpeta_drive;
    
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

                            $usuario_redactor_antiguo_aun_en_grupo_visita = GrupoVisitaInspeccion::where('id_usuario', $redactor_actual->id_usuario)
                                                                                    ->where('estado', 'ACTIVO')
                                                                                    ->whereNotIn('rol', ['Lider de visita', 'Redactor'])
                                                                                    ->where('id_informe', $validatedData['id'])
                                                                                    ->get();

                            if (!$usuario_redactor_antiguo_aun_en_grupo_visita->isEmpty()) {

                                if ($redactor_actual->permiso_carpeta_drive) {
                                    $deleteResponse = Http::withToken($accessToken)
                                        ->delete("https://www.googleapis.com/drive/v3/files/{$folderId}/permissions/{$redactor_actual->permiso_carpeta_drive}");
                                
                                    if (!$deleteResponse->successful()) {
                                        return response()->json(['error' => $deleteResponse->json()['error']['message']], 500);
                                    }
                                } else {
                                    return response()->json(['error' => 'Permiso no encontrado para el usuario especificado'], 404);
                                }

                            }else{
                                $accessToken = decrypt(auth()->user()->google_token);                       

                                $permissionData = [
                                    'type' => 'user',
                                    'role' => 'writer',
                                    'emailAddress' => $usuario_nuevo->email,
                                ];

                                $responseFolder = Http::withToken($accessToken)
                                                ->post("https://www.googleapis.com/drive/v3/files/{$visita_inspeccion->carpeta_drive}/permissions", $permissionData);

                                $permission = $responseFolder->json();
                                $permissionId = $permission['id'];  

                                $redactor_actual->permiso_carpeta_drive = $permissionId;
                                $redactor_actual->save();
                            
                                if (!$responseFolder->successful()) {
                                    return response()->json(['error' => $responseFolder->json()['error']['message']], 500);
                                }
                            }

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
                           

                            $accessToken = decrypt(auth()->user()->google_token);                       

                            $permissionData = [
                                'type' => 'user',
                                'role' => 'writer',
                                'emailAddress' => $usuario_nuevo->email,
                            ];

                            $responseFolder = Http::withToken($accessToken)
                                                ->post("https://www.googleapis.com/drive/v3/files/{$visita_inspeccion->carpeta_drive}/permissions", $permissionData);

                            $permission = $responseFolder->json();
                            $permissionId = $permission['id'];  

                            $grupo_visita_inspeccion->permiso_carpeta_drive = $permissionId;
                            $grupo_visita_inspeccion->save();
                            
                            if (!$responseFolder->successful()) {
                                return response()->json(['error' => $responseFolder->json()['error']['message']], 500);
                            }
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

                    $deleteResponse = Http::withToken($accessToken)
                                        ->delete("https://www.googleapis.com/drive/v3/files/{$folderId}/permissions/{$usuario->permiso_carpeta_drive}");
                                
                    if (!$deleteResponse->successful()) {
                        return response()->json(['error' => $deleteResponse->json()['error']['message']], 500);
                    }
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

                if (!empty($validatedData['observaciones'])) {
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

    /**
     * Contenidos finales expedientes
     * 
     * Se guardan todos los ciclos de vida y los documentos finales en un solo expediente
     *
     * Realiza las siguientes acciones:
     *  - Valida que la etapa del proceso sea la misma actual
     *  - Carga los documentos al drive
     *  - Envía notificación por correo electrónico
     *  - Actualiza el historial de la visita de inspección
     *  - Actualiza los datos de la visita de inspección
     *  - Actualiza el conteo de días
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

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

                'observaciones'  => 'nullable|string',
                'nombre_documento_final.*' => 'nullable|string',
                'enlace_documento.*' => 'nullable|file|max:6000|mimes:pdf,doc,docx,xls,xlsx',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                $update_shits = $this->update_sheets($visita_inspeccion->id, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 
                    NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL,
                    NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'SI', date('d/m/Y'));

                if($update_shits['status'] === 'error'){
                    return response()->json(['error' => $update_shits['message']], 500);
                }

                if ($request->file('enlace_documento')) {

                    $response_files = $this->cargarArchivosGoogle(
                        $request->file('enlace_documento'), 
                        $request->input('nombre_documento_final'), 
                        $visita_inspeccion->carpeta_drive,
                        $visita_inspeccion->id_entidad,
                        'VISITA DE INSPECCIÓN',
                        'ANEXO_EXPEDIENTE_FINAL',
                        '',
                        'ANEXO_EXPEDIENTE_FINAL',
                        $validatedData['id'],
                    );

                    if ($response_files['status'] == 'error') {
                        if (strpos($response_files->json()['error']['message'], 'Expected OAuth 2 access token') !== false) {
                            auth()->logout();
                            return response()->json(['error' => 'Sesión cerrada por problemas de autenticación. Por favor, vuelva a iniciar sesión.'], 401);
                        }
                        return response()->json(['error' => $response_files->json()['error']['message']], 500);
                    }
                }

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

                $this->historialInformes($validatedData['id'], 'CARGUE DE CONTENIDOS FINALES DEL EXPEDIENTE', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $validatedData['observaciones'], $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                $successMessage = 'Se registraron los contenidos finales correctamente';

                $usuariosSinDuplicados = collect($usuarios)->unique('id')->values()->all();

                $visita_inspeccion->etapa = $proxima_etapa;
                $visita_inspeccion->estado_etapa = $estado_etapa;
                $visita_inspeccion->usuario_actual = json_encode($usuariosSinDuplicados);
                $visita_inspeccion->ciclo_vida_contenidos_finales = $validatedData['ciclos_vida'];
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

    /**
     * Generar tablero de control
     * 
     * Se genera el tablero en formato .xls de control diligenciado
     *
     * Realiza las siguientes acciones:
     *  - Valida que la etapa del proceso sea la misma actual
     *  - Envía notificación por correo electrónico
     *  - Actualiza el historial de la visita de inspección
     *  - Actualiza los datos de la visita de inspección
     *  - Actualiza el conteo de días
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

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

        //$templatePath = public_path('templates/FTSUPE058TablerodecontrolvisistasdeinspeccinV1.xlsx');

        //$spreadsheet = IOFactory::load($templatePath);


        $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso', 'entidad', 'conteoDias', 'conteoDias', 'historiales', 
                                                            'grupoInspeccion')
                                                    ->first();

        /*    $sheet = $spreadsheet->getActiveSheet();
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
        $writer->save($outputPath);*/

        if($validatedData['etapa'] === 'EN DILIGENCIAMIENTO DEL TABLERO DE CONTROL'){
            $visita_inspeccion->estado_etapa = 'FINALIZADO';
            $visita_inspeccion->estado_informe = 'FINALIZADO';
            $visita_inspeccion->etapa = 'FINALIZADO';
            $visita_inspeccion->fecha_fin_gestion = date('Y-m-d');
            $visita_inspeccion->save();

            $this->actualizarConteoDias($visita_inspeccion->id, $validatedData['etapa'], date('Y-m-d'));

            $this->historialInformes($validatedData['id'], 'DILIGENCIAMIENTO DEL TABLERO DE CONTROL', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), NULL, $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);
            $this->historialInformes($validatedData['id'], 'FINALIZACIÓNN DE LA VISITA DE INSPECCIÓN', 'FINALIZADO', 'FINALIZADO', date('Y-m-d'), NULL, 'FINALIZADO', '', NULL, NULL, '', NULL);

            $successMessage = 'Se registro el diligenciamiento del tablero correctamente';

        }

        return response()->json(['message' => $successMessage]);
    }

    /**
     * Generar tablero de control
     * 
     * Se genera el tablero en formato .xls de control diligenciado con todas las entidades filtradas
     *
     * Realiza las siguientes acciones:
     *  - Valida que la etapa del proceso sea la misma actual
     *  - Envía notificación por correo electrónico
     *  - Actualiza el historial de la visita de inspección
     *  - Actualiza los datos de la visita de inspección
     *  - Actualiza el conteo de días
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

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

    /*public function redirectToGoogle()
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
    }*/

    /**
     * Suspender visita de inspección
     * 
     * Se suspende la visita de inspección
     *
     * Realiza las siguientes acciones:
     *  - Valida que la etapa del proceso sea la misma actual
     *  - Envía notificación por correo electrónico
     *  - Actualiza el historial de la visita de inspección
     *  - Actualiza los datos de la visita de inspección
     *  - Actualiza el conteo de días
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

    public function suspender_visita(Request $request){

        try {
            $proxima_etapa = '';

            $validatedData = $request->validate([
                'id' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'estado_etapa' => 'required',
                'id_entidad' => 'required',
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

                //Consulta de entidad
                $entidad = Entidad::where('id', $validatedData['id_entidad'])
                                ->first();

                //Busqueda de usuario intendente 
                if($entidad->naturaleza_organizacion === 'FONDO'){
                    $usuarios_intendentes = User::where('profile', 'Intendencia de fondos de empleados')
                                                ->get();
                }else {
                $usuarios_intendentes = User::where('profile', 'Intendencia de cooperativas y otras organizaciones solidarias')
                                            ->get();
                }

                //Send email
                foreach($usuarios_intendentes as $usuario){
                    $this->enviar_correos($usuario->id, $asunto_email, $datos_adicionales);
                }

                $this->historialInformes($validatedData['id'], 'SUSPENSIÓN', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $validatedData['observaciones'], $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);

                $this->conteoDias($visita_inspeccion->id, $proxima_etapa, date('Y-m-d'), NULL);
                $this->actualizarConteoDias($visita_inspeccion->id, $validatedData['etapa'], date('Y-m-d'));

                $successMessage = 'Se suspendio la visita de inspección correctamente';

                $visita_inspeccion->etapa = $proxima_etapa;
                $visita_inspeccion->estado_etapa = $estado_etapa;
                $visita_inspeccion->estado_informe = 'SUSPENDIDO';
                $visita_inspeccion->save();

                return response()->json(['message' => $successMessage]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }

    /**
     * Reanudar visita de inspección
     * 
     * Se reanuda la visita de inspección en el estado que se encontraba
     *
     * Realiza las siguientes acciones:
     *  - Valida que la etapa del proceso sea la misma actual
     *  - Envía notificación por correo electrónico
     *  - Actualiza el historial de la visita de inspección
     *  - Actualiza los datos de la visita de inspección
     *  - Actualiza el conteo de días
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

    public function reanudar_visita(Request $request){

        try {
            $proxima_etapa = '';

            $validatedData = $request->validate([
                'id' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'estado_etapa' => 'required',
                'numero_informe' => 'required',
                'razon_social' => 'required',
                'id_entidad' => 'required',
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

                $ultima_etapa = ConteoDias::where('id_informe', $validatedData['id'])
                          ->where('etapa', '!=', 'SUSPENDIDO')
                          ->select('etapa')
                          ->orderBy('id', 'DESC')
                          ->first();

                $proxima_etapa = $ultima_etapa->etapa;

                $estado_etapa = 'VIGENTE';

                $asunto_email = 'Reanudación de la visita control de la visita '.$validatedData['numero_informe'];
                $datos_adicionales = ['numero_informe' => 'Reanudación de la visita control de la visita '.$validatedData['numero_informe'],
                                                'mensaje' => 'La visita '. $validatedData['numero_informe'] . ' a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                $validatedData['nit'] . ' fue reanudada por '. Auth::user()->name . ' Por el siguiente motivo: '. $validatedData['observaciones'] ];

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

                //Consulta de entidad
                $entidad = Entidad::where('id', $validatedData['id_entidad'])
                                    ->first();

                //Busqueda de usuario intendente 
                if($entidad->naturaleza_organizacion === 'FONDO'){
                    $usuarios_intendentes = User::where('profile', 'Intendencia de fondos de empleados')
                                                ->get();
                }else {
                    $usuarios_intendentes = User::where('profile', 'Intendencia de cooperativas y otras organizaciones solidarias')
                                            ->get();
                }

                //Send email
                foreach($usuarios_intendentes as $usuario){
                    $this->enviar_correos($usuario->id, $asunto_email, $datos_adicionales);
                }

                $successMessage = 'Se reanudo la visita de inspección correctamente';

                $visita_inspeccion->etapa = $proxima_etapa;
                $visita_inspeccion->estado_etapa = $estado_etapa;
                $visita_inspeccion->estado_informe = 'VIGENTE';
                $visita_inspeccion->save();

                $this->historialInformes($validatedData['id'], 'REANUDACIÓN', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $validatedData['observaciones'], $validatedData['estado_etapa'], '', NULL, NULL, '', NULL);
                $this->conteoDias($visita_inspeccion->id, $proxima_etapa, date('Y-m-d'), NULL);
                $this->actualizarConteoDias($visita_inspeccion->id, $validatedData['etapa'], date('Y-m-d'));

                return response()->json(['message' => $successMessage]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }

    /**
     * Cambiar de entidad
     * 
     * Se cambia la entidad a la cual se esta haciendo la visita de inspección
     *
     * Realiza las siguientes acciones:
     *  - Valida que la etapa del proceso sea la misma actual
     *  - Envía notificación por correo electrónico
     *  - Actualiza el historial de la visita de inspección
     *  - Actualiza los datos de la visita de inspección
     *  - Actualiza el conteo de días
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

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

    /**
     * Eliminar archivo
     * 
     * Se elimina el archivo de google drive
     *
     * Realiza las siguientes acciones:
     *  - Envía notificación por correo electrónico
     *  - Actualiza el historial de la visita de inspección
     *  - Actualiza los datos de la visita de inspección
     *  - Actualiza el conteo de días
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

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
                        $accessToken = decrypt(auth()->user()->google_token);

                        $response = Http::withToken($accessToken)
                                ->delete("https://www.googleapis.com/drive/v3/files/{$validatedData['id_archivo']}");

                        if (!$response->successful()) {
                            if (strpos($response->json()['error']['message'], 'Expected OAuth 2 access token') !== false) {
                                auth()->logout();
                                return response()->json(['error' => 'Sesión cerrada por problemas de autenticación. Por favor, vuelva a iniciar sesión.'], 401);
                            }
                            return response()->json(['error' => $response->json()['error']['message']], 500);
                        }else{
                            
                            $anexo_registro = AnexoRegistro::where('ruta', "https://drive.google.com/file/d/{$validatedData['id_archivo']}/view")
                                                            ->where('nombre', $validatedData['nombre_archivo'])
                                                            ->first();

                            $anexo_registro->estado = 'ELIMINADO';
                            $anexo_registro->save();

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

    /**
     * Eliminar archivo
     * 
     * Se elimina el archivo de google drive y se actualiza la tabla con uno nuevo
     *
     * Realiza las siguientes acciones:
     *  - Envía notificación por correo electrónico
     *  - Actualiza el historial de la visita de inspección
     *  - Actualiza los datos de la visita de inspección
     *  - Actualiza el conteo de días
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

    public function eliminar_archivo_update(Request $request) {
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

            $accessToken = decrypt(auth()->user()->google_token);

            $response = Http::withToken($accessToken)
                            ->delete("https://www.googleapis.com/drive/v3/files/{$validatedData['id_archivo']}");
                                 
                                                     
            if (!$response->successful()) {
                return response()->json(['error' => $response->json()['error']['message']], 500);
            }else{                                                    
                $anexo_registro = AnexoRegistro::where('ruta', "https://drive.google.com/file/d/{$validatedData['id_archivo']}/view")
                                        ->where('nombre', $validatedData['nombre_archivo'])
                                        ->first();
                                         
                $anexo_registro->estado = 'ELIMINADO';
                $anexo_registro->save();
            }

            $observacion = "";
            $observacion .="Se elimina el archivo ".$validatedData['nombre_archivo'];

            $this->historialInformes(
                $validatedData['id'], 
                'ELIMINACIÓN DE ANEXO', 
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

    /**
     * Guardar plan de visita modificado
     * 
     * Se guarda una nueva versión del plan de visita
     *
     * Realiza las siguientes acciones:
     *  - Envía notificación por correo electrónico
     *  - Carga los archivos al google drive
     *  - Actualiza el historial de la visita de inspección
     *  - Actualiza los datos de la visita de inspección
     *  - Actualiza el conteo de días
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

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
                'observacion' => 'nullable|string',
                'tipo_visita_modificada' => 'required',
                'ciclo_vida_modificada' => 'required',
                'como_efectua_visita_modificada' => 'required',
                'caracter_visita_modificada' => 'required',
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

                if ($request->file('enlace_plan_visita')) {

                    $plan_actual = AnexoRegistro::where('sub_proceso', "PLAN_VISITA")
                                                ->where('id_tipo_anexo', $validatedData['id'])
                                                ->where('estado', 'ACTIVO')
                                                ->first();

                    $accessToken = decrypt(auth()->user()->google_token);

                    $enlace = $plan_actual->ruta;

                    preg_match('/\/d\/(.*?)\//', $enlace, $coincidencias);

                    $response = Http::withToken($accessToken)
                                ->delete("https://www.googleapis.com/drive/v3/files/{$coincidencias[1]}");


                    if (!$response->successful()) {
                        if (strpos($response->json()['error']['message'], 'Expected OAuth 2 access token') !== false) {
                            auth()->logout();
                            return response()->json(['error' => 'Sesión cerrada por problemas de autenticación. Por favor, vuelva a iniciar sesión.'], 401);
                        }
                        return response()->json(['error' => $response->json()['error']['message']], 500);
                    }else{                                                                                                            
                        $plan_actual->estado = 'ELIMINADO';
                        $plan_actual->save();
                    }

                    $response_files = $this->cargar_documento_individual(
                        $request->file('enlace_plan_visita'), 
                        $visita_inspeccion->carpeta_drive,
                        $visita_inspeccion->id_entidad,
                        'VISITA DE INSPECCIÓN',
                        'PLAN_VISITA',
                        '',
                        'PLAN_VISITA',
                        $validatedData['id'],
                    );

                    if ($response_files['status'] == 'error') {
                        return response()->json(['error' => $response_files['message']], 500);
                    }
                }

                if ($request->file('anexo_plan_visita_modificado')) {

                    $response_files = $this->cargarArchivosGoogle(
                        $request->file('anexo_plan_visita_modificado'), 
                        $request->input('nombre_anexo_plan_visita_modificado'), 
                        $visita_inspeccion->carpeta_drive,
                        $visita_inspeccion->id_entidad,
                        'VISITA DE INSPECCIÓN',
                        'ANEXOS_PLAN_VISITA',
                        '',
                        'ANEXOS_PLAN_VISITA',
                        $validatedData['id'],
                    );

                    if ($response_files['status'] == 'error') {
                        return response()->json(['error' => $response_files['message']], 500);
                    }
                }

                $update_shits = $this->update_sheets($visita_inspeccion->id, NULL, NULL, NULL, NULL, $validatedData['ciclo_vida_modificada'], NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, date('d/m/Y') , $validatedData['como_efectua_visita_modificada'], $validatedData['caracter_visita_modificada'], $validatedData['tipo_visita_modificada']);

                if($update_shits['status'] === 'error'){
                    return response()->json(['error' => $update_shits->json()['error']['message']], 500);
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
                $validatedData['nit'] . ' que se ejecutara de manera '. $validatedData['tipo_visita_modificada']];

                foreach ($usuarios_coordinadores as $usuario) {
                    $usuarios[] = ['id' => $usuario->id, 'nombre' => $usuario->name];

                    $this->enviar_correos($usuario->id, $asunto_email, $datos_adicionales);
                }

                $usuariosSinDuplicados = collect($usuarios)->unique('id')->values()->all();

                $visita_inspeccion->etapa = $proxima_etapa;
                $visita_inspeccion->estado_etapa = $estado_etapa;

                $visita_inspeccion->ciclo_vida = $validatedData['ciclo_vida_modificada'];
                $visita_inspeccion->como_efectua_visita = $validatedData['como_efectua_visita_modificada'];
                $visita_inspeccion->tipo_visita = $validatedData['tipo_visita_modificada'];
                $visita_inspeccion->caracter_visita = $validatedData['caracter_visita_modificada'];



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

    /*public function pdfDownload(Request $request) {
        try {

            $validatedData = $request->validate([
                'enlace_informe_final_intendencia' => 'required',
                'id' => 'required',
                'etapa' => 'required',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                $accessToken = $request->user()->google_token;

                $client = new Client();
                $client->setAccessToken($accessToken);

                $service = new Drive($client);

                $response = $service->files->get($validatedData['enlace_informe_final_intendencia'], ['alt' => 'media']);

                $httpClient = $client->authorize();
                $response = $httpClient->request('GET', "https://www.googleapis.com/drive/v3/files/{$validatedData['enlace_informe_final_intendencia']}?alt=media", [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                    ],
                ]);

                $content = $response->getBody()->getContents();

                $uniqueId = Carbon::now()->format('YmdHisv');

                $filename = $uniqueId.'_informe_final.pdf';

                $filePath = public_path('docs/' . $filename);

                if (!file_exists(public_path('docs'))) {
                    mkdir(public_path('docs'), 0755, true);
                }

                file_put_contents($filePath, $content);

                return response()->json(['message' => $filename]);

            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }*/

    /**
     * Citación a comité interno
     * 
     * Seregistra la fecha y hora de la citación al comité interno
     *
     * Realiza las siguientes acciones:
     *  - Envía notificación por correo electrónico
     *  - Carga los archivos al google drive
     *  - Actualiza el historial de la visita de inspección
     *  - Actualiza los datos de la visita de inspección
     *  - Actualiza el conteo de días
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

    public function citacion_comite_interno_evaluador(Request $request){
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
                'fecha_hora_citacion' => 'required',
                'observaciones' => 'nullable|string',

                'anexo_citacion_comite_interon.*' => 'nullable|file|max:6000|mimes:pdf,doc,docx,xls,xlsx',
                'nombre_anexo_citacion_comite_interon.*' => 'nullable|string',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                $date = date('d/m/Y', strtotime($validatedData['fecha_hora_citacion']));

                $update_shits = $this->update_sheets($visita_inspeccion->id, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 
                    NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL,
                    NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'SI', $date);

                if($update_shits['status'] === 'error'){
                    return response()->json(['error' => $update_shits['message']], 500);
                }

                if ($request->file('anexo_citacion_comite_interon')) {

                    $response_files = $this->cargarArchivosGoogle(
                        $request->file('anexo_citacion_comite_interon'), 
                        $request->input('nombre_anexo_citacion_comite_interon'), 
                        $visita_inspeccion->carpeta_drive,
                        $visita_inspeccion->id_entidad,
                        'VISITA DE INSPECCIÓN',
                        'ANEXOS_CITACION_COMITE_INTERNO',
                        '',
                        'ANEXOS_CITACION_COMITE_INTERNO',
                        $validatedData['id'],
                    );

                    if ($response_files['status'] == 'error') {
                        if (strpos($response_files->json()['error']['message'], 'Expected OAuth 2 access token') !== false) {
                            auth()->logout();
                            return response()->json(['error' => 'Sesión cerrada por problemas de autenticación. Por favor, vuelva a iniciar sesión.'], 401);
                        }
                        return response()->json(['error' => $response_files->json()['error']['message']], 500);
                    }
                }

                $obserciones = ''; 

                if($validatedData['observaciones'] === ''){
                    $obserciones = 'Se cita a comite interno evaluador el día '.$validatedData['fecha_hora_citacion'];
                }else{
                    $obserciones = 'Se cita a comite interno evaluador el día '.$validatedData['fecha_hora_citacion'].' con las siguientes observaciones '.$validatedData['observaciones'];
                }

                $proxima_etapa = 'EN PROPOSICIÓN DE ACTUACIÓN ADMINISTRATIVA';

                $catidad_dias_etapa = Parametro::select('dias')
                        ->where('estado', $proxima_etapa)
                        ->first();

                if ($catidad_dias_etapa->dias > 0) {
                    $estado_etapa = 'VIGENTE';
                }else{
                    $estado_etapa = $validatedData['estado_etapa'];
                }

                $asunto_email = 'Citación a comité interno evaluador de la visita '.$validatedData['numero_informe'];
                $datos_adicionales = ['numero_informe' => 'Citación a comité interno evaluador de la visita '. $validatedData['numero_informe'],
                                                    'mensaje' => 'Citación a comité interno evaluador de la visita '. $validatedData['numero_informe'] . ' a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                                        $validatedData['nit'].', el día '.$validatedData['fecha_hora_citacion']];

                
                $usuario = User::where('profile', 'Delegado')
                                ->first();

                if ($usuario) {
                    $this->enviar_correos($usuario->id, $asunto_email, $datos_adicionales); 
                    $usuarios[] = ['id' => $usuario->id, 'nombre' => $usuario->name];
                }

                $this->historialInformes($validatedData['id'], 'REGISTRO DE CITACIÓN A COMITE INTERNO EVALUADOR DE INSPECCIÓN', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $obserciones, $validatedData['estado_etapa'], NULL, NULL, NULL, '', NULL);

                $successMessage = 'Se registro la fecha y hora del comite interno evaluador de inspección correctamente';

                $usuariosSinDuplicados = collect($usuarios)->unique('id')->values()->all();

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

    /**
     * Guardar documento adicional a la visita de inspección
     * 
     * Se registran los documentos adicionales a la visita de inspección
     *
     * Realiza las siguientes acciones:
     *  - Envía notificación por correo electrónico
     *  - Carga los archivos al google drive
     *  - Actualiza el historial de la visita de inspección
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

    public function guardar_documento_adicional_visita_inspeccion(Request $request){
        try {

            $validatedData = $request->validate([
                'id' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'estado_etapa' => 'required',
                'numero_informe' => 'required',
                'razon_social' => 'required',
                'nit' => 'required',

                'anexo_visita_inspeccion.*' => 'nullable|file|max:6000|mimes:pdf,doc,docx,xls,xlsx',
                'nombre_anexo_visita_inspeccion.*' => 'nullable|string',
            ]);

            $visita_inspeccion =  VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                if ($request->file('anexo_visita_inspeccion')) {

                    $response_files = $this->cargarArchivosGoogle(
                        $request->file('anexo_visita_inspeccion'), 
                        $request->input('nombre_anexo_visita_inspeccion'), 
                        $visita_inspeccion->carpeta_drive,
                        $visita_inspeccion->entidad->id,
                        'VISITA DE INSPECCIÓN',
                        'ANEXO_VISITA_INSPECCION',
                        '',
                        'ANEXO_VISITA_INSPECCION',
                        $validatedData['id'],
                    );

                    if ($response_files['status'] == 'error') {
                        if (strpos($response_files->json()['error']['message'], 'Expected OAuth 2 access token') !== false) {
                            auth()->logout();
                            return response()->json(['error' => 'Sesión cerrada por problemas de autenticación. Por favor, vuelva a iniciar sesión.'], 401);
                        }
                        return response()->json(['error' => $response_files->json()['error']['message']], 500);
                    }
                }

                $asunto_email = 'Registro de anexos a la visita de inspección '.$validatedData['numero_informe'];
                $datos_adicionales = ['numero_informe' => 'Registro de anexos a la visita de inspección'. $validatedData['numero_informe']. ' de la entidad '.$validatedData['razon_social'],
                                                    'mensaje' => 'Se registraron anexos a la visita '. $validatedData['numero_informe'] . ' a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                                                    $validatedData['nit']
                                    ];


                foreach ( json_decode($visita_inspeccion->usuario_actual) as $usuario) {
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

    /**
     * Consulta de días no laborales
     * 
     * Se consultan los días que no son laborales
     *
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

    public function consultar_dias_no_laborales(){
        try {

            $diasFestivos = DiaNoLaboral::pluck('dia')->toArray();

            $successMessage = $diasFestivos;

            return response()->json(['message' => $successMessage]);
            
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }

    /**
     * Crear registro google sheets
     * 
     * Crea el registro de la visita en la hoja de google sheets
     *
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

    public function create_sheets($a, $b, $c=NULL, $d, $e=NULL, $f=NULL, $g=NULL, $h=NULL, $i=NULL, $j=NULL, $k=NULL, $l, $m, $n, $o) {
        $accessToken = decrypt(auth()->user()->google_token);
        $spreadsheetId = env('LIBRO_SHEETS_VISITAS_ASOCIATIVA');

        $range = 'CONSOLIDADO!A:A';

        $response = Http::withToken($accessToken)
            ->get("https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}/values/{$range}");


        $values = $response->json()['values'] ?? [];

        $lastRow = count($values) + 1;  

        $range = "CONSOLIDADO!A{$lastRow}:C{$lastRow}";


        $values = [
            [
                $a, $b, $c, $d, $e, $f, $g, $h, $i, $j, $k, $l, $m, $n, $o
            ],
        ];
    
        $body = [
            'values' => $values
        ];
    
        $response = Http::withToken($accessToken)
            ->withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->post("https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}/values/{$range}:append?valueInputOption=USER_ENTERED", $body);

        if ($response->successful()) {
            return [
                        'status' => 'success',
                        'message' => 'Datos enviados a la hoja de cálculo exitosamente.'
            ];
        } else {
            if (strpos($response->body(), 'Invalid Credentials') !== false) {
                auth()->logout();
                        return [
                            'status' => 'error',
                            'message' => 'Sesión cerrada. Por favor, vuelva a iniciar sesión.'
                        ];
            }
            return [
                'status' => 'error',
                'message' => $response->json()['error']['message']
            ];
        }
    }

    /**
     * Actualiza registro google sheets
     * 
     * Actualiza el registro de la visita en la hoja de google sheets basado en el id
     *
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

    public function update_sheets($id, $a = NULL, $b = NULL, $c = NULL, $d = NULL, $e = NULL, $f = NULL, $g = NULL, $h = NULL, $i = NULL, $j = NULL, $k = NULL, $l = NULL,
    $m = NULL, $n = NULL, $o = NULL, $p = NULL, $q = NULL, $r = NULL, $s = NULL, $t = NULL, $u = NULL, $v = NULL, $w = NULL, $x = NULL, $y = NULL, $z = NULL,
    $aa = NULL, $ab = NULL, $ac = NULL, $ad = NULL, $ae = NULL, $af = NULL, $ag = NULL, $ah = NULL, $ai = NULL, $aj = NULL, $ak = NULL, $al = NULL,
    $am = NULL, $an = NULL, $ao = NULL, $ap = NULL, $aq = NULL, $ar = NULL, $as = NULL, $at = NULL, $au = NULL, $av = NULL, $aw = NULL, $ax = NULL, $ay = NULL, $az = NULL,
    $ba = NULL, $bb = NULL, $bc = NULL, $bd = NULL, $be = NULL, $bf = NULL, $bg = NULL, $bh = NULL, $bi = NULL, $bj = NULL, $bk = NULL, $bl = NULL,
    $bm = NULL, $bn = NULL, $bo = NULL, $bp = NULL, $bq = NULL, $br = NULL, $bs = NULL, $bt = NULL, $bu = NULL, $bv = NULL, $bw = NULL, $bx = NULL, $by = NULL, $bz = NULL,
    $ca = NULL, $cb = NULL, $cc = NULL, $cd = NULL, $ce = NULL, $cf = NULL, $cg = NULL, $ch = NULL, $ci = NULL, $cj = NULL, $ck = NULL, $cl = NULL,
    $cm = NULL, $cn = NULL, $co = NULL, $cp = NULL, $cq = NULL, $cr = NULL, $cs = NULL, $ct = NULL, $cu = NULL, $cv = NULL, $cw = NULL, $cx = NULL, $cy = NULL, $cz = NULL,
    ) {
        $accessToken = decrypt(auth()->user()->google_token);
        $spreadsheetId = env('LIBRO_SHEETS_VISITAS_ASOCIATIVA');
    
        $range = 'CONSOLIDADO!A:A';
    
        $response = Http::withToken($accessToken)
            ->get("https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}/values/{$range}");
    
        if (!$response->successful()) {
            return [
                'status' => 'error',
                'message' => 'No se pudo obtener los datos de la hoja de cálculo.'
            ];
        }
    
        $values = $response->json()['values'];
    
        $rowNumber = null;
        foreach ($values as $index => $value) {
            if (isset($value[0]) && $value[0] == $id) {
                $rowNumber = $index + 1;
                break;
            }
        }
    
        if (!$rowNumber) {
            return [
                'status' => 'error',
                'message' => 'No se encontró el ID en la hoja de cálculo.'
            ];
        }
    
        $updateRange = 'CONSOLIDADO!A'.$rowNumber.':CZ'.$rowNumber;
    
        $updateValues = [
            [
                $a, $b, $c, $d, $e, $d, $d, $d, $d, $d, $d, $d, $d, $d, $d, $d, $d, $q, $r, $s, $t, $u, $v, $w, $x,  $d,, $z,

            ],
        ];
    
        $body = [
            'values' => $updateValues
        ];
    
        $updateResponse = Http::withToken($accessToken)
            ->withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->put("https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}/values/{$updateRange}?valueInputOption=USER_ENTERED", $body);
    
        if ($updateResponse->successful()) {
            return [
                'status' => 'success',
                'message' => 'Datos actualizados exitosamente en la hoja de cálculo.'
            ];
        } else {
            return [
                'status' => 'error',
                'message' => $updateResponse->json()['error']['message']
            ];
        }
    }

    /**
     * Crear histórico registro google sheets
     * 
     * Crea el registro histórico de la visita en la hoja de google sheets
     *
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

    public function create_history_sheets($a, $b, $c, $d, $e, $f, $g, $h) {
        $accessToken = decrypt(auth()->user()->google_token);
        $spreadsheetId = env('LIBRO_SHEETS_VISITAS_ASOCIATIVA');

        $range = 'VAL_OBSERVACIONES';

        $values = [
            [
                $a, $b, $c, $d, $e, $f, $g, $h
            ],
        ];
    
        $body = [
            'values' => $values
        ];
    
        $response = Http::withToken($accessToken)
            ->withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->post("https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}/values/{$range}:append?valueInputOption=USER_ENTERED", $body);

        if ($response->successful()) {
            return [
                        'status' => 'success',
                        'message' => 'Datos enviados a la hoja de cálculo exitosamente.'
            ];
        } else {
            if (strpos($response->body(), 'Invalid Credentials') !== false) {
                auth()->logout();
                        return [
                            'status' => 'error',
                            'message' => 'Sesión cerrada. Por favor, vuelva a iniciar sesión.'
                        ];
            }
            return [
                'status' => 'error',
                'message' => $response->json()['error']['message']
            ];
        }
    }

    /**
     * Registro de los radicados de los comunicados 
     * 
     * Se registran los datos de los oficios enviados a las empresas solidarias antes de una visita de inspección
     *
     * Realiza las siguientes acciones:
     *  - Envía notificación por correo electrónico
     *  - Carga los archivos al google drive
     *  - Actualiza los datos en la hoja de sheets
     *  - Actualiza el historial de la visita de inspección
     *  - Actualiza los datos de la visita de inspección
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

    public function registrar_comunicado_previo_visita(Request $request){
        try {

            $validatedData = $request->validate([
                'id' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'estado_etapa' => 'required',
                'numero_informe' => 'required',
                'razon_social' => 'required',
                'nit' => 'required',

                'observaciones' => 'nullable|string',
                'radicado_salida_comunicado_visita_empresa_solidaria' => 'required|string',
                'fecha_radicado_salida_comunicado_visita_empresa_solidaria' => 'required|string',
                'radicado_salida_comunicado_visita_revisoria_fiscal' => 'required|string',
                'fecha_radicado_salida_comunicado_visita_revisoria_fiscal' => 'required|string',

                'anexo_oficio_previo_visita.*' => 'nullable|file|max:6000|mimes:pdf,doc,docx,xls,xlsx',
                'nombre_anexo_oficio_previo_visita.*' => 'nullable|string',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                $update_shits = $this->update_sheets($visita_inspeccion->id, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, $validatedData['radicado_salida_comunicado_visita_empresa_solidaria'], $validatedData['radicado_salida_comunicado_visita_revisoria_fiscal'] );
                
                if($update_shits['status'] === 'error'){
                    return response()->json(['error' => $update_shits->json()['error']['message']], 500);
                }

                if ($request->file('anexo_oficio_previo_visita')) {

                    $response_files = $this->cargarArchivosGoogle(
                        $request->file('anexo_oficio_previo_visita'), 
                        $request->input('nombre_anexo_oficio_previo_visita'), 
                        $visita_inspeccion->carpeta_drive,
                        $visita_inspeccion->id_entidad,
                        'VISITA DE INSPECCIÓN',
                        'ANEXOS_OFICIOS_PREVIO_VISITA',
                        '',
                        'ANEXOS_OFICIOS_PREVIO_VISITA',
                        $validatedData['id'],
                    );

                    if ($response_files['status'] == 'error') {
                        if (strpos($response_files->json()['error']['message'], 'Expected OAuth 2 access token') !== false) {
                            auth()->logout();
                            return response()->json(['error' => 'Sesión cerrada por problemas de autenticación. Por favor, vuelva a iniciar sesión.'], 401);
                        }
                        return response()->json(['error' => $response_files->json()['error']['message']], 500);
                    }
                }

                $obserciones = ''; 

                if($validatedData['observaciones'] !== ''){
                    $obserciones = ' con las siguientes observaciones '.$validatedData['observaciones'];
                }

                $asunto_email = 'Se actualizó el número de rádicado y fecha de los requerimientos previos a la visita '.$validatedData['numero_informe'];
                $datos_adicionales = ['numero_informe' => 'Se actualizó el número de rádicado y fecha de los requerimientos previos a la visita '. $validatedData['numero_informe'],
                                                    'mensaje' => 'Se actualizó el número de rádicado y fecha de los requerimientos previos a la visita '. $validatedData['numero_informe'] . ' a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                                        $validatedData['nit'].' '.$obserciones];
                
                $this->enviar_correos(Auth::id(), $asunto_email, $datos_adicionales); 

                $this->historialInformes($validatedData['id'], 'REGISTRO DE RADICADOS ENVIADOS PREVIOS A LA VISITA', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $validatedData['observaciones'], $validatedData['estado_etapa'], NULL, NULL, NULL, '', NULL);

                $successMessage = 'Se registraron los oficios de salida correctamente';

                $visita_inspeccion->radicado_salida_comunicado_visita_empresa_solidaria = $validatedData['radicado_salida_comunicado_visita_empresa_solidaria'];
                $visita_inspeccion->fecha_radicado_salida_comunicado_visita_empresa_solidaria = $validatedData['fecha_radicado_salida_comunicado_visita_empresa_solidaria'];
                $visita_inspeccion->radicado_salida_comunicado_visita_revisoria_fiscal = $validatedData['radicado_salida_comunicado_visita_revisoria_fiscal'];
                $visita_inspeccion->fecha_radicado_salida_comunicado_visita_revisoria_fiscal = $validatedData['fecha_radicado_salida_comunicado_visita_revisoria_fiscal'];
                $visita_inspeccion->save();

                return response()->json(['message' => $successMessage]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }
    

    /**
     * Registro de los radicados de los oficios de traslado 
     * 
     * Se registran los datos de los oficios enviados a las empresas solidarias antes
     *
     * Realiza las siguientes acciones:
     *  - Envía notificación por correo electrónico
     *  - Carga los archivos al google drive
     *  - Actualiza los datos en la hoja de sheets
     *  - Actualiza el historial de la visita de inspección
     *  - Actualiza los datos de la visita de inspección
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

    public function registrar_oficio_traslado(Request $request){
        try {

            $validatedData = $request->validate([
                'id' => 'required',
                'etapa' => 'required',
                'estado' => 'required',
                'estado_etapa' => 'required',
                'numero_informe' => 'required',
                'razon_social' => 'required',
                'nit' => 'required',

                'observaciones' => 'nullable|string',
                'radicado_salida_traslado_empresa_solidaria' => 'required|string',
                'fecha_radicado_salida_traslado_empresa_solidaria' => 'required|string',
                'radicado_salida_traslado_revisoria_fiscal' => 'required|string',
                'fecha_radicado_salida_traslado_revisoria_fiscal' => 'required|string',

                'anexo_requerimiento_traslado.*' => 'nullable|file|max:6000|mimes:pdf,doc,docx,xls,xlsx',
                'nombre_anexo_requerimiento_traslado.*' => 'nullable|string',
            ]);

            $visita_inspeccion = VisitaInspeccion::where('id', $validatedData['id'])
                                                    ->with('etapaProceso')
                                                    ->first();

            if($visita_inspeccion->etapa !== $validatedData['etapa']){
                $successMessage = 'Estado de la etapa no permitido';
                return response()->json(['error' => $successMessage], 404);
            }else {

                if ($request->file('anexo_requerimiento_traslado')) {

                    $response_files = $this->cargarArchivosGoogle(
                        $request->file('anexo_requerimiento_traslado'), 
                        $request->input('nombre_anexo_requerimiento_traslado'), 
                        $visita_inspeccion->carpeta_drive,
                        $visita_inspeccion->id_entidad,
                        'VISITA DE INSPECCIÓN',
                        'ANEXOS_OFICIOS_TRASLADO',
                        '',
                        'ANEXOS_OFICIOS_TRASLADO',
                        $validatedData['id'],
                    );

                    if ($response_files['status'] == 'error') {
                        if (strpos($response_files->json()['error']['message'], 'Expected OAuth 2 access token') !== false) {
                            auth()->logout();
                            return response()->json(['error' => 'Sesión cerrada por problemas de autenticación. Por favor, vuelva a iniciar sesión.'], 401);
                        }
                        return response()->json(['error' => $response_files->json()['error']['message']], 500);
                    }
                }

                $update_shits = $this->update_sheets($visita_inspeccion->id, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 
                NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL,
                NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, $validatedData['radicado_salida_traslado_empresa_solidaria'], 
                date('d/m/Y', strtotime($validatedData['fecha_radicado_salida_traslado_empresa_solidaria']))  , $validatedData['radicado_salida_traslado_revisoria_fiscal'], 
                date('d/m/Y', strtotime($validatedData['fecha_radicado_salida_traslado_revisoria_fiscal'])));

                if($update_shits['status'] === 'error'){
                    return response()->json(['error' => $update_shits['message']], 500);
                }

                $obserciones = ''; 

                if($validatedData['observaciones'] !== ''){
                    $obserciones = ' con las siguientes observaciones: '.$validatedData['observaciones'];
                }

                $asunto_email = 'Se actualizó el número de rádicado y fecha de los requerimientos previos a la visita '.$validatedData['numero_informe'];
                $datos_adicionales = ['numero_informe' => 'Se actualizó el número de rádicado y fecha de los requerimientos previos a la visita '. $validatedData['numero_informe'],
                                                    'mensaje' => 'Se actualizó el número de rádicado y fecha de los requerimientos previos a la visita '. $validatedData['numero_informe'] . ' a la entidad '. $validatedData['razon_social'] . ' identificada con el nit '.
                                        $validatedData['nit'].' '.$obserciones];

                $this->historialInformes($validatedData['id'], 'REGISTRO DE RADICADOS ENVIADOS PREVIOS A LA VISITA', $validatedData['etapa'], $validatedData['estado'], date('Y-m-d'), $validatedData['observaciones'], $validatedData['estado_etapa'], NULL, NULL, NULL, '', NULL);

                $successMessage = 'Se registraron los oficios de salida correctamente';

                $visita_inspeccion->radicado_salida_traslado_empresa_solidaria = $validatedData['radicado_salida_traslado_empresa_solidaria'];
                $visita_inspeccion->fecha_radicado_salida_traslado_empresa_solidaria = $validatedData['fecha_radicado_salida_traslado_empresa_solidaria'];
                $visita_inspeccion->radicado_salida_traslado_revisoria_fiscal = $validatedData['radicado_salida_traslado_revisoria_fiscal'];
                $visita_inspeccion->fecha_radicado_salida_traslado_revisoria_fiscal = $validatedData['fecha_radicado_salida_traslado_revisoria_fiscal'];
                $visita_inspeccion->save();

                return response()->json(['message' => $successMessage]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }

    public function validateDrive(){
        // Acceso a Google Drive
        $accessToken = decrypt(auth()->user()->google_token);
        
        // Prueba de conexión a Google Drive
        $driveTestResponse = Http::withToken($accessToken)->get('https://www.googleapis.com/drive/v3/files', [
            'pageSize' => 1
        ]);

        if (!$driveTestResponse->successful()) {
            if (strpos($driveTestResponse->json()['error']['message'], 'Expected OAuth 2 access token') !== false) {
                auth()->logout();
                return response()->json(['error' => 'Sesión cerrada por problemas de autenticación. Por favor, vuelva a iniciar sesión.'], 401);
            }
            return response()->json(['error' => 'Error de conexión con Google Drive: ' . $driveTestResponse->json()['error']['message']], 500);
        }else{
            return response()->json(['message' => 'Conexión con Google Drive establecida correctamente'], 200);
        }
    }
    

}

