<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$cliente = $_GET['cliente'] ?? '';
$desde = $_GET['desde'] ?? '';
$hasta = $_GET['hasta'] ?? '';

$filtros = "WHERE 1=1";
if ($cliente) $filtros .= " AND usuario LIKE '%$cliente%'";
if ($desde) $filtros .= " AND fecha_pedido >= '$desde 00:00:00'";
if ($hasta) $filtros .= " AND fecha_pedido <= '$hasta 23:59:59'";

$sql = "SELECT * FROM pedidos $filtros ORDER BY fecha_pedido DESC";
$result = $conn->query($sql);

// Datos para la gráfica
$grafica = $conn->query("SELECT DATE(fecha_pedido) as fecha, SUM(total) as total FROM pedidos $filtros GROUP BY DATE(fecha_pedido)");
$fechas = [];
$totales = [];
while ($row = $grafica->fetch_assoc()) {
    $fechas[] = $row['fecha'];
    $totales[] = $row['total'];
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
        button { padding: 6px 12px; background: #007bff; color: white; border: none; border-radius: 5px; }
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
    <label>Desde: <input type="date" name="desde" value="<?= $desde ?>"></label>
    <label>Hasta: <input type="date" name="hasta" value="<?= $hasta ?>"></label>
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
            <td><?= $row['id'] ?></td>
            <td><?= $row['usuario'] ?></td>
            <td><?= $row['fecha_pedido'] ?></td>
            <td>$<?= number_format($row['total'], 2) ?></td>
            <td><?= $row['metodo_pago'] ?></td>
            <td><?= $row['entregado'] ? '<span class="entregado">Entregado</span>' : '<span class="pendiente">Pendiente</span>' ?></td>
            <td><a href="?<?= http_build_query(array_merge($_GET, ['ver' => $row['id']])) ?>">Ver detalle</a></td>
        </tr>
        <?php if (isset($_GET['ver']) && $_GET['ver'] == $row['id']):
            $detalle = $conn->query("SELECT dp.*, p.Nombre FROM detalle_pedidos dp INNER JOIN productos p ON dp.producto_id = p.Id WHERE dp.pedido_id = " . $row['id']);
        ?>
        <tr>
            <td colspan="7">
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
                                <td><?= $det['Nombre'] ?></td>
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
