<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'administrador') {
    header("Location: login.php");
    exit;
}

$cliente = $_GET['cliente'] ?? '';
$desde = $_GET['desde'] ?? '';
$hasta = $_GET['hasta'] ?? '';

// Construir filtros con seguridad
$where = [];
$params = [];
$types = '';

if ($cliente) {
    $where[] = "u.Username LIKE ?";
    $params[] = "%$cliente%";
    $types .= 's';
}
if ($desde) {
    $where[] = "p.FechaPedido >= ?";
    $params[] = "$desde 00:00:00";
    $types .= 's';
}
if ($hasta) {
    $where[] = "p.FechaPedido <= ?";
    $params[] = "$hasta 23:59:59";
    $types .= 's';
}

$filtros = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Consulta principal con JOIN para obtener usuario y método de pago
$sql = "SELECT p.IdPedido, u.Username AS Usuario, p.FechaPedido, p.Total, m.Nombre AS MetodoPago, p.Entregado 
        FROM pedido p
        JOIN usuario u ON p.IdUsuario = u.IdUsuario
        JOIN metodopago m ON p.IdMetodoPago = m.IdMetodoPago
        $filtros
        ORDER BY p.FechaPedido DESC";

$stmt = $conn->prepare($sql);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Consulta para la gráfica de totales diarios
$sqlGrafica = "SELECT DATE(p.FechaPedido) AS Fecha, SUM(p.Total) AS Total
               FROM pedido p
               JOIN usuario u ON p.IdUsuario = u.IdUsuario
               $filtros
               GROUP BY DATE(p.FechaPedido)
               ORDER BY Fecha ASC";
$stmtGrafica = $conn->prepare($sqlGrafica);
if ($types) {
    $stmtGrafica->bind_param($types, ...$params);
}
$stmtGrafica->execute();
$resGrafica = $stmtGrafica->get_result();

$fechas = [];
$totales = [];
while ($row = $resGrafica->fetch_assoc()) {
    $fechas[] = $row['Fecha'];
    $totales[] = $row['Total'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial por Cliente</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: Arial; background: #f4f4f4; padding: 30px; }
        h2 { color: #333; }
        table { width: 100%; border-collapse: collapse; background: white; margin-top: 20px; box-shadow: 0 0 6px rgba(0,0,0,0.1); }
        th, td { padding: 10px; border: 1px solid #ccc; text-align: center; }
        th { background: #007bff; color: white; }
        .detalle { margin-top: 10px; background: #fff; border: 1px solid #ddd; padding: 10px; border-radius: 5px; }
        form { margin-top: 20px; }
        input[type="date"], input[type="text"] {
            padding: 6px; border: 1px solid #ccc; border-radius: 5px; margin-right: 10px;
        }
        button { padding: 6px 12px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .entregado { color: green; font-weight: bold; }
        .pendiente { color: orange; font-weight: bold; }
        .volver { margin-top: 20px; display: inline-block; background: #6c757d; color: white; padding: 10px; text-decoration: none; border-radius: 5px; }
        .grafico { margin-top: 40px; background: white; padding: 20px; border-radius: 6px; box-shadow: 0 0 8px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

<h2>📖 Historial de Compras por Cliente</h2>
<form method="GET">
    <input type="text" name="cliente" placeholder="Nombre de usuario" value="<?= htmlspecialchars($cliente) ?>">
    <label>Desde: <input type="date" name="desde" value="<?= htmlspecialchars($desde) ?>"></label>
    <label>Hasta: <input type="date" name="hasta" value="<?= htmlspecialchars($hasta) ?>"></label>
    <button type="submit">Buscar</button>
    <a href="historial_por_cliente.php" style="margin-left: 10px;">Limpiar</a>
</form>

<?php if ($result->num_rows > 0): ?>
<div class="grafico">
    <h3>📊 Resumen de Compras</h3>
    <canvas id="grafico"></canvas>
</div>

<table>
    <tr>
        <th>ID</th>
        <th>Usuario</th>
        <th>Fecha</th>
        <th>Total</th>
        <th>Método</th>
        <th>Estado</th>
        <th>Detalles</th>
    </tr>
    <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['IdPedido'] ?></td>
            <td><?= htmlspecialchars($row['Usuario']) ?></td>
            <td><?= $row['FechaPedido'] ?></td>
            <td>$<?= number_format($row['Total'], 2) ?></td>
            <td><?= htmlspecialchars($row['MetodoPago']) ?></td>
            <td><?= $row['Entregado'] ? '<span class="entregado">Entregado</span>' : '<span class="pendiente">Pendiente</span>' ?></td>
            <td><a href="?<?= http_build_query(array_merge($_GET, ['ver' => $row['IdPedido']])) ?>">Ver detalle</a></td>
        </tr>
        <?php if (isset($_GET['ver']) && $_GET['ver'] == $row['IdPedido']):
            $detalle = $conn->prepare("SELECT dp.Cantidad, dp.PrecioUnitario, p.Nombre 
                                       FROM detalle_pedido dp 
                                       JOIN producto p ON dp.IdProducto = p.IdProducto 
                                       WHERE dp.IdPedido = ?");
            $detalle->bind_param("i", $row['IdPedido']);
            $detalle->execute();
            $resultadoDetalle = $detalle->get_result();
        ?>
        <tr>
            <td colspan="7">
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
    <p>No se encontraron pedidos para este cliente.</p>
<?php endif; ?>

<a href="admin_pedidos.php" class="volver">⬅ Volver al panel</a>

<script>
const ctx = document.getElementById('grafico').getContext('2d');
const chart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($fechas) ?>,
        datasets: [{
            label: 'Total de Compras ($)',
            data: <?= json_encode($totales) ?>,
            backgroundColor: 'rgba(40, 167, 69, 0.7)'
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>

</body>
</html>
