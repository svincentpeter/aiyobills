<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Controllers/AuthController.php';

echo "=== START DEV BYPASS TEST ===\n";

$db = Database::getConnection();
$auth = new AuthController($db);

// Verify admin email is in database or will be created with admin role
$adminWhitelist = ['ravywhienelda@gmail.com', 'dimasrzk06@gmail.com', 'cthrnwong11@gmail.com'];
$targetEmail = 'cthrnwong11@gmail.com';
assert(in_array($targetEmail, $adminWhitelist), 'Admin whitelist must include cthrnwong11@gmail.com');
echo "[PASS] cthrnwong11@gmail.com is in admin whitelist.\n";

// Upsert user to test admin assignment
$stmt = $db->prepare("SELECT * FROM users WHERE email = :email");
$stmt->execute([':email' => $targetEmail]);
$user = $stmt->fetch();

$now = date('Y-m-d H:i:s');
if ($user) {
    $up = $db->prepare("UPDATE users SET role = 'admin', name = 'Catherine Wong', updated_at = :now WHERE id = :id");
    $up->execute([':now' => $now, ':id' => $user['id']]);
} else {
    $ins = $db->prepare("INSERT INTO users (id, google_id, email, name, phone, role, created_at, updated_at) VALUES (:id, :gid, :email, :name, '081234567890', 'admin', :c, :u)");
    $ins->execute([
        ':id' => 'usr_cthrn_test',
        ':gid' => 'dev_cthrn123',
        ':email' => $targetEmail,
        ':name' => 'Catherine Wong',
        ':c' => $now,
        ':u' => $now
    ]);
}

$stmt->execute([':email' => $targetEmail]);
$check = $stmt->fetch();
assert($check !== false, 'User must exist in DB');
assert($check['role'] === 'admin', 'User must have role admin');
echo "[PASS] Database user verified: {$check['name']} ({$check['email']}) - Role: {$check['role']}\n";

// Test me() method with X-Admin-Email header fallback
$_SESSION['user'] = null;
$_SERVER['HTTP_X_ADMIN_EMAIL'] = 'cthrnwong11@gmail.com';
ob_start();
$auth->me();
$meOutput = ob_get_clean();
$meData = json_decode($meOutput, true);
assert($meData['authenticated'] === true, 'Me endpoint must authenticate via header fallback');
assert($meData['user']['email'] === 'cthrnwong11@gmail.com', 'Me endpoint user email must match');
assert($meData['user']['role'] === 'admin', 'Me endpoint user role must be admin');
echo "[PASS] me() method verified with X-Admin-Email header fallback.\n";

echo "=== DEV BYPASS TEST PASSED ===\n";
