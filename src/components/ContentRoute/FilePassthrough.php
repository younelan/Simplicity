<?php
namespace Opensitez\Simplicity\Components;

class FilePassthrough
{
    /**
     * Render and serve a file using fpassthrough, with security checks and options
     * @param array $options Must include 'filename', 'allowedExtensions', 'extension'
     */
    public function render($options)
    {
        $filename = $options['filename'] ?? '';
        $allowedExtensions = $options['allowedExtensions'] ?? ['html', 'htm', 'css', 'jpg', 'jpeg', 'gif', 'png', 'js', 'txt', 'pdf', "svg", 'ico', 'webp', 'avif', 'woff', 'woff2', 'ttf', 'otf'];
        $ext = $options['extension'] ?? strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        // Security: Only allow files within allowed extensions
        if (!in_array($ext, $allowedExtensions)) {
            header('HTTP/1.1 403 Forbidden');
            echo "Forbidden file type.";
            return;
        }
        // Security: Prevent directory traversal
        if (strpos($filename, '..') !== false) {
            header('HTTP/1.1 403 Forbidden');
            echo "Invalid file path.";
            return;
        }
        if (!is_file($filename)) {
            header('HTTP/1.1 404 Not Found');
            echo "File not found.";
            return;
        }
        // Set appropriate content-type
        $mimeTypes = [
            'html' => 'text/html',
            'htm' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'png' => 'image/png',
            'pdf' => 'application/pdf',
            'svg' => 'image/svg+xml',
            'txt' => 'text/plain',
            'ico' => 'image/x-icon',
            'webp' => 'image/webp',
            'avif' => 'image/avif',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'otf' => 'font/otf',
        ];
        header('Content-Type: ' . ($mimeTypes[$ext] ?? 'application/octet-stream'));
        // Serve the file
        $fp = fopen($filename, 'rb');
        if ($fp) {
            fpassthru($fp);
            fclose($fp);
            exit;
        } else {
            header('HTTP/1.1 500 Internal Server Error');
            echo "Unable to open file.";
        }
    }
}
