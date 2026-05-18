<?php
require_once 'config.php';
header('Content-Type: text/plain; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['submit'])) {
    http_response_code(405);
    exit('Invalid request method.');
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] === UPLOAD_ERR_NO_FILE) {
    http_response_code(400);
    exit('No file selected.');
}

$file = $_FILES['file'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    $errors = [
        UPLOAD_ERR_INI_SIZE   => 'File exceeds server size limit.',
        UPLOAD_ERR_FORM_SIZE  => 'File exceeds form size limit.',
        UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Server missing temporary directory.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        UPLOAD_ERR_EXTENSION  => 'Upload blocked by extension.',
    ];
    http_response_code(400);
    exit($errors[$file['error']] ?? 'Unknown upload error.');
}

if ($file['size'] > MAX_FILE_SIZE) {
    http_response_code(413);
    exit('File exceeds maximum allowed size (' . formatSize(MAX_FILE_SIZE) . ').');
}

if ($file['size'] === 0) {
    http_response_code(400);
    exit('File is empty.');
}

$original_name = basename($file['name']);
$extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

if (!in_array($extension, ALLOWED_EXTENSIONS, true)) {
    http_response_code(415);
    exit('File type "' . sanitizeFilename($extension) . '" is not allowed.');
}

$allowed_mimes = [
    'ehi'  => ['application/octet-stream', 'text/plain'],
    'ovpn' => ['application/octet-stream', 'text/plain'],
    'txt'  => ['text/plain'],
    'pdf'  => ['application/pdf'],
    'jpg'  => ['image/jpeg'],
    'jpeg' => ['image/jpeg'],
    'png'  => ['image/png'],
    'zip'  => ['application/zip'],
    'cfg'  => ['text/plain', 'application/octet-stream'],
    'conf' => ['text/plain', 'application/octet-stream'],
];

$finfo = new finfo(FILEINFO_MIME_TYPE);
$detected_mime = $finfo->file($file['tmp_name']);

if (isset($allowed_mimes[$extension]) && !in_array($detected_mime, $allowed_mimes[$extension], true)) {
    http_response_code(415);
    exit('File content does not match extension.');
}

$stored_name = generateSecureFilename($extension);
$destination = UPLOAD_DIR . $stored_name;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    http_response_code(500);
    exit('Failed to store file.');
}

try {
    $pdo = getDB();
    $stmt = $pdo->prepare(
        "INSERT INTO files (original_name, stored_name, file_size, file_type, file_ext, upload_ip)
         VALUES (:original_name, :stored_name, :file_size, :file_type, :file_ext, :upload_ip)"
    );
    $stmt->execute([
        ':original_name' => $original_name,
        ':stored_name'   => $stored_name,
        ':file_size'     => $file['size'],
        ':file_type'     => $detected_mime,
        ':file_ext'      => $extension,
        ':upload_ip'     => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
    ]);

    echo 'File uploaded successfully: ' . sanitizeFilename($original_name);
} catch (Exception $e) {
    unlink($destination);
    http_response_code(500);
    exit('Database error. File was not stored.');
}
