<?php
session_start();
require 'config.php'; // your mysqli connection

// Only allow admin or staff
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header("Location: login.php");
    exit;
}

// Handle add/edit/delete product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'add') {
        $name = trim($_POST['name']);
        $category = $_POST['category'];
        $price = floatval($_POST['price']);
        $image = trim($_POST['image']);
        $isBorrowable = isset($_POST['is_borrowable']) ? 1 : 0;

        if ($name && in_array($category, ['container', 'bottle']) && $price > 0 && preg_match('/\.(jpg|jpeg|png)$/i', $image)) {
            $stmt = $conn->prepare("INSERT INTO products (name, category, price, image, is_borrowable) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssdsi", $name, $category, $price, $image, $isBorrowable);
            $stmt->execute();
            $stmt->close();
            header("Location: admin_products.php");
            exit;
        }
    } elseif ($action === 'edit') {
        $id = intval($_POST['id']);
        $name = trim($_POST['name']);
        $category = $_POST['category'];
        $price = floatval($_POST['price']);
        $image = trim($_POST['image']);
        $isBorrowable = isset($_POST['is_borrowable']) ? 1 : 0;

        if ($name && in_array($category, ['container', 'bottle']) && $price > 0 && preg_match('/\.(jpg|jpeg|png)$/i', $image)) {
            $stmt = $conn->prepare("UPDATE products SET name = ?, category = ?, price = ?, image = ?, is_borrowable = ? WHERE id = ?");
            $stmt->bind_param("ssdsii", $name, $category, $price, $image, $isBorrowable, $id);
            $stmt->execute();
            $stmt->close();
            header("Location: admin_products.php");
            exit;
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id']);
        if ($id) {
            $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            header("Location: admin_products.php");
            exit;
        }
    }
}

