<?php
header('Content-Type: application/json');
require_once 'db_config.php';

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    if (class_exists('Dotenv\\Dotenv')) {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
        $dotenv->safeLoad();
    }
}

use Twilio\Rest\Client;

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$loginId = $conn->real_escape_string($data['loginId']); // Can be Email or Patient ID
$password = $data['password']; // Plain text password from input
$phone = isset($data['phone']) ? $conn->real_escape_string($data['phone']) : '';
$otp = isset($data['otp']) ? trim($data['otp']) : '';

if (empty($loginId) || empty($password) || empty($phone) || empty($otp)) {
    echo json_encode(['success' => false, 'message' => 'Email/ID, Password, Phone, and OTP are required']);
    exit;
}

// Authenticate user
$sql = "SELECT * FROM patients WHERE email = '$loginId' OR patient_id = '$loginId'";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
    
    // Verify password check
    if (password_verify($password, $user['password'])) {
        if ($user['phone_number'] !== $phone) {
             echo json_encode(['success' => false, 'message' => 'Phone number mismatch.']);
             exit;
        }

        // Verify OTP via Twilio
        $sid = $_ENV['TWILIO_SID'] ?? '';
        $token = $_ENV['TWILIO_TOKEN'] ?? ''; 
        $verifySid = $_ENV['TWILIO_VERIFY_SID'] ?? '';
        
        // Twilio requires E.164 format (e.g. +1234567890). Assuming +91 as default country code.
        $twilioPhone = $phone;
        if (substr($twilioPhone, 0, 1) !== '+') {
            $twilioPhone = '+91' . ltrim($twilioPhone, '0');
        }
        
        try {
            if (!$sid || !$token) {
                throw new Exception("Twilio SID or Token is missing in .env file.");
            }
            $twilio = new Client($sid, $token);
            $verification_check = $twilio->verify->v2->services($verifySid)
                                                   ->verificationChecks
                                                   ->create(["to" => $twilioPhone, "code" => $otp]);

            if ($verification_check->status === 'approved') {
                session_start();
                $_SESSION['user_type'] = 'patient';
                $_SESSION['patient_id'] = $user['patient_id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['full_name'] = $user['full_name'];

                echo json_encode(['success' => true, 'message' => 'Login successful', 'redirect' => 'patients.php']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid or expired OTP']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Twilio Error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid password']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'User not found']);
}

$conn->close();

