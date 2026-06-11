<?php
// ================================================================
// download.php — Secure File Download Handler
// KDLMS - Khmer Digital Library Management System
// Author: អេង ចាន់ធឿន (Eng Chanthoeun - Vthe)
// ================================================================

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Must be logged in to download
requireLogin();

$bookId = (int)($_GET['id'] ?? 0);

if ($bookId <= 0) {
    http_response_code(400);
    die('Invalid request.');
}

// Fetch active book
$book = getBookById($bookId);

if (!$book) {
    http_response_code(404);
    die('File not found or unavailable.');
}

// Build physical file path
$filePath = UPLOAD_PATH . ltrim($book['file_path'], '/\\');

if (!file_exists($filePath) || !is_readable($filePath)) {
    http_response_code(404);
    error_log("KDLMS Download: File not found at $filePath");
    die('File not found on server. Please contact administrator.');
}

// Log the download
logDownload($bookId, (int)$_SESSION['user_id'], getClientIp());

// Increment download counter
incrementDownloadCount($bookId);

// Serve file securely
$mimeType  = $book['file_type'] ?: 'application/octet-stream';
$fileName  = $book['file_name'] ?: basename($filePath);
$fileSize  = filesize($filePath);

// Clear any output buffers
while (ob_get_level()) ob_end_clean();

// Set headers
header('Content-Type: '        . $mimeType);
header('Content-Disposition: attachment; filename="' . rawurlencode($fileName) . '"');
header('Content-Length: '      . $fileSize);
header('Cache-Control: private, no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('X-Content-Type-Options: nosniff');

// Stream file in chunks (efficient for large files)
$handle = fopen($filePath, 'rb');
if ($handle) {
    while (!feof($handle) && !connection_aborted()) {
        echo fread($handle, 8192);
        flush();
    }
    fclose($handle);
} else {
    http_response_code(500);
    die('Could not read file.');
}
exit;
