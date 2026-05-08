<?php
include 'db_connect.php';

// Fetch patients for dropdown
$patients = [];
$patientResult = $conn->query("SELECT PatientID, FirstName FROM patients ORDER BY FirstName");
if ($patientResult) {
    while ($row = $patientResult->fetch_assoc()) {
        $patients[] = $row;
    }
}

// Fetch doctors for dropdown
$doctors = [];
$doctorResult = $conn->query("SELECT DoctorID, FullName FROM doctors ORDER BY FullName");
if ($doctorResult) {
    while ($row = $doctorResult->fetch_assoc()) {
        $doctors[] = $row;
    }
}

$message = '';

if (isset($_POST['submit'])) {
    $patientID = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : 0;
    $doctorID = isset($_POST['doctor_id']) ? (int)$_POST['doctor_id'] : 0;
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $serviceType = trim($_POST['service_type'] ?? '');

    // Convert IDs from database arrays to int for safe comparison
    $validPatientIDs = array_map('intval', array_column($patients, 'PatientID'));
    $validDoctorIDs = array_map('intval', array_column($doctors, 'DoctorID'));

    if (!in_array($patientID, $validPatientIDs, true)) {
        $message = "❌ Selected patient does not exist.";
    } elseif (!in_array($doctorID, $validDoctorIDs, true)) {
        $message = "❌ Selected doctor does not exist.";
    } elseif (empty($serviceType)) {
        $message = "❌ Please enter a service type.";
    } else {
        $stmt = $conn->prepare("INSERT INTO appointments (PatientID, DoctorID, AppointmentDate, AppointmentTime, ServiceType) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisss", $patientID, $doctorID, $date, $time, $serviceType);

        if ($stmt->execute()) {
            $message = "✅ Appointment scheduled successfully!";
        } else {
            $message = "❌ Database error: " . $stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Schedule Appointment</title>
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
    <h2>Schedule Appointment</h2>

    <?php if ($message): ?>
        <p><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        Patient:
        <select name="patient_id" required>
            <option value="">-- Select Patient --</option>
            <?php foreach ($patients as $patient): ?>
                <option value="<?php echo $patient['PatientID']; ?>" <?php if (isset($_POST['patient_id']) && $_POST['patient_id'] == $patient['PatientID']) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($patient['FirstName'] . " (ID: " . $patient['PatientID'] . ")"); ?>
                </option>
            <?php endforeach; ?>
        </select><br><br>

        Doctor:
        <select name="doctor_id" required>
            <option value="">-- Select Doctor --</option>
            <?php foreach ($doctors as $doctor): ?>
                <option value="<?php echo $doctor['DoctorID']; ?>" <?php if (isset($_POST['doctor_id']) && $_POST['doctor_id'] == $doctor['DoctorID']) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($doctor['FullName'] . " (ID: " . $doctor['DoctorID'] . ")"); ?>
                </option>
            <?php endforeach; ?>
        </select><br><br>

        Date: <input type="date" name="date" value="<?php echo isset($_POST['date']) ? htmlspecialchars($_POST['date']) : ''; ?>" required><br><br>
        Time: <input type="time" name="time" value="<?php echo isset($_POST['time']) ? htmlspecialchars($_POST['time']) : ''; ?>" required><br><br>
        Service Type: <input type="text" name="service_type" value="<?php echo isset($_POST['service_type']) ? htmlspecialchars($_POST['service_type']) : ''; ?>" required><br><br>

        <button type="submit" name="submit">Schedule</button>
    </form>
</body>
</html>
