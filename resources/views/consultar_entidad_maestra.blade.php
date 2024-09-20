<x-app-layout>
  <div class="container">
    <br>
        <h3>{{$entidad_datos->codigo_entidad}} - {{$entidad_datos->razon_social}} - {{$entidad_datos->nit}}</h3>
    <hr>

    <div class="row">
        <div class="border border-dark p-3">
            <div class="row text-center">
                <div class="col-6 col-sm-4 col-md-4 text-center">
                    <p><b>Tipo de entidad</b></p>
                    <p>{{$entidad_datos->tipo_organizacion}}</p>
                </div>
                <div class="col-6 col-sm-4 col-md-4 text-center">
                    <p><b>Estado</b></p>
                    <p>{{$entidad_datos->estado}}</p>
                </div>
                <div class="col-6 col-sm-4 col-md-4 text-center">
                    <p><b>Sigla</b></p>
                    <p>{{$entidad_datos->sigla}}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="border border-dark">
            <div class="row text-center p-2">
                <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center mr-3">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z" />
                    </svg>
                    <p class="mt-1 mb-0">Acciones</p>
                </div>

                <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3" data-bs-toggle="modal" data-bs-target="#modalCancelar">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <p class="mt-1 mb-0">Cambiar de estado</p>
                </div>

                <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3" data-bs-toggle="modal" data-bs-target="#modalObservaciones">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                    </svg>
                    <p class="mt-1 mb-0 text-center">Observaciones</p>
                </div>

                <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3" data-bs-toggle="modal" data-bs-target="#modalCargarDocumento">
                    <img src="{{ asset('images/upload_docs.svg') }}" width="30px" height="30px" alt="upload_docs">
                    <p class="mt-1 mb-0">Cargar documentos</p>
                </div>

            </div>
        </div>
    </div>

    <nav class="mt-3 mb-3">
        <div class="nav nav-tabs" id="nav-tab" role="tablist">
          <input type="hidden" id="id" value="{{$entidad_datos->id}}">
          <input type="hidden" id="estado" value="{{$entidad_datos->estado}}">
          <input type="hidden" id="razon_social" value="{{$entidad_datos->razon_social}}">
          <input type="hidden" id="nit" value="{{$entidad_datos->nit}}">
          <input type="hidden" id="codigo" value="{{$entidad_datos->codigo_entidad}}">
          <input type="hidden" id="sigla" value="{{$entidad_datos->sigla}}">
          <button class="nav-link active" id="nav-home-tab" data-bs-toggle="tab" data-bs-target="#nav-home" type="button" role="tab" aria-controls="nav-home" aria-selected="true">Información general</button>
          <button class="nav-link" id="nav-profile-tab" data-bs-toggle="tab" data-bs-target="#nav-profile" type="button" role="tab" aria-controls="nav-profile" aria-selected="false">Histórico</button>

            @if ($entidad_datos->anexos->count() > 0)
                <button class="nav-link" id="nav-profile-tab" data-bs-toggle="tab" data-bs-target="#nav-anexos-adicionales" type="button" role="tab" aria-controls="nav-profile" aria-selected="false">Anexos adicionales</button> 
            @endif
        </div>
    </nav>

    <div class="tab-content" id="nav-tabContent">
        <div class="tab-pane fade show active" id="nav-home" role="tabpanel" aria-labelledby="nav-home-tab">
            <div class="row diagnostico">
                <h3 class="mt-3 mb-3" >Datos de la entidad</h3>
                <hr>

                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Código</b></label><br>
                    <p>{{$entidad_datos->codigo_entidad}}</p>
                </div>
                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Nit</b></label><br>
                    <p>{{$entidad_datos->nit}}</p>
                </div>
                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Razón social</b></label><br>
                    <p>{{$entidad_datos->razon_social}}</p>
                </div>
                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Sigla</b></label><br>
                    <p>{{$entidad_datos->sigla}}</p>
                </div>
                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Nivel de supervisión</b></label><br>
                    <p>{{$entidad_datos->nivel_supervision}}</p>
                </div>
                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Tipo de organización</b></label><br>
                    <p>{{$entidad_datos->tipo_organizacion}}</p>
                </div>
                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Naturaleza de la organización</b></label><br>
                    <p>{{$entidad_datos->naturaleza_organizacion}}</p>
                </div>
                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Tipo de organización</b></label><br>
                    <p>{{$entidad_datos->tipo_organizacion}}</p>
                </div>
                @if($entidad_datos->categoria)
                    <div class="col-6 col-sm-4 col-md-3">
                        <label for=""><b>Categoría</b></label><br>
                        <p>{{$entidad_datos->categoria}}</p>
                    </div>
                @endif
                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Grupo NIIF</b></label><br>
                    <p>{{$entidad_datos->grupo_niif}}</p>
                </div>
                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>La visita incluye revisión sarlaft</b></label><br>
                    <p>{{$entidad_datos->incluye_sarlaft}}</p>
                </div>
                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Departamento</b></label><br>
                    <p>{{$entidad_datos->departamento}}</p>
                </div>
                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Ciudad / Municipio</b></label><br>
                    <p>{{$entidad_datos->ciudad_municipio}}</p>
                </div>
                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Dirección</b></label><br>
                    <p>{{$entidad_datos->direccion}}</p>
                </div>
                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Número de asociados</b></label><br>
                    <p>{{$entidad_datos->numero_asociados}}</p>
                </div>
                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Número de empleados</b></label><br>
                    <p>{{$entidad_datos->numero_empleados}}</p>
                </div>
                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Total de activos</b></label><br>
                    <p>$ {{$entidad_datos->total_activos}}</p>
                </div>
                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Total de pasivos</b></label><br>
                    <p>$ {{$entidad_datos->total_pasivos}}</p>
                </div>
                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Total de patrimonio</b></label><br>
                    <p>$ {{$entidad_datos->total_patrimonio}}</p>
                </div>
                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Total de ingresos</b></label><br>
                    <p>$ {{$entidad_datos->total_ingresos}}</p>
                </div>        
            </div>
            <div class="row">
                <br>
                <h3>Datos de RUES</h3>
                <hr>

                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Certificado RUES</b></label><br>
                    <p>{{ $entidad_datos->memorando_traslado }}</p>
                </div>

                @if ($entidad_datos->anexos)
                    @php
                        $anexosMemorandoTraslado = $entidad_datos->anexos->filter(function($anexo) {
                            return $anexo->tipo_anexo === 'ANEXOS_TRASLADAR_MEMORANDO_GRUPO_ASUNTOS_ESPECIALES';
                        });
                    @endphp

                    @if ($anexosMemorandoTraslado->count() > 0)
                        <div class="col-12 col-sm-12 col-md-12">
                            <label for=""><b>Anexos al memorando de traslado</b></label><br>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <tr class="text-center">
                                        <th class="table-primary">#</th>
                                        <th class="table-primary">NOMBRE DEL ARCHIVO</th>
                                        <th class="table-primary">ENLACE</th>
                                    </tr>
                                    @foreach($anexosMemorandoTraslado as $k => $anexo)
                                        <tr>
                                            <td class="text-center">{{ $loop->iteration }}</td>
                                            <td class="text-center">{{ $anexo->nombre }}</td>
                                            <td>
                                                <a href="{{ $anexo->ruta }}" target="_blank" rel="noopener noreferrer" class="flex items-center space-x-2">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 0 0-1.883 2.542l.857 6a2.25 2.25 0 0 0 2.227 1.932H19.05a2.25 2.25 0 0 0 2.227-1.932l.857-6a2.25 2.25 0 0 0-1.883-2.542m-16.5 0V6A2.25 2.25 0 0 1 6 3.75h3.879a1.5 1.5 0 0 1 1.06.44l2.122 2.12a1.5 1.5 0 0 0 1.06.44H18A2.25 2.25 0 0 1 20.25 9v.776" />
                                                    </svg>
                                                    <span>Abrir</span>
                                                </a> 
                                            </td>
                                        </tr>
                                    @endforeach
                                </table>
                            </div>
                        </div>
                    @endif
                @endif
            </div>
            <div class="row contacto">
                <br>
                <h3>Datos de contacto</h3>
                <hr>

                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Representanción legal </b></label><br>
                    <p>{{ $entidad_datos->representate_legal }}</p>
                </div>
                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Correo de la representación legal</b></label><br>
                    <p>{{ $entidad_datos->correo_representate_legal }}</p>
                </div>
                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Teléfono de la representación legal</b></label><br>
                    <p>{{ $entidad_datos->telefono_representate_legal }}</p>
                </div>
                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Tipo de revisor fiscal</b></label><br>
                    <p>{{ $entidad_datos->tipo_revisor_fiscal }}</p>
                </div>
                @if($entidad_datos->tipo_revisor_fiscal === 'PERSONA JURÍDICA')
                    <div class="col-6 col-sm-4 col-md-3">
                        <label for=""><b>Razón social revisión fiscal</b></label><br>
                        <p>{{ $entidad_datos->razon_social_revision_fiscal }}</p>
                    </div>
                @endif
                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Nombre de la persona revisora fiscal</b></label><br>
                    <p>{{ $entidad_datos->nombre_revisor_fiscal }}</p>
                </div>
                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Dirección revisoria fiscal</b></label><br>
                    <p>{{ $entidad_datos->direccion_revisor_fiscal }}</p>
                </div>
                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Teléfono revisoria fiscal</b></label><br>
                    <p>{{ $entidad_datos->telefono_revisor_fiscal }}</p>
                </div>
                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Correo revisoria fiscal</b></label><br>
                    <p>{{ $entidad_datos->correo_revisor_fiscal }}</p>
                </div>

            </div>


            
           
        </div>
        <div class="tab-pane fade" id="nav-profile" role="tabpanel" aria-labelledby="nav-profile-tab">
            <h4 class="mt-3 mb-3">Histórico</h4>
            <hr>
            <div class="table-responsive">
                <table class="table table-sm">
                    <tr class="text-center">
                        <th class="table-primary">#</th>
                        <th class="table-primary">USUARIO</th>
                        <th class="table-primary">ACCIÓN</th>
                        <th class="table-primary">ETAPA</th>
                        <th class="table-primary">FECHA DE REGISTRO</th>
                        <th class="table-primary">OBSERVACIONES</th>
                        <th class="table-primary">ESTADO DE LA ETAPA </th>
                        <th class="table-primary">CICLO DE VIDA</th>
                    </tr>

                            @if(isset($entidad_datos->historiales))
                                @foreach ($entidad_datos->historiales as $index => $historial)
                                <tr>
                                    <td class="text-center">{{ $index +1 }}</td>
                                    <td>{{ $historial->usuario->name }}</td>
                                    <td>{{ $historial->accion }}</td>
                                    <td>{{ $historial->etapa }}</td>
                                    <td>{{ $historial->created_at }}</td>
                                    <td>{{ $historial->observaciones }}</td>
                                    <td>
                                        @if($historial->estado_etapa === 'VIGENTE')
                                            <p class="text-success">{{$historial->estado_etapa}}</p>
                                        @else
                                            <p class="text-danger">{{$historial->estado_etapa}}</p>
                                        @endif
                                    </td>
                                    <td>{{ $historial->usuario_asignado }}</td>
                                </tr>
                                @endforeach  
                            @endif
                </table>
            </div>
        </div>
        
        <div class="tab-pane fade" id="nav-anexos-adicionales" role="tabpanel" aria-labelledby="nav-profile-tab">
            <h4 class="mt-3 mb-3">Anexos adicionales</h4>
            <hr>
            <div class="table-responsive">
                <table class="table table-sm">
                    <tr class="text-center">
                        <th class="table-primary">#</th>
                        <th class="table-primary">NOMBRE DEL ARCHIVO</th>
                        <th class="table-primary">ENLACE</th>
                    </tr>
                    @foreach($entidad_datos->anexos as $k => $anexo)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td class="text-center">{{ $anexo->nombre }}</td>
                            <td>
                                <a href="{{ $anexo->ruta }}" target="_blank" rel="noopener noreferrer" class="flex items-center space-x-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 0 0-1.883 2.542l.857 6a2.25 2.25 0 0 0 2.227 1.932H19.05a2.25 2.25 0 0 0 2.227-1.932l.857-6a2.25 2.25 0 0 0-1.883-2.542m-16.5 0V6A2.25 2.25 0 0 1 6 3.75h3.879a1.5 1.5 0 0 1 1.06.44l2.122 2.12a1.5 1.5 0 0 0 1.06.44H18A2.25 2.25 0 0 1 20.25 9v.776" />
                                    </svg>
                                    <span>Abrir</span>
                                </a> 
                            </td>
                        </tr>
                    @endforeach
                </table>


                                    


            </div>
        </div>
    </div>
  </div>

