<?php

require_once __DIR__ . '/../../../email/index.php';
require_once __DIR__ . '/../../../whatsapp/index.php';

class NotificationService
{
    /**
     * Dispatcher utama saat user melakukan pemesanan (Hold Slot):
     * Otomatis mengirimkan Email & WhatsApp notifikasi pending pembayaran.
     *
     * @param array $data Rincian booking
     * @return array ['email' => bool, 'whatsapp' => bool]
     */
    public static function sendPendingPaymentNotification(array $data): array
    {
        $emailResult = false;
        $waResult = false;

        // 1. Kirim Email Notifikasi
        try {
            $emailResult = self::sendPendingPaymentEmail($data);
        } catch (Throwable $e) {
            error_log("NotificationService Email Pending Error: " . $e->getMessage());
        }

        // 2. Kirim WhatsApp Notifikasi
        try {
            $waRes = self::sendPendingPaymentWhatsApp($data);
            $waResult = !empty($waRes['success']);
        } catch (Throwable $e) {
            error_log("NotificationService WhatsApp Pending Error: " . $e->getMessage());
        }

        return [
            'email' => $emailResult,
            'whatsapp' => $waResult
        ];
    }

    /**
     * Dispatcher utama saat pembayaran berhasil terverifikasi (Lunas):
     * Otomatis mengirimkan Email & WhatsApp notifikasi konfirmasi lunas + pass token pintu.
     *
     * @param array $data Rincian booking + access_pass_token
     * @return array ['email' => bool, 'whatsapp' => bool]
     */
    public static function sendPaidNotification(array $data): array
    {
        $emailResult = false;
        $waResult = false;

        // 1. Kirim Email Notifikasi
        try {
            $emailResult = self::sendPaidEmail($data);
        } catch (Throwable $e) {
            error_log("NotificationService Email Paid Error: " . $e->getMessage());
        }

        // 2. Kirim WhatsApp Notifikasi
        try {
            $waRes = self::sendPaidWhatsApp($data);
            $waResult = !empty($waRes['success']);
        } catch (Throwable $e) {
            error_log("NotificationService WhatsApp Paid Error: " . $e->getMessage());
        }

        return [
            'email' => $emailResult,
            'whatsapp' => $waResult
        ];
    }

