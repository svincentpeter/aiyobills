<?php

// Test script exercising the 5-step lifecycle against backend PHP classes directly and through mock requests.
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Services/SlotLockService.php';
require_once __DIR__ . '/../src/Services/MidtransService.php';
require_once __DIR__ . '/../src/Services/DoorAccessService.php';

function assertCustom(bool $condition, string $label) {
    if (!$condition) {
        echo "❌ FAIL: {$label}\n";
        exit(1);
    }
    echo "✅ PASS: {$label}\n";
}

echo "=== RUNNING AUTOMATED BACKEND TESTS FOR 5-STEP SMART ROOM ACCESS ===\n\n";

// Re-seed clean test database
require_once __DIR__ . '/../database/seed.php';

$db = Database::getConnection();
$db->exec("DELETE FROM bookings");
$db->exec("DELETE FROM door_unlock_queue");
$db->exec("DELETE FROM access_logs");

$slotLockService = new SlotLockService();
$midtransService = new MidtransService();
$doorService = new DoorAccessService();

// ==========================================
// TEST STEP 1: Room Catalog & Availability
// ==========================================
echo "\n--- STEP 1: Room Selection & Catalog ---\n";
$stmt = $db->query("SELECT * FROM rooms WHERE id = 'room-vip-01'");
$room = $stmt->fetch();
assertCustom($room !== false && $room['code'] === 'ROOM-301', "Room VIP-01 is present with code ROOM-301");
assertCustom(!empty($room['static_door_token']), "Room has permanent static_door_token");

// ==========================================
// TEST STEP 2: Temporary Schedule Hold (10-Min)
// ==========================================
echo "\n--- STEP 2: Temporary Schedule Hold (10 Min) ---\n";
$today = date('Y-m-d');
$nowHour = (int) date('H');
$startHour = sprintf("%02d:00", $nowHour);

$holdData = [
    'room_id' => 'room-vip-01',
    'customer_name' => 'Budi Santoso',
    'customer_phone' => '081234567890',
    'customer_email' => 'budi@example.com',
    'date' => $today,
    'start_time' => $startHour,
    'duration_hours' => 2,
];

$holdResult = $slotLockService->createTemporaryHold($holdData);
$bookingId = $holdResult['booking_id'];
assertCustom(!empty($bookingId), "Temporary booking created with ID: {$bookingId}");
assertCustom($holdResult['status'] === 'pending_payment', "Booking status is pending_payment");
assertCustom($holdResult['lock_expires_at'] > (time() * 1000), "Lock expires in future (10 minutes)");
assertCustom(!empty($holdResult['qris_payload']), "QRIS payload generated");
assertCustom(!empty($holdResult['va_numbers']['bca']), "BCA Virtual Account generated");

// Test clash detection: trying to hold the exact same room & time should fail
$clashCaught = false;
try {
    $slotLockService->createTemporaryHold($holdData);
} catch (Exception $e) {
    $clashCaught = true;
}
assertCustom($clashCaught, "Conflicting booking correctly blocked during temporary hold");

// ==========================================
// TEST STEP 3: Midtrans Webhook (HTTP POST)
// ==========================================
echo "\n--- STEP 3: Midtrans Webhook (HTTP POST) ---\n";
$webhookPayload = [
    'order_id' => $bookingId,
    'status_code' => '200',
    'gross_amount' => (string) $holdResult['grand_total'],
    'transaction_status' => 'settlement',
    'payment_type' => 'qris',
    'is_simulation' => true,
];

$webhookResult = $midtransService->handleNotification($webhookPayload);
assertCustom($webhookResult['success'] === true, "Midtrans webhook processed successfully");
assertCustom($webhookResult['booking_status'] === 'paid', "Booking updated to 'paid' status in database");

$stmtBooking = $db->prepare("SELECT * FROM bookings WHERE id = :id");
$stmtBooking->execute(['id' => $bookingId]);
$updatedBooking = $stmtBooking->fetch();
assertCustom($updatedBooking['status'] === 'paid', "Database record verified as paid");
assertCustom($updatedBooking['lock_expires_at'] == 0, "Temporary lock released after settlement");
$accessPassToken = $updatedBooking['access_pass_token'];

// ==========================================
// TEST STEP 4: Active Ticket & Session
// ==========================================
echo "\n--- STEP 4: Active Ticket Inspection ---\n";
assertCustom(!empty($accessPassToken), "Access pass token generated: {$accessPassToken}");

// ==========================================
// TEST STEP 5: Scan Static Door QR & Unlock
// ==========================================
echo "\n--- STEP 5: Scan Static Door QR & Solenoid Unlock ---\n";

// 5A. Negative Test: Wrong Room QR scanned
$wrongDoorToken = 'SPK-DOOR:ROOM-204:sec_token_podcast_02'; // Studio 204 instead of Room 301
$wrongScanResult = $doorService->verifyDoorScan($bookingId, $wrongDoorToken, $accessPassToken);
assertCustom($wrongScanResult['success'] === false, "Negative test: Scanning wrong room door is rejected");
assertCustom(str_contains($wrongScanResult['reason'], 'Ruangan salah'), "Rejection reason clearly identifies incorrect room");

// 5B. Positive Test: Correct Room Static QR scanned
$correctDoorToken = $room['static_door_token'];
$correctScanResult = $doorService->verifyDoorScan($bookingId, $correctDoorToken, $accessPassToken);
if (!$correctScanResult['success']) {
    echo "DEBUG FAIL REASON: " . ($correctScanResult['reason'] ?? 'No reason') . "\n";
}
assertCustom($correctScanResult['success'] === true, "Positive test: Scanning correct static door QR is accepted");
assertCustom($correctScanResult['status'] === 'granted', "Door access granted");
assertCustom(!empty($correctScanResult['queue_id']), "Unlock command added to queue ID: " . $correctScanResult['queue_id']);

// ==========================================
// TEST STEP 5 (IoT): ESP32 Polling & Ack
// ==========================================
echo "\n--- STEP 5 (IoT): ESP32 Polling & Ack ---\n";
$pollResult = $doorService->pollUnlock('ROOM-301');
assertCustom($pollResult['unlock'] === true, "ESP32 poll detects pending unlock command");
assertCustom($pollResult['duration'] === 5, "Solenoid duration is 5 seconds");
$queueId = $pollResult['queue_id'];

$ackResult = $doorService->acknowledgeUnlock($queueId);
assertCustom($ackResult === true, "ESP32 successfully acknowledged relay trigger");

// Second poll should now report no pending unlocks
$secondPoll = $doorService->pollUnlock('ROOM-301');
assertCustom($secondPoll['unlock'] === false, "Subsequent ESP32 poll reports no further unlocks");

echo "\n🎉 ALL 5-STEP LIFECYCLE TESTS PASSED 100%!\n";
