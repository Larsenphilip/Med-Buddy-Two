<?php
header('Content-Type: application/json');
require_once 'db_config.php';

// Get JSON input
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$email = $conn->real_escape_string($data['email']);
$phone = $conn->real_escape_string($data['phone']);
$password = $data['password'];
$patientID = $conn->real_escape_string($data['patientID']);

// Basic Validation
if (empty($email) || empty($phone) || empty($password) || empty($patientID)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

// Check if email already exists
$checkEmail = $conn->query("SELECT user_id FROM patients WHERE email = '$email'");
if ($checkEmail->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Email already registered']);
    exit;
}

// Securely hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert into patients table
$sql = "INSERT INTO patients (patient_id, email, phone_number, password) VALUES ('$patientID', '$email', '$phone', '$hashed_password')";

if ($conn->query($sql)) {
    echo json_encode(['success' => true, 'message' => 'Registration successful', 'patientID' => $patientID]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]);
}

$conn->close();

