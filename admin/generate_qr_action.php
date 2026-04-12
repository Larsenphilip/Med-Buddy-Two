<?php
session_start();

// 1. Authentication Check (Patient or Doctor)
if (!isset($_SESSION['patient_id']) && !isset($_SESSION['doctor_id'])) {
    http_response_code(403);
    echo json_encode(["success" => false, "error" => "Unauthorized"]);
    exit;
}

// 2. Input Validation
$patient_id = isset($_GET['patient_id']) ? trim($_GET['patient_id']) : null;
if (!$patient_id) {
    echo json_encode(["success" => false, "error" => "Missing patient ID"]);
    exit;
}

// 3. Command Setup
$venv_python = 'C:\\wamp64\\www\\Med-Buddy-Two\\venv\\Scripts\\python.exe';
$script_path = __DIR__ . DIRECTORY_SEPARATOR . "generate_qr.py";
$command = "\"$venv_python\" \"$script_path\" " . escapeshellarg($patient_id) . " 2>&1";

// 4. Execute
$output = shell_exec($command);
$result = json_decode($output, true);

if ($result && isset($result['success']) && $result['success'] === true) {
    // 5. Update Status in Database
    require_once '../db_config.php';
    $update_sql = "UPDATE patients SET qr_status = 1 WHERE patient_id = ?";
    $stmt = $conn->prepare($update_sql);
    if ($stmt) {
        $stmt->bind_param("s", $patient_id);
        $stmt->execute();
        $stmt->close();
    }
    echo json_encode($result);
} else {
    echo json_encode(["success" => false, "error" => "Failed to execute QR generation system", "raw" => $output]);
}
?>
