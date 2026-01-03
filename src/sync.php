<?php

// Adjusted path to reach vendor folder from src/
require __DIR__ . '/../vendor/autoload.php';

use TusPhp\Tus\Server as TusServer;

// Configuration
$valid_username = 'username-here';
$valid_password = 'password-here';
$valid_key      = 'your-secret-key-here';   
$valid_token    = 'your-secret-token-here'; 

// Paths adjusted to live outside of src/ for security
$upload_base_dir     = __DIR__ . "/../uploads/";
$upload_dir     = __DIR__ . "/../uploads/$valid_username";
$tus_cache_dir  = __DIR__ . '/../tus_cache/';

$dirs = [
    'Base Uploads' => $upload_base_dir,
    'Uploads' => $upload_dir,
    'Tus Cache' => $tus_cache_dir
];

foreach ($dirs as $name => $path) {
    if (!is_dir($path)) {
        if (!mkdir($path, 0755, true)) {
            header('HTTP/1.1 500 Internal Server Error');
            echo json_encode(['error' => "Failed to create $name directory at $path. Check server permissions."]);
            exit;
        }
    }
}

echo $upload_base_dir;
exit;
foreach ([$upload_dir, $tus_cache_dir] as $dir) {
    if (!is_dir($dir)) mkdir($dir, 0755, true);
}


exit;
$method = $_SERVER['REQUEST_METHOD'];

// Routing Logic
if ($method === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    handleLogin($valid_username, $valid_password, $valid_key, $valid_token);
    exit;
}

// Auth Check
$headers = getallheaders();
$provided_key   = $headers['X-API-KEY'] ?? '';
$provided_token = $headers['X-AUTH-TOKEN'] ?? '';

if ($provided_key !== $valid_key || $provided_token !== $valid_token) {
    header('Content-Type: application/json');
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'Authentication failed.']);
    exit;
}

// Handle TUS Uploads
$tus_methods = ['POST', 'PATCH', 'HEAD', 'OPTIONS', 'DELETE'];
if (in_array($method, $tus_methods) && !isset($_GET['download'])) {
    
    $server = new TusServer();
    $server->setApiPath('/src/sync.php') 
           ->setUploadDir($upload_dir);

    $server->event()->addListener('tus-php.server.upload.complete', function ($event) use ($upload_dir) {
        $file = $event->getFile();
        $metadata = $file->details()['metadata'];
        
        if (isset($metadata['relativePath'])) {
            $finalPath = $upload_dir . $metadata['relativePath'];
            if (!is_dir(dirname($finalPath))) {
                mkdir(dirname($finalPath), 0755, true);
            }
            rename($file->getFilePath(), $finalPath);
        }
    });

    $response = $server->serve();
    $response->send();
    exit;
}

// GET Actions
if ($method === 'GET') {
    if (isset($_GET['download'])) {
        handleFileDownload($upload_dir, $_GET['download']);
    } else {
        handleListFiles($upload_dir);
    }
}

/**
 * 
 */
function handleLogin($user, $pass, $key, $token) {
    header('Content-Type: application/json');
    if (($_POST['username'] ?? '') === $user && ($_POST['password'] ?? '') === $pass) {
        echo json_encode(['status' => 'success', 'api_key' => $key, 'auth_token' => $token]);
    } else {
        header('HTTP/1.1 401 Unauthorized');
        echo json_encode(['error' => 'Invalid credentials.']);
    }
}

/**
 * 
 */
function handleFileDownload($dir, $filename) {
    $path = realpath($dir . basename($filename));
    if ($path && strpos($path, realpath($dir)) === 0 && file_exists($path)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($path) . '"');
        readfile($path);
    } else {
        header('HTTP/1.1 404 Not Found');
        echo json_encode(['error' => 'File not found.']);
    }
}

/**
 * 
 */
function handleListFiles($dir) {
    header('Content-Type: application/json');
    $files = array_values(array_diff(scandir($dir), ['..', '.']));
    echo json_encode(['status' => 'success', 'files' => $files]);
}