<?php
// backend/ClinicSecurity.php
// Adapted for CapitalHealthDB and the existing tables: AppUsers, Roles, Patients, DischargeSummary, LabReports

declare(strict_types=1);

namespace CapitalHealth;

use PDO;
use PDOException;

class ClinicSecurity {
    private PDO $pdo;
    private bool $usePasswordHash; // set true if AppUsers stores hashed password in column PasswordHash
    private string $dbName = 'CapitalHealthDB';

    public function __construct(bool $usePasswordHash = false) {
        $this->usePasswordHash = $usePasswordHash;
        $host = 'localhost';
        $db   = $this->dbName;
        $user = 'root';
        $pass = '';
        $charset = 'utf8mb4';

        $dsn = "mysql:host={$host};dbname={$db};charset={$charset}";

        try {
            $this->pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        } catch (PDOException $e) {
            // In production, log instead of echo
            die('DB Connection failed: ' . $e->getMessage());
        }
    }

    // ------------------------
    // Registration (optional)
    // ------------------------
    public function registerUser(string $username, string $password, string $email = null, int $roleId = null): array {
        if (!$username || !$password) {
            return ['success' => false, 'message' => 'Username and password required'];
        }

        // check exist
        $stmt = $this->pdo->prepare("SELECT UserID FROM AppUsers WHERE Username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) return ['success' => false, 'message' => 'User exists'];

        // hash if enabled
        if ($this->usePasswordHash) {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $sql = "INSERT INTO AppUsers (Username, PasswordHash) VALUES (?, ?)";
            $params = [$username, $hash];
        } else {
            // storing plaintext is insecure; only for local testing
            $sql = "INSERT INTO AppUsers (Username, Password) VALUES (?, ?)";
            $params = [$username, $password];
        }

        $this->pdo->prepare($sql)->execute($params);
        $userId = (int)$this->pdo->lastInsertId();

        // assign role: if RoleID provided, use it; else try default from AppUsers.RoleID column if exists
        if ($roleId) {
            // insert into user_roles for many-to-many
            $this->assignRole($userId, $RoleId);
        }

        return ['success' => true, 'user_id' => $userId];
    }

    // ------------------------
    // Login (creates server-side session)
    // ------------------------
    public function login(string $username, string $password): array {
        // fetch user
        $col = $this->usePasswordHash ? 'PasswordHash' : 'Password';

        $stmt = $this->pdo->prepare("SELECT UserID, Username, {$col} as pw, RoleID FROM AppUsers WHERE Username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user) return ['success' => false, 'message' => 'Invalid credentials'];

        // verify password
        if ($this->usePasswordHash) {
            if (!password_verify($password, $user['pw'])) {
                $this->recordFailedLogin($user['UserID']);
                return ['success' => false, 'message' => 'Invalid credentials'];
            }
        } else {
            if ($password !== $user['pw']) {
                $this->recordFailedLogin($user['UserID']);
                return ['success' => false, 'message' => 'Invalid credentials'];
            }
        }

        // success: create session row
        $sessionId = $this->createSession((int)$user['UserID']);

        // store session cookie (HTTP only)
        setcookie('session_id', $sessionId, time() + 86400, '/', '', false, true);

        // log activity
        $this->logActivity((int)$user['UserID'], 'login', 'User logged in');

        return ['success' => true, 'session_id' => $sessionId, 'user_id' => (int)$user['UserID']];
    }

    private function recordFailedLogin(int $userId): void {
        // increment failed attempts column if present
        try {
            $this->pdo->prepare("UPDATE AppUsers SET FailedAttempts = IFNULL(FailedAttempts,0) + 1 WHERE UserID = ?")
                      ->execute([$userId]);

            $stmt = $this->pdo->prepare("SELECT FailedAttempts FROM AppUsers WHERE UserID = ?");
            $stmt->execute([$userId]);
            $attempts = (int)$stmt->fetchColumn();

            if ($attempts >= 5) {
                $lockUntil = date('Y-m-d H:i:s', time() + 1800);
                $this->pdo->prepare("UPDATE AppUsers SET AccountLocked = 1, LockTime = ? WHERE UserID = ?")
                          ->execute([$lockUntil, $userId]);
            }
        } catch (\Exception $e) {
            // ignore if columns don't exist
        }
    }

    // ------------------------
    // Session management
    // ------------------------
    public function createSession(int $userId): string {
        $sessionId = bin2hex(random_bytes(32));
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $expires = date('Y-m-d H:i:s', time() + 86400);

        $this->pdo->prepare("INSERT INTO sessions (session_id, user_id, ip_address, user_agent, expires_at) VALUES (?, ?, ?, ?, ?)")
                  ->execute([$sessionId, $userId, $ip, $ua, $expires]);

        return $sessionId;
    }

    public function validateSession(string $sessionId) {
        if (!$sessionId) return false;
        $this->cleanExpiredSessions();
        $stmt = $this->pdo->prepare("SELECT u.* FROM sessions s JOIN AppUsers u ON s.user_id = u.UserID WHERE s.session_id = ? AND s.expires_at > NOW()");
        $stmt->execute([$sessionId]);
        return $stmt->fetch() ?: false;
    }

    public function logoutSession(string $sessionId): void {
        $this->pdo->prepare("DELETE FROM sessions WHERE session_id = ?")->execute([$sessionId]);
        setcookie('session_id', '', time()-3600, '/', '', false, true);
    }

    private function cleanExpiredSessions(): void {
        $this->pdo->prepare("DELETE FROM sessions WHERE expires_at <= NOW()")->execute();
    }

    // ------------------------
    // RBAC helpers
    // ------------------------
    public function assignRole(int $userId, int $roleId): bool {
        try {
            // try to insert into user_roles
            $this->pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)")->execute([$userId, $roleId]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function userHasRole(int $userId, string $roleName): bool {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM user_roles ur
            JOIN Roles r ON ur.role_id = r.RoleID
            WHERE ur.user_id = ? AND r.RoleName = ?
        ");
        $stmt->execute([$userId, $roleName]);
        return ((int)$stmt->fetchColumn()) > 0;
    }

    // Convenience: role via AppUsers.RoleID fallback
    public function getRoleNameByUserId(int $userId) {
        $stmt = $this->pdo->prepare("
            SELECT r.RoleName
            FROM AppUsers u
            LEFT JOIN Roles r ON u.RoleID = r.RoleID
            WHERE u.UserID = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn() ?: null;
    }

    // ------------------------
    // Audit logging
    // ------------------------
   public function logActivity($userId, $action) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $this->pdo->prepare("
        INSERT INTO audit_logs (user_id, action, ip_address) 
        VALUES (?, ?, ?)
    ")->execute([$userId, $action, $ip]);
}


    public function getAuditLogs(int $limit = 100): array {
        $stmt = $this->pdo->prepare("
            SELECT a.*, u.Username
            FROM audit_logs a
            LEFT JOIN AppUsers u ON a.user_id = u.UserID
            ORDER BY a.action_time DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    // ------------------------
    // Password management
    // ------------------------
    public function changePassword(int $userId, string $currentPassword, string $newPassword): array {
        $col = $this->usePasswordHash ? 'PasswordHash' : 'Password';
        $stmt = $this->pdo->prepare("SELECT {$col} FROM AppUsers WHERE UserID = ?");
        $stmt->execute([$userId]);
        $stored = $stmt->fetchColumn();

        if ($this->usePasswordHash) {
            if (!password_verify($currentPassword, $stored)) {
                return ['success' => false, 'message' => 'Current password is incorrect'];
            }
            $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
            $this->pdo->prepare("UPDATE AppUsers SET PasswordHash = ? WHERE UserID = ?")->execute([$newHash, $userId]);
        } else {
            if ($currentPassword !== $stored) return ['success' => false, 'message' => 'Current password incorrect'];
            $this->pdo->prepare("UPDATE AppUsers SET Password = ? WHERE UserID = ?")->execute([$newPassword, $userId]);
        }

        $this->logActivity($userId, 'password_change', 'User changed password');
        return ['success' => true, 'message' => 'Password changed'];
    }

    // ------------------------
    // Password reset (store token)
    // ------------------------
    public function createPasswordResetToken(string $email): array {
        // find user id by email if column exists
        $stmt = $this->pdo->prepare("SELECT UserID FROM AppUsers WHERE Email = ?");
        $stmt->execute([$email]);
        $userId = $stmt->fetchColumn();
        if (!$userId) return ['success' => true]; // don't reveal

        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600);
        $this->pdo->prepare("INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)")->execute([$userId, $token, $expires]);

        // return token (in prod you'd send email)
        return ['success' => true, 'token' => $token];
    }
}
