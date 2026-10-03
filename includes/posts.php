<?php
const POST_SELECT = "SELECT p.*, o.name AS org_name, o.acronym AS org_acronym, u.full_name AS author_name
    FROM announcements p
    LEFT JOIN organizations o ON o.id = p.org_id
    JOIN users u ON u.id = p.posted_by";

function get_post(int $id): ?array
{
    return db_row(POST_SELECT . ' WHERE p.id = ?', [$id]);
}

// filters: org, status, limit
function list_posts(array $f = []): array
{
    $params = [];
    $where = [];
    if (!is_admin()) {
        $me = (int) current_user()['id'];
        $sql = "p.status = 'published' OR p.posted_by = ?";
        $params[] = $me;
        $ids = array_map('intval', array_keys(my_memberships()));
        if ($ids) {
            $sql .= ' OR ' . in_clause('p.org_id', $ids, $params);
        }
        $where[] = "($sql)";
    }
    if (!empty($f['org'])) {
        $where[] = 'p.org_id = ?';
        $params[] = (int) $f['org'];
    }
    if (!empty($f['status']) && isset(POST_STATUS[$f['status']])) {
        $where[] = 'p.status = ?';
        $params[] = $f['status'];
    }
    $sql = POST_SELECT . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY p.created_at DESC';
    if (!empty($f['limit'])) {
        $sql .= ' LIMIT ' . (int) $f['limit'];
    }
    return db_all($sql, $params);
}

// org_id 0/null = campus-wide, admin only
function can_post_to(?int $orgId): bool
{
    return $orgId ? is_org_staff($orgId) : is_admin();
}

// officers' posts wait for the adviser; President/VP, advisers and admins publish right away
function post_initial_status(?int $orgId): string
{
    if (!$orgId) {
        return 'published';
    }
    return has_override($orgId) ? 'published' : 'pending';
}

function can_review_post(array $p): bool
{
    return $p['status'] === 'pending' && $p['org_id'] && is_approver((int) $p['org_id']);
}

function can_delete_post(array $p): bool
{
    if (!$p['org_id']) {
        return is_admin();
    }
    if (has_override((int) $p['org_id'])) {
        return true;
    }
    return (int) $p['posted_by'] === (int) current_user()['id'] && $p['status'] === 'pending';
}
