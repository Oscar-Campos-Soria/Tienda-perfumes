<?php 
include 'db.php';

// Consulta para gráfico de métodos de pago
$sql = "SELECT metodo_pago, COUNT(*) as total FROM pedidos GROUP BY metodo_pago";
$result = $conn->query($sql);

// Preparar datos para gráfico
$labels = [];
$data = [];
while ($row = $result->fetch_assoc()) {
    $labels[] = $row['metodo_pago'];
    $data[] = $row['total'];
}

// Consulta completa de pedidos
$compras = $conn->query("SELECT * FROM pedidos ORDER BY fecha_pedido DESC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Consulta de Compras</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            padding: 30px;
            background-color: #f7f9fc;
            margin: 0;
        }
        h2 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 30px;
        }
        .grafica {
            max-width: 500px;
            margin: 40px auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: center;
        }
        th {
            background-color: #007bff;
            color: white;
        }
        .boton-detalle {
            background: #6c63ff;
            color: white;
            padding: 6px 12px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
        }
        .volver {
            display: inline-block;
            margin-top: 30px;
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
        }
    </style>
</head>
<body>

<h2>📊 Historial General de Compras</h2>

<div class="grafica">
    <canvas id="graficaPagos"></canvas>
</div>

<table>
    <tr>
        <th>ID</th>
        <th>Usuario</th>
        <th>Total</th>
        <th>Referencia</th>
        <th>Método de Pago</th>
        <th>Fecha</th>
        <th>Detalle</th>
    </tr>
    <?php while ($row = $compras->fetch_assoc()): ?>
    <tr>
        <td><?= $row['id'] ?></td>
        <td><?= $row['usuario'] ?></td>
        <td>$<?= number_format($row['total'], 2) ?></td>
        <td><?= $row['referencia'] ?></td>
        <td><?= $row['metodo_pago'] ?? 'N/A' ?></td>
        <td><?= $row['fecha_pedido'] ?></td>
        <td><a href="detalle_pedido.php?id=<?= $row['id'] ?>" class="boton-detalle">Ver</a></td>
    </tr>
    <?php endwhile; ?>
</table>

<a href="dashboard.php" class="volver">← Volver al panel</a>

<script>
    const ctx = document.getElementById('graficaPagos').getContext('2d');
    const chart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: <?= json_encode($labels) ?>,
            datasets: [{
                label: 'Método de Pago',
                data: <?= json_encode($data) ?>,
                backgroundColor: ['#28a745', '#007bff', '#ffc107', '#dc3545'],
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Distribución de Compras por Método de Pago'
                }
            }
        }
    });
</script>

</body>
</html>
