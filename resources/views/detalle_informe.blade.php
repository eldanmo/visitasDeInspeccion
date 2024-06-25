<x-app-layout>
  <div class="container">
    <br>
    <h3>Visita de inspección {{$informe->numero_informe}} - {{$informe->entidad->razon_social}}</h3>
    <hr>

    <div class="row">
        <div class="border border-dark p-3">
            <div class="row text-center">
                <div class="col-6 col-sm-4 col-md-3 text-center">
                    <p><b>Etapa</b></p>
                    <p>{{$informe->etapa}}</p>
                </div>
                <div class="col-6 col-sm-4 col-md-3 text-center">
                    <p><b>Estado de la etapa</b></p>
                    @if($informe->estado_etapa === 'VIGENTE' || $informe->estado_etapa === 'FINALIZADO')
                        <p class="text-success">{{$informe->estado_etapa}}</p>
                    @else
                        <p class="text-danger">{{$informe->estado_etapa}}</p>
                    @endif
                </div>
                <div class="col-6 col-sm-4 col-md-3 text-center">
                    <p><b>Estado del Informe</b></p>
                    @if($informe->estado_informe === 'VIGENTE' || $informe->estado_etapa === 'FINALIZADO')
                        <p class="text-success">{{$informe->estado_informe}}</p>
                    @else
                        <p class="text-danger">{{$informe->estado_informe}}</p>
                    @endif
                </div>
                <div class="col-6 col-sm-4 col-md-3 text-center">
                    <p><b>Usuarios actuales</b></p>
                    <p>@php
                        $usuarios = json_decode($informe->usuario_actual);
                        $totalUsuarios = count($usuarios);
                        @endphp
                        @foreach($usuarios as $key => $usuario)
                            {{ $usuario->nombre }}
                            @if($key < $totalUsuarios - 1) , @endif
                        @endforeach</p>
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

                @if($informe->etapa === "DIAGNÓSTICO INTENDENCIA" && ($informe->usuarioDiagnostico->id == Auth::id() ) )
                    <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3" data-bs-toggle="modal" data-bs-target="#modalFinalizarDiagnostico">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        <p class="mt-1 mb-0">Finalizar diagnóstico</p>
                    </div>
                @endif
                @if((($informe->etapa === "DIAGNÓSTICO INTENDENCIA") && ($informe->usuarioDiagnostico->id == Auth::id() )) || (Auth::user()->profile === 'Administrador' && $informe->etapa !== "CANCELADO") )
                    <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3" data-bs-toggle="modal" data-bs-target="#modalCancelar">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        <p class="mt-1 mb-0">Cancelar</p>
                    </div>
                @endif
                @if($informe->etapa === "ASIGNACIÓN GRUPO DE INSPECCIÓN" && (Auth::user()->profile === 'Coordinador' || Auth::user()->profile === 'Administrador' ) )
                    <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3" data-bs-toggle="modal" data-bs-target="#modalAsignarGupoVisitaInspeccion">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z" />
                        </svg>
                        <p class="mt-1 mb-0">Asignar grupo de inspección</p>
                    </div>
                @endif
                @if($informe->etapa === "EN REVISIÓN DEL INFORME DIAGNÓSTICO" && (Auth::user()->profile === 'Coordinador' || Auth::user()->profile === 'Administrador' ) ) 
                    <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3" data-bs-toggle="modal" data-bs-target="#modalresultadoRevisionDiagnostico">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m5.231 13.481L15 17.25m-4.5-15H5.625c-.621 0-1.125.504-1.125 1.125v16.5c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Zm3.75 11.625a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                        </svg>
                        <p class="mt-1 mb-0">Resultado revisión</p>
                    </div>
                @else
                    @foreach($informe->grupoInspeccion as $grupo)
                        @if($informe->etapa === "EN REVISIÓN DEL INFORME DIAGNÓSTICO" && 
                            ($grupo->id_usuario == Auth::id() && $grupo->rol == 'Lider de visita'))

                            <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3" data-bs-toggle="modal" data-bs-target="#modalresultadoRevisionDiagnostico">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m5.231 13.481L15 17.25m-4.5-15H5.625c-.621 0-1.125.504-1.125 1.125v16.5c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Zm3.75 11.625a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                                </svg>
                                <p class="mt-1 mb-0">Resultado revisión</p>
                            </div>
                        @endif
                    @endforeach
                @endif
                @if($informe->etapa === "EN REVISIÓN Y SUBSANACIÓN DEL DOCUMENTO DIAGNÓSTICO" && ($informe->usuarioDiagnostico->id == Auth::id() ) )
                    <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3" data-bs-toggle="modal" data-bs-target="#modalSubsanarDocumentoDiagnostico">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.9-4.414c.376.023.75.05 1.124.08 1.131.094 1.976 1.057 1.976 2.192V16.5A2.25 2.25 0 0 1 18 18.75h-2.25m-7.5-10.5H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-7.5-10.5h6.375c.621 0 1.125.504 1.125 1.125v9.375m-8.25-3 1.5 1.5 3-3.75" />
                        </svg>
                        <p class="mt-1 mb-0">Subsanar documento diagnóstico</p>
                    </div>
                @endif

                @if(($informe->etapa === "ELABORACIÓN DE PLAN DE VISITA") && (Auth::user()->profile === 'Administrador' ) ) 
                    <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3" data-bs-toggle="modal" data-bs-target="#modalPlanDeVisita">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 7.5V6.108c0-1.135.845-2.098 1.976-2.192.373-.03.748-.057 1.123-.08M15.75 18H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08M15.75 18.75v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5A3.375 3.375 0 0 0 6.375 7.5H5.25m11.9-3.664A2.251 2.251 0 0 0 15 2.25h-1.5a2.251 2.251 0 0 0-2.15 1.586m5.8 0c.065.21.1.433.1.664v.75h-6V4.5c0-.231.035-.454.1-.664M6.75 7.5H4.875c-.621 0-1.125.504-1.125 1.125v12c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V16.5a9 9 0 0 0-9-9Z" />
                    </svg>
                        <p class="mt-1 mb-0">Plan de visita</p>
                    </div>
                @else
                    @foreach($informe->grupoInspeccion as $grupo)
                        @if(($informe->etapa === "ELABORACIÓN DE PLAN DE VISITA") && 
                            ($grupo->id_usuario == Auth::id() && $grupo->rol == 'Lider de visita'))

                            <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3" data-bs-toggle="modal" data-bs-target="#modalPlanDeVisita">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 7.5V6.108c0-1.135.845-2.098 1.976-2.192.373-.03.748-.057 1.123-.08M15.75 18H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08M15.75 18.75v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5A3.375 3.375 0 0 0 6.375 7.5H5.25m11.9-3.664A2.251 2.251 0 0 0 15 2.25h-1.5a2.251 2.251 0 0 0-2.15 1.586m5.8 0c.065.21.1.433.1.664v.75h-6V4.5c0-.231.035-.454.1-.664M6.75 7.5H4.875c-.621 0-1.125.504-1.125 1.125v12c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V16.5a9 9 0 0 0-9-9Z" />
                                </svg>
                                <p class="mt-1 mb-0">Plan de visita</p>
                            </div>
                        @endif
                    @endforeach
                @endif

                @if(($informe->etapa === "MODIFICACIÓN DE PLAN DE VISITA") && (Auth::user()->profile === 'Administrador' ) ) 
                    <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3" data-bs-toggle="modal" data-bs-target="#modalModificarPlanDeVisita">
                        <img src="{{ asset('images/modify.svg') }}" width="20px" height="20px" alt="modificar">
                        <p class="mt-1 mb-0">Modificar plan de visita</p>
                    </div>
                @else
                    @foreach($informe->grupoInspeccion as $grupo)
                        @if(($informe->etapa === "MODIFICACIÓN DE PLAN DE VISITA") && 
                            ($grupo->id_usuario == Auth::id() && $grupo->rol == 'Lider de visita'))

                            <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3" data-bs-toggle="modal" data-bs-target="#modalModificarPlanDeVisita">
                                <img src="{{ asset('images/modify.svg') }}" width="20px" height="20px" alt="modificar">
                                <p class="mt-1 mb-0">Modificar plan de visita</p>
                            </div>
                        @endif
                    @endforeach
                @endif

                @if($informe->etapa === "CONFIRMAR PLAN DE VISITA COORDINACIÓN" && (Auth::user()->profile === 'Coordinador' || Auth::user()->profile === 'Administrador' ) ) 
                    <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3" data-bs-toggle="modal" data-bs-target="#modalRevisarPlanDeVisita">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                        </svg>
                        <p class="mt-1 mb-0">Resultado revisión</p>
                    </div>
                @endif

                @if($informe->etapa === "CONFIRMACIÓN REQUERIMIENTO DE INFORMACIÓN PREVIA A LA VISITA" && (Auth::user()->profile === 'Administrador' || Auth::user()->profile === 'Coordinador') ) 
                    <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3" data-bs-toggle="modal" data-bs-target="#modalConfirmarInformacionPrevia">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        <p class="mt-1 mb-0">Confirmar informaión previa</p>
                    </div>
                @else
                    @foreach($informe->grupoInspeccion as $grupo)
                        @if($informe->etapa === "CONFIRMACIÓN REQUERIMIENTO DE INFORMACIÓN PREVIA A LA VISITA" && 
                            ($grupo->id_usuario == Auth::id() && $grupo->rol == 'Lider de visita'))

                            <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3" data-bs-toggle="modal" data-bs-target="#modalConfirmarInformacionPrevia">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                                <p class="mt-1 mb-0">Confirmar informaión previa</p>
                            </div>
                        @endif
                    @endforeach
                @endif

                @if($informe->etapa === "EN REQUERIMIENTO DE INFORMACIÓN Y ELABORACIÓN DE CARTAS DE PRESENTACIÓN" && (Auth::user()->profile === 'Administrador' || Auth::user()->profile === 'Coordinador') ) 
                    <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3" data-bs-toggle="modal" data-bs-target="#modalRequerimientoInformacion">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                        </svg>
                        <p class="mt-1 mb-0">Registrar requerimiento de información</p>
                    </div>
                @else
                    @foreach($informe->grupoInspeccion as $grupo)
                        @if($informe->etapa === "EN REQUERIMIENTO DE INFORMACIÓN Y ELABORACIÓN DE CARTAS DE PRESENTACIÓN" && 
                            ($grupo->id_usuario == Auth::id() && $grupo->rol == 'Lider de visita'))

                            <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3" data-bs-toggle="modal" data-bs-target="#modalRequerimientoInformacion">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                                <p class="mt-1 mb-0">Registrar requerimiento de información</p>
                            </div>
                        @endif
                    @endforeach
                @endif

                @if($informe->etapa === "EN ESPERA DE INFORMACIÓN ADICIONAL POR PARTE DE LA ENTIDAD SOLIDARIA" && (Auth::user()->profile === 'Administrador' || Auth::user()->profile === 'Coordinador') ) 
                    <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3" data-bs-toggle="modal" data-bs-target="#modalRegistroRespuestaInformacionAdicional">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 9v.906a2.25 2.25 0 0 1-1.183 1.981l-6.478 3.488M2.25 9v.906a2.25 2.25 0 0 0 1.183 1.981l6.478 3.488m8.839 2.51-4.66-2.51m0 0-1.023-.55a2.25 2.25 0 0 0-2.134 0l-1.022.55m0 0-4.661 2.51m16.5 1.615a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V8.844a2.25 2.25 0 0 1 1.183-1.981l7.5-4.039a2.25 2.25 0 0 1 2.134 0l7.5 4.039a2.25 2.25 0 0 1 1.183 1.98V19.5Z" />
                        </svg>
                        <p class="mt-1 mb-0">Confirmación informacion recibida</p>
                    </div>
                @else
                    @foreach($informe->grupoInspeccion as $grupo)
                        @if($informe->etapa === "EN ESPERA DE INFORMACIÓN ADICIONAL POR PARTE DE LA ENTIDAD SOLIDARIA" && 
                            ($grupo->id_usuario == Auth::id() && $grupo->rol == 'Lider de visita'))

                            <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3" data-bs-toggle="modal" data-bs-target="#modalRegistroRespuestaInformacionAdicional">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 9v.906a2.25 2.25 0 0 1-1.183 1.981l-6.478 3.488M2.25 9v.906a2.25 2.25 0 0 0 1.183 1.981l6.478 3.488m8.839 2.51-4.66-2.51m0 0-1.023-.55a2.25 2.25 0 0 0-2.134 0l-1.022.55m0 0-4.661 2.51m16.5 1.615a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V8.844a2.25 2.25 0 0 1 1.183-1.981l7.5-4.039a2.25 2.25 0 0 1 2.134 0l7.5 4.039a2.25 2.25 0 0 1 1.183 1.98V19.5Z" />
                                </svg>
                                <p class="mt-1 mb-0">Confirmación informacion recibida</p>
                            </div>
                        @endif
                    @endforeach
                @endif
                @if($informe->etapa === "VALORACIÓN DE LA INFORMACIÓN RECIBIDA" && (Auth::user()->profile === 'Administrador') ) 
                    <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3" data-bs-toggle="modal" data-bs-target="#modalValoracionInformacionRecibida">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" />
                        </svg>
                        <p class="mt-1 mb-0">Confirmar visita de inspección</p>
                    </div>
                @else
                    @foreach($informe->grupoInspeccion as $grupo)
                        @if($informe->etapa === "VALORACIÓN DE LA INFORMACIÓN RECIBIDA" && 
                            ($grupo->id_usuario == Auth::id() && $grupo->rol == 'Lider de visita'))

                            <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3" data-bs-toggle="modal" data-bs-target="#modalValoracionInformacionRecibida">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                                <p class="mt-1 mb-0">Confirmar visita de inspección</p>
                            </div>
                        @endif
                    @endforeach
                @endif

                @if($informe->etapa === "CONFIRMACIÓN DE VISITA DE INSPECCIÓN" && (Auth::user()->profile === 'Coordinador' || Auth::user()->profile === 'Administrador' ) ) 
                    <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3" data-bs-toggle="modal" data-bs-target="#modalConfirmarVisita">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                        </svg>
                        <p class="mt-1 mb-0">Confirmar visita</p>
                    </div>
                @endif

                @if($informe->etapa === "ELABORACIÓN DE CARTAS DE PRESENTACIÓN" && (Auth::user()->profile === 'Administrador' || $informe->usuarioDiagnostico->id == Auth::id() ) ) 
                    <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3 p-1" data-bs-toggle="modal" data-bs-target="#modalElaboracionCartasPresentacion">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 9v.906a2.25 2.25 0 0 1-1.183 1.981l-6.478 3.488M2.25 9v.906a2.25 2.25 0 0 0 1.183 1.981l6.478 3.488m8.839 2.51-4.66-2.51m0 0-1.023-.55a2.25 2.25 0 0 0-2.134 0l-1.022.55m0 0-4.661 2.51m16.5 1.615a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V8.844a2.25 2.25 0 0 1 1.183-1.981l7.5-4.039a2.25 2.25 0 0 1 2.134 0l7.5 4.039a2.25 2.25 0 0 1 1.183 1.98V19.5Z" />
                        </svg>
                        <p class="mt-1 mb-0">Cartas de presentación</p>
                    </div>
                @else
                    @foreach($informe->grupoInspeccion as $grupo)
                        @if($informe->etapa === "ELABORACIÓN DE CARTAS DE PRESENTACIÓN" && 
                            ($grupo->id_usuario == Auth::id() && $grupo->rol == 'Lider de visita'))

                            <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3 p-1" data-bs-toggle="modal" data-bs-target="#modalElaboracionCartasPresentacion">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 9v.906a2.25 2.25 0 0 1-1.183 1.981l-6.478 3.488M2.25 9v.906a2.25 2.25 0 0 0 1.183 1.981l6.478 3.488m8.839 2.51-4.66-2.51m0 0-1.023-.55a2.25 2.25 0 0 0-2.134 0l-1.022.55m0 0-4.661 2.51m16.5 1.615a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V8.844a2.25 2.25 0 0 1 1.183-1.981l7.5-4.039a2.25 2.25 0 0 1 2.134 0l7.5 4.039a2.25 2.25 0 0 1 1.183 1.98V19.5Z" />
                                </svg>
                                <p class="mt-1 mb-0">Cartas de presentación</p>
                            </div>
                        @endif
                    @endforeach
                @endif

                @if($informe->etapa === "EN APERTURA DE VISITA DE INSPECCIÓN" && (Auth::user()->profile === 'Administrador' ) ) 
                    <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3 p-1" data-bs-toggle="modal" data-bs-target="#modalAperturaVisitaInspeccion">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                        </svg>
                        <p class="mt-1 mb-0">Abrir visita</p>
                    </div>
                @else
                    @foreach($informe->grupoInspeccion as $grupo)
                        @if($informe->etapa === "EN APERTURA DE VISITA DE INSPECCIÓN" && 
                            ($grupo->id_usuario == Auth::id() && $grupo->rol == 'Lider de visita'))

                            <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3 p-1" data-bs-toggle="modal" data-bs-target="#modalAperturaVisitaInspeccion">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                                </svg>
                                <p class="mt-1 mb-0">Abrir visita</p>
                            </div>
                        @endif
                    @endforeach
                @endif

                @if($informe->etapa === "PENDIENTE INICIO DE VISITA DE INSPECCIÓN" && (Auth::user()->profile === 'Administrador' ) ) 
                    <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3 p-1" onclick="iniciarVisitaInspeccion()" >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                        </svg>
                        <p class="mt-1 mb-0">Iniciar visita de inspección</p>
                    </div>
                @else
                    @foreach($informe->grupoInspeccion as $grupo)
                        @if($informe->etapa === "PENDIENTE INICIO DE VISITA DE INSPECCIÓN" && 
                            ($grupo->id_usuario == Auth::id() && $grupo->rol == 'Lider de visita'))

                            <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3 p-1" onclick="iniciarVisitaInspeccion()">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                                </svg>
                                <p class="mt-1 mb-0">Iniciar visita de inspección</p>
                            </div>
                        @endif
                    @endforeach
                @endif

                @if($informe->etapa === "EN DESARROLLO DE VISITA DE INSPECCIÓN" && (Auth::user()->profile === 'Administrador' ) ) 
                    <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3 p-1" data-bs-toggle="modal" data-bs-target="#modalCierreVisitaInspeccion">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                        </svg>
                        <p class="mt-1 mb-0">Cerrar visita de inspección</p>
                    </div>
                @else
                    @foreach($informe->grupoInspeccion as $grupo)
                        @if($informe->etapa === "EN DESARROLLO DE VISITA DE INSPECCIÓN" && 
                            ($grupo->id_usuario == Auth::id() && $grupo->rol == 'Lider de visita'))

                            <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3 p-1" data-bs-toggle="modal" data-bs-target="#modalCierreVisitaInspeccion">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                </svg>
                                <p class="mt-1 mb-0">Cerrar visita de inspección</p>
                            </div>
                        @endif
                    @endforeach
                @endif

                @foreach($informe->grupoInspeccion as $grupo)
                    @if($informe->etapa === "EN REGISTRO DE HALLAZGOS DE LA VISITA DE INSPECCIÓN" && 
                        ($grupo->id_usuario == Auth::id() && $grupo->rol === 'Inspector' && $grupo->enlace_hallazgos === NULL)  )
                            <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3 p-1" data-bs-toggle="modal" data-bs-target="#modalRegistrarHallazgos">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 7.5h-.75A2.25 2.25 0 0 0 4.5 9.75v7.5a2.25 2.25 0 0 0 2.25 2.25h7.5a2.25 2.25 0 0 0 2.25-2.25v-7.5a2.25 2.25 0 0 0-2.25-2.25h-.75m0-3-3-3m0 0-3 3m3-3v11.25m6-2.25h.75a2.25 2.25 0 0 1 2.25 2.25v7.5a2.25 2.25 0 0 1-2.25 2.25h-7.5a2.25 2.25 0 0 1-2.25-2.25v-.75" />
                                </svg>
                                <p class="mt-1 mb-0">Registrar hallazgos</p>
                            </div>
                    @endif
                @endforeach

                @if($informe->etapa === "EN CONSOLIDACIÓN DE HALLAZGOS" && (Auth::user()->profile === 'Administrador' ) ) 
                    <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3 p-1" data-bs-toggle="modal" data-bs-target="#modalConsolidacionHallazgos">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                        </svg>
                        <p class="mt-1 mb-0">Consolidar hallazgos</p>
                    </div>
                @else
                    @foreach($informe->grupoInspeccion as $grupo)
                        @if($informe->etapa === "EN CONSOLIDACIÓN DE HALLAZGOS" && 
                            ($grupo->id_usuario == Auth::id() && $grupo->rol == 'Lider de visita'))

                            <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3 p-1" data-bs-toggle="modal" data-bs-target="#modalConsolidacionHallazgos">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                </svg>
                                <p class="mt-1 mb-0">Consolidar hallazgos</p>
                            </div>
                        @endif
                    @endforeach
                @endif

                @if($informe->etapa === "EN ELABORACIÓN DE PROYECTO DE INFORME FINAL" && (Auth::user()->profile === 'Administrador' ) ) 
                    <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3 p-1" data-bs-toggle="modal" data-bs-target="#modalProyectoInformeFinal">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                        </svg>

                        <p class="mt-1 mb-0">Proyecto informe final</p>
                    </div>
                @else
                    @foreach($informe->grupoInspeccion as $grupo)
                        @if($informe->etapa === "EN ELABORACIÓN DE PROYECTO DE INFORME FINAL" && 
                            ($grupo->id_usuario == Auth::id() && $grupo->rol == 'Redactor'))

                            <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3 p-1" data-bs-toggle="modal" data-bs-target="#modalProyectoInformeFinal">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                                </svg>
                                <p class="mt-1 mb-0">Proyecto informe final</p>
                            </div>
                        @endif
                    @endforeach
                @endif

                @if($informe->etapa === "EN REVISIÓN DEL PROYECTO DEL INFORME FINAL" && (Auth::user()->profile === 'Administrador' ) ) 
                    <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3 p-1" data-bs-toggle="modal" data-bs-target="#modalProyectoProyevtoInformeFinal">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 15.75-2.489-2.489m0 0a3.375 3.375 0 1 0-4.773-4.773 3.375 3.375 0 0 0 4.774 4.774ZM21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>

                        <p class="mt-1 mb-0">Revisar proyecto informe final</p>
                    </div>
                @else
                    @foreach($informe->grupoInspeccion as $grupo)
                        @if($informe->etapa === "EN REVISIÓN DEL PROYECTO DEL INFORME FINAL" && 
                            ($grupo->id_usuario == Auth::id() && $grupo->rol == 'Lider de visita'))

                            <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3 p-1" data-bs-toggle="modal" data-bs-target="#modalProyectoProyevtoInformeFinal">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 15.75-2.489-2.489m0 0a3.375 3.375 0 1 0-4.773-4.773 3.375 3.375 0 0 0 4.774 4.774ZM21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                                <p class="mt-1 mb-0">Revisar proyecto informe final</p>
                            </div>
                        @endif
                    @endforeach
                @endif

                @if($informe->etapa === "EN VERIFICACIÓN DE CORRECCIONES DEL INFORME FINAL" && (Auth::user()->profile === 'Administrador' ) ) 
                    <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3 p-1" data-bs-toggle="modal" data-bs-target="#modalVerifcacionesCorrecciones">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                        </svg>

                        <p class="mt-1 mb-0">Verificar correcciones</p>
                    </div>
                @else
                    @foreach($informe->grupoInspeccion as $grupo)
                        @if($informe->etapa === "EN VERIFICACIÓN DE CORRECCIONES DEL INFORME FINAL" && 
                            ($grupo->id_usuario == Auth::id() && $grupo->rol == 'Lider de visita'))

                            <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3 p-1" data-bs-toggle="modal" data-bs-target="#modalVerifcacionesCorrecciones">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                                </svg>
                                <p class="mt-1 mb-0">Verificar correcciones</p>
                            </div>
                        @endif
                    @endforeach
                @endif

                @if($informe->etapa === "EN CORRECCIÓN DEL INFORME FINAL" && (Auth::user()->profile === 'Administrador' ) ) 
                    <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3 p-1" data-bs-toggle="modal" data-bs-target="#modalEnviarCorreccionesProyectoInformeFinal">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.9-4.414c.376.023.75.05 1.124.08 1.131.094 1.976 1.057 1.976 2.192V16.5A2.25 2.25 0 0 1 18 18.75h-2.25m-7.5-10.5H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-7.5-10.5h6.375c.621 0 1.125.504 1.125 1.125v9.375m-8.25-3 1.5 1.5 3-3.75" />
                        </svg>

                        <p class="mt-1 mb-0">Enviar correcciones</p>
                    </div>
                @else
                    @foreach($informe->grupoInspeccion as $grupo)
                        @if($informe->etapa === "EN CORRECCIÓN DEL INFORME FINAL" && 
                            ($grupo->id_usuario == Auth::id() && $grupo->rol == 'Redactor'))

                            <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3 p-1" data-bs-toggle="modal" data-bs-target="#modalEnviarCorreccionesProyectoInformeFinal">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.9-4.414c.376.023.75.05 1.124.08 1.131.094 1.976 1.057 1.976 2.192V16.5A2.25 2.25 0 0 1 18 18.75h-2.25m-7.5-10.5H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-7.5-10.5h6.375c.621 0 1.125.504 1.125 1.125v9.375m-8.25-3 1.5 1.5 3-3.75" />
                                </svg>
                                <p class="mt-1 mb-0">Enviar correcciones</p>
                            </div>
                        @endif
                    @endforeach
                @endif

                @if($informe->etapa === "EN REVISIÓN DE CORRECCIONES INFORME FINAL" && (Auth::user()->profile === 'Administrador' ) ) 
                    <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3 p-1" data-bs-toggle="modal" data-bs-target="#modalRemitirInformeFinalCoordinaciones">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                        </svg>
                        <p class="mt-1 mb-0">Remitir informe a coordinaciones</p>
                    </div>
                @else
                    @foreach($informe->grupoInspeccion as $grupo)
                        @if($informe->etapa === "EN REVISIÓN DE CORRECCIONES INFORME FINAL" && 
                            ($grupo->id_usuario == Auth::id() && $grupo->rol == 'Lider de visita'))

                            <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3 p-1" data-bs-toggle="modal" data-bs-target="#modalRemitirInformeFinalCoordinaciones">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                                </svg>
                                <p class="mt-1 mb-0">Remitir informe a coordinaciones</p>
                            </div>
                        @endif
                    @endforeach
                @endif

                @if($informe->etapa === "EN REVISIÓN DEL INFORME FINAL" && (Auth::user()->profile === 'Administrador' || Auth::user()->profile === 'Coordinador' ) ) 
                    <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3 p-1" data-bs-toggle="modal" data-bs-target="#modalRevisarInformeCoordinaciones">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.9-4.414c.376.023.75.05 1.124.08 1.131.094 1.976 1.057 1.976 2.192V16.5A2.25 2.25 0 0 1 18 18.75h-2.25m-7.5-10.5H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-7.5-10.5h6.375c.621 0 1.125.504 1.125 1.125v9.375m-8.25-3 1.5 1.5 3-3.75" />
                        </svg>
                        <p class="mt-1 mb-0">Revisar informe</p>
                    </div>
                @endif

                @if($informe->etapa === "EN REVISIÓN DEL INFORME FINAL INTENDENTE" && ($informe->usuario_creacion == Auth::id() ) )
                    <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3" data-bs-toggle="modal" data-bs-target="#modalRevisionInformeFinalIntendente">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.9-4.414c.376.023.75.05 1.124.08 1.131.094 1.976 1.057 1.976 2.192V16.5A2.25 2.25 0 0 1 18 18.75h-2.25m-7.5-10.5H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-7.5-10.5h6.375c.621 0 1.125.504 1.125 1.125v9.375m-8.25-3 1.5 1.5 3-3.75" />
                        </svg>
                        <p class="mt-1 mb-0">Revisar informe</p>
                    </div>
                @endif

                @php
                    $botonFirmarInforme = false;
                @endphp

                @foreach($informe->grupoInspeccion as $grupo)
                    @if($informe->etapa === "EN FIRMA DEL INFORME FINAL POR COMISIÓN DE VISITA DE INSPECCIÓN" && 
                        ($grupo->id_usuario == Auth::id() && $grupo->informe_firmado === NULL) && !$botonFirmarInforme)
                        
                        <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3 p-1" data-bs-toggle="modal" data-bs-target="#modalFirmarInformeFinal">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg>
                            <p class="mt-1 mb-0">Firmar informe final</p>
                        </div>

                        @php
                            $botonFirmarInforme = true;
                        @endphp

                    @endif
                @endforeach

                @if($informe->etapa === "EN CONFIRMACIÓN DE MEDIDA DE INTERVENCIÓN INMEDIATA" && ($informe->usuario_creacion == Auth::id() || Auth::user()->profile === 'Administrador') )
                    <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3" data-bs-toggle="modal" data-bs-target="#modalConfirmarIntervencionInmediata">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                        </svg>
                        <p class="mt-1 mb-0">Confirmar intervención inmediata</p>
                    </div>
                @endif

                @if($informe->etapa === "EN ENVÍO DE INFORME DE VISITA DE INSPECCIÓN PARA TRASLADO" && (Auth::user()->profile === 'Administrador' || Auth::user()->profile === 'Coordinador' ) ) 
                    <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3 p-1" data-bs-toggle="modal" data-bs-target="#modalInformeTraslado">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                        </svg>
                        <p class="mt-1 mb-0">Registrar traslado</p>
                    </div>
                @else
                    @foreach($informe->grupoInspeccion as $grupo)
                        @if($informe->etapa === "EN ENVÍO DE INFORME DE VISITA DE INSPECCIÓN PARA TRASLADO" && 
                            ($grupo->id_usuario == Auth::id() && $grupo->rol == 'Lider de visita'))

                            <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3 p-1" data-bs-toggle="modal" data-bs-target="#modalInformeTraslado">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                                </svg>
                                <p class="mt-1 mb-0">Registrar traslado</p>
                            </div>
                        @endif
                    @endforeach
                @endif

                @if($informe->etapa === "EN PROYECCIÓN DEL OFICIO DE TRASLADO DEL INFORME" && (Auth::user()->profile === 'Administrador' ) ) 
                    <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3 p-1" data-bs-toggle="modal" data-bs-target="#modalInformeTrasladoEntidad">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        <p class="mt-1 mb-0">Registrar proyección traslado</p>
                    </div>
                @else
                    @foreach($informe->grupoInspeccion as $grupo)
                        @if($informe->etapa === "EN PROYECCIÓN DEL OFICIO DE TRASLADO DEL INFORME" && 
                            ($grupo->id_usuario == Auth::id()))
                            <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3 p-1" data-bs-toggle="modal" data-bs-target="#modalInformeTrasladoEntidad">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                                <p class="mt-1 mb-0">Registrar proyección traslado</p>
                            </div>
                        @endif
                    @endforeach
                @endif

                @if($informe->etapa === "EN ESPERA DE PRONUNCIAMIENTO DE LA ORGANIZACIÓN SOLIDARIA" && (Auth::user()->profile === 'Administrador' || Auth::user()->profile === 'Coordinador' ) ) 
                    <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3 p-1" data-bs-toggle="modal" data-bs-target="#modalRegistrarPronunciaminetoEntidad">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        <p class="mt-1 mb-0">Registrar pronunciamiento</p>
                    </div>
                @else
                    @foreach($informe->grupoInspeccion as $grupo)
                        @if($informe->etapa === "EN ESPERA DE PRONUNCIAMIENTO DE LA ORGANIZACIÓN SOLIDARIA" && 
                            ($grupo->id_usuario == Auth::id()))

                            <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3 p-1" data-bs-toggle="modal" data-bs-target="#modalRegistrarPronunciaminetoEntidad">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                                <p class="mt-1 mb-0">Registrar pronunciamiento</p>
                            </div>
                        @endif
                    @endforeach
                @endif

                @if($informe->etapa === "EN VALORACIÓN DE LA INFORMACIÓN REMITIDA POR LA ORGANIZACIÓN SOLIDARIA" && (Auth::user()->profile === 'Administrador') ) 
                    <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3 p-1" data-bs-toggle="modal" data-bs-target="#modalEvaluacionRespuestaVisita">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25M9 16.5v.75m3-3v3M15 12v5.25m-4.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                        </svg>
                        <p class="mt-1 mb-0">Registrar valoración de información</p>
                    </div>
                @else
                    @foreach($informe->grupoInspeccion as $grupo)
                        @if($informe->etapa === "EN VALORACIÓN DE LA INFORMACIÓN REMITIDA POR LA ORGANIZACIÓN SOLIDARIA" && 
                            ($grupo->id_usuario == Auth::id()))

                            <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3 p-1" data-bs-toggle="modal" data-bs-target="#modalEvaluacionRespuestaVisita">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25M9 16.5v.75m3-3v3M15 12v5.25m-4.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                                <p class="mt-1 mb-0">Registrar valoración de información</p>
                            </div>
                        @endif
                    @endforeach
                @endif

                @if($informe->etapa === "EN DEFINICIÓN DE HALLAZGOS" && (Auth::user()->profile === 'Administrador' ) ) 
                    <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3 p-1" data-bs-toggle="modal" data-bs-target="#modalHallazgosFinales">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 8.25H7.5a2.25 2.25 0 0 0-2.25 2.25v9a2.25 2.25 0 0 0 2.25 2.25h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25H15m0-3-3-3m0 0-3 3m3-3V15" />
                        </svg>
                        <p class="mt-1 mb-0">Registrar envío de informe con hallazgos finales</p>
                    </div>
                @else
                    @foreach($informe->grupoInspeccion as $grupo)
                        @if($informe->etapa === "EN DEFINICIÓN DE HALLAZGOS" && 
                            ($grupo->id_usuario == Auth::id()))

                            <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3 p-1" data-bs-toggle="modal" data-bs-target="#modalHallazgosFinales">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 8.25H7.5a2.25 2.25 0 0 0-2.25 2.25v9a2.25 2.25 0 0 0 2.25 2.25h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25H15m0-3-3-3m0 0-3 3m3-3V15" />
                                </svg>
                                <p class="mt-1 mb-0">Registrar envío de informe con hallazgos finales</p>
                            </div>
                        @endif
                    @endforeach
                @endif

                @if($informe->etapa === "EN VERIFICACIÓN DE LOS CONTENIDOS FINALES DEL EXPEDIENTE" && (Auth::user()->profile === 'Administrador') ) 
                    <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3 p-1" data-bs-toggle="modal" data-bs-target="#modalContenidosFinalesExpediente">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                        </svg>
                        <p class="mt-1 mb-0">Registrar contenidos finales del expediente</p>
                    </div>
                @else
                    @foreach($informe->grupoInspeccion as $grupo)
                        @if($informe->etapa === "EN VERIFICACIÓN DE LOS CONTENIDOS FINALES DEL EXPEDIENTE" && 
                            ($grupo->id_usuario == Auth::id() && $grupo->rol == 'Lider de visita'))

                            <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3 p-1" data-bs-toggle="modal" data-bs-target="#modalContenidosFinalesExpediente">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                                </svg>
                                <p class="mt-1 mb-0">Registrar contenidos finales del expediente</p>
                            </div>
                        @endif
                    @endforeach
                @endif

                @if($informe->etapa === "EN PROPOSICIÓN DE ACTUACIÓN ADMINISTRATIVA" && (Auth::user()->profile === 'Administrador' || $informe->usuario_creacion == Auth::id() ) ) 
                    <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3 p-1" data-bs-toggle="modal" data-bs-target="#modalProposisionActuacionAdministrativa">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75Z" />
                        </svg>
                        <p class="mt-1 mb-0">Registrar proposición de actuación administrativa</p>
                    </div>
                @endif

                @if(($informe->etapa === "EN DILIGENCIAMIENTO DEL TABLERO DE CONTROL" || $informe->etapa === "FINALIZADO") && (Auth::user()->profile === 'Administrador' ) ) 
                    <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3 p-1" onclick="generarTablero()">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 8.25H7.5a2.25 2.25 0 0 0-2.25 2.25v9a2.25 2.25 0 0 0 2.25 2.25h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25H15M9 12l3 3m0 0 3-3m-3 3V2.25" />
                        </svg>
                        <p class="mt-1 mb-0">Generar tablero</p>
                    </div>
                @else
                    @foreach($informe->grupoInspeccion as $grupo)
                        @if(($informe->etapa === "EN DILIGENCIAMIENTO DEL TABLERO DE CONTROL" || $informe->etapa === "FINALIZADO") && 
                            ($grupo->id_usuario == Auth::id() && $grupo->rol == 'Lider de visita'))

                            <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3 p-1" onclick="generarTablero()">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 8.25H7.5a2.25 2.25 0 0 0-2.25 2.25v9a2.25 2.25 0 0 0 2.25 2.25h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25H15M9 12l3 3m0 0 3-3m-3 3V2.25" />
                                </svg>
                                <p class="mt-1 mb-0">Generar tablero</p>
                            </div>
                        @endif
                    @endforeach
                @endif

                @if($informe->etapa !== "CANCELADO" && $informe->etapa !== "FINALIZADO")
                    <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3" data-bs-toggle="modal" data-bs-target="#modalObservaciones">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                        </svg>
                        <p class="mt-1 mb-0 text-center">Observaciones</p>
                    </div>
                @endif

                @if(($informe->etapa !== "ASIGNACIÓN GRUPO DE INSPECCIÓN" && $informe->etapa !== "DIAGNÓSTICO INTENDENCIA" && $informe->etapa !== "FINALIZADO") && ((Auth::user()->profile === 'Coordinador' || Auth::user()->profile === 'Administrador' ) && $informe->etapa !== "CANCELADO" ) )
                    <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3" data-bs-toggle="modal" data-bs-target="#modalModificarGupoVisitaInspeccion">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                        <p class="mt-1 mb-0">Modificar grupo de inspección</p>
                    </div>
                @endif

                @if(($informe->etapa !== "SUSPENDIDO" && $informe->etapa !== "FINALIZADO") && ((Auth::user()->profile === 'Coordinador' || Auth::user()->profile === 'Administrador' ) && $informe->etapa !== "CANCELADO") )
                    <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3" data-bs-toggle="modal" data-bs-target="#modalSuspenter">
                        <img src="{{ asset('images/pause.svg') }}" width="30px" height="30px" alt="pause">
                        <p class="mt-1 mb-0">Suspender visita</p>
                    </div>
                @endif

                @if(($informe->etapa === "SUSPENDIDO") && (Auth::user()->profile === 'Coordinador' || Auth::user()->profile === 'Administrador' ) )
                    <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3" data-bs-toggle="modal" data-bs-target="#modalReanudar" >
                        <img src="{{ asset('images/play.svg') }}" width="30px" height="30px" alt="play">
                        <p class="mt-1 mb-0">Reanudar visita</p>
                    </div>
                @endif

                @if(($informe->etapa !== "SUSPENDIDO" && $informe->etapa !== "FINALIZADO") && ((Auth::user()->profile === 'Coordinador' || Auth::user()->profile === 'Administrador' ) && $informe->etapa !== "CANCELADO") )
                    <div class="col-12 col-sm-3 col-md-2 mt-1 d-flex flex-column justify-content-center align-items-center border border-dark mr-3" onclick="abrirModalBuscarEntidad(1)">
                        <img src="{{ asset('images/update.svg') }}" width="30px" height="30px" alt="remplazar entidad">
                        <p class="mt-1 mb-0">Reemplazar entidad</p>
                    </div>
                @endif

            </div>
        </div>
    </div>

    <nav class="mt-3 mb-3">
        <div class="nav nav-tabs" id="nav-tab" role="tablist">
          <input type="hidden" id="id" value="{{$informe->id}}">
          <input type="hidden" id="etapa" value="{{$informe->etapa}}">
          <input type="hidden" id="estado" value="{{$informe->estado_informe}}">
          <input type="hidden" id="estado_etapa" value="{{$informe->estado_etapa}}">
          <input type="hidden" id="numero_informe" value="{{$informe->numero_informe}}">
          <input type="hidden" id="razon_social" value="{{$informe->entidad->razon_social}}">
          <input type="hidden" id="nit" value="{{$informe->entidad->nit}}">
          <input type="hidden" id="codigo" value="{{$informe->entidad->codigo_entidad}}">
          <input type="hidden" id="sigla" value="{{$informe->entidad->sigla}}">
          <button class="nav-link active" id="nav-home-tab" data-bs-toggle="tab" data-bs-target="#nav-home" type="button" role="tab" aria-controls="nav-home" aria-selected="true">Información general</button>
          <button class="nav-link" id="nav-profile-tab" data-bs-toggle="tab" data-bs-target="#nav-profile" type="button" role="tab" aria-controls="nav-profile" aria-selected="false">Histórico</button>
          <button class="nav-link" id="nav-profile-tab" data-bs-toggle="tab" data-bs-target="#nav-dias-habiles-transcurridos" type="button" role="tab" aria-controls="nav-profile" aria-selected="false">Días habiles transcurridos</button>
        @if($informe->etapa !== "DIAGNÓSTICO INTENDENCIA" && $informe->etapa !== "ASIGNACIÓN GRUPO DE INSPECCIÓN")
          <button class="nav-link" id="nav-profile-tab" data-bs-toggle="tab" data-bs-target="#nav-grupo-visita-inspeccion" type="button" role="tab" aria-controls="nav-profile" aria-selected="false">Grupo de inspección</button>
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
                    <p>{{$informe->entidad->codigo_entidad}}</p>
                </div>
                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Nit</b></label><br>
                    <p>{{$informe->entidad->nit}}</p>
                </div>
                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Razón social</b></label><br>
                    <p>{{$informe->entidad->razon_social}}</p>
                </div>
                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Sigla</b></label><br>
                    <p>{{$informe->entidad->sigla}}</p>
                </div>
                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Nivel de supervisión</b></label><br>
                    <p>{{$informe->entidad->nivel_supervision}}</p>
                </div>
                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Tipo de organización</b></label><br>
                    <p>{{$informe->entidad->tipo_organizacion}}</p>
                </div>
                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Naturaleza de la organización</b></label><br>
                    <p>{{$informe->entidad->naturaleza_organizacion}}</p>
                </div>
                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Tipo de organización</b></label><br>
                    <p>{{$informe->entidad->tipo_organizacion}}</p>
                </div>
                @if($informe->entidad->categoria)
                    <div class="col-6 col-sm-4 col-md-3">
                        <label for=""><b>Categoría</b></label><br>
                        <p>{{$informe->entidad->categoria}}</p>
                    </div>
                @endif
                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Grupo NIIF</b></label><br>
                    <p>{{$informe->entidad->grupo_niif}}</p>
                </div>
                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>La visita incluye revisión sarlaft</b></label><br>
                    <p>{{$informe->entidad->incluye_sarlaft}}</p>
                </div>
                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Departamento</b></label><br>
                    <p>{{$informe->entidad->departamento}}</p>
                </div>
                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Ciudad / Municipio</b></label><br>
                    <p>{{$informe->entidad->ciudad_municipio}}</p>
                </div>
                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Dirección</b></label><br>
                    <p>{{$informe->entidad->direccion}}</p>
                </div>
                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Número de asociados</b></label><br>
                    <p>{{$informe->entidad->numero_asociados}}</p>
                </div>
                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Número de empleados</b></label><br>
                    <p>{{$informe->entidad->numero_empleados}}</p>
                </div>
                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Total de activos</b></label><br>
                    <p>$ {{$informe->entidad->total_activos}}</p>
                </div>
                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Total de pasivos</b></label><br>
                    <p>$ {{$informe->entidad->total_pasivos}}</p>
                </div>
                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Total de patrimonio</b></label><br>
                    <p>$ {{$informe->entidad->total_patrimonio}}</p>
                </div>
                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Total de ingresos</b></label><br>
                    <p>$ {{$informe->entidad->total_ingresos}}</p>
                </div>
                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Fecha del último reporte</b></label><br>
                    <p>{{ \Carbon\Carbon::parse($informe->entidad->fecha_ultimo_reporte)->format('Y-m-d') }}</p>
                </div>          
                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Fecha de corte de la visita</b></label><br>
                    <p>{{ \Carbon\Carbon::parse($informe->entidad->fecha_corte_visita)->format('Y-m-d') }}</p>
                </div>          
            </div>
            <div class="row contacto">
                <br>
                <h3>Datos de contacto</h3>
                <hr>

                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Representanción legal </b></label><br>
                    <p>{{ $informe->entidad->representate_legal }}</p>
                </div>
                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Correo de la representación legal</b></label><br>
                    <p>{{ $informe->entidad->correo_representate_legal }}</p>
                </div>
                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Teléfono de la representación legal</b></label><br>
                    <p>{{ $informe->entidad->telefono_representate_legal }}</p>
                </div>
                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Tipo de revisor fiscal</b></label><br>
                    <p>{{ $informe->entidad->tipo_revisor_fiscal }}</p>
                </div>
                @if($informe->entidad->tipo_revisor_fiscal === 'PERSONA JURÍDICA')
                    <div class="col-6 col-sm-4 col-md-3">
                        <label for=""><b>Razón social revisión fiscal</b></label><br>
                        <p>{{ $informe->entidad->razon_social_revision_fiscal }}</p>
                    </div>
                @endif
                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Nombre de la persona revisora fiscal</b></label><br>
                    <p>{{ $informe->entidad->nombre_revisor_fiscal }}</p>
                </div>
                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Dirección revisoria fiscal</b></label><br>
                    <p>{{ $informe->entidad->direccion_revisor_fiscal }}</p>
                </div>
                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Teléfono revisoria fiscal</b></label><br>
                    <p>{{ $informe->entidad->telefono_revisor_fiscal }}</p>
                </div>
                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Correo revisoria fiscal</b></label><br>
                    <p>{{ $informe->entidad->correo_revisor_fiscal }}</p>
                </div>

            </div>
            <div class="row diagnostico">
                <br>
                <h3>Diagnóstico</h3>
                <hr>

                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Fecha de inicio</b></label><br>
                    <p>{{ $informe->fecha_inicio_diagnostico }}</p>
                </div>
                @if($informe->fecha_fin_diagnostico)
                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Fecha Fin</b></label><br>
                    <p>{{ $informe->fecha_fin_diagnostico }}</p>
                </div>
                @endif
                <div class="col-6 col-sm-4 col-md-3">
                    <label for=""><b>Usuario creación</b></label><br>
                    {{ $informe->usuarioDiagnostico->name }}
                </div>
                @if($informe->ciclo_vida_diagnostico)
                <div class="col-6 col-sm-4 col-md-3" >
                    <label for=""><b>Documento diagnóstico</b></label><br>
                    <a href="{{ $informe->ciclo_vida_diagnostico }}" target="_blank" rel="noopener noreferrer" class="flex items-center space-x-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 0 0-1.883 2.542l.857 6a2.25 2.25 0 0 0 2.227 1.932H19.05a2.25 2.25 0 0 0 2.227-1.932l.857-6a2.25 2.25 0 0 0-1.883-2.542m-16.5 0V6A2.25 2.25 0 0 1 6 3.75h3.879a1.5 1.5 0 0 1 1.06.44l2.122 2.12a1.5 1.5 0 0 0 1.06.44H18A2.25 2.25 0 0 1 20.25 9v.776" />
                        </svg>
                        <span>Abrir</span>
                    </a>  
                </div>
                @endif
                @if(!empty(json_decode($informe->documentos_adicionales_diagnostico,true)))
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <tr class="text-center">
                                <th class="table-primary">#</th>
                                <th class="table-primary">NOMBRE DEL ARCHIVO</th>
                                <th class="table-primary">ENLACE</th>
                            </tr>
                            @foreach( json_decode($informe->documentos_adicionales_diagnostico) as $index => $documento_adicional_diagnostico )
                                <tr class="text-center">
                                    <td>
                                        {{$index + 1}}
                                    </td>
                                    <td>
                                        <p>                   
                                            <span>{{ $documento_adicional_diagnostico->fileName }}</span>                       
                                        </p>
                                    </td>
                                    <td>
                                        <a href="{{ $documento_adicional_diagnostico->fileUrl }}" target="_blank" rel="noopener noreferrer" class="flex items-center space-x-2">
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
                @endif
            </div>
            @if(!empty(json_decode($informe->anexos_subsanacion_diagnostico,true)) && !empty($informe->enlace_subsanacion_diagnostico))
                <div class="row diagnostico">
                    <br>
                    <h3>Subasanación de diagnóstico</h3>
                    <hr>
                    @if($informe->enlace_subsanacion_diagnostico)
                    <div class="col-6 col-sm-4 col-md-3" >
                        <a href="{{ $informe->enlace_subsanacion_diagnostico }}" target="_blank" rel="noopener noreferrer" class="flex items-center space-x-2" style="text-decoration:none;" >
                            <img src="{{asset('images/video.svg')}}" alt="icono de video" width="30" >
                            <span><b>Enlace de la reunión</b></span>
                        </a>  
                    </div>
                    @endif
                    @if(!empty(json_decode($informe->anexos_subsanacion_diagnostico,true)))
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <tr class="text-center">
                                    <th class="table-primary">#</th>
                                    <th class="table-primary">NOMBRE DEL ARCHIVO</th>
                                    <th class="table-primary">ENLACE</th>
                                </tr>
                                @foreach( json_decode($informe->anexos_subsanacion_diagnostico) as $index => $anexo_subsanacion_diagnostico )
                                    <tr class="text-center">
                                        <td>
                                            {{$index + 1}}
                                        </td>
                                        <td>
                                            <p>                   
                                                <span>{{ $anexo_subsanacion_diagnostico->fileName }}</span>                       
                                            </p>
                                        </td>
                                        <td>
                                            <a href="{{ $anexo_subsanacion_diagnostico->fileUrl }}" target="_blank" rel="noopener noreferrer" class="flex items-center space-x-2">
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
                    @endif
                </div>
            @endif
            @if($informe->etapa !== "DIAGNÓSTICO INTENDENCIA" 
                && $informe->etapa !== "ASIGNACIÓN GRUPO DE INSPECCIÓN"
                && $informe->etapa !== "EN REVISIÓN DEL INFORME DIAGNÓSTICO"
                && $informe->etapa !== "EN REVISIÓN Y SUBSANACIÓN DEL DOCUMENTO DIAGNÓSTICO"
                && $informe->etapa !== "ELABORACIÓN DE PLAN DE VISITA" )
                <div class="row diagnostico">
                    <br>
                    <h3>Datos de la visita de inspección</h3>
                    <hr>
                    <!-- Inicio de pruebas -->

                    @if($informe->tipo_visita)
                        <div class="col-6 col-sm-4 col-md-3">
                            <label for=""><b>¿Cómo se efectua la visita?</b></label><br>
                            <p>{{ $informe->tipo_visita }}</p>
                        </div>
                    @endif

                    @if($informe->plan_visita)
                        <div class="col-6 col-sm-4 col-md-3">
                            <label for=""><b>Plan de visita</b></label><br>
                            <p>
                                @if ($informe->plan_visita)
                                    <a href="{{ $informe->plan_visita }}" target="_blank" rel="noopener noreferrer" class="flex items-center space-x-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 0 0-1.883 2.542l.857 6a2.25 2.25 0 0 0 2.227 1.932H19.05a2.25 2.25 0 0 0 2.227-1.932l.857-6a2.25 2.25 0 0 0-1.883-2.542m-16.5 0V6A2.25 2.25 0 0 1 6 3.75h3.879a1.5 1.5 0 0 1 1.06.44l2.122 2.12a1.5 1.5 0 0 0 1.06.44H18A2.25 2.25 0 0 1 20.25 9v.776" />
                                        </svg>
                                        <span>Abrir</span>
                                    </a>
                                @endif
                            </p>
                        </div>
                    @endif

                    @if(json_decode($informe->anexos_adicionales_plan_visita))
                        <div class="table-responsive">
                            <label for=""><b> Anexos al plan de visita</b></label>
                            <table class="table table-sm">
                                <tr class="text-center">
                                    <th class="table-primary">#</th>
                                    <th class="table-primary">NOMBRE DEL ARCHIVO</th>
                                    <th class="table-primary">ENLACE</th>
                                </tr>
                                @foreach( json_decode($informe->anexos_adicionales_plan_visita) as $index => $anexo_adicionale_plan_visita )
                                    <tr class="text-center">
                                        <td>
                                            {{$index + 1}}
                                        </td>
                                        <td>
                                            <p>                   
                                                <span>{{ $anexo_adicionale_plan_visita->fileName }}</span>                       
                                            </p>
                                        </td>
                                        <td>
                                            <a href="{{ $anexo_adicionale_plan_visita->fileUrl }}" target="_blank" rel="noopener noreferrer" class="flex items-center space-x-2">
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
                    @endif

                    @if ($informe->enlace_apertura)
                        <div class="col-6 col-sm-4 col-md-3">
                            <label for=""><b>Acta de apertura de la visita o grabación</b></label><br>
                            <p>
                                <a href="{{ $informe->enlace_apertura }}" target="_blank" rel="noopener noreferrer" class="flex items-center space-x-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 0 0-1.883 2.542l.857 6a2.25 2.25 0 0 0 2.227 1.932H19.05a2.25 2.25 0 0 0 2.227-1.932l.857-6a2.25 2.25 0 0 0-1.883-2.542m-16.5 0V6A2.25 2.25 0 0 1 6 3.75h3.879a1.5 1.5 0 0 1 1.06.44l2.122 2.12a1.5 1.5 0 0 0 1.06.44H18A2.25 2.25 0 0 1 20.25 9v.776" />
                                    </svg>
                                    <span>Abrir</span>
                                </a>
                            </p>
                        </div>
                    @endif

                    @if ($informe->carta_salvaguarda)
                        <div class="col-6 col-sm-4 col-md-3">
                            <label for=""><b>Carta salvaguarda</b></label><br>
                            <p>
                                <a href="{{ $informe->carta_salvaguarda }}" target="_blank" rel="noopener noreferrer" class="flex items-center space-x-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 0 0-1.883 2.542l.857 6a2.25 2.25 0 0 0 2.227 1.932H19.05a2.25 2.25 0 0 0 2.227-1.932l.857-6a2.25 2.25 0 0 0-1.883-2.542m-16.5 0V6A2.25 2.25 0 0 1 6 3.75h3.879a1.5 1.5 0 0 1 1.06.44l2.122 2.12a1.5 1.5 0 0 0 1.06.44H18A2.25 2.25 0 0 1 20.25 9v.776" />
                                    </svg>
                                    <span>Abrir</span>
                                </a>
                            </p>
                        </div>
                    @endif

                    @if($informe->fecha_inicio_visita)
                        <div class="col-6 col-sm-4 col-md-3">
                            <label for=""><b>Fecha de inicio de la visita</b></label><br>
                            <p>{{ $informe->fecha_inicio_visita }}</p>
                        </div>
                    @endif

                    @if($informe->fecha_fin_visita)
                        <div class="col-6 col-sm-4 col-md-3">
                            <label for=""><b>Fecha final de la visita</b></label><br>
                            {{ $informe->fecha_fin_visita }}
                        </div>
                    @endif

                    @if ($informe->documentos_cierre_visita)
                        <div class="col-12 col-sm-12 col-md-12">
                            <label for=""><b>Documentos de cierre de visita de inspección</b></label><br>
                            <hr>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <tr class="text-center">
                                        <th class="table-primary">#</th>
                                        <th class="table-primary">NOMBRE DEL ARCHIVO</th>
                                        <th class="table-primary">ENLACE</th>
                                    </tr>
                                    @foreach( json_decode($informe->documentos_cierre_visita) as $index => $documento_cierre )
                                        <tr class="text-center">
                                            <td>
                                                {{$index + 1}}
                                            </td>
                                            <td>
                                                <p>                   
                                                    <span>{{ $documento_cierre->nombre_documento_cierre_visita }}</span>                       
                                                </p>
                                            </td>
                                            <td>
                                                <a href="{{ $documento_cierre->enlace_documento_cierre_visita }}" target="_blank" rel="noopener noreferrer" class="flex items-center space-x-2">
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

                    @if ($informe->hallazgos_consolidados)
                        <div class="col-6 col-sm-4 col-md-3">
                            <label for=""><b>Hallazgos consolidados</b></label><br>
                            <p>                          
                                <a href="{{ $informe->hallazgos_consolidados }}" target="_blank" rel="noopener noreferrer" class="flex items-center space-x-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 0 0-1.883 2.542l.857 6a2.25 2.25 0 0 0 2.227 1.932H19.05a2.25 2.25 0 0 0 2.227-1.932l.857-6a2.25 2.25 0 0 0-1.883-2.542m-16.5 0V6A2.25 2.25 0 0 1 6 3.75h3.879a1.5 1.5 0 0 1 1.06.44l2.122 2.12a1.5 1.5 0 0 0 1.06.44H18A2.25 2.25 0 0 1 20.25 9v.776" />
                                    </svg>
                                    <span>Abrir</span>
                                </a>
                            </p>
                        </div>
                    @endif

                    @if ($informe->proyecto_informe_final)
                        <div class="col-6 col-sm-4 col-md-3">
                            <label for=""><b>Proyecto de informe final</b></label><br>
                            <p>
                                <a href="{{ $informe->proyecto_informe_final }}" target="_blank" rel="noopener noreferrer" class="flex items-center space-x-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 0 0-1.883 2.542l.857 6a2.25 2.25 0 0 0 2.227 1.932H19.05a2.25 2.25 0 0 0 2.227-1.932l.857-6a2.25 2.25 0 0 0-1.883-2.542m-16.5 0V6A2.25 2.25 0 0 1 6 3.75h3.879a1.5 1.5 0 0 1 1.06.44l2.122 2.12a1.5 1.5 0 0 0 1.06.44H18A2.25 2.25 0 0 1 20.25 9v.776" />
                                    </svg>
                                    <span>Abrir</span>
                                </a>
                            </p>
                        </div>
                    @endif

                    @if ($informe->informe_final)
                        <div class="col-6 col-sm-4 col-md-3">
                            <label for=""><b>Informe final</b></label><br>
                            <p>
                                <a href="{{ $informe->informe_final }}" target="_blank" rel="noopener noreferrer" class="flex items-center space-x-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 0 0-1.883 2.542l.857 6a2.25 2.25 0 0 0 2.227 1.932H19.05a2.25 2.25 0 0 0 2.227-1.932l.857-6a2.25 2.25 0 0 0-1.883-2.542m-16.5 0V6A2.25 2.25 0 0 1 6 3.75h3.879a1.5 1.5 0 0 1 1.06.44l2.122 2.12a1.5 1.5 0 0 0 1.06.44H18A2.25 2.25 0 0 1 20.25 9v.776" />
                                    </svg>
                                    <span>Abrir</span>
                                </a>
                            </p>
                        </div>
                    @endif

                    @if ($informe->evaluacion_respuesta_entidad)
                        <div class="col-6 col-sm-4 col-md-3">
                            <label for=""><b>Evaluación de respuesta de la entidad</b></label><br>
                            <p>                   
                                    <a href="{{ $informe->informe_final }}" target="_blank" rel="noopener noreferrer" class="flex items-center space-x-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 0 0-1.883 2.542l.857 6a2.25 2.25 0 0 0 2.227 1.932H19.05a2.25 2.25 0 0 0 2.227-1.932l.857-6a2.25 2.25 0 0 0-1.883-2.542m-16.5 0V6A2.25 2.25 0 0 1 6 3.75h3.879a1.5 1.5 0 0 1 1.06.44l2.122 2.12a1.5 1.5 0 0 0 1.06.44H18A2.25 2.25 0 0 1 20.25 9v.776" />
                                        </svg>
                                        <span>Abrir</span>
                                    </a>                           
                            </p>
                        </div>
                    @endif

                    @if ($informe->ciclo_vida_contenidos_finales)
                        <h3>Documentos finales</h3>
                        <hr>
                        <div class="col-6 col-sm-4 col-md-3">
                            <label for=""><b>Ciclos de vida</b></label><br>
                            <hr>
                            @foreach( json_decode($informe->ciclo_vida_contenidos_finales) as $ciclo)
                                <p>                   
                                    <span>{{ $ciclo->ciclo_vida }}</span>                       
                                </p>
                            @endforeach
                        </div>
                    @endif

                    @if ($informe->documentos_contenidos_finales)
                        <div class="col-6 col-sm-6 col-md-6">
                            <label for=""><b>Documentos finales</b></label><br>
                            <hr>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <tr class="text-center">
                                        <th class="table-primary">#</th>
                                        <th class="table-primary">NOMBRE DEL ARCHIVO</th>
                                        <th class="table-primary">ENLACE</th>
                                    </tr>
                                    @foreach( json_decode($informe->documentos_contenidos_finales) as $index => $documento)
                                        <tr>
                                            <td>
                                                {{$index + 1}}
                                            </td>
                                            <td>
                                                <p>                   
                                                    <span>{{ $documento->nombre_documento_final }}</span>                       
                                                </p>
                                            </td>
                                            <td>
                                                <a href="{{ $documento->enlace_documento }}" target="_blank" rel="noopener noreferrer" class="flex items-center space-x-2">
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
                    
                </div>
            @endif 
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
                        <th class="table-primary">ESTADO</th>
                        <th class="table-primary">FECHA DE REGISTRO</th>
                        <th class="table-primary">OBSERVACIONES</th>
                        <th class="table-primary">ESTADO DE LA ETAPA </th>
                        <th class="table-primary">CICLO DE VIDA</th>
                    </tr>

                            @if(isset($informe->historiales))
                                @foreach ($informe->historiales as $index => $historial)
                                <tr>
                                    <td class="text-center">{{ $index +1 }}</td>
                                    <td>{{ $historial->usuario->name }}</td>
                                    <td>{{ $historial->accion }}</td>
                                    <td>{{ $historial->etapa }}</td>
                                    <td>
                                        @if($historial->estado === 'VIGENTE')
                                            <p class="text-success">{{$historial->estado}}</p>
                                        @else
                                            <p class="text-danger">{{$historial->estado}}</p>
                                        @endif
                                    </td>
                                    <td>{{ $historial->fecha_creacion }}</td>
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
        <div class="tab-pane fade" id="nav-dias-habiles-transcurridos" role="tabpanel" aria-labelledby="nav-profile-tab">
            <h4 class="mt-3 mb-3">Días habiles transcurridos</h4>
            <hr>
            <div class="table-responsive">
                <table class="table table-sm">
                    <tr class="text-center">
                        <th class="table-primary">#</th>
                        <th class="table-primary">ETAPA</th>
                        <th class="table-primary">FECHA DE INICIO</th>
                        <th class="table-primary">FECHA FIN</th>
                        <th class="table-primary">DÍAS HABILES DE LA ETAPA</th>
                        <th class="table-primary">DÍAS HABILES TRANSCURRIDOS</th>
                        <th class="table-primary">FECHA DE LÍMITE DE LA ETAPA</th>
                    </tr>
                        @if(isset($informe->conteoDias))
                            @foreach ($informe->conteoDias as $index => $conteoDia)
                            <tr>
                                <td class="text-center">{{ $index +1 }}</td>
                                <td>{{ $conteoDia->etapa }}</td>
                                <td>{{ $conteoDia->fecha_inicio }}</td>
                                <td>{{ $conteoDia->fecha_fin }}</td>
                                <td class="text-center">{{ $conteoDia->dias_habiles }}</td>
                                <td class="text-center">
                                    @if($conteoDia->conteo_dias > $conteoDia->dias_habiles)
                                        <p class="text-danger">{{$conteoDia->conteo_dias}}</p>
                                    @else
                                        <p class="text-success">{{$conteoDia->conteo_dias}}</p>
                                    @endif
                                </td>
                                <td>{{ $conteoDia->fecha_limite_etapa }}</td>
                            </tr>
                            @endforeach  
                        @endif
                </table>
            </div>
        </div>
        <div class="tab-pane fade" id="nav-grupo-visita-inspeccion" role="tabpanel" aria-labelledby="nav-profile-tab">
            <h4 class="mt-3 mb-3">Días habiles transcurridos</h4>
            <hr>
            <div class="table-responsive">
                <table class="table table-sm">
                    <tr class="text-center">
                        <th class="table-primary">#</th>
                        <th class="table-primary">USUARIO</th>
                        <th class="table-primary">ROL</th>
                        <th class="table-primary">FECHA DE ASIGNACIÓN</th>
                        <th class="table-primary">HALLAZGOS</th>
                    </tr>
                        @if(isset($informe->grupoInspeccion))
                            @foreach ($informe->grupoInspeccion as $index => $persona)
                            <tr>
                                <td class="text-center">{{ $index +1 }}</td>
                                <td>{{ $persona->usuarioAsignado->name }}</td>
                                <td>{{ $persona->rol }}</td>
                                <td>{{ $persona->updated_at }}</td>
                                <td>
                                    @if($persona->enlace_hallazgos !== NULL)
                                        @foreach( json_decode($persona->enlace_hallazgos) as $hallazgo_individual )
                                        <p> <b> {{$hallazgo_individual->nombre_documento_hallazgo }} : </b> </p>
                                        <a href="{{ $hallazgo_individual->enlace_documento_hallazgo }}" target="_blank" rel="noopener noreferrer" class="flex items-center space-x-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 0 0-1.883 2.542l.857 6a2.25 2.25 0 0 0 2.227 1.932H19.05a2.25 2.25 0 0 0 2.227-1.932l.857-6a2.25 2.25 0 0 0-1.883-2.542m-16.5 0V6A2.25 2.25 0 0 1 6 3.75h3.879a1.5 1.5 0 0 1 1.06.44l2.122 2.12a1.5 1.5 0 0 0 1.06.44H18A2.25 2.25 0 0 1 20.25 9v.776" />
                                            </svg>
                                            <span>Abrir</span>
                                        </a>
                                        @endforeach
                                    @endif
                                </td>
                            </tr>
                            @endforeach  
                        @endif
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
                <button type="button" class="btn btn-primary enviarObservacion" onclick="guardar_observacion('observacion')">Enviar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCancelar" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Cancelar visita de inspección</h1>
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
                <button type="button" class="btn btn-danger enviarObservacion" onclick="guardar_observacion('cancelar')">Cancelar visita</button>
            </div>
        </div>
    </div>
