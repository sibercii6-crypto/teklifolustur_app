<?php
include "db.php";

if (!isset($_SESSION['user_id'])) { die("Yetkisiz erişim!"); }

$offer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($offer_id <= 0) { die("Geçersiz Teklif ID'si"); }

// IDOR zafiyeti için user_id kontrolü yok, bu CTF'in bir parçası
$offer_stmt = mysqli_prepare($conn, "SELECT o.*, u.email FROM offers o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
mysqli_stmt_bind_param($offer_stmt, "i", $offer_id);
mysqli_stmt_execute($offer_stmt);
$offer_result = mysqli_stmt_get_result($offer_stmt);

$offer = null;
if (mysqli_num_rows($offer_result) == 1) {
    $offer = mysqli_fetch_assoc($offer_result);
} else {
    die("Teklif bulunamadı.");
}

// Teklife ait ürünleri çek
$items_stmt = mysqli_prepare($conn, "SELECT * FROM offer_items WHERE offer_id = ?");
mysqli_stmt_bind_param($items_stmt, "i", $offer_id);
mysqli_stmt_execute($items_stmt);
$items_result = mysqli_stmt_get_result($items_stmt);
$items = [];
while ($row = mysqli_fetch_assoc($items_result)) {
    $items[] = $row;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teklif Detayı #<?php echo $offer['id']; ?></title>
    <style>
        :root {
            --primary-color: #007bff;
            --secondary-color: #6c757d;
            --danger-color: #dc3545;
            --background-color: #f8f9fa;
            --container-bg-color: #ffffff;
            --text-color: #343a40;
            --border-color: #dee2e6;
        }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; background-color: var(--background-color); color: var(--text-color); margin: 0; padding: 20px; }
        .container { max-width: 800px; margin: 20px auto; padding: 40px; background: var(--container-bg-color); border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .offer-header { display: flex; justify-content: space-between; margin-bottom: 40px; }
        .offer-header .company-details h1, .offer-header .customer-details h3 { margin: 0; }
        .offer-header .company-details { text-align: left; }
        .offer-header .customer-details { text-align: right; }
        .offer-id { font-size: 24px; font-weight: 600; color: var(--primary-color); margin-bottom: 10px; }
        
        .offer-table { width: 100%; border-collapse: collapse; margin: 30px 0; }
        .offer-table th, .offer-table td { padding: 15px; border-bottom: 1px solid var(--border-color); text-align: left; }
        .offer-table th { background-color: #f8f9fa; font-weight: 600; }
        .offer-table .text-right { text-align: right; }
        
        .totals { width: 50%; margin-left: auto; margin-top: 20px; }
        .totals-table { width: 100%; }
        .totals-table td { padding: 10px 15px; }
        .totals-table .label { text-align: right; font-weight: 600; }
        .totals-table .value { text-align: right; }
        .grand-total { font-size: 1.2em; font-weight: bold; background-color: #f8f9fa; }

        .actions { margin-top: 40px; text-align: center; }
        .btn { padding: 12px 25px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; font-weight: 600; text-decoration: none; display: inline-block; text-align: center; transition: background-color 0.3s ease; margin: 0 5px; }
        .btn-pdf { background-color: var(--danger-color); color: white; }
        .btn-secondary { background-color: var(--secondary-color); color: white; }
    </style>
</head>
<body>

<div class="container">
    <div class="offer-header">
        <div class="company-details">
            <h1>Teklif</h1>
            <div class="offer-id">#TEKLIF-<?php echo str_pad($offer['id'], 4, '0', STR_PAD_LEFT); ?></div>
            <div>Oluşturan: <?php echo htmlspecialchars($offer['email']); ?></div>
            <div>Tarih: <?php echo date("d.m.Y", strtotime($offer['created_at'])); ?></div>
        </div>
        <div class="customer-details">
            <h3>Müşteri Bilgileri</h3>
            <div><?php echo htmlspecialchars($offer['customer_name']); ?></div>
        </div>
    </div>

    <table class="offer-table">
        <thead>
            <tr>
                <th>Açıklama</th>
                <th class="text-right">Adet</th>
                <th class="text-right">Birim Fiyat</th>
                <th class="text-right">Toplam</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $subtotal = 0;
            foreach ($items as $item): 
                $line_total = $item['quantity'] * $item['unit_price'];
                $subtotal += $line_total;
            ?>
            <tr>
                <td><?php echo nl2br(htmlspecialchars($item['description'])); ?></td>
                <td class="text-right"><?php echo htmlspecialchars($item['quantity']); ?></td>
                <td class="text-right"><?php echo number_format($item['unit_price'], 2, ',', '.'); ?> TL</td>
                <td class="text-right"><?php echo number_format($line_total, 2, ',', '.'); ?> TL</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="totals">
        <table class="totals-table">
            <tr class="grand-total">
                <td class="label">Genel Toplam</td>
                <td class="value"><?php echo number_format($offer['total_amount'], 2, ',', '.'); ?> TL</td>
            </tr>
        </table>
    </div>

    <div class="actions">
        <a href="generate_pdf.php?id=<?php echo $offer['id']; ?>" class="btn btn-pdf">PDF Olarak İndir</a>
        <a href="dashboard.php" class="btn btn-secondary">Panele Geri Dön</a>
    </div>
</div>

</body>
</html>
