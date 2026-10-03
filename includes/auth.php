<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['path' => BASE_URL !== '' ? BASE_URL : '/', 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

// ---------- email domain rules ----------
function email_domain(string $email): string
{
    $at = strrpos($email, '@');
    return $at === false ? '' : strtolower(substr($email, $at + 1));
}

// 'student' | 'admin' | null (not an institutional address)
function email_kind(string $email): ?string
{
    $domain = email_domain($email);
    if (in_array($domain, STUDENT_DOMAINS, true)) return 'student';
    if (in_array($domain, ADMIN_DOMAINS, true)) return 'admin';
    return null;
}

function domain_hint(): string
{
    return 'Use your school email: @' . STUDENT_DOMAINS[0] . ' for students and officers, or @' . ADMIN_DOMAINS[0] . ' for faculty, advisers, the President and the VP.';
}

function default_role_for(string $email): string
{
    return email_kind($email) === 'admin' ? 'adviser' : 'student';
}

// ---------- session ----------
function current_user(): ?array
{
    static $user = false;
    if ($user !== false) {
        return $user;
    }
    $user = null;
    if (!empty($_SESSION['uid'])) {
        $user = db_row('SELECT id, full_name, email, role, is_active FROM users WHERE id = ?', [$_SESSION['uid']]);
        if (!$user || !$user['is_active']) {
            $user = null;
            unset($_SESSION['uid']);
        }
    }
    return $user;
}

function login_user(int $id): void
{
    session_regenerate_id(true);
    $_SESSION['uid'] = $id;
}

function logout_user(): void
{
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

function require_login(): array
{
    $u = current_user();
    if (!$u) {
        flash('info', 'Log in to continue.');
        redirect('login.php');
    }
    return $u;
}

function require_guest(): void
{
    if (current_user()) {
        redirect('dashboard.php');
    }
}

function require_admin(): array
{
    $u = require_login();
    if ($u['role'] !== 'system_admin') {
        abort(403);
    }
    return $u;
}

function abort(int $code, string $message = ''): never
{
    http_response_code($code);
    $titles = [403 => 'You don\'t have access to this', 404 => 'We couldn\'t find that page'];
    $texts = [
        403 => 'Your role doesn\'t allow this action. If you think it should, ask your adviser or the system admin.',
        404 => 'The link may be old, or the item was removed.',
    ];
    $title = $titles[$code] ?? 'Something went wrong';
    $text = $message !== '' ? $message : ($texts[$code] ?? 'Please go back and try again.');
    if (current_user()) {
        render_header($title);
        echo '<div class="empty-state big"><h2>' . e($title) . '</h2><p>' . e($text) . '</p><a class="btn btn-primary" href="' . e(url('dashboard.php')) . '">Back to dashboard</a></div>';
        render_footer();
    } else {
        echo '<!doctype html><meta charset="utf-8"><title>' . e($title) . '</title><p>' . e($title) . '. <a href="' . e(url('login.php')) . '">Log in</a></p>';
    }
    exit;
}

// ---------- user lookups ----------
function find_user_by_email(string $email): ?array
{
    return db_row('SELECT * FROM users WHERE email = ?', [strtolower(trim($email))]);
}

function get_user(int $id): ?array
{
    return db_row('SELECT id, full_name, email, role, is_active, created_at FROM users WHERE id = ?', [$id]);
}

function check_password_rules(string $pw, string $confirm): ?string
{
    if (strlen($pw) < MIN_PASSWORD_LENGTH) {
        return 'Password must be at least ' . MIN_PASSWORD_LENGTH . ' characters.';
    }
    if ($pw !== $confirm) {
        return 'The two passwords do not match.';
    }
    return null;
}

// role follows memberships: any adviser seat -> adviser, any other seat -> officer,
// no seats -> default for the email domain. system admins are never touched.
function sync_user_role(int $userId): void
{
    $u = db_row('SELECT email, role FROM users WHERE id = ?', [$userId]);
    if (!$u || $u['role'] === 'system_admin') {
        return;
    }
    $positions = array_column(db_all('SELECT position FROM org_members WHERE user_id = ?', [$userId]), 'position');
    if (array_intersect($positions, ['adviser', 'co_adviser'])) {
        $role = 'adviser';
    } elseif ($positions) {
        $role = 'officer';
    } else {
        $role = default_role_for($u['email']);
    }
    db_run('UPDATE users SET role = ? WHERE id = ?', [$role, $userId]);
}
