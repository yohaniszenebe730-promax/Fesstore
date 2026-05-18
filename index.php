<?php
require_once 'config.php';

$pdo = getDB();
$stmt = $pdo->query("SELECT id, original_name, file_size, file_ext, downloads, created_at 
                      FROM files ORDER BY created_at DESC LIMIT " . MAX_FILES_PER_PAGE);
$recent_files = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EHI File Uploader</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>&#x1F4C1; EHI File Uploader</h1>
            <p>Upload, store, and manage your configuration files securely</p>
        </header>

        <div class="upload-section">
            <form action="upload.php" method="POST" enctype="multipart/form-data" class="upload-form">
                <input type="hidden" name="MAX_FILE_SIZE" value="<?= MAX_FILE_SIZE ?>">
                
                <div class="file-drop-zone" id="dropZone">
                    <div class="drop-zone-content">
                        <span class="upload-icon">&uarr;</span>
                        <p>Drag & drop files here or <span class="browse-link">browse</span></p>
                        <p class="small">Supported: <?= implode(', ', ALLOWED_EXTENSIONS) ?> | Max: <?= formatSize(MAX_FILE_SIZE) ?></p>
                    </div>
                    <input type="file" name="file" id="fileInput" class="file-input" required>
                </div>

                <div id="fileInfo" class="file-info hidden">
                    <span id="fileName"></span>
                    <span id="fileSize"></span>
                </div>

                <button type="submit" name="submit" class="btn btn-upload" id="submitBtn">
                    Upload File
                </button>
            </form>
            <div id="progressContainer" class="progress-container hidden">
                <div class="progress-bar">
                    <div class="progress-fill" id="progressFill"></div>
                </div>
                <span id="progressText">0%</span>
            </div>
            <div id="message" class="message hidden"></div>
        </div>

        <div class="files-section">
            <h2>Stored Files</h2>
            <?php if (count($recent_files) > 0): ?>
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
                        <?php foreach ($recent_files as $file): ?>
                        <tr>
                            <td class="filename" title="<?= sanitizeFilename($file['original_name']) ?>">
                                <?= sanitizeFilename(mb_substr($file['original_name'], 0, 40)) ?>
                            </td>
                            <td><span class="badge badge-<?= sanitizeFilename($file['file_ext']) ?>">.<?= sanitizeFilename($file['file_ext']) ?></span></td>
                            <td><?= formatSize($file['file_size']) ?></td>
                            <td><?= (int)$file['downloads'] ?></td>
                            <td><?= date('Y-m-d H:i', strtotime($file['created_at'])) ?></td>
                            <td class="actions">
                                <a href="download.php?id=<?= $file['id'] ?>" class="btn btn-sm btn-download">Download</a>
                                <a href="delete.php?id=<?= $file['id'] ?>" class="btn btn-sm btn-delete" onclick="return confirm('Delete this file?')">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <p class="no-files">No files uploaded yet.</p>
            <?php endif; ?>
            <div style="text-align:right; margin-top:16px;">
                <a href="files.php" class="btn btn-back">View All Files &rarr;</a>
            </div>
        </div>

        <footer>
            <p>EHI File Uploader &copy; <?= date('Y') ?></p>
        </footer>
    </div>

    <script>
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const fileInfo = document.getElementById('fileInfo');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        const submitBtn = document.getElementById('submitBtn');
        const progressContainer = document.getElementById('progressContainer');
        const progressFill = document.getElementById('progressFill');
        const progressText = document.getElementById('progressText');
        const message = document.getElementById('message');

        dropZone.addEventListener('click', () => fileInput.click());

        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) showFileInfo(e.target.files[0]);
        });

        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        });

        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('dragover');
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
            if (e.dataTransfer.files.length > 0) {
                fileInput.files = e.dataTransfer.files;
                showFileInfo(e.dataTransfer.files[0]);
            }
        });

        function showFileInfo(file) {
            fileInfo.classList.remove('hidden');
            fileName.textContent = file.name;
            fileSize.textContent = formatBytes(file.size);
            submitBtn.disabled = false;
        }

        function formatBytes(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        document.querySelector('.upload-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const xhr = new XMLHttpRequest();

            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    const percent = Math.round((e.loaded / e.total) * 100);
                    progressContainer.classList.remove('hidden');
                    progressFill.style.width = percent + '%';
                    progressText.textContent = percent + '%';
                }
            });

            xhr.addEventListener('load', () => {
                progressFill.style.width = '100%';
                progressText.textContent = '100%';
                
                message.classList.remove('hidden', 'error', 'success');
                message.textContent = xhr.responseText;
                
                if (xhr.status === 200) {
                    message.classList.add('success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    message.classList.add('error');
                    progressContainer.classList.add('hidden');
                }
            });

            xhr.addEventListener('error', () => {
                message.classList.remove('hidden');
                message.classList.add('error');
                message.textContent = 'Upload failed. Please try again.';
                progressContainer.classList.add('hidden');
            });

            xhr.open('POST', 'upload.php', true);
            xhr.send(formData);
        });
    </script>
</body>
</html>
