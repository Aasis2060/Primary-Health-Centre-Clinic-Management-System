<?php
/* 
 * Clinic Security Management System
 * Implements all 5 security requirements:
 * 1. User Authentication with 2FA
 * 2. Role-Based Access Control
 * 3. Password Management
 * 4. Audit Logging
 * 5. Session Management
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'capitalhealthdb');
define('DB_USER', 'root');
define('DB_PASS', '');

class ClinicSecurity {
    private $pdo;
    private $tfa;
    
    public function __construct() {
        // 1. Database Connection
        try {
            $this->pdo = new PDO(
                "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8", 
                DB_USER, 
                DB_PASS
            );
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
        
        // Load 2FA library
        require_once 'vendor/autoload.php';
        $this->tfa = new RobThree\Auth\TwoFactorAuth('ClinicSystem');
    }

    // ========================
    // 1. USER AUTHENTICATION
    // ========================
    public function registerUser($username, $email, $password) {
        // Validate input
        if (empty($username) || empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'All fields are required'];
        }
        
        // Check password strength
        if (!$this->validatePassword($password)) {
            return ['success' => false, 'message' => 'Password must be 8+ chars with uppercase, lowercase, number, and special char'];
        }
        
        // Check if user exists
        $stmt = $this->pdo->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Username or email already exists'];
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $secret = $this->tfa->createSecret();
        
        try {
            // Insert user
            $stmt = $this->pdo->prepare("
                INSERT INTO users (username, email, password_hash, two_factor_secret) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$username, $email, $hashedPassword, $secret]);
            
            // Get new user ID
            $userId = $this->pdo->lastInsertId();
            
            // Assign default role (adjust role_id as needed)
            $this->assignRole($userId, 2);
            
            return [
                'success' => true,
                'message' => 'Registration successful',
                'secret' => $secret,
                'qr_code' => $this->tfa->getQRCodeImageAsDataUri($username, $secret)
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Registration failed'];
        }
    }
    
    public function loginUser($username, $password, $totpCode) {
        // Get user
        $user = $this->getUserByUsername($username);
        if (!$user) {
            return ['success' => false, 'message' => 'Invalid credentials'];
        }
        
        // Check if account is locked
        if ($user['account_locked'] && strtotime($user['lock_time']) > time()) {
            return ['success' => false, 'message' => 'Account locked. Try again later.'];
        }
        
        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            $this->recordFailedLogin($user['user_id']);
            return ['success' => false, 'message' => 'Invalid credentials'];
        }
        
        // Verify 2FA code
        if (!$this->tfa->verifyCode($user['two_factor_secret'], $totpCode)) {
            $this->recordFailedLogin($user['user_id']);
            return ['success' => false, 'message' => 'Invalid 2FA code'];
        }
        
        // Successful login - reset attempts
        $this->resetFailedLogins($user['user_id']);
        
        // Create session
        $sessionId = $this->createSession($user['user_id']);
        
        // Log login
        $this->logActivity($user['user_id'], 'login', 'User logged in');
        
        return [
            'success' => true,
            'session_id' => $sessionId,
            'user_id' => $user['user_id']
        ];
    }
    
    private function recordFailedLogin($userId) {
        $this->pdo->prepare("UPDATE users SET failed_attempts = failed_attempts + 1 WHERE user_id = ?")
            ->execute([$userId]);
        
        // Lock account after 5 failed attempts
        $stmt = $this->pdo->prepare("SELECT failed_attempts FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $attempts = $stmt->fetchColumn();
        
        if ($attempts >= 5) {
            $lockTime = date('Y-m-d H:i:s', time() + 1800); // 30 minutes
            $this->pdo->prepare("UPDATE users SET account_locked = 1, lock_time = ? WHERE user_id = ?")
                ->execute([$lockTime, $userId]);
        }
    }
    
    // ========================
    // 2. ROLE-BASED ACCESS CONTROL
    // ========================
    public function assignRole($userId, $roleId) {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
            $stmt->execute([$userId, $roleId]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public function checkPermission($userId, $permissionName) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM user_roles ur
            JOIN roles r ON ur.role_id = r.role_id
            WHERE ur.user_id = ? AND r.role_name = ?
        ");
        $stmt->execute([$userId, $permissionName]);
        return $stmt->fetchColumn() > 0;
    }
    
    // ========================
    // 3. PASSWORD MANAGEMENT
    // ========================
    public function changePassword($userId, $currentPassword, $newPassword) {
        // Get current password
        $stmt = $this->pdo->prepare("SELECT password_hash FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $currentHash = $stmt->fetchColumn();
        
        // Verify current password
        if (!password_verify($currentPassword, $currentHash)) {
            return ['success' => false, 'message' => 'Current password is incorrect'];
        }
        
        // Validate new password
        if (!$this->validatePassword($newPassword)) {
            return ['success' => false, 'message' => 'New password does not meet requirements'];
        }
        
        // Update password
        $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
        $this->pdo->prepare("
            UPDATE users 
            SET password_hash = ?, password_last_changed = CURRENT_TIMESTAMP 
            WHERE user_id = ?
        ")->execute([$newHash, $userId]);
        
        // Log password change
        $this->logActivity($userId, 'password_change', 'User changed password');
        
        return ['success' => true, 'message' => 'Password changed successfully'];
    }
    
    public function requestPasswordReset($email) {
        // Check if user exists
        $stmt = $this->pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $userId = $stmt->fetchColumn();
        
        if (!$userId) {
            // Don't reveal if email exists
            return ['success' => true];
        }
        
        // Generate token
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour
        
        // Store token (would need to create password_reset_tokens table)
        $this->pdo->prepare("
            INSERT INTO password_reset_tokens (user_id, token, expires_at) 
            VALUES (?, ?, ?)
        ")->execute([$userId, $token, $expires]);
        
        // Return token (in real app, you would email this)
        return ['success' => true, 'token' => $token];
    }
    
    // ========================
    // 4. AUDIT LOGGING
    // ========================
    public function logActivity($userId, $action, $details = '') {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $this->pdo->prepare("
            INSERT INTO audit_logs (user_id, action, ip_address, details) 
            VALUES (?, ?, ?, ?)
        ")->execute([$userId, $action, $ip, $details]);
    }
    
    public function getAuditLogs($limit = 100) {
        $stmt = $this->pdo->prepare("
            SELECT a.*, u.username 
            FROM audit_logs a
            JOIN users u ON a.user_id = u.user_id
            ORDER BY a.action_time DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // ========================
    // 5. SESSION MANAGEMENT
    // ========================
    public function createSession($userId) {
        $sessionId = bin2hex(random_bytes(64));
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $expires = date('Y-m-d H:i:s', time() + 86400); // 24 hours
        
        $this->pdo->prepare("
            INSERT INTO sessions (session_id, user_id, ip_address, user_agent, expires_at) 
            VALUES (?, ?, ?, ?, ?)
        ")->execute([$sessionId, $userId, $ip, $userAgent, $expires]);
        
        return $sessionId;
    }
    
    public function validateSession($sessionId) {
        // Clean expired sessions first
        $this->cleanExpiredSessions();
        
        $stmt = $this->pdo->prepare("
            SELECT u.* FROM sessions s
            JOIN users u ON s.user_id = u.user_id
            WHERE s.session_id = ? AND s.expires_at > NOW()
        ");
        $stmt->execute([$sessionId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function logout($sessionId) {
        $this->pdo->prepare("DELETE FROM sessions WHERE session_id = ?")
            ->execute([$sessionId]);
    }
    
    // ========================
    // HELPER METHODS
    // ========================
    private function getUserByUsername($username) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function resetFailedLogins($userId) {
        $this->pdo->prepare("
            UPDATE users 
            SET failed_attempts = 0, account_locked = 0, lock_time = NULL 
            WHERE user_id = ?
        ")->execute([$userId]);
    }
    
    private function cleanExpiredSessions() {
        $this->pdo->exec("DELETE FROM sessions WHERE expires_at <= NOW()");
    }
    
    private function validatePassword($password) {
        return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password);
    }
}

// Initialize security system
$security = new ClinicSecurity();

// Helper functions for easy integration
function requireAuth() {
    global $security;
    $sessionId = $_COOKIE['session_id'] ?? '';
    $user = $security->validateSession($sessionId);
    if (!$user) {
        header("Location: login.php");
        exit();
    }
    return $user;
}

function requireRole($roleName) {
    $user = requireAuth();
    global $security;
    if (!$security->checkPermission($user['user_id'], $roleName)) {
        header("Location: unauthorized.php");
        exit();
    }
    return $user;
}
?>