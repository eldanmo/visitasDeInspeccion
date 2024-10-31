<x-app-layout>
    

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.0.0/dist/chart.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>

    

    <div class="container">
        <br>

        <div class="row">
            <h4 class="mt-3 mb-3">Visitas de inspección</h4>
            <hr>
                    <div class="row mb-3">
                        <div class="col-12 col-sm-4">
                            <label class="form-label mt-3 text-start">Etapa actual</label>
                                <select class="form-select" id="etapa_actual" name="etapa_actual">
                                    <option value="" selected>Seleccione</option>
                                    
                                </select>
                        </div>
                        <div class="col-12 col-sm-4">
                            <label class="form-label mt-3 text-start">Estado de la etapa</label>
                                <select class="form-select" id="estado_etapa" name="estado_etapa">
                                    <option value="" selected>Seleccione</option>
                                    <option value="VIGENTE">VIGENTE</option>
                                    <option value="EN DESTIEMPO">EN DESTIEMPO</option>
                                    <option value="FINALIZADO">FINALIZADO</option>
                                </select>
                        </div>
                        
                        <div class="col-12 col-sm-4">
                            <label class="form-label mt-3 text-start">Estado de la visita</label>
                                <select class="form-select" id="estado_informe" name="estado_informe">
                                    <option value="" selected>Seleccione</option>
                                    <option value="VIGENTE">VIGENTE</option>
                                    <option value="EN DESTIEMPO">EN DESTIEMPO</option>
                                    <option value="FINALIZADO">FINALIZADO</option>
                                </select>
                        </div>
                        <div class="col-12 col-sm-4">
                            <label class="form-label mt-3 text-start">Fecha de creación del diagnóstico</label>
                            <input type="date" class="form-control" aria-describedby="basic-addon2" id="fecha_inicial" name="fecha_inicial" value="{{ request('fecha_inicial') }}">
                        </div>
                        <div class="col-12 col-sm-4">
                            <label class="form-label mt-3 text-start">Fecha de finalización de la gestión</label>
                            <input type="date" class="form-control" aria-describedby="basic-addon2" id="fecha_final" name="fecha_final" value="{{ request('fecha_final') }}">
                        </div>
                        <div class="col-12 col-sm-4">
                            <label class="form-label mt-3 text-start">Región</label>
                                <select class="form-select" id="region" name="region">
                                    <option value="" selected>Seleccione</option>
                                    <option value="AMAZONICA">AMAZONICA</option>
                                    <option value="ANDINA">ANDINA</option>
                                    <option value="CARIBE">CARIBE</option>
                                    <option value="ORINOQUIA">ORINOQUIA</option>
                                    <option value="PACIFICA">PACIFICA</option>
                                </select>
                        </div>
                        <div class="col-12 col-sm-4">
                            <label class="form-label mt-3 text-start">Departamento</label>
                                <select class="form-select" id="departamentos" name="departamentos">
                                    <option value="" selected>Seleccione</option>
                                </select>
                        </div>
                        <div class="col-12 col-sm-4">
                            <label class="form-label mt-3 text-start">Naturaleza de la organización</label>
                                <select class="form-select" id="naturaleza_organizacion" name="naturaleza_organizacion">
                                    <option value="">Seleccione</option>
                                    <option value="COOPERATIVA">COOPERATIVA</option>
                                    <option value="COOPERATIVA AYC">COOPERATIVA AYC</option>
                                    <option value="FONDO">FONDO</option>
                                    <option value="MUTUAL">MUTUAL</option>
                                    <option value="OTRA ORGANIZACIÓN">OTRA ORGANIZACIÓN</option>
                                </select>
                        </div>
                        <div class="col-12 col-sm-4">
                            <label class="form-label mt-3 text-start">Tipo de organización</label>
                                <select class="form-select" id="tipo_organizacion" name="tipo_organizacion">
                                    <option value="" selected>Seleccione</option>
                                    <option value="ADMINISTRACIONES PÚBLICAS COOPERATIVAS">ADMINISTRACIONES PÚBLICAS COOPERATIVAS</option>
                                    <option value="APORTES Y CRÉDITO">APORTES Y CRÉDITO</option>
                                    <option value="ASOCIACIONES MUTUALES">ASOCIACIONES MUTUALES</option>
                                    <option value="COOPERATIVAS DE TRABAJO ASOCIADO">COOPERATIVAS DE TRABAJO ASOCIADO</option>
                                    <option value="ESPECIALIZADA DE AHORRO Y CREDITO">ESPECIALIZADA DE AHORRO Y CRÉDITO</option>
                                    <option value="ESPECIALIZADA SIN SECCIÓN DE AHORRO">ESPECIALIZADA SIN SECCIÓN DE AHORRO</option>
                                    <option value="FONDOS DE EMPLEADOS">FONDOS DE EMPLEADOS</option>
                                    <option value="INSTITUCIONES AUXILIARES ESPECIALIZADAS">INSTITUCIONES AUXILIARES ESPECIALIZADAS</option>
                                    <option value="INTEGRAL SIN SECCIÓN DE AHORRO">INTEGRAL SIN SECCIÓN DE AHORRO</option>
                                    <option value="MULTIACTIVA SIN SECCIÓN DE AHORRO">MULTIACTIVA SIN SECCIÓN DE AHORRO</option>
                                    <option value="ORGANISMO DE CARACTER ECÓNOMICO">ORGANISMO DE CARACTER ECÓNOMICO</option>
                                    <option value="ORGANISMO DE REPRESENTACIÓN">ORGANISMO DE REPRESENTACIÓN</option>
                                    <option value="OTRAS ORGANIZACIONES">OTRAS ORGANIZACIONES</option>
                                    <option value="PRECOOPERATIVAS">PRECOOPERATIVAS</option>
                                </select>
                        </div>
                        <div class="col-12 col-sm-4">
                            <label class="form-label mt-3 text-start">Nivel de supervisión</label>
                                <select class="form-select" id="nivel_supervision" name="nivel_supervision">
                                    <option value="">Seleccione</option>
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                </select>
                        </div>
                    </div>
                    
                    <div class="col col-sm-12 text-end mt-3 mb-3">
                        <button type="buttin" onclick="estadisticas()" class="btn btn-primary">Buscar</button>
                    </div>
            <hr>

        <h3 class="text-center" id="cantidad_visitas_consultadas" >Visitas de inspección consultadas</h3>

        <div class="col col-sm-12 text-center mb-3" >
            <h4 for="" class="mb-3" ><b>Porcentaje de ejecución de las visitas </b> </h4>
            
            <div class="progress" role="progressbar" aria-label="Animated striped example" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100">
                <div id="progreso_total" class="progress-bar progress-bar-striped progress-bar-animated" style="width: 0%"></div>
            </div>
        </div>    

        <hr>

        <style>
            .map-container {
                position: relative;
                width: 100%;
                max-width: 500px;
                height: auto;
            }

            .map-container-regiones {
                position: relative;
                width: 100%;
                max-width: 500px;
                height: auto;
            }

            .department-label {
                position: absolute;
                font-family: Arial, sans-serif;
                font-size: 13px;
                font-weight: bold;
                color: black;
            }

            .region-label {
                position: absolute;
                font-family: Arial, sans-serif;
                font-size: 13px;
                font-weight: bold;
                color: black;
            }

            .map-container img {
                max-width: 100%;
                height: auto;
            }
            .table {
                margin-top: 20px;
            }
        </style>

        <div class="row">
                <h4 class="text-center">Visitas por departamento</h4>
                <hr>
                
            <div class="col col-sm-6 text-center mt-3 map-container mb-3">
                <img src="{{ asset('images/colombia.svg') }}" alt="Colombia" width="100%" height="auto" class="me-2">
            </div>
                
            <div class="col col-sm-6 text-center">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Departamento</th>
                            <th>Cantidad de vistas</th>
                            <th>% de cumplimiento</th>
                        </tr>
                    </thead>
                    <tbody id="table_cumplimiento_departamental" >
                        
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row">
                <h4 class="text-center">Visitas por región</h4>
                <hr>
                
            <div class="col col-sm-6 text-center">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Región</th>
                            <th>Cantidad de vistas</th>
                            <th>% de cumplimiento</th>
                        </tr>
                    </thead>
                    <tbody id="table_cumplimiento_regional" >
                        
                    </tbody>
                </table>
            </div>

            <div class="col col-sm-6 text-center mt-3 map-container-regiones mb-3">
                <img src="{{ asset('images/colombia_regiones.svg') }}" alt="Regiones de Colombia" width="100%" height="auto" class="me-2">
            </div>
        </div>

        <div class="col col-sm-5 text-center" >
            <h4>Visitas de inspección por naturaleza de la organización</h4>
            <canvas id="naturaleza"></canvas>
        </div>

        <div class="col col-sm-6 text-center" >
            <h4>Visitas de inspección por tipo de organización</h4>
            <canvas id="grafico_tipo_organizacion"></canvas>
        </div>
            
        <div class="col col-sm-12 text-center" >
            <h4>Visitas de inspección por etapa</h4>
            <canvas id="visitas_etapa"></canvas>
        </div>

        <div class="col col-sm-4 text-center mt-3" >
            <h5>Visitas de inspección por estado</h5>
            <canvas id="visitas_estado"></canvas>
        </div>

        <div class="col col-sm-1 text-center" >
        </div>

        <div class="col col-sm-4 text-center mt-3" >
            <h5>Visitas de inspección por estado de la etapa</h5>
            <canvas id="visitas_estado_etapa"></canvas>
        </div>

        <div class="col col-sm-12 text-center mt-3" >
            <h4>Promedio de días por etapa</h4>
            <canvas id="dias_por_estado"></canvas>
        </div>

        </div>

        <script>
            window.onload = estadisticas();
        </script>

        <script src="https://cdn.jsdelivr.net/npm/chartjs-chart-geo@3"></script>

    </div>
</x-app-layout>
