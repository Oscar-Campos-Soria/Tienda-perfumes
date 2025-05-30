<?php 
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Límite de productos por página
$limite = 10;
$pagina = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$inicio = ($pagina - 1) * $limite;

// Total de productos para paginación
$total_result = $conn->query("SELECT COUNT(*) as total FROM productos");
$total_filas = $total_result->fetch_assoc()['total'];
$total_paginas = ceil($total_filas / $limite);

// Consulta paginada
$sql = "SELECT * FROM productos ORDER BY Id DESC LIMIT $inicio, $limite";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de Perfumes</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap');

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea, #764ba2);
            margin: 0;
            padding: 40px 20px;
            color: #333;
            min-height: 100vh;
        }
        h2 {
            color: white;
            font-weight: 600;
            font-size: 2rem;
            margin-bottom: 30px;
            text-align: center;
            text-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }
        .top-bar {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 15px;
            margin-bottom: 40px;
        }
        .btn {
            padding: 12px 22px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            color: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            transition: background-color 0.3s ease, transform 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 1rem;
            cursor: pointer;
        }
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
        }
        .btn-agregar {
            background-color: #28a745;
        }
        .btn-agregar:hover {
            background-color: #218838;
        }
        .btn-cerrar {
            background-color: #dc3545;
        }
        .btn-cerrar:hover {
            background-color: #b02a37;
        }
        .btn-auditoria {
            background-color: #6f42c1;
        }
        .btn-auditoria:hover {
            background-color: #59359c;
        }
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 12px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 12px 25px rgba(0,0,0,0.15);
            overflow: hidden;
        }
        thead th {
            background-color: #4f46e5;
            color: white;
            font-weight: 700;
            padding: 16px 20px;
            text-align: center;
            font-size: 1rem;
            letter-spacing: 0.06em;
        }
        tbody tr {
            background: #f9f9fb;
            border-radius: 10px;
            box-shadow: inset 0 0 8px rgba(0,0,0,0.03);
            transition: background-color 0.3s ease;
        }
        tbody tr:hover {
            background-color: #e0e4ff;
            cursor: default;
        }
        tbody td {
            padding: 16px 20px;
            text-align: center;
            font-weight: 500;
            color: #4b4b4b;
        }
        tbody td:first-child {
            font-weight: 700;
            color: #4f46e5;
        }
        img {
            max-width: 80px;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 3px 8px rgba(0,0,0,0.1);
        }
        .sin-imagen {
            font-style: italic;
            color: #999;
        }
        .editar, .eliminar {
            padding: 8px 14px;
            border-radius: 30px;
            font-size: 0.9rem;
            font-weight: 600;
            color: white;
            text-decoration: none;
            margin: 0 4px;
            display: inline-block;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transition: background-color 0.3s ease, transform 0.2s ease;
            cursor: pointer;
        }
        .editar {
            background-color: #0dcaf0;
        }
        .editar:hover {
            background-color: #0aa6c0;
            transform: translateY(-2px);
        }
        .eliminar {
            background-color: #dc3545;
        }
        .eliminar:hover {
            background-color: #b02a37;
            transform: translateY(-2px);
        }
        .paginacion {
            margin-top: 30px;
            text-align: center;
        }
        .paginacion a {
            margin: 0 6px;
            padding: 10px 16px;
            border-radius: 50%;
            font-weight: 600;
            color: #4f46e5;
            background: #e0e0ff;
            text-decoration: none;
            box-shadow: 0 4px 10px rgba(79,70,229,0.3);
            transition: background-color 0.3s ease, color 0.3s ease;
            display: inline-block;
            min-width: 44px;
            min-height: 44px;
            line-height: 24px;
        }
        .paginacion a:hover {
            background: #4f46e5;
            color: white;
            box-shadow: 0 8px 16px rgba(79,70,229,0.7);
        }
        .paginacion a.activa {
            background: #4f46e5;
            color: white;
            box-shadow: 0 8px 16px rgba(79,70,229,0.7);
        }
        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding: 20px 10px;
            }
            h2 {
                font-size: 1.5rem;
            }
            .top-bar {
                flex-direction: column;
                gap: 15px;
            }
            .btn {
                width: 100%;
                text-align: center;
                padding: 12px 0;
            }
            table, thead, tbody, tr, th, td {
                display: block;
            }
            thead tr {
                display: none;
            }
            tbody tr {
                margin-bottom: 20px;
                background: white;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                border-radius: 12px;
                padding: 15px;
            }
            tbody td {
                text-align: right;
                padding-left: 50%;
                position: relative;
                font-weight: 600;
            }
            tbody td::before {
                content: attr(data-label);
                position: absolute;
                left: 20px;
                width: 45%;
                padding-left: 10px;
                font-weight: 700;
                text-align: left;
                color: #4f46e5;
            }
            tbody td:first-child {
                text-align: center;
                font-weight: 700;
                color: #4f46e5;
                padding-left: 0;
                position: relative;
            }
            tbody td:first-child::before {
                content: "";
            }
        }
    </style>
</head>
<body>

<div class="top-bar">
    <h2>🧴 Lista de Perfumes</h2>
    <div>
        <a href="form_agregar.php" class="btn btn-agregar"><i class="fas fa-plus"></i> Agregar perfume</a>
        <a href="ver_auditoria.php" class="btn btn-auditoria"><i class="fas fa-search"></i> Auditoría de Productos</a>
        <a href="logout.php" class="btn btn-cerrar"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a>
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
                <td data-label="ID"><?= htmlspecialchars($row['Id']) ?></td>
                <td data-label="Imagen">
                    <?php if (!empty($row['imagen'])): ?>
                        <img src="imagenes/<?= htmlspecialchars($row['imagen']) ?>" alt="Perfume">
                    <?php else: ?>
                        <span class="sin-imagen">Sin imagen</span>
                    <?php endif; ?>
                </td>
                <td data-label="Nombre"><?= htmlspecialchars($row['Nombre']) ?></td>
                <td data-label="Descripción"><?= htmlspecialchars($row['descripcion']) ?></td>
                <td data-label="Precio">$<?= number_format($row['Precio'], 2) ?></td>
                <td data-label="Cantidad"><?= htmlspecialchars($row['Cantidad']) ?></td>
                <td data-label="Categoría"><?= htmlspecialchars($row['Categoria']) ?></td>
                <td data-label="Presentación"><?= htmlspecialchars($row['Presentacion']) ?></td>
                <td data-label="Acciones">
                    <a href="editar_producto.php?id=<?= $row['Id'] ?>" class="editar" title="Editar"><i class="fas fa-edit"></i></a>
                    <a href="eliminar_producto.php?id=<?= $row['Id'] ?>" class="eliminar" title="Eliminar" onclick="return confirm('¿Estás seguro de eliminar este producto?')"><i class="fas fa-trash-alt"></i></a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <!-- Paginación -->
    <div class="paginacion">
        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
            <a href="listar_productos.php?pagina=<?= $i ?>" class="<?= ($i == $pagina) ? 'activa' : '' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
    </div>
<?php else: ?>
    <p style="color: white; text-align: center; font-size: 1.2rem; margin-top: 40px;">No hay perfumes registrados aún.</p>
<?php endif; ?>

<!-- FontAwesome CDN -->
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>

</body>
</html>
