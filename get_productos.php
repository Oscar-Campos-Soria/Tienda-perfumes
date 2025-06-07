<?php
header('Content-Type: application/json');
include 'db.php';

$response = ['success' => false, 'data' => [], 'total_paginas' => 1, 'message' => ''];

try {
    $conn->set_charset("utf8mb4");

    // Parámetros de paginación y filtros
    $pagina = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
    $por_pagina = 9;
    $offset = ($pagina - 1) * $por_pagina;

    $categoria = $_GET['categoria'] ?? '';
    $presentacion = $_GET['presentacion'] ?? '';
    $min = $_GET['min'] ?? '';
    $max = $_GET['max'] ?? '';

    // Construir la consulta base con filtros
    $sql_where = "WHERE 1=1";
    $params = [];
    $types = "";

    if ($categoria !== '') {
        $sql_where .= " AND Categoria = ?";
        $params[] = $categoria;
        $types .= "s";
    }

    if ($presentacion !== '') {
        $sql_where .= " AND IdPresentacion = ?";
        $params[] = $presentacion;
        $types .= "s";
    }

    if ($min !== '') {
        $sql_where .= " AND Precio >= ?";
        $params[] = floatval($min);
        $types .= "d";
    }

    if ($max !== '') {
        $sql_where .= " AND Precio <= ?";
        $params[] = floatval($max);
        $types .= "d";
    }

    // Contar total de resultados
    $stmt_total = $conn->prepare("SELECT COUNT(*) FROM producto $sql_where");
    if ($types !== '') {
        $stmt_total->bind_param($types, ...$params);
    }
    $stmt_total->execute();
    $stmt_total->bind_result($total_resultados);
    $stmt_total->fetch();
    $stmt_total->close();

    $total_paginas = max(1, ceil($total_resultados / $por_pagina));
    $response['total_paginas'] = $total_paginas;

    // Consulta para obtener productos con paginación
    $sql_final = "SELECT * FROM producto $sql_where ORDER BY IdProducto DESC LIMIT ? OFFSET ?";
    
    // Agregar parámetros para LIMIT y OFFSET
    $params2 = $params;
    $types2 = $types . "ii";
    $params2[] = $por_pagina;
    $params2[] = $offset;

    $stmt = $conn->prepare($sql_final);

    // bind_param requiere referencias
    $bind_names[] = $types2;
    for ($i=0; $i < count($params2); $i++) {
        $bind_names[] = &$params2[$i];
    }

    call_user_func_array([$stmt, 'bind_param'], $bind_names);

    $stmt->execute();
    $result = $stmt->get_result();

    $productos = [];
    while ($row = $result->fetch_assoc()) {
        $productos[] = $row;
    }
    $stmt->close();

    $response['success'] = true;
    $response['data'] = $productos;

} catch (Exception $e) {
    $response['message'] = "Error: " . $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
