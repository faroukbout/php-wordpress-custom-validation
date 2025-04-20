<?php
// Database connection
$host = 'localhost'; 
$dbname = ''; 
$username = ''; 
$password = ''; 

$conn = mysqli_connect($host, $username, $password, $dbname);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
mysqli_set_charset($conn, 'utf8mb4');
?>
