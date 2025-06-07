<?php
session_start();
include 'db.php';
include 'log_helper.php'; // Función registrar_log()

// Solo administrador puede eliminar productos
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'administrador') {
    die("⛔ Acceso denegado.");
}

// Validar ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<script>alert('❌ ID inválido o no recibido.'); window.location.href='listar_producto.php';</script>";
    exit;
}

$id = intval($_GET['id']);

// Obtener producto para auditoría y manejo de imagen
$stmt = $conn->prepare("SELECT Nombre, Imagen FROM producto WHERE IdProducto = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo "<script>alert('❌ El producto no existe.'); window.location.href='listar_producto.php';</script>";
    exit;
}

$producto = $res->fetch_assoc();
$stmt->close();

$nombre = $producto['Nombre'];
$imagen = $producto['Imagen'];

// Eliminar producto de la base de datos
$stmt = $conn->prepare("DELETE FROM producto WHERE IdProducto = ?");
$stmt->bind_param("i", $id);
$success = $stmt->execute();
$stmt->close();

if ($success) {
    // Eliminar archivo físico si existe
    if (!empty($imagen)) {
        $rutaImagen = "imagenes/$imagen";
        if (file_exists($rutaImagen)) {
            unlink($rutaImagen);
        }
    }

    // Registrar acción en auditoría
    $usuario = $_SESSION['username'] ?? 'desconocido';
    $accion = "Eliminó el producto '$nombre' (ID: $id)";
    registrar_log($conn, $usuario, $accion, "producto", $id, null, null);

    echo "<script>alert('✅ Producto eliminado correctamente.'); window.location.href='listar_productos.php';</script>";
} else {
    echo "<script>alert('❌ Error al eliminar producto: ".$conn->error."'); window.location.href='listar_productos.php';</script>";
}
?>
