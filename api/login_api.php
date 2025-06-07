<?php
session_start();
header('Content-Type: application/json');
require_once '../db.php';

$data = json_decode(file_get_contents("php://input"), true);
$username = $data['username'] ?? '';
$password = $data['password'] ?? '';

// Tabla en singular y campos CamelCase corregidos
$stmt = $conn->prepare("SELECT IdUsuario, Rol, Contrasena FROM usuario WHERE Username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$res = $stmt->get_result();

if ($row = $res->fetch_assoc()) {
    if (password_verify($password, $row['Contrasena'])) {
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $row['Rol'];
        $_SESSION['id_usuario'] = $row['IdUsuario'];
        echo json_encode(['success' => true, 'role' => $row['Rol']]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Contraseña incorrecta']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Usuario no encontrado']);
}
?>
