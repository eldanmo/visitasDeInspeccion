<x-app-layout>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.0.0/dist/chart.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>

    <div class="container text-center">
        <br>

        <div class="row text-center">
            
            <h3>Visitas de inspección actuales - {{$cantidad_visitas_actuales}}</h3>
            

        <div class="col col-sm-12" >
            <h4>Visitas de inspección por etapa</h4>
            <canvas id="visitas_etapa"></canvas>
        </div>

        <div class="col col-sm-6 text-center mt-3" >
            <h4>Visitas de inspección por estado</h4>
            <canvas id="visitas_estado"></canvas>
        </div>

        <div class="col col-sm-6 text-center mt-3" >
            <h4>Visitas de inspección por estado de la etapa</h4>
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

    </div>
</x-app-layout>
