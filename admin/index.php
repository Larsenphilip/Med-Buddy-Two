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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Using Remix Icon -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">

    <style>
        :root {
            --primary-color: #0066CC;
            --primary-hover: #004C99;
            --secondary-color: #E6F4FF;
            --accent-color: #00D2D3;
            --text-dark: #1F2937;
            --text-light: #6B7280;
            --white: #FFFFFF;
            --card-bg: #FFFFFF;
            --border-color: #E5E7EB;
            --sidebar-width: 260px;
            --header-height: 70px;
            --dashboard-bg: #F8FAFC;
            --border-radius: 12px;
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: var(--dashboard-bg);
            color: var(--text-dark);
            height: 100vh;
            display: flex;
            overflow: hidden;
        }

        /* --- Sidebar --- */
        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--secondary-color);
            height: 100vh;
            display: flex;
            flex-direction: column;
            border-right: 1px solid var(--border-color);
            padding: 1.5rem;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 100;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary-color);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            letter-spacing: -0.5px;
            margin-bottom: 3rem;
            padding-left: 0.5rem;
        }

        .logo span {
            color: var(--text-dark);
        }

        .sidebar-menu {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .sidebar-menu a {
            text-decoration: none;
            color: var(--text-light);
            font-weight: 500;
            padding: 0.85rem 1rem;
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: var(--transition);
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background-color: var(--primary-color);
            color: var(--white);
            box-shadow: 0 4px 12px rgba(0, 102, 204, 0.2);
        }

        .sidebar-menu a i {
            font-size: 1.25rem;
        }

        .logout-btn {
            margin-top: auto;
            color: #ef4444 !important;
        }

        .logout-btn:hover {
            background-color: #fee2e2 !important;
            color: #ef4444 !important;
            box-shadow: none !important;
        }

        /* --- Main Content --- */
        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            height: 100vh;
            overflow-y: auto;
            position: relative;
        }

        /* --- Header --- */
        .top-bar {
            height: var(--header-height);
            background-color: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            display: flex;
            justify-content: flex-end;
            align-items: center;
            padding: 0 2rem;
            position: sticky;
            top: 0;
            z-index: 90;
            border-bottom: 1px solid var(--border-color);
        }

        .top-bar-info {
            display: flex;
            align-items: center;
            gap: 2rem;
            font-size: 0.9rem;
            color: var(--text-light);
        }

        .doc-id span {
            font-weight: 600;
            color: var(--text-dark);
        }

        /* --- Dashboard UI --- */
        .dashboard-container {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .profile-card {
            background: var(--white);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid var(--border-color);
            display: flex;
            gap: 2rem;
            align-items: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
        }

        .profile-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--secondary-color);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .profile-details h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .profile-details .spec {
            color: var(--primary-color);
            font-weight: 600;
            font-size: 0.95rem;
            margin-bottom: 1rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            font-size: 0.9rem;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-item span:first-child {
            color: var(--text-light);
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .info-item span:last-child {
            font-weight: 600;
            color: var(--text-dark);
        }

        /* --- Appointment Section --- */
        .section-card {
            background: var(--white);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid var(--border-color);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-title i {
            color: var(--primary-color);
        }

        /* --- Modern Table --- */
        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 1.25rem 1rem;
            font-size: 0.85rem;
            text-transform: uppercase;
            font-weight: 700;
            color: var(--text-light);
            background: #f8fafc;
            border-bottom: 2px solid var(--border-color);
        }

        td {
            padding: 1.25rem 1rem;
            border-bottom: 1px solid var(--border-color);
            font-weight: 500;
            font-size: 0.95rem;
        }

        tr:hover {
            background-color: #f9fafb;
        }

        .status-badge {
            padding: 6px 14px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .status-Pending { background: #fee2e2; color: #ef4444; }
        .status-Confirmed { background: #dcfce7; color: #16a34a; }
        .status-Cancelled { background: #f3f4f6; color: #4b5563; }

        .btn-action {
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.85rem;
            text-decoration: none;
            transition: var(--transition);
            display: inline-block;
        }

        .btn-confirm {
            background-color: var(--primary-color);
            color: white;
            margin-right: 0.5rem;
        }

        .btn-confirm:hover {
            background-color: var(--primary-hover);
        }

        .btn-cancel {
            background-color: #fee2e2;
            color: #ef4444;
        }

        .btn-cancel:hover {
            background-color: #fecaca;
        }

        /* --- Availability Controls --- */
        .availability-table {
            width: 100%;
        }

        .availability-row td {
            padding: 1.5rem 1rem;
        }

        .day-name {
            font-weight: 700;
            font-size: 1rem;
            width: 150px;
        }

        /* Switch Styling */
        .switch {
            position: relative;
            display: inline-block;
            width: 48px;
            height: 24px;
        }

        .switch input { opacity: 0; width: 0; height: 0; }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: #e5e7eb;
            transition: .4s;
            border-radius: 24px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 18px; width: 18px;
            left: 3px; bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider { background-color: var(--primary-color); }
        input:checked + .slider:before { transform: translateX(24px); }

        .time-inputs {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            transition: var(--transition);
        }

        .time-inputs.disabled {
            opacity: 0.3;
            pointer-events: none;
        }

        .time-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .time-group label {
            font-size: 0.8rem;
            color: var(--text-light);
            font-weight: 600;
        }

        input[type="time"] {
            border: 1.5px solid var(--border-color);
            padding: 8px 12px;
            border-radius: 8px;
            font-weight: 600;
            outline: none;
        }

        input[type="time"]:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.1);
        }

        .btn-save-schedule {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: 0 4px 12px rgba(0, 102, 204, 0.2);
            float: right;
        }

        .btn-save-schedule:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 102, 204, 0.3);
        }

        .alert-success {
            background-color: #dcfce7;
            color: #16a34a;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* --- Scrollbar --- */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

    </style>
</head>
<body>

    <!-- Sidebar -->
    <aside class="sidebar">
        <a href="index.php" class="logo">
            <i class="ri-heart-pulse-fill"></i>
            <span>Med Buddy</span>
        </a>

        <ul class="sidebar-menu">
            <li><a href="#appointments" class="active"><i class="ri-calendar-check-line"></i> Appointments</a></li>
            <li><a href="#availability"><i class="ri-time-line"></i> My Schedule</a></li>
            <li><a href="search_patient.php"><i class="ri-folder-user-line"></i> Patient Results</a></li>
            <li><a href="logout.php" class="logout-btn"><i class="ri-logout-box-line"></i> Logout</a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Header -->
        <div class="top-bar">
            <div class="top-bar-info">
                <div class="doc-id">Doctor ID: <span><?php echo htmlspecialchars($doctor_data['doctor_identity'] ?? 'N/A'); ?></span></div>
                <div class="user-profile">
                    <i class="ri-user-3-line"></i>
                    <span>Welcome, Dr. <?php echo htmlspecialchars($firstName ?? explode(' ', $doctor_name)[0]); ?></span>
                </div>
            </div>
        </div>

        <div class="dashboard-container">
            <?php if(isset($success_msg)): ?>
            <div class="alert-success">
                <i class="ri-checkbox-circle-line"></i> <?php echo $success_msg; ?>
            </div>
            <?php endif; ?>

            <!-- Doctor Profile Card -->
            <div class="profile-card">
                <?php 
                $img_src = !empty($doctor_data['image_path']) ? "../" . $doctor_data['image_path'] : "https://ui-avatars.com/api/?name=Doctor&background=E6F4FF&color=0066CC";
                ?>
                <img src="<?php echo htmlspecialchars($img_src); ?>" alt="Doctor" class="profile-photo" loading="lazy" width="120" height="120" onerror="this.src='https://ui-avatars.com/api/?name=Doctor&background=E6F4FF&color=0066CC'">
                
                <div class="profile-details">
                    <h2>Dr. <?php echo htmlspecialchars($doctor_data['name']); ?></h2>
                    <p class="spec"><?php echo htmlspecialchars($doctor_data['specialization']); ?></p>
                    
                    <div class="info-grid">
                        <div class="info-item">
                            <span>Email Address</span>
                            <span><?php echo htmlspecialchars($doctor_data['email']); ?></span>
                        </div>
                        <div class="info-item">
                            <span>Phone Number</span>
                            <span><?php echo htmlspecialchars($doctor_data['phone_number'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="info-item">
                            <span>Work Type</span>
                            <span><?php echo htmlspecialchars($doctor_data['work_type'] ?? 'N/A'); ?></span>
                        </div>
                        <?php if(($doctor_data['work_type'] ?? '') == 'Clinic'): ?>
                        <div class="info-item">
                            <span>Clinic Name</span>
                            <span><?php echo htmlspecialchars($doctor_data['clinic_name'] ?? 'N/A'); ?></span>
                        </div>
                        <?php else: ?>
                        <div class="info-item">
                            <span>Hospital Name</span>
                            <span><?php echo htmlspecialchars($doctor_data['hospital_name'] ?? 'N/A'); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Appointments Grid -->
            <section id="appointments" class="section-card">
                <div class="section-header">
                    <h3 class="section-title"><i class="ri-calendar-event-line"></i> Upcoming Appointments</h3>
                </div>

                <div class="table-wrapper">
                    <?php if ($appointments->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Patient Name</th>
                                <th>Contact</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($appt = $appointments->fetch_assoc()): ?>
                            <tr>
                                <td><i class="ri-calendar-line"></i> <?php echo date('d M, Y', strtotime($appt['appointment_date'])); ?></td>
                                <td><i class="ri-time-line"></i> <?php echo date('h:i A', strtotime($appt['appointment_time'])); ?></td>
                                <td style="color: var(--primary-color); font-weight: 700;"><?php echo htmlspecialchars($appt['patient_name']); ?></td>
                                <td><?php echo htmlspecialchars($appt['patient_phone']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $appt['status']; ?>">
                                        <?php if($appt['status'] == 'Pending') echo '<i class="ri-loader-4-line"></i>'; ?>
                                        <?php if($appt['status'] == 'Confirmed') echo '<i class="ri-checkbox-circle-line"></i>'; ?>
                                        <?php if($appt['status'] == 'Cancelled') echo '<i class="ri-close-circle-line"></i>'; ?>
                                        <?php echo $appt['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if($appt['status'] == 'Pending'): ?>
                                    <a href="?action=confirm&id=<?php echo $appt['id']; ?>" class="btn-action btn-confirm">Confirm</a>
                                    <a href="?action=cancel&id=<?php echo $appt['id']; ?>" class="btn-action btn-cancel">Cancel</a>
                                    <?php else: ?>
                                    <span style="color: var(--text-light); font-size: 0.8rem;">Action Taken</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div style="text-align: center; padding: 4rem 1rem;">
                        <i class="ri-calendar-check-line" style="font-size: 3rem; color: var(--border-color); margin-bottom: 1rem; display: block;"></i>
                        <p style="color: var(--text-light); font-weight: 500;">No appointments scheduled for you yet.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Schedule Management -->
            <section id="availability" class="section-card">
                <div class="section-header">
                    <h3 class="section-title"><i class="ri-history-line"></i> Manage Weekly Schedule</h3>
                </div>
                <p style="color: var(--text-light); margin-bottom: 2rem; font-size: 0.95rem;">Configure your working hours for each day. Patients will only be able to book during these slots.</p>

                <form method="POST">
                    <table class="availability-table">
                        <tbody>
                            <?php foreach($days as $day): 
                                $avail_data = isset($availability[$day]) ? $availability[$day] : null;
                                $is_avail = ($avail_data && isset($avail_data['availability'])) ? $avail_data['availability'] : 0;
                                $start = $avail_data ? $avail_data['start_time'] : '09:00';
                                $end = $avail_data ? $avail_data['end_time'] : '17:00';
                            ?>
                            <tr class="availability-row">
                                <td class="day-name"><?php echo $day; ?></td>
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
                                        <div class="time-group">
                                            <label>Start From</label>
                                            <input type="time" name="days[<?php echo $day; ?>][start_time]" value="<?php echo date('H:i', strtotime($start)); ?>">
                                        </div>
                                        <div class="time-group">
                                            <label>End At</label>
                                            <input type="time" name="days[<?php echo $day; ?>][end_time]" value="<?php echo date('H:i', strtotime($end)); ?>">
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div style="margin-top: 2rem; border-top: 1px solid var(--border-color); padding-top: 2rem; overflow: hidden;">
                        <button type="submit" name="save_all_availability" class="btn-save-schedule">
                            <i class="ri-save-line"></i> Save Weekly Schedule
                        </button>
                    </div>
                </form>
            </section>

        </div>
        
        <?php include '../footer.php'; ?>
    </main>

    <script>
        function toggleDayRow(checkbox, day) {
            const inputs = document.getElementById('inputs-' + day);
            if (checkbox.checked) {
                inputs.classList.remove('disabled');
            } else {
                inputs.classList.add('disabled');
            }
        }

        // Sidebar active state logic
        document.querySelectorAll('.sidebar-menu a').forEach(link => {
            link.addEventListener('click', function() {
                document.querySelectorAll('.sidebar-menu a').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
            });
        });
    </script>

</body>
</html>
