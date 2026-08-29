<?php
session_start();
require_once 'conexion.php';

// Verificamos si el usuario ha iniciado sesión y si es Chofer
if (!isset($_SESSION['id']) || $_SESSION['cargo'] != 'Chofer') {
    header("Location: login.php");
    exit();
}

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $accion = $_POST['accion'] ?? '';

    // OPCIÓN 1: Reportar Falla / Mantenimiento (Automáticamente CORRECTIVA)
    if ($accion == 'reportar_falla') {
        $id_vehiculo = $_POST['id_vehiculo'];
        $tipo_falla = 'Correctiva'; 
        $comentario = $_POST['comentario'];
        $id_chofer = $_SESSION['id']; 

        try {
            $sql = "CALL sp_RegistrarMantenimiento(:tecnico, :vehiculo, :falla, :comentario)";
            $stmt = $conexion->prepare($sql);
            $stmt->bindParam(':tecnico', $id_chofer, PDO::PARAM_STR);
            $stmt->bindParam(':vehiculo', $id_vehiculo, PDO::PARAM_STR);
            $stmt->bindParam(':falla', $tipo_falla, PDO::PARAM_STR);
            $stmt->bindParam(':comentario', $comentario, PDO::PARAM_STR);
            $stmt->execute();
            $stmt->closeCursor(); // Cerramos el cursor de procedimientos almacenados

            $mensaje = "<div class='alert alert-success'>¡Falla reportada registrada como Correctiva con éxito!</div>";
        } catch (PDOException $e) {
            $mensaje = "<div class='alert alert-danger'>Error al reportar falla: " . $e->getMessage() . "</div>";
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
            $stmt->closeCursor(); // Cerramos el cursor de procedimientos almacenados

            $mensaje = "<div class='alert alert-success'>¡Kilometraje actualizado correctamente!</div>";
        } catch (PDOException $e) {
            $mensaje = "<div class='alert alert-danger'>Error al actualizar kilometraje: " . $e->getMessage() . "</div>";
        }
    }
}

try {
    $stmtVehiculos = $conexion->query("SELECT id, marca, modelo, placa FROM flota");
    $lista_vehiculos = $stmtVehiculos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $lista_vehiculos = [];
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
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Panel del Chofer</h2>
        <a href="logout.php" class="btn btn-danger btn-sm">Cerrar Sesión</a>
    </div>
    
    <div class="alert alert-secondary">
        <p class="mb-1"><strong>Bienvenido, <?php echo htmlspecialchars($_SESSION['nombres']); ?></strong></p>
        <p class="mb-0">Cargo: <?php echo htmlspecialchars($_SESSION['cargo']); ?></p>
    </div>

    <?php echo $mensaje; ?>

    <!-- OPCIÓN 1: Reportar Falla / Mantenimiento -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h3 class="h5 card-title mb-3">1. Reportar Falla (Correctiva)</h3>
            <form action="" method="POST">
                <input type="hidden" name="accion" value="reportar_falla">
                
                <div class="mb-3">
                    <label for="id_vehiculo" class="form-label">Seleccione el Vehículo:</label>
                    <select name="id_vehiculo" class="form-select" required>
                        <option value="">-- Elija un vehículo --</option>
                        <?php foreach ($lista_vehiculos as $v): ?>
                            <option value="<?php echo $v['id']; ?>">
                                ID: <?php echo $v['id']; ?> - <?php echo $v['marca'] . " " . $v['modelo'] . " (" . $v['placa'] . ")"; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
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
                
                <div class="mb-3">
                    <label for="id_vehiculo_km" class="form-label">Seleccione el Vehículo:</label>
                    <select name="id_vehiculo_km" class="form-select" required>
                        <option value="">-- Elija un vehículo --</option>
                        <?php foreach ($lista_vehiculos as $v): ?>
                            <option value="<?php echo $v['id']; ?>">
                                ID: <?php echo $v['id']; ?> - <?php echo $v['marca'] . " " . $v['modelo'] . " (" . $v['placa'] . ")"; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="nuevo_kilometraje" class="form-label">Nuevo Kilometraje:</label>
                    <input type="number" step="0.01" class="form-control" name="nuevo_kilometraje" placeholder="Ej: 15400.00" required>
                </div>

                <button type="submit" class="btn btn-success">Actualizar Kilometraje</button>
            </form>
        </div>
    </div>

</div>

<script src="bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>