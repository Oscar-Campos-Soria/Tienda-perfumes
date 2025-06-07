<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'administrador') {
    die("⛔ Acceso denegado.");
}

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=reporte_pedidos.xls");

// Encabezados de tabla
echo "<table border='1'>";
echo "<tr>
        <th>ID Pedido</th>
        <th>Usuario</th>
        <th>Fecha</th>
        <th>Total Pedido</th>
        <th>Producto</th>
        <th>Cantidad</th>
        <th>Precio Unitario</th>
        <th>Subtotal</th>
        <th>Método de Pago</th>
        <th>Estado</th>
      </tr>";

// Consulta con nombres correctos (singular y camelCase)
$sql = "SELECT p.IdPedido, p.Usuario, p.FechaPedido, p.Total, p.MetodoPago, p.Entregado,
               d.IdProducto, d.Cantidad, d.PrecioUnitario, pr.Nombre
        FROM pedido p
        JOIN detalle_pedido d ON p.IdPedido = d.IdPedido
        JOIN producto pr ON d.IdProducto = pr.IdProducto
        ORDER BY p.FechaPedido DESC";

$resultado = $conn->query($sql);

while ($row = $resultado->fetch_assoc()) {
    $subtotal = $row['PrecioUnitario'] * $row['Cantidad'];
    echo "<tr>
            <td>{$row['IdPedido']}</td>
            <td>" . htmlspecialchars($row['Usuario']) . "</td>
            <td>{$row['FechaPedido']}</td>
            <td>$" . number_format($row['Total'], 2) . "</td>
            <td>" . htmlspecialchars($row['Nombre']) . "</td>
            <td>{$row['Cantidad']}</td>
            <td>$" . number_format($row['PrecioUnitario'], 2) . "</td>
            <td>$" . number_format($subtotal, 2) . "</td>
            <td>" . htmlspecialchars($row['MetodoPago']) . "</td>
            <td>" . ($row['Entregado'] ? 'Entregado' : 'Pendiente') . "</td>
          </tr>";
}
echo "</table>";
?>
