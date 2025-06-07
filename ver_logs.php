<?php
session_start();
include 'db.php';

// Solo pueden ver esta página los usuarios con rol Administrador
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'administrador') {
    die("⛔ Acceso denegado.");
}

$usuario = $_GET['usuario'] ?? '';
$desde = $_GET['desde'] ?? '';
$hasta = $_GET['hasta'] ?? '';

$where = [];
if ($usuario) $where[] = "Usuario LIKE '%" . $conn->real_escape_string($usuario) . "%'";
if ($desde) $where[] = "Fecha >= '" . $conn->real_escape_string($desde) . " 00:00:00'";
if ($hasta) $where[] = "Fecha <= '" . $conn->real_escape_string($hasta) . " 23:59:59'";

$condicion = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';
$sql = "SELECT * FROM auditoria $condicion ORDER BY Fecha DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Auditoría del Sistema</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f0f2f5; padding: 40px; }
        h2 { text-align: center; color: #333; margin-bottom: 30px; }
        form { text-align: center; margin-bottom: 20px; }
        input[type="text"], input[type="date"] { padding: 8px; margin: 5px; border: 1px solid #ccc; border-radius: 5px; }
        button { background-color: #007bff; color: white; padding: 8px 14px; border: none; border-radius: 5px; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; background: white; box-shadow: 0 0 10px rgba(0,0,0,0.05); }
        th, td { padding: 10px; border-bottom: 1px solid #ddd; text-align: center; }
        th { background-color: #007bff; color: white; }
        .btn-container { text-align: center; margin-top: 30px; }
        .btn { display: inline-block; background-color: #007bff; color: white; padding: 10px 16px; margin: 5px; border-radius: 5px; text-decoration: none; }
        .btn:hover { background-color: #0056b3; }
        .limpiar { margin-left: 10px; text-decoration: none; color: red; }
        td pre { text-align: left; white-space: pre-wrap; word-wrap: break-word; max-width: 200px; margin: 0; font-family: Consolas, monospace; font-size: 0.85em; }
    </style>
</head>
<body>

<h2>📝 Registro de Auditoría</h2>

<form method="GET">
    <input type="text" name="usuario" placeholder="Filtrar por usuario" value="<?= htmlspecialchars($usuario) ?>">
    <input type="date" name="desde" value="<?= htmlspecialchars($desde) ?>">
    <input type="date" name="hasta" value="<?= htmlspecialchars($hasta) ?>">
    <button type="submit">Buscar</button>
    <a href="ver_auditoria.php" class="limpiar">Limpiar</a>
</form>

<?php if ($result && $result->num_rows > 0): ?>
    <table>
        <tr>
            <th>ID</th>
            <th>Tabla</th>
            <th>ID Registro</th>
            <th>Usuario</th>
            <th>Acción</th>
            <th>Fecha</th>
            <th>Datos Anteriores</th>
            <th>Datos Nuevos</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['IdAuditoria']) ?></td>
                <td><?= htmlspecialchars($row['Tabla']) ?></td>
                <td><?= htmlspecialchars($row['IdRegistro']) ?></td>
                <td><?= htmlspecialchars($row['Usuario']) ?></td>
                <td><?= htmlspecialchars($row['Accion']) ?></td>
                <td><?= htmlspecialchars($row['Fecha']) ?></td>
                <td><pre><?= htmlspecialchars($row['DatosAnteriores']) ?></pre></td>
                <td><pre><?= htmlspecialchars($row['DatosNuevos']) ?></pre></td>
            </tr>
        <?php endwhile; ?>
    </table>
<?php else: ?>
    <p style="text-align: center; color: #777;">No se encontraron registros con los filtros aplicados.</p>
<?php endif; ?>

<div class="btn-container">
    <a href="listar_producto.php" class="btn">🧴 Ver productos</a>
    <a href="admin_pedidos.php" class="btn">📦 Ver pedidos</a>
    <a href="logout.php" class="btn" style="background-color: #dc3545;">Cerrar sesión</a>
</div>

</body>
</html>