// Fetch products
$result = $conn->query("SELECT * FROM products ORDER BY id DESC");
$products = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Admin Products</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            background-color: #79c7ff;
            font-family: "Boogaloo", sans-serif;
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
            color: white;
        }
        .table thead {
            background: rgba(0, 52, 97, 0.8);
        }
        .table tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.15);
        }
        .product-img {
            max-width: 80px;
            height: auto;
            border-radius: 8px;
        }
        label {
            color: white;
        }

       .form-check-input.toggle-lg {
        width: 3.5em;
        height: 2em;
        cursor: pointer;
        accent-color: #28a745; /* Bootstrap success green */
        transition: background-color 0.3s ease;
        }

        /* White label text */
        label.form-check-label {
            user-select: none;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light" style="background-color: #0d6efd;">
  <div class="container-fluid">
    <a class="navbar-brand text-white" href="#">Admin Dashboard</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link text-white" href="admin_orders.php">Orders</a></li>
        <li class="nav-item"><a class="nav-link active text-white fw-bold" href="#">Products</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="admin_manage_users.php">Users</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="admin_sales_report.php">Sales Report</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="admin_borrowed.php">Borrowed Containers</a></li>
    </ul>
      <span class="navbar-text text-white me-3">Logged in as <?= htmlspecialchars($_SESSION['role']) ?></span>
      <a href="logout.php" class="btn btn-light btn-sm">Logout</a>
    </div>
  </div>
</nav>

<div class="container container-box">
    <h2 class="mb-4 text-center">Manage Products</h2>

    <!-- Add product form -->
<form method="POST" class="mb-5">
    <input type="hidden" name="action" value="add">
    <div class="row g-3 align-items-end">
        <div class="col-md-3">
            <label for="name" class="form-label">Name</label>
            <input required type="text" name="name" id="name" class="form-control" placeholder="Product name" />
        </div>
        <div class="col-md-3">
            <label for="category" class="form-label">Category</label>
            <select required name="category" id="category" class="form-select">
                <option value="">Select category</option>
                <option value="container">Container</option>
                <option value="bottle">Bottle</option>
            </select>
        </div>
        <div class="col-md-3">
            <label for="price" class="form-label">Price (₱)</label>
            <input required type="number" step="0.01" min="0" name="price" id="price" class="form-control" placeholder="0.00" />
        </div>
        <div class="col-md-3">
            <label for="image" class="form-label">Image Filename or Path</label>
            <input required type="text" name="image" id="image" class="form-control" placeholder="image.jpg or images/image.jpg" />
        </div>
    </div>

    <!-- Borrowable toggle and Add button side by side, centered -->
    <div class="row mt-4 justify-content-center align-items-center">
        <div class="col-auto d-flex align-items-center">
            <div class="form-check form-switch">
                <input class="form-check-input toggle-lg" type="checkbox" id="is_borrowable" name="is_borrowable" value="1">
                <label class="form-check-label text-white fs-5 ms-2 mb-0" for="is_borrowable">Borrowable</label>
            </div>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-success btn-lg px-4">Add Product</button>
        </div>
    </div>
</form>


    <!-- Products table -->
    <table class="table table-hover text-white align-middle">
        <thead>
            <tr>
                <th>Image</th>
                <th>Name</th>
                <th>Category</th>
                <th>Borrowable</th>
                <th>Price (₱)</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($products)): ?>
                <tr><td colspan="6" class="text-center">No products found.</td></tr>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                <tr>
                    <td><img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-img" /></td>
                    <td><?= htmlspecialchars($product['name']) ?></td>
                    <td><?= ucfirst($product['category']) ?></td>
                    <td><?= $product['is_borrowable'] ? 'Yes' : 'No' ?></td>
                    <td>₱<?= number_format($product['price'], 2) ?></td>
                    <td>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $product['id'] ?>">Edit</button>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this product?');">
                            <input type="hidden" name="action" value="delete" />
                            <input type="hidden" name="id" value="<?= $product['id'] ?>" />
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>

                <!-- Edit Modal -->
                <div class="modal fade" id="editModal<?= $product['id'] ?>" tabindex="-1">
                  <div class="modal-dialog">
                    <div class="modal-content text-dark">
                      <form method="POST">
                        <input type="hidden" name="action" value="edit" />
                        <input type="hidden" name="id" value="<?= $product['id'] ?>" />
                        <div class="modal-header">
                          <h5 class="modal-title">Edit Product</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                              <label for="name<?= $product['id'] ?>" class="form-label">Name</label>
                              <input required type="text" class="form-control" id="name<?= $product['id'] ?>" name="name" value="<?= htmlspecialchars($product['name']) ?>" />
                            </div>
                            <div class="mb-3">
                              <label for="category<?= $product['id'] ?>" class="form-label">Category</label>
                              <select required class="form-select" id="category<?= $product['id'] ?>" name="category">
                                  <option value="container" <?= $product['category'] === 'container' ? 'selected' : '' ?>>Container</option>
                                  <option value="bottle" <?= $product['category'] === 'bottle' ? 'selected' : '' ?>>Bottle</option>
                              </select>
                            </div>
                            <div class="mb-3">
                              <label for="price<?= $product['id'] ?>" class="form-label">Price (₱)</label>
                              <input required type="number" step="0.01" min="0" class="form-control" id="price<?= $product['id'] ?>" name="price" value="<?= number_format($product['price'], 2, '.', '') ?>" />
                            </div>
                            <div class="mb-3">
                              <label for="image<?= $product['id'] ?>" class="form-label">Image URL</label>
                              <input required type="url" class="form-control" id="image<?= $product['id'] ?>" name="image" value="<?= htmlspecialchars($product['image']) ?>" />
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="borrowable<?= $product['id'] ?>" name="is_borrowable" value="1" <?= $product['is_borrowable'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="borrowable<?= $product['id'] ?>">Borrowable</label>
                            </div>
                        </div>
                        <div class="modal-footer">
                          <button type="submit" class="btn btn-primary">Save changes</button>
                          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelector("form").addEventListener("submit", function (e) {
    const imageInput = document.querySelector("#image");
    const url = imageInput.value.toLowerCase();
    if (!url.endsWith(".jpg") && !url.endsWith(".jpeg") && !url.endsWith(".png")) {
        alert("Only .jpg, .jpeg, or .png image URLs are allowed.");
        e.preventDefault();
    }
});
</script>

</body>
</html>
