<?php
include 'db_connect.php';

$supplier_filter = isset($_GET['supplier_id']) ? intval($_GET['supplier_id']) : null;

// === Get distinct suppliers ===
$supplier_query = $conn->query("SELECT id, name FROM suppliers ORDER BY name ASC");
$suppliers = [];
while ($row = $supplier_query->fetch_assoc()) {
    $suppliers[] = $row;
}

// === Get supplier records (filtered if needed) ===
if ($supplier_filter) {
    // Supplier details
    $stmt = $conn->prepare("SELECT * FROM suppliers WHERE id = ?");
    $stmt->bind_param("i", $supplier_filter);
    $stmt->execute();
    $supplier_result = $stmt->get_result();
    $supplier_data = $supplier_result->fetch_assoc();

    // Purchases Journal data
    $stmt2 = $conn->prepare("
        SELECT date, invoice_no, goods, amount, payment_type
        FROM purchases_journal
        WHERE supplier_id = ?
        ORDER BY date DESC
    ");
    $stmt2->bind_param("i", $supplier_filter);
    $stmt2->execute();
    $purchases = $stmt2->get_result();

    // If no records, insert placeholder row
    if ($purchases->num_rows === 0) {
        $insert = $conn->prepare("
            INSERT INTO purchases_journal (supplier_id, date, invoice_no, goods, amount, payment_type)
            VALUES (?, CURDATE(), 'N/A', 'Opening Balance', 0, 'cash')
        ");
        $insert->bind_param("i", $supplier_filter);
        $insert->execute();

        // Fetch again
        $stmt2->execute();
        $purchases = $stmt2->get_result();
    }

    // ===== Calculate summary totals =====
    $total_debit = 0;   // paid (always from amount)
    $total_credit = 0;  // owed

    if ($purchases && $purchases->num_rows > 0) {
        $purchases->data_seek(0);
        while ($r = $purchases->fetch_assoc()) {
            // Debit always adds amount
            $total_debit += $r['amount'];

            // Credit only when payment_type = credit
            if (strtolower($r['payment_type']) === 'credit') {
                $total_credit += $r['amount'];
            }
        }
        $outstanding = $total_credit - $total_debit;
        $purchases->data_seek(0);
    } else {
        $outstanding = 0;
    }
} else {
    $supplier_data = null;
    $purchases = null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Suppliers</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <style>
    body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
    .container { max-width: 1200px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    .header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; margin-bottom: 20px; }
    .header h1 { margin: 0; font-size: 24px; }
    .add-btn { background-color: green; color: white; padding: 8px 14px; font-size: 14px; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; }
    .add-btn:hover { background-color: darkgreen; }
    .supplier-vertical { display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px; }
    .supplier-vertical a { background: #007BFF; color: white; padding: 10px; border-radius: 4px; text-decoration: none; font-size: 14px; }
    .supplier-vertical a:hover { background: #0056b3; }
    .supplier-vertical a.active { background: #28a745; }
    .filter-info { margin: 10px 0 20px; font-size: 14px; color: #555; }
    .clear-btn { font-size: 13px; margin-left: 10px; color: #d00; text-decoration: none; }
    .clear-btn:hover { text-decoration: underline; }
    table { width: 100%; border-collapse: collapse; font-size: 14px; }
    th, td { border: 1px solid #ddd; padding: 8px 10px; text-align: left; }
    th { background-color: #f2f2f2; font-weight: bold; }
    tr:hover { background-color: #fafafa; }
    .center { text-align: center; color: #888; padding: 20px; }
    .debit { color: green; font-weight: bold; }
    .credit { color: red; font-weight: bold; }
    .summary { margin-top: 20px; font-size: 15px; background: #f9f9f9; padding: 15px; border-radius: 6px; }
    .summary strong { display: inline-block; min-width: 200px; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>👥 Supplier Records</h1>
      <a href="index.php?page=addSupplier" class="add-btn">➕ Add Supplier</a>
    </div>

    <!-- Supplier List -->
    <div class="supplier-vertical">
      <?php if ($supplier_filter && $supplier_data): ?>
        <a href="index.php?page=viewSuppliers&supplier_id=<?= $supplier_data['id'] ?>" class="active">
          <?= htmlspecialchars($supplier_data['name']) ?>
        </a>
      <?php else: ?>
        <?php foreach ($suppliers as $s): ?>
          <a href="index.php?page=viewSuppliers&supplier_id=<?= $s['id'] ?>">
            <?= htmlspecialchars($s['name']) ?>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Filter Info -->
    <?php if ($supplier_filter && $supplier_data): ?>
      <div class="filter-info">
        Showing records for supplier: <strong><?= htmlspecialchars($supplier_data['name']) ?></strong>
        <a href="index.php?page=viewSuppliers" class="clear-btn">[Clear Filter]</a>
      </div>
    <?php endif; ?>

    <!-- Purchases Table -->
    <?php if ($supplier_filter && $supplier_data): ?>
      <table>
        <thead>
          <tr>
            <th>Date</th>
            <th>Invoice No</th>
            <th>Goods</th>
            <th>Debit (Paid)</th>
            <th>Credit (Owed)</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($purchases && $purchases->num_rows > 0): ?>
            <?php while ($row = $purchases->fetch_assoc()): ?>
              <tr>
                <td><?= htmlspecialchars($row['date']) ?></td>
                <td><?= htmlspecialchars($row['invoice_no']) ?></td>
                <td><?= htmlspecialchars($row['goods']) ?></td>
                <td class="debit"><?= number_format($row['amount'], 2) ?></td>
                <td class="credit"><?= strtolower($row['payment_type']) === 'credit' ? number_format($row['amount'], 2) : '-' ?></td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="5" class="center">No purchases found for this supplier.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>

      <!-- Totals -->
      <div class="summary">
        <div><strong>Total Debit (Paid):</strong> KSh <?= number_format($total_debit, 2) ?></div>
        <div><strong>Total Credit (Owed):</strong> KSh <?= number_format($total_credit, 2) ?></div>
        <div>
          <strong>Outstanding Balance:</strong>
          <span style="color:<?= ($outstanding > 0 ? 'red' : 'green') ?>;">
            KSh <?= number_format($outstanding, 2) ?>
          </span>
        </div>
        <div>
          <strong>Status:</strong>
          <span style="color:<?= ($outstanding > 0 ? 'red' : 'green') ?>;">
            <?= $outstanding > 0 ? 'Creditor (Supplier still owed)' : 'Settled' ?>
          </span>
        </div>
      </div>
    <?php else: ?>
      <div class="center">🛈 Please select a supplier on the left to view their records.</div>
    <?php endif; ?>
  </div>
</body>
</html>
