<?php
session_start();
require_once 'conexion.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = trim($_POST['usuario']);
    $contrasena = trim($_POST['contrasena']);

    try {
        $sql = "CALL sp_LoginUsuario(:usuario, :contrasena)";
        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(':usuario', $usuario, PDO::PARAM_STR);
        $stmt->bindParam(':contrasena', $contrasena, PDO::PARAM_STR);
        $stmt->execute();

        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ($user_data) {
            $_SESSION['id'] = $user_data['id'];
            $_SESSION['nombres'] = $user_data['nombres'];
            $_SESSION['cargo'] = $user_data['cargo'];
            $_SESSION['usuario'] = $user_data['usuario'];

            switch ($user_data['cargo']) {
                case 'Chofer':
                    header("Location: chofer.php");
                    break;
                case 'Administrador':
                    header("Location: admin.php");
                    break;
                case 'Supervisor':
                    header("Location: supervisor.php");
                    break;
                case 'Técnico':
                    header("Location: tecnico.php");
                    break;
                default:
                    header("Location: index.php");
                    break;
            }
            exit();

        } else {
            $error = "Usuario o contraseña incorrectos.";
        }

    } catch (PDOException $e) {
        $error = "Error en el sistema: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión - Sistema de Mantenimiento</title>
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
</head>
<body class="bg-light d-flex justify-content-center align-items-center vh-100">

<div class="card shadow p-4" style="width: 22rem;">
    <div class="card-body">
        <h2 class="text-center mb-4 h4 fw-bold">Iniciar Sesión</h2>
        
        <form action="" method="POST">
            <div class="mb-3">
                <label for="usuario" class="form-label">Usuario</label>
                <input type="text" class="form-control" id="usuario" name="usuario" required autocomplete="off">
            </div>

            <div class="mb-3">
                <label for="contrasena" class="form-label">Contraseña</label>
                <input type="password" class="form-control" id="contrasena" name="contrasena" required>
            </div>

            <button type="submit" class="btn btn-primary w-100">Ingresar</button>
        </form>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger mt-3 text-center py-2 mb-0" role="alert">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>