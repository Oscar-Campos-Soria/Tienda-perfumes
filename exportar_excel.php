<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("⛔ Acceso denegado.");
}

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=reporte_pedidos.xls");
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

$sql = "SELECT p.id, p.usuario, p.fecha_pedido, p.total, p.metodo_pago, p.entregado,
               d.producto_id, d.cantidad, d.precio_unitario, pr.Nombre
        FROM pedidos p
        JOIN detalle_pedidos d ON p.id = d.pedido_id
        JOIN productos pr ON d.producto_id = pr.Id
        ORDER BY p.fecha_pedido DESC";

$resultado = $conn->query($sql);

while ($row = $resultado->fetch_assoc()) {
    echo "<tr>
            <td>{$row['id']}</td>
            <td>{$row['usuario']}</td>
            <td>{$row['fecha_pedido']}</td>
            <td>\${$row['total']}</td>
            <td>{$row['Nombre']}</td>
            <td>{$row['cantidad']}</td>
            <td>\${$row['precio_unitario']}</td>
            <td>\$" . number_format($row['precio_unitario'] * $row['cantidad'], 2) . "</td>
            <td>{$row['metodo_pago']}</td>
            <td>" . ($row['entregado'] ? 'Entregado' : 'Pendiente') . "</td>
          </tr>";
}
echo "</table>";
?>
