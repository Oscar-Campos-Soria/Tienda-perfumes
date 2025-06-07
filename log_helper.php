<?php
function registrar_log($conn, $usuario, $accion, $tabla = null, $idRegistro = null, $datosAnteriores = null, $datosNuevos = null) {
    $tabla = $tabla !== null ? $tabla : '';
    $datosAnteriores = $datosAnteriores !== null ? $datosAnteriores : '';
    $datosNuevos = $datosNuevos !== null ? $datosNuevos : '';

    if ($idRegistro === null) {
        // Insertar con IdRegistro NULL
        $sql = "INSERT INTO auditoria (Tabla, IdRegistro, Accion, Usuario, Fecha, DatosAnteriores, DatosNuevos)
                VALUES (?, NULL, ?, ?, NOW(), ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log("Error preparar stmt: " . $conn->error);
            return false;
        }
        $stmt->bind_param("ssss", $tabla, $accion, $usuario, $datosAnteriores, $datosNuevos);
    } else {
        // Insertar con IdRegistro con valor entero
        $sql = "INSERT INTO auditoria (Tabla, IdRegistro, Accion, Usuario, Fecha, DatosAnteriores, DatosNuevos)
                VALUES (?, ?, ?, ?, NOW(), ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log("Error preparar stmt: " . $conn->error);
            return false;
        }
        $stmt->bind_param("sissss", $tabla, $idRegistro, $accion, $usuario, $datosAnteriores, $datosNuevos);
    }

    if (!$stmt->execute()) {
        error_log("Error ejecutar stmt: " . $stmt->error);
        $stmt->close();
        return false;
    }

    $stmt->close();
    return true;
}
?>
