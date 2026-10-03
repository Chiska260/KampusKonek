<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$m = get('m', date('Y-m'));
if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $m)) {
    $m = date('Y-m');
}
$first = strtotime($m . '-01');
$daysInMonth = (int) date('t', $first);
$startOffset = (int) date('w', $first);          // 0 = Sunday
$prev = date('Y-m', strtotime('-1 month', $first));
$next = date('Y-m', strtotime('+1 month', $first));

$params = [];
$vis = event_visibility($params);
$params[] = $m . '-01';
$params[] = $m . '-' . $daysInMonth;
$rows = db_all(EVENT_SELECT . " WHERE $vis AND e.status <> 'cancelled' AND e.event_date BETWEEN ? AND ? ORDER BY e.event_date, e.start_time", $params);
$byDay = [];
foreach ($rows as $r) {
    $byDay[(int) date('j', strtotime($r['event_date']))][] = $r;
}
$today = date('Y-m-d');

render_header('Calendar', 'calendar');
echo page_head('Calendar', 'Approved events from every organization.');
?>
<div class="cal-nav">
  <a class="btn btn-ghost btn-sm" href="?m=<?= e($prev) ?>" aria-label="Previous month"><?= icon('left', 18) ?></a>
  <h2><?= e(date('F Y', $first)) ?></h2>
  <a class="btn btn-ghost btn-sm" href="?m=<?= e($next) ?>" aria-label="Next month"><?= icon('right', 18) ?></a>
  <a class="btn btn-link" href="?m=<?= e(date('Y-m')) ?>">Today</a>
</div>

<div class="cal-grid" role="grid" aria-label="<?= e(date('F Y', $first)) ?>">
  <?php foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $d): ?><div class="cal-dow" role="columnheader"><?= $d ?></div><?php endforeach; ?>
  <?php for ($i = 0; $i < $startOffset; $i++): ?><div class="cal-cell empty"></div><?php endfor; ?>
  <?php for ($day = 1; $day <= $daysInMonth; $day++):
      $isToday = sprintf('%s-%02d', $m, $day) === $today; ?>
    <div class="cal-cell<?= $isToday ? ' today' : '' ?>" role="gridcell">
      <span class="cal-day"><?= $day ?></span>
      <?php foreach ($byDay[$day] ?? [] as $ev): ?>
        <a class="cal-event <?= e($ev['status']) ?>" href="<?= e(url('event_view.php?id=' . $ev['id'])) ?>" title="<?= e($ev['title']) ?>">
          <small><?= e($ev['org_acronym']) ?></small> <?= e($ev['title']) ?>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endfor; ?>
</div>

<section class="cal-agenda panel">
  <h2>This month</h2>
  <?php if (!$rows): echo empty_state('No events this month'); else: ?>
    <ul class="plain-list">
      <?php foreach ($rows as $ev): ?>
        <li><span><a class="row-title" href="<?= e(url('event_view.php?id=' . $ev['id'])) ?>"><?= e($ev['title']) ?></a>
          <small><?= e(fmt_date($ev['event_date'], 'D, M j')) ?>, <?= e(fmt_time_range($ev['start_time'], $ev['end_time'])) ?>, <?= e($ev['venue']) ?></small></span>
          <?= $ev['status'] === 'approved' ? '<span class="org-tag">' . e($ev['org_acronym']) . '</span>' : status_badge(EVENT_STATUS, $ev['status']) ?></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>
<?php render_footer();
