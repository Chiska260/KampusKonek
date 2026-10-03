<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$me = require_login();
if (!is_post()) {
    redirect('tasks.php');
}
csrf_check();

$t = get_task((int) post('id')) ?? abort(404);
$do = post('do');
$back = safe_back(post('back'), 'tasks.php');
$remarks = mb_substr(post('remarks'), 0, 500);

switch ($do) {
    case 'status':
        can_update_task_status($t) || abort(403);
        $status = post('status');
        isset(TASK_STATUS[$status]) || abort(404);
        db_run('UPDATE tasks SET status = ?, completed_at = ? WHERE id = ?', [$status, $status === 'done' ? date('Y-m-d H:i:s') : null, $t['id']]);
        flash('success', 'Task marked as ' . strtolower(TASK_STATUS[$status][0]) . '.');
        break;

    case 'approve':
        can_review_task($t) || abort(403);
        db_run("UPDATE tasks SET approval_status = 'approved', reviewed_by = ?, reviewed_at = NOW(), review_remarks = NULLIF(?, '') WHERE id = ?", [$me['id'], $remarks, $t['id']]);
        flash('success', 'Task approved. It now counts toward the event progress.');
        break;

    case 'disapprove':
        can_review_task($t) || abort(403);
        if ($remarks === '') {
            flash('error', 'Add a short reason so the officer knows what to change.');
            break;
        }
        db_run("UPDATE tasks SET approval_status = 'disapproved', reviewed_by = ?, reviewed_at = NOW(), review_remarks = ? WHERE id = ?", [$me['id'], $remarks, $t['id']]);
        flash('success', 'Task disapproved with your reason.');
        break;

    case 'delete':
        can_delete_task($t) || abort(403);
        db_run('DELETE FROM tasks WHERE id = ?', [$t['id']]);
        flash('success', 'Task deleted.');
        redirect('event_view.php?id=' . $t['event_id']);

    default:
        abort(404);
}
redirect($back);
