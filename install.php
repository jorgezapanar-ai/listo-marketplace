<?php
declare(strict_types=1);

define('BASE_PATH', __DIR__);

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

ini_set('session.use_strict_mode', '1');
session_name('listo_installer');
$installerPath = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/install.php')));
$installerPath = $installerPath === '/' ? '/' : rtrim($installerPath, '/') . '/';
session_set_cookie_params(['path' => $installerPath, 'secure' => $isHttps, 'httponly' => true, 'samesite' => 'Strict']);
session_start();

header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store, private');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
header("Content-Security-Policy: default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'; object-src 'none'; img-src 'self' data:; style-src 'self'");

$lockFile = BASE_PATH . '/storage/installed.lock';
$configFile = BASE_PATH . '/app/config.php';
$installed = is_file($lockFile) || is_file($configFile);
$errors = [];
$success = false;

function installer_e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function installer_phone(string $phone): ?string
{
    $digits = preg_replace('/\D+/', '', $phone) ?? '';
    if (strlen($digits) === 11 && str_starts_with($digits, '51')) {
        $digits = substr($digits, 2);
    }
    return strlen($digits) === 9 && str_starts_with($digits, '9') ? '+51' . $digits : null;
}

function installer_strlen(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
}

function detected_url(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $scheme = $https ? 'https' : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $directory = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/install.php')));
    return $scheme . '://' . $host . ($directory === '/' ? '' : rtrim($directory, '/'));
}

$requirementErrors = [];
if (PHP_VERSION_ID < 80100) {
    $requirementErrors[] = 'El servidor debe usar PHP 8.1 o superior.';
}
if (!extension_loaded('pdo_mysql')) {
    $requirementErrors[] = 'Activa la extensión PHP pdo_mysql antes de instalar.';
}
if (!extension_loaded('mbstring')) {
    $requirementErrors[] = 'Activa la extensión PHP mbstring antes de instalar.';
}
if (!is_dir(BASE_PATH . '/app') || !is_writable(BASE_PATH . '/app')) {
    $requirementErrors[] = 'PHP necesita permiso de escritura temporal en la carpeta app.';
}
if (!is_dir(BASE_PATH . '/storage') || !is_writable(BASE_PATH . '/storage')) {
    $requirementErrors[] = 'PHP necesita permiso de escritura en la carpeta storage.';
}
$requirementsMet = $requirementErrors === [];
$errors = $requirementErrors;

if (empty($_SESSION['_install_csrf'])) {
    $_SESSION['_install_csrf'] = bin2hex(random_bytes(32));
}

