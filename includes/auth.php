<?php
/**
 * Authentication and Session Management Helper
 * Barangay Health Outreach Management System
 * 
 * Supports persistent database-backed sessions across stateless serverless
 * environments (Vercel) connected to TiDB Cloud or MySQL.
 */

if (!isset($pdo)) {
    require_once __DIR__ . '/../config/database.php';
}

if (session_status() === PHP_SESSION_NONE) {
    // Configure secure session cookies
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    if (!headers_sent()) {
        session_set_cookie_params([
            'lifetime' => 86400 * 7, // 7 days retention
            'path'     => '/',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    // Attach DB session handler if PDO is connected
    if (isset($pdo) && $pdo instanceof PDO) {
        try {
            // Ensure sessions table exists in TiDB / MySQL
            $pdo->exec("CREATE TABLE IF NOT EXISTS sessions (
                id VARCHAR(128) NOT NULL PRIMARY KEY,
                data MEDIUMTEXT NOT NULL,
                last_activity INT UNSIGNED NOT NULL,
                KEY idx_last_activity (last_activity)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $handler = new class($pdo) implements SessionHandlerInterface {
                private PDO $db;
                public function __construct(PDO $db) { $this->db = $db; }
                public function open(string $path, string $name): bool { return true; }
                public function close(): bool { return true; }
                public function read(string $id): string|false {
                    try {
                        $stmt = $this->db->prepare("SELECT data FROM sessions WHERE id = :id LIMIT 1");
                        $stmt->execute([':id' => $id]);
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                        return $row ? (string)$row['data'] : '';
                    } catch (Throwable $e) {
                        return '';
                    }
                }
                public function write(string $id, string $data): bool {
                    try {
                        $stmt = $this->db->prepare("REPLACE INTO sessions (id, data, last_activity) VALUES (:id, :data, :act)");
                        return $stmt->execute([':id' => $id, ':data' => $data, ':act' => time()]);
                    } catch (Throwable $e) {
                        return false;
                    }
                }
                public function destroy(string $id): bool {
                    try {
                        $stmt = $this->db->prepare("DELETE FROM sessions WHERE id = :id");
                        return $stmt->execute([':id' => $id]);
                    } catch (Throwable $e) {
                        return false;
                    }
                }
                public function gc(int $max_lifetime): int|false {
                    try {
                        $cutoff = time() - $max_lifetime;
                        $stmt = $this->db->prepare("DELETE FROM sessions WHERE last_activity < :cutoff");
                        $stmt->execute([':cutoff' => $cutoff]);
                        return $stmt->rowCount();
                    } catch (Throwable $e) {
                        return false;
                    }
                }
            };
            session_set_save_handler($handler, true);
        } catch (Throwable $e) {
            error_log("Session DB handler error: " . $e->getMessage());
        }
    }

    session_start();
}

/**
 * Check if the user is currently authenticated
 */
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

/**
 * Get details of the currently authenticated user
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }

    return [
        'user_id'   => $_SESSION['user_id'] ?? null,
        'full_name' => $_SESSION['full_name'] ?? 'Barangay Staff',
        'username'  => $_SESSION['username'] ?? 'staff',
        'role'      => $_SESSION['role'] ?? 'Staff',
    ];
}

/**
 * Get user initials for avatar displays (e.g. "BS" for "Barangay Staff")
 */
function getUserInitials(?string $name = null): string {
    $name = $name ?? (getCurrentUser()['full_name'] ?? 'Barangay Staff');
    $parts = explode(' ', trim($name));
    if (count($parts) >= 2) {
        return strtoupper(substr($parts[0], 0, 1) . substr(end($parts), 0, 1));
    }
    return strtoupper(substr($name, 0, 2));
}

/**
 * Enforce authentication requirement
 */
function requireAuth(string $redirectUrl = 'login.php'): void {
    if (!isLoggedIn()) {
        header("Location: $redirectUrl");
        exit;
    }
}

/**
 * Enforce authorized roles
 */
function requireRole(array $allowedRoles): void {
    $user = getCurrentUser();
    if (!$user || !in_array($user['role'], $allowedRoles, true)) {
        http_response_code(403);
        die("Access Denied: You do not have permission to view this page.");
    }
}
