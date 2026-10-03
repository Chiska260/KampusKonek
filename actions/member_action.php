<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();
if (!is_post()) {
    redirect('organizations.php');
}
csrf_check();

$orgId = (int) post('org_id');
$org = get_org($orgId) ?? abort(404);
assignable_positions($orgId) || abort(403);   // empty list = not allowed to manage this org
$back = 'org_view.php?id=' . $orgId;

switch (post('do')) {
    case 'save':
        $err = save_member($orgId, strtolower(post('email')), post('position'));
        $err ? flash('error', $err) : flash('success', 'Member saved.');
        break;

    case 'remove':
        $err = remove_member($orgId, (int) post('member_id'));
        $err ? flash('error', $err) : flash('success', 'Member removed from the organization.');
        break;

    default:
        abort(404);
}
redirect($back);
