<?php
require_once __DIR__ . '/../config/config.php';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "Estructura de tabla 'aprendices':\n";
    echo str_repeat('-', 60) . "\n";
    
    $stmt = $pdo->query('DESCRIBE aprendices');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        printf("%-20s %-30s\n", $row['Field'], $row['Type']);
    }
    
    echo "\n\nEstructura de tabla 'usuarios':\n";
    echo str_repeat('-', 60) . "\n";
    
    $stmt = $pdo->query('DESCRIBE usuarios');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        printf("%-20s %-30s\n", $row['Field'], $row['Type']);
    }
    
    echo "\n\nPrueba de búsqueda:\n";
    echo str_repeat('-', 60) . "\n";
    
    // Probar con un aprendiz
    $stmt = $pdo->query('SELECT id, documento, email, nombre FROM aprendices LIMIT 1');
    $aprendiz = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($aprendiz) {
        echo "Aprendiz de prueba:\n";
        echo "  ID: {$aprendiz['id']}\n";
        echo "  Documento: {$aprendiz['documento']}\n";
        echo "  Email: {$aprendiz['email']}\n";
        echo "  Nombre: {$aprendiz['nombre']}\n";
    } else {
        echo "No hay aprendices en la BD\n";
    }
    
    // Probar con un usuario
    $stmt = $pdo->query('SELECT id, documento, email, nombre FROM usuarios LIMIT 1');
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($usuario) {
        echo "\nUsuario de prueba:\n";
        echo "  ID: {$usuario['id']}\n";
        echo "  Documento: {$usuario['documento']}\n";
        echo "  Email: {$usuario['email']}\n";
        echo "  Nombre: {$usuario['nombre']}\n";
    } else {
        echo "\nNo hay usuarios en la BD\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
