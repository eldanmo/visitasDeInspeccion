<x-app-layout>

    @if(Auth::user()->profile === 'Coordinador' || Auth::user()->profile === 'Administrador')
        <div class="container">

            <style>
                .custom-file-input {
                    display: none;
                }

                .custom-file-label {
                    display: inline-block;
                    cursor: pointer;
                    background-color: #007bff;
                    color: #fff;
                    padding: 10px 20px;
                    border-radius: 5px;
                    margin-top: 10px;
                    text-align: center;
                    transition: background-color 0.3s ease;
                }

                .custom-file-label:hover {
                    background-color: #0056b3;
                }

            </style>

            <div class="row d-flex align-items-center">
                <div class="col">
                    <h3 class="mt-3">Entidad</h3>
                </div>
                <div class="col-auto ms-auto">
                    <button class="btn btn-outline-success d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#modalCargueEntidadMasivo">
                        <img src="{{ asset('images/excel.svg') }}" alt="..." style="height: 20px; object-fit: contain;" class="me-2">
                        Cargue masivo
                    </button>
                </div>
            </div>

            <div class="modal fade" id="modalCargueEntidadMasivo" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h1 class="modal-title fs-5" id="exampleModalLabel">Cargue masivo de entidades</h1>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <div class="row d-flex mt-3 justify-content-end">
                                    <a href="{{ route('descargar_plantilla_cargue_masivo') }}" class="d-flex align-items-center text-decoration-none" style="cursor: pointer;">
                                        <img src="{{ asset('images/download.svg') }}" alt="Descargar" style="height: 20px; object-fit: contain;" class="me-2">
                                        <label class="col-form-label m-0" style="cursor: pointer;">
                                            Descargar plantilla
                                        </label>
                                    </a>
                                </div>
                                <label for="archivo" class="col-form-label">Excel diligenciado (*)</label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="archivo_entidades" accept=".xls,.xlsx,.csv">
                                    <label class="custom-file-label" for="archivo_entidades">Seleccionar archivo</label>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                            <button type="button" class="btn btn-success" onclick="uploadFile()">Crear</button>
                        </div>
                    </div>
                </div>
            </div>

            <hr>



            <div class="row">
                <div class="col-12 col-sm-4 mt-3 codigo_entidad">
                    <label for="codigo_entidad" class="form-label">Código (*)</label>
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
                    <label for="nivel_supervision" class="form-label">Nivel de supervisión (*)</label>
                    <select class="form-control required" autocomplete="off" name="nivel_supervision" id="nivel_supervision" required >
                        <option value="">--Seleccione--</option>
                        <option value="1" {{ old('nivel_supervision', $entidad->nivel_supervision ?? '') == '1' ? 'selected' : '' }}>1</option>
                        <option value="2" {{ old('nivel_supervision', $entidad->nivel_supervision ?? '') == '2' ? 'selected' : '' }}>2</option>
                        <option value="3" {{ old('nivel_supervision', $entidad->nivel_supervision ?? '') == '3' ? 'selected' : '' }}>3</option>
                    </select>
                </div>
                <div class="col-12 col-sm-4 mt-3 naturaleza_organizacion">
                    <label for="naturaleza_organizacion" class="form-label">Naturaleza de la organización (*)</label>
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
                    <label for="tipo_organizacion" class="form-label">Tipo de organización (*)</label>
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
                    <label for="categoria" class="form-label">Categoría (*)</label>
                    <select class="form-control required" autocomplete="off" name="categoria" id="categoria" required>
                        <option value="">--Seleccione--</option>
                        <option value="BÁSICA" {{ old('categoria', $entidad->categoria ?? '') == 'SI' ? 'selected' : '' }}>BÁSICA</option>
                        <option value="INTERMEDIA" {{ old('categoria', $entidad->categoria ?? '') == 'NO' ? 'selected' : '' }}>INTERMEDIA</option>
                        <option value="PLENA" {{ old('categoria', $entidad->categoria ?? '') == 'NO' ? 'selected' : '' }}>PLENA</option>
                    </select>
                </div>
                <div class="col-12 col-sm-4 mt-3 grupo_niif">
                    <label for="grupo_niif" class="form-label">Grupo NIIF (*)</label>
                    <select class="form-control required" autocomplete="off" name="grupo_niif" id="grupo_niif" required>
                        <option value="">--Seleccione--</option>
                        <option value="I" {{ old('grupo_niif', $entidad->grupo_niif ?? '') == 'I' ? 'selected' : '' }}>I</option>
                        <option value="II" {{ old('grupo_niif', $entidad->grupo_niif ?? '') == 'II' ? 'selected' : '' }}>II</option>
                        <option value="III" {{ old('grupo_niif', $entidad->grupo_niif ?? '') == 'III' ? 'selected' : '' }}>III</option>
                    </select>
                </div>
                <div class="col-12 col-sm-4 mt-3 incluye_sarlaft">
                    <label for="incluye_sarlaft" class="form-label">La visita incluye revisión sarlaft</label>
                    <select class="form-control required" autocomplete="off" name="nivel_supervision" id="incluye_sarlaft" required>
                        <option value="">--Seleccione--</option>
                        <option value="SI" {{ old('incluye_sarlaft', $entidad->incluye_sarlaft ?? '') == 'SI' ? 'selected' : '' }}>SI</option>
                        <option value="NO" {{ old('incluye_sarlaft', $entidad->incluye_sarlaft ?? '') == 'NO' ? 'selected' : '' }}>NO</option>
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
                    <label for="numero_asociados" class="form-label">Número de asociados (*)</label>
                    <input type="number" class="form-control required" autocomplete="off" required id="numero_asociados" min="0" value="{{ $entidad->numero_asociados ?? '' }}">
                </div>
                <div class="col-12 col-sm-4 mt-3 numero_asociados">
                    <label for="numero_asociados" class="form-label">Número de empleados</label>
                    <input type="number" class="form-control required" autocomplete="off" required id="numero_empleados" min="0" value="{{ $entidad->numero_empleados ?? '' }}">
                </div>
                <div class="col-12 col-sm-4 mt-3 total_activos">
                    <label for="total_activos" class="form-label">Total de activos (*)</label>
                    <input type="number" class="form-control required" autocomplete="off" required id="total_activos" min="0" value="{{ $entidad->total_activos ?? '' }}">
                </div>
                <div class="col-12 col-sm-4 mt-3 total_pasivos">
                    <label for="total_pasivos" class="form-label">Total de pasivos (*)</label>
                    <input type="number" class="form-control required" autocomplete="off" required id="total_pasivos" min="0" value="{{ $entidad->total_pasivos ?? '' }}">
                </div>
                <div class="col-12 col-sm-4 mt-3 total_patrimonio">
                    <label for="total_patrimonio" class="form-label">Total de patrimonio (*)</label>
                    <input type="number" class="form-control required" autocomplete="off" required id="total_patrimonio" min="0" value="{{ $entidad->total_patrimonio ?? '' }}">
                </div>
                <div class="col-12 col-sm-4 mt-3 total_ingresos">
                    <label for="total_ingresos" class="form-label">Total de ingresos (*)</label>
                    <input type="number" class="form-control required" autocomplete="off" required id="total_ingresos" min="0" value="{{ $entidad->total_ingresos ?? '' }}"> 
                </div>
                <div class="col-12 col-sm-4 mt-3 fecha_ultimo_reporte">
                    <label for="fecha_ultimo_reporte" class="form-label">Fecha de último reporte (*)</label>
                    <input type="date" class="form-control required" autocomplete="off" required id="fecha_ultimo_reporte" value="{{ isset($entidad->fecha_ultimo_reporte) ? date('Y-m-d', strtotime($entidad->fecha_ultimo_reporte)) : '' }}">
                </div>
                <div class="col-12 col-sm-4 mt-3 fecha_corte_visita">
                    <label for="fecha_corte_visita" class="form-label">Fecha de corte de la visita (*)</label>
                    <input type="date" class="form-control required" autocomplete="off" required id="fecha_corte_visita" value="{{ isset($entidad->fecha_corte_visita) ? date('Y-m-d', strtotime($entidad->fecha_corte_visita)) : '' }}">
                </div>
            </div>

            <hr>

        <h3 class="mt-3" >Datos de contacto</h3>
            <hr>
            
            <div class="row">
                <div class="col-12 col-sm-4 mt-3 representate_legal">
                    <label for="representate_legal" class="form-label">Representanción legal (*)</label>
                    <input type="text" class="form-control required" autocomplete="off" required id="representate_legal" value="{{ $entidad->representate_legal ?? '' }}">
                </div>
                <div class="col-12 col-sm-4 mt-3 correo_representate_legal">
                    <label for="correo_representate_legal" class="form-label">Correo de la representación legal (*)</label>
                    <input type="email" class="form-control required" autocomplete="off" required id="correo_representate_legal" value="{{ $entidad->correo_representate_legal ?? '' }}">
                </div>
                <div class="col-12 col-sm-4 mt-3 telefono_representate_legal">
                    <label for="telefono_representate_legal" class="form-label">Teléfono de la representación legal</label>
                    <input type="text" class="form-control required" autocomplete="off" required id="telefono_representate_legal" value="{{ $entidad->telefono_representate_legal ?? '' }}">
                </div>
                <div class="col-12 col-sm-4 mt-3 tipo_revisor_fiscal">
                    <label for="tipo_revisor_fiscal" class="form-label">Tipo de revisor fiscal (*)</label>
                    <select class="form-control required" autocomplete="off" name="tipo_revisor_fiscal" id="tipo_revisor_fiscal" required onchange="tipo_revisor_fiscal()" >
                        <option value="">--Seleccione--</option>
                        <option value="PERSONA NATURAL" {{ old('tipo_revisor_fiscal', $entidad->tipo_revisor_fiscal ?? '') == 'PERSONA NATURAL' ? 'selected' : '' }}>PERSONA NATURAL</option>
                        <option value="PERSONA JURÍDICA" {{ old('tipo_revisor_fiscal', $entidad->tipo_revisor_fiscal ?? '') == 'PERSONA JURÍDICA' ? 'selected' : '' }}>PERSONA JURÍDICA</option>
                    </select>
                </div>
                <div class="col-12 col-sm-4 mt-3 razon_social_revision_fiscal" style="display: none;">
                    <label for="razon_social_revision_fiscal" class="form-label">Razón social revisión fiscal (*)</label>
                    <input type="text" class="form-control required" autocomplete="off" required id="razon_social_revision_fiscal" value="{{ $entidad->razon_social_revision_fiscal ?? '' }}">
                </div>
                <div class="col-12 col-sm-4 mt-3 nombre_revisor_fiscal">
                    <label for="nombre_revisor_fiscal" class="form-label">Nombre de la persona revisora fiscal (*)</label>
                    <input type="text" class="form-control required" autocomplete="off" required id="nombre_revisor_fiscal" value="{{ $entidad->nombre_revisor_fiscal ?? '' }}">
                </div>
                <div class="col-12 col-sm-4 mt-3 direccion_revisor_fiscal">
                    <label for="direccion_revisor_fiscal" class="form-label">Dirección revisoria fiscal (*)</label>
                    <input type="text" class="form-control required" autocomplete="off" required id="direccion_revisor_fiscal" value="{{ $entidad->direccion_revisor_fiscal ?? '' }}">
                </div>
                <div class="col-12 col-sm-4 mt-3 telefono_revisor_fiscal">
                    <label for="telefono_revisor_fiscal" class="form-label">Teléfono revisoria fiscal (*)</label>
                    <input type="text" class="form-control required" autocomplete="off" required id="telefono_revisor_fiscal" value="{{ $entidad->telefono_revisor_fiscal ?? '' }}">
                </div>
                <div class="col-12 col-sm-4 mt-3 correo_revisor_fiscal">
                    <label for="correo_revisor_fiscal" class="form-label">Correo revisoria fiscal (*)</label>
                    <input type="email" class="form-control required" autocomplete="off" required id="correo_revisor_fiscal" value="{{ $entidad->correo_revisor_fiscal ?? '' }}">
                </div>
            </div>

            <div class="col col-sm-12 text-center mt-3">
                <button type="button" class="btn btn-success" onclick="guardarEntidad()" >Guardar</button>
            </div>
        </div>
    @else
        <div class="container">
            <h3>No tienes permisos para ingresar a esta página</h3>
        </div>
    @endif

    <script>
        document.querySelector('.custom-file-input').addEventListener('change', function(e) {
            var fileName = document.getElementById("archivo_entidades").files[0].name;
            var nextSibling = e.target.nextElementSibling;
            nextSibling.innerText = fileName;
        });
    </script>

</x-app-layout>
