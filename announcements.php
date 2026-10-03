<?php
require_once __DIR__ . '/includes/bootstrap.php';
$me = require_login();

$postOrgs = orgs_for_staff();
$canCampus = is_admin();
$canPost = $postOrgs || $canCampus;
$errors = [];
$form = ['org_id' => '', 'title' => '', 'body' => ''];

if (is_post() && $canPost) {
    csrf_check();
    $form = ['org_id' => post('org_id'), 'title' => post('title'), 'body' => post('body')];
    $orgId = (int) $form['org_id'] ?: null;

    if (!can_post_to($orgId)) {
        $errors[] = 'You can\'t post to that organization.';
    }
    if (mb_strlen($form['title']) < 3 || mb_strlen($form['title']) > 150) {
        $errors[] = 'Title must be 3 to 150 characters.';
    }
    if (mb_strlen($form['body']) < 5 || mb_strlen($form['body']) > 3000) {
        $errors[] = 'Write the announcement (5 to 3000 characters).';
    }
    if (!$errors) {
        $status = post_initial_status($orgId);
        db_insert(
            'INSERT INTO announcements (org_id, title, body, status, posted_by) VALUES (?, ?, ?, ?, ?)',
            [$orgId, $form['title'], $form['body'], $status, $me['id']]
        );
        flash('success', $status === 'published' ? 'Announcement published.' : 'Announcement sent to the adviser. It goes live once approved.');
        redirect('announcements.php');
    }
}

$orgFilter = get('org');
$posts = list_posts(['org' => $orgFilter]);

render_header('Announcements', 'announcements');
echo page_head('Announcements', 'Updates from student councils and organizations.');
?>
<?php if ($canPost): ?>
<details class="panel compose" <?= $errors ? 'open' : '' ?>>
  <summary><?= icon('plus', 18) ?>Write an announcement</summary>
  <form method="post" class="form" novalidate>
    <?= csrf_field() ?>
    <?php foreach ($errors as $err): ?><div class="flash flash-error" role="alert"><?= e($err) ?></div><?php endforeach; ?>
    <div class="field"><label for="org_id">Post as</label>
      <select id="org_id" name="org_id">
        <?php if ($canCampus): ?><option value="">Campus-wide</option><?php endif; ?>
        <?php foreach ($postOrgs as $o): ?><option value="<?= $o['id'] ?>"<?= (string) $o['id'] === $form['org_id'] ? ' selected' : '' ?>><?= e($o['name']) ?></option><?php endforeach; ?>
      </select></div>
    <div class="field"><label for="title">Title</label><input id="title" name="title" required maxlength="150" value="<?= e($form['title']) ?>"></div>
    <div class="field"><label for="body">Message</label><textarea id="body" name="body" rows="4" maxlength="3000" required><?= e($form['body']) ?></textarea></div>
    <div class="form-actions"><button class="btn btn-primary" type="submit">Post announcement</button>
      <span class="muted small">Posts from regular officers wait for adviser approval. President, VP, advisers, and admins publish right away.</span></div>
  </form>
</details>
<?php endif; ?>

<form class="filters compact" method="get">
  <div class="field"><label for="org">Organization</label>
    <select id="org" name="org" data-autosubmit><option value="">All</option>
      <?php foreach (all_orgs() as $o): ?><option value="<?= $o['id'] ?>"<?= (string) $o['id'] === $orgFilter ? ' selected' : '' ?>><?= e($o['acronym']) ?></option><?php endforeach; ?></select></div>
  <noscript><button class="btn btn-ghost" type="submit">Apply</button></noscript>
</form>

<?php if (!$posts): ?>
  <?= empty_state('No announcements yet', $canPost ? 'Be the first to post an update.' : 'Updates will show up here.') ?>
<?php else: foreach ($posts as $p): ?>
  <article class="panel post">
    <!-- [image here] -->
    <div class="post-head">
      <div class="event-tags"><span class="org-tag"><?= e($p['org_acronym'] ?? 'Campus') ?></span><?= $p['status'] !== 'published' ? status_badge(POST_STATUS, $p['status']) : '' ?></div>
      <span class="muted small"><?= e($p['author_name']) ?> on <?= e(fmt_date($p['created_at'])) ?></span>
    </div>
    <h2><?= e($p['title']) ?></h2>
    <p><?= nl2br(e($p['body'])) ?></p>
    <?php if ($p['status'] === 'rejected' && $p['review_remarks']): ?><div class="notice notice-red"><strong>Not approved:</strong> <?= e($p['review_remarks']) ?></div><?php endif; ?>
    <div class="post-actions">
      <?php if (can_review_post($p)) echo review_controls('post_action.php', (int) $p['id'], 'approve', 'reject', 'Publish', 'Don\'t publish'); ?>
      <?php if (can_delete_post($p)) echo action_form('post_action.php', ['id' => $p['id'], 'do' => 'delete', 'back' => current_path()], 'Delete', 'btn btn-danger-ghost btn-sm', 'Delete this announcement?'); ?>
    </div>
  </article>
<?php endforeach; endif; ?>
<?php render_footer();
