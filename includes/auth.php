<?php
require_once 'session.php';

class Auth {
    public static function requireLogin() {
        if (!isLoggedIn()) {
            $_SESSION['error'] = 'Please login to access this page';
            header("Location: " . SITE_URL . "index.php");
            exit();
        }
    }
    
    public static function requireAdmin() {
        self::requireLogin();
        if (!isAdmin()) {
            $_SESSION['error'] = 'Access denied. Admin only.';
            header("Location: " . SITE_URL . "index.php");
            exit();
        }
    }
    
    public static function requireVoter() {
        self::requireLogin();
        if (!isVoter()) {
            $_SESSION['error'] = 'Access denied. Voter only.';
            header("Location: " . SITE_URL . "index.php");
            exit();
        }
    }
    
    public static function login($username, $password, $type = 'voter') {
        $db = Database::getInstance()->getConnection();
        
        if ($type == 'admin') {
            $query = "SELECT * FROM admins WHERE username = ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows == 1) {
                $admin = $result->fetch_assoc();
                // WAMP ma md5 use gareko cha (default admin password)
                if (md5($password) == $admin['password']) {
                    $_SESSION['user_id'] = $admin['id'];
                    $_SESSION['username'] = $admin['username'];
                    $_SESSION['user_type'] = 'admin';
                    $_SESSION['login_time'] = time();
                    return true;
                }
            }
        } else {
            $query = "SELECT * FROM voters WHERE (voter_id = ? OR email = ?)";
            $stmt = $db->prepare($query);
            $stmt->bind_param("ss", $username, $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows == 1) {
                $voter = $result->fetch_assoc();
                if (password_verify($password, $voter['password'])) {
                    $_SESSION['user_id'] = $voter['id'];
                    $_SESSION['voter_id'] = $voter['voter_id'];
                    $_SESSION['name'] = $voter['name'];
                    $_SESSION['user_type'] = 'voter';
                    $_SESSION['login_time'] = time();
                    return true;
                }
            }
        }
        
        return false;
    }
    
    public static function logout() {
        $_SESSION = array();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }
}

// Global functions for easy access
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
}

function isAdmin() {
    return isLoggedIn() && $_SESSION['user_type'] == 'admin';
}

function isVoter() {
    return isLoggedIn() && $_SESSION['user_type'] == 'voter';
}

function redirect($url) {
    header("Location: " . SITE_URL . $url);
    exit();
}
?>