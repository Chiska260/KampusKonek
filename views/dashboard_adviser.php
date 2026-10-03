<?php
$ids = approver_org_ids();
if ($ids !== null && !$ids): ?>
  <?= empty_state('You are not assigned to an organization yet', 'The system admin or your organization\'s adviser can give you a seat. Once that\'s done, your approval queue shows up here.') ?>
<?php return; endif;

$p1 = []; $w1 = in_clause('e.org_id', $ids, $p1);
$pendingEvents = db_all(EVENT_SELECT . " WHERE e.status = 'pending' AND $w1 ORDER BY e.event_date LIMIT 5", $p1);
$p2 = []; $w2 = in_clause('e.org_id', $ids, $p2);
$pendingTasks = db_all(TASK_SELECT . " WHERE t.approval_status = 'pending' AND $w2 ORDER BY t.deadline LIMIT 5", $p2);
$p3 = []; $w3 = in_clause('e.org_id', $ids, $p3);
$taskCounts = db_row("SELECT SUM(t.status = 'todo') todo, SUM(t.status = 'in_progress') doing, SUM(t.status = 'done') done,
        SUM(t.status <> 'done' AND t.deadline < CURDATE()) overdue
    FROM tasks t JOIN events e ON e.id = t.event_id WHERE t.approval_status = 'approved' AND $w3", $p3);
$p4 = []; $w4 = in_clause('e.org_id', $ids, $p4);
$upcoming = db_all(EVENT_SELECT . " WHERE e.status = 'approved' AND e.event_date >= CURDATE() AND $w4 ORDER BY e.event_date, e.start_time LIMIT 4", $p4);
$pendingTotal = pending_approval_count();
?>
<div class="stat-row">
  <?= stat_tile('Waiting for you', $pendingTotal, $pendingTotal ? 'Events, tasks, and posts' : 'All caught up', $pendingTotal ? 'tone-amber' : 'tone-green') ?>
  <?= stat_tile('Tasks in progress', (int) $taskCounts['doing']) ?>
  <?= stat_tile('Tasks done', (int) $taskCounts['done']) ?>
  <?= stat_tile('Overdue tasks', (int) $taskCounts['overdue'], '', (int) $taskCounts['overdue'] ? 'tone-red' : '') ?>
</div>

<div class="grid-2">
  <section class="panel">
    <div class="panel-head"><h2>Event requests</h2><a href="<?= e(url('approvals.php')) ?>">Open approvals</a></div>
    <?php if (!$pendingEvents): ?>
      <?= empty_state('No event requests', 'New event requests from officers will appear here.') ?>
    <?php else: foreach ($pendingEvents as $ev): ?>
      <div class="queue-item">
        <div><span class="org-tag"><?= e($ev['org_acronym']) ?></span> <a class="row-title" href="<?= e(url('event_view.php?id=' . $ev['id'])) ?>"><?= e($ev['title']) ?></a>
          <div class="row-sub"><?= e(fmt_date($ev['event_date'])) ?>, <?= e($ev['venue']) ?>. Requested by <?= e($ev['creator_name']) ?></div></div>
        <?= review_controls('event_action.php', (int) $ev['id'], 'approve', 'disapprove') ?>
      </div>
    <?php endforeach; endif; ?>
  </section>

  <section class="panel">
    <div class="panel-head"><h2>Task requests</h2><a href="<?= e(url('approvals.php')) ?>">Open approvals</a></div>
    <?php if (!$pendingTasks): ?>
      <?= empty_state('No task requests', 'Tasks that officers assign will wait here for your approval.') ?>
    <?php else: foreach ($pendingTasks as $t): ?>
      <div class="queue-item">
        <div><a class="row-title" href="<?= e(url('task_form.php?id=' . $t['id'])) ?>"><?= e($t['title']) ?></a>
          <div class="row-sub"><?= e($t['event_title']) ?>, for <?= e($t['assignee_name'] ?? 'nobody yet') ?>, due <?= e(fmt_date($t['deadline'], 'M j')) ?></div></div>
        <?= review_controls('task_action.php', (int) $t['id'], 'approve', 'disapprove') ?>
      </div>
    <?php endforeach; endif; ?>
  </section>
</div>

<section class="panel">
  <div class="panel-head"><h2>Upcoming approved events</h2><a href="<?= e(url('events.php')) ?>">All events</a></div>
  <?php if (!$upcoming): echo empty_state('No upcoming events yet'); else: ?>
    <div class="card-grid"><?php foreach ($upcoming as $ev) echo event_card($ev); ?></div>
  <?php endif; ?>
</section>
