<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$org = get_org((int) get('id')) ?? abort(404);
$oid = (int) $org['id'];
$isMember = is_org_member($oid);
$canManage = (bool) assignable_positions($oid);
$members = org_members($oid);
$leaders = array_filter($members, fn($m) => !in_array($m['position'], ['officer', 'member'], true));
$people = $isMember ? $members : $leaders;
$missing = org_missing_advisers($oid);
$events = list_events(['org' => $oid, 'limit' => 4]);
$posts = list_posts(['org' => $oid, 'limit' => 3]);
$stats = org_stats($oid);

render_header($org['name'], 'organizations');
echo page_head($org['name'], ($org['type'] === 'council' ? 'Student council' : 'Student organization'),
    is_admin() ? '<a class="btn btn-ghost" href="' . e(url('org_form.php?id=' . $oid)) . '">Edit details</a>' : '');
?>
<?php if ($missing && ($isMember || is_admin())): ?>
  <div class="flash flash-warn" role="alert">This organization needs a <?= e(implode(' and ', $missing)) ?>. Every organization must have at least one Adviser and one Co-Adviser.</div>
<?php endif; ?>

<div class="grid-2 wide-left">
  <div class="stack">
    <section class="panel org-hero">
      <!-- [image here] -->
      <div class="img-slot banner short"><span>[image here]</span></div>
      <p><?= nl2br(e($org['description'] ?: 'No description yet.')) ?></p>
      <div class="stat-row small">
        <?= stat_tile('Members', $stats['members']) ?>
        <?= stat_tile('Approved events', $stats['events']) ?>
        <?= $isMember ? stat_tile('Open tasks', $stats['open_tasks']) : '' ?>
      </div>
    </section>

    <section class="panel">
      <div class="panel-head"><h2>Events</h2>
        <?php if (is_org_staff($oid)): ?><a class="btn btn-primary btn-sm" href="<?= e(url('event_form.php?org=' . $oid)) ?>"><?= icon('plus', 16) ?>New event</a><?php endif; ?></div>
      <?php if (!$events): echo empty_state('No upcoming events'); else: ?><div class="card-grid one"><?php foreach ($events as $ev) echo event_card($ev); ?></div><?php endif; ?>
    </section>

    <section class="panel">
      <div class="panel-head"><h2>Latest updates</h2><a href="<?= e(url('announcements.php?org=' . $oid)) ?>">All updates</a></div>
      <?php if (!$posts): echo empty_state('No updates yet'); else: foreach ($posts as $p): ?>
        <article class="post-mini"><div class="event-tags"><span class="muted small"><?= e(fmt_date($p['created_at'])) ?></span><?= $p['status'] !== 'published' ? status_badge(POST_STATUS, $p['status']) : '' ?></div>
          <h3><?= e($p['title']) ?></h3><p><?= e(excerpt($p['body'], 160)) ?></p></article>
      <?php endforeach; endif; ?>
    </section>
  </div>

  <section class="panel">
    <h2><?= $isMember ? 'People' : 'Leadership' ?></h2>
    <ul class="plain-list people">
      <?php foreach ($people as $m): ?>
        <li>
          <span class="person"><?= avatar($m['full_name'], 'sm') ?><span><strong><?= e($m['full_name']) ?></strong><small><?= e(POSITION_LABELS[$m['position']]) ?></small></span></span>
          <?php if ($canManage && in_array($m['position'], assignable_positions($oid), true)): ?>
            <?= action_form('member_action.php', ['org_id' => $oid, 'member_id' => $m['member_id'], 'do' => 'remove'], 'Remove', 'btn btn-danger-ghost btn-sm', 'Remove ' . $m['full_name'] . ' from ' . $org['acronym'] . '?') ?>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>

    <?php if ($canManage): ?>
    <form method="post" action="<?= e(url('actions/member_action.php')) ?>" class="form add-member">
      <?= csrf_field() ?><input type="hidden" name="org_id" value="<?= $oid ?>"><input type="hidden" name="do" value="save">
      <h3>Add or change a seat</h3>
      <div class="field"><label for="m_email">School email</label><input id="m_email" name="email" type="email" required placeholder="name@students.nu-laguna.edu.ph"></div>
      <div class="field"><label for="m_pos">Position</label>
        <select id="m_pos" name="position"><?php foreach (assignable_positions($oid) as $pos): ?><option value="<?= $pos ?>"><?= e(POSITION_LABELS[$pos]) ?></option><?php endforeach; ?></select>
        <p class="field-hint">Advisers, Co-Advisers, President and VP need an @<?= e(ADMIN_DOMAINS[0]) ?> email. Officers and committee members use @<?= e(STUDENT_DOMAINS[0]) ?>. The person must have signed up first.</p></div>
      <button class="btn btn-primary" type="submit">Save seat</button>
    </form>
    <?php endif; ?>
  </section>
</div>
<?php render_footer();
