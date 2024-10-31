<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;

use Illuminate\Http\Request;
use App\Models\Entidad;  
use App\Models\Lugares;
use App\Models\VisitaInspeccion;  
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Http;

class EntidadController extends Controller
{

    /**
     * Muestra el formulario para crear entidades.
     *
     *
     * @return \Illuminate\View\View Devuelve la vista 'crear_entidad' con los filtros solicitados.
    */

    public function crear()
    {
        $lugares = Lugares::get();
        return view('crear_entidad',['lugares'=>$lugares]);
    }

    /**
     * Muestra el formulario para consultar entidades.
     *
     *
     * @return \Illuminate\View\View Devuelve la vista 'consultar_entidad' con los filtros solicitados.
    */

    public function consultar()
    {
        return view('consultar_entidad');
    }

    /**
     * Crear entidad
     * 
     * Se registra una entidad para realizar el díagnóstico
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

    public function guardar(Request $request)
    {
        try {

            $validatedData = $request->validate([
                'codigo_entidad' => [
                    'required',
                    'numeric',
                    Rule::unique('entidades')->where(function ($query) {
                        return $query->where('estado', '!=', 'ELIMINADA');
                    }),
                ],
                'nit' => [
                    'required',
                    'numeric',
                    Rule::unique('entidades')->where(function ($query) {
                        return $query->where('estado', '!=', 'ELIMINADA');
                    }),
                ],
                'razon_social' => 'required|string',
                'sigla' => 'nullable|string',
                'nivel_supervision' => 'required|numeric',
                'naturaleza_organizacion' => 'required|string',
                'tipo_organizacion' => 'required|string',
                'categoria' => 'nullable|string|required_if:tipo_organizacion,FONDOS DE EMPLEADOS',
                'grupo_niif' => 'required|string',
                'incluye_sarlaft' => 'nullable|string',
                'ciudad_municipio' => 'required|string',
                'departamento' => 'required|string',
                'direccion' => 'required|string',
                'numero_asociados' => 'required|numeric',
                'numero_empleados' => 'required|numeric',
                'total_activos' => 'required|string',
                'total_pasivos' => 'required|string',
                'total_patrimonio' => 'required|string',
                'total_ingresos' => 'required|string',
                'fecha_ultimo_reporte' => 'required',
                'fecha_corte_visita' => 'required',

                'representate_legal' => 'required|string',
                'correo_representate_legal' => 'required|email',
                'telefono_representate_legal' => 'nullable|string',
                'tipo_revisor_fiscal' => 'required|string',
                'razon_social_revision_fiscal' => 'nullable|string|required_if:tipo_revisor_fiscal,PERSONA JURÍDICA',
                'nombre_revisor_fiscal' => 'required|string',
                'direccion_revisor_fiscal' => 'required|string',
                'telefono_revisor_fiscal' => 'required|string',
                'correo_revisor_fiscal' => 'required|email',
            ]);

            $visitaExistente = VisitaInspeccion::where('id_entidad', $validatedData['codigo_entidad'])
                ->whereNotIn('etapa', ['FINALIZADO', 'CANCELADO'])
                ->exists();

            if ($visitaExistente) {
                return response()->json(['error' => 'La entidad ya está asociada a una visita de inspección que no ha finalizado o sido cancelada.'], 400);
            }

            $usuarioCreacionId = Auth::id();

            $entidad = new Entidad();
            $entidad->codigo_entidad = $validatedData['codigo_entidad'];
            $entidad->nit = $validatedData['nit'];
            $entidad->sigla = $validatedData['sigla'];
            $entidad->razon_social = $validatedData['razon_social'];
            $entidad->nivel_supervision = $validatedData['nivel_supervision'];
            $entidad->tipo_organizacion = $validatedData['tipo_organizacion'];
            $entidad->categoria = $validatedData['categoria'];
            $entidad->grupo_niif = $validatedData['grupo_niif'];
            $entidad->incluye_sarlaft = $validatedData['incluye_sarlaft'];
            $entidad->naturaleza_organizacion = $validatedData['naturaleza_organizacion'];
            $entidad->ciudad_municipio = $validatedData['ciudad_municipio'];
            $entidad->departamento = $validatedData['departamento'];
            $entidad->numero_asociados = $validatedData['numero_asociados'];
            $entidad->numero_empleados = $validatedData['numero_empleados'];
            $entidad->total_activos = $validatedData['total_activos'];
            $entidad->total_pasivos = $validatedData['total_pasivos'];
            $entidad->total_patrimonio = $validatedData['total_patrimonio'];
            $entidad->total_ingresos = $validatedData['total_ingresos'];
            $entidad->fecha_ultimo_reporte = $validatedData['fecha_ultimo_reporte'];
            $entidad->direccion = $validatedData['direccion'];
            $entidad->fecha_ultimo_reporte = $validatedData['fecha_ultimo_reporte'];
            $entidad->fecha_corte_visita = $validatedData['fecha_corte_visita'];
            $entidad->representate_legal = $validatedData['representate_legal'];
            $entidad->correo_representate_legal = $validatedData['correo_representate_legal'];
            $entidad->telefono_representate_legal = $validatedData['telefono_representate_legal'];
            $entidad->tipo_revisor_fiscal = $validatedData['tipo_revisor_fiscal'];
            $entidad->razon_social_revision_fiscal = $validatedData['razon_social_revision_fiscal'];
            $entidad->nombre_revisor_fiscal = $validatedData['nombre_revisor_fiscal'];
            $entidad->direccion_revisor_fiscal = $validatedData['direccion_revisor_fiscal'];
            $entidad->telefono_revisor_fiscal = $validatedData['telefono_revisor_fiscal'];
            $entidad->correo_revisor_fiscal = $validatedData['correo_revisor_fiscal'];
            $entidad->usuario_creacion = $usuarioCreacionId;
            $entidad->estado = 'ACTIVA';
            $entidad->save();

            $successMessage = 'Entidad creada correctamente';

            return response()->json(['message' => $successMessage]);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }

    /**
     * Muestra el formulario para consultar entidades con filtros.
     *
     *
     * @return \Illuminate\View\View Devuelve la vista 'consultar_entidad' con los filtros solicitados.
    */

