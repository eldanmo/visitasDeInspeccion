<x-app-layout>
    <div class="container">
        <h2>Crear usuario</h2>
            <div class="row">
                <div class="col-12 col-sm-12 col-md-4">
                    <label class="form-label">Nombre (*)</label>
                    <input type="text" class="form-control" name="name" id="name" required>
                </div>
                <div class="col-12 col-sm-12 col-md-4">
                    <label class="form-label">Correo (*)</label>
                    <input type="email" class="form-control" name="email" id="email" required>
                </div>
                <div class="col-12 col-sm-12 col-md-4">
                    <label class="form-label" for="rol">Rol (*)</label>
                    <select class="form-control" name="profile" id="profile" required>
                        <option value="">Seleccione</option>
                        <option value="Administrador">Administrador</option>
                        <option value="Coordinador">Coordinación visitas de inspección</option>
                        <option value="Coordinacion asuntos especiales">Coordinacion asuntos especiales</option>
                        <option value="Delegado">Delegado</option>
                        <option value="Intendente">Intendente</option>
                        <option value="Contratista">Inspector</option>
                        <option value="Profesional asuntos especiales">Profesional asuntos especiales</option>
                    </select>
                </div>
            </div>

            <div class="col col-sm-12 text-center mt-3">
                <button class="btn btn-success" onclick="guardarUsuario()" >Guardar</button>
            </div>
    </div>
</x-app-layout>
