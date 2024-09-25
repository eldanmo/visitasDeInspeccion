<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;

use Illuminate\Http\Request;
use App\Models\Parametro;  

class ParametrosController extends Controller
{
    /**
     * Muestra el formulario para consultar los parámetros.
     *
     * @return \Illuminate\View\View Devuelve la vista 'consultar_parametros'.
    */

    public function consultar()
    {
        return view('consultar_parametros', ['parametros' => Parametro::paginate(10)]);
    }

    /**
     * actualiza el parámetro
     * 
     * Se actualiza los días para un parámetro
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
                'dias' => 'required|numeric',
            ]);

            $usuarioCreacionId = Auth::id();

            $parametro = Parametro::findOrFail($id);

            $parametro->dias = $validatedData['dias'];
            $parametro->usuario_creacion = $usuarioCreacionId;
            $parametro->save();

            $successMessage = 'Parámetro actualizado correctamente';

            return response()->json(['message' => $successMessage]);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }

}