    public function consultarEntidades(Request $request)
    {
        $entidades = Entidad::query();

        $entidades->where('estado', 'ACTIVA');

        if ($request->filled('codigo')) {
            $entidades->where('codigo_entidad', 'like', '%' . $request->codigo . '%');
        }

        if ($request->filled('nit')) {
            $entidades->where('nit', 'like', '%' . $request->nit . '%');
        }

        if ($request->filled('nombre')) {
            $entidades->where('razon_social', 'like', '%' . $request->nombre . '%');
        }

        if (!$request->filled(['codigo', 'nit', 'nombre'])) {
            $entidades->get();
        }

        $entidades = $entidades->paginate(10);

        return view('consultar_entidad', compact('entidades'));
    }

    /**
     * Eliminar entidad
     * 
     * Se elimina una entidad para realizar el díagnóstico
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

    public function eliminar_entidad(Request $request)
    {
        try {


            $validatedData = $request->validate([
                'motivo' => 'required|string',
                'id_entidad' => 'required|string',
            ]);
    
            $entidad = Entidad::find($validatedData['id_entidad']);
    
            if (!$entidad) {
                return response()->json(['error' => 'Entidad no encontrada'], 404);
            }
    
            $entidad->estado = 'ELIMINADA';
            $entidad->motivo = $validatedData['motivo'];
            $entidad->save();
    
            return response()->json(['mensaje' => 'Usuario eliminado correctamentes'], 200);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
        
    }

    /**
     * Muestra el formulario para editar una entidad.
     *
     * @param int $id id de la entidad a actualizar
     *
     * @return \Illuminate\View\View Devuelve la vista 'crear_entidad' con los datos a editar.
    */

