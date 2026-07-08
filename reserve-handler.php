<?php
// Handles the Reserve page form. Expects a multipart/form-data POST with
// all reservation fields plus file uploads (ownership, tip, insurance, id).
//
// Routing: Marina Slip / Dry Storage go to the reservations CRM inbox.
// Hydraulic Trailer / Tractor go to the yard scheduling inbox, since a
// different team handles haul-outs and launches.

header('Content-Type: application/json');

const TO_SLIP_DRY = 'servicios@marina-san-carlos-llc.odoo.com';
const TO_HAUL_TRACTOR = 'serviciosmarinaseca@marina-san-carlos-llc.odoo.com';
const SITE_DOMAIN = 'marinasancarlos.com';
const MAX_ATTACHMENT_BYTES = 8 * 1024 * 1024; // 8MB per file, generous for scans/photos

function respond($ok, $error = null) {
    http_response_code($ok ? 200 : 400);
    echo json_encode($ok ? ['success' => true] : ['success' => false, 'error' => $error]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Method not allowed');
}

function field($key, $default = '') {
    return isset($_POST[$key]) ? trim((string) $_POST[$key]) : $default;
}
function clean_header_value($v) {
    return trim(str_replace(["\r", "\n"], '', (string) $v));
}

$resType = field('resType');
$validTypes = ['slip', 'dry', 'haul', 'tractor'];
if (!in_array($resType, $validTypes, true)) {
    respond(false, 'Invalid reservation type');
}

$fullName = clean_header_value(field('fullName'));
$nationality = field('nationality');
$email = clean_header_value(field('email'));
$phone = field('phone');
$address = field('address');
$vName = field('vName');
$flag = field('flag');
$make = field('make');
$model = field('model');
$length = field('length');
$beam = field('beam');
$draft = field('draft');
$weight = field('weight');
$hullNumber = field('reg');

if ($fullName === '' || $nationality === '' || $email === '' || $phone === '' || $vName === '' || $flag === '' || $length === '') {
    respond(false, 'Missing required fields');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(false, 'Invalid email address');
}

// Required documents for every reservation type.
foreach (['ownership', 'insurance', 'id'] as $reqFile) {
    if (!isset($_FILES[$reqFile]) || $_FILES[$reqFile]['error'] !== UPLOAD_ERR_OK) {
        respond(false, 'Missing required document: ' . $reqFile);
    }
}

$lines = [];
$lines[] = 'New reservation request from the Marina San Carlos website.';
$lines[] = '';
$lines[] = 'Reservation Type: ' . $resType;
$lines[] = '';
$lines[] = '--- Owner Information ---';
$lines[] = 'Full Name: ' . $fullName;
$lines[] = 'Nationality: ' . $nationality;
$lines[] = 'Email: ' . $email;
$lines[] = 'Phone: ' . $phone;
$lines[] = 'Mailing Address: ' . ($address !== '' ? $address : '(not provided)');
$lines[] = '';
$lines[] = '--- Vessel Information ---';
$lines[] = 'Vessel Name: ' . $vName;
$lines[] = 'Flag State / Country: ' . $flag;
$lines[] = 'Make: ' . ($make !== '' ? $make : '(not provided)');
$lines[] = 'Model: ' . ($model !== '' ? $model : '(not provided)');
$lines[] = 'Length (LOA): ' . $length;
$lines[] = 'Beam: ' . ($beam !== '' ? $beam : '(not provided)');
$lines[] = 'Draft: ' . ($draft !== '' ? $draft : '(not provided)');
$lines[] = 'Weight (tons): ' . ($weight !== '' ? $weight : '(not provided)');
$lines[] = 'Hull Number: ' . ($hullNumber !== '' ? $hullNumber : '(not provided)');
$lines[] = '';
$lines[] = '--- Dates ---';

if ($resType === 'slip' || $resType === 'dry') {
    $lines[] = 'Start Date: ' . field('resStartDate', '(not provided)');
    $lines[] = 'End Date: ' . field('resEndDate', '(not provided)');
} elseif ($resType === 'haul') {
    $lines[] = 'Hydraulic Trailer Date: ' . field('resHaulDate', '(not provided)');
} elseif ($resType === 'tractor') {
    $action = field('tractorAction', 'haul');
    $lines[] = 'Service: ' . ($action === 'launch' ? 'Launch' : 'Haul Out');
    $lines[] = 'Date: ' . field('resLaunchDate', '(not provided)');
}

$body = implode("\n", $lines);

$toAddress = ($resType === 'haul' || $resType === 'tractor') ? TO_HAUL_TRACTOR : TO_SLIP_DRY;
$subject = 'New reservation request (' . $resType . ') from ' . str_replace(["\r", "\n"], '', $fullName);
$fromAddress = 'no-reply@' . SITE_DOMAIN;

$boundary = 'msc-' . bin2hex(random_bytes(16));

$message = "--$boundary\r\n";
$message .= "Content-Type: text/plain; charset=UTF-8\r\n";
$message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
$message .= $body . "\r\n";

foreach (['ownership' => 'Proof of Ownership', 'tip' => 'TIP', 'insurance' => 'Insurance', 'id' => 'Owner ID'] as $key => $label) {
    if (!isset($_FILES[$key]) || $_FILES[$key]['error'] !== UPLOAD_ERR_OK) {
        continue;
    }
    $file = $_FILES[$key];
    if ($file['size'] > MAX_ATTACHMENT_BYTES) {
        respond(false, 'File too large: ' . $label);
    }
    $content = file_get_contents($file['tmp_name']);
    if ($content === false) {
        continue;
    }
    $encoded = chunk_split(base64_encode($content));
    $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $file['name']);
    $mimeType = $file['type'] !== '' ? $file['type'] : 'application/octet-stream';

    $message .= "--$boundary\r\n";
    $message .= "Content-Type: $mimeType; name=\"$safeName\"\r\n";
    $message .= "Content-Transfer-Encoding: base64\r\n";
    $message .= "Content-Disposition: attachment; filename=\"$safeName\"\r\n\r\n";
    $message .= $encoded . "\r\n";
}
$message .= "--$boundary--";

$headers = [
    'From: Marina San Carlos Website <' . $fromAddress . '>',
    'Reply-To: ' . $fullName . ' <' . $email . '>',
    'MIME-Version: 1.0',
    "Content-Type: multipart/mixed; boundary=\"$boundary\"",
];

$sent = mail($toAddress, $subject, $message, implode("\r\n", $headers));

respond($sent, $sent ? null : 'Failed to send reservation request');
