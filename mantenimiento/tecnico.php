<?php
session_start();
require_once 'conexion.php';

// Verificar sesión de Técnico
if (!isset($_SESSION['id']) || trim($_SESSION['cargo']) != 'Técnico') {
    header("Location: login.php");
    exit();
}

$mensaje = "";
$id_tecnico = $_SESSION['id'];

// Procesar acciones del técnico
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $accion = $_POST['accion'] ?? '';

    try {
        // ACCIÓN 1: Mover de "En Espera" a "En Proceso"
        if ($accion == 'iniciar_mantenimiento') {
            $id_mantenimiento = $_POST['id_mantenimiento'];

            $stmt = $conexion->prepare("
                UPDATE mantenimiento 
                SET estado_mantenimiento = 'En Proceso',
                    id_tecnico_asignado = :id_tecnico
                WHERE id = :id_mantenimiento
            ");
            $stmt->execute([
                ':id_tecnico' => $id_tecnico,
                ':id_mantenimiento' => $id_mantenimiento
            ]);

            $mensaje = "<div class='alert alert-info alert-dismissible fade show fw-bold' role='alert'>
                            ¡Mantenimiento #{$id_mantenimiento} ingresado a Taller (En Proceso)!
                            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                        </div>";
        }

        // ACCIÓN 2: Registrar procedimiento y/o Finalizar
        if ($accion == 'guardar_procedimiento') {
            $id_mantenimiento = $_POST['id_mantenimiento'];
            $trabajo_realizado = trim($_POST['trabajo_realizado']);
            $nuevo_estado = $_POST['estado_mantenimiento'];
            $id_vehiculo = $_POST['id_vehiculo'];

            $conexion->beginTransaction();

            // Guardar el procedimiento
            $stmt = $conexion->prepare("
                UPDATE mantenimiento 
                SET trabajo_realizado = :trabajo,
                    estado_mantenimiento = :estado,
                    id_tecnico_asignado = :id_tecnico
                WHERE id = :id_mantenimiento
            ");
            $stmt->execute([
                ':trabajo' => $trabajo_realizado,
                ':estado' => $nuevo_estado,
                ':id_tecnico' => $id_tecnico,
                ':id_mantenimiento' => $id_mantenimiento
            ]);

            // Reactivar vehículo si finalizó
            if ($nuevo_estado == 'Finalizado') {
                $stmtFlota = $conexion->prepare("UPDATE flota SET estado = 'Activo' WHERE id = :id_vehiculo");
                $stmtFlota->execute([':id_vehiculo' => $id_vehiculo]);
            }

            $conexion->commit();
            $mensaje = "<div class='alert alert-success alert-dismissible fade show fw-bold' role='alert'>
                            ¡Procedimiento guardado correctamente!
                            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                        </div>";
        }
    } catch (PDOException $e) {
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }
        $mensaje = "<div class='alert alert-danger alert-dismissible fade show fw-bold' role='alert'>
                        Error: " . htmlspecialchars($e->getMessage()) . "
                        <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                    </div>";
    }
}

// 1. MANTENIMIENTOS PENDIENTES (En Espera)
try {
    $sqlPendientes = "
        SELECT m.id AS id_mantenimiento, m.tipo_falla, m.comentario AS reporte_chofer, m.fecha,
               v.marca, v.modelo, v.placa, u.nombres AS chofer_nombre
        FROM mantenimiento m
        INNER JOIN flota v ON m.id_vehiculo = v.id
        LEFT JOIN usuario u ON m.id_chofer = u.id
        WHERE m.estado_mantenimiento = 'En Espera'
        ORDER BY m.fecha ASC
    ";
    $pendientes = $conexion->query($sqlPendientes)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $pendientes = [];
}

// 2. MANTENIMIENTOS EN PROCESO (Taller)
try {
    $sqlEnProceso = "
        SELECT m.id AS id_mantenimiento, m.id_vehiculo, m.tipo_falla, m.comentario AS reporte_chofer,
               m.trabajo_realizado, m.fecha, v.marca, v.modelo, v.placa,
               u.nombres AS chofer_nombre, tec.nombres AS tecnico_nombre
        FROM mantenimiento m
        INNER JOIN flota v ON m.id_vehiculo = v.id
        LEFT JOIN usuario u ON m.id_chofer = u.id
        LEFT JOIN usuario tec ON m.id_tecnico_asignado = tec.id
        WHERE m.estado_mantenimiento = 'En Proceso'
        ORDER BY m.fecha ASC
    ";
    $enProceso = $conexion->query($sqlEnProceso)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $enProceso = [];
}

