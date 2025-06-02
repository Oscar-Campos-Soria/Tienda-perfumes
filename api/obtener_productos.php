<?php
header('Content-Type: application/json');
require_once '../db.php';

$response = ['success' => false, 'data' => [], 'total_paginas' => 1, 'message' => ''];

try {
    $conn->set_charset("utf8");

    // Parámetros de paginación
    $pagina = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
    $por_pagina = 9;
    $offset = ($pagina - 1) * $por_pagina;

    // Filtros
    $categoria = $_GET['categoria'] ?? '';
    $presentacion = $_GET['presentacion'] ?? '';
    $min = $_GET['min'] ?? '';
    $max = $_GET['max'] ?? '';

    // Construcción del query base
    $sql = "FROM productos WHERE 1=1";
    $params = [];
    $types = "";

    if ($categoria !== '') {
        $sql .= " AND categoria = ?";
        $params[] = $categoria;
        $types .= "s";
    }

    if ($presentacion !== '') {
        $sql .= " AND presentacion = ?";
        $params[] = $presentacion;
        $types .= "s";
    }

    if ($min !== '') {
        $sql .= " AND Precio >= ?";
        $params[] = floatval($min);
        $types .= "d";
    }

    if ($max !== '') {
        $sql .= " AND Precio <= ?";
        $params[] = floatval($max);
        $types .= "d";
    }

    // Contar total para paginación
    $stmt_total = $conn->prepare("SELECT COUNT(*) $sql");
    if ($types !== "") {
        $stmt_total->bind_param($types, ...$params);
    }
    $stmt_total->execute();
    $stmt_total->bind_result($total_resultados);
    $stmt_total->fetch();
    $stmt_total->close();

    $total_paginas = ceil($total_resultados / $por_pagina);
    $response['total_paginas'] = max(1, $total_paginas);

    // Obtener productos con límite
    $sql_final = "SELECT * $sql ORDER BY Id DESC LIMIT ? OFFSET ?";
    $params[] = $por_pagina;
    $params[] = $offset;
    $types .= "ii";

    $stmt = $conn->prepare($sql_final);
    $stmt->bind_param($types, ...$params);
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

echo json_encode($response);
