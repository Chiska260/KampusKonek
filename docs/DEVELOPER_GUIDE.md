# KampusKonek Developer Guide

Read this when you want to change something and aren't sure where it lives. The last section, **When You Feel Lost**, is the one to bookmark.

## 1. The big picture

- Plain PHP 8.1+ (no framework, no Composer), MySQL through PDO, one CSS file, one small JS file.
- Every page is a PHP file in the project root. Each one starts with `require_once 'includes/bootstrap.php'`, which loads config, the database helpers, session/auth, the permission rules, and the layout.
- Anything that **changes data** goes through a POST handler in `actions/` (approve, disapprove, delete, status change, member changes, user admin). Forms that need to keep what you typed when validation fails (event form, task form, login, register, announcements) post to their own page instead.
- Every POST checks a CSRF token, then a permission function, then does the query, then redirects with a flash message. Pages never echo raw user text, they go through `e()`.

### Folder map

```
config/config.php        DB login, email domains, timezone, BASE_URL detection
includes/bootstrap.php   loads everything in the right order
includes/db.php          db(), db_all(), db_row(), db_val(), db_insert(), in_clause()
includes/helpers.php     labels (statuses, roles), e(), url(), flash, csrf, date formatting, progress_bar()
includes/auth.php        session start, email-domain rules, login/logout, require_login(), abort()
includes/permissions.php who-can-see-what (seats, scopes, visibility SQL)
includes/orgs.php        organizations, members, adviser rules
includes/events.php      event queries + can_*_event() rules + form validation
includes/tasks.php       task queries + can_*_task() rules + form validation
includes/posts.php       announcement queries + rules
includes/components.php  reusable HTML: icon(), event_card(), task_table(), review_controls(), chart_block()
includes/layout.php      sidebar shell, public shell, flash output
views/dashboard_*.php    the four role dashboards (admin, adviser, officer, student)
actions/*.php            POST handlers
assets/css/style.css     all styling, colors are variables at the top
assets/js/app.js         mobile menu, autosubmit filters, confirm dialogs, email hint, tiny charts
database/kampuskonek.sql schema + demo data
```

`includes/`, `config/`, `views/`, `database/`, and `docs/` have an `.htaccess` that blocks direct browser access on Apache.

## 2. Database and relationships

```
users 1---* org_members *---1 organizations
organizations 1---* events 1---* tasks
organizations 1---* announcements        (org_id NULL = campus-wide)
users 1---* events.created_by / reviewed_by
users 1---* tasks.created_by / assigned_to / reviewed_by
```

| Table | What it holds | Key columns |
|---|---|---|
| `users` | Accounts | `role` (system_admin, adviser, officer, student), `is_active` |
| `organizations` | Councils and orgs | `type` (council, organization), `is_active` |
| `org_members` | **The role relationship.** One seat per person per organization | `position` (adviser, co_adviser, president, vice_president, officer, member), unique (`org_id`, `user_id`) |
| `events` | Event requests | `status` (draft, pending, approved, disapproved, cancelled), `review_remarks` |
| `tasks` | Tasks inside an event | `status` (todo, in_progress, done), `approval_status` (pending, approved, disapproved), `is_restricted` |
| `announcements` | Updates | `status` (pending, published, rejected) |

## 3. Role hierarchy logic

There are two layers. Mixing them up is the number one source of confusion.

**Layer 1: `users.role`** is the *kind of account*. It is a label and a default, not a power.

- Set at sign-up from the email domain: student domain gives `student`, admin domain gives `adviser`.
- `sync_user_role()` (in `auth.php`) recalculates it whenever seats change: any Adviser/Co-Adviser seat means `adviser`, any other seat means `officer`, no seats falls back to the domain default.
- `system_admin` is the only value that gives real power by itself, and the sync function never touches it.
- It also picks the **dashboard** (`dashboard_kind()` in `permissions.php`).

**Layer 2: `org_members.position`** is the *seat in one organization*. All real permissions come from here.

```
system admin  >  adviser / co-adviser  >  president / vice president  >  officer  >  committee member  >  student
 (everything)    (approve + override)       (override, no approving)      (draft,    (own task status)    (read approved)
                                                                           assign)
```

