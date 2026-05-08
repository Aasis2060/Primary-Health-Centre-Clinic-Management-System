<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'capitalhealthdb'; // Your actual DB name with space

// Use backticks or escape if needed (though MySQL usually handles this)
$conn = new mysqli($host, $user, $pass, $db);

// Check for connection error
if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}
// echo "✅ Database connected successfully!";
?>
