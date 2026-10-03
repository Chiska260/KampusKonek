<?php
$seats = my_memberships();
if (!$seats): ?>
  <?= empty_state('No organization seat yet', 'Ask your adviser or council President to add you to your organization. Until then you can still browse events and announcements.',
      '<a class="btn btn-primary" href="' . e(url('events.php')) . '">Browse events</a>') ?>
<?php return; endif;

$me = (int) current_user()['id'];
$myTasks = list_tasks(['assignee' => 'me']);
$myOpen = array_values(array_filter($myTasks, fn($t) => $t['status'] !== 'done' && $t['approval_status'] === 'approved'));
$overdue = count(array_filter($myOpen, fn($t) => task_state($t) === 'overdue'));
$soon = count(array_filter($myOpen, fn($t) => task_state($t) === 'soon'));
$waiting = count(array_filter($myTasks, fn($t) => $t['approval_status'] === 'pending'));

$orgIds = array_map('intval', array_keys($seats));
$p = []; $w = in_clause('e.org_id', $orgIds, $p);
$events = db_all(EVENT_SELECT . " WHERE $w AND e.status <> 'cancelled' AND e.event_date >= CURDATE() ORDER BY e.event_date, e.start_time LIMIT 6", $p);
$p = []; $w = in_clause('e.org_id', $orgIds, $p);
$tc = db_row("SELECT COALESCE(SUM(t.status = 'done'),0) done, COALESCE(SUM(t.status = 'in_progress'),0) doing,
        COALESCE(SUM(t.status = 'todo' AND t.deadline >= CURDATE()),0) todo, COALESCE(SUM(t.status <> 'done' AND t.deadline < CURDATE()),0) overdue
    FROM tasks t JOIN events e ON e.id = t.event_id WHERE t.approval_status = 'approved' AND $w", $p);
$p = []; $w = in_clause('e.org_id', $orgIds, $p);
$myEventsNeedingAction = db_all(EVENT_SELECT . " WHERE $w AND e.created_by = ? AND e.status IN ('draft','disapproved') ORDER BY e.event_date LIMIT 5", array_merge($p, [$me]));
$needActionIds = array_column($myEventsNeedingAction, 'id');
$events = array_values(array_filter($events, fn($ev) => !in_array($ev['id'], $needActionIds)));
?>
<div class="stat-row">
  <?= stat_tile('My open tasks', count($myOpen), $soon ? $soon . ' due within 2 days' : '') ?>
  <?= stat_tile('My overdue tasks', $overdue, '', $overdue ? 'tone-red' : 'tone-green') ?>
  <?= stat_tile('Waiting on adviser', $waiting, 'Your task requests') ?>
  <?= stat_tile('Upcoming events', count($events), 'In your organizations') ?>
</div>

<div class="grid-2 wide-left">
  <section class="panel">
    <div class="panel-head"><h2>My tasks</h2><a href="<?= e(url('tasks.php?assignee=me')) ?>">See all</a></div>
    <?= task_table(array_slice($myOpen, 0, 6), ['showAssignee' => false, 'showPriority' => false, 'emptyTitle' => 'Nothing assigned to you', 'emptyText' => 'When an officer gives you a task and the adviser approves it, it shows up here.']) ?>
  </section>
  <section class="panel">
    <h2>Task status in my organizations</h2>
    <?= chart_block('officerTasks', 'doughnut', ['Done', 'In progress', 'To do', 'Overdue'],
        [(int) $tc['done'], (int) $tc['doing'], (int) $tc['todo'], (int) $tc['overdue']], ['#2F9E73', '#3D80CB', '#C9D0E3', '#D14B4B']) ?>
  </section>
</div>

<?php if ($myEventsNeedingAction): ?>
<section class="panel">
  <div class="panel-head"><h2>Your events that need action</h2></div>
  <div class="card-grid"><?php foreach ($myEventsNeedingAction as $ev) echo event_card($ev); ?></div>
</section>
<?php endif; ?>

<section class="panel">
  <div class="panel-head"><h2>Event progress</h2>
    <?php if (can_create_event()): ?><a class="btn btn-primary btn-sm" href="<?= e(url('event_form.php')) ?>"><?= icon('plus', 16) ?>New event</a><?php endif; ?></div>
  <?php if (!$events): echo empty_state('No upcoming events', 'Draft one and send it to your adviser.'); else: ?>
    <div class="card-grid"><?php foreach ($events as $ev) echo event_card($ev); ?></div>
  <?php endif; ?>
</section>
