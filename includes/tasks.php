<?php
const TASK_SELECT = "SELECT t.*, e.title AS event_title, e.org_id, e.event_date, e.status AS event_status,
        o.name AS org_name, o.acronym AS org_acronym,
        a.full_name AS assignee_name, c.full_name AS creator_name
    FROM tasks t
    JOIN events e ON e.id = t.event_id
    JOIN organizations o ON o.id = e.org_id
    LEFT JOIN users a ON a.id = t.assigned_to
    JOIN users c ON c.id = t.created_by";

function get_task(int $id): ?array
{
    return db_row(TASK_SELECT . ' WHERE t.id = ?', [$id]);
}

function task_visible(array $t): bool
{
    return is_org_member((int) $t['org_id']) || (int) $t['assigned_to'] === (int) current_user()['id'];
}

// filters: q, org, event, assignee ('me' or user id), status, priority, approval, overdue, limit
function list_tasks(array $f = []): array
{
    $params = [];
    $where = [task_visibility($params)];
    $me = (int) current_user()['id'];

    if (!empty($f['q'])) {
        $where[] = '(t.title LIKE ? OR t.description LIKE ?)';
        $params[] = '%' . $f['q'] . '%';
        $params[] = '%' . $f['q'] . '%';
    }
    if (!empty($f['org'])) {
        $where[] = 'e.org_id = ?';
        $params[] = (int) $f['org'];
    }
    if (!empty($f['event'])) {
        $where[] = 't.event_id = ?';
        $params[] = (int) $f['event'];
    }
    if (!empty($f['assignee'])) {
        $where[] = 't.assigned_to = ?';
        $params[] = $f['assignee'] === 'me' ? $me : (int) $f['assignee'];
    }
    if (!empty($f['status']) && isset(TASK_STATUS[$f['status']])) {
        $where[] = 't.status = ?';
        $params[] = $f['status'];
    }
    if (!empty($f['priority']) && isset(PRIORITY[$f['priority']])) {
        $where[] = 't.priority = ?';
        $params[] = $f['priority'];
    }
    if (!empty($f['approval']) && isset(APPROVAL_STATUS[$f['approval']])) {
        $where[] = 't.approval_status = ?';
        $params[] = $f['approval'];
    }
    if (!empty($f['overdue'])) {
        $where[] = "t.status <> 'done' AND t.approval_status = 'approved' AND t.deadline < CURDATE()";
    }
    $sql = TASK_SELECT . ' WHERE ' . implode(' AND ', $where) .
        " ORDER BY (t.status = 'done'), t.deadline ASC, FIELD(t.priority,'high','medium','low')";
    if (!empty($f['limit'])) {
        $sql .= ' LIMIT ' . (int) $f['limit'];
    }
    return db_all($sql, $params);
}

// 'done' | 'overdue' | 'soon' (due within 2 days) | 'ok' | 'waiting' (not approved yet)
function task_state(array $t): string
{
    if ($t['approval_status'] !== 'approved') return 'waiting';
    if ($t['status'] === 'done') return 'done';
    $n = days_until($t['deadline']);
    if ($n < 0) return 'overdue';
    return $n <= 2 ? 'soon' : 'ok';
}

function deadline_chip(array $t): string
{
    $state = task_state($t);
    $tone = ['overdue' => 'red', 'soon' => 'amber', 'done' => 'green'][$state] ?? 'muted';
    $text = fmt_date($t['deadline'], 'M j');
    if ($state === 'overdue') $text .= ' (overdue)';
    elseif ($state === 'soon') $text .= ' (' . strtolower(days_label($t['deadline'])) . ')';
    return '<span class="deadline deadline-' . $tone . '">' . e($text) . '</span>';
}

// ---------- permissions ----------
function can_create_task(array $ev): bool
{
    return $ev['status'] !== 'cancelled' && is_org_staff((int) $ev['org_id']);
}

function is_task_creator(array $t): bool
{
    return (int) $t['created_by'] === (int) current_user()['id'];
}

// title, description, deadline, assignee, priority
function can_edit_task(array $t): bool
{
    $org = (int) $t['org_id'];
    if (has_override($org)) {
        return true;
    }
    return position_in($org) === 'officer' && is_task_creator($t) && !$t['is_restricted']
        && in_array($t['approval_status'], ['pending', 'disapproved'], true);
}

// only override roles can flip the restricted flag
function can_set_restricted(int $orgId): bool
{
    return has_override($orgId);
}

function can_update_task_status(array $t): bool
{
    if ($t['approval_status'] !== 'approved') {
        return false;
    }
    if (has_override((int) $t['org_id'])) {
        return true;
    }
    return (int) $t['assigned_to'] === (int) current_user()['id'] && !$t['is_restricted'];
}

function can_review_task(array $t): bool
{
    return $t['approval_status'] === 'pending' && is_approver((int) $t['org_id']);
}

function can_delete_task(array $t): bool
{
    if (has_override((int) $t['org_id'])) {
        return true;
    }
    return is_task_creator($t) && $t['approval_status'] === 'pending' && !$t['is_restricted'];
}

// people a task can be given to: student seats of that org
function assignee_choices(int $orgId): array
{
    return array_values(array_filter(org_members($orgId), fn($m) => !in_array($m['position'], APPROVER_POSITIONS, true)));
}

// task created by someone who is allowed to approve skips the approval queue
function task_initial_approval(int $orgId): string
{
    return has_override($orgId) ? 'approved' : 'pending';
}

function parse_task_input(int $orgId, bool $isNew): array
{
    $d = [
        'title'       => post('title'),
        'description' => post('description'),
        'priority'    => post('priority', 'medium'),
        'deadline'    => post('deadline'),
        'assigned_to' => (int) post('assigned_to'),
        'is_restricted' => isset($_POST['is_restricted']) ? 1 : 0,
    ];
    $err = [];
    if (mb_strlen($d['title']) < 3 || mb_strlen($d['title']) > 150) $err[] = 'Task title must be 3 to 150 characters.';
    if (mb_strlen($d['description']) > 2000) $err[] = 'Description is too long (2000 characters max).';
    if (!isset(PRIORITY[$d['priority']])) $err[] = 'Pick a priority.';
    if (!valid_date($d['deadline'])) {
        $err[] = 'Pick a valid deadline.';
    } elseif ($isNew && $d['deadline'] < date('Y-m-d')) {
        $err[] = 'The deadline can\'t be in the past.';
    }
    $allowed = array_column(assignee_choices($orgId), 'user_id');
    if ($d['assigned_to'] === 0) {
        $err[] = 'Choose who this task is for.';
    } elseif (!in_array($d['assigned_to'], array_map('intval', $allowed), true)) {
        $err[] = 'That person is not a member of this organization.';
    }
    return [$d, $err];
}
