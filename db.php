<?php
// Veritabanı bağlantı bilgileri
$db_host = "localhost"; // Genellikle localhost veya 127.0.0.1
$db_user = "root";      // MySQL kullanıcı adınız
$db_pass = "";          // MySQL şifreniz (genellikle boş veya 'root')
$db_name = "order_db";  // Oluşturduğumuz veritabanı adı

// Bağlantı oluşturma
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// Bağlantı kontrolü
if (!$conn) {
    die("Veritabanı bağlantısı başarısız: " . mysqli_connect_error());
}

// Session'ı başlat
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
