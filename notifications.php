<?php
session_start();

if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit;
}

include 'db_connect.php';

// Fetch notifications ordered by newest first
$sql = "SELECT * FROM receptionnotifications ORDER BY CreatedAt DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>System Notifications</title>
<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        max-width: 800px;
        margin: 40px auto;
        background: #f9fafc;
        color: #333;
        padding: 0 20px;
    }
    h1 {
        color: #1a73e8;
        margin-bottom: 25px;
        text-align: center;
    }
    .notification {
        background: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 15px;
        box-shadow: 0 3px 8px rgba(0,0,0,0.1);
        transition: box-shadow 0.3s ease;
    }
    .notification:hover {
        box-shadow: 0 6px 15px rgba(0,0,0,0.15);
    }
    .title {
        font-weight: 700;
        font-size: 1.2rem;
        color: #1a73e8;
        margin-bottom: 8px;
    }
    .message {
        font-size: 1rem;
        margin-bottom: 12px;
        white-space: pre-wrap;
    }
    .date {
        font-size: 0.85rem;
        color: #777;
    }
    a.back {
        display: inline-block;
        margin-top: 30px;
        text-decoration: none;
        color: #1a73e8;
        font-weight: 600;
    }
    a.back:hover {
        text-decoration: underline;
    }
</style>
</head>
<body>

<h1>System Notifications</h1>

<?php
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo '<div class="notification">';
        echo '<div class="title">' . htmlspecialchars($row['MessageType']) . '</div>';
        echo '<div class="message">' . nl2br(htmlspecialchars($row['MessageContent'])) . '</div>';
        echo '<div class="date">' . date('F j, Y, g:i a', strtotime($row['CreatedAt'])) . '</div>';
        echo '</div>';
    }
} else {
    echo '<p>No notifications found.</p>';
}
?>

<a href="dashboard.php" class="back">&larr; Back to Dashboard</a>

</body>
</html>
