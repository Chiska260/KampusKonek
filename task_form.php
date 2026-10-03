<?php
require_once __DIR__ . '/includes/bootstrap.php';
$me = require_login();

$t = null;
if (get('id')) {
    $t = get_task((int) get('id')) ?? abort(404);
    task_visible($t) || abort(404);
    $ev = get_event((int) $t['event_id']);
} else {
    $ev = get_event((int) get('event')) ?? abort(404);
    can_create_task($ev) || abort(403, 'You can only assign tasks inside organizations where you are an officer, adviser, or admin.');
}
$orgId = (int) $ev['org_id'];
$readonly = $t && !can_edit_task($t);
$errors = [];
$values = $t ?? ['title' => '', 'description' => '', 'assigned_to' => 0, 'priority' => 'medium', 'deadline' => '', 'is_restricted' => 0];

if (is_post() && !$readonly) {
    csrf_check();
    [$data, $errors] = parse_task_input($orgId, !$t);
    // only President/VP, advisers and admins can touch the restricted flag
    if (!can_set_restricted($orgId)) {
        $data['is_restricted'] = $t ? (int) $t['is_restricted'] : 0;
    }
    if (!$errors) {
        if (!$t) {
            $approval = task_initial_approval($orgId);
            db_insert(
                'INSERT INTO tasks (event_id, title, description, assigned_to, priority, deadline, approval_status, is_restricted, created_by, reviewed_by, reviewed_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$ev['id'], $data['title'], $data['description'], $data['assigned_to'], $data['priority'], $data['deadline'], $approval, $data['is_restricted'], $me['id'],
                 $approval === 'approved' ? $me['id'] : null, $approval === 'approved' ? date('Y-m-d H:i:s') : null]
            );
            flash('success', $approval === 'approved' ? 'Task assigned.' : 'Task sent to the adviser for approval.');
        } else {
            // an officer's edit goes back to the adviser; override roles keep the current approval
            $approval = has_override($orgId) ? $t['approval_status'] : 'pending';
            $reset = $approval !== $t['approval_status'];
            db_run(
                'UPDATE tasks SET title = ?, description = ?, assigned_to = ?, priority = ?, deadline = ?, is_restricted = ?, approval_status = ?' .
                ($reset ? ', reviewed_by = NULL, reviewed_at = NULL, review_remarks = NULL' : '') . ' WHERE id = ?',
                [$data['title'], $data['description'], $data['assigned_to'], $data['priority'], $data['deadline'], $data['is_restricted'], $approval, $t['id']]
            );
            flash('success', $reset ? 'Task updated and sent back to the adviser.' : 'Task updated.');
        }
        redirect('event_view.php?id=' . $ev['id']);
    }
    $values = array_merge($values, $_POST);
}

