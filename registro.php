<?php
include 'db.php';
session_start();

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $correo = trim($_POST['correo']);
    $telefono = trim($_POST['telefono']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Validación básica
    if ($username && $correo && $telefono && $password) {

        // Verificar si el usuario o correo ya existen
        $check = $conn->prepare("SELECT * FROM usuarios WHERE username = ? OR email = ?");
        $check->bind_param("ss", $username, $correo);
        $check->execute();
        $res = $check->get_result();

        if ($res->num_rows > 0) {
            $error = "El nombre de usuario o correo ya está en uso.";
        } else {
            $stmt = $conn->prepare("INSERT INTO usuarios (username, email, telefono, password, role) VALUES (?, ?, ?, ?, 'cliente')");
            $stmt->bind_param("ssss", $username, $correo, $telefono, $password);
            if ($stmt->execute()) {
                $_SESSION['username'] = $username;
                $_SESSION['role'] = 'cliente';
                echo "<script>alert('Cuenta creada exitosamente'); window.location.href='ver_tienda.php';</script>";
                exit;
            } else {
                $error = "Error al registrar: " . $stmt->error;
            }
            $stmt->close();
        }
        $check->close();
    } else {
        $error = "Por favor, completa todos los campos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro</title>
    <style>
        body { font-family: Arial; background-color: #f4f4f4; padding: 40px; }
        .registro-box {
            background: white; max-width: 400px; margin: auto; padding: 30px;
            border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h2 { text-align: center; color: #333; }
        input[type="text"], input[type="email"], input[type="password"] {
            width: 100%; padding: 10px; margin: 10px 0;
            border: 1px solid #ccc; border-radius: 5px;
        }
        button {
            width: 100%; background-color: #28a745; color: white;
            padding: 12px; border: none; border-radius: 5px; font-weight: bold;
        }
        .mensaje, .error { margin-top: 15px; text-align: center; }
        .mensaje { color: #007bff; }
        .error { color: red; }
    </style>
</head>
<body>

<div class="registro-box">
    <h2>Crear cuenta</h2>
    <form method="POST">
        <label>Nombre de usuario:</label>
        <input type="text" name="username" required>

        <label>Correo electrónico:</label>
        <input type="email" name="correo" required>

        <label>Teléfono:</label>
        <input type="text" name="telefono" required>

        <label>Contraseña:</label>
        <input type="password" name="password" required>

        <button type="submit">Registrarse</button>
    </form>

    <?php if ($mensaje): ?>
        <p class="mensaje"><?= $mensaje ?></p>
    <?php endif; ?>
    <?php if ($error): ?>
        <p class="error"><?= $error ?></p>
    <?php endif; ?>
</div>

</body>
</html>
