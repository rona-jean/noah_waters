<?php
session_start();
require 'config.php'; // your mysqli connection

// Only allow admin or staff
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header("Location: login.php");
    exit;
}

// Handle return status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['borrow_id'], $_POST['returned'])) {
    $borrowId = intval($_POST['borrow_id']);
    $returned = $_POST['returned'] === '1' ? 1 : 0;
    $returnedAt = $returned ? date('Y-m-d H:i:s') : null;

    $stmt = $conn->prepare("UPDATE borrowed_containers SET returned = ?, returned_at = ? WHERE id = ?");
    $stmt->bind_param("isi", $returned, $returnedAt, $borrowId);
    $stmt->execute();
    $stmt->close();

    header("Location: admin_borrowed.php");
    exit;
}

// Fetch borrowed containers with borrower name and container name
$sql = "SELECT
    bc.id,
    bc.order_id,
    bc.container_id,
    bc.borrowed_at,
    bc.returned,
    bc.returned_at,
    p.name AS container_name,
    o.fullname AS borrower_name
FROM borrowed_containers bc
LEFT JOIN products p ON bc.container_id = p.id
LEFT JOIN orders o ON bc.order_id = o.id
ORDER BY bc.borrowed_at DESC";

$result = $conn->query($sql);

if (!$result) {
    die("SQL Error: " . $conn->error);
}

$borrowedContainers = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Manage Borrowed Containers</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            background-color: #f0f8ff;
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        .container-box {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        h2 {
            margin-bottom: 30px;
            text-align: center;
        }
        table th, table td {
            vertical-align: middle !important;
        }
    </style>
</head>
<body>

<div class="container container-box">
    <h2>Manage Borrowed Containers</h2>

    <?php if (empty($borrowedContainers)): ?>
        <p class="text-center">No borrowed containers found.</p>
    <?php else: ?>
        <table class="table table-bordered table-striped">
            <thead class="table-primary">
                <tr>
                    <th>#</th>
                    <th>Borrower Name</th>
                    <th>Container</th>
                    <th>Borrowed At</th>
                    <th>Returned?</th>
                    <th>Returned At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($borrowedContainers as $index => $borrow): ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><?= htmlspecialchars($borrow['borrower_name']) ?></td>
                    <td><?= htmlspecialchars($borrow['container_name']) ?></td>
                    <td><?= htmlspecialchars($borrow['borrowed_at']) ?></td>
                    <td><?= $borrow['returned'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-warning text-dark">No</span>' ?></td>
                    <td><?= $borrow['returned_at'] ? htmlspecialchars($borrow['returned_at']) : '-' ?></td>
                    <td>
                        <form method="POST" class="d-flex align-items-center" onsubmit="return confirm('Are you sure you want to update the return status?');">
                            <input type="hidden" name="borrow_id" value="<?= $borrow['id'] ?>">
                            <select name="returned" class="form-select form-select-sm me-2" style="width: auto;">
                                <option value="0" <?= !$borrow['returned'] ? 'selected' : '' ?>>Not Returned</option>
                                <option value="1" <?= $borrow['returned'] ? 'selected' : '' ?>>Returned</option>
                            </select>
                            <button type="submit" class="btn btn-primary btn-sm">Update</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
