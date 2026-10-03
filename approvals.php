<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
is_any_approver() || abort(403, 'Only advisers, co-advisers, and system admins can review requests.');

$ids = approver_org_ids();
$p = []; $w = in_clause('e.org_id', $ids, $p);
$events = db_all(EVENT_SELECT . " WHERE e.status = 'pending' AND $w ORDER BY e.event_date, e.created_at", $p);
$p = []; $w = in_clause('e.org_id', $ids, $p);
$tasks = db_all(TASK_SELECT . " WHERE t.approval_status = 'pending' AND $w ORDER BY t.deadline", $p);
$p = []; $w = in_clause('p.org_id', $ids, $p);
$posts = db_all(POST_SELECT . " WHERE p.status = 'pending' AND p.org_id IS NOT NULL AND $w ORDER BY p.created_at", $p);
$p = []; $w = in_clause('e.org_id', $ids, $p);
$recent = db_all(EVENT_SELECT . " WHERE e.status IN ('approved','disapproved') AND e.reviewed_at IS NOT NULL AND $w ORDER BY e.reviewed_at DESC LIMIT 6", $p);
$total = count($events) + count($tasks) + count($posts);

render_header('Approvals', 'approvals');
echo page_head('Approvals', $total ? $total . ' waiting for your decision.' : 'Nothing is waiting for you.');
?>
<section class="panel">
  <div class="panel-head"><h2>Event requests <span class="count-pill"><?= count($events) ?></span></h2></div>
  <?php if (!$events): echo empty_state('No event requests'); else: foreach ($events as $ev): ?>
    <div class="queue-item">
      <div>
        <span class="org-tag"><?= e($ev['org_acronym']) ?></span>
        <a class="row-title" href="<?= e(url('event_view.php?id=' . $ev['id'])) ?>"><?= e($ev['title']) ?></a>
        <div class="row-sub"><?= e(fmt_date($ev['event_date'], 'l, M j')) ?>, <?= e(fmt_time_range($ev['start_time'], $ev['end_time'])) ?>, <?= e($ev['venue']) ?>. Requested by <?= e($ev['creator_name']) ?>.</div>
        <?php if ($ev['description']): ?><p class="queue-desc"><?= e(excerpt($ev['description'], 180)) ?></p><?php endif; ?>
      </div>
      <?= review_controls('event_action.php', (int) $ev['id'], 'approve', 'disapprove') ?>
    </div>
  <?php endforeach; endif; ?>
</section>

<section class="panel">
  <div class="panel-head"><h2>Task requests <span class="count-pill"><?= count($tasks) ?></span></h2></div>
  <?php if (!$tasks): echo empty_state('No task requests'); else: foreach ($tasks as $t): ?>
    <div class="queue-item">
      <div>
        <span class="org-tag"><?= e($t['org_acronym']) ?></span>
        <a class="row-title" href="<?= e(url('task_form.php?id=' . $t['id'])) ?>"><?= e($t['title']) ?></a>
        <div class="row-sub">For <a href="<?= e(url('event_view.php?id=' . $t['event_id'])) ?>"><?= e($t['event_title']) ?></a>. Assigned to <?= e($t['assignee_name'] ?? 'nobody') ?>, due <?= e(fmt_date($t['deadline'], 'M j')) ?>, <?= e(strtolower(PRIORITY[$t['priority']][0])) ?> priority. Requested by <?= e($t['creator_name']) ?>.</div>
      </div>
      <?= review_controls('task_action.php', (int) $t['id'], 'approve', 'disapprove') ?>
    </div>
  <?php endforeach; endif; ?>
</section>

<section class="panel">
  <div class="panel-head"><h2>Announcement requests <span class="count-pill"><?= count($posts) ?></span></h2></div>
  <?php if (!$posts): echo empty_state('No announcement requests'); else: foreach ($posts as $post): ?>
    <div class="queue-item">
      <div>
        <span class="org-tag"><?= e($post['org_acronym']) ?></span> <strong class="row-title"><?= e($post['title']) ?></strong>
        <div class="row-sub">Posted by <?= e($post['author_name']) ?> on <?= e(fmt_date($post['created_at'])) ?></div>
        <p class="queue-desc"><?= e(excerpt($post['body'], 220)) ?></p>
      </div>
      <?= review_controls('post_action.php', (int) $post['id'], 'approve', 'reject', 'Publish', 'Don\'t publish') ?>
    </div>
  <?php endforeach; endif; ?>
</section>

<?php if ($recent): ?>
<section class="panel">
  <div class="panel-head"><h2>Recent event decisions</h2></div>
  <ul class="plain-list">
    <?php foreach ($recent as $ev): ?>
      <li><span><a class="row-title" href="<?= e(url('event_view.php?id=' . $ev['id'])) ?>"><?= e($ev['title']) ?></a><small><?= e($ev['org_acronym']) ?>, <?= e(fmt_date($ev['reviewed_at'])) ?></small></span><?= status_badge(EVENT_STATUS, $ev['status']) ?></li>
    <?php endforeach; ?>
  </ul>
</section>
<?php endif; ?>
<?php render_footer();