</div>
@if($informe->etapa === "DIAGNÓSTICO INTENDENCIA" && ($informe->usuarioDiagnostico->id == Auth::id() ) )
<div class="modal fade" id="modalFinalizarDiagnostico" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Finalizar diagnóstico</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="enlace_plan_visita" class="col-form-label">Documento diagnóstico (*)</label>
                    <input type="file" class="form-control required_plan_visita" id="ciclo_vida_diagnostico" name="ciclo_vida_diagnostico" accept=".pdf,.doc,.docx,.xls,.xlsx" required>
                </div>
                <div class="table-responsive">
                    <label for="tabla_ciclos_expediente_final">Documentos adicionales</label>
                    <table class="table table-sm" id="tabla_adicionales_diagnostico">
                        <thead>
                            <tr class="text-center">
                                <th class="table-primary">#</th>
                                <th class="table-primary">Nombre del archivo (*)</th>
                                <th class="table-primary">Adjunto (*)</th>
                                <th class="table-primary">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="tr_documentos_adicionales_diagnostico">
                                <td>
                                    <p class="text-center">1</p>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="nombre_anexo_diagnostico">
                                </td>
                                <td>
                                    <input type="file" class="form-control" id="anexo_diagnostico" name="anexo_diagnostico" accept=".pdf,.doc,.docx,.xls,.xlsx" required>
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
                            onclick="anadirRegistro('tabla_adicionales_diagnostico')">
                            Añadir documento
                        </button>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="observaciones" class="col-form-label">Observación:</label>
                    <textarea class="form-control" id="observaciones_diagnostico"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success enviarObservacion" onclick="finalizarDiagnostico()">Finalizar</button>
            </div>
        </div>
    </div>
