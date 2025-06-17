<?php

namespace Opensitez\Simplicity;

class Routing
{
    /**
     * Get the current domain
     * @return string The domain (e.g., "localhost", "example.com")
     */
    public function getDomain(): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
        
        return $host;
    }

    /**
     * Get the path after the script name
     * For URL like http://localhost/index.php/path/2/3/4/ returns "path/2/3/4"
     * @return string The path segments after the script
     */
    public function getPath(): string
    {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        
        // Remove query string if present
        $requestUri = strtok($requestUri, '?');
        
        // If script name is in the URI, extract path after it
        if (!empty($scriptName) && strpos($requestUri, $scriptName) === 0) {
            $path = substr($requestUri, strlen($scriptName));
        } else {
            // Fallback: use PATH_INFO if available
            $path = $_SERVER['PATH_INFO'] ?? '';
            
            // If no PATH_INFO, try to extract from REQUEST_URI
            if (empty($path)) {
                $path = $requestUri;
                
                // Remove the script directory from the path
                $scriptDir = dirname($scriptName);
                if ($scriptDir !== '/' && strpos($path, $scriptDir) === 0) {
                    $path = substr($path, strlen($scriptDir));
                }
            }
        }
        
        // Clean up the path: remove leading/trailing slashes
        $path = trim($path, '/');
        
        return $path;
    }
}