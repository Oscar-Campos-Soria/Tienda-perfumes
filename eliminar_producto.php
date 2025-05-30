<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


session_start();
include 'db.php';
include 'log_helper.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("⛔ Acceso denegado.");
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);

    // Verificar si el producto existe
    $stmt = $conn->prepare("SELECT imagen FROM productos WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        echo "<script>alert('❌ El producto no existe.'); window.location.href='listar_productos.php';</script>";
      exit;
    }

    $producto = $res->fetch_assoc();
    $imagen = $producto['imagen'];
    $stmt->close();

   
    // Eliminar producto
    $stmt = $conn->prepare("DELETE FROM productos WHERE id = ?");
    $stmt->bind_param("i", $id);
    $done = $stmt->execute();

    //$sql = "DELETE FROM productos
            //WHERE id = $id";
    //$done = $conn->query($sql);
    if ($done) {
        // Eliminar imagen física si existe
        if (!empty($imagen)) {
            $ruta = "imagenes/$imagen";
            if (file_exists($ruta)) unlink($ruta);
        }
       
        // Registrar log
        $usuario = $_SESSION['username'] ?? 'desconocido';
        $accion = "Eliminó el producto ID ". $id . "(Imagen: '". $imagen."')";
        registrar_log($conn, $usuario, $accion);

        echo "<script>alert('✅ Producto eliminado correctamente.'); window.location.href='listar_productos.php';</script>";
    } else {
       
        echo "<script>alert('❌ Error al eliminar producto: ".$stmt->error."'); window.location.href='listar_productos.php';</script>";
      
    }

    //print $conn->error;
    //var_dump($stmt->error);
   $stmt->close();
   //$conn->close();
} else {
    echo "<script>alert('❌ ID inválido o no recibido.'); window.location.href='listar_productos.php';</script>";
}


?>
