<?php

declare(strict_types=1);

use App\Repositories\Connection;

require dirname(__DIR__) . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

$db = new Connection();
$failures = [];

$requiredColumns = [
    'venue_events' => ['id', 'name', 'venue_id', 'start_date', 'end_date'],
    'venues' => ['id', 'user_id', 'name', 'address'],
    'venue_events_tickets' => ['id', 'id_venue_event', 'ticket_sales_enabled'],
    'ticket_types' => ['id', 'id_venue_event_tickets', 'name'],
    'ticket_sales' => ['id', 'id_ticket_type', 'buyer_email', 'created_at'],
    'cms_contents' => ['id', 'id_owner', 'site_key', 'slug', 'status'],
];

foreach ($requiredColumns as $table => $columns) {
    $db->query('SHOW COLUMNS FROM `' . $table . '`');
    $actual = array_map(static fn (object $column): string => $column->Field, $db->fetchAll());
    foreach ($columns as $column) {
        if (!in_array($column, $actual, true)) {
            $failures[] = $table . '.' . $column . ' is missing';
        }
    }
}

$requiredTables = [
    'ecosystem_memberships', 'ecosystem_workspaces', 'ecosystem_entitlements',
    'ecosystem_software_registry', 'ecosystem_sso_tokens', 'tech_lab_member_requests',
    'tech_lab_events', 'tech_lab_event_rsvps', 'tech_lab_saved_tool_results',
    'tech_lab_featured_content',
];

foreach ($requiredTables as $table) {
    $db->query('SHOW TABLES LIKE :table');
    $db->bind(':table', $table);
    if (!$db->fetchOne()) {
        $failures[] = $table . ' has not been installed';
    }
}

$db->query("SELECT COUNT(*) total FROM ecosystem_software_registry WHERE tenant_key='miamitechlab' AND product_key='ophyra' AND required_level=2 AND is_active=1");
$software = $db->fetchOne();
if (!$software || (int) $software->total !== 1) {
    $failures[] = 'Ophyra registry entry is not correctly scoped to Tech Lab Level 2';
}

if ($failures) {
    fwrite(STDERR, "FAILED\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "PASS: shared schema, tenant isolation primitives and Level 2 Ophyra registry are ready.\n";

if (!in_array('--exercise', $argv, true)) {
    exit(0);
}

$db->query("SELECT id FROM users WHERE is_active=1 ORDER BY (level=1) DESC,id LIMIT 1");
$testUser = $db->fetchOne();
if (!$testUser) {
    fwrite(STDERR, "FAILED: no active local user is available for the reversible exercise.\n");
    exit(1);
}

$service = new App\Services\TechLabMembershipService();
$existing = $service->membershipFor((int) $testUser->id);
if ($existing) {
    fwrite(STDERR, "SKIP: selected local user already has a Tech Lab membership; no fixture was changed.\n");
    exit(0);
}

$membershipId = null;
try {
    $membership = $service->enroll((int) $testUser->id);
    $membershipId = (int) $membership->id;
    $data = $service->dashboardData((int) $testUser->id);
    if ((int) $data['membership']->role_level !== 2 || !$data['software']) {
        throw new RuntimeException('Level 2 dashboard contract failed.');
    }
    $url = $service->activateOphyra((int) $testUser->id);
    if (!str_contains($url, '/ecosystem/sso?token=')) {
        throw new RuntimeException('Ophyra activation did not create an SSO URL.');
    }
    $db->query("SELECT e.status,e.activated_at,e.expires_at,w.status workspace_status,t.consumed_at,t.expires_at token_expiry FROM ecosystem_memberships m JOIN ecosystem_entitlements e ON e.membership_id=m.id AND e.product_key='ophyra' JOIN ecosystem_workspaces w ON w.membership_id=m.id JOIN ecosystem_sso_tokens t ON t.membership_id=m.id WHERE m.id=:membership ORDER BY t.id DESC LIMIT 1");
    $db->bind(':membership', $membershipId);
    $activation = $db->fetchOne();
    if (!$activation || $activation->status !== 'ACTIVE' || !$activation->activated_at || !$activation->expires_at || $activation->consumed_at !== null) {
        throw new RuntimeException('Lazy workspace or 12-month entitlement activation failed.');
    }
    parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
    $tokenHash = hash('sha256', (string) ($query['token'] ?? ''));
    $db->query("UPDATE ecosystem_sso_tokens SET consumed_at=NOW() WHERE token_hash=:hash AND audience='ophyra' AND consumed_at IS NULL AND expires_at>=NOW()");
    $db->bind(':hash', $tokenHash);
    $db->execute();
    if ($db->rowCount() !== 1) {
        throw new RuntimeException('One-use Ophyra token could not be consumed.');
    }
    $db->query("SELECT id FROM ecosystem_sso_tokens WHERE token_hash=:hash AND consumed_at IS NULL AND expires_at>=NOW()");
    $db->bind(':hash', $tokenHash);
    if ($db->fetchOne()) {
        throw new RuntimeException('Consumed Ophyra token remained reusable.');
    }
    $db->query("UPDATE ecosystem_entitlements e JOIN ecosystem_workspaces w ON w.membership_id=e.membership_id SET e.status='ACTIVE',e.expires_at=DATE_SUB(NOW(),INTERVAL 1 DAY),w.status='ACTIVE' WHERE e.membership_id=:membership AND e.product_key='ophyra'");
    $db->bind(':membership', $membershipId);
    $db->execute();
    $service->dashboardData((int) $testUser->id);
    $db->query("SELECT e.status,w.status workspace_status FROM ecosystem_entitlements e JOIN ecosystem_workspaces w ON w.membership_id=e.membership_id WHERE e.membership_id=:membership AND e.product_key='ophyra'");
    $db->bind(':membership', $membershipId);
    $expired = $db->fetchOne();
    if (!$expired || $expired->status !== 'EXPIRED' || $expired->workspace_status !== 'READ_ONLY') {
        throw new RuntimeException('Expiry did not preserve the workspace in read-only mode.');
    }
    echo "PASS: enrollment, Level 2 dashboard, lazy workspace, one-use SSO and read-only expiry exercised.\n";
} finally {
    if ($membershipId) {
        foreach (['ecosystem_sso_tokens', 'tech_lab_saved_tool_results', 'tech_lab_member_requests', 'tech_lab_event_rsvps', 'ecosystem_entitlements', 'ecosystem_workspaces'] as $table) {
            $db->query("DELETE FROM `{$table}` WHERE membership_id=:membership");
            $db->bind(':membership', $membershipId);
            $db->execute();
        }
        $db->query("DELETE FROM ecosystem_memberships WHERE id=:membership AND tenant_key='miamitechlab'");
        $db->bind(':membership', $membershipId);
        $db->execute();
    }
}
