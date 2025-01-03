<x-app-layout>
    @if(Auth::user()->profile === 'Administrador')
        <div class="container">
            <h2>Consultar usuarios</h2>

            <form action="{{ route('consultar_usuarios') }}" method="GET">
                <div class="row">
                    <div class="col-12 col-sm-4">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="nombre" class="form-control" value="{{ request('nombre') }}">
                    </div>
                    <div class="col-12 col-sm-4">
                        <label class="form-label">Correo</label>
                        <input type="text" name="correo" class="form-control" value="{{ request('correo') }}">
                    </div>
                    <div class="form-group col-12 col-sm-4">
                        <label class="form-label" for="rol">Rol</label>
                        <select name="rol" class="form-control">
                            <option value="">Seleccione</option>
                            <option value="Administrador" {{ request('rol') == 'Administrador' ? 'selected' : '' }}>Administrador</option>
                            <option value="Coordinador" {{ request('rol') == 'Coordinador' ? 'selected' : '' }}>Coordinación visitas de inspección</option>
                            <option value="Coordinacion asuntos especiales" {{ request('rol') == 'Coordinacion asuntos especiales' ? 'selected' : '' }}>Coordinacion asuntos especiales</option>
                            <option value="Delegado" {{ request('rol') == 'Delegado' ? 'selected' : '' }}>Delegatura</option>
                            <option value="Intendencia de fondos de empleados" {{ request('rol') == 'Intendencia de fondos de empleados' ? 'selected' : '' }}>Intendencia de fondos de empleados</option>
                            <option value="Intendencia de cooperativas y otras organizaciones solidarias" {{ request('rol') == 'Intendencia de cooperativas y otras organizaciones solidarias' ? 'selected' : '' }}>Intendencia de cooperativas y otras organizaciones solidarias</option>
                            <option value="Contratista" {{ request('rol') == 'Contratista' ? 'selected' : '' }}>Inspección</option>
                            <option value="Profesional asuntos especiales" {{ request('rol') == 'Profesional asuntos especiales' ? 'selected' : '' }}>Profesional asuntos especiales</option>
                        </select>
                    </div>
                </div>

                <div class="col col-sm-12 text-end mt-3">
                    <button type="submit" class="btn btn-primary">Buscar</button>
                </div>
            </form>

            <hr>
            <div class="table-responsive">
                <table class="table table-sm">
                    <tr class="text-center">
                        <th class="table-primary">#</th>
                        <th class="table-primary">Nombre</th>
                        <th class="table-primary">Correo</th>
                        <th class="table-primary">Rol</th>
                        <th class="table-primary">Acciones</th>
                    </tr>
                    <tbody>
                        @if(isset($usuarios))
                            @foreach ($usuarios as $usuario)
                            <tr>
                                <td class="text-center">{{ $usuario->id }}</td>
                                <td>{{ $usuario->name }}</td>
                                <td>{{ $usuario->email }}</td>
                                <td>
                                    @if($usuario->profile === 'Contratista')
                                        Inspección
                                    @elseif($usuario->profile === 'Administrador')
                                        Administración
                                    @elseif($usuario->profile === 'Coordinador')
                                        Coordinación visitas de inspección
                                    @elseif($usuario->profile === 'Delegado')
                                    Delegatura
                                    @elseif($usuario->profile === 'Intendente')
                                        Intendencia
                                    @else
                                    {{$usuario->profile}}
                                    @endif
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-primary btn-sm" onclick="abrirModalEditarUsuario('{{$usuario->id}}', '{{$usuario->name}}','{{$usuario->email }}','{{ $usuario->profile }}')">
                                        <i class="fas fa-edit"></i> Editar
                                    </button>                
                                    <button class="btn btn-danger btn-sm" onclick="eliminarUsuario('{{$usuario->id}}', '{{$usuario->name}}')">
                                        <i class="fas fa-trash"></i> Eliminar
                                    </button>
                                </td>
                            </tr>
                            @endforeach  
                        @endif
                    </tbody>
                </table>

                @if(isset($usuarios) && $usuarios->count() > 0)
                    {{ $usuarios->links() }}
                @endif
            </div>
        </div>

        <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Modificar usuario</h5>
                </div>
                <div class="modal-body">

                    <div class="row">
                        <div class="col col-sm-4">
                            <label class="form-label">Nombre (*)</label>
                            <input type="text" class="form-control" name="name" id="name" required>
                            <input type="hidden" name="id" id="id" required>
                        </div>
                        <div class="col col-sm-4">
                            <label class="form-label">Correo (*)</label>
                            <input type="email" class="form-control" name="email" id="email" required>
                        </div>
                        <div class="form-group col col-sm-4">
                            <label class="form-label" for="rol">Rol (*)</label>
                            <select class="form-control" name="profile" id="profile" required>
                                <option value="">Seleccione</option>
                                <option value="Administrador">Administrador</option>
                                <option value="Coordinador">Coordinación visitas de inspección</option>
                                <option value="Coordinacion asuntos especiales">Coordinacion asuntos especiales</option>
                                <option value="Delegado">Delegatura</option>
                                <option value="Intendencia de fondos de empleados">Intendencia de fondos de empleados</option>
                                <option value="Intendencia de cooperativas y otras organizaciones solidarias">Intendencia de cooperativas y otras organizaciones solidarias</option>
                                <option value="Contratista">Inspección</option>
                                <option value="Profesional asuntos especiales">Profesional asuntos especiales</option>
                            </select>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="editarUsuario()" >Guardar</button>
                </div>
                </div>
            </div>
        </div>
    @else
        <div class="container">
            <h3>No tienes permisos para ingresar a esta página</h3>
        </div>
    @endif
</x-app-layout>