<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
$orgs = all_orgs();

render_header('Organizations', 'organizations');
echo page_head('Organizations', 'Student councils and organizations on campus.',
    is_admin() ? '<a class="btn btn-primary" href="' . e(url('org_form.php')) . '">' . icon('plus', 18) . 'New organization</a>' : '');
if (!$orgs) {
    echo empty_state('No organizations yet', is_admin() ? 'Create the first council or organization and assign its advisers.' : 'The system admin sets these up.');
}
?>
<div class="org-grid big">
<?php foreach ($orgs as $o):
    $oid = (int) $o['id'];
    $stats = org_stats($oid);
    $leaders = array_filter(org_leaders($oid), fn($m) => in_array($m['position'], ['adviser', 'president'], true));
    $missing = org_missing_advisers($oid); ?>
  <a class="org-card" href="<?= e(url('org_view.php?id=' . $oid)) ?>">
    <?= img_slot('organization logo', 'org-logo') ?>
    <div>
      <div class="event-tags"><h3><?= e($o['name']) ?></h3><?= badge($o['type'] === 'council' ? 'Council' : 'Organization', $o['type'] === 'council' ? 'navy' : 'muted') ?></div>
      <p><?= e(excerpt((string) $o['description'], 120)) ?></p>
      <p class="org-meta">
        <?php foreach ($leaders as $l): ?><span><?= e(POSITION_LABELS[$l['position']]) ?>: <?= e($l['full_name']) ?></span><?php endforeach; ?>
        <span><?= $stats['members'] ?> members</span><span><?= $stats['events'] ?> approved events</span>
      </p>
      <?php if ($missing && (is_admin() || is_org_member($oid))): ?><p class="warn-line">Needs a <?= e(implode(' and ', $missing)) ?></p><?php endif; ?>
    </div>
  </a>
<?php endforeach; ?>
</div>
<?php render_footer();
