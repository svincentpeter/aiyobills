<?php

include "token.php";

// Pastikan billMasterId sudah tersedia
// $billMasterId = $_POST['billMasterId'];

// =========================
// CREATE INVOICE
// =========================

$bodyCreateInvoice = array(
    "invoiceName"   => $_POST['invoiceName'] ?? '',
    "referenceId"   => $_POST['referenceId'] ?? '',
    "userName"      => $_POST['userName'] ?? '',
    "userEmail"     => $_POST['userEmail'] ?? '',
    "userPhone"     => $_POST['userPhone'] ?? '',
    "remarks"       => $_POST['remarks'] ?? '',
    "payAmount"     => $_POST['payAmount'] ?? 0,
    "expireTime"    => date('Y-m-d\TH:i', strtotime('+3 hours')),
    "billMasterId"  => $billMasterId,

    "paymentMethod" => array(
        "type"     => "VA_CLOSED",
        "bankCode" => "022"
    ),

    "items" => array()
);


// =========================
// ADD ITEMS
// =========================

if (isset($_POST['itemName']) && is_array($_POST['itemName'])) {

    for ($i = 0; $i < count($_POST['itemName']); $i++) {

        $item = array(
            "itemName"       => $_POST['itemName'][$i] ?? '',
            "itemType"       => "ITEM",
            "itemCount"      => $_POST['itemCount'][$i] ?? 1,
            "itemTotalPrice" => $_POST['itemTotalPrice'][$i] ?? 0
        );

        $bodyCreateInvoice['items'][] = $item;
    }
}


// =========================
// URL & SIGNATURE
// =========================

$pathInvoice = '/api/v1/invoice';

$urlCreateInvoice = $host . $pathInvoice;

$signRelativeURLCreateInvoice = parse_url(
    $urlCreateInvoice,
    PHP_URL_PATH
);

$rawBodyCreateInvoice = json_encode(
    $bodyCreateInvoice,
    JSON_UNESCAPED_SLASHES
);

if ($rawBodyCreateInvoice === false) {
    die("Gagal membuat JSON request: " . json_last_error_msg());
}


// Data yang digunakan untuk signature
$dataToSignCreateInvoice =
    $api_key .
    $signRelativeURLCreateInvoice .
    $rawBodyCreateInvoice;


// Generate HMAC SHA256 signature
$signatureCreateInvoice = hash_hmac(
    'sha256',
    $dataToSignCreateInvoice,
    $api_secret
);


// =========================
// CURL REQUEST
// =========================

$chCreateInvoice = curl_init($urlCreateInvoice);

$headersCreateInvoice = array(
    "Content-Type: application/json",
    "Authorization: Bearer " . $accessToken,
    "x-aiyo-key: " . $api_key,
    "x-aiyo-signature: " . $signatureCreateInvoice
);

curl_setopt_array($chCreateInvoice, array(
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => $headersCreateInvoice,
    CURLOPT_POSTFIELDS     => $rawBodyCreateInvoice
));


// =========================
// SEND REQUEST
// =========================

$responseCreateInvoice = curl_exec($chCreateInvoice);


// =========================
// ERROR HANDLING
// =========================

if ($responseCreateInvoice === false) {

    $curlError = curl_error($chCreateInvoice);

    curl_close($chCreateInvoice);

    die("cURL Error: " . $curlError);
}


// Ambil HTTP status code
$httpCode = curl_getinfo(
    $chCreateInvoice,
    CURLINFO_HTTP_CODE
);

curl_close($chCreateInvoice);


// =========================
// RESPONSE
// =========================

$responseData = json_decode(
    $responseCreateInvoice,
    true
);

if ($responseData === null && json_last_error() !== JSON_ERROR_NONE) {

    die(
        "Response bukan JSON valid.<br>" .
        "HTTP Code: " . $httpCode . "<br>" .
        "Response: " . htmlspecialchars($responseCreateInvoice)
    );
}


// =========================
// DEBUG / OUTPUT
// =========================

echo "<pre>";
echo "HTTP Code: " . $httpCode . "\n\n";
echo "Request:\n";
print_r($bodyCreateInvoice);

echo "\n\nResponse:\n";
print_r($responseData);
echo "</pre>";