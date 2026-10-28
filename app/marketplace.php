<?php
declare(strict_types=1);

function categories(bool $activeOnly = true): array
{
    $sql = 'SELECT * FROM categories' . ($activeOnly ? ' WHERE active = 1' : '') . ' ORDER BY sort_order, name';
    return db()->query($sql)->fetchAll();
}

function districts(bool $activeOnly = true): array
{
    $sql = 'SELECT * FROM districts' . ($activeOnly ? ' WHERE active = 1' : '') . ' ORDER BY zone, sort_order, name';
    return db()->query($sql)->fetchAll();
}

function category_exists(int $id): bool
{
    $statement = db()->prepare('SELECT 1 FROM categories WHERE id = ? AND active = 1');
    $statement->execute([$id]);
    return (bool) $statement->fetchColumn();
}

function district_exists(int $id): bool
{
    $statement = db()->prepare('SELECT 1 FROM districts WHERE id = ? AND active = 1');
    $statement->execute([$id]);
    return (bool) $statement->fetchColumn();
}

function create_service_request(int $clientId, array $data): int
{
    $statement = db()->prepare(
        'INSERT INTO service_requests (client_id, category_id, district_id, target_provider_id, title, description, preferred_date, priority, budget_min, budget_max, location_reference)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $statement->execute([
        $clientId,
        (int) $data['category_id'],
        (int) $data['district_id'],
        !empty($data['target_provider_id']) ? (int) $data['target_provider_id'] : null,
        trim((string) $data['title']),
        trim((string) $data['description']),
        $data['preferred_date'] ?: null,
        $data['priority'] ?? 'flexible',
        $data['budget_min'] !== '' && $data['budget_min'] !== null ? (float) $data['budget_min'] : null,
        $data['budget_max'] !== '' && $data['budget_max'] !== null ? (float) $data['budget_max'] : null,
        trim((string) ($data['location_reference'] ?? '')) ?: null,
    ]);
    $id = (int) db()->lastInsertId();
    audit('request.created', 'service_request', $id);
    return $id;
}

function service_request(int $id): ?array
{
    $statement = db()->prepare(
        'SELECT r.*, c.name AS category_name, c.icon AS category_icon, d.name AS district_name, d.zone AS district_zone,
                u.full_name AS client_name, u.phone AS client_phone,
                p.full_name AS selected_provider_name, p.phone AS selected_provider_phone,
                p.status AS selected_provider_status, spp.verification_status AS selected_provider_verification
         FROM service_requests r
         JOIN categories c ON c.id = r.category_id
         JOIN districts d ON d.id = r.district_id
         JOIN users u ON u.id = r.client_id
         LEFT JOIN proposals sp ON sp.id = r.selected_proposal_id
         LEFT JOIN users p ON p.id = sp.provider_id
         LEFT JOIN provider_profiles spp ON spp.user_id = p.id
         WHERE r.id = ? LIMIT 1'
    );
    $statement->execute([$id]);
    $request = $statement->fetch();
    return $request ?: null;
}

function request_proposals(int $requestId): array
{
    $statement = db()->prepare(
        "SELECT p.*, u.full_name AS provider_name, u.status AS provider_account_status,
                pp.headline, pp.years_experience, pp.verification_status,
                COALESCE(rv.rating_avg, 0) AS rating_avg, COALESCE(rv.review_count, 0) AS review_count
         FROM proposals p
         JOIN users u ON u.id = p.provider_id
         JOIN provider_profiles pp ON pp.user_id = p.provider_id
         LEFT JOIN (
            SELECT provider_id, AVG(rating) AS rating_avg, COUNT(*) AS review_count
            FROM reviews WHERE status = 'published' GROUP BY provider_id
         ) rv ON rv.provider_id = p.provider_id
         WHERE p.request_id = ? ORDER BY FIELD(p.status, 'accepted','pending','rejected','withdrawn'), p.created_at DESC"
    );
    $statement->execute([$requestId]);
    return $statement->fetchAll();
}

function provider_public_profile(int $id): ?array
{
    $statement = db()->prepare(
        "SELECT u.id, u.full_name, u.created_at, pp.headline, pp.bio, pp.years_experience, pp.availability_text,
                pp.verification_status, COALESCE(rv.rating_avg, 0) AS rating_avg, COALESCE(rv.review_count, 0) AS review_count
         FROM users u
         JOIN provider_profiles pp ON pp.user_id = u.id
         LEFT JOIN (
            SELECT provider_id, AVG(rating) AS rating_avg, COUNT(*) AS review_count
            FROM reviews WHERE status = 'published' GROUP BY provider_id
         ) rv ON rv.provider_id = u.id
         WHERE u.id = ? AND u.role = 'provider' AND u.status = 'active' AND pp.verification_status = 'verified' LIMIT 1"
    );
    $statement->execute([$id]);
    $profile = $statement->fetch();
    if (!$profile) {
        return null;
    }

    $statement = db()->prepare('SELECT c.id, c.name, c.slug FROM provider_categories pc JOIN categories c ON c.id = pc.category_id WHERE pc.provider_id = ? AND c.active = 1 ORDER BY c.sort_order');
    $statement->execute([$id]);
    $profile['categories'] = $statement->fetchAll();

    $statement = db()->prepare('SELECT d.id, d.name, d.zone FROM provider_districts pd JOIN districts d ON d.id = pd.district_id WHERE pd.provider_id = ? AND d.active = 1 ORDER BY d.name');
    $statement->execute([$id]);
    $profile['districts'] = $statement->fetchAll();

    $statement = db()->prepare("SELECT rating, comment, created_at FROM reviews WHERE provider_id = ? AND status = 'published' ORDER BY created_at DESC LIMIT 10");
    $statement->execute([$id]);
    $profile['reviews'] = $statement->fetchAll();

    return $profile;
}

function provider_matches_request(int $providerId, array $request): bool
{
    $statement = db()->prepare(
        'SELECT 1 FROM provider_categories pc
         JOIN provider_districts pd ON pd.provider_id = pc.provider_id
         WHERE pc.provider_id = ? AND pc.category_id = ? AND pd.district_id = ? LIMIT 1'
    );
    $statement->execute([$providerId, (int) $request['category_id'], (int) $request['district_id']]);
    return (bool) $statement->fetchColumn();
}

function can_access_request(array $request, array $user): bool
{
    if ($user['role'] === 'admin') {
        return true;
    }
    if ($user['role'] === 'client') {
        return (int) $request['client_id'] === (int) $user['id'];
    }
    if ($user['role'] === 'provider') {
        if ($request['status'] === 'open' && (int) ($request['target_provider_id'] ?? 0) === (int) $user['id']) {
            return true;
        }
        $statement = db()->prepare('SELECT 1 FROM proposals WHERE request_id = ? AND provider_id = ?');
        $statement->execute([(int) $request['id'], (int) $user['id']]);
        return (bool) $statement->fetchColumn() || ($request['status'] === 'open' && provider_matches_request((int) $user['id'], $request));
    }
    return false;
}
