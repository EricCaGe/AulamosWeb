<!-- ========================================== -->
<!-- MODAL PARA NUEVO / EDITAR GRUPO           -->
<!-- ========================================== -->
<div class="modal-overlay" id="modalGrupo">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitulo">Nuevo grupo</h2>
            <button class="modal-cerrar" id="modalCerrar">&times;</button>
        </div>
        <form id="formGrupo" method="POST" action="logica/procesar_grupos.php">
            <input type="hidden" name="accion" id="modalAccion" value="guardar">
            <input type="hidden" name="id" id="modalId" value="">

            <div class="form-group">
                <label for="modalCiclo">Ciclo escolar <span class="text-danger">*</span></label>
                <select id="modalCiclo" name="id_ciclo" required>
    <?php
    $ciclos = $conexion->query("SELECT id_ciclo, nombre FROM ciclos_escolares WHERE estado = 'Activo' ORDER BY fecha_inicio DESC");
    if ($ciclos && $ciclos->num_rows > 0) {
        while ($ciclo = $ciclos->fetch_assoc()):
    ?>
    <option value="<?php echo $ciclo['id_ciclo']; ?>">
        <?php echo htmlspecialchars($ciclo['nombre']); ?>
    </option>
    <?php
        endwhile;
    } else {
        echo '<option value="">No hay ciclos disponibles</option>';
    }
    ?>
</select>
            </div>

            <div class="form-group">
                <label for="modalDocente">Docente a cargo <span class="text-danger">*</span></label>
               <select id="modalDocente" name="id_docente" required>
    <?php
    $docentes = $conexion->query("
        SELECT u.id_usuario, u.nombre, u.apellido_paterno, u.apellido_materno 
        FROM usuarios u
        INNER JOIN usuario_roles ur ON u.id_usuario = ur.id_usuario
        WHERE ur.id_rol = 2 AND u.estado = 'Activo'
        ORDER BY u.nombre
    ");
    if ($docentes && $docentes->num_rows > 0) {
        while ($docente = $docentes->fetch_assoc()):
    ?>
    <option value="<?php echo $docente['id_usuario']; ?>">
        <?php echo htmlspecialchars($docente['nombre'] . ' ' . $docente['apellido_paterno'] . ' ' . $docente['apellido_materno']); ?>
    </option>
    <?php
        endwhile;
    } else {
        echo '<option value="">No hay docentes disponibles</option>';
    }
    ?>
</select>
            </div>

            <div class="form-group">
                <label for="modalNombre">Nombre del grupo <span class="text-danger">*</span></label>
                <input type="text" id="modalNombre" name="nombre" placeholder="Ej. A" required>
            </div>

            <div class="form-group">
                <label>Grado escolar</label>
                <div class="radio-group radio-inline">
                    <label>
                        <input type="radio" name="grado" value="1°" checked> 1°
                    </label>
                    <label>
                        <input type="radio" name="grado" value="2°"> 2°
                    </label>
                    <label>
                        <input type="radio" name="grado" value="3°"> 3°
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label>Turno</label>
                <div class="radio-group radio-inline">
                    <label>
                        <input type="radio" name="turno" value="Matutino" checked> Matutino
                    </label>
                    <label>
                        <input type="radio" name="turno" value="Vespertino"> Vespertino
                    </label>
                    <label>
                        <input type="radio" name="turno" value="Mixto"> Mixto
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label>Modalidad</label>
                <div class="radio-group radio-inline">
                    <label>
                        <input type="radio" name="modalidad" value="Presencial" checked> Presencial
                    </label>
                    <label>
                        <input type="radio" name="modalidad" value="Hibrida"> Híbrida
                    </label>
                    <label>
                        <input type="radio" name="modalidad" value="Virtual"> Virtual
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label for="modalCupo">Cupo máximo</label>
                <input type="number" id="modalCupo" name="cupo_maximo" placeholder="30" value="30" min="1">
            </div>

            <div class="form-group">
                <label>Estado</label>
                <div class="radio-group">
                    <label>
                        <input type="radio" name="estado" value="Activo" checked>
                        <i class="fa-solid fa-circle-check"></i> Activo
                    </label>
                    <label>
                        <input type="radio" name="estado" value="Inactivo">
                        <i class="fa-solid fa-circle-xmark"></i> Inactivo
                    </label>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn-cancelar" id="modalCancelar">Cancelar</button>
                <button type="submit" class="btn-guardar">Guardar</button>
            </div>
        </form>
    </div>
</div>