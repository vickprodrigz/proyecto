<?php
require_once 'conexion.php';

try {
    $conexion->beginTransaction();

    // 1. Obtener vehículos activos sin chofer asignado
    $stmtV = $conexion->query("SELECT id FROM flota WHERE estado = 'Activo' AND (id_chofer IS NULL OR id_chofer = 0)");
    $vehiculos = $stmtV->fetchAll(PDO::FETCH_COLUMN);

    // 2. Obtener choferes libres (que no tengan un vehículo activo asignado)
    $stmtC = $conexion->query("
        SELECT id FROM usuario 
        WHERE cargo = 'Chofer' 
        AND id NOT IN (SELECT DISTINCT id_chofer FROM flota WHERE id_chofer IS NOT NULL AND estado = 'Activo')
    ");
    $choferes = $stmtC->fetchAll(PDO::FETCH_COLUMN);

    // 3. Mezclar de forma ALEATORIA ambos arreglos
    shuffle($vehiculos);
    shuffle($choferes);

    $asignados = 0;
    $limite = min(count($vehiculos), count($choferes));

    // 4. Asignar aleatoriamente
    for ($i = 0; $i < $limite; $i++) {
        $id_v = $vehiculos[$i];
        $id_c = $choferes[$i];

        $update = $conexion->prepare("UPDATE flota SET id_chofer = :id_chofer WHERE id = :id_vehiculo");
        $update->execute([':id_chofer' => $id_c, ':id_vehiculo' => $id_v]);
        $asignados++;
    }

    $conexion->commit();
    echo "Asignación aleatoria ejecutada con éxito. Vehículos asignados: $asignados.";
} catch (PDOException $e) {
    $conexion->rollBack();
    echo "Error en la asignación automática: " . $e->getMessage();
}
?>