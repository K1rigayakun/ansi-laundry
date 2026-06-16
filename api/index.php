<?php
// Router untuk Vercel Serverless Function

$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Menangani request ke root ("/")
if ($request_uri === '/' || $request_uri === '') {
    require __DIR__ . '/../index.php';
    exit;
}

$file = __DIR__ . '/..' . $request_uri;

// Jika file ditemukan
if (file_exists($file) && is_file($file)) {
    serve_file($file);
    exit;
}

// Jika request tidak mencantumkan .php (misal: /login)
if (file_exists($file . '.php') && is_file($file . '.php')) {
    serve_file($file . '.php');
    exit;
}

// Jika tidak ditemukan
http_response_code(404);
echo "404 Not Found";
exit;

function serve_file($path) {
    $ext = pathinfo($path, PATHINFO_EXTENSION);
    
    // Jika file PHP, eksekusi file tersebut
    if ($ext === 'php') {
        require $path;
    } else {
        // Jika file statis (CSS, JS, Image, dll), serve sebagai file biasa
        $mime_types = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject',
            'otf' => 'font/otf',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'txt' => 'text/plain',
            'html' => 'text/html',
        ];
        
        if (isset($mime_types[$ext])) {
            header('Content-Type: ' . $mime_types[$ext]);
            // Berikan cache agar file statis dimuat lebih cepat
            header('Cache-Control: public, max-age=31536000');
        }
        
        readfile($path);
    }
}
