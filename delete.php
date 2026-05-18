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
if (file_exists($filepath)) {
    unlink($filepath);
}

$pdo->prepare("DELETE FROM files WHERE id = :id")->execute([':id' => $id]);

header('Location: files.php?deleted=1');
exit;
