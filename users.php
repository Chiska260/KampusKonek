<?php
require_once __DIR__ . '/includes/bootstrap.php';
$me = require_admin();

$q = get('q');
$params = [];
$where = '1=1';
if ($q !== '') {
    $where = '(u.full_name LIKE ? OR u.email LIKE ?)';
    $params = ['%' . $q . '%', '%' . $q . '%'];
}
$users = db_all("SELECT u.* FROM users u WHERE $where ORDER BY FIELD(u.role,'system_admin','adviser','officer','student'), u.full_name LIMIT 200", $params);
$seatRows = db_all('SELECT m.user_id, m.position, o.acronym FROM org_members m JOIN organizations o ON o.id = m.org_id');
$seats = [];
foreach ($seatRows as $s) {
    $seats[$s['user_id']][] = POSITION_LABELS[$s['position']] . ', ' . $s['acronym'];
}

render_header('Users', 'users');
echo page_head('Users', 'Roles follow organization seats. Give someone a seat from the organization page and their account type updates on its own.');
?>
<form class="filters compact" method="get">
  <div class="field"><label for="q">Search</label><input id="q" name="q" type="search" value="<?= e($q) ?>" placeholder="Name or email"></div>
  <div class="field filter-actions"><button class="btn btn-ghost" type="submit">Search</button><a class="btn btn-link" href="<?= e(url('users.php')) ?>">Clear</a></div>
</form>

<section class="panel flush">
  <div class="table-wrap">
  <table class="table user-table">
    <thead><tr><th>Person</th><th>Account type</th><th>Seats</th><th>Status</th><th><span class="sr-only">Actions</span></th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): $isSelf = (int) $u['id'] === (int) $me['id']; ?>
      <tr>
        <td data-label="Person"><span class="person"><?= avatar($u['full_name'], 'sm') ?><span><strong><?= e($u['full_name']) ?></strong><small><?= e($u['email']) ?></small></span></span></td>
        <td data-label="Account type"><?= badge(ROLE_LABELS[$u['role']], $u['role'] === 'system_admin' ? 'navy' : 'muted') ?></td>
        <td data-label="Seats"><?= isset($seats[$u['id']]) ? e(implode('; ', $seats[$u['id']])) : '<span class="muted">None</span>' ?></td>
        <td data-label="Status"><?= $u['is_active'] ? badge('Active', 'green') : badge('Deactivated', 'red') ?></td>
        <td class="row-actions" data-label="">
          <?php if ($u['role'] === 'system_admin' && !$isSelf) echo action_form('user_action.php', ['id' => $u['id'], 'do' => 'remove_admin', 'q' => $q], 'Remove admin', 'btn btn-ghost btn-sm', 'Remove admin access from ' . $u['full_name'] . '?'); ?>
          <?php if ($u['role'] !== 'system_admin' && email_kind($u['email']) === 'admin') echo action_form('user_action.php', ['id' => $u['id'], 'do' => 'make_admin', 'q' => $q], 'Make admin', 'btn btn-ghost btn-sm', 'Give ' . $u['full_name'] . ' full system admin access?'); ?>
          <?php if (!$isSelf) echo action_form('user_action.php', ['id' => $u['id'], 'do' => 'toggle', 'q' => $q], $u['is_active'] ? 'Deactivate' : 'Activate', 'btn btn-ghost btn-sm', $u['is_active'] ? 'Deactivate ' . $u['full_name'] . '? They won\'t be able to log in.' : ''); ?>
          <details class="reject"><summary class="btn btn-ghost btn-sm">Reset password</summary>
            <form method="post" action="<?= e(url('actions/user_action.php')) ?>" class="reject-form">
              <?= csrf_field() ?><input type="hidden" name="id" value="<?= $u['id'] ?>"><input type="hidden" name="do" value="password"><input type="hidden" name="q" value="<?= e($q) ?>">
              <label class="sr-only" for="pw<?= $u['id'] ?>">New password</label>
              <input id="pw<?= $u['id'] ?>" name="password" type="text" minlength="<?= MIN_PASSWORD_LENGTH ?>" required placeholder="New password" autocomplete="off">
              <button class="btn btn-primary btn-sm" type="submit">Set password</button>
            </form></details>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php if (!$users): echo empty_state('No users match that search'); endif; ?>
</section>
<?php render_footer();
