<?php
// positions that need an admin-domain email (faculty/admin accounts); the rest are student accounts
const ADMIN_DOMAIN_POSITIONS = ['adviser', 'co_adviser', 'president', 'vice_president'];

function get_org(int $id): ?array
{
    return db_row('SELECT * FROM organizations WHERE id = ?', [$id]);
}

function all_orgs(): array
{
    return db_all('SELECT * FROM organizations WHERE is_active = 1 ORDER BY type, name');
}

// orgs the person can attach events/tasks/posts to
function orgs_for_staff(): array
{
    $params = [];
    $where = in_clause('id', staff_org_ids(), $params);
    return db_all("SELECT * FROM organizations WHERE is_active = 1 AND $where ORDER BY name", $params);
}

function org_members(int $orgId): array
{
    return db_all(
        "SELECT m.id AS member_id, m.position, m.joined_at, u.id AS user_id, u.full_name, u.email
         FROM org_members m JOIN users u ON u.id = m.user_id
         WHERE m.org_id = ?
         ORDER BY FIELD(m.position,'adviser','co_adviser','president','vice_president','officer','member'), u.full_name",
        [$orgId]
    );
}

function org_leaders(int $orgId): array
{
    return array_values(array_filter(org_members($orgId), fn($m) => $m['position'] !== 'officer' && $m['position'] !== 'member'));
}

function org_count_position(int $orgId, string $position): int
{
    return (int) db_val('SELECT COUNT(*) FROM org_members WHERE org_id = ? AND position = ?', [$orgId, $position]);
}

// every org needs at least one adviser and one co-adviser
function org_missing_advisers(int $orgId): array
{
    $missing = [];
    if (org_count_position($orgId, 'adviser') < 1) $missing[] = 'Adviser';
    if (org_count_position($orgId, 'co_adviser') < 1) $missing[] = 'Co-Adviser';
    return $missing;
}

// positions the current user is allowed to hand out in this org
function assignable_positions(int $orgId): array
{
    if (is_admin()) {
        return array_keys(POSITION_LABELS);
    }
    $pos = position_in($orgId);
    if (in_array($pos, APPROVER_POSITIONS, true)) {
        return ['president', 'vice_president', 'officer', 'member'];
    }
    if (in_array($pos, EXECUTIVE_POSITIONS, true)) {
        return ['officer', 'member'];
    }
    return [];
}

// returns an error string, or null when the seat was saved
function save_member(int $orgId, string $email, string $position): ?string
{
    if (!in_array($position, assignable_positions($orgId), true)) {
        return 'Your role cannot assign that position.';
    }
    $user = find_user_by_email($email);
    if (!$user) {
        return 'No account with that email yet. Ask them to sign up first.';
    }
    if (!$user['is_active']) {
        return 'That account is deactivated.';
    }
    if ($user['role'] === 'system_admin') {
        return 'System admins don\'t need an organization seat.';
    }
    $needsAdminDomain = in_array($position, ADMIN_DOMAIN_POSITIONS, true);
    $kind = email_kind($user['email']);
    if ($needsAdminDomain && $kind !== 'admin') {
        return POSITION_LABELS[$position] . ' must use an @' . ADMIN_DOMAINS[0] . ' email.';
    }
    if (!$needsAdminDomain && $kind !== 'student') {
        return POSITION_LABELS[$position] . ' must use an @' . STUDENT_DOMAINS[0] . ' email.';
    }
    if (in_array($position, EXECUTIVE_POSITIONS, true)) {
        $taken = db_val('SELECT user_id FROM org_members WHERE org_id = ? AND position = ?', [$orgId, $position]);
        if ($taken && (int) $taken !== (int) $user['id']) {
            return 'This organization already has a ' . POSITION_LABELS[$position] . '. Change that seat first.';
        }
    }
    $existing = db_row('SELECT id, position FROM org_members WHERE org_id = ? AND user_id = ?', [$orgId, $user['id']]);
    if ($existing) {
        if (!in_array($existing['position'], assignable_positions($orgId), true)) {
            return 'You cannot change that person\'s current position.';
        }
        if ($existing['position'] !== $position && ($err = guard_last_adviser($orgId, $existing['position']))) {
            return $err;
        }
        db_run('UPDATE org_members SET position = ? WHERE id = ?', [$position, $existing['id']]);
    } else {
        db_run('INSERT INTO org_members (org_id, user_id, position) VALUES (?, ?, ?)', [$orgId, $user['id'], $position]);
    }
    sync_user_role((int) $user['id']);
    return null;
}

function remove_member(int $orgId, int $memberId): ?string
{
    $m = db_row('SELECT * FROM org_members WHERE id = ? AND org_id = ?', [$memberId, $orgId]);
    if (!$m) {
        return 'That member was not found.';
    }
    if (!in_array($m['position'], assignable_positions($orgId), true)) {
        return 'You cannot remove that position.';
    }
    if ($err = guard_last_adviser($orgId, $m['position'])) {
        return $err;
    }
    db_run('DELETE FROM org_members WHERE id = ?', [$memberId]);
    sync_user_role((int) $m['user_id']);
    return null;
}

// blocks removing the only adviser / co-adviser of an org
function guard_last_adviser(int $orgId, string $position): ?string
{
    if (in_array($position, APPROVER_POSITIONS, true) && org_count_position($orgId, $position) <= 1) {
        return 'Every organization needs at least one ' . POSITION_LABELS[$position] . '. Assign a replacement first.';
    }
    return null;
}

// advisers/co-advisers you can pick when creating an org (admin-domain accounts only)
function adviser_candidates(): array
{
    $rows = db_all("SELECT id, full_name, email FROM users WHERE is_active = 1 AND role <> 'system_admin' ORDER BY full_name");
    return array_values(array_filter($rows, fn($r) => email_kind($r['email']) === 'admin'));
}

function org_stats(int $orgId): array
{
    return [
        'members' => (int) db_val('SELECT COUNT(*) FROM org_members WHERE org_id = ?', [$orgId]),
        'events'  => (int) db_val("SELECT COUNT(*) FROM events WHERE org_id = ? AND status = 'approved'", [$orgId]),
        'open_tasks' => (int) db_val(
            "SELECT COUNT(*) FROM tasks t JOIN events e ON e.id = t.event_id
             WHERE e.org_id = ? AND t.approval_status = 'approved' AND t.status <> 'done'",
            [$orgId]
        ),
    ];
}
