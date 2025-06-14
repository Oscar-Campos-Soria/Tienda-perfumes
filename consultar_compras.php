<?php 
include 'db.php';

// Consulta para gráfico de métodos de pago (JOIN a metodopago)
$sql = "SELECT m.Nombre AS MetodoPago, COUNT(*) as total 
        FROM pedido p
        JOIN metodopago m ON p.IdMetodoPago = m.IdMetodoPago
        GROUP BY m.Nombre
        ORDER BY total DESC";
$result = $conn->query($sql);

// Preparar datos para gráfico
$labels = [];
$data = [];
while ($row = $result->fetch_assoc()) {
    $labels[] = $row['MetodoPago'];
    $data[] = (int)$row['total'];
}

// Consulta completa de pedidos (JOIN usuario y metodopago)
$compras = $conn->query(
    "SELECT p.IdPedido, u.Username, p.Total, p.Referencia, m.Nombre AS MetodoPago, p.FechaPedido
     FROM pedido p
     JOIN usuario u ON p.IdUsuario = u.IdUsuario
     JOIN metodopago m ON p.IdMetodoPago = m.IdMetodoPago
     ORDER BY p.FechaPedido DESC"
);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
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
            max-width: 600px;
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
            user-select: none;
        }
        .boton-detalle {
            background: #6c63ff;
            color: white;
            padding: 6px 12px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }
        .boton-detalle:hover {
            background: #574fcf;
        }
        .volver {
            display: inline-block;
            margin-top: 30px;
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }
        .volver:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>

<h2>📊 Historial General de Compras</h2>

<div class="grafica">
    <canvas id="graficaPagos"></canvas>
</div>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Usuario</th>
            <th>Total</th>
            <th>Referencia</th>
            <th>Método de Pago</th>
            <th>Fecha</th>
            <th>Detalle</th>
        </tr>
    </thead>
    <tbody>
    <?php while ($row = $compras->fetch_assoc()): ?>
    <tr>
        <td><?= htmlspecialchars($row['IdPedido']) ?></td>
        <td><?= htmlspecialchars($row['Username']) ?></td>
        <td>$<?= number_format($row['Total'], 2) ?></td>
        <td><?= htmlspecialchars($row['Referencia']) ?></td>
        <td><?= htmlspecialchars($row['MetodoPago']) ?></td>
        <td><?= htmlspecialchars($row['FechaPedido']) ?></td>
        <td><a href="detalle_pedido.php?id=<?= (int)$row['IdPedido'] ?>" class="boton-detalle" target="_blank" rel="noopener noreferrer">Ver</a></td>
    </tr>
    <?php endwhile; ?>
    </tbody>
</table>

<a href="dashboard.php" class="volver">← Volver al panel</a>

<script>
    const ctx = document.getElementById('graficaPagos').getContext('2d');
    const chart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: <?= json_encode($labels, JSON_UNESCAPED_UNICODE) ?>,
            datasets: [{
                label: 'Método de Pago',
                data: <?= json_encode($data) ?>,
                backgroundColor: ['#28a745', '#007bff', '#ffc107', '#dc3545'],
                hoverOffset: 10,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Distribución de Compras por Método de Pago'
                },
                legend: {
                    position: 'right'
                }
            }
        }
    });
</script>

</body>
</html>
