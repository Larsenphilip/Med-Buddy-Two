<?php
session_start();

// 1. Authentication Check
if (!isset($_SESSION['doctor_id'])) {
    http_response_code(403);
    echo "Unauthorized";
    exit;
}

// 2. Input Validation
if (!isset($_GET['file_path'])) {
    http_response_code(400);
    echo "Missing File Path";
    exit;
}

$file_path = trim($_GET['file_path']);

// 3. Environment Preparation
if (function_exists('apache_setenv')) {
    @apache_setenv('no-gzip', 1);
}
@ini_set('zlib.output_compression', 0);
@ini_set('implicit_flush', 1);
while (ob_get_level()) {
    ob_end_flush();
}
ob_implicit_flush(1);

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');

// 4. Command Setup
$venv_python = 'C:\\Users\\LARSEN\\AppData\\Local\\Programs\\Python\\Python312\\python.exe';
$script_path = __DIR__ . DIRECTORY_SEPARATOR . "report_summarizer.py";
$command = "$venv_python \"$script_path\" " . escapeshellarg($file_path) . " 2>&1";

// 5. Execute and Stream
$handle = popen($command, 'r');

if ($handle) {
    while (!feof($handle)) {
        $chunk = fread($handle, 1024);
        if ($chunk !== false) {
            echo $chunk;
            if (ob_get_length()) ob_flush();
            flush();
        }
    }
    pclose($handle);
} else {
    echo "Error: Failed to execute AI system.";
}
?>
