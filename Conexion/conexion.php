<?php
// Configuración de la base de datos MVP
$host     = 'localhost';      // Servidor local
$db_name  = 'aulamos_mvp';    // El nombre exacto de tu Base de Datos
$username = 'root';           // Usuario por defecto en entornos locales
$password = '12345678';               // Contraseña por defecto (vacía en XAMPP)
$charset  = 'utf8mb4';        // Mismo charset utf8mb4 que declaraste en tu SQL

try {
    // Configurar la cadena de conexión (DSN)
    $dsn = "mysql:host=$host;dbname=$db_name;charset=$charset";
    
    // Configurar opciones de PDO para máxima seguridad, rendimiento y manejo de errores
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lanza excepciones si hay fallos en tus queries
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Te regresa los datos listos como arrays asociativos
        PDO::ATTR_EMULATE_PREPARES   => false,                  // Evita la emulación de consultas preparadas para mitigar inyecciones SQL
    ];

    // Crear la instancia de conexión PDO
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // Dejamos la etiqueta de PHP abierta para evitar el envío accidental de espacios en blanco
    // que puedan romper redirecciones (header('Location: ...')) en tus otros archivos.
    
} catch (PDOException $e) {
    // Si algo falla, el script se detiene y te reporta el error de manera limpia
    die("Error crítico de conexión a la base de datos Aulamos: " . $e->getMessage());
}