<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = new mysqli("localhost", "root", "", "noah_waters");
    if ($conn->connect_error) {
        die("Database connection failed: " . $conn->connect_error);
    }

    // Sanitize inputs
    $fullname = trim($_POST['fullname']);
    $phone = trim($_POST['phone']);
    $deliveryAddress = trim($_POST['address']);
    $shippingMethod = $_POST['shipping_method'];
    $pickupTime = $shippingMethod === 'Pickup' ? $_POST['pickup_time'] : null;
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : "";

    // Fetch cart items
    $stmt = $conn->prepare("SELECT c.product_id, c.quantity, p.price FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $items = [];
    $total = 0;
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
        $total += $row['price'] * $row['quantity'];
    }
    $stmt->close();

    if (empty($items)) {
        die("Cart is empty.");
    }

    // Insert into orders
    $stmt = $conn->prepare("INSERT INTO orders (user_id, fullname, phone, delivery_address, shipping_method, pickup_time, notes, total_amount, usertype) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'user')");
    $stmt->bind_param("issssssd", $userId, $fullname, $phone, $deliveryAddress, $shippingMethod, $pickupTime, $notes, $total);
    $stmt->execute();
    $orderId = $stmt->insert_id;
    $stmt->close();

    // Insert each item
    $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    foreach ($items as $item) {
        $stmt->bind_param("iiid", $orderId, $item['product_id'], $item['quantity'], $item['price']);
        $stmt->execute();
    }
    $stmt->close();

    // *** Handle borrowed containers here ***
    $checkCategoryStmt = $conn->prepare("SELECT category FROM products WHERE id = ?");
    $borrowStmt = $conn->prepare("INSERT INTO borrowed_containers (user_id, order_id, container_id, borrowed_at, returned) VALUES (?, ?, ?, NOW(), 0)");

    if (!$checkCategoryStmt || !$borrowStmt) {
        die("Prepare failed for borrowed containers: " . $conn->error);
    }

    foreach ($items as $item) {
        $productId = $item['product_id'];
        $quantity = $item['quantity'];

        // Check product category
        $checkCategoryStmt->bind_param("i", $productId);
        $checkCategoryStmt->execute();
        $checkCategoryStmt->bind_result($category);
        $checkCategoryStmt->fetch();
        $checkCategoryStmt->reset();

        if (strtolower($category) === 'container') {
            for ($i = 0; $i < $quantity; $i++) {
                $borrowStmt->bind_param("iii", $userId, $orderId, $productId);
                if (!$borrowStmt->execute()) {
                    die("Failed to insert borrowed container: " . $borrowStmt->error);
                }
            }
        }
    }

    $checkCategoryStmt->close();
    $borrowStmt->close();

    // Clear user's cart
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();

    $conn->close();

    header("Location: thank_you.php");
    exit;
}
?>
