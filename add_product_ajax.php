<?php
include "db.php";

// Set content type to JSON
header('Content-Type: application/json');

// Response object
$response = [
    'success' => false,
    'message' => 'Bir hata oluştu.',
    'product' => null
];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Yetkisiz erişim.';
    echo json_encode($response);
    exit;
}

// Check if it's a POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get JSON from POST body
    $data = json_decode(file_get_contents('php://input'), true);

    $name = isset($data['name']) ? trim($data['name']) : '';
    $description = isset($data['description']) ? trim($data['description']) : '';
    $price = isset($data['price']) ? (float)$data['price'] : 0;

    // Validation
    if (empty($name) || $price <= 0) {
        $response['message'] = 'Ürün adı ve fiyatı zorunlu alanlardır.';
    } else {
        // Insert into database
        $stmt = mysqli_prepare($conn, "INSERT INTO products (name, description, unit_price) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssd", $name, $description, $price);

        if (mysqli_stmt_execute($stmt)) {
            $new_product_id = mysqli_insert_id($conn);
            $response['success'] = true;
            $response['message'] = 'Ürün başarıyla eklendi!';
            $response['product'] = [
                'id' => $new_product_id,
                'name' => $name,
                'description' => $description,
                'unit_price' => $price
            ];
        } else {
            $response['message'] = 'Veritabanı hatası: ' . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    }
} else {
    $response['message'] = 'Geçersiz istek yöntemi.';
}

echo json_encode($response);
?>
