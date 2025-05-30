<?php
include 'db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$pedido = $conn->query("SELECT * FROM pedidos WHERE id = $id")->fetch_assoc();
$detalles = $conn->query("SELECT dp.*, p.Nombre 
                          FROM detalle_pedidos dp 
                          JOIN productos p ON dp.producto_id = p.Id 
                          WHERE dp.pedido_id = $id");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle del Pedido</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f8; padding: 30px; }
        h2 { color: #333; }
        .info { background: #fff; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .info p { margin: 5px 0; }
        table { width: 100%; background: white; border-collapse: collapse; }
        th, td { padding: 10px; border: 1px solid #ccc; text-align: center; }
        th { background: #eee; }
        .volver {
            margin-top: 25px;
            display: inline-block;
            background: #007bff;
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            text-decoration: none;
        }
    </style>
</head>
<body>

<h2>🧾 Detalles del Pedido #<?= $pedido['id'] ?></h2>

<div class="info">
    <p><strong>Usuario:</strong> <?= $pedido['usuario'] ?></p>
    <p><strong>Total:</strong> $<?= number_format($pedido['total'], 2) ?></p>
    <p><strong>Referencia:</strong> <?= $pedido['referencia'] ?></p>
    <p><strong>Método de pago:</strong> <?= $pedido['metodo_pago'] ?></p>
    <p><strong>Fecha:</strong> <?= $pedido['fecha_pedido'] ?></p>
</div>

<h3>📦 Productos del pedido</h3>
<table>
    <tr>
        <th>Producto</th>
        <th>Cantidad</th>
        <th>Precio Unitario</th>
        <th>Subtotal</th>
    </tr>
    <?php
    while ($fila = $detalles->fetch_assoc()):
        $subtotal = $fila['cantidad'] * $fila['precio_unitario'];
    ?>
        <tr>
            <td><?= $fila['Nombre'] ?></td>
            <td><?= $fila['cantidad'] ?></td>
            <td>$<?= number_format($fila['precio_unitario'], 2) ?></td>
            <td>$<?= number_format($subtotal, 2) ?></td>
        </tr>
    <?php endwhile; ?>
</table>

<a href="consultar_compras.php" class="volver">← Volver</a>

</body>
</html>
