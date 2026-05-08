-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 10, 2025 at 01:10 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `capitalhealthdb`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `GetDischargeSummary` (IN `p_PatientID` INT)   BEGIN
    SELECT * FROM DischargeSummary
    WHERE PatientID = p_PatientID;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `GetDischargeSummaryByUser` (IN `p_Username` VARCHAR(100), IN `p_PatientID` INT)   BEGIN
    DECLARE userRole VARCHAR(50);

    -- Get role of the user
    SELECT r.RoleName INTO userRole
    FROM AppUsers u
    JOIN Roles r ON u.RoleID = r.RoleID
    WHERE u.Username = p_Username;

    -- Role-based access logic
    IF userRole IN ('Doctor', 'Nurse') THEN
        SELECT * FROM DischargeSummary
        WHERE PatientID = p_PatientID;
    
    ELSEIF userRole = 'Patient' THEN
        -- Patient can only access their own records
        SELECT p.PatientID
        INTO @linkedPatientID
        FROM Patients p
        WHERE CONCAT(p.FirstName, '_', p.LastName) = p_Username;

        IF @linkedPatientID = p_PatientID THEN
            SELECT * FROM DischargeSummary
            WHERE PatientID = p_PatientID;
        ELSE
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Access Denied: You can only access your own discharge summary.';
        END IF;

    ELSE
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Access Denied: Role not authorized.';
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `InsertDischargeSummary` (IN `p_PatientID` INT, IN `p_Diagnosis` TEXT, IN `p_Treatment` TEXT, IN `p_FollowUp` TEXT)   BEGIN
    INSERT INTO DischargeSummary (PatientID, Diagnosis, Treatment, FollowUp)
    VALUES (p_PatientID, p_Diagnosis, p_Treatment, p_FollowUp);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `InsertIntrusionAlert` (IN `p_UserID` VARCHAR(100), IN `p_AlertType` VARCHAR(100), IN `p_Description` TEXT)   BEGIN
    INSERT INTO IntrusionAlerts (UserID, AlertType, Description)
    VALUES (p_UserID, p_AlertType, p_Description);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `InsertLabReport` (IN `p_SummaryID` INT, IN `p_ReportType` VARCHAR(100), IN `p_FilePath` VARCHAR(255))   BEGIN
    INSERT INTO LabReports (SummaryID, ReportType, FilePath)
    VALUES (p_SummaryID, p_ReportType, p_FilePath);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `insert_encrypted_patient` (IN `p_name` VARCHAR(100), IN `p_medical_history` TEXT, IN `p_prescriptions` TEXT)   BEGIN
    INSERT INTO patients (name, medical_history, prescriptions)
    VALUES (
        p_name,
        AES_ENCRYPT(p_medical_history, 'clinic_enc_key_2024'),
        AES_ENCRYPT(p_prescriptions, 'clinic_enc_key_2024')
    );
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `LogLoginAttempt` (IN `p_UserID` VARCHAR(100), IN `p_Success` BOOLEAN, IN `p_IPAddress` VARCHAR(45))   BEGIN
    INSERT INTO LoginAttempts (UserID, Success, IPAddress)
    VALUES (p_UserID, p_Success, p_IPAddress);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `UpdateDischargeSummary` (IN `p_SummaryID` INT, IN `p_Diagnosis` TEXT, IN `p_Treatment` TEXT, IN `p_FollowUp` TEXT)   BEGIN
    UPDATE DischargeSummary
    SET
        Diagnosis = p_Diagnosis,
        Treatment = p_Treatment,
        FollowUp = p_FollowUp,
        CreatedAt = CURRENT_TIMESTAMP
    WHERE SummaryID = p_SummaryID;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `UpdateSessionActivity` (IN `p_SessionID` VARCHAR(100), IN `p_UserID` VARCHAR(100))   BEGIN
    INSERT INTO UserSessions (SessionID, UserID, LastActivity, IsActive)
    VALUES (p_SessionID, p_UserID, CURRENT_TIMESTAMP, TRUE)
    ON DUPLICATE KEY UPDATE LastActivity = CURRENT_TIMESTAMP, IsActive = TRUE;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `AppointmentID` int(11) NOT NULL,
  `PatientID` int(11) NOT NULL,
  `DoctorID` int(11) NOT NULL,
  `AppointmentDate` date NOT NULL,
  `AppointmentTime` time NOT NULL,
  `Status` enum('Scheduled','Completed','Cancelled') DEFAULT 'Scheduled',
  `ServiceType` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`AppointmentID`, `PatientID`, `DoctorID`, `AppointmentDate`, `AppointmentTime`, `Status`, `ServiceType`) VALUES
