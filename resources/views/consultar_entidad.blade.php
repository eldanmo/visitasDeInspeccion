    <x-app-layout >
        @if(Auth::user()->profile === 'Coordinador' || Auth::user()->profile === 'Intendente' || Auth::user()->profile === 'Administrador')
            <div class="container">
                <h3 class="mt-3"> Entidades</h3>
                <hr>
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

                <hr>

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
                                        @if(Auth::user()->profile === 'Administrador' || Auth::user()->profile === 'Coordinador' )                
                                            <a class="btn btn-success btn-sm" onclick="abrirModalSolicitarDiagnostico('{{$entidad->id}}','{{$entidad->razon_social}}')"  >
                                                <i class="fas fa-edit"></i> Solicitar diagnóstico
                                            </a>         
                                        @endif       
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

                    @if(Auth::user()->profile === 'Administrador' || Auth::user()->profile === 'Coordinador' ) 
                        <div class="modal fade" id="modalSolicitarDiagnostico" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h1 class="modal-title fs-5" id="exampleModalLabel">Solicitar diagnóstico</h1>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label for="observaciones" class="col-form-label">Observaciones (*)</label>
                                            <textarea class="form-control" id="observaciones_solicitar_diagnostico"></textarea>
                                            <input type="hidden" name="id_entidad" id="id_entidad">
                                        </div>
                                        <div class="table-responsive">
                                            <label for="tabla_ciclos_expediente_final">Documentos adicionales</label>
                                            <table class="table table-sm" id="tabla_adicionales_solicitar_diagnostico">
                                                <thead>
                                                    <tr class="text-center">
                                                        <th class="table-primary">#</th>
                                                        <th class="table-primary">Nombre del archivo (*)</th>
                                                        <th class="table-primary">Adjunto (*)</th>
                                                        <th class="table-primary">Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr class="tr_documentos_adicionales_solicitar_diagnostico">
                                                        <td>
                                                            <p class="text-center">1</p>
                                                        </td>
                                                        <td>
                                                            <input type="text" class="form-control" id="nombre_anexo_solicitar_diagnostico" name="nombre_anexo_solicitar_diagnostico">
                                                        </td>
                                                        <td>
                                                            <input type="file" class="form-control" id="anexo_solicitar_diagnostico" name="anexo_solicitar_diagnostico" accept=".pdf,.doc,.docx,.xls,.xlsx" required>
                                                        </td>
                                                        <td class="text-center" >
                                                            <button type="button" class="btn btn-outline-danger" onclick="eliminarInspector(this)">
                                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                                </svg>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            <div class="d-grid gap-2 d-flex justify-content-end mb-2">
                                                <button type="button" class="btn btn-primary" style="--bs-btn-padding-y: .25rem; --bs-btn-padding-x: .5rem; --bs-btn-font-size: .75rem;"
                                                    onclick="anadirRegistro('tabla_adicionales_solicitar_diagnostico')">
                                                    Añadir documento
                                                </button>
                                            </div>
                                        </div>
                                        
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                        <button type="button" class="btn btn-success enviarObservacion" onclick="solicitarDiagnostico()">Solicitar</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif


                </div>
            </div>
        @else
            <div class="container">
                <h3>No tienes permisos para ingresar a esta página</h3>
            </div>
        @endif

        <script>
            document.addEventListener("DOMContentLoaded", function() {

            });
        </script>

    </x-app-layout>