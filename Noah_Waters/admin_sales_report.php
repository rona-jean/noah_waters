<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Handle filter
$filter = $_GET['filter'] ?? 'day';
$whereClause = '';

switch ($filter) {
    case 'week':
        $whereClause = "WHERE o.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        break;
    case 'month':
        $whereClause = "WHERE o.created_at >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
        break;
    default:
        $whereClause = "WHERE DATE(o.created_at) = CURDATE()";
}

$query = "SELECT o.id, o.user_id, o.total_amount, o.payment_status, o.created_at,
                 u.fullname
          FROM orders o
          LEFT JOIN users u ON o.user_id = u.id
          $whereClause
          ORDER BY o.created_at DESC";


$result = $conn->query($query);
if (!$result) {
    die("Query failed: " . $conn->error);
}

function exportCSV($orders) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="sales_report.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Order ID', 'Customer', 'Total', 'Status', 'Date']);
    foreach ($orders as $row) {
        fputcsv($output, [
            $row['id'],
            $row['fullname'] ?? 'Guest',
            $row['total_amount'],
            $row['payment_status'],
            $row['created_at']
        ]);
    }
    fclose($output);
    exit;
}

// Export CSV only
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    exportCSV($orders);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light" style="background-color: #0d6efd;">
    <div class="container-fluid">
        <a class="navbar-brand text-white" href="#">Admin Dashboard</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="adminNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link text-white" href="admin_orders.php">Orders</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="admin_products.php">Products</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="admin_manage_users.php">Users</a></li>
                <li class="nav-item"><a class="nav-link active fw-bold text-white" href="#">Sales Report</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="admin_borrowed.php">Borrowed Containers</a></li>
            </ul>
            <a href="logout.php" class="btn btn-light btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <h2 class="mb-4 text-center">Sales Report</h2>

    <div class="d-flex justify-content-between mb-3">
        <div>
            <form method="get" class="d-inline">
                <select name="filter" onchange="this.form.submit()" class="form-select d-inline w-auto">
                    <option value="day" <?= $filter === 'day' ? 'selected' : '' ?>>Today</option>
                    <option value="week" <?= $filter === 'week' ? 'selected' : '' ?>>This Week</option>
                    <option value="month" <?= $filter === 'month' ? 'selected' : '' ?>>This Month</option>
                </select>
            </form>
        </div>
        <div>
            <a href="?filter=<?= $filter ?>&export=csv" class="btn btn-outline-primary btn-sm">Export CSV</a>
        </div>
    </div>

    <table class="table table-bordered table-hover">
        <thead class="table-primary">
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Total</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['fullname'] ?? 'Guest') ?></td>
                <td>₱<?= number_format($row['total_amount'], 2) ?></td>
                <td><?= $row['payment_status'] ?></td>
                <td><?= $row['created_at'] ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
