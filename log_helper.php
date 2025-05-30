<?php
function registrar_log($conn, $usuario, $accion) {
    $stmt = $conn->prepare("INSERT INTO logs (usuario, accion) VALUES (?, ?)");
    $stmt->bind_param("ss", $usuario, $accion);
    $stmt->execute();
    var_dump($stmt->error);
    $stmt->close();
}
?>
