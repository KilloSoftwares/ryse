<?php
include 'db_connect.php';

// Generate next order number
$prefix = "ELIAM";
$result = $conn->query("SELECT order_no FROM orders ORDER BY id DESC LIMIT 1");

if($result->num_rows > 0){
    $last = $result->fetch_assoc()['order_no'];
    $num = (int) filter_var($last, FILTER_SANITIZE_NUMBER_INT) + 1;
} else {
    $num = 1;
}
$order_no = $prefix . str_pad($num, 5, "0", STR_PAD_LEFT);

// Example insert
$customer_id = $_POST['customer_id'];
$total       = $_POST['total'];

$sql = "INSERT INTO orders (order_no, customer_id, total) 
        VALUES ('$order_no', '$customer_id', '$total')";

if ($conn->query($sql) === TRUE) {
    echo "New order created successfully. Order No: " . $order_no;
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>