$choices = assignee_choices($orgId);
render_header($t ? 'Task' : 'Assign task', 'tasks');
echo page_head($t ? $t['title'] : 'Assign a task', 'For ' . $ev['title'] . ', ' . $ev['org_acronym']);
?>
<div class="task-layout">
<form method="post" class="form panel narrow" novalidate>
  <?= csrf_field() ?>
  <?php foreach ($errors as $err): ?><div class="flash flash-error" role="alert"><?= e($err) ?></div><?php endforeach; ?>

  <?php if ($t && $t['approval_status'] === 'disapproved'): ?>
    <div class="notice notice-red"><strong>Disapproved.</strong> <?= e($t['review_remarks'] ?: 'No reason was added.') ?> Update the task and save to send it back.</div>
  <?php elseif ($t && $t['approval_status'] === 'pending'): ?>
    <div class="notice">Waiting for adviser approval. It starts counting toward progress once approved.</div>
  <?php endif; ?>
  <?php if ($readonly): ?>
    <div class="notice"><?= $t['is_restricted']
        ? 'This is a restricted task. Only the President, VP, advisers, and admins can change it.'
        : 'You can view this task. Only its creator (before approval), the President, VP, advisers, and admins can edit the details.' ?></div>
  <?php endif; ?>

  <fieldset <?= $readonly ? 'disabled' : '' ?>>
    <div class="field"><label for="title">Task</label>
      <input id="title" name="title" required maxlength="150" value="<?= e($values['title']) ?>"></div>
    <div class="field"><label for="description">Details</label>
      <textarea id="description" name="description" rows="4" maxlength="2000"><?= e($values['description']) ?></textarea></div>
    <div class="field"><label for="assigned_to">Assigned to</label>
      <select id="assigned_to" name="assigned_to" required>
        <option value="">Choose a member</option>
        <?php foreach ($choices as $m): ?>
          <option value="<?= $m['user_id'] ?>"<?= (int) $values['assigned_to'] === (int) $m['user_id'] ? ' selected' : '' ?>><?= e($m['full_name']) ?> (<?= e(POSITION_LABELS[$m['position']]) ?>)</option>
        <?php endforeach; ?>
      </select></div>
    <div class="field-row">
      <div class="field"><label for="priority">Priority</label>
        <select id="priority" name="priority">
          <?php foreach (PRIORITY as $k => [$l]): ?><option value="<?= $k ?>"<?= $values['priority'] === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
        </select></div>
      <div class="field"><label for="deadline">Deadline</label>
        <input id="deadline" name="deadline" type="date" required <?= $t ? '' : 'min="' . date('Y-m-d') . '"' ?> value="<?= e($values['deadline']) ?>"></div>
    </div>
    <?php if (can_set_restricted($orgId)): ?>
      <div class="field check"><label><input type="checkbox" name="is_restricted" value="1"<?= !empty($values['is_restricted']) ? ' checked' : '' ?>>
        Restricted task <span class="field-hint inline">Only the President, VP, advisers, and admins can change its status or details.</span></label></div>
    <?php endif; ?>
  </fieldset>

  <div class="form-actions">
    <?php if (!$readonly): ?><button class="btn btn-primary" type="submit"><?= $t ? 'Save changes' : (has_override($orgId) ? 'Assign task' : 'Send for approval') ?></button><?php endif; ?>
    <a class="btn btn-link" href="<?= e(url('event_view.php?id=' . $ev['id'])) ?>">Back to event</a>
  </div>
</form>

<?php if ($t): ?>
<aside class="panel task-side">
  <h2>Status</h2>
  <p><?= status_badge(TASK_STATUS, $t['status']) ?> <?= status_badge(APPROVAL_STATUS, $t['approval_status']) ?> <?= $t['is_restricted'] ? badge('Restricted', 'navy') : '' ?></p>
  <p class="muted small">Due <?= e(fmt_date($t['deadline'], 'l, M j, Y')) ?>. <?= deadline_chip($t) ?></p>
  <?php if (can_update_task_status($t)): ?>
    <form method="post" action="<?= e(url('actions/task_action.php')) ?>" class="status-form big">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= $t['id'] ?>"><input type="hidden" name="do" value="status"><input type="hidden" name="back" value="<?= e(current_path()) ?>">
      <label for="status">Update status</label>
      <select id="status" name="status" data-autosubmit>
        <?php foreach (TASK_STATUS as $k => [$l]): ?><option value="<?= $k ?>"<?= $t['status'] === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
      </select>
    </form>
  <?php elseif ($t['approval_status'] === 'approved'): ?>
    <p class="muted small"><?= $t['is_restricted'] ? 'Restricted: only the President, VP, advisers, and admins can update this.' : 'Only the person it\'s assigned to can update the status.' ?></p>
  <?php endif; ?>
  <dl class="mini-facts">
    <dt>Created by</dt><dd><?= e($t['creator_name']) ?></dd>
    <?php if ($t['completed_at']): ?><dt>Completed</dt><dd><?= e(fmt_datetime($t['completed_at'])) ?></dd><?php endif; ?>
  </dl>
  <?php if (can_review_task($t)): ?><div class="review-bar"><strong>Your decision</strong><?= review_controls('task_action.php', (int) $t['id'], 'approve', 'disapprove') ?></div><?php endif; ?>
  <?php if (can_delete_task($t)): ?><?= action_form('task_action.php', ['id' => $t['id'], 'do' => 'delete'], 'Delete task', 'btn btn-danger-ghost btn-sm', 'Delete this task?') ?><?php endif; ?>
</aside>
<?php endif; ?>
</div>
<?php render_footer();
