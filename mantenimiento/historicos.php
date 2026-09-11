<?php
session_start();
require_once 'conexion.php';

// Verificar que sea Administrador
if (!isset($_SESSION['id']) || trim($_SESSION['cargo']) != 'Administrador') {
    header("Location: index.php");
    exit();
}

try {
    $historico_vehiculos = $conexion->query("SELECT * FROM vehiculos_historicos ORDER BY fecha_eliminacion DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $historico_vehiculos = [];
}

try {
    $historico_usuarios = $conexion->query("SELECT * FROM usuarios_historicos ORDER BY fecha_eliminacion DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $historico_usuarios = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Bajas - Sistema de Mantenimiento</title>
    <style>
        :root {
            --primary: #2563eb;
            --bg-gradient: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            --card-bg: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border-color: #cbd5e1;
            --table-striped: #f8fafc;
        }

        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: var(--bg-gradient);
            margin: 0;
            padding: 20px;
            color: var(--text-main);
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--card-bg);
            padding: 20px 25px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
        }

        .header-bar h2 {
            margin: 0;
            font-size: 22px;
            color: var(--text-main);
        }

        .btn {
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            text-decoration: none;
            display: inline-block;
            transition: background 0.2s ease;
        }

        .btn-secondary { background: #475569; color: white; }
        .btn-secondary:hover { background: #334155; }

        .card {
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
            overflow: hidden;
        }

        .card-header {
            padding: 15px 20px;
            font-weight: 700;
            font-size: 16px;
            border-bottom: 1px solid var(--border-color);
            background: #f8fafc;
        }

        .card-body {
            padding: 20px;
        }

        .table-responsive {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid var(--border-color);
            border-radius: 6px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            text-align: left;
        }

        th, td {
            padding: 10px 12px;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            background: #f1f5f9;
            font-weight: 600;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        tr:nth-child(even) { background: var(--table-striped); }

        .badge-history {
            background: #e2e8f0;
            color: #334155;
            padding: 3px 6px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }

        .badge-motivo {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            background: #f1f5f9;
            color: #334155;
            border: 1px solid var(--border-color);
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header-bar">
        <div>
            <h2>Historial de Registros Dados de Baja</h2>
            <p style="margin: 4px 0 0 0; font-size: 13px; color: var(--text-muted);">
                Registro detallado de elementos removidos, responsable y motivo de baja.
            </p>
        </div>
        <div>
            <a href="admin.php" class="btn btn-secondary">⬅️ Volver al Panel</a>
        </div>
    </div>

    <!-- TABLA 1: HISTÓRICO DE VEHÍCULOS -->
    <div class="card">
        <div class="card-header" style="color: var(--primary);">Vehículos Dados de Baja</div>
        <div class="card-body">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID Original</th>
                            <th>Vehículo</th>
                            <th>Placa</th>
                            <th>Estado Final</th>
                            <th>Dado de Baja Por</th>
                            <th>Motivo</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($historico_vehiculos)): ?>
                            <tr><td colspan="7" style="text-align: center; color: var(--text-muted);">No hay vehículos en el historial.</td></tr>
                        <?php else: ?>
                            <?php foreach ($historico_vehiculos as $hv): ?>
                                <tr>
                                    <td><span class="badge-history">#<?php echo $hv['id_vehiculo_original']; ?></span></td>
                                    <td><strong><?php echo htmlspecialchars($hv['marca'] . " " . $hv['modelo']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($hv['placa']); ?></td>
                                    <td><?php echo $hv['estado']; ?></td>
                                    <td><span style="color: var(--primary); font-weight: 600;"><?php echo htmlspecialchars($hv['eliminado_por'] ?? 'N/D'); ?></span></td>
                                    <td><span class="badge-motivo"><?php echo htmlspecialchars($hv['comentario'] ?? 'N/D'); ?></span></td>
                                    <td><?php echo $hv['fecha_eliminacion']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TABLA 2: HISTÓRICO DE USUARIOS -->
    <div class="card">
        <div class="card-header" style="color: #16a34a;">Usuarios Dados de Baja</div>
        <div class="card-body">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID Original</th>
                            <th>Usuario</th>
                            <th>Nombres</th>
                            <th>Cargo</th>
                            <th>Dado de Baja Por</th>
                            <th>Motivo</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($historico_usuarios)): ?>
                            <tr><td colspan="7" style="text-align: center; color: var(--text-muted);">No hay usuarios en el historial.</td></tr>
                        <?php else: ?>
                            <?php foreach ($historico_usuarios as $hu): ?>
                                <tr>
                                    <td><span class="badge-history">#<?php echo $hu['id_usuario_original']; ?></span></td>
                                    <td><strong><?php echo htmlspecialchars($hu['usuario']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($hu['nombres']); ?></td>
                                    <td><?php echo htmlspecialchars($hu['cargo']); ?></td>
                                    <td><span style="color: #16a34a; font-weight: 600;"><?php echo htmlspecialchars($hu['eliminado_por'] ?? 'N/D'); ?></span></td>
                                    <td><span class="badge-motivo"><?php echo htmlspecialchars($hu['comentario'] ?? 'N/D'); ?></span></td>
                                    <td><?php echo $hu['fecha_eliminacion']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

</body>
</html>