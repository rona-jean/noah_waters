<?php
// Only show navbar if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="admin_dashboard.php">Noah Waters Admin</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="adminNavbar">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : ''; ?>" 
                       href="admin_dashboard.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_products.php' ? 'active' : ''; ?>" 
                       href="admin_products.php">Products</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_orders.php' ? 'active' : ''; ?>" 
                       href="admin_orders.php">Orders</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_borrowed.php' ? 'active' : ''; ?>" 
                       href="admin_borrowed.php">Borrowed Items</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_users.php' ? 'active' : ''; ?>" 
                       href="admin_users.php">Users</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_reports.php' ? 'active' : ''; ?>" 
                       href="admin_reports.php">Reports</a>
                </li>
            </ul>
            
            <div class="navbar-text text-white me-3">
                Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>
            </div>
            <a href="logout.php" class="btn btn-light btn-sm">Logout</a>
        </div>
    </div>
</nav>

<style>
.navbar {
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.navbar-brand {
    font-weight: bold;
}
.nav-link {
    font-weight: 500;
}
.nav-link.active {
    font-weight: 600;
}
</style> 