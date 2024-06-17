<x-app-layout >
    <div class="container">
        <h4 class="mt-3 mb-3">Dias hábiles</h4>
        <form action="{{ route('dias_habiles') }}" method="GET">
                    <div class="row mb-3">
                        <div class="col-12 col-sm-4">
                            <label class="form-label">Descripción</label>
                            <input type="text" name="descripcion_dia" id="descripcion_dia" class="form-control" value="{{ request('descripcion_dia') }}">
                        </div>
                        <div class="col-12 col-sm-4">
                            <label class="form-label">Año</label>
                            <input type="number" name="dia" id="dia" class="form-control" value="{{ request('dia') }}" min="2024" max="2100" step="1">
                        </div>
                    </div>
                    <div class="col col-sm-12 text-end mt-3 mb-3">
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalCrearDiaNoLaboral">Crear día no laborable</button>
                        <button type="submit" class="btn btn-primary">Buscar</button>
                    </div>
        </form>

            <div class="table-responsive">
                    <table class="table table-sm">
                        <tr class="text-center">
                            <th class="table-primary">#</th>
                            <th class="table-primary">DESCRIPCIÓN</th>
                            <th class="table-primary">DÍA NO LABORABLE</th>
                            <th class="table-primary">ACCIONES</th>
                        </tr>

                        @if(isset($diasNoLaborales))
                            @foreach ($diasNoLaborales as $index => $dia_no_laboral)
                            <tr>
                                <td class="text-center">{{ $index +1 }}</td>
                                <td>{{ $dia_no_laboral->descripcion_dia }}</td>
                                <td>{{ $dia_no_laboral->dia }}</td>
                                <td class="text-center">
                                    @if(Auth::user()->profile === 'Administrador')
                                        <button class="btn btn-primary btn-sm" onclick="abrirModalEditarDia('{{$dia_no_laboral->id}}', '{{$dia_no_laboral->descripcion_dia}}','{{$dia_no_laboral->dia }}')">
                                            <i class="fas fa-edit"></i> Editar
                                        </button>                
                                        <button class="btn btn-danger btn-sm" onclick="eliminarDia('{{$dia_no_laboral->id}}', '{{$dia_no_laboral->descripcion_dia}}')">
                                            <i class="fas fa-trash"></i> Eliminar
                                        </button>
                                    @endif
                                </td>
                            </tr>
                            @endforeach  
                        @endif
                    </table>

                    @if(isset($diasNoLaborales) && $diasNoLaborales->count() > 0)
                        {{ $diasNoLaborales->links() }}
                    @endif
            </div>
    </div>

    <div class="modal fade" id="modalCrearDiaNoLaboral" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">Crear día no laboral</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="dia" class="col-form-label">Fecha (*)</label>
                        <input type="date" class="form-control required_crear" id="dia" required>
                    </div>
                    <div class="mb-3">
                        <label for="descripcion_dia" class="col-form-label">Descripción</label>
                        <input type="text" class="form-control required_crear" id="descripcion_dia">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-success botonEnviar" onclick="crearDiaNoLaboral()">Crear</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar día no laboral</h5>
            </div>
            <div class="modal-body">

                <div class="row">
                    <div class="mb-3">
                        <label for="dia_no_laboral" class="col-form-label">Fecha (*)</label>
                        <input type="date" class="form-control required" id="dia_no_laboral_edit" required>
                        <input type="hidden" class="form-control required" id="id" required>
                    </div>
                    <div class="mb-3">
                        <label for="descripcion_dia" class="col-form-label">Descripción</label>
                        <input type="text" class="form-control required" id="descripcion_dia_edit">
                    </div>
                </div>

            
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="editarDia()" >Guardar</button>
            </div>
            </div>
        </div>
    </div>
</x-app-layout >
