<?php
session_start();

// Check if doctor is logged in
if (!isset($_SESSION['doctor_id'])) {
    header("Location: ../doctor_login.php");
    exit;
}

include '../db_config.php';
$doctor_id = $_SESSION['doctor_id'];
$doctor_name = $_SESSION['doctor_name'];

// Fetch Doctor Details
$sql_doc = "SELECT * FROM doctors WHERE id = ?";
$stmt_doc = $conn->prepare($sql_doc);
$stmt_doc->bind_param("i", $doctor_id);
$stmt_doc->execute();
$doctor_data = $stmt_doc->get_result()->fetch_assoc();

// Handle Bulk Availability Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_all_availability'])) {
    $days_data = $_POST['days']; // Array of day settings
    
    foreach ($days_data as $day => $data) {
        $is_available = isset($data['is_available']) ? 1 : 0;
        $start = $data['start_time'];
        $end = $data['end_time'];
        
        // Check if entry exists
        $check = $conn->prepare("SELECT id FROM doctor_availability WHERE doctor_id = ? AND day_of_week = ?");
        $check->bind_param("is", $doctor_id, $day);
        $check->execute();
        $res = $check->get_result();

        if ($res->num_rows > 0) {
            $update = $conn->prepare("UPDATE doctor_availability SET start_time = ?, end_time = ?, availability = ? WHERE doctor_id = ? AND day_of_week = ?");
            $update->bind_param("ssiis", $start, $end, $is_available, $doctor_id, $day);
            $update->execute();
        } else {
            $insert = $conn->prepare("INSERT INTO doctor_availability (doctor_id, day_of_week, start_time, end_time, availability) VALUES (?, ?, ?, ?, ?)");
            $insert->bind_param("isssi", $doctor_id, $day, $start, $end, $is_available);
            $insert->execute();
        }
    }
    $success_msg = "All availability settings updated successfully!";
}

// Handle Appointment Status Update
if (isset($_GET['action']) && isset($_GET['id'])) {
    $appt_id = intval($_GET['id']);
    $status = $_GET['action'] == 'confirm' ? 'Confirmed' : 'Cancelled';
    
    $update_appt = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ? AND doctor_id = ?");
    $update_appt->bind_param("sii", $status, $appt_id, $doctor_id);
    $update_appt->execute();
    header("Location: index.php"); // Refresh to clear query params
    exit;
}

// Fetch Appointments
$sql_appt = "SELECT * FROM appointments WHERE doctor_id = ? ORDER BY appointment_date DESC, appointment_time ASC";
$stmt = $conn->prepare($sql_appt);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$appointments = $stmt->get_result();

