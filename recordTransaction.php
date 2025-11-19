<?php
include "db_connect.php";

$supplier_filter = isset($_GET['supplier_id']) ? intval($_GET['supplier_id']) : null;

// Get distinct suppliers
$supplier_query = $conn->query("SELECT id, name FROM suppliers ORDER BY name ASC");
$suppliers = [];
while ($row = $supplier_query->fetch_assoc()) {
    $suppliers[] = $row;
}

// Supplier details
$supplier_name = "";
$supplier_details = [];
$total_purchases = $total_paid = $balance = 0;

if ($supplier_filter) {
    // Supplier name
    $stmt = $conn->prepare("SELECT name FROM suppliers WHERE id=?");
    $stmt->bind_param("i", $supplier_filter);
    $stmt->execute();
    $supplier_name = $stmt->get_result()->fetch_assoc()['name'] ?? "";

    // Purchases for this supplier
    $stmt = $conn->prepare("
        SELECT pj.date, pj.invoice_no, pj.goods, pj.amount, 
               IF(cb.id IS NOT NULL, 'Cash', 'Credit') AS payment_type
        FROM purchases_journal pj
        LEFT JOIN cash_book cb 
            ON cb.particulars=? AND cb.amount=pj.amount AND cb.date=pj.date
        WHERE pj.supplier_id=?
        ORDER BY pj.date DESC
    ");
    $stmt->bind_param("si", $supplier_name, $supplier_filter);
    $stmt->execute();
    $supplier_details = $stmt->get_result();

    // Totals
    $stmt3 = $conn->prepare("SELECT SUM(amount) AS total_purchases FROM purchases_journal WHERE supplier_id=?");
    $stmt3->bind_param("i", $supplier_filter);
    $stmt3->execute();
    $total_purchases = $stmt3->get_result()->fetch_assoc()['total_purchases'] ?? 0;

    $stmt4 = $conn->prepare("SELECT SUM(amount) AS total_paid FROM cash_book WHERE particulars=? AND type='credit'");
    $stmt4->bind_param("s", $supplier_name);
    $stmt4->execute();
    $total_paid = $stmt4->get_result()->fetch_assoc()['total_paid'] ?? 0;

    $balance = $total_purchases - $total_paid;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Supplier Records</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f5f5f5;
      margin: 0;
      padding: 20px;
    }
    .container {
      max-width: 1200px;
      margin: 0 auto;
      background: #fff;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      margin-bottom: 20px;
    }
    .header h1 {
      margin: 0;
      font-size: 24px;
    }
    .add-btn {
      background-color: green;
      color: white;
      padding: 8px 14px;
      font-size: 14px;
      text-decoration: none;
      border-radius: 4px;
      border: none;
      cursor: pointer;
    }
    .add-btn:hover {
      background-color: darkgreen;
    }

    .supplier-vertical {
      display: flex;
      flex-direction: column;
      gap: 8px;
      margin-bottom: 20px;
    }

    .supplier-vertical a {
      background: #007BFF;
      color: white;
      padding: 10px;
      border-radius: 4px;
      text-decoration: none;
      font-size: 14px;
    }
    .supplier-vertical a:hover {
      background: #0056b3;
    }

    .filter-info {
      margin: 10px 0 20px;
      font-size: 14px;
      color: #555;
    }
    .clear-btn {
      font-size: 13px;
      margin-left: 10px;
      color: #d00;
      text-decoration: none;
    }
    .clear-btn:hover {
      text-decoration: underline;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 14px;
    }
    th, td {
      border: 1px solid #ddd;
      padding: 8px 10px;
      text-align: left;
    }
    th {
      background-color: #f2f2f2;
      font-weight: bold;
    }
    tr:hover {
      background-color: #fafafa;
    }

    .center {
      text-align: center;
      color: #888;
      padding: 20px;
    }
    .summary-box {
      background: #f9f9f9;
      border-left: 4px solid #007BFF;
      padding: 10px 15px;
      margin-bottom: 15px;
      border-radius: 6px;
    }
    .badge {
      padding: 3px 6px;
      border-radius: 3px;
      font-size: 12px;
    }
    .cash {
      background: #28a745;
      color: white;
    }
    .credit {
      background: #ffc107;
      color: #000;
    }
  </style>
</head>
<body>
  <div class="container">
    <!-- Header -->
    <div class="header">
      <h1>👨‍💼 Supplier Records</h1>
      <a href="index.php?page=add_purchase" class="add-btn">➕ Add Purchase</a>
    </div>

    <!-- Vertical Supplier Buttons -->
    <div class="supplier-vertical">
      <?php foreach ($suppliers as $s): ?>
        <a href="index.php?page=viewSuppliers&supplier_id=<?= $s['id'] ?>">
          <?= htmlspecialchars($s['name']) ?>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- Supplier info -->
    <?php if ($supplier_filter && $supplier_name): ?>
      <div class="filter-info">
        Showing transactions for: <strong><?= htmlspecialchars($supplier_name) ?></strong>
        <a href="index.php?page=viewSuppliers" class="clear-btn">[Clear Filter]</a>
      </div>

      <div class="summary-box">
        <p>
          Total Purchases: <b>KSh <?= number_format($total_purchases,2) ?></b><br>
          Total Paid: <b>KSh <?= number_format($total_paid,2) ?></b><br>
          Balance: <b style="color:red">KSh <?= number_format($balance,2) ?></b>
        </p>
      </div>

      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Date</th>
            <th>Invoice No</th>
            <th>Goods</th>
            <th>Amount (KES)</th>
            <th>Payment Type</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($supplier_details && $supplier_details->num_rows > 0): ?>
            <?php $i=1; while ($row = $supplier_details->fetch_assoc()): ?>
              <tr>
                <td><?= $i++ ?></td>
                <td><?= htmlspecialchars($row['date']) ?></td>
                <td><?= htmlspecialchars($row['invoice_no']) ?></td>
                <td><?= htmlspecialchars($row['goods']) ?></td>
                <td><?= number_format($row['amount'],2) ?></td>
                <td>
                  <?php if ($row['payment_type']=="Cash"): ?>
                    <span class="badge cash">Cash</span>
                  <?php else: ?>
                    <span class="badge credit">Credit</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="6" class="center">No transactions found</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="center">🛈 Please select a supplier on the left to view transactions.</div>
    <?php endif; ?>
  </div>
</body>
</html>
