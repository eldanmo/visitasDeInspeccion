function guardarUsuario() {
    $('.btn-success').prop('disabled', true);

    var url = `/guardar_usuario`;
    let name = $('#name').val();
    let email = $('#email').val();
    let profile = $('#profile').val();
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    if (name === '' || email === '' || profile === '') {
        Swal.fire({
            icon: "warning",
            title: "Atención",
            text: "Todos los campos son obligatorios",
          });

          $('.btn-success').prop('disabled', false);
          return;
    }
            
    const formData = new FormData();
    formData.append('name', name);
    formData.append('email', email);
    formData.append('profile', profile);

    fetch(url,{
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': token 
        },
        body: formData
    }).then(response => {
        if (!response.ok) {
          return response.json().then(data => {
            throw new Error(data.error || 'Error en la solicitud');
          });
        }
        return response.json();
      })
      .then(data => {
        const message = data.message;
        Swal.fire('Éxito!', message, 'success').then(()=>{
            location.reload();
        })
      })
      .catch(error => {
        Swal.fire('Error', error.message, 'error');
      }).finally(() => {
        $('.btn-success').prop('disabled', false);
    });
}

function eliminarUsuario(id_usuario = '', nombre_usuario = ''){

    Swal.fire({
        title: '¿Estás seguro?',
        text: `¿Quieres eliminar el usuario ${nombre_usuario}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí',
        cancelButtonText: 'No'
    }).then((result) => {

        if (result.isConfirmed) {

            var url = `/eliminar_usuario/${id_usuario}`;
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            
            const formData = new FormData();
            formData.append('id_usuario', id_usuario);

            fetch(url,{
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token 
                },
            }).then(response => {
                if (!response.ok) {
                    throw new Error('Error al eliminar usuario');
                }
                Swal.fire('Eliminado', 'El usuario ha sido eliminado correctamente', 'success').then(()=>{
                    location.reload();
                })
                
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'Hubo un problema al eliminar el usuario', 'error');
            });
        }
    });
}

function abrirModalEditarUsuario(id_usuario = '', nombre_usuario ='', correo_usuario = '', rol_usuario = '') {
    $('#exampleModal').modal('show');

    $('#id').val(id_usuario);
    $('#name').val(nombre_usuario);
    $('#email').val(correo_usuario);
    $('#profile').val(rol_usuario);
}

function editarUsuario() {
    let id = $('#id').val();
    let name = $('#name').val();
    let email = $('#email').val();
    let profile = $('#profile').val();

    if (id === '' || name === '' || email === '' || profile === '') {
        Swal.fire({
            title: 'Error',
            text: `Todos lo campos son obligatorios`,
            icon: 'error',
            showCancelButton: true,
            timer: 3000
        });
        return;
    }

    Swal.fire({
        title: '¿Estás seguro?',
        text: `Se actualizara el usuario ${name}`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí',
        cancelButtonText: 'No'
    }).then((result) => {

        if (result.isConfirmed) {

            var url = `/actualizar_usuario/${id}`;
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            
            const data = {
                id: id,
                name: name,
                email: email,
                profile: profile
            };

            fetch(url, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token 
                },
                body: JSON.stringify(data)
            }).then(response => {
                if (!response.ok) {
                  return response.json().then(data => {
                    throw new Error(data.error || 'Error en la solicitud');
                  });
                }
                return response.json();
              })
              .then(data => {
                const message = data.message;
                Swal.fire('Éxito!', message, 'success').then(()=>{
                    location.reload();
                })
              })
              .catch(error => {
                Swal.fire('Error', error.message, 'error');
              })
        }
    });
}

function guardarEntidad() {
    var bandera = false;

    var labels = [];
    var url = `/guardar_entidad`;
    var method = `POST`;
    let id = $('#id').val();
    
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var formData = new FormData();

    var heads = {'X-CSRF-TOKEN': token}

    $('.required').each(function() {
        if ($(this).val() === '') {
            if ($(this).attr('id') !== 'sigla' &&
                $(this).attr('id') !== 'categoria' &&
                $(this).attr('id') !== 'id' &&
                $(this).attr('id') !== 'incluye_sarlaft') {

                    var label = $('label[for="' + $(this).attr('id') + '"]').text().replace(' (*)','');
            
                    labels.push(label);
                    bandera = true;
            }else{
                formData.append($(this).attr('id'), $(this).val());
            }
        } else {
            formData.append($(this).attr('id'), $(this).val());
        }
    });

    if (bandera) {
        var html = `<label>Los siguientes datos son obligatorios:</label><br><ol type=”A”>`;
        for (var i = 0; i < labels.length; i++) {
            html += '<li>' + labels[i] + '</li>';
        }
        html += '</ol>';

        Swal.fire({
          icon: "warning",
          title: "Atención",
          html: html,
        });

        return;
    }

    if (id !== '') {
        url = `/actualizar_entidad/${id}`;
        method = `PUT`;

        const data = {};

        $('.required').each(function() {
            let clave = $(this).attr('id');
            let valor = $(this).val();  

            data[clave] = valor;
        });

        heads = {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token 
        };

        formData = JSON.stringify(data);
    }

    $('.btn-success').prop('disabled', true);

    fetch(url,{
        method: method,
        headers: heads,
        body: formData
    }).then(response => {
        if (!response.ok) {
          return response.json().then(data => {
            throw new Error(data.error || 'Error en la solicitud');
          });
        }
        return response.json();
    })
    .then(data => {
        const message = data.message;
        Swal.fire('Éxito!', message, 'success').then(()=>{
            location.reload();
        })
    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    }).finally(() => {
        $('.btn-success').prop('disabled', false);
    });
}

function eliminarEntidad(id_entidad = '', nombre_entidad = ''){

    Swal.fire({
        title: `¿Quieres eliminar la entidad ${nombre_entidad}?`,
        text: `Ingresa el motivo de la eliminación`,
        icon: 'warning',
        input: 'textarea',
        inputValidator: (value) => {
            if (!value) {
                return 'Debes ingresar un motivo de eliminación';
            }
        },
        showCancelButton: true,
        confirmButtonText: 'Sí',
        cancelButtonText: 'No'
    }).then((result) => {

        if (result.isConfirmed) {

            var url = `/eliminar_entidad`;
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const motivo = result.value;

            const data = {
                id_entidad: id_entidad,
                motivo: motivo,
            };

            fetch(url,{
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token 
                },
                body: JSON.stringify(data)
            }).then(response => {
                if (!response.ok) {
                    throw new Error('Error al eliminar entidad');
                }
                Swal.fire('Eliminada', `La entidad ${nombre_entidad} ha sido eliminada correctamente`, 'success').then(()=>{
                    location.reload();
                })
                
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'Hubo un problema al eliminar el usuario', 'error');
            });
        }
    });
}

function abrirModalEditarparametro(id = '', estado ='', dias = '') {
    $('#modalParametros').modal('show');

    $('#id').val(id);
    $('#dias').val(dias);
    $('#estado').val(estado);
}

function editarParametro() {
    let id = $('#id').val();
    let dias = $('#dias').val();
    //let estado = $('#estado').val();

    if (id === '' || dias === '') {
        Swal.fire({
            title: 'Error',
            text: `Todos lo campos son obligatorios`,
            icon: 'error',
            showCancelButton: true,
            timer: 3000
        });
        return;
    }

    Swal.fire({
        title: '¿Estás seguro?',
        text: `Se actualizaran los días del parámetro`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí',
        cancelButtonText: 'No'
    }).then((result) => {

        if (result.isConfirmed) {

            var url = `/actualizar_parametro/${id}`;
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            
            const data = {
                id: id,
                dias: dias,
            };

            fetch(url, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token 
                },
                body: JSON.stringify(data)
            }).then(response => {
                if (!response.ok) {
                  return response.json().then(data => {
                    throw new Error(data.error || 'Error en la solicitud');
                  });
                }
                return response.json();
              })
              .then(data => {
                const message = data.message;
                Swal.fire('Éxito!', message, 'success').then(()=>{
                    location.reload();
                })
              })
              .catch(error => {
                Swal.fire('Error', error.message, 'error');
              })
        }
    });
}

function abrirModalBuscarEntidad(tipo = '') {
    $('#buscarEntidad').modal('show');
    buscarEntidad(tipo);
}

function buscarEntidad(tipo = '') {
    let codigo = $('#codigo_modal').val();
    let nit =$('#nit_modal').val();
    let nombre =$('#nombre_modal').val(); 

    const method = `POST`;
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var formData = new FormData();

    formData.append('codigo', codigo);
    formData.append('nit', nit);
    formData.append('nombre', nombre);

    let url = `/consultar_entidades_diagnostico`;

    var heads = {'X-CSRF-TOKEN': token};

    $('#table_entidad').html('');

    fetch(url,{
        method: method,
        headers: heads,
        body: formData
    }).then(response => {
        if (!response.ok) {
          return response.json().then(data => {
            throw new Error(data.error || 'Error en la solicitud');
          });
        }
        return response.json();
    })
    .then(data => {
        const message = data.message;
        let html = ''

        if (message.length > 0 ) {
            $.each(message, function (index, entidad){
                console.log('Entidad: ',entidad);
                if (tipo == '1') {
                    html += `
                        <tr>
                            <td class="text-center">${index+1}</td>
                            <td>${entidad.codigo_entidad}</td>
                            <td>${entidad.nit}</td>
                            <td>${entidad.razon_social}</td>
                            <td>${entidad.tipo_organizacion}</td>
                            <td class="text-center">          
                                <button class="btn btn-success btn-sm" onclick='seleccionarEntidadCambiar(${entidad.id})'>
                                    <i class="fas fa-trash"></i> Seleccionar
                                </button>
                            </td>
                        </tr>
                    `
                }else{
                    html += `
                        <tr>
                            <td class="text-center">${index+1}</td>
                            <td>${entidad.codigo_entidad}</td>
                            <td>${entidad.nit}</td>
                            <td>${entidad.razon_social}</td>
                            <td>${entidad.tipo_organizacion}</td>
                            <td class="text-center">          
                                <button class="btn btn-success btn-sm" onclick='seleccionarEntidad(${JSON.stringify(entidad)})'>
                                    <i class="fas fa-trash"></i> Seleccionar
                                </button>
                            </td>
                        </tr>
                    `
                }
                
            })
        }else{
            html += `
                <tr>
                    <td class="text-center" colspan="6">No se encontraron resultados</td>
                </tr>
                `
        }

        $('#table_entidad').html(html);
    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    })
}

function seleccionarEntidad(entidad = {}) {
    $('#id').val(entidad.id);
    $('#codigo_entidad').val(entidad.codigo_entidad);
    $('#nit').val(entidad.nit);
    $('#sigla').val(entidad.sigla);
    $('#nivel_supervision').val(entidad.nivel_supervision);
    $('#tipo_organizacion').val(entidad.tipo_organizacion);
    $('#categoria').val(entidad.categoria);
    $('#grupo_niif').val(entidad.grupo_niif);
    $('#nivel_supervision').val(entidad.nivel_supervision);
    $('#naturaleza_organizacion').val(entidad.naturaleza_organizacion);
    $('#ciudad_municipio').val(entidad.ciudad_municipio);
    $('#departamento').val(entidad.departamento);
    $('#numero_asociados').val(entidad.numero_asociados);
    $('#total_activos').val(entidad.total_activos);
    $('#total_pasivos').val(entidad.total_pasivos);
    $('#total_patrimonio').val(entidad.total_patrimonio);
    $('#total_ingresos').val(entidad.total_ingresos);
    $('#razon_social').val(entidad.razon_social);
    $('#incluye_sarlaft').val(entidad.incluye_sarlaft);
    $('#naturaleza_organizacion').val(entidad.naturaleza_organizacion);
    $('#grupo_niif').val(entidad.grupo_niif);
    $('#direccion').val(entidad.direccion);
    $('#numero_empleados').val(entidad.numero_empleados);
    $('#representate_legal').val(entidad.representate_legal);
    $('#correo_representate_legal').val(entidad.correo_representate_legal);
    $('#telefono_representate_legal').val(entidad.telefono_representate_legal);
    $('#tipo_revisor_fiscal').val(entidad.tipo_revisor_fiscal);
    $('#razon_social_revision_fiscal').val(entidad.razon_social_revision_fiscal);
    $('#nombre_revisor_fiscal').val(entidad.nombre_revisor_fiscal);
    $('#direccion_revisor_fiscal').val(entidad.direccion_revisor_fiscal);
    $('#telefono_revisor_fiscal').val(entidad.telefono_revisor_fiscal);
    $('#correo_revisor_fiscal').val(entidad.correo_revisor_fiscal);

    let fecha = entidad.fecha_ultimo_reporte;

    let fechaObjeto = new Date(fecha);

    let ano = fechaObjeto.getFullYear();
    let mes = ("0" + (fechaObjeto.getMonth() + 1)).slice(-2);
    let dia = ("0" + fechaObjeto.getDate()).slice(-2);

    let fechaFormateada = `${ano}-${mes}-${dia}`;

    $('#fecha_ultimo_reporte').val(fechaFormateada);
    $('#buscarEntidad').modal('hide');

    let fechaUltimoReporte = new Date(entidad.fecha_ultimo_reporte);

    let ano_ultimo_reporte = fechaUltimoReporte.getFullYear();
    let mes_ultimo_reporte = ("0" + (fechaUltimoReporte.getMonth() + 1)).slice(-2);
    let dia_ultimo_reporte = ("0" + fechaUltimoReporte.getDate()).slice(-2);

    let fechaFormateadaUltumoReporte = `${ano_ultimo_reporte}-${mes_ultimo_reporte}-${dia_ultimo_reporte}`;

    $('#fecha_corte_visita').val(fechaFormateadaUltumoReporte);

    tipo_organizacion();
    tipo_revisor_fiscal();
}

function seleccionarEntidadCambiar(entidad) {
    $('#buscarEntidad').modal('hide');
    Swal.fire({
        title: `¿Se cambiara la entidad de la visita de inspección por favor confirme el motivo del cambio?`,
        text: `Ingresa el motivo del cambio de la entidad`,
        icon: 'warning',
        input: 'textarea',
        inputValidator: (value) => {
            if (!value) {
                return 'Debes ingresar un motivo de eliminación';
            }
        },
        showCancelButton: true,
        confirmButtonText: 'Sí',
        cancelButtonText: 'No'
    }).then((result) => {

        if (result.isConfirmed) {

            let id = $('#id').val();
            let etapa = $('#etapa').val();
            let estado = $('#estado').val();
            let estado_etapa = $('#estado_etapa').val();
            let numero_informe = $('#numero_informe').val();
            let razon_social = $('#razon_social').val();
            let nit = $('#nit').val();
            var url = `/cambiar_entidad`;
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const observacion = result.value;

            const data = {
                motivo: observacion,
                entidad: entidad,
                informe: id,
                etapa: etapa,
                estado: estado,
                estado_etapa: estado_etapa,
                numero_informe: numero_informe,
                razon_social: razon_social,
                nit: nit,
            };

            fetch(url,{
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token 
                },
                body: JSON.stringify(data)
            }).then(response => {
                if (!response.ok) {
                    throw new Error('Error al reemplazar la entidad');
                }
                Swal.fire('Eliminada', `La entidad fue reemplazada correctamente`, 'success').then(()=>{
                    location.reload();
                })
                
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'Hubo un problema al reemplazar la entidad', 'error');
            });
        }
    });
}

function parametrosCreacionDiagnostico() {
    let fechaActual = new Date().toISOString().split('T')[0];
    $('#fecha_inicio_diagnostico').val(fechaActual);

    const dias_diagnostico = $('#dias_diagnostico').val();

    let fecha_fin = sumarDiasHabiles(fechaActual, parseInt(dias_diagnostico));
    $('#fecha_fin_diagnostico').val(fecha_fin);
}

const diasFestivosColombia = [
    '2024-01-01', 
    '2024-01-06', 
    '2024-03-25', 
    '2024-03-28', 
    '2024-03-29', 
    '2024-05-01', 
    '2024-05-13', 
    '2024-06-03', 
    '2024-06-10', 
    '2024-07-01', 
    '2024-07-20', 
    '2024-08-07', 
    '2024-08-19', 
    '2024-10-14', 
    '2024-11-04', 
    '2024-11-11', 
    '2024-12-08', 
    '2024-12-25', 
    
];
  
function esDiaHabilColombia(fecha) {
    const dia = fecha.getDay();
    return dia !== 0 && dia !== 6 && !esDiaFestivoColombia(fecha);
}
  
function esDiaFestivoColombia(fecha) {
    const fechaString = fecha.toISOString().split('T')[0];
    return diasFestivosColombia.includes(fechaString);
}
  
function sumarDiasHabiles(fechaString, dias) {
    const fecha = new Date(fechaString);
    let contador = 0;
    while (contador < (dias+1)) {
      fecha.setDate(fecha.getDate() + 1);
      if (esDiaHabilColombia(fecha)) {
        contador++;
      }
    }
    const year = fecha.getFullYear();
    const month = ('0' + (fecha.getMonth() + 1)).slice(-2);
    const day = ('0' + fecha.getDate()).slice(-2);
    return `${year}-${month}-${day}`;
}

function diasHabilesDiagnostico() {
    let fecha_inicio_diagnostico = $('#fecha_inicio_diagnostico').val();

    const dias_diagnostico = $('#dias_diagnostico').val();

    let fecha_fin = sumarDiasHabiles(fecha_inicio_diagnostico, parseInt(dias_diagnostico));
    $('#fecha_fin_diagnostico').val(fecha_fin);
}

function guardarDiagnostico() {
    var bandera = false;

    var labels = [];
    let id = $('#id').val();
    
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var formData = new FormData();

    var heads = {'X-CSRF-TOKEN': token}

    $('.required').each(function() {
        if ($(this).val() === '') {
            if ($(this).attr('id') !== 'sigla' &&
                $(this).attr('id') !== 'categoria' &&
                $(this).attr('id') !== 'id' &&
                $(this).attr('id') !== 'incluye_sarlaft') {

                    var label = $('label[for="' + $(this).attr('id') + '"]').text().replace(' (*)','');
            
                    labels.push(label);
                    bandera = true;
            }else{
                formData.append($(this).attr('id'), $(this).val());
            }
        } else {
            formData.append($(this).attr('id'), $(this).val());
        }
    });

    if (bandera) {
        var html = `<label>Los siguientes datos son obligatorios:</label><br><ol type=”A”>`;
        for (var i = 0; i < labels.length; i++) {
            html += '<li>' + labels[i] + '</li>';
        }
        html += '</ol>';

        Swal.fire({
          icon: "warning",
          title: "Atención",
          html: html,
        });

        return;
    }

    if (id !== '') {
        url = `/actualizar_entidad/${id}`;
        method = `PUT`;

        const data = {};

        $('.required').each(function() {
            let clave = $(this).attr('id');
            let valor = $(this).val();  

            data[clave] = valor;
        });

        heads = {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token 
        };

        formData = JSON.stringify(data);
    }

    $('.btn-success').prop('disabled', true);

    fetch(url,{
        method: method,
        headers: heads,
        body: formData
    }).then(response => {
        if (!response.ok) {
          return response.json().then(data => {
            throw new Error(data.error || 'Error en la solicitud');
          });
        }
        return response.json();
    })
    .then(data => {
        const message = data.message;
        Swal.fire('Éxito!', message, 'success').then(()=>{
            setTimeout(() => {
                
                let fecha_inicio_diagnostico = $('#fecha_inicio_diagnostico').val();
                let fecha_fin_diagnostico = $('#fecha_fin_diagnostico').val();
                let razon_social = $('#razon_social').val();
                let nit = $('#nit').val();

                url = `/crear_diagnostico_entidad`;

                const formData = new FormData();
                formData.append('fecha_inicio_diagnostico', fecha_inicio_diagnostico);
                formData.append('fecha_fin_diagnostico', fecha_fin_diagnostico);
                formData.append('id_entidad', id);
                formData.append('razon_social', razon_social);
                formData.append('nit', nit);

                fetch(url,{
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token 
                    },
                    body: formData
                }).then(response => {
                    if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.error || 'Error en la solicitud');
                    });
                    }
                    return response.json();
                })
                .then(data => {
                    const message = data.message;
                    Swal.fire('Éxito!', message, 'success').then(()=>{
                        //location.reload();
                    })
                })
                .catch(error => {
                    Swal.fire('Error', error.message, 'error');
                }).finally(() => {
                    $('.btn-success').prop('disabled', false);
                });


            }, 1000);
        })
    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    }).finally(() => {
        $('.btn-success').prop('disabled', false);
    });
}

function guardar_observacion(accion) {
    var bandera = false;

    let id = $('#id').val();
    let observaciones = '';
    if (accion === 'observacion') {
        observaciones = $('#observaciones').val();
    }else{
        observaciones = $('#observaciones_cancelacion').val();
    }
    let etapa = $('#etapa').val();
    let estado = $('#estado').val();
    let estado_etapa = $('#estado_etapa').val();
    let numero_informe = $('#numero_informe').val();
    let razon_social = $('#razon_social').val();
    let nit = $('#nit').val();
    let url = `/guardar_observacion`;
    
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var formData = new FormData();
    formData.append('id', id);
    formData.append('observaciones', observaciones);
    formData.append('etapa', etapa);
    formData.append('estado', estado);
    formData.append('estado_etapa', estado_etapa);
    formData.append('numero_informe', numero_informe);
    formData.append('razon_social', razon_social);
    formData.append('nit', nit);
    formData.append('accion', accion);

    var heads = {'X-CSRF-TOKEN': token}

    if (observaciones === '') {
        bandera = true;
    }

    if (bandera) {
        var html = `<label>Los siguientes datos son obligatorios:</label><br><ol type=”A”>`;
        html += '<li>Observaciones</li>';
        html += '</ol>';

        Swal.fire({
          icon: "warning",
          title: "Atención",
          html: html,
        });

        return;
    }

    $('.enviarObservacion').prop('disabled', true);

    fetch(url,{
        method: 'POST',
        headers: heads,
        body: formData
    }).then(response => {
        if (!response.ok) {
          return response.json().then(data => {
            throw new Error(data.error || 'Error en la solicitud');
          });
        }
        return response.json();
    })
    .then(data => {
        const message = data.message;
        Swal.fire('Éxito!', message, 'success').then(()=>{
            location.reload();
        });
    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    }).finally(() => {
        $('.enviarObservacion').prop('disabled', false);
    });
}

function finalizarDiagnostico() {
    var bandera = false;

    let id = $('#id').val();
    let etapa = $('#etapa').val();
    let estado = $('#estado').val();
    let estado_etapa = $('#estado_etapa').val();
    let numero_informe = $('#numero_informe').val();
    let razon_social = $('#razon_social').val();
    let nit = $('#nit').val();
    let codigo = $('#codigo').val();
    let sigla = $('#sigla').val();
    let observacion = $('#observaciones_diagnostico').val();
    let url = `/finalizar_diagnostico`;

    let anexos_diagnostico = [];
    let labels = [];
    
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var formData = new FormData();
    formData.append('id', id);
    formData.append('etapa', etapa);
    formData.append('estado', estado);
    formData.append('estado_etapa', estado_etapa);
    formData.append('numero_informe', numero_informe);
    formData.append('razon_social', razon_social);
    formData.append('nit', nit);
    formData.append('codigo', codigo);
    formData.append('sigla', sigla);
    formData.append('observacion', observacion);

    var heads = {'X-CSRF-TOKEN': token}

    var fileInput = document.getElementById('ciclo_vida_diagnostico');
    var file = fileInput.files[0];
    if (file) {
        formData.append('ciclo_vida_diagnostico', file);
    } else {
        bandera = true;
        labels.push('Documento diagnóstico');
    }

    $('.tr_documentos_adicionales_diagnostico').each(function () {

        var row = {};
        var banderaText = false;
        var banderaFile = false;

            $(this).find('input[type="text"]').each(function() {
                var key = $(this).attr('name');
                var valueText = $(this).val();
                if (valueText !=  '') {
                    row[key] = valueText; 
                    banderaText = true;
                }
            });

            $(this).find('input[type="file"]').each(function() {
                var key = $(this).attr('name');
                var valuefile = this.files[0];
                if (valuefile !=  undefined) {
                    row[key] = valuefile; 
                    banderaFile=true;
                }
            });

            if (banderaText && banderaFile) {
                anexos_diagnostico.push(row);
            }
    })
    
    if (anexos_diagnostico.length > 0 ) {
        anexos_diagnostico.forEach((item, index) => {
            for (var key in item) {
                if (item.hasOwnProperty(key)) {
                    if (Array.isArray(item[key])) {
                        item[key].forEach((file, fileIndex) => {
                            formData.append(`${key}[${index}][${fileIndex}]`, file);
                        });
                    } else {
                        formData.append(`${key}[${index}]`, item[key]);
                    }
                }
            }
        });
    }

    if (bandera) {
        var html = `<label>Los siguientes datos son obligatorios:</label><br><ol type=”A”>`;
        
        labels.forEach(dato =>{
            html += `<li>${dato}</li>`;
        });   
        html += '</ol>';    
        
        Swal.fire({
          icon: "warning",
          title: "Atención",
          html: html,
        });

        return;
    }

    $('.enviarObservacion').prop('disabled', true);

    fetch(url,{
        method: 'POST',
        headers: heads,
        body: formData
    }).then(response => {
        if (!response.ok) {
          return response.json().then(data => {
            throw new Error(data.error || 'Error en la solicitud');
          });
        }
        return response.json();
    })
    .then(data => {
        const message = data.message;
        Swal.fire('Éxito!', message, 'success').then(()=>{
            location.reload();
        });
    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    }).finally(() => {
        $('.enviarObservacion').prop('disabled', false);
    });
}

function asignarGrupoInspeccion() {
    var bandera = false;

    let id = $('#id').val();
    let etapa = $('#etapa').val();
    let estado = $('#estado').val();
    let estado_etapa = $('#estado_etapa').val();
    let numero_informe = $('#numero_informe').val();
    let razon_social = $('#razon_social').val();
    let observacion = $('#observaciones_asignacion_grupo_inspeccion').val();
    let nit = $('#nit').val();
    let url = `/asignar_grupo_inspeccion`;

    let grupo_inspeccion = [];

    $('.tr_grupo_inspeccion').each(function () {
        var row = {};
            $(this).find('select').each(function() {
                var key = $(this).attr('name');
                var value = $(this).val();
                row[key] = value;

                if (value === '') {
                    bandera = true;
                }
            });
            grupo_inspeccion.push(row);
    })
    
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var formData = new FormData();
    formData.append('id', id);
    formData.append('grupo_inspeccion', JSON.stringify(grupo_inspeccion) );
    formData.append('etapa', etapa);
    formData.append('estado', estado);
    formData.append('estado_etapa', estado_etapa);
    formData.append('numero_informe', numero_informe);
    formData.append('razon_social', razon_social);
    formData.append('nit', nit);
    formData.append('observacion', observacion);

    var heads = {'X-CSRF-TOKEN': token}

    if (bandera) {
        var html = `<label>Todos los datos deben ser diligenciados</label`;

        Swal.fire({
          icon: "warning",
          title: "Atención",
          html: html,
        });

        return;
    }

    $('.asignarGrupoInspeccion').prop('disabled', true);

    fetch(url,{
        method: 'POST',
        headers: heads,
        body: formData
    }).then(response => {
        if (!response.ok) {
          return response.json().then(data => {
            throw new Error(data.error || 'Error en la solicitud');
          });
        }
        return response.json();
    })
    .then(data => {
        const message = data.message;
        Swal.fire('Éxito!', message, 'success').then(()=>{
            location.reload();
        });
    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    }).finally(() => {
        $('.asignarGrupoInspeccion').prop('disabled', false);
    });
}

function anadirInspector() {

    var ultimoTr = $('#tabla_asignacion_grupo_inspeccion tbody tr:last').clone();

    var ultimoNumero = parseInt($('#tabla_asignacion_grupo_inspeccion tbody tr:last td:first p').text());
    ultimoTr.find('p').text(ultimoNumero + 1);

    $('#tabla_asignacion_grupo_inspeccion tbody').append(ultimoTr);

}

function eliminarInspector(button) {
        var fila = $(button).closest('tr');
        fila.remove();
}

function resultadoRevisionDiagnostico() {
    let resultado_revision = $('#resultado_revision').val();

    if (resultado_revision === 'No') {
        $('.div_enlace_documento_diagnostico').show();
    }else{
        $('.div_enlace_documento_diagnostico').hide();
    }
    
}

function guardarRevisionDiagnostico() {
    var bandera = false;

    var labels = [];
    let resultado_revision = $('#resultado_revision').val();
    let etapa = $('#etapa').val();
    let estado = $('#estado').val();
    let estado_etapa = $('#estado_etapa').val();
    let numero_informe = $('#numero_informe').val();
    let razon_social = $('#razon_social').val();
    let nit = $('#nit').val();

    var url = `/guardar_revision_diagnostico`;
    let id = $('#id').val();
    
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var formData = new FormData();

    formData.append('id', id);
    formData.append('etapa', etapa);
    formData.append('estado', estado);
    formData.append('estado_etapa', estado_etapa);
    formData.append('numero_informe', numero_informe);
    formData.append('razon_social', razon_social);
    formData.append('nit', nit);

    var heads = {'X-CSRF-TOKEN': token}

    if (resultado_revision === '') {
        var html = `<label>Debe seleccionar si el documento diagnóstico cumple con los criterios suficientes para elaborar el plan de la visita</label>`;

        Swal.fire({
          icon: "warning",
          title: "Atención",
          html: html,
        });

        return;
    }

    $('.required_revision_diagnostico').each(function() {
        if ($(this).val() === '') {
                if (resultado_revision === 'No') {
                    var label = $('label[for="' + $(this).attr('id') + '"]').text().replace(' (*)','');
            
                    labels.push(label);
                    bandera = true;
                }
        } else {
            formData.append($(this).attr('id'), $(this).val());
        }
    });

    if (bandera) {
        var html = `<label>Los siguientes datos son obligatorios:</label><br><ol type=”A”>`;
        for (var i = 0; i < labels.length; i++) {
            html += '<li>' + labels[i] + '</li>';
        }
        html += '</ol>';

        Swal.fire({
          icon: "warning",
          title: "Atención",
          html: html,
        });

        return;
    }

    $('.revisionDiagnostico').prop('disabled', true);

    fetch(url,{
        method: 'POST',
        headers: heads,
        body: formData
    }).then(response => {
        if (!response.ok) {
          return response.json().then(data => {
            throw new Error(data.error || 'Error en la solicitud');
          });
        }
        return response.json();
    })
    .then(data => {
        const message = data.message;
        Swal.fire('Éxito!', message, 'success').then(()=>{
            location.reload();
        });
    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    }).finally(() => {
        $('.revisionDiagnostico').prop('disabled', false);
    });

}

function finalizarSubsanarDiagnostico() {
    var bandera = false;

    let id = $('#id').val();
    let ciclo_vida_diagnostico = $('#enlace_grabacion').val();
    let etapa = $('#etapa').val();
    let estado = $('#estado').val();
    let estado_etapa = $('#estado_etapa').val();
    let numero_informe = $('#numero_informe').val();
    let razon_social = $('#razon_social').val();
    let nit = $('#nit').val();
    let url = `/finalizar_subasanar_diagnostico`;
    var producto = $('#producto_generado_subsanacion').val();
    var observaciones = $('#observaciones_subsanacion_documento_diagnostico').val();

    let anexos_subsanacion_diagnostico = [];
    let labels = [];
    
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var formData = new FormData();
    formData.append('id', id);
    formData.append('ciclo_vida_diagnostico', ciclo_vida_diagnostico);
    formData.append('etapa', etapa);
    formData.append('estado', estado);
    formData.append('estado_etapa', estado_etapa);
    formData.append('numero_informe', numero_informe);
    formData.append('razon_social', razon_social);
    formData.append('nit', nit);
    formData.append('observaciones', observaciones);
    formData.append('producto', producto);

    var heads = {'X-CSRF-TOKEN': token}

    if (producto === '') {
        bandera = true;
        labels.push('Producto generado de la reunión');
    }

    if ((producto === 'GRABACIÓN' || producto === 'AMBOS') && ciclo_vida_diagnostico === '') {
        bandera = true;
        labels.push('Enlace de la grabación');
    }

    $('.tr_documentos_adicionales_subsanacion_diagnostico').each(function () {

        var row = {};
        var banderaText = false;
        var banderaFile = false;

            $(this).find('input[type="text"]').each(function() {
                var key = $(this).attr('name');
                var valueText = $(this).val();
                if (valueText !=  '') {
                    row[key] = valueText; 
                    banderaText = true;
                }

                if ((producto === 'DOCUMENTO(S)' || producto === 'AMBOS') && valueText === '') {
                    bandera = true;
                    labels.push('Nombre del archivo');
                }
            });

            $(this).find('input[type="file"]').each(function() {
                var key = $(this).attr('name');
                var valuefile = this.files[0];
                if (valuefile !=  undefined) {
                    row[key] = valuefile; 
                    banderaFile=true;
                }

                if ((producto === 'DOCUMENTO(S)' || producto === 'AMBOS') && valuefile === undefined) {
                    bandera = true;
                    labels.push('Adjunto');
                }
            });

            if (banderaText && banderaFile) {
                anexos_subsanacion_diagnostico.push(row);
            }
    })

    if (bandera) {
        var html = `<label>Los siguientes datos son obligatorios:</label><br><ol type=”A”>`;
        labels.forEach((label)=>{
            html += `<li>${label}</li>`;
        })
        html += '</ol>';

        Swal.fire({
          icon: "warning",
          title: "Atención",
          html: html,
        });

        return;
    }

    if (anexos_subsanacion_diagnostico.length > 0 ) {
        anexos_subsanacion_diagnostico.forEach((item, index) => {
            for (var key in item) {
                if (item.hasOwnProperty(key)) {
                    if (Array.isArray(item[key])) {
                        item[key].forEach((file, fileIndex) => {
                            formData.append(`${key}[${index}][${fileIndex}]`, file);
                        });
                    } else {
                        formData.append(`${key}[${index}]`, item[key]);
                    }
                }
            }
        });
    }

    $('.diagnosticoSubsanado').prop('disabled', true);

    fetch(url,{
        method: 'POST',
        headers: heads,
        body: formData
    }).then(response => {
        if (!response.ok) {
          return response.json().then(data => {
            throw new Error(data.error || 'Error en la solicitud');
          });
        }
        return response.json();
    })
    .then(data => {
        const message = data.message;
        Swal.fire('Éxito!', message, 'success').then(()=>{
            location.reload();
        });
    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    }).finally(() => {
        $('.diagnosticoSubsanado').prop('disabled', false);
    });
  }

function planVisita() {
    var bandera = false;

    var labels = [];
    var anexos_plan_visita = [];
    let etapa = $('#etapa').val();
    let estado = $('#estado').val();
    let estado_etapa = $('#estado_etapa').val();
    let numero_informe = $('#numero_informe').val();
    let razon_social = $('#razon_social').val();
    let nit = $('#nit').val();
    let observacion = $('#observaciones_envio_plan_visita').val();

    var url = `/guardar_plan_visita`;
    let id = $('#id').val();
    
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var formData = new FormData();

    formData.append('id', id);
    formData.append('etapa', etapa);
    formData.append('estado', estado);
    formData.append('estado_etapa', estado_etapa);
    formData.append('numero_informe', numero_informe);
    formData.append('razon_social', razon_social);
    formData.append('nit', nit);
    formData.append('observacion', observacion);

    var heads = {'X-CSRF-TOKEN': token};

    // Agregar el archivo seleccionado al FormData
    var fileInput = document.getElementById('enlace_plan_visita');
    var file = fileInput.files[0];
    if (file) {
        formData.append('enlace_plan_visita', file);
    } else {
        labels.push('Enlace plan de visita');
        bandera = true;
    }

    $('.required_plan_visita').each(function() {
        if ($(this).val() === '') {
            var label = $('label[for="' + $(this).attr('id') + '"]').text().replace(' (*)','');
            labels.push(label);
            bandera = true;
        } else {
            formData.append($(this).attr('id'), $(this).val());
        }
    });

    $('.tr_documentos_adicionales_plan_visita').each(function () {

        var row = {};
        var banderaText = false;
        var banderaFile = false;

            $(this).find('input[type="text"]').each(function() {
                var key = $(this).attr('name');
                var valueText = $(this).val();
                if (valueText !=  '') {
                    row[key] = valueText; 
                    banderaText = true;
                    //labels.push('Nombre del archivo');
                }
            });

            $(this).find('input[type="file"]').each(function() {
                var key = $(this).attr('name');
                var valuefile = this.files[0];
                if (valuefile !=  undefined) {
                    row[key] = valuefile; 
                    banderaFile=true;
                    //labels.push('Adjunto');
                }
            });

            if (banderaText && banderaFile) {
                anexos_plan_visita.push(row);
            }
    })

    if (bandera) {
        var html = `<label>Los siguientes datos son obligatorios:</label><br><ol type=”A”>`;
        for (var i = 0; i < labels.length; i++) {
            html += '<li>' + labels[i] + '</li>';
        }
        html += '</ol>';

        Swal.fire({
            icon: "warning",
            title: "Atención",
            html: html,
        });

        return;
    }

    if (anexos_plan_visita.length > 0 ) {
        anexos_plan_visita.forEach((item, index) => {
            for (var key in item) {
                if (item.hasOwnProperty(key)) {
                    if (Array.isArray(item[key])) {
                        item[key].forEach((file, fileIndex) => {
                            formData.append(`${key}[${index}][${fileIndex}]`, file);
                        });
                    } else {
                        formData.append(`${key}[${index}]`, item[key]);
                    }
                }
            }
        });
    }

    $('.diagnosticoSubsanado').prop('disabled', true);

    fetch(url, {
        method: 'POST',
        headers: heads,
        body: formData
    }).then(response => {
        if (!response.ok) {
            return response.json().then(data => {
                throw new Error(data.error || 'Error en la solicitud');
            });
        }
        return response.json();
    })
    .then(data => {
        const message = data.message;
        Swal.fire('Éxito!', message, 'success').then(() => {
            location.reload();
        });
    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    }).finally(() => {
        $('.diagnosticoSubsanado').prop('disabled', false);
    });
}

function revisionPlanVisita() {
    var bandera = false;

    var labels = [];
    let etapa = $('#etapa').val();
    let estado = $('#estado').val();
    let estado_etapa = $('#estado_etapa').val();
    let numero_informe = $('#numero_informe').val();
    let razon_social = $('#razon_social').val();
    let nit = $('#nit').val();
    let revision_plan_visita = $('#revision_plan_visita').val();

    var url = `/revisar_plan_visita`;
    let id = $('#id').val();
    
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var formData = new FormData();

    formData.append('id', id);
    formData.append('etapa', etapa);
    formData.append('estado', estado);
    formData.append('estado_etapa', estado_etapa);
    formData.append('numero_informe', numero_informe);
    formData.append('razon_social', razon_social);
    formData.append('nit', nit);

    var heads = {'X-CSRF-TOKEN': token}

    if (revision_plan_visita === '') {
        var html = `<label>Debe seleccionar si el plan de visita requiere modificaciones</label>`;

        Swal.fire({
          icon: "warning",
          title: "Atención",
          html: html,
        });

        return;
    }

    $('.required_revision_plan_visita').each(function() {
        if ($(this).val() === '') {
                if (revision_plan_visita === 'Si') {
                    var label = $('label[for="' + $(this).attr('id') + '"]').text().replace(' (*)','');
            
                    labels.push(label);
                    bandera = true;
                }
        } else {
            formData.append($(this).attr('id'), $(this).val());
        }
    });

    if (bandera) {
        var html = `<label>Los siguientes datos son obligatorios:</label><br><ol type=”A”>`;
        for (var i = 0; i < labels.length; i++) {
            html += '<li>' + labels[i] + '</li>';
        }
        html += '</ol>';

        Swal.fire({
          icon: "warning",
          title: "Atención",
          html: html,
        });

        return;
    }

    $('.diagnosticoSubsanado').prop('disabled', true);

    fetch(url,{
        method: 'POST',
        headers: heads,
        body: formData
    }).then(response => {
        if (!response.ok) {
          return response.json().then(data => {
            throw new Error(data.error || 'Error en la solicitud');
          });
        }
        return response.json();
    })
    .then(data => {
        const message = data.message;
        Swal.fire('Éxito!', message, 'success').then(()=>{
            location.reload();
        });
    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    }).finally(() => {
        $('.diagnosticoSubsanado').prop('disabled', false);
    });

}

function resultadoRevisionPlanVisita() {
    if ($('#revision_plan_visita').val() === 'Si') {
        $('.div_observaciones_plan_visita').show();
    }else{
        $('.div_observaciones_plan_visita').hide();
    }
}

function confirmacionInformacionPreviaVisita() {
    var bandera = false;

    var labels = [];
    let etapa = $('#etapa').val();
    let estado = $('#estado').val();
    let estado_etapa = $('#estado_etapa').val();
    let numero_informe = $('#numero_informe').val();
    let razon_social = $('#razon_social').val();
    let nit = $('#nit').val();
    let observacion = $('#observaciones_informacion_previa').val();

    var url = `/confirmacion_informacion_previa_visita`;
    let id = $('#id').val();
    
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var formData = new FormData();

    formData.append('id', id);
    formData.append('etapa', etapa);
    formData.append('estado', estado);
    formData.append('estado_etapa', estado_etapa);
    formData.append('numero_informe', numero_informe);
    formData.append('razon_social', razon_social);
    formData.append('nit', nit);
    formData.append('observacion', observacion);

    var heads = {'X-CSRF-TOKEN': token}

    $('.required_informacion_previa_visita').each(function() {
        if ($(this).val() === '') {
            var label = $('label[for="' + $(this).attr('id') + '"]').text().replace(' (*)','');
            
            labels.push(label);
            bandera = true;
        } else {
            formData.append($(this).attr('id'), $(this).val());
        }
    });

    if (bandera) {
        var html = `<label>Los siguientes datos son obligatorios:</label><br><ol type=”A”>`;
        for (var i = 0; i < labels.length; i++) {
            html += '<li>' + labels[i] + '</li>';
        }
        html += '</ol>';

        Swal.fire({
          icon: "warning",
          title: "Atención",
          html: html,
        });

        return;
    }

    $('.diagnosticoSubsanado').prop('disabled', true);

    fetch(url,{
        method: 'POST',
        headers: heads,
        body: formData
    }).then(response => {
        if (!response.ok) {
          return response.json().then(data => {
            throw new Error(data.error || 'Error en la solicitud');
          });
        }
        return response.json();
    })
    .then(data => {
        const message = data.message;
        Swal.fire('Éxito!', message, 'success').then(()=>{
            location.reload();
        });
    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    }).finally(() => {
        $('.diagnosticoSubsanado').prop('disabled', false);
    });

}

function resgistrarRespuestaEntidad() { 
    var bandera = false;

    var labels = [];
    let etapa = $('#etapa').val();
    let estado = $('#estado').val();
    let estado_etapa = $('#estado_etapa').val();
    let numero_informe = $('#numero_informe').val();
    let razon_social = $('#razon_social').val();
    let nit = $('#nit').val();
    let confirmacion_informacion_entidad = $('#confirmacion_informacion_entidad').val();

    var url = `/registro_respuesta_informacion_adicional`;
    let id = $('#id').val();
    
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var formData = new FormData();

    formData.append('id', id);
    formData.append('etapa', etapa);
    formData.append('estado', estado);
    formData.append('estado_etapa', estado_etapa);
    formData.append('numero_informe', numero_informe);
    formData.append('razon_social', razon_social);
    formData.append('nit', nit);

    var heads = {'X-CSRF-TOKEN': token}

    $('.required_confirmacion_informacion_entidad').each(function() {
        if ($(this).val() === '') {
            var label = $('label[for="' + $(this).attr('id') + '"]').text().replace(' (*)','');

            if (confirmacion_informacion_entidad === 'Si') {
                if (label === 'Radicado de entrada de la respuesta') {
                    labels.push(label);
                    bandera = true; 
                }else{
                    formData.append($(this).attr('id'), $(this).val());
                }
            }else if (confirmacion_informacion_entidad === 'No') {
                formData.append($(this).attr('id'), $(this).val());
            }else{
                labels.push(label);
                bandera = true; 
            }
            
        } else {
            formData.append($(this).attr('id'), $(this).val());
        }
    });

    if (bandera) {
        var html = `<label>Los siguientes datos son obligatorios:</label><br><ol type=”A”>`;
        for (var i = 0; i < labels.length; i++) {
            html += '<li>' + labels[i] + '</li>';
        }
        html += '</ol>';

        Swal.fire({
          icon: "warning",
          title: "Atención",
          html: html,
        });

        return;
    }

    $('.enviarRequerimientoInformacion').prop('disabled', true);

    fetch(url,{
        method: 'POST',
        headers: heads,
        body: formData
    }).then(response => {
        if (!response.ok) {
          return response.json().then(data => {
            throw new Error(data.error || 'Error en la solicitud');
          });
        }
        return response.json();
    })
    .then(data => {
        const message = data.message;
        Swal.fire('Éxito!', message, 'success').then(()=>{
            location.reload();
        });
    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    }).finally(() => {
        $('.enviarRequerimientoInformacion').prop('disabled', false);
    });

}

function confirmacionInformacionEntidad() {
    if ($('#confirmacion_informacion_entidad').val() === 'Si') {
        $('#div_radicado_entrada_respuesta_informacion_adicional').show();
    }else{
        $('#div_radicado_entrada_respuesta_informacion_adicional').hide();
    }
}

function finalizarRequerimientoInformacion() {
    var bandera = false;

    var labels = [];
    let etapa = $('#etapa').val();
    let estado = $('#estado').val();
    let estado_etapa = $('#estado_etapa').val();
    let numero_informe = $('#numero_informe').val();
    let razon_social = $('#razon_social').val();
    let nit = $('#nit').val();
    let observaciones = $('#observaciones_requerimiento_informacion_adicional').val();

    var url = `/finalizar_requerimiento_informacion`;
    let id = $('#id').val();
    
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var formData = new FormData();

    formData.append('id', id);
    formData.append('etapa', etapa);
    formData.append('estado', estado);
    formData.append('estado_etapa', estado_etapa);
    formData.append('numero_informe', numero_informe);
    formData.append('razon_social', razon_social);
    formData.append('nit', nit);
    formData.append('observaciones', observaciones);

    var heads = {'X-CSRF-TOKEN': token}

    $('.required_requerimiento_informacion_adicional').each(function() {
        if ($(this).val() === '') {
            var label = $('label[for="' + $(this).attr('id') + '"]').text().replace(' (*)','');
            
            labels.push(label);
            bandera = true;
        } else {
            formData.append($(this).attr('id'), $(this).val());
        }
    });

    if (bandera) {
        var html = `<label>Los siguientes datos son obligatorios:</label><br><ol type=”A”>`;
        for (var i = 0; i < labels.length; i++) {
            html += '<li>' + labels[i] + '</li>';
        }
        html += '</ol>';

        Swal.fire({
          icon: "warning",
          title: "Atención",
          html: html,
        });

        return;
    }

    $('.enviarRequerimientoInformacion').prop('disabled', true);

    fetch(url,{
        method: 'POST',
        headers: heads,
        body: formData
    }).then(response => {
        if (!response.ok) {
          return response.json().then(data => {
            throw new Error(data.error || 'Error en la solicitud');
          });
        }
        return response.json();
    })
    .then(data => {
        const message = data.message;
        Swal.fire('Éxito!', message, 'success').then(()=>{
            location.reload();
        });
    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    }).finally(() => {
        $('.enviarRequerimientoInformacion').prop('disabled', false);
    });

}

function necesidadVisita() {
    if ($('#necesidad_visita').val() === 'Si') {
        $('.div_ciclo_vida_plan_visita_ajustado').show();
        $('.div_observaciones_valoracion').hide();
    }else if ($('#necesidad_visita').val() === 'No'){
        $('.div_ciclo_vida_plan_visita_ajustado').hide();
        $('.div_observaciones_valoracion').show();
    }else{
        $('.div_ciclo_vida_plan_visita_ajustado').hide();
        $('.div_observaciones_valoracion').hie();
    }
}

function valoracionInformacionRecibida() {
    var bandera = false;

    var labels = [];
    let etapa = $('#etapa').val();
    let estado = $('#estado').val();
    let estado_etapa = $('#estado_etapa').val();
    let numero_informe = $('#numero_informe').val();
    let razon_social = $('#razon_social').val();
    let nit = $('#nit').val();
    let necesidad_visita = $('#necesidad_visita').val();

    var url = `/valoracion_informacion_recibida`;
    let id = $('#id').val();
    
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var formData = new FormData();

    formData.append('id', id);
    formData.append('etapa', etapa);
    formData.append('estado', estado);
    formData.append('estado_etapa', estado_etapa);
    formData.append('numero_informe', numero_informe);
    formData.append('razon_social', razon_social);
    formData.append('nit', nit);

    var heads = {'X-CSRF-TOKEN': token}

    if (necesidad_visita == '') {
        var html = `<label>Debe completar el campo ¿Es necesario efectuar la visita? </label>`;

        Swal.fire({
          icon: "warning",
          title: "Atención",
          html: html,
        });

        return;
    }

    $('.required_valoracion_informacion').each(function() {
        if ($(this).val() === '') {
            if (necesidad_visita === 'Si') {
                var label = $('label[for="' + $(this).attr('id') + '"]').text().replace(' (*)','');
                if (label === 'Ciclo de vida plan de visita ajustado') {
                    labels.push(label);
                    bandera = true;
                }else{
                    formData.append($(this).attr('id'), $(this).val());
                }
            }else if(necesidad_visita === 'No'){
                var label = $('label[for="' + $(this).attr('id') + '"]').text().replace(' (*)','');
                if (label === 'Observaciones') {
                    labels.push(label);
                    bandera = true;
                } else{
                    formData.append($(this).attr('id'), $(this).val());
                }
            }
            
        } else {
            formData.append($(this).attr('id'), $(this).val());
        }
    });

    if (bandera) {
        var html = `<label>Los siguientes datos son obligatorios:</label><br><ol type=”A”>`;
        for (var i = 0; i < labels.length; i++) {
            html += '<li>' + labels[i] + '</li>';
        }
        html += '</ol>';

        Swal.fire({
          icon: "warning",
          title: "Atención",
          html: html,
        });

        return;
    }

    $('.enviarValoracionInformacion').prop('disabled', true);

    fetch(url,{
        method: 'POST',
        headers: heads,
        body: formData
    }).then(response => {
        if (!response.ok) {
          return response.json().then(data => {
            throw new Error(data.error || 'Error en la solicitud');
          });
        }
        return response.json();
    })
    .then(data => {
        const message = data.message;
        Swal.fire('Éxito!', message, 'success').then(()=>{
            location.reload();
        });
    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    }).finally(() => {
        $('.enviarValoracionInformacion').prop('disabled', false);
    });
}

function confirmacionVisita() {
    var bandera = false;

    var labels = [];
    let etapa = $('#etapa').val();
    let estado = $('#estado').val();
    let estado_etapa = $('#estado_etapa').val();
    let numero_informe = $('#numero_informe').val();
    let razon_social = $('#razon_social').val();
    let nit = $('#nit').val();

    var url = `/confirmacion_visita`;
    let id = $('#id').val();
    
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var formData = new FormData();

    formData.append('id', id);
    formData.append('etapa', etapa);
    formData.append('estado', estado);
    formData.append('estado_etapa', estado_etapa);
    formData.append('numero_informe', numero_informe);
    formData.append('razon_social', razon_social);
    formData.append('nit', nit);

    var heads = {'X-CSRF-TOKEN': token}

    $('.required_confirmacion_visita').each(function() {
        if ($(this).val() === '') {
            var label = $('label[for="' + $(this).attr('id') + '"]').text().replace(' (*)','');

            labels.push(label);
            bandera = true;
        } else {
            formData.append($(this).attr('id'), $(this).val());
        }
    });

    if (bandera) {
        var html = `<label>Los siguientes datos son obligatorios:</label><br><ol type=”A”>`;
        for (var i = 0; i < labels.length; i++) {
            html += '<li>' + labels[i] + '</li>';
        }
        html += '</ol>';

        Swal.fire({
          icon: "warning",
          title: "Atención",
          html: html,
        });

        return;
    }

    $('.enviarConfirmacionVisita').prop('disabled', true);

    fetch(url,{
        method: 'POST',
        headers: heads,
        body: formData
    }).then(response => {
        if (!response.ok) {
          return response.json().then(data => {
            throw new Error(data.error || 'Error en la solicitud');
          });
        }
        return response.json();
    })
    .then(data => {
        const message = data.message;
        Swal.fire('Éxito!', message, 'success').then(()=>{
            location.reload();
        });
    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    }).finally(() => {
        $('.enviarConfirmacionVisita').prop('disabled', false);
    });
}

function cartasPresentacion() {
    var bandera = false;

    var labels = [];
    let etapa = $('#etapa').val();
    let estado = $('#estado').val();
    let estado_etapa = $('#estado_etapa').val();
    let numero_informe = $('#numero_informe').val();
    let razon_social = $('#razon_social').val();
    let nit = $('#nit').val();

    var url = `/cartas_presentacion`;
    let id = $('#id').val();
    
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var formData = new FormData();

    formData.append('id', id);
    formData.append('etapa', etapa);
    formData.append('estado', estado);
    formData.append('estado_etapa', estado_etapa);
    formData.append('numero_informe', numero_informe);
    formData.append('razon_social', razon_social);
    formData.append('nit', nit);

    var heads = {'X-CSRF-TOKEN': token}

    $('.required_cartas_presentacion').each(function() {
        if ($(this).val() === '') {
            var label = $('label[for="' + $(this).attr('id') + '"]').text().replace(' (*)','');

            labels.push(label);
            bandera = true;
        } else {
            formData.append($(this).attr('id'), $(this).val());
        }
    });

    if (bandera) {
        var html = `<label>Los siguientes datos son obligatorios:</label><br><ol type=”A”>`;
        for (var i = 0; i < labels.length; i++) {
            html += '<li>' + labels[i] + '</li>';
        }
        html += '</ol>';

        Swal.fire({
          icon: "warning",
          title: "Atención",
          html: html,
        });

        return;
    }

    $('.enviarCartasPresentacion').prop('disabled', true);

    fetch(url,{
        method: 'POST',
        headers: heads,
        body: formData
    }).then(response => {
        if (!response.ok) {
          return response.json().then(data => {
            throw new Error(data.error || 'Error en la solicitud');
          });
        }
        return response.json();
    })
    .then(data => {
        const message = data.message;
        Swal.fire('Éxito!', message, 'success').then(()=>{
            location.reload();
        });
    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    }).finally(() => {
        $('.enviarCartasPresentacion').prop('disabled', false);
    });
}

function abrirVisitaInspeccion() {
    var bandera = false;

    var labels = [];
    let id = $('#id').val();
    let etapa = $('#etapa').val();
    let estado = $('#estado').val();
    let estado_etapa = $('#estado_etapa').val();
    let numero_informe = $('#numero_informe').val();
    let razon_social = $('#razon_social').val();
    let nit = $('#nit').val();
    let url = `/abrir_visita_inspeccion`;

    let grupo_inspeccion = [];

    $('.tr_grupo_inspeccion_final').each(function () {

        var row = {};
            $(this).find('select').each(function() {
                var key = $(this).attr('name');
                var value = $(this).val();
                row[key] = value;

                if (value === '') {
                    bandera = true;
                }
            });
            grupo_inspeccion.push(row);
    })
    
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var formData = new FormData();
    formData.append('id', id);
    formData.append('grupo_inspeccion', JSON.stringify(grupo_inspeccion) );
    formData.append('etapa', etapa);
    formData.append('estado', estado);
    formData.append('estado_etapa', estado_etapa);
    formData.append('numero_informe', numero_informe);
    formData.append('razon_social', razon_social);
    formData.append('nit', nit);

    var heads = {'X-CSRF-TOKEN': token}


    if (bandera) {
        var html = `<label>Todos los datos de la tabla del grupo de inspección deben ser diligenciados</label`;

        Swal.fire({
          icon: "warning",
          title: "Atención",
          html: html,
        });

        return;
    }


    $('.required_apertura_visita').each(function() {
        if ($(this).val() === '') {
            var label = $('label[for="' + $(this).attr('id') + '"]').text().replace(' (*)','');

            labels.push(label);
            bandera = true;
        } else {
            formData.append($(this).attr('id'), $(this).val());
        }
    });

    if (bandera) {
        var html = `<label>Los siguientes datos son obligatorios:</label><br><ol type=”A”>`;
        for (var i = 0; i < labels.length; i++) {
            html += '<li>' + labels[i] + '</li>';
        }
        html += '</ol>';

        Swal.fire({
          icon: "warning",
          title: "Atención",
          html: html,
        });

        return;
    }

    $('.abrirVisitaInspeccion').prop('disabled', true);

    fetch(url,{
        method: 'POST',
        headers: heads,
        body: formData
    }).then(response => {
        if (!response.ok) {
          return response.json().then(data => {
            throw new Error(data.error || 'Error en la solicitud');
          });
        }
        return response.json();
    })
    .then(data => {
        const message = data.message;
        Swal.fire('Éxito!', message, 'success').then(()=>{
            location.reload();
        });
    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    }).finally(() => {
        $('.abrirVisitaInspeccion').prop('disabled', false);
    });
  }

  function anadirInspectorFinal() {

    var ultimoTr = $('#tabla_asignacion_grupo_inspeccion_final tbody tr:last').clone();

    var ultimoNumero = parseInt($('#tabla_asignacion_grupo_inspeccion_final tbody tr:last td:first p').text());
    ultimoTr.find('p').text(ultimoNumero + 1);
    ultimoTr.find('select:first').val('');

    $('#tabla_asignacion_grupo_inspeccion_final tbody').append(ultimoTr);

  }

  function iniciarVisitaInspeccion() {

    let id = $('#id').val();
    let etapa = $('#etapa').val();
    let estado = $('#estado').val();
    let estado_etapa = $('#estado_etapa').val();
    let numero_informe = $('#numero_informe').val();
    let razon_social = $('#razon_social').val();
    let nit = $('#nit').val();
    let url = `/iniciar_visita_inspeccion`;
    
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var formData = new FormData();
    formData.append('id', id);
    formData.append('etapa', etapa);
    formData.append('estado', estado);
    formData.append('estado_etapa', estado_etapa);
    formData.append('numero_informe', numero_informe);
    formData.append('razon_social', razon_social);
    formData.append('nit', nit);

    var heads = {'X-CSRF-TOKEN': token}

    Swal.fire({
        title: "¿Desea iniciar la visita de inspección?",
        text: "Se actualizará la fecha de inicio de la visita de inspección",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Si, iniciar visita"
      }).then((result) => {
        if (result.isConfirmed) {
            fetch(url,{
                method: 'POST',
                headers: heads,
                body: formData
            }).then(response => {
                if (!response.ok) {
                  return response.json().then(data => {
                    throw new Error(data.error || 'Error en la solicitud');
                  });
                }
                return response.json();
            })
            .then(data => {
                const message = data.message;
                Swal.fire('Éxito!', message, 'success').then(()=>{
                    location.reload();
                });
            })
            .catch(error => {
                Swal.fire('Error', error.message, 'error');
            });
        }
      });

    
  }

  function cerrarVisitaInspeccion() {
    var bandera = false;

    let id = $('#id').val();
    let etapa = $('#etapa').val();
    let estado = $('#estado').val();
    let estado_etapa = $('#estado_etapa').val();
    let numero_informe = $('#numero_informe').val();
    let razon_social = $('#razon_social').val();
    let nit = $('#nit').val();
    let url = `/cerrar_visita_inspeccion`;
    let cierre_visita = [];
    
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var formData = new FormData();
    formData.append('id', id);
    formData.append('etapa', etapa);
    formData.append('estado', estado);
    formData.append('estado_etapa', estado_etapa);
    formData.append('numero_informe', numero_informe);
    formData.append('razon_social', razon_social);
    formData.append('nit', nit);

    var heads = {'X-CSRF-TOKEN': token}

    $('.tr_documentos_cierre_visita').each(function () {

        var row = {};
            $(this).find('input[type="text"]').each(function() {
                var key = $(this).attr('name');
                var value = $(this).val();
                row[key] = value;

                if (value === '') {
                    bandera = true;
                }
            });
            cierre_visita.push(row);
    })

    formData.append('cierre_visita', JSON.stringify(cierre_visita));

    if (bandera || cierre_visita.length < 1) {
        var html = `<label>Todos los datos de las tablas deben ser diligenciados</label>`;

        Swal.fire({
          icon: "warning",
          title: "Atención",
          html: html,
        });

        return;
    }

    $('.cerrarVisitaInspeccion').prop('disabled', true);

    fetch(url,{
        method: 'POST',
        headers: heads,
        body: formData
    }).then(response => {
        if (!response.ok) {
          return response.json().then(data => {
            throw new Error(data.error || 'Error en la solicitud');
          });
        }
        return response.json();
    })
    .then(data => {
        const message = data.message;
        Swal.fire('Éxito!', message, 'success').then(()=>{
            location.reload();
        });
    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    }).finally(() => {
        $('.cerrarVisitaInspeccion').prop('disabled', false);
    });
  }

  function registrarHallazgos() {
    var bandera = false;

    var labels = [];
    let id = $('#id').val();
    let etapa = $('#etapa').val();
    let estado = $('#estado').val();
    let estado_etapa = $('#estado_etapa').val();
    let numero_informe = $('#numero_informe').val();
    let razon_social = $('#razon_social').val();
    let nit = $('#nit').val();
    let url = `/registrar_hallazgos`;
    let registro_hallazgos = [];
    
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var formData = new FormData();
    formData.append('id', id);
    formData.append('etapa', etapa);
    formData.append('estado', estado);
    formData.append('estado_etapa', estado_etapa);
    formData.append('numero_informe', numero_informe);
    formData.append('razon_social', razon_social);
    formData.append('nit', nit);

    var heads = {'X-CSRF-TOKEN': token}

    $('.tr_documentos_hallazgos').each(function () {

        var row = {};
            $(this).find('input[type="text"]').each(function() {
                var key = $(this).attr('name');
                var value = $(this).val();
                row[key] = value;

                if (value === '') {
                    bandera = true;
                }
            });
            registro_hallazgos.push(row);
    })

    if (bandera || registro_hallazgos.length < 1 ) {
        var html = `<label>Todos los datos de las tablas deben ser diligenciados</label>`;

        Swal.fire({
          icon: "warning",
          title: "Atención",
          html: html,
        });

        return;
    }

    formData.append('registro_hallazgos', JSON.stringify(registro_hallazgos));

    $('.registrarHallazgos').prop('disabled', true);

    fetch(url,{
        method: 'POST',
        headers: heads,
        body: formData
    }).then(response => {
        if (!response.ok) {
          return response.json().then(data => {
            throw new Error(data.error || 'Error en la solicitud');
          });
        }
        return response.json();
    })
    .then(data => {
        const message = data.message;
        Swal.fire('Éxito!', message, 'success').then(()=>{
            location.reload();
        });
    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    }).finally(() => {
        $('.registrarHallazgos').prop('disabled', false);
    });
  }

  function registrarHallazgosConsolidados() {
    var bandera = false;

    var labels = [];
    let id = $('#id').val();
    let etapa = $('#etapa').val();
    let estado = $('#estado').val();
    let estado_etapa = $('#estado_etapa').val();
    let numero_informe = $('#numero_informe').val();
    let razon_social = $('#razon_social').val();
    let nit = $('#nit').val();
    let url = `/consolidar_hallazgos`;
    
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var formData = new FormData();
    formData.append('id', id);
    formData.append('etapa', etapa);
    formData.append('estado', estado);
    formData.append('estado_etapa', estado_etapa);
    formData.append('numero_informe', numero_informe);
    formData.append('razon_social', razon_social);
    formData.append('nit', nit);

    var heads = {'X-CSRF-TOKEN': token}

    $('.required_registro_hallazgos_consolidados').each(function() {
        if ($(this).val() === '') {
            var label = $('label[for="' + $(this).attr('id') + '"]').text().replace(' (*)','');

            labels.push(label);
            bandera = true;
        } else {
            formData.append($(this).attr('id'), $(this).val());
        }
    });

    if (bandera) {
        var html = `<label>Los siguientes datos son obligatorios:</label><br><ol type=”A”>`;
        for (var i = 0; i < labels.length; i++) {
            html += '<li>' + labels[i] + '</li>';
        }
        html += '</ol>';

        Swal.fire({
          icon: "warning",
          title: "Atención",
          html: html,
        });

        return;
    }

    $('.registrarHallazgosConsolidados').prop('disabled', true);

    fetch(url,{
        method: 'POST',
        headers: heads,
        body: formData
    }).then(response => {
        if (!response.ok) {
          return response.json().then(data => {
            throw new Error(data.error || 'Error en la solicitud');
          });
        }
        return response.json();
    })
    .then(data => {
        const message = data.message;
        Swal.fire('Éxito!', message, 'success').then(()=>{
            location.reload();
        });
    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    }).finally(() => {
        $('.registrarHallazgosConsolidados').prop('disabled', false);
    });
  }

  function registrarProyectoInformeFinal() {
    var bandera = false;

    var labels = [];
    let id = $('#id').val();
    let etapa = $('#etapa').val();
    let estado = $('#estado').val();
    let estado_etapa = $('#estado_etapa').val();
    let numero_informe = $('#numero_informe').val();
    let razon_social = $('#razon_social').val();
    let nit = $('#nit').val();
    let url = `/proyecto_informe_final`;
    
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var formData = new FormData();
    formData.append('id', id);
    formData.append('etapa', etapa);
    formData.append('estado', estado);
    formData.append('estado_etapa', estado_etapa);
    formData.append('numero_informe', numero_informe);
    formData.append('razon_social', razon_social);
    formData.append('nit', nit);

    var heads = {'X-CSRF-TOKEN': token}

    $('.required_proyecto_informe_final').each(function() {
        if ($(this).val() === '') {
            var label = $('label[for="' + $(this).attr('id') + '"]').text().replace(' (*)','');

            labels.push(label);
            bandera = true;
        } else {
            formData.append($(this).attr('id'), $(this).val());
        }
    });

    if (bandera) {
        var html = `<label>Los siguientes datos son obligatorios:</label><br><ol type=”A”>`;
        for (var i = 0; i < labels.length; i++) {
            html += '<li>' + labels[i] + '</li>';
        }
        html += '</ol>';

        Swal.fire({
          icon: "warning",
          title: "Atención",
          html: html,
        });

        return;
    }

    $('.registrarProyectoInformeFinal').prop('disabled', true);

    fetch(url,{
        method: 'POST',
        headers: heads,
        body: formData
    }).then(response => {
        if (!response.ok) {
          return response.json().then(data => {
            throw new Error(data.error || 'Error en la solicitud');
          });
        }
        return response.json();
    })
    .then(data => {
        const message = data.message;
        Swal.fire('Éxito!', message, 'success').then(()=>{
            location.reload();
        });
    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    }).finally(() => {
        $('.registrarProyectoInformeFinal').prop('disabled', false);
    });
  }

  function registrarRevisionProyectoInformeFinal() {
    var bandera = false;

    var labels = [];
    let id = $('#id').val();
    let etapa = $('#etapa').val();
    let estado = $('#estado').val();
    let estado_etapa = $('#estado_etapa').val();
    let numero_informe = $('#numero_informe').val();
    let razon_social = $('#razon_social').val();
    let nit = $('#nit').val();
    let url = `/revision_proyecto_informe_final`;
    
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var formData = new FormData();
    formData.append('id', id);
    formData.append('etapa', etapa);
    formData.append('estado', estado);
    formData.append('estado_etapa', estado_etapa);
    formData.append('numero_informe', numero_informe);
    formData.append('razon_social', razon_social);
    formData.append('nit', nit);

    var heads = {'X-CSRF-TOKEN': token}

    $('.required_revision_proyecto_informe_final').each(function() {
        if ($(this).val() === '') {
            var label = $('label[for="' + $(this).attr('id') + '"]').text().replace(' (*)','');

            labels.push(label);
            bandera = true;
        } else {
            formData.append($(this).attr('id'), $(this).val());
        }
    });

    if (bandera) {
        var html = `<label>Los siguientes datos son obligatorios:</label><br><ol type=”A”>`;
        for (var i = 0; i < labels.length; i++) {
            html += '<li>' + labels[i] + '</li>';
        }
        html += '</ol>';

        Swal.fire({
          icon: "warning",
          title: "Atención",
          html: html,
        });

        return;
    }

    $('.registrarProyectoInformeFinal').prop('disabled', true);

    fetch(url,{
        method: 'POST',
        headers: heads,
        body: formData
    }).then(response => {
        if (!response.ok) {
          return response.json().then(data => {
            throw new Error(data.error || 'Error en la solicitud');
          });
        }
        return response.json();
    })
    .then(data => {
        const message = data.message;
        Swal.fire('Éxito!', message, 'success').then(()=>{
            location.reload();
        });
    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    }).finally(() => {
        $('.registrarProyectoInformeFinal').prop('disabled', false);
    });
  }

  function verificacionesCorreccionesInformeFinal() {
    var bandera = false;

    var labels = [];
    let id = $('#id').val();
    let etapa = $('#etapa').val();
    let estado = $('#estado').val();
    let estado_etapa = $('#estado_etapa').val();
    let numero_informe = $('#numero_informe').val();
    let razon_social = $('#razon_social').val();
    let nit = $('#nit').val();
    let url = `/verificaciones_correcciones_informe_final`;
    
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var formData = new FormData();
    formData.append('id', id);
    formData.append('etapa', etapa);
    formData.append('estado', estado);
    formData.append('estado_etapa', estado_etapa);
    formData.append('numero_informe', numero_informe);
    formData.append('razon_social', razon_social);
    formData.append('nit', nit);

    var heads = {'X-CSRF-TOKEN': token}

    $('.required_verificacion_correcciones_informe_final').each(function() {
        if ($(this).val() === '') {
            var label = $('label[for="' + $(this).attr('id') + '"]').text().replace(' (*)','');

            labels.push(label);
            bandera = true;
        } else {
            formData.append($(this).attr('id'), $(this).val());
        }
    });

    if (bandera) {
        var html = `<label>Los siguientes datos son obligatorios:</label><br><ol type=”A”>`;
        for (var i = 0; i < labels.length; i++) {
            html += '<li>' + labels[i] + '</li>';
        }
        html += '</ol>';

        Swal.fire({
          icon: "warning",
          title: "Atención",
          html: html,
        });

        return;
    }

    $('.registrarProyectoInformeFinal').prop('disabled', true);

    fetch(url,{
        method: 'POST',
        headers: heads,
        body: formData
    }).then(response => {
        if (!response.ok) {
          return response.json().then(data => {
            throw new Error(data.error || 'Error en la solicitud');
          });
        }
        return response.json();
    })
    .then(data => {
        const message = data.message;
        Swal.fire('Éxito!', message, 'success').then(()=>{
            location.reload();
        });
    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    }).finally(() => {
        $('.registrarProyectoInformeFinal').prop('disabled', false);
    });
  }

  function correccionesInformeFinalCorregido() {
    var bandera = false;

    var labels = [];
    let id = $('#id').val();
    let etapa = $('#etapa').val();
    let estado = $('#estado').val();
    let estado_etapa = $('#estado_etapa').val();
    let numero_informe = $('#numero_informe').val();
    let razon_social = $('#razon_social').val();
    let nit = $('#nit').val();
    let url = `/correcciones_informe_final`;
    
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var formData = new FormData();
    formData.append('id', id);
    formData.append('etapa', etapa);
    formData.append('estado', estado);
    formData.append('estado_etapa', estado_etapa);
    formData.append('numero_informe', numero_informe);
    formData.append('razon_social', razon_social);
    formData.append('nit', nit);

    var heads = {'X-CSRF-TOKEN': token}

    $('.required_revision_proyecto_informe_final_corregido').each(function() {
        if ($(this).val() === '') {
            var label = $('label[for="' + $(this).attr('id') + '"]').text().replace(' (*)','');

            labels.push(label);
            bandera = true;
        } else {
            formData.append($(this).attr('id'), $(this).val());
        }
    });

    if (bandera) {
        var html = `<label>Los siguientes datos son obligatorios:</label><br><ol type=”A”>`;
        for (var i = 0; i < labels.length; i++) {
            html += '<li>' + labels[i] + '</li>';
        }
        html += '</ol>';

        Swal.fire({
          icon: "warning",
          title: "Atención",
          html: html,
        });

        return;
    }

    $('.registrarProyectoInformeFinalCorregido').prop('disabled', true);

    fetch(url,{
        method: 'POST',
        headers: heads,
        body: formData
    }).then(response => {
        if (!response.ok) {
          return response.json().then(data => {
            throw new Error(data.error || 'Error en la solicitud');
          });
        }
        return response.json();
    })
    .then(data => {
        const message = data.message;
        Swal.fire('Éxito!', message, 'success').then(()=>{
            location.reload();
        });
    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    }).finally(() => {
        $('.registrarProyectoInformeFinalCorregido').prop('disabled', false);
    });
  }

  function informeFinalCoordinacinoes() {
    var bandera = false;

    var labels = [];
    let id = $('#id').val();
    let etapa = $('#etapa').val();
    let estado = $('#estado').val();
    let estado_etapa = $('#estado_etapa').val();
    let numero_informe = $('#numero_informe').val();
    let razon_social = $('#razon_social').val();
    let nit = $('#nit').val();
    let url = `/remitir_proyecto_informe_final_coordinaciones`;
    
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var formData = new FormData();
    formData.append('id', id);
    formData.append('etapa', etapa);
    formData.append('estado', estado);
    formData.append('estado_etapa', estado_etapa);
    formData.append('numero_informe', numero_informe);
    formData.append('razon_social', razon_social);
    formData.append('nit', nit);

    var heads = {'X-CSRF-TOKEN': token}

    $('.required_revision_proyecto_informe_final_coordinacinoes').each(function() {
        if ($(this).val() === '') {
            var label = $('label[for="' + $(this).attr('id') + '"]').text().replace(' (*)','');

            labels.push(label);
            bandera = true;
        } else {
            formData.append($(this).attr('id'), $(this).val());
        }
    });

    if (bandera) {
        var html = `<label>Los siguientes datos son obligatorios:</label><br><ol type=”A”>`;
        for (var i = 0; i < labels.length; i++) {
            html += '<li>' + labels[i] + '</li>';
        }
        html += '</ol>';

        Swal.fire({
          icon: "warning",
          title: "Atención",
          html: html,
        });

        return;
    }

    $('.registrarProyectoInformeFinalCoordinacinoes').prop('disabled', true);

    fetch(url,{
        method: 'POST',
        headers: heads,
        body: formData
    }).then(response => {
        if (!response.ok) {
          return response.json().then(data => {
            throw new Error(data.error || 'Error en la solicitud');
          });
        }
        return response.json();
    })
    .then(data => {
        const message = data.message;
        Swal.fire('Éxito!', message, 'success').then(()=>{
            location.reload();
        });
    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    }).finally(() => {
        $('.registrarProyectoInformeFinalCoordinacinoes').prop('disabled', false);
    });
  }

  function revisionInformeFinalCoordinacinoes() {
    var bandera = false;

    var labels = [];
    let id = $('#id').val();
    let etapa = $('#etapa').val();
    let estado = $('#estado').val();
    let estado_etapa = $('#estado_etapa').val();
    let numero_informe = $('#numero_informe').val();
    let razon_social = $('#razon_social').val();
    let nit = $('#nit').val();
    let url = `/revision_informe_final_coordinaciones`;
    
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var formData = new FormData();
    formData.append('id', id);
    formData.append('etapa', etapa);
    formData.append('estado', estado);
    formData.append('estado_etapa', estado_etapa);
    formData.append('numero_informe', numero_informe);
    formData.append('razon_social', razon_social);
    formData.append('nit', nit);

    var heads = {'X-CSRF-TOKEN': token}

    $('.required_revision_informe_final_coordinaciones').each(function() {
        if ($(this).val() === '') {
            if ($(this).attr('id') !== 'observaciones_revision_informe_final_coordinaciones') {
                var label = $('label[for="' + $(this).attr('id') + '"]').text().replace(' (*)','');

                labels.push(label);
                bandera = true;
            }else{
                formData.append($(this).attr('id'), $(this).val());
            }
        } else {
            formData.append($(this).attr('id'), $(this).val());
        }
    });

    if (bandera) {
        var html = `<label>Los siguientes datos son obligatorios:</label><br><ol type=”A”>`;
        for (var i = 0; i < labels.length; i++) {
            html += '<li>' + labels[i] + '</li>';
        }
        html += '</ol>';

        Swal.fire({
          icon: "warning",
          title: "Atención",
          html: html,
        });

        return;
    }

    $('.registrarProyectoInformeFinalCoordinacinoes').prop('disabled', true);

    fetch(url,{
        method: 'POST',
        headers: heads,
        body: formData
    }).then(response => {
        if (!response.ok) {
          return response.json().then(data => {
            throw new Error(data.error || 'Error en la solicitud');
          });
        }
        return response.json();
    })
    .then(data => {
        const message = data.message;
        Swal.fire('Éxito!', message, 'success').then(()=>{
            location.reload();
        });
    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    }).finally(() => {
        $('.registrarProyectoInformeFinalCoordinacinoes').prop('disabled', false);
    });
  }

  function revisionInformeFinalIntendente() {
    var bandera = false;

    var labels = [];
    let id = $('#id').val();
    let etapa = $('#etapa').val();
    let estado = $('#estado').val();
    let estado_etapa = $('#estado_etapa').val();
    let numero_informe = $('#numero_informe').val();
    let razon_social = $('#razon_social').val();
    let nit = $('#nit').val();
    let url = `/revision_informe_final_intendente`;
    
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var formData = new FormData();
    formData.append('id', id);
    formData.append('etapa', etapa);
    formData.append('estado', estado);
    formData.append('estado_etapa', estado_etapa);
    formData.append('numero_informe', numero_informe);
    formData.append('razon_social', razon_social);
    formData.append('nit', nit);

    var heads = {'X-CSRF-TOKEN': token}

    $('.required_revision_informe_final_intendente').each(function() {
        if ($(this).val() === '') {
            if ($(this).attr('id') !== 'observaciones_revision_informe_final_intendente') {
                var label = $('label[for="' + $(this).attr('id') + '"]').text().replace(' (*)','');

                labels.push(label);
                bandera = true;
            }else{
                formData.append($(this).attr('id'), $(this).val());
            }
        } else {
            formData.append($(this).attr('id'), $(this).val());
        }
    });

    if (bandera) {
        var html = `<label>Los siguientes datos son obligatorios:</label><br><ol type=”A”>`;
        for (var i = 0; i < labels.length; i++) {
            html += '<li>' + labels[i] + '</li>';
        }
        html += '</ol>';

        Swal.fire({
          icon: "warning",
          title: "Atención",
          html: html,
        });

        return;
    }

    $('.registrarProyectoInformeFinalCoordinacinoes').prop('disabled', true);

    fetch(url,{
        method: 'POST',
        headers: heads,
        body: formData
    }).then(response => {
        if (!response.ok) {
          return response.json().then(data => {
            throw new Error(data.error || 'Error en la solicitud');
          });
        }
        return response.json();
    })
    .then(data => {
        const message = data.message;
        Swal.fire('Éxito!', message, 'success').then(()=>{
            location.reload();
        });
    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    }).finally(() => {
        $('.registrarProyectoInformeFinalCoordinacinoes').prop('disabled', false);
    });
  }

  function informeFinalFirmado() {
    var bandera = false;

    var labels = [];
    let id = $('#id').val();
    let etapa = $('#etapa').val();
    let estado = $('#estado').val();
    let estado_etapa = $('#estado_etapa').val();
    let numero_informe = $('#numero_informe').val();
    let razon_social = $('#razon_social').val();
    let nit = $('#nit').val();
    let url = `/firmar_informe_final`;
    
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var formData = new FormData();
    formData.append('id', id);
    formData.append('etapa', etapa);
    formData.append('estado', estado);
    formData.append('estado_etapa', estado_etapa);
    formData.append('numero_informe', numero_informe);
    formData.append('razon_social', razon_social);
    formData.append('nit', nit);

    var heads = {'X-CSRF-TOKEN': token}

    $('.required_informe_final_firmado').each(function() {
        if ($(this).val() === '') {
            var label = $('label[for="' + $(this).attr('id') + '"]').text().replace(' (*)','');

            labels.push(label);
            bandera = true;
        } else {
            formData.append($(this).attr('id'), $(this).val());
        }
    });

    if (bandera) {
        var html = `<label>Los siguientes datos son obligatorios:</label><br><ol type=”A”>`;
        for (var i = 0; i < labels.length; i++) {
            html += '<li>' + labels[i] + '</li>';
        }
        html += '</ol>';

        Swal.fire({
          icon: "warning",
          title: "Atención",
          html: html,
        });

        return;
    }

    $('.informeFinalFirmado').prop('disabled', true);

    fetch(url,{
        method: 'POST',
        headers: heads,
        body: formData
    }).then(response => {
        if (!response.ok) {
          return response.json().then(data => {
            throw new Error(data.error || 'Error en la solicitud');
          });
        }
        return response.json();
    })
    .then(data => {
        const message = data.message;
        Swal.fire('Éxito!', message, 'success').then(()=>{
            location.reload();
        });
    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    }).finally(() => {
        $('.informeFinalFirmado').prop('disabled', false);
    });
  }

  function registrarConfirmacionIntervencionInmediata() {
    var bandera = false;

    var labels = [];
    let id = $('#id').val();
    let etapa = $('#etapa').val();
    let estado = $('#estado').val();
    let estado_etapa = $('#estado_etapa').val();
    let numero_informe = $('#numero_informe').val();
    let razon_social = $('#razon_social').val();
    let nit = $('#nit').val();
    let url = `/confirmacion_intervencion_inmediata`;
    
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var formData = new FormData();
    formData.append('id', id);
    formData.append('etapa', etapa);
    formData.append('estado', estado);
    formData.append('estado_etapa', estado_etapa);
    formData.append('numero_informe', numero_informe);
    formData.append('razon_social', razon_social);
    formData.append('nit', nit);

    var heads = {'X-CSRF-TOKEN': token}

    $('.required_confirmacion_intervencion_inmediata').each(function() {
        if ($(this).val() === '') {
            if ($(this).attr('id') !== 'observaciones_intervencion_inmediata') {
                var label = $('label[for="' + $(this).attr('id') + '"]').text().replace(' (*)','');

                labels.push(label);
                bandera = true;
            }else{
                formData.append($(this).attr('id'), $(this).val());
            }
        } else {
            formData.append($(this).attr('id'), $(this).val());
        }
    });

    if (bandera) {
        var html = `<label>Los siguientes datos son obligatorios:</label><br><ol type=”A”>`;
        for (var i = 0; i < labels.length; i++) {
            html += '<li>' + labels[i] + '</li>';
        }
        html += '</ol>';

        Swal.fire({
          icon: "warning",
          title: "Atención",
          html: html,
        });

        return;
    }

    $('.registrarConfirmacionIntervencionInmediata').prop('disabled', true);

    fetch(url,{
        method: 'POST',
        headers: heads,
        body: formData
    }).then(response => {
        if (!response.ok) {
          return response.json().then(data => {
            throw new Error(data.error || 'Error en la solicitud');
          });
        }
        return response.json();
    })
    .then(data => {
        const message = data.message;
        Swal.fire('Éxito!', message, 'success').then(()=>{
            location.reload();
        });
    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    }).finally(() => {
        $('.registrarConfirmacionIntervencionInmediata').prop('disabled', false);
    });
  }

  function anadirDesignado() {

    var ultimoTr = $('#tabla_asignacion_designados tbody tr:last').clone();

    var ultimoNumero = parseInt($('#tabla_asignacion_designados tbody tr:last td:first p').text());
    ultimoTr.find('p').text(ultimoNumero + 1);
    ultimoTr.find('select:first').val('');

    $('#tabla_asignacion_designados tbody').append(ultimoTr);

  }

  function envarTraslado() {
    var bandera = false;

    var labels = [];
    let id = $('#id').val();
    let etapa = $('#etapa').val();
    let estado = $('#estado').val();
    let estado_etapa = $('#estado_etapa').val();
    let numero_informe = $('#numero_informe').val();
    let razon_social = $('#razon_social').val();
    let nit = $('#nit').val();
    let url = `/enviar_traslado`;

    let grupo_inspeccion = [];

    $('.tr_grupo_designados_traslado').each(function () {

        var row = {};
            $(this).find('select').each(function() {
                var key = $(this).attr('name');
                var value = $(this).val();
                row[key] = value;

                if (value === '') {
                    bandera = true;
                }
            });
            grupo_inspeccion.push(row);
    })
    
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var formData = new FormData();
    formData.append('id', id);
    formData.append('grupo_inspeccion', JSON.stringify(grupo_inspeccion) );
    formData.append('etapa', etapa);
    formData.append('estado', estado);
    formData.append('estado_etapa', estado_etapa);
    formData.append('numero_informe', numero_informe);
    formData.append('razon_social', razon_social);
    formData.append('nit', nit);

    var heads = {'X-CSRF-TOKEN': token}


    /*if (bandera) {
        var html = `<label>Todos los datos de la tabla de los usuarios a trasladar son necesarios</label`;

        Swal.fire({
          icon: "warning",
          title: "Atención",
          html: html,
        });

        return;
    }*/


    $('.required_informe_traslado').each(function() {
        if ($(this).val() === '') {
            var label = $('label[for="' + $(this).attr('id') + '"]').text().replace(' (*)','');

            labels.push(label);
            bandera = true;
        } else {
            formData.append($(this).attr('id'), $(this).val());
        }
    });

    if (bandera) {
        var html = `<label>Los siguientes datos son obligatorios:</label><br><ol type=”A”>`;
        for (var i = 0; i < labels.length; i++) {
            html += '<li>' + labels[i] + '</li>';
        }
        html += '</ol>';

        Swal.fire({
          icon: "warning",
          title: "Atención",
          html: html,
        });

        return;
    }

    $('.abrirVisitaInspeccion').prop('disabled', true);

    fetch(url,{
        method: 'POST',
        headers: heads,
        body: formData
    }).then(response => {
        if (!response.ok) {
          return response.json().then(data => {
            throw new Error(data.error || 'Error en la solicitud');
          });
        }
        return response.json();
    })
    .then(data => {
        const message = data.message;
        Swal.fire('Éxito!', message, 'success').then(()=>{
            location.reload();
        });
    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    }).finally(() => {
        $('.abrirVisitaInspeccion').prop('disabled', false);
    });
  }

  function enviarTrasladoEntidad() {
    var bandera = false;

    var labels = [];
    let id = $('#id').val();
    let etapa = $('#etapa').val();
    let estado = $('#estado').val();
    let estado_etapa = $('#estado_etapa').val();
    let numero_informe = $('#numero_informe').val();
    let razon_social = $('#razon_social').val();
    let nit = $('#nit').val();
    let url = `/informe_traslado_entidad`;
    
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var formData = new FormData();
    formData.append('id', id);
    formData.append('etapa', etapa);
    formData.append('estado', estado);
    formData.append('estado_etapa', estado_etapa);
    formData.append('numero_informe', numero_informe);
    formData.append('razon_social', razon_social);
    formData.append('nit', nit);

    var heads = {'X-CSRF-TOKEN': token}

    $('.required_informe_traslado_entidad').each(function() {
        if ($(this).val() === '') {
            var label = $('label[for="' + $(this).attr('id') + '"]').text().replace(' (*)','');

            labels.push(label);
            bandera = true;
        } else {
            formData.append($(this).attr('id'), $(this).val());
        }
    });

    if (bandera) {
        var html = `<label>Los siguientes datos son obligatorios:</label><br><ol type=”A”>`;
        for (var i = 0; i < labels.length; i++) {
            html += '<li>' + labels[i] + '</li>';
        }
        html += '</ol>';

        Swal.fire({
          icon: "warning",
          title: "Atención",
          html: html,
        });

        return;
    }

    $('.abrirVisitaInspeccion').prop('disabled', true);

    fetch(url,{
        method: 'POST',
        headers: heads,
        body: formData
    }).then(response => {
        if (!response.ok) {
          return response.json().then(data => {
            throw new Error(data.error || 'Error en la solicitud');
          });
        }
        return response.json();
    })
    .then(data => {
        const message = data.message;
        Swal.fire('Éxito!', message, 'success').then(()=>{
            location.reload();
        });
    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    }).finally(() => {
        $('.abrirVisitaInspeccion').prop('disabled', false);
    });
  }

  function registroPronunciamiento(){
     if ($('#confirmacion_pronunciacion_entidad').val() === "Si") {
        $('.div_radicado_entrada_pronunciacion').show();
     }else{
        $('.div_radicado_entrada_pronunciacion').hide();
     }
  }

  function registrarPronunciamientoEntidad() {
    var bandera = false;

    var labels = [];
    let id = $('#id').val();
    let etapa = $('#etapa').val();
    let estado = $('#estado').val();
    let estado_etapa = $('#estado_etapa').val();
    let numero_informe = $('#numero_informe').val();
    let razon_social = $('#razon_social').val();
    let nit = $('#nit').val();
    let url = `/registrar_pronunciamiento_entidad`;
    
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var formData = new FormData();
    formData.append('id', id);
    formData.append('etapa', etapa);
    formData.append('estado', estado);
    formData.append('estado_etapa', estado_etapa);
    formData.append('numero_informe', numero_informe);
    formData.append('razon_social', razon_social);
    formData.append('nit', nit);

    var heads = {'X-CSRF-TOKEN': token}

    $('.required_pronunciacion_entidad').each(function() {
        if ($(this).val() === '') {
            if (($(this).attr('id') === 'radicado_entrada_pronunciacion' && $('#confirmacion_pronunciacion_entidad').val() === 'Si')
                || $(this).attr('id') === 'confirmacion_pronunciacion_entidad' ) {
                var label = $('label[for="' + $(this).attr('id') + '"]').text().replace(' (*)','');

                labels.push(label);
                bandera = true;
            }else{
                formData.append($(this).attr('id'), $(this).val());
            }
        } else {
            formData.append($(this).attr('id'), $(this).val());
        }
    });

    if (bandera) {
        var html = `<label>Los siguientes datos son obligatorios:</label><br><ol type=”A”>`;
        for (var i = 0; i < labels.length; i++) {
            html += '<li>' + labels[i] + '</li>';
        }
        html += '</ol>';

        Swal.fire({
          icon: "warning",
          title: "Atención",
          html: html,
        });

        return;
    }

    $('.abrirVisitaInspeccion').prop('disabled', true);

    fetch(url,{
        method: 'POST',
        headers: heads,
        body: formData
    }).then(response => {
        if (!response.ok) {
          return response.json().then(data => {
            throw new Error(data.error || 'Error en la solicitud');
          });
        }
        return response.json();
    })
    .then(data => {
        const message = data.message;
        Swal.fire('Éxito!', message, 'success').then(()=>{
            location.reload();
        });
    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    }).finally(() => {
        $('.abrirVisitaInspeccion').prop('disabled', false);
    });
  }

  function registrarEvaluacionRespuesta() {
    var bandera = false;

    var labels = [];
    let id = $('#id').val();
    let etapa = $('#etapa').val();
    let estado = $('#estado').val();
    let estado_etapa = $('#estado_etapa').val();
    let numero_informe = $('#numero_informe').val();
    let razon_social = $('#razon_social').val();
    let nit = $('#nit').val();
    let url = `/registrar_valoracion_respuesta`;
    
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var formData = new FormData();
    formData.append('id', id);
    formData.append('etapa', etapa);
    formData.append('estado', estado);
    formData.append('estado_etapa', estado_etapa);
    formData.append('numero_informe', numero_informe);
    formData.append('razon_social', razon_social);
    formData.append('nit', nit);

    var heads = {'X-CSRF-TOKEN': token}

    $('.required_evaluacion_respuesta').each(function() {
        if ($(this).val() === '') {
            var label = $('label[for="' + $(this).attr('id') + '"]').text().replace(' (*)','');

            labels.push(label);
            bandera = true;
        } else {
            formData.append($(this).attr('id'), $(this).val());
        }
    });

    if (bandera) {
        var html = `<label>Los siguientes datos son obligatorios:</label><br><ol type=”A”>`;
        for (var i = 0; i < labels.length; i++) {
            html += '<li>' + labels[i] + '</li>';
        }
        html += '</ol>';

        Swal.fire({
          icon: "warning",
          title: "Atención",
          html: html,
        });

        return;
    }

    $('.registrarConfirmacionIntervencionInmediata').prop('disabled', true);

    fetch(url,{
        method: 'POST',
        headers: heads,
        body: formData
    }).then(response => {
        if (!response.ok) {
          return response.json().then(data => {
            throw new Error(data.error || 'Error en la solicitud');
          });
        }
        return response.json();
    })
    .then(data => {
        const message = data.message;
        Swal.fire('Éxito!', message, 'success').then(()=>{
            location.reload();
        });
    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    }).finally(() => {
        $('.registrarConfirmacionIntervencionInmediata').prop('disabled', false);
    });
  }

  function registrarInformeHallazgosFinales() {
    var bandera = false;

    var labels = [];
    let id = $('#id').val();
    let etapa = $('#etapa').val();
    let estado = $('#estado').val();
    let estado_etapa = $('#estado_etapa').val();
    let numero_informe = $('#numero_informe').val();
    let razon_social = $('#razon_social').val();
    let nit = $('#nit').val();
    let url = `/registrar_informe_hallazgos_finales`;
    
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var formData = new FormData();
    formData.append('id', id);
    formData.append('etapa', etapa);
    formData.append('estado', estado);
    formData.append('estado_etapa', estado_etapa);
    formData.append('numero_informe', numero_informe);
    formData.append('razon_social', razon_social);
    formData.append('nit', nit);

    var heads = {'X-CSRF-TOKEN': token}

    $('.required_informe_final_hallazgos').each(function() {
        if ($(this).val() === '') {
            var label = $('label[for="' + $(this).attr('id') + '"]').text().replace(' (*)','');

            labels.push(label);
            bandera = true;
        } else {
            formData.append($(this).attr('id'), $(this).val());
        }
    });

    if (bandera) {
        var html = `<label>Los siguientes datos son obligatorios:</label><br><ol type=”A”>`;
        for (var i = 0; i < labels.length; i++) {
            html += '<li>' + labels[i] + '</li>';
        }
        html += '</ol>';

        Swal.fire({
          icon: "warning",
          title: "Atención",
          html: html,
        });

        return;
    }

    $('.registrarConfirmacionIntervencionInmediata').prop('disabled', true);

    fetch(url,{
        method: 'POST',
        headers: heads,
        body: formData
    }).then(response => {
        if (!response.ok) {
          return response.json().then(data => {
            throw new Error(data.error || 'Error en la solicitud');
          });
        }
        return response.json();
    })
    .then(data => {
        const message = data.message;
        Swal.fire('Éxito!', message, 'success').then(()=>{
            location.reload();
        });
    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    }).finally(() => {
        $('.registrarConfirmacionIntervencionInmediata').prop('disabled', false);
    });
  }

  function proponerActuacionAdministrativa() {
    var bandera = false;

    var labels = [];
    let id = $('#id').val();
    let etapa = $('#etapa').val();
    let estado = $('#estado').val();
    let estado_etapa = $('#estado_etapa').val();
    let numero_informe = $('#numero_informe').val();
    let razon_social = $('#razon_social').val();
    let nit = $('#nit').val();
    let url = `/proponer_actuacion_administrativa`;
    
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var formData = new FormData();
    formData.append('id', id);
    formData.append('etapa', etapa);
    formData.append('estado', estado);
    formData.append('estado_etapa', estado_etapa);
    formData.append('numero_informe', numero_informe);
    formData.append('razon_social', razon_social);
    formData.append('nit', nit);

    var heads = {'X-CSRF-TOKEN': token}

    $('.required_actuacion_administrativa').each(function() {
        if ($(this).val() === '') {
            var label = $('label[for="' + $(this).attr('id') + '"]').text().replace(' (*)','');

            labels.push(label);
            bandera = true;
        } else {
            formData.append($(this).attr('id'), $(this).val());
        }
    });

    if (bandera) {
        var html = `<label>Los siguientes datos son obligatorios:</label><br><ol type=”A”>`;
        for (var i = 0; i < labels.length; i++) {
            html += '<li>' + labels[i] + '</li>';
        }
        html += '</ol>';

        Swal.fire({
          icon: "warning",
          title: "Atención",
          html: html,
        });

        return;
    }

    $('.registrarConfirmacionIntervencionInmediata').prop('disabled', true);

    fetch(url,{
        method: 'POST',
        headers: heads,
        body: formData
    }).then(response => {
        if (!response.ok) {
          return response.json().then(data => {
            throw new Error(data.error || 'Error en la solicitud');
          });
        }
        return response.json();
    })
    .then(data => {
        const message = data.message;
        Swal.fire('Éxito!', message, 'success').then(()=>{
            location.reload();
        });
    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    }).finally(() => {
        $('.registrarConfirmacionIntervencionInmediata').prop('disabled', false);
    });
  }

  function anadirInspectorModificar() {

    var ultimoTr = $('#tabla_modificacion_grupo_inspeccion tbody tr:last').clone();

    var ultimoNumero = parseInt($('#tabla_modificacion_grupo_inspeccion tbody tr:last td:first p').text());
    ultimoTr.find('p').text(ultimoNumero + 1);
    ultimoTr.find('select:first').val('');

    $('#tabla_modificacion_grupo_inspeccion tbody').append(ultimoTr);

  }

  function modificarGrupoInspeccion() {
    var bandera = false;

    var labels = [];
    let id = $('#id').val();
    let etapa = $('#etapa').val();
    let estado = $('#estado').val();
    let estado_etapa = $('#estado_etapa').val();
    let numero_informe = $('#numero_informe').val();
    let razon_social = $('#razon_social').val();
    let observaciones_modificar_grupo = $('#observaciones_modificar_grupo').val();
    let nit = $('#nit').val();
    let url = `/modificar_grupo_inspeccion`;

    let grupo_inspeccion = [];

    $('.tr_grupo_inspeccion_modificar').each(function () {

        var row = {};
            $(this).find('select').each(function() {
                var key = $(this).attr('name');
                var value = $(this).val();
                row[key] = value;

                if (value === '') {
                    bandera = true;
                }
            });
            grupo_inspeccion.push(row);
    })
    
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var formData = new FormData();
    formData.append('id', id);
    formData.append('grupo_inspeccion', JSON.stringify(grupo_inspeccion) );
    formData.append('etapa', etapa);
    formData.append('estado', estado);
    formData.append('estado_etapa', estado_etapa);
    formData.append('numero_informe', numero_informe);
    formData.append('razon_social', razon_social);
    formData.append('nit', nit);
    formData.append('observaciones', observaciones_modificar_grupo);

    var heads = {'X-CSRF-TOKEN': token}

    if (bandera) {
        var html = `<label>Todos los datos de la tabla del grupo de inspección deben ser diligenciados</label`;

        Swal.fire({
          icon: "warning",
          title: "Atención",
          html: html,
        });

        return;
    }

    if (bandera) {
        var html = `<label>Los siguientes datos son obligatorios:</label><br><ol type=”A”>`;
        for (var i = 0; i < labels.length; i++) {
            html += '<li>' + labels[i] + '</li>';
        }
        html += '</ol>';

        Swal.fire({
          icon: "warning",
          title: "Atención",
          html: html,
        });

        return;
    }

    $('.modificarGrupoVisita').prop('disabled', true);

    fetch(url,{
        method: 'POST',
        headers: heads,
        body: formData
    }).then(response => {
        if (!response.ok) {
          return response.json().then(data => {
            throw new Error(data.error || 'Error en la solicitud');
          });
        }
        return response.json();
    })
    .then(data => {
        const message = data.message;
        Swal.fire('Éxito!', message, 'success').then(()=>{
            location.reload();
        });
    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    }).finally(() => {
        $('.modificarGrupoVisita').prop('disabled', false);
    });
  }

  function anadirRegistro(tabla) {

    var ultimoTr = $( `#${tabla} tbody tr:last`).clone();

    var ultimoNumero = parseInt($(`#${tabla} tbody tr:last td:first p`).text());
    ultimoTr.find('p').text(ultimoNumero + 1);
    ultimoTr.find('input[type="text"]').val('');
    ultimoTr.find('input[type="file"]').val('');

    $(`#${tabla} tbody`).append(ultimoTr);

  }

  function contenidosFinalesExpediente() {
    var bandera = false;

    let id = $('#id').val();
    let etapa = $('#etapa').val();
    let estado = $('#estado').val();
    let estado_etapa = $('#estado_etapa').val();
    let numero_informe = $('#numero_informe').val();
    let razon_social = $('#razon_social').val();
    let nit = $('#nit').val();
    let url = `/contenidos_finales_expedientes`;

    let ciclos_vida = [];
    let documentos_finales = [];

    $('.tr_ciclo_expediente_final').each(function () {

        var row = {};
            $(this).find('input[type="text"]').each(function() {
                var key = $(this).attr('name');
                var value = $(this).val();
                row[key] = value;

                if (value === '') {
                    bandera = true;
                }
            });
            ciclos_vida.push(row);
    })

    $('.tr_documentos_finales').each(function () {

        var row = {};
            $(this).find('input[type="text"]').each(function() {
                var key = $(this).attr('name');
                var value = $(this).val();
                row[key] = value;

                if (value === '') {
                    bandera = true;
                }
            });
            documentos_finales.push(row);
    })

    if (bandera || documentos_finales.length < 1) {
        var html = `<label>Todos los datos de las tablas deben ser diligenciados</label>`;

        Swal.fire({
          icon: "warning",
          title: "Atención",
          html: html,
        });

        return;
    }
    
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var formData = new FormData();
    formData.append('id', id);
    formData.append('ciclos_vida', JSON.stringify(ciclos_vida) );
    formData.append('documentos_finales', JSON.stringify(documentos_finales) );
    formData.append('etapa', etapa);
    formData.append('estado', estado);
    formData.append('estado_etapa', estado_etapa);
    formData.append('numero_informe', numero_informe);
    formData.append('razon_social', razon_social);
    formData.append('nit', nit);

    var heads = {'X-CSRF-TOKEN': token}

    $('.botonEnviar').prop('disabled', true);

    fetch(url,{
        method: 'POST',
        headers: heads,
        body: formData
    }).then(response => {
        if (!response.ok) {
          return response.json().then(data => {
            throw new Error(data.error || 'Error en la solicitud');
          });
        }
        return response.json();
    })
    .then(data => {
        const message = data.message;
        Swal.fire('Éxito!', message, 'success').then(()=>{
            location.reload();
        });
    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    }).finally(() => {
        $('.botonEnviar').prop('disabled', false);
    });
  }

  function generarTablero() {

    let id = $('#id').val();
    let etapa = $('#etapa').val();
    let estado = $('#estado').val();
    let estado_etapa = $('#estado_etapa').val();
    let numero_informe = $('#numero_informe').val();
    let razon_social = $('#razon_social').val();
    let nit = $('#nit').val();
    let url = `/generar_tablero`;

    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var formData = new FormData();
    formData.append('id', id);
    formData.append('etapa', etapa);
    formData.append('estado', estado);
    formData.append('estado_etapa', estado_etapa);
    formData.append('numero_informe', numero_informe);
    formData.append('razon_social', razon_social);
    formData.append('nit', nit);

    var heads = {'X-CSRF-TOKEN': token}

    $('.botonEnviar').prop('disabled', true);

    fetch(url,{
        method: 'POST',
        headers: heads,
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(data => {
                throw new Error(data.error || 'Error en la solicitud');
            });
        }
        return response.blob();
    })
    .then(blob => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'tablero.xlsx';
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
    })
    .catch(error => {
        console.error('Error:', error);
    }).finally(() => {
        location.reload();
    });
  }

  function estadisticas() {

    let url = `/estadisticas_datos`;
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var heads = {'X-CSRF-TOKEN': token};
    let etapasContadas = {};
    let etapasSinRepetir = [];

    let estadosContados = {};
    let estadosSinRepetir = [];

    let estadosEtapasContados = {};
    let estadosEtapaSinRepetir = [];

    fetch(url,{
        method: 'POST',
        headers: heads,
    }).then(response => {
        if (!response.ok) {
          return response.json().then(data => {
            throw new Error(data.error || 'Error en la solicitud');
          });
        }
        return response.json();
    })
    .then(data => {
        const message = data.datos;
        const conteo_dias = data.conteo_dias;

        message.forEach(dato => {
            let etapa = dato.etapa;
            let estado = dato.estado_informe;
            let estado_etapa = dato.estado_etapa;

            if (etapasContadas.hasOwnProperty(etapa)) {
                etapasContadas[etapa]++;
            } else {
                etapasContadas[etapa] = 1;
            }

            if (estadosContados.hasOwnProperty(estado)) {
                estadosContados[estado]++;
            } else {
                estadosContados[estado] = 1;
            }

            if (estadosEtapasContados.hasOwnProperty(estado_etapa)) {
                estadosEtapasContados[estado_etapa]++;
            } else {
                estadosEtapasContados[estado_etapa] = 1;
            }
        });

        etapasSinRepetir = Object.keys(etapasContadas);
        const soloNumeros = Object.values(etapasContadas);
        const visitas_etapa = document.getElementById('visitas_etapa');

        new Chart(visitas_etapa, {
            type: 'bar',
            plugins: [ChartDataLabels],
            data: {
                labels: etapasSinRepetir,
                datasets: [{
                    label: 'Visitas de inspección',
                    data: soloNumeros,
                    borderWidth: 1,
                    backgroundColor: [
                        'rgb(255, 186, 125)',
                        'rgb(255, 235, 125)',
                        'rgb(233, 255, 125)',
                        'rgb(172, 255, 125)',
                        'rgb(125, 255, 164)',
                        'rgb(125, 255, 200)',
                        'rgb(125, 220, 255)',
                        'rgb(149, 125, 255)',
                    ],
                }]
            },
            options: {
                plugins: {
                    datalabels: {
                      color: '#FFFFFF'
                    }
                },
                scales: {
                    y: {
                    beginAtZero: true
                    }
                }
            }
        });

        const visitas_estado = document.getElementById('visitas_estado');
        estadosSinRepetir = Object.keys(estadosContados);
        const soloNumerosEstados = Object.values(estadosContados);

        new Chart(visitas_estado, {
            type: 'pie',
            plugins: [ChartDataLabels],
            data: {
                labels: estadosSinRepetir,
                datasets: [{
                    label: 'Estado de la visita de inspección',
                    data: soloNumerosEstados,
                    borderWidth: 1,
                    backgroundColor: [
                        'rgb(39, 108, 255)',
                        'rgb(62, 255, 39)',
                        'rgb(255, 137, 39)',
                    ],
                }]
            },
            options: {
                plugins: {
                    datalabels: {
                      color: '#FFFFFF'
                    }
                },
                scales: {
                    y: {
                    beginAtZero: true
                    }
                }
            }
        });

        const visitas_estado_etapa = document.getElementById('visitas_estado_etapa');
        estadosEtapaSinRepetir = Object.keys(estadosEtapasContados);
        const soloNumerosEstadosEtapas = Object.values(estadosEtapasContados);

        new Chart(visitas_estado_etapa, {
            type: 'doughnut',
            plugins: [ChartDataLabels],
            data: {
                labels: estadosEtapaSinRepetir,
                datasets: [{
                    label: 'Estado de la etapa de la visita de inspección',
                    data: soloNumerosEstadosEtapas,
                    borderWidth: 1,
                    backgroundColor: [
                        'rgb(255, 2, 2 )',
                        'rgb(2, 79, 255 )',
                        'rgb(25, 255, 2 )',
                    ],
                }],
                
            },
            options: {
                plugins: {
                    datalabels: {
                      color: '#FFFFFF'
                    }
                },

                scales: {
                    y: {
                    beginAtZero: true
                    }
                }
            }
        });

        const etapasConteoDias = conteo_dias.map(objeto => objeto.etapa);
        const promediosConteoDias = conteo_dias.map(objeto => objeto.promedio_conteo_dias);

        console.log('etapasConteoDias', etapasConteoDias, 'promediosConteoDias', promediosConteoDias);
        const dias_por_estado = document.getElementById('dias_por_estado');

        new Chart(dias_por_estado, {
            type: 'bar',
            plugins: [ChartDataLabels],
            data: {
                labels: etapasConteoDias,
                datasets: [{
                    label: 'Días habiles',
                    data: promediosConteoDias,
                    borderWidth: 1,
                    backgroundColor: [
                        'rgb(149, 125, 255)',
                    ],
                }]
            },
            options: {
                indexAxis: 'y',
                plugins: {
                    datalabels: {
                      color: '#FFFFFF'
                    }
                },
                scales: {
                    y: {
                    beginAtZero: true
                    }
                },
                animations: {
                    tension: {
                      duration: 1000,
                      easing: 'linear',
                      from: 1,
                      to: 0,
                      loop: true
                    }
                },
            }
        });
    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    })

    
  }

  function crearDiaNoLaboral() {
    var bandera = false;

    var labels = [];

    let url = `/crear_dia_no_laboral`;
    
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var formData = new FormData();

    var heads = {'X-CSRF-TOKEN': token}

    $('.required_crear').each(function() {
        if ($(this).val() === '') {
            if ($(this).attr('id') !== 'descripcion_dia') {
                var label = $('label[for="' + $(this).attr('id') + '"]').text().replace(' (*)','');

                labels.push(label);
                bandera = true;
            }else{
                formData.append($(this).attr('id'), $(this).val());
            }
        } else {
            formData.append($(this).attr('id'), $(this).val());
        }
    });

    if (bandera) {
        var html = `<label>Los siguientes datos son obligatorios:</label><br><ol type=”A”>`;
        for (var i = 0; i < labels.length; i++) {
            html += '<li>' + labels[i] + '</li>';
        }
        html += '</ol>';

        Swal.fire({
          icon: "warning",
          title: "Atención",
          html: html,
        });

        return;
    }

    $('.botonEnviar').prop('disabled', true);

    fetch(url,{
        method: 'POST',
        headers: heads,
        body: formData
    }).then(response => {
        if (!response.ok) {
          return response.json().then(data => {
            throw new Error(data.error || 'Error en la solicitud');
          });
        }
        return response.json();
    })
    .then(data => {
        const message = data.message;
        Swal.fire('Éxito!', message, 'success').then(()=>{
            location.reload();
        });
    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    }).finally(() => {
        $('.botonEnviar').prop('disabled', false);
    });
  }

  function abrirModalEditarDia(id_dia = '', descripcion_dia ='', dia = '') {
    $('#exampleModal').modal('show');

    $('#id').val(id_dia);
    $('#dia_no_laboral_edit').val(dia);
    $('#descripcion_dia_edit').val(descripcion_dia);
}

