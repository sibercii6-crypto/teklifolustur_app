<?php
include "db.php";

// Kullanıcı girişi kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'];

$error_message = '';
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Alanların boş olup olmadığını kontrol et
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = "Tüm alanlar zorunludur.";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "Yeni parolalar eşleşmiyor.";
    } else {
        // Mevcut kullanıcı bilgilerini veritabanından al
        $stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        // Mevcut şifre kontrolü
        if ($user && password_verify($current_password, $user['password'])) {
            // Yeni şifreyi hashle
            $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Yeni şifreyi güncelle
            $update_stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
            mysqli_stmt_bind_param($update_stmt, "si", $hashed_new_password, $user_id);

            if (mysqli_stmt_execute($update_stmt)) {
                $success_message = "Parolanız başarıyla güncellendi!";
            } else {
                $error_message = "Parola güncellenirken bir hata oluştu.";
            }
            mysqli_stmt_close($update_stmt);
        } else {
            $error_message = "Mevcut parolanız yanlış.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Yönetimi</title>
    <style>
        :root {
            --primary-color: #007bff;
            --secondary-color: #6c757d;
            --background-color: #f8f9fa;
            --form-background-color: #ffffff;
            --text-color: #343a40;
            --border-color: #dee2e6;
        }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; 
            background-color: var(--background-color); 
            color: var(--text-color);
            margin: 0;
            padding: 20px;
        }
        .container { 
            max-width: 600px; 
            margin: 20px auto; 
            padding: 30px; 
            background: var(--form-background-color); 
            border-radius: 8px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); 
        }
        h2 { 
            color: var(--text-color); 
            margin-bottom: 10px;
            font-weight: 600;
        }
        p {
            color: #6c757d;
            margin-bottom: 25px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 15px;
        }
        .form-group { margin-bottom: 20px; }
        label { 
            display: block; 
            margin-bottom: 8px; 
            font-weight: 500;
            font-size: 14px;
        }
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            box-sizing: border-box;
            font-size: 16px;
        }
        input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
        }
        .actions { margin-top: 30px; }
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: background-color 0.3s ease;
        }
        .btn-primary { background-color: var(--primary-color); color: white; }
        .btn-primary:hover { background-color: #0056b3; }
        .btn-secondary { background-color: var(--secondary-color); color: white; }
        .btn-secondary:hover { background-color: #5a6268; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 5px; border: 1px solid transparent; }
        .alert-danger { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }
        .alert-success { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }
    </style>
</head>
<body>

<div class="container">
    <h2>Parolayı Değiştir</h2>
    <p>Giriş yapılan hesap: <strong><?php echo htmlspecialchars($user_email); ?></strong></p>
    
    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>
    <?php if ($success_message): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>

    <form method="POST" action="profile.php">
        <div class="form-group">
            <label for="current_password">Mevcut Parola</label>
            <input type="password" id="current_password" name="current_password" required>
        </div>
        <div class="form-group">
            <label for="new_password">Yeni Parola</label>
            <input type="password" id="new_password" name="new_password" required>
        </div>
        <div class="form-group">
            <label for="confirm_password">Yeni Parolayı Onayla</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
        </div>
        
        <div class="actions">
            <button type="submit" class="btn btn-primary">Parolayı Güncelle</button>
            <a href="dashboard.php" class="btn btn-secondary">Geri Dön</a>
        </div>
    </form>
</div>

</body>
</html>
