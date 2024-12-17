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
                $(this).attr('id') !== 'incluye_sarlaft' &&
                $(this).attr('id') !== 'razon_social_revision_fiscal') {
    
                var label = $('label[for="' + $(this).attr('id') + '"]').text().replace(' (*)','');
                labels.push(label);
                bandera = true;
            } else {
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
            //location.reload();
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

async function parametrosCreacionDiagnostico() {
    let fechaActual = new Date().toISOString().split('T')[0];
    $('#fecha_inicio_diagnostico').val(fechaActual);

    const dias_diagnostico = $('#dias_diagnostico').val();

    let fecha_fin = await sumarDiasHabiles(fechaActual, parseInt(dias_diagnostico));

    $('#fecha_fin_diagnostico').val(fecha_fin);
}

const diasFestivosColombia = [
    '2024-07-10', 
    '2024-07-19', 
    '2024-09-20', 
];

async function esDiaHabilColombia(fecha) {
    const dia = fecha.getDay();
    return dia !== 0 && dia !== 6 && !(await esDiaFestivoColombia(fecha));
}
  
/*function esDiaHabilColombia(fecha) {
    const dia = fecha.getDay();
    
    return dia !== 0 && dia !== 6 && !esDiaFestivoColombia(fecha);
}*/

async function esDiaFestivoColombia(fecha) {
    const fechaString = fecha.toISOString().split('T')[0];
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const heads = { 'X-CSRF-TOKEN': token };

    try {
        const response = await fetch(`/consultar_dias_no_laborales`, {
            method: 'POST',
            headers: heads,
        });

        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.error || 'Error en la solicitud');
        }

        const data = await response.json();
        const message = data.message; // Arreglo con los días festivos
        return message.includes(fechaString);
    } catch (error) {
        Swal.fire('Error', error.message, 'error');
        return false; // En caso de error, consideramos que no es festivo
    }
}
  
/*function esDiaFestivoColombia(fecha) {
    const fechaString = fecha.toISOString().split('T')[0];
    var diasFestivosColombiaBase = [];

    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    var heads = {'X-CSRF-TOKEN': token}

    fetch(`/consultar_dias_no_laborales`,{
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
        const message = data.message;
        diasFestivosColombiaBase.push(message);

        console.log('fechaString',fechaString);
        console.log('include',message.includes(fechaString));
        console.log('diasFestivosColombiaBase', diasFestivosColombiaBase);
        console.log('message', message);

        return message.includes(fechaString);
    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    }).finally(() => {
        $('.revisionDiagnostico').prop('disabled', false);
    });
    
    
}*/
  
/*function sumarDiasHabiles(fechaString, dias) {
    const fecha = new Date(fechaString);    
    
    let contador = 0;
    while (contador < (dias+1)) {
      fecha.setDate(fecha.getDate() + 1);
        console.log('es dia habil colombia', esDiaHabilColombia(fecha));
        
      if (esDiaHabilColombia(fecha)) {
        contador++;
        console.log('fecha',fecha);
        
      }
    }
    const year = fecha.getFullYear();
    const month = ('0' + (fecha.getMonth() + 1)).slice(-2);
    const day = ('0' + fecha.getDate()).slice(-2);
    return `${year}-${month}-${day}`;
}*/

async function sumarDiasHabiles(fechaString, dias) {
    const fecha = new Date(fechaString);    
    let contador = 0;

    while (contador < (dias + 1)) {
        fecha.setDate(fecha.getDate() + 1);

        if (await esDiaHabilColombia(fecha)) {
            contador++;
        }
    }

    const year = fecha.getFullYear();
    const month = ('0' + (fecha.getMonth() + 1)).slice(-2);
    const day = ('0' + fecha.getDate()).slice(-2);
    return `${year}-${month}-${day}`;
}

/*function diasHabilesDiagnostico() {
    let fecha_inicio_diagnostico = $('#fecha_inicio_diagnostico').val();

    const dias_diagnostico = $('#dias_diagnostico').val();

    let fecha_fin = sumarDiasHabiles(fecha_inicio_diagnostico, parseInt(dias_diagnostico));
    $('#fecha_fin_diagnostico').val(fecha_fin);
}*/

