<?php
include('db_connect.php');
if (session_status() === PHP_SESSION_NONE) session_start();

// ===== Fetch only Nails products =====
$products = [];
$qry = $conn->query("
    SELECT 
        p.id, 
        p.name,
        p.category_id,
        COALESCE(NULLIF(p.total_brought, 0), c.quantity) AS total_brought,
        c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE c.name = 'Nails'
    ORDER BY p.id ASC
");
while($row = $qry->fetch_assoc()) $products[] = $row;

// ===== Orders count per product (paid only) =====
$ordersMap = [];
$qry2 = $conn->query("
    SELECT oi.product_id, SUM(oi.qty) AS total_orders
    FROM order_items oi
    INNER JOIN orders o ON o.id = oi.order_id
    WHERE o.amount_tendered > 0
    GROUP BY oi.product_id
");
while($r = $qry2->fetch_assoc()) {
    $ordersMap[(int)$r['product_id']] = (int)$r['total_orders'];
}

// ===== Total orders for Nails category =====
$totalOrders = 0;
foreach ($products as $p) {
    $pid = (int)$p['id'];
    $totalOrders += $ordersMap[$pid] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Nails Inventory</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body{background:#f7f7fb;}
    .card{border:0;box-shadow:0 8px 24px rgba(0,0,0,.06);border-radius:14px;}
    .badge{padding:.25rem .5rem;border-radius:999px;}
    .badge-ok{background:#ecfdf5;color:#065f46;}
    .badge-low{background:#fff7ed;color:#9a3412;}
  </style>
</head>
<body>
<div class="container py-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h3 class="mb-0">🔩 Nails Inventory</h3>
    <a href="inventory.php" class="btn btn-outline-secondary btn-sm">← Back to All Products</a>
  </div>

  <div class="card">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-striped table-hover mb-0">
          <thead class="thead-light">
            <tr>
              <th>#</th>
              <th>Product</th>
              <th>Category</th>
              <th>Total Brought</th>
              <th>Total Orders (All Nails)</th>
              <th>Remaining</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $i = 1;
            $carryRemaining = null; // for row-by-row deduction
            foreach ($products as $p):
                $brought = (int)$p['total_brought'];

                // special rule: carry forward remainders
                if ($carryRemaining === null) {
                    $remaining = max($brought - $totalOrders, 0);
                } else {
                    $remaining = max($carryRemaining - $totalOrders, 0);
                }
                $carryRemaining = $remaining;

                $badgeClass = $remaining > 0 ? 'badge-ok' : 'badge-low';
            ?>
              <tr>
                <td class="text-center"><?php echo $i++; ?></td>
                <td><?php echo htmlspecialchars($p['name']); ?></td>
                <td><?php echo htmlspecialchars($p['category_name']); ?></td>
                <td><?php echo $brought; ?></td>
                <td><?php echo $totalOrders; ?></td>
                <td><span class="badge <?php echo $badgeClass; ?>"><?php echo $remaining; ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
</body>
</html>
