<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'db.php';
include 'log_helper.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("⛔ Acceso no autorizado.");
}

if (
    empty($_POST['nombre']) || empty($_POST['descripcion']) ||
    empty($_POST['precio']) || empty($_POST['cantidad']) ||
    empty($_POST['categoria']) || empty($_POST['presentacion'])
) {
    echo "<script>alert('❌ Todos los campos son obligatorios.'); history.back();</script>";
    exit;
}

$nombre = htmlspecialchars(trim($_POST['nombre']));
$descripcion = htmlspecialchars(trim($_POST['descripcion']));
$precio = floatval($_POST['precio']);
$cantidad = intval($_POST['cantidad']);
$categoria = htmlspecialchars(trim($_POST['categoria']));
$presentacion = htmlspecialchars(trim($_POST['presentacion']));

if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
    $imagen_tmp = $_FILES['imagen']['tmp_name'];
    $tipo = mime_content_type($imagen_tmp);
    $tamano = $_FILES['imagen']['size'];
    $permitidos = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $maximo = 2 * 1024 * 1024;

    if (!in_array($tipo, $permitidos)) {
        echo "<script>alert('❌ Tipo de imagen no permitido. Usa JPG, PNG, GIF o WEBP.'); history.back();</script>";
        exit;
    }

    if ($tamano > $maximo) {
        echo "<script>alert('❌ La imagen excede el tamaño máximo de 2MB.'); history.back();</script>";
        exit;
    }

    $ext = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
    $nombre_imagen = uniqid('perfume_') . '.' . strtolower($ext);
    $ruta_destino = __DIR__ . '/imagenes/' . $nombre_imagen;

    if (!move_uploaded_file($imagen_tmp, $ruta_destino)) {
        echo "<h2 style='color:red;'>❌ Error al mover la imagen al servidor.</h2>";
        echo "<p>Revisa permisos de la carpeta 'imagenes/' y que la ruta sea correcta.</p>";
        exit;
    }

    // Aquí la consulta corregida, sin producto_id:
    $stmt = $conn->prepare("INSERT INTO productos (Nombre, descripcion, Precio, Cantidad, Categoria, Presentacion, imagen) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if ($stmt === false) {
        die("Error en prepare(): " . htmlspecialchars($conn->error));
    }
    $stmt->bind_param("ssdisss", $nombre, $descripcion, $precio, $cantidad, $categoria, $presentacion, $nombre_imagen);

    if ($stmt->execute()) {
        $id_insertado = $stmt->insert_id;
        $usuario = $_SESSION['username'] ?? 'desconocido';
        $accion = "Agregó el producto '$nombre' con ID $id_insertado";
        registrar_log($conn, $usuario, $accion);

        echo "<script>
                alert('✅ Producto agregado correctamente.');
                setTimeout(function() {
                    window.location.href = 'listar_productos.php';
                }, 800);
              </script>";
    } else {
        echo "<h2 style='color:red;'>❌ Error al guardar en la base de datos: " . htmlspecialchars($stmt->error) . "</h2>";
        exit;
    }
    $stmt->close();
} else {
    echo "<h2 style='color:red;'>❌ Debes subir una imagen válida.</h2>";
    exit;
}

$conn->close();
?>
