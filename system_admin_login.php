<?php
session_start();
include 'db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Retrieve hashed password for admin
    $sql = "SELECT id, username, password FROM admins WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $admin = $result->fetch_assoc();
        
        if (password_verify($password, $admin['password'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            header("Location: system_admin_dashboard.php");
            exit;
        } else {
            $error = "Invalid username or password";
        }
    } else {
        $error = "Invalid username or password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Admin Login - Med Buddy</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #111827;
            --primary-gradient: linear-gradient(135deg, #1f2937 0%, #111827 100%);
            --white: #FFFFFF;
            --border-color: #E5E7EB;
            --radius-md: 12px;
            --radius-sm: 8px;
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --text-dark: #1F2937;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { display: flex; min-height: 100vh; background-color: #f3f4f6; align-items: center; justify-content: center; }
        .login-card { background: var(--white); padding: 3rem; border-radius: var(--radius-md); box-shadow: var(--shadow-lg); width: 100%; max-width: 400px; }
        .login-card h2 { margin-bottom: 1rem; color: var(--text-dark); text-align: center; }
        .form-group { margin-bottom: 1.25rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-dark); font-size: 0.9rem; }
        .form-group input { width: 100%; padding: 0.875rem 1rem; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 1rem; transition: all 0.2s; background-color: #f9fafb; }
        .form-group input:focus { outline: none; border-color: var(--primary-color); background-color: var(--white); box-shadow: 0 0 0 3px rgba(17, 24, 39, 0.1); }
        .btn-login { width: 100%; background: var(--primary-gradient); color: var(--white); border: none; padding: 1rem; font-size: 1.1rem; font-weight: 600; border-radius: var(--radius-md); cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; margin-top: 1rem; }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 10px 15px rgba(0, 0, 0, 0.2); }
        .error-alert { background-color: #FEF2F2; color: #DC2626; border: 1px solid #FECACA; padding: 1rem; border-radius: var(--radius-sm); margin-bottom: 1.5rem; text-align: center; font-weight: 500; }
        .back-home { display: block; text-align: center; margin-top: 1.5rem; color: #6b7280; text-decoration: none; font-size: 0.9rem; }
        .back-home:hover { color: var(--text-dark); }
    </style>
</head>
<body>
    <div class="login-card">
        <h2>System Admin Portal</h2>
        <?php if(isset($error)): ?>
            <div class="error-alert"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required placeholder="sysadmin">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="Enter password">
            </div>
            <button type="submit" class="btn-login">Secure Login</button>
        </form>
        <a href="index.html" class="back-home">← Back to Med Buddy Home</a>
    </div>
</body>
</html>
