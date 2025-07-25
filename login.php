<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require 'db.php';

$login_error = "";
$registro_error = "";
$registro_exito = "";

// Procesar login
if (isset($_POST['accion']) && $_POST['accion'] === 'login') {
    $usuario = trim($_POST['usuario']);
    $password = trim($_POST['password']);

    // Usar columna username en vez de usuario
    $stmt = $conn->prepare("SELECT * FROM usuario WHERE username = ?");
    $stmt->execute([$usuario]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        if ($password === $user['password']) { // solo para pruebas, sin hash
            $_SESSION['usuario'] = $usuario;
            header("Location: index.php");
            exit();
        } else {
            $login_error = "Contraseña incorrecta.";
        }
    } else {
        $login_error = "Usuario no existe.";
    }
}

// Procesar registro
if (isset($_POST['accion']) && $_POST['accion'] === 'registro') {
    $nuevo_usuario = trim($_POST['nuevo_usuario']);
    $nuevo_password = trim($_POST['nuevo_password']);
    $nuevo_password2 = trim($_POST['nuevo_password2']);

    if ($nuevo_password !== $nuevo_password2) {
        $registro_error = "Las contraseñas no coinciden.";
    } elseif (strlen($nuevo_password) < 4) {
        $registro_error = "La contraseña debe tener al menos 4 caracteres.";
    } else {
        // Buscar por username
        $stmt = $conn->prepare("SELECT * FROM usuario WHERE username = ?");
        $stmt->execute([$nuevo_usuario]);
        if ($stmt->fetch()) {
            $registro_error = "El usuario ya existe.";
        } else {
            $stmt = $conn->prepare("INSERT INTO usuario (username, password) VALUES (?, ?)");
            $stmt->execute([$nuevo_usuario, $nuevo_password]);
            $registro_exito = "Usuario creado con éxito. Ahora puedes iniciar sesión.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Login y Registro - Tienda Online</title>
<link rel="stylesheet" href="css/estilos.css">
<style>
    body {
        font-family: Arial, sans-serif;
        background-color: #eaf2f8;
        margin: 0;
        padding: 0;
    }
    header {
        text-align: center;
        background-color: #2471a3;
        color: white;
        padding: 20px 10px;
    }
    header h1 {
        margin: 0;
        font-size: 28px;
    }
    .usuario-prueba {
        color: #154360;
        font-weight: bold;
        background-color: #d6eaf8;
        padding: 10px 15px;
        border-radius: 8px;
        max-width: 400px;
        margin: 15px auto;
        text-align: center;
    }
    .form-wrapper {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 40px;
        margin-top: 20px;
    }
    form {
        background: #f0f8ff;
        padding: 20px;
        border-radius: 10px;
        width: 280px;
        box-shadow: 0 4px 8px rgba(0,70,150,0.1);
    }
    form h3 {
        text-align: center;
        margin-bottom: 15px;
        color: #1a5276;
    }
    label {
        display: block;
        margin-top: 10px;
        font-weight: bold;
        color: #154360;
    }
    input {
        width: 100%;
        padding: 6px;
        margin-top: 5px;
        border: 1px solid #aed6f1;
        border-radius: 4px;
    }
    button {
        margin-top: 15px;
        width: 100%;
        padding: 8px;
        background-color: #2980b9;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }
    button:hover {
        background-color: #1b4f72;
    }
    .mensaje {
        margin-top: 10px;
        font-weight: bold;
        text-align: center;
    }
    .mensaje.error {
        color: #c0392b;
    }
    .mensaje.exito {
        color: #27ae60;
    }
</style>
</head>
<body>
<header>
    <h1>Tienda Online - Login y Registro</h1>
</header>

<p class="usuario-prueba">
    Usuario de prueba: <span style="color:#1b4f72;">admin</span> / Contraseña: <span style="color:#1b4f72;">1234</span>
</p>

<div class="form-wrapper">

    <!-- Formulario de Login -->
    <form method="post" autocomplete="off">
        <h3>Iniciar Sesión</h3>
        <input type="hidden" name="accion" value="login">
        <label>Usuario:</label>
        <input type="text" name="usuario" required>
        <label>Contraseña:</label>
        <input type="password" name="password" required>
        <button type="submit">Ingresar</button>
        <?php if ($login_error): ?>
            <p class="mensaje error"><?= htmlspecialchars($login_error) ?></p>
        <?php endif; ?>
    </form>

    <!-- Formulario de Registro -->
    <form method="post" autocomplete="off">
        <h3>Crear Nuevo Usuario</h3>
        <input type="hidden" name="accion" value="registro">
        <label>Usuario:</label>
        <input type="text" name="nuevo_usuario" required>
        <label>Contraseña:</label>
        <input type="password" name="nuevo_password" required>
        <label>Repetir Contraseña:</label>
        <input type="password" name="nuevo_password2" required>
        <button type="submit">Registrar</button>
        <?php if ($registro_error): ?>
            <p class="mensaje error"><?= htmlspecialchars($registro_error) ?></p>
        <?php elseif ($registro_exito): ?>
            <p class="mensaje exito"><?= htmlspecialchars($registro_exito) ?></p>
        <?php endif; ?>
    </form>

</div>
</body>
</html>
