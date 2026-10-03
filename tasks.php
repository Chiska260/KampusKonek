<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
if (!my_memberships() && !is_admin() && !db_val('SELECT 1 FROM tasks WHERE assigned_to = ? LIMIT 1', [current_user()['id']])) {
    abort(403, 'Tasks are visible to officers and committee members of an organization.');
}

$f = [
    'q' => get('q'), 'org' => get('org'), 'event' => get('event'), 'assignee' => get('assignee'),
    'status' => get('status'), 'priority' => get('priority'), 'approval' => get('approval'), 'overdue' => get('overdue'),
];
$tasks = list_tasks($f);
$done = count(array_filter($tasks, fn($t) => $t['status'] === 'done'));
$overdue = count(array_filter($tasks, fn($t) => task_state($t) === 'overdue'));

$eventChoices = [];
foreach (list_events(['when' => 'all']) as $ev) {
    if (is_org_member((int) $ev['org_id'])) {
        $eventChoices[] = $ev;
    }
}
$orgChoices = is_admin() ? all_orgs() : array_filter(all_orgs(), fn($o) => is_org_member((int) $o['id']));

render_header('Tasks', 'tasks');
echo page_head('Tasks', 'Everything assigned across your organizations.');
?>
<form class="filters" method="get">
  <div class="field"><label for="q">Search</label><input id="q" name="q" type="search" value="<?= e($f['q']) ?>" placeholder="Task name"></div>
  <div class="field"><label for="assignee">Assigned to</label>
    <select id="assignee" name="assignee" data-autosubmit><option value="">Anyone</option><option value="me"<?= $f['assignee'] === 'me' ? ' selected' : '' ?>>Me</option></select></div>
  <div class="field"><label for="org">Organization</label>
    <select id="org" name="org" data-autosubmit><option value="">All</option>
      <?php foreach ($orgChoices as $o): ?><option value="<?= $o['id'] ?>"<?= (string) $o['id'] === $f['org'] ? ' selected' : '' ?>><?= e($o['acronym']) ?></option><?php endforeach; ?></select></div>
  <div class="field"><label for="event">Event</label>
    <select id="event" name="event" data-autosubmit><option value="">All</option>
      <?php foreach ($eventChoices as $ev): ?><option value="<?= $ev['id'] ?>"<?= (string) $ev['id'] === $f['event'] ? ' selected' : '' ?>><?= e(excerpt($ev['title'], 32)) ?></option><?php endforeach; ?></select></div>
  <div class="field"><label for="status">Status</label>
    <select id="status" name="status" data-autosubmit><option value="">Any</option>
      <?php foreach (TASK_STATUS as $k => [$l]): ?><option value="<?= $k ?>"<?= $k === $f['status'] ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
  <div class="field"><label for="priority">Priority</label>
    <select id="priority" name="priority" data-autosubmit><option value="">Any</option>
      <?php foreach (PRIORITY as $k => [$l]): ?><option value="<?= $k ?>"<?= $k === $f['priority'] ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
  <div class="field"><label for="approval">Approval</label>
    <select id="approval" name="approval" data-autosubmit><option value="">Any</option>
      <?php foreach (APPROVAL_STATUS as $k => [$l]): ?><option value="<?= $k ?>"<?= $k === $f['approval'] ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
  <div class="field check"><label><input type="checkbox" name="overdue" value="1" data-autosubmit<?= $f['overdue'] ? ' checked' : '' ?>> Overdue only</label></div>
  <div class="field filter-actions"><button class="btn btn-ghost" type="submit">Apply</button><a class="btn btn-link" href="<?= e(url('tasks.php')) ?>">Clear</a></div>
</form>

<p class="result-line"><strong><?= count($tasks) ?></strong> <?= count($tasks) === 1 ? 'task' : 'tasks' ?>, <?= $done ?> done<?= $overdue ? ', <span class="text-red">' . $overdue . ' overdue</span>' : '' ?></p>
<section class="panel flush"><?= task_table($tasks) ?></section>
<?php render_footer();
