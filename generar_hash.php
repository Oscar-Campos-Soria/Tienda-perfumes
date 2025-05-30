<?php
$contraseña_plana = '123'; // Cambia esto por la contraseña deseada
$hash = password_hash($contraseña_plana, PASSWORD_DEFAULT);
echo "Hash generado: " . $hash;
?>
