<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'db.php';

// Solo permitir acceso a admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("⛔ Acceso denegado.");
}

$usuario_filtro = $_GET['usuario'] ?? '';
$condicion = $usuario_filtro ? "WHERE usuario LIKE '%" . $conn->real_escape_string($usuario_filtro) . "%'" : '';

$sql = "SELECT * FROM auditoria_productos $condicion ORDER BY fecha DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>📋 Auditoría Productos</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #6a11cb, #2575fc);
            padding: 30px;
            color: #333;
            min-height: 100vh;
            margin: 0;
        }
        h2 {
            text-align: center;
            color: #fff;
            margin-bottom: 25px;
            font-weight: 700;
            text-shadow: 1px 1px 4px rgba(0,0,0,0.5);
        }
        .barra-superior {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        .btn {
            padding: 10px 18px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            color: white;
            background-color: #ff6f61;
            box-shadow: 0 4px 10px rgba(255,111,97,0.5);
            transition: background-color 0.3s ease;
        }
        .btn:hover {
            background-color: #ff4a36;
            box-shadow: 0 6px 15px rgba(255,74,54,0.7);
        }
        form {
            margin-bottom: 20px;
            text-align: center;
        }
        input[type="text"] {
            padding: 10px 14px;
            width: 300px;
            border-radius: 30px;
            border: none;
            box-shadow: 0 0 8px rgba(0,0,0,0.15);
            font-size: 16px;
            transition: box-shadow 0.3s ease;
            outline: none;
        }
        input[type="text"]:focus {
            box-shadow: 0 0 12px #ff6f61;
        }
        button {
            padding: 10px 18px;
            border: none;
            background-color: #28a745;
            color: white;
            border-radius: 30px;
            cursor: pointer;
            font-weight: 600;
            margin-left: 8px;
            box-shadow: 0 4px 10px rgba(40,167,69,0.5);
            transition: background-color 0.3s ease;
            font-size: 16px;
        }
        button:hover {
            background-color: #1e7e34;
            box-shadow: 0 6px 15px rgba(30,126,52,0.7);
        }
        a.limpiar {
            margin-left: 15px;
            color: #ffc107;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        a.limpiar:hover {
            color: #ffa000;
            text-decoration: underline;
        }
        table {
            width: 100%;
            background: white;
            border-collapse: separate;
            border-spacing: 0 8px;
            box-shadow: 0 0 30px rgba(0,0,0,0.15);
            border-radius: 12px;
            overflow: hidden;
        }
        thead th {
            background-color: #2575fc;
            color: white;
            font-weight: 700;
            padding: 14px 20px;
            text-align: center;
            letter-spacing: 0.05em;
            border-bottom: 3px solid #1a56d4;
        }
        tbody tr {
            background: #f9f9f9;
            border-radius: 8px;
            box-shadow: inset 0 0 5px rgba(0,0,0,0.03);
            transition: background-color 0.3s ease;
        }
        tbody tr:hover {
            background-color: #e0f0ff;
            cursor: default;
        }
        tbody td {
            padding: 14px 20px;
            text-align: center;
            color: #555;
            font-weight: 500;
        }
        tbody td:first-child {
            font-weight: 700;
            color: #2575fc;
        }
        tbody td:nth-child(5), /* Precio */
        tbody td:nth-child(7), /* Cantidad */
        tbody td:nth-child(6) /* Acción */ {
            font-weight: 600;
            color: #333;
        }
        /* Responsive */
        @media screen and (max-width: 960px) {
            body {
                padding: 15px;
            }
            input[type="text"] {
                width: 70%;
                max-width: 300px;
            }
            table {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>

<div class="barra-superior">
    <h2>📋 Auditoría de Productos</h2>
    <a href="listar_productos.php" class="btn">← Volver al panel</a>
</div>

<form method="GET" action="ver_auditoria.php">
    <input type="text" name="usuario" placeholder="Filtrar por usuario" value="<?= htmlspecialchars($usuario_filtro) ?>">
    <button type="submit">Buscar</button>
    <a href="ver_auditoria.php" class="limpiar">Limpiar filtro</a>
</form>

<?php if ($result && $result->num_rows > 0): ?>
<table>
    <thead>
        <tr>
            <th>ID Auditoría</th>
            <th>Usuario</th>
            <th>Acción</th>
            <th>Fecha</th>
            <th>Descripción</th>
            <th>Precio</th>
            <th>Cantidad</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $result->fetch_assoc()): 
            // Decodificar JSON de datos nuevos o anteriores
            $datos = json_decode($row['datos_nuevos'], true);
            if (!$datos) {
                $datos = json_decode($row['datos_anteriores'], true);
            }

            // Preparar valores para mostrar
            $descripcion = $datos['descripcion'] ?? 'N/A';
            $precio = isset($datos['precio']) ? number_format($datos['precio'], 2) : 'N/A';
            $cantidad = $datos['cantidad'] ?? 'N/A';
        ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['usuario']) ?></td>
            <td><?= htmlspecialchars($row['accion']) ?></td>
            <td><?= $row['fecha'] ?></td>
            <td><?= htmlspecialchars($descripcion) ?></td>
            <td>$<?= $precio ?></td>
            <td><?= htmlspecialchars($cantidad) ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>
<?php else: ?>
    <p style="color: white; text-align:center; font-size: 18px;">No se encontraron registros de auditoría.</p>
<?php endif; ?>

</body>
</html>
