<?php
// c:\Users\SD Kristen Petra 1\Sistem SD Kristen Petra 1\logout.php

require_once __DIR__ . '/config/auth.php';

if (is_logged_in()) {
    // Log the logout activity before session destruction
    write_audit_log("Melakukan logout dari sistem.");

    // Remove token from database if cookie exists
    if (isset($_COOKIE['petra_remember'])) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET remember_token = NULL WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
        } catch (Exception $e) {
            error_log("Failed to clear remember token on logout: " . $e->getMessage());
        }

        // Delete remember cookie by setting expiration to past time
        setcookie('petra_remember', '', time() - 3600, '/');
    }
}

// Unset all session variables
$_SESSION = [];

// Destroy session cookie if set
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Redirect to login page
$redirect_url = "login.php";
if (isset($_GET['reason']) && $_GET['reason'] === 'timeout') {
    $redirect_url .= "?timeout=1";
}
header("Location: " . $redirect_url);
exit;
?>
