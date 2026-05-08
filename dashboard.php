<?php
session_start();

if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['Username'] ?? 'Receptionist';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Receptionist Dashboard</title>
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

<header class="navbar">
    <div class="logo">Primary Health Centre</div>
    <div class="user-info">
        <div class="user-avatar" title="<?php echo htmlspecialchars($username); ?>"></div>
        <div><?php echo htmlspecialchars($username); ?></div>
        <form method="POST" action="login.html" style="margin:0;">
            <button class="logout-btn" type="submit" name="logout">Logout</button>
        </form>
    </div>
</header>

<div class="container">
    <h1 class="welcome">Welcome, <?php echo htmlspecialchars($username); ?>!</h1>

    <div class="dashboard-grid">
        <a href="Patient_Registration.php" class="card" title="Manage Patients">
            <img src="https://img.icons8.com/ios-filled/100/1a73e8/patient-file.png" alt="Patients Icon" class="card-icon" />
            <div class="card-title">Patients</div>
            <div class="card-desc">View and manage patient records.</div>
        </a>

        <a href="schedule_appointment.php" class="card" title="View Appointments">
            <img src="https://img.icons8.com/ios-filled/100/1a73e8/calendar--v1.png" alt="Appointments Icon" class="card-icon" />
            <div class="card-title">Appointments</div>
            <div class="card-desc">Schedule and track appointments.</div>
        </a>

        <a href="notifications.php" class="card" title="Check Notifications">
            <img src="https://img.icons8.com/ios-filled/100/1a73e8/appointment-reminders.png" alt="Notifications Icon" class="card-icon" />
            <div class="card-title">Notifications</div>
            <div class="card-desc">View alerts and reminders.</div>
        </a>

        <a href="add_receptionist.php" class="card" title="Create receprionist">
            <img src="https://img.icons8.com/ios-filled/100/1a73e8/appointment-reminders.png" alt="Receptionist Icon" class="card-icon" />
            <div class="card-title">create new receptionists</div>
            
        </a>
    </div>
</div>

</body>
</html>
