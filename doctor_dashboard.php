<?php
session_start();
if (!isset($_SESSION["username"]) || $_SESSION["role"] !== "Doctor") {
  header("Location: login.html");
  exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Doctor Dashboard</title>
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
  <div class="container">
    <h1>Doctor Dashboard</h1>
    <p>Welcome, Dr. <?= htmlspecialchars($_SESSION["username"]) ?></p>

    <ul>
       <li><a href="dashboard.php">dashboard for receptionist</a></li>
            <li>DISCHARGE SUMMARY</li>

      <li><a href="add_summary.html">Add Discharge Summary</a></li>
      <li><a href="update_summary.html">Update Summary</a></li>
      <li><a href="view_summary.html">View All Summaries</a></li>
      <li><a href="attach_report.html">Attach report</a></li>
    </ul>

    <a class="logout" href="login.html">Logout</a>
  </div>
</body>
</html>
