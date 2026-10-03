<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (current_user()) {
    redirect('dashboard.php');
}
$demoDay = strtotime('+5 days');
render_public_header('Welcome', false);
?>
<section class="hero">
  <div class="hero-copy">
    <h1>Every campus event and task, in one place.</h1>
    <p class="lead">KampusKonek keeps your council's events, delegated tasks, and adviser approvals together, so nothing gets buried in the group chat.</p>
    <div class="hero-actions">
      <a class="btn btn-primary" href="<?= e(url('login.php')) ?>">Log in</a>
      <a class="btn btn-ghost" href="<?= e(url('register.php')) ?>">Create an account</a>
    </div>
    <p class="fine">Sign in with your <strong>@students.nu-laguna.edu.ph</strong> or <strong>@admin.nu-laguna.edu.ph</strong> email.</p>
  </div>
  <div class="hero-demo" aria-hidden="true">
    <!-- [image here] -->
    <div class="img-slot banner"><span>[image here]</span></div>
    <article class="event-card demo">
      <div class="date-tab"><span class="m"><?= date('M', $demoDay) ?></span><span class="d"><?= date('j', $demoDay) ?></span></div>
      <div class="event-main">
        <div class="event-tags"><span class="org-tag">SCS-SC</span><span class="when soon">In 5 days</span></div>
        <h3>SCS General Assembly</h3>
        <p class="meta"><span><?= icon('pin', 15) ?>AVR 1</span><span><?= icon('clock', 15) ?>2:00 PM to 5:00 PM</span></p>
        <div class="progress"><span class="progress-done" style="width:50%"></span><span class="progress-doing" style="width:33%"></span></div>
        <div class="progress-label"><strong>50%</strong> <span>3 of 6 tasks done</span></div>
      </div>
    </article>
  </div>
</section>

<section class="flow">
  <h2>How a council event moves</h2>
  <ol class="flow-steps">
    <li><h3>Officers draft</h3><p>Create the event, then assign tasks to committee members with deadlines.</p></li>
    <li><h3>Advisers approve</h3><p>Advisers review event and task requests and approve or send them back with a reason.</p></li>
    <li><h3>Everyone follows along</h3><p>Students see approved events and announcements. Officers watch the progress bar fill up.</p></li>
  </ol>
</section>

<section class="roles">
  <h2>Built around who you are on campus</h2>
  <div class="role-grid">
    <div><h3>Students</h3><p>See approved events, the campus calendar, and updates from every organization.</p></div>
    <div><h3>Officers and committee</h3><p>Draft events, post updates, and keep your assigned tasks moving.</p></div>
    <div><h3>Advisers</h3><p>Review requests in one queue. Approve, or disapprove with feedback.</p></div>
    <div><h3>System admin</h3><p>Set up organizations, assign advisers, and manage accounts.</p></div>
  </div>
</section>
<?php render_public_footer();
