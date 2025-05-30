<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

// Marcar pedido como entregado y enviar correo
if (isset($_GET['entregar'])) {
    $id = $_GET['entregar'];
    $conn->query("UPDATE pedidos SET entregado = 1 WHERE id = $id");

    $pedido = $conn->query("SELECT * FROM pedidos WHERE id = $id")->fetch_assoc();
    $usuario = $pedido['usuario'];
    $correo = $conn->query("SELECT email FROM usuarios WHERE username = '$usuario'")->fetch_assoc()['email'];

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.tucorreo.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'tucorreo@dominio.com';
        $mail->Password = 'tu_contraseña';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('tucorreo@dominio.com', 'Tienda de Perfumes');
        $mail->addAddress($correo);
        $mail->Subject = 'Pedido entregado';
        $mail->Body = "Hola $usuario, tu pedido con referencia {$pedido['referencia']} ha sido entregado.\n\nGracias por tu compra.";

        $mail->send();
    } catch (Exception $e) {
        // Log error o ignorar
    }
}

$cliente = $_GET['cliente'] ?? '';
$desde = $_GET['desde'] ?? '';
$hasta = $_GET['hasta'] ?? '';
$order = $_GET['orden'] === 'asc' ? 'ASC' : 'DESC';

$where = [];
if ($cliente) $where[] = "usuario LIKE '%$cliente%'";
if ($desde) $where[] = "fecha_pedido >= '$desde 00:00:00'";
if ($hasta) $where[] = "fecha_pedido <= '$hasta 23:59:59'";

$condicion = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';
$sql = "SELECT * FROM pedidos $condicion ORDER BY total $order";
$result = $conn->query($sql);

$grafica = $conn->query("SELECT DATE(fecha_pedido) as fecha, SUM(total) as total FROM pedidos $condicion GROUP BY DATE(fecha_pedido)");
$fechas = [];
$totales = [];
while ($g = $grafica->fetch_assoc()) {
    $fechas[] = $g['fecha'];
    $totales[] = $g['total'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Administrador</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: Arial; padding: 20px; background-color: #f2f2f2; }
        h2 { color: #333; }
        form, .filtros { margin-top: 15px; }
        input[type="text"], input[type="date"] {
            padding: 6px; margin-right: 10px; border: 1px solid #ccc; border-radius: 4px;
        }
        button { padding: 6px 12px; border: none; background: #007bff; color: white; border-radius: 4px; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        th, td { padding: 12px; text-align: center; border-bottom: 1px solid #ddd; }
        th { background-color: #007bff; color: white; }
        .btn { padding: 6px 12px; border: none; border-radius: 4px; color: white; cursor: pointer; text-decoration: none; }
        .btn-entregar { background-color: #28a745; }
        .btn-detalle { background-color: #6c757d; }
        .entregado { background-color: #28a745; padding: 4px 10px; border-radius: 5px; color: white; }
        .pendiente { background-color: #ffc107; padding: 4px 10px; border-radius: 5px; color: #212529; }
        .logout { margin-top: 20px; display: inline-block; background-color: #dc3545; color: white; padding: 10px; border-radius: 4px; text-decoration: none; }
        .detalle { margin-top: 15px; background: #ffffff; border: 1px solid #ccc; padding: 10px; border-radius: 6px; }
        .detalle table { width: 100%; margin-top: 10px; border-collapse: collapse; }
        .detalle th, .detalle td { padding: 8px; border: 1px solid #ddd; }
        .detalle th { background-color: #f1f1f1; }
        .grafico { margin-top: 40px; background: white; padding: 20px; border-radius: 6px; box-shadow: 0 0 8px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

<h2>📋 Panel de Pedidos</h2>
<a href="logout.php" class="logout">Cerrar sesión</a>
<a href="exportar_excel.php" style="margin-left: 10px; padding: 8px 14px; background: #28a745; color: white; border-radius: 5px; text-decoration: none;">📤 Exportar a Excel</a>

<form method="GET">
    <input type="text" name="cliente" placeholder="Buscar cliente" value="<?= htmlspecialchars($cliente) ?>">
    <input type="date" name="desde" value="<?= $desde ?>">
    <input type="date" name="hasta" value="<?= $hasta ?>">
    <button type="submit">Filtrar</button>
    <a href="admin_pedidos.php" style="margin-left: 10px;">Limpiar</a>
</form>

<div class="filtros">
    <strong>Ordenar por total:</strong>
    <a href="?<?= http_build_query(array_merge($_GET, ['orden' => 'desc'])) ?>">Más caros</a>
    <a href="?<?= http_build_query(array_merge($_GET, ['orden' => 'asc'])) ?>">Más baratos</a>
</div>

<div class="grafico">
    <h3>📊 Resumen de Ventas</h3>
    <canvas id="grafico"></canvas>
</div>

<table>
    <tr>
        <th>ID</th>
        <th>Usuario</th>
        <th>Total</th>
        <th>Referencia</th>
        <th>Fecha</th>
        <th>Método</th>
        <th>Estado</th>
        <th>Acciones</th>
    </tr>
    <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= $row['usuario'] ?></td>
            <td>$<?= number_format($row['total'], 2) ?></td>
            <td><?= $row['referencia'] ?></td>
            <td><?= $row['fecha_pedido'] ?></td>
            <td><?= $row['metodo_pago'] ?></td>
            <td>
                <?= $row['entregado'] ? '<span class="entregado">Entregado</span>' : '<span class="pendiente">Pendiente</span>' ?>
            </td>
            <td>
                <?php if (!$row['entregado']): ?>
                    <a class="btn btn-entregar" href="admin_pedidos.php?entregar=<?= $row['id'] ?>">Marcar como entregado</a><br><br>
                <?php endif; ?>
                <a class="btn btn-detalle" href="?<?= http_build_query(array_merge($_GET, ['ver' => $row['id']])) ?>">Ver detalle</a>
            </td>
        </tr>
        <?php if (isset($_GET['ver']) && $_GET['ver'] == $row['id']):
            $detalle = $conn->query("SELECT dp.*, p.Nombre FROM detalle_pedidos dp INNER JOIN productos p ON dp.producto_id = p.Id WHERE dp.pedido_id = " . $row['id']);
        ?>
        <tr>
            <td colspan="8">
                <div class="detalle">
                    <strong>Detalle del Pedido #<?= $row['id'] ?>:</strong>
                    <table>
                        <tr>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Precio Unitario</th>
                            <th>Subtotal</th>
                        </tr>
                        <?php while ($det = $detalle->fetch_assoc()): ?>
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

<script>
    const ctx = document.getElementById('grafico').getContext('2d');
    const chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($fechas) ?>,
            datasets: [{
                label: 'Total de Ventas ($)',
                data: <?= json_encode($totales) ?>,
                backgroundColor: 'rgba(0, 123, 255, 0.7)'
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
</script>

</body>
</html>

