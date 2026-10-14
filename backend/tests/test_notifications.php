<?php

require_once __DIR__ . '/../src/Services/NotificationService.php';

echo "=== TEST NOTIFICATION SERVICE (EMAIL & WHATSAPP) ===\n\n";

$testData = [
    'booking_id'        => 'SPK-TEST-' . date('His'),
    'customer_name'     => 'Vincent Peter',
    'customer_phone'    => '085329000345',
    'customer_email'    => 'msra.contact@gmail.com',
    'room_name'         => 'Podcast Studio Pro',
    'door_number'       => 'Pintu Studio A',
    'floor'             => 'Lantai 2',
    'date'              => date('Y-m-d'),
    'start_time'        => '14:00',
    'end_time'          => '16:00',
    'duration_hours'    => 2,
    'grand_total'       => 150000,
    'access_pass_token' => 'SPK-PASS-998877'
];

echo "1. Menguji Pending Payment Notification (Email + WhatsApp)...\n";
$resPending = NotificationService::sendPendingPaymentNotification($testData);
echo "Hasil Pending:\n";
echo " - Email: " . ($resPending['email'] ? "BERHASIL (Terkirim via SMTP)" : "GAGAL / Format tidak sesuai") . "\n";
echo " - WhatsApp: " . ($resPending['whatsapp'] ? "BERHASIL (Diterima server Fonnte)" : "GAGAL / Terkendala") . "\n\n";

echo "2. Menguji Paid Notification (Email + WhatsApp)...\n";
$resPaid = NotificationService::sendPaidNotification($testData);
echo "Hasil Paid:\n";
echo " - Email: " . ($resPaid['email'] ? "BERHASIL (Terkirim via SMTP)" : "GAGAL / Format tidak sesuai") . "\n";
echo " - WhatsApp: " . ($resPaid['whatsapp'] ? "BERHASIL (Diterima server Fonnte)" : "GAGAL / Terkendala") . "\n\n";

echo "=== SELESAI ===\n";