</div>
@endif

<div class="modal fade" id="modalAsignarGupoVisitaInspeccion" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Asignar grupo visita de inspección</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <table class="table table-sm" id="tabla_asignacion_grupo_inspeccion">
                    <thead>
                        <tr class="text-center">
                            <th class="table-primary">#</th>
                            <th class="table-primary">Inspector</th>
                            <th class="table-primary">Rol</th>
                            <th class="table-primary">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="tr_grupo_inspeccion">
                            <td>
                                <p class="text-center">1</p>
                            </td>
                            <td>
                                <select class="form-select grupo_inspeccion" id="inspector_lider" name="usuario">
                                    <option value="">Seleccione</option>
                                    @if(isset($usuariosTotales))
                                        @foreach ($usuariosTotales as $index => $usuarioTotal)
                                            <option value="{{$usuarioTotal->id}}">{{$usuarioTotal->name}}</option>
                                        @endforeach  
                                    @endif
                                </select>
                            </td>
                            <td>
                                <select class="form-select grupo_inspeccion" id="inspector_rol_lider" name="rol" disabled>
                                    <option value="Lider de visita">Lider de visita</option>
                                </select>
                            </td>
                            <td>
                            </td>
                        </tr>
                        <tr class="tr_grupo_inspeccion">
                            <td>
                                <p class="text-center">2</p>
                            </td>
                            <td>
                                <select class="form-select grupo_inspeccion" id="inspector_redactor" name="usuario">
                                    <option value="">Seleccione</option>
                                    @if(isset($usuariosTotales))
                                        @foreach ($usuariosTotales as $index => $usuarioTotal)
                                            <option value="{{$usuarioTotal->id}}">{{$usuarioTotal->name}}</option>
                                        @endforeach  
                                    @endif
                                </select>
                            </td>
                            <td>
                                <select class="form-select grupo_inspeccion" id="inspector_rol_redactor" name="rol" disabled>
                                    <option value="Redactor">Redactor</option>
                                </select>
                            </td>
                            <td>
                            </td>
                        </tr>
                        <tr class="tr_grupo_inspeccion">
                            <td>
                                <p class="text-center">3</p>
                            </td>
                            <td>
                                <select class="form-select grupo_inspeccion" name="usuario">
                                    <option value="">Seleccione</option>
                                    @if(isset($usuariosTotales))
                                        @foreach ($usuariosTotales as $index => $usuarioTotal)
                                            <option value="{{$usuarioTotal->id}}">{{$usuarioTotal->name}}</option>
                                        @endforeach  
                                    @endif
                                </select>
                            </td>
                            <td>
                                <select class="form-select grupo_inspeccion" name="rol">
                                    <option value="Inspector">Inspector</option>
                                </select>
                            </td>
                            <td>
                                <button type="button" class="btn btn-outline-danger" onclick="eliminarInspector(this)">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="d-grid gap-2 d-flex justify-content-end">
                    <button type="button" class="btn btn-primary" style="--bs-btn-padding-y: .25rem; --bs-btn-padding-x: .5rem; --bs-btn-font-size: .75rem;"
                        onclick="anadirInspector()">
                        Añadir inspector
                    </button>
                </div>
                <div class="mb-3">
                    <label for="observaciones" class="col-form-label">Observación:</label>
                    <textarea class="form-control" id="observaciones_asignacion_grupo_inspeccion"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success asignarGrupoInspeccion" onclick="asignarGrupoInspeccion()">Asignar grupo de inspección</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalresultadoRevisionDiagnostico" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Resulatado de la revisión del documento diagnóstico</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="resultado_revision" class="col-form-label">¿El documento diagnóstico cumple con los criterios suficientes para elaborar el plan de la visita? (*)</label>
                    <select class="form-select grupo_inspeccion required_revision_diagnostico" id="resultado_revision" name="resultado_revision" onchange="resultadoRevisionDiagnostico()" >
                        <option value="">--Seleccione--</option>
                        <option value="Si">Si</option>
                        <option value="No">No</option>
                    </select>
                    <div class="mb-3 div_enlace_documento_diagnostico" style="display: none;">
                        <label for="ciclo_devolucion_documento_diagnostico" class="col-form-label">Fecha y hora en que se socializará el diagnóstico (*)</label>
                        <input type="datetime-local" class="form-control required_revision_diagnostico" id="ciclo_devolucion_documento_diagnostico" required>
                    </div>
                    <div class="mb-3 div_observaciones_documento_diagnostico">
                        <label for="observaciones_documento_diagnostico" class="col-form-label">Observaciones (*)</label>
                        <textarea class="form-control required_revision_diagnostico" id="observaciones_documento_diagnostico"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success revisionDiagnostico" onclick="guardarRevisionDiagnostico()">Enviar revisión</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalSubsanarDocumentoDiagnostico" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Subsanar documento diagnóstico</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="producto_generado_subsanacion" class="col-form-label">Producto generado de la reunión (*):</label>
                    <select name="producto_generado_subsanacion" id="producto_generado_subsanacion" class="form-control" onchange="productoGeneradoSubsanacion()" >
                        <option value="">--Seleccione--</option>
                        <option value="GRABACIÓN">GRABACIÓN</option>
                        <option value="DOCUMENTO(S)">DOCUMENTO(S)</option>
                        <option value="AMBOS">AMBOS</option>
                    </select>
                </div>
                <div class="mb-3" style="display:none;" id="div_enlace_grabacion" >
                    <label for="enlace_grabacion" class="col-form-label">Enlace de la grabación (*):</label>
                    <input type="text" class="form-control required" id="enlace_grabacion" required>
                </div>

                <div style="display: none;" id="div_tabla_adicionales_subsanacion_diagnostico" class="table-responsive" >
                    <table class="table table-sm" id="tabla_adicionales_subsanacion_diagnostico">
                        <thead>
                            <tr class="text-center">
                                <th class="table-primary">#</th>
                                <th class="table-primary">Nombre del archivo (*)</th>
                                <th class="table-primary">Adjunto (*)</th>
                                <th class="table-primary">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="tr_documentos_adicionales_subsanacion_diagnostico">
                                <td>
                                    <p class="text-center">1</p>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="nombre_anexo_subsanacion_diagnostico">
                                </td>
                                <td>
                                    <input type="file" class="form-control" id="anexo_subsanacion_diagnostico" name="anexo_subsanacion_diagnostico" accept=".pdf,.doc,.docx,.xls,.xlsx" required>
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
                            onclick="anadirRegistro('tabla_adicionales_subsanacion_diagnostico')">
                            Añadir documento
                        </button>
                    </div>
                </div>

                <div class="mb-3 div_observaciones_subsanacion_documento_diagnostico">
                    <label for="observaciones_subsanacion_documento_diagnostico" class="col-form-label">Observaciones</label>
                    <textarea class="form-control required" id="observaciones_subsanacion_documento_diagnostico"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success diagnosticoSubsanado" onclick="finalizarSubsanarDiagnostico()">Enviar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalPlanDeVisita" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Enviar plan de visita </h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="tipo_visita" class="col-form-label">¿Cómo se efectuará la visita? (*)</label>
                    <select class="form-select form-control required_plan_visita" id="tipo_visita">
                        <option value="">--Seleccione--</option>
                        <option value="VIRTUAL">VIRTUAL</option>
                        <option value="PRESENCIAL">PRESENCIAL</option>
                        <option value="MIXTA">MIXTA</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="enlace_plan_visita" class="col-form-label">Plan de visita (*)</label>
                    <input type="file" class="form-control required_plan_visita" id="enlace_plan_visita" name="enlace_plan_visita" accept=".pdf,.doc,.docx,.xls,.xlsx" required>
                </div>
                <div class="table-responsive" >
                    <label class="col-form-label">Documentos  adicionales</label>
                    <table class="table table-sm" id="tabla_adicionales_plan_visita">
                        <thead>
                            <tr class="text-center">
                                <th class="table-primary">#</th>
                                <th class="table-primary">Nombre del archivo (*)</th>
                                <th class="table-primary">Adjunto (*)</th>
                                <th class="table-primary">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="tr_documentos_adicionales_plan_visita">
                                <td>
                                    <p class="text-center">1</p>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="nombre_anexo_plan_visita">
                                </td>
                                <td>
                                    <input type="file" class="form-control" id="anexo_plan_visita" name="anexo_plan_visita" accept=".pdf,.doc,.docx,.xls,.xlsx" required>
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
                            onclick="anadirRegistro('tabla_adicionales_plan_visita')">
                            Añadir documento
                        </button>
                    </div>
                </div>
                <div class="mb-3 div_observaciones_envio_plan_visita">
                    <label for="observaciones_envio_plan_visita" class="col-form-label">Observaciones</label>
                    <textarea class="form-control" id="observaciones_envio_plan_visita"></textarea>
                </div>
            </div>

            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success diagnosticoSubsanado" onclick="planVisita()">Enviar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalModificarPlanDeVisita" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Modificar plan de visita </h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <a href="{{ $informe->plan_visita }}" target="_blank" class="flex items-center space-x-2" style="text-decoration:none;" >
                        <img src="{{ asset('images/download.svg') }}" alt="descargar" width="15px" height="15px"> Descargar plan de visita actual
                    </a>
                </div>
                <div class="mb-3">
                    <label for="tipo_visita_modificada" class="col-form-label">¿Cómo se efectuará la visita? (*)</label>
                    <select class="form-select form-control required_plan_visita_modificado" id="tipo_visita_modificada">
                        <option value="" {{ $informe->tipo_visita == '' ? 'selected' : '' }}>--Seleccione--</option>
                        <option value="VIRTUAL" {{ $informe->tipo_visita == 'VIRTUAL' ? 'selected' : '' }}>VIRTUAL</option>
                        <option value="PRESENCIAL" {{ $informe->tipo_visita == 'PRESENCIAL' ? 'selected' : '' }}>PRESENCIAL</option>
                        <option value="MIXTA" {{ $informe->tipo_visita == 'MIXTA' ? 'selected' : '' }}>MIXTA</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="enlace_plan_visita_modificado" class="col-form-label">Nuevo plan de visita</label>
                    <input type="file" class="form-control" id="enlace_plan_visita_modificado" name="enlace_plan_visita_modificado" accept=".pdf,.doc,.docx,.xls,.xlsx" required>
                </div>
                <div class="table-responsive" >
                    <label class="col-form-label">Documentos adicionales</label>
                    <table class="table table-sm" id="tabla_modificacion_adicionales_plan_visita">
                        <thead>
                            <tr class="text-center">
                                <th class="table-primary">#</th>
                                <th class="table-primary">Nombre del archivo (*)</th>
                                <th class="table-primary">Adjunto (*)</th>
                                <th class="table-primary">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $counter = 1; @endphp
                            @if($informe->anexos_adicionales_plan_visita)
                                @foreach( json_decode($informe->anexos_adicionales_plan_visita) as $index => $anexo_adicional_antiguo_plan_visita )
                                    @php $counter = $index+1; @endphp
                                    <tr class="tr_documentos_modificado_adicionales_plan_visita">
                                        <td>
                                            <p class="text-center">{{$counter}}</p>
                                        </td>
                                        <td>
                                            <label for="">{{$anexo_adicional_antiguo_plan_visita->fileName}}</label>
                                        </td>
                                        <td>
                                            <a href="{{ $anexo_adicional_antiguo_plan_visita->fileUrl }}" target="_blank" class="flex items-center space-x-2" style="text-decoration:none;">
                                                <img src="{{ asset('images/download.svg') }}" alt="descargar" width="15px" height="15px"> Descargar
                                            </a>
                                        </td>
                                        <td class="text-center" >
                                            <button type="button" class="btn btn-outline-danger" onclick="eliminarAnexo(this, '{{$anexo_adicional_antiguo_plan_visita->fileUrl}}', '{{$anexo_adicional_antiguo_plan_visita->fileName}}')">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                            <tr class="tr_documentos_modificado_adicionales_plan_visita">
                                <td>
                                    <p class="text-center">{{$counter+1}}</p>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="nombre_anexo_plan_visita_modificado">
                                </td>
                                <td>
                                    <input type="file" class="form-control" id="anexo_plan_visita_modificado" name="anexo_plan_visita_modificado" accept=".pdf,.doc,.docx,.xls,.xlsx" required>
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
                            onclick="anadirRegistro('tabla_modificacion_adicionales_plan_visita')">
                            Añadir documento
                        </button>
                    </div>
                </div>
                <div class="mb-3 div_observaciones_envio_plan_visita">
                    <label for="observaciones_envio_plan_visita_modificado" class="col-form-label">Observaciones</label>
                    <textarea class="form-control" id="observaciones_envio_plan_visita_modificado"></textarea>
                </div>
            </div>

            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success diagnosticoSubsanado" onclick="planVisitaModificado()">Enviar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalRevisarPlanDeVisita" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Revisión del plan de visita</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="tipo_visita" class="col-form-label">¿El plan de visita requiere modificaciones? (*)</label>
                    <select class="form-select form-control required_revision_plan_visita" id="revision_plan_visita" onchange="resultadoRevisionPlanVisita()">
                        <option value="">--Seleccione--</option>
                        <option value="Si">Si</option>
                        <option value="No">No</option>
                    </select>
                    <div class="mb-3 div_observaciones_plan_visita" style="display: none;">
                        <label for="observaciones_plan_visita" class="col-form-label">Observaciones (*)</label>
                        <textarea class="form-control required_revision_plan_visita" id="observaciones_plan_visita"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success diagnosticoSubsanado" onclick="revisionPlanVisita()">Enviar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalConfirmarInformacionPrevia" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Confirmar información previa a la visita</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="tipo_visita" class="col-form-label">¿Es necesario realizar requerimiento de información previa a la visita de inspección?</label>
                    <select class="form-select form-control required_informacion_previa_visita" id="informacion_previa_visita">
                        <option value="">--Seleccione--</option>
                        <option value="Si">Si</option>
                        <option value="No">No</option>
                    </select>
                </div>
                <div class="mb-3 div_observaciones_informacion_previa">
                    <label for="observaciones_informacion_previa" class="col-form-label">Observaciones</label>
                    <textarea class="form-control required_revision_informacion_previa" id="observaciones_informacion_previa"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success diagnosticoSubsanado" onclick="confirmacionInformacionPreviaVisita()">Enviar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalRequerimientoInformacion" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Requerimiento de información adicional</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="ciclo_vida_requerimiento_informacion_adicional" class="col-form-label">Ciclo de vida del requerimiento enviado (*)</label>
                    <input type="text" class="form-control required_requerimiento_informacion_adicional" id="ciclo_vida_requerimiento_informacion_adicional" required>
                </div>
                <div class="mb-3 div_observaciones_requerimiento_informacion_adicional">
                    <label for="observaciones_requerimiento_informacion_adicional" class="col-form-label">Observaciones</label>
                    <textarea class="form-control required_revision_requerimiento_informacion_adicional" id="observaciones_requerimiento_informacion_adicional"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success enviarRequerimientoInformacion" onclick="finalizarRequerimientoInformacion()">Enviar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalRegistroRespuestaInformacionAdicional" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Confirmación de información adicional por parte de la entidad solidaria</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="confirmacion_informacion_entidad" class="col-form-label">¿La entidad solidaria emitio respuesta? (*)</label>
                    <select class="form-select form-control required_confirmacion_informacion_entidad" id="confirmacion_informacion_entidad" onchange="confirmacionInformacionEntidad()" >
                        <option value="">--Seleccione--</option>
                        <option value="Si">Si</option>
                        <option value="No">No</option>
                    </select>
                </div>
                <div class="mb-3" id="div_radicado_entrada_respuesta_informacion_adicional" style="display: none;" >
                    <label for="radicado_respuesta_entidad" class="col-form-label">Radicado de entrada de la respuesta (*)</label>
                    <input type="text" class="form-control required_confirmacion_informacion_entidad" id="radicado_respuesta_entidad" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success btnEnviar" onclick="resgistrarRespuestaEntidad()">Enviar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalValoracionInformacionRecibida" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Valoración de la información recibida</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="necesidad_visita" class="col-form-label">¿Es necesario efectuar la visita? (*)</label>
                    <select class="form-select form-control required_valoracion_informacion" id="necesidad_visita" onchange="necesidadVisita()">
                        <option value="">--Seleccione--</option>
                        <option value="Si">Si</option>
                        <option value="No">No</option>
                    </select>
                </div>
                <div class="mb-3 div_ciclo_vida_plan_visita_ajustado" style="display: none;" >
                    <label for="ciclo_vida_plan_visita_ajustado" class="col-form-label">Ciclo de vida plan de visita ajustado (*)</label>
                    <input type="text" class="form-control required_valoracion_informacion" id="ciclo_vida_plan_visita_ajustado" required>
                </div>
                <div class="mb-3 div_observaciones_valoracion" style="display: none;">
                    <label for="observaciones_valoracion" class="col-form-label">Observaciones (*)</label>
                    <textarea class="form-control required_valoracion_informacion" id="observaciones_valoracion" required></textarea>
                </div>
                
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success enviarValoracionInformacion" onclick="valoracionInformacionRecibida()">Enviar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalConfirmarVisita" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Confirmar visita de inspección</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="confirmacion_necesidad_visita" class="col-form-label">¿Es necesario efectuar la visita? (*)</label>
                    <select class="form-select form-control required_confirmacion_visita" id="confirmacion_necesidad_visita">
                        <option value="">--Seleccione--</option>
                        <option value="Si">Si</option>
                        <option value="No">No</option>
                    </select>
                </div>
                <div class="mb-3 div_ciclo_vida_plan_visita_ajustado" >
                    <label for="ciclo_vida_confirmacion_visita" class="col-form-label">Ciclo de vida (*)</label>
                    <input type="text" class="form-control required_confirmacion_visita" id="ciclo_vida_confirmacion_visita" required>
                </div>  
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success enviarConfirmacionVisita" onclick="confirmacionVisita()">Enviar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalElaboracionCartasPresentacion" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Elaboración de cartas de presentación</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3" >
                    <label for="ciclo_vida_cartas_presentacion" class="col-form-label">Ciclo de vida cartas de presentación (*)</label>
                    <input type="text" class="form-control required_cartas_presentacion" id="ciclo_vida_cartas_presentacion" required>
                </div>  
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success enviarCartasPresentacion" onclick="cartasPresentacion()">Enviar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAperturaVisitaInspeccion" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Abrir visita de inspección</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3" >
                    <label for="apertura_visita" class="col-form-label">Enlace acta de apertura de la visita o grabación (*)</label>
                    <input type="text" class="form-control required_apertura_visita" id="apertura_visita" required>
                </div>  
                <div class="mb-3" >
                    <label for="carta_salvaguarda" class="col-form-label">Enlace carta salvaguarda (*)</label>
                    <input type="text" class="form-control required_apertura_visita" id="carta_salvaguarda" required>
                </div>  
                <table class="table table-sm" id="tabla_asignacion_grupo_inspeccion_final">
                    <thead>
                        <tr class="text-center">
                            <th class="table-primary">#</th>
                            <th class="table-primary">Inspector</th>
                            <th class="table-primary">Rol</th>
                            <th class="table-primary">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($informe->grupoInspeccion as $index => $grupo)
                            @if($grupo->rol === 'Redactor')
                                <tr class="tr_grupo_inspeccion_final">
                                    <td>
                                        <p class="text-center">1</p>
                                    </td>
                                    <td>
                                        <select class="form-select grupo_inspeccion" id="inspector_redactor" name="usuario">
                                            <option value="">Seleccione</option>
                                            @if(isset($usuariosTotales))
                                                @foreach ($usuariosTotales as $index => $usuarioTotal)
                                                    <option value="{{$usuarioTotal->id}}" {{ $grupo->id_usuario == $usuarioTotal->id ? 'selected' : '' }}>{{$usuarioTotal->name}}</option>
                                                @endforeach  
                                            @endif
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-select grupo_inspeccion" id="inspector_rol_redactor" name="rol" disabled>
                                            <option value="Redactor">Redactor</option>
                                        </select>
                                    </td>
                                    <td>
                                    </td>
                                </tr>
                            @elseif($grupo->rol === 'Inspector')
                                <tr class="tr_grupo_inspeccion_final">
                                    <td>
                                        <p class="text-center">{{$index}}</p>
                                    </td>
                                    <td>
                                        <select class="form-select grupo_inspeccion" name="usuario">
                                            <option value="">Seleccione</option>
                                            @if(isset($usuariosTotales))
                                                @foreach ($usuariosTotales as $index => $usuarioTotal)
                                                    <option value="{{$usuarioTotal->id}}" {{ $grupo->id_usuario == $usuarioTotal->id ? 'selected' : '' }}>{{$usuarioTotal->name}}</option>
                                                @endforeach  
                                            @endif
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-select grupo_inspeccion" name="rol">
                                            <option value="Inspector">Inspector</option>
                                        </select>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-outline-danger" onclick="eliminarInspector(this)">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            @endif
                        @endforeach  
                    </tbody>
                </table>
                <div class="d-grid gap-2 d-flex justify-content-end">
                    <button type="button" class="btn btn-primary" style="--bs-btn-padding-y: .25rem; --bs-btn-padding-x: .5rem; --bs-btn-font-size: .75rem;"
                        onclick="anadirInspectorFinal()">
                        Añadir inspector
                    </button>
                </div>
                
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success abrirVisitaInspeccion" onclick="abrirVisitaInspeccion()">Finalizar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCierreVisitaInspeccion" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Cierre de visita de inspección</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3" >               
                    <label>Documentos del cierre de la visita (*)</label>
                    <table class="table table-sm" id="tabla_documentos_cierre_visita">
                        <thead>
                            <tr class="text-center">
                                <th class="table-primary">#</th>
                                <th class="table-primary">Nombre del documento (*)</th>
                                <th class="table-primary">Enlace (*)</th>
                                <th class="table-primary">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="tr_documentos_cierre_visita">
                                <td>
                                    <p class="text-center">1</p>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="nombre_documento_cierre_visita">
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="enlace_documento_cierre_visita">
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
                            onclick="anadirRegistro('tabla_documentos_cierre_visita')">
                            Añadir documento
                        </button>
                    </div>
                </div>  
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success cerrarVisitaInspeccion" onclick="cerrarVisitaInspeccion()">Finalizar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalRegistrarHallazgos" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Registrar hallazgos</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3" >               
                    <label>Documentos con hallazgos (*)</label>
                    <table class="table table-sm" id="tabla_documentos_hallazgos">
                        <thead>
                            <tr class="text-center">
                                <th class="table-primary">#</th>
                                <th class="table-primary">Nombre del documento (*)</th>
                                <th class="table-primary">Enlace (*)</th>
                                <th class="table-primary">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="tr_documentos_hallazgos">
                                <td>
                                    <p class="text-center">1</p>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="nombre_documento_hallazgo">
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="enlace_documento_hallazgo">
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
                            onclick="anadirRegistro('tabla_documentos_hallazgos')">
                            Añadir documento
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success registrarHallazgos" onclick="registrarHallazgos()">Enviar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalConsolidacionHallazgos" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Registrar hallazgos consolidados</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3" >
                    <label for="registro_hallazgos_consolidados" class="col-form-label">Enlace con hallazgos consolidados (*)</label>
                    <input type="text" class="form-control required_registro_hallazgos_consolidados" id="registro_hallazgos_consolidados" required>
                </div>  
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success registrarHallazgosConsolidados" onclick="registrarHallazgosConsolidados()">Enviar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalProyectoInformeFinal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Registrar proyecto de informe final</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3" >
                    <label for="proyecto_informe_final" class="col-form-label">Enlace proyecto de informe final (*)</label>
                    <input type="text" class="form-control required_proyecto_informe_final" id="proyecto_informe_final" required>
                </div>  
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success registrarProyectoInformeFinal" onclick="registrarProyectoInformeFinal()">Enviar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalProyectoProyevtoInformeFinal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Revisar proyecto de informe final</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="confirmacion_revision_proyecto_informe_final" class="col-form-label">¿Requiere correcciones? (*)</label>
                    <select class="form-select form-control required_revision_proyecto_informe_final" id="confirmacion_revision_proyecto_informe_final">
                        <option value="">--Seleccione--</option>
                        <option value="Si">Si</option>
                        <option value="No">No</option>
                    </select>
                </div>
                <div class="mb-3" >
                    <label for="revision_proyecto_informe_final" class="col-form-label">Enlace de revision proyecto de informe final (*)</label>
                    <input type="text" class="form-control required_revision_proyecto_informe_final" id="revision_proyecto_informe_final" required>
                </div>  

                <div class="mb-3">
                    <label for="observaciones_revision_proyecto_informe_final" class="col-form-label">Observaciones (*)</label>
                    <textarea class="form-control required_revision_proyecto_informe_final" id="observaciones_revision_proyecto_informe_final"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success registrarProyectoInformeFinal" onclick="registrarRevisionProyectoInformeFinal()">Enviar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalVerifcacionesCorrecciones" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Enviar verificación de correcciones del informe final</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="observaciones_verificacion_correcciones_informe_final" class="col-form-label">Observaciones (*)</label>
                    <textarea class="form-control required_verificacion_correcciones_informe_final" id="observaciones_verificacion_correcciones_informe_final"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success registrarProyectoInformeFinal" onclick="verificacionesCorreccionesInformeFinal()">Enviar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEnviarCorreccionesProyectoInformeFinal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Enviar corrección al proyecto del informe final</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3" >
                    <label for="revision_proyecto_informe_final_corregido" class="col-form-label">Enlace del proyecto de informe final corregido (*)</label>
                    <input type="text" class="form-control required_revision_proyecto_informe_final_corregido" id="revision_proyecto_informe_final_corregido" required>
                </div>  
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success registrarProyectoInformeFinalCorregido" onclick="correccionesInformeFinalCorregido()">Enviar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalRemitirInformeFinalCoordinaciones" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Remitir proyecto informe final a coordinaciones</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3" >
                    <label for="revision_proyecto_informe_final_coordinacinoes" class="col-form-label">Enlace del proyecto de informe final (*)</label>
                    <input type="text" class="form-control required_revision_proyecto_informe_final_coordinacinoes" id="revision_proyecto_informe_final_coordinacinoes" required>
                </div>  
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success registrarProyectoInformeFinalCoordinacinoes" onclick="informeFinalCoordinacinoes()">Enviar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalRevisarInformeCoordinaciones" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Revisar informe final</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3" >
                    <label for="revision_informe_final_coordinaciones" class="col-form-label">Enlace con revisiones (*)</label>
                    <input type="text" class="form-control required_revision_informe_final_coordinaciones" id="revision_informe_final_coordinaciones" required>
                </div>  
            
                <div class="mb-3">
                    <label for="observaciones_revision_informe_final_coordinaciones" class="col-form-label">Observaciones</label>
                    <textarea class="form-control required_revision_informe_final_coordinaciones" id="observaciones_revision_informe_final_coordinaciones"></textarea>
            </div>
           </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success registrarProyectoInformeFinalCoordinacinoes" onclick="revisionInformeFinalCoordinacinoes()">Enviar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalRevisionInformeFinalIntendente" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Revisar informe final</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3" >
                    <label for="revision_informe_final_intendente" class="col-form-label">Enlace con revisiones (*)</label>
                    <input type="text" class="form-control required_revision_informe_final_intendente" id="revision_informe_final_intendente" required>
                </div>  
            
                <div class="mb-3">
                    <label for="observaciones_revision_informe_final_intendente" class="col-form-label">Observaciones</label>
                    <textarea class="form-control required_revision_informe_final_intendente" id="observaciones_revision_informe_final_intendente"></textarea>
            </div>
           </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success registrarProyectoInformeFinalCoordinacinoes" onclick="revisionInformeFinalIntendente()">Enviar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalFirmarInformeFinal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Firmar informe final</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3" >
                    <label for="informe_final_firmado" class="col-form-label">Enlace con documento firmado (*)</label>
                    <input type="text" class="form-control required_informe_final_firmado" id="informe_final_firmado" required>
                </div>  
           </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success informeFinalFirmado" onclick="informeFinalFirmado()">Enviar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalConfirmarIntervencionInmediata" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Confirmar medida de intervencion inmediata</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3" >
                    <label for="confirmacion_intervencion_inmediata" class="col-form-label">¿La situación de la vigilada amerita una medida de intervención inmediata, entre tanto se surte el traslado? (*)</label>
                    <select class="form-select form-control required_confirmacion_intervencion_inmediata" id="confirmacion_intervencion_inmediata">
                        <option value="">--Seleccione--</option>
                        <option value="Si">Si</option>
                        <option value="No">No</option>
                    </select>
                </div>  
                <div class="mb-3">
                    <label for="observaciones_intervencion_inmediata" class="col-form-label">Observaciones</label>
                    <textarea class="form-control required_confirmacion_intervencion_inmediata" id="observaciones_intervencion_inmediata"></textarea>
                </div>
           </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success registrarConfirmacionIntervencionInmediata" onclick="registrarConfirmacionIntervencionInmediata()">Enviar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalInformeTraslado" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Enviar informe de visita para traslado</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3" >
                    <label for="ciclo_informe_traslado" class="col-form-label">Ciclo de vida memorando de envío (*)</label>
                    <input type="text" class="form-control required_informe_traslado" id="ciclo_informe_traslado" required>
                </div>  
                <!--<div class="mb-3" >
                    <label for="carta_salvaguarda" class="col-form-label">Usuarios a trasladar (*)</label>
                </div>  
                 <table class="table table-sm" id="tabla_asignacion_designados">
                    <thead>
                        <tr class="text-center">
                            <th class="table-primary">#</th>
                            <th class="table-primary">Inspector</th>
                            <th class="table-primary">Rol</th>
                            <th class="table-primary">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                                <tr class="tr_grupo_designados_traslado">
                                    <td>
                                        <p class="text-center">1</p>
                                    </td>
                                    <td>
                                        <select class="form-select designados_traslado" name="usuario">
                                            <option value="">Seleccione</option>
                                            @if(isset($usuariosTotales))
                                                @foreach ($usuariosTotales as $index => $usuarioTotal)
                                                    <option value="{{$usuarioTotal->id}}">{{$usuarioTotal->name}}</option>
                                                @endforeach  
                                            @endif
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-select designados_traslado" name="rol">
                                            <option value="Designado para traslado">Designado para traslado</option>
                                        </select>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-outline-danger" onclick="eliminarInspector(this)">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            
                        
                    </tbody>
                </table> 
                <div class="d-grid gap-2 d-flex justify-content-end">
                    <button type="button" class="btn btn-primary" style="--bs-btn-padding-y: .25rem; --bs-btn-padding-x: .5rem; --bs-btn-font-size: .75rem;"
                        onclick="anadirDesignado()">
                        Añadir designado
                    </button>
                </div> -->
                
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success abrirVisitaInspeccion" onclick="envarTraslado()">Finalizar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalInformeTrasladoEntidad" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Registrar proyección de informe de traslado</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3" >
                    <label for="ciclo_informe_traslado_entidad" class="col-form-label">Ciclo de vida oficio de traslado del informe (*)</label>
                    <input type="text" class="form-control required_informe_traslado_entidad" id="ciclo_informe_traslado_entidad" required>
                </div>  
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success abrirVisitaInspeccion" onclick="enviarTrasladoEntidad()">Finalizar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalRegistrarPronunciaminetoEntidad" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Registrar pronunciamiento</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3" >
                    <label for="confirmacion_pronunciacion_entidad" class="col-form-label">¿La organización de la economía solidaria realiza pronunciamiento alguno en el marco del traslado? (*)</label>
                    <select class="form-select form-control required_pronunciacion_entidad" id="confirmacion_pronunciacion_entidad" onchange="registroPronunciamiento()">
                        <option value="">--Seleccione--</option>
                        <option value="Si">Si</option>
                        <option value="No">No</option>
                    </select>
                </div>
                <div class="mb-3 div_radicado_entrada_pronunciacion" style="display: none;">
                    <label for="radicado_entrada_pronunciacion" class="col-form-label">Radicado de entrada (*)</label>
                    <input type="text" class="form-control required_pronunciacion_entidad" id="radicado_entrada_pronunciacion" required>
                </div> 
           </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success registrarConfirmacionIntervencionInmediata" onclick="registrarPronunciamientoEntidad()">Enviar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEvaluacionRespuestaVisita" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Registrar valoración de la información recibida</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="evaluacion_respuesta" class="col-form-label">Enlace de evaluación respuesta informe de visita (*)</label>
                    <input type="text" class="form-control required_evaluacion_respuesta" id="evaluacion_respuesta" required>
                </div> 
           </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success registrarConfirmacionIntervencionInmediata" onclick="registrarEvaluacionRespuesta()">Enviar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalHallazgosFinales" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Registrar envío de informe con hallazgos finales</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="ciclo_informe_final_hallazgos" class="col-form-label">Ciclo de vida informe con hallazgos finales (*)</label>
                    <input type="text" class="form-control required_informe_final_hallazgos" id="ciclo_informe_final_hallazgos" required>
                </div> 
           </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success registrarConfirmacionIntervencionInmediata" onclick="registrarInformeHallazgosFinales()">Enviar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalProposisionActuacionAdministrativa" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Proponer actuación administrativa</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3" >
                    <label for="tipo_recomendacion" class="col-form-label">Tipo de recomendación (*)</label>
                    <select class="form-select form-control required_actuacion_administrativa" id="tipo_recomendacion">
                        <option value="">--Seleccione--</option>
                        <option value="Cierre proceso de inspección">Cierre proceso de inspección</option>
                        <option value="Proyectar acto administrativo">Proyectar acto administrativo</option>
                        <option value="Recomendar al grupo sancionatorio">Recomendar al grupo sancionatorio</option>
                        <option value="Solicitar convocatoria del comite">Solicitar convocatoria del comite</option>
                        <option value="Otra">Otra</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="ciclo_informe_final_hallazgos_intendencia" class="col-form-label">Ciclo de vida informe con hallazgos (*)</label>
                    <input type="text" class="form-control required_actuacion_administrativa" id="ciclo_informe_final_hallazgos_intendencia" required>
                </div> 
           </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success registrarConfirmacionIntervencionInmediata" onclick="proponerActuacionAdministrativa()">Enviar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalModificarGupoVisitaInspeccion" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Modificar grupo de inspección</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body"> 
                <table class="table table-sm" id="tabla_modificacion_grupo_inspeccion">
                    <thead>
                        <tr class="text-center">
                            <th class="table-primary">#</th>
                            <th class="table-primary">Inspector</th>
                            <th class="table-primary">Rol</th>
                            <th class="table-primary">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($informe->grupoInspeccion as $index => $grupo)
                            @if($grupo->rol === 'Lider de visita')
                                <tr class="tr_grupo_inspeccion_modificar">
                                    <td>
                                        <p class="text-center">{{$index+1}}</p>
                                    </td>
                                    <td>
                                        <select class="form-select grupo_inspeccion_modificar" id="inspector_lider" name="usuario">
                                            <option value="">Seleccione</option>
                                            @if(isset($usuariosTotales))
                                                @foreach ($usuariosTotales as $index => $usuarioTotal)
                                                    <option value="{{$usuarioTotal->id}}" {{ $grupo->id_usuario == $usuarioTotal->id ? 'selected' : '' }}>{{$usuarioTotal->name}}</option>
                                                @endforeach  
                                            @endif
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-select grupo_inspeccion_modificar" id="inspector_rol_lider" name="rol" disabled>
                                            <option value="Lider de visita">Lider de visita</option>
                                        </select>
                                    </td>
                                    <td>
                                    </td>
                                </tr>
                            @elseif($grupo->rol === 'Redactor')
                                <tr class="tr_grupo_inspeccion_modificar">
                                    <td>
                                        <p class="text-center">{{$index+1}}</p>
                                    </td>
                                    <td>
                                        <select class="form-select grupo_inspeccion_modificar" name="usuario">
                                            <option value="">Seleccione</option>
                                            @if(isset($usuariosTotales))
                                                @foreach ($usuariosTotales as $index => $usuarioTotal)
                                                    <option value="{{$usuarioTotal->id}}" {{ $grupo->id_usuario == $usuarioTotal->id ? 'selected' : '' }}>{{$usuarioTotal->name}}</option>
                                                @endforeach  
                                            @endif
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-select grupo_inspeccion_modificar" name="rol">
                                            <option option value="Redactor">Redactor</option>
                                        </select>
                                    </td>
                                    <td>
                                    </td>
                                </tr>
                            @elseif($grupo->rol === 'Inspector')
                                <tr class="tr_grupo_inspeccion_modificar">
                                    <td>
                                        <p class="text-center">{{$index+1}}</p>
                                    </td>
                                    <td>
                                        <select class="form-select grupo_inspeccion_modificar" name="usuario">
                                            <option value="">Seleccione</option>
                                            @if(isset($usuariosTotales))
                                                @foreach ($usuariosTotales as $index => $usuarioTotal)
                                                    <option value="{{$usuarioTotal->id}}" {{ $grupo->id_usuario == $usuarioTotal->id ? 'selected' : '' }}>{{$usuarioTotal->name}}</option>
                                                @endforeach  
                                            @endif
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-select grupo_inspeccion_modificar" name="rol">
                                            <option value="Inspector">Inspector</option>
                                        </select>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-outline-danger" onclick="eliminarInspector(this)">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            @endif
                        @endforeach  
                    </tbody>
                </table>
                <div class="d-grid gap-2 d-flex justify-content-end">
                    <button type="button" class="btn btn-primary" style="--bs-btn-padding-y: .25rem; --bs-btn-padding-x: .5rem; --bs-btn-font-size: .75rem;"
                        onclick="anadirInspectorModificar()">
                        Añadir inspector
                    </button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="observaciones_modificar_grupo" class="col-form-label">Observación</label>
                        <textarea class="form-control" id="observaciones_modificar_grupo"></textarea>
                    </div>
                </div>
                
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success modificarGrupoVisita" onclick="modificarGrupoInspeccion()">Modificar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalContenidosFinalesExpediente" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Contenidos finales del expediente</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body"> 
                <label for="tabla_ciclos_expediente_final">Ciclos de vida</label>
                <table class="table table-sm" id="tabla_ciclos_expediente_final">
                    <thead>
                        <tr class="text-center">
                            <th class="table-primary">#</th>
                            <th class="table-primary">Ciclo de vida (*)</th>
                            <th class="table-primary">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="tr_ciclo_expediente_final">
                            <td>
                                <p class="text-center">1</p>
                            </td>
                            <td>
                                <input type="text" class="form-control" name="ciclo_vida">
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
                        onclick="anadirRegistro('tabla_ciclos_expediente_final')">
                        Añadir ciclo de vida
                    </button>
                </div>

                <label for="tabla_ciclos_expediente_final">Documentos</label>
                <table class="table table-sm" id="tabla_documentos_finales">
                    <thead>
                        <tr class="text-center">
                            <th class="table-primary">#</th>
                            <th class="table-primary">Nombre del archivo (*)</th>
                            <th class="table-primary">Enlace (*)</th>
                            <th class="table-primary">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="tr_documentos_finales">
                            <td>
                                <p class="text-center">1</p>
                            </td>
                            <td>
                                <input type="text" class="form-control" name="nombre_documento_final">
                            </td>
                            <td>
                                <input type="text" class="form-control" name="enlace_documento">
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
                        onclick="anadirRegistro('tabla_documentos_finales')">
                        Añadir documento
                    </button>
                </div>
                
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success botonEnviar" onclick="contenidosFinalesExpediente()">Enviar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalSuspenter" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Motivo de la suspensión</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="observaciones_suspencion" class="col-form-label">Motivo de la suspensión (*):</label>
                    <textarea class="form-control" id="observaciones_suspencion"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary enviarObservacion" onclick="suspender_visita()">Suspender</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalReanudar" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Reanudar visita de inspección</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="observaciones_reanudacion" class="col-form-label">Observaciones:</label>
                    <textarea class="form-control" id="observaciones_reanudacion"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary enviarObservacion" onclick="reanudar_visita()">Reanudar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="buscarEntidad" tabindex="-1" aria-labelledby="buscarEntidadLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="buscarEntidadLabel">Entidades</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body mb-3">
                <div class="row">
                    <div class="col-12 col-sm-4">
                        <label class="form-label">Código</label>
                        <input type="number" class="form-control" name="codigo_modal" id="codigo_modal" aria-describedby="basic-addon2">
                    </div>
                    <div class="col-12 col-sm-4">
                        <label class="form-label">Nit</label>
                        <input type="number" class="form-control" name="nit_modal" id="nit_modal" aria-describedby="basic-addon2">
                    </div>
                    <div class="col-12 col-sm-4">
                        <label class="form-label">Razón social</label>
                        <input type="text" class="form-control" name="nombre_modal" id="nombre_modal" aria-describedby="basic-addon2">
                    </div>
                </div>

                <div class="col text-end mt-3 mb-3">
                    <button class="btn btn-primary" onclick="buscarEntidad()">Buscar</button>
                </div>

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
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>


</x-app-layout>