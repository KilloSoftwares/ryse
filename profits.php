<?php
include 'db_connect.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

$message = '';

// === Fetch unique batch names (supplier filter) ===
$batch_list = [];
$batch_query = $conn->query("SELECT DISTINCT batch FROM stock_in ORDER BY batch ASC");
while ($row = $batch_query->fetch_assoc()) {
    $batch_list[] = $row['batch'];
}

// === Filter selection ===
$selected_batch = $_POST['batch'] ?? '';

// === Step 1: Get all products, with qty sold and total change from related orders ===
$products = [];
$qry = $conn->query("
    SELECT 
        p.id,
        p.name,
        p.price,
        c.name AS category_name,
        IFNULL(SUM(oi.qty),0) AS quantity_sold,
        IFNULL(SUM(o.`change`),0) AS total_change
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    LEFT JOIN order_items oi ON oi.product_id = p.id
    LEFT JOIN orders o ON o.id = oi.order_id
    GROUP BY p.id, p.name, p.price, c.name
    ORDER BY c.name ASC, p.name ASC
");

while ($row = $qry->fetch_assoc()) {
    $products[$row['name']] = [
        'sale_price'    => $row['price'],
        'quantity_sold' => $row['quantity_sold'],
        'category'      => $row['category_name'],
        'total_change'  => $row['total_change']
    ];
}

// === Step 2: Fetch purchase stock data (for cost side) ===
$stock_data = [];
$sql_stock = "SELECT product_name, unit_price, quantity, batch FROM stock_in";
if ($selected_batch !== '') {
    $sql_stock .= " WHERE batch = '" . $conn->real_escape_string($selected_batch) . "'";
}
$stock_query = $conn->query($sql_stock);
while ($row = $stock_query->fetch_assoc()) {
    $pname = $row['product_name'];
    if (!isset($stock_data[$pname])) {
        $stock_data[$pname] = ['total_qty' => 0, 'total_cost' => 0];
    }
    $stock_data[$pname]['total_qty']  += $row['quantity'];
    $stock_data[$pname]['total_cost'] += ($row['unit_price'] * $row['quantity']);
}

// === Step 3: Compute revenue, cost, and profit ===
$profit_data = [];
$total_profit = 0;

foreach ($products as $pname => $p) {
    if ($selected_batch !== '' && !isset($stock_data[$pname])) {
        continue; // skip products not in the selected supplier batch
    }

    $base_price = $p['sale_price'];
    $quantity_sold = $p['quantity_sold'];
    $total_change = $p['total_change'];

    // Distribute the total change across sold items (if any)
    $change_per_item = $quantity_sold > 0 ? ($total_change / $quantity_sold) : 0;

    // Actual selling price includes distributed change
    $selling_price_with_change = $base_price + $change_per_item;

    // Calculate revenue and cost
    $revenue   = $selling_price_with_change * $quantity_sold;

    // Calculate average purchase cost
    $avg_cost = 0;
    if (isset($stock_data[$pname]) && $stock_data[$pname]['total_qty'] > 0) {
        $avg_cost = $stock_data[$pname]['total_cost'] / $stock_data[$pname]['total_qty'];
    }
    $cost = $avg_cost * $quantity_sold;
    $profit = $revenue - $cost;

    $total_profit += $profit;

    $profit_data[] = [
        'name'          => $pname,
        'category'      => $p['category'],
        'quantity_sold' => $quantity_sold,
        'sale_price'    => $selling_price_with_change,
        'cost'          => $cost,
        'revenue'       => $revenue,
        'profit'        => $profit
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Profit Report</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body {font-family: Arial, sans-serif; background: #f4f4f9; padding: 20px;}
    .container {max-width: 1100px; margin: auto; background: #fff; padding: 20px; border-radius: 6px; box-shadow: 0 2px 6px rgba(0,0,0,0.1);}
    h2 {text-align: center; margin-bottom: 20px; color: #333;}
    form {text-align: center; margin-bottom: 15px;}
    select, button {padding: 6px 10px; margin: 0 5px;}
    .print-btn {float: right; cursor: pointer; background: #28a745; color: #fff; border: none; padding: 6px 12px; border-radius: 4px;}
    .print-btn i {margin-right: 5px;}
    table {width: 100%; border-collapse: collapse; margin-top: 15px;}
    table th, table td {border: 1px solid #ddd; padding: 8px 10px; text-align: center;}
    table th {background-color: #007BFF; color: white;}
    .profit-positive {color: green; font-weight: bold;}
    .profit-negative {color: red; font-weight: bold;}
    .total-row {font-weight: bold; background: #e6ffe6;}
    @media print {
        body {background: #fff; padding: 0;}
        .print-btn, form {display: none;}
        table th, table td {font-size: 12pt; padding: 6px;}
    }
  </style>
</head>
<body>
<div class="container">
  <h2>📊 Profit Report <?= $selected_batch ? "(Supplier: " . htmlspecialchars($selected_batch) . ")" : "" ?></h2>

  <form method="post">
    <label for="batch">Filter by Supplier:</label>
    <select name="batch" id="batch">
      <option value="">-- All Suppliers --</option>
      <?php foreach ($batch_list as $batch): ?>
        <option value="<?= htmlspecialchars($batch) ?>" <?= $batch === $selected_batch ? 'selected' : '' ?>>
          <?= htmlspecialchars($batch) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <button type="submit">Filter</button>
    <button type="button" class="print-btn" onclick="window.print()">
        <i class="fas fa-print"></i> Print
    </button>
  </form>

  <table>
    <thead>
      <tr>
        <th>Category</th>
        <th>Product</th>
        <th>Quantity Sold</th>
        <th>Selling Price (incl. Change)</th>
        <th>Purchase Cost</th>
        <th>Total Revenue</th>
        <th>Profit</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!empty($profit_data)): ?>
        <?php foreach ($profit_data as $p): ?>
        <tr>
          <td><?= htmlspecialchars($p['category']) ?></td>
          <td><?= htmlspecialchars($p['name']) ?></td>
          <td><?= $p['quantity_sold'] ?></td>
          <td><?= number_format($p['sale_price'], 2) ?></td>
          <td><?= number_format($p['cost'], 2) ?></td>
          <td><?= number_format($p['revenue'], 2) ?></td>
          <td class="<?= $p['profit'] >= 0 ? 'profit-positive' : 'profit-negative' ?>">
            <?= number_format($p['profit'], 2) ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <tr class="total-row">
          <td colspan="6">TOTAL PROFIT</td>
          <td><?= number_format($total_profit, 2) ?></td>
        </tr>
      <?php else: ?>
        <tr><td colspan="7">No records found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
</body>
</html>
