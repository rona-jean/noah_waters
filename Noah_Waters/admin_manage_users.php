<?php
session_start();
require 'config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Handle role update or user deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_role'], $_POST['user_id'])) {
        $userId = intval($_POST['user_id']);
        $newRole = $_POST['new_role'];

        $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->bind_param("si", $newRole, $userId);
        $stmt->execute();
        $stmt->close();
    }

    if (isset($_POST['delete_user'], $_POST['user_id'])) {
        $userId = intval($_POST['user_id']);

        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();
    }

    // Redirect to avoid form resubmission on refresh
    header("Location: admin_manage_users.php");
    exit;
}

// Fetch all users
$result = $conn->query("SELECT id, fullname, email, role FROM users");
if (!$result) {
    die("Query failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Users</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #79c7ff;
      background-image: url('back.webp');
      background-size: cover;
      background-repeat: no-repeat;
      background-attachment: fixed;
      font-family: 'Boogaloo', sans-serif;
    }
    .container-box {
      background: rgba(255, 255, 255, 0.9);
      border-radius: 15px;
      padding: 25px;
      margin-top: 30px;
      box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
    }
  </style>
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
        <li class="nav-item"><a class="nav-link active text-white fw-bold" href="#">Users</a></li>
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
  <h2 class="text-center mb-4">Manage Users</h2>

  <table class="table table-hover table-bordered">
    <thead class="table-primary">
      <tr>
        <th>ID</th>
        <th>Full Name</th>
        <th>Email</th>
        <th>Role</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($user = $result->fetch_assoc()): ?>
        <tr>
          <td><?= $user['id'] ?></td>
          <td><?= htmlspecialchars($user['fullname']) ?></td>
          <td><?= htmlspecialchars($user['email']) ?></td>
          <td><?= htmlspecialchars($user['role']) ?></td>
          <td>
            <form method="POST" class="d-inline">
              <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
              <select name="new_role" class="form-select d-inline w-auto">
                <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
                <option value="staff" <?= $user['role'] === 'staff' ? 'selected' : '' ?>>Staff</option>
                <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
              </select>
              <button type="submit" name="update_role" class="btn btn-sm btn-outline-success">Update</button>
            </form>
            <form method="POST" class="d-inline">
              <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
              <button type="submit" name="delete_user" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')">Delete</button>
            </form>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
