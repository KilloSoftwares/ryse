<?php
include 'db_connect.php';

$message = '';

// Fetch unique batch names
$batch_list = [];
$batch_query = $conn->query("SELECT DISTINCT batch FROM stock_in ORDER BY batch ASC");
while ($row = $batch_query->fetch_assoc()) {
    $batch_list[] = $row['batch'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Auto-generate a product ID
    $product_id = uniqid('P'); // Example: P651a7f3e1d3c1

    $product_name = mysqli_real_escape_string($conn, $_POST['product_name']);
    $unit_price   = (float)$_POST['unit_price'];
    $quantity     = (int)$_POST['quantity'];

    // Determine batch: from dropdown or new
    $selected_batch = $_POST['existing_batch'];
    $new_batch      = trim($_POST['new_batch']);
    $batch = !empty($new_batch) ? mysqli_real_escape_string($conn, $new_batch) : mysqli_real_escape_string($conn, $selected_batch);

    $sql = "INSERT INTO stock_in (product_id, product_name, unit_price, quantity, batch)
            VALUES ('$product_id', '$product_name', $unit_price, $quantity, '$batch')";

    if ($conn->query($sql)) {
        $message = "✅ Stock added successfully!";
    } else {
        $message = "❌ Error: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add Stock</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f5f5f5;
      margin: 0;
      padding: 20px;
    }
    .container {
      max-width: 550px;
      margin: 0 auto;
      background: #fff;
      padding: 25px;
      border-radius: 8px;
      box-shadow: 0 0 8px rgba(0,0,0,0.1);
    }
    h2 {
      margin-bottom: 20px;
      text-align: center;
      font-size: 22px;
      color: #333;
    }
    label {
      display: block;
      margin-bottom: 6px;
      font-weight: bold;
    }
    input[type="text"],
    input[type="number"],
    select {
      width: 100%;
      padding: 8px 10px;
      margin-bottom: 15px;
      border: 1px solid #ccc;
      border-radius: 4px;
      font-size: 14px;
    }
    button {
      background-color: green;
      color: white;
      padding: 10px 16px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 15px;
      width: 100%;
    }
    button:hover {
      background-color: darkgreen;
    }
    .message {
      margin-bottom: 15px;
      padding: 10px;
      border-radius: 4px;
      font-size: 14px;
    }
    .success {
      background-color: #d4edda;
      color: #155724;
    }
    .error {
      background-color: #f8d7da;
      color: #721c24;
    }
    .back-link {
      display: block;
      margin-top: 20px;
      text-align: center;
      text-decoration: none;
      color: #007BFF;
      font-size: 14px;
    }
    .back-link:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>➕ Add Stock</h2>

    <?php if ($message): ?>
      <div class="message <?= strpos($message, '✅') !== false ? 'success' : 'error' ?>">
        <?= $message ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="add_stock.php">
      <!-- Hidden auto-generated product_id -->
      <!-- <input type="hidden" name="product_id" value="<?= uniqid('P') ?>"> -->

      <label>Product Name:</label>
      <input type="text" name="product_name" required>

      <label>Unit Price (KSh):</label>
      <input type="number" name="unit_price" step="0.01" required>

      <label>Quantity:</label>
      <input type="number" name="quantity" required>

      <label>Select Existing Supplier:</label>
      <select name="existing_batch">
        <option value="">-- Select Supplier --</option>
        <?php foreach ($batch_list as $b): ?>
          <option value="<?= htmlspecialchars($b) ?>"><?= htmlspecialchars($b) ?></option>
        <?php endforeach; ?>
      </select>

      <label>Or Create New Supplier:</label>
      <input type="text" name="new_batch" placeholder="Supplier Name">

      <button type="submit">Save Stock</button>
    </form>

    <a class="back-link" href="index.php?page=view_stock">← Back to Stock Records</a>
  </div>
</body>
</html>
