<?php
include "db.php";

$error_message = '';
$success_message = '';

// Eğer form POST edildiyse
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // Kullanıcının zaten var olup olmadığını kontrol et
    $check_q = "SELECT id FROM users WHERE email='$email'"; // Sadece id çekmek yeterli
    $check_res = mysqli_query($conn, $check_q);

    if (mysqli_num_rows($check_res) > 0) {
        $error_message = "Bu e-posta adresi zaten kayıtlı!";
    } else {
        // Parolayı hashle
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Yeni kullanıcıyı ekle
        $insert_stmt = mysqli_prepare($conn, "INSERT INTO users (email, password) VALUES (?, ?)");
        mysqli_stmt_bind_param($insert_stmt, "ss", $email, $hashed_password);
        
        if (mysqli_stmt_execute($insert_stmt)) {
            // Başarı mesajıyla giriş sayfasına yönlendir
            header("Location: login.php?registered=success");
            exit;
        } else {
            $error_message = "Kayıt sırasında bir hata oluştu: " . mysqli_error($conn);
        }
        mysqli_stmt_close($insert_stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Kullanıcı Oluştur</title>
    <style>
        :root {
            --primary-color: #007bff;
            --primary-hover-color: #0056b3;
            --success-color: #28a745;
            --success-hover-color: #218838;
            --background-color: #f4f7f6;
            --form-background-color: #ffffff;
            --text-color: #333;
            --input-border-color: #ccc;
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
        .register-container {
            background-color: var(--form-background-color);
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        .register-container h2 {
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
            background: var(--success-color);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }
        .btn:hover {
            background-color: var(--success-hover-color);
        }
        .alert {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-size: 14px;
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

<div class="register-container">
    <h2>Yeni Kullanıcı Oluştur</h2>

    <?php if ($error_message): ?>
        <div class="alert"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <form method="POST" action="register_user.php" novalidate>
        <div class="form-group">
            <label for="email">E-posta Adresi</label>
            <input type="email" id="email" name="email" required>
        </div>
        <div class="form-group">
            <label for="password">Parola</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" class="btn">Kayıt Ol</button>
    </form>
    <div class="form-footer">
        Zaten bir hesabınız var mı? <a href="login.php">Giriş Yap</a>
    </div>
</div>

</body>
</html>
