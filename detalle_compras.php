<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require 'db.php';

header('Content-Type: application/json');

if (!isset($_GET['id_cliente'])) {
    echo json_encode(['error' => 'Falta el parámetro id_cliente']);
    exit;
}

$id_cliente = intval($_GET['id_cliente']);

try {
    $stmt = $conn->prepare("
        SELECT p.nombre AS producto, co.cantidad, co.total, co.fecha
        FROM COMPRA co
        JOIN PRODUCTO p ON co.id_producto = p.id_producto
        WHERE co.id_cliente = ?
    ");
    $stmt->execute([$id_cliente]);
    $detalle = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['detalle' => $detalle]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Error al obtener detalle: ' . $e->getMessage()]);
}