// Fetch Availability
$sql_avail = "SELECT * FROM doctor_availability WHERE doctor_id = ?";
$stmt_avail = $conn->prepare($sql_avail);
$stmt_avail->bind_param("i", $doctor_id);
$stmt_avail->execute();
$availability_result = $stmt_avail->get_result();
$availability = [];
while ($row = $availability_result->fetch_assoc()) {
    $availability[$row['day_of_week']] = $row;
}

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - Med Buddy</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0066CC;
            --secondary-color: #f4fdf0; /* Matching green theme background */
            --white: #ffffff;
            --text-dark: #1F2937;
            --border-color: #e5e7eb;
        }

        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            background-color: #f9fafb;
            display: flex;
            height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background-color: var(--white);
            border-right: 1px solid var(--border-color);
            padding: 2rem;
            display: flex;
            flex-direction: column;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary-color);
            margin-bottom: 3rem;
        }

        .menu-item {
            padding: 0.75rem 1rem;
            color: var(--text-dark);
            text-decoration: none;
            border-radius: 6px;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .menu-item.active {
            background-color: var(--secondary-color);
            color: var(--primary-color);
        }

        .menu-item:hover {
            background-color: #f3f4f6;
        }

        .logout {
            margin-top: auto;
            color: red;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .card {
            background: var(--white);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        h2 {
            margin-top: 0;
            color: var(--text-dark);
            border-bottom: 2px solid #eee;
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
        }

        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            text-align: left;
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            background-color: #f9fafb;
            font-weight: 600;
        }

        .status {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .status-Pending { background-color: #FEF3C7; color: #D97706; }
        .status-Confirmed { background-color: #D1FAE5; color: #059669; }
        .status-Cancelled { background-color: #FEE2E2; color: #DC2626; }

        .btn-action {
            padding: 0.4rem 0.8rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            text-decoration: none;
            margin-right: 0.5rem;
        }
        
        .btn-confirm { background-color: #059669; color: white; }
        .btn-cancel { background-color: #DC2626; color: white; }

        /* Availability Form */
        .availability-row {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            gap: 1rem;
            padding: 1rem;
            background: #f9fafb;
            border-radius: 6px;
        }
        
        .availability-row h4 { margin: 0; width: 100px; }
        
        input[type="time"] {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .btn-save {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .alert {
            padding: 1rem;
            background-color: #D1FAE5;
            color: #059669;
            border-radius: 6px;
            margin-bottom: 1rem;
        }

        /* Toggle Switch */
        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }

        .switch input { 
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            -webkit-transition: .4s;
            transition: .4s;
            border-radius: 24px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            -webkit-transition: .4s;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: var(--primary-color);
        }

        input:focus + .slider {
            box-shadow: 0 0 1px var(--primary-color);
        }

        input:checked + .slider:before {
            -webkit-transform: translateX(26px);
            -ms-transform: translateX(26px);
            transform: translateX(26px);
        }

        .day-label {
            width: 120px;
            font-weight: 600;
        }

        .time-inputs {
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: opacity 0.3s;
        }

        .time-inputs.disabled {
            opacity: 0.5;
            pointer-events: none;
        }

        .availability-table {
            width: 100%;
            margin-bottom: 2rem;
        }

        .availability-table td {
            padding: 1.25rem 1rem;
            border-bottom: 1px solid #f3f4f6;
        }

        .save-all-container {
            display: flex;
            justify-content: flex-end;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
        }

        .btn-save-all {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 1rem 2.5rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0, 102, 204, 0.2);
            transition: all 0.3s;
        }

        .btn-save-all:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 102, 204, 0.3);
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="logo">Med Buddy</div>
        <a href="#appointments" class="menu-item active">Appointments</a>
        <a href="#availability" class="menu-item">My Schedule</a>
        <a href="search_patient.php" class="menu-item">Patient Results</a>
        <a href="logout.php" class="menu-item logout">Logout</a>
    </div>

    <div class="main-content">
        <div class="header">
            <h1>Welcome, <?php echo htmlspecialchars($doctor_name); ?></h1>
        </div>
        
        <?php if(isset($success_msg)) echo "<div class='alert'>$success_msg</div>"; ?>

        <!-- Profile Section -->
        <div class="card" style="display: flex; align-items: start; gap: 2rem;">
            <div style="flex-shrink: 0;">
                <?php 
                $img_src = !empty($doctor_data['image_path']) ? "../" . $doctor_data['image_path'] : "https://via.placeholder.com/150";
                ?>
                <img src="<?php echo htmlspecialchars($img_src); ?>" alt="Doctor Photo" style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid #E6F4FF;">
            </div>
            <div>
                <h2 style="border: none; margin-bottom: 0.5rem; padding-bottom: 0;"><?php echo htmlspecialchars($doctor_data['name']); ?></h2>
                <p style="margin: 0 0 1rem; color: #666; font-weight: 500;">
                    <?php echo htmlspecialchars($doctor_data['specialization']); ?> | 
                    ID: <span style="color: var(--primary-color); font-weight: 700;"><?php echo htmlspecialchars($doctor_data['doctor_identity'] ?? 'N/A'); ?></span>
                </p>
                
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; font-size: 0.95rem;">
                    <div>
                        <strong>Email:</strong> <?php echo htmlspecialchars($doctor_data['email']); ?>
                    </div>
                    <div>
                        <strong>Phone:</strong> <?php echo htmlspecialchars($doctor_data['phone_number'] ?? 'N/A'); ?>
                    </div>
                    <div>
                        <strong>Work Type:</strong> <?php echo htmlspecialchars($doctor_data['work_type'] ?? 'N/A'); ?>
                    </div>
                    
                    <?php if(($doctor_data['work_type'] ?? '') == 'Clinic'): ?>
                    <div>
                        <strong>Clinic:</strong> <?php echo htmlspecialchars($doctor_data['clinic_name'] ?? ''); ?>
                    </div>
                    <div>
                        <strong>Timings:</strong> <?php echo htmlspecialchars($doctor_data['clinic_timings'] ?? ''); ?>
                    </div>
                    <?php else: ?>
                    <div>
                        <strong>Hospital:</strong> <?php echo htmlspecialchars($doctor_data['hospital_name'] ?? ''); ?>
                    </div>
                    <div>
                        <strong>Designation:</strong> <?php echo htmlspecialchars($doctor_data['designation'] ?? ''); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Appointments Section -->
        <div id="appointments" class="card">
            <h2>Upcoming Appointments</h2>
            <?php if ($appointments->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Patient Name</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($appt = $appointments->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $appt['appointment_date']; ?></td>
                        <td><?php echo date('h:i A', strtotime($appt['appointment_time'])); ?></td>
                        <td><?php echo htmlspecialchars($appt['patient_name']); ?></td>
                        <td><?php echo htmlspecialchars($appt['patient_phone']); ?></td>
                        <td><span class="status status-<?php echo $appt['status']; ?>"><?php echo $appt['status']; ?></span></td>
                        <td>
                            <?php if($appt['status'] == 'Pending'): ?>
                            <a href="?action=confirm&id=<?php echo $appt['id']; ?>" class="btn-action btn-confirm">Confirm</a>
                            <a href="?action=cancel&id=<?php echo $appt['id']; ?>" class="btn-action btn-cancel">Cancel</a>
                            <?php else: ?>
                            --
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
                <p>No appointments found.</p>
            <?php endif; ?>
        </div>

        <!-- Availability Section -->
        <div id="availability" class="card">
            <h2>Manage Weekly Availability</h2>
            <p style="margin-bottom: 2rem; color: #666;">Toggle your availability and set working hours for the entire week.</p>
            
            <form method="POST">
                <table class="availability-table">
                    <tbody>
                        <?php foreach($days as $day): 
                            $avail_data = isset($availability[$day]) ? $availability[$day] : null;
                            $is_avail = ($avail_data && isset($avail_data['availability'])) ? $avail_data['availability'] : 0;
                            $start = $avail_data ? $avail_data['start_time'] : '09:00';
                            $end = $avail_data ? $avail_data['end_time'] : '17:00';
                        ?>
                        <tr>
                            <td class="day-label"><?php echo $day; ?></td>
                            <td style="width: 80px;">
                                <label class="switch">
                                    <input type="checkbox" name="days[<?php echo $day; ?>][is_available]" 
                                           onchange="toggleDayRow(this, '<?php echo $day; ?>')"
                                           <?php echo $is_avail ? 'checked' : ''; ?>>
                                    <span class="slider"></span>
                                </label>
                            </td>
                            <td>
                                <div class="time-inputs <?php echo $is_avail ? '' : 'disabled'; ?>" id="inputs-<?php echo $day; ?>">
                                    <label>From:</label>
                                    <input type="time" name="days[<?php echo $day; ?>][start_time]" value="<?php echo date('H:i', strtotime($start)); ?>">
                                    <label>To:</label>
                                    <input type="time" name="days[<?php echo $day; ?>][end_time]" value="<?php echo date('H:i', strtotime($end)); ?>">
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="save-all-container">
                    <button type="submit" name="save_all_availability" class="btn-save-all">Save Weekly Schedule</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleDayRow(checkbox, day) {
            const inputs = document.getElementById('inputs-' + day);
            if (checkbox.checked) {
                inputs.classList.remove('disabled');
            } else {
                inputs.classList.add('disabled');
            }
        }
    </script>

</body>
</html>
