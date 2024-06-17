<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;  
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    public function crear()
    {
        return view('crear_usuario');
    }

    public function consultar()
    {
        return view('consultar_usuario');
    }

    public function guardar(Request $request)
    {
        try {

            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users',
                'profile' => 'required|string'
            ]);

            $user = new User();
            $user->name = $validatedData['name'];
            $user->email = $validatedData['email'];
            $user->profile = $validatedData['profile'];
            $user->password = Hash::make('Colombia123*');   
            $user->save();

            $successMessage = 'Usuario creado correctamente';

            return response()->json(['message' => $successMessage]);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }

    public function consultarUsuarios(Request $request) {

        $usuarios = User::query();

        if ($request->filled('nombre')) {
            $usuarios->where('name', 'like', '%' . $request->nombre . '%');
        }

        if ($request->filled('correo')) {
            $usuarios->where('email', 'like', '%' . $request->correo . '%');
        }

        if ($request->filled('rol')) {
            $usuarios->where('profile', $request->rol);
        }

        if (!$request->filled(['nombre', 'correo', 'rol'])) {
            $usuarios->get();
        }

        $usuarios = $usuarios->paginate(10);

        return view('consultar_usuario', compact('usuarios'));
    }

    public function eliminar($id)
    {

        $usuario = User::find($id);

        if (!$usuario) {
            return response()->json(['error' => 'Usuario no encontrado'], 404);
        }

        $usuario->delete();

        return response()->json(['mensaje' => 'Usuario eliminado correctamentes'], 200);
    }

    public function actualizar(Request $request, $id)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,'.$id, 
                'profile' => 'required|string'
            ]);

            $user = User::findOrFail($id);

            $user->name = $validatedData['name'];
            $user->email = $validatedData['email'];
            $user->profile = $validatedData['profile'];
            $user->save();

            $successMessage = 'Usuario actualizado correctamente';

            return response()->json(['message' => $successMessage]);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        }
    }
}
