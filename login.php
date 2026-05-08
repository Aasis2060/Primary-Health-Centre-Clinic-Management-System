<?php
session_start();

// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "capitalhealthdb";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        die("Please enter both username and password.");
    }

    // Fetch user info
    $stmt = $conn->prepare("
        SELECT u.UserID, u.Username, u.Password, r.RoleName
        FROM AppUsers u
        JOIN Roles r ON u.RoleID = r.RoleID
        WHERE u.Username = ?
    ");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && $password === $user['Password']) {
        $_SESSION['UserID'] = $user['UserID'];
        $_SESSION['username'] = $user['Username'];
        $_SESSION['role'] = $user['RoleName'];

        // Debug: Show role before redirect
        // echo "Detected Role: " . $_SESSION['role']; exit;

        // Redirect based on role (case-insensitive)
        $role = strtolower($_SESSION['role']);
        if ($role === 'doctor') {
            header("Location: ../doctor_dashboard.php");
            exit();
        } elseif ($role === 'nurse') {
            header("Location: ../nurse_dashboard.php");
            exit();
        } elseif ($role === 'patient') {
            header("Location: ../patient_dashboard.php");
            exit();
        } elseif ($role === 'admin') {
            header("Location: ../dashboard.php");
            exit();
        } else {
            die("Unauthorized role: " . $_SESSION['role']);
        }
    } else {
        echo "Invalid username or password.";
    }
}
?>
