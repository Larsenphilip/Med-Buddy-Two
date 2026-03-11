<?php
$host = 'localhost';
$username = 'root'; // Default XAMPP username
$password = '';     // Default XAMPP password is empty
$dbname = 'med_buddy_db';

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

