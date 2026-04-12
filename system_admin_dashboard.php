<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: system_admin_login.php");
    exit;
}

include 'db_config.php';

// Handle Action
$action_msg = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && isset($_POST['doctor_id'])) {
    $doc_id = intval($_POST['doctor_id']);
    $action = $_POST['action']; // 'approve' or 'reject'
    
    if ($action === 'approve' || $action === 'reject') {
        $status = ($action === 'approve') ? 'approved' : 'rejected';
        $update_sql = "UPDATE doctors SET status = ? WHERE id = ?";
        $stmt_update = $conn->prepare($update_sql);
        $stmt_update->bind_param("si", $status, $doc_id);
        if ($stmt_update->execute()) {
            $action_msg = "Doctor account successfully {$status}.";
        } else {
            $action_msg = "Error updating status: " . $conn->error;
        }
    }
}

// Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: system_admin_login.php");
    exit;
}

// Fetch pending doctors
$sql = "SELECT id, doctor_identity, name, email, specialization, work_type, clinic_name, hospital_name, phone_number, created_at, certificate_path FROM doctors WHERE status = 'pending' ORDER BY created_at ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Admin Dashboard - Med Buddy</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #111827;
            --text-dark: #1F2937;
            --text-light: #6B7280;
            --white: #FFFFFF;
            --border-color: #E5E7EB;
            --dashboard-bg: #F8FAFC;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--dashboard-bg); color: var(--text-dark); min-height: 100vh; }
        .header { background-color: var(--white); padding: 1rem 2rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1); }
        .header h1 { font-size: 1.5rem; font-weight: 700; color: var(--primary-color); display: flex; align-items: center; gap: 0.5rem; }
        .logout-btn { color: #DC2626; text-decoration: none; font-weight: 600; padding: 0.5rem 1rem; border: 1px solid #FECACA; border-radius: 8px; background-color: #FEF2F2; transition: all 0.2s; display: flex; align-items: center; gap: 0.5rem;}
        .logout-btn:hover { background-color: #FCA5A5; color: #991B1B; }
        .container { max-width: 1400px; margin: 2rem auto; padding: 0 2rem; }
        .msg-alert { padding: 1rem; border-radius: 8px; margin-bottom: 2rem; font-weight: 600; background-color: #DEF7EC; color: #03543F; border: 1px solid #31C48D; }
        
        .card { background: var(--white); border-radius: 12px; border: 1px solid var(--border-color); padding: 2rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .card h2 { margin-bottom: 1.5rem; font-size: 1.25rem; font-weight: 700; border-bottom: 2px solid #F3F4F6; padding-bottom: 0.75rem;}
        
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 1rem; font-size: 0.85rem; text-transform: uppercase; color: var(--text-light); background: #F9FAFB; border-bottom: 2px solid var(--border-color); }
        td { padding: 1rem; border-bottom: 1px solid var(--border-color); font-size: 0.95rem; vertical-align: middle; }
        tr:hover { background-color: #F9FAFB; }
        
        .action-btns { display: flex; gap: 0.5rem; }
        .btn { padding: 0.5rem 1rem; border: none; border-radius: 6px; font-weight: 600; font-size: 0.85rem; cursor: pointer; transition: all 0.2s; }
        .btn-approve { background-color: #10B981; color: white; }
        .btn-approve:hover { background-color: #059669; }
        .btn-reject { background-color: #EF4444; color: white; }
        .btn-reject:hover { background-color: #DC2626; }
        
        .empty-state { text-align: center; padding: 3rem; color: var(--text-light); }
        .empty-state i { font-size: 3rem; color: #D1D5DB; margin-bottom: 1rem; display: block; }
        
    </style>
</head>
<body>
    <header class="header">
        <h1><i class="ri-shield-user-fill"></i> System Administration</h1>
        <div style="display: flex; align-items: center; gap: 1rem;">
            <span style="font-weight: 500; color: var(--text-light);"><i class="ri-user-settings-line"></i> Admin User</span>
            <a href="?logout=true" class="logout-btn"><i class="ri-logout-box-r-line"></i> Secure Logout</a>
        </div>
    </header>

    <div class="container">
        <?php if(!empty($action_msg)): ?>
            <div class="msg-alert"><i class="ri-checkbox-circle-fill"></i> <?php echo htmlspecialchars($action_msg); ?></div>
        <?php endif; ?>

        <div class="card">
            <h2>Pending Doctor Registrations</h2>
            
            <?php if ($result->num_rows > 0): ?>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Registered On</th>
                            <th>Doctor ID</th>
                            <th>Name</th>
                            <th>Specialization</th>
                            <th>Work Profile</th>
                            <th>Contact Info</th>
                            <th>Certificate</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td style="color: var(--text-light); font-size: 0.85rem;"><?php echo date('d M Y, H:i', strtotime($row['created_at'])); ?></td>
                            <td style="font-weight: 600;"><?php echo htmlspecialchars($row['doctor_identity'] ?? ''); ?></td>
                            <td style="font-weight: 700; color: var(--primary-color);">Dr. <?php echo htmlspecialchars($row['name'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['specialization'] ?? ''); ?></td>
                            <td>
                                <span style="font-size: 0.8rem; background: #E5E7EB; padding: 2px 6px; border-radius: 4px;"><?php echo htmlspecialchars($row['work_type'] ?? ''); ?></span><br>
                                <?php echo htmlspecialchars(($row['work_type'] == 'Clinic' ? $row['clinic_name'] : $row['hospital_name']) ?? ''); ?>
                            </td>
                            <td style="font-size: 0.85rem;">
                                <div><i class="ri-mail-line"></i> <?php echo htmlspecialchars($row['email'] ?? ''); ?></div>
                                <div><i class="ri-phone-line"></i> <?php echo htmlspecialchars($row['phone_number'] ?? ''); ?></div>
                            </td>
                            <td>
                                <?php if(!empty($row['certificate_path'])): ?>
                                    <a href="<?php echo htmlspecialchars($row['certificate_path']); ?>" target="_blank" style="color: #2563EB; text-decoration: none; font-size: 0.85rem; font-weight: 600;"><i class="ri-file-pdf-2-line"></i> View PDF</a>
                                <?php else: ?>
                                    <span style="color: var(--text-light); font-size: 0.85rem;">None provided</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" action="" class="action-btns" onsubmit="return confirm('Are you sure you want to perform this action?');">
                                    <input type="hidden" name="doctor_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" name="action" value="approve" class="btn btn-approve" title="Approve"><i class="ri-check-line"></i></button>
                                    <button type="submit" name="action" value="reject" class="btn btn-reject" title="Reject"><i class="ri-close-line"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="ri-check-double-line"></i>
                <h3>All Caught Up!</h3>
                <p>There are no pending doctor registrations to review at this time.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
