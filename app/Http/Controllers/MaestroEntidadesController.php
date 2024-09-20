<?php

namespace App\Http\Controllers;

use App\Models\AnexoRegistro;
use App\Models\HistorialVisitas;
use App\Models\MaestroEntidad;
use App\Models\Parametro;
use App\Models\User; 

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

use Illuminate\Validation\Rule;

class MaestroEntidadesController extends Controller
{
    public function consultar_maestro_entidades(Request $request) {

        $entidad = MaestroEntidad::query();
    
        if ($request->filled('codigo_entidad')) {
            $entidad->where('codigo_entidad', $request->codigo_entidad);
        }

        if ($request->filled('nit_entidad')) {
            $entidad->where('nit', 'like', '%' . $request->nit_entidad . '%');
        }

        if ($request->filled('nombre_entidad')) {
            $entidad->where('razon_social', 'like', '%' . $request->nombre_entidad . '%');
        }

        if ($request->filled('estado')) {
            $entidad->where('estado', $request->estado );
        }

        if ($request->filled('vigilada_supersolidaria_segun_depuracion')) {
            $entidad->where('vigilada_supersolidaria_segun_depuracion', $request->vigilada_supersolidaria_segun_depuracion);
        }else{
            $entidad->where('vigilada_supersolidaria_segun_depuracion', 'SI');
        }

        if (!$request->filled(['codigo_entidad', 'nit_entidad', 'nombre_entidad', 'estado', 'vigilada_supersolidaria_segun_depuracion'])) {
            $entidad->get();
        }

        $entidad = $entidad->orderby('id', 'desc')->paginate(10);

        return view('consultar_entidad_base_maestra', [
            'entidades' => $entidad,
        ]);
    }

