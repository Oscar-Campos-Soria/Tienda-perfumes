<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("⛔ Acceso denegado.");
}

$usuario = $_GET['usuario'] ?? '';
$desde = $_GET['desde'] ?? '';
$hasta = $_GET['hasta'] ?? '';

$where = [];
if ($usuario) $where[] = "usuario LIKE '%$usuario%'";
if ($desde) $where[] = "fecha >= '$desde 00:00:00'";
if ($hasta) $where[] = "fecha <= '$hasta 23:59:59'";

$condicion = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';
$sql = "SELECT * FROM logs $condicion ORDER BY fecha DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Logs</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f0f2f5;
            padding: 40px;
        }

        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }

        form {
            text-align: center;
            margin-bottom: 20px;
        }

        input[type="text"], input[type="date"] {
            padding: 8px;
            margin: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        button {
            background-color: #007bff;
            color: white;
            padding: 8px 14px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }

        th, td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            text-align: center;
        }

        th {
            background-color: #007bff;
            color: white;
        }

        .btn-container {
            text-align: center;
            margin-top: 30px;
        }

        .btn {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 10px 16px;
            margin: 5px;
            border-radius: 5px;
            text-decoration: none;
        }

        .btn:hover {
            background-color: #0056b3;
        }

        .limpiar {
            margin-left: 10px;
            text-decoration: none;
            color: red;
        }
    </style>
</head>
<body>

<h2>📋 Registro de Actividades</h2>

<form method="GET">
    <input type="text" name="usuario" placeholder="Filtrar por usuario" value="<?= htmlspecialchars($usuario) ?>">
    <input type="date" name="desde" value="<?= $desde ?>">
    <input type="date" name="hasta" value="<?= $hasta ?>">
    <button type="submit">Buscar</button>
    <a href="ver_logs.php" class="limpiar">Limpiar</a>
</form>

<?php if ($result->num_rows > 0): ?>
    <table>
        <tr>
            <th>#</th>
            <th>Usuario</th>
            <th>Acción</th>
            <th>Fecha</th>
        </tr>
        <?php while ($log = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $log['id'] ?></td>
                <td><?= htmlspecialchars($log['usuario']) ?></td>
                <td><?= htmlspecialchars($log['accion']) ?></td>
                <td><?= $log['fecha'] ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
<?php else: ?>
    <p style="text-align: center; color: #777;">No se encontraron registros con los filtros aplicados.</p>
<?php endif; ?>

<div class="btn-container">
    <a href="listar_productos.php" class="btn">🧴 Ver Productos</a>
    <a href="admin_pedidos.php" class="btn">📦 Ver Pedidos</a>
    <a href="logout.php" class="btn" style="background-color: #dc3545;">Cerrar sesión</a>
</div>

</body>
</html>
