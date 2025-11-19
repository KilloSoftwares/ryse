<?php
include "db_connect.php";

$message = "";
$next_invoice_no = "";

// === Auto-generate next invoice number ===
$result = $conn->query("SELECT invoice_no FROM purchases_journal ORDER BY id DESC LIMIT 1");
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $last_invoice = $row['invoice_no'];

    // Extract number from format INV0001
    preg_match('/\d+$/', $last_invoice, $matches);
    $num = $matches ? intval($matches[0]) : 0;
    $next_num = str_pad($num + 1, 4, '0', STR_PAD_LEFT);
    $next_invoice_no = "INV" . $next_num;
} else {
    $next_invoice_no = "INV0001"; // first invoice
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'];
    $supplier = trim($_POST['supplier']);
    $invoice_no = $_POST['invoice_no'];
    $goods = $_POST['goods'];
    $amount = $_POST['amount'];
    $payment_type = $_POST['payment_type'];

    // === Insert supplier if not exists ===
    $stmt = $conn->prepare("SELECT id FROM suppliers WHERE name=?");
    $stmt->bind_param("s", $supplier);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt2 = $conn->prepare("INSERT INTO suppliers (name) VALUES (?)");
        $stmt2->bind_param("s", $supplier);
        $stmt2->execute();
        $supplier_id = $stmt2->insert_id;
    } else {
        $row = $result->fetch_assoc();
        $supplier_id = $row['id'];
    }

    // === Insert into Purchases Journal ===
    $stmt = $conn->prepare("
        INSERT INTO purchases_journal (date, supplier_id, invoice_no, goods, amount) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("sissd", $date, $supplier_id, $invoice_no, $goods, $amount);
    $stmt->execute();

    // === Ledger entries ===
    // Debit Purchases
    $stmt = $conn->prepare("INSERT INTO ledger (date, account, debit) VALUES (?, 'Purchases', ?)");
    $stmt->bind_param("sd", $date, $amount);
    $stmt->execute();

    if ($payment_type === 'credit') {
        $acc = "Supplier: $supplier";
        $stmt = $conn->prepare("INSERT INTO ledger (date, account, credit) VALUES (?, ?, ?)");
        $stmt->bind_param("ssd", $date, $acc, $amount);
        $stmt->execute();
    } else {
        // Cash purchase → Cash Book + Credit Cash/Bank
        $stmt = $conn->prepare("INSERT INTO cash_book (date, particulars, type, amount) VALUES (?, ?, 'credit', ?)");
        $stmt->bind_param("ssd", $date, $supplier, $amount);
        $stmt->execute();

        $stmt = $conn->prepare("INSERT INTO ledger (date, account, credit) VALUES (?, 'Cash/Bank', ?)");
        $stmt->bind_param("sd", $date, $amount);
        $stmt->execute();
    }

    $message = "✅ Transaction recorded successfully!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
  <div class="row justify-content-center">
    <div class="col-lg-6">

      <div class="card shadow-lg border-0 rounded-4">
        <div class="card-header bg-primary text-white text-center rounded-top-4">
          <h4>Eliam</h4>
        </div>
        <div class="card-body p-4">

          <?php if ($message): ?>
            <div class="alert alert-success text-center"><?= $message ?></div>
          <?php endif; ?>

          <form method="post">
            <div class="mb-3">
              <label class="form-label">Date</label>
              <input type="date" name="date" class="form-control" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Supplier</label>
              <input type="text" name="supplier" class="form-control" placeholder="e.g. Bamburi Cement Ltd" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Invoice No.</label>
              <input type="text" name="invoice_no" class="form-control" value="<?= $next_invoice_no ?>" readonly>
            </div>

            <div class="mb-3">
              <label class="form-label">Goods (Details)</label>
              <input type="text" name="goods" class="form-control" placeholder="e.g. 20 Bags Cement" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Amount (KES)</label>
              <input type="number" step="0.01" name="amount" class="form-control" placeholder="e.g. 12000" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Payment Type</label>
              <select name="payment_type" class="form-select" required>
                <option value="credit">Credit (on account)</option>
                <option value="cash">Cash/Bank (immediate)</option>
              </select>
            </div>

            <div class="d-grid">
              <button type="submit" class="btn btn-primary btn-lg rounded-3"> Save Transaction</button>
            </div>
          </form>
        </div>
      </div>

    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