<div class="modal fade" id="modalObservaciones" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Observaciones</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="observaciones" class="col-form-label">Observación:</label>
                    <textarea class="form-control" id="observaciones"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <!-- <button type="button" class="btn btn-primary enviarObservacion" onclick="guardar_observacion_asunto_especial('observacion')">Enviar</button> -->
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCancelar" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Cambiar estado de la entidad</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="observaciones" class="col-form-label">Observación:</label>
                    <textarea class="form-control" id="observaciones_cancelacion"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <!-- <button type="button" class="btn btn-danger enviarObservacion" onclick="guardar_observacion_asunto_especial('cancelar')">Cancelar visita</button> -->
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCargarDocumento" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Cargar documento adicional</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive" >
                    <label class="col-form-label">Documentos adicionales</label>
                    <table class="table table-sm" id="tabla_adicionales_asuntos_especiales">
                        <thead>
                            <tr class="text-center">
                                <th class="table-primary">#</th>
                                <th class="table-primary">Nombre del archivo (*)</th>
                                <th class="table-primary">Adjunto (*)</th>
                                <th class="table-primary">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="tr_documentos_adicionales_asuntos_especiales">
                                <td>
                                    <p class="text-center">1</p>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="nombre_anexo_asuntos_especiales">
                                </td>
                                <td>
                                    <input type="file" class="form-control" id="anexo_asuntos_especiales" name="anexo_asuntos_especiales" accept=".pdf,.doc,.docx,.xls,.xlsx" required>
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
                            onclick="anadirRegistro('tabla_adicionales_asuntos_especiales')">
                            Añadir documento
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <!-- <button type="button" class="btn btn-success sendButton" onclick="guardar_documento_adicional_asunto_especial()">Guardar</button> -->
            </div>
        </div>
    </div>
</div>

</x-app-layout>