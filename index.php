<?php
include 'db.php';

// Obtener los últimos 6 productos
$sql = "SELECT * FROM producto ORDER BY IdProducto DESC LIMIT 6";
$result = $conn->query($sql);
$productos = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $productos[] = $row;
    }
} else {
    // En caso de error en consulta
    die("Error al obtener productos: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>Inicio - Tienda de Perfumes</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f5f5f5; margin: 0; padding: 0; }
        header { background-color: #007bff; color: white; padding: 20px; text-align: center; }
        nav { background-color: #343a40; padding: 10px; text-align: center; }
        nav a {
            color: white; text-decoration: none; margin: 0 15px;
            font-weight: bold; padding: 6px 12px;
        }
        nav a:hover { background-color: #495057; border-radius: 4px; }
        .contenedor { max-width: 1200px; margin: auto; padding: 20px; }
        .productos {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        .producto {
            background: white; border-radius: 8px; padding: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
        }
        .producto img { max-width: 100%; border-radius: 5px; height: 200px; object-fit: cover; }
        .producto h3 { margin: 10px 0 5px; }
        .producto p { color: #555; flex-grow: 1; }
        .precio { font-size: 1.2em; color: #28a745; font-weight: bold; margin: 10px 0; }
        .botones {
            display: flex;
            flex-direction: column;
            gap: 8px;
            align-items: center;
            margin-top: 10px;
        }
        .boton {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            width: 100%;
            text-align: center;
            transition: background-color 0.3s ease;
        }
        .boton:hover {
            background-color: #0056b3;
        }
        .boton-secundario {
            background-color: #28a745;
        }
        .boton-secundario:hover {
            background-color: #1e7e34;
        }
        footer {
            text-align: center;
            padding: 20px;
            background-color: #343a40;
            color: white;
            margin-top: 40px;
        }
    </style>
</head>
<body>

<header>
    <h1>Bienvenido a nuestra tienda de perfumes</h1>
    <p>Explora nuestra selección exclusiva de fragancias</p>
</header>

<nav>
    <a href="login.php">Iniciar sesión</a>
    <a href="registro.php">Registrarse</a>
</nav>

<div class="contenedor">
    <h2>Últimos perfumes agregados</h2>
    <div class="productos">
        <?php if (count($productos) === 0): ?>
            <p>No hay productos disponibles en este momento.</p>
        <?php else: ?>
            <?php foreach ($productos as $p): ?>
                <div class="producto">
                    <?php if (!empty($p['Imagen']) && file_exists("imagenes/" . $p['Imagen'])): ?>
                        <img src="imagenes/<?= htmlspecialchars($p['Imagen']) ?>" alt="<?= htmlspecialchars($p['Nombre']) ?>">
                    <?php else: ?>
                        <img src="imagenes/no-image.png" alt="Sin imagen">
                    <?php endif; ?>
                    <h3><?= htmlspecialchars($p['Nombre']) ?></h3>
                    <p><?= htmlspecialchars($p['Descripcion']) ?></p>
                    <div class="precio">$<?= number_format($p['Precio'], 2) ?></div>
                    <div class="botones">
                        <a href="login.php" class="boton">Comprar</a>
                        <a href="registro.php" class="boton boton-secundario">Crear cuenta</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<footer>
    <p>&copy; <?= date('Y') ?> Tienda de Perfumes. Todos los derechos reservados.</p>
</footer>

</body>
</html>