### Permission helper cheat sheet (`includes/permissions.php`)

| Function | True when |
|---|---|
| `is_admin()` | account role is `system_admin` |
| `is_approver($orgId)` | admin, or Adviser/Co-Adviser seat in that org |
| `is_executive($orgId)` | President or VP seat in that org |
| `has_override($orgId)` | approver or executive. Used for "restricted" rights |
| `is_org_staff($orgId)` | override roles or Officer seat. Can draft events, assign tasks, post |
| `is_org_member($orgId)` | any seat in that org, or admin |
| `approver_org_ids()`, `staff_org_ids()`, `member_org_ids()` | list of org ids, or `null` for admin (meaning "all") |

Rules for individual items sit next to their data: `can_edit_event`, `can_submit_event`, `can_review_event`, `can_cancel_event`, `can_delete_event` (events.php); `can_create_task`, `can_edit_task`, `can_update_task_status`, `can_review_task`, `can_delete_task` (tasks.php); `can_post_to`, `can_review_post`, `can_delete_post` (posts.php); `assignable_positions()` (orgs.php).

**Rule of thumb:** never write a role check inside a page. Add or change a `can_*` function, then call it from the page *and* from the action file. The page uses it to show or hide a button, the action uses it to refuse the request. Both need it.

### The guardrails from the proposal, and where they are enforced

| Rule | Where |
|---|---|
| Committee members can't change restricted or admin settings | `can_edit_task()` and `can_update_task_status()` return false for restricted tasks unless `has_override()`. `task_form.php` also ignores the `is_restricted` field from non-override users |
| Only admins, advisers, and executives override | `has_override()` |
| Every org has an Adviser and a Co-Adviser | `org_form.php` requires both on creation; `guard_last_adviser()` blocks removing the last one; `org_missing_advisers()` drives the warnings |
| Advisers approve or disapprove | `can_review_event/task/post()`, `actions/*_action.php` |
| Email domains | `email_kind()` in `auth.php`; seat domain rules in `save_member()` |
| Unregistered login goes to sign up | `login.php` |
| No OTP or verification | there is none, `register.php` logs the user in directly |

## 4. Data flow

### Event lifecycle

```
officer creates  ->  draft  --submit-->  pending  --adviser approves-->  approved  (visible to students)
                       ^                    |
                       |                    +--adviser disapproves (reason required)-->  disapproved
                       +----- officer edits disapproved event ------------------------------+
approved + edited by non-adviser  ->  back to pending
adviser/admin creates  ->  approved immediately
approved/pending  --President, VP, adviser, admin cancel-->  cancelled
```

Files: form is `event_form.php`; transitions are in `actions/event_action.php` (`submit`, `approve`, `disapprove`, `cancel`, `delete`); the "what status after an edit" decision is `status_after_edit()` in `events.php`.

### Task lifecycle

Two separate fields, do not confuse them:

- `approval_status`: pending, approved, disapproved. Decided by an adviser. Created as `approved` when the creator has override rights, otherwise `pending` (`task_initial_approval()`).
- `status`: todo, in_progress, done. Changed by the assignee (or an override role), only after approval, and never on restricted tasks by the assignee.

Progress bars count **approved tasks only**. The numbers come from `event_progress()` and the subqueries in `EVENT_SELECT` (events.php). A change in either field is reflected everywhere because nothing is cached.

### Visibility

- Events: `event_visibility()` gives everyone approved events, plus every status for organizations you belong to. Admin sees all.
- Tasks: `task_visibility()` limits to your organizations plus tasks assigned to you.
- Announcements: `list_posts()` shows published to everyone, plus pending/rejected for org members and the author.

Every list query uses these. If you write a new query that returns events or tasks, call them, otherwise you will leak drafts to students.

### Request lifecycle (what happens on a click)

1. Browser asks for a page. `bootstrap.php` loads and `require_login()` runs.
2. The page runs its queries (using `db_*` helpers with `?` placeholders) and builds HTML with `e()`.
3. A button posts to `actions/xyz_action.php` with a hidden `csrf` and `back` field.
4. The action: `csrf_check()`, loads the row, calls the `can_*()` function (`abort(403)` if not allowed), runs the update, calls `flash()`, then `redirect($back)`.
5. The next page load calls `render_flash()` and shows the message.

