<?php
include 'db.php';  // Incluye el archivo de conexión

// Verifica si la conexión fue exitosa
if ($conn && !$conn->connect_error) {
    echo "✅ Conexión exitosa a la base de datos.";
} else {
    echo "❌ Error de conexión: " . $conn->connect_error;
}
?>