    /**
     * Kirim email notifikasi ketika user melakukan pemesanan dan belum bayar.
     */
    public static function sendPendingPaymentEmail(array $data): bool
    {
        $to = trim($data['customer_email'] ?? '');
        if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $customerName = htmlspecialchars($data['customer_name'] ?? 'Pelanggan MSRA', ENT_QUOTES, 'UTF-8');
        $bookingId    = htmlspecialchars($data['booking_id'] ?? ($data['id'] ?? 'SPK-' . date('Ymd')), ENT_QUOTES, 'UTF-8');
        $roomName     = htmlspecialchars($data['room_name'] ?? 'Ruangan MSRA', ENT_QUOTES, 'UTF-8');
        $doorNumber   = htmlspecialchars($data['door_number'] ?? 'Pintu Utama', ENT_QUOTES, 'UTF-8');
        $floor        = htmlspecialchars($data['floor'] ?? 'Lantai 1', ENT_QUOTES, 'UTF-8');
        $date         = htmlspecialchars($data['date'] ?? ($data['booking_date'] ?? date('Y-m-d')), ENT_QUOTES, 'UTF-8');
        $startTime    = htmlspecialchars($data['start_time'] ?? '10:00', ENT_QUOTES, 'UTF-8');
        $endTime      = htmlspecialchars($data['end_time'] ?? '12:00', ENT_QUOTES, 'UTF-8');
        $duration     = (int) ($data['duration_hours'] ?? 2);
        $totalAmount  = number_format((int) ($data['grand_total'] ?? 0), 0, ',', '.');

        $subject = "Order Pemesanan Ruangan MSRA [{$bookingId}] - Menunggu Pembayaran";

        $body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>{$subject}</title>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f1f5f9; margin: 0; padding: 24px; color: #1e293b; }
                .email-container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); border: 1px solid #e2e8f0; }
                .email-header { background: #234c6a; padding: 24px 32px; color: #ffffff; text-align: left; }
                .email-header h1 { margin: 0; font-size: 22px; font-weight: 700; letter-spacing: -0.5px; }
                .email-header p { margin: 4px 0 0 0; font-size: 13px; color: #cbd5e1; }
                .email-body { padding: 32px; }
                .status-badge { display: inline-block; background-color: #fef3c7; color: #92400e; font-size: 12px; font-weight: 700; padding: 6px 14px; border-radius: 9999px; text-transform: uppercase; margin-bottom: 20px; }
                .alert-box { background: #eff6ff; border-left: 4px solid #2563eb; padding: 16px 20px; border-radius: 6px; margin: 20px 0; }
                .alert-box p { margin: 0; font-size: 15px; font-weight: 600; color: #1d4ed8; }
                .detail-table { width: 100%; border-collapse: collapse; margin-top: 16px; margin-bottom: 24px; }
                .detail-table td { padding: 12px 16px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
                .detail-table td.label { color: #64748b; width: 40%; }
                .detail-table td.value { color: #0f172a; font-weight: 600; text-align: right; }
                .total-row td { background-color: #f8fafc; font-size: 16px; font-weight: 700; color: #234c6a; border-top: 2px solid #e2e8f0; }
                .instruction-box { background: #fffbeb; border: 1px solid #fef3c7; border-radius: 8px; padding: 16px 20px; margin-top: 24px; }
                .instruction-box h4 { margin: 0 0 8px 0; color: #92400e; font-size: 14px; }
                .instruction-box ul { margin: 0; padding-left: 20px; font-size: 13px; color: #78350f; }
                .instruction-box li { margin-bottom: 4px; }
                .email-footer { background-color: #f8fafc; padding: 20px 32px; text-align: center; border-top: 1px solid #e2e8f0; font-size: 12px; color: #94a3b8; }
            </style>
        </head>
        <body>
            <div class='email-container'>
                <div class='email-header'>
                    <h1>MSRA</h1>
                    <p>Money Smart Room Access</p>
                </div>
                <div class='email-body'>
                    <div class='status-badge'>Menunggu Pembayaran</div>
                    <h2 style='margin: 0 0 8px 0; color: #0f172a; font-size: 20px;'>Halo, {$customerName}</h2>
                    <p style='margin: 0; color: #475569; font-size: 14px; line-height: 1.5;'>Terima kasih telah melakukan pemesanan ruangan di sistem MSRA.</p>
                    
                    <div class='alert-box'>
                        <p>Data sudah lengkap, silahkan lakukan pembayaran.</p>
                    </div>

                    <table class='detail-table'>
                        <tr>
                            <td class='label'>Nomor Pesanan (Booking ID)</td>
                            <td class='value'>{$bookingId}</td>
                        </tr>
                        <tr>
                            <td class='label'>Ruangan yang Dipesan</td>
                            <td class='value'>{$roomName}</td>
                        </tr>
                        <tr>
                            <td class='label'>Lokasi & Pintu</td>
                            <td class='value'>{$floor} &bull; {$doorNumber}</td>
                        </tr>
                        <tr>
                            <td class='label'>Tanggal Pemakaian</td>
                            <td class='value'>{$date}</td>
                        </tr>
                        <tr>
                            <td class='label'>Jam Akses</td>
                            <td class='value'>{$startTime} - {$endTime} WIB ({$duration} Jam)</td>
                        </tr>
                        <tr class='total-row'>
                            <td class='label' style='color: #234c6a;'>Total Pembayaran</td>
                            <td class='value' style='color: #234c6a;'>Rp {$totalAmount}</td>
                        </tr>
                    </table>

                    <div class='instruction-box'>
                        <h4>Petunjuk Pembayaran:</h4>
                        <ul>
                            <li>Buka halaman checkout pembayaran di aplikasi MSRA.</li>
                            <li>Pilih metode pembayaran melalui <strong>QRIS</strong> atau <strong>Virtual Account Bank</strong>.</li>
                            <li>Batas waktu pembayaran adalah <strong>10 menit</strong> sejak pesanan dibuat.</li>
                            <li>Setelah pembayaran terverifikasi, token akses digital akan dikirimkan otomatis ke WhatsApp & Email Anda.</li>
                        </ul>
                    </div>
                </div>
                <div class='email-footer'>
                    &copy; " . date('Y') . " MSRA — Money Smart Room Access. Seluruh hak cipta dilindungi.<br>
                    Email ini dibuat otomatis oleh sistem, mohon untuk tidak membalas email ini.
                </div>
            </div>
        </body>
        </html>
        ";

        return sendEmail($to, $subject, $body);
    }

    /**
     * Kirim pesan WhatsApp notifikasi ketika user melakukan pemesanan (menunggu pembayaran).
     */
    public static function sendPendingPaymentWhatsApp(array $data): array
    {
        $phone = trim($data['customer_phone'] ?? '');
        if (empty($phone)) {
            return ['success' => false, 'error' => 'Nomor HP/WhatsApp pelanggan kosong'];
        }

        $customerName = trim($data['customer_name'] ?? 'Pelanggan MSRA');
        $bookingId    = trim($data['booking_id'] ?? ($data['id'] ?? 'SPK-' . date('Ymd')));
        $roomName     = trim($data['room_name'] ?? 'Ruangan MSRA');
        $doorNumber   = trim($data['door_number'] ?? 'Pintu Utama');
        $floor        = trim($data['floor'] ?? 'Lantai 1');
        $date         = trim($data['date'] ?? ($data['booking_date'] ?? date('Y-m-d')));
        $startTime    = trim($data['start_time'] ?? '10:00');
        $endTime      = trim($data['end_time'] ?? '12:00');
        $duration     = (int) ($data['duration_hours'] ?? 2);
        $totalAmount  = number_format((int) ($data['grand_total'] ?? 0), 0, ',', '.');

        $message = "🏢 *MSRA — MONEY SMART ROOM ACCESS*\n";
        $message .= "--------------------------------------\n";
        $message .= "Halo, *{$customerName}*! 👋\n\n";
        $message .= "*Data sudah lengkap, silahkan lakukan pembayaran.*\n\n";
        $message .= "📋 *Rincian Pesanan:*\n";
        $message .= "• No. Booking: `{$bookingId}`\n";
        $message .= "• Ruangan: *{$roomName}*\n";
        $message .= "• Lokasi: {$floor} • {$doorNumber}\n";
        $message .= "• Tanggal: {$date}\n";
        $message .= "• Jam Sewa: {$startTime} - {$endTime} WIB ({$duration} Jam)\n";
        $message .= "• Total Tagihan: *Rp {$totalAmount}*\n\n";
        $message .= "⏳ *Batas Waktu Pembayaran:* 10 Menit\n";
        $message .= "💳 *Cara Pembayaran:*\n";
        $message .= "Silakan selesaikan pembayaran di aplikasi Web/PWA MSRA melalui *QRIS* atau *Virtual Account Bank* (Aiyo Bills).\n\n";
        $message .= "Setelah pembayaran berhasil dan lunas, token akses digital pintu akan dikirimkan otomatis ke WhatsApp ini. Terima kasih! 🙏\n";
        $message .= "--------------------------------------\n";
        $message .= "_Pesan otomatis sistem MSRA_";

        return sendWhatsApp($phone, $message);
    }

    /**
     * Kirim email notifikasi ketika pembayaran sudah dilakukan dan lunas.
     */
    public static function sendPaidEmail(array $data): bool
    {
        $to = trim($data['customer_email'] ?? '');
        if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $customerName = htmlspecialchars($data['customer_name'] ?? 'Pelanggan MSRA', ENT_QUOTES, 'UTF-8');
        $bookingId    = htmlspecialchars($data['booking_id'] ?? ($data['id'] ?? 'SPK-' . date('Ymd')), ENT_QUOTES, 'UTF-8');
        $roomName     = htmlspecialchars($data['room_name'] ?? 'Ruangan MSRA', ENT_QUOTES, 'UTF-8');
        $doorNumber   = htmlspecialchars($data['door_number'] ?? 'Pintu Utama', ENT_QUOTES, 'UTF-8');
        $floor        = htmlspecialchars($data['floor'] ?? 'Lantai 1', ENT_QUOTES, 'UTF-8');
        $date         = htmlspecialchars($data['booking_date'] ?? ($data['date'] ?? date('Y-m-d')), ENT_QUOTES, 'UTF-8');
        $startTime    = htmlspecialchars($data['start_time'] ?? '10:00', ENT_QUOTES, 'UTF-8');
        $endTime      = htmlspecialchars($data['end_time'] ?? '12:00', ENT_QUOTES, 'UTF-8');
        $duration     = (int) ($data['duration_hours'] ?? 2);
        $totalAmount  = number_format((int) ($data['grand_total'] ?? 0), 0, ',', '.');
        $passToken    = htmlspecialchars($data['access_pass_token'] ?? 'SPK-PASS-ACTIVE', ENT_QUOTES, 'UTF-8');

        $subject = "Pembayaran LUNAS - Tiket Akses Ruangan MSRA [{$bookingId}]";

        $body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>{$subject}</title>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f1f5f9; margin: 0; padding: 24px; color: #1e293b; }
                .email-container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); border: 1px solid #e2e8f0; }
                .email-header { background: #15803d; padding: 24px 32px; color: #ffffff; text-align: left; }
                .email-header h1 { margin: 0; font-size: 22px; font-weight: 700; letter-spacing: -0.5px; }
                .email-header p { margin: 4px 0 0 0; font-size: 13px; color: #dcfce7; }
                .email-body { padding: 32px; }
                .status-badge { display: inline-block; background-color: #dcfce7; color: #166534; font-size: 12px; font-weight: 700; padding: 6px 14px; border-radius: 9999px; text-transform: uppercase; margin-bottom: 20px; }
                .success-card { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; padding: 20px; text-align: center; margin: 20px 0; }
                .success-card h3 { margin: 0 0 6px 0; font-size: 18px; color: #166534; }
                .success-card p { margin: 0; font-size: 14px; color: #15803d; }
                .token-box { background: #ffffff; border: 2px dashed #16a34a; border-radius: 8px; padding: 14px; margin-top: 14px; display: inline-block; min-width: 240px; }
                .token-label { font-size: 11px; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; margin-bottom: 4px; font-weight: 600; }
                .token-code { font-family: 'Courier New', Courier, monospace; font-size: 18px; font-weight: 800; color: #15803d; letter-spacing: 1px; word-break: break-all; }
                .detail-table { width: 100%; border-collapse: collapse; margin-top: 20px; margin-bottom: 24px; }
                .detail-table td { padding: 12px 16px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
                .detail-table td.label { color: #64748b; width: 40%; }
                .detail-table td.value { color: #0f172a; font-weight: 600; text-align: right; }
                .total-row td { background-color: #f8fafc; font-size: 15px; font-weight: 700; color: #166534; border-top: 2px solid #e2e8f0; }
                .guide-box { background: #f8fafc; border-radius: 8px; padding: 16px 20px; border: 1px solid #e2e8f0; }
                .guide-box h4 { margin: 0 0 8px 0; font-size: 14px; color: #1e293b; }
                .guide-box ol { margin: 0; padding-left: 20px; font-size: 13px; color: #475569; }
                .guide-box li { margin-bottom: 6px; }
                .email-footer { background-color: #f8fafc; padding: 20px 32px; text-align: center; border-top: 1px solid #e2e8f0; font-size: 12px; color: #94a3b8; }
            </style>
        </head>
        <body>
            <div class='email-container'>
                <div class='email-header'>
                    <h1>MSRA</h1>
                    <p>Money Smart Room Access</p>
                </div>
                <div class='email-body'>
                    <div class='status-badge'>Pembayaran Lunas & Terverifikasi</div>
                    <h2 style='margin: 0 0 8px 0; color: #0f172a; font-size: 20px;'>Halo, {$customerName}</h2>
                    <p style='margin: 0; color: #475569; font-size: 14px; line-height: 1.5;'>Pembayaran untuk pemesanan ruangan Anda telah <strong>berhasil dan lunas</strong>.</p>
                    
                    <div class='success-card'>
                        <h3>Akses Ruangan Anda Telah Aktif</h3>
                        <p>Gunakan pass token di bawah ini atau buka menu Tiket Saya di aplikasi MSRA untuk scan QR di pintu.</p>
                        <div class='token-box'>
                            <div class='token-label'>Token Akses Pintu</div>
                            <div class='token-code'>{$passToken}</div>
                        </div>
                    </div>

                    <table class='detail-table'>
                        <tr>
                            <td class='label'>Nomor Pesanan (Booking ID)</td>
                            <td class='value'>{$bookingId}</td>
                        </tr>
                        <tr>
                            <td class='label'>Ruangan</td>
                            <td class='value'>{$roomName}</td>
                        </tr>
                        <tr>
                            <td class='label'>Lokasi & Pintu</td>
                            <td class='value'>{$floor} &bull; {$doorNumber}</td>
                        </tr>
                        <tr>
                            <td class='label'>Tanggal Akses</td>
                            <td class='value'>{$date}</td>
                        </tr>
                        <tr>
                            <td class='label'>Jadwal Akses</td>
                            <td class='value'>{$startTime} - {$endTime} WIB ({$duration} Jam)</td>
                        </tr>
                        <tr class='total-row'>
                            <td class='label'>Status Pembayaran</td>
                            <td class='value'>LUNAS (Rp {$totalAmount})</td>
                        </tr>
                    </table>

                    <div class='guide-box'>
                        <h4>Cara Mengakses Ruangan:</h4>
                        <ol>
                            <li>Buka aplikasi MSRA di smartphone Anda dan masuk ke menu <strong>Tiket Saya</strong>.</li>
                            <li>Saat jadwal pemakaian tiba, arahkan kamera ke stiker QR Code yang terpasang di depan pintu ruangan.</li>
                            <li>Kunci solenoid pintu akan terbuka otomatis secara instan.</li>
                        </ol>
                    </div>
                </div>
                <div class='email-footer'>
                    &copy; " . date('Y') . " MSRA — Money Smart Room Access. Seluruh hak cipta dilindungi.<br>
                    Email ini dibuat otomatis oleh sistem, mohon untuk tidak membalas email ini.
                </div>
            </div>
        </body>
        </html>
        ";

        return sendEmail($to, $subject, $body);
    }

    /**
     * Kirim pesan WhatsApp notifikasi ketika pembayaran lunas (disertai Pass Token).
     */
    public static function sendPaidWhatsApp(array $data): array
    {
        $phone = trim($data['customer_phone'] ?? '');
        if (empty($phone)) {
            return ['success' => false, 'error' => 'Nomor HP/WhatsApp pelanggan kosong'];
        }

        $customerName = trim($data['customer_name'] ?? 'Pelanggan MSRA');
        $bookingId    = trim($data['booking_id'] ?? ($data['id'] ?? 'SPK-' . date('Ymd')));
        $roomName     = trim($data['room_name'] ?? 'Ruangan MSRA');
        $doorNumber   = trim($data['door_number'] ?? 'Pintu Utama');
        $floor        = trim($data['floor'] ?? 'Lantai 1');
        $date         = trim($data['booking_date'] ?? ($data['date'] ?? date('Y-m-d')));
        $startTime    = trim($data['start_time'] ?? '10:00');
        $endTime      = trim($data['end_time'] ?? '12:00');
        $duration     = (int) ($data['duration_hours'] ?? 2);
        $totalAmount  = number_format((int) ($data['grand_total'] ?? 0), 0, ',', '.');
        $passToken    = trim($data['access_pass_token'] ?? 'SPK-PASS-ACTIVE');

        $message = "🏢 *MSRA — MONEY SMART ROOM ACCESS*\n";
        $message .= "--------------------------------------\n";
        $message .= "Halo, *{$customerName}*! 👋\n\n";
        $message .= "Pembayaran untuk pemesanan ruangan Anda telah *berhasil dan lunas!* ✅\n\n";
        $message .= "🔑 *AKSES RUANGAN TELAH AKTIF!*\n";
        $message .= "• *Token Akses Pintu:* `{$passToken}`\n";
        $message .= "• No. Booking: `{$bookingId}`\n";
        $message .= "• Ruangan: *{$roomName}*\n";
        $message .= "• Lokasi: {$floor} • {$doorNumber}\n";
        $message .= "• Jadwal: {$date} ({$startTime} - {$endTime} WIB)\n";
        $message .= "• Status: *LUNAS (Rp {$totalAmount})*\n\n";
        $message .= "🚪 *Cara Mengakses Pintu Ruangan:*\n";
        $message .= "1. Buka aplikasi PWA MSRA di smartphone Anda.\n";
        $message .= "2. Masuk ke tab *Tiket Akses Saya* atau *Pindai QR Pintu*.\n";
        $message .= "3. Arahkan scanner ke kode QR di pintu atau gunakan token akses di atas untuk membuka kunci solenoid pintu.\n\n";
        $message .= "Selamat beraktivitas di MSRA! 🌟\n";
        $message .= "--------------------------------------\n";
        $message .= "_Pesan otomatis sistem MSRA_";

        return sendWhatsApp($phone, $message);
    }
}
