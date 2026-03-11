<?php
header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
require_once 'db_config.php';

$doctor_id = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 0;
$date = isset($_GET['date']) ? $_GET['date'] : '';

if (!$doctor_id || !$date) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

// Get day of week from date
$dayOfWeek = date('l', strtotime($date));

// Check if doctor is available on this day
$availabilityQuery = "SELECT start_time, end_time, availability 
                      FROM doctor_availability 
                      WHERE doctor_id = ? AND day_of_week = ? AND availability = 1";
$stmt = $conn->prepare($availabilityQuery);
$stmt->bind_param("is", $doctor_id, $dayOfWeek);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Doctor not available on this day', 'slots' => []]);
    exit;
}

$availability = $result->fetch_assoc();
$startTime = $availability['start_time'];
$endTime = $availability['end_time'];

// Get already booked appointments for this doctor on this date
$bookedQuery = "SELECT appointment_time 
                FROM appointments 
                WHERE doctor_id = ? AND appointment_date = ? AND status != 'Cancelled'";
$stmt = $conn->prepare($bookedQuery);
$stmt->bind_param("is", $doctor_id, $date);
$stmt->execute();
$bookedResult = $stmt->get_result();

$bookedSlots = [];
while ($row = $bookedResult->fetch_assoc()) {
    $bookedSlots[] = substr($row['appointment_time'], 0, 5); // Format HH:MM
}

// Generate available time slots (30-minute intervals)
$slots = [];
$currentTime = strtotime($startTime);
$endTimeStamp = strtotime($endTime);

while ($currentTime < $endTimeStamp) {
    $timeSlot = date('H:i', $currentTime);
    
    // Check if slot is not already booked
    if (!in_array($timeSlot, $bookedSlots)) {
        // If the date is today, only show future time slots
        if ($date === date('Y-m-d')) {
            $now = time();
            if ($currentTime > $now) {
                $slots[] = $timeSlot;
            }
        } else {
            $slots[] = $timeSlot;
        }
    }
    
    $currentTime = strtotime('+30 minutes', $currentTime);
}

echo json_encode([
    'success' => true,
    'slots' => $slots,
    'day' => $dayOfWeek
]);

$conn->close();
?>
