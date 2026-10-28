<?php
declare(strict_types=1);

function config(?string $path = null, mixed $default = null): mixed
{
    $config = $GLOBALS['app_config'] ?? [];
    if ($path === null || $path === '') {
        return $config;
    }

    $value = $config;
    foreach (explode('.', $path) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }

    return $value;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    $base = rtrim((string) config('app.url', ''), '/');
    $path = ltrim($path, '/');
    return $path === '' ? $base . '/' : $base . '/' . $path;
}

function current_url(): string
{
    $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    $requestPath = (string) (parse_url($requestUri, PHP_URL_PATH) ?: '/');
    $basePath = rtrim((string) (parse_url((string) config('app.url', ''), PHP_URL_PATH) ?: ''), '/');

    if ($basePath !== '' && ($requestPath === $basePath || str_starts_with($requestPath, $basePath . '/'))) {
        $requestPath = substr($requestPath, strlen($basePath));
    }

    $query = (string) (parse_url($requestUri, PHP_URL_QUERY) ?? '');
    return url(ltrim($requestPath, '/')) . ($query !== '' ? '?' . $query : '');
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

function redirect(string $path, int $status = 302): never
{
    $destination = preg_match('/^https?:\/\//i', $path) ? $path : url($path);
    header('Location: ' . $destination, true, $status);
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = (string) ($_POST['_csrf'] ?? '');
    if ($token === '' || !hash_equals((string) ($_SESSION['_csrf'] ?? ''), $token)) {
        http_response_code(419);
        require BASE_PATH . '/templates/error-csrf.php';
        exit;
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['_flashes'][] = ['type' => $type, 'message' => $message];
}

function flashes(): array
{
    $messages = $_SESSION['_flashes'] ?? [];
    unset($_SESSION['_flashes']);
    return is_array($messages) ? $messages : [];
}

function with_form_errors(array $errors, array $input = []): void
{
    unset($input['password'], $input['password_confirmation'], $input['current_password'], $input['new_password'], $input['_csrf']);
    $_SESSION['_form_errors'] = $errors;
    $_SESSION['_form_old'] = $input;
}

function pull_form_state(): void
{
    $GLOBALS['form_errors'] = $_SESSION['_form_errors'] ?? [];
    $GLOBALS['form_old'] = $_SESSION['_form_old'] ?? [];
    unset($_SESSION['_form_errors'], $_SESSION['_form_old']);
}

function old(string $key, mixed $default = ''): mixed
{
    return $GLOBALS['form_old'][$key] ?? $default;
}

function form_error(string $key): ?string
{
    $error = $GLOBALS['form_errors'][$key] ?? null;
    return is_string($error) ? $error : null;
}

function selected(mixed $value, mixed $expected): string
{
    return (string) $value === (string) $expected ? ' selected' : '';
}

function checked(mixed $value, mixed $expected = true): string
{
    if (is_array($value)) {
        return in_array((string) $expected, array_map('strval', $value), true) ? ' checked' : '';
    }
    return $value == $expected ? ' checked' : '';
}

function normalize_peru_phone(string $phone): ?string
{
    $digits = preg_replace('/\D+/', '', $phone) ?? '';
    if (strlen($digits) === 11 && str_starts_with($digits, '51')) {
        $digits = substr($digits, 2);
    }
    if (strlen($digits) !== 9 || !str_starts_with($digits, '9')) {
        return null;
    }
    return '+51' . $digits;
}

function whatsapp_digits(string $phone): string
{
    return preg_replace('/\D+/', '', $phone) ?? '';
}

function mask_phone(string $phone): string
{
    $digits = whatsapp_digits($phone);
    if (strlen($digits) < 6) {
        return 'Número protegido';
    }
    return '+' . substr($digits, 0, 2) . ' *** *** ' . substr($digits, -3);
}

function password_is_valid(string $password): bool
{
    $length = mb_strlen($password);
    return $length >= 10
        && $length <= 128
        && preg_match('/[A-Za-zÁÉÍÓÚáéíóúÑñ]/u', $password) === 1
        && preg_match('/\d/', $password) === 1;
}

function money(float|string|null $amount): string
{
    if ($amount === null || $amount === '') {
        return 'Por acordar';
    }
    return 'S/ ' . number_format((float) $amount, 2, '.', ',');
}

function date_pe(?string $date, bool $withTime = false): string
{
    if (!$date) {
        return 'Por coordinar';
    }
    try {
        $format = $withTime ? 'd/m/Y · H:i' : 'd/m/Y';
        return (new DateTimeImmutable($date))->format($format);
    } catch (Throwable) {
        return $date;
    }
}

function setting(string $key, mixed $default = null): mixed
{
    static $settings = null;
    if ($settings === null) {
        $settings = [];
        try {
            $rows = db()->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
            foreach ($rows as $row) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Throwable) {
            return $default;
        }
    }
    return array_key_exists($key, $settings) ? $settings[$key] : $default;
}

function site_name(): string
{
    return (string) setting('site_name', config('app.name', 'Listo'));
}

function support_whatsapp_url(string $message = ''): ?string
{
    $phone = whatsapp_digits((string) setting('support_whatsapp', ''));
    if ($phone === '') {
        return null;
    }
    return 'https://wa.me/' . $phone . ($message !== '' ? '?text=' . rawurlencode($message) : '');
}

function person_whatsapp_url(string $phone, string $message): string
{
    return 'https://wa.me/' . whatsapp_digits($phone) . '?text=' . rawurlencode($message);
}

function status_label(string $status): string
{
    return [
        'active' => 'Activo', 'suspended' => 'Suspendido',
        'pending' => 'Pendiente', 'verified' => 'Aprobado', 'rejected' => 'Rechazado',
        'open' => 'Abierto', 'matched' => 'Proveedor elegido', 'in_progress' => 'En curso',
        'completed' => 'Completado', 'cancelled' => 'Cancelado',
        'accepted' => 'Aceptada', 'withdrawn' => 'Retirada',
        'published' => 'Publicada', 'hidden' => 'Oculta',
    ][$status] ?? ucfirst(str_replace('_', ' ', $status));
}

function status_class(string $status): string
{
    return match ($status) {
        'active', 'verified', 'completed', 'accepted', 'published' => 'success',
        'suspended', 'rejected', 'cancelled', 'hidden' => 'danger',
        'matched', 'in_progress' => 'info',
        default => 'warning',
    };
}

function priority_label(string $priority): string
{
    return ['flexible' => 'Fecha flexible', 'soon' => 'Lo antes posible', 'urgent' => 'Urgente'][$priority] ?? 'Flexible';
}

function request_ip_hash(): string
{
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    return hash_hmac('sha256', $ip, (string) config('app.key', 'listo'));
}

function rate_limit(string $action, string $identifier, int $maxAttempts, int $windowSeconds): bool
{
    $hash = hash_hmac('sha256', mb_strtolower(trim($identifier)), (string) config('app.key', 'listo'));
    $pdo = db();
    $statement = $pdo->prepare('SELECT attempts, window_started_at FROM rate_limits WHERE action_key = ? AND identifier_hash = ?');
    $statement->execute([$action, $hash]);
    $row = $statement->fetch();
    $now = new DateTimeImmutable();

    if (!$row || (new DateTimeImmutable($row['window_started_at']))->getTimestamp() + $windowSeconds < $now->getTimestamp()) {
        $statement = $pdo->prepare('INSERT INTO rate_limits (action_key, identifier_hash, attempts, window_started_at) VALUES (?, ?, 1, NOW()) ON DUPLICATE KEY UPDATE attempts = 1, window_started_at = NOW()');
        $statement->execute([$action, $hash]);
        return true;
    }

    if ((int) $row['attempts'] >= $maxAttempts) {
        return false;
    }

    $statement = $pdo->prepare('UPDATE rate_limits SET attempts = attempts + 1 WHERE action_key = ? AND identifier_hash = ?');
    $statement->execute([$action, $hash]);
    return true;
}

function clear_rate_limit(string $action, string $identifier): void
{
    $hash = hash_hmac('sha256', mb_strtolower(trim($identifier)), (string) config('app.key', 'listo'));
    $statement = db()->prepare('DELETE FROM rate_limits WHERE action_key = ? AND identifier_hash = ?');
    $statement->execute([$action, $hash]);
}

function audit(string $action, ?string $entityType = null, ?int $entityId = null, array $metadata = []): void
{
    try {
        $statement = db()->prepare('INSERT INTO audit_logs (user_id, action, entity_type, entity_id, metadata, ip_hash) VALUES (?, ?, ?, ?, ?, ?)');
        $statement->execute([
            auth_id(),
            $action,
            $entityType,
            $entityId,
            $metadata === [] ? null : json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            request_ip_hash(),
        ]);
    } catch (Throwable) {
        // Audit logging must not break the user flow.
    }
}

function icon(string $name, string $class = ''): string
{
    $paths = [
        'link' => '<circle cx="8" cy="8" r="3"></circle><circle cx="16" cy="16" r="3"></circle><path d="m10.2 10.2 3.6 3.6M14 7h4a2 2 0 0 1 2 2v2M10 17H6a2 2 0 0 1-2-2v-2"></path>',
        'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"></path>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"></path>',
        'close' => '<path d="m6 6 12 12M18 6 6 18"></path>',
        'user' => '<circle cx="12" cy="8" r="3"></circle><path d="M5 21a7 7 0 0 1 14 0"></path>',
        'plus' => '<path d="M12 5v14M5 12h14"></path>',
        'search' => '<circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M16 3v4M8 3v4M3 10h18"></path>',
        'map' => '<path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"></path><circle cx="12" cy="10" r="2.5"></circle>',
        'shield' => '<path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6l-8-3Z"></path><path d="m8.5 12 2.3 2.3 4.7-5"></path>',
        'chat' => '<path d="M4 4h16v12H7l-3 3Z"></path><path d="M8 9h8M8 12h5"></path>',
        'briefcase' => '<rect x="3" y="7" width="18" height="13" rx="2"></rect><path d="M8 7V4h8v3M3 12h18"></path>',
        'star' => '<path d="m12 3 2.7 5.5 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1-4.4-4.3 6.1-.9Z"></path>',
        'phone' => '<path d="M6.5 3h3l1.5 4-2 1.5a14 14 0 0 0 6.5 6.5l1.5-2 4 1.5v3c0 1.4-1.1 2.5-2.5 2.5C10.5 20 4 13.5 4 5.5 4 4.1 5.1 3 6.5 3Z"></path>',
        'settings' => '<circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-2.8 2.8-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.6v.2h-4V21a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1L4.2 17l.1-.1a1.7 1.7 0 0 0 .3-1.9A1.7 1.7 0 0 0 3 14H2.8v-4H3a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9L4.2 7 7 4.2l.1.1A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-1.6v-.2h4V3a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.3l.1-.1L19.8 7l-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.6 1h.2v4H21a1.7 1.7 0 0 0-1.6 1Z"></path>',
        'logout' => '<path d="M10 17l5-5-5-5M15 12H3M14 3h6v18h-6"></path>',
        'home' => '<path d="m3 11 9-8 9 8"></path><path d="M5 10v10h14V10M9 20v-6h6v6"></path>',
        'check' => '<path d="m5 12 4 4L19 6"></path>',
        'sparkles' => '<path d="M4 14c3 0 4-2 4-5 0 3 1 5 4 5-3 0-4 2-4 5 0-3-1-5-4-5Z"></path><path d="M13 8c2.5 0 3.5-1.5 3.5-4 0 2.5 1 4 3.5 4-2.5 0-3.5 1.5-3.5 4 0-2.5-1-4-3.5-4Z"></path>',
        'droplet' => '<path d="M12 3s6 6.5 6 11a6 6 0 0 1-12 0c0-4.5 6-11 6-11Z"></path>',
        'bolt' => '<path d="m13 2-8 12h7l-1 8 8-12h-7z"></path>',
        'paint' => '<path d="M4 4h12v5H4zM16 6h3v5h-7v3M12 14v7"></path>',
        'tools' => '<path d="m14 6 4 4M12 8l6-6 4 4-6 6"></path><path d="m13 11-8 8-3 1 1-3 8-8"></path>',
        'care' => '<circle cx="9" cy="8" r="3"></circle><path d="M3 20a6 6 0 0 1 12 0M17 10c2 0 3 1.5 3 3.5 0 3-3 4.5-3 4.5s-3-1.5-3-4.5c0-2 1-3.5 3-3.5Z"></path>',
        'paw' => '<circle cx="6" cy="8" r="2"></circle><circle cx="18" cy="8" r="2"></circle><circle cx="9" cy="4" r="2"></circle><circle cx="15" cy="4" r="2"></circle><path d="M12 10c-4 0-7 4-5 7 1.5 2.5 3.5 0 5 0s3.5 2.5 5 0c2-3-1-7-5-7Z"></path>',
        'truck' => '<path d="M3 6h11v11H3zM14 10h4l3 3v4h-7z"></path><circle cx="7" cy="18" r="2"></circle><circle cx="18" cy="18" r="2"></circle>',
        'book' => '<path d="M4 5a3 3 0 0 1 3-2h5v17H7a3 3 0 0 0-3 2ZM20 5a3 3 0 0 0-3-2h-5v17h5a3 3 0 0 1 3 2Z"></path>',
    ];
    $body = $paths[$name] ?? $paths['tools'];
    return '<svg class="icon ' . e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $body . '</svg>';
}
