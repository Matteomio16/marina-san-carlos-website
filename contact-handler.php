<?php
// Handles the Contact page form. Expects a JSON POST body:
// { "name": "...", "email": "...", "phone": "...", "message": "..." }
// Sends the message to the marina's general contact inbox.

header('Content-Type: application/json');

const TO_ADDRESS = 'services@marina-san-carlos-llc.com';
const SITE_DOMAIN = 'marinasancarlos.com';

function respond($ok, $error = null) {
    http_response_code($ok ? 200 : 400);
    echo json_encode($ok ? ['success' => true] : ['success' => false, 'error' => $error]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Method not allowed');
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    respond(false, 'Invalid request body');
}

function clean_header_value($v) {
    // Strip CR/LF to prevent header injection via any field used in headers.
    return trim(str_replace(["\r", "\n"], '', (string) $v));
}

$name = clean_header_value($data['name'] ?? '');
$email = clean_header_value($data['email'] ?? '');
$phone = trim((string) ($data['phone'] ?? ''));
$message = trim((string) ($data['message'] ?? ''));

if ($name === '' || $email === '' || $message === '') {
    respond(false, 'Missing required fields');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(false, 'Invalid email address');
}

$subject = 'New contact form message from ' . str_replace(["\r", "\n"], '', $name);

$bodyLines = [
    'New message from the Marina San Carlos website contact form.',
    '',
    'Name: ' . $name,
    'Email: ' . $email,
    'Phone: ' . ($phone !== '' ? $phone : '(not provided)'),
    '',
    'Message:',
    $message,
];
$body = implode("\n", $bodyLines);

$fromAddress = 'no-reply@' . SITE_DOMAIN;
$headers = [
    'From: Marina San Carlos Website <' . $fromAddress . '>',
    'Reply-To: ' . $name . ' <' . $email . '>',
    'Content-Type: text/plain; charset=UTF-8',
];

$sent = mail(TO_ADDRESS, $subject, $body, implode("\r\n", $headers));

respond($sent, $sent ? null : 'Failed to send message');
