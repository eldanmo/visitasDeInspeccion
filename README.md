# Visitas de inspección

## Descripción
Este proyecto inicio principalmente para llevar un correcto registro de las visitas de inspección, pero se puede ambpliar a los demás grupos de la delegatura asocitiva

### Requisitos del Sistema
- PHP >= 8.1
- Composer
- MySQL o cualquier otra base de datos compatible
- npm
- Google cloud

#### Versiones

- 0 Version en pruebas

##### Arquitectura del sistema
- Frontend: Blade, JavaScript, jQuery, bootstrap, sweetalert2
- Backend: La aplicación permite que el frontend (JavaScript) envíe solicitudes HTTP al backend de Laravel utilizando la Fetch API. Estas solicitudes son procesadas por RESTful enviando las solicitudes a los controladores de Laravel que manejan la información de manera correspondiente.
- Base de datos: MySQL

###### Instalación  y configuración
- Clonar el repositorio
- Ejecutar las miggraciones (php artisan migrate)
- Ejecutar composer install, npm install
- Cargar en la carpeta raiz del proyecto el archivo creadenciales.json con las credenciales de las apis del google cloud console
- Cargar en la carpeta public/js/ el archivo creadenciales.json con las credenciales del google cloud console
- Archivo .env, modificar los datos de la DB_ con los de la base de datos a la que se vaya a conectar, modificar los datos MAIL_ con los datos del servidor de correo, las variables de GOOGLE_APPLICATION_CREDENTIALS redirecciona al archivo credenciales.json en la raiz del proyecto, GOOGLE_CLIENT_ID y GOOGLE_CLIENT_SECRET son las variables de google cloud, FOLDER_GOOGLE id para los documentos de las visitas de inspección, FOLDER_GOOGLE_TOMA_POSESION id de la carpeta para tomas de posesión y FOLDER_GOOGLE_OTROS_EXPEDIENTES id de la carpeta para documentos individuales

