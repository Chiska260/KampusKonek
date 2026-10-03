<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$f = ['q' => get('q'), 'org' => get('org'), 'status' => get('status'), 'when' => get('when', 'upcoming')];
if (!in_array($f['when'], ['upcoming', 'past', 'all'], true)) {
    $f['when'] = 'upcoming';
}
$events = list_events($f);
$showStatusFilter = is_admin() || (bool) my_memberships();

render_header('Events', 'events');
echo page_head('Events', 'Approved events from every organization' . ($showStatusFilter ? ', plus your own organizations\' drafts and requests.' : '.'),
    can_create_event() ? '<a class="btn btn-primary" href="' . e(url('event_form.php')) . '">' . icon('plus', 18) . 'New event</a>' : '');
?>
<form class="filters" method="get">
  <div class="field"><label for="q">Search</label><input id="q" name="q" type="search" value="<?= e($f['q']) ?>" placeholder="Title or venue"></div>
  <div class="field"><label for="org">Organization</label>
    <select id="org" name="org" data-autosubmit><option value="">All</option>
      <?php foreach (all_orgs() as $o): ?><option value="<?= $o['id'] ?>"<?= (string) $o['id'] === $f['org'] ? ' selected' : '' ?>><?= e($o['acronym']) ?></option><?php endforeach; ?>
    </select></div>
  <?php if ($showStatusFilter): ?>
  <div class="field"><label for="status">Status</label>
    <select id="status" name="status" data-autosubmit><option value="">Any</option>
      <?php foreach (EVENT_STATUS as $k => [$label]): ?><option value="<?= $k ?>"<?= $k === $f['status'] ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
    </select></div>
  <?php endif; ?>
  <div class="field"><label for="when">When</label>
    <select id="when" name="when" data-autosubmit>
      <option value="upcoming"<?= $f['when'] === 'upcoming' ? ' selected' : '' ?>>Upcoming</option>
      <option value="past"<?= $f['when'] === 'past' ? ' selected' : '' ?>>Past</option>
      <option value="all"<?= $f['when'] === 'all' ? ' selected' : '' ?>>All dates</option>
    </select></div>
  <div class="field filter-actions"><button class="btn btn-ghost" type="submit">Apply</button><a class="btn btn-link" href="<?= e(url('events.php')) ?>">Clear</a></div>
</form>

<?php if (!$events): ?>
  <?= empty_state('No events match', 'Try a different filter' . (can_create_event() ? ', or draft a new event for your organization.' : '.')) ?>
<?php else: ?>
  <div class="card-grid"><?php foreach ($events as $ev) echo event_card($ev); ?></div>
<?php endif; ?>
<?php render_footer();
