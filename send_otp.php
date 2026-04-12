<?php
header('Content-Type: application/json');
require_once 'db_config.php';

// Try to load composer dependencies for Twilio
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

$loginId = $conn->real_escape_string($data['loginId']);
$password = $data['password'];
$phone = $conn->real_escape_string($data['phone']);

if (empty($loginId) || empty($password) || empty($phone)) {
    echo json_encode(['success' => false, 'message' => 'Email/ID, Password and Phone are required']);
    exit;
}

// Authenticate user
$sql = "SELECT * FROM patients WHERE email = '$loginId' OR patient_id = '$loginId'";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
    
    // Verify password
    if (password_verify($password, $user['password'])) {
        // Logically verify the phone matches what's in DB (or if using phone login just proceed)
        if ($user['phone_number'] !== $phone) {
            echo json_encode(['success' => false, 'message' => 'Phone number does not match registered phone number.']);
            exit;
        }

        // Twilio Credentials
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
            $verification = $twilio->verify->v2->services($verifySid)
                                             ->verifications
                                             ->create($twilioPhone, "sms");
            echo json_encode(['success' => true, 'message' => 'OTP sent successfully']);
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
