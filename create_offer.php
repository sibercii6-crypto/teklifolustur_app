<?php
include "db.php";

if (!isset($_SESSION['user_id'])) { die("Yetkisiz erişim!"); }

$error_message = '';
$user_id = $_SESSION['user_id'];

// Ürünleri veritabanından çek
$products_result = mysqli_query($conn, "SELECT * FROM products ORDER BY name ASC");
$products = [];
while ($row = mysqli_fetch_assoc($products_result)) {
    $products[] = $row;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $customer_name = mysqli_real_escape_string($conn, $_POST['customer_name']);
    
    // Formdan gelen ürün dizileri
    $descriptions = $_POST['description'];
    $quantities = $_POST['quantity'];
    $unit_prices = $_POST['unit_price'];

    if (empty($customer_name) || !isset($descriptions) || count($descriptions) == 0) {
        $error_message = "Lütfen müşteri adı ve en az bir ürün satırı ekleyin.";
    } else {
        mysqli_begin_transaction($conn);
        try {
            // 1. Ana teklif kaydını oluştur (toplam 0 olarak)
            $stmt_offer = mysqli_prepare($conn, "INSERT INTO offers (user_id, customer_name) VALUES (?, ?)");
            mysqli_stmt_bind_param($stmt_offer, "is", $user_id, $customer_name);
            mysqli_stmt_execute($stmt_offer);
            $offer_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt_offer);

            $total_amount = 0;

            // 2. Her bir ürün satırını `offer_items`'a ekle
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

            // 3. Ana teklifteki toplam tutarı güncelle
            $stmt_update = mysqli_prepare($conn, "UPDATE offers SET total_amount = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt_update, "di", $total_amount, $offer_id);
            mysqli_stmt_execute($stmt_update);
            mysqli_stmt_close($stmt_update);

            mysqli_commit($conn);
            header("Location: dashboard.php?status=created");
            exit;
        } catch (mysqli_sql_exception $exception) {
            mysqli_rollback($conn);
            $error_message = "Teklif oluşturulurken bir veritabanı hatası oluştu: " . $exception->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Teklif Oluştur</title>
    <style>
        :root {
            --primary-color: #007bff;
            --secondary-color: #6c757d;
            --success-color: #28a745;
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
        .btn-primary { background-color: var(--success-color); color: white; }
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
        
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 1000; }
        .modal-content { background: white; padding: 30px; border-radius: 8px; width: 90%; max-width: 500px; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-header h3 { margin: 0; }
        .modal-close { background: none; border: none; font-size: 24px; cursor: pointer; }
    </style>
</head>
<body>

<div class="container">
    <h2>Yeni Teklif Oluştur</h2>

    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <form method="POST" action="create_offer.php">
        <div class="form-group">
            <label for="customer_name">Müşteri Adı <span class="required-mark">*</span></label>
            <input type="text" id="customer_name" name="customer_name" required>
        </div>
        <hr style="margin: 25px 0;">
        
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h4>Ürün/Hizmet Kalemleri</h4>
            <button type="button" id="add-new-product-btn" class="btn-link">Yeni Ürün Ekle</button>
        </div>
        <div id="line-items-container">
            <!-- Line items will be added here by JavaScript -->
        </div>
        <button type="button" id="add-item-btn" class="btn btn-secondary">Kalem Ekle</button>

        <div class="actions">
            <button type="submit" class="btn btn-primary">Teklifi Kaydet</button>
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

<!-- Add New Product Modal -->
<div class="modal-overlay" id="add-product-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Yeni Ürün Ekle</h3>
            <button type="button" class="modal-close" id="modal-close-btn">&times;</button>
        </div>
        <div id="modal-error" class="alert alert-danger" style="display: none;"></div>
        <div class="form-group">
            <label for="new_product_name">Ürün Adı <span class="required-mark">*</span></label>
            <input type="text" id="new_product_name">
        </div>
        <div class="form-group">
            <label for="new_product_description">Açıklama</label>
            <textarea id="new_product_description"></textarea>
        </div>
        <div class="form-group">
            <label for="new_product_price">Birim Fiyat (TL) <span class="required-mark">*</span></label>
            <input type="number" id="new_product_price" step="0.01">
        </div>
        <div class="actions">
            <button type="button" id="save-product-btn" class="btn btn-primary">Kaydet</button>
            <button type="button" id="modal-cancel-btn" class="btn btn-secondary">İptal</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('line-items-container');
    const template = document.getElementById('line-item-template');
    const addItemBtn = document.getElementById('add-item-btn');
    
    let products = <?php echo json_encode($products); ?>;

    function addNewLineItem() {
        const clone = template.content.cloneNode(true);
        const productSelect = clone.querySelector('.product-select');
        
        // This makes sure that if a new product was added via modal,
        // any subsequent new line also gets it.
        products.forEach(p => {
            if (![...productSelect.options].some(o => o.value == p.id)) {
                 const option = document.createElement('option');
                 option.value = p.id;
                 option.textContent = p.name;
                 productSelect.appendChild(option);
            }
        });
        
        container.appendChild(clone);
    }

    // Add initial item on page load
    addNewLineItem();

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
    
    // Modal logic
    const modal = document.getElementById('add-product-modal');
    const openModalBtn = document.getElementById('add-new-product-btn');
    const closeModalBtn = document.getElementById('modal-close-btn');
    const cancelModalBtn = document.getElementById('modal-cancel-btn');
    const saveProductBtn = document.getElementById('save-product-btn');
    const modalError = document.getElementById('modal-error');

    const newProductName = document.getElementById('new_product_name');
    const newProductDesc = document.getElementById('new_product_description');
    const newProductPrice = document.getElementById('new_product_price');

    openModalBtn.addEventListener('click', () => modal.style.display = 'flex');
    
    const closeModal = () => {
        modal.style.display = 'none';
        modalError.style.display = 'none';
        newProductName.value = '';
        newProductDesc.value = '';
        newProductPrice.value = '';
    };

    closeModalBtn.addEventListener('click', closeModal);
    cancelModalBtn.addEventListener('click', closeModal);

    saveProductBtn.addEventListener('click', function() {
        const name = newProductName.value.trim();
        const description = newProductDesc.value.trim();
        const price = newProductPrice.value.trim();

        if (!name || !price || price <= 0) {
            modalError.textContent = 'Ürün adı ve geçerli bir fiyat girin.';
            modalError.style.display = 'block';
            return;
        }

        fetch('add_product_ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name, description, price })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.product) {
                const newProd = data.product;
                products.push(newProd);

                document.querySelectorAll('.product-select').forEach(select => {
                    const option = document.createElement('option');
                    option.value = newProd.id;
                    option.textContent = newProd.name;
                    select.appendChild(option);
                });
                
                closeModal();
            } else {
                modalError.textContent = data.message || 'Bilinmeyen bir hata oluştu.';
                modalError.style.display = 'block';
            }
        })
        .catch(error => {
            modalError.textContent = 'İstek gönderilemedi: ' + error;
            modalError.style.display = 'block';
        });
    });
});
</script>

</body>
</html>
