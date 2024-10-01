<nav x-data="{ open: false }" style="background-color: #069169;">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center text-white">
                    <a href="{{ route('dashboard') }}" style="text-decoration: none; color: inherit;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                        </svg>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" class="text-white">
                        {{ __('Inicio') }}
                    </x-nav-link>
                </div>

                @if(Auth::user()->profile !== NULL)

                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex dropdown">
                    <x-nav-link  :active="request()->routeIs('crear_diagnostico') || request()->routeIs('consultar_informe')" class="text-white dropdown-toggle" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        {{ __('Visitas de inspección') }}
                    </x-nav-link>

                    <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                        @if(Auth::user()->profile === 'Administrador' || Auth::user()->profile === 'Intendente' || Auth::user()->profile === 'Coordinador')
                            <x-dropdown-link :href="route('crear_entidad')" :active="request()->routeIs('crear_entidad')">
                                {{ __('Crear entidades') }}
                            </x-dropdown-link>
                            <x-dropdown-link :href="route('consultar_entidad')" :active="request()->routeIs('consultar_entidad')">
                                {{ __('Consultar entidades') }}
                            </x-dropdown-link>
                        @endif
                        @if(Auth::user()->profile === 'Administrador' || Auth::user()->profile === 'Intendente')
                            <x-dropdown-link :href="route('crear_diagnostico')" :active="request()->routeIs('crear_diagnostico')">
                                {{ __('Crear diagnóstico') }}
                            </x-dropdown-link>
                        @endif
                        <x-dropdown-link :href="route('consultar_informe')" :active="request()->routeIs('consultar_informe')">
                            {{ __('Consultar') }}
                        </x-dropdown-link>
                        <x-dropdown-link :href="route('estadisticas')" :active="request()->routeIs('estadisticas')">
                            {{ __('Estadísticas') }}
                        </x-dropdown-link>
                    </div>
                </div>

                @if(Auth::user()->profile === 'Administrador' )
                    <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex dropdown">
                        <x-nav-link  :active="request()->routeIs('crear_usuario') || request()->routeIs('consultar_usuario')" class="text-white dropdown-toggle" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            {{ __('Usuarios') }}
                        </x-nav-link>

                        <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                            <x-dropdown-link :href="route('consultar_usuario')" :active="request()->routeIs('consultar_usuario')">
                                {{ __('Consultar') }}
                            </x-dropdown-link>
                        </div>
                    </div>
                @endif

                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex dropdown">
                    <x-nav-link  :active="request()->routeIs('consultar_parametros') || request()->routeIs('estadisticas') || request()->routeIs('dias_habiles')" class="text-white dropdown-toggle" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        {{ __('Parámetros') }}
                    </x-nav-link>

                    <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                        <x-dropdown-link :href="route('consultar_parametros')" :active="request()->routeIs('consultar_parametros')">
                            {{ __('Consultar') }}
                        </x-dropdown-link>
                        
                        <x-dropdown-link :href="route('dias_habiles')" :active="request()->routeIs('dias_habiles')">
                            {{ __('Días no laborales') }}
                        </x-dropdown-link>
                    </div>
                </div>

                @endif

            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <!-- <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Perfil') }}
                        </x-dropdown-link> -->

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Cerrar sesión') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1 text-success">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" :class="request()->routeIs('dashboard') ? 'text-success' : 'text-white'">
                {{ __('Inicio') }}
            </x-responsive-nav-link>
        </div>

        @if(Auth::user()->profile !== NULL)

            @if(Auth::user()->profile === 'Administrador' || Auth::user()->profile === 'Intendente' || Auth::user()->profile === 'Coordinador' || Auth::user()->profile === 'Contratista')
                <div class="pt-2 pb-3 space-y-1">
                    <x-responsive-nav-link  :active="request()->routeIs('crear_diagnostico') || request()->routeIs('consultar_informe')" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                    :class="(request()->routeIs('crear_diagnostico') || request()->routeIs('consultar_informe')) ? 'text-success dropdown-toggle' : 'text-white dropdown-toggle'">
                                {{ __('Visitas de inspección') }}
                    </x-responsive-nav-link>

                    <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                        <x-dropdown-link :href="route('crear_entidad')" :active="request()->routeIs('crear_entidad')">
                            {{ __('Crear entidades') }}
                        </x-dropdown-link>
                        <x-dropdown-link :href="route('consultar_entidad')" :active="request()->routeIs('consultar_entidad')">
                            {{ __('Consultar entidades') }}
                        </x-dropdown-link>
                        @if(Auth::user()->profile === 'Administrador' || Auth::user()->profile === 'Intendente')
                            <x-dropdown-link :href="route('crear_diagnostico')" :active="request()->routeIs('crear_diagnostico')">
                                {{ __('Crear diagnóstico') }}
                            </x-dropdown-link>
                        @endif
                        <x-dropdown-link :href="route('consultar_informe')" :active="request()->routeIs('consultar_informe')">
                            {{ __('Consultar') }}
                        </x-dropdown-link>
                        <x-dropdown-link :href="route('estadisticas')" :active="request()->routeIs('estadisticas')">
                            {{ __('Estadísticas') }}
                        </x-dropdown-link>
                    </div>
                </div>
            @endif

            @if(Auth::user()->profile === 'Administrador' )
                <div class="pt-2 pb-3 space-y-1">
                    <x-responsive-nav-link  :active="request()->routeIs('crear_usuario') || request()->routeIs('consultar_usuario')" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                    :class="(request()->routeIs('crear_usuario') || request()->routeIs('consultar_usuario')) ? 'text-success dropdown-toggle' : 'text-white dropdown-toggle'">
                        {{ __('Usuarios') }}
                    </x-responsive-nav-link>

                    <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                        <x-dropdown-link :href="route('consultar_usuario')" :active="request()->routeIs('consultar_usuario')">
                            {{ __('Consultar') }}
                        </x-dropdown-link>
                    </div>
                </div>
            @endif

            <div class="pt-2 pb-3 space-y-1">
                <x-responsive-nav-link  :active="request()->routeIs('consultar_parametros') || request()->routeIs('estadisticas') || request()->routeIs('dias_habiles')" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                :class="(request()->routeIs('consultar_parametros') || request()->routeIs('estadisticas') || request()->routeIs('dias_habiles')) ? 'text-success dropdown-toggle' : 'text-white dropdown-toggle'">
                    {{ __('Parámetros') }}
                </x-responsive-nav-link>

                <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                    <x-dropdown-link :href="route('consultar_parametros')" :active="request()->routeIs('consultar_parametros')">
                        {{ __('Consultar') }}
                    </x-dropdown-link>
                    <x-dropdown-link :href="route('dias_habiles')" :active="request()->routeIs('dias_habiles')">
                        {{ __('Días no laborales') }}
                    </x-dropdown-link>
                </div>
            </div>

        @endif

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-white">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-white">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <!-- <x-responsive-nav-link :href="route('profile.edit')" class="text-white">
                    {{ __('Perfil') }}
                </x-responsive-nav-link> -->

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();" class="text-white">
                        {{ __('Cerrar sesión') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var dropdowns = document.querySelectorAll('.dropdown-toggle');
        dropdowns.forEach(function(dropdown) {
            dropdown.addEventListener('click', function() {
                this.nextElementSibling.classList.toggle('show');
            });
        });
    });
</script>