function editarDia() {
    let id = $('#id').val();
    let fecha = $('#dia_no_laboral_edit').val();
    let descripcion = $('#descripcion_dia_edit').val();

    if (id === '' || fecha === '' || descripcion === '') {
        Swal.fire({
            title: 'Error',
            text: `Todos lo campos son obligatorios`,
            icon: 'error',
            showCancelButton: true,
            timer: 3000
        });
        return;
    }

    Swal.fire({
        title: '¿Estás seguro?',
        text: `Se actualizara el día ${descripcion} - ${fecha}`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí',
        cancelButtonText: 'No'
    }).then((result) => {

        if (result.isConfirmed) {

            var url = `/actualizar_dia/${id}`;
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            
            const data = {
                id: id,
                dia: fecha,
                descripcion: descripcion
            };

            fetch(url, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token 
                },
                body: JSON.stringify(data)
            }).then(response => {
                if (!response.ok) {
                  return response.json().then(data => {
                    throw new Error(data.error || 'Error en la solicitud');
                  });
                }
                return response.json();
              })
              .then(data => {
                const message = data.message;
                Swal.fire('Éxito!', message, 'success').then(()=>{
                    location.reload();
                })
              })
              .catch(error => {
                Swal.fire('Error', error.message, 'error');
              })
        }
    });
}

