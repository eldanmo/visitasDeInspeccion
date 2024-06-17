    <x-app-layout >
        <div class="container">
            <h3>Entidades</h3>
            <form id="searchForm" action="{{ route('consultar_entidades') }}" method="GET">
                <div class="row">
                    <div class="col-12 col-sm-4">
                        <label class="form-label">Código</label>
                        <input type="text" class="form-control" name="codigo" id="codigo" aria-describedby="basic-addon2" value="{{ request('codigo') }}">
                    </div>
                    <div class="col-12 col-sm-4">
                        <label class="form-label">Nit</label>
                        <input type="text" class="form-control" name="nit" id="nit" aria-describedby="basic-addon2" value="{{ request('nit') }}">
                    </div>
                    <div class="col-12 col-sm-4">
                        <label class="form-label">Razón social</label>
                        <input type="text" class="form-control" name="nombre" id="nombre" aria-describedby="basic-addon2" value="{{ request('nombre') }}">
                    </div>
                </div>

                <div class="col col-sm-12 text-end mt-3">
                    <button type="submit" class="btn btn-primary">Buscar</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-sm">
                    <tr class="text-center" >
                        <th class="table-primary">#</th>
                        <th class="table-primary">Código</th>
                        <th class="table-primary">Nit</th>
                        <th class="table-primary">Razón social</th>
                        <th class="table-primary">Tipo de entidad</th>
                        <th class="table-primary">Acciones</th>
                    </tr>
                    <tbody id="table_entidad">
                        @if(isset($entidades))
                            @foreach ($entidades as $entidad)
                            <tr>
                                <td class="text-center">{{ $entidad->id }}</td>
                                <td>{{ $entidad->codigo_entidad }}</td>
                                <td>{{ $entidad->nit }}</td>
                                <td>{{ $entidad->razon_social }}</td>
                                <td>{{ $entidad->tipo_organizacion }}</td>
                                <td class="text-center">
                                    <a href="{{ url('/entidades/' . $entidad->id . '/editar') }}" class="btn btn-primary btn-sm">
                                        <i class="fas fa-edit"></i> Editar
                                    </a>                
                                    <button class="btn btn-danger btn-sm" onclick="eliminarEntidad('{{$entidad->id}}', '{{$entidad->razon_social}}')">
                                        <i class="fas fa-trash"></i> Eliminar
                                    </button>
                                </td>
                            </tr>
                            @endforeach  
                        @endif
                    </tbody>
                </table>

                @if(isset($entidades) && $entidades->count() > 0)
                    {{ $entidades->links() }}
                @endif
            </div>
        </div>

        <script>
            document.addEventListener("DOMContentLoaded", function() {

            });
        </script>

    </x-app-layout>