<x-app-layout>
    <div class="container text-center">
        <br>

        @if(Auth::user()->profile !== NULL)
            <div class="row text-center">

                    @if(Auth::user()->profile === 'Administrador')
                        <div class="col col-sm-5 col-md-5 col-lg-3 border border-warning text-center mr-3 mt-3" style="background-color: orange;" >
                            <div class="row text-center p-3 justify-content-center">
                                <div class="col col-sm-12">
                                    <h5 style="color: white;">Base maestra de entidades</h5>
                                </div>
                                <a href="{{ url('/consultar_maestro_entidades') }}" class="col-10 col-sm-10 col-md-10 col-lg-3 border border-warning pt-3 m-3 text-center flex flex-col items-center bg-white" style="padding: 0 45px; text-decoration: none; color: inherit;">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                    </svg>                      
                                    <p>Entidades</p>
                                </a>
                            </div>
                        </div>
                    @endif

                @if(Auth::user()->profile === 'Coordinador' || Auth::user()->profile === 'Administrador' || Auth::user()->profile === 'Delegado' || Auth::user()->profile === 'Contratista' || Auth::user()->profile === 'Intendente' )
                    <div class="col col-sm-5 col-md-5 col-lg-8 border border-primary text-center mr-3 mt-3 bg-primary">
                    
                        <div class="row text-center p-3 justify-content-center">
                            <div class="col col-sm-12">
                                <h5 style="color: white;">Visitas de inspección</h5> 
                            </div>
                            @if(Auth::user()->profile === 'Coordinador' || Auth::user()->profile === 'Intendente' || Auth::user()->profile === 'Administrador' )
                                <a href="{{ url('/crear_entidad') }}" class="col-10 col-sm-10 col-md-10 col-lg-2 border border-success pt-3 m-3 text-center flex flex-col items-center bg-white" style="padding: 0 45px; text-decoration: none; color: inherit;">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>                   
                                    <p>Crear entidades</p>
                                </a>
                                <a href="{{ url('/consultar_entidad') }}"  class="col-10 col-sm-10 col-md-10 col-lg-2 border border-success pt-3 m-3 text-center flex flex-col items-center bg-white" style="padding: 0 45px; text-decoration: none; color: inherit;">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                    </svg>                        
                                    <p>Consultar entidades</p>
                                </a>
                            @endif
                            @if(Auth::user()->profile === 'Administrador' || Auth::user()->profile === 'Intendente')
                                <a href="{{ url('/crear_diagnostico') }}" class="col-10 col-sm-10 col-md-10 col-lg-2 border border-warning pt-3 m-3 text-center flex flex-col items-center bg-white" style="padding: 0 45px; text-decoration: none; color: inherit;">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    <p >Crear diagnóstico</p>
                                </a>
                            @endif
                            <a href="{{ url('/consultar_informe') }}"  class="col-10 col-sm-10 col-md-10 col-lg-2 border border-warning pt-3 m-3 text-center flex flex-col items-center bg-white" style="padding: 0 45px; text-decoration: none; color: inherit;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                </svg>
                                <p>Consultar visitas</p>
                            </a>
                            <a href="{{ url('/estadisticas') }}" class="col-10 col-sm-10 col-md-10 col-lg-2 border border-warning pt-3 m-3 text-center flex flex-col items-center bg-white" style="padding: 0 45px; text-decoration: none; color: inherit;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25M9 16.5v.75m3-3v3M15 12v5.25m-4.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                                <p>Estadísticas</p>
                            </a>

                            


                        </div>
                    </div>
                @endif

                @if(Auth::user()->profile === 'Coordinacion asuntos especiales' || Auth::user()->profile === 'Administrador' || Auth::user()->profile === 'Profesional asuntos especiales' )
                    <div class="col col-sm-5 col-md-5 col-lg-3 border border-secondary text-center mr-3 mt-3 bg-secondary">
                        <div class="row text-center p-3 justify-content-center">
                            <div class="col col-sm-12">
                                <h5 style="color: white;">Asuntos especiales</h5>
                            </div>
                            <a href="{{ url('/consultar_entidad_asunto_especial') }}"  class="col-10 col-sm-10 col-md-10 col-lg-4 border border-secondary pt-3 m-3 text-center flex flex-col items-center bg-white" style="padding: 0 45px; text-decoration: none; color: inherit;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                </svg>                        
                                <p>Consultar</p>
                            </a>
                        </div>
                    </div>
                @endif

                @if(Auth::user()->profile === 'Coordinador' || Auth::user()->profile === 'Administrador' )
                    <!-- <div class="col col-sm-5 col-md-5 col-lg-3 border border-success text-center mr-3 mt-3 bg-success">
                        <div class="row text-center p-3 justify-content-center">
                            <div class="col col-sm-12">
                                <h5 style="color: white;">Entidades</h5>
                            </div>
                            <a href="{{ url('/crear_entidad') }}" class="col-10 col-sm-10 col-md-10 col-lg-3 border border-success pt-3 m-3 text-center flex flex-col items-center bg-white" style="padding: 0 45px; text-decoration: none; color: inherit;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>                   
                                <p>Crear</p>
                            </a>
                            <a href="{{ url('/consultar_entidad') }}"  class="col-10 col-sm-10 col-md-10 col-lg-3 border border-success pt-3 m-3 text-center flex flex-col items-center bg-white" style="padding: 0 45px; text-decoration: none; color: inherit;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                </svg>                        
                                <p>Consultar</p>
                            </a>
                        </div>
                    </div> -->
                @endif
                
                @if(Auth::user()->profile === 'Administrador' )
                    <div class="col col-sm-5 col-md-5 col-lg-3 border border-info text-center mr-3 mt-3 bg-info">
                        <div class="row text-center p-3 justify-content-center">
                            <div class="col col-sm-12">
                                <h5 style="color: white;">Usuarios</h5>
                            </div>
                            <!-- <a href="{{ url('/crear_usuario') }}" class="col-10 col-sm-10 col-md-10 col-lg-4 border border-info pt-3 m-3 text-center flex flex-col items-center bg-white" style="padding: 0 45px; text-decoration: none; color: inherit;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>                     
                                <p>Crear</p>
                            </a> -->
                            <a href="{{ url('/consultar_usuario') }}" class="col-10 col-sm-10 col-md-10 col-lg-4 border border-info pt-3 m-3 text-center flex flex-col items-center bg-white" style="padding: 0 45px; text-decoration: none; color: inherit;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                </svg>                      
                                <p>Consultar</p>
                            </a>
                        </div>
                    </div>
                @endif
                    <div class="col col-sm-5 col-md-5 col-lg-3 border border-warning text-center mr-3 mt-3 bg-warning">
                        <div class="row text-center p-3 justify-content-center">
                            <div class="col col-sm-12">
                                <h5 style="color: white;">Parámetros</h5>
                            </div>
                            <a href="{{ url('/consultar_parametros') }}" class="col-10 col-sm-10 col-md-10 col-lg-3 border border-warning pt-3 m-3 text-center flex flex-col items-center bg-white" style="padding: 0 45px; text-decoration: none; color: inherit;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                </svg>                      
                                <p>Estados</p>
                            </a>
                            
                            <a href="{{ url('/dias_habiles') }}" class="col-10 col-sm-10 col-md-10 col-lg-3 border border-warning pt-3 m-3 text-center flex flex-col items-center bg-white" style="padding: 0 45px; text-decoration: none; color: inherit;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm0 2.25h.008v.008h-.008v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z" />
                                </svg>

                                <p>Días no laborales</p>
                            </a>
                        </div>
                    </div>
            </div>
        @else
            <div class="row text-center">
                <h2>Sin perfil asignado, por favor comuniquese con el administrador</h2>
            </div>
        @endif
    </div>
</x-app-layout>
