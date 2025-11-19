<?php
include('db_connect.php');
if (session_status() === PHP_SESSION_NONE) session_start();

// ===== Fetch products =====
$products = [];
$qry = $conn->query("
    SELECT 
        p.id, 
        p.name,
        p.category_id,
        p.total_brought,
        c.name AS category_name,
        c.quantity AS category_quantity
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    ORDER BY c.name ASC, p.id ASC
");
while($row = $qry->fetch_assoc()) $products[] = $row;

// ===== Orders per product =====
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

// ===== Nails aggregation =====
$nails_total_brought = 0;
$nails_orders = 0;

// Fetch total brought from category ONLY for Nails
$catQry = $conn->query("SELECT quantity FROM categories WHERE name='Nails' LIMIT 1");
$nails_total_brought = $catQry->num_rows ? (int)$catQry->fetch_assoc()['quantity'] : 0;

// Sum all orders for products in Nails category or with name containing "Nails"
$ordersQry = $conn->query("
    SELECT SUM(oi.qty) AS total_orders
    FROM order_items oi
    INNER JOIN orders o ON o.id = oi.order_id
    INNER JOIN products p ON p.id = oi.product_id
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE o.amount_tendered > 0
    AND (c.name='Nails' OR p.name LIKE '%Nails%')
");
$nails_orders = $ordersQry->num_rows ? (int)$ordersQry->fetch_assoc()['total_orders'] : 0;

// Remaining stock
$nails_remaining = max($nails_total_brought - $nails_orders, 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Inventory</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap4.min.css" rel="stylesheet">
<style>
body{background:#f7f7fb;}
.card{border:0;box-shadow:0 8px 24px rgba(0,0,0,.06);border-radius:14px;}
.badge{padding:.25rem .5rem;border-radius:999px;}
.badge-ok{background:#ecfdf5;color:#065f46;}
.badge-low{background:#fff7ed;color:#9a3412;}
#printBtn{position:fixed;bottom:20px;right:20px;background:#059669;color:#fff;border:none;border-radius:50%;width:54px;height:54px;font-size:20px;box-shadow:0 6px 16px rgba(0,0,0,.2);cursor:pointer;z-index:1000;}
#printBtn:hover{background:#047857;}
.stock-btn{background:#2563eb;color:#fff;border:none;padding:6px 12px;border-radius:8px;font-size:14px;margin-left:8px;cursor:pointer;}
.stock-btn:hover{background:#1d4ed8;}
.clickable-row:hover {background:#f1f5f9 !important; cursor:pointer;}
</style>
</head>
<body>
<div class="container py-4">

  <!-- Header with title and buttons -->
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h3 class="mb-0">📦 Inventory</h3>
    <div>
      <button class="stock-btn" onclick="window.location.href='index.php?page=update_stock'">Update Category</button>
      <button class="stock-btn" onclick="window.location.href='index.php?page=new_stock'">Update Stock</button>
      <button class="stock-btn" onclick="window.location.href='index.php?page=awaiting_stock'">Awaiting Stock</button>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <div class="table-responsive">
        <table id="invTable" class="table table-striped table-hover mb-0">
          <thead class="thead-light">
            <tr>
              <th>#</th>
              <th>Product</th>
              <th>Category</th>
              <th>Purchases</th>
              <th>Orders</th>
              <th>Remaining</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $i = 1;
            foreach($products as $p):
                // skip nails products individually
                if(stripos($p['category_name'], 'Nails') !== false || stripos($p['name'], 'Nails') !== false)
                    continue;

                $brought = (int)$p['total_brought']; // regular product quantity
                $orders = $ordersMap[(int)$p['id']] ?? 0;
                $remaining = max($brought - $orders, 0);
                $badgeClass = $remaining > 0 ? 'badge-ok' : 'badge-low';
            ?>
            <tr>
                <td><?php echo $i++; ?></td>
                <td><?php echo htmlspecialchars($p['name']); ?></td>
                <td><?php echo htmlspecialchars($p['category_name']); ?></td>
                <td><?php echo $brought; ?></td>
                <td><?php echo $orders; ?></td>
                <td><span class="badge <?php echo $badgeClass; ?>"><?php echo $remaining; ?></span></td>
            </tr>
            <?php endforeach; ?>

            <!-- Nails row -->
            <!-- <tr>
                <td><?php echo $i++; ?></td>
                <td><strong>Nails</strong></td>
                <td>Nails</td>
                <td><?php echo $nails_total_brought; ?></td>
                <td><?php echo $nails_orders; ?></td>
                <td><span class="badge <?php echo ($nails_remaining>0?'badge-ok':'badge-low'); ?>"><?php echo $nails_remaining; ?></span></td>
            </tr> -->
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<button id="printBtn" title="Print Inventory">🖨️</button>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap4.min.js"></script>
<script>
$(function(){
    $('#invTable').DataTable({paging:false,searching:true,info:false,order:[[1,'asc']]});
    
    $('#printBtn').on('click', function(){
        var content = document.querySelector('.card').innerHTML;
        var css = `<style>
          body {font-family: Arial, sans-serif; margin:0; padding:24px;}
          h2 {text-align:center;}
          table{width:100%;border-collapse:collapse;margin-top:10px;}
          th, td{padding:10px;border-bottom:1px solid #eef0f3;text-align:left;}
          thead th{font-size:12px;color:#6b7280;text-transform:uppercase;}
          .badge{padding:2px 8px;border-radius:999px;font-size:12px;}
          .badge-ok{background:#ecfdf5;color:#065f46;}
          .badge-low{background:#fff7ed;color:#9a3412;}
        </style>`;
        var w = window.open('', '', 'width=900,height=600');
        w.document.write('<html><head><title>Inventory Print</title>'+css+'</head><body>');
        w.document.write('<h2>Inventory Summary</h2>');
        w.document.write(content);
        w.document.write('</body></html>');
        w.document.close();
        w.focus();
        w.print();
        w.close();
    });
});
</script>
</body>
</html>
