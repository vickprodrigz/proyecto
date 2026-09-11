<?php
session_start();
require_once 'conexion.php';

// Verificamos si el usuario ha iniciado sesión y si es Supervisor o Administrador
if (!isset($_SESSION['id']) || (trim($_SESSION['cargo']) != 'Supervisor' && trim($_SESSION['cargo']) != 'Administrador')) {
    header("Location: login.php");
    exit();
}

$mensaje = "";
$id_usuario_actual = $_SESSION['id']; // ID general para las acciones

// --- 1. PROCESAR ASIGNACIÓN AUTOMÁTICA ALEATORIA ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['asignar_automatico'])) {
    try {
        $conexion->beginTransaction();

        // Obtener vehículos activos sin chofer asignado
        $stmtV = $conexion->query("SELECT id FROM flota WHERE estado = 'Activo' AND (id_chofer IS NULL OR id_chofer = 0)");
        $vehiculos = $stmtV->fetchAll(PDO::FETCH_COLUMN);

        // Obtener choferes libres (que no tengan un vehículo activo asignado)
        $stmtC = $conexion->query("
            SELECT id FROM usuario 
            WHERE cargo = 'Chofer' 
            AND id NOT IN (SELECT DISTINCT id_chofer FROM flota WHERE id_chofer IS NOT NULL AND estado = 'Activo')
        ");
        $choferes = $stmtC->fetchAll(PDO::FETCH_COLUMN);

        // Mezclar aleatoriamente ambos arreglos
        shuffle($vehiculos);
        shuffle($choferes);

        $asignados = 0;
        $limite = min(count($vehiculos), count($choferes));

        for ($i = 0; $i < $limite; $i++) {
            $id_v = $vehiculos[$i];
            $id_c = $choferes[$i];

            $update = $conexion->prepare("UPDATE flota SET id_chofer = :id_chofer WHERE id = :id_vehiculo");
            $update->execute([':id_chofer' => $id_c, ':id_vehiculo' => $id_v]);
            $asignados++;
        }

        $conexion->commit();
        $mensaje = "<div class='alert alert-success fw-bold'>¡Asignación automática aleatoria completada con éxito! Se vincularon $asignados vehículos a choferes libres.</div>";
    } catch (PDOException $e) {
        $conexion->rollBack();
        $mensaje = "<div class='alert alert-danger fw-bold'>Error en la asignación automática: " . $e->getMessage() . "</div>";
    }
}

// --- 2. PROCESAR FORMULARIO DE MANTENIMIENTO PREVENTIVO ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['registrar_preventivo'])) {
    $id_vehiculo = $_POST['id_vehiculo'];
    $comentario = trim($_POST['comentario']);
    $tipo_falla = "Preventivo"; 

    if (!empty($id_vehiculo) && !empty($comentario)) {
        try {
            $stmt = $conexion->prepare("CALL sp_RegistrarMantenimiento(:id_usuario, :id_vehiculo, :tipo_falla, :comentario)");
            $stmt->bindParam(':id_usuario', $id_usuario_actual, PDO::PARAM_INT);
            $stmt->bindParam(':id_vehiculo', $id_vehiculo, PDO::PARAM_INT);
            $stmt->bindParam(':tipo_falla', $tipo_falla, PDO::PARAM_STR);
            $stmt->bindParam(':comentario', $comentario, PDO::PARAM_STR);
            $stmt->execute();
            $stmt->closeCursor();

            // Liberar el chofer del vehículo al entrar en mantenimiento preventivo
            $stmtLiberar = $conexion->prepare("UPDATE flota SET id_chofer = NULL WHERE id = :id_vehiculo");
            $stmtLiberar->execute([':id_vehiculo' => $id_vehiculo]);

            $mensaje = "<div class='alert alert-danger fw-bold'>Mantenimiento preventivo programado con éxito. El vehículo pasó a estado 'Inactivo'.</div>";
        } catch (PDOException $e) {
            $mensaje = "<div class='alert alert-danger fw-bold'>Error al registrar preventivo: " . $e->getMessage() . "</div>";
        }
    } else {
        $mensaje = "<div class='alert alert-danger fw-bold'>Por favor, completa todos los campos del formulario.</div>";
    }
}

