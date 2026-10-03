<?php
// ---------- labels used all over the UI ----------
const EVENT_STATUS = [
    'draft'       => ['Draft', 'muted'],
    'pending'     => ['Awaiting approval', 'amber'],
    'approved'    => ['Approved', 'green'],
    'disapproved' => ['Disapproved', 'red'],
    'cancelled'   => ['Cancelled', 'muted'],
];
const TASK_STATUS = [
    'todo'        => ['To do', 'muted'],
    'in_progress' => ['In progress', 'blue'],
    'done'        => ['Done', 'green'],
];
const APPROVAL_STATUS = [
    'pending'     => ['Awaiting approval', 'amber'],
    'approved'    => ['Approved', 'green'],
    'disapproved' => ['Disapproved', 'red'],
];
const POST_STATUS = [
    'pending'   => ['Awaiting approval', 'amber'],
    'published' => ['Published', 'green'],
    'rejected'  => ['Not approved', 'red'],
];
const PRIORITY = [
    'low'    => ['Low', 'muted'],
    'medium' => ['Medium', 'blue'],
    'high'   => ['High', 'red'],
];
const ROLE_LABELS = [
    'system_admin' => 'System Admin',
    'adviser'      => 'Adviser',
    'officer'      => 'Officer / Committee',
    'student'      => 'Student',
];
const POSITION_LABELS = [
    'adviser'        => 'Adviser',
    'co_adviser'     => 'Co-Adviser',
    'president'      => 'President',
    'vice_president' => 'Vice President',
    'officer'        => 'Officer',
    'member'         => 'Committee Member',
];

// ---------- output + routing ----------
function e($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

function redirect(string $path): never
{
    $target = (str_starts_with($path, '/') || str_starts_with($path, 'http')) ? $path : url($path);
    header('Location: ' . $target);
    exit;
}

// only lets us bounce back to our own pages (no open redirects)
function safe_back(?string $back, string $fallback): string
{
    if ($back && preg_match('/^[a-z_]+\.php(\?[\w=&%.\-]*)?$/i', $back)) {
        return $back;
    }
    return $fallback;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
}

function post(string $key, string $default = ''): string
{
    return trim((string) ($_POST[$key] ?? $default));
}

function get(string $key, string $default = ''): string
{
    return trim((string) ($_GET[$key] ?? $default));
}

function old(string $key, $default = ''): string
{
    return e($_POST[$key] ?? $default);
}

// ---------- flash messages ----------
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flash_take(): array
{
    $all = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $all;
}

// ---------- csrf ----------
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $sent = $_POST['csrf'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        exit('Your session expired. Go back, refresh the page, and try again.');
    }
}

// ---------- validation + formatting ----------
function valid_date(string $s): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $s);
    return $d && $d->format('Y-m-d') === $s;
}

function valid_time(string $s): bool
{
    return (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $s);
}

function fmt_date(?string $d, string $format = 'M j, Y'): string
{
    return $d ? date($format, strtotime($d)) : '';
}

function fmt_time(?string $t): string
{
    return $t ? date('g:i A', strtotime($t)) : '';
}

function fmt_datetime(?string $dt): string
{
    return $dt ? date('M j, Y g:i A', strtotime($dt)) : '';
}

function fmt_time_range(?string $start, ?string $end): string
{
    if (!$start) {
        return 'Time to be announced';
    }
    return $end ? fmt_time($start) . ' to ' . fmt_time($end) : fmt_time($start);
}

function days_until(string $date): int
{
    return (int) floor((strtotime($date) - strtotime(date('Y-m-d'))) / 86400);
}

function days_label(string $date): string
{
    $n = days_until($date);
    if ($n === 0) return 'Today';
    if ($n === 1) return 'Tomorrow';
    if ($n === -1) return 'Yesterday';
    return $n > 0 ? "In $n days" : abs($n) . ' days ago';
}

function excerpt(string $text, int $len = 140): string
{
    $text = trim(preg_replace('/\s+/', ' ', $text));
    return mb_strlen($text) > $len ? mb_substr($text, 0, $len - 1) . '…' : $text;
}

function initials(string $name): string
{
    $name = preg_replace('/^(Prof\.|Dr\.|Mr\.|Ms\.|Mrs\.)\s+/', '', trim($name));
    $parts = preg_split('/\s+/', $name);
    $out = mb_substr($parts[0] ?? '', 0, 1);
    if (count($parts) > 1) {
        $out .= mb_substr(end($parts), 0, 1);
    }
    return mb_strtoupper($out);
}

// ---------- tiny html builders ----------
function badge(string $text, string $tone = 'muted'): string
{
    return '<span class="badge badge-' . e($tone) . '">' . e($text) . '</span>';
}

function status_badge(array $map, string $key): string
{
    [$label, $tone] = $map[$key] ?? [$key, 'muted'];
    return badge($label, $tone);
}

// image slots: we don't ship real images, just marked spots to fill in later
function img_slot(string $label = 'image', string $class = ''): string
{
    return "<!-- [image here] -->\n" .
        '<span class="img-slot ' . e($class) . '" role="img" aria-label="' . e($label) . ' placeholder"><span>[image here]</span></span>';
}

function avatar(string $name, string $class = ''): string
{
    return "<!-- [image here] -->\n" . '<span class="avatar ' . e($class) . '" aria-hidden="true">' . e(initials($name)) . '</span>';
}

// segmented bar: green = done, blue = in progress, rest = to do
function progress_bar(array $p, bool $withLabel = true): string
{
    $total = (int) ($p['total'] ?? 0);
    $done  = (int) ($p['done'] ?? 0);
    $doing = (int) ($p['doing'] ?? 0);
    $pct   = $total > 0 ? (int) round($done / $total * 100) : 0;
    $donePct  = $total > 0 ? $done / $total * 100 : 0;
    $doingPct = $total > 0 ? $doing / $total * 100 : 0;

    $html  = '<div class="progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="' . $pct . '">';
    $html .= '<span class="progress-done" style="width:' . round($donePct, 1) . '%"></span>';
    $html .= '<span class="progress-doing" style="width:' . round($doingPct, 1) . '%"></span>';
    $html .= '</div>';
    if ($withLabel) {
        $html .= '<div class="progress-label">';
        $html .= $total > 0
            ? '<strong>' . $pct . '%</strong> <span>' . $done . ' of ' . $total . ' tasks done</span>'
            : '<span>No approved tasks yet</span>';
        $html .= '</div>';
    }
    return $html;
}
