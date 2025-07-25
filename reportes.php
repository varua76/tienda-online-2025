<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require 'db.php';

// Obtener clientes con 2 o más compras
$stmt = $conn->query("
    SELECT c.id_cliente, c.nombre, c.email, COUNT(co.id_compra) AS total_compras
    FROM CLIENTE c
    JOIN COMPRA co ON c.id_cliente = co.id_cliente
    GROUP BY c.id_cliente
    HAVING total_compras >= 2
    ORDER BY total_compras DESC, c.nombre
");
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Reportes de Compras</title>
<style>
body { font-family: Arial, sans-serif; background: #eaf6fb; margin:0; padding:20px; color:#154360; }
h2 { text-align:center; color:#21618c; margin-top:10px; }
.usuario-activo { text-align:right; margin-bottom:10px; color:#c0392b; font-weight:bold; }
table { width: 90%; margin: 20px auto; border-collapse: collapse; background: white; }
th, td { padding: 10px; border-bottom: 1px solid #aed6f1; text-align: left; }
th { background: #5dade2; color: white; }
a.button, button { 
    background:#2980b9; 
    color:white; 
    border:none; 
    padding:8px 12px; 
    border-radius:5px; 
    cursor:pointer; 
    text-decoration:none; 
    margin: 5px 0;
    display: inline-block;
}
a.button:hover, button:hover { background:#1f618d; }
button.print-btn { background:#27ae60; }
button.print-btn:hover { background:#1e8449; }
.modal {
    display:none; 
    position: fixed; 
    z-index: 10; 
    left: 0; top: 0; width: 100%; height: 100%; 
    overflow: auto; 
    background-color: rgba(0,0,0,0.4);
}
.modal-content {
    background-color: #fefefe;
    margin: 10% auto; 
    padding: 20px;
    border: 1px solid #888;
    width: 80%;
    max-width: 600px;
    border-radius: 10px;
    box-shadow: 0 0 15px rgba(0,0,0,0.2);
}
.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}
.close:hover, .close:focus {
    color: black;
}
</style>
</head>
<body>

<div class="usuario-activo">
    Usuario activo: <?= htmlspecialchars($_SESSION['usuario']) ?>
</div>

<h2>Clientes con 2 o más Compras</h2>

<table>
    <thead>
        <tr>
            <th>Nombre</th>
            <th>Email</th>
            <th>Total Compras</th>
            <th>Acción</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($clientes): ?>
            <?php foreach ($clientes as $cliente): ?>
            <tr>
                <td><?= htmlspecialchars($cliente['nombre']) ?></td>
                <td><?= htmlspecialchars($cliente['email']) ?></td>
                <td><?= $cliente['total_compras'] ?></td>
                <td><button class="btn-detalle" data-id="<?= $cliente['id_cliente'] ?>">Ver Detalle</button></td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="4" style="text-align:center;">No hay clientes con 2 o más compras.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<div style="text-align:center; margin-top:20px;">
    <a href="index.php" class="button">Volver al Panel</a>
</div>

<!-- Modal para detalle -->
<div id="modalDetalle" class="modal">
  <div class="modal-content">
    <span class="close">&times;</span>
    <h3>Detalle de Compras</h3>
    <table id="detalleTabla" style="width:100%; border-collapse: collapse;">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Subtotal ($)</th>
                <th>Fecha Compra</th>
            </tr>
        </thead>
        <tbody></tbody>
        <tfoot>
            <tr>
                <td colspan="2" style="text-align:right; font-weight:bold;">Total a Pagar:</td>
                <td id="totalPagar" style="font-weight:bold;"></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
    <button class="print-btn" onclick="window.print()">Imprimir</button>
  </div>
</div>

<script>
// Cerrar modal
const modal = document.getElementById('modalDetalle');
const spanCerrar = document.querySelector('.close');

spanCerrar.onclick = () => {
    modal.style.display = 'none';
}
window.onclick = (event) => {
    if (event.target === modal) {
        modal.style.display = 'none';
    }
}

// Manejar click en "Ver Detalle"
document.querySelectorAll('.btn-detalle').forEach(btn => {
    btn.addEventListener('click', () => {
        const clienteId = btn.getAttribute('data-id');

        // Obtener detalle vía fetch con Ajax
        fetch('detalle_compras.php?id_cliente=' + clienteId)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
                return;
            }
            const tbody = document.querySelector('#detalleTabla tbody');
            tbody.innerHTML = '';
            let total = 0;
            data.detalle.forEach(item => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${item.producto}</td>
                    <td>${item.cantidad}</td>
                    <td>$${parseFloat(item.total).toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ".")}</td>
                    <td>${item.fecha}</td>
                `;
                tbody.appendChild(tr);
                total += parseFloat(item.total);
            });
            document.getElementById('totalPagar').textContent = `$${total.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ".")}`;
            modal.style.display = 'block';
        })
        .catch(() => alert('Error cargando detalle'));
    });
});
</script>

</body>
</html>
