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
$clienteEditar = null;

// Buscar clientes
if ($buscar !== '') {
    $stmt = $conn->prepare("SELECT * FROM cliente WHERE nombre LIKE ? OR email LIKE ? OR direccion LIKE ?");
    $like = "%$buscar%";
    $stmt->execute([$like, $like, $like]);
} else {
    $stmt = $conn->query("SELECT * FROM cliente ORDER BY nombre");
}
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Si se va a editar
if ($editarId) {
    $stmt = $conn->prepare("SELECT * FROM cliente WHERE id_cliente=?");
    $stmt->execute([$editarId]);
    $clienteEditar = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Procesar datos del formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Error CSRF: token inválido.");
    }

    $accion = $_POST['accion'];

    if ($accion == 'agregar') {
        $nombre = trim($_POST['nombre']);
        $email = trim($_POST['email']);
        $direccion = trim($_POST['direccion']);
        if ($nombre && $email && $direccion) {
            $stmt = $conn->prepare("INSERT INTO cliente (nombre, email, direccion) VALUES (?, ?, ?)");
            $stmt->execute([$nombre, $email, $direccion]);
        }
        header("Location: clientes.php");
        exit;
    } elseif ($accion == 'editar') {
        $id = intval($_POST['id_cliente']);
        $nombre = trim($_POST['nombre']);
        $email = trim($_POST['email']);
        $direccion = trim($_POST['direccion']);
        if ($nombre && $email && $direccion) {
            $stmt = $conn->prepare("UPDATE cliente SET nombre=?, email=?, direccion=? WHERE id_cliente=?");
            $stmt->execute([$nombre, $email, $direccion, $id]);
        }
        header("Location: clientes.php");
        exit;
    } elseif ($accion == 'eliminar') {
        $id = intval($_POST['id_cliente']);
        try {
            $stmt = $conn->prepare("DELETE FROM cliente WHERE id_cliente=?");
            $stmt->execute([$id]);
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') {
                $mensajeError = "No se puede eliminar el cliente porque tiene una venta pendiente.";
            } else {
                $mensajeError = "Error al eliminar: " . $e->getMessage();
            }
        }
        if ($mensajeError === '') {
            header("Location: clientes.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Gestión de Clientes</title>
<link rel="stylesheet" href="css/estilos.css">
<style>
/* Mantengo los estilos que ya tienes */
body { font-family: Arial, sans-serif; background: #ecf6fc; margin:0; color:#333; }
h2 { text-align:center; color:#2c3e50; margin-top:20px; }
.main-container { max-width:900px; margin:20px auto; padding:15px; background:#f1faee; border-radius:8px; }
.mensaje-error { color:#c0392b; font-weight:bold; text-align:center; margin:10px 0; }
.explicacion { background:#f1faee; padding:12px; margin:15px 0; border-radius:6px; font-size:14px; line-height:1.5; }
.buscar-form, .agregar-form, .editar-form { display:flex; gap:10px; margin-bottom:20px; flex-wrap:wrap; }
.buscar-form input, .agregar-form input, .editar-form input { flex:1; padding:8px; border:1px solid #457b9d; border-radius:4px; }
button { background:#457b9d; color:white; border:none; padding:8px 16px; border-radius:4px; cursor:pointer; }
button:hover { background:#1d3557; }
.cliente-card { background:white; border:1px solid #ccc; border-radius:6px; padding:12px; margin-bottom:15px; }
.acciones { display:flex; gap:10px; flex-wrap:wrap; }
.eliminar { background:#c0392b; }
@media(max-width:600px) {
 .buscar-form, .agregar-form, .editar-form { flex-direction:column; }
}
</style>
<script>
// Validación JS simple para el formulario de agregar
function validarAgregar() {
    var nombre = document.forms['formAgregar']['nombre'].value.trim();
    var email = document.forms['formAgregar']['email'].value.trim();
    var direccion = document.forms['formAgregar']['direccion'].value.trim();
    if (nombre === "" || email === "" || direccion === "") {
        alert("Por favor, completa todos los campos.");
        return false;
    }
    return true;
}
</script>
</head>
<body>

<div style="text-align:right; color:#c0392b; font-weight:bold; padding:10px;">
Usuario activo: <?=htmlspecialchars($_SESSION['usuario'])?>
</div>

<h2>Gestión de Clientes</h2>

<div class="main-container">

<div class="explicacion">
<p>Busca, agrega, modifica o elimina clientes. Se requiere nombre, email y dirección para registrar nuevos clientes. Al presionar "Modificar" puedes editar un cliente; al presionar "Eliminar", lo borrarás si no tiene ventas asociadas.</p>
</div>

<?php if ($mensajeError): ?>
<div class="mensaje-error"><?=htmlspecialchars($mensajeError)?></div>
<?php endif; ?>

<form method="get" class="buscar-form">
    <input type="text" name="buscar" value="<?=htmlspecialchars($buscar)?>" placeholder="Buscar cliente">
    <button type="submit">Buscar</button>
</form>

<h3>Agregar nuevo cliente</h3>
<form name="formAgregar" method="post" class="agregar-form" onsubmit="return validarAgregar()">
    <input type="hidden" name="accion" value="agregar">
    <input type="hidden" name="csrf_token" value="<?=$csrf_token?>">
    <input type="text" name="nombre" placeholder="Nombre" required>
    <input type="email" name="email" placeholder="Email" required>
    <input type="text" name="direccion" placeholder="Dirección" required>
    <button type="submit">Agregar</button>
</form>

<h3>Listado de Clientes</h3>
<?php foreach ($clientes as $c): ?>
<div class="cliente-card">
    <div class="cliente-datos">
        <input type="text" value="<?=htmlspecialchars($c['nombre'])?>" disabled>
        <input type="email" value="<?=htmlspecialchars($c['email'])?>" disabled>
        <input type="text" value="<?=htmlspecialchars($c['direccion'])?>" disabled>
    </div>
    <div class="acciones">
        <form method="get" style="margin:0;">
            <input type="hidden" name="editar" value="<?=$c['id_cliente']?>">
            <button type="submit">Modificar</button>
        </form>
        <form method="post" style="margin:0;">
            <input type="hidden" name="id_cliente" value="<?=$c['id_cliente']?>">
            <input type="hidden" name="csrf_token" value="<?=$csrf_token?>">
            <button type="submit" name="accion" value="eliminar" class="eliminar">Eliminar</button>
        </form>
    </div>
</div>
<?php endforeach; ?>

<?php if ($clienteEditar): ?>
<h3>Editar cliente</h3>
<form method="post" class="editar-form">
    <input type="hidden" name="accion" value="editar">
    <input type="hidden" name="id_cliente" value="<?=$clienteEditar['id_cliente']?>">
    <input type="hidden" name="csrf_token" value="<?=$csrf_token?>">
    <input type="text" name="nombre" value="<?=htmlspecialchars($clienteEditar['nombre'])?>" required>
    <input type="email" name="email" value="<?=htmlspecialchars($clienteEditar['email'])?>" required>
    <input type="text" name="direccion" value="<?=htmlspecialchars($clienteEditar['direccion'])?>" required>
    <button type="submit">Guardar cambios</button>
</form>
<?php endif; ?>

<div style="margin-top:15px;">
    <a href="index.php"><button>Volver al Panel</button></a>
</div>

</div>
</body>
</html>