    public function editar($id)
    {
        $entidad = Entidad::findOrFail($id);
        $lugares = Lugares::get();
        return view('crear_entidad',['entidad'=> $entidad,'lugares'=>$lugares]);

    }

    /**
     * Editar entidad
     * 
     * Se editan los datos de una entidad para realizar el díagnóstico
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

    public function actualizar(Request $request, $id)
    {
        try {
            $validatedData = $request->validate([
                'codigo_entidad' => [
                    'required',
                    'numeric',
                    Rule::unique('entidades', 'codigo_entidad')->ignore($id)->where(function ($query) {
                        return $query->where('estado', '!=', 'ELIMINADA');
                    })
                ],
                'nit' => [
                    'required',
                    'numeric',
                    Rule::unique('entidades', 'nit')->ignore($id)->where(function ($query) {
                        return $query->where('estado', '!=', 'ELIMINADA');
                    })
                ],
                'razon_social' => 'required|string',
                'sigla' => 'nullable|string',
                'nivel_supervision' => 'required|numeric',
                'tipo_organizacion' => 'required|string',
                'categoria' => 'nullable|string',
                'grupo_niif' => 'required|string',
                'incluye_sarlaft' => 'nullable|string',
                'naturaleza_organizacion' => 'required|string',
                'ciudad_municipio' => 'required|string',
                'departamento' => 'required|string',
                'direccion' => 'required|string',
                'numero_asociados' => 'required|numeric',
                'numero_empleados' => 'required|numeric',
                'total_activos' => 'required|string',
                'total_pasivos' => 'required|string',
                'total_patrimonio' => 'required|string',
                'total_ingresos' => 'required|string',
                'fecha_ultimo_reporte' => 'required',
                'fecha_corte_visita' => 'required',

                'representate_legal' => 'required|string',
                'correo_representate_legal' => 'required|email',
                'telefono_representate_legal' => 'nullable|string',
                'tipo_revisor_fiscal' => 'required|string',
                'razon_social_revision_fiscal' => 'nullable|string|required_if:tipo_revisor_fiscal,PERSONA JURÍDICA',
                'nombre_revisor_fiscal' => 'required|string',
                'direccion_revisor_fiscal' => 'required|string',
                'telefono_revisor_fiscal' => 'required|string',
                'correo_revisor_fiscal' => 'required|email',
            ]);

            $usuarioCreacionId = Auth::id();

            $entidad = Entidad::findOrFail($id);

            $entidad->codigo_entidad = $validatedData['codigo_entidad'];
            $entidad->nit = $validatedData['nit'];
            $entidad->sigla = $validatedData['sigla'];
            $entidad->razon_social = $validatedData['razon_social'];
            $entidad->nivel_supervision = $validatedData['nivel_supervision'];
            $entidad->tipo_organizacion = $validatedData['tipo_organizacion'];
            $entidad->categoria = $validatedData['categoria'];
            $entidad->grupo_niif = $validatedData['grupo_niif'];
            $entidad->incluye_sarlaft = $validatedData['incluye_sarlaft'];
            $entidad->naturaleza_organizacion = $validatedData['naturaleza_organizacion'];
            $entidad->ciudad_municipio = $validatedData['ciudad_municipio'];
            $entidad->departamento = $validatedData['departamento'];
            $entidad->numero_asociados = $validatedData['numero_asociados'];
            $entidad->numero_empleados = $validatedData['numero_empleados'];
            $entidad->total_activos = $validatedData['total_activos'];
            $entidad->total_pasivos = $validatedData['total_pasivos'];
            $entidad->total_patrimonio = $validatedData['total_patrimonio'];
            $entidad->total_ingresos = $validatedData['total_ingresos'];
            $entidad->fecha_ultimo_reporte = $validatedData['fecha_ultimo_reporte'];
            $entidad->direccion = $validatedData['direccion'];
            $entidad->fecha_ultimo_reporte = $validatedData['fecha_ultimo_reporte'];
            $entidad->fecha_corte_visita = $validatedData['fecha_corte_visita'];
            $entidad->representate_legal = $validatedData['representate_legal'];
            $entidad->correo_representate_legal = $validatedData['correo_representate_legal'];
            $entidad->telefono_representate_legal = $validatedData['telefono_representate_legal'];
            $entidad->tipo_revisor_fiscal = $validatedData['tipo_revisor_fiscal'];
            $entidad->razon_social_revision_fiscal = $validatedData['razon_social_revision_fiscal'];
            $entidad->nombre_revisor_fiscal = $validatedData['nombre_revisor_fiscal'];
            $entidad->direccion_revisor_fiscal = $validatedData['direccion_revisor_fiscal'];
            $entidad->telefono_revisor_fiscal = $validatedData['telefono_revisor_fiscal'];
            $entidad->correo_revisor_fiscal = $validatedData['correo_revisor_fiscal'];
            $entidad->usuario_creacion = $usuarioCreacionId;
            $entidad->save();

            $successMessage = 'Entidad actualizada correctamente';

            return response()->json(['message' => $successMessage]);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }

    /**
     * consultar entidades diagnóstico
     * 
     * Devuelve los datos de las entidades con los filtros aplicados para realizar el diagnóstico
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

    public function consultarEntidadesDiagnostico(Request $request)
    {
        $entidades = Entidad::query();

        $entidades->where('estado', 'ACTIVA');

        if ($request->filled('codigo')) {
            $entidades->where('codigo_entidad', 'like', '%' . $request->codigo . '%');
        }

        if ($request->filled('nit')) {
            $entidades->where('nit', 'like', '%' . $request->nit . '%');
        }

        if ($request->filled('nombre')) {
            $entidades->where('razon_social', 'like', '%' . $request->nombre . '%');
        }

       /* $entidades->whereNotIn('id', function ($query) {
            $query->select('id_entidad')
                ->from('visitas_inspeccion')
                ->whereNotIn('etapa', ['FINALIZADO', 'CANCELADO']);
        });*/

