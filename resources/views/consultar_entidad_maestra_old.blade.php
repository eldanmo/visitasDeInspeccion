<x-app-layout >
    <div class="container">
        <h4 class="mt-3 mb-3">Entidad</h4>

        <form action="{{ route('consultar_entidad_asunto_especial') }}" method="GET">
                    <div class="row mb-3">
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
                            <label class="form-label">Estado</label>
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
                        <!-- <div class="col-12 col-sm-4">
                            <label class="form-label">Fecha de asignación del grupo</label>
                            <input type="date" class="form-control" aria-describedby="basic-addon2" id="fecha_inicial" name="fecha_inicial" value="{{ request('fecha_inicial') }}">
                        </div>
                        <div class="col-12 col-sm-4">
                            <label class="form-label">Fecha de diligenciamiento de tablero de control</label>
                            <input type="date" class="form-control" aria-describedby="basic-addon2" id="fecha_final" name="fecha_final" value="{{ request('fecha_final') }}">
                        </div> -->
                    </div>
                    
                    <div class="col col-sm-12 text-end mt-3 mb-3">
                        <!-- <button type="button botonEnviar" onclick="generarTableroMasivo()" class="btn btn-success">Generar tablero</button> -->
                        <button type="submit" class="btn btn-primary">Buscar</button>
                    </div>
        </form>
        
            <div class="table-responsive">
                    <table class="table table-sm">
                        <tr class="text-center">
                            <th class="table-primary">#</th>
                            <th class="table-primary">NIT</th>
                            <th class="table-primary">ENTIDAD</th>
                            <th class="table-primary">ETAPA ACTUAL</th>
                            <th class="table-primary">USUARIO ACTUAL</th>
                            <th class="table-primary">ESTADO ETAPA ACTUAL</th>
                            <th class="table-primary">ACCIONES</th>
                        </tr>

                        @if(isset($ausntoEspeciales))
                            @foreach ($ausntoEspeciales as $index => $asuntoEspecial)
                            <tr>
                                <td class="text-center">{{ $index +1 }}</td>
                                <td> {{$asuntoEspecial->entidad_data->nit}} </td>
                                <td> {{$asuntoEspecial->entidad_data->razon_social}} </td>
                                <td>{{ $asuntoEspecial->etapa }}</td>
                                <td>
                                @php
                                    $usuarios = json_decode($asuntoEspecial->usuarios_actuales);
                                    $totalUsuarios = count($usuarios);
                                @endphp
                                @foreach($usuarios as $key => $usuario)
                                    {{ $usuario->nombre }}
                                    @if($key < $totalUsuarios - 1) , @endif
                                @endforeach
                                </td>
                                <td>
                                    @if($asuntoEspecial->estado_etapa === 'EN DESTIEMPO')
                                        <p class="text-danger">{{ $asuntoEspecial->estado_etapa }}</p>
                                    @else
                                        <p class="text-success">{{ $asuntoEspecial->estado_etapa }}</p>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('asunto_especial', ['id' => $asuntoEspecial->id]) }}" class="btn btn-primary btn-sm flex items-center justify-center">
                                        Consultar
                                    </a> 
                                </td>
                            </tr>
                            @endforeach  
                        @endif
                    </table>

                    @if(isset($ausntoEspeciales) && $ausntoEspeciales->count() > 0)
                        {{ $ausntoEspeciales->links() }}
                    @endif
            </div>
    </div>
</x-app-layout >
