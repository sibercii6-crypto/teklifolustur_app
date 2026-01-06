<?php
include "db.php";

$error_message = '';

// Eğer form POST edildiyse
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // E-posta ile kullanıcıyı çek, hashlenmiş parolayı al
    $stmt = mysqli_prepare($conn, "SELECT id, email, password FROM users WHERE email=?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($res) == 1) {
        $user = mysqli_fetch_assoc($res);
        // Girilen parolayı veritabanındaki hashlenmiş parolayla doğrula
        if (password_verify($password, $user['password'])) {
            // Kullanıcı bilgilerini session'a kaydet
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            // Dashboard'a yönlendir
            header("Location: dashboard.php");
            exit;
        } else {
            $error_message = "Geçersiz e-posta veya şifre!";
        }
    } else {
        $error_message = "Geçersiz e-posta veya şifre!";
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap</title>
    <style>
        :root {
            --primary-color: #007bff;
            --primary-hover-color: #0056b3;
            --background-color: #f4f7f6;
            --form-background-color: #ffffff;
            --text-color: #333;
            --input-border-color: #ccc;
            --success-color: #28a745;
            --error-color: #dc3545;
        }
        body, html {
            height: 100%;
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: var(--background-color);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background-color: var(--form-background-color);
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        .login-container h2 {
            color: var(--text-color);
            margin-bottom: 25px;
            font-weight: 600;
        }
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            font-size: 14px;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--input-border-color);
            border-radius: 5px;
            box-sizing: border-box;
            font-size: 16px;
        }
        .btn {
            width: 100%;
            padding: 12px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }
        .btn:hover {
            background-color: var(--primary-hover-color);
        }
        .alert {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-size: 14px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        .form-footer {
            margin-top: 25px;
            font-size: 14px;
        }
        .form-footer a {
            color: var(--primary-color);
            text-decoration: none;
        }
        .form-footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="login-container">
    <h2>Giriş Yap</h2>
    
    <?php 
        if (isset($_GET['registered']) && $_GET['registered'] == 'success') {
            echo '<div class="alert alert-success">Kayıt başarılı! Şimdi giriş yapabilirsiniz.</div>';
        }
        if ($error_message) {
            echo '<div class="alert alert-danger">' . htmlspecialchars($error_message) . '</div>';
        }
    ?>

    <form method="POST" action="login.php" novalidate>
        <div class="form-group">
            <label for="email">E-posta Adresi</label>
            <input type="email" id="email" name="email" required>
        </div>
        <div class="form-group">
            <label for="password">Parola</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" class="btn">Giriş Yap</button>
    </form>
    <div class="form-footer">
        Hesabınız yok mu? <a href="register_user.php">Yeni Kullanıcı Oluştur</a>
    </div>

</div>

</body>
</html>
