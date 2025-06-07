<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'db.php';

// Solo permitir acceso a administrador
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'administrador') {
    die("⛔ Acceso denegado.");
}

// Filtros
$usuario_filtro = $_GET['usuario'] ?? '';
$tipo_filtro    = $_GET['tipo'] ?? '';
$pagina         = isset($_GET['pagina']) && is_numeric($_GET['pagina']) && $_GET['pagina'] > 0 ? intval($_GET['pagina']) : 1;
$limite         = 12;
$offset         = ($pagina - 1) * $limite;

// WHERE dinámico
$where = [];
$params = [];
$types  = '';

if ($usuario_filtro) {
    $where[]  = "u.Username LIKE ?";
    $params[] = "%{$usuario_filtro}%";
    $types   .= 's';
}
if ($tipo_filtro && in_array($tipo_filtro, ['producto', 'pedido'])) {
    $where[]  = "a.Tabla = ?";
    $params[] = $tipo_filtro;
    $types   .= 's';
} else {
    $where[] = "(a.Tabla = 'producto' OR a.Tabla = 'pedido')";
}
$condicion = "WHERE " . implode(" AND ", $where);

// Total para paginación
$sql_total = "SELECT COUNT(*) FROM auditoria a LEFT JOIN usuario u ON a.IdUsuario = u.IdUsuario $condicion";
$stmt_total = $conn->prepare($sql_total);
if ($types !== '') $stmt_total->bind_param($types, ...$params);
$stmt_total->execute();
$stmt_total->bind_result($total_registros);
$stmt_total->fetch();
$stmt_total->close();
$total_paginas = max(1, ceil($total_registros / $limite));

// Consulta principal
$sql = "SELECT a.*, u.Username AS Usuario
        FROM auditoria a
        LEFT JOIN usuario u ON a.IdUsuario = u.IdUsuario
        $condicion
        ORDER BY a.Fecha DESC
        LIMIT ? OFFSET ?";
$bindParams = $params;
$bindTypes  = $types . 'ii';
$bindParams[] = $limite;
$bindParams[] = $offset;
$stmt = $conn->prepare($sql);
$refs = [];
foreach ($bindParams as $key => $value) { $refs[$key] = &$bindParams[$key]; }
call_user_func_array([$stmt, 'bind_param'], array_merge([$bindTypes], $refs));
$stmt->execute();
$result = $stmt->get_result();

// Decodificar JSON seguro
function safeJsonDecode($json) {
    if (!$json || $json === "null") return null;
    $data = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) return null;
    return $data;
}

// Etiqueta bonita según tabla
function tipoRegistroBonito($tabla) {
    if ($tabla === 'producto') return '<span style="color:#6ec5ff;">Producto</span>';
    if ($tabla === 'pedido')   return '<span style="color:#ffe156;">Venta/Pedido</span>';
    return $tabla;
}

