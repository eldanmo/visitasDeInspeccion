<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DiagnosticoController;
use App\Http\Controllers\EntidadController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\ParametrosController;
use App\Http\Controllers\EstadisticaController;
use App\Http\Controllers\DiaNoLaboralController;
use App\Http\Controllers\GoogleController;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
//use DragonCode\Contracts\Cashier\Auth\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return view('auth/login');
});

Route::get('/google-auth/redirect', [GoogleController::class, 'redirectToGoogle']);
Route::get('/google-auth/callback', [GoogleController::class, 'handleGoogleCallback']);

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/actualizar_dias_visitas', [DiagnosticoController::class, 'actualizar_dias_visitas'])->name('actualizar_dias_visitas');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');


    Route::get('/crear_diagnostico', [DiagnosticoController::class, 'crear'])->name('crear_diagnostico');
    Route::post('/crear_diagnostico_entidad', [DiagnosticoController::class, 'crear_diagnostico'])->name('crear_diagnostico_entidad');
    Route::get('/buscar_informes', [DiagnosticoController::class, 'buscar_informes'])->name('buscar_informes');

    Route::get('/consultar_informe', [DiagnosticoController::class, 'consultar_informes'])->name('consultar_informe');
    Route::get('/informe/{id}', [DiagnosticoController::class, 'vista_informe'])->name('visita');

    Route::get('/consultar_parametros', [ParametrosController::class, 'consultar'])->name('consultar_parametros');
    Route::put('/actualizar_parametro/{id}', [ParametrosController::class, 'actualizar'])->name('actualizar_parametro');
    
    Route::get('/crear_entidad', [EntidadController::class, 'crear'])->name('crear_entidad');
    Route::post('/guardar_entidad', [EntidadController::class, 'guardar'])->name('guardar_entidad');
    Route::get('/consultar_entidad', [EntidadController::class, 'consultar'])->name('consultar_entidad');
    Route::get('/consultar_entidades', [EntidadController::class, 'consultarEntidades'])->name('consultar_entidades');
    Route::post('/consultar_entidades_diagnostico', [EntidadController::class, 'consultarEntidadesDiagnostico'])->name('consultar_entidades_diagnostico');
    Route::put('/eliminar_entidad', [EntidadController::class, 'eliminar_entidad'])->name('eliminar_entidad');
    Route::get('/entidades/{id}/editar', [EntidadController::class, 'editar'])->name('entidades.editar');
    Route::put('/actualizar_entidad/{id}', [EntidadController::class, 'actualizar'])->name('actualizar_entidad');
    Route::get('/descargar_plantilla_cargue_masivo', [EntidadController::class, 'descargar_plantilla_cargue_masivo'])->name('descargar_plantilla_cargue_masivo');
    
    Route::get('/crear_usuario', [UsuarioController::class, 'crear'])->name('crear_usuario');
    Route::get('/consultar_usuario', [UsuarioController::class, 'consultar'])->name('consultar_usuario');
    Route::post('/guardar_usuario', [UsuarioController::class, 'guardar'])->name('guardar_usuario');
    Route::get('/consultar_usuarios', [UsuarioController::class, 'consultarUsuarios'])->name('consultar_usuarios');
    Route::delete('/eliminar_usuario/{id}', [UsuarioController::class, 'eliminar'])->name('eliminar_usuario');
    Route::put('/actualizar_usuario/{id}', [UsuarioController::class, 'actualizar'])->name('actualizar_usuario');

    Route::post('/guardar_observacion', [DiagnosticoController::class, 'guardar_observacion'])->name('guardar_observacion');

    Route::post('/finalizar_diagnostico', [DiagnosticoController::class, 'finalizar_diagnostico'])->name('finalizar_diagnostico');
    Route::post('/asignar_grupo_inspeccion', [DiagnosticoController::class, 'asignar_grupo_inspeccion'])->name('asignar_grupo_inspeccion');
    Route::post('/guardar_revision_diagnostico', [DiagnosticoController::class, 'guardar_revision_diagnostico'])->name('guardar_revision_diagnostico');
    Route::post('/finalizar_subasanar_diagnostico', [DiagnosticoController::class, 'finalizar_subasanar_diagnostico'])->name('finalizar_subasanar_diagnostico');
    Route::post('/guardar_plan_visita', [DiagnosticoController::class, 'guardar_plan_visita'])->name('guardar_plan_visita');
    Route::post('/revisar_plan_visita', [DiagnosticoController::class, 'revisar_plan_visita'])->name('revisar_plan_visita');
    Route::post('/confirmacion_informacion_previa_visita', [DiagnosticoController::class, 'confirmacion_informacion_previa_visita'])->name('confirmacion_informacion_previa_visita');
    Route::post('/finalizar_requerimiento_informacion', [DiagnosticoController::class, 'finalizar_requerimiento_informacion'])->name('finalizar_requerimiento_informacion');
    Route::post('/valoracion_informacion_recibida', [DiagnosticoController::class, 'valoracion_informacion_recibida'])->name('valoracion_informacion_recibida');
    Route::post('/confirmacion_visita', [DiagnosticoController::class, 'confirmacion_visita'])->name('confirmacion_visita');
    Route::post('/cartas_presentacion', [DiagnosticoController::class, 'cartas_presentacion'])->name('cartas_presentacion');
    Route::post('/abrir_visita_inspeccion', [DiagnosticoController::class, 'abrir_visita_inspeccion'])->name('abrir_visita_inspeccion');
    Route::post('/iniciar_visita_inspeccion', [DiagnosticoController::class, 'iniciar_visita_inspeccion'])->name('iniciar_visita_inspeccion');
    Route::post('/cerrar_visita_inspeccion', [DiagnosticoController::class, 'cerrar_visita_inspeccion'])->name('cerrar_visita_inspeccion');
    Route::post('/registrar_hallazgos', [DiagnosticoController::class, 'registrar_hallazgos'])->name('registrar_hallazgos');
    Route::post('/consolidar_hallazgos', [DiagnosticoController::class, 'consolidar_hallazgos'])->name('consolidar_hallazgos');
    Route::post('/proyecto_informe_final', [DiagnosticoController::class, 'proyecto_informe_final'])->name('proyecto_informe_final');
    Route::post('/revision_proyecto_informe_final', [DiagnosticoController::class, 'revision_proyecto_informe_final'])->name('revision_proyecto_informe_final');
    Route::post('/verificaciones_correcciones_informe_final', [DiagnosticoController::class, 'verificaciones_correcciones_informe_final'])->name('verificaciones_correcciones_informe_final');
    Route::post('/correcciones_informe_final', [DiagnosticoController::class, 'correcciones_informe_final'])->name('correcciones_informe_final');
    Route::post('/remitir_proyecto_informe_final_coordinaciones', [DiagnosticoController::class, 'remitir_proyecto_informe_final_coordinaciones'])->name('remitir_proyecto_informe_final_coordinaciones');
    Route::post('/revision_informe_final_coordinaciones', [DiagnosticoController::class, 'revision_informe_final_coordinaciones'])->name('revision_informe_final_coordinaciones');
    Route::post('/revision_informe_final_intendente', [DiagnosticoController::class, 'revision_informe_final_intendente'])->name('revision_informe_final_intendente');
    Route::post('/firmar_informe_final', [DiagnosticoController::class, 'firmar_informe_final'])->name('firmar_informe_final');
    Route::post('/confirmacion_intervencion_inmediata', [DiagnosticoController::class, 'confirmacion_intervencion_inmediata'])->name('confirmacion_intervencion_inmediata');
    Route::post('/enviar_traslado', [DiagnosticoController::class, 'enviar_traslado'])->name('enviar_traslado');
    Route::post('/informe_traslado_entidad', [DiagnosticoController::class, 'informe_traslado_entidad'])->name('informe_traslado_entidad');
    Route::post('/registrar_pronunciamiento_entidad', [DiagnosticoController::class, 'registrar_pronunciamiento_entidad'])->name('registrar_pronunciamiento_entidad');
    Route::post('/registrar_valoracion_respuesta', [DiagnosticoController::class, 'registrar_valoracion_respuesta'])->name('registrar_valoracion_respuesta');
    Route::post('/registrar_informe_hallazgos_finales', [DiagnosticoController::class, 'registrar_informe_hallazgos_finales'])->name('registrar_informe_hallazgos_finales');
    Route::post('/proponer_actuacion_administrativa', [DiagnosticoController::class, 'proponer_actuacion_administrativa'])->name('proponer_actuacion_administrativa');
    Route::post('/modificar_grupo_inspeccion', [DiagnosticoController::class, 'modificar_grupo_inspeccion'])->name('modificar_grupo_inspeccion');
    Route::post('/generar_tablero', [DiagnosticoController::class, 'generar_tablero'])->name('generar_tablero');
    Route::post('/generar_tablero_masivo', [DiagnosticoController::class, 'generar_tablero_masivo'])->name('generar_tablero_masivo');
    Route::post('/contenidos_finales_expedientes', [DiagnosticoController::class, 'contenidos_finales_expedientes'])->name('contenidos_finales_expedientes');
    Route::post('/registro_respuesta_informacion_adicional', [DiagnosticoController::class, 'registro_respuesta_informacion_adicional'])->name('registro_respuesta_informacion_adicional');
    Route::post('/suspender_visita', [DiagnosticoController::class, 'suspender_visita'])->name('suspender_visita');
    Route::post('/reanudar_visita', [DiagnosticoController::class, 'reanudar_visita'])->name('reanudar_visita');
    Route::post('/cambiar_entidad', [DiagnosticoController::class, 'cambiar_entidad'])->name('cambiar_entidad');

    Route::get('/estadisticas', [EstadisticaController::class, 'estadisticas'])->name('estadisticas');
    Route::post('/estadisticas_datos', [EstadisticaController::class, 'estadisticas_datos'])->name('estadisticas_datos');

    Route::get('/dias_habiles', [DiaNoLaboralController::class, 'dias_habiles'])->name('dias_habiles');
    Route::post('/crear_dia_no_laboral', [DiaNoLaboralController::class, 'crear_dia_no_laboral'])->name('crear_dia_no_laboral');
    Route::put('/actualizar_dia/{id}', [DiaNoLaboralController::class, 'actualizar'])->name('actualizar_dia');
    Route::delete('/eliminar_dia/{id}', [DiaNoLaboralController::class, 'eliminar'])->name('eliminar_dia');

    Route::post('importar_entidades', [EntidadController::class, 'importar_entidades'])->name('importar_entidades');

});

require __DIR__.'/auth.php';
