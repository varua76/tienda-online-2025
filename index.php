<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Panel de Administración</title>
<link rel="stylesheet" href="css/estilos.css">
<style>
.container-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 10px;
    max-width: 800px;
    margin: 20px auto;
}
button {
    padding: 10px 20px;
    margin: 5px;
    cursor: pointer;
    border: none;
    border-radius: 4px;
    background-color: #457b9d;
    color: white;
}
button:hover {
    background-color: #1d3557;
}
.instrucciones {
    max-width: 800px;
    margin: 10px auto;
    background: #f1faee;
    padding: 15px;
    border-radius: 8px;
    color: #1d3557;
}
</style>
</head>
<body>

<div style="text-align:right; color:#c0392b; font-weight:bold; padding:10px;">
    Usuario activo: <?= htmlspecialchars($_SESSION['usuario']) ?>
</div>

<h2 style="text-align:center;">Panel de Administración</h2>

<div class="instrucciones">
    Bienvenido al sistema de administración.  
    Desde aquí puede gestionar clientes, productos, realizar ventas y ver reportes.  
    Seleccione la opción deseada usando los botones de abajo.
</div>

<div class="container-grid">
    <a href="clientes.php"><button>Gestionar Clientes</button></a>
    <a href="productos.php"><button>Gestionar Productos</button></a>
    <a href="comprar.php"><button>Realizar Ventas</button></a>
    <a href="reportes.php"><button>Ver Reportes</button></a>
    <a href="logout.php"><button>Cerrar sesión</button></a>
</div>

</body>
</html>

