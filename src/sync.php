<?php

require __DIR__ . '/../vendor/autoload.php';

use TusPhp\Tus\Server as TusServer;

// Your specific user array structure
$users = [
    '1' => [
        'username' => 'user-1',
        'password' => 'pass-1',
        'token'    => 'token-1',
        'key'      => 'key-1'
    ],
    '2' => [
        'username' => 'user-2',
        'password' => 'pass-2',
        'token'    => 'token-2',
        'key'      => 'key-2' 
    ],
];

$method = $_SERVER['REQUEST_METHOD'];

//  Handle Login Action
if ($method === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $provided_user = $_POST['username'] ?? '';
    $provided_pass = $_POST['password'] ?? '';

    foreach ($users as $id => $data) {
        if ($data['username'] === $provided_user && $data['password'] === $provided_pass) {
            header('Content-Type: application/json');
            echo json_encode([
                'status'     => 'success',
                'api_key'    => $data['key'],
                'auth_token' => $data['token']
            ]);
            exit;
        }
    }
    
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'Invalid credentials.']);
    exit;
}

// Auth Check & User Identification
$headers        = getallheaders();
$provided_key   = $headers['X-API-KEY'] ?? '';
$provided_token = $headers['X-AUTH-TOKEN'] ?? '';

$currentUser = null;

// Loop through users to find who matches the provided Key and Token
foreach ($users as $id => $data) {
    if ($data['key'] === $provided_key && $data['token'] === $provided_token) {
        $currentUser = $data;
        break;
    }
}

if (!$currentUser) {
    header('Content-Type: application/json');
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'Authentication failed. Check keys/tokens.']). '\n';
    exit;
}

// Set up User-Specific Directories based on the identified user
$username_folder = $currentUser['username'];
$upload_base_dir = __DIR__ . "/../data_tests/uploads/";
$upload_dir      = $upload_base_dir . $username_folder . "/"; 
$tus_cache_dir   = __DIR__ . '/../data_tests/tus_cache/';

foreach ([$upload_base_dir, $upload_dir, $tus_cache_dir] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
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
            $finalPath = $upload_dir . ltrim($metadata['relativePath'], '/');
            if (!is_dir(dirname($finalPath))) {
                mkdir(dirname($finalPath), 0755, true);
            }
            rename($file->getFilePath(), $finalPath);
        }
    });

    $server->serve()->send();
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

// Helper Functions
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

function handleListFiles($dir) {
    header('Content-Type: application/json');
    $files = is_dir($dir) ? array_values(array_diff(scandir($dir), ['..', '.'])) : [];
    echo json_encode(['status' => 'success', 'files' => $files]);
}