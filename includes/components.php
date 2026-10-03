<?php
// small reusable pieces of markup used across pages

function icon(string $name, int $size = 20): string
{
    static $paths = [
        'dashboard' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'events'    => '<path d="M5 21V4m0 0h12l-2.5 4L17 12H5"/>',
        'calendar'  => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
        'tasks'     => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="m8 12 3 3 5-6"/>',
        'approvals' => '<path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6l-8-3Z"/><path d="m9 12 2 2 4-4"/>',
        'posts'     => '<path d="M3 11v2a1 1 0 0 0 1 1h3l8 4V6L7 10H4a1 1 0 0 0-1 1Z"/><path d="M18 9a4 4 0 0 1 0 6"/>',
        'orgs'      => '<path d="M4 21V5l8-2v18"/><path d="M12 8h8v13"/><path d="M8 8h1M8 12h1M8 16h1M16 12h1M16 16h1"/>',
        'users'     => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c.5-3.5 3-5.5 6.5-5.5s6 2 6.5 5.5"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18 14.8c2 .6 3.2 2.3 3.5 5.2"/>',
        'user'      => '<circle cx="12" cy="8" r="4"/><path d="M4 21c.7-4 3.8-6 8-6s7.3 2 8 6"/>',
        'logout'    => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/>',
        'menu'      => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'plus'      => '<path d="M12 5v14M5 12h14"/>',
        'lock'      => '<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>',
        'clock'     => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'pin'       => '<path d="M12 21s7-6.2 7-11a7 7 0 0 0-14 0c0 4.8 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/>',
        'left'      => '<path d="m15 18-6-6 6-6"/>',
        'right'     => '<path d="m9 18 6-6-6-6"/>',
        'check'     => '<path d="m5 12 5 5 9-10"/>',
        'close'     => '<path d="M6 6l12 12M18 6 6 18"/>',
    ];
    $p = $paths[$name] ?? '';
    return '<svg class="icon" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
}

// where we are right now, as a relative path (used for "go back here after the action")
function current_path(): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (BASE_URL !== '' && str_starts_with($uri, BASE_URL)) {
        $uri = substr($uri, strlen(BASE_URL));
    }
    return ltrim($uri, '/');
}

function page_head(string $title, string $sub = '', string $actions = ''): string
{
    $h = '<div class="page-head"><div><h1>' . e($title) . '</h1>';
    if ($sub !== '') {
        $h .= '<p class="sub">' . e($sub) . '</p>';
    }
    $h .= '</div>';
    if ($actions !== '') {
        $h .= '<div class="page-actions">' . $actions . '</div>';
    }
    return $h . '</div>';
}

function stat_tile(string $label, $value, string $note = '', string $tone = ''): string
{
    return '<div class="stat ' . e($tone) . '"><div class="stat-value">' . e($value) . '</div><div class="stat-label">' . e($label) . '</div>' .
        ($note !== '' ? '<div class="stat-note">' . e($note) . '</div>' : '') . '</div>';
}

function empty_state(string $title, string $text = '', string $actionHtml = ''): string
{
    return '<div class="empty-state"><h3>' . e($title) . '</h3>' . ($text !== '' ? '<p>' . e($text) . '</p>' : '') . $actionHtml . '</div>';
}

