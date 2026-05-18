# EHI File Uploader & Storage System

A secure PHP-based file upload, storage, and management web application.

## Features

- **Drag & drop upload** with real-time progress bar
- **AJAX upload** — no page reload needed
- **File management** — browse, search, download, delete
- **Pagination & search** for large file collections
- **Security-first** — MIME validation, extension whitelist, secure filenames, prepared statements
- **Modern UI** — dark theme, responsive design

## Quick Start

### Requirements
- PHP 7.4+
- MySQL / MariaDB
- Apache with mod_rewrite or Nginx

### Setup

```bash
# 1. Clone the repo
git clone https://github.com/YOUR_USERNAME/ehi-uploader.git
cd ehi-uploader

# 2. Create the database
mysql -u root -p < db.sql

# 3. Edit config.php with your database credentials
#    - DB_HOST, DB_USER, DB_PASS, DB_NAME

# 4. Ensure uploads directory is writable
chmod 755 uploads

# 5. Run development server
php -S localhost:8080

# 6. Open http://localhost:8080
