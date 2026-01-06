<?php
include "db.php";

// Kullanıcı girişi kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$offer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Teklif ID'si geçerli değilse veya belirtilmemişse, panele yönlendir
if ($offer_id <= 0) {
    header("Location: dashboard.php");
    exit;
}

// Silme işlemini sadece POST isteği ile yap (daha güvenli bir yöntem)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Veritabanından teklifi sil (güvenlik kontrolü ile)
    $stmt = mysqli_prepare($conn, "DELETE FROM offers WHERE id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $offer_id, $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        // Etkilenen satır sayısını kontrol et
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            header("Location: dashboard.php?status=deleted");
            exit;
        } else {
            // Silinecek bir şey bulunamadı (ya ID yanlış ya da kullanıcıya ait değil)
            die("Hata: Teklif bulunamadı veya bu işlemi yapma yetkiniz yok.");
        }
    } else {
        die("Hata: " . mysqli_error($conn));
    }
    mysqli_stmt_close($stmt);
} else {
    // POST dışındaki istekleri reddet
    die("Geçersiz istek yöntemi.");
}
?>
