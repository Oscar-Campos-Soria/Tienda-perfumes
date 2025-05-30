<?php
session_start();
include 'db.php';
include 'log_helper.php'; // ✅ Agregado para registrar logs

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM usuarios WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $conn->query("SET @usuario_actual = '{$_SESSION['username']}'");


            // ✅ Registrar log de inicio de sesión
            $accion = "Inició sesión exitosamente";
            registrar_log($conn, $user['username'], $accion);

            // Redireccionar según rol
            $rol = $user['role'];
            echo "<script>
                alert('Inicio de sesión exitoso. Bienvenido $username');
                window.location.href = '" . ($rol === 'admin' ? 'listar_productos.php' : 'ver_tienda.php') . "';
            </script>";
            exit;
        } else {
            $error = "Contraseña incorrecta.";
        }
    } else {
        $error = "Usuario no encontrado.";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(to right, #f0f2f5, #d4dce4);
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .login-box {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        h2 {
            text-align: center;
            color: #333;
        }
        form {
            display: flex;
            flex-direction: column;
        }
        label {
            margin: 10px 0 5px;
            color: #555;
        }
        input[type="text"], input[type="password"] {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
        }
        button {
            margin-top: 20px;
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        .error {
            margin-top: 10px;
            color: red;
            font-size: 14px;
            text-align: center;
        }
        .extra {
            text-align: center;
            margin-top: 15px;
        }
        .extra a {
            text-decoration: none;
            color: #007bff;
        }
        .extra a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Iniciar Sesión</h2>
        <form method="POST">
            <label for="username">Usuario:</label>
            <input type="text" name="username" required>

            <label for="password">Contraseña:</label>
            <input type="password" name="password" required>

            <button type="submit">Entrar</button>

            <?php if ($error): ?>
                <p class="error"><?= $error ?></p>
            <?php endif; ?>
        </form>
        <div class="extra">
            ¿No tienes cuenta? <a href="registro.php">Crea una aquí</a>
        </div>
    </div>
</body>
</html>
