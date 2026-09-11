<?php
session_start();
require_once 'conexion.php';

// Si ya hay una sesión activa, validar el rol y redirigir
if (isset($_SESSION['cargo'])) {
    switch (trim($_SESSION['cargo'])) {
        case 'Chofer':
            header("Location: chofer.php");
            exit();
        case 'Supervisor':
            header("Location: supervisor.php");
            exit();
        case 'Administrador':
            header("Location: admin.php");
            exit();
        case 'Técnico':
            header("Location: tecnico.php");
            exit();
        default:
            session_unset();
            session_destroy();
            header("Location: login.php");
            exit();
    }
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = trim($_POST['usuario']);
    $contrasena = trim($_POST['password']);

    try {
        // Ejecución segura del procedimiento almacenado con binding de parámetros
        $stmt = $conexion->prepare("CALL sp_login_usuario(:usuario)");
        $stmt->bindParam(':usuario', $usuario, PDO::PARAM_STR);
        $stmt->execute();
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor(); // Libera la conexión para consultas posteriores

        if ($user_data && $contrasena === $user_data['contraseña']) {
            $_SESSION['id'] = $user_data['id'];
            $_SESSION['nombres'] = $user_data['nombres'];
            $_SESSION['cargo'] = trim($user_data['cargo']);
            $_SESSION['usuario'] = $user_data['usuario'];

            // Redirección estricta según el cargo
            switch ($_SESSION['cargo']) {
                case 'Chofer':
                    header("Location: chofer.php");
                    exit();
                case 'Supervisor':
                    header("Location: supervisor.php");
                    exit();
                case 'Administrador':
                    header("Location: admin.php");
                    exit();
                case 'Técnico':
                    header("Location: tecnico.php");
                    exit();
                default:
                    session_unset();
                    session_destroy();
                    header("Location: login.php");
                    exit();
            }
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Control de Mantenimiento</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
</head>
<body class="bg-light d-flex align-items-center justify-content-center min-vh-100">

    <div class="container" style="max-width: 420px;">
        <div class="card border-0 shadow-lg rounded-4 p-4">
            <div class="card-body">
                
                <!-- Encabezado -->
                <div class="text-center mb-4">
                    <div class="display-4 mb-2">🚛</div>
                    <h3 class="fw-bold text-dark mb-1">Mantenimiento</h3>
                    <p class="text-muted small">Ingresa tus datos para acceder al sistema</p>
                </div>

                <!-- Mensaje de Error -->
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger text-center p-2 small mb-3" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <!-- Formulario -->
                <form action="" method="POST">
                    <div class="mb-3">
                        <label for="usuario" class="form-label fw-semibold text-secondary small">Nombre de Usuario</label>
                        <input type="text" class="form-control form-control-lg fs-6" id="usuario" name="usuario" placeholder="Ej. cperez" required autofocus autocomplete="off">
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label fw-semibold text-secondary small">Contraseña</label>
                        <input type="password" class="form-control form-control-lg fs-6" id="password" name="password" placeholder="••••••••" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm">Iniciar Sesión</button>
                </form>

                <!-- Pie de tarjeta -->
                <div class="text-center mt-4 text-muted small">
                    Sistema de Flota y Mantenimiento &copy; 2026
                </div>

            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>