<?php
session_start();

// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "capitalhealthdb";

// Connect to the database
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Patient Registration</title>
    <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: #f0f4f8;
      margin: 0;
      padding: 0;
      color: #333;
    }

    .container {
      max-width: 480px;
      background: #fff;
      margin: 80px auto;
      padding: 30px 40px;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      text-align: center;
    }

    h1 {
      color: #2c3e50;
      margin-bottom: 24px;
    }

    p {
      font-size: 1.1rem;
      margin-bottom: 30px;
    }

    ul {
      list-style-type: none;
      padding: 0;
      margin-bottom: 30px;
    }

    ul li {
      margin: 12px 0;
    }

    ul li a {
      display: inline-block;
      text-decoration: none;
      background-color: #2980b9;
      color: white;
      padding: 12px 24px;
      border-radius: 5px;
      transition: background-color 0.3s ease;
      font-weight: 600;
    }

    ul li a:hover {
      background-color: #1c5980;
    }

    .logout {
      display: inline-block;
      margin-top: 10px;
      text-decoration: none;
      color: #e74c3c;
      font-weight: 600;
      padding: 10px 20px;
      border: 2px solid #e74c3c;
      border-radius: 5px;
      transition: background-color 0.3s ease, color 0.3s ease;
    }

    .logout:hover {
      background-color: #e74c3c;
      color: white;
    }
  </style>
</head>
<body>
    <h2>Register New Patient</h2>
    <form method="POST">
        First Name: <input type="text" name="firstname" required><br>
        Last Name: <input type="text" name="lastname" required><br>
        Date of Birth: <input type="date" name="dob" required><br>
        Gender:
        <select name="gender">
            <option>Male</option>
            <option>Female</option>
            <option>Other</option>
        </select><br>
        Contact Info: <input type="text" name="contact" required><br>
        <button type="submit" name="submit">Register</button>
    </form>

<?php
if (isset($_POST['submit'])) {
    $firstName = trim($_POST['firstname']);
    $lastName = trim($_POST['lastname']);
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $contact = trim($_POST['contact']);

    $stmt = $conn->prepare("INSERT INTO Patients (FirstName, LastName, DOB, Gender, ContactInfo) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $firstName, $lastName, $dob, $gender, $contact);

    if ($stmt->execute()) {
        echo "<p>✅ Patient registered successfully!</p>";
    } else {
        echo "<p>❌ Error: " . $stmt->error . "</p>";
    }

    $stmt->close();
}
$conn->close();
?>
</body>
</html>
