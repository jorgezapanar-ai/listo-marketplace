<?php
declare(strict_types=1);

function current_user(bool $refresh = false): ?array
{
    static $user = false;

    if ($refresh) {
        $user = false;
    }
    if ($user !== false) {
        return $user;
    }
    if (empty($_SESSION['user_id'])) {
        $user = null;
        return null;
    }

    $statement = db()->prepare('SELECT id, role, full_name, phone, email, status, must_change_password, phone_verified_at, created_at FROM users WHERE id = ? LIMIT 1');
    $statement->execute([(int) $_SESSION['user_id']]);
    $found = $statement->fetch();

    if (!$found || $found['status'] !== 'active') {
        unset($_SESSION['user_id']);
        $user = null;
        return null;
    }

    $user = $found;
    return $user;
}

function auth_id(): ?int
{
    $user = current_user();
    return $user ? (int) $user['id'] : null;
}

function is_authenticated(): bool
{
    return current_user() !== null;
}

function is_role(string ...$roles): bool
{
    $user = current_user();
    return $user !== null && in_array($user['role'], $roles, true);
}

function require_auth(): array
{
    $user = current_user();
    if (!$user) {
        $_SESSION['intended_url'] = (string) ($_SERVER['REQUEST_URI'] ?? '/dashboard.php');
        flash('warning', 'Inicia sesión para continuar.');
        redirect('login.php');
    }
    if ((int) $user['must_change_password'] === 1 && !str_ends_with((string) ($_SERVER['SCRIPT_NAME'] ?? ''), '/account.php')) {
        flash('warning', 'Cambia la contraseña temporal antes de continuar.');
        redirect('account.php');
    }
    return $user;
}

function require_role(string ...$roles): array
{
    $user = require_auth();
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        require BASE_PATH . '/templates/error-403.php';
        exit;
    }
    return $user;
}

function login_user(int $id): void
{
    session_regenerate_id(true);
    unset($_SESSION['_csrf']);
    $_SESSION['user_id'] = $id;
    current_user(true);
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
    }
    session_destroy();
}

function user_initials(string $name): string
{
    $parts = preg_split('/\s+/u', trim($name)) ?: [];
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $initials .= mb_strtoupper(mb_substr($part, 0, 1));
    }
    return $initials !== '' ? $initials : 'U';
}

function first_name(string $name): string
{
    return explode(' ', trim($name))[0] ?: $name;
}

function provider_profile(int $userId): ?array
{
    $statement = db()->prepare('SELECT * FROM provider_profiles WHERE user_id = ? LIMIT 1');
    $statement->execute([$userId]);
    $profile = $statement->fetch();
    return $profile ?: null;
}

function provider_is_verified(int $userId): bool
{
    $profile = provider_profile($userId);
    return $profile !== null && $profile['verification_status'] === 'verified';
}