###### Rutas
- GET|HEAD  / ...........................................................................................
- POST      _ignition/execute-solution ignition.executeSolution › Spatie\LaravelIgnition › ExecuteSoluti…
- GET|HEAD  _ignition/health-check ignition.healthCheck › Spatie\LaravelIgnition › HealthCheckController
- POST      _ignition/update-config ignition.updateConfig › Spatie\LaravelIgnition › UpdateConfigControl…
- POST      abrir_visita_inspeccion abrir_visita_inspeccion › DiagnosticoController@abrir_visita_inspecc…
- PUT       actualizar_dia/{id} ...................... actualizar_dia › DiaNoLaboralController@actualizar
- GET|HEAD  actualizar_dias_visitas actualizar_dias_visitas › DiagnosticoController@actualizar_dias_visi…
- PUT       actualizar_entidad/{id} ................... actualizar_entidad › EntidadController@actualizar
- GET|HEAD  actualizar_historico_visitas actualizar_historico_visitas › DiagnosticoController@actualizar…
- PUT       actualizar_parametro/{id} ............ actualizar_parametro › ParametrosController@actualizar
- PUT       actualizar_usuario/{id} ................... actualizar_usuario › UsuarioController@actualizar
- GET|HEAD  api/user ....................................................................................
- POST      asignar_grupo_inspeccion asignar_grupo_inspeccion › DiagnosticoController@asignar_grupo_insp…
- GET|HEAD  asunto_especial/{id} ............. asunto_especial › AsuntoEspecialController@asunto_especial
- GET|HEAD  buscar_informes ..................... buscar_informes › DiagnosticoController@buscar_informes
- POST      cambiar_entidad ..................... cambiar_entidad › DiagnosticoController@cambiar_entidad
- POST      cartas_presentacion ......... cartas_presentacion › DiagnosticoController@cartas_presentacion
- POST      cerrar_visita_inspeccion cerrar_visita_inspeccion › DiagnosticoController@cerrar_visita_insp…
- POST      citacion_comite_interno_evaluador citacion_comite_interno_evaluador › DiagnosticoController@…
- GET|HEAD  confirm-password ................. password.confirm › Auth\ConfirmablePasswordController@show
- POST      confirm-password ................................... Auth\ConfirmablePasswordController@store
- POST      confirmacion_informacion_previa_visita confirmacion_informacion_previa_visita › DiagnosticoC…
- POST      confirmacion_intervencion_inmediata confirmacion_intervencion_inmediata › DiagnosticoControl…
- POST      confirmacion_visita ......... confirmacion_visita › DiagnosticoController@confirmacion_visita
- POST      confirmar_dias_adicionales_coordinacion confirmar_dias_adicionales_coordinacion › Diagnostic…
- POST      confirmar_dias_adicionales_delegatura confirmar_dias_adicionales_delegatura › DiagnosticoCon…
- POST      consolidar_hallazgos ...... consolidar_hallazgos › DiagnosticoController@consolidar_hallazgos
- POST      consultar_dias_no_laborales consultar_dias_no_laborales › DiagnosticoController@consultar_di…
- GET|HEAD  consultar_entidad ........................... consultar_entidad › EntidadController@consultar
- GET|HEAD  consultar_entidad_asunto_especial consultar_entidad_asunto_especial › AsuntoEspecialControll…
- GET|HEAD  consultar_entidad_maestra/{id} consultar_entidad_maestra › MaestroEntidadesController@consul…
- GET|HEAD  consultar_entidades .............. consultar_entidades › EntidadController@consultarEntidades
- POST      consultar_entidades_diagnostico consultar_entidades_diagnostico › EntidadController@consulta…
- GET|HEAD  consultar_informe .............. consultar_informe › DiagnosticoController@consultar_informes
- GET|HEAD  consultar_maestro_entidades consultar_maestro_entidades › MaestroEntidadesController@consult…
- GET|HEAD  consultar_parametros .................. consultar_parametros › ParametrosController@consultar
- GET|HEAD  consultar_usuario ........................... consultar_usuario › UsuarioController@consultar
- GET|HEAD  consultar_usuarios ................. consultar_usuarios › UsuarioController@consultarUsuarios
- POST      contenidos_finales_expedientes contenidos_finales_expedientes › DiagnosticoController@conten…
- POST      correcciones_informe_final correcciones_informe_final › DiagnosticoController@correcciones_i…
- POST      crear_dia_no_laboral ..... crear_dia_no_laboral › DiaNoLaboralController@crear_dia_no_laboral
- GET|HEAD  crear_diagnostico ........................... crear_diagnostico › DiagnosticoController@crear
- POST      crear_diagnostico_entidad crear_diagnostico_entidad › DiagnosticoController@crear_diagnostico
- GET|HEAD  crear_entidad ....................................... crear_entidad › EntidadController@crear
- POST      crear_entidad_maestra crear_entidad_maestra › MaestroEntidadesController@crear_entidad_maest…
- GET|HEAD  crear_usuario ....................................... crear_usuario › UsuarioController@crear
- GET|HEAD  dashboard ......................................................................... dashboard
- GET|HEAD  descargar_plantilla_cargue_masivo descargar_plantilla_cargue_masivo › EntidadController@desc…
- GET|HEAD  dias_habiles ............................. dias_habiles › DiaNoLaboralController@dias_habiles
- POST      eliminar_archivo .................. eliminar_archivo › DiagnosticoController@eliminar_archivo
- POST      eliminar_archivo_update eliminar_archivo_update › DiagnosticoController@eliminar_archivo_upd…
- DELETE    eliminar_dia/{id} ............................ eliminar_dia › DiaNoLaboralController@eliminar
- PUT       eliminar_entidad ...................... eliminar_entidad › EntidadController@eliminar_entidad
- DELETE    eliminar_usuario/{id} ......................... eliminar_usuario › UsuarioController@eliminar
- POST      email/verification-notification verification.send › Auth\EmailVerificationNotificationContro…
- GET|HEAD  email/verify verification.notice › Laravel\Fortify › EmailVerificationPromptController@__inv…
- GET|HEAD  email/verify/{id}/{hash} verification.verify › Laravel\Fortify › VerifyEmailController@__inv…
- GET|HEAD  entidades/{id}/editar ........................... entidades.editar › EntidadController@editar
- POST      enviar_traslado ..................... enviar_traslado › DiagnosticoController@enviar_traslado
- GET|HEAD  estadisticas .............................. estadisticas › EstadisticaController@estadisticas
- POST      estadisticas_datos ............ estadisticas_datos › EstadisticaController@estadisticas_datos
- POST      finalizar_diagnostico ... finalizar_diagnostico › DiagnosticoController@finalizar_diagnostico
- POST      finalizar_requerimiento_informacion finalizar_requerimiento_informacion › DiagnosticoControl…
- POST      finalizar_subasanar_diagnostico finalizar_subasanar_diagnostico › DiagnosticoController@fina…
- POST      firmar_informe_final ...... firmar_informe_final › DiagnosticoController@firmar_informe_final
- GET|HEAD  forgot-password .................. password.request › Auth\PasswordResetLinkController@create
- POST      forgot-password ..................... password.email › Auth\PasswordResetLinkController@store
- POST      generar_tablero ..................... generar_tablero › DiagnosticoController@generar_tablero
- POST      generar_tablero_masivo generar_tablero_masivo › DiagnosticoController@generar_tablero_masivo
- GET|HEAD  google-auth/callback .................................. GoogleController@handleGoogleCallback
- GET|HEAD  google-auth/redirect ...................................... GoogleController@redirectToGoogle
- POST      guardar_documento_adicional_asunto_especial guardar_documento_adicional_asunto_especial › As…
- POST      guardar_documento_adicional_visita_inspeccion guardar_documento_adicional_visita_inspeccion …
- POST      guardar_entidad ................................. guardar_entidad › EntidadController@guardar
- POST      guardar_memorando_traslado_grupo_asuntos_especiales guardar_memorando_traslado_grupo_asuntos…
- POST      guardar_observacion ......... guardar_observacion › DiagnosticoController@guardar_observacion
- POST      guardar_observacion_asunto_especial guardar_observacion_asunto_especial › AsuntoEspecialCont…
- POST      guardar_plan_visita ......... guardar_plan_visita › DiagnosticoController@guardar_plan_visita
- POST      guardar_plan_visita_modificado guardar_plan_visita_modificado › DiagnosticoController@guarda…
- POST      guardar_revision_diagnostico guardar_revision_diagnostico › DiagnosticoController@guardar_re…
- POST      guardar_usuario ................................. guardar_usuario › UsuarioController@guardar
- POST      importar_entidades ................ importar_entidades › EntidadController@importar_entidades
- GET|HEAD  informe/{id} ................................... visita › DiagnosticoController@vista_informe
- POST      informe_traslado_entidad informe_traslado_entidad › DiagnosticoController@informe_traslado_e…
- POST      iniciar_visita_inspeccion iniciar_visita_inspeccion › DiagnosticoController@iniciar_visita_i…
- GET|HEAD  login .................................... login › Auth\AuthenticatedSessionController@create
- POST      login ............................................. Auth\AuthenticatedSessionController@store
- POST      logout ................................. logout › Auth\AuthenticatedSessionController@destroy
- POST      modificar_grupo_inspeccion modificar_grupo_inspeccion › DiagnosticoController@modificar_grup…
- PUT       password ................................... password.update › Auth\PasswordController@update
- GET|HEAD  pdf ............................................................ pdfshow › PdfController@show
- GET|HEAD  pdf-viewer ..................................................................................
- POST      pdfDownload ................................. pdfDownload › DiagnosticoController@pdfDownload
- GET|HEAD  profile ............................................... profile.edit › ProfileController@edit
- PATCH     profile ........................................... profile.update › ProfileController@update
- DELETE    profile ......................................... profile.destroy › ProfileController@destroy
- POST      proponer_actuacion_administrativa proponer_actuacion_administrativa › DiagnosticoController@…
- POST      proyecto_informe_final proyecto_informe_final › DiagnosticoController@proyecto_informe_final
- POST      reanudar_visita ..................... reanudar_visita › DiagnosticoController@reanudar_visita
- GET|HEAD  register .................................... register › Auth\RegisteredUserController@create
- POST      register ................................................ Auth\RegisteredUserController@store
- POST      registrar_hallazgos ......... registrar_hallazgos › DiagnosticoController@registrar_hallazgos
- POST      registrar_informe_hallazgos_finales registrar_informe_hallazgos_finales › DiagnosticoControl…
- POST      registrar_pronunciamiento_entidad registrar_pronunciamiento_entidad › DiagnosticoController@…
- POST      registrar_valoracion_respuesta registrar_valoracion_respuesta › DiagnosticoController@regist…
- POST      registro_respuesta_informacion_adicional registro_respuesta_informacion_adicional › Diagnost…
- POST      remitir_proyecto_informe_final_coordinaciones remitir_proyecto_informe_final_coordinaciones …
- POST      reset-password ............................ password.store › Auth\NewPasswordController@store
- GET|HEAD  reset-password/{token} ................... password.reset › Auth\NewPasswordController@create
- POST      revisar_plan_visita ......... revisar_plan_visita › DiagnosticoController@revisar_plan_visita
- POST      revision_informe_final_coordinaciones revision_informe_final_coordinaciones › DiagnosticoCon…
- POST      revision_informe_final_intendente revision_informe_final_intendente › DiagnosticoController@…
- POST      revision_proyecto_informe_final revision_proyecto_informe_final › DiagnosticoController@revi…
- GET|HEAD  sanctum/csrf-cookie ....... sanctum.csrf-cookie › Laravel\Sanctum › CsrfCookieController@show
- POST      solicitar_dias_adicionales solicitar_dias_adicionales › DiagnosticoController@solicitar_dias…
- POST      suspender_visita .................. suspender_visita › DiagnosticoController@suspender_visita
- GET|HEAD  two-factor-challenge two-factor.login › Laravel\Fortify › TwoFactorAuthenticatedSessionContr…
- POST      two-factor-challenge ........ Laravel\Fortify › TwoFactorAuthenticatedSessionController@store
- DELETE    user ............... current-user.destroy › Laravel\Jetstream › CurrentUserController@destroy
- GET|HEAD  user/confirm-password .................. Laravel\Fortify › ConfirmablePasswordController@show
- POST      user/confirm-password password.confirm › Laravel\Fortify › ConfirmablePasswordController@sto…
- GET|HEAD  user/confirmed-password-status password.confirmation › Laravel\Fortify › ConfirmedPasswordSt…
- POST      user/confirmed-two-factor-authentication two-factor.confirm › Laravel\Fortify › ConfirmedTwo…
- DELETE    user/other-browser-sessions other-browser-sessions.destroy › Laravel\Jetstream › OtherBrowse…
- PUT       user/password ............ user-password.update › Laravel\Fortify › PasswordController@update
- GET|HEAD  user/profile .................. profile.show › Laravel\Jetstream › UserProfileController@show
- PUT       user/profile-information user-profile-information.update › Laravel\Fortify › ProfileInformat…
- DELETE    user/profile-photo current-user-photo.destroy › Laravel\Jetstream › ProfilePhotoController@d…
- POST      user/two-factor-authentication two-factor.enable › Laravel\Fortify › TwoFactorAuthentication…
- DELETE    user/two-factor-authentication two-factor.disable › Laravel\Fortify › TwoFactorAuthenticatio…
- GET|HEAD  user/two-factor-qr-code two-factor.qr-code › Laravel\Fortify › TwoFactorQrCodeController@show
- GET|HEAD  user/two-factor-recovery-codes two-factor.recovery-codes › Laravel\Fortify › RecoveryCodeCon…
- POST      user/two-factor-recovery-codes ............... Laravel\Fortify › RecoveryCodeController@store
- GET|HEAD  user/two-factor-secret-key two-factor.secret-key › Laravel\Fortify › TwoFactorSecretKeyContr…
- POST      valoracion_informacion_recibida valoracion_informacion_recibida › DiagnosticoController@valo…
- POST      verificaciones_correcciones_informe_final verificaciones_correcciones_informe_final › Diagno…
- GET|HEAD  verify-email ................... verification.notice › Auth\EmailVerificationPromptController
- GET|HEAD  verify-email/{id}/{hash} ................... verification.verify › Auth\VerifyEmailController

