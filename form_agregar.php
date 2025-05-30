<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Perfume</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 40px;
        }

        .formulario {
            background: white;
            max-width: 600px;
            margin: auto;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-top: 10px;
            color: #555;
        }

        input, textarea, select {
            width: 100%;
            padding: 10px;
            margin-top: 6px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
        }

        input[type="number"] {
            -moz-appearance: textfield;
        }

        input[type="file"] {
            padding: 5px;
            font-size: 14px;
        }

        button {
            background-color: #28a745;
            color: white;
            padding: 12px;
            width: 100%;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background-color: #218838;
        }

        .back {
            text-align: center;
            margin-top: 20px;
        }

        .back a {
            color: #007bff;
            text-decoration: none;
        }

        .back a:hover {
            text-decoration: underline;
        }

        .error {
            color: red;
            text-align: center;
            margin-bottom: 10px;
        }
    </style>

    <script>
        function validarFormulario() {
            const imagen = document.getElementById('imagen').files[0];
            if (imagen) {
                const tiposPermitidos = ['image/jpeg', 'image/png', 'image/webp'];
                if (!tiposPermitidos.includes(imagen.type)) {
                    alert('Solo se permiten imágenes JPG, PNG o WEBP.');
                    return false;
                }
                if (imagen.size > 2 * 1024 * 1024) {
                    alert('La imagen no debe superar los 2 MB.');
                    return false;
                }
            }
            return true;
        }
    </script>
</head>
<body>

<div class="formulario">
    <h2>Agregar nuevo perfume</h2>

    <form action="insertar_producto.php" method="POST" enctype="multipart/form-data" onsubmit="return validarFormulario()">
        <label for="nombre">Nombre:</label>
        <input type="text" name="nombre" id="nombre" maxlength="100" required>

        <label for="descripcion">Descripción:</label>
        <textarea name="descripcion" id="descripcion" rows="4" maxlength="500" required></textarea>

        <label for="precio">Precio ($ MXN):</label>
        <input type="number" name="precio" id="precio" step="0.01" min="1" required>

        <label for="cantidad">Cantidad:</label>
        <input type="number" name="cantidad" id="cantidad" min="1" required>

        <label for="categoria">Categoría:</label>
        <select name="categoria" id="categoria" required>
            <option value="" disabled selected>Selecciona una categoría</option>
            <option value="caballero">Caballero</option>
            <option value="dama">Dama</option>
            <option value="mixto">Mixto</option>
        </select>

        <label for="presentacion">Presentación:</label>
        <select name="presentacion" id="presentacion" required>
            <option value="" disabled selected>Selecciona presentación</option>
            <option value="completo">Completo</option>
            <option value="5ml">5 ml</option>
            <option value="10ml">10 ml</option>
        </select>

        <label for="imagen">Imagen del perfume:</label>
        <input type="file" name="imagen" id="imagen" accept="image/*" required>

        <button type="submit">Guardar Perfume</button>
    </form>

    <div class="back">
        <a href="listar_productos.php">← Volver a la lista de perfumes</a>
    </div>
</div>

</body>
</html>
