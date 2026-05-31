<?php
/**
 * upload.php — Aromea Admin Image Upload Handler
 * Accepts a multipart POST with file field "image".
 * Returns JSON: { "success": true, "url": "images/uploads/xxx.jpg" }
 *              or { "success": false, "error": "..." }
 */
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['image'])) {
    echo json_encode(['success' => false, 'error' => 'No file received']);
    exit;
}

$file  = $_FILES['image'];
$error = $file['error'];

if ($error !== UPLOAD_ERR_OK) {
    $msgs = [
        UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload limit.',
        UPLOAD_ERR_FORM_SIZE  => 'File exceeds form upload limit.',
        UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temp folder.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        UPLOAD_ERR_EXTENSION  => 'Upload blocked by server extension.',
    ];
    echo json_encode(['success' => false, 'error' => $msgs[$error] ?? 'Unknown upload error.']);
    exit;
}

// Validate MIME type via finfo (not trusting $_FILES['type'])
$finfo    = new finfo(FILEINFO_MIME_TYPE);
$mime     = $finfo->file($file['tmp_name']);
$allowed  = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif', 'image/avif' => 'avif'];

if (!array_key_exists($mime, $allowed)) {
    echo json_encode(['success' => false, 'error' => 'Only JPEG, PNG, WebP, GIF and AVIF images are allowed.']);
    exit;
}

// 10 MB max
if ($file['size'] > 10 * 1024 * 1024) {
    echo json_encode(['success' => false, 'error' => 'File is too large (max 10 MB).']);
    exit;
}

// Ensure upload directory exists
$upload_dir = __DIR__ . '/../images/uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Build a unique, sanitized filename
$ext      = $allowed[$mime];
$basename = pathinfo($file['name'], PATHINFO_FILENAME);
$basename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $basename);
$basename = substr($basename, 0, 60);
$filename = $basename . '_' . uniqid() . '.' . $ext;
$dest     = $upload_dir . $filename;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    echo json_encode(['success' => false, 'error' => 'Could not save the file. Check folder permissions.']);
    exit;
}

// Return a path relative to the site root (BASE_URL is something like /aromea)
$relative = 'images/uploads/' . $filename;
echo json_encode(['success' => true, 'url' => $relative]);
exit;