        $entidades = $entidades->get();

        return response()->json(['message' => $entidades]);
    }

    /**
     * Descargar plantilla de cargue masivo
     * 
     * Se descarga un excel con los datos de las entidades a crear
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

    public function descargar_plantilla_cargue_masivo() {
        $filePath = public_path('templates/plantilla_cargue_entidades_masivo.xlsx');
        return response()->download($filePath);
    }

    /**
     * crear entidades masivo
     * 
     * Se carga un excel con los datos de las entidades a crear
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

    public function importar_entidades(Request $request)
    {
        $request->validate([
            'archivo_entidades' => 'required|mimes:xlsx,xls,csv',
        ]);

        try {
            $file = $request->file('archivo_entidades');
            $spreadsheet = IOFactory::load($file->getPathname());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);

            $usuarioCreacionId = Auth::id();
            $errors = [];

            foreach ($rows as $index => $row) {
                // Saltar la fila de encabezado
                if ($row['A'] == 'Código') {
                    continue;
                }

                $data = [
                    'codigo_entidad' => $row['A'],
                    'nit' => $row['B'],
                    'razon_social' => $row['C'],
                    'sigla' => $row['D'],
                    'nivel_supervision' => $row['E'],
                    'naturaleza_organizacion' => $row['F'],
                    'tipo_organizacion' => $row['G'],
                    'categoria' => $row['H'],
                    'grupo_niif' => $row['I'],
                    'incluye_sarlaft' => $row['J'],
                    'ciudad_municipio' => $row['K'],
                    'departamento' => $row['L'],
                    'direccion' => $row['M'],
                    'numero_asociados' => $row['N'],
                    'numero_empleados' => $row['O'],
                    'total_activos' => $row['P'],
                    'total_pasivos' => $row['Q'],
                    'total_patrimonio' => $row['R'],
                    'total_ingresos' => $row['S'],
                    'fecha_ultimo_reporte' => $row['T'],
                    'fecha_corte_visita' => $row['U'],
                    'representate_legal' => $row['V'],
                    'correo_representate_legal' => $row['W'],
                    'telefono_representate_legal' => $row['X'],
                    'tipo_revisor_fiscal' => $row['Y'],
                    'razon_social_revision_fiscal' => $row['Z'],
                    'nombre_revisor_fiscal' => $row['AA'],
                    'direccion_revisor_fiscal' => $row['AB'],
                    'telefono_revisor_fiscal' => $row['AC'],
                    'correo_revisor_fiscal' => $row['AD'],
                    'usuario_creacion' => $usuarioCreacionId,
                    'estado' => 'ACTIVA',
                ];

                // Verificar si ya existe una visita activa para la entidad
                $visitaExistente = VisitaInspeccion::where('id_entidad', $data['codigo_entidad'])
                    ->whereNotIn('etapa', ['FINALIZADO', 'CANCELADO'])
                    ->exists();

                if ($visitaExistente) {
                    $errors[$row['A']][] = 'La entidad ya tiene una visita de inspección activa.';
                    continue; // Saltar esta entidad y continuar con las demás
                }

                // Validar los datos de la entidad
                $validator = Validator::make($data, [
                    'codigo_entidad' => [
                        'required',
                        'numeric',
                        Rule::unique('entidades')->where(function ($query) {
                            return $query->where('estado', '!=', 'ELIMINADA');
                        }),
                    ],
                    'nit' => [
                        'required',
                        'numeric',
                        Rule::unique('entidades')->where(function ($query) {
                            return $query->where('estado', '!=', 'ELIMINADA');
                        }),
                    ],
                    'razon_social' => 'required|string',
                    'sigla' => 'nullable|string',
                    'nivel_supervision' => 'required|numeric',
                    'naturaleza_organizacion' => 'required|string',
                    'tipo_organizacion' => 'required|string',
                    'categoria' => 'nullable|string|required_if:tipo_organizacion,FONDOS DE EMPLEADOS',
                    'grupo_niif' => 'required|string',
                    'incluye_sarlaft' => 'nullable|string',
                    'ciudad_municipio' => 'required|string',
                    'departamento' => 'required|string',
                    'direccion' => 'required|string',
                    'numero_asociados' => 'required|numeric',
                    'numero_empleados' => 'required|numeric',
                    'total_activos' => 'required|string',
                    'total_pasivos' => 'required|string',
                    'total_patrimonio' => 'required|string',
                    'total_ingresos' => 'required|string',
                    'fecha_ultimo_reporte' => 'required|date_format:Y-m-d',
                    'fecha_corte_visita' => 'required|date_format:Y-m-d',
                    'representate_legal' => 'required|string',
                    'correo_representate_legal' => 'required|email',
                    'telefono_representate_legal' => 'nullable|string',
                    'tipo_revisor_fiscal' => 'required|string',
                    'razon_social_revision_fiscal' => 'nullable|string|required_if:tipo_revisor_fiscal,PERSONA JURÍDICA',
                    'nombre_revisor_fiscal' => 'required|string',
                    'direccion_revisor_fiscal' => 'required|string',
                    'telefono_revisor_fiscal' => 'required|string',
                    'correo_revisor_fiscal' => 'required|email',
                ]);

                if ($validator->fails()) {
                    $errors[$row['A']] = $validator->errors()->all();
                    continue;
                }

                // Crear la entidad
                Entidad::create($data);
            }

            if (!empty($errors)) {
                return response()->json(['error' => 'Algunas entidades no se pudieron importar', 'errors' => $errors], 422);
            }

            return response()->json(['message' => 'Entidades importadas correctamente']);

            // Devolver las entidades con visitas activas junto con el resultado
            /*return response()->json([
                'message' => 'Entidades importadas correctamente',
                'entidades_con_visitas_activas' => $entidadesConVisitasActivas,
                'errors' => $errors
            ]);*/
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


}
