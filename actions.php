<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

if (!is_post()) {
    redirect('index.php');
}

verify_csrf();
$action = (string) ($_POST['action'] ?? '');

$fail = static function (array $errors, string $destination): never {
    with_form_errors($errors, $_POST);
    redirect($destination);
};

$validDate = static function (?string $date, bool $allowPast = false): bool {
    if ($date === null || $date === '') {
        return true;
    }
    $parsed = DateTimeImmutable::createFromFormat('Y-m-d', $date);
    if (!$parsed || $parsed->format('Y-m-d') !== $date) {
        return false;
    }
    return $allowPast || $parsed >= new DateTimeImmutable('today');
};

switch ($action) {
    case 'request_start':
        $user = current_user();
        if ($user && $user['role'] !== 'client') {
            flash('warning', 'Las solicitudes se publican desde una cuenta de cliente.');
            redirect($user['role'] === 'provider' ? 'opportunities.php' : 'dashboard.php');
        }

        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $districtId = (int) ($_POST['district_id'] ?? 0);
        $description = trim((string) ($_POST['description'] ?? ''));
        $preferredDate = trim((string) ($_POST['preferred_date'] ?? ''));
        $priority = (string) ($_POST['priority'] ?? 'flexible');
        $errors = [];
        if (!category_exists($categoryId)) $errors['category_id'] = 'Selecciona una categoría válida.';
        if (!district_exists($districtId)) $errors['district_id'] = 'Selecciona un distrito válido.';
        if (mb_strlen($description) < 20 || mb_strlen($description) > 1000) $errors['description'] = 'Describe el trabajo con entre 20 y 1000 caracteres.';
        if (!$validDate($preferredDate)) $errors['preferred_date'] = 'Selecciona una fecha válida desde hoy en adelante.';
        if (!in_array($priority, ['flexible', 'soon', 'urgent'], true)) $priority = 'flexible';
        if ($errors !== []) $fail($errors, 'index.php#solicitar');

        $statement = db()->prepare('SELECT name FROM categories WHERE id = ?');
        $statement->execute([$categoryId]);
        $categoryName = (string) $statement->fetchColumn();
        $draft = [
            'category_id' => $categoryId,
            'district_id' => $districtId,
            'title' => 'Solicitud de ' . $categoryName,
            'description' => $description,
            'preferred_date' => $preferredDate,
            'priority' => $priority,
            'budget_min' => null,
            'budget_max' => null,
            'location_reference' => '',
            'target_provider_id' => null,
        ];

        if (!$user) {
            $_SESSION['draft_request'] = $draft;
            flash('info', 'Crea tu cuenta para publicar la solicitud. Conservamos la información que ya completaste.');
            redirect('register.php?role=client');
        }

        $requestId = create_service_request((int) $user['id'], $draft);
        flash('success', 'Tu solicitud quedó publicada.');
        redirect('request-view.php?id=' . $requestId);

    case 'register':
        if (is_authenticated()) redirect('dashboard.php');
        $role = (string) ($_POST['role'] ?? 'client');
        $name = trim((string) ($_POST['full_name'] ?? ''));
        $phoneInput = (string) ($_POST['phone'] ?? '');
        $phone = normalize_peru_phone($phoneInput);
        $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $confirmation = (string) ($_POST['password_confirmation'] ?? '');
        $errors = [];

        if (!in_array($role, ['client', 'provider'], true)) $errors['role'] = 'Selecciona un tipo de cuenta válido.';
        if (mb_strlen($name) < 3 || mb_strlen($name) > 120) $errors['full_name'] = 'Ingresa tu nombre completo.';
        if ($phone === null) $errors['phone'] = 'Ingresa un celular peruano válido de 9 dígitos.';
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Ingresa un correo válido o déjalo vacío.';
        if (!password_is_valid($password)) $errors['password'] = 'Usa al menos 10 caracteres, incluyendo letras y números.';
        if ($password !== $confirmation) $errors['password_confirmation'] = 'Las contraseñas no coinciden.';
        if (empty($_POST['terms'])) $errors['terms'] = 'Debes aceptar los términos y la política de privacidad.';
        if (!rate_limit('register', request_ip_hash(), 5, 3600)) $errors['general'] = 'Alcanzaste el límite de registros. Intenta más tarde.';

        if ($phone !== null) {
            $statement = db()->prepare('SELECT 1 FROM users WHERE phone = ?');
            $statement->execute([$phone]);
            if ($statement->fetchColumn()) $errors['phone'] = 'Ya existe una cuenta con este celular.';
        }
        if ($email !== '') {
            $statement = db()->prepare('SELECT 1 FROM users WHERE email = ?');
            $statement->execute([$email]);
            if ($statement->fetchColumn()) $errors['email'] = 'Ya existe una cuenta con este correo.';
        }
        if ($errors !== []) $fail($errors, 'register.php?role=' . urlencode($role));

        try {
            $userId = db_transaction(static function (PDO $pdo) use ($role, $name, $phone, $email, $password): int {
                $statement = $pdo->prepare('INSERT INTO users (role, full_name, phone, email, password_hash) VALUES (?, ?, ?, ?, ?)');
                $statement->execute([$role, $name, $phone, $email !== '' ? $email : null, password_hash($password, PASSWORD_DEFAULT)]);
                $id = (int) $pdo->lastInsertId();
                if ($role === 'provider') {
                    $statement = $pdo->prepare("INSERT INTO provider_profiles (user_id, verification_status) VALUES (?, 'pending')");
                    $statement->execute([$id]);
                }
                return $id;
            });
        } catch (PDOException $exception) {
            if ((string) $exception->getCode() === '23000') {
                $fail(['general' => 'El celular o correo ya pertenece a otra cuenta.'], 'register.php?role=' . urlencode($role));
            }
            throw $exception;
        }
        login_user($userId);
        audit('user.registered', 'user', $userId, ['role' => $role]);

        if ($role === 'client' && !empty($_SESSION['draft_request'])) {
            $draft = $_SESSION['draft_request'];
            unset($_SESSION['draft_request']);
            $requestId = create_service_request($userId, $draft);
            flash('success', 'Tu cuenta y solicitud fueron creadas correctamente.');
            redirect('request-view.php?id=' . $requestId);
        }
        if ($role === 'provider') {
            unset($_SESSION['draft_request']);
            flash('success', 'Cuenta creada. Completa tu perfil para solicitar la validación.');
            redirect('provider-edit.php');
        }
        flash('success', 'Tu cuenta fue creada correctamente.');
        redirect('dashboard.php');

    case 'login':
        if (is_authenticated()) redirect('dashboard.php');
        $phoneInput = (string) ($_POST['phone'] ?? '');
        $phone = normalize_peru_phone($phoneInput);
        $password = (string) ($_POST['password'] ?? '');
        $identifier = ($phone ?? $phoneInput) . '|' . request_ip_hash();
        if (!rate_limit('login', $identifier, 6, 900)) {
            $fail(['general' => 'Demasiados intentos. Espera 15 minutos antes de volver a intentar.'], 'login.php');
        }
        if ($phone === null || $password === '' || mb_strlen($password) > 128) {
            $fail(['general' => 'El celular o la contraseña no son correctos.'], 'login.php');
        }
        $statement = db()->prepare('SELECT * FROM users WHERE phone = ? LIMIT 1');
        $statement->execute([$phone]);
        $found = $statement->fetch();
        if (!$found || !password_verify($password, $found['password_hash'])) {
            usleep(250000);
            $fail(['general' => 'El celular o la contraseña no son correctos.'], 'login.php');
        }
        if ($found['status'] !== 'active') {
            $fail(['general' => 'Esta cuenta está suspendida. Contacta a soporte para revisarla.'], 'login.php');
        }
        if (password_needs_rehash($found['password_hash'], PASSWORD_DEFAULT)) {
            $statement = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $statement->execute([password_hash($password, PASSWORD_DEFAULT), $found['id']]);
        }
        db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([$found['id']]);
        clear_rate_limit('login', $identifier);
        login_user((int) $found['id']);
        audit('user.logged_in', 'user', (int) $found['id']);
        $intended = (string) ($_SESSION['intended_url'] ?? '');
        unset($_SESSION['intended_url']);
        if ($intended !== '' && str_starts_with($intended, '/') && !str_starts_with($intended, '//')) {
            $basePath = (string) parse_url((string) config('app.url'), PHP_URL_PATH);
            if ($basePath !== '' && str_starts_with($intended, $basePath)) $intended = substr($intended, strlen($basePath));
            redirect(ltrim($intended, '/'));
        }
        redirect($found['role'] === 'admin' ? 'admin/index.php' : 'dashboard.php');

    case 'logout':
        if (is_authenticated()) audit('user.logged_out', 'user', auth_id());
        logout_user();
        redirect('login.php');

    case 'request_create':
        $user = require_role('client');
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $districtId = (int) ($_POST['district_id'] ?? 0);
        $title = trim((string) ($_POST['title'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $preferredDate = trim((string) ($_POST['preferred_date'] ?? ''));
        $priority = (string) ($_POST['priority'] ?? 'flexible');
        $budgetMin = trim((string) ($_POST['budget_min'] ?? ''));
        $budgetMax = trim((string) ($_POST['budget_max'] ?? ''));
        $location = trim((string) ($_POST['location_reference'] ?? ''));
        $targetProviderId = (int) ($_POST['target_provider_id'] ?? 0);
        $targetProvider = $targetProviderId > 0 ? provider_public_profile($targetProviderId) : null;
        $errors = [];
        if (!category_exists($categoryId)) $errors['category_id'] = 'Selecciona una categoría válida.';
        if (!district_exists($districtId)) $errors['district_id'] = 'Selecciona un distrito válido.';
        if (mb_strlen($title) < 8 || mb_strlen($title) > 140) $errors['title'] = 'El título debe tener entre 8 y 140 caracteres.';
        if (mb_strlen($description) < 20 || mb_strlen($description) > 3000) $errors['description'] = 'La descripción debe tener entre 20 y 3000 caracteres.';
        if (!$validDate($preferredDate)) $errors['preferred_date'] = 'Selecciona una fecha válida desde hoy en adelante.';
        if (!in_array($priority, ['flexible', 'soon', 'urgent'], true)) $priority = 'flexible';
        if ($budgetMin !== '' && (!is_numeric($budgetMin) || (float) $budgetMin < 0)) $errors['budget_min'] = 'Ingresa un presupuesto mínimo válido.';
        if ($budgetMax !== '' && (!is_numeric($budgetMax) || (float) $budgetMax < 0)) $errors['budget_max'] = 'Ingresa un presupuesto máximo válido.';
        if ($budgetMin !== '' && $budgetMax !== '' && (float) $budgetMin > (float) $budgetMax) $errors['budget_max'] = 'El máximo debe ser mayor o igual al mínimo.';
        if (mb_strlen($location) > 255) $errors['location_reference'] = 'La referencia debe tener máximo 255 caracteres.';
        if ($targetProviderId > 0 && !$targetProvider) {
            $errors['general'] = 'El proveedor seleccionado ya no está disponible.';
        } elseif ($targetProviderId > 0 && !provider_matches_request($targetProviderId, [
            'category_id' => $categoryId,
            'district_id' => $districtId,
        ])) {
            $errors['general'] = 'El proveedor seleccionado no atiende esa categoría en el distrito indicado.';
        }
        if (!rate_limit('request_create', (string) $user['id'], 10, 3600)) {
            $errors['general'] = 'Alcanzaste el límite de solicitudes por hora. Intenta más tarde.';
        }
        if ($errors !== []) $fail($errors, 'request-new.php' . ($targetProviderId > 0 ? '?provider=' . $targetProviderId : ''));

        $requestId = create_service_request((int) $user['id'], [
            'category_id' => $categoryId, 'district_id' => $districtId, 'title' => $title,
            'description' => $description, 'preferred_date' => $preferredDate, 'priority' => $priority,
            'budget_min' => $budgetMin, 'budget_max' => $budgetMax, 'location_reference' => $location,
            'target_provider_id' => $targetProviderId ?: null,
        ]);
        flash('success', 'Tu solicitud fue publicada. Podrás revisar aquí las propuestas recibidas.');
        redirect('request-view.php?id=' . $requestId);

    case 'provider_profile_update':
        $user = require_role('provider');
        $headline = trim((string) ($_POST['headline'] ?? ''));
        $bio = trim((string) ($_POST['bio'] ?? ''));
        $years = max(0, min(60, (int) ($_POST['years_experience'] ?? 0)));
        $availability = trim((string) ($_POST['availability_text'] ?? ''));
        $categoryIds = array_values(array_unique(array_map('intval', (array) ($_POST['categories'] ?? []))));
        $districtIds = array_values(array_unique(array_map('intval', (array) ($_POST['districts'] ?? []))));
        $errors = [];
        if (mb_strlen($headline) < 10 || mb_strlen($headline) > 160) $errors['headline'] = 'Escribe un titular de entre 10 y 160 caracteres.';
        if (mb_strlen($bio) < 80 || mb_strlen($bio) > 2000) $errors['bio'] = 'Describe tu experiencia con entre 80 y 2000 caracteres.';
        if (mb_strlen($availability) < 5 || mb_strlen($availability) > 255) $errors['availability_text'] = 'Describe brevemente tu disponibilidad.';
        if ($categoryIds === [] || count($categoryIds) > 5) $errors['categories'] = 'Selecciona entre 1 y 5 categorías.';
        if ($districtIds === [] || count($districtIds) > 20) $errors['districts'] = 'Selecciona entre 1 y 20 distritos.';
        if ($categoryIds !== []) {
            $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
            $statement = db()->prepare("SELECT COUNT(*) FROM categories WHERE active = 1 AND id IN ($placeholders)");
            $statement->execute($categoryIds);
            if ((int) $statement->fetchColumn() !== count($categoryIds)) $errors['categories'] = 'Una categoría seleccionada no es válida.';
        }
        if ($districtIds !== []) {
            $placeholders = implode(',', array_fill(0, count($districtIds), '?'));
            $statement = db()->prepare("SELECT COUNT(*) FROM districts WHERE active = 1 AND id IN ($placeholders)");
            $statement->execute($districtIds);
            if ((int) $statement->fetchColumn() !== count($districtIds)) $errors['districts'] = 'Un distrito seleccionado no es válido.';
        }
        if ($errors !== []) $fail($errors, 'provider-edit.php');

        db_transaction(static function (PDO $pdo) use ($user, $headline, $bio, $years, $availability, $categoryIds, $districtIds): void {
            $statement = $pdo->prepare(
                "INSERT INTO provider_profiles (user_id, headline, bio, years_experience, availability_text, verification_status)
                 VALUES (?, ?, ?, ?, ?, 'pending')
                 ON DUPLICATE KEY UPDATE headline = VALUES(headline), bio = VALUES(bio),
                    years_experience = VALUES(years_experience), availability_text = VALUES(availability_text),
                    verification_status = 'pending', verification_notes = NULL, verified_at = NULL, verified_by = NULL"
            );
            $statement->execute([$user['id'], $headline, $bio, $years, $availability]);
            $pdo->prepare('DELETE FROM provider_categories WHERE provider_id = ?')->execute([$user['id']]);
            $pdo->prepare('DELETE FROM provider_districts WHERE provider_id = ?')->execute([$user['id']]);
            $categoryStatement = $pdo->prepare('INSERT INTO provider_categories (provider_id, category_id) VALUES (?, ?)');
            foreach ($categoryIds as $categoryId) $categoryStatement->execute([$user['id'], $categoryId]);
            $districtStatement = $pdo->prepare('INSERT INTO provider_districts (provider_id, district_id) VALUES (?, ?)');
            foreach ($districtIds as $districtId) $districtStatement->execute([$user['id'], $districtId]);
        });
        audit('provider.profile_updated', 'user', (int) $user['id']);
        flash('success', 'Tu perfil fue actualizado y volvió a revisión administrativa.');
        redirect('provider-edit.php');

    case 'proposal_submit':
        $user = require_role('provider');
        $requestId = (int) ($_POST['request_id'] ?? 0);
        $request = service_request($requestId);
        if (!$request || $request['status'] !== 'open') {
            flash('danger', 'La solicitud ya no acepta propuestas.');
            redirect('opportunities.php');
        }
        if (!provider_is_verified((int) $user['id'])) {
            flash('warning', 'Tu perfil debe estar aprobado antes de enviar propuestas.');
            redirect('provider-edit.php');
        }
        if (!provider_matches_request((int) $user['id'], $request) && (int) ($request['target_provider_id'] ?? 0) !== (int) $user['id']) {
            http_response_code(403);
            require BASE_PATH . '/templates/error-403.php';
            exit;
        }
        $message = trim((string) ($_POST['message'] ?? ''));
        $price = trim((string) ($_POST['price'] ?? ''));
        $availableDate = trim((string) ($_POST['available_date'] ?? ''));
        $errors = [];
        if (mb_strlen($message) < 20 || mb_strlen($message) > 1500) $errors['message'] = 'Explica tu propuesta con entre 20 y 1500 caracteres.';
        if (!is_numeric($price) || (float) $price <= 0 || (float) $price > 1000000) $errors['price'] = 'Ingresa un precio válido.';
        if (!$validDate($availableDate)) $errors['available_date'] = 'Selecciona una fecha válida.';
        $statement = db()->prepare('SELECT status FROM proposals WHERE request_id = ? AND provider_id = ?');
        $statement->execute([$requestId, $user['id']]);
        $currentStatus = $statement->fetchColumn();
        if ($currentStatus === 'accepted') $errors['general'] = 'La propuesta ya fue aceptada y no puede editarse.';
        if ($errors !== []) $fail($errors, 'request-view.php?id=' . $requestId);
        $statement = db()->prepare(
            "INSERT INTO proposals (request_id, provider_id, message, price, available_date, status) VALUES (?, ?, ?, ?, ?, 'pending')
             ON DUPLICATE KEY UPDATE message = VALUES(message), price = VALUES(price), available_date = VALUES(available_date), status = 'pending', updated_at = NOW()"
        );
        $statement->execute([$requestId, $user['id'], $message, (float) $price, $availableDate ?: null]);
        audit('proposal.submitted', 'service_request', $requestId);
        flash('success', 'Tu propuesta fue enviada al cliente.');
        redirect('request-view.php?id=' . $requestId);

    case 'proposal_withdraw':
        $user = require_role('provider');
        $proposalId = (int) ($_POST['proposal_id'] ?? 0);
        $statement = db()->prepare("UPDATE proposals SET status = 'withdrawn' WHERE id = ? AND provider_id = ? AND status = 'pending'");
        $statement->execute([$proposalId, $user['id']]);
        audit('proposal.withdrawn', 'proposal', $proposalId);
        flash('success', 'La propuesta fue retirada.');
        redirect('proposals.php');

    case 'proposal_accept':
        $user = require_role('client');
        $proposalId = (int) ($_POST['proposal_id'] ?? 0);
        $statement = db()->prepare(
            "SELECT p.*, r.client_id, r.status AS request_status,
                    u.status AS provider_status, pp.verification_status
             FROM proposals p
             JOIN service_requests r ON r.id = p.request_id
             JOIN users u ON u.id = p.provider_id
             JOIN provider_profiles pp ON pp.user_id = p.provider_id
             WHERE p.id = ?"
        );
        $statement->execute([$proposalId]);
        $proposal = $statement->fetch();
        if (!$proposal
            || (int) $proposal['client_id'] !== (int) $user['id']
            || $proposal['request_status'] !== 'open'
            || $proposal['status'] !== 'pending'
            || $proposal['provider_status'] !== 'active'
            || $proposal['verification_status'] !== 'verified') {
            flash('danger', 'La propuesta ya no puede ser aceptada.');
            redirect('requests.php');
        }
        db_transaction(static function (PDO $pdo) use ($proposal): void {
            $statement = $pdo->prepare("UPDATE service_requests SET selected_proposal_id = ?, status = 'matched' WHERE id = ? AND status = 'open'");
            $statement->execute([$proposal['id'], $proposal['request_id']]);
            if ($statement->rowCount() !== 1) throw new RuntimeException('La solicitud fue actualizada por otra operación.');
            $pdo->prepare("UPDATE proposals SET status = CASE WHEN id = ? THEN 'accepted' ELSE 'rejected' END WHERE request_id = ? AND status = 'pending'")->execute([$proposal['id'], $proposal['request_id']]);
        });
        audit('proposal.accepted', 'proposal', $proposalId);
        flash('success', 'Elegiste al proveedor. Ya pueden coordinar por WhatsApp.');
        redirect('request-view.php?id=' . (int) $proposal['request_id']);

    case 'request_status':
        $user = require_role('client');
        $requestId = (int) ($_POST['request_id'] ?? 0);
        $newStatus = (string) ($_POST['status'] ?? '');
        $request = service_request($requestId);
        if (!$request || (int) $request['client_id'] !== (int) $user['id']) {
            http_response_code(403); require BASE_PATH . '/templates/error-403.php'; exit;
        }
        $allowed = [
            'open' => ['cancelled'],
            'matched' => ['in_progress', 'cancelled'],
            'in_progress' => ['completed'],
        ];
        if (!in_array($newStatus, $allowed[$request['status']] ?? [], true)) {
            flash('danger', 'Ese cambio de estado no está permitido.');
            redirect('request-view.php?id=' . $requestId);
        }
        $statement = db()->prepare("UPDATE service_requests SET status = ?, completed_at = IF(? = 'completed', NOW(), completed_at) WHERE id = ? AND status = ?");
        $statement->execute([$newStatus, $newStatus, $requestId, $request['status']]);
        if ($statement->rowCount() !== 1) {
            flash('warning', 'La solicitud cambió mientras realizabas la acción. Revisa su estado actual.');
            redirect('request-view.php?id=' . $requestId);
        }
        audit('request.status_changed', 'service_request', $requestId, ['from' => $request['status'], 'to' => $newStatus]);
        flash('success', 'El estado de la solicitud fue actualizado.');
        redirect('request-view.php?id=' . $requestId);

    case 'review_create':
        $user = require_role('client');
        $requestId = (int) ($_POST['request_id'] ?? 0);
        $rating = (int) ($_POST['rating'] ?? 0);
        $comment = trim((string) ($_POST['comment'] ?? ''));
        $request = service_request($requestId);
        if (!$request || (int) $request['client_id'] !== (int) $user['id'] || $request['status'] !== 'completed' || empty($request['selected_proposal_id'])) {
            flash('danger', 'Solo puedes evaluar servicios completados.');
            redirect('requests.php');
        }
        $errors = [];
        if ($rating < 1 || $rating > 5) $errors['rating'] = 'Selecciona una calificación entre 1 y 5.';
        if (mb_strlen($comment) > 1000) $errors['comment'] = 'El comentario debe tener máximo 1000 caracteres.';
        if ($errors !== []) $fail($errors, 'request-view.php?id=' . $requestId . '#review');
        $statement = db()->prepare('SELECT provider_id FROM proposals WHERE id = ?');
        $statement->execute([$request['selected_proposal_id']]);
        $providerId = (int) $statement->fetchColumn();
        if ($providerId <= 0) {
            flash('danger', 'No se pudo identificar al proveedor elegido. Contacta a soporte.');
            redirect('request-view.php?id=' . $requestId);
        }
        $reviewStatus = setting('reviews_auto_publish', '0') === '1' ? 'published' : 'pending';
        $statement = db()->prepare('INSERT INTO reviews (request_id, client_id, provider_id, rating, comment, status) VALUES (?, ?, ?, ?, ?, ?)');
        try {
            $statement->execute([$requestId, $user['id'], $providerId, $rating, $comment !== '' ? $comment : null, $reviewStatus]);
        } catch (PDOException $exception) {
            if ((string) $exception->getCode() === '23000' && (int) ($exception->errorInfo[1] ?? 0) === 1062) {
                flash('warning', 'Ya registraste una reseña para este servicio.');
                redirect('request-view.php?id=' . $requestId);
            }
            throw $exception;
        }
        audit('review.created', 'review', (int) db()->lastInsertId());
        flash('success', $reviewStatus === 'published' ? 'Tu reseña fue publicada.' : 'Tu reseña fue enviada a moderación.');
        redirect('request-view.php?id=' . $requestId);

    case 'account_update':
        $user = require_auth();
        $name = trim((string) ($_POST['full_name'] ?? ''));
        $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmation = (string) ($_POST['password_confirmation'] ?? '');
        $errors = [];
        if (mb_strlen($name) < 3 || mb_strlen($name) > 120) $errors['full_name'] = 'Ingresa tu nombre completo.';
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Ingresa un correo válido.';
        if ($email !== '') {
            $statement = db()->prepare('SELECT 1 FROM users WHERE email = ? AND id <> ?');
            $statement->execute([$email, $user['id']]);
            if ($statement->fetchColumn()) $errors['email'] = 'El correo ya está asociado a otra cuenta.';
        }
        $changePassword = $newPassword !== '' || (int) $user['must_change_password'] === 1;
        if ($changePassword) {
            $statement = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
            $statement->execute([$user['id']]);
            $hash = (string) $statement->fetchColumn();
            if (!password_verify($currentPassword, $hash)) $errors['current_password'] = 'La contraseña actual no es correcta.';
            if (!password_is_valid($newPassword)) $errors['new_password'] = 'Usa al menos 10 caracteres, letras y números.';
            if ($newPassword !== $confirmation) $errors['password_confirmation'] = 'Las contraseñas no coinciden.';
        }
        if ($errors !== []) $fail($errors, 'account.php');
        if ($changePassword) {
            $statement = db()->prepare('UPDATE users SET full_name = ?, email = ?, password_hash = ?, must_change_password = 0 WHERE id = ?');
            $statement->execute([$name, $email !== '' ? $email : null, password_hash($newPassword, PASSWORD_DEFAULT), $user['id']]);
        } else {
            $statement = db()->prepare('UPDATE users SET full_name = ?, email = ? WHERE id = ?');
            $statement->execute([$name, $email !== '' ? $email : null, $user['id']]);
        }
        current_user(true);
        audit('user.account_updated', 'user', (int) $user['id']);
        flash('success', 'Tu cuenta fue actualizada.');
        redirect('account.php');

    case 'admin_user_status':
        $admin = require_role('admin');
        $userId = (int) ($_POST['user_id'] ?? 0);
        $status = (string) ($_POST['status'] ?? '');
        if ($userId === (int) $admin['id'] || !in_array($status, ['active', 'suspended'], true)) {
            flash('danger', 'No puedes aplicar ese cambio.');
            redirect('admin/users.php');
        }
        $statement = db()->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
        $statement->execute([$userId]);
        $targetRole = $statement->fetchColumn();
        if (!$targetRole || $targetRole === 'admin') {
            flash('danger', 'El usuario no existe o no puede ser modificado desde esta acción.');
            redirect('admin/users.php');
        }
        db()->prepare('UPDATE users SET status = ? WHERE id = ?')->execute([$status, $userId]);
        audit('admin.user_status', 'user', $userId, ['status' => $status]);
        flash('success', 'Estado del usuario actualizado.');
        redirect('admin/users.php');

    case 'admin_provider_verify':
        $admin = require_role('admin');
        $providerId = (int) ($_POST['provider_id'] ?? 0);
        $status = (string) ($_POST['verification_status'] ?? '');
        $notes = trim((string) ($_POST['verification_notes'] ?? ''));
        if (!in_array($status, ['pending', 'verified', 'rejected'], true)) {
            flash('danger', 'Estado de validación no permitido.');
            redirect('admin/users.php?role=provider');
        }
        if (mb_strlen($notes) > 500) {
            flash('danger', 'Las notas de validación deben tener máximo 500 caracteres.');
            redirect('admin/user.php?id=' . $providerId);
        }
        if ($status === 'rejected' && $notes === '') {
            flash('danger', 'Explica al proveedor qué debe corregir antes de rechazar el perfil.');
            redirect('admin/user.php?id=' . $providerId);
        }
        $statement = db()->prepare(
            "SELECT u.status, pp.headline, pp.bio,
                    (SELECT COUNT(*) FROM provider_categories pc WHERE pc.provider_id = u.id) AS category_count,
                    (SELECT COUNT(*) FROM provider_districts pd WHERE pd.provider_id = u.id) AS district_count
             FROM users u
             JOIN provider_profiles pp ON pp.user_id = u.id
             WHERE u.id = ? AND u.role = 'provider' LIMIT 1"
        );
        $statement->execute([$providerId]);
        $provider = $statement->fetch();
        if (!$provider) {
            flash('danger', 'El perfil de proveedor no existe.');
            redirect('admin/users.php?role=provider');
        }
        if ($status === 'verified' && ($provider['status'] !== 'active'
            || mb_strlen(trim((string) $provider['headline'])) < 10
            || mb_strlen(trim((string) $provider['bio'])) < 80
            || (int) $provider['category_count'] < 1
            || (int) $provider['district_count'] < 1)) {
            flash('danger', 'El proveedor debe tener una cuenta activa y completar experiencia, categoría y cobertura antes de ser aprobado.');
            redirect('admin/user.php?id=' . $providerId);
        }
        $statement = db()->prepare("UPDATE provider_profiles SET verification_status = ?, verification_notes = ?, verified_at = IF(? = 'verified', NOW(), NULL), verified_by = ? WHERE user_id = ?");
        $statement->execute([$status, $notes !== '' ? $notes : null, $status, $admin['id'], $providerId]);
        audit('admin.provider_verification', 'user', $providerId, ['status' => $status]);
        flash('success', 'Validación del proveedor actualizada.');
        redirect('admin/users.php?role=provider');

    case 'admin_review_status':
        require_role('admin');
        $reviewId = (int) ($_POST['review_id'] ?? 0);
        $status = (string) ($_POST['status'] ?? '');
        if (!in_array($status, ['pending', 'published', 'hidden'], true)) {
            flash('danger', 'Estado no permitido.'); redirect('admin/reviews.php');
        }
        $statement = db()->prepare('UPDATE reviews SET status = ? WHERE id = ?');
        $statement->execute([$status, $reviewId]);
        if ($statement->rowCount() !== 1) {
            $statement = db()->prepare('SELECT 1 FROM reviews WHERE id = ?');
            $statement->execute([$reviewId]);
            if (!$statement->fetchColumn()) {
                flash('danger', 'La reseña ya no existe.');
                redirect('admin/reviews.php');
            }
        }
        audit('admin.review_status', 'review', $reviewId, ['status' => $status]);
        flash('success', 'Estado de la reseña actualizado.');
        redirect('admin/reviews.php');

    case 'admin_request_status':
        require_role('admin');
        $requestId = (int) ($_POST['request_id'] ?? 0);
        $status = (string) ($_POST['status'] ?? '');
        if (!in_array($status, ['open', 'matched', 'in_progress', 'completed', 'cancelled'], true)) {
            flash('danger', 'Estado no permitido.'); redirect('admin/requests.php');
        }
        $statement = db()->prepare('SELECT status, selected_proposal_id FROM service_requests WHERE id = ? LIMIT 1');
        $statement->execute([$requestId]);
        $request = $statement->fetch();
        if (!$request) {
            flash('danger', 'La solicitud ya no existe.');
            redirect('admin/requests.php');
        }
        if (in_array($status, ['matched', 'in_progress', 'completed'], true) && empty($request['selected_proposal_id'])) {
            flash('danger', 'No puedes asignar ese estado sin una propuesta elegida.');
            redirect('admin/requests.php');
        }
        if ($status === 'open' && !empty($request['selected_proposal_id'])) {
            flash('danger', 'Una solicitud con propuesta elegida no puede reabrirse sin restablecer sus propuestas.');
            redirect('admin/requests.php');
        }
        $statement = db()->prepare("UPDATE service_requests SET status = ?, completed_at = IF(? = 'completed', COALESCE(completed_at, NOW()), NULL) WHERE id = ?");
        $statement->execute([$status, $status, $requestId]);
        audit('admin.request_status', 'service_request', $requestId, ['status' => $status]);
        flash('success', 'Solicitud actualizada.');
        redirect('admin/requests.php');

    case 'admin_settings_update':
        $admin = require_role('admin');
        $siteName = trim((string) ($_POST['site_name'] ?? ''));
        $supportWhatsappInput = trim((string) ($_POST['support_whatsapp'] ?? ''));
        $supportWhatsapp = $supportWhatsappInput !== '' ? normalize_peru_phone($supportWhatsappInput) : '';
        $supportEmail = mb_strtolower(trim((string) ($_POST['support_email'] ?? '')));
        $city = trim((string) ($_POST['marketplace_city'] ?? ''));
        $errors = [];
        if ($siteName === '' || mb_strlen($siteName) > 80) $errors['site_name'] = 'Ingresa un nombre de máximo 80 caracteres.';
        if ($supportWhatsappInput !== '' && $supportWhatsapp === null) $errors['support_whatsapp'] = 'Ingresa un celular peruano válido.';
        if ($supportEmail !== '' && !filter_var($supportEmail, FILTER_VALIDATE_EMAIL)) $errors['support_email'] = 'Ingresa un correo válido.';
        if ($city === '' || mb_strlen($city) > 100) $errors['marketplace_city'] = 'Ingresa una cobertura válida.';
        if ($errors !== []) $fail($errors, 'admin/settings.php');
        $values = [
            'site_name' => $siteName,
            'support_whatsapp' => $supportWhatsapp,
            'support_email' => $supportEmail,
            'marketplace_city' => $city,
            'reviews_auto_publish' => !empty($_POST['reviews_auto_publish']) ? '1' : '0',
        ];
        $statement = db()->prepare('INSERT INTO settings (setting_key, setting_value, is_public, updated_by) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by)');
        foreach ($values as $key => $value) {
            $public = in_array($key, ['site_name', 'support_whatsapp', 'support_email', 'marketplace_city'], true) ? 1 : 0;
            $statement->execute([$key, $value, $public, $admin['id']]);
        }
        audit('admin.settings_updated', 'settings', null);
        flash('success', 'Configuración actualizada.');
        redirect('admin/settings.php');

    case 'admin_reset_password':
        $admin = require_role('admin');
        $userId = (int) ($_POST['user_id'] ?? 0);
        if ($userId === (int) $admin['id']) {
            flash('danger', 'Cambia tu propia contraseña desde Mi cuenta.'); redirect('admin/users.php');
        }
        $statement = db()->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
        $statement->execute([$userId]);
        $targetRole = $statement->fetchColumn();
        if (!$targetRole || $targetRole === 'admin') {
            flash('danger', 'La cuenta no existe o no admite restablecimiento desde esta acción.');
            redirect('admin/users.php');
        }
        $temporary = 'Li' . bin2hex(random_bytes(4)) . random_int(10, 99);
        $statement = db()->prepare('UPDATE users SET password_hash = ?, must_change_password = 1 WHERE id = ?');
        $statement->execute([password_hash($temporary, PASSWORD_DEFAULT), $userId]);
        audit('admin.password_reset', 'user', $userId);
        flash('warning', 'Contraseña temporal: ' . $temporary . ' · Cópiala ahora; no volverá a mostrarse.');
        redirect('admin/users.php');

    default:
        flash('danger', 'La acción solicitada no es válida.');
        redirect('dashboard.php');
}
