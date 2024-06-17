<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;

use Illuminate\Http\Request;
use App\Models\Parametro;  

class ParametrosController extends Controller
{
    public function consultar()
    {
        return view('consultar_parametros', ['parametros' => Parametro::paginate(10)]);
    }

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
