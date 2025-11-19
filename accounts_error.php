<?php
// --- Database Connection ---
$host = "localhost";
$user = "root";
$pass = "";
$db   = "eliam";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->query("CREATE TABLE IF NOT EXISTS negative_explanations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    record_id INT NOT NULL,
    explanation TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_id'])) {
    $record_id  = (int)$_POST['record_id'];
    $explanation = $conn->real_escape_string($_POST['explanation']);
    $conn->query("INSERT INTO negative_explanations (record_id, explanation) VALUES ($record_id, '$explanation')");
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

$orders = $conn->query("
    SELECT o.id as order_id, o.ref_no, o.total_amount, o.amount_tendered, o.change_value, o.date_created,
           oi.product_id, oi.qty, oi.price, oi.amount,
           p.name AS product_name
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE o.change_value < 0
    ORDER BY o.id DESC
");

$order_data = [];
if ($orders && $orders->num_rows > 0) {
    while($row = $orders->fetch_assoc()){
        $order_data[$row['order_id']]['info'] = [
            'ref_no' => $row['ref_no'],
            'amount_tendered' => $row['amount_tendered'],
            'change_value' => $row['change_value'],
            'date_created' => $row['date_created']
        ];
        $order_data[$row['order_id']]['items'][] = [
            'product_id' => $row['product_id'],
            'product_name' => $row['product_name'],
            'qty' => $row['qty'],
            'price' => $row['price'],
            'amount' => $row['amount']
        ];
    }
}

$negatives = $conn->query("SELECT id, ref_no, change_value FROM orders WHERE change_value < 0");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Negative Orders Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .card { border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .card-header { font-weight: bold; font-size: 1rem; border-radius: 12px 12px 0 0; }
        .table th { background-color: #343a40 !important; color: white; text-align: center; }
        .table td { vertical-align: middle; }
        h2 { font-weight: bold; color: #0d6efd; }
        .form-control { border-radius: 8px; }
        .btn { border-radius: 8px; }
    </style>
</head>
<body>
<div class="container py-5">

    <h2 class="mb-4 text-center">Orders with Negative Change</h2>
    <?php if(!empty($order_data)): ?>
        <?php foreach($order_data as $oid => $data): 
            $adjusted_paid = $data['info']['amount_tendered'] - $data['info']['change_value'];
        ?>
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    Order #<?= htmlspecialchars($oid) ?> | Ref: <?= htmlspecialchars($data['info']['ref_no']) ?> |
                    Date: <?= htmlspecialchars($data['info']['date_created']) ?>
                </div>
                <div class="card-body">
                    <p class="mb-3">
                        <strong>Paid:</strong> <?= htmlspecialchars($data['info']['amount_tendered']) ?> |
                        <strong>Discount:</strong> <span class="text-danger"><?= htmlspecialchars($data['info']['change_value']) ?></span> |
                        <strong>Adjusted Paid:</strong> <span class="text-success"><?= htmlspecialchars($adjusted_paid) ?></span>
                    </p>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle text-center">
                            <thead class="table-secondary">
                                <tr>
                                    <th>Product ID</th>
                                    <th>Product Name</th>
                                    <th>Qty</th>
                                    <th>Price</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($data['items'] as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['product_id']) ?></td>
                                        <td><?= htmlspecialchars($item['product_name']) ?></td>
                                        <td><?= htmlspecialchars($item['qty']) ?></td>
                                        <td><?= htmlspecialchars($item['price']) ?></td>
                                        <td><?= htmlspecialchars($item['amount']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="alert alert-info text-center shadow-sm">No orders with negative change found.</div>
    <?php endif; ?>

    <h2 class="mb-4 mt-5 text-center">Negative Values Report</h2>
    <div class="card p-3 shadow-sm">
        <div class="table-responsive">
            <table class="table table-bordered table-striped bg-white align-middle text-center">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Ref No</th>
                        <th>Negative Value</th>
                        <th>Explanation</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($negatives && $negatives->num_rows > 0): ?>
                        <?php while($row = $negatives->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['id']) ?></td>
                                <td><?= htmlspecialchars($row['ref_no']) ?></td>
                                <td class="text-danger fw-bold"><?= htmlspecialchars($row['change_value']) ?></td>
                                <td>
                                    <form method="POST" class="d-flex">
                                        <input type="hidden" name="record_id" value="<?= $row['id'] ?>">
                                        <input type="text" name="explanation" class="form-control me-2" placeholder="Enter explanation" required>
                                        <button class="btn btn-success btn-sm" type="submit">Save</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center">No negative values found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>
