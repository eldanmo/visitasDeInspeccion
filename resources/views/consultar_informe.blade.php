<x-app-layout >
    <div class="container">
        <h4 class="mt-3 mb-3">Visitas de inspección</h4>
        <form action="{{ route('consultar_informe') }}" method="GET">
                    <div class="row mb-3">
                        <div class="col-12 col-sm-4">
                            <label class="form-label">Número de informe</label>
                            <input type="text" name="numero_informe" id="numero_informe" class="form-control" value="{{ request('numero_informe') }}">
                        </div>
                        <div class="col-12 col-sm-4">
                            <label class="form-label">Estado de la etapa</label>
                                <select class="form-select" id="estado_etapa" name="estado_etapa">
                                    <option value="" selected>Seleccione</option>
                                    <option value="VIGENTE" {{ request('estado_etapa') == 'VIGENTE' ? 'selected' : '' }}>VIGENTE</option>
                                    <option value="EN DESTIEMPO" {{ request('estado_etapa') == 'EN DESTIEMPO' ? 'selected' : '' }}>EN DESTIEMPO</option>
                                    <option value="FINALIZADO" {{ request('estado_etapa') == 'FINALIZADO' ? 'selected' : '' }}>FINALIZADO</option>
                                </select>
                        </div>
                        <div class="col-12 col-sm-4">
                            <label class="form-label">Usuario actual</label>
                            <select class="form-select" id="usuario_actual" name="usuario_actual" value="{{ request('usuario_actual') }}">
                            <option value="">Seleccione</option>
                                @if(isset($usuarios))
                                    @foreach ($usuarios as $index => $usuario)
                                        <option value="{{$usuario->id}}" {{ request('usuario_actual') == $usuario->id ? 'selected' : '' }}>{{$usuario->name}}</option>
                                    @endforeach  
                                @endif
                            </select>
                        </div>
                        <div class="col-12 col-sm-4">
                            <label class="form-label">Nit entidad</label>
                            <input type="text" class="form-control" aria-describedby="basic-addon2" id="nit_entidad" name="nit_entidad" value="{{ request('nit_entidad') }}">
                        </div>
                        <div class="col-12 col-sm-4">
                            <label class="form-label">Nombre entidad</label>
                            <input type="text" class="form-control" aria-describedby="basic-addon2" id="nombre_entidad" name="nombre_entidad" value="{{ request('nombre_entidad') }}">
                        </div>
                        <div class="col-12 col-sm-4">
                            <label class="form-label">Estado de la visita</label>
                                <select class="form-select" id="estado_informe" name="estado_informe">
                                    <option value="" selected>Seleccione</option>
                                    <option value="VIGENTE" {{ request('estado_informe') == 'VIGENTE' ? 'selected' : '' }}>VIGENTE</option>
                                    <option value="EN DESTIEMPO" {{ request('estado_informe') == 'EN DESTIEMPO' ? 'selected' : '' }}>EN DESTIEMPO</option>
                                    <option value="FINALIZADO" {{ request('estado_informe') == 'FINALIZADO' ? 'selected' : '' }}>FINALIZADO</option>
                                    <option value="CANCELADO" {{ request('estado_informe') == 'CANCELADO' ? 'selected' : '' }}>CANCELADO</option>
                                </select>
                        </div>
                        <div class="col-12 col-sm-4">
                            <label class="form-label">Etapa actual</label>
                                <select class="form-select" id="etapa_actual" name="etapa_actual">
                                    <option value="" selected>Seleccione</option>
                                    @foreach ($parametros as $index => $parametro)
                                        <option value="{{$parametro->estado}}" {{ request('etapa_actual') == $parametro->estado ? 'selected' : '' }}>{{$parametro->estado}}</option>
                                    @endforeach                         
                                </select>
                        </div>
                        <div class="col-12 col-sm-4">
                            <label class="form-label">Fecha de asignación del grupo</label>
                            <input type="date" class="form-control" aria-describedby="basic-addon2" id="fecha_inicial" name="fecha_inicial" value="{{ request('fecha_inicial') }}">
                        </div>
                        <div class="col-12 col-sm-4">
                            <label class="form-label">Fecha de diligenciamiento de tablero de control</label>
                            <input type="date" class="form-control" aria-describedby="basic-addon2" id="fecha_final" name="fecha_final" value="{{ request('fecha_final') }}">
                        </div>
                    </div>
                    
                    <div class="col col-sm-12 text-end mt-3 mb-3">
                        <button type="button botonEnviar" onclick="generarTableroMasivo()" class="btn btn-success">Generar tablero</button>
                        <button type="submit" class="btn btn-primary">Buscar</button>
                    </div>
        </form>
        
            <div class="table-responsive">
                    <table class="table table-sm">
                        <tr class="text-center">
                            <th class="table-primary">#</th>
                            <th class="table-primary">NIT</th>
                            <th class="table-primary">ENTIDAD</th>
                            <th class="table-primary">VISITA</th>
                            <th class="table-primary">ETAPA ACTUAL</th>
                            <th class="table-primary">USUARIO ACTUAL</th>
                            <th class="table-primary">ESTADO ETAPA ACTUAL</th>
                            <th class="table-primary">ESTADO DE LA VISITA</th>
                            <th class="table-primary">ACCIONES</th>
                        </tr>

                        @if(isset($informes))
                            @foreach ($informes as $index => $informe)
                            <tr>
                                <td class="text-center">{{ $index +1 }}</td>
                                <td>{{ $informe->entidad->nit }}</td>
                                <td>{{ $informe->entidad->razon_social }}</td>
                                <td>{{ $informe->numero_informe }}</td>
                                <td>{{ $informe->etapa }}</td>
                                <td>
                                @php
                                    $usuarios = json_decode($informe->usuario_actual);
                                    $totalUsuarios = count($usuarios);
                                @endphp
                                @foreach($usuarios as $key => $usuario)
                                    {{ $usuario->nombre }}
                                    @if($key < $totalUsuarios - 1) , @endif
                                @endforeach
                                </td>
                                <td>
                                    @if($informe->estado_etapa === 'EN DESTIEMPO')
                                        <p class="text-danger">{{ $informe->estado_etapa }}</p>
                                    @else
                                        <p class="text-success">{{ $informe->estado_etapa }}</p>
                                    @endif
                                </td>
                                <td>
                                    @if($informe->estado_informe === 'EN DESTIEMPO')
                                        <p class="text-danger">{{ $informe->estado_informe }}</p>
                                    @else
                                        <p class="text-success">{{ $informe->estado_informe }}</p>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('visita', ['id' => $informe->id]) }}" class="btn btn-primary btn-sm flex items-center justify-center">
                                        Consultar
                                    </a> 
                                </td>
                            </tr>
                            @endforeach  
                        @endif
                    </table>

                    @if(isset($informes) && $informes->count() > 0)
                        {{ $informes->links() }}
                    @endif
            </div>
    </div>
</x-app-layout >
