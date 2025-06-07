<?php 
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'administrador') {
    header("Location: login.php");
    exit;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

// Marcar pedido como entregado y enviar correo
if (isset($_GET['entregar'])) {
    $id = intval($_GET['entregar']);

    // Actualizar estatus a entregado (IdEstatus = 4)
    $stmt = $conn->prepare("UPDATE pedido SET IdEstatus = 4 WHERE IdPedido = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    // Obtener datos del pedido y usuario
    $stmt = $conn->prepare("
        SELECT p.Referencia, u.Username, u.Email
        FROM pedido p
        JOIN usuario u ON p.IdUsuario = u.IdUsuario
        WHERE p.IdPedido = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $pedido = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($pedido) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.tucorreo.com'; // Cambia esto por tu servidor SMTP real
            $mail->SMTPAuth = true;
            $mail->Username = 'tucorreo@dominio.com'; // Cambia por tu usuario SMTP
            $mail->Password = 'tu_contraseña'; // Cambia por tu contraseña SMTP
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('tucorreo@dominio.com', 'Tienda de Perfumes');
            $mail->addAddress($pedido['Email'], $pedido['Username']);
            $mail->Subject = 'Pedido entregado';
            $mail->Body = "Hola {$pedido['Username']}, tu pedido con referencia {$pedido['Referencia']} ha sido entregado.\n\nGracias por tu compra.";

            $mail->send();
        } catch (Exception $e) {
            // Puedes registrar el error en un log si quieres
        }
    }
}

// Filtros y orden
$cliente = $_GET['cliente'] ?? '';
$desde = $_GET['desde'] ?? '';
$hasta = $_GET['hasta'] ?? '';
$order = (isset($_GET['orden']) && $_GET['orden'] === 'asc') ? 'ASC' : 'DESC';

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

$condicion = count($where) ? "WHERE " . implode(' AND ', $where) : "";

// Preparar y ejecutar consulta con JOIN
$sql = "SELECT p.*, u.Username, u.Email, m.Nombre AS MetodoPago, e.Nombre AS Estatus
        FROM pedido p
        JOIN usuario u ON p.IdUsuario = u.IdUsuario
        JOIN metodopago m ON p.IdMetodoPago = m.IdMetodoPago
        JOIN estatus e ON p.IdEstatus = e.IdEstatus
        $condicion
        ORDER BY p.Total $order";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Datos para gráfica
$sql_graf = "SELECT DATE(FechaPedido) as fecha, SUM(Total) as total 
             FROM pedido p
             $condicion
             GROUP BY DATE(FechaPedido)";
$stmt_graf = $conn->prepare($sql_graf);
if ($params) {
    $stmt_graf->bind_param($types, ...$params);
}
$stmt_graf->execute();
$grafica = $stmt_graf->get_result();

$fechas = [];
$totales = [];
while ($g = $grafica->fetch_assoc()) {
    $fechas[] = $g['fecha'];
    $totales[] = $g['total'];
}

$stmt->close();
$stmt_graf->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Administrador - Pedidos</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background-color: #f2f2f2; }
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
    <input type="date" name="desde" value="<?= htmlspecialchars($desde) ?>">
    <input type="date" name="hasta" value="<?= htmlspecialchars($hasta) ?>">
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
    <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['IdPedido'] ?></td>
            <td><?= htmlspecialchars($row['Username']) ?></td>
            <td>$<?= number_format($row['Total'], 2) ?></td>
            <td><?= htmlspecialchars($row['Referencia']) ?></td>
            <td><?= htmlspecialchars($row['FechaPedido']) ?></td>
            <td><?= htmlspecialchars($row['MetodoPago']) ?></td>
            <td>
                <?php
                if ($row['IdEstatus'] == 4) {
                    echo '<span class="entregado">Entregado</span>';
                } elseif ($row['IdEstatus'] == 5) {
                    echo '<span class="pendiente">No Enviado</span>';
                } elseif ($row['IdEstatus'] == 6) {
                    echo '<span class="pendiente">Cancelado</span>';
                } else {
                    echo htmlspecialchars($row['Estatus']);
                }
                ?>
            </td>
            <td>
                <?php if ($row['IdEstatus'] != 4): ?>
                    <a class="btn btn-entregar" href="admin_pedidos.php?entregar=<?= $row['IdPedido'] ?>">Marcar como entregado</a><br><br>
                <?php endif; ?>
                <a class="btn btn-detalle" href="?<?= http_build_query(array_merge($_GET, ['ver' => $row['IdPedido']])) ?>">Ver detalle</a>
            </td>
        </tr>

        <?php if (isset($_GET['ver']) && $_GET['ver'] == $row['IdPedido']):
            $detalle = $conn->query("SELECT pd.*, pr.Nombre FROM pedidodetalle pd 
                INNER JOIN producto pr ON pd.IdProducto = pr.IdProducto 
                WHERE pd.IdPedido = " . intval($row['IdPedido'])); ?>
        <tr>
            <td colspan="8">
                <div class="detalle">
                    <strong>Detalle del Pedido #<?= $row['IdPedido'] ?>:</strong>
                    <table>
                        <tr>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Precio Unitario</th>
                            <th>Subtotal</th>
                        </tr>
                        <?php while ($det = $detalle->fetch_assoc()): ?>
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
