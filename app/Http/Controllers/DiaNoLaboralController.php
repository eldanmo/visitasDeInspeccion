<?php

namespace App\Http\Controllers;

use App\Models\DiaNoLaboral;
use Illuminate\Http\Request;

class DiaNoLaboralController extends Controller
{

    /**
     * Crear día no laboral
     * 
     * Se registra los días no laborales
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

    public function crear_dia_no_laboral(Request $request) {

        try {

            $validatedData = $request->validate([
                'dia' => 'required|unique:dia_no_laborable',
                'descripcion_dia' => 'string',
            ]);

            $visita_inspeccion = new DiaNoLaboral();
            $visita_inspeccion->descripcion_dia = $validatedData['descripcion_dia'];
            $visita_inspeccion->dia = $validatedData['dia'];
            $visita_inspeccion->save();

            $successMessage = 'Día no laboral registrado';

            return response()->json(['message' => $successMessage]);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }

    }

    /**
     * Muestra el formulario para crear un días habiles.
     *
     *
     * @return \Illuminate\View\View Devuelve la vista 'dias_habiles' con los filtros solicitados.
    */

    public function dias_habiles(Request $request) {

        $diasNoLaborales = DiaNoLaboral::query();

        if ($request->filled('descripcion_dia')) {
            $diasNoLaborales->where('descripcion_dia', 'like', '%' . $request->descripcion_dia . '%');
        }

        if ($request->filled('dia')) {
            $year = $request->dia;
            $startDate = "$year-01-01";
            $endDate = "$year-12-31";
        
            $diasNoLaborales->whereBetween('dia', [$startDate, $endDate]);
        }

        if (!$request->filled(['descripcion_dia', 'dia'])) {
            $diasNoLaborales->get();
        }

        $diasNoLaborales = $diasNoLaborales->paginate(10);

        return view('dias_habiles', [
            'diasNoLaborales' => $diasNoLaborales,
        ]);
    }

    /**
     * Actualizar día no laboral
     * 
     * Se actualiza los días no laborales
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
                'dia' => 'required|unique:dia_no_laborable,dia,' . $id,
                'descripcion' => 'string', 
            ]);

            $user = DiaNoLaboral::findOrFail($id);

            $user->dia = $validatedData['dia'];
            $user->descripcion_dia = $validatedData['descripcion'];
            $user->save();

            $successMessage = 'Día actualizado correctamente';

            return response()->json(['message' => $successMessage]);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }

    /**
     * eliminar día no laboral
     * 
     * Se elimina los días no laborales
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     * 
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
    */

    public function eliminar($id)
    {

        $usuario = DiaNoLaboral::find($id);

        if (!$usuario) {
            return response()->json(['error' => 'Dia no encontrado'], 404);
        }

        $usuario->delete();

        return response()->json(['mensaje' => 'Día eliminado correctamente'], 200);
    }
}
