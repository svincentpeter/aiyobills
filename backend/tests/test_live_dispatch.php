<?php

require_once __DIR__ . '/../src/Services/NotificationService.php';

$phone = $argv[1] ?? '082182314967';
$email = $argv[2] ?? 'akunpremium01ytb@gmail.com';

echo "====================================================\n";
echo " UJI COBA LIVE NOTIFIKASI MSRA\n";
echo " Target Phone : {$phone}\n";
echo " Target Email : {$email}\n";
echo "====================================================\n\n";

$dataPending = [
    'booking_id'     => 'SPK-DEMO-' . rand(1000, 9999),
    'customer_name'  => 'Pelanggan MSRA',
    'customer_phone' => $phone,
    'customer_email' => $email,
    'room_name'      => 'Meeting Room Executive A',
    'door_number'    => 'Pintu 201',
    'floor'          => 'Lantai 2',
    'date'           => date('Y-m-d'),
    'start_time'     => date('H:i'),
    'end_time'       => date('H:i', strtotime('+2 hours')),
    'duration_hours' => 2,
    'grand_total'    => 125000,
];

echo ">>> [1/2] MENGIRIM NOTIFIKASI PENDING (MENUNGGU PEMBAYARAN)...\n";
echo "1.1 Mengirim Email...\n";
$emailPending = NotificationService::sendPendingPaymentEmail($dataPending);
echo "    -> Email Status: " . ($emailPending ? "BERHASIL (Terkirim via Gmail SMTP)" : "GAGAL") . "\n";

echo "1.2 Mengirim WhatsApp...\n";
$waPending = NotificationService::sendPendingPaymentWhatsApp($dataPending);
echo "    -> WhatsApp Status: " . (!empty($waPending['success']) ? "BERHASIL" : "GAGAL / TERTUNDA") . "\n";
echo "    -> Fonnte Response: " . json_encode($waPending, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n\n";


$dataPaid = [
    'booking_id'        => $dataPending['booking_id'],
    'customer_name'     => 'Pelanggan MSRA',
    'customer_phone'    => $phone,
    'customer_email'    => $email,
    'room_name'         => 'Meeting Room Executive A',
    'door_number'       => 'Pintu 201',
    'floor'             => 'Lantai 2',
    'booking_date'      => date('Y-m-d'),
    'start_time'        => date('H:i'),
    'end_time'          => date('H:i', strtotime('+2 hours')),
    'duration_hours'    => 2,
    'grand_total'       => 125000,
    'access_pass_token' => 'SPK-PASS-' . strtoupper(substr(md5(uniqid()), 0, 8)),
];

echo ">>> [2/2] MENGIRIM NOTIFIKASI PAID (PEMBAYARAN LUNAS & TOKEN AKSES)...\n";
echo "2.1 Mengirim Email...\n";
$emailPaid = NotificationService::sendPaidEmail($dataPaid);
echo "    -> Email Status: " . ($emailPaid ? "BERHASIL (Terkirim via Gmail SMTP)" : "GAGAL") . "\n";

echo "2.2 Mengirim WhatsApp...\n";
$waPaid = NotificationService::sendPaidWhatsApp($dataPaid);
echo "    -> WhatsApp Status: " . (!empty($waPaid['success']) ? "BERHASIL" : "GAGAL / TERTUNDA") . "\n";
echo "    -> Fonnte Response: " . json_encode($waPaid, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n\n";

echo "====================================================\n";
echo " UJI COBA SELESAI\n";
echo "====================================================\n";
