<?php
// Activar reporte de errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'db.php';
include 'log_helper.php';

if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'administrador') {
    die("⛔ Acceso denegado.");
}

$id_usuario_crea = $_SESSION['id_usuario'] ?? null;
if ($id_usuario_crea === null) {
    die("⛔ Usuario no autenticado correctamente.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener datos del formulario
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $precio = floatval($_POST['precio']);
    $cantidad = intval($_POST['cantidad']);
    $categoria = trim($_POST['categoria']);
    $presentacion = intval($_POST['presentacion']);
    $id_estatus = 1; // Estatus por defecto (activo)

    // Validar imagen subida
    if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
        die("❌ Error al subir la imagen.");
    }

    $permitidos = ['image/jpeg', 'image/png', 'image/webp'];
    $tipo = mime_content_type($_FILES['imagen']['tmp_name']);
    $tamano = $_FILES['imagen']['size'];

    if (!in_array($tipo, $permitidos)) {
        die("❌ Tipo de imagen no permitido. Usa JPG, PNG o WEBP.");
    }

    if ($tamano > 2 * 1024 * 1024) {
        die("❌ La imagen supera los 2MB.");
    }

    // Procesar imagen
    $ext = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
    $nombre_imagen = uniqid('perfume_') . '.' . $ext;
    $ruta_destino = 'imagenes/' . $nombre_imagen;

    if (!move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta_destino)) {
        die("❌ Error al guardar la imagen.");
    }

    // Insertar producto, incluyendo IdUsuarioCrea
    $sql = "INSERT INTO producto 
            (Nombre, Descripcion, Precio, Cantidad, Categoria, IdPresentacion, Imagen, IdEstatus, IdUsuarioCrea) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("❌ Error al preparar la consulta: " . $conn->error);
    }

    $stmt->bind_param("ssdisssii", 
        $nombre, 
        $descripcion, 
        $precio, 
        $cantidad, 
        $categoria, 
        $presentacion, 
        $nombre_imagen, 
        $id_estatus,
        $id_usuario_crea
    );

    if ($stmt->execute()) {
        // No registrar log aquí, el trigger lo hace para INSERT
        echo "<script>alert('✅ Producto agregado correctamente.'); window.location.href = 'listar_productos.php';</script>";
    } else {
        echo "<script>alert('❌ Error al guardar producto: {$stmt->error}'); window.location.href = 'form_agregar.php';</script>";
    }

    $stmt->close();
} else {
    header('Location: form_agregar.php');
    exit;
}
