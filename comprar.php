<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require 'db.php';

// Obtener clientes y productos
$stmt = $conn->query("SELECT id_cliente, nombre FROM CLIENTE ORDER BY nombre");
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->query("SELECT id_producto, nombre, precio, stock FROM PRODUCTO ORDER BY nombre");
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_cliente = $_POST['id_cliente'] ?? '';
    $productosSeleccionados = $_POST['productos'] ?? []; // id_producto => cantidad

    if (!$id_cliente) {
        $error = 'Seleccione un cliente.';
    } elseif (empty($productosSeleccionados)) {
        $error = 'Seleccione al menos un producto con cantidad.';
    } else {
        $totalCompra = 0;
        $fechaCompra = date('Y-m-d H:i:s');
        try {
            $conn->beginTransaction();

            foreach ($productosSeleccionados as $id_producto => $cantidad) {
                $cantidad = intval($cantidad);
                if ($cantidad < 1) continue;

                // Validar que exista el producto
                $stmtProd = $conn->prepare("SELECT precio, stock FROM PRODUCTO WHERE id_producto = ?");
                $stmtProd->execute([$id_producto]);
                $productoInfo = $stmtProd->fetch(PDO::FETCH_ASSOC);

                if (!$productoInfo) {
                    throw new Exception("Producto con ID $id_producto no encontrado.");
                }

                if ($cantidad > $productoInfo['stock']) {
                    throw new Exception("Stock insuficiente para el producto con ID $id_producto.");
                }

                $subtotal = $productoInfo['precio'] * $cantidad;
                $totalCompra += $subtotal;

                // Insertar compra
                $stmtInsert = $conn->prepare("INSERT INTO COMPRA (cantidad, total, fecha, id_producto, id_cliente) VALUES (?, ?, ?, ?, ?)");
                $stmtInsert->execute([$cantidad, $subtotal, $fechaCompra, $id_producto, $id_cliente]);

                // Actualizar stock
                $nuevoStock = $productoInfo['stock'] - $cantidad;
                $stmtUpdateStock = $conn->prepare("UPDATE PRODUCTO SET stock = ? WHERE id_producto = ?");
                $stmtUpdateStock->execute([$nuevoStock, $id_producto]);
            }

            $conn->commit();
            $mensaje = "Compra realizada con éxito. Total: $" . number_format($totalCompra, 0, ',', '.');
        } catch (Exception $e) {
            $conn->rollBack();
            $error = "Error en la compra: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Realizar Compra</title>
<style>
body { font-family: Arial, sans-serif; background: #eaf6fb; margin:0; padding:20px; color:#154360; }
h2 { text-align:center; color:#21618c; margin-top:10px; }
.usuario-activo { text-align:right; margin-bottom:10px; color:#c0392b; font-weight:bold; }
form { max-width:600px; margin:20px auto; background:white; padding:20px; border-radius:10px; box-shadow: 0 0 10px rgba(0,0,0,0.1);}
label { display:block; margin-top:15px; font-weight:bold; }
select, input[type=number] { width:100%; padding:8px; margin-top:5px; border:1px solid #aed6f1; border-radius:5px; }
.producto-cantidad { display:flex; gap:10px; margin-bottom: 10px; }
.producto-cantidad select { flex: 2; }
.producto-cantidad input { flex: 1; }
button { background:#2980b9; color:white; border:none; padding:10px 15px; border-radius:5px; cursor:pointer; margin-top:15px; }
button:hover { background:#1f618d; }
.mensaje { margin: 15px auto; max-width: 600px; padding: 10px; border-radius: 5px; text-align:center; }
.mensaje.exito { background: #d4edda; color: #155724; }
.mensaje.error { background: #f8d7da; color: #721c24; }
.volver { text-align:center; margin-top: 20px; }
</style>
<script>
function agregarProducto() {
    const container = document.getElementById('productos-container');
    const productoDiv = document.createElement('div');
    productoDiv.className = 'producto-cantidad';

    const select = document.createElement('select');
    const inputCant = document.createElement('input');
    inputCant.type = 'number';
    inputCant.min = 1;
    inputCant.value = 1;

    <?php
    $jsProductos = json_encode(array_map(function($p){
        return ['id' => $p['id_producto'], 'nombre' => $p['nombre'], 'stock' => $p['stock']];
    }, $productos));
    ?>
    const productos = <?= $jsProductos ?>;

    productos.forEach(p => {
        let option = document.createElement('option');
        option.value = p.id;
        option.text = `${p.nombre} (stock: ${p.stock})`;
        select.appendChild(option);
    });

    // Actualizar name según producto seleccionado
    select.onchange = () => {
        inputCant.name = `productos[${select.value}]`;
    };
    select.dispatchEvent(new Event('change'));

    productoDiv.appendChild(select);
    productoDiv.appendChild(inputCant);
    container.appendChild(productoDiv);
}

window.onload = () => {
    agregarProducto();
};
function validarFormulario() {
    const cliente = document.getElementById('id_cliente').value;
    if (!cliente) {
        alert('Seleccione un cliente.');
        return false;
    }
    const cantidades = document.querySelectorAll('#productos-container input[type=number]');
    for(let c of cantidades) {
        if (c.value < 1) {
            alert('Cantidad debe ser mayor a 0.');
            return false;
        }
    }
    return true;
}
</script>
</head>
<body>

<div class="usuario-activo">
    Usuario activo: <?= htmlspecialchars($_SESSION['usuario']) ?>
</div>

<h2>Realizar Compra</h2>

<?php if ($mensaje): ?>
    <div class="mensaje exito"><?= htmlspecialchars($mensaje) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="mensaje error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" onsubmit="return validarFormulario()">
    <label for="id_cliente">Selecciona Cliente:</label>
    <select name="id_cliente" id="id_cliente" required>
        <option value="">-- Seleccione Cliente --</option>
        <?php foreach($clientes as $c): ?>
            <option value="<?= $c['id_cliente'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
        <?php endforeach; ?>
    </select>

    <label>Productos a comprar:</label>
    <div id="productos-container"></div>

    <button type="button" onclick="agregarProducto()">Agregar otro producto</button>
    <button type="submit">Realizar Compra</button>
</form>

<div class="volver">
    <a href="index.php"><button>Volver al Panel</button></a>
</div>

</body>
</html>