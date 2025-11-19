<?php
include('db_connect.php');
if (session_status() === PHP_SESSION_NONE) session_start();

// ===== Top stats =====
$total_categories = (int)$conn->query("SELECT COUNT(*) FROM categories")->fetch_row()[0];
$total_orders     = (int)$conn->query("SELECT COUNT(*) FROM orders")->fetch_row()[0];
$total_products   = (int)$conn->query("SELECT COUNT(*) FROM products")->fetch_row()[0];
$total_users      = (int)$conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];

// ===== Orders per Category for chart =====
$categories = [];
$orders_per_cat = [];
$qry = $conn->query("
    SELECT c.name AS category, COUNT(DISTINCT o.id) AS total_orders
    FROM orders o
    INNER JOIN order_items oi ON o.id = oi.order_id
    INNER JOIN products p ON oi.product_id = p.id
    INNER JOIN categories c ON p.category_id = c.id
    GROUP BY c.id, c.name
    ORDER BY c.name ASC
");
while ($row = $qry->fetch_assoc()) {
    $categories[] = $row['category'];
    $orders_per_cat[] = (int)$row['total_orders'];
}

// ===== Pagination for Orders list =====
$limit = 2; // orders per page
$page = isset($_GET['page_num']) ? max(1, intval($_GET['page_num'])) : 1;
$offset = ($page - 1) * $limit;

// count total orders (with items)
$total_orders_count = (int)$conn->query("
    SELECT COUNT(*) 
    FROM orders o 
    INNER JOIN order_items oi ON o.id = oi.order_id
")->fetch_row()[0];
$total_pages = ceil($total_orders_count / $limit);

// fetch orders with materials
$orders = $conn->query("
    SELECT o.date_created, p.name AS material, oi.price ,o.change
    FROM orders o
    INNER JOIN order_items oi ON o.id = oi.order_id
    INNER JOIN products p ON oi.product_id = p.id
    ORDER BY o.date_created DESC
    LIMIT $limit OFFSET $offset
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="./assets/js/charts/dist/apexcharts.min.js"></script>
  <style>
    .stat-card .display-4{font-weight:700;}
    .card.border-0{box-shadow:0 8px 24px rgba(0,0,0,.06);border-radius:14px;}
  </style>
</head>
<body class="bg-light">
<div class="container-fluid py-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h3 class="mb-0">📊 Dashboard</h3>
    <div>
      <a href="index.php?page=inventory" class="btn btn-outline-primary btn-sm">Go to Inventory</a>
    </div>
  </div>

  <!-- Stat cards -->
  <div class="row">
    <div class="col-md-3 mb-3">
      <div class="card text-white bg-primary border-0 stat-card">
        <div class="card-body">
          <h6 class="mb-1">Categories</h6>
          <div class="display-4"><?php echo $total_categories; ?></div>
        </div>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="card text-white bg-success border-0 stat-card">
        <div class="card-body">
          <h6 class="mb-1">Orders</h6>
          <div class="display-4"><?php echo $total_orders; ?></div>
        </div>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="card text-white bg-info border-0 stat-card">
        <div class="card-body">
          <h6 class="mb-1">Products</h6>
          <div class="display-4"><?php echo $total_products; ?></div>
        </div>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="card text-white bg-dark border-0 stat-card">
        <div class="card-body">
          <h6 class="mb-1">Users</h6>
          <div class="display-4"><?php echo $total_users; ?></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Chart + quick stats -->
  <div class="row">
    <div class="col-md-6 mb-3">
      <div class="card border-0">
        <div class="card-body">
          <h5 class="mb-3">Orders per Category</h5>
          <div id="piechart" style="width:100%;height:350px;"></div>
        </div>
      </div>
    </div>

    <div class="col-md-6 mb-3">
      <div class="card border-0">
        <div class="card-body">
          <h5 class="mb-3">Recent Orders</h5>
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead class="thead-light">
                <tr>
                  <th>Date/Time</th>
                  <th>Material</th>
                  <th>Price</th>
                </tr>
              </thead>
              <tbody>
              <?php if($orders->num_rows > 0): ?>
                <?php while($row = $orders->fetch_assoc()): ?>
                  <tr>
                    <td><?php echo htmlspecialchars(date("m-d-Y H:i:s", strtotime($row['date_created']))); ?></td>
                    <td><?php echo htmlspecialchars($row['material']); ?></td>
                    <td class="text-right"><?php echo number_format((float)$row['price'] + (float)$row['change'], 2); ?></td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr><td colspan="3" class="text-center">No recent orders</td></tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>

          <!-- Pagination with arrows only -->
          <nav class="mt-3">
            <ul class="pagination justify-content-center">
              <?php if($page > 1): ?>
                <li class="page-item">
                  <a class="page-link" href="?page=dashboard&page_num=<?php echo $page-1; ?>" aria-label="Previous">&laquo;</a>
                </li>
              <?php endif; ?>

              <?php if($page < $total_pages): ?>
                <li class="page-item">
                  <a class="page-link" href="?page=dashboard&page_num=<?php echo $page+1; ?>" aria-label="Next">&raquo;</a>
                </li>
              <?php endif; ?>
            </ul>
          </nav>

        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
  var options = {
    chart: { type: 'donut', height: 350 },
    series: <?php echo json_encode($orders_per_cat); ?>,
    labels: <?php echo json_encode($categories, JSON_UNESCAPED_UNICODE); ?>,
    title: { text: 'Orders per Category' },
    responsive: [{
      breakpoint: 480,
      options: { chart: { width: 300 }, legend: { position: 'bottom' } }
    }]
  };
  new ApexCharts(document.querySelector("#piechart"), options).render();
});
</script>
</body>
</html>
