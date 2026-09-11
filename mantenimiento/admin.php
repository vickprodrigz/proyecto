<?php
session_start();
require_once 'conexion.php';

// Verificar que sea Administrador
if (!isset($_SESSION['id']) || trim($_SESSION['cargo']) != 'Administrador') {
    header("Location: index.php");
    exit();
}

$mensaje = "";
$tipo_alerta = "alert-success"; // Por defecto

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $accion = $_POST['accion'] ?? '';
    $admin_actual = $_SESSION['nombres'] ?? 'Administrador';

    // Gestión de Flota
    if ($accion == 'agregar_vehiculo') {
        $marca = trim($_POST['marca']);
        $modelo = trim($_POST['modelo']);
        $placa = trim($_POST['placa']);
        $kilometraje = $_POST['kilometraje'];
        $estado = $_POST['estado'];

        try {
            $stmt = $conexion->prepare("INSERT INTO flota (marca, modelo, placa, kilometraje, estado) VALUES (:marca, :modelo, :placa, :kilometraje, :estado)");
            $stmt->execute([
                ':marca' => $marca,
                ':modelo' => $modelo,
                ':placa' => $placa,
                ':kilometraje' => $kilometraje,
                ':estado' => $estado
            ]);
            $mensaje = "¡Vehículo agregado correctamente a la flota!";
            $tipo_alerta = "alert-success";
        } catch (PDOException $e) {
            $mensaje = "Error al agregar vehículo: " . htmlspecialchars($e->getMessage());
            $tipo_alerta = "alert-danger";
        }
    }

    if ($accion == 'eliminar_vehiculo') {
        $id_vehiculo = $_POST['id_vehiculo'];
        $comentario = trim($_POST['comentario'] ?? 'Venta');

        try {
            $conexion->beginTransaction();

            $stmtSelect = $conexion->prepare("SELECT * FROM flota WHERE id = :id");
            $stmtSelect->execute([':id' => $id_vehiculo]);
            $vehiculo = $stmtSelect->fetch(PDO::FETCH_ASSOC);

            if ($vehiculo) {
                $stmtInsert = $conexion->prepare("
                    INSERT INTO vehiculos_historicos (id_vehiculo_original, marca, modelo, placa, kilometraje, estado, id_chofer, eliminado_por, comentario, fecha_eliminacion) 
                    VALUES (:id_orig, :marca, :modelo, :placa, :kilometraje, :estado, :id_chofer, :admin, :comentario, NOW())
                ");
                $stmtInsert->execute([
                    ':id_orig' => $vehiculo['id'],
                    ':marca' => $vehiculo['marca'],
                    ':modelo' => $vehiculo['modelo'],
                    ':placa' => $vehiculo['placa'],
                    ':kilometraje' => $vehiculo['kilometraje'],
                    ':estado' => $vehiculo['estado'],
                    ':id_chofer' => $vehiculo['id_chofer'] ?? null,
                    ':admin' => $admin_actual,
                    ':comentario' => $comentario
                ]);

                $stmtDelete = $conexion->prepare("DELETE FROM flota WHERE id = :id");
                $stmtDelete->execute([':id' => $id_vehiculo]);

                $conexion->commit();
                $mensaje = "¡Vehículo dado de baja correctamente!";
                $tipo_alerta = "alert-success";
            } else {
                $conexion->rollBack();
                $mensaje = "El vehículo que intenta eliminar no existe.";
                $tipo_alerta = "alert-danger";
            }
        } catch (PDOException $e) {
            $conexion->rollBack();
            $mensaje = "Error al eliminar vehículo: " . htmlspecialchars($e->getMessage());
            $tipo_alerta = "alert-danger";
        }
    }

    // Gestión de Usuarios y Roles
    if ($accion == 'agregar_usuario') {
        $nombres = trim($_POST['nombres']);
        $usuario = trim($_POST['usuario']);
        $contrasena_raw = $_POST['contrasena'];
        $cargo = $_POST['cargo'];

        // Cifrado seguro de contraseña
        $contrasena = password_hash($contrasena_raw, PASSWORD_DEFAULT);

        try {
            $stmt = $conexion->prepare("INSERT INTO usuario (nombres, usuario, contraseña, cargo) VALUES (:nombres, :usuario, :contrasena, :cargo)");
            $stmt->execute([
                ':nombres' => $nombres,
                ':usuario' => $usuario,
                ':contrasena' => $contrasena,
                ':cargo' => $cargo
            ]);
            $mensaje = "¡Usuario registrado exitosamente!";
            $tipo_alerta = "alert-success";
        } catch (PDOException $e) {
            $mensaje = "Error al registrar usuario: " . htmlspecialchars($e->getMessage());
            $tipo_alerta = "alert-danger";
        }
    }

    if ($accion == 'cambiar_cargo') {
        $id_usuario = $_POST['id_usuario'];
        $nuevo_cargo = $_POST['nuevo_cargo'];

        try {
            $stmt = $conexion->prepare("UPDATE usuario SET cargo = :cargo WHERE id = :id");
            $stmt->execute([':cargo' => $nuevo_cargo, ':id' => $id_usuario]);
            $mensaje = "¡Cargo actualizado correctamente!";
            $tipo_alerta = "alert-success";
        } catch (PDOException $e) {
            $mensaje = "Error al cambiar el cargo: " . htmlspecialchars($e->getMessage());
            $tipo_alerta = "alert-danger";
        }
    }

    if ($accion == 'eliminar_usuario') {
        $id_usuario = $_POST['id_usuario'];
        $comentario = trim($_POST['comentario'] ?? 'Renuncia');

        if ($id_usuario == $_SESSION['id']) {
            $mensaje = "No puedes eliminar tu propio usuario administrador.";
            $tipo_alerta = "alert-danger";
        } else {
            try {
                $conexion->beginTransaction();

                $stmtSelect = $conexion->prepare("SELECT * FROM usuario WHERE id = :id");
                $stmtSelect->execute([':id' => $id_usuario]);
                $usr = $stmtSelect->fetch(PDO::FETCH_ASSOC);

                if ($usr) {
                    $stmtInsert = $conexion->prepare("
                        INSERT INTO usuarios_historicos (id_usuario_original, nombres, usuario, contraseña, cargo, eliminado_por, comentario, fecha_eliminacion) 
                        VALUES (:id_orig, :nombres, :usuario, :contrasena, :cargo, :admin, :comentario, NOW())
                    ");
                    $stmtInsert->execute([
                        ':id_orig' => $usr['id'],
                        ':nombres' => $usr['nombres'] ?? null,
                        ':usuario' => $usr['usuario'],
                        ':contrasena' => $usr['contraseña'],
                        ':cargo' => $usr['cargo'],
                        ':admin' => $admin_actual,
                        ':comentario' => $comentario
                    ]);

                    $stmtDelete = $conexion->prepare("DELETE FROM usuario WHERE id = :id");
                    $stmtDelete->execute([':id' => $id_usuario]);

                    $conexion->commit();
                    $mensaje = "¡Usuario dado de baja correctamente!";
                    $tipo_alerta = "alert-success";
                } else {
                    $conexion->rollBack();
                    $mensaje = "El usuario que intenta eliminar no existe.";
                    $tipo_alerta = "alert-danger";
                }
            } catch (PDOException $e) {
                $conexion->rollBack();
                $mensaje = "Error al eliminar usuario: " . htmlspecialchars($e->getMessage());
                $tipo_alerta = "alert-danger";
            }
        }
    }

    $_SESSION['flash_mensaje'] = $mensaje;
    $_SESSION['flash_tipo'] = $tipo_alerta;
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

try {
    $lista_vehiculos = $conexion->query("SELECT * FROM flota")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $lista_vehiculos = [];
}

try {
    $lista_usuarios = $conexion->query("SELECT id, nombres, usuario, cargo FROM usuario")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $lista_usuarios = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel del Administrador - Sistema de Mantenimiento</title>
    <style>
        :root {
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --success: #16a34a;
            --danger: #dc2626;
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
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .header-bar h2 {
            margin: 0;
            font-size: 22px;
            color: var(--text-main);
        }

        .header-actions {
            display: flex;
            gap: 10px;
            align-items: center;
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

        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-hover); }

        .btn-success { background: var(--success); color: white; }
        .btn-success:hover { background: #15803d; }

        .btn-danger { background: var(--danger); color: white; }
        .btn-danger:hover { background: #b91c1c; }

        .btn-outline-success { background: transparent; border: 1px solid var(--success); color: var(--success); }
        .btn-outline-success:hover { background: var(--success); color: white; }

        .alerta-flotante-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            font-size: 14px;
            margin-bottom: 20px;
            font-weight: bold;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: opacity 0.5s ease-in-out;
            opacity: 1;
        }
        .alert.fade-out {
            opacity: 0;
        }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        .grid-section {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 20px;
            margin-bottom: 25px;
        }

        @media (max-width: 768px) {
            .grid-section { grid-template-columns: 1fr; }
        }

        .card {
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            display: flex;
            flex-direction: column;
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
            flex-grow: 1;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 13px;
            font-weight: 600;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
            outline: none;
            font-family: inherit;
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

        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-activo { background: #dcfce7; color: #166534; }
        .badge-inactivo { background: #fee2e2; color: #991b1b; }

        .inline-form {
            display: flex;
            gap: 5px;
            align-items: center;
        }
        .inline-form select {
            padding: 4px 6px;
            font-size: 12px;
        }

        .delete-box {
            background: #fff5f5;
            padding: 8px;
            border-radius: 6px;
            border: 1px solid #fed7d7;
            margin-top: 5px;
        }
    </style>
</head>
<body>

<div class="alerta-flotante-container">
    <?php if (isset($_SESSION['flash_mensaje'])): ?>
        <div class="alert <?php echo htmlspecialchars($_SESSION['flash_tipo']); ?> alerta-flotante">
            <?php echo htmlspecialchars($_SESSION['flash_mensaje']); ?>
        </div>
        <?php 
            unset($_SESSION['flash_mensaje']);
            unset($_SESSION['flash_tipo']);
        ?>
    <?php endif; ?>
</div>

<div class="container">
    <div class="header-bar">
        <div>
            <h2>Panel del Administrador</h2>
            <p style="margin: 4px 0 0 0; font-size: 13px; color: var(--text-muted);">
                Bienvenido, <strong><?php echo htmlspecialchars($_SESSION['nombres']); ?></strong> (Control Total)
            </p>
        </div>
        <div class="header-actions">
            <a href="historicos.php" class="btn" style="background: #475569; color: white;">📋 Ver Históricos</a>
            <a href="supervisor.php" class="btn btn-outline-success">⚙️ Vista Supervisor</a>
            <a href="logout.php" class="btn btn-danger">Cerrar Sesión</a>
        </div>
    </div>

    <!-- SECCIÓN 1: GESTIÓN DE FLOTA -->
    <div class="grid-section">
        <div class="card">
            <div class="card-header" style="color: var(--primary);">Agregar Nuevo Vehículo</div>
            <div class="card-body">
                <form action="" method="POST">
                    <input type="hidden" name="accion" value="agregar_vehiculo">
                    <div class="form-group">
                        <label>Marca</label>
                        <input type="text" name="marca" placeholder="Ej. Mack" required>
                    </div>
                    <div class="form-group">
                        <label>Modelo</label>
                        <input type="text" name="modelo" placeholder="Ej. Vision" required>
                    </div>
                    <div class="form-group">
                        <label>Placa</label>
                        <input type="text" name="placa" placeholder="Ej. A12BC3D" required>
                    </div>
                    <div class="form-group">
                        <label>Kilometraje Inicial</label>
                        <input type="number" step="0.01" name="kilometraje" placeholder="0.00" required>
                    </div>
                    <div class="form-group">
                        <label>Estado Inicial</label>
                        <select name="estado" required>
                            <option value="Activo">Activo</option>
                            <option value="Inactivo">Inactivo</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 5px;">Registrar Vehículo</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Inventario de Flota Actual</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Vehículo</th>
                                <th>Placa</th>
                                <th>Estado</th>
                                <th>Motivo de Baja</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($lista_vehiculos)): ?>
                                <tr><td colspan="5" style="text-align: center; color: var(--text-muted);">No hay vehículos registrados.</td></tr>
                            <?php else: ?>
                                <?php foreach ($lista_vehiculos as $v): ?>
                                    <tr>
                                        <td><?php echo $v['id']; ?></td>
                                        <td><?php echo htmlspecialchars($v['marca'] . " " . $v['modelo']); ?></td>
                                        <td><?php echo htmlspecialchars($v['placa']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo strtolower($v['estado']); ?>">
                                                <?php echo htmlspecialchars($v['estado']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form action="" method="POST" onsubmit="return confirm('¿Seguro que deseas dar de baja este vehículo?');">
                                                <input type="hidden" name="accion" value="eliminar_vehiculo">
                                                <input type="hidden" name="id_vehiculo" value="<?php echo $v['id']; ?>">
                                                <div class="delete-box">
                                                    <select name="comentario" style="font-size: 12px; padding: 6px; margin-bottom: 5px;" required>
                                                        <option value="Venta">Venta</option>
                                                        <option value="Choque">Choque</option>
                                                        <option value="Hurto">Hurto</option>
                                                    </select>
                                                    <button type="submit" class="btn btn-danger" style="padding: 4px 8px; font-size: 11px; width: 100%;">🗑️ Dar de Baja</button>
                                                </div>
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
    </div>

    <!-- SECCIÓN 2: GESTIÓN DE USUARIOS Y CARGOS -->
    <div class="grid-section">
        <div class="card">
            <div class="card-header" style="color: var(--success);">Registrar Nuevo Usuario</div>
            <div class="card-body">
                <form action="" method="POST">
                    <input type="hidden" name="accion" value="agregar_usuario">
                    <div class="form-group">
                        <label>Nombre Completo</label>
                        <input type="text" name="nombres" placeholder="Ej. Carlos Pérez" required>
                    </div>
                    <div class="form-group">
                        <label>Nombre de Usuario</label>
                        <input type="text" name="usuario" placeholder="Ej. cperez" required autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>Contraseña</label>
                        <input type="password" name="contrasena" placeholder="••••••••" required>
                    </div>
                    <div class="form-group">
                        <label>Cargo / Rol</label>
                        <select name="cargo" required>
                            <option value="Chofer">Chofer</option>
                            <option value="Supervisor">Supervisor</option>
                            <option value="Técnico">Técnico</option>
                            <option value="Administrador">Administrador</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success" style="width: 100%; margin-top: 5px;">Crear Usuario</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Gestión de Usuarios y Roles</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Nombre</th>
                                <th>Cargo Actual</th>
                                <th>Motivo de Baja</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($lista_usuarios)): ?>
                                <tr><td colspan="4" style="text-align: center; color: var(--text-muted);">No hay usuarios registrados.</td></tr>
                            <?php else: ?>
                                <?php foreach ($lista_usuarios as $usr): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($usr['usuario']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($usr['nombres']); ?></td>
                                        <td>
                                            <form action="" method="POST" class="inline-form">
                                                <input type="hidden" name="accion" value="cambiar_cargo">
                                                <input type="hidden" name="id_usuario" value="<?php echo $usr['id']; ?>">
                                                <select name="nuevo_cargo">
                                                    <option value="Chofer" <?php if(trim($usr['cargo'])=='Chofer') echo 'selected'; ?>>Chofer</option>
                                                    <option value="Supervisor" <?php if(trim($usr['cargo'])=='Supervisor') echo 'selected'; ?>>Supervisor</option>
                                                    <option value="Técnico" <?php if(trim($usr['cargo'])=='Técnico') echo 'selected'; ?>>Técnico</option>
                                                    <option value="Administrador" <?php if(trim($usr['cargo'])=='Administrador') echo 'selected'; ?>>Administrador</option>
                                                </select>
                                                <button type="submit" class="btn btn-primary" style="padding: 4px 8px; font-size: 11px;" title="Guardar cambio de cargo">💾</button>
                                            </form>
                                        </td>
                                        <td>
                                            <form action="" method="POST" onsubmit="return confirm('¿Seguro que deseas dar de baja este usuario?');">
                                                <input type="hidden" name="accion" value="eliminar_usuario">
                                                <input type="hidden" name="id_usuario" value="<?php echo $usr['id']; ?>">
                                                <div class="delete-box">
                                                    <select name="comentario" style="font-size: 12px; padding: 6px; margin-bottom: 5px;" required>
                                                        <option value="Renuncia">Renuncia</option>
                                                        <option value="Despido">Despido</option>
                                                        <option value="Falleció">Falleció</option>
                                                    </select>
                                                    <button type="submit" class="btn btn-danger" style="padding: 4px 8px; font-size: 11px; width: 100%;">🗑️ Dar de Baja</button>
                                                </div>
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
    </div>

</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const alerts = document.querySelectorAll(".alerta-flotante");
        
        alerts.forEach(function (alertElement) {
            setTimeout(function () {
                alertElement.classList.add("fade-out");
                setTimeout(function () {
                    alertElement.remove();
                }, 500);
            }, 4000);
        });
    });
</script>

</body>
</html>