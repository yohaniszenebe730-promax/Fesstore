<?php
require_once 'config.php';

$pdo = getDB();
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * MAX_FILES_PER_PAGE;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_cond = '';
$params = [];

if ($search !== '') {
    $search_cond = 'WHERE original_name LIKE :search';
    $params[':search'] = '%' . $search . '%';
}

$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM files $search_cond");
$count_stmt->execute($params);
$total_files = $count_stmt->fetchColumn();
$total_pages = ceil($total_files / MAX_FILES_PER_PAGE);

$sql = "SELECT id, original_name, file_size, file_ext, downloads, created_at 
        FROM files $search_cond ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':limit', MAX_FILES_PER_PAGE, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->execute();
$files = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>All Files - EHI Uploader</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>&#x1F4C1; All Stored Files</h1>
            <a href="index.php" class="btn btn-back">&larr; Back to Upload</a>
        </header>

        <div class="search-section">
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Search files..." value="<?= sanitizeFilename($search) ?>">
                <button type="submit" class="btn btn-search">Search</button>
                <?php if ($search !== ''): ?>
                    <a href="files.php" class="btn btn-clear">Clear</a>
                <?php endif; ?>
            </form>
            <p class="total-count">Total: <?= $total_files ?> file(s)</p>
        </div>

        <?php if (count($files) > 0): ?>
        <div class="table-wrapper">
            <table class="files-table">
                <thead>
                    <tr>
                        <th>Filename</th>
                        <th>Type</th>
                        <th>Size</th>
                        <th>Downloads</th>
                        <th>Uploaded</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($files as $file): ?>
                    <tr>
                        <td class="filename" title="<?= sanitizeFilename($file['original_name']) ?>">
                            <?= sanitizeFilename(mb_substr($file['original_name'], 0, 50)) ?>
                        </td>
                        <td><span class="badge badge-<?= sanitizeFilename($file['file_ext']) ?>">.<?= sanitizeFilename($file['file_ext']) ?></span></td>
                        <td><?= formatSize($file['file_size']) ?></td>
                        <td><?= (int)$file['downloads'] ?></td>
                        <td><?= date('Y-m-d H:i', strtotime($file['created_at'])) ?></td>
                        <td class="actions">
                            <a href="download.php?id=<?= $file['id'] ?>" class="btn btn-sm btn-download">Download</a>
                            <a href="delete.php?id=<?= $file['id'] ?>" class="btn btn-sm btn-delete" onclick="return confirm('Delete this file permanently?')">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>" class="btn btn-page">&larr; Prev</a>
            <?php endif; ?>
            <span>Page <?= $page ?> of <?= $total_pages ?></span>
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>" class="btn btn-page">Next &rarr;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <p class="no-files">No files found.</p>
        <?php endif; ?>
    </div>
</body>
</html>
