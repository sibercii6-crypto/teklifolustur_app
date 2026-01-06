<?php
include "db.php";

// Eğer kullanıcı giriş yapmamışsa, login sayfasına yönlendir
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'];

$success_message = '';
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'created') {
        $success_message = 'Teklif başarıyla oluşturuldu!';
    } elseif ($_GET['status'] == 'updated') {
        $success_message = 'Teklif başarıyla güncellendi!';
    } elseif ($_GET['status'] == 'deleted') {
        $success_message = 'Teklif başarıyla silindi!';
    }
}

// Arama işlevi
$search_term = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$sql_where = "WHERE user_id=$user_id";
if (!empty($search_term)) {
    $sql_where .= " AND customer_name LIKE '%$search_term%'";
}

?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teklif Paneli</title>
    <style>
        :root {
            --primary-color: #007bff;
            --primary-hover-color: #0056b3;
            --background-color: #f8f9fa;
            --container-bg-color: #ffffff;
            --text-color: #343a40;
            --border-color: #dee2e6;
            --success-color: #28a745;
            --danger-color: #dc3545;
        }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; 
            background-color: var(--background-color); 
            color: var(--text-color);
            margin: 0;
            padding: 20px;
        }
        .container { 
            max-width: 1000px; 
            margin: 20px auto; 
            padding: 30px; 
            background: var(--container-bg-color); 
            border-radius: 8px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); 
        }
        .header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 25px; 
            border-bottom: 1px solid var(--border-color); 
            padding-bottom: 20px; 
        }
        .header h2 { 
            color: var(--text-color); 
            margin: 0;
            font-weight: 600;
        }
        .user-info { 
            text-align: right;
            font-size: 14px;
        }
        .user-info a { 
            color: var(--primary-color);
            text-decoration: none;
            margin: 0 5px; 
        }
        .user-info a:hover { text-decoration: underline; }
        .toolbar { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 25px; 
        }
        .search-form { display: flex; }
        .search-form input { 
            padding: 10px; 
            border: 1px solid var(--border-color); 
            border-radius: 5px 0 0 5px; 
            min-width: 250px;
        }
        .search-form button, .search-form a { 
            padding: 10px 15px; 
            border: 1px solid var(--primary-color); 
            background-color: var(--primary-color); 
            color: white; 
            cursor: pointer;
            text-decoration: none;
        }
        .search-form button { border-radius: 0 5px 5px 0; }
        .search-form a { border-radius: 5px; margin-left: 10px; border-color: #6c757d; background-color: #6c757d;}

        .btn-create { 
            display: inline-block; 
            padding: 10px 20px; 
            background-color: var(--success-color); 
            color: white; 
            text-decoration: none; 
            border-radius: 5px; 
            font-size: 16px; 
            font-weight: 500;
            transition: background-color 0.3s; 
        }
        .btn-create:hover { background-color: #218838; }

        .table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px; 
        }
        .table th, .table td { 
            padding: 15px; 
            border-bottom: 1px solid var(--border-color); 
            text-align: left; 
            vertical-align: middle; 
        }
        .table th { 
            background-color: #f8f9fa; 
            font-weight: 600;
            font-size: 14px;
            color: #6c757d;
        }
        .table tr:hover { background-color: #f1f3f5; }
        .table .actions a, .table .actions .btn-delete {
            color: var(--primary-color);
            text-decoration: none;
            margin-right: 15px;
            font-size: 14px;
            font-weight: 500;
        }
        .btn-delete {
            background-color: transparent;
            border: none;
            color: var(--danger-color);
            cursor: pointer;
            padding: 0;
            display: inline;
        }
        .alert-success { 
            padding: 15px; 
            background-color: #d4edda; 
            color: #155724; 
            border: 1px solid #c3e6cb; 
            border-radius: 5px; 
            margin-bottom: 20px; 
        }
        .empty-state { 
            text-align: center; 
            padding: 50px; 
            color: #6c757d; 
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2>Teklif Paneli</h2>
        <div class="user-info">
            Hoşgeldin, <b><?php echo htmlspecialchars($user_email); ?></b> |
            <a href="profile.php">Profil</a> |
            <a href="logout.php">Çıkış Yap</a>
        </div>
    </div>

    <?php if ($success_message): ?>
        <div class="alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>
    
    <div class="toolbar">
        <a href="create_offer.php" class="btn-create">Yeni Teklif Oluştur</a>
        <form action="dashboard.php" method="GET" class="search-form">
            <input type="text" name="search" placeholder="Müşteri adıyla ara..." value="<?php echo htmlspecialchars($search_term); ?>">
            <button type="submit">Ara</button>
            <?php if (!empty($search_term)): ?>
                <a href="dashboard.php">Temizle</a>
            <?php endif; ?>
        </form>
    </div>

    <h3>Tekliflerim</h3>
    
    <table class="table">
        <thead>
            <tr>
                <th>Müşteri Adı</th>
                <th>Toplam Tutar</th>
                <th>Oluşturulma Tarihi</th>
                <th>İşlemler</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $q = "SELECT id, customer_name, total_amount, created_at FROM offers $sql_where ORDER BY created_at DESC";
            $res = mysqli_query($conn, $q);

            if (mysqli_num_rows($res) > 0) {
                while ($row = mysqli_fetch_assoc($res)) {
                    echo "<tr>
                            <td>" . htmlspecialchars($row['customer_name']) . "</td>
                            <td>" . number_format($row['total_amount'], 2, ',', '.') . " TL</td>
                            <td>" . date("d.m.Y", strtotime($row['created_at'])) . "</td>
                            <td class='actions'>
                                <a href='view_offer.php?id={$row['id']}'>Görüntüle</a>
                                <a href='edit_offer.php?id={$row['id']}'>Düzenle</a>
                                <form action='delete_offer.php?id={$row['id']}' method='POST' style='display: inline;' onsubmit=\"return confirm('Bu teklifi silmek istediğinizden emin misiniz?');\">
                                    <button type='submit' class='btn-delete'>Sil</button>
                                </form>
                            </td>
                          </tr>";
                }
            } else {
                echo '<tr><td colspan="4" class="empty-state">';
                if (!empty($search_term)) {
                    echo 'Aradığınız kriterlere uygun teklif bulunamadı.';
                } else {
                    echo 'Hiç teklifiniz bulunmuyor.';
                }
                echo '</td></tr>';
            }
            ?>
        </tbody>
    </table>

</div>

</body>
</html>
