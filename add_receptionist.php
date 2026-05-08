<?php
include 'db_connect.php';
$message = '';

if (isset($_POST['submit'])) {
    $fullname = trim($_POST['fullname'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = trim($_POST['role'] ?? 'Receptionist');
    $shift = trim($_POST['shift'] ?? '');

    // Validation for required fields
    if ($fullname === '' || $username === '' || $password === '') {
        $message = "❌ Please fill in all required fields.";
    } else {
        // Use plain password (no hashing)
        $passwordPlain = $password;

        // RoleID for Admin (you mentioned it's 4)
        $roleid = 4;

        // Start transaction
        $conn->begin_transaction();

        try {
            // ✅ Insert into receptionists table
            $stmt1 = $conn->prepare("INSERT INTO receptionists (FullName, Username, Password, Role, ShiftTiming) VALUES (?, ?, ?, ?, ?)");
            $stmt1->bind_param("sssss", $fullname, $username, $passwordPlain, $role, $shift);
            $stmt1->execute();

            // ✅ Insert into appusers table with RoleID instead of Role
            $stmt2 = $conn->prepare("INSERT INTO appusers (Username, Password, RoleID) VALUES (?, ?, ?)");
            $stmt2->bind_param("ssi", $username, $passwordPlain, $roleid);  // Correct bind_param for RoleID
            $stmt2->execute();

            // ✅ Commit transaction
            $conn->commit();
            $message = "✅ Receptionist added to both tables successfully!";
        } catch (Exception $e) {
            // If something goes wrong, rollback
            $conn->rollback();
            $message = "❌ Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Receptionist</title>
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
    <h2>Register Receptionist</h2>
    <?php if ($message): ?>
        <p><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>
    <form method="POST">
        Full Name: <input type="text" name="fullname" required><br>
        Username: <input type="text" name="username" required><br>
        Password: <input type="password" name="password" required><br>
        Role: <input type="text" name="role" value="Receptionist"><br>
        Shift: <input type="text" name="shift"><br>
        <button type="submit" name="submit">Register</button>
    </form>
</body>
</html>
