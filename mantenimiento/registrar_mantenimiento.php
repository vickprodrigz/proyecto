<?php
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_tecnico = $_POST['id_tecnico'];
    $id_vehiculo = $_POST['id_vehiculo'];
    $tipo_falla = $_POST['tipo_falla'];
    $comentario = $_POST['comentario'];

    try {
        $sql = "CALL sp_RegistrarMantenimiento(:tecnico, :vehiculo, :falla, :comentario)";
        $stmt = $conexion->prepare($sql);
        
        $stmt->bindParam(':tecnico', $id_tecnico, PDO::PARAM_STR);
        $stmt->bindParam(':vehiculo', $id_vehiculo, PDO::PARAM_STR);
        $stmt->bindParam(':falla', $tipo_falla, PDO::PARAM_STR);
        $stmt->bindParam(':comentario', $comentario, PDO::PARAM_STR);

        $stmt->execute();
        
        echo "¡Mantenimiento registrado con éxito!";
        
    } catch (PDOException $e) {
        echo "Error al registrar el mantenimiento: " . $e->getMessage();
    }
}
?>

<!-- Formulario HTML -->
<form action="" method="POST">
    <label>ID del Técnico:</label>
    <input type="text" name="id_tecnico" required><br>

    <label>ID del Vehículo:</label>
    <input type="text" name="id_vehiculo" required><br>

    <label>Tipo de Falla:</label>
    <input type="text" name="tipo_falla" required><br>

    <label>Comentario:</label>
    <textarea name="comentario" required></textarea><br>

    <button type="submit">Guardar Reporte</button>
</form>