<?php
include "db.php";

if (!isset($_SESSION['user_id'])) { die("Yetkisiz erişim!"); }

$user_id = $_SESSION['user_id'];
$offer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error_message = '';
$offer = null;
$items = [];

// Fetch products for the dropdown
$products_result = mysqli_query($conn, "SELECT * FROM products ORDER BY name ASC");
$products = [];
while ($row = mysqli_fetch_assoc($products_result)) {
    $products[] = $row;
}

if ($offer_id <= 0) {
    header("Location: dashboard.php");
    exit;
}

// Fetch existing offer and its items
$stmt = mysqli_prepare($conn, "SELECT * FROM offers WHERE id = ? AND user_id = ?");
mysqli_stmt_bind_param($stmt, "ii", $offer_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if (mysqli_num_rows($result) == 1) {
    $offer = mysqli_fetch_assoc($result);
    $item_stmt = mysqli_prepare($conn, "SELECT * FROM offer_items WHERE offer_id = ?");
    mysqli_stmt_bind_param($item_stmt, "i", $offer_id);
    mysqli_stmt_execute($item_stmt);
    $item_result = mysqli_stmt_get_result($item_stmt);
    while($row = mysqli_fetch_assoc($item_result)) {
        $items[] = $row;
    }
    mysqli_stmt_close($item_stmt);
} else {
    die("Hata: Teklif bulunamadı veya bu işlemi yapma yetkiniz yok.");
}
mysqli_stmt_close($stmt);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $customer_name = mysqli_real_escape_string($conn, $_POST['customer_name']);
    $descriptions = $_POST['description'];
    $quantities = $_POST['quantity'];
    $unit_prices = $_POST['unit_price'];

    if (empty($customer_name) || !isset($descriptions) || count($descriptions) == 0) {
        $error_message = "Lütfen müşteri adı ve en az bir ürün satırı ekleyin.";
    } else {
        mysqli_begin_transaction($conn);
        try {
            // 1. Delete old items
            $stmt_delete = mysqli_prepare($conn, "DELETE FROM offer_items WHERE offer_id = ?");
            mysqli_stmt_bind_param($stmt_delete, "i", $offer_id);
            mysqli_stmt_execute($stmt_delete);
            mysqli_stmt_close($stmt_delete);
            
            $total_amount = 0;

            // 2. Insert new items
            $stmt_item = mysqli_prepare($conn, "INSERT INTO offer_items (offer_id, description, quantity, unit_price) VALUES (?, ?, ?, ?)");
            for ($i = 0; $i < count($descriptions); $i++) {
                $desc = $descriptions[$i];
                $qty = (int)$quantities[$i];
                $price = (float)$unit_prices[$i];

                if (!empty($desc) && $qty > 0 && $price >= 0) {
                    mysqli_stmt_bind_param($stmt_item, "isid", $offer_id, $desc, $qty, $price);
                    mysqli_stmt_execute($stmt_item);
                    $total_amount += $qty * $price;
                }
            }
            mysqli_stmt_close($stmt_item);

            // 3. Update main offer record
            $stmt_update = mysqli_prepare($conn, "UPDATE offers SET customer_name = ?, total_amount = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt_update, "sdi", $customer_name, $total_amount, $offer_id);
            mysqli_stmt_execute($stmt_update);
            mysqli_stmt_close($stmt_update);

            mysqli_commit($conn);
            header("Location: dashboard.php?status=updated");
            exit;
        } catch (mysqli_sql_exception $exception) {
            mysqli_rollback($conn);
            $error_message = "Teklif güncellenirken bir veritabanı hatası oluştu: " . $exception->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teklifi Düzenle</title>
     <style>
        :root {
            --primary-color: #007bff;
            --secondary-color: #6c757d;
            --warning-color: #ffc107;
            --warning-hover-color: #e0a800;
            --danger-color: #dc3545;
            --background-color: #f8f9fa;
            --form-background-color: #ffffff;
            --text-color: #343a40;
            --border-color: #dee2e6;
        }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; background-color: var(--background-color); color: var(--text-color); margin: 0; padding: 20px; }
        .container { max-width: 900px; margin: 20px auto; padding: 30px; background: var(--form-background-color); border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        h2 { color: var(--text-color); margin-bottom: 25px; font-weight: 600; border-bottom: 1px solid var(--border-color); padding-bottom: 15px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 8px; font-weight: 500; font-size: 14px; }
        input, textarea, select { width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 5px; box-sizing: border-box; font-size: 16px; font-family: inherit; }
        input:focus, textarea:focus, select:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25); }
        .actions { margin-top: 30px; }
        .btn { padding: 12px 25px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; font-weight: 600; text-decoration: none; display: inline-block; text-align: center; transition: background-color 0.3s ease; }
        .btn-primary { background-color: var(--warning-color); color: #212529; }
        .btn-secondary { background-color: var(--secondary-color); color: white; }
        .alert-danger { padding: 15px; margin-bottom: 20px; border-radius: 5px; border: 1px solid #f5c6cb; background-color: #f8d7da; color: #721c24; }
        .btn-link { background: none; border: none; color: var(--primary-color); cursor: pointer; font-size: 14px; padding: 0; }
        
        #line-items-container .line-item { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 15px; padding: 15px; border: 1px solid var(--border-color); border-radius: 5px; }
        .line-item .form-group { flex: 1; min-width: 120px; }
        .line-item .item-product { flex-basis: 100%; }
        .line-item .item-description { flex: 3; min-width: 250px; }
        .line-item .item-actions { flex-shrink: 0; padding-top: 30px; }
        .btn-danger { background-color: var(--danger-color); color: white; padding: 8px 12px; font-size: 12px; }
        #add-item-btn { margin-top: 10px; }
    </style>
</head>
<body>

<div class="container">
    <h2>Teklifi Düzenle #<?php echo $offer_id; ?></h2>

    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <form method="POST" action="edit_offer.php?id=<?php echo $offer_id; ?>">
        <div class="form-group">
            <label for="customer_name">Müşteri Adı <span class="required-mark">*</span></label>
            <input type="text" id="customer_name" name="customer_name" value="<?php echo htmlspecialchars($offer['customer_name']); ?>" required>
        </div>
        <hr style="margin: 25px 0;">
        
        <h4>Ürün/Hizmet Kalemleri</h4>
        <div id="line-items-container">
            <?php foreach($items as $item): ?>
                <div class="line-item">
                    <div class="form-group item-product">
                        <label>Ürün Seç</label>
                        <select class="product-select">
                            <option value="">-- Manuel Giriş --</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group item-description">
                        <label>Açıklama</label>
                        <input type="text" name="description[]" class="item-desc" value="<?php echo htmlspecialchars($item['description']); ?>" required>
                    </div>
                    <div class="form-group item-quantity">
                        <label>Adet</label>
                        <input type="number" name="quantity[]" value="<?php echo htmlspecialchars($item['quantity']); ?>" class="item-qty" required>
                    </div>
                    <div class="form-group item-price">
                        <label>Birim Fiyat</label>
                        <input type="number" name="unit_price[]" step="0.01" value="<?php echo htmlspecialchars($item['unit_price']); ?>" class="item-price" required>
                    </div>
                    <div class="item-actions">
                        <button type="button" class="btn btn-danger remove-item-btn">Kaldır</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" id="add-item-btn" class="btn btn-secondary">Kalem Ekle</button>

        <div class="actions">
            <button type="submit" class="btn btn-primary">Değişiklikleri Kaydet</button>
            <a href="dashboard.php" class="btn btn-secondary">Geri Dön</a>
        </div>
    </form>
</div>

<template id="line-item-template">
    <div class="line-item">
        <div class="form-group item-product">
            <label>Ürün Seç</label>
            <select class="product-select">
                <option value="">-- Manuel Giriş --</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group item-description">
            <label>Açıklama</label>
            <input type="text" name="description[]" class="item-desc" required>
        </div>
        <div class="form-group item-quantity">
            <label>Adet</label>
            <input type="number" name="quantity[]" value="1" class="item-qty" required>
        </div>
        <div class="form-group item-price">
            <label>Birim Fiyat</label>
            <input type="number" name="unit_price[]" step="0.01" value="0.00" class="item-price" required>
        </div>
        <div class="item-actions">
            <button type="button" class="btn btn-danger remove-item-btn">Kaldır</button>
        </div>
    </div>
</template>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('line-items-container');
    const template = document.getElementById('line-item-template');
    const addItemBtn = document.getElementById('add-item-btn');
    
    let products = <?php echo json_encode($products); ?>;

    function addNewLineItem() {
        const clone = template.content.cloneNode(true);
        container.appendChild(clone);
    }

    addItemBtn.addEventListener('click', addNewLineItem);

    container.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-item-btn')) {
            e.target.closest('.line-item').remove();
        }
    });

    container.addEventListener('change', function(e) {
        if (e.target.classList.contains('product-select')) {
            const selectedProductId = e.target.value;
            const lineItem = e.target.closest('.line-item');
            const descInput = lineItem.querySelector('.item-desc');
            const priceInput = lineItem.querySelector('.item-price');
            
            if (selectedProductId) {
                const selectedProduct = products.find(p => p.id == selectedProductId);
                if (selectedProduct) {
                    descInput.value = selectedProduct.description;
                    priceInput.value = selectedProduct.unit_price;
                }
            }
        }
    });
});
</script>

</body>
</html>
