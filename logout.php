<?php
// Session'ı başlat
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Tüm session değişkenlerini temizle
$_SESSION = array();

// Session'ı yok et
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Login sayfasına yönlendir
header("Location: login.php");
exit;
?>
