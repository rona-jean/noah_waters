<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo "<h2 style='text-align:center;'>Guest orders are not supported here. Please login to view your orders.</h2>";
    exit;
}

$user_id = $_SESSION['user_id'];

if (isset($_GET['cancel_order_id'])) {
    $cancel_order_id = intval($_GET['cancel_order_id']);
    $stmt = $conn->prepare("UPDATE orders SET status = 'Cancelled' WHERE id = ? AND user_id = ? AND status = 'Pending'");
    $stmt->bind_param("ii", $cancel_order_id, $user_id);
    $stmt->execute();
    $stmt->close();
}

$sql = "SELECT o.id AS order_id, o.status, o.created_at,
               GROUP_CONCAT(CONCAT(p.name, ' (x', oi.quantity, ')') SEPARATOR ', ') AS items,
               SUM(oi.quantity * oi.price) AS total
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        WHERE o.user_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>My Orders - Noah Waters</title>
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
    }
  }
</style>
</head>
<body>

<nav class="navbar" role="navigation" aria-label="Main Navigation">
  <div class="logo">Noah Waters</div>
  <nav>
    <a href="index.php" aria-current="page">Home</a>
    <a href="shop.php">Shop</a>
    <a href="track_order_user.php" class="active">My Orders</a>
    <a href="logout.php">Logout</a>
  </nav>
</nav>

<main class="container" role="main">
  <h1>📦 My Orders</h1>

  <?php if ($result->num_rows > 0): ?>
    <?php while ($row = $result->fetch_assoc()): ?>
      <?php 
        $status = strtolower($row['status']);
        $statusClass = match($status) {
          'pending' => 'status-pending',
          'preparing' => 'status-preparing',
          'out for delivery' => 'status-out-for-delivery',
          'paid' => 'status-paid',
          'delivered' => 'status-delivered',
          'cancelled' => 'status-cancelled',
          default => 'status-pending',
        };
      ?>
      <article class="order-card" aria-labelledby="order-<?= htmlspecialchars($row['order_id']) ?>">
        <header class="order-header">
          <div>
            <div class="order-id" id="order-<?= htmlspecialchars($row['order_id']) ?>">Order #<?= htmlspecialchars($row['order_id']) ?></div>
            <div class="order-date"><?= date("M d, Y h:i A", strtotime($row['created_at'])) ?></div>
          </div>
          <div class="order-total">₱<?= number_format($row['total'], 2) ?></div>
        </header>

        <p class="order-items" aria-label="Ordered items"><?= htmlspecialchars($row['items']) ?></p>

        <div>
          <span class="status-badge <?= $statusClass ?>" aria-live="polite"><?= htmlspecialchars(ucfirst($row['status'])) ?></span>
          <?php if ($status === 'pending'): ?>
            <a href="track_order_user.php?cancel_order_id=<?= $row['order_id'] ?>" class="cancel-btn" onclick="return confirm('Cancel this order?')">Cancel Order</a>
          <?php endif; ?>
        </div>

        <section class="tracking-steps" aria-label="Order progress for order <?= htmlspecialchars($row['order_id']) ?>">
          <div class="step <?= ($status == 'pending' || in_array($status, ['preparing','out for delivery','paid','delivered'])) ? 'active' : '' ?>">Placed</div>
          <div class="step <?= in_array($status, ['preparing','out for delivery','paid','delivered']) ? 'active' : '' ?>">Preparing</div>
          <div class="step <?= in_array($status, ['out for delivery','paid','delivered']) ? 'active' : '' ?>">Out for Delivery</div>
          <div class="step <?= in_array($status, ['paid','delivered']) ? 'active' : '' ?>">Paid</div>
          <div class="step <?= $status == 'delivered' ? 'active' : '' ?>">Delivered</div>
        </section>
      </article>
    <?php endwhile; ?>
  <?php else: ?>
    <p style="text-align:center; font-size: 1.1rem; color: #555;">You haven't placed any orders yet.</p>
  <?php endif; ?>
</main>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
