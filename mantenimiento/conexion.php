<?php
$host = 'localhost';
$dbname = 'mantenimiento';
$username = 'root'; // Cambia esto por tu usuario de base de datos
$password = ''; // Cambia esto por tu contraseña si aplica

try {
    $conexion = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>