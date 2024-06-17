<?php

namespace App\Http\Controllers;

use App\Models\DiaNoLaboral;
use Illuminate\Http\Request;

class DiaNoLaboralController extends Controller
{

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
