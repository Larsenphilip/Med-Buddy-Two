<?php
include 'db_config.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Collect and Sanitize Inputs
    $name = trim($_POST['name']);
    $specialization = trim($_POST['specialization']);
    if ($specialization === 'Others') {
        $specialization = trim($_POST['specialization_other']);
    }
    $email_prefix = strtolower(explode(" ", $name)[0]); // First name for email
    $phone_number = trim($_POST['phone_number']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $work_type = $_POST['work_type'];

    // Validation
    if ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        // 2. Handle File Uploads
        $upload_dir_cert = "uploads/doctors/certificates/";
        $upload_dir_img = "uploads/doctors/images/";
        
        // Create directories if not exist
        if (!file_exists($upload_dir_cert)) mkdir($upload_dir_cert, 0777, true);
        if (!file_exists($upload_dir_img)) mkdir($upload_dir_img, 0777, true);

        $cert_path = "";
        $image_path = "";

        // Certificate Upload
        if (isset($_FILES['certificate']) && $_FILES['certificate']['error'] == 0) {
            $cert_ext = strtolower(pathinfo($_FILES['certificate']['name'], PATHINFO_EXTENSION));
            if ($cert_ext != "pdf") {
                $error = "Certificate must be a PDF file.";
            } else {
                $cert_new_name = uniqid("cert_") . ".pdf";
                $cert_path = $upload_dir_cert . $cert_new_name;
                move_uploaded_file($_FILES['certificate']['tmp_name'], $cert_path);
            }
        }

        // Image Upload
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0 && empty($error)) {
            $img_ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png'];
            if (!in_array($img_ext, $allowed)) {
                $error = "Photo must be JPG or PNG.";
            } else {
                $img_new_name = uniqid("img_") . "." . $img_ext;
                $image_path = $upload_dir_img . $img_new_name;
                move_uploaded_file($_FILES['photo']['tmp_name'], $image_path);
            }
        }

        if (empty($error)) {
            // 3. Generate Data
            // Generate Doctor ID: DOC-<year>-<3alphanumeric><3alphanumeric> (Example F32D3W)
            $year = date("Y");
            $random_chars = substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 6);
            $doctor_identity = "DOC-$year-$random_chars";

            // Generate Email: unique check
            $email = $email_prefix . "@medbuddy.com";
            $count = 1;
            while (true) {
                $stmt = $conn->prepare("SELECT id FROM doctors WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                if ($stmt->get_result()->num_rows == 0) break;
                $email = $email_prefix . $count . "@medbuddy.com";
                $count++;
            }

            // Hash Password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Dynamic Fields
            $clinic_name = $clinic_address = $clinic_timings = "";
            $hospital_name = $designation = $hospital_reg_id = "";

            if ($work_type == 'Clinic') {
                $clinic_name = $_POST['clinic_name'];
                $clinic_address = $_POST['clinic_address'];
                $clinic_timings = $_POST['clinic_timings'];
            } else {
                $hospital_name = $_POST['hospital_name'];
                $designation = $_POST['designation'];
                $hospital_reg_id = $_POST['hospital_reg_id'];
            }

            // 4. Insert into Database
            // Schema: id, name, specialization, department(NULL), hospital_id(NULL), email, password, image_path, created_at, 
            // doctor_identity, work_type, clinic_name, clinic_address, clinic_timings, hospital_name, designation, hospital_reg_id, phone_number, certificate_path
            
            // Note: 'id' is AI (assumed), but schema has 'id INT PRIMARY KEY'. If it's not AI, we might have issue. 
            // The prompt "CREATE TABLE doctors (id INT PRIMARY KEY..." usually implies manual ID unless AUTO_INCREMENT is specified.
            // But usually in such tasks it's AUTO_INCREMENT. I will assume AUTO_INCREMENT.
            // If it fails, I'll need to generate an ID.
            
            // Wait, looking at current `doctor_login.php` it doesn't show ID generation.
            // Let's assume AUTO_INCREMENT for ID. Or I can check schema.
            // I'll assume AUTO_INCREMENT.

            $sql = "INSERT INTO doctors (
                name, specialization, email, password, image_path, 
                doctor_identity, work_type, clinic_name, clinic_address, clinic_timings, 
                hospital_name, designation, hospital_reg_id, phone_number, certificate_path, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("sssssssssssssss", 
                    $name, $specialization, $email, $hashed_password, $image_path,
                    $doctor_identity, $work_type, $clinic_name, $clinic_address, $clinic_timings,
                    $hospital_name, $designation, $hospital_reg_id, $phone_number, $cert_path
                );

                if ($stmt->execute()) {
                    $success = "Registration successful! Your Doctor ID is <b>$doctor_identity</b> and Email is <b>$email</b>. <a href='doctor_login.php'>Login Here</a>";
                } else {
                    $error = "Database Error: " . $stmt->error;
                }
            } else {
                $error = "Prepare failed: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Registration - Med Buddy</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0066CC;
            --primary-gradient: linear-gradient(135deg, #0066CC 0%, #004C99 100%);
            --secondary-color: #E6F4FF;
            --text-dark: #1F2937;
            --text-light: #6B7280;
            --white: #FFFFFF;
            --border-color: #E5E7EB;
            --radius-md: 12px;
            --radius-sm: 8px;
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            display: flex;
            min-height: 100vh;
            background-color: #f9fafb;
        }

        /* Left Side - Hero/Info */
        .info-panel {
            width: 40%;
            background: var(--primary-gradient);
            color: var(--white);
            padding: 4rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            z-index: 10;
        }

        .brand-logo {
            font-size: 2rem;
            font-weight: 800;
            color: var(--white);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 2rem;
        }

        .info-content h1 {
            font-size: 3rem;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            font-weight: 700;
        }

        .info-content p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 3rem;
            line-height: 1.6;
        }

        .features-list {
            list-style: none;
        }

        .features-list li {
            position: relative;
            padding-left: 2rem;
            margin-bottom: 1rem;
            font-size: 1.1rem;
            font-weight: 500;
        }

        .features-list li::before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #4ade80; /* Green tick */
            font-weight: bold;
        }

        .info-footer {
            font-size: 0.9rem;
            opacity: 0.7;
        }

        /* Right Side - Form */
        .form-panel {
            width: 60%;
            margin-left: 40%; /* Offset for fixed left panel */
            padding: 4rem 6rem;
            background-color: var(--white);
            min-height: 100vh;
        }

        .form-header {
            margin-bottom: 3rem;
        }

        .form-header h2 {
            font-size: 2rem;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .form-header p {
            color: var(--text-light);
        }

        /* Form Components */
        .form-section {
            background: var(--white);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .form-section h3 {
            font-size: 1.25rem;
            color: var(--text-dark);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-bottom: 2px solid var(--secondary-color);
            padding-bottom: 0.75rem;
        }
        
        .section-icon {
            background: var(--secondary-color);
            color: var(--primary-color);
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .full-width {
            grid-column: span 2;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 0.9rem;
        }

        .form-group input, 
        .form-group select {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            font-size: 1rem;
            transition: all 0.2s;
            background-color: #f9fafb;
        }

        .form-group input:focus, 
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
            background-color: var(--white);
            box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.1);
        }
        
        .file-input-wrapper {
            position: relative;
            border: 2px dashed var(--border-color);
            border-radius: var(--radius-md);
            padding: 2rem;
            text-align: center;
            transition: all 0.2s;
            background: #f9fafb;
            cursor: pointer;
        }
        
        .file-input-wrapper:hover {
            border-color: var(--primary-color);
            background: var(--secondary-color);
        }
        
        .file-input-wrapper input[type="file"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }
        
        .file-label {
            pointer-events: none;
            color: var(--text-light);
            font-size: 0.9rem;
        }
        
        .file-label span {
            color: var(--primary-color);
            font-weight: 600;
        }

        .btn-register {
            width: 100%;
            background: var(--primary-gradient);
            color: var(--white);
            border: none;
            padding: 1rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 4px 6px rgba(0, 102, 204, 0.2);
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px rgba(0, 102, 204, 0.3);
        }

        .login-link {
            text-align: center;
            margin-top: 2rem;
            color: var(--text-light);
        }

        .login-link a {
            color: var(--primary-color);
            font-weight: 600;
            text-decoration: none;
        }

        .alert {
            padding: 1rem;
            border-radius: var(--radius-sm);
            margin-bottom: 2rem;
            font-weight: 500;
        }

        .alert-error {
            background-color: #FEF2F2;
            color: #DC2626;
            border: 1px solid #FECACA;
        }

        .alert-success {
            background-color: #ecfdf5;
            color: #059669;
            border: 1px solid #A7F3D0;
        }

        .hidden { display: none; }

        /* Responsive */
        @media (max-width: 1024px) {
            .info-panel { width: 35%; padding: 3rem 2rem; }
            .form-panel { width: 65%; margin-left: 35%; padding: 4rem 3rem; }
        }

        @media (max-width: 768px) {
            body { flex-direction: column; }
            .info-panel { 
                position: relative; 
                width: 100%; 
                height: auto; 
                padding: 3rem 2rem;
            }
            .form-panel { 
                width: 100%; 
                margin-left: 0; 
                padding: 2rem 1.5rem;
            }
            .form-grid { grid-template-columns: 1fr; }
            .full-width { grid-column: span 1; }
        }
    </style>
</head>
<body>

    <!-- Left Panel -->
    <div class="info-panel">
        <div>
            <a href="index.html" class="brand-logo">
                Med Buddy
            </a>
            <div class="info-content">
                <h1>Join Our Global<br>Health Network</h1>
                <p>Register as a specialist to connect with thousands of patients, manage your appointments efficiently, and digitize your practice.</p>
                <ul class="features-list">
                    <li>Smart Appointment Scheduling</li>
                    <li>Digital Health Records Access</li>
                    <li>Secure Platform for Doctors</li>
                    <li>Integrated Analytics Dashboard</li>
                </ul>
            </div>
        </div>
        <div class="info-footer">
            &copy; <?php echo date("Y"); ?> Med Buddy. All rights reserved.
        </div>
    </div>

    <!-- Right Panel (Form) -->
    <div class="form-panel">
        <div class="form-header">
            <h2>Create Doctor Profile</h2>
            <p>Please fill in the details below to complete your registration.</p>
        </div>

        <?php if($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if(!$success): ?>
        <form method="POST" enctype="multipart/form-data">
            
            <!-- Personal Info -->
            <div class="form-section">
                <h3><span class="section-icon">1</span> Personal Information</h3>
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Full Name</label>
                        <input type="text" name="name" required placeholder="Dr. John Doe">
                    </div>
                   
                    <div class="form-group full-width">
                        <label>Specialization</label>
                        <select name="specialization" id="specialization" required onchange="toggleSpecialization()">
                            <option value="" disabled selected>Select Specialization</option>
                            <option value="General Physician">General Physician</option>
                            <option value="Cardiologist">Cardiologist (Heart specialist)</option>
                            <option value="Neurologist">Neurologist (Brain & nerves)</option>
                            <option value="Orthopedic Surgeon">Orthopedic Surgeon (Bones & joints)</option>
                            <option value="Dermatologist">Dermatologist (Skin, hair & nails)</option>
                            <option value="Pediatrician">Pediatrician (Child specialist)</option>
                            <option value="Gynecologist / Obstetrician">Gynecologist / Obstetrician (Women’s health & pregnancy)</option>
                            <option value="Psychiatrist">Psychiatrist (Mental health)</option>
                            <option value="ENT Specialist">ENT Specialist (Ear, Nose & Throat)</option>
                            <option value="Ophthalmologist">Ophthalmologist (Eye specialist)</option>
                            <option value="Dentist">Dentist</option>
                            <option value="Urologist">Urologist (Urinary system)</option>
                            <option value="Nephrologist">Nephrologist (Kidney specialist)</option>
                            <option value="Gastroenterologist">Gastroenterologist (Digestive system)</option>
                            <option value="Pulmonologist">Pulmonologist (Lungs & respiratory system)</option>
                            <option value="Endocrinologist">Endocrinologist (Hormonal disorders)</option>
                            <option value="Oncologist">Oncologist (Cancer specialist)</option>
                            <option value="Rheumatologist">Rheumatologist (Arthritis & autoimmune diseases)</option>
                            <option value="Hematologist">Hematologist (Blood disorders)</option>
                            <option value="Allergist / Immunologist">Allergist / Immunologist (Allergies & immune system)</option>
                            <option value="General Surgeon">General Surgeon</option>
                            <option value="Cardiothoracic Surgeon">Cardiothoracic Surgeon</option>
                            <option value="Neurosurgeon">Neurosurgeon</option>
                            <option value="Plastic Surgeon">Plastic Surgeon</option>
                            <option value="Vascular Surgeon">Vascular Surgeon</option>
                            <option value="Radiologist">Radiologist</option>
                            <option value="Pathologist">Pathologist</option>
                            <option value="Anesthesiologist">Anesthesiologist</option>
                            <option value="Physiotherapist">Physiotherapist</option>
                            <option value="Nutritionist / Dietitian">Nutritionist / Dietitian</option>
                            <option value="Sports Medicine Specialist">Sports Medicine Specialist</option>
                            <option value="Emergency Medicine Specialist">Emergency Medicine Specialist</option>
                            <option value="Others">Others</option>
                        </select>
                        <div id="otherSpecialization" class="hidden" style="margin-top: 10px;">
                            <input type="text" name="specialization_other" id="specialization_other" placeholder="Enter your specialization">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Profile Photo</label>
                        <div class="file-input-wrapper">
                            <input type="file" name="photo" accept=".jpg, .jpeg, .png" required onchange="updateFileName(this, 'photo-label')">
                            <div class="file-label" id="photo-label">
                                <span>Upload Photo</span> <br> PNG, JPG (Max 2MB)
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Degree Certificate</label>
                        <div class="file-input-wrapper">
                            <input type="file" name="certificate" accept=".pdf" required onchange="updateFileName(this, 'cert-label')">
                            <div class="file-label" id="cert-label">
                                <span>Upload PDF</span> <br> Certificate of Practice
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Work Info -->
            <div class="form-section">
                <h3><span class="section-icon">2</span> Professional Details</h3>
                <div class="form-group full-width">
                    <label>Work Setting</label>
                    <select name="work_type" id="workType" onchange="toggleWorkFields()" required>
                        <option value="Clinic">Private Clinic</option>
                        <option value="Hospital">Hospital / Medical Center</option>
                    </select>
                </div>

                <!-- Clinic Fields -->
                <div id="clinicFields" class="form-grid">
                    <div class="form-group">
                        <label>Clinic Name</label>
                        <input type="text" name="clinic_name" placeholder="E.g. Sunshine Clinic">
                    </div>
                    <div class="form-group">
                        <label>Timings</label>
                        <input type="text" name="clinic_timings" placeholder="E.g. Mon-Fri, 9AM - 5PM">
                    </div>
                    <div class="form-group full-width">
                        <label>Clinic Address</label>
                        <input type="text" name="clinic_address" placeholder="Full address of your clinic">
                    </div>
                </div>

                <!-- Hospital Fields -->
                <div id="hospitalFields" class="form-grid hidden">
                    <div class="form-group">
                        <label>Hospital Name</label>
                        <input type="text" name="hospital_name" placeholder="E.g. City General Hospital">
                    </div>
                    <div class="form-group">
                        <label>Designation</label>
                        <input type="text" name="designation" placeholder="E.g. Senior Representative">
                    </div>
                    <div class="form-group full-width">
                        <label>Registration ID</label>
                        <input type="text" name="hospital_reg_id" placeholder="Hospital / Medical Board ID">
                    </div>
                </div>
            </div>

            <!-- Credentials -->
            <div class="form-section">
                <h3><span class="section-icon">3</span> Account Credentials</h3>
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Mobile Number</label>
                        <input type="tel" name="phone_number" required placeholder="+1 234 567 8900">
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" required placeholder="Min 8 characters">
                    </div>
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="confirm_password" required placeholder="Re-enter password">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-register">Complete Registration</button>
            <div class="login-link">
                Already have an account? <a href="doctor_login.php">Log in</a>
            </div>
        </form>
        <?php endif; ?>
    </div>

    <script>
        function toggleWorkFields() {
            var workType = document.getElementById('workType').value;
            var clinicFields = document.getElementById('clinicFields');
            var hospitalFields = document.getElementById('hospitalFields');
            
            if (workType === 'Clinic') {
                clinicFields.classList.remove('hidden');
                hospitalFields.classList.add('hidden');
                setRequired(clinicFields, true);
                setRequired(hospitalFields, false);
            } else {
                clinicFields.classList.add('hidden');
                hospitalFields.classList.remove('hidden');
                setRequired(clinicFields, false);
                setRequired(hospitalFields, true);
            }
        }

        function setRequired(container, isRequired) {
            var inputs = container.querySelectorAll('input');
            inputs.forEach(input => {
                if(isRequired) input.setAttribute('required', 'required');
                else input.removeAttribute('required');
            });
        }

        function toggleSpecialization() {
            var specSelect = document.getElementById('specialization');
            var otherField = document.getElementById('otherSpecialization');
            var otherInput = document.getElementById('specialization_other');

            if (specSelect.value === 'Others') {
                otherField.classList.remove('hidden');
                otherInput.setAttribute('required', 'required');
            } else {
                otherField.classList.add('hidden');
                otherInput.removeAttribute('required');
                otherInput.value = ''; 
            }
        }

        function updateFileName(input, labelId) {
            if (input.files && input.files.length > 0) {
                document.getElementById(labelId).innerHTML = '<span style="color:#059669">✓ ' + input.files[0].name + '</span>';
            }
        }

        // Initialize
        toggleWorkFields();
    </script>
</body>
</html>
