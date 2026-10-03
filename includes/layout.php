<?php
function pending_approval_count(): int
{
    $ids = approver_org_ids();
    if ($ids !== null && !$ids) {
        return 0;
    }
    $n = 0;
    foreach (['SELECT COUNT(*) FROM events e WHERE e.status = \'pending\' AND %s',
              'SELECT COUNT(*) FROM tasks t JOIN events e ON e.id = t.event_id WHERE t.approval_status = \'pending\' AND %s',
              'SELECT COUNT(*) FROM announcements a WHERE a.status = \'pending\' AND a.org_id IS NOT NULL AND %s'] as $i => $tpl) {
        $params = [];
        $col = $i === 2 ? 'a.org_id' : 'e.org_id';
        $n += (int) db_val(sprintf($tpl, in_clause($col, $ids, $params)), $params);
    }
    return $n;
}

function user_role_label(array $u): string
{
    if ($u['role'] === 'system_admin') {
        return 'System Admin';
    }
    $seat = db_row(
        "SELECT m.position, o.acronym FROM org_members m JOIN organizations o ON o.id = m.org_id
         WHERE m.user_id = ? ORDER BY FIELD(m.position,'adviser','co_adviser','president','vice_president','officer','member') LIMIT 1",
        [$u['id']]
    );
    return $seat ? POSITION_LABELS[$seat['position']] . ', ' . $seat['acronym'] : ROLE_LABELS[$u['role']];
}

function nav_items(): array
{
    $items = [
        ['dashboard', 'Dashboard', 'dashboard.php', 'dashboard'],
        ['events', 'Events', 'events.php', 'events'],
        ['calendar', 'Calendar', 'calendar.php', 'calendar'],
        ['announcements', 'Announcements', 'announcements.php', 'posts'],
    ];
    if (has_any_staff_seat() || my_memberships()) {
        $items[] = ['tasks', 'Tasks', 'tasks.php', 'tasks'];
    }
    if (is_any_approver()) {
        $items[] = ['approvals', 'Approvals', 'approvals.php', 'approvals'];
    }
    $items[] = ['organizations', 'Organizations', 'organizations.php', 'orgs'];
    if (is_admin()) {
        $items[] = ['users', 'Users', 'users.php', 'users'];
    }
    return $items;
}

function head_tags(string $title): void
{
    ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> | <?= e(APP_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700&family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
</head>
<?php
}

// shell for logged-in pages
function render_header(string $title, string $active = ''): void
{
    $u = current_user();
    head_tags($title);
    $pending = is_any_approver() ? pending_approval_count() : 0;
    ?>
<body class="app">
<a class="skip-link" href="#main">Skip to content</a>
<aside class="sidebar" id="sidebar">
  <a class="brand" href="<?= e(url('dashboard.php')) ?>">
    <?= img_slot('logo', 'logo') ?>
    <span class="brand-name">Kampus<b>Konek</b></span>
  </a>
  <nav aria-label="Main">
    <?php foreach (nav_items() as [$key, $label, $href, $ico]): ?>
      <a class="nav-link<?= $active === $key ? ' active' : '' ?>" href="<?= e(url($href)) ?>"<?= $active === $key ? ' aria-current="page"' : '' ?>>
        <?= icon($ico) ?><span><?= e($label) ?></span>
        <?php if ($key === 'approvals' && $pending > 0): ?><span class="count" aria-label="<?= $pending ?> waiting"><?= $pending ?></span><?php endif; ?>
      </a>
    <?php endforeach; ?>
  </nav>
  <div class="sidebar-foot">
    <a class="user-chip" href="<?= e(url('profile.php')) ?>">
      <?= avatar($u['full_name']) ?>
      <span class="who"><strong><?= e($u['full_name']) ?></strong><small><?= e(user_role_label($u)) ?></small></span>
    </a>
    <form method="post" action="<?= e(url('actions/logout.php')) ?>"><?= csrf_field() ?>
      <button class="nav-link logout" type="submit"><?= icon('logout') ?><span>Log out</span></button>
    </form>
  </div>
</aside>
<div class="scrim" id="scrim" hidden></div>
<div class="shell">
  <header class="topbar">
    <button class="menu-btn" id="menuBtn" type="button" aria-label="Open menu" aria-controls="sidebar" aria-expanded="false"><?= icon('menu', 22) ?></button>
    <span class="topbar-brand">Kampus<b>Konek</b></span>
    <span class="role-chip"><?= e(user_role_label($u)) ?></span>
  </header>
  <main id="main">
    <?php render_flash(); ?>
<?php
}

function render_footer(): void
{
    ?>
  </main>
  <footer class="site-foot">KampusKonek, National University Laguna. Built for student councils and organizations.</footer>
</div>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
<?php
}

function render_flash(): void
{
    foreach (flash_take() as $f) {
        echo '<div class="flash flash-' . e($f['type']) . '" role="status">' . e($f['message']) . '<button type="button" class="flash-close" aria-label="Dismiss">' . icon('close', 16) . '</button></div>';
    }
}

// shell for login / register / landing (no sidebar)
function render_public_header(string $title, bool $narrow = true): void
{
    head_tags($title);
    ?>
<body class="public">
<script>window.KK = <?= json_encode(['students' => STUDENT_DOMAINS, 'admins' => ADMIN_DOMAINS]) ?>;</script>
<a class="skip-link" href="#main">Skip to content</a>
<header class="public-bar">
  <a class="brand" href="<?= e(url('index.php')) ?>">
    <?= img_slot('logo', 'logo') ?>
    <span class="brand-name">Kampus<b>Konek</b></span>
  </a>
  <nav class="public-nav" aria-label="Account">
    <a class="btn btn-ghost btn-sm" href="<?= e(url('login.php')) ?>">Log in</a>
    <a class="btn btn-gold btn-sm" href="<?= e(url('register.php')) ?>">Sign up</a>
  </nav>
</header>
<main id="main" class="<?= $narrow ? 'public-narrow' : '' ?>">
  <?php render_flash(); ?>
<?php
}

function render_public_footer(): void
{
    ?>
</main>
<footer class="site-foot public-foot">KampusKonek, National University Laguna.</footer>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
<?php
}
