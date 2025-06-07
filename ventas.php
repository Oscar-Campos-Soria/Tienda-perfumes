<?php
session_start();
include 'db.php';

// Verificar si el usuario es un administrador
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'administrador') {
    header('Location: login.php');
    exit;
}

// AJAX: Cambiar estatus
if (isset($_POST['accion']) && $_POST['accion'] === 'cambiar_estatus') {
    $idPedido = intval($_POST['id_pedido']);
    $estatusActual = intval($_POST['estatus_actual']);

    // Secuencia de estados: 1 (Pendiente) → 2 (Enviado) → 3 (Completado) → 1 (Pendiente)
    $nuevoEstatus = 1;
    if ($estatusActual == 1) $nuevoEstatus = 2;
    elseif ($estatusActual == 2) $nuevoEstatus = 3;
    elseif ($estatusActual == 3) $nuevoEstatus = 1;

    $stmt = $conn->prepare("UPDATE pedido SET IdEstatus = ? WHERE IdPedido = ?");
    $stmt->bind_param("ii", $nuevoEstatus, $idPedido);
    $ok = $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => $ok, 'nuevoEstatus' => $nuevoEstatus]);
    exit;
}

// Definir la cantidad de registros por página
$limite = 10;
$pagina = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$inicio = ($pagina - 1) * $limite;

// Consulta para obtener las ventas (PEDIDOS)
$sql = "SELECT p.IdPedido, p.FechaPedido, p.Total, u.Username AS Usuario, p.IdEstatus, p.Referencia, p.IdMetodoPago
        FROM pedido p
        JOIN usuario u ON p.IdUsuario = u.IdUsuario
        ORDER BY p.FechaPedido DESC
        LIMIT $inicio, $limite";
$result = $conn->query($sql);
if (!$result) {
    die("Error al consultar las ventas: " . $conn->error);
}

// Contar el total de ventas para la paginación
$total_sql = "SELECT COUNT(*) AS total FROM pedido";
$total_result = $conn->query($total_sql);
$total_filas = $total_result->fetch_assoc()['total'];
$total_paginas = ceil($total_filas / $limite);