// 3. HISTORIAL DE MANTENIMIENTOS REALIZADOS (Finalizados)
try {
    $sqlHistorial = "
        SELECT m.id AS id_mantenimiento, m.tipo_falla, m.comentario AS reporte_chofer,
               m.trabajo_realizado, m.fecha, v.marca, v.modelo, v.placa,
               u.nombres AS chofer_nombre, tec.nombres AS tecnico_nombre
        FROM mantenimiento m
        INNER JOIN flota v ON m.id_vehiculo = v.id
        LEFT JOIN usuario u ON m.id_chofer = u.id
        LEFT JOIN usuario tec ON m.id_tecnico_asignado = tec.id
        WHERE m.estado_mantenimiento = 'Finalizado'
        ORDER BY m.fecha DESC
    ";
    $historial = $conexion->query($sqlHistorial)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $historial = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel del Técnico - Mantenimiento</title>
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container my-4">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center p-3 bg-white rounded shadow-sm mb-4">
        <div>
            <h2 class="h4 mb-1 text-primary">Panel de Control Técnico</h2>
            <p class="mb-0 text-muted small">
                Técnico en sesión: <strong><?php echo htmlspecialchars($_SESSION['nombres']); ?></strong>
            </p>
        </div>
        <a href="logout.php" class="btn btn-outline-danger btn-sm">Cerrar Sesión</a>
    </div>

    <?php echo $mensaje; ?>

    <!-- TABLA 1: SOLICITUDES PENDIENTES -->
    <div class="card shadow-sm mb-4 border-warning">
        <div class="card-header bg-warning text-dark fw-bold d-flex justify-content-between align-items-center">
            <span>⏳ 1. Solicitudes Pendientes (En Espera)</span>
            <span class="badge bg-dark"><?php echo count($pendientes); ?></span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th># / Fecha</th>
                            <th>Vehículo</th>
                            <th>Chofer</th>
                            <th>Falla Reportada</th>
                            <th>Estado</th>
                            <th class="text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pendientes)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-3">No hay solicitudes pendientes en espera.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pendientes as $p): ?>
                                <tr>
                                    <td>
                                        <strong>#<?php echo $p['id_mantenimiento']; ?></strong><br>
                                        <small class="text-muted"><?php echo $p['fecha']; ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($p['marca'] . " " . $p['modelo']); ?></strong><br>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($p['placa']); ?></span>
                                    </td>
                                    <td><small><?php echo htmlspecialchars($p['chofer_nombre'] ?? 'N/D'); ?></small></td>
                                    <td>
                                        <span class="fw-bold text-danger"><?php echo htmlspecialchars($p['tipo_falla']); ?></span><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($p['reporte_chofer']); ?></small>
                                    </td>
                                    <td><span class="badge bg-warning text-dark">En Espera</span></td>
                                    <td class="text-center">
                                        <form action="" method="POST">
                                            <input type="hidden" name="accion" value="iniciar_mantenimiento">
                                            <input type="hidden" name="id_mantenimiento" value="<?php echo $p['id_mantenimiento']; ?>">
                                            <button type="submit" class="btn btn-warning btn-sm fw-bold">🛠️ Recibir en Taller</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TABLA 2: MANTENIMIENTOS EN PROCESO (AQUÍ SE INDICA EL PROCEDIMIENTO) -->
    <div class="card shadow-sm mb-4 border-info">
        <div class="card-header bg-info text-dark fw-bold d-flex justify-content-between align-items-center">
            <span>🔧 2. Mantenimientos En Proceso (Taller)</span>
            <span class="badge bg-dark"><?php echo count($enProceso); ?></span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th># / Fecha</th>
                            <th>Vehículo</th>
                            <th>Falla Reportada</th>
                            <th>Técnico Asignado</th>
                            <th style="width: 35%;">Procedimiento / Reparación</th>
                            <th style="width: 15%;">Estado / Guardar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($enProceso)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-3">No hay vehículos actualmente en mantenimiento.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($enProceso as $proc): ?>
                                <tr>
                                    <td>
                                        <strong>#<?php echo $proc['id_mantenimiento']; ?></strong><br>
                                        <small class="text-muted"><?php echo $proc['fecha']; ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($proc['marca'] . " " . $proc['modelo']); ?></strong><br>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($proc['placa']); ?></span>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-danger"><?php echo htmlspecialchars($proc['tipo_falla']); ?></span><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($proc['reporte_chofer']); ?></small>
                                    </td>
                                    <td>
                                        <small class="fw-bold text-primary"><?php echo htmlspecialchars($proc['tecnico_nombre'] ?? 'N/D'); ?></small>
                                    </td>
                                    <form action="" method="POST">
                                        <input type="hidden" name="accion" value="guardar_procedimiento">
                                        <input type="hidden" name="id_mantenimiento" value="<?php echo $proc['id_mantenimiento']; ?>">
                                        <input type="hidden" name="id_vehiculo" value="<?php echo $proc['id_vehiculo']; ?>">
                                        <td>
                                            <textarea name="trabajo_realizado" class="form-control form-control-sm" rows="3" placeholder="Indique detalladamente el procedimiento, repuestos o reparación realizada..." required><?php echo htmlspecialchars($proc['trabajo_realizado'] ?? ''); ?></textarea>
                                        </td>
                                        <td>
                                            <select name="estado_mantenimiento" class="form-select form-select-sm mb-2" required>
                                                <option value="En Proceso" selected>En Proceso</option>
                                                <option value="Finalizado">Finalizado</option>
                                            </select>
                                            <button type="submit" class="btn btn-info btn-sm w-100 fw-bold">💾 Guardar</button>
                                        </td>
                                    </form>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TABLA 3: HISTORIAL GENERAL DE MANTENIMIENTOS REALIZADOS + BUSCADOR -->
    <div class="card shadow-sm mb-4 border-success">
        <div class="card-header bg-success text-white fw-bold d-flex justify-content-between align-items-center">
            <span>✅ 3. Historial de Mantenimientos Realizados</span>
            <span class="badge bg-light text-dark"><?php echo count($historial); ?></span>
        </div>
        <div class="card-body">
            
            <!-- Buscador para Filtrar la Tabla -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <input type="text" id="inputBuscador" class="form-control form-control-sm" placeholder="🔍 Buscar en el historial (placa, vehículo, técnico, chofer, falla)...">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0" id="tablaHistorial">
                    <thead class="table-light">
                        <tr>
                            <th># / Fecha</th>
                            <th>Vehículo</th>
                            <th>Chofer</th>
                            <th>Falla Inicial</th>
                            <th>Procedimiento Realizado</th>
                            <th>Técnico Responsable</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($historial)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-3">Aún no existen mantenimientos registrados como finalizados.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($historial as $h): ?>
                                <tr>
                                    <td>
                                        <strong>#<?php echo $h['id_mantenimiento']; ?></strong><br>
                                        <small class="text-muted"><?php echo $h['fecha']; ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($h['marca'] . " " . $h['modelo']); ?></strong><br>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($h['placa']); ?></span>
                                    </td>
                                    <td><small><?php echo htmlspecialchars($h['chofer_nombre'] ?? 'N/D'); ?></small></td>
                                    <td>
                                        <strong class="text-secondary"><?php echo htmlspecialchars($h['tipo_falla']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($h['reporte_chofer']); ?></small>
                                    </td>
                                    <td>
                                        <small class="fw-bold text-dark"><?php echo htmlspecialchars($h['trabajo_realizado'] ?? 'Sin detalle'); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary"><?php echo htmlspecialchars($h['tecnico_nombre'] ?? 'N/D'); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-success">Finalizado</span>
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
<script>
// Filtro de búsqueda instantánea para la Tabla 3 (Historial)
document.getElementById('inputBuscador').addEventListener('keyup', function() {
    let filtro = this.value.toLowerCase();
    let filas = document.querySelectorAll('#tablaHistorial tbody tr');

    filas.forEach(fila => {
        let texto = fila.textContent.toLowerCase();
        fila.style.display = texto.includes(filtro) ? '' : 'none';
    });
});
</script>
</body>
</html>