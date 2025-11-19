<?php phpinfo(); ?>

<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('db_connect.php');
$message = "";

// ------------------------------
// ADD NEW DISCOUNT
// ------------------------------
if (isset($_POST['add_discount'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $amount = floatval($_POST['amount']);
    $start_date = $conn->real_escape_string($_POST['start_date']);
    $end_date = $conn->real_escape_string($_POST['end_date']);

    $conn->query("INSERT INTO fixed_discounts (name, amount, start_date, end_date) 
                  VALUES ('$name', '$amount', '$start_date', '$end_date')");
    $message = "✅ Fixed discount added successfully!";
}

// ------------------------------
// APPLY DISCOUNT TO PRODUCT
// ------------------------------
if (isset($_POST['apply_discount'])) {
    $product_id = intval($_POST['product_id']);
    $discount_id = intval($_POST['discount_id']);

    // Get product price
    $prod = $conn->query("SELECT price FROM products WHERE id = '$product_id'")->fetch_assoc();
    $price = floatval($prod['price']);

    // Get discount amount
    $disc = $conn->query("SELECT amount FROM fixed_discounts WHERE id = '$discount_id'")->fetch_assoc();
    $amount = floatval($disc['amount']);

    // Calculate new price
    $new_price = max($price - $amount, 0);

    // Save applied discount
    $conn->query("INSERT INTO product_fixed_discounts (product_id, discount_id, discounted_price, applied_at)
                  VALUES ('$product_id', '$discount_id', '$new_price', NOW())");

    $message = "✅ Discount applied successfully!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fixed Discounts & Orders</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-4 mb-5">
    <?php if (!empty($message)): ?>
        <div class="alert alert-success text-center font-weight-bold"><?php echo $message; ?></div>
    <?php endif; ?>

    <!-- ADD FIXED DISCOUNT -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-primary text-white"><b>Add Fixed Discount</b></div>
        <div class="card-body">
            <form method="post" autocomplete="off">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Discount Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Easter Sale" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Discount Amount (KES)</label>
                        <input type="number" step="0.01" name="amount" class="form-control" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Start Date</label>
                        <input type="date" name="start_date" class="form-control" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label>End Date</label>
                        <input type="date" name="end_date" class="form-control" required>
                    </div>
                </div>
                <button class="btn btn-success btn-block" name="add_discount">Add Discount</button>
            </form>
        </div>
    </div>

    <!-- APPLY DISCOUNTS TO PRODUCTS -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-secondary text-white"><b>Apply Fixed Discounts to Products</b></div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="thead-dark">
                    <tr>
                        <th>#</th>
                        <th>Category</th>
                        <th>Product</th>
                        <th>Original Price (KES)</th>
                        <th>Apply Discount</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $i = 1;
                $products = $conn->query("SELECT p.*, c.name AS category_name 
                                          FROM products p 
                                          LEFT JOIN categories c ON p.category_id = c.id 
                                          ORDER BY p.id ASC");
                $discounts = $conn->query("SELECT * FROM fixed_discounts ORDER BY id DESC");

                if ($products->num_rows > 0):
                    while ($row = $products->fetch_assoc()):
                ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo number_format($row['price'], 2); ?></td>
                        <td>
                            <form method="post" class="form-inline">
                                <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                                <select name="discount_id" class="form-control mr-2" required>
                                    <option value="">Select Discount</option>
                                    <?php
                                    $discounts->data_seek(0);
                                    while ($d = $discounts->fetch_assoc()):
                                        echo "<option value='{$d['id']}'>" . htmlspecialchars($d['name']) . " - KES " . number_format($d['amount'],2) . "</option>";
                                    endwhile;
                                    ?>
                                </select>
                                <button class="btn btn-success btn-sm" name="apply_discount">Apply</button>
                            </form>
                        </td>
                    </tr>
                <?php
                    endwhile;
                else:
                    echo "<tr><td colspan='5' class='text-center text-muted'>No products found.</td></tr>";
                endif;
                ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- LIST APPLIED DISCOUNTS -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-warning"><b>Applied Discounts</b></div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="thead-dark">
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th>Discount</th>
                        <th>Discounted Price (KES)</th>
                        <th>Applied At</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $i = 1;
                $applied = $conn->query("
                    SELECT pd.discounted_price, pd.applied_at, 
                           p.name AS product_name, d.name AS discount_name
                    FROM product_fixed_discounts pd
                    LEFT JOIN products p ON pd.product_id = p.id
                    LEFT JOIN fixed_discounts d ON pd.discount_id = d.id
                    ORDER BY pd.id DESC
                ");

                if ($applied->num_rows > 0):
                    while ($row = $applied->fetch_assoc()):
                ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['discount_name']); ?></td>
                        <td><?php echo number_format($row['discounted_price'], 2); ?></td>
                        <td><?php echo date("Y-m-d H:i", strtotime($row['applied_at'])); ?></td>
                    </tr>
                <?php
                    endwhile;
                else:
                    echo "<tr><td colspan='5' class='text-center text-muted'>No discounts applied yet.</td></tr>";
                endif;
                ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ORDERS SUMMARY -->
    <div class="card shadow-sm">
        <div class="card-header bg-info text-white"><b>Orders Summary (Payments & Discounts)</b></div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="thead-dark">
                    <tr>
                        <th>#</th>
                        <th>Order No.</th>
                        <th>Amount Paid (KES)</th>
                        <th>Discount / Change (KES)</th>
                        <th>Total Amount (KES)</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $i = 1;
                $orders = $conn->query("SELECT id, order_number, amount_tendered, `change`, total_amount, date_created 
                                        FROM orders ORDER BY id DESC");

                if ($orders->num_rows > 0):
                    while ($row = $orders->fetch_assoc()):
                ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><?php echo htmlspecialchars($row['order_number']); ?></td>
                        <td><?php echo number_format($row['amount_tendered'], 2); ?></td>
                        <td><?php echo number_format($row['change'], 2); ?></td>
                        <td><?php echo number_format($row['total_amount'], 2); ?></td>
                        <td><?php echo date("Y-m-d H:i", strtotime($row['date_created'])); ?></td>
                    </tr>
                <?php
                    endwhile;
                else:
                    echo "<tr><td colspan='6' class='text-center text-muted'>No orders found.</td></tr>";
                endif;
                ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
