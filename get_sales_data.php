<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "eliam";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Query total sales per product
$sql = "SELECT product_name, SUM(quantity) as total_quantity 
        FROM sales 
        GROUP BY product_name";

$result = $conn->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        "product" => $row['product_name'],
        "quantity" => (int)$row['total_quantity']
    ];
}

echo json_encode($data);
$conn->close();
?>
