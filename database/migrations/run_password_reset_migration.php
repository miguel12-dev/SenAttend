<?php
/**
 * Script para ejecutar la migración de password_reset_tokens
 */

require_once __DIR__ . '/../../config/config.php';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "Ejecutando migración: 012_create_password_reset_tokens_table\n";
    echo str_repeat('-', 80) . "\n";

    $stmt = $pdo->query("SHOW TABLES LIKE 'password_reset_tokens'");
    $exists = $stmt->fetch();

    if ($exists) {
        echo "⚠ La tabla 'password_reset_tokens' ya existe. Eliminando...\n";
        $pdo->exec("DROP TABLE IF EXISTS password_reset_tokens");
        echo "✓ Tabla anterior eliminada.\n";
    }

    echo "Creando tabla password_reset_tokens...\n";
    
    $sql = file_get_contents(__DIR__ . '/012_create_password_reset_tokens_table.sql');
    $pdo->exec($sql);

    echo "✓ Migración completada exitosamente!\n";
    echo str_repeat('-', 80) . "\n";
    
    $stmt = $pdo->query("DESCRIBE password_reset_tokens");
    $columns = $stmt->fetchAll();
    
    echo "\nEstructura de la tabla:\n";
    echo str_repeat('-', 80) . "\n";
    printf("%-20s %-30s %-10s\n", "Campo", "Tipo", "Nulo");
    echo str_repeat('-', 80) . "\n";
    
    foreach ($columns as $column) {
        printf("%-20s %-30s %-10s\n",
            $column['Field'],
            $column['Type'],
            $column['Null']
        );
    }
    echo str_repeat('-', 80) . "\n";
    
    echo "\n✅ La tabla ahora soporta tanto usuarios como aprendices mediante el campo 'user_type'\n\n";
    
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
