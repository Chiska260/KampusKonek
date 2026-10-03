<?php
const EVENT_SELECT = "SELECT e.*, o.name AS org_name, o.acronym AS org_acronym, u.full_name AS creator_name,
        (SELECT COUNT(*) FROM tasks t WHERE t.event_id = e.id AND t.approval_status = 'approved') AS tasks_total,
        (SELECT COUNT(*) FROM tasks t WHERE t.event_id = e.id AND t.approval_status = 'approved' AND t.status = 'done') AS tasks_done,
        (SELECT COUNT(*) FROM tasks t WHERE t.event_id = e.id AND t.approval_status = 'approved' AND t.status = 'in_progress') AS tasks_doing
    FROM events e
    JOIN organizations o ON o.id = e.org_id
    JOIN users u ON u.id = e.created_by";

function get_event(int $id): ?array
{
    $ev = db_row(
        "SELECT e.*, o.name AS org_name, o.acronym AS org_acronym, u.full_name AS creator_name, r.full_name AS reviewer_name
         FROM events e
         JOIN organizations o ON o.id = e.org_id
         JOIN users u ON u.id = e.created_by
         LEFT JOIN users r ON r.id = e.reviewed_by
         WHERE e.id = ?",
        [$id]
    );
    return $ev;
}

function event_visible(array $ev): bool
{
    return $ev['status'] === 'approved' || is_org_member((int) $ev['org_id']);
}

// progress only counts approved tasks
function event_progress(int $eventId): array
{
    $row = db_row(
        "SELECT COUNT(*) AS total,
                COALESCE(SUM(status = 'done'), 0) AS done,
                COALESCE(SUM(status = 'in_progress'), 0) AS doing
         FROM tasks WHERE event_id = ? AND approval_status = 'approved'",
        [$eventId]
    );
    return array_map('intval', $row);
}

function progress_of(array $ev): array
{
    return ['total' => $ev['tasks_total'], 'done' => $ev['tasks_done'], 'doing' => $ev['tasks_doing']];
}

// filters: q, org, status, when (upcoming|past|all), limit
function list_events(array $f = []): array
{
    $params = [];
    $where = [event_visibility($params)];
    if (!empty($f['q'])) {
        $where[] = '(e.title LIKE ? OR e.venue LIKE ?)';
        $params[] = '%' . $f['q'] . '%';
        $params[] = '%' . $f['q'] . '%';
    }
    if (!empty($f['org'])) {
        $where[] = 'e.org_id = ?';
        $params[] = (int) $f['org'];
    }
    if (!empty($f['status']) && isset(EVENT_STATUS[$f['status']])) {
        $where[] = 'e.status = ?';
        $params[] = $f['status'];
    }
    $when = $f['when'] ?? 'upcoming';
    $order = 'e.event_date ASC, e.start_time ASC';
    if ($when === 'upcoming') {
        $where[] = 'e.event_date >= CURDATE()';
    } elseif ($when === 'past') {
        $where[] = 'e.event_date < CURDATE()';
        $order = 'e.event_date DESC, e.start_time DESC';
    }
    $sql = EVENT_SELECT . ' WHERE ' . implode(' AND ', $where) . " ORDER BY $order";
    if (!empty($f['limit'])) {
        $sql .= ' LIMIT ' . (int) $f['limit'];
    }
    return db_all($sql, $params);
}

// ---------- permissions ----------
function can_create_event(): bool
{
    return has_any_staff_seat();
}

function is_event_creator(array $ev): bool
{
    return (int) $ev['created_by'] === (int) current_user()['id'];
}

function can_edit_event(array $ev): bool
{
    $org = (int) $ev['org_id'];
    if (has_override($org)) {
        return true;
    }
    return position_in($org) === 'officer' && is_event_creator($ev) && in_array($ev['status'], ['draft', 'disapproved'], true);
}

function can_submit_event(array $ev): bool
{
    $org = (int) $ev['org_id'];
    if (!in_array($ev['status'], ['draft', 'disapproved'], true) || !is_org_staff($org)) {
        return false;
    }
    return has_override($org) || is_event_creator($ev);
}

function can_review_event(array $ev): bool
{
    return $ev['status'] === 'pending' && is_approver((int) $ev['org_id']);
}

function can_cancel_event(array $ev): bool
{
    return in_array($ev['status'], ['approved', 'pending'], true) && has_override((int) $ev['org_id']);
}

function can_delete_event(array $ev): bool
{
    $org = (int) $ev['org_id'];
    if (has_override($org)) {
        return true;
    }
    return position_in($org) === 'officer' && is_event_creator($ev) && $ev['status'] === 'draft';
}

// what status an event lands in after someone edits it
function status_after_edit(array $ev): string
{
    $org = (int) $ev['org_id'];
    if (is_approver($org)) {
        return $ev['status'];                    // advisers/admin edits don't need re-approval
    }
    return match ($ev['status']) {
        'approved'    => 'pending',              // changed after approval, adviser checks again
        'disapproved' => 'draft',
        default       => $ev['status'],
    };
}

// reads and checks the event form. returns [cleanData, errors]
function parse_event_input(bool $isNew): array
{
    $d = [
        'org_id'      => (int) post('org_id'),
        'title'       => post('title'),
        'description' => post('description'),
        'venue'       => post('venue'),
        'event_date'  => post('event_date'),
        'start_time'  => post('start_time'),
        'end_time'    => post('end_time'),
    ];
    $err = [];
    if (mb_strlen($d['title']) < 3 || mb_strlen($d['title']) > 150) $err[] = 'Title must be 3 to 150 characters.';
    if (mb_strlen($d['venue']) < 2 || mb_strlen($d['venue']) > 150) $err[] = 'Add a venue (2 to 150 characters).';
    if (mb_strlen($d['description']) > 3000) $err[] = 'Description is too long (3000 characters max).';
    if (!valid_date($d['event_date'])) {
        $err[] = 'Pick a valid event date.';
    } elseif ($isNew && $d['event_date'] < date('Y-m-d')) {
        $err[] = 'The event date can\'t be in the past.';
    }
    if ($d['start_time'] !== '' && !valid_time($d['start_time'])) $err[] = 'Start time is not valid.';
    if ($d['end_time'] !== '' && !valid_time($d['end_time'])) $err[] = 'End time is not valid.';
    if ($d['end_time'] !== '' && $d['start_time'] === '') $err[] = 'Add a start time before the end time.';
    if ($d['start_time'] !== '' && $d['end_time'] !== '' && $d['end_time'] <= $d['start_time']) $err[] = 'End time must be after the start time.';
    $d['start_time'] = $d['start_time'] === '' ? null : $d['start_time'];
    $d['end_time']   = $d['end_time'] === '' ? null : $d['end_time'];
    return [$d, $err];
}
