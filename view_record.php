<?php
session_start();
require_once 'db_config.php';

// Validate user access
if (!isset($_SESSION['patient_id']) && !isset($_SESSION['doctor_id']) && !isset($_SESSION['admin_id'])) {
    die("Unauthorized Access");
}

$file_path = '';
$description = '';
$is_pdf = false;

// If viewing a record by ID from patient_images
if (isset($_GET['id'])) {
    $stmt = $conn->prepare("SELECT file_path, description, original_file_name FROM patient_images WHERE image_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $_GET['id']);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $file_path = $row['file_path'];
            $description = !empty($row['description']) ? $row['description'] : $row['original_file_name'];
        }
        $stmt->close();
    }
} 
// Fallback for legacy documents
elseif (isset($_GET['file'])) {
    $file_path = $_GET['file'];
    $description = $_GET['title'] ?? 'Uploaded Document';
}

if (empty($file_path)) {
    die("Record not found.");
}

$ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
$is_pdf = ($ext === 'pdf');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Record - MedBuddy</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0066cc;
            --text-dark: #1e293b;
            --text-light: #64748b;
            --border-color: #e2e8f0;
            --bg-color: #f1f5f9;
        }

        body, html {
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
            background: var(--bg-color);
            color: var(--text-dark);
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .template-container {
            max-width: 900px;
            width: 100%;
            background: #ffffff;
            margin: 2rem auto;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* Top Section */
        .top-section {
            padding: 1.5rem 2rem;
            display: flex;
            align-items: center;
        }

        .brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Middle Section */
        .middle-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 0 2rem;
            background: #ffffff;
        }

        .document-wrapper {
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #f8fafc;
            padding: 2rem;
            border-radius: 8px;
            border: 1px dashed var(--border-color);
        }

        img.record-image {
            max-width: 100%;
            max-height: 70vh;
            object-fit: contain;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        iframe.record-pdf {
            width: 100%;
            height: 70vh;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            background: white;
        }

        .description {
            width: 100%;
            text-align: center;
            padding: 1.5rem 0;
            font-size: 1.1rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 0.5rem;
        }

        /* Bottom Section */
        .bottom-section {
            width: 100%;
            border-bottom: 3px solid var(--border-color);
        }

        @media (max-width: 768px) {
            .template-container {
                margin: 0;
                border-radius: 0;
                min-height: 100vh;
                box-shadow: none;
            }
            .document-wrapper {
                padding: 1rem;
            }
            img.record-image, iframe.record-pdf {
                max-height: 60vh;
            }
        }
    </style>
</head>
<body>
    <div class="template-container">
        
        <!-- Top Section -->
        <div class="top-section">
            <div class="brand">
                <i class="ri-hospital-line"></i> MedBuddy
            </div>
        </div>
        
        <!-- Middle Section -->
        <div class="middle-section">
            <div class="document-wrapper">
                <?php if ($is_pdf): ?>
                    <iframe src="<?php echo htmlspecialchars($file_path); ?>" class="record-pdf"></iframe>
                <?php else: ?>
                    <img src="<?php echo htmlspecialchars($file_path); ?>" class="record-image" alt="Patient Record">
                <?php endif; ?>
            </div>
            
            <div class="description">
                <?php echo htmlspecialchars($description); ?>
            </div>
        </div>
        
        <!-- Bottom Section -->
        <div class="bottom-section"></div>
        
    </div>
</body>
</html>
