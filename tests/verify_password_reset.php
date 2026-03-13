<?php
/**
 * Script de verificación del sistema de recuperación de contraseña
 */

echo "=================================================================\n";
echo "   VERIFICACIÓN: Sistema de Recuperación de Contraseña\n";
echo "=================================================================\n\n";

$errors = [];
$warnings = [];
$success = [];

// 1. Verificar archivos del repositorio
echo "[1/9] Verificando PasswordResetTokenRepository...\n";
$repoFile = __DIR__ . '/../src/Repositories/PasswordResetTokenRepository.php';
if (file_exists($repoFile)) {
    $success[] = "✓ PasswordResetTokenRepository existe";
    require_once $repoFile;
    if (class_exists('App\Repositories\PasswordResetTokenRepository')) {
        $success[] = "✓ Clase PasswordResetTokenRepository cargada";
    }
} else {
    $errors[] = "✗ PasswordResetTokenRepository no encontrado";
}

// 2. Verificar servicio
echo "[2/9] Verificando PasswordResetService...\n";
$serviceFile = __DIR__ . '/../src/Services/PasswordResetService.php';
if (file_exists($serviceFile)) {
    $success[] = "✓ PasswordResetService existe";
} else {
    $errors[] = "✗ PasswordResetService no encontrado";
}

// 3. Verificar controlador
echo "[3/9] Verificando PasswordResetController...\n";
$controllerFile = __DIR__ . '/../src/Controllers/PasswordResetController.php';
if (file_exists($controllerFile)) {
    $success[] = "✓ PasswordResetController existe";
} else {
    $errors[] = "✗ PasswordResetController no encontrado";
}

// 4. Verificar vistas
echo "[4/9] Verificando vistas...\n";
$forgotView = __DIR__ . '/../views/auth/forgot-password.php';
$resetView = __DIR__ . '/../views/auth/reset-password.php';

if (file_exists($forgotView)) {
    $success[] = "✓ Vista forgot-password.php existe";
} else {
    $errors[] = "✗ Vista forgot-password.php no encontrada";
}

if (file_exists($resetView)) {
    $success[] = "✓ Vista reset-password.php existe";
} else {
    $errors[] = "✗ Vista reset-password.php no encontrada";
}

// 5. Verificar actualización de login
echo "[5/9] Verificando actualización de login...\n";
$loginView = __DIR__ . '/../views/auth/login.php';
if (file_exists($loginView)) {
    $content = file_get_contents($loginView);
    if (strpos($content, '/password/forgot') !== false) {
        $success[] = "✓ Enlace de recuperación añadido en login";
    } else {
        $errors[] = "✗ Enlace de recuperación no encontrado en login";
    }
}

// 6. Verificar migración
echo "[6/9] Verificando migración...\n";
$migrationFile = __DIR__ . '/../database/migrations/012_create_password_reset_tokens_table.sql';
if (file_exists($migrationFile)) {
    $success[] = "✓ Archivo de migración existe";
} else {
    $errors[] = "✗ Archivo de migración no encontrado";
}

// 7. Verificar tabla en BD
echo "[7/9] Verificando tabla en base de datos...\n";
require_once __DIR__ . '/../config/config.php';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $stmt = $pdo->query("SHOW TABLES LIKE 'password_reset_tokens'");
    if ($stmt->fetch()) {
        $success[] = "✓ Tabla password_reset_tokens existe en BD";
        
        // Verificar estructura
        $stmt = $pdo->query("DESCRIBE password_reset_tokens");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $requiredColumns = ['id', 'user_id', 'user_type', 'token', 'email', 'expires_at', 'used'];
        $missingColumns = array_diff($requiredColumns, $columns);
        
        if (empty($missingColumns)) {
            $success[] = "✓ Estructura de tabla correcta";
        } else {
            $errors[] = "✗ Faltan columnas: " . implode(', ', $missingColumns);
        }
    } else {
        $warnings[] = "⚠ Tabla password_reset_tokens no existe (ejecutar migración)";
    }
} catch (PDOException $e) {
    $errors[] = "✗ Error de conexión a BD: " . $e->getMessage();
}

// 8. Verificar extensión de EmailService
echo "[8/9] Verificando EmailService...\n";
$emailServiceFile = __DIR__ . '/../src/Services/EmailService.php';
if (file_exists($emailServiceFile)) {
    $content = file_get_contents($emailServiceFile);
    if (strpos($content, 'enviarTokenRecuperacion') !== false) {
        $success[] = "✓ Método enviarTokenRecuperacion añadido a EmailService";
    } else {
        $errors[] = "✗ Método enviarTokenRecuperacion no encontrado en EmailService";
    }
}

// 9. Verificar rutas en index.php
echo "[9/9] Verificando rutas...\n";
$indexFile = __DIR__ . '/../public/index.php';
if (file_exists($indexFile)) {
    $content = file_get_contents($indexFile);
    if (strpos($content, "'/password/forgot'") !== false && 
        strpos($content, "'/password/reset'") !== false &&
        strpos($content, 'PasswordResetController') !== false) {
        $success[] = "✓ Rutas de recuperación registradas en index.php";
    } else {
        $errors[] = "✗ Rutas de recuperación no encontradas en index.php";
    }
}

// Resumen
echo "\n";
echo "=================================================================\n";
echo "                         RESUMEN\n";
echo "=================================================================\n\n";

if (!empty($success)) {
    echo "ÉXITOS (" . count($success) . "):\n";
    foreach ($success as $msg) {
        echo "  $msg\n";
    }
    echo "\n";
}

if (!empty($warnings)) {
    echo "ADVERTENCIAS (" . count($warnings) . "):\n";
    foreach ($warnings as $msg) {
        echo "  $msg\n";
    }
    echo "\n";
}

if (!empty($errors)) {
    echo "ERRORES (" . count($errors) . "):\n";
    foreach ($errors as $msg) {
        echo "  $msg\n";
    }
    echo "\n";
    exit(1);
}

echo "=================================================================\n";
echo "✓ SISTEMA DE RECUPERACIÓN DE CONTRASEÑA CONFIGURADO CORRECTAMENTE\n";
echo "=================================================================\n\n";

echo "PRÓXIMOS PASOS:\n";
echo "1. Si la tabla no existe, ejecutar: php database/migrations/run_password_reset_migration.php\n";
echo "2. Verificar configuración SMTP en .env\n";
echo "3. Probar accediendo a /login y hacer clic en '¿Olvidaste tu contraseña?'\n\n";

exit(0);
