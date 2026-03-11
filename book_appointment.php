<?php
header('Content-Type: application/json');
require_once 'db_config.php';

// Get POST data
$doctor_id = isset($_POST['doctor_id']) ? intval($_POST['doctor_id']) : 0;
$patient_name = isset($_POST['name']) ? trim($_POST['name']) : '';
$patient_email = isset($_POST['email']) ? trim($_POST['email']) : '';
$patient_phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$appointment_date = isset($_POST['date']) ? $_POST['date'] : '';
$appointment_time = isset($_POST['time']) ? $_POST['time'] : '';

// Validate inputs 


if (!$doctor_id || !$patient_name || !$patient_email || !$patient_phone || !$appointment_date || !$appointment_time) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

// Validate email format
if (!filter_var($patient_email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

// Convert time to proper format (add seconds if not present)
if (strlen($appointment_time) === 5) {
    $appointment_time .= ':00';
}

// Check if the time slot is still available
$dayOfWeek = date('l', strtotime($appointment_date));

// Verify doctor availability
$availabilityQuery = "SELECT start_time, end_time 
                      FROM doctor_availability 
                      WHERE doctor_id = ? AND day_of_week = ? AND availability = 1";
$stmt = $conn->prepare($availabilityQuery);
$stmt->bind_param("is", $doctor_id, $dayOfWeek);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Doctor not available on this day']);
    exit;
}

$availability = $result->fetch_assoc();
$startTime = $availability['start_time'];
$endTime = $availability['end_time'];

// Check if requested time is within doctor's availability
if ($appointment_time < $startTime || $appointment_time >= $endTime) {
    echo json_encode(['success' => false, 'message' => 'Selected time is outside doctor availability hours']);
    exit;
}

// Check if slot is already booked
$checkQuery = "SELECT id FROM appointments 
               WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND status != 'Cancelled'";
$stmt = $conn->prepare($checkQuery);
$stmt->bind_param("iss", $doctor_id, $appointment_date, $appointment_time);
$stmt->execute();
$checkResult = $stmt->get_result();

if ($checkResult->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'This time slot is already booked']);
    exit;
}

// Insert the appointment
$insertQuery = "INSERT INTO appointments (doctor_id, patient_name, patient_email, patient_phone, appointment_date, appointment_time, status) 
                VALUES (?, ?, ?, ?, ?, ?, 'Pending')";
$stmt = $conn->prepare($insertQuery);
$stmt->bind_param("isssss", $doctor_id, $patient_name, $patient_email, $patient_phone, $appointment_date, $appointment_time);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true, 
        'message' => 'Appointment booked successfully',
        'appointment_id' => $stmt->insert_id
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to book appointment: ' . $conn->error]);
}

$conn->close();
?>
