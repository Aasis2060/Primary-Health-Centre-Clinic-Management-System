<?php
session_start();
if (!isset($_SESSION['Username'])) {
    header("Location: receptionist_login.php");
    exit;
}

include 'includes/db_connect.php';

// Handle search/filter
$search = $_GET['search'] ?? '';
$search_param = '%' . $search . '%';

$stmt = $conn->prepare("SELECT PatientID, FullName, DateOfBirth, Gender, ContactNumber FROM Patients WHERE FullName LIKE ? ORDER BY FullName ASC");
$stmt->bind_param("s", $search_param);
$stmt->execute();
$result = $stmt->get_result();

$username = htmlspecialchars($_SESSION['Username']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Patients - Primary Health Centre</title>
<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        max-width: 1000px;
        margin: 40px auto;
        padding: 0 20px 50px;
        background: #f9fafc;
        color: #333;
    }
    header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
    }
    header h1 {
        color: #1a73e8;
        margin: 0;
    }
    header .welcome {
        font-weight: 600;
        font-size: 1.1rem;
    }
    .top-bar {
        display: flex;
        justify-content: space-between;
        margin-bottom: 15px;
        flex-wrap: wrap;
        gap: 10px;
    }
    .top-bar input[type="search"] {
        padding: 8px 12px;
        font-size: 1rem;
        border: 1px solid #ccc;
        border-radius: 5px;
        flex-grow: 1;
        min-width: 200px;
    }
    .top-bar a.button {
        background-color: #1a73e8;
        color: white;
        padding: 10px 20px;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 700;
        white-space: nowrap;
        transition: background-color 0.3s ease;
    }
    .top-bar a.button:hover {
        background-color: #155ab6;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(26,115,232,0.1);
    }
    thead {
        background-color: #1a73e8;
        color: white;
    }
    th, td {
        text-align: left;
        padding: 12px 15px;
        border-bottom: 1px solid #eaeaea;
    }
    tbody tr:hover {
        background-color: #f0f6ff;
    }
    @media (max-width: 600px) {
        th, td {
            padding: 8px 10px;
        }
        .top-bar {
            flex-direction: column;
            align-items: stretch;
        }
    }
    a.back {
        display: inline-block;
        margin-top: 20px;
        color: #1a73e8;
        font-weight: 600;
        text-decoration: none;
    }
    a.back:hover {
        text-decoration: underline;
    }
</style>
</head>
<body>

<header>
    <h1>Patients</h1>
    <div class="welcome">Welcome, <?= $username ?></div>
</header>

<div class="top-bar">
    <form method="GET" style="flex-grow:1;">
        <input type="search" name="search" placeholder="Search by name..." value="<?= htmlspecialchars($search) ?>" />
    </form>
    <a href="/Reception_Management/add_test_patient.php" class="button" title="Add New Patient">+ Add Patient</a>
</div>

<table>
    <thead>
        <tr>
            <th>Patient ID</th>
            <th>Full Name</th>
            <th>Date of Birth</th>
            <th>Gender</th>
            <th>Contact</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result->num_rows === 0): ?>
            <tr><td colspan="5" style="text-align:center; padding: 20px;">No patients found.</td></tr>
        <?php else: ?>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['PatientID'] ?></td>
                <td><?= htmlspecialchars($row['FullName']) ?></td>
                <td><?= htmlspecialchars($row['DateOfBirth']) ?></td>
                <td><?= htmlspecialchars($row['Gender']) ?></td>
                <td><?= htmlspecialchars($row['ContactNumber']) ?></td>
            </tr>
            <?php endwhile; ?>
        <?php endif; ?>
    </tbody>
</table>

<a href="/Reception_Management/dashboard.php" class="back">&larr; Back to Dashboard</a>

</body>
</html>
