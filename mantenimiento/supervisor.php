<?php
session_start();
require_once 'conexion.php';

// Verificamos si el usuario ha iniciado sesión y si es Supervisor
if (!isset($_SESSION['id']) || $_SESSION['cargo'] != 'Supervisor') {
    header("Location: login.php");
    exit();
}

$mensaje = "";

// 1. Consultar la lista de vehículos y su estado usando el procedimiento almacenado
try {
    $stmtVehiculos = $conexion->query("CALL sp_VerEstadoVehiculos()");
    $lista_vehiculos = $stmtVehiculos->fetchAll(PDO::FETCH_ASSOC);
    $stmtVehiculos->closeCursor(); // Muy importante cerrar el cursor en llamadas CALL con PDO
} catch (PDOException $e) {
    $lista_vehiculos = [];
}

// 2. Consultar el historial reciente de mantenimientos reportados
try {
    $stmtMantenimientos = $conexion->query("
        SELECT m.id, m.tipo_falla, m.comentario, m.fecha, f.marca, f.modelo, f.placa, u.nombres AS chofer 
        FROM mantenimiento m
        JOIN flota f ON m.id_vehiculo = f.id
        JOIN usuarios u ON m.id_tecnico = u.id
        ORDER BY m.fecha DESC 
        LIMIT 10
    ");
    $lista_mantenimientos = $stmtMantenimientos->fetchAll(PDO::FETCH_ASSOC);
    $stmtMantenimientos->closeCursor();
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

<div class="container my-4" style="max-width: 900px;">
    <!-- Cabecera -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Panel del Supervisor</h2>
        <a href="logout.php" class="btn btn-danger btn-sm">Cerrar Sesión</a>
    </div>
    
    <div class="alert alert-secondary">
        <p class="mb-1"><strong>Bienvenido, <?php echo htmlspecialchars($_SESSION['nombres']); ?></strong></p>
        <p class="mb-0">Cargo: Supervisor</p>
    </div>

    <?php echo $mensaje; ?>

    <!-- SECCIÓN 1: Estado de la Flota -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h3 class="h5 card-title mb-3">1. Estado Actual de la Flota (Activo / Inactivo)</h3>
            <div class="table-responsive">
                <table class="table table-striped table-bordered align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Vehículo</th>
                            <th>Placa</th>
                            <th>Kilometraje</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($lista_vehiculos)): ?>
                            <tr><td colspan="5" class="text-center">No hay vehículos registrados.</td></tr>
                        <?php else: ?>
                            <?php foreach ($lista_vehiculos as $v): ?>
                                <tr>
                                    <td><?php echo $v['id']; ?></td>
                                    <td><?php echo htmlspecialchars($v['marca'] . " " . $v['modelo']); ?></td>
                                    <td><?php echo htmlspecialchars($v['placa']); ?></td>
                                    <td><?php echo number_format($v['kilometraje'], 2, ',', '.'); ?> km</td>
                                    <td>
                                        <?php if (strtolower($v['estado']) == 'activo'): ?>
                                            <span class="badge bg-success">Activo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactivo</span>
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

    <!-- SECCIÓN 2: Últimos Mantenimientos / Fallas Reportadas -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h3 class="h5 card-title mb-3">2. Últimos Reportes de Fallas y Mantenimientos</h3>
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle">
                    <thead class="table-secondary">
                        <tr>
                            <th>Fecha</th>
                            <th>Vehículo</th>
                            <th>Chofer / Reportado por</th>
                            <th>Tipo</th>
                            <th>Comentario</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($lista_mantenimientos)): ?>
                            <tr><td colspan="5" class="text-center">No hay reportes de fallas registrados.</td></tr>
                        <?php else: ?>
                            <?php foreach ($lista_mantenimientos as $m): ?>
                                <tr>
                                    <td><?php echo $m['fecha']; ?></td>
                                    <td><?php echo htmlspecialchars($m['marca'] . " " . $m['modelo'] . " (" . $m['placa'] . ")"); ?></td>
                                    <td><?php echo htmlspecialchars($m['chofer']); ?></td>
                                    <td><span class="badge bg-danger"><?php echo htmlspecialchars($m['tipo_falla']); ?></span></td>
                                    <td><?php echo htmlspecialchars($m['comentario']); ?></td>
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