<?php
require_once 'config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    exit('Invalid file ID.');
}

$id = (int)$_GET['id'];

$pdo = getDB();
$stmt = $pdo->prepare("SELECT * FROM files WHERE id = :id");
$stmt->execute([':id' => $id]);
$file = $stmt->fetch();

if (!$file) {
    http_response_code(404);
    exit('File not found.');
}

$filepath = UPLOAD_DIR . $file['stored_name'];

if (!file_exists($filepath)) {
    http_response_code(404);
    exit('File not found on disk.');
}

$pdo->prepare("UPDATE files SET downloads = downloads + 1 WHERE id = :id")
    ->execute([':id' => $id]);

header('Content-Description: File Transfer');
header('Content-Type: ' . $file['file_type']);
header('Content-Disposition: attachment; filename="' . $file['original_name'] . '"');
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

readfile($filepath);
exit;
