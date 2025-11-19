<?php
include 'db_connect.php';

$batch_filter = isset($_GET['batch']) ? $_GET['batch'] : null;

// Get distinct batch names
$batch_query = $conn->query("SELECT DISTINCT batch FROM stock_in ORDER BY batch ASC");
$batches = [];
while ($row = $batch_query->fetch_assoc()) {
    $batches[] = $row['batch'];
}

// Get stock records (filtered by batch if needed)
if ($batch_filter) {
    $stmt = $conn->prepare("SELECT * FROM stock_in WHERE batch = ? ORDER BY created_at DESC");
    $stmt->bind_param("s", $batch_filter);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Stock In Records</title>
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

    .batch-vertical {
      display: flex;
      flex-direction: column;
      gap: 8px;
      margin-bottom: 20px;
    }

    .batch-vertical a {
      background: #007BFF;
      color: white;
      padding: 10px;
      border-radius: 4px;
      text-decoration: none;
      font-size: 14px;
    }
    .batch-vertical a:hover {
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

    .action-btn {
      padding: 4px 8px;
      font-size: 13px;
      margin-right: 5px;
      border: none;
      border-radius: 3px;
      cursor: pointer;
      text-decoration: none;
    }
    .edit-btn {
      background-color: #ffc107;
      color: #000;
    }
    .edit-btn:hover {
      background-color: #e0a800;
    }
    .delete-btn {
      background-color: #dc3545;
      color: white;
    }
    .delete-btn:hover {
      background-color: #c82333;
    }
  </style>
</head>
<body>
  <div class="container">
    <!-- Header -->
    <div class="header">
      <h1>📦 Stock In Records</h1>
      <a href="index.php?page=add_stock" class="add-btn">➕ Add Stock</a>
    </div>

    <!-- Vertical Batch Buttons -->
    <div class="batch-vertical">
      <?php foreach ($batches as $batch): ?>
        <a href="index.php?page=view_stock&batch=<?= urlencode($batch) ?>">
          <?= htmlspecialchars($batch) ?>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- Filter info -->
    <?php if ($batch_filter): ?>
      <div class="filter-info">
        Showing products from suppliers: <strong><?= htmlspecialchars($batch_filter) ?></strong>
        <a href="index.php?page=view_stock" class="clear-btn">[Clear Filter]</a>
      </div>
    <?php endif; ?>

    <!-- Stock Table -->
    <?php if ($batch_filter): ?>
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Name</th>
            <th>Unit Price (KSh)</th>
            <th>Quantity</th>
            <th>Suppliers</th>
            <th>Date & Time</th>
            <th>Product ID</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($result && $result->num_rows > 0): ?>
            <?php $i = 1; ?>
            <?php while ($row = $result->fetch_assoc()): ?>
              <tr>
                <td><?= $i++ ?></td>
                <td><?= htmlspecialchars($row['product_name']) ?></td>
                <td><?= number_format($row['unit_price'], 2) ?></td>
                <td><?= $row['quantity'] ?></td>
                <td><?= htmlspecialchars($row['batch']) ?></td>
                <td><?= date('Y-m-d H:i', strtotime($row['created_at'])) ?></td>
                <td><?= htmlspecialchars($row['product_id']) ?></td>
                <td>
                  <a href="index.php?page=edit_stock&id=<?= $row['id'] ?>" class="action-btn edit-btn">Edit</a>
                  <a href="delete_stock.php?id=<?= $row['id'] ?>" class="action-btn delete-btn" onclick="return confirm('Are you sure you want to delete this record?')">Delete</a>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="8" class="center">No stock records found for this batch.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="center">🛈 Please select a batch on the left to view its stock records.</div>
    <?php endif; ?>
  </div>
</body>
</html>
