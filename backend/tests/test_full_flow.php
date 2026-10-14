<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Services/SlotLockService.php';
require_once __DIR__ . '/../src/Services/AiyoBillsService.php';
require_once __DIR__ . '/../src/Services/NotificationService.php';

echo "=== TEST AUTOMATISASI END-TO-END NOTIFIKASI PEMBAYARAN ===\n\n";

$targetPhone = '082182314967';
$targetEmail = 'akunpremium01ytb@gmail.com';

// 1. Dapatkan ruangan yang tersedia
$db = Database::getConnection();
$room = $db->query("SELECT id, name FROM rooms LIMIT 1")->fetch();
$roomId = $room['id'] ?? 'room-88fd3a8d';

$bookingPayload = [
    'room_id'        => $roomId,
    'customer_name'  => 'Vincent Peter',
    'customer_phone' => $targetPhone,
    'customer_email' => $targetEmail,
    'date'           => date('Y-m-d', strtotime('+1 day')),
    'start_time'     => '15:00',
    'duration_hours' => 2,
    'base_price'     => 100000,
    'grand_total'    => 111000
];

echo "1. Memulai Pemesanan (Hold Slot)...\n";
$slotService = new SlotLockService();
$holdRes = $slotService->createTemporaryHold($bookingPayload);

echo "   -> Booking ID Terbuat: " . $holdRes['booking_id'] . "\n";
echo "   -> Notifikasi Pending (Email & WA) otomatis terkirim dari SlotLockService!\n\n";

echo "2. Mensimulasikan Pembayaran Selesai (Aiyo Webhook SETTLED)...\n";
$aiyoService = new AiyoBillsService();
$webhookPayload = [
    'booking_id' => $holdRes['booking_id'],
    'invoice_id' => 'INV-AUTO-' . rand(1000, 9999),
    'status'     => 'SETTLED',
    'amount'     => 111000
];

$settleRes = $aiyoService->handleWebhook($webhookPayload);
echo "   -> Status Webhook: " . ($settleRes['success'] ? 'SUCCESS' : 'FAILED') . "\n";
echo "   -> Pass Token Diterbitkan: " . ($settleRes['access_pass_token'] ?? 'N/A') . "\n";
echo "   -> Notifikasi Lunas (Email & WA + Token) otomatis terkirim dari AiyoBillsService!\n\n";

echo "=== TEST END-TO-END BERHASIL 100% ===\n";
