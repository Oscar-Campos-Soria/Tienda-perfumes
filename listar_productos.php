<?php
session_start();
include 'db.php';

// Validar rol administrador
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'administrador') {
    header('Location: login.php');
    exit;
}

$limite = 10;
$pagina = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$inicio = ($pagina - 1) * $limite;
$tabla_producto = 'producto';
$tabla_presentacion = 'presentacion';

// Total productos para paginación
$total_sql = "SELECT COUNT(*) as total FROM $tabla_producto";
$total_result = $conn->query($total_sql);

if (!$total_result) {
    die("Error al ejecutar la consulta para contar los productos: " . $conn->error);
}

$total_filas = $total_result->fetch_assoc()['total'];
$total_paginas = ceil($total_filas / $limite);

// Consulta productos con JOIN a presentacion
$sql = "SELECT p.IdProducto, p.Nombre, p.Descripcion, p.Precio, p.Cantidad, p.Categoria, 
               pr.Nombre AS Presentacion, p.Imagen
        FROM $tabla_producto p
        LEFT JOIN $tabla_presentacion pr ON p.IdPresentacion = pr.IdPresentacion
        ORDER BY p.IdProducto DESC
        LIMIT $inicio, $limite";

$result = $conn->query($sql);
if (!$result) {
    die("Error al consultar los productos: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>Lista de Perfumes</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <!-- FontAwesome CDN CORRECTO (sin CORS) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #121212;
            color: #eee;
            margin: 0;
            padding: 20px;
        }
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .top-bar h2 {
            margin: 0;
        }
        .btn {
            background-color: #007bff;
            color: #fff;
            padding: 8px 14px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            margin-left: 10px;
            transition: background-color 0.3s ease;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: #222;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 0 15px rgba(0,0,0,0.5);
        }
        th, td {
            padding: 12px 15px;
            text-align: center;
            border-bottom: 1px solid #333;
        }
        th {
            background-color: #1f1f1f;
            font-weight: 700;
        }
        tr:hover {
            background-color: #333;
        }
        td img {
            max-width: 80px;
            border-radius: 6px;
        }
        .sin-imagen {
            color: #999;
            font-style: italic;
        }
        .acciones a {
            margin: 0 5px;
            color: #eee;
            font-size: 1.2em;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        .acciones a.editar:hover {
            color: #28a745;
        }
        .acciones a.eliminar:hover {
            color: #dc3545;
        }
        .paginacion {
            margin-top: 20px;
            text-align: center;
        }
        .paginacion a {
            color: #eee;
            padding: 8px 14px;
            margin: 0 3px;
            text-decoration: none;
            background-color: #1f1f1f;
            border-radius: 6px;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }
        .paginacion a:hover, .paginacion a.activa {
            background-color: #007bff;
        }
        @media (max-width: 800px) {
            th, td { padding: 6px 2px; font-size: 0.97em;}
            .top-bar { flex-direction: column; align-items: flex-start; }
            .btn { margin: 6px 2px; }
        }
    </style>
</head>
<body>

<div class="top-bar">
    <h2>🧴 Lista de Perfumes</h2>
    <div>
        <a href="form_agregar.php" class="btn"><i class="fa-solid fa-plus"></i> Agregar perfume</a>
        <a href="ver_auditoria.php" class="btn"><i class="fa-solid fa-search"></i> Auditoría de producto</a>
        <a href="ventas.php" class="btn"><i class="fa-solid fa-chart-line"></i> Ver ventas</a>
        <a href="logout.php" class="btn"><i class="fa-solid fa-sign-out-alt"></i> Cerrar sesión</a>
        <a href="index.php" class="btn"><i class="fa-solid fa-home"></i> Inicio</a>
    </div>
</div>

<?php if ($result->num_rows > 0): ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Imagen</th>
                <th>Nombre</th>
                <th>Descripción</th>
                <th>Precio</th>
                <th>Cantidad</th>
                <th>Categoría</th>
                <th>Presentación</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['IdProducto']) ?></td>
                    <td>
                        <?php if (!empty($row['Imagen'])): ?>
                            <img src="imagenes/<?= htmlspecialchars($row['Imagen']) ?>" alt="Imagen de <?= htmlspecialchars($row['Nombre']) ?>" />
                        <?php else: ?>
                            <span class="sin-imagen">Sin imagen</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($row['Nombre']) ?></td>
                    <td><?= htmlspecialchars($row['Descripcion']) ?></td>
                    <td>$<?= number_format($row['Precio'], 2) ?></td>
                    <td><?= htmlspecialchars($row['Cantidad']) ?></td>
                    <td><?= htmlspecialchars($row['Categoria']) ?></td>
                    <td><?= !empty($row['Presentacion']) ? htmlspecialchars($row['Presentacion']) : 'N/A' ?></td>
                    <td class="acciones">
                        <a href="editar_producto.php?id=<?= $row['IdProducto'] ?>" class="editar" title="Editar"><i class="fa-solid fa-edit"></i></a>
                        <a href="eliminar_producto.php?id=<?= $row['IdProducto'] ?>" class="eliminar" title="Eliminar" onclick="return confirm('¿Estás seguro de eliminar este producto?');"><i class="fa-solid fa-trash"></i></a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <div class="paginacion">
        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
            <a href="listar_productos.php?pagina=<?= $i ?>" class="<?= $i === $pagina ? 'activa' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
<?php else: ?>
    <p style="text-align: center; font-size: 1.2rem; margin-top: 40px;">No hay perfumes registrados aún.</p>
<?php endif; ?>

</body>
</html>
