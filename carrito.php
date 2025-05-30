<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'cliente') {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

$productos = [];
if (!empty($_SESSION['carrito'])) {
    $ids = implode(',', array_keys($_SESSION['carrito']));
    $sql = "SELECT * FROM productos WHERE Id IN ($ids)";
    $result = $conn->query($sql);

    while ($row = $result->fetch_assoc()) {
        $productos[$row['Id']] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Carrito de Compras</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f6f6f6;
            padding: 30px;
        }
        h2 {
            color: #444;
        }
        table {
            width: 100%;
            background: #fff;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: center;
        }
        th {
            background-color: #ddd;
        }
        input[type="number"] {
            width: 60px;
            padding: 5px;
        }
        .total {
            text-align: right;
            margin-top: 20px;
            font-size: 1.2em;
        }
        .btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 18px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            cursor: pointer;
        }
        .btn:hover {
            background-color: #45a049;
        }
        .volver {
            margin-top: 20px;
            display: inline-block;
        }
    </style>
</head>
<body>

<h2>🛒 Carrito de Compras</h2>

<?php if (empty($_SESSION['carrito'])): ?>
    <p>Tu carrito está vacío. <a href="ver_tienda.php" class="btn">Ver productos</a></p>
<?php else: ?>
    <table>
        <tr>
            <th>Producto</th>
            <th>Precio Unitario</th>
            <th>Cantidad</th>
            <th>Subtotal</th>
            <th>Quitar</th>
        </tr>
        <?php $total = 0; ?>
        <?php foreach ($_SESSION['carrito'] as $id => $cantidad):
            $producto = $productos[$id];
            $subtotal = $producto['Precio'] * $cantidad;
            $total += $subtotal;
        ?>
        <tr>
            <td><?= $producto['Nombre'] ?></td>
            <td>$<?= number_format($producto['Precio'], 2) ?></td>
            <td>
                <input type="number" min="1" value="<?= $cantidad ?>" data-id="<?= $id ?>" class="cambio-cantidad">
            </td>
            <td>$<?= number_format($subtotal, 2) ?></td>
            <td><a href="actualizar_carrito.php?accion=quitar&id=<?= $id ?>">❌</a></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <div class="total">
        <strong>Total: $<?= number_format($total, 2) ?></strong>
    </div>
    <br>
    <a href="ver_tienda.php" class="btn volver">← Seguir comprando</a>
<?php endif; ?>

<script>
document.querySelectorAll('.cambio-cantidad').forEach(input => {
    input.addEventListener('change', () => {
        const id = input.dataset.id;
        const cantidad = input.value;

        fetch('actualizar_carrito.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `id=${id}&cantidad=${cantidad}`
        }).then(res => res.text())
          .then(() => location.reload());
    });
});
</script>

</body>
</html>
