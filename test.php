<?php
require 'db_config.php';
$res = $conn->query("SHOW COLUMNS FROM patient_images");
while($row=$res->fetch_assoc()) {
    print_r($row);
}
