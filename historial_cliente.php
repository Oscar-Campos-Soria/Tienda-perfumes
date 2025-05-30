<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'cliente') {
    header("Location: login.php");
    exit;
}

$usuario = $_SESSION['username'];
$desde = $_GET['desde'] ?? '';
$hasta = $_GET['hasta'] ?? '';

// Filtro de fecha con protección básica
$filtros = "WHERE usuario = ?";
$parametros = [$usuario];
$tipos = "s";

if ($desde) {
    $filtros .= " AND fecha_pedido >= ?";
    $parametros[] = "$desde 00:00:00";
    $tipos .= "s";
}
if ($hasta) {
    $filtros .= " AND fecha_pedido <= ?";
    $parametros[] = "$hasta 23:59:59";
    $tipos .= "s";
}

$stmt = $conn->prepare("SELECT * FROM pedidos $filtros ORDER BY fecha_pedido DESC");
$stmt->bind_param($tipos, ...$parametros);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Historial de Compras</title>
    <style>
        body { font-family: Arial; background: #f4f4f4; padding: 30px; }
        h2 { color: #333; }
        table { width: 100%; border-collapse: collapse; background: white; margin-top: 20px; box-shadow: 0 0 6px rgba(0,0,0,0.1); }
        th, td { padding: 10px; border: 1px solid #ccc; text-align: center; }
        th { background: #007bff; color: white; }
        .entregado { color: green; font-weight: bold; }
        .pendiente { color: orange; font-weight: bold; }
        .detalle { margin-top: 10px; background: #fff; border: 1px solid #ddd; padding: 10px; border-radius: 5px; }
        form { margin-top: 20px; }
        input[type="date"] { padding: 6px; border: 1px solid #ccc; border-radius: 5px; margin-right: 10px; }
        button { padding: 6px 12px; background: #007bff; color: white; border: none; border-radius: 5px; }
        .logout { background: #dc3545; color: white; padding: 10px; text-decoration: none; border-radius: 5px; float: right; }
        a.detalle-link { color: #007bff; text-decoration: none; }
    </style>
</head>
<body>

<a href="logout.php" class="logout">Cerrar sesión</a>
<h2>🛍️ Mi Historial de Compras</h2>

<form method="GET">
    <label>Desde: <input type="date" name="desde" value="<?= htmlspecialchars($desde) ?>"></label>
    <label>Hasta: <input type="date" name="hasta" value="<?= htmlspecialchars($hasta) ?>"></label>
    <button type="submit">Filtrar</button>
    <a href="historial_cliente.php" style="margin-left: 10px;">Limpiar</a>
</form>

<?php if ($result->num_rows > 0): ?>
<table>
    <tr>
        <th>ID</th>
        <th>Fecha</th>
        <th>Total</th>
        <th>Método</th>
        <th>Estado</th>
        <th>Detalles</th>
    </tr>
    <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= $row['fecha_pedido'] ?></td>
            <td>$<?= number_format($row['total'], 2) ?></td>
            <td><?= htmlspecialchars($row['metodo_pago']) ?></td>
            <td><?= $row['entregado'] ? '<span class="entregado">Entregado</span>' : '<span class="pendiente">Pendiente</span>' ?></td>
            <td>
                <a href="?<?= http_build_query(array_merge($_GET, ['ver' => $row['id']])) ?>" class="detalle-link">Ver detalle</a>
            </td>
        </tr>
        <?php if (isset($_GET['ver']) && $_GET['ver'] == $row['id']): 
            $detalle = $conn->query("SELECT dp.*, p.Nombre FROM detalle_pedidos dp INNER JOIN productos p ON dp.producto_id = p.Id WHERE dp.pedido_id = " . intval($row['id']));
        ?>
        <tr>
            <td colspan="6">
                <div class="detalle">
                    <strong>Detalle del pedido #<?= $row['id'] ?>:</strong>
                    <table>
                        <tr>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Precio Unitario</th>
                            <th>Subtotal</th>
                        </tr>
                        <?php while($det = $detalle->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($det['Nombre']) ?></td>
                                <td><?= $det['cantidad'] ?></td>
                                <td>$<?= number_format($det['precio_unitario'], 2) ?></td>
                                <td>$<?= number_format($det['precio_unitario'] * $det['cantidad'], 2) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </table>
                </div>
            </td>
        </tr>
        <?php endif; ?>
    <?php endwhile; ?>
</table>
<?php else: ?>
    <p>No hay pedidos para mostrar.</p>
<?php endif; ?>

</body>
</html>
