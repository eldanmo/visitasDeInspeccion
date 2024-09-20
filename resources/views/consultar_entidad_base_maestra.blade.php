<x-app-layout >

<style>
    label{
        font-weight: bold;
    }
</style>

    <div class="container">
        <h4 class="mt-3 mb-3">Base maestra de entidades</h4>
        <form action="{{ route('consultar_maestro_entidades') }}" method="GET">
                    <div class="row mb-3">
                        <div class="col-12 col-sm-4 mt-1">
                            <label class="form-label">Código de entidad</label>
                            <input type="text" class="form-control" aria-describedby="basic-addon2" id="codigo_entidad" name="codigo_entidad" value="{{ request('codigo_entidad') }}">
                        </div>
                        <div class="col-12 col-sm-4 mt-1">
                            <label class="form-label">Nit entidad</label>
                            <input type="text" class="form-control" aria-describedby="basic-addon2" id="nit_entidad" name="nit_entidad" value="{{ request('nit_entidad') }}">
                        </div>
                        <div class="col-12 col-sm-4 mt-1">
                            <label class="form-label">Nombre / razón social de entidad</label>
                            <input type="text" class="form-control" aria-describedby="basic-addon2" id="nombre_entidad" name="nombre_entidad" value="{{ request('nombre_entidad') }}">
                        </div>
                        <div class="col-12 col-sm-4 mt-1">
                            <label class="form-label">Estado</label>
                                <select class="form-select" id="estado" name="estado">
                                    <option value="" selected>Seleccione</option>
                                    <option value="ACTIVA" {{ request('estado') == 'ACTIVA' ? 'selected' : '' }}>ACTIVA</option>
                                    <option value="INTERVENIDA" {{ request('estado') == 'INTERVENIDA' ? 'selected' : '' }}>INTERVENIDA</option>
                                    <option value="EN LIQUIDACIÓN" {{ request('estado') == 'EN LIQUIDACIÓN' ? 'selected' : '' }}>EN LIQUIDACIÓN</option>
                                    <option value="MATRICULA CANCELADA" {{ request('estado') == 'MATRICULA CANCELADA' ? 'selected' : '' }}>MATRICULA CANCELADA</option>
                                </select>
                        </div>
                        <div class="col-12 col-sm-4 mt-1">
                            <label class="form-label">Vigilada SES</label>
                                <select class="form-select" id="vigilada_supersolidaria_segun_depuracion" name="vigilada_supersolidaria_segun_depuracion">
                                    <option value="SI" {{ request('vigilada_supersolidaria_segun_depuracion') == 'SI' ? 'selected' : '' }}>SI</option>
                                    <option value="NO" {{ request('vigilada_supersolidaria_segun_depuracion') == 'NO' ? 'selected' : '' }}>NO</option>
                                </select>
                        </div>
                    </div>
                    
                    <div class="col col-sm-12 text-end mt-3 mb-3">
                        <button type="button" class="btn btn-success" style="display: inline-flex; align-items: center;" data-bs-toggle="modal" data-bs-target="#modalCrearEntidad" >
                            <img src="{{ asset('images/create_identity.svg') }}" width="20px" height="20px" alt="crear entidad" class="me-2">
                            <span>Crear entidad</span>
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <img src="{{ asset('images/search.svg') }}" style="display: inline-flex; align-items: center;" width="20px" height="20px" alt="buscar entidad" class="me-2">
                            <span>Buscar</span>
                        </button>
                    </div>
        </form>
        
            <div class="table-responsive">
                    <table class="table table-sm">
                        <tr class="text-center">
                            <th class="table-primary">#</th>
                            <th class="table-primary">NIT</th>
                            <th class="table-primary">ENTIDAD</th>
                            <th class="table-primary">ESTADO</th>
                            <th class="table-primary">ACCIONES</th>
                        </tr>

                        @if(isset($entidades))
                            @foreach ($entidades as $index => $entidad)
                            <tr>
                                <td class="text-center">{{ $index +1 }}</td>
                                <td> {{$entidad->nit}} </td>
                                <td> {{$entidad->razon_social}} </td>
                                <td>{{ $entidad->estado }}</td>
                                <td class="text-center">
                                    
                                    <a href="{{ route('consultar_entidad_maestra', ['id' => $entidad->id]) }}">
                                        <button type="button" class="btn btn-info" style="display: inline-flex; align-items: center;" >
                                            <img src="{{ asset('images/view.svg') }}" width="20px" height="20px" alt="ver entidad" class="me-2">
                                            <span>Ver detalle</span>
                                        </button>
                                    </a> 
                                </td>
                            </tr>
                            @endforeach  
                        @endif
                    </table>

                    @if(isset($entidades) && $entidades->count() > 0)
                        {{ $entidades->links() }}
                    @endif
            </div>

            <div class="modal fade" id="modalCrearEntidad" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h1 class="modal-title fs-5" id="exampleModalLabel">Crear entidad </h1>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">

                            <div class="row">
                                <h3 class="mt-3" >Datos de la entidad</h3>
                                <hr>
                                <div class="col-12 col-sm-4 mt-3 codigo_entidad">
                                    <label for="codigo_entidad" class="form-label">Código</label>
                                    <input type="hidden" class="form-control required" autocomplete="off" id="id" value="{{ $entidad->id ?? '' }}">
                                    <input type="number" class="form-control required" autocomplete="off" required id="codigo_entidad" min="0" value="{{ $entidad->codigo_entidad ?? '' }}">
                                </div>
                                <div class="col-12 col-sm-4 mt-3 nit">
                                    <label for="nit" class="form-label">Nit (*)</label>
                                    <input type="number" class="form-control required" autocomplete="off" required id="nit" min="0" value="{{ $entidad->nit ?? '' }}">
                                </div>
                                <div class="col-12 col-sm-4 mt-3">
                                    <label for="razon_social" class="form-label">Razón social (*)</label>
                                    <input type="text" class="form-control required" autocomplete="off" required id="razon_social" value="{{ $entidad->razon_social ?? '' }}">
                                </div>
                                <div class="col-12 col-sm-4 mt-3 sigla">
                                    <label for="sigla" class="form-label">Sigla</label>
                                    <input type="text" class="form-control required" autocomplete="off" required id="sigla" value="{{ $entidad->sigla ?? '' }}">
                                </div>
                                <div class="col-12 col-sm-4 mt-3 nivel_supervision">
                                    <label for="nivel_supervision" class="form-label">Nivel de supervisión</label>
                                    <select class="form-control required" autocomplete="off" name="nivel_supervision" id="nivel_supervision" required >
                                        <option value="">--Seleccione--</option>
                                        <option value="1" {{ old('nivel_supervision', $entidad->nivel_supervision ?? '') == '1' ? 'selected' : '' }}>1</option>
                                        <option value="2" {{ old('nivel_supervision', $entidad->nivel_supervision ?? '') == '2' ? 'selected' : '' }}>2</option>
                                        <option value="3" {{ old('nivel_supervision', $entidad->nivel_supervision ?? '') == '3' ? 'selected' : '' }}>3</option>
                                    </select>
                                </div>
                                <div class="col-12 col-sm-4 mt-3 naturaleza_organizacion">
                                    <label for="naturaleza_organizacion" class="form-label">Naturaleza de la organización</label>
                                    <select class="form-control required" autocomplete="off" name="naturaleza_organizacion" id="naturaleza_organizacion" required>
                                        <option value="">--Seleccione--</option>
                                        <option value="COOPERATIVA" {{ old('naturaleza_organizacion', $entidad->naturaleza_organizacion ?? '') == 'COOPERATIVA' ? 'selected' : '' }}>COOPERATIVA</option>
                                        <option value="COOPERATIVA AYC" {{ old('naturaleza_organizacion', $entidad->naturaleza_organizacion ?? '') == 'COOPERATIVA AYC' ? 'selected' : '' }}>COOPERATIVA AYC</option>
                                        <option value="FONDO" {{ old('naturaleza_organizacion', $entidad->naturaleza_organizacion ?? '') == 'FONDO' ? 'selected' : '' }}>FONDO</option>
                                        <option value="MUTUAL" {{ old('naturaleza_organizacion', $entidad->naturaleza_organizacion ?? '') == 'MUTUAL' ? 'selected' : '' }}>MUTUAL</option>
                                        <option value="OTRA ORGANIZACIÓN" {{ old('naturaleza_organizacion', $entidad->naturaleza_organizacion ?? '') == 'OTRA ORGANIZACIÓN' ? 'selected' : '' }}>OTRA ORGANIZACIÓN</option>
                                    </select>
                                </div>
                                <div class="col-12 col-sm-4 mt-3 tipo_organizacion">
                                    <label for="tipo_organizacion" class="form-label">Tipo de organización</label>
                                    <select class="form-control required" autocomplete="off" name="tipo_organizacion" id="tipo_organizacion" required onchange="tipo_organizacion()" >
                                        <option value="">--Seleccione--</option>
                                        <option value="ADMINISTRACIONES PÚBLICAS COOPERATIVAS" {{ old('tipo_organizacion', $entidad->tipo_organizacion ?? '') == 'ADMINISTRACIONES PÚBLICAS COOPERATIVAS' ? 'selected' : '' }}>ADMINISTRACIONES PÚBLICAS COOPERATIVAS</option>
                                        <option value="APORTES Y CRÉDITO" {{ old('tipo_organizacion', $entidad->tipo_organizacion ?? '') == 'APORTES Y CRÉDITO' ? 'selected' : '' }}>APORTES Y CRÉDITO</option>
                                        <option value="ASOCIACIONES MUTUALES" {{ old('tipo_organizacion', $entidad->tipo_organizacion ?? '') == 'ASOCIACIONES MUTUALES' ? 'selected' : '' }}>ASOCIACIONES MUTUALES</option>
                                        <option value="COOPERATIVAS DE TRABAJO ASOCIADO" {{ old('tipo_organizacion', $entidad->tipo_organizacion ?? '') == 'COOPERATIVAS DE TRABAJO ASOCIADO' ? 'selected' : '' }}>COOPERATIVAS DE TRABAJO ASOCIADO</option>
                                        <option value="ESPECIALIZADA DE AHORRO Y CREDITO" {{ old('tipo_organizacion', $entidad->tipo_organizacion ?? '') == 'ESPECIALIZADA DE AHORRO Y CREDITO' ? 'selected' : '' }}>ESPECIALIZADA DE AHORRO Y CRÉDITO</option>
                                        <option value="ESPECIALIZADA SIN SECCIÓN DE AHORRO">ESPECIALIZADA SIN SECCIÓN DE AHORRO</option>
                                        <option value="FONDOS DE EMPLEADOS" {{ old('tipo_organizacion', $entidad->tipo_organizacion ?? '') == 'FONDOS DE EMPLEADOS' ? 'selected' : '' }}>FONDOS DE EMPLEADOS</option>
                                        <option value="INSTITUCIONES AUXILIARES ESPECIALIZADAS" {{ old('tipo_organizacion', $entidad->tipo_organizacion ?? '') == 'INSTITUCIONES AUXILIARES ESPECIALIZADAS' ? 'selected' : '' }}>INSTITUCIONES AUXILIARES ESPECIALIZADAS</option>
                                        <option value="INTEGRAL SIN SECCIÓN DE AHORRO" {{ old('tipo_organizacion', $entidad->tipo_organizacion ?? '') == 'INTEGRAL SIN SECCIÓN DE AHORRO' ? 'selected' : '' }}>INTEGRAL SIN SECCIÓN DE AHORRO</option>
                                        <option value="MULTIACTIVA SIN SECCIÓN DE AHORRO" {{ old('tipo_organizacion', $entidad->tipo_organizacion ?? '') == 'MULTIACTIVA SIN SECCIÓN DE AHORRO' ? 'selected' : '' }}>MULTIACTIVA SIN SECCIÓN DE AHORRO</option>
                                        <option value="ORGANISMO DE CARACTER ECÓNOMICO" {{ old('tipo_organizacion', $entidad->tipo_organizacion ?? '') == 'ORGANISMO DE CARACTER ECÓNOMICO' ? 'selected' : '' }}>ORGANISMO DE CARACTER ECÓNOMICO</option>
                                        <option value="ORGANISMO DE REPRESENTACIÓN" {{ old('tipo_organizacion', $entidad->tipo_organizacion ?? '') == 'ORGANISMO DE REPRESENTACIÓN' ? 'selected' : '' }}>ORGANISMO DE REPRESENTACIÓN</option>
                                        <option value="OTRAS ORGANIZACIONES" {{ old('tipo_organizacion', $entidad->tipo_organizacion ?? '') == 'OTRAS ORGANIZACIONES' ? 'selected' : '' }}>OTRAS ORGANIZACIONES</option>
                                        <option value="PRECOOPERATIVAS" {{ old('tipo_organizacion', $entidad->tipo_organizacion ?? '') == 'PRECOOPERATIVAS' ? 'selected' : '' }}>PRECOOPERATIVAS</option>
                                    </select>
                                </div>
                                <div class="col-12 col-sm-4 mt-3 categoria" id="divCategoria" style="display: none;" >
                                    <label for="categoria" class="form-label">Categoría</label>
                                    <select class="form-control required" autocomplete="off" name="categoria" id="categoria" required>
                                        <option value="">--Seleccione--</option>
                                        <option value="BÁSICA" {{ old('categoria', $entidad->categoria ?? '') == 'SI' ? 'selected' : '' }}>BÁSICA</option>
                                        <option value="INTERMEDIA" {{ old('categoria', $entidad->categoria ?? '') == 'NO' ? 'selected' : '' }}>INTERMEDIA</option>
                                        <option value="PLENA" {{ old('categoria', $entidad->categoria ?? '') == 'NO' ? 'selected' : '' }}>PLENA</option>
                                    </select>
                                </div>
                                <div class="col-12 col-sm-4 mt-3 grupo_niif">
                                    <label for="grupo_niif" class="form-label">Grupo NIIF</label>
                                    <select class="form-control required" autocomplete="off" name="grupo_niif" id="grupo_niif" required>
                                        <option value="">--Seleccione--</option>
                                        <option value="I" {{ old('grupo_niif', $entidad->grupo_niif ?? '') == 'I' ? 'selected' : '' }}>I</option>
                                        <option value="II" {{ old('grupo_niif', $entidad->grupo_niif ?? '') == 'II' ? 'selected' : '' }}>II</option>
                                        <option value="III" {{ old('grupo_niif', $entidad->grupo_niif ?? '') == 'III' ? 'selected' : '' }}>III</option>
                                    </select>
                                </div>

                                <div class="col-12 col-sm-4 mt-3 departamento">
                                    <label for="departamento" class="form-label">Departamento (*)</label>
                                    <input type="text" class="form-control required" autocomplete="off" required id="departamento" value="{{ $entidad->departamento ?? '' }}">
                                </div>
                                <div class="col-12 col-sm-4 mt-3 ciudad_municipio">
                                    <label for="ciudad_municipio" class="form-label">Ciudad / Municipio (*)</label>
                                    <input type="text" class="form-control required" autocomplete="off" required id="ciudad_municipio" value="{{ $entidad->ciudad_municipio ?? '' }}">
                                </div>
                                <div class="col-12 col-sm-4 mt-3 direccion">
                                    <label for="direccion" class="form-label">Dirección (*)</label>
                                    <input type="text" class="form-control required" autocomplete="off" required id="direccion" value="{{ $entidad->direccion ?? '' }}">
                                </div>
                                
                                <div class="col-12 col-sm-4 mt-3 numero_asociados">
                                    <label for="numero_asociados" class="form-label">Número de asociados</label>
                                    <input type="number" class="form-control required" autocomplete="off" required id="numero_asociados" min="0" value="{{ $entidad->numero_asociados ?? '' }}">
                                </div>
                                <div class="col-12 col-sm-4 mt-3 numero_asociados">
                                    <label for="numero_asociados" class="form-label">Número de empleados</label>
                                    <input type="number" class="form-control required" autocomplete="off" required id="numero_empleados" min="0" value="{{ $entidad->numero_empleados ?? '' }}">
                                </div>
                                <div class="col-12 col-sm-4 mt-3 total_activos">
                                    <label for="total_activos" class="form-label">Total de activos</label>
                                    <input type="number" class="form-control required" autocomplete="off" required id="total_activos" min="0" value="{{ $entidad->total_activos ?? '' }}">
                                </div>
                                <div class="col-12 col-sm-4 mt-3 total_pasivos">
                                    <label for="total_pasivos" class="form-label">Total de pasivos</label>
                                    <input type="number" class="form-control required" autocomplete="off" required id="total_pasivos" min="0" value="{{ $entidad->total_pasivos ?? '' }}">
                                </div>
                                <div class="col-12 col-sm-4 mt-3 total_patrimonio">
                                    <label for="total_patrimonio" class="form-label">Total de patrimonio</label>
                                    <input type="number" class="form-control required" autocomplete="off" required id="total_patrimonio" min="0" value="{{ $entidad->total_patrimonio ?? '' }}">
                                </div>
                                <div class="col-12 col-sm-4 mt-3 total_ingresos">
                                    <label for="total_ingresos" class="form-label">Total de ingresos</label>
                                    <input type="number" class="form-control required" autocomplete="off" required id="total_ingresos" min="0" value="{{ $entidad->total_ingresos ?? '' }}"> 
                                </div>
                                <div class="col-12 col-sm-4 mt-3 fecha_ultimo_reporte">
                                    <label for="fecha_ultimo_reporte" class="form-label">Fecha de último reporte</label>
                                    <input type="date" class="form-control required" autocomplete="off" required id="fecha_ultimo_reporte" value="{{ isset($entidad->fecha_ultimo_reporte) ? date('Y-m-d', strtotime($entidad->fecha_ultimo_reporte)) : '' }}">
                                </div>
                            </div>

                            <div class="row">
                                <h3 class="mt-3" >Datos de RUES</h3>
                                <hr>
                                <div class="col-12 col-sm-4 mt-3 estado_matricula_rues">
                                    <label for="estado_matricula_rues" class="form-label">Estado de la matricula (*)</label>
                                    <select class="form-control required" autocomplete="off" name="estado_matricula_rues" id="estado_matricula_rues" onchange="estadoMatriculaRues()" >
                                        <option value="">--Seleccione--</option>
                                        <option value="ACTIVA" {{ old('estado_matricula_rues', $entidad->estado_matricula_rues ?? '') == 'ACTIVA' ? 'selected' : '' }}>ACTIVA</option>
                                        <option value="CANCELADA" {{ old('estado_matricula_rues', $entidad->estado_matricula_rues ?? '') == 'CANCELADA' ? 'selected' : '' }}>CANCELADA</option>
                                    </select>
                                </div>
                                <div class="col-12 col-sm-4 mt-3 ano_renovacion_matricula">
                                    <label for="ano_renovacion_matricula" class="form-label">Último año de renovación de matricula (*)</label>
                                    <input type="number" min="1900" max="2100" class="form-control required" autocomplete="off" required id="ano_renovacion_matricula" value="{{ $entidad->ano_renovacion_matricula ?? '' }}">
                                </div>
                                <div class="col-12 col-sm-4 mt-3 fecha_renovacion_matricula">
                                    <label for="fecha_renovacion_matricula" class="form-label">Fecha de renovación de matricula (*)</label>
                                    <input type="date" class="form-control required" autocomplete="off" required id="fecha_renovacion_matricula" value="{{ $entidad->fecha_renovacion_matricula ?? '' }}">
                                </div>
                                <div class="col-12 col-sm-4 mt-3 permiten_notificacion_correo_electronico" style="display: none;" >
                                    <label for="permiten_notificacion_correo_electronico" class="form-label">¿Permiten notificaciones por correo electrónico? (*)</label>
                                    <select class="form-control required" autocomplete="off" name="permiten_notificacion_correo_electronico" id="permiten_notificacion_correo_electronico" onchange="permitenNotificacionCorreoElectronico()" >
                                        <option value="">--Seleccione--</option>
                                        <option value="SI" {{ old('permiten_notificacion_correo_electronico', $entidad->permiten_notificacion_correo_electronico ?? '') == 'SI' ? 'selected' : '' }}>SI</option>
                                        <option value="NO" {{ old('permiten_notificacion_correo_electronico', $entidad->permiten_notificacion_correo_electronico ?? '') == 'NO' ? 'selected' : '' }}>NO</option>
                                        <option value="NO INDICA" {{ old('permiten_notificacion_correo_electronico', $entidad->permiten_notificacion_correo_electronico ?? '') == 'NO INDICA' ? 'selected' : '' }}>NO INDICA</option>
                                    </select>
                                </div>
                                <div class="col-12 col-sm-4 mt-3 correo_notificaciones_judiciales" style="display: none;">
                                    <label for="correo_notificaciones_judiciales" id="label_correo_notificaciones_judiciales" class="form-label">Correo para notificaciones judiciales</label>
                                    <input type="email" class="form-control required" autocomplete="off" required id="correo_notificaciones_judiciales" value="{{ $entidad->correo_notificaciones_judiciales ?? '' }}">
                                </div>
                                <div class="col-12 col-sm-4 mt-3 en_liquidacion_rues" style="display: none;">
                                    <label for="en_liquidacion_rues" class="form-label">En liquidación en RUES (*)</label>
                                    <select class="form-control required" autocomplete="off" name="en_liquidacion_rues" id="en_liquidacion_rues" onchange="enLiquidacionRues()">
                                        <option value="">--Seleccione--</option>
                                        <option value="SI" {{ old('en_liquidacion_rues', $entidad->en_liquidacion_rues ?? '') == 'SI' ? 'selected' : '' }}>SI</option>
                                        <option value="NO" {{ old('en_liquidacion_rues', $entidad->en_liquidacion_rues ?? '') == 'NO' ? 'selected' : '' }}>NO</option>
                                    </select>
                                </div>
                                <div class="col-12 col-sm-4 mt-3 tipo_liquidacion_rues" style="display: none;">
                                    <label for="tipo_liquidacion_rues" class="form-label">Tipo de liquidación en RUES (*)</label>
                                    <select class="form-control required" autocomplete="off" name="tipo_liquidacion_rues" id="tipo_liquidacion_rues" onchange="otroTipoLiquidacion()" >
                                        <option value="">--Seleccione--</option>
                                        <option value="DISUELTA" {{ old('tipo_liquidacion_rues', $entidad->tipo_liquidacion_rues ?? '') == 'DISUELTA' ? 'selected' : '' }}>DISUELTA</option>
                                        <option value="FORSOZA" {{ old('tipo_liquidacion_rues', $entidad->tipo_liquidacion_rues ?? '') == 'FORSOZA' ? 'selected' : '' }}>FORSOZA</option>
                                        <option value="LEY 1233" {{ old('tipo_liquidacion_rues', $entidad->tipo_liquidacion_rues ?? '') == 'LEY 1233' ? 'selected' : '' }}>LEY 1233</option>
                                        <option value="LEY 1727" {{ old('tipo_liquidacion_rues', $entidad->tipo_liquidacion_rues ?? '') == 'LEY 1727' ? 'selected' : '' }}>LEY 1727</option>
                                        <option value="TÉRMINO DE LA DURACIÓN" {{ old('tipo_liquidacion_rues', $entidad->tipo_liquidacion_rues ?? '') == 'TÉRMINO DE LA DURACIÓN' ? 'selected' : '' }}>TÉRMINO DE LA DURACIÓN</option>
                                        <option value="VOLUNTARIA" {{ old('tipo_liquidacion_rues', $entidad->tipo_liquidacion_rues ?? '') == 'VOLUNTARIA' ? 'selected' : '' }}>VOLUNTARIA</option>
                                        <option value="OTRA" {{ old('tipo_liquidacion_rues', $entidad->tipo_liquidacion_rues ?? '') == 'OTRA' ? 'selected' : '' }}>OTRA</option>
                                    </select>
                                </div>
                                <div class="col-12 col-sm-4 mt-3 otro_tipo_liquidacion" style="display: none;">
                                    <label for="otro_tipo_liquidacion" class="form-label">Otro tipo de liquidación  (*)</label>
                                    <input type="text" class="form-control required" autocomplete="off" required id="otro_tipo_liquidacion" value="{{ $entidad->otro_tipo_liquidacion ?? '' }}">
                                </div>
                                <div class="col-12 col-sm-4 mt-3 entidad_que_vigila_rues" style="display: none;">
                                    <label for="entidad_que_vigila_rues" class="form-label">Entidad que vigila según RUES (*)</label>
                                    <select class="form-control required" autocomplete="off" name="entidad_que_vigila_rues" id="entidad_que_vigila_rues" onchange="enteVigilanciaRUES()" >
                                        <option value="">--Seleccione--</option>
                                        <option value="GOBERNACIÓN / ALCALDÍA" {{ old('entidad_que_vigila_rues', $entidad->entidad_que_vigila_rues ?? '') == 'GOBERNACIÓN / ALCALDÍA' ? 'selected' : '' }}>GOBERNACIÓN / ALCALDÍA</option>
                                        <option value="SUPERINTENDENCIA DE LA ENCONOMÍA SOLIDARIA (SES)" {{ old('entidad_que_vigila_rues', $entidad->entidad_que_vigila_rues ?? '') == 'SUPERINTENDENCIA DE LA ENCONOMÍA SOLIDARIA (SES)' ? 'selected' : '' }}>SUPERINTENDENCIA DE LA ENCONOMÍA SOLIDARIA (SES)</option>
                                        <option value="SUPERINTENDENCIA DE INDUSTRIA Y COMERCIO (SIC)" {{ old('entidad_que_vigila_rues', $entidad->entidad_que_vigila_rues ?? '') == 'SUPERINTENDENCIA DE INDUSTRIA Y COMERCIO (SIC)' ? 'selected' : '' }}>SUPERINTENDENCIA DE INDUSTRIA Y COMERCIO (SIC)</option>
                                        <option value="SUPERINTENDENCIA DE NOTARIADO Y REGISTRO (SNR)" {{ old('entidad_que_vigila_rues', $entidad->entidad_que_vigila_rues ?? '') == 'SUPERINTENDENCIA DE NOTARIADO Y REGISTRO (SNR)' ? 'selected' : '' }}>SUPERINTENDENCIA DE NOTARIADO Y REGISTRO (SNR)</option>
                                        <option value="SUPERINTENDENCIA DE SERVICIOS PÚBLICOS DOMICILIARIOS (SSPD)" {{ old('entidad_que_vigila_rues', $entidad->entidad_que_vigila_rues ?? '') == 'SUPERINTENDENCIA DE SERVICIOS PÚBLICOS DOMICILIARIOS (SSPD)' ? 'selected' : '' }}>SUPERINTENDENCIA DE SERVICIOS PÚBLICOS DOMICILIARIOS (SSPD)</option>
                                        <option value="SUPERINTENDENCIA DE SOCIEDADES (SUPERSOCIEDADES)" {{ old('entidad_que_vigila_rues', $entidad->entidad_que_vigila_rues ?? '') == 'SUPERINTENDENCIA DE SOCIEDADES (SUPERSOCIEDADES)' ? 'selected' : '' }}>SUPERINTENDENCIA DE SOCIEDADES (SUPERSOCIEDADES)</option>
                                        <option value="SUPERINTENDENCIA DE SUBSIDIO FAMILIAR" {{ old('entidad_que_vigila_rues', $entidad->entidad_que_vigila_rues ?? '') == 'SUPERINTENDENCIA DE SUBSIDIO FAMILIAR' ? 'selected' : '' }}>SUPERINTENDENCIA DE SUBSIDIO FAMILIAR</option>
                                        <option value="SUPERINTENDENCIA DE TRANSPORTE (SUPERTRANSPORTE)" {{ old('entidad_que_vigila_rues', $entidad->entidad_que_vigila_rues ?? '') == 'SUPERINTENDENCIA DE TRANSPORTE (SUPERTRANSPORTE)' ? 'selected' : '' }}>SUPERINTENDENCIA DE TRANSPORTE (SUPERTRANSPORTE)</option>
                                        <option value="SUPERINTENDENCIA DE VIGILANCIA Y SEGURIDAD PRIVADA" {{ old('entidad_que_vigila_rues', $entidad->entidad_que_vigila_rues ?? '') == 'SUPERINTENDENCIA DE VIGILANCIA Y SEGURIDAD PRIVADA' ? 'selected' : '' }}>SUPERINTENDENCIA DE VIGILANCIA Y SEGURIDAD PRIVADA</option>
                                        <option value="SUPERINTENDENCIA FINANCIERA DE COLOMBIA (SFC)" {{ old('entidad_que_vigila_rues', $entidad->entidad_que_vigila_rues ?? '') == 'SUPERINTENDENCIA FINANCIERA DE COLOMBIA (SFC)' ? 'selected' : '' }}>SUPERINTENDENCIA FINANCIERA DE COLOMBIA (SFC)</option>
                                        <option value="SUPERINTENDENCIA NACIONAL DE SALUD (SUPERSALUD)" {{ old('entidad_que_vigila_rues', $entidad->entidad_que_vigila_rues ?? '') == 'SUPERINTENDENCIA NACIONAL DE SALUD (SUPERSALUD)' ? 'selected' : '' }}>SUPERINTENDENCIA NACIONAL DE SALUD (SUPERSALUD)</option>
                                        <option value="OTRA" {{ old('entidad_que_vigila_rues', $entidad->entidad_que_vigila_rues ?? '') == 'OTRA' ? 'selected' : '' }}>OTRA</option>
                                    </select>
                                </div>
                                <div class="col-12 col-sm-4 mt-3 otro_ente_vigilancia_rues" style="display: none;">
                                    <label for="otro_ente_vigilancia_rues" class="form-label">Otro ente de vigilancia  (*)</label>
                                    <input type="text" class="form-control required" autocomplete="off" required id="otro_ente_vigilancia_rues" value="{{ $entidad->otro_ente_vigilancia_rues ?? '' }}">
                                </div>
                                <div class="col-12 mt-1 objeto_social" style="display: none;">
                                    <label for="objeto_social" class="form-label">Objeto social (*)</label>
                                    <textarea class="form-control required" name="objeto_social" id="objeto_social" value="{{ $entidad->objeto_social ?? '' }}"  autocomplete="off" required ></textarea>
                                </div>
                                <div class="col-12 col-sm-4 mt-3 ecomun" style="display: none;">
                                    <label for="ecomun" class="form-label">¿Esta entidad es ecomun? (*)</label>
                                    <select class="form-control required" autocomplete="off" name="ecomun" id="ecomun">
                                        <option value="">--Seleccione--</option>
                                        <option value="SI" {{ old('ecomun', $entidad->ecomun ?? '') == 'SI' ? 'selected' : '' }}>SI</option>
                                        <option value="NO" {{ old('ecomun', $entidad->ecomun ?? '') == 'NO' ? 'selected' : '' }}>NO</option>
                                    </select>
                                </div>
                                <div class="col-12 col-sm-4 mt-3 cafetera" style="display: none;">
                                    <label for="cafetera" class="form-label">¿Esta entidad es cafetera? (*)</label>
                                    <select class="form-control required" autocomplete="off" name="cafetera" id="cafetera">
                                        <option value="">--Seleccione--</option>
                                        <option value="SI" {{ old('cafetera', $entidad->cafetera ?? '') == 'SI' ? 'selected' : '' }}>SI</option>
                                        <option value="NO" {{ old('cafetera', $entidad->cafetera ?? '') == 'NO' ? 'selected' : '' }}>NO</option>
                                    </select>
                                </div>
                                <div class="col-12 col-sm-4 mt-3 vigilada_supersolidaria_segun_depuracion_crear" style="display: none;">
                                    <label for="vigilada_supersolidaria_segun_depuracion_crear" class="form-label">¿Debe vigilar la superintendencia de la economía solidaria según el resultado de la depuración? (*)</label>
                                    <select class="form-control required" autocomplete="off" name="vigilada_supersolidaria_segun_depuracion_crear" id="vigilada_supersolidaria_segun_depuracion_crear" onchange="vigiladaSupersolidariaSegunDepuracion()" >
                                        <option value="">--Seleccione--</option>
                                        <option value="SI" {{ old('vigilada_supersolidaria_segun_depuracion_crear', $entidad->vigilada_supersolidaria_segun_depuracion_crear ?? '') == 'SI' ? 'selected' : '' }}>SI</option>
                                        <option value="NO" {{ old('vigilada_supersolidaria_segun_depuracion_crear', $entidad->vigilada_supersolidaria_segun_depuracion_crear ?? '') == 'NO' ? 'selected' : '' }}>NO</option>
                                    </select>
                                </div>
                                <div class="col-12 col-sm-4 mt-3 entidad_debe_vigilar_segun_depuracion" style="display: none;">
                                    <label for="entidad_debe_vigilar_segun_depuracion" class="form-label">Entidad que debe vigilar según depuración (*)</label>
                                    <select class="form-control required" autocomplete="off" name="entidad_debe_vigilar_segun_depuracion" id="entidad_debe_vigilar_segun_depuracion" onchange="otroEnteVigilancia()" >
                                        <option value="">--Seleccione--</option>
                                        <option value="GOBERNACIÓN / ALCALDÍA" {{ old('entidad_debe_vigilar_segun_depuracion', $entidad->entidad_debe_vigilar_segun_depuracion ?? '') == 'GOBERNACIÓN / ALCALDÍA' ? 'selected' : '' }}>GOBERNACIÓN / ALCALDÍA</option>
                                        <option value="SUPERINTENDENCIA DE INDUSTRIA Y COMERCIO (SIC)" {{ old('entidad_debe_vigilar_segun_depuracion', $entidad->entidad_debe_vigilar_segun_depuracion ?? '') == 'SUPERINTENDENCIA DE INDUSTRIA Y COMERCIO (SIC)' ? 'selected' : '' }}>SUPERINTENDENCIA DE INDUSTRIA Y COMERCIO (SIC)</option>
                                        <option value="SUPERINTENDENCIA DE NOTARIADO Y REGISTRO (SNR)" {{ old('entidad_debe_vigilar_segun_depuracion', $entidad->entidad_debe_vigilar_segun_depuracion ?? '') == 'SUPERINTENDENCIA DE NOTARIADO Y REGISTRO (SNR)' ? 'selected' : '' }}>SUPERINTENDENCIA DE NOTARIADO Y REGISTRO (SNR)</option>
                                        <option value="SUPERINTENDENCIA DE SERVICIOS PÚBLICOS DOMICILIARIOS (SSPD)" {{ old('entidad_debe_vigilar_segun_depuracion', $entidad->entidad_debe_vigilar_segun_depuracion ?? '') == 'SUPERINTENDENCIA DE SERVICIOS PÚBLICOS DOMICILIARIOS (SSPD)' ? 'selected' : '' }}>SUPERINTENDENCIA DE SERVICIOS PÚBLICOS DOMICILIARIOS (SSPD)</option>
                                        <option value="SUPERINTENDENCIA DE SOCIEDADES (SUPERSOCIEDADES)" {{ old('entidad_debe_vigilar_segun_depuracion', $entidad->entidad_debe_vigilar_segun_depuracion ?? '') == 'SUPERINTENDENCIA DE SOCIEDADES (SUPERSOCIEDADES)' ? 'selected' : '' }}>SUPERINTENDENCIA DE SOCIEDADES (SUPERSOCIEDADES)</option>
                                        <option value="SUPERINTENDENCIA DE SUBSIDIO FAMILIAR" {{ old('entidad_debe_vigilar_segun_depuracion', $entidad->entidad_debe_vigilar_segun_depuracion ?? '') == 'SUPERINTENDENCIA DE SUBSIDIO FAMILIAR' ? 'selected' : '' }}>SUPERINTENDENCIA DE SUBSIDIO FAMILIAR</option>
                                        <option value="SUPERINTENDENCIA DE TRANSPORTE (SUPERTRANSPORTE)" {{ old('entidad_debe_vigilar_segun_depuracion', $entidad->entidad_debe_vigilar_segun_depuracion ?? '') == 'SUPERINTENDENCIA DE TRANSPORTE (SUPERTRANSPORTE)' ? 'selected' : '' }}>SUPERINTENDENCIA DE TRANSPORTE (SUPERTRANSPORTE)</option>
                                        <option value="SUPERINTENDENCIA DE VIGILANCIA Y SEGURIDAD PRIVADA" {{ old('entidad_debe_vigilar_segun_depuracion', $entidad->entidad_debe_vigilar_segun_depuracion ?? '') == 'SUPERINTENDENCIA DE VIGILANCIA Y SEGURIDAD PRIVADA' ? 'selected' : '' }}>SUPERINTENDENCIA DE VIGILANCIA Y SEGURIDAD PRIVADA</option>
                                        <option value="SUPERINTENDENCIA FINANCIERA DE COLOMBIA (SFC)" {{ old('entidad_debe_vigilar_segun_depuracion', $entidad->entidad_debe_vigilar_segun_depuracion ?? '') == 'SUPERINTENDENCIA FINANCIERA DE COLOMBIA (SFC)' ? 'selected' : '' }}>SUPERINTENDENCIA FINANCIERA DE COLOMBIA (SFC)</option>
                                        <option value="SUPERINTENDENCIA NACIONAL DE SALUD (SUPERSALUD)" {{ old('entidad_debe_vigilar_segun_depuracion', $entidad->entidad_debe_vigilar_segun_depuracion ?? '') == 'SUPERINTENDENCIA NACIONAL DE SALUD (SUPERSALUD)' ? 'selected' : '' }}>SUPERINTENDENCIA NACIONAL DE SALUD (SUPERSALUD)</option>
                                        <option value="OTRA" {{ old('entidad_debe_vigilar_segun_depuracion', $entidad->entidad_debe_vigilar_segun_depuracion ?? '') == 'OTRA' ? 'selected' : '' }}>OTRA</option>
                                    </select>
                                </div>
                                <div class="col-12 col-sm-4 mt-3 otro_ente_vigilancia" style="display: none;">
                                    <label for="otro_ente_vigilancia" class="form-label">Otro ente de vigilancia  (*)</label>
                                    <input type="text" class="form-control required" autocomplete="off" required id="otro_ente_vigilancia" value="{{ $entidad->otro_ente_vigilancia ?? '' }}">
                                </div>
                                <div class="col-12 col-sm-4 mt-3 certificado_rues">
                                    <label for="certificado_rues" class="col-form-label">Certificado RUES (*)</label>
                                    <input type="file" class="form-control" id="certificado_rues" name="certificado_rues" accept=".pdf" required>
                                </div>
                            </div>

                            <div class="row">
                                <h3 class="mt-3" >Datos de contacto</h3>
                                <hr>
                                <div class="col-12 col-sm-4 mt-3 representate_legal">
                                    <label for="representate_legal" class="form-label">Representante legal (*)</label>
                                    <input type="text" class="form-control required" autocomplete="off" required id="representate_legal" value="{{ $entidad->representate_legal ?? '' }}">
                                </div>
                                <div class="col-12 col-sm-4 mt-3 correo_representate_legal">
                                    <label for="correo_representate_legal" class="form-label">Correo representante legal</label>
                                    <input type="email" class="form-control required" autocomplete="off" required id="correo_representate_legal" value="{{ $entidad->correo_representate_legal ?? '' }}">
                                </div>
                                <div class="col-12 col-sm-4 mt-3 telefono_representate_legal">
                                    <label for="telefono_representate_legal" class="form-label">Teléfono representante legal</label>
                                    <input type="text" class="form-control required" autocomplete="off" required id="telefono_representate_legal" value="{{ $entidad->telefono_representate_legal ?? '' }}">
                                </div>
                                <div class="col-12 col-sm-4 mt-3 tipo_revisor_fiscal">
                                    <label for="tipo_revisor_fiscal" class="form-label">Tipo de revisor fiscal</label>
                                    <select class="form-control required" autocomplete="off" name="tipo_revisor_fiscal" id="tipo_revisor_fiscal" required onchange="tipo_revisor_fiscal()" >
                                        <option value="">--Seleccione--</option>
                                        <option value="PERSONA NATURAL" {{ old('tipo_revisor_fiscal', $entidad->tipo_revisor_fiscal ?? '') == 'PERSONA NATURAL' ? 'selected' : '' }}>PERSONA NATURAL</option>
                                        <option value="PERSONA JURÍDICA" {{ old('tipo_revisor_fiscal', $entidad->tipo_revisor_fiscal ?? '') == 'PERSONA JURÍDICA' ? 'selected' : '' }}>PERSONA JURÍDICA</option>
                                        <option value="NO INDICA" {{ old('tipo_revisor_fiscal', $entidad->tipo_revisor_fiscal ?? '') == 'NO INDICA' ? 'selected' : '' }}>NO INDICA</option>
                                    </select>
                                </div>
                                <div class="col-12 col-sm-4 mt-3 razon_social_revision_fiscal" style="display: none;">
                                    <label for="razon_social_revision_fiscal" class="form-label">Razón social revisión fiscal</label>
                                    <input type="text" class="form-control required" autocomplete="off" required id="razon_social_revision_fiscal" value="{{ $entidad->razon_social_revision_fiscal ?? '' }}">
                                </div>
                                <div class="col-12 col-sm-4 mt-3 nombre_revisor_fiscal">
                                    <label for="nombre_revisor_fiscal" class="form-label">Nombre de la persona revisora fiscal</label>
                                    <input type="text" class="form-control required" autocomplete="off" required id="nombre_revisor_fiscal" value="{{ $entidad->nombre_revisor_fiscal ?? '' }}">
                                </div>
                                <div class="col-12 col-sm-4 mt-3 direccion_revisor_fiscal">
                                    <label for="direccion_revisor_fiscal" class="form-label">Dirección revisoria fiscal</label>
                                    <input type="text" class="form-control required" autocomplete="off" required id="direccion_revisor_fiscal" value="{{ $entidad->direccion_revisor_fiscal ?? '' }}">
                                </div>
                                <div class="col-12 col-sm-4 mt-3 telefono_revisor_fiscal">
                                    <label for="telefono_revisor_fiscal" class="form-label">Teléfono revisoria fiscal</label>
                                    <input type="text" class="form-control required" autocomplete="off" required id="telefono_revisor_fiscal" value="{{ $entidad->telefono_revisor_fiscal ?? '' }}">
                                </div>
                                <div class="col-12 col-sm-4 mt-3 correo_revisor_fiscal">
                                    <label for="correo_revisor_fiscal" class="form-label">Correo revisoria fiscal</label>
                                    <input type="email" class="form-control required" autocomplete="off" required id="correo_revisor_fiscal" value="{{ $entidad->correo_revisor_fiscal ?? '' }}">
                                </div>
                            </div>

                            <div class="mb-3 div_observaciones">
                                <label for="observaciones" class="col-form-label">Observaciones</label>
                                <textarea class="form-control" id="observaciones"></textarea>
                            </div>
                        </div>

                        
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                            <button type="button" class="btn btn-success diagnosticoSubsanado" onclick="crearEntidadMaestra()">Enviar</button>
                        </div>
                    </div>
                </div>
            </div>
    </div>
</x-app-layout >