async function diasHabilesDiagnostico() {
    let fecha_inicio_diagnostico = $('#fecha_inicio_diagnostico').val();
    const dias_diagnostico = $('#dias_diagnostico').val();

    let fecha_fin = await sumarDiasHabiles(fecha_inicio_diagnostico, parseInt(dias_diagnostico));
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
                $(this).attr('id') !== 'razon_social_revision_fiscal' &&
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
    let ciclo_devolucion_documento_diagnostico = $('#ciclo_devolucion_documento_diagnostico').val();
    let observaciones_documento_diagnostico = $('#observaciones_documento_diagnostico').val();
    let etapa = $('#etapa').val();
    let estado = $('#estado').val();
    let estado_etapa = $('#estado_etapa').val();
    let numero_informe = $('#numero_informe').val();
    let razon_social = $('#razon_social').val();
    let nit = $('#nit').val();

    var url = `/guardar_revision_diagnostico`;
    let id = $('#id').val();

    if (resultado_revision === '') {
        labels.push('¿El documento diagnóstico cumple con los criterios suficientes para elaborar el plan de la visita?');
        bandera = true;
    }

    if (resultado_revision === 'No' && ciclo_devolucion_documento_diagnostico === '') {
        labels.push('Fecha y hora en que se socializará el diagnóstico');
        bandera = true;
    }

    if (resultado_revision === 'No' && observaciones_documento_diagnostico === '') {
        labels.push('Observaciones');
        bandera = true;
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

    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var formData = new FormData();

    formData.append('id', id);
    formData.append('etapa', etapa);
    formData.append('estado', estado);
    formData.append('estado_etapa', estado_etapa);
    formData.append('numero_informe', numero_informe);
    formData.append('razon_social', razon_social);
    formData.append('nit', nit);
    formData.append('resultado_revision', resultado_revision);
    formData.append('ciclo_devolucion_documento_diagnostico', ciclo_devolucion_documento_diagnostico);
    formData.append('observaciones_documento_diagnostico', observaciones_documento_diagnostico);

    var heads = {'X-CSRF-TOKEN': token}

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

function finalizarSocializarVisita() {

    validarSesionDrive();

    var bandera = false;

    let id = $('#id').val();
    let enlace_grabacion_socializacion = $('#enlace_grabacion_socializacion').val().trim();
    let etapa = $('#etapa').val();
    let estado = $('#estado').val();
    let estado_etapa = $('#estado_etapa').val();
    let numero_informe = $('#numero_informe').val();
    let razon_social = $('#razon_social').val();
    let nit = $('#nit').val();
    let url = `/finalizar_socializar_visita`;
    var producto = $('#producto_generado_socializacion').val();
    var observaciones = $('#observaciones_socializacion_visita').val();

    let anexos_socializacion_visita = [];
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
    formData.append('observaciones', observaciones);
    formData.append('producto_generado_socializacion', producto);

    formData.append('enlace_grabacion_socializacion', enlace_grabacion_socializacion);

    var heads = {'X-CSRF-TOKEN': token}

    if (producto === '') {
        bandera = true;
        labels.push('Producto generado de la socialización');
    }
    
    if ((producto === 'GRABACIÓN' || producto === 'AMBOS') && enlace_grabacion_socializacion === '') {
        bandera = true;
        labels.push('Enlace de la grabación');
    }

    if ((producto === 'DOCUMENTO(S)' || producto === 'AMBOS')) {
        // Agregar el archivo seleccionado al FormData
        var fileInput = document.getElementById('acta_asistencia_socializacion');
        var file = fileInput.files[0];
        if (file) {
            formData.append('acta_asistencia_socializacion', file);
        } else {
            labels.push('Acta de asistencia a la reunión');
            bandera = true;
        }
    }

    $('.tr_documentos_socializacion_visita').each(function () {

        var row = {};
        var banderaText = false;
        var banderaFile = false;

            $(this).find('input[type="text"]').each(function() {
                var key = $(this).attr('name');
                var valueText = $(this).val().trim();
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
                anexos_socializacion_visita.push(row);
            }else if (banderaText && !banderaFile) {
                bandera = true;
                labels.push('Todos los anexos deben tener un documento');
            }else if (!banderaText && banderaFile) {
                bandera = true;
                labels.push('Todos los anexos deben tener un nombre');
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

    if (anexos_socializacion_visita.length > 0 ) {
        anexos_socializacion_visita.forEach((item, index) => {
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
    let id_entidad = $('#id_entidad').val();

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
    formData.append('id_entidad', id_entidad);

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
    let anexos_adicionales = [];
    let observaciones = $('#observaciones_respuesta_informacion_previa').val();

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
    formData.append('observaciones', observaciones);

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

    $('.tr_documentos_adicionales_respuesta_informacion_adicional').each(function () {

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
                anexos_adicionales.push(row);
            }

            if ( (!banderaText && banderaFile) || (banderaText && !banderaFile) ) {
                labels.push('Todos los anexos deben tener nombre y adjunto');
                bandera = true;
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

    if (anexos_adicionales.length > 0 ) {
        anexos_adicionales.forEach((item, index) => {
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
    }else if ($('#necesidad_visita').val() === 'No'){
        $('.div_ciclo_vida_plan_visita_ajustado').hide();
    }else{
        $('.div_ciclo_vida_plan_visita_ajustado').hide();
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
    //let necesidad_visita = $('#necesidad_visita').val();
    let observaciones_valoracion = $('#observaciones_valoracion').val();
    let anexos_adicionales = [];

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
    formData.append('observaciones_valoracion', observaciones_valoracion);

    var heads = {'X-CSRF-TOKEN': token}

    /*if (necesidad_visita == '') {
        var html = `<label>Debe completar el campo ¿Es necesario efectuar la visita? </label>`;

        Swal.fire({
          icon: "warning",
          title: "Atención",
          html: html,
        });

        return;
    }*/

    //if (necesidad_visita === 'Si') {
        var fileInput = document.getElementById('ciclo_vida_plan_visita_ajustado');
        var file = fileInput.files[0];
        if (file) {
            formData.append('ciclo_vida_plan_visita_ajustado', file);
        } else {
            labels.push('Plan de visita ajustado');
            bandera = true;
        }
    //}

    $('.required_valoracion_informacion').each(function() {
            formData.append($(this).attr('id'), $(this).val());
    });

    $('.tr_documentos_validacion_informacion_recibida').each(function () {

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
                anexos_adicionales.push(row);
            }

            if ( (!banderaText && banderaFile) || (banderaText && !banderaFile) ) {
                labels.push('Todos los anexos deben tener nombre y adjunto');
                bandera = true;
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

    if (anexos_adicionales.length > 0 ) {
        anexos_adicionales.forEach((item, index) => {
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
    let anexos_adicionales = [];
    let observaciones = $('#observaciones_confirmacion_visita').val();

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
    formData.append('observaciones', observaciones);

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

    $('.tr_documentos_adicionales_confirmar_visita').each(function () {

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
                anexos_adicionales.push(row);
            }

            if ( (!banderaText && banderaFile) || (banderaText && !banderaFile) ) {
                labels.push('Todos los anexos deben tener nombre y adjunto');
                bandera = true;
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

    if (anexos_adicionales.length > 0 ) {
        anexos_adicionales.forEach((item, index) => {
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
    let anexos_adicionales = [];
    let observaciones = $('#observaciones_abrir_visita').val();

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
    formData.append('observaciones', observaciones);
    formData.append('documento_apertura_visita', documento_apertura_visita);

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

    $('.tr_documentos_adicionales_abrir_visita').each(function () {

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
                anexos_adicionales.push(row);
            }

            if ( (!banderaText && banderaFile) || (banderaText && !banderaFile) ) {
                labels.push('Todos los anexos deben tener nombre y adjunto');
                bandera = true;
            }
    })

    if (anexos_adicionales.length > 0 ) {
        anexos_adicionales.forEach((item, index) => {
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

    /*var fileInputCarta = document.getElementById('carta_salvaguarda');
    var fileCarta = fileInputCarta.files[0];
    if (fileCarta) {
        formData.append('carta_salvaguarda', fileCarta);
    } else {
        labels.push('Carta salvaguarda');
        bandera = true;
    }*/

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
    let observaciones = $('#observaciones_cierre_visita').val();
    let documento_apertura_visita = $('#documento_apertura_visita').val();
    let anexos_adicionales = [];
    var labels = [];
    let id_entidad = $('#id_entidad').val();

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
    formData.append('documento_apertura_visita', documento_apertura_visita);
    formData.append('id_entidad', id_entidad);

    var heads = {'X-CSRF-TOKEN': token}

    if (documento_apertura_visita === 'Acta de apertura') {
        var fileInput = document.getElementById('acta_apertura_visita');
        var file = fileInput.files[0];
        if (file) {
            formData.append('acta_apertura_visita', file);
        } else {
            labels.push('Acta de apertura');
            bandera = true;
        }
    }else if(documento_apertura_visita === 'Grabación de apertura'){
        let grabacion_apertura_visita = document.getElementById('grabacion_apertura_visita').value;
        if (grabacion_apertura_visita) {
            formData.append('grabacion_apertura_visita', grabacion_apertura_visita);
        }else{
            labels.push('Enlace de grabación');
            bandera = true;
        }
    }else{
        labels.push('¿Acta de apertura de la visita o grabación?');
        bandera = true;
    }

    $('.tr_documentos_cierre_visita').each(function () {

        var row = {};
        var banderaText = false;
        var banderaFile = false;

            $(this).find('input[type="text"]').each(function() {
                var key = $(this).attr('name');
                var valueText = $(this).val();

                if (!key.includes("noobligat") ) {
                    if (valueText !=  '' ) {
                        row[key] = valueText; 
                        banderaText = true;
                    } 
                }else{

                    var fileInputNoObligat = $(this).closest('tr').find('input[type="file"]');
                    
                    if (fileInputNoObligat.length > 0) {

                        console.log('al archivo');

                        var keyFile = fileInputNoObligat.attr('name');
                        var valuefile = fileInputNoObligat[0].files[0];
                        if (keyFile.includes("noobligat") ) {

                            if (valuefile !=  undefined) {

                                row[key] = valueText; 
                                banderaText = true;

                                row[keyFile] = valuefile; 
                                banderaFile=true;
                                
                            }
                        }
                    };

                }
                
            });

            $(this).find('input[type="file"]').each(function() {
                var key = $(this).attr('name');
                var valuefile = this.files[0];
                if (!key.includes("noobligat") ) {
                    if (valuefile !=  undefined) {
                        row[key] = valuefile; 
                        banderaFile=true;
                    }
                }
            });

            if (banderaText && banderaFile) {
                anexos_adicionales.push(row);
            }

            if ( (!banderaText && banderaFile) || (banderaText && !banderaFile) ) {
                labels.push('Todos los anexos deben tener nombre y adjunto');
                bandera = true;
            }
    })

    if (anexos_adicionales.length > 0 ) {
        anexos_adicionales.forEach((item, index) => {
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
    }else{
        labels.push('Documentos del cierre de la visita ');
        bandera = true;
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

  function solicitarDiasAdicionales() {
    var bandera = false;

    let id = $('#id').val();
    let etapa = $('#etapa').val();
    let estado = $('#estado').val();
    let estado_etapa = $('#estado_etapa').val();
    let numero_informe = $('#numero_informe').val();
    let razon_social = $('#razon_social').val();
    let nit = $('#nit').val();
    let url = `/solicitar_dias_adicionales`;
    let observaciones = $('#observaciones_solicitud_dias_adicionales').val();
    let dias = $('#dias').val();
    let id_entidad = $('#id_entidad').val();
    var labels = [];
    let anexos_adicionales = [];

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
    formData.append('dias', dias);
    formData.append('id_entidad', id_entidad);

    let resultado_anexos = cargarDocumentosAdicionales('.tr_documentos_adicionales_dias_adicionales_lider');
    let bandera_anexos = resultado_anexos.bandera;

    if (bandera_anexos) {
        labels.push(resultado_anexos.labels);
        bandera = true;
    }

    anexos_adicionales = resultado_anexos.anexos_adicionales;

    var heads = {'X-CSRF-TOKEN': token}

    if (dias === '') {
        labels.push('Cantidad de días adicionales');
        bandera = true;
    }else if(dias < 1){
        labels.push('Cantidad de días adicionales debe ser mayor a 0');
        bandera = true;
    }

    if (observaciones === '') {
        labels.push('Observaciones ');
        bandera = true;
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

    if (anexos_adicionales.length > 0 ) {
        anexos_adicionales.forEach((item, index) => {
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

  function cargarDocumentosAdicionales(clase) {
    let anexos_adicionales = [];
    let bandera = false;
    let labels = '';

    $(clase).each(function () {

        let row = {};
        let banderaText = false;
        let banderaFile = false;
    
        $(this).find('input[type="text"]').each(function() {
            let key = $(this).attr('name');
            let valueText = $(this).val();
            if (valueText !=  '') {
                row[key] = valueText; 
                banderaText = true;
            }
        });

        $(this).find('input[type="file"]').each(function() {
            let key = $(this).attr('name');
            let valuefile = this.files[0];
            if (valuefile !=  undefined) {
                row[key] = valuefile; 
                banderaFile=true;
            }
        });

        if (banderaText && banderaFile) {
            anexos_adicionales.push(row);
        }

        if ( (!banderaText && banderaFile) || (banderaText && !banderaFile) ) {
            labels = 'Todos los anexos deben tener nombre y adjunto';
            bandera = true;
        }
    })

    return {bandera, anexos_adicionales, labels};
  }

  function abrirModalAprobarDiasAdicionalesCordinacion(id, observacion, dias) {
    $('#modalConfirmarDiasAdicionales').modal('show');
    $('#id_solicitud').val(id);

    document.getElementById('dias_solicitados').innerHTML = dias;
    document.getElementById('motivo_solicitud').innerHTML = observacion;
    document.getElementById('dias_autorizar').value = dias;
    
  }
  
  function confirmarDiasAdicionales() {
    var bandera = false;

    let id = $('#id').val();
    let etapa = $('#etapa').val();
    let estado = $('#estado').val();
    let estado_etapa = $('#estado_etapa').val();
    let numero_informe = $('#numero_informe').val();
    let razon_social = $('#razon_social').val();
    let nit = $('#nit').val();
    let url = `/confirmar_dias_adicionales_coordinacion`;
    let observaciones = $('#observaciones_solicitud_dias_adicionales_coordinacion').val();
    let dias = $('#dias_autorizar').val();
    let confirmar_rechazar_solicitud = $('#confirmar_rechazar_solicitud').val();
    let id_solicitud = $('#id_solicitud').val();
    var labels = [];

    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    if (confirmar_rechazar_solicitud === '') {
        labels.push('Confirmar / rechazar solicitud');
        bandera = true;
    }else if(confirmar_rechazar_solicitud === 'Confirmar'){
        if (dias === '') {
            labels.push('Cantidad de días adicionales');
            bandera = true;
        }else if(dias < 1){
            labels.push('Cantidad de días adicionales debe ser mayor a 0');
            bandera = true;
        }
    }else{
        if (observaciones === '') {
            labels.push('Observaciones');
            bandera = true;
        }
    }

    var formData = new FormData();
    formData.append('id', id);
    formData.append('etapa', etapa);
    formData.append('estado', estado);
    formData.append('estado_etapa', estado_etapa);
    formData.append('numero_informe', numero_informe);
    formData.append('razon_social', razon_social);
    formData.append('nit', nit);
    formData.append('observaciones', observaciones);
    formData.append('dias', dias);
    formData.append('confirmar_rechazar_solicitud', confirmar_rechazar_solicitud);
    formData.append('id_solicitud', id_solicitud);

    var heads = {'X-CSRF-TOKEN': token}

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

  function confirmarRechazarSolicitudDiasAdicionales() {
    var aceptar_rechazar = document.getElementById('confirmar_rechazar_solicitud').value;
    if (aceptar_rechazar === 'Confirmar') {
        $('#div_dias_adicionales_coordinacion').show();
        document.getElementById('label_observaciones_dias_coordinacion').innerHTML = '<b>Observaciones</b>';
    }else{
        $('#div_dias_adicionales_coordinacion').hide();
        document.getElementById('label_observaciones_dias_coordinacion').innerHTML = '<b>Observaciones (*)</b>';
    }
  }

  function abrirModalAprobarDiasAdicionalesDelegatura(id, observacion, dias, historico) {
    $('#modalConfirmarDiasAdicionalesDelegatura').modal('show');
    $('#id_solicitud').val(id);

    var historial = JSON.parse(historico);

    historial.forEach(function(historia){
        document.getElementById('dias_aprobados_coordinacion').textContent = historia.dias;
        document.getElementById('observaciones_aprobacion_coordinacion').textContent = historia.observacion;
        document.getElementById('dias_autorizar_delegatura').value = historia.dias;
    });

    document.getElementById('dias_solicitados_lider').textContent = dias;
    document.getElementById('motivo_solicitud_lider').textContent = observacion;
  }

  function confirmarRechazarSolicitudDiasAdicionalesDelegatura() {
    var aceptar_rechazar = document.getElementById('confirmar_rechazar_solicitud_delegatura').value;
    if (aceptar_rechazar === 'Confirmar') {
        $('#div_dias_adicionales_delegatura').show();
        document.getElementById('label_observaciones_dias_delegatura').textContent = 'Observaciones';
    }else{
        $('#div_dias_adicionales_delegatura').hide();
        document.getElementById('label_observaciones_dias_delegatura').textContent = 'Observaciones (*)';
    }
  }

  function confirmarDiasAdicionalesDelegatura() {
    var bandera = false;

    let id = $('#id').val();
    let etapa = $('#etapa').val();
    let estado = $('#estado').val();
    let estado_etapa = $('#estado_etapa').val();
    let numero_informe = $('#numero_informe').val();
    let razon_social = $('#razon_social').val();
    let nit = $('#nit').val();
    let url = `/confirmar_dias_adicionales_delegatura`;
    let observaciones = $('#observaciones_solicitud_dias_adicionales_delegatura').val();
    let dias = $('#dias_autorizar_delegatura').val();
    let confirmar_rechazar_solicitud = $('#confirmar_rechazar_solicitud_delegatura').val();
    let id_solicitud = $('#id_solicitud').val();
    var labels = [];

    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    if (confirmar_rechazar_solicitud === '') {
        labels.push('Confirmar / rechazar solicitud');
        bandera = true;
    }else if(confirmar_rechazar_solicitud === 'Confirmar'){
        if (dias === '') {
            labels.push('Cantidad de días adicionales');
            bandera = true;
        }else if(dias < 1){
            labels.push('Cantidad de días adicionales debe ser mayor a 0');
            bandera = true;
        }
    }else{
        if (observaciones === '') {
            labels.push('Observaciones');
            bandera = true;
        }
    }

    var formData = new FormData();
    formData.append('id', id);
    formData.append('etapa', etapa);
    formData.append('estado', estado);
    formData.append('estado_etapa', estado_etapa);
    formData.append('numero_informe', numero_informe);
    formData.append('razon_social', razon_social);
    formData.append('nit', nit);
    formData.append('observaciones', observaciones);
    formData.append('dias', dias);
    formData.append('confirmar_rechazar_solicitud', confirmar_rechazar_solicitud);
    formData.append('id_solicitud', id_solicitud);

    var heads = {'X-CSRF-TOKEN': token}

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

  function registrarHallazgos() {

    let id = $('#id').val();
    let etapa = $('#etapa').val();
    let estado = $('#estado').val();
    let estado_etapa = $('#estado_etapa').val();
    let numero_informe = $('#numero_informe').val();
    let razon_social = $('#razon_social').val();
    let nit = $('#nit').val();
    let url = `/registrar_hallazgos`;
    let observaciones = $('#observaciones_hallazgos').val();
    let anexos_adicionales = [];
    
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

    var heads = {'X-CSRF-TOKEN': token};

    let resultado_anexos = cargarDocumentosAdicionales('.tr_documentos_hallazgos');
    let bandera_anexos = resultado_anexos.bandera;

    if (bandera_anexos) {
        bandera_anexos = true;
    }

    anexos_adicionales = resultado_anexos.anexos_adicionales;

    if (anexos_adicionales.length > 0 ) {
        anexos_adicionales.forEach((item, index) => {
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
    }else{
        bandera_anexos = true;
    }

    if (bandera_anexos) {
        var html = `<label>Todos los datos de las tablas deben ser diligenciados</label>`;

        Swal.fire({
          icon: "warning",
          title: "Atención",
          html: html,
        });

        return;
    }

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
    let anexos_adicionales = [];
    let observaciones = $('#observaciones_consolidar_hallazgos').val();
    
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

    var fileInput = document.getElementById('registro_hallazgos_consolidados');
    var file = fileInput.files[0];
    if (file) {
        formData.append('registro_hallazgos_consolidados', file);
    } else {
        labels.push('Hallazgos consolidados');
        bandera = true;
    }

    let resultado_anexos = cargarDocumentosAdicionales('.tr_documentos_consolidar_hallazgos');
    let bandera_anexos = resultado_anexos.bandera;

    if (bandera_anexos) {
        labels.push(resultado_anexos.labels);
        bandera = true;
    }
    
    anexos_adicionales = resultado_anexos.anexos_adicionales;

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

    if (anexos_adicionales.length > 0 ) {
        anexos_adicionales.forEach((item, index) => {
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







    /*$('.required_registro_hallazgos_consolidados').each(function() {
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
    }*/

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
    let observaciones = $('#observaciones_proyecto_informe_final').val();
    let anexos_adicionales = [];

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

    var fileInput = document.getElementById('proyecto_informe_final');
    var file = fileInput.files[0];
    if (file) {
        formData.append('proyecto_informe_final', file);
    } else {
        labels.push('Proyecto de informe final');
        bandera = true;
    }

    let resultado_anexos = cargarDocumentosAdicionales('.tr_documentos_anexos_informe_final');
    let bandera_anexos = resultado_anexos.bandera;

    if (bandera_anexos) {
        labels.push(resultado_anexos.labels);
        bandera = true;
    }

    anexos_adicionales = resultado_anexos.anexos_adicionales;

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

    if (anexos_adicionales.length > 0 ) {
        anexos_adicionales.forEach((item, index) => {
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

  function confirmacionCorreccionesProyectoInformeFinal() {
    let confirmacion_revision_proyecto_informe_final = $('#confirmacion_revision_proyecto_informe_final').val();
    let label_observaciones_revision_diagnostico_proyecto_informe_final = $('#label_observaciones_revision_diagnostico_proyecto_informe_final');

    if (confirmacion_revision_proyecto_informe_final === 'Si') {
        label_observaciones_revision_diagnostico_proyecto_informe_final.text('Observaciones (*)');
        $('#div_revision_proyecto_informe_final').show();
    } else {
        label_observaciones_revision_diagnostico_proyecto_informe_final.text('Observaciones');
        $('#div_revision_proyecto_informe_final').hide();
    }
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
    let confirmacion_revision_proyecto_informe_final = $('#confirmacion_revision_proyecto_informe_final').val();
    let observaciones = $('#observaciones_revision_proyecto_informe_final').val();
    let url = `/revision_proyecto_informe_final`;
    let anexos_adicionales = [];
    
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var formData = new FormData();
    formData.append('id', id);
    formData.append('etapa', etapa);
    formData.append('estado', estado);
    formData.append('estado_etapa', estado_etapa);
    formData.append('numero_informe', numero_informe);
    formData.append('razon_social', razon_social);
    formData.append('nit', nit);
    formData.append('confirmacion_revision_proyecto_informe_final', confirmacion_revision_proyecto_informe_final);
    formData.append('observaciones', observaciones);

    var heads = {'X-CSRF-TOKEN': token}

    let resultado_anexos = cargarDocumentosAdicionales('.tr_documentos_adicionales_revision_proyecto_informe_final');
    let bandera_anexos = resultado_anexos.bandera;

    if (bandera_anexos) {
        labels.push(resultado_anexos.labels);
        bandera = true;
    }

    anexos_adicionales = resultado_anexos.anexos_adicionales;

    if(confirmacion_revision_proyecto_informe_final === ''){
        labels.push('¿Requiere correcciones?');
        bandera = true;
    }else if(confirmacion_revision_proyecto_informe_final === 'Si' ){
        var fileInput = document.getElementById('revision_proyecto_informe_final');
        var file = fileInput.files[0];
        if (file) {
            formData.append('revision_proyecto_informe_final', file);
        } else {
            labels.push('Proyecto de informe final');
            bandera = true;
        }
        
        if (observaciones === '') {
            labels.push('Observaciones');
            bandera = true;
        }
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

    if (anexos_adicionales.length > 0 ) {
        anexos_adicionales.forEach((item, index) => {
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
    let observaciones = $('#correccion_proyecto_informe_final').val();
    let anexos_adicionales = [];
    
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

    var fileInput = document.getElementById('revision_proyecto_informe_final_corregido');
    var file = fileInput.files[0];
    if (file) {
        formData.append('revision_proyecto_informe_final_corregido', file);
    } else {
        labels.push('proyecto de informe final corregido');
        bandera = true;
    }

    let resultado_anexos = cargarDocumentosAdicionales('.tr_documentos_adicionales_correcion_proyecto_informe_final');
    let bandera_anexos = resultado_anexos.bandera;

    if (bandera_anexos) {
        labels.push(resultado_anexos.labels);
        bandera = true;
    }

    anexos_adicionales = resultado_anexos.anexos_adicionales;

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

    if (anexos_adicionales.length > 0 ) {
        anexos_adicionales.forEach((item, index) => {
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
    let observaciones = $('#observaciones_informe_final_coordiaciones').val();
    let anexos_adicionales = [];
    
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

    var fileInput = document.getElementById('revision_proyecto_informe_final_coordinacinoes');
    var file = fileInput.files[0];
    if (file) {
        formData.append('revision_proyecto_informe_final_coordinacinoes', file);
    }

    let resultado_anexos = cargarDocumentosAdicionales('.tr_documentos_adicionales_informe_final_coordinaciones');
    let bandera_anexos = resultado_anexos.bandera;

    if (bandera_anexos) {
        labels.push(resultado_anexos.labels);
        bandera = true;
    }

    anexos_adicionales = resultado_anexos.anexos_adicionales;

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

    if (anexos_adicionales.length > 0 ) {
        anexos_adicionales.forEach((item, index) => {
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
    var labels = [];
    let observaciones = $('#observaciones_revision_informe_final_coordinaciones').val();
    let anexos_adicionales = [];
    let id_entidad = $('#id_entidad').val();
    
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
    formData.append('id_entidad', id_entidad);

    var heads = {'X-CSRF-TOKEN': token}

    var fileInput = document.getElementById('revision_informe_final_coordinaciones');
    var file = fileInput.files[0];
    if (file) {
        formData.append('revision_informe_final_coordinaciones', file);
    } else {
        labels.push('Informe final revisado');
        bandera = true;
    }

    let resultado_anexos = cargarDocumentosAdicionales('.tr_documentos_adicionales_revision_coordinacion');
    let bandera_anexos = resultado_anexos.bandera;

    if (bandera_anexos) {
        labels.push(resultado_anexos.labels);
        bandera = true;
    }

    anexos_adicionales = resultado_anexos.anexos_adicionales;

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

    if (anexos_adicionales.length > 0 ) {
        anexos_adicionales.forEach((item, index) => {
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
    let observaciones = $('#observaciones_revision_informe_final_intendente').val();
    let anexos_adicionales = [];
    
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

    var fileInput = document.getElementById('revision_informe_final_intendente');
    var file = fileInput.files[0];
    if (file) {
        formData.append('revision_informe_final_intendente', file);
    } else {
        labels.push('Informe final revisado por la intendencia');
        bandera = true;
    }

    let resultado_anexos = cargarDocumentosAdicionales('.tr_documentos_adicionales_revision_informe_final_intendente');
    let bandera_anexos = resultado_anexos.bandera;

    if (bandera_anexos) {
        labels.push(resultado_anexos.labels);
        bandera = true;
    }

    anexos_adicionales = resultado_anexos.anexos_adicionales;

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

    if (anexos_adicionales.length > 0 ) {
        anexos_adicionales.forEach((item, index) => {
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

  function medidaDeIntervencionInmediata() {
    let medida_intervencion = document.getElementById('confirmacion_intervencion_inmediata').value;

    if (medida_intervencion === 'Si') {
        document.getElementById('labelObservacionesIntenvencionInmediata').textContent = 'Observaciones (*)';
        $('#div_cargue_memorando_causales_intervencion').show();
    }else{
        document.getElementById('labelObservacionesIntenvencionInmediata').textContent = 'Observaciones';
        $('#div_cargue_memorando_causales_intervencion').hide();
    }
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
    let observaciones = $('#observaciones_firma_informe_final').val();
    let id_entidad = $('#id_entidad').val();
    
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
    formData.append('id_entidad', id_entidad);

    var heads = {'X-CSRF-TOKEN': token}

    var fileInput = document.getElementById('informe_final_firmado');
    var file = fileInput.files[0];
    if (file) {
        formData.append('informe_final_firmado', file);
    } else {
        labels.push('Informe final firmado');
        bandera = true;
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
    let id_entidad = $('#id_entidad').val();
    let codigo = $('#codigo').val();
    let sigla = $('#sigla').val();
    let etapa = $('#etapa').val();
    let estado = $('#estado').val();
    let estado_etapa = $('#estado_etapa').val();
    let numero_informe = $('#numero_informe').val();
    let razon_social = $('#razon_social').val();
    let nit = $('#nit').val();
    let url = `/confirmacion_intervencion_inmediata`;
    let anexos_adicionales = [];
    let confirmacion_intervencion_inmediata = $('#confirmacion_intervencion_inmediata').val();
    let memorando_causales_intervencion = $('#memorando_causales_intervencion').val();
    let observaciones = $('#observaciones_intervencion_inmediata').val();

    if (confirmacion_intervencion_inmediata === '' || confirmacion_intervencion_inmediata === null ) {
            labels.push('¿La situación de la vigilada amerita una medida de intervención inmediata, entre tanto se surte el traslado?');
            bandera = true;
    }

    if ( confirmacion_intervencion_inmediata === 'Si' ) {
        if (memorando_causales_intervencion === null || memorando_causales_intervencion === '') {
            labels.push('Ciclo de vida memorando toma de posesión');
            bandera = true;
        }
        if (observaciones === null || observaciones === '') {
            labels.push('Observaciones');
            bandera = true;
        }
    }

    let resultado_anexos = cargarDocumentosAdicionales('.tr_documentos_adicionales_confirmacion_intervencion_inmediata');
    let bandera_anexos = resultado_anexos.bandera;

    if (bandera_anexos) {
        labels.push(resultado_anexos.labels);
        bandera = true;
    }

    anexos_adicionales = resultado_anexos.anexos_adicionales;

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
    
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var formData = new FormData();
    formData.append('id', id);
    formData.append('etapa', etapa);
    formData.append('estado', estado);
    formData.append('estado_etapa', estado_etapa);
    formData.append('numero_informe', numero_informe);
    formData.append('razon_social', razon_social);
    formData.append('nit', nit);
    formData.append('memorando_causales_intervencion', memorando_causales_intervencion);
    formData.append('confirmacion_intervencion_inmediata', confirmacion_intervencion_inmediata);
    formData.append('observaciones_intervencion_inmediata', observaciones);
    formData.append('id_entidad', id_entidad);
    formData.append('codigo', codigo);
    formData.append('sigla', sigla);

    var heads = {'X-CSRF-TOKEN': token}

    if (anexos_adicionales.length > 0 ) {
        anexos_adicionales.forEach((item, index) => {
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
    let observaciones = $('#observaciones_informe_para_traslado').val();
    let anexos_adicionales = [];

    let grupo_inspeccion = [];
    
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
    formData.append('observaciones', observaciones);

    var heads = {'X-CSRF-TOKEN': token}

    $('.required_informe_traslado').each(function() {
        if ($(this).val() === '') {
            var label = $('label[for="' + $(this).attr('id') + '"]').text().replace(' (*)','');

            labels.push(label);
            bandera = true;
        } else {
            formData.append($(this).attr('id'), $(this).val());
        }
    });

    let resultado_anexos = cargarDocumentosAdicionales('.tr_documentos_adicionales_informe_visita_para_traslado');
    let bandera_anexos = resultado_anexos.bandera;

    if (bandera_anexos) {
        labels.push(resultado_anexos.labels);
        bandera = true;
    }

    anexos_adicionales = resultado_anexos.anexos_adicionales;

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

    if (anexos_adicionales.length > 0 ) {
        anexos_adicionales.forEach((item, index) => {
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
    let observaciones = $('#observaciones_proyeccion_informe_traslado').val();
    let anexos_adicionales = [];
    
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

    $('.required_informe_traslado_entidad').each(function() {
        if ($(this).val() === '') {
            var label = $('label[for="' + $(this).attr('id') + '"]').text().replace(' (*)','');

            labels.push(label);
            bandera = true;
        } else {
            formData.append($(this).attr('id'), $(this).val());
        }
    });

    let resultado_anexos = cargarDocumentosAdicionales('.tr_documentos_adicionales_proyeccion_informe_traslado');
    let bandera_anexos = resultado_anexos.bandera;

    if (bandera_anexos) {
        labels.push(resultado_anexos.labels);
        bandera = true;
    }

    anexos_adicionales = resultado_anexos.anexos_adicionales;

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

    if (anexos_adicionales.length > 0 ) {
        anexos_adicionales.forEach((item, index) => {
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
    let observaciones = $('#observaciones_registrar_pronunciamiento').val();
    let anexos_adicionales = [];
    
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

    let radicado_entrada_pronunciacion_empresa_solidaria = document.getElementById('radicado_entrada_pronunciacion_empresa_solidaria').value;
    let fecha_radicado_entrada_pronunciacion_empresa_solidaria = document.getElementById('fecha_radicado_entrada_pronunciacion_empresa_solidaria').value;
    let radicado_entrada_pronunciacion_revisoria_fiscal = document.getElementById('radicado_entrada_pronunciacion_revisoria_fiscal').value;
    let fecha_radicado_entrada_pronunciacion_revisoria_fiscal = document.getElementById('fecha_radicado_entrada_pronunciacion_revisoria_fiscal').value;
    let confirmacion_pronunciacion_entidad = document.getElementById('confirmacion_pronunciacion_entidad').value;
    
    if (confirmacion_pronunciacion_entidad === '') {
        labels.push('¿La organización de la economía solidaria realiza pronunciamiento alguno en el marco del traslado?');
        bandera = true;
    }

    if (
        (radicado_entrada_pronunciacion_empresa_solidaria === '' && fecha_radicado_entrada_pronunciacion_empresa_solidaria === '' 
        && radicado_entrada_pronunciacion_revisoria_fiscal === '' && fecha_radicado_entrada_pronunciacion_revisoria_fiscal === '') 
        && confirmacion_pronunciacion_entidad === 'Si'
     ) {
        labels.push('Alguno de los radicados de entrada y fecha');
        bandera = true;
    }

    let resultado_anexos = cargarDocumentosAdicionales('.tr_documentos_adicionales_registrar_pronunciamiento');
    let bandera_anexos = resultado_anexos.bandera;

    if (bandera_anexos) {
        labels.push(resultado_anexos.labels);
        bandera = true;
    }

    anexos_adicionales = resultado_anexos.anexos_adicionales;

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
    
    formData.append('radicado_entrada_pronunciacion_empresa_solidaria', radicado_entrada_pronunciacion_empresa_solidaria);
    formData.append('fecha_radicado_entrada_pronunciacion_empresa_solidaria', fecha_radicado_entrada_pronunciacion_empresa_solidaria);
    formData.append('radicado_entrada_pronunciacion_revisoria_fiscal', radicado_entrada_pronunciacion_revisoria_fiscal);
    formData.append('fecha_radicado_entrada_pronunciacion_revisoria_fiscal', fecha_radicado_entrada_pronunciacion_revisoria_fiscal);
    formData.append('confirmacion_pronunciacion_entidad', confirmacion_pronunciacion_entidad);

    if (anexos_adicionales.length > 0 ) {
        anexos_adicionales.forEach((item, index) => {
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
    let observaciones = $('#observaciones_valoracion_informacion_remitida').val();
    let anexos_adicionales = [];
    
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

    var fileInput = document.getElementById('evaluacion_respuesta');
    var file = fileInput.files[0];
    if (file) {
            formData.append('evaluacion_respuesta', file);
    } else {
            labels.push('Evaluación respuesta informe de visita');
            bandera = true;
    }

    $('.required_evaluacion_respuesta').each(function() {
        if ($(this).val() === '') {
            var label = $('label[for="' + $(this).attr('id') + '"]').text().replace(' (*)','');

            labels.push(label);
            bandera = true;
        } else {
            formData.append($(this).attr('id'), $(this).val());
        }
    });

    let resultado_anexos = cargarDocumentosAdicionales('.tr_documentos_adicionales_valoracion_informacion_remitida');
    let bandera_anexos = resultado_anexos.bandera;

    if (bandera_anexos) {
        labels.push(resultado_anexos.labels);
        bandera = true;
    }

    anexos_adicionales = resultado_anexos.anexos_adicionales;

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

    if (anexos_adicionales.length > 0 ) {
        anexos_adicionales.forEach((item, index) => {
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
    let observaciones = $('#observaciones_traslado_resultado_respuesta').val();
    let anexos_adicionales = [];
    let id_entidad = $('#id_entidad').val();
    
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
    formData.append('id_entidad', id_entidad);

    var heads = {'X-CSRF-TOKEN': token}

    let resultado_anexos = cargarDocumentosAdicionales('.tr_documentos_adicionales_traslado_resultado_respuesta');
    let bandera_anexos = resultado_anexos.bandera;

    if (bandera_anexos) {
        labels.push(resultado_anexos.labels);
        bandera = true;
    }

    anexos_adicionales = resultado_anexos.anexos_adicionales;

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

    if (anexos_adicionales.length > 0 ) {
        anexos_adicionales.forEach((item, index) => {
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

  function guardarCitacionComiteInternoEvaluador() {
    var bandera = false;

    var labels = [];
    let id = $('#id').val();
    let etapa = $('#etapa').val();
    let estado = $('#estado').val();
    let estado_etapa = $('#estado_etapa').val();
    let numero_informe = $('#numero_informe').val();
    let razon_social = $('#razon_social').val();
    let nit = $('#nit').val();
    let url = `/citacion_comite_interno_evaluador`;
    let observaciones = $('#observaciones_fecha_hora_citacion_comite_interno').val();
    let fecha_hora_citacion = $('#fecha_hora_citacion_comite_interno').val();
    let anexos_adicionales = [];

    if (fecha_hora_citacion === '') {
        labels.push('Fecha y hora de la citación');
        bandera = true;
    }
    
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
    formData.append('fecha_hora_citacion', fecha_hora_citacion);

    var heads = {'X-CSRF-TOKEN': token}

    let resultado_anexos = cargarDocumentosAdicionales('.tr_documentos_adicionales_citacion_comite_interon');
    let bandera_anexos = resultado_anexos.bandera;

    if (bandera_anexos) {
        labels.push(resultado_anexos.labels);
        bandera = true;
    }

    anexos_adicionales = resultado_anexos.anexos_adicionales;

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

    if (anexos_adicionales.length > 0 ) {
        anexos_adicionales.forEach((item, index) => {
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
    let observaciones = $('#observaciones_actuacion_administrativa').val();
    let anexos_adicionales = [];
    
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

    var fileInput = document.getElementById('acta_actuacion_administrativa');
    var file = fileInput.files[0];
    if (file) {
        formData.append('acta_actuacion_administrativa', file);
    } else {
        labels.push('Acta del comité interno evaluador');
        bandera = true;
    }

    let resultado_anexos = cargarDocumentosAdicionales('.tr_documentos_adicionales_actuacion_administrativa');
    let bandera_anexos = resultado_anexos.bandera;

    if (bandera_anexos) {
        labels.push(resultado_anexos.labels);
        bandera = true;
    }

    anexos_adicionales = resultado_anexos.anexos_adicionales;


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

    if (anexos_adicionales.length > 0 ) {
        anexos_adicionales.forEach((item, index) => {
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
    let observaciones = $('#observaciones_expedientes_finales').val();
    let anexos_adicionales = [];
    var labels = [];

    let ciclos_vida = [];

    $('.tr_ciclo_expediente_final').each(function () {

        var row = {};
            $(this).find('input[type="text"], input[type="hidden"]').each(function() {
                var key = $(this).attr('name');
                var value = $(this).val();
                row[key] = value;
            });
            ciclos_vida.push(row);
    })


    let resultado_anexos = cargarDocumentosAdicionales('.tr_documentos_finales');
    let bandera_anexos = resultado_anexos.bandera;

    if (bandera_anexos) {
        labels.push(resultado_anexos.labels);
        bandera = true;
    }

    anexos_adicionales = resultado_anexos.anexos_adicionales;
    
    if (bandera) {
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
    formData.append('etapa', etapa);
    formData.append('estado', estado);
    formData.append('estado_etapa', estado_etapa);
    formData.append('numero_informe', numero_informe);
    formData.append('razon_social', razon_social);
    formData.append('nit', nit);
    formData.append('observaciones', observaciones);

    if (anexos_adicionales.length > 0 ) {
        anexos_adicionales.forEach((item, index) => {
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

    Swal.fire({
        text: "¿El tablero de control esta completamente diligenciado para la visita de insepcción?",
        icon: "question",
        footer: '<a href="#">Tablero de control</a>',
        showCancelButton: true,
        cancelButtonText: "Cancelar",
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Confirmar"
      }).then((result) => {
        if (result.isConfirmed) {
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

      });

  }

  // Variables globales para los gráficos
  let chartVisitasEtapa = null;
  let chartVisitasEstado = null;
  let chartVisitasEstadoEtapa = null;
  let chartDiasPorEstado = null;
  let chartVisitasNaturaleza = null;
  let chartVisitasTipoOrganizacion = null;

  function estadisticas() {

    let url = `/estadisticas_datos`;
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var heads = {'X-CSRF-TOKEN': token};

    let etapa_actual = document.getElementById('etapa_actual').value;
    let estado_etapa = document.getElementById('estado_etapa').value;
    let estado_informe = document.getElementById('estado_informe').value;
    let fecha_inicial = document.getElementById('fecha_inicial');
    let fecha_final = document.getElementById('fecha_final');
    let region = document.getElementById('region');
    let departamentos = document.getElementById('departamentos');
    let naturaleza_organizacion = document.getElementById('naturaleza_organizacion');
    let tipo_organizacion = document.getElementById('tipo_organizacion');
    let nivel_supervision = document.getElementById('nivel_supervision');

    //Rango de fechas año actual
    const fechaActual = new Date();
    const anioActual = fechaActual.getFullYear();  

    if (fecha_inicial.value == '' && fecha_final.value == '' ) {
        fecha_inicial.value = anioActual+'-01-01';
        fecha_final.value = anioActual+'-12-31';
    }else{
        fecha_inicial.value = fecha_inicial.value;
        fecha_final.value = fecha_final.value;
    }

    var formData = new FormData();
    formData.append('etapa_actual', etapa_actual);
    formData.append('estado_etapa', estado_etapa);
    formData.append('estado_informe', estado_informe);
    formData.append('fecha_inicial', fecha_inicial.value);
    formData.append('fecha_final', fecha_final.value);
    formData.append('region', region.value);
    formData.append('departamentos', departamentos.value);
    formData.append('naturaleza_organizacion', naturaleza_organizacion.value);
    formData.append('tipo_organizacion', tipo_organizacion.value);
    formData.append('nivel_supervision', nivel_supervision.value);

    let etapasContadas = {};
    let etapasSinRepetir = [];

    let naturalezaContadas = {};
    let naturalezaSinRepetir = [];

    let estadosContados = {};
    let estadosSinRepetir = [];

    let estadosEtapasContados = {};
    let estadosEtapaSinRepetir = [];

    let tipoOrganizacionContadas = {};
    let tipoOrganizacionSinRepetir = [];

    let nivelSupervisionContadas = {};

    fetch(url,{
        method: 'POST',
        body: formData,
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
        const parametros = data.parametros;
        const cantidad_visitas_actuales = data.cantidad_visitas_actuales;
        const departamentos = data.lugares;
        const mayor_valor_etapa = 34;

        let cantidad_visitas_consultadas = document.getElementById('cantidad_visitas_consultadas');
        cantidad_visitas_consultadas.innerText = 'Cantidad de visitas consultadas: '+cantidad_visitas_actuales;


        
        //Añadir opciones al select de etapa actual
        let select_etapa_actual = document.getElementById('etapa_actual');

        if (select_etapa_actual.value == '') {
            //Vaciar las opciones del select
            vaciarSelect('etapa_actual');

            let nueva_opcion_select = new Option("Seleccione", "");
            select_etapa_actual.add(nueva_opcion_select);

            parametros.forEach(parametros => { 
                let nombre_parametro = parametros.estado;

                nueva_opcion_select = new Option(nombre_parametro, nombre_parametro); 

                select_etapa_actual.add(nueva_opcion_select);
            });
        }else{
            select_etapa_actual.value = select_etapa_actual.value;
        }

        //Añadir opciones al select de departamentos
        let select_departamentos = document.getElementById('departamentos');

        if (select_departamentos.value == '') {
            //Vaciar las opciones del select
            vaciarSelect('departamentos');

            let nueva_opcion_select_departamento = new Option("Seleccione", "");
            select_departamentos.add(nueva_opcion_select_departamento);

            departamentos.forEach(departamento => { 
                let nombre_departamento = departamento.departamento;
                let id_departamento = departamento.id;

                nueva_opcion_select_departamento = new Option(nombre_departamento, nombre_departamento); 

                select_departamentos.add(nueva_opcion_select_departamento);
            });
        }else{
            select_departamentos.value = select_departamentos.value;
        }

        message.forEach(dato => {
            let etapa = dato.etapa;
            let estado = dato.estado_informe;
            let estado_etapa = dato.estado_etapa;
            let naturaleza_organizacion = dato.entidad.naturaleza_organizacion;
            let tipo_organizacion = dato.entidad.tipo_organizacion;
            let nivel_supervision = dato.entidad.nivel_supervision;
            
            if (!etapasContadas.hasOwnProperty(etapa)) {
                etapasContadas[etapa] = {
                    conteo_etapa: 0,
                    nivel_1: 0,
                    nivel_2: 0,
                    nivel_3: 0,
                };
            }

            // Incrementa el conteo general de la etapa
            etapasContadas[etapa].conteo_etapa++;

            if (!naturalezaContadas.hasOwnProperty(naturaleza_organizacion)) {
                naturalezaContadas[naturaleza_organizacion] = {
                    conteo_naturaleza: 0,
                    nivel_1: 0,
                    nivel_2: 0,
                    nivel_3: 0,
                };
            } 

            naturalezaContadas[naturaleza_organizacion].conteo_naturaleza++;

            if (!tipoOrganizacionContadas.hasOwnProperty(tipo_organizacion)) {
                tipoOrganizacionContadas[tipo_organizacion] = {
                    conteo_tipo: 0,
                    nivel_1: 0,
                    nivel_2: 0,
                    nivel_3: 0,
                };
            } 

            tipoOrganizacionContadas[tipo_organizacion].conteo_tipo++;

            // Incrementa el conteo específico según el nivel de supervisión
            if (nivel_supervision == 1) {
                etapasContadas[etapa].nivel_1++;
                naturalezaContadas[naturaleza_organizacion].nivel_1++;
                tipoOrganizacionContadas[tipo_organizacion].nivel_1++;
            } else if (nivel_supervision == 2) {
                etapasContadas[etapa].nivel_2++;
                naturalezaContadas[naturaleza_organizacion].nivel_2++;
                tipoOrganizacionContadas[tipo_organizacion].nivel_2++;
            } else if (nivel_supervision == 3) {
                etapasContadas[etapa].nivel_3++;
                naturalezaContadas[naturaleza_organizacion].nivel_3++;
                tipoOrganizacionContadas[tipo_organizacion].nivel_3++;
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

            if (nivelSupervisionContadas.hasOwnProperty(nivel_supervision)) {
                nivelSupervisionContadas[nivel_supervision]++;
            } else {
                nivelSupervisionContadas[nivel_supervision] = 1;
            }
        });

        etapasSinRepetir = Object.keys(etapasContadas);
        const soloNumeros = Object.values(etapasContadas);
        const visitas_etapa = document.getElementById('visitas_etapa');

        const coloresNivel = {
            1: 'rgb(41, 114, 241)',
            2: 'rgb(41, 241, 68)',
            3: 'rgb(44, 225, 200)',
        };

        // Extraemos los valores de cada nivel en arreglos separados
        const dataNivel1 = soloNumeros.map(item => item.nivel_1 === 0 ? null : item.nivel_1);
        const dataNivel2 = soloNumeros.map(item => item.nivel_2 === 0 ? null : item.nivel_2);
        const dataNivel3 = soloNumeros.map(item => item.nivel_3 === 0 ? null : item.nivel_3);
        
        //Destruye el gráfico si existe
        if (chartVisitasEtapa) {
            chartVisitasEtapa.destroy();
        }

        //Crea un nuevo gráfico
        chartVisitasEtapa = new Chart(visitas_etapa, {
            type: 'bar',
            data: {
                labels: etapasSinRepetir,
                datasets: [
                    {
                        label: 'Nivel 1',
                        data: dataNivel1,
                        borderWidth: 1,
                        backgroundColor: coloresNivel[1],
                    },
                    {
                        label: 'Nivel 2',
                        data: dataNivel2,
                        borderWidth: 1,
                        backgroundColor: coloresNivel[2],
                    },
                    {
                        label: 'Nivel 3',
                        data: dataNivel3,
                        borderWidth: 1,
                        backgroundColor: coloresNivel[3],
                    },
                ]
            },

            plugins: [ChartDataLabels],
            
            options: {
                plugins: {
                    datalabels: {
                      color: '#FFFFFF'
                    }
                },
                scales: {
                  x: {
                    stacked: true,
                  },
                  y: {
                    stacked: true
                  }
                }
            }
        });

        const visitas_estado = document.getElementById('visitas_estado');
        estadosSinRepetir = Object.keys(estadosContados);
        const soloNumerosEstados = Object.values(estadosContados);

        // Destruir gráfico anterior si existe
        if (chartVisitasEstado) {
            chartVisitasEstado.destroy();
        }

        //Crea un nuevo gráfico
        chartVisitasEstado = new Chart(visitas_estado, {
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

        // Destruir gráfico anterior si existe
        if (chartVisitasEstadoEtapa) {
            chartVisitasEstadoEtapa.destroy();
        }

        //Crea un nuevo gráfico
        chartVisitasEstadoEtapa = new Chart(visitas_estado_etapa, {
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

        const dias_por_estado = document.getElementById('dias_por_estado');

        let prom = data.datos;
        let etapasConteo = {};

        // Recorrer los datos y acumular días y conteos por etapa
        prom.forEach(conteo_dia => {
            let conteo_por_visita = conteo_dia.conteo_dias;

            conteo_por_visita.forEach(dias => {
                let etapa = dias.etapa;
                let conteoDias = dias.conteo_dias;

                // Inicializar el objeto para la etapa si no existe
                if (!etapasConteo[etapa]) {
                    etapasConteo[etapa] = { totalDias: 0, visitas: 0 };
                }

                // Acumular los días y contar la visita
                etapasConteo[etapa].totalDias += parseInt(conteoDias);
                etapasConteo[etapa].visitas += parseInt(1);
            });
        });

        // Calcular el promedio de días por etapa
        let promediosPorEtapa = {};
        let etapas_promedio = [];
        let prmedios_etapas_promedio = [];
        for (let etapa in etapasConteo) {
            let totalDias = etapasConteo[etapa].totalDias;
            let visitas = etapasConteo[etapa].visitas;
            let promedio = totalDias / visitas;

            // Redondear el promedio a dos decimales
            promediosPorEtapa[etapa] = parseFloat(promedio.toFixed(2));

            etapas_promedio.push(etapa);
            prmedios_etapas_promedio.push(parseFloat(promedio.toFixed(2)));
        }

        // Destruir gráfico anterior si existe
        if (chartDiasPorEstado) {
            chartDiasPorEstado.destroy();
        }

        //Crea un nuevo gráfico
        chartDiasPorEstado = new Chart(dias_por_estado, {
            type: 'bar',
            plugins: [ChartDataLabels],
            data: {
                labels: etapas_promedio,
                datasets: [{
                    label: 'Días hábiles',
                    data: prmedios_etapas_promedio,
                    borderWidth: 1,
                    backgroundColor: [
                        'rgb(149, 125, 255)',
                    ],
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                plugins: {
                    datalabels: {
                      color: '#FFFFFF'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            maxTicksLimit: etapas_promedio.length, // Asegura que no se limiten las etiquetas
                        }
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
                }
            }
        });
        

        //Estadísticas por departamento

        const departmentCounts = new Map();
        const avanceEtapaSums = new Map();

        message.forEach(dato => {
            let departamento = dato.entidad.lugar.departamento;
            let avance_etapa = dato.etapa_proceso.orden_etapa;
            
            if (departmentCounts.has(departamento)) {
                departmentCounts.set(departamento, departmentCounts.get(departamento) + 1);
            } else {
                departmentCounts.set(departamento, 1);
            }

            if (avanceEtapaSums.has(departamento)) {
                avanceEtapaSums.set(departamento, avanceEtapaSums.get(departamento) + avance_etapa);
            } else {
                avanceEtapaSums.set(departamento, avance_etapa);
            }
        });

        let labels = [
            { department: 'ANTIOQUIA', x: 215, y: 180, value: 1, cumplimiento: 0 },
            { department: 'BOGOTA', x: 250, y: 230, value: 2, cumplimiento: 0 },
            { department: 'NARIÑO', x: 155, y: 300, value: 3, cumplimiento: 0 },
            { department: 'AMAZONAS', x: 315, y: 375, value: 4, cumplimiento: 0 },
            { department: 'ARAUCA', x: 320, y: 180, value: 5, cumplimiento: 0 },
            { department: 'ATLANTICO', x: 230, y: 80, value: 6, cumplimiento: 0 },
            { department: 'BOLIVAR', x: 249, y: 145, value: 7, cumplimiento: 0 },
            { department: 'BOYACA', x: 272, y: 210, value: 8, cumplimiento: 0 },
            { department: 'CALDAS', x: 225, y: 210, value: 9, cumplimiento: 0 },
            { department: 'CAQUETA', x: 250, y: 325, value: 10, cumplimiento: 0 },
            { department: 'CASANARE', x: 310, y: 210, value: 11, cumplimiento: 0 },
            { department: 'CAUCA', x: 175, y: 280, value: 12, cumplimiento: 0 },
            { department: 'CESAR', x: 260, y: 100, value: 13, cumplimiento: 0 },
            { department: 'CHOCO', x: 180, y: 210, value: 14, cumplimiento: 0 },
            { department: 'CORDOBA', x: 205, y: 140, value: 15, cumplimiento: 0 },
            { department: 'CUNDINAMARCA', x: 240, y: 215, value: 16, cumplimiento: 0 },
            { department: 'GUAINIA', x: 375, y: 275, value: 17, cumplimiento: 0 },
            { department: 'GUAVIARE', x: 290, y: 295, value: 18, cumplimiento: 0 },
            { department: 'HUILA', x: 210, y: 275, value: 19, cumplimiento: 0 },
            { department: 'LA GUAJIRA', x: 275, y: 65, value: 20, cumplimiento: 0 },
            { department: 'MAGDALENA', x: 240, y: 105, value: 21, cumplimiento: 0 },
            { department: 'META', x: 275, y: 255, value: 22, cumplimiento: 0 },
            { department: 'NORTE DE SANTANDER', x: 275, y: 150, value: 23, cumplimiento: 0 },
            { department: 'PUTUMAYO', x: 195, y: 325, value: 24, cumplimiento: 0 },
            { department: 'QUINDIO', x: 210, y: 235, value: 25, cumplimiento: 0 },
            { department: 'RISARALDA', x: 205, y: 215, value: 26, cumplimiento: 0 },
            { department: 'SAN ANDRES', x: 170, y: 75, value: 27, cumplimiento: 0 },
            { department: 'SANTANDER', x: 260, y: 180, value: 28, cumplimiento: 0 },
            { department: 'SUCRE', x: 225, y: 125, value: 29, cumplimiento: 0 },
            { department: 'TOLIMA', x: 220, y: 250, value: 30, cumplimiento: 0 },
            { department: 'VALLE DEL CAUCA', x: 185, y: 250, value: 31, cumplimiento: 0 },
            { department: 'VAUPES', x: 325, y: 315, value: 32, cumplimiento: 0 },
            { department: 'VICHADA', x: 365, y: 225, value: 33, cumplimiento: 0 },
        ];

        labels = labels.map(label => {

            const count = departmentCounts.get(label.department) || '';

            if (count) {
                return {
                    ...label,
                    value: count
                };
            }else{
                return null; 
            }
                
        }).filter(label => label !== null);   

        labels = labels.map(label => {

            const count = avanceEtapaSums.get(label.department) || '';

            if (count) {
                return {
                    ...label,
                    cumplimiento: count
                };
            }else{
                return null; 
            }
                
        }).filter(label => label !== null); 
        

        let table_cumplimiento_departamental = document.getElementById('table_cumplimiento_departamental');

        // Limpiar labels anteriores
        const labels_limpiar = document.querySelectorAll('.department-label');
        labels_limpiar.forEach(label => label.remove());

        // Limpiar filas anteriores de la tabla
        while (table_cumplimiento_departamental.rows.length > 0) {
            table_cumplimiento_departamental.deleteRow(0);
        }

        let cantidad_departamentos = 0;
        let cumplimientos_visitas = [];

        labels.forEach((label,i) => {

            let total_cumplimiento_departamento = label.cumplimiento;
            let cantidad_visitas_departamento = label.value;
            let ponderado_cumplimiento_departamento = parseFloat(mayor_valor_etapa) * parseFloat(cantidad_visitas_departamento);

            let porcentaje_cumplimiento_departamento = parseFloat(total_cumplimiento_departamento) / parseFloat(ponderado_cumplimiento_departamento);

            const div = document.createElement('div');
            div.className = 'department-label';
            div.style.top = label.y + 'px';
            div.style.left = label.x + 'px';
            div.textContent = label.value;
            document.querySelector('.map-container').appendChild(div);
            
            if (label.value) {
                let nueva_fila = table_cumplimiento_departamental.insertRow();
                let celda_consecutivo = nueva_fila.insertCell(0);
                let celda_departamento = nueva_fila.insertCell(1);
                let celda_cantidad_visitas = nueva_fila.insertCell(2);
                let celda_porcentaje_cumplimiento = nueva_fila.insertCell(3);

                celda_consecutivo.textContent = i+1;
                celda_departamento.textContent = label.department;
                celda_cantidad_visitas.textContent = label.value;
                celda_porcentaje_cumplimiento.textContent = (parseFloat(porcentaje_cumplimiento_departamento)*100).toFixed(2) + '%' ;
            }

            cantidad_departamentos++;
            cumplimientos_visitas.push(porcentaje_cumplimiento_departamento);

        });

        //Barra de progreso

        let sumatoria_cumplimiento_visitas = cumplimientos_visitas.reduce((accumulator, currentValue) => accumulator + currentValue, 0);
        let porcentual_cumplimiento_visitas = parseFloat(sumatoria_cumplimiento_visitas) / parseFloat(cantidad_departamentos);

        let barra_progreso = document.getElementById('progreso_total');

        barra_progreso.textContent = (parseFloat(porcentual_cumplimiento_visitas)*100).toFixed(2) + '%' ;
        barra_progreso.style.width = (parseFloat(porcentual_cumplimiento_visitas)*100).toFixed(2) + '%'

        //Estadísticas por región

        const regionCounts = new Map();
        const avanceRegionSums = new Map();

        message.forEach(dato => {
            let region = dato.entidad.lugar.region;
            let avance_region = dato.etapa_proceso.orden_etapa;

            console.log('region',region);
            
            
            if (regionCounts.has(region)) {
                regionCounts.set(region, regionCounts.get(region) + 1);
            } else {
                regionCounts.set(region, 1);
            }

            if (avanceRegionSums.has(region)) {
                avanceRegionSums.set(region, avanceRegionSums.get(region) + avance_region);
            } else {
                avanceRegionSums.set(region, avance_region);
            }
        });

        let labels_region = [
            { region: 'AMAZONICA', x: 315, y: 375, value: 1, cumplimiento: 0 },
            { region: 'ORINOQUIA', x: 290, y: 295, value: 2, cumplimiento: 0 },
            { region: 'CARIBE', x: 249, y: 115, value: 3, cumplimiento: 0 },
            { region: 'ANDINA', x: 250, y: 230, value: 4, cumplimiento: 0 },
            { region: 'PACIFICA', x: 185, y: 250, value: 5, cumplimiento: 0 },
            
        ];

        labels_region = labels_region.map(label => {

            const count = regionCounts.get(label.region) || '';

            if (count) {
                return {
                    ...label,
                    value: count
                };
            }else{
                return null; 
            }
                
        }).filter(label => label !== null);   

        labels_region = labels_region.map(label => {

            const count = avanceRegionSums.get(label.region) || '';

            if (count) {
                return {
                    ...label,
                    cumplimiento: count
                };
            }else{
                return null; 
            }
                
        }).filter(label => label !== null); 


        let table_cumplimiento_regional = document.getElementById('table_cumplimiento_regional');

        // Limpiar labels anteriores
        const labels_limpiar_region = document.querySelectorAll('.region-label');
        labels_limpiar_region.forEach(label => label.remove());

        // Limpiar filas anteriores de la tabla
        while (table_cumplimiento_regional.rows.length > 0) {
            table_cumplimiento_regional.deleteRow(0);
        }

        let cantidad_giones = 0;
        let cumplimientos_visitas_regiones = [];

        labels_region.forEach((label,i) => {

            let total_cumplimiento_region = label.cumplimiento;
            let cantidad_visitas_region = label.value;
            let ponderado_cumplimiento_region = parseFloat(mayor_valor_etapa) * parseFloat(cantidad_visitas_region);

            let porcentaje_cumplimiento_region = parseFloat(total_cumplimiento_region) / parseFloat(ponderado_cumplimiento_region);

            const div = document.createElement('div');
            div.className = 'region-label';
            div.style.top = label.y + 'px';
            div.style.left = label.x + 'px';
            div.textContent = label.value;
            document.querySelector('.map-container-regiones').appendChild(div);
            
            if (label.value) {
                let nueva_fila = table_cumplimiento_regional.insertRow();
                let celda_consecutivo = nueva_fila.insertCell(0);
                let celda_departamento = nueva_fila.insertCell(1);
                let celda_cantidad_visitas = nueva_fila.insertCell(2);
                let celda_porcentaje_cumplimiento = nueva_fila.insertCell(3);

                celda_consecutivo.textContent = i+1;
                celda_departamento.textContent = label.region;
                celda_cantidad_visitas.textContent = label.value;
                celda_porcentaje_cumplimiento.textContent = (parseFloat(porcentaje_cumplimiento_region)*100).toFixed(2) + '%' ;
            }

            cantidad_giones++;
            cumplimientos_visitas_regiones.push(porcentaje_cumplimiento_region);

        });
        
        //Gráfico por naturaleza organización

            naturalezaSinRepetir = Object.keys(naturalezaContadas);
            const soloNumerosNaturaleza = Object.values(naturalezaContadas);
            
            // Extraemos los valores de cada nivel en arreglos separados
            const dataNivelNaturaleza1 = soloNumerosNaturaleza.map(item => item.nivel_1 === 0 ? null : item.nivel_1);
            const dataNivelNaturaleza2 = soloNumerosNaturaleza.map(item => item.nivel_2 === 0 ? null : item.nivel_2);
            const dataNivelNaturaleza3 = soloNumerosNaturaleza.map(item => item.nivel_3 === 0 ? null : item.nivel_3);

            //Destruye el gráfico si existe
            if (chartVisitasNaturaleza) {
                chartVisitasNaturaleza.destroy();
            }

            //Crea un nuevo gráfico
            chartVisitasNaturaleza = new Chart(naturaleza, {
                type: 'bar',
                plugins: [ChartDataLabels],
                data: {
                    labels: naturalezaSinRepetir,
                    datasets: [
                        {
                            label: 'Nivel 1',
                            data: dataNivelNaturaleza1,
                            borderWidth: 1,
                            backgroundColor: coloresNivel[1],
                        },
                        {
                            label: 'Nivel 2',
                            data: dataNivelNaturaleza2,
                            borderWidth: 1,
                            backgroundColor: coloresNivel[2],
                        },
                        {
                            label: 'Nivel 3',
                            data: dataNivelNaturaleza3,
                            borderWidth: 1,
                            backgroundColor: coloresNivel[3],
                        },
                    ]
                },

                options: {
                    plugins: {
                        datalabels: {
                        color: '#FFFFFF'
                        }
                    },
                    scales: {
                    x: {
                        stacked: true,
                    },
                    y: {
                        stacked: true
                    }
                    }
                }
            });

        //Gráfico por tipo de organización
            tipoOrganizacionSinRepetir = Object.keys(tipoOrganizacionContadas);
            const soloNumerosTipoOrganizacion = Object.values(tipoOrganizacionContadas);
            const tipoOrganizacion = document.getElementById('grafico_tipo_organizacion');
            
            // Extraemos los valores de cada nivel en arreglos separados
            const dataNivelTipo1 = soloNumerosTipoOrganizacion.map(item => item.nivel_1 === 0 ? null : item.nivel_1);
            const dataNivelTipo2 = soloNumerosTipoOrganizacion.map(item => item.nivel_2 === 0 ? null : item.nivel_2);
            const dataNivelTipo3 = soloNumerosTipoOrganizacion.map(item => item.nivel_3 === 0 ? null : item.nivel_3);

            //Destruye el gráfico si existe
            if (chartVisitasTipoOrganizacion) {
                chartVisitasTipoOrganizacion.destroy();
            }

            //Crea un nuevo gráfico
            chartVisitasTipoOrganizacion = new Chart(tipoOrganizacion, {
                type: 'bar',
                plugins: [ChartDataLabels],
                data: {
                    labels: tipoOrganizacionSinRepetir,
                    datasets: [
                        {
                            label: 'Nivel 1',
                            data: dataNivelTipo1,
                            borderWidth: 1,
                            backgroundColor: coloresNivel[1],
                        },
                        {
                            label: 'Nivel 2',
                            data: dataNivelTipo2,
                            borderWidth: 1,
                            backgroundColor: coloresNivel[2],
                        },
                        {
                            label: 'Nivel 3',
                            data: dataNivelTipo3,
                            borderWidth: 1,
                            backgroundColor: coloresNivel[3],
                        },
                    ]
                },

                options: {
                    plugins: {
                        datalabels: {
                        color: '#FFFFFF'
                        }
                    },
                    scales: {
                    x: {
                        stacked: true,
                    },
                    y: {
                        stacked: true
                    }
                    }
                }
            });


    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    })  
  }

  /**
    * vacia las opciones de un select.
    * @param {string} select_id - id del select.
  */

  function vaciarSelect(select_id){

        let select = document.getElementById(select_id);

        while (select.options.length > 0) {
            select.remove(0);
        }
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
        $('.razon_social_revision_fiscal').hide();
    }else{
        $('.razon_social_revision_fiscal').show();
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
    let id_entidad = $('#id_entidad').val();
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
    formData.append('id_entidad', id_entidad);

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
    let id_entidad = $('#id_entidad').val();
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
    formData.append('id_entidad', id_entidad);

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

function productoGeneradoSocializacion(){
    var producto = $('#producto_generado_socializacion').val();

    if (producto === 'GRABACIÓN') {
        $('#div_enlace_grabacion_socializacion').show();
        $('#div_acta_asistencia_socializacion').hide();
    }else if(producto === 'DOCUMENTO(S)'){
        $('#div_acta_asistencia_socializacion').show();
        $('#div_enlace_grabacion_socializacion').hide();
    }else if(producto === 'AMBOS'){
        $('#div_enlace_grabacion_socializacion').show();
        $('#div_acta_asistencia_socializacion').show();
    }else{
        $('#div_enlace_grabacion_socializacion').hide();
        $('#div_acta_asistencia_socializacion').hide();
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

function productoAbrirVisita() {
    let documento_apertura_visita = document.getElementById('documento_apertura_visita').value;

    if (documento_apertura_visita === 'Acta de apertura') {
        document.getElementById('div_acta_apertura_visita').style.display = 'block';
        document.getElementById('div_grabacion_apertura_visita').style.display = 'none';
    }else if(documento_apertura_visita === 'Grabación de apertura'){
        document.getElementById('div_acta_apertura_visita').style.display = 'none';
        document.getElementById('div_grabacion_apertura_visita').style.display = 'block';
    }else{
        document.getElementById('div_acta_apertura_visita').style.display = 'none';
        document.getElementById('div_grabacion_apertura_visita').style.display = 'none';
    }
}

function tipo_firma_informe_final() {
    var forma_firma_documento = document.getElementById('tipo_firma_informe').value;

    if (forma_firma_documento === 'firmar_online') {
        

    }else if(forma_firma_documento === 'cargar_documento_firmado'){
        $('.div_cargar_firma').hide();
        $('.div_documento_firmar').hide();
        $('#div_cargue_informe_firmado').show();
    }
}

function mostrarModalFirmarInformeFinal() {
    $('#modalFirmarInformeFinal').modal('show');

    //TODO: reservado para firma electronica
    /*let enlace_informe_final_intendencia = document.getElementById('enlace_informe_final_intendencia').getAttribute('href');

    let id = $('#id').val();
    let etapa = $('#etapa').val();

    var regex = /\/d\/([a-zA-Z0-9_-]+)\//;
    var match = enlace_informe_final_intendencia.match(regex);

    var id_ruta = match ? match[1] : null;

    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var formData = new FormData();
    formData.append('enlace_informe_final_intendencia', id_ruta);
    formData.append('id', id);
    formData.append('etapa', etapa);

    var heads = {'X-CSRF-TOKEN': token};
    var url = `/pdfDownload`;

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
    }).then(data => {
        const message = data.message;
        document.getElementById('divPdfIframe').innerHTML = `<iframe id="pdfIframe" style="width:100%; height:500px;" src="http://visitasdeinspeccion.com/pdf-viewer?file=docs/${message}"></iframe>`;
    }).catch(error => {
        Swal.fire('Error', error.message, 'error');
    }).finally(() => {
        $('.diagnosticoSubsanado').prop('disabled', false);
    });*/
}

function eliminarAnexoUpdate(button, url_archivo, nombre_archivo) {
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    let id = $('#id').val();
    let numero_informe = $('#numero_informe').val();
    let etapa = $('#etapa').val();
    let estado = $('#estado').val();
    let estado_etapa = $('#estado_etapa').val();
    let razon_social = $('#razon_social').val();
    let url = `/eliminar_archivo_update`;

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

function guardar_documento_adicional_visita_inspeccion() {
    var bandera = false;

    let id = $('#id').val();
    let etapa = $('#etapa').val();
    let estado = $('#estado').val();
    let estado_etapa = $('#estado_etapa').val();
    let numero_informe = $('#numero_informe').val();
    let razon_social = $('#razon_social').val();
    let nit = $('#nit').val();
    let url = `/guardar_documento_adicional_visita_inspeccion`;
    let anexos_adicionales = [];
    var labels = [];

    let resultado_anexos = cargarDocumentosAdicionales('.tr_documentos_adicionales_visita_inspeccion');
    let bandera_anexos = resultado_anexos.bandera;

    if (bandera_anexos) {
        labels.push(resultado_anexos.labels);
        bandera = true;
    }

    anexos_adicionales = resultado_anexos.anexos_adicionales;

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

    $('.sendButton').prop('disabled', true);
    
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var formData = new FormData();
    formData.append('id', id);
    formData.append('etapa', etapa);
    formData.append('estado', estado);
    formData.append('estado_etapa', estado_etapa);
    formData.append('numero_informe', numero_informe);
    formData.append('razon_social', razon_social);
    formData.append('nit', nit);

    var heads = {'X-CSRF-TOKEN': token};

    if (anexos_adicionales.length > 0 ) {
        anexos_adicionales.forEach((item, index) => {
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

function registroComunicadoPrevioVisita() {
    
    var bandera = false;

    var labels = [];
    var anexos_adicionales = [];
    let etapa = $('#etapa').val();
    let estado = $('#estado').val();
    let estado_etapa = $('#estado_etapa').val();
    let numero_informe = $('#numero_informe').val();
    let razon_social = $('#razon_social').val();
    let nit = $('#nit').val();
    let observacion = $('#observaciones_oficio_previo_visita').val();

    var url = `/registrar_comunicado_previo_visita`;
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
    formData.append('observaciones', observacion);

    var heads = {'X-CSRF-TOKEN': token};

    $('.required_requerimiento_previo_visita').each(function() {
        if ($(this).val() === '') {
            var label = $('label[for="' + $(this).attr('id') + '"]').text().replace(' (*)','');
            labels.push(label);
            bandera = true;
        } else {
            formData.append($(this).attr('id'), $(this).val());
        }
    });

    $('.tr_documentos_adicionales_oficio_previo_visita').each(function () {

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
                anexos_adicionales.push(row);
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

    if (anexos_adicionales.length > 0 ) {
        anexos_adicionales.forEach((item, index) => {
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

function registroOficioTraslado() {
    
    var bandera = false;

    var labels = [];
    var anexos_adicionales = [];
    let etapa = $('#etapa').val();
    let estado = $('#estado').val();
    let estado_etapa = $('#estado_etapa').val();
    let numero_informe = $('#numero_informe').val();
    let razon_social = $('#razon_social').val();
    let nit = $('#nit').val();
    let observacion = $('#observaciones_oficio_traslado_visita').val();

    var url = `/registrar_oficio_traslado`;
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
    formData.append('observaciones', observacion);

    var heads = {'X-CSRF-TOKEN': token};

    $('.required_requerimiento_traslado').each(function() {
        if ($(this).val() === '') {
            var label = $('label[for="' + $(this).attr('id') + '"]').text().replace(' (*)','');
            labels.push(label);
            bandera = true;
        } else {
            formData.append($(this).attr('id'), $(this).val());
        }
    });

    $('.tr_documentos_oficio_traslado_visita').each(function () {

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
                anexos_adicionales.push(row);
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

    if (anexos_adicionales.length > 0 ) {
        anexos_adicionales.forEach((item, index) => {
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

    $('.enviarFormulario').prop('disabled', true);

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
        $('.enviarFormulario').prop('disabled', false);
    });
}

function solicitarDiagnostico() {
    var bandera = false;

    let id = document.getElementById('id_entidad').val;
    let observacion = $('#observaciones_solicitar_diagnostico').val().trim();
    let url = `/solicitar_diagnostico`;

    let anexos_diagnostico = [];
    let labels = [];
    
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var formData = new FormData();
    formData.append('id', id);

    var heads = {'X-CSRF-TOKEN': token}

    if (observacion === null || observacion === "" || observacion === undefined ) {
        bandera = true;
        labels.push('Observaciones');
    } else {
        formData.append('observacion', observacion);
    }

    $('.tr_documentos_adicionales_solicitar_diagnostico').each(function () {

        var row = {};
        var banderaText = false;
        var banderaFile = false;

            $(this).find('input[type="text"]').each(function() {
                var key = $(this).attr('name');
                var valueText = $(this).val().trim();
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
            }else if (banderaText && !banderaFile) {
                bandera = true;
                labels.push('Todos los anexos deben tener un documento');
            }else if (!banderaText && banderaFile) {
                bandera = true;
                labels.push('Todos los anexos deben tener un nombre');
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

function abrirModalSolicitarDiagnostico(id, razon_social ) {
    validarSesionDrive();
    document.getElementById('id_entidad').val = null;
    document.getElementById('exampleModalLabel').textContent = "Solicitar diagnóstico";
    document.getElementById('observaciones_solicitar_diagnostico').value = "";
    $('#modalSolicitarDiagnostico').modal('show');
    document.getElementById('id_entidad').val = id;
    document.getElementById('exampleModalLabel').textContent = "Solicitar diagnóstico - " + razon_social;
}

function validarSesionDrive(){
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const heads = {'X-CSRF-TOKEN': token}
    fetch(`/validateDrive`,{
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
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    }).finally(() => {
        $('.enviarObservacion').prop('disabled', false);
    });
}

//////////  Asuntos especiales /////////////

function guardar_observacion_asunto_especial(accion) {
    
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
    let url = `/guardar_observacion_asunto_especial`;
    
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

function guardar_documento_adicional_asunto_especial() {
    var bandera = false;

    let id = $('#id').val();
    let etapa = $('#etapa').val();
    let estado = $('#estado').val();
    let estado_etapa = $('#estado_etapa').val();
    let numero_informe = $('#numero_informe').val();
    let razon_social = $('#razon_social').val();
    let nit = $('#nit').val();
    let url = `/guardar_documento_adicional_asunto_especial`;
    let anexos_adicionales = [];
    var labels = [];

    let resultado_anexos = cargarDocumentosAdicionales('.tr_documentos_adicionales_asuntos_especiales');
    let bandera_anexos = resultado_anexos.bandera;

    if (bandera_anexos) {
        labels.push(resultado_anexos.labels);
        bandera = true;
    }

    anexos_adicionales = resultado_anexos.anexos_adicionales;

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

    $('.sendButton').prop('disabled', true);
    
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var formData = new FormData();
    formData.append('id', id);
    formData.append('etapa', etapa);
    formData.append('estado', estado);
    formData.append('estado_etapa', estado_etapa);
    formData.append('numero_informe', numero_informe);
    formData.append('razon_social', razon_social);
    formData.append('nit', nit);

    var heads = {'X-CSRF-TOKEN': token};

    if (anexos_adicionales.length > 0 ) {
        anexos_adicionales.forEach((item, index) => {
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

function guardar_memorando_traslado_grupo_asuntos_especiales() {
    var bandera = false;

    var labels = [];
    let id = $('#id').val();
    let etapa = $('#etapa').val();
    let estado = $('#estado').val();
    let estado_etapa = $('#estado_etapa').val();
    let numero_informe = $('#numero_informe').val();
    let razon_social = $('#razon_social').val();
    let nit = $('#nit').val();
    let url = `/guardar_memorando_traslado_grupo_asuntos_especiales`;
    let observaciones = $('#observaciones_memorando_traslado_grupo_asuntos_especiales').val();
    let ciclo_vida_memorando_traslado_grupo_asuntos_especiales = $('#ciclo_vida_memorando_traslado_grupo_asuntos_especiales').val();
    let anexos_adicionales = [];

    if (ciclo_vida_memorando_traslado_grupo_asuntos_especiales === '') {
        labels.push('Ciclo de vida del memorando de traslado al grupo de asuntos especiales');
        bandera = true;
    }
    
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
    formData.append('ciclo_vida_memorando_traslado_grupo_asuntos_especiales', ciclo_vida_memorando_traslado_grupo_asuntos_especiales);

    var heads = {'X-CSRF-TOKEN': token}

    let resultado_anexos = cargarDocumentosAdicionales('.tr_documentos_adicionales_trasladar_memorando_grupo_asuntos_especiales');
    let bandera_anexos = resultado_anexos.bandera;

    if (bandera_anexos) {
        labels.push(resultado_anexos.labels);
        bandera = true;
    }

    anexos_adicionales = resultado_anexos.anexos_adicionales;

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

    if (anexos_adicionales.length > 0 ) {
        anexos_adicionales.forEach((item, index) => {
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


// Maestro entidades

function estadoMatriculaRues() {
    let estado_matricula = document.getElementById('estado_matricula_rues').value;

    if (estado_matricula === 'ACTIVA') {
        $('.permiten_notificacion_correo_electronico').show();
        $('.en_liquidacion_rues').show();
        $('.entidad_que_vigila_rues').show();
        $('.objeto_social').show();
        $('.ecomun').show();
        $('.cafetera').show();
        $('.vigilada_supersolidaria_segun_depuracion_crear').show();
    }else{
        $('.permiten_notificacion_correo_electronico').hide();
        $('.correo_notificaciones_judiciales').hide();
        $('.en_liquidacion_rues').hide();
        $('.tipo_liquidacion_rues').hide();
        $('.otro_tipo_liquidacion').hide();
        $('.entidad_que_vigila_rues').hide();
        $('.objeto_social').hide();
        $('.ecomun').hide();
        $('.cafetera').hide();
        $('.vigilada_supersolidaria_segun_depuracion_crear').hide();
        $('.entidad_debe_vigilar_segun_depuracion').hide();
        $('.otro_ente_vigilancia').hide();
    }

    permitenNotificacionCorreoElectronico();
    enLiquidacionRues();
    otroTipoLiquidacion();
    vigiladaSupersolidariaSegunDepuracion();
    otroEnteVigilancia();
}

function permitenNotificacionCorreoElectronico() {
    let permiten_notificacion_correo_electronico = document.getElementById('permiten_notificacion_correo_electronico').value;

    if (permiten_notificacion_correo_electronico === 'NO INDICA' ) {
        $('.correo_notificaciones_judiciales').show();
        $('#label_correo_notificaciones_judiciales').text('Correo para notificaciones judiciales');
    }else if(permiten_notificacion_correo_electronico === 'SI'){
        $('.correo_notificaciones_judiciales').show();
        $('#label_correo_notificaciones_judiciales').text('Correo para notificaciones judiciales (*)');
    }
    else{
        $('.correo_notificaciones_judiciales').hide();
    }
}

function enLiquidacionRues() {
    let en_liquidacion_rues = document.getElementById('en_liquidacion_rues').value;

    if (en_liquidacion_rues === 'SI') {
        $('.tipo_liquidacion_rues').show();
    }else{
        $('.tipo_liquidacion_rues').hide();
    }
}

function otroTipoLiquidacion() {
    let tipo_liquidacion_rues = document.getElementById('tipo_liquidacion_rues').value;

    if (tipo_liquidacion_rues === 'OTRA') {
        $('.otro_tipo_liquidacion').show();
    }else{
        $('.otro_tipo_liquidacion').hide();
    }
}

function enteVigilanciaRUES() {
    let entidad_que_vigila_rues = document.getElementById('entidad_que_vigila_rues').value;

    if (entidad_que_vigila_rues === 'OTRA') {
        $('.otro_ente_vigilancia_rues').show();
    }else{
        $('.otro_ente_vigilancia_rues').hide();
    }
}

function vigiladaSupersolidariaSegunDepuracion() {
    let vigilada_supersolidaria_segun_depuracion = document.getElementById('vigilada_supersolidaria_segun_depuracion_crear').value;

    if (vigilada_supersolidaria_segun_depuracion === 'NO') {
        $('.entidad_debe_vigilar_segun_depuracion').show();  
    }else{
        $('.entidad_debe_vigilar_segun_depuracion').hide();
    }
}

function otroEnteVigilancia() {
    let entidad_debe_vigilar_segun_depuracion = document.getElementById('entidad_debe_vigilar_segun_depuracion').value;

    if (entidad_debe_vigilar_segun_depuracion === 'OTRA') {
        $('.otro_ente_vigilancia').show();
    }else{
        $('.otro_ente_vigilancia').hide();
    }
}

function crearEntidadMaestra() {
    var bandera = false;

    var labels = [];
    let codigo_entidad = $('#codigo_entidad').val();
    let nit = $('#nit').val();
    let razon_social = $('#razon_social').val();
    let sigla = $('#sigla').val();
    let nivel_supervision = $('#nivel_supervision').val();
    let naturaleza_organizacion = $('#naturaleza_organizacion').val();
    let tipo_organizacion = $('#tipo_organizacion').val();
    let categoria = $('#categoria').val();
    let grupo_niif = $('#grupo_niif').val();
    let departamento = $('#departamento').val();
    let ciudad_municipio = $('#ciudad_municipio').val();
    let direccion = $('#direccion').val();
    let numero_asociados = $('#numero_asociados').val();
    let numero_empleados = $('#numero_empleados').val();
    let total_activos = $('#total_activos').val();
    let total_pasivos = $('#total_pasivos').val();
    let total_patrimonio = $('#total_patrimonio').val();
    let total_ingresos = $('#total_ingresos').val();
    let fecha_ultimo_reporte = $('#fecha_ultimo_reporte').val();
    let estado_matricula_rues = $('#estado_matricula_rues').val();
    let ano_renovacion_matricula = $('#ano_renovacion_matricula').val();
    let fecha_renovacion_matricula = $('#fecha_renovacion_matricula').val();
    let permiten_notificacion_correo_electronico = $('#permiten_notificacion_correo_electronico').val();
    let correo_notificaciones_judiciales = $('#correo_notificaciones_judiciales').val();
    let en_liquidacion_rues = $('#en_liquidacion_rues').val();
    let tipo_liquidacion_rues = $('#tipo_liquidacion_rues').val();
    let otro_tipo_liquidacion = $('#otro_tipo_liquidacion').val();
    let entidad_que_vigila_rues = $('#entidad_que_vigila_rues').val();
    let otro_ente_vigilancia_rues = $('#otro_ente_vigilancia_rues').val();
    let objeto_social = $('#objeto_social').val();
    let ecomun = $('#ecomun').val();
    let cafetera = $('#cafetera').val();
    let vigilada_supersolidaria_segun_depuracion_crear = $('#vigilada_supersolidaria_segun_depuracion_crear').val();
    let entidad_debe_vigilar_segun_depuracion = $('#entidad_debe_vigilar_segun_depuracion').val();
    let otro_ente_vigilancia = $('#otro_ente_vigilancia').val();
    let certificado_rues = $('#certificado_rues').val();
    let representate_legal = $('#representate_legal').val();
    let correo_representate_legal = $('#correo_representate_legal').val();
    let telefono_representate_legal = $('#telefono_representate_legal').val();
    let tipo_revisor_fiscal = $('#tipo_revisor_fiscal').val();
    let razon_social_revision_fiscal = $('#razon_social_revision_fiscal').val();
    let nombre_revisor_fiscal = $('#nombre_revisor_fiscal').val();
    let direccion_revisor_fiscal = $('#direccion_revisor_fiscal').val();
    let telefono_revisor_fiscal = $('#telefono_revisor_fiscal').val();
    let correo_revisor_fiscal = $('#correo_revisor_fiscal').val();
    let observaciones = $('#observaciones').val();
    let url = `/crear_entidad_maestra`;

    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var formData = new FormData();

    if (nit === '') {
        labels.push('Nit');
        bandera = true;
    }

    if (razon_social === '') {
        labels.push('Razón social');
        bandera = true;
    }

    if (departamento === '') {
        labels.push('Departamento');
        bandera = true;
    }

    if (ciudad_municipio === '') {
        labels.push('Ciudad / Municipio');
        bandera = true;
    }

    if (direccion === '') {
        labels.push('Dirección');
        bandera = true;
    }

    if (estado_matricula_rues === '') {
        labels.push('Estado de la matricula');
        bandera = true;
    }

    if (ano_renovacion_matricula === '') {
        labels.push('Último año de renovación de matricula');
        bandera = true;
    }

    if (fecha_renovacion_matricula === '') {
        labels.push('Fecha de renovación de matricula');
        bandera = true;
    }

    if (estado_matricula_rues === 'ACTIVA' && permiten_notificacion_correo_electronico === '') {
        labels.push('¿Permiten notificaciones por correo electrónico?');
        bandera = true;
    }

    if (estado_matricula_rues === 'ACTIVA' && permiten_notificacion_correo_electronico === 'SI' && correo_notificaciones_judiciales === '') {
        labels.push('Correo para notificaciones judiciales');
        bandera = true;
    }

    if (estado_matricula_rues === 'ACTIVA' && en_liquidacion_rues === '' ) {
        labels.push('En liquidación en RUES');
        bandera = true;
    }

    if (estado_matricula_rues === 'ACTIVA' && en_liquidacion_rues === 'SI' && tipo_liquidacion_rues === '' ) {
        labels.push('Tipo de liquidación en RUES');
        bandera = true;
    }

    if (estado_matricula_rues === 'ACTIVA' && en_liquidacion_rues === 'SI' && tipo_liquidacion_rues === 'OTRA' && otro_tipo_liquidacion === '') {
        labels.push('Otro tipo de liquidación');
        bandera = true;
    }

    if (estado_matricula_rues === 'ACTIVA' && entidad_que_vigila_rues === '') {
        labels.push('Entidad que vigila según RUES');
        bandera = true;
    }

    if (estado_matricula_rues === 'ACTIVA' && entidad_que_vigila_rues === 'OTRA' && otro_ente_vigilancia_rues === '') {
        labels.push('Otro ente de vigilancia');
        bandera = true;
    }

    if (estado_matricula_rues === 'ACTIVA' && objeto_social === '') {
        labels.push('Objeto social');
        bandera = true;
    }

    if (estado_matricula_rues === 'ACTIVA' && ecomun === '') {
        labels.push('¿Esta entidad es ecomun?');
        bandera = true;
    }

    if (estado_matricula_rues === 'ACTIVA' && cafetera === '') {
        labels.push('¿Esta entidad es cafetera?');
        bandera = true;
    }

    if (estado_matricula_rues === 'ACTIVA' && vigilada_supersolidaria_segun_depuracion_crear === '') {
        labels.push('¿Debe vigilar la superintendencia de la economía solidaria según el resultado de la depuración?');
        bandera = true;
    }

    if (estado_matricula_rues === 'ACTIVA' && vigilada_supersolidaria_segun_depuracion_crear === 'NO' && entidad_debe_vigilar_segun_depuracion === '') {
        labels.push('Entidad que debe vigilar según depuración');
        bandera = true;
    }

    if (estado_matricula_rues === 'ACTIVA' && vigilada_supersolidaria_segun_depuracion_crear === 'NO' && entidad_debe_vigilar_segun_depuracion === 'OTRA' && otro_ente_vigilancia === '') {
        labels.push('Otro ente de vigilancia');
        bandera = true;
    }

    var fileInput = document.getElementById('certificado_rues');
    var file = fileInput.files[0];
    if (file) {
        formData.append('certificado_rues', file);
    } else {
        labels.push('Certificado RUES');
        bandera = true;
    }

    if (ano_renovacion_matricula === '') {
        labels.push('representate_legal');
        bandera = true;
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

    formData.append('codigo_entidad', codigo_entidad);
    formData.append('nit', nit);
    formData.append('razon_social', razon_social);
    formData.append('sigla', sigla);
    formData.append('nivel_supervision', nivel_supervision);
    formData.append('naturaleza_organizacion', naturaleza_organizacion);
    formData.append('tipo_organizacion', tipo_organizacion);
    formData.append('grupo_niif', grupo_niif);
    formData.append('departamento', departamento);
    formData.append('ciudad_municipio', ciudad_municipio);
    formData.append('direccion', direccion);
    formData.append('numero_asociados', numero_asociados);
    formData.append('numero_empleados', numero_empleados);
    formData.append('total_activos', total_activos);
    formData.append('total_pasivos', total_pasivos);
    formData.append('total_patrimonio', total_patrimonio);
    formData.append('total_ingresos', total_ingresos);
    formData.append('fecha_ultimo_reporte', fecha_ultimo_reporte);

    formData.append('estado_matricula_rues', estado_matricula_rues);
    formData.append('ano_renovacion_matricula', ano_renovacion_matricula);
    formData.append('fecha_renovacion_matricula', fecha_renovacion_matricula);
    formData.append('permiten_notificacion_correo_electronico', permiten_notificacion_correo_electronico);
    formData.append('correo_notificaciones_judiciales', correo_notificaciones_judiciales);
    formData.append('en_liquidacion_rues', en_liquidacion_rues);
    formData.append('tipo_liquidacion_rues', tipo_liquidacion_rues);
    formData.append('otro_tipo_liquidacion', otro_tipo_liquidacion);
    formData.append('entidad_que_vigila_rues', entidad_que_vigila_rues);
    formData.append('otro_ente_vigilancia_rues', otro_ente_vigilancia_rues);
    formData.append('objeto_social', objeto_social);
    formData.append('ecomun', ecomun);
    formData.append('cafetera', cafetera);
    formData.append('vigilada_supersolidaria_segun_depuracion_crear', vigilada_supersolidaria_segun_depuracion_crear);
    formData.append('entidad_debe_vigilar_segun_depuracion', entidad_debe_vigilar_segun_depuracion);
    formData.append('otro_ente_vigilancia', otro_ente_vigilancia);
    formData.append('certificado_rues', certificado_rues);

    formData.append('representate_legal', representate_legal);
    formData.append('correo_representate_legal', correo_representate_legal);
    formData.append('telefono_representate_legal', telefono_representate_legal);
    formData.append('tipo_revisor_fiscal', tipo_revisor_fiscal);
    formData.append('razon_social_revision_fiscal', razon_social_revision_fiscal);
    formData.append('nombre_revisor_fiscal', nombre_revisor_fiscal);
    formData.append('direccion_revisor_fiscal', direccion_revisor_fiscal);
    formData.append('telefono_revisor_fiscal', telefono_revisor_fiscal);
    formData.append('correo_revisor_fiscal', correo_revisor_fiscal);
    formData.append('observaciones', observaciones);
    formData.append('categoria', categoria);

    var heads = {'X-CSRF-TOKEN': token}

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

function solo_numeros(event) {
    const key = event.key;
    const allowedKeys = ['Backspace', 'Tab', 'ArrowLeft', 'ArrowRight', 'Delete', 'Enter'];

    if (!/^[0-9]$/.test(key) && !allowedKeys.includes(key)) {
        event.preventDefault();
    }
}

function confirmacionCargueDocs() {
    const checkboxes = document.querySelectorAll('.check-send-condition');
    const buttons = document.querySelectorAll('.btn-send-condition');

    checkboxes.forEach((checkbox, index) => {
        const button = buttons[index]; 
        if (checkbox.checked) {
            button.disabled = false;
        } else {
            button.disabled = true;
        }
    });
}
