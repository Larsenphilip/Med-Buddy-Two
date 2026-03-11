<?php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['patient_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$patient_id = $_SESSION['patient_id'];

// Check if POST data
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Extract fields
$full_name = trim($_POST['full_name'] ?? '');
$date_of_birth = $_POST['date_of_birth'] ?? null;
$gender = $_POST['gender'] ?? '';
$address = trim($_POST['address'] ?? '');
$city = trim($_POST['city'] ?? '');
$state = trim($_POST['state'] ?? '');
$postal_code = trim($_POST['postal_code'] ?? '');
$blood_group = $_POST['blood_group'] ?? '';
$height = $_POST['height'] ?? null;
$weight = $_POST['weight'] ?? null;
$allergies = trim($_POST['allergies'] ?? '');
$chronic_conditions = trim($_POST['chronic_conditions'] ?? '');
$emergency_contact_name = trim($_POST['emergency_contact_name'] ?? '');
$emergency_contact_phone = trim($_POST['emergency_contact_phone'] ?? '');

// Basic validation (optional, can be expanded)
if (empty($full_name)) {
    echo json_encode(['success' => false, 'message' => 'Full Name is required']);
    exit();
}

if (empty($date_of_birth)) $date_of_birth = null;
if ($height === '') $height = null;
if ($weight === '') $weight = null;

// Update Query
$sql = "UPDATE patients SET 
        full_name = ?,
        date_of_birth = ?,
        gender = ?,
        address = ?,
        city = ?,
        state = ?,
        postal_code = ?,
        blood_group = ?,
        height = ?,
        weight = ?,
        allergies = ?,
        chronic_conditions = ?,
        emergency_contact_name = ?,
        emergency_contact_phone = ?
        WHERE patient_id = ?";

$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("ssssssssddsssss", 
        $full_name, 
        $date_of_birth, 
        $gender, 
        $address, 
        $city, 
        $state, 
        $postal_code, 
        $blood_group, 
        $height, 
        $weight,
        $allergies,
        $chronic_conditions,
        $emergency_contact_name,
        $emergency_contact_phone,
        $patient_id
    );

    if ($stmt->execute()) {
        $_SESSION['full_name'] = $full_name;
    } else {
        echo json_encode(['success' => false, 'message' => 'Database update failed: ' . $stmt->error]);
        $stmt->close();
        $conn->close();
        exit();
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Query preparation failed: ' . $conn->error]);
    $conn->close();
    exit();
}

// ------ Handle File Uploads ------
$allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png'];
$max_size = 10 * 1024 * 1024; // 10 MB
$base_dir = __DIR__ . '/uploads/patients/';

if (!is_dir($base_dir)) {
    mkdir($base_dir, 0755, true);
}

$upload_errors = [];
$files_uploaded = 0;

// Support dynamic categories based on input field name
$categories = array_keys($_FILES);

foreach ($categories as $category) {
    if (!is_array($_FILES[$category]['name'])) {
        continue;
    }

    $patient_folder = $base_dir . $patient_id . '/' . $category . '/';
    if (!is_dir($patient_folder)) {
        mkdir($patient_folder, 0755, true);
    }

    $files = $_FILES[$category];
    $count = count($files['name']);
    $titles = $_POST[$category . '_title'] ?? [];
    $dates = $_POST[$category . '_date'] ?? [];

    for ($i = 0; $i < $count; $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        $name = $files['name'][$i];
        $tmp_name = $files['tmp_name'][$i];
        $error = $files['error'][$i];
        $size = $files['size'][$i];

        if ($error !== UPLOAD_ERR_OK) {
            $upload_errors[] = "Error uploading $name";
            continue;
        }

        if ($size > $max_size) {
            $upload_errors[] = "$name exceeds 10MB limit";
            continue;
        }

        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed_extensions)) {
            $upload_errors[] = "$name: Invalid file type";
            continue;
        }

        $safe_name = uniqid($patient_id . '_', true) . '.' . $ext;
        $dest_path = $patient_folder . $safe_name;
        $public_url = 'uploads/patients/' . $patient_id . '/' . $category . '/' . $safe_name;

        if (move_uploaded_file($tmp_name, $dest_path)) {
            $desc = !empty($titles[$i]) ? $titles[$i] : '';
            $taken_at = date('Y-m-d H:i:s');
            
            $upload_stmt = $conn->prepare("INSERT INTO patient_images (patient_id, image_type, file_path, original_file_name, modality, description, taken_at) VALUES (?, ?, ?, ?, 'document', ?, ?)");
            $upload_stmt->bind_param("ssssss", $patient_id, $category, $public_url, $name, $desc, $taken_at);
            $upload_stmt->execute();
            $upload_stmt->close();
            
            $files_uploaded++;
        } else {
            $upload_errors[] = "Failed to save $name";
        }
    }
}

// Prepare final message
$msg = 'Profile updated successfully.';
if ($files_uploaded > 0) {
    $msg .= " $files_uploaded document(s) uploaded.";
}
if (!empty($upload_errors)) {
    $msg .= " Some errors occurred: " . implode(', ', $upload_errors);
}

echo json_encode(['success' => true, 'message' => $msg]);
$conn->close();
?>
