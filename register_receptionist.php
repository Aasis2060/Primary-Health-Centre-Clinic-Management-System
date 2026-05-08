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
    /* Reset & base */
    * {
        box-sizing: border-box;
    }
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        margin: 0;
        background: #f4f7fa;
        color: #333;
    }
    a {
        color: #1a73e8;
        text-decoration: none;
    }
    a:hover {
        text-decoration: underline;
    }

    /* Navbar */
    .navbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background-color: #1a73e8;
        padding: 10px 20px;
        color: white;
    }
    .navbar .logo {
        font-weight: 700;
        font-size: 1.5rem;
        letter-spacing: 2px;
    }
    .navbar .user-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: url('https://i.pravatar.cc/150?u=<?php echo urlencode($username); ?>') no-repeat center/cover;
        border: 2px solid white;
    }
    .logout-btn {
        background: transparent;
        border: 1.5px solid white;
        padding: 6px 12px;
        border-radius: 20px;
        color: white;
        font-weight: 600;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }
    .logout-btn:hover {
        background-color: white;
        color: #1a73e8;
    }

    /* Container */
    .container {
        max-width: 1200px;
        margin: 30px auto;
        padding: 0 20px;
    }

    /* Welcome */
    h1.welcome {
        margin-bottom: 30px;
        font-weight: 600;
        font-size: 2rem;
        color: #1a73e8;
    }

    /* Dashboard grid */
    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
    }

    /* Card style */
    .card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 10px rgb(0 0 0 / 0.1);
        padding: 30px 20px;
        text-align: center;
        transition: transform 0.2s ease;
        cursor: pointer;
        display: block;
        color: inherit;
    }
    .card:hover {
        transform: translateY(-8px);
        box-shadow: 0 6px 15px rgb(0 0 0 / 0.15);
        text-decoration: none;
    }

    /* Card icon */
    .card-icon {
        width: 70px;
        height: 70px;
        margin-bottom: 15px;
    }

    /* Card title */
    .card-title {
        font-size: 1.25rem;
        font-weight: 700;
        margin-bottom: 8px;
        color: #333;
    }

    /* Card description */
    .card-desc {
        color: #666;
        font-size: 0.9rem;
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
