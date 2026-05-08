<?php
session_start();

if (!isset($_SESSION['ReceptionistID'])) {
    header("Location: login.php");
    exit;
}

include 'db_connect.php';

$successMsg = '';
$errorMsg = '';

function existsInTable($conn, $table, $column, $value) {
    $stmt = $conn->prepare("SELECT 1 FROM $table WHERE $column = ? LIMIT 1");
    $stmt->bind_param("i", $value);
    $stmt->execute();
    $stmt->store_result();
    return $stmt->num_rows > 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pid = intval($_POST['pid']);
    $aid = intval($_POST['aid']);
    $rid = $_SESSION['ReceptionistID']; // Use logged-in receptionist ID

    // Validate foreign keys exist
    if (!existsInTable($conn, "Patients", "PatientID", $pid)) {
        $errorMsg = "❌ Patient ID does not exist.";
    } elseif (!existsInTable($conn, "Appointments", "AppointmentID", $aid)) {
        $errorMsg = "❌ Appointment ID does not exist.";
    } elseif (!existsInTable($conn, "Receptionists", "ReceptionistID", $rid)) {
        $errorMsg = "❌ Receptionist ID does not exist.";
    } else {
        if (isset($_POST['checkin'])) {
            $stmt = $conn->prepare("INSERT INTO VisitLogs (PatientID, AppointmentID, CheckInTime, ReceptionistID) VALUES (?, ?, NOW(), ?)");
            $stmt->bind_param("iii", $pid, $aid, $rid);
            if ($stmt->execute()) {
                $successMsg = "✅ Checked in successfully!";
            } else {
                $errorMsg = "❌ Check-in failed: " . $stmt->error;
            }
        }

        if (isset($_POST['checkout'])) {
            $stmt = $conn->prepare("UPDATE VisitLogs SET CheckOutTime = NOW() WHERE PatientID = ? AND AppointmentID = ? AND CheckOutTime IS NULL");
            $stmt->bind_param("ii", $pid, $aid);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $successMsg = "✅ Checked out successfully!";
            } else {
                $errorMsg = "❌ Check-out failed or no matching check-in found.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Patient Check-In / Check-Out</title>
<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        max-width: 500px;
        margin: 50px auto;
        background: #f9fafc;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 4px 14px rgba(0,0,0,0.1);
        color: #333;
    }
    h2 {
        color: #1a73e8;
        margin-bottom: 25px;
        text-align: center;
    }
    form {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    label {
        font-weight: 600;
    }
    input[type="number"] {
        padding: 8px;
        font-size: 1rem;
        border: 1px solid #ccc;
        border-radius: 5px;
    }
    .btn-group {
        display: flex;
        justify-content: space-between;
        gap: 10px;
    }
    input[type="submit"] {
        background-color: #1a73e8;
        border: none;
        padding: 10px 0;
        color: white;
        font-weight: 700;
        border-radius: 5px;
        cursor: pointer;
        flex: 1;
        transition: background-color 0.3s ease;
    }
    input[type="submit"]:hover {
        background-color: #155ab6;
    }
    .message {
        margin-top: 20px;
        font-weight: 600;
        text-align: center;
    }
    .success {
        color: green;
    }
    .error {
        color: red;
    }
    a.back {
        display: block;
        margin-top: 30px;
        text-align: center;
        color: #1a73e8;
        text-decoration: none;
        font-weight: 600;
    }
    a.back:hover {
        text-decoration: underline;
    }
</style>
</head>
<body>

<h2>Patient Check-In / Check-Out</h2>

<form method="POST" novalidate>
    <label for="pid">Patient ID:</label>
    <input type="number" id="pid" name="pid" required min="1" />

    <label for="aid">Appointment ID:</label>
    <input type="number" id="aid" name="aid" required min="1" />

    <div class="btn-group">
        <input type="submit" name="checkin" value="Check-In" />
        <input type="submit" name="checkout" value="Check-Out" />
    </div>
</form>

<?php if ($successMsg): ?>
    <div class="message success"><?= htmlspecialchars($successMsg) ?></div>
<?php endif; ?>

<?php if ($errorMsg): ?>
    <div class="message error"><?= htmlspecialchars($errorMsg) ?></div>
<?php endif; ?>

<a href="dashboard.php" class="back">&larr; Back to Dashboard</a>

</body>
</html>
