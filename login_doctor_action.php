<?php
session_start();
header('Content-Type: application/json');
include 'db_config.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$email = $data['email']; 
$password = $data['password']; 

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Email and Password are required']);
    exit;
}

// Check doctor credentials
$sql = "SELECT id, name, email, password FROM doctors WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 1) {
    $doctor = $result->fetch_assoc();
    if (password_verify($password, $doctor['password']) || $password === $doctor['password']) {
        $_SESSION['doctor_id'] = $doctor['id'];
        $_SESSION['doctor_name'] = $doctor['name'];
        echo json_encode(['success' => true, 'message' => 'Login successful', 'redirect' => 'admin/index.php']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
}

$stmt->close();
$conn->close();