    public function crear_entidad_maestra(Request $request) {

        try {
            $validatedData = $request->validate([
                'codigo_entidad' => [
                    'nullable',
                    'numeric',
                    Rule::unique('maestro_entidades')->where(function ($query) {
                        return $query->where('estado', '!=', 'ELIMINADA');
                    }),
                ],
                'nit' => [
                    'nullable',
                    'numeric',
                    Rule::unique('maestro_entidades')->where(function ($query) {
                        return $query->where('estado', '!=', 'ELIMINADA');
                    }),
                ],
                'razon_social' => 'required|string',
                'sigla' => 'nullable|string',
                'nivel_supervision' => 'nullable|numeric',
                'naturaleza_organizacion' => 'nullable|string',
                'tipo_organizacion' => 'nullable|string',
                'categoria' => 'nullable|string|required_if:tipo_organizacion,FONDOS DE EMPLEADOS',
                'grupo_niif' => 'nullable|string',
                'ciudad_municipio' => 'required|string',
                'departamento' => 'required|string',
                'direccion' => 'required|string',
                'numero_asociados' => 'nullable|numeric',
                'numero_empleados' => 'nullable|numeric',
                'total_activos' => 'nullable|string',
                'total_pasivos' => 'nullable|string',
                'total_patrimonio' => 'nullable|string',
                'total_ingresos' => 'nullable|string',
                'fecha_ultimo_reporte' => 'nullable',
                'estado_matricula_rues' => 'required|string',
                'ano_renovacion_matricula' => 'required|string',
                'fecha_renovacion_matricula' => 'required|string',
                'permiten_notificacion_correo_electronico' => 'nullable|string|required_if:estado_matricula_rues,ACTIVA',
                'correo_notificaciones_judiciales' => 'nullable|email|required_if:permiten_notificacion_correo_electronico,SI',
                'en_liquidacion_rues' => 'nullable|string|required_if:estado_matricula_rues,ACTIVA',
                'tipo_liquidacion_rues' => 'nullable|string|required_if:en_liquidacion_rues,SI',
                'otro_tipo_liquidacion' => 'nullable|string|required_if:tipo_liquidacion_rues,OTRA',
                'entidad_que_vigila_rues' => 'nullable|string|required_if:estado_matricula_rues,ACTIVA',
                'otro_ente_vigilancia_rues' => 'nullable|string|required_if:entidad_que_vigila_rues,OTRA',
                'objeto_social' => 'nullable|string|required_if:estado_matricula_rues,ACTIVA',
                'ecomun' => 'nullable|string|required_if:estado_matricula_rues,ACTIVA',
                'cafetera' => 'nullable|string|required_if:estado_matricula_rues,ACTIVA',
                'vigilada_supersolidaria_segun_depuracion_crear' => 'nullable|string|required_if:estado_matricula_rues,ACTIVA',
                'entidad_debe_vigilar_segun_depuracion' => 'nullable|string|required_if:vigilada_supersolidaria_segun_depuracion_crear,NO',
                'otro_ente_vigilancia' => 'nullable|string|required_if:entidad_debe_vigilar_segun_depuracion,OTRA',
                'certificado_rues' => 'required|file|max:6000|mimes:pdf',
                'representate_legal' => 'required|string',
                'correo_representate_legal' => 'nullable|email',
                'telefono_representate_legal' => 'nullable|string',
                'tipo_revisor_fiscal' => 'nullable|string',
                'razon_social_revision_fiscal' => 'nullable|string|required_if:tipo_revisor_fiscal,PERSONA JURÍDICA',
                'nombre_revisor_fiscal' => 'nullable|string',
                'direccion_revisor_fiscal' => 'nullable|string',
                'telefono_revisor_fiscal' => 'nullable|string',
                'correo_revisor_fiscal' => 'nullable|email',
                'observaciones' => 'nullable|string',
            ]);

            $usuarioCreacionId = Auth::id();
            $estado = 'ACTIVA';

            $accessToken = auth()->user()->google_token;
            $folderId = "";

            $folderData = [
                'name' => $validatedData['codigo_entidad'].'_'.$validatedData['nit'].'_'.$validatedData['sigla'],
                'parents' => [env('FOLDER_GOOGLE_OTROS_EXPEDIENTES')],
                'mimeType' => 'application/vnd.google-apps.folder',
            ];

            $response = Http::withToken($accessToken)->post('https://www.googleapis.com/drive/v3/files', $folderData);

            if ($response->successful()) {
                $folder = $response->json();
                $folderId = $folder['id'];
            } else {
                return response()->json(['error' => $response->json()['error']['message']], 500);
            }

            if ($validatedData['estado_matricula_rues'] === 'CANCELADA') {
                $estado = 'MATRICULA CANCELADA EN RUES';
            }else if ($validatedData['estado_matricula_rues'] === 'ACTIVA' && $validatedData['vigilada_supersolidaria_segun_depuracion_crear'] === 'SI' && $validatedData['entidad_que_vigila_rues'] !== 'SUPERINTENDENCIA DE LA ENCONOMÍA SOLLIDARIA (SES)') {
                $estado = 'EN REQUERIMIENTO CORRECIÓN DE RUES (VIGILADA)';
            }else if ($validatedData['estado_matricula_rues'] === 'ACTIVA' && $validatedData['vigilada_supersolidaria_segun_depuracion_crear'] === 'SI' && $validatedData['entidad_que_vigila_rues'] === 'SUPERINTENDENCIA DE LA ENCONOMÍA SOLLIDARIA (SES)') {
                $estado = 'EN REQUERIMIENTO DE DOCUMENTACIÓN PARA CONTROL DE LEGALIDAD';
            }else if ($validatedData['estado_matricula_rues'] === 'ACTIVA' && $validatedData['vigilada_supersolidaria_segun_depuracion_crear'] !== 'SI' && $validatedData['entidad_que_vigila_rues'] === 'SUPERINTENDENCIA DE LA ENCONOMÍA SOLLIDARIA (SES)') {
                $estado = 'EN REQUERIMIENTO CORRECIÓN DE RUES (NO VIGILADA)';
            }else if ($validatedData['estado_matricula_rues'] === 'ACTIVA' && $validatedData['vigilada_supersolidaria_segun_depuracion_crear'] === 'SI' && $validatedData['en_liquidacion_rues'] === 'SI' && $validatedData['tipo_liquidacion_rues'] !== 'LEY 1727') {
                $estado = 'EN LIQUIDACIÓN '.$validatedData['tipo_liquidacion_rues'];
            }else if ($validatedData['estado_matricula_rues'] === 'ACTIVA' && 
                    $validatedData['vigilada_supersolidaria_segun_depuracion_crear'] === 'SI' &&
                    $validatedData['en_liquidacion_rues'] === 'SI' && 
                    $validatedData['tipo_liquidacion_rues'] !== 'LEY 1727') {
                $estado = 'EN LIQUIDACIÓN '.$validatedData['tipo_liquidacion_rues'];
            }else {
                $estado = 'NO VIGILADA';
            }

            $entidad = new MaestroEntidad();
            $entidad->codigo_entidad = $validatedData['codigo_entidad'];
            $entidad->nit = $validatedData['nit'];
            $entidad->sigla = $validatedData['sigla'];
            $entidad->razon_social = $validatedData['razon_social'];
            $entidad->nivel_supervision = $validatedData['nivel_supervision'];
            $entidad->tipo_organizacion = $validatedData['tipo_organizacion'];
            $entidad->categoria = $validatedData['categoria'];
            $entidad->grupo_niif = $validatedData['grupo_niif'];

            $entidad->naturaleza_organizacion = $validatedData['naturaleza_organizacion'];
            $entidad->ciudad_municipio = $validatedData['ciudad_municipio'];
            $entidad->departamento = $validatedData['departamento'];
            $entidad->direccion = $validatedData['direccion'];
            $entidad->numero_asociados = $validatedData['numero_asociados'];
            $entidad->numero_empleados = $validatedData['numero_empleados'];
            $entidad->total_activos = $validatedData['total_activos'];
            $entidad->total_pasivos = $validatedData['total_pasivos'];
            $entidad->total_patrimonio = $validatedData['total_patrimonio'];
            $entidad->total_ingresos = $validatedData['total_ingresos'];
            $entidad->fecha_ultimo_reporte = $validatedData['fecha_ultimo_reporte'];
            
            $entidad->objeto_social = $validatedData['objeto_social'];
            $entidad->entidad_que_vigila_rues = $validatedData['entidad_que_vigila_rues'];
            $entidad->estado_matricula_rues = $validatedData['estado_matricula_rues'];
            $entidad->vigilada_supersolidaria_segun_depuracion = $validatedData['vigilada_supersolidaria_segun_depuracion_crear'];
            $entidad->entidad_debe_vigilar_segun_depuracion = $validatedData['entidad_debe_vigilar_segun_depuracion'];
            $entidad->correo_notificaciones_judiciales = $validatedData['correo_notificaciones_judiciales'];
            $entidad->permiten_notificacion_correo_electronico = $validatedData['permiten_notificacion_correo_electronico'];
            $entidad->en_liquidacion_rues = $validatedData['en_liquidacion_rues'];
            $entidad->tipo_liquidacion_rues = $validatedData['tipo_liquidacion_rues'];
            $entidad->otro_tipo_liquidacion = $validatedData['fecha_ultimo_reporte'];
            $entidad->ecomun = $validatedData['ecomun'];
            $entidad->cafetera = $validatedData['cafetera'];
            $entidad->ano_renovacion_matricula = $validatedData['ano_renovacion_matricula'];
            $entidad->fecha_renovacion_matricula = $validatedData['fecha_renovacion_matricula'];
            $entidad->certificado_rues = $validatedData['certificado_rues'];
            $entidad->codigos_actividades_financieras = $validatedData['fecha_ultimo_reporte'];
            $entidad->otro_ente_vigilancia = $validatedData['otro_ente_vigilancia'];

            $entidad->representate_legal = $validatedData['representate_legal'];
            $entidad->correo_representate_legal = $validatedData['correo_representate_legal'];
            $entidad->telefono_representate_legal = $validatedData['telefono_representate_legal'];
            $entidad->tipo_revisor_fiscal = $validatedData['tipo_revisor_fiscal'];
            $entidad->razon_social_revision_fiscal = $validatedData['razon_social_revision_fiscal'];
            $entidad->nombre_revisor_fiscal = $validatedData['nombre_revisor_fiscal'];
            $entidad->direccion_revisor_fiscal = $validatedData['direccion_revisor_fiscal'];
            $entidad->telefono_revisor_fiscal = $validatedData['telefono_revisor_fiscal'];
            $entidad->usuario_creacion = $usuarioCreacionId;
            $entidad->observaciones = $validatedData['observaciones'];
            $entidad->carpeta_drive = $folderId;

            $entidad->estado = $estado;
            $entidad->save();

            if ($request->file('certificado_rues')) {
                $response_files = $this->cargar_documento_individual(
                    $request->file('certificado_rues'), 
                    $folderId,
                    $entidad->id,
                    'ENTIDAD_INDIVIDUAL',
                    NULL,
                    NULL,
                    NULL,
                    NULL,
                    'CERTIFICADO_RUES'
                );

                if ($response_files['status'] == 'error') {
                    return response()->json(['error' => $response_files['message']], 500);
                }
            }


            $this->historialInformes('', 'CREACIÓN DE ENTIDAD', '', $estado, date('Y-m-d'), $validatedData['observaciones'], '', '', NULL, NULL, $entidad->id, NULL);

            
            $successMessage = 'Entidad creada correctamente';

            return response()->json(['message' => $successMessage]);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }

    public function consultar_entidad_maestra($id)
    {
        $entidad_datos = MaestroEntidad::where('id', $id)
                                    ->with('historiales.usuario')
                                    ->with('usuario')
                                    ->with('anexos')
                                    ->first();
        $usuarios = User::orderby('name', 'ASC')->get();
        return view('consultar_entidad_maestra', [
            'usuariosTotales' => $usuarios,
            'entidad_datos' => $entidad_datos,
        ]);
    }

    //helpers

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

                dd('respuesta desde la función', $responseAnexos);

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
                dd('No ingreso a la función');
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

    public function cargar_documento_individual($enlace_plan_visita, $folderId, $id_entidad, $proceso, $sub_proceso, $id_sub_proceso, $tipo_anexo, $id_tipo_anexo, $fileNameAsigned) {
        $accessToken = auth()->user()->google_token;
        $uniqueCode = Str::random(8);
        $fecha = date('Ymd');

        if ($fileNameAsigned === '' || $fileNameAsigned === NULL) {
            $nameFormat = str_replace(' ', '_', $enlace_plan_visita->getClientOriginalName());
            $newFileName = "{$fecha}_{$uniqueCode}_{$nameFormat}";
        }else{
            $newFileName = "{$fecha}_{$uniqueCode}_{$fileNameAsigned}";
        }
    
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
        $historial_informe->proceso= 'ENTIDAD_INDIVIDUAL';
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
}