`safe_back()` only lets `back` point to our own pages, which prevents open redirects.

## 5. Recipes

### Add a new page

1. Create `mypage.php` in the root, start with `require_once __DIR__ . '/includes/bootstrap.php'; require_login();`.
2. Check permissions with an existing helper, or `abort(403)`.
3. Call `render_header('Title', 'navkey')`, echo your HTML, call `render_footer()`.
4. Add it to `nav_items()` in `includes/layout.php` if it needs a menu link.

### Add a button that changes data

1. Render it with `action_form('my_action.php', ['id' => $id, 'do' => 'something', 'back' => current_path()], 'Label')`.
2. In `actions/my_action.php`, copy the pattern from `actions/task_action.php`: `csrf_check()`, load, `can_*()`, query, `flash()`, `redirect()`.

### Change who is allowed to do something

Edit the `can_*` function (or `assignable_positions()` for seats). Test it with a student, a committee member, an officer, the President, an adviser, and the admin. The demo accounts in the README cover all of them.

### Add a new status to events

1. Add it to the `ENUM` in `database/kampuskonek.sql` (and `ALTER TABLE` your live DB).
2. Add its label and color to `EVENT_STATUS` in `helpers.php`.
3. Handle it in `events.php` rules and `actions/event_action.php`.

### Change the look

Colors, fonts, and radii are CSS variables at the top of `assets/css/style.css`. The brand navy is `--navy: #303B70`. The proposal listed `#303B70` for both gold and navy, so the gold (`--gold: #F5B82E`) was picked to match the NU logo. Change it if your adviser has an official value. The softer blues and ambers come from the reference palette.

### Replace the `[image here]` placeholders

Search the project for `[image here]`. Placeholders come from two helpers in `helpers.php`:

- `img_slot($label, $class)` for logos, banners, flyers, organization logos.
- `avatar($name)` for people, currently initials in a gold circle.

To use a real image, change the helper to output an `<img>` when a file exists, or replace the spot in the page. The `.img-slot` CSS class sizes each spot.

### Reset the demo data

Re-import `database/kampuskonek.sql`. It drops and recreates every table, so you lose anything you added.

## 6. Conventions

- Queries: always `db_all/db_row/db_val/db_run/db_insert` with `?` placeholders. Never put `$_GET`/`$_POST` values inside the SQL string.
- Output: always `e($value)`. Dates and times go through `fmt_date()`, `fmt_time()`.
- Forms: include `csrf_field()`. Posting handlers call `csrf_check()` first.
- Pages stay thin. Rules live in `includes/`. If a page grows long `if` chains about roles, move them into a `can_*` function.
- Comments explain *why* or point to a rule. They don't narrate the next line.

## 7. When You Feel Lost

Start here. Find the symptom, then follow the fix.

### Quick map: "I want to change X, which file?"

| I want to change... | Open |
|---|---|
| What a role can do | `includes/permissions.php`, then the `can_*` functions in `events.php`, `tasks.php`, `posts.php` |
| Who can be assigned which seat | `includes/orgs.php` (`assignable_positions`, `save_member`) |
| An approval step | `actions/event_action.php`, `task_action.php`, `post_action.php` |
| What a dashboard shows | `views/dashboard_<role>.php` |
| A list's filters | the page itself (`events.php`, `tasks.php`) and the `list_*` function it calls |
| The sidebar links | `nav_items()` in `includes/layout.php` |
| Allowed email domains | `config/config.php` |
| Status names and colors | constants at the top of `includes/helpers.php`, badge colors in `style.css` |

### Symptoms and fixes

**Blank white page.** PHP hit an error and is hiding it. In `php.ini` set `display_errors=On`, restart Apache, reload. Also check `C:\xampp\apache\logs\error.log`.

**"Cannot connect to the database."** MySQL isn't running, or the login in `config/config.php` is wrong. Start MySQL in XAMPP and check `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`. Confirm `kampuskonek_db` exists in phpMyAdmin.

