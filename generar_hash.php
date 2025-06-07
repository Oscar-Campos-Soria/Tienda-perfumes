<?php
// Contraseña en texto plano
$contraseña_plana = '123';

// Generar hash seguro usando bcrypt (PASSWORD_DEFAULT)
$hash = password_hash($contraseña_plana, PASSWORD_DEFAULT);

// Mostrar el hash generado (para usarlo en la base de datos)
echo "Hash generado: " . $hash;
?>
