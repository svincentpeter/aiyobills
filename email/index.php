<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/vendor/autoload.php';

function sendEmail($to, $subject = 'Judul Email', $body = 'Isi Notifikasi') {
    $mail = new PHPMailer(true);

    try {
        // Konfigurasi Server SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'msra.contact@gmail.com'; // Email pengirim
        $mail->Password   = 'tbou mdlr epic wodi';  // App Password Gmail
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        // Pengaturan Pengirim & Penerima
        $mail->setFrom('msra.contact@gmail.com', 'MSRA System');
        $mail->addAddress($to);

        // Konten Email
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        // error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}

// Jalankan pengiriman uji coba hanya saat index.php dibuka langsung (bukan saat di-include)
$isDirect = (php_sapi_name() === 'cli' && isset($argv[0]) && realpath($argv[0]) === realpath(__FILE__))
    || (isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__));

if ($isDirect) {
    $tujuan = 'rmikael345@gmail.com';
    $subjek = 'Tes Pengiriman Email Fintech';
    $pesan  = '<h3>Halo!</h3><p>Ini adalah email uji coba langsung dari <b>index.php</b> menggunakan Gmail SMTP.</p>';

    echo "Sedang mengirim email ke {$tujuan}...<br>";

    if (sendEmail($tujuan, $subjek, $pesan)) {
        echo "<b>Hasil:</b> Email berhasil dikirim! Silakan periksa inbox / spam.";
    } else {
        echo "<b>Hasil:</b> Gagal mengirim email.";
    }
}