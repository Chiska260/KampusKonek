<?php
$events = list_events(['limit' => 6]);
$posts = list_posts(['status' => 'published', 'limit' => 4]);
?>
<div class="grid-2 wide-left">
  <section class="panel">
    <div class="panel-head"><h2>Coming up</h2><a href="<?= e(url('calendar.php')) ?>">Open calendar</a></div>
    <?php if (!$events): echo empty_state('No approved events yet', 'Check back soon. New events show up here as soon as advisers approve them.'); else: ?>
      <div class="card-grid one"><?php foreach ($events as $ev) echo event_card($ev); ?></div>
    <?php endif; ?>
  </section>
  <section class="panel">
    <div class="panel-head"><h2>Latest updates</h2><a href="<?= e(url('announcements.php')) ?>">All updates</a></div>
    <?php if (!$posts): echo empty_state('No announcements yet'); else: foreach ($posts as $p): ?>
      <article class="post-mini">
        <div class="event-tags"><span class="org-tag"><?= e($p['org_acronym'] ?? 'Campus') ?></span><span class="muted small"><?= e(fmt_date($p['created_at'])) ?></span></div>
        <h3><?= e($p['title']) ?></h3>
        <p><?= e(excerpt($p['body'], 110)) ?></p>
      </article>
    <?php endforeach; endif; ?>
  </section>
</div>
<section class="panel">
  <div class="panel-head"><h2>Organizations</h2><a href="<?= e(url('organizations.php')) ?>">See all</a></div>
  <div class="org-grid">
    <?php foreach (all_orgs() as $o): ?>
      <a class="org-card" href="<?= e(url('org_view.php?id=' . $o['id'])) ?>">
        <?= img_slot('organization logo', 'org-logo') ?>
        <div><h3><?= e($o['name']) ?></h3><p><?= e(excerpt((string) $o['description'], 90)) ?></p></div>
      </a>
    <?php endforeach; ?>
  </div>
</section>
