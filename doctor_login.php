<?php
session_start();
include 'db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Retrieve hashed password
    $sql = "SELECT id, name, email, password FROM doctors WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $doctor = $result->fetch_assoc();
        
        // Check password (hashed or plain text fallback)
        if (password_verify($password, $doctor['password']) || $password === $doctor['password']) {
            $_SESSION['doctor_id'] = $doctor['id'];
            $_SESSION['doctor_name'] = $doctor['name'];
            header("Location: admin/index.php");
            exit;
        } else {
            $error = "Invalid email or password";
        }
    } else {
        $error = "Invalid email or password"; // User not found
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Login - Med Buddy</title>
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
            width: 45%;
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
            width: 55%;
            margin-left: 45%; /* Offset for fixed left panel */
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 4rem 6rem;
            background-color: var(--white);
            min-height: 100vh;
        }

        .form-container {
            max-width: 480px;
            width: 100%;
            margin: 0 auto;
        }

        .form-header {
            margin-bottom: 3rem;
            text-align: center;
        }

        .form-header h2 {
            font-size: 2rem;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .form-header p {
            color: var(--text-light);
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 0.9rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            font-size: 1rem;
            transition: all 0.2s;
            background-color: #f9fafb;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            background-color: var(--white);
            box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.1);
        }

        .btn-login {
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
            margin-top: 1rem;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px rgba(0, 102, 204, 0.3);
        }

        .error-alert {
            background-color: #FEF2F2;
            color: #DC2626;
            border: 1px solid #FECACA;
            padding: 1rem;
            border-radius: var(--radius-sm);
            margin-bottom: 1.5rem;
            text-align: center;
            font-weight: 500;
        }

        .links {
            text-align: center;
            margin-top: 2rem;
            color: var(--text-light);
        }

        .links a {
            color: var(--primary-color);
            font-weight: 600;
            text-decoration: none;
            margin-left: 0.25rem;
        }
        
        .back-home {
            display: block;
            text-align: center;
            margin-top: 1.5rem;
            color: var(--text-light);
            text-decoration: none;
            font-size: 0.9rem;
        }

        .back-home:hover {
            color: var(--text-dark);
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .info-panel { width: 40%; padding: 3rem 2rem; }
            .form-panel { width: 60%; margin-left: 40%; padding: 4rem 3rem; }
        }

        @media (max-width: 768px) {
            body { flex-direction: column; }
            .info-panel { 
                position: relative; 
                width: 100%; 
                height: auto; 
                padding: 3rem 2rem;
                min-height: 300px;
            }
            .form-panel { 
                width: 100%; 
                margin-left: 0; 
                padding: 3rem 1.5rem;
                min-height: auto;
            }
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
                <h1>Welcome Back,<br>Doctor</h1>
                <p>Log in to access your dashboard, manage appointments, and view patient records.</p>
                <ul class="features-list">
                    <li>Secure Dashboard Access</li>
                    <li>Manage Availability</li>
                    <li>Patient History Tracking</li>
                    <li>Real-time Updates</li>
                </ul>
            </div>
        </div>
        <div class="info-footer">
            &copy; <?php echo date("Y"); ?> Med Buddy. All rights reserved.
        </div>
    </div>

    <!-- Right Panel (Form) -->
    <div class="form-panel">
        <div class="form-container">
            <div class="form-header">
                <h2>Doctor Login</h2>
                <p>Enter your credentials to continue</p>
            </div>

            <?php if(isset($error)): ?>
                <div class="error-alert">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required placeholder="doctor@medbuddy.com">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required placeholder="Enter your password">
                </div>
                
                <button type="submit" class="btn-login">Login to Dashboard</button>
            </form>

            <div class="links">
                New to Med Buddy? <a href="doctor_registration.php">Create Account</a>
            </div>
            
            <a href="index.html" class="back-home">← Back to Home</a>
        </div>
    </div>

</body>
</html>
