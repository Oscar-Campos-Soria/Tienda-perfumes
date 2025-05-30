<?php
session_start();
include 'db.php';
include 'log_helper.php'; // ✅ Asegúrate de tener este archivo para registrar logs

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Acceso denegado.");
}

if (!isset($_GET['id'])) {
    die("ID no especificado.");
}

$id = intval($_GET['id']);
$sql = "SELECT * FROM productos WHERE Id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$producto = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $precio = floatval($_POST['precio']);
    $cantidad = intval($_POST['cantidad']);
    $categoria = trim($_POST['categoria']);
    $presentacion = trim($_POST['presentacion']);
    $imagen = $producto['imagen']; // por defecto se mantiene la anterior

    // Si se sube nueva imagen
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $permitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $tamanoMaximo = 2 * 1024 * 1024; // 2MB

        $tipo = mime_content_type($_FILES['imagen']['tmp_name']);
        $tamano = $_FILES['imagen']['size'];

        if (!in_array($tipo, $permitidos)) {
            die("❌ Tipo de imagen no permitido. Usa JPG, PNG, GIF o WEBP.");
        }

        if ($tamano > $tamanoMaximo) {
            die("❌ La imagen excede el tamaño máximo de 2MB.");
        }

        $ext = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
        $nombre_imagen = uniqid('perfume_') . '.' . $ext;
        $ruta_destino = 'imagenes/' . $nombre_imagen;

        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta_destino)) {
            // Eliminar la imagen anterior si existe
            if (!empty($producto['imagen']) && file_exists('imagenes/' . $producto['imagen'])) {
                unlink('imagenes/' . $producto['imagen']);
            }
            $imagen = $nombre_imagen;
        } else {
            die("❌ Error al mover la nueva imagen al servidor.");
        }
    }

    $sql = "UPDATE productos SET Nombre = ?, descripcion = ?, Precio = ?, Cantidad = ?, Categoria = ?, Presentacion = ?, imagen = ? WHERE Id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdisssi", $nombre, $descripcion, $precio, $cantidad, $categoria, $presentacion, $imagen, $id);

    if ($stmt->execute()) {
        // ✅ Registro en logs
        $usuario = $_SESSION['username'] ?? 'desconocido';
        $accion = "Editó el perfume '$nombre' (ID: $id)";
        registrar_log($conn, $usuario, $accion);

        echo "<script>alert('✅ Producto actualizado correctamente.'); window.location.href = 'listar_productos.php';</script>";
    } else {
        echo "Error al actualizar: " . $stmt->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Perfume</title>
    <style>
        body {
            font-family: Arial;
            background-color: #f4f4f4;
            padding: 40px;
        }

        form {
            max-width: 600px;
            margin: auto;
            background-color: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 0 12px rgba(0,0,0,0.1);
        }

        h2 {
            text-align: center;
            color: #333;
        }

        label {
            font-weight: bold;
        }

        input, textarea, select {
            width: 100%;
            padding: 10px;
            margin-top: 8px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        button {
            background-color: #007bff;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
        }

        button:hover {
            background-color: #0056b3;
        }

        .volver {
            display: block;
            text-align: center;
            margin-top: 15px;
            text-decoration: none;
            color: #dc3545;
        }

        img {
            max-width: 100px;
            margin-bottom: 10px;
            display: block;
        }
    </style>
</head>
<body>

<h2>Editar Perfume</h2>
<form method="POST" enctype="multipart/form-data">
    <label>Nombre:</label>
    <input type="text" name="nombre" value="<?= htmlspecialchars($producto['Nombre']) ?>" required>

    <label>Descripción:</label>
    <textarea name="descripcion" required><?= htmlspecialchars($producto['descripcion']) ?></textarea>

    <label>Precio:</label>
    <input type="number" name="precio" step="0.01" value="<?= $producto['Precio'] ?>" required>

    <label>Cantidad:</label>
    <input type="number" name="cantidad" value="<?= $producto['Cantidad'] ?>" required>

    <label>Categoría:</label>
    <select name="categoria" required>
        <option value="caballero" <?= $producto['Categoria'] === 'caballero' ? 'selected' : '' ?>>Caballero</option>
        <option value="dama" <?= $producto['Categoria'] === 'dama' ? 'selected' : '' ?>>Dama</option>
        <option value="mixto" <?= $producto['Categoria'] === 'mixto' ? 'selected' : '' ?>>Mixto</option>
    </select>

    <label>Presentación:</label>
    <select name="presentacion" required>
        <option value="completo" <?= $producto['Presentacion'] === 'completo' ? 'selected' : '' ?>>Completo</option>
        <option value="5ml" <?= $producto['Presentacion'] === '5ml' ? 'selected' : '' ?>>5 ml</option>
        <option value="10ml" <?= $producto['Presentacion'] === '10ml' ? 'selected' : '' ?>>10 ml</option>
    </select>

    <label>Imagen actual:</label>
    <?php if ($producto['imagen']): ?>
        <img src="imagenes/<?= htmlspecialchars($producto['imagen']) ?>" alt="Imagen actual">
    <?php else: ?>
        <p>Sin imagen</p>
    <?php endif; ?>

    <label>¿Cambiar imagen?</label>
    <input type="file" name="imagen" accept="image/*">

    <button type="submit">Guardar cambios</button>
    <a href="listar_productos.php" class="volver">Cancelar</a>
</form>

</body>
</html>
