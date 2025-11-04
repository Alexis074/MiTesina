<?php
// Script temporal para generar el hash de la contraseña "admin"
// Ejecutar una vez y luego eliminar este archivo por seguridad

$password = 'admin';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Contraseña: " . $password . "\n";
echo "Hash: " . $hash . "\n";
echo "\n";
echo "SQL para insertar en la base de datos:\n";
echo "UPDATE usuarios SET password = '" . $hash . "' WHERE usuario = 'admin';\n";
?>

