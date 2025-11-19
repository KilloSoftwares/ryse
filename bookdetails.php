<?php
// --- Enable errors ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- DB connection ---
$conn = new mysqli("localhost", "root", "", "eliam");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// --- CREATE TABLES IF NOT EXIST ---

// Products
$conn->query("CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category_id INT,
    total_brought INT DEFAULT 0
) ENGINE=InnoDB");

// Categories
$conn->query("CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    quantity INT DEFAULT 0
) ENGINE=InnoDB");

// Cash Book
$conn->query("CREATE TABLE IF NOT EXISTS cash_book (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    particulars VARCHAR(255),
    receipt DECIMAL(10,2) DEFAULT 0,
    payment DECIMAL(10,2) DEFAULT 0,
    balance DECIMAL(10,2) DEFAULT 0
) ENGINE=InnoDB");

// Petty Cash Book
$conn->query("CREATE TABLE IF NOT EXISTS petty_cash_book (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    particulars VARCHAR(255),
    receipt DECIMAL(10,2) DEFAULT 0,
    payment DECIMAL(10,2) DEFAULT 0,
    balance DECIMAL(10,2) DEFAULT 0
) ENGINE=InnoDB");

// Purchase Book
$conn->query("CREATE TABLE IF NOT EXISTS purchase_book (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    supplier VARCHAR(255),
    product_id INT,
    quantity INT DEFAULT 0,
    total_amount DECIMAL(10,2) DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB");

// Sales Book
$conn->query("CREATE TABLE IF NOT EXISTS sales_book (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    customer VARCHAR(255),
    product_id INT,
    quantity INT DEFAULT 0,
    total_amount DECIMAL(10,2) DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB");

// Stock Book
$conn->query("CREATE TABLE IF NOT EXISTS stock_book (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT,
    date DATE NOT NULL,
    stock_in INT DEFAULT 0,
    stock_out INT DEFAULT 0,
    balance INT DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB");

// --- INSERT REALISTIC DATA IF EMPTY ---

// Categories
$conn->query("INSERT IGNORE INTO categories (id, name, quantity) VALUES
    (1, 'Nails', 1000),
    (2, 'Screws', 500),
    (3, 'Wood', 200)");

// Products
$conn->query("INSERT IGNORE INTO products (id, name, category_id, total_brought) VALUES
    (1, 'Small Nails', 1, 0),
    (2, 'Large Screws', 2, 0),
    (3, 'Plywood', 3, 0)");

// Cash Book
$conn->query("INSERT IGNORE INTO cash_book (id, date, particulars, receipt, payment, balance) VALUES
    (1, '2025-08-30', 'Cash sale', 5000, 0, 5000),
    (2, '2025-08-31', 'Payment to supplier', 0, 2000, 3000)");

// Petty Cash Book
$conn->query("INSERT IGNORE INTO petty_cash_book (id, date, particulars, receipt, payment, balance) VALUES
    (1, '2025-08-30', 'Office supplies', 0, 500, 500),
    (2, '2025-08-31', 'Misc income', 200, 0, 700)");

// Purchase Book
$conn->query("INSERT IGNORE INTO purchase_book (id, date, supplier, product_id, quantity, total_amount) VALUES
    (1, '2025-08-30', 'Supplier A', 1, 100, 1000),
    (2, '2025-08-30', 'Supplier B', 2, 50, 750)");

// Sales Book
$conn->query("INSERT IGNORE INTO sales_book (id, date, customer, product_id, quantity, total_amount) VALUES
    (1, '2025-08-30', 'Customer X', 1, 50, 500),
    (2, '2025-08-30', 'Customer Y', 3, 10, 2000)");

// Stock Book
$conn->query("INSERT IGNORE INTO stock_book (id, product_id, date, stock_in, stock_out, balance) VALUES
    (1, 1, '2025-08-30', 100, 50, 50),
    (2, 2, '2025-08-30', 50, 0, 50),
    (3, 3, '2025-08-30', 20, 10, 10)");

// --- DISPLAY BOOKS ---
$book = isset($_GET['book']) ? $_GET['book'] : '';
$valid_books = ['cash_book','petty_cash_book','purchase_book','sales_book','stock_book'];

if($book && in_array($book, $valid_books)){
    if($book == 'purchase_book'){
        $query = "SELECT pu.id, pu.date, pu.supplier, p.name AS product, pu.quantity, pu.total_amount 
                  FROM purchase_book pu
                  JOIN products p ON pu.product_id = p.id
                  ORDER BY pu.date ASC";
    } elseif($book == 'sales_book'){
        $query = "SELECT s.id, s.date, s.customer, p.name AS product, s.quantity, s.total_amount
                  FROM sales_book s
                  JOIN products p ON s.product_id = p.id
                  ORDER BY s.date ASC";
    } elseif($book == 'stock_book'){
        $query = "SELECT sb.id, sb.date, p.name AS product, sb.stock_in, sb.stock_out, sb.balance
                  FROM stock_book sb
                  JOIN products p ON sb.product_id = p.id
                  ORDER BY sb.date ASC";
    } else {
        $query = "SELECT * FROM $book ORDER BY date ASC";
    }
    $result = $conn->query($query);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Record Keeping Books</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h1 class="mb-4 text-center">Record Keeping Books</h1>
    <div class="row g-3 mb-4">
        <?php foreach($valid_books as $b): ?>
        <div class="col-md-4">
            <a href="?book=<?php echo $b; ?>" class="btn btn-primary btn-lg w-100"><?php echo ucfirst(str_replace("_"," ",$b)); ?></a>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if($book && isset($result)): ?>
        <h3 class="mb-3"><?php echo ucfirst(str_replace("_"," ",$book)); ?> Details</h3>
        <?php if($result->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <?php foreach($result->fetch_assoc() as $col => $val): ?>
                            <th><?php echo ucfirst(str_replace("_"," ",$col)); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php $result->data_seek(0); while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <?php foreach($row as $val): ?>
                                <td><?php echo $val; ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <p>No data available for this book.</p>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>