if (!$installed && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $token = (string) ($_POST['_csrf'] ?? '');
    if (!hash_equals((string) $_SESSION['_install_csrf'], $token)) {
        $errors[] = 'La sesión del instalador venció. Recarga la página e inténtalo nuevamente.';
    }

    $siteName = trim((string) ($_POST['site_name'] ?? 'Listo'));
    $appUrl = rtrim(trim((string) ($_POST['app_url'] ?? '')), '/');
    $dbHost = trim((string) ($_POST['db_host'] ?? 'localhost'));
    $dbPort = (int) ($_POST['db_port'] ?? 3306);
    $dbName = trim((string) ($_POST['db_name'] ?? ''));
    $dbUser = trim((string) ($_POST['db_user'] ?? ''));
    $dbPassword = (string) ($_POST['db_password'] ?? '');
    $adminName = trim((string) ($_POST['admin_name'] ?? ''));
    $adminPhone = installer_phone((string) ($_POST['admin_phone'] ?? ''));
    $adminPassword = (string) ($_POST['admin_password'] ?? '');

    if ($siteName === '' || installer_strlen($siteName) > 80) {
        $errors[] = 'El nombre del sitio es obligatorio y debe tener máximo 80 caracteres.';
    }
    $urlParts = parse_url($appUrl);
    if (!filter_var($appUrl, FILTER_VALIDATE_URL)
        || !is_array($urlParts)
        || !in_array(strtolower((string) ($urlParts['scheme'] ?? '')), ['http', 'https'], true)
        || empty($urlParts['host'])
        || isset($urlParts['user'])
        || isset($urlParts['pass'])
        || isset($urlParts['query'])
        || isset($urlParts['fragment'])) {
        $errors[] = 'Ingresa una URL válida, incluyendo https://.';
    }
    if ($dbHost === '' || $dbName === '' || $dbUser === '' || $dbPort < 1 || $dbPort > 65535
        || preg_match('/[;\r\n]/', $dbHost . $dbName . $dbUser) === 1
        || strlen($dbHost) > 255 || strlen($dbName) > 128 || strlen($dbUser) > 128) {
        $errors[] = 'Completa correctamente los datos de MySQL.';
    }
    if ($adminName === '' || installer_strlen($adminName) < 3 || installer_strlen($adminName) > 120) {
        $errors[] = 'Ingresa el nombre completo del administrador.';
    }
    if ($adminPhone === null) {
        $errors[] = 'El celular del administrador debe tener 9 dígitos y empezar con 9.';
    }
    $adminPasswordLength = installer_strlen($adminPassword);
    if ($adminPasswordLength < 12 || $adminPasswordLength > 128 || preg_match('/[A-Za-z]/', $adminPassword) !== 1 || preg_match('/\d/', $adminPassword) !== 1) {
        $errors[] = 'La contraseña del administrador debe tener entre 12 y 128 caracteres, letras y números.';
    }

    if ($errors === []) {
        try {
            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $dbHost, $dbPort, $dbName);
            $pdo = new PDO($dsn, $dbUser, $dbPassword, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
            ]);

            $schema = file_get_contents(BASE_PATH . '/database/schema.sql');
            if ($schema === false) {
                throw new RuntimeException('No se pudo leer database/schema.sql.');
            }
            $pdo->exec($schema);

            $statement = $pdo->prepare('SELECT id, role FROM users WHERE phone = ? LIMIT 1');
            $statement->execute([$adminPhone]);
            $phoneOwner = $statement->fetch();

            $statement = $pdo->query("SELECT id, phone FROM users WHERE role = 'admin' ORDER BY id LIMIT 1");
            $existingAdmin = $statement->fetch();
            if ($phoneOwner && (!$existingAdmin || (int) $phoneOwner['id'] !== (int) $existingAdmin['id'])) {
                throw new RuntimeException('El celular administrador ya pertenece a otra cuenta de la base de datos.');
            }
            $passwordHash = password_hash($adminPassword, PASSWORD_DEFAULT);

            if ($existingAdmin) {
                $adminId = (int) $existingAdmin['id'];
                $statement = $pdo->prepare("UPDATE users SET role = 'admin', full_name = ?, phone = ?, password_hash = ?, status = 'active', phone_verified_at = NOW(), must_change_password = 0 WHERE id = ?");
                $statement->execute([$adminName, $adminPhone, $passwordHash, $adminId]);
            } else {
                $statement = $pdo->prepare("INSERT INTO users (role, full_name, phone, password_hash, status, phone_verified_at) VALUES ('admin', ?, ?, ?, 'active', NOW())");
                $statement->execute([$adminName, $adminPhone, $passwordHash]);
                $adminId = (int) $pdo->lastInsertId();
            }

            $statement = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, is_public, updated_by) VALUES ('site_name', ?, 1, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by)");
            $statement->execute([$siteName, $adminId]);

            $configuration = [
                'app' => [
                    'name' => $siteName,
                    'url' => $appUrl,
                    'env' => 'production',
                    'debug' => false,
                    'timezone' => 'America/Lima',
                    'key' => 'base64:' . base64_encode(random_bytes(48)),
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
                throw new RuntimeException('No se pudo escribir app/config.php. Revisa los permisos de la carpeta app.');
            }
            @chmod($configFile, 0640);

            if (!is_dir(BASE_PATH . '/storage/logs')) {
                mkdir(BASE_PATH . '/storage/logs', 0755, true);
            }
            if (file_put_contents($lockFile, date('c') . "\n", LOCK_EX) === false) {
                throw new RuntimeException('No se pudo bloquear el instalador. Revisa los permisos de storage.');
            }

            session_regenerate_id(true);
            $_SESSION = [];
            $success = true;
        } catch (Throwable $exception) {
            $errors[] = 'No se pudo completar la instalación: ' . $exception->getMessage();
        }
    }
}

