<?php
session_start();
require_once 'db_config.php';

// Check if user is logged in
if (!isset($_SESSION['patient_id'])) {
    // Redirect to home/login if not logged in
    header("Location: index.html");
    exit();
}

$patient_id = $_SESSION['patient_id'];



// Prepare statement to fetch patient details
$sql = "SELECT * FROM patients WHERE patient_id = ?";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("s", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $patient = $result->fetch_assoc();
    $stmt->close();
} else {
    die("Database query failed: " . $conn->error);
}

if (!$patient) {
    // Handle case where session exists but patient not found (deleted?)
    session_destroy();
    header("Location: index.html");
    exit();
}

// Helper function to handle NULL/Empty values
function displayField($value)
{
    return (!empty($value)) ? htmlspecialchars($value) : '<span class="not-provided">Not provided</span>';
}

// Calculate Age
$age = 'N/A';
if (!empty($patient['date_of_birth'])) {
    $dob = new DateTime($patient['date_of_birth']);
    $now = new DateTime();
    $age = $now->diff($dob)->y;
}

// Get First Name for Welcome Message
$fullName = $patient['full_name'] ?? '';
$firstName = !empty($fullName) ? explode(' ', $fullName)[0] : 'Patient';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Profile - Med Buddy</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Using Remix Icon -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">

    <style>
        :root {
            /* Inherited Theme from Index */
            --primary-color: #0066CC;
            --primary-hover: #004C99;
            --secondary-color: #E6F4FF;
            --accent-color: #00D2D3;
            --text-dark: #1F2937;
            --text-light: #6B7280;
            --white: #FFFFFF;
            --card-bg: #FFFFFF;
            --border-color: #E5E7EB;

            /* Dashboard Specific */
            --sidebar-width: 260px;
            --sidebar-bg: #F3F4F6;
            --header-height: 70px;
            --dashboard-bg: #F8FAFC;

            --container-padding: 5%;
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

        .patient-id span {
            font-weight: 600;
            color: var(--text-dark);
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logout-link {
            color: var(--text-light);
            text-decoration: none;
            transition: var(--transition);
        }

        .logout-link:hover {
            color: #ef4444;
        }

        /* --- Dashboard Grid --- */
        .dashboard-container {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .welcome-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            position: relative;
        }

        .welcome-text h1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .welcome-text p {
            color: var(--text-light);
            font-size: 1.1rem;
        }

        .welcome-banner-img {
            position: absolute;
            right: 0;
            top: -20px;
            height: 180px;
            opacity: 0.9;
        }

        /* Layout Grid (Old UI) */
        .grid-layout {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            /* Summary vs Appointment */
            gap: 2rem;
            margin-bottom: 3rem;
        }

        /* Health Summary Card */
        .summary-card {
            background-color: var(--secondary-color);
            border-radius: 20px;
            padding: 0;
            overflow: hidden;
            border: 1px solid rgba(0, 102, 204, 0.1);
            height: 100%;
        }

        .summary-header {
            background-color: #FEF3C7;
            /* Soft yellow accent */
            padding: 1.5rem 2rem;
            font-weight: 600;
            color: #92400E;
            font-size: 1.1rem;
        }

        .summary-content {
            padding: 2rem;
        }

        .info-row {
            margin-bottom: 0.75rem;
            font-size: 1.05rem;
            color: var(--text-dark);
        }

        .info-row span {
            font-weight: 600;
            min-width: 120px;
            display: inline-block;
            color: var(--text-light);
        }

        .download-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            margin-top: 1.5rem;
            font-size: 0.9rem;
        }

        /* Appointment Card */
        .appointment-card {
            background: var(--white);
            border-radius: 20px;
            padding: 1.5rem;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
            height: 100%;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .card-header h3 {
            font-size: 1.1rem;
            font-weight: 700;
        }

        .doctor-preview {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .doc-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: #E2E8F0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .doc-info h4 {
            font-size: 1rem;
            font-weight: 600;
        }

        .doc-info p {
            font-size: 0.9rem;
            color: var(--text-light);
        }

        .appt-meta {
            font-size: 0.9rem;
            color: var(--text-dark);
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
        }

        .appt-actions {
            display: flex;
            gap: 1rem;
        }

        .btn-sm {
            flex: 1;
            padding: 0.7rem;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            font-size: 0.9rem;
            transition: var(--transition);
        }

        .btn-fill {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-dark);
        }

        /* --- New UI: Profile Details Grid --- */
        .section-header-large {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 1.5rem;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 0.5rem;
            margin-top: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .profile-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .detail-card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            border: 1px solid var(--border-color);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
            transition: var(--transition);
        }

        .detail-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.05);
            border-color: rgba(0, 102, 204, 0.3);
        }

        .card-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #f0f0f0;
        }

        .card-title i {
            color: var(--primary-color);
            font-size: 1.2rem;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.85rem;
            font-size: 0.95rem;
        }

        .detail-row:last-child {
            margin-bottom: 0;
        }

        .detail-label {
            color: var(--text-light);
            font-weight: 500;
        }

        .detail-value {
            color: var(--text-dark);
            font-weight: 600;
            text-align: right;
            max-width: 60%;
        }

        .not-provided {
            color: #9ca3af;
            font-style: italic;
            font-weight: 400;
        }

        /* Badge styles for specific fields */
        .badge {
            padding: 0.25rem 0.6rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .blood-group {
            background: #fee2e2;
            color: #991b1b;
        }

        .condition-tag {
            display: inline-block;
            background: #e0f2fe;
            color: #075985;
            padding: 2px 8px;
            border-radius: 4px;
            margin-bottom: 4px;
            font-size: 0.85rem;
        }

        /* Report Grid & Visits (Old UI) */
        .reports-section {
            margin-bottom: 3rem;
        }

        .section-header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-dark);
        }

        .view-all {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .reports-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .report-card {
            background: var(--white);
            padding: 1.25rem;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: var(--transition);
        }

        .report-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
            border-color: var(--primary-color);
        }

        .file-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .icon-red {
            background: #FEF2F2;
            color: #EF4444;
        }

        .icon-blue {
            background: #EBF8FF;
            color: #3182CE;
        }

        .report-info {
            flex: 1;
        }

        .report-name {
            font-weight: 600;
            font-size: 0.95rem;
            margin-bottom: 0.2rem;
        }

        .report-date {
            font-size: 0.8rem;
            color: var(--text-light);
        }

        /* Doctor Visits */
        .visit-card {
            background: var(--white);
            padding: 1.25rem;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            transition: var(--transition);
        }

        .visit-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .visit-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .btn-view {
            padding: 0.5rem 1rem;
            background: #2563EB;
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
        }

        /* Footer Help */
        .help-container {
            background: linear-gradient(135deg, #F3F4F6 0%, #E5E7EB 100%);
            border-radius: 20px;
            padding: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 2rem;
        }

        .help-text h3 {
            margin-bottom: 0.5rem;
            color: var(--text-dark);
        }

        .contact-methods {
            display: flex;
            gap: 2rem;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-dark);
            font-weight: 500;
        }

        .contact-item i {
            color: var(--primary-color);
        }

        /* Mobile Responsive */
        @media (max-width: 1024px) {
            .grid-layout {
                grid-template-columns: 1fr;
            }

            .welcome-banner-img {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .dashboard-container {
                padding: 1rem;
            }

            .help-container {
                flex-direction: column;
                align-items: flex-start;
            }

            .contact-methods {
                flex-direction: column;
                gap: 1rem;
            }

            .profile-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <!-- QR Code Modal -->
    <div id="qrModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; justify-content: center; align-items: center;">
        <div style="background: white; padding: 2rem; border-radius: 12px; text-align: center; position: relative; max-width: 400px; width: 90%; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
            <button onclick="closeQRModal()" style="position: absolute; top: 10px; right: 10px; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #666;">&times;</button>
            <h3 style="margin-bottom: 1rem; color: #1f2937; font-weight: 700;">Your Medical QR Code</h3>
            <p style="font-size: 0.875rem; color: #6b7280; margin-bottom: 1.5rem;">Scan to share Patient ID, blood group, allergies, and emergency contact info.</p>
            
            <div id="qrLoading" style="margin: 2rem 0; display: none;">
                <div style="width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3b82f6; border-radius: 50%; animation: qr-spin 1s linear infinite; margin: 0 auto;"></div>
                <p style="margin-top: 10px; color: #6b7280;">Generating...</p>
            </div>
            
            <div id="qrImageContainer" style="min-height: 250px; display: flex; align-items: center; justify-content: center;">
                <img id="qrImage" src="" alt="QR Code" style="display: none; width: 250px; height: 250px; border: 1px solid #e5e7eb; border-radius: 8px;">
            </div>
            
            <button id="downloadQRBtn" style="display: none; margin-top: 1.5rem; width: 100%; padding: 0.75rem; background: #10b981; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; align-items: center; gap: 8px; justify-content: center;">
                <i class="ri-download-2-line"></i> Download PNG
            </button>

            <button onclick="closeQRModal()" style="margin-top: 1rem; width: 100%; padding: 0.75rem; background: #ef4444; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">Close</button>
        </div>
    </div>

    <script>
        function closeQRModal() {
            document.getElementById('qrModal').style.display = 'none';
        }

        async function generateMyQR(event) {
            if(event) event.preventDefault();
            const modal = document.getElementById('qrModal');
            const loader = document.getElementById('qrLoading');
            const img = document.getElementById('qrImage');
            const dlBtn = document.getElementById('downloadQRBtn');
            const patientId = "<?php echo $patient_id; ?>";

            // Show modal and loader
            modal.style.display = 'flex';
            loader.style.display = 'block';
            img.style.display = 'none';
            dlBtn.style.display = 'none';

            try {
                const response = await fetch(`admin/generate_qr_action.php?patient_id=${patientId}`);
                const data = await response.json();

                loader.style.display = 'none';
                if (data.success) {
                    img.src = data.qr_url + '?v=' + Date.now();
                    img.style.display = 'block';
                    dlBtn.style.display = 'flex';
                    dlBtn.onclick = () => {
                        const a = document.createElement('a');
                        a.href = data.qr_url;
                        a.download = `QR_${patientId}.png`;
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                    };
                } else {
                    alert('QR Generation Failed: ' + (data.error || 'Server error'));
                    modal.style.display = 'none';
                }
            } catch (err) {
                loader.style.display = 'none';
                alert('Connection Error: ' + err.message);
                modal.style.display = 'none';
            }
        }
    </script>

    <style>
        @keyframes qr-spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>

    <!-- Sidebar -->
    <aside class="sidebar">
        <a href="index.html" class="logo">
            <i class="ri-heart-pulse-fill" style="color: var(--primary-color);"></i>
            <span>Med Buddy</span>
        </a>

        <ul class="sidebar-menu">
            <li><a href="#" class="active"><i class="ri-user-settings-fill"></i> My Profile</a></li>
           <!-- <li><a href="#"><i class="ri-file-user-line"></i> My Profile</a></li> -->
            <li><a href="appointment.php"><i class="ri-calendar-check-line"></i> Book Appointment</a></li>
            <li><a href="#"><i class="ri-notification-3-line"></i> Notifications</a></li>
        </ul>
    </aside>

    <!-- Main Content Area -->
    <main class="main-content">
        <!-- Top Header -->
        <div class="top-bar">
            <div class="top-bar-info">
                <div class="patient-id">Patient ID: <span><?php echo htmlspecialchars($patient_id); ?></span></div>
                <button id="generateQRBtn" onclick="generateMyQR(event)" class="qr-btn" style="background: var(--primary-color); color: white; border: none; padding: 6px 12px; border-radius: 6px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 5px;">
                    <i class="ri-qr-code-line"></i> Generate QR Code
                </button>
                <div class="user-profile">
                    <span>Welcome, <?php echo displayField($patient['full_name']); ?></span> | <a href="logout.php"
                        onclick="event.preventDefault(); document.cookie = 'PHPSESSID=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;'; window.location.href='logout.php';"
                        class="logout-link">Logout</a>
                </div>
            </div>
        </div>

        <div class="profile-container" style="padding: 2rem 5% 4rem;">

            <!-- Welcome Header -->
            <div class="welcome-section">
                <div class="welcome-text">
                    <h1>Welcome Back, <?php echo $firstName; ?></h1>
                    <p>Manage your health records and personal information.</p>
                </div>
                <button onclick="openUpdateModal()" class="btn-update">
                    <i class="ri-edit-box-line"></i> Update Profile
                </button>
            </div>

            <!-- Health Summary & Appointment (OLD UI + Dynamic Data) -->
            <div class="grid-layout">
                <!-- Left Column: Health Summary -->
                <div class="summary-card">
                    <div class="summary-header">Patient Health Summary</div>
                    <div class="summary-content">
                        <div class="info-row"><span>Name:</span> <?php echo displayField($patient['full_name']); ?>
                        </div>
                        <div class="info-row"><span>Age:</span> <?php echo $age; ?></div>
                        <div class="info-row"><span>Blood Group:</span>
                            <?php echo !empty($patient['blood_group']) ? htmlspecialchars($patient['blood_group']) : 'N/A'; ?>
                        </div>
                        <div class="info-row"><span>Conditions:</span>
                            <?php
                            if (!empty($patient['chronic_conditions'])) {
                                echo htmlspecialchars(substr($patient['chronic_conditions'], 0, 30)) . (strlen($patient['chronic_conditions']) > 30 ? '...' : '');
                            } else {
                                echo 'None';
                            }
                            ?>
                        </div>

                        <a href="#" class="download-link">
                            <i class="ri-download-cloud-2-line"></i> Download All Records
                        </a>
                    </div>
                </div>

                <!-- Right Column: Appointment (Keeping Static as Placeholder for now) -->
                <div class="appointment-card">
                    <div class="card-header">
                        <h3>Upcoming Appointment</h3>
                        <a href="#"><i class="ri-arrow-right-line" style="color: var(--text-light);"></i></a>
                    </div>

                    <div class="doctor-preview">
                        <div class="doc-avatar">👨‍⚕️</div>
                        <div class="doc-info">
                            <h4>Dr. Mehta</h4>
                            <p>Cardiologist</p>
                        </div>
                    </div>

                    <div class="appt-meta">
                        <span>Date: 25/04/2024</span>
                        <span>10:00 AM</span>
                    </div>

                    <div class="appt-actions">
                        <button class="btn-sm btn-fill">Reschedule</button>
                        <button class="btn-sm btn-outline">Cancel</button>
                    </div>
                </div>
            </div>

            <!-- Detailed Profile Information (NEW UI) -->
            <div class="section-header-large">
                Full Patient Profile
            </div>

            <div class="profile-grid">

                <!-- Personal Information -->
                <div class="detail-card">
                    <div class="card-title">
                        <i class="ri-user-line"></i> Personal Information
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Full Name</span>
                        <span class="detail-value"><?php echo displayField($patient['full_name']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Date of Birth</span>
                        <span class="detail-value"><?php echo displayField($patient['date_of_birth']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Gender</span>
                        <span class="detail-value"><?php echo displayField($patient['gender']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Blood Group</span>
                        <span
                            class="detail-value"><?php echo !empty($patient['blood_group']) ? '<span class="badge blood-group">' . htmlspecialchars($patient['blood_group']) . '</span>' : displayField(''); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Height</span>
                        <span class="detail-value"><?php echo displayField($patient['height']); ?> cm</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Weight</span>
                        <span class="detail-value"><?php echo displayField($patient['weight']); ?> kg</span>
                    </div>
                </div>

                <!-- Contact Details -->
                <div class="detail-card">
                    <div class="card-title">
                        <i class="ri-contacts-book-line"></i> Contact Details
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Phone</span>
                        <span class="detail-value"><?php echo displayField($patient['phone_number']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Email</span>
                        <span class="detail-value"><?php echo displayField($patient['email']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Address</span>
                        <span class="detail-value"><?php echo displayField($patient['address']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">City</span>
                        <span class="detail-value"><?php echo displayField($patient['city']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">State</span>
                        <span class="detail-value"><?php echo displayField($patient['state']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Postal Code</span>
                        <span class="detail-value"><?php echo displayField($patient['postal_code']); ?></span>
                    </div>
                </div>

                <!-- Medical Information -->
                <div class="detail-card">
                    <div class="card-title">
                        <i class="ri-stethoscope-line"></i> Medical Information
                    </div>
                    <div class="detail-row" style="display: block;">
                        <span class="detail-label" style="display: block; margin-bottom: 0.5rem;">Allergies</span>
                        <div class="detail-value" style="text-align: left; max-width: 100%;">
                            <?php
                            if (!empty($patient['allergies'])) {
                                echo htmlspecialchars($patient['allergies']);
                            } else {
                                echo displayField('');
                            }
                            ?>
                        </div>
                    </div>
                    <div class="detail-row" style="display: block; margin-top: 1rem;">
                        <span class="detail-label" style="display: block; margin-bottom: 0.5rem;">Chronic
                            Conditions</span>
                        <div class="detail-value" style="text-align: left; max-width: 100%;">
                            <?php
                            if (!empty($patient['chronic_conditions'])) {
                                $conditions = explode(',', $patient['chronic_conditions']);
                                foreach ($conditions as $cond) {
                                    echo '<span class="condition-tag">' . htmlspecialchars(trim($cond)) . '</span> ';
                                }
                            } else {
                                echo displayField('');
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <!-- Emergency Contact -->
                <div class="detail-card">
                    <div class="card-title">
                        <i class="ri-alert-line"></i> Emergency Contact
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Contact Name</span>
                        <span
                            class="detail-value"><?php echo displayField($patient['emergency_contact_name']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Phone Number</span>
                        <span
                            class="detail-value"><?php echo displayField($patient['emergency_contact_phone']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Document Uploads Section (Moved here from modal) -->
            <div class="section-header-large" style="margin-top:2.5rem; border-bottom: none;">
                <span style="font-size: 1.8rem;">Patient Forms</span>
            </div>
            <div
                style="margin-top: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; margin-bottom: 1.5rem;">
                <span style="font-size: 1.1rem; font-weight: 700; color: var(--text-dark);">Document Uploads</span>
            </div>


            <!-- Uploaded Patient Documents Section -->
            <?php
            $patient_docs = [];
            // Try fetching from patient_images table
            $sql_images = "SELECT * FROM patient_images WHERE patient_id = ? ORDER BY taken_at DESC";
            $stmt_img = $conn->prepare($sql_images);
            if ($stmt_img) {
                $stmt_img->bind_param("s", $patient_id);
                $stmt_img->execute();
                $res_img = $stmt_img->get_result();
                while ($row = $res_img->fetch_assoc()) {
                    $category = $row['image_type'];
                    if ($category === 'document' || empty($category)) {
                        $path_parts = explode('/', $row['file_path']);
                        $category = 'medical_reports'; // Default
                        if (count($path_parts) > 3) {
                            $category = $path_parts[3];
                        }
                    }
                    $patient_docs[] = [
                        'image_id' => $row['image_id'],
                        'category' => $category,
                        'original_name' => $row['original_file_name'],
                        'file_path' => $row['file_path'],
                        'custom_title' => $row['description'],
                        'custom_date' => $row['taken_at'],
                        'uploaded_at' => $row['taken_at']
                    ];
                }
                $stmt_img->close();
            }

            // Fallback to merge legacy JSON documents
            if (!empty($patient['documents'])) {
                $legacy_docs = json_decode($patient['documents'], true) ?? [];
                $patient_docs = array_merge($patient_docs, $legacy_docs);
            }

            if (!empty($patient_docs)):
                $grouped_docs = [];
                foreach ($patient_docs as $d) {
                    $cat = $d['category'];
                    if (!isset($grouped_docs[$cat])) {
                        $grouped_docs[$cat] = [];
                    }
                    $grouped_docs[$cat][] = $d;
                }
                ?>
                    <div class="section-header-large" style="margin-top:2.5rem;">
                        <span><i class="ri-folder-health-line" style="color:var(--primary-color);margin-right:8px;"></i>My Uploaded Documents</span>
                    </div>
                    <div class="docs-outer-grid">
                        <?php foreach ($grouped_docs as $cat_key => $docs_list): 
                            $display_cat = ucwords(str_replace('_', ' ', $cat_key));
                            // Dynamic colors
                            $hash = md5($cat_key);
                            $colors = ['#7C3AED', '#0284C7', '#059669', '#EA580C', '#E11D48'];
                            $bgColors = ['#F3E8FF', '#E0F2FE', '#D1FAE5', '#FFEDD5', '#FFE4E6'];
                            $colorIndex = hexdec(substr($hash, 0, 1)) % count($colors);
                            $iconColor = $colors[$colorIndex];
                            $bgColor = $bgColors[$colorIndex];
                        ?>
                            <div class="docs-category-card">
                                <div class="docs-cat-header" style="background:linear-gradient(135deg,<?php echo $iconColor; ?>22,<?php echo $iconColor; ?>11);">
                                    <i class="ri-folder-shield-2-line" style="color:<?php echo $iconColor; ?>;"></i> <?php echo htmlspecialchars($display_cat); ?>
                                </div>
                                <div class="docs-file-list">
                                    <?php foreach ($docs_list as $doc): ?>
                                            <?php
                                                $displayTitle = !empty($doc['custom_title']) ? $doc['custom_title'] : $doc['original_name'];
                                                if (!empty($doc['image_id'])) {
                                                    $view_url = "view_record.php?id=" . urlencode($doc['image_id']);
                                                } else {
                                                    $view_url = "view_record.php?file=" . urlencode($doc['file_path']) . "&title=" . urlencode($displayTitle);
                                                }
                                            ?>
                                            <a href="<?php echo htmlspecialchars($view_url); ?>" target="_blank"
                                                class="doc-file-item">
                                                <div class="doc-file-icon" style="background:<?php echo $bgColor; ?>;color:<?php echo $iconColor; ?>;">
                                                    <i
                                                        class="ri-<?php echo (pathinfo($doc['file_path'], PATHINFO_EXTENSION) === 'pdf') ? 'file-pdf-line' : 'image-line'; ?>"></i>
                                                </div>
                                                <div class="doc-file-info">
                                                    <span class="doc-file-name">
                                                        <?php echo htmlspecialchars($displayTitle); ?>
                                                    </span>
                                                    <span class="doc-file-date">
                                                        <?php
                                                        $displayDate = !empty($doc['custom_date']) ? $doc['custom_date'] : $doc['uploaded_at'];
                                                        echo date('d M Y', strtotime($displayDate));
                                                        ?>
                                                    </span>
                                                </div>
                                                <i class="ri-external-link-line doc-dl-icon"></i>
                                            </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
            <?php endif; ?>

            <!-- Reports Section (Old UI)
            <section class="reports-section">
                <div class="section-header-row">
                    <h2 class="section-title">Reports & Scans</h2>
                    <a href="#" class="view-all">View All <i class="ri-arrow-right-s-line"></i></a>
                </div>

                <div class="reports-grid">
                    <div class="report-card">
                        <div class="file-icon icon-red"><i class="ri-file-pdf-line"></i></div>
                        <div class="report-info">
                            <div class="report-name">Blood Test Report</div>
                            <div class="report-date">Uploaded today</div>
                        </div>
                        <div>24/04/2024</div>
                    </div>

                    <div class="report-card">
                        <div class="file-icon icon-blue"><i class="ri-image-line"></i></div>
                        <div class="report-info">
                            <div class="report-name">Chest X-Ray Scan</div>
                            <div class="report-date">Uploaded yesterday</div>
                        </div>
                        <div>23/04/2024</div>
                    </div>

                    <div class="report-card">
                        <div class="file-icon icon-blue"><i class="ri-image-line"></i></div>
                        <div class="report-info">
                            <div class="report-name">Chest X-Ray</div>
                            <div class="report-date">2 days ago</div>
                        </div>
                        <div>23/01/2024</div>
                    </div>

                    <div class="report-card">
                        <div class="file-icon icon-red"><i class="ri-file-pdf-line"></i></div>
                        <div class="report-info">
                            <div class="report-name">ECG Report</div>
                            <div class="report-date">Last Month</div>
                        </div>
                        <div>22/07/2023</div>
                    </div>
                </div>
            </section>
 -->
            <!-- Doctor Visits (Old UI) -->
            <section class="visits-section">
                <div class="section-header-row">
                    <h2 class="section-title">My Doctor Visits</h2>
                    <a href="#" class="view-all">View All <i class="ri-arrow-right-s-line"></i></a>
                </div>

                <div class="doctors-grid">
                    <div class="visit-card">
                        <div class="visit-left">
                            <div class="doc-avatar">👨‍⚕️</div>
                            <div class="doc-info">
                                <h4>Dr. Mehta</h4>
                                <p>Cardiologist</p>
                            </div>
                        </div>
                        <div style="font-weight: 500;">12/01/2024</div>
                        <a href="#" class="btn-view">View Details</a>
                    </div>

                    <div class="visit-card">
                        <div class="visit-left">
                            <div class="doc-avatar">👩‍⚕️</div>
                            <div class="doc-info">
                                <h4>Dr. Rao</h4>
                                <p>Endocrinologist</p>
                            </div>
                        </div>
                        <div style="font-weight: 500;">05/03/2023</div>
                        <a href="#" class="btn-view">View Details</a>
                    </div>
                </div>
            </section>

            <!-- Need Help (Old UI) -->
            <div class="help-container" style="margin-top: 3rem;">
                <div class="help-text">
                    <h3>Need Help? Contact Support</h3>
                    <p style="color: var(--text-light);">We are here to assist you 24/7</p>
                </div>
                <div class="contact-methods">
                    <div class="contact-item">
                        <i class="ri-mail-send-line"></i>
                        support@medbuddy.com
                    </div>
                    <div class="contact-item">
                        <i class="ri-phone-line"></i>
                        +1 800 123 4567
                    </div>
                </div>
            </div>

        </div>
    </main>

    <!-- Update Profile Modal -->
    <div id="updateModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Update Profile</h2>
                <button class="close-btn" onclick="closeUpdateModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="updateProfileForm">
                    <!-- Personal Info -->
                    <div class="profile-form-card">
                        <div class="card-title">
                            <i class="ri-user-smile-line"></i> Personal Information
                        </div>
                        <div class="grid-2">
                            <div class="form-group">
                                <label>Full Name</label>
                                <div class="input-with-icon">
                                    <i class="ri-user-line"></i>
                                    <input type="text" name="full_name"
                                        value="<?php echo htmlspecialchars($patient['full_name'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Date of Birth</label>
                                <div class="input-with-icon">
                                    <i class="ri-calendar-line"></i>
                                    <input type="date" name="date_of_birth"
                                        value="<?php echo htmlspecialchars($patient['date_of_birth'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Gender</label>
                                <select name="gender">
                                    <option value="">Select</option>
                                    <option value="Male" <?php if (($patient['gender'] ?? '') == 'Male')
                                        echo 'selected'; ?>>Male</option>
                                    <option value="Female" <?php if (($patient['gender'] ?? '') == 'Female')
                                        echo 'selected'; ?>>Female</option>
                                    <option value="Other" <?php if (($patient['gender'] ?? '') == 'Other')
                                        echo 'selected'; ?>>Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Blood Group</label>
                                <select name="blood_group">
                                    <option value="">Select</option>
                                    <option value="A+" <?php if (($patient['blood_group'] ?? '') == 'A+')
                                        echo 'selected'; ?>>A+</option>
                                    <option value="A-" <?php if (($patient['blood_group'] ?? '') == 'A-')
                                        echo 'selected'; ?>>A-</option>
                                    <option value="B+" <?php if (($patient['blood_group'] ?? '') == 'B+')
                                        echo 'selected'; ?>>B+</option>
                                    <option value="B-" <?php if (($patient['blood_group'] ?? '') == 'B-')
                                        echo 'selected'; ?>>B-</option>
                                    <option value="O+" <?php if (($patient['blood_group'] ?? '') == 'O+')
                                        echo 'selected'; ?>>O+</option>
                                    <option value="O-" <?php if (($patient['blood_group'] ?? '') == 'O-')
                                        echo 'selected'; ?>>O-</option>
                                    <option value="AB+" <?php if (($patient['blood_group'] ?? '') == 'AB+')
                                        echo 'selected'; ?>>AB+</option>
                                    <option value="AB-" <?php if (($patient['blood_group'] ?? '') == 'AB-')
                                        echo 'selected'; ?>>AB-</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Height (cm)</label>
                                <div class="input-with-icon">
                                    <i class="ri-ruler-2-line"></i>
                                    <input type="number" name="height"
                                        value="<?php echo htmlspecialchars($patient['height'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Weight (kg)</label>
                                <div class="input-with-icon">
                                    <i class="ri-scales-3-line"></i>
                                    <input type="number" name="weight"
                                        value="<?php echo htmlspecialchars($patient['weight'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Info (Address) -->
                    <div class="profile-form-card">
                        <div class="card-title">
                            <i class="ri-map-pin-line"></i> Address Details
                        </div>
                        <div class="form-group">
                            <label>Address</label>
                            <div class="input-with-icon">
                                <i class="ri-community-line"></i>
                                <input type="text" name="address"
                                    value="<?php echo htmlspecialchars($patient['address'] ?? ''); ?>"
                                    placeholder="Enter complete address">
                            </div>
                        </div>
                        <div class="grid-3">
                            <div class="form-group">
                                <label>City</label>
                                <div class="input-with-icon">
                                    <i class="ri-building-line"></i>
                                    <input type="text" name="city"
                                        value="<?php echo htmlspecialchars($patient['city'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>State</label>
                                <div class="input-with-icon">
                                    <i class="ri-map-2-line"></i>
                                    <input type="text" name="state"
                                        value="<?php echo htmlspecialchars($patient['state'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Postal Code</label>
                                <div class="input-with-icon">
                                    <i class="ri-hashtag"></i>
                                    <input type="text" name="postal_code"
                                        value="<?php echo htmlspecialchars($patient['postal_code'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Read-only Security Info -->
                    <div class="form-section">
                        <h3>Account Info (Read Only)</h3>
                        <div class="grid-2">
                            <div class="form-group">
                                <label>Email</label>
                                <input type="text" value="<?php echo htmlspecialchars($patient['email'] ?? ''); ?>"
                                    disabled style="background: #e5e7eb; cursor: not-allowed;">
                            </div>
                            <div class="form-group">
                                <label>Phone</label>
                                <input type="text"
                                    value="<?php echo htmlspecialchars($patient['phone_number'] ?? ''); ?>" disabled
                                    style="background: #e5e7eb; cursor: not-allowed;">
                            </div>
                        </div>
                    <!-- Medical & Emergency -->
                    <div class="profile-form-card">
                        <div class="card-title">
                            <i class="ri-heart-pulse-line"></i> Medical & Emergency
                        </div>
                        <div class="form-group">
                            <label>Allergies (Comma separated)</label>
                            <div class="input-with-icon">
                                <i class="ri-hand-coin-line"></i>
                                <input type="text" name="allergies"
                                    value="<?php echo htmlspecialchars($patient['allergies'] ?? ''); ?>"
                                    placeholder="e.g. Peanuts, Penicillin">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Chronic Conditions (Comma separated)</label>
                            <div class="input-with-icon">
                                <i class="ri-pulse-line"></i>
                                <input type="text" name="chronic_conditions"
                                    value="<?php echo htmlspecialchars($patient['chronic_conditions'] ?? ''); ?>"
                                    placeholder="e.g. Diabetes, Hypertension">
                            </div>
                        </div>
                        <div class="grid-2">
                            <div class="form-group">
                                <label>Emergency Contact Name</label>
                                <div class="input-with-icon">
                                    <i class="ri-user-voice-line"></i>
                                    <input type="text" name="emergency_contact_name"
                                        value="<?php echo htmlspecialchars($patient['emergency_contact_name'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Emergency Contact Phone</label>
                                <div class="input-with-icon">
                                    <i class="ri-phone-fill"></i>
                                    <input type="text" name="emergency_contact_phone"
                                        value="<?php echo htmlspecialchars($patient['emergency_contact_phone'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- New Document Uploads Section (Moved under Update Profile) -->
                    <div class="profile-form-card">
                        <div class="card-title">
                            <i class="ri-upload-cloud-2-line"></i> Document Uploads
                        </div>
                        <div id="upload-docs-section">
                            <!-- Medical Reports Category -->
                            <div class="category-card" style="margin-bottom: 1.5rem; border: 1px solid var(--border-color); border-radius: 12px; padding: 1rem;">
                                <div class="category-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.75rem; margin-bottom: 1.25rem;">
                                    <div class="cat-title-wrapper" style="display: flex; align-items: center; gap: 0.75rem; color: #1e40af;">
                                        <i class="ri-clipboard-line" style="font-size: 1.35rem;"></i>
                                        <h3 style="font-size: 1.15rem; font-weight: 700; margin: 0;">Medical Reports</h3>
                                    </div>
                                    <div class="cat-counter-wrapper" style="display: flex; align-items: center; gap: 0.5rem;">
                                        <span style="color: #64748b; font-size: 0.85rem;">No of Reports:</span>
                                        <input type="number" id="medical_reports-count" value="0" min="0" max="10" 
                                            style="width: 55px; padding: 0.35rem; border: 1px solid #cbd5e1; border-radius: 6px; text-align: center; font-weight: 700;" 
                                            oninput="updateCatInputs('medical_reports')">
                                    </div>
                                </div>
                                <div id="medical_reports-container" class="cat-items-list" style="display: flex; flex-direction: column; gap: 1rem;"></div>
                            </div>

                            <!-- Medical Scans Category -->
                            <div class="category-card" style="margin-bottom: 1.5rem; border: 1px solid var(--border-color); border-radius: 12px; padding: 1rem;">
                                <div class="category-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.75rem; margin-bottom: 1.25rem;">
                                    <div class="cat-title-wrapper" style="display: flex; align-items: center; gap: 0.75rem; color: #1e40af;">
                                        <i class="ri-focus-2-line" style="font-size: 1.35rem;"></i>
                                        <h3 style="font-size: 1.15rem; font-weight: 700; margin: 0;">Medical Scans</h3>
                                    </div>
                                    <div class="cat-counter-wrapper" style="display: flex; align-items: center; gap: 0.5rem;">
                                        <span style="color: #64748b; font-size: 0.85rem;">No of Scans:</span>
                                        <input type="number" id="scans-count" value="0" min="0" max="10" 
                                            style="width: 55px; padding: 0.35rem; border: 1px solid #cbd5e1; border-radius: 6px; text-align: center; font-weight: 700;" 
                                            oninput="updateCatInputs('scans')">
                                    </div>
                                </div>
                                <div id="scans-container" class="cat-items-list" style="display: flex; flex-direction: column; gap: 1rem;"></div>
                            </div>

                            <!-- Prescriptions Category -->
                            <div class="category-card" style="border: 1px solid var(--border-color); border-radius: 12px; padding: 1rem;">
                                <div class="category-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.75rem; margin-bottom: 1.25rem;">
                                    <div class="cat-title-wrapper" style="display: flex; align-items: center; gap: 0.75rem; color: #1e40af;">
                                        <i class="ri-capsule-line" style="font-size: 1.35rem;"></i>
                                        <h3 style="font-size: 1.15rem; font-weight: 700; margin: 0;">Prescriptions</h3>
                                    </div>
                                    <div class="cat-counter-wrapper" style="display: flex; align-items: center; gap: 0.5rem;">
                                        <span style="color: #64748b; font-size: 0.85rem;">No of Prescriptions:</span>
                                        <input type="number" id="prescriptions-count" value="0" min="0" max="10" 
                                            style="width: 55px; padding: 0.35rem; border: 1px solid #cbd5e1; border-radius: 6px; text-align: center; font-weight: 700;" 
                                            oninput="updateCatInputs('prescriptions')">
                                    </div>
                                </div>
                                <div id="prescriptions-container" class="cat-items-list" style="display: flex; flex-direction: column; gap: 1rem;"></div>
                            </div>


                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-save">Save Profile Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Styles for Modal + Upload Documents -->
    <style>
        .btn-update {
            padding: 0.75rem 1.5rem;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition);
        }

        .btn-update:hover {
            background-color: var(--primary-hover);
        }

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: white;
            width: 90%;
            max-width: 800px;
            border-radius: 16px;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            animation: slideUp 0.3s ease-out;
        }

        @keyframes slideUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            font-size: 1.25rem;
            margin: 0;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-light);
        }

        .modal-body {
            padding: 2rem;
            overflow-y: auto;
            background: #fbfbfc;
        }

        .form-section {
            margin-bottom: 2rem;
            border-bottom: 1px solid #f3f4f6;
            padding-bottom: 1rem;
        }

        .form-section h3 {
            font-size: 1rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-family: inherit;
        }

        .form-actions {
            text-align: right;
            position: sticky;
            bottom: -2rem;
            background: white;
            padding: 1rem 0;
            border-top: 1px solid var(--border-color);
        }

        .btn-save {
            padding: 0.75rem 2rem;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            transition: var(--transition);
        }

        .btn-save:hover {
            filter: brightness(1.1);
        }

        /* ---- Upload Documents UI ---- */
        .upload-category {
            margin-bottom: 1.5rem;
        }

        .upload-category-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--text-dark);
            margin-bottom: 0.6rem;
        }

        .upload-drop-zone {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            border: 2px dashed var(--border-color);
            border-radius: 12px;
            padding: 1.5rem 1rem;
            cursor: pointer;
            background: #F8FAFC;
            transition: all 0.2s ease;
            text-align: center;
            position: relative;
        }

        .upload-drop-zone:hover,
        .upload-drop-zone.dragover {
            border-color: var(--primary-color);
            background: var(--secondary-color);
        }

        .upload-icon {
            font-size: 2rem;
            color: var(--primary-color);
        }

        .upload-label {
            font-weight: 600;
            color: var(--text-dark);
            font-size: 0.9rem;
        }

        .upload-hint {
            font-size: 0.8rem;
            color: var(--text-light);
        }

        .upload-input {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }

        .file-preview-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.6rem;
        }

        .file-preview-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            background: #EFF6FF;
            border: 1px solid #BFDBFE;
            border-radius: 20px;
            padding: 0.3rem 0.8rem;
            font-size: 0.82rem;
            color: #1D4ED8;
            font-weight: 500;
        }

        .file-preview-chip i {
            font-size: 0.9rem;
        }

        .file-preview-chip .rm-file {
            cursor: pointer;
            color: #9CA3AF;
            margin-left: 2px;
            font-size: 0.9rem;
        }

        .file-preview-chip .rm-file:hover {
            color: #EF4444;
        }

        /* ---- New Profile Form Card Styles ---- */
        .profile-form-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid var(--border-color);
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
        }

        .profile-form-card .card-title {
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-light);
            margin-bottom: 0.5rem;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.95rem;
            color: var(--text-dark);
            background: #fff;
            transition: var(--transition);
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.1);
            outline: none;
        }

        .input-with-icon {
            position: relative;
        }

        .input-with-icon i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 1.1rem;
        }

        .input-with-icon input {
            padding-left: 2.75rem !important;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }

        @media (max-width: 600px) {

            .grid-2,
            .grid-3 {
                grid-template-columns: 1fr;
            }
        }

        #upload-status.success {
            color: #059669;
            font-weight: 600;
        }

        #upload-status.error {
            color: #DC2626;
            font-weight: 600;
        }

        /* ---- Uploaded Docs on Dashboard ---- */
        .docs-outer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .docs-category-card {
            background: var(--white);
            border-radius: var(--border-radius);
            border: 1px solid var(--border-color);
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.03);
            transition: var(--transition);
        }

        .docs-category-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.06);
        }

        .docs-cat-header {
            padding: 0.9rem 1.25rem;
            font-weight: 700;
            font-size: 0.95rem;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .docs-file-list {
            padding: 0.75rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .doc-file-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.65rem 0.75rem;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            text-decoration: none;
            color: var(--text-dark);
            transition: var(--transition);
            background: #FAFAFA;
        }

        .doc-file-item:hover {
            background: var(--secondary-color);
            border-color: var(--primary-color);
        }

        .doc-file-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .doc-file-info {
            flex: 1;
            overflow: hidden;
        }

        .doc-file-name {
            display: block;
            font-weight: 600;
            font-size: 0.88rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .doc-file-date {
            display: block;
            font-size: 0.78rem;
            color: var(--text-light);
        }

        .doc-dl-icon {
            color: var(--primary-color);
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        /* ---- New Categorical Document Upload UI ---- */
        .cat-items-list {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .cat-item-row {
            display: grid;
            grid-template-columns: 1.5fr 1fr 1fr 40px;
            align-items: end;
            gap: 1.25rem;
            padding: 1.25rem;
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
            animation: fadeIn 0.3s ease;
        }

        .cat-field-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .cat-field-group label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #64748b;
        }

        .cat-field-group input {
            padding: 0.65rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.9rem;
            background: #ffffff;
            outline: none;
            transition: border-color 0.2s;
        }

        .cat-field-group input:focus {
            border-color: #3b82f6;
        }

        /* Choose file styling */
        .file-choose-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 0.35rem 0.5rem;
            cursor: pointer;
        }

        .file-choose-wrapper i {
            color: #64748b;
            margin-right: 0.5rem;
        }

        .file-choose-wrapper span {
            font-size: 0.9rem;
            font-weight: 600;
            color: #1e293b;
        }

        .real-file-input {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
        }

        .btn-remove-item {
            background: none;
            border: none;
            color: #94a3b8;
            font-size: 1.25rem;
            cursor: pointer;
            padding: 5px;
            transition: color 0.2s;
        }

        .btn-remove-item:hover {
            color: #ef4444;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(5px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 900px) {
            .cat-item-row {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
        }
    </style>

    <script>
        function openUpdateModal() {
            document.getElementById('updateModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeUpdateModal() {
            document.getElementById('updateModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        /* ---- Profile save (FormData with files) ---- */
        document.getElementById('updateProfileForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            const btn = this.querySelector('.btn-save');
            const originalBtnHtml = btn.innerHTML;
            
            btn.disabled = true;
            btn.innerHTML = '<i class="ri-loader-4-line"></i> Saving...';

            fetch('update_patient_profile.php', {
                method: 'POST',
                body: formData
            })
                .then(r => r.json())
                .then(result => {
                    btn.disabled = false;
                    btn.innerHTML = originalBtnHtml;
                    if (result.success) {
                        alert(result.message);
                        window.location.reload();
                    } else {
                        alert('Error: ' + result.message);
                    }
                })
                .catch(() => {
                    btn.disabled = false;
                    btn.innerHTML = originalBtnHtml;
                    alert('An error occurred while saving.');
                });
        });
        /* ---- Categorical Counter & Dynamic Inputs Logic ---- */
        function updateCatInputs(cat) {
            const count = parseInt(document.getElementById(cat + '-count').value) || 0;
            const container = document.getElementById(cat + '-container');
            const currentItems = container.querySelectorAll('.cat-item-row').length;

            if (count > currentItems) {
                // Add rows
                for (let i = currentItems + 1; i <= count; i++) {
                    const row = document.createElement('div');
                    row.className = 'cat-item-row';
                    row.innerHTML = `
                        <div class="cat-field-group">
                            <label>Document Title</label>
                            <input type="text" name="${cat}_title[]" placeholder="e.g. Blood Test Report">
                        </div>
                        <div class="cat-field-group">
                            <label>Date</label>
                            <input type="date" name="${cat}_date[]">
                        </div>
                        <div class="cat-field-group">
                            <label>File</label>
                            <div class="file-choose-wrapper">
                                <i class="ri-upload-2-line"></i>
                                <span class="file-chosen-name">Choose</span>
                                <input type="file" name="${cat}[]" class="real-file-input" accept=".pdf,.jpg,.jpeg,.png" onchange="updateFileName(this)">
                            </div>
                        </div>
                        <button type="button" class="btn-remove-item" onclick="removeItem(this, '${cat}')">
                            <i class="ri-delete-bin-line"></i>
                        </button>
                    `;
                    container.appendChild(row);
                }
            } else if (count < currentItems) {
                // Remove rows from bottom
                const rows = container.querySelectorAll('.cat-item-row');
                for (let i = currentItems - 1; i >= count; i--) {
                    rows[i].remove();
                }
            }
        }

        function updateFileName(input) {
            const nameSpan = input.parentElement.querySelector('.file-chosen-name');
            if (input.files.length > 0) {
                nameSpan.textContent = input.files[0].name;
            } else {
                nameSpan.textContent = 'Choose';
            }
        }

        function removeItem(btn, cat) {
            btn.parentElement.remove();
            const counter = document.getElementById(cat + '-count');
            counter.value = parseInt(counter.value) - 1;
        }

        /* Initialize categories (start with 0 or 1 as needed) */
        document.addEventListener('DOMContentLoaded', () => {
            // Defaulting to 1 for better visibility if desired, but 0 matches "initial count" requirement
            // updateCatInputs('medical_reports');
            // updateCatInputs('scans');
            // updateCatInputs('prescriptions');
        });



        /* Close on outside click */
        document.getElementById('updateModal').addEventListener('click', function (e) {
            if (e.target === this) closeUpdateModal();
        });

    </script>





    <style>
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>

</body>

</html>