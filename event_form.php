<?php
require_once __DIR__ . '/includes/bootstrap.php';
$me = require_login();

$id = (int) get('id');
$ev = null;
if ($id) {
    $ev = get_event($id) ?? abort(404);
    can_edit_event($ev) || abort(403);
}
if (!$ev && !can_create_event()) {
    abort(403, 'Only officers, advisers and admins can create events. Ask your council officers to add you to an organization.');
}

$errors = [];
$orgs = orgs_for_staff();
$values = $ev ?? ['org_id' => (int) get('org', (string) ($orgs[0]['id'] ?? 0)), 'title' => '', 'description' => '', 'venue' => '', 'event_date' => '', 'start_time' => '', 'end_time' => ''];

if (is_post()) {
    csrf_check();
    [$data, $errors] = parse_event_input(!$ev);
    $intent = post('intent', 'draft');
    $orgId = $ev ? (int) $ev['org_id'] : $data['org_id'];

    if (!$ev && !in_array($orgId, array_map('intval', array_column($orgs, 'id')), true)) {
        $errors[] = 'Choose one of your organizations.';
    }
    if (!$errors) {
        $startTime = $data['start_time'];
        $endTime = $data['end_time'];
        if (!$ev) {
            $status = is_approver($orgId) ? 'approved' : ($intent === 'submit' ? 'pending' : 'draft');
            $newId = db_insert(
                'INSERT INTO events (org_id, title, description, venue, event_date, start_time, end_time, status, created_by, reviewed_by, reviewed_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$orgId, $data['title'], $data['description'], $data['venue'], $data['event_date'], $startTime, $endTime, $status, $me['id'],
                 $status === 'approved' ? $me['id'] : null, $status === 'approved' ? date('Y-m-d H:i:s') : null]
            );
            flash('success', match ($status) {
                'approved' => 'Event created and approved.',
                'pending'  => 'Event sent to the adviser for approval.',
                default    => 'Draft saved. Send it for approval when it\'s ready.',
            });
            redirect('event_view.php?id=' . $newId);
        }

        $status = status_after_edit($ev);
        if ($intent === 'submit' && $status === 'draft' && !is_approver($orgId)) {
            $status = 'pending';
        }
        $clearReview = $status !== $ev['status'] && in_array($status, ['pending', 'draft'], true);
        db_run(
            'UPDATE events SET title = ?, description = ?, venue = ?, event_date = ?, start_time = ?, end_time = ?, status = ?' .
            ($clearReview ? ', reviewed_by = NULL, reviewed_at = NULL, review_remarks = NULL' : '') . ' WHERE id = ?',
            [$data['title'], $data['description'], $data['venue'], $data['event_date'], $startTime, $endTime, $status, $ev['id']]
        );
        flash('success', $ev['status'] === 'approved' && $status === 'pending'
            ? 'Saved. Because the event was already approved, your adviser needs to approve the changes.'
            : 'Event updated.');
        redirect('event_view.php?id=' . $ev['id']);
    }
    $values = array_merge($values, $_POST);
}

$isApproverHere = $ev ? is_approver((int) $ev['org_id']) : (is_admin() || (bool) approver_org_ids());
render_header($ev ? 'Edit event' : 'New event', 'events');
echo page_head($ev ? 'Edit event' : 'New event', $ev ? $ev['org_name'] : 'Fill in the details. Advisers approve events before students can see them.');
?>
<form method="post" class="form panel narrow" novalidate>
  <?= csrf_field() ?>
  <?php foreach ($errors as $err): ?><div class="flash flash-error" role="alert"><?= e($err) ?></div><?php endforeach; ?>

  <?php if (!$ev): ?>
  <div class="field"><label for="org_id">Organization</label>
    <select id="org_id" name="org_id" required>
      <?php foreach ($orgs as $o): ?><option value="<?= $o['id'] ?>"<?= (int) $values['org_id'] === (int) $o['id'] ? ' selected' : '' ?>><?= e($o['name']) ?></option><?php endforeach; ?>
    </select></div>
  <?php endif; ?>

  <div class="field"><label for="title">Event title</label>
    <input id="title" name="title" required maxlength="150" value="<?= e($values['title']) ?>"></div>
  <div class="field"><label for="description">Description</label>
    <textarea id="description" name="description" rows="5" maxlength="3000" placeholder="What is it, who is it for, what should people bring?"><?= e($values['description']) ?></textarea></div>
  <div class="field"><label for="venue">Venue</label>
    <input id="venue" name="venue" required maxlength="150" value="<?= e($values['venue']) ?>"></div>
  <div class="field-row">
    <div class="field"><label for="event_date">Date</label>
      <input id="event_date" name="event_date" type="date" required <?= $ev ? '' : 'min="' . date('Y-m-d') . '"' ?> value="<?= e($values['event_date']) ?>"></div>
    <div class="field"><label for="start_time">Start time</label>
      <input id="start_time" name="start_time" type="time" value="<?= e(substr((string) $values['start_time'], 0, 5)) ?>"></div>
    <div class="field"><label for="end_time">End time</label>
      <input id="end_time" name="end_time" type="time" value="<?= e(substr((string) $values['end_time'], 0, 5)) ?>"></div>
  </div>

  <div class="form-actions">
    <?php if ($isApproverHere): ?>
      <button class="btn btn-primary" type="submit" name="intent" value="save"><?= $ev ? 'Save changes' : 'Create approved event' ?></button>
      <?php if (!$ev): ?><span class="muted small">Events created by advisers and admins are approved right away.</span><?php endif; ?>
    <?php else: ?>
      <button class="btn btn-primary" type="submit" name="intent" value="submit"><?= $ev ? 'Save and send for approval' : 'Send for approval' ?></button>
      <button class="btn btn-ghost" type="submit" name="intent" value="draft">Save as draft</button>
    <?php endif; ?>
    <a class="btn btn-link" href="<?= e($ev ? url('event_view.php?id=' . $ev['id']) : url('events.php')) ?>">Cancel</a>
  </div>
</form>
<?php render_footer();
