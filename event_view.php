<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$ev = get_event((int) get('id'));
if (!$ev || !event_visible($ev)) {
    abort(404);
}
$orgId = (int) $ev['org_id'];
$isMember = is_org_member($orgId);
$ev['reviewer_name'] = $ev['reviewer_name'] ?? null;

render_header($ev['title'], 'events');

$actions = '';
if (can_edit_event($ev)) {
    $actions .= '<a class="btn btn-ghost" href="' . e(url('event_form.php?id=' . $ev['id'])) . '">Edit</a>';
}
if (can_submit_event($ev)) {
    $actions .= action_form('event_action.php', ['id' => $ev['id'], 'do' => 'submit', 'back' => current_path()], $ev['status'] === 'disapproved' ? 'Resubmit for approval' : 'Send for approval', 'btn btn-primary');
}
if (can_cancel_event($ev)) {
    $actions .= action_form('event_action.php', ['id' => $ev['id'], 'do' => 'cancel', 'back' => current_path()], 'Cancel event', 'btn btn-ghost', 'Cancel this event? Students will no longer see it as approved.');
}
if (can_delete_event($ev)) {
    $actions .= action_form('event_action.php', ['id' => $ev['id'], 'do' => 'delete'], 'Delete', 'btn btn-danger-ghost', 'Delete this event and all of its tasks? This cannot be undone.');
}
echo page_head($ev['title'], '', $actions);
?>
<div class="event-detail">
  <div class="event-detail-main">
    <section class="panel">
      <div class="event-tags"><a class="org-tag" href="<?= e(url('org_view.php?id=' . $orgId)) ?>"><?= e($ev['org_acronym']) ?></a><?= status_badge(EVENT_STATUS, $ev['status']) ?></div>
      <?php if ($ev['status'] === 'pending' && !is_approver($orgId)): ?>
        <p class="notice">Waiting for the adviser. You'll see their decision here.</p>
      <?php endif; ?>
      <?php if ($ev['status'] === 'disapproved' && $isMember): ?>
        <div class="notice notice-red"><strong>Disapproved.</strong> <?= e($ev['review_remarks'] ?: 'No reason was added.') ?> Edit the event and resubmit it.</div>
      <?php elseif ($isMember && $ev['status'] === 'approved' && $ev['review_remarks']): ?>
        <div class="notice notice-green"><strong>Adviser note:</strong> <?= e($ev['review_remarks']) ?></div>
      <?php endif; ?>
      <p class="event-desc"><?= nl2br(e($ev['description'] ?: 'No description yet.')) ?></p>
      <?php if (can_review_event($ev)): ?>
        <div class="review-bar"><strong>Your decision</strong><?= review_controls('event_action.php', (int) $ev['id'], 'approve', 'disapprove') ?></div>
      <?php endif; ?>
    </section>

    <?php if ($isMember): ?>
    <?php $progress = event_progress((int) $ev['id']); $tasks = list_tasks(['event' => $ev['id']]); ?>
    <section class="panel">
      <div class="panel-head"><h2>Task progress</h2>
        <?php if (can_create_task($ev)): ?><a class="btn btn-primary btn-sm" href="<?= e(url('task_form.php?event=' . $ev['id'])) ?>"><?= icon('plus', 16) ?>Assign task</a><?php endif; ?></div>
      <?= progress_bar($progress) ?>
      <?= task_table($tasks, ['showEvent' => false, 'showPriority' => false, 'emptyTitle' => 'No tasks yet', 'emptyText' => 'Assign tasks to committee members so everyone knows who is doing what.']) ?>
    </section>
    <?php endif; ?>
  </div>

  <aside class="event-detail-side">
    <div class="panel facts">
      <div class="date-tab big" aria-hidden="true"><span class="m"><?= e(date('M', strtotime($ev['event_date']))) ?></span><span class="d"><?= e(date('j', strtotime($ev['event_date']))) ?></span></div>
      <dl>
        <dt>Date</dt><dd><?= e(fmt_date($ev['event_date'], 'l, F j, Y')) ?><br><small class="muted"><?= e(days_label($ev['event_date'])) ?></small></dd>
        <dt>Time</dt><dd><?= e(fmt_time_range($ev['start_time'], $ev['end_time'])) ?></dd>
        <dt>Venue</dt><dd><?= e($ev['venue']) ?></dd>
        <dt>Organization</dt><dd><a href="<?= e(url('org_view.php?id=' . $orgId)) ?>"><?= e($ev['org_name']) ?></a></dd>
        <?php if ($isMember): ?><dt>Created by</dt><dd><?= e($ev['creator_name']) ?></dd><?php endif; ?>
        <?php if ($ev['reviewer_name'] && $isMember): ?><dt>Reviewed by</dt><dd><?= e($ev['reviewer_name']) ?><br><small class="muted"><?= e(fmt_date($ev['reviewed_at'])) ?></small></dd><?php endif; ?>
      </dl>
    </div>
    <!-- [image here] -->
    <div class="img-slot flyer"><span>[image here]</span></div>
  </aside>
</div>
<?php render_footer();
