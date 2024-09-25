<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;  
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    /**
     * Muestra el formulario para crear un usuario (inhabilitado).
     *
     * @return \Illuminate\View\View Devuelve la vista 'crear_usuario'.
    */

    public function crear()
    {
        return view('crear_usuario');
    }

    /**
     * Muestra el formulario para consultar usuarios.
     *
     * @return \Illuminate\View\View Devuelve la vista 'consultar_usuario'.
    */

    public function consultar()
    {
        return view('consultar_usuario');
    }

    /**
     * Guardar usuario (inhabilitado)
     * 
     * Se crea un usuario nuevo
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

    /**
     * consultar usuarios
     * 
     * Retorna los datos de los usuarios a consultar
     * 
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos.
     *
     * @return \Illuminate\View\View Devuelve la vista 'consultar_usuario' con los filtros aplicados.
     * 
    */

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

    /**
     * Eliminar usuarios
     * 
     * Elimina un usuario de la base de datos
     * 
     * @param int $id id del usuario a eliminar
     *
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
     * 
    */

    public function eliminar($id)
    {

        $usuario = User::find($id);

        if (!$usuario) {
            return response()->json(['error' => 'Usuario no encontrado'], 404);
        }

        $usuario->delete();

        return response()->json(['mensaje' => 'Usuario eliminado correctamentes'], 200);
    }

    /**
     * Actualizar usuario
     * 
     * Actualiza un usuario de la base de datos
     * 
     * @param int $id id del usuario a eliminar
     *
     * @return \Illuminate\Http\JsonResponse Devuelve una respuesta JSON con un mensaje de éxito 
     *                                       si el registro se crea correctamente o un mensaje 
     *                                       de error en caso de que falle.
     * 
    */

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