// Función para mostrar el estatus como botón interactivo
function mostrarEstatus($id, $idPedido) {
    $estilos = [
        1 => ['Pendiente', 'pendiente', '#ffe89b', '#ffc107', '#7a6108'],
        2 => ['Enviado', 'enviado', '#baf4e5', '#23c483', '#06563c'],
        3 => ['Completado', 'completado', '#d6eaff', '#1e90ff', '#114579']
    ];
    $etiqueta = $estilos[$id][0] ?? 'Otro';
    $clase = $estilos[$id][1] ?? 'otro';
    $bg = $estilos[$id][2] ?? '#ddd';
    $border = $estilos[$id][3] ?? '#bbb';
    $color = $estilos[$id][4] ?? '#444';
    return "<button 
        class='badge $clase cambiar-estatus'
        data-id='$idPedido' 
        data-estatus='$id'
        style='
            background: $bg;
            border: 2px solid $border;
            color: $color;
            box-shadow: 0 2px 8px #bbb4;
            font-weight: bold;
            cursor: pointer;
            outline: none;
            transition: background .18s, border .18s, color .18s, transform .09s;
        '
        title='Haz click para cambiar el estatus'
    >$etiqueta</button>";
}
function mostrarMetodo($id) {
    switch ($id) {
        case 1: return 'Efectivo';
        case 2: return 'Transferencia';
        default: return 'Otro';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ventas - PerFime</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #e4eafc;
            margin: 0;
            padding: 0;
        }
        .top-bar {
            background: linear-gradient(90deg, #6e4fd6 40%, #a372ec 100%);
            color: #fff;
            padding: 2.4rem 0 1.2rem 0;
            text-align: center;
            letter-spacing: 1.5px;
            box-shadow: 0 4px 16px #0002;
        }
        .container {
            max-width: 1100px;
            margin: 40px auto;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 2px 18px #bb9bfc33;
            padding: 36px 40px;
            animation: fadeIn .6s;
        }
        h2 {
            margin-top: 0;
            font-size: 2.7rem;
            letter-spacing: 1px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }
        th, td {
            padding: 15px 10px;
            text-align: center;
        }
        thead {
            background: linear-gradient(90deg, #5f43b2 20%, #b996f7 80%);
            color: #fff;
        }
        tr {
            transition: background 0.15s;
        }
        tbody tr:nth-child(odd) {
            background: #f5f4fa;
        }
        tbody tr:nth-child(even) {
            background: #ebe7f8;
        }
        tbody tr:hover {
            background: #e1dbfa;
        }
        .badge {
            border-radius: 1rem;
            padding: 7px 16px;
            min-width: 100px;
            font-size: 1.01rem;
            display: inline-block;
            box-shadow: 0 1px 8px #bbb5;
        }
        .cambiar-estatus:active {
            transform: scale(0.93);
        }
        .badge.pendiente:hover { background: #ffe055 !important; border-color: #ffb200 !important; color: #4f3806 !important;}
        .badge.enviado:hover   { background: #5cedc7 !important; border-color: #23c483 !important; color: #06563c !important;}
        .badge.completado:hover { background: #9fd4fd !important; border-color: #1e90ff !important; color: #114579 !important;}
        .paginacion {
            text-align: center;
            margin-top: 20px;
        }
        .paginacion a {
            display: inline-block;
            padding: 7px 16px;
            margin: 0 4px;
            background: #ece6fc;
            color: #6e4fd6;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            box-shadow: 0 1px 5px #0001;
            transition: background .15s, color .15s;
        }
        .paginacion a.activa, .paginacion a:hover {
            background: #6e4fd6;
            color: #fff;
        }
        @media (max-width: 700px) {
            .container { padding: 14px 4px; }
            th, td { padding: 7px 2px; font-size: 0.95em;}
            .badge { min-width: unset; padding: 5px 9px;}
        }
        .volver-btn {
            display: inline-block;
            margin-bottom: 20px;
            background: #f5f5fa;
            color: #6e4fd6;
            font-weight: bold;
            padding: 8px 20px;
            border-radius: 9px;
            border: none;
            text-decoration: none;
            box-shadow: 0 2px 8px #bdb3ef22;
            transition: background 0.2s, color 0.2s;
        }
        .volver-btn:hover {
            background: #6e4fd6;
            color: #fff;
        }
        @keyframes fadeIn { 0% { opacity:0; transform: translateY(40px);} 100% { opacity:1; transform:none;} }
    </style>
</head>
<body>
<div class="top-bar">
    <h2>📊 Lista de Ventas</h2>
</div>
<div class="container">
    <a class="volver-btn" href="index.php">← Volver al panel</a>
<?php if ($result->num_rows > 0): ?>
    <table>
        <thead>
            <tr>
                <th>ID Pedido</th>
                <th>Fecha</th>
                <th>Total</th>
                <th>Usuario</th>
                <th>Referencia</th>
                <th>Método de Pago</th>
                <th>Estatus</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['IdPedido']) ?></td>
                    <td><?= htmlspecialchars($row['FechaPedido']) ?></td>
                    <td><b>$<?= number_format($row['Total'], 2) ?></b></td>
                    <td><?= htmlspecialchars($row['Usuario']) ?></td>
                    <td><?= htmlspecialchars($row['Referencia']) ?></td>
                    <td><?= mostrarMetodo($row['IdMetodoPago']) ?></td>
                    <td><?= mostrarEstatus($row['IdEstatus'], $row['IdPedido']) ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <div class="paginacion">
        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
            <a href="ventas.php?pagina=<?= $i ?>" class="<?= $i === $pagina ? 'activa' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
<?php else: ?>
    <p style="text-align:center;font-size:1.1rem;color:#888;">No hay ventas registradas aún.</p>
<?php endif; ?>
</div>
<!-- ============= AJAX CAMBIO DE ESTATUS ============= -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    function setEvents() {
        document.querySelectorAll('.cambiar-estatus').forEach(btn => {
            btn.onclick = function() {
                btn.disabled = true;
                const idPedido = btn.getAttribute('data-id');
                const estatusActual = btn.getAttribute('data-estatus');
                fetch('ventas.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'accion=cambiar_estatus&id_pedido=' + idPedido + '&estatus_actual=' + estatusActual
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        let nuevo = 'Pendiente', clase = 'pendiente', bg='#ffe89b', border='#ffc107', color='#7a6108';
                        if (data.nuevoEstatus == 2) { nuevo = 'Enviado'; clase = 'enviado'; bg='#baf4e5'; border='#23c483'; color='#06563c'; }
                        if (data.nuevoEstatus == 3) { nuevo = 'Completado'; clase = 'completado'; bg='#d6eaff'; border='#1e90ff'; color='#114579'; }
                        btn.innerText = nuevo;
                        btn.setAttribute('data-estatus', data.nuevoEstatus);
                        btn.className = 'badge ' + clase + ' cambiar-estatus';
                        btn.style.background = bg;
                        btn.style.borderColor = border;
                        btn.style.color = color;
                        btn.disabled = false;
                    } else {
                        alert("No se pudo actualizar el estatus.");
                        btn.disabled = false;
                    }
                }).catch(()=>{
                    alert("Error al conectar con el servidor.");
                    btn.disabled = false;
                });
            }
        });
    }
    setEvents();
});
</script>
</body>
</html>
