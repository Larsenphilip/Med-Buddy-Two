<?php
include 'db_config.php';
$sql = "ALTER TABLE appointments MODIFY patient_email VARCHAR(100) NULL";
if ($conn->query($sql)) {
    echo "Table 'appointments' updated. 'patient_email' is now nullable.\n";
} else {
    echo "Error: " . $conn->error . "\n";
}
?>
