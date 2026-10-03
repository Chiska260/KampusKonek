<?php
$counts = [
    'users'  => (int) db_val('SELECT COUNT(*) FROM users'),
    'orgs'   => (int) db_val('SELECT COUNT(*) FROM organizations WHERE is_active = 1'),
    'events' => (int) db_val("SELECT COUNT(*) FROM events WHERE status = 'approved' AND event_date >= CURDATE()"),
    'open'   => (int) db_val("SELECT COUNT(*) FROM tasks WHERE approval_status = 'approved' AND status <> 'done'"),
];
$pending = pending_approval_count();
$byRole = array_column(db_all('SELECT role, COUNT(*) n FROM users GROUP BY role'), 'n', 'role');
$statusRows = array_column(db_all('SELECT status, COUNT(*) n FROM events GROUP BY status'), 'n', 'status');
$noAdvisers = [];
foreach (all_orgs() as $o) {
    if ($m = org_missing_advisers((int) $o['id'])) {
        $noAdvisers[] = [$o, $m];
    }
}
$recent = db_all('SELECT id, full_name, email, role, created_at FROM users ORDER BY id DESC LIMIT 5');
?>
<div class="stat-row">
  <?= stat_tile('Accounts', $counts['users']) ?>
  <?= stat_tile('Organizations', $counts['orgs']) ?>
  <?= stat_tile('Upcoming events', $counts['events']) ?>
  <?= stat_tile('Open tasks', $counts['open']) ?>
  <?= stat_tile('Waiting for approval', $pending, $pending ? 'Advisers have requests to review' : 'Nothing waiting', $pending ? 'tone-amber' : '') ?>
</div>

<?php if ($noAdvisers): ?>
<div class="flash flash-warn" role="alert">
  <strong>Missing advisers:</strong>
  <?php foreach ($noAdvisers as [$o, $m]): ?>
    <a href="<?= e(url('org_view.php?id=' . $o['id'])) ?>"><?= e($o['acronym']) ?></a> needs a <?= e(implode(' and ', $m)) ?>.
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="grid-2">
  <section class="panel">
    <h2>Events by status</h2>
    <?php
    $labels = []; $vals = []; $cols = [];
    $palette = ['draft' => '#C9D0E3', 'pending' => '#FFBF6B', 'approved' => '#2F9E73', 'disapproved' => '#D14B4B', 'cancelled' => '#8A93B2'];
    foreach (EVENT_STATUS as $k => [$label]) {
        $labels[] = $label; $vals[] = (int) ($statusRows[$k] ?? 0); $cols[] = $palette[$k];
    }
    echo chart_block('adminEvents', 'bars', $labels, $vals, $cols);
    ?>
  </section>
  <section class="panel">
    <h2>Accounts by type</h2>
    <?= chart_block('adminRoles', 'doughnut',
        ['System admins', 'Advisers', 'Officers and committee', 'Students'],
        [(int) ($byRole['system_admin'] ?? 0), (int) ($byRole['adviser'] ?? 0), (int) ($byRole['officer'] ?? 0), (int) ($byRole['student'] ?? 0)],
        ['#303B70', '#F5B82E', '#3D80CB', '#71A9DB']) ?>
  </section>
</div>

<div class="grid-2">
  <section class="panel">
    <div class="panel-head"><h2>Newest accounts</h2><a href="<?= e(url('users.php')) ?>">Manage users</a></div>
    <ul class="plain-list">
      <?php foreach ($recent as $r): ?>
        <li><span class="person"><?= avatar($r['full_name'], 'sm') ?><span><strong><?= e($r['full_name']) ?></strong><small><?= e($r['email']) ?></small></span></span><?= badge(ROLE_LABELS[$r['role']], $r['role'] === 'system_admin' ? 'navy' : 'muted') ?></li>
      <?php endforeach; ?>
    </ul>
  </section>
  <section class="panel">
    <div class="panel-head"><h2>Quick actions</h2></div>
    <div class="action-list">
      <a class="btn btn-primary" href="<?= e(url('org_form.php')) ?>"><?= icon('plus', 18) ?>New organization</a>
      <a class="btn btn-ghost" href="<?= e(url('users.php')) ?>">Manage accounts</a>
      <a class="btn btn-ghost" href="<?= e(url('approvals.php')) ?>">Review approvals</a>
      <a class="btn btn-ghost" href="<?= e(url('announcements.php')) ?>">Post a campus announcement</a>
    </div>
  </section>
</div>
