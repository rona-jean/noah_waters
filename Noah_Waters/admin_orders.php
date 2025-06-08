<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['order_id'])) {
        $order_id = intval($_POST['order_id']);
        if (isset($_POST['status'])) {
            // Update order status
            $status = $_POST['status'];
            $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $status, $order_id);
            $stmt->execute();
            $stmt->close();
        }
        if (isset($_POST['toggle_payment'])) {
            // Toggle payment status
            $stmt = $conn->prepare("SELECT payment_status FROM orders WHERE id = ?");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $order = $result->fetch_assoc();
            $stmt->close();

            $newPaymentStatus = ($order['payment_status'] === 'paid') ? 'unpaid' : 'paid';

            $stmt = $conn->prepare("UPDATE orders SET payment_status = ? WHERE id = ?");
            $stmt->bind_param("si", $newPaymentStatus, $order_id);
            $stmt->execute();
            $stmt->close();
        }
    }
    header("Location: admin_orders.php"); // redirect to refresh page
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $order_id = intval($_POST['order_id']);
    $status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $order_id);
    $stmt->execute();
    $stmt->close();
}

$limit = 8;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$statusFilter = isset($_GET['status']) && $_GET['status'] !== '' ? $_GET['status'] : null;
$usertypeFilter = isset($_GET['usertype']) && $_GET['usertype'] !== '' ? $_GET['usertype'] : null;
$paymentStatusFilter = isset($_GET['payment_status']) && $_GET['payment_status'] !== '' ? $_GET['payment_status'] : null;

if ($paymentStatusFilter) {
    $conditions[] = "o.payment_status = ?";
    $params[] = $paymentStatusFilter;
    $paramTypes .= 's';
}

$conditions = [];
$params = [];
$paramTypes = '';

if ($statusFilter) {
    $conditions[] = "o.status = ?";
    $params[] = $statusFilter;
    $paramTypes .= 's';
}
if ($usertypeFilter) {
    $conditions[] = "LOWER(o.usertype) = ?";
    $params[] = strtolower($usertypeFilter);
    $paramTypes .= 's';
}

$whereSQL = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$countSql = "SELECT COUNT(*) as total FROM orders o $whereSQL";
$countStmt = $conn->prepare($countSql);
if ($params) {
    $countStmt->bind_param($paramTypes, ...$params);
}
$countStmt->execute();
$totalOrders = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();

$totalPages = ceil($totalOrders / $limit);

$sql = "SELECT o.*, u.fullname AS username 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id
        $whereSQL
        ORDER BY o.created_at DESC
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($paramTypes . 'ii', ...array_merge($params, [$limit, $offset]));
} else {
    $stmt->bind_param("ii", $limit, $offset);
}
$stmt->execute();
$orders = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Orders</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #79c7ff;
            margin: 0;
            padding: 0;
            font-family: "Boogaloo", sans-serif;
            font-weight: 400;
            font-style: normal;
            background-image: url('back.webp');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }
        .container-box {
            background: rgba(3, 0, 0, 0.1);
            border-radius: 15px;
            padding: 25px;
            margin-top: 30px;
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.52);
        }
        .order-box {
            background: rgba(255, 255, 255, 0.73);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 25px;
        }
        .order-box h5 {
            color:rgb(0, 52, 97);
        }
        .items-list {
            padding-left: 1rem;
        }
    </style>
</head>
<body>

<!-- Responsive Bootstrap Navbar -->
<nav class="navbar navbar-expand-lg navbar-light" style="background-color: #0d6efd;">
  <div class="container-fluid">
    <a class="navbar-brand text-white" href="#">Admin Dashboard</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar" aria-controls="adminNavbar" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="adminNavbar">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link active text-white fw-bold" href="#">Orders</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="admin_products.php">Products</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="admin_manage_users.php">Users</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="admin_sales_report.php">Sales Report</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="admin_borrowed.php">Borrowed Containers</a></li>
      </ul>
      <span class="navbar-text text-white me-3">
        Logged in as <?= htmlspecialchars($_SESSION['role']) ?>
      </span>
      <a href="logout.php" class="btn btn-light btn-sm">Logout</a>
    </div>
  </div>
</nav>



<div class="container container-box">

    <h2 class="text-center mb-4">Manage Orders</h2>

    <form method="GET" class="row g-3 mb-4">
        <div class="col-md-4">
            <label class="form-label text-light">Status</label>
            <select name="status" class="form-select">
                <option value="">All</option>
                <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="processing" <?= $statusFilter === 'processing' ? 'selected' : '' ?>>Processing</option>
                <option value="delivered" <?= $statusFilter === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label text-light">User Type</label>
            <select name="usertype" class="form-select">
                <option value="">All</option>
                <option value="user" <?= $usertypeFilter === 'user' ? 'selected' : '' ?>>Users Only</option>
                <option value="guest" <?= $usertypeFilter === 'guest' ? 'selected' : '' ?>>Guests Only</option>
            </select>
        </div>
        <div class="col-md-4 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
        </div>
    </form>

    <?php if ($orders->num_rows === 0): ?>
        <p>No orders found.</p>
    <?php else: ?>
        <?php while ($order = $orders->fetch_assoc()): ?>
            <div class="order-box">
                <h5>Order #<?= $order['id'] ?></h5>
                <p><strong>Type:</strong> <?= ucfirst($order['usertype']) ?></p>
                <?php if ($order['user_id']): ?>
                    <p><strong>User:</strong> <?= htmlspecialchars($order['username']) ?> | <strong>Address:</strong> <?= htmlspecialchars($order['delivery_address']) ?></p>
                <?php else: ?>
                    <p><strong>Guest Address:</strong> <?= htmlspecialchars($order['delivery_address']) ?></p>
                <?php endif; ?>
                <p><strong>Payment Status:</strong> <?= ucfirst($order['payment_status']) ?></p>
                <p><strong>Status:</strong> <?= ucfirst($order['status']) ?></p>
                <p><strong>Payment:</strong> <span class="me-2"><?= ucfirst($order['payment_status']) ?></span>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                        <input type="hidden" name="toggle_payment" value="1">
                        <button type="submit" 
                        class="btn btn-sm <?= $order['payment_status'] === 'paid' ? 'btn-secondary' : 'btn-success' ?>">
                        Mark as <?= $order['payment_status'] === 'paid' ? 'Unpaid' : 'Paid' ?>
                        </button>
                    </form>
                </p>




                <p><strong>Created:</strong> <?= $order['created_at'] ?></p>

                <?php
                $stmt2 = $conn->prepare("SELECT oi.*, p.name FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
                $stmt2->bind_param("i", $order['id']);
                $stmt2->execute();
                $items = $stmt2->get_result();
                ?>
                <ul class="items-list">
                    <?php while ($item = $items->fetch_assoc()): ?>
                        <li><?= htmlspecialchars($item['name']) ?> - ₱<?= $item['price'] ?> × <?= $item['quantity'] ?></li>
                    <?php endwhile; ?>
                </ul>
                <?php $stmt2->close(); ?>

                <form method="POST" class="mt-2">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <div class="input-group">
                        <select name="status" class="form-select">
                            <?php foreach (['pending', 'processing', 'delivered', 'cancelled'] as $s): ?>
                                <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-outline-light">Update</button>
                    </div>
                </form>
            </div>
        <?php endwhile; ?>

        <!-- Pagination -->
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">Previous</a></li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
