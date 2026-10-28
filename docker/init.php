<?php
declare(strict_types=1);

/**
 * Inicialización idempotente para el entorno Docker de pruebas.
 *
 * - Espera a que MariaDB esté disponible.
 * - Importa database/schema.sql (CREATE TABLE IF NOT EXISTS + INSERT IGNORE).
 * - Crea o actualiza la cuenta administradora desde variables de entorno.
 * - Genera app/config.php y storage/installed.lock.
 * - Opcionalmente crea datos demo con SEED_DEMO=1.
 *
 * Solo debe ejecutarse dentro del contenedor. Nunca se expone por HTTP.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Este script solo puede ejecutarse por CLI.\n");
}

$basePath = dirname(__DIR__);
$configFile = $basePath . '/app/config.php';
$lockFile = $basePath . '/storage/installed.lock';
$keyFile = $basePath . '/storage/app.key';

function env_value(string $key, string $default = ''): string
{
    $value = getenv($key);
    return ($value === false || trim($value) === '') ? $default : trim($value);
}

function env_flag(string $key, bool $default = false): bool
{
    $value = strtolower(env_value($key, $default ? '1' : '0'));
    return in_array($value, ['1', 'true', 'yes', 'on'], true);
}

/**
 * Misma normalización que app/helpers.php: guarda celulares como +51XXXXXXXXX.
 */
function normalize_seed_phone(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone) ?? '';
    if (strlen($digits) === 11 && str_starts_with($digits, '51')) {
        $digits = substr($digits, 2);
    }
    if (strlen($digits) !== 9 || !str_starts_with($digits, '9')) {
        throw new InvalidArgumentException("Celular inválido para el entorno demo: {$phone}");
    }
    return '+51' . $digits;
}

$dbHost = env_value('DB_HOST', 'db');
$dbPort = (int) env_value('DB_PORT', '3306');
$dbName = env_value('DB_NAME', 'listo');
$dbUser = env_value('DB_USER', 'listo');
$dbPassword = env_value('DB_PASSWORD', 'listo_dev_password');
$appUrl = rtrim(env_value('APP_URL', 'http://localhost:8080'), '/');

$adminName = env_value('ADMIN_NAME', 'Administrador Listo');
$adminPhone = env_value('ADMIN_PHONE', '999111222');
$adminPassword = env_value('ADMIN_PASSWORD', 'AdminListo2026');
$supportWhatsapp = env_value('SUPPORT_WHATSAPP', '+51999000111');
$seedDemo = env_flag('SEED_DEMO', true);

$dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $dbHost, $dbPort, $dbName);

$pdo = null;
$lastError = null;
$attempts = 45;
for ($attempt = 1; $attempt <= $attempts; $attempt++) {
    try {
        $pdo = new PDO($dsn, $dbUser, $dbPassword, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
        ]);
        break;
    } catch (PDOException $exception) {
        $lastError = $exception->getMessage();
        fwrite(STDERR, sprintf("[init] Base de datos no disponible (%d/%d): %s\n", $attempt, $attempts, $lastError));
        sleep(2);
    }
}

if (!$pdo instanceof PDO) {
    fwrite(STDERR, "[init] No se pudo conectar a la base de datos tras {$attempts} intentos.\n");
    fwrite(STDERR, "[init] Último error: " . ($lastError ?? 'desconocido') . "\n");
    exit(1);
}

/**
 * Crea el usuario si no existe o actualiza su contraseña y rol.
 */
function upsert_user(PDO $pdo, string $role, string $name, string $phone, string $password): int
{
    $phone = normalize_seed_phone($phone);
    $statement = $pdo->prepare('SELECT id FROM users WHERE phone = ? LIMIT 1');
    $statement->execute([$phone]);
    $existingId = $statement->fetchColumn();
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    if ($existingId !== false) {
        $update = $pdo->prepare("UPDATE users SET role = ?, full_name = ?, password_hash = ?, status = 'active', phone_verified_at = COALESCE(phone_verified_at, NOW()), must_change_password = 0 WHERE id = ?");
        $update->execute([$role, $name, $passwordHash, (int) $existingId]);
        return (int) $existingId;
    }

    $insert = $pdo->prepare("INSERT INTO users (role, full_name, phone, password_hash, status, phone_verified_at) VALUES (?, ?, ?, ?, 'active', NOW())");
    $insert->execute([$role, $name, $phone, $passwordHash]);
    return (int) $pdo->lastInsertId();
}

