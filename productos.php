<?php
session_start();
require 'db.php';

// Generar token CSRF si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$mensajeError = '';
$buscar = trim($_GET['buscar'] ?? '');
$editarId = isset($_GET['editar']) ? intval($_GET['editar']) : null;
$productoEditar = null;

// Buscar
if ($buscar !== '') {
    $stmt = $conn->prepare("SELECT * FROM producto WHERE nombre LIKE ? OR descripcion LIKE ?");
    $like = "%$buscar%";
    $stmt->execute([$like, $like]);
} else {
    $stmt = $conn->query("SELECT * FROM producto ORDER BY nombre");
}
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Si se va a editar
if ($editarId) {
    $stmt = $conn->prepare("SELECT * FROM producto WHERE id_producto=?");
    $stmt->execute([$editarId]);
    $productoEditar = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Acciones POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Error CSRF: token inválido.");
    }

    $accion = $_POST['accion'];

    if ($accion == 'agregar') {
        $nombre = trim($_POST['nombre']);
        $descripcion = trim($_POST['descripcion']);
        $precio = intval($_POST['precio']);
        $stock = intval($_POST['stock']);
        if ($nombre && $descripcion && $precio >= 0 && $stock >= 0) {
            $stmt = $conn->prepare("INSERT INTO producto (nombre, descripcion, precio, stock) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nombre, $descripcion, $precio, $stock]);
        }
        header("Location: productos.php");
        exit;
    } elseif ($accion == 'editar') {
        $id = intval($_POST['id_producto']);
        $nombre = trim($_POST['nombre']);
        $descripcion = trim($_POST['descripcion']);
        $precio = intval($_POST['precio']);
        $stock = intval($_POST['stock']);
        if ($nombre && $descripcion && $precio >= 0 && $stock >= 0) {
            $stmt = $conn->prepare("UPDATE producto SET nombre=?, descripcion=?, precio=?, stock=? WHERE id_producto=?");
            $stmt->execute([$nombre, $descripcion, $precio, $stock, $id]);
        }
        header("Location: productos.php");
        exit;
    } elseif ($accion == 'eliminar') {
        $id = intval($_POST['id_producto']);
        try {
            $stmt = $conn->prepare("DELETE FROM producto WHERE id_producto=?");
            $stmt->execute([$id]);
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') {
                $mensajeError = "No se puede eliminar el producto porque está asociado a una venta.";
            } else {
                $mensajeError = "Error al eliminar: " . $e->getMessage();
            }
        }
        if ($mensajeError === '') {
            header("Location: productos.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Gestión de Productos</title>
<link rel="stylesheet" href="css/estilos.css">
<style>
body {
    font-family: Arial, sans-serif;
    background: #ecf6fc;
    color: #333;
    margin: 0; padding: 0;
}
h2 {
    text-align: center;
    color: #2c3e50;
    margin-top: 20px;
}
.main-container {
    max-width: 900px;
    margin: 20px auto;
    padding: 15px;
    background: #f1faee;
    border-radius: 8px;
}
.explicacion {
    background: #f1faee;
    padding: 12px;
    margin: 15px 0;
    border-radius: 6px;
    font-size: 14px;
    line-height: 1.5;
}
.mensaje-error {
    color: #c0392b;
    font-weight: bold;
    text-align: center;
    margin: 10px 0;
}
.buscar-form,
.agregar-form,
.editar-form {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}
/* Inputs dentro de formularios */
.buscar-form input[type="text"],
.agregar-form input[type="text"],
.agregar-form input[type="number"],
.editar-form input[type="text"],
.editar-form input[type="number"] {
    flex: 1;
    padding: 8px;
    border: 1px solid #457b9d;
    border-radius: 4px;
}
button {
    background-color: #457b9d;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    white-space: nowrap;
}
button:hover {
    background-color: #1d3557;
}
.producto-card {
    background: white;
    border: 1px solid #ccc;
    border-radius: 6px;
    padding: 12px 15px;
    margin-bottom: 15px;
    box-shadow: 0 0 5px rgba(0,0,0,0.1);
}
.producto-datos {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 10px;
}
.producto-datos input {
    flex: 1 1 200px;
    padding: 6px;
    border: 1px solid #ddd;
    border-radius: 4px;
}
.producto-datos input:disabled {
    background: #f9f9f9;
    border-color: #ccc;
}
.acciones {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
.eliminar {
    background: #c0392b;
}
/* Nuevos estilos para formularios contenidos y alineados a la izquierda */
.form-container {
    max-width: 600px;
    margin-left: 0;
    margin-bottom: 20px;
}
form {
    width: 100%;
    box-sizing: border-box;
    padding: 10px;
    background: #f9f9f9;
    border-radius: 8px;
    border: 1px solid #ccc;
}
input[type="text"],
input[type="number"] {
    width: 100%;
    padding: 8px;
    margin-bottom: 10px;
    border-radius: 4px;
    border: 1px solid #aaa;
}
@media (max-width: 600px) {
    .producto-datos {
        flex-direction: column;
    }
    .buscar-form,
    .agregar-form,
    .editar-form {
        flex-direction: column;
    }
}
</style>
</head>
<body>
<div style="text-align:right; color:#c0392b; font-weight:bold; padding:10px;">
    Usuario activo: <?=htmlspecialchars($_SESSION['usuario'])?>
</div>

<h2>Gestión de Productos</h2>

<div class="main-container">

    <div class="explicacion">
        <p>Aquí puedes buscar productos por nombre o descripción. También agregar nuevos productos, modificar datos y eliminar productos (si no están asociados a ventas).</p>
        <p>Para editar, presiona "Modificar". Para eliminar, presiona "Eliminar".</p>
    </div>

    <?php if ($mensajeError): ?>
    <div class="mensaje-error"><?=htmlspecialchars($mensajeError)?></div>
    <?php endif; ?>

    <div class="form-container">
        <form method="get" class="buscar-form">
            <input type="text" name="buscar" value="<?=htmlspecialchars($buscar)?>" placeholder="Buscar producto">
            <button type="submit">Buscar</button>
        </form>
    </div>

    <div class="form-container">
        <h3>Agregar nuevo producto</h3>
        <form method="post" class="agregar-form">
            <input type="hidden" name="accion" value="agregar">
            <input type="hidden" name="csrf_token" value="<?=$csrf_token?>">
            <input type="text" name="nombre" placeholder="Nombre" required>
            <input type="text" name="descripcion" placeholder="Descripción" required>
            <input type="number" name="precio" placeholder="Precio (en pesos)" required>
            <input type="number" name="stock" placeholder="Stock" required>
            <button type="submit">Agregar</button>
        </form>
    </div>

    <?php if ($productoEditar): ?>
    <div class="form-container">
        <h3>Editar producto</h3>
        <form method="post" class="editar-form">
            <input type="hidden" name="accion" value="editar">
            <input type="hidden" name="id_producto" value="<?=$productoEditar['id_producto']?>">
            <input type="hidden" name="csrf_token" value="<?=$csrf_token?>">
            <input type="text" name="nombre" value="<?=htmlspecialchars($productoEditar['nombre'])?>" required>
            <input type="text" name="descripcion" value="<?=htmlspecialchars($productoEditar['descripcion'])?>" required>
            <input type="number" name="precio" value="<?=$productoEditar['precio']?>" required>
            <input type="number" name="stock" value="<?=$productoEditar['stock']?>" required>
            <button type="submit">Guardar cambios</button>
        </form>
    </div>
    <?php endif; ?>

    <h3>Listado de Productos</h3>

    <?php foreach ($productos as $p): ?>
    <div class="producto-card">
        <div class="producto-datos">
            <input type="text" value="<?=htmlspecialchars($p['nombre'])?>" disabled>
            <input type="text" value="<?=htmlspecialchars($p['descripcion'])?>" disabled>
            <input type="text" value="$<?=number_format($p['precio'], 0, ',', '.')?>" disabled>
            <input type="number" value="<?=$p['stock']?>" disabled>
        </div>
        <div class="acciones">
            <form method="get" style="margin:0;">
                <input type="hidden" name="buscar" value="<?=htmlspecialchars($buscar)?>">
                <input type="hidden" name="editar" value="<?=$p['id_producto']?>">
                <button type="submit">Modificar</button>
            </form>
            <form method="post" style="margin:0;">
                <input type="hidden" name="id_producto" value="<?=$p['id_producto']?>">
                <input type="hidden" name="csrf_token" value="<?=$csrf_token?>">
                <button type="submit" name="accion" value="eliminar" class="eliminar">Eliminar</button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>

    <div style="margin-top: 15px;">
        <a href="index.php"><button>Volver al Panel</button></a>
    </div>

</div>
</body>
</html>