// Mostrar detalles de productos para ventas
function mostrarDetallePedido($datosNuevos) {
    if (is_array($datosNuevos) && isset($datosNuevos[0]['IdProducto'])) {
        $html = '';
        foreach ($datosNuevos as $prod) {
            $nombre   = htmlspecialchars($prod['Nombre'] ?? '-');
            $cantidad = htmlspecialchars($prod['Cantidad'] ?? '-');
            $precio   = isset($prod['PrecioUnitario']) ? '$'.number_format($prod['PrecioUnitario'],2) : '-';
            $html .= "Producto #{$prod['IdProducto']} ({$cantidad} x {$precio})<br>";
        }
        return $html;
    }
    return '-';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>📋 Auditoría General</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #19191c; color: #eee; padding: 32px; }
        .barra-superior { display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; }
        a.btn { background: #007bff; color: white; padding: 8px 18px; border-radius: 6px; text-decoration: none; font-weight: 600; box-shadow: 0 2px 8px #4446; }
        a.btn:hover { background: #0056b3; }
        form { margin-bottom: 28px; display: flex; gap: 12px; align-items: center; }
        input[type="text"], select { padding: 7px 12px; border-radius: 5px; border: none; background: #242434; color: #e9e9f1; width: 230px; }
        button { padding: 8px 18px; border: none; border-radius: 5px; background: #30d158; color: white; font-weight: 600; cursor: pointer; margin-left: 8px;}
        button:hover { background: #26ab45; }
        a.limpiar { margin-left: 15px; color: #ffc107; text-decoration: none; font-weight: 600; }
        a.limpiar:hover { text-decoration: underline; }
        table { width: 100%; border-collapse: collapse; background: #25253a; border-radius: 10px; overflow: hidden; margin-bottom: 30px; }
        th, td { padding: 11px 7px; text-align: center; border-bottom: 1px solid #39394d; font-size: 14px; }
        th { background: #393952; font-weight: 700; }
        tr:hover { background-color: #33334a; }
        .json-mini { font-size: 12px; color: #bbb; background: #181829; border-radius: 4px; padding: 3px 7px; }
        .paginacion { margin-top: 16px; text-align: center; }
        .paginacion a, .paginacion span {
            color: #eee; padding: 7px 13px; margin: 0 3px; text-decoration: none;
            background: #23233a; border-radius: 7px; font-weight: 600; display: inline-block; cursor: pointer;
        }
        .paginacion a:hover { background: #007bff; }
        .paginacion .activo { background: #007bff; pointer-events: none; }
        .json-view-btn {
            background: #39394d; border: 1px solid #232345; color: #ffc107; border-radius: 5px;
            font-size: 11px; padding: 2px 12px; cursor: pointer; margin-bottom: 3px;
        }
        .tipo-label { font-size: 13px; font-weight: bold; }
    </style>
    <script>
        function toggleJson(id) {
            var el = document.getElementById(id);
            if (el.style.display === 'block') el.style.display = 'none';
            else el.style.display = 'block';
        }
    </script>
</head>
<body>
<div class="barra-superior">
    <h2>📋 Auditoría General</h2>
    <a href="listar_productos.php" class="btn">← Volver al panel</a>
</div>

<form method="GET" action="ver_auditoria.php">
    <input type="text" name="usuario" placeholder="Filtrar por usuario" value="<?= htmlspecialchars($usuario_filtro) ?>">
    <select name="tipo">
        <option value="">Todos</option>
        <option value="producto" <?= $tipo_filtro=='producto'?'selected':''; ?>>Producto</option>
        <option value="pedido" <?= $tipo_filtro=='pedido'?'selected':''; ?>>Venta/Pedido</option>
    </select>
    <button type="submit">Buscar</button>
    <a href="ver_auditoria.php" class="limpiar">Limpiar filtro</a>
</form>

<?php if ($result && $result->num_rows > 0): ?>
<table>
    <thead>
        <tr>
            <th>ID Auditoría</th>
            <th>Tipo</th>
            <th>Usuario</th>
            <th>Acción</th>
            <th>Fecha</th>
            <th>ID Registro</th>
            <th>Detalle Productos (solo ventas)</th>
            <th>JSON Anterior</th>
            <th>JSON Nuevo</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $result->fetch_assoc()):
            $datosNuevos     = safeJsonDecode($row['DatosNuevos']);
            $datosAnteriores = safeJsonDecode($row['DatosAnteriores']);
            $accion = strtoupper($row['Accion']);
            $tabla  = $row['Tabla'];
            $jsonAnteriorId = "ja_" . $row['IdAuditoria'];
            $jsonNuevoId    = "jn_" . $row['IdAuditoria'];
            ?>
        <tr>
            <td><?= htmlspecialchars($row['IdAuditoria']) ?></td>
            <td class="tipo-label"><?= tipoRegistroBonito($tabla) ?></td>
            <td><?= $row['Usuario'] ? htmlspecialchars($row['Usuario']) : '-' ?></td>
            <td><?= htmlspecialchars($row['Accion']) ?></td>
            <td><?= htmlspecialchars($row['Fecha']) ?></td>
            <td><?= ($tabla=='producto') ? 'Producto #'.htmlspecialchars($row['IdRegistro']) : 'Venta #'.htmlspecialchars($row['IdRegistro']) ?></td>
            <td>
                <?php
                if ($tabla === 'pedido') {
                    echo mostrarDetallePedido($datosNuevos);
                } else {
                    echo '-';
                }
                ?>
            </td>
            <td>
                <button type="button" class="json-view-btn" onclick="toggleJson('<?= $jsonAnteriorId ?>')">Ver</button>
                <div id="<?= $jsonAnteriorId ?>" style="display:none;max-width:250px;overflow-x:auto;word-break:break-all;" class="json-mini">
                    <?= htmlspecialchars($row['DatosAnteriores']) ?>
                </div>
            </td>
            <td>
                <button type="button" class="json-view-btn" onclick="toggleJson('<?= $jsonNuevoId ?>')">Ver</button>
                <div id="<?= $jsonNuevoId ?>" style="display:none;max-width:250px;overflow-x:auto;word-break:break-all;" class="json-mini">
                    <?= htmlspecialchars($row['DatosNuevos']) ?>
                </div>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<div class="paginacion">
    <?php if ($pagina > 1): ?>
        <a href="?usuario=<?= urlencode($usuario_filtro) ?>&tipo=<?= urlencode($tipo_filtro) ?>&pagina=<?= $pagina - 1 ?>">← Anterior</a>
    <?php else: ?>
        <span style="opacity: 0.5;">← Anterior</span>
    <?php endif; ?>
    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
        <?php if ($i == $pagina): ?>
            <span class="activo"><?= $i ?></span>
        <?php else: ?>
            <a href="?usuario=<?= urlencode($usuario_filtro) ?>&tipo=<?= urlencode($tipo_filtro) ?>&pagina=<?= $i ?>"><?= $i ?></a>
        <?php endif; ?>
    <?php endfor; ?>
    <?php if ($pagina < $total_paginas): ?>
        <a href="?usuario=<?= urlencode($usuario_filtro) ?>&tipo=<?= urlencode($tipo_filtro) ?>&pagina=<?= $pagina + 1 ?>">Siguiente →</a>
    <?php else: ?>
        <span style="opacity: 0.5;">Siguiente →</span>
    <?php endif; ?>
</div>

<?php else: ?>
    <p style="text-align:center; font-size: 18px;">No se encontraron registros de auditoría.</p>
<?php endif; ?>

</body>
</html>
