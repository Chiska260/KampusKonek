<?php
// All the "who can do what" rules live here (and in the can_* functions of
// events.php / tasks.php / posts.php). Rights come from the seat a person holds
// in an organization (org_members.position), not from users.role alone.

const APPROVER_POSITIONS  = ['adviser', 'co_adviser'];
const EXECUTIVE_POSITIONS = ['president', 'vice_president'];
const STAFF_POSITIONS     = ['adviser', 'co_adviser', 'president', 'vice_president', 'officer'];

function is_admin(): bool
{
    $u = current_user();
    return $u && $u['role'] === 'system_admin';
}

function my_memberships(): array
{
    static $cache = [];
    $u = current_user();
    if (!$u) {
        return [];
    }
    if (!isset($cache[$u['id']])) {
        $rows = db_all('SELECT org_id, position FROM org_members WHERE user_id = ?', [$u['id']]);
        $cache[$u['id']] = array_column($rows, 'position', 'org_id');
    }
    return $cache[$u['id']];
}

function position_in(int $orgId): ?string
{
    return my_memberships()[$orgId] ?? null;
}

function is_approver(int $orgId): bool
{
    return is_admin() || in_array(position_in($orgId), APPROVER_POSITIONS, true);
}

function is_executive(int $orgId): bool
{
    return in_array(position_in($orgId), EXECUTIVE_POSITIONS, true);
}

// admin, advisers and the President/VP can modify restricted things
function has_override(int $orgId): bool
{
    return is_approver($orgId) || is_executive($orgId);
}

// can draft events, post updates and assign tasks for this org
function is_org_staff(int $orgId): bool
{
    return is_admin() || in_array(position_in($orgId), STAFF_POSITIONS, true);
}

function is_org_member(int $orgId): bool
{
    return is_admin() || position_in($orgId) !== null;
}

// null means "all orgs" (system admin)
function org_ids_where(array $positions): ?array
{
    if (is_admin()) {
        return null;
    }
    $ids = [];
    foreach (my_memberships() as $orgId => $pos) {
        if (in_array($pos, $positions, true)) {
            $ids[] = (int) $orgId;
        }
    }
    return $ids;
}

function approver_org_ids(): ?array { return org_ids_where(APPROVER_POSITIONS); }
function staff_org_ids(): ?array    { return org_ids_where(STAFF_POSITIONS); }
function member_org_ids(): ?array   { return is_admin() ? null : array_map('intval', array_keys(my_memberships())); }

function is_any_approver(): bool
{
    return is_admin() || (bool) approver_org_ids();
}

function has_any_staff_seat(): bool
{
    return is_admin() || (bool) staff_org_ids();
}

// which dashboard a person lands on
function dashboard_kind(): string
{
    $u = current_user();
    if ($u['role'] === 'system_admin') return 'admin';
    if ($u['role'] === 'adviser' || approver_org_ids()) return 'adviser';
    if ($u['role'] === 'officer' || my_memberships()) return 'officer';
    return 'student';
}

// events: everyone sees approved ones, org members also see their own drafts/pending
function event_visibility(array &$params, string $a = 'e'): string
{
    if (is_admin()) {
        return '1=1';
    }
    $sql = "$a.status = 'approved'";
    $ids = array_map('intval', array_keys(my_memberships()));
    if ($ids) {
        $sql .= ' OR ' . in_clause("$a.org_id", $ids, $params);
    }
    return "($sql)";
}

// tasks: only people in the org (plus the assignee) can see them
function task_visibility(array &$params, string $t = 't', string $e = 'e'): string
{
    if (is_admin()) {
        return '1=1';
    }
    $params[] = current_user()['id'];
    $sql = "$t.assigned_to = ?";
    $ids = array_map('intval', array_keys(my_memberships()));
    if ($ids) {
        $sql .= ' OR ' . in_clause("$e.org_id", $ids, $params);
    }
    return "($sql)";
}
