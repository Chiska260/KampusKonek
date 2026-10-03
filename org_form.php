<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_admin();

$org = get_org((int) get('id'));
if (get('id') && !$org) {
    abort(404);
}
$candidates = $org ? [] : adviser_candidates();
$errors = [];
$v = $org ?? ['name' => '', 'acronym' => '', 'type' => 'organization', 'description' => '', 'is_active' => 1];

if (is_post()) {
    csrf_check();
    $v = ['name' => post('name'), 'acronym' => post('acronym'), 'type' => post('type'), 'description' => post('description'), 'is_active' => $org ? (int) isset($_POST['is_active']) : 1];
    $adviser = (int) post('adviser_id');
    $co = (int) post('co_adviser_id');

    if (mb_strlen($v['name']) < 3 || mb_strlen($v['name']) > 120) $errors[] = 'Name must be 3 to 120 characters.';
    if (mb_strlen($v['acronym']) < 2 || mb_strlen($v['acronym']) > 20) $errors[] = 'Acronym must be 2 to 20 characters.';
    if (!in_array($v['type'], ['council', 'organization'], true)) $errors[] = 'Pick a type.';
    if (mb_strlen($v['description']) > 2000) $errors[] = 'Description is too long.';
    $dupe = db_val('SELECT id FROM organizations WHERE name = ? AND id <> ?', [$v['name'], $org['id'] ?? 0]);
    if ($dupe) $errors[] = 'An organization with that name already exists.';
    if (!$org) {
        $okIds = array_map('intval', array_column($candidates, 'id'));
        if (!in_array($adviser, $okIds, true) || !in_array($co, $okIds, true)) {
            $errors[] = 'Choose both an Adviser and a Co-Adviser.';
        } elseif ($adviser === $co) {
            $errors[] = 'The Adviser and Co-Adviser must be two different people.';
        }
    }

    if (!$errors) {
        if ($org) {
            db_run('UPDATE organizations SET name = ?, acronym = ?, type = ?, description = ?, is_active = ? WHERE id = ?',
                [$v['name'], $v['acronym'], $v['type'], $v['description'], $v['is_active'], $org['id']]);
            flash('success', 'Organization updated.');
            redirect('org_view.php?id=' . $org['id']);
        }
        $newId = db_insert('INSERT INTO organizations (name, acronym, type, description) VALUES (?, ?, ?, ?)',
            [$v['name'], $v['acronym'], $v['type'], $v['description']]);
        db_run("INSERT INTO org_members (org_id, user_id, position) VALUES (?, ?, 'adviser'), (?, ?, 'co_adviser')", [$newId, $adviser, $newId, $co]);
        sync_user_role($adviser);
        sync_user_role($co);
        flash('success', 'Organization created with its adviser and co-adviser. Add the officers next.');
        redirect('org_view.php?id=' . $newId);
    }
}

render_header($org ? 'Edit organization' : 'New organization', 'organizations');
echo page_head($org ? 'Edit organization' : 'New organization', $org ? $org['name'] : 'Every organization needs an Adviser and a Co-Adviser from day one.');
?>
<form method="post" class="form panel narrow" novalidate>
  <?= csrf_field() ?>
  <?php foreach ($errors as $err): ?><div class="flash flash-error" role="alert"><?= e($err) ?></div><?php endforeach; ?>
  <div class="field"><label for="name">Name</label><input id="name" name="name" required maxlength="120" value="<?= e($v['name']) ?>"></div>
  <div class="field-row">
    <div class="field"><label for="acronym">Acronym</label><input id="acronym" name="acronym" required maxlength="20" value="<?= e($v['acronym']) ?>"></div>
    <div class="field"><label for="type">Type</label>
      <select id="type" name="type"><option value="council"<?= $v['type'] === 'council' ? ' selected' : '' ?>>Student council</option><option value="organization"<?= $v['type'] === 'organization' ? ' selected' : '' ?>>Student organization</option></select></div>
  </div>
  <div class="field"><label for="description">About</label><textarea id="description" name="description" rows="4" maxlength="2000"><?= e($v['description']) ?></textarea></div>

  <?php if (!$org): ?>
    <?php if (count($candidates) < 2): ?>
      <div class="notice notice-red">You need at least two registered faculty accounts (@<?= e(ADMIN_DOMAINS[0]) ?>) to act as Adviser and Co-Adviser. Ask them to sign up first.</div>
    <?php endif; ?>
    <div class="field-row">
      <div class="field"><label for="adviser_id">Adviser</label>
        <select id="adviser_id" name="adviser_id" required><option value="">Choose</option>
          <?php foreach ($candidates as $c): ?><option value="<?= $c['id'] ?>"<?= (int) post('adviser_id') === (int) $c['id'] ? ' selected' : '' ?>><?= e($c['full_name']) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="co_adviser_id">Co-Adviser</label>
        <select id="co_adviser_id" name="co_adviser_id" required><option value="">Choose</option>
          <?php foreach ($candidates as $c): ?><option value="<?= $c['id'] ?>"<?= (int) post('co_adviser_id') === (int) $c['id'] ? ' selected' : '' ?>><?= e($c['full_name']) ?></option><?php endforeach; ?></select></div>
    </div>
  <?php else: ?>
    <div class="field check"><label><input type="checkbox" name="is_active" value="1"<?= $v['is_active'] ? ' checked' : '' ?>> Active <span class="field-hint inline">Inactive organizations are hidden from lists.</span></label></div>
  <?php endif; ?>

  <div class="form-actions">
    <button class="btn btn-primary" type="submit"><?= $org ? 'Save changes' : 'Create organization' ?></button>
    <a class="btn btn-link" href="<?= e(url($org ? 'org_view.php?id=' . $org['id'] : 'organizations.php')) ?>">Cancel</a>
  </div>
</form>
<?php render_footer();
