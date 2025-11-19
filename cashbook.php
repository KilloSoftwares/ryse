<?php
// --- Enable errors ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- DB connection ---
$conn = new mysqli("localhost", "root", "", "eliam");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// --- Handle form submission for new cash book entry ---
if(isset($_POST['add_entry'])){
    $date = $_POST['date'];
    $particulars = $_POST['particulars'];
    $receipt = floatval($_POST['receipt']);
    $payment = floatval($_POST['payment']);
    
    // Get last balance
    $last_balance = 0;
    $res = $conn->query("SELECT balance FROM cash_book ORDER BY date DESC, id DESC LIMIT 1");
    if($res->num_rows > 0){
        $last_balance = $res->fetch_assoc()['balance'];
    }

    $balance = $last_balance + $receipt - $payment;

    // Insert entry
    $stmt = $conn->prepare("INSERT INTO cash_book (date, particulars, receipt, payment, balance) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssddd", $date, $particulars, $receipt, $payment, $balance);
    $stmt->execute();
    $stmt->close();

    header("Location: cash_book.php");
    exit;
}

// --- Fetch cash book entries ---
$result = $conn->query("SELECT * FROM cash_book ORDER BY date ASC, id ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cash Book</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h1 class="mb-4 text-center">Cash Book</h1>
    
    <!-- Form to add new entry -->
    <div class="card mb-4">
        <div class="card-header">Add New Entry</div>
        <div class="card-body">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-3">
                        <input type="date" name="date" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="particulars" placeholder="Particulars" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <input type="number" step="0.01" name="receipt" placeholder="Receipt" class="form-control" value="0">
                    </div>
                    <div class="col-md-2">
                        <input type="number" step="0.01" name="payment" placeholder="Payment" class="form-control" value="0">
                    </div>
                    <div class="col-md-1">
                        <button type="submit" name="add_entry" class="btn btn-success w-100">Add</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Display cash book entries -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Date</th>
                    <th>Particulars</th>
                    <th>Receipt</th>
                    <th>Payment</th>
                    <th>Balance</th>
                </tr>
            </thead>
            <tbody>
                <?php if($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo $row['date']; ?></td>
                            <td><?php echo $row['particulars']; ?></td>
                            <td><?php echo number_format($row['receipt'], 2); ?></td>
                            <td><?php echo number_format($row['payment'], 2); ?></td>
                            <td><?php echo number_format($row['balance'], 2); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center">No entries found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <a href="index.php" class="btn btn-secondary mt-3">Back to Books</a>
</div>
</body>
</html>
