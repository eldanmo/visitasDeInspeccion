<x-app-layout>
    <div class="container">
        <h4 class="mt-2" >Consultar parámetros</h4>

        <form action="{{ route('consultar_parametros') }}" method="GET">

        </form>
        <div class="table-responsive">
            <table class="table table-sm">
                <tr class="text-center">
                    <th class="table-primary">#</th>
                    <th class="table-primary">Estado</th>
                    <th class="table-primary">Dias habiles</th>
                    <th class="table-primary">Acciones</th>
                </tr>
                <tbody>
                    @if(isset($parametros))
                        @foreach ($parametros as $index => $parametro)
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td>{{ $parametro->estado }}</td>
                            <td class="text-center">{{ $parametro->dias }}</td>
                            <td class="text-center">
                                @if(Auth::user()->profile === 'Administrador' )
                                    <button class="btn btn-primary btn-sm" onclick="abrirModalEditarparametro('{{$parametro->id}}', '{{$parametro->estado}}','{{$parametro->dias }}')">
                                        <i class="fas fa-edit"></i> Editar
                                    </button>     
                                @endif         
                            </td>
                        </tr>
                        @endforeach  
                    @endif
                </tbody>
            </table>

            @if(isset($parametros) && $parametros->count() > 0)
                {{ $parametros->links() }}
            @endif
        </div>
    </div>

    <div class="modal fade" id="modalParametros" tabindex="-1" aria-labelledby="modalParametrosLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modificar parametro</h5>
            </div>
            <div class="modal-body">

                <div class="row">
                    <!-- <div class="form-group col col-sm-4">
                        <label class="form-label" for="rol">Estado (*)</label>
                        <select class="form-control" name="estado" id="estado" required disabled>
                            <option value="">Seleccione</option>
                            <option value="DIAGNOSTICO">DIAGNOSTICO</option>
                            <option value="PLAN DE VISITA - REQUERIMIENTOS (REVISIÓN IN SITU)">PLAN DE VISITA - REQUERIMIENTOS (REVISIÓN IN SITU)</option>
                            <option value="PREPARAR INFORMACIÓN POR PARTE DE LA OS">PREPARAR INFORMACIÓN POR PARTE DE LA OS</option>
                            <option value="EJECUCIÓN DE LA VISITA">EJECUCIÓN DE LA VISITA</option>
                            <option value="INFORME EJECUTIVO">INFORME EJECUTIVO</option>
                            <option value="CREACIÓN INFORME EXTERNO COORDINACIÓN (V1)">CREACIÓN INFORME EXTERNO COORDINACIÓN (V1)</option>
                            <option value="VERIFICACIÓN DE INFORME EXTERNO FINACIERO Y JURIDICO">VERIFICACIÓN DE INFORME EXTERNO FINACIERO Y JURIDICO</option>
                            <option value="AJUSTES COODINACIÓN Y EQUIPO DE INSPECCIÓN">AJUSTES COODINACIÓN Y EQUIPO DE INSPECCIÓN</option>
                            <option value="VERIFICACIÓN DE INFORME EXTERNO OFICINA ASESORA E INTENDENTE (V2)">VERIFICACIÓN DE INFORME EXTERNO OFICINA ASESORA E INTENDENTE (V2)</option>
                            <option value="AJUSTES COORDINACIÓN Y EQUIPO DE INSPECCIÓN (V2)">AJUSTES COORDINACIÓN Y EQUIPO DE INSPECCIÓN (V2)</option>
                            <option value="REVISIÓN FINAL DE ASESORA E INTENDENTE">REVISIÓN FINAL DE ASESORA E INTENDENTE</option>
                            <option value="PUBLICACION ESIGNA (V3)">PUBLICACION ESIGNA (V3)</option>
                            <option value="FIRMA">FIRMA</option>
                        </select>
                    </div> -->
                    <div class="col col-sm-4">
                        <label class="form-label">Días (*)</label>
                        <input type="number" class="form-control" name="dias" id="dias" required>
                        <input type="hidden" name="id" id="id" required>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="editarParametro()" >Guardar</button>
            </div>
            </div>
        </div>
    </div>

</x-app-layout>