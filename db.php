<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$db   = 'TIENDA';
$user = 'root';   // Cambia aquí si usas otro usuario
$pass = '';       // Cambia aquí si tienes contraseña
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Muestra errores PDO
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch como array asociativo
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Usa consultas preparadas nativas
];

try {
    $conn = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // Si la conexión falla, muestra mensaje y termina
    echo "Error de conexión a la base de datos: " . $e->getMessage();
    exit;
}
