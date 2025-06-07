<?php
session_start();
include 'db.php'; // Conexión a la base de datos

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $sql = "SELECT u.IdUsuario, u.Username, u.Password, r.Nombre AS Rol
            FROM usuario u
            JOIN rol r ON u.IdRol = r.IdRol
            WHERE u.Username = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            // Verificar contraseña
            if (password_verify($password, $user['Password'])) {
                $_SESSION['username'] = $user['Username'];
                $_SESSION['role'] = strtolower($user['Rol']); // 'administrador' o 'cliente'
                $_SESSION['id_usuario'] = $user['IdUsuario'];

                // Redireccionar según rol
                if ($_SESSION['role'] === 'administrador') {
                    header("Location: listar_productos.php");
                } else {
                    header("Location: ver_tienda.php");
                }
                exit;
            } else {
                $error = "Contraseña incorrecta.";
            }
        } else {
            $error = "Usuario no encontrado.";
        }
        $stmt->close();
    } else {
        $error = "Error en la consulta SQL: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>Iniciar Sesión | Perfume Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            background: linear-gradient(120deg, #e0eafc, #cfdef3);
        }
        .card {
            margin-top: 80px;
            border-radius: 20px;
            box-shadow: 0 8px 30px rgba(31, 38, 135, 0.1);
        }
        .logo {
            font-size: 2.2em;
            color: #1976d2;
        }
    </style>
</head>
<body>
<div class="container d-flex justify-content-center align-items-center" style="min-height: 100vh;">
    <div class="col-md-5">
        <div class="card p-4">
            <div class="logo text-center mb-3"><i class="fas fa-spray-can-sparkles"></i> <b>Perfime</b></div>
            <h2 class="mb-4 text-center">Iniciar Sesión</h2>
            <form method="POST" novalidate>
                <div class="mb-3">
                    <label for="username" class="form-label">Usuario:</label>
                    <input type="text" class="form-control" id="username" name="username" required autofocus />
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Contraseña:</label>
                    <input type="password" class="form-control" id="password" name="password" required />
                </div>
                <?php if ($error): ?>
                    <div class="alert alert-danger py-1"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary w-100">Entrar</button>
            </form>
            <div class="text-center mt-3">
                ¿No tienes cuenta? <a href="registro.php">Crea una aquí</a>
            </div>
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>
</html>
