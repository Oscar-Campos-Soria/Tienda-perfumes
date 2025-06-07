<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'cliente') {
    header("Location: login.php");
    exit;
}

$usuario = $_SESSION['username'];
$desde = $_GET['desde'] ?? '';
$hasta = $_GET['hasta'] ?? '';

// Construir filtros con protección
$filtros = "WHERE u.Username = ?";
$parametros = [$usuario];
$tipos = "s";

if ($desde) {
    $filtros .= " AND p.FechaPedido >= ?";
    $parametros[] = "$desde 00:00:00";
    $tipos .= "s";
}
if ($hasta) {
    $filtros .= " AND p.FechaPedido <= ?";
    $parametros[] = "$hasta 23:59:59";
    $tipos .= "s";
}

$sql = "SELECT p.IdPedido, p.FechaPedido, p.Total, m.Nombre AS MetodoPago, p.Entregado 
        FROM pedido p
        JOIN usuario u ON p.IdUsuario = u.IdUsuario
        JOIN metodopago m ON p.IdMetodoPago = m.IdMetodoPago
        $filtros
        ORDER BY p.FechaPedido DESC";

$stmt = $conn->prepare($sql);
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
        body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 30px; }
        h2 { color: #333; }
        table { width: 100%; border-collapse: collapse; background: white; margin-top: 20px; box-shadow: 0 0 6px rgba(0,0,0,0.1); }
        th, td { padding: 10px; border: 1px solid #ccc; text-align: center; }
        th { background: #007bff; color: white; }
        .entregado { color: green; font-weight: bold; }
        .pendiente { color: orange; font-weight: bold; }
        .detalle { margin-top: 10px; background: #fff; border: 1px solid #ddd; padding: 10px; border-radius: 5px; }
        form { margin-top: 20px; }
        input[type="date"] { padding: 6px; border: 1px solid #ccc; border-radius: 5px; margin-right: 10px; }
        button { padding: 6px 12px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
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
            <td><?= $row['IdPedido'] ?></td>
            <td><?= $row['FechaPedido'] ?></td>
            <td>$<?= number_format($row['Total'], 2) ?></td>
            <td><?= htmlspecialchars($row['MetodoPago']) ?></td>
            <td><?= $row['Entregado'] ? '<span class="entregado">Entregado</span>' : '<span class="pendiente">Pendiente</span>' ?></td>
            <td>
                <a href="?<?= http_build_query(array_merge($_GET, ['ver' => $row['IdPedido']])) ?>" class="detalle-link">Ver detalle</a>
            </td>
        </tr>
        <?php 
        if (isset($_GET['ver']) && $_GET['ver'] == $row['IdPedido']): 
            $detalle = $conn->prepare("SELECT dp.Cantidad, dp.PrecioUnitario, p.Nombre 
                                      FROM detalle_pedido dp 
                                      JOIN producto p ON dp.IdProducto = p.IdProducto 
                                      WHERE dp.IdPedido = ?");
            $detalle->bind_param("i", $row['IdPedido']);
            $detalle->execute();
            $resultadoDetalle = $detalle->get_result();
        ?>
        <tr>
            <td colspan="6">
                <div class="detalle">
                    <strong>Detalle del pedido #<?= $row['IdPedido'] ?>:</strong>
                    <table>
                        <tr>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Precio Unitario</th>
                            <th>Subtotal</th>
                        </tr>
                        <?php while($det = $resultadoDetalle->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($det['Nombre']) ?></td>
                                <td><?= $det['Cantidad'] ?></td>
                                <td>$<?= number_format($det['PrecioUnitario'], 2) ?></td>
                                <td>$<?= number_format($det['PrecioUnitario'] * $det['Cantidad'], 2) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </table>
                </div>
            </td>
        </tr>
        <?php 
            $detalle->close();
        endif; ?>
    <?php endwhile; ?>
</table>
<?php else: ?>
    <p>No hay pedidos para mostrar.</p>
<?php endif; ?>

</body>
</html>