// --- 3. CONSULTAR ESTADO DE LA FLOTA Y CHOFERES ASIGNADOS ---
try {
    $stmtVehiculos = $conexion->query("
        SELECT f.id, f.marca, f.modelo, f.placa, f.kilometraje, f.estado, u.nombres AS chofer_asignado
        FROM flota f
        LEFT JOIN usuario u ON f.id_chofer = u.id
    ");
    $lista_vehiculos = $stmtVehiculos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $lista_vehiculos = [];
}

// --- 4. OBTENER VEHÍCULOS ACTIVOS (Para el select preventivo) ---
try {
    $stmtActivos = $conexion->query("SELECT id, marca, modelo, placa FROM flota WHERE estado = 'Activo'");
    $vehiculos_activos = $stmtActivos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $vehiculos_activos = [];
}

// --- 5. BUSCADOR E HISTORIAL UNIFICADO DE MANTENIMIENTOS ---
$busqueda = trim($_GET['busqueda'] ?? '');
try {
    $sqlMantenimientos = "
        SELECT 
            m.id, 
            m.tipo_falla, 
            m.comentario, 
            m.fecha, 
            m.estado_mantenimiento,
            f.marca, 
            f.modelo, 
            f.placa, 
            u_chofer.nombres AS reportado_por,
            u_tecnico.nombres AS tecnico
        FROM mantenimiento m
        JOIN flota f ON m.id_vehiculo = f.id
        LEFT JOIN usuario u_chofer ON m.id_chofer = u_chofer.id
        LEFT JOIN usuario u_tecnico ON m.id_tecnico_asignado = u_tecnico.id
    ";

    if (!empty($busqueda)) {
        $sqlMantenimientos .= " WHERE f.placa LIKE :busq OR f.marca LIKE :busq OR f.modelo LIKE :busq OR u_chofer.nombres LIKE :busq OR m.tipo_falla LIKE :busq OR m.estado_mantenimiento LIKE :busq";
        $stmtMantenimientos = $conexion->prepare($sqlMantenimientos);
        $stmtMantenimientos->execute([':busq' => "%$busqueda%"]);
    } else {
        $sqlMantenimientos .= " ORDER BY m.fecha DESC LIMIT 15";
        $stmtMantenimientos = $conexion->query($sqlMantenimientos);
    }

    $lista_mantenimientos = $stmtMantenimientos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $lista_mantenimientos = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel del Supervisor - Sistema de Mantenimiento</title>
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container my-4" style="max-width: 1000px;">
    <!-- Cabecera -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Panel de Supervisión</h2>
        <div>
            <?php if (trim($_SESSION['cargo']) == 'Administrador'): ?>
                <!-- Botón exclusivo para regresar al panel de admin si el usuario actual es Admin -->
                <a href="admin.php" class="btn btn-outline-primary btn-sm me-2 fw-bold">⬅️ Volver a Admin</a>
            <?php endif; ?>
            <a href="logout.php" class="btn btn-danger btn-sm">Cerrar Sesión</a>
        </div>
    </div>
    
    <div class="alert alert-secondary shadow-sm">
        <p class="mb-1"><strong>Bienvenido, <?php echo htmlspecialchars($_SESSION['nombres']); ?></strong></p>
        <p class="mb-0">Cargo: <?php echo htmlspecialchars($_SESSION['cargo']); ?></p>
    </div>

    <?php echo $mensaje; ?>

    <!-- SECCIÓN: Asignación Automática Aleatoria -->
    <div class="card shadow-sm mb-4 border-success">
        <div class="card-header bg-success text-white fw-bold">
            Asignación Automática Aleatoria de Vehículos
        </div>
        <div class="card-body">
            <p class="text-muted small">Haz clic en el botón para vincular de forma completamente aleatoria los vehículos activos disponibles con los choferes libres.</p>
            <form action="" method="POST">
                <button type="submit" name="asignar_automatico" class="btn btn-success w-100 fw-bold">Ejecutar Asignación Aleatoria</button>
            </form>
        </div>
    </div>

    <!-- SECCIÓN: Programar Mantenimiento Preventivo -->
    <div class="card shadow-sm mb-4 border-primary">
        <div class="card-header bg-primary text-white fw-bold">
            Programar Mantenimiento Preventivo
        </div>
        <div class="card-body">
            <form action="" method="POST">
                <input type="hidden" name="registrar_preventivo" value="1">
                
                <div class="mb-3">
                    <label class="form-label fw-bold">1. Seleccionar Vehículo Activo</label>
                    <select name="id_vehiculo" class="form-select" required>
                        <option value="">-- Elige un vehículo disponible --</option>
                        <?php foreach ($vehiculos_activos as $va): ?>
                            <option value="<?php echo $va['id']; ?>">
                                <?php echo htmlspecialchars($va['marca'] . " " . $va['modelo'] . " - Placa: " . $va['placa']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">2. Detalle / Motivo Preventivo</label>
                    <textarea name="comentario" class="form-control" rows="2" required placeholder="Ej. Cambio de aceite, revisión general..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary w-100">Registrar y Enviar a Taller</button>
            </form>
        </div>
    </div>

    <!-- SECCIÓN 1: Estado General de la Flota y Asignaciones -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h3 class="h5 card-title mb-3">Estado Actual de la Flota y Choferes Asignados</h3>
            <div class="table-responsive">
                <table class="table table-striped table-bordered align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Vehículo</th>
                            <th>Placa</th>
                            <th>Kilometraje</th>
                            <th>Chofer Asignado</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($lista_vehiculos)): ?>
                            <tr><td colspan="6" class="text-center">No hay vehículos registrados.</td></tr>
                        <?php else: ?>
                            <?php foreach ($lista_vehiculos as $v): ?>
                                <tr>
                                    <td><?php echo $v['id']; ?></td>
                                    <td><?php echo htmlspecialchars($v['marca'] . " " . $v['modelo']); ?></td>
                                    <td><?php echo htmlspecialchars($v['placa']); ?></td>
                                    <td><?php echo number_format($v['kilometraje'], 2, ',', '.'); ?> km</td>
                                    <td>
                                        <?php if (!empty($v['chofer_asignado'])): ?>
                                            <span class="fw-bold text-success"><?php echo htmlspecialchars($v['chofer_asignado']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted fst-italic">Sin asignar</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (strtolower($v['estado']) == 'activo'): ?>
                                            <span class="badge bg-success">Activo</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- SECCIÓN 2: Tabla Unificada de Mantenimientos con Buscador -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h3 class="h5 card-title mb-3">Seguimiento de Reportes y Mantenimientos</h3>
            
            <!-- Buscador -->
            <form method="GET" action="" class="mb-3">
                <div class="input-group">
                    <input type="text" name="busqueda" class="form-control" placeholder="Buscar por placa, marca, modelo, chofer, tipo de falla o estado..." value="<?php echo htmlspecialchars($busqueda); ?>">
                    <button class="btn btn-outline-primary" type="submit">Buscar</button>
                    <a href="supervisor.php" class="btn btn-outline-secondary">Limpiar</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle">
                    <thead class="table-secondary">
                        <tr>
                            <th>Fecha</th>
                            <th>Vehículo</th>
                            <th>Reportado por</th>
                            <th>Tipo / Detalle</th>
                            <th>Estado Mantenimiento</th>
                            <th>Técnico Asignado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($lista_mantenimientos)): ?>
                            <tr><td colspan="6" class="text-center">No se encontraron registros de mantenimientos.</td></tr>
                        <?php else: ?>
                            <?php foreach ($lista_mantenimientos as $m): ?>
                                <tr>
                                    <td><?php echo $m['fecha']; ?></td>
                                    <td><?php echo htmlspecialchars($m['marca'] . " " . $m['modelo'] . " (" . $m['placa'] . ")"); ?></td>
                                    <td><?php echo htmlspecialchars($m['reportado_por'] ?? 'Supervisor (Preventivo)'); ?></td>
                                    <td>
                                        <small class="d-block fw-bold text-danger">
                                            <?php echo htmlspecialchars($m['tipo_falla']); ?>
                                        </small>
                                        <small class="text-muted"><?php echo htmlspecialchars($m['comentario']); ?></small>
                                    </td>
                                    <td>
                                        <?php 
                                            $st = $m['estado_mantenimiento'] ?? 'En Espera';
                                            if ($st == 'En Espera') echo '<span class="badge bg-warning text-dark">En Espera</span>';
                                            elseif ($st == 'En Mantenimiento') echo '<span class="badge bg-info text-dark">En Mantenimiento</span>';
                                            else echo '<span class="badge bg-success">Terminado</span>';
                                        ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($m['tecnico'])): ?>
                                            <span class="fw-bold text-primary"><?php echo htmlspecialchars($m['tecnico']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted fst-italic">Sin asignar</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script src="bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>