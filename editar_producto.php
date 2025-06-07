<?php 
session_start();
include 'db.php';
// include 'log_helper.php'; // Si usas triggers NO es necesario, ¡si quieres auditoría doble, lo puedes dejar!

// Solo permitir acceso a administradores
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'administrador') {
    die("Acceso denegado.");
}
if (!isset($_SESSION['id_usuario'])) {
    die("Usuario no autenticado correctamente.");
}

if (!isset($_GET['id'])) {
    die("ID no especificado.");
}

$id = intval($_GET['id']);

// Obtener producto a editar
$sql = "SELECT * FROM producto WHERE IdProducto = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$producto = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$producto) {
    die("Producto no encontrado.");
}

// Obtener opciones de presentación para el select
$presentaciones = [];
$sql_pres = "SELECT IdPresentacion, Nombre FROM presentacion ORDER BY Nombre ASC";
$res_pres = $conn->query($sql_pres);
if ($res_pres && $res_pres->num_rows > 0) {
    while ($fila = $res_pres->fetch_assoc()) {
        $presentaciones[] = $fila;
    }
}

// Procesar formulario de actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $precio = floatval($_POST['precio']);
    $cantidad = intval($_POST['cantidad']);
    $categoria = trim($_POST['categoria']);
    $presentacion = intval($_POST['presentacion']);
    $imagen = $producto['Imagen']; // Mantener la imagen anterior por defecto

    // Validar y subir nueva imagen si se envió
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
            // Eliminar imagen anterior si existe
            if (!empty($producto['Imagen']) && file_exists('imagenes/' . $producto['Imagen'])) {
                unlink('imagenes/' . $producto['Imagen']);
            }
            $imagen = $nombre_imagen;
        } else {
            die("❌ Error al mover la nueva imagen al servidor.");
        }
    }

    // Obtener el ID del usuario que está modificando (importante)
    $id_usuario_modifica = $_SESSION['id_usuario'];

    // Actualizar producto: ahora sí actualiza también el usuario y la fecha de modificación
    $sql = "UPDATE producto SET Nombre = ?, Descripcion = ?, Precio = ?, Cantidad = ?, Categoria = ?, IdPresentacion = ?, Imagen = ?, IdUsuarioModifica = ?, FechaModifica = NOW() WHERE IdProducto = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdisssii", $nombre, $descripcion, $precio, $cantidad, $categoria, $presentacion, $imagen, $id_usuario_modifica, $id);

    if ($stmt->execute()) {
        echo "<script>alert('✅ Producto actualizado correctamente.'); window.location.href = 'listar_productos.php';</script>";
        exit;
    } else {
        echo "Error al actualizar: " . $stmt->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>Editar Perfume</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 40px; }
        form {
            max-width: 600px; margin: auto; background-color: #fff;
            padding: 25px; border-radius: 8px; box-shadow: 0 0 12px rgba(0,0,0,0.1);
        }
        h2 { text-align: center; color: #333; margin-bottom: 20px; }
        label { font-weight: bold; display: block; margin-top: 10px; }
        input, textarea, select {
            width: 100%; padding: 10px; margin-top: 6px; margin-bottom: 20px;
            border: 1px solid #ccc; border-radius: 5px; font-size: 14px;
        }
        button {
            background-color: #007bff; color: white; padding: 12px; border: none;
            border-radius: 5px; cursor: pointer; width: 100%;
            font-size: 16px;
        }
        button:hover { background-color: #0056b3; }
        .volver {
            display: block; text-align: center; margin-top: 15px;
            text-decoration: none; color: #dc3545; font-weight: bold;
        }
        img {
            max-width: 150px; margin-bottom: 10px; border-radius: 8px;
            display: block;
        }
    </style>
</head>
<body>

<h2>Editar Perfume</h2>

<form method="POST" enctype="multipart/form-data">
    <label for="nombre">Nombre:</label>
    <input type="text" name="nombre" id="nombre" value="<?= htmlspecialchars($producto['Nombre']) ?>" required>

    <label for="descripcion">Descripción:</label>
    <textarea name="descripcion" id="descripcion" required><?= htmlspecialchars($producto['Descripcion']) ?></textarea>

    <label for="precio">Precio:</label>
    <input type="number" step="0.01" name="precio" id="precio" value="<?= $producto['Precio'] ?>" required>

    <label for="cantidad">Cantidad:</label>
    <input type="number" name="cantidad" id="cantidad" value="<?= $producto['Cantidad'] ?>" required>

    <label for="categoria">Categoría:</label>
    <select name="categoria" id="categoria" required>
        <option value="caballero" <?= $producto['Categoria'] === 'caballero' ? 'selected' : '' ?>>Caballero</option>
        <option value="dama" <?= $producto['Categoria'] === 'dama' ? 'selected' : '' ?>>Dama</option>
        <option value="mixto" <?= $producto['Categoria'] === 'mixto' ? 'selected' : '' ?>>Mixto</option>
    </select>

    <label for="presentacion">Presentación:</label>
    <select name="presentacion" id="presentacion" required>
        <?php foreach ($presentaciones as $p): ?>
            <option value="<?= $p['IdPresentacion'] ?>" <?= $producto['IdPresentacion'] == $p['IdPresentacion'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($p['Nombre']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label>Imagen actual:</label>
    <?php if ($producto['Imagen']): ?>
        <img src="imagenes/<?= htmlspecialchars($producto['Imagen']) ?>" alt="Imagen actual">
    <?php else: ?>
        <p>Sin imagen disponible</p>
    <?php endif; ?> 

    <label for="imagen">Cambiar imagen:</label>
    <input type="file" name="imagen" id="imagen" accept="image/*">

    <button type="submit">Guardar cambios</button>
    <a href="listar_producto.php" class="volver">Cancelar</a>
</form>

</body>
</html>
