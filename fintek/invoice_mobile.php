<?php
include "token.php";
// Create Invoice
$bodyCreateInvoice = array(
"invoiceName" => $_POST['invoiceName'],
"referenceId" => $_POST['referenceId'],
"userName" => $_POST['userName'],
"userEmail" => $_POST['userEmail'],
"userPhone" => $_POST['userPhone'],
"remarks" => $_POST['remarks'],
"payAmount" => $_POST['payAmount'],
"expireTime" => date('Y-m-d\TH:i', strtotime('+3 hour')),
"billMasterId" => $billMasterId,
"paymentMethod" => array(
"type" => "VA_CLOSED",
"bankCode" => "022"
), "items" => array()
);
for ($i = 0; $i < count($_POST['itemName']); $i++) {
$item = array(
"itemName" => $_POST['itemName'][$i],
"itemType" => "ITEM",
"itemCount" => $_POST['itemCount'][$i],
"itemTotalPrice" => $_POST['itemTotalPrice'][$i]
);
// Add each item to the $items array
$bodyCreateInvoice['items'][] = $item;
}
//Signature
$pathInvoice = '/api/v1/invoice';
$urlCreateInvoice = $host . $pathInvoice;
$signRelativeURLCreateInvoice = parse_url($urlCreateInvoice, PHP_URL_PATH);
$rawBodyCreateInvoice = json_encode($bodyCreateInvoice);
$dataToSignCreateInvoice = $api_key . $signRelativeURLCreateInvoice . $rawBodyCreateInvoice;
$signatureCreateInvoice = hash_hmac('sha256', $dataToSignCreateInvoice, $api_secret);
$chCreateInvoice = curl_init($urlCreateInvoice);
$headersCreateInvoice = array(
"Content-Type: application/json",
"Authorization: Bearer ". $accessToken,
"x-aiyo-key: " . $api_key,
"x-aiyo-signature: " . $signatureCreateInvoice
);
curl_setopt($chCreateInvoice, CURLOPT_TIMEOUT, 30);
curl_setopt($chCreateInvoice, CURLOPT_POST, 1);
curl_setopt($chCreateInvoice, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($chCreateInvoice, CURLOPT_HTTPHEADER, $headersCreateInvoice);
curl_setopt($chCreateInvoice, CURLOPT_POSTFIELDS, $rawBodyCreateInvoice);
$sentHeaders = curl_getinfo($chCreateInvoice, CURLINFO_HEADER_OUT);
$responseCreateInvoice = curl_exec($chCreateInvoice);