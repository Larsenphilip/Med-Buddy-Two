<?php
session_start();
include 'db_config.php';

// Fetch unique hospitals/clinics for filter
$hospitals = [];
$sql_hosp = "SELECT DISTINCT CASE WHEN work_type = 'Clinic' THEN clinic_name ELSE hospital_name END as place FROM doctors";
$res_hosp = $conn->query($sql_hosp);
if($res_hosp) {
    while($r = $res_hosp->fetch_assoc()) {
        if(!empty($r['place'])) {
            $hospitals[] = $r['place'];
        }
    }
}

// Fetch all doctors ordered by specialization
$sql = "SELECT * FROM doctors ORDER BY specialization ASC, name ASC";
$result = $conn->query($sql);

$doctors_by_spec = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // Group by specialization
        $spec = !empty($row['specialization']) ? $row['specialization'] : 'General';
        $doctors_by_spec[$spec][] = $row;
    }
}

// Get unique specializations for filter
$specializations = array_keys($doctors_by_spec);
sort($specializations);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
        content="Book Doctor Appointments - Med Buddy. Select from our top specialists across various departments.">
    <title>Book Appointment - Med Buddy</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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

            /* Specific for Appointment Cards */
            --card-header-bg: #EAEAEA;
            --btn-yellow: #0066CC;
            --btn-yellow-hover: #0066CC;
            
            --container-padding: 5%;
            --border-radius: 12px;
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
            scroll-behavior: smooth;
        }

        body {
            background-color: #F9FAFB;
            color: var(--text-dark);
            -webkit-font-smoothing: antialiased;
        }

        /* --- Header (Responsive) --- */
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.25rem var(--container-padding);
            background-color: var(--white);
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
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
        }

        .logo span {
            color: var(--text-dark);
        }

        nav ul {
            display: flex;
            list-style: none;
            gap: 2.5rem;
        }

        nav a {
            text-decoration: none;
            color: var(--text-dark);
            font-weight: 500;
            font-size: 1rem;
            transition: var(--transition);
        }

        /* --- Authentication Modal --- */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(8px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .modal-overlay.active {
            display: flex;
            opacity: 1;
        }

        .auth-modal {
            background: var(--white);
            width: 90%;
            max-width: 450px;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            transform: translateY(20px);
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-overlay.active .auth-modal {
            transform: translateY(0);
        }

        .modal-close {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 1.5rem;
            color: var(--text-light);
            cursor: pointer;
            background: none;
            border: none;
            z-index: 10;
            transition: color 0.3s;
        }

        .modal-close:hover {
            color: var(--text-dark);
        }

        .auth-tabs {
            display: flex;
            background: #f8f9fa;
            border-bottom: 1px solid var(--border-color);
        }

        .auth-tab {
            flex: 1;
            padding: 1.25rem;
            font-weight: 600;
            color: var(--text-light);
            background: none;
            border: none;
            cursor: pointer;
            transition: var(--transition);
            font-size: 1rem;
            position: relative;
        }

        .auth-tab.active {
            color: var(--primary-color);
            background: var(--white);
        }

        .auth-tab.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: var(--primary-color);
        }

        .auth-body {
            padding: 2rem;
        }

        .auth-form {
            display: none;
        }

        .auth-form.active {
            display: block;
            animation: fadeIn 0.4s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            color: var(--text-dark);
            font-weight: 600;
        }

        .form-control {
            width: 100%;
            padding: 0.85rem 1rem;
            border: 1px solid var(--border-color);
            background: #F9FAFB;
            border-radius: 8px;
            font-size: 1rem;
            transition: var(--transition);
            outline: none;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            background: var(--white);
            box-shadow: 0 0 0 4px rgba(0, 102, 204, 0.1);
        }

        .form-control.error {
            border-color: #ef4444;
            background: #fff5f5;
        }

        .error-msg {
            color: #ef4444;
            font-size: 0.8rem;
            margin-top: 0.35rem;
            display: none;
        }

        .btn-full {
            width: 100%;
            margin-top: 1rem;
            justify-content: center;
        }

        .form-footer {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.9rem;
            color: var(--text-light);
        }

        .form-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }

        .form-footer a:hover {
            text-decoration: underline;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: var(--white);
            border: 2px solid var(--primary-color);
            box-shadow: 0 4px 6px rgba(0, 102, 204, 0.2);
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius);
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 102, 204, 0.3);
        }

        .auth-buttons {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .btn-text {
            color: var(--primary-color);
            background: transparent;
            font-weight: 600;
            padding: 0.75rem 1rem;
            text-decoration: none;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }


        /* Mobile Header */
        @media (max-width: 768px) {
            header {
                flex-direction: column;
                padding: 1rem;
                gap: 1rem;
            }
            
            nav ul {
                gap: 1rem;
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .auth-buttons {
                width: 100%;
                justify-content: center;
            }
        }

        /* --- Page Layout --- */
        .page-header {
            background: linear-gradient(135deg, #F8FAFC 0%, var(--secondary-color) 100%);
            padding: 2rem 5%;
            margin-bottom: 2rem;
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            gap: 2rem;
            min-height: 250px;
        }

        .filter-container {
            /* Positioned in the first column by default */
            grid-column: 1;
            z-index: 10;
            width: 100%;
            display: flex;
            justify-content: flex-start;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .header-text {
            grid-column: 2;
            text-align: center;
            max-width: 800px;
            width: 100%;
        }

        /* Custom Dropdown Styles */
        .custom-dropdown {
            position: relative;
            min-width: 240px;
            font-family: 'Inter', sans-serif;
        }

        .dropdown-selected {
            background-color: var(--white);
            color: var(--text-dark);
            padding: 0.8rem 1.5rem;
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 50px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            font-weight: 500;
            font-size: 0.95rem;
        }

        .dropdown-selected:hover {
            box-shadow: 0 6px 20px rgba(0, 102, 204, 0.15);
            transform: translateY(-2px);
            border-color: var(--primary-color);
        }

        .dropdown-selected::after {
            content: "";
            width: 1rem;
            height: 1rem;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%230066CC' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-size: contain;
            transition: transform 0.3s ease;
        }

        .custom-dropdown.open .dropdown-selected::after {
            transform: rotate(180deg);
        }

        .dropdown-options {
            position: absolute;
            top: 120%;
            left: 0;
            right: 0;
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 0.5rem 0;
            display: none;
            z-index: 100;
            animation: fadeIn 0.2s ease;
            max-height: 300px;
            overflow-y: auto;
        }

        .custom-dropdown.open .dropdown-options {
            display: block;
        }

        .dropdown-option {
            padding: 0.75rem 1.5rem;
            cursor: pointer;
            transition: background 0.2s;
            color: var(--text-dark);
            font-size: 0.95rem;
        }

        .dropdown-option:hover {
            background-color: var(--secondary-color);
            color: var(--primary-color);
        }
        
        .dropdown-option.selected {
            background-color: var(--primary-color);
            color: white;
        }

        /* Responsive Adjustments for Filter */
        @media (max-width: 1024px) {
            .page-header {
                display: flex;
                flex-direction: column;
                gap: 1.5rem;
                padding-top: 3rem;
                padding-bottom: 3rem;
                text-align: center;
            }
            .filter-container {
                position: relative;
                width: 100%;
                order: 2; /* Filter below text */
                justify-content: center;
            }
            .header-text {
                grid-column: auto;
                text-align: center;
                order: 1; /* Text on top */
            }
            .custom-dropdown {
                width: 100%;
                max-width: 320px;
                margin: 0 auto;
            }

            .dropdown-selected {
                padding: 0.6rem 1rem;
                font-size: 0.9rem;
            }
        }

        .page-header h1 {
            font-size: 3rem;
            font-weight: 800;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .page-header p {
            color: var(--text-light);
            font-size: 1.1rem;
        }

        /* --- Doctor Sections --- */
        .department-section {
            padding: 3rem var(--container-padding);
            max-width: 1400px;
            margin: 0 auto;
        }

        .section-title {
            font-size: 2rem;
            color: #003366; /* Dark Navy from image reference style */
            font-weight: 700;
            margin-bottom: 2rem;
            border-left: 5px solid var(--btn-yellow);
            padding-left: 1rem;
        }

        .doctors-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
        }

        /* --- Doctor Card Premium Design --- */
        .doctor-card {
            background: var(--white);
            border: none;
            border-radius: 20px;
            overflow: visible;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: flex;
            flex-direction: column;
            text-align: center;
            box-shadow: 0 10px 30px -10px rgba(0,0,0,0.05);
            position: relative;
        }

        .card-image-container {
            background: linear-gradient(to bottom, var(--secondary-color), transparent);
            padding: 2.5rem 1rem 1.5rem;
            border-radius: 20px 20px 0 0;
            position: relative;
        }

        .doctor-img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--white);
            box-shadow: 0 8px 16px rgba(0,0,0,0.08);
            margin: 0 auto;
            background-color: #fff;
        }
        
        .doctor-img-placeholder {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background-color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3.5rem;
            border: 4px solid var(--white);
            box-shadow: 0 8px 16px rgba(0,0,0,0.08);
            margin: 0 auto;
        }

        .card-content {
            padding: 2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            flex-grow: 1;
        }

        .doctor-name {
            font-size: 1.4rem;
            font-weight: 800;
            margin-bottom: 0.25rem;
            letter-spacing: -0.5px;
            color: var(--text-dark);
        }

        .doctor-designation {
            font-size: 0.9rem;
            color: var(--primary-color);
            margin-bottom: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .doctor-department {
            font-size: 0.95rem;
            margin-bottom: 2rem;
            background: #f3f4f6;
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            color: var(--text-dark);
        }

        .btn-request {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
            color: #FFFFFF;
            border: none;
            padding: 1rem 1.5rem;
            font-weight: 600;
            font-size: 1rem;
            border-radius: 12px;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 102, 204, 0.2);
            margin-top: auto;
        }
        
        .btn-request:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 102, 204, 0.3);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1001;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background-color: var(--white);
            margin: 2vh auto; 
            padding: 1.5rem; 
            border: none;
            width: 95%;
            max-width: 500px;
            max-height: 95vh;
            overflow-y: auto; 
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            animation: modalFadeIn 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            position: relative;
        }
        .modal-content::-webkit-scrollbar { display: none; }

        @keyframes modalFadeIn {
            from {opacity: 0; transform: translateY(20px);}
            to {opacity: 1; transform: translateY(0);}
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        .form-group {
            margin-bottom: 0.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
            font-weight: 500;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 1rem;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.1);
        }

        .submit-btn {
            background-color: var(--primary-color);
            color: white;
            padding: 1rem;
            border: none;
            width: 100%;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }

        .submit-btn:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        /* --- Alarm Clock Style Picker --- */
        .picker-wrapper {
            margin-bottom: 1rem;
        }
        
        .picker-labels {
            display: flex;
            gap: 1rem;
            margin-bottom: 0.5rem;
        }
        
        .picker-label {
            flex: 1;
            text-align: center;
            font-size: 0.85rem;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .picker-container {
            display: flex;
            justify-content: space-between;
            height: 140px; 
            background: #f8fafc;
            border-radius: 12px;
            position: relative;
            overflow: hidden;
            border: 1px solid var(--border-color);
            mask-image: linear-gradient(to bottom, transparent, black 20%, black 80%, transparent);
            -webkit-mask-image: linear-gradient(to bottom, transparent, black 20%, black 80%, transparent);
        }

        .picker-column {
            flex: 1;
            overflow-y: scroll;
            scroll-snap-type: y mandatory;
            text-align: center;
            padding-top: 50px; 
            padding-bottom: 50px;
            scrollbar-width: none;
            position: relative;
            z-index: 2;
        }
        .picker-column::-webkit-scrollbar { display: none; }

        .picker-item {
            height: 40px;
            line-height: 40px;
            scroll-snap-align: center;
            font-size: 1rem;
            color: var(--text-light);
            cursor: pointer;
            transition: all 0.2s ease;
            user-select: none;
        }

        .picker-item.active-item {
            color: var(--primary-color);
            font-weight: 700;
            font-size: 1.15rem;
            transform: scale(1.1);
        }

        .picker-highlight {
            position: absolute;
            top: 50%;
            left: 10px;
            right: 10px;
            height: 40px;
            transform: translateY(-50%);
            border-top: 1px solid var(--primary-color);
            border-bottom: 1px solid var(--primary-color);
            background-color: rgba(0, 102, 204, 0.05);
            border-radius: 8px;
            pointer-events: none;
            z-index: 1;
        }
    </style>
</head>

<body>

    <!-- Header Navigation -->
    <header>
        <a href="index.html" class="logo">
            <span>Med Buddy</span>
        </a>

        <nav>
            <ul>
                <li><a href="index.html#home">Home</a></li>
                 <li><a href="#about">About</a></li>
                <li><a href="appointment.php">Appointment</a></li>
                <li><a href="index.html#contact">Contact</a></li>
            </ul>
        </nav>

        <div class="auth-buttons">
            <a href="javascript:void(0)" onclick="openModal('login')" class="btn-text">Login</a>
        </div>
    </header>

    <!-- Page Title & Filter -->
    <div class="page-header">
        <div class="filter-container">
            <!-- Hospital Filter -->
             <div class="custom-dropdown" id="hospitalDropdown">
                <input type="hidden" id="hospitalFilter" value="all">
                <div class="dropdown-selected" onclick="toggleDropdown('hospitalDropdown')">All Hospitals / Clinics</div>
                <div class="dropdown-options">
                    <div class="dropdown-option selected" onclick="selectFilter('hospital', 'all', 'All Hospitals / Clinics')">All Hospitals / Clinics</div>
                    <?php foreach($hospitals as $hosp): ?>
                        <div class="dropdown-option" onclick="selectFilter('hospital', '<?php echo htmlspecialchars($hosp); ?>', '<?php echo htmlspecialchars($hosp); ?>')">
                            <?php echo htmlspecialchars($hosp); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Specialization Filter -->
            <div class="custom-dropdown" id="specDropdown">
                <input type="hidden" id="specFilter" value="all">
                <div class="dropdown-selected" onclick="toggleDropdown('specDropdown')">All Specializations</div>
                <div class="dropdown-options">
                    <div class="dropdown-option selected" onclick="selectFilter('spec', 'all', 'All Specializations')">All Specializations</div>
                    <?php foreach($specializations as $s): ?>
                        <div class="dropdown-option" onclick="selectFilter('spec', '<?php echo htmlspecialchars($s); ?>', '<?php echo htmlspecialchars($s); ?>')">
                            <?php echo htmlspecialchars($s); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="header-text">
            <h1>Book an Appointment</h1>
            <p>Choose from our specialized doctors and book your slot today.</p>
        </div>
    </div>

    <?php if(empty($doctors_by_spec)): ?>
        <section class="department-section">
            <p style="text-align: center; color: #666; font-size: 1.2rem;">No doctors currently available.</p>
        </section>
    <?php else: ?>
        <?php foreach($doctors_by_spec as $spec => $doctors): ?>
        <section class="department-section" data-specialization="<?php echo htmlspecialchars($spec); ?>">
            <h2 class="section-title"><?php echo htmlspecialchars($spec); ?></h2>
            <div class="doctors-grid">
                <?php foreach($doctors as $doc): 
                    $place = ($doc['work_type'] == 'Clinic') ? ($doc['clinic_name'] ?? '') : ($doc['hospital_name'] ?? '');
                    $designation = ($doc['work_type'] == 'Clinic') ? 'General Practitioner' : ($doc['designation'] ?? 'Consultant');
                    $image_src = !empty($doc['image_path']) ? $doc['image_path'] : '';
                ?>
                <div class="doctor-card" data-hospital="<?php echo htmlspecialchars($place ?? ''); ?>">
                    <div class="card-image-container">
                        <?php if($image_src && file_exists($image_src)): ?>
                            <img src="<?php echo htmlspecialchars($image_src); ?>" alt="Doctor" class="doctor-img">
                        <?php else: ?>
                            <div class="doctor-img-placeholder">👨‍⚕️</div>
                        <?php endif; ?>
                    </div>
                    <div class="card-content">
                        <h3 class="doctor-name"><?php echo htmlspecialchars($doc['name']); ?></h3>
                        <p class="doctor-designation"><?php echo htmlspecialchars($designation); ?></p>
                        <p class="doctor-department"><?php echo htmlspecialchars($doc['specialization']); ?></p>
                        <button class="btn-request" onclick="openBookingModal(<?php echo $doc['id']; ?>, '<?php echo htmlspecialchars(addslashes($doc['name'])); ?>')">Request Appointment</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Booking Modal -->
    <div id="bookingModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeBookingModal()">&times;</span>
            <h2 style="margin-bottom: 1.5rem; color: var(--primary-color);">Book Appointment</h2>
            <p id="modalDeviceName" style="margin-bottom: 1.5rem; font-weight: 600;"></p>
            
            <form id="bookingForm">
                <input type="hidden" id="doctorId" name="doctor_id">
                
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" required>
                </div>
         
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required>
                </div>

               <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" required>
                </div>
    
                <div class="picker-wrapper">
                    <div class="picker-labels">
                        <div class="picker-label">Date</div>
                        <div class="picker-label">Time</div>
                    </div>
                    <div class="picker-container">
                        <div class="picker-highlight"></div>
                        <div class="picker-column" id="dateColumn">
                            <!-- Dates populated by JS -->
                        </div>
                        <div class="picker-column" id="timeColumn">
                            <div class="picker-item">Select Date</div>
                        </div>
                    </div>
                    
                    <input type="hidden" id="date" name="date" required>
                    <input type="hidden" id="selectedTime" name="time" required>
                    <p id="availability-message" style="text-align: center; color: red;"></p>
                </div>

                <button type="submit" class="submit-btn" id="submitBtn" disabled>Confirm Booking</button>
            </form>
        </div>
    </div>

     <!-- Authentication Modal -->
     <div class="modal-overlay" id="authModal">
         <div class="auth-modal">
             <button class="modal-close" onclick="closeModal()">&times;</button>
 
             <div class="auth-tabs">
                 <button class="auth-tab active" id="tab-login" onclick="switchTab('login')">Patient Login</button>
                 <button class="auth-tab" id="tab-register" onclick="switchTab('register')">Register</button>
             </div>
 
             <div class="auth-body">
                 <!-- Login Form -->
                 <div class="auth-form active" id="form-login">
                     <form id="loginFormTag">
                         <div class="form-group">
                             <label for="login-email">Email or Patient ID</label>
                             <input type="text" id="login-email" class="form-control"
                                 placeholder="Enter your email or ID" required>
                             <div class="error-msg"></div>
                         </div>
 
                         <div class="form-group">
                             <label for="login-pass">Password</label>
                             <input type="password" id="login-pass" class="form-control"
                                 placeholder="Enter your password" required>
                             <div class="error-msg"></div>
                         </div>
 
                         <button type="submit" class="btn btn-primary btn-full">Login to Dashboard</button>
 
                         <div class="form-footer">
                             <p>Don't have an account? <a href="#" onclick="switchTab('register')">Register here</a></p>
                         </div>
                     </form>
                 </div>
 
                 <!-- Register Form -->
                 <div class="auth-form" id="form-register">
                     <form id="registerFormTag">
                         <div class="form-group">
                             <label for="reg-email">Email Address</label>
                             <input type="email" id="reg-email" class="form-control" placeholder="your@email.com"
                                 required>
                             <div class="error-msg"></div>
                         </div>
 
                         <div class="form-group">
                             <label for="reg-phone">Phone Number</label>
                             <input type="tel" id="reg-phone" class="form-control" placeholder="9876543210" required>
                             <div class="error-msg"></div>
                         </div>
 
                         <div class="form-group">
                             <label for="reg-pass">Password</label>
                             <input type="password" id="reg-pass" class="form-control" placeholder="Create a password"
                                 required>
                             <div class="error-msg"></div>
                         </div>
 
                         <div class="form-group">
                             <label for="reg-confirm-pass">Re-enter Password</label>
                             <input type="password" id="reg-confirm-pass" class="form-control"
                                 placeholder="Confirm your password" required>
                             <div class="error-msg"></div>
                         </div>
 
                         <button type="submit" class="btn btn-primary btn-full">Create Account</button>
 
                         <div class="form-footer">
                             <p>Already have an account? <a href="#" onclick="switchTab('login')">Login here</a></p>
                         </div>
                     </form>
                 </div>
 
             </div>
         </div>
     </div>
     </div>
 
     <script>
         // Modal Logic
         // Modal Logic
         const modalOverlay = document.getElementById('authModal');
         const loginTab = document.getElementById('tab-login');
         const registerTab = document.getElementById('tab-register');
 
         const loginForm = document.getElementById('form-login');
         const registerForm = document.getElementById('form-register');
 
 
         function openModal(type) {
             modalOverlay.classList.add('active');
             document.body.style.overflow = 'hidden';
             if (type === 'register') {
                 switchTab('register');
             } else {
                 switchTab('login');
             }
         }
 
 
         function closeModal() {
             modalOverlay.classList.remove('active');
             document.body.style.overflow = '';
             // Reset patient forms
             document.getElementById('loginFormTag').reset();
             document.getElementById('registerFormTag').reset();
             clearAllErrors();
         }
 
 
         function switchTab(tab) {
             // Remove active class from all
             loginTab.classList.remove('active');
             registerTab.classList.remove('active');
 
             loginForm.classList.remove('active');
             registerForm.classList.remove('active');
 
             // Add active class to selected
             if (tab === 'login') {
                 loginTab.classList.add('active');
                 loginForm.classList.add('active');
             } else if (tab === 'register') {
                 registerTab.classList.add('active');
                 registerForm.classList.add('active');
             }
         }
 
         function clearAllErrors() {
             document.querySelectorAll('.error-msg').forEach(el => el.style.display = 'none');
             document.querySelectorAll('.form-control').forEach(el => el.classList.remove('error'));
         }
 
         // Close on outside click
         modalOverlay.addEventListener('click', (e) => {
             if (e.target === modalOverlay) closeModal();
         });
 
 
         // Error helper
         function showError(input, message) {
             const formGroup = input.closest('.form-group');
             input.classList.add('error');
             const errorDiv = formGroup.querySelector('.error-msg');
             errorDiv.innerText = message;
             errorDiv.style.display = 'block';
         }
 
         function clearErrors() {
             document.querySelectorAll('.error-msg').style.display = 'none';
             document.querySelectorAll('.form-control').classList.remove('error');
         }
 
         // Generate Unique Patient ID
         function generatePatientID() {
             const year = new Date().getFullYear();
             const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
             const nums = '0123456789';
 
             // Generate 3 random letters
             let randomChars = '';
             for (let i = 0; i < 3; i++) {
                 randomChars += chars.charAt(Math.floor(Math.random() * chars.length));
             }
 
             // Generate 3 random numbers
             let randomNums = '';
             for (let i = 0; i < 3; i++) {
                 randomNums += nums.charAt(Math.floor(Math.random() * nums.length));
             }
 
             // Mix them
             let combined = (randomChars + randomNums).split('');
             // Fisher-Yates shuffle
             for (let i = combined.length - 1; i > 0; i--) {
                 const j = Math.floor(Math.random() * (i + 1));
                 [combined[i], combined[j]] = [combined[j], combined[i]];
             }
 
             return `PAT-${year}-${combined.join('')}`;
         }
 
         // Registration Handler
         document.getElementById('registerFormTag').addEventListener('submit', function (e) {
             e.preventDefault();
 
             const emailInput = document.getElementById('reg-email');
             const phoneInput = document.getElementById('reg-phone');
             const passInput = document.getElementById('reg-pass');
             const confirmPassInput = document.getElementById('reg-confirm-pass');
 
             // Clear previous errors
             document.querySelectorAll('.error-msg').forEach(el => el.style.display = 'none');
             document.querySelectorAll('.form-control').forEach(el => el.classList.remove('error'));
 
             let isValid = true;
 
             // Email Validation
             const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
             if (!emailRegex.test(emailInput.value.trim())) {
                 showError(emailInput, 'Please enter a valid email address.');
                 isValid = false;
             }
 
             // Phone Validation (basic 10 digit)
             const phone = phoneInput.value.replace(/[^0-9]/g, '');
             if (phone.length < 10) {
                 showError(phoneInput, 'Please enter a valid phone number (at least 10 digits).');
                 isValid = false;
             }
 
             // Password Validation
             if (passInput.value.length < 6) {
                 showError(passInput, 'Password must be at least 6 characters.');
                 isValid = false;
             }
 
             // Match Validation
             if (passInput.value !== confirmPassInput.value) {
                 showError(confirmPassInput, 'Passwords do not match.');
                 isValid = false;
             }
 
             if (isValid) {
                 const patientID = generatePatientID();
                 const formData = {
                     email: emailInput.value.trim(),
                     phone: phone,
                     password: passInput.value,
                     patientID: patientID
                 };
 
                 fetch('register_patient.php', {
                     method: 'POST',
                     headers: {
                         'Content-Type': 'application/json'
                     },
                     body: JSON.stringify(formData)
                 })
                     .then(response => response.json())
                     .then(data => {
                         if (data.success) {
                             alert(`Registration Successful!\n\nYour Unique Patient ID: ${patientID}\n\nPlease save this ID for future login.`);
                             // Switch to Login
                             switchTab('login');
                             document.getElementById('login-email').value = formData.email; // Auto-fill
                             document.getElementById('registerFormTag').reset();
                         } else {
                             showError(emailInput, data.message || 'Registration failed.');
                         }
                     })
                     .catch(error => {
                         console.error('Error:', error);
                         showError(emailInput, 'An error occurred. Please try again.');
                     });
             }
         });
 
         // Login Handler
         document.getElementById('loginFormTag').addEventListener('submit', function (e) {
             e.preventDefault();
 
             const loginInput = document.getElementById('login-email');
             const passInput = document.getElementById('login-pass');
 
             const loginVal = loginInput.value.trim();
             const passVal = passInput.value;
 
             fetch('login_patient.php', {
                 method: 'POST',
                 headers: {
                     'Content-Type': 'application/json'
                 },
                 body: JSON.stringify({
                     loginId: loginVal,
                     password: passVal
                 })
             })
                 .then(response => response.json())
                 .then(data => {
                     if (data.success) {
                         window.location.href = 'patients.php';
                     } else {
                         showError(passInput, data.message || 'Invalid email/ID or password.');
                         loginInput.classList.add('error');
                     }
                 })
                 .catch(error => {
                     console.error('Error:', error);
                     showError(passInput, 'An error occurred. Please try again.');
                 });
         });
 
     </script>
     <script>
        const modal = document.getElementById("bookingModal");
        const form = document.getElementById("bookingForm");
        const submitBtn = document.getElementById("submitBtn");

        /* Custom Dropdown Logic */
        function toggleDropdown(id) {
            // Close others
            const allDropdowns = document.querySelectorAll('.custom-dropdown');
            allDropdowns.forEach(d => {
                if(d.id !== id) d.classList.remove('open');
            });
            document.getElementById(id).classList.toggle('open');
        }

        function selectFilter(type, value, text) {
            const dropdownId = type === 'hospital' ? 'hospitalDropdown' : 'specDropdown';
            const inputId = type === 'hospital' ? 'hospitalFilter' : 'specFilter';
            
            document.getElementById(inputId).value = value;
            document.querySelector(`#${dropdownId} .dropdown-selected`).innerText = text;
            document.getElementById(dropdownId).classList.remove('open');
            
            // Re-render UI highlight
            document.querySelectorAll(`#${dropdownId} .dropdown-option`).forEach(opt => {
                opt.classList.remove('selected');
                if(opt.innerText === text) opt.classList.add('selected');
            });
            
            filterDoctors();
        }

        function filterDoctors() {
            const selectedHospital = document.getElementById("hospitalFilter").value;
            const selectedSpec = document.getElementById("specFilter").value;
            
            const doctorCards = document.querySelectorAll(".doctor-card");
            const sections = document.querySelectorAll(".department-section");

            doctorCards.forEach(card => {
                const hospitalMatch = (selectedHospital === "all" || card.dataset.hospital === selectedHospital);
                // For card visibility, we mostly care if it matches the hospital filter
                // However, we also need to respect specialization if we want to hide individual cards in a section
                // But typically, the section itself represents the specialization.
                // Since this page is grouped by Specialization, filtering by Spec effectively hides entire Sections.
                
                if (hospitalMatch) {
                    card.style.display = "flex";
                } else {
                    card.style.display = "none";
                }
            });

            // Hide sections based on Spec Filter AND if they contain visible cards
            sections.forEach(section => {
                const specMatch = (selectedSpec === "all" || section.dataset.specialization === selectedSpec);
                
                let hasVisibleCards = false;
                 section.querySelectorAll(".doctor-card").forEach(c => {
                     if(c.style.display !== 'none') hasVisibleCards = true;
                 });
                 
                 if(hasVisibleCards && specMatch) {
                     section.style.display = "block";
                 } else {
                     section.style.display = "none";
                 }
            });
        }

        function openBookingModal(id, name) {
            document.getElementById("doctorId").value = id;
            document.getElementById("modalDeviceName").innerText = "Booking with " + name;
            modal.style.display = "block";
            document.body.style.overflow = 'hidden'; 
            
            // Allow modal to render then init wheel
            setTimeout(() => {
                initDateWheel();
            }, 100);
            
            form.reset();
            submitBtn.disabled = true;
        }

        function closeBookingModal() {
            modal.style.display = "none";
            document.body.style.overflow = '';
        }

        /* --- Wheel Picker Logic --- */
        function initDateWheel() {
            const dateColumn = document.getElementById('dateColumn');
            dateColumn.innerHTML = '';
            const today = new Date();
            let firstDate = '';

            // Generate next 30 days
            for(let i=0; i<30; i++) {
                const d = new Date(today);
                d.setDate(today.getDate() + i);
                
                const dateStr = d.toISOString().split('T')[0];
                const displayStr = d.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
                
                if(i===0) firstDate = dateStr;

                const div = document.createElement('div');
                div.className = 'picker-item';
                div.innerText = displayStr;
                div.dataset.value = dateStr;
                div.onclick = function() { scrollToItem(dateColumn, this); };
                dateColumn.appendChild(div);
            }
            
            // Init Scroll Listener for Date
            setupWheelListener(dateColumn, (selectedDate) => {
                document.getElementById('date').value = selectedDate;
                checkAvailabilityWheel();
            });

            // Init Scroll Listener for Time
            setupWheelListener(document.getElementById('timeColumn'), (selectedTime) => {
                document.getElementById('selectedTime').value = selectedTime;
                submitBtn.disabled = !selectedTime || selectedTime === 'No Slots';
            });

            // Set initial date
            document.getElementById('date').value = firstDate;
            // Force active state visual
            updateActiveItem(dateColumn);
            // Load slots for today
            checkAvailabilityWheel();
        }

        function setupWheelListener(container, callback) {
            container.onscroll = () => {
                clearTimeout(container.scrollTimer);
                container.scrollTimer = setTimeout(() => {
                    const activeItem = updateActiveItem(container);
                    if(activeItem && activeItem.dataset.value) {
                         callback(activeItem.dataset.value);
                    }
                }, 100);
            };
        }

        function scrollToItem(container, item) {
            const top = item.offsetTop - container.offsetTop - (container.clientHeight/2) + (item.clientHeight/2);
            container.scrollTo({ top: top, behavior: 'smooth' });
        }

        function updateActiveItem(container) {
            const center = container.scrollTop + (container.clientHeight / 2);
            let closestInfo = { dist: Infinity, el: null };
            
            Array.from(container.children).forEach(child => {
                const childCenter = child.offsetTop - container.offsetTop + (child.clientHeight / 2);
                const dist = Math.abs(center - childCenter);
                child.classList.remove('active-item');
                if(dist < closestInfo.dist) {
                    closestInfo = { dist: dist, el: child };
                }
            });
            
            if(closestInfo.el) {
                closestInfo.el.classList.add('active-item');
            }
            return closestInfo.el;
        }

        function checkAvailabilityWheel() {
            const doctorId = document.getElementById("doctorId").value;
            const date = document.getElementById("date").value;
            const timeColumn = document.getElementById("timeColumn");
            
            if (!date) return;

            fetch(`get_slots.php?doctor_id=${doctorId}&date=${date}&t=${new Date().getTime()}`)
                .then(response => response.json())
                .then(data => {
                    timeColumn.innerHTML = '';
                    if (data.success && data.slots.length > 0) {
                        data.slots.forEach(slot => {
                            const div = document.createElement('div');
                            div.className = 'picker-item';
                            div.innerText = slot;
                            div.dataset.value = slot;
                            div.onclick = function() { scrollToItem(timeColumn, this); };
                            timeColumn.appendChild(div);
                        });
                        
                        // Select first slot by default to avoid empty state
                        updateActiveItem(timeColumn); 
                        const firstSlot = timeColumn.querySelector('.picker-item');
                        if(firstSlot) {
                            document.getElementById('selectedTime').value = firstSlot.dataset.value;
                            submitBtn.disabled = false;
                        }

                        document.getElementById("availability-message").innerText = "";
                    } else {
                        timeColumn.innerHTML = '<div class="picker-item" style="color:red">No Slots</div>';
                         document.getElementById('selectedTime').value = "";
                         submitBtn.disabled = true;
                        document.getElementById("availability-message").innerText = "No slots available for this date.";
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    timeColumn.innerHTML = '<div class="picker-item">Error</div>';
                });
        }

        /* --- Click Outside Logic --- */
        window.onclick = function(event) {
            // Dropdown close logic
            if (!event.target.matches('.dropdown-selected') && !event.target.matches('.dropdown-selected *')) {
                var dropdowns = document.getElementsByClassName("custom-dropdown");
                for (var i = 0; i < dropdowns.length; i++) {
                    var openDropdown = dropdowns[i];
                    if (openDropdown.classList.contains('open')) {
                        openDropdown.classList.remove('open');
                    }
                }
            }
            
            // Modal close logic
            if (event.target === modal) {
                closeBookingModal();
            }
        }

        form.onsubmit = function(e) {
            e.preventDefault();
            
            const formData = new FormData(form);
            
            fetch('book_appointment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Appointment Booked Successfully!');
                    closeBookingModal();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        }
    </script>
</body>

</html>