**Page has no styling, links go to the wrong place.** `BASE_URL` was detected wrong (it is figured out in `config/config.php`). Open the site as `http://localhost/kampuskonek/`, not through a different alias. If it still fails, hard-code `define('BASE_URL', '/kampuskonek');` in `config.php`.

**I keep getting sent back to the login page.** Sessions aren't sticking. Check that cookies are enabled and that the PHP session folder is writable. If you changed the folder name, clear cookies for localhost.

**"Your session expired" (HTTP 419).** The CSRF token didn't match. The page was open too long, or a form is missing `csrf_field()`. New forms must include it.

**"You don't have access to this" (403) when it should work.**
1. Check the person's seat on their organization page. Seats, not account types, give power.
2. Open the matching `can_*` function and read it with that seat in mind.
3. Remember `has_override()` includes advisers and executives but `is_approver()` does not include executives. Approving is advisers only.
4. For tasks, check `is_restricted` and `approval_status` in phpMyAdmin.

**A student can see something they shouldn't (or can't see something they should).** The query isn't using `event_visibility()` or `task_visibility()`. Only `status = 'approved'` events are public.

**Event is approved but not on the calendar.** Calendar shows non-cancelled events visible to you inside the selected month. Check `event_date` and `status`.

**Progress bar says 0% or "No approved tasks yet".** Tasks need `approval_status = 'approved'`. Look at the Approvals page or the task's badge.

**Can't add someone as a member.** Read the red message. Usually the person hasn't signed up, or the email domain doesn't match the position (`ADMIN_DOMAIN_POSITIONS` in `orgs.php`), or the org already has a President or VP.

**Can't remove an adviser.** By design. Every organization must keep at least one Adviser and one Co-Adviser. Add the replacement first (`guard_last_adviser()`).

**An account has the wrong "account type".** It follows seats. Fix the seat and `sync_user_role()` fixes the label. System admin is the one value set by hand (Users page).

**New page throws "Call to undefined function".** The function lives in a file that `bootstrap.php` doesn't load yet, or you forgot `require_once __DIR__ . '/includes/bootstrap.php'` at the top.

**SQL error "Unknown column".** You edited the schema file but not your live database. Re-import the SQL file or run an `ALTER TABLE`.

**Dates look off by a day.** Timezone is `Asia/Manila` (`APP_TIMEZONE`) for PHP and `+08:00` for MySQL (`db.php`). Keep them in sync.

**Charts are empty.** `assets/js/app.js` didn't load (check the browser console) or the canvas data is empty. The legend next to each chart still shows the real numbers, so compare with that.

**Fonts look plain.** The Google Fonts link in `layout.php` needs internet. Offline, the CSS falls back to system fonts. Everything still works.

### A debugging routine that works

1. Reproduce it as the exact demo account that has the problem.
2. Look at the URL and the page file it points to.
3. Find the function or action it calls (`grep` the file name or button label).
4. Check the data in phpMyAdmin, usually `org_members`, then the item's status columns.
5. Read the `can_*` function with that data in your head.
6. Fix one thing, reload, retest with a second role to make sure you didn't open a hole.

### Before you hand it in: manual test checklist

- Sign up with a student email, an admin email, and a bad email (rejected).
- Log in with an unregistered school email (goes to sign up).
- As officer: draft an event, submit it, request a task. Confirm you can't approve.
- As adviser: approve one, disapprove one with a reason. Confirm the officer sees the reason.
- As committee member: update your own task, try someone else's (blocked), try a restricted one (blocked).
- As President: edit a restricted task, create an auto-approved task.
- As admin: create an organization (needs two advisers), try to remove the last adviser (blocked), reset a password, deactivate a user.
- As student: confirm drafts, pending events, and task lists are invisible.
- Resize the browser to phone width and open the menu.

## 8. Known limits (good "future work" for your defense)

- Password reset is done by the admin, no email flow (the proposal asked for simple auth).
- No login rate limiting. Add a failed-attempt counter in `login.php` if it ever goes public.
- Email domains are checked, but ownership of the mailbox is not (no verification by design).
- Notifications are in-app only (the Approvals badge, flash messages and status badges).
- Images are placeholders until real files are added.
