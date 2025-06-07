<?php 
include 'db.php';
session_start();

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $correo = trim($_POST['correo']);
    $telefono = trim($_POST['telefono']);
    $password_plano = $_POST['password'] ?? '';

    // Validación básica de campos
    if ($username && $correo && $telefono && $password_plano) {

        // Validar formato email
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $error = "El correo electrónico no es válido.";
        } elseif (strlen($password_plano) < 6) {
            $error = "La contraseña debe tener al menos 6 caracteres.";
        } else {

            // Hashear contraseña
            $password = password_hash($password_plano, PASSWORD_DEFAULT);

            // Verificar si el usuario o correo ya existen
            $check = $conn->prepare("SELECT 1 FROM usuario WHERE Username = ? OR Email = ?");
            if (!$check) {
                $error = "Error en la consulta: " . $conn->error;
            } else {
                $check->bind_param("ss", $username, $correo);
                $check->execute();
                $res = $check->get_result();

                if ($res->num_rows > 0) {
                    $error = "El nombre de usuario o correo ya está en uso.";
                } else {
                    // Obtener IdRol para 'Cliente'
                    $rolQuery = $conn->prepare("SELECT IdRol FROM rol WHERE LOWER(Nombre) = 'cliente' LIMIT 1");
                    $rolQuery->execute();
                    $rolResult = $rolQuery->get_result();
                    if ($rolResult->num_rows === 1) {
                        $rolRow = $rolResult->fetch_assoc();
                        $idRol = $rolRow['IdRol'];
                    } else {
                        $error = "No se encontró el rol 'Cliente' en la base de datos.";
                    }
                    $rolQuery->close();

                    // Insertar nuevo usuario
                    if (!$error) {
                        $stmt = $conn->prepare("INSERT INTO usuario (Username, Email, Telefono, Password, IdRol, IdEstatus, FechaCreacion) VALUES (?, ?, ?, ?, ?, 1, NOW())");
                        if (!$stmt) {
                            $error = "Error en la consulta: " . $conn->error;
                        } else {
                            $stmt->bind_param("ssssi", $username, $correo, $telefono, $password, $idRol);
                            if ($stmt->execute()) {
                                // Guardar sesión y redirigir
                                $_SESSION['username'] = $username;
                                $_SESSION['role'] = 'cliente';
                                echo "<script>alert('Cuenta creada exitosamente'); window.location.href='ver_tienda.php';</script>";
                                exit;
                            } else {
                                $error = "Error al registrar: " . $stmt->error;
                            }
                            $stmt->close();
                        }
                    }
                }
                $check->close();
            }
        }
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
            cursor: pointer;
        }
        button:hover {
            background-color: #218838;
        }
        .mensaje, .error { margin-top: 15px; text-align: center; }
        .mensaje { color: #007bff; }
        .error { color: red; }
    </style>
</head>
<body>

<div class="registro-box">
    <h2>Crear cuenta</h2>
    <form method="POST" novalidate>
        <label for="username">Nombre de usuario:</label>
        <input type="text" id="username" name="username" required>

        <label for="correo">Correo electrónico:</label>
        <input type="email" id="correo" name="correo" required>

        <label for="telefono">Teléfono:</label>
        <input type="text" id="telefono" name="telefono" required pattern="\d{10,15}" title="Solo números, entre 10 y 15 dígitos">

        <label for="password">Contraseña:</label>
        <input type="password" id="password" name="password" required minlength="6">

        <button type="submit">Registrarse</button>
    </form>

    <?php if ($mensaje): ?>
        <p class="mensaje"><?= htmlspecialchars($mensaje) ?></p>
    <?php endif; ?>
    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
</div>

</body>
</html>