###### Controladores
- AsuntoEspecialController: controlador para el modulo 
- Controller: controlador general
- DiagnosticoController: controlador para las visitas de inspección
- DiaNoLaboralController: controlador para los días no laborales
- EntidadController: controlador para las entidades de visita de inspección
- EstadisticaController: controlador para las estadísticas del grupo de inspección
- GoogleController: controlador de incio de sesión con google
- GoogleDriveController: controlador de las api de google
- InformeController: controlador para los informes
- MaestroEntidadesController: controlador para el modulo maestro de entidades
- ParametrosController: controlador para los parámetros (estaps y días)
- PdfController: controlador para consultar pdf
- ProfileController: controlador para visualizar el perfil de los usuarios
- UsuarioController: controlador para la gestión de usuarios

###### Autenticación y autorización
- En el GoogleController estan las url de autorización para el acceso a los datos del usuario, google drive y hojas de calculo de google sheets
###### Notificaciones
- Las notificaciones se envían con la plantilla ubicada en la ruta resources\views\emails\creacion_diagnostico.blade.php con los datos de las variables de entorno de tipo MAIL_
###### Tareas Programadas
- Se ejecutan dos tareas programadas que se encuentran en la carpeta app\Console\Kernel.php en donde se ejcuta la función actualizar_dias_visitas todas las mañanas a las 8 am de lunes a viernes ademas de enviar notificaciones a los usuarios actuales y la función actualizar_historico_visitas que actualiza el estado la etapa de una visita de inspección

###### Integraciones Externas
- Se tiene integración con google cloud con el fin de inicio de sesión, envío y eliminación de documentos en google drive y actualización de datos en hojas de cálculo de sheets

###### Seguridad
- Todos los formularios se envian con el token CSRF