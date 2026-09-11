<?php
session_start();
require_once 'conexion.php';

// Verificación segura: si no hay sesión o el cargo no coincide, fuera al login
if (!isset($_SESSION['id']) || trim($_SESSION['cargo']) != 'Chofer') {
    header("Location: login.php");
    exit();
}

$mensaje = "";
$id_chofer = $_SESSION['id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $accion = $_POST['accion'] ?? '';

    // OPCIÓN 1: Reportar Falla / Mantenimiento (Automáticamente CORRECTIVA)
    if ($accion == 'reportar_falla') {
        $id_vehiculo = $_POST['id_vehiculo'];
        $tipo_falla = 'Correctiva'; 
        $comentario = trim($_POST['comentario']);

        try {
            $sql = "CALL sp_RegistrarMantenimiento(:tecnico, :vehiculo, :falla, :comentario)";
            $stmt = $conexion->prepare($sql);
            $stmt->bindParam(':tecnico', $id_chofer, PDO::PARAM_INT);
            $stmt->bindParam(':vehiculo', $id_vehiculo, PDO::PARAM_INT);
            $stmt->bindParam(':falla', $tipo_falla, PDO::PARAM_STR);
            $stmt->bindParam(':comentario', $comentario, PDO::PARAM_STR);
            $stmt->execute();
            $stmt->closeCursor();

            // Desvincular el vehículo para que pase al taller y cambie a estado inactivo o libre de chofer
            $stmtLiberar = $conexion->prepare("UPDATE flota SET id_chofer = NULL WHERE id = :id_vehiculo");
            $stmtLiberar->execute([':id_vehiculo' => $id_vehiculo]);

            $mensaje = "<div class='alert alert-success fw-bold'>¡Falla reportada registrada con éxito! El vehículo pasó a mantenimiento y fue liberado de tu usuario.</div>";
        } catch (PDOException $e) {
            $mensaje = "<div class='alert alert-danger fw-bold'>Error al reportar falla: " . $e->getMessage() . "</div>";
        }
    }

    // OPCIÓN 2: Actualizar Kilometraje
    if ($accion == 'actualizar_km') {
        $id_vehiculo = $_POST['id_vehiculo_km'];
        $nuevo_km = $_POST['nuevo_kilometraje'];

        try {
            $sql = "CALL sp_ActualizarKilometrajeVehiculo(:id, :kilometraje)";
            $stmt = $conexion->prepare($sql);
            $stmt->bindParam(':id', $id_vehiculo, PDO::PARAM_INT);
            $stmt->bindParam(':kilometraje', $nuevo_km, PDO::PARAM_STR);
            $stmt->execute();
            $stmt->closeCursor();

            $mensaje = "<div class='alert alert-success fw-bold'>¡Kilometraje actualizado correctamente!</div>";
        } catch (PDOException $e) {
            $mensaje = "<div class='alert alert-danger fw-bold'>Error al actualizar kilometraje: " . $e->getMessage() . "</div>";
        }
    }
}

// Consultar el vehículo asignado a este chofer en tiempo real
try {
    $stmtVehiculo = $conexion->prepare("SELECT id, marca, modelo, placa, kilometraje, estado FROM flota WHERE id_chofer = :id_chofer");
    $stmtVehiculo->bindParam(':id_chofer', $id_chofer, PDO::PARAM_INT);
    $stmtVehiculo->execute();
    $vehiculo_asignado = $stmtVehiculo->fetch(PDO::FETCH_ASSOC);
    $stmtVehiculo->closeCursor();
} catch (PDOException $e) {
    $vehiculo_asignado = null;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel del Chofer - Sistema de Mantenimiento</title>
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container my-4" style="max-width: 700px;">
    <!-- Cabecera -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Panel del Chofer</h2>
        <a href="logout.php" class="btn btn-danger btn-sm">Cerrar Sesión</a>
    </div>
    
    <div class="alert alert-secondary shadow-sm">
        <p class="mb-1"><strong>Bienvenido, <?php echo htmlspecialchars($_SESSION['nombres'] ?? 'Chofer'); ?></strong></p>
        <p class="mb-0">Cargo: Chofer</p>
    </div>

    <?php echo $mensaje; ?>

    <!-- SECCIÓN: Información del Vehículo Asignado -->
    <div class="card shadow-sm mb-4 border-info">
        <div class="card-header bg-info text-dark fw-bold">
            Mi Vehículo Asignado
        </div>
        <div class="card-body">
            <?php if ($vehiculo_asignado): ?>
                <ul class="list-group list-group-flush mb-3">
                    <li class="list-group-item"><strong>ID de Unidad:</strong> <?php echo $vehiculo_asignado['id']; ?></li>
                    <li class="list-group-item"><strong>Marca y Modelo:</strong> <?php echo htmlspecialchars($vehiculo_asignado['marca'] . " " . $vehiculo_asignado['modelo']); ?></li>
                    <li class="list-group-item"><strong>Placa:</strong> <?php echo htmlspecialchars($vehiculo_asignado['placa']); ?></li>
                    <li class="list-group-item"><strong>Kilometraje Actual:</strong> <?php echo number_format($vehiculo_asignado['kilometraje'], 2, ',', '.'); ?> km</li>
                    <li class="list-group-item"><strong>Estado:</strong> <span class="badge bg-success"><?php echo $vehiculo_asignado['estado']; ?></span></li>
                </ul>
            <?php else: ?>
                <div class="alert alert-warning mb-0 text-center">
                    No tienes ningún vehículo asignado en este momento. Las asignaciones automáticas se realizan diariamente a las 6:00 AM o puedes contactar al supervisor.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($vehiculo_asignado): ?>
        <!-- OPCIÓN 1: Reportar Falla / Mantenimiento -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h3 class="h5 card-title mb-3">1. Reportar Falla (Correctiva)</h3>
                <form action="" method="POST">
                    <input type="hidden" name="accion" value="reportar_falla">
                    <input type="hidden" name="id_vehiculo" value="<?php echo $vehiculo_asignado['id']; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Vehículo Seleccionado:</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($vehiculo_asignado['marca'] . " " . $vehiculo_asignado['modelo'] . " (" . $vehiculo_asignado['placa'] . ")"); ?>" disabled>
                    </div>

                    <div class="mb-3">
                        <label for="comentario" class="form-label">Comentario / Descripción detallada de la falla:</label>
                        <textarea name="comentario" class="form-control" rows="3" placeholder="Describa el problema presentado..." required></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">Enviar Reporte</button>
                </form>
            </div>
        </div>

        <!-- OPCIÓN 2: Actualizar Kilometraje -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h3 class="h5 card-title mb-3">2. Actualizar Kilometraje</h3>
                <form action="" method="POST">
                    <input type="hidden" name="accion" value="actualizar_km">
                    <input type="hidden" name="id_vehiculo_km" value="<?php echo $vehiculo_asignado['id']; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Vehículo Seleccionado:</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($vehiculo_asignado['marca'] . " " . $vehiculo_asignado['modelo'] . " (" . $vehiculo_asignado['placa'] . ")"); ?>" disabled>
                    </div>

                    <div class="mb-3">
                        <label for="nuevo_kilometraje" class="form-label">Nuevo Kilometraje:</label>
                        <input type="number" step="0.01" class="form-control" name="nuevo_kilometraje" placeholder="Ej: 15400.00" required>
                    </div>

                    <button type="submit" class="btn btn-success">Actualizar Kilometraje</button>
                </form>
            </div>
        </div>
    <?php endif; ?>

</div>

<script src="bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>