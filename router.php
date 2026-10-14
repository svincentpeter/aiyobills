<?php
// router.php - Robust Development Server Router for MSRA (aiyobills)
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// 1. Route API requests directly to the backend application router
if (str_starts_with($uri, '/api/')) {
    require __DIR__ . '/backend/public/index.php';
    exit;
}

// 2. Friendly route for Admin Console
if ($uri === '/admin' || $uri === '/admin.html') {
    header('Content-Type: text/html; charset=utf-8');
    require __DIR__ . '/admin.html';
    exit;
}

// 3. Serve static files with proper MIME types
$filePath = __DIR__ . str_replace('/', DIRECTORY_SEPARATOR, $uri);
if ($uri !== '/' && file_exists($filePath) && !is_dir($filePath)) {
    $mimes = [
        'css'   => 'text/css; charset=utf-8',
        'js'    => 'application/javascript; charset=utf-8',
        'json'  => 'application/json; charset=utf-8',
        'png'   => 'image/png',
        'jpg'   => 'image/jpeg',
        'jpeg'  => 'image/jpeg',
        'svg'   => 'image/svg+xml',
        'ico'   => 'image/x-icon',
        'woff'  => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf'   => 'font/ttf',
        'html'  => 'text/html; charset=utf-8',
        'txt'   => 'text/plain; charset=utf-8',
    ];

    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    if (isset($mimes[$ext])) {
        header('Content-Type: ' . $mimes[$ext]);
    }
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
    exit;
}

// 4. Default to index.html for customer booking & room access PWA
header('Content-Type: text/html; charset=utf-8');
require __DIR__ . '/index.html';
exit;
