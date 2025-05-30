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

if (isset($_GET['agregar'])) {
    $id = $_GET['agregar'];
    $_SESSION['carrito'][$id] = ($_SESSION['carrito'][$id] ?? 0) + 1;
    header("Location: ver_tienda.php");
    exit;
}

if (isset($_GET['quitar'])) {
    $id = $_GET['quitar'];
    if (isset($_SESSION['carrito'][$id])) {
        $_SESSION['carrito'][$id]--;
        if ($_SESSION['carrito'][$id] <= 0) {
            unset($_SESSION['carrito'][$id]);
        }
    }
    header("Location: ver_tienda.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_actualizar'])) {
    $id = intval($_POST['id']);
    $cantidad = intval($_POST['cantidad']);
    if ($cantidad <= 0) {
        unset($_SESSION['carrito'][$id]);
    } else {
        $_SESSION['carrito'][$id] = $cantidad;
    }
    $total = 0;
    foreach ($_SESSION['carrito'] as $prod_id => $cant) {
        $sql = $conn->prepare("SELECT Precio FROM productos WHERE Id=?");
        $sql->bind_param("i", $prod_id);
        $sql->execute();
        $res = $sql->get_result()->fetch_assoc();
        $precio = $res['Precio'] ?? 0;
        $total += $precio * $cant;
        $sql->close();
    }
    echo json_encode(['success' => true, 'total' => number_format($total, 2)]);
    exit;
}

$categoria = $_GET['categoria'] ?? '';
$presentacion = $_GET['presentacion'] ?? '';
$min = isset($_GET['min']) && $_GET['min'] !== '0' ? floatval($_GET['min']) : '';
$max = isset($_GET['max']) && $_GET['max'] !== '999999' ? floatval($_GET['max']) : '';

$min_sql = $min !== '' ? $min : 0;
$max_sql = $max !== '' ? $max : 999999;

$pagina_actual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$por_pagina = 10;
$inicio = ($pagina_actual - 1) * $por_pagina;

$count_sql = "SELECT COUNT(*) FROM productos WHERE Precio BETWEEN ? AND ?";
$params = [$min_sql, $max_sql];
$types = "dd";

if ($categoria) {
    $count_sql .= " AND Categoria = ?";
    $params[] = $categoria;
    $types .= "s";
}
if ($presentacion) {
    $count_sql .= " AND Presentacion = ?";
    $params[] = $presentacion;
    $types .= "s";
}

$stmt_count = $conn->prepare($count_sql);
$stmt_count->bind_param($types, ...$params);
$stmt_count->execute();
$stmt_count->bind_result($total_productos);
$stmt_count->fetch();
$stmt_count->close();

$total_paginas = ceil($total_productos / $por_pagina);

$sql = "SELECT * FROM productos WHERE Precio BETWEEN ? AND ?";
$params = [$min_sql, $max_sql];
$types = "dd";

if ($categoria) {
    $sql .= " AND Categoria = ?";
    $params[] = $categoria;
    $types .= "s";
}
if ($presentacion) {
    $sql .= " AND Presentacion = ?";
    $params[] = $presentacion;
    $types .= "s";
}

$sql .= " ORDER BY Id DESC LIMIT ?, ?";
$params[] = $inicio;
$params[] = $por_pagina;
$types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$productos = [];
while ($row = $result->fetch_assoc()) {
    $productos[$row['Id']] = $row;
}
$stmt->close();

$masVendidos = $conn->query("SELECT * FROM productos ORDER BY Cantidad DESC LIMIT 3");

$usuario = $_SESSION['username'] ?? 'cliente';
$referencia = strtoupper(uniqid("PED"));
$fecha = date('Y-m-d H:i:s');

if (isset($_POST['finalizar'])) {
    $totalFinal = $_POST['total'];
    $refFinal = $_POST['referencia'];
    $metodoPago = 'Transferencia';

    $conn->query("INSERT INTO pedidos (usuario, total, referencia, fecha_pedido, metodo_pago)
                  VALUES ('$usuario', $totalFinal, '$refFinal', '$fecha', '$metodoPago')");
    $pedido_id = $conn->insert_id;

    foreach ($_SESSION['carrito'] as $id => $cantidad) {
        if (!isset($productos[$id])) continue;
        $producto = $productos[$id];
        $precio = $producto['Precio'];

        $conn->query("INSERT INTO detalle_pedidos (pedido_id, producto_id, cantidad, precio_unitario)
                      VALUES ($pedido_id, $id, $cantidad, $precio)");

        $conn->query("UPDATE productos SET Cantidad = Cantidad - $cantidad WHERE Id = $id");
    }

    $_SESSION['carrito'] = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>PerFime</title>

<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
<!-- FontAwesome CSS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />

<style>
    /* General */
    body {
        background: linear-gradient(135deg, #311b92, #512da8);
        color: #f0e8ff;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        min-height: 100vh;
        margin: 0;
        padding: 20px 15px;
    }

    header {
        background-color: #4527a0;
        padding: 22px 30px;
        border-radius: 12px;
        box-shadow: 0 6px 20px rgba(69, 39, 160, 0.8);
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 35px;
    }
    header h2 {
        font-weight: 900;
        font-size: 1.9rem;
        letter-spacing: 1.2px;
        text-shadow: 0 0 8px rgba(255, 255, 255, 0.5);
    }
    .logout {
        background-color: #7e57c2;
        color: white;
        border-radius: 50px;
        font-weight: 700;
        padding: 10px 26px;
        box-shadow: 0 5px 18px rgba(126, 87, 194, 0.8);
        text-decoration: none;
        transition: background-color 0.3s ease, box-shadow 0.3s ease;
    }
    .logout:hover {
        background-color: #5e35b1;
        box-shadow: 0 7px 25px rgba(94, 53, 177, 1);
    }

    /* Filtros */
    .filters-form {
        background: rgba(97, 54, 192, 0.85);
        padding: 20px 22px;
        border-radius: 18px;
        display: flex;
        flex-wrap: wrap;
        gap: 18px;
        margin-bottom: 50px;
        box-shadow: 0 8px 28px rgba(126, 87, 194, 0.6);
    }
    .filters-form select,
    .filters-form input[type="number"] {
        background: #e0d7ff;
        border: none;
        border-radius: 10px;
        padding: 10px 18px;
        min-width: 140px;
        font-size: 1rem;
        font-weight: 600;
        color: #311b92;
        transition: box-shadow 0.3s ease;
    }
    .filters-form select:focus,
    .filters-form input[type="number"]:focus {
        outline: none;
        box-shadow: 0 0 8px #9575cd;
        background: #fff;
    }
    .filters-form button {
        background-color: #9575cd;
        border: none;
        color: white;
        font-weight: 700;
        padding: 12px 34px;
        border-radius: 50px;
        cursor: pointer;
        box-shadow: 0 6px 22px rgba(149, 117, 205, 0.9);
        transition: background-color 0.3s ease, box-shadow 0.3s ease;
        min-width: 150px;
    }
    .filters-form button:hover {
        background-color: #6a1b9a;
        box-shadow: 0 9px 28px rgba(106, 27, 154, 1);
    }
    .filters-form a.limpiar {
        color: #9575cd;
        font-weight: 700;
        text-decoration: underline;
        margin-left: auto;
        align-self: center;
        cursor: pointer;
        transition: color 0.3s ease;
    }
    .filters-form a.limpiar:hover {
        color: #6a1b9a;
    }

    /* Grid productos */
    .producto-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit,minmax(250px,1fr));
        gap: 28px;
        margin-bottom: 55px;
    }
    .producto {
        background: #ede7f6;
        border-radius: 20px;
        padding: 26px 24px 30px;
        color: #311b92;
        text-align: center;
        box-shadow: 0 12px 32px rgba(93, 63, 211, 0.25);
        transition: transform 0.4s ease, box-shadow 0.4s ease;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .producto:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 48px rgba(93, 63, 211, 0.38);
    }
    .producto img {
        max-width: 150px;
        margin: 0 auto 22px;
        border-radius: 16px;
        box-shadow: 0 6px 20px rgba(93, 63, 211, 0.3);
    }
    .producto h4 {
        font-weight: 900;
        font-size: 1.3rem;
        margin-bottom: 18px;
        color: #4a148c;
    }
    .producto p {
        font-size: 1rem;
        margin-bottom: 20px;
        color: #5e35b1;
        min-height: 58px;
        line-height: 1.2;
    }
    .producto strong {
        font-size: 1.4rem;
        color: #6a1b9a;
        font-weight: 900;
        margin-bottom: 22px;
        display: block;
    }
    .producto button {
        background-color: #4a148c;
        color: white !important;
        border: none;
        padding: 12px 32px;
        border-radius: 40px;
        font-weight: 900;
        cursor: pointer;
        box-shadow: 0 8px 28px rgba(74, 20, 140, 0.8);
        transition: background-color 0.3s ease, box-shadow 0.3s ease;
        align-self: center;
        margin-top: auto;
    }
    .producto button:hover {
        background-color: #38006b;
        box-shadow: 0 12px 38px rgba(56, 0, 107, 0.95);
    }

    /* Paginación */
    .paginacion {
        text-align: center;
        margin-bottom: 60px;
    }
    .paginacion a {
        display: inline-block;
        margin: 0 10px;
        padding: 14px 22px;
        background-color: #9575cd;
        color: white;
        font-weight: 700;
        border-radius: 18px;
        text-decoration: none;
        box-shadow: 0 8px 25px rgba(149, 117, 205, 0.85);
        transition: background-color 0.3s ease, box-shadow 0.3s ease;
    }
    .paginacion a.activa,
    .paginacion a:hover {
        background-color: #4a148c;
        box-shadow: 0 14px 40px rgba(74, 20, 140, 0.95);
    }

    /* Títulos */
    h3 {
        font-weight: 900;
        font-size: 2.1rem;
        margin-bottom: 28px;
        color: #d1c4e9;
        text-shadow: 0 0 12px rgba(38, 0, 77, 0.7);
    }

    /* Tabla carrito */
    table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 8px;
        margin-bottom: 30px;
        font-weight: 700;
        color: #311b92;
    }
    th, td {
        padding: 18px 12px;
        text-align: center;
        vertical-align: middle;
    }
    th {
        background: linear-gradient(90deg, #6a1b9a, #9575cd);
        color: white;
        font-weight: 900;
        border-top-left-radius: 12px;
        border-top-right-radius: 12px;
        box-shadow: 0 5px 20px rgba(106, 27, 154, 0.7);
    }
    tbody tr {
        background: #ede7f6;
        border-radius: 12px;
        box-shadow: 0 6px 20px rgba(126, 87, 194, 0.3);
        transition: box-shadow 0.3s ease;
    }
    tbody tr:hover {
        box-shadow: 0 12px 40px rgba(126, 87, 194, 0.55);
    }

    /* Botones cantidad */
    .input-group > button {
        min-width: 38px;
        cursor: pointer;
        color: #4a148c;
        border: 1.5px solid #4a148c;
        background: transparent;
        font-weight: 700;
        transition: background-color 0.3s ease, color 0.3s ease;
        border-radius: 6px;
    }
    .input-group > button:hover {
        background-color: #4a148c;
        color: white;
        border-color: #38006b;
    }
    input[type=number] {
        border: 1.5px solid #9575cd;
        border-radius: 6px;
        padding: 4px 8px;
        max-width: 60px;
        font-weight: 700;
        color: #4a148c;
        text-align: center;
        transition: border-color 0.3s ease;
    }
    input[type=number]:focus {
        outline: none;
        border-color: #38006b;
        box-shadow: 0 0 8px #38006b;
    }
    /* Botón quitar */
    .btn-danger {
        background-color: #9c27b0;
        border: none;
        box-shadow: 0 6px 20px rgba(156, 39, 176, 0.7);
        transition: background-color 0.3s ease, box-shadow 0.3s ease;
    }
    .btn-danger:hover {
        background-color: #6a1b9a;
        box-shadow: 0 10px 28px rgba(106, 27, 154, 0.85);
    }

    /* Total y actualizar */
    .d-flex h4 {
        font-weight: 900;
        color: #d1c4e9;
        text-shadow: 0 1px 4px rgba(38, 0, 77, 0.7);
    }
    .btn-warning {
        background-color: #9575cd;
        color: #311b92;
        font-weight: 900;
        padding: 14px 38px;
        border-radius: 50px;
        box-shadow: 0 8px 28px rgba(149, 117, 205, 0.9);
        transition: background-color 0.3s ease, box-shadow 0.3s ease;
    }
    .btn-warning:hover {
        background-color: #4a148c;
        color: white;
        box-shadow: 0 14px 40px rgba(74, 20, 140, 0.95);
    }

    /* Finalizar compra */
    .btn-success {
        background-color: #6a1b9a;
        font-weight: 900;
        padding: 16px 0;
        border-radius: 50px;
        box-shadow: 0 10px 38px rgba(106, 27, 154, 0.9);
        transition: background-color 0.3s ease, box-shadow 0.3s ease;
    }
    .btn-success:hover {
        background-color: #38006b;
        box-shadow: 0 14px 48px rgba(56, 0, 107, 1);
    }

    /* Instrucciones de pago */
    .pago {
        background: linear-gradient(135deg, #9575cd, #6a1b9a);
        padding: 28px 32px;
        border-radius: 30px;
        font-weight: 900;
        box-shadow: 0 15px 55px rgba(149, 117, 205, 0.45);
        color: #ede7f6;
        text-shadow: 0 2px 8px rgba(38,0,77,0.6);
        margin-top: 30px;
    }
    .pago h3 {
        font-size: 1.9rem;
        margin-bottom: 20px;
    }

    /* Responsive mobile */
    @media (max-width: 767px) {
        body {
            padding: 15px 12px;
        }
        header {
            flex-direction: column;
            gap: 12px;
            padding: 18px 20px;
            text-align: center;
        }
        header h2 {
            font-size: 1.6rem;
        }
        .filters-form {
            flex-direction: column;
            padding: 20px 18px;
            gap: 15px;
        }
        .filters-form select,
        .filters-form input[type="number"],
        .filters-form button,
        .filters-form a.limpiar {
            width: 100%;
            min-width: auto;
        }
        .producto-grid {
            grid-template-columns: 1fr;
            gap: 25px;
            margin-bottom: 45px;
        }
        .producto {
            padding: 22px 18px 26px;
        }
        .producto img {
            max-width: 120px;
        }
        .producto h4 {
            font-size: 1.25rem;
        }
        .producto p {
            font-size: 0.95rem;
        }
        table {
            font-size: 0.95rem;
        }
        th, td {
            padding: 14px 10px;
        }
        .input-group > button {
            min-width: 32px;
            font-weight: 600;
        }
        input[type=number] {
            max-width: 50px;
            padding: 3px 6px;
            font-weight: 600;
        }
        .d-flex {
            flex-direction: column;
            gap: 20px;
            align-items: stretch;
        }
        .d-flex h4 {
            text-align: center;
        }
        .btn-warning {
            width: 100%;
        }
        .btn-success {
            padding: 14px 0;
            font-size: 1.15rem;
        }
        .pago {
            padding: 22px 20px;
        }
    }
</style>
</head>
<body>

<header>
    <h2>✨ PerFime</h2>
    <a href="logout.php" class="logout" aria-label="Cerrar sesión">Cerrar sesión</a>
</header>

<div class="filtros">
    <form class="filters-form" method="get" action="ver_tienda.php" aria-label="Formulario de filtros de productos">
        <select name="categoria" aria-label="Filtrar por categoría">
            <option value="">Categoría</option>
            <option value="dama" <?= $categoria === 'dama' ? 'selected' : '' ?>>Dama</option>
            <option value="caballero" <?= $categoria === 'caballero' ? 'selected' : '' ?>>Caballero</option>
            <option value="mixto" <?= $categoria === 'mixto' ? 'selected' : '' ?>>Mixto</option>
        </select>

        <select name="presentacion" aria-label="Filtrar por presentación">
            <option value="">Presentación</option>
            <option value="completo" <?= $presentacion === 'completo' ? 'selected' : '' ?>>Completo</option>
            <option value="5ml" <?= $presentacion === '5ml' ? 'selected' : '' ?>>5 ml</option>
            <option value="10ml" <?= $presentacion === '10ml' ? 'selected' : '' ?>>10 ml</option>
        </select>

        <input type="number" name="min" step="0.01" aria-label="Precio mínimo" placeholder="Precio mínimo" value="<?= htmlspecialchars($min) ?>">
        <input type="number" name="max" step="0.01" aria-label="Precio máximo" placeholder="Precio máximo" value="<?= htmlspecialchars($max) ?>">

        <button type="submit" aria-label="Aplicar filtros">Filtrar</button>
        <a href="ver_tienda.php" class="limpiar" aria-label="Limpiar filtros">Limpiar</a>
    </form>
</div>

<h3><i class="fa-solid fa-star"></i> Más Vendidos</h3>
<div class="producto-grid" aria-label="Lista de productos más vendidos">
    <?php while ($row = $masVendidos->fetch_assoc()): ?>
        <div class="producto" role="group" aria-labelledby="producto-<?= $row['Id'] ?>">
            <?php if ($row['imagen']): ?>
                <img src="imagenes/<?= htmlspecialchars($row['imagen']) ?>" alt="<?= htmlspecialchars($row['Nombre']) ?>" loading="lazy" />
            <?php else: ?>
                <p>Sin imagen</p>
            <?php endif; ?>
            <h4 id="producto-<?= $row['Id'] ?>"><?= htmlspecialchars($row['Nombre']) ?></h4>
            <p><strong>$<?= number_format($row['Precio'], 2) ?></strong></p>
        </div>
    <?php endwhile; ?>
</div>

<h3><i class="fa-solid fa-shopping-bag"></i> Productos Disponibles</h3>
<div class="producto-grid" aria-label="Lista de productos disponibles">
    <?php foreach ($productos as $id => $producto): ?>
        <div class="producto" role="group" aria-labelledby="producto-<?= $id ?>">
            <?php if ($producto['imagen']): ?>
                <img src="imagenes/<?= htmlspecialchars($producto['imagen']) ?>" alt="<?= htmlspecialchars($producto['Nombre']) ?>" loading="lazy" />
            <?php else: ?>
                <p>Sin imagen</p>
            <?php endif; ?>
            <h4 id="producto-<?= $id ?>"><?= htmlspecialchars($producto['Nombre']) ?></h4>
            <p><?= htmlspecialchars($producto['descripcion']) ?></p>
            <p><strong>$<?= number_format($producto['Precio'], 2) ?></strong></p>
            <button onclick="agregarAlCarrito(<?= $id ?>)" aria-label="Agregar <?= htmlspecialchars($producto['Nombre']) ?> al carrito" class="btn btn-primary mt-2">Agregar al carrito</button>
        </div>
    <?php endforeach; ?>
</div>

<div class="paginacion" role="navigation" aria-label="Paginación de productos">
    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
        <a class="<?= $i == $pagina_actual ? 'activa' : '' ?>"
           href="?<?= http_build_query(array_merge($_GET, ['pagina' => $i])) ?>"
           aria-current="<?= $i == $pagina_actual ? 'page' : 'false' ?>">
            <?= $i ?>
        </a>
    <?php endfor; ?>
</div>

<h3><i class="fa-solid fa-cart-shopping"></i> Carrito</h3>
<?php if (empty($_SESSION['carrito'])): ?>
    <p>El carrito está vacío.</p>
<?php else: ?>
    <form id="carrito-form" method="POST" action="ver_tienda.php?<?= $_SERVER['QUERY_STRING'] ?>" aria-label="Formulario de carrito de compras">
        <input type="hidden" name="actualizar_carrito" value="1" />
        <table class="table table-bordered text-center align-middle" aria-describedby="total-carrito" style="color:#311b92;">
            <thead class="table-primary">
                <tr>
                    <th>Producto</th>
                    <th style="width: 140px;">Cantidad</th>
                    <th>Subtotal</th>
                    <th>Quitar</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $total = 0;
            foreach ($_SESSION['carrito'] as $id => $cantidad):
                $producto = $productos[$id];
                $subtotal = $producto['Precio'] * $cantidad;
                $total += $subtotal;
            ?>
                <tr>
                    <td><?= htmlspecialchars($producto['Nombre']) ?></td>
                    <td>
                        <div class="input-group justify-content-center">
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="cambiarCantidad(<?= $id ?>, -1)" aria-label="Disminuir cantidad">-</button>
                            <input type="number" name="cantidades[<?= $id ?>]" id="cantidad-<?= $id ?>" class="form-control form-control-sm text-center" value="<?= $cantidad ?>" min="1" max="100" aria-label="Cantidad del producto <?= htmlspecialchars($producto['Nombre']) ?>" />
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="cambiarCantidad(<?= $id ?>, 1)" aria-label="Aumentar cantidad">+</button>
                        </div>
                    </td>
                    <td>$<?= number_format($subtotal, 2) ?></td>
                    <td>
                        <button type="button" class="btn btn-danger btn-sm" onclick="quitarProducto(<?= $id ?>)" aria-label="Quitar <?= htmlspecialchars($producto['Nombre']) ?> del carrito">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <h4>Total: <span id="total-carrito" style="color:#6a1b9a;">$<?= number_format($total, 2) ?></span></h4>
            <button type="submit" class="btn btn-warning btn-lg mt-3 mt-md-0" aria-label="Actualizar carrito">Actualizar carrito</button>
        </div>
    </form>

    <form method="POST" class="mt-4" aria-label="Formulario de finalizar compra">
        <input type="hidden" name="total" value="<?= $total ?>">
        <input type="hidden" name="referencia" value="<?= $referencia ?>">
        <button type="submit" name="finalizar" class="btn btn-success btn-lg w-100 mt-3" aria-label="Finalizar compra">Finalizar compra</button>
    </form>

    <section class="pago" aria-labelledby="pago-instrucciones">
        <h3 id="pago-instrucciones">💳 Instrucciones de pago</h3>
        <p><strong>Banco:</strong> BBVA</p>
        <p><strong>Cuenta:</strong> 1234 5678 9012 3456</p>
        <p><strong>Referencia:</strong> <?= htmlspecialchars($referencia) ?></p>
    </section>
<?php endif; ?>

<script>
function cambiarCantidad(id, delta) {
    const input = document.getElementById('cantidad-' + id);
    let valor = parseInt(input.value);
    valor += delta;
    if (valor < 1) valor = 1;
    if (valor > 100) valor = 100;
    input.value = valor;
    actualizarCarrito(id, valor);
}

function quitarProducto(id) {
    actualizarCarrito(id, 0);
}

function actualizarCarrito(id, cantidad) {
    fetch('ver_tienda.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            ajax_actualizar: '1',
            id: id,
            cantidad: cantidad
        })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            if(cantidad <= 0) {
                const row = document.getElementById('cantidad-' + id).closest('tr');
                row.remove();
            } else {
                document.getElementById('cantidad-' + id).value = cantidad;
            }
            document.getElementById('total-carrito').textContent = data.total;

            if(document.querySelectorAll('tbody tr').length === 0) {
                document.getElementById('carrito-form').innerHTML = '<p>El carrito está vacío.</p>';
            }
        }
    })
    .catch(console.error);
}

function agregarAlCarrito(id) {
    const url = new URL(window.location.href);
    url.searchParams.set('agregar', id);
    window.location.href = url.toString();
}
</script>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