try {
    fwrite(STDOUT, "[init] Importando esquema base...\n");
    $schema = file_get_contents($basePath . '/database/schema.sql');
    if ($schema === false) {
        throw new RuntimeException('No se pudo leer database/schema.sql.');
    }
    $pdo->exec($schema);

    fwrite(STDOUT, "[init] Configurando administrador...\n");
    $adminId = upsert_user($pdo, 'admin', $adminName, $adminPhone, $adminPassword);

    $settings = $pdo->prepare('INSERT INTO settings (setting_key, setting_value, is_public, updated_by) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by)');
    $settings->execute(['site_name', 'Listo', 1, $adminId]);
    $settings->execute(['marketplace_city', 'Lima Metropolitana y Callao', 1, $adminId]);

    if ($supportWhatsapp !== '') {
        $settings->execute(['support_whatsapp', normalize_seed_phone($supportWhatsapp), 1, $adminId]);
    }

    if ($seedDemo) {
        fwrite(STDOUT, "[init] Cargando datos demo...\n");

        $clientId = upsert_user($pdo, 'client', 'Cliente Demo', '999222333', 'ClienteListo2026');
        $providerId = upsert_user($pdo, 'provider', 'Proveedor Demo', '999333444', 'ProveedorListo2026');
        $pendingProviderId = upsert_user($pdo, 'provider', 'Proveedor Pendiente', '999444555', 'ProveedorPendiente2026');

        // El perfil se inserta una sola vez para no revertir cambios hechos durante las pruebas.
        $providerProfile = $pdo->prepare("INSERT IGNORE INTO provider_profiles (user_id, headline, bio, years_experience, availability_text, verification_status, verified_at, verified_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $providerProfile->execute([
            $providerId,
            'Electricista y gasfitero certificado',
            'Atención de urgencias, instalaciones eléctricas y reparaciones de gasfitería en Lima Metropolitana.',
            8,
            'Lunes a sábado, 8:00 a 19:00',
            'verified',
            date('Y-m-d H:i:s'),
            $adminId,
        ]);
        $providerProfile->execute([
            $pendingProviderId,
            'Técnico en instalaciones',
            'Perfil de ejemplo pendiente de revisión administrativa.',
            3,
            'Fines de semana',
            'pending',
            null,
            null,
        ]);

        $categoryIds = [];
        foreach (['electricidad', 'gasfiteria'] as $slug) {
            $statement = $pdo->prepare('SELECT id FROM categories WHERE slug = ?');
            $statement->execute([$slug]);
            $categoryIds[] = (int) $statement->fetchColumn();
        }
        $districtIds = [];
        foreach (['Miraflores', 'San Isidro'] as $name) {
            $statement = $pdo->prepare('SELECT id FROM districts WHERE name = ?');
            $statement->execute([$name]);
            $districtIds[] = (int) $statement->fetchColumn();
        }
        $categoryIds = array_values(array_filter($categoryIds));
        $districtIds = array_values(array_filter($districtIds));

        if ($categoryIds !== [] && $districtIds !== []) {
            $linkCategory = $pdo->prepare('INSERT IGNORE INTO provider_categories (provider_id, category_id) VALUES (?, ?)');
            foreach ($categoryIds as $categoryId) {
                $linkCategory->execute([$providerId, $categoryId]);
            }
            $linkDistrict = $pdo->prepare('INSERT IGNORE INTO provider_districts (provider_id, district_id) VALUES (?, ?)');
            foreach ($districtIds as $districtId) {
                $linkDistrict->execute([$providerId, $districtId]);
            }

            $electricityId = $categoryIds[0];
            $gasfiteriaId = $categoryIds[1] ?? $categoryIds[0];
            $mirafloresId = $districtIds[0];
            $sanIsidroId = $districtIds[1] ?? $districtIds[0];

            $findRequest = $pdo->prepare('SELECT id FROM service_requests WHERE client_id = ? AND title = ? LIMIT 1');

            $findRequest->execute([$clientId, 'Instalación de tomacorrientes']);
            $openRequestId = (int) ($findRequest->fetchColumn() ?: 0);
            if ($openRequestId === 0) {
                $insertRequest = $pdo->prepare("INSERT INTO service_requests (client_id, category_id, district_id, title, description, preferred_date, priority, budget_min, budget_max, location_reference, status) VALUES (?, ?, ?, ?, ?, DATE_ADD(CURDATE(), INTERVAL 3 DAY), 'soon', 150, 250, 'Frente al parque', 'open')");
                $insertRequest->execute([
                    $clientId,
                    $electricityId,
                    $mirafloresId,
                    'Instalación de tomacorrientes',
                    'Necesito instalar dos tomacorrientes dobles en la sala y revisar la carga del tablero.',
                ]);
                $openRequestId = (int) $pdo->lastInsertId();
            }

            $insertProposal = $pdo->prepare('INSERT IGNORE INTO proposals (request_id, provider_id, message, price, available_date, status) VALUES (?, ?, ?, ?, DATE_ADD(CURDATE(), INTERVAL 2 DAY), ?)');
            $insertProposal->execute([
                $openRequestId,
                $providerId,
                'Puedo realizar la instalación con materiales incluidos y dejar prueba de funcionamiento.',
                180,
                'pending',
            ]);

            $findRequest->execute([$clientId, 'Reparación de fuga en el baño']);
            $completedRequestId = (int) ($findRequest->fetchColumn() ?: 0);
            if ($completedRequestId === 0) {
                $insertRequest = $pdo->prepare("INSERT INTO service_requests (client_id, category_id, district_id, title, description, preferred_date, priority, budget_min, budget_max, location_reference, status, completed_at) VALUES (?, ?, ?, ?, ?, DATE_SUB(CURDATE(), INTERVAL 7 DAY), 'soon', 200, 300, 'Edificio azul, piso 4', 'completed', DATE_SUB(NOW(), INTERVAL 5 DAY))");
                $insertRequest->execute([
                    $clientId,
                    $gasfiteriaId,
                    $sanIsidroId,
                    'Reparación de fuga en el baño',
                    'Fuga bajo el lavadero que ya afectó el mueble. Se necesita revisión y cambio de conexiones.',
                ]);
                $completedRequestId = (int) $pdo->lastInsertId();
            }

            $insertProposal->execute([
                $completedRequestId,
                $providerId,
                'Se reemplazó la conexión y se probó la presión del sistema sin fugas.',
                220,
                'accepted',
            ]);

            $proposalId = $pdo->prepare('SELECT id FROM proposals WHERE request_id = ? AND provider_id = ? LIMIT 1');
            $proposalId->execute([$completedRequestId, $providerId]);
            $acceptedProposalId = (int) ($proposalId->fetchColumn() ?: 0);
            if ($acceptedProposalId > 0) {
                $pdo->prepare('UPDATE service_requests SET selected_proposal_id = COALESCE(selected_proposal_id, ?) WHERE id = ?')
                    ->execute([$acceptedProposalId, $completedRequestId]);
            }

            $insertReview = $pdo->prepare("INSERT IGNORE INTO reviews (request_id, client_id, provider_id, rating, comment, status) VALUES (?, ?, ?, 5, ?, 'published')");
            $insertReview->execute([
                $completedRequestId,
                $clientId,
                $providerId,
                'Trabajo ordenado, puntual y explicó cada paso. Recomendado.',
            ]);
        } else {
            fwrite(STDERR, "[init] Aviso: no se encontraron categorías o distritos para los datos demo.\n");
        }
    }

    fwrite(STDOUT, "[init] Generando configuración...\n");

    if (!is_dir($basePath . '/storage/logs')) {
        mkdir($basePath . '/storage/logs', 0755, true);
    }

    $appKey = '';
    if (is_file($keyFile)) {
        $appKey = trim((string) file_get_contents($keyFile));
    }
    if ($appKey === '') {
        $appKey = 'base64:' . base64_encode(random_bytes(48));
        if (file_put_contents($keyFile, $appKey . "\n", LOCK_EX) === false) {
            throw new RuntimeException('No se pudo escribir storage/app.key.');
        }
        @chmod($keyFile, 0640);
    }

    $configuration = [
        'app' => [
            'name' => 'Listo',
            'url' => $appUrl,
            'env' => 'local',
            'debug' => false,
            'timezone' => 'America/Lima',
            'key' => $appKey,
        ],
        'database' => [
            'host' => $dbHost,
            'port' => $dbPort,
            'name' => $dbName,
            'user' => $dbUser,
            'password' => $dbPassword,
            'charset' => 'utf8mb4',
        ],
        'session' => [
            'name' => 'listo_session',
            'lifetime' => 7200,
        ],
    ];

    $configContents = "<?php\ndeclare(strict_types=1);\n\nreturn " . var_export($configuration, true) . ";\n";
    $temporaryConfig = $configFile . '.tmp';
    if (file_put_contents($temporaryConfig, $configContents, LOCK_EX) === false || !rename($temporaryConfig, $configFile)) {
        throw new RuntimeException('No se pudo escribir app/config.php.');
    }
    @chmod($configFile, 0640);

    if (file_put_contents($lockFile, date('c') . "\n", LOCK_EX) === false) {
        throw new RuntimeException('No se pudo escribir storage/installed.lock.');
    }

    fwrite(STDOUT, "[init] Listo. Entorno disponible en {$appUrl}\n");
    fwrite(STDOUT, "[init] Admin: {$adminPhone} / {$adminPassword}\n");
    if ($seedDemo) {
        fwrite(STDOUT, "[init] Demo cliente: 999222333 / ClienteListo2026\n");
        fwrite(STDOUT, "[init] Demo proveedor: 999333444 / ProveedorListo2026\n");
        fwrite(STDOUT, "[init] Demo proveedor pendiente: 999444555 / ProveedorPendiente2026\n");
    }
} catch (Throwable $exception) {
    fwrite(STDERR, '[init] Error: ' . $exception->getMessage() . "\n");
    exit(1);
}
