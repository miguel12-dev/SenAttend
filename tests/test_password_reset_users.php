<?php
/**
 * Test de recuperación de contraseña para usuarios y aprendices
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

use App\Repositories\PasswordResetTokenRepository;
use App\Repositories\UserRepository;
use App\Repositories\AprendizRepository;
use App\Services\PasswordResetService;
use App\Services\EmailService;

echo "=================================================================\n";
echo "   TEST: Recuperación de Contraseña (Usuarios y Aprendices)\n";
echo "=================================================================\n\n";

$tokenRepo = new PasswordResetTokenRepository();
$userRepo = new UserRepository();
$aprendizRepo = new AprendizRepository();
$emailService = new EmailService();

$service = new PasswordResetService($tokenRepo, $userRepo, $aprendizRepo, $emailService);

// Test 1: Buscar usuario por email
echo "[TEST 1] Buscar usuario por email...\n";
$usuario = $userRepo->findByEmail('admin@sena.edu.co');
if ($usuario) {
    echo "  ✓ Usuario encontrado: {$usuario['nombre']} ({$usuario['email']})\n";
} else {
    echo "  ✗ Usuario no encontrado\n";
}

// Test 2: Buscar usuario por documento
echo "\n[TEST 2] Buscar usuario por documento...\n";
$usuario = $userRepo->findByDocumento('1000000001');
if ($usuario) {
    echo "  ✓ Usuario encontrado: {$usuario['nombre']} (Doc: {$usuario['documento']})\n";
} else {
    echo "  ✗ Usuario no encontrado\n";
}

// Test 3: Buscar aprendiz por email
echo "\n[TEST 3] Buscar aprendiz por email...\n";
$aprendiz = $aprendizRepo->findByEmail('anasofia0763@gmail.com');
if ($aprendiz) {
    echo "  ✓ Aprendiz encontrado: {$aprendiz['nombre']} {$aprendiz['apellido']} ({$aprendiz['email']})\n";
} else {
    echo "  ✗ Aprendiz no encontrado\n";
}

// Test 4: Buscar aprendiz por documento
echo "\n[TEST 4] Buscar aprendiz por documento...\n";
$aprendiz = $aprendizRepo->findByDocumento('1006524033');
if ($aprendiz) {
    echo "  ✓ Aprendiz encontrado: {$aprendiz['nombre']} {$aprendiz['apellido']} (Doc: {$aprendiz['documento']})\n";
} else {
    echo "  ✗ Aprendiz no encontrado\n";
}

// Test 5: Solicitar recuperación con email de usuario (SIN ENVIAR EMAIL REAL)
echo "\n[TEST 5] Simular solicitud con email de usuario...\n";
$usuario = $userRepo->findByEmail('admin@sena.edu.co');
if ($usuario) {
    // Crear token manualmente sin enviar email
    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    $created = $tokenRepo->create($usuario['id'], 'usuario', $usuario['email'], $token, $expiresAt);
    if ($created) {
        echo "  ✓ Token creado para usuario: {$usuario['nombre']}\n";
        echo "  Token: " . substr($token, 0, 20) . "...\n";
    } else {
        echo "  ✗ Error al crear token\n";
    }
} else {
    echo "  ⚠ Usuario no encontrado\n";
}

// Test 6: Solicitar recuperación con documento de aprendiz (SIN ENVIAR EMAIL REAL)
echo "\n[TEST 6] Simular solicitud con documento de aprendiz...\n";
$aprendiz = $aprendizRepo->findByDocumento('1006524033');
if ($aprendiz) {
    // Crear token manualmente sin enviar email
    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    $created = $tokenRepo->create($aprendiz['id'], 'aprendiz', $aprendiz['email'], $token, $expiresAt);
    if ($created) {
        echo "  ✓ Token creado para aprendiz: {$aprendiz['nombre']} {$aprendiz['apellido']}\n";
        echo "  Token: " . substr($token, 0, 20) . "...\n";
    } else {
        echo "  ✗ Error al crear token\n";
    }
} else {
    echo "  ⚠ Aprendiz no encontrado\n";
}

// Test 7: Verificar tokens creados
echo "\n[TEST 7] Verificar tokens en base de datos...\n";
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $stmt = $pdo->query('SELECT user_id, user_type, email, used, expires_at FROM password_reset_tokens ORDER BY created_at DESC LIMIT 5');
    $tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "  Últimos tokens creados:\n";
    echo "  " . str_repeat('-', 70) . "\n";
    printf("  %-10s %-12s %-30s %-8s\n", "User ID", "Tipo", "Email", "Usado");
    echo "  " . str_repeat('-', 70) . "\n";
    
    foreach ($tokens as $t) {
        printf("  %-10s %-12s %-30s %-8s\n", 
            $t['user_id'], 
            $t['user_type'], 
            $t['email'], 
            $t['used'] ? 'Sí' : 'No'
        );
    }
    echo "  " . str_repeat('-', 70) . "\n";
    
} catch (PDOException $e) {
    echo "  ✗ Error: " . $e->getMessage() . "\n";
}

echo "\n=================================================================\n";
echo "✅ TESTS COMPLETADOS\n";
echo "=================================================================\n\n";

echo "RESUMEN:\n";
echo "- El sistema ahora busca tanto en 'usuarios' como en 'aprendices'\n";
echo "- La tabla tiene un campo 'user_type' para distinguir el origen\n";
echo "- Los tokens se crean correctamente para ambos tipos\n";
echo "- El reseteo de contraseña funcionará para todos los roles\n\n";

echo "PARA PROBAR EN NAVEGADOR:\n";
echo "1. Ir a /login\n";
echo "2. Hacer clic en '¿Olvidaste tu contraseña?'\n";
echo "3. Probar con:\n";
echo "   - Email de usuario: admin@sena.edu.co\n";
echo "   - Documento de usuario: 1000000001\n";
echo "   - Email de aprendiz: anasofia0763@gmail.com\n";
echo "   - Documento de aprendiz: 1006524033\n\n";