(6, 1, 2, '2025-08-07', '14:30:00', 'Scheduled', 'surgery'),
(7, 1, 2, '2025-08-14', '14:35:00', 'Scheduled', 'surgery'),
(8, 1, 2, '2025-08-14', '15:40:00', 'Scheduled', 'surgery');

-- --------------------------------------------------------

--
-- Table structure for table `appointmentstatuslogs`
--

CREATE TABLE `appointmentstatuslogs` (
  `StatusID` int(11) NOT NULL,
  `AppointmentID` int(11) DEFAULT NULL,
  `UpdatedByReceptionistID` int(11) DEFAULT NULL,
  `Status` enum('Checked-in','Cancelled','No-show') DEFAULT NULL,
  `UpdateTime` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `appuser`
--

CREATE TABLE `appuser` (
  `userid` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `roleid` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `appusers`
--

CREATE TABLE `appusers` (
  `UserID` int(11) NOT NULL,
  `Username` varchar(100) DEFAULT NULL,
  `Password` varchar(100) DEFAULT NULL,
  `RoleID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appusers`
--

INSERT INTO `appusers` (`UserID`, `Username`, `Password`, `RoleID`) VALUES
(1, 'doctor1', '123456', 1),
(2, 'nurse1', 'abc123', 2),
(3, 'patient1', 'mypassword', 3),
(4, 'admin1', 'adminpass', 4),
(5, 'ganesh11', '123456', 4);

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `action_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`log_id`, `user_id`, `action`, `action_time`, `ip_address`) VALUES
(0, 1, 'login', '2025-08-10 02:02:55', '::1'),
(0, 1, 'login', '2025-08-10 05:08:12', '::1'),
(0, 1, 'login', '2025-08-10 05:08:55', '::1'),
(0, 3, 'login', '2025-08-10 05:09:05', '::1'),
(0, 3, 'login', '2025-08-10 05:09:54', '::1'),
(0, 3, 'login', '2025-08-10 05:09:54', '::1'),
(0, 1, 'login', '2025-08-10 05:10:41', '::1'),
(0, 3, 'login', '2025-08-10 05:12:36', '::1'),
(0, 2, 'login', '2025-08-10 05:13:32', '::1');

-- --------------------------------------------------------

--
-- Table structure for table `dischargesummary`
--

CREATE TABLE `dischargesummary` (
  `SummaryID` int(11) NOT NULL,
  `PatientID` int(11) NOT NULL,
  `Diagnosis` text DEFAULT NULL,
  `Treatment` text DEFAULT NULL,
  `FollowUp` text DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dischargesummary`
--

INSERT INTO `dischargesummary` (`SummaryID`, `PatientID`, `Diagnosis`, `Treatment`, `FollowUp`, `CreatedAt`) VALUES
(60, 1, 'hagha', 'HGZAHGh', 'hzgxh', '2025-08-05 02:09:30'),
(61, 1, 'jsja', 'jahxjah', 'jhxjah\n', '2025-08-05 02:09:49'),
(62, 1, 'bp low', 'salt', 'next week', '2025-08-05 02:15:16'),
(80, 1, 'sugar', 'sugar', 'sugar', '2025-08-05 02:29:24'),
(94, 1, 'aashish', 'ashish', 'ajshaih', '2025-08-05 02:47:51'),
(95, 1, 'bp', 'jshdj', 'kdsjks', '2025-08-05 03:14:30');

--
-- Triggers `dischargesummary`
--
DELIMITER $$
CREATE TRIGGER `trg_DischargeSummary_Update` BEFORE UPDATE ON `dischargesummary` FOR EACH ROW BEGIN
    INSERT INTO DischargeSummaryAudit(SummaryID, Action, UserID, OldDiagnosis, NewDiagnosis)
    VALUES (OLD.SummaryID, 'UPDATE', CURRENT_USER(), OLD.Diagnosis, NEW.Diagnosis);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `dischargesummaryaudit`
--

CREATE TABLE `dischargesummaryaudit` (
  `AuditID` int(11) NOT NULL,
  `SummaryID` int(11) DEFAULT NULL,
  `Action` varchar(10) DEFAULT NULL,
  `UserID` varchar(100) DEFAULT NULL,
  `ActionTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `OldDiagnosis` text DEFAULT NULL,
  `NewDiagnosis` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dischargesummaryaudit`
--

INSERT INTO `dischargesummaryaudit` (`AuditID`, `SummaryID`, `Action`, `UserID`, `ActionTime`, `OldDiagnosis`, `NewDiagnosis`) VALUES
(1, 60, 'UPDATE', 'root@localhost', '2025-08-10 02:09:42', 'Test Diagnosis', 'hagha');

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

CREATE TABLE `doctors` (
  `DoctorID` int(11) NOT NULL,
  `FullName` varchar(100) NOT NULL,
  `Specialization` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctors`
--

INSERT INTO `doctors` (`DoctorID`, `FullName`, `Specialization`) VALUES
(0, 'GaneshShahi', 'surgery');

-- --------------------------------------------------------

--
-- Table structure for table `intrusionalerts`
--

CREATE TABLE `intrusionalerts` (
  `AlertID` int(11) NOT NULL,
  `UserID` varchar(100) DEFAULT NULL,
  `AlertType` varchar(100) DEFAULT NULL,
  `AlertTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `Description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `labreports`
--

CREATE TABLE `labreports` (
  `ReportID` int(11) NOT NULL,
  `SummaryID` int(11) NOT NULL,
  `ReportType` varchar(100) DEFAULT NULL,
  `FilePath` varchar(255) DEFAULT NULL,
  `UploadedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `labreports`
--

INSERT INTO `labreports` (`ReportID`, `SummaryID`, `ReportType`, `FilePath`, `UploadedAt`) VALUES
(80, 60, 'blood', 'kdmckd', '2025-08-05 02:21:13');

-- --------------------------------------------------------

--
-- Table structure for table `loginattempts`
--

CREATE TABLE `loginattempts` (
  `AttemptID` int(11) NOT NULL,
  `UserID` varchar(100) DEFAULT NULL,
  `AttemptTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `Success` tinyint(1) DEFAULT NULL,
  `IPAddress` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loginattempts`
--

INSERT INTO `loginattempts` (`AttemptID`, `UserID`, `AttemptTime`, `Success`, `IPAddress`) VALUES
(1, '1', '2025-08-10 01:44:34', 1, '::1'),
(3, '3', '2025-08-10 01:45:24', 1, '::1'),
(4, '1', '2025-08-10 01:51:19', 1, '::1'),
(5, '1', '2025-08-10 01:51:37', 1, '::1');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `NotificationID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `MessageType` enum('Reminder','Alert','Update') DEFAULT NULL,
  `MessageContent` text DEFAULT NULL,
  `CreatedAt` datetime DEFAULT current_timestamp(),
  `IsRead` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `token_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(128) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `PatientID` int(11) NOT NULL,
  `FirstName` varchar(100) DEFAULT NULL,
  `LastName` varchar(100) DEFAULT NULL,
  `DOB` date DEFAULT NULL,
  `Gender` varchar(10) DEFAULT NULL,
  `ContactInfo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`PatientID`, `FirstName`, `LastName`, `DOB`, `Gender`, `ContactInfo`) VALUES
(1, 'Test', 'User', '2000-01-01', 'Male', '123456789'),
(2, 'ganesh', 'shahi', '2004-04-22', 'Male', '213131331');

-- --------------------------------------------------------

--
-- Table structure for table `receptiondashboardaccess`
--

CREATE TABLE `receptiondashboardaccess` (
  `AccessID` int(11) NOT NULL,
  `ReceptionistID` int(11) DEFAULT NULL,
  `PatientID` int(11) DEFAULT NULL,
  `AccessDate` datetime DEFAULT current_timestamp(),
  `ActionPerformed` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `receptionists`
--

CREATE TABLE `receptionists` (
  `ReceptionistID` int(11) NOT NULL,
  `FullName` varchar(100) NOT NULL,
  `Username` varchar(50) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Role` varchar(50) DEFAULT NULL,
  `ShiftTiming` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `receptionists`
--

INSERT INTO `receptionists` (`ReceptionistID`, `FullName`, `Username`, `Password`, `Role`, `ShiftTiming`) VALUES
(1, 'Aashish Kumar Chhetri', 'aashish', '$2y$10$Aa.N34gfQKgRiaJ3.8iNu.OaUaGPxQVaqxFVLwGIbIUNj0kkJ2YO6', 'Receptionist', 'Morning Shift'),
(2, 'Ganesh', 'Ganesh', 'hello', 'Receptionist', 'Morning Shift'),
(0, 'ganesh', 'ganesh11', '123456', 'Receptionist', 'morning');

-- --------------------------------------------------------

--
-- Table structure for table `receptionistshifts`
--

CREATE TABLE `receptionistshifts` (
  `ShiftID` int(11) NOT NULL,
  `ReceptionistID` int(11) DEFAULT NULL,
  `ShiftDate` date DEFAULT NULL,
  `StartTime` time DEFAULT NULL,
  `EndTime` time DEFAULT NULL,
  `Status` enum('Assigned','Accepted','Requested Swap') DEFAULT 'Assigned'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `receptionnotifications`
--

CREATE TABLE `receptionnotifications` (
  `NotificationID` int(11) NOT NULL,
  `ReceptionistID` int(11) DEFAULT NULL,
  `MessageContent` text DEFAULT NULL,
  `MessageType` enum('Shift Change','Missed Appointment','Alert') DEFAULT NULL,
  `CreatedAt` datetime DEFAULT current_timestamp(),
  `IsRead` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `receptionnotifications`
--

INSERT INTO `receptionnotifications` (`NotificationID`, `ReceptionistID`, `MessageContent`, `MessageType`, `CreatedAt`, `IsRead`) VALUES
(1, NULL, 'The system will be down from 10 PM to 12 AM tonight.', '', '2025-08-05 15:08:14', 0),
(2, NULL, 'You can now schedule appointments online.', '', '2025-08-05 15:08:14', 0);

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `RoleID` int(11) NOT NULL,
  `RoleName` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`RoleID`, `RoleName`) VALUES
(4, 'Admin'),
(1, 'Doctor'),
(2, 'Nurse'),
(3, 'Patient');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `session_id` varchar(128) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`session_id`, `user_id`, `ip_address`, `user_agent`, `expires_at`, `created_at`) VALUES
('0f992c3d067179b4a2b431deb42cbdfa87bb43da6ab31d946981b50010b21fc3', 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '2025-08-11 07:09:05', '2025-08-10 05:09:05'),
('51427d178db9d852b9ff5c2a643dfee99bd6a11dacd86056d4cd9ffaad3ac143', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '2025-08-11 04:02:55', '2025-08-10 02:02:55'),
('54c57c74d9d138d8c46125d463b594867e3e2eefd67e9abd0676fd5136c05418', 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '2025-08-11 07:12:36', '2025-08-10 05:12:36'),
('5e203a6f82cf2463999cdff532be240bfb2db1d19d42308f4e6239def48055ec', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '2025-08-11 07:13:32', '2025-08-10 05:13:32'),
('8fcfc2f411d15ca6f52130c1cba7b919892767294cc2fe7c162de45eb1574f46', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '2025-08-11 07:08:55', '2025-08-10 05:08:55'),
('a2e5b51858f520db04e3c1454913dd4f2a782121167598720f49fc0fbd95b19a', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '2025-08-11 07:10:41', '2025-08-10 05:10:41'),
('c505faf059454ca04569337f3f37fbed030a8dbdff1a50437a68c147b31325a4', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '2025-08-11 07:08:12', '2025-08-10 05:08:12'),
('f0c78905f8c40dc7093fcc1b90e1d49d63b752299b786ccdd9de5b528bc7512f', 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '2025-08-11 07:09:54', '2025-08-10 05:09:54'),
('f176efe91a7caadfc9c9865618bbd8b8aa1977b88878db620e23fbe9becfc8f7', 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '2025-08-11 07:09:54', '2025-08-10 05:09:54'),
('f8bef4eeae237f88f0345bb1bf6629e002ab88d816229befafc2379824388172', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '2025-08-11 04:00:39', '2025-08-10 02:00:39');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `RoleID` int(11) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `RoleID`, `full_name`) VALUES
(1, 'dr_john', 'c7ef8fc860e6b06ce37526b3e361700d', 1, 'Dr. John Smith'),
(2, 'nurse_amy', 'f1a89f6f9e93ed813dded0135b82833d', 2, 'Nurse Amy Lee'),
(3, 'patient_joe', 'c63f24079f1d5e4cae3fdc1a29116a7b', 3, 'Joe Brown'),
(4, 'admin1', 'adminpass', 4, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `usersessions`
--

CREATE TABLE `usersessions` (
  `SessionID` varchar(100) NOT NULL,
  `UserID` varchar(100) DEFAULT NULL,
  `LastActivity` timestamp NOT NULL DEFAULT current_timestamp(),
  `IsActive` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `visitlogs`
--

CREATE TABLE `visitlogs` (
  `LogID` int(11) NOT NULL,
  `PatientID` int(11) NOT NULL,
  `AppointmentID` int(11) NOT NULL,
  `CheckInTime` datetime DEFAULT NULL,
  `CheckOutTime` datetime DEFAULT NULL,
  `ReceptionistID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `visitorlogs`
--

CREATE TABLE `visitorlogs` (
  `VisitorID` int(11) NOT NULL,
  `FullName` varchar(100) DEFAULT NULL,
  `VisitPurpose` varchar(100) DEFAULT NULL,
  `EntryTime` datetime DEFAULT current_timestamp(),
  `LinkedToPatientID` int(11) DEFAULT NULL,
  `LinkedToDoctorID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`AppointmentID`),
  ADD KEY `PatientID` (`PatientID`),
  ADD KEY `DoctorID` (`DoctorID`);

--
-- Indexes for table `appointmentstatuslogs`
--
ALTER TABLE `appointmentstatuslogs`
  ADD PRIMARY KEY (`StatusID`);

--
-- Indexes for table `appuser`
--
ALTER TABLE `appuser`
  ADD PRIMARY KEY (`userid`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `roleid` (`roleid`);

--
-- Indexes for table `appusers`
--
ALTER TABLE `appusers`
  ADD PRIMARY KEY (`UserID`),
  ADD UNIQUE KEY `Username` (`Username`),
  ADD KEY `RoleID` (`RoleID`);

--
-- Indexes for table `dischargesummary`
--
ALTER TABLE `dischargesummary`
  ADD PRIMARY KEY (`SummaryID`),
  ADD KEY `PatientID` (`PatientID`);

--
-- Indexes for table `dischargesummaryaudit`
--
ALTER TABLE `dischargesummaryaudit`
  ADD PRIMARY KEY (`AuditID`),
  ADD KEY `SummaryID` (`SummaryID`);

--
-- Indexes for table `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`DoctorID`);

--
-- Indexes for table `intrusionalerts`
--
ALTER TABLE `intrusionalerts`
  ADD PRIMARY KEY (`AlertID`);

--
-- Indexes for table `labreports`
--
ALTER TABLE `labreports`
  ADD PRIMARY KEY (`ReportID`),
  ADD KEY `SummaryID` (`SummaryID`);

--
-- Indexes for table `loginattempts`
--
ALTER TABLE `loginattempts`
  ADD PRIMARY KEY (`AttemptID`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`NotificationID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`token_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`PatientID`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`RoleID`),
  ADD UNIQUE KEY `RoleName` (`RoleName`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `RoleID` (`RoleID`);

--
-- Indexes for table `usersessions`
--
ALTER TABLE `usersessions`
  ADD PRIMARY KEY (`SessionID`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appuser`
--
ALTER TABLE `appuser`
  MODIFY `userid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `appusers`
--
ALTER TABLE `appusers`
  MODIFY `UserID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `dischargesummary`
--
ALTER TABLE `dischargesummary`
  MODIFY `SummaryID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=96;

--
-- AUTO_INCREMENT for table `dischargesummaryaudit`
--
ALTER TABLE `dischargesummaryaudit`
  MODIFY `AuditID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `intrusionalerts`
--
ALTER TABLE `intrusionalerts`
  MODIFY `AlertID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `labreports`
--
ALTER TABLE `labreports`
  MODIFY `ReportID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `loginattempts`
--
ALTER TABLE `loginattempts`
  MODIFY `AttemptID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `token_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `PatientID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `RoleID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `user_roles`
--
ALTER TABLE `user_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appuser`
--
ALTER TABLE `appuser`
  ADD CONSTRAINT `appuser_ibfk_1` FOREIGN KEY (`roleid`) REFERENCES `roles` (`RoleID`);

--
-- Constraints for table `appusers`
--
ALTER TABLE `appusers`
  ADD CONSTRAINT `appusers_ibfk_1` FOREIGN KEY (`RoleID`) REFERENCES `roles` (`RoleID`);

--
-- Constraints for table `dischargesummary`
--
ALTER TABLE `dischargesummary`
  ADD CONSTRAINT `dischargesummary_ibfk_1` FOREIGN KEY (`PatientID`) REFERENCES `patients` (`PatientID`);

--
-- Constraints for table `dischargesummaryaudit`
--
ALTER TABLE `dischargesummaryaudit`
  ADD CONSTRAINT `dischargesummaryaudit_ibfk_1` FOREIGN KEY (`SummaryID`) REFERENCES `dischargesummary` (`SummaryID`);

--
-- Constraints for table `labreports`
--
ALTER TABLE `labreports`
  ADD CONSTRAINT `labreports_ibfk_1` FOREIGN KEY (`SummaryID`) REFERENCES `dischargesummary` (`SummaryID`);

--
-- Constraints for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD CONSTRAINT `password_reset_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `appusers` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `appusers` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`RoleID`) REFERENCES `roles` (`RoleID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `user_roles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `appusers` (`UserID`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`RoleID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
