<?php
// Menerima data JSON yang dikirim dari laptop lokal Anda
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

// Konfigurasi Kredensial AiYO Anda
$host = "https://api-bills-invoice.aiyo.id";
$username = "MONEY_TALKS";
$password = "pass_7dfvDSp4ztpsX3qqaTB02yrnXgccfF";
$billMasterId = "6xvjNydEUkcbclPQf78C";
$api_key = "key_7ccLlvknDtoL5ir3hUrI4SxHdkVTY0";
$api_secret = "secret_WpAp3WZ3a9m0C1BpSur5D1Bxf41udu";

// Menyiapkan cURL untuk menembak API AiYO dari server mesinbayar.com
$ch = curl_init($host . '/v1/invoices'); // Sesuaikan endpoint jika berbeda
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'bill_master_id' => $billMasterId,
    'external_id'    => $input['external_id'] ?? 'TRX-DEFAULT',
    'amount'         => $input['amount'] ?? 0,
    'description'    => $input['description'] ?? '',
    'customer'       => $input['customer'] ?? [],
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'API-Key: ' . $api_key,
    'API-Secret: ' . $api_secret,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Kembalikan respons dari AiYO kembali ke laptop lokal Anda
http_response_code($httpCode);
header('Content-Type: application/json');
echo $response;