function event_card(array $ev, bool $showStatus = true): string
{
    $ts = strtotime($ev['event_date']);
    $href = url('event_view.php?id=' . (int) $ev['id']);
    $past = $ev['event_date'] < date('Y-m-d');
    $h  = '<article class="event-card' . ($ev['status'] === 'cancelled' ? ' is-cancelled' : '') . '">';
    $h .= '<div class="date-tab" aria-hidden="true"><span class="m">' . date('M', $ts) . '</span><span class="d">' . date('j', $ts) . '</span></div>';
    $h .= '<div class="event-main"><div class="event-tags"><span class="org-tag">' . e($ev['org_acronym']) . '</span>';
    if ($showStatus && $ev['status'] !== 'approved') {
        $h .= status_badge(EVENT_STATUS, $ev['status']);
    }
    if ($ev['status'] === 'approved') {
        $h .= '<span class="when' . ($past ? '' : ' soon') . '">' . e(days_label($ev['event_date'])) . '</span>';
    }
    $h .= '</div><h3><a href="' . e($href) . '">' . e($ev['title']) . '</a></h3>';
    $h .= '<p class="meta"><span>' . icon('pin', 15) . e($ev['venue']) . '</span><span>' . icon('clock', 15) . e(fmt_time_range($ev['start_time'], $ev['end_time'])) . '</span></p>';
    if (in_array($ev['status'], ['approved', 'pending', 'draft'], true) && isset($ev['tasks_total']) && is_org_member((int) $ev['org_id'])) {
        $h .= progress_bar(progress_of($ev));
    }
    return $h . '</div></article>';
}

// <canvas> + legend; app.js draws it. $type is 'doughnut' or 'bars'
function chart_block(string $id, string $type, array $labels, array $values, array $colors): string
{
    $cfg = e(json_encode(['type' => $type, 'labels' => $labels, 'values' => $values, 'colors' => $colors]));
    $h = '<div class="chart-block"><canvas id="' . e($id) . '" data-chart="' . $cfg . '" width="220" height="220" role="img" aria-label="Chart: ' . e(implode(', ', array_map(fn($l, $v) => "$l $v", $labels, $values))) . '"></canvas><ul class="legend">';
    foreach ($labels as $i => $label) {
        $h .= '<li><span class="dot" style="background:' . e($colors[$i]) . '"></span>' . e($label) . '<strong>' . (int) $values[$i] . '</strong></li>';
    }
    return $h . '</ul></div>';
}

// one small POST form with csrf, used for single-click actions
function action_form(string $file, array $fields, string $label, string $class = 'btn btn-ghost btn-sm', string $confirm = ''): string
{
    $h = '<form method="post" action="' . e(url('actions/' . $file)) . '" class="inline-form"' . ($confirm !== '' ? ' data-confirm="' . e($confirm) . '"' : '') . '>' . csrf_field();
    foreach ($fields as $k => $v) {
        $h .= '<input type="hidden" name="' . e($k) . '" value="' . e($v) . '">';
    }
    return $h . '<button type="submit" class="' . e($class) . '">' . e($label) . '</button></form>';
}

// approve button + a "disapprove with reason" drop-down
function review_controls(string $file, int $id, string $approveDo, string $rejectDo, string $approveLabel = 'Approve', string $rejectLabel = 'Disapprove'): string
{
    $back = current_path();
    $h  = '<div class="review-controls">';
    $h .= action_form($file, ['id' => $id, 'do' => $approveDo, 'back' => $back], $approveLabel, 'btn btn-success btn-sm');
    $h .= '<details class="reject"><summary class="btn btn-ghost btn-sm">' . e($rejectLabel) . '</summary>';
    $h .= '<form method="post" action="' . e(url('actions/' . $file)) . '" class="reject-form">' . csrf_field();
    $h .= '<input type="hidden" name="id" value="' . $id . '"><input type="hidden" name="do" value="' . e($rejectDo) . '"><input type="hidden" name="back" value="' . e($back) . '">';
    $h .= '<label class="sr-only" for="rm' . $id . $rejectDo . '">Reason</label>';
    $h .= '<textarea id="rm' . $id . $rejectDo . '" name="remarks" rows="2" maxlength="500" placeholder="Tell them what to fix (required)" required></textarea>';
    $h .= '<button type="submit" class="btn btn-danger btn-sm">' . e($rejectLabel) . ' and send reason</button></form></details></div>';
    return $h;
}