function eliminarDia(id_usuario = '', nombre_usuario = ''){

    Swal.fire({
        title: '¿Estás seguro?',
        text: `¿Quieres eliminar el día ${nombre_usuario}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí',
        cancelButtonText: 'No'
    }).then((result) => {

        if (result.isConfirmed) {

            var url = `/eliminar_dia/${id_usuario}`;
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            
            const formData = new FormData();
            formData.append('id', id_usuario);

            fetch(url,{
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token 
                },
            }).then(response => {
                if (!response.ok) {
                    throw new Error('Error al eliminar día');
                }
                Swal.fire('Eliminado', 'El día ha sido eliminado correctamente', 'success').then(()=>{
                    location.reload();
                })
                
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'Hubo un problema al eliminar el día', 'error');
            });
        }
    });
}

function generarTableroMasivo() {

    event.preventDefault();

    let fecha_inicial = $('#fecha_inicial').val();
    let numero_informe = $('#numero_informe').val();
    let estado_etapa = $('#estado_etapa').val();
    let usuario_actual = $('#usuario_actual').val();
    let nit_entidad = $('#nit_entidad').val();
    let nombre_entidad = $('#nombre_entidad').val();
    let estado_informe = $('#estado_informe').val();
    let etapa_actual = $('#etapa_actual').val();
    let fecha_final = $('#fecha_final').val();
    let url = `/generar_tablero_masivo`;

    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var formData = new FormData();
    formData.append('fecha_inicial', fecha_inicial);
    formData.append('numero_informe', numero_informe);
    formData.append('estado_etapa', estado_etapa);
    formData.append('usuario_actual', usuario_actual);
    formData.append('nit_entidad', nit_entidad);
    formData.append('nombre_entidad', nombre_entidad);
    formData.append('estado_informe', estado_informe);
    formData.append('etapa_actual', etapa_actual);
    formData.append('fecha_final', fecha_final);

    var heads = {'X-CSRF-TOKEN': token}

    $('.botonEnviar').prop('disabled', true);

    fetch(url,{
        method: 'POST',
        headers: heads,
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(data => {
                throw new Error(data.error || 'Error en la solicitud');
            });
        }
        return response.blob();
    })
    .then(blob => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'tablero.xlsx';
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
    })
    .catch(error => {
        console.error('Error:', error);
    })
  }

function tipo_revisor_fiscal() {
    let tipo_revisor_fiscal = $('#tipo_revisor_fiscal').val();

    if (tipo_revisor_fiscal === 'PERSONA NATURAL') {
        $('.razon_social_revision_fiscal').show();
    }else{
        $('.razon_social_revision_fiscal').hide();
    }
}

function tipo_organizacion() {
    let tipo_organizacion = $('#tipo_organizacion').val();

    if (tipo_organizacion === 'FONDOS DE EMPLEADOS') {
        $('#divCategoria').show();
    }else{
        $('#divCategoria').hide();
    }
}

function uploadFile() {
    let url = `/importar_entidades`;
    var formData = new FormData();
    formData.append('archivo_entidades', document.getElementById('archivo_entidades').files[0]);

    fetch(url, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        const message = data.message;
        const errors = data.errors;

        if (message) {
            Swal.fire('Éxito!', message, 'success').then(()=>{
                location.reload();
            });
        } else if (errors) {
            let errorMessages = '';
            for (const [codigo, errorList] of Object.entries(errors)) {
                errorMessages += `Código ${codigo}:\n${errorList.join('\n')}\n\n`;
            }
            Swal.fire('Errores de importación', errorMessages, 'error');
        } else {
            Swal.fire('Error', data.error, 'error');
        }
    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    }).finally(() => {
        $('.botonEnviar').prop('disabled', false);
    });
}

function suspender_visita() {
    var bandera = false;

    let id = $('#id').val();
    let observaciones = $('#observaciones_suspencion').val();
    let etapa = $('#etapa').val();
    let estado = $('#estado').val();
    let estado_etapa = $('#estado_etapa').val();
    let numero_informe = $('#numero_informe').val();
    let razon_social = $('#razon_social').val();
    let nit = $('#nit').val();
    let url = `/suspender_visita`;
    
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var formData = new FormData();
    formData.append('id', id);
    formData.append('observaciones', observaciones);
    formData.append('etapa', etapa);
    formData.append('estado', estado);
    formData.append('estado_etapa', estado_etapa);
    formData.append('numero_informe', numero_informe);
    formData.append('razon_social', razon_social);
    formData.append('nit', nit);

    var heads = {'X-CSRF-TOKEN': token}

    if (observaciones === '') {
        bandera = true;
    }

    if (bandera) {
        var html = `<label>Los siguientes datos son obligatorios:</label><br><ol type=”A”>`;
        html += '<li>Motivo de la suspensión</li>';
        html += '</ol>';

        Swal.fire({
          icon: "warning",
          title: "Atención",
          html: html,
        });

        return;
    }

    $('.enviarObservacion').prop('disabled', true);

    fetch(url,{
        method: 'POST',
        headers: heads,
        body: formData
    }).then(response => {
        if (!response.ok) {
          return response.json().then(data => {
            throw new Error(data.error || 'Error en la solicitud');
          });
        }
        return response.json();
    })
    .then(data => {
        const message = data.message;
        Swal.fire('Éxito!', message, 'success').then(()=>{
            location.reload();
        });
    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    }).finally(() => {
        $('.enviarObservacion').prop('disabled', false);
    });
}

function reanudar_visita() {

    let id = $('#id').val();
    let observaciones = $('#observaciones_reanudacion').val();
    let etapa = $('#etapa').val();
    let estado = $('#estado').val();
    let estado_etapa = $('#estado_etapa').val();
    let numero_informe = $('#numero_informe').val();
    let razon_social = $('#razon_social').val();
    let nit = $('#nit').val();
    let url = `/reanudar_visita`;
    
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var formData = new FormData();
    formData.append('id', id);
    formData.append('observaciones', observaciones);
    formData.append('etapa', etapa);
    formData.append('estado', estado);
    formData.append('estado_etapa', estado_etapa);
    formData.append('numero_informe', numero_informe);
    formData.append('razon_social', razon_social);
    formData.append('nit', nit);

    var heads = {'X-CSRF-TOKEN': token}

    $('.enviarObservacion').prop('disabled', true);

    fetch(url,{
        method: 'POST',
        headers: heads,
        body: formData
    }).then(response => {
        if (!response.ok) {
          return response.json().then(data => {
            throw new Error(data.error || 'Error en la solicitud');
          });
        }
        return response.json();
    })
    .then(data => {
        const message = data.message;
        Swal.fire('Éxito!', message, 'success').then(()=>{
            location.reload();
        });
    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    }).finally(() => {
        $('.enviarObservacion').prop('disabled', false);
    });
}

function productoGeneradoSubsanacion(){
    var producto = $('#producto_generado_subsanacion').val();

    if (producto === 'GRABACIÓN') {
        $('#div_enlace_grabacion').show();
        $('#div_tabla_adicionales_subsanacion_diagnostico').hide();
    }else if(producto === 'DOCUMENTO(S)'){
        $('#div_tabla_adicionales_subsanacion_diagnostico').show();
        $('#div_enlace_grabacion').hide();
    }else if(producto === 'AMBOS'){
        $('#div_enlace_grabacion').show();
        $('#div_tabla_adicionales_subsanacion_diagnostico').show();
    }else{
        $('#div_enlace_grabacion').hide();
        $('#div_tabla_adicionales_subsanacion_diagnostico').hide();
    }
}

function eliminarAnexo(button, url_archivo, nombre_archivo) {
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    let id = $('#id').val();
    let numero_informe = $('#numero_informe').val();
    let etapa = $('#etapa').val();
    let estado = $('#estado').val();
    let estado_etapa = $('#estado_etapa').val();
    let razon_social = $('#razon_social').val();
    let url = `/eliminar_archivo`;

    var fila = $(button).closest('tr');
    var heads = {'X-CSRF-TOKEN': token}
    var id_archivo = url_archivo.replace('https://drive.google.com/file/d/', '').replace('/view', '');

    var formData = new FormData();
    formData.append('id', id);
    formData.append('numero_informe', numero_informe);
    formData.append('razon_social', razon_social);
    formData.append('nombre_archivo', nombre_archivo);
    formData.append('id_archivo', id_archivo);
    formData.append('etapa', etapa);
    formData.append('estado', estado);
    formData.append('estado_etapa', estado_etapa);

    Swal.fire({
        title: "¿Desea eliminar el anexo "+nombre_archivo+"?",
        text: "No se podra recuperar el anexo",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Si, eliminar"
      }).then((result) => {
        if (result.isConfirmed) {
            fetch(url,{
                method: 'POST',
                headers: heads,
                body: formData
            }).then(response => {
                if (!response.ok) {
                  return response.json().then(data => {
                    throw new Error(data.error || 'Error en la solicitud');
                  });
                }
                return response.json();
            })
            .then(data => {
                const message = data.message;
                Swal.fire('Éxito!', message, 'success').then(()=>{
                    fila.remove();
                });
            })
            .catch(error => {
                Swal.fire('Error', error.message, 'error');
            }).finally(() => {
                $('.enviarObservacion').prop('disabled', false);
            });
        }
      });

}

function planVisitaModificado() {
    
    var bandera = false;

    var labels = [];
    var anexos_plan_visita = [];
    let etapa = $('#etapa').val();
    let estado = $('#estado').val();
    let estado_etapa = $('#estado_etapa').val();
    let numero_informe = $('#numero_informe').val();
    let razon_social = $('#razon_social').val();
    let nit = $('#nit').val();
    let observacion = $('#observaciones_envio_plan_visita_modificado').val();

    var url = `/guardar_plan_visita_modificado`;
    let id = $('#id').val();
    
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var formData = new FormData();

    formData.append('id', id);
    formData.append('etapa', etapa);
    formData.append('estado', estado);
    formData.append('estado_etapa', estado_etapa);
    formData.append('numero_informe', numero_informe);
    formData.append('razon_social', razon_social);
    formData.append('nit', nit);
    formData.append('observacion', observacion);

    var heads = {'X-CSRF-TOKEN': token};

    var fileInput = document.getElementById('enlace_plan_visita_modificado');
    var file = fileInput.files[0];
    if (file) {
        formData.append('enlace_plan_visita', file);
    }

    $('.required_plan_visita_modificado').each(function() {
        if ($(this).val() === '') {
            var label = $('label[for="' + $(this).attr('id') + '"]').text().replace(' (*)','');
            labels.push(label);
            bandera = true;
        } else {
            formData.append($(this).attr('id'), $(this).val());
        }
    });

    $('.tr_documentos_modificado_adicionales_plan_visita').each(function () {

        var row = {};
        var banderaText = false;
        var banderaFile = false;

            $(this).find('input[type="text"]').each(function() {
                var key = $(this).attr('name');
                var valueText = $(this).val();
                if (valueText !=  '') {
                    row[key] = valueText; 
                    banderaText = true;
                    //labels.push('Nombre del archivo');
                }
            });

            $(this).find('input[type="file"]').each(function() {
                var key = $(this).attr('name');
                var valuefile = this.files[0];
                if (valuefile !=  undefined) {
                    row[key] = valuefile; 
                    banderaFile=true;
                    //labels.push('Adjunto');
                }
            });

            if (banderaText && banderaFile) {
                anexos_plan_visita.push(row);
            }
    })

    if (bandera) {
        var html = `<label>Los siguientes datos son obligatorios:</label><br><ol type=”A”>`;
        for (var i = 0; i < labels.length; i++) {
            html += '<li>' + labels[i] + '</li>';
        }
        html += '</ol>';

        Swal.fire({
            icon: "warning",
            title: "Atención",
            html: html,
        });

        return;
    }

    if (anexos_plan_visita.length > 0 ) {
        anexos_plan_visita.forEach((item, index) => {
            for (var key in item) {
                if (item.hasOwnProperty(key)) {
                    if (Array.isArray(item[key])) {
                        item[key].forEach((file, fileIndex) => {
                            formData.append(`${key}[${index}][${fileIndex}]`, file);
                        });
                    } else {
                        formData.append(`${key}[${index}]`, item[key]);
                    }
                }
            }
        });
    }

    $('.diagnosticoSubsanado').prop('disabled', true);

    fetch(url, {
        method: 'POST',
        headers: heads,
        body: formData
    }).then(response => {
        if (!response.ok) {
            return response.json().then(data => {
                throw new Error(data.error || 'Error en la solicitud');
            });
        }
        return response.json();
    })
    .then(data => {
        const message = data.message;
        Swal.fire('Éxito!', message, 'success').then(() => {
            location.reload();
        });
    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    }).finally(() => {
        $('.diagnosticoSubsanado').prop('disabled', false);
    });
}