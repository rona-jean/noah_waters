<?php
session_start();
include 'config.php'; // adjust path if needed

$orders = [];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle order cancellation
    if (isset($_POST['cancel_order_id'])) {
        $cancelId = intval($_POST['cancel_order_id']);
        $conn->query("UPDATE orders SET status = 'Cancelled' WHERE id = $cancelId AND usertype = 'guest'");
    }

    $fullname = trim($_POST['fullname'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (empty($fullname) && empty($phone)) {
        $error = "Please enter your full name or phone number.";
    } else {
        $sql = "SELECT o.id AS order_id, o.status, o.created_at,
                       GROUP_CONCAT(CONCAT(p.name, ' (x', oi.quantity, ')') SEPARATOR ', ') AS items,
                       SUM(oi.quantity * oi.price) AS total
                FROM orders o
                JOIN order_items oi ON o.id = oi.order_id
                JOIN products p ON oi.product_id = p.id
                WHERE o.usertype = 'guest'";

        if (!empty($fullname)) {
            $sql .= " AND o.fullname = '" . $conn->real_escape_string($fullname) . "'";
        }
        if (!empty($phone)) {
            $sql .= " AND o.phone = '" . $conn->real_escape_string($phone) . "'";
        }

        $sql .= " GROUP BY o.id ORDER BY o.created_at DESC";

        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $orders[] = $row;
            }
        } else {
            $error = "No orders found.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Guest Order Tracking</title>
    <link rel="stylesheet" href="style.css">
    <style>
      /* Reset & base */
      * {
        box-sizing: border-box;
      }
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
      a {
        text-decoration: none;
        color: inherit;
      }
      /* Navbar */
      .navbar {
        position: sticky;
        top: 0;
        background: #0f65b4;
        display: flex;
        align-items: center;
        padding: 0 2rem;
        height: 60px;
        box-shadow: 0 2px 5px rgb(0 0 0 / 0.1);
        z-index: 1000;
      }
      .navbar .logo {
        color: white;
        font-weight: 700;
        font-size: 1.5rem;
        letter-spacing: 1px;
        user-select: none;
      }
      .navbar nav {
        margin-left: auto;
        display: flex;
        gap: 1.2rem;
      }
      .navbar nav a {
        color: white;
        font-weight: 600;
        padding: 8px 12px;
        border-radius: 4px;
        transition: background-color 0.3s ease;
      }
      .navbar nav a:hover,
      .navbar nav a.active {
        background-color: #094a85;
      }
      /* Container */
      .container {
        max-width: 900px;
        margin: 2.5rem auto 3rem;
        padding: 0 1rem;
      }
      h1 {
        color: #0f65b4;
        text-align: center;
        margin-bottom: 2rem;
        font-weight: 700;
      }
      /* Order cards */
      .order-card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 3px 8px rgb(0 0 0 / 0.1);
        margin-bottom: 2rem;
        padding: 1.6rem 2rem;
        transition: box-shadow 0.3s ease;
      }
      .order-card:hover {
        box-shadow: 0 5px 15px rgb(0 0 0 / 0.15);
      }
      .order-header {
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        margin-bottom: 1rem;
      }
      .order-id {
        font-weight: 700;
        font-size: 1.1rem;
        color: #0f65b4;
      }
      .order-date {
        font-size: 0.9rem;
        color: #666;
        margin-top: 4px;
      }
      .order-total {
        font-weight: 700;
        font-size: 1.1rem;
        color: #2a9d8f;
      }
      .order-items {
        font-size: 0.95rem;
        color: #444;
        margin-bottom: 1.5rem;
      }
      /* Status badges */
      .status-badge {
        display: inline-block;
        padding: 0.3rem 0.75rem;
        border-radius: 15px;
        font-size: 0.85rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        user-select: none;
      }
      .status-pending {
        background: #ffcc00;
        color: #856404;
      }
      .status-preparing {
        background: #17a2b8;
        color: white;
      }
      .status-out-for-delivery {
        background: #007bff;
        color: white;
      }
      .status-paid {
        background: #28a745;
        color: white;
      }
      .status-delivered {
        background: #1c7430;
        color: white;
      }
      .status-cancelled {
        background: #dc3545;
        color: white;
      }
      /* Cancel button */
      .cancel-btn {
        background: #dc3545;
        color: white;
        padding: 8px 14px;
        font-weight: 600;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        user-select: none;
        transition: background-color 0.3s ease;
        float: right; /* This moves the button to the right */
      }
      .cancel-btn:hover {
        background: #a71d2a;
      }
      /* Order tracking steps */
      .tracking-steps {
        display: flex;
        justify-content: space-between;
        margin-top: 1.5rem;
        position: relative;
      }
      .tracking-steps::before {
        content: '';
        position: absolute;
        top: 15px;
        left: 10px;
        right: 10px;
        height: 4px;
        background: #ddd;
        border-radius: 2px;
        z-index: 0;
      }
      .step {
        position: relative;
        z-index: 1;
        flex: 1;
        text-align: center;
        font-size: 0.85rem;
        color: #999;
        font-weight: 600;
        user-select: none;
      }
      .step:not(:last-child) {
        margin-right: 10px;
      }
      .step.active {
        color: #28a745;
      }
      .step::before {
        content: "●";
        display: block;
        font-size: 20px;
        margin-bottom: 6px;
        color: #ccc;
      }
      .step.active::before {
        color: #28a745;
      }
      /* Responsive */
      @media (max-width: 600px) {
        .order-header {
          flex-direction: column;
          gap: 6px;
        }
        .tracking-steps {
          font-size: 0.75rem;
        }
        .cancel-btn {
          width: 100%;
          margin-top: 12px;
          float: none;
        }
      }
    </style>
</head>
<body style="margin: 0; font-family: 'Segoe UI', Tahoma, sans-serif; background: url('your-background.jpg') no-repeat center center fixed; background-size: cover;">

    <div style="background-color: #005eb8; padding: 20px; color: white; display: flex; justify-content: space-between; align-items: center;">
        <h2 style="margin: 0;">Noah Waters</h2>
        <div>
            <a href="index.html" style="margin-right: 20px; color: white; text-decoration: none;">Home</a>
            <a href="track_order_guest.php" style="margin-right: 20px; color: white; text-decoration: none;">My Orders</a>
        </div>
    </div>

    <div style="padding: 30px;">
        <h2 style="text-align: center; font-size: 2rem; color: #004aad;"><img src="📦" alt="" style="vertical-align: middle;"> My Orders</h2>

        <?php if (!empty($error)): ?>
            <div class="error-message" style="color: red; text-align: center; margin-bottom: 20px;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (empty($orders)): ?>
            <form method="POST" style="max-width: 400px; margin: 0 auto; background: white; padding: 25px; border-radius: 10px; box-shadow: 0 8px 16px rgba(0,0,0,0.1);">
                <label for="fullname">Full Name</label>
                <input type="text" name="fullname" id="fullname" placeholder="Enter your name" value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>" style="width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 6px;">

                <label for="phone">Phone Number</label>
                <input type="tel" name="phone" id="phone" placeholder="Enter your phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" style="width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 6px;">

                <small style="display:block; text-align:center; margin-bottom: 15px;">Enter your full name or phone number (or both)</small>

                <button type="submit" style="background-color: #0d6efd; color: white; padding: 10px; border: none; border-radius: 6px; width: 100%; font-weight: bold;">Track My Order</button>
            </form>
        <?php else: ?>
            <?php foreach ($orders as $row): ?>
                <div style="background: white; border-radius: 10px; padding: 20px; margin: 20px auto; max-width: 700px; box-shadow: 0 8px 16px rgba(0,0,0,0.15);">
                    <div style="display: flex; justify-content: space-between; flex-wrap: wrap;">
                        <div>
                            <strong>Order #<?= htmlspecialchars($row['order_id']) ?></strong>
                            <div style="color: #777; font-size: 0.85rem; margin-top: 4px;"><?= date('F j, Y, g:i A', strtotime($row['created_at'])) ?></div>
                        </div>
                        <div style="text-align: right; min-width: 140px;">
                            <div style="font-weight: 700; font-size: 1.1rem; color: #2a9d8f;">₱<?= number_format($row['total'], 2) ?></div>
                            <?php
                            $status = strtolower($row['status']);
                            $statusClass = 'status-pending';
                            if ($status === 'pending') $statusClass = 'status-pending';
                            elseif ($status === 'preparing') $statusClass = 'status-preparing';
                            elseif ($status === 'out for delivery') $statusClass = 'status-out-for-delivery';
                            elseif ($status === 'paid') $statusClass = 'status-paid';
                            elseif ($status === 'delivered') $statusClass = 'status-delivered';
                            elseif ($status === 'cancelled') $statusClass = 'status-cancelled';
                            ?>
                            <span class="status-badge <?= $statusClass ?>"><?= ucfirst($row['status']) ?></span>
                        </div>
                    </div>
                    <p style="margin-top: 1rem; font-size: 1rem; color: #444;"><strong>Items:</strong> <?= htmlspecialchars($row['items']) ?></p>

                    <?php if ($status === 'pending'): ?>
                        <form method="POST" style="margin-top: 15px; text-align: right;">
                            <input type="hidden" name="cancel_order_id" value="<?= htmlspecialchars($row['order_id']) ?>">
                            <input type="hidden" name="fullname" value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>">
                            <input type="hidden" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                            <button type="submit" class="cancel-btn" onclick="return confirm('Are you sure you want to cancel this order?');">Cancel Order</button>
                        </form>
                    <?php endif; ?>

                    <div class="tracking-steps">
                        <div class="step <?= $status === 'pending' ? 'active' : '' ?>">Pending</div>
                        <div class="step <?= $status === 'preparing' ? 'active' : '' ?>">Preparing</div>
                        <div class="step <?= $status === 'out for delivery' ? 'active' : '' ?>">Out for Delivery</div>
                        <div class="step <?= $status === 'paid' ? 'active' : '' ?>">Paid</div>
                        <div class="step <?= $status === 'delivered' ? 'active' : '' ?>">Delivered</div>
                        <div class="step <?= $status === 'cancelled' ? 'active' : '' ?>">Cancelled</div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</body>
</html>