// one line of "reviewed by ..." text under approved/disapproved items
function review_note(array $row): string
{
    if (empty($row['reviewer_name']) && empty($row['review_remarks'])) {
        return '';
    }
    $h = '<div class="review-note">';
    if (!empty($row['reviewer_name'])) {
        $h .= '<span>Reviewed by ' . e($row['reviewer_name']) . (!empty($row['reviewed_at']) ? ' on ' . e(fmt_date($row['reviewed_at'])) : '') . '</span>';
    }
    if (!empty($row['review_remarks'])) {
        $h .= '<p>“' . e($row['review_remarks']) . '”</p>';
    }
    return $h . '</div>';
}

function task_table(array $tasks, array $opt = []): string
{
    $showEvent = $opt['showEvent'] ?? true;
    $showWho = $opt['showAssignee'] ?? true;
    $showPriority = $opt['showPriority'] ?? true;
    if (!$tasks) {
        return empty_state($opt['emptyTitle'] ?? 'No tasks found', $opt['emptyText'] ?? 'Try clearing a filter.');
    }
    $back = current_path();
    $h = '<div class="table-wrap"><table class="table task-table"><thead><tr><th>Task</th>' . ($showEvent ? '<th>Event</th>' : '') .
        ($showWho ? '<th>Assigned to</th>' : '') . '<th>Deadline</th>' . ($showPriority ? '<th>Priority</th>' : '') . '<th>Status</th><th><span class="sr-only">Actions</span></th></tr></thead><tbody>';
    foreach ($tasks as $t) {
        $state = task_state($t);
        $h .= '<tr class="task-' . e($state) . '"><td data-label="Task"><a class="row-title" href="' . e(url('task_form.php?id=' . (int) $t['id'])) . '">' . e($t['title']) . '</a>';
        if ($t['is_restricted']) {
            $h .= ' <span class="restricted" title="Restricted: only the President, VP, advisers and admins can change this">' . icon('lock', 14) . '<span class="sr-only">Restricted task</span></span>';
        }
        if ($t['approval_status'] !== 'approved') {
            $h .= '<div class="row-sub">' . status_badge(APPROVAL_STATUS, $t['approval_status']) . '</div>';
        }
        $h .= '</td>';
        if ($showEvent) {
            $h .= '<td data-label="Event"><a href="' . e(url('event_view.php?id=' . (int) $t['event_id'])) . '">' . e($t['event_title']) . '</a><div class="row-sub">' . e($t['org_acronym']) . '</div></td>';
        }
        if ($showWho) {
            $h .= '<td data-label="Assigned to">' . ($t['assignee_name'] ? '<span class="person">' . avatar($t['assignee_name'], 'sm') . e($t['assignee_name']) . '</span>' : '<span class="muted">Unassigned</span>') . '</td>';
        }
        $h .= '<td data-label="Deadline">' . deadline_chip($t) . '</td>';
        if ($showPriority) {
            $h .= '<td data-label="Priority">' . status_badge(PRIORITY, $t['priority']) . '</td>';
        }
        $h .= '<td data-label="Status">';
        if (can_update_task_status($t)) {
            $h .= '<form method="post" action="' . e(url('actions/task_action.php')) . '" class="inline-form status-form">' . csrf_field() .
                '<input type="hidden" name="id" value="' . (int) $t['id'] . '"><input type="hidden" name="do" value="status"><input type="hidden" name="back" value="' . e($back) . '">' .
                '<label class="sr-only" for="st' . (int) $t['id'] . '">Status</label><select id="st' . (int) $t['id'] . '" name="status" data-autosubmit>';
            foreach (TASK_STATUS as $k => [$label]) {
                $h .= '<option value="' . e($k) . '"' . ($t['status'] === $k ? ' selected' : '') . '>' . e($label) . '</option>';
            }
            $h .= '</select><noscript><button class="btn btn-ghost btn-sm">Save</button></noscript></form>';
        } else {
            $h .= status_badge(TASK_STATUS, $t['status']);
        }
        $h .= '</td><td class="row-actions" data-label="">';
        if (can_review_task($t)) {
            $h .= review_controls('task_action.php', (int) $t['id'], 'approve', 'disapprove');
        }
        $h .= '</td></tr>';
    }
    return $h . '</tbody></table></div>';
}
