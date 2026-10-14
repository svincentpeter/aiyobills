<?php

/**
 * WhatsApp Gateway Helper via Fonnte API
 * Digunakan oleh MSRA (Money Smart Room Access)
 */

if (!function_exists('sendWhatsApp')) {
    /**
     * Kirim pesan WhatsApp melalui Fonnte API
     *
     * @param string $target Nomor tujuan WhatsApp (misal: '085329000345' atau '6285329000345')
     * @param string $message Isi pesan (mendukung format markdown WhatsApp seperti *tebal*, _miring_)
     * @param string $countryCode Kode negara default ('62' untuk Indonesia)
     * @param string|null $customToken Token khusus jika ingin menimpa token default
     * @return array ['success' => bool, 'response' => array|string, 'error' => ?string]
     */
    function sendWhatsApp(string $target, string $message, string $countryCode = '62', ?string $customToken = null): array
    {
        // 1. Bersihkan & normalisasi nomor telepon
        $cleanPhone = preg_replace('/[^0-9+]/', '', trim($target));
        if (str_starts_with($cleanPhone, '+')) {
            $cleanPhone = substr($cleanPhone, 1);
        }
        if (str_starts_with($cleanPhone, '08')) {
            $cleanPhone = '628' . substr($cleanPhone, 2);
        } elseif (str_starts_with($cleanPhone, '8')) {
            $cleanPhone = '628' . substr($cleanPhone, 1);
        }

        if (empty($cleanPhone) || strlen($cleanPhone) < 8) {
            return [
                'success' => false,
                'response' => null,
                'error' => 'Nomor WhatsApp tidak valid: ' . $target
            ];
        }

        // 2. Ambil token Fonnte dari parameter, environment, atau default token project
        $token = $customToken
            ?: (getenv('FONNTE_TOKEN')
            ?: ($_ENV['FONNTE_TOKEN'] ?? 'ZmDmEvDkfQphdomxS7YU'));

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.fonnte.com/send',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => [
                'target' => $cleanPhone,
                'message' => $message,
                'countryCode' => $countryCode,
            ],
            CURLOPT_HTTPHEADER => [
                'Authorization: ' . $token
            ],
        ]);

        $response = curl_exec($curl);
        $curlError = curl_error($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($curlError) {
            error_log("Fonnte cURL Error: " . $curlError);
            return [
                'success' => false,
                'response' => null,
                'error' => $curlError
            ];
        }

        $decoded = json_decode($response, true);
        // Fonnte mengembalikan status: true / false
        $isSuccess = ($httpCode === 200) && (!empty($decoded['status']) && $decoded['status'] === true);

        return [
            'success' => $isSuccess,
            'response' => $decoded ?: $response,
            'error' => $isSuccess ? null : ($decoded['reason'] ?? 'Gagal mengirim pesan via Fonnte')
        ];
    }
}

// Jalankan pengiriman uji coba hanya saat index.php dibuka langsung (bukan saat di-include)
$isDirect = (php_sapi_name() === 'cli' && isset($argv[0]) && realpath($argv[0]) === realpath(__FILE__))
    || (isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__));

if ($isDirect) {
    header('Content-Type: text/html; charset=utf-8');
    $target  = $_GET['target'] ?? ($_POST['target'] ?? '085329000345');
    $message = $_GET['message'] ?? ($_POST['message'] ?? "🔔 *MSRA System Notification*\n\nIni adalah pesan uji coba WhatsApp Gateway menggunakan Fonnte API untuk project *Money Smart Room Access*.");

    echo "<h3>Uji Coba Pengiriman WhatsApp (Fonnte Gateway)</h3>";
    echo "<p>Mengirim ke: <b>" . htmlspecialchars($target) . "</b></p>";

    $res = sendWhatsApp($target, $message);
    if ($res['success']) {
        echo "<p style='color:green;'><b>Hasil:</b> Pesan berhasil dikirim / diantrikan ke server Fonnte!</p>";
    } else {
        echo "<p style='color:red;'><b>Hasil:</b> Gagal mengirim pesan. Alasan: " . htmlspecialchars($res['error'] ?? 'Unknown error') . "</p>";
    }
    echo "<pre>" . htmlspecialchars(json_encode($res, JSON_PRETTY_PRINT)) . "</pre>";
}