$values = [
    'site_name' => $_POST['site_name'] ?? 'Listo',
    'app_url' => $_POST['app_url'] ?? detected_url(),
    'db_host' => $_POST['db_host'] ?? 'localhost',
    'db_port' => $_POST['db_port'] ?? '3306',
    'db_name' => $_POST['db_name'] ?? '',
    'db_user' => $_POST['db_user'] ?? '',
    'admin_name' => $_POST['admin_name'] ?? '',
    'admin_phone' => $_POST['admin_phone'] ?? '',
];
?>
<!doctype html>
<html lang="es-PE">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title>Instalación · Listo</title>
  <link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
  <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="auth-page">
  <main class="install-shell">
    <a class="brand brand-center" href="install.php" aria-label="Listo"><?= '<span class="brand-mark">●</span><strong>Listo</strong>' ?></a>

    <?php if ($installed): ?>
      <section class="auth-card center-card">
        <span class="success-mark">✓</span>
        <h1>La plataforma ya está instalada</h1>
        <p>Por seguridad, el instalador quedó bloqueado. Para reinstalar debes eliminar manualmente <code>app/config.php</code> y <code>storage/installed.lock</code> después de crear un respaldo.</p>
        <a class="btn btn-primary btn-block" href="login.php">Ir a iniciar sesión</a>
      </section>
    <?php elseif ($success): ?>
      <section class="auth-card center-card">
        <span class="success-mark">✓</span>
        <h1>Instalación completada</h1>
        <p>La base de datos, configuración y cuenta administradora quedaron creadas correctamente.</p>
        <a class="btn btn-primary btn-block" href="login.php">Ingresar como administrador</a>
      </section>
    <?php else: ?>
      <section class="auth-card install-card">
        <div class="auth-heading">
          <span class="eyebrow">Instalación segura</span>
          <h1>Configura tu marketplace</h1>
          <p>Antes de continuar, crea una base de datos MySQL y un usuario desde cPanel.</p>
        </div>

        <?php if (!$isHttps): ?>
          <div class="alert alert-warning"><strong>Recomendación:</strong> activa SSL e instala desde HTTPS para proteger las credenciales.</div>
        <?php endif; ?>
        <?php if ($errors !== []): ?>
          <div class="alert alert-danger" role="alert"><strong>Revisa lo siguiente:</strong><ul><?php foreach ($errors as $error): ?><li><?= installer_e($error) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>

        <form method="post" class="form-stack" autocomplete="off">
          <input type="hidden" name="_csrf" value="<?= installer_e($_SESSION['_install_csrf']) ?>">
          <fieldset>
            <legend>1. Sitio</legend>
            <div class="form-grid">
              <div class="field"><label for="site_name">Nombre</label><input id="site_name" name="site_name" value="<?= installer_e($values['site_name']) ?>" maxlength="80" required></div>
              <div class="field"><label for="app_url">URL pública</label><input id="app_url" name="app_url" type="url" value="<?= installer_e($values['app_url']) ?>" required><small>Incluye https:// y la subcarpeta, si existe.</small></div>
            </div>
          </fieldset>
          <fieldset>
            <legend>2. Base de datos MySQL</legend>
            <div class="form-grid">
              <div class="field"><label for="db_host">Servidor</label><input id="db_host" name="db_host" value="<?= installer_e($values['db_host']) ?>" required></div>
              <div class="field"><label for="db_port">Puerto</label><input id="db_port" name="db_port" type="number" min="1" max="65535" value="<?= installer_e($values['db_port']) ?>" required></div>
              <div class="field"><label for="db_name">Nombre de base de datos</label><input id="db_name" name="db_name" value="<?= installer_e($values['db_name']) ?>" required></div>
              <div class="field"><label for="db_user">Usuario MySQL</label><input id="db_user" name="db_user" value="<?= installer_e($values['db_user']) ?>" required></div>
              <div class="field field-full"><label for="db_password">Contraseña MySQL</label><input id="db_password" name="db_password" type="password" required></div>
            </div>
          </fieldset>
          <fieldset>
            <legend>3. Cuenta administradora</legend>
            <div class="form-grid">
              <div class="field"><label for="admin_name">Nombre completo</label><input id="admin_name" name="admin_name" value="<?= installer_e($values['admin_name']) ?>" maxlength="120" required></div>
              <div class="field"><label for="admin_phone">Celular</label><input id="admin_phone" name="admin_phone" type="tel" inputmode="numeric" value="<?= installer_e($values['admin_phone']) ?>" placeholder="999 999 999" required></div>
              <div class="field field-full"><label for="admin_password">Contraseña segura</label><input id="admin_password" name="admin_password" type="password" minlength="12" maxlength="128" autocomplete="new-password" required><small>Entre 12 y 128 caracteres, con letras y números.</small></div>
            </div>
          </fieldset>
          <button class="btn btn-primary btn-block btn-large" type="submit"<?= $requirementsMet ? '' : ' disabled aria-disabled="true"' ?>>Instalar plataforma</button>
          <p class="form-fine">Al instalar se crearán las tablas y datos iniciales. No cierres esta ventana durante el proceso.</p>
        </form>
      </section>
    <?php endif; ?>
  </main>
</body>
</html>
